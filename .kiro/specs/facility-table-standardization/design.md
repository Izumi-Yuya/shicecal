# Design Document

## Overview

This design document outlines the technical approach for standardizing all facility table displays in the Shise-Cal facility management system. The solution will create a unified table component system that provides consistent user experience across basic information, service information, and land information sections while maintaining the existing functionality and improving maintainability.

## Architecture

### Component-Based Architecture

The standardization will be implemented using a layered component architecture:

```
┌─────────────────────────────────────────┐
│           Blade View Layer              │
│  ┌─────────────────────────────────────┐│
│  │     Standardized Table Views       ││
│  │  • basic-info-table.blade.php     ││
│  │  • service-info-table.blade.php   ││
│  │  • land-info-table.blade.php      ││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│        Component Layer                  │
│  ┌─────────────────────────────────────┐│
│  │    Universal Table Component       ││
│  │  • table-wrapper.blade.php        ││
│  │  • table-header.blade.php         ││
│  │  • table-body.blade.php           ││
│  │  • comment-section.blade.php      ││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│         Service Layer                   │
│  ┌─────────────────────────────────────┐│
│  │      Table Configuration           ││
│  │  • TableConfigService.php         ││
│  │  • TableDataFormatter.php         ││
│  │  • TableViewHelper.php            ││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│      Configuration Layer                │
│  ┌─────────────────────────────────────┐│
│  │       Config Files                 ││
│  │  • table-config.php               ││
│  │  • table-styling.php              ││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
```

### Design Patterns

1. **Template Method Pattern**: Base table component with customizable sections
2. **Strategy Pattern**: Different formatting strategies for different data types
3. **Configuration Pattern**: Centralized configuration management
4. **Observer Pattern**: Comment system integration

## Components and Interfaces

### Core Components

#### 1. Universal Table Component (`resources/views/components/universal-table.blade.php`)

```php
@props([
    'tableId' => null,
    'config' => [],
    'data' => [],
    'section' => null,
    'commentEnabled' => true,
    'responsive' => true
])
```

**Responsibilities:**
- Render standardized table structure
- Handle responsive layout for PC environments
- Integrate comment functionality
- Apply consistent styling

#### 2. Table Configuration Service (`app/Services/TableConfigService.php`)

```php
interface TableConfigServiceInterface
{
    public function getTableConfig(string $tableType): array;
    public function validateConfig(array $config): bool;
    public function mergeWithDefaults(array $config): array;
}
```

**Responsibilities:**
- Load and validate table configurations
- Provide default fallback configurations
- Handle dynamic column configurations

#### 3. Table Data Formatter (`app/Services/TableDataFormatter.php`)

```php
interface TableDataFormatterInterface
{
    public function formatTableData(array $data, array $config): array;
    public function formatEmptyValue(string $fieldName): string;
    public function formatComplexColumns(array $data, array $columnConfig): array;
}
```

**Responsibilities:**
- Format data according to table configuration
- Handle empty value display
- Process complex column structures (merged cells, hierarchical headers)

#### 4. Table View Helper (`app/Services/TableViewHelper.php`)

```php
interface TableViewHelperInterface
{
    public function prepareTableData(Collection $data, string $tableType): array;
    public function generateTableClasses(array $config): string;
    public function calculateColumnWidths(array $columns): array;
}
```

**Responsibilities:**
- Prepare data for view rendering
- Generate CSS classes based on configuration
- Calculate responsive column widths

### Interface Definitions

#### Table Configuration Interface

```php
interface TableConfigInterface
{
    public const BASIC_INFO_TABLE = 'basic_info';
    public const SERVICE_INFO_TABLE = 'service_info';
    public const LAND_INFO_TABLE = 'land_info';
    
    public function getColumns(): array;
    public function getStyling(): array;
    public function getResponsiveSettings(): array;
    public function getCommentSettings(): array;
}
```

## Data Models

### Table Configuration Schema

```php
// config/table-config.php structure
[
    'tables' => [
        'basic_info' => [
            'columns' => [
                [
                    'key' => 'company_name',
                    'label' => '会社名',
                    'type' => 'text',
                    'width' => '25%',
                    'required' => false,
                    'empty_text' => '未設定'
                ],
                // ... more columns
            ],
            'layout' => [
                'type' => 'key_value_pairs', // or 'standard_table'
                'columns_per_row' => 2,
                'responsive_breakpoint' => 'lg'
            ],
            'styling' => [
                'table_class' => 'table table-bordered facility-info',
                'header_class' => 'bg-primary text-white',
                'empty_value_class' => 'text-muted'
            ],
            'features' => [
                'comments' => true,
                'sorting' => false,
                'filtering' => false
            ]
        ],
        'service_info' => [
            'columns' => [
                [
                    'key' => 'service_type',
                    'label' => 'サービス種類',
                    'type' => 'text',
                    'rowspan_group' => true
                ],
                [
                    'key' => 'renewal_period',
                    'label' => '有効期限',
                    'type' => 'date_range',
                    'format' => 'Y年m月d日',
                    'separator' => '〜'
                ]
            ],
            'layout' => [
                'type' => 'grouped_rows',
                'group_by' => 'service_type'
            ]
        ],
        'land_info' => [
            'columns' => [
                [
                    'key' => 'land_type',
                    'label' => '土地種別',
                    'type' => 'select'
                ],
                [
                    'key' => 'area',
                    'label' => '面積',
                    'type' => 'number',
                    'unit' => '㎡'
                ]
            ],
            'layout' => [
                'type' => 'standard_table',
                'show_headers' => true
            ]
        ]
    ],
    'global_settings' => [
        'responsive' => [
            'enabled' => true,
            'pc_only' => true,
            'breakpoints' => [
                'lg' => '992px',
                'md' => '768px',
                'sm' => '576px'
            ]
        ],
        'performance' => [
            'cache_enabled' => true,
            'cache_ttl' => 300
        ]
    ]
]
```

### Comment Integration Schema

```php
// Existing comment system integration
[
    'comment_sections' => [
        'basic_info' => [
            'section_name' => 'basic_info',
            'display_name' => '基本情報',
            'enabled' => true
        ],
        'service_info' => [
            'section_name' => 'service_info', 
            'display_name' => 'サービス情報',
            'enabled' => true
        ],
        'land_info' => [
            'section_name' => 'land_info',
            'display_name' => '土地情報', 
            'enabled' => true
        ]
    ]
]
```

## Error Handling

### Configuration Validation

```php
class TableConfigValidator
{
    public function validate(array $config): ValidationResult
    {
        $errors = [];
        
        // Validate required fields
        if (!isset($config['columns']) || empty($config['columns'])) {
            $errors[] = 'Table must have at least one column defined';
        }
        
        // Validate column structure
        foreach ($config['columns'] as $column) {
            if (!isset($column['key']) || !isset($column['label'])) {
                $errors[] = 'Each column must have key and label';
            }
        }
        
        // Validate responsive settings
        if (isset($config['responsive']) && !$this->validateResponsiveConfig($config['responsive'])) {
            $errors[] = 'Invalid responsive configuration';
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
}
```

### Fallback Mechanisms

1. **Configuration Fallback**: If custom config fails, use default configuration
2. **Data Fallback**: If data formatting fails, display raw data with warning
3. **Component Fallback**: If universal component fails, fall back to original table
4. **Style Fallback**: If custom styles fail, use Bootstrap default classes

### Error Logging

```php
class TableErrorHandler
{
    public function handleConfigError(string $tableType, Exception $e): void
    {
        Log::warning("Table configuration error for {$tableType}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Use default configuration
        $this->useDefaultConfig($tableType);
    }
    
    public function handleRenderError(string $tableType, Exception $e): void
    {
        Log::error("Table rendering error for {$tableType}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fall back to simple table
        $this->renderFallbackTable($tableType);
    }
}
```

## Testing Strategy

### Unit Testing

1. **Configuration Service Tests**
   - Test configuration loading and validation
   - Test default fallback mechanisms
   - Test configuration merging logic

2. **Data Formatter Tests**
   - Test data formatting for different types
   - Test empty value handling
   - Test complex column processing

3. **View Helper Tests**
   - Test data preparation logic
   - Test CSS class generation
   - Test responsive width calculations

### Integration Testing

1. **Component Integration Tests**
   - Test universal table component rendering
   - Test comment system integration
   - Test responsive behavior

2. **End-to-End Tests**
   - Test complete table rendering flow
   - Test configuration changes
   - Test error handling scenarios

### Browser Testing

1. **PC Responsive Testing**
   - Test different PC screen sizes (1920px, 1366px, 1024px)
   - Test horizontal scrolling behavior
   - Test table layout adjustments

2. **Performance Testing**
   - Test rendering performance with large datasets
   - Test memory usage optimization
   - Test scroll performance

### Test Data

```php
// Test configuration for unit tests
$testConfigs = [
    'minimal_config' => [
        'columns' => [
            ['key' => 'name', 'label' => '名前', 'type' => 'text']
        ]
    ],
    'complex_config' => [
        'columns' => [
            ['key' => 'group', 'label' => 'グループ', 'rowspan_group' => true],
            ['key' => 'date_range', 'label' => '期間', 'type' => 'date_range']
        ],
        'layout' => ['type' => 'grouped_rows']
    ],
    'invalid_config' => [
        'columns' => [] // Should trigger validation error
    ]
];
```

## Implementation Phases

### Phase 1: Core Infrastructure
- Create universal table component
- Implement configuration service
- Set up basic styling system

### Phase 2: Data Processing
- Implement data formatter service
- Create view helper utilities
- Add error handling mechanisms

### Phase 3: Feature Integration
- Integrate comment system
- Add responsive PC support
- Implement complex column features

### Phase 4: Migration & Testing
- Migrate existing tables to new system
- Comprehensive testing
- Performance optimization

## Performance Considerations

### Caching Strategy
- Cache compiled table configurations
- Cache formatted data for complex tables
- Use Laravel's view caching for rendered components

### Optimization Techniques
- Lazy load table data for large datasets
- Use CSS Grid for complex layouts
- Minimize DOM manipulation in JavaScript

### Memory Management
- Limit maximum rows per table
- Implement pagination for large datasets
- Clean up event listeners properly

## Security Considerations

### Data Sanitization
- Sanitize all user input in table data
- Escape HTML content in table cells
- Validate configuration data

### Access Control
- Respect existing authorization policies
- Maintain comment system permissions
- Validate table access permissions

## Migration Strategy

### Backward Compatibility
- Maintain existing table functionality during migration
- Provide configuration mapping for existing tables
- Support gradual migration approach

### Migration Steps
1. Deploy new components alongside existing tables
2. Create configuration files for existing tables
3. Migrate one table type at a time
4. Remove old table implementations after validation