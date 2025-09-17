<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CommentPostingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate');
    }

    public function test_user_can_post_comment_on_facility()
    {
        // Create test users
        $user = User::factory()->create(['role' => 'viewer']);
        $primaryResponder = User::factory()->create(['role' => 'primary_responder']);

        // Create test facility
        $facility = Facility::factory()->create();

        // Act as the user
        $this->actingAs($user);

        // Post a comment
        $commentData = [
            'facility_id' => $facility->id,
            'field_name' => 'facility_name',
            'content' => 'This is a test comment about the facility name.',
        ];

        $response = $this->post(route('comments.store'), $commentData);

        // Assert redirect back with success message
        $response->assertRedirect();
        $response->assertSessionHas('success', 'コメントを投稿しました。');

        // Assert comment was created in database
        $this->assertDatabaseHas('facility_comments', [
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'section' => 'facility_info',
            'comment' => 'This is a test comment about the facility name.',
        ]);
    }

    public function test_comment_requires_content()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();

        $this->actingAs($user);

        // Try to post comment without content
        $response = $this->post(route('comments.store'), [
            'facility_id' => $facility->id,
            'field_name' => 'facility_name',
            'content' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'コメント内容は必須です。');

        // Assert no comment was created
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_requires_valid_facility()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user);

        // Try to post comment for non-existent facility
        $response = $this->post(route('comments.store'), [
            'facility_id' => 999,
            'field_name' => 'facility_name',
            'content' => 'Test comment',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', '施設が見つかりません。');

        // Assert no comment was created
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_user_can_view_facility_with_comments()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();

        // Create some comments for the facility
        $comments = FacilityComment::factory()->count(3)->create([
            'facility_id' => $facility->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('facilities.show', $facility));

        $response->assertStatus(200);
        $response->assertViewIs('facilities.show');
        $response->assertViewHas('facility', $facility);

        // Check that comments are loaded
        $viewFacility = $response->viewData('facility');
        $this->assertEquals(3, $viewFacility->comments->count());
    }

    public function test_user_can_view_their_own_comments()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $otherUser = User::factory()->create(['role' => 'viewer']);
        $facility = Facility::factory()->create();

        // Create comments by the user
        $userComments = FacilityComment::factory()->count(2)->create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
        ]);

        // Create comments by other user
        FacilityComment::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'facility_id' => $facility->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('comments.my-comments'));

        $response->assertStatus(200);
        $response->assertViewIs('comments.my-comments');

        // Should only see own comments
        $comments = $response->viewData('comments');
        $this->assertEquals(2, $comments->count());

        foreach ($comments as $comment) {
            $this->assertEquals($user->id, $comment->user_id);
        }
    }

    public function test_primary_responder_can_view_assigned_comments()
    {
        $this->markTestSkipped('FacilityComment model does not have assigned_to field yet');
    }
}
