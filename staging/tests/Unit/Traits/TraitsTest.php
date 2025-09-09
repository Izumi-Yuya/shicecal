<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;
use Tests\Traits\CreatesTestFacilities;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TraitsTest extends TestCase
{
    use RefreshDatabase, CreatesTestUsers, CreatesTestFacilities;

    public function test_creates_test_users_trait_works()
    {
        // Test creating users with different roles
        $admin = $this->createAdminUser();
        $editor = $this->createEditorUser();
        $viewer = $this->createViewerUser();

        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('editor', $editor->role);
        $this->assertEquals('viewer', $viewer->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($editor->is_active);
        $this->assertTrue($viewer->is_active);
    }

    public function test_creates_test_users_with_departments()
    {
        $landUser = $this->createLandAffairsUser();
        $accountingUser = $this->createAccountingUser();

        $this->assertEquals('land_affairs', $landUser->department);
        $this->assertEquals('accounting', $accountingUser->department);
        $this->assertTrue($landUser->isLandAffairs());
        $this->assertTrue($accountingUser->isAccounting());
    }

    public function test_creates_complete_user_set()
    {
        $users = $this->createCompleteUserSet();

        $this->assertArrayHasKey('admin', $users);
        $this->assertArrayHasKey('editor', $users);
        $this->assertArrayHasKey('primary_responder', $users);
        $this->assertArrayHasKey('approver', $users);
        $this->assertArrayHasKey('viewer', $users);

        $this->assertEquals('admin', $users['admin']->role);
        $this->assertEquals('editor', $users['editor']->role);
        $this->assertEquals('primary_responder', $users['primary_responder']->role);
        $this->assertEquals('approver', $users['approver']->role);
        $this->assertEquals('viewer', $users['viewer']->role);
    }

    public function test_creates_test_facilities_trait_works()
    {
        // Test creating facilities with different statuses
        $approved = $this->createApprovedFacility();
        $draft = $this->createDraftFacility();
        $pending = $this->createPendingFacility();

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals('draft', $draft->status);
        $this->assertEquals('pending_approval', $pending->status);
        $this->assertTrue($approved->isApproved());
        $this->assertFalse($draft->isApproved());
    }

    public function test_creates_facility_with_land_info()
    {
        [$facility, $landInfo] = $this->createFacilityWithLandInfo();

        $this->assertNotNull($facility);
        $this->assertNotNull($landInfo);
        $this->assertEquals($facility->id, $landInfo->facility_id);
    }

    public function test_creates_complete_facility()
    {
        [$facility, $landInfo] = $this->createCompleteFacility();

        $this->assertEquals('テスト株式会社', $facility->company_name);
        $this->assertEquals('テスト施設', $facility->facility_name);
        $this->assertEquals('approved', $facility->status);
        $this->assertEquals('owned', $landInfo->ownership_type);
        $this->assertEquals(50000000, $landInfo->purchase_price);
    }

    public function test_acting_as_methods()
    {
        $admin = $this->actingAsAdmin();
        $this->assertEquals('admin', $admin->role);
        $this->assertEquals($admin->id, auth()->id());

        $editor = $this->actingAsEditor();
        $this->assertEquals('editor', $editor->role);
        $this->assertEquals($editor->id, auth()->id());
    }
}
