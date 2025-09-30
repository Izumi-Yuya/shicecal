<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Management Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains environment-specific configurations for the document
    | management system. Different settings are applied based on the current
    | application environment.
    |
    */

    'environments' => [
        'testing' => [
            'storage' => [
                'driver' => 'local',
                'root' => storage_path('app/public/documents'),
                'url' => env('APP_URL').'/storage/documents',
                'visibility' => 'public',
            ],
            'limits' => [
                'max_file_size' => 5 * 1024 * 1024, // 5MB for testing
                'max_files_per_upload' => 5,
                'max_folder_depth' => 5,
                'max_files_per_folder' => 100,
            ],
            'features' => [
                'virus_scanning' => false,
                'watermarking' => false,
                'encryption' => false,
                'thumbnails' => false,
                'versioning' => false,
            ],
            'performance' => [
                'caching_enabled' => false,
                'cache_ttl' => 60,
                'lazy_loading' => false,
                'compression' => false,
            ],
        ],

        'local' => [
            'storage' => [
                'driver' => 'local',
                'root' => storage_path('app/public/documents'),
                'url' => env('APP_URL').'/storage/documents',
                'visibility' => 'public',
            ],
            'limits' => [
                'max_file_size' => 10 * 1024 * 1024, // 10MB for development
                'max_files_per_upload' => 10,
                'max_folder_depth' => 10,
                'max_files_per_folder' => 500,
            ],
            'features' => [
                'virus_scanning' => false,
                'watermarking' => true,
                'watermark_text' => 'DEVELOPMENT',
                'encryption' => false,
                'thumbnails' => true,
                'versioning' => false,
            ],
            'performance' => [
                'caching_enabled' => true,
                'cache_ttl' => 300, // 5 minutes
                'lazy_loading' => true,
                'compression' => false,
            ],
        ],

        'staging' => [
            'storage' => [
                'driver' => env('DOCUMENTS_STORAGE_DRIVER', 's3'),
                'bucket' => env('AWS_DOCUMENTS_BUCKET', 'shicecal-documents-staging'),
                'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
                'visibility' => 'private',
            ],
            'limits' => [
                'max_file_size' => 20 * 1024 * 1024, // 20MB for staging
                'max_files_per_upload' => 20,
                'max_folder_depth' => 15,
                'max_files_per_folder' => 1000,
            ],
            'features' => [
                'virus_scanning' => true,
                'watermarking' => true,
                'watermark_text' => 'STAGING',
                'encryption' => true,
                'thumbnails' => true,
                'versioning' => true,
            ],
            'performance' => [
                'caching_enabled' => true,
                'cache_ttl' => 1800, // 30 minutes
                'lazy_loading' => true,
                'compression' => true,
            ],
        ],

        'production' => [
            'storage' => [
                'driver' => env('DOCUMENTS_STORAGE_DRIVER', 's3'),
                'bucket' => env('AWS_DOCUMENTS_BUCKET', 'shicecal-documents-prod'),
                'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
                'visibility' => 'private',
                'cdn_url' => env('DOCUMENTS_CDN_URL'),
            ],
            'limits' => [
                'max_file_size' => 50 * 1024 * 1024, // 50MB for production
                'max_files_per_upload' => 50,
                'max_folder_depth' => 20,
                'max_files_per_folder' => 5000,
            ],
            'features' => [
                'virus_scanning' => true,
                'watermarking' => true,
                'watermark_text' => 'CONFIDENTIAL',
                'encryption' => true,
                'thumbnails' => true,
                'versioning' => true,
            ],
            'performance' => [
                'caching_enabled' => true,
                'cache_ttl' => 3600, // 1 hour
                'lazy_loading' => true,
                'compression' => true,
            ],
            'monitoring' => [
                'metrics_enabled' => true,
                'error_tracking' => true,
                'performance_monitoring' => true,
                'audit_logging' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Current Environment Configuration
    |--------------------------------------------------------------------------
    |
    | This returns the configuration for the current environment.
    |
    */

    'current' => function () {
        $env = app()->environment();
        $environments = config('document-environments.environments');
        
        return $environments[$env] ?? $environments['production'];
    },

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types by Environment
    |--------------------------------------------------------------------------
    |
    | Different environments may have different security requirements
    | for allowed file types.
    |
    */

    'allowed_file_types' => [
        'testing' => [
            'pdf', 'txt', 'jpg', 'png'
        ],
        'local' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
            'zip', 'rar'
        ],
        'staging' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
            'zip', 'rar', '7z'
        ],
        'production' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
            'zip', 'rar', '7z', 'mp4', 'avi', 'mov'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings by Environment
    |--------------------------------------------------------------------------
    |
    | Environment-specific security configurations.
    |
    */

    'security' => [
        'testing' => [
            'csrf_protection' => false,
            'rate_limiting' => false,
            'ip_whitelist' => [],
            'audit_logging' => false,
        ],
        'local' => [
            'csrf_protection' => true,
            'rate_limiting' => false,
            'ip_whitelist' => ['127.0.0.1', '::1'],
            'audit_logging' => true,
        ],
        'staging' => [
            'csrf_protection' => true,
            'rate_limiting' => true,
            'rate_limit' => '100,1', // 100 requests per minute
            'ip_whitelist' => [], // Allow all IPs in staging
            'audit_logging' => true,
        ],
        'production' => [
            'csrf_protection' => true,
            'rate_limiting' => true,
            'rate_limit' => '60,1', // 60 requests per minute
            'ip_whitelist' => [], // Configure as needed
            'audit_logging' => true,
            'intrusion_detection' => true,
        ],
    ],
];