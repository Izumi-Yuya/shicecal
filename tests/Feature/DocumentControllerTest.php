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

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function user_can_view_document_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('facilities.documents.index', $this->facility));

        $response->assertOk();
        $response->assertViewIs('facilities.documents.index');
        $response->assertViewHas('facility', $this->facility);
    }

    /** @test */
    public function user_can_get_folder_contents_via_ajax()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.documents.show', $this->facility));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'folders',
                'files',
                'breadcrumbs',
                'current_folder',
                'sort_by',
                'sort_direction',
                'view_mode'
            ]
        ]);
    }

    /** @test */
    public function user_can_create_folder()
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Test Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'folder' => [
                'id',
                'name',
                'path',
                'created_at',
                'creator'
            ]
        ]);

        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'parent_id' => null,
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
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'folder' => [
                'id',
                'name',
                'path',
                'updated_at'
            ]
        ]);

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
        $response->assertJson([
            'success' => true,
            'message' => 'フォルダを削除しました。'
        ]);

        $this->assertDatabaseMissing('document_folders', [
            'id' => $folder->id,
        ]);
    }

    /** @test */
    public function user_can_upload_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [$file],
            'folder_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'file' => [
                'id',
                'name',
                'size',
                'type',
                'uploaded_at',
                'uploader',
                'download_url',
                'icon',
                'color'
            ]
        ]);

        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'original_name' => 'test.pdf',
            'uploaded_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_download_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // Create a test file
        $testContent = 'Test file content';
        $filePath = 'documents/facility_' . $this->facility->id . '/root/test.pdf';
        Storage::disk('public')->put($filePath, $testContent);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'test.pdf',
            'file_path' => $filePath,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.documents.files.download', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function user_can_delete_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('facilities.documents.files.destroy', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'ファイルを削除しました。'
        ]);

        $this->assertDatabaseMissing('document_files', [
            'id' => $documentFile->id,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_documents()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->get(route('facilities.documents.index', $this->facility));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_create_folder_with_duplicate_name()
    {
        $this->actingAs($this->user);

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Existing Folder',
            'parent_id' => null,
        ]);

        $response = $this->postJson(route('facilities.documents.folders.store', $this->facility), [
            'name' => 'Existing Folder',
            'parent_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }
}