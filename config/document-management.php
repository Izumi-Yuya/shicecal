<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Management Configuration
    |--------------------------------------------------------------------------
    */

    'file_upload' => [
        'max_size' => env('DOCUMENT_MAX_FILE_SIZE', 10240), // KB
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'],
        'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'public'),
        'storage_path' => 'documents',
    ],

    'pagination' => [
        'default_per_page' => 50,
        'max_per_page' => 100,
    ],

    'cache' => [
        'file_types_ttl' => 300, // 5 minutes
        'folder_contents_ttl' => 60, // 1 minute
    ],

    'security' => [
        'enable_file_integrity_check' => true,
        'enable_path_traversal_protection' => true,
        'sanitize_filenames' => true,
    ],

    'export' => [
        'max_facilities_per_export' => 100,
        'max_fields_per_export' => 200,
        'csv_encoding' => 'UTF-8',
        'include_bom' => true,
    ],
];