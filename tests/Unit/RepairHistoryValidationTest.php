<?php

namespace Tests\Unit;

use App\Http\Controllers\RepairHistoryController;
use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RepairHistoryValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $facility;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new RepairHistoryController();
        $this->facility = Facility::factory()->create(['status' => 'approved']);
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($this->user);
    }

    /**
     * Test validation rules for exterior category.
     */
    public function test_exterior_category_validation_rules()
    {
        $rules = $this->getValidationRules('exterior');

        // Test base rules exist
        $this->assertArrayHasKey('histories', $rules);
        $this->assertArrayHasKey('histories.*.maintenance_date', $rules);
        $this->assertArrayHasKey('histories.*.contractor', $rules);
        $this->assertArrayHasKey('histories.*.content', $rules);
        $this->assertArrayHasKey('histories.*.subcategory', $rules);

        // Test exterior-specific warranty rules
        $this->assertArrayHasKey('histories.*.warranty_period_years', $rules);
        $this->assertArrayHasKey('histories.*.warranty_start_date', $rules);
        $this->assertArrayHasKey('histories.*.warranty_end_date', $rules);

        // Test special notes rule
        $this->assertArrayHasKey('special_notes', $rules);
    }

    /**
     * Test validation rules for interior category.
     */
    public function test_interior_category_validation_rules()
    {
        $rules = $this->getValidationRules('interior');

        // Test base rules exist
        $this->assertArrayHasKey('histories', $rules);
        $this->assertArrayHasKey('histories.*.maintenance_date', $rules);
        $this->assertArrayHasKey('histories.*.contractor', $rules);
        $this->assertArrayHasKey('histories.*.content', $rules);
        $this->assertArrayHasKey('histories.*.subcategory', $rules);

        // Test that warranty rules are NOT present for interior
        $this->assertArrayNotHasKey('histories.*.warranty_period_years', $rules);
        $this->assertArrayNotHasKey('histories.*.warranty_start_date', $rules);
        $this->assertArrayNotHasKey('histories.*.warranty_end_date', $rules);

        // Test special notes rule
        $this->assertArrayHasKey('special_notes', $rules);
    }

    /**
     * Test validation rules for other category.
     */
    public function test_other_category_validation_rules()
    {
        $rules = $this->getValidationRules('other');

        // Test base rules exist
        $this->assertArrayHasKey('histories', $rules);
        $this->assertArrayHasKey('histories.*.maintenance_date', $rules);
        $this->assertArrayHasKey('histories.*.contractor', $rules);
        $this->assertArrayHasKey('histories.*.content', $rules);
        $this->assertArrayHasKey('histories.*.subcategory', $rules);

        // Test that warranty rules are NOT present for other
        $this->assertArrayNotHasKey('histories.*.warranty_period_years', $rules);
        $this->assertArrayNotHasKey('histories.*.warranty_start_date', $rules);
        $this->assertArrayNotHasKey('histories.*.warranty_end_date', $rules);

        // Test special notes rule
        $this->assertArrayHasKey('special_notes', $rules);
    }

    /**
     * Test optional field validation (no required fields).
     */
    public function test_optional_field_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with empty data - should pass since no fields are required
        $data = [
            'histories' => [
                [
                    // All fields optional
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
        
        // Test with minimal valid data
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-01',
                    'contractor' => 'テスト会社',
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test date field validation.
     */
    public function test_date_field_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with invalid date formats
        $data = [
            'histories' => [
                [
                    'maintenance_date' => 'invalid-date',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'warranty_start_date' => 'invalid-date',
                    'warranty_end_date' => 'invalid-date',
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.maintenance_date'));
        $this->assertTrue($errors->has('histories.0.warranty_start_date'));
        $this->assertTrue($errors->has('histories.0.warranty_end_date'));
    }

    /**
     * Test warranty end date after start date validation.
     */
    public function test_warranty_end_date_after_start_date_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with end date before start date
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2024-01-10', // Before start date
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.warranty_end_date'));
    }

    /**
     * Test numeric field validation.
     */
    public function test_numeric_field_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with invalid numeric values
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'cost' => 'invalid-number',
                    'warranty_period_years' => 'invalid-number',
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.cost'));
        $this->assertTrue($errors->has('histories.0.warranty_period_years'));
    }

    /**
     * Test cost minimum value validation.
     */
    public function test_cost_minimum_value_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with negative cost
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'cost' => -1000,
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.cost'));
    }

    /**
     * Test warranty period years range validation.
     */
    public function test_warranty_period_years_range_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with warranty period out of range
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'warranty_period_years' => 0, // Below minimum
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.warranty_period_years'));

        // Test with warranty period above maximum
        $data['histories'][0]['warranty_period_years'] = 51; // Above maximum

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.warranty_period_years'));
    }

    /**
     * Test string length validation.
     */
    public function test_string_length_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with strings exceeding maximum length
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => str_repeat('a', 256), // Too long
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    'contact_person' => str_repeat('b', 256), // Too long
                    'phone_number' => str_repeat('c', 21), // Too long
                    'classification' => str_repeat('d', 101), // Too long
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.contractor'));
        $this->assertTrue($errors->has('histories.0.contact_person'));
        $this->assertTrue($errors->has('histories.0.phone_number'));
        $this->assertTrue($errors->has('histories.0.classification'));
    }

    /**
     * Test subcategory validation for exterior category.
     */
    public function test_subcategory_validation_exterior()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with valid subcategories
        $validData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                ]
            ]
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test with invalid subcategory
        $invalidData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'invalid-subcategory',
                ]
            ]
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.subcategory'));
    }

    /**
     * Test subcategory validation for interior category.
     */
    public function test_subcategory_validation_interior()
    {
        $rules = $this->getValidationRules('interior');
        
        // Test with valid subcategories
        $validData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'renovation',
                ]
            ]
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test with exterior subcategory (should fail)
        $invalidData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof', // Exterior subcategory
                ]
            ]
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.subcategory'));
    }

    /**
     * Test subcategory validation for other category.
     */
    public function test_subcategory_validation_other()
    {
        $rules = $this->getValidationRules('other');
        
        // Test with valid subcategory
        $validData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'renovation_work',
                ]
            ]
        ];

        $validator = Validator::make($validData, $rules);
        $this->assertFalse($validator->fails());

        // Test with invalid subcategory
        $invalidData = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof', // Exterior subcategory
                ]
            ]
        ];

        $validator = Validator::make($invalidData, $rules);
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertTrue($errors->has('histories.0.subcategory'));
    }

    /**
     * Test optional field validation.
     */
    public function test_optional_field_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with some optional fields filled
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                    // All fields are now optional
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
        
        // Test with completely empty data - should also pass
        $data = [
            'histories' => [
                [
                    // All fields empty - should pass since all are optional
                ]
            ]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test special notes validation.
     */
    public function test_special_notes_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with valid special notes
        $data = [
            'histories' => [
                [
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '業者名',
                    'content' => '工事内容',
                    'subcategory' => 'waterproof',
                ]
            ],
            'special_notes' => '特記事項のテスト',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());

        // Test with null special notes
        $data['special_notes'] = null;

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test validation messages are in Japanese.
     */
    public function test_validation_messages_are_in_japanese()
    {
        $messages = $this->getValidationMessages();

        // Test that messages exist and are in Japanese for non-required fields
        $this->assertArrayHasKey('histories.*.maintenance_date.date', $messages);
        $this->assertEquals('施工日は有効な日付形式で入力してください。', $messages['histories.*.maintenance_date.date']);

        $this->assertArrayHasKey('histories.*.contractor.max', $messages);
        $this->assertEquals('会社名は255文字以内で入力してください。', $messages['histories.*.contractor.max']);

        $this->assertArrayHasKey('histories.*.content.max', $messages);
        $this->assertEquals('修繕内容は500文字以内で入力してください。', $messages['histories.*.content.max']);

        $this->assertArrayHasKey('histories.*.subcategory.max', $messages);
        $this->assertEquals('種別は50文字以内で入力してください。', $messages['histories.*.subcategory.max']);
    }

    /**
     * Test complete valid data passes validation.
     */
    public function test_complete_valid_data_passes_validation()
    {
        $rules = $this->getValidationRules('exterior');
        
        // Test with complete valid data
        $data = [
            'histories' => [
                [
                    'id' => null,
                    'maintenance_date' => '2024-01-15',
                    'contractor' => '山田防水工事',
                    'content' => '屋上防水工事',
                    'cost' => 500000,
                    'subcategory' => 'waterproof',
                    'contact_person' => '山田太郎',
                    'phone_number' => '03-1234-5678',
                    'classification' => '定期点検',
                    'notes' => '特記事項なし',
                    'warranty_period_years' => 10,
                    'warranty_start_date' => '2024-01-15',
                    'warranty_end_date' => '2034-01-15',
                ]
            ],
            'special_notes' => '外装工事の特記事項',
        ];

        $validator = Validator::make($data, $rules, $this->getValidationMessages());
        $this->assertFalse($validator->fails());
    }

    /**
     * Helper method to get validation rules using reflection.
     */
    private function getValidationRules(string $category): array
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getValidationRules');
        $method->setAccessible(true);
        
        return $method->invoke($this->controller, $category);
    }

    /**
     * Helper method to get validation messages using reflection.
     */
    private function getValidationMessages(): array
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getValidationMessages');
        $method->setAccessible(true);
        
        return $method->invoke($this->controller);
    }
}