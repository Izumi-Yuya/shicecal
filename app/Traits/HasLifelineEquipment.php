<?php

namespace App\Traits;

use App\Models\LifelineEquipment;

trait HasLifelineEquipment
{
    /**
     * Get lifeline equipment by category with consistent data structure
     */
    public function getLifelineEquipmentData(string $category): array
    {
        $equipment = $this->getLifelineEquipmentByCategory($category);
        
        if (!$equipment) {
            return [
                'equipment' => null,
                'basic_info' => [],
                'can_edit' => auth()->user()?->canEditFacility($this->id) ?? false,
            ];
        }

        $equipmentData = match($category) {
            'electrical' => $equipment->electricalEquipment,
            'gas' => $equipment->gasEquipment,
            'water' => $equipment->waterEquipment,
            'elevator' => $equipment->elevatorEquipment,
            'hvac_lighting' => $equipment->hvacLightingEquipment,
            default => null,
        };

        return [
            'equipment' => $equipmentData,
            'basic_info' => $equipmentData?->basic_info ?? [],
            'can_edit' => auth()->user()?->canEditFacility($this->id) ?? false,
        ];
    }

    /**
     * Get equipment-specific data with fallback
     */
    public function getEquipmentSpecificData(string $category, string $dataKey): array
    {
        $data = $this->getLifelineEquipmentData($category);
        return $data['basic_info'][$dataKey] ?? [];
    }
}