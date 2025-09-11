<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FacilityViewDataParityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private Facility $facility;

    // Test constants
    private const VIEW_MODE_SESSION_KEY = 'facility_basic_info_view_mode';

    private const CARD_VIEW_MODE = 'card';

    private const TABLE_VIEW_MODE = 'table';

    private const EMPTY_VALUE_PLACEHOLDER = '未設定';

    // XPath selectors for data extraction
    private const TABLE_CELL_SELECTOR = '//td[contains(@class, "detail-value") or not(@class)]';

    private const CARD_VALUE_SELECTOR = '//span[contains(@class, "detail-value")]';

    private const SERVICE_ELEMENT_SELECTOR = '//*[contains(@class, "service-card-title") or contains(@class, "svc-name")]';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Test that all facility data from card view appears in table view
     *
     * @test
     */
    public function it_displays_all_facility_data_in_both_views()
    {
        // Create facility with comprehensive data
        $this->facility = $this->createFacilityWithCompleteData();

        // Extract data from both views
        $viewData = $this->extractDataFromBothViews();

        // Validate data parity
        $this->assertDataParity($viewData['card'], $viewData['table']);
    }

    /**
     * Test proper formatting of all data types in table view
     *
     * Validates that:
     * - Dates are formatted in Japanese format (Y年m月d日)
     * - Numbers include appropriate units (階, 室, 名, 年)
     * - Email addresses are formatted as mailto links
     * - URLs are formatted as external links with target="_blank"
     * - Empty values display as "未設定"
     *
     * @test
     */
    public function it_formats_data_types_correctly_in_table_view()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        // Set view mode to table
        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);

        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Test date formatting (Japanese format Y年m月d日)
        $this->assertDateFormatting($content);

        // Test number formatting with units
        $this->assertNumberFormatting($content);

        // Test link formatting
        $this->assertLinkFormatting($content);

        // Test empty value handling
        $facilityWithEmptyValues = $this->createFacilityWithEmptyValues();
        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);

        $response = $this->get(route('facilities.show', $facilityWithEmptyValues));
        $emptyContent = $response->getContent();

        // Count occurrences of empty value placeholder
        $emptyValueCount = substr_count($emptyContent, self::EMPTY_VALUE_PLACEHOLDER);
        $this->assertGreaterThan(0, $emptyValueCount, 'Empty values should display as "'.self::EMPTY_VALUE_PLACEHOLDER.'"');
    }

    /**
     * Test service information completeness between both views
     *
     * Ensures that all facility services are displayed consistently
     * in both card and table views, including:
     * - Service types
     * - Renewal start and end dates (when present)
     * - Proper Japanese date formatting
     *
     * @test
     */
    public function it_displays_complete_service_information_in_both_views()
    {
        $this->facility = $this->createFacilityWithServices();

        // Test card view service display
        session([self::VIEW_MODE_SESSION_KEY => self::CARD_VIEW_MODE]);
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardContent = $cardResponse->getContent();

        // Test table view service display
        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableContent = $tableResponse->getContent();

        // Verify all services are displayed in both views
        foreach ($this->facility->services as $service) {
            // Service type should appear in both views
            $this->assertStringContains($cardContent, $service->service_type);
            $this->assertStringContains($tableContent, $service->service_type);

            // Service dates should appear in both views if set (using consistent Japanese format)
            if ($service->renewal_start_date) {
                $formattedStartDate = $service->renewal_start_date->format('Y年m月d日');
                $this->assertStringContains($cardContent, $formattedStartDate);
                $this->assertStringContains($tableContent, $formattedStartDate);
            }

            if ($service->renewal_end_date) {
                $formattedEndDate = $service->renewal_end_date->format('Y年m月d日');
                $this->assertStringContains($cardContent, $formattedEndDate);
                $this->assertStringContains($tableContent, $formattedEndDate);
            }
        }
    }

    /**
     * Test that no information is lost when switching between view modes
     *
     * @test
     */
    public function it_preserves_all_information_when_switching_view_modes()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        // Extract data from card view
        session([self::VIEW_MODE_SESSION_KEY => self::CARD_VIEW_MODE]);
        $cardData = $this->extractAllDisplayedData();

        // Extract data from table view
        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);
        $tableData = $this->extractAllDisplayedData();

        // Compare data sets - table view should contain all card view data
        foreach ($cardData as $dataPoint) {
            $this->assertContains(
                $dataPoint,
                $tableData,
                "Data point '{$dataPoint}' from card view is missing in table view"
            );
        }

        // Verify essential facility information is present in both views
        $essentialFields = [
            $this->facility->company_name,
            $this->facility->facility_name,
            $this->facility->office_code,
            $this->facility->formatted_postal_code,
            $this->facility->full_address,
            $this->facility->phone_number,
        ];

        foreach ($essentialFields as $field) {
            if ($field) {
                $this->assertContains($field, $cardData, 'Essential field missing from card view');
                $this->assertContains($field, $tableData, 'Essential field missing from table view');
            }
        }
    }

    /**
     * Test badge formatting for service types and approval status
     *
     * @test
     */
    public function it_displays_badges_correctly_in_table_view()
    {
        $this->facility = $this->createFacilityWithServices();

        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();

        // Test office code badge
        $this->assertStringContains($content, 'badge bg-primary');
        $this->assertStringContains($content, $this->facility->office_code);

        // Test service type display (should be properly formatted)
        foreach ($this->facility->services as $service) {
            $this->assertStringContains($content, $service->service_type);
        }
    }

    /**
     * Create facility with complete data for testing
     */
    private function createFacilityWithCompleteData(): Facility
    {
        return Facility::factory()->create($this->getCompleteTestData());
    }

    /**
     * Create facility with empty values for testing
     */
    private function createFacilityWithEmptyValues(): Facility
    {
        return Facility::factory()->create($this->getEmptyTestData());
    }

    /**
     * Get complete test data for facility creation
     */
    private function getCompleteTestData(): array
    {
        return [
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'facility_name' => 'テスト施設',
            'postal_code' => '1234567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストビル4F',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'toll_free_number' => '0120-123-456',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
            'opening_date' => Carbon::parse('2020-01-15'),
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60,
            'status' => 'approved',
        ];
    }

    /**
     * Get empty test data for facility creation
     */
    private function getEmptyTestData(): array
    {
        $baseData = [
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST002',
            'facility_name' => 'テスト施設2',
        ];

        $nullableFields = [
            'designation_number', 'postal_code', 'address', 'building_name',
            'phone_number', 'fax_number', 'toll_free_number', 'email',
            'website_url', 'opening_date', 'years_in_operation', 'building_structure',
            'building_floors', 'paid_rooms_count', 'ss_rooms_count', 'capacity',
        ];

        foreach ($nullableFields as $field) {
            $baseData[$field] = null;
        }

        return $baseData;
    }

    /**
     * Create facility with services for testing
     */
    private function createFacilityWithServices(): Facility
    {
        $facility = $this->createFacilityWithCompleteData();

        $serviceConfigurations = $this->getTestServiceConfigurations();

        foreach ($serviceConfigurations as $config) {
            FacilityService::factory()->create(array_merge(
                ['facility_id' => $facility->id],
                $config
            ));
        }

        return $facility->fresh(['services']);
    }

    /**
     * Get test service configurations
     */
    private function getTestServiceConfigurations(): array
    {
        return [
            [
                'service_type' => '介護保険サービス',
                'renewal_start_date' => Carbon::parse('2023-04-01'),
                'renewal_end_date' => Carbon::parse('2029-03-31'),
            ],
            [
                'service_type' => '障害福祉サービス',
                'renewal_start_date' => Carbon::parse('2022-10-01'),
                'renewal_end_date' => Carbon::parse('2028-09-30'),
            ],
            [
                'service_type' => '地域密着型サービス',
                'renewal_start_date' => null,
                'renewal_end_date' => null,
            ],
        ];
    }

    /**
     * Extract data from both views efficiently
     * Optimized to reduce HTTP requests and database queries
     */
    private function extractDataFromBothViews(): array
    {
        // Pre-load facility with all necessary relationships to avoid N+1 queries
        $this->facility->load(['services', 'landInfo']);

        return [
            'card' => $this->extractCardViewData(),
            'table' => $this->extractTableViewData(),
        ];
    }

    /**
     * Extract data displayed in card view
     */
    private function extractCardViewData(): array
    {
        session([self::VIEW_MODE_SESSION_KEY => self::CARD_VIEW_MODE]);
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(200);

        return $this->parseDisplayedData($response->getContent());
    }

    /**
     * Extract data displayed in table view
     */
    private function extractTableViewData(): array
    {
        session([self::VIEW_MODE_SESSION_KEY => self::TABLE_VIEW_MODE]);
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(200);

        return $this->parseDisplayedData($response->getContent());
    }

    /**
     * Extract all displayed data from current view
     */
    private function extractAllDisplayedData(): array
    {
        $response = $this->get(route('facilities.show', $this->facility));

        return $this->parseDisplayedData($response->getContent());
    }

    /**
     * Parse displayed data from HTML content
     */
    private function parseDisplayedData(string $content): array
    {
        $dom = $this->createDomDocument($content);
        $xpath = new \DOMXPath($dom);

        $data = [];
        $data = array_merge($data, $this->extractTableCellData($xpath));
        $data = array_merge($data, $this->extractCardDetailData($xpath));
        $data = array_merge($data, $this->extractServiceData($xpath));

        return array_unique($data);
    }

    /**
     * Create and configure DOM document
     */
    private function createDomDocument(string $content): \DOMDocument
    {
        $dom = new \DOMDocument;

        // Suppress warnings for malformed HTML and load with proper options
        $previousErrorReporting = error_reporting(0);
        $loaded = $dom->loadHTML(
            $content,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        error_reporting($previousErrorReporting);

        if (! $loaded) {
            $this->fail('Failed to parse HTML content for data extraction');
        }

        return $dom;
    }

    /**
     * Extract data from table cells
     */
    private function extractTableCellData(\DOMXPath $xpath): array
    {
        $data = [];
        $tableCells = $xpath->query(self::TABLE_CELL_SELECTOR);

        foreach ($tableCells as $cell) {
            $text = $this->cleanTextContent($cell->textContent);
            if ($this->isValidDataPoint($text)) {
                $data[] = $text;
            }
        }

        return $data;
    }

    /**
     * Extract data from card detail elements
     */
    private function extractCardDetailData(\DOMXPath $xpath): array
    {
        $data = [];
        $cardValues = $xpath->query(self::CARD_VALUE_SELECTOR);

        foreach ($cardValues as $value) {
            $text = $this->cleanTextContent($value->textContent);
            if ($this->isValidDataPoint($text)) {
                $data[] = $text;
            }
        }

        return $data;
    }

    /**
     * Extract service information data
     */
    private function extractServiceData(\DOMXPath $xpath): array
    {
        $data = [];
        $serviceElements = $xpath->query(self::SERVICE_ELEMENT_SELECTOR);

        foreach ($serviceElements as $element) {
            $text = $this->cleanTextContent($element->textContent);
            if ($this->isValidDataPoint($text)) {
                $data[] = $text;
            }
        }

        return $data;
    }

    /**
     * Clean and normalize text content
     */
    private function cleanTextContent(string $text): string
    {
        return trim($text);
    }

    /**
     * Check if text is a valid data point for comparison
     */
    private function isValidDataPoint(string $text): bool
    {
        return ! empty($text) && $text !== self::EMPTY_VALUE_PLACEHOLDER;
    }

    /**
     * Assert data parity between card and table views
     */
    private function assertDataParity(array $cardData, array $tableData): void
    {
        // Remove empty and placeholder values for comparison
        $cardData = array_filter($cardData, fn ($item) => $this->isValidDataPoint($item));
        $tableData = array_filter($tableData, fn ($item) => $this->isValidDataPoint($item));

        // Check that all card data appears in table data
        foreach ($cardData as $dataPoint) {
            $this->assertContains(
                $dataPoint,
                $tableData,
                "Data point '{$dataPoint}' from card view is missing in table view"
            );
        }

        // Verify essential facility fields are present in both views
        $this->assertContains($this->facility->company_name, $cardData);
        $this->assertContains($this->facility->company_name, $tableData);

        $this->assertContains($this->facility->facility_name, $cardData);
        $this->assertContains($this->facility->facility_name, $tableData);

        $this->assertContains($this->facility->office_code, $cardData);
        $this->assertContains($this->facility->office_code, $tableData);
    }

    /**
     * Helper method to check if string contains substring
     */
    private function assertStringContains(string $haystack, string $needle): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }

    /**
     * Assert proper date formatting in content
     */
    private function assertDateFormatting(string $content): void
    {
        if ($this->facility->opening_date) {
            $expectedDateFormat = $this->facility->opening_date->format('Y年m月d日');
            $this->assertStringContains($content, $expectedDateFormat);
        }
    }

    /**
     * Assert proper number formatting with units in content
     */
    private function assertNumberFormatting(string $content): void
    {
        $numberFields = [
            'building_floors' => '階',
            'paid_rooms_count' => '室',
            'ss_rooms_count' => '室',
            'capacity' => '名',
            'years_in_operation' => '年',
        ];

        foreach ($numberFields as $field => $unit) {
            if ($this->facility->{$field} !== null) {
                $expectedFormat = number_format($this->facility->{$field}).$unit;
                $this->assertStringContains($content, $expectedFormat);
            }
        }
    }

    /**
     * Assert proper link formatting in content
     */
    private function assertLinkFormatting(string $content): void
    {
        // Test email link formatting
        if ($this->facility->email) {
            $this->assertStringContains($content, 'href="mailto:'.$this->facility->email.'"');
        }

        // Test URL link formatting
        if ($this->facility->website_url) {
            $this->assertStringContains($content, 'href="'.$this->facility->website_url.'"');
            $this->assertStringContains($content, 'target="_blank"');
        }
    }
}
