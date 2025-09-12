# Facility Master Import

This document explains how to import facility data from the `facility_master.csv` file.

## CSV File Format

The CSV file should be located at the project root as `facility_master.csv` with the following format:

```csv
facility_code,facility_name,service_type
10901,あおぞらの里 御幸ヶ原デイサービスセンター,デイサービス
11201,あおぞらの里 八千代デイサービスセンター,デイサービス
...
```

### Fields:
- `facility_code`: Unique facility identifier (used as office_code in database)
- `facility_name`: Full name of the facility
- `service_type`: Type of service provided (デイサービス, 有料老人ホーム, etc.)

## Import Methods

### Method 1: Using Artisan Command (Recommended)

```bash
# Import with confirmation prompt
php artisan facility:import-master

# Force import without confirmation
php artisan facility:import-master --force
```

### Method 2: Using Database Seeder

The `FacilityMasterImportSeeder` is now enabled by default in `DatabaseSeeder.php`. Run all seeders:

```bash
php artisan db:seed
```

Or run just the import seeder:

```bash
php artisan db:seed --class=FacilityMasterImportSeeder
```

**Note**: Sample facility data (`FacilitySeeder`) has been disabled in favor of real CSV data.

## Data Mapping

The import process automatically maps CSV data to database fields:

### Facilities Table:
- `office_code` ← `facility_code`
- `facility_name` ← `facility_name`
- `company_name` ← Generated based on facility_name (see Company Name Logic below)
- `designation_number` ← Generated from facility_code
- `status` ← Set to 'approved'
- Other fields ← Set to null (can be filled later)

### Facility Services Table:
- `service_type` ← `service_type`
- `section` ← Mapped based on service_type
- `renewal_start_date` ← Set to '2024-04-01'
- `renewal_end_date` ← Set to '2030-03-31'

## Company Name Logic

施設名に基づいて会社名を自動判定：

| Facility Name Pattern | Company Name | Notes |
|----------------------|--------------|-------|
| 麻生の郷 | 株式会社パイン | パイン系施設 |
| 武蔵野の郷 | 株式会社パイン | パイン系施設 |
| わらび 花の郷 | 株式会社パイン | パイン系施設 |
| 靎見の鄕 | 株式会社パイン | パイン系施設 |
| 小文字の郷 | 株式会社パイン | パイン系施設 |
| わじろの郷 | 株式会社パイン | パイン系施設 |
| あおぞらの里 | 社会福祉法人あおぞらの里 | あおぞらの里系施設 |
| ラ・ナシカ | 株式会社ラ・ナシカ | ラ・ナシカ系施設 |
| 本社・関東本部 | 株式会社シダー | 本社・支社 |
| その他 | 株式会社シダー | デフォルト |

## Service Type Mapping

| Service Type | Section |
|--------------|---------|
| デイサービス | 通所系サービス |
| 有料老人ホーム | 入居系サービス |
| グループホーム | 認知症対応サービス |
| 訪問看護 | 在宅系サービス |
| ヘルパー | 在宅系サービス |
| ケアプラン | 在宅系サービス |
| 本社 | 管理部門 |

## Notes

- Facilities with empty `facility_code` will be skipped
- Existing facilities (same office_code) will be skipped
- All imported facilities are set to 'approved' status
- The import process is wrapped in a database transaction for safety
- Detailed logs are provided during import showing progress and any issues

## Troubleshooting

### File Not Found Error
Ensure `facility_master.csv` is located in the project root directory.

### Permission Errors
Make sure the web server has read permissions on the CSV file.

### Database Errors
Ensure the database is properly migrated and admin users exist:

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```
## Product
ion Setup

For production deployment, the system is now configured to use real facility data from the CSV file:

1. **DatabaseSeeder Configuration**: 
   - `FacilityMasterImportSeeder` is enabled by default
   - `FacilitySeeder` (sample data) is disabled
   - `FacilityServiceSeeder` only processes facilities without existing services

2. **Clean Database Setup**:
   ```bash
   php artisan migrate:fresh
   php artisan db:seed
   ```

3. **Data Verification**:
   ```bash
   php scripts/verify-facility-import.php
   ```

This ensures that only real facility data from the CSV is used in production, with no sample/test data mixed in.