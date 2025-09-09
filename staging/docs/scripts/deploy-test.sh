#!/bin/bash

# Shise-Cal Test Environment Deployment Script
# This script handles the deployment process for the test environment

set -e

echo "🧪 Starting Shise-Cal test environment deployment..."

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Create backup directory if it doesn't exist
mkdir -p storage/app/backups

# Backup current deployment (if exists)
if [ -d "app" ]; then
    echo "📦 Creating backup..."
    BACKUP_NAME="test_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "storage/app/backups/$BACKUP_NAME"
    cp -r app config database routes resources "storage/app/backups/$BACKUP_NAME/" 2>/dev/null || true
    echo "✅ Backup created: storage/app/backups/$BACKUP_NAME"
fi

# Copy test environment configuration
echo "🔧 Setting up test environment configuration..."
if [ -f ".env.testing" ]; then
    cp .env.testing .env
    echo "✅ Test environment configuration applied"
else
    echo "⚠️  Warning: .env.testing not found, using .env.example"
    cp .env.example .env
fi

# Update dependencies (including dev dependencies for testing)
echo "📥 Installing dependencies..."
composer install --optimize-autoloader --no-interaction

# Install Node.js dependencies and build assets
echo "🎨 Building frontend assets..."
if command -v npm &> /dev/null; then
    npm install
    npm run build
    echo "✅ Frontend assets built successfully"
else
    echo "⚠️  Warning: npm not found, skipping frontend build"
fi

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Generate application key if not set
if grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Run database migrations with seeding for test data
echo "🗄️ Setting up test database..."
php artisan migrate:fresh --seed --force

# Create symbolic link for storage (if not exists)
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symbolic link..."
    php artisan storage:link
fi

# Set proper permissions for test environment
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
if command -v chown &> /dev/null; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Cache configurations for better performance
echo "⚡ Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run tests to verify deployment
echo "🧪 Running tests to verify deployment..."
if php artisan test --testsuite=Feature --stop-on-failure; then
    echo "✅ All tests passed!"
else
    echo "❌ Some tests failed. Please check the output above."
    exit 1
fi

echo ""
echo "✅ Test environment deployment completed successfully!"
echo ""
echo "📋 Test environment information:"
echo "  - Environment: Testing"
echo "  - Debug mode: Enabled"
echo "  - Database: shisecal_testing"
echo "  - URL: $(grep APP_URL .env | cut -d '=' -f2)"
echo ""
echo "🔍 Useful test commands:"
echo "  - Run all tests: php artisan test"
echo "  - Run feature tests: php artisan test --testsuite=Feature"
echo "  - Run unit tests: php artisan test --testsuite=Unit"
echo "  - Check logs: tail -f storage/logs/laravel.log"
echo "  - Reset test data: php artisan migrate:fresh --seed"
echo ""
echo "🌐 Access the application:"
echo "  - Admin user: admin@shisecal.com / password"
echo "  - Test user: test@shisecal.com / password"