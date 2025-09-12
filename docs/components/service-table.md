# Standardized Service Table Component Documentation

## Overview

The Standardized Service Table component is part of the unified table system that displays facility service information using the universal table component architecture. It provides consistent styling, responsive design, and integrated comment functionality across all facility tables.

## Architecture

### Components

1. **Standardized Template**: `resources/views/facilities/services/partials/standardized-table.blade.php`
   - Uses the universal table component system
   - Handles service collection and configuration
   - Integrates with comment functionality

2. **Universal Table Component**: `resources/views/components/universal-table.blade.php`
   - Core table rendering engine
   - Supports multiple layout types including service tables
   - Provides performance optimizations and error handling

3. **Service Table Layout**: `resources/views/components/universal-table/service-table.blade.php`
   - Service-specific table layout implementation
   - Handles rowspan grouping for service types
   - Implements period date formatting

4. **Configuration**: `config/table-config.php`
   - Centralized configuration for all table types
   - Service table specific settings under 'service_info' key
   - Column definitions and styling rules

5. **Services**: 
   - `app/Services/TableConfigService.php` - Configuration management
   - `app/Services/TableDataFormatter.php` - Data formatting
   - `app/Services/TableViewHelper.php` - View preparation

## Usage

### Basic Usage (Recommended)

```blade
@include('facilities.services.partials.standardized-table', [
    'services' => $facility->services
])
```

### Direct Universal Table Usage

```blade
@php
    $tableConfig = app('App\Services\TableConfigService')->getTableConfig('service_info');
    $serviceData = $services->map(function($service) {
        return [
            'service_type' => $service->service_type ?? '',
            'renewal_start_date' => $service->renewal_start_date,
            'period_separator' => '〜',
            'renewal_end_date' => $service->renewal_end_date,
        ];
    })->toArray();
@endphp

<x-universal-table 
    :table-id="'service-info-table'"
    :config="$tableConfig"
    :data="$serviceData"
    :section="'service_info'"
    :comment-enabled="true"
    :responsive="true"
/>
```

## Configuration Options

The service table configuration is defined in `config/table-config.php` under the `service_info` key:

### Column Configuration

```php
'service_info' => [
    'columns' => [
        [
            'key' => 'service_type',
            'label' => 'サービス種類',
            'type' => 'text',
            'rowspan_group' => true
        ],
        [
            'key' => 'renewal_start_date',
            'label' => '有効期限開始',
            'type' => 'date',
            'format' => 'Y年n月j日'
        ],
        [
            'key' => 'period_separator',
            'label' => '',
            'type' => 'text',
            'value' => '〜'
        ],
        [
            'key' => 'renewal_end_date',
            'label' => '有効期限終了',
            'type' => 'date',
            'format' => 'Y年n月j日'
        ]
    ],
    'layout' => [
        'type' => 'service_table',
        'group_by' => 'service_type',
        'service_header_rowspan' => true
    ],
    'styling' => [
        'table_class' => 'service-info',
        'header_class' => 'bg-info text-white',
        'empty_value_class' => 'text-muted'
    ],
    'features' => [
        'comments' => true,
        'sorting' => false,
        'filtering' => false
    ]
]
```

### Global Settings

```php
'global_settings' => [
    'responsive' => [
        'enabled' => true,
        'pc_only' => true,
        'breakpoints' => [
            'lg' => '992px',
            'md' => '768px'
        ]
    ],
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 300
    ]
]
```

## Features

### Responsive Design
- Automatic column width adjustment for mobile devices
- Optimized layout for different screen sizes
- Maintains readability across all devices

### Security
- XSS protection through proper escaping
- Input validation for service data
- Secure handling of user-generated content

### Performance
- Efficient service data preparation
- Minimal template rendering overhead
- Optimized CSS delivery

### Accessibility
- Proper semantic HTML structure
- Screen reader compatible
- Keyboard navigation support

## API Reference

### ServiceTableService Methods

#### `prepareServicesForDisplay(Collection $services): array`
Prepares service collection for table display.

**Parameters:**
- `$services`: Collection of FacilityService models

**Returns:**
- Array with keys: `services`, `hasData`, `templateRowsNeeded`

#### `hasValidServiceData($service): bool`
Validates if service has displayable data.

**Parameters:**
- `$service`: FacilityService model or null

**Returns:**
- Boolean indicating if service has valid data

#### `formatServiceForDisplay($service): array`
Formats service data for display.

**Parameters:**
- `$service`: FacilityService model or null

**Returns:**
- Array with keys: `service_type`, `period`, `has_data`

## Testing

### Unit Tests
- `tests/Unit/Services/ServiceTableServiceTest.php`
- Covers all service formatting and validation logic
- Tests configuration access and CSS generation

### Integration Tests
- Component rendering tests in existing facility test suites
- Comment functionality integration tests
- Responsive design browser tests

## Customization

### Adding New Columns

1. Update configuration in `config/service-table.php`:
```php
'columns' => [
    'new_column' => [
        'label' => 'New Column',
        'width_percentage' => 20,
        'mobile_width_percentage' => 25,
    ],
],
```

2. Update CSS in `resources/css/components/service-table.css`
3. Modify templates to include new column data

### Custom Styling

Override CSS classes in your application's CSS:

```css
.service-info .custom-header {
    background-color: #custom-color;
}
```

### Environment Configuration

Set environment variables to customize behavior:

```env
SERVICE_TABLE_MAX_SERVICES=15
SERVICE_TABLE_SHOW_EMPTY_ROWS=false
SERVICE_TABLE_ENABLE_COMMENTS=true
```

## Troubleshooting

### Common Issues

1. **Services not displaying**: Check that `$services` is a valid Collection
2. **Styling issues**: Ensure CSS is properly loaded via Vite
3. **Configuration not loading**: Verify config cache is cleared
4. **XSS warnings**: Ensure all user data is properly escaped

### Debug Mode

Enable debug mode to see detailed service preparation information:

```php
$serviceTableService = app(\App\Services\ServiceTableService::class);
$debug = $serviceTableService->prepareServicesForDisplay($services);
dd($debug);
```

## Migration Guide

### From Legacy Implementation

1. Replace direct service loops with service table component
2. Update CSS classes to use new naming convention
3. Move configuration to `config/service-table.php`
4. Update tests to use new service methods

### Breaking Changes

- Column width classes changed from inline styles to CSS classes
- Service validation logic moved to ServiceTableService
- Configuration structure updated for better organization