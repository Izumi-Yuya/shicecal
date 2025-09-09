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
            'role' => 'editor'
        ]);

        // Create a test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
            'office_code' => 'TEST001'
        ]);
    }

    /** @test */
    public function edit_button_is_visible_in_table_view_for_authorized_users()
    {
        // Set view mode to table
        session(['facility_basic_info_view_mode' => 'table']);

        $response = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Check that edit button is present in the response
        $response->assertSee('編集');
        $response->assertSee(route('facilities.edit-basic-info', $this->facility));

        // Verify table view is being displayed
        $response->assertSee('facility-table-view');
        $response->assertViewHas('viewMode', 'table');
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
            'role' => 'viewer'
        ]);

        // Set view mode to table
        session(['facility_basic_info_view_mode' => 'table']);

        $response = $this->actingAs($viewerUser)
            ->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);

        // Edit button should not be visible for viewers
        $response->assertDontSee(route('facilities.edit-basic-info', $this->facility));
    }

    /** @test */
    public function view_mode_preference_is_maintained_after_edit_operation()
    {
        // Set initial view mode to table
        $this->actingAs($this->user)
            ->post(route('facilities.set-view-mode'), [
                'view_mode' => 'table'
            ])
            ->assertJson(['success' => true]);

        // Verify table view mode is set
        $this->assertEquals('table', session('facility_basic_info_view_mode'));

        // Perform edit operation
        $updateData = [
            'company_name' => 'Updated Company Name',
            'office_code' => 'UPD001',
            'facility_name' => 'Updated Facility Name',
            'postal_code' => '100-0001',
            'address' => 'Updated Address',
            'phone_number' => '03-1234-5678',
            'email' => 'updated@example.com',
            'opening_date' => '2020-01-01',
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 3,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60
        ];

        $response = $this->actingAs($this->user)
            ->put(route('facilities.update-basic-info', $this->facility), $updateData);

        // Should redirect back to facility show page
        $response->assertRedirect(route('facilities.show', $this->facility));

        // View mode preference should be maintained
        $this->assertEquals('table', session('facility_basic_info_view_mode'));

        // Follow redirect and verify table view is still active
        $showResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $showResponse->assertStatus(200);
        $showResponse->assertViewHas('viewMode', 'table');
        $showResponse->assertSee('facility-table-view');
    }

    /** @test */
    public function seamless_transition_back_to_selected_view_mode_after_editing()
    {
        // Test with card view mode
        $this->actingAs($this->user)
            ->post(route('facilities.set-view-mode'), [
                'view_mode' => 'card'
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
            'email' => 'cardview@example.com'
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
    public function edit_workflow_works_correctly_with_table_view_mode()
    {
        // Set table view mode
        $this->actingAs($this->user)
            ->post(route('facilities.set-view-mode'), [
                'view_mode' => 'table'
            ]);

        // Access facility show page in table view
        $showResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $showResponse->assertStatus(200);
        $showResponse->assertViewHas('viewMode', 'table');

        // Click edit button (simulate navigation)
        $editResponse = $this->actingAs($this->user)
            ->get(route('facilities.edit-basic-info', $this->facility));

        $editResponse->assertStatus(200);

        // Perform edit operation
        $updateData = [
            'company_name' => 'Table View Updated Company',
            'office_code' => 'TBL001',
            'facility_name' => 'Table View Updated Facility',
            'postal_code' => '300-0003',
            'address' => 'Table View Updated Address',
            'building_name' => 'Table View Building',
            'phone_number' => '03-1111-2222',
            'fax_number' => '03-3333-4444',
            'toll_free_number' => '0120-555-666',
            'email' => 'tableview@example.com',
            'website_url' => 'https://tableview.example.com',
            'opening_date' => '2019-06-15',
            'years_in_operation' => 5,
            'building_structure' => '鉄骨造',
            'building_floors' => 4,
            'paid_rooms_count' => 75,
            'ss_rooms_count' => 15,
            'capacity' => 90
        ];

        $updateResponse = $this->actingAs($this->user)
            ->put(route('facilities.update-basic-info', $this->facility), $updateData);

        $updateResponse->assertRedirect(route('facilities.show', $this->facility));
        $updateResponse->assertSessionHas('success', '施設基本情報を更新しました。');

        // Verify return to table view with updated data
        $finalResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $finalResponse->assertStatus(200);
        $finalResponse->assertViewHas('viewMode', 'table');
        $finalResponse->assertSee('facility-table-view');

        // Verify all updated data is displayed in table view
        $finalResponse->assertSee('Table View Updated Company');
        $finalResponse->assertSee('Table View Updated Facility');
        $finalResponse->assertSee('TBL001');
        $finalResponse->assertSee('tableview@example.com');
        $finalResponse->assertSee('https://tableview.example.com');
        $finalResponse->assertSee('2019年06月15日'); // Japanese date format with zero-padded month
        $finalResponse->assertSee('5年'); // Years with unit
        $finalResponse->assertSee('75室'); // Rooms with unit
        $finalResponse->assertSee('90名'); // Capacity with unit
    }

    /** @test */
    public function view_mode_session_persists_across_multiple_edit_operations()
    {
        // Set table view mode
        $this->actingAs($this->user)
            ->post(route('facilities.set-view-mode'), [
                'view_mode' => 'table'
            ]);

        // First edit operation
        $firstUpdateData = [
            'company_name' => 'First Update Company',
            'office_code' => 'FIRST001',
            'facility_name' => 'First Update Facility'
        ];

        $this->actingAs($this->user)
            ->put(route('facilities.update-basic-info', $this->facility), $firstUpdateData);

        // Verify table view is maintained
        $this->assertEquals('table', session('facility_basic_info_view_mode'));

        // Second edit operation
        $secondUpdateData = [
            'company_name' => 'Second Update Company',
            'office_code' => 'SECOND001',
            'facility_name' => 'Second Update Facility'
        ];

        $this->actingAs($this->user)
            ->put(route('facilities.update-basic-info', $this->facility), $secondUpdateData);

        // Verify table view is still maintained
        $this->assertEquals('table', session('facility_basic_info_view_mode'));

        // Final verification
        $finalResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $finalResponse->assertViewHas('viewMode', 'table');
        $finalResponse->assertSee('Second Update Company');
    }

    /** @test */
    public function edit_button_functionality_works_in_both_view_modes()
    {
        // Test edit button in card view
        session(['facility_basic_info_view_mode' => 'card']);

        $cardViewResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $cardViewResponse->assertStatus(200);
        $cardViewResponse->assertSee('編集');

        // Test edit button in table view
        session(['facility_basic_info_view_mode' => 'table']);

        $tableViewResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));

        $tableViewResponse->assertStatus(200);
        $tableViewResponse->assertSee('編集');

        // Verify both responses contain the same edit URL
        $editUrl = route('facilities.edit-basic-info', $this->facility);
        $cardViewResponse->assertSee($editUrl);
        $tableViewResponse->assertSee($editUrl);
    }

    /** @test */
    public function admin_users_can_edit_in_both_view_modes()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'admin'
        ]);

        // Test table view for admin
        session(['facility_basic_info_view_mode' => 'table']);

        $tableResponse = $this->actingAs($adminUser)
            ->get(route('facilities.show', $this->facility));

        $tableResponse->assertStatus(200);
        $tableResponse->assertSee('編集');

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
