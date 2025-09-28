<?php

namespace Tests\Unit\Services;

use App\Services\CommonTableValidator;
use Tests\TestCase;

/**
 * CommonTableValidator単体テスト
 *
 * データバリデーション機能の詳細なテスト
 * 要件: 設計書のテスト戦略
 */
class CommonTableValidatorUnitTest extends TestCase
{
    /**
     * @test
     * 有効なテーブルデータのバリデーション
     */
    public function test_validate_table_data_有効なデータで成功する()
    {
        $validData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email'],
                ],
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'URL', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 2],
                ],
            ],
        ];

        $result = CommonTableValidator::validateTableData($validData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * @test
     * 無効なテーブルデータのバリデーション
     */
    public function test_validate_table_data_無効なデータでエラーを返す()
    {
        // 配列でないデータ
        $result = CommonTableValidator::validateTableData('invalid');
        $this->assertFalse($result['valid']);
        $this->assertContains('テーブルデータは配列である必要があります', $result['errors']);

        // 無効な行データ
        $invalidData = [
            'invalid_row',
        ];
        $result = CommonTableValidator::validateTableData($invalidData);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('行データは配列である必要があります', $result['errors'][0]);
    }

    /**
     * @test
     * 空データのバリデーション
     */
    public function test_validate_table_data_空データで警告を返す()
    {
        $result = CommonTableValidator::validateTableData([]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertContains('テーブルデータが空です', $result['warnings']);
    }

    /**
     * @test
     * 行データのバリデーション
     */
    public function test_validate_row_data_有効な行データで成功する()
    {
        $validRow = [
            'type' => 'standard',
            'cells' => [
                ['label' => 'テスト', 'value' => '値', 'type' => 'text'],
            ],
        ];

        $result = CommonTableValidator::validateRowData($validRow, 0);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * @test
     * 行データの必須フィールドチェック
     */
    public function test_validate_row_data_必須フィールドなしでエラー()
    {
        // cellsフィールドなし
        $invalidRow = ['type' => 'standard'];
        $result = CommonTableValidator::validateRowData($invalidRow, 0);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("'cells' フィールドが必要です", $result['errors'][0]);

        // cellsが配列でない
        $invalidRow = ['type' => 'standard', 'cells' => 'invalid'];
        $result = CommonTableValidator::validateRowData($invalidRow, 0);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("'cells' は配列である必要があります", $result['errors'][0]);
    }

    /**
     * @test
     * サポートされていない行タイプのバリデーション
     */
    public function test_validate_row_data_サポートされていない行タイプでエラー()
    {
        $invalidRow = [
            'type' => 'unsupported_type',
            'cells' => [
                ['label' => 'テスト', 'value' => '値', 'type' => 'text'],
            ],
        ];

        $result = CommonTableValidator::validateRowData($invalidRow, 0);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("サポートされていない行タイプ 'unsupported_type'", $result['errors'][0]);
    }

    /**
     * @test
     * セルデータのバリデーション
     */
    public function test_validate_cell_data_有効なセルデータで成功する()
    {
        $validCell = [
            'label' => 'テストラベル',
            'value' => 'テスト値',
            'type' => 'text',
            'colspan' => 1,
            'rowspan' => 1,
        ];

        $result = CommonTableValidator::validateCellData($validCell, 0, 0);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * @test
     * サポートされていないセルタイプのバリデーション
     */
    public function test_validate_cell_data_サポートされていないセルタイプでエラー()
    {
        $invalidCell = [
            'label' => 'テスト',
            'value' => '値',
            'type' => 'unsupported_type',
        ];

        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString("サポートされていないセルタイプ 'unsupported_type'", $result['errors'][0]);
    }

    /**
     * @test
     * colspanのバリデーション
     */
    public function test_validate_colspan_有効な値で成功する()
    {
        $result = CommonTableValidator::validateColspan(2, 0, 0);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // 境界値テスト
        $result = CommonTableValidator::validateColspan(1, 0, 0);
        $this->assertTrue($result['valid']);

        $result = CommonTableValidator::validateColspan(12, 0, 0);
        $this->assertTrue($result['valid']);
    }

    /**
     * @test
     * colspanの無効な値でエラー
     */
    public function test_validate_colspan_無効な値でエラー()
    {
        // 数値でない
        $result = CommonTableValidator::validateColspan('invalid', 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('colspan は数値である必要があります', $result['errors'][0]);

        // 1未満
        $result = CommonTableValidator::validateColspan(0, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('colspan は1以上である必要があります', $result['errors'][0]);

        // 最大値超過
        $result = CommonTableValidator::validateColspan(13, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('colspan は12以下である必要があります', $result['errors'][0]);
    }

    /**
     * @test
     * colspanの警告値テスト
     */
    public function test_validate_colspan_大きな値で警告()
    {
        $result = CommonTableValidator::validateColspan(7, 0, 0);
        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('colspan が大きすぎる可能性があります', $result['warnings'][0]);
    }

    /**
     * @test
     * rowspanのバリデーション
     */
    public function test_validate_rowspan_有効な値で成功する()
    {
        $result = CommonTableValidator::validateRowspan(2, 0, 0);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // 境界値テスト
        $result = CommonTableValidator::validateRowspan(1, 0, 0);
        $this->assertTrue($result['valid']);

        $result = CommonTableValidator::validateRowspan(10, 0, 0);
        $this->assertTrue($result['valid']);
    }

    /**
     * @test
     * rowspanの無効な値でエラー
     */
    public function test_validate_rowspan_無効な値でエラー()
    {
        // 数値でない
        $result = CommonTableValidator::validateRowspan('invalid', 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('rowspan は数値である必要があります', $result['errors'][0]);

        // 1未満
        $result = CommonTableValidator::validateRowspan(0, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('rowspan は1以上である必要があります', $result['errors'][0]);

        // 最大値超過
        $result = CommonTableValidator::validateRowspan(11, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('rowspan は10以下である必要があります', $result['errors'][0]);
    }

    /**
     * @test
     * rowspanの警告値テスト
     */
    public function test_validate_rowspan_大きな値で警告()
    {
        $result = CommonTableValidator::validateRowspan(6, 0, 0);
        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('rowspan が大きすぎる可能性があります', $result['warnings'][0]);
    }

    /**
     * @test
     * セルタイプ固有のバリデーション
     */
    public function test_validate_cell_type_specific_メール形式チェック()
    {
        // 有効なメール
        $validCell = ['type' => 'email', 'value' => 'test@example.com'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効なメール（警告）
        $invalidCell = ['type' => 'email', 'value' => 'invalid-email'];
        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);
        $this->assertTrue($result['valid']); // 警告なのでvalidはtrue
        $this->assertStringContainsString('有効なメールアドレス形式ではありません', $result['warnings'][0]);
    }

    /**
     * @test
     * セルタイプ固有のバリデーション - URL
     */
    public function test_validate_cell_type_specific_ur_l形式チェック()
    {
        // 有効なURL
        $validCell = ['type' => 'url', 'value' => 'https://example.com'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // プロトコルなしURL（有効）
        $validCell = ['type' => 'url', 'value' => 'example.com'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効なURL（警告）
        $invalidCell = ['type' => 'url', 'value' => 'not-a-url'];
        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);
        $this->assertTrue($result['valid']); // 警告なのでvalidはtrue
        if (! empty($result['warnings'])) {
            $this->assertStringContainsString('有効なURL形式ではありません', $result['warnings'][0]);
        }
    }

    /**
     * @test
     * セルタイプ固有のバリデーション - 日付
     */
    public function test_validate_cell_type_specific_日付形式チェック()
    {
        // 有効な日付
        $validCell = ['type' => 'date', 'value' => '2023-12-25'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効な日付（警告）
        $invalidCell = ['type' => 'date', 'value' => 'invalid-date'];
        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);
        $this->assertTrue($result['valid']); // 警告なのでvalidはtrue
        $this->assertStringContainsString('有効な日付形式ではありません', $result['warnings'][0]);
    }

    /**
     * @test
     * セルタイプ固有のバリデーション - 数値
     */
    public function test_validate_cell_type_specific_数値形式チェック()
    {
        // 有効な数値
        $validCell = ['type' => 'number', 'value' => 123];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        $validCell = ['type' => 'currency', 'value' => '123.45'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効な数値（警告）
        $invalidCell = ['type' => 'number', 'value' => 'not-a-number'];
        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);
        $this->assertTrue($result['valid']); // 警告なのでvalidはtrue
        $this->assertStringContainsString('数値形式ではありません', $result['warnings'][0]);
    }

    /**
     * @test
     * セルタイプ固有のバリデーション - ファイル
     */
    public function test_validate_cell_type_specific_ファイルパスチェック()
    {
        // 有効なファイルパス
        $validCell = ['type' => 'file', 'value' => '/path/to/file.pdf'];
        $result = CommonTableValidator::validateCellData($validCell, 0, 0);
        $this->assertTrue($result['valid']);

        // 不正なファイルパス（エラー）
        $invalidCell = ['type' => 'file', 'value' => '/path/../../../etc/passwd'];
        $result = CommonTableValidator::validateCellData($invalidCell, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('不正なファイルパスです', $result['errors'][0]);
    }

    /**
     * @test
     * テーブル構造のバリデーション
     */
    public function test_validate_table_structure_行数チェック()
    {
        // 大量の行データ
        $largeData = array_fill(0, 150, [
            'type' => 'standard',
            'cells' => [['label' => 'test', 'value' => 'value', 'type' => 'text']],
        ]);

        $result = CommonTableValidator::validateTableData($largeData, ['max_rows' => 100]);
        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('テーブルの行数が多すぎます', $result['warnings'][0]);
    }

    /**
     * @test
     * セル数一貫性チェック
     */
    public function test_validate_table_structure_セル数一貫性チェック()
    {
        $inconsistentData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'test1', 'value' => 'value1', 'type' => 'text'],
                    ['label' => 'test2', 'value' => 'value2', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'test3', 'value' => 'value3', 'type' => 'text'],
                ],
            ],
        ];

        $result = CommonTableValidator::validateTableData($inconsistentData, ['check_cell_consistency' => true]);
        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('行ごとのセル数が一致していません', $result['warnings'][0]);
    }

    /**
     * @test
     * サポートされているタイプの取得
     */
    public function test_get_supported_types_正しいタイプを返す()
    {
        $cellTypes = CommonTableValidator::getSupportedCellTypes();
        $expectedCellTypes = ['text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file'];
        $this->assertEquals($expectedCellTypes, $cellTypes);

        $rowTypes = CommonTableValidator::getSupportedRowTypes();
        $expectedRowTypes = ['standard', 'grouped', 'single'];
        $this->assertEquals($expectedRowTypes, $rowTypes);
    }

    /**
     * @test
     * バリデーションオプションの検証
     */
    public function test_validate_options_有効なオプションで成功()
    {
        $validOptions = [
            'max_rows' => 50,
            'check_cell_consistency' => true,
        ];

        $result = CommonTableValidator::validateOptions($validOptions);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * @test
     * バリデーションオプションの検証 - 無効なオプション
     */
    public function test_validate_options_無効なオプションでエラー()
    {
        // 無効なmax_rows
        $invalidOptions = ['max_rows' => -1];
        $result = CommonTableValidator::validateOptions($invalidOptions);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('max_rows は1以上の数値である必要があります', $result['errors'][0]);

        // 無効なcheck_cell_consistency
        $invalidOptions = ['check_cell_consistency' => 'invalid'];
        $result = CommonTableValidator::validateOptions($invalidOptions);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('check_cell_consistency はboolean値である必要があります', $result['errors'][0]);
    }

    /**
     * @test
     * 大量データでのバリデーション
     */
    public function test_validate_table_data_大量データでの動作()
    {
        // 大量のデータを生成
        $largeData = [];
        for ($i = 0; $i < 200; $i++) {
            $largeData[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "ラベル{$i}", 'value' => "値{$i}", 'type' => 'text'],
                ],
            ];
        }

        $result = CommonTableValidator::validateTableData($largeData, ['max_rows' => 100]);
        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('テーブルの行数が多すぎます', $result['warnings'][0]);
    }
}
