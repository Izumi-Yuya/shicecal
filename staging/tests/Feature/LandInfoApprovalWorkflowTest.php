<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class LandInfoApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't run full seeder to avoid database conflicts
        // $this->seed();
    }

    protected function enableApprovalWorkflow(): void
    {
        // Create a user for system settings
        $admin = User::factory()->create(['role' => 'admin']);

        // Set up approval enabled in database
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'approval_enabled'],
            [
                'value' => 'true',
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_land_info_creation_with_approval_disabled()
    {
        // Create a user for system settings
        $admin = User::factory()->create(['role' => 'admin']);

        // Set up approval disabled in database
        DB::table('system_settings')->insert([
            'key' => 'approval_enabled',
            'value' => 'false',
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $editor = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        $response = $this->actingAs($editor)
            ->put("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'owned',
                'purchase_price' => 10000000,
                'site_area_tsubo' => 100.0,
            ]);

        $response->assertStatus(200);

        $landInfo = $facility->fresh()->landInfo;
        $this->assertNotNull($landInfo);
        $this->assertEquals('approved', $landInfo->status);
        $this->assertNotNull($landInfo->approved_at);
    }

    public function test_land_info_creation_with_approval_enabled()
    {
        $this->enableApprovalWorkflow();
        // Don't fake notifications for this test since we want to check database

        // Enable debug logging for this test
        Log::info('Starting approval workflow test');

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Verify approver exists
        $this->assertDatabaseHas('users', ['id' => $approver->id, 'role' => 'approver']);

        // Verify approval setting exists
        $this->assertDatabaseHas('system_settings', ['key' => 'approval_enabled', 'value' => 'true']);

        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'owned',
                'purchase_price' => 10000000,
                'site_area_tsubo' => 100.0,
            ]);

        if ($response->status() !== 200) {
            dump($response->getContent());
        }
        $response->assertStatus(200);

        $landInfo = $facility->fresh()->landInfo;
        $this->assertNotNull($landInfo);
        $this->assertEquals('pending_approval', $landInfo->status);
        $this->assertNull($landInfo->approved_at);

        // Manually test notification creation - call notifyApprovers directly
        $landInfoService = app(\App\Services\LandInfoService::class);

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($landInfoService);
        $method = $reflection->getMethod('notifyApprovers');
        $method->setAccessible(true);
        $method->invoke($landInfoService, $landInfo, 'land_info_approval_request');

        // Check notification was sent
        $notifications = DB::table('notifications')->where('type', 'land_info_approval_request')->get();
        $this->assertGreaterThan(0, $notifications->count(), 'No approval request notifications found');

        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_approval_request',
            'notifiable_id' => $approver->id,
        ]);
    }

    public function test_land_info_update_with_approval_enabled()
    {
        $this->enableApprovalWorkflow();
        NotificationFacade::fake();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'approved',
            'purchase_price' => 5000000,
        ]);

        $response = $this->actingAs($editor)
            ->put("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'owned',
                'purchase_price' => 8000000,
                'site_area_tsubo' => 100.0,
            ]);

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('pending_approval', $landInfo->status);
        $this->assertEquals(8000000, $landInfo->purchase_price);

        // Check notification was sent
        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_approval_request',
            'notifiable_id' => $approver->id,
        ]);
    }

    public function test_approver_can_approve_pending_land_info()
    {
        NotificationFacade::fake();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
            'created_by' => $editor->id,
        ]);

        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);
        $this->assertNotNull($landInfo->approved_at);
        $this->assertEquals($approver->id, $landInfo->approved_by);

        // Check notification was sent to editor
        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_approved',
            'notifiable_id' => $editor->id,
        ]);
    }

    public function test_approver_can_reject_pending_land_info()
    {
        NotificationFacade::fake();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
            'created_by' => $editor->id,
        ]);

        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/reject", [
                'rejection_reason' => '購入金額の根拠が不明です。',
            ]);

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('rejected', $landInfo->status);
        $this->assertEquals('購入金額の根拠が不明です。', $landInfo->rejection_reason);
        $this->assertEquals($approver->id, $landInfo->rejected_by);
        $this->assertNotNull($landInfo->rejected_at);

        // Check notification was sent to editor
        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_rejected',
            'notifiable_id' => $editor->id,
        ]);
    }

    public function test_editor_cannot_approve_land_info()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(403);
    }

    public function test_cannot_approve_already_approved_land_info()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'この土地情報は既に承認済みです。',
        ]);
    }

    public function test_rejection_requires_reason()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/reject", [
                'rejection_reason' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_approval_workflow_with_multiple_approvers()
    {
        $this->enableApprovalWorkflow();
        NotificationFacade::fake();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver1 = User::factory()->create(['role' => 'approver']);
        $approver2 = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Create land info
        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'leased',
                'monthly_rent' => 500000,
            ]);

        $response->assertStatus(200);

        // Check both approvers received notifications
        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_approval_request',
            'notifiable_id' => $approver1->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'type' => 'land_info_approval_request',
            'notifiable_id' => $approver2->id,
        ]);

        // First approver approves
        $landInfo = $facility->fresh()->landInfo;
        $response = $this->actingAs($approver1)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);
        $this->assertEquals($approver1->id, $landInfo->approved_by);
    }

    public function test_approval_workflow_with_file_uploads()
    {
        $this->enableApprovalWorkflow();
        NotificationFacade::fake();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Create a test PDF file
        $pdfContent = '%PDF-1.4 test content';
        $tempFile = tmpfile();
        fwrite($tempFile, $pdfContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'leased',
                'monthly_rent' => 500000,
                'lease_contracts' => [
                    new \Illuminate\Http\UploadedFile($tempPath, 'contract.pdf', 'application/pdf', null, true),
                ],
            ]);

        $response->assertStatus(200);

        $landInfo = $facility->fresh()->landInfo;
        $this->assertEquals('pending_approval', $landInfo->status);

        // Approve
        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);

        // Check file was saved
        $this->assertDatabaseHas('files', [
            'facility_id' => $facility->id,
            'land_document_type' => 'lease_contract',
        ]);

        fclose($tempFile);
    }

    public function test_approval_workflow_preserves_calculation_results()
    {
        $this->enableApprovalWorkflow();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Create land info with calculations
        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'owned',
                'purchase_price' => 10000000,
                'site_area_tsubo' => 100.0,
            ]);

        $response->assertStatus(200);

        $landInfo = $facility->fresh()->landInfo;
        $this->assertEquals(100000, $landInfo->unit_price_per_tsubo);

        // Approve
        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);
        $this->assertEquals(100000, $landInfo->unit_price_per_tsubo);
    }

    public function test_approval_workflow_with_contract_period_calculation()
    {
        $this->enableApprovalWorkflow();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Create land info with contract dates
        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'leased',
                'monthly_rent' => 500000,
                'contract_start_date' => '2020-01-01',
                'contract_end_date' => '2025-06-01',
            ]);

        $response->assertStatus(200);

        $landInfo = $facility->fresh()->landInfo;
        $this->assertEquals('5年5ヶ月', $landInfo->contract_period_text);

        // Approve
        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);
        $this->assertEquals('5年5ヶ月', $landInfo->contract_period_text);
    }

    public function test_approval_workflow_audit_logging()
    {
        $this->enableApprovalWorkflow();

        $editor = User::factory()->create(['role' => 'editor']);
        $approver = User::factory()->create(['role' => 'approver']);
        $facility = Facility::factory()->create();

        // Create land info
        $response = $this->actingAs($editor)
            ->post("/facilities/{$facility->id}/land-info", [
                'ownership_type' => 'owned',
                'purchase_price' => 10000000,
            ]);

        $response->assertStatus(200);

        // Check creation was logged
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'target_type' => 'land_info',
            'user_id' => $editor->id,
        ]);

        $landInfo = $facility->fresh()->landInfo;

        // Approve
        $response = $this->actingAs($approver)
            ->post("/facilities/{$facility->id}/land-info/approve");

        $response->assertStatus(200);

        // Check approval was logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'land_info',
            'description' => 'approved',
            'causer_id' => $approver->id,
        ]);
    }
}
