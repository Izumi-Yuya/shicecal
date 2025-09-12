<?php

namespace Tests\Support;

use DOMDocument;
use DOMXPath;

/**
 * Service for extracting facility data from HTML content for testing
 */
class FacilityDataExtractor
{
    private const CARD_VALUE_SELECTOR = '//span[contains(@class, "detail-value")]';

    private const SERVICE_ELEMENT_SELECTOR = '//*[contains(@class, "service-card-title") or contains(@class, "svc-name")]';

    /**
     * Extract all displayed data from HTML content
     */
    public function extractDisplayedData(string $content): array
    {
        $dom = $this->createDomDocument($content);
        $xpath = new DOMXPath($dom);

        $data = [];
        $data = array_merge($data, $this->extractBySelector($xpath, self::CARD_VALUE_SELECTOR));
        $data = array_merge($data, $this->extractBySelector($xpath, self::SERVICE_ELEMENT_SELECTOR));

        return array_unique(array_filter($data, [$this, 'isValidDataPoint']));
    }

    /**
     * Extract facility field values specifically
     */
    public function extractFacilityFields(string $content, array $fieldNames): array
    {
        $allData = $this->extractDisplayedData($content);

        return array_filter($allData, function ($item) use ($fieldNames) {
            return in_array($item, $fieldNames, true);
        });
    }

    /**
     * Count occurrences of empty value placeholder
     */
    public function countEmptyValuePlaceholders(string $content): int
    {
        return substr_count($content, TestConstants::EMPTY_VALUE_PLACEHOLDER);
    }

    /**
     * Create DOM document with error handling
     */
    private function createDomDocument(string $content): DOMDocument
    {
        $dom = new DOMDocument;

        $previousErrorReporting = error_reporting(0);
        $loaded = $dom->loadHTML(
            $content,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        error_reporting($previousErrorReporting);

        if (! $loaded) {
            throw new \RuntimeException('Failed to parse HTML content for data extraction');
        }

        return $dom;
    }

    /**
     * Extract text content by XPath selector
     */
    private function extractBySelector(DOMXPath $xpath, string $selector): array
    {
        $data = [];
        $elements = $xpath->query($selector);

        foreach ($elements as $element) {
            $text = trim($element->textContent);
            if (! empty($text)) {
                $data[] = $text;
            }
        }

        return $data;
    }

    /**
     * Check if text is a valid data point
     */
    private function isValidDataPoint(string $text): bool
    {
        return ! empty($text) && $text !== TestConstants::EMPTY_VALUE_PLACEHOLDER;
    }
}
