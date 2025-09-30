<?php

namespace App\Exceptions;

use Exception;

/**
 * ドキュメント管理サービス専用例外クラス
 * 
 * ドキュメント管理システムで発生する特定のエラーを表現
 */
class DocumentServiceException extends Exception
{
    /**
     * エラーコード定数
     */
    const FOLDER_NOT_FOUND = 'FOLDER_NOT_FOUND';
    const FILE_NOT_FOUND = 'FILE_NOT_FOUND';
    const DUPLICATE_NAME = 'DUPLICATE_NAME';
    const FOLDER_NOT_EMPTY = 'FOLDER_NOT_EMPTY';
    const STORAGE_FULL = 'STORAGE_FULL';
    const FILE_TOO_LARGE = 'FILE_TOO_LARGE';
    const INVALID_FILE_TYPE = 'INVALID_FILE_TYPE';
    const PERMISSION_DENIED = 'PERMISSION_DENIED';
    const UPLOAD_FAILED = 'UPLOAD_FAILED';
    const DOWNLOAD_FAILED = 'DOWNLOAD_FAILED';
    const DELETE_FAILED = 'DELETE_FAILED';
    const MOVE_FAILED = 'MOVE_FAILED';
    const RENAME_FAILED = 'RENAME_FAILED';
    const INVALID_PATH = 'INVALID_PATH';
    const CORRUPTED_FILE = 'CORRUPTED_FILE';
    const NETWORK_ERROR = 'NETWORK_ERROR';

    /**
     * エラーコード
     */
    protected string $errorCode;

    /**
     * 追加コンテキスト情報
     */
    protected array $context;

    /**
     * コンストラクタ
     */
    public function __construct(
        string $message,
        string $errorCode = 'UNKNOWN_ERROR',
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * エラーコードの取得
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * コンテキスト情報の取得
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * フォルダが見つからないエラー
     */
    public static function folderNotFound(int $folderId, array $context = []): self
    {
        return new self(
            "フォルダ（ID: {$folderId}）が見つかりません。",
            self::FOLDER_NOT_FOUND,
            array_merge(['folder_id' => $folderId], $context)
        );
    }

    /**
     * ファイルが見つからないエラー
     */
    public static function fileNotFound(int $fileId, array $context = []): self
    {
        return new self(
            "ファイル（ID: {$fileId}）が見つかりません。",
            self::FILE_NOT_FOUND,
            array_merge(['file_id' => $fileId], $context)
        );
    }

    /**
     * 名前重複エラー
     */
    public static function duplicateName(string $name, string $type = 'item', array $context = []): self
    {
        return new self(
            "同じ名前の{$type}「{$name}」が既に存在します。",
            self::DUPLICATE_NAME,
            array_merge(['name' => $name, 'type' => $type], $context)
        );
    }

    /**
     * フォルダが空でないエラー
     */
    public static function folderNotEmpty(string $folderName, array $context = []): self
    {
        return new self(
            "フォルダ「{$folderName}」は空でないため削除できません。",
            self::FOLDER_NOT_EMPTY,
            array_merge(['folder_name' => $folderName], $context)
        );
    }

    /**
     * ストレージ容量不足エラー
     */
    public static function storageFull(int $requiredSize, int $availableSize, array $context = []): self
    {
        return new self(
            "ストレージ容量が不足しています。必要: {$requiredSize}MB、利用可能: {$availableSize}MB",
            self::STORAGE_FULL,
            array_merge(['required_size' => $requiredSize, 'available_size' => $availableSize], $context)
        );
    }

    /**
     * ファイルサイズ超過エラー
     */
    public static function fileTooLarge(string $fileName, int $fileSize, int $maxSize, array $context = []): self
    {
        return new self(
            "ファイル「{$fileName}」のサイズ（{$fileSize}MB）が制限（{$maxSize}MB）を超えています。",
            self::FILE_TOO_LARGE,
            array_merge(['file_name' => $fileName, 'file_size' => $fileSize, 'max_size' => $maxSize], $context)
        );
    }

    /**
     * 無効なファイル形式エラー
     */
    public static function invalidFileType(string $fileName, string $fileType, array $allowedTypes, array $context = []): self
    {
        $allowedTypesStr = implode(', ', $allowedTypes);
        return new self(
            "ファイル「{$fileName}」の形式（{$fileType}）はサポートされていません。許可される形式: {$allowedTypesStr}",
            self::INVALID_FILE_TYPE,
            array_merge(['file_name' => $fileName, 'file_type' => $fileType, 'allowed_types' => $allowedTypes], $context)
        );
    }

    /**
     * 権限不足エラー
     */
    public static function permissionDenied(string $operation, array $context = []): self
    {
        return new self(
            "操作「{$operation}」を実行する権限がありません。",
            self::PERMISSION_DENIED,
            array_merge(['operation' => $operation], $context)
        );
    }

    /**
     * アップロード失敗エラー
     */
    public static function uploadFailed(string $fileName, string $reason = '', array $context = []): self
    {
        $message = "ファイル「{$fileName}」のアップロードに失敗しました。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::UPLOAD_FAILED,
            array_merge(['file_name' => $fileName, 'reason' => $reason], $context)
        );
    }

    /**
     * ダウンロード失敗エラー
     */
    public static function downloadFailed(string $fileName, string $reason = '', array $context = []): self
    {
        $message = "ファイル「{$fileName}」のダウンロードに失敗しました。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::DOWNLOAD_FAILED,
            array_merge(['file_name' => $fileName, 'reason' => $reason], $context)
        );
    }

    /**
     * 削除失敗エラー
     */
    public static function deleteFailed(string $itemName, string $type = 'item', string $reason = '', array $context = []): self
    {
        $message = "{$type}「{$itemName}」の削除に失敗しました。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::DELETE_FAILED,
            array_merge(['item_name' => $itemName, 'type' => $type, 'reason' => $reason], $context)
        );
    }

    /**
     * 移動失敗エラー
     */
    public static function moveFailed(string $itemName, string $destination, string $reason = '', array $context = []): self
    {
        $message = "「{$itemName}」を「{$destination}」に移動できませんでした。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::MOVE_FAILED,
            array_merge(['item_name' => $itemName, 'destination' => $destination, 'reason' => $reason], $context)
        );
    }

    /**
     * 名前変更失敗エラー
     */
    public static function renameFailed(string $oldName, string $newName, string $reason = '', array $context = []): self
    {
        $message = "「{$oldName}」を「{$newName}」に名前変更できませんでした。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::RENAME_FAILED,
            array_merge(['old_name' => $oldName, 'new_name' => $newName, 'reason' => $reason], $context)
        );
    }

    /**
     * 無効なパスエラー
     */
    public static function invalidPath(string $path, string $reason = '', array $context = []): self
    {
        $message = "無効なパス「{$path}」が指定されました。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::INVALID_PATH,
            array_merge(['path' => $path, 'reason' => $reason], $context)
        );
    }

    /**
     * ファイル破損エラー
     */
    public static function corruptedFile(string $fileName, string $reason = '', array $context = []): self
    {
        $message = "ファイル「{$fileName}」が破損している可能性があります。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::CORRUPTED_FILE,
            array_merge(['file_name' => $fileName, 'reason' => $reason], $context)
        );
    }

    /**
     * ネットワークエラー
     */
    public static function networkError(string $operation, string $reason = '', array $context = []): self
    {
        $message = "ネットワークエラーにより操作「{$operation}」が失敗しました。";
        if ($reason) {
            $message .= " 理由: {$reason}";
        }
        
        return new self(
            $message,
            self::NETWORK_ERROR,
            array_merge(['operation' => $operation, 'reason' => $reason], $context)
        );
    }

    /**
     * エラー情報の配列形式での取得
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * JSON形式での出力
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}