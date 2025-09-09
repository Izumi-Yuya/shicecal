<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CommentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;
    private CommentService $commentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create();
        $this->commentService = app(CommentService::class);
        
        $this->actingAs($this->user);
    }

    /**
     * Test comment creation through service
     * @test
     */
    public function it_creates_comments_through_service()
    {
        $comment = $this->commentService->createComment(
            $this->facility,
            'basic_info',
            'Test comment content',
            $this->user
        );

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals('Test comment content', $comment->comment);
        $this->assertEquals('basic_info', $comment->section);
        $this->assertEquals($this->facility->id, $comment->facility_id);
        $this->assertEquals($this->user->id, $comment->user_id);
    }

    /**
     * Test comment retrieval with caching
     * @test
     */
    public function it_retrieves_comments_with_caching()
    {
        // Create test comments
        Comment::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'user_id' => $this->user->id
        ]);

        // First call should hit database
        $comments1 = $this->commentService->getCommentsForSection($this->facility, 'basic_info');
        $this->assertCount(3, $comments1);

        // Second call should hit cache
        $comments2 = $this->commentService->getCommentsForSection($this->facility, 'basic_info');
        $this->assertCount(3, $comments2);
        $this->assertEquals($comments1->pluck('id'), $comments2->pluck('id'));
    }

    /**
     * Test comment count functionality
     * @test
     */
    public function it_counts_comments_correctly()
    {
        Comment::factory()->count(5)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'user_id' => $this->user->id
        ]);

        Comment::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'section' => 'contact_info',
            'user_id' => $this->user->id
        ]);

        $basicInfoCount = $this->commentService->getCommentCount($this->facility, 'basic_info');
        $contactInfoCount = $this->commentService->getCommentCount($this->facility, 'contact_info');

        $this->assertEquals(5, $basicInfoCount);
        $this->assertEquals(3, $contactInfoCount);
    }

    /**
     * Test bulk comment count retrieval
     * @test
     */
    public function it_retrieves_all_comment_counts()
    {
        Comment::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'user_id' => $this->user->id
        ]);

        Comment::factory()->count(1)->create([
            'facility_id' => $this->facility->id,
            'section' => 'service_info',
            'user_id' => $this->user->id
        ]);

        $counts = $this->commentService->getAllCommentCounts($this->facility);

        $this->assertIsArray($counts);
        $this->assertEquals(2, $counts['basic_info']);
        $this->assertEquals(1, $counts['service_info']);
        $this->assertEquals(0, $counts['contact_info']); // No comments
    }

    /**
     * Test cache invalidation on comment creation
     * @test
     */
    public function it_invalidates_cache_on_comment_creation()
    {
        // Prime the cache
        $this->commentService->getCommentCount($this->facility, 'basic_info');
        
        // Verify cache exists
        $cacheKey = "facility_comment_count_{$this->facility->id}_basic_info";
        $this->assertTrue(Cache::has($cacheKey));

        // Create new comment
        $this->commentService->createComment(
            $this->facility,
            'basic_info',
            'New comment',
            $this->user
        );

        // Verify cache was cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test validation of invalid sections
     * @test
     */
    public function it_validates_comment_sections()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid comment section: invalid_section');

        $this->commentService->createComment(
            $this->facility,
            'invalid_section',
            'Test comment',
            $this->user
        );
    }

    /**
     * Test validation of comment content
     * @test
     */
    public function it_validates_comment_content()
    {
        // Test empty content
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment content too short');

        $this->commentService->createComment(
            $this->facility,
            'basic_info',
            '',
            $this->user
        );
    }

    /**
     * Test validation of long comment content
     * @test
     */
    public function it_validates_long_comment_content()
    {
        $longContent = str_repeat('a', 501); // Exceeds max length

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment content too long');

        $this->commentService->createComment(
            $this->facility,
            'basic_info',
            $longContent,
            $this->user
        );
    }

    /**
     * Test API endpoint for comment creation
     * @test
     */
    public function it_creates_comments_via_api()
    {
        $response = $this->postJson(route('facilities.comments.store', $this->facility), [
            'section' => 'basic_info',
            'comment' => 'API test comment'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'コメントを投稿しました。'
                ]);

        $this->assertDatabaseHas('comments', [
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'comment' => 'API test comment',
            'user_id' => $this->user->id
        ]);
    }

    /**
     * Test API endpoint for comment retrieval
     * @test
     */
    public function it_retrieves_comments_via_api()
    {
        Comment::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson(route('facilities.comments.index', [
            'facility' => $this->facility,
            'section' => 'basic_info'
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'comments' => [
                        '*' => [
                            'id',
                            'comment',
                            'created_at',
                            'user' => ['id', 'name']
                        ]
                    ]
                ]);
    }

    /**
     * Test authorization for comment operations
     * @test
     */
    public function it_enforces_authorization_for_comments()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewer);

        $response = $this->postJson(route('facilities.comments.store', $this->facility), [
            'section' => 'basic_info',
            'comment' => 'Unauthorized comment'
        ]);

        $response->assertStatus(403);
    }
}