<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LandInfoTableStandardizationTest extends TestCase
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

    public function test_land_info_table_config_is_loaded_correctly()
    {
        $config = $this->configService->getTableConfig('land_info');
        
        $this->assertIsArray($config);
        $this->assertEquals('standard_table', $config['layout']['type']);
        $this->assertTrue($config['features']['comments']);
        
        // Verify key columns exist
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('ownership_type', $columnKeys);
        $this->assertContains('parking_spaces', $columnKeys);
        $this->assertContains('site_area_sqm', $columnKeys);
        $this->assertContains('site_area_tsubo', $columnKeys);
        $this->assertContains('purchase_price', $columnKeys);
        $this->assertContains('monthly_rent', $columnKeys);
        $this->assertContains('contract_start_date', $columnKeys);
        $this->assertContains('contract_end_date', $columnKeys);
    }

    public function test_land_info_table_data_formatting()
    {
        $facility = Facility::factory()->create();
        
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'parking_spaces' => 20,
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 302.65,
            'purchase_price' => 50000000,
            'monthly_rent' => null,
            'contract_start_date' => '2020-01-01',
            'contract_end_date' => '2025-12-31'
        ]);

        $config = $this->configService->getTableConfig('land_info');
        $formattedData = $this->formatter->formatTableData($landInfo->toArray(), $config);

        // Test select formatting (ownership_type)
        $this->assertEquals('自社', $formattedData['ownership_type']);
        
        // Test number formatting with units
        $this->assertEquals('20台', $formattedData['parking_spaces']);
        $this->assertEquals('1,000.50㎡', $formattedData['site_area_sqm']);
        $this->assertEquals('302.65坪', $formattedData['site_area_tsubo']);
        $this->assertEquals('50,000,000円', $formattedData['purchase_price']);
        
        // Test null value (monthly_rent should be null for owned property)
        $this->assertNull($formattedData['monthly_rent']);
        
        // Test date formatting - verify it's in Japanese format
        $this->assertMatchesRegularExpression('/\d{4}年\d{1,2}月\d{1,2}日/', $formattedData['contract_start_date']);
        $this->assertMatchesRegularExpression('/\d{4}年\d{1,2}月\d{1,2}日/', $formattedData['contract_end_date']);
    }

    public function test_land_info_table_handles_empty_values()
    {
        $facility = Facility::factory()->create();
        
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'leased',
            'parking_spaces' => null,
            'site_area_sqm' => null,
            'site_area_tsubo' => null,
            'purchase_price' => null,
            'monthly_rent' => 100000,
            'contract_start_date' => null,
            'contract_end_date' => null
        ]);

        $config = $this->configService->getTableConfig('land_info');
        $formattedData = $this->formatter->formatTableData($landInfo->toArray(), $config);

        // Test that null values return null (will be handled by view as "未設定")
        $this->assertNull($formattedData['parking_spaces']);
        $this->assertNull($formattedData['site_area_sqm']);
        $this->assertNull($formattedData['site_area_tsubo']);
        $this->assertNull($formattedData['purchase_price']);
        $this->assertNull($formattedData['contract_start_date']);
        $this->assertNull($formattedData['contract_end_date']);
        
        // Test that non-null values are formatted correctly
        $this->assertEquals('賃借', $formattedData['ownership_type']);
        $this->assertEquals('100,000円', $formattedData['monthly_rent']);
    }

    public function test_land_info_standardized_table_view_can_be_rendered()
    {
        $facility = Facility::factory()->create();
        
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'parking_spaces' => 15,
            'site_area_sqm' => 800.25,
            'purchase_price' => 30000000
        ]);

        $view = view('facilities.land-info.partials.standardized-table', compact('facility', 'landInfo'));
        $html = $view->render();

        // Verify the view renders without errors
        $this->assertStringContainsString('土地情報（テーブル形式）', $html);
        $this->assertStringContainsString('land-info-table', $html);
        $this->assertStringContainsString('universal-table-wrapper', $html);
    }

    public function test_land_info_standardized_table_view_handles_no_land_info()
    {
        // Create a user for authentication
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $facility = Facility::factory()->create();
        $landInfo = null; // No land info

        $view = view('facilities.land-info.partials.standardized-table', compact('facility', 'landInfo'));
        $html = $view->render();

        // Verify the view renders the empty state
        $this->assertStringContainsString('土地情報が登録されていません', $html);
    }
}