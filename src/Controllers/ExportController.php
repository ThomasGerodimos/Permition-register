<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\Session;
use App\Services\ExportService;

class ExportController
{
    private function getFilters(): array
    {
        $filters = [
            'department'       => trim($_GET['department']       ?? ''),
            'type_id'          => (int)($_GET['type_id']         ?? 0) ?: null,
            'permission_level' => trim($_GET['permission_level'] ?? ''),
            'user_id'          => (int)($_GET['user_id']         ?? 0) ?: null,
            'search'           => trim($_GET['search']           ?? ''),
        ];

        // Managers (non-type-admin) can only export their department
        if (Session::isManager() && !Session::isTypeAdmin()) {
            $filters['department'] = Session::department();
        }

        // Type-admins can only export their assigned resource types
        if (!Session::isAdmin() && Session::isTypeAdmin()) {
            $filters['type_ids'] = Session::getTypeAdminTypes();
        }

        return $filters;
    }

    public function csv(): never
    {
        Middleware::requireLogin();
        (new ExportService())->exportCsv($this->getFilters());
    }

    public function excel(): never
    {
        Middleware::requireLogin();
        (new ExportService())->exportExcel($this->getFilters());
    }

    public function pdf(): never
    {
        Middleware::requireLogin();
        $title = trim($_GET['title'] ?? 'Αναφορά Δικαιωμάτων');
        (new ExportService())->exportPdf($this->getFilters(), $title);
    }
}
