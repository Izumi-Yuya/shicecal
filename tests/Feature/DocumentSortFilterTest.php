<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\UserPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSortFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Facility $facility;
    protected UserPreferenceService $userPreferenceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();
        $this->userPreferenceService = app(UserPreferenceService::class);

        Storage::fake('public');
    }

    /** @test */
    public function user_can_sort_documents_by_name()
    {
        $this->actingAs($this->user);

        // Create test folders and files
        $folderA = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Folder A',
            'parent_id' => null,
        ]);

        $folderB = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Folder B',
            'parent_id' => null,
        ]);

        $fileA = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Document A.pdf',
            'folder_id' => null,
        ]);

        $fileB = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Document B.pdf',
            'folder_id' => null,
        ]);

        // Test ascending sort
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Folder A', $data['folders'][0]['name']);
        $this->assertEquals('Folder B', $data['folders'][1]['name']);
        $this->assertEquals('Document A.pdf', $data['files'][0]['name']);
        $this->assertEquals('Document B.pdf', $data['files'][1]['name']);

        // Test descending sort
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'name',
            'sort_direction' => 'desc'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Folder B', $data['folders'][0]['name']);
        $this->assertEquals('Folder A', $data['folders'][1]['name']);
        $this->assertEquals('Document B.pdf', $data['files'][0]['name']);
        $this->assertEquals('Document A.pdf', $data['files'][1]['name']);
    }

    /** @test */
    public function user_can_sort_documents_by_date()
    {
        $this->actingAs($this->user);

        // Create test files with different dates
        $oldFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Old Document.pdf',
            'folder_id' => null,
            'created_at' => now()->subDays(2),
        ]);

        $newFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'New Document.pdf',
            'folder_id' => null,
            'created_at' => now(),
        ]);

        // Test ascending sort (oldest first)
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'date',
            'sort_direction' => 'asc'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('Old Document.pdf', $data['files'][0]['name']);
        $this->assertEquals('New Document.pdf', $data['files'][1]['name']);

        // Test descending sort (newest first)
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'date',
            'sort_direction' => 'desc'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('New Document.pdf', $data['files'][0]['name']);
        $this->assertEquals('Old Document.pdf', $data['files'][1]['name']);
    }

    /** @test */
    public function user_can_filter_documents_by_file_type()
    {
        $this->actingAs($this->user);

        // Create test files with different extensions
        $pdfFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Document.pdf',
            'file_extension' => 'pdf',
            'folder_id' => null,
        ]);

        $docFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Document.docx',
            'file_extension' => 'docx',
            'folder_id' => null,
        ]);

        // Test filtering by PDF
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'filter_type' => 'pdf'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data['files']);
        $this->assertEquals('Document.pdf', $data['files'][0]['name']);

        // Test filtering by DOCX
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'filter_type' => 'docx'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data['files']);
        $this->assertEquals('Document.docx', $data['files'][0]['name']);
    }

    /** @test */
    public function user_can_search_documents()
    {
        $this->actingAs($this->user);

        // Create test folders and files
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Important Folder',
            'parent_id' => null,
        ]);

        $matchingFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Important Document.pdf',
            'folder_id' => null,
        ]);

        $nonMatchingFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'Regular File.pdf',
            'folder_id' => null,
        ]);

        // Test search
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'search' => 'Important'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data['folders']);
        $this->assertEquals('Important Folder', $data['folders'][0]['name']);
        $this->assertCount(1, $data['files']);
        $this->assertEquals('Important Document.pdf', $data['files'][0]['name']);
    }

    /** @test */
    public function user_preferences_are_persisted_in_session()
    {
        $this->actingAs($this->user);

        // Set preferences
        $settings = [
            'sort_by' => 'date',
            'sort_direction' => 'desc',
            'view_mode' => 'icon',
            'filter_type' => 'pdf',
        ];

        $this->userPreferenceService->saveDocumentSettings($this->facility->id, $settings);

        // Verify preferences are saved
        $savedSettings = $this->userPreferenceService->getDocumentSettings($this->facility->id);

        $this->assertEquals('date', $savedSettings['sort_by']);
        $this->assertEquals('desc', $savedSettings['sort_direction']);
        $this->assertEquals('icon', $savedSettings['view_mode']);
        $this->assertEquals('pdf', $savedSettings['filter_type']);
    }

    /** @test */
    public function user_can_reset_preferences()
    {
        $this->actingAs($this->user);

        // Set custom preferences
        $this->userPreferenceService->saveDocumentSettings($this->facility->id, [
            'sort_by' => 'date',
            'sort_direction' => 'desc',
            'view_mode' => 'icon',
        ]);

        // Reset preferences
        $response = $this->postJson(route('facilities.documents.preferences.reset', $this->facility));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => '表示設定をリセットしました。'
        ]);

        // Verify preferences are reset to defaults
        $settings = $this->userPreferenceService->getDocumentSettings($this->facility->id);

        $this->assertEquals('name', $settings['sort_by']);
        $this->assertEquals('asc', $settings['sort_direction']);
        $this->assertEquals('list', $settings['view_mode']);
        $this->assertNull($settings['filter_type']);
    }

    /** @test */
    public function folders_are_always_displayed_first_regardless_of_sort()
    {
        $this->actingAs($this->user);

        // Create folder and file with names that would sort differently
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Z Folder',
            'parent_id' => null,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => 'A Document.pdf',
            'folder_id' => null,
        ]);

        // Test with name sort ascending
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        // Folder should come first even though its name starts with 'Z'
        $this->assertCount(1, $data['folders']);
        $this->assertCount(1, $data['files']);
        $this->assertEquals('Z Folder', $data['folders'][0]['name']);
        $this->assertEquals('A Document.pdf', $data['files'][0]['name']);
    }

    /** @test */
    public function invalid_sort_parameters_are_ignored()
    {
        $this->actingAs($this->user);

        // Test with invalid sort parameters
        $response = $this->getJson(route('facilities.documents.show', [
            'facility' => $this->facility,
            'sort_by' => 'invalid_sort',
            'sort_direction' => 'invalid_direction'
        ]));

        $response->assertOk();
        $data = $response->json('data');

        // Should fall back to default sorting
        $this->assertEquals('name', $data['sort_options']['sort_by']);
        $this->assertEquals('asc', $data['sort_options']['sort_direction']);
    }

    /** @test */
    public function user_preference_service_validates_settings()
    {
        // Test valid settings
        $validSettings = [
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'view_mode' => 'icon',
            'filter_type' => 'pdf',
            'search' => 'test search',
        ];

        $validated = $this->userPreferenceService->validateDocumentSettings($validSettings);

        $this->assertEquals($validSettings, $validated);

        // Test invalid settings
        $invalidSettings = [
            'sort_by' => 'invalid_sort',
            'sort_direction' => 'invalid_direction',
            'view_mode' => 'invalid_mode',
            'filter_type' => 123, // Should be string or null
            'search' => ['invalid'], // Should be string or null
        ];

        $validated = $this->userPreferenceService->validateDocumentSettings($invalidSettings);

        $this->assertEmpty($validated);
    }
}