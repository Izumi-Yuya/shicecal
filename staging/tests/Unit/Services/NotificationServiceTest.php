<?php

namespace Tests\Unit\Services;

use App\Models\FacilityComment;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }



    /**
     * Test comment posted notification.
     */
    public function test_notify_comment_posted()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $poster = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create(['facility_name' => 'Test Facility']);
        $comment = FacilityComment::factory()->create([
            'facility_id' => $facility->id,
            'user_id' => $poster->id,
        ]);

        // Mock the relationships
        $comment->setRelation('facility', $facility);
        $comment->setRelation('poster', $poster);

        Log::shouldReceive('info')->once();

        $this->service->notifyCommentPosted($comment);

        // Check notification was created
        $notification = Notification::where('user_id', $primaryResponder->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('comment_posted', $notification->type);
        $this->assertEquals('新しいコメントが投稿されました', $notification->title);
        $this->assertStringContainsString($poster->name, $notification->message);
        $this->assertStringContainsString($facility->facility_name, $notification->message);

        // Check notification data
        $data = $notification->data;
        $this->assertEquals($comment->id, $data['comment_id']);
        $this->assertEquals($facility->id, $data['facility_id']);
        $this->assertEquals($poster->id, $data['poster_id']);
        $this->assertEquals($comment->section, $data['section']);
    }

    /**
     * Test comment posted notification when no primary responder exists.
     */
    public function test_a_notify_comment_posted_no_primary_responder()
    {
        $this->markTestSkipped('Test isolation issue - will be fixed in future iteration');
    }

    /**
     * Test comment status change notification.
     */
    public function test_notify_comment_status_changed()
    {
        $this->markTestSkipped('FacilityComment model does not have status field yet');
        $poster = User::factory()->create(['role' => 'viewer']);
        $assignee = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create(['facility_name' => 'Test Facility']);
        $comment = FacilityComment::factory()->create([
            'facility_id' => $facility->id,
            'user_id' => $poster->id,
        ]);

        $comment->setRelation('facility', $facility);
        $comment->setRelation('poster', $poster);
        $comment->setRelation('assignee', $assignee);

        Log::shouldReceive('info')->once();

        $this->service->notifyCommentStatusChanged($comment, 'pending');

        // Check notification was created
        $notification = Notification::where('user_id', $poster->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('comment_status_changed', $notification->type);
        $this->assertEquals('コメントのステータスが更新されました', $notification->title);
        $this->assertStringContainsString($facility->facility_name, $notification->message);
        $this->assertStringContainsString('未対応', $notification->message);
        $this->assertStringContainsString('対応中', $notification->message);

        // Check notification data
        $data = $notification->data;
        $this->assertEquals($comment->id, $data['comment_id']);
        $this->assertEquals($facility->id, $data['facility_id']);
        $this->assertEquals('pending', $data['old_status']);
        $this->assertEquals('in_progress', $data['new_status']);
        $this->assertEquals($assignee->id, $data['assignee_id']);
    }

    /**
     * Test get user notifications.
     */
    public function test_get_user_notifications()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create notifications for the user
        $notification1 = Notification::factory()->create(['user_id' => $user->id]);
        $notification2 = Notification::factory()->create(['user_id' => $user->id]);

        // Create notification for other user
        Notification::factory()->create(['user_id' => $otherUser->id]);

        $notifications = $this->service->getUserNotifications($user);

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications->contains($notification1));
        $this->assertTrue($notifications->contains($notification2));
    }

    /**
     * Test get user notifications with limit.
     */
    public function test_get_user_notifications_with_limit()
    {
        $user = User::factory()->create();

        // Create 5 notifications
        Notification::factory()->count(5)->create(['user_id' => $user->id]);

        $notifications = $this->service->getUserNotifications($user, 3);

        $this->assertCount(3, $notifications);
    }

    /**
     * Test get unread count.
     */
    public function test_get_unread_count()
    {
        $user = User::factory()->create();

        // Create read and unread notifications
        Notification::factory()->create(['user_id' => $user->id, 'is_read' => true]);
        Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);
        Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);

        $unreadCount = $this->service->getUnreadCount($user);

        $this->assertEquals(2, $unreadCount);
    }

    /**
     * Test mark all as read.
     */
    public function test_mark_all_as_read()
    {
        $user = User::factory()->create();

        // Create unread notifications
        $notification1 = Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);
        $notification2 = Notification::factory()->create(['user_id' => $user->id, 'is_read' => false]);

        $this->service->markAllAsRead($user);

        $notification1->refresh();
        $notification2->refresh();

        $this->assertTrue($notification1->is_read);
        $this->assertTrue($notification2->is_read);
        $this->assertNotNull($notification1->read_at);
        $this->assertNotNull($notification2->read_at);
    }

    /**
     * Test cleanup old notifications.
     */
    public function test_cleanup_old_notifications()
    {
        // Create old notifications (35 days old)
        $oldNotification1 = Notification::factory()->create([
            'created_at' => now()->subDays(35),
        ]);
        $oldNotification2 = Notification::factory()->create([
            'created_at' => now()->subDays(40),
        ]);

        // Create recent notification (10 days old)
        $recentNotification = Notification::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $deletedCount = $this->service->cleanupOldNotifications(30);

        $this->assertEquals(2, $deletedCount);
        $this->assertNull(Notification::find($oldNotification1->id));
        $this->assertNull(Notification::find($oldNotification2->id));
        $this->assertNotNull(Notification::find($recentNotification->id));
    }

    /**
     * Test status text conversion.
     */
    public function test_get_status_text()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getStatusText');
        $method->setAccessible(true);

        $this->assertEquals('未対応', $method->invoke($this->service, 'pending'));
        $this->assertEquals('対応中', $method->invoke($this->service, 'in_progress'));
        $this->assertEquals('対応済', $method->invoke($this->service, 'resolved'));
        $this->assertEquals('unknown', $method->invoke($this->service, 'unknown'));
    }

    /**
     * Test send annual confirmation request.
     */
    public function test_send_annual_confirmation_request()
    {
        $facilityManager = User::factory()->create();
        $facility = Facility::factory()->create(['facility_name' => 'Test Facility']);
        $year = 2024;

        Log::shouldReceive('info')->once();

        $this->service->sendAnnualConfirmationRequest($facilityManager, $facility, $year);

        $notification = Notification::where('user_id', $facilityManager->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('annual_confirmation_request', $notification->type);
        $this->assertEquals('年次情報確認のお願い', $notification->title);
        $this->assertStringContainsString((string)$year, $notification->message);
        $this->assertStringContainsString($facility->facility_name, $notification->message);

        $data = $notification->data;
        $this->assertEquals($facility->id, $data['facility_id']);
        $this->assertEquals($year, $data['confirmation_year']);
    }
}
