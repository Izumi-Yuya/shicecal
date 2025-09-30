<?php

namespace Tests\Unit\Services;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DocumentService;
use App\Services\FileHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $documentService;
    protected FileHandlingService $fileHandlingService;
    protected ActivityLogService $activityLogService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHandlingService = Mockery::mock(FileHandlingService::class);
        $this->activityLogService = Mockery::mock(ActivityLogService::class);
        
        $this->documentService = new DocumentService(
            $this->fileHandlingService,
            $this->activityLogService
        );

        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_folder_successfully()
    {
        $folderName = 'Test Folder';
        
        $this->activityLogService
            ->shouldReceive('logDocumentFolderCreated')
            ->once()
            ->with($this->facility, Mockery::type(DocumentFolder::class), $this->user);

        $folder = $this->documentService->createFolder(
            $this->facility,
            null,
            $folderName,
            $this->user
        );

        $this->assertInstanceOf(DocumentFolder::class, $folder);
        $this->assertEquals($folderName, $folder->name);
        $this->assertEquals($this->facility->id, $folder->facility_id);
        $this->assertEquals($this->user->id, $folder->created_by);
        $this->assertNull($folder->parent_id);
        $this->assertEquals($folderName, $folder->path);
    }

    /** @test */
    public function it_creates_nested_folder_successfully()
    {
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Parent',
            'path' => 'Parent',
            'created_by' => $this->user->id,
        ]);

        $folderName = 'Child Folder';
        
        $this->activityLogService
            ->shouldReceive('logDocumentFolderCreated')
            ->once()
            ->with($this->facility, Mockery::type(DocumentFolder::class), $this->user);

        $folder = $this->documentService->createFolder(
            $this->facility,
            $parentFolder,
            $folderName,
            $this->user
        );

        $this->assertEquals($parentFolder->id, $folder->parent_id);
        $this->assertEquals('Parent/Child Folder', $folder->path);
    }

    /** @test */
    public function it_renames_folder_successfully()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Old Name',
            'path' => 'Old Name',
            'created_by' => $this->user->id,
        ]);

        $newName = 'New Name';
        
        $this->activityLogService
            ->shouldReceive('logDocumentFolderRenamed')
            ->once()
            ->with($this->facility, $folder, 'Old Name', $newName, $this->user);

        $renamedFolder = $this->documentService->renameFolder($folder, $newName, $this->user);

        $this->assertEquals($newName, $renamedFolder->name);
        $this->assertEquals($newName, $renamedFolder->path);
    }

    /** @test */
    public function it_deletes_empty_folder_successfully()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $this->activityLogService
            ->shouldReceive('logDocumentFolderDeleted')
            ->once()
            ->with($this->facility, $folder->name, $this->user);

        $result = $this->documentService->deleteFolder($folder, $this->user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('document_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_non_empty_folder()
    {
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $folder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('フォルダが空でないため削除できません。');

        $this->documentService->deleteFolder($folder, $this->user);
    }

    /** @test */
    public function it_uploads_file_successfully()
    {
        Storage::fake('public');
        
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $uploadedFile = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        
        $this->fileHandlingService
            ->shouldReceive('uploadFile')
            ->once()
            ->with($uploadedFile, Mockery::type('string'), 'document')
            ->andReturn([
                'success' => true,
                'filename' => 'test.pdf',
                'stored_filename' => 'stored_test_123.pdf',
                'path' => 'documents/facility_1/folder_1/stored_test_123.pdf',
            ]);

        $this->activityLogService
            ->shouldReceive('logDocumentUploaded')
            ->once()
            ->with($this->facility, Mockery::type(DocumentFile::class), $this->user);

        $documentFile = $this->documentService->uploadFile(
            $this->facility,
            $folder,
            $uploadedFile,
            $this->user
        );

        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('test.pdf', $documentFile->original_name);
        $this->assertEquals('stored_test_123.pdf', $documentFile->stored_name);
        $this->assertEquals($this->facility->id, $documentFile->facility_id);
        $this->assertEquals($folder->id, $documentFile->folder_id);
        $this->assertEquals($this->user->id, $documentFile->uploaded_by);
    }

    /** @test */
    public function it_uploads_file_to_root_successfully()
    {
        Storage::fake('public');
        
        $uploadedFile = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        
        $this->fileHandlingService
            ->shouldReceive('uploadFile')
            ->once()
            ->with($uploadedFile, Mockery::type('string'), 'document')
            ->andReturn([
                'success' => true,
                'filename' => 'test.pdf',
                'stored_filename' => 'stored_test_123.pdf',
                'path' => 'documents/facility_1/root/stored_test_123.pdf',
            ]);

        $this->activityLogService
            ->shouldReceive('logDocumentUploaded')
            ->once()
            ->with($this->facility, Mockery::type(DocumentFile::class), $this->user);

        $documentFile = $this->documentService->uploadFile(
            $this->facility,
            null,
            $uploadedFile,
            $this->user
        );

        $this->assertNull($documentFile->folder_id);
    }

    /** @test */
    public function it_deletes_file_successfully()
    {
        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->fileHandlingService
            ->shouldReceive('deleteFile')
            ->once()
            ->with($file->file_path)
            ->andReturn(true);

        $this->activityLogService
            ->shouldReceive('logDocumentDeleted')
            ->once()
            ->with($this->facility, $file->original_name, $this->user);

        $result = $this->documentService->deleteFile($file, $this->user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('document_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_gets_folder_contents_correctly()
    {
        $parentFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
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

        $contents = $this->documentService->getFolderContents($this->facility, $parentFolder);

        $this->assertArrayHasKey('folders', $contents);
        $this->assertArrayHasKey('files', $contents);
        $this->assertCount(1, $contents['folders']);
        $this->assertCount(1, $contents['files']);
        $this->assertEquals($childFolder->id, $contents['folders'][0]->id);
        $this->assertEquals($file->id, $contents['files'][0]->id);
    }

    /** @test */
    public function it_gets_root_folder_contents_correctly()
    {
        $rootFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $rootFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $contents = $this->documentService->getFolderContents($this->facility, null);

        $this->assertCount(1, $contents['folders']);
        $this->assertCount(1, $contents['files']);
        $this->assertEquals($rootFolder->id, $contents['folders'][0]->id);
        $this->assertEquals($rootFile->id, $contents['files'][0]->id);
    }

    /** @test */
    public function it_sorts_folder_contents_by_name()
    {
        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Z Folder',
            'created_by' => $this->user->id,
        ]);

        DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'A Folder',
            'created_by' => $this->user->id,
        ]);

        $contents = $this->documentService->getFolderContents($this->facility, null, [
            'sort' => 'name',
            'direction' => 'asc'
        ]);

        $this->assertEquals('A Folder', $contents['folders'][0]->name);
        $this->assertEquals('Z Folder', $contents['folders'][1]->name);
    }

    /** @test */
    public function it_generates_breadcrumbs_correctly()
    {
        $grandparent = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Grandparent',
            'path' => 'Grandparent',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $parent = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Parent',
            'path' => 'Grandparent/Parent',
            'parent_id' => $grandparent->id,
            'created_by' => $this->user->id,
        ]);

        $child = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'name' => 'Child',
            'path' => 'Grandparent/Parent/Child',
            'parent_id' => $parent->id,
            'created_by' => $this->user->id,
        ]);

        $breadcrumbs = $this->documentService->getBreadcrumbs($child);

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Grandparent', $breadcrumbs[0]['name']);
        $this->assertEquals('Parent', $breadcrumbs[1]['name']);
        $this->assertEquals('Child', $breadcrumbs[2]['name']);
    }

    /** @test */
    public function it_calculates_storage_stats_correctly()
    {
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'file_size' => 1024,
            'uploaded_by' => $this->user->id,
        ]);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'file_size' => 2048,
            'uploaded_by' => $this->user->id,
        ]);

        $stats = $this->documentService->getStorageStats($this->facility);

        $this->assertEquals(2, $stats['total_files']);
        $this->assertEquals(3072, $stats['total_size']);
        $this->assertEquals('3.00 KB', $stats['formatted_size']);
    }

    /** @test */
    public function it_moves_file_to_different_folder()
    {
        $sourceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $targetFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'created_by' => $this->user->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $sourceFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        $this->activityLogService
            ->shouldReceive('logDocumentMoved')
            ->once()
            ->with($this->facility, $file, $sourceFolder->name, $targetFolder->name, $this->user);

        $movedFile = $this->documentService->moveFile($file, $targetFolder, $this->user);

        $this->assertEquals($targetFolder->id, $movedFile->folder_id);
    }
}