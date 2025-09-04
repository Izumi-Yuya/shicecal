#!/bin/bash

# Laravel Deployment Script for AWS EC2
# Run this script after uploading your Laravel project to the server

set -e

PROJECT_PATH="/var/www/facility-management"
WEB_USER="www-data"

echo "🚀 Starting Laravel deployment..."

# Navigate to project directory
cd $PROJECT_PATH

echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

echo "🔧 Setting up environment..."
# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚠️  Please edit .env file with your database credentials"
fi

# Generate application key
php artisan key:generate

echo "🎨 Building frontend assets..."
npm install
npm run build

echo "🔐 Setting correct permissions..."
sudo chown -R $WEB_USER:$WEB_USER $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 775 $PROJECT_PATH/storage
sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache

echo "🗄️  Running database migrations..."
php artisan migrate --force

echo "🌱 Running database seeders..."
php artisan db:seed --force

echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel deployment completed!"
echo ""
echo "🔍 Testing application..."
php artisan --version

echo ""
echo "📋 Next steps:"
echo "1. Configure your .env file with correct database settings"
echo "2. Set up Nginx virtual host"
echo "3. Test the application in browser"
echo "4. Configure SSL certificate for production"