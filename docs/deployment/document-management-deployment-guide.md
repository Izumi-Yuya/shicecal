# Document Management System Deployment Guide

## Overview
This guide provides comprehensive instructions for deploying the Document Management System across different environments (Test, Development, and Production).

## Environment Configurations

### Test Environment (SQLite + Local Storage)

#### Database Configuration
```php
// config/database.php
'connections' => [
    'sqlite' => [
        'driver' => 'sqlite',
        'url' => env('DATABASE_URL'),
        'database' => env('DB_DATABASE', database_path('database.sqlite')),
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    ],
],
```

#### Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/public/documents'),
        'url' => env('APP_URL').'/storage/documents',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

#### Environment Variables (.env.testing)
```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Storage
DOCUMENTS_STORAGE_DRIVER=local
DOCUMENTS_STORAGE_ROOT=storage/app/public/documents
DOCUMENTS_MAX_FILE_SIZE=10240
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar,7z

# Security
DOCUMENTS_ENABLE_VIRUS_SCAN=false
DOCUMENTS_ENABLE_WATERMARK=false
```

### Development Environment (MySQL + Local Storage)

#### Database Configuration
```php
// config/database.php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
],
```

#### Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/public/documents'),
        'url' => env('APP_URL').'/storage/documents',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

#### Environment Variables (.env.development)
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shicecal_dev
DB_USERNAME=root
DB_PASSWORD=

# Storage
DOCUMENTS_STORAGE_DRIVER=local
DOCUMENTS_STORAGE_ROOT=storage/app/public/documents
DOCUMENTS_MAX_FILE_SIZE=10240
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar,7z

# Security
DOCUMENTS_ENABLE_VIRUS_SCAN=false
DOCUMENTS_ENABLE_WATERMARK=true
DOCUMENTS_WATERMARK_TEXT="DEVELOPMENT"

# Performance
DOCUMENTS_ENABLE_CACHING=true
DOCUMENTS_CACHE_TTL=300
```

### Production Environment (MySQL + AWS S3)

#### Database Configuration
```php
// config/database.php - Same as development but with production credentials
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
],
```

#### S3 Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'documents' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_DOCUMENTS_BUCKET'),
        'url' => env('AWS_DOCUMENTS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
        'visibility' => 'private', // Important for security
    ],
    
    'documents_public' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_DOCUMENTS_PUBLIC_BUCKET'),
        'url' => env('AWS_DOCUMENTS_PUBLIC_URL'),
        'visibility' => 'public',
    ],
],
```

#### Environment Variables (.env.production)
```env
# Database
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=3306
DB_DATABASE=shicecal_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# AWS S3 Storage
DOCUMENTS_STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_DOCUMENTS_BUCKET=shicecal-documents-prod
AWS_DOCUMENTS_PUBLIC_BUCKET=shicecal-documents-public-prod
AWS_DOCUMENTS_URL=https://shicecal-documents-prod.s3.ap-northeast-1.amazonaws.com
AWS_DOCUMENTS_PUBLIC_URL=https://shicecal-documents-public-prod.s3.ap-northeast-1.amazonaws.com

# File Limits
DOCUMENTS_MAX_FILE_SIZE=20480
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar,7z

# Security
DOCUMENTS_ENABLE_VIRUS_SCAN=true
DOCUMENTS_ENABLE_WATERMARK=true
DOCUMENTS_WATERMARK_TEXT="CONFIDENTIAL"
DOCUMENTS_ENABLE_ENCRYPTION=true

# Performance
DOCUMENTS_ENABLE_CACHING=true
DOCUMENTS_CACHE_TTL=3600
DOCUMENTS_ENABLE_CDN=true
DOCUMENTS_CDN_URL=https://d1234567890.cloudfront.net

# Monitoring
DOCUMENTS_ENABLE_METRICS=true
DOCUMENTS_LOG_LEVEL=warning
```

## Deployment Steps

### 1. Pre-deployment Checklist

#### Code Preparation
- [ ] All tests pass (`php artisan test`)
- [ ] Code is linted (`./vendor/bin/pint`)
- [ ] Assets are compiled (`npm run build`)
- [ ] Documentation is updated
- [ ] Version is tagged in Git

#### Database Preparation
- [ ] Backup existing database
- [ ] Review migration files
- [ ] Test migrations on staging environment
- [ ] Prepare rollback plan

#### Infrastructure Preparation
- [ ] AWS S3 buckets are created and configured
- [ ] IAM roles and policies are set up
- [ ] CloudFront distribution is configured (if using CDN)
- [ ] Monitoring and alerting are configured

### 2. Migration Execution

#### Step 1: Database Migrations
```bash
# Backup database first
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Run migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

#### Step 2: Storage Setup
```bash
# Create storage link (if using local storage)
php artisan storage:link

# Set proper permissions
chmod -R 755 storage/app/public/documents
chown -R www-data:www-data storage/app/public/documents

# Test S3 connection (if using S3)
php artisan tinker
>>> Storage::disk('documents')->put('test.txt', 'test content');
>>> Storage::disk('documents')->exists('test.txt');
>>> Storage::disk('documents')->delete('test.txt');
```

#### Step 3: Configuration Deployment
```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 3. Environment-Specific Deployment

#### Test Environment Deployment
```bash
#!/bin/bash
# deploy-test.sh

set -e

echo "Deploying to Test Environment..."

# Set environment
export APP_ENV=testing

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Database setup
php artisan migrate:fresh --seed --force

# Create test data
php artisan db:seed --class=DocumentTestDataSeeder

# Run tests
php artisan test --env=testing

echo "Test deployment completed successfully!"
```

#### Development Environment Deployment
```bash
#!/bin/bash
# deploy-dev.sh

set -e

echo "Deploying to Development Environment..."

# Set environment
export APP_ENV=local

# Install dependencies
composer install
npm install
npm run dev

# Database setup
php artisan migrate --force
php artisan db:seed --class=DevelopmentSeeder

# Create storage link
php artisan storage:link

# Clear caches
php artisan optimize:clear

echo "Development deployment completed successfully!"
```

#### Production Environment Deployment
```bash
#!/bin/bash
# deploy-prod.sh

set -e

echo "Deploying to Production Environment..."

# Set environment
export APP_ENV=production

# Backup database
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).sql

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production
npm run build

# Database migrations
php artisan migrate --force

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart services
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

# Verify deployment
php artisan health:check

echo "Production deployment completed successfully!"
```

### 4. Post-Deployment Verification

#### Functional Tests
```bash
# Test document operations
curl -X GET "https://your-domain.com/facilities/1/documents" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"

# Test file upload
curl -X POST "https://your-domain.com/facilities/1/documents/files" \
  -H "Authorization: Bearer $API_TOKEN" \
  -F "file=@test.pdf"

# Test file download
curl -X GET "https://your-domain.com/facilities/1/documents/files/1/download" \
  -H "Authorization: Bearer $API_TOKEN" \
  -o downloaded_file.pdf
```

#### Performance Tests
```bash
# Test response times
ab -n 100 -c 10 https://your-domain.com/facilities/1/documents

# Test file upload performance
for i in {1..10}; do
  time curl -X POST "https://your-domain.com/facilities/1/documents/files" \
    -H "Authorization: Bearer $API_TOKEN" \
    -F "file=@test_${i}.pdf"
done
```

#### Security Tests
```bash
# Test unauthorized access
curl -X GET "https://your-domain.com/facilities/1/documents" \
  -H "Accept: application/json" \
  -w "%{http_code}"

# Test file type validation
curl -X POST "https://your-domain.com/facilities/1/documents/files" \
  -H "Authorization: Bearer $API_TOKEN" \
  -F "file=@malicious.exe" \
  -w "%{http_code}"
```

## Rollback Procedures

### Database Rollback
```bash
# Rollback migrations
php artisan migrate:rollback --step=5

# Restore from backup
mysql -u username -p database_name < backup_20241201_120000.sql
```

### Application Rollback
```bash
# Switch to previous version
git checkout previous-stable-tag

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Clear caches
php artisan optimize:clear
php artisan optimize
```

### Storage Rollback
```bash
# Restore files from backup (if using local storage)
rsync -av backup/documents/ storage/app/public/documents/

# Restore S3 files (if using S3)
aws s3 sync s3://backup-bucket/documents/ s3://production-bucket/documents/
```

## Monitoring and Maintenance

### Health Checks
```php
// app/Console/Commands/DocumentHealthCheck.php
class DocumentHealthCheck extends Command
{
    public function handle()
    {
        // Check database connectivity
        $this->checkDatabase();
        
        // Check storage connectivity
        $this->checkStorage();
        
        // Check file permissions
        $this->checkPermissions();
        
        // Check disk space
        $this->checkDiskSpace();
    }
}
```

### Automated Backups
```bash
#!/bin/bash
# backup-documents.sh

# Database backup
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > /backups/db_$(date +%Y%m%d_%H%M%S).sql

# File backup (local storage)
tar -czf /backups/documents_$(date +%Y%m%d_%H%M%S).tar.gz storage/app/public/documents/

# S3 backup
aws s3 sync s3://production-bucket/documents/ s3://backup-bucket/documents/$(date +%Y%m%d)/

# Cleanup old backups (keep 30 days)
find /backups -name "*.sql" -mtime +30 -delete
find /backups -name "*.tar.gz" -mtime +30 -delete
```

### Performance Monitoring
```php
// config/logging.php - Add document-specific logging
'channels' => [
    'documents' => [
        'driver' => 'daily',
        'path' => storage_path('logs/documents.log'),
        'level' => env('DOCUMENTS_LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

## Troubleshooting

### Common Issues

#### File Upload Failures
```bash
# Check PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size
php -i | grep max_execution_time

# Check storage permissions
ls -la storage/app/public/documents/
```

#### S3 Connection Issues
```bash
# Test AWS credentials
aws sts get-caller-identity

# Test S3 access
aws s3 ls s3://your-bucket-name/

# Check IAM permissions
aws iam get-user-policy --user-name your-user --policy-name your-policy
```

#### Database Migration Issues
```bash
# Check migration status
php artisan migrate:status

# Reset migrations (development only)
php artisan migrate:fresh

# Rollback specific migration
php artisan migrate:rollback --step=1
```

### Performance Issues
```bash
# Check slow queries
mysql -u username -p -e "SHOW PROCESSLIST;"

# Analyze query performance
php artisan telescope:install  # If using Telescope

# Check storage performance
time dd if=/dev/zero of=storage/app/public/test bs=1M count=100
```

## Security Considerations

### File Security
- All uploaded files are scanned for malware (production)
- File types are strictly validated
- File paths are sanitized to prevent directory traversal
- Files are stored with secure permissions

### Access Control
- All document operations require authentication
- Policy-based authorization is enforced
- Cross-facility access is prevented
- Audit logs are maintained for all operations

### Data Protection
- Files are encrypted at rest (S3 with KMS)
- Data is transmitted over HTTPS
- Sensitive metadata is not exposed
- Regular security audits are performed

## Conclusion

This deployment guide provides comprehensive instructions for deploying the Document Management System across all environments. Follow the environment-specific configurations and deployment procedures to ensure a successful deployment.

For additional support or questions, refer to the project documentation or contact the development team.