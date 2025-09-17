<?php

namespace Tests\Feature;

use App\Models\AnnualConfirmation;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnnualConfirmationResponseTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->facilityManager = User::factory()->create(['role' => 'viewer']);
        $this->otherManager = User::factory()->create(['role' => 'viewer']);

        // Create test facility
        $this->facility = Facility::factory()->create(['status' => 'approved']);

        // Create test confirmation
        $this->confirmation = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $this->facility->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);
    }

    public function test_facility_manager_can_view_response_form()
    {
        $this->actingAs($this->facilityManager);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertSee('年次確認回答');
        $response->assertSee('確認完了');
        $response->assertSee('相違報告');
    }

    public function test_other_users_cannot_view_response_form()
    {
        $this->actingAs($this->otherManager);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(403);
    }

    public function test_facility_manager_can_confirm_information_is_correct()
    {
        $this->actingAs($this->facilityManager);

        $response = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'confirmed',
        ]);

        $response->assertRedirect(route('annual-confirmation.show', $this->confirmation));
        $response->assertSessionHas('success', '確認完了を報告しました。');

        // Check database was updated
        $this->confirmation->refresh();
        $this->assertEquals('confirmed', $this->confirmation->status);
        $this->assertNotNull($this->confirmation->responded_at);
        $this->assertNull($this->confirmation->discrepancy_details);
        $this->assertNull($this->confirmation->resolved_at);
    }

    public function test_facility_manager_can_report_discrepancy()
    {
        $this->actingAs($this->facilityManager);

        $discrepancyDetails = 'The phone number listed is incorrect. The current number is 03-1234-5678.';

        $response = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'discrepancy',
            'discrepancy_details' => $discrepancyDetails,
        ]);

        $response->assertRedirect(route('annual-confirmation.show', $this->confirmation));
        $response->assertSessionHas('success', '相違報告を送信しました。');

        // Check database was updated
        $this->confirmation->refresh();
        $this->assertEquals('discrepancy_reported', $this->confirmation->status);
        $this->assertEquals($discrepancyDetails, $this->confirmation->discrepancy_details);
        $this->assertNotNull($this->confirmation->responded_at);
        $this->assertNull($this->confirmation->resolved_at);

        // Check notification was sent to editors
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->editor->id,
            'type' => 'discrepancy_reported',
        ]);
    }

    public function test_cannot_report_discrepancy_without_details()
    {
        $this->actingAs($this->facilityManager);

        $response = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'discrepancy',
            'discrepancy_details' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', '相違内容を入力してください。');

        // Check database was not updated
        $this->confirmation->refresh();
        $this->assertEquals('pending', $this->confirmation->status);
        $this->assertNull($this->confirmation->responded_at);
    }

    public function test_cannot_respond_to_already_responded_confirmation()
    {
        // Set confirmation as already confirmed
        $this->confirmation->update([
            'status' => 'confirmed',
            'responded_at' => now(),
        ]);

        $this->actingAs($this->facilityManager);

        $response = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'confirmed',
        ]);

        // Should still redirect but not change anything
        $response->assertRedirect(route('annual-confirmation.show', $this->confirmation));

        // Status should remain the same
        $this->confirmation->refresh();
        $this->assertEquals('confirmed', $this->confirmation->status);
    }

    public function test_other_facility_manager_cannot_respond()
    {
        $this->actingAs($this->otherManager);

        $response = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'confirmed',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_confirmation_response()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.show');
    }

    public function test_editor_can_view_any_confirmation_response()
    {
        $this->actingAs($this->editor);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertViewIs('annual-confirmation.show');
    }

    public function test_response_form_not_shown_for_non_pending_confirmations()
    {
        // Set confirmation as already confirmed
        $this->confirmation->update([
            'status' => 'confirmed',
            'responded_at' => now(),
        ]);

        $this->actingAs($this->facilityManager);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertDontSee('年次確認回答');
        $response->assertDontSee('記載内容に相違はありません');
        $response->assertDontSee('記載内容に相違があります');
    }

    public function test_response_form_not_shown_for_admin_or_editor()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertDontSee('年次確認回答');

        $this->actingAs($this->editor);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertDontSee('年次確認回答');
    }

    public function test_facility_information_is_displayed_correctly()
    {
        $this->actingAs($this->facilityManager);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertSee($this->facility->facility_name);
        $response->assertSee($this->facility->office_code);
        $response->assertSee($this->facility->company_name);
        $response->assertSee($this->confirmation->confirmation_year.'年度');
    }

    public function test_discrepancy_details_are_displayed_after_reporting()
    {
        $discrepancyDetails = 'The address is outdated. Please update to the new location.';

        // Report discrepancy
        $this->confirmation->update([
            'status' => 'discrepancy_reported',
            'discrepancy_details' => $discrepancyDetails,
            'responded_at' => now(),
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertSee('相違報告内容');
        $response->assertSee($discrepancyDetails);
        $response->assertSee('解決済みとしてマーク');
    }

    public function test_resolved_status_is_displayed_correctly()
    {
        // Set confirmation as resolved
        $this->confirmation->update([
            'status' => 'resolved',
            'discrepancy_details' => 'Some discrepancy',
            'responded_at' => now()->subHour(),
            'resolved_at' => now(),
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('annual-confirmation.show', $this->confirmation));

        $response->assertStatus(200);
        $response->assertSee('解決済み');
        $response->assertSee('この相違報告は解決済みとしてマークされています。');
        // The resolve button should not be shown for resolved confirmations
        $response->assertDontSee('解決済みとしてマークしますか');
    }

    public function test_multiple_discrepancy_reports_for_different_facilities()
    {
        // Create another facility and confirmation
        $facility2 = Facility::factory()->create(['status' => 'approved']);
        $confirmation2 = AnnualConfirmation::factory()->pending()->create([
            'facility_id' => $facility2->id,
            'facility_manager_id' => $this->facilityManager->id,
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($this->facilityManager);

        // Report discrepancy for first facility
        $response1 = $this->post(route('annual-confirmation.respond', $this->confirmation), [
            'response' => 'discrepancy',
            'discrepancy_details' => 'Issue with facility 1',
        ]);

        // Report discrepancy for second facility
        $response2 = $this->post(route('annual-confirmation.respond', $confirmation2), [
            'response' => 'discrepancy',
            'discrepancy_details' => 'Issue with facility 2',
        ]);

        $response1->assertRedirect();
        $response2->assertRedirect();

        // Check both confirmations were updated
        $this->confirmation->refresh();
        $confirmation2->refresh();

        $this->assertEquals('discrepancy_reported', $this->confirmation->status);
        $this->assertEquals('discrepancy_reported', $confirmation2->status);
        $this->assertEquals('Issue with facility 1', $this->confirmation->discrepancy_details);
        $this->assertEquals('Issue with facility 2', $confirmation2->discrepancy_details);
    }
}
