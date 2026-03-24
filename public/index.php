<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_START', microtime(true));

// Autoloader
require ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
App\Core\Env::load(ROOT_PATH . '/.env');

// Bootstrap
use App\Core\{Session, View, Router};
use App\Auth\{AuthController, IpRestriction};
use App\Controllers\{
    DashboardController,
    PermissionController,
    UserController,
    AuditController,
    ExportController,
    EmailController,
    SettingsController,
    ApiController
};

// Config
$config = require ROOT_PATH . '/config/config.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', $config['app_env'] === 'development' ? '1' : '0');
error_reporting(E_ALL);

// Session
Session::start();

// Views path
View::setPath(ROOT_PATH . '/views');

// ── Router ────────────────────────────────────────────────────────────────────

$router = new Router();

// Auth
$router->get('/login',  [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Permissions
$router->get('/permissions',              [PermissionController::class, 'index']);
$router->get('/permissions/create',       [PermissionController::class, 'create']);
$router->post('/permissions/store',       [PermissionController::class, 'store']);
$router->get('/permissions/bulk',         [PermissionController::class, 'bulk']);
$router->post('/permissions/bulk',        [PermissionController::class, 'bulkStore']);
$router->get('/permissions/{id}/edit',    [PermissionController::class, 'edit']);
$router->post('/permissions/{id}/update', [PermissionController::class, 'update']);
$router->post('/permissions/{id}/delete', [PermissionController::class, 'delete']);

// Users
$router->get('/users',               [UserController::class, 'index']);
$router->post('/users/sync-ad',      [UserController::class, 'syncAd']);
$router->get('/users/{id}',          [UserController::class, 'show']);

// Department view
$router->get('/departments/view',    [DashboardController::class, 'departmentView']);

// Audit Log
$router->get('/audit', [AuditController::class, 'index']);

// Export
$router->get('/export/csv',   [ExportController::class, 'csv']);
$router->get('/export/excel', [ExportController::class, 'excel']);
$router->get('/export/pdf',   [ExportController::class, 'pdf']);

// Email
$router->post('/email/send', [EmailController::class, 'send']);

// Settings (admin only)
$router->get('/settings',                    [SettingsController::class, 'index']);
$router->post('/settings/ip/store',          [SettingsController::class, 'storeIp']);
$router->post('/settings/ip/{id}/delete',    [SettingsController::class, 'deleteIp']);
$router->post('/settings/ip/{id}/toggle',    [SettingsController::class, 'toggleIp']);

// Resources management (admin only)
$router->get('/resources',              [SettingsController::class, 'resources']);
$router->post('/resources/store',        [SettingsController::class, 'storeResource']);
$router->post('/resources/{id}/update',  [SettingsController::class, 'updateResource']);
$router->post('/resources/{id}/delete',  [SettingsController::class, 'deleteResource']);
$router->get('/resources/by-type/{id}',     [SettingsController::class, 'resourcesByType']);
$router->get('/resources/{id}/permissions', [SettingsController::class, 'resourcePermissions']);
$router->post('/resources/{id}/clone-permissions', [SettingsController::class, 'clonePermissions']);

// Network Graph
$router->get('/network-graph', [DashboardController::class, 'networkGraph']);

// Impersonate (admin only)
$router->post('/impersonate/start', [DashboardController::class, 'impersonateStart']);
$router->get('/impersonate/stop',   [DashboardController::class, 'impersonateStop']);

// Documentation download (admin only)
$router->get('/docs/DOCUMENTATION.docx', [SettingsController::class, 'downloadDoc']);

// AJAX API endpoints
$router->get('/api/ad/search',           [ApiController::class, 'adSearch']);
$router->get('/api/resources-by-type/{id}',           [ApiController::class, 'resourcesByType']);
$router->get('/api/resource-types/{id}/permissions', [ApiController::class, 'resourceTypePermissions']);

// ── Dispatch ──────────────────────────────────────────────────────────────────

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
