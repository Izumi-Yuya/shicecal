<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicInfoFormConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'テスト施設',
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
        ]);
    }

    /** @test */
    public function basic_info_edit_form_uses_new_layout_system()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $response->assertStatus(200);

        // Check that the new layout structure is being used
        $response->assertSee('facility-edit-layout');
        $response->assertSee('form-section');
        $response->assertSee('breadcrumb');
        $response->assertSee('facility-info-card');

        // Check that the form sections are present
        $response->assertSee('基本情報');
        $response->assertSee('住所・連絡先');
        $response->assertSee('開設・建物情報');
        $response->assertSee('施設情報');
        $response->assertSee('サービスの種類・指定更新情報');
    }

    /** @test */
    public function basic_info_form_has_consistent_structure_with_land_info()
    {
        $basicInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        $basicInfoResponse->assertStatus(200);
        $landInfoResponse->assertStatus(200);

        // Both should use the same layout structure
        $basicInfoResponse->assertSee('facility-edit-layout');
        $landInfoResponse->assertSee('facility-edit-layout');

        // Both should have breadcrumbs
        $basicInfoResponse->assertSee('施設一覧');
        $landInfoResponse->assertSee('施設一覧');

        // Both should have facility info card
        $basicInfoResponse->assertSee($this->facility->facility_name);
        $landInfoResponse->assertSee($this->facility->facility_name);
    }

    /** @test */
    public function basic_info_form_has_proper_icons_and_colors()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $response->assertStatus(200);

        // Check for proper icon classes
        $response->assertSee('fas fa-info-circle'); // Basic info icon
        $response->assertSee('fas fa-map-marker-alt'); // Contact info icon
        $response->assertSee('fas fa-building'); // Building info icon
        $response->assertSee('fas fa-home'); // Facility info icon
        $response->assertSee('fas fa-cogs'); // Services icon

        // Check for proper color classes
        $response->assertSee('text-primary'); // Basic info color
        $response->assertSee('text-success'); // Contact info color
        $response->assertSee('text-info'); // Building info color
        $response->assertSee('text-warning'); // Facility info color
        $response->assertSee('text-dark'); // Services color
    }

    /** @test */
    public function basic_info_form_maintains_existing_functionality()
    {
        $response = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $response->assertStatus(200);

        // Check that all required fields are present
        $response->assertSee('name="company_name"', false);
        $response->assertSee('name="office_code"', false);
        $response->assertSee('name="facility_name"', false);

        // Check that service management functionality is present
        $response->assertSee('add-service-btn');
        $response->assertSee('service-count');
        $response->assertSee('clearServiceRow');

        // Check that form action is correct
        $response->assertSee('action="'.route('facilities.update-basic-info', $this->facility).'"', false);
    }

    /** @test */
    public function both_forms_have_consistent_error_handling()
    {
        // Test that error display structure is consistent
        $basicInfoFormResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $landInfoFormResponse = $this->actingAs($this->user)
            ->get(route('facilities.land-info.edit', $this->facility));

        // Both should use consistent error display structure
        $basicInfoFormResponse->assertSee('form-section');
        $landInfoFormResponse->assertSee('form-section');

        // Both should have the same form structure elements
        $basicInfoFormResponse->assertSee('facility-edit-layout');
        $landInfoFormResponse->assertSee('facility-edit-layout');

        // Both should have consistent action buttons
        $basicInfoFormResponse->assertSee('キャンセル');
        $basicInfoFormResponse->assertSee('保存');
        $landInfoFormResponse->assertSee('キャンセル');
        $landInfoFormResponse->assertSee('保存');
    }
}
