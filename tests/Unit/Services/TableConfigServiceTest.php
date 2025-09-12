<?php

namespace Tests\Unit\Services;

use App\Services\TableConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class TableConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    private TableConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TableConfigService();
    }

    public function test_can_get_basic_info_table_config()
    {
        $config = $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('columns', $config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertArrayHasKey('styling', $config);
        $this->assertArrayHasKey('features', $config);
    }

    public function test_can_get_service_info_table_config()
    {
        $config = $this->service->getTableConfig(TableConfigService::SERVICE_INFO_TABLE);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('columns', $config);
        $this->assertEquals('grouped_rows', $config['layout']['type']);
    }

    public function test_can_get_land_info_table_config()
    {
        $config = $this->service->getTableConfig(TableConfigService::LAND_INFO_TABLE);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('columns', $config);
        $this->assertEquals('standard_table', $config['layout']['type']);
    }

    public function test_throws_exception_for_invalid_table_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table type: invalid_type');

        $this->service->getTableConfig('invalid_type');
    }

    public function test_validates_valid_config()
    {
        $validConfig = [
            'columns' => [
                [
                    'key' => 'test_field',
                    'label' => 'Test Field',
                    'type' => 'text'
                ]
            ]
        ];

        $result = $this->service->validateConfig($validConfig);
        $this->assertTrue($result);
    }

    public function test_validates_invalid_config_missing_columns()
    {
        $invalidConfig = [];

        $result = $this->service->validateConfig($invalidConfig);
        $this->assertFalse($result);
    }

    public function test_validates_invalid_config_missing_required_fields()
    {
        $invalidConfig = [
            'columns' => [
                [
                    'key' => 'test_field'
                    // Missing 'label' and 'type'
                ]
            ]
        ];

        $result = $this->service->validateConfig($invalidConfig);
        $this->assertFalse($result);
    }

    public function test_validates_invalid_column_type()
    {
        $invalidConfig = [
            'columns' => [
                [
                    'key' => 'test_field',
                    'label' => 'Test Field',
                    'type' => 'invalid_type'
                ]
            ]
        ];

        $result = $this->service->validateConfig($invalidConfig);
        $this->assertFalse($result);
    }

    public function test_validates_select_column_with_options()
    {
        $validConfig = [
            'columns' => [
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive'
                    ]
                ]
            ]
        ];

        $result = $this->service->validateConfig($validConfig);
        $this->assertTrue($result);
    }

    public function test_validates_select_column_without_options()
    {
        $invalidConfig = [
            'columns' => [
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'type' => 'select'
                    // Missing 'options'
                ]
            ]
        ];

        $result = $this->service->validateConfig($invalidConfig);
        $this->assertFalse($result);
    }

    public function test_merges_with_defaults()
    {
        $customConfig = [
            'columns' => [
                [
                    'key' => 'custom_field',
                    'label' => 'Custom Field',
                    'type' => 'text'
                ]
            ]
        ];

        $merged = $this->service->mergeWithDefaults($customConfig);

        $this->assertArrayHasKey('columns', $merged);
        $this->assertArrayHasKey('layout', $merged);
        $this->assertArrayHasKey('styling', $merged);
        $this->assertArrayHasKey('features', $merged);
        $this->assertEquals('custom_field', $merged['columns'][0]['key']);
    }

    public function test_gets_available_table_types()
    {
        $types = $this->service->getAvailableTableTypes();

        $this->assertIsArray($types);
        $this->assertContains(TableConfigService::BASIC_INFO_TABLE, $types);
        $this->assertContains(TableConfigService::SERVICE_INFO_TABLE, $types);
        $this->assertContains(TableConfigService::LAND_INFO_TABLE, $types);
    }

    public function test_gets_comment_section_config()
    {
        $config = $this->service->getCommentSectionConfig(TableConfigService::BASIC_INFO_TABLE);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('section_name', $config);
        $this->assertArrayHasKey('display_name', $config);
        $this->assertArrayHasKey('enabled', $config);
    }

    public function test_gets_global_settings()
    {
        $settings = $this->service->getGlobalSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('responsive', $settings);
        $this->assertArrayHasKey('performance', $settings);
        $this->assertArrayHasKey('validation', $settings);
    }

    public function test_caches_configuration_when_enabled()
    {
        Config::set('table-config.global_settings.performance.cache_enabled', true);
        Cache::flush();

        // First call should cache the result
        $config1 = $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);
        
        // Second call should use cached result
        $config2 = $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);

        $this->assertEquals($config1, $config2);
    }

    public function test_clears_cache()
    {
        // Set up cache
        $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);
        
        // Clear cache
        $result = $this->service->clearCache(TableConfigService::BASIC_INFO_TABLE);
        
        $this->assertTrue($result);
    }

    public function test_clears_all_caches()
    {
        // Set up caches
        $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);
        $this->service->getTableConfig(TableConfigService::SERVICE_INFO_TABLE);
        
        // Clear all caches
        $result = $this->service->clearCache();
        
        $this->assertTrue($result);
    }

    public function test_falls_back_to_defaults_for_missing_config()
    {
        // Temporarily remove config
        Config::set('table-config.tables.basic_info', null);
        
        $config = $this->service->getTableConfig(TableConfigService::BASIC_INFO_TABLE);
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('layout', $config);
        $this->assertEquals('key_value_pairs', $config['layout']['type']);
    }
}