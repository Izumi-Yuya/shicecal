# Database Migration Summary

## Created Migrations

### 1. Users Table Modification (2024_01_01_000001_add_facility_fields_to_users_table.php)
- Added `role` enum field (admin, editor, primary_responder, approver, viewer)
- Added `department` string field (nullable)
- Added `access_scope` JSON field for permission ranges (nullable)
- Added `is_active` boolean field (default: true)
- Added indexes on `role` and `is_active`

### 2. Facilities Table (2024_01_01_000002_create_facilities_table.php)
- Primary key: `id`
- Basic info: `company_name`, `office_code`, `designation_number`, `facility_name`
- Address info: `postal_code`, `address`, `phone_number`, `fax_number`
- Approval workflow: `status` enum, `approved_at`, `approved_by`
- Audit trail: `created_by`, `updated_by`, `created_at`, `updated_at`
- Foreign keys to users table with proper constraints
- Indexes on key search fields

### 3. Files Table (2024_01_01_000003_create_files_table.php)
- Links to facilities via `facility_id`
- File metadata: `original_name`, `file_path`, `file_size`, `mime_type`
- File categorization: `file_type` enum (contract, blueprint, inspection, other)
- Upload tracking: `uploaded_by`, timestamps
- Foreign key constraints and indexes

### 4. Comments Table (2024_01_01_000004_create_comments_table.php)
- Links to facilities via `facility_id`
- Comment details: `field_name`, `content`
- Status tracking: `status` enum (pending, in_progress, resolved)
- User assignment: `posted_by`, `assigned_to`, `resolved_at`
- Foreign key constraints and indexes

### 5. Maintenance Histories Table (2024_01_01_000005_create_maintenance_histories_table.php)
- Links to facilities via `facility_id`
- Maintenance details: `maintenance_date`, `content`, `cost`, `contractor`
- Audit trail: `created_by`, timestamps
- Foreign key constraints and indexes

### 6. Export Favorites Table (2024_01_01_000006_create_export_favorites_table.php)
- User-specific export settings via `user_id`
- Export configuration: `name`, `facility_ids` (JSON), `export_fields` (JSON)
- Timestamps for tracking
- Foreign key constraints and indexes

### 7. System Settings Table (2024_01_01_000007_create_system_settings_table.php)
- Key-value configuration storage: `key`, `value`, `description`
- Change tracking: `updated_by`, timestamps
- Unique constraint on `key`
- Foreign key constraints and indexes

### 8. Activity Logs Table (2024_01_01_000008_create_activity_logs_table.php)
- Comprehensive audit logging: `user_id`, `action`, `target_type`, `target_id`
- Log details: `description`, `ip_address`, `user_agent`
- Timestamp tracking: `created_at`
- Foreign key constraints and indexes

## Foreign Key Relationships

- **Users** → Facilities (created_by, updated_by, approved_by)
- **Facilities** → Files (facility_id)
- **Facilities** → Comments (facility_id)
- **Facilities** → Maintenance Histories (facility_id)
- **Users** → Export Favorites (user_id)
- **Users** → System Settings (updated_by)
- **Users** → Activity Logs (user_id)
- **Users** → Files (uploaded_by)
- **Users** → Comments (posted_by, assigned_to)
- **Users** → Maintenance Histories (created_by)

## Indexes Created

All tables include appropriate indexes on:
- Foreign key columns
- Frequently searched columns (role, status, dates)
- Unique constraints where needed

## Requirements Coverage

✅ **Requirement 1.2**: User management with roles and permissions
✅ **Requirement 2.1**: Facility information storage and management
✅ **Requirement 8.1**: Comment system for facility information

All migrations include proper:
- Foreign key constraints with appropriate cascade/set null actions
- Indexes for performance optimization
- Proper data types and constraints
- Rollback functionality in down() methods