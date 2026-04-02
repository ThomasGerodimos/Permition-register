<?php

namespace App\Models;

use App\Core\Database;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Upsert user from AD data. Returns local user ID. */
    public function syncFromAd(array $adUser): int
    {
        $existing = $this->db->fetchOne(
            'SELECT id, role FROM users WHERE username = ?',
            [$adUser['username']]
        );

        if ($existing) {
            $this->db->execute(
                'UPDATE users SET full_name=?, email=?, department=?, job_title=?, phone=?, manager=?, last_sync=NOW()
                 WHERE id=?',
                [
                    $adUser['full_name']  ?? null,
                    $adUser['email']      ?? null,
                    $adUser['department'] ?? null,
                    $adUser['job_title']  ?? null,
                    $adUser['phone']      ?? null,
                    $adUser['manager']    ?? null,
                    $existing['id'],
                ]
            );
            // Update role only if changed
            if (isset($adUser['role']) && $adUser['role'] !== $existing['role']) {
                $this->db->execute(
                    'UPDATE users SET role=? WHERE id=?',
                    [$adUser['role'], $existing['id']]
                );
            }
            return (int)$existing['id'];
        }

        return $this->db->insert(
            'INSERT INTO users (username, full_name, email, department, job_title, phone, manager, role, last_sync)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $adUser['username'],
                $adUser['full_name']  ?? null,
                $adUser['email']      ?? null,
                $adUser['department'] ?? null,
                $adUser['job_title']  ?? null,
                $adUser['phone']      ?? null,
                $adUser['manager']    ?? null,
                $adUser['role']       ?? 'viewer',
            ]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByUsername(string $username): array|false
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function getAll(array $filters = []): array
    {
        [$where, $params] = $this->buildUserWhere($filters);
        return $this->db->fetchAll("SELECT u.* FROM users u {$where} ORDER BY u.full_name ASC", $params);
    }

    /** Paginated user list — search always queries ALL users (ignores page) */
    public function getList(array $filters = [], int $page = 1, int $perPage = 24): array
    {
        [$where, $params] = $this->buildUserWhere($filters);

        // Type-admin filter: only users that have permissions for specific resource types
        $typeJoin = '';
        if (!empty($filters['type_ids']) && is_array($filters['type_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['type_ids']), '?'));
            $typeJoin = " AND u.id IN (
                SELECT DISTINCT p2.user_id FROM permissions p2
                JOIN resources r2 ON r2.id = p2.resource_id
                WHERE p2.is_active = 1 AND r2.resource_type_id IN ($placeholders)
            )";
            $params = array_merge($params, $filters['type_ids']);
        }

        $countParams = $params;
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM users u {$where} {$typeJoin}",
            $countParams
        );

        $offset = ($page - 1) * $perPage;

        // perm_count: if type-filtered, count only permissions for those types
        if (!empty($filters['type_ids']) && is_array($filters['type_ids'])) {
            $placeholders2 = implode(',', array_fill(0, count($filters['type_ids']), '?'));
            $permCountSql = "(SELECT COUNT(*) FROM permissions p
                              JOIN resources r3 ON r3.id = p.resource_id
                              WHERE p.user_id = u.id AND p.is_active = 1
                              AND r3.resource_type_id IN ($placeholders2))";
            $listParams = array_merge($params, $filters['type_ids'], [$perPage, $offset]);
        } else {
            $permCountSql = "(SELECT COUNT(*) FROM permissions p WHERE p.user_id = u.id AND p.is_active = 1)";
            $listParams = array_merge($params, [$perPage, $offset]);
        }

        $rows = $this->db->fetchAll(
            "SELECT u.*, {$permCountSql} AS perm_count
             FROM users u {$where} {$typeJoin}
             ORDER BY u.full_name ASC
             LIMIT ? OFFSET ?",
            $listParams
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    private function buildUserWhere(array $filters): array
    {
        $conditions = ['u.is_active = 1'];
        $params     = [];

        if (!empty($filters['department'])) {
            $conditions[] = 'u.department = ?';
            $params[]     = $filters['department'];
        }
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $conditions[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.department LIKE ? OR u.job_title LIKE ?)';
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);
        return [$where, $params];
    }

    public function getDepartments(): array
    {
        return $this->db->fetchAll(
            'SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND is_active = 1 ORDER BY department'
        );
    }

    public function updateRole(int $id, string $role): void
    {
        $this->db->execute('UPDATE users SET role=? WHERE id=?', [$role, $id]);
    }
}
