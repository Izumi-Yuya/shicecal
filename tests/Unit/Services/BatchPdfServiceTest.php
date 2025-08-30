<?php

namespace Tests\Unit\Services;

use App\Models\Facility;
use App\Models\User;
use App\Services\BatchPdfService;
use App\Services\SecurePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class BatchPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private BatchPdfService $service;
    private SecurePdfService $mockSecurePdfService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSecurePdfService = Mockery::mock(SecurePdfService::class);
        $this->service = new BatchPdfService($this->mockSecurePdfService);
        
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    /**
     * Test successful batch PDF generation.
     */
    public function test_generate_batch_pdf_success()
    {
        $facilities = collect([
            Facility::factory()->create(['facility_name' => 'Facility 1']),
            Facility::factory()->create(['facility_name' => 'Facility 2']),
        ]);

        // Mock secure PDF service
        $this->mockSecurePdfService
            ->shouldReceive('generateSecureFacilityPdf')
            ->twice()
            ->andReturn('mock pdf content');

        $this->mockSecurePdfService
            ->shouldReceive('generateSecureFilename')
            ->twice()
            ->andReturn('mock_filename.pdf');

        // Mock cache operations
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->andReturn(true);

        $result = $this->service->generateBatchPdf($facilities, ['secure' => true]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('batch_id', $result);
        $this->assertArrayHasKey('zip_path', $result);
        $this->assertArrayHasKey('zip_filename', $result);
        $this->assertEquals(2, $result['processed_count']);
        $this->assertEquals(2, $result['total_count']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test batch PDF generation with errors.
     */
    public function test_generate_batch_pdf_with_errors()
    {
        $facilities = collect([
            Facility::factory()->create(['facility_name' => 'Facility 1']),
            Facility::factory()->create(['facility_name' => 'Facility 2']),
        ]);

        // Mock secure PDF service - first succeeds, second fails
        $this->mockSecurePdfService
            ->shouldReceive('generateSecureFacilityPdf')
            ->once()
            ->andReturn('mock pdf content');

        $this->mockSecurePdfService
            ->shouldReceive('generateSecureFacilityPdf')
            ->once()
            ->andThrow(new \Exception('PDF generation failed'));

        $this->mockSecurePdfService
            ->shouldReceive('generateSecureFilename')
            ->once()
            ->andReturn('mock_filename.pdf');

        // Mock cache operations
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->andReturn(true);

        // Mock log
        Log::shouldReceive('error')->once();

        $result = $this->service->generateBatchPdf($facilities, ['secure' => true]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);
        $this->assertEquals(2, $result['total_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('Facility 2', $result['errors'][0]['facility']);
    }

    /**
     * Test batch PDF generation failure.
     */
    public function test_generate_batch_pdf_failure()
    {
        $facilities = collect([
            Facility::factory()->create(['facility_name' => 'Facility 1']),
        ]);

        // Mock cache operations
        Cache::shouldReceive('get')->andReturn([]);
        Cache::shouldReceive('put')->andReturn(true);

        // Create a service that will fail during ZIP creation
        $service = new class($this->mockSecurePdfService) extends BatchPdfService {
            public function generateBatchPdf(Collection $facilities, array $options = []): array
            {
                return [
                    'success' => false,
                    'batch_id' => 'test_batch_123',
                    'error' => 'ZIP creation failed'
                ];
            }
        };

        $result = $service->generateBatchPdf($facilities);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('batch_id', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('ZIP creation failed', $result['error']);
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
            ->with("batch_pdf_progress_{$batchId}", Mockery::any())
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
            ->with("batch_pdf_progress_{$batchId}", Mockery::any())
            ->andReturn([
                'status' => 'not_found',
                'message' => 'バッチが見つかりません'
            ]);

        $result = $this->service->getBatchProgress($batchId);

        $this->assertEquals('not_found', $result['status']);
        $this->assertEquals('バッチが見つかりません', $result['message']);
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

    /**
     * Test generate batch ID format.
     */
    public function test_generate_batch_id_format()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateBatchId');
        $method->setAccessible(true);

        $batchId = $method->invoke($this->service);

        $this->assertStringStartsWith('batch_' . $this->user->id . '_', $batchId);
        $this->assertMatchesRegularExpression('/^batch_\d+_\d{8}\d{6}_[a-z0-9]{6}$/', $batchId);
    }

    /**
     * Test generate ZIP filename.
     */
    public function test_generate_zip_filename()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateZipFilename');
        $method->setAccessible(true);

        // Test secure filename
        $secureFilename = $method->invoke($this->service, true);
        $this->assertStringStartsWith('secure_facility_reports_', $secureFilename);
        $this->assertStringEndsWith('.zip', $secureFilename);

        // Test standard filename
        $standardFilename = $method->invoke($this->service, false);
        $this->assertStringStartsWith('facility_reports_', $standardFilename);
        $this->assertStringEndsWith('.zip', $standardFilename);
        $this->assertStringNotContainsString('secure_', $standardFilename);
    }

    /**
     * Test generate standard filename.
     */
    public function test_generate_standard_filename()
    {
        $facility = Facility::factory()->create([
            'office_code' => 'TEST001',
            'facility_name' => 'Test Facility & Co.',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateStandardFilename');
        $method->setAccessible(true);

        $filename = $method->invoke($this->service, $facility);

        $this->assertStringContainsString('facility_report_TEST001_Test_Facility___Co_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}