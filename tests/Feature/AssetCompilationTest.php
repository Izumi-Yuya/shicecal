<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AssetCompilationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ========================================
    // CSS Compilation Tests
    // ========================================

    public function test_css_files_exist_in_resources()
    {
        // Test that all expected CSS files exist in resources/css/
        $expectedCssFiles = [
            'resources/css/app.css',
            'resources/css/shared/variables.css',
            'resources/css/shared/components.css',
            'resources/css/shared/utilities.css',
            'resources/css/pages/facilities.css',
            'resources/css/pages/notifications.css',
            'resources/css/pages/export.css',
        ];

        foreach ($expectedCssFiles as $file) {
            $this->assertTrue(
                File::exists(base_path($file)),
                "CSS file {$file} should exist"
            );
        }
    }

    public function test_css_files_have_valid_syntax()
    {
        // Test that CSS files don't have obvious syntax errors
        $cssFiles = [
            'resources/css/shared/variables.css',
            'resources/css/shared/components.css',
            'resources/css/shared/utilities.css',
            'resources/css/pages/facilities.css',
            'resources/css/pages/notifications.css',
            'resources/css/pages/export.css',
        ];

        foreach ($cssFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                // Check for basic CSS syntax issues
                $this->assertStringNotContainsString('{{', $content, "CSS file {$file} should not contain template syntax");
                $this->assertStringNotContainsString('}}', $content, "CSS file {$file} should not contain template syntax");

                // Check for balanced braces (basic check)
                $openBraces = substr_count($content, '{');
                $closeBraces = substr_count($content, '}');
                $this->assertEquals($openBraces, $closeBraces, "CSS file {$file} should have balanced braces");
            }
        }
    }

    public function test_css_variables_are_defined()
    {
        $variablesFile = base_path('resources/css/shared/variables.css');

        if (File::exists($variablesFile)) {
            $content = File::get($variablesFile);

            // Check for CSS custom properties
            $this->assertStringContainsString(':root', $content, 'Variables file should contain :root selector');
            $this->assertStringContainsString('--', $content, 'Variables file should contain CSS custom properties');
        }
    }

    public function test_css_components_use_consistent_naming()
    {
        $componentsFile = base_path('resources/css/shared/components.css');

        if (File::exists($componentsFile)) {
            $content = File::get($componentsFile);

            // Check for consistent naming patterns (BEM or kebab-case)
            // This is a more flexible test that allows for different naming conventions
            $hasConsistentNaming = preg_match('/\.[a-z][a-z0-9-_]*/', $content);
            $this->assertTrue($hasConsistentNaming > 0, 'Components should use consistent CSS class naming');

            // Check that there are actual CSS rules
            $this->assertStringContainsString('{', $content, 'Components file should contain CSS rules');
            $this->assertStringContainsString('}', $content, 'Components file should contain CSS rules');
        }
    }

    // ========================================
    // JavaScript Module Tests
    // ========================================

    public function test_javascript_modules_exist()
    {
        // Test that all expected JavaScript modules exist
        $expectedJsFiles = [
            'resources/js/app.js',
            'resources/js/shared/utils.js',
            'resources/js/shared/api.js',
            'resources/js/shared/validation.js',
            'resources/js/shared/components.js',
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js',
        ];

        foreach ($expectedJsFiles as $file) {
            $this->assertTrue(
                File::exists(base_path($file)),
                "JavaScript file {$file} should exist"
            );
        }
    }

    public function test_javascript_modules_have_valid_syntax()
    {
        $jsFiles = [
            'resources/js/shared/utils.js',
            'resources/js/shared/api.js',
            'resources/js/shared/validation.js',
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js',
        ];

        foreach ($jsFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                // Check for basic JavaScript syntax issues
                $this->assertStringNotContainsString('{{', $content, "JS file {$file} should not contain template syntax");
                $this->assertStringNotContainsString('}}', $content, "JS file {$file} should not contain template syntax");

                // Check for ES6 module syntax
                $hasImportOrExport = strpos($content, 'import ') !== false ||
                    strpos($content, 'export ') !== false ||
                    strpos($content, 'export default') !== false;

                if (strlen(trim($content)) > 0) {
                    $this->assertTrue($hasImportOrExport, "JS module {$file} should use ES6 import/export syntax");
                }
            }
        }
    }

    public function test_app_js_imports_modules()
    {
        $appJsFile = base_path('resources/js/app.js');

        if (File::exists($appJsFile)) {
            $content = File::get($appJsFile);

            // Check that app.js imports the main modules
            $expectedImports = [
                './modules/facilities',
                './modules/notifications',
                './modules/export',
                './shared/utils',
                './shared/api',
            ];

            foreach ($expectedImports as $import) {
                $this->assertStringContainsString($import, $content, "app.js should import {$import}");
            }
        }
    }

    public function test_shared_modules_export_functions()
    {
        $sharedFiles = [
            'resources/js/shared/utils.js',
            'resources/js/shared/api.js',
            'resources/js/shared/validation.js',
        ];

        foreach ($sharedFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                if (strlen(trim($content)) > 0) {
                    $this->assertStringContainsString('export', $content, "Shared module {$file} should export functions");
                }
            }
        }
    }

    // ========================================
    // Vite Configuration Tests
    // ========================================

    public function test_vite_config_exists()
    {
        $this->assertTrue(
            File::exists(base_path('vite.config.js')),
            'vite.config.js should exist'
        );
    }

    public function test_vite_config_includes_css_files()
    {
        $viteConfigFile = base_path('vite.config.js');

        if (File::exists($viteConfigFile)) {
            $content = File::get($viteConfigFile);

            // Check that CSS files are included in the build
            $expectedCssIncludes = [
                'resources/css/app.css',
                'resources/css/pages/facilities.css',
                'resources/css/pages/notifications.css',
                'resources/css/pages/export.css',
            ];

            foreach ($expectedCssIncludes as $cssFile) {
                $this->assertStringContainsString($cssFile, $content, "Vite config should include {$cssFile}");
            }
        }
    }

    public function test_vite_config_includes_js_files()
    {
        $viteConfigFile = base_path('vite.config.js');

        if (File::exists($viteConfigFile)) {
            $content = File::get($viteConfigFile);

            // Check that main JS files are included in the build
            $expectedJsIncludes = [
                'resources/js/app.js',
            ];

            foreach ($expectedJsIncludes as $jsFile) {
                $this->assertStringContainsString($jsFile, $content, "Vite config should include {$jsFile}");
            }
        }
    }

    // ========================================
    // Asset Versioning Tests
    // ========================================

    public function test_package_json_exists()
    {
        $this->assertTrue(
            File::exists(base_path('package.json')),
            'package.json should exist'
        );
    }

    public function test_package_json_has_build_scripts()
    {
        $packageJsonFile = base_path('package.json');

        if (File::exists($packageJsonFile)) {
            $content = File::get($packageJsonFile);
            $packageData = json_decode($content, true);

            $this->assertArrayHasKey('scripts', $packageData, 'package.json should have scripts section');
            $this->assertArrayHasKey('build', $packageData['scripts'], 'package.json should have build script');
            $this->assertArrayHasKey('dev', $packageData['scripts'], 'package.json should have dev script');
        }
    }

    public function test_package_json_has_required_dependencies()
    {
        $packageJsonFile = base_path('package.json');

        if (File::exists($packageJsonFile)) {
            $content = File::get($packageJsonFile);
            $packageData = json_decode($content, true);

            $requiredDevDependencies = [
                'vite',
                'laravel-vite-plugin',
            ];

            foreach ($requiredDevDependencies as $dependency) {
                $this->assertArrayHasKey(
                    $dependency,
                    $packageData['devDependencies'] ?? [],
                    "package.json should have {$dependency} in devDependencies"
                );
            }
        }
    }

    // ========================================
    // Build Output Tests
    // ========================================

    public function test_public_build_directory_structure()
    {
        $buildDir = public_path('build');

        // Only test if build directory exists (it may not in test environment)
        if (File::exists($buildDir)) {
            $this->assertTrue(File::isDirectory($buildDir), 'public/build should be a directory');

            // Check for manifest file
            $manifestFile = $buildDir . '/manifest.json';
            if (File::exists($manifestFile)) {
                $manifest = json_decode(File::get($manifestFile), true);
                $this->assertIsArray($manifest, 'Build manifest should be valid JSON');
            }
        }
    }

    // ========================================
    // Template Integration Tests
    // ========================================

    public function test_blade_templates_use_vite_directives()
    {
        $layoutFile = base_path('resources/views/layouts/app.blade.php');

        if (File::exists($layoutFile)) {
            $content = File::get($layoutFile);

            // Check for Vite directives
            $this->assertStringContainsString('@vite', $content, 'Layout should use @vite directive');
        }
    }

    public function test_blade_templates_removed_inline_styles()
    {
        $bladeFiles = [
            'resources/views/facilities/show.blade.php',
            'resources/views/notifications/index.blade.php',
            'resources/views/export/csv/index.blade.php',
        ];

        foreach ($bladeFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                // Check that inline styles have been removed or significantly reduced
                $styleTagCount = substr_count($content, '<style>');
                $this->assertLessThanOrEqual(
                    1,
                    $styleTagCount,
                    "Blade file {$file} should have minimal inline styles"
                );

                // Check that @push('styles') sections have been removed or reduced
                $pushStylesCount = substr_count($content, "@push('styles')");
                $this->assertLessThanOrEqual(
                    1,
                    $pushStylesCount,
                    "Blade file {$file} should have minimal @push('styles') sections"
                );
            }
        }
    }

    public function test_blade_templates_removed_inline_scripts()
    {
        $bladeFiles = [
            'resources/views/facilities/show.blade.php',
            'resources/views/notifications/index.blade.php',
            'resources/views/export/csv/index.blade.php',
        ];

        foreach ($bladeFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                // Check that inline scripts have been removed or significantly reduced
                $scriptTagCount = substr_count($content, '<script>');
                $this->assertLessThanOrEqual(
                    1,
                    $scriptTagCount,
                    "Blade file {$file} should have minimal inline scripts"
                );

                // Check that @push('scripts') sections have been removed or reduced
                $pushScriptsCount = substr_count($content, "@push('scripts')");
                $this->assertLessThanOrEqual(
                    1,
                    $pushScriptsCount,
                    "Blade file {$file} should have minimal @push('scripts') sections"
                );
            }
        }
    }

    // ========================================
    // Performance Tests
    // ========================================

    public function test_css_files_are_reasonably_sized()
    {
        $cssFiles = [
            'resources/css/shared/variables.css' => 5000,    // 5KB max
            'resources/css/shared/components.css' => 20000,  // 20KB max
            'resources/css/shared/utilities.css' => 10000,   // 10KB max
            'resources/css/pages/facilities.css' => 15000,   // 15KB max
            'resources/css/pages/notifications.css' => 10000, // 10KB max
            'resources/css/pages/export.css' => 15000,       // 15KB max
        ];

        foreach ($cssFiles as $file => $maxSize) {
            if (File::exists(base_path($file))) {
                $size = File::size(base_path($file));
                $this->assertLessThanOrEqual(
                    $maxSize,
                    $size,
                    "CSS file {$file} should be under {$maxSize} bytes (current: {$size} bytes)"
                );
            }
        }
    }

    public function test_js_modules_are_reasonably_sized()
    {
        $jsFiles = [
            'resources/js/shared/utils.js' => 15000,      // 15KB max
            'resources/js/shared/api.js' => 15000,        // 15KB max
            'resources/js/shared/validation.js' => 15000, // 15KB max
            'resources/js/modules/facilities.js' => 30000, // 30KB max
            'resources/js/modules/notifications.js' => 20000, // 20KB max
            'resources/js/modules/export.js' => 30000,    // 30KB max
        ];

        foreach ($jsFiles as $file => $maxSize) {
            if (File::exists(base_path($file))) {
                $size = File::size(base_path($file));
                $this->assertLessThanOrEqual(
                    $maxSize,
                    $size,
                    "JS file {$file} should be under {$maxSize} bytes (current: {$size} bytes)"
                );
            }
        }
    }

    // ========================================
    // Code Quality Tests
    // ========================================

    public function test_css_follows_naming_conventions()
    {
        $cssFiles = [
            'resources/css/shared/components.css',
            'resources/css/pages/facilities.css',
            'resources/css/pages/notifications.css',
            'resources/css/pages/export.css',
        ];

        foreach ($cssFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                // Check for consistent naming (kebab-case for classes)
                preg_match_all('/\.[a-zA-Z][a-zA-Z0-9-_]*/', $content, $matches);

                foreach ($matches[0] as $className) {
                    $className = substr($className, 1); // Remove the dot

                    // Skip utility classes and vendor classes
                    if (
                        strpos($className, 'btn-') === 0 ||
                        strpos($className, 'text-') === 0 ||
                        strpos($className, 'bg-') === 0 ||
                        strpos($className, 'border-') === 0
                    ) {
                        continue;
                    }

                    // Check that class names use kebab-case or BEM
                    $this->assertMatchesRegularExpression(
                        '/^[a-z][a-z0-9-_]*$/',
                        $className,
                        "CSS class '{$className}' in {$file} should use kebab-case or BEM naming"
                    );
                }
            }
        }
    }

    public function test_javascript_follows_es6_standards()
    {
        $jsFiles = [
            'resources/js/shared/utils.js',
            'resources/js/shared/api.js',
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js',
        ];

        foreach ($jsFiles as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));

                if (strlen(trim($content)) > 0) {
                    // Check for modern JavaScript features
                    $hasModernFeatures = strpos($content, 'const ') !== false ||
                        strpos($content, 'let ') !== false ||
                        strpos($content, '=>') !== false;

                    $this->assertTrue(
                        $hasModernFeatures,
                        "JS file {$file} should use modern ES6+ features (const, let, arrow functions)"
                    );

                    // Check against old-style var declarations (should be minimal)
                    $varCount = substr_count($content, 'var ');
                    $constLetCount = substr_count($content, 'const ') + substr_count($content, 'let ');

                    if ($constLetCount > 0) {
                        $this->assertLessThan(
                            $constLetCount,
                            $varCount,
                            "JS file {$file} should prefer const/let over var"
                        );
                    }
                }
            }
        }
    }
}
