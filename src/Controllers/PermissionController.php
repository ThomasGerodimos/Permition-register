<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Csrf};
use App\Models\{Permission, User, Resource, AuditLog};

class PermissionController
{
    public function index(): void
    {
        Middleware::requireLogin();

        $permModel = new Permission();
        $userModel = new User();
        $resModel  = new Resource();

        $perPage = 10;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'search'           => trim($_GET['search']           ?? ''),
            'department'       => trim($_GET['department']       ?? ''),
            'type_id'          => (int)($_GET['type_id']         ?? 0) ?: null,
            'permission_level' => trim($_GET['permission_level'] ?? ''),
            'user_id'          => (int)($_GET['user_id']         ?? 0) ?: null,
        ];

        // Managers see only their department
        if (Session::isManager()) {
            $filters['department'] = Session::department();
        }

        $result      = $permModel->getList($filters, $page, $perPage);
        $departments = $userModel->getDepartments();
        $types       = $resModel->getAllTypes();

        View::render('permissions/index', [
            'pageTitle'   => 'Δικαιώματα Πρόσβασης',
            'result'      => $result,
            'filters'     => $filters,
            'departments' => $departments,
            'types'       => $types,
        ]);
    }

    public function create(): void
    {
        Middleware::requireAdmin();

        $resModel = new Resource();
        $types    = $resModel->getAllTypes();
        $resources = $resModel->getAll();

        View::render('permissions/form', [
            'pageTitle' => 'Νέο Δικαίωμα',
            'permission' => null,
            'types'     => $types,
            'resources' => $resources,
            'action'    => 'create',
        ]);
    }

    public function store(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $data = $this->extractFormData();
        $errors = $this->validate($data);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            View::redirect('/permissions/create');
        }

        // Ensure user exists in DB
        $userModel = new User();
        $user = $userModel->findByUsername($data['username']);
        if (!$user) {
            Session::flash('error', 'Ο χρήστης "' . htmlspecialchars($data['username']) . '" δεν βρέθηκε.');
            View::redirect('/permissions/create');
        }
        $data['user_id']    = $user['id'];
        $data['granted_by'] = Session::userId();

        $permModel = new Permission();
        $id = $permModel->create($data);

        // Audit log
        (new AuditLog())->log('create', 'permissions', $id, [], $data,
            'Προσθήκη δικαιώματος για ' . $user['full_name']);

        Session::flash('success', 'Το δικαίωμα καταχωρήθηκε επιτυχώς.');
        View::redirect('/permissions');
    }

    public function edit(string $id): void
    {
        Middleware::requireAdmin();

        $permModel = new Permission();
        $permission = $permModel->findById((int)$id);

        if (!$permission) {
            Session::flash('error', 'Το δικαίωμα δεν βρέθηκε.');
            View::redirect('/permissions');
        }

        $resModel  = new Resource();
        $types     = $resModel->getAllTypes();
        $resources = $resModel->getAll();

        View::render('permissions/form', [
            'pageTitle'  => 'Επεξεργασία Δικαιώματος',
            'permission' => $permission,
            'types'      => $types,
            'resources'  => $resources,
            'action'     => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $permModel = new Permission();
        $old = $permModel->findById((int)$id);

        if (!$old) {
            Session::flash('error', 'Το δικαίωμα δεν βρέθηκε.');
            View::redirect('/permissions');
        }

        $data   = $this->extractFormData();
        $errors = $this->validate($data, false);

        if ($errors) {
            Session::flash('error', implode('<br>', $errors));
            View::redirect('/permissions/' . $id . '/edit');
        }

        $permModel->update((int)$id, $data);

        (new AuditLog())->log('update', 'permissions', (int)$id, $old, $data,
            'Ενημέρωση δικαιώματος για ' . ($old['full_name'] ?? '') . ' → ' . ($old['resource_name'] ?? ''));

        Session::flash('success', 'Το δικαίωμα ενημερώθηκε.');
        View::redirect('/permissions');
    }

    public function delete(string $id): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $permModel = new Permission();
        $old = $permModel->findById((int)$id);

        if (!$old) {
            Session::flash('error', 'Το δικαίωμα δεν βρέθηκε.');
            View::redirect('/permissions');
        }

        $permModel->delete((int)$id);

        (new AuditLog())->log('delete', 'permissions', (int)$id, $old, [],
            'Διαγραφή δικαιώματος για ' . ($old['full_name'] ?? ''));

        Session::flash('success', 'Το δικαίωμα διαγράφηκε.');
        View::redirect('/permissions');
    }

    // ── Bulk Assignment ─────────────────────────────────────────────────────

    public function bulk(): void
    {
        Middleware::requireAdmin();

        $resModel  = new Resource();
        $types     = $resModel->getAllTypes();
        $resources = $resModel->getAll();

        View::render('permissions/bulk-form', [
            'pageTitle' => 'Μαζική Ανάθεση Δικαιωμάτων',
            'types'     => $types,
            'resources' => $resources,
        ]);
    }

    public function bulkStore(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        // Support both single resource_id (legacy) and multiple resource_ids[]
        $resourceIds = $_POST['resource_ids'] ?? [];
        if (!is_array($resourceIds)) {
            $resourceIds = [];
        }
        // Fallback: single resource_id for backward compatibility
        if (empty($resourceIds) && !empty($_POST['resource_id'])) {
            $resourceIds = [(int)$_POST['resource_id']];
        }
        $resourceIds = array_filter(array_map('intval', $resourceIds));

        $permissionLevel = trim($_POST['permission_level'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');
        $expiresAt       = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $usernames       = json_decode($_POST['usernames'] ?? '[]', true);

        // Validate
        if (empty($resourceIds) || !$permissionLevel || empty($usernames)) {
            Session::flash('error', 'Συμπληρώστε πόρο(ους), δικαίωμα και τουλάχιστον έναν χρήστη.');
            View::redirect('/permissions/bulk');
        }

        $userModel = new User();
        $permModel = new Permission();
        $auditLog  = new AuditLog();
        $resModel  = new Resource();
        $db        = \App\Core\Database::getInstance();
        $grantedBy = Session::userId();

        $created  = 0;
        $skipped  = 0;
        $notFound = [];

        // Pre-fetch resource names for audit log
        $resourceNames = [];
        foreach ($resourceIds as $rid) {
            $r = $resModel->findById($rid);
            if ($r) $resourceNames[$rid] = $r['name'];
        }

        $db->beginTransaction();
        try {
            foreach ($usernames as $username) {
                $username = trim($username);
                if (!$username) continue;

                // Find user in local DB
                $user = $userModel->findByUsername($username);

                // Try to sync from AD if not found locally
                if (!$user) {
                    try {
                        (new \App\Services\AdService())->fetchAndSync($username);
                        $user = $userModel->findByUsername($username);
                    } catch (\Throwable $e) {
                        // AD lookup failed
                    }
                }

                if (!$user) {
                    $notFound[] = $username;
                    continue;
                }

                // Loop through each selected resource
                foreach ($resourceIds as $resourceId) {
                    $data = [
                        'user_id'          => $user['id'],
                        'resource_id'      => $resourceId,
                        'permission_level' => $permissionLevel,
                        'granted_by'       => $grantedBy,
                        'notes'            => $notes,
                        'expires_at'       => $expiresAt,
                    ];

                    try {
                        $id = $permModel->create($data);
                        $auditLog->log('create', 'permissions', $id, [], $data,
                            'Μαζική ανάθεση: ' . ($user['full_name'] ?? $username)
                            . ' → ' . ($resourceNames[$resourceId] ?? "#{$resourceId}")
                            . ' (' . $permissionLevel . ')');
                        $created++;
                    } catch (\PDOException $e) {
                        // Duplicate — already has this exact permission
                        if ($e->getCode() == '23000') {
                            $skipped++;
                        } else {
                            throw $e;
                        }
                    }
                }
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την μαζική ανάθεση: ' . $e->getMessage());
            View::redirect('/permissions/bulk');
        }

        // Build summary message
        $resCount  = count($resourceIds);
        $userCount = count($usernames) - count($notFound);
        $msg = "<strong>{$created}</strong> δικαιώματα ανατέθηκαν επιτυχώς";
        $msg .= " ({$userCount} χρήστες × {$resCount} πόροι).";
        if ($skipped) {
            $msg .= " <span class='text-warning'>{$skipped} παραλείφθηκαν (υπάρχουν ήδη).</span>";
        }
        if ($notFound) {
            $msg .= " <span class='text-danger'>Δεν βρέθηκαν: " . implode(', ', $notFound) . "</span>";
        }

        Session::flash('success', $msg);
        View::redirect('/permissions');
    }

    private function extractFormData(): array
    {
        return [
            'username'         => trim($_POST['username']         ?? ''),
            'resource_id'      => (int)($_POST['resource_id']    ?? 0),
            'permission_level' => trim($_POST['permission_level'] ?? ''),
            'notes'            => trim($_POST['notes']            ?? ''),
            'expires_at'       => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        ];
    }

    private function validate(array $data, bool $requireUsername = true): array
    {
        $errors = [];
        if ($requireUsername && empty($data['username'])) {
            $errors[] = 'Το πεδίο χρήστης είναι υποχρεωτικό.';
        }
        if (empty($data['resource_id'])) {
            $errors[] = 'Παρακαλώ επιλέξτε πόρο.';
        }
        if (empty($data['permission_level'])) {
            $errors[] = 'Παρακαλώ επιλέξτε επίπεδο δικαιώματος.';
        }
        return $errors;
    }
}
