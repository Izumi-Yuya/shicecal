<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Services\LifelineEquipmentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     * Update the specified lifeline equipment category data.
     */
    public function update(Request $request, Facility $facility, string $category): JsonResponse
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

            if (!$result['success']) {
                $statusCode = isset($result['errors']) ? 422 : 500;
                return response()->json($result, $statusCode);
            }

            return response()->json($result);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設のライフライン設備情報を編集する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline equipment update failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
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
}