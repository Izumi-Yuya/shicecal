<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FacilityEditWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with editor permissions
        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);

        // Create a test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
            'office_code' => 'TEST001',
        ]);
    }



    /** @test */
    public function edit_button_is_visible_in_card_view_for_authorized_users()
    {
        // Set view mode to card (default)
        session(['facility_basic_info_view_mode' => 'card']);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check that edit button is present in the response
        $response->assertSee('編集');
        $response->assertSee(route('facilities.edit-basic-info', $this->facility));

        // Verify card view is being displayed (default)
        $response->assertViewHas('viewMode', 'card');
    }

    /** @test */
    public function edit_button_is_not_visible_for_unauthorized_users()
    {
        // Create a viewer user (no edit permissions)
        $viewerUser = User::factory()->create([
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewerUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Edit button should not be visible for viewers
        $response->assertDontSee(route('facilities.edit-basic-info', $this->facility));
    }



    /** @test */
    public function seamless_transition_back_to_selected_view_mode_after_editing()
    {
        // Test with card view mode
        $this->actingAs($this->user)
            ->post(route('facilities.set-view-mode'), [
                'view_mode' => 'card',
            ]);

        // Access edit page
        $editResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $editResponse->assertStatus(200);
        $editResponse->assertSee('施設基本情報編集');

        // Perform update
        $updateData = [
            'company_name' => 'Card View Test Company',
            'office_code' => 'CARD001',
            'facility_name' => 'Card View Test Facility',
            'postal_code' => '200-0002',
            'address' => 'Card View Address',
            'phone_number' => '03-9876-5432',
            'email' => 'cardview@example.com',
        ];

        $updateResponse = $this->actingAs($this->user)
            ->put(route('facilities.update-basic-info', $this->facility), $updateData);

        $updateResponse->assertRedirect(route('facilities.show', $this->facility));

        // Follow redirect and verify card view is maintained
        $showResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $showResponse->assertStatus(200);
        $showResponse->assertViewHas('viewMode', 'card');

        // Verify updated data is displayed
        $showResponse->assertSee('Card View Test Company');
        $showResponse->assertSee('Card View Test Facility');
    }





    /** @test */
    public function edit_button_functionality_works_in_card_view()
    {
        // Test edit button in card view
        session(['facility_basic_info_view_mode' => 'card']);

        $cardViewResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $cardViewResponse->assertStatus(200);
        $cardViewResponse->assertSee('編集');

        // Verify response contains the edit URL
        $editUrl = route('facilities.edit-basic-info', $this->facility);
        $cardViewResponse->assertSee($editUrl);
    }

    /** @test */
    public function admin_users_can_edit_in_card_view()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        // Test card view for admin
        session(['facility_basic_info_view_mode' => 'card']);

        $cardResponse = $this->actingAs($adminUser)
            ->get(route('facilities.show', $this->facility));

        $cardResponse->assertStatus(200);
        $cardResponse->assertSee('編集');
    }

    /** @test */
    public function view_mode_defaults_to_card_when_not_set()
    {
        // Clear any existing session data
        session()->forget('facility_basic_info_view_mode');

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertViewHas('viewMode', 'card');
    }
}
