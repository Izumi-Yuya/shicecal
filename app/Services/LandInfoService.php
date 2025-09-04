<?php

namespace App\Services;

use App\Models\LandInfo;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class LandInfoService
{
    protected LandCalculationService $calculationService;
    protected NotificationService $notificationService;

    public function __construct(
        LandCalculationService $calculationService,
        NotificationService $notificationService
    ) {
        $this->calculationService = $calculationService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get land information for a facility with caching
     * Requirements: 1.1, 1.2
     *
     * @param Facility $facility
     * @return LandInfo|null
     */
    public function getLandInfo(Facility $facility): ?LandInfo
    {
        return Cache::remember(
            "land_info.facility.{$facility->id}",
            3600, // 1 hour
            fn() => $facility->landInfo
        );
    }

    /**
     * Create or update land information for a facility
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 9.1, 9.2, 9.3, 9.4, 9.5
     *
     * @param Facility $facility
     * @param array $data
     * @param User $user
     * @return LandInfo
     * @throws Exception
     */
    public function createOrUpdateLandInfo(Facility $facility, array $data, User $user): LandInfo
    {
        try {
            DB::beginTransaction();

            // Sanitize input data
            $sanitizedData = $this->sanitizeInputData($data);

            // Perform automatic calculations
            $calculatedData = $this->performCalculations($sanitizedData);

            // Merge sanitized and calculated data
            $finalData = array_merge($sanitizedData, $calculatedData);

            // Get or create land info record
            $landInfo = $facility->landInfo ?? new LandInfo(['facility_id' => $facility->id]);
            $isNewRecord = !$landInfo->exists;

            // Store original data for audit logging
            $originalData = $landInfo->exists ? $landInfo->toArray() : null;

            // Check if approval is enabled
            if ($this->isApprovalEnabled()) {
                $landInfo = $this->handleApprovalWorkflow($landInfo, $finalData, $user);
            } else {
                $landInfo = $this->directUpdate($landInfo, $finalData, $user);
            }

            // Log the change for audit purposes
            $this->logLandInfoChange($landInfo, $originalData, $finalData, $user, $isNewRecord ? 'create' : 'update');

            // Clear cache
            $this->clearLandInfoCache($facility);

            DB::commit();

            return $landInfo;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create/update land info', [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Format land information data for display
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
     *
     * @param LandInfo $landInfo
     * @return array
     */
    public function formatDisplayData(LandInfo $landInfo): array
    {
        return [
            'id' => $landInfo->id,
            'facility_id' => $landInfo->facility_id,
            'ownership_type' => $landInfo->ownership_type,
            'parking_spaces' => $landInfo->parking_spaces,

            // Formatted area information
            'site_area_sqm' => $landInfo->site_area_sqm,
            'site_area_tsubo' => $landInfo->site_area_tsubo,
            'formatted_site_area_sqm' => $landInfo->formatted_site_area_sqm,
            'formatted_site_area_tsubo' => $landInfo->formatted_site_area_tsubo,

            // Formatted financial information
            'purchase_price' => $landInfo->purchase_price,
            'formatted_purchase_price' => $landInfo->formatted_purchase_price,
            'unit_price_per_tsubo' => $landInfo->unit_price_per_tsubo,
            'formatted_unit_price_per_tsubo' => $landInfo->formatted_unit_price_per_tsubo,
            'monthly_rent' => $landInfo->monthly_rent,
            'formatted_monthly_rent' => $landInfo->formatted_monthly_rent,

            // Contract information
            'contract_start_date' => $landInfo->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $landInfo->contract_end_date?->format('Y-m-d'),
            'japanese_contract_start_date' => $landInfo->japanese_contract_start_date,
            'japanese_contract_end_date' => $landInfo->japanese_contract_end_date,
            'auto_renewal' => $landInfo->auto_renewal,
            'contract_period_text' => $landInfo->contract_period_text,

            // Management company information
            'management_company_name' => $landInfo->management_company_name,
            'management_company_postal_code' => $landInfo->management_company_postal_code,
            'management_company_address' => $landInfo->management_company_address,
            'management_company_building' => $landInfo->management_company_building,
            'management_company_phone' => $landInfo->management_company_phone,
            'management_company_fax' => $landInfo->management_company_fax,
            'management_company_email' => $landInfo->management_company_email,
            'management_company_url' => $landInfo->management_company_url,
            'management_company_notes' => $landInfo->management_company_notes,

            // Owner information
            'owner_name' => $landInfo->owner_name,
            'owner_postal_code' => $landInfo->owner_postal_code,
            'owner_address' => $landInfo->owner_address,
            'owner_building' => $landInfo->owner_building,
            'owner_phone' => $landInfo->owner_phone,
            'owner_fax' => $landInfo->owner_fax,
            'owner_email' => $landInfo->owner_email,
            'owner_url' => $landInfo->owner_url,
            'owner_notes' => $landInfo->owner_notes,

            // Other information
            'notes' => $landInfo->notes,
            'status' => $landInfo->status,
            'approved_at' => $landInfo->approved_at?->format('Y-m-d H:i:s'),
            'approved_by' => $landInfo->approved_by,
            'created_by' => $landInfo->created_by,
            'updated_by' => $landInfo->updated_by,
            'created_at' => $landInfo->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $landInfo->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Prepare land information for approval workflow
     * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
     *
     * @param LandInfo $landInfo
     * @param array $changes
     * @return void
     */
    public function prepareForApproval(LandInfo $landInfo, array $changes): void
    {
        // Store original data for comparison
        $originalData = $landInfo->getOriginal();

        // Create approval request data
        $approvalData = [
            'land_info_id' => $landInfo->id,
            'facility_id' => $landInfo->facility_id,
            'original_data' => json_encode($originalData),
            'proposed_changes' => json_encode($changes),
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ];

        // Log the approval request
        Log::info('Land info approval requested', $approvalData);

        // Send notification to approvers
        $this->notifyApprovers($landInfo, 'land_info_update');
    }

    /**
     * Approve land information changes
     * Requirements: 9.4
     *
     * @param LandInfo $landInfo
     * @param User $approver
     * @return LandInfo
     */
    public function approveLandInfo(LandInfo $landInfo, User $approver): LandInfo
    {
        $landInfo->status = 'approved';
        $landInfo->approved_at = now();
        $landInfo->approved_by = $approver->id;
        $landInfo->save();

        // Clear cache
        $this->clearLandInfoCache($landInfo->facility);

        // Notify the requester
        $this->notifyApprovalComplete($landInfo, $approver);

        return $landInfo;
    }

    /**
     * Reject land information changes
     * Requirements: 9.5
     *
     * @param LandInfo $landInfo
     * @param User $approver
     * @param string $reason
     * @return LandInfo
     */
    public function rejectLandInfo(LandInfo $landInfo, User $approver, string $reason): LandInfo
    {
        $landInfo->status = 'draft';
        $landInfo->approved_at = null;
        $landInfo->approved_by = null;
        $landInfo->save();

        // Clear cache
        $this->clearLandInfoCache($landInfo->facility);

        // Notify the requester with rejection reason
        $this->notifyApprovalRejected($landInfo, $approver, $reason);

        return $landInfo;
    }

    /**
     * Clear land information cache
     *
     * @param Facility $facility
     * @return void
     */
    public function clearLandInfoCache(Facility $facility): void
    {
        Cache::forget("land_info.facility.{$facility->id}");
    }

    /**
     * Sanitize input data (full-width to half-width conversion and security)
     * Requirements: 4.10, Security enhancements
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeInputData(array $data): array
    {
        $numericFields = [
            'parking_spaces',
            'site_area_sqm',
            'site_area_tsubo',
            'purchase_price',
            'monthly_rent'
        ];

        $phoneFields = [
            'management_company_phone',
            'management_company_fax',
            'owner_phone',
            'owner_fax'
        ];

        $postalCodeFields = [
            'management_company_postal_code',
            'owner_postal_code'
        ];

        $textFields = [
            'notes',
            'management_company_notes',
            'owner_notes'
        ];

        $stringFields = [
            'management_company_name',
            'management_company_address',
            'management_company_building',
            'management_company_email',
            'management_company_url',
            'owner_name',
            'owner_address',
            'owner_building',
            'owner_email',
            'owner_url'
        ];

        $sanitized = $data;

        // Convert full-width numbers to half-width for numeric fields
        foreach ($numericFields as $field) {
            if (isset($sanitized[$field]) && !empty($sanitized[$field])) {
                $sanitized[$field] = $this->calculationService->convertToHalfWidth($sanitized[$field]);
                // Remove any non-numeric characters except decimal point
                $sanitized[$field] = preg_replace('/[^0-9.]/', '', $sanitized[$field]);

                // Additional security: prevent extremely large numbers
                if (is_numeric($sanitized[$field])) {
                    $value = (float) $sanitized[$field];
                    if ($value > PHP_INT_MAX || $value < 0) {
                        $sanitized[$field] = null;
                    }
                }
            }
        }

        // Format phone numbers with security checks
        foreach ($phoneFields as $field) {
            if (isset($sanitized[$field]) && !empty($sanitized[$field])) {
                // Remove any non-numeric and non-hyphen characters for security
                $cleaned = preg_replace('/[^0-9\-]/', '', $sanitized[$field]);
                $formatted = $this->calculationService->formatPhoneNumber($cleaned);
                $sanitized[$field] = $formatted;
            }
        }

        // Format postal codes with security checks
        foreach ($postalCodeFields as $field) {
            if (isset($sanitized[$field]) && !empty($sanitized[$field])) {
                // Remove any non-numeric and non-hyphen characters for security
                $cleaned = preg_replace('/[^0-9\-]/', '', $sanitized[$field]);
                $formatted = $this->calculationService->formatPostalCode($cleaned);
                $sanitized[$field] = $formatted;
            }
        }

        // Sanitize text fields with enhanced security
        foreach ($textFields as $field) {
            if (isset($sanitized[$field])) {
                // Strip HTML tags and potentially dangerous content
                $sanitized[$field] = strip_tags($sanitized[$field]);
                // Remove null bytes and control characters
                $sanitized[$field] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $sanitized[$field]);
                // Trim whitespace
                $sanitized[$field] = trim($sanitized[$field]);
            }
        }

        // Sanitize string fields
        foreach ($stringFields as $field) {
            if (isset($sanitized[$field])) {
                // Strip HTML tags
                $sanitized[$field] = strip_tags($sanitized[$field]);
                // Remove null bytes and control characters
                $sanitized[$field] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $sanitized[$field]);
                // Trim whitespace
                $sanitized[$field] = trim($sanitized[$field]);

                // Special handling for email fields
                if (str_contains($field, 'email') && !empty($sanitized[$field])) {
                    $sanitized[$field] = filter_var($sanitized[$field], FILTER_SANITIZE_EMAIL);
                }

                // Special handling for URL fields
                if (str_contains($field, 'url') && !empty($sanitized[$field])) {
                    $sanitized[$field] = filter_var($sanitized[$field], FILTER_SANITIZE_URL);
                }
            }
        }

        // Validate ownership type for security
        if (isset($sanitized['ownership_type'])) {
            $allowedTypes = ['owned', 'leased', 'owned_rental'];
            if (!in_array($sanitized['ownership_type'], $allowedTypes)) {
                $sanitized['ownership_type'] = null;
            }
        }

        // Validate auto_renewal for security
        if (isset($sanitized['auto_renewal'])) {
            $allowedValues = ['yes', 'no'];
            if (!in_array($sanitized['auto_renewal'], $allowedValues)) {
                $sanitized['auto_renewal'] = null;
            }
        }

        return $sanitized;
    }

    /**
     * Perform automatic calculations
     * Requirements: 2.1, 2.2, 2.3, 2.4
     *
     * @param array $data
     * @return array
     */
    protected function performCalculations(array $data): array
    {
        $calculated = [];

        // Calculate unit price per tsubo for owned properties
        if (isset($data['ownership_type']) && $data['ownership_type'] === 'owned') {
            $purchasePrice = (float) ($data['purchase_price'] ?? 0);
            $areaInTsubo = (float) ($data['site_area_tsubo'] ?? 0);

            if ($purchasePrice > 0 && $areaInTsubo > 0) {
                $calculated['unit_price_per_tsubo'] = $this->calculationService->calculateUnitPrice(
                    $purchasePrice,
                    $areaInTsubo
                );
            }
        }

        // Calculate contract period for leased properties
        if (isset($data['ownership_type']) && in_array($data['ownership_type'], ['leased', 'owned_rental'])) {
            $startDate = $data['contract_start_date'] ?? null;
            $endDate = $data['contract_end_date'] ?? null;

            if ($startDate && $endDate) {
                $calculated['contract_period_text'] = $this->calculationService->calculateContractPeriod(
                    $startDate,
                    $endDate
                );
            }
        }

        return $calculated;
    }

    /**
     * Handle approval workflow
     * Requirements: 9.1, 9.2, 9.3
     *
     * @param LandInfo $landInfo
     * @param array $data
     * @param User $user
     * @return LandInfo
     */
    protected function handleApprovalWorkflow(LandInfo $landInfo, array $data, User $user): LandInfo
    {
        $landInfo->fill($data);
        $landInfo->status = 'pending_approval';
        $landInfo->updated_by = $user->id;

        if (!$landInfo->exists) {
            $landInfo->created_by = $user->id;
        }

        $landInfo->save();

        // Prepare for approval and notify approvers
        $this->prepareForApproval($landInfo, $data);

        return $landInfo;
    }

    /**
     * Direct update without approval
     * Requirements: 9.2
     *
     * @param LandInfo $landInfo
     * @param array $data
     * @param User $user
     * @return LandInfo
     */
    protected function directUpdate(LandInfo $landInfo, array $data, User $user): LandInfo
    {
        $landInfo->fill($data);
        $landInfo->status = 'approved';
        $landInfo->approved_at = now();
        $landInfo->updated_by = $user->id;

        if (!$landInfo->exists) {
            $landInfo->created_by = $user->id;
        }

        $landInfo->save();

        return $landInfo;
    }

    /**
     * Check if approval is enabled in system settings
     * Requirements: 9.1
     *
     * @return bool
     */
    protected function isApprovalEnabled(): bool
    {
        return Cache::remember('system_setting.approval_enabled', 3600, function () {
            $setting = DB::table('system_settings')
                ->where('key', 'approval_enabled')
                ->value('value');

            return $setting === 'true';
        });
    }

    /**
     * Notify approvers about land info update request
     * Requirements: 9.1, 9.3
     *
     * @param LandInfo $landInfo
     * @param string $type
     * @return void
     */
    protected function notifyApprovers(LandInfo $landInfo, string $type): void
    {
        // Find users with approver role
        $approvers = User::where('role', 'approver')->get();

        foreach ($approvers as $approver) {
            $this->notificationService->createNotification([
                'user_id' => $approver->id,
                'type' => $type,
                'title' => '土地情報の承認依頼',
                'message' => sprintf(
                    '施設「%s」の土地情報の変更が承認待ちです。',
                    $landInfo->facility->facility_name
                ),
                'data' => [
                    'land_info_id' => $landInfo->id,
                    'facility_id' => $landInfo->facility_id,
                    'requested_by' => $landInfo->updated_by,
                ],
            ]);
        }
    }

    /**
     * Notify approval completion
     * Requirements: 9.4
     *
     * @param LandInfo $landInfo
     * @param User $approver
     * @return void
     */
    protected function notifyApprovalComplete(LandInfo $landInfo, User $approver): void
    {
        $requester = User::find($landInfo->updated_by);

        if ($requester) {
            $this->notificationService->createNotification([
                'user_id' => $requester->id,
                'type' => 'land_info_approved',
                'title' => '土地情報が承認されました',
                'message' => sprintf(
                    '施設「%s」の土地情報の変更が承認されました。',
                    $landInfo->facility->facility_name
                ),
                'data' => [
                    'land_info_id' => $landInfo->id,
                    'facility_id' => $landInfo->facility_id,
                    'approved_by' => $approver->id,
                ],
            ]);
        }
    }

    /**
     * Notify approval rejection
     * Requirements: 9.5
     *
     * @param LandInfo $landInfo
     * @param User $approver
     * @param string $reason
     * @return void
     */
    protected function notifyApprovalRejected(LandInfo $landInfo, User $approver, string $reason): void
    {
        $requester = User::find($landInfo->updated_by);

        if ($requester) {
            $this->notificationService->createNotification([
                'user_id' => $requester->id,
                'type' => 'land_info_rejected',
                'title' => '土地情報の変更が差し戻されました',
                'message' => sprintf(
                    '施設「%s」の土地情報の変更が差し戻されました。理由: %s',
                    $landInfo->facility->facility_name,
                    $reason
                ),
                'data' => [
                    'land_info_id' => $landInfo->id,
                    'facility_id' => $landInfo->facility_id,
                    'rejected_by' => $approver->id,
                    'rejection_reason' => $reason,
                ],
            ]);
        }
    }

    /**
     * Log land information changes for audit purposes
     * Security enhancement for tracking all changes
     *
     * @param LandInfo $landInfo
     * @param array|null $originalData
     * @param array $newData
     * @param User $user
     * @param string $action
     * @return void
     */
    protected function logLandInfoChange(LandInfo $landInfo, ?array $originalData, array $newData, User $user, string $action): void
    {
        $changes = [];

        if ($originalData) {
            // Calculate what changed
            foreach ($newData as $key => $value) {
                $originalValue = $originalData[$key] ?? null;
                if ($originalValue != $value) {
                    $changes[$key] = [
                        'old' => $originalValue,
                        'new' => $value
                    ];
                }
            }
        } else {
            // New record - all fields are changes
            $changes = array_map(function ($value) {
                return ['old' => null, 'new' => $value];
            }, $newData);
        }

        // Log to application log
        Log::info('Land information audit log', [
            'action' => $action,
            'land_info_id' => $landInfo->id,
            'facility_id' => $landInfo->facility_id,
            'facility_name' => $landInfo->facility->facility_name ?? 'Unknown',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'changes' => $changes,
            'status' => $landInfo->status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // Also log to activity log if available
        if (class_exists('\App\Services\ActivityLogService')) {
            try {
                $activityLogService = app(\App\Services\ActivityLogService::class);
                $activityLogService->logFacilityUpdated(
                    $landInfo->facility_id,
                    $landInfo->facility->facility_name . ' - 土地情報' . ($action === 'create' ? '作成' : '更新'),
                    request()
                );
            } catch (Exception $e) {
                Log::warning('Failed to log to activity log service', [
                    'error' => $e->getMessage(),
                    'land_info_id' => $landInfo->id
                ]);
            }
        }
    }
}
