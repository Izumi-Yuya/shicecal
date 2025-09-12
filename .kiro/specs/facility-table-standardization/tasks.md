# Implementation Plan

- [x] 1. Set up core table configuration infrastructure
  - Create centralized table configuration file with support for multiple table types
  - Implement TableConfigService to load and validate table configurations
  - Create base configuration structure for basic_info, service_info, and land_info tables
  - _Requirements: 1.1, 1.2, 1.3, 4.1, 4.2_

- [x] 2. Create universal table component foundation
  - [x] 2.1 Implement universal table Blade component
    - Create resources/views/components/universal-table.blade.php with configurable structure
    - Support different layout types (key_value_pairs, standard_table, grouped_rows)
    - Implement responsive PC-only layout with horizontal scrolling
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3_

  - [x] 2.2 Create table data formatter service
    - Implement TableDataFormatter service to handle data formatting based on column types
    - Add support for text, date_range, number, and select field types
    - Implement empty value handling with configurable "未設定" display
    - _Requirements: 1.5, 4.4, 5.4_

  - [x] 2.3 Implement table view helper utilities
    - Create TableViewHelper service for data preparation and CSS class generation
    - Add methods for calculating responsive column widths for PC environments
    - Implement table class generation based on configuration
    - _Requirements: 2.1, 2.2, 4.3, 5.6_

- [x] 3. Integrate comment system with standardized tables
  - [x] 3.1 Create comment-enabled table wrapper
    - Extend universal table component to include comment functionality
    - Integrate with existing comment system for basic_info, service_info, and land_info sections
    - Implement comment toggle buttons and comment count badges
    - _Requirements: 3.1, 3.2, 3.4_

  - [x] 3.2 Implement comment section integration
    - Create reusable comment section component for tables
    - Add real-time comment posting and display functionality
    - Implement comment section expand/collapse state management
    - _Requirements: 3.3, 3.5_

- [x] 4. Implement complex column configuration support
  - [x] 4.1 Add support for rowspan grouping
    - Implement rowspan functionality for grouped data (like service types)
    - Create logic to calculate and apply rowspan attributes dynamically
    - Add support for hierarchical header structures
    - _Requirements: 5.1, 5.4_

  - [x] 4.2 Create dynamic column management
    - Implement dynamic column addition/removal based on configuration
    - Add support for conditional column display based on data content
    - Create column width calculation for content-responsive layouts
    - _Requirements: 5.3, 5.5, 5.6_

  - [x] 4.3 Implement cell merging and nested data support
    - Add support for cell merging configurations in table config
    - Implement nested data display with visual hierarchy
    - Create logic for complex column structures with multiple levels
    - _Requirements: 5.2, 5.4_

- [x] 5. Create table-specific configurations and migrate existing tables
  - [x] 5.1 Configure basic info table standardization
    - Create configuration for basic info table with key-value pair layout
    - Migrate resources/views/facilities/basic-info/partials/table.blade.php to use universal component
    - Test basic info table rendering with existing data
    - _Requirements: 1.1, 4.1, 4.2_

  - [x] 5.2 Configure service info table standardization
    - Create configuration for service info table with grouped rows and rowspan
    - Migrate resources/views/facilities/services/partials/table.blade.php to use universal component
    - Implement service-specific date range formatting and period separator
    - _Requirements: 1.2, 5.1, 5.4_

  - [x] 5.3 Configure land info table standardization
    - Create configuration for land info table with standard table layout
    - Implement land info specific column types and formatting
    - Add support for land info specific data validation and display
    - _Requirements: 1.3, 4.3, 5.5_

- [x] 6. Implement error handling and fallback mechanisms
  - [x] 6.1 Create configuration validation system
    - Implement TableConfigValidator to validate table configurations
    - Add validation for required fields, column structure, and responsive settings
    - Create comprehensive error messages for configuration issues
    - _Requirements: 4.5_

  - [x] 6.2 Implement fallback mechanisms
    - Create fallback to default configuration when custom config fails
    - Implement graceful degradation to original table components on errors
    - Add error logging for configuration and rendering failures
    - _Requirements: 4.5_

- [x] 7. Add performance optimizations
  - [x] 7.1 Implement table rendering performance optimizations
    - Add caching for compiled table configurations
    - Implement efficient DOM rendering for large datasets
    - Optimize CSS and JavaScript loading for table components
    - _Requirements: 6.1, 6.3_

  - [x] 7.2 Create memory usage optimizations
    - Implement pagination support for large datasets
    - Add lazy loading for table data when needed
    - Optimize JavaScript event handling and cleanup
    - _Requirements: 6.2, 6.4_

- [x] 8. Create comprehensive test suite
  - [x] 8.1 Write unit tests for core services
    - Create unit tests for TableConfigService configuration loading and validation
    - Write tests for TableDataFormatter data formatting and empty value handling
    - Implement tests for TableViewHelper data preparation and CSS generation
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 8.2 Create integration tests for table components
    - Write integration tests for universal table component rendering
    - Test comment system integration with standardized tables
    - Create tests for responsive PC layout behavior and horizontal scrolling
    - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 3.3_

  - [x] 8.3 Implement browser and performance tests
    - Create browser tests for PC responsive behavior at different screen sizes
    - Write performance tests for table rendering with large datasets
    - Test complex column configurations and dynamic column management
    - _Requirements: 2.4, 5.1, 5.2, 5.3, 5.6, 6.1, 6.2_

- [x] 9. Finalize migration and cleanup
  - [x] 9.1 Complete table migration verification
    - Verify all existing table functionality works with new standardized components
    - Test comment integration across all table types
    - Validate responsive behavior and performance meets requirements
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 9.2 Remove deprecated table implementations
    - Remove old table partial files after successful migration
    - Clean up unused CSS and JavaScript related to old table implementations
    - Update documentation to reflect new standardized table system
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_