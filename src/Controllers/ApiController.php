<?php

namespace App\Controllers;

use App\Auth\Middleware;
use App\Core\View;
use App\Models\Resource;
use App\Services\AdService;

class ApiController
{
    /** GET /api/ad/search?q=... */
    public function adSearch(): void
    {
        Middleware::requireLogin();

        $term    = trim($_GET['q'] ?? '');
        $service = new AdService();
        $results = $service->search($term);

        View::json($results);
    }

    /** GET /api/resources-by-type/{id} */
    public function resourcesByType(string $id): void
    {
        Middleware::requireAdmin();

        $resModel  = new Resource();
        $resources = $resModel->getAll(['type_id' => (int)$id]);

        // Return only id + name
        $result = array_map(function ($r) {
            return ['id' => (int)$r['id'], 'name' => $r['name']];
        }, $resources);

        View::json($result);
    }

    /** GET /api/resource-types/{id}/permissions */
    public function resourceTypePermissions(string $id): void
    {
        Middleware::requireLogin();

        $resModel = new Resource();
        $type     = $resModel->getTypeById((int)$id);

        if (!$type) {
            View::json(['error' => 'Not found'], 404);
        }

        View::json($type['permissions']);
    }
}
