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

class LifelineDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $this->facility = Facility::factory()->create();
        
        Storage::fake('public');
    }

    /** @test */
    public function user_can_view_lifeline_document_list()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.lifeline-documents.index', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]));

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'folders',
                        'files',
                        'current_folder',
                        'breadcrumbs',
                        'pagination',
                        'stats'
                    ],
                    'category',
                    'category_name'
                ]);
    }

    /** @test */
    public function user_can_upload_file_to_lifeline_category()
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test_report.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.lifeline-documents.upload', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]), [
            'file' => $file
        ]);

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'original_name' => 'test_report.pdf',
            'uploaded_by' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_create_folder_in_lifeline_category()
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('facilities.lifeline-documents.create-folder', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]), [
            'name' => 'Test Folder'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'created_by' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_rename_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Old Name',
            'created_by' => $this->user->id
        ]);

        $response = $this->putJson(route('facilities.lifeline-documents.rename-folder', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'folder' => $folder
        ]), [
            'name' => 'New Name'
        ]);

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $folder->id,
            'name' => 'New Name'
        ]);
    }

    /** @test */
    public function user_can_delete_empty_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->deleteJson(route('facilities.lifeline-documents.delete-folder', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'folder' => $folder
        ]));

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseMissing('document_folders', [
            'id' => $folder->id
        ]);
    }

    /** @test */
    public function user_can_rename_file()
    {
        $this->actingAs($this->user);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'old_name.pdf',
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->putJson(route('facilities.lifeline-documents.rename-file', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'file' => $file
        ]), [
            'name' => 'new_name.pdf'
        ]);

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('document_files', [
            'id' => $file->id,
            'original_name' => 'new_name.pdf'
        ]);
    }

    /** @test */
    public function user_can_delete_file()
    {
        $this->actingAs($this->user);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->deleteJson(route('facilities.lifeline-documents.delete-file', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'file' => $file
        ]));

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseMissing('document_files', [
            'id' => $file->id
        ]);
    }

    /** @test */
    public function user_can_move_file_to_folder()
    {
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->patchJson(route('facilities.lifeline-documents.move-file', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'file' => $file
        ]), [
            'folder_id' => $folder->id
        ]);

        $response->assertOk()
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseHas('document_files', [
            'id' => $file->id,
            'folder_id' => $folder->id
        ]);
    }

    /** @test */
    public function user_can_get_category_stats()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.lifeline-documents.stats', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]));

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'file_count',
                        'folder_count',
                        'total_size',
                        'formatted_size',
                        'recent_files'
                    ]
                ]);
    }

    /** @test */
    public function user_can_search_files_in_category()
    {
        $this->actingAs($this->user);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'test_report.pdf',
            'uploaded_by' => $this->user->id
        ]);

        $response = $this->getJson(route('facilities.lifeline-documents.search', [
            'facility' => $this->facility,
            'category' => 'electrical',
            'query' => 'test'
        ]));

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'files',
                        'folders',
                        'pagination',
                        'total_count'
                    ],
                    'query',
                    'category'
                ]);
    }

    /** @test */
    public function unauthorized_user_cannot_upload_files()
    {
        $unauthorizedUser = User::factory()->create([
            'role' => 'viewer'
        ]);

        $this->actingAs($unauthorizedUser);

        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.lifeline-documents.upload', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]), [
            'file' => $file
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function invalid_file_type_is_rejected()
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('test.exe', 1024, 'application/x-executable');

        $response = $this->postJson(route('facilities.lifeline-documents.upload', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]), [
            'file' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function file_size_limit_is_enforced()
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('large_file.pdf', 11 * 1024, 'application/pdf'); // 11MB

        $response = $this->postJson(route('facilities.lifeline-documents.upload', [
            'facility' => $this->facility,
            'category' => 'electrical'
        ]), [
            'file' => $file
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function invalid_category_returns_404()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('facilities.lifeline-documents.index', [
            'facility' => $this->facility,
            'category' => 'invalid_category'
        ]));

        $response->assertStatus(404);
    }
}