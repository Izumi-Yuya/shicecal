# Service Method Inventory for Refactoring

**Date**: 2025-09-08  
**Purpose**: Detailed method inventory for services to be merged during project simplification

## LandInfoService + LandCalculationService → FacilityService

### LandInfoService Methods (18 methods)

#### Public Methods (12 methods)
1. **getLandInfo(Facility $facility)** - Get land info with caching
2. **createOrUpdateLandInfo(Facility $facility, array $data, User $user)** - Create/update land info
3. **formatDisplayData(LandInfo $landInfo)** - Format data for display
4. **prepareForApproval(LandInfo $landInfo, array $changes)** - Prepare for approval workflow
5. **approveLandInfo(LandInfo $landInfo, User $approver)** - Approve land info changes
6. **rejectLandInfo(LandInfo $landInfo, User $approver, string $reason)** - Reject changes
7. **clearLandInfoCache(Facility $facility)** - Clear cache
8. **getBulkLandInfo(array $facilityIds)** - Get multiple land info records
9. **getFormattedLandInfoWithCache(Facility $facility)** - Get formatted data with cache
10. **getExportDataWithCache(Facility $facility)** - Get export data with cache
11. **warmUpCache(array $facilityIds)** - Warm up cache for multiple facilities
12. **clearAllCaches()** - Clear all land info caches

#### Protected Methods (6 methods)
1. **sanitizeInputData(array $data)** - Sanitize input data
2. **performCalculations(array $data)** - Perform automatic calculations
3. **handleApprovalWorkflow(LandInfo $landInfo, array $data, User $user)** - Handle approval
4. **directUpdate(LandInfo $landInfo, array $data, User $user)** - Direct update
5. **isApprovalEnabled()** - Check if approval is enabled
6. **notifyApprovers(LandInfo $landInfo, string $type)** - Notify approvers
7. **notifyApprovalComplete(LandInfo $landInfo, User $approver)** - Notify completion
8. **notifyApprovalRejected(LandInfo $landInfo, User $approver, string $reason)** - Notify rejection
9. **logLandInfoChange(...)** - Log changes for audit

#### Dependencies
- LandCalculationService
- NotificationService

### LandCalculationService Methods (8 methods)

#### Public Methods (8 methods)
1. **calculateUnitPrice(float $purchasePrice, float $areaInTsubo)** - Calculate unit price per tsubo
2. **calculateContractPeriod(string $startDate, string $endDate)** - Calculate contract period
3. **formatCurrency(float $amount)** - Format currency with commas
4. **formatArea(float $area, string $unit)** - Format area with unit
5. **formatJapaneseDate(string $date)** - Format date in Japanese
6. **convertToHalfWidth(string $input)** - Convert full-width to half-width
7. **formatPostalCode(string $postalCode)** - Format postal code
8. **formatPhoneNumber(string $phoneNumber)** - Format phone number

#### Dependencies
- None (utility service)

## SecurePdfService + BatchPdfService + FileService → ExportService

### SecurePdfService Methods (6 methods)

#### Public Methods (4 methods)
1. **generateSecureFacilityPdf(Facility $facility, array $options)** - Generate secure PDF
2. **generateSecureFilename(Facility $facility)** - Generate secure filename
3. **getPdfMetadata(Facility $facility)** - Get PDF metadata

#### Private Methods (4 methods)
1. **setSecuritySettings(\TCPDF $pdf, array $options)** - Set PDF security
2. **generateHtmlContent(array $data)** - Generate HTML content
3. **addWatermark(\TCPDF $pdf, string $text)** - Add watermark
4. **generateSecurePassword(int $length)** - Generate secure password

#### Dependencies
- None (uses TCPDF directly)

### BatchPdfService Methods (10 methods)

#### Public Methods (3 methods)
1. **generateBatchPdf(Collection $facilities, array $options)** - Generate batch PDF
2. **getBatchProgress(string $batchId)** - Get batch progress
3. **cleanupOldBatches()** - Clean up old batch data

#### Private Methods (7 methods)
1. **generateStandardPdf(Facility $facility)** - Generate standard PDF
2. **generateStandardFilename(Facility $facility)** - Generate standard filename
3. **generateBatchId()** - Generate batch ID
4. **generateZipFilename(bool $useSecure)** - Generate ZIP filename
5. **initializeProgress(string $batchId, int $totalCount)** - Initialize progress
6. **updateProgress(...)** - Update progress
7. **completeProgress(...)** - Complete progress
8. **failProgress(string $batchId, string $error)** - Fail progress

#### Dependencies
- SecurePdfService

### FileService Methods (12 methods)

#### Public Methods (8 methods)
1. **uploadLandDocument(Facility $facility, UploadedFile $file, string $documentType, User $user)** - Upload document
2. **uploadMultipleLeaseContracts(Facility $facility, array $files, User $user)** - Upload multiple files
3. **getLandDocuments(Facility $facility, ?string $documentType)** - Get documents
4. **downloadLandDocument(File $file, User $user)** - Download document
5. **deleteLandDocument(File $file, User $user)** - Delete document
6. **replaceLandDocument(Facility $facility, UploadedFile $newFile, string $documentType, User $user)** - Replace document
7. **formatFileSize(int $bytes)** - Format file size
8. **getDocumentTypeDisplayName(string $documentType)** - Get display name

#### Protected Methods (4 methods)
1. **validateLandDocument(UploadedFile $file)** - Validate document
2. **generateUniqueFilename(UploadedFile $file, Facility $facility, string $documentType)** - Generate filename
3. **storeLandDocument(UploadedFile $file, Facility $facility, string $filename)** - Store document

#### Dependencies
- None (uses Storage facade)

## Consolidation Strategy

### FacilityService (New - Merged from LandInfoService + LandCalculationService)

**Core Facility Operations:**
- Basic facility CRUD operations (to be added)
- Facility data validation and sanitization

**Land Information Operations (from LandInfoService):**
- Land info CRUD with caching
- Approval workflow management
- Data formatting and display
- Bulk operations and cache management

**Calculation Operations (from LandCalculationService):**
- Unit price calculations
- Contract period calculations
- Data formatting (currency, area, dates)
- Input sanitization (phone, postal code)

**Method Organization:**
```php
class FacilityService
{
    // Basic facility operations
    public function createFacility(array $data)
    public function updateFacility($id, array $data)
    public function deleteFacility($id)
    public function getFacilityWithPermissions($id, User $user)
    
    // Land info operations (from LandInfoService)
    public function getLandInfo(Facility $facility)
    public function createOrUpdateLandInfo(Facility $facility, array $data, User $user)
    public function formatDisplayData(LandInfo $landInfo)
    public function approveLandInfo(LandInfo $landInfo, User $approver)
    public function rejectLandInfo(LandInfo $landInfo, User $approver, string $reason)
    
    // Calculation operations (from LandCalculationService)
    public function calculateUnitPrice(float $purchasePrice, float $areaInTsubo)
    public function calculateContractPeriod(string $startDate, string $endDate)
    public function formatCurrency(float $amount)
    public function formatArea(float $area, string $unit)
    
    // Cache management
    public function clearLandInfoCache(Facility $facility)
    public function warmUpCache(array $facilityIds)
}
```

### ExportService (New - Merged from SecurePdfService + BatchPdfService + FileService)

**PDF Generation Operations (from SecurePdfService + BatchPdfService):**
- Single facility PDF generation (secure and standard)
- Batch PDF generation with progress tracking
- ZIP file creation and management
- Security settings and watermarks

**File Management Operations (from FileService):**
- Document upload/download/delete
- File validation and storage
- Multiple file handling
- File metadata management

**Method Organization:**
```php
class ExportService
{
    // PDF generation (from SecurePdfService + BatchPdfService)
    public function generateFacilityPdf($facilityId, array $options = [])
    public function generateSecurePdf($facilityId, array $options = [])
    public function generateBatchPdf(array $facilityIds, array $options = [])
    public function getBatchProgress($batchId)
    
    // CSV generation (from CsvExportController logic)
    public function generateCsv(array $facilityIds, array $fields)
    public function getAvailableFields()
    public function previewFieldData(array $facilityIds, array $fields)
    
    // File management (from FileService)
    public function uploadFile($file, $facilityId, string $type)
    public function downloadFile($fileId)
    public function deleteFile($fileId)
    public function getFilesByFacility($facilityId)
}
```

## Risk Assessment

### Low Risk Merges
- **LandCalculationService → FacilityService**: Pure utility methods, no dependencies
- **FileService → ExportService**: Clear file operations, minimal dependencies

### Medium Risk Merges
- **SecurePdfService + BatchPdfService → ExportService**: Related functionality, shared dependencies
- **LandInfoService → FacilityService**: Complex business logic but well-defined boundaries

### High Risk Areas
- **Service dependency injection**: Multiple services need to be updated in controllers
- **Cache key management**: Ensure cache keys remain consistent after merge
- **Notification system**: Approval workflow notifications need to work with new service structure

## Implementation Order

1. **Phase 1**: Create shared service traits for error handling
2. **Phase 2**: Create FacilityService (merge LandInfoService + LandCalculationService)
3. **Phase 3**: Create ExportService (merge SecurePdfService + BatchPdfService + FileService)
4. **Phase 4**: Update all controller dependencies
5. **Phase 5**: Update service provider bindings
6. **Phase 6**: Update and run all tests

## Files to Update During Merge

### Service Files
- `app/Services/FacilityService.php` (create)
- `app/Services/ExportService.php` (create)
- Delete: `LandInfoService.php`, `LandCalculationService.php`, `SecurePdfService.php`, `BatchPdfService.php`, `FileService.php`

### Controller Files
- Update dependency injection in all affected controllers
- Update method calls to use new service structure

### Service Provider
- `app/Providers/AppServiceProvider.php` (update bindings)

### Test Files
- Merge and update all service tests
- Update controller tests with new service dependencies

### Configuration
- Update any service-specific configuration if needed

## Dependencies After Merge

### FacilityService Dependencies
- NotificationService (for approval workflow)
- Cache facade (for caching operations)
- DB facade (for transactions)

### ExportService Dependencies
- Storage facade (for file operations)
- Cache facade (for progress tracking)
- TCPDF (for secure PDF generation)
- DomPDF (for standard PDF generation)

## Success Criteria
- Reduce from 8 to 5 services (37% reduction)
- Maintain all existing functionality
- Preserve test coverage
- Improve code organization and maintainability
- Reduce service dependency complexity in controllers