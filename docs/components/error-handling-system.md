# Error Handling System

This document describes the integrated error handling system for facility forms, implemented as part of the facility form layout standardization.

## Overview

The error handling system provides three levels of error display:
1. **Form-level errors** - Summary of all validation errors at the top of the form
2. **Section-level error indicators** - Visual indicators on section headers when fields in that section have errors
3. **Field-level errors** - Individual error messages displayed below each form field

## Components

### 1. Form Errors Component (`x-form.errors`)

Displays a summary of all validation errors at the form level.

**Usage:**
```blade
<x-form.errors />
```

**Features:**
- Automatically displays when validation errors exist
- Shows a dismissible alert with all error messages
- Includes an exclamation triangle icon
- Hidden when no errors are present

### 2. Field Error Component (`x-form.field-error`)

Displays validation errors for individual form fields.

**Usage:**
```blade
<x-form.field-error field="field_name" />
<!-- Or with custom message -->
<x-form.field-error field="field_name" message="Custom error message" />
```

**Features:**
- Automatically retrieves error message for the specified field
- Supports custom error messages
- Uses Bootstrap's `invalid-feedback` styling
- Hidden when no error exists for the field

### 3. Enhanced Form Section Component (`x-form.section`)

Extended to show error indicators when fields within the section have validation errors.

**Usage:**
```blade
<x-form.section 
    title="Basic Information" 
    icon="fas fa-info" 
    :error-fields="['field1', 'field2']">
    <!-- Section content -->
</x-form.section>
```

**Features:**
- Shows warning triangle icon in section header when any specified field has errors
- Includes tooltip text "このセクションにエラーがあります"
- Animated pulse effect to draw attention
- Maintains all existing section functionality

## Error Field Mappings

The `FacilityFormHelper` class provides error field mappings for different form sections:

```php
// Get error fields for a specific section
$errorFields = FacilityFormHelper::getErrorFieldsForSection('basic_info');

// Available sections for land info form:
// - basic_info
// - area_info  
// - owned_property
// - leased_property
// - management_company
// - owner_info
// - documents
// - notes
```

## Implementation Example

Here's how the error handling system is integrated in the land info edit form:

```blade
<x-facility.edit-layout>
    <!-- Form-level errors are automatically displayed by the layout -->
    
    <!-- Section with error indicator -->
    <x-form.section 
        title="基本情報" 
        icon="fas fa-map" 
        icon-color="primary"
        :error-fields="App\Helpers\FacilityFormHelper::getErrorFieldsForSection('basic_info')">
        
        <!-- Form field with error display -->
        <div class="col-md-6 mb-3">
            <label for="ownership_type" class="form-label required">所有形態</label>
            <select name="ownership_type" 
                    class="form-select @error('ownership_type') is-invalid @enderror">
                <!-- Options -->
            </select>
            <x-form.field-error field="ownership_type" />
        </div>
        
    </x-form.section>
</x-facility.edit-layout>
```

## CSS Styling

The error handling system includes custom CSS for visual enhancements:

- **Error indicators**: Animated pulse effect with warning color
- **Section headers**: Hover effects for error indicators
- **Form validation**: Enhanced styling for invalid fields
- **Responsive design**: Mobile-optimized error display

## Testing

The error handling system is covered by comprehensive tests in `ValidationErrorHandlingTest.php`:

- Form-level error display
- Field-level error styling
- Section-level error indicators
- Error field mappings
- Input preservation after validation errors
- File upload validation errors
- Conditional field validation

## Benefits

1. **Consistent UX**: Standardized error display across all facility forms
2. **Better Accessibility**: Clear error indicators and messages
3. **Improved Usability**: Section-level indicators help users quickly identify problem areas
4. **Maintainable**: Reusable components reduce code duplication
5. **Extensible**: Easy to add error handling to new forms

## Future Enhancements

- Real-time validation with JavaScript
- Custom error message templates
- Error grouping by severity
- Internationalization support for error messages