#!/bin/bash

# Fix AWS Server Permissions Script
# This script fixes file permission issues on the AWS server

set -e

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

# Load configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/../aws-server-config.sh"

if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    print_status "ERROR" "Configuration file not found. Please run setup-aws-deployment.sh first"
    exit 1
fi

# SSH command wrapper
ssh_exec() {
    ssh -i "$KEY_PATH" -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_HOST" "$@"
}

print_status "INFO" "🔧 Fixing file permissions on AWS server..."
print_status "INFO" "Target: $SERVER_USER@$SERVER_HOST"
print_status "INFO" "Project: $PROJECT_PATH"

# Test connection
print_status "INFO" "Testing SSH connection..."
if ! ssh_exec "echo 'Connection successful'"; then
    print_status "ERROR" "SSH connection failed"
    exit 1
fi

print_status "SUCCESS" "SSH connection established"

# Stop web services temporarily
print_status "INFO" "Stopping web services..."
ssh_exec "sudo systemctl stop nginx" || print_status "WARNING" "Could not stop nginx"
ssh_exec "sudo systemctl stop php8.2-fpm" || ssh_exec "sudo systemctl stop php8.1-fpm" || print_status "WARNING" "Could not stop PHP-FPM"

# Fix ownership and permissions
print_status "INFO" "Fixing directory ownership..."
ssh_exec "sudo chown -R $SERVER_USER:$SERVER_USER $PROJECT_PATH"

print_status "INFO" "Setting base permissions..."
ssh_exec "find $PROJECT_PATH -type f -exec chmod 644 {} \;"
ssh_exec "find $PROJECT_PATH -type d -exec chmod 755 {} \;"

print_status "INFO" "Setting executable permissions for scripts..."
ssh_exec "chmod +x $PROJECT_PATH/artisan"
ssh_exec "find $PROJECT_PATH -name '*.sh' -exec chmod +x {} \;" || true

print_status "INFO" "Creating and setting storage permissions..."
# Create storage directories if they don't exist
ssh_exec "mkdir -p $PROJECT_PATH/storage/logs"
ssh_exec "mkdir -p $PROJECT_PATH/storage/framework/cache"
ssh_exec "mkdir -p $PROJECT_PATH/storage/framework/sessions"
ssh_exec "mkdir -p $PROJECT_PATH/storage/framework/views"
ssh_exec "mkdir -p $PROJECT_PATH/storage/app/public"
ssh_exec "mkdir -p $PROJECT_PATH/bootstrap/cache"

# Set proper permissions for storage and cache
ssh_exec "chmod -R 775 $PROJECT_PATH/storage"
ssh_exec "chmod -R 775 $PROJECT_PATH/bootstrap/cache"

# Detect web server user
print_status "INFO" "Detecting web server user..."
WEB_USER=$(ssh_exec "ps aux | grep -E '(nginx|apache|httpd)' | grep -v root | head -1 | awk '{print \$1}'" || echo "nginx")
if [ -z "$WEB_USER" ] || [ "$WEB_USER" = "root" ]; then
    WEB_USER="nginx"  # Default for Amazon Linux
fi
print_status "INFO" "Using web server user: $WEB_USER"

# Set web server ownership for storage directories
print_status "INFO" "Setting web server ownership for writable directories..."
ssh_exec "sudo chown -R $WEB_USER:$WEB_USER $PROJECT_PATH/storage"
ssh_exec "sudo chown -R $WEB_USER:$WEB_USER $PROJECT_PATH/bootstrap/cache"

# Ensure the user can still access these directories
ssh_exec "sudo usermod -a -G $WEB_USER $SERVER_USER" || print_status "WARNING" "Could not add user to web server group"

# Create storage link
print_status "INFO" "Creating storage link..."
ssh_exec "cd $PROJECT_PATH && php artisan storage:link" || print_status "WARNING" "Storage link already exists or failed"

# Clear all caches
print_status "INFO" "Clearing application caches..."
ssh_exec "cd $PROJECT_PATH && php artisan optimize:clear" || print_status "WARNING" "Cache clear failed"

# Test log file creation
print_status "INFO" "Testing log file creation..."
ssh_exec "cd $PROJECT_PATH && php artisan tinker --execute=\"Log::info('Permission test successful')\"" || print_status "WARNING" "Log test failed"

# Start web services
print_status "INFO" "Starting web services..."
ssh_exec "sudo systemctl start php8.2-fpm" || ssh_exec "sudo systemctl start php8.1-fpm" || print_status "WARNING" "Could not start PHP-FPM"
ssh_exec "sudo systemctl start nginx" || print_status "WARNING" "Could not start nginx"

# Final health check
print_status "INFO" "Running health check..."
if ssh_exec "cd $PROJECT_PATH && php artisan --version > /dev/null"; then
    print_status "SUCCESS" "Application is responding"
else
    print_status "ERROR" "Application health check failed"
    exit 1
fi

# Test web access
print_status "INFO" "Testing web access..."
if ssh_exec "curl -s -o /dev/null -w '%{http_code}' http://localhost" | grep -q "200\|302"; then
    print_status "SUCCESS" "Web server is responding correctly"
else
    print_status "WARNING" "Web server response may be incorrect"
fi

print_status "SUCCESS" "🎉 Permission fix completed successfully!"
print_status "INFO" "Summary of changes:"
echo "  - Fixed directory ownership to $SERVER_USER:$SERVER_USER"
echo "  - Set storage and cache ownership to $WEB_USER:$WEB_USER"
echo "  - Set proper file permissions (644 for files, 755 for directories)"
echo "  - Set writable permissions (775) for storage and cache directories"
echo "  - Added $SERVER_USER to $WEB_USER group"
echo "  - Cleared application caches"
echo ""
print_status "INFO" "Your application should now be accessible at: http://$SERVER_HOST"