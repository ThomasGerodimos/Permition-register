<?php

use App\Core\Env;

return [
    // Application
    'app_name'    => 'Μητρώο Δικαιωμάτων',
    'app_url'     => Env::get('APP_URL', 'http://localhost/permissions'),
    'app_env'     => Env::get('APP_ENV', 'production'),
    'timezone'    => Env::get('APP_TIMEZONE', 'Europe/Athens'),

    // Database
    'db' => [
        'host'    => Env::get('DB_HOST', '127.0.0.1'),
        'port'    => Env::int('DB_PORT', 3306),
        'dbname'  => Env::get('DB_NAME', 'permissions_db'),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
    ],

    // Session
    'session_name'     => Env::get('SESSION_NAME', 'PERM_REG_SESS'),
    'session_lifetime' => Env::int('SESSION_LIFETIME', 7200),

    // Pagination
    'per_page' => 25,

    // Storage
    'log_path' => dirname(__DIR__) . '/storage/logs',
];
