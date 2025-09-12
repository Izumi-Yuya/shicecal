<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for managing table configurations
 * 
 * This service handles loading, validating, and caching table configurations
 * for the standardized facility table system.
 */
class TableConfigService
{
    /**
     * Available table types
     */
    public const BASIC_INFO_TABLE = 'basic_info';
    public const SERVICE_INFO_TABLE = 'service_info';
    public const LAND_INFO_TABLE = 'land_info';

    /**
     * Cache key prefix for table configurations
     */
    private const CACHE_PREFIX = 'table_config_';
    
    /**
     * Cache key prefix for compiled table configurations
     */
    private const COMPILED_CACHE_PREFIX = 'compiled_table_config_';

    /**
     * Default cache TTL in seconds
     */
    private const DEFAULT_CACHE_TTL = 300;
    
    /**
     * Compiled configuration cache TTL (longer since these are processed)
     */
    private const COMPILED_CACHE_TTL = 1800;

    /**
     * Get table configuration for a specific table type
     *
     * @param string $tableType The table type (basic_info, service_info, land_info)
     * @return array The table configuration
     * @throws InvalidArgumentException If table type is invalid
     */
    public function getTableConfig(string $tableType): array
    {
        if (!$this->isValidTableType($tableType)) {
            throw new InvalidArgumentException("Invalid table type: {$tableType}");
        }

        $cacheKey = self::CACHE_PREFIX . $tableType;
        $cacheTtl = Config::get('table-config.global_settings.performance.cache_ttl', self::DEFAULT_CACHE_TTL);

        if (Config::get('table-config.global_settings.performance.cache_enabled', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($tableType) {
                return $this->loadTableConfig($tableType);
            });
        }

        return $this->loadTableConfig($tableType);
    }

    /**
     * Validate a table configuration
     *
     * @param array $config The configuration to validate
     * @return bool True if valid, false otherwise
     */
    public function validateConfig(array $config): bool
    {
        $validator = app(TableConfigValidator::class);
        $result = $validator->validate($config);
        
        if (!$result->isValid()) {
            Log::warning('Table configuration validation failed', [
                'errors' => $result->getErrors(),
                'config' => $config
            ]);
        }
        
        return $result->isValid();
    }

    /**
     * Validate a table configuration and return detailed results
     *
     * @param array $config The configuration to validate
     * @return ValidationResult The detailed validation result
     */
    public function validateConfigDetailed(array $config): ValidationResult
    {
        $validator = app(TableConfigValidator::class);
        return $validator->validate($config);
    }

    /**
     * Merge configuration with defaults
     *
     * @param array $config The custom configuration
     * @return array The merged configuration
     */
    public function mergeWithDefaults(array $config): array
    {
        $defaults = $this->getDefaultConfig();
        
        // Use array_merge for non-nested arrays to avoid duplication
        $merged = array_merge($defaults, $config);
        
        // Handle nested arrays separately
        if (isset($config['layout']) && isset($defaults['layout'])) {
            $merged['layout'] = array_merge($defaults['layout'], $config['layout']);
        }
        
        if (isset($config['styling']) && isset($defaults['styling'])) {
            $merged['styling'] = array_merge($defaults['styling'], $config['styling']);
        }
        
        if (isset($config['features']) && isset($defaults['features'])) {
            $merged['features'] = array_merge($defaults['features'], $config['features']);
        }
        
        return $merged;
    }

    /**
     * Get all available table types
     *
     * @return array Array of table type constants
     */
    public function getAvailableTableTypes(): array
    {
        return [
            self::BASIC_INFO_TABLE,
            self::SERVICE_INFO_TABLE,
            self::LAND_INFO_TABLE
        ];
    }

    /**
     * Clear cached configuration for a table type
     *
     * @param string $tableType The table type to clear cache for
     * @return bool True if cache was cleared
     */
    public function clearCache(string $tableType = null): bool
    {
        if ($tableType) {
            $cacheKey = self::CACHE_PREFIX . $tableType;
            $result1 = Cache::forget($cacheKey);
            
            // Also clear compiled caches for this table type
            $pattern = self::COMPILED_CACHE_PREFIX . $tableType . '_*';
            $result2 = $this->clearCachePattern($pattern);
            
            return $result1 || $result2;
        }

        // Clear all table config caches
        $results = [];
        foreach ($this->getAvailableTableTypes() as $type) {
            $cacheKey = self::CACHE_PREFIX . $type;
            $results[] = Cache::forget($cacheKey);
            
            // Clear compiled caches
            $pattern = self::COMPILED_CACHE_PREFIX . $type . '_*';
            $results[] = $this->clearCachePattern($pattern);
        }

        // Return true if at least one cache was cleared or if no caches existed
        return !empty($results);
    }
    
    /**
     * Clear cache entries matching a pattern
     *
     * @param string $pattern The cache key pattern
     * @return bool True if any caches were cleared
     */
    private function clearCachePattern(string $pattern): bool
    {
        // For Redis cache, we can use pattern matching
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            try {
                $redis = Cache::getStore()->connection();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    $redis->del($keys);
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clear cache pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // For other cache stores, we can't efficiently clear by pattern
        // so we'll just return false to indicate no specific clearing was done
        return false;
    }

    /**
     * Get comment section configuration for a table type
     *
     * @param string $tableType The table type
     * @return array|null The comment section configuration or null if not found
     */
    public function getCommentSectionConfig(string $tableType): ?array
    {
        $commentSections = Config::get('table-config.comment_sections', []);
        
        return $commentSections[$tableType] ?? null;
    }

    /**
     * Get global settings
     *
     * @return array The global settings
     */
    public function getGlobalSettings(): array
    {
        return Config::get('table-config.global_settings', []);
    }

    /**
     * Load table configuration from config file
     *
     * @param string $tableType The table type
     * @return array The loaded configuration
     */
    private function loadTableConfig(string $tableType): array
    {
        try {
            $config = Config::get("table-config.tables.{$tableType}");
            
            if (!$config) {
                Log::warning("Table configuration not found for type: {$tableType}");
                return $this->handleConfigNotFound($tableType);
            }

            $validationResult = $this->validateConfigDetailed($config);
            
            if (!$validationResult->isValid()) {
                return $this->handleValidationFailure($tableType, $validationResult, $config);
            }

            return $config;
            
        } catch (\Exception $e) {
            return $this->handleConfigException($tableType, $e);
        }
    }

    /**
     * Handle configuration not found
     *
     * @param string $tableType The table type
     * @return array The fallback configuration
     */
    private function handleConfigNotFound(string $tableType): array
    {
        $errorHandler = app(TableErrorHandler::class);
        $exception = new \Exception("Configuration not found for table type: {$tableType}");
        
        return $errorHandler->handleConfigError($tableType, $exception);
    }

    /**
     * Handle validation failure
     *
     * @param string $tableType The table type
     * @param ValidationResult $validationResult The validation result
     * @param array $config The invalid configuration
     * @return array The corrected or fallback configuration
     */
    private function handleValidationFailure(string $tableType, ValidationResult $validationResult, array $config): array
    {
        $errorHandler = app(TableErrorHandler::class);
        
        return $errorHandler->handleValidationError($tableType, $validationResult, $config);
    }

    /**
     * Handle configuration loading exception
     *
     * @param string $tableType The table type
     * @param \Exception $exception The exception
     * @return array The fallback configuration
     */
    private function handleConfigException(string $tableType, \Exception $exception): array
    {
        $errorHandler = app(TableErrorHandler::class);
        
        return $errorHandler->handleConfigError($tableType, $exception);
    }

    /**
     * Check if table type is valid
     *
     * @param string $tableType The table type to check
     * @return bool True if valid
     */
    private function isValidTableType(string $tableType): bool
    {
        return in_array($tableType, $this->getAvailableTableTypes());
    }

    /**
     * Perform detailed configuration validation
     *
     * @param array $config The configuration to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function performConfigValidation(array $config): void
    {
        $requiredFields = Config::get('table-config.global_settings.validation.required_fields', ['key', 'label', 'type']);
        $strictMode = Config::get('table-config.global_settings.validation.strict_mode', true);

        // Validate columns exist
        if (!isset($config['columns']) || !is_array($config['columns']) || empty($config['columns'])) {
            throw new InvalidArgumentException('Table configuration must have at least one column');
        }

        // Validate each column
        foreach ($config['columns'] as $index => $column) {
            if (!is_array($column)) {
                throw new InvalidArgumentException("Column at index {$index} must be an array");
            }

            // Check required fields
            foreach ($requiredFields as $field) {
                if (!isset($column[$field]) || empty($column[$field])) {
                    throw new InvalidArgumentException("Column at index {$index} missing required field: {$field}");
                }
            }

            // Validate column type
            $validTypes = ['text', 'email', 'url', 'number', 'date', 'date_range', 'select'];
            if (!in_array($column['type'], $validTypes)) {
                throw new InvalidArgumentException("Invalid column type '{$column['type']}' at index {$index}");
            }

            // Validate select options if type is select
            if ($column['type'] === 'select' && (!isset($column['options']) || !is_array($column['options']))) {
                throw new InvalidArgumentException("Select column at index {$index} must have options array");
            }
        }

        // Validate layout if present
        if (isset($config['layout'])) {
            $validLayoutTypes = ['key_value_pairs', 'standard_table', 'grouped_rows', 'service_table'];
            if (isset($config['layout']['type']) && !in_array($config['layout']['type'], $validLayoutTypes)) {
                throw new InvalidArgumentException("Invalid layout type: {$config['layout']['type']}");
            }
        }
    }

    /**
     * Get default configuration structure
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'columns' => [],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true,
                'responsive_breakpoint' => 'md'
            ],
            'styling' => [
                'table_class' => 'table table-bordered',
                'header_class' => 'bg-light',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => false,
                'sorting' => false,
                'filtering' => false
            ]
        ];
    }

    /**
     * Get default configuration for a specific table type
     *
     * @param string $tableType The table type
     * @return array Default configuration for the type
     */
    private function getDefaultConfigForType(string $tableType): array
    {
        $baseConfig = $this->getDefaultConfig();

        switch ($tableType) {
            case self::BASIC_INFO_TABLE:
                $baseConfig['layout']['type'] = 'key_value_pairs';
                $baseConfig['styling']['table_class'] = 'table table-bordered facility-info';
                break;

            case self::SERVICE_INFO_TABLE:
                $baseConfig['layout']['type'] = 'grouped_rows';
                $baseConfig['styling']['table_class'] = 'table table-bordered service-info';
                break;

            case self::LAND_INFO_TABLE:
                $baseConfig['layout']['type'] = 'standard_table';
                $baseConfig['styling']['table_class'] = 'table table-bordered land-info';
                break;
        }

        return $baseConfig;
    }

    /**
     * Get table configuration with dynamic columns applied
     *
     * @param string $tableType The table type
     * @param array $data The data to analyze for dynamic columns
     * @return array The configuration with dynamic columns
     */
    public function getConfigWithDynamicColumns(string $tableType, array $data = []): array
    {
        $config = $this->getTableConfig($tableType);
        
        if (empty($data)) {
            return $config;
        }

        // Create cache key based on data structure for compiled config
        $dataHash = $this->generateDataHash($data);
        $compiledCacheKey = self::COMPILED_CACHE_PREFIX . $tableType . '_' . $dataHash;
        
        if (Config::get('table-config.global_settings.performance.cache_enabled', true)) {
            return Cache::remember($compiledCacheKey, self::COMPILED_CACHE_TTL, function () use ($config, $data) {
                return $this->compileConfigWithData($config, $data);
            });
        }

        return $this->compileConfigWithData($config, $data);
    }
    
    /**
     * Compile configuration with data optimizations
     *
     * @param array $config The base configuration
     * @param array $data The data to analyze
     * @return array The compiled configuration
     */
    private function compileConfigWithData(array $config, array $data): array
    {
        $formatter = app(TableDataFormatter::class);
        
        // Add dynamic columns based on data
        if ($config['features']['dynamic_columns'] ?? false) {
            $config['columns'] = $formatter->addDynamicColumns($config['columns'], $data);
        }
        
        // Filter conditional columns
        $config['columns'] = $formatter->filterConditionalColumns($config['columns'], $data);
        
        // Calculate optimal widths if enabled
        if ($config['features']['auto_width'] ?? false) {
            $config['columns'] = $formatter->calculateOptimalColumnWidths($config['columns'], $data);
        }
        
        // Pre-compile CSS classes for performance
        $config['compiled_classes'] = $this->preCompileCssClasses($config);
        
        // Pre-calculate responsive breakpoints
        $config['compiled_responsive'] = $this->preCalculateResponsive($config);
        
        return $config;
    }
    
    /**
     * Generate hash for data structure to use in cache keys
     *
     * @param array $data The data array
     * @return string The hash
     */
    private function generateDataHash(array $data): string
    {
        // Create a lightweight hash based on data structure, not content
        $structure = [];
        
        if (!empty($data)) {
            $firstRow = reset($data);
            if (is_array($firstRow)) {
                $structure['keys'] = array_keys($firstRow);
                $structure['count'] = count($data);
                
                // Sample a few rows to detect data types
                $sampleSize = min(5, count($data));
                $sample = array_slice($data, 0, $sampleSize);
                $structure['types'] = $this->detectDataTypes($sample);
            }
        }
        
        return md5(json_encode($structure));
    }
    
    /**
     * Detect data types from sample data
     *
     * @param array $sample Sample data rows
     * @return array Data type information
     */
    private function detectDataTypes(array $sample): array
    {
        $types = [];
        
        foreach ($sample as $row) {
            if (is_array($row)) {
                foreach ($row as $key => $value) {
                    if (!isset($types[$key])) {
                        $types[$key] = [];
                    }
                    
                    $type = gettype($value);
                    if (!in_array($type, $types[$key])) {
                        $types[$key][] = $type;
                    }
                }
            }
        }
        
        return $types;
    }
    
    /**
     * Pre-compile CSS classes for performance
     *
     * @param array $config The configuration
     * @return array Compiled CSS classes
     */
    private function preCompileCssClasses(array $config): array
    {
        $styling = $config['styling'] ?? [];
        $layout = $config['layout'] ?? [];
        
        return [
            'table' => trim(($styling['table_class'] ?? 'table table-bordered') . ' performance-optimized'),
            'header' => $styling['header_class'] ?? 'bg-primary text-white',
            'empty' => $styling['empty_value_class'] ?? 'text-muted',
            'group' => $styling['group_class'] ?? 'table-group',
            'responsive_wrapper' => 'table-responsive table-responsive-' . ($layout['responsive_breakpoint'] ?? 'lg'),
            'container' => 'universal-table-wrapper performance-optimized'
        ];
    }
    
    /**
     * Pre-calculate responsive settings
     *
     * @param array $config The configuration
     * @return array Compiled responsive settings
     */
    private function preCalculateResponsive(array $config): array
    {
        $globalSettings = $config['global_settings'] ?? [];
        $responsive = $globalSettings['responsive'] ?? [];
        $layout = $config['layout'] ?? [];
        
        $breakpoint = $layout['responsive_breakpoint'] ?? 'lg';
        $breakpoints = $responsive['breakpoints'] ?? [
            'lg' => '992px',
            'md' => '768px',
            'sm' => '576px'
        ];
        
        return [
            'enabled' => $responsive['enabled'] ?? true,
            'pc_only' => $responsive['pc_only'] ?? true,
            'breakpoint' => $breakpoint,
            'breakpoint_value' => $breakpoints[$breakpoint] ?? '992px',
            'show_scroll_indicator' => true
        ];
    }

    /**
     * Add a column to table configuration
     *
     * @param string $tableType The table type
     * @param array $columnConfig The column configuration to add
     * @param int|null $position The position to insert at (null for end)
     * @return bool True if successful
     */
    public function addColumn(string $tableType, array $columnConfig, ?int $position = null): bool
    {
        try {
            $config = $this->getTableConfig($tableType);
            
            // Validate the new column
            $this->validateColumnConfig($columnConfig);
            
            if ($position === null) {
                $config['columns'][] = $columnConfig;
            } else {
                array_splice($config['columns'], $position, 0, [$columnConfig]);
            }
            
            // Update the configuration (this would need to be implemented based on storage method)
            return $this->updateTableConfig($tableType, $config);
            
        } catch (\Exception $e) {
            Log::error("Failed to add column to table {$tableType}", [
                'error' => $e->getMessage(),
                'column' => $columnConfig
            ]);
            return false;
        }
    }

    /**
     * Remove a column from table configuration
     *
     * @param string $tableType The table type
     * @param string $columnKey The key of the column to remove
     * @return bool True if successful
     */
    public function removeColumn(string $tableType, string $columnKey): bool
    {
        try {
            $config = $this->getTableConfig($tableType);
            
            $config['columns'] = array_filter($config['columns'], function($column) use ($columnKey) {
                return $column['key'] !== $columnKey;
            });
            
            // Re-index array
            $config['columns'] = array_values($config['columns']);
            
            return $this->updateTableConfig($tableType, $config);
            
        } catch (\Exception $e) {
            Log::error("Failed to remove column from table {$tableType}", [
                'error' => $e->getMessage(),
                'column_key' => $columnKey
            ]);
            return false;
        }
    }

    /**
     * Update column configuration
     *
     * @param string $tableType The table type
     * @param string $columnKey The key of the column to update
     * @param array $updates The updates to apply
     * @return bool True if successful
     */
    public function updateColumn(string $tableType, string $columnKey, array $updates): bool
    {
        try {
            $config = $this->getTableConfig($tableType);
            
            foreach ($config['columns'] as &$column) {
                if ($column['key'] === $columnKey) {
                    $column = array_merge($column, $updates);
                    
                    // Validate updated column
                    $this->validateColumnConfig($column);
                    break;
                }
            }
            
            return $this->updateTableConfig($tableType, $config);
            
        } catch (\Exception $e) {
            Log::error("Failed to update column in table {$tableType}", [
                'error' => $e->getMessage(),
                'column_key' => $columnKey,
                'updates' => $updates
            ]);
            return false;
        }
    }

    /**
     * Reorder columns in table configuration
     *
     * @param string $tableType The table type
     * @param array $columnOrder Array of column keys in desired order
     * @return bool True if successful
     */
    public function reorderColumns(string $tableType, array $columnOrder): bool
    {
        try {
            $config = $this->getTableConfig($tableType);
            $currentColumns = $config['columns'];
            $reorderedColumns = [];
            
            // Build reordered array
            foreach ($columnOrder as $key) {
                $column = collect($currentColumns)->firstWhere('key', $key);
                if ($column) {
                    $reorderedColumns[] = $column;
                }
            }
            
            // Add any columns not in the order array
            foreach ($currentColumns as $column) {
                if (!in_array($column['key'], $columnOrder)) {
                    $reorderedColumns[] = $column;
                }
            }
            
            $config['columns'] = $reorderedColumns;
            
            return $this->updateTableConfig($tableType, $config);
            
        } catch (\Exception $e) {
            Log::error("Failed to reorder columns in table {$tableType}", [
                'error' => $e->getMessage(),
                'column_order' => $columnOrder
            ]);
            return false;
        }
    }

    /**
     * Validate a single column configuration
     *
     * @param array $column The column configuration to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateColumnConfig(array $column): void
    {
        $requiredFields = ['key', 'label', 'type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($column[$field]) || empty($column[$field])) {
                throw new InvalidArgumentException("Column missing required field: {$field}");
            }
        }
        
        $validTypes = ['text', 'email', 'url', 'number', 'date', 'date_range', 'select', 'phone'];
        if (!in_array($column['type'], $validTypes)) {
            throw new InvalidArgumentException("Invalid column type: {$column['type']}");
        }
        
        if ($column['type'] === 'select' && (!isset($column['options']) || !is_array($column['options']))) {
            throw new InvalidArgumentException("Select column must have options array");
        }
    }

    /**
     * Update table configuration (placeholder for actual implementation)
     *
     * @param string $tableType The table type
     * @param array $config The updated configuration
     * @return bool True if successful
     */
    private function updateTableConfig(string $tableType, array $config): bool
    {
        // This would need to be implemented based on how configurations are stored
        // For now, we'll just clear the cache to force reload
        $this->clearCache($tableType);
        
        // In a real implementation, this would save to database or config file
        Log::info("Table configuration updated for {$tableType}", ['config' => $config]);
        
        return true;
    }
}