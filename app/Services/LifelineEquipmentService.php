<?php

namespace App\Services;

use App\Exceptions\LifelineEquipmentServiceException;
use App\Models\ElectricalEquipment;
use App\Models\ElevatorEquipment;
use App\Models\Facility;
use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use App\Services\Traits\HandlesServiceErrors;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LifelineEquipmentService
{
    use HandlesServiceErrors;

    protected LifelineEquipmentValidationService $validationService;

    protected ActivityLogService $activityLogService;

    protected FileHandlingService $fileHandlingService;

    public function __construct(
        LifelineEquipmentValidationService $validationService,
        ActivityLogService $activityLogService,
        FileHandlingService $fileHandlingService
    ) {
        $this->validationService = $validationService;
        $this->activityLogService = $activityLogService;
        $this->fileHandlingService = $fileHandlingService;
    }

    /**
     * Retrieves the lifeline equipment data for a specific facility and category.
     */
    public function getEquipmentData(Facility $facility, string $category): array
    {
        try {
            // Validate category
            if (! $this->isValidCategory($category)) {
                return [
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ];
            }

            // Get or create lifeline equipment record
            $lifelineEquipment = $this->getOrCreateLifelineEquipment($facility, $category);

            // Get category-specific data
            $data = $this->getCategorySpecificData($lifelineEquipment, $category);

            return [
                'success' => true,
                'data' => $data,
                'lifeline_equipment' => [
                    'id' => $lifelineEquipment->id,
                    'status' => $lifelineEquipment->status,
                    'category' => $lifelineEquipment->category,
                    'category_display_name' => $lifelineEquipment->getCategoryDisplayName(),
                    'status_display_name' => $lifelineEquipment->getStatusDisplayName(),
                    'updated_at' => $lifelineEquipment->updated_at,
                    'created_at' => $lifelineEquipment->created_at,
                ],
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get lifeline equipment data', [
                'facility_id' => $facility->id,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ライフライン設備データの取得に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Updates lifeline equipment data for the specified facility and category.
     */
    public function updateEquipmentData(
        Facility $facility,
        string $category,
        array $data,
        int $userId
    ): array {
        try {
            Log::info('LifelineEquipmentService: Starting equipment data update', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => $userId,
                'data_keys' => array_keys($data),
            ]);

            // Validate category
            if (! $this->isValidCategory($category)) {
                Log::warning('LifelineEquipmentService: Invalid category', ['category' => $category]);

                return [
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ];
            }

            // Validate data using validation service
            Log::info('LifelineEquipmentService: Starting data validation');
            $validationResult = $this->validationService->validateCategoryData($category, $data);
            if (! $validationResult['success']) {
                Log::warning('LifelineEquipmentService: Validation failed', $validationResult);

                return $validationResult;
            }

            $validatedData = $validationResult['data'];

            // Keep reference to all original data for file processing
            $allData = $data;

            // Add file data to the validated data for processing
            if (isset($data['inspection_report_file']) && $data['inspection_report_file']) {
                $validatedData['inspection_report_file'] = $data['inspection_report_file'];
            }

            Log::info('LifelineEquipmentService: Data validation completed successfully');

            DB::beginTransaction();

            try {
                Log::info('LifelineEquipmentService: Starting database transaction');

                // Get or create lifeline equipment record
                $lifelineEquipment = $this->getOrCreateLifelineEquipment($facility, $category, $userId);
                Log::info('LifelineEquipmentService: Got lifeline equipment', ['id' => $lifelineEquipment->id]);

                // Update lifeline equipment status and metadata
                $this->updateLifelineEquipmentMetadata($lifelineEquipment, $userId);
                Log::info('LifelineEquipmentService: Updated metadata');

                // Update the category-specific data
                $this->updateCategorySpecificData($lifelineEquipment, $category, $validatedData, $allData);
                Log::info('LifelineEquipmentService: Updated category-specific data');

                DB::commit();
                Log::info('LifelineEquipmentService: Transaction committed successfully');

                // Log the activity
                $this->logEquipmentUpdate($facility, $category);

                return [
                    'success' => true,
                    'message' => 'ライフライン設備情報を更新しました。',
                    'data' => [
                        'lifeline_equipment_id' => $lifelineEquipment->id,
                        'status' => $lifelineEquipment->status,
                        'updated_at' => $lifelineEquipment->updated_at,
                    ],
                ];
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->logError('Failed to update lifeline equipment data', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return specific error messages for known exceptions
            if (strpos($e->getMessage(), '開発中です') !== false) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            return [
                'success' => false,
                'message' => 'ライフライン設備データの更新に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get electrical equipment data with a formatted structure.
     */
    public function getElectricalEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $electricalEquipment = $lifelineEquipment->electricalEquipment;

        if (! $electricalEquipment) {
            return $this->getDefaultElectricalEquipmentStructure();
        }

        return [
            'basic_info' => $this->formatBasicInfo($electricalEquipment->basic_info ?? []),
            'pas_info' => $this->formatPasInfo($electricalEquipment->pas_info ?? []),
            'cubicle_info' => $this->formatCubicleInfo($electricalEquipment->cubicle_info ?? []),
            'generator_info' => $this->formatGeneratorInfo($electricalEquipment->generator_info ?? []),
            'notes' => $electricalEquipment->notes ?? '',
        ];
    }

    /**
     * Update electrical equipment data.
     */
    public function updateElectricalEquipmentData(
        LifelineEquipment $lifelineEquipment,
        array $validatedData,
        array $allData = []
    ): void {
        $electricalEquipment = $lifelineEquipment->electricalEquipment;

        if (! $electricalEquipment) {
            $electricalEquipment = new ElectricalEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        // This allows for partial updates without overwriting existing data
        if (array_key_exists('basic_info', $validatedData)) {
            // Pass the entire validated data and existing data to handle file uploads
            $electricalEquipment->basic_info = $this->processBasicInfo(
                $validatedData['basic_info'],
                $allData,
                $electricalEquipment->basic_info ?? []
            );
        }

        if (array_key_exists('pas_info', $validatedData)) {
            $electricalEquipment->pas_info = $this->processPasInfo($validatedData['pas_info']);
        }

        if (array_key_exists('cubicle_info', $validatedData)) {
            $electricalEquipment->cubicle_info = $this->processCubicleInfo($validatedData['cubicle_info']);
        }

        if (array_key_exists('generator_info', $validatedData)) {
            $electricalEquipment->generator_info = $this->processGeneratorInfo($validatedData['generator_info']);
        }

        if (array_key_exists('notes', $validatedData)) {
            $electricalEquipment->notes = $validatedData['notes'];
        }

        $electricalEquipment->save();
    }

    /**
     * Get gas equipment data with formatted structure.
     */
    public function getGasEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $gasEquipment = $lifelineEquipment->gasEquipment;

        if (! $gasEquipment) {
            return $this->getDefaultGasEquipmentStructure();
        }

        return [
            'basic_info' => $gasEquipment->basic_info ?? [],
            'notes' => $gasEquipment->notes ?? '',
        ];
    }

    /**
     * Update gas equipment data.
     */
    public function updateGasEquipmentData(
        LifelineEquipment $lifelineEquipment,
        array $validatedData
    ): void {
        $gasEquipment = $lifelineEquipment->gasEquipment;

        if (! $gasEquipment) {
            $gasEquipment = new \App\Models\GasEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $gasEquipment->basic_info = $this->processGasBasicInfo($validatedData['basic_info']);
        }

        if (array_key_exists('notes', $validatedData)) {
            $gasEquipment->notes = $validatedData['notes'];
        }

        $gasEquipment->save();
    }

    /**
     * Process gas equipment basic info data before saving.
     * Handles data sanitization, validation, and structure normalization.
     */
    private function processGasBasicInfo(array $basicInfo): array
    {
        $processedData = [
            'gas_supplier' => isset($basicInfo['gas_supplier'])
                ? ($basicInfo['gas_supplier'] === null ? null : trim($basicInfo['gas_supplier']))
                : null,
            'gas_type' => $basicInfo['gas_type'] ?? null,
        ];

        // Process water heater info (multiple water heaters support)
        if (isset($basicInfo['water_heater_info']) && is_array($basicInfo['water_heater_info'])) {
            $waterHeaterInfo = $basicInfo['water_heater_info'];

            $processedData['water_heater_info'] = [
                'availability' => $waterHeaterInfo['availability'] ?? null,
            ];

            // Process water heaters list if availability is '有'
            if (($waterHeaterInfo['availability'] ?? '') === '有' && isset($waterHeaterInfo['water_heaters']) && is_array($waterHeaterInfo['water_heaters'])) {
                $waterHeaterList = [];

                foreach ($waterHeaterInfo['water_heaters'] as $heater) {
                    if (is_array($heater) && ! empty(array_filter($heater))) {
                        $waterHeaterList[] = [
                            'manufacturer' => isset($heater['manufacturer'])
                                ? ($heater['manufacturer'] === null ? null : trim($heater['manufacturer']))
                                : null,
                            'model_year' => $heater['model_year'] ?? null,
                            'update_date' => $heater['update_date'] ?? null,
                        ];
                    }
                }

                $processedData['water_heater_info']['water_heaters'] = $waterHeaterList;
            }
        }

        // Process floor heating info
        if (isset($basicInfo['floor_heating_info']) && is_array($basicInfo['floor_heating_info'])) {
            $floorHeatingInfo = $basicInfo['floor_heating_info'];

            $processedData['floor_heating_info'] = [
                'manufacturer' => isset($floorHeatingInfo['manufacturer'])
                    ? ($floorHeatingInfo['manufacturer'] === null ? null : trim($floorHeatingInfo['manufacturer']))
                    : null,
                'model_year' => $floorHeatingInfo['model_year'] ?? null,
                'update_date' => $floorHeatingInfo['update_date'] ?? null,
            ];
        }

        return $processedData;
    }

    /**
     * Get default gas equipment structure.
     */
    private function getDefaultGasEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'gas_supplier' => '',
                'gas_type' => '',
                'water_heater_info' => [
                    'availability' => '',
                    'water_heaters' => [],
                ],
                'floor_heating_info' => [
                    'manufacturer' => '',
                    'model_year' => '',
                    'update_date' => '',
                ],
            ],
            'notes' => '',
        ];
    }

    /**
     * Get water equipment data with formatted structure.
     */
    public function getWaterEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $waterEquipment = $lifelineEquipment->waterEquipment;

        if (! $waterEquipment) {
            return $this->getDefaultWaterEquipmentStructure();
        }

        return [
            'basic_info' => $waterEquipment->basic_info ?? [],
            'notes' => $waterEquipment->notes ?? '',
        ];
    }

    /**
     * Update water equipment data.
     */
    public function updateWaterEquipmentData(
        LifelineEquipment $lifelineEquipment,
        array $validatedData,
        array $allData = []
    ): void {
        $waterEquipment = $lifelineEquipment->waterEquipment;
        $existingData = [];

        if (! $waterEquipment) {
            $waterEquipment = new \App\Models\WaterEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        } else {
            $existingData = $waterEquipment->basic_info ?? [];
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $waterEquipment->basic_info = $this->processWaterBasicInfo($validatedData['basic_info'], $allData, $existingData);
        }

        if (array_key_exists('notes', $validatedData)) {
            $waterEquipment->notes = $validatedData['notes'];
        }

        $waterEquipment->save();
    }

    /**
     * Process water equipment basic info data before saving.
     * Handles data sanitization, validation, and structure normalization.
     */
    private function processWaterBasicInfo(array $basicInfo, array $allData = [], array $existingData = []): array
    {

        $processedData = [
            'water_contractor' => isset($basicInfo['water_contractor'])
                ? ($basicInfo['water_contractor'] === null ? null : trim($basicInfo['water_contractor']))
                : null,
            'tank_cleaning_company' => isset($basicInfo['tank_cleaning_company'])
                ? ($basicInfo['tank_cleaning_company'] === null ? null : trim($basicInfo['tank_cleaning_company']))
                : null,
            'tank_cleaning_date' => $basicInfo['tank_cleaning_date'] ?? null,
        ];

        // Handle tank cleaning report PDF upload
        if (isset($allData['tank_cleaning_report_file']) && $allData['tank_cleaning_report_file'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete existing file if present
            if (isset($existingData['tank_cleaning']['tank_cleaning_report_pdf_path'])) {
                $this->fileHandlingService->deleteFile($existingData['tank_cleaning']['tank_cleaning_report_pdf_path']);
            }

            $uploadResult = $this->handleFileUpload($allData['tank_cleaning_report_file'], 'water/tank-cleaning-reports');
            if ($uploadResult) {
                $processedData['tank_cleaning'] = [
                    'tank_cleaning_report_pdf' => $uploadResult['filename'],
                    'tank_cleaning_report_pdf_path' => $uploadResult['path'],
                ];
            }
        } elseif (isset($existingData['tank_cleaning'])) {
            // Preserve existing file information
            $processedData['tank_cleaning'] = $existingData['tank_cleaning'];
        }

        // Handle file deletion
        if (isset($allData['remove_tank_cleaning_report']) && $allData['remove_tank_cleaning_report'] === '1') {
            if (isset($existingData['tank_cleaning']['tank_cleaning_report_pdf_path'])) {
                $this->fileHandlingService->deleteFile($existingData['tank_cleaning']['tank_cleaning_report_pdf_path']);
            }
            $processedData['tank_cleaning'] = [
                'tank_cleaning_report_pdf' => null,
                'tank_cleaning_report_pdf_path' => null,
            ];
        }

        // Process filter info
        if (isset($basicInfo['filter_info']) && is_array($basicInfo['filter_info'])) {
            $processedData['filter_info'] = [
                'bath_system' => isset($basicInfo['filter_info']['bath_system'])
                    ? ($basicInfo['filter_info']['bath_system'] === null ? null : trim($basicInfo['filter_info']['bath_system']))
                    : null,
                'availability' => $basicInfo['filter_info']['availability'] ?? null,
                'manufacturer' => isset($basicInfo['filter_info']['manufacturer'])
                    ? ($basicInfo['filter_info']['manufacturer'] === null ? null : trim($basicInfo['filter_info']['manufacturer']))
                    : null,
                'model_year' => $basicInfo['filter_info']['model_year'] ?? null,
            ];
        }

        // Process tank info
        if (isset($basicInfo['tank_info']) && is_array($basicInfo['tank_info'])) {
            $processedData['tank_info'] = [
                'availability' => $basicInfo['tank_info']['availability'] ?? null,
                'manufacturer' => isset($basicInfo['tank_info']['manufacturer'])
                    ? ($basicInfo['tank_info']['manufacturer'] === null ? null : trim($basicInfo['tank_info']['manufacturer']))
                    : null,
                'model_year' => $basicInfo['tank_info']['model_year'] ?? null,
            ];
        }

        // Process pump info (multiple pumps support)
        if (isset($basicInfo['pump_info']) && is_array($basicInfo['pump_info'])) {
            $pumpList = [];

            if (isset($basicInfo['pump_info']['pumps']) && is_array($basicInfo['pump_info']['pumps'])) {
                foreach ($basicInfo['pump_info']['pumps'] as $pump) {
                    if (is_array($pump) && ! empty(array_filter($pump))) {
                        $pumpList[] = [
                            'manufacturer' => isset($pump['manufacturer'])
                                ? ($pump['manufacturer'] === null ? null : trim($pump['manufacturer']))
                                : null,
                            'model_year' => $pump['model_year'] ?? null,
                            'update_date' => $pump['update_date'] ?? null,
                        ];
                    }
                }
            }

            $processedData['pump_info'] = [
                'pumps' => $pumpList,
            ];
        }

        // Process septic tank info
        if (isset($basicInfo['septic_tank_info']) && is_array($basicInfo['septic_tank_info'])) {
            $septicTankData = [
                'availability' => $basicInfo['septic_tank_info']['availability'] ?? null,
                'manufacturer' => isset($basicInfo['septic_tank_info']['manufacturer'])
                    ? ($basicInfo['septic_tank_info']['manufacturer'] === null ? null : trim($basicInfo['septic_tank_info']['manufacturer']))
                    : null,
                'model_year' => $basicInfo['septic_tank_info']['model_year'] ?? null,
                'inspection_company' => isset($basicInfo['septic_tank_info']['inspection_company'])
                    ? ($basicInfo['septic_tank_info']['inspection_company'] === null ? null : trim($basicInfo['septic_tank_info']['inspection_company']))
                    : null,
                'inspection_date' => $basicInfo['septic_tank_info']['inspection_date'] ?? null,
            ];

            // Handle septic tank inspection report PDF upload
            if (isset($allData['septic_tank_inspection_report_file']) && $allData['septic_tank_inspection_report_file'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete existing file if present
                if (isset($existingData['septic_tank_info']['inspection']['inspection_report_pdf_path'])) {
                    $this->fileHandlingService->deleteFile($existingData['septic_tank_info']['inspection']['inspection_report_pdf_path']);
                }

                $uploadResult = $this->handleFileUpload($allData['septic_tank_inspection_report_file'], 'water/septic-tank-reports');
                if ($uploadResult) {
                    $septicTankData['inspection'] = [
                        'inspection_report_pdf' => $uploadResult['filename'],
                        'inspection_report_pdf_path' => $uploadResult['path'],
                    ];
                }
            } elseif (isset($existingData['septic_tank_info']['inspection'])) {
                // Preserve existing file information
                $septicTankData['inspection'] = $existingData['septic_tank_info']['inspection'];
            }

            // Handle septic tank file deletion
            if (isset($allData['remove_septic_tank_inspection_report']) && $allData['remove_septic_tank_inspection_report'] === '1') {
                if (isset($existingData['septic_tank_info']['inspection']['inspection_report_pdf_path'])) {
                    $this->fileHandlingService->deleteFile($existingData['septic_tank_info']['inspection']['inspection_report_pdf_path']);
                }
                $septicTankData['inspection'] = [
                    'inspection_report_pdf' => null,
                    'inspection_report_pdf_path' => null,
                ];
            }

            $processedData['septic_tank_info'] = $septicTankData;
        }

        // Process legionella info (multiple inspections support)
        if (isset($basicInfo['legionella_info']) && is_array($basicInfo['legionella_info'])) {
            $legionellaList = [];

            if (isset($basicInfo['legionella_info']['inspections']) && is_array($basicInfo['legionella_info']['inspections'])) {
                foreach ($basicInfo['legionella_info']['inspections'] as $index => $inspection) {
                    if (is_array($inspection) && ! empty(array_filter($inspection))) {
                        $inspectionData = [
                            'inspection_date' => $inspection['inspection_date'] ?? null,
                            'first_result' => $inspection['first_result'] ?? null,
                            'first_value' => isset($inspection['first_value'])
                                ? ($inspection['first_value'] === null ? null : trim($inspection['first_value']))
                                : null,
                            'second_result' => $inspection['second_result'] ?? null,
                            'second_value' => isset($inspection['second_value'])
                                ? ($inspection['second_value'] === null ? null : trim($inspection['second_value']))
                                : null,
                        ];

                        // Handle legionella report PDF upload for this inspection
                        $fileFieldName = "legionella_report_file_{$index}";
                        if (isset($allData[$fileFieldName]) && $allData[$fileFieldName] instanceof \Illuminate\Http\UploadedFile) {
                            // Delete existing file if present
                            if (isset($existingData['legionella_info']['inspections'][$index]['report']['report_pdf_path'])) {
                                $this->fileHandlingService->deleteFile($existingData['legionella_info']['inspections'][$index]['report']['report_pdf_path']);
                            }

                            $uploadResult = $this->handleFileUpload($allData[$fileFieldName], 'water/legionella-reports');
                            if ($uploadResult) {
                                $inspectionData['report'] = [
                                    'report_pdf' => $uploadResult['filename'],
                                    'report_pdf_path' => $uploadResult['path'],
                                ];
                            }
                        } elseif (isset($existingData['legionella_info']['inspections'][$index]['report'])) {
                            // Preserve existing file information
                            $inspectionData['report'] = $existingData['legionella_info']['inspections'][$index]['report'];
                        }

                        // Handle legionella file deletion
                        $removeFieldName = "remove_legionella_report_{$index}";
                        if (isset($allData[$removeFieldName]) && $allData[$removeFieldName] === '1') {
                            if (isset($existingData['legionella_info']['inspections'][$index]['report']['report_pdf_path'])) {
                                $this->fileHandlingService->deleteFile($existingData['legionella_info']['inspections'][$index]['report']['report_pdf_path']);
                            }
                            $inspectionData['report'] = [
                                'report_pdf' => null,
                                'report_pdf_path' => null,
                            ];
                        }

                        $legionellaList[] = $inspectionData;
                    }
                }
            }

            $processedData['legionella_info'] = [
                'inspections' => $legionellaList,
            ];
        }

        return $processedData;
    }

    /**
     * Get default water equipment structure.
     */
    private function getDefaultWaterEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'water_contractor' => '',
                'tank_cleaning_company' => '',
                'tank_cleaning_date' => '',
                'tank_cleaning' => [
                    'tank_cleaning_report_pdf' => null,
                    'tank_cleaning_report_pdf_path' => null,
                ],
                'filter_info' => [
                    'bath_system' => '',
                    'availability' => '',
                    'manufacturer' => '',
                    'model_year' => '',
                ],
                'tank_info' => [
                    'availability' => '',
                    'manufacturer' => '',
                    'model_year' => '',
                ],
                'pump_info' => [
                    'pumps' => [],
                ],
                'septic_tank_info' => [
                    'availability' => '',
                    'manufacturer' => '',
                    'model_year' => '',
                    'inspection_company' => '',
                    'inspection_date' => '',
                    'inspection' => [
                        'inspection_report_pdf' => null,
                        'inspection_report_pdf_path' => null,
                    ],
                ],
                'legionella_info' => [
                    'inspections' => [],
                ],
            ],
            'notes' => '',
        ];
    }

    /**
     * Get HVAC/Lighting equipment data with formatted structure.
     */
    public function getHvacLightingEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $hvacLightingEquipment = $lifelineEquipment->hvacLightingEquipment;

        if (! $hvacLightingEquipment) {
            return $this->getDefaultHvacLightingEquipmentStructure();
        }

        return [
            'basic_info' => $hvacLightingEquipment->basic_info ?? [],
            'notes' => $hvacLightingEquipment->notes ?? '',
        ];
    }

    /**
     * Update HVAC/Lighting equipment data.
     */
    public function updateHvacLightingEquipmentData(
        LifelineEquipment $lifelineEquipment,
        array $validatedData,
        array $allData = []
    ): void {
        Log::info('LifelineEquipmentService: Starting HVAC/Lighting equipment update', [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'validated_data_keys' => array_keys($validatedData),
        ]);

        $hvacLightingEquipment = $lifelineEquipment->hvacLightingEquipment;

        if (! $hvacLightingEquipment) {
            Log::info('LifelineEquipmentService: Creating new HVAC/Lighting equipment');
            $hvacLightingEquipment = new HvacLightingEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        } else {
            Log::info('LifelineEquipmentService: Updating existing HVAC/Lighting equipment', ['id' => $hvacLightingEquipment->id]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            Log::info('LifelineEquipmentService: Processing basic_info');
            // Pass the entire validated data and existing data to handle file uploads
            $hvacLightingEquipment->basic_info = $this->processHvacLightingBasicInfo(
                $validatedData['basic_info'],
                $allData,
                $hvacLightingEquipment->basic_info ?? []
            );
        }

        if (array_key_exists('notes', $validatedData)) {
            Log::info('LifelineEquipmentService: Processing notes');
            $hvacLightingEquipment->notes = $validatedData['notes'];
        }

        $hvacLightingEquipment->save();
        Log::info('LifelineEquipmentService: HVAC/Lighting equipment saved successfully', ['id' => $hvacLightingEquipment->id]);
    }

    /**
     * Process HVAC/Lighting equipment basic info data before saving.
     * Handles data sanitization, validation, and file uploads.
     */
    private function processHvacLightingBasicInfo(array $basicInfo, array $allData = [], array $existingData = []): array
    {
        $processedData = [];

        // Process HVAC info
        if (isset($basicInfo['hvac']) && is_array($basicInfo['hvac'])) {
            $hvacInfo = $basicInfo['hvac'];
            $existingHvacInfo = $existingData['hvac'] ?? [];

            $processedData['hvac'] = [
                'freon_inspector' => isset($hvacInfo['freon_inspector'])
                    ? ($hvacInfo['freon_inspector'] === null ? null : trim($hvacInfo['freon_inspector']))
                    : null,
                'inspection_date' => $hvacInfo['inspection_date'] ?? null,
                'target_equipment' => isset($hvacInfo['target_equipment'])
                    ? ($hvacInfo['target_equipment'] === null ? null : trim($hvacInfo['target_equipment']))
                    : null,
                'notes' => isset($hvacInfo['notes'])
                    ? ($hvacInfo['notes'] === null ? null : trim($hvacInfo['notes']))
                    : null,
            ];

            // Handle inspection report file upload
            if (isset($allData['inspection_report_file']) && $allData['inspection_report_file'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete existing file if it exists
                if (isset($existingHvacInfo['inspection_report_path'])) {
                    $this->fileHandlingService->deleteFile($existingHvacInfo['inspection_report_path']);
                }
                
                $uploadResult = $this->handleFileUpload($allData['inspection_report_file'], 'hvac/inspection-reports');
                if ($uploadResult) {
                    $processedData['hvac']['inspection'] = [
                        'inspection_report_filename' => $uploadResult['filename'],
                        'inspection_report_path' => $uploadResult['path'],
                    ];
                }
            } elseif (isset($existingHvacInfo['inspection'])) {
                // Keep existing file info if no new file uploaded
                $processedData['hvac']['inspection'] = $existingHvacInfo['inspection'];
            }

            // Handle file removal
            if (isset($allData['remove_inspection_report']) && $allData['remove_inspection_report'] === '1') {
                if (isset($existingHvacInfo['inspection']['inspection_report_path'])) {
                    $this->fileHandlingService->deleteFile($existingHvacInfo['inspection']['inspection_report_path']);
                }
                $processedData['hvac']['inspection'] = [
                    'inspection_report_filename' => null,
                    'inspection_report_path' => null,
                ];
            }
        }

        // Process Lighting info
        if (isset($basicInfo['lighting']) && is_array($basicInfo['lighting'])) {
            $lightingInfo = $basicInfo['lighting'];

            $processedData['lighting'] = [
                'manufacturer' => isset($lightingInfo['manufacturer'])
                    ? ($lightingInfo['manufacturer'] === null ? null : trim($lightingInfo['manufacturer']))
                    : null,
                'update_date' => $lightingInfo['update_date'] ?? null,
                'warranty_period' => isset($lightingInfo['warranty_period'])
                    ? ($lightingInfo['warranty_period'] === null ? null : trim($lightingInfo['warranty_period']))
                    : null,
                'notes' => isset($lightingInfo['notes'])
                    ? ($lightingInfo['notes'] === null ? null : trim($lightingInfo['notes']))
                    : null,
            ];
        }

        return $processedData;
    }

    /**
     * Get default HVAC/Lighting equipment structure.
     */
    private function getDefaultHvacLightingEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'hvac_contractor' => '',
                'maintenance_company' => '',
                'last_inspection_date' => '',
                'next_inspection_date' => '',
                'system_type' => '',
                'lighting_type' => '',
            ],
            'notes' => '',
        ];
    }

    /**
     * Get elevator equipment data with formatted structure.
     */
    public function getElevatorEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment;

        if (! $elevatorEquipment) {
            return $this->getDefaultElevatorEquipmentStructure();
        }

        return [
            'basic_info' => $elevatorEquipment->basic_info ?? [],
            'notes' => $elevatorEquipment->notes ?? '',
        ];
    }

    /**
     * Update elevator equipment data.
     */
    public function updateElevatorEquipmentData(
        LifelineEquipment $lifelineEquipment,
        array $validatedData,
        array $allData = []
    ): void {
        Log::info('LifelineEquipmentService: Starting elevator equipment update', [
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'validated_data_keys' => array_keys($validatedData),
        ]);

        $elevatorEquipment = $lifelineEquipment->elevatorEquipment;

        if (! $elevatorEquipment) {
            Log::info('LifelineEquipmentService: Creating new elevator equipment');
            $elevatorEquipment = new ElevatorEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        } else {
            Log::info('LifelineEquipmentService: Updating existing elevator equipment', ['id' => $elevatorEquipment->id]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            Log::info('LifelineEquipmentService: Processing basic_info');
            // Pass the entire validated data and existing data to handle file uploads
            $elevatorEquipment->basic_info = $this->processElevatorBasicInfo(
                $validatedData['basic_info'],
                $allData,
                $elevatorEquipment->basic_info ?? []
            );
        }

        if (array_key_exists('notes', $validatedData)) {
            Log::info('LifelineEquipmentService: Processing notes');
            $elevatorEquipment->notes = $validatedData['notes'];
        }

        $elevatorEquipment->save();
        Log::info('LifelineEquipmentService: Elevator equipment saved successfully', ['id' => $elevatorEquipment->id]);
    }

    /**
     * Process elevator equipment basic info data before saving.
     * Handles data sanitization, validation, and file uploads.
     */
    private function processElevatorBasicInfo(array $basicInfo, array $allData = [], array $existingData = []): array
    {
        $processedData = [
            'availability' => $basicInfo['availability'] ?? null,
        ];

        // Process elevators list if availability is '有'
        if (($basicInfo['availability'] ?? '') === '有' && isset($basicInfo['elevators']) && is_array($basicInfo['elevators'])) {
            $elevatorList = [];

            foreach ($basicInfo['elevators'] as $elevator) {
                if (is_array($elevator) && ! empty(array_filter($elevator))) {
                    $elevatorList[] = [
                        'manufacturer' => isset($elevator['manufacturer'])
                            ? ($elevator['manufacturer'] === null ? null : trim($elevator['manufacturer']))
                            : null,
                        'type' => isset($elevator['type'])
                            ? ($elevator['type'] === null ? null : trim($elevator['type']))
                            : null,
                        'model_year' => $elevator['model_year'] ?? null,
                        'update_date' => $elevator['update_date'] ?? null,
                    ];
                }
            }

            $processedData['elevators'] = $elevatorList;
        } else {
            $processedData['elevators'] = [];
        }

        // Process inspection info
        if (isset($basicInfo['inspection']) && is_array($basicInfo['inspection'])) {
            $inspectionInfo = $basicInfo['inspection'];
            $existingInspectionInfo = $existingData['inspection'] ?? [];

            $processedData['inspection'] = [
                'maintenance_contractor' => isset($inspectionInfo['maintenance_contractor'])
                    ? ($inspectionInfo['maintenance_contractor'] === null ? null : trim($inspectionInfo['maintenance_contractor']))
                    : null,
                'inspection_date' => $inspectionInfo['inspection_date'] ?? null,
            ];

            // Handle inspection report file upload - check in the main data array
            if (isset($allData['inspection_report_file']) && $allData['inspection_report_file'] instanceof \Illuminate\Http\UploadedFile) {
                Log::info('LifelineEquipmentService: Processing file upload');

                // Delete old file if exists - use existing data
                if (isset($existingInspectionInfo['inspection_report_path']) && $existingInspectionInfo['inspection_report_path']) {
                    Storage::disk('public')->delete($existingInspectionInfo['inspection_report_path']);
                }

                $uploadedFile = $this->handleFileUpload($allData['inspection_report_file'], 'elevator/inspection-reports');
                if ($uploadedFile) {
                    $processedData['inspection']['inspection_report_filename'] = $uploadedFile['filename'];
                    $processedData['inspection']['inspection_report_path'] = $uploadedFile['path'];
                    Log::info('LifelineEquipmentService: File uploaded successfully', $uploadedFile);
                }
            } elseif (isset($existingInspectionInfo['inspection_report_filename'])) {
                // Keep existing file info if no new file uploaded - use existing data
                $processedData['inspection']['inspection_report_filename'] = $existingInspectionInfo['inspection_report_filename'];
                $processedData['inspection']['inspection_report_path'] = $existingInspectionInfo['inspection_report_path'] ?? null;
                Log::info('LifelineEquipmentService: Keeping existing file info');
            }

            // Handle file removal - check in the main data array
            if (isset($allData['remove_inspection_report']) && $allData['remove_inspection_report'] === '1') {
                // Delete existing file if it exists - use existing data
                if (isset($existingInspectionInfo['inspection_report_path']) && $existingInspectionInfo['inspection_report_path']) {
                    Storage::disk('public')->delete($existingInspectionInfo['inspection_report_path']);
                }
                $processedData['inspection']['inspection_report_filename'] = null;
                $processedData['inspection']['inspection_report_path'] = null;
                Log::info('LifelineEquipmentService: File removal requested and processed');
            }
        }

        return $processedData;
    }

    /**
     * Get default elevator equipment structure.
     */
    private function getDefaultElevatorEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'availability' => '',
                'elevators' => [],
                'inspection' => [
                    'maintenance_contractor' => '',
                    'inspection_date' => '',
                    'inspection_report_filename' => '',
                    'inspection_report_path' => '',
                ],
            ],
            'notes' => '',
        ];
    }

    /**
     * Check if the category is valid.
     */
    private function isValidCategory(string $category): bool
    {
        return array_key_exists($category, LifelineEquipment::CATEGORIES);
    }

    /**
     * Get or create lifeline equipment record.
     */
    private function getOrCreateLifelineEquipment(
        Facility $facility,
        string $category,
        ?int $userId = null
    ): LifelineEquipment {
        return LifelineEquipment::firstOrCreate(
            [
                'facility_id' => $facility->id,
                'category' => $category,
            ],
            [
                'status' => 'draft',
                'created_by' => $userId ?? auth()->id(),
                'updated_by' => $userId ?? auth()->id(),
            ]
        );
    }

    /**
     * Update lifeline equipment metadata.
     */
    private function updateLifelineEquipmentMetadata(LifelineEquipment $lifelineEquipment, int $userId): void
    {
        $lifelineEquipment->update([
            'updated_by' => $userId,
            'status' => 'active', // Set to active when data is saved
        ]);
    }

    /**
     * Get category-specific data.
     */
    private function getCategorySpecificData(LifelineEquipment $lifelineEquipment, string $category): array
    {
        switch ($category) {
            case 'electrical':
                return $this->getElectricalEquipmentData($lifelineEquipment);

            case 'gas':
                return $this->getGasEquipmentData($lifelineEquipment);

            case 'water':
                return $this->getWaterEquipmentData($lifelineEquipment);

            case 'elevator':
                return $this->getElevatorEquipmentData($lifelineEquipment);

            case 'hvac_lighting':
                return $this->getHvacLightingEquipmentData($lifelineEquipment);

            default:
                throw new Exception('無効なカテゴリです。');
        }
    }

    /**
     * Update category-specific data based on the equipment category.
     *
     * @param  LifelineEquipment  $lifelineEquipment  The lifeline equipment instance
     * @param  string  $category  The equipment category
     * @param  array  $validatedData  The validated data array
     * @param  array  $allData  The complete request data including files
     */
    private function updateCategorySpecificData(
        LifelineEquipment $lifelineEquipment,
        string $category,
        array $validatedData,
        array $allData = []
    ): void {
        switch ($category) {
            case 'electrical':
                $this->updateElectricalEquipmentData($lifelineEquipment, $validatedData, $allData);
                break;

            case 'gas':
                $this->updateGasEquipmentData($lifelineEquipment, $validatedData);
                break;

            case 'water':
                $this->updateWaterEquipmentData($lifelineEquipment, $validatedData, $allData);
                break;

            case 'elevator':
                $this->updateElevatorEquipmentData($lifelineEquipment, $validatedData, $allData);
                break;

            case 'hvac_lighting':
                $this->updateHvacLightingEquipmentData($lifelineEquipment, $validatedData, $allData);
                break;

            default:
                throw new Exception('無効なカテゴリです。');
        }
    }

    /**
     * Get default electrical equipment structure.
     */
    private function getDefaultElectricalEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'electrical_contractor' => '',
                'safety_management_company' => '',
                'maintenance_inspection_date' => '',
                'inspection' => [
                    'inspection_report_pdf' => '',
                    'inspection_report_pdf_path' => '',
                ],
            ],
            'pas_info' => [
                'availability' => '',
                'details' => '',
                'update_date' => '',
            ],
            'cubicle_info' => [
                'availability' => '',
                'details' => '',
                'equipment_list' => [],
            ],
            'generator_info' => [
                'availability' => '',
                'availability_details' => '',
                'equipment_list' => [],
            ],
            'notes' => '',
        ];
    }

    /**
     * Format basic info data.
     */
    private function formatBasicInfo(array $basicInfo): array
    {
        return [
            'electrical_contractor' => $basicInfo['electrical_contractor'] ?? '',
            'safety_management_company' => $basicInfo['safety_management_company'] ?? '',
            'maintenance_inspection_date' => $basicInfo['maintenance_inspection_date'] ?? '',
            'inspection' => [
                'inspection_report_pdf' => $basicInfo['inspection']['inspection_report_pdf'] ?? '',
                'inspection_report_pdf_path' => $basicInfo['inspection']['inspection_report_pdf_path'] ?? '',
            ],
        ];
    }

    /**
     * Format PAS info data.
     */
    private function formatPasInfo(array $pasInfo): array
    {
        return [
            'availability' => $pasInfo['availability'] ?? '',
            'details' => $pasInfo['details'] ?? '',
            'update_date' => $pasInfo['update_date'] ?? '',
        ];
    }

    /**
     * Format cubicle info data.
     */
    private function formatCubicleInfo(array $cubicleInfo): array
    {
        return [
            'availability' => $cubicleInfo['availability'] ?? '',
            'details' => $cubicleInfo['details'] ?? '',
            'equipment_list' => $cubicleInfo['equipment_list'] ?? [],
        ];
    }

    /**
     * Format generator info data.
     */
    private function formatGeneratorInfo(array $generatorInfo): array
    {
        return [
            'availability' => $generatorInfo['availability'] ?? '',
            'availability_details' => $generatorInfo['availability_details'] ?? '',
            'equipment_list' => $generatorInfo['equipment_list'] ?? [],
        ];
    }

    /**
     * Process basic info data before saving.
     * Handles data sanitization, validation, and file uploads.
     */
    private function processBasicInfo(array $basicInfo, array $allData = [], array $existingData = []): array
    {
        $processedData = [
            'electrical_contractor' => isset($basicInfo['electrical_contractor'])
                ? ($basicInfo['electrical_contractor'] === null ? null : trim($basicInfo['electrical_contractor']))
                : null,
            'safety_management_company' => isset($basicInfo['safety_management_company'])
                ? ($basicInfo['safety_management_company'] === null ? null : trim($basicInfo['safety_management_company']))
                : null,
            'maintenance_inspection_date' => $basicInfo['maintenance_inspection_date'] ?? null,
        ];

        // Process inspection info
        $existingInspectionInfo = $existingData['inspection'] ?? [];
        $processedData['inspection'] = [];

        // Handle inspection report file upload - check in the main data array
        if (isset($allData['inspection_report_file']) && $allData['inspection_report_file'] instanceof \Illuminate\Http\UploadedFile) {
            Log::info('LifelineEquipmentService: Processing electrical inspection report file upload');

            // Delete old file if exists
            if (isset($existingInspectionInfo['inspection_report_pdf_path']) && $existingInspectionInfo['inspection_report_pdf_path']) {
                $this->fileHandlingService->deleteFile($existingInspectionInfo['inspection_report_pdf_path']);
            }

            $uploadedFile = $this->handleFileUpload($allData['inspection_report_file'], 'electrical/inspection-reports');
            if ($uploadedFile) {
                $processedData['inspection']['inspection_report_pdf'] = $uploadedFile['filename'];
                $processedData['inspection']['inspection_report_pdf_path'] = $uploadedFile['path'];
                Log::info('LifelineEquipmentService: Electrical inspection report uploaded successfully', $uploadedFile);
            }
        } elseif (isset($existingInspectionInfo['inspection_report_pdf'])) {
            // Keep existing file info if no new file uploaded
            $processedData['inspection']['inspection_report_pdf'] = $existingInspectionInfo['inspection_report_pdf'];
            $processedData['inspection']['inspection_report_pdf_path'] = $existingInspectionInfo['inspection_report_pdf_path'] ?? null;
            Log::info('LifelineEquipmentService: Keeping existing electrical file info');
        }

        // Handle file removal
        if (isset($allData['remove_inspection_report']) && $allData['remove_inspection_report'] === '1') {
            // Delete existing file if it exists
            if (isset($existingInspectionInfo['inspection_report_pdf_path']) && $existingInspectionInfo['inspection_report_pdf_path']) {
                $this->fileHandlingService->deleteFile($existingInspectionInfo['inspection_report_pdf_path']);
            }
            $processedData['inspection']['inspection_report_pdf'] = null;
            $processedData['inspection']['inspection_report_pdf_path'] = null;
            Log::info('LifelineEquipmentService: Electrical file removal requested and processed');
        }

        return $processedData;
    }

    /**
     * Handle file upload using FileHandlingService for consistency.
     */
    private function handleFileUpload(UploadedFile $file, string $directory): ?array
    {
        try {
            $result = $this->fileHandlingService->uploadFile($file, $directory, 'pdf');

            return [
                'filename' => $result['filename'],
                'path' => $result['path'],
                'stored_filename' => $result['stored_filename'],
            ];
        } catch (Exception $e) {
            Log::error('File upload failed in LifelineEquipmentService', [
                'directory' => $directory,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('ファイルのアップロードに失敗しました: '.$e->getMessage());
        }
    }

    /**
     * Process PAS info data before saving.
     */
    private function processPasInfo(array $pasInfo): array
    {
        return [
            'availability' => $pasInfo['availability'] ?? null,
            'details' => isset($pasInfo['details'])
                ? ($pasInfo['details'] === null ? null : trim($pasInfo['details']))
                : null,
            'update_date' => $pasInfo['update_date'] ?? null,
        ];
    }

    /**
     * Process cubicle info data before saving.
     */
    private function processCubicleInfo(array $cubicleInfo): array
    {
        $equipmentList = [];
        if (isset($cubicleInfo['equipment_list']) && is_array($cubicleInfo['equipment_list'])) {
            foreach ($cubicleInfo['equipment_list'] as $equipment) {
                if (is_array($equipment) && ! empty(array_filter($equipment))) {
                    $equipmentList[] = [
                        'manufacturer' => isset($equipment['manufacturer'])
                            ? ($equipment['manufacturer'] === null ? null : trim($equipment['manufacturer']))
                            : null,
                        'model_year' => isset($equipment['model_year'])
                            ? ($equipment['model_year'] === null ? null : trim($equipment['model_year']))
                            : null,
                        'update_date' => $equipment['update_date'] ?? null,
                    ];
                }
            }
        }

        return [
            'availability' => $cubicleInfo['availability'] ?? null,
            'details' => isset($cubicleInfo['details'])
                ? ($cubicleInfo['details'] === null ? null : trim($cubicleInfo['details']))
                : null,
            'equipment_list' => $equipmentList,
        ];
    }

    /**
     * Process generator info data before saving.
     */
    private function processGeneratorInfo(array $generatorInfo): array
    {
        $equipmentList = [];
        if (isset($generatorInfo['equipment_list']) && is_array($generatorInfo['equipment_list'])) {
            foreach ($generatorInfo['equipment_list'] as $equipment) {
                if (is_array($equipment) && ! empty(array_filter($equipment))) {
                    $equipmentList[] = [
                        'manufacturer' => isset($equipment['manufacturer'])
                            ? ($equipment['manufacturer'] === null ? null : trim($equipment['manufacturer']))
                            : null,
                        'model_year' => isset($equipment['model_year'])
                            ? ($equipment['model_year'] === null ? null : trim($equipment['model_year']))
                            : null,
                        'update_date' => $equipment['update_date'] ?? null,
                    ];
                }
            }
        }

        return [
            'availability' => $generatorInfo['availability'] ?? null,
            'availability_details' => isset($generatorInfo['availability_details'])
                ? ($generatorInfo['availability_details'] === null ? null : trim($generatorInfo['availability_details']))
                : null,
            'equipment_list' => $equipmentList,
        ];
    }

    /**
     * Log equipment update activity.
     */
    private function logEquipmentUpdate(Facility $facility, string $category): void
    {
        $categoryDisplayName = LifelineEquipment::CATEGORIES[$category] ?? $category;
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name.' - ライフライン設備('.$categoryDisplayName.')',
            request()
        );
    }

    /**
     * Get all lifeline equipment data for a facility.
     */
    public function getAllEquipmentData(Facility $facility): array
    {
        try {
            $allData = [];
            $categories = array_keys(LifelineEquipment::CATEGORIES);

            foreach ($categories as $category) {
                $result = $this->getEquipmentData($facility, $category);
                if ($result['success']) {
                    $allData[$category] = $result['data'];
                } else {
                    // Log error but continue with other categories
                    $this->logError("Failed to get data for category: {$category}", [
                        'facility_id' => $facility->id,
                        'category' => $category,
                        'error' => $result['message'] ?? 'Unknown error',
                    ]);
                    $allData[$category] = null;
                }
            }

            return [
                'success' => true,
                'data' => [
                    'facility_id' => $facility->id,
                    'facility_name' => $facility->facility_name,
                    'equipment_data' => $allData,
                    'categories' => LifelineEquipment::CATEGORIES,
                ],
                'message' => 'ライフライン設備データを取得しました。',
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get all lifeline equipment data', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '全ライフライン設備データの取得に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get equipment data for multiple categories.
     */
    public function getMultipleCategoriesData(Facility $facility, array $categories): array
    {
        try {
            $data = [];
            $errors = [];

            foreach ($categories as $category) {
                if (! $this->isValidCategory($category)) {
                    $errors[$category] = '無効なカテゴリです。';

                    continue;
                }

                $result = $this->getEquipmentData($facility, $category);
                if ($result['success']) {
                    $data[$category] = $result['data'];
                } else {
                    $errors[$category] = $result['message'] ?? 'データ取得に失敗しました。';
                }
            }

            $success = empty($errors);

            return [
                'success' => $success,
                'data' => [
                    'facility_id' => $facility->id,
                    'equipment_data' => $data,
                    'requested_categories' => $categories,
                ],
                'errors' => $errors,
                'message' => $success
                    ? '指定されたカテゴリのデータを取得しました。'
                    : '一部のカテゴリでデータ取得に失敗しました。',
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get multiple categories data', [
                'facility_id' => $facility->id,
                'categories' => $categories,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '複数カテゴリデータの取得に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk update multiple equipment categories.
     */
    public function bulkUpdateEquipmentData(Facility $facility, array $equipmentData, int $userId): array
    {
        try {
            $results = [];
            $errors = [];
            $successCount = 0;

            DB::beginTransaction();

            try {
                foreach ($equipmentData as $item) {
                    $category = $item['category'];
                    $data = $item['data'];

                    if (! $this->isValidCategory($category)) {
                        $errors[$category] = '無効なカテゴリです。';

                        continue;
                    }

                    $result = $this->updateEquipmentData($facility, $category, $data, $userId);

                    if ($result['success']) {
                        $results[$category] = $result;
                        $successCount++;
                    } else {
                        $errors[$category] = $result['message'] ?? 'データ更新に失敗しました。';
                        if (isset($result['errors'])) {
                            $errors[$category.'_validation'] = $result['errors'];
                        }
                    }
                }

                // Check if we should commit or rollback
                if (! empty($errors)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => '一部のカテゴリで更新に失敗したため、すべての変更をロールバックしました。',
                        'errors' => $errors,
                        'results' => $results,
                    ];
                }

                DB::commit();

                return [
                    'success' => true,
                    'message' => "{$successCount}個のカテゴリを正常に更新しました。",
                    'data' => [
                        'updated_categories' => array_keys($results),
                        'success_count' => $successCount,
                        'results' => $results,
                    ],
                ];
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->logError('Failed to bulk update equipment data', [
                'facility_id' => $facility->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '一括更新に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get equipment summary for a facility.
     */
    public function getEquipmentSummary(Facility $facility): array
    {
        try {
            $summary = [];
            $categories = array_keys(LifelineEquipment::CATEGORIES);

            foreach ($categories as $category) {
                $lifelineEquipment = LifelineEquipment::where('facility_id', $facility->id)
                    ->where('category', $category)
                    ->first();

                $summary[$category] = [
                    'category_name' => LifelineEquipment::CATEGORIES[$category],
                    'has_data' => $lifelineEquipment !== null,
                    'status' => $lifelineEquipment?->status ?? 'not_configured',
                    'status_display' => $lifelineEquipment?->getStatusDisplayName() ?? '未設定',
                    'last_updated' => $lifelineEquipment?->updated_at?->toISOString(),
                    'updated_by' => $lifelineEquipment?->updater?->name,
                ];
            }

            $totalCategories = count($categories);
            $configuredCategories = count(array_filter($summary, fn ($item) => $item['has_data']));
            $completionPercentage = $totalCategories > 0 ? round(($configuredCategories / $totalCategories) * 100, 1) : 0;

            return [
                'success' => true,
                'data' => [
                    'facility_id' => $facility->id,
                    'facility_name' => $facility->facility_name,
                    'summary' => $summary,
                    'statistics' => [
                        'total_categories' => $totalCategories,
                        'configured_categories' => $configuredCategories,
                        'completion_percentage' => $completionPercentage,
                    ],
                ],
                'message' => 'ライフライン設備サマリーを取得しました。',
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get equipment summary', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '設備サマリーの取得に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate data consistency across equipment categories.
     */
    public function validateDataConsistency(Facility $facility, array $equipmentData): array
    {
        try {
            $consistencyIssues = [];
            $warnings = [];

            // Check for common contractor/maintenance company consistency
            $contractors = [];
            $maintenanceCompanies = [];

            foreach ($equipmentData as $category => $data) {
                if (! $this->isValidCategory($category)) {
                    continue;
                }

                // Extract contractor and maintenance company information
                if (isset($data['basic_info'])) {
                    $basicInfo = $data['basic_info'];

                    // Collect contractor information
                    $contractorFields = [
                        'electrical_contractor', 'gas_supplier', 'water_contractor',
                        'elevator_contractor', 'hvac_contractor',
                    ];

                    foreach ($contractorFields as $field) {
                        if (! empty($basicInfo[$field])) {
                            $contractors[$category] = $basicInfo[$field];
                            break;
                        }
                    }

                    // Collect maintenance company information
                    $maintenanceFields = [
                        'safety_management_company', 'maintenance_company',
                    ];

                    foreach ($maintenanceFields as $field) {
                        if (! empty($basicInfo[$field])) {
                            $maintenanceCompanies[$category] = $basicInfo[$field];
                            break;
                        }
                    }
                }
            }

            // Check for potential inconsistencies
            if (count(array_unique($contractors)) > 1) {
                $warnings[] = [
                    'type' => 'contractor_inconsistency',
                    'message' => '複数の異なる契約会社が設定されています。',
                    'details' => $contractors,
                ];
            }

            if (count(array_unique($maintenanceCompanies)) > 1) {
                $warnings[] = [
                    'type' => 'maintenance_company_inconsistency',
                    'message' => '複数の異なる保守会社が設定されています。',
                    'details' => $maintenanceCompanies,
                ];
            }

            // Check for missing critical information
            $criticalFields = ['electrical', 'gas', 'water'];
            $missingCritical = [];

            foreach ($criticalFields as $category) {
                if (! isset($equipmentData[$category]) ||
                    empty($equipmentData[$category]['basic_info'])) {
                    $missingCritical[] = LifelineEquipment::CATEGORIES[$category];
                }
            }

            if (! empty($missingCritical)) {
                $consistencyIssues[] = [
                    'type' => 'missing_critical_equipment',
                    'message' => '重要な設備情報が不足しています: '.implode(', ', $missingCritical),
                    'severity' => 'high',
                ];
            }

            $hasIssues = ! empty($consistencyIssues);
            $hasWarnings = ! empty($warnings);

            return [
                'success' => true,
                'data' => [
                    'facility_id' => $facility->id,
                    'validation_status' => $hasIssues ? 'issues_found' : ($hasWarnings ? 'warnings_found' : 'consistent'),
                    'consistency_issues' => $consistencyIssues,
                    'warnings' => $warnings,
                    'recommendations' => $this->generateConsistencyRecommendations($consistencyIssues, $warnings),
                ],
                'message' => $hasIssues
                    ? 'データ整合性に問題が見つかりました。'
                    : ($hasWarnings ? 'データ整合性に注意点があります。' : 'データ整合性に問題はありません。'),
            ];
        } catch (Exception $e) {
            $this->logError('Failed to validate data consistency', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'データ整合性チェックに失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available categories and their configuration status.
     */
    public function getAvailableCategories(Facility $facility): array
    {
        try {
            $categories = [];

            foreach (LifelineEquipment::CATEGORIES as $key => $name) {
                $lifelineEquipment = LifelineEquipment::where('facility_id', $facility->id)
                    ->where('category', $key)
                    ->first();

                $categories[$key] = [
                    'key' => $key,
                    'name' => $name,
                    'is_configured' => $lifelineEquipment !== null,
                    'status' => $lifelineEquipment?->status ?? 'not_configured',
                    'status_display' => $lifelineEquipment?->getStatusDisplayName() ?? '未設定',
                    'has_detailed_implementation' => $key === 'electrical', // Only electrical is fully implemented
                    'last_updated' => $lifelineEquipment?->updated_at?->toISOString(),
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'facility_id' => $facility->id,
                    'categories' => $categories,
                    'available_statuses' => LifelineEquipment::STATUSES,
                ],
                'message' => '利用可能なカテゴリ情報を取得しました。',
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get available categories', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'カテゴリ情報の取得に失敗しました。',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate consistency recommendations based on issues and warnings.
     */
    private function generateConsistencyRecommendations(array $issues, array $warnings): array
    {
        $recommendations = [];

        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'missing_critical_equipment':
                    $recommendations[] = [
                        'priority' => 'high',
                        'action' => '重要な設備情報（電気、ガス、水道）の基本情報を入力してください。',
                        'category' => 'data_completion',
                    ];
                    break;
            }
        }

        foreach ($warnings as $warning) {
            switch ($warning['type']) {
                case 'contractor_inconsistency':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => '契約会社の情報を確認し、必要に応じて統一してください。',
                        'category' => 'data_consistency',
                    ];
                    break;
                case 'maintenance_company_inconsistency':
                    $recommendations[] = [
                        'priority' => 'medium',
                        'action' => '保守会社の情報を確認し、必要に応じて統一してください。',
                        'category' => 'data_consistency',
                    ];
                    break;
            }
        }

        return $recommendations;
    }

    /**
     * Get the service-specific exception class.
     */
    protected function getServiceExceptionClass(): string
    {
        return LifelineEquipmentServiceException::class;
    }
}
