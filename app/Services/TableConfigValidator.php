<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Validator for table configurations
 * 
 * This service validates table configurations to ensure they meet
 * the required structure and contain valid data.
 */
class TableConfigValidator
{
    /**
     * Valid column types
     */
    private const VALID_COLUMN_TYPES = [
        'text', 'email', 'url', 'number', 'date', 'date_range', 'select', 'phone'
    ];

    /**
     * Valid layout types
     */
    private const VALID_LAYOUT_TYPES = [
        'key_value_pairs', 'standard_table', 'grouped_rows', 'service_table'
    ];

    /**
     * Valid responsive breakpoints
     */
    private const VALID_BREAKPOINTS = ['xs', 'sm', 'md', 'lg', 'xl'];

    /**
     * Required column fields
     */
    private const REQUIRED_COLUMN_FIELDS = ['key', 'label', 'type'];

    /**
     * Validate a complete table configuration
     *
     * @param array $config The configuration to validate
     * @return ValidationResult The validation result
     */
    public function validate(array $config): ValidationResult
    {
        $errors = [];

        try {
            // Validate basic structure
            $errors = array_merge($errors, $this->validateBasicStructure($config));
            
            // Validate columns
            $errors = array_merge($errors, $this->validateColumns($config));
            
            // Validate layout
            $errors = array_merge($errors, $this->validateLayout($config));
            
            // Validate styling
            $errors = array_merge($errors, $this->validateStyling($config));
            
            // Validate features
            $errors = array_merge($errors, $this->validateFeatures($config));
            
            // Validate responsive settings
            $responsiveResult = $this->validateResponsiveSettings($config);
            if (!$responsiveResult->isValid()) {
                $errors = array_merge($errors, $responsiveResult->getErrors());
            }
            
        } catch (\Exception $e) {
            $errors[] = 'Validation failed with exception: ' . $e->getMessage();
            Log::error('Table configuration validation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Validate required fields in configuration
     *
     * @param array $config The configuration to validate
     * @return ValidationResult The validation result
     */
    public function validateRequiredFields(array $config): ValidationResult
    {
        $errors = [];
        $requiredFields = Config::get('table-config.global_settings.validation.required_fields', self::REQUIRED_COLUMN_FIELDS);

        if (!isset($config['columns']) || !is_array($config['columns']) || empty($config['columns'])) {
            $errors[] = 'Configuration must have at least one column defined';
            return new ValidationResult(false, $errors);
        }

        foreach ($config['columns'] as $index => $column) {
            if (!is_array($column)) {
                $errors[] = "Column at index {$index} must be an array";
                continue;
            }

            foreach ($requiredFields as $field) {
                if (!isset($column[$field]) || (is_string($column[$field]) && trim($column[$field]) === '')) {
                    $errors[] = "Column at index {$index} missing required field: {$field}";
                }
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Validate column structure and types
     *
     * @param array $config The configuration to validate
     * @return ValidationResult The validation result
     */
    public function validateColumnStructure(array $config): ValidationResult
    {
        $errors = [];

        if (!isset($config['columns'])) {
            return new ValidationResult(true, []);
        }

        foreach ($config['columns'] as $index => $column) {
            if (!is_array($column)) {
                $errors[] = "Column at index {$index} must be an array";
                continue;
            }

            // Validate column type
            if (isset($column['type']) && !in_array($column['type'], self::VALID_COLUMN_TYPES)) {
                $errors[] = "Invalid column type '{$column['type']}' at index {$index}. Valid types: " . implode(', ', self::VALID_COLUMN_TYPES);
            }

            // Validate select column options
            if (isset($column['type']) && $column['type'] === 'select') {
                if (!isset($column['options']) || !is_array($column['options']) || empty($column['options'])) {
                    $errors[] = "Select column at index {$index} must have a non-empty options array";
                }
            }

            // Validate width format
            if (isset($column['width'])) {
                if (!$this->isValidWidthFormat($column['width'])) {
                    $errors[] = "Invalid width format '{$column['width']}' at column index {$index}. Use percentage (e.g., '25%') or pixels (e.g., '200px')";
                }
            }

            // Validate numeric fields
            if (isset($column['colspan']) && (!is_int($column['colspan']) || $column['colspan'] < 1)) {
                $errors[] = "Column colspan at index {$index} must be a positive integer";
            }

            if (isset($column['decimals']) && (!is_int($column['decimals']) || $column['decimals'] < 0)) {
                $errors[] = "Column decimals at index {$index} must be a non-negative integer";
            }

            // Validate boolean fields
            $booleanFields = ['required', 'rowspan_group'];
            foreach ($booleanFields as $field) {
                if (isset($column[$field]) && !is_bool($column[$field])) {
                    $errors[] = "Column {$field} at index {$index} must be a boolean value";
                }
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Validate responsive settings
     *
     * @param array $config The configuration to validate
     * @return ValidationResult The validation result
     */
    public function validateResponsiveSettings(array $config): ValidationResult
    {
        $errors = [];

        if (!isset($config['layout'])) {
            return new ValidationResult(true, []);
        }

        $layout = $config['layout'];

        // Validate responsive breakpoint
        if (isset($layout['responsive_breakpoint'])) {
            if (!in_array($layout['responsive_breakpoint'], self::VALID_BREAKPOINTS)) {
                $errors[] = "Invalid responsive breakpoint '{$layout['responsive_breakpoint']}'. Valid breakpoints: " . implode(', ', self::VALID_BREAKPOINTS);
            }
        }

        // Validate columns per row
        if (isset($layout['columns_per_row'])) {
            if (!is_int($layout['columns_per_row']) || $layout['columns_per_row'] < 1 || $layout['columns_per_row'] > 4) {
                $errors[] = "columns_per_row must be an integer between 1 and 4";
            }
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Create comprehensive error messages for configuration issues
     *
     * @param array $config The configuration that failed validation
     * @param ValidationResult $validationResult The validation result
     * @return array Array of detailed error messages
     */
    public function createDetailedErrorMessages(array $config, ValidationResult $validationResult): array
    {
        $detailedErrors = [];
        $errors = $validationResult->getErrors();

        foreach ($errors as $error) {
            $detailedError = [
                'message' => $error,
                'severity' => $this->getErrorSeverity($error),
                'suggestion' => $this->getErrorSuggestion($error),
                'context' => $this->getErrorContext($error, $config)
            ];

            $detailedErrors[] = $detailedError;
        }

        return $detailedErrors;
    }

    /**
     * Validate basic configuration structure
     *
     * @param array $config The configuration to validate
     * @return array Array of validation errors
     */
    private function validateBasicStructure(array $config): array
    {
        $errors = [];

        // Check if columns exist and is array
        if (!isset($config['columns'])) {
            $errors[] = 'Configuration must have a "columns" section';
        } elseif (!is_array($config['columns'])) {
            $errors[] = 'Configuration "columns" must be an array';
        } elseif (empty($config['columns'])) {
            $errors[] = 'Configuration must have at least one column defined';
        }

        return $errors;
    }

    /**
     * Validate columns configuration
     *
     * @param array $config The configuration to validate
     * @return array Array of validation errors
     */
    private function validateColumns(array $config): array
    {
        $errors = [];

        if (!isset($config['columns']) || !is_array($config['columns'])) {
            return $errors; // Already handled in basic structure validation
        }

        $columnKeys = [];
        foreach ($config['columns'] as $index => $column) {
            if (!is_array($column)) {
                $errors[] = "Column at index {$index} must be an array";
                continue;
            }

            // Check for required fields
            foreach (self::REQUIRED_COLUMN_FIELDS as $field) {
                if (!isset($column[$field]) || (is_string($column[$field]) && trim($column[$field]) === '')) {
                    $errors[] = "Column at index {$index} missing required field: {$field}";
                }
            }

            // Check for duplicate keys
            if (isset($column['key'])) {
                if (in_array($column['key'], $columnKeys)) {
                    $errors[] = "Duplicate column key '{$column['key']}' found at index {$index}";
                }
                $columnKeys[] = $column['key'];
            }

            // Validate column type
            if (isset($column['type']) && !in_array($column['type'], self::VALID_COLUMN_TYPES)) {
                $errors[] = "Invalid column type '{$column['type']}' at index {$index}";
            }

            // Type-specific validation
            $errors = array_merge($errors, $this->validateColumnByType($column, $index));
            
            // Validate boolean fields in columns
            $booleanFields = ['required', 'rowspan_group'];
            foreach ($booleanFields as $field) {
                if (isset($column[$field]) && !is_bool($column[$field])) {
                    $errors[] = "Column {$field} at index {$index} must be a boolean value";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate column based on its type
     *
     * @param array $column The column configuration
     * @param int $index The column index
     * @return array Array of validation errors
     */
    private function validateColumnByType(array $column, int $index): array
    {
        $errors = [];

        if (!isset($column['type'])) {
            return $errors;
        }

        switch ($column['type']) {
            case 'select':
                if (!isset($column['options']) || !is_array($column['options']) || empty($column['options'])) {
                    $errors[] = "Select column at index {$index} must have a non-empty options array";
                }
                break;

            case 'number':
                if (isset($column['decimals']) && (!is_int($column['decimals']) || $column['decimals'] < 0)) {
                    $errors[] = "Number column decimals at index {$index} must be a non-negative integer";
                }
                break;

            case 'date':
            case 'date_range':
                if (isset($column['format']) && !$this->isValidDateFormat($column['format'])) {
                    $errors[] = "Invalid date format '{$column['format']}' at column index {$index}";
                }
                break;

            case 'email':
                // Email columns don't need additional validation
                break;

            case 'url':
                // URL columns don't need additional validation
                break;

            case 'phone':
                // Phone columns don't need additional validation
                break;

            case 'text':
            default:
                // Text columns don't need additional validation
                break;
        }

        return $errors;
    }

    /**
     * Validate layout configuration
     *
     * @param array $config The configuration to validate
     * @return array Array of validation errors
     */
    private function validateLayout(array $config): array
    {
        $errors = [];

        if (!isset($config['layout'])) {
            return $errors; // Layout is optional
        }

        $layout = $config['layout'];

        if (!is_array($layout)) {
            $errors[] = 'Layout configuration must be an array';
            return $errors;
        }

        // Validate layout type
        if (isset($layout['type']) && !in_array($layout['type'], self::VALID_LAYOUT_TYPES)) {
            $errors[] = "Invalid layout type '{$layout['type']}'. Valid types: " . implode(', ', self::VALID_LAYOUT_TYPES);
        }

        // Validate boolean fields
        $booleanFields = ['show_headers', 'hierarchical_headers', 'service_header_rowspan'];
        foreach ($booleanFields as $field) {
            if (isset($layout[$field]) && !is_bool($layout[$field])) {
                $errors[] = "Layout {$field} must be a boolean value";
            }
        }

        // Validate numeric fields
        if (isset($layout['columns_per_row'])) {
            if (!is_int($layout['columns_per_row']) || $layout['columns_per_row'] < 1 || $layout['columns_per_row'] > 4) {
                $errors[] = "Layout columns_per_row must be an integer between 1 and 4";
            }
        }

        // Validate responsive breakpoint
        if (isset($layout['responsive_breakpoint']) && !in_array($layout['responsive_breakpoint'], self::VALID_BREAKPOINTS)) {
            $errors[] = "Invalid responsive breakpoint '{$layout['responsive_breakpoint']}'";
        }

        return $errors;
    }

    /**
     * Validate styling configuration
     *
     * @param array $config The configuration to validate
     * @return array Array of validation errors
     */
    private function validateStyling(array $config): array
    {
        $errors = [];

        if (!isset($config['styling'])) {
            return $errors; // Styling is optional
        }

        $styling = $config['styling'];

        if (!is_array($styling)) {
            $errors[] = 'Styling configuration must be an array';
            return $errors;
        }

        // Validate CSS class fields
        $classFields = ['table_class', 'header_class', 'empty_value_class', 'group_class', 'rowspan_class'];
        foreach ($classFields as $field) {
            if (isset($styling[$field]) && !is_string($styling[$field])) {
                $errors[] = "Styling {$field} must be a string";
            }
        }

        return $errors;
    }

    /**
     * Validate features configuration
     *
     * @param array $config The configuration to validate
     * @return array Array of validation errors
     */
    private function validateFeatures(array $config): array
    {
        $errors = [];

        if (!isset($config['features'])) {
            return $errors; // Features is optional
        }

        $features = $config['features'];

        if (!is_array($features)) {
            $errors[] = 'Features configuration must be an array';
            return $errors;
        }

        // Validate boolean feature flags
        $booleanFeatures = ['comments', 'sorting', 'filtering', 'advanced_rowspan', 'dynamic_columns', 'auto_width'];
        foreach ($booleanFeatures as $feature) {
            if (isset($features[$feature]) && !is_bool($features[$feature])) {
                $errors[] = "Feature {$feature} must be a boolean value";
            }
        }

        return $errors;
    }

    /**
     * Check if width format is valid
     *
     * @param string $width The width value to validate
     * @return bool True if valid
     */
    private function isValidWidthFormat(string $width): bool
    {
        // Allow percentage (e.g., "25%") or pixels (e.g., "200px") or auto
        return preg_match('/^(\d+(\.\d+)?(%|px)|auto)$/', $width) === 1;
    }

    /**
     * Check if date format is valid
     *
     * @param string $format The date format to validate
     * @return bool True if valid
     */
    private function isValidDateFormat(string $format): bool
    {
        // Allow common PHP date format characters
        return preg_match('/^[YymdHisaAjnFMlDNwzWtLoSuveITZcrU\s\-\/\.\:年月日時分秒〜]+$/', $format) === 1;
    }

    /**
     * Get error severity level
     *
     * @param string $error The error message
     * @return string The severity level
     */
    private function getErrorSeverity(string $error): string
    {
        if (strpos($error, 'missing required field') !== false) {
            return 'critical';
        }
        
        if (strpos($error, 'must have at least one column') !== false) {
            return 'critical';
        }
        
        if (strpos($error, 'Invalid') !== false) {
            return 'error';
        }
        
        return 'warning';
    }

    /**
     * Get error suggestion
     *
     * @param string $error The error message
     * @return string The suggestion
     */
    private function getErrorSuggestion(string $error): string
    {
        if (strpos($error, 'missing required field: key') !== false) {
            return 'Add a unique "key" field to identify this column';
        }
        
        if (strpos($error, 'missing required field: label') !== false) {
            return 'Add a "label" field with the display name for this column';
        }
        
        if (strpos($error, 'missing required field: type') !== false) {
            return 'Add a "type" field with one of: ' . implode(', ', self::VALID_COLUMN_TYPES);
        }
        
        if (strpos($error, 'Invalid column type') !== false) {
            return 'Use one of the valid column types: ' . implode(', ', self::VALID_COLUMN_TYPES);
        }
        
        if (strpos($error, 'Select column') !== false && strpos($error, 'options') !== false) {
            return 'Add an "options" array with key-value pairs for select options';
        }
        
        return 'Please check the configuration documentation for correct format';
    }

    /**
     * Get error context
     *
     * @param string $error The error message
     * @param array $config The configuration
     * @return array The context information
     */
    private function getErrorContext(string $error, array $config): array
    {
        $context = [];
        
        // Extract column index if present
        if (preg_match('/index (\d+)/', $error, $matches)) {
            $index = (int)$matches[1];
            if (isset($config['columns'][$index])) {
                $context['column'] = $config['columns'][$index];
                $context['column_index'] = $index;
            }
        }
        
        // Add table type if available
        if (isset($config['comment_display_name'])) {
            $context['table_display_name'] = $config['comment_display_name'];
        }
        
        return $context;
    }
}