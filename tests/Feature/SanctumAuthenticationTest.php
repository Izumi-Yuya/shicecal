<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SanctumAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'department' => 'IT',
            'access_scope' => [],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_create_personal_access_token()
    {
        // Create a personal access token
        $token = $this->user->createToken('test-token');
        
        $this->assertNotNull($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    /** @test */
    public function user_can_access_protected_api_route_with_valid_token()
    {
        // Create a personal access token
        $token = $this->user->createToken('test-token');
        
        // Make API request with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->id,
            'email' => $this->user->email,
            'name' => $this->user->name,
        ]);
    }

    /** @test */
    public function user_cannot_access_protected_api_route_without_token()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get('/api/user');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_access_protected_api_route_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ])->get('/api/user');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_revoke_personal_access_token()
    {
        // Create a personal access token
        $token = $this->user->createToken('test-token');
        
        // Verify token works
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        $response->assertStatus(200);
        
        // Revoke the token
        $this->user->tokens()->delete();
        
        // Verify token no longer works
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        $response->assertStatus(401);
        
        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => 'test-token',
        ]);
    }

    /** @test */
    public function user_can_have_multiple_tokens()
    {
        // Create multiple tokens
        $token1 = $this->user->createToken('token-1');
        $token2 = $this->user->createToken('token-2');
        
        $this->assertNotEquals($token1->plainTextToken, $token2->plainTextToken);
        
        // Both tokens should work
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        $response1->assertStatus(200);
        
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/user');
        $response2->assertStatus(200);
        
        // Verify both tokens exist in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'token-1',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'token-2',
        ]);
    }

    /** @test */
    public function sanctum_middleware_is_properly_configured()
    {
        // Test that Sanctum middleware is working by checking the auth:sanctum guard
        $this->actingAs($this->user, 'sanctum');
        
        $response = $this->get('/api/user');
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->user->id,
            'email' => $this->user->email,
        ]);
    }

    /** @test */
    public function user_model_has_sanctum_traits()
    {
        // Verify that the User model has the HasApiTokens trait
        $this->assertTrue(method_exists($this->user, 'createToken'));
        $this->assertTrue(method_exists($this->user, 'tokens'));
        $this->assertTrue(method_exists($this->user, 'currentAccessToken'));
    }

    /** @test */
    public function personal_access_tokens_table_exists()
    {
        // Verify the personal_access_tokens table exists and has correct structure
        $this->assertTrue(\Schema::hasTable('personal_access_tokens'));
        
        $columns = \Schema::getColumnListing('personal_access_tokens');
        $expectedColumns = [
            'id', 'tokenable_type', 'tokenable_id', 'name', 'token', 
            'abilities', 'last_used_at', 'expires_at', 'created_at', 'updated_at'
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $columns, "Column {$column} not found in personal_access_tokens table");
        }
    }

    /** @test */
    public function token_abilities_can_be_set_and_checked()
    {
        // Create token with specific abilities
        $token = $this->user->createToken('test-token', ['read', 'write']);
        
        // Check abilities
        $this->assertTrue($token->accessToken->can('read'));
        $this->assertTrue($token->accessToken->can('write'));
        $this->assertFalse($token->accessToken->can('delete'));
    }
}