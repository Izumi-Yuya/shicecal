<?php

namespace Tests\Unit\Services;

use App\Services\TableViewHelper;
use App\Services\TableConfigService;
use App\Services\TableDataFormatter;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery;

class TableViewHelperTest extends TestCase
{
    protected TableViewHelper $helper;
    protected $configService;
    protected $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->configService = Mockery::mock(TableConfigService::class);
        $this->formatter = Mockery::mock(TableDataFormatter::class);
        $this->helper = new TableViewHelper($this->configService, $this->formatter);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_prepare_table_data_with_array()
    {
        $data = [['name' => 'Test', 'email' => 'test@example.com']];
        $config = [
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email']
            ],
            'layout' => ['type' => 'standard_table'],
            'global_settings' => ['responsive' => ['enabled' => true]]
        ];
        
        $this->configService->shouldReceive('getTableConfig')
            ->with('basic_info')
            ->andReturn($config);
            
        $this->formatter->shouldReceive('formatTableData')
            ->with($data, $config)
            ->andReturn($data);

        $result = $this->helper->prepareTableData($data, 'basic_info');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertEquals($data, $result['data']);
        $this->assertEquals('basic_info', $result['metadata']['table_type']);
        $this->assertEquals(1, $result['metadata']['row_count']);
        $this->assertEquals(2, $result['metadata']['column_count']);
        $this->assertTrue($result['metadata']['has_data']);
        $this->assertEquals('standard_table', $result['metadata']['layout_type']);
        $this->assertTrue($result['metadata']['responsive_enabled']);
    }

    public function test_prepare_table_data_with_collection()
    {
        $data = collect([['name' => 'Test']]);
        $config = [
            'columns' => [['key' => 'name', 'label' => 'Name']],
            'layout' => ['type' => 'key_value_pairs']
        ];
        
        $this->configService->shouldReceive('getTableConfig')
            ->with('basic_info')
            ->andReturn($config);
            
        $this->formatter->shouldReceive('formatTableData')
            ->with($data->toArray(), $config)
            ->andReturn($data->toArray());

        $result = $this->helper->prepareTableData($data, 'basic_info');

        $this->assertEquals($data->toArray(), $result['data']);
    }

    public function test_prepare_table_data_with_empty_data()
    {
        $data = [];
        $config = ['columns' => []];
        
        $this->configService->shouldReceive('getTableConfig')
            ->with('basic_info')
            ->andReturn($config);
            
        $this->formatter->shouldReceive('formatTableData')
            ->with($data, $config)
            ->andReturn($data);

        $result = $this->helper->prepareTableData($data, 'basic_info');

        $this->assertEquals(0, $result['metadata']['row_count']);
        $this->assertFalse($result['metadata']['has_data']);
    }

    public function test_generate_table_classes_basic()
    {
        $config = [
            'styling' => ['table_class' => 'table table-bordered'],
            'layout' => ['type' => 'key_value_pairs'],
            'global_settings' => ['responsive' => ['enabled' => true]],
            'features' => []
        ];

        $result = $this->helper->generateTableClasses($config);

        $this->assertStringContainsString('table table-bordered', $result);
        $this->assertStringContainsString('table-layout-key-value-pairs', $result);
        $this->assertStringContainsString('table-responsive-lg', $result);
    }

    public function test_generate_table_classes_with_features()
    {
        $config = [
            'styling' => ['table_class' => 'table'],
            'layout' => ['type' => 'standard_table'],
            'global_settings' => ['responsive' => ['enabled' => true]],
            'features' => ['sorting' => true, 'filtering' => true]
        ];

        $result = $this->helper->generateTableClasses($config);

        $this->assertStringContainsString('table-sortable', $result);
        $this->assertStringContainsString('table-filterable', $result);
    }

    public function test_generate_table_classes_with_options()
    {
        $config = [
            'styling' => ['table_class' => 'table'],
            'layout' => ['type' => 'standard_table'],
            'global_settings' => ['responsive' => ['enabled' => false]],
            'features' => []
        ];
        $options = [
            'size' => 'sm',
            'state' => 'hover',
            'additional_classes' => ['custom-class', 'another-class']
        ];

        $result = $this->helper->generateTableClasses($config, $options);

        $this->assertStringContainsString('table-sm', $result);
        $this->assertStringContainsString('table-hover', $result);
        $this->assertStringContainsString('custom-class', $result);
        $this->assertStringContainsString('another-class', $result);
    }

    public function test_calculate_column_widths_equal_distribution()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address']
        ];

        $result = $this->helper->calculateColumnWidths($columns);

        $this->assertCount(4, $result);
        $this->assertEquals('25%', $result[0]);
        $this->assertEquals('25%', $result[1]);
        $this->assertEquals('25%', $result[2]);
        $this->assertEquals('25%', $result[3]);
    }

    public function test_calculate_column_widths_with_specified_widths()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'width' => '30%'],
            ['key' => 'email', 'label' => 'Email', 'width' => '40%'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'address', 'label' => 'Address']
        ];

        $result = $this->helper->calculateColumnWidths($columns);

        $this->assertEquals('30%', $result[0]);
        $this->assertEquals('40%', $result[1]);
        $this->assertEquals('15%', $result[2]); // (100 - 30 - 40) / 2
        $this->assertEquals('15%', $result[3]); // (100 - 30 - 40) / 2
    }

    public function test_calculate_column_widths_with_pixel_values()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'width' => '200px'],
            ['key' => 'email', 'label' => 'Email']
        ];

        $result = $this->helper->calculateColumnWidths($columns);

        $this->assertEquals('16.666666666667%', $result[0]); // 200px / 1200px * 100 (raw calculation)
        $this->assertEquals('83.33%', $result[1]); // Remaining width rounded to 2 decimals
    }

    public function test_calculate_column_widths_with_minimum_width()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email']
        ];
        $options = ['min_column_width' => 200, 'screen_width' => 800];

        $result = $this->helper->calculateColumnWidths($columns, $options);

        // Each column would be 50% of 800px = 400px, which is > 200px minimum
        $this->assertEquals('50%', $result[0]);
        $this->assertEquals('50%', $result[1]);
    }

    public function test_calculate_column_widths_enforces_minimum_width()
    {
        $columns = [
            ['key' => 'col1', 'label' => 'Col1'],
            ['key' => 'col2', 'label' => 'Col2'],
            ['key' => 'col3', 'label' => 'Col3'],
            ['key' => 'col4', 'label' => 'Col4'],
            ['key' => 'col5', 'label' => 'Col5']
        ];
        $options = ['min_column_width' => 200, 'screen_width' => 800];

        $result = $this->helper->calculateColumnWidths($columns, $options);

        // Each column would be 20% of 800px = 160px, which is < 200px minimum
        foreach ($result as $width) {
            $this->assertStringContainsString('min-width: 200px', $width);
        }
    }

    public function test_generate_row_classes_basic()
    {
        $rowData = ['name' => 'Test', 'status' => 'active'];
        $index = 0;

        $result = $this->helper->generateRowClasses($rowData, $index);

        $this->assertStringContainsString('table-row', $result);
        $this->assertStringContainsString('row-even', $result);
        $this->assertStringContainsString('row-status-active', $result);
    }

    public function test_generate_row_classes_odd_row()
    {
        $rowData = ['name' => 'Test'];
        $index = 1;

        $result = $this->helper->generateRowClasses($rowData, $index);

        $this->assertStringContainsString('row-odd', $result);
    }

    public function test_generate_row_classes_with_group_data()
    {
        $rowData = ['name' => 'Test', '_group_value' => 'Group A', '_rowspan' => 2];
        $index = 0;

        $result = $this->helper->generateRowClasses($rowData, $index);

        $this->assertStringContainsString('row-group-group-a', $result);
        $this->assertStringContainsString('row-group-first', $result);
    }

    public function test_generate_header_classes_basic()
    {
        $column = ['key' => 'name', 'label' => 'Name', 'type' => 'text'];
        $config = ['styling' => ['header_class' => 'bg-primary text-white']];

        $result = $this->helper->generateHeaderClasses($column, $config);

        $this->assertStringContainsString('bg-primary text-white', $result);
        $this->assertStringContainsString('header-type-text', $result);
    }

    public function test_generate_header_classes_with_features()
    {
        $column = [
            'key' => 'name',
            'label' => 'Name',
            'type' => 'text',
            'required' => true,
            'sortable' => true,
            'align' => 'center'
        ];
        $config = ['styling' => ['header_class' => 'bg-secondary']];

        $result = $this->helper->generateHeaderClasses($column, $config);

        $this->assertStringContainsString('header-required', $result);
        $this->assertStringContainsString('header-sortable', $result);
        $this->assertStringContainsString('text-center', $result);
    }

    public function test_generate_cell_classes_with_value()
    {
        $value = 'Test Value';
        $column = ['type' => 'text', 'align' => 'left'];
        $config = [];

        $result = $this->helper->generateCellClasses($value, $column, $config);

        $this->assertStringContainsString('table-cell', $result);
        $this->assertStringContainsString('cell-type-text', $result);
        $this->assertStringContainsString('cell-has-value', $result);
        $this->assertStringContainsString('text-left', $result);
    }

    public function test_generate_cell_classes_empty_value()
    {
        $value = null;
        $column = ['type' => 'text'];
        $config = ['styling' => ['empty_value_class' => 'text-muted']];

        $result = $this->helper->generateCellClasses($value, $column, $config);

        $this->assertStringContainsString('cell-empty', $result);
        $this->assertStringContainsString('text-muted', $result);
    }

    public function test_generate_cell_classes_number_type_auto_align()
    {
        $value = 123;
        $column = ['type' => 'number'];
        $config = [];

        $result = $this->helper->generateCellClasses($value, $column, $config);

        $this->assertStringContainsString('text-end', $result);
    }

    public function test_prepare_key_value_data()
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com', 'phone' => '123-456-7890'];
        $config = [
            'columns' => [
                ['key' => 'name', 'label' => 'Name'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'phone', 'label' => 'Phone']
            ],
            'layout' => ['columns_per_row' => 2]
        ];

        $this->formatter->shouldReceive('formatValue')
            ->andReturn('formatted_value');

        $result = $this->helper->prepareKeyValueData($data, $config);

        $this->assertCount(2, $result); // 3 columns / 2 per row = 2 rows
        $this->assertCount(2, $result[0]); // First row has 2 columns
        $this->assertCount(1, $result[1]); // Second row has 1 column
        
        $this->assertEquals('name', $result[0][0]['key']);
        $this->assertEquals('Name', $result[0][0]['label']);
        $this->assertEquals('Test', $result[0][0]['value']);
    }

    public function test_prepare_grouped_data()
    {
        $data = [
            ['type' => 'A', 'name' => 'Item 1'],
            ['type' => 'A', 'name' => 'Item 2'],
            ['type' => 'B', 'name' => 'Item 3']
        ];
        $config = ['layout' => ['group_by' => 'type']];

        $this->formatter->shouldReceive('groupDataBy')
            ->with($data, 'type')
            ->andReturn([
                'A' => [
                    ['type' => 'A', 'name' => 'Item 1'],
                    ['type' => 'A', 'name' => 'Item 2']
                ],
                'B' => [
                    ['type' => 'B', 'name' => 'Item 3']
                ]
            ]);

        $result = $this->helper->prepareGroupedData($data, $config);

        $this->assertCount(2, $result);
        $this->assertEquals('A', $result[0]['group_value']);
        $this->assertEquals(2, $result[0]['group_count']);
        $this->assertTrue($result[0]['items'][0]['_is_first_in_group']);
        $this->assertFalse($result[0]['items'][1]['_is_first_in_group']);
    }

    public function test_get_responsive_css()
    {
        $config = [
            'global_settings' => [
                'responsive' => [
                    'breakpoints' => [
                        'lg' => '992px',
                        'md' => '768px'
                    ]
                ]
            ]
        ];

        $result = $this->helper->getResponsiveCSS($config);

        $this->assertStringContainsString('@media (max-width: 992px)', $result);
        $this->assertStringContainsString('.table-responsive-lg', $result);
        $this->assertStringContainsString('overflow-x: auto', $result);
        $this->assertStringContainsString('横スクロールできます', $result);
    }

    public function test_sanitize_html_class()
    {
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($this->helper);
        $method = $reflection->getMethod('sanitizeHtmlClass');
        $method->setAccessible(true);

        $result = $method->invoke($this->helper, 'Test Class Name!@#');
        
        $this->assertEquals('test-class-name', $result);
    }

    public function test_parse_width_percentage()
    {
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($this->helper);
        $method = $reflection->getMethod('parseWidth');
        $method->setAccessible(true);

        $result = $method->invoke($this->helper, '25%');
        
        $this->assertEquals(25.0, $result);
    }

    public function test_parse_width_pixels()
    {
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($this->helper);
        $method = $reflection->getMethod('parseWidth');
        $method->setAccessible(true);

        $result = $method->invoke($this->helper, '200px');
        
        $this->assertEqualsWithDelta(16.666666666666668, $result, 0.01); // 200/1200 * 100
    }
}