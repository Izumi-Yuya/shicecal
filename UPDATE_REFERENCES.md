# Reference Updates Required After Directory Restructure

## Files that need to be updated:

### 1. Controller References
- `app/Http/Controllers/FacilityController.php`
  - Line 232: `facilities.basic-info` → `facilities.basic-info.show`
  - Line 241: `facilities.edit-basic-info` → `facilities.basic-info.edit`
  - Line 369: `facilities.land-info-edit` → `facilities.land-info.edit`

### 2. View Include References
- `resources/views/components/section-header.blade.php`
  - Line 30: `facilities.partials.view-toggle` → `facilities.shared.view-toggle`

- `resources/views/facilities/show.blade.php`
  - Line 86: `facilities.partials.view-toggle` → `facilities.shared.view-toggle`
  - Line 90: `facilities.partials.basic-info-table` → `facilities.basic-info.partials.table`
  - Line 92: `facilities.partials.basic-info` → `facilities.basic-info.partials.display-card`

- `resources/views/facilities/basic-info/partials/table.blade.php`
  - Line 202: `facilities.partials.service-table` → `facilities.services.partials.table`

- `resources/views/facilities/services/partials/table-row.blade.php`
  - Line 27: `facilities.partials.service-period` → `facilities.services.partials.period-form`
  - Line 43: `facilities.partials.service-period` → `facilities.services.partials.period-form`

### 3. Service Provider References
- `app/Providers/AppServiceProvider.php`
  - Line 46: `facilities.partials.service-table` → `facilities.services.partials.table`

- `app/Providers/ViewServiceProvider.php`
  - Line 31: `facilities.partials.service-table` → `facilities.services.partials.table`
  - Line 32: `facilities.partials.service-table-improved` → `facilities.services.partials.improved-table`

### 4. Documentation Files
- `.kiro/specs/facility-land-information/design.md`
- `docs/components/service-table.md`
- Various staging files

## Update Commands to Run:
```bash
# Update controller references
# Update view includes
# Update service provider references
```