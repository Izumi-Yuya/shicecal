<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLandPermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function editor_can_edit_land_info()
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'department' => 'any_department',
        ]);

        $this->assertTrue($user->canEditLandInfo());
        $this->assertTrue($user->canEditLandBasicInfo());
        $this->assertTrue($user->canEditLandFinancialInfo());
        $this->assertTrue($user->canEditLandManagementInfo());
        $this->assertTrue($user->canEditLandDocuments());
    }

    /** @test */
    public function admin_can_edit_land_info()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'department' => 'any_department',
        ]);

        $this->assertTrue($user->canEditLandInfo());
        $this->assertTrue($user->canEditLandBasicInfo());
        $this->assertTrue($user->canEditLandFinancialInfo());
        $this->assertTrue($user->canEditLandManagementInfo());
        $this->assertTrue($user->canEditLandDocuments());
    }

    /** @test */
    public function primary_responder_can_edit_land_info()
    {
        $user = User::factory()->create([
            'role' => 'primary_responder',
            'department' => 'any_department',
        ]);

        $this->assertTrue($user->canEditLandInfo());
        $this->assertTrue($user->canEditLandBasicInfo());
        $this->assertTrue($user->canEditLandFinancialInfo());
        $this->assertTrue($user->canEditLandManagementInfo());
        $this->assertTrue($user->canEditLandDocuments());
    }

    /** @test */
    public function non_editor_roles_cannot_edit_land_info()
    {
        $roles = ['viewer', 'approver'];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'department' => 'any_department',
            ]);

            $this->assertFalse($user->canEditLandInfo(), "Role {$role} should not be able to edit land info");
            $this->assertFalse($user->canEditLandBasicInfo());
            $this->assertFalse($user->canEditLandFinancialInfo());
            $this->assertFalse($user->canEditLandManagementInfo());
            $this->assertFalse($user->canEditLandDocuments());
        }
    }

    /** @test */
    public function department_methods_still_work_for_backward_compatibility()
    {
        $user = User::factory()->create([
            'department' => 'land_affairs,accounting',
        ]);

        $this->assertTrue($user->hasMultipleDepartments());
        $this->assertEquals(['land_affairs', 'accounting'], $user->getDepartments());
        $this->assertTrue($user->isInDepartment('land_affairs'));
        $this->assertTrue($user->isInDepartment('accounting'));
        $this->assertFalse($user->isInDepartment('construction_planning'));
    }
}
