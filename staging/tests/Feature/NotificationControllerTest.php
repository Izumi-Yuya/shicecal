<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function route_exists()
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('notifications.unread-count'));
    }

    /** @test */
    public function authenticated_user_can_get_unread_count()
    {
        $user = User::factory()->create();

        // Create some notifications directly
        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test Notification 1',
            'message' => 'This is a test notification',
            'data' => ['test' => true],
            'is_read' => false
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test Notification 2',
            'message' => 'This is another test notification',
            'data' => ['test' => true],
            'is_read' => false
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test Notification 3',
            'message' => 'This is a third test notification',
            'data' => ['test' => true],
            'is_read' => false
        ]);

        // Create read notifications
        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Read Notification',
            'message' => 'This notification is read',
            'data' => ['test' => true],
            'is_read' => true
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 3
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_unread_count()
    {
        $response = $this->getJson(route('notifications.unread-count'));

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    /** @test */
    public function user_gets_zero_count_when_no_unread_notifications()
    {
        $user = User::factory()->create();

        // Create only read notifications
        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Read Notification 1',
            'message' => 'This notification is read',
            'data' => ['test' => true],
            'is_read' => true
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Read Notification 2',
            'message' => 'This notification is also read',
            'data' => ['test' => true],
            'is_read' => true
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notifications.unread-count'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0
            ]);
    }

    /** @test */
    public function user_only_sees_their_own_notifications()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create notifications for both users
        for ($i = 1; $i <= 3; $i++) {
            Notification::create([
                'user_id' => $user1->id,
                'type' => 'test',
                'title' => "User1 Notification $i",
                'message' => "This is notification $i for user1",
                'data' => ['test' => true],
                'is_read' => false
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            Notification::create([
                'user_id' => $user2->id,
                'type' => 'test',
                'title' => "User2 Notification $i",
                'message' => "This is notification $i for user2",
                'data' => ['test' => true],
                'is_read' => false
            ]);
        }

        $response = $this->actingAs($user1)
            ->getJson(route('notifications.unread-count'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 3
            ]);
    }
}
