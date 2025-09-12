<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use App\Services\TableViewHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableComponentRenderingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;
    private TableConfigService $configService;
    private TableDataFormatter $formatter;
    private TableViewHelper $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001'
        ]);
        
        $this->configService = app(TableConfigService::class);
        $this->formatter = app(TableDataFormatter::class);
        $this->viewHelper = app(TableViewHelper::class);
    }

    public function test_key_value_pairs_table_component_renders()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.universal-table.key-value-pairs', [
            'config' => $config,
            'data' => $data,
            'tableId' => 'test-kv-table'
        ]);

        $html = $view->render();

        // Assert basic structure
        $this->assertStringContainsString('id="test-kv-table"', $html);
        $this->assertStringContainsString('table', $html);
        
        // Assert data is displayed
        $this->assertStringContainsString('テスト施設', $html);
        $this->assertStringContainsString('テスト会社', $html);
        $this->assertStringContainsString('TEST001', $html);
    }

    public function test_standard_table_component_renders()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('service_info');
        $data = [
            ['service_type' => 'Web', 'service_name' => 'Website', 'status' => 'active'],
            ['service_type' => 'API', 'service_name' => 'REST API', 'status' => 'active']
        ];

        $view = view('components.universal-table.standard-table', [
            'config' => $config,
            'data' => $data,
            'tableId' => 'test-standard-table'
        ]);

        $html = $view->render();

        // Assert table structure
        $this->assertStringContainsString('id="test-standard-table"', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        
        // Assert data is displayed
        $this->assertStringContainsString('Web', $html);
        $this->assertStringContainsString('Website', $html);
        $this->assertStringContainsString('API', $html);
        $this->assertStringContainsString('REST API', $html);
    }

    public function test_grouped_rows_table_component_renders()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('service_info');
        $data = [
            ['service_type' => 'Web', 'service_name' => 'Website A', 'status' => 'active'],
            ['service_type' => 'Web', 'service_name' => 'Website B', 'status' => 'inactive'],
            ['service_type' => 'API', 'service_name' => 'API Service', 'status' => 'active']
        ];

        // Process data for rowspan grouping
        $processedData = $this->formatter->calculateRowspanValues($data, $config['columns']);

        $view = view('components.universal-table.grouped-rows', [
            'config' => $config,
            'data' => $processedData,
            'tableId' => 'test-grouped-table'
        ]);

        $html = $view->render();

        // Assert table structure
        $this->assertStringContainsString('id="test-grouped-table"', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        
        // Assert grouped data is displayed
        $this->assertStringContainsString('Web', $html);
        $this->assertStringContainsString('API', $html);
        $this->assertStringContainsString('Website A', $html);
        $this->assertStringContainsString('API Service', $html);
    }

    public function test_table_comment_wrapper_integration()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $this->facility->toArray();

        $view = view('components.table-comment-wrapper', [
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'facility' => $this->facility,
            'commentEnabled' => true
        ]);

        $html = $view->render();

        // Assert comment integration
        $this->assertStringContainsString('comment-toggle', $html);
        $this->assertStringContainsString('data-section="basic_info"', $html);
        $this->assertStringContainsString('comment-section', $html);
        
        // Assert table content is included
        $this->assertStringContainsString('テスト施設', $html);
    }

    public function test_table_data_formatting_integration()
    {
        $this->actingAs($this->user);

        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'opening_date' => \Carbon\Carbon::parse('2020-01-01'),
            'years_in_operation' => 4,
            'capacity' => 100,
            'email' => 'test@example.com',
            'website_url' => 'example.com'
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $formattedData = $this->formatter->formatTableData($facility->toArray(), $config);

        $view = view('components.universal-table.key-value-pairs', [
            'config' => $config,
            'data' => $formattedData,
            'tableId' => 'formatted-data-table'
        ]);

        $html = $view->render();

        // Assert formatted data is displayed correctly
        $this->assertMatchesRegularExpression('/\d{4}年\d{1,2}月\d{1,2}日/', $html); // Date formatting
        $this->assertStringContainsString('4年', $html); // Number with unit
        $this->assertStringContainsString('100名', $html); // Capacity with unit
        $this->assertStringContainsString('test@example.com', $html); // Email
        $this->assertStringContainsString('https://example.com', $html); // URL with protocol
    }

    public function test_table_handles_empty_values()
    {
        $this->actingAs($this->user);

        $facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'email' => null,
            'website_url' => '',
            'building_name' => null
        ]);

        $config = $this->configService->getTableConfig('basic_info');
        $data = $facility->toArray();

        $view = view('components.universal-table.key-value-pairs', [
            'config' => $config,
            'data' => $data,
            'tableId' => 'empty-values-table'
        ]);

        $html = $view->render();

        // Assert empty values are handled
        $this->assertStringContainsString('未設定', $html);
        $this->assertStringContainsString('テスト施設', $html); // Non-empty value still shows
    }

    public function test_responsive_table_classes_applied()
    {
        $this->actingAs($this->user);

        $config = $this->configService->getTableConfig('basic_info');
        $tableClasses = $this->viewHelper->generateTableClasses($config);

        $this->assertStringContainsString('table-responsive', $tableClasses);
        $this->assertStringContainsString('table-layout-key-value-pairs', $tableClasses);
    }

    public function test_table_css_class_generation()
    {
        $config = $this->configService->getTableConfig('basic_info');
        
        $tableClasses = $this->viewHelper->generateTableClasses($config, [
            'size' => 'sm',
            'additional_classes' => ['custom-test-class']
        ]);

        $this->assertStringContainsString('table-sm', $tableClasses);
        $this->assertStringContainsString('custom-test-class', $tableClasses);
        $this->assertStringContainsString('table table-bordered', $tableClasses);
    }

    public function test_column_width_calculation_integration()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email', 'width' => '40%'],
            ['key' => 'phone', 'label' => 'Phone']
        ];

        $widths = $this->viewHelper->calculateColumnWidths($columns);

        $this->assertCount(3, $widths);
        $this->assertStringContainsString('40%', $widths[1]); // Specified width
        $this->assertStringContainsString('30%', $widths[0]); // Auto-calculated
        $this->assertStringContainsString('30%', $widths[2]); // Auto-calculated
    }

    public function test_table_data_preparation_integration()
    {
        $data = collect([$this->facility]);
        
        $preparedData = $this->viewHelper->prepareTableData($data, 'basic_info');

        $this->assertArrayHasKey('data', $preparedData);
        $this->assertArrayHasKey('metadata', $preparedData);
        $this->assertArrayHasKey('config', $preparedData);
        
        $this->assertEquals('basic_info', $preparedData['metadata']['table_type']);
        $this->assertTrue($preparedData['metadata']['has_data']);
        $this->assertEquals('key_value_pairs', $preparedData['metadata']['layout_type']);
    }

    public function test_nested_table_component_renders()
    {
        $this->actingAs($this->user);

        $config = [
            'columns' => [
                ['key' => 'parent', 'label' => 'Parent', 'type' => 'text'],
                ['key' => 'child', 'label' => 'Child', 'type' => 'text']
            ],
            'layout' => ['type' => 'nested'],
            'styling' => ['table_class' => 'table table-bordered']
        ];

        $data = [
            [
                'parent' => 'Parent 1',
                'children' => [
                    ['child' => 'Child 1.1'],
                    ['child' => 'Child 1.2']
                ]
            ]
        ];

        $view = view('components.universal-table.nested-table', [
            'config' => $config,
            'data' => $data,
            'tableId' => 'nested-table'
        ]);

        $html = $view->render();

        // Assert nested structure
        $this->assertStringContainsString('id="nested-table"', $html);
        $this->assertStringContainsString('Parent 1', $html);
        $this->assertStringContainsString('Child 1.1', $html);
        $this->assertStringContainsString('Child 1.2', $html);
    }
}