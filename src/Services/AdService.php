<?php

namespace App\Services;

use App\Auth\LdapService;
use App\Models\User;

class AdService
{
    private LdapService $ldap;

    public function __construct()
    {
        $this->ldap = new LdapService();
    }

    /**
     * Search users in AD by partial term (for autocomplete).
     * Also syncs found users to local DB.
     */
    public function search(string $term): array
    {
        if (strlen(trim($term)) < 2) {
            return [];
        }

        try {
            $adUsers = $this->ldap->searchUsers($term);
        } catch (\Throwable $e) {
            error_log('AD search error: ' . $e->getMessage());
            return [];
        }

        $userModel = new User();
        $results   = [];

        foreach ($adUsers as $adUser) {
            // Sync to local DB (upsert)
            try {
                $userModel->syncFromAd($adUser);
            } catch (\Throwable) {
                // Non-fatal
            }

            $results[] = [
                'username'   => $adUser['username'],
                'full_name'  => $adUser['full_name'],
                'email'      => $adUser['email'],
                'department' => $adUser['department'],
                'job_title'  => $adUser['job_title'],
                'phone'      => $adUser['phone'],
                'manager'    => $adUser['manager'],
            ];
        }

        return $results;
    }

    /**
     * Fetch a single user from AD and sync.
     */
    public function fetchAndSync(string $username): array|false
    {
        try {
            $adUser = $this->ldap->findUser($username);
        } catch (\Throwable $e) {
            error_log('AD fetch error: ' . $e->getMessage());
            return false;
        }

        if (!$adUser) {
            return false;
        }

        $userModel = new User();
        $userModel->syncFromAd($adUser);

        return $adUser;
    }
}
