<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CommonTableValidator;

class CommonTableValidatorTest extends TestCase
{
    /**
     * 有効なテーブルデータの検証テスト
     */
    public function test_validates_valid_table_data()
    {
        $validData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
                    ['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email'],
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['label' => 'URL', 'value' => 'https://example.com', 'type' => 'url', 'colspan' => 2],
                ]
            ]
        ];

        $result = CommonTableValidator::validateTableData($validData);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * 無効なテーブルデータの検証テスト
     */
    public function test_validates_invalid_table_data()
    {
        $invalidData = [
            [
                'type' => 'invalid_type',
                'cells' => [
                    ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_cell_type'],
                ]
            ]
        ];

        $result = CommonTableValidator::validateTableData($invalidData);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('サポートされていない行タイプ', $result['errors'][0]);
        $this->assertStringContainsString('サポートされていないセルタイプ', $result['errors'][1]);
    }

    /**
     * 空のテーブルデータの検証テスト
     */
    public function test_validates_empty_table_data()
    {
        $result = CommonTableValidator::validateTableData([]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('テーブルデータが空です', $result['warnings'][0]);
    }

    /**
     * 非配列データの検証テスト
     */
    public function test_validates_non_array_data()
    {
        $result = CommonTableValidator::validateTableData('invalid');

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('配列である必要があります', $result['errors'][0]);
    }

    /**
     * 行データの検証テスト
     */
    public function test_validates_row_data()
    {
        // 有効な行データ
        $validRowData = [
            'type' => 'standard',
            'cells' => [
                ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'text']
            ]
        ];

        $result = CommonTableValidator::validateRowData($validRowData, 0);
        $this->assertTrue($result['valid']);

        // 無効な行データ（cellsが欠如）
        $invalidRowData = [
            'type' => 'standard'
        ];

        $result = CommonTableValidator::validateRowData($invalidRowData, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('cells', $result['errors'][0]);
    }

    /**
     * セルデータの検証テスト
     */
    public function test_validates_cell_data()
    {
        // 有効なセルデータ
        $validCellData = [
            'label' => 'テスト',
            'value' => 'テスト値',
            'type' => 'text',
            'colspan' => 2,
            'rowspan' => 1
        ];

        $result = CommonTableValidator::validateCellData($validCellData, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効なセルデータ（非配列）
        $result = CommonTableValidator::validateCellData('invalid', 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('配列である必要があります', $result['errors'][0]);
    }

    /**
     * colspan検証テスト
     */
    public function test_validates_colspan()
    {
        // 有効なcolspan
        $result = CommonTableValidator::validateColspan(3, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効なcolspan（0）
        $result = CommonTableValidator::validateColspan(0, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('1以上である必要があります', $result['errors'][0]);

        // 無効なcolspan（大きすぎる）
        $result = CommonTableValidator::validateColspan(20, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('以下である必要があります', $result['errors'][0]);

        // 非数値のcolspan
        $result = CommonTableValidator::validateColspan('invalid', 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('数値である必要があります', $result['errors'][0]);
    }

    /**
     * rowspan検証テスト
     */
    public function test_validates_rowspan()
    {
        // 有効なrowspan
        $result = CommonTableValidator::validateRowspan(2, 0, 0);
        $this->assertTrue($result['valid']);

        // 無効なrowspan（0）
        $result = CommonTableValidator::validateRowspan(0, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('1以上である必要があります', $result['errors'][0]);

        // 無効なrowspan（大きすぎる）
        $result = CommonTableValidator::validateRowspan(15, 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('以下である必要があります', $result['errors'][0]);

        // 非数値のrowspan
        $result = CommonTableValidator::validateRowspan('invalid', 0, 0);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('数値である必要があります', $result['errors'][0]);
    }

    /**
     * セルタイプ固有の検証テスト
     */
    public function test_validates_cell_type_specific()
    {
        // メールアドレスの検証
        $emailCellData = [
            'label' => 'メール',
            'value' => 'invalid-email',
            'type' => 'email'
        ];

        $result = CommonTableValidator::validateCellData($emailCellData, 0, 0);
        $this->assertTrue($result['valid']); // エラーではなく警告
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('有効なメールアドレス形式ではありません', implode(' ', $result['warnings']));

        // URLの検証
        $urlCellData = [
            'label' => 'URL',
            'value' => 'not a valid url at all',
            'type' => 'url'
        ];

        $result = CommonTableValidator::validateCellData($urlCellData, 0, 0);
        $this->assertTrue($result['valid']); // エラーではなく警告
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('有効なURL形式ではありません', implode(' ', $result['warnings']));

        // 数値の検証
        $numberCellData = [
            'label' => '数値',
            'value' => 'not-a-number',
            'type' => 'number'
        ];

        $result = CommonTableValidator::validateCellData($numberCellData, 0, 0);
        $this->assertTrue($result['valid']); // エラーではなく警告
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('数値形式ではありません', implode(' ', $result['warnings']));
    }

    /**
     * サポートされているタイプの取得テスト
     */
    public function test_gets_supported_types()
    {
        $cellTypes = CommonTableValidator::getSupportedCellTypes();
        $this->assertIsArray($cellTypes);
        $this->assertContains('text', $cellTypes);
        $this->assertContains('email', $cellTypes);
        $this->assertContains('url', $cellTypes);

        $rowTypes = CommonTableValidator::getSupportedRowTypes();
        $this->assertIsArray($rowTypes);
        $this->assertContains('standard', $rowTypes);
        $this->assertContains('grouped', $rowTypes);
        $this->assertContains('single', $rowTypes);
    }

    /**
     * 検証オプションの検証テスト
     */
    public function test_validates_options()
    {
        // 有効なオプション
        $validOptions = [
            'max_rows' => 50,
            'check_cell_consistency' => true
        ];

        $result = CommonTableValidator::validateOptions($validOptions);
        $this->assertTrue($result['valid']);

        // 無効なオプション
        $invalidOptions = [
            'max_rows' => -1,
            'check_cell_consistency' => 'invalid'
        ];

        $result = CommonTableValidator::validateOptions($invalidOptions);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * 大量データでの警告テスト
     */
    public function test_warns_for_large_data()
    {
        // 大量の行データを作成
        $largeData = [];
        for ($i = 0; $i < 150; $i++) {
            $largeData[] = [
                'type' => 'standard',
                'cells' => [
                    ['label' => "ラベル{$i}", 'value' => "値{$i}", 'type' => 'text']
                ]
            ];
        }

        $result = CommonTableValidator::validateTableData($largeData);
        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('行数が多すぎます', $result['warnings'][0]);
    }

    /**
     * セル一貫性チェックテスト
     */
    public function test_checks_cell_consistency()
    {
        $inconsistentData = [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text'],
                    ['label' => 'ラベル2', 'value' => '値2', 'type' => 'text']
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'ラベル3', 'value' => '値3', 'type' => 'text']
                ]
            ]
        ];

        $options = ['check_cell_consistency' => true];
        $result = CommonTableValidator::validateTableData($inconsistentData, $options);

        $this->assertTrue($result['valid']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('セル数が一致していません', $result['warnings'][0]);
    }
}