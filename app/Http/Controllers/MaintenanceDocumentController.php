<?php

namespace App\Http\Controllers;

use App\Http\Traits\HandlesApiResponses;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Services\DocumentService;
use App\Services\MaintenanceDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaintenanceDocumentController extends Controller
{
    use HandlesApiResponses;

    protected MaintenanceDocumentService $maintenanceDocumentService;
    protected DocumentService $documentService;

    public function __construct(
        MaintenanceDocumentService $maintenanceDocumentService,
        DocumentService $documentService
    ) {
        $this->maintenanceDocumentService = $maintenanceDocumentService;
        $this->documentService = $documentService;
    }

    /**
     * 修繕履歴カテゴリのドキュメント一覧を取得
     */
    public function index(Request $request, Facility $facility, string $category)
    {
        try {
            // 認可チェック
            $this->authorize('view', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // ドキュメント一覧取得
            $options = [
                'folder_id' => $request->input('folder_id'),
                'per_page' => $request->input('per_page', 50),
                'sort_by' => $request->input('sort_by', 'name'),
                'sort_order' => $request->input('sort_order', 'asc'),
            ];

            $result = $this->maintenanceDocumentService->getCategoryDocuments($facility, $category, $options);

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'ドキュメントの取得に失敗しました。', 500);
            }

            return $this->successResponse($result['data'], 'ドキュメント一覧を取得しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('この施設のドキュメントを閲覧する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'ドキュメント一覧の取得に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
            ]);
        }
    }

    /**
     * ファイルアップロード
     */
    public function uploadFile(Request $request, Facility $facility, string $category)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // バリデーション
            $request->validate([
                'file' => ['required', 'file', 'max:51200'], // 50MB
                'folder_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            ]);

            // ファイルアップロード
            $result = $this->maintenanceDocumentService->uploadCategoryFile(
                $facility,
                $category,
                $request->file('file'),
                auth()->user(),
                $request->input('folder_id')
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'ファイルのアップロードに失敗しました。', 500);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイルをアップロードする権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'ファイルのアップロードに失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
            ]);
        }
    }

    /**
     * フォルダ作成
     */
    public function createFolder(Request $request, Facility $facility, string $category)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            ]);

            // フォルダ作成
            $result = $this->maintenanceDocumentService->createCategoryFolder(
                $facility,
                $category,
                $request->input('name'),
                auth()->user(),
                $request->input('parent_id')
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'フォルダの作成に失敗しました。', 500);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダを作成する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'フォルダの作成に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
            ]);
        }
    }

    /**
     * ファイルダウンロード
     */
    public function downloadFile(Facility $facility, string $category, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('view', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                abort(404, '指定されたカテゴリが無効です。');
            }

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                abort(404, 'ファイルが見つかりません。');
            }

            // ダウンロード
            return $this->documentService->downloadFile($documentFile);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'ファイルをダウンロードする権限がありません。');
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'facility_id' => $facility->id,
                'category' => $category,
                'file_id' => $file,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }

    /**
     * ファイル削除
     */
    public function deleteFile(Facility $facility, string $category, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // ファイル削除
            $this->documentService->deleteFile($documentFile, auth()->user());

            return $this->successResponse(null, 'ファイルを削除しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイルを削除する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'ファイルの削除に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
                'file_id' => $file,
            ]);
        }
    }

    /**
     * フォルダ削除
     */
    public function deleteFolder(Facility $facility, string $category, int $folder)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // フォルダ取得
            $documentFolder = DocumentFolder::findOrFail($folder);

            // フォルダが施設に属しているか確認
            if ($documentFolder->facility_id !== $facility->id) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // フォルダ削除
            $this->documentService->deleteFolder($documentFolder, auth()->user());

            return $this->successResponse(null, 'フォルダを削除しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダを削除する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'フォルダの削除に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
                'folder_id' => $folder,
            ]);
        }
    }

    /**
     * ファイル名変更
     */
    public function renameFile(Request $request, Facility $facility, string $category, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            // ファイル名変更
            $this->documentService->renameFile($documentFile, $request->input('name'), auth()->user());

            return $this->successResponse(['file' => $documentFile->fresh()], 'ファイル名を変更しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイル名を変更する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'ファイル名の変更に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
                'file_id' => $file,
            ]);
        }
    }

    /**
     * フォルダ名変更
     */
    public function renameFolder(Request $request, Facility $facility, string $category, int $folder)
    {
        try {
            // 認可チェック
            $this->authorize('update', [MaintenanceHistory::class, $facility]);

            // カテゴリ検証
            if (!array_key_exists($category, MaintenanceDocumentService::CATEGORY_FOLDER_MAPPING)) {
                return $this->errorResponse('指定されたカテゴリが無効です。', 404);
            }

            // フォルダ取得
            $documentFolder = DocumentFolder::findOrFail($folder);

            // フォルダが施設に属しているか確認
            if ($documentFolder->facility_id !== $facility->id) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            // フォルダ名変更
            $this->documentService->renameFolder($documentFolder, $request->input('name'), auth()->user());

            return $this->successResponse(['folder' => $documentFolder->fresh()], 'フォルダ名を変更しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダ名を変更する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleControllerException($e, 'フォルダ名の変更に失敗しました。', [
                'facility_id' => $facility->id,
                'category' => $category,
                'folder_id' => $folder,
            ]);
        }
    }
}
