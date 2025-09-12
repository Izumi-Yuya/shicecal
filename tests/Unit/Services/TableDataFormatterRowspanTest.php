<?php

namespace Tests\Unit\Services;

use App\Services\TableDataFormatter;
use Tests\TestCase;

class TableDataFormatterRowspanTest extends TestCase
{
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TableDataFormatter();
    }

    public function test_calculate_rowspan_values_with_single_grouping_column()
    {
        $data = [
            ['service_type' => 'Web', 'service_name' => 'Website A', 'status' => 'active'],
            ['service_type' => 'Web', 'service_name' => 'Website B', 'status' => 'inactive'],
            ['service_type' => 'API', 'service_name' => 'API A', 'status' => 'active'],
            ['service_type' => 'API', 'service_name' => 'API B', 'status' => 'active'],
            ['service_type' => 'API', 'service_name' => 'API C', 'status' => 'pending'],
        ];

        $columns = [
            ['key' => 'service_type', 'label' => 'Service Type', 'rowspan_group' => true],
            ['key' => 'service_name', 'label' => 'Service Name'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $result = $this->formatter->calculateRowspanValues($data, $columns);

        // Check first group (Web)
        $this->assertTrue($result[0]['_rowspan_service_type']['is_first']);
        $this->assertEquals(2, $result[0]['_rowspan_service_type']['group_size']);
        $this->assertFalse($result[1]['_rowspan_service_type']['is_first']);
        $this->assertEquals(2, $result[1]['_rowspan_service_type']['group_size']);

        // Check second group (API)
        $this->assertTrue($result[2]['_rowspan_service_type']['is_first']);
        $this->assertEquals(3, $result[2]['_rowspan_service_type']['group_size']);
        $this->assertFalse($result[3]['_rowspan_service_type']['is_first']);
        $this->assertEquals(3, $result[3]['_rowspan_service_type']['group_size']);
        $this->assertFalse($result[4]['_rowspan_service_type']['is_first']);
        $this->assertEquals(3, $result[4]['_rowspan_service_type']['group_size']);
    }

    public function test_calculate_rowspan_values_with_multiple_grouping_columns()
    {
        $data = [
            ['service_type' => 'Web', 'category' => 'Frontend', 'service_name' => 'Website A'],
            ['service_type' => 'Web', 'category' => 'Frontend', 'service_name' => 'Website B'],
            ['service_type' => 'Web', 'category' => 'Backend', 'service_name' => 'API Gateway'],
            ['service_type' => 'API', 'category' => 'REST', 'service_name' => 'User API'],
            ['service_type' => 'API', 'category' => 'REST', 'service_name' => 'Product API'],
        ];

        $columns = [
            ['key' => 'service_type', 'label' => 'Service Type', 'rowspan_group' => true],
            ['key' => 'category', 'label' => 'Category', 'rowspan_group' => true],
            ['key' => 'service_name', 'label' => 'Service Name'],
        ];

        $result = $this->formatter->calculateRowspanValues($data, $columns);

        // Check service_type grouping
        $this->assertTrue($result[0]['_rowspan_service_type']['is_first']);
        $this->assertEquals(3, $result[0]['_rowspan_service_type']['group_size']);
        $this->assertFalse($result[1]['_rowspan_service_type']['is_first']);
        $this->assertFalse($result[2]['_rowspan_service_type']['is_first']);

        // Check category grouping
        $this->assertTrue($result[0]['_rowspan_category']['is_first']);
        $this->assertEquals(2, $result[0]['_rowspan_category']['group_size']);
        $this->assertFalse($result[1]['_rowspan_category']['is_first']);
        $this->assertEquals(2, $result[1]['_rowspan_category']['group_size']);
        
        $this->assertTrue($result[2]['_rowspan_category']['is_first']);
        $this->assertEquals(1, $result[2]['_rowspan_category']['group_size']);
    }

    public function test_generate_hierarchical_headers()
    {
        $columns = [
            [
                'key' => 'service_name',
                'label' => 'Service Name',
                'header_level' => 1,
                'header_group' => 'basic',
                'header_group_label' => 'Basic Info'
            ],
            [
                'key' => 'service_type',
                'label' => 'Service Type',
                'header_level' => 1,
                'header_group' => 'basic'
            ],
            [
                'key' => 'start_date',
                'label' => 'Start Date',
                'header_level' => 1,
                'header_group' => 'period',
                'header_group_label' => 'Period Info'
            ],
            [
                'key' => 'end_date',
                'label' => 'End Date',
                'header_level' => 1,
                'header_group' => 'period'
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'header_level' => 1
            ]
        ];

        $result = $this->formatter->generateHierarchicalHeaders($columns);

        $this->assertArrayHasKey(1, $result);
        $this->assertCount(3, $result[1]); // basic, period, status

        // Check basic group
        $basicGroup = collect($result[1])->firstWhere('label', 'Basic Info');
        $this->assertNotNull($basicGroup);
        $this->assertEquals(2, $basicGroup['colspan']);
        $this->assertCount(2, $basicGroup['columns']);

        // Check period group
        $periodGroup = collect($result[1])->firstWhere('label', 'Period Info');
        $this->assertNotNull($periodGroup);
        $this->assertEquals(2, $periodGroup['colspan']);
        $this->assertCount(2, $periodGroup['columns']);

        // Check standalone status
        $statusItem = collect($result[1])->firstWhere('label', 'Status');
        $this->assertNotNull($statusItem);
        $this->assertEquals(1, $statusItem['colspan']);
    }

    public function test_process_multi_level_rowspan()
    {
        $data = [
            ['type' => 'Web', 'category' => 'Frontend', 'name' => 'Site A'],
            ['type' => 'Web', 'category' => 'Frontend', 'name' => 'Site B'],
            ['type' => 'Web', 'category' => 'Backend', 'name' => 'API'],
            ['type' => 'API', 'category' => 'REST', 'name' => 'User API'],
        ];

        $groupingConfig = [
            ['group_by' => 'type', 'priority' => 1],
            ['group_by' => 'category', 'priority' => 2],
        ];

        $result = $this->formatter->processMultiLevelRowspan($data, $groupingConfig);

        // Check type grouping
        $this->assertTrue($result[0]['_group_type']['is_first']);
        $this->assertEquals(3, $result[0]['_group_type']['group_size']);
        $this->assertFalse($result[1]['_group_type']['is_first']);
        $this->assertFalse($result[2]['_group_type']['is_first']);
        $this->assertTrue($result[3]['_group_type']['is_first']);
        $this->assertEquals(1, $result[3]['_group_type']['group_size']);

        // Check category grouping
        $this->assertTrue($result[0]['_group_category']['is_first']);
        $this->assertEquals(2, $result[0]['_group_category']['group_size']);
        $this->assertFalse($result[1]['_group_category']['is_first']);
        $this->assertTrue($result[2]['_group_category']['is_first']);
        $this->assertEquals(1, $result[2]['_group_category']['group_size']);
    }

    public function test_calculate_rowspan_values_with_empty_data()
    {
        $data = [];
        $columns = [
            ['key' => 'service_type', 'label' => 'Service Type', 'rowspan_group' => true],
        ];

        $result = $this->formatter->calculateRowspanValues($data, $columns);

        $this->assertEmpty($result);
    }

    public function test_calculate_rowspan_values_with_no_rowspan_columns()
    {
        $data = [
            ['service_name' => 'Website A', 'status' => 'active'],
            ['service_name' => 'Website B', 'status' => 'inactive'],
        ];

        $columns = [
            ['key' => 'service_name', 'label' => 'Service Name'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $result = $this->formatter->calculateRowspanValues($data, $columns);

        $this->assertEquals($data, $result);
    }

    public function test_calculate_rowspan_values_with_null_values()
    {
        $data = [
            ['service_type' => 'Web', 'service_name' => 'Website A'],
            ['service_type' => null, 'service_name' => 'Website B'],
            ['service_type' => null, 'service_name' => 'Website C'],
            ['service_type' => 'API', 'service_name' => 'API A'],
        ];

        $columns = [
            ['key' => 'service_type', 'label' => 'Service Type', 'rowspan_group' => true],
            ['key' => 'service_name', 'label' => 'Service Name'],
        ];

        $result = $this->formatter->calculateRowspanValues($data, $columns);

        // Check Web group
        $this->assertTrue($result[0]['_rowspan_service_type']['is_first']);
        $this->assertEquals(1, $result[0]['_rowspan_service_type']['group_size']);

        // Check null group
        $this->assertTrue($result[1]['_rowspan_service_type']['is_first']);
        $this->assertEquals(2, $result[1]['_rowspan_service_type']['group_size']);
        $this->assertFalse($result[2]['_rowspan_service_type']['is_first']);

        // Check API group
        $this->assertTrue($result[3]['_rowspan_service_type']['is_first']);
        $this->assertEquals(1, $result[3]['_rowspan_service_type']['group_size']);
    }
}