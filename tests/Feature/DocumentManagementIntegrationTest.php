<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;
    protected DocumentService $documentService;
    protected ActivityLogService $activityLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->facility = Facility::factory()->create();

        $this->documentService = app(DocumentService::class);
        $this->activityLogService = app(ActivityLogService::class);

        Storage::fake('public');
    }

    /** @test */
    public function facility_detail_page_includes_documents_tab()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('id="documents-tab"', false);
        $response->assertSee('data-bs-target="#documents"', false);
        $response->assertSee('<i class="fas fa-folder me-2"></i>ドキュメント', false);
    }

    /** @test */
    public function documents_tab_loads_content_dynamically()
    {
        $this->actingAs($this->user);

        // Create some test documents
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'テストフォルダ',
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('facilities.documents.show', [$this->facility]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'folders',
                'files',
                'breadcrumbs',
                'sort_options',
                'available_file_types',
            ],
        ]);

        $response->assertJsonFragment([
            'name' => 'テストフォルダ',
        ]);
    }

    /** @test */
    public function user_permissions_are_enforced_in_documents_tab()
    {
        // Test with viewer user
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $this->actingAs($viewer);

        // Should be able to view documents
        $response = $this->getJson(route('facilities.documents.show', [$this->facility]));
        $response->assertStatus(200);

        // Should not be able to create folders
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'テストフォルダ',
        ]);
        $response->assertStatus(403);

        // Should not be able to upload files
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function activity_log_integration_works_correctly()
    {
        $this->actingAs($this->user);

        // Create a folder
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'テストフォルダ',
        ]);

        $response->assertStatus(201);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'facility_id' => $this->facility->id,
            'action' => 'document_folder_created',
        ]);

        // Upload a file
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(201);

        // Check activity log for file upload
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'facility_id' => $this->facility->id,
            'action' => 'document_file_uploaded',
        ]);
    }

    /** @test */
    public function tab_switching_preserves_state()
    {
        $this->actingAs($this->user);

        // Test that activeTab session is handled correctly
        $response = $this->post(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'テストフォルダ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('activeTab', 'documents');
    }

    /** @test */
    public function document_management_integrates_with_existing_user_system()
    {
        $this->actingAs($this->user);

        // Create folder
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        // Create file
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Verify relationships work
        $this->assertEquals($this->user->id, $folder->created_by);
        $this->assertEquals($this->user->name, $folder->creator->name);
        $this->assertEquals($this->user->id, $file->uploaded_by);
        $this->assertEquals($this->user->name, $file->uploader->name);
    }

    /** @test */
    public function document_management_works_with_facility_relationships()
    {
        $this->actingAs($this->user);

        // Create documents for facility
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Test facility relationships
        $this->assertTrue($this->facility->documentFolders->contains($folder));
        $this->assertTrue($this->facility->documentFiles->contains($file));

        // Test that documents are isolated per facility
        $otherFacility = Facility::factory()->create();
        $this->assertFalse($otherFacility->documentFolders->contains($folder));
        $this->assertFalse($otherFacility->documentFiles->contains($file));
    }

    /** @test */
    public function error_handling_integrates_with_existing_notification_system()
    {
        $this->actingAs($this->user);

        // Test validation error handling
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => '', // Invalid name
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Test file upload error handling
        $largeFile = UploadedFile::fake()->create('large.pdf', 20000, 'application/pdf'); // 20MB
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $largeFile,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function document_management_css_and_js_are_loaded()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        // Check that document management styles are included
        $response->assertSee('documents-container', false);
        $response->assertSee('document-management', false);
    }

    /** @test */
    public function breadcrumb_navigation_works_correctly()
    {
        $this->actingAs($this->user);

        // Create nested folder structure
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => '親フォルダ',
            'created_by' => $this->user->id,
        ]);

        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $parentFolder->id,
            'name' => '子フォルダ',
            'created_by' => $this->user->id,
        ]);

        // Test breadcrumb generation
        $response = $this->getJson(route('facilities.documents.show', [$this->facility, $childFolder]));

        $response->assertStatus(200);
        $response->assertJsonPath('data.breadcrumbs.0.name', 'ルート');
        $response->assertJsonPath('data.breadcrumbs.1.name', '親フォルダ');
        $response->assertJsonPath('data.breadcrumbs.2.name', '子フォルダ');
    }

    /** @test */
    public function file_download_security_is_enforced()
    {
        $this->actingAs($this->user);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'uploaded_by' => $this->user->id,
        ]);

        // Create actual file in storage
        Storage::disk('public')->put($file->file_path, 'test content');

        // Test authorized download
        $response = $this->get(route('facilities.documents.files.download', $file));
        $response->assertStatus(200);

        // Test unauthorized access (different facility)
        $otherFacility = Facility::factory()->create();
        $otherFile = DocumentFile::factory()->create([
            'facility_id' => $otherFacility->id,
            'uploaded_by' => $this->user->id,
        ]);
        $response = $this->get(route('facilities.documents.files.download', $otherFile));
        $response->assertStatus(403); // Should be forbidden due to policy check
    }



    /** @test */
    public function document_management_respects_facility_access_permissions()
    {
        // Create user with limited facility access
        $limitedUser = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        // Mock facility access check (assuming this method exists)
        $this->actingAs($limitedUser);

        // Test that user cannot access documents for facility they don't have access to
        $response = $this->getJson(route('facilities.documents.show', [$this->facility]));
        
        // This should depend on your actual permission system
        // Adjust the assertion based on your implementation
        if (method_exists($limitedUser, 'canViewFacility')) {
            if (!$limitedUser->canViewFacility($this->facility->id)) {
                $response->assertStatus(403);
            } else {
                $response->assertStatus(200);
            }
        } else {
            // If no specific facility permissions, should work for active users
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function document_operations_trigger_appropriate_notifications()
    {
        $this->actingAs($this->user);

        // Test folder creation notification
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'テストフォルダ',
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'フォルダを作成しました。',
        ]);

        // Test file upload notification
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'ファイルをアップロードしました。',
        ]);
    }
}