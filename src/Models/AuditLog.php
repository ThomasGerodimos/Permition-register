<?php

namespace App\Models;

use App\Core\{Database, Session};
use App\Auth\IpRestriction;

class AuditLog
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log(
        string $action,
        string $table,
        int    $recordId,
        array  $oldValues = [],
        array  $newValues = [],
        string $description = ''
    ): void {
        $this->db->execute(
            'INSERT INTO audit_log (action, table_name, record_id, changed_by, old_values, new_values, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $action,
                $table,
                $recordId,
                Session::userId(),
                $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $description,
                IpRestriction::clientIp(),
            ]
        );
    }

    public function getList(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $conditions = [];
        $params     = [];

        if (!empty($filters['action'])) {
            $conditions[] = 'al.action = ?';
            $params[]     = $filters['action'];
        }
        if (!empty($filters['changed_by'])) {
            $conditions[] = 'al.changed_by = ?';
            $params[]     = $filters['changed_by'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'DATE(al.created_at) >= ?';
            $params[]     = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'DATE(al.created_at) <= ?';
            $params[]     = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $term         = '%' . $filters['search'] . '%';
            $conditions[] = '(u.full_name LIKE ? OR al.description LIKE ? OR al.table_name LIKE ?)';
            $params       = array_merge($params, [$term, $term, $term]);
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM audit_log al
             LEFT JOIN users u ON u.id = al.changed_by $where",
            $params
        );

        $offset = ($page - 1) * $perPage;
        $rows   = $this->db->fetchAll(
            "SELECT al.*, u.full_name, u.username
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.changed_by
             $where
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return compact('rows', 'total', 'page', 'perPage');
    }
}
