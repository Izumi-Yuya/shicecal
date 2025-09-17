<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentBasicInfoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $editor;
    private User $viewer;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);

        // Create test facility
        $this->facility = Facility::factory()->create();
    }

    public function test_admin_can_view_electrical_basic_info()
    {
        // Create lifeline equipment with electrical data
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理株式会社',
                'maintenance_inspection_date' => '2024-03-15',
                'inspection_report_pdf' => 'inspection_report_2024.pdf',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/facilities/{$this->facility->id}/lifeline-equipment/electrical");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'basic_info' => [
                        'electrical_contractor' => '東京電力',
                        'safety_management_company' => '保安管理株式会社',
                        'maintenance_inspection_date' => '2024-03-15',
                        'inspection_report_pdf' => 'inspection_report_2024.pdf',
                    ],
                ],
            ]);
    }

    public function test_editor_can_update_electrical_basic_info()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'safety_management_company' => '新保安管理株式会社',
                'maintenance_inspection_date' => '2024-04-20',
                'inspection_report_pdf' => 'new_inspection_report.pdf',
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ライフライン設備情報を更新しました。',
            ]);

        // Verify data was saved
        $lifelineEquipment->refresh();
        $electricalEquipment = $lifelineEquipment->electricalEquipment;

        $this->assertEquals('関西電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('新保安管理株式会社', $electricalEquipment->basic_info['safety_management_company']);
        $this->assertEquals('2024-04-20', $electricalEquipment->basic_info['maintenance_inspection_date']);
        $this->assertEquals('new_inspection_report.pdf', $electricalEquipment->basic_info['inspection_report_pdf']);
    }

    public function test_basic_info_validation_rules()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        // Test with invalid data
        $invalidData = [
            'basic_info' => [
                'electrical_contractor' => str_repeat('a', 256), // Too long
                'safety_management_company' => str_repeat('b', 256), // Too long
                'maintenance_inspection_date' => 'invalid-date',
                'inspection_report_pdf' => str_repeat('c', 256), // Too long
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'basic_info.electrical_contractor',
                'basic_info.safety_management_company',
                'basic_info.maintenance_inspection_date',
                'basic_info.inspection_report_pdf',
            ]);
    }

    public function test_viewer_cannot_update_electrical_basic_info()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
            ],
        ];

        $response = $this->actingAs($this->viewer)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設のライフライン設備情報を編集する権限がありません。',
            ]);
    }

    public function test_creates_lifeline_equipment_if_not_exists()
    {
        // No existing lifeline equipment
        $this->assertDatabaseMissing('lifeline_equipment', [
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '中部電力',
                'safety_management_company' => 'テスト保安管理',
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(200);

        // Verify lifeline equipment was created
        $this->assertDatabaseHas('lifeline_equipment', [
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'active',
        ]);

        // Verify electrical equipment was created
        $lifelineEquipment = LifelineEquipment::where([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ])->first();

        $electricalEquipment = $lifelineEquipment->electricalEquipment;
        $this->assertNotNull($electricalEquipment);
        $this->assertEquals('中部電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('テスト保安管理', $electricalEquipment->basic_info['safety_management_company']);
    }

    public function test_partial_update_preserves_existing_data()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理株式会社',
                'maintenance_inspection_date' => '2024-03-15',
                'inspection_report_pdf' => 'original_report.pdf',
            ],
            'pas_info' => [
                'availability' => '有',
                'update_date' => '2024-01-01',
            ],
        ]);

        // Update only basic_info
        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '関西電力',
                'safety_management_company' => '新保安管理株式会社',
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(200);

        // Verify basic_info was updated
        $electricalEquipment->refresh();
        $this->assertEquals('関西電力', $electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('新保安管理株式会社', $electricalEquipment->basic_info['safety_management_company']);

        // Verify pas_info was preserved (not overwritten)
        $this->assertNotNull($electricalEquipment->pas_info);
        $this->assertEquals('有', $electricalEquipment->pas_info['availability'] ?? null);
        $this->assertEquals('2024-01-01', $electricalEquipment->pas_info['update_date'] ?? null);
    }



    public function test_null_values_clear_specific_fields()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '保安管理株式会社',
            ],
        ]);

        // Send null values to clear specific fields
        $updateData = [
            'basic_info' => [
                'electrical_contractor' => null,
                'safety_management_company' => null,
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(200);

        // Verify fields were cleared
        $electricalEquipment->refresh();
        $this->assertNull($electricalEquipment->basic_info['electrical_contractor'] ?? null);
        $this->assertNull($electricalEquipment->basic_info['safety_management_company'] ?? null);
    }

    public function test_lifeline_equipment_status_updated_on_save()
    {
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
            'status' => 'draft',
        ]);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '九州電力',
            ],
        ];

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", $updateData);

        $response->assertStatus(200);

        // Verify status was updated to active
        $lifelineEquipment->refresh();
        $this->assertEquals('active', $lifelineEquipment->status);
        $this->assertEquals($this->editor->id, $lifelineEquipment->updated_by);
    }
}