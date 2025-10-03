<?php

namespace App\Http\Controllers;

use App\Exceptions\DocumentServiceException;
use App\Http\Requests\CreateFolderRequest;
use App\Http\Requests\RenameFolderRequest;
use App\Http\Requests\UploadFileRequest;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Services\ActivityLogService;
use App\Services\DocumentErrorHandler;
use App\Services\DocumentService;
use App\Services\UserPreferenceService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    protected ActivityLogService $activityLogService;
    protected UserPreferenceService $userPreferenceService;

    public function __construct(
        DocumentService $documentService, 
        ActivityLogService $activityLogService,
        UserPreferenceService $userPreferenceService
    ) {
        $this->documentService = $documentService;
        $this->activityLogService = $activityLogService;
        $this->userPreferenceService = $userPreferenceService;
    }

    /**
     * Display the document management interface for a facility.
     * 
     * @param Facility $facility
     * @return View
     */
    public function index(Facility $facility): View
    {
        try {
            // Check authorization
            $this->authorize('viewAny', [DocumentFolder::class, $facility]);

            // Get settings from user preferences
            $settings = $this->userPreferenceService->getDocumentSettings($facility->id);

            // Get root folder contents with saved settings
            $folderContents = $this->documentService->getFolderContents($facility, null, $settings);

            // Get available file types for filtering
            $availableFileTypes = $this->documentService->getAvailableFileTypes($facility);

            return view('facilities.documents.index', compact(
                'facility',
                'folderContents',
                'availableFileTypes'
            ));

        } catch (Exception $e) {
            // Use centralized error handler
            $errorResponse = DocumentErrorHandler::handleError($e, request(), [
                'facility_id' => $facility->id,
                'operation' => 'document_index'
            ]);

            // For HTML requests, return view with error state
            if (!request()->expectsJson()) {
                return view('facilities.documents.index', [
                    'facility' => $facility,
                    'folderContents' => ['folders' => [], 'files' => [], 'sort_options' => []],
                    'availableFileTypes' => [],
                    'error' => 'ドキュメントの読み込みに失敗しました。'
                ]);
            }

            return $errorResponse;
        }
    }

    /**
     * Show folder contents (AJAX endpoint).
     * 
     * @param Facility $facility
     * @param DocumentFolder|null $folder
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Facility $facility, ?DocumentFolder $folder = null): JsonResponse
    {
        try {
            Log::info('DocumentController::show called', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'request_params' => request()->all()
            ]);
            
            // Check authorization
            $this->authorize('viewAny', [DocumentFolder::class, $facility]);

            // Validate folder belongs to facility if provided
            if ($folder && $folder->facility_id !== $facility->id) {
                throw DocumentServiceException::folderNotFound($folder->id, [
                    'facility_id' => $facility->id,
                    'expected_facility_id' => $folder->facility_id
                ]);
            }

            // Get current settings from user preferences
            $request = request();
            $currentSettings = $this->userPreferenceService->getDocumentSettings($facility->id);
            
            // Extract settings from request parameters
            $requestSettings = $this->userPreferenceService->extractDocumentSettingsFromRequest($request->all());
            
            // Merge current settings with request settings
            $settings = array_merge($currentSettings, $requestSettings);
            
            // Validate settings
            $validatedSettings = $this->userPreferenceService->validateDocumentSettings($settings);
            
            // Save validated settings to user preferences
            if (!empty($validatedSettings)) {
                $this->userPreferenceService->saveDocumentSettings($facility->id, $validatedSettings);
            }
            
            // Use final settings for folder contents
            $finalSettings = array_merge($currentSettings, $validatedSettings);

            // パフォーマンス最適化のためのオプション追加
            $performanceOptions = array_merge($finalSettings, [
                'page' => request('page', 1),
                'per_page' => min(request('per_page', 50), 100), // 最大100件に制限
                'load_stats' => request('load_stats', true),
            ]);

            // Get folder contents
            $folderContents = $this->documentService->getFolderContents($facility, $folder, $performanceOptions);

            // Get available file types for filtering (キャッシュ対応)
            $availableFileTypes = cache()->remember(
                "facility_file_types_{$facility->id}",
                300, // 5分キャッシュ
                fn() => $this->documentService->getAvailableFileTypes($facility)
            );

            $responseData = [
                'success' => true,
                'data' => [
                    'folders' => $folderContents['folders'],
                    'files' => $folderContents['files'],
                    'breadcrumbs' => $folderContents['breadcrumbs'],
                    'current_folder' => $folderContents['current_folder'],
                    'stats' => $folderContents['stats'] ?? null,
                    'sort_options' => $folderContents['sort_options'],
                    'pagination' => $folderContents['pagination'] ?? null,
                    'available_file_types' => $availableFileTypes,
                ]
            ];

            Log::info('DocumentController::show response', [
                'facility_id' => $facility->id,
                'folder_count' => count($folderContents['folders']),
                'file_count' => count($folderContents['files']),
                'response_data' => $responseData
            ]);

            return response()->json($responseData);

        } catch (Exception $e) {
            return DocumentErrorHandler::handleError($e, request(), [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'operation' => 'document_show'
            ]);
        }
    }

    /**
     * Create a new folder.
     * 
     * @param CreateFolderRequest $request
     * @param Facility $facility
     * @return JsonResponse
     */
    public function createFolder(CreateFolderRequest $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('create', [DocumentFolder::class, $facility]);

            $validated = $request->validated();
            $parentFolder = null;

            // Get parent folder if specified
            if (!empty($validated['parent_id'])) {
                $parentFolder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $validated['parent_id'])
                    ->first();

                if (!$parentFolder) {
                    throw DocumentServiceException::folderNotFound($validated['parent_id'], [
                        'facility_id' => $facility->id,
                        'context' => 'parent_folder_lookup'
                    ]);
                }
            }

            // Create folder
            $folder = $this->documentService->createFolder(
                $facility,
                $parentFolder,
                $validated['name'],
                auth()->user()
            );

            // Log activity
            $this->activityLogService->logDocumentFolderCreated(
                $folder->id,
                $folder->name,
                $facility->id
            );

            return response()->json([
                'success' => true,
                'message' => 'フォルダを作成しました。',
                'folder' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'created_at' => $folder->created_at->format('Y-m-d H:i:s'),
                    'creator' => $folder->creator->name ?? 'Unknown'
                ]
            ], 201);

        } catch (Exception $e) {
            return DocumentErrorHandler::handleError($e, request(), [
                'facility_id' => $facility->id,
                'folder_name' => $request->input('name'),
                'parent_id' => $request->input('parent_id'),
                'operation' => 'folder_create'
            ]);
        }
    }

    /**
     * Rename a folder.
     * 
     * @param RenameFolderRequest $request
     * @param Facility $facility
     * @param DocumentFolder $folder
     * @return JsonResponse
     */
    public function renameFolder(RenameFolderRequest $request, Facility $facility, DocumentFolder $folder): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('update', $folder);

            $validated = $request->validated();
            $oldName = $folder->name;

            // Rename folder
            $updatedFolder = $this->documentService->renameFolder(
                $folder,
                $validated['name'],
                auth()->user()
            );

            // Log activity
            $this->activityLogService->logDocumentFolderRenamed(
                $updatedFolder->id,
                $oldName,
                $updatedFolder->name,
                $updatedFolder->facility_id
            );

            return response()->json([
                'success' => true,
                'message' => 'フォルダ名を変更しました。',
                'folder' => [
                    'id' => $updatedFolder->id,
                    'name' => $updatedFolder->name,
                    'path' => $updatedFolder->path,
                    'updated_at' => $updatedFolder->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'フォルダ名を変更する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('Folder rename failed', [
                'folder_id' => $folder->id,
                'facility_id' => $folder->facility_id,
                'new_name' => $validated['name'] ?? 'unknown',
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダ名の変更に失敗しました。'
            ], 500);
        }
    }

    /**
     * Delete a folder.
     * 
     * @param Facility $facility
     * @param DocumentFolder $folder
     * @return JsonResponse
     */
    public function deleteFolder(Facility $facility, DocumentFolder $folder): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('delete', $folder);

            $folderName = $folder->name;
            $facilityId = $folder->facility_id;

            // Delete folder
            $result = $this->documentService->deleteFolder($folder, auth()->user());

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'フォルダを削除できませんでした。フォルダが空でない可能性があります。'
                ], 400);
            }

            // Log activity
            $this->activityLogService->logDocumentFolderDeleted(
                $folder->id,
                $folderName,
                $facilityId
            );

            return response()->json([
                'success' => true,
                'message' => 'フォルダを削除しました。'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'フォルダを削除する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('Folder deletion failed', [
                'folder_id' => $folder->id,
                'facility_id' => $folder->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダの削除に失敗しました。'
            ], 500);
        }
    }

    /**
     * Upload multiple files.
     * 
     * @param UploadFileRequest $request
     * @param Facility $facility
     * @return JsonResponse
     */
    public function uploadFile(UploadFileRequest $request, Facility $facility): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('create', [DocumentFile::class, $facility]);

            $validated = $request->validated();
            $folder = null;

            // Get folder if specified
            if (!empty($validated['folder_id'])) {
                $folder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $validated['folder_id'])
                    ->first();

                if (!$folder) {
                    return response()->json([
                        'success' => false,
                        'message' => '指定されたフォルダが見つかりません。'
                    ], 404);
                }
            }

            $uploadedFiles = [];
            $errors = [];
            $successCount = 0;

            // Process each file
            foreach ($validated['files'] as $index => $file) {
                try {
                    // Upload file
                    $documentFile = $this->documentService->uploadFile(
                        $facility,
                        $folder,
                        $file,
                        auth()->user()
                    );

                    $uploadedFiles[] = [
                        'id' => $documentFile->id,
                        'name' => $documentFile->original_name,
                        'size' => $documentFile->getFormattedSize(),
                        'type' => $documentFile->file_extension,
                        'uploaded_at' => $documentFile->created_at->format('Y-m-d H:i:s'),
                        'uploader' => $documentFile->uploader->name ?? 'Unknown',
                        'download_url' => $documentFile->getDownloadUrl(),
                        'icon' => $documentFile->getFileIcon(),
                        'color' => $documentFile->getFileColor(),
                        'can_preview' => $documentFile->canPreview()
                    ];

                    // Log activity
                    $this->activityLogService->logDocumentFileUploaded(
                        $documentFile->id,
                        $documentFile->original_name,
                        $folder?->name,
                        $facility->id,
                        $documentFile->file_size
                    );

                    $successCount++;

                } catch (Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];

                    Log::error('Individual file upload failed', [
                        'facility_id' => $facility->id,
                        'file_name' => $file->getClientOriginalName(),
                        'user_id' => auth()->id(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Determine response based on results
            if ($successCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'すべてのファイルのアップロードに失敗しました。',
                    'errors' => $errors
                ], 400);
            } elseif (count($errors) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}個のファイルをアップロードしました。" . count($errors) . "個のファイルでエラーが発生しました。",
                    'files' => $uploadedFiles,
                    'errors' => $errors,
                    'partial' => true
                ], 201);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "{$successCount}個のファイルをアップロードしました。",
                    'files' => $uploadedFiles
                ], 201);
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイルをアップロードする権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('File upload failed', [
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
     * Download a file with enhanced security checks.
     * 
     * Requirements: 8.4, 9.1, 9.2, 9.3 - ファイルダウンロード時の権限確認とセキュリティ
     * 
     * @param Facility $facility
     * @param DocumentFile $file
     * @return StreamedResponse
     */
    public function downloadFile(Facility $facility, DocumentFile $file): StreamedResponse
    {
        try {
            // Check authorization (Requirement 9.1, 9.2)
            $this->authorize('view', $file);

            // Additional security checks
            $this->performDownloadSecurityChecks($file);

            // Validate file path to prevent path traversal (Requirement 8.4)
            $this->validateFilePath($file->file_path);

            // Check if file exists and is readable
            $filePath = storage_path('app/public/' . $file->file_path);
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception('ファイルが見つからないか、読み取りできません。');
            }

            // Verify file integrity
            if (!$this->verifyFileIntegrity($file, $filePath)) {
                Log::warning('File integrity check failed during download', [
                    'file_id' => $file->id,
                    'file_path' => $file->file_path,
                    'user_id' => auth()->id()
                ]);
                throw new Exception('ファイルの整合性チェックに失敗しました。');
            }

            // Log activity (Requirement 9.4)
            $this->activityLogService->logDocumentFileDownloaded(
                $file->id,
                $file->original_name,
                $file->facility_id
            );

            // Return secure file download response
            return response()->streamDownload(function () use ($filePath) {
                $handle = fopen($filePath, 'rb');
                if ($handle) {
                    while (!feof($handle)) {
                        echo fread($handle, 8192); // Read in chunks for memory efficiency
                        flush();
                    }
                    fclose($handle);
                } else {
                    throw new Exception('ファイルを開けませんでした。');
                }
            }, $this->sanitizeFilename($file->original_name), [
                'Content-Type' => $file->mime_type,
                'Content-Length' => $file->file_size,
                'Content-Disposition' => 'attachment; filename="' . $this->sanitizeFilename($file->original_name) . '"',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Log unauthorized access attempt (Requirement 9.4)
            Log::warning('Unauthorized file download attempt', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip()
            ]);
            abort(403, 'このファイルをダウンロードする権限がありません。');
        } catch (Exception $e) {
            Log::error('File download failed', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            abort(404, 'ファイルが見つかりません。');
        }
    }

    /**
     * Preview a file (for supported file types).
     * 
     * @param Facility $facility
     * @param DocumentFile $file
     * @return mixed
     */
    public function previewFile(Facility $facility, DocumentFile $file)
    {
        try {
            // Check authorization
            $this->authorize('view', $file);

            // Check if file can be previewed
            if (!$file->canPreview()) {
                return response()->json([
                    'success' => false,
                    'message' => 'このファイル形式はプレビューできません。'
                ], 400);
            }

            $filePath = storage_path('app/public/' . $file->file_path);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ファイルが見つかりません。'
                ], 404);
            }

            // Log activity
            $this->activityLogService->logDocumentFilePreviewed(
                $file->id,
                $file->original_name,
                $file->facility_id
            );

            // Return file for preview
            return response()->file($filePath, [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'このファイルをプレビューする権限がありません。');
        } catch (Exception $e) {
            Log::error('File preview failed', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのプレビューに失敗しました。'
            ], 500);
        }
    }

    /**
     * Delete a file.
     * 
     * @param Facility $facility
     * @param DocumentFile $file
     * @return JsonResponse
     */
    public function deleteFile(Facility $facility, DocumentFile $file): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('delete', $file);

            $fileName = $file->original_name;
            $facilityId = $file->facility_id;

            // Delete file
            $result = $this->documentService->deleteFile($file, auth()->user());

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'ファイルを削除できませんでした。'
                ], 400);
            }

            // Log activity
            $this->activityLogService->logDocumentFileDeleted(
                $file->id,
                $fileName,
                $facilityId
            );

            return response()->json([
                'success' => true,
                'message' => 'ファイルを削除しました。'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイルを削除する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルの削除に失敗しました。'
            ], 500);
        }
    }

    /**
     * Get file properties.
     * 
     * @param Facility $facility
     * @param DocumentFile $file
     * @return JsonResponse
     */
    public function getFileProperties(Facility $facility, DocumentFile $file): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('view', $file);

            // Validate file belongs to facility
            if ($file->facility_id !== $facility->id) {
                return response()->json([
                    'success' => false,
                    'message' => '指定されたファイルが見つかりません。'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'file',
                    'name' => $file->original_name,
                    'size' => $file->file_size,
                    'formatted_size' => $file->getFormattedSize(),
                    'extension' => $file->file_extension,
                    'mime_type' => $file->mime_type,
                    'created_at' => $file->created_at,
                    'updated_at' => $file->updated_at,
                    'creator' => $file->uploader->name ?? 'Unknown',
                    'path' => $file->folder ? $file->folder->getFullPath() . '/' . $file->original_name : $file->original_name
                ]
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'このファイルのプロパティを表示する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('File properties fetch failed', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルのプロパティを取得できませんでした。'
            ], 500);
        }
    }

    /**
     * Get folder properties.
     * 
     * @param Facility $facility
     * @param DocumentFolder $folder
     * @return JsonResponse
     */
    public function getFolderProperties(Facility $facility, DocumentFolder $folder): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('view', $folder);

            // Validate folder belongs to facility
            if ($folder->facility_id !== $facility->id) {
                return response()->json([
                    'success' => false,
                    'message' => '指定されたフォルダが見つかりません。'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'folder',
                    'name' => $folder->name,
                    'file_count' => $folder->getFileCount(),
                    'created_at' => $folder->created_at,
                    'updated_at' => $folder->updated_at,
                    'creator' => $folder->creator->name ?? 'Unknown',
                    'path' => $folder->getFullPath()
                ]
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'このフォルダのプロパティを表示する権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('Folder properties fetch failed', [
                'folder_id' => $folder->id,
                'facility_id' => $folder->facility_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダのプロパティを取得できませんでした。'
            ], 500);
        }
    }

    /**
     * Rename a file.
     * 
     * @param Request $request
     * @param Facility $facility
     * @param DocumentFile $file
     * @return JsonResponse
     */
    public function renameFile(Request $request, Facility $facility, DocumentFile $file): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('update', $file);

            // Validate file belongs to facility
            if ($file->facility_id !== $facility->id) {
                return response()->json([
                    'success' => false,
                    'message' => '指定されたファイルが見つかりません。'
                ], 404);
            }

            $request->validate([
                'name' => ['required', 'string', 'max:255']
            ]);

            $newName = $request->input('name');

            // Rename file using service
            $updatedFile = $this->documentService->renameFile($file, $newName, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'ファイル名を変更しました。',
                'file' => [
                    'id' => $updatedFile->id,
                    'name' => $updatedFile->original_name,
                    'updated_at' => $updatedFile->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイル名を変更する権限がありません。'
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイル名が正しくありません。',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('File rename failed', [
                'file_id' => $file->id,
                'facility_id' => $file->facility_id,
                'new_name' => $request->input('name'),
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイル名の変更に失敗しました。'
            ], 500);
        }
    }

    /**
     * Reset user preferences for document management.
     * 
     * @param Facility $facility
     * @return JsonResponse
     */
    public function resetPreferences(Facility $facility): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('viewAny', [DocumentFolder::class, $facility]);

            // Reset preferences
            $this->userPreferenceService->resetDocumentSettings($facility->id);

            // Log activity
            $this->activityLogService->log(
                'document_preferences_reset',
                'facility',
                $facility->id,
                'ドキュメント管理の表示設定をリセットしました'
            );

            return response()->json([
                'success' => true,
                'message' => '表示設定をリセットしました。'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => '設定をリセットする権限がありません。'
            ], 403);
        } catch (Exception $e) {
            Log::error('Document preferences reset failed', [
                'facility_id' => $facility->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '設定のリセットに失敗しました。'
            ], 500);
        }
    }

    /**
     * Perform additional security checks for file downloads.
     * 
     * Requirements: 8.4 - ファイルアクセス時の権限確認
     */
    protected function performDownloadSecurityChecks(DocumentFile $file): void
    {
        // Check if user has exceeded download limits
        $user = auth()->user();
        $downloadKey = 'file_downloads:' . $user->id;
        
        if (cache()->get($downloadKey, 0) > 100) { // Max 100 downloads per hour
            throw new Exception('ダウンロード制限に達しました。しばらく待ってから再試行してください。');
        }
        
        cache()->increment($downloadKey, 1);
        cache()->expire($downloadKey, 3600); // 1 hour

        // Check file age - warn about very old files
        if ($file->created_at->diffInDays(now()) > 365) {
            Log::info('Old file downloaded', [
                'file_id' => $file->id,
                'file_age_days' => $file->created_at->diffInDays(now()),
                'user_id' => $user->id
            ]);
        }

        // Check for suspicious download patterns
        $recentDownloads = cache()->get('recent_downloads:' . $user->id, []);
        if (count($recentDownloads) > 10) { // More than 10 downloads in short time
            Log::warning('Suspicious download pattern detected', [
                'user_id' => $user->id,
                'recent_downloads_count' => count($recentDownloads),
                'file_id' => $file->id
            ]);
        }

        // Add current download to recent downloads
        $recentDownloads[] = [
            'file_id' => $file->id,
            'timestamp' => now()->timestamp
        ];
        
        // Keep only last 15 minutes of downloads
        $recentDownloads = array_filter($recentDownloads, function ($download) {
            return (now()->timestamp - $download['timestamp']) < 900; // 15 minutes
        });
        
        cache()->put('recent_downloads:' . $user->id, $recentDownloads, 900);
    }

    /**
     * Validate file path to prevent path traversal attacks.
     * 
     * Requirements: 8.4 - パストラバーサル攻撃対策
     */
    protected function validateFilePath(string $filePath): void
    {
        // Check for path traversal attempts
        if (strpos($filePath, '..') !== false) {
            throw new Exception('不正なファイルパスが検出されました。');
        }

        // Check for absolute paths
        if (strpos($filePath, '/') === 0 || preg_match('/^[a-zA-Z]:/', $filePath)) {
            throw new Exception('不正なファイルパスが検出されました。');
        }

        // Ensure path is within allowed directory
        $allowedPrefix = 'documents/';
        if (strpos($filePath, $allowedPrefix) !== 0) {
            throw new Exception('許可されていないディレクトリへのアクセスです。');
        }

        // Check for dangerous characters
        if (preg_match('/[<>:"|?*\x00-\x1f]/', $filePath)) {
            throw new Exception('不正な文字がファイルパスに含まれています。');
        }
    }

    /**
     * Verify file integrity before download.
     * 
     * Requirements: 8.4 - ファイル整合性チェック
     */
    protected function verifyFileIntegrity(DocumentFile $file, string $filePath): bool
    {
        // Check if actual file size matches database record
        $actualSize = filesize($filePath);
        if ($actualSize !== $file->file_size) {
            Log::warning('File size mismatch detected', [
                'file_id' => $file->id,
                'expected_size' => $file->file_size,
                'actual_size' => $actualSize
            ]);
            return false;
        }

        // Basic MIME type verification
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Allow some flexibility in MIME type matching
        $allowedMimeVariations = [
            'application/pdf' => ['application/pdf'],
            'image/jpeg' => ['image/jpeg', 'image/jpg'],
            'image/png' => ['image/png'],
            'application/zip' => ['application/zip', 'application/x-zip-compressed'],
            'text/plain' => ['text/plain', 'text/x-plain'],
        ];

        if (isset($allowedMimeVariations[$file->mime_type])) {
            if (!in_array($actualMimeType, $allowedMimeVariations[$file->mime_type])) {
                Log::warning('MIME type mismatch detected', [
                    'file_id' => $file->id,
                    'expected_mime' => $file->mime_type,
                    'actual_mime' => $actualMimeType
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize filename for safe download.
     * 
     * Requirements: 8.4 - セキュアなファイル名処理
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove or replace dangerous characters
        $filename = preg_replace('/[<>:"|?*\x00-\x1f]/', '_', $filename);
        
        // Remove path separators
        $filename = str_replace(['/', '\\'], '_', $filename);
        
        // Limit filename length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $basename = substr($basename, 0, 255 - strlen($extension) - 1);
            $filename = $basename . '.' . $extension;
        }
        
        // Ensure filename is not empty
        if (empty(trim($filename))) {
            $filename = 'download_' . time();
        }
        
        return $filename;
    }

    /**
     * 仮想スクロール用のフォルダ内容取得
     */
    public function showVirtual(Facility $facility, ?DocumentFolder $folder = null): JsonResponse
    {
        try {
            $this->authorize('viewAny', [DocumentFolder::class, $facility]);

            // Validate folder belongs to facility if provided
            if ($folder && $folder->facility_id !== $facility->id) {
                throw DocumentServiceException::folderNotFound($folder->id, [
                    'facility_id' => $facility->id,
                    'expected_facility_id' => $folder->facility_id
                ]);
            }

            $options = [
                'offset' => request('offset', 0),
                'limit' => min(request('limit', 50), 100),
                'sort_by' => request('sort_by', 'name'),
                'sort_direction' => request('sort_direction', 'asc'),
                'filter_type' => request('filter_type'),
                'search' => request('search'),
                'item_type' => request('item_type', 'all'),
            ];

            $data = $this->documentService->getFolderContentsVirtual($facility, $folder, $options);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => '仮想スクロール用データを取得しました。'
            ]);

        } catch (Exception $e) {
            return DocumentErrorHandler::handleError($e, request(), [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'operation' => 'document_show_virtual'
            ]);
        }
    }


}