<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\ExportFavorite;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user role checking methods.
     */
    public function test_user_role_checking_methods()
    {
        // Test admin role
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isEditor());
        $this->assertFalse($admin->isPrimaryResponder());
        $this->assertFalse($admin->isApprover());
        $this->assertFalse($admin->isViewer());

        // Test editor role
        $editor = User::factory()->create(['role' => 'editor']);
        $this->assertFalse($editor->isAdmin());
        $this->assertTrue($editor->isEditor());
        $this->assertFalse($editor->isPrimaryResponder());
        $this->assertFalse($editor->isApprover());
        $this->assertFalse($editor->isViewer());

        // Test primary_responder role
        $responder = User::factory()->create(['role' => 'primary_responder']);
        $this->assertFalse($responder->isAdmin());
        $this->assertFalse($responder->isEditor());
        $this->assertTrue($responder->isPrimaryResponder());
        $this->assertFalse($responder->isApprover());
        $this->assertFalse($responder->isViewer());

        // Test approver role
        $approver = User::factory()->create(['role' => 'approver']);
        $this->assertFalse($approver->isAdmin());
        $this->assertFalse($approver->isEditor());
        $this->assertFalse($approver->isPrimaryResponder());
        $this->assertTrue($approver->isApprover());
        $this->assertFalse($approver->isViewer());

        // Test viewer role
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->assertFalse($viewer->isAdmin());
        $this->assertFalse($viewer->isEditor());
        $this->assertFalse($viewer->isPrimaryResponder());
        $this->assertFalse($viewer->isApprover());
        $this->assertTrue($viewer->isViewer());
    }

    /**
     * Test user relationships.
     */
    public function test_user_relationships()
    {
        $user = User::factory()->create();

        // Test posted comments relationship
        $postedComment = Comment::factory()->create(['posted_by' => $user->id]);
        $this->assertTrue($user->postedComments->contains($postedComment));

        // Test assigned comments relationship
        $assignedComment = Comment::factory()->create(['assigned_to' => $user->id]);
        $this->assertTrue($user->assignedComments->contains($assignedComment));

        // Test export favorites relationship
        $favorite = ExportFavorite::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->exportFavorites->contains($favorite));

        // Test notifications relationship
        $notification = Notification::factory()->create(['user_id' => $user->id]);
        $this->assertTrue($user->notifications->contains($notification));
    }

    /**
     * Test user fillable attributes.
     */
    public function test_user_fillable_attributes()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'editor',
            'department' => 'IT Department',
            'access_scope' => ['region' => 'tokyo'],
            'is_active' => true,
        ];

        $user = User::create($userData);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('editor', $user->role);
        $this->assertEquals('IT Department', $user->department);
        $this->assertEquals(['region' => 'tokyo'], $user->access_scope);
        $this->assertTrue($user->is_active);
    }

    /**
     * Test user casts.
     */
    public function test_user_casts()
    {
        $user = User::factory()->create([
            'access_scope' => ['region' => 'tokyo', 'departments' => ['IT', 'HR']],
            'is_active' => true,
        ]);

        // Test access_scope is cast to array
        $this->assertIsArray($user->access_scope);
        $this->assertEquals(['region' => 'tokyo', 'departments' => ['IT', 'HR']], $user->access_scope);

        // Test is_active is cast to boolean
        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    /**
     * Test user hidden attributes.
     */
    public function test_user_hidden_attributes()
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }
}
