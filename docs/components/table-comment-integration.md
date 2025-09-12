# Table Comment Integration

This document describes how to use the comment-enabled table wrapper component that integrates the standardized table system with the facility comment system.

## Overview

The table comment integration provides a unified way to display facility data in standardized tables with integrated comment functionality. Users can view, add, and manage comments for specific table sections directly from the table interface.

## Components

### 1. Table Comment Wrapper (`table-comment-wrapper.blade.php`)

The main wrapper component that combines a standardized table with comment functionality.

**Props:**
- `tableId` (optional): Unique identifier for the table
- `config`: Table configuration array
- `data`: Data to display in the table
- `section`: Comment section identifier (e.g., 'basic_info', 'service_info', 'land_info')
- `facility`: Facility model instance
- `commentEnabled` (default: true): Whether to show comment functionality
- `responsive` (default: true): Whether to enable responsive behavior

### 2. Table Comment Section (`table-comment-section.blade.php`)

The reusable comment section component that handles comment display and interaction.

**Props:**
- `section`: Comment section identifier
- `displayName`: Human-readable section name
- `facility`: Facility model instance
- `comments` (optional): Pre-loaded comments collection

## Usage Examples

### Basic Usage

```blade
<x-table-comment-wrapper 
    :config="$basicInfoConfig"
    :data="$facilityData"
    section="basic_info"
    :facility="$facility"
/>
```

### With Custom Configuration

```blade
@php
$config = [
    'comment_display_name' => '基本情報',
    'columns' => [
        [
            'key' => 'facility_name',
            'label' => '施設名',
            'type' => 'text',
            'required' => true
        ],
        [
            'key' => 'address',
            'label' => '住所',
            'type' => 'text',
            'required' => false
        ]
    ],
    'layout' => [
        'type' => 'key_value_pairs',
        'columns_per_row' => 2
    ],
    'features' => [
        'comments' => true
    ]
];

$data = [
    'facility_name' => $facility->facility_name,
    'address' => $facility->address
];
@endphp

<x-table-comment-wrapper 
    :config="$config"
    :data="$data"
    section="basic_info"
    :facility="$facility"
    table-id="facility-basic-info"
/>
```

### Disabling Comments

```blade
<x-table-comment-wrapper 
    :config="$config"
    :data="$data"
    section="basic_info"
    :facility="$facility"
    :comment-enabled="false"
/>
```

### Using Comment Section Independently

```blade
<x-table-comment-section 
    section="basic_info"
    display-name="基本情報"
    :facility="$facility"
/>
```

## Configuration

### Table Configuration

The table configuration should include comment settings:

```php
// config/table-config.php
'basic_info' => [
    'comment_display_name' => '基本情報',
    'columns' => [
        // ... column definitions
    ],
    'layout' => [
        'type' => 'key_value_pairs'
    ],
    'features' => [
        'comments' => true
    ]
]
```

### Comment Sections

Supported comment sections are defined in the table configuration:

```php
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
```

## Features

### Comment Toggle

- Click the comment button to show/hide the comment section
- Comment count badge shows the number of comments for the section
- Smooth animation when expanding/collapsing

### Comment Management

- Add new comments with priority levels (normal, high, urgent)
- View existing comments with user information and timestamps
- Delete comments (for comment owners and admins)
- Real-time comment posting via AJAX

### Responsive Design

- PC-optimized responsive behavior
- Horizontal scrolling for wide tables
- Mobile-friendly comment interface

## JavaScript API

### Global Functions

```javascript
// Update comment count for a section
TableCommentWrapper.updateCommentCount('basic_info', 5);

// Show comment section programmatically
TableCommentWrapper.showCommentSection('basic_info');
```

### Events

The component automatically handles:
- Comment form submission
- Comment deletion
- Comment section toggle
- Form validation

## Styling

### CSS Classes

- `.table-comment-wrapper`: Main wrapper container
- `.table-header`: Table header with comment controls
- `.comment-toggle`: Comment toggle button
- `.comment-count`: Comment count badge
- `.comment-section`: Comment section container
- `.comment-item`: Individual comment item
- `.comment-form`: Comment input form

### Customization

Override default styles by targeting the component classes:

```css
.table-comment-wrapper .comment-toggle {
    background-color: #custom-color;
}

.comment-item {
    border-left-color: #custom-accent;
}
```

## API Endpoints

The component uses the existing facility comment API:

- `POST /facilities/{facility}/comments` - Create comment
- `DELETE /facilities/{facility}/comments/{comment}` - Delete comment
- `GET /facilities/{facility}/comments/{section}` - Get section comments

## Requirements Fulfilled

This implementation fulfills the following requirements from the specification:

- **3.1**: Comment functionality integrated with standardized tables
- **3.2**: Comment toggle buttons and count badges
- **3.3**: Real-time comment posting and display
- **3.4**: Comment section integration with existing comment system
- **3.5**: Comment section expand/collapse state management

## Testing

Comprehensive tests are available in `tests/Feature/TableCommentIntegrationTest.php` covering:

- Component rendering
- Comment display
- User permissions
- Section handling
- Form functionality

Run tests with:
```bash
php artisan test tests/Feature/TableCommentIntegrationTest.php
```