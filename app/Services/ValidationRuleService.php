<?php

namespace App\Services;

class ValidationRuleService
{
    /**
     * Get validation rules for land info based on ownership type
     */
    public static function getLandInfoRules(string $ownershipType): array
    {
        $baseRules = [
            'ownership_type' => ['required', 'in:owned,leased,owned_rental'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'site_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'site_area_tsubo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];

        return match ($ownershipType) {
            'owned' => array_merge($baseRules, self::getOwnedPropertyRules()),
            'leased' => array_merge($baseRules, self::getLeasedPropertyRules(), self::getManagementRules(), self::getOwnerRules()),
            'owned_rental' => array_merge($baseRules, self::getOwnedPropertyRules(), self::getLeasedPropertyRules()),
            default => $baseRules
        };
    }

    /**
     * Get validation configuration for frontend
     */
    public static function getValidationConfig(): array
    {
        return [
            'rules' => [
                'currency_max' => 999999999999999,
                'area_max' => 99999999.99,
                'parking_max' => 9999999999,
                'text_max' => 1000,
            ],
            'patterns' => [
                'postal_code' => '/^\d{3}-\d{4}$/',
                'phone' => '/^\d{2,4}-\d{2,4}-\d{4}$/',
                'email' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            ],
            'ownership_requirements' => [
                'owned' => ['purchase_price', 'site_area_required'],
                'leased' => ['monthly_rent', 'contract_start_date', 'contract_end_date'],
                'owned_rental' => ['purchase_price', 'monthly_rent', 'contract_start_date', 'contract_end_date'],
            ],
        ];
    }

    private static function getOwnedPropertyRules(): array
    {
        return [
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:999999999999999'],
            'site_area_tsubo' => ['required_without:site_area_sqm', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'site_area_sqm' => ['required_without:site_area_tsubo', 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    private static function getLeasedPropertyRules(): array
    {
        return [
            'monthly_rent' => ['required', 'numeric', 'min:0', 'max:999999999999999'],
            'contract_start_date' => ['required', 'date', 'before_or_equal:contract_end_date'],
            'contract_end_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'auto_renewal' => ['nullable', 'in:yes,no'],
        ];
    }

    private static function getManagementRules(): array
    {
        return [
            'management_company_name' => ['nullable', 'string', 'max:30'],
            'management_company_postal_code' => ['nullable', 'regex:/^\d{3}-\d{4}$/'],
            'management_company_address' => ['nullable', 'string', 'max:30'],
            'management_company_building' => ['nullable', 'string', 'max:20'],
            'management_company_phone' => ['nullable', 'regex:/^\d{2,4}-\d{2,4}-\d{4}$/'],
            'management_company_fax' => ['nullable', 'regex:/^\d{2,4}-\d{2,4}-\d{4}$/'],
            'management_company_email' => ['nullable', 'email', 'max:100'],
            'management_company_url' => ['nullable', 'url', 'max:100'],
            'management_company_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private static function getOwnerRules(): array
    {
        return [
            'owner_name' => ['nullable', 'string', 'max:30'],
            'owner_postal_code' => ['nullable', 'regex:/^\d{3}-\d{4}$/'],
            'owner_address' => ['nullable', 'string', 'max:30'],
            'owner_building' => ['nullable', 'string', 'max:20'],
            'owner_phone' => ['nullable', 'regex:/^\d{2,4}-\d{2,4}-\d{4}$/'],
            'owner_fax' => ['nullable', 'regex:/^\d{2,4}-\d{2,4}-\d{4}$/'],
            'owner_email' => ['nullable', 'email', 'max:100'],
            'owner_url' => ['nullable', 'url', 'max:100'],
            'owner_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
