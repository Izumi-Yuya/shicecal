<?php

namespace Tests\Unit\Models;

use App\Models\Facility;
use App\Models\FacilityComment;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test facility relationships.
     */
    public function test_facility_relationships()
    {
        $creator = User::factory()->create();
        $updater = User::factory()->create();
        $approver = User::factory()->create();

        $facility = Facility::factory()->create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
            'approved_by' => $approver->id,
        ]);

        // Test creator relationship
        $this->assertEquals($creator->id, $facility->creator->id);

        // Test updater relationship
        $this->assertEquals($updater->id, $facility->updater->id);

        // Test approver relationship
        $this->assertEquals($approver->id, $facility->approver->id);

        // Test comments relationship
        $comment = FacilityComment::factory()->create(['facility_id' => $facility->id]);
        $this->assertTrue($facility->comments->contains($comment));

        // Test maintenance histories relationship
        $maintenance = MaintenanceHistory::factory()->create(['facility_id' => $facility->id]);
        $this->assertTrue($facility->maintenanceHistories->contains($maintenance));
    }

    /**
     * Test facility status checking methods.
     */
    public function test_facility_status_checking_methods()
    {
        // Test approved facility
        $approvedFacility = Facility::factory()->create(['status' => 'approved']);
        $this->assertTrue($approvedFacility->isApproved());

        // Test non-approved facility
        $draftFacility = Facility::factory()->create(['status' => 'draft']);
        $this->assertFalse($draftFacility->isApproved());

        $pendingFacility = Facility::factory()->create(['status' => 'pending_approval']);
        $this->assertFalse($pendingFacility->isApproved());
    }

    /**
     * Test facility approved scope.
     */
    public function test_facility_approved_scope()
    {
        // Create facilities with different statuses
        $approvedFacility1 = Facility::factory()->create(['status' => 'approved']);
        $approvedFacility2 = Facility::factory()->create(['status' => 'approved']);
        $draftFacility = Facility::factory()->create(['status' => 'draft']);
        $pendingFacility = Facility::factory()->create(['status' => 'pending_approval']);

        // Test approved scope
        $approvedFacilities = Facility::approved()->get();

        $this->assertCount(2, $approvedFacilities);
        $this->assertTrue($approvedFacilities->contains($approvedFacility1));
        $this->assertTrue($approvedFacilities->contains($approvedFacility2));
        $this->assertFalse($approvedFacilities->contains($draftFacility));
        $this->assertFalse($approvedFacilities->contains($pendingFacility));
    }

    /**
     * Test facility fillable attributes.
     */
    public function test_facility_fillable_attributes()
    {
        $user = User::factory()->create();

        $facilityData = [
            'company_name' => 'Test Company',
            'office_code' => 'TC001',
            'designation_number' => 'DN123456',
            'facility_name' => 'Test Facility',
            'postal_code' => '123-4567',
            'address' => 'Tokyo, Japan',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $facility = Facility::create($facilityData);

        $this->assertEquals('Test Company', $facility->company_name);
        $this->assertEquals('TC001', $facility->office_code);
        $this->assertEquals('DN123456', $facility->designation_number);
        $this->assertEquals('Test Facility', $facility->facility_name);
        $this->assertEquals('123-4567', $facility->postal_code);
        $this->assertEquals('Tokyo, Japan', $facility->address);
        $this->assertEquals('03-1234-5678', $facility->phone_number);
        $this->assertEquals('03-1234-5679', $facility->fax_number);
        $this->assertEquals('approved', $facility->status);
        $this->assertEquals($user->id, $facility->approved_by);
        $this->assertEquals($user->id, $facility->created_by);
        $this->assertEquals($user->id, $facility->updated_by);
    }

    /**
     * Test facility casts.
     */
    public function test_facility_casts()
    {
        $approvedAt = now();
        $facility = Facility::factory()->create([
            'approved_at' => $approvedAt,
        ]);

        // Test approved_at is cast to datetime
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $facility->approved_at);
        $this->assertEquals($approvedAt->format('Y-m-d H:i:s'), $facility->approved_at->format('Y-m-d H:i:s'));
    }
}
