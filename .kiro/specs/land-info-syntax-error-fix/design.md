# Design Document

## Overview

This design addresses the Blade template syntax error in the land information edit page caused by duplicate closing tags and improper conditional structure. The error "syntax error, unexpected token 'endif', expecting end of file" is preventing the page from loading correctly.

## Architecture

### Current Issue Analysis

The problem is in `resources/views/facilities/land-info/edit.blade.php` where there are:

1. **Duplicate Closing Tags**: Two `</x-facility.edit-layout>` tags - one at line ~601 and another after the `@endpush` section
2. **Improper Conditional Structure**: The `@if(!$canEditAny)` conditional has an `@else` clause, but the closing structure is malformed
3. **Script Placement**: The `@push('scripts')` section is placed after a closing tag, causing structural issues

### Root Cause

The template structure currently looks like:
```blade
@if(!$canEditAny)
    @extends('layouts.app')
    <!-- Permission denied content -->
    @endsection
@else
    @push('styles')
        @vite('resources/css/land-info.css')
    @endpush
    <x-facility.edit-layout>
        <!-- Form content -->
    </x-facility.edit-layout>  <!-- First closing tag -->
    
    @push('scripts')
        @vite('resources/js/land-info-new.js')
        <!-- Script content -->
    @endpush
    </x-facility.edit-layout>  <!-- Duplicate closing tag -->
@endif
```

## Components and Interfaces

### 1. Template Structure Fix

The corrected structure should be:

```blade
@if(!$canEditAny)
    @extends('layouts.app')
    @section('title', '土地情報編集 - ' . $facility->facility_name)
    @section('content')
        <!-- Permission denied content -->
    @endsection
@else
    @push('styles')
        @vite('resources/css/land-info.css')
    @endpush
    
    <x-facility.edit-layout>
        <!-- Form content -->
    </x-facility.edit-layout>
    
    @push('scripts')
        @vite('resources/js/land-info-new.js')
        <!-- Script content -->
    @endpush
@endif
```

### 2. Key Changes Required

1. **Remove Duplicate Closing Tag**: Remove the second `</x-facility.edit-layout>` tag that appears after the `@endpush`
2. **Proper Script Placement**: Move the `@push('scripts')` section to be within the `@else` block but after the component closing tag
3. **Clean Conditional Structure**: Ensure the `@if/@else/@endif` structure is properly balanced

### 3. Template Flow

#### For Users Without Edit Permission (`@if(!$canEditAny)`)
- Extends the main app layout
- Shows permission denied message
- Uses standard Laravel layout structure

#### For Users With Edit Permission (`@else`)
- Includes land-info CSS styles
- Uses the facility edit layout component
- Includes land-info JavaScript functionality
- Maintains all existing form functionality

## Data Models

No data model changes are required. This is purely a template syntax fix.

## Error Handling

### Blade Template Parsing
- Fix syntax errors to allow proper template compilation
- Ensure all directives are properly closed
- Maintain backward compatibility with existing functionality

### User Experience
- Users without permissions see appropriate error message
- Users with permissions access the full editing interface
- No functional changes to the form behavior

## Testing Strategy

### Syntax Validation
- Verify Blade template compiles without errors
- Test page loading for both authorized and unauthorized users
- Confirm no console errors related to template parsing

### Functional Testing
- Verify land information form loads correctly for authorized users
- Confirm permission denied page displays for unauthorized users
- Test that all existing JavaScript functionality continues to work

### Integration Testing
- Test the complete user workflow from navigation to form submission
- Verify CSS and JavaScript assets load correctly
- Confirm responsive design and accessibility features remain intact

## Implementation Priority

### Critical (Immediate Fix Required)
1. Remove duplicate `</x-facility.edit-layout>` closing tag
2. Ensure proper `@if/@else/@endif` structure
3. Verify template compiles without syntax errors

### Verification Steps
1. Load the page and confirm no Blade syntax errors
2. Test with both authorized and unauthorized users
3. Verify all existing functionality remains intact

## Security Considerations

- No security changes required
- Existing permission checks remain in place
- User authorization logic is preserved

## Performance Impact

- Minimal performance impact
- Template compilation will be faster without syntax errors
- No changes to runtime performance

## Backward Compatibility

- Full backward compatibility maintained
- No changes to form functionality
- All existing features preserved