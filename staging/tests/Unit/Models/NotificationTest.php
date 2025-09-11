<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test notification relationships.
     */
    public function test_notification_relationships()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        // Test user relationship
        $this->assertEquals($user->id, $notification->user->id);
    }

    /**
     * Test notification fillable attributes.
     */
    public function test_notification_fillable_attributes()
    {
        $user = User::factory()->create();
        $readAt = now();
        $emailSentAt = now()->addMinutes(5);

        $notificationData = [
            'user_id' => $user->id,
            'type' => 'comment_posted',
            'title' => 'New Comment',
            'message' => 'A new comment has been posted.',
            'data' => ['comment_id' => 123, 'facility_id' => 456],
            'is_read' => true,
            'read_at' => $readAt,
            'email_sent' => true,
            'email_sent_at' => $emailSentAt,
        ];

        $notification = Notification::create($notificationData);

        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('comment_posted', $notification->type);
        $this->assertEquals('New Comment', $notification->title);
        $this->assertEquals('A new comment has been posted.', $notification->message);
        $this->assertEquals(['comment_id' => 123, 'facility_id' => 456], $notification->data);
        $this->assertTrue($notification->is_read);
        $this->assertEquals($readAt->format('Y-m-d H:i:s'), $notification->read_at->format('Y-m-d H:i:s'));
        $this->assertTrue($notification->email_sent);
        $this->assertEquals($emailSentAt->format('Y-m-d H:i:s'), $notification->email_sent_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test notification casts.
     */
    public function test_notification_casts()
    {
        $readAt = now();
        $emailSentAt = now()->addMinutes(5);

        $notification = Notification::factory()->create([
            'data' => ['key1' => 'value1', 'key2' => 'value2'],
            'is_read' => true,
            'read_at' => $readAt,
            'email_sent' => true,
            'email_sent_at' => $emailSentAt,
        ]);

        // Test data is cast to array
        $this->assertIsArray($notification->data);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $notification->data);

        // Test is_read is cast to boolean
        $this->assertIsBool($notification->is_read);
        $this->assertTrue($notification->is_read);

        // Test email_sent is cast to boolean
        $this->assertIsBool($notification->email_sent);
        $this->assertTrue($notification->email_sent);

        // Test read_at is cast to datetime
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->read_at);
        $this->assertEquals($readAt->format('Y-m-d H:i:s'), $notification->read_at->format('Y-m-d H:i:s'));

        // Test email_sent_at is cast to datetime
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->email_sent_at);
        $this->assertEquals($emailSentAt->format('Y-m-d H:i:s'), $notification->email_sent_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test notification scopes.
     */
    public function test_notification_scopes()
    {
        $user = User::factory()->create();

        // Create notifications with different statuses and types
        $unreadNotification1 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'comment_posted',
            'is_read' => false,
        ]);
        $unreadNotification2 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'status_changed',
            'is_read' => false,
        ]);
        $readNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'comment_posted',
            'is_read' => true,
        ]);

        // Test unread scope
        $unreadNotifications = Notification::unread()->get();
        $this->assertCount(2, $unreadNotifications);
        $this->assertTrue($unreadNotifications->contains($unreadNotification1));
        $this->assertTrue($unreadNotifications->contains($unreadNotification2));

        // Test read scope
        $readNotifications = Notification::read()->get();
        $this->assertCount(1, $readNotifications);
        $this->assertTrue($readNotifications->contains($readNotification));

        // Test ofType scope
        $commentNotifications = Notification::ofType('comment_posted')->get();
        $this->assertCount(2, $commentNotifications);
        $this->assertTrue($commentNotifications->contains($unreadNotification1));
        $this->assertTrue($commentNotifications->contains($readNotification));
    }

    /**
     * Test notification status methods.
     */
    public function test_notification_status_methods()
    {
        // Test unread notification
        $unreadNotification = Notification::factory()->create([
            'is_read' => false,
            'read_at' => null,
        ]);

        $this->assertFalse($unreadNotification->isRead());

        // Test read notification
        $readNotification = Notification::factory()->create([
            'is_read' => true,
            'read_at' => now(),
        ]);

        $this->assertTrue($readNotification->isRead());
    }

    /**
     * Test notification email status methods.
     */
    public function test_notification_email_status_methods()
    {
        // Test notification without email sent
        $notificationNoEmail = Notification::factory()->create([
            'email_sent' => false,
            'email_sent_at' => null,
        ]);

        $this->assertFalse($notificationNoEmail->isEmailSent());

        // Test notification with email sent
        $notificationWithEmail = Notification::factory()->create([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);

        $this->assertTrue($notificationWithEmail->isEmailSent());
    }

    /**
     * Test mark as read method.
     */
    public function test_mark_as_read()
    {
        $notification = Notification::factory()->create([
            'is_read' => false,
            'read_at' => null,
        ]);

        $notification->markAsRead();

        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    /**
     * Test mark as unread method.
     */
    public function test_mark_as_unread()
    {
        $notification = Notification::factory()->create([
            'is_read' => true,
            'read_at' => now(),
        ]);

        $notification->markAsUnread();

        $this->assertFalse($notification->is_read);
        $this->assertNull($notification->read_at);
    }

    /**
     * Test mark email as sent method.
     */
    public function test_mark_email_as_sent()
    {
        $notification = Notification::factory()->create([
            'email_sent' => false,
            'email_sent_at' => null,
        ]);

        $notification->markEmailAsSent();

        $this->assertTrue($notification->email_sent);
        $this->assertNotNull($notification->email_sent_at);
    }
}
