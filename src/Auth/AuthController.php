<?php

namespace App\Auth;

use App\Core\{Database, Session, View, Csrf};
use App\Models\User;

class AuthController
{
    public function loginForm(): void
    {
        if (Session::isLoggedIn()) {
            View::redirect('/dashboard');
        }
        View::render('auth/login', [], false);
    }

    public function login(): void
    {
        Csrf::check();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Παρακαλώ συμπληρώστε όνομα χρήστη και κωδικό.');
            View::redirect('/login');
        }

        $clientIp = IpRestriction::clientIp();

        try {
            $ldap    = new LdapService();
            $adUser  = $ldap->authenticate($username, $password);
        } catch (\RuntimeException $e) {
            error_log('LDAP error: ' . $e->getMessage());
            Session::flash('error', 'Αδυναμία σύνδεσης με τον Active Directory. Επικοινωνήστε με την Πληροφορική.');
            View::redirect('/login');
        }

        if (!$adUser) {
            Session::flash('error', 'Λανθασμένο όνομα χρήστη ή κωδικός.');
            View::redirect('/login');
        }

        $role = $adUser['role'];

        // Upsert user in local DB (AD cache) — needed early for type-admin check
        $userModel = new User();
        $userId    = $userModel->syncFromAd($adUser);

        // Load type-admin assignments
        $db = Database::getInstance();
        $typeAdminRows = $db->fetchAll(
            'SELECT resource_type_id FROM user_type_admins WHERE user_id = ?',
            [$userId]
        );
        $typeAdminTypes = array_column($typeAdminRows, 'resource_type_id');

        // Viewers can log in only if they have type-admin assignments
        if ($role === 'viewer' && empty($typeAdminTypes)) {
            Session::flash('error', 'Δεν έχετε δικαίωμα πρόσβασης στην εφαρμογή.');
            View::redirect('/login');
        }

        // IP restriction check
        if (!IpRestriction::isAllowed($clientIp, $role)) {
            Session::flash('error', 'Η IP διεύθυνσή σας (' . htmlspecialchars($clientIp) . ') δεν επιτρέπεται για αυτόν τον ρόλο.');
            View::redirect('/login');
        }

        // Set session
        Session::set('user_id',    $userId);
        Session::set('username',   $adUser['username']);
        Session::set('full_name',  $adUser['full_name'] ?? $username);
        Session::set('role',       $role);
        Session::set('department', $adUser['department'] ?? null);
        Session::set('email',      $adUser['email'] ?? null);
        Session::set('client_ip',  $clientIp);
        Session::setTypeAdminTypes($typeAdminTypes);

        // Regenerate session ID for security
        session_regenerate_id(true);

        Session::flash('success', 'Καλωσορίσατε, ' . ($adUser['full_name'] ?? $username) . '!');
        View::redirect('/dashboard');
    }

    public function logout(): void
    {
        Session::destroy();
        View::redirect('/login', ['success' => 'Αποσυνδεθήκατε επιτυχώς.']);
    }

    /**
     * Dev-only: login as any local DB user without LDAP.
     * Only available when APP_ENV=development.
     */
    public function devLoginAs(string $username): void
    {
        // Safety: only in development
        if (\App\Core\Env::get('APP_ENV') !== 'development') {
            http_response_code(403);
            exit('Forbidden');
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            Session::flash('error', 'Ο χρήστης "' . htmlspecialchars($username) . '" δεν βρέθηκε στη βάση.');
            View::redirect('/login');
        }

        // Load type-admin assignments
        $db = Database::getInstance();
        $typeAdminRows = $db->fetchAll(
            'SELECT resource_type_id FROM user_type_admins WHERE user_id = ?',
            [$user['id']]
        );
        $typeAdminTypes = array_column($typeAdminRows, 'resource_type_id');

        // Set session
        Session::set('user_id',    $user['id']);
        Session::set('username',   $user['username']);
        Session::set('full_name',  $user['full_name'] ?? $username);
        Session::set('role',       $user['role']);
        Session::set('department', $user['department'] ?? null);
        Session::set('email',      $user['email'] ?? null);
        Session::set('client_ip',  IpRestriction::clientIp());
        Session::setTypeAdminTypes($typeAdminTypes);

        session_regenerate_id(true);

        Session::flash('success', '[DEV] Συνδεθήκατε ως ' . ($user['full_name'] ?? $username) . ' (role: ' . $user['role'] . ')');
        View::redirect('/dashboard');
    }
}
