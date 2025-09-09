<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comment System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the facility comment system including validation,
    | sections, and UI settings.
    |
    */

    'sections' => [
        'basic_info' => [
            'name' => '基本情報',
            'description' => '施設の基本情報に関するコメント',
            'icon' => 'fas fa-info-circle',
            'color' => 'primary'
        ],
        'contact_info' => [
            'name' => '住所・連絡先',
            'description' => '住所・連絡先情報に関するコメント',
            'icon' => 'fas fa-map-marker-alt',
            'color' => 'success'
        ],
        'building_info' => [
            'name' => '開設・建物情報',
            'description' => '開設・建物情報に関するコメント',
            'icon' => 'fas fa-calendar-alt',
            'color' => 'warning'
        ],
        'facility_info' => [
            'name' => '基本施設情報',
            'description' => '基本施設情報に関するコメント',
            'icon' => 'fas fa-home',
            'color' => 'info'
        ],
        'service_info' => [
            'name' => 'サービス情報',
            'description' => 'サービス種類・指定更新に関するコメント',
            'icon' => 'fas fa-cogs',
            'color' => 'info'
        ],
        'services' => [
            'name' => 'サービス種類',
            'description' => 'サービス種類に関するコメント',
            'icon' => 'fas fa-clipboard-list',
            'color' => 'secondary'
        ]
    ],

    'validation' => [
        'min_length' => env('COMMENT_MIN_LENGTH', 1),
        'max_length' => env('COMMENT_MAX_LENGTH', 500),
        'allowed_html_tags' => [],
        'rate_limit' => [
            'max_per_minute' => env('COMMENT_RATE_LIMIT', 10),
            'max_per_hour' => env('COMMENT_RATE_LIMIT_HOUR', 100)
        ]
    ],

    'ui' => [
        'default_variant' => 'outline-primary',
        'default_size' => 'sm',
        'show_text_by_default' => true,
        'auto_refresh_interval' => env('COMMENT_REFRESH_INTERVAL', 30000), // 30 seconds
        'animation_duration' => 300 // milliseconds
    ],

    'cache' => [
        'enabled' => env('COMMENT_CACHE_ENABLED', true),
        'ttl' => env('COMMENT_CACHE_TTL', 300), // 5 minutes
        'key_prefix' => 'facility_comments'
    ],

    'notifications' => [
        'enabled' => env('COMMENT_NOTIFICATIONS_ENABLED', true),
        'channels' => ['database', 'mail'],
        'notify_roles' => ['admin', 'editor', 'approver']
    ]
];