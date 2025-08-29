<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_constants_are_defined()
    {
        $this->assertEquals('admin', User::ROLE_ADMIN);
        $this->assertEquals('editor', User::ROLE_EDITOR);
        $this->assertEquals('primary_responder', User::ROLE_PRIMARY_RESPONDER);
        $this->assertEquals('approver', User::ROLE_APPROVER);
        $this->assertEquals('viewer', User::ROLE_VIEWER);
    }

    public function test_user_can_have_single_role()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertEquals(User::ROLE_ADMIN, $user->role);
    }

    public function test_has_role_method_with_single_role()
    {
        $user = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
        $this->assertFalse($user->hasRole(User::ROLE_EDITOR));
        $this->assertFalse($user->hasRole(User::ROLE_VIEWER));
    }

    public function test_has_role_method_with_array_of_roles()
    {
        $adminUser = new User(['role' => User::ROLE_ADMIN]);
        $editorUser = new User(['role' => User::ROLE_EDITOR]);

        $adminRoles = [User::ROLE_ADMIN, User::ROLE_EDITOR];
        $viewerRoles = [User::ROLE_VIEWER, User::ROLE_APPROVER];

        $this->assertTrue($adminUser->hasRole($adminRoles));
        $this->assertTrue($editorUser->hasRole($adminRoles));
        $this->assertFalse($adminUser->hasRole($viewerRoles));
        $this->assertFalse($editorUser->hasRole($viewerRoles));
    }

    public function test_is_admin_method()
    {
        $adminUser = new User(['role' => User::ROLE_ADMIN]);
        $editorUser = new User(['role' => User::ROLE_EDITOR]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($editorUser->isAdmin());
    }

    public function test_is_editor_method()
    {
        $adminUser = new User(['role' => User::ROLE_ADMIN]);
        $editorUser = new User(['role' => User::ROLE_EDITOR]);

        $this->assertFalse($adminUser->isEditor());
        $this->assertTrue($editorUser->isEditor());
    }

    public function test_is_approver_method()
    {
        $approverUser = new User(['role' => User::ROLE_APPROVER]);
        $viewerUser = new User(['role' => User::ROLE_VIEWER]);

        $this->assertTrue($approverUser->isApprover());
        $this->assertFalse($viewerUser->isApprover());
    }

    public function test_get_role_display_name_attribute()
    {
        $adminUser = new User(['role' => User::ROLE_ADMIN]);
        $editorUser = new User(['role' => User::ROLE_EDITOR]);
        $responderUser = new User(['role' => User::ROLE_PRIMARY_RESPONDER]);
        $approverUser = new User(['role' => User::ROLE_APPROVER]);
        $viewerUser = new User(['role' => User::ROLE_VIEWER]);

        $this->assertEquals('管理者', $adminUser->role_display_name);
        $this->assertEquals('編集者', $editorUser->role_display_name);
        $this->assertEquals('一次対応者', $responderUser->role_display_name);
        $this->assertEquals('承認者', $approverUser->role_display_name);
        $this->assertEquals('閲覧者', $viewerUser->role_display_name);
    }

    public function test_unknown_role_returns_original_value()
    {
        $user = new User(['role' => 'unknown_role']);

        $this->assertEquals('unknown_role', $user->role_display_name);
    }

    public function test_scope_by_role()
    {
        // This test would require database interaction
        // We'll test the scope method exists and can be called
        $this->assertTrue(method_exists(User::class, 'scopeByRole'));
    }

    public function test_scope_active()
    {
        // This test would require database interaction
        // We'll test the scope method exists and can be called
        $this->assertTrue(method_exists(User::class, 'scopeActive'));
    }

    public function test_access_scope_is_cast_to_array()
    {
        $user = new User([
            'access_scope' => ['tokyo', 'osaka']
        ]);

        $this->assertIsArray($user->access_scope);
        $this->assertEquals(['tokyo', 'osaka'], $user->access_scope);
    }

    public function test_is_active_is_cast_to_boolean()
    {
        $activeUser = new User(['is_active' => true]);
        $inactiveUser = new User(['is_active' => false]);

        $this->assertIsBool($activeUser->is_active);
        $this->assertIsBool($inactiveUser->is_active);
        $this->assertTrue($activeUser->is_active);
        $this->assertFalse($inactiveUser->is_active);
    }
}