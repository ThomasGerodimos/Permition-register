<?php

namespace App\Models;

use App\Core\Database;

class Resource
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAllTypes(): array
    {
        $types = $this->db->fetchAll('SELECT * FROM resource_types WHERE is_active = 1 ORDER BY label');
        foreach ($types as &$type) {
            $type['permissions'] = json_decode($type['permissions_json'], true);
        }
        return $types;
    }

    public function getTypeById(int $id): array|false
    {
        $type = $this->db->fetchOne('SELECT * FROM resource_types WHERE id = ?', [$id]);
        if ($type) {
            $type['permissions'] = json_decode($type['permissions_json'], true);
        }
        return $type;
    }

    public function getAll(array $filters = []): array
    {
        $sql    = 'SELECT r.*, rt.label AS type_label, rt.name AS type_name, rt.icon AS type_icon
                   FROM resources r
                   JOIN resource_types rt ON rt.id = r.resource_type_id
                   WHERE r.is_active = 1';
        $params = [];

        if (!empty($filters['type_id'])) {
            $sql     .= ' AND r.resource_type_id = ?';
            $params[] = $filters['type_id'];
        }
        if (!empty($filters['search'])) {
            $sql     .= ' AND (r.name LIKE ? OR r.description LIKE ? OR r.location LIKE ?)';
            $term     = '%' . $filters['search'] . '%';
            $params   = array_merge($params, [$term, $term, $term]);
        }

        $sql .= ' ORDER BY rt.label, r.name';
        return $this->db->fetchAll($sql, $params);
    }

    public function getList(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $conditions = ['r.is_active = 1'];
        $params     = [];

        if (!empty($filters['type_id'])) {
            $conditions[] = 'r.resource_type_id = ?';
            $params[]     = $filters['type_id'];
        }
        if (!empty($filters['type_ids']) && is_array($filters['type_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['type_ids']), '?'));
            $conditions[] = "r.resource_type_id IN ($placeholders)";
            $params       = array_merge($params, $filters['type_ids']);
        }
        if (!empty($filters['search'])) {
            $term         = '%' . $filters['search'] . '%';
            $conditions[] = '(r.name LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR rt.label LIKE ?)';
            $params       = array_merge($params, [$term, $term, $term, $term]);
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM resources r
             JOIN resource_types rt ON rt.id = r.resource_type_id
             $where",
            $params
        );

        $offset = ($page - 1) * $perPage;
        $rows   = $this->db->fetchAll(
            "SELECT r.*, rt.label AS type_label, rt.name AS type_name, rt.icon AS type_icon,
                    (SELECT COUNT(*) FROM permissions p WHERE p.resource_id = r.id AND p.is_active = 1) AS perm_count
             FROM resources r
             JOIN resource_types rt ON rt.id = r.resource_type_id
             $where
             ORDER BY rt.label, r.name
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return compact('rows', 'total', 'page', 'perPage');
    }

    public function findById(int $id): array|false
    {
        $row = $this->db->fetchOne(
            'SELECT r.*, rt.label AS type_label, rt.name AS type_name, rt.permissions_json
             FROM resources r
             JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE r.id = ?',
            [$id]
        );
        if ($row) {
            $row['permissions'] = json_decode($row['permissions_json'], true);
        }
        return $row;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO resources (resource_type_id, name, description, location, expires_at) VALUES (?, ?, ?, ?, ?)',
            [
                $data['resource_type_id'],
                $data['name'],
                $data['description'] ?? null,
                $data['location'] ?? null,
                !empty($data['expires_at']) ? $data['expires_at'] : null,
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $this->db->execute(
            'UPDATE resources SET resource_type_id=?, name=?, description=?, location=?, expires_at=? WHERE id=?',
            [
                $data['resource_type_id'],
                $data['name'],
                $data['description'] ?? null,
                $data['location'] ?? null,
                !empty($data['expires_at']) ? $data['expires_at'] : null,
                $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('UPDATE resources SET is_active=0 WHERE id=?', [$id]);
    }
}
