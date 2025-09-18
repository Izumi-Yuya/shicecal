<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectricalEquipmentEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
        
        // Grant user permission to edit this facility
        $this->user->grantPermission('edit_facility', $this->facility->id);
    }

    /** @test */
    public function user_can_view_electrical_equipment_edit_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.lifeline-equipment.edit', [$this->facility, 'electrical']));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.lifeline-equipment.electrical-edit');
        $response->assertViewHas('facility', $this->facility);
        $response->assertViewHas('category', 'electrical');
    }

    /** @test */
    public function user_can_update_electrical_equipment_basic_info()
    {
        $this->actingAs($this->user);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'safety_management_company' => '安全管理株式会社',
                'maintenance_inspection_date' => '2024-01-15',
                'inspection_report_pdf' => 'inspection_report_2024.pdf',
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertRedirect(route('facilities.show', $this->facility) . '#electrical');
        $response->assertSessionHas('success', 'ライフライン設備情報を更新しました。');

        // Verify data was saved
        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $this->assertNotNull($lifelineEquipment);
        $this->assertNotNull($lifelineEquipment->electricalEquipment);
        
        $basicInfo = $lifelineEquipment->electricalEquipment->basic_info;
        $this->assertEquals('東京電力', $basicInfo['electrical_contractor']);
        $this->assertEquals('安全管理株式会社', $basicInfo['safety_management_company']);
        $this->assertEquals('2024-01-15', $basicInfo['maintenance_inspection_date']);
        $this->assertEquals('inspection_report_2024.pdf', $basicInfo['inspection_report_pdf']);
    }

    /** @test */
    public function user_can_update_pas_info()
    {
        $this->actingAs($this->user);

        $updateData = [
            'pas_info' => [
                'availability' => '有',
                'update_date' => '2024-02-01',
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertRedirect();

        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $pasInfo = $lifelineEquipment->electricalEquipment->pas_info;
        $this->assertEquals('有', $pasInfo['availability']);
        $this->assertEquals('2024-02-01', $pasInfo['update_date']);
    }

    /** @test */
    public function user_can_update_cubicle_equipment_list()
    {
        $this->actingAs($this->user);

        $updateData = [
            'cubicle_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'manufacturer' => '三菱電機',
                        'model_year' => '2020',
                        'equipment_number' => 'CB-001',
                        'update_date' => '2024-01-10',
                    ],
                    [
                        'manufacturer' => '東芝',
                        'model_year' => '2019',
                        'equipment_number' => 'CB-002',
                        'update_date' => '2024-01-11',
                    ],
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertRedirect();

        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $cubicleInfo = $lifelineEquipment->electricalEquipment->cubicle_info;
        $this->assertEquals('有', $cubicleInfo['availability']);
        $this->assertCount(2, $cubicleInfo['equipment_list']);
        
        $equipment1 = $cubicleInfo['equipment_list'][0];
        $this->assertEquals('三菱電機', $equipment1['manufacturer']);
        $this->assertEquals('2020', $equipment1['model_year']);
        $this->assertEquals('CB-001', $equipment1['equipment_number']);
    }

    /** @test */
    public function user_can_update_generator_equipment_list()
    {
        $this->actingAs($this->user);

        $updateData = [
            'generator_info' => [
                'availability' => '有',
                'equipment_list' => [
                    [
                        'manufacturer' => 'ヤンマー',
                        'model_year' => '2021',
                        'equipment_number' => 'GEN-001',
                        'update_date' => '2024-01-20',
                    ],
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertRedirect();

        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $generatorInfo = $lifelineEquipment->electricalEquipment->generator_info;
        $this->assertEquals('有', $generatorInfo['availability']);
        $this->assertCount(1, $generatorInfo['equipment_list']);
        
        $equipment = $generatorInfo['equipment_list'][0];
        $this->assertEquals('ヤンマー', $equipment['manufacturer']);
        $this->assertEquals('2021', $equipment['model_year']);
        $this->assertEquals('GEN-001', $equipment['equipment_number']);
    }

    /** @test */
    public function user_can_update_notes()
    {
        $this->actingAs($this->user);

        $updateData = [
            'notes' => '電気設備の定期点検は毎年実施しています。',
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertRedirect();

        $lifelineEquipment = LifelineEquipment::where('facility_id', $this->facility->id)
            ->where('category', 'electrical')
            ->first();

        $this->assertEquals('電気設備の定期点検は毎年実施しています。', $lifelineEquipment->electricalEquipment->notes);
    }

    /** @test */
    public function validation_fails_for_invalid_data()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'basic_info' => [
                'maintenance_inspection_date' => 'invalid-date',
            ],
            'cubicle_info' => [
                'availability' => '無効な値',
                'equipment_list' => [
                    [
                        'model_year' => 'invalid-year',
                    ],
                ],
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $invalidData
        );

        $response->assertSessionHasErrors([
            'basic_info.maintenance_inspection_date',
            'cubicle_info.availability',
            'cubicle_info.equipment_list.0.model_year',
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_edit_page()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->get(route('facilities.lifeline-equipment.edit', [$this->facility, 'electrical']));

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthorized_user_cannot_update_equipment()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $updateData = [
            'basic_info' => [
                'electrical_contractor' => '東京電力',
            ],
        ];

        $response = $this->put(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(403);
    }
}