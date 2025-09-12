<?php

namespace Tests\Unit\Services;

use App\Services\TableDataFormatter;
use Tests\TestCase;

class TableDataFormatterCellMergingTest extends TestCase
{
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TableDataFormatter();
    }

    public function test_process_horizontal_cell_merging()
    {
        $data = [
            ['name' => 'John', 'first_name' => 'John', 'last_name' => 'Doe', 'age' => 30],
            ['name' => 'Jane', 'first_name' => 'Jane', 'last_name' => 'Smith', 'age' => 25],
        ];

        $mergingConfig = [
            [
                'type' => 'horizontal',
                'columns' => ['name', 'first_name', 'last_name'],
                'separator' => ' ',
                'condition' => ['type' => 'always']
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);

        $this->assertCount(2, $result);
        
        // Check first row
        $this->assertEquals('John John Doe', $result[0]['name']);
        $this->assertArrayHasKey('_merged_horizontal', $result[0]);
        $this->assertEquals(3, $result[0]['_merged_horizontal']['name']['colspan']);
        $this->assertContains('first_name', $result[0]['_hidden_columns']);
        $this->assertContains('last_name', $result[0]['_hidden_columns']);
    }

    public function test_process_horizontal_cell_merging_with_template()
    {
        $data = [
            ['full_name' => '', 'first_name' => 'John', 'last_name' => 'Doe'],
        ];

        $mergingConfig = [
            [
                'type' => 'horizontal',
                'columns' => ['full_name', 'first_name', 'last_name'],
                'template' => '{first_name} {last_name}',
                'condition' => ['type' => 'always']
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);

        $this->assertEquals('John Doe', $result[0]['full_name']);
    }

    public function test_process_vertical_cell_merging()
    {
        $data = [
            ['category' => 'Web', 'service' => 'Website A'],
            ['category' => 'Web', 'service' => 'Website B'],
            ['category' => 'API', 'service' => 'API A'],
        ];

        $mergingConfig = [
            [
                'type' => 'vertical',
                'columns' => ['category'],
                'group_by' => 'category'
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);

        // Check first row (first in Web group)
        $this->assertTrue($result[0]['_merged_vertical']['category']['is_first']);
        $this->assertEquals(2, $result[0]['_merged_vertical']['category']['rowspan']);

        // Check second row (second in Web group)
        $this->assertFalse($result[1]['_merged_vertical']['category']['is_first']);
        $this->assertEquals(0, $result[1]['_merged_vertical']['category']['rowspan']);

        // Check third row (first in API group)
        $this->assertTrue($result[2]['_merged_vertical']['category']['is_first']);
        $this->assertEquals(1, $result[2]['_merged_vertical']['category']['rowspan']);
    }

    public function test_process_complex_cell_merging()
    {
        $data = [
            ['category' => 'Web', 'name' => 'Site', 'type' => 'Frontend', 'status' => 'Active'],
            ['category' => 'Web', 'name' => 'API', 'type' => 'Backend', 'status' => 'Active'],
        ];

        $mergingConfig = [
            [
                'type' => 'complex',
                'horizontal' => [
                    'columns' => ['name', 'type'],
                    'separator' => ' - '
                ],
                'vertical' => [
                    'columns' => ['category'],
                    'group_by' => 'category'
                ]
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);

        // Check horizontal merging
        $this->assertEquals('Site - Frontend', $result[0]['name']);
        $this->assertEquals('API - Backend', $result[1]['name']);

        // Check vertical merging
        $this->assertTrue($result[0]['_merged_vertical']['category']['is_first']);
        $this->assertEquals(2, $result[0]['_merged_vertical']['category']['rowspan']);
    }

    public function test_process_nested_data()
    {
        $data = [
            [
                'category' => 'Web Services',
                'description' => 'Web-related services',
                'sub_services' => [
                    ['name' => 'Website A', 'status' => 'active'],
                    ['name' => 'Website B', 'status' => 'inactive']
                ]
            ]
        ];

        $nestingConfig = [
            'nested_field' => 'sub_services',
            'parent_fields' => ['category', 'description'],
            'child_fields' => ['name', 'status'],
            'max_depth' => 2
        ];

        $result = $this->formatter->processNestedData($data, $nestingConfig);

        $this->assertCount(2, $result); // Should expand to 2 rows

        // Check first expanded row
        $this->assertEquals('Web Services', $result[0]['category']);
        $this->assertEquals('Website A', $result[0]['name']);
        $this->assertEquals('active', $result[0]['status']);
        $this->assertTrue($result[0]['_nested_info']['is_first_child']);
        $this->assertEquals(0, $result[0]['_nested_info']['child_index']);

        // Check second expanded row
        $this->assertEquals('Web Services', $result[1]['category']);
        $this->assertEquals('Website B', $result[1]['name']);
        $this->assertEquals('inactive', $result[1]['status']);
        $this->assertTrue($result[1]['_nested_info']['is_last_child']);
        $this->assertEquals(1, $result[1]['_nested_info']['child_index']);
    }

    public function test_generate_nested_display_structure()
    {
        $data = [
            ['category' => 'Web', '_nested_info' => ['depth' => 0]],
            ['service' => 'Website A', '_nested_info' => ['depth' => 1, 'is_first_child' => true]],
            ['service' => 'Website B', '_nested_info' => ['depth' => 1, 'is_last_child' => true]],
        ];

        $hierarchyConfig = [
            'levels' => [
                ['field' => 'category', 'condition' => ['type' => 'field_not_empty', 'field' => 'category']],
                ['field' => 'service', 'condition' => ['type' => 'depth_equals', 'depth' => 1]]
            ],
            'indent_size' => 20,
            'show_connectors' => true
        ];

        $result = $this->formatter->generateNestedDisplayStructure($data, $hierarchyConfig);

        $this->assertCount(3, $result);

        // Check root level
        $this->assertEquals(0, $result[0]['_display_structure']['level']);
        $this->assertEquals('0px', $result[0]['_display_structure']['indent']);
        $this->assertFalse($result[0]['_display_structure']['show_connector']);

        // Check child levels
        $this->assertEquals(1, $result[1]['_display_structure']['level']);
        $this->assertEquals('20px', $result[1]['_display_structure']['indent']);
        $this->assertTrue($result[1]['_display_structure']['show_connector']);
        $this->assertEquals('start', $result[1]['_display_structure']['connector_type']);

        $this->assertEquals(1, $result[2]['_display_structure']['level']);
        $this->assertEquals('20px', $result[2]['_display_structure']['indent']);
        $this->assertTrue($result[2]['_display_structure']['show_connector']);
        $this->assertEquals('end', $result[2]['_display_structure']['connector_type']);
    }

    public function test_merge_condition_evaluation()
    {
        $data = [
            ['name' => 'John', 'details' => 'Some details'],
            ['name' => 'Jane', 'details' => ''],
        ];

        $mergingConfig = [
            [
                'type' => 'horizontal',
                'columns' => ['name', 'details'],
                'separator' => ' - ',
                'condition' => [
                    'type' => 'field_not_empty',
                    'field' => 'details'
                ]
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);

        // First row should be merged (has details)
        $this->assertEquals('John - Some details', $result[0]['name']);
        $this->assertArrayHasKey('_merged_horizontal', $result[0]);

        // Second row should not be merged (no details)
        $this->assertEquals('Jane', $result[1]['name']);
        $this->assertArrayNotHasKey('_merged_horizontal', $result[1]);
    }

    public function test_empty_data_handling()
    {
        $data = [];
        $mergingConfig = [
            [
                'type' => 'horizontal',
                'columns' => ['name', 'details']
            ]
        ];

        $result = $this->formatter->processCellMerging($data, $mergingConfig);
        $this->assertEmpty($result);

        $nestingConfig = [
            'nested_field' => 'sub_items',
            'parent_fields' => ['name'],
            'child_fields' => ['detail']
        ];

        $result = $this->formatter->processNestedData($data, $nestingConfig);
        $this->assertEmpty($result);
    }

    public function test_nested_data_without_nested_field()
    {
        $data = [
            ['name' => 'Item 1', 'status' => 'active']
        ];

        $nestingConfig = [
            'nested_field' => 'sub_items', // This field doesn't exist
            'parent_fields' => ['name'],
            'child_fields' => ['status']
        ];

        $result = $this->formatter->processNestedData($data, $nestingConfig);

        $this->assertCount(1, $result);
        $this->assertEquals($data[0], $result[0]); // Should return original data unchanged
    }
}