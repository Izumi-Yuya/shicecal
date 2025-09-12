<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TablePerformanceService;
use Illuminate\Support\Facades\Cache;

class TablePerformanceServiceTest extends TestCase
{
    private TablePerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TablePerformanceService();
    }

    public function test_optimize_table_data_with_small_dataset()
    {
        $data = $this->generateTestData(10);
        $config = $this->getTestConfig();
        
        $result = $this->service->optimizeTableData($data, $config);
        
        $this->assertEquals('full_render', $result['optimizations']['strategy']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('optimizations', $result);
    }

    public function test_optimize_table_data_with_medium_dataset()
    {
        $data = $this->generateTestData(75);
        $config = $this->getTestConfig();
        
        $result = $this->service->optimizeTableData($data, $config);
        
        $this->assertEquals('lazy_loading', $result['optimizations']['strategy']);
        $this->assertArrayHasKey('initial_data', $result['data']);
        $this->assertArrayHasKey('remaining_data', $result['data']);
        $this->assertEquals(50, $result['data']['loaded_rows']);
    }

    public function test_optimize_table_data_with_large_dataset()
    {
        $data = $this->generateTestData(250);
        $config = $this->getTestConfig();
        
        $result = $this->service->optimizeTableData($data, $config);
        
        $this->assertEquals('virtual_scroll', $result['optimizations']['strategy']);
        $this->assertArrayHasKey('total_rows', $result['data']);
        $this->assertArrayHasKey('chunk_size', $result['data']);
        $this->assertEquals(250, $result['data']['total_rows']);
        $this->assertEquals(50, $result['data']['chunk_size']);
    }

    public function test_paginate_data()
    {
        $data = $this->generateTestData(150);
        
        $result = $this->service->paginateData($data, 1, 50);
        
        $this->assertCount(50, $result['data']);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(50, $result['pagination']['per_page']);
        $this->assertEquals(150, $result['pagination']['total_items']);
        $this->assertEquals(3, $result['pagination']['total_pages']);
        $this->assertTrue($result['pagination']['has_next']);
        $this->assertFalse($result['pagination']['has_previous']);
    }

    public function test_paginate_data_last_page()
    {
        $data = $this->generateTestData(150);
        
        $result = $this->service->paginateData($data, 3, 50);
        
        $this->assertCount(50, $result['data']);
        $this->assertEquals(3, $result['pagination']['current_page']);
        $this->assertFalse($result['pagination']['has_next']);
        $this->assertTrue($result['pagination']['has_previous']);
    }

    public function test_prepare_lazy_loading_structure()
    {
        $data = $this->generateTestData(100);
        
        $result = $this->service->prepareLazyLoadingStructure($data, 30, 20);
        
        $this->assertCount(30, $result['initial_data']);
        $this->assertEquals(100, $result['lazy_config']['total_items']);
        $this->assertEquals(30, $result['lazy_config']['loaded_items']);
        $this->assertEquals(20, $result['lazy_config']['load_increment']);
        $this->assertTrue($result['lazy_config']['has_more']);
        $this->assertEquals(30, $result['lazy_config']['next_offset']);
    }

    public function test_get_next_lazy_chunk()
    {
        $data = $this->generateTestData(100);
        
        $result = $this->service->getNextLazyChunk($data, 30, 25);
        
        $this->assertCount(25, $result['data']);
        $this->assertEquals(25, $result['meta']['loaded_items']);
        $this->assertEquals(55, $result['meta']['new_offset']);
        $this->assertTrue($result['meta']['has_more']);
        $this->assertEquals(100, $result['meta']['total_items']);
        $this->assertEquals(55.0, $result['meta']['progress_percentage']);
    }

    public function test_optimize_memory_usage_with_large_dataset()
    {
        $data = $this->generateTestData(300);
        $config = $this->getTestConfig();
        
        $result = $this->service->optimizeMemoryUsage($config, $data);
        
        $this->assertArrayHasKey('estimated_memory', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        // Should recommend virtual scrolling for large dataset
        $recommendations = collect($result['recommendations']);
        $virtualScrollRec = $recommendations->firstWhere('type', 'virtual_scrolling');
        $this->assertNotNull($virtualScrollRec);
        $this->assertEquals('Large dataset detected', $virtualScrollRec['reason']);
    }

    public function test_optimize_memory_usage_with_medium_dataset()
    {
        $data = $this->generateTestData(75);
        $config = $this->getTestConfig();
        
        $result = $this->service->optimizeMemoryUsage($config, $data);
        
        $this->assertArrayHasKey('recommendations', $result);
        
        // Should recommend lazy loading for medium dataset
        $recommendations = collect($result['recommendations']);
        $lazyLoadingRec = $recommendations->firstWhere('type', 'lazy_loading');
        $this->assertNotNull($lazyLoadingRec);
        $this->assertEquals('Medium dataset detected', $lazyLoadingRec['reason']);
    }

    public function test_cleanup_data_for_memory()
    {
        $data = [
            ['name' => 'Test 1', 'description' => '  Long description with spaces  ', 'empty_field' => ''],
            ['name' => 'Test 2', 'description' => str_repeat('A', 1500), 'empty_field' => null],
            ['name' => '', 'description' => 'Valid description', 'value' => 0]
        ];
        
        $result = $this->service->cleanupDataForMemory($data, [
            'remove_empty' => true,
            'trim_strings' => true,
            'max_string_length' => 1000
        ]);
        
        // First row should have trimmed description and no empty field
        $this->assertEquals('Long description with spaces', $result[0]['description']);
        $this->assertArrayNotHasKey('empty_field', $result[0]);
        
        // Second row should have truncated description
        $this->assertEquals(1003, strlen($result[1]['description'])); // 1000 + '...'
        $this->assertStringEndsWith('...', $result[1]['description']);
        
        // Third row should keep value 0 but remove empty name
        $this->assertArrayNotHasKey('name', $result[2]);
        $this->assertEquals(0, $result[2]['value']);
    }

    public function test_generate_memory_optimized_config()
    {
        $config = $this->getTestConfig();
        $memoryOptimizations = [
            'aggressive_cleanup' => true,
            'column_virtualization' => true
        ];
        
        $result = $this->service->generateMemoryOptimizedConfig($config, $memoryOptimizations);
        
        $this->assertArrayHasKey('memory_optimizations', $result);
        $this->assertTrue($result['memory_optimizations']['enabled']);
        $this->assertTrue($result['memory_optimizations']['aggressive_cleanup']);
        $this->assertTrue($result['memory_optimizations']['column_virtualization']);
        $this->assertEquals(30000, $result['memory_optimizations']['cleanup_interval']);
        
        $this->assertStringContainsString('memory-optimized', $result['styling']['table_class']);
        $this->assertEquals('true', $result['attributes']['data-memory-optimized']);
    }

    public function test_generate_optimized_css()
    {
        $config = $this->getTestConfig();
        $optimizations = [
            'strategy' => 'virtual_scroll',
            'dom' => ['use_css_containment' => true],
            'css' => ['enable_gpu_acceleration' => true]
        ];
        
        $css = $this->service->generateOptimizedCSS($config, $optimizations);
        
        $this->assertStringContainsString('.performance-optimized', $css);
        $this->assertStringContainsString('contain: layout style paint', $css);
        $this->assertStringContainsString('.virtual-scroll-container', $css);
        $this->assertStringContainsString('transform: translateZ(0)', $css);
    }

    public function test_generate_optimized_javascript()
    {
        $optimizations = [
            'strategy' => 'lazy_loading',
            'js' => [
                'use_event_delegation' => true,
                'cleanup_event_listeners' => true
            ]
        ];
        
        $js = $this->service->generateOptimizedJavaScript($optimizations);
        
        $this->assertStringContainsString('class TablePerformanceManager', $js);
        $this->assertStringContainsString('setupEventDelegation', $js);
        $this->assertStringContainsString('initLazyLoading', $js);
        $this->assertStringContainsString('destroy()', $js);
    }

    public function test_record_and_get_performance_metrics()
    {
        Cache::flush();
        
        $tableType = 'test_table';
        $metrics = [
            'render_time' => 150,
            'memory_usage' => 75.5,
            'rows_rendered' => 100
        ];
        
        $this->service->recordPerformanceMetrics($tableType, $metrics);
        
        $retrievedMetrics = $this->service->getPerformanceMetrics($tableType, 1);
        
        $this->assertCount(1, $retrievedMetrics);
        $this->assertEquals(150, $retrievedMetrics[0]['render_time']);
        $this->assertEquals(75.5, $retrievedMetrics[0]['memory_usage']);
        $this->assertEquals(100, $retrievedMetrics[0]['rows_rendered']);
        $this->assertArrayHasKey('timestamp', $retrievedMetrics[0]);
    }

    private function generateTestData(int $count): array
    {
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => "Description for item {$i}",
                'value' => rand(1, 1000),
                'created_at' => now()->subDays(rand(1, 30))->format('Y-m-d')
            ];
        }
        return $data;
    }

    private function getTestConfig(): array
    {
        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'type' => 'number'],
                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['key' => 'description', 'label' => 'Description', 'type' => 'text'],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'created_at', 'label' => 'Created', 'type' => 'date']
            ],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true
            ],
            'styling' => [
                'table_class' => 'table table-bordered',
                'header_class' => 'bg-primary text-white'
            ],
            'features' => [
                'comments' => false,
                'sorting' => false
            ]
        ];
    }
}