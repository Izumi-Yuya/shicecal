<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * CommonTableValidator - 共通テーブルレイアウトコンポーネント用のデータバリデーター
 * 
 * テーブルデータ構造の検証、セルタイプの妥当性チェック、
 * colspan/rowspanの範囲チェックを行う
 */
class CommonTableValidator
{
    /**
     * サポートされているセルタイプ
     */
    private const SUPPORTED_CELL_TYPES = [
        'text',
        'badge',
        'email',
        'url',
        'date',
        'currency',
        'number',
        'file',
        'label'
    ];

    /**
     * サポートされている行タイプ
     */
    private const SUPPORTED_ROW_TYPES = [
        'standard',
        'grouped',
        'single'
    ];

    /**
     * 最大colspan値
     */
    private const MAX_COLSPAN = 12;

    /**
     * 最大rowspan値
     */
    private const MAX_ROWSPAN = 10;

    /**
     * テーブルデータ全体を検証する
     *
     * @param mixed $data テーブルデータ
     * @param array $options 検証オプション
     * @return array 検証結果 ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public static function validateTableData($data, array $options = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // データが配列かどうかチェック
            if (!is_array($data)) {
                $errors[] = 'テーブルデータは配列である必要があります';
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 空データのチェック
            if (empty($data)) {
                $warnings[] = 'テーブルデータが空です';
                return ['valid' => true, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 各行の検証
            foreach ($data as $rowIndex => $rowData) {
                $rowValidation = self::validateRowData($rowData, $rowIndex, $options);
                
                if (!$rowValidation['valid']) {
                    $errors = array_merge($errors, $rowValidation['errors']);
                }
                
                $warnings = array_merge($warnings, $rowValidation['warnings']);
            }

            // 全体的な構造チェック
            $structureValidation = self::validateTableStructure($data, $options);
            $errors = array_merge($errors, $structureValidation['errors']);
            $warnings = array_merge($warnings, $structureValidation['warnings']);

        } catch (\Exception $e) {
            Log::error('CommonTableValidator: テーブルデータ検証中にエラーが発生しました', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errors[] = 'データ検証中に予期しないエラーが発生しました';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 行データを検証する
     *
     * @param mixed $rowData 行データ
     * @param int $rowIndex 行インデックス
     * @param array $options 検証オプション
     * @return array 検証結果
     */
    public static function validateRowData($rowData, int $rowIndex, array $options = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // 行データが配列かどうかチェック
            if (!is_array($rowData)) {
                $errors[] = "行 {$rowIndex}: 行データは配列である必要があります";
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 必須フィールドのチェック
            if (!isset($rowData['cells'])) {
                $errors[] = "行 {$rowIndex}: 'cells' フィールドが必要です";
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 行タイプの検証
            $rowType = $rowData['type'] ?? 'standard';
            if (!in_array($rowType, self::SUPPORTED_ROW_TYPES)) {
                $errors[] = "行 {$rowIndex}: サポートされていない行タイプ '{$rowType}' です";
            }

            // セル配列の検証
            if (!is_array($rowData['cells'])) {
                $errors[] = "行 {$rowIndex}: 'cells' は配列である必要があります";
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 空のセル配列の警告
            if (empty($rowData['cells'])) {
                $warnings[] = "行 {$rowIndex}: セルが空です";
                return ['valid' => true, 'errors' => $errors, 'warnings' => $warnings];
            }

            // 各セルの検証
            foreach ($rowData['cells'] as $cellIndex => $cellData) {
                $cellValidation = self::validateCellData($cellData, $rowIndex, $cellIndex, $options);
                
                if (!$cellValidation['valid']) {
                    $errors = array_merge($errors, $cellValidation['errors']);
                }
                
                $warnings = array_merge($warnings, $cellValidation['warnings']);
            }

        } catch (\Exception $e) {
            Log::error('CommonTableValidator: 行データ検証中にエラーが発生しました', [
                'row_index' => $rowIndex,
                'error' => $e->getMessage()
            ]);
            
            $errors[] = "行 {$rowIndex}: データ検証中にエラーが発生しました";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * セルデータを検証する
     *
     * @param mixed $cellData セルデータ
     * @param int $rowIndex 行インデックス
     * @param int $cellIndex セルインデックス
     * @param array $options 検証オプション
     * @return array 検証結果
     */
    public static function validateCellData($cellData, int $rowIndex, int $cellIndex, array $options = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // セルデータが配列かどうかチェック
            if (!is_array($cellData)) {
                $errors[] = "行 {$rowIndex}, セル {$cellIndex}: セルデータは配列である必要があります";
                return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // セルタイプの検証
            $cellType = $cellData['type'] ?? 'text';
            if (!in_array($cellType, self::SUPPORTED_CELL_TYPES)) {
                $errors[] = "行 {$rowIndex}, セル {$cellIndex}: サポートされていないセルタイプ '{$cellType}' です";
            }

            // colspan の検証
            if (isset($cellData['colspan'])) {
                $colspanValidation = self::validateColspan($cellData['colspan'], $rowIndex, $cellIndex);
                if (!$colspanValidation['valid']) {
                    $errors = array_merge($errors, $colspanValidation['errors']);
                }
                $warnings = array_merge($warnings, $colspanValidation['warnings']);
            }

            // rowspan の検証
            if (isset($cellData['rowspan'])) {
                $rowspanValidation = self::validateRowspan($cellData['rowspan'], $rowIndex, $cellIndex);
                if (!$rowspanValidation['valid']) {
                    $errors = array_merge($errors, $rowspanValidation['errors']);
                }
                $warnings = array_merge($warnings, $rowspanValidation['warnings']);
            }

            // ラベルと値の存在チェック
            if (!isset($cellData['label']) && !isset($cellData['value'])) {
                $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: ラベルまたは値が設定されていません";
            }

            // セルタイプ固有の検証
            $typeValidation = self::validateCellTypeSpecific($cellData, $rowIndex, $cellIndex);
            $errors = array_merge($errors, $typeValidation['errors']);
            $warnings = array_merge($warnings, $typeValidation['warnings']);

        } catch (\Exception $e) {
            Log::error('CommonTableValidator: セルデータ検証中にエラーが発生しました', [
                'row_index' => $rowIndex,
                'cell_index' => $cellIndex,
                'error' => $e->getMessage()
            ]);
            
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: データ検証中にエラーが発生しました";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * colspan値を検証する
     *
     * @param mixed $colspan colspan値
     * @param int $rowIndex 行インデックス
     * @param int $cellIndex セルインデックス
     * @return array 検証結果
     */
    public static function validateColspan($colspan, int $rowIndex, int $cellIndex): array
    {
        $errors = [];
        $warnings = [];

        if (!is_numeric($colspan)) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: colspan は数値である必要があります";
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $colspanValue = (int) $colspan;

        if ($colspanValue < 1) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: colspan は1以上である必要があります";
        }

        if ($colspanValue > self::MAX_COLSPAN) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: colspan は" . self::MAX_COLSPAN . "以下である必要があります";
        }

        if ($colspanValue > 6) {
            $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: colspan が大きすぎる可能性があります (値: {$colspanValue})";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * rowspan値を検証する
     *
     * @param mixed $rowspan rowspan値
     * @param int $rowIndex 行インデックス
     * @param int $cellIndex セルインデックス
     * @return array 検証結果
     */
    public static function validateRowspan($rowspan, int $rowIndex, int $cellIndex): array
    {
        $errors = [];
        $warnings = [];

        if (!is_numeric($rowspan)) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: rowspan は数値である必要があります";
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $rowspanValue = (int) $rowspan;

        if ($rowspanValue < 1) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: rowspan は1以上である必要があります";
        }

        if ($rowspanValue > self::MAX_ROWSPAN) {
            $errors[] = "行 {$rowIndex}, セル {$cellIndex}: rowspan は" . self::MAX_ROWSPAN . "以下である必要があります";
        }

        if ($rowspanValue > 5) {
            $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: rowspan が大きすぎる可能性があります (値: {$rowspanValue})";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * セルタイプ固有の検証を行う
     *
     * @param array $cellData セルデータ
     * @param int $rowIndex 行インデックス
     * @param int $cellIndex セルインデックス
     * @return array 検証結果
     */
    private static function validateCellTypeSpecific(array $cellData, int $rowIndex, int $cellIndex): array
    {
        $errors = [];
        $warnings = [];
        $cellType = $cellData['type'] ?? 'text';
        $value = $cellData['value'] ?? null;

        // 空値の場合は検証をスキップ
        if (ValueFormatter::isEmpty($value)) {
            return ['valid' => true, 'errors' => $errors, 'warnings' => $warnings];
        }

        switch ($cellType) {
            case 'email':
                if (is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: 有効なメールアドレス形式ではありません";
                }
                break;

            case 'url':
                if (is_string($value)) {
                    $url = $value;
                    if (!preg_match('/^https?:\/\//', $url)) {
                        $url = 'https://' . $url;
                    }
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: 有効なURL形式ではありません";
                    }
                }
                break;

            case 'date':
                try {
                    \Carbon\Carbon::parse($value);
                } catch (\Exception $e) {
                    $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: 有効な日付形式ではありません";
                }
                break;

            case 'currency':
            case 'number':
                if (!is_numeric($value)) {
                    $warnings[] = "行 {$rowIndex}, セル {$cellIndex}: 数値形式ではありません";
                }
                break;

            case 'file':
                if (is_string($value) && !empty($value)) {
                    // ファイルパスの基本的な検証
                    if (strpos($value, '..') !== false) {
                        $errors[] = "行 {$rowIndex}, セル {$cellIndex}: 不正なファイルパスです";
                    }
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * テーブル全体の構造を検証する
     *
     * @param array $data テーブルデータ
     * @param array $options 検証オプション
     * @return array 検証結果
     */
    private static function validateTableStructure(array $data, array $options = []): array
    {
        $errors = [];
        $warnings = [];

        try {
            // 行数の妥当性チェック
            $rowCount = count($data);
            $maxRows = $options['max_rows'] ?? 100;
            
            if ($rowCount > $maxRows) {
                $warnings[] = "テーブルの行数が多すぎます ({$rowCount}行)。パフォーマンスに影響する可能性があります";
            }

            // 各行のセル数の一貫性チェック（オプション）
            if ($options['check_cell_consistency'] ?? false) {
                $cellCounts = [];
                foreach ($data as $rowIndex => $rowData) {
                    if (isset($rowData['cells']) && is_array($rowData['cells'])) {
                        $cellCounts[] = count($rowData['cells']);
                    }
                }

                if (!empty($cellCounts)) {
                    $uniqueCounts = array_unique($cellCounts);
                    if (count($uniqueCounts) > 1) {
                        $warnings[] = "行ごとのセル数が一致していません。レイアウトが崩れる可能性があります";
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('CommonTableValidator: テーブル構造検証中にエラーが発生しました', [
                'error' => $e->getMessage()
            ]);
            
            $errors[] = 'テーブル構造検証中にエラーが発生しました';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * サポートされているセルタイプの一覧を取得する
     *
     * @return array
     */
    public static function getSupportedCellTypes(): array
    {
        return self::SUPPORTED_CELL_TYPES;
    }

    /**
     * サポートされている行タイプの一覧を取得する
     *
     * @return array
     */
    public static function getSupportedRowTypes(): array
    {
        return self::SUPPORTED_ROW_TYPES;
    }

    /**
     * 検証設定の妥当性をチェックする
     *
     * @param array $options 検証オプション
     * @return array 検証結果
     */
    public static function validateOptions(array $options): array
    {
        $errors = [];
        $warnings = [];

        if (isset($options['max_rows']) && (!is_numeric($options['max_rows']) || $options['max_rows'] < 1)) {
            $errors[] = 'max_rows は1以上の数値である必要があります';
        }

        if (isset($options['check_cell_consistency']) && !is_bool($options['check_cell_consistency'])) {
            $errors[] = 'check_cell_consistency はboolean値である必要があります';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}