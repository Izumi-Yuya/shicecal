<?php

namespace App\Services;

use App\Models\Facility;

class LifelineEquipmentPresenter
{
    public function formatWaterEquipmentData(Facility $facility): array
    {
        $waterEquipment = $facility->getWaterEquipment();
        $basicInfo = $waterEquipment?->basic_info ?? [];
        
        return [
            'basicInfo' => $this->formatBasicInfo($basicInfo),
            'filterInfo' => $this->formatFilterInfo($basicInfo['filter_info'] ?? []),
            'tankInfo' => $this->formatTankInfo($basicInfo['tank_info'] ?? []),
            'pumpInfo' => $this->formatPumpInfo($basicInfo['pump_info'] ?? []),
            'septicTankInfo' => $this->formatSepticTankInfo($basicInfo['septic_tank_info'] ?? []),
            'legionellaInfo' => $this->formatLegionellaInfo($basicInfo['legionella_info'] ?? []),
            'notes' => $waterEquipment?->notes ?? null,
        ];
    }

    private function formatBasicInfo(array $basicInfo): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '水道契約会社',
                        'value' => $basicInfo['water_contractor'] ?? null,
                        'type' => 'text',
                        'width' => '100%'
                    ],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '受水槽清掃業者',
                        'value' => $basicInfo['tank_cleaning_company'] ?? null,
                        'type' => 'text',
                        'width' => '33.33%'
                    ],
                    [
                        'label' => '受水槽清掃実施日',
                        'value' => $basicInfo['tank_cleaning_date'] ?? null,
                        'type' => 'date',
                        'width' => '33.33%'
                    ],
                    [
                        'label' => '受水槽清掃報告書',
                        'value' => $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'] ?? null,
                        'type' => 'file_display',
                        'options' => [
                            'route' => 'facilities.lifeline-equipment.download-file',
                            'params' => ['facility', 'water', 'tank_cleaning_report'],
                            'display_name' => 'ダウンロード'
                        ],
                        'width' => '33.33%'
                    ],
                ]
            ],
        ];
    }

    private function formatFilterInfo(array $filterInfo): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '浴槽循環方式',
                        'value' => $filterInfo['bath_system'] ?? null,
                        'type' => 'badge',
                        'options' => ['badge_class' => 'bg-info'],
                        'width' => '100%'
                    ],
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '設置の有無',
                        'value' => $filterInfo['availability'] ?? null,
                        'type' => 'badge',
                        'options' => ['badge_class' => 'availability'],
                        'width' => '33.33%'
                    ],
                    [
                        'label' => 'メーカー',
                        'value' => $filterInfo['manufacturer'] ?? null,
                        'type' => 'text',
                        'width' => '33.33%'
                    ],
                    [
                        'label' => '年式',
                        'value' => !empty($filterInfo['model_year']) ? $filterInfo['model_year'] . '年式' : null,
                        'type' => 'text',
                        'width' => '33.33%'
                    ],
                ]
            ],
        ];
    }

    // Add other formatting methods...
    private function formatTankInfo(array $tankInfo): array
    {
        // TODO: Implementation for tank data formatting
        return [];
    }

    private function formatPumpInfo(array $pumpInfo): array
    {
        // TODO: Implementation for pump data formatting
        return [];
    }

    private function formatSepticTankInfo(array $septicTankInfo): array
    {
        // TODO: Implementation for septic tank data formatting
        return [];
    }

    private function formatLegionellaInfo(array $legionellaInfo): array
    {
        // TODO: Implementation for legionella data formatting
        return [];
    }
}