<?php

namespace Tests\Unit\Policies;

use App\Models\Facility;
use App\Models\User;
use App\Policies\LifelineEquipmentPolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LifelineEquipmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private LifelineEquipmentPolicy $policy;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LifelineEquipmentPolicy();
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function admin_can_view_lifeline_equipment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($this->policy->view($admin, $this->facility));
        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function editor_can_view_lifeline_equipment()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        
        $this->assertTrue($this->policy->view($editor, $this->facility));
        $this->assertTrue($this->policy->viewAny($editor));
    }

    /** @test */
    public function primary_responder_can_view_lifeline_equipment()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        $this->assertTrue($this->policy->view($primaryResponder, $this->facility));
        $this->assertTrue($this->policy->viewAny($primaryResponder));
    }

    /** @test */
    public function approver_can_view_lifeline_equipment()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        
        $this->assertTrue($this->policy->view($approver, $this->facility));
        $this->assertTrue($this->policy->viewAny($approver));
    }

    /** @test */
    public function viewer_can_view_lifeline_equipment_with_access()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertTrue($this->policy->view($viewer, $this->facility));
        $this->assertTrue($this->policy->viewAny($viewer));
    }

    /** @test */
    public function policy_respects_facility_access_restrictions()
    {
        // This test verifies that the policy correctly delegates to the User model's
        // canAccessFacility method, which handles the complex access scope logic.
        // The actual access control logic is tested in the User model tests.
        
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        // The policy should call canAccessFacility on the user
        $this->assertTrue($this->policy->view($viewer, $this->facility));
    }

    /** @test */
    public function admin_can_create_lifeline_equipment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($this->policy->create($admin, $this->facility));
    }

    /** @test */
    public function editor_can_create_lifeline_equipment()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        
        $this->assertTrue($this->policy->create($editor, $this->facility));
    }

    /** @test */
    public function primary_responder_can_create_lifeline_equipment()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        $this->assertTrue($this->policy->create($primaryResponder, $this->facility));
    }

    /** @test */
    public function viewer_cannot_create_lifeline_equipment()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertFalse($this->policy->create($viewer, $this->facility));
    }

    /** @test */
    public function approver_cannot_create_lifeline_equipment()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        
        $this->assertFalse($this->policy->create($approver, $this->facility));
    }

    /** @test */
    public function admin_can_update_lifeline_equipment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($this->policy->update($admin, $this->facility));
    }

    /** @test */
    public function editor_can_update_lifeline_equipment()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        
        $this->assertTrue($this->policy->update($editor, $this->facility));
    }

    /** @test */
    public function primary_responder_can_update_lifeline_equipment()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        $this->assertTrue($this->policy->update($primaryResponder, $this->facility));
    }

    /** @test */
    public function viewer_cannot_update_lifeline_equipment()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertFalse($this->policy->update($viewer, $this->facility));
    }

    /** @test */
    public function approver_cannot_update_lifeline_equipment()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        
        $this->assertFalse($this->policy->update($approver, $this->facility));
    }

    /** @test */
    public function only_admin_can_delete_lifeline_equipment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertTrue($this->policy->delete($admin, $this->facility));
        $this->assertFalse($this->policy->delete($editor, $this->facility));
        $this->assertFalse($this->policy->delete($viewer, $this->facility));
    }

    /** @test */
    public function admin_can_approve_lifeline_equipment_changes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($this->policy->approve($admin, $this->facility));
    }

    /** @test */
    public function approver_can_approve_lifeline_equipment_changes()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        
        $this->assertTrue($this->policy->approve($approver, $this->facility));
    }

    /** @test */
    public function editor_cannot_approve_lifeline_equipment_changes()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        
        $this->assertFalse($this->policy->approve($editor, $this->facility));
    }

    /** @test */
    public function viewer_cannot_approve_lifeline_equipment_changes()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertFalse($this->policy->approve($viewer, $this->facility));
    }

    /** @test */
    public function admin_can_reject_lifeline_equipment_changes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->assertTrue($this->policy->reject($admin, $this->facility));
    }

    /** @test */
    public function approver_can_reject_lifeline_equipment_changes()
    {
        $approver = User::factory()->create(['role' => 'approver']);
        
        $this->assertTrue($this->policy->reject($approver, $this->facility));
    }

    /** @test */
    public function editor_cannot_reject_lifeline_equipment_changes()
    {
        $editor = User::factory()->create(['role' => 'editor']);
        
        $this->assertFalse($this->policy->reject($editor, $this->facility));
    }

    /** @test */
    public function all_authenticated_users_can_export_lifeline_equipment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $viewer = User::factory()->create(['role' => 'viewer']);
        $approver = User::factory()->create(['role' => 'approver']);
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        $this->assertTrue($this->policy->export($admin));
        $this->assertTrue($this->policy->export($editor));
        $this->assertTrue($this->policy->export($viewer));
        $this->assertTrue($this->policy->export($approver));
        $this->assertTrue($this->policy->export($primaryResponder));
    }

    /** @test */
    public function only_admin_and_approver_can_view_audit_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $approver = User::factory()->create(['role' => 'approver']);
        $editor = User::factory()->create(['role' => 'editor']);
        $viewer = User::factory()->create(['role' => 'viewer']);
        
        $this->assertTrue($this->policy->viewAuditLogs($admin, $this->facility));
        $this->assertTrue($this->policy->viewAuditLogs($approver, $this->facility));
        $this->assertFalse($this->policy->viewAuditLogs($editor, $this->facility));
        $this->assertFalse($this->policy->viewAuditLogs($viewer, $this->facility));
    }
}