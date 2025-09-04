<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LandInfoControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'access_scope' => null
        ]);

        $this->editorUser = User::factory()->create([
            'role' => 'editor',
            'access_scope' => null
        ]);

        $this->viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => null
        ]);

        // Create test facility
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function admin_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000,
            'site_area_tsubo' => 100.0
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'owned',
                    'purchase_price' => 10000000,
                    'site_area_tsubo' => 100.0
                ]
            ]);
    }

    /** @test */
    public function editor_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'ownership_type' => 'leased',
            'monthly_rent' => 500000
        ]);

        $response = $this->actingAs($this->editorUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'leased',
                    'monthly_rent' => 500000
                ]
            ]);
    }

    /** @test */
    public function viewer_can_view_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned_rental'
        ]);

        $response = $this->actingAs($this->viewerUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ownership_type' => 'owned_rental'
                ]
            ]);
    }

    /** @test */
    public function returns_null_when_no_land_info_exists()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'message' => '土地情報が登録されていません。'
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_land_info()
    {
        $response = $this->getJson("/facilities/{$this->facility->id}/land-info");

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_edit_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'facility_id' => $this->facility->id
                ]
            ]);
    }

    /** @test */
    public function editor_can_edit_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id
        ]);

        $response = $this->actingAs($this->editorUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function viewer_cannot_edit_land_info()
    {
        $response = $this->actingAs($this->viewerUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/edit");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を編集する権限がありません。'
            ]);
    }

    /** @test */
    public function admin_can_create_new_land_info()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'site_area_sqm' => 300.50,
            'site_area_tsubo' => 90.91,
            'purchase_price' => 15000000,
            'notes' => 'Test land information'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を更新しました。'
            ]);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned',
            'parking_spaces' => 50,
            'purchase_price' => 15000000
        ]);
    }

    /** @test */
    public function editor_can_update_existing_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000
        ]);

        $updateData = [
            'ownership_type' => 'leased',
            'monthly_rent' => 800000,
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-12-31',
            'auto_renewal' => 'yes'
        ];

        $response = $this->actingAs($this->editorUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を更新しました。'
            ]);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'ownership_type' => 'leased',
            'monthly_rent' => 800000,
            'auto_renewal' => 'yes'
        ]);
    }

    /** @test */
    public function viewer_cannot_update_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id
        ]);

        $updateData = [
            'ownership_type' => 'owned',
            'purchase_price' => 20000000
        ];

        $response = $this->actingAs($this->viewerUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を編集する権限がありません。'
            ]);
    }

    /** @test */
    public function validates_required_ownership_type()
    {
        $landData = [
            'parking_spaces' => 50,
            'site_area_sqm' => 300.50
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ownership_type']);
    }

    /** @test */
    public function validates_ownership_type_values()
    {
        $landData = [
            'ownership_type' => 'invalid_type',
            'parking_spaces' => 50
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ownership_type']);
    }

    /** @test */
    public function validates_numeric_fields()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => 'not_a_number',
            'site_area_sqm' => 'invalid',
            'purchase_price' => 'not_numeric'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'parking_spaces',
                'site_area_sqm',
                'purchase_price'
            ]);
    }

    /** @test */
    public function validates_date_fields()
    {
        $landData = [
            'ownership_type' => 'leased',
            'contract_start_date' => 'invalid_date',
            'contract_end_date' => '2024-01-01'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_start_date']);
    }

    /** @test */
    public function validates_contract_end_date_after_start_date()
    {
        $landData = [
            'ownership_type' => 'leased',
            'contract_start_date' => '2024-12-31',
            'contract_end_date' => '2024-01-01'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contract_end_date']);
    }

    /** @test */
    public function validates_email_format()
    {
        $landData = [
            'ownership_type' => 'leased',
            'management_company_email' => 'invalid_email',
            'owner_email' => 'also_invalid'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_email',
                'owner_email'
            ]);
    }

    /** @test */
    public function validates_url_format()
    {
        $landData = [
            'ownership_type' => 'leased',
            'management_company_url' => 'not_a_url',
            'owner_url' => 'invalid_url'
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_url',
                'owner_url'
            ]);
    }

    /** @test */
    public function validates_string_length_limits()
    {
        $landData = [
            'ownership_type' => 'owned',
            'management_company_name' => str_repeat('a', 31), // Max 30
            'owner_name' => str_repeat('b', 31), // Max 30
            'notes' => str_repeat('c', 2001) // Max 2000
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'management_company_name',
                'owner_name',
                'notes'
            ]);
    }

    /** @test */
    public function can_calculate_unit_price()
    {
        $calculationData = [
            'calculation_type' => 'unit_price',
            'purchase_price' => 10000000,
            'site_area_tsubo' => 100.0
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unit_price' => 100000.0
                ]
            ]);
    }

    /** @test */
    public function can_calculate_contract_period()
    {
        $calculationData = [
            'calculation_type' => 'contract_period',
            'contract_start_date' => '2020-01-01',
            'contract_end_date' => '2025-06-01'
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'contract_period' => '5年5ヶ月'
                ]
            ]);
    }

    /** @test */
    public function validates_calculation_request()
    {
        $calculationData = [
            'calculation_type' => 'invalid_type'
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/calculate", $calculationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['calculation_type']);
    }

    /** @test */
    public function can_get_land_info_status()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/facilities/{$this->facility->id}/land-info/status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'has_pending_changes' => false
                ]
            ]);
    }

    /** @test */
    public function admin_can_approve_pending_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を承認しました。'
            ]);

        $this->assertDatabaseHas('land_info', [
            'id' => $landInfo->id,
            'status' => 'approved',
            'approved_by' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function editor_cannot_approve_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->editorUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'この施設の土地情報を承認する権限がありません。'
            ]);
    }

    /** @test */
    public function admin_can_reject_pending_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'pending_approval'
        ]);

        $rejectionData = [
            'rejection_reason' => '入力内容に不備があります。'
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/reject", $rejectionData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '土地情報を差戻ししました。'
            ]);

        $this->assertDatabaseHas('land_info', [
            'id' => $landInfo->id,
            'status' => 'draft',
            'rejection_reason' => '入力内容に不備があります。',
            'rejected_by' => $this->adminUser->id
        ]);
    }

    /** @test */
    public function rejection_requires_reason()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'pending_approval'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);
    }

    /** @test */
    public function cannot_approve_non_pending_land_info()
    {
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $this->facility->id,
            'status' => 'approved'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/facilities/{$this->facility->id}/land-info/approve");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => '承認待ちの土地情報がありません。'
            ]);
    }

    /** @test */
    public function converts_full_width_numbers_to_half_width()
    {
        $landData = [
            'ownership_type' => 'owned',
            'parking_spaces' => '５０', // Full-width numbers
            'purchase_price' => '１０００００００' // Full-width numbers
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/facilities/{$this->facility->id}/land-info", $landData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('land_info', [
            'facility_id' => $this->facility->id,
            'parking_spaces' => 50,
            'purchase_price' => 10000000
        ]);
    }
}
