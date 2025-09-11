<?php

namespace App\Services;

use App\Services\ServiceTable\ServiceFormatterFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing service table display logic
 * Implements business logic for service table rendering
 */
class ServiceTableService
{
    // Cache configuration
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    private const CACHE_KEY_PREFIX = 'service_table_data';

    // Display configuration
    private const MIN_SERVICES_FOR_TEMPLATE_ROWS = 1;

    private array $config;

    private ServiceFormatterFactory $formatterFactory;

    public function __construct()
    {
        $this->config = config('service-table');
        $this->formatterFactory = new ServiceFormatterFactory($this->config);
    }

    /**
     * Prepare services data for table display
     */
    public function prepareServicesForDisplay(Collection $services): array
    {
        $maxServices = $this->config['display']['max_services'];
        $showEmptyRows = $this->config['display']['show_empty_rows'];

        // Ensure we have at least one row to show headers
        if ($services->isEmpty()) {
            return [
                'services' => collect([null]), // Single null service for header row
                'hasData' => false,
                'templateRowsNeeded' => $showEmptyRows ? max(0, $maxServices - 1) : 0,
            ];
        }

        // Limit services to max display count
        $displayServices = $services->take($maxServices);
        $templateRowsNeeded = $showEmptyRows ? max(0, $maxServices - $displayServices->count()) : 0;

        return [
            'services' => $displayServices,
            'hasData' => true,
            'templateRowsNeeded' => $templateRowsNeeded,
        ];
    }

    /**
     * Prepare complete view data for service table with caching
     */
    public function prepareViewData(Collection $services): array
    {
        $cacheKey = $this->generateCacheKey($services);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($services) {
            $displayData = $this->prepareServicesForDisplay($services);
            $configData = $this->prepareConfigData($services);

            return array_merge($displayData, $configData);
        });
    }

    /**
     * Prepare configuration data for view
     */
    private function prepareConfigData(Collection $services): array
    {
        return [
            'config' => $this->config,
            'maxServices' => $this->config['display']['max_services'],
            'emptyValueText' => $this->config['styling']['empty_value_text'],
            'headerBgClass' => $this->config['styling']['header_bg_class'],
            'headerTextClass' => $this->config['styling']['header_text_class'],
            'shouldShowTemplateRows' => $this->shouldShowTemplateRows($services),
        ];
    }

    /**
     * Generate cache key for services data
     * Improved to handle edge cases and use more efficient hashing
     */
    private function generateCacheKey(Collection $services): string
    {
        // Handle empty collections
        if ($services->isEmpty()) {
            $configHash = $this->getConfigHash();

            return self::CACHE_KEY_PREFIX."_empty_{$configHash}";
        }

        // Use more efficient data extraction for cache key generation
        $serviceData = $services->map(function ($service) {
            return [
                'id' => $service->id ?? 0,
                'updated_at' => optional($service->updated_at)->timestamp ?? 0,
                'service_type' => $service->service_type ?? '',
                // Include renewal dates for cache invalidation
                'renewal_start' => optional($service->renewal_start_date)->timestamp ?? 0,
                'renewal_end' => optional($service->renewal_end_date)->timestamp ?? 0,
            ];
        })->toArray();

        // Use JSON encoding instead of serialize for better performance
        $serviceHash = hash('xxh64', json_encode($serviceData));
        $configHash = $this->getConfigHash();

        return self::CACHE_KEY_PREFIX."_{$serviceHash}_{$configHash}";
    }

    /**
     * Get configuration hash for cache key
     */
    private function getConfigHash(): string
    {
        static $configHash = null;

        if ($configHash === null) {
            $configHash = hash('xxh64', json_encode($this->config));
        }

        return $configHash;
    }

    /**
     * Determine if template rows should be shown
     */
    public function shouldShowTemplateRows(Collection $services): bool
    {
        return $this->config['display']['show_empty_rows'] &&
               $services->count() >= self::MIN_SERVICES_FOR_TEMPLATE_ROWS;
    }

    /**
     * Get table configuration
     */
    public function getTableConfig(): array
    {
        return $this->config;
    }

    /**
     * Get column configuration
     */
    public function getColumnConfig(): array
    {
        return $this->config['columns'];
    }

    /**
     * Get styling configuration
     */
    public function getStylingConfig(): array
    {
        return $this->config['styling'];
    }

    /**
     * Check if service has valid data
     */
    public function hasValidServiceData($service): bool
    {
        return $service &&
               isset($service->service_type) &&
               ! empty(trim($service->service_type));
    }

    /**
     * Format service for display using appropriate formatter
     */
    public function formatServiceForDisplay($service): array
    {
        $formatter = $this->formatterFactory->createFormatter($service);

        return $formatter->format($service);
    }

    /**
     * Generate CSS classes for responsive column widths
     */
    public function generateColumnCss(): string
    {
        $css = '';
        $columns = $this->config['columns'];

        foreach ($columns as $key => $column) {
            $className = "col-{$key}";
            $width = $column['width_percentage'];
            $mobileWidth = $column['mobile_width_percentage'];

            $css .= ".service-info .{$className} { width: {$width}%; }\n";
            $css .= "@media (max-width: 768px) { .service-info .{$className} { width: {$mobileWidth}%; } }\n";
        }

        return $css;
    }
}
