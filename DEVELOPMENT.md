# Shise-Cal Development Guide

## Git Workflow Strategy

### Branch Structure

- **main**: Production-ready code, stable releases only
- **develop**: Integration branch for development, all features merge here first
- **feature/**: Feature development branches (e.g., feature/user-management, feature/database-migrations)
- **hotfix/**: Emergency fixes for production issues

### Workflow Process

1. **Feature Development**:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/task-name
   # Develop feature
   git add .
   git commit -m "[Task#] Description: Implementation details"
   git push origin feature/task-name
   # Create PR to develop
   ```

2. **Release Process**:
   ```bash
   git checkout main
   git merge develop
   git tag v1.0.0
   git push origin main --tags
   ```

3. **Hotfix Process**:
   ```bash
   git checkout main
   git checkout -b hotfix/issue-description
   # Fix issue
   git commit -m "Hotfix: Description"
   git checkout main
   git merge hotfix/issue-description
   git checkout develop
   git merge hotfix/issue-description
   ```

### Commit Message Format

- **[Task#] Component: Description**
- Examples:
  - `[1.2] Database: Create users and facilities migrations`
  - `[2.1] Auth: Implement Laravel Sanctum setup`
  - `[3.1] UI: Create user management interface`

### Code Review Requirements

- All feature branches require PR review before merging to develop
- Main branch merges require additional approval
- All tests must pass before merge
- Code coverage should maintain 80%+ for new features

## Development Environment Setup

### Prerequisites

- PHP 8.1+
- Composer
- MySQL 8.0
- Node.js (for frontend assets)

### Local Setup

1. Clone repository
2. Copy `.env.example` to `.env`
3. Configure database settings
4. Run `composer install`
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan serve`

### Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

## Task Implementation Guidelines

- Each task should be implemented in its own feature branch
- Follow the task list order when possible
- Complete all sub-tasks before marking parent task complete
- Write tests for new functionality
- Update documentation as needed
- Commit frequently with descriptive messages