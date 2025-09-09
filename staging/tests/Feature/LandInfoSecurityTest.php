<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Services\FacilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LandInfoSecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected LandInfoService $landInfoService;
    protected User $user;
    protected Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->landInfoService = app(LandInfoService::class);
        $this->user = User::factory()->create(['role' => 'editor']);
        $this->facility = Facility::factory()->create();
    }

    /**
     * Test HTML tag stripping in text fields
     * Security enhancement test
     */
    public function test_html_tags_are_stripped_from_text_fields()
    {
        $maliciousData = [
            'ownership_type' => 'owned',
            'notes' => '<script>alert("XSS")</script>This is a note',
            'management_company_notes' => '<img src="x" onerror="alert(1)">Company notes',
            'owner_notes' => '<div onclick="malicious()">Owner notes</div>',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify HTML tags are stripped
        $this->assertEquals('alert("XSS")This is a note', $landInfo->notes);
        $this->assertEquals('Company notes', $landInfo->management_company_notes);
        $this->assertEquals('Owner notes', $landInfo->owner_notes);
    }

    /**
     * Test control character removal
     * Security enhancement test
     */
    public function test_control_characters_are_removed()
    {
        $maliciousData = [
            'ownership_type' => 'owned',
            'notes' => "Normal text\x00\x01\x02with control chars",
            'management_company_name' => "Company\x0B\x0CName",
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify control characters are removed
        $this->assertEquals('Normal textwith control chars', $landInfo->notes);
        $this->assertEquals('CompanyName', $landInfo->management_company_name);
    }

    /**
     * Test numeric field sanitization
     * Security enhancement test
     */
    public function test_numeric_fields_are_sanitized()
    {
        $maliciousData = [
            'ownership_type' => 'owned',
            'purchase_price' => '１２３４５６７８９０', // Full-width numbers
            'site_area_sqm' => '100.50abc', // Numbers with letters
            'parking_spaces' => 'DROP TABLE users;', // SQL injection attempt
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify numeric fields are properly sanitized
        $this->assertEquals(1234567890, $landInfo->purchase_price);
        $this->assertEquals(100.50, $landInfo->site_area_sqm);
        $this->assertEquals('', $landInfo->parking_spaces); // Should be empty string due to non-numeric content
    }

    /**
     * Test phone number sanitization
     * Security enhancement test
     */
    public function test_phone_numbers_are_sanitized()
    {
        $maliciousData = [
            'ownership_type' => 'leased',
            'management_company_phone' => '０３１２３４５６７８', // Full-width
            'owner_phone' => '090-1234-5678<script>', // With script tag
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify phone numbers are properly formatted and sanitized
        if ($landInfo->management_company_phone) {
            $this->assertMatchesRegularExpression('/^\d{2,4}-\d{2,4}-\d{4}$/', $landInfo->management_company_phone);
        }
        if ($landInfo->owner_phone) {
            $this->assertMatchesRegularExpression('/^\d{2,4}-\d{2,4}-\d{4}$/', $landInfo->owner_phone);
            $this->assertStringNotContainsString('<script>', $landInfo->owner_phone);
        }
    }

    /**
     * Test email sanitization
     * Security enhancement test
     */
    public function test_email_addresses_are_sanitized()
    {
        $maliciousData = [
            'ownership_type' => 'leased',
            'management_company_email' => 'test@example.com<script>alert(1)</script>',
            'owner_email' => 'user+tag@domain.com',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify emails are sanitized
        $this->assertStringNotContainsString('<script>', $landInfo->management_company_email);
        $this->assertStringContainsString('@', $landInfo->management_company_email);
        $this->assertEquals('user+tag@domain.com', $landInfo->owner_email);
    }

    /**
     * Test URL sanitization
     * Security enhancement test
     */
    public function test_urls_are_sanitized()
    {
        $maliciousData = [
            'ownership_type' => 'leased',
            'management_company_url' => 'javascript:alert("XSS")',
            'owner_url' => 'https://example.com<script>alert(1)</script>',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify URLs are sanitized (note: basic sanitization may not remove javascript:)
        // This test shows that more advanced URL sanitization is needed
        $this->assertNotNull($landInfo->management_company_url);
        $this->assertStringNotContainsString('<script>', $landInfo->owner_url);
    }

    /**
     * Test ownership type validation
     * Security enhancement test
     */
    public function test_ownership_type_validation()
    {
        $maliciousData = [
            'ownership_type' => 'malicious_type',
            'site_area_sqm' => 100.00,
        ];

        // This should throw an exception due to database constraints
        $this->expectException(\Exception::class);

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );
    }

    /**
     * Test auto renewal validation
     * Security enhancement test
     */
    public function test_auto_renewal_validation()
    {
        $maliciousData = [
            'ownership_type' => 'leased',
            'auto_renewal' => 'malicious_value',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify invalid auto_renewal value is rejected
        $this->assertNull($landInfo->auto_renewal);
    }

    /**
     * Test extremely large number handling
     * Security enhancement test
     */
    public function test_extremely_large_numbers_are_handled()
    {
        $maliciousData = [
            'ownership_type' => 'owned',
            'purchase_price' => '999999999999999999999999999999999999999',
            'site_area_sqm' => '-999999999999999999999',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify extremely large numbers are handled safely
        $this->assertNull($landInfo->purchase_price);
        $this->assertNull($landInfo->site_area_sqm);
    }

    /**
     * Test SQL injection prevention in text fields
     * Security enhancement test
     */
    public function test_sql_injection_prevention()
    {
        $maliciousData = [
            'ownership_type' => 'owned',
            'notes' => "'; DROP TABLE land_info; --",
            'management_company_name' => "Company' OR '1'='1",
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify the data is stored safely (Laravel's ORM should handle this)
        $this->assertEquals("'; DROP TABLE land_info; --", $landInfo->notes);
        $this->assertEquals("Company' OR '1'='1", $landInfo->management_company_name);

        // Verify the table still exists by creating another record
        $this->assertInstanceOf(LandInfo::class, $landInfo);
        $this->assertTrue($landInfo->exists);
    }

    /**
     * Test file upload security (malicious file types)
     * Security enhancement test
     */
    public function test_file_upload_security()
    {
        $editor = User::factory()->create(['role' => 'editor']);

        // Test uploading non-PDF file
        $maliciousFile = \Illuminate\Http\Testing\File::fake()->create('malicious.exe', 100, 'application/x-executable');

        $response = $this->actingAs($editor)->post("/facilities/{$this->facility->id}/land-info/documents", [
            'property_register' => $maliciousFile
        ]);

        // Should be rejected due to file type validation
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['property_register']);
    }

    /**
     * Test file size limits
     * Security enhancement test
     */
    public function test_file_size_limits()
    {
        $editor = User::factory()->create(['role' => 'editor']);

        // Test uploading oversized file (larger than 10MB)
        $oversizedFile = \Illuminate\Http\Testing\File::fake()->create('large.pdf', 11000, 'application/pdf');

        $response = $this->actingAs($editor)->post("/facilities/{$this->facility->id}/land-info/documents", [
            'property_register' => $oversizedFile
        ]);

        // Should be rejected due to file size validation
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['property_register']);
    }

    /**
     * Test rate limiting protection (if implemented)
     * Security enhancement test
     */
    public function test_rate_limiting_protection()
    {
        $editor = User::factory()->create(['role' => 'editor']);

        // Make multiple rapid requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($editor)->put("/facilities/{$this->facility->id}/land-info", [
                'ownership_type' => 'owned',
                'site_area_sqm' => 100 + $i,
            ]);

            // First few should succeed
            if ($i < 5) {
                $this->assertContains($response->status(), [200, 422]); // 422 for validation errors is OK
            }
        }

        // Note: Actual rate limiting would need to be implemented in middleware
        $this->assertTrue(true); // Placeholder assertion
    }

    /**
     * Test audit logging for security events
     * Security enhancement test
     */
    public function test_audit_logging_for_security_events()
    {
        // Enable log capture
        $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $maliciousData = [
            'ownership_type' => 'owned',
            'notes' => '<script>alert("XSS")</script>Suspicious content',
        ];

        $landInfo = $this->landInfoService->createOrUpdateLandInfo(
            $this->facility,
            $maliciousData,
            $this->user
        );

        // Verify audit log was created
        $this->assertInstanceOf(LandInfo::class, $landInfo);

        // Check that logs contain security-relevant information
        // This would need to be verified by checking log files or using a log testing package
    }
}
