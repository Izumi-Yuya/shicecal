<?php

namespace App\Services\LandInfo;

use App\Models\LandInfo;
use Carbon\Carbon;

class LandInfoCalculationService
{
    /**
     * Calculate tsubo unit price from purchase amount and tsubo area
     */
    public function calculateTsuboUnitPrice(LandInfo $landInfo): ?array
    {
        if (! $landInfo->purchase_price || ! $landInfo->site_area_tsubo) {
            return ['value' => '計算不可', 'calculated' => false];
        }

        $unitPrice = $landInfo->purchase_price / $landInfo->site_area_tsubo;

        return [
            'value' => number_format($unitPrice).'円/坪',
            'calculated' => true,
        ];
    }

    /**
     * Calculate contract years from start and end dates
     */
    public function calculateContractYears(LandInfo $landInfo): ?array
    {
        if (! $landInfo->contract_start_date || ! $landInfo->contract_end_date) {
            return ['value' => '計算不可', 'calculated' => false];
        }

        $start = Carbon::parse($landInfo->contract_start_date);
        $end = Carbon::parse($landInfo->contract_end_date);
        $years = $start->diffInYears($end);

        return [
            'value' => $years.'年',
            'calculated' => true,
        ];
    }
}
