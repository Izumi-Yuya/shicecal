<?php

/**
 * Validate configuration values
 */
$maxServices = (int) env('SERVICE_TABLE_MAX_SERVICES', 10);
$cacheTtl = (int) env('SERVICE_TABLE_CACHE_TTL', 300);
$maxNameLength = (int) env('SERVICE_TABLE_MAX_NAME_LENGTH', 100);

// Ensure reasonable limits
$maxServices = max(1, min($maxServices, 50)); // Between 1 and 50
$cacheTtl = max(60, min($cacheTtl, 3600)); // Between 1 minute and 1 hour
$maxNameLength = max(10, min($maxNameLength, 500)); // Between 10 and 500 characters

return [
    /*
    |--------------------------------------------------------------------------
    | Service Table Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the service information table component
    | Type-safe configuration with environment variable overrides
    |
    */

    'display' => [
        'max_services' => $maxServices,
        'show_empty_rows' => filter_var(env('SERVICE_TABLE_SHOW_EMPTY_ROWS', false), FILTER_VALIDATE_BOOLEAN),
        'enable_comments' => filter_var(env('SERVICE_TABLE_ENABLE_COMMENTS', true), FILTER_VALIDATE_BOOLEAN),
        'date_format' => env('SERVICE_TABLE_DATE_FORMAT', 'Y年m月d日'),
        'items_per_page' => (int) env('SERVICE_TABLE_ITEMS_PER_PAGE', 25),
        'min_display_rows' => (int) env('SERVICE_TABLE_MIN_ROWS', 1),
        'period_separator' => env('SERVICE_TABLE_PERIOD_SEPARATOR', '〜'),
    ],

    'columns' => [
        'service_type' => [
            'label' => 'サービス種類',
            'width_percentage' => 40,
            'mobile_width_percentage' => 50,
            'css_class' => 'col-service-type',
            'sortable' => true,
            'required' => true,
        ],
        'service_period' => [
            'label' => '有効期限',
            'width_percentage' => 35,
            'mobile_width_percentage' => 50,
            'css_class' => 'col-service-period',
            'sortable' => true,
            'required' => false,
        ],
        'actions' => [
            'label' => '',
            'width_percentage' => 25,
            'mobile_width_percentage' => 100,
            'css_class' => 'col-service-actions',
            'sortable' => false,
            'required' => false,
        ],
    ],

    'styling' => [
        'table_class' => 'service-info table table-bordered',
        'header_bg_class' => 'bg-info',
        'header_text_class' => 'text-white',
        'empty_value_class' => 'text-muted',
        'empty_value_text' => '未設定',
        'hover_effect' => true,
        'striped_rows' => false,
    ],

    'cache' => [
        'enabled' => filter_var(env('SERVICE_TABLE_CACHE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'ttl' => $cacheTtl,
        'key_prefix' => env('SERVICE_TABLE_CACHE_PREFIX', 'service_table'),
        'tags' => ['service_table', 'facility_data'],
    ],

    'validation' => [
        'max_service_name_length' => $maxNameLength,
        'required_fields' => ['service_type'],
        'date_validation' => filter_var(env('SERVICE_TABLE_DATE_VALIDATION', true), FILTER_VALIDATE_BOOLEAN),
        'allow_future_dates' => filter_var(env('SERVICE_TABLE_ALLOW_FUTURE_DATES', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'accessibility' => [
        'enable_aria_labels' => true,
        'enable_screen_reader_support' => true,
        'keyboard_navigation' => true,
        'high_contrast_support' => true,
    ],

    'performance' => [
        'lazy_loading' => filter_var(env('SERVICE_TABLE_LAZY_LOADING', false), FILTER_VALIDATE_BOOLEAN),
        'debounce_search_ms' => (int) env('SERVICE_TABLE_DEBOUNCE_MS', 300),
    ],
];
