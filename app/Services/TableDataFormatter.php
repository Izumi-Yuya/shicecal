<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Table Data Formatter Service
 * 
 * Handles data formatting for universal table components based on column types.
 * Supports text, date_range, number, select field types and empty value handling.
 */
class TableDataFormatter
{
    /**
     * Format table data based on column configuration
     *
     * @param array|Collection $data
     * @param array $config
     * @return array
     */
    public function formatTableData($data, array $config): array
    {
        $columns = $config['columns'] ?? [];
        
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        
        if (empty($data)) {
            return [];
        }
        
        // Handle single row vs multiple rows
        if (isset($data[0]) && is_array($data[0])) {
            // Multiple rows
            return array_map(function ($row) use ($columns) {
                return $this->formatRow($row, $columns);
            }, $data);
        } else {
            // Single row
            return $this->formatRow($data, $columns);
        }
    }

    /**
     * Format a single row of data
     *
     * @param array $row
     * @param array $columns
     * @return array
     */
    protected function formatRow(array $row, array $columns): array
    {
        $formattedRow = [];
        
        foreach ($columns as $column) {
            $key = $column['key'];
            $value = $row[$key] ?? null;
            
            $formattedRow[$key] = $this->formatValue($value, $column);
        }
        
        return $formattedRow;
    }

    /**
     * Format a single value based on column configuration
     *
     * @param mixed $value
     * @param array $column
     * @return string|null
     */
    public function formatValue($value, array $column): ?string
    {
        $type = $column['type'] ?? 'text';
        
        // Handle static values
        if (isset($column['static_value'])) {
            return $column['static_value'];
        }
        
        // Handle null or empty values
        if ($value === null || $value === '') {
            return null;
        }
        
        switch ($type) {
            case 'text':
                return $this->formatText($value, $column);
                
            case 'email':
                return $this->formatEmail($value, $column);
                
            case 'url':
                return $this->formatUrl($value, $column);
                
            case 'phone':
                return $this->formatPhone($value, $column);
                
            case 'date':
                return $this->formatDate($value, $column);
                
            case 'date_range':
                return $this->formatDateRange($value, $column);
                
            case 'number':
                return $this->formatNumber($value, $column);
                
            case 'select':
                return $this->formatSelect($value, $column);
                
            default:
                return $this->formatText($value, $column);
        }
    }

    /**
     * Format text value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatText($value, array $column): string
    {
        $text = (string) $value;
        
        // Apply any text transformations
        if (isset($column['transform'])) {
            switch ($column['transform']) {
                case 'uppercase':
                    $text = strtoupper($text);
                    break;
                case 'lowercase':
                    $text = strtolower($text);
                    break;
                case 'capitalize':
                    $text = ucfirst($text);
                    break;
            }
        }
        
        // Apply special formatting
        if (isset($column['special_formatting'])) {
            switch ($column['special_formatting']) {
                case 'bold':
                    $text = '<span class="fw-bold">' . $text . '</span>';
                    break;
            }
        }
        
        // Add prefix/suffix if configured
        $prefix = $column['prefix'] ?? '';
        $suffix = $column['suffix'] ?? '';
        
        return $prefix . $text . $suffix;
    }

    /**
     * Format email value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatEmail($value, array $column): string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? (string) $value : (string) $value;
    }

    /**
     * Format URL value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatUrl($value, array $column): string
    {
        $url = (string) $value;
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        return $url;
    }

    /**
     * Format phone number value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatPhone($value, array $column): string
    {
        $phone = (string) $value;
        
        // Apply phone formatting if configured
        if (isset($column['format']) && $column['format'] === 'japanese') {
            // Format Japanese phone numbers (e.g., 03-1234-5678)
            $phone = preg_replace('/(\d{2,4})(\d{4})(\d{4})/', '$1-$2-$3', preg_replace('/[^\d]/', '', $phone));
        }
        
        return $phone;
    }

    /**
     * Format date value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatDate($value, array $column): string
    {
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse($value);
            $format = $column['format'] ?? 'Y年m月d日';
            
            return $date->format($format);
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * Format date range value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatDateRange($value, array $column): string
    {
        $separator = $column['separator'] ?? '〜';
        $format = $column['format'] ?? 'Y年m月d日';
        
        if (is_array($value) && count($value) >= 2) {
            // Array with start and end dates
            try {
                $startDate = $value[0] instanceof Carbon ? $value[0] : Carbon::parse($value[0]);
                $endDate = $value[1] instanceof Carbon ? $value[1] : Carbon::parse($value[1]);
                
                return $startDate->format($format) . $separator . $endDate->format($format);
            } catch (\Exception $e) {
                return implode($separator, $value);
            }
        } elseif (is_string($value) && strpos($value, $separator) !== false) {
            // String with separator
            $dates = explode($separator, $value);
            if (count($dates) >= 2) {
                try {
                    $startDate = Carbon::parse(trim($dates[0]));
                    $endDate = Carbon::parse(trim($dates[1]));
                    
                    return $startDate->format($format) . $separator . $endDate->format($format);
                } catch (\Exception $e) {
                    return $value;
                }
            }
        }
        
        return (string) $value;
    }

    /**
     * Format number value
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatNumber($value, array $column): string
    {
        $number = is_numeric($value) ? (float) $value : 0;
        
        // Apply number formatting
        $decimals = $column['decimals'] ?? 0;
        $decimalSeparator = $column['decimal_separator'] ?? '.';
        $thousandsSeparator = $column['thousands_separator'] ?? ',';
        
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
        
        // Add unit if configured
        $unit = $column['unit'] ?? '';
        if ($unit) {
            $formatted .= $unit;
        }
        
        return $formatted;
    }

    /**
     * Format select value using options mapping
     *
     * @param mixed $value
     * @param array $column
     * @return string
     */
    protected function formatSelect($value, array $column): string
    {
        $options = $column['options'] ?? [];
        
        if (isset($options[$value])) {
            return $options[$value];
        }
        
        return (string) $value;
    }

    /**
     * Format empty value with configurable display text
     *
     * @param string $fieldName
     * @param array $column
     * @return string
     */
    public function formatEmptyValue(string $fieldName, array $column = []): string
    {
        return $column['empty_text'] ?? '未設定';
    }

    /**
     * Group data by specified field
     *
     * @param array $data
     * @param string $groupBy
     * @return array
     */
    public function groupDataBy(array $data, string $groupBy): array
    {
        if (empty($data) || !$groupBy) {
            return $data;
        }
        
        $grouped = [];
        
        foreach ($data as $item) {
            $groupValue = $item[$groupBy] ?? 'その他';
            
            if (!isset($grouped[$groupValue])) {
                $grouped[$groupValue] = [];
            }
            
            $grouped[$groupValue][] = $item;
        }
        
        return $grouped;
    }

    /**
     * Format complex columns with merged cells and hierarchical headers
     *
     * @param array $data
     * @param array $columnConfig
     * @return array
     */
    public function formatComplexColumns(array $data, array $columnConfig): array
    {
        // Handle rowspan grouping
        if (isset($columnConfig['rowspan_group']) && $columnConfig['rowspan_group']) {
            return $this->processRowspanGrouping($data, $columnConfig);
        }
        
        // Handle nested data structures
        if (isset($columnConfig['nested']) && $columnConfig['nested']) {
            return $this->processNestedData($data, $columnConfig);
        }
        
        return $data;
    }

    /**
     * Process rowspan grouping for complex table structures
     *
     * @param array $data
     * @param array $columnConfig
     * @return array
     */
    protected function processRowspanGrouping(array $data, array $columnConfig): array
    {
        $groupBy = $columnConfig['group_by'] ?? null;
        
        if (!$groupBy) {
            return $data;
        }
        
        $grouped = $this->groupDataBy($data, $groupBy);
        $processed = [];
        
        foreach ($grouped as $groupValue => $items) {
            foreach ($items as $index => $item) {
                $item['_rowspan'] = $index === 0 ? count($items) : 0;
                $item['_group_value'] = $groupValue;
                $processed[] = $item;
            }
        }
        
        return $processed;
    }

    /**
     * Calculate rowspan values for grouped data
     *
     * @param array $data
     * @param array $columns
     * @return array
     */
    public function calculateRowspanValues(array $data, array $columns): array
    {
        if (empty($data)) {
            return $data;
        }

        // Find columns that need rowspan grouping
        $rowspanColumns = array_filter($columns, function($column) {
            return isset($column['rowspan_group']) && $column['rowspan_group'] === true;
        });

        if (empty($rowspanColumns)) {
            return $data;
        }

        $processed = [];
        $groupTracker = [];

        foreach ($data as $rowIndex => $row) {
            $processedRow = $row;
            
            foreach ($rowspanColumns as $column) {
                $key = $column['key'];
                $value = $row[$key] ?? null;
                
                // Initialize group tracker for this column
                if (!isset($groupTracker[$key])) {
                    $groupTracker[$key] = [
                        'current_value' => null,
                        'start_index' => 0,
                        'count' => 0
                    ];
                }
                
                // Check if this is a new group
                if ($groupTracker[$key]['current_value'] !== $value) {
                    // Finalize previous group
                    if ($groupTracker[$key]['count'] > 0) {
                        $this->applyRowspanToGroup($processed, $key, 
                            $groupTracker[$key]['start_index'], 
                            $groupTracker[$key]['count']);
                    }
                    
                    // Start new group
                    $groupTracker[$key] = [
                        'current_value' => $value,
                        'start_index' => $rowIndex,
                        'count' => 1
                    ];
                } else {
                    // Continue current group
                    $groupTracker[$key]['count']++;
                }
                
                // Mark rowspan info in the row
                $processedRow['_rowspan_' . $key] = [
                    'value' => $value,
                    'is_first' => $groupTracker[$key]['count'] === 1,
                    'group_size' => 0 // Will be set later
                ];
            }
            
            $processed[] = $processedRow;
        }
        
        // Finalize remaining groups
        foreach ($rowspanColumns as $column) {
            $key = $column['key'];
            if (isset($groupTracker[$key]) && $groupTracker[$key]['count'] > 0) {
                $this->applyRowspanToGroup($processed, $key, 
                    $groupTracker[$key]['start_index'], 
                    $groupTracker[$key]['count']);
            }
        }
        
        return $processed;
    }

    /**
     * Apply rowspan values to a group of rows
     *
     * @param array &$data
     * @param string $columnKey
     * @param int $startIndex
     * @param int $groupSize
     * @return void
     */
    protected function applyRowspanToGroup(array &$data, string $columnKey, int $startIndex, int $groupSize): void
    {
        for ($i = $startIndex; $i < $startIndex + $groupSize && $i < count($data); $i++) {
            if (isset($data[$i]['_rowspan_' . $columnKey])) {
                $data[$i]['_rowspan_' . $columnKey]['group_size'] = $groupSize;
            }
        }
    }

    /**
     * Generate hierarchical header structure for complex tables
     *
     * @param array $columns
     * @return array
     */
    public function generateHierarchicalHeaders(array $columns): array
    {
        $headers = [];
        $levels = [];
        
        foreach ($columns as $column) {
            $level = $column['header_level'] ?? 1;
            $group = $column['header_group'] ?? null;
            
            if (!isset($levels[$level])) {
                $levels[$level] = [];
            }
            
            if ($group) {
                if (!isset($levels[$level][$group])) {
                    $levels[$level][$group] = [
                        'label' => $column['header_group_label'] ?? $group,
                        'columns' => [],
                        'colspan' => 0
                    ];
                }
                $levels[$level][$group]['columns'][] = $column;
                $levels[$level][$group]['colspan']++;
            } else {
                $levels[$level][] = [
                    'label' => $column['label'],
                    'key' => $column['key'],
                    'colspan' => 1,
                    'rowspan' => $this->calculateHeaderRowspan($column, $levels)
                ];
            }
        }
        
        // Sort levels and convert to indexed array
        ksort($levels);
        foreach ($levels as $levelIndex => $levelData) {
            $headers[$levelIndex] = array_values($levelData);
        }
        
        return $headers;
    }

    /**
     * Calculate rowspan for header cells in hierarchical structures
     *
     * @param array $column
     * @param array $levels
     * @return int
     */
    protected function calculateHeaderRowspan(array $column, array $levels): int
    {
        $currentLevel = $column['header_level'] ?? 1;
        $maxLevel = max(array_keys($levels));
        
        return $maxLevel - $currentLevel + 1;
    }

    /**
     * Process multi-level rowspan grouping
     *
     * @param array $data
     * @param array $groupingConfig
     * @return array
     */
    public function processMultiLevelRowspan(array $data, array $groupingConfig): array
    {
        if (empty($data) || empty($groupingConfig)) {
            return $data;
        }
        
        // Sort grouping config by priority (lower number = higher priority)
        usort($groupingConfig, function($a, $b) {
            return ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0);
        });
        
        $processed = $data;
        
        foreach ($groupingConfig as $config) {
            $groupBy = $config['group_by'];
            $processed = $this->applyGroupingLevel($processed, $groupBy, $config);
        }
        
        return $processed;
    }

    /**
     * Apply a single level of grouping
     *
     * @param array $data
     * @param string $groupBy
     * @param array $config
     * @return array
     */
    protected function applyGroupingLevel(array $data, string $groupBy, array $config): array
    {
        $grouped = [];
        $currentGroup = null;
        $groupCount = 0;
        
        foreach ($data as $index => $row) {
            $groupValue = $row[$groupBy] ?? null;
            
            if ($currentGroup !== $groupValue) {
                // New group started
                if ($currentGroup !== null && $groupCount > 0) {
                    // Apply rowspan to previous group
                    $this->applyRowspanToRange($grouped, $groupBy, 
                        count($grouped) - $groupCount, $groupCount);
                }
                
                $currentGroup = $groupValue;
                $groupCount = 1;
            } else {
                $groupCount++;
            }
            
            // Add rowspan metadata
            $row['_group_' . $groupBy] = [
                'value' => $groupValue,
                'is_first' => $groupCount === 1,
                'group_size' => 0 // Will be set when group is finalized
            ];
            
            $grouped[] = $row;
        }
        
        // Apply rowspan to the last group
        if ($currentGroup !== null && $groupCount > 0) {
            $this->applyRowspanToRange($grouped, $groupBy, 
                count($grouped) - $groupCount, $groupCount);
        }
        
        return $grouped;
    }

    /**
     * Apply rowspan to a range of rows
     *
     * @param array &$data
     * @param string $groupBy
     * @param int $startIndex
     * @param int $count
     * @return void
     */
    protected function applyRowspanToRange(array &$data, string $groupBy, int $startIndex, int $count): void
    {
        for ($i = $startIndex; $i < $startIndex + $count && $i < count($data); $i++) {
            if (isset($data[$i]['_group_' . $groupBy])) {
                $data[$i]['_group_' . $groupBy]['group_size'] = $count;
            }
        }
    }

    /**
     * Filter columns based on data content and conditions
     *
     * @param array $columns
     * @param array $data
     * @return array
     */
    public function filterConditionalColumns(array $columns, array $data): array
    {
        if (empty($data)) {
            return $columns;
        }

        $filteredColumns = [];

        foreach ($columns as $column) {
            if ($this->shouldShowColumn($column, $data)) {
                $filteredColumns[] = $column;
            }
        }

        return $filteredColumns;
    }

    /**
     * Determine if a column should be shown based on conditions
     *
     * @param array $column
     * @param array $data
     * @return bool
     */
    protected function shouldShowColumn(array $column, array $data): bool
    {
        // Always show if no conditions are set
        if (!isset($column['show_condition'])) {
            return true;
        }

        $condition = $column['show_condition'];

        // Handle different condition types
        switch ($condition['type'] ?? 'always') {
            case 'has_data':
                return $this->columnHasData($column['key'], $data);

            case 'data_equals':
                return $this->columnDataEquals($column['key'], $condition['value'] ?? null, $data);

            case 'data_not_empty':
                return $this->columnDataNotEmpty($column['key'], $data);

            case 'field_exists':
                return $this->fieldExists($condition['field'] ?? $column['key'], $data);

            case 'custom_function':
                return $this->evaluateCustomCondition($condition['function'] ?? null, $column, $data);

            case 'never':
                return false;

            case 'always':
            default:
                return true;
        }
    }

    /**
     * Check if column has any non-empty data
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    protected function columnHasData(string $key, array $data): bool
    {
        foreach ($data as $row) {
            $value = $row[$key] ?? null;
            if ($value !== null && $value !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if column data equals specific value
     *
     * @param string $key
     * @param mixed $expectedValue
     * @param array $data
     * @return bool
     */
    protected function columnDataEquals(string $key, $expectedValue, array $data): bool
    {
        // Look for the key in any field of the data
        foreach ($data as $row) {
            foreach ($row as $fieldKey => $value) {
                if ($value === $expectedValue) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if column has any non-empty data
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    protected function columnDataNotEmpty(string $key, array $data): bool
    {
        return $this->columnHasData($key, $data);
    }

    /**
     * Check if field exists in data
     *
     * @param string $field
     * @param array $data
     * @return bool
     */
    protected function fieldExists(string $field, array $data): bool
    {
        foreach ($data as $row) {
            if (array_key_exists($field, $row)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluate custom condition function
     *
     * @param string|null $function
     * @param array $column
     * @param array $data
     * @return bool
     */
    protected function evaluateCustomCondition(?string $function, array $column, array $data): bool
    {
        if (!$function || !method_exists($this, $function)) {
            return true;
        }

        return $this->$function($column, $data);
    }

    /**
     * Add dynamic columns based on data content
     *
     * @param array $columns
     * @param array $data
     * @return array
     */
    public function addDynamicColumns(array $columns, array $data): array
    {
        if (empty($data)) {
            return $columns;
        }

        $dynamicColumns = [];

        // Find all unique keys in data that aren't already in columns
        $existingKeys = array_column($columns, 'key');
        $dataKeys = [];

        foreach ($data as $row) {
            $dataKeys = array_merge($dataKeys, array_keys($row));
        }

        $uniqueDataKeys = array_unique($dataKeys);
        $newKeys = array_diff($uniqueDataKeys, $existingKeys);

        foreach ($newKeys as $key) {
            // Skip internal keys
            if (strpos($key, '_') === 0) {
                continue;
            }

            $dynamicColumns[] = [
                'key' => $key,
                'label' => $this->generateColumnLabel($key),
                'type' => $this->inferColumnType($key, $data),
                'width' => 'auto',
                'dynamic' => true,
                'required' => false,
                'empty_text' => '未設定'
            ];
        }

        return array_merge($columns, $dynamicColumns);
    }

    /**
     * Generate a human-readable label from column key
     *
     * @param string $key
     * @return string
     */
    protected function generateColumnLabel(string $key): string
    {
        // Convert snake_case to Title Case
        $label = str_replace('_', ' ', $key);
        $label = ucwords($label);

        // Handle common Japanese translations
        $translations = [
            'Id' => 'ID',
            'Url' => 'URL',
            'Email' => 'メールアドレス',
            'Email Address' => 'メールアドレス',
            'Phone' => '電話番号',
            'Phone Number' => '電話番号',
            'Address' => '住所',
            'Name' => '名前',
            'User Name' => 'ユーザー名',
            'Type' => '種類',
            'Status' => 'ステータス',
            'Date' => '日付',
            'Created At' => '作成日時',
            'Updated At' => '更新日時',
        ];

        return $translations[$label] ?? $label;
    }

    /**
     * Infer column type from data content
     *
     * @param string $key
     * @param array $data
     * @return string
     */
    protected function inferColumnType(string $key, array $data): string
    {
        $sampleValues = [];

        // Collect sample values
        foreach ($data as $row) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                $sampleValues[] = $row[$key];
                if (count($sampleValues) >= 5) break; // Sample first 5 non-empty values
            }
        }

        if (empty($sampleValues)) {
            return 'text';
        }

        // Check for email pattern
        if ($this->isEmailColumn($key, $sampleValues)) {
            return 'email';
        }

        // Check for URL pattern
        if ($this->isUrlColumn($key, $sampleValues)) {
            return 'url';
        }

        // Check for phone pattern
        if ($this->isPhoneColumn($key, $sampleValues)) {
            return 'phone';
        }

        // Check for date pattern
        if ($this->isDateColumn($key, $sampleValues)) {
            return 'date';
        }

        // Check for number pattern
        if ($this->isNumberColumn($key, $sampleValues)) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Check if column contains email data
     *
     * @param string $key
     * @param array $values
     * @return bool
     */
    protected function isEmailColumn(string $key, array $values): bool
    {
        if (strpos(strtolower($key), 'email') !== false || strpos(strtolower($key), 'mail') !== false) {
            return true;
        }

        $emailCount = 0;
        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $emailCount++;
            }
        }

        return $emailCount / count($values) > 0.5; // More than 50% are valid emails
    }

    /**
     * Check if column contains URL data
     *
     * @param string $key
     * @param array $values
     * @return bool
     */
    protected function isUrlColumn(string $key, array $values): bool
    {
        if (strpos(strtolower($key), 'url') !== false || strpos(strtolower($key), 'website') !== false) {
            return true;
        }

        $urlCount = 0;
        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_URL) || preg_match('/^https?:\/\//', $value)) {
                $urlCount++;
            }
        }

        return $urlCount / count($values) > 0.5;
    }

    /**
     * Check if column contains phone data
     *
     * @param string $key
     * @param array $values
     * @return bool
     */
    protected function isPhoneColumn(string $key, array $values): bool
    {
        if (strpos(strtolower($key), 'phone') !== false || strpos(strtolower($key), 'tel') !== false) {
            return true;
        }

        $phoneCount = 0;
        foreach ($values as $value) {
            // Simple phone pattern check
            if (preg_match('/^[\d\-\(\)\+\s]{10,}$/', $value)) {
                $phoneCount++;
            }
        }

        return $phoneCount / count($values) > 0.7;
    }

    /**
     * Check if column contains date data
     *
     * @param string $key
     * @param array $values
     * @return bool
     */
    protected function isDateColumn(string $key, array $values): bool
    {
        if (strpos(strtolower($key), 'date') !== false || strpos(strtolower($key), '_at') !== false) {
            return true;
        }

        $dateCount = 0;
        foreach ($values as $value) {
            try {
                // Only consider it a date if it's not a simple number
                if (is_numeric($value)) {
                    continue;
                }
                Carbon::parse($value);
                $dateCount++;
            } catch (\Exception $e) {
                // Not a valid date
            }
        }

        return count($values) > 0 && $dateCount / count($values) > 0.7;
    }

    /**
     * Check if column contains numeric data
     *
     * @param string $key
     * @param array $values
     * @return bool
     */
    protected function isNumberColumn(string $key, array $values): bool
    {
        if (strpos(strtolower($key), 'count') !== false || 
            strpos(strtolower($key), 'amount') !== false ||
            strpos(strtolower($key), 'price') !== false ||
            strpos(strtolower($key), 'cost') !== false) {
            return true;
        }

        $numberCount = 0;
        foreach ($values as $value) {
            if (is_numeric($value) && !$this->looksLikeDate($value)) {
                $numberCount++;
            }
        }

        return count($values) > 0 && $numberCount / count($values) > 0.8;
    }

    /**
     * Check if a value looks like a date
     *
     * @param mixed $value
     * @return bool
     */
    protected function looksLikeDate($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        // Check for common date patterns
        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) || 
               preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value) ||
               strpos($value, '-') !== false && strlen($value) > 8;
    }

    /**
     * Calculate optimal column widths based on content
     *
     * @param array $columns
     * @param array $data
     * @return array
     */
    public function calculateOptimalColumnWidths(array $columns, array $data): array
    {
        if (empty($data)) {
            return $columns;
        }

        $totalColumns = count($columns);
        $baseWidth = 100 / $totalColumns;
        $adjustedColumns = [];

        foreach ($columns as $column) {
            $key = $column['key'];
            $maxLength = strlen($column['label']); // Start with header length

            // Find maximum content length for this column
            foreach ($data as $row) {
                $value = $row[$key] ?? '';
                $displayValue = $this->formatValue($value, $column) ?? '';
                $contentLength = mb_strlen(strip_tags($displayValue));
                $maxLength = max($maxLength, $contentLength);
            }

            // Calculate width based on content length
            $calculatedWidth = $this->calculateWidthFromLength($maxLength, $column['type'] ?? 'text');
            
            // Apply constraints
            $minWidth = $column['min_width'] ?? 8;
            $maxWidth = $column['max_width'] ?? 30;
            $width = max($minWidth, min($maxWidth, $calculatedWidth));

            $column['calculated_width'] = $width . '%';
            $adjustedColumns[] = $column;
        }

        // Normalize widths to total 100%
        return $this->normalizeColumnWidths($adjustedColumns);
    }

    /**
     * Calculate width percentage from content length
     *
     * @param int $length
     * @param string $type
     * @return float
     */
    protected function calculateWidthFromLength(int $length, string $type): float
    {
        // Base calculation: roughly 0.8% per character
        $baseWidth = $length * 0.8;

        // Adjust based on column type
        switch ($type) {
            case 'email':
            case 'url':
                return max($baseWidth, 20); // URLs and emails need more space
            case 'date':
            case 'date_range':
                return max($baseWidth, 12); // Dates have predictable width
            case 'number':
                return max($baseWidth, 8); // Numbers are usually shorter
            case 'select':
                return max($baseWidth, 10); // Select options vary
            default:
                return max($baseWidth, 10); // Default minimum
        }
    }

    /**
     * Normalize column widths to total 100%
     *
     * @param array $columns
     * @return array
     */
    protected function normalizeColumnWidths(array $columns): array
    {
        $totalWidth = 0;
        
        foreach ($columns as $column) {
            $width = floatval(str_replace('%', '', $column['calculated_width'] ?? '10'));
            $totalWidth += $width;
        }

        if ($totalWidth <= 0) {
            return $columns;
        }

        $normalizedColumns = [];
        foreach ($columns as $column) {
            $width = floatval(str_replace('%', '', $column['calculated_width'] ?? '10'));
            $normalizedWidth = ($width / $totalWidth) * 100;
            $column['width'] = round($normalizedWidth, 1) . '%';
            $normalizedColumns[] = $column;
        }

        return $normalizedColumns;
    }

    /**
     * Process cell merging configurations
     *
     * @param array $data
     * @param array $mergingConfig
     * @return array
     */
    public function processCellMerging(array $data, array $mergingConfig): array
    {
        if (empty($data) || empty($mergingConfig)) {
            return $data;
        }

        $processedData = [];
        
        foreach ($data as $rowIndex => $row) {
            $processedRow = $row;
            
            foreach ($mergingConfig as $mergeConfig) {
                $processedRow = $this->applyCellMerge($processedRow, $mergeConfig, $rowIndex, $data);
            }
            
            $processedData[] = $processedRow;
        }
        
        return $processedData;
    }

    /**
     * Apply a single cell merge configuration
     *
     * @param array $row
     * @param array $mergeConfig
     * @param int $rowIndex
     * @param array $allData
     * @return array
     */
    protected function applyCellMerge(array $row, array $mergeConfig, int $rowIndex, array $allData): array
    {
        $type = $mergeConfig['type'] ?? 'horizontal';
        $columns = $mergeConfig['columns'] ?? [];
        $condition = $mergeConfig['condition'] ?? null;
        
        // Check if merge condition is met
        if ($condition && !$this->evaluateMergeCondition($row, $condition, $rowIndex, $allData)) {
            return $row;
        }
        
        switch ($type) {
            case 'horizontal':
                return $this->applyHorizontalMerge($row, $columns, $mergeConfig);
                
            case 'vertical':
                return $this->applyVerticalMerge($row, $columns, $mergeConfig, $rowIndex, $allData);
                
            case 'complex':
                return $this->applyComplexMerge($row, $mergeConfig, $rowIndex, $allData);
                
            default:
                return $row;
        }
    }

    /**
     * Apply horizontal cell merging
     *
     * @param array $row
     * @param array $columns
     * @param array $mergeConfig
     * @return array
     */
    protected function applyHorizontalMerge(array $row, array $columns, array $mergeConfig): array
    {
        if (count($columns) < 2) {
            return $row;
        }
        
        $targetColumn = $columns[0];
        $separator = $mergeConfig['separator'] ?? ' ';
        $template = $mergeConfig['template'] ?? null;
        
        // Collect values from all columns (including target)
        $values = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? '';
            if ($value !== '' && $value !== null) {
                $values[] = $value;
            }
        }
        
        // Merge values
        if ($template) {
            $mergedValue = $this->applyMergeTemplate($template, $row, $columns);
        } else {
            $mergedValue = implode($separator, $values);
        }
        
        // Set merged value and mark source columns as merged
        $row[$targetColumn] = $mergedValue;
        $row['_merged_horizontal'] = $row['_merged_horizontal'] ?? [];
        $row['_merged_horizontal'][$targetColumn] = [
            'source_columns' => array_slice($columns, 1),
            'colspan' => count($columns)
        ];
        
        // Mark source columns as hidden (not the target)
        $row['_hidden_columns'] = $row['_hidden_columns'] ?? [];
        foreach (array_slice($columns, 1) as $column) {
            $row['_hidden_columns'][] = $column;
        }
        
        return $row;
    }

    /**
     * Apply vertical cell merging
     *
     * @param array $row
     * @param array $columns
     * @param array $mergeConfig
     * @param int $rowIndex
     * @param array $allData
     * @return array
     */
    protected function applyVerticalMerge(array $row, array $columns, array $mergeConfig, int $rowIndex, array $allData): array
    {
        foreach ($columns as $column) {
            $groupBy = $mergeConfig['group_by'] ?? $column;
            $currentValue = $row[$groupBy] ?? null;
            
            // Find consecutive rows with same value
            $groupSize = 1;
            $isFirstInGroup = true;
            
            // Check previous rows
            for ($i = $rowIndex - 1; $i >= 0; $i--) {
                if (($allData[$i][$groupBy] ?? null) === $currentValue) {
                    $isFirstInGroup = false;
                    break;
                }
            }
            
            // Count group size if this is the first row
            if ($isFirstInGroup) {
                for ($i = $rowIndex + 1; $i < count($allData); $i++) {
                    if (($allData[$i][$groupBy] ?? null) === $currentValue) {
                        $groupSize++;
                    } else {
                        break;
                    }
                }
            }
            
            // Mark vertical merge info
            $row['_merged_vertical'] = $row['_merged_vertical'] ?? [];
            $row['_merged_vertical'][$column] = [
                'is_first' => $isFirstInGroup,
                'rowspan' => $isFirstInGroup ? $groupSize : 0,
                'group_value' => $currentValue
            ];
        }
        
        return $row;
    }

    /**
     * Apply complex cell merging (combination of horizontal and vertical)
     *
     * @param array $row
     * @param array $mergeConfig
     * @param int $rowIndex
     * @param array $allData
     * @return array
     */
    protected function applyComplexMerge(array $row, array $mergeConfig, int $rowIndex, array $allData): array
    {
        // Apply horizontal merging first
        if (isset($mergeConfig['horizontal'])) {
            $row = $this->applyHorizontalMerge($row, $mergeConfig['horizontal']['columns'], $mergeConfig['horizontal']);
        }
        
        // Then apply vertical merging
        if (isset($mergeConfig['vertical'])) {
            $row = $this->applyVerticalMerge($row, $mergeConfig['vertical']['columns'], $mergeConfig['vertical'], $rowIndex, $allData);
        }
        
        return $row;
    }

    /**
     * Apply merge template to combine values
     *
     * @param string $template
     * @param array $row
     * @param array $columns
     * @return string
     */
    protected function applyMergeTemplate(string $template, array $row, array $columns): string
    {
        $result = $template;
        
        foreach ($columns as $index => $column) {
            $placeholder = '{' . $index . '}';
            $value = $row[$column] ?? '';
            $result = str_replace($placeholder, $value, $result);
            
            // Also support column name placeholders
            $placeholder = '{' . $column . '}';
            $result = str_replace($placeholder, $value, $result);
        }
        
        return $result;
    }

    /**
     * Evaluate merge condition
     *
     * @param array $row
     * @param array $condition
     * @param int $rowIndex
     * @param array $allData
     * @return bool
     */
    protected function evaluateMergeCondition(array $row, array $condition, int $rowIndex, array $allData): bool
    {
        $type = $condition['type'] ?? 'always';
        
        switch ($type) {
            case 'field_not_empty':
                $field = $condition['field'] ?? '';
                return !empty($row[$field]);
                
            case 'field_equals':
                $field = $condition['field'] ?? '';
                $value = $condition['value'] ?? null;
                return ($row[$field] ?? null) === $value;
                
            case 'row_index':
                $operator = $condition['operator'] ?? '=';
                $value = $condition['value'] ?? 0;
                return $this->compareValues($rowIndex, $operator, $value);
                
            case 'custom':
                $function = $condition['function'] ?? null;
                if ($function && method_exists($this, $function)) {
                    return $this->$function($row, $rowIndex, $allData);
                }
                return true;
                
            case 'never':
                return false;
                
            case 'always':
            default:
                return true;
        }
    }

    /**
     * Compare values with operator
     *
     * @param mixed $left
     * @param string $operator
     * @param mixed $right
     * @return bool
     */
    protected function compareValues($left, string $operator, $right): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $left == $right;
            case '!=':
                return $left != $right;
            case '>':
                return $left > $right;
            case '>=':
                return $left >= $right;
            case '<':
                return $left < $right;
            case '<=':
                return $left <= $right;
            default:
                return false;
        }
    }

    /**
     * Process nested data structures for hierarchical display
     *
     * @param array $data
     * @param array $nestingConfig
     * @return array
     */
    public function processNestedData(array $data, array $nestingConfig): array
    {
        if (empty($data) || empty($nestingConfig)) {
            return $data;
        }
        
        $processedData = [];
        
        foreach ($data as $row) {
            $nestedRows = $this->expandNestedRow($row, $nestingConfig);
            $processedData = array_merge($processedData, $nestedRows);
        }
        
        return $processedData;
    }

    /**
     * Expand a single row with nested data
     *
     * @param array $row
     * @param array $nestingConfig
     * @return array
     */
    protected function expandNestedRow(array $row, array $nestingConfig): array
    {
        $nestedField = $nestingConfig['nested_field'] ?? null;
        $parentFields = $nestingConfig['parent_fields'] ?? [];
        $childFields = $nestingConfig['child_fields'] ?? [];
        $maxDepth = $nestingConfig['max_depth'] ?? 3;
        
        if (!$nestedField || !isset($row[$nestedField])) {
            return [$row];
        }
        
        $nestedData = $row[$nestedField];
        if (!is_array($nestedData)) {
            return [$row];
        }
        
        $expandedRows = [];
        
        foreach ($nestedData as $index => $nestedItem) {
            $expandedRow = [];
            
            // Add parent fields
            foreach ($parentFields as $field) {
                $expandedRow[$field] = $row[$field] ?? null;
            }
            
            // Add child fields
            if (is_array($nestedItem)) {
                foreach ($childFields as $field) {
                    $expandedRow[$field] = $nestedItem[$field] ?? null;
                }
            } else {
                $expandedRow[$nestedField] = $nestedItem;
            }
            
            // Add nesting metadata
            $expandedRow['_nested_info'] = [
                'parent_index' => 0,
                'child_index' => $index,
                'depth' => 1,
                'is_first_child' => $index === 0,
                'is_last_child' => $index === count($nestedData) - 1,
                'total_children' => count($nestedData)
            ];
            
            $expandedRows[] = $expandedRow;
        }
        
        return $expandedRows;
    }

    /**
     * Generate nested data display structure
     *
     * @param array $data
     * @param array $hierarchyConfig
     * @return array
     */
    public function generateNestedDisplayStructure(array $data, array $hierarchyConfig): array
    {
        $levels = $hierarchyConfig['levels'] ?? [];
        $indentSize = $hierarchyConfig['indent_size'] ?? 20;
        $showConnectors = $hierarchyConfig['show_connectors'] ?? true;
        
        $structuredData = [];
        
        foreach ($data as $row) {
            $level = $this->determineRowLevel($row, $levels);
            $indent = $level * $indentSize;
            
            $row['_display_structure'] = [
                'level' => $level,
                'indent' => $indent . 'px',
                'css_class' => 'nested-level-' . $level,
                'show_connector' => $showConnectors && $level > 0,
                'connector_type' => $this->getConnectorType($row, $level)
            ];
            
            $structuredData[] = $row;
        }
        
        return $structuredData;
    }

    /**
     * Determine the nesting level of a row
     *
     * @param array $row
     * @param array $levels
     * @return int
     */
    protected function determineRowLevel(array $row, array $levels): int
    {
        foreach ($levels as $levelIndex => $levelConfig) {
            $field = $levelConfig['field'] ?? null;
            $condition = $levelConfig['condition'] ?? null;
            
            if ($field && isset($row[$field])) {
                if (!$condition || $this->evaluateNestedCondition($row, $condition)) {
                    return $levelIndex;
                }
            }
        }
        
        return 0; // Default to root level
    }

    /**
     * Get connector type for nested display
     *
     * @param array $row
     * @param int $level
     * @return string
     */
    protected function getConnectorType(array $row, int $level): string
    {
        $nestedInfo = $row['_nested_info'] ?? [];
        
        if ($level === 0) {
            return 'none';
        }
        
        if ($nestedInfo['is_last_child'] ?? false) {
            return 'end';
        }
        
        if ($nestedInfo['is_first_child'] ?? false) {
            return 'start';
        }
        
        return 'middle';
    }

    /**
     * Evaluate nested display condition
     *
     * @param array $row
     * @param array $condition
     * @return bool
     */
    protected function evaluateNestedCondition(array $row, array $condition): bool
    {
        $type = $condition['type'] ?? 'field_exists';
        
        switch ($type) {
            case 'field_exists':
                $field = $condition['field'] ?? '';
                return isset($row[$field]);
                
            case 'field_not_empty':
                $field = $condition['field'] ?? '';
                return !empty($row[$field]);
                
            case 'depth_equals':
                $depth = $condition['depth'] ?? 0;
                return ($row['_nested_info']['depth'] ?? 0) === $depth;
                
            default:
                return true;
        }
    }


}