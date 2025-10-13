<?php

namespace App\Http\Controllers;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Services\DocumentService;
use App\Services\LifelineDocumentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LifelineDocumentController extends Controller
{
    protected LifelineDocumentService $lifelineDocumentService;
    protected DocumentService $documentService;

    public function __construct(
        LifelineDocumentService $lifelineDocumentService,
        DocumentService $documentService
    ) {
        $this->lifelineDocumentService = $lifelineDocumentService;
        $this->documentService = $documentService;
    }

    /**
     * ライフライン設備カテゴリのドキュメント一覧を表示
     */
    public function index(Request $request, Facility $facility, string $category): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // カテゴリの正規化
            $normalizedCategory = str_replace('-', '_', $category);
            if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ], 404);
            }

            $options = [
                'folder_id' => $request->input('folder_id'),
                'sort_by' => $request->input('sort_by', 'name'),
                'sort_direction' => $request->input('sort_direction', 'asc'),
                'filter_type' => $request->input('filter_type'),
                'search' => $request->input('search'),
                'view_mode' => $request->input('view_mode', 'list'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 50),
                'load_stats' => $request->boolean('load_stats', true),
            ];

            $result = $this->lifelineDocumentService->getCategoryDocuments($facility, $normalizedCategory, $options);

            return response()->json($result);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設のドキュメントを表示する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document index failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ドキュメント一覧の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * ライフライン設備カテゴリにファイルをアップロード
     */
    public function uploadFile(Request $request, Facility $facility, string $category): JsonResponse
    {
        try {
            // デバッグ用：リクエスト内容をログ出力
            Log::info('Lifeline document upload request', [
                'facility_id' => $facility->id,
                'category' => $category,
                'has_files' => $request->hasFile('files'),
                'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
                'all_input' => $request->all(),
                'file_keys' => array_keys($request->allFiles()),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
            ]);

            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // カテゴリの正規化
            $normalizedCategory = str_replace('-', '_', $category);
            if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ], 404);
            }

            $request->validate([
                'files' => 'required|array|min:1',
                'files.*' => [
                    'required',
                    'file',
                    'max:10240', // 10MB
                    'mimes:pdf',
                ],
                'folder_id' => 'nullable|integer|exists:document_folders,id',
            ]);

            $results = [];
            $files = $request->file('files');
            
            foreach ($files as $file) {
                $result = $this->lifelineDocumentService->uploadCategoryFile(
                    $facility,
                    $normalizedCategory,
                    $file,
                    auth()->user(),
                    $request->input('folder_id')
                );
                
                if (!$result['success']) {
                    return response()->json($result, 422);
                }
                
                $results[] = $result;
            }

            $fileCount = count($files);
            $message = $fileCount === 1 
                ? 'ファイルのアップロードが完了しました。'
                : "{$fileCount}個のファイルのアップロードが完了しました。";

            $result = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'files' => array_column($results, 'data'),
                    'category' => $normalizedCategory,
                    'count' => $fileCount,
                ],
            ];

            return response()->json($result, $result['success'] ? 200 : 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Lifeline document upload validation failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設にファイルをアップロードする権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document upload failed', [
                'facility_id' => $facility->id,
                'category' => $category,
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
     * ライフライン設備カテゴリにフォルダを作成
     */
    public function createFolder(Request $request, Facility $facility, string $category): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // カテゴリの正規化
            $normalizedCategory = str_replace('-', '_', $category);
            if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'parent_folder_id' => 'nullable|integer|exists:document_folders,id',
            ]);

            $result = $this->lifelineDocumentService->createCategoryFolder(
                $facility,
                $normalizedCategory,
                $request->input('name'),
                auth()->user(),
                $request->input('parent_folder_id')
            );

            return response()->json($result, $result['success'] ? 201 : 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設にフォルダを作成する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document folder creation failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダの作成に失敗しました。',
            ], 500);
        }
    }

    /**
     * フォルダ名を変更
     */
    public function renameFolder(Request $request, Facility $facility, string $category, $folderId): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // フォルダを取得
            $folder = DocumentFolder::where('id', $folderId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$folder) {
                return response()->json([
                    'success' => false,
                    'message' => 'フォルダが見つかりません。',
                ], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $folder = $this->documentService->renameFolder($folder, $request->input('name'), auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'フォルダ名を変更しました。',
                'data' => ['folder' => $folder],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'フォルダ名を変更する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document folder rename failed', [
                'facility_id' => $facility->id,
                'folder_id' => $folder->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダ名の変更に失敗しました。',
            ], 500);
        }
    }

    /**
     * フォルダを削除
     */
    public function deleteFolder(Facility $facility, string $category, $folderId): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // フォルダを取得
            $folder = DocumentFolder::where('id', $folderId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$folder) {
                return response()->json([
                    'success' => false,
                    'message' => 'フォルダが見つかりません。',
                ], 404);
            }

            $this->documentService->deleteFolder($folder, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'フォルダを削除しました。',
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'フォルダを削除する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document folder deletion failed', [
                'facility_id' => $facility->id,
                'folder_id' => $folder->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'フォルダの削除に失敗しました。',
            ], 500);
        }
    }

    /**
     * ファイル名を変更
     */
    public function renameFile(Request $request, Facility $facility, string $category, $fileId): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // ファイルを取得
            $file = DocumentFile::where('id', $fileId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'ファイルが見つかりません。',
                ], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $file = $this->documentService->renameFile($file, $request->input('name'), auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'ファイル名を変更しました。',
                'data' => ['file' => $file],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイル名を変更する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document file rename failed', [
                'facility_id' => $facility->id,
                'file_id' => $file->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイル名の変更に失敗しました。',
            ], 500);
        }
    }

    /**
     * ファイルを削除
     */
    public function deleteFile(Facility $facility, string $category, $fileId): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // ファイルを取得
            $file = DocumentFile::where('id', $fileId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'ファイルが見つかりません。',
                ], 404);
            }

            $this->documentService->deleteFile($file, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'ファイルを削除しました。',
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイルを削除する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document file deletion failed', [
                'facility_id' => $facility->id,
                'file_id' => $file->id,
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
     * ファイルを移動
     */
    public function moveFile(Request $request, Facility $facility, string $category, $fileId): JsonResponse
    {
        try {
            $this->authorize('update', [LifelineEquipment::class, $facility]);

            // ファイルを取得
            $file = DocumentFile::where('id', $fileId)
                ->where('facility_id', $facility->id)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'ファイルが見つかりません。',
                ], 404);
            }

            $request->validate([
                'folder_id' => 'nullable|integer|exists:document_folders,id',
            ]);

            $targetFolder = null;
            if ($request->input('folder_id')) {
                $targetFolder = DocumentFolder::where('facility_id', $facility->id)
                    ->where('id', $request->input('folder_id'))
                    ->first();

                if (!$targetFolder) {
                    return response()->json([
                        'success' => false,
                        'message' => '移動先フォルダが見つかりません。',
                    ], 404);
                }
            }

            $file = $this->documentService->moveFile($file, $targetFolder, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'ファイルを移動しました。',
                'data' => ['file' => $file],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイルを移動する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document file move failed', [
                'facility_id' => $facility->id,
                'file_id' => $file->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイルの移動に失敗しました。',
            ], 500);
        }
    }

    /**
     * ライフライン設備カテゴリの統計情報を取得
     */
    public function stats(Facility $facility, string $category): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // カテゴリの正規化
            $normalizedCategory = str_replace('-', '_', $category);
            if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ], 404);
            }

            $stats = $this->lifelineDocumentService->getCategoryStats($facility, $normalizedCategory);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設の統計情報を表示する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document stats failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '統計情報の取得に失敗しました。',
            ], 500);
        }
    }

    /**
     * ライフライン設備カテゴリ内のファイル検索
     */
    public function search(Request $request, Facility $facility, string $category): JsonResponse
    {
        try {
            $this->authorize('view', [LifelineEquipment::class, $facility]);

            // カテゴリの正規化
            $normalizedCategory = str_replace('-', '_', $category);
            if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
                return response()->json([
                    'success' => false,
                    'message' => '無効なカテゴリです。',
                ], 404);
            }

            $request->validate([
                'query' => 'required|string|min:1|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $options = [
                'per_page' => $request->input('per_page', 20),
            ];

            $result = $this->lifelineDocumentService->searchCategoryFiles(
                $facility,
                $normalizedCategory,
                $request->input('query'),
                $options
            );

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'この施設のファイルを検索する権限がありません。',
            ], 403);
        } catch (Exception $e) {
            Log::error('Lifeline document search failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ファイル検索に失敗しました。',
            ], 500);
        }
    }

    /**
     * 利用可能なライフライン設備カテゴリ一覧を取得
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = $this->lifelineDocumentService->getAvailableCategories();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (Exception $e) {
            Log::error('Lifeline document categories failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'カテゴリ一覧の取得に失敗しました。',
            ], 500);
        }
    }
}