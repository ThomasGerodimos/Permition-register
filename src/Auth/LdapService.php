<?php

namespace App\Auth;

class LdapService
{
    private array $cfg;
    /** @var resource|\LDAP\Connection|null */
    private mixed $conn = null;

    public function __construct()
    {
        $this->cfg = require dirname(__DIR__, 2) . '/config/ldap.php';
    }

    /** Connect and bind with service account */
    private function connect(): bool
    {
        if ($this->conn !== null) {
            return true;
        }

        // Embed port in URI to avoid deprecated two-argument ldap_connect (PHP 8.3+)
        $host = rtrim($this->cfg['host'], '/');
        $port = (int)$this->cfg['port'];
        $uri  = (str_contains($host, ':' . $port) || $port === 389) ? $host : $host . ':' . $port;
        $this->conn = ldap_connect($uri);
        if (!$this->conn) {
            return false;
        }

        ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->conn, LDAP_OPT_REFERRALS, 0);

        if ($this->cfg['use_tls'] ?? false) {
            ldap_start_tls($this->conn);
        }

        return @ldap_bind($this->conn, $this->cfg['bind_user'], $this->cfg['bind_pass']);
    }

    /**
     * Authenticate a user with username + password.
     * Returns user attributes on success, false on failure.
     */
    public function authenticate(string $username, string $password): array|false
    {
        if (empty($password)) {
            return false;
        }

        if (!$this->connect()) {
            throw new \RuntimeException('LDAP connection failed');
        }

        // Build UPN (user@domain.local) or DOMAIN\user
        $domain   = $this->cfg['domain'];
        $baseDn   = $this->cfg['base_dn'];
        $attrMap  = $this->cfg['attr_map'];

        // Search for user first to get DN
        $search = @ldap_search(
            $this->conn,
            $baseDn,
            '(' . $attrMap['username'] . '=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')',
            ['dn', ...array_values($attrMap)]
        );

        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->conn, $search);
        if ($entries['count'] === 0) {
            return false;
        }

        $userDn = $entries[0]['dn'];

        // Attempt bind with user's credentials
        $bound = @ldap_bind($this->conn, $userDn, $password);

        // Re-bind as service account for subsequent searches
        @ldap_bind($this->conn, $this->cfg['bind_user'], $this->cfg['bind_pass']);

        if (!$bound) {
            return false;
        }

        return $this->parseEntry($entries[0]);
    }

    /**
     * Lookup user by username (without authentication).
     */
    public function findUser(string $username): array|false
    {
        if (!$this->connect()) {
            return false;
        }

        $attrMap = $this->cfg['attr_map'];
        $search  = @ldap_search(
            $this->conn,
            $this->cfg['base_dn'],
            '(' . $attrMap['username'] . '=' . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')',
            array_values($attrMap)
        );

        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->conn, $search);
        return $entries['count'] > 0 ? $this->parseEntry($entries[0]) : false;
    }

    /**
     * Search users by partial name/username (for autocomplete).
     */
    public function searchUsers(string $term, int $limit = 10): array
    {
        if (!$this->connect() || strlen($term) < 2) {
            return [];
        }

        $attrMap = $this->cfg['attr_map'];
        // Exclude 'manager' from search attributes — it returns a DN, not needed for autocomplete
        $searchAttrs = array_values(array_filter($attrMap, fn($k) => $k !== 'manager', ARRAY_FILTER_USE_KEY));

        $escaped = ldap_escape($term, '', LDAP_ESCAPE_FILTER);
        $filter  = '(&(objectClass=user)(objectCategory=person)'
                 . '(!(userAccountControl:1.2.840.113556.1.4.803:=2))'  // exclude disabled accounts
                 . '(|(' . $attrMap['username'] . '=*' . $escaped . '*)'
                 . '(' . $attrMap['full_name'] . '=*' . $escaped . '*)))';

        $search = @ldap_search(
            $this->conn,
            $this->cfg['base_dn'],
            $filter,
            $searchAttrs,
            0,
            $limit
        );

        if (!$search) {
            error_log('LDAP searchUsers failed. Filter: ' . $filter . ' | Error: ' . ldap_error($this->conn));
            return [];
        }

        $entries = ldap_get_entries($this->conn, $search);
        error_log('LDAP searchUsers: term=' . $term . ' | found=' . $entries['count'] . ' | filter=' . $filter);

        $results = [];
        for ($i = 0; $i < $entries['count']; $i++) {
            $results[] = $this->parseEntry($entries[$i]);
        }
        return $results;
    }

    /**
     * Check if user is member of a specific AD group.
     */
    public function isMemberOf(string $username, string $groupDn): bool
    {
        if (!$this->connect()) {
            return false;
        }

        $attrMap = $this->cfg['attr_map'];
        $escaped = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $filter  = '(&(' . $attrMap['username'] . '=' . $escaped . ')(memberOf:1.2.840.113556.1.4.1941:=' . ldap_escape($groupDn, '', LDAP_ESCAPE_FILTER) . '))';

        $search  = @ldap_search($this->conn, $this->cfg['base_dn'], $filter, ['dn']);
        if (!$search) {
            return false;
        }

        $entries = ldap_get_entries($this->conn, $search);
        return $entries['count'] > 0;
    }

    /**
     * Resolve a manager DN to a display name.
     * The AD 'manager' attribute returns a full DN like:
     * CN=John Doe,OU=Users,DC=domain,DC=loc
     */
    public function resolveManagerName(?string $managerDn): ?string
    {
        if (!$managerDn || !$this->connect()) {
            return null;
        }

        $search = @ldap_read($this->conn, $managerDn, '(objectClass=*)', ['displayname', 'cn']);
        if (!$search) {
            // Fallback: extract CN from DN
            if (preg_match('/^CN=([^,]+)/i', $managerDn, $m)) {
                return $m[1];
            }
            return null;
        }

        $entries = ldap_get_entries($this->conn, $search);
        if ($entries['count'] === 0) {
            if (preg_match('/^CN=([^,]+)/i', $managerDn, $m)) {
                return $m[1];
            }
            return null;
        }

        return $entries[0]['displayname'][0] ?? $entries[0]['cn'][0] ?? null;
    }

    /** Parse raw LDAP entry to clean array */
    private function parseEntry(array $entry): array
    {
        $attrMap = $this->cfg['attr_map'];
        $result  = [];

        foreach ($attrMap as $field => $ldapAttr) {
            $result[$field] = isset($entry[$ldapAttr][0]) ? $entry[$ldapAttr][0] : null;
        }

        // Resolve manager DN to display name
        if (!empty($result['manager'])) {
            $result['manager'] = $this->resolveManagerName($result['manager']);
        }

        // Determine role based on AD groups
        $result['role'] = $this->determineRole($result['username'] ?? '');

        return $result;
    }

    private function determineRole(string $username): string
    {
        if (!empty($this->cfg['admin_group']) && $this->isMemberOf($username, $this->cfg['admin_group'])) {
            return 'admin';
        }
        if (!empty($this->cfg['manager_group']) && $this->isMemberOf($username, $this->cfg['manager_group'])) {
            return 'manager';
        }
        return 'viewer';
    }

    public function __destruct()
    {
        if ($this->conn !== null) {
            @ldap_unbind($this->conn);
        }
    }
}
