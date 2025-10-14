<?php

namespace App\Helpers;

use App\Models\Facility;

class FacilityFormHelper
{
    /**
     * Generate breadcrumbs array for facility forms
     */
    public static function generateBreadcrumbs(string $currentPage, ?Facility $facility = null, array $additionalCrumbs = []): array
    {
        $breadcrumbs = [
            [
                'title' => 'ホーム',
                'route' => 'facilities.index',
                'active' => false,
            ],
        ];

        if ($facility) {
            $breadcrumbs[] = [
                'title' => '施設詳細',
                'route' => 'facilities.show',
                'params' => [$facility],
                'active' => false,
            ];
        }

        // Add any additional breadcrumbs
        foreach ($additionalCrumbs as $crumb) {
            $breadcrumbs[] = array_merge([
                'active' => false,
            ], $crumb);
        }

        // Add current page as active breadcrumb
        $breadcrumbs[] = [
            'title' => $currentPage,
            'active' => true,
        ];

        return $breadcrumbs;
    }

    /**
     * Get icon class for a section type
     */
    public static function getSectionIcon(string $sectionType): string
    {
        $icons = config('facility-form.icons', []);

        return $icons[$sectionType] ?? 'fas fa-cog';
    }

    /**
     * Get color class for a section type
     */
    public static function getSectionColor(string $sectionType): string
    {
        $colors = config('facility-form.section_colors', []);

        return $colors[$sectionType] ?? 'primary';
    }

    /**
     * Generate form section configuration
     */
    public static function getSectionConfig(string $sectionType, ?string $customTitle = null, ?string $customIcon = null, ?string $customColor = null): array
    {
        return [
            'title' => $customTitle ?? self::getSectionTitle($sectionType),
            'icon' => $customIcon ?? self::getSectionIcon($sectionType),
            'color' => $customColor ?? self::getSectionColor($sectionType),
        ];
    }

    /**
     * Get default title for section type
     */
    public static function getSectionTitle(string $sectionType): string
    {
        $titles = [
            'basic_info' => '基本情報',
            'land_info' => '土地情報',
            'contact_info' => '連絡先情報',
            'building_info' => '建物情報',
            'service_info' => 'サービス情報',
            'area_info' => '面積情報',
            'owned_property' => '自社物件情報',
            'leased_property' => '賃借物件情報',
            'management_company' => '管理会社情報',
            'owner_info' => 'オーナー情報',
            'documents' => '関連書類',
        ];

        return $titles[$sectionType] ?? ucfirst(str_replace('_', ' ', $sectionType));
    }

    /**
     * Generate facility info card data
     */
    public static function getFacilityCardData(Facility $facility): array
    {
        return [
            'name' => $facility->name ?? '未設定',
            'address' => $facility->address ?? '住所未設定',
            'type' => $facility->type ?? '種別未設定',
            'prefecture' => $facility->prefecture ?? '',
            'city' => $facility->city ?? '',
        ];
    }

    /**
     * Generate form action URL based on context
     */
    public static function getFormAction(string $action, ?Facility $facility = null, array $parameters = []): string
    {
        if ($facility) {
            $parameters = array_merge([$facility], $parameters);
        }

        return route($action, $parameters);
    }

    /**
     * Get layout configuration
     *
     * @param  mixed  $default
     * @return mixed
     */
    public static function getLayoutConfig(string $key, $default = null)
    {
        return config("facility-form.layout.{$key}", $default);
    }

    /**
     * Generate breadcrumbs for land info edit page
     */
    public static function getLandInfoEditBreadcrumbs(Facility $facility): array
    {
        return self::generateBreadcrumbs('土地情報編集', $facility);
    }

    /**
     * Generate breadcrumbs for basic info edit page
     */
    public static function getBasicInfoEditBreadcrumbs(Facility $facility): array
    {
        return self::generateBreadcrumbs('基本情報編集', $facility);
    }

    /**
     * Generate breadcrumbs for service edit page
     */
    public static function getServiceEditBreadcrumbs(Facility $facility): array
    {
        return self::generateBreadcrumbs('サービス情報編集', $facility);
    }

    /**
     * Get all available section types
     */
    public static function getAvailableSectionTypes(): array
    {
        return array_keys(config('facility-form.icons', []));
    }

    /**
     * Validate section type
     */
    public static function isValidSectionType(string $sectionType): bool
    {
        return in_array($sectionType, self::getAvailableSectionTypes());
    }

    /**
     * Get form validation rules for common fields
     */
    public static function getCommonValidationRules(string $context = 'default'): array
    {
        $rules = [
            'default' => [
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:500',
                'type' => 'nullable|string|max:100',
            ],
            'land_info' => [
                'land_area' => 'nullable|numeric|min:0',
                'building_area' => 'nullable|numeric|min:0',
                'floor_area' => 'nullable|numeric|min:0',
            ],
            'contact' => [
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'contact_person' => 'nullable|string|max:100',
            ],
        ];

        return $rules[$context] ?? $rules['default'];
    }

    /**
     * Get error field mappings for land info form sections
     */
    public static function getLandInfoErrorFieldMappings(): array
    {
        return [
            'basic_info' => [
                'ownership_type',
                'parking_spaces',
            ],
            'area_info' => [
                'site_area_sqm',
                'site_area_tsubo',
            ],
            'owned_property' => [
                'purchase_price',
            ],
            'leased_property' => [
                'monthly_rent',
                'contract_start_date',
                'contract_end_date',
                'auto_renewal',
            ],
            'management_company' => [
                'management_company_name',
                'management_company_postal_code',
                'management_company_address',
                'management_company_building',
                'management_company_phone',
                'management_company_fax',
                'management_company_email',
                'management_company_url',
                'management_company_notes',
            ],
            'owner_info' => [
                'owner_name',
                'owner_postal_code',
                'owner_address',
                'owner_building',
                'owner_phone',
                'owner_fax',
                'owner_email',
                'owner_url',
                'owner_notes',
            ],
            'documents' => [
                'lease_contract_pdf',
                'registry_pdf',
            ],
            'notes' => [
                'notes',
            ],
        ];
    }

    /**
     * Get error field mappings for basic info form sections
     */
    public static function getBasicInfoErrorFieldMappings(): array
    {
        return [
            'basic_info' => [
                'company_name',
                'office_code',
                'designation_number',
                'designation_number_2',
                'facility_name',
            ],
            'contact_info' => [
                'postal_code',
                'address',
                'building_name',
                'phone_number',
                'fax_number',
                'toll_free_number',
                'email',
                'website_url',
            ],
            'building_info' => [
                'opening_date',
                'years_in_operation',
                'building_structure',
                'building_floors',
            ],
            'facility_info' => [
                'paid_rooms_count',
                'ss_rooms_count',
                'capacity',
            ],
            'services' => [
                'services.*.service_type',
                'services.*.renewal_start_date',
                'services.*.renewal_end_date',
            ],
        ];
    }

    /**
     * Get error fields for a specific section
     */
    public static function getErrorFieldsForSection(string $sectionType, string $formType = 'land_info'): array
    {
        $mappings = match ($formType) {
            'land_info' => self::getLandInfoErrorFieldMappings(),
            'basic_info' => self::getBasicInfoErrorFieldMappings(),
            default => []
        };

        return $mappings[$sectionType] ?? [];
    }
}
