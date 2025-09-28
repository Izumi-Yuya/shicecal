<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentCubicleCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);

        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function it_can_save_cubicle_availability_as_none()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '無',
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ライフライン設備情報を更新しました。',
            ]);

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $this->assertNotNull($lifelineEquipment);

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $this->assertNotNull($electricalEquipment);
        $this->assertEquals('無', $electricalEquipment->cubicle_info['availability']);
    }

    /** @test */
    public function it_can_save_cubicle_availability_as_available_with_details()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => 'キュービクル設備の詳細情報です。',
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ライフライン設備情報を更新しました。',
            ]);

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $this->assertEquals('有', $electricalEquipment->cubicle_info['availability']);
        $this->assertEquals('キュービクル設備の詳細情報です。', $electricalEquipment->cubicle_info['details']);
    }

    /** @test */
    public function it_can_save_cubicle_with_single_equipment_item()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => 'キュービクル設備の詳細情報です。',
                'equipment_list' => [
                    [
                        'equipment_number' => 'CB-001',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2020',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200);

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $cubicleInfo = $electricalEquipment->cubicle_info;

        $this->assertEquals('有', $cubicleInfo['availability']);
        $this->assertEquals('キュービクル設備の詳細情報です。', $cubicleInfo['details']);
        $this->assertCount(1, $cubicleInfo['equipment_list']);

        $equipment = $cubicleInfo['equipment_list'][0];
        $this->assertEquals('CB-001', $equipment['equipment_number']);
        $this->assertEquals('三菱電機', $equipment['manufacturer']);
        $this->assertEquals('2020', $equipment['model_year']);
        $this->assertEquals('2024-03-15', $equipment['update_date']);
    }

    /** @test */
    public function it_can_save_cubicle_with_multiple_equipment_items()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => 'キュービクル設備の詳細情報です。',
                'equipment_list' => [
                    [
                        'equipment_number' => 'CB-001',
                        'manufacturer' => '三菱電機',
                        'model_year' => '2020',
                        'update_date' => '2024-03-15',
                    ],
                    [
                        'equipment_number' => 'CB-002',
                        'manufacturer' => '東芝',
                        'model_year' => '2019',
                        'update_date' => '2024-03-15',
                    ],
                    [
                        'equipment_number' => 'CB-003',
                        'manufacturer' => 'パナソニック',
                        'model_year' => '2021',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200);

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $cubicleInfo = $electricalEquipment->cubicle_info;

        $this->assertEquals('有', $cubicleInfo['availability']);
        $this->assertCount(3, $cubicleInfo['equipment_list']);

        // Verify first equipment
        $equipment1 = $cubicleInfo['equipment_list'][0];
        $this->assertEquals('CB-001', $equipment1['equipment_number']);
        $this->assertEquals('三菱電機', $equipment1['manufacturer']);

        // Verify second equipment
        $equipment2 = $cubicleInfo['equipment_list'][1];
        $this->assertEquals('CB-002', $equipment2['equipment_number']);
        $this->assertEquals('東芝', $equipment2['manufacturer']);

        // Verify third equipment
        $equipment3 = $cubicleInfo['equipment_list'][2];
        $this->assertEquals('CB-003', $equipment3['equipment_number']);
        $this->assertEquals('パナソニック', $equipment3['manufacturer']);
    }

    /** @test */
    public function it_can_update_existing_cubicle_equipment_list()
    {
        $this->actingAs($this->user);

        // Create initial data
        $lifelineEquipment = LifelineEquipment::create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        ElectricalEquipment::create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'cubicle_info' => [
                'availability' => '有',
                'details' => '初期の詳細情報',
                'equipment_list' => [
                    [
                        'equipment_number' => 'OLD-001',
                        'manufacturer' => '古いメーカー',
                        'model_year' => '2018',
                        'update_date' => '2023-01-01',
                    ],
                ],
            ],
        ]);

        // Update with new data
        $updateData = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => '更新された詳細情報',
                'equipment_list' => [
                    [
                        'equipment_number' => 'NEW-001',
                        'manufacturer' => '新しいメーカー',
                        'model_year' => '2023',
                        'update_date' => '2024-03-15',
                    ],
                    [
                        'equipment_number' => 'NEW-002',
                        'manufacturer' => '別の新しいメーカー',
                        'model_year' => '2024',
                        'update_date' => '2024-03-15',
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $updateData
        );

        $response->assertStatus(200);

        // Verify updated data
        $lifelineEquipment->refresh();
        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $cubicleInfo = $electricalEquipment->cubicle_info;

        $this->assertEquals('更新された詳細情報', $cubicleInfo['details']);
        $this->assertCount(2, $cubicleInfo['equipment_list']);

        // Verify old data is replaced
        $this->assertEquals('NEW-001', $cubicleInfo['equipment_list'][0]['equipment_number']);
        $this->assertEquals('NEW-002', $cubicleInfo['equipment_list'][1]['equipment_number']);
    }

    /** @test */
    public function it_validates_cubicle_availability_values()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '無効な値',
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cubicle_info.availability']);
    }

    /** @test */
    public function it_validates_cubicle_details_max_length()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => str_repeat('あ', 1001), // 1001 characters (over limit)
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cubicle_info.details']);
    }

    /** @test */
    public function it_validates_equipment_field_max_lengths()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => str_repeat('A', 51), // Over 50 char limit
                        'manufacturer' => str_repeat('B', 256), // Over 255 char limit
                        'model_year' => str_repeat('C', 11), // Over 10 char limit
                        'update_date' => 'invalid-date',
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'cubicle_info.equipment_list.0.equipment_number',
                'cubicle_info.equipment_list.0.manufacturer',
                'cubicle_info.equipment_list.0.model_year',
                'cubicle_info.equipment_list.0.update_date',
            ]);
    }

    /** @test */
    public function it_can_save_empty_equipment_list_when_availability_is_available()
    {
        $this->actingAs($this->user);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
                'details' => 'キュービクルはあるが設備情報は未登録',
                'equipment_list' => [],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200);

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $cubicleInfo = $electricalEquipment->cubicle_info;

        $this->assertEquals('有', $cubicleInfo['availability']);
        $this->assertEquals('キュービクルはあるが設備情報は未登録', $cubicleInfo['details']);
        $this->assertEmpty($cubicleInfo['equipment_list']);
    }

    /** @test */
    public function unauthorized_user_cannot_update_cubicle_info()
    {
        $unauthorizedUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($unauthorizedUser);

        $data = [
            'cubicle_info' => [
                'availability' => '有',
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設のライフライン設備情報を編集する権限がありません。',
            ]);
    }
}
