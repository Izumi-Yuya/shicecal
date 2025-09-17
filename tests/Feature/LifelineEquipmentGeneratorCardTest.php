<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentGeneratorCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
        ]);

        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
    }

    /** @test */
    public function it_can_save_generator_availability_as_none()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
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

        $generatorInfo = $electricalEquipment->generator_info;
        $this->assertEquals('無', $generatorInfo['availability']);
    }

    /** @test */
    public function it_can_save_generator_availability_as_available_with_details()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
                'availability' => '有',
                'availability_details' => '非常用発電機は地下1階に設置されており、停電時に自動で起動します。',
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
        $generatorInfo = $electricalEquipment->generator_info;

        $this->assertEquals('有', $generatorInfo['availability']);
        $this->assertEquals('非常用発電機は地下1階に設置されており、停電時に自動で起動します。', $generatorInfo['availability_details']);
    }

    /** @test */
    public function it_can_save_generator_equipment_list()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
                'availability' => '有',
                'availability_details' => '非常用発電機設備',
                'equipment_list' => [
                    [
                        'equipment_number' => 'GEN-001',
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2021',
                        'update_date' => '2024-03-15',
                    ],
                    [
                        'equipment_number' => 'GEN-002',
                        'manufacturer' => 'デンヨー',
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
        $generatorInfo = $electricalEquipment->generator_info;

        $this->assertEquals('有', $generatorInfo['availability']);
        $this->assertEquals('非常用発電機設備', $generatorInfo['availability_details']);
        $this->assertCount(2, $generatorInfo['equipment_list']);

        // Check first equipment
        $firstEquipment = $generatorInfo['equipment_list'][0];
        $this->assertEquals('GEN-001', $firstEquipment['equipment_number']);
        $this->assertEquals('ヤンマー', $firstEquipment['manufacturer']);
        $this->assertEquals('2021', $firstEquipment['model_year']);
        $this->assertEquals('2024-03-15', $firstEquipment['update_date']);

        // Check second equipment
        $secondEquipment = $generatorInfo['equipment_list'][1];
        $this->assertEquals('GEN-002', $secondEquipment['equipment_number']);
        $this->assertEquals('デンヨー', $secondEquipment['manufacturer']);
        $this->assertEquals('2020', $secondEquipment['model_year']);
        $this->assertEquals('2024-03-15', $secondEquipment['update_date']);
    }

    /** @test */
    public function it_validates_generator_availability_values()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
                'availability' => '不明', // Invalid value
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_info.availability']);
    }

    /** @test */
    public function it_validates_generator_details_max_length()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
                'availability' => '有',
                'availability_details' => str_repeat('あ', 1001), // Exceeds 1000 character limit
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['generator_info.availability_details']);
    }

    /** @test */
    public function it_validates_equipment_list_field_lengths()
    {
        $this->actingAs($this->user);

        $data = [
            'generator_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'equipment_number' => str_repeat('A', 51), // Exceeds 50 character limit
                        'manufacturer' => str_repeat('B', 256), // Exceeds 255 character limit
                        'model_year' => str_repeat('C', 11), // Exceeds 10 character limit
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
                'generator_info.equipment_list.0.equipment_number',
                'generator_info.equipment_list.0.manufacturer',
                'generator_info.equipment_list.0.model_year',
                'generator_info.equipment_list.0.update_date',
            ]);
    }

    /** @test */
    public function it_can_update_existing_generator_data()
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

        $electricalEquipment = ElectricalEquipment::create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'generator_info' => [
                'availability' => '無',
            ],
        ]);

        // Update to have generators
        $data = [
            'generator_info' => [
                'availability' => '有',
                'availability_details' => '更新された非常用発電機情報',
                'equipment_list' => [
                    [
                        'equipment_number' => 'GEN-NEW-001',
                        'manufacturer' => '新しいメーカー',
                        'model_year' => '2024',
                        'update_date' => '2024-09-17',
                    ],
                ],
            ],
        ];

        $response = $this->putJson(
            "/facilities/{$this->facility->id}/lifeline-equipment/electrical",
            $data
        );

        $response->assertStatus(200);

        // Verify updated data
        $electricalEquipment->refresh();
        $generatorInfo = $electricalEquipment->generator_info;

        $this->assertEquals('有', $generatorInfo['availability']);
        $this->assertEquals('更新された非常用発電機情報', $generatorInfo['availability_details']);
        $this->assertCount(1, $generatorInfo['equipment_list']);
        $this->assertEquals('GEN-NEW-001', $generatorInfo['equipment_list'][0]['equipment_number']);
    }

    /** @test */
    public function it_requires_proper_authorization_to_update_generator_data()
    {
        // Create a user without edit permissions
        $unauthorizedUser = User::factory()->create([
            'role' => 'viewer',
        ]);

        $this->actingAs($unauthorizedUser);

        $data = [
            'generator_info' => [
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

    /** @test */
    public function it_can_retrieve_generator_data()
    {
        $this->actingAs($this->user);

        // Create test data
        $lifelineEquipment = LifelineEquipment::create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $electricalEquipment = ElectricalEquipment::create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'generator_info' => [
                'availability' => '有',
                'availability_details' => 'テスト用非常用発電機',
                'equipment_list' => [
                    [
                        'equipment_number' => 'GEN-TEST-001',
                        'manufacturer' => 'テストメーカー',
                        'model_year' => '2023',
                        'update_date' => '2024-01-01',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'generator_info' => [
                        'availability' => '有',
                        'availability_details' => 'テスト用非常用発電機',
                        'equipment_list' => [
                            [
                                'equipment_number' => 'GEN-TEST-001',
                                'manufacturer' => 'テストメーカー',
                                'model_year' => '2023',
                                'update_date' => '2024-01-01',
                            ],
                        ],
                    ],
                ],
            ]);
    }
}