# Project Structure & Organization

## Root Directory Layout
```
shicecal/
├── app/                    # Application code
├── bootstrap/              # Framework bootstrap files
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
├── docs/                   # Project documentation
├── public/                 # Web server document root
├── resources/              # Views, CSS, JS, language files
├── routes/                 # Route definitions
├── storage/                # File storage and logs
├── tests/                  # Test files
└── vendor/                 # Composer dependencies
```

## Application Structure (`app/`)
```
app/
├── Console/                # Artisan commands
├── Exceptions/             # Exception handlers
├── Helpers/                # Helper classes and utilities
├── Http/
│   ├── Controllers/        # HTTP controllers
│   │   └── Admin/          # Admin-specific controllers
│   ├── Middleware/         # HTTP middleware
│   └── Requests/           # Form request validation
├── Models/                 # Eloquent models
├── Policies/               # Authorization policies
├── Providers/              # Service providers
└── Services/               # Business logic services
```

## Frontend Structure (`resources/`)
```
resources/
├── css/
│   ├── app.css             # Main application styles
│   ├── auth.css            # Authentication pages
│   ├── admin.css           # Admin interface styles
│   ├── land-info.css       # Land information specific styles
│   ├── base.css            # Base styles and resets
│   ├── components.css      # Reusable components
│   ├── layout.css          # Layout and grid systems
│   ├── pages.css           # Page-specific styles
│   ├── utilities.css       # Utility classes
│   ├── variables.css       # CSS custom properties
│   └── animations.css      # Animation definitions
├── js/
│   ├── app.js              # Main application JavaScript
│   ├── admin.js            # Admin interface functionality
│   ├── land-info.js        # Land information form handling
│   └── bootstrap.js        # Framework initialization
└── views/
    ├── admin/              # Admin interface views
    ├── auth/               # Authentication views
    ├── comments/           # Comment system views
    ├── export/             # Export functionality views
    ├── facilities/         # Facility management views
    ├── layouts/            # Layout templates
    ├── maintenance/        # Maintenance views
    ├── my-page/            # User dashboard views
    └── notifications/      # Notification views
```

## Database Structure (`database/`)
```
database/
├── factories/              # Model factories for testing
├── migrations/             # Database schema migrations
├── seeders/                # Database seeders
├── database.sqlite         # SQLite database (development)
└── testing.sqlite          # SQLite database (testing)
```

## Testing Structure (`tests/`)
```
tests/
├── Feature/                # Feature/integration tests
├── Unit/                   # Unit tests
│   ├── Helpers/            # Helper class tests
│   ├── Models/             # Model tests
│   ├── Policies/           # Policy tests
│   └── Services/           # Service tests
├── js/                     # JavaScript tests
└── TestCase.php            # Base test case
```

## Configuration Files (`config/`)
- `app.php` - Application configuration
- `database.php` - Database connections
- `auth.php` - Authentication settings
- `facility.php` - Facility-specific settings
- `dompdf.php` - PDF generation settings
- `tcpdf.php` - Advanced PDF settings

## Key Architectural Patterns

### MVC Pattern
- **Models**: Data layer with Eloquent ORM
- **Views**: Blade templates for presentation
- **Controllers**: HTTP request handling and response

### Service Layer Pattern
- Business logic separated into service classes
- Controllers remain thin, delegating to services
- Services handle complex operations and calculations

### Repository Pattern (Implicit)
- Eloquent models act as repositories
- Complex queries encapsulated in model methods
- Relationships defined at model level

### Policy-Based Authorization
- Authorization logic in dedicated Policy classes
- Gate-based permissions for fine-grained control
- Role-based access control (RBAC) implementation

## Naming Conventions

### PHP Classes
- Controllers: `PascalCase` + `Controller` suffix
- Models: `PascalCase` (singular)
- Services: `PascalCase` + `Service` suffix
- Policies: `PascalCase` + `Policy` suffix

### Database
- Tables: `snake_case` (plural)
- Columns: `snake_case`
- Foreign keys: `{table}_id`
- Pivot tables: `{table1}_{table2}` (alphabetical)

### Files & Directories
- Blade views: `kebab-case.blade.php`
- CSS/JS files: `kebab-case`
- Migration files: Laravel timestamp format

### Routes
- Route names: `dot.notation` (e.g., `facilities.show`)
- URL paths: `kebab-case` with resource conventions

## Feature Organization
Each major feature follows a consistent structure:
- Controller for HTTP handling
- Service for business logic
- Model for data access
- Policy for authorization
- Request classes for validation
- Blade views in feature subdirectory
- Feature-specific CSS/JS files
- Comprehensive test coverage

## Documentation Structure (`docs/`)
- `requirements/` - System requirements and specifications
- `setup/` - Development and deployment setup guides
- `implementation/` - Technical implementation details
- `deployment/` - Production deployment documentation
- `troubleshooting/` - Common issues and solutions