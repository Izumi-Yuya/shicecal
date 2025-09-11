# Facilities Views Directory Restructure Plan

## Current Structure Issues
- Empty subdirectories in partials
- Mixed concerns in main directory
- Service-related partials not properly organized
- No clear separation between different feature areas

## Proposed New Structure

```
resources/views/facilities/
├── index.blade.php                 # Main facility listing
├── show.blade.php                  # Facility detail view
├── create.blade.php                # New facility creation
├── edit.blade.php                  # General facility editing
│
├── basic-info/                     # Basic information views
│   ├── show.blade.php             # Basic info display
│   ├── edit.blade.php             # Basic info editing
│   └── partials/
│       ├── form-fields.blade.php  # Form input fields
│       ├── display-card.blade.php # Display card component
│       └── validation-errors.blade.php
│
├── land-info/                      # Land information views
│   ├── show.blade.php             # Land info display
│   ├── edit.blade.php             # Land info editing
│   └── partials/
│       ├── form-fields.blade.php  # Land info form fields
│       ├── display-card.blade.php # Land info display card
│       ├── ownership-section.blade.php
│       ├── contract-section.blade.php
│       └── financial-section.blade.php
│
├── services/                       # Service period related views
│   ├── index.blade.php            # Service periods listing
│   ├── create.blade.php           # New service period
│   ├── edit.blade.php             # Edit service period
│   └── partials/
│       ├── table.blade.php        # Service table
│       ├── table-header.blade.php # Table header
│       ├── table-row.blade.php    # Table row
│       ├── period-form.blade.php  # Period form fields
│       └── improved-table.blade.php
│
├── shared/                         # Shared components
│   ├── view-toggle.blade.php      # View mode toggle
│   ├── header-card.blade.php      # Facility header card
│   ├── navigation.blade.php       # Facility navigation
│   └── breadcrumbs.blade.php      # Breadcrumb navigation
│
└── components/                     # Reusable UI components
    ├── info-card.blade.php        # Generic info card
    ├── comment-section.blade.php  # Comment functionality
    ├── action-buttons.blade.php   # Action button groups
    └── status-badges.blade.php    # Status indicators
```

## Migration Steps

1. Create new directory structure
2. Move existing files to appropriate locations
3. Update file references in controllers and other views
4. Remove empty directories
5. Update any hardcoded paths in JavaScript/CSS

## Benefits of New Structure

- **Clear separation of concerns**: Each feature area has its own directory
- **Better maintainability**: Related files are grouped together
- **Improved reusability**: Shared components are clearly identified
- **Easier navigation**: Developers can quickly find relevant files
- **Scalability**: Easy to add new feature areas without cluttering