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
 * ライフライン設備専用ドキュメント管理サービス
 *
 * ライフライン設備の各カテゴリ（電気、ガス、水道等）に対応した
 * ドキュメント管理機能を提供します。
 */
class LifelineDocumentService
{
    protected DocumentService $documentService;
    protected FileHandlingService $fileHandlingService;
    protected ActivityLogService $activityLogService;

    // ライフライン設備カテゴリとフォルダ名のマッピング
    const CATEGORY_FOLDER_MAPPING = [
        'electrical' => '電気設備',
        'gas' => 'ガス設備',
        'water' => '水道設備',
        'elevator' => 'エレベーター設備',
        'hvac_lighting' => '空調・照明設備',
        'security_disaster' => '防犯・防災設備',
    ];

    // デフォルトサブフォルダ構成
    const DEFAULT_SUBFOLDERS = [
        'inspection_reports' => '点検報告書',
        'maintenance_records' => '保守記録',
        'manuals' => '取扱説明書',
        'certificates' => '証明書類',
        'past_reports' => '過去分報告書',
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
     * ライフライン設備カテゴリのルートフォルダを取得または作成
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
    {
        try {
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // 既存のルートフォルダを検索
            $rootFolder = DocumentFolder::where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            if ($rootFolder) {
                return $rootFolder;
            }

            // ルートフォルダを作成
            $rootFolder = $this->documentService->createFolder($facility, null, $categoryName, $user);

            // デフォルトサブフォルダを作成
            $this->createDefaultSubfolders($facility, $rootFolder, $user);

            Log::info('Lifeline category root folder created', [
                'facility_id' => $facility->id,
                'category' => $category,
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
                    $this->documentService->createFolder($facility, $parentFolder, $name, $user);
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to create some default subfolders', [
                'facility_id' => $facility->id,
                'parent_folder_id' => $parentFolder->id,
                'error' => $e->getMessage(),
            ]);
            // サブフォルダ作成失敗は致命的エラーではないため、ログのみ
        }
    }

    /**
     * ライフライン設備カテゴリのドキュメント一覧を取得
     */
    public function getCategoryDocuments(Facility $facility, string $category, array $options = []): array
    {
        try {
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得
            $rootFolder = DocumentFolder::where('facility_id', $facility->id)
                ->whereNull('parent_id')
                ->where('name', $categoryName)
                ->first();

            if (!$rootFolder) {
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
                $requestedFolder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $options['folder_id'])
                    ->first();

                if ($requestedFolder && $this->isFolderInCategory($requestedFolder, $rootFolder)) {
                    $currentFolder = $requestedFolder;
                }
            }

            // ドキュメントサービスを使用してフォルダ内容を取得
            $result = $this->documentService->getFolderContents($facility, $currentFolder, $options);

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
     * ライフライン設備カテゴリにファイルをアップロード
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

            // ファイルアップロード
            $documentFile = $this->documentService->uploadFile($facility, $targetFolder, $file, $user);

            // アクティビティログ
            $this->activityLogService->log(
                'upload',
                'lifeline_document',
                $documentFile->id,
                "ライフライン設備「{$category}」にファイル「{$file->getClientOriginalName()}」をアップロードしました"
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

            Log::error('Lifeline category file upload failed', [
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
     * ライフライン設備カテゴリにフォルダを作成
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

            // 親フォルダを決定
            $parentFolder = $rootFolder;
            if ($parentFolderId) {
                $requestedParent = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $parentFolderId)
                    ->first();

                if ($requestedParent && $this->isFolderInCategory($requestedParent, $rootFolder)) {
                    $parentFolder = $requestedParent;
                }
            }

            // フォルダ作成
            $newFolder = $this->documentService->createFolder($facility, $parentFolder, $folderName, $user);

            // アクティビティログ
            $this->activityLogService->log(
                'create',
                'lifeline_document_folder',
                $newFolder->id,
                "ライフライン設備「{$category}」にフォルダ「{$folderName}」を作成しました"
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'フォルダを作成しました。',
                'data' => [
                    'folder' => $newFolder,
                    'category' => $category,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Lifeline category folder creation failed', [
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
     * ライフライン設備カテゴリの統計情報を取得
     */
    public function getCategoryStats(Facility $facility, string $category): array
    {
        try {
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得
            $rootFolder = DocumentFolder::where('facility_id', $facility->id)
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

            // ファイル統計
            $fileStats = DocumentFile::where('facility_id', $facility->id)
                ->whereIn('folder_id', $folderIds)
                ->selectRaw('COUNT(*) as count, SUM(file_size) as total_size')
                ->first();

            // フォルダ統計
            $folderCount = count($folderIds) - 1; // ルートフォルダを除く

            // 最近のファイル
            $recentFiles = DocumentFile::where('facility_id', $facility->id)
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
     * 利用可能なライフライン設備カテゴリ一覧を取得
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
            $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

            // カテゴリのルートフォルダを取得
            $rootFolder = DocumentFolder::where('facility_id', $facility->id)
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

            // ファイル検索
            $filesQuery = DocumentFile::where('facility_id', $facility->id)
                ->whereIn('folder_id', $folderIds)
                ->where('original_name', 'like', "%{$query}%")
                ->with(['uploader:id,name', 'folder:id,name']);

            // フォルダ検索
            $foldersQuery = DocumentFolder::where('facility_id', $facility->id)
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