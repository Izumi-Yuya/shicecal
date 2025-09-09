<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test comment relationships.
     */
    public function test_comment_relationships()
    {
        $facility = Facility::factory()->create();
        $poster = User::factory()->create();
        $assignee = User::factory()->create();

        $comment = Comment::factory()->create([
            'facility_id' => $facility->id,
            'posted_by' => $poster->id,
            'assigned_to' => $assignee->id,
        ]);

        // Test facility relationship
        $this->assertEquals($facility->id, $comment->facility->id);

        // Test poster relationship
        $this->assertEquals($poster->id, $comment->poster->id);

        // Test assignee relationship
        $this->assertEquals($assignee->id, $comment->assignee->id);
    }

    /**
     * Test comment status checking methods.
     */
    public function test_comment_status_checking_methods()
    {
        // Test pending comment
        $pendingComment = Comment::factory()->create(['status' => 'pending']);
        $this->assertTrue($pendingComment->isPending());
        $this->assertFalse($pendingComment->isInProgress());
        $this->assertFalse($pendingComment->isResolved());

        // Test in progress comment
        $inProgressComment = Comment::factory()->create(['status' => 'in_progress']);
        $this->assertFalse($inProgressComment->isPending());
        $this->assertTrue($inProgressComment->isInProgress());
        $this->assertFalse($inProgressComment->isResolved());

        // Test resolved comment
        $resolvedComment = Comment::factory()->create(['status' => 'resolved']);
        $this->assertFalse($resolvedComment->isPending());
        $this->assertFalse($resolvedComment->isInProgress());
        $this->assertTrue($resolvedComment->isResolved());
    }

    /**
     * Test comment scopes.
     */
    public function test_comment_scopes()
    {
        // Create comments with different statuses
        $pendingComment1 = Comment::factory()->create(['status' => 'pending']);
        $pendingComment2 = Comment::factory()->create(['status' => 'pending']);
        $inProgressComment = Comment::factory()->create(['status' => 'in_progress']);
        $resolvedComment = Comment::factory()->create(['status' => 'resolved']);

        // Test pending scope
        $pendingComments = Comment::pending()->get();
        $this->assertCount(2, $pendingComments);
        $this->assertTrue($pendingComments->contains($pendingComment1));
        $this->assertTrue($pendingComments->contains($pendingComment2));

        // Test in progress scope
        $inProgressComments = Comment::inProgress()->get();
        $this->assertCount(1, $inProgressComments);
        $this->assertTrue($inProgressComments->contains($inProgressComment));

        // Test resolved scope
        $resolvedComments = Comment::resolved()->get();
        $this->assertCount(1, $resolvedComments);
        $this->assertTrue($resolvedComments->contains($resolvedComment));
    }

    /**
     * Test comment fillable attributes.
     */
    public function test_comment_fillable_attributes()
    {
        $facility = Facility::factory()->create();
        $poster = User::factory()->create();
        $assignee = User::factory()->create();
        $resolvedAt = now();

        $commentData = [
            'facility_id' => $facility->id,
            'field_name' => 'facility_name',
            'content' => 'This is a test comment',
            'status' => 'resolved',
            'posted_by' => $poster->id,
            'assigned_to' => $assignee->id,
            'resolved_at' => $resolvedAt,
        ];

        $comment = Comment::create($commentData);

        $this->assertEquals($facility->id, $comment->facility_id);
        $this->assertEquals('facility_name', $comment->field_name);
        $this->assertEquals('This is a test comment', $comment->content);
        $this->assertEquals('resolved', $comment->status);
        $this->assertEquals($poster->id, $comment->posted_by);
        $this->assertEquals($assignee->id, $comment->assigned_to);
        $this->assertEquals($resolvedAt->format('Y-m-d H:i:s'), $comment->resolved_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test comment casts.
     */
    public function test_comment_casts()
    {
        $resolvedAt = now();
        $comment = Comment::factory()->create([
            'resolved_at' => $resolvedAt,
        ]);

        // Test resolved_at is cast to datetime
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $comment->resolved_at);
        $this->assertEquals($resolvedAt->format('Y-m-d H:i:s'), $comment->resolved_at->format('Y-m-d H:i:s'));
    }
}