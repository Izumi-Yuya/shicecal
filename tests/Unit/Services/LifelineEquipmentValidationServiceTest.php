<?php

namespace Tests\Unit\Services;

use App\Services\LifelineEquipmentValidationService;
use Tests\TestCase;

class LifelineEquipmentValidationServiceTest extends TestCase
{
    protected LifelineEquipmentValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LifelineEquipmentValidationService();
    }

    public function test_validate_category_data_returns_success_for_valid_electrical_data(): void
    {
        $data = [
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
                'maintenance_inspection_date' => '2024-01-15',
                'inspection_report_pdf' => 'report.pdf',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS詳細情報',
                'update_date' => '2024-01-15',
            ],
            'notes' => '備考情報',
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_category_data_returns_errors_for_invalid_electrical_data(): void
    {
        $data = [
            'basic_info' => [
                'electrical_contractor' => str_repeat('a', 300), // Too long
                'maintenance_inspection_date' => '2025-12-31', // Future date
            ],
            'pas_info' => [
                'availability' => '無効', // Invalid value
                'details' => str_repeat('a', 1100), // Too long
            ],
            'notes' => str_repeat('a', 2100), // Too long
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('basic_info.electrical_contractor', $result['errors']);
        $this->assertArrayHasKey('basic_info.maintenance_inspection_date', $result['errors']);
        $this->assertArrayHasKey('pas_info.availability', $result['errors']);
        $this->assertArrayHasKey('pas_info.details', $result['errors']);
        $this->assertArrayHasKey('notes', $result['errors']);
    }

    public function test_validate_category_data_validates_cubicle_equipment_list(): void
    {
        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '001',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2024',
                        'update_date' => '2024-01-15',
                    ],
                    [
                        'equipment_number' => str_repeat('a', 60), // Too long
                        'manufacturer' => str_repeat('a', 300), // Too long
                        'model_year' => 'invalid', // Invalid format
                        'update_date' => '2025-12-31', // Future date
                    ],
                ],
            ],
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('cubicle_info.equipment_list.1.equipment_number', $result['errors']);
        $this->assertArrayHasKey('cubicle_info.equipment_list.1.manufacturer', $result['errors']);
        $this->assertArrayHasKey('cubicle_info.equipment_list.1.model_year', $result['errors']);
        $this->assertArrayHasKey('cubicle_info.equipment_list.1.update_date', $result['errors']);
    }

    public function test_validate_category_data_validates_generator_equipment_list(): void
    {
        $data = [
            'generator_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => '001',
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2023',
                        'update_date' => '2024-01-15',
                    ],
                    [
                        'equipment_number' => str_repeat('a', 60), // Too long
                        'manufacturer' => str_repeat('a', 300), // Too long
                        'model_year' => '23', // Invalid format
                        'update_date' => 'invalid-date', // Invalid date
                    ],
                ],
            ],
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('generator_info.equipment_list.1.equipment_number', $result['errors']);
        $this->assertArrayHasKey('generator_info.equipment_list.1.manufacturer', $result['errors']);
        $this->assertArrayHasKey('generator_info.equipment_list.1.model_year', $result['errors']);
        $this->assertArrayHasKey('generator_info.equipment_list.1.update_date', $result['errors']);
    }

    public function test_validate_category_data_limits_equipment_list_size(): void
    {
        $equipmentList = [];
        for ($i = 0; $i < 25; $i++) {
            $equipmentList[] = [
                'equipment_number' => sprintf('%03d', $i),
                'manufacturer' => 'メーカー' . $i,
                'model_year' => '2024',
                'update_date' => '2024-01-15',
            ];
        }

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => $equipmentList,
            ],
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('cubicle_info.equipment_list', $result['errors']);
    }

    public function test_validate_category_data_accepts_null_values(): void
    {
        $data = [
            'basic_info' => [
                'electrical_contractor' => null,
                'safety_management_company' => null,
                'maintenance_inspection_date' => null,
                'inspection_report_pdf' => null,
            ],
            'pas_info' => [
                'availability' => null,
                'details' => null,
                'update_date' => null,
            ],
            'cubicle_info' => [
                'availability' => null,
                'details' => null,
                'equipment_list' => null,
            ],
            'generator_info' => [
                'availability' => null,
                'availability_details' => null,
                'equipment_list' => null,
            ],
            'notes' => null,
        ];

        $result = $this->service->validateCategoryData('electrical', $data);

        $this->assertTrue($result['success']);
    }

    public function test_validate_category_data_returns_error_for_invalid_category(): void
    {
        $data = ['test' => 'data'];

        $result = $this->service->validateCategoryData('invalid_category', $data);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('無効なカテゴリです', $result['message']);
    }

    public function test_validate_category_data_returns_error_for_under_development_categories(): void
    {
        // Test elevator category which requires status field
        $data = ['status' => 'under_development'];
        $result = $this->service->validateCategoryData('elevator', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals(['status' => 'under_development'], $result['data']);

        // Test other categories with basic data
        $categories = ['water', 'gas', 'hvac_lighting'];

        foreach ($categories as $category) {
            $data = ['notes' => 'テスト備考'];
            $result = $this->service->validateCategoryData($category, $data);

            $this->assertTrue($result['success']);
            $this->assertEquals(['notes' => 'テスト備考'], $result['data']);
        }
    }

    public function test_get_validation_rules_returns_correct_rules_for_electrical(): void
    {
        $rules = $this->service->getValidationRules('electrical');

        $this->assertArrayHasKey('basic_info', $rules);
        $this->assertArrayHasKey('basic_info.electrical_contractor', $rules);
        $this->assertArrayHasKey('pas_info', $rules);
        $this->assertArrayHasKey('pas_info.availability', $rules);
        $this->assertArrayHasKey('cubicle_info', $rules);
        $this->assertArrayHasKey('generator_info', $rules);
        $this->assertArrayHasKey('notes', $rules);
    }

    public function test_get_validation_rules_throws_exception_for_invalid_category(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無効なカテゴリです: invalid_category');

        $this->service->getValidationRules('invalid_category');
    }

    public function test_get_validation_messages_returns_correct_messages_for_electrical(): void
    {
        $messages = $this->service->getValidationMessages('electrical');

        $this->assertArrayHasKey('basic_info.electrical_contractor.max', $messages);
        $this->assertArrayHasKey('pas_info.availability.in', $messages);
        $this->assertArrayHasKey('cubicle_info.equipment_list.*.model_year.regex', $messages);
        $this->assertArrayHasKey('generator_info.equipment_list.*.model_year.regex', $messages);
        $this->assertArrayHasKey('notes.max', $messages);
    }

    public function test_validate_electrical_card_data_validates_basic_info_card(): void
    {
        $data = [
            'electrical_contractor' => '東京電力',
            'safety_management_company' => '保安管理会社',
            'maintenance_inspection_date' => '2024-01-15',
            'inspection_report_pdf' => 'report.pdf',
        ];

        $result = $this->service->validateElectricalCardData('basic_info', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_electrical_card_data_validates_pas_info_card(): void
    {
        $data = [
            'availability' => '有',
            'details' => 'PAS詳細情報',
            'update_date' => '2024-01-15',
        ];

        $result = $this->service->validateElectricalCardData('pas_info', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_electrical_card_data_validates_cubicle_info_card(): void
    {
        $data = [
            'availability' => '有',
            'details' => 'キュービクル詳細',
            'equipment_list' => [
                [
                    'equipment_number' => '001',
                    'manufacturer' => '三菱電機',
                    'model_year' => '2024',
                    'update_date' => '2024-01-15',
                ],
            ],
        ];

        $result = $this->service->validateElectricalCardData('cubicle_info', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_electrical_card_data_validates_generator_info_card(): void
    {
        $data = [
            'availability' => '有',
            'availability_details' => '発電機詳細',
            'equipment_list' => [
                [
                    'equipment_number' => '001',
                    'manufacturer' => 'ヤンマー',
                    'model_year' => '2023',
                    'update_date' => '2024-01-15',
                ],
            ],
        ];

        $result = $this->service->validateElectricalCardData('generator_info', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_electrical_card_data_validates_notes_card(): void
    {
        $data = [
            'notes' => '備考情報です。',
        ];

        $result = $this->service->validateElectricalCardData('notes', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_validate_electrical_card_data_returns_error_for_invalid_card_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('無効なカードタイプです: invalid_card');

        $this->service->validateElectricalCardData('invalid_card', []);
    }

    public function test_validate_electrical_card_data_returns_validation_errors(): void
    {
        $data = [
            'electrical_contractor' => str_repeat('a', 300), // Too long
            'maintenance_inspection_date' => '2025-12-31', // Future date
        ];

        $result = $this->service->validateElectricalCardData('basic_info', $data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('electrical_contractor', $result['errors']);
        $this->assertArrayHasKey('maintenance_inspection_date', $result['errors']);
    }
}