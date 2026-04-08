<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\View;
use App\Models\{AuditLog, User};

class AuditController
{
    public function index(): void
    {
        Middleware::requireAdmin();

        $auditModel = new AuditLog();
        $userModel  = new User();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        $filters = [
            'action'     => trim($_GET['action']     ?? ''),
            'changed_by' => (int)($_GET['changed_by'] ?? 0) ?: null,
            'date_from'  => trim($_GET['date_from']  ?? ''),
            'date_to'    => trim($_GET['date_to']    ?? ''),
            'search'     => trim($_GET['search']     ?? ''),
        ];

        $result = $auditModel->getList($filters, $page, $perPage);
        $users  = $userModel->getAll();

        View::render('audit/index', [
            'pageTitle' => 'Ιστορικό Αλλαγών',
            'result'    => $result,
            'filters'   => $filters,
            'users'     => $users,
        ]);
    }
}
