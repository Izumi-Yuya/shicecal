# Controller Method Inventory for Refactoring

**Date**: 2025-09-08  
**Purpose**: Detailed method inventory for controllers to be merged during project simplification

## LandInfoController → FacilityController

### Public Methods (11 methods)
1. **show(Facility $facility)** - Display land information for facility
2. **edit(Facility $facility)** - Show form for editing land information
3. **update(LandInfoRequest $request, Facility $facility)** - Update land information
4. **calculateFields(Request $request)** - Real-time field calculations
5. **getStatus(Facility $facility)** - Get land info status for approval workflow
6. **approve(Facility $facility)** - Approve pending land information changes
7. **reject(Request $request, Facility $facility)** - Reject pending changes
8. **uploadDocuments(Request $request, Facility $facility)** - Upload land documents
9. **getDocuments(Facility $facility, Request $request)** - Get land documents list
10. **downloadDocument(Facility $facility, $fileId)** - Download land document
11. **deleteDocument(Facility $facility, $fileId)** - Delete land document

### Private Methods (2 methods)
1. **filterDataByPermissions(array $data, $user)** - Filter data by user permissions
2. **handlePdfUploads(Request $request, LandInfo $landInfo)** - Handle PDF uploads

### Dependencies
- LandInfoService
- LandCalculationService
- ActivityLogService
- FileService

## FacilityCommentController → CommentController

### Public Methods (3 methods)
1. **index(Facility $facility, string $section)** - Get comments for specific section
2. **store(Request $request, Facility $facility)** - Save new comment
3. **destroy(Facility $facility, FacilityComment $comment)** - Delete comment

### Dependencies
- None (uses models directly)

## PdfExportController → ExportController

### Public Methods (6 methods)
1. **index()** - Display PDF export page
2. **generateSingle(Request $request, Facility $facility)** - Generate single facility PDF
3. **generateSecureSingle(Facility $facility)** - Generate secure single PDF
4. **generateBatch(Request $request)** - Generate multiple facility PDFs
5. **getBatchProgress(Request $request, string $batchId)** - Get batch progress (AJAX)

### Private Methods (6 methods)
1. **generateBatchWithProgress($facilities, bool $useSecure)** - Generate batch with progress
2. **generateFacilityPdf(Facility $facility)** - Generate PDF for single facility
3. **generateBatchZip($facilities, bool $useSecure)** - Generate ZIP with multiple PDFs
4. **generatePdfFilename(Facility $facility)** - Generate PDF filename
5. **getFacilitiesForUser()** - Get facilities based on user permissions
6. **canViewFacility(Facility $facility)** - Check facility view permissions

### Dependencies
- SecurePdfService
- BatchPdfService
- ActivityLogService

## CsvExportController → ExportController

### Public Methods (10 methods)
1. **index()** - Display CSV export menu
2. **getFieldPreview(Request $request)** - Get field preview data
3. **generateCsv(Request $request)** - Generate and download CSV
4. **generateCsvOptimized(Request $request)** - Optimized CSV for large datasets
5. **getFavorites()** - Get user's export favorites
6. **saveFavorite(Request $request)** - Save export settings as favorite
7. **loadFavorite($id)** - Load favorite settings
8. **updateFavorite(Request $request, $id)** - Update favorite name
9. **deleteFavorite($id)** - Delete favorite

### Private Methods (11 methods)
1. **streamLargeExport(Request $request)** - Stream large export with progress
2. **getFieldValueOptimized($facility, $field)** - Optimized field value extraction
3. **generateCsvContent($facilities, $exportFields, $selectedFields)** - Generate CSV content
4. **arrayToCsvLine(array $data)** - Convert array to CSV line
5. **getFacilitiesQuery(User $user)** - Get facilities query by user permissions
6. **getFacilitiesForUser(User $user)** - Get facilities by user permissions
7. **getAvailableFields()** - Get available fields for export
8. **getFacilityFields()** - Get facility fields for export
9. **getLandInfoFields()** - Get land info fields for export
10. **getFieldValue(Facility $facility, string $field)** - Get formatted field value
11. **getLandInfoFieldValue($landInfo, string $field)** - Get land info field value
12. **getStatusLabel(string $status)** - Get status label in Japanese
13. **getOwnershipTypeLabel(?string $ownershipType)** - Get ownership type label
14. **getAutoRenewalLabel(?string $autoRenewal)** - Get auto renewal label

### Dependencies
- ActivityLogService

## Consolidation Strategy

### FacilityController (Enhanced)
**New Methods from LandInfoController:**
- Land info CRUD operations (show, edit, update)
- Approval workflow (getStatus, approve, reject)
- Document management (upload, get, download, delete)
- Field calculations (calculateFields)

**Route Changes:**
- `/facilities/{facility}/land-info` → `/facilities/{facility}/land-info`
- `/facilities/{facility}/land-info/edit` → `/facilities/{facility}/land-info/edit`
- `/facilities/{facility}/land-info/calculate` → `/facilities/{facility}/land-info/calculate`
- `/facilities/{facility}/land-info/approve` → `/facilities/{facility}/land-info/approve`
- `/facilities/{facility}/land-info/reject` → `/facilities/{facility}/land-info/reject`
- `/facilities/{facility}/land-info/documents` → `/facilities/{facility}/land-info/documents`

### CommentController (Enhanced)
**New Methods from FacilityCommentController:**
- Facility-specific comment operations (index, store, destroy)

**Route Changes:**
- `/facility-comments/{facility}/{section}` → `/comments/facility/{facility}/{section}`
- `/facility-comments/{facility}` → `/comments/facility/{facility}`
- `/facility-comments/{facility}/{comment}` → `/comments/facility/{facility}/{comment}`

### ExportController (New)
**Methods from PdfExportController:**
- PDF generation (index, generateSingle, generateSecureSingle, generateBatch)
- Progress tracking (getBatchProgress)

**Methods from CsvExportController:**
- CSV generation (index, getFieldPreview, generateCsv, generateCsvOptimized)
- Favorites management (getFavorites, saveFavorite, loadFavorite, updateFavorite, deleteFavorite)

**Route Changes:**
- `/export/pdf/*` → `/export/pdf/*` (unchanged)
- `/export/csv/*` → `/export/csv/*` (unchanged)

## Risk Assessment

### Low Risk Merges
- **FacilityCommentController → CommentController**: Simple methods, minimal dependencies
- **Route consolidation**: Most routes can maintain backward compatibility

### Medium Risk Merges
- **LandInfoController → FacilityController**: Complex approval workflow, multiple services
- **PdfExportController + CsvExportController → ExportController**: Different export types, shared logic

### High Risk Areas
- **Service dependency injection**: Multiple services need to be injected into consolidated controllers
- **Authorization policies**: Need to ensure all policies work with new controller structure
- **Test coverage**: Extensive test updates required for merged functionality

## Implementation Order

1. **Phase 1**: Create shared error handling traits
2. **Phase 2**: Merge FacilityCommentController → CommentController (lowest risk)
3. **Phase 3**: Create ExportController (PdfExportController + CsvExportController)
4. **Phase 4**: Merge LandInfoController → FacilityController (highest complexity)
5. **Phase 5**: Update routes and test all functionality

## Files to Update During Merge

### Controller Files
- `app/Http/Controllers/FacilityController.php` (enhance)
- `app/Http/Controllers/CommentController.php` (enhance)
- `app/Http/Controllers/ExportController.php` (create)
- Delete: `LandInfoController.php`, `FacilityCommentController.php`, `PdfExportController.php`, `CsvExportController.php`

### Route Files
- `routes/web.php` (update all affected routes)

### View Files
- Update form actions in all Blade templates
- Update AJAX endpoints in JavaScript files

### Test Files
- Merge and update all controller tests
- Update feature tests with new route structure

### Service Provider
- Update dependency injection bindings if needed