<?php

namespace Tests\Unit\Services;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class SecurePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurePdfService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SecurePdfService;
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

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
     * Test generate secure filename with special characters.
     */
    public function test_generate_secure_filename_with_special_characters()
    {
        $facility = Facility::factory()->create([
            'office_code' => 'SPEC001',
            'facility_name' => 'Test/Facility\\Name:With*Special?Characters',
        ]);

        $filename = $this->service->generateSecureFilename($facility);

        // Check that special characters are replaced with underscores
        $this->assertStringContainsString('Test_Facility_Name_With_Special_Characters', $filename);
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);
        $this->assertStringNotContainsString(':', $filename);
        $this->assertStringNotContainsString('*', $filename);
        $this->assertStringNotContainsString('?', $filename);
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

        // Test checksum is consistent
        $metadata2 = $this->service->getPdfMetadata($facility);
        $this->assertEquals($metadata['checksum'], $metadata2['checksum']);
    }

    /**
     * Test get PDF metadata for non-approved facility.
     */
    public function test_get_pdf_metadata_non_approved()
    {
        $facility = Facility::factory()->create([
            'status' => 'draft',
            'approved_at' => null,
        ]);

        $metadata = $this->service->getPdfMetadata($facility);

        $this->assertEquals('draft', $metadata['approval_status']);
        $this->assertNull($metadata['approved_at']);
    }

    /**
     * Test generate secure password.
     */
    public function test_generate_secure_password()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateSecurePassword');
        $method->setAccessible(true);

        // Test default length (12)
        $password1 = $method->invoke($this->service);
        $this->assertEquals(12, strlen($password1));

        // Test custom length
        $password2 = $method->invoke($this->service, 16);
        $this->assertEquals(16, strlen($password2));

        // Test passwords are different
        $password3 = $method->invoke($this->service);
        $this->assertNotEquals($password1, $password3);

        // Test password contains valid characters
        $validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        for ($i = 0; $i < strlen($password1); $i++) {
            $this->assertStringContainsString($password1[$i], $validChars);
        }
    }

    /**
     * Test generate HTML content method exists and works.
     */
    public function test_generate_html_content()
    {
        $facility = Facility::factory()->create();
        $data = [
            'facility' => $facility,
            'generated_at' => now(),
            'generated_by' => $this->user,
        ];

        // Mock the view
        View::shouldReceive('make')
            ->with('export.pdf.secure-facility-report', $data)
            ->once()
            ->andReturnSelf();

        View::shouldReceive('render')
            ->once()
            ->andReturn('<html><body>Test HTML Content</body></html>');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateHtmlContent');
        $method->setAccessible(true);

        $html = $method->invoke($this->service, $data);

        $this->assertEquals('<html><body>Test HTML Content</body></html>', $html);
    }

    /**
     * Test that secure PDF generation method exists and has proper structure.
     * Note: We can't fully test TCPDF without mocking it extensively.
     */
    public function test_generate_secure_facility_pdf_method_exists()
    {
        $facility = Facility::factory()->create();

        $this->assertTrue(method_exists($this->service, 'generateSecureFacilityPdf'));

        // Test that the method accepts the expected parameters
        $reflection = new \ReflectionMethod($this->service, 'generateSecureFacilityPdf');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('facility', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertEquals([], $parameters[1]->getDefaultValue());
    }

    /**
     * Test metadata checksum consistency.
     */
    public function test_metadata_checksum_consistency()
    {
        $facility = Facility::factory()->create();

        // Get metadata twice on the same day
        $metadata1 = $this->service->getPdfMetadata($facility);
        $metadata2 = $this->service->getPdfMetadata($facility);

        // Checksums should be the same for the same facility on the same day
        $this->assertEquals($metadata1['checksum'], $metadata2['checksum']);
    }

    /**
     * Test metadata checksum uniqueness for different facilities.
     */
    public function test_metadata_checksum_uniqueness()
    {
        $facility1 = Facility::factory()->create();
        $facility2 = Facility::factory()->create();

        $metadata1 = $this->service->getPdfMetadata($facility1);
        $metadata2 = $this->service->getPdfMetadata($facility2);

        // Checksums should be different for different facilities
        $this->assertNotEquals($metadata1['checksum'], $metadata2['checksum']);
    }
}
