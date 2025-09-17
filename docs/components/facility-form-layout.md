# Facility Form Layout Components

This document describes the standardized facility form layout components created for consistent form design across the application.

## Overview

The facility form layout standardization system provides reusable Blade components for creating consistent edit forms for facility-related data. The system includes:

- **FacilityEditLayout**: Main layout wrapper with header, breadcrumbs, and form structure
- **FacilityInfoCard**: Displays facility information in a consistent card format
- **FormSection**: Standardized form sections with icons and optional collapsible functionality
- **FormActions**: Consistent action buttons (cancel/save) layout

## Components

### 1. FacilityEditLayout

Main layout component that wraps the entire edit form.

**Usage:**
```blade
<x-facility.edit-layout
    title="土地情報編集"
    :facility="$facility"
    :breadcrumbs="$breadcrumbs"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.land-info.update', $facility)"
    form-method="PUT">
    
    <!-- Form content goes here -->
    <x-form.section title="基本情報" icon="fas fa-map" icon-color="primary">
        <!-- Form fields -->
    </x-form.section>
    
</x-facility.edit-layout>
```

**Props:**
- `title` (string): Page title
- `facility` (Facility): Facility object
- `breadcrumbs` (array): Breadcrumb navigation array
- `backRoute` (string): URL for back button
- `formAction` (string): Form submission URL
- `formMethod` (string, default: 'POST'): HTTP method

### 2. FacilityInfoCard

Displays facility information in a consistent card format.

**Usage:**
```blade
<x-facility.info-card :facility="$facility" :show-type="true" />
```

**Props:**
- `facility` (Facility): Facility object
- `showType` (boolean, default: true): Whether to show facility type badge

### 3. FormSection

Creates standardized form sections with icons and optional collapsible functionality.

**Usage:**
```blade
<x-form.section 
    title="基本情報" 
    icon="fas fa-info-circle" 
    icon-color="primary"
    :collapsible="false">
    
    <div class="row">
        <div class="col-md-6">
            <label for="field1" class="form-label">フィールド1</label>
            <input type="text" class="form-control" id="field1" name="field1">
        </div>
    </div>
    
</x-form.section>
```

**Props:**
- `title` (string): Section title
- `icon` (string): Font Awesome icon class
- `iconColor` (string, default: 'primary'): Icon color theme
- `collapsible` (boolean, default: false): Enable collapsible functionality
- `collapsed` (boolean, default: false): Initial collapsed state

### 4. FormActions

Provides consistent action buttons layout.

**Usage:**
```blade
<x-form.actions 
    :cancel-route="route('facilities.show', $facility)"
    cancel-text="キャンセル"
    submit-text="保存"
    submit-icon="fas fa-save" />
```

**Props:**
- `cancelRoute` (string): Cancel button URL
- `cancelText` (string, default: 'キャンセル'): Cancel button text
- `submitText` (string, default: '保存'): Submit button text
- `submitIcon` (string, default: 'fas fa-save'): Submit button icon

## Configuration

The system uses a configuration file at `config/facility-form.php` for customizing icons, colors, and layout settings.

### Available Icons

```php
'icons' => [
    'basic_info' => 'fas fa-info-circle',
    'land_info' => 'fas fa-map',
    'contact_info' => 'fas fa-phone',
    'building_info' => 'fas fa-building',
    'service_info' => 'fas fa-cogs',
    'area_info' => 'fas fa-ruler-combined',
    'owned_property' => 'fas fa-building',
    'leased_property' => 'fas fa-file-contract',
    'management_company' => 'fas fa-building',
    'owner_info' => 'fas fa-user-tie',
    'documents' => 'fas fa-file-pdf',
]
```

### Color Themes

```php
'colors' => [
    'primary' => 'primary',
    'success' => 'success',
    'info' => 'info',
    'warning' => 'warning',
    'danger' => 'danger',
    'secondary' => 'secondary',
    'dark' => 'dark',
]
```

## JavaScript Functionality

The system includes JavaScript functionality for:

- **Collapsible sections**: Click to expand/collapse form sections
- **Form validation**: Real-time field validation with Japanese formats
- **Responsive features**: Mobile-optimized interactions
- **Accessibility**: Keyboard navigation and screen reader support

The JavaScript module is automatically loaded when the `.facility-edit-layout` class is detected on the page.

## CSS Styling

The components use CSS custom properties for consistent theming:

```css
:root {
  --facility-form-card-shadow: 0 2px 4px rgba(0,0,0,0.1);
  --facility-form-section-border: #e9ecef;
  --facility-form-header-bg: #f8f9fa;
  --facility-form-spacing: 1.5rem;
  --facility-form-border-radius: 0.375rem;
}
```

## Example Implementation

Here's a complete example of how to convert an existing form to use the new components:

**Before:**
```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>土地情報編集</h1>
    
    <form action="{{ route('facilities.land-info.update', $facility) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card">
            <div class="card-header">
                <h5>基本情報</h5>
            </div>
            <div class="card-body">
                <!-- Form fields -->
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="{{ route('facilities.show', $facility) }}" class="btn btn-secondary">戻る</a>
            <button type="submit" class="btn btn-primary">保存</button>
        </div>
    </form>
</div>
@endsection
```

**After:**
```blade
<x-facility.edit-layout
    title="土地情報編集"
    :facility="$facility"
    :breadcrumbs="[
        ['title' => 'ホーム', 'route' => 'facilities.index', 'active' => false],
        ['title' => '施設詳細', 'route' => 'facilities.show', 'params' => [$facility], 'active' => false],
        ['title' => '土地情報編集', 'active' => true]
    ]"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.land-info.update', $facility)"
    form-method="PUT">
    
    <x-form.section title="基本情報" icon="fas fa-map" icon-color="primary">
        <!-- Form fields -->
    </x-form.section>
    
</x-facility.edit-layout>
```

## Benefits

1. **Consistency**: All forms follow the same visual and interaction patterns
2. **Maintainability**: Changes to layout can be made in one place
3. **Accessibility**: Built-in ARIA attributes and keyboard navigation
4. **Responsive**: Mobile-optimized by default
5. **Reusability**: Components can be used across different forms
6. **Validation**: Integrated form validation with Japanese format support

## Migration Guide

To migrate existing forms to use the new components:

1. Replace the `@extends('layouts.app')` with `<x-facility.edit-layout>`
2. Wrap form sections with `<x-form.section>` components
3. Replace manual action buttons with `<x-form.actions>`
4. Update CSS classes to use the new standardized classes
5. Test responsive behavior and accessibility features

## Browser Support

The components support all modern browsers and include fallbacks for:
- CSS Grid and Flexbox
- ES6 JavaScript features
- Touch interactions on mobile devices
- High contrast mode
- Reduced motion preferences