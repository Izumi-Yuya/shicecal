<?php

namespace Tests\Feature;

use App\Models\AnnualConfirmation;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnnualConfirmationRequestTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);
        
        // Create test facilities
        $this->facility1 = Facility::factory()->create(['status' => 'approved']);
        $this->facility2 = Facility::factory()->create(['status' => 'approved']);
    }

    public function test_admin_can_access_annual_confirmation_index()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.index');
    }

    public function test_admin_can_access_create_form()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.create');
        $response->assertViewHas('facilities');
    }

    public function test_non_admin_cannot_access_create_form()
    {
        $this->actingAs($this->editor);
        
        $response = $this->get(route('annual-confirmation.create'));
        
        $response->assertStatus(403);
    }

    public function test_admin_can_send_annual_confirmation_requests()
    {
        $this->actingAs($this->admin);
        
        $year = date('Y');
        
        $response = $this->post(route('annual-confirmation.store'), [
            'year' => $year,
            'facility_ids' => [$this->facility1->id, $this->facility2->id]
        ]);
        
        $response->assertRedirect(route('annual-confirmation.index'));
        $response->assertSessionHas('success');
        
        // Check that confirmations were created
        $this->assertDatabaseHas('annual_confirmations', [
            'confirmation_year' => $year,
            'facility_id' => $this->facility1->id,
            'requested_by' => $this->admin->id,
            'status' => 'pending'
        ]);
        
        $this->assertDatabaseHas('annual_confirmations', [
            'confirmation_year' => $year,
            'facility_id' => $this->facility2->id,
            'requested_by' => $this->admin->id,
            'status' => 'pending'
        ]);
        
        // Check that notifications were created (since we have a viewer user from setUp)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->viewer->id,
            'type' => 'annual_confirmation_request'
        ]);
    }

    public function test_cannot_send_request_without_facilities()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post(route('annual-confirmation.store'), [
            'year' => date('Y'),
            'facility_ids' => []
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseCount('annual_confirmations', 0);
    }

    public function test_can_view_confirmation_details()
    {
        $confirmation = AnnualConfirmation::factory()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.show', $confirmation));
        
        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.show');
        $response->assertViewHas('annualConfirmation', $confirmation);
    }

    public function test_facility_manager_can_view_their_confirmation()
    {
        $confirmation = AnnualConfirmation::factory()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $response = $this->get(route('annual-confirmation.show', $confirmation));
        
        $response->assertStatus(200);
    }

    public function test_facility_manager_cannot_view_other_confirmations()
    {
        $otherManager = User::factory()->create(['role' => 'viewer']);
        
        $confirmation = AnnualConfirmation::factory()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $otherManager->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $response = $this->get(route('annual-confirmation.show', $confirmation));
        
        $response->assertStatus(403);
    }

    public function test_facility_manager_can_confirm_information()
    {
        $confirmation = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $response = $this->post(route('annual-confirmation.respond', $confirmation), [
            'response' => 'confirmed'
        ]);
        
        $response->assertRedirect(route('annual-confirmation.show', $confirmation));
        $response->assertSessionHas('success');
        
        $confirmation->refresh();
        $this->assertEquals('confirmed', $confirmation->status);
        $this->assertNotNull($confirmation->responded_at);
        $this->assertNull($confirmation->discrepancy_details);
    }

    public function test_facility_manager_can_report_discrepancy()
    {
        $confirmation = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $discrepancyDetails = 'The phone number is incorrect.';
        
        $response = $this->post(route('annual-confirmation.respond', $confirmation), [
            'response' => 'discrepancy',
            'discrepancy_details' => $discrepancyDetails
        ]);
        
        $response->assertRedirect(route('annual-confirmation.show', $confirmation));
        $response->assertSessionHas('success');
        
        $confirmation->refresh();
        $this->assertEquals('discrepancy_reported', $confirmation->status);
        $this->assertEquals($discrepancyDetails, $confirmation->discrepancy_details);
        $this->assertNotNull($confirmation->responded_at);
        
        // Check that notification was sent to editors
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->editor->id,
            'type' => 'discrepancy_reported'
        ]);
    }

    public function test_cannot_report_discrepancy_without_details()
    {
        $confirmation = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $response = $this->post(route('annual-confirmation.respond', $confirmation), [
            'response' => 'discrepancy',
            'discrepancy_details' => ''
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $confirmation->refresh();
        $this->assertEquals('pending', $confirmation->status);
    }

    public function test_editor_can_resolve_discrepancy()
    {
        $confirmation = AnnualConfirmation::factory()->discrepancyReported()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->editor);
        
        $response = $this->patch(route('annual-confirmation.resolve', $confirmation));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $confirmation->refresh();
        $this->assertEquals('resolved', $confirmation->status);
        $this->assertNotNull($confirmation->resolved_at);
    }

    public function test_viewer_cannot_resolve_discrepancy()
    {
        $confirmation = AnnualConfirmation::factory()->discrepancyReported()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->viewer->id
        ]);
        
        $this->actingAs($this->viewer);
        
        $response = $this->patch(route('annual-confirmation.resolve', $confirmation));
        
        $response->assertStatus(403);
    }

    public function test_can_filter_confirmations_by_year()
    {
        $confirmation2023 = AnnualConfirmation::factory()->forYear(2023)->create();
        $confirmation2024 = AnnualConfirmation::factory()->forYear(2024)->create();
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.index', ['year' => 2023]));
        
        $response->assertStatus(200);
        $response->assertViewHas('confirmations');
        $response->assertViewHas('year', 2023);
    }

    public function test_get_facilities_ajax_endpoint()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.facilities'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'facility_name', 'office_code', 'company_name']
        ]);
    }

    public function test_get_facilities_with_search()
    {
        $searchableFacility = Facility::factory()->create([
            'facility_name' => 'Test Facility Search',
            'status' => 'approved'
        ]);
        
        $this->actingAs($this->admin);
        
        $response = $this->get(route('annual-confirmation.facilities', ['search' => 'Test Facility']));
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'facility_name' => 'Test Facility Search'
        ]);
    }
}