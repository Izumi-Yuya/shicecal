<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Services\FileHandlingService;
use App\Services\LifelineEquipmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LifelineEquipmentController extends Controller
{
    protected LifelineEquipmentService $lifelineEquipmentService;

    protected FileHandlingService $fileHandlingService;

    public function __construct(
        LifelineEquipmentService $lifelineEquipmentService,
        FileHandlingService $fileHandlingService
    ) {
        $this->lifelineEquipmentService = $lifelineEquipmentService;
        $this->fileHandlingService = $fileHandlingService;
    }

    /**
     * Display lifeline equipment data for the specified category.
     */
    public function show(Facility $facility, string $category): JsonResponse
    {
        try {
            // Check authorization using the LifelineEquipment policy
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // Normalize category (convert hyphens to underscores for internal processing)
            $normalizedCategory = str_replace('-', '_', $category);

            // Use service to get equipment data
            $result = $this->lifelineEquipmentService->getEquipmentData($facility, $normalizedCategory);

            if (! $result['success']) {
                return response()->json($result, 422);
            }

            return response()->json($result);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設のライフライン設備情報を表示する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline equipment show failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
        }
    }

    /**
     * Shows the form for editing lifeline equipment for the specified category.
     */
    public function edit(Facility $facility, string $category)
    {
        try {
            // Check authorization using the LifelineEquipment policy
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // Normalize category (convert hyphens to underscores for internal processing)
            $normalizedCategory = str_replace('-', '_', $category);

            // Validate category
            if (! array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                abort(404, 'Invalid equipment category');
            }

            // Special handling for security disaster category
            if ($normalizedCategory === 'security_disaster') {
                return redirect()->route('facilities.security-disaster.edit', $facility);
            }

            // Return the appropriate edit view based on category
            // Convert underscores back to hyphens for view name
            $viewCategory = str_replace('_', '-', $normalizedCategory);
            $viewName = "facilities.lifeline-equipment.{$viewCategory}-edit";

            return view($viewName, [
                'facility' => $facility,
                'category' => $normalizedCategory,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設のライフライン設備情報を編集する権限がありません。');
        } catch (Exception $e) {
            Log::error('Lifeline equipment edit failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, 'システムエラーが発生しました。');
        }
    }

    /**
     * Update lifeline equipment data for the specified category.
     */
    public function update(Request $request, Facility $facility, string $category)
    {
        try {
            // Check authorization using the LifelineEquipment policy
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // Normalize category (convert hyphens to underscores for internal processing)
            $normalizedCategory = str_replace('-', '_', $category);

            // Use service to update equipment data
            $result = $this->lifelineEquipmentService->updateEquipmentData(
                $facility,
                $normalizedCategory,
                $request->all(),
                auth()->id()
            );

            // Handle AJAX requests
            if ($request->expectsJson()) {
                if (! $result['success']) {
                    $statusCode = isset($result['errors']) ? 422 : 500;

                    return response()->json($result, $statusCode);
                }

                return response()->json($result);
            }

            // Handle form submissions
            if (! $result['success']) {
                if (isset($result['errors'])) {
                    return back()->withErrors($result['errors'])->withInput();
                }

                return back()->with('error', $result['message'] ?? 'システムエラーが発生しました。')->withInput();
            }

            // Always redirect to the facility show page after successful update
            return redirect(route('facilities.show', $facility).'#'.str_replace('_', '-', $normalizedCategory))
                ->with('success', 'ライフライン設備情報を更新しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この施設のライフライン設備情報を編集する権限がありません。',
                ], 403);
            }
            abort(403, 'この施設のライフライン設備情報を編集する権限がありません。');
        } catch (Exception $e) {
            Log::error('Lifeline equipment update failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'システムエラーが発生しました。',
                ], 500);
            }

            return back()->with('error', 'システムエラーが発生しました。')->withInput();
        }
    }

    /**
     * Get all lifeline equipment data for a facility.
     */
    public function index(Facility $facility): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $result = $this->lifelineEquipmentService->getAllEquipmentData($facility);

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を表示する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment index failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Get equipment data for multiple categories.
     */
    public function getMultipleCategories(Request $request, Facility $facility): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $request->validate([
                'categories' => 'required|array|min:1',
                'categories.*' => [
                    'required',
                    'string',
                    Rule::in(array_keys(LifelineEquipment::CATEGORIES)),
                ],
            ]);

            $categories = $request->input('categories');
            $result = $this->lifelineEquipmentService->getMultipleCategoriesData($facility, $categories);

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->formatValidationErrorResponse($e);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を表示する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment multiple categories failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Bulk update multiple equipment categories.
     */
    public function bulkUpdate(Request $request, Facility $facility): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            $request->validate([
                'equipment_data' => 'required|array|min:1',
                'equipment_data.*.category' => [
                    'required',
                    'string',
                    Rule::in(array_keys(LifelineEquipment::CATEGORIES)),
                ],
                'equipment_data.*.data' => 'required|array',
            ]);

            $equipmentData = $request->input('equipment_data');
            $result = $this->lifelineEquipmentService->bulkUpdateEquipmentData(
                $facility,
                $equipmentData,
                auth()->id()
            );

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->formatValidationErrorResponse($e);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を編集する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment bulk update failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Get equipment summary for a facility.
     */
    public function summary(Facility $facility): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $result = $this->lifelineEquipmentService->getEquipmentSummary($facility);

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を表示する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment summary failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Validate data consistency across equipment categories.
     */
    public function validateConsistency(Request $request, Facility $facility): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $request->validate([
                'equipment_data' => 'required|array|min:1',
            ]);

            $equipmentData = $request->input('equipment_data');
            $result = $this->lifelineEquipmentService->validateDataConsistency($facility, $equipmentData);

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->formatValidationErrorResponse($e);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を表示する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment consistency validation failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Get available categories and their status.
     */
    public function categories(Facility $facility): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            $result = $this->lifelineEquipmentService->getAvailableCategories($facility);

            return $this->formatApiResponse($result);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->formatErrorResponse(
                'この施設のライフライン設備情報を表示する権限がありません。',
                403
            );
        } catch (Exception $e) {
            Log::error('Lifeline equipment categories failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse('システムエラーが発生しました。', 500);
        }
    }

    /**
     * Format a unified API response.
     */
    private function formatApiResponse(array $result): JsonResponse
    {
        $statusCode = $result['success'] ? 200 : ($result['status_code'] ?? 500);

        $response = [
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
                'request_id' => request()->header('X-Request-ID', uniqid()),
            ],
        ];

        if (! $result['success']) {
            $response['errors'] = $result['errors'] ?? null;
            $response['error_code'] = $result['error_code'] ?? null;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format error response.
     */
    private function formatErrorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return $this->formatApiResponse([
            'success' => false,
            'message' => $message,
            'status_code' => $statusCode,
        ]);
    }

    /**
     * Format validation error response.
     */
    private function formatValidationErrorResponse(\Illuminate\Validation\ValidationException $e): JsonResponse
    {
        return $this->formatApiResponse([
            'success' => false,
            'message' => '入力内容に誤りがあります。',
            'errors' => $e->errors(),
            'error_code' => 'VALIDATION_ERROR',
            'status_code' => 422,
        ]);
    }

    /**
     * Download lifeline equipment file.
     */
    public function downloadFile(Facility $facility, string $category, string $type)
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // Normalize category (convert hyphens to underscores for internal processing)
            $normalizedCategory = str_replace('-', '_', $category);

            // Validate category
            if (! array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                abort(404, 'Invalid equipment category');
            }

            // Get lifeline equipment
            $lifelineEquipment = $facility->getLifelineEquipmentByCategory($normalizedCategory);
            if (! $lifelineEquipment) {
                abort(404, 'Equipment not found');
            }

            // Get equipment data based on category
            $equipmentData = null;
            switch ($normalizedCategory) {
                case 'electrical':
                    $equipmentData = $lifelineEquipment->electricalEquipment;
                    break;
                case 'gas':
                    $equipmentData = $lifelineEquipment->gasEquipment;
                    break;
                case 'water':
                    $equipmentData = $lifelineEquipment->waterEquipment;
                    break;
                case 'elevator':
                    $equipmentData = $lifelineEquipment->elevatorEquipment;
                    break;
                case 'hvac_lighting':
                    $equipmentData = $lifelineEquipment->hvacLightingEquipment;
                    break;
                default:
                    abort(404, 'Invalid equipment category');
            }

            if (! $equipmentData) {
                abort(404, 'Equipment data not found');
            }

            $filePath = null;
            $fileName = null;

            // Get file path and name based on type
            $basicInfo = $equipmentData->basic_info ?? [];
            switch ($type) {
                case 'inspection_report':
                    $filePath = $basicInfo['inspection']['inspection_report_pdf_path'] ?? null;
                    $fileName = $basicInfo['inspection']['inspection_report_pdf'] ?? null;
                    break;
                case 'hvac_inspection_report':
                    $filePath = $basicInfo['hvac']['inspection']['inspection_report_path'] ?? null;
                    $fileName = $basicInfo['hvac']['inspection']['inspection_report_filename'] ?? null;
                    break;
                    // Water equipment file types
                case 'tank_cleaning_report':
                    $filePath = $basicInfo['tank_cleaning']['tank_cleaning_report_pdf_path'] ?? null;
                    $fileName = $basicInfo['tank_cleaning']['tank_cleaning_report_pdf'] ?? null;
                    break;
                case 'septic_tank_inspection_report':
                    $filePath = $basicInfo['septic_tank_info']['inspection']['inspection_report_pdf_path'] ?? null;
                    $fileName = $basicInfo['septic_tank_info']['inspection']['inspection_report_pdf'] ?? null;
                    break;
                default:
                    // Handle legionella report files (pattern: legionella_report_0, legionella_report_1, etc.)
                    if (preg_match('/^legionella_report_(\d+)$/', $type, $matches)) {
                        $index = (int) $matches[1];
                        $filePath = $basicInfo['legionella_info']['inspections'][$index]['report']['report_pdf_path'] ?? null;
                        $fileName = $basicInfo['legionella_info']['inspections'][$index]['report']['report_pdf'] ?? null;
                    } else {
                        abort(404, '指定されたファイルタイプが無効です。');
                    }
                    break;
            }

            if (! $filePath) {
                abort(404, 'ファイルが見つかりません。');
            }

            // Use FileHandlingService for download
            try {
                return $this->fileHandlingService->downloadFile($filePath, $fileName);
            } catch (Exception $e) {
                Log::error('Lifeline equipment file download failed', [
                    'facility_id' => $facility->id,
                    'category' => $normalizedCategory,
                    'file_type' => $type,
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                ]);

                abort(500, 'ファイルのダウンロードに失敗しました。');
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設のファイルをダウンロードする権限がありません。');
        } catch (Exception $e) {
            Log::error('Lifeline equipment file download failed', [
                'facility_id' => $facility->id,
                'category' => $normalizedCategory,
                'file_type' => $type,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }
}
