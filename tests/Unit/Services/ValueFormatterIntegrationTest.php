<?php

namespace Tests\Unit\Services;

use App\Services\ValueFormatter;
use PHPUnit\Framework\TestCase;

/**
 * ValueFormatterの統合テスト
 * 実際の使用シナリオに基づいたテスト
 */
class ValueFormatterIntegrationTest extends TestCase
{
    /**
     * @test
     */
    public function it_formats_facility_basic_info_data_correctly()
    {
        // 施設基本情報のサンプルデータ
        $facilityData = [
            ['label' => '会社名', 'value' => '株式会社テスト', 'type' => 'text'],
            ['label' => 'ステータス', 'value' => '有効', 'type' => 'badge', 'options' => ['auto_class' => true]],
            ['label' => 'メールアドレス', 'value' => 'contact@test.co.jp', 'type' => 'email'],
            ['label' => 'ウェブサイト', 'value' => 'www.test.co.jp', 'type' => 'url'],
            ['label' => '設立日', 'value' => '2020-04-01', 'type' => 'date'],
            ['label' => '資本金', 'value' => 10000000, 'type' => 'currency'],
            ['label' => '従業員数', 'value' => 150, 'type' => 'number'],
            ['label' => '会社案内', 'value' => '/documents/company-profile.pdf', 'type' => 'file'],
            ['label' => '備考', 'value' => null, 'type' => 'text'],
        ];

        $expectedResults = [
            '株式会社テスト',
            '<span class="badge bg-success">有効</span>',
            '<a href="mailto:contact@test.co.jp" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>contact@test.co.jp</a>',
            '<a href="https://www.test.co.jp" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>www.test.co.jp</a>',
            '2020年04月01日',
            '10,000,000円',
            '150',
            '<a href="/documents/company-profile.pdf" class="text-decoration-none" aria-label="/documents/company-profile.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/documents/company-profile.pdf</a>',
            '未設定',
        ];

        foreach ($facilityData as $index => $item) {
            $options = $item['options'] ?? [];
            $result = ValueFormatter::format($item['value'], $item['type'], $options);
            $this->assertEquals($expectedResults[$index], $result, "Failed for {$item['label']}");
        }
    }

    /**
     * @test
     */
    public function it_formats_land_info_data_correctly()
    {
        // 土地情報のサンプルデータ
        $landData = [
            ['label' => '所有者名', 'value' => '田中太郎', 'type' => 'text'],
            ['label' => '契約状況', 'value' => '契約中', 'type' => 'badge', 'options' => ['badge_class' => 'badge bg-success']],
            ['label' => '連絡先', 'value' => 'tanaka@example.com', 'type' => 'email'],
            ['label' => '契約開始日', 'value' => '2022-01-01', 'type' => 'date'],
            ['label' => '月額賃料', 'value' => 500000, 'type' => 'currency'],
            ['label' => '面積', 'value' => 1500.5, 'type' => 'number', 'options' => ['decimals' => 1]],
            ['label' => '契約書', 'value' => '/contracts/land-contract-2022.pdf', 'type' => 'file'],
            ['label' => '特記事項', 'value' => '', 'type' => 'text', 'options' => ['empty_text' => '特になし']],
        ];

        $expectedResults = [
            '田中太郎',
            '<span class="badge bg-success">契約中</span>',
            '<a href="mailto:tanaka@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>tanaka@example.com</a>',
            '2022年01月01日',
            '500,000円',
            '1,500.5',
            '<a href="/contracts/land-contract-2022.pdf" class="text-decoration-none" aria-label="/contracts/land-contract-2022.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/contracts/land-contract-2022.pdf</a>',
            '特になし',
        ];

        foreach ($landData as $index => $item) {
            $options = $item['options'] ?? [];
            $result = ValueFormatter::format($item['value'], $item['type'], $options);
            $this->assertEquals($expectedResults[$index], $result, "Failed for {$item['label']}");
        }
    }

    /**
     * @test
     */
    public function it_handles_mixed_data_types_correctly()
    {
        // 混合データタイプのテスト
        $mixedData = [
            ['value' => 0, 'type' => 'number', 'expected' => '0'],
            ['value' => false, 'type' => 'text', 'expected' => ''],
            ['value' => [], 'type' => 'text', 'expected' => '未設定'],
            ['value' => '0', 'type' => 'currency', 'expected' => '0円'],
            ['value' => 'https://example.com/path with spaces', 'type' => 'url', 'expected' => '<a href="https://example.com/path with spaces" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://example.com/path with spaces</a>'],
        ];

        foreach ($mixedData as $item) {
            $result = ValueFormatter::format($item['value'], $item['type']);
            $this->assertEquals($item['expected'], $result, 'Failed for value: '.json_encode($item['value']));
        }
    }

    /**
     * @test
     */
    public function it_supports_all_required_cell_types()
    {
        // 要件で指定されたすべてのセルタイプをテスト
        $requiredTypes = [
            'text' => ['value' => 'テストテキスト', 'expected' => 'テストテキスト'],
            'badge' => ['value' => 'テストバッジ', 'expected' => '<span class="badge bg-primary">テストバッジ</span>'],
            'email' => ['value' => 'test@example.com', 'expected' => '<a href="mailto:test@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>test@example.com</a>'],
            'url' => ['value' => 'https://example.com', 'expected' => '<a href="https://example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://example.com</a>'],
            'date' => ['value' => '2023-12-25', 'expected' => '2023年12月25日'],
            'currency' => ['value' => 1000, 'expected' => '1,000円'],
            'number' => ['value' => 1000, 'expected' => '1,000'],
            'file' => ['value' => '/path/to/file.pdf', 'expected' => '<a href="/path/to/file.pdf" class="text-decoration-none" aria-label="/path/to/file.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/path/to/file.pdf</a>'],
        ];

        foreach ($requiredTypes as $type => $testCase) {
            $result = ValueFormatter::format($testCase['value'], $type);
            $this->assertEquals($testCase['expected'], $result, "Failed for type: {$type}");
        }
    }

    /**
     * @test
     */
    public function it_maintains_consistent_empty_value_handling()
    {
        // 空値処理の一貫性テスト
        $emptyValues = [null, '', '   ', []];
        $types = ['text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file'];

        foreach ($types as $type) {
            foreach ($emptyValues as $emptyValue) {
                $result = ValueFormatter::format($emptyValue, $type);
                $this->assertEquals('未設定', $result, "Failed for type {$type} with empty value: ".json_encode($emptyValue));
            }
        }
    }

    /**
     * @test
     */
    public function it_handles_japanese_locale_correctly()
    {
        // 日本語ロケール対応のテスト
        $japaneseData = [
            ['value' => '株式会社テスト企業', 'type' => 'text', 'expected' => '株式会社テスト企業'],
            ['value' => '有効', 'type' => 'badge', 'options' => ['auto_class' => true], 'expected' => '<span class="badge bg-success">有効</span>'],
            ['value' => 'テスト@example.co.jp', 'type' => 'email', 'expected' => '<a href="mailto:テスト@example.co.jp" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>テスト@example.co.jp</a>'],
            ['value' => 1000000, 'type' => 'currency', 'expected' => '1,000,000円'],
            ['value' => '2023-12-31', 'type' => 'date', 'expected' => '2023年12月31日'],
            ['value' => '/documents/報告書.pdf', 'type' => 'file', 'expected' => '<a href="/documents/報告書.pdf" class="text-decoration-none" aria-label="/documents/報告書.pdfをダウンロード" target="_blank"><i class="fas fa-file-pdf text-danger"></i>/documents/報告書.pdf</a>'],
        ];

        foreach ($japaneseData as $item) {
            $options = $item['options'] ?? [];
            $result = ValueFormatter::format($item['value'], $item['type'], $options);
            $this->assertEquals($item['expected'], $result, "Failed for Japanese data: {$item['value']}");
        }
    }
}
