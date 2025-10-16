<?php

namespace App\Services;

use App\Models\Facility;
use Carbon\Carbon;

class LifelineEquipmentViewService
{
    /**
     * Prepare gas equipment data for view
     */
    public function prepareGasEquipmentData(Facility $facility): array
    {
        $gasEquipment = $facility->getGasEquipment();
        $basicInfo = $gasEquipment?->basic_info ?? [];
        $waterHeaterInfo = $basicInfo['water_heater_info'] ?? [];
        $floorHeatingInfo = $basicInfo['floor_heating_info'] ?? [];

        return [
            'gasEquipment' => $gasEquipment,
            'basicInfo' => $basicInfo,
            'canEdit' => auth()->user()->canEditFacility($facility->id),
            'basicInfoData' => $this->formatBasicInfoData($basicInfo),
            'waterHeaterData' => $this->formatWaterHeaterData($waterHeaterInfo),
            'floorHeatingData' => $this->formatFloorHeatingData($floorHeatingInfo),
            'notesData' => $this->formatNotesData($gasEquipment),
        ];
    }

    /**
     * Format basic info data for table display
     */
    private function formatBasicInfoData(array $basicInfo): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'ガス契約会社',
                        'value' => $basicInfo['gas_supplier'] ?? null,
                        'type' => 'text',
                        'width' => '50%'
                    ],
                    [
                        'label' => 'ガスの種類',
                        'value' => $basicInfo['gas_type'] ?? null,
                        'type' => 'text',
                        'width' => '50%'
                    ],
                ],
            ],
        ];
    }

    /**
     * Format water heater data
     */
    private function formatWaterHeaterData(array $waterHeaterInfo): array
    {
        $waterHeaters = is_array($waterHeaterInfo['water_heaters'] ?? null)
            ? $waterHeaterInfo['water_heaters']
            : [];

        return [
            'availability' => $waterHeaterInfo['availability'] ?? null,
            'heaters' => $waterHeaters,
        ];
    }

    /**
     * Format floor heating data for table display
     */
    private function formatFloorHeatingData(array $floorHeatingInfo): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'メーカー',
                        'value' => $floorHeatingInfo['manufacturer'] ?? null,
                        'type' => 'text',
                        'width' => '33.33%'
                    ],
                    [
                        'label' => '年式',
                        'value' => !empty($floorHeatingInfo['model_year'])
                            ? $floorHeatingInfo['model_year'] . '年式'
                            : null,
                        'type' => 'text',
                        'width' => '33.33%'
                    ],
                    [
                        'label' => '更新年月日',
                        'value' => $floorHeatingInfo['update_date'] ?? null,
                        'type' => 'date',
                        'width' => '33.33%'
                    ],
                ],
            ],
        ];
    }

    /**
     * Format notes data for table display
     */
    private function formatNotesData($gasEquipment): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '備考',
                        'value' => $gasEquipment?->notes ?? null,
                        'type' => 'text',
                        'width' => '100%'
                    ],
                ],
            ],
        ];
    }
}