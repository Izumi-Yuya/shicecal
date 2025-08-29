<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the home page loads successfully
     */
    public function test_home_page_loads_successfully()
    {
        $response = $this->get('/');
        
        $response->assertStatus(302); // Should redirect to home
        $response->assertRedirect('/home');
    }

    /**
     * Test that the home page displays correctly for authenticated users
     */
    public function test_home_page_displays_for_authenticated_users()
    {
        // Create a test user
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertSee('施設管理システムへようこそ');
        $response->assertSee('ダッシュボード');
    }

    /**
     * Test that the layout includes Bootstrap CSS
     */
    public function test_layout_includes_bootstrap_css()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('bootstrap@5.3.0/dist/css/bootstrap.min.css');
        $response->assertSee('bootstrap-icons@1.10.0/font/bootstrap-icons.css');
    }

    /**
     * Test that navigation menu displays correctly
     */
    public function test_navigation_menu_displays_correctly()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理システム');
        $response->assertSee('施設一覧');
        $response->assertSee('施設登録');
        $response->assertSee('管理');
    }

    /**
     * Test that role-based navigation works
     */
    public function test_role_based_navigation_for_viewer()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設一覧');
        $response->assertDontSee('施設登録'); // Viewers shouldn't see create button
        $response->assertDontSee('管理'); // Viewers shouldn't see admin menu
    }

    /**
     * Test that unauthenticated users see login prompt
     */
    public function test_unauthenticated_users_see_login_prompt()
    {
        $response = $this->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('ログインが必要です');
        $response->assertSee('ログイン');
    }
}