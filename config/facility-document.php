<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Management Configuration
    | ドキュメント管理設定
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the facility document
    | management system.
    | このファイルには施設ドキュメント管理システムの設定オプションが含まれています。
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    | ファイルアップロード設定
    |--------------------------------------------------------------------------
    */
    'max_file_size' => env('DOCUMENT_MAX_FILE_SIZE', 10240), // KB (default 10MB)
    'max_files_per_upload' => env('DOCUMENT_MAX_FILES_PER_UPLOAD', 10),
    'max_folder_depth' => env('DOCUMENT_MAX_FOLDER_DEPTH', 10),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ],

    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
        'zip', 'rar', '7z',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('DOCUMENT_STORAGE_DISK', 'public'),
    'storage_path' => env('DOCUMENT_STORAGE_PATH', 'documents'),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'scan_uploaded_files' => env('DOCUMENT_SCAN_FILES', true),
    'quarantine_suspicious_files' => env('DOCUMENT_QUARANTINE_FILES', true),
    
    /*
    |--------------------------------------------------------------------------
    | Folder Name Validation
    |--------------------------------------------------------------------------
    */
    'folder_name_max_length' => 255,
    'folder_name_min_length' => 1,
    'forbidden_folder_names' => [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ],
    'forbidden_folder_characters' => ['/', '\\', ':', '*', '?', '"', '<', '>', '|'],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'items_per_page' => env('DOCUMENT_ITEMS_PER_PAGE', 50),
    'enable_thumbnails' => env('DOCUMENT_ENABLE_THUMBNAILS', true),
    'thumbnail_cache_duration' => env('DOCUMENT_THUMBNAIL_CACHE_DURATION', 3600), // seconds

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'log_file_operations' => env('DOCUMENT_LOG_OPERATIONS', true),
    'log_access_attempts' => env('DOCUMENT_LOG_ACCESS', true),
];