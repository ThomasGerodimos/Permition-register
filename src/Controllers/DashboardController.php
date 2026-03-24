<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\{View, Session, Csrf, Database};
use App\Models\{Permission, User};

class DashboardController
{
    public function index(): void
    {
        Middleware::requireLogin();

        $permModel = new Permission();
        $userModel = new User();

        // Managers see only their department
        $dept = Session::isManager() ? Session::department() : null;

        $stats = $permModel->getStats($dept);

        if ($dept) {
            $totalUsers = (int)Database::getInstance()->fetchColumn(
                'SELECT COUNT(*) FROM users WHERE department = ? AND is_active = 1',
                [$dept]
            );
        } else {
            $totalUsers = count($userModel->getAll());
        }

        View::render('dashboard/index', [
            'pageTitle'  => 'Dashboard',
            'stats'      => $stats,
            'totalUsers' => $totalUsers,
        ]);
    }

    public function departmentView(): void
    {
        Middleware::requireLogin();

        $department = trim($_GET['dept'] ?? '');
        if (!$department) {
            Session::flash('error', 'Δεν ορίστηκε τμήμα.');
            View::redirect('/dashboard');
        }

        // Managers can only see their own department
        if (Session::isManager() && $department !== Session::department()) {
            Session::flash('error', 'Δεν έχετε πρόσβαση σε αυτό το τμήμα.');
            View::redirect('/dashboard');
        }

        $db = Database::getInstance();

        // Get department users
        $users = $db->fetchAll(
            'SELECT * FROM users WHERE department = ? AND is_active = 1 ORDER BY full_name',
            [$department]
        );

        // Get all permissions for this department, grouped by resource
        $permissions = $db->fetchAll(
            "SELECT p.*, u.full_name, u.username, u.email, u.job_title,
                    r.id AS res_id, r.name AS resource_name, r.location,
                    rt.label AS type_label, rt.icon AS type_icon,
                    gb.full_name AS granted_by_name
             FROM permissions p
             JOIN users u ON u.id = p.user_id
             JOIN resources r ON r.id = p.resource_id
             JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN users gb ON gb.id = p.granted_by
             WHERE u.department = ? AND p.is_active = 1
             ORDER BY rt.label, r.name, p.permission_level, u.full_name",
            [$department]
        );

        // Group by resource
        $byResource = [];
        foreach ($permissions as $p) {
            $key = $p['res_id'];
            if (!isset($byResource[$key])) {
                $byResource[$key] = [
                    'name'       => $p['resource_name'],
                    'location'   => $p['location'],
                    'type_label' => $p['type_label'],
                    'type_icon'  => $p['type_icon'],
                    'users'      => [],
                ];
            }
            $byResource[$key]['users'][] = $p;
        }

        View::render('dashboard/department', [
            'pageTitle'   => $department,
            'department'  => $department,
            'users'       => $users,
            'byResource'  => $byResource,
            'permissions' => $permissions,
        ]);
    }

    // ── Network Graph ─────────────────────────────────────────────────

    public function networkGraph(): void
    {
        Middleware::requireLogin();

        $db = Database::getInstance();

        // Managers see only their department
        $dept = Session::isManager() ? Session::department() : null;

        // Fetch all active permissions with user/resource/type info
        $sql = "SELECT p.id, p.permission_level,
                       u.id AS user_id, u.full_name, u.department, u.job_title,
                       r.id AS resource_id, r.name AS resource_name, r.location,
                       rt.id AS type_id, rt.label AS type_label, rt.icon AS type_icon
                FROM permissions p
                JOIN users u ON u.id = p.user_id
                JOIN resources r ON r.id = p.resource_id
                JOIN resource_types rt ON rt.id = r.resource_type_id
                WHERE p.is_active = 1 AND u.is_active = 1 AND r.is_active = 1";
        $params = [];

        if ($dept) {
            $sql .= " AND u.department = ?";
            $params[] = $dept;
        }

        $sql .= " ORDER BY u.full_name, r.name";
        $permissions = $db->fetchAll($sql, $params);

        // Build nodes and edges for vis.js
        $userNodes = [];
        $resourceNodes = [];
        $edges = [];

        foreach ($permissions as $p) {
            $uid = 'u_' . $p['user_id'];
            $rid = 'r_' . $p['resource_id'];

            if (!isset($userNodes[$uid])) {
                $userNodes[$uid] = [
                    'id'         => $uid,
                    'label'      => $p['full_name'],
                    'group'      => 'user',
                    'department' => $p['department'],
                    'title'      => $p['full_name'] . "\n" . ($p['job_title'] ?? '') . "\n" . $p['department'],
                ];
            }

            if (!isset($resourceNodes[$rid])) {
                $resourceNodes[$rid] = [
                    'id'        => $rid,
                    'label'     => $p['resource_name'],
                    'group'     => 'resource_' . $p['type_id'],
                    'typeLabel' => $p['type_label'],
                    'typeIcon'  => $p['type_icon'],
                    'title'     => $p['resource_name'] . "\n" . ($p['location'] ?? '') . "\n[" . $p['type_label'] . ']',
                ];
            }

            $edges[] = [
                'from'  => $uid,
                'to'    => $rid,
                'label' => $p['permission_level'],
                'level' => $p['permission_level'],
            ];
        }

        // Get departments list for filter
        $departments = $db->fetchAll(
            "SELECT DISTINCT department FROM users WHERE is_active = 1 AND department IS NOT NULL ORDER BY department"
        );

        // Get resource types for legend
        $types = $db->fetchAll("SELECT id, label, icon FROM resource_types WHERE is_active = 1 ORDER BY label");

        View::render('network-graph/index', [
            'pageTitle'     => 'Δικτυακό Γράφημα',
            'nodes'         => array_merge(array_values($userNodes), array_values($resourceNodes)),
            'edges'         => $edges,
            'departments'   => $departments,
            'resourceTypes' => $types,
            'totalUsers'    => count($userNodes),
            'totalResources'=> count($resourceNodes),
            'totalEdges'    => count($edges),
        ]);
    }

    // ── Impersonate ─────────────────────────────────────────────────

    public function impersonateStart(): void
    {
        // Only real admins can impersonate
        if (Session::realRole() !== 'admin') {
            Session::flash('error', 'Δεν έχετε δικαίωμα.');
            View::redirect('/dashboard');
            return;
        }

        Csrf::check();

        $role       = trim($_POST['imp_role'] ?? 'manager');
        $department = trim($_POST['imp_department'] ?? '');

        if (!in_array($role, ['manager', 'viewer'], true)) {
            $role = 'manager';
        }

        Session::impersonate($role, $department ?: null);

        Session::flash('success', 'Προβολή ως: ' . ($role === 'manager' ? 'Προϊστάμενος' : 'Viewer')
            . ($department ? ' — ' . $department : ''));
        View::redirect('/dashboard');
    }

    public function impersonateStop(): void
    {
        Session::stopImpersonate();
        Session::flash('success', 'Επιστροφή σε κανονική προβολή Διαχειριστή.');
        View::redirect('/dashboard');
    }
}
