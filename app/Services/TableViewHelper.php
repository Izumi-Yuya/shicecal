<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Table View Helper Service
 * 
 * Provides utilities for data preparation, CSS class generation, and responsive
 * column width calculations for universal table components.
 */
class TableViewHelper
{
    /**
     * @var TableConfigService
     */
    protected $configService;

    /**
     * @var TableDataFormatter
     */
    protected $formatter;

    public function __construct(TableConfigService $configService, TableDataFormatter $formatter)
    {
        $this->configService = $configService;
        $this->formatter = $formatter;
    }

    /**
     * Prepare table data for view rendering
     *
     * @param Collection|array $data
     * @param string $tableType
     * @return array
     */
    public function prepareTableData($data, string $tableType): array
    {
        $config = $this->configService->getTableConfig($tableType);
        
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        
        // Format data using the formatter
        $formattedData = $this->formatter->formatTableData($data, $config);
        
        // Prepare additional metadata
        $metadata = [
            'table_type' => $tableType,
            'row_count' => is_array($formattedData) && isset($formattedData[0]) && is_array($formattedData[0]) 
                ? count($formattedData) 
                : (empty($formattedData) ? 0 : 1),
            'column_count' => count($config['columns'] ?? []),
            'has_data' => !empty($formattedData),
            'layout_type' => $config['layout']['type'] ?? 'key_value_pairs',
            'responsive_enabled' => $config['global_settings']['responsive']['enabled'] ?? true
        ];
        
        return [
            'data' => $formattedData,
            'metadata' => $metadata,
            'config' => $config
        ];
    }

    /**
     * Generate CSS classes for table based on configuration
     *
     * @param array $config
     * @param array $options
     * @return string
     */
    public function generateTableClasses(array $config, array $options = []): string
    {
        $classes = [];
        
        // Base table classes from configuration
        $baseClass = $config['styling']['table_class'] ?? 'table table-bordered';
        $classes[] = $baseClass;
        
        // Layout-specific classes
        $layoutType = $config['layout']['type'] ?? 'key_value_pairs';
        $classes[] = 'table-layout-' . str_replace('_', '-', $layoutType);
        
        // Responsive classes
        if ($config['global_settings']['responsive']['enabled'] ?? true) {
            $breakpoint = $config['layout']['responsive_breakpoint'] ?? 'lg';
            $classes[] = 'table-responsive-' . $breakpoint;
        }
        
        // Feature-based classes
        $features = $config['features'] ?? [];
        if ($features['sorting'] ?? false) {
            $classes[] = 'table-sortable';
        }
        if ($features['filtering'] ?? false) {
            $classes[] = 'table-filterable';
        }
        
        // Size classes
        if (isset($options['size'])) {
            $classes[] = 'table-' . $options['size'];
        }
        
        // State classes
        if (isset($options['state'])) {
            $classes[] = 'table-' . $options['state'];
        }
        
        // Custom classes from options
        if (isset($options['additional_classes'])) {
            if (is_array($options['additional_classes'])) {
                $classes = array_merge($classes, $options['additional_classes']);
            } else {
                $classes[] = $options['additional_classes'];
            }
        }
        
        return implode(' ', array_unique(array_filter($classes)));
    }

    /**
     * Calculate responsive column widths for PC environments
     *
     * @param array $columns
     * @param array $options
     * @return array
     */
    public function calculateColumnWidths(array $columns, array $options = []): array
    {
        $totalColumns = count($columns);
        $widths = [];
        $specifiedWidths = [];
        $remainingWidth = 100;
        
        // First pass: collect explicitly specified widths
        foreach ($columns as $index => $column) {
            if (isset($column['width'])) {
                $width = $this->parseWidth($column['width']);
                $specifiedWidths[$index] = $width;
                $remainingWidth -= $width;
            }
        }
        
        // Calculate auto widths for columns without specified widths
        $autoColumns = $totalColumns - count($specifiedWidths);
        $autoWidth = $autoColumns > 0 ? $remainingWidth / $autoColumns : 0;
        
        // Second pass: assign final widths
        foreach ($columns as $index => $column) {
            if (isset($specifiedWidths[$index])) {
                $widths[$index] = $specifiedWidths[$index] . '%';
            } else {
                $widths[$index] = round($autoWidth, 2) . '%';
            }
        }
        
        // Apply minimum width constraints for PC environments
        $minWidth = $options['min_column_width'] ?? 120; // pixels
        $screenWidth = $options['screen_width'] ?? 1200; // pixels
        
        foreach ($widths as $index => $width) {
            $pixelWidth = ($screenWidth * floatval($width)) / 100;
            if ($pixelWidth < $minWidth) {
                $widths[$index] = 'min-width: ' . $minWidth . 'px';
            }
        }
        
        return $widths;
    }

    /**
     * Parse width value from string (e.g., "25%", "200px")
     *
     * @param string $width
     * @return float
     */
    protected function parseWidth(string $width): float
    {
        if (strpos($width, '%') !== false) {
            return floatval(str_replace('%', '', $width));
        } elseif (strpos($width, 'px') !== false) {
            // Convert pixels to percentage (assuming 1200px screen width)
            $pixels = floatval(str_replace('px', '', $width));
            return ($pixels / 1200) * 100;
        }
        
        return floatval($width);
    }

    /**
     * Generate row classes based on data and configuration
     *
     * @param array $rowData
     * @param int $index
     * @param array $config
     * @return string
     */
    public function generateRowClasses(array $rowData, int $index, array $config = []): string
    {
        $classes = [];
        
        // Base row class
        $classes[] = 'table-row';
        
        // Index-based classes
        $classes[] = $index % 2 === 0 ? 'row-even' : 'row-odd';
        
        // Data-based classes
        if (isset($rowData['_group_value'])) {
            $classes[] = 'row-group-' . $this->sanitizeHtmlClass($rowData['_group_value']);
        }
        
        if (isset($rowData['_rowspan']) && $rowData['_rowspan'] > 0) {
            $classes[] = 'row-group-first';
        }
        
        // State-based classes
        if (isset($rowData['status'])) {
            $classes[] = 'row-status-' . $this->sanitizeHtmlClass($rowData['status']);
        }
        
        // Priority-based classes
        if (isset($rowData['priority'])) {
            $classes[] = 'row-priority-' . $this->sanitizeHtmlClass($rowData['priority']);
        }
        
        return implode(' ', array_unique(array_filter($classes)));
    }

    /**
     * Generate header classes for table headers
     *
     * @param array $column
     * @param array $config
     * @return string
     */
    public function generateHeaderClasses(array $column, array $config = []): string
    {
        $classes = [];
        
        // Base header class from config
        $baseClass = $config['styling']['header_class'] ?? 'bg-primary text-white';
        $classes[] = $baseClass;
        
        // Column-specific classes
        if (isset($column['type'])) {
            $classes[] = 'header-type-' . str_replace('_', '-', $column['type']);
        }
        
        if ($column['required'] ?? false) {
            $classes[] = 'header-required';
        }
        
        if ($column['sortable'] ?? false) {
            $classes[] = 'header-sortable';
        }
        
        // Alignment classes
        if (isset($column['align'])) {
            $classes[] = 'text-' . $column['align'];
        }
        
        return implode(' ', array_unique(array_filter($classes)));
    }

    /**
     * Generate cell classes for table cells
     *
     * @param mixed $value
     * @param array $column
     * @param array $config
     * @return string
     */
    public function generateCellClasses($value, array $column, array $config = []): string
    {
        $classes = [];
        
        // Base cell class
        $classes[] = 'table-cell';
        
        // Type-based classes
        if (isset($column['type'])) {
            $classes[] = 'cell-type-' . str_replace('_', '-', $column['type']);
        }
        
        // Value-based classes
        if ($value === null || $value === '') {
            $classes[] = 'cell-empty';
            $emptyClass = $config['styling']['empty_value_class'] ?? 'text-muted';
            $classes[] = $emptyClass;
        } else {
            $classes[] = 'cell-has-value';
        }
        
        // Alignment classes
        if (isset($column['align'])) {
            $classes[] = 'text-' . $column['align'];
        }
        
        // Numeric alignment for number types
        if ($column['type'] === 'number' && !isset($column['align'])) {
            $classes[] = 'text-end';
        }
        
        return implode(' ', array_unique(array_filter($classes)));
    }

    /**
     * Prepare data for key-value pairs layout
     *
     * @param array $data
     * @param array $config
     * @return array
     */
    public function prepareKeyValueData(array $data, array $config): array
    {
        $columns = $config['columns'] ?? [];
        $columnsPerRow = $config['layout']['columns_per_row'] ?? 2;
        
        // Group columns into rows
        $columnGroups = array_chunk($columns, $columnsPerRow);
        
        $prepared = [];
        foreach ($columnGroups as $groupIndex => $columnGroup) {
            $row = [];
            foreach ($columnGroup as $column) {
                $key = $column['key'];
                $value = $data[$key] ?? null;
                
                $row[] = [
                    'key' => $key,
                    'label' => $column['label'],
                    'value' => $value,
                    'column' => $column,
                    'formatted_value' => $this->formatter->formatValue($value, $column)
                ];
            }
            $prepared[] = $row;
        }
        
        return $prepared;
    }

    /**
     * Prepare data for grouped rows layout
     *
     * @param array $data
     * @param array $config
     * @return array
     */
    public function prepareGroupedData(array $data, array $config): array
    {
        $groupBy = $config['layout']['group_by'] ?? null;
        
        if (!$groupBy) {
            return $data;
        }
        
        $grouped = $this->formatter->groupDataBy($data, $groupBy);
        $prepared = [];
        
        foreach ($grouped as $groupValue => $items) {
            $groupData = [
                'group_value' => $groupValue,
                'group_count' => count($items),
                'items' => []
            ];
            
            foreach ($items as $index => $item) {
                $item['_is_first_in_group'] = $index === 0;
                $item['_group_index'] = $index;
                $groupData['items'][] = $item;
            }
            
            $prepared[] = $groupData;
        }
        
        return $prepared;
    }

    /**
     * Get responsive breakpoint CSS for PC environments
     *
     * @param array $config
     * @return string
     */
    public function getResponsiveCSS(array $config): string
    {
        $breakpoints = $config['global_settings']['responsive']['breakpoints'] ?? [
            'lg' => '992px',
            'md' => '768px',
            'sm' => '576px'
        ];
        
        $css = '';
        
        foreach ($breakpoints as $size => $width) {
            $css .= "@media (max-width: {$width}) {\n";
            $css .= "  .table-responsive-{$size} {\n";
            $css .= "    overflow-x: auto;\n";
            $css .= "    -webkit-overflow-scrolling: touch;\n";
            $css .= "  }\n";
            $css .= "  .table-responsive-{$size}::after {\n";
            $css .= "    content: '→ 横スクロールできます';\n";
            $css .= "    position: absolute;\n";
            $css .= "    top: 10px;\n";
            $css .= "    right: 10px;\n";
            $css .= "    background: rgba(0, 123, 255, 0.8);\n";
            $css .= "    color: white;\n";
            $css .= "    padding: 4px 8px;\n";
            $css .= "    border-radius: 4px;\n";
            $css .= "    font-size: 12px;\n";
            $css .= "    pointer-events: none;\n";
            $css .= "    z-index: 10;\n";
            $css .= "  }\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }

    /**
     * Sanitize string for use as HTML class name
     *
     * @param string $class
     * @return string
     */
    protected function sanitizeHtmlClass(string $class): string
    {
        // Convert to lowercase and replace non-alphanumeric characters with hyphens
        $class = strtolower($class);
        $class = preg_replace('/[^a-z0-9\-_]/', '-', $class);
        $class = preg_replace('/-+/', '-', $class);
        $class = trim($class, '-');
        
        return $class;
    }
}