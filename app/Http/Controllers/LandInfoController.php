<?php

namespace App\Http\Controllers;

use App\Http\Requests\LandInfoRequest;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\File;
use App\Services\LandInfoService;
use App\Services\LandCalculationService;
use App\Services\ActivityLogService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class LandInfoController extends Controller
{
    protected LandInfoService $landInfoService;
    protected LandCalculationService $landCalculationService;
    protected ActivityLogService $activityLogService;
    protected FileService $fileService;

    public function __construct(
        LandInfoService $landInfoService,
        LandCalculationService $landCalculationService,
        ActivityLogService $activityLogService,
        FileService $fileService
    ) {
        $this->landInfoService = $landInfoService;
        $this->landCalculationService = $landCalculationService;
        $this->activityLogService = $activityLogService;
        $this->fileService = $fileService;
    }

    /**
     * Display the land information for the specified facility.
     */
    public function show(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('view', [LandInfo::class, $facility]);

            $landInfo = $this->landInfoService->getLandInfo($facility);

            if (!$landInfo) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => '土地情報が登録されていません。'
                ]);
            }

            $formattedData = $this->landInfoService->formatDisplayData($landInfo);

            return response()->json([
                'success' => true,
                'data' => $formattedData
            ]);
        } catch (Exception $e) {
            Log::error('Land info show failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * Show the form for editing land information.
     */
    public function edit(Facility $facility)
    {
        try {
            // Check authorization using policy
            $this->authorize('update', [LandInfo::class, $facility]);

            $landInfo = $this->landInfoService->getLandInfo($facility);

            return view('facilities.land-info-edit', compact('facility', 'landInfo'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の土地情報を編集する権限がありません。');
        } catch (Exception $e) {
            Log::error('Land info edit failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('facilities.show', $facility)
                ->with('error', 'システムエラーが発生しました。');
        }
    }

    /**
     * Update the land information for the specified facility.
     */
    public function update(LandInfoRequest $request, Facility $facility)
    {
        try {
            // Check authorization using policy
            $this->authorize('update', [LandInfo::class, $facility]);

            // Check field-level permissions
            $user = auth()->user();
            $validatedData = $request->validated();

            // Filter data based on user permissions
            $filteredData = $this->filterDataByPermissions($validatedData, $user);

            $landInfo = $this->landInfoService->createOrUpdateLandInfo(
                $facility,
                $filteredData,
                $user
            );

            // Handle PDF file uploads if user has permission
            if ($user->canEditLandDocuments()) {
                $this->handlePdfUploads($request, $landInfo);
            }

            // Log the activity
            $this->activityLogService->logFacilityUpdated(
                $facility->id,
                $facility->facility_name . ' - 土地情報',
                $request
            );

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                $formattedData = $this->landInfoService->formatDisplayData($landInfo);
                return response()->json([
                    'success' => true,
                    'message' => '土地情報を更新しました。',
                    'data' => $formattedData
                ]);
            }

            return redirect()->route('facilities.show', $facility)
                ->with('success', '土地情報を更新しました。');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この施設の土地情報を編集する権限がありません。'
                ], 403);
            }
            return redirect()->route('facilities.show', $facility)
                ->with('error', 'この施設の土地情報を編集する権限がありません。');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '入力内容に誤りがあります。',
                    'errors' => $e->errors()
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
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'システムエラーが発生しました。'
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'システムエラーが発生しました。')
                ->withInput();
        }
    }

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
     * Handle PDF file uploads
     */
    private function handlePdfUploads(Request $request, LandInfo $landInfo): void
    {
        // Handle file deletions first
        if ($request->input('delete_lease_contract_pdf')) {
            if ($landInfo->lease_contract_pdf_path) {
                \Storage::disk('public')->delete($landInfo->lease_contract_pdf_path);
                $landInfo->update([
                    'lease_contract_pdf_path' => null,
                    'lease_contract_pdf_name' => null
                ]);
            }
        }

        if ($request->input('delete_registry_pdf')) {
            if ($landInfo->registry_pdf_path) {
                \Storage::disk('public')->delete($landInfo->registry_pdf_path);
                $landInfo->update([
                    'registry_pdf_path' => null,
                    'registry_pdf_name' => null
                ]);
            }
        }

        // Handle lease contract PDF upload
        if ($request->hasFile('lease_contract_pdf')) {
            // Delete old file if exists
            if ($landInfo->lease_contract_pdf_path) {
                \Storage::disk('public')->delete($landInfo->lease_contract_pdf_path);
            }

            $file = $request->file('lease_contract_pdf');
            $path = $file->store('land_documents/lease_contracts', 'public');

            $landInfo->update([
                'lease_contract_pdf_path' => $path,
                'lease_contract_pdf_name' => $file->getClientOriginalName()
            ]);
        }

        // Handle registry PDF upload
        if ($request->hasFile('registry_pdf')) {
            // Delete old file if exists
            if ($landInfo->registry_pdf_path) {
                \Storage::disk('public')->delete($landInfo->registry_pdf_path);
            }

            $file = $request->file('registry_pdf');
            $path = $file->store('land_documents/registry', 'public');

            $landInfo->update([
                'registry_pdf_path' => $path,
                'registry_pdf_name' => $file->getClientOriginalName()
            ]);
        }
    }

    /**
     * Calculate fields for real-time calculations.
     */
    public function calculateFields(Request $request): JsonResponse
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
                        $unitPrice = $this->landCalculationService->calculateUnitPrice(
                            (float) $validated['purchase_price'],
                            (float) $validated['site_area_tsubo']
                        );

                        $result['unit_price'] = $unitPrice;
                        $result['formatted_unit_price'] = $unitPrice ?
                            $this->landCalculationService->formatCurrency($unitPrice) : '';
                    }
                    break;

                case 'contract_period':
                    if (isset($validated['contract_start_date']) && isset($validated['contract_end_date'])) {
                        $contractPeriod = $this->landCalculationService->calculateContractPeriod(
                            $validated['contract_start_date'],
                            $validated['contract_end_date']
                        );

                        $result['contract_period'] = $contractPeriod;
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Land info calculation failed', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '計算処理でエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * Get land information status for approval workflow.
     */
    public function getStatus(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('view', [LandInfo::class, $facility]);

            $landInfo = $this->landInfoService->getLandInfo($facility);

            if (!$landInfo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => null,
                        'has_pending_changes' => false
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $landInfo->status,
                    'has_pending_changes' => $landInfo->status === 'pending_approval',
                    'approved_at' => $landInfo->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $landInfo->approver?->name
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Land info status check failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * Approve pending land information changes.
     */
    public function approve(Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('approve', [LandInfo::class, $facility]);

            $landInfo = $this->landInfoService->getLandInfo($facility);

            if (!$landInfo || $landInfo->status !== 'pending_approval') {
                $message = !$landInfo ? '承認待ちの土地情報がありません。' : 'この土地情報は既に承認済みです。';
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 422);
            }

            $landInfo->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id()
            ]);

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
                $facility->facility_name . ' - 土地情報承認',
                request()
            );

            return response()->json([
                'success' => true,
                'message' => '土地情報を承認しました。'
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設の土地情報を承認する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('Land info approval failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * Reject pending land information changes.
     */
    public function reject(Request $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('reject', [LandInfo::class, $facility]);

            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:1000'
            ]);

            $landInfo = $this->landInfoService->getLandInfo($facility);

            if (!$landInfo || $landInfo->status !== 'pending_approval') {
                return response()->json([
                    'success' => false,
                    'message' => '承認待ちの土地情報がありません。'
                ], 422);
            }

            $landInfo->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'rejected_at' => now(),
                'rejected_by' => auth()->id()
            ]);

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
                $facility->facility_name . ' - 土地情報差戻し: ' . $validated['rejection_reason'],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => '土地情報を差戻ししました。'
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設の土地情報を差戻しする権限がありません。'
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Land info rejection failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'システムエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * Upload land document files
     * Requirements: 6.1, 6.2, 6.3
     */
    public function uploadDocuments(Request $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('uploadDocuments', [LandInfo::class, $facility]);

            $request->validate([
                'lease_contracts.*' => 'nullable|file|mimes:pdf|max:10240',
                'property_register' => 'nullable|file|mimes:pdf|max:10240',
            ]);

            $uploadedFiles = [];
            $errors = [];

            // Handle multiple lease contract uploads
            if ($request->hasFile('lease_contracts')) {
                $result = $this->fileService->uploadMultipleLeaseContracts(
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
                    $uploadedFile = $this->fileService->replaceLandDocument(
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
            if (!empty($errors)) {
                $message .= ' 一部のファイルでエラーが発生しました。';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'errors' => $errors,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイル形式またはサイズが無効です。',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Land document upload failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのアップロードに失敗しました。'
            ], 500);
        }
    }

    /**
     * Get land documents for a facility
     * Requirements: 6.4
     */
    public function getDocuments(Facility $facility, Request $request): JsonResponse
    {
        try {
            // Check authorization using policy
            $this->authorize('downloadDocuments', [LandInfo::class, $facility]);

            $documentType = $request->query('type');
            $documents = $this->fileService->getLandDocuments($facility, $documentType);

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
                })
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get land documents', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイル一覧の取得に失敗しました。'
            ], 500);
        }
    }

    /**
     * Download land document
     * Requirements: 6.4
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

            return $this->fileService->downloadLandDocument($file, auth()->user());
        } catch (Exception $e) {
            Log::error('Land document download failed', [
                'facility_id' => $facility->id,
                'file_id' => $fileId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのダウンロードに失敗しました。'
            ], 500);
        }
    }

    /**
     * Delete land document
     * Requirements: 6.5
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

            $this->fileService->deleteLandDocument($file, auth()->user());

            // Log activity
            $this->activityLogService->logFileDeleted(
                $file->id,
                $file->original_name,
                $facility->id
            );

            return response()->json([
                'success' => true,
                'message' => 'ファイルを削除しました。'
            ]);
        } catch (Exception $e) {
            Log::error('Land document deletion failed', [
                'facility_id' => $facility->id,
                'file_id' => $fileId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルの削除に失敗しました。'
            ], 500);
        }
    }
}
