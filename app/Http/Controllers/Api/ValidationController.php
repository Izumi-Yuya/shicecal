<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ValidationRuleService;
use Illuminate\Http\JsonResponse;

class ValidationController extends Controller
{
    /**
     * Get validation configuration for frontend
     */
    public function getValidationConfig(): JsonResponse
    {
        try {
            $config = ValidationRuleService::getValidationConfig();

            return response()->json([
                'success' => true,
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validation configuration',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get validation rules for specific ownership type
     */
    public function getRulesForOwnershipType(string $ownershipType): JsonResponse
    {
        try {
            $validOwnershipTypes = ['owned', 'leased', 'owned_rental'];

            if (! in_array($ownershipType, $validOwnershipTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ownership type',
                ], 400);
            }

            $rules = ValidationRuleService::getLandInfoRules($ownershipType);

            return response()->json([
                'success' => true,
                'data' => [
                    'ownership_type' => $ownershipType,
                    'rules' => $rules,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve validation rules',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
