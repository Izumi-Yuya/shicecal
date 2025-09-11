<?php

namespace App\Services\ServiceTable\Formatters;

use App\Services\ServiceTable\Contracts\ServiceFormatterInterface;

/**
 * Standard service formatter for regular facility services
 */
class StandardServiceFormatter implements ServiceFormatterInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Format service for display
     */
    public function format($service): array
    {
        if (! $this->canFormat($service)) {
            return $this->getEmptyFormat();
        }

        return [
            'service_type' => $this->sanitizeText($service->service_type),
            'period' => $this->formatServicePeriod($service),
            'has_data' => true,
            'css_class' => 'service-standard',
        ];
    }

    /**
     * Check if this formatter can handle the service
     */
    public function canFormat($service): bool
    {
        return $service &&
               isset($service->service_type) &&
               ! empty(trim($service->service_type));
    }

    /**
     * Format service period for display
     */
    private function formatServicePeriod($service): string
    {
        if (! $service->renewal_start_date && ! $service->renewal_end_date) {
            return $this->config['styling']['empty_value_text'];
        }

        $dateFormat = $this->config['display']['date_format'] ?? 'Y年m月d日';

        $startDate = $service->renewal_start_date
            ? $service->renewal_start_date->format($dateFormat)
            : '';

        $endDate = $service->renewal_end_date
            ? $service->renewal_end_date->format($dateFormat)
            : '';

        return $this->buildPeriodString($startDate, $endDate);
    }

    /**
     * Build period string from start and end dates
     */
    private function buildPeriodString(string $startDate, string $endDate): string
    {
        if ($startDate && $endDate) {
            return "{$startDate} 〜 {$endDate}";
        } elseif ($startDate) {
            return "{$startDate} 〜";
        } elseif ($endDate) {
            return "〜 {$endDate}";
        }

        return $this->config['styling']['empty_value_text'];
    }

    /**
     * Get empty format for services without data
     */
    private function getEmptyFormat(): array
    {
        return [
            'service_type' => $this->config['styling']['empty_value_text'],
            'period' => $this->config['styling']['empty_value_text'],
            'has_data' => false,
            'css_class' => 'service-empty',
        ];
    }

    /**
     * Sanitize text for safe display
     */
    private function sanitizeText(string $text): string
    {
        // Remove potentially dangerous characters and trim whitespace
        $sanitized = trim(strip_tags($text));

        // Limit length to prevent display issues
        $maxLength = $this->config['validation']['max_service_name_length'] ?? 100;

        return mb_strlen($sanitized) > $maxLength
            ? mb_substr($sanitized, 0, $maxLength).'...'
            : $sanitized;
    }
}
