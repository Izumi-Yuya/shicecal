<?php

namespace App\Services\ServiceTable;

/**
 * Service Table Configuration Manager
 *
 * Provides type-safe configuration management for service table components
 * with validation and environment variable support.
 */
class ServiceTableConfig
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('service-table');
    }

    /**
     * Get display configuration with validation
     */
    public function getDisplayConfig(): array
    {
        $display = $this->config['display'] ?? [];

        return [
            'max_services' => $this->validateRange($display['max_services'] ?? 10, 1, 50),
            'show_empty_rows' => (bool) ($display['show_empty_rows'] ?? false),
            'enable_comments' => (bool) ($display['enable_comments'] ?? true),
            'date_format' => $this->validateDateFormat($display['date_format'] ?? 'Y年m月d日'),
            'items_per_page' => $this->validateRange($display['items_per_page'] ?? 25, 5, 100),
            'min_display_rows' => $this->validateRange($display['min_display_rows'] ?? 1, 1, 10),
            'period_separator' => $display['period_separator'] ?? '〜',
        ];
    }

    /**
     * Get column configuration with validation
     */
    public function getColumnConfig(): array
    {
        $columns = $this->config['columns'] ?? [];

        foreach ($columns as $key => $column) {
            $columns[$key] = $this->validateColumnConfig($column);
        }

        return $columns;
    }

    /**
     * Get styling configuration
     */
    public function getStylingConfig(): array
    {
        return $this->config['styling'] ?? [
            'table_class' => 'service-info table table-bordered',
            'header_bg_class' => 'bg-primary',
            'header_text_class' => 'text-white',
            'empty_value_class' => 'text-muted',
            'empty_value_text' => '未設定',
            'hover_effect' => true,
            'striped_rows' => false,
        ];
    }

    /**
     * Get cache configuration with validation
     */
    public function getCacheConfig(): array
    {
        $cache = $this->config['cache'] ?? [];

        return [
            'enabled' => (bool) ($cache['enabled'] ?? true),
            'ttl' => $this->validateRange($cache['ttl'] ?? 300, 60, 3600),
            'key_prefix' => $cache['key_prefix'] ?? 'service_table',
            'tags' => $cache['tags'] ?? ['service_table', 'facility_data'],
        ];
    }

    /**
     * Get validation configuration
     */
    public function getValidationConfig(): array
    {
        $validation = $this->config['validation'] ?? [];

        return [
            'max_service_name_length' => $this->validateRange($validation['max_service_name_length'] ?? 100, 10, 500),
            'required_fields' => $validation['required_fields'] ?? ['service_type'],
            'date_validation' => (bool) ($validation['date_validation'] ?? true),
            'allow_future_dates' => (bool) ($validation['allow_future_dates'] ?? true),
        ];
    }

    /**
     * Get accessibility configuration
     */
    public function getAccessibilityConfig(): array
    {
        return $this->config['accessibility'] ?? [
            'enable_aria_labels' => true,
            'enable_screen_reader_support' => true,
            'keyboard_navigation' => true,
            'high_contrast_support' => true,
        ];
    }

    /**
     * Get performance configuration
     */
    public function getPerformanceConfig(): array
    {
        $performance = $this->config['performance'] ?? [];

        return [
            'lazy_loading' => (bool) ($performance['lazy_loading'] ?? false),
            'debounce_search_ms' => $this->validateRange($performance['debounce_search_ms'] ?? 300, 100, 2000),
        ];
    }

    /**
     * Validate numeric range
     */
    private function validateRange(int $value, int $min, int $max): int
    {
        return max($min, min($value, $max));
    }

    /**
     * Validate date format
     */
    private function validateDateFormat(string $format): string
    {
        $allowedFormats = ['Y年m月d日', 'Y/m/d', 'Y-m-d', 'm/d/Y'];

        return in_array($format, $allowedFormats) ? $format : 'Y年m月d日';
    }

    /**
     * Validate column configuration
     */
    private function validateColumnConfig(array $column): array
    {
        return [
            'label' => $column['label'] ?? '',
            'width_percentage' => $this->validateRange($column['width_percentage'] ?? 33, 5, 95),
            'mobile_width_percentage' => $this->validateRange($column['mobile_width_percentage'] ?? 100, 10, 100),
            'css_class' => $column['css_class'] ?? '',
            'sortable' => (bool) ($column['sortable'] ?? false),
            'required' => (bool) ($column['required'] ?? false),
        ];
    }
}
