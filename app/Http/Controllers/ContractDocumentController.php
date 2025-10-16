<?php

namespace App\Http\Controllers;

use App\Http\Traits\HandlesApiResponses;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\FacilityContract;
use App\Services\ContractDocumentService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractDocumentController extends Controller
{
    use HandlesApiResponses;

    protected ContractDocumentService $contractDocumentService;
    protected DocumentService $documentService;

    public function __construct(
        ContractDocumentService $contractDocumentService,
        DocumentService $documentService
    ) {
        $this->contractDocumentService = $contractDocumentService;
        $this->documentService = $documentService;
    }

    /**
     * 契約書カテゴリのドキュメント一覧を取得
     */
    public function index(Request $request, Facility $facility)
    {
        try {
            // 認可チェック
            $this->authorize('view', [FacilityContract::class, $facility]);

            // ドキュメント一覧取得
            $options = [
                'folder_id' => $request->input('folder_id'),
                'per_page' => $request->input('per_page', 50),
                'sort_by' => $request->input('sort_by', 'name'),
                'sort_order' => $request->input('sort_order', 'asc'),
            ];

            $result = $this->contractDocumentService->getCategoryDocuments($facility, $options);

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'ドキュメントの取得に失敗しました。', 500);
            }

            return $this->successResponse('ドキュメント一覧を取得しました。', $result['data']);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('この施設のドキュメントを閲覧する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'get_contract_documents',
                'facility_id' => $facility->id,
            ]);
        }
    }

    /**
     * ファイルアップロード
     */
    public function uploadFile(Request $request, Facility $facility)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // バリデーション
            $request->validate([
                'file' => ['required', 'file', 'max:51200'], // 50MB
                'folder_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            ]);

            // ファイルアップロード
            $result = $this->contractDocumentService->uploadCategoryFile(
                $facility,
                $request->file('file'),
                auth()->user(),
                $request->input('folder_id')
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'ファイルのアップロードに失敗しました。', 500);
            }

            return $this->successResponse($result['message'], $result['data']);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイルをアップロードする権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'upload_contract_file',
                'facility_id' => $facility->id,
            ]);
        }
    }

    /**
     * フォルダ作成
     */
    public function createFolder(Request $request, Facility $facility)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'integer', 'exists:document_folders,id'],
            ]);

            // フォルダ作成
            $result = $this->contractDocumentService->createCategoryFolder(
                $facility,
                $request->input('name'),
                auth()->user(),
                $request->input('parent_id')
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'] ?? 'フォルダの作成に失敗しました。', 500);
            }

            return $this->successResponse($result['message'], $result['data']);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダを作成する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'create_contract_folder',
                'facility_id' => $facility->id,
            ]);
        }
    }

    /**
     * ファイルダウンロード
     */
    public function downloadFile(Facility $facility, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('view', [FacilityContract::class, $facility]);

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                abort(404, 'ファイルが見つかりません。');
            }

            // カテゴリ確認
            if ($documentFile->category !== ContractDocumentService::CATEGORY) {
                abort(404, 'ファイルが見つかりません。');
            }

            // ダウンロード
            return $this->documentService->downloadFile($documentFile);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, 'ファイルをダウンロードする権限がありません。');
        } catch (\Exception $e) {
            Log::error('Contract file download failed', [
                'facility_id' => $facility->id,
                'file_id' => $file,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }

    /**
     * ファイル削除
     */
    public function deleteFile(Request $request, Facility $facility, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // カテゴリ確認
            if ($documentFile->category !== ContractDocumentService::CATEGORY) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // ファイル削除
            $this->documentService->deleteFile($documentFile, auth()->user());

            return $this->successResponse('ファイルを削除しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイルを削除する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'delete_contract_file',
                'facility_id' => $facility->id,
                'file_id' => $file,
            ]);
        }
    }

    /**
     * フォルダ削除
     */
    public function deleteFolder(Request $request, Facility $facility, int $folder)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // フォルダ取得
            $documentFolder = DocumentFolder::findOrFail($folder);

            // フォルダが施設に属しているか確認
            if ($documentFolder->facility_id !== $facility->id) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // カテゴリ確認
            if ($documentFolder->category !== ContractDocumentService::CATEGORY) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // フォルダ削除
            $this->documentService->deleteFolder($documentFolder, auth()->user());

            return $this->successResponse('フォルダを削除しました。');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダを削除する権限がありません。', 403);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'delete_contract_folder',
                'facility_id' => $facility->id,
                'folder_id' => $folder,
            ]);
        }
    }

    /**
     * ファイル名変更
     */
    public function renameFile(Request $request, Facility $facility, int $file)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // ファイル取得
            $documentFile = DocumentFile::findOrFail($file);

            // ファイルが施設に属しているか確認
            if ($documentFile->facility_id !== $facility->id) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // カテゴリ確認
            if ($documentFile->category !== ContractDocumentService::CATEGORY) {
                return $this->errorResponse('ファイルが見つかりません。', 404);
            }

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            // ファイル名変更
            $this->documentService->renameFile($documentFile, $request->input('name'), auth()->user());

            return $this->successResponse('ファイル名を変更しました。', ['file' => $documentFile->fresh()]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('ファイル名を変更する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'rename_contract_file',
                'facility_id' => $facility->id,
                'file_id' => $file,
            ]);
        }
    }

    /**
     * フォルダ名変更
     */
    public function renameFolder(Request $request, Facility $facility, int $folder)
    {
        try {
            // 認可チェック
            $this->authorize('update', [FacilityContract::class, $facility]);

            // フォルダ取得
            $documentFolder = DocumentFolder::findOrFail($folder);

            // フォルダが施設に属しているか確認
            if ($documentFolder->facility_id !== $facility->id) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // カテゴリ確認
            if ($documentFolder->category !== ContractDocumentService::CATEGORY) {
                return $this->errorResponse('フォルダが見つかりません。', 404);
            }

            // バリデーション
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            // フォルダ名変更
            $this->documentService->renameFolder($documentFolder, $request->input('name'), auth()->user());

            return $this->successResponse('フォルダ名を変更しました。', ['folder' => $documentFolder->fresh()]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('フォルダ名を変更する権限がありません。', 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, [
                'operation' => 'rename_contract_folder',
                'facility_id' => $facility->id,
                'folder_id' => $folder,
            ]);
        }
    }
}
