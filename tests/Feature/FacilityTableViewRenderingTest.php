<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\TestConstants;
use Tests\Support\FacilityTestDataFactory;
use Carbon\Carbon;

/**
 * Feature tests for table view rendering functionality
 * 
 * Tests Requirements:
 * - 1.2: Table view displays all facility data with proper structure
 * - 1.3: Two-column table layout (label/value pairs)
 * - 1.4: Information categorization and proper formatting
 * - 2.1: Edit button visibility based on user permissions
 * - 3.3: Empty value handling displays "未設定"
 * - 4.1: Complete data parity between card and table views
 */
class FacilityTableViewRenderingTest extends TestCase
{
    use RefreshDatabase;

    // CSS Class Constants
    private const TABLE_VIEW_CLASS = 'facility-table-view';
    private const TABLE_RESPONSIVE_CLASS = 'table-responsive';
    private const TABLE_BORDERED_CLASS = 'table table-bordered facility-info';
    private const CARD_VIEW_CLASS = 'facility-info-card';
    private const SERVICE_INFO_CLASS = 'service-info';
    
    // Button Constants
    private const EDIT_BUTTON_TEXT = '編集';
    private const EDIT_BUTTON_CLASS = 'btn btn-primary';
    
    // Service Labels
    private const SERVICE_TYPE_LABEL = 'サービス種類';

    private User $adminUser;
    private User $editorUser;
    private User $viewerUser;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->editorUser = User::factory()->create(['role' => 'editor']);
        $this->viewerUser = User::factory()->create(['role' => 'viewer']);
    }

    /**
     * Helper method to get table view content for a facility
     */
    private function getTableViewContent(): string
    {
        $this->actingAs($this->adminUser);
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(TestConstants::HTTP_OK);
        
        return $response->getContent();
    }

    /**
     * Helper method to get card view content for a facility
     */
    private function getCardViewContent(): string
    {
        $this->actingAs($this->adminUser);
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::CARD_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(TestConstants::HTTP_OK);
        
        return $response->getContent();
    }

    /**
     * Test complete table view rendering with all data categories
     * Requirements: 1.2, 1.3, 1.4
     * 
     * @test
     */
    public function it_renders_complete_table_view_with_all_data_categories()
    {
        $this->facility = FacilityTestDataFactory::createWithServices();
        $content = $this->getTableViewContent();
        
        // Verify table structure exists
        $this->assertTableStructureExists($content);
        
        // Verify all data categories are present
        $this->assertAllDataCategoriesPresent($content);
    }

    /**
     * Test basic text formatting in table view
     * Requirements: 1.4, 4.1
     * 
     * @test
     */
    public function it_formats_basic_text_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $content = $this->getTableViewContent();
        
        $this->assertStringContainsString($this->facility->company_name, $content);
        $this->assertStringContainsString($this->facility->facility_name, $content);
        $this->assertStringContainsString($this->facility->office_code, $content);
    }

    /**
     * Test email link formatting in table view
     * Requirements: 1.4, 3.4
     * 
     * @test
     */
    public function it_formats_email_links_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $content = $this->getTableViewContent();
        
        if ($this->facility->email) {
            $this->assertStringContainsString('href="mailto:' . $this->facility->email . '"', $content);
            $this->assertStringContainsString('text-primary', $content);
        }
    }

    /**
     * Test URL link formatting in table view
     * Requirements: 1.4, 3.4
     * 
     * @test
     */
    public function it_formats_url_links_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $content = $this->getTableViewContent();
        
        if ($this->facility->website_url) {
            $this->assertStringContainsString('href="' . $this->facility->website_url . '"', $content);
            $this->assertStringContainsString('target="_blank"', $content);
            $this->assertStringContainsString('rel="noopener noreferrer"', $content);
        }
    }

    /**
     * Test date formatting in table view
     * Requirements: 1.4, 4.2
     * 
     * @test
     */
    public function it_formats_dates_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $content = $this->getTableViewContent();
        
        if ($this->facility->opening_date) {
            $expectedDateFormat = $this->facility->opening_date->format('Y年m月d日');
            $this->assertStringContainsString($expectedDateFormat, $content);
        }
    }

    /**
     * Test number formatting with units in table view
     * Requirements: 1.4, 4.4
     * 
     * @test
     */
    public function it_formats_numbers_with_units_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $content = $this->getTableViewContent();
        
        $this->assertNumberFormattingWithUnits($content);
    }

    /**
     * Test empty value handling displays "未設定" correctly
     * Requirements: 3.3
     * 
     * @test
     */
    public function it_displays_empty_values_as_misetei_correctly()
    {
        $this->facility = FacilityTestDataFactory::createWithEmptyValues();
        $this->actingAs($this->adminUser);
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // Count occurrences of empty value placeholder
        $emptyValueCount = substr_count($content, TestConstants::EMPTY_VALUE_PLACEHOLDER);
        $this->assertGreaterThan(5, $emptyValueCount, 'Empty values should display as "' . TestConstants::EMPTY_VALUE_PLACEHOLDER . '"');
        
        // Verify specific empty fields show the placeholder
        $this->assertEmptyFieldsShowPlaceholder($content);
    }

    /**
     * Test edit button visibility based on user permissions in table view
     * Requirements: 2.1
     * 
     * @test
     */
    public function it_shows_edit_button_based_on_user_permissions_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        // Test admin user can see edit button
        $this->actingAs($this->adminUser);
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('編集', $content);
        $this->assertStringContainsString('btn btn-primary', $content);
        
        // Test editor user can see edit button
        $this->actingAs($this->editorUser);
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('編集', $content);
        
        // Test viewer user cannot see edit button
        $this->actingAs($this->viewerUser);
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // The edit button should not be present for viewers
        $editButtonPattern = '/href="[^"]*edit[^"]*"[^>]*class="[^"]*btn[^"]*btn-primary[^"]*"[^>]*>.*?編集.*?<\/a>/s';
        $this->assertDoesNotMatchRegularExpression($editButtonPattern, $content);
    }

    /**
     * Test view mode switching and session persistence across requests
     * Requirements: 1.2, 4.1
     * 
     * @test
     */
    public function it_maintains_view_mode_switching_and_session_persistence()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $this->actingAs($this->adminUser);
        
        // Start with card view (default)
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('facility-info-card', $content);
        $this->assertStringNotContainsString('facility-table-view', $content);
        
        // Switch to table view via session
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('facility-table-view', $content);
        $this->assertStringNotContainsString('facility-info-card', $content);
        
        // Verify session persistence across multiple requests
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('facility-table-view', $content);
        
        // Switch back to card view
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::CARD_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        $this->assertStringContainsString('facility-info-card', $content);
        $this->assertStringNotContainsString('facility-table-view', $content);
    }

    /**
     * Test service information completeness and badge formatting
     * Requirements: 4.1
     * 
     * @test
     */
    public function it_displays_service_information_with_proper_badge_formatting()
    {
        $this->facility = FacilityTestDataFactory::createWithServices();
        // Eager load services to prevent N+1 queries
        $this->facility->load('services');
        
        $content = $this->getTableViewContent();
        
        // Verify service table is included
        $this->assertServiceTableExists($content);
        
        // Test each service is displayed with proper formatting
        $this->assertServicesDisplayedCorrectly($content);
    }

    /**
     * Test data parity between card and table views
     * Requirements: 4.1
     * 
     * @test
     */
    public function it_maintains_complete_data_parity_between_views()
    {
        $this->facility = FacilityTestDataFactory::createWithServices();
        $this->actingAs($this->adminUser);
        
        // Extract data from card view
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::CARD_VIEW_MODE]);
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardData = $this->extractDisplayedData($cardResponse->getContent());
        
        // Extract data from table view
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableData = $this->extractDisplayedData($tableResponse->getContent());
        
        // Verify essential facility data appears in both views
        $essentialFields = [
            $this->facility->company_name,
            $this->facility->facility_name,
            $this->facility->office_code,
        ];
        
        foreach ($essentialFields as $field) {
            if ($field) {
                $this->assertContains($field, $cardData, "Essential field '{$field}' missing from card view");
                $this->assertContains($field, $tableData, "Essential field '{$field}' missing from table view");
            }
        }
        
        // Verify service information appears in both views
        foreach ($this->facility->services as $service) {
            $this->assertContains($service->service_type, $cardData, "Service type missing from card view");
            $this->assertContains($service->service_type, $tableData, "Service type missing from table view");
        }
    }

    /**
     * Test responsive table structure
     * Requirements: 1.3
     * 
     * @test
     */
    public function it_renders_responsive_table_structure()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $this->actingAs($this->adminUser);
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // Verify responsive table wrapper
        $this->assertStringContainsString('table-responsive', $content);
        
        // Verify table structure with proper Bootstrap classes
        $this->assertStringContainsString('table table-bordered', $content);
        
        // Verify table has proper column structure (4 columns for 2x2 layout)
        $this->assertMatchesRegularExpression('/<tr[^>]*>.*?<th[^>]*>.*?<\/th>.*?<td[^>]*>.*?<\/td>.*?<th[^>]*>.*?<\/th>.*?<td[^>]*>.*?<\/td>.*?<\/tr>/s', $content);
    }

    /**
     * Test table view with mixed data (some empty, some filled)
     * Requirements: 3.3, 4.1
     * 
     * @test
     */
    public function it_handles_mixed_data_correctly_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createWithMixedData();
        $this->actingAs($this->adminUser);
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // Verify filled fields display correctly
        $this->assertStringContainsString($this->facility->company_name, $content);
        $this->assertStringContainsString($this->facility->facility_name, $content);
        $this->assertStringContainsString($this->facility->phone_number, $content);
        
        // Verify empty fields show placeholder
        $emptyValueCount = substr_count($content, TestConstants::EMPTY_VALUE_PLACEHOLDER);
        $this->assertGreaterThan(3, $emptyValueCount, 'Mixed data should show some empty value placeholders');
        
        // Verify specific empty fields
        $this->assertStringContainsString('<span class="text-muted">' . TestConstants::EMPTY_VALUE_PLACEHOLDER . '</span>', $content);
    }

    /**
     * Test table view with facility that has no services
     * Requirements: 4.1
     * 
     * @test
     */
    public function it_handles_facility_with_no_services_correctly()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $this->actingAs($this->adminUser);
        
        // Ensure facility has no services
        $this->facility->services()->delete();
        $this->facility->refresh();
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // Verify service table still renders with empty state
        $this->assertStringContainsString('service-info', $content);
        $this->assertStringContainsString('サービス種類', $content);
        $this->assertStringContainsString(TestConstants::EMPTY_VALUE_PLACEHOLDER, $content);
    }

    /**
     * Test table view performance with large amounts of data
     * Requirements: 1.2, 4.1
     * 
     * @test
     */
    public function it_renders_table_view_efficiently_with_multiple_services()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        
        // Add multiple services to test performance
        for ($i = 0; $i < 5; $i++) {
            FacilityService::factory()->create([
                'facility_id' => $this->facility->id,
                'service_type' => 'テストサービス' . ($i + 1),
                'renewal_start_date' => Carbon::now()->subYears($i),
                'renewal_end_date' => Carbon::now()->addYears(5 - $i),
            ]);
        }
        
        $this->facility->refresh();
        $this->actingAs($this->adminUser);
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $startTime = microtime(true);
        $response = $this->get(route('facilities.show', $this->facility));
        $endTime = microtime(true);
        
        $response->assertStatus(TestConstants::HTTP_OK);
        
        // Verify all services are displayed
        foreach ($this->facility->services as $service) {
            $this->assertStringContainsString($service->service_type, $response->getContent());
        }
        
        // Basic performance check (should render within reasonable time)
        $renderTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $this->assertLessThan(TestConstants::MAX_RESPONSE_TIME_MS, $renderTime, 'Table view should render efficiently');
    }

    /**
     * Test table view accessibility features
     * Requirements: 1.3
     * 
     * @test
     */
    public function it_includes_proper_accessibility_features_in_table_view()
    {
        $this->facility = FacilityTestDataFactory::createComplete();
        $this->actingAs($this->adminUser);
        
        session([TestConstants::VIEW_MODE_SESSION_KEY => TestConstants::TABLE_VIEW_MODE]);
        
        $response = $this->get(route('facilities.show', $this->facility));
        $content = $response->getContent();
        
        // Verify table has proper semantic structure
        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('<tbody>', $content);
        $this->assertStringContainsString('<th', $content);
        $this->assertStringContainsString('<td', $content);
        
        // Verify table headers are properly marked
        $this->assertMatchesRegularExpression('/<th[^>]*>.*?会社名.*?<\/th>/', $content);
        $this->assertMatchesRegularExpression('/<th[^>]*>.*?事業所コード.*?<\/th>/', $content);
    }

    /**
     * Assert table structure exists
     */
    private function assertTableStructureExists(string $content): void
    {
        $this->assertStringContainsString(self::TABLE_VIEW_CLASS, $content);
        $this->assertStringContainsString(self::TABLE_RESPONSIVE_CLASS, $content);
        $this->assertStringContainsString(self::TABLE_BORDERED_CLASS, $content);
    }

    /**
     * Assert all data categories are present
     */
    private function assertAllDataCategoriesPresent(string $content): void
    {
        $this->assertTableContainsBasicInfo($content);
        $this->assertTableContainsContactInfo($content);
        $this->assertTableContainsBuildingInfo($content);
        $this->assertTableContainsFacilityInfo($content);
        $this->assertTableContainsServiceInfo($content);
    }

    /**
     * Assert table contains basic information
     */
    private function assertTableContainsBasicInfo(string $content): void
    {
        $expectedLabels = $this->getBasicInfoLabels();
        
        foreach ($expectedLabels as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    /**
     * Get basic info field labels
     */
    private function getBasicInfoLabels(): array
    {
        return ['会社名', '事業所コード', '施設名', '指定番号'];
    }

    /**
     * Assert table contains contact information
     */
    private function assertTableContainsContactInfo(string $content): void
    {
        $expectedLabels = $this->getContactInfoLabels();
        
        foreach ($expectedLabels as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    /**
     * Assert table contains building information
     */
    private function assertTableContainsBuildingInfo(string $content): void
    {
        $expectedLabels = $this->getBuildingInfoLabels();
        
        foreach ($expectedLabels as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    /**
     * Assert table contains facility information
     */
    private function assertTableContainsFacilityInfo(string $content): void
    {
        $expectedLabels = $this->getFacilityInfoLabels();
        
        foreach ($expectedLabels as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    /**
     * Get contact info field labels
     */
    private function getContactInfoLabels(): array
    {
        return ['郵便番号', '住所', '電話番号', 'FAX番号', 'メールアドレス', 'URL'];
    }

    /**
     * Get building info field labels
     */
    private function getBuildingInfoLabels(): array
    {
        return ['開設日', '開設年数', '建物構造', '建物階数'];
    }

    /**
     * Get facility info field labels
     */
    private function getFacilityInfoLabels(): array
    {
        return ['居室数', '内SS数', '定員数'];
    }

    /**
     * Assert service table exists
     */
    private function assertServiceTableExists(string $content): void
    {
        $this->assertStringContainsString(self::SERVICE_INFO_CLASS, $content);
        $this->assertStringContainsString(self::SERVICE_TYPE_LABEL, $content);
    }

    /**
     * Assert services are displayed correctly
     */
    private function assertServicesDisplayedCorrectly(string $content): void
    {
        foreach ($this->facility->services as $service) {
            $this->assertStringContainsString($service->service_type, $content);
            $this->assertServiceDatesFormatted($service, $content);
        }
    }

    /**
     * Assert service dates are properly formatted
     */
    private function assertServiceDatesFormatted(FacilityService $service, string $content): void
    {
        if ($service->renewal_start_date) {
            $expectedStartDate = $service->renewal_start_date->format('Y年m月d日');
            $this->assertStringContainsString($expectedStartDate, $content);
        }
        
        if ($service->renewal_end_date) {
            $expectedEndDate = $service->renewal_end_date->format('Y年m月d日');
            $this->assertStringContainsString($expectedEndDate, $content);
        }
    }

    /**
     * Assert table contains service information
     */
    private function assertTableContainsServiceInfo(string $content): void
    {
        $this->assertStringContainsString('サービス種類', $content);
    }

    /**
     * Assert number formatting with units
     */
    private function assertNumberFormattingWithUnits(string $content): void
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
                $expectedFormat = number_format($this->facility->{$field}) . $unit;
                $this->assertStringContainsString($expectedFormat, $content);
            }
        }
    }

    /**
     * Assert empty fields show placeholder
     */
    private function assertEmptyFieldsShowPlaceholder(string $content): void
    {
        // Check that empty fields are wrapped in text-muted spans with the placeholder
        $this->assertMatchesRegularExpression(
            '/<span[^>]*class="[^"]*text-muted[^"]*"[^>]*>' . preg_quote(TestConstants::EMPTY_VALUE_PLACEHOLDER, '/') . '<\/span>/',
            $content
        );
    }

    /**
     * Extract displayed data from HTML content for comparison
     */
    private function extractDisplayedData(string $content): array
    {
        $dom = $this->createDomFromContent($content);
        if (!$dom) {
            return [];
        }
        
        $xpath = new \DOMXPath($dom);
        $data = [];
        
        $selectors = $this->getComparisonDataSelectors();
        
        foreach ($selectors as $selector) {
            $data = array_merge($data, $this->extractTextFromSelector($xpath, $selector));
        }
        
        return array_unique($data);
    }

    /**
     * Get selectors for data comparison between views
     */
    private function getComparisonDataSelectors(): array
    {
        return [
            // Table view selectors
            '//td[not(contains(@class, "text-muted"))]',
            '//td[@class="value-cell svc-name"]',
            
            // Card view selectors
            '//span[contains(@class, "detail-value")]',
            '//div[contains(@class, "service-card-title")]',
            
            // Common selectors
            '//span[contains(@class, "svc-name")]',
        ];
    }

    /**
     * Create DOM document from HTML content
     */
    private function createDomFromContent(string $content): ?\DOMDocument
    {
        $dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        $previousErrorReporting = error_reporting(0);
        $loaded = $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        error_reporting($previousErrorReporting);
        
        return $loaded ? $dom : null;
    }

    /**
     * Get selectors for data extraction
     */
    private function getDataExtractionSelectors(): array
    {
        return [
            '//td[not(contains(@class, "text-muted"))]',
            '//span[contains(@class, "detail-value")]',
            '//span[contains(@class, "svc-name")]',
        ];
    }

    /**
     * Extract text from XPath selector
     */
    private function extractTextFromSelector(\DOMXPath $xpath, string $selector): array
    {
        $data = [];
        $elements = $xpath->query($selector);
        
        foreach ($elements as $element) {
            $text = trim($element->textContent);
            if ($this->isValidExtractedText($text)) {
                $data[] = $text;
            }
        }
        
        return $data;
    }

    /**
     * Check if extracted text is valid for comparison
     */
    private function isValidExtractedText(string $text): bool
    {
        return !empty($text) && $text !== TestConstants::EMPTY_VALUE_PLACEHOLDER;
    }

    /**
     * Test that table view includes comment functionality
     * Requirements: Comment system integration
     */
    public function test_it_includes_comment_functionality_in_table_view(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('facilities.show', $this->facility) . '?view_mode=table');
        
        $response->assertStatus(200);
        
        // Check for comment toggle buttons
        $response->assertSee('comment-toggle', false);
        $response->assertSee('data-section="basic_info"', false);
        $response->assertSee('data-section="service_info"', false);
        
        // Check for comment sections
        $response->assertSee('comment-section', false);
        $response->assertSee('comment-input', false);
        $response->assertSee('comment-submit', false);
        $response->assertSee('comment-list', false);
        
        // Check for comment count badges
        $response->assertSee('comment-count', false);
        
        // Check for proper ARIA attributes
        $response->assertSee('data-bs-toggle="tooltip"', false);
        $response->assertSee('title="コメントを表示/非表示"', false);
    }

    /**
     * Test that table view comment sections are properly structured
     * Requirements: Comment system structure
     */
    public function test_it_has_properly_structured_comment_sections_in_table_view(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('facilities.show', $this->facility) . '?view_mode=table');
        
        $response->assertStatus(200);
        
        // Check for basic info comment section
        $response->assertSee('基本情報のコメント', false);
        $response->assertSee('data-section="basic_info"', false);
        
        // Check for service info comment section
        $response->assertSee('サービス情報のコメント', false);
        $response->assertSee('data-section="service_info"', false);
        
        // Check for comment form structure
        $response->assertSee('input-group', false);
        $response->assertSee('placeholder="コメントを入力..."', false);
        $response->assertSee('fas fa-paper-plane', false);
        
        // Check for comment list containers
        $response->assertSee('comment-list', false);
    }

    /**
     * Test that table view maintains comment functionality across view switches
     * Requirements: View mode persistence with comments
     */
    public function test_it_maintains_comment_functionality_across_view_switches(): void
    {
        $this->actingAs($this->adminUser);
        
        // Test card view has comments
        $cardResponse = $this->get(route('facilities.show', $this->facility) . '?view_mode=card');
        $cardResponse->assertStatus(200);
        $cardResponse->assertSee('comment-toggle', false);
        
        // Test table view has comments
        $tableResponse = $this->get(route('facilities.show', $this->facility) . '?view_mode=table');
        $tableResponse->assertStatus(200);
        $tableResponse->assertSee('comment-toggle', false);
        
        // Both views should have the same comment sections
        $cardResponse->assertSee('data-section="basic_info"', false);
        $tableResponse->assertSee('data-section="basic_info"', false);
        
        $cardResponse->assertSee('data-section="service_info"', false);
        $tableResponse->assertSee('data-section="service_info"', false);
    }
}