<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);

        $this->facility = Facility::factory()->create();
    }

    public function test_can_show_electrical_equipment_data()
    {
        // Create lifeline equipment with electrical data
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '○○保安管理株式会社',
            ],
            'notes' => 'テスト備考',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('facilities.lifeline-equipment.show', [
                'facility' => $this->facility->id,
                'category' => 'electrical'
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'basic_info' => [
                        'electrical_contractor' => '東京電力',
                        'safety_management_company' => '○○保安管理株式会社',
                    ],
                    'notes' => 'テスト備考',
                ],
            ]);
    }

    public function test_can_show_empty_electrical_equipment_data()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('facilities.lifeline-equipment.show', [
                'facility' => $this->facility->id,
                'category' => 'electrical'
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'basic_info' => [
                        'electrical_contractor' => '',
                        'safety_management_company' => '',
                        'maintenance_inspection_date' => '',
                        'inspection_report_pdf' => '',
                    ],
                    'pas_info' => [
                        'availability' => '',
                        'details' => '',
                        'update_date' => '',
                    ],
                    'cubicle_info' => [
                        'availability' => '',
                        'details' => '',
                        'equipment_list' => [],
                    ],
                    'generator_info' => [
                        'availability' => '',
                        'availability_details' => '',
                        'equipment_list' => [],
                    ],
                    'notes' => '',
                ],
            ]);
    }

    public function test_can_update_electrical_equipment_data()
    {
        $data = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'safety_management_company' => '新保安管理株式会社',
                'maintenance_inspection_date' => '2024-03-15',
            ],
            'pas_info' => [
                'availability' => '有',
                'update_date' => '2023-09-15',
            ],
            'notes' => '更新されたテスト備考',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('facilities.lifeline-equipment.update', [
                'facility' => $this->facility->id,
                'category' => 'electrical'
            ]), $data);

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
        $this->assertEquals('active', $lifelineEquipment->status);

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $this->assertNotNull($electricalEquipment);
        $this->assertEquals('関西電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']);
        $this->assertEquals('更新されたテスト備考', $electricalEquipment->notes);
    }

    public function test_returns_error_for_invalid_category()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('facilities.lifeline-equipment.show', [
                'facility' => $this->facility->id,
                'category' => 'invalid_category'
            ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => '無効なカテゴリです。',
            ]);
    }

    public function test_returns_under_development_for_non_electrical_categories()
    {
        $categories = ['water', 'gas', 'elevator', 'hvac_lighting'];

        foreach ($categories as $category) {
            $response = $this->actingAs($this->user)
                ->getJson(route('facilities.lifeline-equipment.show', [
                    'facility' => $this->facility->id,
                    'category' => $category
                ]));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'success',
                    'data',
                    'lifeline_equipment' => [
                        'id',
                        'status',
                        'category',
                        'category_display_name',
                        'status_display_name',
                        'updated_at',
                        'created_at'
                    ]
                ]);
        }
    }

    public function test_all_valid_roles_can_access_lifeline_equipment()
    {
        $roles = ['admin', 'editor', 'primary_responder', 'approver', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)
                ->getJson(route('facilities.lifeline-equipment.show', [
                    'facility' => $this->facility->id,
                    'category' => 'electrical'
                ]));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
        }
    }

    public function test_validation_errors_for_invalid_electrical_data()
    {
        $invalidData = [
            'basic_info' => [
                'maintenance_inspection_date' => 'invalid-date',
            ],
            'pas_info' => [
                'availability' => 'invalid-option',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('facilities.lifeline-equipment.update', [
                'facility' => $this->facility->id,
                'category' => 'electrical'
            ]), $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => '入力内容に誤りがあります。',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'basic_info.maintenance_inspection_date',
                    'pas_info.availability',
                ]
            ]);
    }
}