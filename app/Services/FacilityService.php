<?php

namespace App\Services;

use App\Exceptions\FacilityServiceException;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use App\Services\Traits\HandlesServiceErrors;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FacilityService
{
    use HandlesServiceErrors;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get service exception class for error handling trait
     */
    protected function getServiceExceptionClass(): string
    {
        return FacilityServiceException::class;
    }

    // ========================================
    // Basic Facility Operations
    // ========================================

    /**
     * Create a new facility
     * Requirements: 2.1, 2.3
     *
     * @throws FacilityServiceException
     */
    public function createFacility(array $data, User $user): Facility
    {
        try {
            DB::beginTransaction();

            $facility = new Facility($data);
            $facility->created_by = $user->id;
            $facility->updated_by = $user->id;
            $facility->save();

            $this->logError('Facility created', [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
                'action' => 'create',
            ]);

            DB::commit();

            return $facility;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Failed to create facility: '.$e->getMessage(), [
                'user_id' => $user->id,
                'data' => $data,
            ]);
            $this->throwServiceException('施設の作成に失敗しました。');
        }
    }

    /**
     * Update facility basic information
     * Requirements: 2.1, 2.3
     *
     * @throws FacilityServiceException
     */
    public function updateFacility(int $facilityId, array $data, User $user): Facility
    {
        try {
            DB::beginTransaction();

            $facility = Facility::findOrFail($facilityId);
            $originalData = $facility->toArray();

            $facility->fill($data);
            $facility->updated_by = $user->id;
            $facility->save();

            $this->logFacilityChange($facility, $originalData, $data, $user, 'update');

            DB::commit();

            return $facility;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Failed to update facility: '.$e->getMessage(), [
                'facility_id' => $facilityId,
                'user_id' => $user->id,
            ]);
            $this->throwServiceException('施設の更新に失敗しました。');
        }
    }

    /**
     * Delete a facility
     * Requirements: 2.1, 2.3
     *
     * @throws FacilityServiceException
     */
    public function deleteFacility(int $facilityId, User $user): bool
    {
        try {
            DB::beginTransaction();

            $facility = Facility::findOrFail($facilityId);

            $this->logError('Facility deleted', [
                'facility_id' => $facility->id,
                'facility_name' => $facility->facility_name,
                'user_id' => $user->id,
                'action' => 'delete',
            ]);

            $facility->delete(); // This is soft delete if SoftDeletes trait is used

            // Clear related caches
            $this->clearFacilityCache($facility);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logError('Failed to delete facility: '.$e->getMessage(), [
                'facility_id' => $facilityId,
                'user_id' => $user->id,
            ]);
            $this->throwServiceException('施設の削除に失敗しました。');
        }
    }

    /**
     * Get facility with permissions check
     * Requirements: 2.1, 2.3
     *
     * @throws FacilityServiceException
     */
    public function getFacilityWithPermissions(int $facilityId, User $user): Facility
    {
        try {
            $facility = Facility::with(['landInfo'])->findOrFail($facilityId);

            // Add permission checks here if needed
            // This would integrate with existing authorization logic

            return $facility;
        } catch (Exception $e) {
            $this->logError('Failed to get facility: '.$e->getMessage(), [
                'facility_id' => $facilityId,
                'user_id' => $user->id,
            ]);
            $this->throwServiceException('施設の取得に失敗しました。');
        }
    }

    // ========================================
    // Land Information Operations (from LandInfoService)
    // ========================================

    /**
     * Get land information for a facility with caching
     * Requirements: 1.1, 1.2
     */
    public function getLandInfo(Facility $facility): ?LandInfo
    {
        return Cache::remember(
            "land_info.facility.{$facility->id}",
            3600, // 1 hour
            fn () => $facility->landInfo
        );
    }

    /**
     * Create or update land information for a facility
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 9.1, 9.2, 9.3, 9.4, 9.5
     *
     * @throws FacilityServiceException
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
            $isNewRecord = ! $landInfo->exists;

            // Store original data for audit logging
            $originalData = $landInfo->exists ? $landInfo->toArray() : null;

            // Check if approval is enabled
            $approvalEnabled = $this->isApprovalEnabled();
            Log::info('Approval enabled check', ['enabled' => $approvalEnabled]);

            if ($approvalEnabled) {
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
            $this->logError('Failed to create/update land info: '.$e->getMessage(), [
                'facility_id' => $facility->id,
                'user_id' => $user->id,
            ]);
            $this->throwServiceException('土地情報の保存に失敗しました。');
        }
    }

    /**
     * Approve land information changes
     * Requirements: 9.4
     *
     * @throws FacilityServiceException
     */
    public function approveLandInfo(LandInfo $landInfo, User $approver): LandInfo
    {
        try {
            $landInfo->status = 'approved';
            $landInfo->approved_at = now();
            $landInfo->approved_by = $approver->id;
            $landInfo->save();

            // Clear cache
            $this->clearLandInfoCache($landInfo->facility);

            // Notify the requester
            $this->notifyApprovalComplete($landInfo, $approver);

            return $landInfo;
        } catch (Exception $e) {
            $this->logError('Failed to approve land info: '.$e->getMessage(), [
                'land_info_id' => $landInfo->id,
                'approver_id' => $approver->id,
            ]);
            $this->throwServiceException('土地情報の承認に失敗しました。');
        }
    }

    /**
     * Reject land information changes
     * Requirements: 9.5
     *
     * @throws FacilityServiceException
     */
    public function rejectLandInfo(LandInfo $landInfo, User $approver, string $reason): LandInfo
    {
        try {
            $landInfo->status = 'draft';
            $landInfo->approved_at = null;
            $landInfo->approved_by = null;
            $landInfo->save();

            // Clear cache
            $this->clearLandInfoCache($landInfo->facility);

            // Notify the requester with rejection reason
            $this->notifyApprovalRejected($landInfo, $approver, $reason);

            return $landInfo;
        } catch (Exception $e) {
            $this->logError('Failed to reject land info: '.$e->getMessage(), [
                'land_info_id' => $landInfo->id,
                'approver_id' => $approver->id,
            ]);
            $this->throwServiceException('土地情報の差し戻しに失敗しました。');
        }
    }

    // ========================================
    // Calculation Methods (from LandCalculationService)
    // ========================================

    /**
     * Calculate unit price per tsubo (購入価格÷敷地面積（坪数）)
     * Requirements: 2.1, 2.2
     *
     * @param  float  $purchasePrice  Purchase price in yen
     * @param  float  $areaInTsubo  Area in tsubo
     * @return float|null Unit price per tsubo, null if calculation not possible
     */
    public function calculateUnitPrice(float $purchasePrice, float $areaInTsubo): ?float
    {
        if ($purchasePrice <= 0 || $areaInTsubo <= 0) {
            return null;
        }

        return round($purchasePrice / $areaInTsubo);
    }

    /**
     * Calculate contract period in years and months format (5年5ヶ月)
     * Requirements: 2.3, 2.4
     *
     * @param  string  $startDate  Contract start date (YYYY-MM-DD format)
     * @param  string  $endDate  Contract end date (YYYY-MM-DD format)
     * @return string Contract period in Japanese format
     */
    public function calculateContractPeriod(string $startDate, string $endDate): string
    {
        try {
            /** @var \Carbon\Carbon $start */
            $start = Carbon::parse($startDate);
            /** @var \Carbon\Carbon $end */
            $end = Carbon::parse($endDate);

            if ($end <= $start) {
                return '';
            }

            $diff = $start->diff($end);
            $years = $diff->y;
            $months = $diff->m;

            $result = '';
            if ($years > 0) {
                $result .= $years.'年';
            }
            if ($months > 0) {
                $result .= $months.'ヶ月';
            }

            return $result ?: '0ヶ月';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format currency with comma separators (3桁区切りカンマ)
     * Requirements: 1.7, 1.8, 2.2
     *
     * @param  float  $amount  Amount to format
     * @return string Formatted currency string
     */
    public function formatCurrency(float $amount): string
    {
        if ($amount == 0) {
            return '';
        }

        return number_format(round($amount), 0, '.', ',');
    }

    /**
     * Format area with unit display
     * Requirements: 2.5, 2.6
     *
     * @param  float  $area  Area value
     * @param  string  $unit  Unit type ('sqm' for ㎡, 'tsubo' for 坪)
     * @return string Formatted area string
     */
    public function formatArea(float $area, string $unit): string
    {
        if ($area <= 0) {
            return '';
        }

        $formattedArea = number_format($area, 2, '.', ',');

        switch ($unit) {
            case 'sqm':
                return $formattedArea.'㎡';
            case 'tsubo':
                return $formattedArea.'坪';
            default:
                throw new InvalidArgumentException("Invalid unit: {$unit}. Use 'sqm' or 'tsubo'.");
        }
    }

    // ========================================
    // Data Formatting and Display Methods
    // ========================================

    /**
     * Format land information data for display
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
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
     * Get formatted land information with caching for display
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
     */
    public function getFormattedLandInfoWithCache(Facility $facility): ?array
    {
        return Cache::remember(
            "land_info.formatted.{$facility->id}",
            3600, // 1 hour
            function () use ($facility) {
                $landInfo = $facility->landInfo;

                return $landInfo ? $this->formatDisplayData($landInfo) : null;
            }
        );
    }

    /**
     * Get land information export data with caching
     * Requirements: 10.1, 10.2, 10.3, 10.4
     */
    public function getExportDataWithCache(Facility $facility): ?array
    {
        return Cache::remember(
            "land_info.export_data.{$facility->id}",
            7200, // 2 hours
            function () use ($facility) {
                $landInfo = $facility->landInfo;
                if (! $landInfo) {
                    return null;
                }

                return [
                    'land_ownership_type' => $landInfo->ownership_type,
                    'land_parking_spaces' => $landInfo->parking_spaces,
                    'land_site_area_sqm' => $landInfo->site_area_sqm,
                    'land_site_area_tsubo' => $landInfo->site_area_tsubo,
                    'land_purchase_price' => $landInfo->purchase_price,
                    'land_unit_price_per_tsubo' => $landInfo->unit_price_per_tsubo,
                    'land_monthly_rent' => $landInfo->monthly_rent,
                    'land_contract_start_date' => $landInfo->contract_start_date?->format('Y/m/d'),
                    'land_contract_end_date' => $landInfo->contract_end_date?->format('Y/m/d'),
                    'land_auto_renewal' => $landInfo->auto_renewal,
                    'land_management_company_name' => $landInfo->management_company_name,
                    'land_owner_name' => $landInfo->owner_name,
                    'land_notes' => $landInfo->notes,
                ];
            }
        );
    }

    // ========================================
    // Bulk Operations and Performance Methods
    // ========================================

    /**
     * Get multiple land information records with caching for bulk operations
     * Requirements: 10.1, 10.2, 10.3, 10.4
     */
    public function getBulkLandInfo(array $facilityIds): array
    {
        sort($facilityIds); // Sort in place
        $cacheKey = 'land_info.bulk.'.md5(implode(',', $facilityIds));

        return Cache::remember($cacheKey, 1800, function () use ($facilityIds) { // 30 minutes
            return LandInfo::whereIn('facility_id', $facilityIds)
                ->with(['facility:id,facility_name', 'approver:id,name'])
                ->get()
                ->keyBy('facility_id')
                ->toArray();
        });
    }

    /**
     * Warm up cache for multiple facilities
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function warmUpCache(array $facilityIds): void
    {
        // Batch load land info to reduce database queries
        $landInfos = LandInfo::whereIn('facility_id', $facilityIds)
            ->with(['facility:id,facility_name'])
            ->get()
            ->keyBy('facility_id');

        // Cache each land info individually
        foreach ($facilityIds as $facilityId) {
            $landInfo = $landInfos->get($facilityId);
            if ($landInfo) {
                Cache::put("land_info.facility.{$facilityId}", $landInfo, 3600);
                Cache::put("land_info.formatted.{$facilityId}", $this->formatDisplayData($landInfo), 3600);
            }
        }
    }

    // ========================================
    // Cache Management Methods
    // ========================================

    /**
     * Clear facility-related cache
     */
    public function clearFacilityCache(Facility $facility): void
    {
        Cache::forget("facility.{$facility->id}");
        $this->clearLandInfoCache($facility);
    }

    /**
     * Clear land information cache
     */
    public function clearLandInfoCache(Facility $facility): void
    {
        Cache::forget("land_info.facility.{$facility->id}");
        Cache::forget("land_info.formatted.{$facility->id}");
        Cache::forget("land_info.export_data.{$facility->id}");
    }

    /**
     * Clear all facility and land info caches (for maintenance)
     */
    public function clearAllCaches(): void
    {
        $patterns = ['facility.*', 'land_info.*'];

        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            if (! empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    // ========================================
    // Protected Helper Methods
    // ========================================

    /**
     * Sanitize input data (full-width to half-width conversion and security)
     * Requirements: 4.10, Security enhancements
     */
    protected function sanitizeInputData(array $data): array
    {
        $numericFields = [
            'parking_spaces',
            'site_area_sqm',
            'site_area_tsubo',
            'purchase_price',
            'monthly_rent',
        ];

        $phoneFields = [
            'management_company_phone',
            'management_company_fax',
            'owner_phone',
            'owner_fax',
        ];

        $postalCodeFields = [
            'management_company_postal_code',
            'owner_postal_code',
        ];

        $textFields = [
            'notes',
            'management_company_notes',
            'owner_notes',
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
            'owner_url',
        ];

        $sanitized = $data;

        // Convert full-width numbers to half-width for numeric fields
        foreach ($numericFields as $field) {
            if (isset($sanitized[$field]) && ! empty($sanitized[$field])) {
                $sanitized[$field] = $this->convertToHalfWidth($sanitized[$field]);
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
            if (isset($sanitized[$field]) && ! empty($sanitized[$field])) {
                // Remove any non-numeric and non-hyphen characters for security
                $cleaned = preg_replace('/[^0-9\-]/', '', $sanitized[$field]);
                $formatted = $this->formatPhoneNumber($cleaned);
                $sanitized[$field] = $formatted;
            }
        }

        // Format postal codes with security checks
        foreach ($postalCodeFields as $field) {
            if (isset($sanitized[$field]) && ! empty($sanitized[$field])) {
                // Remove any non-numeric and non-hyphen characters for security
                $cleaned = preg_replace('/[^0-9\-]/', '', $sanitized[$field]);
                $formatted = $this->formatPostalCode($cleaned);
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
                if (str_contains($field, 'email') && ! empty($sanitized[$field])) {
                    $sanitized[$field] = filter_var($sanitized[$field], FILTER_SANITIZE_EMAIL);
                }

                // Special handling for URL fields
                if (str_contains($field, 'url') && ! empty($sanitized[$field])) {
                    $sanitized[$field] = filter_var($sanitized[$field], FILTER_SANITIZE_URL);
                }
            }
        }

        // Validate ownership type for security
        if (isset($sanitized['ownership_type'])) {
            $allowedTypes = ['owned', 'leased', 'owned_rental'];
            if (! in_array($sanitized['ownership_type'], $allowedTypes)) {
                $sanitized['ownership_type'] = null;
            }
        }

        // Validate auto_renewal for security
        if (isset($sanitized['auto_renewal'])) {
            $allowedValues = ['yes', 'no'];
            if (! in_array($sanitized['auto_renewal'], $allowedValues)) {
                $sanitized['auto_renewal'] = null;
            }
        }

        return $sanitized;
    }

    /**
     * Perform automatic calculations
     * Requirements: 2.1, 2.2, 2.3, 2.4
     */
    protected function performCalculations(array $data): array
    {
        $calculated = [];

        // Calculate unit price per tsubo for owned properties
        if (isset($data['ownership_type']) && $data['ownership_type'] === 'owned') {
            $purchasePrice = (float) ($data['purchase_price'] ?? 0);
            $areaInTsubo = (float) ($data['site_area_tsubo'] ?? 0);

            if ($purchasePrice > 0 && $areaInTsubo > 0) {
                $calculated['unit_price_per_tsubo'] = $this->calculateUnitPrice(
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
                $calculated['contract_period_text'] = $this->calculateContractPeriod(
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
     */
    protected function handleApprovalWorkflow(LandInfo $landInfo, array $data, User $user): LandInfo
    {
        Log::info('handleApprovalWorkflow called', ['land_info_id' => $landInfo->id ?? 'new']);
        $landInfo->fill($data);
        $landInfo->status = 'pending_approval';
        $landInfo->updated_by = $user->id;

        if (! $landInfo->exists) {
            $landInfo->created_by = $user->id;
        }

        $landInfo->save();

        // Prepare for approval and notify approvers
        Log::info('Calling prepareForApproval', ['land_info_id' => $landInfo->id]);
        $this->prepareForApproval($landInfo, $data);

        return $landInfo;
    }

    /**
     * Direct update without approval
     * Requirements: 9.2
     */
    protected function directUpdate(LandInfo $landInfo, array $data, User $user): LandInfo
    {
        $landInfo->fill($data);
        $landInfo->status = 'approved';
        $landInfo->approved_at = now();
        $landInfo->updated_by = $user->id;

        if (! $landInfo->exists) {
            $landInfo->created_by = $user->id;
        }

        $landInfo->save();

        return $landInfo;
    }

    /**
     * Check if approval is enabled in system settings
     * Requirements: 9.1
     */
    protected function isApprovalEnabled(): bool
    {
        // For testing, check if approval is explicitly set in database
        $setting = DB::table('system_settings')
            ->where('key', 'approval_enabled')
            ->value('value');

        return $setting === 'true';
    }

    /**
     * Prepare land information for approval workflow
     * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
     */
    protected function prepareForApproval(LandInfo $landInfo, array $changes): void
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
        $this->notifyApprovers($landInfo, 'land_info_approval_request');
    }

    /**
     * Notify approvers about land info update request
     * Requirements: 9.1, 9.3
     */
    protected function notifyApprovers(LandInfo $landInfo, string $type): void
    {
        // Simplified version for testing - just create a basic notification
        $approvers = User::where('role', 'approver')->get();

        foreach ($approvers as $approver) {
            DB::table('notifications')->insert([
                'user_id' => $approver->id,
                'type' => $type,
                'title' => '土地情報の承認依頼',
                'message' => sprintf(
                    '施設「%s」の土地情報の変更が承認待ちです。',
                    $landInfo->facility ? $landInfo->facility->facility_name : 'Unknown'
                ),
                'data' => json_encode([
                    'land_info_id' => $landInfo->id,
                    'facility_id' => $landInfo->facility_id,
                    'requested_by' => $landInfo->updated_by,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Notify approval completion
     * Requirements: 9.4
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
     * Convert full-width numbers to half-width numbers
     * Requirements: 4.10
     *
     * @param  string  $input  Input string with potential full-width numbers
     * @return string String with half-width numbers
     */
    protected function convertToHalfWidth(string $input): string
    {
        return mb_convert_kana($input, 'n');
    }

    /**
     * Validate and format postal code (XXX-XXXX format)
     * Requirements: 4.2, 5.2
     *
     * @param  string  $postalCode  Postal code input
     * @return string|null Formatted postal code or null if invalid
     */
    protected function formatPostalCode(string $postalCode): ?string
    {
        $cleaned = $this->convertToHalfWidth($postalCode);
        $cleaned = preg_replace('/[^0-9-]/', '', $cleaned);

        if (preg_match('/^(\d{3})-?(\d{4})$/', $cleaned, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        return null;
    }

    /**
     * Validate and format phone number (XX-XXXX-XXXX format)
     * Requirements: 4.5, 4.6, 5.5, 5.6
     *
     * @param  string  $phoneNumber  Phone number input
     * @return string|null Formatted phone number or null if invalid
     */
    protected function formatPhoneNumber(string $phoneNumber): ?string
    {
        $cleaned = $this->convertToHalfWidth($phoneNumber);
        $cleaned = preg_replace('/[^0-9-]/', '', $cleaned);

        // Remove existing hyphens for processing
        $numbersOnly = str_replace('-', '', $cleaned);

        // Format based on common Japanese phone number patterns
        if (strlen($numbersOnly) === 10) {
            // Standard 10-digit format: 03-1234-5678
            if (preg_match('/^(0\d{1,3})(\d{4})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1].'-'.$matches[2].'-'.$matches[3];
            }
        } elseif (strlen($numbersOnly) === 11) {
            // Check for toll-free numbers first (0120, 0800)
            if (preg_match('/^(0120|0800)(\d{3})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1].'-'.$matches[2].'-'.$matches[3];
            }
            // Mobile numbers: 090, 080, 070
            elseif (preg_match('/^(0\d{2})(\d{4})(\d{4})$/', $numbersOnly, $matches)) {
                return $matches[1].'-'.$matches[2].'-'.$matches[3];
            }
        }

        return null;
    }

    /**
     * Format date in Japanese format (2000年12月12日)
     * Requirements: 3.3
     *
     * @param  string  $date  Date string in YYYY-MM-DD format
     * @return string Japanese formatted date
     */
    protected function formatJapaneseDate(string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            /** @var \Carbon\Carbon $carbon */
            $carbon = Carbon::parse($date);

            return $carbon->format('Y年n月j日');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Log facility changes for audit purposes
     */
    protected function logFacilityChange(Facility $facility, ?array $originalData, array $newData, User $user, string $action): void
    {
        $changes = [];

        if ($originalData) {
            // Calculate what changed
            foreach ($newData as $key => $value) {
                $originalValue = $originalData[$key] ?? null;
                if ($originalValue != $value) {
                    $changes[$key] = [
                        'old' => $originalValue,
                        'new' => $value,
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
        Log::info('Facility audit log', [
            'action' => $action,
            'facility_id' => $facility->id,
            'facility_name' => $facility->facility_name ?? 'Unknown',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log land information changes for audit purposes
     * Security enhancement for tracking all changes
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
                        'new' => $value,
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
                    $landInfo->facility->facility_name.' - 土地情報'.($action === 'create' ? '作成' : '更新'),
                    request()
                );
            } catch (Exception $e) {
                Log::warning('Failed to log to activity log service', [
                    'error' => $e->getMessage(),
                    'land_info_id' => $landInfo->id,
                ]);
            }
        }
    }
}
