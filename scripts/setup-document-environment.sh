#!/bin/bash

# Document Management Environment Setup Script
# This script sets up the environment for document management system

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

ENVIRONMENT=${1:-local}

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Create environment-specific .env file
create_env_file() {
    log "Creating environment file for $ENVIRONMENT..."
    
    case $ENVIRONMENT in
        testing)
            cat > .env.testing << 'EOF'
APP_NAME="Shise-Cal (Testing)"
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

BROADCAST_DRIVER=log
CACHE_DRIVER=array
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
SESSION_LIFETIME=120

# Document Management - Testing
DOCUMENTS_STORAGE_DRIVER=local
DOCUMENTS_MAX_FILE_SIZE=5120
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,txt,jpg,png
DOCUMENTS_ENABLE_VIRUS_SCAN=false
DOCUMENTS_ENABLE_WATERMARK=false
DOCUMENTS_ENABLE_CACHING=false
EOF
            ;;
            
        local)
            cat > .env.local << 'EOF'
APP_NAME="Shise-Cal (Development)"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shicecal_dev
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Document Management - Development
DOCUMENTS_STORAGE_DRIVER=local
DOCUMENTS_MAX_FILE_SIZE=10240
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar
DOCUMENTS_ENABLE_VIRUS_SCAN=false
DOCUMENTS_ENABLE_WATERMARK=true
DOCUMENTS_WATERMARK_TEXT="DEVELOPMENT"
DOCUMENTS_ENABLE_CACHING=true
DOCUMENTS_CACHE_TTL=300
EOF
            ;;
            
        staging)
            cat > .env.staging << 'EOF'
APP_NAME="Shise-Cal (Staging)"
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://staging.your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=your-staging-db-host
DB_PORT=3306
DB_DATABASE=shicecal_staging
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# AWS S3 - Staging
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=shicecal-staging
AWS_DOCUMENTS_BUCKET=shicecal-documents-staging
AWS_USE_PATH_STYLE_ENDPOINT=false

# Document Management - Staging
DOCUMENTS_STORAGE_DRIVER=s3
DOCUMENTS_MAX_FILE_SIZE=20480
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar,7z
DOCUMENTS_ENABLE_VIRUS_SCAN=true
DOCUMENTS_ENABLE_WATERMARK=true
DOCUMENTS_WATERMARK_TEXT="STAGING"
DOCUMENTS_ENABLE_CACHING=true
DOCUMENTS_CACHE_TTL=1800
DOCUMENTS_ENABLE_ENCRYPTION=true
EOF
            ;;
            
        production)
            cat > .env.production << 'EOF'
APP_NAME="Shise-Cal"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=your-production-db-host
DB_PORT=3306
DB_DATABASE=shicecal_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=s3
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# AWS S3 - Production
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=shicecal-production
AWS_DOCUMENTS_BUCKET=shicecal-documents-prod
AWS_USE_PATH_STYLE_ENDPOINT=false

# Document Management - Production
DOCUMENTS_STORAGE_DRIVER=s3
DOCUMENTS_MAX_FILE_SIZE=51200
DOCUMENTS_ALLOWED_EXTENSIONS=pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,bmp,svg,zip,rar,7z,mp4,avi,mov
DOCUMENTS_ENABLE_VIRUS_SCAN=true
DOCUMENTS_ENABLE_WATERMARK=true
DOCUMENTS_WATERMARK_TEXT="CONFIDENTIAL"
DOCUMENTS_ENABLE_CACHING=true
DOCUMENTS_CACHE_TTL=3600
DOCUMENTS_ENABLE_ENCRYPTION=true
DOCUMENTS_ENABLE_CDN=true
DOCUMENTS_CDN_URL=https://d1234567890.cloudfront.net

# Monitoring
DOCUMENTS_ENABLE_METRICS=true
DOCUMENTS_LOG_LEVEL=warning
EOF
            ;;
    esac
    
    success "Environment file created: .env.$ENVIRONMENT"
}

# Setup storage directories
setup_storage() {
    log "Setting up storage directories..."
    
    # Create required directories
    mkdir -p storage/app/public/documents
    mkdir -p storage/logs
    mkdir -p storage/backups
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    
    # Set permissions
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    success "Storage directories created and configured"
}

# Setup database
setup_database() {
    log "Setting up database for $ENVIRONMENT..."
    
    case $ENVIRONMENT in
        testing)
            # SQLite - no setup needed for in-memory database
            success "SQLite in-memory database configured"
            ;;
            
        local)
            # MySQL - create database if it doesn't exist
            if command -v mysql &> /dev/null; then
                mysql -u root -e "CREATE DATABASE IF NOT EXISTS shicecal_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || warning "Could not create database (may already exist or no permissions)"
                success "MySQL database configured"
            else
                warning "MySQL not found - please create database manually"
            fi
            ;;
            
        staging|production)
            warning "Database setup for $ENVIRONMENT should be done manually"
            warning "Please ensure the database exists and credentials are correct"
            ;;
    esac
}

# Setup AWS S3 (for staging/production)
setup_s3() {
    if [ "$ENVIRONMENT" = "staging" ] || [ "$ENVIRONMENT" = "production" ]; then
        log "Setting up AWS S3 configuration..."
        
        if command -v aws &> /dev/null; then
            # Check AWS credentials
            if aws sts get-caller-identity &> /dev/null; then
                success "AWS credentials are configured"
                
                # Create S3 bucket if it doesn't exist
                BUCKET_NAME="shicecal-documents-$ENVIRONMENT"
                if aws s3 ls "s3://$BUCKET_NAME" &> /dev/null; then
                    success "S3 bucket $BUCKET_NAME exists"
                else
                    warning "S3 bucket $BUCKET_NAME does not exist - please create it manually"
                fi
            else
                warning "AWS credentials not configured - please run 'aws configure'"
            fi
        else
            warning "AWS CLI not installed - please install it for S3 functionality"
        fi
    fi
}

# Install PHP dependencies
install_php_dependencies() {
    log "Installing PHP dependencies..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        composer install
    fi
    
    success "PHP dependencies installed"
}

# Install Node.js dependencies
install_node_dependencies() {
    log "Installing Node.js dependencies..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        npm ci --production
    else
        npm install
    fi
    
    success "Node.js dependencies installed"
}

# Generate application key
generate_app_key() {
    log "Generating application key..."
    
    # Copy environment file
    cp ".env.$ENVIRONMENT" .env
    
    # Generate key
    php artisan key:generate --force
    
    # Copy back to environment-specific file
    cp .env ".env.$ENVIRONMENT"
    
    success "Application key generated"
}

# Run migrations
run_migrations() {
    log "Running database migrations..."
    
    # Use environment-specific file
    cp ".env.$ENVIRONMENT" .env
    
    if [ "$ENVIRONMENT" = "testing" ]; then
        php artisan migrate:fresh --seed --force
    else
        php artisan migrate --force
    fi
    
    success "Database migrations completed"
}

# Build assets
build_assets() {
    log "Building frontend assets..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        npm run build
    else
        npm run dev
    fi
    
    success "Frontend assets built"
}

# Create storage link (for local environments)
create_storage_link() {
    if [ "$ENVIRONMENT" = "local" ] || [ "$ENVIRONMENT" = "testing" ]; then
        log "Creating storage link..."
        
        php artisan storage:link
        
        success "Storage link created"
    fi
}

# Verify setup
verify_setup() {
    log "Verifying setup..."
    
    # Use environment-specific file
    cp ".env.$ENVIRONMENT" .env
    
    # Test database connection
    php artisan tinker --execute="
        try {
            \DB::connection()->getPdo();
            echo 'Database connection: OK\n';
        } catch (Exception \$e) {
            echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "
    
    # Test storage
    php artisan tinker --execute="
        try {
            \Storage::disk('documents')->put('test.txt', 'test');
            \Storage::disk('documents')->delete('test.txt');
            echo 'Storage test: OK\n';
        } catch (Exception \$e) {
            echo 'Storage test: FAILED - ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "
    
    # Run migration verification
    if [ -f "scripts/verify-document-migrations.php" ]; then
        php scripts/verify-document-migrations.php
    fi
    
    success "Setup verification completed"
}

# Main setup function
main() {
    echo "Document Management Environment Setup"
    echo "===================================="
    echo "Environment: $ENVIRONMENT"
    echo ""
    
    create_env_file
    setup_storage
    setup_database
    setup_s3
    install_php_dependencies
    install_node_dependencies
    generate_app_key
    run_migrations
    build_assets
    create_storage_link
    verify_setup
    
    echo ""
    success "Environment setup completed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Review the generated .env.$ENVIRONMENT file"
    echo "2. Update any placeholder values (database credentials, AWS keys, etc.)"
    echo "3. Test the application: php artisan serve"
    echo "4. Run tests: php artisan test"
    echo ""
    echo "Environment file location: .env.$ENVIRONMENT"
}

# Validate environment parameter
case $ENVIRONMENT in
    testing|local|staging|production)
        main
        ;;
    *)
        error "Invalid environment: $ENVIRONMENT"
        error "Valid environments: testing, local, staging, production"
        exit 1
        ;;
esac