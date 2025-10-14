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
                str_starts_with($field, 'lighting_') => $this->getHvacFieldValue($facility, $field),
                str_starts_with($field, 'drawing_') => $this->getDrawingFieldValue($facility, $field),
                str_starts_with($field, 'security_') => $this->getSecurityDisasterFieldValue($facility, $field),
                str_starts_with($field, 'fire_') => $this->getSecurityDisasterFieldValue($facility, $field),
                str_starts_with($field, 'disaster_') => $this->getSecurityDisasterFieldValue($facility, $field),
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
            'service_types' => $this->formatServiceTypes($facility->service_types ?? []),
            'service_validity_periods' => '', // このフィールドは現在データベースに存在しない
            'status' => $facility->status ?? '',
            'approved_at' => $facility->approved_at?->format('Y-m-d H:i:s') ?? '',
            'created_at' => $facility->created_at?->format('Y-m-d H:i:s') ?? '',
            'updated_at' => $facility->updated_at?->format('Y-m-d H:i:s') ?? '',
            default => '',
        };
    }

    /**
     * Format service types array to string
     */
    private function formatServiceTypes($serviceTypes): string
    {
        if (!is_array($serviceTypes) || empty($serviceTypes)) {
            return '';
        }
        return implode('、', $serviceTypes);
    }

    /**
     * Get drawing field value
     */
    private function getDrawingFieldValue(Facility $facility, string $field): string
    {
        $drawing = $this->getCachedRelationship($facility, 'drawing');
        
        if (!$drawing) {
            return '';
        }

        return match ($field) {
            'drawing_handover_notes' => $this->getHandoverNotes($drawing),
            'drawing_notes' => $drawing->notes ?? '',
            default => '',
        };
    }

    /**
     * Get handover notes from handover_drawings_notes column
     */
    private function getHandoverNotes($drawing): string
    {
        if (!$drawing) {
            return '';
        }
        
        // handover_drawings_notes カラムから取得
        return $drawing->handover_drawings_notes ?? '';
    }

    /**
     * Get building information field value
     */
    private function getBuildingFieldValue(Facility $facility, string $field): string
    {
        $buildingInfo = $this->getCachedRelationship($facility, 'buildingInfo');
        if (!$buildingInfo) {
            return '';
        }

        return match ($field) {
            'building_ownership_type' => $this->formatOwnershipType($buildingInfo->ownership_type ?? ''),
            'building_area_sqm' => $buildingInfo->area_sqm ? (string)$buildingInfo->area_sqm : '',
            'building_area_tsubo' => $buildingInfo->area_tsubo ? (string)$buildingInfo->area_tsubo : '',
            'building_completion_date' => $buildingInfo->completion_date?->format('Y-m-d') ?? '',
            'building_total_floor_area_sqm' => $buildingInfo->total_floor_area_sqm ? (string)$buildingInfo->total_floor_area_sqm : '',
            'building_total_floor_area_tsubo' => $buildingInfo->total_floor_area_tsubo ? (string)$buildingInfo->total_floor_area_tsubo : '',
            'building_age' => $buildingInfo->completion_date ? (string)$buildingInfo->completion_date->diffInYears(now()) : '',
            'building_construction_cost' => $buildingInfo->construction_cost ? (string)$buildingInfo->construction_cost : '',
            'building_cost_per_tsubo' => $buildingInfo->cost_per_tsubo ? (string)$buildingInfo->cost_per_tsubo : '',
            'building_useful_life' => $buildingInfo->useful_life ? (string)$buildingInfo->useful_life : '',
            'building_construction_cooperation_fee' => $buildingInfo->construction_cooperation_fee ? (string)$buildingInfo->construction_cooperation_fee : '',
            'building_monthly_rent' => $buildingInfo->monthly_rent ? (string)$buildingInfo->monthly_rent : '',
            'building_contract_years' => $buildingInfo->contract_years ? (string)$buildingInfo->contract_years : '',
            'building_contract_start_date' => $buildingInfo->contract_start_date?->format('Y-m-d') ?? '',
            'building_contract_end_date' => $buildingInfo->contract_end_date?->format('Y-m-d') ?? '',
            'building_auto_renewal' => $this->formatAutoRenewal($buildingInfo->auto_renewal ?? ''),
            'building_construction_company_name' => $buildingInfo->construction_company_name ?? '',
            'building_construction_company_phone' => $buildingInfo->construction_company_phone ?? '',
            'building_periodic_inspection_type' => $buildingInfo->periodic_inspection_type ?? '',
            'building_periodic_inspection_date' => $buildingInfo->periodic_inspection_date?->format('Y-m-d') ?? '',
            'building_notes' => $buildingInfo->notes ?? '',
            'building_management_company_name' => $buildingInfo->management_company_name ?? '',
            'building_management_company_phone' => $buildingInfo->management_company_phone ?? '',
            'building_management_company_email' => $buildingInfo->management_company_email ?? '',
            'building_owner_name' => $buildingInfo->owner_name ?? '',
            'building_owner_phone' => $buildingInfo->owner_phone ?? '',
            'building_owner_email' => $buildingInfo->owner_email ?? '',
            default => '',
        };
    }

    /**
     * Get electrical equipment field value
     */
    private function getElectricalFieldValue(Facility $facility, string $field): string
    {
        $electrical = $facility->getElectricalEquipment();
        if (!$electrical) {
            return '';
        }

        $basicInfo = $electrical->basic_info ?? [];
        $pasInfo = $electrical->pas_info ?? [];
        $cubicleInfo = $electrical->cubicle_info ?? [];
        $generatorInfo = $electrical->generator_info ?? [];

        return match ($field) {
            'electrical_contractor' => $basicInfo['electrical_contractor'] ?? '',
            'electrical_safety_management_company' => $basicInfo['safety_management_company'] ?? '',
            'electrical_maintenance_inspection_date' => isset($basicInfo['maintenance_inspection_date']) ? $basicInfo['maintenance_inspection_date'] : '',
            'electrical_pas_availability' => $pasInfo['availability'] ?? '',
            'electrical_pas_update_date' => isset($pasInfo['update_date']) ? $pasInfo['update_date'] : '',
            'electrical_cubicle_availability' => $cubicleInfo['availability'] ?? '',
            'electrical_cubicle_manufacturers' => $this->formatArrayField($cubicleInfo['equipment_list'] ?? [], 'manufacturer'),
            'electrical_cubicle_model_years' => $this->formatArrayField($cubicleInfo['equipment_list'] ?? [], 'model_year'),
            'electrical_generator_availability' => $generatorInfo['availability'] ?? '',
            'electrical_generator_manufacturers' => $this->formatArrayField($generatorInfo['equipment_list'] ?? [], 'manufacturer'),
            'electrical_generator_model_years' => $this->formatArrayField($generatorInfo['equipment_list'] ?? [], 'model_year'),
            'electrical_notes' => $electrical->notes ?? '',
            default => '',
        };
    }

    /**
     * Get water equipment field value
     */
    private function getWaterFieldValue(Facility $facility, string $field): string
    {
        $water = $facility->getWaterEquipment();
        if (!$water) {
            return '';
        }

        $basicInfo = $water->basic_info ?? [];

        return match ($field) {
            'water_contractor' => $basicInfo['water_contractor'] ?? '',
            'water_tank_cleaning_company' => $basicInfo['tank_cleaning_company'] ?? '',
            'water_tank_cleaning_date' => isset($basicInfo['tank_cleaning_date']) ? $basicInfo['tank_cleaning_date'] : '',
            'water_filter_bath_system' => $basicInfo['filter_bath_system'] ?? '',
            'water_filter_availability' => $basicInfo['filter_availability'] ?? '',
            'water_filter_manufacturer' => $basicInfo['filter_manufacturer'] ?? '',
            'water_filter_model_year' => $basicInfo['filter_model_year'] ?? '',
            'water_tank_availability' => $basicInfo['tank_availability'] ?? '',
            'water_tank_manufacturer' => $basicInfo['tank_manufacturer'] ?? '',
            'water_tank_model_year' => $basicInfo['tank_model_year'] ?? '',
            'water_pump_manufacturers' => $this->formatArrayField($basicInfo['pump_equipment_list'] ?? [], 'manufacturer'),
            'water_pump_model_years' => $this->formatArrayField($basicInfo['pump_equipment_list'] ?? [], 'model_year'),
            'water_septic_tank_availability' => $basicInfo['septic_tank_availability'] ?? '',
            'water_septic_tank_manufacturer' => $basicInfo['septic_tank_manufacturer'] ?? '',
            'water_septic_tank_model_year' => $basicInfo['septic_tank_model_year'] ?? '',
            'water_septic_tank_inspection_company' => $basicInfo['septic_tank_inspection_company'] ?? '',
            'water_septic_tank_inspection_date' => isset($basicInfo['septic_tank_inspection_date']) ? $basicInfo['septic_tank_inspection_date'] : '',
            'water_legionella_inspection_dates' => $this->formatLegionellaInspectionDates($basicInfo),
            'water_legionella_first_results' => $basicInfo['legionella_first_result'] ?? '',
            'water_legionella_second_results' => $basicInfo['legionella_second_result'] ?? '',
            'water_notes' => $water->notes ?? '',
            default => '',
        };
    }

    /**
     * Get gas equipment field value
     */
    private function getGasFieldValue(Facility $facility, string $field): string
    {
        $gas = $facility->getGasEquipment();
        if (!$gas) {
            return '';
        }

        $basicInfo = $gas->basic_info ?? [];

        return match ($field) {
            'gas_contractor' => $basicInfo['gas_contractor'] ?? '',
            'gas_safety_management_company' => $basicInfo['safety_management_company'] ?? '',
            'gas_maintenance_inspection_date' => isset($basicInfo['maintenance_inspection_date']) ? $basicInfo['maintenance_inspection_date'] : '',
            'gas_notes' => $gas->notes ?? '',
            default => '',
        };
    }

    /**
     * Get elevator equipment field value
     */
    private function getElevatorFieldValue(Facility $facility, string $field): string
    {
        $elevator = $facility->getElevatorEquipment();
        if (!$elevator) {
            return '';
        }

        $basicInfo = $elevator->basic_info ?? [];

        return match ($field) {
            'elevator_availability' => $basicInfo['availability'] ?? '',
            'elevator_manufacturer' => $basicInfo['manufacturer'] ?? '',
            'elevator_model_year' => $basicInfo['model_year'] ?? '',
            'elevator_maintenance_company' => $basicInfo['maintenance_company'] ?? '',
            'elevator_maintenance_date' => isset($basicInfo['maintenance_date']) ? $basicInfo['maintenance_date'] : '',
            'elevator_notes' => $elevator->notes ?? '',
            default => '',
        };
    }

    /**
     * Get HVAC equipment field value
     */
    private function getHvacFieldValue(Facility $facility, string $field): string
    {
        $hvac = $facility->getHvacLightingEquipment();
        if (!$hvac) {
            return '';
        }

        $basicInfo = $hvac->basic_info ?? [];
        $hvacData = $basicInfo['hvac'] ?? [];
        $lightingData = $basicInfo['lighting'] ?? [];

        return match ($field) {
            // 空調設備
            'hvac_freon_inspection_company' => $hvacData['freon_inspection_company'] ?? '',
            'hvac_freon_inspection_date' => isset($hvacData['freon_inspection_date']) ? $hvacData['freon_inspection_date'] : '',
            'hvac_inspection_equipment' => $hvacData['inspection_equipment'] ?? '',
            'hvac_notes' => $hvacData['notes'] ?? '',
            
            // 照明設備
            'lighting_manufacturer' => $lightingData['manufacturer'] ?? '',
            'lighting_update_date' => isset($lightingData['update_date']) ? $lightingData['update_date'] : '',
            'lighting_warranty_period' => $lightingData['warranty_period'] ?? '',
            'lighting_notes' => $lightingData['notes'] ?? '',
            
            // 後方互換性のため古い項目も残す
            'hvac_lighting_availability' => $basicInfo['availability'] ?? '',
            'hvac_lighting_manufacturer' => $basicInfo['manufacturer'] ?? '',
            'hvac_lighting_model_year' => $basicInfo['model_year'] ?? '',
            'hvac_lighting_maintenance_company' => $basicInfo['maintenance_company'] ?? '',
            'hvac_lighting_maintenance_date' => isset($basicInfo['maintenance_date']) ? $basicInfo['maintenance_date'] : '',
            'hvac_lighting_notes' => $hvac->notes ?? '',
            default => '',
        };
    }

    /**
     * Format array field (e.g., manufacturers, model years)
     */
    private function formatArrayField(array $items, string $key): string
    {
        $values = array_filter(array_map(fn($item) => $item[$key] ?? '', $items));
        return implode(', ', $values);
    }

    /**
     * Format legionella inspection dates
     */
    private function formatLegionellaInspectionDates(array $basicInfo): string
    {
        $dates = [];
        if (!empty($basicInfo['legionella_first_inspection_date'])) {
            $dates[] = $basicInfo['legionella_first_inspection_date'];
        }
        if (!empty($basicInfo['legionella_second_inspection_date'])) {
            $dates[] = $basicInfo['legionella_second_inspection_date'];
        }
        return implode(', ', $dates);
    }

    /**
     * Get maintenance field value
     */
    private function getMaintenanceFieldValue(Facility $facility, string $field): string
    {
        // 外装 - 防水
        if (str_starts_with($field, 'maintenance_exterior_waterproof_')) {
            return $this->getMaintenanceExteriorWaterproofValue($facility, $field);
        }
        
        // 外装 - 塗装
        if (str_starts_with($field, 'maintenance_exterior_painting_')) {
            return $this->getMaintenanceExteriorPaintingValue($facility, $field);
        }
        
        // 内装リニューアル
        if (str_starts_with($field, 'maintenance_interior_renewal_')) {
            return $this->getMaintenanceInteriorRenewalValue($facility, $field);
        }
        
        // 内装・意匠履歴
        if (str_starts_with($field, 'maintenance_interior_history_')) {
            return $this->getMaintenanceInteriorHistoryValue($facility, $field);
        }
        
        // その他 - 改修工事履歴
        if (str_starts_with($field, 'maintenance_other_renovation_')) {
            return $this->getMaintenanceOtherRenovationValue($facility, $field);
        }
        
        return '';
    }

    /**
     * Get maintenance exterior waterproof value
     */
    private function getMaintenanceExteriorWaterproofValue(Facility $facility, string $field): string
    {
        // 外装・防水カテゴリの最新履歴を取得
        $latest = $facility->maintenanceHistories()
            ->where('category', 'exterior')
            ->where('subcategory', 'waterproof')
            ->orderBy('maintenance_date', 'desc')
            ->first();
        
        if (!$latest) {
            return '';
        }
        
        return match ($field) {
            'maintenance_exterior_waterproof_date' => $latest->maintenance_date?->format('Y-m-d') ?? '',
            'maintenance_exterior_waterproof_company' => $latest->contractor ?? '',
            'maintenance_exterior_waterproof_contact_person' => $latest->contact_person ?? '',
            'maintenance_exterior_waterproof_contact' => $latest->phone_number ?? '',
            'maintenance_exterior_waterproof_notes' => $latest->notes ?? '',
            'maintenance_exterior_waterproof_special_notes' => $latest->special_notes ?? '',
            default => '',
        };
    }

    /**
     * Get maintenance exterior painting value
     */
    private function getMaintenanceExteriorPaintingValue(Facility $facility, string $field): string
    {
        // 外装・塗装カテゴリの最新履歴を取得
        $latest = $facility->maintenanceHistories()
            ->where('category', 'exterior')
            ->where('subcategory', 'painting')
            ->orderBy('maintenance_date', 'desc')
            ->first();
        
        if (!$latest) {
            return '';
        }
        
        return match ($field) {
            'maintenance_exterior_painting_date' => $latest->maintenance_date?->format('Y-m-d') ?? '',
            'maintenance_exterior_painting_company' => $latest->contractor ?? '',
            'maintenance_exterior_painting_contact_person' => $latest->contact_person ?? '',
            'maintenance_exterior_painting_contact' => $latest->phone_number ?? '',
            'maintenance_exterior_painting_notes' => $latest->notes ?? '',
            'maintenance_exterior_painting_special_notes' => $latest->special_notes ?? '',
            default => '',
        };
    }

    /**
     * Get maintenance interior renewal value
     */
    private function getMaintenanceInteriorRenewalValue(Facility $facility, string $field): string
    {
        // 内装リニューアルカテゴリの最新履歴を取得
        $latest = $facility->maintenanceHistories()
            ->where('category', 'interior')
            ->where('subcategory', 'renovation')
            ->orderBy('maintenance_date', 'desc')
            ->first();
        
        if (!$latest) {
            return '';
        }
        
        return match ($field) {
            'maintenance_interior_renewal_date' => $latest->maintenance_date?->format('Y-m-d') ?? '',
            'maintenance_interior_renewal_company' => $latest->contractor ?? '',
            'maintenance_interior_renewal_contact_person' => $latest->contact_person ?? '',
            'maintenance_interior_renewal_contact' => $latest->phone_number ?? '',
            'maintenance_interior_renewal_special_notes' => $latest->special_notes ?? '',
            default => '',
        };
    }

    /**
     * Get maintenance interior history value
     */
    private function getMaintenanceInteriorHistoryValue(Facility $facility, string $field): string
    {
        // 内装・意匠履歴カテゴリの最新履歴を取得
        $latest = $facility->maintenanceHistories()
            ->where('category', 'interior')
            ->where('subcategory', 'design')
            ->orderBy('maintenance_date', 'desc')
            ->first();
        
        if (!$latest) {
            return '';
        }
        
        return match ($field) {
            'maintenance_interior_history_no' => $latest->id ? (string)$latest->id : '',
            'maintenance_interior_history_date' => $latest->maintenance_date?->format('Y-m-d') ?? '',
            'maintenance_interior_history_company' => $latest->contractor ?? '',
            'maintenance_interior_history_amount' => $latest->cost ? (string)$latest->cost : '',
            'maintenance_interior_history_content' => $latest->content ?? '',
            'maintenance_interior_history_notes' => $latest->notes ?? '',
            'maintenance_interior_history_special_notes' => $latest->special_notes ?? '',
            default => '',
        };
    }
    
    /**
     * Format maintenance histories as array string
     */
    private function formatMaintenanceHistoriesArray($histories): string
    {
        if ($histories->isEmpty()) {
            return '';
        }
        
        $data = [];
        foreach ($histories as $history) {
            $data[] = sprintf(
                '[NO:%s|日付:%s|会社:%s|金額:%s|内容:%s|担当:%s|連絡先:%s|備考:%s|特記事項:%s]',
                $history->id ?? '',
                $history->maintenance_date?->format('Y-m-d') ?? '',
                $history->contractor ?? '',
                $history->cost ? number_format($history->cost) : '',
                $history->content ?? '',
                $history->contact_person ?? '',
                $history->phone_number ?? '',
                $history->notes ?? '',
                $history->special_notes ?? ''
            );
        }
        
        return implode(' / ', $data);
    }

    /**
     * Get maintenance other renovation value
     */
    private function getMaintenanceOtherRenovationValue(Facility $facility, string $field): string
    {
        // その他改修工事履歴カテゴリの最新履歴を取得
        $latest = $facility->maintenanceHistories()
            ->where('category', 'other')
            ->where('subcategory', 'renovation_work')
            ->orderBy('maintenance_date', 'desc')
            ->first();
        
        if (!$latest) {
            return '';
        }
        
        return match ($field) {
            'maintenance_other_renovation_no' => $latest->id ? (string)$latest->id : '',
            'maintenance_other_renovation_date' => $latest->maintenance_date?->format('Y-m-d') ?? '',
            'maintenance_other_renovation_company' => $latest->contractor ?? '',
            'maintenance_other_renovation_amount' => $latest->cost ? (string)$latest->cost : '',
            'maintenance_other_renovation_content' => $latest->content ?? '',
            'maintenance_other_renovation_notes' => $latest->notes ?? '',
            'maintenance_other_renovation_special_notes' => $latest->special_notes ?? '',
            default => '',
        };
    }

    /**
     * Get contract field value
     */
    private function getContractFieldValue(Facility $facility, string $field): string
    {
        $contract = $this->getCachedRelationship($facility, 'contracts');
        if (!$contract) {
            return '';
        }

        // その他契約書
        if (str_starts_with($field, 'contract_others_')) {
            return $this->getOthersContractFieldValue($contract, $field);
        }

        // 給食契約書
        if (str_starts_with($field, 'contract_meal_')) {
            return $this->getMealContractFieldValue($contract, $field);
        }

        // 駐車場契約書
        if (str_starts_with($field, 'contract_parking_')) {
            return $this->getParkingContractFieldValue($contract, $field);
        }

        return '';
    }

    /**
     * Get others contract field value
     */
    private function getOthersContractFieldValue($contract, string $field): string
    {
        return match ($field) {
            'contract_others_company_name' => $contract->others_company_name ?? '',
            'contract_others_contract_type' => $contract->others_contract_type ?? '',
            'contract_others_contract_content' => $contract->others_contract_content ?? '',
            'contract_others_auto_renewal' => $contract->others_auto_renewal ?? '',
            'contract_others_auto_renewal_details' => $contract->others_auto_renewal_details ?? '',
            'contract_others_contract_start_date' => $contract->others_contract_start_date?->format('Y-m-d') ?? '',
            'contract_others_contract_end_date' => $contract->others_contract_end_date?->format('Y-m-d') ?? '',
            'contract_others_amount' => $contract->others_amount ? (string)$contract->others_amount : '',
            'contract_others_notes' => $contract->others_notes ?? '',
            default => '',
        };
    }

    /**
     * Get meal contract field value
     */
    private function getMealContractFieldValue($contract, string $field): string
    {
        $mealData = $contract->meal_service_data ?? [];

        return match ($field) {
            'contract_meal_service_company_name' => $mealData['company_name'] ?? '',
            'contract_meal_service_contract_type' => $mealData['contract_type'] ?? '',
            'contract_meal_service_contract_content' => $mealData['contract_content'] ?? '',
            'contract_meal_service_auto_renewal' => $mealData['auto_renewal'] ?? '',
            'contract_meal_service_contract_start_date' => isset($mealData['contract_start_date']) ? $mealData['contract_start_date'] : '',
            'contract_meal_service_contract_end_date' => isset($mealData['contract_end_date']) ? $mealData['contract_end_date'] : '',
            'contract_meal_service_amount' => isset($mealData['amount']) ? (string)$mealData['amount'] : '',
            'contract_meal_service_notes' => $mealData['notes'] ?? '',
            default => '',
        };
    }

    /**
     * Get parking contract field value
     */
    private function getParkingContractFieldValue($contract, string $field): string
    {
        $parkingData = $contract->parking_data ?? [];

        return match ($field) {
            'contract_parking_company_name' => $parkingData['company_name'] ?? '',
            'contract_parking_contract_type' => $parkingData['contract_type'] ?? '',
            'contract_parking_contract_content' => $parkingData['contract_content'] ?? '',
            'contract_parking_auto_renewal' => $parkingData['auto_renewal'] ?? '',
            'contract_parking_contract_start_date' => isset($parkingData['contract_start_date']) ? $parkingData['contract_start_date'] : '',
            'contract_parking_contract_end_date' => isset($parkingData['contract_end_date']) ? $parkingData['contract_end_date'] : '',
            'contract_parking_amount' => isset($parkingData['amount']) ? (string)$parkingData['amount'] : '',
            'contract_parking_spaces' => isset($parkingData['spaces']) ? (string)$parkingData['spaces'] : '',
            'contract_parking_notes' => $parkingData['notes'] ?? '',
            default => '',
        };
    }

    /**
     * Get security/disaster field value
     */
    private function getSecurityDisasterFieldValue(Facility $facility, string $field): string
    {
        $securityDisaster = $facility->getSecurityDisasterEquipment();
        if (!$securityDisaster) {
            return '';
        }

        // 防犯カメラ
        if (str_starts_with($field, 'security_camera_')) {
            return $this->getSecurityCameraFieldValue($securityDisaster, $field);
        }

        // 電子錠
        if (str_starts_with($field, 'security_lock_')) {
            return $this->getSecurityLockFieldValue($securityDisaster, $field);
        }

        // 消防
        if (str_starts_with($field, 'fire_')) {
            return $this->getFireFieldValue($securityDisaster, $field);
        }

        // 防災
        if (str_starts_with($field, 'disaster_')) {
            return $this->getDisasterFieldValue($securityDisaster, $field);
        }

        return '';
    }

    /**
     * Get security camera field value
     */
    private function getSecurityCameraFieldValue($securityDisaster, string $field): string
    {
        $securitySystems = $securityDisaster->security_systems ?? [];
        $cameraData = $securitySystems['camera'] ?? [];

        return match ($field) {
            'security_camera_management_company' => $cameraData['management_company'] ?? '',
            'security_camera_model_year' => $cameraData['model_year'] ?? '',
            'security_camera_notes' => $cameraData['notes'] ?? '',
            default => '',
        };
    }

    /**
     * Get security lock field value
     */
    private function getSecurityLockFieldValue($securityDisaster, string $field): string
    {
        $securitySystems = $securityDisaster->security_systems ?? [];
        $lockData = $securitySystems['lock'] ?? [];

        return match ($field) {
            'security_lock_management_company' => $lockData['management_company'] ?? '',
            'security_lock_model_year' => $lockData['model_year'] ?? '',
            'security_lock_notes' => $lockData['notes'] ?? '',
            default => '',
        };
    }

    /**
     * Get fire field value
     */
    private function getFireFieldValue($securityDisaster, string $field): string
    {
        $disasterPrevention = $securityDisaster->disaster_prevention ?? [];
        $fireData = $disasterPrevention['fire'] ?? [];

        return match ($field) {
            'fire_manager' => $fireData['manager'] ?? '',
            'fire_training_date' => isset($fireData['training_date']) ? $fireData['training_date'] : '',
            'fire_inspection_company' => $fireData['inspection_company'] ?? '',
            'fire_inspection_date' => isset($fireData['inspection_date']) ? $fireData['inspection_date'] : '',
            default => '',
        };
    }

    /**
     * Get disaster field value
     */
    private function getDisasterFieldValue($securityDisaster, string $field): string
    {
        $disasterPrevention = $securityDisaster->disaster_prevention ?? [];
        $disasterData = $disasterPrevention['disaster'] ?? [];

        return match ($field) {
            'disaster_practical_training_date' => isset($disasterData['practical_training_date']) ? $disasterData['practical_training_date'] : '',
            'disaster_riding_training_date' => isset($disasterData['riding_training_date']) ? $disasterData['riding_training_date'] : '',
            'disaster_notes' => $disasterData['notes'] ?? '',
            default => '',
        };
    }
}