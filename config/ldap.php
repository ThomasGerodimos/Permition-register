<?php

use App\Core\Env;

return [
    // Active Directory / LDAP settings
    'host'        => Env::get('LDAP_HOST', 'ldap://your-dc.domain.loc'),
    'port'        => Env::int('LDAP_PORT', 389),
    'use_tls'     => Env::bool('LDAP_USE_TLS', false),
    'domain'      => Env::get('LDAP_DOMAIN', 'domain.loc'),
    'base_dn'     => Env::get('LDAP_BASE_DN', 'DC=domain,DC=loc'),
    'users_ou'    => Env::get('LDAP_USERS_OU', 'OU=Users,DC=domain,DC=loc'),

    // Bind account (service account for AD searches)
    'bind_user'   => Env::get('LDAP_BIND_USER', ''),
    'bind_pass'   => Env::get('LDAP_BIND_PASS', ''),

    // Attribute mapping (AD attribute => app field)
    'attr_map' => [
        'username'   => 'samaccountname',
        'full_name'  => 'displayname',
        'email'      => 'mail',
        'department' => 'department',
        'job_title'  => 'title',
        'phone'      => 'telephonenumber',
        'manager'    => 'manager',
    ],

    // Groups
    'admin_group'   => Env::get('LDAP_ADMIN_GROUP', ''),
    'manager_group' => Env::get('LDAP_MANAGER_GROUP', ''),
];
