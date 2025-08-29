<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains various optimization settings for production
    | environment to improve performance and security.
    |
    */

    'cache' => [
        'config_cache' => true,
        'route_cache' => true,
        'view_cache' => true,
        'event_cache' => true,
    ],

    'database' => [
        'query_cache' => true,
        'connection_pooling' => true,
        'read_write_split' => false,
    ],

    'session' => [
        'driver' => 'redis',
        'lifetime' => 120,
        'encrypt' => true,
        'secure' => true,
        'same_site' => 'strict',
    ],

    'security' => [
        'force_https' => true,
        'hsts_enabled' => true,
        'csrf_protection' => true,
        'xss_protection' => true,
        'content_type_nosniff' => true,
        'frame_options' => 'SAMEORIGIN',
    ],

    'performance' => [
        'opcache_enabled' => true,
        'gzip_compression' => true,
        'asset_versioning' => true,
        'cdn_enabled' => false,
        'lazy_loading' => true,
    ],

    'monitoring' => [
        'error_tracking' => true,
        'performance_monitoring' => true,
        'log_level' => 'error',
        'debug_mode' => false,
    ],

    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',
        'retention_days' => 30,
        'storage_disk' => 's3',
    ],
];