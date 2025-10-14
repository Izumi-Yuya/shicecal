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

            Log::info('Maintenance category root folder created', [
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
        }
    }

    /**
     * 修繕履歴カテゴリのドキュメント一覧を取得
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

            // ファイルアップロード
            $documentFile = $this->documentService->uploadFile($facility, $targetFolder, $file, $user);

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
}
