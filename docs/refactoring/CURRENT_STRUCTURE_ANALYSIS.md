# Current Project Structure Analysis

**Date**: 2025-09-08  
**Purpose**: Pre-refactoring documentation for project simplification  
**Branch**: feature/project-simplification

## Current Controller Structure (13 Controllers)

### Main Controllers
1. **AuthController** - User authentication and login
2. **FacilityController** - Basic facility management
3. **LandInfoController** - Land information management (TO BE MERGED)
4. **CommentController** - General comment system
5. **FacilityCommentController** - Facility-specific comments (TO BE MERGED)
6. **PdfExportController** - PDF export functionality (TO BE MERGED)
7. **CsvExportController** - CSV export functionality (TO BE MERGED)
8. **NotificationController** - Notification management
9. **MyPageController** - User dashboard
10. **MaintenanceController** - Maintenance history
11. **AnnualConfirmationController** - Annual confirmation process
12. **ActivityLogController** - Activity logging
13. **Controller** - Base controller class

### Admin Controllers (Subdirectory)
14. **Admin/UserController** - User management (PLACEHOLDER ROUTES - Controller to be implemented)
15. **Admin/SettingsController** - System settings (PLACEHOLDER ROUTES)

## Current Service Structure (8 Services)

1. **ActivityLogService** - Activity logging operations
2. **BatchPdfService** - Batch PDF generation (TO BE MERGED)
3. **FileService** - File upload/download operations (TO BE MERGED)
4. **LandCalculationService** - Land calculation logic (TO BE MERGED)
5. **LandInfoService** - Land information operations (TO BE MERGED)
6. **NotificationService** - Notification operations
7. **PerformanceMonitoringService** - Performance tracking
8. **SecurePdfService** - Secure PDF generation (TO BE MERGED)

## Planned Consolidation Strategy

### Controller Consolidation (13 → 8)
- **FacilityController** ← merge LandInfoController
- **CommentController** ← merge FacilityCommentController  
- **ExportController** ← merge PdfExportController + CsvExportController
- Keep: AuthController, NotificationController, MyPageController, MaintenanceController, AnnualConfirmationController, ActivityLogController, Controller (base)
- **Admin Controllers**: Currently implemented as placeholder routes (Admin/UserController to be implemented later)

### Service Consolidation (8 → 5)
- **FacilityService** ← merge LandInfoService + LandCalculationService
- **ExportService** ← merge BatchPdfService + SecurePdfService + FileService
- Keep: ActivityLogService, NotificationService, PerformanceMonitoringService

## Current Route Analysis

### Route Groups by Controller
- `/auth/*` - AuthController
- `/facilities/*` - FacilityController
- `/land-info/*` - LandInfoController (TO BE MERGED into facilities)
- `/comments/*` - CommentController
- `/facility-comments/*` - FacilityCommentController (TO BE MERGED)
- `/export/pdf/*` - PdfExportController (TO BE MERGED)
- `/export/csv/*` - CsvExportController (TO BE MERGED)
- `/notifications/*` - NotificationController
- `/my-page/*` - MyPageController
- `/maintenance/*` - MaintenanceController
- `/annual-confirmation/*` - AnnualConfirmationController
- `/activity-logs/*` - ActivityLogController
- `/admin/*` - Admin controllers

## Dependencies and Relationships

### Controller Dependencies
- **FacilityController**: Uses basic facility operations
- **LandInfoController**: Uses LandInfoService, LandCalculationService
- **PdfExportController**: Uses SecurePdfService, BatchPdfService
- **CsvExportController**: Uses FileService for exports
- **CommentController**: Basic comment operations
- **FacilityCommentController**: Facility-specific comment operations

### Service Dependencies
- **LandInfoService**: Uses LandCalculationService
- **BatchPdfService**: Uses FileService
- **SecurePdfService**: Uses FileService
- **FileService**: Standalone file operations

## Risk Assessment

### Low Risk Merges
- CommentController + FacilityCommentController (similar functionality)
- PdfExportController + CsvExportController (both export-related)

### Medium Risk Merges
- FacilityController + LandInfoController (different domains but related)
- BatchPdfService + SecurePdfService + FileService (different responsibilities)

### High Risk Areas
- Route compatibility during consolidation
- Service dependency injection updates
- Test suite updates after merges

## Files to Monitor During Refactoring

### Critical Files
- `routes/web.php` - Route definitions
- `app/Providers/AppServiceProvider.php` - Service bindings
- All Blade templates using affected controllers
- All test files for merged controllers/services

### Backup Strategy
- Database backups created in `storage/app/backups/`
- Git branch protection with `feature/project-simplification`
- Incremental commits for each merge operation

## Success Criteria
- Reduce from 13 to 8 controllers (38% reduction)
- Reduce from 8 to 5 services (37% reduction)
- Maintain all existing functionality
- Preserve test coverage
- Maintain route compatibility where possible