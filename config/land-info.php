<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Land Information Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the land information management system
    |
    */

    'validation' => [
        'max_file_size' => env('LAND_INFO_MAX_FILE_SIZE', 10485760), // 10MB in bytes
        'allowed_file_types' => [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx',
        ],
        'max_files_per_upload' => env('LAND_INFO_MAX_FILES', 5),
        'currency_max_value' => env('LAND_INFO_CURRENCY_MAX', 999999999999999),
        'area_max_value' => env('LAND_INFO_AREA_MAX', 99999999.99),
        'parking_max_value' => env('LAND_INFO_PARKING_MAX', 9999999999),
        'input_sanitization' => [
            'enabled' => env('LAND_INFO_SANITIZE_INPUTS', true),
            'max_input_length' => env('LAND_INFO_MAX_INPUT_LENGTH', 10000),
            'allow_html' => env('LAND_INFO_ALLOW_HTML', false),
            'strict_mode' => env('LAND_INFO_STRICT_VALIDATION', true),
        ],
        'rate_limiting' => [
            'enabled' => env('LAND_INFO_RATE_LIMIT_ENABLED', true),
            'max_requests_per_minute' => env('LAND_INFO_RATE_LIMIT_RPM', 60),
            'max_validation_attempts' => env('LAND_INFO_MAX_VALIDATION_ATTEMPTS', 10),
        ],
    ],

    'calculations' => [
        'cache_enabled' => env('LAND_INFO_CACHE_CALCULATIONS', true),
        'cache_ttl' => env('LAND_INFO_CACHE_TTL', 3600), // 1 hour
        'debounce_delay' => env('LAND_INFO_DEBOUNCE_DELAY', 300), // milliseconds
        'precision' => [
            'currency' => 0, // No decimal places for currency
            'area' => 2, // 2 decimal places for area
            'unit_price' => 0, // No decimal places for unit price
        ],
    ],

    'ui' => [
        'animation_duration' => env('LAND_INFO_ANIMATION_DURATION', 300), // milliseconds
        'enable_transitions' => env('LAND_INFO_ENABLE_TRANSITIONS', true),
        'enable_tooltips' => env('LAND_INFO_ENABLE_TOOLTIPS', true),
        'enable_character_counters' => env('LAND_INFO_ENABLE_COUNTERS', true),
        'auto_save_enabled' => env('LAND_INFO_AUTO_SAVE', false),
        'auto_save_interval' => env('LAND_INFO_AUTO_SAVE_INTERVAL', 30000), // 30 seconds
    ],

    'security' => [
        'enable_xss_protection' => env('LAND_INFO_XSS_PROTECTION', true),
        'enable_csrf_protection' => env('LAND_INFO_CSRF_PROTECTION', true),
        'sanitize_inputs' => env('LAND_INFO_SANITIZE_INPUTS', true),
        'max_input_length' => env('LAND_INFO_MAX_INPUT_LENGTH', 1000),
        'rate_limit' => [
            'enabled' => env('LAND_INFO_RATE_LIMIT', true),
            'max_attempts' => env('LAND_INFO_RATE_LIMIT_ATTEMPTS', 60),
            'decay_minutes' => env('LAND_INFO_RATE_LIMIT_DECAY', 1),
        ],
    ],

    'performance' => [
        'enable_dom_caching' => env('LAND_INFO_DOM_CACHING', true),
        'enable_calculation_caching' => env('LAND_INFO_CALC_CACHING', true),
        'enable_performance_monitoring' => env('LAND_INFO_PERFORMANCE_MONITORING', false),
        'cache_size_limit' => env('LAND_INFO_CACHE_SIZE_LIMIT', 100),
        'enable_lazy_loading' => env('LAND_INFO_LAZY_LOADING', true),
    ],

    'logging' => [
        'enabled' => env('LAND_INFO_LOGGING', true),
        'level' => env('LAND_INFO_LOG_LEVEL', 'info'),
        'log_calculations' => env('LAND_INFO_LOG_CALCULATIONS', false),
        'log_validations' => env('LAND_INFO_LOG_VALIDATIONS', false),
        'log_performance' => env('LAND_INFO_LOG_PERFORMANCE', false),
    ],

    'features' => [
        'enable_preview' => env('LAND_INFO_ENABLE_PREVIEW', true),
        'enable_export' => env('LAND_INFO_ENABLE_EXPORT', true),
        'enable_comments' => env('LAND_INFO_ENABLE_COMMENTS', true),
        'enable_history' => env('LAND_INFO_ENABLE_HISTORY', true),
        'enable_notifications' => env('LAND_INFO_ENABLE_NOTIFICATIONS', true),
    ],

    'ownership_types' => [
        'owned' => [
            'label' => '自社',
            'sections' => ['owned_section'],
            'required_fields' => ['ownership_type'],
            'optional_fields' => ['purchase_price', 'site_area_sqm', 'site_area_tsubo', 'parking_spaces'],
        ],
        'leased' => [
            'label' => '賃借',
            'sections' => ['leased_section', 'management_section', 'owner_section', 'file_section'],
            'required_fields' => ['ownership_type'],
            'optional_fields' => [
                'monthly_rent', 'contract_start_date', 'contract_end_date', 'auto_renewal',
                'management_company_name', 'owner_name', 'parking_spaces',
            ],
        ],
        'owned_rental' => [
            'label' => '自社（賃貸）',
            'sections' => ['owned_section', 'leased_section', 'file_section'],
            'required_fields' => ['ownership_type'],
            'optional_fields' => [
                'purchase_price', 'monthly_rent', 'contract_start_date', 'contract_end_date',
                'auto_renewal', 'site_area_sqm', 'site_area_tsubo', 'parking_spaces',
            ],
        ],
    ],

    'field_groups' => [
        'basic_info' => ['ownership_type', 'parking_spaces'],
        'area_info' => ['site_area_sqm', 'site_area_tsubo'],
        'owned_property' => ['purchase_price', 'unit_price_display'],
        'leased_property' => [
            'monthly_rent', 'contract_start_date', 'contract_end_date',
            'auto_renewal', 'contract_period_display',
        ],
        'management_company' => [
            'management_company_name', 'management_company_postal_code',
            'management_company_address', 'management_company_building',
            'management_company_phone', 'management_company_fax',
            'management_company_email', 'management_company_url',
            'management_company_notes',
        ],
        'owner_info' => [
            'owner_name', 'owner_postal_code', 'owner_address',
            'owner_building', 'owner_phone', 'owner_fax',
            'owner_email', 'owner_url', 'owner_notes',
        ],
    ],

    'error_messages' => [
        'calculation_failed' => '計算処理でエラーが発生しました。入力値を確認してください。',
        'validation_failed' => '入力内容に問題があります。エラーメッセージを確認してください。',
        'section_display_failed' => 'セクションの表示処理でエラーが発生しました。',
        'network_error' => 'ネットワークエラーが発生しました。接続を確認してください。',
        'file_too_large' => 'ファイルサイズが大きすぎます。10MB以下のファイルを選択してください。',
        'invalid_file_type' => '許可されていないファイル形式です。',
        'system_error' => 'システムエラーが発生しました。管理者にお問い合わせください。',
    ],

    'advanced' => [
        'batch_processing' => [
            'enabled' => env('LAND_INFO_BATCH_PROCESSING', true),
            'batch_size' => env('LAND_INFO_BATCH_SIZE', 50),
            'max_batch_time' => env('LAND_INFO_MAX_BATCH_TIME', 100), // milliseconds
        ],
        'state_management' => [
            'enabled' => env('LAND_INFO_STATE_MANAGEMENT', true),
            'persist_state' => env('LAND_INFO_PERSIST_STATE', false),
            'max_history_size' => env('LAND_INFO_MAX_HISTORY', 50),
        ],
        'error_handling' => [
            'detailed_errors' => env('LAND_INFO_DETAILED_ERRORS', false),
            'error_reporting' => env('LAND_INFO_ERROR_REPORTING', true),
            'fallback_mode' => env('LAND_INFO_FALLBACK_MODE', true),
        ],
        'accessibility' => [
            'enhanced_aria' => env('LAND_INFO_ENHANCED_ARIA', true),
            'keyboard_navigation' => env('LAND_INFO_KEYBOARD_NAV', true),
            'screen_reader_support' => env('LAND_INFO_SCREEN_READER', true),
        ],
        'development' => [
            'debug_mode' => env('LAND_INFO_DEBUG', false),
            'performance_monitoring' => env('LAND_INFO_PERF_MONITOR', false),
            'test_mode' => env('LAND_INFO_TEST_MODE', false),
        ],
    ],

    'integrations' => [
        'external_apis' => [
            'postal_code_lookup' => env('LAND_INFO_POSTAL_API', null),
            'address_validation' => env('LAND_INFO_ADDRESS_API', null),
            'currency_conversion' => env('LAND_INFO_CURRENCY_API', null),
        ],
        'notifications' => [
            'email_enabled' => env('LAND_INFO_EMAIL_NOTIFICATIONS', true),
            'slack_webhook' => env('LAND_INFO_SLACK_WEBHOOK', null),
            'teams_webhook' => env('LAND_INFO_TEAMS_WEBHOOK', null),
        ],
    ],
];
