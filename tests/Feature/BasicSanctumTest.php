<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BasicSanctumTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sanctum_is_installed_and_configured()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'department' => 'IT',
            'access_scope' => [],
            'is_active' => true,
        ]);

        // Verify user has Sanctum traits
        $this->assertTrue(method_exists($user, 'createToken'));
        
        // Create a token
        $token = $user->createToken('test-token');
        $this->assertNotNull($token->plainTextToken);
        
        // Test API endpoint with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        
        $response->assertStatus(200);
        $response->assertJson(['email' => 'test@example.com']);
    }
}