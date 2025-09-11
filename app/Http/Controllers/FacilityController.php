<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandInfoRequest;
use App\Models\Facility;
use App\Models\File;
use App\Models\LandInfo;
use App\Services\ActivityLogService;
use App\Services\ExportService;
use App\Services\FacilityService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FacilityController extends Controller
{
    protected ActivityLogService $activityLogService;

    protected FacilityService $facilityService;

    protected ExportService $exportService;

    public function __construct(
        ActivityLogService $activityLogService,
        FacilityService $facilityService,
        ExportService $exportService
    ) {
        $this->activityLogService = $activityLogService;
        $this->facilityService = $facilityService;
        $this->exportService = $exportService;
    }

    // View mode session management constants
    const VIEW_PREFERENCE_KEY = 'facility_basic_info_view_mode';

    const VIEW_MODES = [
        'card' => 'カード形式',
        'table' => 'テーブル形式',
    ];

    /**
     * Set view mode preference via AJAX
     */
    public function setViewMode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view_mode' => 'required|in:card,table',
        ]);

        // Store view preference in session
        session([self::VIEW_PREFERENCE_KEY => $validated['view_mode']]);

        return response()->json([
            'success' => true,
            'view_mode' => $validated['view_mode'],
            'message' => '表示形式を変更しました。',
        ]);
    }

    /**
     * Get current view mode preference with 'card' as default
     */
    public function getViewMode(): string
    {
        return session(self::VIEW_PREFERENCE_KEY, 'card');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $facilities = Facility::with(['creator', 'updater'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('facilities.index', compact('facilities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('facilities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'service_types' => 'nullable|array',
            'designation_renewal_date' => 'nullable|date',
        ]);

        $facilityData = array_merge($validated, [
            'status' => 'approved', // For now, directly approve
        ]);

        $facility = $this->facilityService->createFacility($facilityData, auth()->user());

        // Log facility creation
        $this->activityLogService->logFacilityCreated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Facility $facility)
    {
        $facility->load([
            'services',
            'comments.poster',
            'maintenanceHistories' => function ($query) {
                $query->with('creator')->latest('maintenance_date');
            },
            'landInfo',
        ]);

        $landInfo = $facility->landInfo;
        $viewMode = $this->getViewMode();

        return view('facilities.show', compact('facility', 'landInfo', 'viewMode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Facility $facility)
    {
        return view('facilities.edit', compact('facility'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'service_types' => 'nullable|array',
            'designation_renewal_date' => 'nullable|date',
        ]);

        $facility = $this->facilityService->updateFacility($facility->id, $validated, auth()->user());

        // Log facility update
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Facility $facility)
    {
        $facilityName = $facility->facility_name;
        $facilityId = $facility->id;

        $this->facilityService->deleteFacility($facility->id, auth()->user());

        // Log facility deletion
        $this->activityLogService->logFacilityDeleted(
            $facilityId,
            $facilityName,
            request()
        );

        return redirect()->route('facilities.index')
            ->with('success', '施設を削除しました。');
    }

    /**
     * Display the basic information of the facility.
     */
    public function basicInfo(Facility $facility)
    {
        return view('facilities.basic-info.show', compact('facility'));
    }

    /**
     * Show the form for editing basic information.
     */
    public function editBasicInfo(Facility $facility)
    {
        $facility->load('services');

        return view('facilities.basic-info.edit', compact('facility'));
    }

    /**
     * Update the basic information of the facility.
     */
    public function updateBasicInfo(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'office_code' => 'required|string|max:20',
            'designation_number' => 'nullable|string|max:50',
            'facility_name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'building_name' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'fax_number' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:500',
            'opening_date' => 'nullable|date',
            'years_in_operation' => 'nullable|integer|min:0',
            'building_structure' => 'nullable|string|max:100',
            'building_floors' => 'nullable|integer|min:1',
            'paid_rooms_count' => 'nullable|integer|min:0',
            'ss_rooms_count' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'services' => 'nullable|array',
            'services.*.service_type' => 'nullable|string|max:255',
            'services.*.renewal_start_date' => 'nullable|date',
            'services.*.renewal_end_date' => 'nullable|date|after_or_equal:services.*.renewal_start_date',
        ]);

        // 基本情報を更新（servicesを除く）
        $basicInfo = collect($validated)->except('services')->toArray();
        $facility = $this->facilityService->updateFacility($facility->id, $basicInfo, auth()->user());

        // サービス情報を更新
        if (isset($validated['services'])) {
            $this->updateFacilityServices($facility, $validated['services']);
        }

        // Log facility basic info update
        $this->activityLogService->logFacilityUpdated(
            $facility->id,
            $facility->facility_name,
            $request
        );

        return redirect()->route('facilities.show', $facility)
            ->with('success', '施設基本情報を更新しました。');
    }

    /**
     * Update facility services
     */
    private function updateFacilityServices(Facility $facility, array $services)
    {
        // 既存のサービス情報を削除
        $facility->services()->delete();

        // 新しいサービス情報を保存（空でない行のみ）
        foreach ($services as $serviceData) {
            if (! empty($serviceData['service_type'])) {
                $facility->services()->create([
                    'service_type' => $serviceData['service_type'],
                    'renewal_start_date' => $serviceData['renewal_start_date'] ?? null,
                    'renewal_end_date' => $serviceData['renewal_end_date'] ?? null,
                ]);
            }
        }
    }

    // ========================================
    // Land Information Methods (from LandInfoController)
    // ========================================

    /**
     * Display the land information for the specified facility.
     */
    public function showLandInfo(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('view', [LandInfo::class, $facility]);

            $landInfo = $this->facilityService->getLandInfo($facility);

            if (! $landInfo) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => '土地情報が登録されていません。',
                ]);
            }

            $formattedData = $this->facilityService->formatDisplayData($landInfo);

            return response()->json([
                'success' => true,
                'data' => $formattedData,
            ]);
        } catch (Exception $e) {
            Log::error('Land info show failed', [
                'facility_id' => $facility->id,
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
     * Show the form for editing land information.
     */
    public function editLandInfo(Facility $facility)
    {
        try {
            // Check authorization using policy
            $this->authorize('update', [LandInfo::class, $facility]);

            $landInfo = $this->facilityService->getLandInfo($facility);

            return view('facilities.land-info.edit', compact('facility', 'landInfo'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の土地情報を編集する権限がありません。');
        } catch (Exception $e) {
            Log::error('Land info edit failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('facilities.show', $facility)
                ->with('error', 'システムエラーが発生しました。');
        }
    }

    /**
     * Update the land information for the specified facility.
     */
    public function updateLandInfo(LandInfoRequest $request, Facility $facility)
    {
        try {
            // Check authorization using policy
            $this->authorize('update', [LandInfo::class, $facility]);

            // Check field-level permissions
            $user = auth()->user();
            $validatedData = $request->validated();

            // Filter data based on user permissions
            $filteredData = $this->filterDataByPermissions($validatedData, $user);

            $landInfo = $this->facilityService->createOrUpdateLandInfo(
                $facility,
                $filteredData,
                $user
            );

            // Handle PDF file uploads if user has permission
            if ($user->canEditLandDocuments()) {
                try {
                    $this->handlePdfUploads($request, $landInfo);
                } catch (\Exception $e) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'ファイルのアップロードに失敗しました: ' . $e->getMessage(),
                        ], 422);
                    }

                    return redirect()->back()
                        ->withErrors(['file_upload' => 'ファイルのアップロードに失敗しました: ' . $e->getMessage()])
                        ->withInput();
                }
            }

            // Log the activity
            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name.' - 土地情報',
                $request
            );

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                $formattedData = $this->facilityService->formatDisplayData($landInfo);

                return response()->json([
                    'success' => true,
                    'message' => '土地情報を更新しました。',
                    'data' => $formattedData,
                ]);
            }

            $redirectUrl = route('facilities.show', $facility) . '#land-info';
            return redirect($redirectUrl)
                ->with('success', '土地情報を更新しました。');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この施設の土地情報を編集する権限がありません。',
                ], 403);
            }

            $redirectUrl = route('facilities.show', $facility) . '#land-info';
            return redirect($redirectUrl)
                ->with('error', 'この施設の土地情報を編集する権限がありません。');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Exception $e) {
            Log::error('Land info update failed', [
                'facility_id' => $facility->id,
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

            return redirect()->back()
                ->with('error', 'システムエラーが発生しました。')
                ->withInput();
        }
    }

    /**
     * Calculate fields for real-time calculations.
     */
    public function calculateLandFields(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'calculation_type' => 'required|in:unit_price,contract_period',
                'purchase_price' => 'nullable|numeric',
                'site_area_tsubo' => 'nullable|numeric',
                'contract_start_date' => 'nullable|date',
                'contract_end_date' => 'nullable|date|after:contract_start_date',
            ]);

            $result = [];

            switch ($validated['calculation_type']) {
                case 'unit_price':
                    if (isset($validated['purchase_price']) && isset($validated['site_area_tsubo'])) {
                        $unitPrice = $this->facilityService->calculateUnitPrice(
                            (float) $validated['purchase_price'],
                            (float) $validated['site_area_tsubo']
                        );

                        $result['unit_price'] = $unitPrice;
                        $result['formatted_unit_price'] = $unitPrice ?
                            $this->facilityService->formatCurrency($unitPrice) : '';
                    }
                    break;

                case 'contract_period':
                    if (isset($validated['contract_start_date']) && isset($validated['contract_end_date'])) {
                        $contractPeriod = $this->facilityService->calculateContractPeriod(
                            $validated['contract_start_date'],
                            $validated['contract_end_date']
                        );

                        $result['contract_period'] = $contractPeriod;
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Land info calculation failed', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '計算処理でエラーが発生しました。',
            ], 500);
        }
    }

    /**
     * Approve pending land information changes.
     */
    public function approveLandInfo(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('approve', [LandInfo::class, $facility]);

            $landInfo = $this->facilityService->getLandInfo($facility);

            if (! $landInfo || $landInfo->status !== 'pending_approval') {
                $message = ! $landInfo ? '承認待ちの土地情報がありません。' : 'この土地情報は既に承認済みです。';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            $this->facilityService->approveLandInfo($landInfo, auth()->user());

            // Send notification to the editor who created/updated the land info
            if ($landInfo->updated_by) {
                DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'land_info_approved',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $landInfo->updated_by,
                    'data' => json_encode([
                        'land_info_id' => $landInfo->id,
                        'facility_id' => $landInfo->facility_id,
                        'approved_by' => auth()->id(),
                        'title' => '土地情報承認完了',
                        'message' => sprintf(
                            '施設「%s」の土地情報が承認されました。',
                            $facility->facility_name
                        ),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Log the approval
            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name.' - 土地情報承認',
                request()
            );

            return response()->json([
                'success' => true,
                'message' => '土地情報を承認しました。',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設の土地情報を承認する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Land info approval failed', [
                'facility_id' => $facility->id,
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
     * Reject pending land information changes.
     */
    public function rejectLandInfo(Request $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('reject', [LandInfo::class, $facility]);

            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:1000',
            ]);

            $landInfo = $this->facilityService->getLandInfo($facility);

            if (! $landInfo || $landInfo->status !== 'pending_approval') {
                return response()->json([
                    'success' => false,
                    'message' => '承認待ちの土地情報がありません。',
                ], 422);
            }

            $this->facilityService->rejectLandInfo($landInfo, auth()->user(), $validated['rejection_reason']);

            // Send notification to the editor who created/updated the land info
            if ($landInfo->updated_by) {
                DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'type' => 'land_info_rejected',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $landInfo->updated_by,
                    'data' => json_encode([
                        'land_info_id' => $landInfo->id,
                        'facility_id' => $landInfo->facility_id,
                        'rejected_by' => auth()->id(),
                        'rejection_reason' => $validated['rejection_reason'],
                        'title' => '土地情報差戻し',
                        'message' => sprintf(
                            '施設「%s」の土地情報が差戻しされました。理由: %s',
                            $facility->facility_name,
                            $validated['rejection_reason']
                        ),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Log the rejection
            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name.' - 土地情報差戻し: '.$validated['rejection_reason'],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => '土地情報を差戻ししました。',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設の土地情報を差戻しする権限がありません。',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Land info rejection failed', [
                'facility_id' => $facility->id,
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
     * Upload land document files
     */
    public function uploadDocuments(Request $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('uploadDocuments', [LandInfo::class, $facility]);

            $request->validate([
                'lease_contract_pdf' => 'nullable|file|mimes:pdf|max:2048',
                'registry_pdf' => 'nullable|file|mimes:pdf|max:2048',
            ]);

            $uploadedFiles = [];
            $errors = [];

            // Handle multiple lease contract uploads
            if ($request->hasFile('lease_contracts')) {
                $result = $this->exportService->uploadMultipleLeaseContracts(
                    $facility,
                    $request->file('lease_contracts'),
                    auth()->user()
                );
                $uploadedFiles = array_merge($uploadedFiles, $result['uploaded_files']);
                $errors = array_merge($errors, $result['errors']);
            }

            // Handle property register upload
            if ($request->hasFile('property_register')) {
                try {
                    $uploadedFile = $this->exportService->replaceLandDocument(
                        $facility,
                        $request->file('property_register'),
                        'property_register',
                        auth()->user()
                    );
                    $uploadedFiles[] = $uploadedFile;
                } catch (Exception $e) {
                    $errors[] = [
                        'filename' => $request->file('property_register')->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Log activity
            foreach ($uploadedFiles as $file) {
                $this->activityLogService->logFileUploaded(
                    $file->id,
                    $file->original_name,
                    $facility->id,
                    $request
                );
            }

            $message = count($uploadedFiles) > 0 ? 'ファイルをアップロードしました。' : '';
            if (! empty($errors)) {
                $message .= ' 一部のファイルでエラーが発生しました。';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'errors' => $errors,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイル形式またはサイズが無効です。',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Land document upload failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのアップロードに失敗しました。',
            ], 500);
        }
    }

    /**
     * Get land documents for a facility
     */
    public function getDocuments(Facility $facility, Request $request): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('downloadDocuments', [LandInfo::class, $facility]);

            $documentType = $request->query('type');
            $documents = $this->exportService->getLandDocuments($facility, $documentType);

            return response()->json([
                'success' => true,
                'data' => $documents->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'original_name' => $file->original_name,
                        'file_size' => $file->file_size,
                        'formatted_file_size' => $file->formatted_file_size,
                        'land_document_type' => $file->land_document_type,
                        'document_type_display_name' => $file->document_type_display_name,
                        'uploaded_at' => $file->created_at->format('Y-m-d H:i:s'),
                        'uploader_name' => $file->uploader->name ?? '',
                    ];
                }),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get land documents', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイル一覧の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * Download land document
     */
    public function downloadDocument(Facility $facility, $fileId)
    {
        try {
            // Check authorization using policy
            $this->authorize('downloadDocuments', [LandInfo::class, $facility]);

            $file = File::where('id', $fileId)
                ->where('facility_id', $facility->id)
                ->whereNotNull('land_document_type')
                ->firstOrFail();

            return $this->exportService->downloadLandDocument($file, auth()->user());
        } catch (Exception $e) {
            Log::error('Land document download failed', [
                'facility_id' => $facility->id,
                'file_id' => $fileId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのダウンロードに失敗しました。',
            ], 500);
        }
    }

    /**
     * Delete land document
     */
    public function deleteDocument(Facility $facility, $fileId): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('deleteDocuments', [LandInfo::class, $facility]);

            $file = File::where('id', $fileId)
                ->where('facility_id', $facility->id)
                ->whereNotNull('land_document_type')
                ->firstOrFail();

            $this->exportService->deleteLandDocument($file, auth()->user());

            // Log activity
            $this->activityLogService->logFileDeleted(
                $file->id,
                $file->original_name,
                $facility->id
            );

            return response()->json([
                'success' => true,
                'message' => 'ファイルを削除しました。',
            ]);
        } catch (Exception $e) {
            Log::error('Land document deletion failed', [
                'facility_id' => $facility->id,
                'file_id' => $fileId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルの削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * Get land information status for approval workflow.
     */
    public function getLandInfoStatus(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('view', [LandInfo::class, $facility]);

            $landInfo = $this->facilityService->getLandInfo($facility);

            if (! $landInfo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => null,
                        'has_pending_changes' => false,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $landInfo->status,
                    'has_pending_changes' => $landInfo->status === 'pending_approval',
                    'approved_at' => $landInfo->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $landInfo->approver?->name,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Land info status check failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。',
            ], 500);
        }
    }

    // ========================================
    // Private Helper Methods (from LandInfoController)
    // ========================================

    /**
     * Filter data based on user permissions (simplified)
     */
    private function filterDataByPermissions(array $data, $user): array
    {
        // If user can edit land info, allow all fields
        if ($user->canEditLandInfo()) {
            return $data;
        }

        // If no permission, return empty array
        return [];
    }

    /**
     * Download land info PDF file
     */
    public function downloadLandInfoPdf(Facility $facility, string $type)
    {
        try {
            // Check authorization
            $this->authorize('view', [LandInfo::class, $facility]);

            $landInfo = $facility->landInfo;
            if (!$landInfo) {
                abort(404, '土地情報が見つかりません。');
            }

            $filePath = null;
            $fileName = null;

            switch ($type) {
                case 'lease_contract':
                    $filePath = $landInfo->lease_contract_pdf_path;
                    $fileName = $landInfo->lease_contract_pdf_name;
                    break;
                case 'registry':
                    $filePath = $landInfo->registry_pdf_path;
                    $fileName = $landInfo->registry_pdf_name;
                    break;
                default:
                    abort(404, '指定されたファイルタイプが無効です。');
            }

            if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'ファイルが見つかりません。');
            }

            // Log file access
            Log::info('Land info PDF accessed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'file_type' => $type,
                'file_name' => $fileName
            ]);

            return Storage::disk('public')->download($filePath, $fileName);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'このファイルにアクセスする権限がありません。');
        } catch (\Exception $e) {
            Log::error('Land info PDF download failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'file_type' => $type,
                'error' => $e->getMessage()
            ]);
            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }

    /**
     * Handle PDF file uploads
     */
    private function handlePdfUploads(Request $request, LandInfo $landInfo): void
    {
        try {
            // Handle file deletions first
            if ($request->input('delete_lease_contract_pdf')) {
                if ($landInfo->lease_contract_pdf_path) {
                    Storage::disk('public')->delete($landInfo->lease_contract_pdf_path);
                    $landInfo->update([
                        'lease_contract_pdf_path' => null,
                        'lease_contract_pdf_name' => null,
                    ]);
                    Log::info('Lease contract PDF deleted', [
                        'facility_id' => $landInfo->facility_id,
                        'user_id' => auth()->id()
                    ]);
                }
            }

            if ($request->input('delete_registry_pdf')) {
                if ($landInfo->registry_pdf_path) {
                    Storage::disk('public')->delete($landInfo->registry_pdf_path);
                    $landInfo->update([
                        'registry_pdf_path' => null,
                        'registry_pdf_name' => null,
                    ]);
                    Log::info('Registry PDF deleted', [
                        'facility_id' => $landInfo->facility_id,
                        'user_id' => auth()->id()
                    ]);
                }
            }

            // Handle lease contract PDF upload
            if ($request->hasFile('lease_contract_pdf')) {
                $file = $request->file('lease_contract_pdf');
                
                // Validate file
                if (!$file->isValid()) {
                    throw new \Exception('アップロードされたファイルが無効です: ' . $file->getErrorMessage());
                }

                if ($file->getSize() > 2097152) { // 2MB (PHP upload_max_filesize limit)
                    throw new \Exception('ファイルサイズが大きすぎます。2MB以下のファイルを選択してください。');
                }

                if ($file->getMimeType() !== 'application/pdf') {
                    throw new \Exception('PDFファイルのみアップロード可能です。');
                }

                // Delete old file if exists
                if ($landInfo->lease_contract_pdf_path) {
                    Storage::disk('public')->delete($landInfo->lease_contract_pdf_path);
                }

                // Store new file
                $path = $file->store('land_documents/lease_contracts', 'public');
                
                if (!$path) {
                    throw new \Exception('ファイルの保存に失敗しました。');
                }

                $landInfo->update([
                    'lease_contract_pdf_path' => $path,
                    'lease_contract_pdf_name' => $file->getClientOriginalName(),
                ]);

                Log::info('Lease contract PDF uploaded', [
                    'facility_id' => $landInfo->facility_id,
                    'user_id' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path
                ]);
            }

            // Handle registry PDF upload
            if ($request->hasFile('registry_pdf')) {
                $file = $request->file('registry_pdf');
                
                // Validate file
                if (!$file->isValid()) {
                    throw new \Exception('アップロードされたファイルが無効です: ' . $file->getErrorMessage());
                }

                if ($file->getSize() > 2097152) { // 2MB (PHP upload_max_filesize limit)
                    throw new \Exception('ファイルサイズが大きすぎます。2MB以下のファイルを選択してください。');
                }

                if ($file->getMimeType() !== 'application/pdf') {
                    throw new \Exception('PDFファイルのみアップロード可能です。');
                }

                // Delete old file if exists
                if ($landInfo->registry_pdf_path) {
                    Storage::disk('public')->delete($landInfo->registry_pdf_path);
                }

                // Store new file
                $path = $file->store('land_documents/registry', 'public');
                
                if (!$path) {
                    throw new \Exception('ファイルの保存に失敗しました。');
                }

                $landInfo->update([
                    'registry_pdf_path' => $path,
                    'registry_pdf_name' => $file->getClientOriginalName(),
                ]);

                Log::info('Registry PDF uploaded', [
                    'facility_id' => $landInfo->facility_id,
                    'user_id' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PDF upload failed', [
                'facility_id' => $landInfo->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
