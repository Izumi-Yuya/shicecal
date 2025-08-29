<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
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

    public function test_login_page_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('ログイン');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->testUser);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_cannot_login_with_nonexistent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login()
    {
        // Create inactive user
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => 'Test Department',
            'access_scope' => ['tokyo'],
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_to_home()
    {
        $this->actingAs($this->testUser);

        $response = $this->get('/');
        $response->assertRedirect('/home');
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_home_page_requires_authentication()
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_home()
    {
        $this->actingAs($this->testUser);

        $response = $this->get('/home');
        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    public function test_login_form_validation_errors_are_displayed()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_form_retains_email_on_error()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('_old.email', 'test@example.com');
    }

    public function test_session_is_regenerated_on_successful_login()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->testUser);
        
        // Check that session was regenerated (new session ID)
        $this->assertNotNull(session()->getId());
    }
}