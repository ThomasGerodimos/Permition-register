<?php

namespace App\Auth;

use App\Core\{Session, View};

class Middleware
{
    /** Require login — redirect to /login if not authenticated */
    public static function requireLogin(): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Πρέπει να συνδεθείτε για να συνεχίσετε.');
            View::redirect('/login');
        }
    }

    /** Require admin role (real role — not blocked by impersonation) */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (Session::realRole() !== 'admin') {
            http_response_code(403);
            View::render('errors/403', [], false);
            exit;
        }
    }

    /** Require permission editor: admin OR type-admin for the given resource type */
    public static function requirePermissionEditor(?int $resourceTypeId = null): void
    {
        self::requireLogin();

        // Full admins always pass
        if (Session::realRole() === 'admin') {
            return;
        }

        // Type-admins pass for their assigned types (or any if no specific type given)
        if ($resourceTypeId !== null && Session::isTypeAdmin($resourceTypeId)) {
            return;
        }
        if ($resourceTypeId === null && Session::isTypeAdmin()) {
            return;
        }

        http_response_code(403);
        View::render('errors/403', [], false);
        exit;
    }

    /** Require admin or manager role */
    public static function requireAdminOrManager(): void
    {
        self::requireLogin();
        $role = Session::role();
        if (!in_array($role, ['admin', 'manager'], true)) {
            http_response_code(403);
            View::render('errors/403', [], false);
            exit;
        }
    }
}
