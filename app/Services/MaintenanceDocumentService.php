<?php

namespace App\Services;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 修繕履歴専用ドキュメント管理サービス
 *
 * 修繕履歴の各カテゴリ（外装、内装、その他）に対応した
 * ドキュメント管理機能を提供します。
 */
class MaintenanceDocumentService
{
    protected DocumentService $documentService;
    protected FileHandlingService $fileHandlingService;
    protected ActivityLogService $activityLogService;

    // 修繕履歴カテゴリとフォルダ名のマッピング
    const CATEGORY_FOLDER_MAPPING = [
        'exterior' => '外装',
        'interior' => '内装リニューアル',
        'summer_condensation' => '夏型結露',
        'other' => 'その他',
    ];

    // デフォルトサブフォルダ構成
    const DEFAULT_SUBFOLDERS = [
        'contracts' => '契約書',
        'estimates' => '見積書',
        'invoices' => '請求書',
        'photos' => '施工写真',
        'reports' => '報告書',
        'warranties' => '保証書',
    ];

    public function __construct(
        DocumentService $documentService,
        FileHandlingService $fileHandlingService,
        ActivityLogService $activityLogService
    ) {
        $this->documentService = $documentService;
        $this->fileHandlingService = $fileHandlingService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * 修繕履歴カテゴリのルートフォルダを取得または作成
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
    {
        try {
            $categoryValue = 'maintenance_' . $category;
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // 既存のルートフォルダを検索（カテゴリで識別）
            $rootFolder = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            if ($rootFolder) {
                return $rootFolder;
            }

            // ルートフォルダを作成（カテゴリを設定）
            $rootFolder = DocumentFolder::create([
                'facility_id' => $facility->id,
                'parent_id' => null,
                'category' => $categoryValue,
                'name' => $categoryName,
                'path' => $categoryName,
                'created_by' => $user->id,
            ]);

            // デフォルトサブフォルダを作成
            $this->createDefaultSubfolders($facility, $rootFolder, $user);

            Log::info('Maintenance category root folder created', [
                'facility_id' => $facility->id,
                'category' => $category,
                'category_value' => $categoryValue,
                'folder_id' => $rootFolder->id,
                'user_id' => $user->id,
            ]);

            return $rootFolder;

        } catch (Exception $e) {
            Log::error('Failed to get or create category root folder', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('カテゴリフォルダの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * デフォルトサブフォルダを作成
     */
    protected function createDefaultSubfolders(Facility $facility, DocumentFolder $parentFolder, User $user): void
    {
        try {
            foreach (self::DEFAULT_SUBFOLDERS as $key => $name) {
                // 既存チェック
                $existingFolder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('parent_id', $parentFolder->id)
                    ->where('name', $name)
                    ->first();

                if (!$existingFolder) {
                    // サブフォルダ作成時に親の`category`を継承
                    DocumentFolder::create([
                        'facility_id' => $facility->id,
                        'parent_id' => $parentFolder->id,
                        'category' => $parentFolder->category,
                        'name' => $name,
                        'path' => $parentFolder->path . '/' . $name,
                        'created_by' => $user->id,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to create some default subfolders', [
                'facility_id' => $facility->id,
                'parent_folder_id' => $parentFolder->id,
                'parent_category' => $parentFolder->category,
                'error' => $e->getMessage(),
            ]);
            // サブフォルダ作成失敗は致命的エラーではないため、ログのみ
        }
    }

    /**
     * 修繕履歴カテゴリのドキュメント一覧を取得
     */
    public function getCategoryDocuments(Facility $facility, string $category, array $options = []): array
    {
        try {
            $categoryValue = 'maintenance_' . $category;
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得（カテゴリでフィルタリング）
            $rootFolder = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            // ルートフォルダが存在しない場合は、空のデータを返す
            // （フォルダは作成時に自動的に作成される）
            if (!$rootFolder) {
                Log::info('Category root folder not found, returning empty data', [
                    'facility_id' => $facility->id,
                    'category' => $category,
                    'category_value' => $categoryValue,
                    'category_name' => $categoryName,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'folders' => [],
                        'files' => [],
                        'current_folder' => null,
                        'breadcrumbs' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => $options['per_page'] ?? 50,
                            'total' => 0,
                            'has_more_pages' => false,
                        ],
                        'stats' => [
                            'file_count' => 0,
                            'folder_count' => 0,
                            'total_size' => 0,
                            'formatted_size' => '0 B',
                        ],
                    ],
                    'category' => $category,
                    'category_name' => $categoryName,
                ];
            }

            // 指定されたフォルダIDがある場合はそのフォルダを取得
            $currentFolder = $rootFolder;
            if (!empty($options['folder_id'])) {
                $requestedFolder = DocumentFolder::maintenance($category)
                    ->where('facility_id', $facility->id)
                    ->where('id', $options['folder_id'])
                    ->first();

                if ($requestedFolder && $this->isFolderInCategory($requestedFolder, $rootFolder)) {
                    $currentFolder = $requestedFolder;
                }
            }

            // フォルダとファイルのクエリに`maintenance($category)`スコープを適用
            $perPage = min($options['per_page'] ?? 50, 100);
            $page = $options['page'] ?? 1;

            // フォルダ取得（カテゴリでフィルタリング）
            $foldersQuery = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->where('parent_id', $currentFolder->id)
                ->with(['creator:id,name']);

            if (!empty($options['sort_by'])) {
                $sortDirection = $options['sort_direction'] ?? 'asc';
                $foldersQuery->orderBy($options['sort_by'], $sortDirection);
            } else {
                $foldersQuery->orderBy('name', 'asc');
            }

            $folders = $foldersQuery->get();

            // ファイル取得（カテゴリでフィルタリング）
            $filesQuery = DocumentFile::maintenance($category)
                ->where('facility_id', $facility->id)
                ->where('folder_id', $currentFolder->id)
                ->with(['uploader:id,name']);

            if (!empty($options['file_type'])) {
                $filesQuery->where('file_extension', $options['file_type']);
            }

            if (!empty($options['sort_by'])) {
                $sortDirection = $options['sort_direction'] ?? 'asc';
                $filesQuery->orderBy($options['sort_by'], $sortDirection);
            } else {
                $filesQuery->orderBy('original_name', 'asc');
            }

            $filesPaginated = $filesQuery->paginate($perPage, ['*'], 'page', $page);

            // パンくずリスト
            $breadcrumbs = $currentFolder->getBreadcrumbs();

            // 統計情報
            $stats = [
                'file_count' => $currentFolder->getDirectFileCount(),
                'folder_count' => $folders->count(),
                'total_size' => $currentFolder->getTotalSize(),
                'formatted_size' => $this->fileHandlingService->formatFileSize($currentFolder->getTotalSize()),
            ];

            $result = [
                'folders' => $folders->toArray(),
                'files' => $filesPaginated->items(),
                'current_folder' => $currentFolder,
                'breadcrumbs' => $breadcrumbs,
                'pagination' => [
                    'current_page' => $filesPaginated->currentPage(),
                    'last_page' => $filesPaginated->lastPage(),
                    'per_page' => $filesPaginated->perPage(),
                    'total' => $filesPaginated->total(),
                    'has_more_pages' => $filesPaginated->hasMorePages(),
                ],
                'stats' => $stats,
            ];

            Log::info('Category documents retrieved', [
                'facility_id' => $facility->id,
                'category' => $category,
                'category_value' => $categoryValue,
                'root_folder_id' => $rootFolder->id,
                'current_folder_id' => $currentFolder->id,
                'folders_count' => count($result['folders'] ?? []),
                'files_count' => count($result['files'] ?? []),
            ]);

            return [
                'success' => true,
                'data' => $result,
                'category' => $category,
                'category_name' => $categoryName,
                'root_folder_id' => $rootFolder->id,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get category documents', [
                'facility_id' => $facility->id,
                'category' => $category,
                'options' => $options,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ドキュメントの取得に失敗しました: ' . $e->getMessage(),
                'category' => $category,
            ];
        }
    }

    /**
     * フォルダがカテゴリ内に属するかチェック
     */
    protected function isFolderInCategory(DocumentFolder $folder, DocumentFolder $rootFolder): bool
    {
        $current = $folder;
        while ($current) {
            if ($current->id === $rootFolder->id) {
                return true;
            }
            $current = $current->parent;
        }
        return false;
    }

    /**
     * 修繕履歴カテゴリにファイルをアップロード
     */
    public function uploadCategoryFile(
        Facility $facility,
        string $category,
        UploadedFile $file,
        User $user,
        ?int $folderId = null
    ): array {
        try {
            DB::beginTransaction();

            // カテゴリのルートフォルダを取得または作成
            $rootFolder = $this->getOrCreateCategoryRootFolder($facility, $category, $user);

            // アップロード先フォルダを決定
            $targetFolder = $rootFolder;
            if ($folderId) {
                $requestedFolder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $folderId)
                    ->first();

                if ($requestedFolder && $this->isFolderInCategory($requestedFolder, $rootFolder)) {
                    $targetFolder = $requestedFolder;
                }
            }

            // ファイルアップロード時にフォルダの`category`を継承
            $documentFile = DocumentFile::create([
                'facility_id' => $facility->id,
                'folder_id' => $targetFolder->id,
                'category' => $targetFolder->category,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $this->fileHandlingService->generateUniqueFileName($file),
                'file_path' => $this->fileHandlingService->storeFile($file, 'documents'),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
                'uploaded_by' => $user->id,
            ]);

            // アクティビティログ
            $this->activityLogService->log(
                'upload',
                'maintenance_document',
                $documentFile->id,
                "修繕履歴「{$category}」にファイル「{$file->getClientOriginalName()}」をアップロードしました"
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'ファイルのアップロードが完了しました。',
                'data' => [
                    'file' => $documentFile,
                    'category' => $category,
                    'folder_id' => $targetFolder->id,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Maintenance category file upload failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'file_name' => $file->getClientOriginalName(),
                'folder_id' => $folderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ファイルのアップロードに失敗しました: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 修繕履歴カテゴリにフォルダを作成
     */
    public function createCategoryFolder(
        Facility $facility,
        string $category,
        string $folderName,
        User $user,
        ?int $parentFolderId = null
    ): array {
        try {
            DB::beginTransaction();

            // カテゴリのルートフォルダを取得または作成
            $rootFolder = $this->getOrCreateCategoryRootFolder($facility, $category, $user);

            Log::info('Root folder obtained for category', [
                'facility_id' => $facility->id,
                'category' => $category,
                'root_folder_id' => $rootFolder->id,
                'root_folder_name' => $rootFolder->name,
                'root_folder_path' => $rootFolder->path,
                'root_folder_category' => $rootFolder->category,
            ]);

            // 親フォルダを決定
            $parentFolder = $rootFolder;
            if ($parentFolderId) {
                $requestedParent = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $parentFolderId)
                    ->first();

                if ($requestedParent && $this->isFolderInCategory($requestedParent, $rootFolder)) {
                    $parentFolder = $requestedParent;
                    Log::info('Using requested parent folder', [
                        'parent_folder_id' => $parentFolder->id,
                        'parent_folder_name' => $parentFolder->name,
                        'parent_folder_category' => $parentFolder->category,
                    ]);
                }
            } else {
                Log::info('No parent folder specified, using root folder as parent', [
                    'parent_folder_id' => $parentFolder->id,
                    'parent_folder_name' => $parentFolder->name,
                    'parent_folder_category' => $parentFolder->category,
                ]);
            }

            // フォルダ作成（親のカテゴリを継承）
            $newFolder = DocumentFolder::create([
                'facility_id' => $facility->id,
                'parent_id' => $parentFolder->id,
                'category' => $parentFolder->category,
                'name' => $folderName,
                'path' => $parentFolder->path . '/' . $folderName,
                'created_by' => $user->id,
            ]);

            Log::info('Maintenance category folder created successfully', [
                'facility_id' => $facility->id,
                'category' => $category,
                'folder_id' => $newFolder->id,
                'folder_name' => $folderName,
                'folder_category' => $newFolder->category,
                'parent_folder_id' => $parentFolder->id,
                'root_folder_id' => $rootFolder->id,
                'user_id' => $user->id,
            ]);

            // アクティビティログ
            $this->activityLogService->log(
                'create',
                'maintenance_document_folder',
                $newFolder->id,
                "修繕履歴「{$category}」にフォルダ「{$folderName}」を作成しました"
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'フォルダを作成しました。',
                'data' => [
                    'folder' => $newFolder,
                    'category' => $category,
                    'root_folder_id' => $rootFolder->id,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Maintenance category folder creation failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'folder_name' => $folderName,
                'parent_folder_id' => $parentFolderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'フォルダの作成に失敗しました: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * カテゴリ内の全フォルダIDを再帰的に取得
     */
    protected function getCategoryFolderIds(DocumentFolder $rootFolder): array
    {
        $folderIds = [$rootFolder->id];

        $childFolders = DocumentFolder::where('facility_id', $rootFolder->facility_id)
            ->where('path', 'like', $rootFolder->path . '/%')
            ->pluck('id')
            ->toArray();

        return array_merge($folderIds, $childFolders);
    }

    /**
     * 修繕履歴カテゴリの統計情報を取得
     */
    public function getCategoryStats(Facility $facility, string $category): array
    {
        try {
            $categoryValue = 'maintenance_' . $category;
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得（カテゴリでフィルタリング）
            $rootFolder = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            if (!$rootFolder) {
                return [
                    'file_count' => 0,
                    'folder_count' => 0,
                    'total_size' => 0,
                    'formatted_size' => '0 B',
                    'recent_files' => [],
                ];
            }

            // カテゴリ内の全フォルダIDを取得
            $folderIds = $this->getCategoryFolderIds($rootFolder);

            // ファイル統計（カテゴリでフィルタリング）
            $fileStats = DocumentFile::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereIn('folder_id', $folderIds)
                ->selectRaw('COUNT(*) as count, SUM(file_size) as total_size')
                ->first();

            // フォルダ統計（カテゴリでフィルタリング）
            $folderCount = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereIn('id', $folderIds)
                ->where('id', '!=', $rootFolder->id) // ルートフォルダを除く
                ->count();

            // 最近のファイル（カテゴリでフィルタリング）
            $recentFiles = DocumentFile::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereIn('folder_id', $folderIds)
                ->with(['uploader:id,name', 'folder:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name,
                        'size' => $file->getFormattedSize(),
                        'uploaded_at' => $file->created_at,
                        'uploader' => $file->uploader->name ?? '不明',
                        'folder' => $file->folder->name ?? 'ルート',
                    ];
                });

            return [
                'file_count' => $fileStats->count ?? 0,
                'folder_count' => $folderCount,
                'total_size' => $fileStats->total_size ?? 0,
                'formatted_size' => $this->fileHandlingService->formatFileSize($fileStats->total_size ?? 0),
                'recent_files' => $recentFiles->toArray(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to get category stats', [
                'facility_id' => $facility->id,
                'category' => $category,
                'category_value' => 'maintenance_' . $category,
                'error' => $e->getMessage(),
            ]);

            return [
                'file_count' => 0,
                'folder_count' => 0,
                'total_size' => 0,
                'formatted_size' => '0 B',
                'recent_files' => [],
            ];
        }
    }

    /**
     * 利用可能な修繕履歴カテゴリ一覧を取得
     */
    public function getAvailableCategories(): array
    {
        return array_map(function ($key, $name) {
            return [
                'key' => $key,
                'name' => $name,
                'folder_name' => $name,
            ];
        }, array_keys(self::CATEGORY_FOLDER_MAPPING), self::CATEGORY_FOLDER_MAPPING);
    }

    /**
     * カテゴリ内のファイル検索
     */
    public function searchCategoryFiles(Facility $facility, string $category, string $query, array $options = []): array
    {
        try {
            $categoryValue = 'maintenance_' . $category;
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得（カテゴリでフィルタリング）
            $rootFolder = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            if (!$rootFolder) {
                return [
                    'success' => true,
                    'data' => [
                        'files' => [],
                        'folders' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => $options['per_page'] ?? 20,
                            'total' => 0,
                            'has_more_pages' => false,
                        ],
                        'total_count' => 0,
                    ],
                    'query' => $query,
                    'category' => $category,
                ];
            }

            // カテゴリ内の全フォルダIDを取得
            $folderIds = $this->getCategoryFolderIds($rootFolder);

            // ファイル検索（カテゴリでフィルタリング）
            $filesQuery = DocumentFile::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereIn('folder_id', $folderIds)
                ->where('original_name', 'like', "%{$query}%")
                ->with(['uploader:id,name', 'folder:id,name']);

            // フォルダ検索（カテゴリでフィルタリング）
            $foldersQuery = DocumentFolder::maintenance($category)
                ->where('facility_id', $facility->id)
                ->whereIn('id', $folderIds)
                ->where('name', 'like', "%{$query}%")
                ->with(['creator:id,name']);

            // ページネーション
            $perPage = min($options['per_page'] ?? 20, 100);
            $files = $filesQuery->paginate($perPage);
            $folders = $foldersQuery->limit($perPage)->get();

            return [
                'success' => true,
                'data' => [
                    'files' => $files->items(),
                    'folders' => $folders->toArray(),
                    'pagination' => [
                        'current_page' => $files->currentPage(),
                        'last_page' => $files->lastPage(),
                        'per_page' => $files->perPage(),
                        'total' => $files->total(),
                        'has_more_pages' => $files->hasMorePages(),
                    ],
                    'total_count' => $files->total() + $folders->count(),
                ],
                'query' => $query,
                'category' => $category,
            ];

        } catch (Exception $e) {
            Log::error('Category file search failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'category_value' => $categoryValue,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'ファイル検索に失敗しました: ' . $e->getMessage(),
            ];
        }
    }
}
