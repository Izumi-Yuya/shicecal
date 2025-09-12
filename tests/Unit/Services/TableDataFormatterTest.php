<?php

namespace Tests\Unit\Services;

use App\Services\TableDataFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class TableDataFormatterTest extends TestCase
{
    protected TableDataFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TableDataFormatter();
    }

    public function test_format_table_data_with_empty_data()
    {
        $data = [];
        $config = ['columns' => []];

        $result = $this->formatter->formatTableData($data, $config);

        $this->assertEmpty($result);
    }

    public function test_format_table_data_with_single_row()
    {
        $data = ['name' => 'Test Name', 'email' => 'test@example.com'];
        $config = [
            'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email']
            ]
        ];

        $result = $this->formatter->formatTableData($data, $config);

        $this->assertEquals('Test Name', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function test_format_table_data_with_multiple_rows()
    {
        $data = [
            ['name' => 'User 1', 'age' => 25],
            ['name' => 'User 2', 'age' => 30]
        ];
        $config = [
            'columns' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['key' => 'age', 'label' => 'Age', 'type' => 'number']
            ]
        ];

        $result = $this->formatter->formatTableData($data, $config);

        $this->assertCount(2, $result);
        $this->assertEquals('User 1', $result[0]['name']);
        $this->assertEquals('25', $result[0]['age']);
        $this->assertEquals('User 2', $result[1]['name']);
        $this->assertEquals('30', $result[1]['age']);
    }

    public function test_format_value_text_type()
    {
        $column = ['type' => 'text'];
        
        $result = $this->formatter->formatValue('Hello World', $column);
        
        $this->assertEquals('Hello World', $result);
    }

    public function test_format_value_text_with_transform()
    {
        $column = ['type' => 'text', 'transform' => 'uppercase'];
        
        $result = $this->formatter->formatValue('hello world', $column);
        
        $this->assertEquals('HELLO WORLD', $result);
    }

    public function test_format_value_text_with_prefix_suffix()
    {
        $column = ['type' => 'text', 'prefix' => '【', 'suffix' => '】'];
        
        $result = $this->formatter->formatValue('test', $column);
        
        $this->assertEquals('【test】', $result);
    }

    public function test_format_value_email_type()
    {
        $column = ['type' => 'email'];
        
        $result = $this->formatter->formatValue('test@example.com', $column);
        
        $this->assertEquals('test@example.com', $result);
    }

    public function test_format_value_url_type()
    {
        $column = ['type' => 'url'];
        
        $result = $this->formatter->formatValue('example.com', $column);
        
        $this->assertEquals('https://example.com', $result);
    }

    public function test_format_value_phone_type_japanese()
    {
        $column = ['type' => 'phone', 'format' => 'japanese'];
        
        $result = $this->formatter->formatValue('0312345678', $column);
        
        $this->assertEquals('03-1234-5678', $result);
    }

    public function test_format_value_date_type()
    {
        $column = ['type' => 'date', 'format' => 'Y年m月d日'];
        $date = Carbon::parse('2023-04-15');
        
        $result = $this->formatter->formatValue($date, $column);
        
        $this->assertEquals('2023年04月15日', $result);
    }

    public function test_format_value_date_range_type_with_array()
    {
        $column = ['type' => 'date_range', 'format' => 'Y年m月d日', 'separator' => '〜'];
        $dates = [Carbon::parse('2023-04-01'), Carbon::parse('2023-04-30')];
        
        $result = $this->formatter->formatValue($dates, $column);
        
        $this->assertEquals('2023年04月01日〜2023年04月30日', $result);
    }

    public function test_format_value_date_range_type_with_string()
    {
        $column = ['type' => 'date_range', 'format' => 'Y年m月d日', 'separator' => '〜'];
        
        $result = $this->formatter->formatValue('2023-04-01〜2023-04-30', $column);
        
        $this->assertEquals('2023年04月01日〜2023年04月30日', $result);
    }

    public function test_format_value_number_type()
    {
        $column = ['type' => 'number', 'decimals' => 2, 'thousands_separator' => ','];
        
        $result = $this->formatter->formatValue(1234.56, $column);
        
        $this->assertEquals('1,234.56', $result);
    }

    public function test_format_value_number_with_unit()
    {
        $column = ['type' => 'number', 'unit' => '㎡'];
        
        $result = $this->formatter->formatValue(100, $column);
        
        $this->assertEquals('100㎡', $result);
    }

    public function test_format_value_select_type()
    {
        $column = [
            'type' => 'select',
            'options' => [
                'active' => 'アクティブ',
                'inactive' => '非アクティブ'
            ]
        ];
        
        $result = $this->formatter->formatValue('active', $column);
        
        $this->assertEquals('アクティブ', $result);
    }

    public function test_format_value_select_type_unknown_value()
    {
        $column = [
            'type' => 'select',
            'options' => [
                'active' => 'アクティブ'
            ]
        ];
        
        $result = $this->formatter->formatValue('unknown', $column);
        
        $this->assertEquals('unknown', $result);
    }

    public function test_format_value_static_value()
    {
        $column = ['type' => 'text', 'static_value' => '固定値'];
        
        $result = $this->formatter->formatValue('ignored', $column);
        
        $this->assertEquals('固定値', $result);
    }

    public function test_format_value_null_returns_null()
    {
        $column = ['type' => 'text'];
        
        $result = $this->formatter->formatValue(null, $column);
        
        $this->assertNull($result);
    }

    public function test_format_empty_value_default()
    {
        $result = $this->formatter->formatEmptyValue('test_field');
        
        $this->assertEquals('未設定', $result);
    }

    public function test_format_empty_value_custom()
    {
        $column = ['empty_text' => 'カスタム未設定'];
        
        $result = $this->formatter->formatEmptyValue('test_field', $column);
        
        $this->assertEquals('カスタム未設定', $result);
    }

    public function test_group_data_by_field()
    {
        $data = [
            ['type' => 'A', 'name' => 'Item 1'],
            ['type' => 'B', 'name' => 'Item 2'],
            ['type' => 'A', 'name' => 'Item 3']
        ];
        
        $result = $this->formatter->groupDataBy($data, 'type');
        
        $this->assertArrayHasKey('A', $result);
        $this->assertArrayHasKey('B', $result);
        $this->assertCount(2, $result['A']);
        $this->assertCount(1, $result['B']);
    }

    public function test_group_data_by_field_with_null_values()
    {
        $data = [
            ['type' => 'A', 'name' => 'Item 1'],
            ['type' => null, 'name' => 'Item 2'],
            ['name' => 'Item 3'] // Missing type field
        ];
        
        $result = $this->formatter->groupDataBy($data, 'type');
        
        $this->assertArrayHasKey('A', $result);
        $this->assertArrayHasKey('その他', $result);
        $this->assertCount(1, $result['A']);
        $this->assertCount(2, $result['その他']);
    }

    public function test_filter_conditional_columns_show_condition_has_data()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'optional', 'label' => 'Optional', 'show_condition' => ['type' => 'has_data']]
        ];
        $data = [
            ['name' => 'Test', 'optional' => 'Value']
        ];
        
        $result = $this->formatter->filterConditionalColumns($columns, $data);
        
        $this->assertCount(2, $result);
    }

    public function test_filter_conditional_columns_hide_empty_data()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'optional', 'label' => 'Optional', 'show_condition' => ['type' => 'has_data']]
        ];
        $data = [
            ['name' => 'Test', 'optional' => '']
        ];
        
        $result = $this->formatter->filterConditionalColumns($columns, $data);
        
        $this->assertCount(1, $result);
        $this->assertEquals('name', $result[0]['key']);
    }

    public function test_add_dynamic_columns()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text']
        ];
        $data = [
            ['name' => 'Test', 'email' => 'test@example.com', 'age' => 25]
        ];
        
        $result = $this->formatter->addDynamicColumns($columns, $data);
        
        $this->assertCount(3, $result);
        
        // Find the email column
        $emailColumn = collect($result)->firstWhere('key', 'email');
        $this->assertNotNull($emailColumn);
        $this->assertEquals('email', $emailColumn['type']);
        
        // Find the age column
        $ageColumn = collect($result)->firstWhere('key', 'age');
        $this->assertNotNull($ageColumn);
        $this->assertEquals('number', $ageColumn['type']);
    }

    public function test_infer_column_type_email()
    {
        $data = [
            ['email_address' => 'test1@example.com'],
            ['email_address' => 'test2@example.com']
        ];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $emailColumn = collect($result)->firstWhere('key', 'email_address');
        $this->assertEquals('email', $emailColumn['type']);
    }

    public function test_infer_column_type_url()
    {
        $data = [
            ['website' => 'https://example.com'],
            ['website' => 'https://test.com']
        ];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $urlColumn = collect($result)->firstWhere('key', 'website');
        $this->assertEquals('url', $urlColumn['type']);
    }

    public function test_infer_column_type_phone()
    {
        $data = [
            ['phone_number' => '03-1234-5678'],
            ['phone_number' => '090-1234-5678']
        ];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $phoneColumn = collect($result)->firstWhere('key', 'phone_number');
        $this->assertEquals('phone', $phoneColumn['type']);
    }

    public function test_infer_column_type_date()
    {
        $data = [
            ['created_at' => '2023-04-15 10:30:00'],
            ['created_at' => '2023-04-16 14:20:00']
        ];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $dateColumn = collect($result)->firstWhere('key', 'created_at');
        $this->assertEquals('date', $dateColumn['type']);
    }

    public function test_infer_column_type_number()
    {
        $data = [
            ['count' => 100],
            ['count' => 200]
        ];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $numberColumn = collect($result)->firstWhere('key', 'count');
        $this->assertEquals('number', $numberColumn['type']);
    }

    public function test_generate_column_label_from_snake_case()
    {
        $data = [['user_name' => 'test']];
        
        $result = $this->formatter->addDynamicColumns([], $data);
        
        $column = collect($result)->firstWhere('key', 'user_name');
        $this->assertEquals('ユーザー名', $column['label']);
    }

    public function test_format_value_handles_invalid_date_gracefully()
    {
        $column = ['type' => 'date', 'format' => 'Y年m月d日'];
        
        $result = $this->formatter->formatValue('invalid-date', $column);
        
        $this->assertEquals('invalid-date', $result);
    }

    public function test_format_value_handles_invalid_date_range_gracefully()
    {
        $column = ['type' => 'date_range', 'format' => 'Y年m月d日', 'separator' => '〜'];
        
        $result = $this->formatter->formatValue('invalid〜dates', $column);
        
        $this->assertEquals('invalid〜dates', $result);
    }
}