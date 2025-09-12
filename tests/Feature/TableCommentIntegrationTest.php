<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableCommentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'editor'
        ]);
        
        $this->facility = Facility::factory()->create([
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company'
        ]);
    }

    public function test_comment_wrapper_component_renders_correctly()
    {
        $this->actingAs($this->user);

        // Create some test comments
        FacilityComment::factory()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->user->id,
            'section' => 'basic_info',
            'comment' => 'Test comment for basic info'
        ]);

        $config = [
            'comment_display_name' => '基本情報',
            'columns' => [
                [
                    'key' => 'facility_name',
                    'label' => '施設名',
                    'type' => 'text'
                ]
            ],
            'layout' => [
                'type' => 'key_value_pairs'
            ],
            'features' => [
                'comments' => true
            ]
        ];

        $data = [
            'facility_name' => $this->facility->facility_name
        ];

        $view = view('components.table-comment-wrapper', [
            'config' => $config,
            'data' => $data,
            'section' => 'basic_info',
            'facility' => $this->facility,
            'commentEnabled' => true
        ]);

        $html = $view->render();

        // Assert comment toggle button is present
        $this->assertStringContainsString('comment-toggle', $html);
        $this->assertStringContainsString('data-section="basic_info"', $html);
        
        // Assert comment count badge shows correct count
        $this->assertStringContainsString('comment-count', $html);
        $this->assertStringContainsString('>1<', $html); // 1 comment
        
        // Assert comment section is present
        $this->assertStringContainsString('comment-section', $html);
        $this->assertStringContainsString('基本情報のコメント', $html);
    }

    public function test_comment_section_component_renders_comments()
    {
        $this->actingAs($this->user);

        // Create test comments
        $comment1 = FacilityComment::factory()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->user->id,
            'section' => 'basic_info',
            'comment' => 'First test comment'
        ]);

        $comment2 = FacilityComment::factory()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->user->id,
            'section' => 'basic_info',
            'comment' => 'Second test comment'
        ]);

        $comments = collect([$comment1, $comment2]);

        $view = view('components.table-comment-section', [
            'section' => 'basic_info',
            'displayName' => '基本情報',
            'facility' => $this->facility,
            'comments' => $comments
        ]);

        $html = $view->render();

        // Assert comments are displayed
        $this->assertStringContainsString('First test comment', $html);
        $this->assertStringContainsString('Second test comment', $html);
        
        // Assert user names are displayed (HTML escaped)
        $this->assertStringContainsString(htmlspecialchars($this->user->name), $html);
        
        // Assert comment form is present
        $this->assertStringContainsString('comment-submit-form', $html);
        $this->assertStringContainsString('name="comment"', $html);
        
        // Assert comment count is correct
        $this->assertStringContainsString('>2<', $html); // 2 comments
    }

    public function test_comment_section_shows_no_comments_message_when_empty()
    {
        $this->actingAs($this->user);

        $view = view('components.table-comment-section', [
            'section' => 'basic_info',
            'displayName' => '基本情報',
            'facility' => $this->facility,
            'comments' => collect()
        ]);

        $html = $view->render();

        // Assert no comments message is displayed
        $this->assertStringContainsString('まだコメントはありません', $html);
        $this->assertStringContainsString('最初のコメントを追加', $html);
        
        // Assert comment count is zero
        $this->assertStringContainsString('>0<', $html);
    }

    public function test_comment_wrapper_can_be_disabled()
    {
        $this->actingAs($this->user);

        $config = [
            'comment_display_name' => '基本情報',
            'columns' => [
                [
                    'key' => 'facility_name',
                    'label' => '施設名',
                    'type' => 'text'
                ]
            ],
            'features' => [
                'comments' => false
            ]
        ];

        $view = view('components.table-comment-wrapper', [
            'config' => $config,
            'data' => ['facility_name' => $this->facility->facility_name],
            'section' => 'basic_info',
            'facility' => $this->facility,
            'commentEnabled' => false
        ]);

        $html = $view->render();

        // Assert comment controls are not present
        $this->assertStringNotContainsString('comment-toggle', $html);
        $this->assertStringNotContainsString('comment-section', $html);
    }

    public function test_comment_section_handles_different_sections()
    {
        $this->actingAs($this->user);

        $sections = [
            'basic_info' => '基本情報',
            'service_info' => 'サービス情報',
            'land_info' => '土地情報'
        ];

        foreach ($sections as $section => $displayName) {
            // Create comment for this section
            FacilityComment::factory()->create([
                'facility_id' => $this->facility->id,
                'user_id' => $this->user->id,
                'section' => $section,
                'comment' => "Test comment for {$section}"
            ]);

            $view = view('components.table-comment-section', [
                'section' => $section,
                'displayName' => $displayName,
                'facility' => $this->facility,
                'comments' => null // Let component load comments
            ]);

            $html = $view->render();

            // Assert section-specific content
            $this->assertStringContainsString("data-section=\"{$section}\"", $html);
            $this->assertStringContainsString("{$displayName}のコメント", $html);
            $this->assertStringContainsString("Test comment for {$section}", $html);
        }
    }

    public function test_comment_delete_button_shows_for_authorized_users()
    {
        $this->actingAs($this->user);

        $comment = FacilityComment::factory()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->user->id,
            'section' => 'basic_info',
            'comment' => 'Test comment'
        ]);

        $view = view('components.table-comment-section', [
            'section' => 'basic_info',
            'displayName' => '基本情報',
            'facility' => $this->facility,
            'comments' => collect([$comment])
        ]);

        $html = $view->render();

        // Assert delete button is present for comment owner
        $this->assertStringContainsString('comment-delete', $html);
        $this->assertStringContainsString("data-comment-id=\"{$comment->id}\"", $html);
    }

    public function test_comment_delete_button_hidden_for_unauthorized_users()
    {
        // Create another user
        $otherUser = User::factory()->create(['role' => 'viewer']);
        
        $comment = FacilityComment::factory()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $otherUser->id,
            'section' => 'basic_info',
            'comment' => 'Test comment'
        ]);

        // Act as the original user (not the comment owner)
        $this->actingAs($this->user);

        $view = view('components.table-comment-section', [
            'section' => 'basic_info',
            'displayName' => '基本情報',
            'facility' => $this->facility,
            'comments' => collect([$comment])
        ]);

        $html = $view->render();

        // Assert delete button is not present for non-owner
        $this->assertStringNotContainsString('comment-delete', $html);
    }
}