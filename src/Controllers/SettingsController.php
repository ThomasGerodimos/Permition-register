<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Database, Csrf};
use App\Models\{Resource, Permission, AuditLog, User};

class SettingsController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Middleware::requireAdmin();

        $ipRestrictions = $this->db->fetchAll(
            'SELECT ip.*, u.full_name AS created_by_name
             FROM ip_restrictions ip
             LEFT JOIN users u ON u.id = ip.created_by
             ORDER BY ip.role, ip.ip_range'
        );

        // Type-admin assignments
        $typeAdmins = $this->db->fetchAll(
            'SELECT uta.*, u.full_name, u.username, u.department,
                    rt.label AS type_label, rt.icon AS type_icon,
                    cb.full_name AS created_by_name
             FROM user_type_admins uta
             JOIN users u ON u.id = uta.user_id
             JOIN resource_types rt ON rt.id = uta.resource_type_id
             LEFT JOIN users cb ON cb.id = uta.created_by
             ORDER BY u.full_name, rt.label'
        );

        $resModel = new Resource();
        $resourceTypes = $resModel->getAllTypes();

        View::render('settings/index', [
            'pageTitle'      => 'Ρυθμίσεις',
            'ipRestrictions' => $ipRestrictions,
            'typeAdmins'     => $typeAdmins,
            'resourceTypes'  => $resourceTypes,
        ]);
    }

    public function storeIp(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $role        = trim($_POST['role']        ?? '');
        $ipRange     = trim($_POST['ip_range']    ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!in_array($role, ['admin', 'manager'], true) || empty($ipRange)) {
            Session::flash('error', 'Συμπληρώστε όλα τα υποχρεωτικά πεδία.');
            View::redirect('/settings');
        }

        $id = $this->db->insert(
            'INSERT INTO ip_restrictions (role, ip_range, description, created_by) VALUES (?, ?, ?, ?)',
            [$role, $ipRange, $description, Session::userId()]
        );

        (new AuditLog())->log('create', 'ip_restrictions', $id, [], compact('role', 'ipRange', 'description'),
            "Νέο IP restriction: $ipRange για $role");

        Session::flash('success', 'Το IP restriction προστέθηκε.');
        View::redirect('/settings');
    }

    public function deleteIp(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $row = $this->db->fetchOne('SELECT * FROM ip_restrictions WHERE id=?', [(int)$id]);
        if ($row) {
            $this->db->execute('DELETE FROM ip_restrictions WHERE id=?', [(int)$id]);
            (new AuditLog())->log('delete', 'ip_restrictions', (int)$id, $row, [],
                'Διαγραφή IP restriction: ' . $row['ip_range']);
        }

        Session::flash('success', 'Το IP restriction διαγράφηκε.');
        View::redirect('/settings');
    }

    public function toggleIp(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $this->db->execute(
            'UPDATE ip_restrictions SET is_active = 1 - is_active WHERE id=?',
            [(int)$id]
        );

        Session::flash('success', 'Η κατάσταση άλλαξε.');
        View::redirect('/settings');
    }

    // ── Resources ─────────────────────────────────────────────────────────────

    public function resources(): void
    {
        Middleware::requirePermissionEditor();

        $resModel = new Resource();
        $types    = $resModel->getAllTypes();

        // Type-admins see only their assigned types
        $typeAdminTypes = null;
        if (!Session::isAdmin() && Session::isTypeAdmin()) {
            $typeAdminTypes = Session::getTypeAdminTypes();
            $types = array_values(array_filter($types, fn($t) => in_array((int)$t['id'], $typeAdminTypes, true)));
        }

        $perPage = 5;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'search'  => trim($_GET['search']  ?? ''),
            'type_id' => (int)($_GET['type_id'] ?? 0) ?: null,
        ];

        // Restrict type_ids for type-admins
        if ($typeAdminTypes !== null) {
            $filters['type_ids'] = $typeAdminTypes;
        }

        $result = $resModel->getList($filters, $page, $perPage);

        View::render('settings/resources', [
            'pageTitle' => 'Διαχείριση Πόρων',
            'result'    => $result,
            'filters'   => $filters,
            'types'     => $types,
        ]);
    }

    public function resourcesByType(string $typeId): void
    {
        Middleware::requirePermissionEditor((int)$typeId);

        $resModel = new Resource();
        $type     = $resModel->getTypeById((int)$typeId);

        if (!$type) {
            Session::flash('error', 'Ο τύπος πόρου δεν βρέθηκε.');
            View::redirect('/resources');
        }

        $perPage = 5;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $allResources = $resModel->getAll(['type_id' => (int)$typeId]);
        $total        = count($allResources);

        // Paginate
        $offset    = ($page - 1) * $perPage;
        $resources = array_slice($allResources, $offset, $perPage);

        // Count permissions per resource + gather users per resource
        $permModel = new Permission();
        foreach ($resources as &$r) {
            $r['permissions'] = $permModel->getByResource($r['id']);
            $r['perm_count']  = count($r['permissions']);
            // Group by level
            $r['grouped'] = [];
            foreach ($r['permissions'] as $p) {
                $r['grouped'][$p['permission_level']][] = $p;
            }
        }

        // Stats from ALL resources (not just current page)
        $allPermsCount = 0;
        $allUsers      = [];
        foreach ($allResources as $ar) {
            $perms = $permModel->getByResource($ar['id']);
            $allPermsCount += count($perms);
            foreach ($perms as $p) $allUsers[$p['user_id']] = true;
        }

        View::render('settings/resources-by-type', [
            'pageTitle'      => $type['label'],
            'type'           => $type,
            'resources'      => $resources,
            'total'          => $total,
            'totalPerms'     => $allPermsCount,
            'totalUsers'     => count($allUsers),
            'page'           => $page,
            'perPage'        => $perPage,
        ]);
    }

    public function resourcePermissions(string $id): void
    {
        $resModel = new Resource();
        $resource = $resModel->findById((int)$id);

        if (!$resource) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        Middleware::requirePermissionEditor((int)$resource['resource_type_id']);

        $permModel   = new Permission();
        $permissions = $permModel->getByResource((int)$id);

        // Group by permission level
        $grouped = [];
        foreach ($permissions as $p) {
            $grouped[$p['permission_level']][] = $p;
        }

        View::render('settings/resource-permissions', [
            'pageTitle'   => 'Δικαιώματα: ' . $resource['name'],
            'resource'    => $resource,
            'permissions' => $permissions,
            'grouped'     => $grouped,
            'allTypes'    => $resModel->getAllTypes(),
        ]);
    }

    public function storeResource(): void
    {
        $typeId = (int)($_POST['resource_type_id'] ?? 0);
        Middleware::requirePermissionEditor($typeId ?: null);
        Csrf::check();

        $data = [
            'resource_type_id' => $typeId,
            'name'             => trim($_POST['name']        ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'location'         => trim($_POST['location']    ?? ''),
            'expires_at'       => trim($_POST['expires_at']  ?? ''),
        ];

        if (empty($data['name']) || empty($data['resource_type_id'])) {
            Session::flash('error', 'Συμπληρώστε όνομα και τύπο πόρου.');
            View::redirect('/resources');
        }

        // Type-admin can only create resources for their types
        if (!Session::isAdmin() && !Session::isTypeAdmin($typeId)) {
            http_response_code(403);
            View::render('errors/403', [], false);
            exit;
        }

        $id = (new Resource())->create($data);
        (new AuditLog())->log('create', 'resources', $id, [], $data, 'Νέος πόρος: ' . $data['name']);

        Session::flash('success', 'Ο πόρος δημιουργήθηκε.');
        View::redirect('/resources');
    }

    public function updateResource(string $id): void
    {
        Csrf::check();

        $resModel = new Resource();
        $old = $resModel->findById((int)$id);

        if (!$old) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        // Check access for the existing resource type
        Middleware::requirePermissionEditor((int)$old['resource_type_id']);

        $data = [
            'resource_type_id' => (int)($_POST['resource_type_id'] ?? 0),
            'name'             => trim($_POST['name']        ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'location'         => trim($_POST['location']    ?? ''),
            'expires_at'       => trim($_POST['expires_at']  ?? ''),
        ];

        if (empty($data['name']) || empty($data['resource_type_id'])) {
            Session::flash('error', 'Συμπληρώστε όνομα και τύπο πόρου.');
            View::redirect('/resources');
        }

        // Type-admin cannot change type to one they don't manage
        if (!Session::isAdmin() && !Session::isTypeAdmin($data['resource_type_id'])) {
            Session::flash('error', 'Δεν μπορείτε να αλλάξετε τον τύπο σε κάποιον που δεν διαχειρίζεστε.');
            View::redirect('/resources');
        }

        $resModel->update((int)$id, $data);
        (new AuditLog())->log('update', 'resources', (int)$id, $old, $data,
            'Ενημέρωση πόρου: ' . $data['name']);

        Session::flash('success', 'Ο πόρος ενημερώθηκε.');
        View::redirect('/resources');
    }

    public function deleteResource(string $id): void
    {
        Csrf::check();

        $resModel = new Resource();
        $res = $resModel->findById((int)$id);

        if (!$res) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        Middleware::requirePermissionEditor((int)$res['resource_type_id']);

        // Count active permissions on this resource
        $permCount = (int)$this->db->fetchColumn(
            'SELECT COUNT(*) FROM permissions WHERE resource_id = ? AND is_active = 1',
            [(int)$id]
        );

        // If permissions exist and user hasn't confirmed, ask for confirmation via AJAX
        if ($permCount > 0 && empty($_POST['confirm_delete'])) {
            // Return JSON for AJAX confirmation
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'confirm'    => true,
                'permCount'  => $permCount,
                'resourceName' => $res['name'],
            ]);
            exit;
        }

        $auditLog = new AuditLog();

        // Deactivate all permissions on this resource
        if ($permCount > 0) {
            $perms = $this->db->fetchAll(
                'SELECT p.*, u.full_name, u.username
                 FROM permissions p
                 JOIN users u ON u.id = p.user_id
                 WHERE p.resource_id = ? AND p.is_active = 1',
                [(int)$id]
            );

            $this->db->execute(
                'UPDATE permissions SET is_active = 0 WHERE resource_id = ? AND is_active = 1',
                [(int)$id]
            );

            foreach ($perms as $p) {
                $auditLog->log('delete', 'permissions', (int)$p['id'], $p, [],
                    'Αυτόματη απενεργοποίηση δικαιώματος λόγω διαγραφής πόρου «' . $res['name'] . '»: '
                    . ($p['full_name'] ?? $p['username']) . ' / ' . $p['permission_level']);
            }
        }

        $resModel->delete((int)$id);
        $auditLog->log('delete', 'resources', (int)$id, $res, [],
            'Διαγραφή πόρου: ' . $res['name'] . ($permCount > 0 ? " (αφαιρέθηκαν $permCount δικαιώματα)" : ''));

        $msg = 'Ο πόρος «' . htmlspecialchars($res['name']) . '» διαγράφηκε.';
        if ($permCount > 0) {
            $msg .= " Απενεργοποιήθηκαν $permCount δικαιώματα.";
        }
        Session::flash('success', $msg);
        View::redirect('/resources');
    }

    /** Bulk delete (soft) permissions from a resource */
    public function bulkDeletePermissions(string $id): void
    {
        Csrf::check();

        $resModel = new Resource();
        $resource = $resModel->findById((int)$id);

        if (!$resource) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        Middleware::requirePermissionEditor((int)$resource['resource_type_id']);

        $permIds = array_map('intval', array_filter($_POST['perm_ids'] ?? []));

        if (empty($permIds)) {
            Session::flash('error', 'Δεν επιλέχθηκαν δικαιώματα.');
            View::redirect("/resources/{$id}/permissions");
            return;
        }

        $permModel = new Permission();
        $auditLog  = new AuditLog();
        $deleted   = 0;

        foreach ($permIds as $permId) {
            $perm = $this->db->fetchOne(
                'SELECT p.*, u.full_name, u.username
                 FROM permissions p
                 JOIN users u ON u.id = p.user_id
                 WHERE p.id = ? AND p.resource_id = ? AND p.is_active = 1',
                [$permId, (int)$id]
            );

            if (!$perm) continue;

            $permModel->delete($permId);
            $auditLog->log('delete', 'permissions', $permId, $perm, [],
                'Μαζική αφαίρεση δικαιώματος: ' . ($perm['full_name'] ?? $perm['username'])
                . ' / ' . $perm['permission_level'] . ' από «' . $resource['name'] . '»');
            $deleted++;
        }

        Session::flash('success', "Αφαιρέθηκαν $deleted δικαιώματα από τον πόρο «" . htmlspecialchars($resource['name']) . "».");
        View::redirect("/resources/{$id}/permissions");
    }

    /** Bulk set/clear expiry date on permissions of a resource */
    public function bulkSetExpiry(string $id): void
    {
        Csrf::check();

        $resModel = new Resource();
        $resource = $resModel->findById((int)$id);

        if (!$resource) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        Middleware::requirePermissionEditor((int)$resource['resource_type_id']);

        $permIds = array_map('intval', array_filter($_POST['perm_ids'] ?? []));

        if (empty($permIds)) {
            Session::flash('error', 'Δεν επιλέχθηκαν δικαιώματα.');
            View::redirect("/resources/{$id}/permissions");
            return;
        }

        $action    = trim($_POST['expiry_action'] ?? 'set');
        $expiresAt = ($action === 'set') ? trim($_POST['expires_at'] ?? '') : null;

        if ($action === 'set' && empty($expiresAt)) {
            Session::flash('error', 'Επιλέξτε ημερομηνία λήξης.');
            View::redirect("/resources/{$id}/permissions");
            return;
        }

        $auditLog = new AuditLog();
        $updated  = 0;

        foreach ($permIds as $permId) {
            $perm = $this->db->fetchOne(
                'SELECT p.*, u.full_name, u.username
                 FROM permissions p
                 JOIN users u ON u.id = p.user_id
                 WHERE p.id = ? AND p.resource_id = ? AND p.is_active = 1',
                [$permId, (int)$id]
            );

            if (!$perm) continue;

            $oldExpiry = $perm['expires_at'];

            $this->db->execute(
                'UPDATE permissions SET expires_at = ? WHERE id = ?',
                [$expiresAt, $permId]
            );

            $description = ($action === 'clear')
                ? 'Αφαίρεση λήξης δικαιώματος: ' . ($perm['full_name'] ?? $perm['username']) . ' / ' . $perm['permission_level']
                : 'Ορισμός λήξης δικαιώματος (' . $expiresAt . '): ' . ($perm['full_name'] ?? $perm['username']) . ' / ' . $perm['permission_level'];

            $auditLog->log('update', 'permissions', $permId,
                ['expires_at' => $oldExpiry],
                ['expires_at' => $expiresAt],
                $description . ' στον πόρο «' . $resource['name'] . '»');

            $updated++;
        }

        $msg = ($action === 'clear')
            ? "Αφαιρέθηκε η λήξη από $updated δικαιώματα"
            : "Ορίστηκε λήξη ($expiresAt) σε $updated δικαιώματα";
        $msg .= ' του πόρου «' . htmlspecialchars($resource['name']) . '».';

        Session::flash('success', $msg);
        View::redirect("/resources/{$id}/permissions");
    }

    /** Clone permissions from one resource to another */
    public function clonePermissions(string $id): void
    {
        Csrf::check();

        $sourceId = (int)$id;
        $targetId = (int)($_POST['target_resource_id'] ?? 0);
        $keepExpiry = !empty($_POST['keep_expiry']);
        $keepNotes  = !empty($_POST['keep_notes']);

        $resModel  = new Resource();
        $permModel = new Permission();
        $auditLog  = new AuditLog();

        $source = $resModel->findById($sourceId);
        $target = $resModel->findById($targetId);

        if (!$source || !$target) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        // Check access for both source and target
        Middleware::requirePermissionEditor((int)$source['resource_type_id']);
        if (!Session::isAdmin() && !Session::isTypeAdmin((int)$target['resource_type_id'])) {
            http_response_code(403);
            View::render('errors/403', [], false);
            exit;
        }

        if ($sourceId === $targetId) {
            Session::flash('error', 'Δεν μπορείτε να αντιγράψετε δικαιώματα στον ίδιο πόρο.');
            View::redirect("/resources/$sourceId/permissions");
        }

        // Get source permissions
        $sourcePerms = $permModel->getByResource($sourceId);

        // Get existing target permissions to avoid duplicates
        $targetPerms = $permModel->getByResource($targetId);
        $existingKeys = [];
        foreach ($targetPerms as $tp) {
            $existingKeys[$tp['user_id'] . '|' . $tp['permission_level']] = true;
        }

        $created = 0;
        $skipped = 0;

        foreach ($sourcePerms as $perm) {
            $key = $perm['user_id'] . '|' . $perm['permission_level'];

            // Skip if already exists
            if (isset($existingKeys[$key])) {
                $skipped++;
                continue;
            }

            $newData = [
                'user_id'          => $perm['user_id'],
                'resource_id'      => $targetId,
                'permission_level' => $perm['permission_level'],
                'granted_by'       => Session::userId(),
                'notes'            => $keepNotes ? $perm['notes'] : null,
                'expires_at'       => $keepExpiry ? $perm['expires_at'] : null,
            ];

            $newId = $permModel->create($newData);

            $auditLog->log('create', 'permissions', $newId, [], $newData,
                'Αντιγραφή δικαιώματος από ' . $source['name'] . ' → ' . $target['name']
                . ' (' . ($perm['full_name'] ?? $perm['username']) . ' / ' . $perm['permission_level'] . ')');

            $created++;
        }

        $msg = "Αντιγράφηκαν $created δικαιώματα στο «{$target['name']}».";
        if ($skipped > 0) {
            $msg .= " ($skipped παραλείφθηκαν γιατί υπήρχαν ήδη.)";
        }

        Session::flash('success', $msg);
        View::redirect("/resources/$targetId/permissions");
    }

    // ── Type Admins (Διαχειριστές Τύπου Πόρου) ─────────────────────────────

    public function storeTypeAdmin(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $username       = trim($_POST['username'] ?? '');
        $resourceTypeId = (int)($_POST['resource_type_id'] ?? 0);

        if (empty($username) || empty($resourceTypeId)) {
            Session::flash('error', 'Συμπληρώστε χρήστη και τύπο πόρου.');
            View::redirect('/settings');
        }

        // Find user
        $userModel = new User();
        $user = $userModel->findByUsername($username);
        if (!$user) {
            Session::flash('error', 'Ο χρήστης "' . htmlspecialchars($username) . '" δεν βρέθηκε.');
            View::redirect('/settings');
        }

        // Check for duplicate
        $existing = $this->db->fetchOne(
            'SELECT id FROM user_type_admins WHERE user_id = ? AND resource_type_id = ?',
            [$user['id'], $resourceTypeId]
        );
        if ($existing) {
            Session::flash('error', 'Ο χρήστης είναι ήδη διαχειριστής αυτού του τύπου πόρου.');
            View::redirect('/settings');
        }

        $id = $this->db->insert(
            'INSERT INTO user_type_admins (user_id, resource_type_id, created_by) VALUES (?, ?, ?)',
            [$user['id'], $resourceTypeId, Session::userId()]
        );

        $resModel = new Resource();
        $type = $resModel->getTypeById($resourceTypeId);

        (new AuditLog())->log('create', 'user_type_admins', $id, [],
            ['user_id' => $user['id'], 'resource_type_id' => $resourceTypeId],
            'Ανάθεση type admin: ' . ($user['full_name'] ?? $username) . ' → ' . ($type['label'] ?? ''));

        Session::flash('success', 'Ο χρήστης <strong>' . htmlspecialchars($user['full_name'] ?? $username) . '</strong> ορίστηκε ως διαχειριστής τύπου «' . htmlspecialchars($type['label'] ?? '') . '».');
        View::redirect('/settings');
    }

    public function deleteTypeAdmin(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $row = $this->db->fetchOne(
            'SELECT uta.*, u.full_name, rt.label AS type_label
             FROM user_type_admins uta
             JOIN users u ON u.id = uta.user_id
             JOIN resource_types rt ON rt.id = uta.resource_type_id
             WHERE uta.id = ?',
            [(int)$id]
        );

        if ($row) {
            $this->db->execute('DELETE FROM user_type_admins WHERE id = ?', [(int)$id]);
            (new AuditLog())->log('delete', 'user_type_admins', (int)$id, $row, [],
                'Αφαίρεση type admin: ' . ($row['full_name'] ?? '') . ' → ' . ($row['type_label'] ?? ''));
        }

        Session::flash('success', 'Η ανάθεση αφαιρέθηκε.');
        View::redirect('/settings');
    }

    /** Serve the documentation DOCX file (admin or Υποδ. Ψηφιακής Διακυβέρνησης) */
    public function downloadDoc(): void
    {
        Middleware::requireLogin();

        if (Session::realRole() !== 'admin' && Session::department() !== 'Υποδιεύθυνση Ψηφιακής Διακυβέρνησης') {
            http_response_code(403);
            View::render('errors/403', [], false);
            exit;
        }

        $filePath = ROOT_PATH . '/docs/DOCUMENTATION.docx';

        if (!is_file($filePath)) {
            Session::flash('error', 'Το αρχείο τεκμηρίωσης δεν βρέθηκε.');
            View::redirect('/dashboard');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="DOCUMENTATION.docx"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }
}
