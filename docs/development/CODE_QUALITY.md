# Code Quality & CI Integration

This document outlines the code quality tools and CI integration implemented for the Shise-Cal project.

## Overview

The project uses a comprehensive code quality system that includes:

- **PHP Code Style**: Laravel Pint for consistent PHP formatting
- **JavaScript Linting**: ESLint for JavaScript code quality
- **Blade Template Validation**: Custom linter for Blade syntax checking
- **HTML Validation**: HTMLHint for HTML structure validation
- **Build Verification**: Vite build process validation
- **Automated Testing**: PHP and JavaScript test suites
- **CI/CD Integration**: GitHub Actions workflows

## Tools & Configuration

### PHP Code Style (Laravel Pint)

Laravel Pint is already configured and available via Composer.

```bash
# Check code style
vendor/bin/pint --test

# Fix code style issues
vendor/bin/pint
```

### JavaScript Linting (ESLint)

ESLint is configured with modern ES6+ rules and project-specific globals.

**Configuration**: `eslint.config.js`

```bash
# Lint JavaScript files
npm run lint:js

# Fix auto-fixable issues
npm run lint:js:fix
```

**Key Rules**:
- ES6+ features required (no var, prefer const/let)
- Consistent formatting (4 spaces, single quotes, semicolons)
- Error prevention (no unused vars, no undefined variables)
- Security awareness (no debugger, console warnings)

### Blade Template Validation

Custom Blade linter that checks for:
- Unclosed Blade directives
- Missing CSRF tokens in forms
- Potential XSS vulnerabilities
- Accessibility issues (missing alt attributes)
- Code maintainability (inline styles warnings)

```bash
# Run Blade template linting
npm run lint:blade
```

### HTML Validation (HTMLHint)

HTMLHint validates HTML structure and attributes.

**Configuration**: `.htmlhintrc`

```bash
# Run HTML validation
npm run lint:html
```

### Build Verification

Vite build process is validated to ensure:
- Assets compile successfully
- Manifest file is generated
- No debug statements in production builds

```bash
# Build assets
npm run build

# Run all quality checks
npm run quality
```

## Available Scripts

### Individual Checks
```bash
npm run lint:js          # JavaScript linting (ESLint)
npm run lint:js:fix      # Fix JavaScript issues automatically
npm run lint:blade       # Blade template validation (custom linter)
npm run lint:html        # HTML validation (HTMLHint)
npm run lint:php         # PHP code style check (Laravel Pint)
npm run lint:php:fix     # Fix PHP code style automatically
```

### Combined Checks
```bash
npm run lint             # All linting checks (JS + Blade + HTML)
npm run quality          # Complete quality pipeline (lint + test + build)
npm run ci               # Full CI pipeline locally (alias for quality)
```

### Testing
```bash
npm run test             # Run JavaScript tests once
npm run test:watch       # Run JavaScript tests in watch mode
```

### Build
```bash
npm run dev              # Development build with HMR
npm run build            # Production build with optimization
```

## Git Hooks

### Pre-commit Hook

Automatically runs quality checks before each commit:

```bash
# Install Git hooks
./scripts/setup-git-hooks.sh
```

The pre-commit hook runs:
1. PHP code style check (if PHP files changed)
2. JavaScript linting (if JS files changed)
3. Blade template validation (if Blade files changed)
4. Asset build verification
5. Quick test suite

### Bypassing Hooks

To skip the pre-commit hook for a specific commit:

```bash
git commit --no-verify -m "Emergency fix"
```

## CI/CD Integration

### GitHub Actions Workflows

#### 1. Continuous Integration (`.github/workflows/ci.yml`)

Runs on all pushes and pull requests:

**Code Quality Checks**:
- PHP linting (Laravel Pint)
- JavaScript linting (ESLint)
- Blade template validation
- HTML validation
- Build verification

**Testing**:
- PHP test suite with coverage
- JavaScript test suite

**Security & Performance**:
- Dependency security audits
- Bundle size analysis

#### 2. Deployment (`.github/workflows/deploy.yml`)

Enhanced deployment workflow with quality gate:

**Quality Gate** (runs first):
- All linting checks
- Build verification
- Test suite execution

**Deployment** (only if quality gate passes):
- Deploy to AWS EC2
- Production build verification
- Cache optimization

### Quality Gate Requirements

Deployment is blocked if any of these fail:
- PHP code style violations
- JavaScript linting errors
- Blade template syntax errors
- Build failures
- Test failures

## Development Workflow

### Setting Up

1. Install dependencies:
```bash
composer install
npm install
```

2. Setup Git hooks:
```bash
./scripts/setup-git-hooks.sh
```

3. Run initial quality check:
```bash
npm run quality
```

### Daily Development

1. **During development**: Use watch mode for continuous feedback:
```bash
npm run dev          # Development server with HMR
npm run test:watch   # Continuous testing
```

2. **Before committing**: Pre-commit hook runs automatically, or run manually:
```bash
npm run lint         # Quick linting check
npm run quality      # Full quality pipeline
```

3. **Before pushing**: Ensure CI checks pass locally:
```bash
npm run ci           # Complete CI pipeline (same as quality)
```

4. **Code review**: CI status must be green for PR approval

### Fixing Issues

#### PHP Code Style Issues
```bash
vendor/bin/pint
```

#### JavaScript Linting Issues
```bash
npm run lint:js:fix  # Auto-fix
# Manual fixes for remaining issues
```

#### Blade Template Issues
- Review linter output
- Fix syntax errors manually
- Ensure proper directive pairing

#### Build Issues
- Check Vite configuration
- Resolve import/export errors
- Verify asset paths

## Configuration Files

| File | Purpose |
|------|---------|
| `eslint.config.js` | ESLint configuration |
| `.htmlhintrc` | HTMLHint configuration |
| `scripts/lint-blade.js` | Custom Blade linter |
| `scripts/pre-commit-hook.sh` | Git pre-commit hook |
| `.github/workflows/ci.yml` | CI workflow |
| `.github/workflows/deploy.yml` | Deployment workflow |

## Troubleshooting

### Common Issues

#### ESLint Errors
- **Undefined variables**: Add to globals in `eslint.config.js`
- **Import/export issues**: Check module syntax
- **Formatting issues**: Run `npm run lint:js:fix`

#### Blade Linting Errors
- **Unclosed directives**: Check @if/@endif pairs
- **CSRF warnings**: Add @csrf to forms
- **XSS warnings**: Review {!! usage

#### Build Failures
- **Module not found**: Check import paths
- **Syntax errors**: Fix JavaScript/CSS syntax
- **Asset issues**: Verify file paths in Vite config

#### CI Failures
- **Quality gate**: Fix linting/test issues locally first
- **Build timeout**: Optimize build process
- **Test failures**: Run tests locally to debug

### Getting Help

1. **Local debugging**: Run individual checks to isolate issues
2. **CI logs**: Check GitHub Actions logs for detailed error messages
3. **Documentation**: Review tool-specific documentation
4. **Team support**: Ask team members for assistance with complex issues

## Best Practices

### Code Quality
- Fix linting issues immediately
- Write tests for new functionality
- Use consistent formatting
- Follow security best practices

### CI/CD
- Keep builds fast (< 10 minutes)
- Fix broken builds immediately
- Monitor build performance
- Update dependencies regularly

### Team Workflow
- Never bypass quality checks in production
- Review CI status before merging PRs
- Communicate build issues promptly
- Share knowledge about quality tools

## Metrics & Monitoring

### Quality Metrics
- Code coverage percentage
- Linting error count
- Build success rate
- Test pass rate

### Performance Metrics
- Build time
- Bundle size
- CI pipeline duration
- Deployment frequency

### Monitoring
- GitHub Actions dashboard
- Build notifications
- Quality trend analysis
- Performance regression detection