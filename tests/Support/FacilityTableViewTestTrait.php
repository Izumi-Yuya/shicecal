<?php

namespace Tests\Support;

use App\Models\Facility;
use Illuminate\Testing\TestResponse;

/**
 * Trait for common facility table view test assertions
 * Reduces code duplication and improves test maintainability
 */
trait FacilityTableViewTestTrait
{
    /**
     * Assert that table view structure is present
     */
    protected function assertTableViewStructure(TestResponse $response): void
    {
        $response->assertSee('facility-table-view', false);
        $response->assertSee('table-responsive', false);
        $response->assertSee('table table-bordered', false);
    }

    /**
     * Assert that essential facility data is displayed
     */
    protected function assertEssentialFacilityData(TestResponse $response, Facility $facility): void
    {
        $response->assertSee($facility->company_name);
        $response->assertSee($facility->facility_name);
        $response->assertSee($facility->office_code);
    }

    /**
     * Assert proper date formatting in Japanese format
     */
    protected function assertJapaneseDateFormatting(TestResponse $response, Facility $facility): void
    {
        if ($facility->opening_date) {
            $expectedDate = $facility->opening_date->format('Y年m月d日');
            $response->assertSee($expectedDate);
        }
    }

    /**
     * Assert number formatting with units
     */
    protected function assertNumberFormattingWithUnits(TestResponse $response, Facility $facility): void
    {
        $numberFields = [
            'building_floors' => '階',
            'paid_rooms_count' => '室',
            'ss_rooms_count' => '室',
            'capacity' => '名',
            'years_in_operation' => '年',
        ];

        foreach ($numberFields as $field => $unit) {
            if ($facility->{$field} !== null) {
                $expectedFormat = number_format($facility->{$field}) . $unit;
                $response->assertSee($expectedFormat);
            }
        }
    }

    /**
     * Assert link formatting for contact information
     */
    protected function assertLinkFormatting(TestResponse $response, Facility $facility): void
    {
        if ($facility->email) {
            $response->assertSee('href="mailto:' . $facility->email . '"', false);
        }

        if ($facility->website_url) {
            $response->assertSee('href="' . $facility->website_url . '"', false);
            $response->assertSee('target="_blank"', false);
        }
    }

    /**
     * Assert empty value handling
     */
    protected function assertEmptyValueHandling(TestResponse $response): void
    {
        $response->assertSee('未設定');
        $response->assertSee('<span class="text-muted">未設定</span>', false);
    }

    /**
     * Assert comment functionality is present
     */
    protected function assertCommentFunctionality(TestResponse $response): void
    {
        $response->assertSee('comment-toggle', false);
        $response->assertSee('comment-section', false);
        $response->assertSee('comment-input', false);
        $response->assertSee('comment-submit', false);
        $response->assertSee('comment-list', false);
        $response->assertSee('comment-count', false);
    }

    /**
     * Assert responsive table structure
     */
    protected function assertResponsiveTableStructure(TestResponse $response): void
    {
        $response->assertSee('table-responsive', false);
        $response->assertSee('table table-bordered', false);
        
        // Check for proper column structure (4 columns for 2x2 layout)
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<tr[^>]*>.*?<th[^>]*>.*?<\/th>.*?<td[^>]*>.*?<\/td>.*?<th[^>]*>.*?<\/th>.*?<td[^>]*>.*?<\/td>.*?<\/tr>/s',
            $content
        );
    }

    /**
     * Assert service information completeness
     */
    protected function assertServiceInformation(TestResponse $response, Facility $facility): void
    {
        $response->assertSee('service-info', false);
        $response->assertSee('サービス種類');

        foreach ($facility->services as $service) {
            $response->assertSee($service->service_type);
            
            if ($service->renewal_start_date) {
                $expectedDate = $service->renewal_start_date->format('Y年m月d日');
                $response->assertSee($expectedDate);
            }
            
            if ($service->renewal_end_date) {
                $expectedDate = $service->renewal_end_date->format('Y年m月d日');
                $response->assertSee($expectedDate);
            }
        }
    }

    /**
     * Assert badge formatting
     */
    protected function assertBadgeFormatting(TestResponse $response, Facility $facility): void
    {
        $response->assertSee('badge bg-primary', false);
        $response->assertSee($facility->office_code);
    }

    /**
     * Assert accessibility features
     */
    protected function assertAccessibilityFeatures(TestResponse $response): void
    {
        $content = $response->getContent();
        
        // Check for proper semantic structure
        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('<tbody>', $content);
        $this->assertStringContainsString('<th', $content);
        $this->assertStringContainsString('<td', $content);
        
        // Check for proper table headers
        $this->assertMatchesRegularExpression('/<th[^>]*>.*?会社名.*?<\/th>/', $content);
        $this->assertMatchesRegularExpression('/<th[^>]*>.*?事業所コード.*?<\/th>/', $content);
    }

    /**
     * Get table view content for a facility
     */
    protected function getTableViewContent(Facility $facility): string
    {
        session(['facility_basic_info_view_mode' => 'table']);
        
        $response = $this->get(route('facilities.show', $facility));
        $response->assertStatus(200);
        
        return $response->getContent();
    }

    /**
     * Get card view content for a facility
     */
    protected function getCardViewContent(Facility $facility): string
    {
        session(['facility_basic_info_view_mode' => 'card']);
        
        $response = $this->get(route('facilities.show', $facility));
        $response->assertStatus(200);
        
        return $response->getContent();
    }

    /**
     * Extract displayed data from HTML content for comparison
     */
    protected function extractDisplayedData(string $content): array
    {
        $dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        $previousErrorReporting = error_reporting(0);
        $loaded = $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        error_reporting($previousErrorReporting);
        
        if (!$loaded) {
            return [];
        }
        
        $xpath = new \DOMXPath($dom);
        $data = [];
        
        // Extract data from various selectors
        $selectors = [
            '//td[not(contains(@class, "text-muted"))]',
            '//span[contains(@class, "detail-value")]',
            '//span[contains(@class, "svc-name")]',
        ];
        
        foreach ($selectors as $selector) {
            $elements = $xpath->query($selector);
            foreach ($elements as $element) {
                $text = trim($element->textContent);
                if (!empty($text) && $text !== '未設定') {
                    $data[] = $text;
                }
            }
        }
        
        return array_unique($data);
    }

    /**
     * Assert data parity between two views
     */
    protected function assertDataParity(array $cardData, array $tableData, Facility $facility): void
    {
        // Essential fields that must appear in both views
        $essentialFields = [
            $facility->company_name,
            $facility->facility_name,
            $facility->office_code,
        ];
        
        foreach ($essentialFields as $field) {
            if ($field) {
                $this->assertContains($field, $cardData, "Essential field '{$field}' missing from card view");
                $this->assertContains($field, $tableData, "Essential field '{$field}' missing from table view");
            }
        }
        
        // Check that all card data appears in table data
        foreach ($cardData as $dataPoint) {
            $this->assertContains(
                $dataPoint,
                $tableData,
                "Data point '{$dataPoint}' from card view is missing in table view"
            );
        }
    }
}