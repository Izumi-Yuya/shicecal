# Design Document - Facility Basic Info Table View

## Overview

This feature adds a table view option to the existing facility basic information display, providing users with an alternative to the current card-based layout. The table view will offer improved information density and comparison capabilities while maintaining all existing functionality including editing permissions and data integrity.

The design follows a progressive enhancement approach, adding the table view as an optional display mode without disrupting the existing card view functionality. Users can seamlessly switch between views with their preference persisted across sessions.

## Architecture

### Component Structure

```
FacilityBasicInfo/
├── Controllers/
│   └── FacilityController (enhanced)
├── Views/
│   ├── facilities/show.blade.php (enhanced)
│   ├── facilities/partials/
│   │   ├── basic-info-card.blade.php (existing)
│   │   ├── basic-info-table.blade.php (new)
│   │   └── view-toggle.blade.php (new)
├── CSS/
│   └── facility-table-view.css (new)
└── JavaScript/
    └── view-toggle.js (new)
```

### Data Flow

1. **View Selection**: User selects display mode via toggle buttons
2. **Session Storage**: Selected view preference stored in session
3. **Conditional Rendering**: Blade template renders appropriate partial based on session preference
4. **State Persistence**: View preference maintained across page refreshes and navigation

## Components and Interfaces

### 1. View Toggle Component

**Purpose**: Provides UI controls for switching between card and table views

**Interface**:
```php
// Session key for storing view preference
const VIEW_PREFERENCE_KEY = 'facility_basic_info_view_mode';

// View modes
const VIEW_MODES = [
    'card' => 'カード形式',
    'table' => 'テーブル形式'
];
```

**Responsibilities**:
- Render toggle buttons with current selection highlighted
- Handle view mode switching via AJAX or form submission
- Update session storage with user preference

### 2. Table View Component

**Purpose**: Renders facility basic information in structured table format

**Interface**:
```php
// Table structure configuration
const TABLE_CATEGORIES = [
    'basic' => '基本情報',
    'contact' => '住所・連絡先',
    'building' => '開設・建物情報', 
    'service' => '施設・サービス情報',
    'status' => 'ステータス情報'
];

// Data type formatting configuration
const DATA_TYPE_FORMATTERS = [
    'text' => 'Standard text display',
    'email' => 'Clickable mailto links',
    'url' => 'Clickable external links with target="_blank"',
    'date' => 'Japanese format (Y年m月d日)',
    'number' => 'With appropriate units (室、名、年、階)',
    'badge' => 'Bootstrap badge styling for service types and approval status',
    'empty' => 'Display as "未設定"'
];
```

**Responsibilities**:
- Group facility data by logical categories with visual separation
- Render two-column table (label/value pairs) as specified in requirements
- Apply appropriate formatting for different data types including badges for service types
- Handle empty/null values with "未設定" display
- Maintain consistent styling with existing Bootstrap theme
- Display category headers with visual distinction
- Ensure all facility data from card view is included

### 3. Enhanced Facility Controller

**Purpose**: Handle view preference management and data preparation

**New Methods**:
```php
public function setViewMode(Request $request): JsonResponse
public function getViewMode(): string
private function prepareTableData(Facility $facility): array
private function formatDataForTable($value, string $type): array
```

**Responsibilities**:
- Store and retrieve view preferences from session with persistence across requests
- Prepare facility data for table rendering with proper categorization
- Format data according to type (text, email, url, date, number, badge)
- Maintain view mode selection after edit operations (Requirement 2.3)
- Ensure edit button visibility based on user permissions in both view modes
- Maintain backward compatibility with existing functionality

## Data Models

### Session Data Structure

```php
// Session storage for view preference
session([
    'facility_basic_info_view_mode' => 'table|card'
]);
```

### Table Data Structure

```php
// Categorized facility data for table rendering
[
    'basic' => [
        ['label' => '施設名', 'value' => $facility->name, 'type' => 'text'],
        ['label' => '施設番号', 'value' => $facility->facility_number, 'type' => 'text'],
        ['label' => '施設種別', 'value' => $facility->facility_type, 'type' => 'text'],
        // ... all basic information fields
    ],
    'contact' => [
        ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
        ['label' => 'ウェブサイト', 'value' => $facility->website, 'type' => 'url'],
        ['label' => '郵便番号', 'value' => $facility->postal_code, 'type' => 'text'],
        ['label' => '住所', 'value' => $facility->address, 'type' => 'text'],
        // ... all contact information fields
    ],
    'building' => [
        ['label' => '開設年月日', 'value' => $facility->opening_date, 'type' => 'date'],
        ['label' => '建物構造', 'value' => $facility->building_structure, 'type' => 'text'],
        ['label' => '階数', 'value' => $facility->floors, 'type' => 'number', 'unit' => '階'],
        // ... all building information fields
    ],
    'service' => [
        ['label' => 'サービス種類', 'value' => $facility->service_types, 'type' => 'badge'],
        ['label' => '定員', 'value' => $facility->capacity, 'type' => 'number', 'unit' => '名'],
        ['label' => '居室数', 'value' => $facility->room_count, 'type' => 'number', 'unit' => '室'],
        // ... all service information fields
    ],
    'status' => [
        ['label' => '承認状況', 'value' => $facility->approval_status, 'type' => 'badge'],
        ['label' => '更新日時', 'value' => $facility->updated_at, 'type' => 'date'],
        // ... all status information fields
    ]
]
```

### Data Type Handling

- **Text**: Standard text display
- **Email**: Clickable mailto links
- **URL**: Clickable external links  
- **Date**: Japanese format (Y年m月d日)
- **Number**: With appropriate units (室、名、年、階)
- **Badge**: Service types and approval status
- **Empty**: Display as "未設定"

## Error Handling

### View Mode Validation

```php
// Validate view mode parameter
if (!in_array($viewMode, ['card', 'table'])) {
    $viewMode = 'card'; // Default fallback
}
```

### Session Handling

- Graceful fallback to card view if session data is corrupted
- No impact on functionality if JavaScript is disabled
- Progressive enhancement approach ensures basic functionality remains

### Data Rendering

- Safe handling of null/empty facility data
- Proper escaping of user-generated content
- Fallback display for missing or invalid data

## Testing Strategy

### Unit Tests

1. **View Mode Management**
   - Test session storage and retrieval (Requirement 1.5)
   - Validate view mode parameter handling
   - Test default fallback behavior to card view

2. **Data Preparation**
   - Test table data structure generation with all categories
   - Validate data type formatting (text, email, url, date, number, badge)
   - Test handling of null/empty values displaying as "未設定" (Requirement 3.3)
   - Test Japanese date formatting (Y年m月d日) (Requirement 4.2)
   - Test number formatting with units (室、名、年、階) (Requirement 4.4)

### Feature Tests

1. **View Toggle Functionality**
   - Test switching between card and table views (Requirement 1.1, 1.2)
   - Verify session persistence across requests (Requirement 1.5)
   - Test edit button functionality in both views (Requirement 2.1, 2.2)
   - Test view mode retention after edit operations (Requirement 2.3)

2. **Table Rendering**
   - Verify all facility data from card view is displayed (Requirement 4.1)
   - Test proper categorization of information (Requirement 1.4)
   - Validate two-column table structure (Requirement 1.3)
   - Test category header visual distinction (Requirement 3.2)
   - Test Bootstrap theme consistency (Requirement 3.1)
   - Test link formatting for email and URL fields (Requirement 3.4)
   - Test badge formatting for service types and approval status (Requirement 3.5, 4.3)

3. **Permission Integration**
   - Test edit button visibility based on user permissions in table view
   - Verify consistent behavior across view modes
   - Test approval workflow integration

### Browser Tests

1. **User Interface**
   - Test toggle button interactions with proper highlighting
   - Verify responsive design on different screen sizes
   - Test accessibility compliance for table structure
   - Test keyboard navigation for toggle buttons

2. **Session Persistence**
   - Test view preference retention across browser sessions
   - Verify behavior with disabled cookies/sessions
   - Test preference persistence after login/logout cycles

## Design Decisions and Rationales

### 1. Session-Based Preference Storage

**Decision**: Store view preference in server-side session rather than client-side localStorage

**Rationale**: 
- Ensures preference is available immediately on page load
- Works consistently across different browsers and devices
- Integrates naturally with Laravel's session management
- No additional client-side complexity

### 2. Two-Column Table Layout

**Decision**: Use simple two-column table (label/value) rather than multi-column grid

**Rationale**:
- Maintains readability on mobile devices
- Easier to implement responsive design
- Consistent with Japanese business application conventions
- Allows for variable-length content without layout issues

### 3. Category-Based Grouping

**Decision**: Group related information into logical categories with visual separation

**Rationale**:
- Improves information organization and findability
- Reduces cognitive load when scanning large amounts of data
- Maintains consistency with existing card view structure
- Allows for future expansion of categories

### 4. Progressive Enhancement Approach

**Decision**: Implement as enhancement to existing functionality rather than replacement

**Rationale**:
- Minimizes risk of breaking existing workflows
- Allows gradual user adoption
- Maintains backward compatibility
- Reduces testing and deployment complexity

### 5. Bootstrap Integration

**Decision**: Leverage existing Bootstrap classes and theme rather than custom CSS framework

**Rationale**:
- Maintains visual consistency with rest of application (Requirement 3.1)
- Reduces CSS bundle size and complexity
- Leverages proven responsive design patterns
- Easier maintenance and updates
- Provides consistent badge styling for service types and approval status

### 6. Complete Data Parity

**Decision**: Ensure table view displays exactly the same information as card view

**Rationale**:
- Meets requirement for no information loss between view modes (Requirement 4.1)
- Maintains user trust and consistency
- Prevents confusion when switching between views
- Ensures all business-critical information remains accessible

### 7. Japanese Localization

**Decision**: Implement proper Japanese formatting for dates, numbers, and text

**Rationale**:
- Aligns with Japanese business application standards (Requirement 4.2)
- Provides familiar user experience for Japanese users
- Maintains consistency with existing application localization
- Supports proper display of Japanese characters and formatting

This design ensures the table view feature integrates seamlessly with the existing facility management system while providing the enhanced information density and comparison capabilities requested in the requirements. All acceptance criteria from the requirements document are addressed through specific design decisions and implementation details.