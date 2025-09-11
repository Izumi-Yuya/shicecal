<?php

namespace App\Services\ServiceTable;

use Illuminate\Support\Collection;

/**
 * Helper service for service table view rendering
 * Separates presentation logic from business logic
 */
class ServiceTableViewHelper
{
    /**
     * Prepare service data for table rendering
     */
    public function prepareServiceTableData(Collection $services, int $maxServices = 10): array
    {
        $serviceCount = $services->count();
        $displayCount = max(1, $serviceCount);

        return [
            'services' => $services,
            'serviceCount' => $serviceCount,
            'displayCount' => $displayCount,
            'maxServices' => $maxServices,
            'emptyRowsCount' => max(0, $maxServices - $serviceCount),
            'hasServices' => $serviceCount > 0,
        ];
    }

    /**
     * Format service date for display
     */
    public function formatServiceDate($date): string
    {
        return $date ? $date->format('Y年n月j日') : '';
    }

    /**
     * Get CSS classes for service row
     */
    public function getServiceRowClasses(int $index, bool $isEmpty = false): string
    {
        $classes = [];

        if ($isEmpty) {
            $classes[] = 'template-row';
        }

        if ($index === 0) {
            $classes[] = 'first-service-row';
        }

        return implode(' ', $classes);
    }
}
