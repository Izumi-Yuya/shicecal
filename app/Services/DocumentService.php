<?php

namespace App\Services;

use App\Exceptions\DocumentServiceException;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ドキュメント管理サービス
 *
 * Comprehensive document management service for facility documents.
 * Provides folder and file operations with proper validation, authorization,
 * and error handling.
 *
 * Features:
 * - Folder creation, update, and deletion with hierarchy support
 * - File upload, download, and management operations
 * - Sorting and filtering capabilities
 * - Breadcrumb navigation generation
 * - Storage statistics and usage tracking
 */
class DocumentService
{
    protected FileHandlingService $fileHandlingService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        FileHandlingService $fileHandlingService,
        ActivityLogService $activityLogService
    ) {
        $this->fileHandlingService = $fileHandlingService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * フォルダ作成
     */
    public function createFolder(Facility $facility, ?DocumentFolder $parent, string $name, User $user): DocumentFolder
    {
        try {
            DB::beginTransaction();

            // 同一階層での名前重複チェック
            $existingFolder = DocumentFolder::where('facility_id', $facility->id)
                ->where('parent_id', $parent?->id)
                ->where('name', $name)
                ->first();

            if ($existingFolder) {
                throw DocumentServiceException::duplicateName($name, 'フォルダ', [
                    'facility_id' => $facility->id,
                    'parent_id' => $parent?->id,
                    'existing_folder_id' => $existingFolder->id
                ]);
            }

            // パス生成
            $path = $parent ? $parent->path . '/' . $name : $name;

            // フォルダ作成
            $folder = DocumentFolder::create([
                'facility_id' => $facility->id,
                'parent_id' => $parent?->id,
                'name' => $name,
                'path' => $path,
                'created_by' => $user->id,
            ]);

            // 物理ディレクトリ作成
            $physicalPath = "facility_{$facility->id}/" . ($parent ? "folder_{$parent->id}" : 'root') . "/{$name}";
            $this->fileHandlingService->createDirectory($physicalPath);

            // アクティビティログ
            $this->activityLogService->logDocumentFolderCreated(
                $folder->id,
                $name,
                $facility->id
            );

            DB::commit();

            Log::info('Document folder created successfully', [
                'facility_id' => $facility->id,
                'folder_id' => $folder->id,
                'name' => $name,
                'path' => $path,
                'user_id' => $user->id,
            ]);

            return $folder;

        } catch (DocumentServiceException $e) {
            DB::rollBack();
            throw $e; // Re-throw document-specific exceptions as-is
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document folder creation failed', [
                'facility_id' => $facility->id,
                'parent_id' => $parent?->id,
                'name' => $name,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw DocumentServiceException::uploadFailed($name, $e->getMessage(), [
                'facility_id' => $facility->id,
                'parent_id' => $parent?->id,
                'user_id' => $user->id,
                'operation' => 'folder_creation'
            ]);
        }
    }

    /**
     * フォルダ名変更
     */
    public function renameFolder(DocumentFolder $folder, string $newName, User $user): DocumentFolder
    {
        try {
            DB::beginTransaction();

            $oldName = $folder->name;
            $oldPath = $folder->path;

            // 同一階層での名前重複チェック
            $existingFolder = DocumentFolder::where('facility_id', $folder->facility_id)
                ->where('parent_id', $folder->parent_id)
                ->where('name', $newName)
                ->where('id', '!=', $folder->id)
                ->first();

            if ($existingFolder) {
                throw new Exception('同じ名前のフォルダが既に存在します。');
            }

            // 新しいパス生成
            $newPath = $folder->parent ? 
                str_replace('/' . $oldName, '/' . $newName, $folder->path) : 
                $newName;

            // フォルダ更新
            $folder->update([
                'name' => $newName,
                'path' => $newPath,
            ]);

            // 子フォルダのパス更新
            $this->updateChildrenPaths($folder, $oldPath, $newPath);

            // アクティビティログ
            $this->activityLogService->logDocumentFolderRenamed(
                $folder->id,
                $oldName,
                $newName,
                $folder->facility_id
            );

            DB::commit();

            Log::info('Document folder renamed successfully', [
                'folder_id' => $folder->id,
                'old_name' => $oldName,
                'new_name' => $newName,
                'user_id' => $user->id,
            ]);

            return $folder->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document folder rename failed', [
                'folder_id' => $folder->id,
                'new_name' => $newName,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('フォルダ名の変更に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フォルダ削除
     */
    public function deleteFolder(DocumentFolder $folder, User $user): bool
    {
        try {
            DB::beginTransaction();

            // 削除可能性チェック
            if (!$folder->canDelete()) {
                throw new Exception('このフォルダは削除できません。サブフォルダまたはファイルが存在します。');
            }

            $folderName = $folder->name;
            $facilityId = $folder->facility_id;

            // 物理ディレクトリ削除
            $physicalPath = "facility_{$facilityId}/folder_{$folder->id}";
            $this->fileHandlingService->deleteDirectory($physicalPath);

            // データベースから削除
            $folder->delete();

            // アクティビティログ
            $this->activityLogService->logDocumentFolderDeleted(
                $folder->id,
                $folderName,
                $facilityId
            );

            DB::commit();

            Log::info('Document folder deleted successfully', [
                'folder_id' => $folder->id,
                'name' => $folderName,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document folder deletion failed', [
                'folder_id' => $folder->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('フォルダの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ファイルアップロード
     */
    public function uploadFile(Facility $facility, ?DocumentFolder $folder, UploadedFile $file, User $user): DocumentFile
    {
        try {
            DB::beginTransaction();

            // ディレクトリパス生成
            $directory = "facility_{$facility->id}/" . ($folder ? "folder_{$folder->id}" : 'root');

            // ファイルアップロード
            $uploadResult = $this->fileHandlingService->uploadFile($file, $directory, 'facility_document');

            // データベース保存
            $documentFile = DocumentFile::create([
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $uploadResult['stored_filename'],
                'file_path' => $uploadResult['path'],
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'file_extension' => strtolower($file->getClientOriginalExtension()),
                'uploaded_by' => $user->id,
            ]);

            // アクティビティログ
            $this->activityLogService->log(
                'upload',
                'document_file',
                $documentFile->id,
                "ファイル「{$file->getClientOriginalName()}」をアップロードしました"
            );

            DB::commit();

            Log::info('Document file uploaded successfully', [
                'facility_id' => $facility->id,
                'file_id' => $documentFile->id,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'user_id' => $user->id,
            ]);

            return $documentFile;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document file upload failed', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'file_name' => $file->getClientOriginalName(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('ファイルのアップロードに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ファイル削除
     */
    public function deleteFile(DocumentFile $file, User $user): bool
    {
        try {
            DB::beginTransaction();

            $fileName = $file->original_name;
            $facilityId = $file->facility_id;

            // 物理ファイル削除
            $this->fileHandlingService->deleteFile($file->file_path);

            // データベースから削除
            $file->delete();

            // アクティビティログ
            $this->activityLogService->log(
                'delete',
                'document_file',
                $file->id,
                "ファイル「{$fileName}」を削除しました"
            );

            DB::commit();

            Log::info('Document file deleted successfully', [
                'file_id' => $file->id,
                'name' => $fileName,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document file deletion failed', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('ファイルの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ファイル名変更
     */
    public function renameFile(DocumentFile $file, string $newName, User $user): DocumentFile
    {
        try {
            DB::beginTransaction();

            $oldName = $file->original_name;

            // 同一フォルダ内での名前重複チェック
            $existingFile = DocumentFile::where('facility_id', $file->facility_id)
                ->where('folder_id', $file->folder_id)
                ->where('original_name', $newName)
                ->where('id', '!=', $file->id)
                ->first();

            if ($existingFile) {
                throw new Exception('同じ名前のファイルが既に存在します。');
            }

            // ファイル名更新
            $file->update(['original_name' => $newName]);

            // アクティビティログ
            $this->activityLogService->logDocumentFileRenamed(
                $file->id,
                $oldName,
                $newName,
                $file->facility_id
            );

            DB::commit();

            Log::info('Document file renamed successfully', [
                'file_id' => $file->id,
                'old_name' => $oldName,
                'new_name' => $newName,
                'user_id' => $user->id,
            ]);

            return $file->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document file rename failed', [
                'file_id' => $file->id,
                'new_name' => $newName,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('ファイル名の変更に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ファイル移動
     */
    public function moveFile(DocumentFile $file, ?DocumentFolder $newFolder, User $user): DocumentFile
    {
        try {
            DB::beginTransaction();

            $oldFolderId = $file->folder_id;
            $oldFolderName = $file->folder ? $file->folder->name : 'ルート';
            $newFolderName = $newFolder ? $newFolder->name : 'ルート';

            // 新しいディレクトリパス
            $newDirectory = "facility_{$file->facility_id}/" . ($newFolder ? "folder_{$newFolder->id}" : 'root');
            $newPath = $newDirectory . '/' . $file->stored_name;

            // 物理ファイル移動
            $this->fileHandlingService->moveFile($file->file_path, $newPath);

            // データベース更新
            $file->update([
                'folder_id' => $newFolder?->id,
                'file_path' => $newPath,
            ]);

            // アクティビティログ
            $this->activityLogService->log(
                'move',
                'document_file',
                $file->id,
                "ファイル「{$file->original_name}」を「{$oldFolderName}」から「{$newFolderName}」に移動しました"
            );

            DB::commit();

            Log::info('Document file moved successfully', [
                'file_id' => $file->id,
                'old_folder_id' => $oldFolderId,
                'new_folder_id' => $newFolder?->id,
                'user_id' => $user->id,
            ]);

            return $file->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document file move failed', [
                'file_id' => $file->id,
                'new_folder_id' => $newFolder?->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('ファイルの移動に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フォルダ内容取得（ソート・フィルタ・ページネーション対応）
     */
    public function getFolderContents(Facility $facility, ?DocumentFolder $folder, array $options = []): array
    {
        try {
            Log::info('DocumentService::getFolderContents called', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'options' => $options
            ]);
            
            // デフォルトオプション
            $options = array_merge([
                'sort_by' => 'name',
                'sort_direction' => 'asc',
                'filter_type' => null,
                'search' => null,
                'view_mode' => 'list',
                'page' => 1,
                'per_page' => 50,
                'load_stats' => true,
            ], $options);

            // N+1問題を解決するため、必要な関連データのみを事前に読み込み
            $foldersQuery = DocumentFolder::select([
                'id', 'name', 'path', 'created_at', 'updated_at', 'created_by'
            ])
                ->with(['creator:id,name'])
                ->where('facility_id', $facility->id)
                ->where('parent_id', $folder?->id);

            $filesQuery = DocumentFile::select([
                'id', 'original_name', 'file_size', 'file_extension', 'mime_type',
                'created_at', 'updated_at', 'uploaded_by', 'file_path'
            ])
                ->with(['uploader:id,name'])
                ->where('facility_id', $facility->id)
                ->where('folder_id', $folder?->id);

            // 検索フィルター（インデックスを活用）
            if (!empty($options['search'])) {
                $search = $options['search'];
                $foldersQuery->where('name', 'like', "%{$search}%");
                $filesQuery->where('original_name', 'like', "%{$search}%");
            }

            // ファイルタイプフィルター（インデックスを活用）
            if (!empty($options['filter_type']) && $options['filter_type'] !== 'all') {
                $filesQuery->where('file_extension', $options['filter_type']);
            }

            // ソート適用（フォルダ優先表示を考慮）
            $this->applySorting($foldersQuery, $filesQuery, $options);

            // ページネーション適用
            $perPage = min($options['per_page'], 100); // 最大100件に制限
            $page = max(1, $options['page']);

            // フォルダは常に全件取得（通常少数のため）
            $folders = $foldersQuery->get();

            // ファイルはページネーション適用
            $filesPaginated = $filesQuery->paginate($perPage, ['*'], 'page', $page);
            $files = $filesPaginated->items();

            // 表示用データ整形（最適化）
            $foldersData = $this->formatFoldersData($folders);
            
            // ファイル処理は個別にエラーハンドリング
            try {
                $filesData = $this->formatFilesData($files, $facility);
            } catch (\Exception $e) {
                Log::warning('Failed to format files data, returning empty array', [
                    'facility_id' => $facility->id,
                    'folder_id' => $folder?->id,
                    'files_count' => count($files),
                    'error' => $e->getMessage()
                ]);
                $filesData = [];
            }

            $result = [
                'folders' => $foldersData,
                'files' => $filesData,
                'current_folder' => $folder ? [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                ] : null,
                'breadcrumbs' => $this->getBreadcrumbs($folder),
                'pagination' => [
                    'current_page' => $filesPaginated->currentPage(),
                    'last_page' => $filesPaginated->lastPage(),
                    'per_page' => $filesPaginated->perPage(),
                    'total' => $filesPaginated->total(),
                    'has_more_pages' => $filesPaginated->hasMorePages(),
                ],
                'sort_options' => [
                    'sort_by' => $options['sort_by'],
                    'sort_direction' => $options['sort_direction'],
                    'view_mode' => $options['view_mode'],
                    'filter_type' => $options['filter_type'],
                    'search' => $options['search'],
                ],
            ];

            // 統計情報は必要な場合のみ取得（パフォーマンス最適化）
            if ($options['load_stats']) {
                $result['stats'] = $this->getFolderStats($facility, $folder);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to get folder contents', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'options' => $options,
                'error' => $e->getMessage(),
            ]);

            return [
                'folders' => [],
                'files' => [],
                'current_folder' => null,
                'breadcrumbs' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $options['per_page'],
                    'total' => 0,
                    'has_more_pages' => false,
                ],
                'stats' => [],
                'sort_options' => $options,
            ];
        }
    }

    /**
     * パンくずナビゲーション生成
     */
    public function getBreadcrumbs(?DocumentFolder $folder): array
    {
        if (!$folder) {
            return [
                [
                    'name' => 'ルート',
                    'id' => null,
                    'is_current' => true,
                ]
            ];
        }

        $breadcrumbs = [];
        $current = $folder;

        // 階層を遡って構築
        while ($current) {
            array_unshift($breadcrumbs, [
                'name' => $current->name,
                'id' => $current->id,
                'is_current' => $current->id === $folder->id,
            ]);
            $current = $current->parent;
        }

        // ルートを先頭に追加
        array_unshift($breadcrumbs, [
            'name' => 'ルート',
            'id' => null,
            'is_current' => false,
        ]);

        return $breadcrumbs;
    }



    /**
     * フォルダ統計情報取得
     */
    private function getFolderStats(Facility $facility, ?DocumentFolder $folder): array
    {
        try {
            $query = DocumentFile::where('facility_id', $facility->id);
            
            if ($folder) {
                $query->where('folder_id', $folder->id);
            } else {
                $query->whereNull('folder_id');
            }

            $fileCount = $query->count();
            $totalSize = $query->sum('file_size');

            $folderCount = DocumentFolder::where('facility_id', $facility->id)
                ->where('parent_id', $folder?->id)
                ->count();

            return [
                'file_count' => $fileCount,
                'folder_count' => $folderCount,
                'total_size' => $totalSize,
                'formatted_size' => $this->fileHandlingService->formatFileSize($totalSize),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get folder stats', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'file_count' => 0,
                'folder_count' => 0,
                'total_size' => 0,
                'formatted_size' => '0 B',
            ];
        }
    }

    /**
     * 利用可能なファイルタイプ取得
     */
    public function getAvailableFileTypes(Facility $facility): array
    {
        try {
            $fileTypes = DocumentFile::where('facility_id', $facility->id)
                ->selectRaw('file_extension, COUNT(*) as count')
                ->groupBy('file_extension')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'extension' => $item->file_extension,
                        'count' => $item->count,
                        'label' => strtoupper($item->file_extension) . ' ファイル (' . $item->count . ')',
                    ];
                });

            return $fileTypes->toArray();

        } catch (Exception $e) {
            Log::error('Failed to get available file types', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * ソート適用（フォルダ優先表示対応）
     */
    private function applySorting($foldersQuery, $filesQuery, array $options): void
    {
        $sortBy = $options['sort_by'];
        $direction = $options['sort_direction'];

        switch ($sortBy) {
            case 'name':
                // フォルダは常に名前でソート（フォルダ優先表示）
                $foldersQuery->orderBy('name', $direction);
                $filesQuery->orderBy('original_name', $direction);
                break;
                
            case 'date':
                // 作成日時でソート
                $foldersQuery->orderBy('created_at', $direction)
                           ->orderBy('name', 'asc'); // 同じ日時の場合は名前順
                $filesQuery->orderBy('created_at', $direction)
                         ->orderBy('original_name', 'asc'); // 同じ日時の場合は名前順
                break;
                
            case 'modified':
                // 更新日時でソート
                $foldersQuery->orderBy('updated_at', $direction)
                           ->orderBy('name', 'asc');
                $filesQuery->orderBy('updated_at', $direction)
                         ->orderBy('original_name', 'asc');
                break;
                
            case 'size':
                // フォルダは名前でソート（サイズ概念なし）、ファイルはサイズでソート
                $foldersQuery->orderBy('name', 'asc');
                $filesQuery->orderBy('file_size', $direction)
                         ->orderBy('original_name', 'asc'); // 同じサイズの場合は名前順
                break;
                
            case 'type':
                // フォルダは名前でソート、ファイルは拡張子でソート
                $foldersQuery->orderBy('name', 'asc');
                $filesQuery->orderBy('file_extension', $direction)
                         ->orderBy('original_name', 'asc'); // 同じ拡張子の場合は名前順
                break;
                
            default:
                // デフォルトは名前順（昇順）
                $foldersQuery->orderBy('name', 'asc');
                $filesQuery->orderBy('original_name', 'asc');
        }
    }

    /**
     * フォルダデータの最適化された整形
     */
    private function formatFoldersData($folders): array
    {
        return $folders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'type' => 'folder',
                'created_at' => $folder->created_at,
                'updated_at' => $folder->updated_at,
                'created_by' => $folder->creator->name ?? '不明',
                'path' => $folder->path,
                // 重い処理は遅延読み込みで対応
                'file_count' => null, // 必要時にAJAXで取得
                'has_children' => null, // 必要時にAJAXで取得
                'can_delete' => null, // 必要時にAJAXで取得
            ];
        })->toArray();
    }

    /**
     * ファイルデータの最適化された整形
     */
    private function formatFilesData($files, Facility $facility): array
    {
        return collect($files)->map(function ($file) use ($facility) {
            try {
                return [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'type' => 'file',
                    'size' => $file->file_size,
                    'formatted_size' => $file->getFormattedSize(),
                    'extension' => $file->file_extension,
                    'mime_type' => $file->mime_type,
                    'created_at' => $file->created_at,
                    'updated_at' => $file->updated_at,
                    'uploaded_by' => $file->uploader->name ?? '不明',
                    'download_url' => $file->getDownloadUrl(),
                    'can_preview' => $file->canPreview(),
                    'icon' => $file->getFileIcon(),
                    'color' => $file->getFileColor(),
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to format file data', [
                    'file_id' => $file->id,
                    'facility_id' => $facility->id,
                    'error' => $e->getMessage()
                ]);
                
                // エラーが発生した場合は基本情報のみ返す
                return [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'type' => 'file',
                    'size' => $file->file_size,
                    'formatted_size' => $file->getFormattedSize(),
                    'extension' => $file->file_extension,
                    'mime_type' => $file->mime_type,
                    'created_at' => $file->created_at,
                    'updated_at' => $file->updated_at,
                    'uploaded_by' => $file->uploader->name ?? '不明',
                    'download_url' => '#', // エラー時はダミーURL
                    'can_preview' => false,
                    'icon' => 'fas fa-file',
                    'color' => 'text-muted',
                ];
            }
        })->toArray();
    }

    /**
     * 大量データ対応のフォルダ内容取得（仮想スクロール用）
     */
    public function getFolderContentsVirtual(Facility $facility, ?DocumentFolder $folder, array $options = []): array
    {
        try {
            $options = array_merge([
                'offset' => 0,
                'limit' => 50,
                'sort_by' => 'name',
                'sort_direction' => 'asc',
                'filter_type' => null,
                'search' => null,
                'item_type' => 'all', // 'folders', 'files', 'all'
            ], $options);

            $results = [];
            $totalCount = 0;

            // フォルダとファイルを統合してソート・ページネーション
            if ($options['item_type'] === 'all' || $options['item_type'] === 'folders') {
                $foldersQuery = DocumentFolder::select([
                    'id', 'name', 'created_at', 'updated_at', 'created_by',
                    DB::raw("'folder' as item_type")
                ])
                    ->with(['creator:id,name'])
                    ->where('facility_id', $facility->id)
                    ->where('parent_id', $folder?->id);

                if (!empty($options['search'])) {
                    $foldersQuery->where('name', 'like', "%{$options['search']}%");
                }

                $folders = $foldersQuery->get();
                $results = array_merge($results, $folders->toArray());
            }

            if ($options['item_type'] === 'all' || $options['item_type'] === 'files') {
                $filesQuery = DocumentFile::select([
                    'id', 'original_name as name', 'file_size', 'file_extension',
                    'created_at', 'updated_at', 'uploaded_by as created_by',
                    DB::raw("'file' as item_type")
                ])
                    ->with(['uploader:id,name'])
                    ->where('facility_id', $facility->id)
                    ->where('folder_id', $folder?->id);

                if (!empty($options['search'])) {
                    $filesQuery->where('original_name', 'like', "%{$options['search']}%");
                }

                if (!empty($options['filter_type']) && $options['filter_type'] !== 'all') {
                    $filesQuery->where('file_extension', $options['filter_type']);
                }

                $files = $filesQuery->get();
                $results = array_merge($results, $files->toArray());
            }

            // ソート適用
            $results = collect($results)->sortBy(function ($item) use ($options) {
                switch ($options['sort_by']) {
                    case 'name':
                        return $item['name'];
                    case 'date':
                        return $item['created_at'];
                    case 'modified':
                        return $item['updated_at'];
                    case 'size':
                        return $item['file_size'] ?? 0;
                    default:
                        return $item['name'];
                }
            });

            if ($options['sort_direction'] === 'desc') {
                $results = $results->reverse();
            }

            $totalCount = $results->count();

            // ページネーション適用
            $results = $results->slice($options['offset'], $options['limit'])->values();

            return [
                'items' => $results->toArray(),
                'total_count' => $totalCount,
                'has_more' => ($options['offset'] + $options['limit']) < $totalCount,
                'offset' => $options['offset'],
                'limit' => $options['limit'],
            ];

        } catch (Exception $e) {
            Log::error('Failed to get virtual folder contents', [
                'facility_id' => $facility->id,
                'folder_id' => $folder?->id,
                'options' => $options,
                'error' => $e->getMessage(),
            ]);

            return [
                'items' => [],
                'total_count' => 0,
                'has_more' => false,
                'offset' => $options['offset'],
                'limit' => $options['limit'],
            ];
        }
    }

    /**
     * 子フォルダのパス更新（再帰処理）
     */
    private function updateChildrenPaths(DocumentFolder $folder, string $oldPath, string $newPath): void
    {
        $children = DocumentFolder::where('parent_id', $folder->id)->get();
        
        foreach ($children as $child) {
            $childOldPath = $child->path;
            $childNewPath = str_replace($oldPath, $newPath, $childOldPath);
            
            $child->update(['path' => $childNewPath]);
            
            // 再帰的に子フォルダのパスも更新
            $this->updateChildrenPaths($child, $childOldPath, $childNewPath);
        }
    }



    /**
     * フォルダツリーを取得（階層構造）
     */
    public function getFolderTree(Facility $facility): array
    {
        try {
            // 全フォルダを取得（パフォーマンス最適化のため一度に取得）
            $folders = DocumentFolder::where('facility_id', $facility->id)
                ->orderBy('path')
                ->get();

            // 階層構造を構築
            return $this->buildFolderTree($folders);

        } catch (Exception $e) {
            Log::error('Failed to get folder tree', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * フォルダツリーを構築（階層構造）
     */
    private function buildFolderTree($folders, $parentId = null): array
    {
        $tree = [];

        foreach ($folders as $folder) {
            if ($folder->parent_id == $parentId) {
                $folderData = [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'parent_id' => $folder->parent_id,
                    'children' => $this->buildFolderTree($folders, $folder->id)
                ];

                $tree[] = $folderData;
            }
        }

        return $tree;
    }

    /**
     * バッチ処理用のフォルダ削除（大量データ対応）
     */
    public function deleteFolderBatch(DocumentFolder $folder, User $user, int $batchSize = 100): bool
    {
        try {
            DB::beginTransaction();

            // 子フォルダを再帰的に削除
            $childFolders = DocumentFolder::where('parent_id', $folder->id)->get();
            foreach ($childFolders as $childFolder) {
                $this->deleteFolderBatch($childFolder, $user, $batchSize);
            }

            // フォルダ内のファイルをバッチで削除
            $fileIds = DocumentFile::where('folder_id', $folder->id)
                ->pluck('id')
                ->chunk($batchSize);

            foreach ($fileIds as $chunk) {
                $files = DocumentFile::whereIn('id', $chunk)->get();
                foreach ($files as $file) {
                    $this->deleteFile($file, $user);
                }
            }

            // フォルダ削除
            $folderName = $folder->name;
            $facilityId = $folder->facility_id;

            // 物理ディレクトリ削除
            $physicalPath = "facility_{$facilityId}/folder_{$folder->id}";
            $this->fileHandlingService->deleteDirectory($physicalPath);

            $folder->delete();

            // アクティビティログ
            $this->activityLogService->logDocumentFolderDeleted(
                $folder->id,
                $folderName,
                $facilityId
            );

            DB::commit();

            Log::info('Document folder batch deleted successfully', [
                'folder_id' => $folder->id,
                'name' => $folderName,
                'user_id' => $user->id,
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Document folder batch deletion failed', [
                'folder_id' => $folder->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('フォルダの削除に失敗しました: ' . $e->getMessage());
        }
    }


}