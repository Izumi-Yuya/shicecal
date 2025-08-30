# Test Data Documentation

This document describes the comprehensive test data created by the seeders for the Facility Management System.

## Overview

The test data seeders create a complete dataset for testing all system functionality, including:
- Users with different roles and access scopes
- System settings with default values
- Sample facilities across different regions
- Test data for all major features

## Users Created

### Admin Users (2 users)
- **システム管理者** (admin@example.com) - Main system administrator
- **副管理者** (sub-admin@example.com) - Secondary administrator

### Editor Users (3 users)
- **編集者（東京）** (editor-tokyo@example.com) - Tokyo facility editor
- **編集者（大阪）** (editor-osaka@example.com) - Osaka facility editor  
- **編集者（全国）** (editor@example.com) - National facility editor

### Primary Responder Users (3 users)
- **一次対応者（東日本）** (responder-east@example.com) - East Japan responder
- **一次対応者（西日本）** (responder-west@example.com) - West Japan responder
- **一次対応者（全国）** (responder@example.com) - National responder

### Approver Users (2 users)
- **承認者（部長）** (approver-manager@example.com) - Manager level approver
- **承認者（課長）** (approver@example.com) - Section chief level approver

### Viewer Users (11 users)
- **閲覧者（全権限）** (viewer-all@example.com) - Full access viewer
- **地区担当者（関東）** (viewer-kanto@example.com) - Kanto region viewer
- **地区担当者（関西）** (viewer-kansai@example.com) - Kansai region viewer
- **地区担当者（中部）** (viewer-chubu@example.com) - Chubu region viewer
- **地区担当者（九州）** (viewer-kyushu@example.com) - Kyushu region viewer
- **地区担当者（北海道）** (viewer-hokkaido@example.com) - Hokkaido region viewer
- **部門責任者（営業）** (manager-sales@example.com) - Sales department manager
- **部門責任者（総務）** (manager-admin@example.com) - Admin department manager
- **閲覧者テスト** (viewer@example.com) - Legacy test viewer
- **地区担当者テスト** (regional@example.com) - Legacy regional viewer
- **部門責任者テスト** (manager@example.com) - Legacy manager viewer

**Default Password for all users:** `password`

## Access Scopes

Regional viewers have restricted access to specific prefectures:
- **Kanto**: 東京都, 神奈川県, 千葉県, 埼玉県
- **Kansai**: 大阪府, 京都府, 兵庫県, 奈良県
- **Chubu**: 愛知県, 静岡県, 岐阜県
- **Kyushu**: 福岡県, 熊本県, 鹿児島県
- **Hokkaido**: 北海道

## Facilities Created (19 facilities)

### Tokyo Area (3 facilities)
- 東京本社ビル (TKY001) - Approved
- 新宿支店 (TKY002) - Approved
- 渋谷営業所 (TKY003) - Approved

### Kanagawa Area (2 facilities)
- 横浜営業所 (YKH001) - Approved
- 川崎事業所 (KWS001) - Approved

### Osaka Area (2 facilities)
- 大阪支社 (OSK001) - Approved
- 難波営業所 (OSK002) - Approved

### Kyoto Area (1 facility)
- 京都事業所 (KYT001) - Pending Approval

### Hyogo Area (2 facilities)
- 神戸営業所 (KBE001) - Draft
- 姫路営業所 (HMJ001) - Approved

### Aichi Area (2 facilities)
- 名古屋支店 (NGY001) - Approved
- 豊田事業所 (TYD001) - Approved

### Fukuoka Area (2 facilities)
- 福岡営業所 (FKO001) - Approved
- 北九州営業所 (KKS001) - Approved

### Hokkaido Area (1 facility)
- 札幌営業所 (SPR001) - Approved

### Other Areas (4 facilities)
- 仙台営業所 (SND001) - Pending Approval
- 広島営業所 (HRS001) - Draft
- 静岡営業所 (SZK001) - Approved
- 金沢営業所 (KNZ001) - Approved

## System Settings (40 settings)

### Core System Settings
- approval_enabled: true
- system_maintenance_mode: false
- system_name: 施設管理システム
- system_version: 1.0.0

### File Management Settings
- max_file_size: 10240 (KB)
- allowed_file_types: pdf
- file_storage_path: facilities

### Security Settings
- session_timeout: 120 (minutes)
- password_min_length: 8
- login_attempt_limit: 5
- account_lockout_duration: 30 (minutes)
- ip_restriction_enabled: false

### Notification Settings
- notification_email_enabled: true
- notification_from_email: noreply@facility-system.com
- notification_from_name: 施設管理システム
- email_notification_delay: 5 (minutes)

### Export Settings
- csv_export_limit: 1000
- pdf_export_limit: 100
- export_timeout: 300 (seconds)
- csv_encoding: UTF-8

### PDF Settings
- pdf_password_protection: true
- pdf_default_password: facility2024
- pdf_print_permission: false
- pdf_copy_permission: false

### Data Retention Settings
- backup_retention_days: 30
- log_retention_days: 90
- activity_log_retention_days: 365
- notification_retention_days: 30

### Annual Confirmation Settings
- annual_confirmation_enabled: true
- annual_confirmation_period: 30 (days)
- annual_confirmation_reminder_days: 7,3,1

### UI/UX Settings
- items_per_page: 20
- search_results_limit: 500
- auto_save_interval: 300 (seconds)
- theme_color: #007bff

### Maintenance Settings
- maintenance_history_retention_years: 5
- maintenance_cost_alert_threshold: 100000 (yen)

### Comment Settings
- comment_auto_assignment: true
- comment_response_deadline_days: 7

## Test Data Created

### Comments (4 comments)
- Comments on facility names, phone numbers, addresses, and designation numbers
- Different statuses: pending, in_progress, resolved
- Assigned to primary responders

### Export Favorites (3 favorites)
- Basic information export
- All fields export
- Regional facility export

### Maintenance Histories (5 records)
- Air conditioning filter replacement
- Fire safety equipment inspection
- Electrical equipment inspection
- Water supply and drainage cleaning
- Elevator inspection

### Maintenance Search Favorites (3 favorites)
- Current month inspection work
- High-cost maintenance
- Equipment-related work

### Notifications (2 notifications)
- Comment posted notification
- Comment status changed notification

### Annual Confirmations (8 confirmations)
- Completed confirmations for previous year
- Pending confirmations for current year
- Discrepancy reported confirmations

### Activity Logs (100 logs)
- Various user actions over the past 30 days
- Login/logout activities
- Facility operations
- File operations
- Export operations
- System administration activities

## Usage for Testing

### Login Credentials
Use any of the created users with password `password` to test different role functionalities.

### Testing Different Roles
- **Admin**: Use admin@example.com for full system administration
- **Editor**: Use editor@example.com for facility editing
- **Approver**: Use approver@example.com for approval workflows
- **Primary Responder**: Use responder@example.com for comment management
- **Viewer**: Use viewer-kanto@example.com for regional access testing

### Testing Features
- **Facility Management**: Various facilities with different statuses
- **Comment System**: Pre-existing comments with different statuses
- **Export Functions**: Pre-configured export favorites
- **Maintenance History**: Sample maintenance records
- **Annual Confirmations**: Different confirmation statuses
- **Activity Logging**: Historical activity data
- **Notifications**: Sample notification data

### Resetting Test Data
To reset all test data:
```bash
php artisan migrate:fresh --seed
```

To seed only specific data:
```bash
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=SystemSettingsSeeder
php artisan db:seed --class=FacilitySeeder
php artisan db:seed --class=TestDataSeeder
```

## Notes

- All timestamps are relative to the seeding time
- Random elements (like IP addresses and user agents) will vary between seeding runs
- The data is designed to test all major system functionality
- Regional access restrictions are properly configured for testing
- All required relationships and foreign keys are properly maintained