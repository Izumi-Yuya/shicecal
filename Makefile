# Shise-Cal Development Makefile
# Provides convenient commands for development workflow

.PHONY: help setup start stop restart logs shell test migrate seed fresh composer npm artisan clear build dev status clean

# Default target
help: ## Show this help message
	@echo "Shise-Cal Development Commands"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ {printf "  \033[36m%-15s\033[0m %s\n", $1, $2}' $(MAKEFILE_LIST)

# Environment setup
setup: ## Set up the development environment
	@./scripts/dev-setup.sh

# Container management
start: ## Start development environment
	@docker-compose -f docker-compose.dev.yml up -d
	@echo "✅ Development environment started"
	@echo "🌐 Application: http://localhost:8080"

stop: ## Stop development environment
	@docker-compose -f docker-compose.dev.yml down
	@echo "✅ Development environment stopped"

restart: ## Restart development environment
	@docker-compose -f docker-compose.dev.yml restart
	@echo "✅ Development environment restarted"

# Logging and debugging
logs: ## Show logs for all services
	@docker-compose -f docker-compose.dev.yml logs -f

logs-app: ## Show logs for app service only
	@docker-compose -f docker-compose.dev.yml logs -f app

logs-db: ## Show logs for database service only
	@docker-compose -f docker-compose.dev.yml logs -f db

# Container access
shell: ## Open bash shell in app container
	@docker-compose -f docker-compose.dev.yml exec app bash

shell-db: ## Open MySQL shell
	@docker-compose -f docker-compose.dev.yml exec db mysql -u shisecal_dev -pdev_password shisecal_development

# Testing
test: ## Run all tests
	@docker-compose -f docker-compose.dev.yml exec app php artisan test

test-coverage: ## Run tests with coverage report
	@docker-compose -f docker-compose.dev.yml exec app php artisan test --coverage

test-feature: ## Run feature tests only
	@docker-compose -f docker-compose.dev.yml exec app php artisan test --testsuite=Feature

test-unit: ## Run unit tests only
	@docker-compose -f docker-compose.dev.yml exec app php artisan test --testsuite=Unit

# Database operations
migrate: ## Run database migrations
	@docker-compose -f docker-compose.dev.yml exec app php artisan migrate

migrate-rollback: ## Rollback last migration
	@docker-compose -f docker-compose.dev.yml exec app php artisan migrate:rollback

seed: ## Seed database with test data
	@docker-compose -f docker-compose.dev.yml exec app php artisan db:seed

fresh: ## Fresh migration with seeding
	@docker-compose -f docker-compose.dev.yml exec app php artisan migrate:fresh --seed

# Package management
composer-install: ## Install PHP dependencies
	@docker-compose -f docker-compose.dev.yml exec app composer install

composer-update: ## Update PHP dependencies
	@docker-compose -f docker-compose.dev.yml exec app composer update

npm-install: ## Install Node.js dependencies
	@docker-compose -f docker-compose.dev.yml exec node npm install

npm-update: ## Update Node.js dependencies
	@docker-compose -f docker-compose.dev.yml exec node npm update

# Laravel commands
artisan: ## Run artisan command (usage: make artisan CMD="make:controller TestController")
	@docker-compose -f docker-compose.dev.yml exec app php artisan $(CMD)

key-generate: ## Generate application key
	@docker-compose -f docker-compose.dev.yml exec app php artisan key:generate

storage-link: ## Create storage symbolic link
	@docker-compose -f docker-compose.dev.yml exec app php artisan storage:link

# Cache management
clear: ## Clear all caches
	@docker-compose -f docker-compose.dev.yml exec app php artisan optimize:clear
	@echo "✅ All caches cleared"

cache: ## Cache configuration and routes
	@docker-compose -f docker-compose.dev.yml exec app php artisan config:cache
	@docker-compose -f docker-compose.dev.yml exec app php artisan route:cache
	@docker-compose -f docker-compose.dev.yml exec app php artisan view:cache
	@echo "✅ Configuration cached"

# Frontend development
build: ## Build frontend assets
	@docker-compose -f docker-compose.dev.yml exec node npm run build
	@echo "✅ Frontend assets built"

dev: ## Start frontend development server
	@docker-compose -f docker-compose.dev.yml exec node npm run dev

watch: ## Watch frontend assets for changes
	@docker-compose -f docker-compose.dev.yml exec node npm run watch

# System management
status: ## Show container status
	@docker-compose -f docker-compose.dev.yml ps

clean: ## Clean up containers and volumes
	@docker-compose -f docker-compose.dev.yml down -v --remove-orphans
	@docker system prune -f
	@echo "✅ Cleanup completed"

rebuild: ## Rebuild containers from scratch
	@docker-compose -f docker-compose.dev.yml down -v --remove-orphans
	@docker-compose -f docker-compose.dev.yml build --no-cache
	@docker-compose -f docker-compose.dev.yml up -d
	@echo "✅ Containers rebuilt"

# Quality assurance
lint: ## Run code linting
	@docker-compose -f docker-compose.dev.yml exec app ./vendor/bin/pint --test

lint-fix: ## Fix code style issues
	@docker-compose -f docker-compose.dev.yml exec app ./vendor/bin/pint

analyze: ## Run static analysis
	@docker-compose -f docker-compose.dev.yml exec app ./vendor/bin/phpstan analyse

# Backup and restore
backup-db: ## Backup development database
	@docker-compose -f docker-compose.dev.yml exec db mysqldump -u shisecal_dev -pdev_password shisecal_development > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Database backup created"

# Development workflow shortcuts
dev-start: start migrate seed build ## Complete development startup
	@echo "✅ Development environment ready!"
	@echo "🌐 Application: http://localhost:8080"
	@echo "📧 MailHog: http://localhost:8025"
	@echo "🗄️  MinIO: http://localhost:9001"

dev-reset: clean setup ## Reset development environment completely
	@echo "✅ Development environment reset complete!"

# Production preparation
prod-build: ## Build for production
	@docker-compose -f docker-compose.yml build
	@echo "✅ Production build completed"

# Help for specific commands
help-artisan: ## Show available artisan commands
	@docker-compose -f docker-compose.dev.yml exec app php artisan list

help-composer: ## Show composer help
	@docker-compose -f docker-compose.dev.yml exec app composer --help

help-npm: ## Show npm help
	@docker-compose -f docker-compose.dev.yml exec node npm --help