<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Facility;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicInfoTableStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected TableConfigService $configService;
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configService = app(TableConfigService::class);
        $this->formatter = app(TableDataFormatter::class);
    }

    public function test_basic_info_table_config_is_loaded_correctly()
    {
        $config = $this->configService->getTableConfig('basic_info');
        
        $this->assertIsArray($config);
        $this->assertEquals('key_value_pairs', $config['layout']['type']);
        $this->assertTrue($config['features']['comments']);
        
        // Verify key columns exist
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('company_name', $columnKeys);
        $this->assertContains('facility_name', $columnKeys);
        $this->assertContains('office_code', $columnKeys);
        $this->assertContains('designation_number', $columnKeys);
        $this->assertContains('website_url', $columnKeys);
    }

    public function test_basic_info_table_data_formatting()
    {
        $facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'postal_code' => '1234567',
            'address' => '東京都渋谷区テスト1-2-3',
            'phone_number' => '03-1234-5678',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
            'opening_date' => \Carbon\Carbon::parse('2020-01-01')->setTimezone('Asia/Tokyo'),
            'years_in_operation' => 4,
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'capacity' => 100
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $formattedData = $this->formatter->formatTableData($facility->toArray(), $config);

        // Test basic text formatting
        $this->assertEquals('テスト会社', $formattedData['company_name']);
        $this->assertEquals('TEST001', $formattedData['office_code']);
        
        // Test special formatting (bold for facility_name)
        $this->assertStringContainsString('fw-bold', $formattedData['facility_name']);
        $this->assertStringContainsString('テスト施設', $formattedData['facility_name']);
        
        // Test date formatting - verify it's in Japanese format
        $this->assertMatchesRegularExpression('/\d{4}年\d{1,2}月\d{1,2}日/', $formattedData['opening_date']);
        
        // Test number formatting with units
        $this->assertEquals('4年', $formattedData['years_in_operation']);
        $this->assertEquals('5階', $formattedData['building_floors']);
        $this->assertEquals('50室', $formattedData['paid_rooms_count']);
        $this->assertEquals('100名', $formattedData['capacity']);
    }

    public function test_basic_info_table_handles_empty_values()
    {
        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'email' => null,
            'website_url' => null,
            'building_name' => null,
            'toll_free_number' => null
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $formattedData = $this->formatter->formatTableData($facility->toArray(), $config);

        // Test that null values return null (will be handled by view as "未設定")
        $this->assertNull($formattedData['email']);
        $this->assertNull($formattedData['website_url']);
        $this->assertNull($formattedData['building_name']);
        $this->assertNull($formattedData['toll_free_number']);
        
        // Test that non-null values are formatted correctly
        $this->assertStringContainsString('テスト施設', $formattedData['facility_name']);
        $this->assertEquals('TEST001', $formattedData['office_code']);
    }

    public function test_basic_info_standardized_table_view_can_be_rendered()
    {
        $facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001'
        ]);

        $view = view('facilities.basic-info.partials.standardized-table', compact('facility'));
        $html = $view->render();

        // Verify the view renders without errors
        $this->assertStringContainsString('基本情報（テーブル形式）', $html);
        $this->assertStringContainsString('basic-info-table', $html);
        $this->assertStringContainsString('universal-table-wrapper', $html);
    }
}