<?php

namespace Tests\Feature;

use App\Services\TableConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableConfigIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TableConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TableConfigService::class);
    }

    public function test_basic_info_table_has_correct_structure()
    {
        $config = $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);

        // Verify basic structure
        $this->assertArrayHasKey('columns', $config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('styling', $config);
        $this->assertArrayHasKey('features', $config);

        // Verify layout type
        $this->assertEquals('key_value_pairs', $config['layout']['type']);

        // Verify has expected columns
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('company_name', $columnKeys);
        $this->assertContains('facility_name', $columnKeys);
        $this->assertContains('address', $columnKeys);

        // Verify comment feature is enabled
        $this->assertTrue($config['features']['comments']);
    }

    public function test_service_info_table_has_correct_structure()
    {
        $config = $this->service->getTableConfig(TableConfigService::SERVICE_INFO_TABLE);

        // Verify layout type
        $this->assertEquals('grouped_rows', $config['layout']['type']);

        // Verify has expected columns
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('service_type', $columnKeys);
        $this->assertContains('renewal_period', $columnKeys);

        // Verify rowspan grouping is configured
        $serviceTypeColumn = collect($config['columns'])->firstWhere('key', 'service_type');
        $this->assertTrue($serviceTypeColumn['rowspan_group'] ?? false);

        // Verify date range formatting
        $renewalColumn = collect($config['columns'])->firstWhere('key', 'renewal_period');
        $this->assertEquals('date_range', $renewalColumn['type']);
        $this->assertEquals('〜', $renewalColumn['separator']);
    }

    public function test_land_info_table_has_correct_structure()
    {
        $config = $this->service->getTableConfig(TableConfigService::LAND_INFO_TABLE);

        // Verify layout type
        $this->assertEquals('standard_table', $config['layout']['type']);

        // Verify has expected columns
        $columnKeys = array_column($config['columns'], 'key');
        $this->assertContains('land_type', $columnKeys);
        $this->assertContains('area', $columnKeys);
        $this->assertContains('monthly_cost', $columnKeys);

        // Verify select column has options
        $landTypeColumn = collect($config['columns'])->firstWhere('key', 'land_type');
        $this->assertEquals('select', $landTypeColumn['type']);
        $this->assertArrayHasKey('options', $landTypeColumn);
        $this->assertArrayHasKey('owned', $landTypeColumn['options']);

        // Verify number columns have units
        $areaColumn = collect($config['columns'])->firstWhere('key', 'area');
        $this->assertEquals('number', $areaColumn['type']);
        $this->assertEquals('㎡', $areaColumn['unit']);
    }

    public function test_comment_sections_are_configured()
    {
        $basicInfoComment = $this->service->getCommentSectionConfig(TableConfigService::BASIC_INFO_TABLE);
        $this->assertEquals('basic_info', $basicInfoComment['section_name']);
        $this->assertEquals('基本情報', $basicInfoComment['display_name']);
        $this->assertTrue($basicInfoComment['enabled']);

        $serviceInfoComment = $this->service->getCommentSectionConfig(TableConfigService::SERVICE_INFO_TABLE);
        $this->assertEquals('service_info', $serviceInfoComment['section_name']);
        $this->assertEquals('サービス情報', $serviceInfoComment['display_name']);

        $landInfoComment = $this->service->getCommentSectionConfig(TableConfigService::LAND_INFO_TABLE);
        $this->assertEquals('land_info', $landInfoComment['section_name']);
        $this->assertEquals('土地情報', $landInfoComment['display_name']);
    }

    public function test_global_settings_are_configured()
    {
        $settings = $this->service->getGlobalSettings();

        // Verify responsive settings
        $this->assertTrue($settings['responsive']['enabled']);
        $this->assertTrue($settings['responsive']['pc_only']);
        $this->assertArrayHasKey('breakpoints', $settings['responsive']);

        // Verify performance settings
        $this->assertTrue($settings['performance']['cache_enabled']);
        $this->assertEquals(300, $settings['performance']['cache_ttl']);

        // Verify validation settings
        $this->assertTrue($settings['validation']['strict_mode']);
        $this->assertContains('key', $settings['validation']['required_fields']);
        $this->assertContains('label', $settings['validation']['required_fields']);
        $this->assertContains('type', $settings['validation']['required_fields']);
    }

    public function test_service_can_be_resolved_from_container()
    {
        $service = app(TableConfigService::class);
        $this->assertInstanceOf(TableConfigService::class, $service);

        // Verify it's a singleton
        $service2 = app(TableConfigService::class);
        $this->assertSame($service, $service2);
    }
}