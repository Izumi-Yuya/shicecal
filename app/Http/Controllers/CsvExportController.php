<?php

namespace App\Http\Controllers;

use App\Models\ExportFavorite;
use App\Models\Facility;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CsvExportController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }
    /**
     * Display the CSV export menu
     */
    public function index()
    {
        $user = Auth::user();

        // Get facilities based on user role and permissions
        $facilities = $this->getFacilitiesForUser($user);

        // Get available export fields
        $availableFields = $this->getAvailableFields();

        return view('export.csv.index', compact('facilities', 'availableFields'));
    }

    /**
     * Get field preview data for selected facilities and fields
     */
    public function getFieldPreview(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);

        if (empty($facilityIds) || empty($exportFields)) {
            return response()->json([
                'success' => false,
                'message' => '施設または項目が選択されていません。'
            ]);
        }

        $user = Auth::user();

        // Get facilities that user has access to
        $facilities = $this->getFacilitiesQuery($user)
            ->whereIn('id', $facilityIds)
            ->take(3) // Limit preview to 3 facilities
            ->get();

        // Get available fields
        $availableFields = $this->getAvailableFields();

        // Filter to only requested fields
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));

        // Generate preview data
        $previewData = [];
        foreach ($facilities as $facility) {
            $row = [];
            foreach ($exportFields as $field) {
                $row[$field] = $this->getFieldValue($facility, $field);
            }
            $previewData[] = $row;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'fields' => $selectedFields,
                'preview_data' => $previewData,
                'total_facilities' => count($facilityIds),
                'preview_count' => count($previewData)
            ]
        ]);
    }

    /**
     * Generate and download CSV file
     */
    public function generateCsv(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);

        if (empty($facilityIds) || empty($exportFields)) {
            return response()->json([
                'success' => false,
                'message' => '施設または項目が選択されていません。'
            ], 400);
        }

        $user = Auth::user();

        // Get facilities that user has access to
        $facilities = $this->getFacilitiesQuery($user)
            ->whereIn('id', $facilityIds)
            ->get();

        if ($facilities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '出力可能な施設がありません。'
            ], 400);
        }

        // Get available fields
        $availableFields = $this->getAvailableFields();

        // Filter to only requested fields
        $selectedFields = array_intersect_key($availableFields, array_flip($exportFields));

        // Generate CSV content
        $csvContent = $this->generateCsvContent($facilities, $exportFields, $selectedFields);

        // Log CSV export
        $this->activityLogService->logCsvExported($facilityIds, $exportFields, $request);

        // Generate filename with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "facility_export_{$timestamp}.csv";

        // Return CSV file as download
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($csvContent));
    }

    /**
     * Optimized CSV generation for large datasets
     */
    public function generateCsvOptimized(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);
        $batchSize = 1000;

        if (empty($facilityIds) || empty($exportFields)) {
            return response()->json([
                'success' => false,
                'message' => '施設または項目が選択されていません。'
            ], 400);
        }

        // For large exports, use streaming
        if (count($facilityIds) > 5000) {
            return $this->streamLargeExport($request);
        }

        $user = Auth::user();

        // Warm up cache for land info if needed
        $landInfoFields = array_filter($exportFields, fn($field) => str_starts_with($field, 'land_'));
        if (!empty($landInfoFields)) {
            app(LandInfoService::class)->warmUpCache($facilityIds);
        }

        $filename = 'facilities_export_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($user, $facilityIds, $exportFields, $batchSize) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            $availableFields = $this->getAvailableFields();
            $headers = [];
            foreach ($exportFields as $field) {
                $headers[] = $availableFields[$field] ?? $field;
            }
            fputcsv($handle, $headers);

            // Process in batches
            $query = $this->getFacilitiesQuery($user)
                ->whereIn('id', $facilityIds)
                ->with(['landInfo']);

            $query->chunk($batchSize, function ($facilities) use ($handle, $exportFields) {
                foreach ($facilities as $facility) {
                    $row = [];
                    foreach ($exportFields as $field) {
                        $row[] = $this->getFieldValueOptimized($facility, $field);
                    }
                    fputcsv($handle, $row);
                }

                // Memory management
                if (memory_get_usage() > 100 * 1024 * 1024) {
                    gc_collect_cycles();
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8'
        ]);
    }

    /**
     * Stream large export with progress tracking
     */
    public function streamLargeExport(Request $request)
    {
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);
        $jobId = uniqid('export_');

        // Store job info
        Cache::put("export_job_{$jobId}", [
            'status' => 'processing',
            'total' => count($facilityIds),
            'processed' => 0,
            'started_at' => now()
        ], 3600);

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
            'download_url' => route('csv.download', $jobId)
        ]);
    }

    /**
     * Optimized field value extraction
     */
    protected function getFieldValueOptimized($facility, $field)
    {
        // Use cached data for land info fields
        if (str_starts_with($field, 'land_')) {
            $landInfoService = app(LandInfoService::class);
            $exportData = $landInfoService->getExportDataWithCache($facility);
            if ($exportData && isset($exportData[$field])) {
                return (string) $exportData[$field];
            }
        }

        // Fallback to existing method
        return $this->getFieldValue($facility, $field);
    }

    /**
     * Generate CSV content with UTF-8 BOM
     */
    private function generateCsvContent($facilities, $exportFields, $selectedFields)
    {
        // Start with UTF-8 BOM to prevent character encoding issues
        $csvContent = "\xEF\xBB\xBF";

        // Create CSV header
        $header = array_values($selectedFields);
        $csvContent .= $this->arrayToCsvLine($header);

        // Add data rows
        foreach ($facilities as $facility) {
            $row = [];
            foreach ($exportFields as $field) {
                $row[] = $this->getFieldValue($facility, $field);
            }
            $csvContent .= $this->arrayToCsvLine($row);
        }

        return $csvContent;
    }

    /**
     * Convert array to CSV line
     */
    private function arrayToCsvLine(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $data);
        rewind($output);
        $line = fgets($output);
        fclose($output);

        return $line;
    }

    /**
     * Get user's export favorites
     */
    public function getFavorites()
    {
        $user = Auth::user();

        $favorites = ExportFavorite::where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Save export settings as favorite
     */
    public function saveFavorite(Request $request)
    {
        $user = Auth::user();
        $name = $request->input('name');
        $facilityIds = $request->input('facility_ids', []);
        $exportFields = $request->input('export_fields', []);

        if (empty($name) || empty($facilityIds) || empty($exportFields)) {
            return response()->json([
                'success' => false,
                'message' => '名前、施設、出力項目を指定してください。'
            ], 400);
        }

        // Check if name already exists for this user
        $existingFavorite = ExportFavorite::where('user_id', $user->id)
            ->where('name', $name)
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'この名前のお気に入りは既に存在します。'
            ], 400);
        }

        // Validate that user has access to selected facilities
        $accessibleFacilities = $this->getFacilitiesQuery($user)
            ->whereIn('id', $facilityIds)
            ->pluck('id')
            ->toArray();

        if (count($accessibleFacilities) !== count($facilityIds)) {
            return response()->json([
                'success' => false,
                'message' => 'アクセス権限のない施設が含まれています。'
            ], 400);
        }

        $favorite = ExportFavorite::create([
            'user_id' => $user->id,
            'name' => $name,
            'facility_ids' => $facilityIds,
            'export_fields' => $exportFields,
        ]);

        return response()->json([
            'success' => true,
            'data' => $favorite,
            'message' => 'お気に入りを保存しました。'
        ]);
    }

    /**
     * Load favorite settings
     */
    public function loadFavorite($id)
    {
        $user = Auth::user();

        $favorite = ExportFavorite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        // Validate that user still has access to the facilities
        $accessibleFacilities = $this->getFacilitiesQuery($user)
            ->whereIn('id', $favorite->facility_ids)
            ->pluck('id')
            ->toArray();

        // Filter out facilities that are no longer accessible
        $validFacilityIds = array_intersect($favorite->facility_ids, $accessibleFacilities);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $favorite->id,
                'name' => $favorite->name,
                'facility_ids' => $validFacilityIds,
                'export_fields' => $favorite->export_fields,
                'original_facility_count' => count($favorite->facility_ids),
                'accessible_facility_count' => count($validFacilityIds)
            ]
        ]);
    }

    /**
     * Update favorite name
     */
    public function updateFavorite(Request $request, $id)
    {
        $user = Auth::user();
        $name = $request->input('name');

        if (empty($name)) {
            return response()->json([
                'success' => false,
                'message' => '名前を入力してください。'
            ], 400);
        }

        $favorite = ExportFavorite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        // Check if name already exists for this user (excluding current favorite)
        $existingFavorite = ExportFavorite::where('user_id', $user->id)
            ->where('name', $name)
            ->where('id', '!=', $id)
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'この名前のお気に入りは既に存在します。'
            ], 400);
        }

        $favorite->update(['name' => $name]);

        return response()->json([
            'success' => true,
            'data' => $favorite,
            'message' => 'お気に入り名を更新しました。'
        ]);
    }

    /**
     * Delete favorite
     */
    public function deleteFavorite($id)
    {
        $user = Auth::user();

        $favorite = ExportFavorite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りが見つかりません。'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'お気に入りを削除しました。'
        ]);
    }

    /**
     * Get facilities query based on user role and access scope
     */
    private function getFacilitiesQuery(User $user)
    {
        $query = Facility::approved()->with('landInfo'); // Include land info for export

        switch ($user->role) {
            case 'admin':
            case 'editor':
                // Admin and editor can see all facilities
                break;

            case 'viewer':
                // Viewer role - check access scope
                if (isset($user->access_scope['type'])) {
                    switch ($user->access_scope['type']) {
                        case 'department':
                            // Department manager - filter by department
                            if (isset($user->access_scope['departments'])) {
                                $query->whereIn('department', $user->access_scope['departments']);
                            }
                            break;

                        case 'region':
                            // Regional manager - filter by region/prefecture
                            if (isset($user->access_scope['regions'])) {
                                $query->where(function ($q) use ($user) {
                                    foreach ($user->access_scope['regions'] as $region) {
                                        $q->orWhere('address', 'like', "%{$region}%");
                                    }
                                });
                            }
                            break;

                        case 'facility':
                            // Facility specific - only their own facility
                            if (isset($user->access_scope['facility_ids'])) {
                                $query->whereIn('id', $user->access_scope['facility_ids']);
                            }
                            break;
                    }
                }
                break;

            default:
                // Other roles get no facilities by default
                $query->whereRaw('1 = 0');
                break;
        }

        return $query->orderBy('company_name')
            ->orderBy('facility_name');
    }

    /**
     * Get facilities based on user role and access scope
     */
    private function getFacilitiesForUser(User $user)
    {
        return $this->getFacilitiesQuery($user)->get();
    }

    /**
     * Get available fields for CSV export
     */
    private function getAvailableFields()
    {
        return array_merge(
            $this->getFacilityFields(),
            $this->getLandInfoFields()
        );
    }

    /**
     * Get facility fields for CSV export
     */
    private function getFacilityFields()
    {
        return [
            'company_name' => '会社名',
            'office_code' => '事業所コード',
            'designation_number' => '指定番号',
            'facility_name' => '施設名',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'phone_number' => '電話番号',
            'fax_number' => 'FAX番号',
            'status' => 'ステータス',
            'approved_at' => '承認日時',
            'created_at' => '作成日時',
            'updated_at' => '更新日時',
        ];
    }

    /**
     * Get land information fields for CSV export
     */
    private function getLandInfoFields()
    {
        return [
            'land_ownership_type' => '土地所有形態',
            'land_parking_spaces' => '敷地内駐車場台数',
            'land_site_area_sqm' => '敷地面積(㎡)',
            'land_site_area_tsubo' => '敷地面積(坪数)',
            'land_purchase_price' => '購入金額',
            'land_unit_price_per_tsubo' => '坪単価',
            'land_monthly_rent' => '家賃',
            'land_contract_start_date' => '契約開始日',
            'land_contract_end_date' => '契約終了日',
            'land_contract_period_text' => '契約年数',
            'land_auto_renewal' => '自動更新の有無',
            'land_management_company_name' => '管理会社名',
            'land_management_company_postal_code' => '管理会社郵便番号',
            'land_management_company_address' => '管理会社住所',
            'land_management_company_building' => '管理会社建物名',
            'land_management_company_phone' => '管理会社電話番号',
            'land_management_company_fax' => '管理会社FAX番号',
            'land_management_company_email' => '管理会社メールアドレス',
            'land_management_company_url' => '管理会社URL',
            'land_management_company_notes' => '管理会社備考',
            'land_owner_name' => 'オーナー名',
            'land_owner_postal_code' => 'オーナー郵便番号',
            'land_owner_address' => 'オーナー住所',
            'land_owner_building' => 'オーナー建物名',
            'land_owner_phone' => 'オーナー電話番号',
            'land_owner_fax' => 'オーナーFAX番号',
            'land_owner_email' => 'オーナーメールアドレス',
            'land_owner_url' => 'オーナーURL',
            'land_owner_notes' => 'オーナー備考',
            'land_notes' => '土地備考',
            'land_status' => '土地情報ステータス',
            'land_approved_at' => '土地情報承認日時',
        ];
    }

    /**
     * Get formatted field value for a facility
     */
    private function getFieldValue(Facility $facility, string $field)
    {
        // Handle land information fields
        if (str_starts_with($field, 'land_')) {
            return $this->getLandInfoFieldValue($facility->landInfo, $field);
        }

        // Handle facility fields
        switch ($field) {
            case 'status':
                return $this->getStatusLabel($facility->status);
            case 'approved_at':
                return $facility->approved_at ? $facility->approved_at->format('Y-m-d H:i:s') : '';
            case 'created_at':
                return $facility->created_at->format('Y-m-d H:i:s');
            case 'updated_at':
                return $facility->updated_at->format('Y-m-d H:i:s');
            default:
                return $facility->{$field} ?? '';
        }
    }

    /**
     * Get formatted land information field value
     */
    private function getLandInfoFieldValue($landInfo, string $field)
    {
        if (!$landInfo) {
            return '';
        }

        $fieldName = str_replace('land_', '', $field);

        switch ($fieldName) {
            case 'ownership_type':
                return $this->getOwnershipTypeLabel($landInfo->ownership_type);
            case 'parking_spaces':
                return $landInfo->parking_spaces ?? '';
            case 'site_area_sqm':
                return $landInfo->site_area_sqm ? number_format($landInfo->site_area_sqm, 2) : '';
            case 'site_area_tsubo':
                return $landInfo->site_area_tsubo ? number_format($landInfo->site_area_tsubo, 2) : '';
            case 'purchase_price':
                return $landInfo->purchase_price ? number_format($landInfo->purchase_price) : '';
            case 'unit_price_per_tsubo':
                return $landInfo->unit_price_per_tsubo ? number_format($landInfo->unit_price_per_tsubo) : '';
            case 'monthly_rent':
                return $landInfo->monthly_rent ? number_format($landInfo->monthly_rent) : '';
            case 'contract_start_date':
                return $landInfo->contract_start_date ? $landInfo->contract_start_date->format('Y/m/d') : '';
            case 'contract_end_date':
                return $landInfo->contract_end_date ? $landInfo->contract_end_date->format('Y/m/d') : '';
            case 'auto_renewal':
                return $this->getAutoRenewalLabel($landInfo->auto_renewal);
            case 'status':
                return $this->getStatusLabel($landInfo->status);
            case 'approved_at':
                return $landInfo->approved_at ? $landInfo->approved_at->format('Y-m-d H:i:s') : '';
            default:
                return $landInfo->{$fieldName} ?? '';
        }
    }

    /**
     * Get status label in Japanese
     */
    private function getStatusLabel(string $status): string
    {
        $statusLabels = [
            'draft' => '下書き',
            'pending_approval' => '承認待ち',
            'approved' => '承認済み',
        ];

        return $statusLabels[$status] ?? $status;
    }

    /**
     * Get ownership type label in Japanese
     */
    private function getOwnershipTypeLabel(?string $ownershipType): string
    {
        if (!$ownershipType) {
            return '';
        }

        $ownershipLabels = [
            'owned' => '自社',
            'leased' => '賃借',
            'owned_rental' => '自社（賃貸）',
        ];

        return $ownershipLabels[$ownershipType] ?? $ownershipType;
    }

    /**
     * Get auto renewal label in Japanese
     */
    private function getAutoRenewalLabel(?string $autoRenewal): string
    {
        if (!$autoRenewal) {
            return '';
        }

        $renewalLabels = [
            'yes' => 'あり',
            'no' => 'なし',
        ];

        return $renewalLabels[$autoRenewal] ?? $autoRenewal;
    }
}
