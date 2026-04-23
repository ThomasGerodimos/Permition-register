<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Csrf};
use App\Models\{User, Permission};
use App\Services\AdService;

class UserController
{
    public function index(): void
    {
        Middleware::requireLogin();

        $userModel = new User();
        $perPage   = 12;
        $page      = max(1, (int)($_GET['page'] ?? 1));

        $filters = [
            'search'     => trim($_GET['search']     ?? ''),
            'department' => trim($_GET['department']  ?? ''),
        ];

        // Managers see only their department
        if (Session::isManager() && !Session::isTypeAdmin()) {
            $filters['department'] = Session::department();
        }

        // Type-admins see only users with permissions for their resource types
        if (!Session::isAdmin() && Session::isTypeAdmin()) {
            $filters['type_ids'] = Session::getTypeAdminTypes();
        }

        $result      = $userModel->getList($filters, $page, $perPage);
        $departments = $userModel->getDepartments();

        View::render('permissions/users', [
            'pageTitle'   => 'Χρήστες',
            'result'      => $result,
            'filters'     => $filters,
            'departments' => $departments,
        ]);
    }

    public function show(string $id): void
    {
        Middleware::requireLogin();

        $userModel = new User();
        $user = $userModel->findById((int)$id);

        if (!$user) {
            Session::flash('error', 'Ο χρήστης δεν βρέθηκε.');
            View::redirect('/users');
        }

        // Managers can only view their department (unless type-admin)
        if (Session::isManager() && !Session::isTypeAdmin() && $user['department'] !== Session::department()) {
            http_response_code(403);
            View::render('errors/403', [], false);
            return;
        }

        $permModel = new Permission();
        $permissions = $permModel->getByUser((int)$id);

        // Type-admins: filter permissions to only their resource types
        if (!Session::isAdmin() && Session::isTypeAdmin()) {
            $allowedTypeIds = Session::getTypeAdminTypes();
            $permissions = array_values(array_filter($permissions, function($p) use ($allowedTypeIds) {
                return in_array((int)($p['resource_type_id'] ?? 0), $allowedTypeIds, true);
            }));
        }

        View::render('permissions/user_view', [
            'pageTitle'   => 'Δικαιώματα: ' . ($user['full_name'] ?? $user['username']),
            'user'        => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Import a single new user from AD into the local DB.
     * Admin only.
     */
    public function importFromAd(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            Session::flash('error', 'Δώστε username.');
            View::redirect('/users');
        }

        $adService = new AdService();
        $adUser    = $adService->fetchAndSync($username);

        if (!$adUser) {
            Session::flash('error', 'Ο χρήστης «' . htmlspecialchars($username) . '» δεν βρέθηκε στο Active Directory.');
            View::redirect('/users');
        }

        $name = htmlspecialchars($adUser['full_name'] ?? $username);
        Session::flash('success', "Ο χρήστης <strong>{$name}</strong> εισήχθη επιτυχώς από το AD.");
        View::redirect('/users');
    }

    /**
     * Bulk AD sync — updates all local users from Active Directory.
     * Admin only.
     */
    public function syncAd(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $userModel = new User();
        $adService = new AdService();

        $allUsers = $userModel->getAll();
        $synced   = 0;
        $failed   = 0;
        $errors   = [];

        foreach ($allUsers as $u) {
            try {
                $result = $adService->fetchAndSync($u['username']);
                if ($result !== false) {
                    $synced++;
                } else {
                    $failed++;
                    $errors[] = $u['username'] . ' (δεν βρέθηκε στο AD)';
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $u['username'] . ' (' . $e->getMessage() . ')';
            }
        }

        $msg = "Συγχρονισμός AD: {$synced} ενημερώθηκαν";
        if ($failed > 0) {
            $msg .= ", {$failed} απέτυχαν";
            if (!empty($errors)) {
                $msg .= ' — ' . implode(', ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $msg .= '...';
                }
            }
        }

        Session::flash($failed > 0 ? 'warning' : 'success', $msg);
        View::redirect('/users');
    }
}
