<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
        
        // Mock user permissions
        $this->user->shouldReceive('canViewFacility')
            ->with($this->facility->id)
            ->andReturn(true);
        $this->user->shouldReceive('canEditFacility')
            ->with($this->facility->id)
            ->andReturn(true);
    }

    /** @test */
    public function user_can_access_document_management_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.documents.index', $this->facility));

        $response->assertOk();
        $response->assertViewIs('facilities.documents.index');
        $response->assertViewHas('facility', $this->facility);
    }

    /** @test */
    public function user_can_view_root_folder_contents()
    {
        $this->actingAs($this->user);

        // Create test data
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('facilities.documents.show', [$this->facility, 'root']));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'folders' => [
                '*' => ['id', 'name', 'created_at', 'file_count', 'creator']
            ],
            'files' => [
                '*' => ['id', 'original_name', 'file_size', 'created_at', 'uploader']
            ],
            'breadcrumbs',
            'current_folder'
        ]);
    }

    /** @test */
    public function user_can_view_specific_folder_contents()
    {
        $this->actingAs($this->user);

        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $childFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => $parentFolder->id,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $parentFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('facilities.documents.show', [$this->facility, $parentFolder->id]));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $childFolder->id]);
        $response->assertJsonFragment(['id' => $file->id]);
    }

    /** @test */
    public function user_can_create_folder()
    {
        $this->actingAs($this->user);

        $folderData = [
            'name' => 'Test Folder',
            'parent_id' => null,
        ];

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), $folderData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'folder' => ['id', 'name', 'path', 'created_at']
        ]);

        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_create_nested_folder()
    {
        $this->actingAs($this->user);

        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $folderData = [
            'name' => 'Child Folder',
            'parent_id' => $parentFolder->id,
        ];

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), $folderData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Child Folder',
            'parent_id' => $parentFolder->id,
        ]);
    }

    /** @test */
    public function user_can_rename_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Old Name',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson(route('facilities.documents.folders.update', [$this->facility, $folder]), [
            'name' => 'New Name'
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('document_folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function user_can_delete_empty_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('facilities.documents.folders.destroy', [$this->facility, $folder]));

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);

        $this->assertDatabaseMissing('document_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function user_cannot_delete_non_empty_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('facilities.documents.folders.destroy', [$this->facility, $folder]));

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);

        $this->assertDatabaseHas('document_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function user_can_upload_file_to_root()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
            'folder_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'file' => ['id', 'original_name', 'file_size', 'created_at']
        ]);

        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'original_name' => 'test.pdf',
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_upload_file_to_folder()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'original_name' => 'test.pdf',
        ]);
    }

    /** @test */
    public function user_can_download_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $fileContent = 'test file content';
        $fileName = 'test.pdf';
        $storedPath = 'documents/facility_' . $this->facility->id . '/root/' . $fileName;
        
        Storage::disk('public')->put($storedPath, $fileContent);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => $fileName,
            'stored_name' => $fileName,
            'file_path' => $storedPath,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.documents.files.download', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename="' . $fileName . '"');
        $this->assertEquals($fileContent, $response->getContent());
    }

    /** @test */
    public function user_can_preview_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $fileContent = 'test file content';
        $fileName = 'test.pdf';
        $storedPath = 'documents/facility_' . $this->facility->id . '/root/' . $fileName;
        
        Storage::disk('public')->put($storedPath, $fileContent);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => $fileName,
            'stored_name' => $fileName,
            'file_path' => $storedPath,
            'file_extension' => 'pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.documents.files.preview', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function user_can_delete_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $fileName = 'test.pdf';
        $storedPath = 'documents/facility_' . $this->facility->id . '/root/' . $fileName;
        
        Storage::disk('public')->put($storedPath, 'test content');

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'file_path' => $storedPath,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('facilities.documents.files.destroy', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);

        $this->assertDatabaseMissing('document_files', ['id' => $documentFile->id]);
        Storage::disk('public')->assertMissing($storedPath);
    }

    /** @test */
    public function api_validates_folder_name_requirements()
    {
        $this->actingAs($this->user);

        // Test empty name
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => '',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Test name too long
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => str_repeat('a', 256),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);

        // Test invalid characters
        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'folder/with/slashes',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function api_validates_file_upload_requirements()
    {
        $this->actingAs($this->user);

        // Test missing file
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('test.exe', 1024, 'application/x-executable');
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $invalidFile,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        // Test file too large
        $largeFile = UploadedFile::fake()->create('large.pdf', 20480, 'application/pdf'); // 20MB
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $largeFile,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function api_prevents_duplicate_folder_names_in_same_directory()
    {
        $this->actingAs($this->user);

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Existing Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Existing Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function api_allows_same_folder_names_in_different_directories()
    {
        $this->actingAs($this->user);

        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Same Name',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Same Name',
            'parent_id' => $parentFolder->id,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function api_returns_proper_error_for_non_existent_folder()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.documents.show', [$this->facility, 99999]));

        $response->assertStatus(404);
    }

    /** @test */
    public function api_returns_proper_error_for_non_existent_file()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.documents.files.download', [$this->facility, 99999]));

        $response->assertStatus(404);
    }

    /** @test */
    public function api_handles_concurrent_folder_operations()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        // Simulate concurrent rename operations
        $response1 = $this->putJson(route('facilities.documents.folders.update', [$this->facility, $folder]), [
            'name' => 'New Name 1'
        ]);

        $response2 = $this->putJson(route('facilities.documents.folders.update', [$this->facility, $folder]), [
            'name' => 'New Name 2'
        ]);

        // Both should succeed (last one wins)
        $response1->assertOk();
        $response2->assertOk();

        $folder->refresh();
        $this->assertEquals('New Name 2', $folder->name);
    }
}