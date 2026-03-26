<?php

namespace App\Models;

use App\Core\Database;

class Permission
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Paginated list with filters */
    public function getList(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN users gb ON gb.id = p.granted_by
             $where",
            $params
        );

        $offset = ($page - 1) * $perPage;
        $rows   = $this->db->fetchAll(
            "SELECT p.*, u.full_name, u.username, u.department, u.email, u.job_title,
                    r.name AS resource_name, r.location,
                    rt.label AS type_label, rt.name AS type_name, rt.icon AS type_icon,
                    gb.full_name AS granted_by_name
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN users gb ON gb.id = p.granted_by
             $where
             ORDER BY u.full_name, rt.label, r.name
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    /** All permissions for a single user */
    public function getByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, r.name AS resource_name, r.location,
                    rt.label AS type_label, rt.name AS type_name, rt.icon AS type_icon,
                    gb.full_name AS granted_by_name
             FROM permissions p
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN users gb ON gb.id = p.granted_by
             WHERE p.user_id = ? AND p.is_active = 1
             ORDER BY rt.label, r.name",
            [$userId]
        );
    }

    /** All permissions for a department */
    public function getByDepartment(string $department): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, u.full_name, u.username, u.email,
                    r.name AS resource_name, r.location,
                    rt.label AS type_label, rt.name AS type_name, rt.icon AS type_icon
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE u.department = ? AND p.is_active = 1
             ORDER BY u.full_name, rt.label, r.name",
            [$department]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT p.*, u.full_name, u.username, u.department, u.email,
                    r.name AS resource_name, r.resource_type_id,
                    rt.label AS type_label, rt.name AS type_name
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE p.id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO permissions (user_id, resource_id, permission_level, granted_by, notes, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['resource_id'],
                $data['permission_level'],
                $data['granted_by'] ?? null,
                $data['notes']      ?? null,
                $data['expires_at'] ?? null,
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE permissions SET resource_id=?, permission_level=?, notes=?, expires_at=? WHERE id=?',
            [
                $data['resource_id'],
                $data['permission_level'],
                $data['notes']      ?? null,
                $data['expires_at'] ?? null,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('UPDATE permissions SET is_active=0 WHERE id=?', [$id]);
    }

    /** All permissions for a specific resource */
    public function getByResource(int $resourceId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, u.full_name, u.username, u.email, u.department, u.job_title,
                    gb.full_name AS granted_by_name
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN users gb ON gb.id = p.granted_by
             WHERE p.resource_id = ? AND p.is_active = 1
             ORDER BY p.permission_level, u.full_name",
            [$resourceId]
        );
    }

    /** Dashboard stats (optionally filtered by department for managers) */
    public function getStats(?string $department = null): array
    {
        $deptWhere  = '';
        $deptParams = [];
        if ($department) {
            $deptWhere  = ' AND u.department = ?';
            $deptParams = [$department];
        }

        // Total permissions
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM permissions p
             JOIN users u ON u.id = p.user_id
             WHERE p.is_active = 1 $deptWhere",
            $deptParams
        );

        // Resources by type — for managers: count only resources with permissions for dept users
        if ($department) {
            $byType = $this->db->fetchAll(
                "SELECT rt.id AS type_id, rt.label, rt.icon,
                        COUNT(DISTINCT r.id) AS cnt
                 FROM resource_types rt
                 LEFT JOIN (
                     SELECT DISTINCT r2.id, r2.resource_type_id
                     FROM resources r2
                     JOIN permissions p2 ON p2.resource_id = r2.id AND p2.is_active = 1
                     JOIN users u2 ON u2.id = p2.user_id AND u2.department = ?
                     WHERE r2.is_active = 1
                 ) r ON r.resource_type_id = rt.id
                 WHERE rt.is_active = 1
                 GROUP BY rt.id, rt.label, rt.icon
                 ORDER BY rt.label",
                [$department]
            );
        } else {
            $byType = $this->db->fetchAll(
                'SELECT rt.id AS type_id, rt.label, rt.icon, COUNT(r.id) AS cnt
                 FROM resource_types rt
                 LEFT JOIN resources r ON r.resource_type_id = rt.id AND r.is_active = 1
                 WHERE rt.is_active = 1
                 GROUP BY rt.id, rt.label, rt.icon
                 ORDER BY rt.label'
            );
        }

        // By department
        $byDept = $this->db->fetchAll(
            "SELECT u.department, COUNT(p.id) AS cnt
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             WHERE p.is_active = 1 AND u.department IS NOT NULL $deptWhere
             GROUP BY u.department
             ORDER BY cnt DESC
             LIMIT 10",
            $deptParams
        );

        // Recent entries
        $recent = $this->db->fetchAll(
            "SELECT p.*, u.full_name, r.name AS resource_name, rt.label AS type_label
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE p.is_active = 1 $deptWhere
             ORDER BY p.granted_at DESC
             LIMIT 5",
            $deptParams
        );

        return compact('total', 'byType', 'byDept', 'recent');
    }

    private function buildWhere(array $filters): array
    {
        $conditions = ['p.is_active = 1'];
        $params     = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = 'p.user_id = ?';
            $params[]     = $filters['user_id'];
        }
        if (!empty($filters['department'])) {
            $conditions[] = 'u.department = ?';
            $params[]     = $filters['department'];
        }
        if (!empty($filters['type_id'])) {
            $conditions[] = 'rt.id = ?';
            $params[]     = $filters['type_id'];
        }
        if (!empty($filters['permission_level'])) {
            $conditions[] = 'p.permission_level LIKE ?';
            $params[]     = '%' . $filters['permission_level'] . '%';
        }
        if (!empty($filters['search'])) {
            $term         = '%' . $filters['search'] . '%';
            $conditions[] = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?
                              OR u.department LIKE ? OR r.name LIKE ? OR r.location LIKE ?
                              OR rt.label LIKE ? OR p.permission_level LIKE ?
                              OR p.notes LIKE ? OR gb.full_name LIKE ?)';
            $params       = array_merge($params, [$term, $term, $term, $term, $term, $term, $term, $term, $term, $term]);
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}
