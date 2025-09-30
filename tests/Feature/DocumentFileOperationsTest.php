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

class DocumentFileOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $facility;
    protected $folder;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create();
        $this->folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_upload_multiple_files()
    {
        Storage::fake('public');
        
        $this->actingAs($this->user);
        
        $files = [
            UploadedFile::fake()->create('document1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 2048, 'application/pdf'),
        ];
        
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => $files,
            'folder_id' => $this->folder->id,
        ]);
        
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'files' => [
                ['name' => 'document1.pdf'],
                ['name' => 'document2.pdf'],
            ]
        ]);
        
        $this->assertDatabaseCount('document_files', 2);
    }

    /** @test */
    public function user_can_get_file_properties()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->actingAs($this->user);
        
        $response = $this->getJson(route('facilities.documents.files.properties', [$this->facility, $file]));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'type' => 'file',
                'name' => $file->original_name,
                'size' => $file->file_size,
            ]
        ]);
    }

    /** @test */
    public function user_can_rename_file()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $this->folder->id,
            'uploaded_by' => $this->user->id,
            'original_name' => 'old_name.pdf'
        ]);
        
        $this->actingAs($this->user);
        
        $response = $this->putJson(route('facilities.documents.files.rename', [$this->facility, $file]), [
            'name' => 'new_name.pdf'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'ファイル名を変更しました。'
        ]);
        
        $this->assertDatabaseHas('document_files', [
            'id' => $file->id,
            'original_name' => 'new_name.pdf'
        ]);
    }

    /** @test */
    public function user_can_get_folder_properties()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson(route('facilities.documents.folders.properties', [$this->facility, $this->folder]));
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'type' => 'folder',
                'name' => $this->folder->name,
            ]
        ]);
    }

    /** @test */
    public function upload_validates_file_types()
    {
        Storage::fake('public');
        
        $this->actingAs($this->user);
        
        $invalidFile = UploadedFile::fake()->create('malicious.exe', 1024, 'application/x-executable');
        
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [$invalidFile],
            'folder_id' => $this->folder->id,
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function upload_validates_file_size()
    {
        Storage::fake('public');
        
        $this->actingAs($this->user);
        
        // Create a file larger than 10MB
        $largeFile = UploadedFile::fake()->create('large.pdf', 11 * 1024, 'application/pdf');
        
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [$largeFile],
            'folder_id' => $this->folder->id,
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function upload_validates_max_files()
    {
        Storage::fake('public');
        
        $this->actingAs($this->user);
        
        // Create 11 files (exceeds limit of 10)
        $files = [];
        for ($i = 1; $i <= 11; $i++) {
            $files[] = UploadedFile::fake()->create("document{$i}.pdf", 1024, 'application/pdf');
        }
        
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => $files,
            'folder_id' => $this->folder->id,
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function unauthorized_user_cannot_access_file_operations()
    {
        // Create a user with no edit permissions
        $otherUser = User::factory()->create(['role' => 'viewer']);
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->actingAs($otherUser);
        
        // Test file rename (requires edit permissions)
        $response = $this->putJson(route('facilities.documents.files.rename', [$this->facility, $file]), [
            'name' => 'new_name.pdf'
        ]);
        $response->assertStatus(403);
        
        // Test file upload (requires edit permissions)
        $response = $this->postJson(route('facilities.documents.files.store', $this->facility), [
            'files' => [UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf')],
        ]);
        $response->assertStatus(403);
    }
}