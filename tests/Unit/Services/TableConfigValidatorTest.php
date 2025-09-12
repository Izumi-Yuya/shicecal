<?php

namespace Tests\Unit\Services;

use App\Services\TableConfigValidator;
use App\Services\ValidationResult;
use Tests\TestCase;

class TableConfigValidatorTest extends TestCase
{
    private TableConfigValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TableConfigValidator();
    }

    public function test_validates_valid_basic_configuration()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function test_fails_validation_when_columns_missing()
    {
        $config = [];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('Configuration must have a "columns" section', $result->getErrors());
    }

    public function test_fails_validation_when_columns_empty()
    {
        $config = [
            'columns' => []
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('Configuration must have at least one column defined', $result->getErrors());
    }

    public function test_fails_validation_when_columns_not_array()
    {
        $config = [
            'columns' => 'invalid'
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('Configuration "columns" must be an array', $result->getErrors());
    }

    public function test_validates_required_column_fields()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前'
                    // Missing 'type' field
                ]
            ]
        ];

        $result = $this->validator->validateRequiredFields($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('Column at index 0 missing required field: type', $result->getErrors());
    }

    public function test_validates_column_structure_with_invalid_type()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'invalid_type'
                ]
            ]
        ];

        $result = $this->validator->validateColumnStructure($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Invalid column type \'invalid_type\' at index 0', $result->getErrors()[0]);
    }

    public function test_validates_select_column_requires_options()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'status',
                    'label' => 'ステータス',
                    'type' => 'select'
                    // Missing 'options' field
                ]
            ]
        ];

        $result = $this->validator->validateColumnStructure($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('Select column at index 0 must have a non-empty options array', $result->getErrors());
    }

    public function test_validates_select_column_with_valid_options()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'status',
                    'label' => 'ステータス',
                    'type' => 'select',
                    'options' => [
                        'active' => 'アクティブ',
                        'inactive' => '非アクティブ'
                    ]
                ]
            ]
        ];

        $result = $this->validator->validateColumnStructure($config);

        $this->assertTrue($result->isValid());
    }

    public function test_validates_width_format()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text',
                    'width' => 'invalid_width'
                ]
            ]
        ];

        $result = $this->validator->validateColumnStructure($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Invalid width format \'invalid_width\' at column index 0', $result->getErrors()[0]);
    }

    public function test_validates_valid_width_formats()
    {
        $validWidths = ['25%', '200px', 'auto', '33.33%'];

        foreach ($validWidths as $width) {
            $config = [
                'columns' => [
                    [
                        'key' => 'name',
                        'label' => '名前',
                        'type' => 'text',
                        'width' => $width
                    ]
                ]
            ];

            $result = $this->validator->validateColumnStructure($config);
            $this->assertTrue($result->isValid(), "Width format '{$width}' should be valid");
        }
    }

    public function test_validates_responsive_settings()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text'
                ]
            ],
            'layout' => [
                'responsive_breakpoint' => 'invalid_breakpoint'
            ]
        ];

        $result = $this->validator->validateResponsiveSettings($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Invalid responsive breakpoint \'invalid_breakpoint\'', $result->getErrors()[0]);
    }

    public function test_validates_valid_responsive_breakpoints()
    {
        $validBreakpoints = ['xs', 'sm', 'md', 'lg', 'xl'];

        foreach ($validBreakpoints as $breakpoint) {
            $config = [
                'columns' => [
                    [
                        'key' => 'name',
                        'label' => '名前',
                        'type' => 'text'
                    ]
                ],
                'layout' => [
                    'responsive_breakpoint' => $breakpoint
                ]
            ];

            $result = $this->validator->validateResponsiveSettings($config);
            $this->assertTrue($result->isValid(), "Breakpoint '{$breakpoint}' should be valid");
        }
    }

    public function test_validates_columns_per_row()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text'
                ]
            ],
            'layout' => [
                'columns_per_row' => 5 // Invalid: should be 1-4
            ]
        ];

        $result = $this->validator->validateResponsiveSettings($config);

        $this->assertFalse($result->isValid());
        $this->assertContains('columns_per_row must be an integer between 1 and 4', $result->getErrors());
    }

    public function test_validates_duplicate_column_keys()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text'
                ],
                [
                    'key' => 'name', // Duplicate key
                    'label' => '別の名前',
                    'type' => 'text'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Duplicate column key \'name\' found at index 1', implode(' ', $result->getErrors()));
    }

    public function test_validates_number_column_decimals()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'price',
                    'label' => '価格',
                    'type' => 'number',
                    'decimals' => -1 // Invalid: should be non-negative
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Number column decimals at index 0 must be a non-negative integer', implode(' ', $result->getErrors()));
    }

    public function test_validates_layout_type()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text'
                ]
            ],
            'layout' => [
                'type' => 'invalid_layout_type'
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Invalid layout type \'invalid_layout_type\'', implode(' ', $result->getErrors()));
    }

    public function test_validates_boolean_fields()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前',
                    'type' => 'text',
                    'required' => 'yes' // Should be boolean
                ]
            ],
            'layout' => [
                'show_headers' => 'true' // Should be boolean
            ],
            'features' => [
                'comments' => 1 // Should be boolean
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result->isValid());
        $errors = implode(' ', $result->getErrors());
        $this->assertStringContainsString('Column required at index 0 must be a boolean value', $errors);
        $this->assertStringContainsString('Layout show_headers must be a boolean value', $errors);
        $this->assertStringContainsString('Feature comments must be a boolean value', $errors);
    }

    public function test_creates_detailed_error_messages()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'name',
                    'label' => '名前'
                    // Missing 'type' field
                ]
            ]
        ];

        $result = $this->validator->validate($config);
        $detailedErrors = $this->validator->createDetailedErrorMessages($config, $result);

        $this->assertNotEmpty($detailedErrors);
        $this->assertArrayHasKey('message', $detailedErrors[0]);
        $this->assertArrayHasKey('severity', $detailedErrors[0]);
        $this->assertArrayHasKey('suggestion', $detailedErrors[0]);
        $this->assertArrayHasKey('context', $detailedErrors[0]);
        
        $this->assertEquals('critical', $detailedErrors[0]['severity']);
        $this->assertStringContainsString('Add a "type" field', $detailedErrors[0]['suggestion']);
    }

    public function test_validates_complex_configuration()
    {
        $config = [
            'columns' => [
                [
                    'key' => 'company_name',
                    'label' => '会社名',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false
                ],
                [
                    'key' => 'status',
                    'label' => 'ステータス',
                    'type' => 'select',
                    'options' => [
                        'active' => 'アクティブ',
                        'inactive' => '非アクティブ'
                    ]
                ],
                [
                    'key' => 'price',
                    'label' => '価格',
                    'type' => 'number',
                    'decimals' => 2,
                    'unit' => '円'
                ]
            ],
            'layout' => [
                'type' => 'key_value_pairs',
                'columns_per_row' => 2,
                'responsive_breakpoint' => 'lg',
                'show_headers' => true
            ],
            'styling' => [
                'table_class' => 'table table-bordered',
                'header_class' => 'bg-primary text-white'
            ],
            'features' => [
                'comments' => true,
                'sorting' => false,
                'filtering' => true
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result->isValid(), 'Complex valid configuration should pass validation. Errors: ' . implode(', ', $result->getErrors()));
    }

    public function test_handles_validation_exceptions_gracefully()
    {
        // Create a malformed config that might cause exceptions
        $config = [
            'columns' => [
                [
                    'key' => null, // This might cause issues in validation
                    'label' => '名前',
                    'type' => 'text'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        // Should not throw exception, should return validation result
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isValid());
    }
}