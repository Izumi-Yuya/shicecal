<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesTestFacilities;
use Tests\Traits\CreatesTestUsers;

class FacilityControllerTest extends TestCase
{
    use CreatesTestFacilities, CreatesTestUsers, RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected User $editorUser;

    protected User $viewerUser;

    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->adminUser = $this->createUserWithRole('admin');
        $this->editorUser = $this->createUserWithRole('editor');
        $this->viewerUser = $this->createUserWithRole('viewer');

        // Create test facility
        $this->facility = Facility::factory()->create();
    }

    // ========================================
    // Basic Facility Tests
    // ========================================

    public function test_facility_index_page_loads()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('facilities.index'));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.index');
    }

    public function test_facility_show_page_loads()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.show');
    }

    public function test_admin_can_create_facility()
    {
        $facilityData = [
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'address' => '東京都渋谷区',
            'phone_number' => '03-1234-5678',
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('facilities.store'), $facilityData);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', [
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
        ]);
    }

    public function test_admin_can_update_facility()
    {
        $updateData = [
            'company_name' => $this->facility->company_name, // Keep existing required field
            'office_code' => $this->facility->office_code,   // Keep existing required field
            'facility_name' => '更新されたテスト施設',
            'address' => '更新された住所',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('facilities.update', $this->facility), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', [
            'id' => $this->facility->id,
            'facility_name' => '更新されたテスト施設',
            'address' => '更新された住所',
        ]);
    }

    public function test_viewer_cannot_create_facility()
    {
        $facilityData = [
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
        ];

        $response = $this->actingAs($this->viewerUser)
            ->post(route('facilities.store'), $facilityData);

        $response->assertStatus(403);
    }

    // ========================================
    // Land Info Tests (Merged from LandInfoControllerTest)
    // ========================================

    public function test_admin_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->owned()->create([
            'facility_id' => $this->facility->id,
            'purchase_price' => 10000000,
            'site_area_tsubo' => 100.0,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'owned',
                    'purchase_price' => 10000000,
                    'site_area_tsubo' => 100.0,
                ],
            ]);
    }

    public function test_editor_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->leased()->create([
            'facility_id' => $this->facility->id,
            'monthly_rent' => 500000,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $response = $this->actingAs($this->editorUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'leased',
                    'monthly_rent' => 500000,
                ],
            ]);
    }

    public function test_viewer_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->ownedRental()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'owned_rental',
                ],
            ]);
    }

    public function test_returns_null_when_no_land_info_exists()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'message' => '土地情報が登録されていません。',
            ]);
    }

    public function test_unauthenticated_user_cannot_view_land_info()
    {
        $response = $this->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(401);
    }

    public function test_admin_can_edit_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'facility_id' => $this->facility->id,
                ],
            ]);
    }

    public function test_editor_can_edit_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $response = $this->actingAs($this->editorUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_viewer_cannot_edit_land_info()
    {
        $response = $this->actingAs($this->viewerUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を編集する権限がありません。',
            ]);
    }

    public function test_admin_can_create_new_land_info()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'site_area_sqm' => 300.50,
            'site_area_tsubo' => 90.91,
            'purchase_price' => 15000000,
            'notes' => 'Test land information',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を更新しました。',
            ]);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'purchase_price' => 15000000,
        ]);
    }

    public function test_editor_can_update_existing_land_info()
    {
        $landInfo = LandInfo::factory()->owned()->create([
            'facility_id' => $this->facility->id,
            'purchase_price' => 10000000,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $updateData = [
            'ownership_type' => 'leased',
            'monthly_rent' => 800000,
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-12-31',
            'auto_renewal' => 'yes',
        ];

        $response = $this->actingAs($this->editorUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を更新しました。',
            ]);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'ownership_type' => 'leased',
            'monthly_rent' => 800000,
            'auto_renewal' => 'yes',
        ]);
    }

    public function test_viewer_cannot_update_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $updateData = [
            'ownership_type' => 'owned',
            'purchase_price' => 20000000,
        ];

        $response = $this->actingAs($this->viewerUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を編集する権限がありません。',
            ]);
    }

    // ========================================
    // Land Info Validation Tests
    // ========================================

    public function test_validates_required_ownership_type()
    {
        $landData = [
            'parking_spaces' => 50,
            'site_area_sqm' => 300.50,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ownership_type']);
    }

    public function test_validates_ownership_type_values()
    {
        $landData = [
            'ownership_type' => 'invalid_type',
            'parking_spaces' => 50,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ownership_type']);
    }

    public function test_validates_numeric_fields()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => 'not_a_number',
            'site_area_sqm' => 'invalid',
            'purchase_price' => 'not_numeric',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'parking_spaces',
                'site_area_sqm',
                'purchase_price',
            ]);
    }

    public function test_validates_date_fields()
    {
        $landData = [
            'ownership_type' => 'leased',
            'contract_start_date' => 'invalid_date',
            'contract_end_date' => '2024-01-01',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_start_date']);
    }

    public function test_validates_contract_end_date_after_start_date()
    {
        $landData = [
            'ownership_type' => 'leased',
            'contract_start_date' => '2024-12-31',
            'contract_end_date' => '2024-01-01',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_end_date']);
    }

    public function test_validates_email_format()
    {
        $landData = [
            'ownership_type' => 'leased',
            'management_company_email' => 'invalid_email',
            'owner_email' => 'also_invalid',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_email',
                'owner_email',
            ]);
    }

    public function test_validates_url_format()
    {
        $landData = [
            'ownership_type' => 'leased',
            'management_company_url' => 'not_a_url',
            'owner_url' => 'invalid_url',
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_url',
                'owner_url',
            ]);
    }

    public function test_validates_string_length_limits()
    {
        $landData = [
            'ownership_type' => 'owned',
            'management_company_name' => str_repeat('a', 31), // Max 30
            'owner_name' => str_repeat('b', 31), // Max 30
            'notes' => str_repeat('c', 2001), // Max 2000
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_name',
                'owner_name',
                'notes',
            ]);
    }

    // ========================================
    // Land Info Calculation Tests
    // ========================================

    public function test_can_calculate_unit_price()
    {
        $calculationData = [
            'calculation_type' => 'unit_price',
            'purchase_price' => 10000000,
            'site_area_tsubo' => 100.0,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unit_price' => 100000.0,
                ],
            ]);
    }

    public function test_can_calculate_contract_period()
    {
        $calculationData = [
            'calculation_type' => 'contract_period',
            'contract_start_date' => '2020-01-01',
            'contract_end_date' => '2025-06-01',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'contract_period' => '5年5ヶ月',
                ],
            ]);
    }

    public function test_validates_calculation_request()
    {
        $calculationData = [
            'calculation_type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['calculation_type']);
    }

    // ========================================
    // Land Info Approval Tests
    // ========================================

    public function test_can_get_land_info_status()
    {
        $landInfo = LandInfo::factory()->approved()->create([
            'facility_id' => $this->facility->id,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'has_pending_changes' => false,
                ],
            ]);
    }

    public function test_admin_can_approve_pending_land_info()
    {
        $landInfo = LandInfo::factory()->pendingApproval()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を承認しました。',
            ]);

        $this->assertDatabaseHas('land_info', [
            'id' => $landInfo->id,
            'status' => 'approved',
            'approved_by' => $this->adminUser->id,
        ]);
    }

    public function test_editor_cannot_approve_land_info()
    {
        $landInfo = LandInfo::factory()->pendingApproval()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $response = $this->actingAs($this->editorUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を承認する権限がありません。',
            ]);
    }

    public function test_admin_can_reject_pending_land_info()
    {
        $landInfo = LandInfo::factory()->pendingApproval()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $rejectionData = [
            'rejection_reason' => '入力内容に不備があります。',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/reject", $rejectionData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を差戻ししました。',
            ]);

        $this->assertDatabaseHas('land_info', [
            'id' => $landInfo->id,
            'status' => 'draft',
            'rejection_reason' => '入力内容に不備があります。',
            'rejected_by' => $this->adminUser->id,
        ]);
    }

    public function test_rejection_requires_reason()
    {
        $landInfo = LandInfo::factory()->pendingApproval()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->editorUser->id,
            'updated_by' => $this->editorUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_cannot_approve_non_pending_land_info()
    {
        $landInfo = LandInfo::factory()->approved()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '承認待ちの土地情報がありません。',
            ]);
    }

    public function test_converts_full_width_numbers_to_half_width()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => '５０', // Full-width numbers
            'purchase_price' => '１０００００００', // Full-width numbers
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'parking_spaces' => 50,
            'purchase_price' => 10000000,
        ]);
    }

    public function test_view_toggle_component_renders_correctly()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check that viewMode variable is passed to the view
        $response->assertViewHas('viewMode');

        // The view toggle component should be included in the basic info tab
        // We'll test this when the component is integrated in task 4
    }

    public function test_set_view_mode_ajax_endpoint()
    {
        // Test setting card view mode
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('facilities.set-view-mode'), [
                'view_mode' => 'card',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'view_mode' => 'card',
        ]);

        // Test invalid view mode
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('facilities.set-view-mode'), [
                'view_mode' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    // ========================================
    // Edit Workflow Integration Tests (Task 8)
    // ========================================

    public function test_seamless_transition_back_to_selected_view_mode_after_editing()
    {
        // Test with card view mode
        $this->actingAs($this->adminUser)
            ->postJson(route('facilities.set-view-mode'), [
                'view_mode' => 'card',
            ]);

        // Edit and verify card view is maintained
        $updateData = [
            'company_name' => $this->facility->company_name,
            'office_code' => $this->facility->office_code,
            'facility_name' => 'カード表示テスト',
        ];

        $this->actingAs($this->adminUser)
            ->put(route('facilities.update-basic-info', $this->facility), $updateData);

        $response = $this->actingAs($this->adminUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertViewHas('viewMode', 'card');

    }
}
