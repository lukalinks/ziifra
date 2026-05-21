<?php

return [
    'title' => 'Import employees',
    'subtitle' => 'Upload a CSV file to add multiple employees at once.',
    'download_template' => 'Download CSV template',
    'upload' => 'Upload and import',
    'file_label' => 'CSV file',
    'file_hint' => 'Max 2 MB. UTF-8 CSV with a header row.',
    'columns_title' => 'Supported columns',
    'limit_reached' => 'Employee plan limit reached — remaining rows were not imported.',
    'result_imported' => ':count employee(s) imported successfully.',
    'result_skipped' => ':count row(s) skipped.',
    'errors_title' => 'Row errors',

    'columns' => [
        'first_name' => 'First name (required)',
        'last_name' => 'Last name (required)',
        'email' => 'Email (optional, must be unique)',
        'phone' => 'Phone',
        'department' => 'Department name (must already exist)',
        'position' => 'Position title (must already exist)',
        'manager_email' => 'Manager email (existing employee)',
        'employment_type' => 'full_time, part_time, contract, intern, temporary',
        'employment_status' => 'active, on_leave, terminated',
        'start_date' => 'Start date (YYYY-MM-DD)',
    ],

    'errors' => [
        'missing_columns' => 'CSV must include first_name and last_name columns.',
        'name_required' => 'First name and last name are required.',
        'invalid_email' => 'Invalid email address.',
        'duplicate_email_in_file' => 'Duplicate email in this file.',
        'email_exists' => 'An employee with this email already exists.',
        'unknown_department' => 'Unknown department: :name.',
        'unknown_position' => 'Unknown position: :name.',
        'unknown_manager' => 'No employee found with manager email: :email.',
        'invalid_type' => 'Invalid employment_type.',
        'invalid_status' => 'Invalid employment_status.',
        'invalid_date' => 'Invalid start_date — use YYYY-MM-DD.',
    ],
];
