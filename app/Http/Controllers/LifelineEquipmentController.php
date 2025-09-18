<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Services\LifelineEquipmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LifelineEquipmentController extends Controller
{
    protected LifelineEquipmentService $lifelineEquipmentService;

    public function __construct(LifelineEquipmentService $lifelineEquipmentService)
    {
        $this->lifelineEquipmentService = $lifelineEquipmentService;
    }

    /**
     * Display the specified lifeline equipment category data.
     */
    public function show(Facility $facility, string $category): JsonResponse
    {
        try {
            // Check authorization using LifelineEquipment policy
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // Use service to get equipment data
            $result = $this->lifelineEquipmentService->getEquipmentData($facility, $category);

            if (!$result['success']) {
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
     * Show the form for editing the specified lifeline equipment category.
     */
    public function edit(Facility $facility, string $category)
    {
        try {
            // Check authorization using LifelineEquipment policy
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // Validate category
            if (!array_key_exists($category, LifelineEquipment::CATEGORIES)) {
                abort(404, 'Invalid equipment category');
            }

            // Return the appropriate edit view based on category
            $viewName = "facilities.lifeline-equipment.{$category}-edit";
            
            return view($viewName, compact('facility', 'category'));
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
     * Update the specified lifeline equipment category data.
     */
    public function update(Request $request, Facility $facility, string $category)
    {
        try {
            // Check authorization using LifelineEquipment policy
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // Use service to update equipment data
            $result = $this->lifelineEquipmentService->updateEquipmentData(
                $facility,
                $category,
                $request->all(),
                auth()->id()
            );

            // Handle AJAX requests
            if ($request->expectsJson()) {
                if (!$result['success']) {
                    $statusCode = isset($result['errors']) ? 422 : 500;
                    return response()->json($result, $statusCode);
                }
                return response()->json($result);
            }

            // Handle form submissions
            if (!$result['success']) {
                if (isset($result['errors'])) {
                    return back()->withErrors($result['errors'])->withInput();
                }
                return back()->with('error', $result['message'] ?? 'システムエラーが発生しました。')->withInput();
            }

            return redirect()->route('facilities.show', $facility)
                           ->with('success', 'ライフライン設備情報を更新しました。')
                           ->withFragment($category);

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
                    Rule::in(array_keys(LifelineEquipment::CATEGORIES))
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
                    Rule::in(array_keys(LifelineEquipment::CATEGORIES))
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
     * Format unified API response.
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

        if (!$result['success']) {
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
     * Download inspection report PDF.
     */
    public function downloadInspectionReport(Facility $facility, string $category, string $filename)
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // Validate category
            if (!array_key_exists($category, LifelineEquipment::CATEGORIES)) {
                abort(404, 'Invalid equipment category');
            }

            // Get lifeline equipment
            $lifelineEquipment = $facility->getLifelineEquipmentByCategory($category);
            if (!$lifelineEquipment) {
                abort(404, 'Equipment not found');
            }

            // Get equipment data to verify file exists
            $equipmentData = null;
            switch ($category) {
                case 'electrical':
                    $equipmentData = $lifelineEquipment->electricalEquipment;
                    break;
                // Add other categories as needed
            }

            if (!$equipmentData) {
                abort(404, 'Equipment data not found');
            }

            $basicInfo = $equipmentData->basic_info ?? [];
            if (empty($basicInfo['inspection_report_pdf_path'])) {
                abort(404, 'File not found');
            }

            $filePath = $basicInfo['inspection_report_pdf_path'];
            
            // Check if file exists in storage
            if (!Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found in storage');
            }

            // Return file download response
            return Storage::disk('public')->download($filePath, $filename);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'この施設のファイルをダウンロードする権限がありません。');
        } catch (Exception $e) {
            Log::error('Inspection report download failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'filename' => $filename,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }
}