<?php

namespace App\Services;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ドキュメント管理システム専用エラーハンドラー
 * 
 * 統一されたエラーハンドリングとユーザーフレンドリーなエラーメッセージを提供します。
 */
class DocumentErrorHandler
{
    /**
     * エラータイプの定数
     */
    const ERROR_AUTHORIZATION = 'authorization';
    const ERROR_VALIDATION = 'validation';
    const ERROR_NOT_FOUND = 'not_found';
    const ERROR_STORAGE = 'storage';
    const ERROR_NETWORK = 'network';
    const ERROR_SYSTEM = 'system';
    const ERROR_FILE_OPERATION = 'file_operation';
    const ERROR_FOLDER_OPERATION = 'folder_operation';

    /**
     * エラーメッセージマッピング
     */
    private static array $errorMessages = [
        self::ERROR_AUTHORIZATION => [
            'default' => 'この操作を実行する権限がありません。',
            'view' => 'このドキュメントを表示する権限がありません。',
            'create' => 'ドキュメントを作成する権限がありません。',
            'update' => 'ドキュメントを編集する権限がありません。',
            'delete' => 'ドキュメントを削除する権限がありません。',
        ],
        self::ERROR_VALIDATION => [
            'default' => '入力内容に問題があります。',
            'file_size' => 'ファイルサイズが制限を超えています。',
            'file_type' => 'サポートされていないファイル形式です。',
            'folder_name' => 'フォルダ名が正しくありません。',
            'duplicate_name' => '同じ名前のフォルダまたはファイルが既に存在します。',
        ],
        self::ERROR_NOT_FOUND => [
            'default' => '指定されたリソースが見つかりません。',
            'folder' => '指定されたフォルダが見つかりません。',
            'file' => '指定されたファイルが見つかりません。',
            'facility' => '指定された施設が見つかりません。',
        ],
        self::ERROR_STORAGE => [
            'default' => 'ストレージエラーが発生しました。',
            'disk_full' => 'ストレージ容量が不足しています。',
            'permission' => 'ファイルへのアクセス権限がありません。',
            'corruption' => 'ファイルが破損している可能性があります。',
        ],
        self::ERROR_NETWORK => [
            'default' => 'ネットワークエラーが発生しました。',
            'timeout' => 'リクエストがタイムアウトしました。',
            'connection' => 'サーバーに接続できません。',
            'upload_interrupted' => 'アップロードが中断されました。',
        ],
        self::ERROR_SYSTEM => [
            'default' => 'システムエラーが発生しました。',
            'database' => 'データベースエラーが発生しました。',
            'service_unavailable' => 'サービスが一時的に利用できません。',
        ],
        self::ERROR_FILE_OPERATION => [
            'default' => 'ファイル操作に失敗しました。',
            'upload_failed' => 'ファイルのアップロードに失敗しました。',
            'download_failed' => 'ファイルのダウンロードに失敗しました。',
            'delete_failed' => 'ファイルの削除に失敗しました。',
            'move_failed' => 'ファイルの移動に失敗しました。',
            'rename_failed' => 'ファイル名の変更に失敗しました。',
        ],
        self::ERROR_FOLDER_OPERATION => [
            'default' => 'フォルダ操作に失敗しました。',
            'create_failed' => 'フォルダの作成に失敗しました。',
            'delete_failed' => 'フォルダの削除に失敗しました。',
            'rename_failed' => 'フォルダ名の変更に失敗しました。',
            'not_empty' => 'フォルダが空でないため削除できません。',
        ],
    ];

    /**
     * 包括的エラーハンドリング
     */
    public static function handleError(
        Exception $exception,
        Request $request,
        array $context = []
    ): JsonResponse|Response {
        $errorType = self::determineErrorType($exception);
        $errorCode = self::getErrorCode($exception);
        $userMessage = self::getUserFriendlyMessage($exception, $errorType);
        
        // ログ出力
        self::logError($exception, $errorType, $context);

        // レスポンス形式の判定
        if ($request->expectsJson() || $request->ajax()) {
            return self::createJsonErrorResponse($exception, $errorType, $userMessage, $errorCode);
        }

        return self::createHtmlErrorResponse($exception, $errorType, $userMessage, $errorCode);
    }

    /**
     * エラータイプの判定
     */
    private static function determineErrorType(Exception $exception): string
    {
        if ($exception instanceof AuthorizationException) {
            return self::ERROR_AUTHORIZATION;
        }

        if ($exception instanceof ValidationException) {
            return self::ERROR_VALIDATION;
        }

        // メッセージベースの判定
        $message = strtolower($exception->getMessage());

        if (str_contains($message, '見つかりません') || str_contains($message, 'not found')) {
            return self::ERROR_NOT_FOUND;
        }

        if (str_contains($message, 'ストレージ') || str_contains($message, 'storage') || 
            str_contains($message, 'disk') || str_contains($message, '容量')) {
            return self::ERROR_STORAGE;
        }

        if (str_contains($message, 'ネットワーク') || str_contains($message, 'network') || 
            str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return self::ERROR_NETWORK;
        }

        if (str_contains($message, 'ファイル') && 
            (str_contains($message, 'アップロード') || str_contains($message, 'ダウンロード') || 
             str_contains($message, '削除') || str_contains($message, '移動'))) {
            return self::ERROR_FILE_OPERATION;
        }

        if (str_contains($message, 'フォルダ') && 
            (str_contains($message, '作成') || str_contains($message, '削除') || 
             str_contains($message, '変更'))) {
            return self::ERROR_FOLDER_OPERATION;
        }

        return self::ERROR_SYSTEM;
    }

    /**
     * HTTPステータスコードの取得
     */
    private static function getErrorCode(Exception $exception): int
    {
        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        $message = strtolower($exception->getMessage());

        if (str_contains($message, '見つかりません') || str_contains($message, 'not found')) {
            return 404;
        }

        if (str_contains($message, '権限') || str_contains($message, 'unauthorized')) {
            return 403;
        }

        if (str_contains($message, 'バリデーション') || str_contains($message, 'validation')) {
            return 422;
        }

        return 500;
    }

    /**
     * ユーザーフレンドリーなエラーメッセージの生成
     */
    private static function getUserFriendlyMessage(Exception $exception, string $errorType): string
    {
        // ValidationExceptionの場合は元のメッセージを使用
        if ($exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        $originalMessage = $exception->getMessage();
        $messages = self::$errorMessages[$errorType] ?? self::$errorMessages[self::ERROR_SYSTEM];

        // 具体的なメッセージがある場合はそれを使用
        foreach ($messages as $key => $message) {
            if ($key !== 'default' && str_contains(strtolower($originalMessage), $key)) {
                return $message;
            }
        }

        // デフォルトメッセージを使用
        return $messages['default'];
    }

    /**
     * エラーログの出力
     */
    private static function logError(Exception $exception, string $errorType, array $context = []): void
    {
        $logLevel = self::getLogLevel($errorType);
        $logContext = array_merge([
            'error_type' => $errorType,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $context);

        Log::log($logLevel, "Document management error: {$exception->getMessage()}", $logContext);
    }

    /**
     * ログレベルの決定
     */
    private static function getLogLevel(string $errorType): string
    {
        return match ($errorType) {
            self::ERROR_AUTHORIZATION => 'warning',
            self::ERROR_VALIDATION => 'info',
            self::ERROR_NOT_FOUND => 'info',
            self::ERROR_STORAGE => 'error',
            self::ERROR_NETWORK => 'warning',
            self::ERROR_SYSTEM => 'error',
            self::ERROR_FILE_OPERATION => 'error',
            self::ERROR_FOLDER_OPERATION => 'error',
            default => 'error',
        };
    }

    /**
     * JSON エラーレスポンスの作成
     */
    private static function createJsonErrorResponse(
        Exception $exception,
        string $errorType,
        string $userMessage,
        int $errorCode
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $userMessage,
            'error_type' => $errorType,
        ];

        // ValidationExceptionの場合はエラー詳細を追加
        if ($exception instanceof ValidationException) {
            $response['errors'] = $exception->errors();
        }

        // 開発環境では詳細情報を追加
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'original_message' => $exception->getMessage(),
            ];
        }

        return response()->json($response, $errorCode);
    }

    /**
     * HTML エラーレスポンスの作成
     */
    private static function createHtmlErrorResponse(
        Exception $exception,
        string $errorType,
        string $userMessage,
        int $errorCode
    ): Response {
        // ValidationExceptionの場合は入力データと共にリダイレクト
        if ($exception instanceof ValidationException) {
            return back()->withErrors($exception->validator)->withInput();
        }

        // その他のエラーの場合はエラーメッセージと共にリダイレクト
        return back()->with('error', $userMessage);
    }

    /**
     * ネットワークエラーの検出
     */
    public static function isNetworkError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $networkKeywords = [
            'network', 'connection', 'timeout', 'unreachable',
            'ネットワーク', '接続', 'タイムアウト'
        ];

        foreach ($networkKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ストレージエラーの検出
     */
    public static function isStorageError(Exception $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $storageKeywords = [
            'storage', 'disk', 'space', 'permission', 'file system',
            'ストレージ', 'ディスク', '容量', '権限', 'ファイルシステム'
        ];

        foreach ($storageKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 復旧可能エラーの判定
     */
    public static function isRecoverableError(Exception $exception): bool
    {
        $errorType = self::determineErrorType($exception);
        
        return in_array($errorType, [
            self::ERROR_NETWORK,
            self::ERROR_VALIDATION,
        ]);
    }

    /**
     * エラー統計の取得
     */
    public static function getErrorStats(int $facilityId, int $days = 7): array
    {
        // 実装は要件に応じて追加予定
        return [
            'total_errors' => 0,
            'error_types' => [],
            'most_common_error' => null,
            'error_trend' => [],
        ];
    }
}