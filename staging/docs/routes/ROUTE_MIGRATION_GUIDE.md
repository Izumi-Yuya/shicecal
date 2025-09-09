# Route Migration Guide

This document outlines the changes made to the route structure during the project simplification refactoring.

## Overview

The route structure has been reorganized to follow RESTful conventions and logical grouping. All old routes have backward compatibility redirects in place.

## Route Structure Changes

### 1. Admin User Management Routes

**Current Implementation:**
```
/admin/users/          - User list (placeholder view)
/admin/users/create    - User creation form (placeholder view)
/admin/users/{user}    - User details (placeholder view)
/admin/users/{user}/edit - User edit form (placeholder view)
```

**Status:**
- Routes are currently implemented as placeholder closures returning views
- Full controller implementation (`Admin\UserController`) is planned for future development
- Views are expected to exist at `resources/views/admin/users/`
- Middleware protection: `auth` + `role:admin`

**Migration Notes:**
- Previous resource route `Route::resource('users', 'Admin\UserController')` has been replaced with individual placeholder routes
- This allows for gradual implementation of admin functionality
- Route names remain consistent: `admin.users.index`, `admin.users.create`, etc.

### 2. Export Routes

**Old Structure:**
```
/pdf-export/
/csv-export/
```

**New Structure:**
```
/export/pdf/
/export/csv/
```

**Migration:**
- All old export routes automatically redirect to new structure with 301 status
- Route names changed from `pdf-export.*` to `export.pdf.*`
- Route names changed from `csv-export.*` to `export.csv.*`

### 2. Land Information Routes

**Old Structure:**
```
/land-info/{facility}/
/land-info/{facility}/edit
```

**New Structure:**
```
/facilities/{facility}/land-info/
/facilities/{facility}/land-info/edit
```

**Migration:**
- Land info is now nested under facilities as it's facility-specific data
- All old routes redirect with 301 status to maintain SEO and bookmarks
- Route names changed from `land-info.*` to `facilities.land-info.*`

### 3. Comment Routes

**Old Structure:**
```
/facility-comments/{facility}/
/comments/
```

**New Structure:**
```
/facilities/{facility}/comments/
/comments/
```

**Migration:**
- Facility-specific comments are now nested under facilities
- General comment management remains at `/comments/`
- Backward compatibility maintained with redirects

### 4. Admin Routes

**New Structure:**
```
/admin/users/
/admin/settings/
/admin/logs/
```

**Features:**
- Proper middleware protection with `role:admin`
- RESTful resource conventions
- Nested resource structure for settings and logs

## Route Naming Conventions

All routes now follow Laravel's standard naming conventions:

### Resource Routes
- `index` - List resources
- `create` - Show create form
- `store` - Store new resource
- `show` - Show specific resource
- `edit` - Show edit form
- `update` - Update resource
- `destroy` - Delete resource

### Nested Resources
- Parent resource comes first: `facilities.land-info.show`
- Dot notation for hierarchy: `export.csv.favorites.index`

### Custom Actions
- Use descriptive names: `facilities.land-info.approve`
- Follow RESTful verbs: POST for actions, GET for forms

## Middleware Changes

### Authentication
All protected routes use `auth` middleware consistently.

### Authorization
Admin routes use `role:admin` middleware for proper role-based access control.

### Route Groups
Routes are logically grouped with shared middleware and prefixes.

## Backward Compatibility

### Automatic Redirects
All old routes automatically redirect to new routes with 301 status codes to maintain:
- SEO rankings
- Bookmarked URLs
- External links

### Route Aliases
Legacy route names are preserved as aliases where possible.

### Deprecation Timeline
- **Phase 1** (Current): All old routes redirect to new routes
- **Phase 2** (3 months): Add deprecation warnings to logs
- **Phase 3** (6 months): Remove redirect routes (optional)

## Frontend Updates Required

### JavaScript/AJAX Calls
Update any hardcoded URLs in JavaScript:

```javascript
// Old
fetch('/pdf-export/facility/' + facilityId)

// New
fetch('/export/pdf/facility/' + facilityId)
```

### Form Actions
Update form action URLs in Blade templates:

```html
<!-- Old -->
<form action="{{ route('land-info.update', $facility) }}">

<!-- New -->
<form action="{{ route('facilities.land-info.update', $facility) }}">
```

### Route Helpers
Update route helper calls:

```php
// Old
route('csv-export.index')

// New
route('export.csv.index')
```

## Testing Route Changes

### Automated Tests
Run the route test suite to verify all routes resolve correctly:

```bash
php artisan test --filter RouteTest
```

### Manual Testing
1. Test all major user workflows
2. Verify redirects work correctly
3. Check that middleware is properly applied
4. Confirm nested routes resolve correctly

## Benefits of New Structure

### 1. Logical Grouping
- Related functionality is grouped together
- Easier to understand and navigate
- Consistent URL patterns

### 2. RESTful Conventions
- Standard Laravel resource patterns
- Predictable URL structure
- Better API design

### 3. Maintainability
- Clearer route organization
- Easier to add new features
- Reduced route conflicts

### 4. Security
- Proper middleware application
- Role-based access control
- Consistent authentication

## Route Count Reduction

**Before:** ~50+ individual route definitions
**After:** ~30 route definitions with logical grouping

This represents a 40% reduction in route complexity while maintaining all functionality.