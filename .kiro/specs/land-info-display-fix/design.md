# Design Document

## Overview

This design addresses display issues in the land information edit page by fixing HTML syntax errors, improving JavaScript functionality, and optimizing conditional section display logic. The design aims to provide users with appropriate section visibility based on ownership type and deliver a smooth operational experience.

## Architecture

### Current Issues

1. **HTML Syntax Errors**: Multiple div tags in Blade templates are not properly closed, causing rendering problems (addresses Requirement 1)
2. **JavaScript Initialization Issues**: ES6 modular approach may not be loading correctly, preventing proper functionality (addresses Requirement 3)
3. **Section Display Logic**: Conditional section show/hide functionality not working as expected when ownership type changes (addresses Requirement 2)
4. **Form Validation**: Hidden section fields are still being validated, causing irrelevant validation errors (addresses Requirement 4)
5. **Styling Consistency**: Improper application of Bootstrap 5.1.3 and responsive design issues affecting user experience (addresses Requirement 5)

### Solution Approach

1. **HTML Template Fixes**: Fix syntax errors in Blade templates and use proper Bootstrap 5.1.3 classes to achieve responsive design without console errors (addresses Requirement 1)
2. **JavaScript Integration**: Integrate existing modular code and leverage ES6 module system to improve error resilience and ensure reliable initialization (addresses Requirement 3)
3. **Section Management**: Improve dynamic display logic based on ownership type (owned/leased) with smooth animated transitions and proper styling (addresses Requirement 2)
4. **Validation System**: Implement dynamic validation rules that only validate visible sections and clear errors when sections are hidden (addresses Requirement 4)
5. **Calculation Features**: Ensure reliable operation of automatic calculation functions for price per tsubo and contract period with proper user feedback (addresses Requirement 3)
6. **User Experience**: Maintain consistent Bootstrap styling and responsive design across all screen sizes (addresses Requirement 5)

## Components and Interfaces

### 1. Blade Template (`resources/views/facilities/land-info/edit.blade.php`)

#### Areas Requiring Fixes
- Missing closing div tags for conditional sections (addresses Requirement 1)
- Proper application of Bootstrap 5.1.3 classes (addresses Requirement 5)
- Addition of accessibility attributes (addresses Requirement 5)
- Ensuring responsive design (addresses Requirement 5)

#### Improvements
```blade
<!-- Before -->
<div id="owned_section" class="conditional-section d-none"
    <x-form.section>
    <!-- Content -->
    </x-form.section>
</div>

<!-- After -->
<div id="owned_section" class="conditional-section d-none" aria-hidden="true">
    <x-form.section>
    <!-- Content -->
    </x-form.section>
</div>
```

#### Section Display Control Design Decisions
- Use `d-none` class to leverage Bootstrap standard display control for consistent behavior
- Improve accessibility with `aria-hidden` attribute to support screen readers
- Add CSS transitions for smooth, non-jarring transition effects (addresses Requirement 2.3, 2.4)
- Ensure proper styling and layout when sections are displayed (addresses Requirement 2.4)

### 2. JavaScript Integration (`resources/js/land-info.js`)

#### Current Structure
- `LandInfoManager` class: Main functionality
- Modular approach: `resources/js/modules/land-info/`
- New integration file: `resources/js/land-info-new.js`

#### Integration Strategy
1. Use existing `land-info.js` as foundation and ensure ES6 modules initialize without errors (addresses Requirement 3.1)
2. Gradually integrate modular components leveraging ES6 module system for better maintainability
3. Add fallback functionality and error handling to improve error resilience (addresses Requirement 3.1)
4. Ensure reliable initialization and operation of calculation features (price per tsubo, contract period) with automatic updates (addresses Requirement 3.2, 3.3)
5. Implement proper validation execution with clear user feedback (addresses Requirement 3.4, 3.5)

#### JavaScript Initialization Design Decisions
- Leverage ES6 module system to improve code maintainability
- Strengthen error resilience with try-catch exception handling
- Guarantee reliable initialization with DOMContentLoaded event

### 3. Section Management System

#### Section Visibility Rules (addresses Requirement 2.1, 2.2)
```javascript
const SECTION_VISIBILITY_RULES = {
  owned_section: ['owned'],           // Show only when "owned" is selected
  leased_section: ['leased'],         // Show only when "leased" is selected
  management_section: ['leased'],     // Show only when "leased" is selected
  owner_section: ['leased'],          // Show only when "leased" is selected
  file_section: ['leased']            // Show only when "leased" is selected
};
```

#### Display Control Flow
1. Detection of ownership type selection (owned/leased) (addresses Requirement 2.1, 2.2)
2. Calculation of section visibility based on display rules
3. Smooth animated show/hide transitions with proper styling (addresses Requirement 2.3, 2.4, 5.2)
4. Field clearing and validation error reset for hidden sections (addresses Requirement 4.3)
5. Dynamic updating of aria-hidden attributes to ensure accessibility
6. Ensure only relevant sections are displayed based on ownership type (addresses Requirement 2.1, 2.2)

#### Section Management Design Decisions
- Predictable behavior through clear display rules
- Smooth transitions leveraging Bootstrap 5.1.3 transition functionality
- Data integrity through clearing of hidden section data

## Data Models

### Form Data Structure
```javascript
{
  // Basic information
  ownership_type: 'owned|leased',
  parking_spaces: number,
  
  // Area information
  site_area_sqm: number,
  site_area_tsubo: number,
  
  // Owned property information (when owned)
  purchase_price: number,
  
  // Leased property information (when leased)
  monthly_rent: number,
  contract_start_date: date,
  contract_end_date: date,
  auto_renewal: 'yes|no',
  
  // Management company information (when leased)
  management_company_*: string,
  
  // Owner information (when leased)
  owner_*: string,
  
  // Notes
  notes: string
}
```

### Validation Rules (addresses Requirement 4.1, 4.2, 4.3, 4.4)
```javascript
const VALIDATION_RULES = {
  owned: {
    required: ['ownership_type'],
    optional: ['purchase_price', 'site_area_sqm', 'site_area_tsubo', 'parking_spaces'],
    validate_only: 'owned_section'  // Only validate owned property fields
  },
  leased: {
    required: ['ownership_type'],
    optional: ['monthly_rent', 'contract_start_date', 'contract_end_date', 'auto_renewal', 'parking_spaces'],
    validate_only: ['leased_section', 'management_section', 'owner_section']  // Only validate leased-related fields
  }
};
```

### Calculation Functions (addresses Requirement 3)
```javascript
const CALCULATION_FUNCTIONS = {
  // Price per tsubo calculation: Purchase price ÷ Site area (tsubo)
  pricePerTsubo: (purchasePrice, siteAreaTsubo) => {
    return siteAreaTsubo > 0 ? Math.round(purchasePrice / siteAreaTsubo) : 0;
  },
  
  // Contract period calculation: End date - Start date
  contractPeriod: (startDate, endDate) => {
    if (!startDate || !endDate) return '';
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return `${diffDays} days`;
  }
};
```

## Error Handling

### HTML Rendering Errors
- Fix syntax errors
- Proper tag closure
- Correct application of Bootstrap classes

### JavaScript Execution Errors
- Exception handling with try-catch statements
- Implementation of fallback functionality
- Debug information through console logging

### Form Validation Errors (addresses Requirement 4.1, 4.2, 4.3, 4.4)
- Apply dynamic validation rules: Only validate visible sections based on ownership type (addresses Requirement 4.1, 4.2)
- Skip validation of hidden fields: Clear validation errors for unrelated fields when ownership type changes (addresses Requirement 4.3)
- User-friendly error messages: Clear error display in Japanese with proper feedback (addresses Requirement 4.4)
- Real-time validation: Immediate feedback during field input
- Form submission validation: Execute validation only for visible fields during form submission (addresses Requirement 4.4)

## Testing Strategy

### Unit Tests
- Section display logic testing: Verify correct sections show/hide based on ownership type (addresses Requirement 2.1, 2.2)
- Calculation function testing: Accuracy of price per tsubo and contract period calculations (addresses Requirement 3.2, 3.3)
- Validation rule testing: Accuracy of dynamic validation for visible sections only (addresses Requirement 4.1, 4.2)
- HTML syntax accuracy testing: Ensure proper HTML rendering without console errors (addresses Requirement 1.1, 1.3)

### Integration Tests
- Form submission flow testing: Overall operation flow verification with proper validation execution (addresses Requirement 3.4, 4.4)
- Ownership type change behavior testing: Behavior during owned/leased switching with smooth transitions (addresses Requirement 2.1, 2.2, 2.3)
- Error handling testing: JavaScript initialization error handling without console errors (addresses Requirement 3.1)
- Responsive design testing: Display verification across different screen sizes (addresses Requirement 5.4)

### Usability Tests
- Operation flow with different ownership types: User experience consistency verification with smooth transitions (addresses Requirement 2.3, 2.4)
- Responsive design verification: Display on mobile, tablet, and desktop (addresses Requirement 5.4)
- Accessibility verification: Screen reader support and keyboard navigation
- Styling consistency: Unified UI with Bootstrap 5.1.3 and consistent visual feedback (addresses Requirement 5.1, 5.2, 5.5)
- Form interaction testing: Ensure proper element positioning and spacing (addresses Requirement 5.3)

## Performance Considerations

### DOM Operation Optimization
- Element caching
- Efficiency through batch processing
- Use of requestAnimationFrame

### Calculation Processing Optimization
- Implementation of debounce processing
- Caching of calculation results
- Avoiding unnecessary recalculations

### Memory Management
- Proper removal of event listeners
- Cache size limitations
- Memory leak prevention

## Security Considerations

### Input Value Validation
- XSS attack prevention
- SQL injection prevention
- File upload restrictions

### CSRF Tokens
- Automatic validation during form submission
- Use of Laravel's standard functionality

### Data Encryption
- Proper handling of sensitive information
- Use of HTTPS

## Implementation Priority

### High Priority
1. HTML syntax error fixes: Ensure proper HTML rendering without console errors (addresses Requirement 1.1, 1.2, 1.3)
2. Basic section display functionality: Correct section visibility based on ownership type (addresses Requirement 2.1, 2.2)
3. JavaScript initialization fixes: Reliable module loading and error-free initialization (addresses Requirement 3.1)

### Medium Priority
1. Calculation feature improvements: Automatic calculation of price per tsubo and contract period (addresses Requirement 3.2, 3.3)
2. Form validation optimization: Dynamic validation rules for visible sections only (addresses Requirement 4.1, 4.2, 4.3, 4.4)
3. Error handling enhancement: User-friendly error display with proper feedback (addresses Requirement 3.5)
4. Smooth transitions and styling: Consistent Bootstrap styling and responsive design (addresses Requirement 5.1, 5.2, 5.3, 5.4, 5.5)

### Low Priority
1. Performance optimization: DOM operation efficiency
2. Accessibility improvements: Enhanced WCAG compliance
3. Additional feature implementation: Support for future feature expansion

## Design Decision Rationale

### Reasons for Adopting Bootstrap 5.1.3
- Consistency with project standard CSS framework
- Reliable implementation of responsive design
- Standard support for accessibility features

### Leveraging ES6 Module System
- Improved code maintainability and reusability
- Clear dependency relationships
- Enhanced testability

### Implementation of Dynamic Validation System
- Improved user experience: Elimination of unnecessary validation errors
- Data integrity assurance: Validation only for displayed fields
- Flexibility: Adaptability to future requirement changes