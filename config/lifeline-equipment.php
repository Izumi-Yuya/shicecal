<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lifeline Equipment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration values for lifeline equipment management system
    |
    */

    'document_management' => [
        'max_file_size' => '10MB',
        'allowed_file_types' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        'height' => [
            'edit_mode' => '500px',
            'readonly_mode' => '400px',
        ],
        'pagination' => [
            'per_page' => 50,
            'max_per_page' => 100,
        ],
        'cache' => [
            'file_types_ttl' => 300, // 5 minutes
        ],
    ],

    'categories' => [
        'electrical' => [
            'name' => '電気設備',
            'icon' => 'fas fa-bolt',
            'color' => 'text-warning',
        ],
        'water' => [
            'name' => '水道設備',
            'icon' => 'fas fa-tint',
            'color' => 'text-info',
        ],
        'gas' => [
            'name' => 'ガス設備',
            'icon' => 'fas fa-fire',
            'color' => 'text-danger',
        ],
        'elevator' => [
            'name' => 'エレベーター設備',
            'icon' => 'fas fa-elevator',
            'color' => 'text-secondary',
        ],
        'hvac_lighting' => [
            'name' => '空調・照明設備',
            'icon' => 'fas fa-snowflake',
            'color' => 'text-success',
        ],
        'security_disaster' => [
            'name' => '防犯・防災設備',
            'icon' => 'fas fa-shield-alt',
            'color' => 'text-dark',
        ],
    ],

    'default_subfolders' => [
        'inspection_reports' => '点検報告書',
        'maintenance_records' => '保守記録',
        'manuals' => '取扱説明書',
        'certificates' => '証明書類',
        'past_reports' => '過去分報告書',
    ],
];