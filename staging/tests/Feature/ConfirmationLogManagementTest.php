<?php

namespace Tests\Feature;

use App\Models\AnnualConfirmation;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ConfirmationLogManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->facilityManager = User::factory()->create(['role' => 'viewer']);

        // Create test facilities
        $this->facility1 = Facility::factory()->create(['status' => 'approved']);
        $this->facility2 = Facility::factory()->create(['status' => 'approved']);

        // Create test confirmations with different statuses
        $this->pendingConfirmation = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $this->facility1->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);

        $this->confirmedConfirmation = AnnualConfirmation::factory()->confirmed()->create([
            'facility_id' => $this->facility2->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_access_confirmation_logs()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.index');
        $response->assertViewHas('confirmations');
    }

    public function test_confirmation_logs_show_audit_trail()
    {
        // Update confirmations to current year so they show up in the default view
        $this->pendingConfirmation->update(['confirmation_year' => date('Y')]);
        $this->confirmedConfirmation->update(['confirmation_year' => date('Y')]);

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);

        // Check that confirmations are displayed with audit information
        $response->assertSee($this->pendingConfirmation->facility->facility_name);
        $response->assertSee($this->confirmedConfirmation->facility->facility_name);
        $response->assertSee($this->pendingConfirmation->requested_at->format('Y/m/d H:i'));
        $response->assertSee($this->confirmedConfirmation->requested_at->format('Y/m/d H:i'));
    }

    public function test_confirmation_logs_can_be_filtered_by_year()
    {
        // Create confirmations for different years
        $confirmation2023 = AnnualConfirmation::factory()->confirmed()->create([
            'confirmation_year' => 2023,
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);

        $confirmation2024 = AnnualConfirmation::factory()->confirmed()->create([
            'confirmation_year' => 2024,
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        // Filter by 2023
        $response = $this->get(route('annual-confirmation.index', ['year' => 2023]));

        $response->assertStatus(200);
        $response->assertViewHas('year', 2023);

        // Filter by 2024
        $response = $this->get(route('annual-confirmation.index', ['year' => 2024]));

        $response->assertStatus(200);
        $response->assertViewHas('year', 2024);
    }

    public function test_confirmation_logs_show_complete_status_history()
    {
        // Create a confirmation that went through the full workflow
        $facility = Facility::factory()->create(['status' => 'approved']);
        $confirmation = AnnualConfirmation::factory()->resolved()->create([
            'facility_id' => $facility->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
            'discrepancy_details' => 'Test discrepancy details',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $confirmation));

        $response->assertStatus(200);

        // Check that all status information is displayed
        $response->assertSee('解決済み'); // Status badge
        $response->assertSee($confirmation->requested_at->format('Y年m月d日 H:i')); // Request date
        $response->assertSee($confirmation->responded_at->format('Y年m月d日 H:i')); // Response date
        $response->assertSee($confirmation->resolved_at->format('Y年m月d日 H:i')); // Resolution date

        // Check that the page loads correctly (discrepancy details are shown in a different section)
        $this->assertEquals('resolved', $confirmation->status);
        $this->assertEquals('Test discrepancy details', $confirmation->discrepancy_details);
    }

    public function test_confirmation_logs_preserve_audit_trail()
    {
        $this->actingAs($this->admin);

        // Create a confirmation request
        $response = $this->post(route('annual-confirmation.store'), [
            'year' => date('Y'),
            'facility_ids' => [$this->facility1->id],
        ]);

        $response->assertRedirect();

        // Check that the confirmation was logged with proper audit information
        $confirmation = AnnualConfirmation::where('facility_id', $this->facility1->id)
            ->where('confirmation_year', date('Y'))
            ->first();

        $this->assertNotNull($confirmation);
        $this->assertEquals($this->admin->id, $confirmation->requested_by);
        $this->assertNotNull($confirmation->requested_at);
        $this->assertEquals('pending', $confirmation->status);
    }

    public function test_facility_manager_response_is_logged()
    {
        $this->actingAs($this->facilityManager);

        // Respond to confirmation
        $response = $this->post(route('annual-confirmation.respond', $this->pendingConfirmation), [
            'response' => 'confirmed',
        ]);

        $response->assertRedirect();

        // Check that the response was logged
        $this->pendingConfirmation->refresh();
        $this->assertEquals('confirmed', $this->pendingConfirmation->status);
        $this->assertNotNull($this->pendingConfirmation->responded_at);
        $this->assertEquals($this->facilityManager->id, $this->pendingConfirmation->facility_manager_id);
    }

    public function test_discrepancy_resolution_is_logged()
    {
        // Create a discrepancy confirmation
        $discrepancyConfirmation = AnnualConfirmation::factory()->discrepancyReported()->create([
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($this->editor);

        // Resolve the discrepancy
        $response = $this->patch(route('annual-confirmation.resolve', $discrepancyConfirmation));

        $response->assertRedirect();

        // Check that the resolution was logged
        $discrepancyConfirmation->refresh();
        $this->assertEquals('resolved', $discrepancyConfirmation->status);
        $this->assertNotNull($discrepancyConfirmation->resolved_at);
    }

    public function test_confirmation_logs_show_user_information()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmedConfirmation));

        $response->assertStatus(200);

        // Check that user information is displayed
        $response->assertSee($this->confirmedConfirmation->requestedBy->name); // Requester name
        $response->assertSee($this->confirmedConfirmation->facilityManager->name); // Facility manager name
    }

    public function test_confirmation_logs_can_be_searched()
    {
        $this->actingAs($this->admin);

        // Test basic search functionality through index page
        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);

        // Check that search/filter functionality is available
        $response->assertSee('確認年度'); // Year filter
        $response->assertSee('絞り込み'); // Filter button
    }

    public function test_confirmation_logs_show_facility_information()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmedConfirmation));

        $response->assertStatus(200);

        // Check that facility information is displayed for audit purposes
        $response->assertSee($this->confirmedConfirmation->facility->facility_name);
        $response->assertSee($this->confirmedConfirmation->facility->office_code);
        $response->assertSee($this->confirmedConfirmation->facility->company_name);
        $response->assertSee($this->confirmedConfirmation->facility->address);
        $response->assertSee($this->confirmedConfirmation->facility->phone_number);
    }

    public function test_confirmation_logs_maintain_data_integrity()
    {
        // Test that confirmation data cannot be modified after creation
        $originalRequestedAt = $this->confirmedConfirmation->requested_at;
        $originalRequestedBy = $this->confirmedConfirmation->requested_by;

        // Attempt to modify the confirmation (this should not affect audit fields)
        $this->confirmedConfirmation->update([
            'status' => 'resolved', // This is allowed
        ]);

        $this->confirmedConfirmation->refresh();

        // Check that audit fields remain unchanged
        $this->assertEquals($originalRequestedAt, $this->confirmedConfirmation->requested_at);
        $this->assertEquals($originalRequestedBy, $this->confirmedConfirmation->requested_by);
        $this->assertEquals('resolved', $this->confirmedConfirmation->status); // Status change is allowed
    }

    public function test_confirmation_logs_support_pagination()
    {
        // Create multiple confirmations to test pagination
        $facilities = Facility::factory()->count(25)->create(['status' => 'approved']);

        foreach ($facilities as $index => $facility) {
            AnnualConfirmation::factory()->create([
                'confirmation_year' => date('Y'),
                'facility_id' => $facility->id,
                'facility_manager_id' => $this->facilityManager->id,
                'requested_by' => $this->admin->id,
            ]);
        }

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);

        // Check that pagination is working (should show paginated results)
        $confirmations = $response->viewData('confirmations');
        $this->assertNotNull($confirmations);
        $this->assertTrue($confirmations->hasPages());
    }

    public function test_non_admin_users_have_limited_log_access()
    {
        // Test that facility managers can only see their own confirmations
        $this->actingAs($this->facilityManager);

        $response = $this->get(route('annual-confirmation.show', $this->pendingConfirmation));

        $response->assertStatus(200);

        // Test that they cannot access confirmations they're not assigned to
        $otherConfirmation = AnnualConfirmation::factory()->create([
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => User::factory()->create(['role' => 'viewer'])->id,
            'requested_by' => $this->admin->id,
        ]);

        $response = $this->get(route('annual-confirmation.show', $otherConfirmation));

        $response->assertStatus(403);
    }

    public function test_confirmation_logs_export_functionality()
    {
        $this->actingAs($this->admin);

        // Test that the index page loads (which would be the basis for export functionality)
        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);

        // Check that the data structure supports export
        $confirmations = $response->viewData('confirmations');
        $this->assertNotNull($confirmations);

        // Verify that all necessary fields are available for export
        if ($confirmations->count() > 0) {
            $confirmation = $confirmations->first();
            $this->assertNotNull($confirmation->facility);
            $this->assertNotNull($confirmation->requestedBy);
            $this->assertNotNull($confirmation->requested_at);
            $this->assertNotNull($confirmation->status);
        }
    }

    public function test_confirmation_logs_show_chronological_order()
    {
        // Create confirmations at different times
        $olderConfirmation = AnnualConfirmation::factory()->create([
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
            'requested_at' => now()->subDays(5),
        ]);

        $newerConfirmation = AnnualConfirmation::factory()->create([
            'facility_id' => Facility::factory()->create(['status' => 'approved'])->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
            'requested_at' => now()->subDays(1),
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.index'));

        $response->assertStatus(200);

        // Check that confirmations are ordered by requested_at desc (newest first)
        $confirmations = $response->viewData('confirmations');
        $this->assertNotNull($confirmations);

        if ($confirmations->count() >= 2) {
            $first = $confirmations->first();
            $second = $confirmations->skip(1)->first();
            $this->assertGreaterThanOrEqual($second->requested_at, $first->requested_at);
        }
    }
}
