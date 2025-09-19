# Common Table Card Wrapper Implementation

## Overview

The CardWrapper component is a reusable Blade component that provides consistent card structure for the Common Table Layout system. It integrates with existing `facility-info-card` CSS classes and provides accessibility features.

## Component Location

```
resources/views/components/common-table/card-wrapper.blade.php
```

## Features

### 1. Card Structure
- Provides consistent card wrapper with optional header
- Integrates with existing `facility-info-card` and `detail-card-improved` classes
- Supports custom CSS classes for card and header

### 2. Title and Header Management
- Optional card title with configurable HTML tag (default: h5)
- Configurable header display (can be hidden)
- Custom header CSS classes

### 3. Accessibility Features
- Automatic ARIA attributes (`role="region"`)
- Unique ID generation for title-card association
- Support for custom ARIA labels
- Proper `aria-labelledby` relationships

### 4. Customization Options
- Custom card and header CSS classes
- Additional HTML attributes for card and header elements
- Configurable title tag and classes

## Usage

### Basic Usage
```blade
<x-common-table.card-wrapper title="基本情報">
    <div class="card-body">
        <!-- Table content -->
    </div>
</x-common-table.card-wrapper>
```

### Advanced Usage
```blade
<x-common-table.card-wrapper 
    title="カスタムタイトル"
    cardClass="custom-card-class"
    headerClass="custom-header-class"
    titleTag="h3"
    titleClass="custom-title-class"
    ariaLabel="カスタムアクセシビリティラベル"
    :cardAttributes="['data-section' => 'basic-info']"
    :headerAttributes="['data-header' => 'custom']">
    
    <div class="card-body card-body-clean">
        <!-- Table content -->
    </div>
</x-common-table.card-wrapper>
```

### Without Header
```blade
<x-common-table.card-wrapper :showHeader="false">
    <div class="card-body">
        <!-- Table content -->
    </div>
</x-common-table.card-wrapper>
```

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `title` | string\|null | null | Card title text |
| `cardClass` | string | 'facility-info-card detail-card-improved mb-3' | CSS classes for card element |
| `headerClass` | string | 'card-header' | CSS classes for header element |
| `showHeader` | boolean | true | Whether to display the header |
| `headerAttributes` | array | [] | Additional HTML attributes for header |
| `cardAttributes` | array | [] | Additional HTML attributes for card |
| `titleTag` | string | 'h5' | HTML tag for title element |
| `titleClass` | string | 'card-title mb-0' | CSS classes for title element |
| `ariaLabel` | string\|null | null | ARIA label for accessibility |

## Integration with Common Table

The CardWrapper is automatically used by the main CommonTable component when a title is provided:

```blade
<x-common-table :data="$tableData" title="基本情報" />
```

This automatically wraps the table in the CardWrapper component.

## CSS Classes Integration

### Default Classes
- `facility-info-card`: Main card styling (existing class)
- `detail-card-improved`: Enhanced card styling (existing class)
- `mb-3`: Bootstrap margin bottom
- `card-header`: Bootstrap card header
- `card-title mb-0`: Bootstrap card title with no margin

### Existing CSS Integration
The component integrates with existing CSS classes defined in:
- `resources/css/detail-table-clean.css`
- Bootstrap 5.1.3 classes
- Existing facility display card patterns

## Accessibility Features

### ARIA Attributes
- `role="region"`: Identifies the card as a landmark region
- `aria-label`: Custom accessibility label
- `aria-labelledby`: Links card to its title for screen readers

### Unique ID Generation
- Automatic unique ID generation for title elements
- Proper association between card and title elements
- Prevents ID conflicts when multiple cards are present

### Keyboard Navigation
- Proper focus management
- Screen reader compatibility
- Semantic HTML structure

## Testing

### Unit Tests
- Component rendering with and without title
- CSS class application
- Accessibility attribute handling
- Custom attribute support

### Integration Tests
- Integration with CommonTable component
- Complex data handling
- Multiple card scenarios

### Manual Testing
- Browser compatibility testing
- Screen reader testing
- Keyboard navigation testing

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Screen reader compatibility
- High contrast mode support

## Performance Considerations

- Lightweight component with minimal overhead
- Efficient unique ID generation
- No JavaScript dependencies
- Optimized for server-side rendering

## Migration from Existing Cards

To migrate existing card structures to use CardWrapper:

1. Replace existing card wrapper HTML:
```blade
<!-- Old -->
<div class="card facility-info-card detail-card-improved mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">タイトル</h5>
    </div>
    <!-- content -->
</div>

<!-- New -->
<x-common-table.card-wrapper title="タイトル">
    <!-- content -->
</x-common-table.card-wrapper>
```

2. Maintain existing functionality while gaining:
   - Consistent accessibility features
   - Automatic unique ID generation
   - Standardized structure
   - Better maintainability

## Future Enhancements

- Support for card actions/buttons in header
- Collapsible card functionality
- Enhanced theming options
- Additional accessibility features