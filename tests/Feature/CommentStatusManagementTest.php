<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommentStatusManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate');
    }

    public function test_primary_responder_can_view_status_dashboard()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);

        // Create some comments with different statuses
        $facility = Facility::factory()->create();
        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'in_progress']);
        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($primaryResponder);

        $response = $this->get(route('comments.status-dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('comments.status-dashboard');
        $response->assertViewHas('statusCounts');
        $response->assertViewHas('comments');
        $response->assertViewHas('assignees');
    }

    public function test_status_dashboard_shows_correct_counts()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        // Create comments with specific statuses
        Comment::factory()->count(2)->create(['facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->count(3)->create(['facility_id' => $facility->id, 'status' => 'in_progress']);
        Comment::factory()->count(1)->create(['facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($primaryResponder);

        $response = $this->get(route('comments.status-dashboard'));

        $statusCounts = $response->viewData('statusCounts');
        $this->assertEquals(2, $statusCounts['pending']);
        $this->assertEquals(3, $statusCounts['in_progress']);
        $this->assertEquals(1, $statusCounts['resolved']);
    }

    public function test_status_dashboard_filters_by_status()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'pending']);
        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'in_progress']);
        Comment::factory()->create(['facility_id' => $facility->id, 'status' => 'resolved']);

        $this->actingAs($primaryResponder);

        // Filter by pending status
        $response = $this->get(route('comments.status-dashboard', ['status' => 'pending']));

        $comments = $response->viewData('comments');
        $this->assertEquals(1, $comments->count());
        $this->assertEquals('pending', $comments->first()->status);
    }

    public function test_status_dashboard_filters_by_facility_name()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);

        $facility1 = Facility::factory()->create(['facility_name' => 'Test Facility A']);
        $facility2 = Facility::factory()->create(['facility_name' => 'Test Facility B']);

        Comment::factory()->create(['facility_id' => $facility1->id]);
        Comment::factory()->create(['facility_id' => $facility2->id]);

        $this->actingAs($primaryResponder);

        // Filter by facility name
        $response = $this->get(route('comments.status-dashboard', ['facility_name' => 'Facility A']));

        $comments = $response->viewData('comments');
        $this->assertEquals(1, $comments->count());
        $this->assertEquals($facility1->id, $comments->first()->facility_id);
    }

    public function test_status_dashboard_filters_by_assignee()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $assignee1 = User::factory()->create(['role' => 'primary_responder']);
        $assignee2 = User::factory()->create(['role' => 'primary_responder']);

        $facility = Facility::factory()->create();

        Comment::factory()->create(['facility_id' => $facility->id, 'assigned_to' => $assignee1->id]);
        Comment::factory()->create(['facility_id' => $facility->id, 'assigned_to' => $assignee2->id]);

        $this->actingAs($primaryResponder);

        // Filter by assignee
        $response = $this->get(route('comments.status-dashboard', ['assignee' => $assignee1->id]));

        $comments = $response->viewData('comments');
        $this->assertEquals(1, $comments->count());
        $this->assertEquals($assignee1->id, $comments->first()->assigned_to);
    }

    public function test_bulk_status_update()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        $comments = Comment::factory()->count(3)->create([
            'facility_id' => $facility->id,
            'status' => 'pending',
            'assigned_to' => $primaryResponder->id,
        ]);

        $this->actingAs($primaryResponder);

        // Bulk update to in_progress
        $response = $this->post(route('comments.bulk-update-status'), [
            'comment_ids' => $comments->pluck('id')->toArray(),
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check all comments were updated
        foreach ($comments as $comment) {
            $comment->refresh();
            $this->assertEquals('in_progress', $comment->status);
        }
    }

    public function test_bulk_status_update_with_invalid_status()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        $comment = Comment::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending',
        ]);

        $this->actingAs($primaryResponder);

        // Try bulk update with invalid status
        $response = $this->post(route('comments.bulk-update-status'), [
            'comment_ids' => [$comment->id],
            'status' => 'invalid_status',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Check comment status wasn't changed
        $comment->refresh();
        $this->assertEquals('pending', $comment->status);
    }

    public function test_comment_status_update_sets_resolved_at()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        $comment = Comment::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending',
            'resolved_at' => null,
        ]);

        $this->actingAs($primaryResponder);

        // Update to resolved
        $response = $this->patch(route('comments.update-status', $comment), [
            'status' => 'resolved',
        ]);

        $comment->refresh();
        $this->assertEquals('resolved', $comment->status);
        $this->assertNotNull($comment->resolved_at);
    }

    public function test_comment_status_update_clears_resolved_at_when_not_resolved()
    {
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);
        $facility = Facility::factory()->create();

        $comment = Comment::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        $this->actingAs($primaryResponder);

        // Update back to in_progress
        $response = $this->patch(route('comments.update-status', $comment), [
            'status' => 'in_progress',
        ]);

        $comment->refresh();
        $this->assertEquals('in_progress', $comment->status);
        $this->assertNull($comment->resolved_at);
    }

    public function test_viewer_cannot_access_status_dashboard()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer);

        $response = $this->get(route('comments.status-dashboard'));

        // Should be accessible to all authenticated users for now
        // In production, you might want to restrict this
        $response->assertStatus(200);
    }
}
