<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Csrf};
use App\Models\{User, Permission, AuditLog};
use DateTime;

class OffboardingController
{
    /** GET /offboarding */
    public function index(): void
    {
        Middleware::requireAdmin();

        View::render('offboarding/index', [
            'pageTitle' => 'Αποχώρηση Υπαλλήλου',
        ]);
    }

    /** GET /offboarding/preview?user_id=X  (AJAX) */
    public function preview(): void
    {
        Middleware::requireAdmin();

        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            View::json(['error' => 'Missing user_id'], 400);
            return;
        }

        $userModel = new User();
        $user      = $userModel->findById($userId);
        if (!$user) {
            View::json(['error' => 'Ο χρήστης δεν βρέθηκε.'], 404);
            return;
        }

        $permModel   = new Permission();
        $permissions = $permModel->getByUser($userId);

        View::json([
            'user' => [
                'id'         => (int)$user['id'],
                'username'   => $user['username'],
                'full_name'  => $user['full_name']  ?? '',
                'email'      => $user['email']      ?? '',
                'department' => $user['department'] ?? '',
                'job_title'  => $user['job_title']  ?? '',
            ],
            'permissions' => array_map(function (array $p): array {
                return [
                    'id'               => (int)$p['id'],
                    'resource_name'    => $p['resource_name'],
                    'type_label'       => $p['type_label'],
                    'type_icon'        => $p['type_icon'],
                    'permission_level' => $p['permission_level'],
                    'expires_at'       => $p['expires_at'] ?? null,
                ];
            }, $permissions),
        ]);
    }

    /** POST /offboarding/apply */
    public function apply(): void
    {
        Middleware::requireAdmin();
        Csrf::check();

        $userId    = (int)($_POST['user_id']   ?? 0);
        $expiresAt = trim($_POST['expires_at'] ?? '');

        if (!$userId || !$expiresAt) {
            Session::flash('error', 'Παρακαλώ επιλέξτε υπάλληλο και ημερομηνία λήξης.');
            View::redirect('/offboarding');
        }

        $dt = DateTime::createFromFormat('Y-m-d', $expiresAt);
        if (!$dt || $dt->format('Y-m-d') !== $expiresAt) {
            Session::flash('error', 'Μη έγκυρη ημερομηνία.');
            View::redirect('/offboarding');
        }

        $userModel = new User();
        $user      = $userModel->findById($userId);
        if (!$user) {
            Session::flash('error', 'Ο χρήστης δεν βρέθηκε.');
            View::redirect('/offboarding');
        }

        $permModel   = new Permission();
        $permissions = $permModel->getByUser($userId);

        if (empty($permissions)) {
            Session::flash('error', 'Ο χρήστης δεν έχει ενεργά δικαιώματα.');
            View::redirect('/offboarding');
        }

        $ids     = array_map('intval', array_column($permissions, 'id'));
        $updated = $permModel->bulkSetExpiry($ids, $expiresAt);

        // Mark user as departed (requires migration 005 — fail silently if column missing)
        try {
            $userModel->setDeparted($userId, $expiresAt);
        } catch (\Throwable $e) {
            // Column probably missing — offboarding permissions still applied
        }

        // Audit log — one entry per permission
        $audit = new AuditLog();
        $name  = $user['full_name'] ?: $user['username'];
        foreach ($permissions as $p) {
            $audit->log(
                'update',
                'permissions',
                (int)$p['id'],
                ['expires_at' => $p['expires_at']],
                ['expires_at' => $expiresAt],
                "Αποχώρηση υπαλλήλου: {$name}"
            );
        }

        $formattedDate = $dt->format('d/m/Y');
        Session::flash(
            'success',
            "Η αποχώρηση του υπαλλήλου <strong>{$name}</strong> καταχωρήθηκε. " .
            "Ορίστηκε ημερομηνία λήξης <strong>{$formattedDate}</strong> " .
            "σε <strong>{$updated}</strong> δικαιώματα."
        );

        View::redirect('/offboarding');
    }
}
