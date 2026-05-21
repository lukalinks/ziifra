<?php

return [

    'audit_log_per_page' => (int) env('ADMIN_AUDIT_LOG_PER_PAGE', 50),

    'organizations_per_page' => (int) env('ADMIN_ORGANIZATIONS_PER_PAGE', 25),

    'users_per_page' => (int) env('ADMIN_USERS_PER_PAGE', 25),

    'default_super_admin_email' => env('SUPER_ADMIN_EMAIL', 'admin@ziifra.com'),

    'default_super_admin_name' => env('SUPER_ADMIN_NAME', 'ZIIFRA Admin'),

    /** Only used when creating the default super admin user (--create). */
    'default_super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'password'),

];
