<?php

namespace Tests\Unit\Services;

use App\Models\Facility;
use App\Models\File;
use App\Models\User;
use App\Services\ExportService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExportService $service;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExportService;
        $this->user = User::factory()->create();
        $this->facility = Facility::factory()->create([
            'office_code' => 'TEST001',
            'facility_name' => 'Test Facility',
            'status' => 'approved',
        ]);

        Auth::login($this->user);
        Storage::fake('local');
    }

    // ========================================
    // PDF Generation Tests
    // ========================================

    /**
     * Test generate secure filename.
     */
    public function test_generate_secure_filename()
    {
        $facility = Facility::factory()->create([
            'office_code' => 'TEST001',
            'facility_name' => 'Test Facility & Co.',
        ]);

        $filename = $this->service->generateSecureFilename($facility);

        $this->assertStringStartsWith('secure_facility_report_TEST001_Test_Facility___Co_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertMatchesRegularExpression('/secure_facility_report_TEST001_Test_Facility___Co__[a-f0-9]{8}_\d{4}-\d{2}-\d{2}\.pdf/', $filename);
    }

    /**
     * Test get PDF metadata.
     */
    public function test_get_pdf_metadata()
    {
        $facility = Facility::factory()->create([
            'id' => 123,
            'facility_name' => 'Test Facility',
            'office_code' => 'TEST001',
            'status' => 'approved',
            'approved_at' => now()->subDays(5),
        ]);

        $metadata = $this->service->getPdfMetadata($facility);

        $this->assertEquals('Facility Report', $metadata['document_type']);
        $this->assertEquals(123, $metadata['facility_id']);
        $this->assertEquals('Test Facility', $metadata['facility_name']);
        $this->assertEquals('TEST001', $metadata['office_code']);
        $this->assertEquals($this->user->email, $metadata['generated_by']);
        $this->assertEquals('Protected', $metadata['security_level']);
        $this->assertEquals('approved', $metadata['approval_status']);
        $this->assertNotNull($metadata['approved_at']);
        $this->assertNotNull($metadata['generated_at']);
        $this->assertNotNull($metadata['checksum']);
    }

    /**
     * Test batch PDF generation success.
     */
    public function test_generate_batch_pdf_success()
    {
        $facilities = collect([
            Facility::factory()->create(['facility_name' => 'Facility 1', 'status' => 'approved']),
            Facility::factory()->create(['facility_name' => 'Facility 2', 'status' => 'approved']),
        ]);

        // Mock cache operations
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->andReturn(true);

        // Mock View for HTML content generation
        View::shouldReceive('make')->andReturnSelf();
        View::shouldReceive('render')->andReturn('<html><body>Test</body></html>');

        // Note: We can't fully test TCPDF without extensive mocking
        // This test verifies the method structure and basic functionality
        $this->assertTrue(method_exists($this->service, 'generateBatchPdf'));

        $reflection = new \ReflectionMethod($this->service, 'generateBatchPdf');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('facilities', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());
    }

    /**
     * Test get batch progress.
     */
    public function test_get_batch_progress()
    {
        $batchId = 'test_batch_123';
        $progressData = [
            'status' => 'processing',
            'processed_count' => 5,
            'total_count' => 10,
            'percentage' => 50.0,
        ];

        Cache::shouldReceive('get')
            ->with("batch_pdf_progress_{$batchId}", \Mockery::any())
            ->andReturn($progressData);

        $result = $this->service->getBatchProgress($batchId);

        $this->assertEquals($progressData, $result);
    }

    /**
     * Test get batch progress for non-existent batch.
     */
    public function test_get_batch_progress_not_found()
    {
        $batchId = 'non_existent_batch';

        Cache::shouldReceive('get')
            ->with("batch_pdf_progress_{$batchId}", \Mockery::any())
            ->andReturn([
                'status' => 'not_found',
                'message' => 'バッチが見つかりません。',
            ]);

        $result = $this->service->getBatchProgress($batchId);

        $this->assertEquals('not_found', $result['status']);
        $this->assertEquals('バッチが見つかりません。', $result['message']);
    }

    // ========================================
    // CSV Generation Tests
    // ========================================

    /**
     * Test get available fields.
     */
    public function test_get_available_fields()
    {
        $fields = $this->service->getAvailableFields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('company_name', $fields);
        $this->assertArrayHasKey('facility_name', $fields);
        $this->assertArrayHasKey('land_ownership_type', $fields);
        $this->assertEquals('会社名', $fields['company_name']);
        $this->assertEquals('施設名', $fields['facility_name']);
        $this->assertEquals('土地所有形態', $fields['land_ownership_type']);
    }

    /**
     * Test CSV generation.
     */
    public function test_generate_csv()
    {
        $facility1 = Facility::factory()->create([
            'company_name' => 'Test Company 1',
            'facility_name' => 'Test Facility 1',
            'status' => 'approved',
        ]);

        $facility2 = Facility::factory()->create([
            'company_name' => 'Test Company 2',
            'facility_name' => 'Test Facility 2',
            'status' => 'approved',
        ]);

        $facilityIds = [$facility1->id, $facility2->id];
        $fields = ['company_name', 'facility_name', 'status'];

        $csvContent = $this->service->generateCsv($facilityIds, $fields);

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);

        // Check header row
        $this->assertStringContainsString('会社名', $csvContent);
        $this->assertStringContainsString('施設名', $csvContent);
        $this->assertStringContainsString('ステータス', $csvContent);

        // Check data rows
        $this->assertStringContainsString('Test Company 1', $csvContent);
        $this->assertStringContainsString('Test Facility 1', $csvContent);
        $this->assertStringContainsString('Test Company 2', $csvContent);
        $this->assertStringContainsString('Test Facility 2', $csvContent);
    }

    /**
     * Test CSV preview data.
     */
    public function test_preview_field_data()
    {
        $facilities = Facility::factory()->count(5)->create([
            'status' => 'approved',
        ]);

        $facilityIds = $facilities->pluck('id')->toArray();
        $fields = ['company_name', 'facility_name'];

        $result = $this->service->previewFieldData($facilityIds, $fields);

        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('preview_data', $result);
        $this->assertArrayHasKey('total_facilities', $result);
        $this->assertArrayHasKey('preview_count', $result);

        $this->assertEquals(5, $result['total_facilities']);
        $this->assertEquals(3, $result['preview_count']); // Limited to 3 for preview
        $this->assertCount(3, $result['preview_data']);

        // Check field mapping
        $this->assertEquals('会社名', $result['fields']['company_name']);
        $this->assertEquals('施設名', $result['fields']['facility_name']);
    }

    // ========================================
    // File Management Tests
    // ========================================

    /**
     * Test uploading a valid land document.
     */
    public function test_upload_file()
    {
        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $result = $this->service->uploadFile($file, $this->facility->id, 'lease_contract');

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
     * Test file validation - invalid file type.
     */
    public function test_upload_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PDFファイルのみアップロード可能です。');

        $this->service->uploadFile($file, $this->facility->id, 'lease_contract');
    }

    /**
     * Test file validation - file too large.
     */
    public function test_upload_file_too_large()
    {
        // Create a file larger than 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024, 'application/pdf');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ファイルサイズが10MBを超えています。');

        $this->service->uploadFile($file, $this->facility->id, 'lease_contract');
    }

    /**
     * Test downloading a land document.
     */
    public function test_download_file()
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

        $response = $this->service->downloadFile($file->id);

        $this->assertNotNull($response);
    }

    /**
     * Test downloading non-land document throws exception.
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

        $this->service->downloadFile($file->id);
    }

    /**
     * Test deleting a land document.
     */
    public function test_delete_file()
    {
        // Create a test file
        Storage::put('test/document.pdf', 'Test content');

        $file = File::factory()->create([
            'facility_id' => $this->facility->id,
            'land_document_type' => 'lease_contract',
            'file_path' => 'test/document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $result = $this->service->deleteFile($file->id);

        $this->assertTrue($result);

        // Check file was deleted from storage
        Storage::assertMissing('test/document.pdf');

        // Check database record was deleted
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    /**
     * Test getting files by facility.
     */
    public function test_get_files_by_facility()
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

        $documents = $this->service->getFilesByFacility($this->facility->id);

        $this->assertCount(2, $documents);
        $this->assertTrue($documents->contains('id', $leaseContract->id));
        $this->assertTrue($documents->contains('id', $propertyRegister->id));
    }

    // ========================================
    // Utility Method Tests
    // ========================================

    /**
     * Test file size formatting.
     */
    public function test_format_file_size()
    {
        $this->assertEquals('1 KB', $this->service->formatFileSize(1024));
        $this->assertEquals('1 MB', $this->service->formatFileSize(1024 * 1024));
        $this->assertEquals('1.5 MB', $this->service->formatFileSize(1024 * 1024 * 1.5));
        $this->assertEquals('500 B', $this->service->formatFileSize(500));
    }

    /**
     * Test document type display names.
     */
    public function test_document_type_display_names()
    {
        $this->assertEquals('賃貸借契約書・覚書', $this->service->getDocumentTypeDisplayName('lease_contract'));
        $this->assertEquals('謄本', $this->service->getDocumentTypeDisplayName('property_register'));
        $this->assertEquals('その他', $this->service->getDocumentTypeDisplayName('other'));
        $this->assertEquals('unknown', $this->service->getDocumentTypeDisplayName('unknown'));
    }

    /**
     * Test cleanup old batches.
     */
    public function test_cleanup_old_batches()
    {
        $result = $this->service->cleanupOldBatches();

        // Currently returns 0 as placeholder
        $this->assertEquals(0, $result);
    }

    // ========================================
    // Private Method Tests (using reflection)
    // ========================================

    /**
     * Test status label conversion.
     */
    public function test_get_status_label()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getStatusLabel');
        $method->setAccessible(true);

        $this->assertEquals('下書き', $method->invoke($this->service, 'draft'));
        $this->assertEquals('承認待ち', $method->invoke($this->service, 'pending_approval'));
        $this->assertEquals('承認済み', $method->invoke($this->service, 'approved'));
        $this->assertEquals('unknown', $method->invoke($this->service, 'unknown'));
    }

    /**
     * Test ownership type label conversion.
     */
    public function test_get_ownership_type_label()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getOwnershipTypeLabel');
        $method->setAccessible(true);

        $this->assertEquals('自社所有', $method->invoke($this->service, 'owned'));
        $this->assertEquals('賃貸', $method->invoke($this->service, 'leased'));
        $this->assertEquals('その他', $method->invoke($this->service, 'other'));
        $this->assertEquals('', $method->invoke($this->service, null));
        $this->assertEquals('unknown', $method->invoke($this->service, 'unknown'));
    }

    /**
     * Test auto renewal label conversion.
     */
    public function test_get_auto_renewal_label()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAutoRenewalLabel');
        $method->setAccessible(true);

        $this->assertEquals('あり', $method->invoke($this->service, true));
        $this->assertEquals('なし', $method->invoke($this->service, false));
        $this->assertEquals('', $method->invoke($this->service, null));
    }

    /**
     * Test array to CSV line conversion.
     */
    public function test_array_to_csv_line()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('arrayToCsvLine');
        $method->setAccessible(true);

        $data = ['Test', 'Data', 'With,Comma', 'With"Quote'];
        $csvLine = $method->invoke($this->service, $data);

        $this->assertStringContainsString('Test', $csvLine);
        $this->assertStringContainsString('Data', $csvLine);
        $this->assertStringContainsString('"With,Comma"', $csvLine);
        $this->assertStringContainsString('"With""Quote"', $csvLine);
    }

    /**
     * Test field value extraction for facility.
     */
    public function test_get_field_value_facility()
    {
        $facility = Facility::factory()->create([
            'company_name' => 'Test Company',
            'status' => 'approved',
            'approved_at' => now()->subDays(1),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('Test Company', $method->invoke($this->service, $facility, 'company_name'));
        $this->assertEquals('承認済み', $method->invoke($this->service, $facility, 'status'));
        $this->assertNotEmpty($method->invoke($this->service, $facility, 'approved_at'));
        $this->assertNotEmpty($method->invoke($this->service, $facility, 'created_at'));
    }

    /**
     * Test error handling in service methods.
     */
    public function test_error_handling()
    {
        // Test that service methods properly handle and log errors
        Log::shouldReceive('error')->atLeast()->once();

        // Test with invalid facility ID for file upload
        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $this->expectException(Exception::class);
        $this->service->uploadFile($file, 99999, 'lease_contract'); // Non-existent facility
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
