<?php

use App\Core\Env;

return [
    'from_address' => Env::get('MAIL_FROM_ADDRESS', 'permissions@yourdomain.gr'),
    'from_name'    => Env::get('MAIL_FROM_NAME', 'Μητρώο Δικαιωμάτων'),
    'reply_to'     => Env::get('MAIL_REPLY_TO', 'helpdesk@yourdomain.gr'),

    // SMTP settings
    'smtp' => [
        'host'       => Env::get('MAIL_HOST', 'smtp.office365.com'),
        'port'       => Env::int('MAIL_PORT', 587),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'username'   => Env::get('MAIL_USERNAME', ''),
        'password'   => Env::get('MAIL_PASSWORD', ''),
        'from_email' => Env::get('MAIL_USERNAME', ''),
        'from_name'  => Env::get('MAIL_FROM_NAME', 'Μητρώο Δικαιωμάτων'),
    ],

    // Timeouts
    'timeout' => Env::int('MAIL_TIMEOUT', 10),
];
