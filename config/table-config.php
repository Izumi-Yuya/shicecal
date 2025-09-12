<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for standardized facility tables.
    | Each table type has its own configuration defining columns, layout,
    | styling, and features.
    |
    */

    'tables' => [
        'basic_info' => [
            'comment_display_name' => '基本情報',
            'columns' => [
                [
                    'key' => 'company_name',
                    'label' => '会社名',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'office_code',
                    'label' => '事業所コード',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'facility_name',
                    'label' => '施設名',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => true,
                    'empty_text' => '未設定',
                    'special_formatting' => 'bold'
                ],
                [
                    'key' => 'designation_number',
                    'label' => '指定番号',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'postal_code',
                    'label' => '郵便番号',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => false,
                    'empty_text' => '未設定',
                    'accessor' => 'formatted_postal_code'
                ],
                [
                    'key' => 'opening_date',
                    'label' => '開設日',
                    'type' => 'date',
                    'width' => '8em',
                    'format' => 'Y年m月d日',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'address',
                    'label' => '住所',
                    'type' => 'text',
                    'width' => '8em',
                    'required' => false,
                    'empty_text' => '未設定',
                    'accessor' => 'full_address'
                ],
                [
                    'key' => 'years_in_operation',
                    'label' => '開設年数',
                    'type' => 'number',
                    'width' => '8em',
                    'unit' => '年',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'building_name',
                    'label' => '住所（建物名）',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'building_structure',
                    'label' => '建物構造',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'phone_number',
                    'label' => '電話番号',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'building_floors',
                    'label' => '建物階数',
                    'type' => 'number',
                    'width' => '25%',
                    'unit' => '階',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'fax_number',
                    'label' => 'FAX番号',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'paid_rooms_count',
                    'label' => '居室数',
                    'type' => 'number',
                    'width' => '25%',
                    'unit' => '室',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'toll_free_number',
                    'label' => 'フリーダイヤル',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'ss_rooms_count',
                    'label' => '内SS数',
                    'type' => 'number',
                    'width' => '25%',
                    'unit' => '室',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'email',
                    'label' => 'メールアドレス',
                    'type' => 'email',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'capacity',
                    'label' => '定員数',
                    'type' => 'number',
                    'width' => '25%',
                    'unit' => '名',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'website_url',
                    'label' => 'URL',
                    'type' => 'url',
                    'width' => '100%',
                    'colspan' => 3,
                    'required' => false,
                    'empty_text' => '未設定'
                ]
            ],
            'layout' => [
                'type' => 'key_value_pairs',
                'columns_per_row' => 2,
                'responsive_breakpoint' => 'lg'
            ],
            'styling' => [
                'table_class' => 'table table-bordered facility-info',
                'header_class' => 'bg-primary text-white',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => true,
                'sorting' => false,
                'filtering' => false
            ]
        ],

        'service_info' => [
            'comment_display_name' => 'サービス情報',
            'columns' => [
                [
                    'key' => 'service_type',
                    'label' => 'サービス種類',
                    'type' => 'text',
                    'width' => '20%',
                    'rowspan_group' => true,
                    'required' => false,
                    'empty_text' => ''
                ],
                [
                    'key' => 'renewal_start_date',
                    'label' => '有効期限開始',
                    'type' => 'date',
                    'width' => '20%',
                    'format' => 'Y年n月j日',
                    'required' => false,
                    'empty_text' => ''
                ],
                [
                    'key' => 'period_separator',
                    'label' => '区切り',
                    'type' => 'text',
                    'width' => '5%',
                    'static_value' => '〜',
                    'required' => false,
                    'empty_text' => '〜'
                ],
                [
                    'key' => 'renewal_end_date',
                    'label' => '有効期限終了',
                    'type' => 'date',
                    'width' => '20%',
                    'format' => 'Y年n月j日',
                    'required' => false,
                    'empty_text' => ''
                ]
            ],
            'layout' => [
                'type' => 'service_table',
                'group_by' => 'service_type',
                'show_headers' => false,
                'hierarchical_headers' => false,
                'service_header_rowspan' => true
            ],
            'styling' => [
                'table_class' => 'table table-bordered service-info',
                'header_class' => 'bg-info text-white',
                'group_class' => 'table-group',
                'rowspan_class' => 'rowspan-cell',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => true,
                'sorting' => true,
                'filtering' => false,
                'advanced_rowspan' => true
            ]
        ],

        'land_info' => [
            'comment_display_name' => '土地情報',
            'columns' => [
                [
                    'key' => 'ownership_type',
                    'label' => '所有形態',
                    'type' => 'select',
                    'width' => '15%',
                    'options' => [
                        'owned' => '自社',
                        'leased' => '賃借',
                        'owned_rental' => '自社（賃貸）'
                    ],
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'parking_spaces',
                    'label' => '敷地内駐車場台数',
                    'type' => 'number',
                    'width' => '15%',
                    'unit' => '台',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'site_area_sqm',
                    'label' => '敷地面積（㎡）',
                    'type' => 'number',
                    'width' => '15%',
                    'unit' => '㎡',
                    'decimals' => 2,
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'site_area_tsubo',
                    'label' => '敷地面積（坪）',
                    'type' => 'number',
                    'width' => '15%',
                    'unit' => '坪',
                    'decimals' => 2,
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'purchase_price',
                    'label' => '購入金額',
                    'type' => 'number',
                    'width' => '15%',
                    'unit' => '円',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'monthly_rent',
                    'label' => '月額賃料',
                    'type' => 'number',
                    'width' => '15%',
                    'unit' => '円',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'contract_start_date',
                    'label' => '契約開始日',
                    'type' => 'date',
                    'width' => '15%',
                    'format' => 'Y年m月d日',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                [
                    'key' => 'contract_end_date',
                    'label' => '契約終了日',
                    'type' => 'date',
                    'width' => '15%',
                    'format' => 'Y年m月d日',
                    'required' => false,
                    'empty_text' => '未設定'
                ]
            ],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true,
                'responsive_breakpoint' => 'md'
            ],
            'styling' => [
                'table_class' => 'table table-bordered land-info',
                'header_class' => 'bg-success text-white',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => true,
                'sorting' => true,
                'filtering' => true
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    |
    | Global configuration settings that apply to all table types.
    |
    */

    'global_settings' => [
        'responsive' => [
            'enabled' => true,
            'pc_only' => true,
            'breakpoints' => [
                'lg' => '992px',
                'md' => '768px',
                'sm' => '576px'
            ]
        ],
        'performance' => [
            'cache_enabled' => true,
            'cache_ttl' => 300
        ],
        'validation' => [
            'strict_mode' => true,
            'required_fields' => ['key', 'label', 'type']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for integrating the comment system with tables.
    |
    */

    'comment_sections' => [
        'basic_info' => [
            'section_name' => 'basic_info',
            'display_name' => '基本情報',
            'enabled' => true
        ],
        'service_info' => [
            'section_name' => 'service_info',
            'display_name' => 'サービス情報',
            'enabled' => true
        ],
        'land_info' => [
            'section_name' => 'land_info',
            'display_name' => '土地情報',
            'enabled' => true
        ]
    ]
];