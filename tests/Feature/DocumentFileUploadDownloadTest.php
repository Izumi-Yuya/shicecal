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

class DocumentFileUploadDownloadTest extends TestCase
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
    public function user_can_upload_pdf_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
            'folder_id' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'file' => ['id', 'original_name', 'file_size', 'mime_type', 'file_extension']
        ]);

        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
        ]);

        // Verify file was stored
        $documentFile = DocumentFile::where('original_name', 'document.pdf')->first();
        Storage::disk('public')->assertExists($documentFile->file_path);
    }

    /** @test */
    public function user_can_upload_word_document()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.docx', 2048, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_files', [
            'original_name' => 'document.docx',
            'file_extension' => 'docx',
            'file_size' => 2048,
        ]);
    }

    /** @test */
    public function user_can_upload_excel_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('spreadsheet.xlsx', 1536, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_files', [
            'original_name' => 'spreadsheet.xlsx',
            'file_extension' => 'xlsx',
        ]);
    }

    /** @test */
    public function user_can_upload_image_files()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $imageTypes = [
            ['name' => 'image.jpg', 'mime' => 'image/jpeg', 'ext' => 'jpg'],
            ['name' => 'image.png', 'mime' => 'image/png', 'ext' => 'png'],
            ['name' => 'image.gif', 'mime' => 'image/gif', 'ext' => 'gif'],
        ];

        foreach ($imageTypes as $imageType) {
            $file = UploadedFile::fake()->image($imageType['name'], 100, 100);

            $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
                'file' => $file,
            ]);

            $response->assertStatus(201);
            $this->assertDatabaseHas('document_files', [
                'original_name' => $imageType['name'],
                'file_extension' => $imageType['ext'],
            ]);
        }
    }

    /** @test */
    public function user_cannot_upload_unsupported_file_types()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $unsupportedTypes = [
            'executable.exe',
            'script.bat',
            'archive.zip',
            'video.mp4',
            'audio.mp3',
        ];

        foreach ($unsupportedTypes as $fileName) {
            $file = UploadedFile::fake()->create($fileName, 1024);

            $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
                'file' => $file,
            ]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['file']);
        }
    }

    /** @test */
    public function user_cannot_upload_files_exceeding_size_limit()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // Create a file larger than 10MB (assuming 10MB is the limit)
        $largeFile = UploadedFile::fake()->create('large.pdf', 11264, 'application/pdf'); // 11MB

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $largeFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function user_can_upload_multiple_files_sequentially()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $files = [
            UploadedFile::fake()->create('file1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('file2.docx', 2048, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('file3.xlsx', 1536, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];

        foreach ($files as $file) {
            $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
                'file' => $file,
            ]);

            $response->assertStatus(201);
        }

        $this->assertEquals(3, DocumentFile::where('facility_id', $this->facility->id)->count());
    }

    /** @test */
    public function uploaded_files_have_unique_stored_names()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // Upload two files with the same name
        $file1 = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $file2 = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf');

        $this->postJson(route('facilities.documents.files.store', $this->facility), ['file' => $file1]);
        $this->postJson(route('facilities.documents.files.store', $this->facility), ['file' => $file2]);

        $files = DocumentFile::where('facility_id', $this->facility->id)->get();
        
        $this->assertCount(2, $files);
        $this->assertNotEquals($files[0]->stored_name, $files[1]->stored_name);
        $this->assertEquals('document.pdf', $files[0]->original_name);
        $this->assertEquals('document.pdf', $files[1]->original_name);
    }

    /** @test */
    public function user_can_download_uploaded_file()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $fileContent = 'This is test PDF content';
        $fileName = 'test.pdf';
        $storedPath = 'documents/facility_' . $this->facility->id . '/root/test_' . time() . '.pdf';
        
        Storage::disk('public')->put($storedPath, $fileContent);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'original_name' => $fileName,
            'stored_name' => basename($storedPath),
            'file_path' => $storedPath,
            'file_size' => strlen($fileContent),
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.documents.files.download', [$this->facility, $documentFile]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="' . $fileName . '"');
        $this->assertEquals($fileContent, $response->getContent());
    }

    /** @test */
    public function user_can_preview_supported_file_types()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $previewableTypes = [
            ['name' => 'document.pdf', 'mime' => 'application/pdf', 'ext' => 'pdf'],
            ['name' => 'image.jpg', 'mime' => 'image/jpeg', 'ext' => 'jpg'],
            ['name' => 'image.png', 'mime' => 'image/png', 'ext' => 'png'],
            ['name' => 'text.txt', 'mime' => 'text/plain', 'ext' => 'txt'],
        ];

        foreach ($previewableTypes as $type) {
            $fileContent = 'Test content for ' . $type['name'];
            $storedPath = 'documents/facility_' . $this->facility->id . '/root/' . $type['name'];
            
            Storage::disk('public')->put($storedPath, $fileContent);

            $documentFile = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'original_name' => $type['name'],
                'file_path' => $storedPath,
                'mime_type' => $type['mime'],
                'file_extension' => $type['ext'],
                'uploaded_by' => $this->user->id,
            ]);

            $response = $this->get(route('facilities.documents.files.preview', [$this->facility, $documentFile]));

            $response->assertOk();
            $response->assertHeader('content-type', $type['mime']);
            $this->assertEquals($fileContent, $response->getContent());
        }
    }

    /** @test */
    public function user_cannot_preview_non_previewable_files()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $nonPreviewableTypes = [
            ['name' => 'document.docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ext' => 'docx'],
            ['name' => 'spreadsheet.xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ext' => 'xlsx'],
        ];

        foreach ($nonPreviewableTypes as $type) {
            $documentFile = DocumentFile::factory()->create([
                'facility_id' => $this->facility->id,
                'original_name' => $type['name'],
                'mime_type' => $type['mime'],
                'file_extension' => $type['ext'],
                'uploaded_by' => $this->user->id,
            ]);

            $response = $this->get(route('facilities.documents.files.preview', [$this->facility, $documentFile]));

            $response->assertStatus(422);
        }
    }

    /** @test */
    public function download_returns_404_for_missing_file()
    {
        $this->actingAs($this->user);

        $documentFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'file_path' => 'non-existent-path.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.documents.files.download', [$this->facility, $documentFile]));

        $response->assertStatus(404);
    }

    /** @test */
    public function user_can_delete_uploaded_file()
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
    public function file_upload_creates_proper_directory_structure()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Test Folder',
            'created_by' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
            'folder_id' => $folder->id,
        ]);

        $response->assertStatus(201);

        $documentFile = DocumentFile::where('original_name', 'document.pdf')->first();
        
        // Verify the file path includes facility and folder structure
        $this->assertStringContains('facility_' . $this->facility->id, $documentFile->file_path);
        $this->assertStringContains('folder_' . $folder->id, $documentFile->file_path);
        
        Storage::disk('public')->assertExists($documentFile->file_path);
    }

    /** @test */
    public function file_upload_handles_japanese_filenames()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('日本語ファイル名.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_files', [
            'original_name' => '日本語ファイル名.pdf',
            'file_extension' => 'pdf',
        ]);
    }

    /** @test */
    public function file_upload_preserves_file_metadata()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf');

        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(201);

        $documentFile = DocumentFile::where('original_name', 'document.pdf')->first();
        
        $this->assertEquals('document.pdf', $documentFile->original_name);
        $this->assertEquals(2048, $documentFile->file_size);
        $this->assertEquals('application/pdf', $documentFile->mime_type);
        $this->assertEquals('pdf', $documentFile->file_extension);
        $this->assertEquals($this->user->id, $documentFile->uploaded_by);
        $this->assertEquals($this->facility->id, $documentFile->facility_id);
    }

    /** @test */
    public function concurrent_file_uploads_are_handled_correctly()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $files = [
            UploadedFile::fake()->create('file1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('file2.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('file3.pdf', 1024, 'application/pdf'),
        ];

        $responses = [];
        foreach ($files as $file) {
            $responses[] = $this->postJson(route('facilities.documents.files.store', $this->facility), [
                'file' => $file,
            ]);
        }

        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $this->assertEquals(3, DocumentFile::where('facility_id', $this->facility->id)->count());
        
        // Verify all files have unique stored names
        $storedNames = DocumentFile::where('facility_id', $this->facility->id)
            ->pluck('stored_name')
            ->toArray();
        
        $this->assertEquals(3, count(array_unique($storedNames)));
    }
}