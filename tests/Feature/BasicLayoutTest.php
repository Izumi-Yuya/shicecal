<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class BasicLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the home page redirects correctly
     */
    public function test_root_redirects_to_home()
    {
        $response = $this->get('/');
        $response->assertRedirect('/home');
    }

    /**
     * Test that unauthenticated users see login message
     */
    public function test_unauthenticated_home_shows_login_message()
    {
        $response = $this->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('ログインが必要です');
        $response->assertSee('システムを利用するにはログインしてください');
    }

    /**
     * Test that authenticated admin sees dashboard
     */
    public function test_authenticated_admin_sees_dashboard()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => 'テスト管理者',
        ]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('ダッシュボード');
        $response->assertSee('施設管理システムへようこそ');
        $response->assertSee('テスト管理者'); // User name in navigation
    }

    /**
     * Test that layout includes Bootstrap
     */
    public function test_layout_includes_bootstrap()
    {
        $response = $this->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('bootstrap@5.3.0');
        $response->assertSee('bootstrap-icons');
    }

    /**
     * Test navigation for different roles
     */
    public function test_admin_navigation()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設一覧');
        $response->assertSee('施設登録');
        $response->assertSee('管理'); // Admin dropdown
    }

    public function test_viewer_navigation()
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設一覧');
        $response->assertDontSee('施設登録'); // Viewers can't create
        $response->assertDontSee('管理'); // Viewers can't access admin
    }
}