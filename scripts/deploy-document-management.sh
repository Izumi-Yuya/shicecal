#!/bin/bash

# Document Management System Deployment Script
# Usage: ./scripts/deploy-document-management.sh [environment]
# Environments: test, development, staging, production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default environment
ENVIRONMENT=${1:-development}

# Logging function
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

# Validate environment
validate_environment() {
    case $ENVIRONMENT in
        test|testing)
            ENVIRONMENT="testing"
            ;;
        dev|development|local)
            ENVIRONMENT="local"
            ;;
        stage|staging)
            ENVIRONMENT="staging"
            ;;
        prod|production)
            ENVIRONMENT="production"
            ;;
        *)
            error "Invalid environment: $ENVIRONMENT"
            error "Valid environments: test, development, staging, production"
            exit 1
            ;;
    esac
}

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites for $ENVIRONMENT environment..."
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed or not in PATH"
        exit 1
    fi
    
    # Check Node.js and npm
    if ! command -v node &> /dev/null; then
        error "Node.js is not installed or not in PATH"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        error "npm is not installed or not in PATH"
        exit 1
    fi
    
    # Check environment file
    if [ ! -f ".env.$ENVIRONMENT" ] && [ ! -f ".env" ]; then
        error "Environment file not found (.env.$ENVIRONMENT or .env)"
        exit 1
    fi
    
    success "Prerequisites check passed"
}

# Backup database (production only)
backup_database() {
    if [ "$ENVIRONMENT" = "production" ]; then
        log "Creating database backup..."
        
        # Load environment variables
        if [ -f ".env.$ENVIRONMENT" ]; then
            source .env.$ENVIRONMENT
        else
            source .env
        fi
        
        BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
        
        if [ "$DB_CONNECTION" = "mysql" ]; then
            mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "storage/backups/$BACKUP_FILE"
            success "Database backup created: storage/backups/$BACKUP_FILE"
        else
            warning "Database backup skipped (not MySQL)"
        fi
    fi
}

# Install dependencies
install_dependencies() {
    log "Installing dependencies for $ENVIRONMENT..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        # Production dependencies
        composer install --no-dev --optimize-autoloader --no-interaction
        npm ci --production
    else
        # Development dependencies
        composer install
        npm install
    fi
    
    success "Dependencies installed"
}

# Build assets
build_assets() {
    log "Building assets for $ENVIRONMENT..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        npm run build
    else
        npm run dev
    fi
    
    success "Assets built"
}

# Setup environment
setup_environment() {
    log "Setting up environment configuration..."
    
    # Copy environment file if needed
    if [ -f ".env.$ENVIRONMENT" ]; then
        cp ".env.$ENVIRONMENT" .env
        log "Environment file copied from .env.$ENVIRONMENT"
    fi
    
    # Generate app key if needed
    if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d'=' -f2)" ]; then
        php artisan key:generate --force
        log "Application key generated"
    fi
    
    success "Environment setup completed"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    if [ "$ENVIRONMENT" = "testing" ]; then
        # Fresh migrations with seeding for testing
        php artisan migrate:fresh --seed --force
    else
        # Regular migrations
        php artisan migrate --force
    fi
    
    success "Database migrations completed"
}

# Setup storage
setup_storage() {
    log "Setting up storage for $ENVIRONMENT..."
    
    # Create storage directories
    mkdir -p storage/app/public/documents
    mkdir -p storage/logs
    mkdir -p storage/backups
    
    if [ "$ENVIRONMENT" = "local" ] || [ "$ENVIRONMENT" = "testing" ]; then
        # Create storage link for local environments
        php artisan storage:link
        
        # Set permissions
        chmod -R 755 storage/app/public/documents
        if [ "$(whoami)" = "root" ]; then
            chown -R www-data:www-data storage/app/public/documents
        fi
    fi
    
    # Test storage configuration
    php artisan tinker --execute="
        try {
            \Storage::disk('documents')->put('test.txt', 'test');
            \Storage::disk('documents')->delete('test.txt');
            echo 'Storage test: PASSED\n';
        } catch (Exception \$e) {
            echo 'Storage test: FAILED - ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "
    
    success "Storage setup completed"
}

# Optimize application
optimize_application() {
    log "Optimizing application for $ENVIRONMENT..."
    
    # Clear all caches first
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        # Cache for production
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan optimize
    fi
    
    success "Application optimization completed"
}

# Run tests
run_tests() {
    if [ "$ENVIRONMENT" = "testing" ] || [ "$ENVIRONMENT" = "local" ]; then
        log "Running tests..."
        
        # Run PHP tests
        php artisan test --env=testing
        
        # Run JavaScript tests
        npm test --run
        
        success "All tests passed"
    else
        log "Skipping tests for $ENVIRONMENT environment"
    fi
}

# Verify deployment
verify_deployment() {
    log "Verifying deployment..."
    
    # Check if application is accessible
    if command -v curl &> /dev/null; then
        APP_URL=$(grep APP_URL= .env | cut -d'=' -f2)
        if [ -n "$APP_URL" ]; then
            HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL" || echo "000")
            if [ "$HTTP_CODE" = "200" ]; then
                success "Application is accessible (HTTP $HTTP_CODE)"
            else
                warning "Application returned HTTP $HTTP_CODE"
            fi
        fi
    fi
    
    # Check database connection
    php artisan tinker --execute="
        try {
            \DB::connection()->getPdo();
            echo 'Database connection: OK\n';
        } catch (Exception \$e) {
            echo 'Database connection: FAILED - ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "
    
    # Check document management routes
    php artisan route:list | grep -q "documents" && success "Document routes registered" || error "Document routes not found"
    
    success "Deployment verification completed"
}

# Restart services (production only)
restart_services() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "staging" ]; then
        log "Restarting services..."
        
        # Restart PHP-FPM
        if command -v systemctl &> /dev/null; then
            if systemctl is-active --quiet php8.2-fpm; then
                sudo systemctl reload php8.2-fpm
                log "PHP-FPM reloaded"
            fi
            
            # Restart Nginx
            if systemctl is-active --quiet nginx; then
                sudo systemctl reload nginx
                log "Nginx reloaded"
            fi
        fi
        
        success "Services restarted"
    fi
}

# Main deployment function
deploy() {
    log "Starting deployment to $ENVIRONMENT environment..."
    
    validate_environment
    check_prerequisites
    backup_database
    install_dependencies
    build_assets
    setup_environment
    run_migrations
    setup_storage
    optimize_application
    run_tests
    verify_deployment
    restart_services
    
    success "Deployment to $ENVIRONMENT completed successfully!"
    
    # Show post-deployment information
    echo ""
    echo "=== Post-Deployment Information ==="
    echo "Environment: $ENVIRONMENT"
    echo "PHP Version: $(php -v | head -n1)"
    echo "Laravel Version: $(php artisan --version)"
    echo "Node.js Version: $(node -v)"
    echo "npm Version: $(npm -v)"
    echo ""
    echo "Next steps:"
    echo "1. Verify the application is working correctly"
    echo "2. Check logs for any errors: tail -f storage/logs/laravel.log"
    echo "3. Monitor system performance"
    if [ "$ENVIRONMENT" = "production" ]; then
        echo "4. Update monitoring and alerting systems"
        echo "5. Notify stakeholders of successful deployment"
    fi
}

# Handle script interruption
cleanup() {
    error "Deployment interrupted!"
    exit 1
}

trap cleanup INT TERM

# Run deployment
deploy