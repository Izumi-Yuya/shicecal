<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
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

        $this->viewerUser = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Sales',
            'access_scope' => ['osaka'],
            'is_active' => true,
        ]);

        $this->inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Test',
            'access_scope' => ['tokyo'],
            'is_active' => false,
        ]);

        // Define test routes
        Route::get('/test-admin', function () {
            return 'Admin access granted';
        })->middleware(['auth', 'role:admin']);

        Route::get('/test-editor-admin', function () {
            return 'Editor or Admin access granted';
        })->middleware(['auth', 'role:editor,admin']);

        Route::get('/test-authenticated', function () {
            return 'Authenticated access granted';
        })->middleware(['auth', 'role']);
    } 
   public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/test-admin');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_route()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/test-admin');
        $response->assertStatus(200);
        $response->assertSee('Admin access granted');
    }

    public function test_non_admin_cannot_access_admin_route()
    {
        $this->actingAs($this->editorUser);
        
        $response = $this->get('/test-admin');
        $response->assertStatus(403);
    }

    public function test_editor_can_access_editor_admin_route()
    {
        $this->actingAs($this->editorUser);
        
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(200);
        $response->assertSee('Editor or Admin access granted');
    }

    public function test_admin_can_access_editor_admin_route()
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(200);
        $response->assertSee('Editor or Admin access granted');
    }

    public function test_viewer_cannot_access_editor_admin_route()
    {
        $this->actingAs($this->viewerUser);
        
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_access_authenticated_route()
    {
        $this->actingAs($this->viewerUser);
        
        $response = $this->get('/test-authenticated');
        $response->assertStatus(200);
        $response->assertSee('Authenticated access granted');
    }

    public function test_inactive_user_is_logged_out_and_redirected()
    {
        $this->actingAs($this->inactiveUser);
        
        $response = $this->get('/test-authenticated');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_middleware_handles_multiple_roles()
    {
        // Test that both editor and admin can access
        $this->actingAs($this->editorUser);
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(200);

        $this->actingAs($this->adminUser);
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(200);

        // Test that viewer cannot access
        $this->actingAs($this->viewerUser);
        $response = $this->get('/test-editor-admin');
        $response->assertStatus(403);
    }
}