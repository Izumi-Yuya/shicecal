#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

/**
 * Blade Template Linter
 * Validates Blade template syntax and common issues
 */
class BladeLinter {
  constructor() {
    this.errors = [];
    this.warnings = [];
  }

  /**
   * Find all Blade template files
   */
  findBladeFiles(dir = 'resources/views') {
    const files = [];

    function traverse(currentDir) {
      const items = fs.readdirSync(currentDir);

      for (const item of items) {
        const fullPath = path.join(currentDir, item);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
          traverse(fullPath);
        } else if (item.endsWith('.blade.php')) {
          files.push(fullPath);
        }
      }
    }

    if (fs.existsSync(dir)) {
      traverse(dir);
    }

    return files;
  }

  /**
   * Validate Blade template syntax
   */
  validateBladeFile(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');
    const fileErrors = [];
    const fileWarnings = [];

    // Check for common Blade syntax issues
    lines.forEach((line, index) => {
      const lineNumber = index + 1;

      // Check for unclosed Blade directives
      const openDirectives = line.match(/@(if|foreach|for|while|switch|section|push|component|slot)\b/g) || [];
      const closeDirectives = line.match(/@(endif|endforeach|endfor|endwhile|endswitch|endsection|endpush|endcomponent|endslot)\b/g) || [];

      // Check for unmatched HTML tags (basic check)
      const openTags = (line.match(/<[^\/][^>]*[^\/]>/g) || []).filter(tag =>
        !tag.match(/<(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)/i)
      );
      const closeTags = line.match(/<\/[^>]+>/g) || [];

      // Check for missing CSRF tokens in forms
      if (line.includes('<form') && !content.includes('@csrf') && !content.includes('csrf_token()')) {
        fileWarnings.push({
          line: lineNumber,
          message: 'Form detected without CSRF token protection',
          type: 'security'
        });
      }

      // Check for potential XSS vulnerabilities
      if (line.includes('{!!') && !line.includes('!!}')) {
        fileErrors.push({
          line: lineNumber,
          message: 'Unclosed raw output directive {!! - potential XSS vulnerability',
          type: 'security'
        });
      }

      // Check for missing alt attributes on images
      if (line.includes('<img') && !line.includes('alt=')) {
        fileWarnings.push({
          line: lineNumber,
          message: 'Image tag missing alt attribute for accessibility',
          type: 'accessibility'
        });
      }

      // Check for inline styles (should use CSS classes)
      if (line.includes('style=') && !line.includes('style=""')) {
        fileWarnings.push({
          line: lineNumber,
          message: 'Inline styles detected - consider using CSS classes',
          type: 'maintainability'
        });
      }
    });

    // Check for balanced Blade directives (simplified)
    const directiveBalance = this.checkDirectiveBalance(content);
    if (directiveBalance.length > 0) {
      fileErrors.push(...directiveBalance);
    }

    return { errors: fileErrors, warnings: fileWarnings };
  }

  /**
   * Check for balanced Blade directives
   */
  checkDirectiveBalance(content) {
    const errors = [];
    const stack = [];
    const lines = content.split('\n');

    const directivePairs = {
      '@if': '@endif',
      '@foreach': '@endforeach',
      '@for': '@endfor',
      '@while': '@endwhile',
      '@switch': '@endswitch',
      '@section': '@endsection',
      '@push': '@endpush',
      '@component': '@endcomponent',
      '@slot': '@endslot'
    };

    lines.forEach((line, index) => {
      const lineNumber = index + 1;

      // Find opening directives
      Object.keys(directivePairs).forEach(openDir => {
        const regex = new RegExp(`${openDir.replace('@', '@')}\\b`, 'g');
        const matches = line.match(regex);
        if (matches) {
          matches.forEach(() => {
            stack.push({ directive: openDir, line: lineNumber });
          });
        }
      });

      // Find closing directives
      Object.values(directivePairs).forEach(closeDir => {
        const regex = new RegExp(`${closeDir.replace('@', '@')}\\b`, 'g');
        const matches = line.match(regex);
        if (matches) {
          matches.forEach(() => {
            const expected = Object.keys(directivePairs).find(key => directivePairs[key] === closeDir);
            const last = stack.pop();

            if (!last) {
              errors.push({
                line: lineNumber,
                message: `Unexpected closing directive ${closeDir}`,
                type: 'syntax'
              });
            } else if (last.directive !== expected) {
              errors.push({
                line: lineNumber,
                message: `Mismatched directive: expected ${directivePairs[last.directive]}, found ${closeDir}`,
                type: 'syntax'
              });
            }
          });
        }
      });
    });

    // Check for unclosed directives
    stack.forEach(item => {
      errors.push({
        line: item.line,
        message: `Unclosed directive ${item.directive}`,
        type: 'syntax'
      });
    });

    return errors;
  }

  /**
   * Run the linter
   */
  run() {
    console.log('🔍 Running Blade template linter...\n');

    const bladeFiles = this.findBladeFiles();

    if (bladeFiles.length === 0) {
      console.log('No Blade template files found.');
      return true;
    }

    console.log(`Found ${bladeFiles.length} Blade template files\n`);

    let hasErrors = false;

    bladeFiles.forEach(filePath => {
      const result = this.validateBladeFile(filePath);

      if (result.errors.length > 0 || result.warnings.length > 0) {
        console.log(`📄 ${filePath}`);

        result.errors.forEach(error => {
          console.log(`  ❌ Line ${error.line}: ${error.message} (${error.type})`);
          hasErrors = true;
        });

        result.warnings.forEach(warning => {
          console.log(`  ⚠️  Line ${warning.line}: ${warning.message} (${warning.type})`);
        });

        console.log('');
      }
    });

    if (!hasErrors) {
      console.log('✅ All Blade templates passed linting checks!');
    } else {
      console.log('❌ Blade template linting failed with errors.');
    }

    return !hasErrors;
  }
}

// Run the linter
const linter = new BladeLinter();
const success = linter.run();
process.exit(success ? 0 : 1);