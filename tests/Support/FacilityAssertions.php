<?php

namespace Tests\Support;

use App\Models\Facility;
use PHPUnit\Framework\Assert;

/**
 * Custom assertions for facility testing
 */
class FacilityAssertions
{
    /**
     * Assert that facility data appears in both views
     */
    public static function assertDataParityBetweenViews(
        string $cardContent,
        string $tableContent,
        Facility $facility
    ): void {
        $extractor = new FacilityDataExtractor;

        $cardData = $extractor->extractDisplayedData($cardContent);
        $tableData = $extractor->extractDisplayedData($tableContent);

        // Essential fields must appear in both views
        $essentialFields = [
            $facility->company_name,
            $facility->facility_name,
            $facility->office_code,
        ];

        foreach ($essentialFields as $field) {
            if ($field) {
                Assert::assertContains($field, $cardData, "Essential field '{$field}' missing from card view");
                Assert::assertContains($field, $tableData, "Essential field '{$field}' missing from table view");
            }
        }

        // All card data should appear in table data
        foreach ($cardData as $dataPoint) {
            Assert::assertContains(
                $dataPoint,
                $tableData,
                "Data point '{$dataPoint}' from card view missing in table view"
            );
        }
    }

    /**
     * Assert proper date formatting in content
     */
    public static function assertJapaneseDateFormatting(string $content, Facility $facility): void
    {
        if ($facility->opening_date) {
            $expectedFormat = $facility->opening_date->format('Y年m月d日');
            Assert::assertStringContainsString(
                $expectedFormat,
                $content,
                "Japanese date format not found: {$expectedFormat}"
            );
        }
    }

    /**
     * Assert proper number formatting with units
     */
    public static function assertNumberFormattingWithUnits(string $content, Facility $facility): void
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
                $expectedFormat = number_format($facility->{$field}).$unit;
                Assert::assertStringContainsString(
                    $expectedFormat,
                    $content,
                    "Number with unit not found: {$expectedFormat}"
                );
            }
        }
    }

    /**
     * Assert proper link formatting
     */
    public static function assertLinkFormatting(string $content, Facility $facility): void
    {
        // Email links
        if ($facility->email) {
            Assert::assertStringContainsString(
                'href="mailto:'.$facility->email.'"',
                $content,
                'Email link not properly formatted'
            );
        }

        // Website links
        if ($facility->website_url) {
            Assert::assertStringContainsString(
                'href="'.$facility->website_url.'"',
                $content,
                'Website link not properly formatted'
            );
            Assert::assertStringContainsString(
                'target="_blank"',
                $content,
                "Website link missing target='_blank'"
            );
        }
    }

    /**
     * Assert empty value handling
     */
    public static function assertEmptyValueHandling(string $content, int $expectedMinCount = 1): void
    {
        $emptyValueCount = substr_count($content, TestConstants::EMPTY_VALUE_PLACEHOLDER);

        Assert::assertGreaterThanOrEqual(
            $expectedMinCount,
            $emptyValueCount,
            "Expected at least {$expectedMinCount} empty value placeholders, found {$emptyValueCount}"
        );
    }

    /**
     * Assert service information completeness
     */
    public static function assertServiceInformationComplete(string $content, Facility $facility): void
    {
        $facility->load('services');

        foreach ($facility->services as $service) {
            Assert::assertStringContainsString(
                $service->service_type,
                $content,
                "Service type not found: {$service->service_type}"
            );

            if ($service->renewal_start_date) {
                $formattedDate = $service->renewal_start_date->format('Y年m月d日');
                Assert::assertStringContainsString(
                    $formattedDate,
                    $content,
                    "Service start date not found: {$formattedDate}"
                );
            }

            if ($service->renewal_end_date) {
                $formattedDate = $service->renewal_end_date->format('Y年m月d日');
                Assert::assertStringContainsString(
                    $formattedDate,
                    $content,
                    "Service end date not found: {$formattedDate}"
                );
            }
        }
    }

    /**
     * Assert view mode specific elements
     */
    public static function assertViewModeElements(string $content, string $viewMode): void
    {
        if ($viewMode === 'table') {
            Assert::assertStringContainsString(
                'facility-table-view',
                $content,
                'Table view class not found'
            );
            Assert::assertStringContainsString(
                'table-responsive',
                $content,
                'Responsive table wrapper not found'
            );
        } else {
            Assert::assertStringContainsString(
                'facility-info-card',
                $content,
                'Card view class not found'
            );
        }
    }

    /**
     * Assert comment functionality elements
     */
    public static function assertCommentFunctionality(string $content): void
    {
        $commentElements = [
            'comment-toggle',
            'comment-section',
            'comment-input',
            'comment-submit',
            'comment-list',
            'comment-count',
        ];

        foreach ($commentElements as $element) {
            Assert::assertStringContainsString(
                $element,
                $content,
                "Comment element not found: {$element}"
            );
        }
    }
}
