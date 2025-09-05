# Suggested Commands for Shise-Cal Development

## Development Setup
```bash
# Initial setup
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Start development servers
php artisan serve          # Backend server (port 8000)
npm run dev               # Frontend dev server with HMR
```

## Database Operations
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh DB with test data
php artisan db:seed             # Seed test data only
```

## Testing
```bash
php artisan test                # Run all PHP tests
php artisan test --coverage    # With coverage report
npm run test                    # Run JavaScript tests
npm run test:watch             # Watch mode for JS tests
```

## Code Quality
```bash
./vendor/bin/pint              # Format PHP code
php artisan test               # Run tests before committing
```

## Build & Deployment
```bash
npm run build                   # Build production assets
php artisan config:cache        # Cache configuration
php artisan route:cache         # Cache routes
php artisan view:cache          # Cache views
php artisan optimize:clear      # Clear all caches
```

## Useful Development Commands
```bash
php artisan make:controller     # Create controller
php artisan make:model         # Create model
php artisan make:migration     # Create migration
php artisan make:seeder        # Create seeder
php artisan make:test          # Create test
```