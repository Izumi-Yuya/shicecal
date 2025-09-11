# Implementation Plan

- [x] 1. Fix HTML structure and Bootstrap integration
  - Fix unclosed div tags in Blade template
  - Apply proper Bootstrap 5.1.3 classes for responsive design
  - Add accessibility attributes (aria-hidden, aria-expanded)
  - Ensure proper semantic HTML structure
  - _Requirements: 1.1, 5.1, 5.2_

- [x] 2. Implement dynamic section visibility system
- [x] 2.1 Create section visibility rules and logic
  - Define SECTION_VISIBILITY_RULES constant for owned/leased sections
  - Implement section show/hide logic based on ownership type selection
  - Add smooth Bootstrap transitions for section changes
  - _Requirements: 2.1, 2.2_

- [x] 2.2 Integrate section management with form interactions
  - Connect ownership type radio buttons to section visibility
  - Clear hidden section data when sections are hidden
  - Reset validation errors for hidden sections
  - Set disabled attribute on all inputs in hidden sections to prevent submission
  - Remove is-invalid classes from hidden section fields
  - Ensure hidden fields are never included in form submission payload
  - _Requirements: 2.1, 2.2, 4.2_

- [x] 3. Enhance JavaScript functionality and calculations
- [x] 3.1 Fix JavaScript initialization and module integration
  - Ensure reliable ES6 module loading and initialization
  - Add error handling with try-catch blocks
  - Implement fallback functionality for module loading failures
  - Handle initial state on DOMContentLoaded based on old() or existing values
  - Synchronize aria-hidden and aria-expanded attributes on page load
  - _Requirements: 3.1, 3.2_

- [x] 3.2 Implement automatic calculation features
  - Create price per tsubo calculation (purchase_price / site_area_tsubo)
  - Implement contract period calculation (end_date - start_date) with edge case handling
  - Handle invalid date ranges (end date earlier than start date) with real-time errors
  - Add real-time calculation updates on field changes
  - Consider flexible formatting (years/months/days) for contract period display
  - _Requirements: 3.3_

- [x] 4. Implement dynamic form validation system
- [x] 4.1 Create conditional validation rules
  - Implement validation that only applies to visible sections
  - Define explicit required fields per ownership type:
    - owned: purchase_price and site_area_tsubo (or site_area_sqm)
    - leased: monthly_rent, contract_start_date, and contract_end_date
  - Skip validation for hidden section fields
  - Update FormRequest validation rules to enforce ownership-specific requirements
  - _Requirements: 4.1, 4.2_

- [x] 4.2 Add real-time validation feedback
  - Implement field-level validation on input events
  - Display user-friendly error messages in Japanese
  - Clear validation errors when sections become hidden
  - _Requirements: 4.1, 4.2_

- [x] 5. Apply consistent Bootstrap styling and responsive design
- [x] 5.1 Implement responsive layout with Bootstrap 5.1.3
  - Apply proper Bootstrap grid system and utility classes
  - Ensure mobile-first responsive design
  - Test layout across different screen sizes
  - _Requirements: 5.1, 5.2_

- [x] 5.2 Add smooth transitions and animations
  - Use Bootstrap .collapse + .show as standard method for animated section transitions
  - Use d-none only when absolutely necessary for immediate hiding
  - Keep aria-expanded and aria-hidden attributes synchronized during transitions
  - Add CSS transitions for smooth user experience
  - Ensure accessibility compliance with transition timing
  - _Requirements: 5.2_

- [x] 6. Create comprehensive test suite
- [x] 6.1 Write unit tests for core functionality
  - Test section visibility logic with different ownership types
  - Test calculation functions for accuracy and edge cases (including invalid date ranges)
  - Test validation rules for both owned and leased scenarios with explicit required fields
  - Verify that hidden fields are never included in form submission payload
  - Test that disabled attribute is properly set on hidden section inputs
  - _Requirements: 1.1, 2.1, 3.3, 4.1_

- [x] 6.2 Write integration tests for user workflows
  - Test complete form submission flow for both ownership types
  - Test ownership type switching behavior and data clearing
  - Test responsive design across different viewport sizes
  - Add E2E tests for page reload with pre-filled data or after validation errors
  - Test initial state handling based on old() values or existing data
  - Test accessibility attribute synchronization during transitions
  - _Requirements: 2.2, 4.2, 5.1_

- [x] 7. Integrate and finalize all components
  - Ensure all JavaScript modules work together seamlessly
  - Verify HTML structure is valid and accessible
  - Test complete user workflow from page load to form submission
  - Perform cross-browser compatibility testing
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1_

- [x] 8. Add code quality and CI integration
  - Add HTML/Blade linting checks to catch syntax errors
  - Integrate ESLint checks for JavaScript code quality
  - Add vite build step to CI pipeline to catch build issues
  - Configure CI to fail on HTML/Blade or JavaScript syntax issues
  - Ensure all linting and build checks pass before deployment
  - _Requirements: 1.1, 1.2, 1.3_