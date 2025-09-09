# Implementation Plan

- [x] 1. Set up refactoring foundation and backup current state
  - Create feature branch for refactoring work
  - Create database backup before starting changes
  - Document current controller and service structure for reference
  - _Requirements: 1.1, 2.1, 3.1_

- [x] 2. Create shared error handling and utility traits
  - [x] 2.1 Implement HandlesControllerErrors trait
    - Write trait with handleException and getErrorCode methods
    - Include logging and JSON/redirect response handling
    - _Requirements: 5.1, 5.2_
  
  - [x] 2.2 Implement HandlesServiceErrors trait
    - Write trait with logError and throwServiceException methods
    - Create custom service exception classes
    - _Requirements: 5.1, 5.2_
  
  - [x] 2.3 Create shared test traits
    - Write CreatesTestFacilities trait for test data
    - Write CreatesTestUsers trait for user creation
    - _Requirements: 7.1, 7.3_

- [x] 3. Merge LandInfoController into FacilityController
  - [x] 3.1 Copy land info methods to FacilityController
    - Move showLandInfo, editLandInfo, updateLandInfo methods
    - Move calculateLandFields, approveLandInfo, rejectLandInfo methods
    - Move document management methods (upload, download, delete)
    - _Requirements: 1.1, 1.3_
  
  - [x] 3.2 Update route definitions for land info
    - Modify routes/web.php to point to FacilityController
    - Maintain backward compatibility with route aliases
    - Test all land info routes work correctly
    - _Requirements: 3.1, 3.3_
  
  - [x] 3.3 Update land info views and forms
    - Update form action URLs in Blade templates
    - Verify all land info functionality works through FacilityController
    - _Requirements: 1.3, 4.1_

- [x] 4. Create FacilityService by merging land info services
  - [x] 4.1 Create new FacilityService class
    - Implement basic facility CRUD operations
    - Move land info operations from LandInfoService
    - Move calculation methods from LandCalculationService
    - _Requirements: 2.1, 2.3_
  
  - [x] 4.2 Update FacilityController to use FacilityService
    - Replace LandInfoService dependency with FacilityService
    - Update all method calls to use new service structure
    - _Requirements: 2.1, 2.3_
  
  - [x] 4.3 Write comprehensive tests for FacilityService
    - Create FacilityServiceTest combining existing land info tests
    - Test all facility and land info operations
    - Verify calculation methods work correctly
    - _Requirements: 7.1, 7.2_

- [x] 5. Merge comment controllers into unified CommentController
  - [x] 5.1 Move FacilityCommentController methods to CommentController
    - Consolidate all comment-related functionality
    - Ensure facility-specific comments work correctly
    - _Requirements: 1.1, 1.3_
  
  - [x] 5.2 Update comment routes and views
    - Simplify comment route structure
    - Update Blade templates to use unified controller
    - _Requirements: 3.1, 4.1_
  
  - [x] 5.3 Test unified comment functionality
    - Verify all comment features work correctly
    - Test comment assignment and status updates
    - _Requirements: 1.3, 7.1_

- [x] 6. Create ExportController by merging PDF and CSV controllers
  - [x] 6.1 Create new ExportController class
    - Move PDF export methods from PdfExportController
    - Move CSV export methods from CsvExportController
    - Move favorites functionality from CsvExportController
    - _Requirements: 1.1, 1.3_
  
  - [x] 6.2 Update export routes structure
    - Consolidate export routes under /export prefix
    - Maintain separate PDF and CSV endpoints
    - Update route names for consistency
    - _Requirements: 3.1, 3.3_
  
  - [x] 6.3 Test all export functionality
    - Verify PDF generation works correctly
    - Test CSV export with field selection
    - Verify favorites save/load functionality
    - _Requirements: 1.3, 7.1_

- [x] 7. Create ExportService by merging export-related services
  - [x] 7.1 Create new ExportService class
    - Merge SecurePdfService and BatchPdfService functionality
    - Include file management operations from FileService
    - Implement CSV generation logic
    - _Requirements: 2.1, 2.3_
  
  - [x] 7.2 Update ExportController to use ExportService
    - Replace multiple service dependencies with single ExportService
    - Update all export method calls
    - _Requirements: 2.1, 2.3_
  
  - [x] 7.3 Write comprehensive tests for ExportService
    - Test PDF generation functionality
    - Test CSV export with various field combinations
    - Test file upload/download operations
    - _Requirements: 7.1, 7.2_

- [x] 8. Extract inline CSS from Blade templates
  - [x] 8.1 Create feature-specific CSS files
    - Extract styles from facilities/show.blade.php to resources/css/pages/facilities.css
    - Extract styles from notifications/index.blade.php to resources/css/pages/notifications.css
    - Extract styles from export/csv/index.blade.php to resources/css/pages/export.css
    - _Requirements: 10.1, 10.2_
  
  - [x] 8.2 Create shared CSS architecture
    - Create resources/css/shared/variables.css for CSS custom properties
    - Create resources/css/shared/components.css for reusable components
    - Create resources/css/shared/utilities.css for utility classes
    - _Requirements: 10.2, 10.5_
  
  - [x] 8.3 Update Vite configuration for CSS compilation
    - Add new CSS files to vite.config.js
    - Configure CSS processing and minification
    - Test CSS compilation and loading
    - _Requirements: 10.2, 10.4_
  
  - [x] 8.4 Remove inline styles from Blade templates
    - Replace @push('styles') sections with @vite directives
    - Remove <style> tags from all Blade files
    - Verify styling remains consistent after extraction
    - _Requirements: 10.1, 10.4_

- [x] 9. Extract inline JavaScript from Blade templates
  - [x] 9.1 Create feature-specific JavaScript modules
    - Extract JS from facilities/show.blade.php to resources/js/modules/facilities.js
    - Extract JS from notifications/index.blade.php to resources/js/modules/notifications.js
    - Extract JS from export/csv/index.blade.php to resources/js/modules/export.js
    - _Requirements: 10.1, 10.3_
  
  - [x] 9.2 Create shared JavaScript utilities
    - Create resources/js/shared/utils.js for common functions
    - Create resources/js/shared/api.js for API communication helpers
    - Create resources/js/shared/validation.js for form validation
    - _Requirements: 10.3, 10.5_
  
  - [x] 9.3 Implement ES6 module structure
    - Convert JavaScript to ES6 module format with imports/exports
    - Update app.js to import and initialize modules
    - Configure Vite for JavaScript module bundling
    - _Requirements: 10.3, 10.4_
  
  - [x] 9.4 Remove inline scripts from Blade templates
    - Replace @push('scripts') sections with @vite directives
    - Remove <script> tags from all Blade files
    - Verify JavaScript functionality works after extraction
    - _Requirements: 10.1, 10.4_

- [x] 10. Simplify and consolidate route structure
  - [x] 10.1 Reorganize routes into logical groups
    - Group facility routes (including land info) under facilities resource
    - Group export routes under /export prefix
    - Group admin routes under /admin prefix with middleware
    - _Requirements: 3.1, 3.2_
  
  - [x] 10.2 Implement RESTful route conventions
    - Ensure all resource routes follow Laravel conventions
    - Use nested resources where appropriate (facilities.land-info)
    - Standardize route naming patterns
    - _Requirements: 3.2, 3.3_
  
  - [x] 10.3 Create route compatibility layer
    - Add route aliases for backward compatibility
    - Implement redirects for changed URLs where necessary
    - Document route changes for frontend updates
    - _Requirements: 3.3, 3.4_
  
  - [x] 10.4 Test all route functionality
    - Verify all routes resolve correctly
    - Test route middleware and permissions
    - Confirm backward compatibility works
    - _Requirements: 3.4, 7.1_

- [x] 11. Clean up duplicate code and unused files
  - [x] 11.1 Remove obsolete controller files
    - Delete LandInfoController.php after functionality moved
    - Delete FacilityCommentController.php after merge
    - Delete PdfExportController.php and CsvExportController.php after merge
    - _Requirements: 5.1, 5.4_
  
  - [x] 11.2 Remove obsolete service files
    - Delete LandInfoService.php and LandCalculationService.php after merge
    - Delete SecurePdfService.php and BatchPdfService.php after merge
    - Clean up FileService.php if functionality moved to ExportService
    - _Requirements: 5.1, 5.4_
  
  - [x] 11.3 Clean up duplicate view components
    - Identify and consolidate duplicate Blade partials
    - Create reusable Blade components for common UI elements
    - Remove unused view files
    - _Requirements: 5.4, 4.2_
  
  - [x] 11.4 Update imports and dependencies
    - Update all use statements in controllers and services
    - Remove unused imports throughout codebase
    - Update service provider bindings
    - _Requirements: 5.1, 5.4_

- [-] 12. Update and consolidate test files
  - [x] 12.1 Merge controller test files
    - Combine LandInfoControllerTest into FacilityControllerTest
    - Merge export controller tests into ExportControllerTest
    - Update test methods for new controller structure
    - _Requirements: 7.1, 7.2_
  
  - [x] 12.2 Merge service test files
    - Combine land info service tests into FacilityServiceTest
    - Merge export service tests into ExportServiceTest
    - Update test data and assertions
    - _Requirements: 7.1, 7.2_
  
  - [x] 12.3 Create asset compilation tests
    - Write tests to verify CSS compilation works correctly
    - Write tests to verify JavaScript module loading
    - Test asset versioning and caching
    - _Requirements: 7.1, 10.4_
  
  - [x] 12.4 Run full test suite and fix issues
    - Execute all tests and identify failures
    - Fix broken tests due to refactoring changes
    - Ensure test coverage remains high
    - _Requirements: 7.1, 7.4_

- [ ] 13. Update documentation and configuration
  - [x] 13.1 Update project documentation
    - Update README.md with new project structure
    - Create architecture documentation for simplified structure
    - Document API changes and migration guide
    - _Requirements: 9.1, 9.2_
  
  - [x] 13.2 Clean up configuration files
    - Remove unused configuration options
    - Consolidate environment variables in .env.example
    - Update service provider registrations
    - _Requirements: 6.1, 6.2_
  
  - [ ] 13.3 Update deployment scripts
    - Modify deployment scripts for new asset structure
    - Update build processes for CSS/JS compilation
    - Test deployment in staging environment
    - _Requirements: 6.3, 9.3_
  
  - [ ] 13.4 Create change log and migration notes
    - Document all breaking changes
    - Create migration checklist for existing installations
    - Update version number and release notes
    - _Requirements: 9.4, 11.4_

- [ ] 14. Performance testing and optimization
  - [ ] 14.1 Benchmark application performance
    - Measure page load times before and after refactoring
    - Test database query performance with consolidated services
    - Monitor memory usage and response times
    - _Requirements: 8.3, 11.5_
  
  - [ ] 14.2 Optimize asset loading
    - Configure CSS/JS minification and compression
    - Implement asset versioning for cache busting
    - Test asset loading performance
    - _Requirements: 10.4, 11.5_
  
  - [ ] 14.3 Validate functionality preservation
    - Perform end-to-end testing of all major features
    - Verify user workflows work correctly
    - Test error handling and edge cases
    - _Requirements: 1.3, 7.4_
  
  - [ ] 14.4 Security audit and dependency updates
    - Run composer audit and npm audit for vulnerabilities
    - Update dependencies to latest compatible versions
    - Verify security measures remain intact
    - _Requirements: 11.1, 11.5_