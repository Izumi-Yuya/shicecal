<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Facility Form Layout Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the facility form layout
    | standardization system, including CSS classes, icons, and colors.
    |
    */

    'layout' => [
        'container_class' => 'container-fluid',
        'card_spacing' => 'mb-4',
        'section_spacing' => 'mb-4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Icons
    |--------------------------------------------------------------------------
    |
    | Default icons for different types of form sections. These use Font
    | Awesome classes and can be overridden on a per-component basis.
    |
    */
    'icons' => [
        'basic_info' => 'fas fa-info-circle',
        'land_info' => 'fas fa-map',
        'contact_info' => 'fas fa-phone',
        'building_info' => 'fas fa-building',
        'service_info' => 'fas fa-cogs',
        'area_info' => 'fas fa-ruler-combined',
        'owned_property' => 'fas fa-building',
        'leased_property' => 'fas fa-file-contract',
        'management_company' => 'fas fa-building',
        'owner_info' => 'fas fa-user-tie',
        'documents' => 'fas fa-file-pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Themes
    |--------------------------------------------------------------------------
    |
    | Bootstrap color classes for different section types and states.
    |
    */
    'colors' => [
        'primary' => 'primary',
        'success' => 'success',
        'info' => 'info',
        'warning' => 'warning',
        'danger' => 'danger',
        'secondary' => 'secondary',
        'dark' => 'dark',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Section Colors
    |--------------------------------------------------------------------------
    |
    | Default color assignments for different section types.
    |
    */
    'section_colors' => [
        'basic_info' => 'primary',
        'land_info' => 'primary',
        'contact_info' => 'primary',
        'building_info' => 'primary',
        'service_info' => 'primary',
        'area_info' => 'success',
        'owned_property' => 'info',
        'leased_property' => 'warning',
        'management_company' => 'secondary',
        'owner_info' => 'dark',
        'documents' => 'danger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Defaults
    |--------------------------------------------------------------------------
    |
    | Default values and configurations for form components.
    |
    */
    'defaults' => [
        'cancel_text' => 'キャンセル',
        'save_text' => '保存',
        'submit_icon' => 'fas fa-save',
        'back_icon' => 'fas fa-arrow-left',
        'edit_icon' => 'fas fa-edit',
        'delete_icon' => 'fas fa-trash',
        'view_icon' => 'fas fa-eye',
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for breadcrumb generation.
    |
    */
    'breadcrumbs' => [
        'home_title' => 'ホーム',
        'facility_detail_title' => '施設詳細',
        'separator' => '/',
        'show_icons' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Responsive Breakpoints
    |--------------------------------------------------------------------------
    |
    | CSS breakpoints for responsive design.
    |
    */
    'breakpoints' => [
        'mobile' => '768px',
        'tablet' => '992px',
        'desktop' => '1200px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Validation Messages
    |--------------------------------------------------------------------------
    |
    | Common validation messages for facility forms.
    |
    */
    'validation_messages' => [
        'required' => 'この項目は必須です',
        'numeric' => '数値を入力してください',
        'email' => '有効なメールアドレスを入力してください',
        'max_length' => '文字数が上限を超えています',
        'min_value' => '値が小さすぎます',
    ],
];
