#!/bin/bash

# AWS Web Server Setup Script for Laravel Facility Management System
# Run this script on your AWS EC2 instance (Ubuntu/Amazon Linux)

set -e

echo "🚀 Starting AWS Web Server Setup..."

# Update system packages
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install Nginx
echo "🌐 Installing Nginx..."
sudo apt install nginx -y
sudo systemctl start nginx
sudo systemctl enable nginx

# Install PHP 8.1 and required extensions
echo "🐘 Installing PHP 8.1 and extensions..."
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring \
    php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath \
    php8.1-soap php8.1-redis php8.1-sqlite3 -y

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js and npm
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Create web directory
echo "📁 Setting up web directory..."
sudo mkdir -p /var/www/facility-management
sudo chown -R $USER:www-data /var/www/facility-management
sudo chmod -R 755 /var/www/facility-management

echo "✅ Web server setup completed!"
echo "Next steps:"
echo "1. Upload your Laravel project to /var/www/facility-management"
echo "2. Run the Laravel deployment script"
echo "3. Configure Nginx virtual host"