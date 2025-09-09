# Implementation Plan

- [x] 1. Set up view mode session management in FacilityController
  - Add methods to handle view preference storage and retrieval in session
  - Implement setViewMode() method to store user preference via AJAX
  - Implement getViewMode() method to retrieve current preference with 'card' as default
  - Update show() method to pass current view mode to the view
  - _Requirements: 1.5_

- [x] 2. Create view toggle component
  - Create resources/views/facilities/partials/view-toggle.blade.php partial
  - Implement toggle buttons for card and table view modes with proper styling
  - Add Bootstrap button group with active state highlighting
  - Include icons and Japanese labels (カード形式/テーブル形式)
  - _Requirements: 1.1_

- [x] 3. Create table view partial component
- [x] 3.1 Create basic table structure partial
  - Create resources/views/facilities/partials/basic-info-table.blade.php
  - Implement two-column table layout (label/value pairs) as specified
  - Add proper Bootstrap table classes for consistent styling
  - _Requirements: 1.3, 3.1_

- [x] 3.2 Implement data categorization and formatting
  - Group facility data into categories (基本情報、住所・連絡先、開設・建物情報、施設・サービス情報、ステータス情報)
  - Add category headers with visual distinction using Bootstrap styling
  - Implement proper data formatting for different types (text, email, url, date, number, badge)
  - Handle empty values by displaying "未設定"
  - Format dates in Japanese format (Y年m月d日)
  - Add appropriate units for numbers (室、名、年、階)
  - _Requirements: 1.4, 3.2, 3.3, 4.2, 4.4_

- [x] 3.3 Implement service types and approval status badges
  - Add badge formatting for service types using Bootstrap badge classes
  - Implement approval status badges with appropriate colors
  - Ensure all service information from card view is displayed in table format
  - _Requirements: 3.5, 4.3_

- [x] 3.4 Add link formatting for contact information
  - Implement clickable mailto links for email addresses
  - Add clickable external links for website URLs with target="_blank"
  - Maintain consistent link styling with existing application theme
  - _Requirements: 3.4_

- [x] 4. Update main facility show view
  - Modify resources/views/facilities/show.blade.php to include view toggle component
  - Add conditional rendering logic to display either card or table view based on session preference
  - Ensure edit button visibility is maintained in both view modes for authorized users
  - Preserve existing tab navigation and functionality
  - _Requirements: 1.2, 2.1_

- [x] 5. Create CSS styling for table view
  - Create resources/css/pages/facility-table-view.css for table-specific styles
  - Implement responsive design that works on different screen sizes
  - Add proper spacing and typography consistent with Bootstrap theme
  - Style category headers to be visually distinct from data rows
  - Ensure proper contrast and readability for all text elements
  - _Requirements: 3.1, 3.2_

- [x] 6. Implement JavaScript for view toggle functionality
  - Create resources/js/modules/facility-view-toggle.js module
  - Implement AJAX functionality to switch between view modes
  - Handle view mode persistence by calling controller setViewMode method
  - Add smooth transitions between view modes
  - Update toggle button active states when switching views
  - _Requirements: 1.1, 1.5_

- [x] 7. Add controller routes for view mode management
  - Add route for AJAX view mode switching in routes/web.php
  - Ensure proper middleware and authorization for view mode endpoints
  - Test route accessibility and response format
  - _Requirements: 1.5_

- [x] 8. Ensure edit workflow integration
  - Verify edit button functionality works in table view mode
  - Test that view mode preference is maintained after edit operations
  - Ensure seamless transition back to selected view mode after editing
  - _Requirements: 2.2, 2.3_

- [x] 9. Implement comprehensive data parity validation
  - Verify all facility data from card view appears in table view
  - Test that no information is lost when switching between view modes
  - Validate proper formatting of all data types (dates, numbers, badges, links)
  - Ensure service information completeness between both views
  - _Requirements: 4.1_

- [x] 10. Add unit tests for view mode functionality
  - Write tests for FacilityController view mode session management
  - Test setViewMode and getViewMode methods with various inputs
  - Validate session persistence and default fallback behavior
  - Test view mode parameter validation and sanitization
  - _Requirements: 1.5_

- [x] 11. Add feature tests for table view rendering
  - Test complete table view rendering with all data categories
  - Validate proper data formatting for each data type (text, email, url, date, number, badge)
  - Test empty value handling displays "未設定" correctly
  - Verify edit button visibility based on user permissions in table view
  - Test view mode switching and session persistence across requests
  - _Requirements: 1.2, 1.3, 1.4, 2.1, 3.3, 4.1_

- [x] 12. Add browser tests for user interface
  - Test view toggle button interactions and visual feedback
  - Verify responsive design works on different screen sizes
  - Test keyboard navigation and accessibility compliance
  - Validate smooth transitions and user experience flow
  - Test session persistence across browser refresh and navigation
  - _Requirements: 1.1, 1.5_