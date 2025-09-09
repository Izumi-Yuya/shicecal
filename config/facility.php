<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facility Management System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options specific to the Shise-Cal facility management system.
    |
    */

    // System Features
    'approval_system_enabled' => env('APPROVAL_SYSTEM_ENABLED', true),

    // File Upload Settings
    'file_upload' => [
        'max_size' => env('FILE_UPLOAD_MAX_SIZE', 10240), // KB
        'allowed_types' => ['pdf'],
        'storage_disk' => env('FILESYSTEM_DISK', 'local'),
    ],

    // Security Settings
    'security' => [
        'allowed_ip_addresses' => env('ALLOWED_IP_ADDRESSES', ''),
        'session_timeout' => 120, // minutes
    ],

    // User Roles
    'roles' => [
        'admin' => 'admin',
        'editor' => 'editor',
        'primary_responder' => 'primary_responder',
        'approver' => 'approver',
        'viewer' => 'viewer',
    ],

    // Export Settings
    'export' => [
        'csv_encoding' => 'UTF-8',
        'pdf_security' => true,
        'max_facilities_per_export' => 100,
    ],

    // Service Display Settings
    'services' => [
        'max_display_rows' => env('FACILITY_MAX_SERVICES_DISPLAY', 10),
        'expiry_warning_days' => env('FACILITY_SERVICE_EXPIRY_WARNING_DAYS', 30),
        'date_format' => 'Y年m月d日',
    ],

    // Notification Settings
    'notifications' => [
        'email_enabled' => env('MAIL_ENABLED', true),
        'approval_notifications' => true,
        'comment_notifications' => true,
    ],
];
