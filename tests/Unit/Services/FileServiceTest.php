<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileService;
use App\Models\File;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class FileServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileService $fileService;
    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileService = new FileService();
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create();

        // Use fake storage for testing
        Storage::fake('local');
    }

    /**
     * Test uploading a valid land document
     * Requirements: 6.1, 6.2, 6.3
     */
    public function test_upload_valid_land_document()
    {
        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $result = $this->fileService->uploadLandDocument(
            $this->facility,
            $file,
            'lease_contract',
            $this->user
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertEquals($this->facility->id, $result->facility_id);
        $this->assertEquals('lease_contract', $result->land_document_type);
        $this->assertEquals($this->user->id, $result->uploaded_by);
        $this->assertEquals('contract.pdf', $result->original_name);

        // Check file was stored
        Storage::assertExists($result->file_path);

        // Check database record
        $this->assertDatabaseHas('files', [
            'id' => $result->id,
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'uploaded_by' => $this->user->id,
        ]);
    }

    /**
     * Test uploading multiple lease contracts
     * Requirements: 6.1
     */
    public function test_upload_multiple_lease_contracts()
    {
        $files = [
            UploadedFile::fake()->create('contract1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('contract2.pdf', 2048, 'application/pdf'),
        ];

        $result = $this->fileService->uploadMultipleLeaseContracts(
            $this->facility,
            $files,
            $this->user
        );

        $this->assertCount(2, $result['uploaded_files']);
        $this->assertEmpty($result['errors']);

        foreach ($result['uploaded_files'] as $uploadedFile) {
            $this->assertEquals('lease_contract', $uploadedFile->land_document_type);
            $this->assertEquals($this->facility->id, $uploadedFile->facility_id);
        }
    }

    /**
     * Test file validation - invalid file type
     * Requirements: 6.1, 6.2
     */
    public function test_upload_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PDFファイルのみアップロード可能です。');

        $this->fileService->uploadLandDocument(
            $this->facility,
            $file,
            'lease_contract',
            $this->user
        );
    }

    /**
     * Test file validation - file too large
     * Requirements: 6.1, 6.2
     */
    public function test_upload_file_too_large()
    {
        // Create a file larger than 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024, 'application/pdf');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ファイルサイズが10MBを超えています。');

        $this->fileService->uploadLandDocument(
            $this->facility,
            $file,
            'lease_contract',
            $this->user
        );
    }

    /**
     * Test getting land documents for a facility
     * Requirements: 6.4
     */
    public function test_get_land_documents()
    {
        // Create test files
        $leaseContract = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'uploaded_by' => $this->user->id,
        ]);

        $propertyRegister = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'property_register',
            'uploaded_by' => $this->user->id,
        ]);

        // Create a non-land document (should not be included)
        File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $documents = $this->fileService->getLandDocuments($this->facility);

        $this->assertCount(2, $documents);
        $this->assertTrue($documents->contains('id', $leaseContract->id));
        $this->assertTrue($documents->contains('id', $propertyRegister->id));
    }

    /**
     * Test getting land documents filtered by type
     * Requirements: 6.4
     */
    public function test_get_land_documents_filtered_by_type()
    {
        // Create test files
        File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'uploaded_by' => $this->user->id,
        ]);

        $propertyRegister = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'property_register',
            'uploaded_by' => $this->user->id,
        ]);

        $documents = $this->fileService->getLandDocuments($this->facility, 'property_register');

        $this->assertCount(1, $documents);
        $this->assertEquals($propertyRegister->id, $documents->first()->id);
    }

    /**
     * Test downloading a land document
     * Requirements: 6.4
     */
    public function test_download_land_document()
    {
        // Create a test file
        $testContent = 'Test PDF content';
        Storage::put('test/document.pdf', $testContent);

        $file = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'file_path' => 'test/document.pdf',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->fileService->downloadLandDocument($file, $this->user);

        $this->assertNotNull($response);
    }

    /**
     * Test downloading non-land document throws exception
     * Requirements: 6.4
     */
    public function test_download_non_land_document_throws_exception()
    {
        $file = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => null, // Not a land document
            'uploaded_by' => $this->user->id,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('指定されたファイルは土地関連書類ではありません。');

        $this->fileService->downloadLandDocument($file, $this->user);
    }

    /**
     * Test deleting a land document
     * Requirements: 6.5
     */
    public function test_delete_land_document()
    {
        // Create a test file
        Storage::put('test/document.pdf', 'Test content');

        $file = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'file_path' => 'test/document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $result = $this->fileService->deleteLandDocument($file, $this->user);

        $this->assertTrue($result);

        // Check file was deleted from storage
        Storage::assertMissing('test/document.pdf');

        // Check database record was deleted
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    /**
     * Test replacing existing land document
     * Requirements: 6.5
     */
    public function test_replace_land_document()
    {
        // Create existing file
        Storage::put('test/old_document.pdf', 'Old content');

        $existingFile = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'property_register',
            'file_path' => 'test/old_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // Upload new file to replace it
        $newFile = UploadedFile::fake()->create('new_register.pdf', 1024, 'application/pdf');

        $result = $this->fileService->replaceLandDocument(
            $this->facility,
            $newFile,
            'property_register',
            $this->user
        );

        // Check old file was deleted
        $this->assertDatabaseMissing('files', ['id' => $existingFile->id]);
        Storage::assertMissing('test/old_document.pdf');

        // Check new file was created
        $this->assertInstanceOf(File::class, $result);
        $this->assertEquals('property_register', $result->land_document_type);
        $this->assertEquals('new_register.pdf', $result->original_name);
        Storage::assertExists($result->file_path);
    }

    /**
     * Test file size formatting
     */
    public function test_format_file_size()
    {
        $this->assertEquals('1 KB', $this->fileService->formatFileSize(1024));
        $this->assertEquals('1 MB', $this->fileService->formatFileSize(1024 * 1024));
        $this->assertEquals('1.5 MB', $this->fileService->formatFileSize(1024 * 1024 * 1.5));
        $this->assertEquals('500 B', $this->fileService->formatFileSize(500));
    }

    /**
     * Test document type display names
     */
    public function test_document_type_display_names()
    {
        $this->assertEquals('賃貸借契約書・覚書', $this->fileService->getDocumentTypeDisplayName('lease_contract'));
        $this->assertEquals('謄本', $this->fileService->getDocumentTypeDisplayName('property_register'));
        $this->assertEquals('その他', $this->fileService->getDocumentTypeDisplayName('other'));
        $this->assertEquals('unknown', $this->fileService->getDocumentTypeDisplayName('unknown'));
    }

    /**
     * Test handling upload errors in multiple file upload
     * Requirements: 6.1
     */
    public function test_multiple_upload_with_errors()
    {
        $files = [
            UploadedFile::fake()->create('valid.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('invalid.txt', 1024, 'text/plain'), // Invalid type
        ];

        $result = $this->fileService->uploadMultipleLeaseContracts(
            $this->facility,
            $files,
            $this->user
        );

        $this->assertCount(1, $result['uploaded_files']); // Only valid file uploaded
        $this->assertCount(1, $result['errors']); // One error for invalid file

        $this->assertEquals('invalid.txt', $result['errors'][0]['filename']);
        $this->assertStringContainsString('PDFファイルのみアップロード可能です。', $result['errors'][0]['error']);
    }
}
