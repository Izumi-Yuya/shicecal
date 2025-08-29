<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $editorUser;
    protected $approverUser;
    protected $viewerUser;
    protected $responderUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users with different roles
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'department' => 'IT',
            'access_scope' => ['all'],
            'is_active' => true,
        ]);

        $this->editorUser = User::create([
            'name' => 'Editor User',
            'email' => 'editor@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EDITOR,
            'department' => 'Operations',
            'access_scope' => ['tokyo'],
            'is_active' => true,
        ]);

        $this->approverUser = User::create([
            'name' => 'Approver User',
            'email' => 'approver@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_APPROVER,
            'department' => 'Management',
            'access_scope' => ['all'],
            'is_active' => true,
        ]);

        $this->viewerUser = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Sales',
            'access_scope' => ['osaka'],
            'is_active' => true,
        ]);

        $this->responderUser = User::create([
            'name' => 'Responder User',
            'email' => 'responder@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_PRIMARY_RESPONDER,
            'department' => 'Support',
            'access_scope' => ['tokyo'],
            'is_active' => true,
        ]);
    } 
   public function test_admin_can_access_home_page()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_editor_can_access_home_page()
    {
        $this->actingAs($this->editorUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_approver_can_access_home_page()
    {
        $this->actingAs($this->approverUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_viewer_can_access_home_page()
    {
        $this->actingAs($this->viewerUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_responder_can_access_home_page()
    {
        $this->actingAs($this->responderUser);
        
        $response = $this->get('/home');
        $response->assertStatus(200);
    }

    public function test_inactive_user_cannot_login()
    {
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Test',
            'access_scope' => ['tokyo'],
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_role_display_in_navigation()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/home');
        $response->assertSee($this->adminUser->name);
    }

    public function test_different_access_scopes_are_preserved()
    {
        $this->assertEquals(['all'], $this->adminUser->access_scope);
        $this->assertEquals(['tokyo'], $this->editorUser->access_scope);
        $this->assertEquals(['osaka'], $this->viewerUser->access_scope);
    }
}