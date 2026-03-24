<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Database, Csrf};
use App\Models\{Resource, Permission, AuditLog};

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

        View::render('settings/index', [
            'pageTitle'      => 'Ρυθμίσεις',
            'ipRestrictions' => $ipRestrictions,
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
        Middleware::requireAdmin();

        $resModel = new Resource();
        $types    = $resModel->getAllTypes();

        $perPage = 5;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'search'  => trim($_GET['search']  ?? ''),
            'type_id' => (int)($_GET['type_id'] ?? 0) ?: null,
        ];

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
        Middleware::requireAdmin();

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
        Middleware::requireAdmin();

        $resModel = new Resource();
        $resource = $resModel->findById((int)$id);

        if (!$resource) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

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
        Middleware::requireAdmin();
        Csrf::check();

        $data = [
            'resource_type_id' => (int)($_POST['resource_type_id'] ?? 0),
            'name'             => trim($_POST['name']        ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'location'         => trim($_POST['location']    ?? ''),
        ];

        if (empty($data['name']) || empty($data['resource_type_id'])) {
            Session::flash('error', 'Συμπληρώστε όνομα και τύπο πόρου.');
            View::redirect('/resources');
        }

        $id = (new Resource())->create($data);
        (new AuditLog())->log('create', 'resources', $id, [], $data, 'Νέος πόρος: ' . $data['name']);

        Session::flash('success', 'Ο πόρος δημιουργήθηκε.');
        View::redirect('/resources');
    }

    public function updateResource(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $resModel = new Resource();
        $old = $resModel->findById((int)$id);

        if (!$old) {
            Session::flash('error', 'Ο πόρος δεν βρέθηκε.');
            View::redirect('/resources');
        }

        $data = [
            'resource_type_id' => (int)($_POST['resource_type_id'] ?? 0),
            'name'             => trim($_POST['name']        ?? ''),
            'description'      => trim($_POST['description'] ?? ''),
            'location'         => trim($_POST['location']    ?? ''),
        ];

        if (empty($data['name']) || empty($data['resource_type_id'])) {
            Session::flash('error', 'Συμπληρώστε όνομα και τύπο πόρου.');
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
        Middleware::requireAdmin();
        Csrf::check();

        $resModel = new Resource();
        $res = $resModel->findById((int)$id);
        if ($res) {
            $resModel->delete((int)$id);
            (new AuditLog())->log('delete', 'resources', (int)$id, $res, [],
                'Διαγραφή πόρου: ' . $res['name']);
        }

        Session::flash('success', 'Ο πόρος διαγράφηκε.');
        View::redirect('/resources');
    }

    /** Clone permissions from one resource to another */
    public function clonePermissions(string $id): void
    {
        Middleware::requireAdmin();
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
