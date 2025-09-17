<?php

namespace App\Services;

class ValidationRuleService
{
    /**
     * Get validation rules for land info based on ownership type
     */
    public static function getLandInfoRules(string $ownershipType): array
    {
        // Return minimal validation rules - all fields are optional
        return [
            'ownership_type' => ['nullable', 'in:owned,leased,owned_rental'],
            'parking_spaces' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'site_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'site_area_tsubo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            // All other fields are optional with basic validation only
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'auto_renewal' => ['nullable', 'in:yes,no'],
            // Management company fields
            'management_company_name' => ['nullable', 'string', 'max:100'],
            'management_company_postal_code' => ['nullable', 'string', 'max:20'],
            'management_company_address' => ['nullable', 'string', 'max:100'],
            'management_company_building' => ['nullable', 'string', 'max:100'],
            'management_company_phone' => ['nullable', 'string', 'max:20'],
            'management_company_fax' => ['nullable', 'string', 'max:20'],
            'management_company_email' => ['nullable', 'email', 'max:100'],
            'management_company_url' => ['nullable', 'url', 'max:200'],
            'management_company_notes' => ['nullable', 'string', 'max:2000'],
            // Owner fields
            'owner_name' => ['nullable', 'string', 'max:100'],
            'owner_postal_code' => ['nullable', 'string', 'max:20'],
            'owner_address' => ['nullable', 'string', 'max:100'],
            'owner_building' => ['nullable', 'string', 'max:100'],
            'owner_phone' => ['nullable', 'string', 'max:20'],
            'owner_fax' => ['nullable', 'string', 'max:20'],
            'owner_email' => ['nullable', 'email', 'max:100'],
            'owner_url' => ['nullable', 'url', 'max:200'],
            'owner_notes' => ['nullable', 'string', 'max:2000'],
        ];
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
