# Service Table Component Documentation

## Overview

The Service Table component displays facility service information in a structured table format with support for comments, responsive design, and dynamic content management.

## Architecture

### Components

1. **Main Template**: `resources/views/facilities/partials/service-table.blade.php`
   - Primary component entry point
   - Handles service collection and configuration
   - Includes comment functionality

2. **Row Template**: `resources/views/facilities/partials/service-table-row.blade.php`
   - Individual table row rendering
   - Handles first row headers and subsequent data rows
   - Implements security measures (XSS protection)

3. **Service Class**: `app/Services/ServiceTableService.php`
   - Business logic for service table operations
   - Data formatting and validation
   - Configuration management

4. **Configuration**: `config/service-table.php`
   - Centralized configuration for display options
   - Column definitions and styling
   - Validation rules

5. **Styles**: `resources/css/components/service-table.css`
   - Component-specific styling
   - Responsive design rules
   - Bootstrap integration

## Usage

### Basic Usage

```blade
@include('facilities.partials.service-table', [
    'services' => $facility->services
])
```

### With Custom Configuration

```blade
@php
    $serviceTableService = app(\App\Services\ServiceTableService::class);
    $preparedData = $serviceTableService->prepareServicesForDisplay($facility->services);
@endphp

@include('facilities.partials.service-table', [
    'services' => $preparedData['services']
])
```

## Configuration Options

### Display Settings

```php
'display' => [
    'max_services' => 10,           // Maximum services to display
    'show_empty_rows' => true,      // Show template rows for empty services
    'enable_comments' => true,      // Enable comment functionality
],
```

### Column Configuration

```php
'columns' => [
    'service_type' => [
        'label' => 'サービス種類',
        'width_percentage' => 15,
        'mobile_width_percentage' => 20,
    ],
    // ... other columns
],
```

### Styling Options

```php
'styling' => [
    'header_bg_class' => 'bg-info',
    'header_text_class' => 'text-white',
    'empty_value_class' => 'text-muted',
    'empty_value_text' => '未設定',
],
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