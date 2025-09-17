#!/bin/bash

# Setup Git hooks for code quality
echo "🔧 Setting up Git hooks for code quality..."

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo "❌ Not in a git repository. Please run this from the project root."
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p .git/hooks

# Install pre-commit hook
if [ -f .git/hooks/pre-commit ]; then
    echo "⚠️  Pre-commit hook already exists. Backing up to pre-commit.backup"
    mv .git/hooks/pre-commit .git/hooks/pre-commit.backup
fi

# Create symlink to our pre-commit script
ln -s ../../scripts/pre-commit-hook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

echo "✅ Pre-commit hook installed successfully!"
echo ""
echo "The hook will run the following checks before each commit:"
echo "  • PHP code style (Laravel Pint)"
echo "  • JavaScript linting (ESLint)"
echo "  • Blade template validation"
echo "  • Asset build verification"
echo "  • Quick test suite"
echo ""
echo "To skip the hook for a specific commit, use: git commit --no-verify"
echo "To uninstall the hook, delete: .git/hooks/pre-commit"