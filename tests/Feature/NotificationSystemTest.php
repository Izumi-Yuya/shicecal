<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
    }

    public function test_notification_created_when_comment_posted()
    {
        // Create test users
        $user = User::factory()->create(['role' => 'viewer']);
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        // Create test facility
        $facility = Facility::factory()->create();

        // Act as the user
        $this->actingAs($user);

        // Post a comment
        $response = $this->post(route('comments.store'), [
            'facility_id' => $facility->id,
            'field_name' => 'facility_name',
            'content' => 'This is a test comment.',
        ]);

        // Assert notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $primaryResponder->id,
            'type' => 'comment_posted',
            'title' => '新しいコメントが投稿されました',
        ]);

        $notification = Notification::where('user_id', $primaryResponder->id)->first();
        $this->assertStringContainsString($user->name, $notification->message);
        $this->assertStringContainsString($facility->facility_name, $notification->message);
    }

    public function test_notification_created_when_comment_status_changed()
    {
        // Create test users
        $poster = User::factory()->create(['role' => 'viewer']);
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        
        // Create test facility and comment
        $facility = Facility::factory()->create();
        $comment = Comment::factory()->create([
            'facility_id' => $facility->id,
            'posted_by' => $poster->id,
            'assigned_to' => $primaryResponder->id,
            'status' => 'pending',
        ]);

        // Act as primary responder
        $this->actingAs($primaryResponder);

        // Update comment status
        $response = $this->patch(route('comments.update-status', $comment), [
            'status' => 'in_progress',
        ]);

        // Assert notification was created for the poster
        $this->assertDatabaseHas('notifications', [
            'user_id' => $poster->id,
            'type' => 'comment_status_changed',
            'title' => 'コメントのステータスが更新されました',
        ]);

        $notification = Notification::where('user_id', $poster->id)->first();
        $this->assertStringContainsString('未対応', $notification->message);
        $this->assertStringContainsString('対応中', $notification->message);
    }

    public function test_user_can_view_notifications()
    {
        $user = User::factory()->create();
        
        // Create some notifications
        $notifications = Notification::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        
        $viewNotifications = $response->viewData('notifications');
        $this->assertEquals(3, $viewNotifications->count());
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('notifications.mark-as-read', $notification));

        $response->assertJson(['success' => true]);
        
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $notification = Notification::factory()->create([
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1);

        $response = $this->post(route('notifications.mark-as-read', $notification));

        $response->assertStatus(403);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        
        // Create unread notifications
        $notifications = Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('notifications.mark-all-read'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check all notifications are marked as read
        foreach ($notifications as $notification) {
            $notification->refresh();
            $this->assertTrue($notification->is_read);
        }
    }

    public function test_unread_count_api_returns_correct_count()
    {
        $user = User::factory()->create();
        
        // Create mix of read and unread notifications
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);
        
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('notifications.unread-count'));

        $response->assertJson(['count' => 2]);
    }

    public function test_notification_service_methods()
    {
        $notificationService = new NotificationService();
        
        $user = User::factory()->create();
        
        // Create notifications
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);
        
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => true,
        ]);

        // Test unread count
        $unreadCount = $notificationService->getUnreadCount($user);
        $this->assertEquals(2, $unreadCount);

        // Test get user notifications
        $notifications = $notificationService->getUserNotifications($user, 10);
        $this->assertEquals(5, $notifications->count());

        // Test mark all as read
        $notificationService->markAllAsRead($user);
        $unreadCountAfter = $notificationService->getUnreadCount($user);
        $this->assertEquals(0, $unreadCountAfter);
    }
}
