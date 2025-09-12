<?php

namespace Tests\Unit\Services;

use App\Services\TableDataFormatter;
use Tests\TestCase;

class TableDataFormatterDynamicColumnsTest extends TestCase
{
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TableDataFormatter();
    }

    public function test_filter_conditional_columns_with_has_data_condition()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            [
                'key' => 'notes',
                'label' => 'Notes',
                'show_condition' => ['type' => 'has_data']
            ],
            [
                'key' => 'description',
                'label' => 'Description',
                'show_condition' => ['type' => 'has_data']
            ]
        ];

        $data = [
            ['name' => 'Item 1', 'notes' => 'Some notes', 'description' => ''],
            ['name' => 'Item 2', 'notes' => '', 'description' => ''],
        ];

        $result = $this->formatter->filterConditionalColumns($columns, $data);

        $this->assertCount(2, $result); // name and notes should be shown
        $this->assertEquals('name', $result[0]['key']);
        $this->assertEquals('notes', $result[1]['key']);
    }

    public function test_filter_conditional_columns_with_data_equals_condition()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            [
                'key' => 'priority_info',
                'label' => 'Priority Info',
                'show_condition' => [
                    'type' => 'data_equals',
                    'value' => 'high'
                ]
            ]
        ];

        $data = [
            ['name' => 'Item 1', 'priority' => 'high'],
            ['name' => 'Item 2', 'priority' => 'low'],
        ];

        $result = $this->formatter->filterConditionalColumns($columns, $data);

        $this->assertCount(2, $result); // Both columns should be shown since priority equals 'high' exists
        $this->assertEquals('name', $result[0]['key']);
        $this->assertEquals('priority_info', $result[1]['key']);
    }

    public function test_filter_conditional_columns_with_field_exists_condition()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            [
                'key' => 'optional_field',
                'label' => 'Optional Field',
                'show_condition' => [
                    'type' => 'field_exists',
                    'field' => 'optional_field'
                ]
            ]
        ];

        $data = [
            ['name' => 'Item 1'],
            ['name' => 'Item 2', 'optional_field' => 'value'],
        ];

        $result = $this->formatter->filterConditionalColumns($columns, $data);

        $this->assertCount(2, $result); // Both columns should be shown since optional_field exists in at least one row
    }

    public function test_add_dynamic_columns()
    {
        $existingColumns = [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text']
        ];

        $data = [
            ['name' => 'Item 1', 'status' => 'active', 'email' => 'test@example.com', 'amount' => 100],
            ['name' => 'Item 2', 'status' => 'inactive', 'email' => 'test2@example.com', 'amount' => 200],
        ];

        $result = $this->formatter->addDynamicColumns($existingColumns, $data);

        $this->assertCount(4, $result); // Original 2 + 2 dynamic columns

        // Check that email column was added with correct type
        $emailColumn = collect($result)->firstWhere('key', 'email');
        $this->assertNotNull($emailColumn);
        $this->assertEquals('email', $emailColumn['type']);
        $this->assertTrue($emailColumn['dynamic']);

        // Check that amount column was added with correct type
        $amountColumn = collect($result)->firstWhere('key', 'amount');
        $this->assertNotNull($amountColumn);
        $this->assertEquals('number', $amountColumn['type']);
        $this->assertTrue($amountColumn['dynamic']);
    }

    public function test_calculate_optimal_column_widths()
    {
        $columns = [
            ['key' => 'short', 'label' => 'S', 'type' => 'text'],
            ['key' => 'medium_length', 'label' => 'Medium Length', 'type' => 'text'],
            ['key' => 'very_long_column_name', 'label' => 'Very Long Column Name', 'type' => 'text'],
        ];

        $data = [
            ['short' => 'A', 'medium_length' => 'Medium text', 'very_long_column_name' => 'Very long content here'],
            ['short' => 'B', 'medium_length' => 'Another medium', 'very_long_column_name' => 'Even longer content here'],
        ];

        $result = $this->formatter->calculateOptimalColumnWidths($columns, $data);

        $this->assertCount(3, $result);

        // Check that widths were calculated and normalized to 100%
        $totalWidth = 0;
        foreach ($result as $column) {
            $this->assertArrayHasKey('width', $column);
            $width = floatval(str_replace('%', '', $column['width']));
            $totalWidth += $width;
        }

        $this->assertEquals(100.0, $totalWidth, 'Total width should equal 100%', 0.1);
    }

    public function test_infer_column_type_email()
    {
        $columns = [];
        $data = [
            ['email_field' => 'test@example.com'],
            ['email_field' => 'another@test.com'],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $emailColumn = $result[0];
        $this->assertEquals('email', $emailColumn['type']);
    }

    public function test_infer_column_type_url()
    {
        $columns = [];
        $data = [
            ['website' => 'https://example.com'],
            ['website' => 'http://test.com'],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $urlColumn = $result[0];
        $this->assertEquals('url', $urlColumn['type']);
    }

    public function test_infer_column_type_date()
    {
        $columns = [];
        $data = [
            ['created_at' => '2023-01-01 10:00:00'],
            ['created_at' => '2023-02-01 11:00:00'],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $dateColumn = $result[0];
        $this->assertEquals('date', $dateColumn['type']);
    }

    public function test_infer_column_type_number()
    {
        $columns = [];
        $data = [
            ['amount' => 100.50],
            ['amount' => 200.75],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $numberColumn = $result[0];
        $this->assertEquals('number', $numberColumn['type']);
    }

    public function test_generate_column_label()
    {
        $columns = [];
        $data = [
            ['user_name' => 'John'],
            ['phone_number' => '123-456-7890'],
            ['email_address' => 'test@example.com'],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $userNameColumn = collect($result)->firstWhere('key', 'user_name');
        $this->assertEquals('ユーザー名', $userNameColumn['label']);

        $phoneColumn = collect($result)->firstWhere('key', 'phone_number');
        $this->assertEquals('電話番号', $phoneColumn['label']);

        $emailColumn = collect($result)->firstWhere('key', 'email_address');
        $this->assertEquals('メールアドレス', $emailColumn['label']);
    }

    public function test_filter_conditional_columns_with_empty_data()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            [
                'key' => 'notes',
                'label' => 'Notes',
                'show_condition' => ['type' => 'has_data']
            ]
        ];

        $data = [];

        $result = $this->formatter->filterConditionalColumns($columns, $data);

        $this->assertEquals($columns, $result); // Should return original columns when data is empty
    }

    public function test_add_dynamic_columns_skips_internal_keys()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text']
        ];

        $data = [
            ['name' => 'Item 1', '_internal_key' => 'internal', '_rowspan_info' => 'rowspan'],
        ];

        $result = $this->formatter->addDynamicColumns($columns, $data);

        $this->assertCount(1, $result); // Should only have the original column, internal keys skipped
    }
}