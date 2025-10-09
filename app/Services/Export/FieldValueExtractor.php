<?php

namespace App\Services\Export;

use App\Models\Facility;
use Illuminate\Support\Facades\Log;

/**
 * Field Value Extractor
 * Optimized field value extraction for CSV export
 */
class FieldValueExtractor
{
    /**
     * Cache for relationship data to avoid N+1 queries
     */
    private array $relationshipCache = [];

    /**
     * Get field value with optimized relationship loading
     */
    public function getFieldValue(Facility $facility, string $field): string
    {
        try {
            // Use cached relationships when possible
            return match (true) {
                str_starts_with($field, 'land_') => $this->getLandFieldValue($facility, $field),
                str_starts_with($field, 'building_') => $this->getBuildingFieldValue($facility, $field),
                str_starts_with($field, 'electrical_') => $this->getElectricalFieldValue($facility, $field),
                str_starts_with($field, 'water_') => $this->getWaterFieldValue($facility, $field),
                str_starts_with($field, 'gas_') => $this->getGasFieldValue($facility, $field),
                str_starts_with($field, 'elevator_') => $this->getElevatorFieldValue($facility, $field),
                str_starts_with($field, 'hvac_') => $this->getHvacFieldValue($facility, $field),
                str_starts_with($field, 'drawing_') => $this->getDrawingFieldValue($facility, $field),
                str_starts_with($field, 'maintenance_') => $this->getMaintenanceFieldValue($facility, $field),
                str_starts_with($field, 'contract_') => $this->getContractFieldValue($facility, $field),
                default => $this->getFacilityFieldValue($facility, $field),
            };
        } catch (\Exception $e) {
            Log::warning('Field value extraction failed', [
                'facility_id' => $facility->id,
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Get land information field value
     */
    private function getLandFieldValue(Facility $facility, string $field): string
    {
        $landInfo = $this->getCachedRelationship($facility, 'landInfo');
        if (!$landInfo) {
            return '';
        }

        return match ($field) {
            'land_ownership_type' => $this->formatOwnershipType($landInfo->ownership_type ?? ''),
            'land_site_area_sqm' => $landInfo->site_area_sqm ? (string)$landInfo->site_area_sqm : '',
            'land_site_area_tsubo' => $landInfo->site_area_tsubo ? (string)$landInfo->site_area_tsubo : '',
            'land_parking_spaces' => $landInfo->parking_spaces ? (string)$landInfo->parking_spaces : '',
            'land_purchase_price' => $landInfo->purchase_price ? (string)$landInfo->purchase_price : '',
            'land_unit_price_per_tsubo' => $landInfo->unit_price_per_tsubo ? (string)$landInfo->unit_price_per_tsubo : '',
            'land_monthly_rent' => $landInfo->monthly_rent ? (string)$landInfo->monthly_rent : '',
            'land_contract_period' => $this->formatContractPeriod($landInfo),
            'land_auto_renewal' => $this->formatAutoRenewal($landInfo->auto_renewal ?? ''),
            'land_contract_period_text' => $landInfo->contract_period_text ?? '',
            'land_notes' => $landInfo->notes ?? '',
            default => $this->getLandContactFieldValue($landInfo, $field),
        };
    }

    /**
     * Get cached relationship or load it
     */
    private function getCachedRelationship(Facility $facility, string $relationship)
    {
        $cacheKey = $facility->id . '_' . $relationship;
        
        if (!isset($this->relationshipCache[$cacheKey])) {
            $this->relationshipCache[$cacheKey] = $facility->$relationship;
        }
        
        return $this->relationshipCache[$cacheKey];
    }

    /**
     * Format ownership type for display
     */
    private function formatOwnershipType(?string $type): string
    {
        return match ($type) {
            'owned' => '自社所有',
            'leased' => '賃貸',
            'other' => 'その他',
            default => $type ?? '',
        };
    }

    /**
     * Format contract period
     */
    private function formatContractPeriod($landInfo): string
    {
        if ($landInfo->contract_start_date && $landInfo->contract_end_date) {
            return $landInfo->contract_start_date->format('Y年n月j日') . ' ～ ' . 
                   $landInfo->contract_end_date->format('Y年n月j日');
        }
        return '';
    }

    /**
     * Format auto renewal status
     */
    private function formatAutoRenewal($autoRenewal): string
    {
        return match ($autoRenewal) {
            true, 'あり', '1' => 'あり',
            false, 'なし', '0' => 'なし',
            default => '',
        };
    }

    /**
     * Get land contact field values (management company, owner)
     */
    private function getLandContactFieldValue($landInfo, string $field): string
    {
        return match ($field) {
            'land_management_company_name' => $landInfo->management_company_name ?? '',
            'land_management_company_postal_code' => $landInfo->management_company_postal_code ?? '',
            'land_management_company_address' => $landInfo->management_company_address ?? '',
            'land_management_company_building' => $landInfo->management_company_building ?? '',
            'land_management_company_phone' => $landInfo->management_company_phone ?? '',
            'land_management_company_fax' => $landInfo->management_company_fax ?? '',
            'land_management_company_email' => $landInfo->management_company_email ?? '',
            'land_management_company_url' => $landInfo->management_company_url ?? '',
            'land_management_company_notes' => $landInfo->management_company_notes ?? '',
            'land_owner_name' => $landInfo->owner_name ?? '',
            'land_owner_postal_code' => $landInfo->owner_postal_code ?? '',
            'land_owner_address' => $landInfo->owner_address ?? '',
            'land_owner_building' => $landInfo->owner_building ?? '',
            'land_owner_phone' => $landInfo->owner_phone ?? '',
            'land_owner_fax' => $landInfo->owner_fax ?? '',
            'land_owner_email' => $landInfo->owner_email ?? '',
            'land_owner_url' => $landInfo->owner_url ?? '',
            'land_owner_notes' => $landInfo->owner_notes ?? '',
            default => '',
        };
    }

    /**
     * Get facility basic field value
     */
    private function getFacilityFieldValue(Facility $facility, string $field): string
    {
        return match ($field) {
            'company_name' => $facility->company_name ?? '',
            'office_code' => $facility->office_code ?? '',
            'facility_name' => $facility->facility_name ?? '',
            'designation_number' => $facility->designation_number ?? '',
            'postal_code' => $facility->formatted_postal_code ?? $facility->postal_code ?? '',
            'opening_date' => $facility->opening_date?->format('Y-m-d') ?? '',
            'address' => $facility->full_address ?? $facility->address ?? '',
            'opening_years' => $facility->opening_date ? (string)$facility->opening_date->diffInYears(now()) : '',
            'building_name' => $facility->building_name ?? '',
            'building_structure' => $facility->building_structure ?? '',
            'phone_number' => $facility->phone_number ?? '',
            'building_floors' => $facility->building_floors ? (string)$facility->building_floors : '',
            'fax_number' => $facility->fax_number ?? '',
            'paid_rooms_count' => $facility->paid_rooms_count !== null ? (string)$facility->paid_rooms_count : '',
            'toll_free_number' => $facility->toll_free_number ?? '',
            'ss_rooms_count' => $facility->ss_rooms_count !== null ? (string)$facility->ss_rooms_count : '',
            'email' => $facility->email ?? '',
            'capacity' => $facility->capacity ? (string)$facility->capacity : '',
            'website_url' => $facility->website_url ?? '',
            'status' => $facility->status ?? '',
            'approved_at' => $facility->approved_at?->format('Y-m-d H:i:s') ?? '',
            'created_at' => $facility->created_at?->format('Y-m-d H:i:s') ?? '',
            'updated_at' => $facility->updated_at?->format('Y-m-d H:i:s') ?? '',
            default => '',
        };
    }

    // Additional methods for other field types would go here...
    // getBuildingFieldValue, getElectricalFieldValue, etc.
}