<?php

namespace App\Services\Export;

use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * CSV Export Service
 * Handles CSV generation and field management
 */
class CsvExportService
{
    protected FieldMappingService $fieldMappingService;
    protected DataFormatterService $dataFormatterService;

    public function __construct(
        FieldMappingService $fieldMappingService,
        DataFormatterService $dataFormatterService
    ) {
        $this->fieldMappingService = $fieldMappingService;
        $this->dataFormatterService = $dataFormatterService;
    }

    /**
     * Get available fields for CSV export
     */
    public function getAvailableFields(): array
    {
        return $this->fieldMappingService->getAllFields();
    }

    /**
     * Get total field count
     */
    public function getTotalFieldCount(): int
    {
        return count($this->getAvailableFields());
    }

    /**
     * Generate CSV content
     */
    public function generateCsv(array $facilityIds, array $exportFields): string
    {
        $relationships = $this->determineRequiredRelationships($exportFields);
        
        $facilities = Facility::whereIn('id', $facilityIds)
            ->with($relationships)
            ->select($this->determineRequiredColumns($exportFields))
            ->get();

        return $this->buildCsvContent($facilities, $exportFields);
    }

    /**
     * Stream CSV content for large datasets
     */
    public function streamCsv($output, array $facilityIds, array $exportFields): void
    {
        $availableFields = $this->getAvailableFields();
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));
        $relationships = $this->determineRequiredRelationships($exportFields);

        // Write header
        fputcsv($output, array_values($selectedFields));

        // Process facilities in smaller chunks for better memory management
        $chunkSize = count($exportFields) > 50 ? 50 : 100; // Smaller chunks for more fields
        
        Facility::whereIn('id', $facilityIds)
            ->with($relationships)
            ->chunk($chunkSize, function ($facilities) use ($output, $exportFields) {
                foreach ($facilities as $facility) {
                    $row = [];
                    foreach ($exportFields as $field) {
                        $row[] = $this->dataFormatterService->getFieldValue($facility, $field);
                    }
                    fputcsv($output, $row);
                }
                
                // Force garbage collection for large exports
                if (memory_get_usage() > 128 * 1024 * 1024) { // 128MB threshold
                    gc_collect_cycles();
                }
            });
    }

    /**
     * Determine required relationships based on selected fields
     */
    private function determineRequiredRelationships(array $exportFields): array
    {
        $relationships = [];
        $fieldPrefixes = [
            'land_' => 'landInfo',
            'building_' => 'buildingInfo', 
            'maintenance_' => 'maintenanceHistories',
            'drawing_' => 'drawing',
            'electrical_' => 'lifelineEquipments',
            'water_' => 'lifelineEquipments',
            'gas_' => 'lifelineEquipments',
            'elevator_' => 'lifelineEquipments',
            'hvac_' => 'lifelineEquipments',
            'contract_' => 'contracts',
        ];
        
        foreach ($exportFields as $field) {
            foreach ($fieldPrefixes as $prefix => $relationship) {
                if (str_starts_with($field, $prefix)) {
                    $relationships[] = $relationship;
                    break; // Exit inner loop once match is found
                }
            }
        }
        
        return array_unique($relationships);
    }

    /**
     * Determine required columns for query optimization
     */
    private function determineRequiredColumns(array $exportFields): array
    {
        $baseColumns = ['id', 'facility_name', 'company_name', 'created_at', 'updated_at'];
        
        // Add specific columns based on selected fields
        foreach ($exportFields as $field) {
            if (in_array($field, ['company_name', 'facility_name', 'office_code', 'address'])) {
                $baseColumns[] = $field;
            }
        }
        
        return array_unique($baseColumns);
    }

    /**
     * Build CSV content from facilities data
     */
    private function buildCsvContent(Collection $facilities, array $exportFields): string
    {
        $availableFields = $this->getAvailableFields();
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));

        $output = fopen('php://temp', 'r+');

        // Add BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write header
        fputcsv($output, array_values($selectedFields));

        // Write data rows
        foreach ($facilities as $facility) {
            $row = [];
            foreach ($exportFields as $field) {
                $row[] = $this->dataFormatterService->getFieldValue($facility, $field);
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}