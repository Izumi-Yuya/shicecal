#!/bin/bash

# Pre-commit hook for code quality checks
# Install with: ln -s ../../scripts/pre-commit-hook.sh .git/hooks/pre-commit

set -e

echo "🔍 Running pre-commit quality checks..."

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo "❌ Not in a git repository"
    exit 1
fi

# Get list of staged files
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM)

# Check if there are any staged files
if [ -z "$STAGED_FILES" ]; then
    echo "No staged files to check"
    exit 0
fi

# Initialize flags
HAS_PHP_FILES=false
HAS_JS_FILES=false
HAS_BLADE_FILES=false

# Check file types
for FILE in $STAGED_FILES; do
    if [[ $FILE == *.php ]]; then
        HAS_PHP_FILES=true
    elif [[ $FILE == *.js ]]; then
        HAS_JS_FILES=true
    elif [[ $FILE == *.blade.php ]]; then
        HAS_BLADE_FILES=true
    fi
done

# Run PHP linting if PHP files are staged
if [ "$HAS_PHP_FILES" = true ]; then
    echo "📝 Checking PHP code style..."
    if ! vendor/bin/pint --test; then
        echo "❌ PHP code style issues found. Run 'vendor/bin/pint' to fix them."
        exit 1
    fi
    echo "✅ PHP code style check passed"
fi

# Run JavaScript linting if JS files are staged
if [ "$HAS_JS_FILES" = true ]; then
    echo "📝 Checking JavaScript code quality..."
    if ! npm run lint:js; then
        echo "❌ JavaScript linting failed. Run 'npm run lint:js:fix' to fix auto-fixable issues."
        exit 1
    fi
    echo "✅ JavaScript linting passed"
fi

# Run Blade template linting if Blade files are staged
if [ "$HAS_BLADE_FILES" = true ]; then
    echo "📝 Checking Blade templates..."
    if ! npm run lint:blade; then
        echo "❌ Blade template linting failed. Please fix the issues manually."
        exit 1
    fi
    echo "✅ Blade template linting passed"
fi

# Run build test to ensure assets compile
echo "🏗️  Testing asset build..."
if ! npm run build; then
    echo "❌ Asset build failed. Please fix build issues before committing."
    exit 1
fi
echo "✅ Asset build test passed"

# Run quick tests
echo "🧪 Running quick tests..."
if ! php artisan test --stop-on-failure; then
    echo "❌ Tests failed. Please fix failing tests before committing."
    exit 1
fi

if ! npm run test; then
    echo "❌ JavaScript tests failed. Please fix failing tests before committing."
    exit 1
fi
echo "✅ All tests passed"

echo "🎉 All pre-commit checks passed! Ready to commit."