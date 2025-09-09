#!/bin/bash

# Shise-Cal Development Environment Setup Script
# This script sets up the local development environment using Docker

set -e

echo "🚀 Setting up Shise-Cal development environment..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Copy environment file
echo "⚙️  Setting up environment configuration..."
if [ ! -f .env ]; then
    cp .env.development .env
    echo "✅ Environment file created from .env.development"
else
    echo "⚠️  .env file already exists, skipping..."
fi

# Build and start containers
echo "🐳 Building and starting Docker containers..."
docker-compose -f docker-compose.dev.yml up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 30

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
docker-compose -f docker-compose.dev.yml exec app composer install

# Generate application key
echo "🔑 Generating application key..."
docker-compose -f docker-compose.dev.yml exec app php artisan key:generate

# Run database migrations
echo "🗄️  Running database migrations..."
docker-compose -f docker-compose.dev.yml exec app php artisan migrate

# Seed database with test data
echo "🌱 Seeding database with test data..."
docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

# Install Node.js dependencies and build assets
echo "🎨 Installing Node.js dependencies and building assets..."
docker-compose -f docker-compose.dev.yml exec node npm install
docker-compose -f docker-compose.dev.yml exec node npm run build

# Set up MinIO bucket
echo "🪣 Setting up MinIO bucket..."
sleep 10
docker-compose -f docker-compose.dev.yml exec app php artisan storage:link

# Create symbolic link for storage
echo "🔗 Creating storage symbolic link..."
docker-compose -f docker-compose.dev.yml exec app php artisan storage:link

# Set proper permissions
echo "🔒 Setting proper permissions..."
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo ""
echo "✅ Development environment setup complete!"
echo ""
echo "🌐 Access points:"
echo "   - Application: http://localhost:8080"
echo "   - Laravel Dev Server: http://localhost:8000"
echo "   - MailHog (Email testing): http://localhost:8025"
echo "   - MinIO Console (File storage): http://localhost:9001"
echo "   - Database: localhost:3307 (user: shisecal_dev, password: dev_password)"
echo "   - Redis: localhost:6380"
echo ""
echo "🛠️  Development commands:"
echo "   - Start services: docker-compose -f docker-compose.dev.yml up -d"
echo "   - Stop services: docker-compose -f docker-compose.dev.yml down"
echo "   - View logs: docker-compose -f docker-compose.dev.yml logs -f"
echo "   - Run tests: docker-compose -f docker-compose.dev.yml exec app php artisan test"
echo "   - Access app container: docker-compose -f docker-compose.dev.yml exec app bash"
echo ""
echo "📚 Next steps:"
echo "   1. Open your browser and go to http://localhost:8080"
echo "   2. Login with admin credentials (check database seeders)"
echo "   3. Start developing!"