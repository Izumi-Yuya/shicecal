#!/bin/bash

# Shise-Cal Production Deployment Script
# This script handles the deployment process for the facility management system

set -e

echo "🚀 Starting Shise-Cal deployment..."

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Backup current deployment (if exists)
if [ -d "storage/app/backups" ]; then
    echo "📦 Creating backup..."
    BACKUP_NAME="backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "storage/app/backups/$BACKUP_NAME"
    cp -r app config database routes resources "storage/app/backups/$BACKUP_NAME/"
    echo "✅ Backup created: storage/app/backups/$BACKUP_NAME"
fi

# Update dependencies
echo "📥 Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Cache configurations for production
echo "⚡ Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize application
echo "🔧 Optimizing application..."
php artisan optimize

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create symbolic link for storage (if not exists)
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symbolic link..."
    php artisan storage:link
fi

# Restart services (uncomment as needed)
# echo "🔄 Restarting services..."
# sudo systemctl restart nginx
# sudo systemctl restart php8.1-fpm
# sudo supervisorctl restart all

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Post-deployment checklist:"
echo "  - Verify application is accessible"
echo "  - Check logs for any errors"
echo "  - Test critical functionality"
echo "  - Monitor system resources"
echo ""
echo "🔍 Useful commands:"
echo "  - Check logs: tail -f storage/logs/laravel.log"
echo "  - Monitor queue: php artisan queue:work"
echo "  - Clear cache: php artisan cache:clear"