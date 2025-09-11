<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BatchPdfTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_batch_pdf_service_generates_multiple_pdfs()
    {
        $facilities = Facility::factory()->count(3)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);
        $result = $service->generateBatchPdf($facilities, ['secure' => true]);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total_count']);
        $this->assertEquals(3, $result['processed_count']);
        $this->assertArrayHasKey('batch_id', $result);
        $this->assertArrayHasKey('zip_path', $result);
        $this->assertFileExists($result['zip_path']);

        // Clean up
        if (file_exists($result['zip_path'])) {
            unlink($result['zip_path']);
        }
    }

    public function test_batch_progress_tracking()
    {
        $facilities = Facility::factory()->count(2)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);
        $result = $service->generateBatchPdf($facilities, ['secure' => false]);

        $this->assertTrue($result['success']);

        // Check progress tracking
        $progress = $service->getBatchProgress($result['batch_id']);
        $this->assertEquals('completed', $progress['status']);
        $this->assertEquals(100, $progress['percentage']);

        // Clean up
        if (file_exists($result['zip_path'])) {
            unlink($result['zip_path']);
        }
    }

    public function test_batch_pdf_with_mixed_security_options()
    {
        $facilities = Facility::factory()->count(2)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);

        // Test secure batch
        $secureResult = $service->generateBatchPdf($facilities, ['secure' => true]);
        $this->assertTrue($secureResult['success']);
        $this->assertStringContainsString('secure_', $secureResult['zip_filename']);

        // Test standard batch
        $standardResult = $service->generateBatchPdf($facilities, ['secure' => false]);
        $this->assertTrue($standardResult['success']);
        $this->assertStringNotContainsString('secure_', $standardResult['zip_filename']);

        // Clean up
        foreach ([$secureResult, $standardResult] as $result) {
            if (file_exists($result['zip_path'])) {
                unlink($result['zip_path']);
            }
        }
    }

    public function test_batch_progress_api_endpoint()
    {
        $facilities = Facility::factory()->count(2)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);
        $result = $service->generateBatchPdf($facilities);

        // Test progress API endpoint
        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.progress', $result['batch_id']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'processed_count',
            'total_count',
            'percentage',
        ]);

        // Clean up
        if (file_exists($result['zip_path'])) {
            unlink($result['zip_path']);
        }
    }

    public function test_batch_pdf_handles_errors_gracefully()
    {
        // Create a valid facility
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'Test Facility',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);
        $result = $service->generateBatchPdf(collect([$facility]));

        // Should succeed with valid data
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertTrue($result['success']);

        if ($result['success'] && file_exists($result['zip_path'])) {
            unlink($result['zip_path']);
        }
    }

    public function test_batch_id_generation_is_unique()
    {
        $this->actingAs($this->user);

        $service = app(BatchPdfService::class);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateBatchId');
        $method->setAccessible(true);

        $id1 = $method->invoke($service);
        $id2 = $method->invoke($service);

        $this->assertNotEquals($id1, $id2);
        $this->assertStringStartsWith('batch_', $id1);
        $this->assertStringStartsWith('batch_', $id2);
    }
}
