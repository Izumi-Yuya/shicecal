# Code Quality & CI Integration Setup

This document describes the code quality tools and CI integration that have been implemented for the Shise-Cal project.

## 🎯 Overview

The project now includes comprehensive code quality checks that run automatically in CI and can be run locally:

- **PHP Code Style**: Laravel Pint for consistent PHP formatting
- **JavaScript Linting**: ESLint for JavaScript code quality
- **Blade Template Validation**: Custom linter for Blade syntax checking
- **HTML Validation**: HTMLHint for HTML structure validation
- **Build Verification**: Vite build process validation
- **Automated Testing**: PHP and JavaScript test suites

## 🚀 Quick Start

### Install Git Hooks (Recommended)
```bash
./scripts/setup-git-hooks.sh
```

### Run All Quality Checks
```bash
npm run quality
```

### Individual Checks
```bash
# JavaScript linting
npm run lint:js

# Blade template validation
npm run lint:blade

# HTML validation
npm run lint:html

# PHP code style
npm run lint:php

# Build verification
npm run build
```

## 🔧 Available Scripts

| Script | Description |
|--------|-------------|
| `npm run lint` | Run all linting checks (JS + Blade + HTML) |
| `npm run lint:js` | JavaScript ESLint |
| `npm run lint:js:fix` | Fix JavaScript issues automatically |
| `npm run lint:blade` | Blade template validation |
| `npm run lint:html` | HTML validation |
| `npm run lint:php` | PHP code style check (Laravel Pint) |
| `npm run lint:php:fix` | Fix PHP code style automatically |
| `npm run test` | Run JavaScript tests once |
| `npm run test:watch` | Run JavaScript tests in watch mode |
| `npm run build` | Production build with optimization |
| `npm run dev` | Development build with HMR |
| `npm run quality` | Full quality pipeline (lint + test + build) |
| `npm run ci` | Complete CI checks locally (alias for quality) |

## 🤖 CI/CD Integration

### GitHub Actions Workflows

#### 1. Continuous Integration (`.github/workflows/ci.yml`)
Runs on all pushes and pull requests with:
- Code quality checks (PHP, JavaScript, Blade, HTML)
- Build verification
- Test suites
- Security audits
- Bundle size analysis

#### 2. Deployment (`.github/workflows/deploy.yml`)
Enhanced with quality gate that blocks deployment if:
- Linting checks fail
- Build fails
- Tests fail

### Quality Gate Requirements

All deployments require:
- ✅ PHP code style compliance
- ✅ JavaScript linting passes
- ✅ Blade templates are valid
- ✅ Assets build successfully
- ✅ All tests pass

## 🎨 Code Style Rules

### JavaScript (ESLint)
- ES6+ features required
- 4-space indentation
- Single quotes
- Semicolons required
- No unused variables
- Consistent formatting

### PHP (Laravel Pint)
- PSR-12 compliance
- Laravel conventions
- Consistent formatting
- Proper imports

### Blade Templates
- Proper directive pairing
- CSRF token validation
- Accessibility checks
- XSS prevention

## 🔍 What Gets Checked

### JavaScript Files
- Syntax errors
- Code quality issues
- Unused variables
- Missing globals
- Formatting consistency

### Blade Templates
- Unclosed directives
- Missing CSRF tokens
- Accessibility issues
- Potential XSS vulnerabilities
- Inline style warnings

### PHP Files
- Code style compliance
- Import organization
- Formatting consistency
- Laravel conventions

### Build Process
- Asset compilation
- Manifest generation
- Bundle optimization
- Production readiness

## 🛠️ Configuration Files

| File | Purpose |
|------|---------|
| `eslint.config.js` | JavaScript linting rules |
| `.htmlhintrc` | HTML validation rules |
| `scripts/lint-blade.js` | Custom Blade linter |
| `scripts/pre-commit-hook.sh` | Git pre-commit checks |
| `.github/workflows/ci.yml` | CI pipeline |
| `.github/workflows/deploy.yml` | Deployment pipeline |

## 🚨 Troubleshooting

### Common Issues

#### ESLint Errors
- Check `eslint.config.js` for global variables
- Run `npm run lint:js:fix` for auto-fixes
- Review error messages for specific issues

#### Blade Linting Errors
- Check for unclosed `@if/@endif` pairs
- Add `@csrf` to forms
- Review XSS warnings for `{!!` usage

#### Build Failures
- Check import paths
- Verify asset references
- Review Vite configuration

#### CI Failures
- Run `npm run ci` locally first
- Check GitHub Actions logs
- Fix issues before pushing

### Getting Help

1. Run individual checks to isolate issues
2. Check tool documentation
3. Review CI logs for detailed errors
4. Ask team members for assistance

## 📈 Benefits

### For Developers
- Consistent code style across team
- Early error detection
- Automated formatting
- Better code quality

### For Project
- Reduced bugs in production
- Easier code maintenance
- Consistent user experience
- Improved security

### For CI/CD
- Faster feedback loops
- Automated quality gates
- Reliable deployments
- Reduced manual reviews

## 🎯 Next Steps

1. **Install Git hooks**: Run `./scripts/setup-git-hooks.sh`
2. **Fix existing issues**: Run `npm run quality` and address issues
3. **Team training**: Familiarize team with new tools
4. **Monitor metrics**: Track code quality improvements
5. **Iterate**: Adjust rules based on team feedback

---

For detailed documentation, see `docs/development/CODE_QUALITY.md`