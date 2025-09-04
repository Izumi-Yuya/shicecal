# Implementation Plan

- [x] 1. Set up database structure and core models
  - Create land_info table migration with all required fields
  - Add land_document_type column to existing files table
  - Create LandInfo Eloquent model with relationships and accessors
  - Update Facility model to include landInfo relationship
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8_

- [x] 2. Implement land calculation service
  - Create LandCalculationService class for unit price calculation
  - Implement contract period calculation (years and months format)
  - Add currency formatting methods with comma separators
  - Create area formatting methods for sqm and tsubo display
  - Add Japanese date formatting functionality
  - Write unit tests for all calculation methods
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 3. Create land information service layer
  - Implement LandInfoService class for business logic
  - Add methods for creating and updating land information
  - Integrate with existing approval workflow system
  - Implement data sanitization (full-width to half-width conversion)
  - Add cache management for land information
  - Write unit tests for service methods
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 4. Implement land information controller
  - Create LandInfoController with show, edit, update methods
  - Add calculateFields endpoint for real-time calculations
  - Implement proper authorization using existing policies
  - Add comprehensive error handling and validation
  - Create LandInfoRequest for form validation
  - Write feature tests for all controller methods
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 5. Create tabbed facility detail view
  - Update facilities/show.blade.php to include tab navigation
  - Create facilities/partials/basic-info.blade.php for existing content
  - Create facilities/partials/land-info.blade.php for land information display
  - Implement Bootstrap tab functionality
  - Add proper responsive design for mobile compatibility
  - _Requirements: 1.1, 1.2, 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 6. Implement JavaScript functionality
  - Create LandInfoManager class for form interactions
  - Add ownership type change handlers for conditional sections
  - Implement real-time unit price calculation
  - Add contract period calculation functionality
  - Create currency formatting for input fields
  - Add form validation and user feedback
  - Write JavaScript tests for calculation functions
  - _Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 7. Set up routes and file management infrastructure
  - Add land information routes to web.php
  - Implement FileService methods for land documents (uploadLandDocument, uploadMultipleLeaseContracts, getLandDocuments)
  - Add land_document_type field handling in File model
  - Create file upload/download/delete endpoints in controller
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8. Create land information edit form
  - Build comprehensive land information edit form with all fields
  - Implement ownership type dropdown with conditional section display
  - Add area input fields with proper formatting hints
  - Create management company information section
  - Add owner information section with all required fields
  - Implement file upload areas for contracts and property registers
  - Add notes textarea with character limit
  - _Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9, 6.1, 6.2, 6.3, 6.4, 6.5, 7.1, 7.2, 7.3_

- [x] 9. Complete remaining FileService methods
  - Implement replaceLandDocument method for property register uploads
  - Add downloadLandDocument and deleteLandDocument methods
  - Add file validation for PDF format and size limits
  - Write tests for land document file operations
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 10. Integrate with existing export systems
  - Update CsvExportController to include land information fields
  - Add land info fields to CSV export field selection
  - Implement land data extraction for CSV generation
  - Update PDF export templates to include land information
  - Add conditional display based on ownership type in PDF
  - Create export favorites functionality for land fields
  - Write tests for land information export functionality
  - _Requirements: 10.1, 10.2, 10.3, 10.4_

- [x] 11. Implement authorization and security enhancements
  - Create LandInfoPolicy for access control
  - Add authorization checks to all land info endpoints
  - Implement role-based access for land information viewing
  - Add input sanitization for security
  - Create audit logging for land information changes
  - Write security tests for authorization
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 12. Complete comprehensive testing
  - Complete unit tests for LandCalculationService
  - Write feature tests for land information CRUD operations
  - Add browser tests for JavaScript functionality
  - Create integration tests for approval workflow
  - Add performance tests for large datasets
  - Write tests for export functionality with land data
  - _Requirements: All requirements_

- [ ] 13. Create database seeders and test data
  - Create LandInfoSeeder with sample data for all ownership types
  - Add land information to existing FacilitySeeder
  - Create test data for different scenarios (owned, leased, owned_rental)
  - Add sample land documents to file seeder
  - Write factory classes for land information testing
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8_

- [ ] 14. Optimize performance and add caching
  - Implement caching for land information queries
  - Add database indexes for performance
  - Optimize JavaScript for large forms
  - Add lazy loading for land information
  - Implement efficient bulk operations for export
  - Write performance tests and benchmarks
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 10.1, 10.2, 10.3, 10.4_