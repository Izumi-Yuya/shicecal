# NPM Scripts Reference

This document provides a comprehensive reference for all available npm scripts in the Shise-Cal project.

## 📋 Quick Reference

### Development Scripts
```bash
npm run dev              # Start development server with HMR
npm run build            # Build production assets
```

### Testing Scripts
```bash
npm run test             # Run JavaScript tests once
npm run test:watch       # Run JavaScript tests in watch mode
```

### Linting Scripts
```bash
npm run lint             # Run all linting checks
npm run lint:js          # JavaScript ESLint
npm run lint:js:fix      # Fix JavaScript issues automatically
npm run lint:blade       # Blade template validation
npm run lint:html        # HTML validation
npm run lint:php         # PHP code style check
npm run lint:php:fix     # Fix PHP code style automatically
```

### Quality Assurance Scripts
```bash
npm run quality          # Complete quality pipeline (lint + test + build)
npm run ci               # CI pipeline (alias for quality)
```

## 📖 Detailed Descriptions

### Development Scripts

#### `npm run dev`
- **Purpose**: Start Vite development server with Hot Module Replacement (HMR)
- **Usage**: During active development for real-time asset compilation
- **Output**: Development server typically runs on `http://localhost:5173`
- **Features**: 
  - Hot reload for CSS/JS changes
  - Source maps for debugging
  - Fast compilation

#### `npm run build`
- **Purpose**: Build optimized production assets
- **Usage**: Before deployment or to test production builds
- **Output**: Compiled assets in `public/build/`
- **Features**:
  - Minification and optimization
  - Asset versioning
  - Manifest generation

### Testing Scripts

#### `npm run test`
- **Purpose**: Run JavaScript test suite once
- **Framework**: Vitest
- **Usage**: CI/CD pipelines, pre-commit checks
- **Coverage**: Includes unit and integration tests
- **Exit**: Exits after completion with status code

#### `npm run test:watch`
- **Purpose**: Run tests in watch mode
- **Usage**: During development for continuous testing
- **Features**:
  - Re-runs tests on file changes
  - Interactive mode for test filtering
  - Real-time feedback

### Linting Scripts

#### `npm run lint`
- **Purpose**: Run all linting checks (JavaScript + Blade + HTML)
- **Usage**: Comprehensive code quality check
- **Combines**: `lint:js`, `lint:blade`, `lint:html`
- **Exit**: Fails if any linter reports errors

#### `npm run lint:js`
- **Purpose**: JavaScript code linting with ESLint
- **Files**: `resources/js/**/*.js`, `tests/js/**/*.js`
- **Rules**: ES6+, 4-space indentation, single quotes, semicolons
- **Checks**: Syntax errors, code quality, unused variables

#### `npm run lint:js:fix`
- **Purpose**: Automatically fix JavaScript linting issues
- **Usage**: Fix formatting and auto-fixable issues
- **Note**: Manual fixes may still be required for some issues

#### `npm run lint:blade`
- **Purpose**: Validate Blade template syntax and best practices
- **Tool**: Custom Node.js script (`scripts/lint-blade.js`)
- **Checks**:
  - Unclosed Blade directives
  - Missing CSRF tokens in forms
  - Potential XSS vulnerabilities
  - Accessibility issues (missing alt attributes)
  - Inline styles warnings

#### `npm run lint:html`
- **Purpose**: HTML structure and attribute validation
- **Tool**: HTMLHint
- **Files**: `resources/views/**/*.blade.php`
- **Config**: `.htmlhintrc`

#### `npm run lint:php`
- **Purpose**: PHP code style checking
- **Tool**: Laravel Pint
- **Command**: `vendor/bin/pint --test`
- **Standards**: PSR-12 + Laravel conventions

#### `npm run lint:php:fix`
- **Purpose**: Automatically fix PHP code style issues
- **Tool**: Laravel Pint
- **Command**: `vendor/bin/pint`
- **Usage**: Fix formatting and style issues

### Quality Assurance Scripts

#### `npm run quality`
- **Purpose**: Complete quality assurance pipeline
- **Sequence**: 
  1. `npm run lint` (all linting checks)
  2. `npm run test` (JavaScript tests)
  3. `npm run build` (production build verification)
- **Usage**: Pre-deployment checks, comprehensive validation
- **Exit**: Fails if any step fails

#### `npm run ci`
- **Purpose**: CI/CD pipeline simulation
- **Alias**: Same as `npm run quality`
- **Usage**: Local testing of CI pipeline
- **Benefits**: Catch issues before pushing to repository

## 🔄 Workflow Integration

### Daily Development
```bash
# Start development
npm run dev              # Terminal 1: Development server
npm run test:watch       # Terminal 2: Continuous testing

# Before committing
npm run lint             # Quick quality check
npm run quality          # Full pipeline (if time permits)
```

### Pre-Commit (Automated)
```bash
# Git hook automatically runs:
npm run lint:js          # If JS files changed
npm run lint:blade       # If Blade files changed
npm run lint:php         # If PHP files changed
npm run test             # Always
```

### Pre-Push
```bash
npm run ci               # Full CI pipeline locally
```

### Deployment
```bash
npm run quality          # Quality gate
npm run build            # Production assets
```

## 🛠️ Configuration Files

| Script | Configuration File | Purpose |
|--------|-------------------|---------|
| `lint:js` | `eslint.config.js` | ESLint rules and globals |
| `lint:html` | `.htmlhintrc` | HTMLHint validation rules |
| `lint:blade` | `scripts/lint-blade.js` | Custom Blade linter logic |
| `test` | `vitest.config.js` | Vitest configuration |
| `build` | `vite.config.js` | Vite build configuration |

## 🚨 Troubleshooting

### Common Issues

#### Script Not Found
```bash
# Ensure dependencies are installed
npm install

# Check package.json for script definition
cat package.json | grep -A 20 '"scripts"'
```

#### Linting Failures
```bash
# Run individual linters to isolate issues
npm run lint:js
npm run lint:blade
npm run lint:html

# Use fix commands where available
npm run lint:js:fix
npm run lint:php:fix
```

#### Build Failures
```bash
# Check for syntax errors
npm run lint:js

# Clear cache and rebuild
rm -rf node_modules/.vite
npm run build
```

#### Test Failures
```bash
# Run tests with verbose output
npm run test -- --reporter=verbose

# Run specific test file
npm run test -- path/to/test.js
```

### Performance Tips

#### Faster Development
```bash
# Use watch modes for continuous feedback
npm run dev              # Asset compilation
npm run test:watch       # Testing
```

#### Efficient CI
```bash
# Run quality checks locally before pushing
npm run ci

# Use lint:fix commands to reduce manual work
npm run lint:js:fix
npm run lint:php:fix
```

## 📊 Metrics and Monitoring

### Quality Metrics
- **Linting Error Count**: Track reduction over time
- **Test Coverage**: Monitor coverage percentage
- **Build Success Rate**: Track build reliability
- **CI Pipeline Duration**: Monitor performance

### Performance Metrics
- **Build Time**: Track asset compilation speed
- **Bundle Size**: Monitor asset optimization
- **Test Execution Time**: Track test performance

## 🎯 Best Practices

### Script Usage
1. **Use `npm run quality` before important commits**
2. **Run `npm run ci` before pushing to main branch**
3. **Use watch modes during active development**
4. **Fix linting issues immediately**
5. **Keep scripts fast and reliable**

### Team Workflow
1. **Establish consistent script usage patterns**
2. **Document custom scripts and their purposes**
3. **Monitor script performance and optimize as needed**
4. **Share knowledge about script capabilities**

## 🔗 Related Documentation

- [Code Quality Guide](CODE_QUALITY.md)
- [Development Setup](../setup/DEVELOPMENT.md)
- [CI/CD Pipeline](../deployment/CI_CD.md)
- [Testing Strategy](TESTING.md)

---

This reference should be updated whenever new scripts are added or existing scripts are modified.