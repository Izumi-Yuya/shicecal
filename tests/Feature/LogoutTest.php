<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Test Department',
            'access_scope' => ['tokyo'],
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_logout()
    {
        // Login the user first
        $this->actingAs($this->testUser);
        
        // Verify user is authenticated
        $this->assertAuthenticated();
        
        // Perform logout
        $response = $this->post('/logout');
        
        // Should redirect to login page
        $response->assertRedirect('/login');
        
        // User should no longer be authenticated
        $this->assertGuest();
    }

    public function test_logout_invalidates_session()
    {
        // Login the user first
        $this->actingAs($this->testUser);
        
        // Store session ID before logout
        $sessionId = session()->getId();
        
        // Perform logout
        $response = $this->post('/logout');
        
        // Session should be invalidated (new session ID)
        $this->assertNotEquals($sessionId, session()->getId());
    }

    public function test_logout_regenerates_csrf_token()
    {
        // Login the user first
        $this->actingAs($this->testUser);
        
        // Get CSRF token before logout
        $originalToken = csrf_token();
        
        // Perform logout
        $response = $this->post('/logout');
        
        // CSRF token should be regenerated
        $this->assertNotEquals($originalToken, csrf_token());
    }

    public function test_guest_user_logout_redirects_to_login()
    {
        // Ensure user is not authenticated
        $this->assertGuest();
        
        // Try to logout as guest
        $response = $this->post('/logout');
        
        // Should still redirect to login
        $response->assertRedirect('/login');
        
        // Should still be guest
        $this->assertGuest();
    }

    public function test_logout_clears_user_session_data()
    {
        // Login the user first
        $this->actingAs($this->testUser);
        
        // Add some session data
        session(['test_data' => 'some_value']);
        $this->assertEquals('some_value', session('test_data'));
        
        // Perform logout
        $response = $this->post('/logout');
        
        // Session data should be cleared
        $this->assertNull(session('test_data'));
    }
}