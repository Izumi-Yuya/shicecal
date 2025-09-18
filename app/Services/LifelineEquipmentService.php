<?php

namespace App\Services;

use App\Models\ElectricalEquipment;
use App\Models\ElevatorEquipment;
use App\Models\Facility;
use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use App\Exceptions\LifelineEquipmentServiceException;
use App\Services\Traits\HandlesServiceErrors;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LifelineEquipmentService
{
    use HandlesServiceErrors;

    protected LifelineEquipmentValidationService $validationService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        LifelineEquipmentValidationService $validationService,
        ActivityLogService $activityLogService
    ) {
        $this->validationService = $validationService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get lifeline equipment data for a specific facility and category.
     */
    public function getEquipmentData(Facility $facility, string $category): array
    {
        try {
            // Validate category
            if (!$this->isValidCategory($category)) {
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
     * Update lifeline equipment data for a specific facility and category.
     */
    public function updateEquipmentData(
        Facility $facility,
        string $category,
        array $data,
        int $userId
    ): array {
        try {
            // Validate category
            if (!$this->isValidCategory($category)) {
                return [
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ];
            }

            // Validate data using validation service
            $validationResult = $this->validationService->validateCategoryData($category, $data);
            if (!$validationResult['success']) {
                return $validationResult;
            }

            $validatedData = $validationResult['data'];

            DB::beginTransaction();

            try {
                // Get or create lifeline equipment record
                $lifelineEquipment = $this->getOrCreateLifelineEquipment($facility, $category, $userId);

                // Update lifeline equipment status and metadata
                $this->updateLifelineEquipmentMetadata($lifelineEquipment, $userId);

                // Update category-specific data
                $this->updateCategorySpecificData($lifelineEquipment, $category, $validatedData);

                DB::commit();

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
     * Get electrical equipment data with formatted structure.
     */
    public function getElectricalEquipmentData(LifelineEquipment $lifelineEquipment): array
    {
        $electricalEquipment = $lifelineEquipment->electricalEquipment;

        if (!$electricalEquipment) {
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
        array $validatedData
    ): void {
        $electricalEquipment = $lifelineEquipment->electricalEquipment;

        if (!$electricalEquipment) {
            $electricalEquipment = new ElectricalEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        // This allows for partial updates without overwriting existing data
        if (array_key_exists('basic_info', $validatedData)) {
            $electricalEquipment->basic_info = $this->processBasicInfo($validatedData['basic_info']);
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

        if (!$gasEquipment) {
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

        if (!$gasEquipment) {
            $gasEquipment = new \App\Models\GasEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $gasEquipment->basic_info = $validatedData['basic_info'];
        }

        if (array_key_exists('notes', $validatedData)) {
            $gasEquipment->notes = $validatedData['notes'];
        }

        $gasEquipment->save();
    }

    /**
     * Get default gas equipment structure.
     */
    private function getDefaultGasEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'gas_supplier' => '',
                'safety_management_company' => '',
                'maintenance_inspection_date' => '',
                'inspection_report_pdf' => '',
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

        if (!$waterEquipment) {
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
        array $validatedData
    ): void {
        $waterEquipment = $lifelineEquipment->waterEquipment;

        if (!$waterEquipment) {
            $waterEquipment = new \App\Models\WaterEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $waterEquipment->basic_info = $validatedData['basic_info'];
        }

        if (array_key_exists('notes', $validatedData)) {
            $waterEquipment->notes = $validatedData['notes'];
        }

        $waterEquipment->save();
    }

    /**
     * Get default water equipment structure.
     */
    private function getDefaultWaterEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'water_contractor' => '',
                'maintenance_company' => '',
                'maintenance_date' => '',
                'inspection_report' => '',
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

        if (!$hvacLightingEquipment) {
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
        array $validatedData
    ): void {
        $hvacLightingEquipment = $lifelineEquipment->hvacLightingEquipment;

        if (!$hvacLightingEquipment) {
            $hvacLightingEquipment = new HvacLightingEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $hvacLightingEquipment->basic_info = $validatedData['basic_info'];
        }

        if (array_key_exists('notes', $validatedData)) {
            $hvacLightingEquipment->notes = $validatedData['notes'];
        }

        $hvacLightingEquipment->save();
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

        if (!$elevatorEquipment) {
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
        array $validatedData
    ): void {
        $elevatorEquipment = $lifelineEquipment->elevatorEquipment;

        if (!$elevatorEquipment) {
            $elevatorEquipment = new ElevatorEquipment([
                'lifeline_equipment_id' => $lifelineEquipment->id,
            ]);
        }

        // Update only the sections that are provided in the request
        if (array_key_exists('basic_info', $validatedData)) {
            $elevatorEquipment->basic_info = $validatedData['basic_info'];
        }

        if (array_key_exists('notes', $validatedData)) {
            $elevatorEquipment->notes = $validatedData['notes'];
        }

        $elevatorEquipment->save();
    }

    /**
     * Get default elevator equipment structure.
     */
    private function getDefaultElevatorEquipmentStructure(): array
    {
        return [
            'basic_info' => [
                'elevator_contractor' => '',
                'maintenance_company' => '',
                'maintenance_date' => '',
                'inspection_report' => '',
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
     * Update category-specific data.
     */
    private function updateCategorySpecificData(
        LifelineEquipment $lifelineEquipment,
        string $category,
        array $validatedData
    ): void {
        switch ($category) {
            case 'electrical':
                $this->updateElectricalEquipmentData($lifelineEquipment, $validatedData);
                break;

            case 'gas':
                $this->updateGasEquipmentData($lifelineEquipment, $validatedData);
                break;

            case 'water':
                $this->updateWaterEquipmentData($lifelineEquipment, $validatedData);
                break;

            case 'elevator':
                $this->updateElevatorEquipmentData($lifelineEquipment, $validatedData);
                break;

            case 'hvac_lighting':
                $this->updateHvacLightingEquipmentData($lifelineEquipment, $validatedData);
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
                'inspection_report_pdf' => '',
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
            'inspection_report_pdf' => $basicInfo['inspection_report_pdf'] ?? '',
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
     */
    private function processBasicInfo(array $basicInfo): array
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

        // Handle PDF file upload
        if (isset($basicInfo['inspection_report_pdf_file']) && $basicInfo['inspection_report_pdf_file'] instanceof UploadedFile) {
            $uploadedFile = $this->handlePdfUpload($basicInfo['inspection_report_pdf_file'], 'electrical/inspection-reports');
            if ($uploadedFile) {
                $processedData['inspection_report_pdf'] = $uploadedFile['filename'];
                $processedData['inspection_report_pdf_path'] = $uploadedFile['path'];
            }
        } elseif (isset($basicInfo['inspection_report_pdf'])) {
            // Keep existing PDF filename if no new file uploaded
            $processedData['inspection_report_pdf'] = $basicInfo['inspection_report_pdf'] === null ? null : trim($basicInfo['inspection_report_pdf']);
        } else {
            $processedData['inspection_report_pdf'] = null;
        }

        return $processedData;
    }

    /**
     * Handle PDF file upload for inspection reports.
     */
    private function handlePdfUpload(UploadedFile $file, string $directory): ?array
    {
        try {
            // Validate file type
            if (!in_array($file->getClientMimeType(), ['application/pdf'])) {
                throw new Exception('PDFファイルのみアップロード可能です。');
            }

            // Validate file size (max 10MB)
            if ($file->getSize() > 10 * 1024 * 1024) {
                throw new Exception('ファイルサイズは10MB以下にしてください。');
            }

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = pathinfo($originalName, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            return [
                'filename' => $originalName,
                'path' => $path,
                'stored_filename' => $filename,
            ];
        } catch (Exception $e) {
            Log::error('PDF upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);
            
            throw new Exception('PDFファイルのアップロードに失敗しました: ' . $e->getMessage());
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
                if (is_array($equipment) && !empty(array_filter($equipment))) {
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
                if (is_array($equipment) && !empty(array_filter($equipment))) {
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
            $facility->facility_name . ' - ライフライン設備(' . $categoryDisplayName . ')',
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
                if (!$this->isValidCategory($category)) {
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

                    if (!$this->isValidCategory($category)) {
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
                            $errors[$category . '_validation'] = $result['errors'];
                        }
                    }
                }

                // Check if we should commit or rollback
                if (!empty($errors)) {
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
            $configuredCategories = count(array_filter($summary, fn($item) => $item['has_data']));
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
                if (!$this->isValidCategory($category)) {
                    continue;
                }

                // Extract contractor and maintenance company information
                if (isset($data['basic_info'])) {
                    $basicInfo = $data['basic_info'];
                    
                    // Collect contractor information
                    $contractorFields = [
                        'electrical_contractor', 'gas_supplier', 'water_contractor', 
                        'elevator_contractor', 'hvac_contractor'
                    ];
                    
                    foreach ($contractorFields as $field) {
                        if (!empty($basicInfo[$field])) {
                            $contractors[$category] = $basicInfo[$field];
                            break;
                        }
                    }

                    // Collect maintenance company information
                    $maintenanceFields = [
                        'safety_management_company', 'maintenance_company'
                    ];
                    
                    foreach ($maintenanceFields as $field) {
                        if (!empty($basicInfo[$field])) {
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
                if (!isset($equipmentData[$category]) || 
                    empty($equipmentData[$category]['basic_info'])) {
                    $missingCritical[] = LifelineEquipment::CATEGORIES[$category];
                }
            }

            if (!empty($missingCritical)) {
                $consistencyIssues[] = [
                    'type' => 'missing_critical_equipment',
                    'message' => '重要な設備情報が不足しています: ' . implode(', ', $missingCritical),
                    'severity' => 'high',
                ];
            }

            $hasIssues = !empty($consistencyIssues);
            $hasWarnings = !empty($warnings);

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