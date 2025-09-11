<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Mode Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for facility view modes.
    | Centralizes view mode settings for better maintainability.
    |
    */

    'session_key' => 'facility_basic_info_view_mode',

    'default_mode' => 'card',

    'available_modes' => [
        'card' => [
            'name' => 'カード形式',
            'description' => 'カード形式で施設情報を表示',
            'icon' => 'fas fa-th-large',
            'css_class' => 'view-mode-card',
        ],
        'table' => [
            'name' => 'テーブル形式',
            'description' => 'テーブル形式で施設情報を表示',
            'icon' => 'fas fa-table',
            'css_class' => 'view-mode-table',
        ],
    ],

    'cache' => [
        'enabled' => env('VIEW_MODE_CACHE_ENABLED', true),
        'ttl' => env('VIEW_MODE_CACHE_TTL', 3600), // 1 hour
    ],

    'validation' => [
        'max_switches_per_minute' => 10,
        'throttle_key' => 'view_mode_switches',
    ],
];
