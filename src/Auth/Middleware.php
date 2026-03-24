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
