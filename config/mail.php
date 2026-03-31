<?php

use App\Core\Env;

return [
    'from_address' => Env::get('MAIL_FROM_ADDRESS', 'system@aml-authority.gov.gr'),
    'from_name'    => Env::get('MAIL_FROM_NAME', 'Μητρώο Δικαιωμάτων'),
    'reply_to'     => Env::get('MAIL_REPLY_TO', 'helpdesk@aml-authority.gov.gr'),

    // SMTP settings (used when MAIL_USE_OAUTH=false)
    'smtp' => [
        'host'       => Env::get('MAIL_HOST', 'smtp.office365.com'),
        'port'       => Env::int('MAIL_PORT', 587),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'username'   => Env::get('MAIL_USERNAME', ''),
        'password'   => Env::get('MAIL_PASSWORD', ''),
        'from_email' => Env::get('MAIL_USERNAME', ''),
        'from_name'  => Env::get('MAIL_FROM_NAME', 'Μητρώο Δικαιωμάτων'),
    ],

    // OAuth 2.0 via Microsoft Graph API (recommended for Microsoft 365)
    // Requires "Mail.Send" Application permission in Azure app registration.
    'oauth' => [
        'enabled'       => Env::bool('MAIL_USE_OAUTH', false),
        'tenant_id'     => Env::get('MAIL_OAUTH_TENANT_ID', ''),
        'client_id'     => Env::get('MAIL_OAUTH_CLIENT_ID', ''),
        'client_secret' => Env::get('MAIL_OAUTH_CLIENT_SECRET', ''),
    ],

    // Timeouts
    'timeout' => Env::int('MAIL_TIMEOUT', 10),
];