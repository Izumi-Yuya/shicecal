<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\Facility;
use App\Models\LifelineEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifelineEquipmentNotesCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $viewer;

    private Facility $facility;

    private LifelineEquipment $lifelineEquipment;

    private ElectricalEquipment $electricalEquipment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);

        // Create test facility
        $this->facility = Facility::factory()->create();

        // Create lifeline equipment
        $this->lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'electrical',
        ]);

        // Create electrical equipment
        $this->electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $this->lifelineEquipment->id,
            'notes' => 'Initial notes content',
        ]);
    }

    /** @test */
    public function notes_card_displays_correctly_for_authorized_users()
    {
        $response = $this->actingAs($this->editor)
            ->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);
        $response->assertSee('備考');
        $response->assertSee('Initial notes content');
        $response->assertSee('編集'); // Edit button should be visible for editors
    }

    /** @test */
    public function notes_card_displays_correctly_for_viewers()
    {
        $response = $this->actingAs($this->viewer)
            ->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);
        $response->assertSee('備考');
        $response->assertSee('Initial notes content');

        // Check that the notes card specifically does not have an edit button
        $content = $response->getContent();
        $this->assertStringContainsString('data-section="electrical_notes"', $content);

        // Extract just the notes card section
        preg_match('/data-section="electrical_notes".*?<\/div>\s*<\/div>\s*<\/div>/s', $content, $matches);
        if (! empty($matches)) {
            $notesCardContent = $matches[0];
            $this->assertStringNotContainsString('編集', $notesCardContent, 'Edit button should not be visible in notes card for viewers');
        }
    }

    /** @test */
    public function notes_card_shows_empty_state_when_no_notes()
    {
        // Update electrical equipment to have no notes
        $this->electricalEquipment->update(['notes' => null]);

        $response = $this->actingAs($this->editor)
            ->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);
        $response->assertSee('備考');
        $response->assertSee('未登録');
    }

    /** @test */
    public function editor_can_update_notes_via_api()
    {
        $newNotes = 'Updated notes content with important information about electrical equipment maintenance.';

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $newNotes,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'ライフライン設備情報を更新しました。',
        ]);

        // Verify that the notes were updated in the database
        $this->electricalEquipment->refresh();
        $this->assertEquals($newNotes, $this->electricalEquipment->notes);
    }

    /** @test */
    public function admin_can_update_notes_via_api()
    {
        $newNotes = 'Admin updated notes content.';

        $response = $this->actingAs($this->admin)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $newNotes,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->electricalEquipment->refresh();
        $this->assertEquals($newNotes, $this->electricalEquipment->notes);
    }

    /** @test */
    public function viewer_cannot_update_notes_via_api()
    {
        $newNotes = 'Unauthorized update attempt.';

        $response = $this->actingAs($this->viewer)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $newNotes,
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'この施設のライフライン設備情報を編集する権限がありません。',
        ]);

        // Verify that the notes were not updated
        $this->electricalEquipment->refresh();
        $this->assertEquals('Initial notes content', $this->electricalEquipment->notes);
    }

    /** @test */
    public function notes_field_validates_maximum_length()
    {
        $longNotes = str_repeat('A', 2001); // Exceeds 2000 character limit

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $longNotes,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function notes_field_accepts_null_and_empty_values()
    {
        // Test null value
        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => null,
            ]);

        $response->assertStatus(200);
        $this->electricalEquipment->refresh();
        $this->assertNull($this->electricalEquipment->notes);

        // Test empty string
        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => '',
            ]);

        $response->assertStatus(200);
        $this->electricalEquipment->refresh();
        $this->assertEquals('', $this->electricalEquipment->notes);
    }

    /** @test */
    public function notes_field_preserves_line_breaks()
    {
        $notesWithLineBreaks = "Line 1\nLine 2\n\nLine 4 after empty line";

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $notesWithLineBreaks,
            ]);

        $response->assertStatus(200);
        $this->electricalEquipment->refresh();
        $this->assertEquals($notesWithLineBreaks, $this->electricalEquipment->notes);
    }

    /** @test */
    public function notes_update_logs_activity()
    {
        $newNotes = 'Notes updated for activity log test.';

        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $newNotes,
            ]);

        $response->assertStatus(200);

        // Check that activity was logged using the custom ActivityLog model
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->editor->id,
            'action' => 'update',
            'target_type' => 'facility',
            'target_id' => $this->facility->id,
        ]);
    }

    /** @test */
    public function notes_can_be_updated_independently_of_other_fields()
    {
        // First, set some initial data in other fields
        $this->electricalEquipment->update([
            'basic_info' => [
                'electrical_contractor' => 'Test Contractor',
                'safety_management_company' => 'Test Safety Company',
            ],
            'pas_info' => [
                'availability' => '有',
                'details' => 'Test PAS details',
            ],
        ]);

        $newNotes = 'Only notes should be updated.';

        // Update only the notes field
        $response = $this->actingAs($this->editor)
            ->putJson("/facilities/{$this->facility->id}/lifeline-equipment/electrical", [
                'notes' => $newNotes,
            ]);

        $response->assertStatus(200);

        // Verify that notes were updated
        $this->electricalEquipment->refresh();
        $this->assertEquals($newNotes, $this->electricalEquipment->notes);

        // Verify other fields were not affected
        $this->assertEquals('Test Contractor', $this->electricalEquipment->basic_info['electrical_contractor']);
        $this->assertEquals('Test Safety Company', $this->electricalEquipment->basic_info['safety_management_company']);
        $this->assertEquals('有', $this->electricalEquipment->pas_info['availability']);
        $this->assertEquals('Test PAS details', $this->electricalEquipment->pas_info['details']);
    }

    /** @test */
    public function notes_card_displays_with_proper_html_structure()
    {
        $response = $this->actingAs($this->editor)
            ->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);

        // Check for proper card structure
        $response->assertSee('data-section="electrical_notes"', false);
        $response->assertSee('data-card-type="notes"', false);
        $response->assertSee('fas fa-sticky-note', false);

        // Check for edit mode form structure
        $response->assertSee('name="notes"', false);
        $response->assertSee('maxlength="2000"', false);
        $response->assertSee('character-count', false);

        // Check for accessibility attributes
        $response->assertSee('aria-label="備考を編集"', false);
        $response->assertSee('aria-describedby="electrical_notes_help"', false);
    }

    /** @test */
    public function notes_display_handles_special_characters_safely()
    {
        $notesWithSpecialChars = 'Notes with <script>alert("xss")</script> and & special chars';

        $this->electricalEquipment->update(['notes' => $notesWithSpecialChars]);

        $response = $this->actingAs($this->editor)
            ->get("/facilities/{$this->facility->id}");

        $response->assertStatus(200);

        // Should see escaped content, not raw HTML
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
        $response->assertSee('&amp; special chars', false);
    }
}
