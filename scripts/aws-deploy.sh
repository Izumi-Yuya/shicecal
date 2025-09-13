#!/bin/bash

# Shise-Cal AWS Deployment Script
# Usage: ./scripts/aws-deploy.sh [full|quick|health|rollback]

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
    # Default configuration
    SERVER_USER="${AWS_USERNAME:-ec2-user}"
    SERVER_HOST="${AWS_HOST:-35.75.1.64}"
    KEY_PATH="${SSH_KEY_PATH:-$HOME/Shise-Cal-test-key.pem}"
    PROJECT_PATH="/home/$SERVER_USER/shicecal"
    BRANCH="production"
fi

# Validate configuration
if [ -z "$SERVER_HOST" ] || [ -z "$SERVER_USER" ] || [ -z "$KEY_PATH" ]; then
    print_status "ERROR" "Missing configuration. Please run setup-aws-deployment.sh first"
    exit 1
fi

if [ ! -f "$KEY_PATH" ]; then
    print_status "ERROR" "SSH key file not found: $KEY_PATH"
    exit 1
fi

# Deployment type
DEPLOY_TYPE="${1:-quick}"

print_status "INFO" "Starting AWS deployment..."
print_status "INFO" "Target: $SERVER_USER@$SERVER_HOST"
print_status "INFO" "Type: $DEPLOY_TYPE"

# SSH command wrapper
ssh_exec() {
    ssh -i "$KEY_PATH" -o StrictHostKeyChecking=no "$SERVER_USER@$SERVER_HOST" "$@"
}

# Test connection
print_status "INFO" "Testing SSH connection..."
if ssh_exec "echo 'Connection successful'"; then
    print_status "SUCCESS" "SSH connection established"
else
    print_status "ERROR" "SSH connection failed"
    exit 1
fi

case $DEPLOY_TYPE in
    "full")
        print_status "INFO" "Performing full deployment..."
        
        # Update code
        print_status "INFO" "Updating code from Git..."
        ssh_exec "cd $PROJECT_PATH && git fetch origin && git reset --hard origin/$BRANCH"
        
        # Install dependencies
        print_status "INFO" "Installing PHP dependencies..."
        ssh_exec "cd $PROJECT_PATH && composer install --no-dev --optimize-autoloader --no-interaction"
        
        # Install Node.js dependencies and build assets
        print_status "INFO" "Building frontend assets..."
        ssh_exec "cd $PROJECT_PATH && npm install && npm run build"
        
        # Run migrations
        print_status "INFO" "Running database migrations..."
        ssh_exec "cd $PROJECT_PATH && php artisan migrate --force"
        
        # Clear and cache
        print_status "INFO" "Clearing and caching configurations..."
        ssh_exec "cd $PROJECT_PATH && php artisan optimize:clear"
        ssh_exec "cd $PROJECT_PATH && php artisan config:cache"
        ssh_exec "cd $PROJECT_PATH && php artisan route:cache"
        ssh_exec "cd $PROJECT_PATH && php artisan view:cache"
        
        # Set permissions
        print_status "INFO" "Setting file permissions..."
        ssh_exec "cd $PROJECT_PATH && chmod -R 755 storage bootstrap/cache"
        ssh_exec "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache" || true
        
        # Create storage link
        ssh_exec "cd $PROJECT_PATH && php artisan storage:link" || true
        
        # Restart services
        print_status "INFO" "Restarting services..."
        ssh_exec "sudo systemctl restart nginx" || print_status "WARNING" "Could not restart nginx"
        ssh_exec "sudo systemctl restart php8.2-fpm" || ssh_exec "sudo systemctl restart php8.1-fpm" || print_status "WARNING" "Could not restart PHP-FPM"
        
        print_status "SUCCESS" "Full deployment completed!"
        ;;
        
    "quick")
        print_status "INFO" "Performing quick deployment..."
        
        # Update code
        print_status "INFO" "Updating code from Git..."
        ssh_exec "cd $PROJECT_PATH && git fetch origin && git reset --hard origin/$BRANCH"
        
        # Build assets
        print_status "INFO" "Building frontend assets..."
        ssh_exec "cd $PROJECT_PATH && npm run build"
        
        # Clear cache
        print_status "INFO" "Clearing cache..."
        ssh_exec "cd $PROJECT_PATH && php artisan optimize:clear"
        
        print_status "SUCCESS" "Quick deployment completed!"
        ;;
        
    "health")
        print_status "INFO" "Performing health check..."
        
        # Check if application is running
        if ssh_exec "cd $PROJECT_PATH && php artisan --version"; then
            print_status "SUCCESS" "Laravel application is running"
        else
            print_status "ERROR" "Laravel application is not responding"
            exit 1
        fi
        
        # Check database connection
        if ssh_exec "cd $PROJECT_PATH && php artisan migrate:status"; then
            print_status "SUCCESS" "Database connection is working"
        else
            print_status "ERROR" "Database connection failed"
            exit 1
        fi
        
        # Check web server
        if ssh_exec "curl -s http://localhost > /dev/null"; then
            print_status "SUCCESS" "Web server is responding"
        else
            print_status "WARNING" "Web server may not be responding on localhost"
        fi
        
        print_status "SUCCESS" "Health check completed!"
        ;;
        
    "rollback")
        print_status "INFO" "Performing rollback..."
        
        # Get previous commit
        PREVIOUS_COMMIT=$(ssh_exec "cd $PROJECT_PATH && git log --oneline -n 2 | tail -1 | cut -d' ' -f1")
        
        if [ -n "$PREVIOUS_COMMIT" ]; then
            print_status "INFO" "Rolling back to commit: $PREVIOUS_COMMIT"
            ssh_exec "cd $PROJECT_PATH && git reset --hard $PREVIOUS_COMMIT"
            
            # Clear cache
            ssh_exec "cd $PROJECT_PATH && php artisan optimize:clear"
            
            print_status "SUCCESS" "Rollback completed!"
        else
            print_status "ERROR" "Could not determine previous commit"
            exit 1
        fi
        ;;
        
    *)
        print_status "ERROR" "Invalid deployment type: $DEPLOY_TYPE"
        echo "Usage: $0 [full|quick|health|rollback]"
        echo ""
        echo "  full     - Complete deployment with dependencies and migrations"
        echo "  quick    - Code update and cache clear only"
        echo "  health   - Health check only"
        echo "  rollback - Rollback to previous commit"
        exit 1
        ;;
esac

# Final health check
print_status "INFO" "Running post-deployment health check..."
if ssh_exec "cd $PROJECT_PATH && php artisan --version > /dev/null"; then
    print_status "SUCCESS" "Application is healthy"
    
    # Show application info
    APP_VERSION=$(ssh_exec "cd $PROJECT_PATH && php artisan --version")
    CURRENT_COMMIT=$(ssh_exec "cd $PROJECT_PATH && git rev-parse --short HEAD")
    
    echo ""
    print_status "INFO" "Deployment Summary:"
    echo "  - Application: $APP_VERSION"
    echo "  - Commit: $CURRENT_COMMIT"
    echo "  - Server: $SERVER_HOST"
    echo "  - URL: http://$SERVER_HOST"
    echo ""
    print_status "SUCCESS" "Deployment completed successfully! 🚀"
else
    print_status "ERROR" "Application health check failed"
    exit 1
fi