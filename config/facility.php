<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facility Management System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options specific to the Shise-Cal
    | facility management system.
    |
    */

    'approval_system_enabled' => env('APPROVAL_SYSTEM_ENABLED', true),
    
    'file_upload' => [
        'max_size' => env('FILE_UPLOAD_MAX_SIZE', 10240), // KB
        'allowed_types' => ['pdf'],
        'storage_disk' => env('FILESYSTEM_DISK', 'local'),
    ],
    
    'security' => [
        'allowed_ip_addresses' => env('ALLOWED_IP_ADDRESSES', ''),
        'session_timeout' => 120, // minutes
    ],
    
    'roles' => [
        'admin' => 'admin',
        'editor' => 'editor', 
        'primary_responder' => 'primary_responder',
        'approver' => 'approver',
        'viewer' => 'viewer',
    ],
    
    'export' => [
        'csv_encoding' => 'UTF-8',
        'pdf_security' => true,
        'max_facilities_per_export' => 100,
    ],
    
    'notifications' => [
        'email_enabled' => true,
        'approval_notifications' => true,
        'comment_notifications' => true,
    ],
];