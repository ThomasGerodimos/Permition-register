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

        // Viewers (no role) cannot log in
        if ($role === 'viewer') {
            Session::flash('error', 'Δεν έχετε δικαίωμα πρόσβασης στην εφαρμογή.');
            View::redirect('/login');
        }

        // IP restriction check
        if (!IpRestriction::isAllowed($clientIp, $role)) {
            Session::flash('error', 'Η IP διεύθυνσή σας (' . htmlspecialchars($clientIp) . ') δεν επιτρέπεται για αυτόν τον ρόλο.');
            View::redirect('/login');
        }

        // Upsert user in local DB (AD cache)
        $userModel = new User();
        $userId    = $userModel->syncFromAd($adUser);

        // Set session
        Session::set('user_id',    $userId);
        Session::set('username',   $adUser['username']);
        Session::set('full_name',  $adUser['full_name'] ?? $username);
        Session::set('role',       $role);
        Session::set('department', $adUser['department'] ?? null);
        Session::set('email',      $adUser['email'] ?? null);
        Session::set('client_ip',  $clientIp);

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
}
