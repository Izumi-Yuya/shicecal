<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableViewCommentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create();
    }

    /**
     * Test that table view displays comment button with correct count
     *
     * @test
     */
    public function it_displays_comment_button_with_correct_count_in_table_view()
    {
        // Create some test comments
        Comment::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'posted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check that table view is rendered
        $response->assertSee('基本情報（テーブル形式）');

        // Check that comment button is present
        $response->assertSee('コメント');

        // Check that comment count is displayed (should be 3)
        $response->assertSee('comment-count');
    }

    /**
     * Test that table view comment section is properly structured
     *
     * @test
     */
    public function it_renders_table_view_comment_section_properly()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check for comment section structure
        $response->assertSee('comment-section');
        $response->assertSee('comment-form');
        $response->assertSee('comment-input');
        $response->assertSee('comment-submit');
        $response->assertSee('comment-list');

        // Check for proper CSS classes
        $response->assertSee('table-view-comment-controls');
        $response->assertSee('facility-table-view');
    }

    /**
     * Test that comment toggle functionality is available
     *
     * @test
     */
    public function it_provides_comment_toggle_functionality()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check for toggle button attributes
        $response->assertSee('comment-toggle');
        $response->assertSee('data-section=&quot;basic_info&quot;', false);
        $response->assertSee('コメントを表示/非表示');
    }

    /**
     * Test that table view maintains comment functionality parity with card view
     *
     * @test
     */
    public function it_maintains_comment_functionality_parity_with_card_view()
    {
        // Test table view
        $tableResponse = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        // Test card view
        $cardResponse = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'card'])
            ->get(route('facilities.show', $this->facility));

        $tableResponse->assertStatus(200);
        $cardResponse->assertStatus(200);

        // Both views should have comment functionality
        $tableResponse->assertSee('comment-toggle');
        $cardResponse->assertSee('comment-toggle');

        $tableResponse->assertSee('comment-section');
        $cardResponse->assertSee('comment-section');

        $tableResponse->assertSee('data-section=&quot;basic_info&quot;', false);
        $cardResponse->assertSee('data-section=&quot;basic_info&quot;', false);
    }

    /**
     * Test that comment count updates correctly
     *
     * @test
     */
    public function it_updates_comment_count_correctly()
    {
        // Initially no comments
        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('comment-count');

        // Add comments and check count updates
        Comment::factory()->count(5)->create([
            'facility_id' => $this->facility->id,
            'section' => 'basic_info',
            'posted_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('comment-count');
    }

    /**
     * Test responsive design for table view comments
     *
     * @test
     */
    public function it_provides_responsive_comment_design_for_table_view()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['facility_basic_info_view_mode' => 'table'])
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check for responsive CSS classes and structure
        $response->assertSee('table-view-comment-controls');
        $response->assertSee('d-flex');
        $response->assertSee('justify-content-between');
        $response->assertSee('align-items-center');
    }
}
