<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MyPageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
    }

    public function test_user_can_view_my_page_dashboard()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create some comments and notifications for the user
        Comment::factory()->count(3)->create(['posted_by' => $user->id, 'facility_id' => $facility->id]);
        Notification::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->get(route('my-page.index'));

        $response->assertStatus(200);
        $response->assertViewIs('my-page.index');
        $response->assertViewHas('myComments');
        $response->assertViewHas('commentStatusCounts');
        $response->assertViewHas('recentNotifications');
        $response->assertViewHas('unreadNotificationCount');
    }

    public function test_primary_responder_sees_assigned_comments_on_dashboard()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();
        
        // Create comments assigned to the primary responder
        Comment::factory()->count(2)->create([
            'assigned_to' => $primaryResponder->id,
            'facility_id' => $facility->id,
        ]);

        $this->actingAs($primaryResponder);

        $response = $this->get(route('my-page.index'));

        $response->assertStatus(200);
        $response->assertViewHas('assignedComments');
        $response->assertViewHas('assignedStatusCounts');
        
        $assignedComments = $response->viewData('assignedComments');
        $this->assertEquals(2, $assignedComments->count());
    }

    public function test_user_can_view_detailed_my_comments()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create comments with different statuses
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'in_progress']);
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($user);

        $response = $this->get(route('my-page.my-comments'));

        $response->assertStatus(200);
        $response->assertViewIs('my-page.my-comments');
        $response->assertViewHas('comments');
        $response->assertViewHas('statusCounts');
        
        $statusCounts = $response->viewData('statusCounts');
        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['in_progress']);
        $this->assertEquals(1, $statusCounts['resolved']);
    }

    public function test_my_comments_filters_by_status()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($user);

        // Filter by pending status
        $response = $this->get(route('my-page.my-comments', ['status' => 'pending']));

        $comments = $response->viewData('comments');
        $this->assertEquals(1, $comments->count());
        $this->assertEquals('pending', $comments->first()->status);
    }

    public function test_my_comments_filters_by_facility_name()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        
        $facility1 = Facility::factory()->create(['facility_name' => 'Test Facility A']);
        $facility2 = Facility::factory()->create(['facility_name' => 'Test Facility B']);
        
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility1->id]);
        Comment::factory()->create(['posted_by' => $user->id, 'facility_id' => $facility2->id]);

        $this->actingAs($user);

        // Filter by facility name
        $response = $this->get(route('my-page.my-comments', ['facility_name' => 'Facility A']));

        $comments = $response->viewData('comments');
        $this->assertEquals(1, $comments->count());
        $this->assertEquals($facility1->id, $comments->first()->facility_id);
    }

    public function test_user_can_view_activity_summary()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create some resolved comments for statistics
        Comment::factory()->create([
            'posted_by' => $user->id,
            'facility_id' => $facility->id,
            'status' => 'resolved',
            'resolved_at' => now(),
            'created_at' => now()->subHours(5),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('my-page.activity-summary'));

        $response->assertStatus(200);
        $response->assertViewIs('my-page.activity-summary');
        $response->assertViewHas('commentsByMonth');
        $response->assertViewHas('responseStats');
        $response->assertViewHas('topFacilities');
    }

    public function test_activity_summary_shows_correct_statistics()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create resolved comment with specific timing
        $comment = Comment::factory()->create([
            'posted_by' => $user->id,
            'facility_id' => $facility->id,
            'status' => 'resolved',
            'created_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(2), // 8 hours response time
        ]);

        $this->actingAs($user);

        $response = $this->get(route('my-page.activity-summary'));

        $responseStats = $response->viewData('responseStats');
        $this->assertNotNull($responseStats);
        $this->assertEquals(8, round($responseStats->avg_response_hours));
    }

    public function test_activity_summary_shows_top_facilities()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        
        $facility1 = Facility::factory()->create(['facility_name' => 'Facility A']);
        $facility2 = Facility::factory()->create(['facility_name' => 'Facility B']);
        
        // Create more comments for facility1
        Comment::factory()->count(3)->create(['posted_by' => $user->id, 'facility_id' => $facility1->id]);
        Comment::factory()->count(1)->create(['posted_by' => $user->id, 'facility_id' => $facility2->id]);

        $this->actingAs($user);

        $response = $this->get(route('my-page.activity-summary'));

        $topFacilities = $response->viewData('topFacilities');
        $this->assertEquals(2, $topFacilities->count());
        
        // First facility should have more comments
        $this->assertEquals($facility1->id, $topFacilities->first()->facility_id);
        $this->assertEquals(3, $topFacilities->first()->comment_count);
    }

    public function test_user_only_sees_own_data()
    {
        $user1 = User::factory()->create(['role' => 'viewer']);
        $user2 = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create comments for both users
        Comment::factory()->count(2)->create(['posted_by' => $user1->id, 'facility_id' => $facility->id]);
        Comment::factory()->count(3)->create(['posted_by' => $user2->id, 'facility_id' => $facility->id]);

        $this->actingAs($user1);

        $response = $this->get(route('my-page.my-comments'));

        $comments = $response->viewData('comments');
        $this->assertEquals(2, $comments->count());
        
        // All comments should belong to user1
        foreach ($comments as $comment) {
            $this->assertEquals($user1->id, $comment->posted_by);
        }
    }

    public function test_dashboard_shows_correct_comment_status_counts()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();
        
        // Create comments with specific statuses
        Comment::factory()->count(2)->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->count(1)->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'in_progress']);
        Comment::factory()->count(3)->create(['posted_by' => $user->id, 'facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($user);

        $response = $this->get(route('my-page.index'));

        $statusCounts = $response->viewData('commentStatusCounts');
        $this->assertEquals(2, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['in_progress']);
        $this->assertEquals(3, $statusCounts['resolved']);
    }
}
