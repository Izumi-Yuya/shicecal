<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentPasCardTest extends TestCase
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

    public function test_pas_card_displays_correctly_when_no_data_exists()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('PAS');
        $response->assertSee('未登録');
    }

    public function test_pas_card_displays_data_when_availability_is_none()
    {
        $this->actingAs($this->user);

        // Create lifeline equipment with PAS info set to "無"
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'pas_info' => [
                'availability' => '無',
            ],
        ]);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('PAS');
        $response->assertSee('無');
        // Should not show PAS details section when availability is "無"
        $response->assertDontSee('PAS詳細情報のテスト');
        $response->assertDontSee('2024年3月15日');
    }

    public function test_pas_card_displays_full_data_when_availability_is_yes()
    {
        $this->actingAs($this->user);

        // Create lifeline equipment with full PAS info
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'pas_info' => [
                'availability' => '有',
                'details' => 'PAS詳細情報のテスト',
                'update_date' => '2024-03-15',
            ],
        ]);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('PAS');
        $response->assertSee('有');
        $response->assertSee('PAS詳細情報のテスト');
        $response->assertSee('2024年3月15日');
    }

    public function test_can_update_pas_info_to_none()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => '無',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'ライフライン設備情報を更新しました。',
        ]);

        // Verify data was saved
        $electricalEquipment = $lifelineEquipment->fresh()->electricalEquipment;
        $this->assertEquals('無', $electricalEquipment->pas_info['availability']);
    }

    public function test_can_update_pas_info_to_yes_with_details()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => '有',
                'details' => '新しいPAS詳細情報',
                'update_date' => '2024-09-17',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'ライフライン設備情報を更新しました。',
        ]);

        // Verify data was saved
        $electricalEquipment = $lifelineEquipment->fresh()->electricalEquipment;
        $this->assertEquals('有', $electricalEquipment->pas_info['availability']);
        $this->assertEquals('新しいPAS詳細情報', $electricalEquipment->pas_info['details']);
        $this->assertEquals('2024-09-17', $electricalEquipment->pas_info['update_date']);
    }

    public function test_pas_details_validation_max_length()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => '有',
                'details' => str_repeat('a', 1001), // Exceeds max length of 1000
                'update_date' => '2024-09-17',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pas_info.details']);
    }

    public function test_pas_availability_validation_invalid_value()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => 'invalid_value',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pas_info.availability']);
    }

    public function test_pas_update_date_validation_invalid_format()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => '有',
                'update_date' => 'invalid-date',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pas_info.update_date']);
    }

    public function test_unauthorized_user_cannot_update_pas_info()
    {
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
        ]);

        $this->actingAs($viewerUser);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
        ]);

        $updateData = [
            'pas_info' => [
                'availability' => '有',
                'details' => 'Unauthorized update attempt',
            ],
        ];

        $response = $this->putJson(
            route('facilities.lifeline-equipment.update', [$this->facility, 'electrical']),
            $updateData
        );

        $response->assertStatus(403);
    }

    public function test_pas_card_edit_mode_shows_correct_form_fields()
    {
        $this->actingAs($this->user);

        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'pas_info' => [
                'availability' => '有',
                'details' => 'Existing PAS details',
                'update_date' => '2024-03-15',
            ],
        ]);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        // Check that edit form elements are present
        $response->assertSee('name="pas_info[availability]"', false);
        $response->assertSee('name="pas_info[details]"', false);
        $response->assertSee('name="pas_info[update_date]"', false);
        // Check that existing values are populated
        $response->assertSee('value="有"', false);
        $response->assertSee('Existing PAS details');
        $response->assertSee('value="2024-03-15"', false);
    }
}