<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for testing
        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_user_index()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertSee('ユーザー管理');
    }

    public function test_non_admin_cannot_access_user_management()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
                         ->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_user_creation_form()
    {
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
        $response->assertSee('新規ユーザー登録');
    }

    public function test_admin_can_create_new_user()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'role' => User::ROLE_EDITOR,
            'department' => '総務部',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'department' => $userData['department'],
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_user_details()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_admin_can_view_user_edit_form()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.edit', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_admin_can_update_user()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => User::ROLE_EDITOR,
            'department' => '営業部',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $updateData['name'],
            'email' => $updateData['email'],
            'role' => $updateData['role'],
            'department' => $updateData['department'],
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_user()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
                         ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // User should be soft deleted (is_active = false)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_user_search_functionality()
    {
        // Create test users
        $user1 = User::factory()->create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'role' => User::ROLE_EDITOR,
            'is_active' => true,
        ]);

        $user2 = User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'role' => User::ROLE_VIEWER,
            'is_active' => false,
        ]);

        // Test email search
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.index', ['email' => 'test1']));

        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertDontSee($user2->name);

        // Test role filter
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.index', ['role' => User::ROLE_EDITOR]));

        $response->assertStatus(200);
        $response->assertSee($user1->name);

        // Test status filter
        $response = $this->actingAs($this->adminUser)
                         ->get(route('admin.users.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertSee($user2->name);
    }

    public function test_user_access_scope_handling()
    {
        $accessScope = [
            'regions' => ['東京', '大阪'],
            'departments' => ['営業部']
        ];

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'role' => User::ROLE_VIEWER,
            'department' => '営業部',
            'access_scope' => json_encode($accessScope),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)
                         ->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', $userData['email'])->first();
        $this->assertEquals($accessScope, $user->access_scope);
    }

    public function test_last_login_tracking()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
            'last_login_at' => null,
        ]);

        // Verify user has no last login initially
        $this->assertNull($user->last_login_at);

        // Simulate login
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));

        // Verify last login was updated
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertTrue($user->last_login_at->isToday());
    }
}