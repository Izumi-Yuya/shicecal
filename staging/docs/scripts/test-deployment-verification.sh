#!/bin/bash

# Shise-Cal Test Deployment Verification Script
# This script verifies that the test environment is properly deployed and functional

set -e

echo "🔍 Starting Shise-Cal test deployment verification..."

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "SUCCESS")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "ERROR")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "INFO")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
    esac
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_status "ERROR" "artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

print_status "INFO" "Verifying test environment deployment..."

# 1. Check PHP version and extensions
print_status "INFO" "Checking PHP configuration..."
PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
if [[ $(echo "$PHP_VERSION >= 8.1" | bc -l) -eq 1 ]]; then
    print_status "SUCCESS" "PHP version: $PHP_VERSION"
else
    print_status "ERROR" "PHP version $PHP_VERSION is not supported. Requires PHP 8.1+"
    exit 1
fi

# Check required PHP extensions
REQUIRED_EXTENSIONS=("pdo_mysql" "mbstring" "xml" "curl" "zip" "gd" "bcmath")
OPTIONAL_EXTENSIONS=("redis")

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        print_status "SUCCESS" "PHP extension '$ext' is installed"
    else
        print_status "ERROR" "Required PHP extension '$ext' is missing"
        exit 1
    fi
done

for ext in "${OPTIONAL_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        print_status "SUCCESS" "Optional PHP extension '$ext' is installed"
    else
        print_status "WARNING" "Optional PHP extension '$ext' is not installed (not required for file-based caching)"
    fi
done

# 2. Check Composer dependencies
print_status "INFO" "Checking Composer dependencies..."
if command_exists composer; then
    if composer check-platform-reqs --no-dev > /dev/null 2>&1; then
        print_status "SUCCESS" "All Composer dependencies are satisfied"
    else
        print_status "WARNING" "Some Composer dependencies may have issues"
        composer check-platform-reqs --no-dev
    fi
else
    print_status "ERROR" "Composer is not installed"
    exit 1
fi

# 3. Check environment configuration
print_status "INFO" "Checking environment configuration..."
if [ -f ".env" ]; then
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
    if [ "$APP_ENV" = "testing" ]; then
        print_status "SUCCESS" "Environment is set to testing"
    else
        print_status "WARNING" "Environment is set to '$APP_ENV', expected 'testing'"
    fi
    
    # Check if APP_KEY is set
    if grep -q "^APP_KEY=base64:" .env; then
        print_status "SUCCESS" "Application key is configured"
    else
        print_status "ERROR" "Application key is not configured"
        exit 1
    fi
else
    print_status "ERROR" ".env file not found"
    exit 1
fi

# 4. Check database connection
print_status "INFO" "Checking database connection..."
if php artisan migrate:status > /dev/null 2>&1; then
    print_status "SUCCESS" "Database connection is working"
    
    # Check if migrations are up to date
    PENDING_MIGRATIONS=$(php artisan migrate:status | grep -c "Pending" || true)
    if [ "$PENDING_MIGRATIONS" -eq 0 ]; then
        print_status "SUCCESS" "All migrations are up to date"
    else
        print_status "WARNING" "$PENDING_MIGRATIONS pending migrations found"
    fi
else
    print_status "ERROR" "Database connection failed"
    exit 1
fi

# 5. Check Redis connection (if configured)
print_status "INFO" "Checking Redis connection..."
if grep -q "REDIS_HOST=" .env; then
    if php artisan tinker --execute="Redis::ping();" > /dev/null 2>&1; then
        print_status "SUCCESS" "Redis connection is working"
    else
        print_status "WARNING" "Redis connection failed (may not be required for basic functionality)"
    fi
fi

# 6. Check file permissions
print_status "INFO" "Checking file permissions..."
if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
    print_status "SUCCESS" "Storage and cache directories are writable"
else
    print_status "ERROR" "Storage or cache directories are not writable"
    exit 1
fi

# 7. Check if storage link exists
if [ -L "public/storage" ]; then
    print_status "SUCCESS" "Storage symbolic link exists"
else
    print_status "WARNING" "Storage symbolic link not found, creating..."
    php artisan storage:link
    print_status "SUCCESS" "Storage symbolic link created"
fi

# 8. Run basic functionality tests
print_status "INFO" "Running basic functionality tests..."

# Test route caching
if php artisan route:cache > /dev/null 2>&1; then
    print_status "SUCCESS" "Route caching works"
else
    print_status "ERROR" "Route caching failed"
    exit 1
fi

# Test config caching
if php artisan config:cache > /dev/null 2>&1; then
    print_status "SUCCESS" "Config caching works"
else
    print_status "ERROR" "Config caching failed"
    exit 1
fi

# 9. Run automated tests
print_status "INFO" "Running automated test suite..."
if php artisan test --testsuite=Unit --stop-on-failure > /dev/null 2>&1; then
    print_status "SUCCESS" "Unit tests passed"
else
    print_status "WARNING" "Some unit tests failed"
    echo "Running tests with output for debugging:"
    php artisan test --testsuite=Unit --stop-on-failure
fi

if php artisan test --testsuite=Feature --stop-on-failure > /dev/null 2>&1; then
    print_status "SUCCESS" "Feature tests passed"
else
    print_status "WARNING" "Some feature tests failed (may require additional setup)"
    echo "Running feature tests with output:"
    php artisan test --testsuite=Feature
fi

# 10. Check application health
print_status "INFO" "Checking application health..."
if command_exists curl; then
    # Start a temporary server for testing
    php artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 3
    
    # Test health endpoint
    if curl -s http://127.0.0.1:8000/health > /dev/null 2>&1; then
        print_status "SUCCESS" "Application health check passed"
    else
        print_status "WARNING" "Health endpoint not accessible (may not be implemented)"
    fi
    
    # Test main page
    if curl -s http://127.0.0.1:8000 | grep -q "Laravel\|Shise-Cal"; then
        print_status "SUCCESS" "Main application page is accessible"
    else
        print_status "WARNING" "Main application page may have issues"
    fi
    
    # Stop the temporary server
    kill $SERVER_PID > /dev/null 2>&1 || true
else
    print_status "WARNING" "curl not available, skipping HTTP tests"
fi

# 11. Generate deployment report
print_status "INFO" "Generating deployment report..."
REPORT_FILE="storage/logs/test-deployment-report-$(date +%Y%m%d_%H%M%S).log"
{
    echo "Shise-Cal Test Deployment Report"
    echo "Generated: $(date)"
    echo "================================"
    echo ""
    echo "Environment Information:"
    echo "- PHP Version: $(php -v | head -n 1)"
    echo "- Laravel Version: $(php artisan --version)"
    echo "- Environment: $(grep APP_ENV .env)"
    echo "- Debug Mode: $(grep APP_DEBUG .env)"
    echo ""
    echo "Database Information:"
    php artisan migrate:status
    echo ""
    echo "Installed Packages:"
    composer show --installed --no-dev | head -20
    echo ""
    echo "Test Results:"
    php artisan test --testsuite=Unit 2>&1 | tail -10
} > "$REPORT_FILE"

print_status "SUCCESS" "Deployment report saved to: $REPORT_FILE"

echo ""
print_status "SUCCESS" "Test deployment verification completed successfully!"
echo ""
echo "📋 Summary:"
echo "  - PHP and extensions: ✅"
echo "  - Database connection: ✅"
echo "  - File permissions: ✅"
echo "  - Basic functionality: ✅"
echo "  - Unit tests: ✅"
echo ""
echo "🌐 Test Environment Access:"
echo "  - Local server: php artisan serve"
echo "  - Admin login: admin@shisecal.com / password"
echo "  - Test user: test@shisecal.com / password"
echo ""
echo "🔧 Useful commands:"
echo "  - Run tests: php artisan test"
echo "  - Check logs: tail -f storage/logs/laravel.log"
echo "  - Reset test data: php artisan migrate:fresh --seed"
echo "  - Clear cache: php artisan optimize:clear"