# Technology Stack & Build System

## Backend Framework
- **Laravel 9.x** - PHP web application framework
- **PHP 8.2+** - Server-side programming language
- **MySQL 8.0** - Primary database (SQLite for testing)
- **Redis** - Caching layer

## Frontend Technologies
- **Blade Templates** - Laravel's templating engine
- **Bootstrap 5.1.3** - CSS framework with custom styling
- **ES6 Modules** - Modern JavaScript module system
- **Vanilla JavaScript (ES6+)** - Client-side scripting with modular architecture
- **Font Awesome 6.0.0** - Icon library
- **Vite 4.x** - Modern build tool and dev server with ES6 module support

## Key Dependencies
- **barryvdh/laravel-dompdf** - PDF generation
- **elibyy/tcpdf-laravel** - Advanced PDF features
- **spatie/laravel-activitylog** - Activity logging
- **laravel/sanctum** - API authentication

## Development Tools
- **Laravel Pint** - Code formatting
- **PHPUnit** - PHP testing framework
- **Vitest** - JavaScript testing framework
- **Docker** - Containerization (optional)
- **Composer** - PHP dependency management
- **npm** - Node.js package management

## Common Commands

### Development Setup
```bash
# Initial setup
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Start development
php artisan serve          # Backend server (port 8000)
npm run dev               # Frontend dev server with HMR
```

### Database Operations
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh DB with test data
php artisan db:seed             # Seed test data only
```

### Testing
```bash
php artisan test                # Run all PHP tests
php artisan test --coverage    # With coverage report
npm run test                    # Run JavaScript tests
npm run test:watch             # Watch mode for JS tests
```

### Build & Deployment
```bash
npm run build                   # Build production assets
php artisan config:cache        # Cache configuration
php artisan route:cache         # Cache routes
php artisan view:cache          # Cache views
php artisan optimize:clear      # Clear all caches
```

### Docker Development (Optional)
```bash
make setup                      # Initial Docker setup
make start                      # Start containers
make shell                      # Access app container
make test                       # Run tests in container
make logs                       # View container logs
```

## File Structure Conventions
- Controllers follow Laravel conventions in `app/Http/Controllers/`
- Services in `app/Services/` for business logic
- Models in `app/Models/` with proper relationships
- Policies in `app/Policies/` for authorization
- CSS organized by purpose in `resources/css/` (shared/, pages/ subdirectories)
- JavaScript ES6 modules in `resources/js/` (modules/, shared/ subdirectories)
- Blade views organized by feature in `resources/views/`

## Frontend Architecture
- **Entry Point**: `resources/js/app.js` - Main ES6 module entry point
- **Feature Modules**: `resources/js/modules/` - Feature-specific functionality
- **Shared Modules**: `resources/js/shared/` - Reusable utilities and components
- **Module Pattern**: Each feature exports initialization functions
- **State Management**: ApplicationState class for global state
- **Backward Compatibility**: Legacy API via `window.ShiseCal` object

## Code Quality
- Use Laravel Pint for consistent PHP formatting
- Follow PSR-12 coding standards
- Write comprehensive tests (Feature and Unit)
- Use type hints and return types in PHP
- Implement proper error handling and logging
- Follow Laravel best practices for security