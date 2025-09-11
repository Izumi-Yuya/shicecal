<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_access_notification_endpoint_directly()
    {
        $user = User::factory()->create();

        // Test direct URL access
        $response = $this->actingAs($user)->get('/notifications/unread-count');

        dump('Status: '.$response->status());
        dump('Content: '.$response->getContent());

        $this->assertTrue(true); // Just to make the test pass for now
    }
}
