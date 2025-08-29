<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->approver = User::factory()->create(['role' => 'approver']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);
    }

    /** @test */
    public function guest_can_see_basic_navigation()
    {
        $response = $this->get('/');
        
        $response->assertStatus(302); // Redirects to home
        
        $response = $this->get('/home');
        $response->assertStatus(200);
        $response->assertSee('施設管理システム');
        $response->assertSee('ログイン');
    }

    /** @test */
    public function authenticated_user_can_see_navigation_menu()
    {
        $response = $this->actingAs($this->viewer)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理');
        $response->assertSee('出力');
        $response->assertSee('コメント');
    }

    /** @test */
    public function admin_can_see_all_navigation_items()
    {
        $response = $this->actingAs($this->admin)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理');
        $response->assertSee('出力');
        $response->assertSee('コメント');
        $response->assertSee('承認待ち');
        $response->assertSee('年次確認');
        $response->assertSee('管理');
    }

    /** @test */
    public function editor_can_see_appropriate_navigation_items()
    {
        $response = $this->actingAs($this->editor)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理');
        $response->assertSee('出力');
        $response->assertSee('コメント');
        $response->assertSee('年次確認');
        $response->assertDontSee('管理');
    }

    /** @test */
    public function approver_can_see_approval_navigation()
    {
        $response = $this->actingAs($this->approver)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理');
        $response->assertSee('出力');
        $response->assertSee('コメント');
        $response->assertSee('承認待ち');
        $response->assertDontSee('管理');
        $response->assertDontSee('年次確認');
    }

    /** @test */
    public function viewer_has_limited_navigation_access()
    {
        $response = $this->actingAs($this->viewer)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理');
        $response->assertSee('出力');
        $response->assertSee('コメント');
        $response->assertDontSee('承認待ち');
        $response->assertDontSee('年次確認');
        $response->assertDontSee('管理');
    }

    /** @test */
    public function navigation_links_are_accessible()
    {
        // Test facility management links
        $this->actingAs($this->admin)
            ->get(route('facilities.index'))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('facilities.create'))
            ->assertStatus(200);

        // Test export links
        $this->actingAs($this->admin)
            ->get(route('export.csv'))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('export.pdf'))
            ->assertStatus(200);

        // Test admin links
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('system.settings'))
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get(route('logs.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_admin_routes()
    {
        // Editor should not access admin routes
        $this->actingAs($this->editor)
            ->get(route('users.index'))
            ->assertStatus(403);

        // Viewer should not access admin routes
        $this->actingAs($this->viewer)
            ->get(route('system.settings'))
            ->assertStatus(403);
    }

    /** @test */
    public function navigation_shows_correct_badges()
    {
        // Create some test data for badges
        $facility = \App\Models\Facility::factory()->create([
            'status' => 'pending_approval'
        ]);

        $comment = \App\Models\Comment::factory()->create([
            'status' => 'pending',
            'assigned_to' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)->get('/home');
        
        $response->assertStatus(200);
        // Should show badge for pending approvals
        $response->assertSee('badge bg-warning');
        // Should show badge for pending comments
        $response->assertSee('badge bg-danger');
    }
}