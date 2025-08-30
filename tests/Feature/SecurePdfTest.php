<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use App\Services\SecurePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SecurePdfTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
    }

    public function test_can_generate_secure_pdf()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.secure.single', $facility));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        // Check that cache control contains no-cache directive
        $this->assertStringContainsString('no-cache', $response->headers->get('cache-control'));
    }

    public function test_secure_pdf_service_generates_content()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = new SecurePdfService();
        $pdfContent = $service->generateSecureFacilityPdf($facility);

        $this->assertNotEmpty($pdfContent);
        $this->assertStringStartsWith('%PDF', $pdfContent);
    }

    public function test_secure_filename_generation()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = new SecurePdfService();
        $filename = $service->generateSecureFilename($facility);

        $this->assertStringStartsWith('secure_facility_report_', $filename);
        $this->assertStringContainsString('TEST001', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_pdf_metadata_generation()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $service = new SecurePdfService();
        $metadata = $service->getPdfMetadata($facility);

        $this->assertArrayHasKey('document_type', $metadata);
        $this->assertArrayHasKey('facility_id', $metadata);
        $this->assertArrayHasKey('security_level', $metadata);
        $this->assertArrayHasKey('checksum', $metadata);
        $this->assertEquals('Facility Report', $metadata['document_type']);
        $this->assertEquals('Protected', $metadata['security_level']);
        $this->assertEquals($facility->id, $metadata['facility_id']);
    }

    public function test_batch_secure_pdf_generation()
    {
        $facilities = Facility::factory()->count(2)->create([
            'status' => 'approved',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('pdf.export.batch'), [
                'facility_ids' => $facilities->pluck('id')->toArray(),
                'secure' => '1'
            ]);

        $response->assertStatus(200);
        // Should return a ZIP file for multiple facilities
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), 'secure_facility_reports_') &&
            str_contains($response->headers->get('content-disposition'), '.zip')
        );
    }

    public function test_standard_pdf_generation_when_secure_disabled()
    {
        $facility = Facility::factory()->create([
            'status' => 'approved',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST001',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('pdf.export.single', ['facility' => $facility, 'secure' => '0']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}