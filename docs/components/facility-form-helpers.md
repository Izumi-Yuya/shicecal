# Facility Form Helpers and Configuration

This document describes the helper functions, Blade directives, and configuration options available for the facility form layout standardization system.

## Configuration File

The main configuration is located in `config/facility-form.php` and includes:

- Layout settings (CSS classes, spacing)
- Section icons (Font Awesome classes)
- Color themes (Bootstrap color classes)
- Form defaults (button text, icons)
- Breadcrumb configuration
- Validation messages

## Helper Functions

### FacilityFormHelper Class

#### generateBreadcrumbs()
```php
// Generate breadcrumbs for any facility form
$breadcrumbs = FacilityFormHelper::generateBreadcrumbs('土地情報編集', $facility);

// With additional breadcrumbs
$additionalCrumbs = [
    ['title' => 'カスタムページ', 'route' => 'custom.page']
];
$breadcrumbs = FacilityFormHelper::generateBreadcrumbs('編集', $facility, $additionalCrumbs);
```

#### getSectionIcon() / getSectionColor()
```php
// Get icon for a section type
$icon = FacilityFormHelper::getSectionIcon('basic_info'); // 'fas fa-info-circle'

// Get color for a section type
$color = FacilityFormHelper::getSectionColor('land_info'); // 'primary'
```

#### getSectionConfig()
```php
// Get complete section configuration
$config = FacilityFormHelper::getSectionConfig('basic_info');
// Returns: ['title' => '基本情報', 'icon' => 'fas fa-info-circle', 'color' => 'primary']

// With custom values
$config = FacilityFormHelper::getSectionConfig('basic_info', 'カスタムタイトル', 'fas fa-custom', 'success');
```

### Global Helper Functions

```php
// Available globally without class prefix
$breadcrumbs = facility_breadcrumbs('編集ページ', $facility);
$icon = section_icon('basic_info');
$color = section_color('land_info');
$config = section_config('documents');
$configValue = facility_form_config('defaults.save_text');
$message = form_validation_message('required');
```

## Blade Directives

### @facilityBreadcrumbs
```blade
{{-- Generate breadcrumbs --}}
@facilityBreadcrumbs('土地情報編集', $facility)

{{-- With additional breadcrumbs --}}
@facilityBreadcrumbs('編集', $facility, [['title' => 'カスタム', 'route' => 'custom']])
```

### @facilitySection
```blade
{{-- Auto-configured section --}}
@facilitySection('basic_info')
    <p>Section content here</p>
@endfacilitySection

{{-- With custom title --}}
@facilitySection('basic_info', 'カスタムタイトル')
    <p>Section content here</p>
@endfacilitySection
```

### @facilityInfoCard
```blade
{{-- Display facility info card --}}
@facilityInfoCard($facility)
```

### @facilityFormActions
```blade
{{-- Default form actions --}}
@facilityFormActions(['cancelRoute' => 'facilities.show', 'cancelText' => 'キャンセル'])
```

### @sectionIcon / @sectionColor
```blade
{{-- Get icon or color inline --}}
<i class="@sectionIcon('basic_info')"></i>
<div class="text-@sectionColor('land_info')">Content</div>
```

## Usage Examples

### Basic Form Layout
```blade
<x-facility.edit-layout 
    :title="'土地情報編集'" 
    :facility="$facility"
    :breadcrumbs="facility_breadcrumbs('土地情報編集', $facility)"
    :back-route="route('facilities.show', $facility)"
    :form-action="route('facilities.land-info.update', $facility)">
    
    <x-form.section 
        :title="section_config('basic_info')['title']"
        :icon="section_config('basic_info')['icon']"
        :icon-color="section_config('basic_info')['color']">
        
        {{-- Form fields here --}}
        
    </x-form.section>
    
    <x-slot name="actions">
        <x-form.actions 
            :cancel-route="route('facilities.show', $facility)"
            :cancel-text="facility_form_config('defaults.cancel_text')"
            :submit-text="facility_form_config('defaults.save_text')" />
    </x-slot>
    
</x-facility.edit-layout>
```

### Using Directives
```blade
{{-- Simplified with directives --}}
@facilityBreadcrumbs('土地情報編集', $facility)

<form action="{{ route('facilities.land-info.update', $facility) }}" method="POST">
    @csrf
    @method('PUT')
    
    @facilityInfoCard($facility)
    
    @facilitySection('basic_info')
        {{-- Form fields --}}
    @endfacilitySection
    
    @facilitySection('area_info')
        {{-- Area form fields --}}
    @endfacilitySection
    
    @facilityFormActions(['cancelRoute' => route('facilities.show', $facility)])
</form>
```

### Custom Section Configuration
```blade
{{-- Custom section with helper --}}
@php
$customConfig = section_config('documents', 'カスタム書類', 'fas fa-file-alt', 'warning');
@endphp

<x-form.section 
    :title="$customConfig['title']"
    :icon="$customConfig['icon']"
    :icon-color="$customConfig['color']">
    
    {{-- Custom content --}}
    
</x-form.section>
```

## Configuration Options

### Available Section Types
- `basic_info` - 基本情報
- `land_info` - 土地情報
- `contact_info` - 連絡先情報
- `building_info` - 建物情報
- `service_info` - サービス情報
- `area_info` - 面積情報
- `owned_property` - 自社物件情報
- `leased_property` - 賃借物件情報
- `management_company` - 管理会社情報
- `owner_info` - オーナー情報
- `documents` - 関連書類

### Available Colors
- `primary`, `success`, `info`, `warning`, `danger`, `secondary`, `dark`

### Validation Rules
Common validation rules are available through:
```php
$rules = FacilityFormHelper::getCommonValidationRules('land_info');
```

Available contexts: `default`, `land_info`, `contact`

## Testing

Helper functions are tested in `tests/Unit/Helpers/FacilityFormHelperTest.php`. Run tests with:

```bash
php artisan test tests/Unit/Helpers/FacilityFormHelperTest.php
```