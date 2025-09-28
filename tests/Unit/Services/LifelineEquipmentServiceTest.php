<?php

namespace Tests\Unit\Services;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\LifelineEquipmentService;
use App\Services\LifelineEquipmentValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LifelineEquipmentService $service;

    protected LifelineEquipmentValidationService $validationService;

    protected ActivityLogService $activityLogService;

    protected User $user;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationService = $this->createMock(LifelineEquipmentValidationService::class);
        $this->activityLogService = $this->createMock(ActivityLogService::class);

        $this->service = new LifelineEquipmentService(
            $this->validationService,
            $this->activityLogService
        );

        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_get_equipment_data_returns_success_for_valid_category(): void
    {
        $result = $this->service->getEquipmentData($this->facility, 'electrical');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('lifeline_equipment', $result);
    }

    public function test_get_equipment_data_returns_error_for_invalid_category(): void
    {
        $result = $this->service->getEquipmentData($this->facility, 'invalid_category');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('無効なカテゴリです', $result['message']);
    }

    public function test_get_equipment_data_creates_lifeline_equipment_if_not_exists(): void
    {
        $this->assertDatabaseMissing('lifeline_equipment', [
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $result = $this->service->getEquipmentData($this->facility, 'electrical');

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('lifeline_equipment', [
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'draft',
        ]);
    }

    public function test_get_electrical_equipment_data_returns_default_structure_when_no_data(): void
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $result = $this->service->getElectricalEquipmentData($lifelineEquipment);

        $this->assertArrayHasKey('basic_info', $result);
        $this->assertArrayHasKey('pas_info', $result);
        $this->assertArrayHasKey('cubicle_info', $result);
        $this->assertArrayHasKey('generator_info', $result);
        $this->assertArrayHasKey('notes', $result);

        // Check default structure
        $this->assertEquals('', $result['basic_info']['electrical_contractor']);
        $this->assertEquals('', $result['pas_info']['availability']);
        $this->assertEquals([], $result['cubicle_info']['equipment_list']);
    }

    public function test_get_electrical_equipment_data_returns_formatted_data_when_exists(): void
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理会社',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS詳細情報',
            ],
        ]);

        $result = $this->service->getElectricalEquipmentData($lifelineEquipment);

        $this->assertEquals('東京電力', $result['basic_info']['electrical_contractor']);
        $this->assertEquals('保安管理会社', $result['basic_info']['safety_management_company']);
        $this->assertEquals('有', $result['pas_info']['availability']);
        $this->assertEquals('PAS詳細情報', $result['pas_info']['details']);
    }

    public function test_update_equipment_data_returns_success_for_valid_data(): void
    {
        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->with('electrical', ['basic_info' => ['electrical_contractor' => '東京電力']])
            ->willReturn([
                'success' => true,
                'data' => ['basic_info' => ['electrical_contractor' => '東京電力']],
            ]);

        $this->activityLogService
            ->expects($this->once())
            ->method('logFacilityUpdated');

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['basic_info' => ['electrical_contractor' => '東京電力']],
            $this->user->id
        );

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('更新しました', $result['message']);
    }

    public function test_update_equipment_data_returns_validation_errors(): void
    {
        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->with('electrical', ['invalid_data' => 'test'])
            ->willReturn([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
                'errors' => ['basic_info.electrical_contractor' => ['必須項目です']],
            ]);

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['invalid_data' => 'test'],
            $this->user->id
        );

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_update_equipment_data_creates_electrical_equipment_if_not_exists(): void
    {
        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->willReturn([
                'success' => true,
                'data' => ['basic_info' => ['electrical_contractor' => '東京電力']],
            ]);

        $this->activityLogService
            ->expects($this->once())
            ->method('logFacilityUpdated');

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['basic_info' => ['electrical_contractor' => '東京電力']],
            $this->user->id
        );

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('lifeline_equipment', [
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        $lifelineEquipment = LifelineEquipment::where([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ])->first();

        $this->assertDatabaseHas('electrical_equipment', [
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);
    }

    public function test_update_equipment_data_updates_existing_electrical_equipment(): void
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => ['electrical_contractor' => '旧契約会社'],
        ]);

        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->willReturn([
                'success' => true,
                'data' => ['basic_info' => ['electrical_contractor' => '新契約会社']],
            ]);

        $this->activityLogService
            ->expects($this->once())
            ->method('logFacilityUpdated');

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['basic_info' => ['electrical_contractor' => '新契約会社']],
            $this->user->id
        );

        $this->assertTrue($result['success']);

        $electricalEquipment->refresh();
        $this->assertEquals('新契約会社', $electricalEquipment->basic_info['electrical_contractor']);
    }

    public function test_update_equipment_data_handles_partial_updates(): void
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => ['electrical_contractor' => '既存契約会社'],
            'pas_info' => ['availability' => '有'],
        ]);

        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->willReturn([
                'success' => true,
                'data' => ['basic_info' => ['electrical_contractor' => '更新契約会社']],
            ]);

        $this->activityLogService
            ->expects($this->once())
            ->method('logFacilityUpdated');

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['basic_info' => ['electrical_contractor' => '更新契約会社']],
            $this->user->id
        );

        $this->assertTrue($result['success']);

        $electricalEquipment->refresh();
        $this->assertEquals('更新契約会社', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']); // Should remain unchanged
    }

    public function test_update_equipment_data_rolls_back_on_error(): void
    {
        // Create a service that will throw an exception during activity logging
        $mockValidationService = $this->createMock(LifelineEquipmentValidationService::class);
        $mockValidationService->method('validateCategoryData')->willReturn([
            'success' => true,
            'data' => ['basic_info' => ['electrical_contractor' => '東京電力']],
        ]);

        $mockActivityLogService = $this->createMock(ActivityLogService::class);
        $mockActivityLogService->method('logFacilityUpdated')->willThrowException(new \Exception('Test exception'));

        $service = new LifelineEquipmentService($mockValidationService, $mockActivityLogService);

        $result = $service->updateEquipmentData(
            $this->facility,
            'electrical',
            ['basic_info' => ['electrical_contractor' => '東京電力']],
            $this->user->id
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('更新に失敗しました', $result['message']);
    }

    public function test_update_equipment_data_returns_error_for_invalid_category(): void
    {
        $result = $this->service->updateEquipmentData(
            $this->facility,
            'invalid_category',
            ['data' => 'test'],
            $this->user->id
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('無効なカテゴリです', $result['message']);
    }

    public function test_update_equipment_data_handles_hvac_lighting_category(): void
    {
        // Create lifeline equipment record first
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'hvac_lighting',
            'status' => 'active',
        ]);

        $this->validationService
            ->expects($this->once())
            ->method('validateCategoryData')
            ->with('hvac_lighting', ['basic_info' => ['hvac_contractor' => 'Test Company']])
            ->willReturn([
                'success' => true,
                'data' => ['basic_info' => ['hvac_contractor' => 'Test Company']],
            ]);

        $result = $this->service->updateEquipmentData(
            $this->facility,
            'hvac_lighting',
            ['basic_info' => ['hvac_contractor' => 'Test Company']],
            $this->user->id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('ライフライン設備情報を更新しました。', $result['message']);
    }
}
