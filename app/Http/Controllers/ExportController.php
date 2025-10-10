<?php

namespace App\Http\Controllers;

use App\Http\Traits\HandlesControllerErrors;
use App\Models\ExportFavorite;
use App\Models\Facility;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    use HandlesControllerErrors;

    protected ExportService $exportService;

    // Configuration constants
    private const CHUNK_SIZE = 100;
    private const MAX_FILE_SIZE_KB = 10240; // 10MB
    private const CACHE_DURATION_MINUTES = 5;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    // ========================================
    // PDF Export Methods (from PdfExportController)
    // ========================================

    /**
     * Display PDF export page
     */
    public function pdfIndex()
    {
        try {
            // Get facilities based on user permissions
            $facilities = $this->getFacilitiesForUser();

            return view('export.pdf.index', compact('facilities'));
        } catch (\Exception $e) {
            return $this->handleException($e, 'PDF export index');
        }
    }

    /**
     * Generate PDF for a single facility
     */
    public function generateSinglePdf(Request $request, Facility $facility)
    {
        try {
            // Check if user has permission to view this facility
            if (! $this->canViewFacility($facility)) {
                abort(403, 'この施設の情報を閲覧する権限がありません。');
            }

            // Only export approved information
            if (! $facility->isApproved()) {
                abort(403, '承認済みの施設情報のみPDF出力可能です。');
            }

            // Check if secure PDF is requested
            $useSecure = $request->get('secure', true);

            if ($useSecure) {
                return $this->generateSecurePdf($facility);
            }

            $pdfContent = $this->exportService->generateFacilityPdf($facility);
            $filename = $this->exportService->generateSecureFilename($facility);

            // Log PDF export (simplified)
            \Log::info('PDF exported', ['facility_id' => $facility->id, 'user_id' => Auth::id()]);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Single PDF generation');
        }
    }

    /**
     * Generate secure PDF for a single facility
     */
    public function generateSecurePdf(Facility $facility)
    {
        try {
            // Check if user has permission to view this facility
            if (! $this->canViewFacility($facility)) {
                abort(403, 'この施設の情報を閲覧する権限がありません。');
            }

            // Only export approved information
            if (! $facility->isApproved()) {
                abort(403, '承認済みの施設情報のみPDF出力可能です。');
            }

            // Generate secure PDF using ExportService
            $pdfContent = $this->exportService->generateSecureFacilityPdf($facility);
            $filename = $this->exportService->generateSecureFilename($facility);

            // Log PDF export (simplified)
            \Log::info('Secure PDF exported', ['facility_id' => $facility->id, 'user_id' => Auth::id()]);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Secure PDF generation');
        }
    }

    /**
     * Generate PDF for multiple facilities
     */
    public function generateBatchPdf(Request $request)
    {
        try {
            $facilityIds = $request->input('facility_ids', []);
            $useSecure = $request->input('secure', true);

            if (empty($facilityIds)) {
                return back()->with('error', '出力する施設を選択してください。');
            }

            $facilities = Facility::whereIn('id', $facilityIds)
                ->approved()
                ->with('landInfo')
                ->get();

            // Filter facilities based on user permissions
            $facilities = $facilities->filter(function ($facility) {
                return $this->canViewFacility($facility);
            });

            if ($facilities->isEmpty()) {
                return back()->with('error', '出力可能な施設がありません。');
            }

            if ($facilities->count() === 1) {
                // Single facility - direct PDF download
                $facility = $facilities->first();

                if ($useSecure) {
                    return $this->generateSecurePdf($facility);
                }

                $pdfContent = $this->exportService->generateFacilityPdf($facility);
                $filename = $this->exportService->generateSecureFilename($facility);

                // Log single facility PDF export
                \Log::info('Single facility PDF exported', ['facility_id' => $facility->id, 'user_id' => Auth::id()]);

                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
            }

            // Multiple facilities - use ExportService for batch generation
            $options = ['secure' => $useSecure];
            $result = $this->exportService->generateBatchPdf($facilities, $options);

            if ($result['success']) {
                return response()->download($result['zip_path'], $result['zip_filename'])->deleteFileAfterSend(true);
            } else {
                return back()->with('error', 'バッチPDF生成に失敗しました: '.$result['error']);
            }
        } catch (\Exception $e) {
            return $this->handleException($e, 'Batch PDF generation');
        }
    }

    /**
     * Get batch progress (AJAX endpoint)
     */
    public function getBatchProgress(Request $request, string $batchId)
    {
        try {
            $progress = $this->exportService->getBatchProgress($batchId);

            return response()->json([
                'success' => true,
                'data' => $progress,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'バッチ進捗の取得に失敗しました。',
            ], 500);
        }
    }

    // ========================================
    // CSV Export Methods (from CsvExportController)
    // ========================================

    /**
     * Display the CSV export menu
     */
    public function csvIndex()
    {
        // Ensure no PHP errors/warnings are displayed that could cause Quirks Mode
        $originalDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        try {
            $user = Auth::user();

            // Get facilities based on user role and permissions
            $facilities = $this->getFacilitiesForUser($user);

            // Get available export fields from ExportService
            $availableFields = $this->exportService->getAvailableFields();

            // Ensure facilities is always an array/collection
            if (!$facilities) {
                $facilities = collect();
            }

            // Get unique sections (departments) for filter dropdown
            $rawSections = \DB::table('facility_basics')
                ->select('section')
                ->whereNotNull('section')
                ->where('section', '!=', '')
                ->distinct()
                ->orderBy('section')
                ->pluck('section');

            // Process sections to combine 有料老人ホーム and グループホーム
            $sections = collect();
            $hasYuryoRojinhome = false;
            $hasGroupHome = false;

            foreach ($rawSections as $section) {
                if ($section === '有料老人ホーム') {
                    $hasYuryoRojinhome = true;
                } elseif ($section === 'グループホーム') {
                    $hasGroupHome = true;
                } else {
                    $sections->push($section);
                }
            }

            // Add combined option if both exist
            if ($hasYuryoRojinhome && $hasGroupHome) {
                $sections->push('有料老人ホーム・グループホーム');
            } elseif ($hasYuryoRojinhome) {
                $sections->push('有料老人ホーム');
            } elseif ($hasGroupHome) {
                $sections->push('グループホーム');
            }

            // Define custom section order
            $sectionOrder = [
                '有料老人ホーム・グループホーム',
                'デイサービスセンター',
                '訪問看護ステーション',
                'ヘルパーステーション',
                'ケアプランセンター',
                '他（事務所など）',
            ];

            // Sort sections according to custom order
            $sections = $sections->sort(function ($a, $b) use ($sectionOrder) {
                $posA = array_search($a, $sectionOrder);
                $posB = array_search($b, $sectionOrder);

                if ($posA !== false && $posB !== false) {
                    return $posA - $posB;
                }
                if ($posA !== false) {
                    return -1;
                }
                if ($posB !== false) {
                    return 1;
                }
                return strcmp($a, $b);
            })->values();

            // Get prefectures that have facilities (standard 47 prefectures only)
            $allPrefectures = config('prefectures.codes');
            $prefectures = collect($allPrefectures)
                ->filter(function ($prefecture, $code) {
                    return strlen($code) === 2 &&
                           intval($code) >= 1 &&
                           intval($code) <= 47 &&
                           Facility::where('office_code', 'like', $code.'%')->exists();
                })
                ->sort();

            return view('export.csv.index', compact('facilities', 'availableFields', 'sections', 'prefectures'));
        } catch (\Exception $e) {
            return $this->handleException($e, 'CSV export index');
        } finally {
            // Restore original display_errors setting
            ini_set('display_errors', $originalDisplayErrors);
        }
    }

    /**
     * Generate and download CSV file
     */
    public function generateCsv(Request $request)
    {
        \Log::info('CSV generation started', [
            'user_id' => Auth::id(),
            'facility_ids' => $request->input('facility_ids', []),
            'export_fields' => $request->input('export_fields', []),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $facilityIds = $request->input('facility_ids', []);
            $exportFields = $request->input('export_fields', []);

            if (empty($facilityIds) || empty($exportFields)) {
                \Log::warning('CSV generation failed: empty input', [
                    'facility_ids_count' => count($facilityIds),
                    'export_fields_count' => count($exportFields)
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '施設または項目が選択されていません。',
                    ], 400);
                }
                return back()->with('error', '施設または項目が選択されていません。');
            }

            $user = Auth::user();

            // Get facilities that user has access to
            $accessibleFacilityIds = $this->getFacilitiesQuery($user)
                ->whereIn('id', $facilityIds)
                ->pluck('id')
                ->toArray();

            \Log::info('Accessible facilities found', [
                'requested_count' => count($facilityIds),
                'accessible_count' => count($accessibleFacilityIds),
                'accessible_ids' => $accessibleFacilityIds
            ]);

            if (empty($accessibleFacilityIds)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '出力可能な施設がありません。',
                    ], 400);
                }
                return back()->with('error', '出力可能な施設がありません。');
            }

            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "facility_export_{$timestamp}.csv";

            \Log::info('Starting CSV stream download', [
                'filename' => $filename,
                'facility_count' => count($accessibleFacilityIds),
                'field_count' => count($exportFields)
            ]);

            // Use streamDownload to prevent any HTML output and ensure proper CSV delivery
            return response()->streamDownload(function () use ($accessibleFacilityIds, $exportFields) {
                $output = fopen('php://output', 'w');
                
                // Add BOM for Excel compatibility
                fwrite($output, "\xEF\xBB\xBF");
                
                // Generate and stream CSV content using ExportService
                $this->exportService->streamCsv($output, $accessibleFacilityIds, $exportFields);
                
                fclose($output);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            \Log::error('CSV generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'facility_ids' => $request->input('facility_ids', []),
                'export_fields' => $request->input('export_fields', [])
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV生成に失敗しました: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'CSV生成に失敗しました: ' . $e->getMessage());
        }
    }

    // ========================================
    // Favorites Methods (from CsvExportController)
    // ========================================

    /**
     * Get user's export favorites
     */
    public function getFavorites()
    {
        try {
            $user = Auth::user();

            $favorites = ExportFavorite::where('user_id', $user->id)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $favorites,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * Save export settings as favorite
     */
    public function saveFavorite(Request $request)
    {
        try {
            $user = Auth::user();
            $name = $request->input('name');
            $facilityIds = $request->input('facility_ids', []);
            $exportFields = $request->input('export_fields', []);

            if (empty($name) || empty($facilityIds) || empty($exportFields)) {
                return response()->json([
                    'success' => false,
                    'message' => '名前、施設、出力項目を指定してください。',
                ], 400);
            }

            // Check if name already exists for this user
            $existingFavorite = ExportFavorite::where('user_id', $user->id)
                ->where('name', $name)
                ->first();

            if ($existingFavorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'この名前のお気に入りは既に存在します。',
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
                    'message' => 'アクセス権限のない施設が含まれています。',
                ], 400);
            }

            $favorite = ExportFavorite::create([
                'user_id' => $user->id,
                'name' => $name,
                'type' => 'csv',
                'facility_ids' => $facilityIds,
                'export_fields' => $exportFields,
            ]);

            return response()->json([
                'success' => true,
                'data' => $favorite,
                'message' => 'お気に入りを保存しました。',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの保存に失敗しました。',
            ], 500);
        }
    }

    /**
     * Load favorite settings
     */
    public function loadFavorite($id)
    {
        try {
            $user = Auth::user();

            $favorite = ExportFavorite::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (! $favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりません。',
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
                    'accessible_facility_count' => count($validFacilityIds),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの読み込みに失敗しました。',
            ], 500);
        }
    }

    /**
     * Update favorite name
     */
    public function updateFavorite(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $name = $request->input('name');

            if (empty($name)) {
                return response()->json([
                    'success' => false,
                    'message' => '名前を入力してください。',
                ], 400);
            }

            $favorite = ExportFavorite::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (! $favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりません。',
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
                    'message' => 'この名前のお気に入りは既に存在します。',
                ], 400);
            }

            $favorite->update(['name' => $name]);

            return response()->json([
                'success' => true,
                'data' => $favorite,
                'message' => 'お気に入り名を更新しました。',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの更新に失敗しました。',
            ], 500);
        }
    }

    /**
     * Delete favorite
     */
    public function deleteFavorite($id)
    {
        try {
            $user = Auth::user();

            $favorite = ExportFavorite::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (! $favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'お気に入りが見つかりません。',
                ], 404);
            }

            $favorite->delete();

            return response()->json([
                'success' => true,
                'message' => 'お気に入りを削除しました。',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * Display favorites management page
     */
    public function favoritesIndex()
    {
        try {
            $user = Auth::user();

            // Get statistics
            $stats = [
                'total_favorites' => ExportFavorite::where('user_id', $user->id)->count(),
                'csv_favorites' => ExportFavorite::where('user_id', $user->id)->where('type', 'csv')->count(),
                'pdf_favorites' => ExportFavorite::where('user_id', $user->id)->where('type', 'pdf')->count(),
                'recent_used' => ExportFavorite::where('user_id', $user->id)
                    ->where('last_used_at', '>=', now()->subDays(30))
                    ->count(),
            ];

            return view('export.favorites.index', compact('stats'));
        } catch (\Exception $e) {
            return $this->handleException($e, 'Favorites index');
        }
    }

    /**
     * Get favorites API endpoint for AJAX
     */
    public function getFavoritesApi()
    {
        try {
            $user = Auth::user();

            $favorites = ExportFavorite::where('user_id', $user->id)
                ->orderBy('name')
                ->get()
                ->map(function ($favorite) {
                    return [
                        'id' => $favorite->id,
                        'name' => $favorite->name,
                        'type' => $favorite->type,
                        'description' => $favorite->options['description'] ?? null,
                        'facility_count' => count($favorite->facility_ids),
                        'field_count' => count($favorite->export_fields),
                        'created_at' => $favorite->created_at,
                        'updated_at' => $favorite->updated_at,
                        'last_used_at' => $favorite->options['last_used_at'] ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $favorites,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'お気に入りの取得に失敗しました。',
            ], 500);
        }
    }

    // ========================================
    // Private Helper Methods
    // ========================================

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
     * Get facilities based on user permissions
     */
    private function getFacilitiesForUser(?User $user = null)
    {
        $user = $user ?? Auth::user();

        return $this->getFacilitiesQuery($user)->get();
    }

    /**
     * Check if user can view a specific facility
     */
    private function canViewFacility(Facility $facility): bool
    {
        // For now, allow all authenticated users to view approved facilities
        // This will be enhanced with proper permission checking in later tasks
        return true; // Allow access for testing, will be restricted to approved facilities in the generate methods
    }
}
