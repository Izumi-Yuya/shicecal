<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Throwable;

/**
 * CommonTableErrorHandler - 共通テーブルレイアウトコンポーネント用のエラーハンドラー
 * 
 * エラーログ記録、エラー表示、フォールバック処理を管理する
 */
class CommonTableErrorHandler
{
    /**
     * エラーレベル定数
     */
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_INFO = 'info';

    /**
     * エラータイプ定数
     */
    public const TYPE_VALIDATION = 'validation';
    public const TYPE_RENDERING = 'rendering';
    public const TYPE_DATA = 'data';
    public const TYPE_SYSTEM = 'system';

    /**
     * エラーを記録し、適切な表示用データを返す
     *
     * @param Throwable|string $error エラー情報
     * @param string $type エラータイプ
     * @param array $context 追加コンテキスト
     * @param string $level ログレベル
     * @return array エラー表示用データ
     */
    public static function handleError($error, string $type = self::TYPE_SYSTEM, array $context = [], string $level = self::LEVEL_ERROR): array
    {
        $errorId = self::generateErrorId();
        $errorMessage = self::extractErrorMessage($error);
        
        // ログに記録
        self::logError($error, $type, $context, $level, $errorId);
        
        // ユーザー向けメッセージを生成
        $userMessage = self::generateUserMessage($type, $errorMessage);
        
        return [
            'error_id' => $errorId,
            'user_message' => $userMessage,
            'technical_message' => $errorMessage,
            'type' => $type,
            'level' => $level,
            'show_details' => config('app.debug', false),
            'context' => $context
        ];
    }

    /**
     * バリデーションエラーを処理する
     *
     * @param array $validationResult バリデーション結果
     * @param array $context 追加コンテキスト
     * @return array エラー表示用データ
     */
    public static function handleValidationErrors(array $validationResult, array $context = []): array
    {
        $errorId = self::generateErrorId();
        
        // バリデーションエラーをログに記録
        if (!empty($validationResult['errors'])) {
            Log::warning('CommonTable: バリデーションエラーが発生しました', [
                'error_id' => $errorId,
                'errors' => $validationResult['errors'],
                'warnings' => $validationResult['warnings'] ?? [],
                'context' => $context
            ]);
        }
        
        // 警告のみの場合はinfoレベルでログ記録
        if (empty($validationResult['errors']) && !empty($validationResult['warnings'])) {
            Log::info('CommonTable: バリデーション警告が発生しました', [
                'error_id' => $errorId,
                'warnings' => $validationResult['warnings'],
                'context' => $context
            ]);
        }
        
        return [
            'error_id' => $errorId,
            'user_message' => 'データの形式に問題があります',
            'errors' => $validationResult['errors'] ?? [],
            'warnings' => $validationResult['warnings'] ?? [],
            'type' => self::TYPE_VALIDATION,
            'level' => !empty($validationResult['errors']) ? self::LEVEL_ERROR : self::LEVEL_WARNING,
            'show_details' => config('app.debug', false) || !empty($validationResult['errors']),
            'context' => $context
        ];
    }

    /**
     * レンダリングエラーを処理する
     *
     * @param Throwable $exception 例外
     * @param array $data テーブルデータ
     * @param array $options レンダリングオプション
     * @return array エラー表示用データ
     */
    public static function handleRenderingError(Throwable $exception, array $data = [], array $options = []): array
    {
        $context = [
            'data_count' => count($data),
            'options' => $options,
            'exception_class' => get_class($exception)
        ];
        
        return self::handleError($exception, self::TYPE_RENDERING, $context);
    }

    /**
     * データエラーを処理する
     *
     * @param string $message エラーメッセージ
     * @param array $data 問題のあるデータ
     * @param array $context 追加コンテキスト
     * @return array エラー表示用データ
     */
    public static function handleDataError(string $message, array $data = [], array $context = []): array
    {
        $context['data_structure'] = self::analyzeDataStructure($data);
        
        return self::handleError($message, self::TYPE_DATA, $context);
    }

    /**
     * エラーをログに記録する
     *
     * @param Throwable|string $error エラー情報
     * @param string $type エラータイプ
     * @param array $context コンテキスト
     * @param string $level ログレベル
     * @param string $errorId エラーID
     */
    private static function logError($error, string $type, array $context, string $level, string $errorId): void
    {
        $logData = [
            'error_id' => $errorId,
            'type' => $type,
            'context' => $context
        ];
        
        if ($error instanceof Throwable) {
            $logData['message'] = $error->getMessage();
            $logData['file'] = $error->getFile();
            $logData['line'] = $error->getLine();
            $logData['trace'] = $error->getTraceAsString();
        } else {
            $logData['message'] = (string) $error;
        }
        
        switch ($level) {
            case self::LEVEL_ERROR:
                Log::error('CommonTable: ' . $logData['message'], $logData);
                break;
            case self::LEVEL_WARNING:
                Log::warning('CommonTable: ' . $logData['message'], $logData);
                break;
            case self::LEVEL_INFO:
                Log::info('CommonTable: ' . $logData['message'], $logData);
                break;
        }
    }

    /**
     * エラーメッセージを抽出する
     *
     * @param Throwable|string $error エラー情報
     * @return string エラーメッセージ
     */
    private static function extractErrorMessage($error): string
    {
        if ($error instanceof Throwable) {
            return $error->getMessage();
        }
        
        return (string) $error;
    }

    /**
     * ユーザー向けメッセージを生成する
     *
     * @param string $type エラータイプ
     * @param string $technicalMessage 技術的なメッセージ
     * @return string ユーザー向けメッセージ
     */
    private static function generateUserMessage(string $type, string $technicalMessage): string
    {
        return match ($type) {
            self::TYPE_VALIDATION => 'データの形式に問題があります',
            self::TYPE_RENDERING => 'テーブルの表示中にエラーが発生しました',
            self::TYPE_DATA => 'データの読み込み中にエラーが発生しました',
            self::TYPE_SYSTEM => 'システムエラーが発生しました',
            default => 'エラーが発生しました'
        };
    }

    /**
     * エラーIDを生成する
     *
     * @return string エラーID
     */
    private static function generateErrorId(): string
    {
        return 'CT_' . strtoupper(Str::random(8)) . '_' . time();
    }

    /**
     * データ構造を分析する
     *
     * @param array $data データ
     * @return array 分析結果
     */
    private static function analyzeDataStructure(array $data): array
    {
        $analysis = [
            'total_rows' => count($data),
            'row_types' => [],
            'cell_types' => [],
            'has_empty_rows' => false,
            'max_cells_per_row' => 0,
            'min_cells_per_row' => PHP_INT_MAX
        ];
        
        foreach ($data as $rowIndex => $rowData) {
            if (!is_array($rowData)) {
                continue;
            }
            
            // 行タイプの収集
            if (isset($rowData['type'])) {
                $analysis['row_types'][] = $rowData['type'];
            }
            
            // セル情報の分析
            if (isset($rowData['cells']) && is_array($rowData['cells'])) {
                $cellCount = count($rowData['cells']);
                $analysis['max_cells_per_row'] = max($analysis['max_cells_per_row'], $cellCount);
                $analysis['min_cells_per_row'] = min($analysis['min_cells_per_row'], $cellCount);
                
                if ($cellCount === 0) {
                    $analysis['has_empty_rows'] = true;
                }
                
                // セルタイプの収集
                foreach ($rowData['cells'] as $cellData) {
                    if (is_array($cellData) && isset($cellData['type'])) {
                        $analysis['cell_types'][] = $cellData['type'];
                    }
                }
            }
        }
        
        // 重複を除去
        $analysis['row_types'] = array_unique($analysis['row_types']);
        $analysis['cell_types'] = array_unique($analysis['cell_types']);
        
        // 最小値の調整
        if ($analysis['min_cells_per_row'] === PHP_INT_MAX) {
            $analysis['min_cells_per_row'] = 0;
        }
        
        return $analysis;
    }

    /**
     * フォールバック表示用のデータを生成する
     *
     * @param string $title テーブルタイトル
     * @param string $message カスタムメッセージ
     * @param bool $showRetry 再試行ボタンを表示するか
     * @return array フォールバック表示用データ
     */
    public static function generateFallbackData(string $title = null, string $message = null, bool $showRetry = false): array
    {
        return [
            'title' => $title,
            'message' => $message ?? 'データを表示できませんでした',
            'show_retry' => $showRetry,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * エラー情報をデバッグ用に整形する
     *
     * @param array $errorData エラーデータ
     * @return array デバッグ用データ
     */
    public static function formatForDebug(array $errorData): array
    {
        return [
            'error_id' => $errorData['error_id'] ?? 'N/A',
            'type' => $errorData['type'] ?? 'unknown',
            'level' => $errorData['level'] ?? 'unknown',
            'user_message' => $errorData['user_message'] ?? 'N/A',
            'technical_message' => $errorData['technical_message'] ?? 'N/A',
            'context' => $errorData['context'] ?? [],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * エラーレベルに基づいてCSSクラスを取得する
     *
     * @param string $level エラーレベル
     * @return string CSSクラス
     */
    public static function getAlertClass(string $level): string
    {
        return match ($level) {
            self::LEVEL_ERROR => 'alert-danger',
            self::LEVEL_WARNING => 'alert-warning',
            self::LEVEL_INFO => 'alert-info',
            default => 'alert-secondary'
        };
    }

    /**
     * エラーレベルに基づいてアイコンを取得する
     *
     * @param string $level エラーレベル
     * @return string アイコンクラス
     */
    public static function getIconClass(string $level): string
    {
        return match ($level) {
            self::LEVEL_ERROR => 'fas fa-exclamation-triangle',
            self::LEVEL_WARNING => 'fas fa-exclamation-circle',
            self::LEVEL_INFO => 'fas fa-info-circle',
            default => 'fas fa-question-circle'
        };
    }
}