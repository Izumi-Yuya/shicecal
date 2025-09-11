<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-End Functionality Validation Tests
 *
 * Tests all major features to ensure functionality is preserved after refactoring
 */
class EndToEndFunctionalityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected $editor;

    protected $viewer;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->viewer = User::factory()->create(['role' => 'viewer']);

        // Create test facility
        $this->facility = Facility::factory()->create([
            'facility_name' => 'Test Facility',
            'office_code' => 'TEST001',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_perform_all_facility_operations()
    {
        $this->actingAs($this->admin);

        // Test facility creation
        $response = $this->post('/facilities', [
            'name' => 'New Test Facility',
            'facility_code' => 'NEW001',
            'address' => '123 Test Street',
            'status' => 'active',
            'facility_type' => 'office',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', [
            'name' => 'New Test Facility',
            'facility_code' => 'NEW001',
        ]);

        $newFacility = Facility::where('facility_code', 'NEW001')->first();

        // Test facility viewing
        $response = $this->get("/facilities/{$newFacility->id}");
        $response->assertStatus(200);
        $response->assertSee('New Test Facility');

        // Test facility editing
        $response = $this->put("/facilities/{$newFacility->id}", [
            'name' => 'Updated Test Facility',
            'facility_code' => 'NEW001',
            'address' => '456 Updated Street',
            'status' => 'active',
            'facility_type' => 'office',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', [
            'id' => $newFacility->id,
            'name' => 'Updated Test Facility',
            'address' => '456 Updated Street',
        ]);

        // Test facility deletion
        $response = $this->delete("/facilities/{$newFacility->id}");
        $response->assertRedirect();
        $this->assertSoftDeleted('facilities', ['id' => $newFacility->id]);
    }

    /** @test */
    public function land_information_management_works_correctly()
    {
        $this->actingAs($this->admin);

        // Test land info creation
        $response = $this->post("/facilities/{$this->facility->id}/land-info", [
            'land_type' => 'owned',
            'area_sqm' => 1000.50,
            'acquisition_date' => '2023-01-15',
            'acquisition_cost' => 50000000,
            'current_value' => 55000000,
            'notes' => 'Test land information',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('land_infos', [
            'facility_id' => $this->facility->id,
            'land_type' => 'owned',
            'area_sqm' => 1000.50,
        ]);

        $landInfo = LandInfo::where('facility_id', $this->facility->id)->first();

        // Test land info viewing
        $response = $this->get("/facilities/{$this->facility->id}/land-info/{$landInfo->id}");
        $response->assertStatus(200);
        $response->assertSee('1000.5');

        // Test land info editing
        $response = $this->put("/facilities/{$this->facility->id}/land-info/{$landInfo->id}", [
            'land_type' => 'leased',
            'area_sqm' => 1200.75,
            'acquisition_date' => '2023-01-15',
            'acquisition_cost' => 50000000,
            'current_value' => 60000000,
            'notes' => 'Updated land information',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('land_infos', [
            'id' => $landInfo->id,
            'land_type' => 'leased',
            'area_sqm' => 1200.75,
        ]);
    }

    /** @test */
    public function comment_system_functions_properly()
    {
        $this->actingAs($this->editor);

        // Test comment creation
        $response = $this->post("/facilities/{$this->facility->id}/comments", [
            'content' => 'This is a test comment',
            'assigned_to' => $this->admin->id,
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'facility_id' => $this->facility->id,
            'content' => 'This is a test comment',
            'user_id' => $this->editor->id,
            'assigned_to' => $this->admin->id,
        ]);

        $comment = Comment::where('facility_id', $this->facility->id)->first();

        // Test comment viewing
        $response = $this->get("/facilities/{$this->facility->id}");
        $response->assertStatus(200);
        $response->assertSee('This is a test comment');

        // Test comment reply
        $response = $this->post("/comments/{$comment->id}/reply", [
            'content' => 'This is a reply to the comment',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'parent_id' => $comment->id,
            'content' => 'This is a reply to the comment',
            'user_id' => $this->editor->id,
        ]);
    }

    /** @test */
    public function file_upload_and_management_works()
    {
        Storage::fake('local');
        $this->actingAs($this->admin);

        // Create a fake file
        $file = UploadedFile::fake()->create('test-document.pdf', 1024, 'application/pdf');

        // Test file upload
        $response = $this->post("/facilities/{$this->facility->id}/files", [
            'file' => $file,
            'description' => 'Test document upload',
        ]);

        $response->assertRedirect();

        // Verify file was stored
        Storage::disk('local')->assertExists('facilities/'.$this->facility->id.'/'.$file->hashName());

        // Verify database record
        $this->assertDatabaseHas('facility_files', [
            'facility_id' => $this->facility->id,
            'original_name' => 'test-document.pdf',
            'description' => 'Test document upload',
        ]);
    }

    /** @test */
    public function notification_system_works_correctly()
    {
        $this->actingAs($this->admin);

        // Create a notification
        $notification = Notification::create([
            'user_id' => $this->editor->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification message',
            'type' => 'info',
            'facility_id' => $this->facility->id,
        ]);

        // Test notification viewing
        $this->actingAs($this->editor);
        $response = $this->get('/notifications');
        $response->assertStatus(200);
        $response->assertSee('Test Notification');

        // Test marking notification as read
        $response = $this->patch("/notifications/{$notification->id}/read");
        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /** @test */
    public function export_functionality_works()
    {
        $this->actingAs($this->admin);

        // Create additional test data
        Facility::factory()->count(5)->create();

        // Test CSV export
        $response = $this->post('/export/facilities', [
            'format' => 'csv',
            'fields' => ['name', 'facility_code', 'address', 'status'],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Test PDF export
        $response = $this->post('/export/facilities', [
            'format' => 'pdf',
            'fields' => ['name', 'facility_code', 'address', 'status'],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function user_authentication_and_authorization_works()
    {
        // Test login
        $response = $this->post('/login', [
            'email' => $this->editor->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->editor);

        // Test role-based access
        $this->actingAs($this->viewer);

        // Viewer should be able to view facilities
        $response = $this->get("/facilities/{$this->facility->id}");
        $response->assertStatus(200);

        // Viewer should not be able to edit facilities
        $response = $this->get("/facilities/{$this->facility->id}/edit");
        $response->assertStatus(403);

        // Viewer should not be able to delete facilities
        $response = $this->delete("/facilities/{$this->facility->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function search_and_filtering_works()
    {
        $this->actingAs($this->admin);

        // Create facilities with different attributes
        Facility::factory()->create(['name' => 'Tokyo Office', 'status' => 'active']);
        Facility::factory()->create(['name' => 'Osaka Branch', 'status' => 'inactive']);
        Facility::factory()->create(['name' => 'Kyoto Center', 'status' => 'active']);

        // Test search functionality
        $response = $this->get('/facilities?search=Tokyo');
        $response->assertStatus(200);
        $response->assertSee('Tokyo Office');
        $response->assertDontSee('Osaka Branch');

        // Test status filtering
        $response = $this->get('/facilities?status=active');
        $response->assertStatus(200);
        $response->assertSee('Tokyo Office');
        $response->assertSee('Kyoto Center');
        $response->assertDontSee('Osaka Branch');
    }

    /** @test */
    public function error_handling_works_correctly()
    {
        $this->actingAs($this->editor);

        // Test validation errors
        $response = $this->post('/facilities', [
            'name' => '', // Required field missing
            'facility_code' => 'INVALID CODE WITH SPACES', // Invalid format
        ]);

        $response->assertSessionHasErrors(['name', 'facility_code']);

        // Test 404 handling
        $response = $this->get('/facilities/99999');
        $response->assertStatus(404);

        // Test unauthorized access
        $this->actingAs($this->viewer);
        $response = $this->post('/facilities', [
            'name' => 'Test Facility',
            'facility_code' => 'TEST001',
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_and_statistics_work()
    {
        $this->actingAs($this->admin);

        // Create test data for statistics
        Facility::factory()->count(10)->create(['status' => 'active']);
        Facility::factory()->count(3)->create(['status' => 'inactive']);

        // Test dashboard access
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Should show facility counts
        $response->assertSee('13'); // Total facilities
        $response->assertSee('10'); // Active facilities
        $response->assertSee('3');  // Inactive facilities
    }

    /** @test */
    public function maintenance_and_activity_logging_works()
    {
        $this->actingAs($this->admin);

        // Perform an action that should be logged
        $response = $this->put("/facilities/{$this->facility->id}", [
            'name' => 'Updated Facility Name',
            'facility_code' => $this->facility->facility_code,
            'address' => $this->facility->address,
            'status' => $this->facility->status,
            'facility_type' => $this->facility->facility_type,
        ]);

        $response->assertRedirect();

        // Check that activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Facility::class,
            'subject_id' => $this->facility->id,
            'description' => 'updated',
            'causer_id' => $this->admin->id,
        ]);
    }
}
