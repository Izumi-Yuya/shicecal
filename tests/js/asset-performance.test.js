/**
 * Asset Loading Performance Tests
 * Tests asset loading performance and optimization
 */

import { describe, it, expect, beforeAll } from 'vitest';
import fs from 'fs';
import path from 'path';

describe('Asset Performance Tests', () => {
    let manifest;
    let buildDir;

    beforeAll(() => {
        buildDir = path.join(process.cwd(), 'public/build');
        const manifestPath = path.join(buildDir, 'manifest.json');

        if (fs.existsSync(manifestPath)) {
            manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
        }
    });

    describe('Asset Optimization', () => {
        it('should have generated a manifest file', () => {
            expect(manifest).toBeDefined();
            expect(typeof manifest).toBe('object');
        });

        it('should have versioned asset files with hashes', () => {
            const assetFiles = Object.values(manifest);

            assetFiles.forEach(asset => {
                if (asset.file) {
                    // Check that files have hash in their names for cache busting
                    expect(asset.file).toMatch(/\.[a-f0-9]{8,}\.(js|css)$/);
                }
            });
        });

        it('should have minified CSS files', () => {
            const cssFiles = Object.values(manifest)
                .filter(asset => asset.file && asset.file.endsWith('.css'))
                .map(asset => path.join(buildDir, asset.file));

            cssFiles.forEach(cssFile => {
                if (fs.existsSync(cssFile)) {
                    const content = fs.readFileSync(cssFile, 'utf8');

                    // Minified CSS should not have unnecessary whitespace
                    expect(content).not.toMatch(/\n\s+/);
                    expect(content).not.toMatch(/;\s+/);

                    // Should not contain comments (basic check)
                    expect(content).not.toMatch(/\/\*.*?\*\//);
                }
            });
        });

        it('should have minified JavaScript files', () => {
            const jsFiles = Object.values(manifest)
                .filter(asset => asset.file && asset.file.endsWith('.js'))
                .map(asset => path.join(buildDir, asset.file));

            jsFiles.forEach(jsFile => {
                if (fs.existsSync(jsFile)) {
                    const content = fs.readFileSync(jsFile, 'utf8');

                    // Minified JS should not have console.log statements
                    expect(content).not.toMatch(/console\.log/);
                    expect(content).not.toMatch(/console\.debug/);

                    // Should be compact (no unnecessary line breaks)
                    const lines = content.split('\n');
                    expect(lines.length).toBeLessThan(10); // Minified should be very compact
                }
            });
        });

        it('should organize assets in proper directories', () => {
            const assets = Object.values(manifest);

            const cssAssets = assets.filter(asset => asset.file && asset.file.endsWith('.css'));
            const jsAssets = assets.filter(asset => asset.file && asset.file.endsWith('.js'));

            // CSS files should be in css/ directory
            cssAssets.forEach(asset => {
                expect(asset.file).toMatch(/^css\//);
            });

            // JS files should be in js/ directory
            jsAssets.forEach(asset => {
                expect(asset.file).toMatch(/^js\//);
            });
        });

        it('should have reasonable file sizes', () => {
            const assets = Object.values(manifest);

            assets.forEach(asset => {
                if (asset.file) {
                    const filePath = path.join(buildDir, asset.file);
                    if (fs.existsSync(filePath)) {
                        const stats = fs.statSync(filePath);
                        const sizeKB = stats.size / 1024;

                        // Individual files should not be excessively large
                        if (asset.file.endsWith('.js')) {
                            expect(sizeKB).toBeLessThan(100); // JS files under 100KB
                        }
                        if (asset.file.endsWith('.css')) {
                            expect(sizeKB).toBeLessThan(50); // CSS files under 50KB
                        }
                    }
                }
            });
        });

        it('should have proper chunk splitting', () => {
            const jsAssets = Object.values(manifest)
                .filter(asset => asset.file && asset.file.endsWith('.js'));

            // Should have multiple JS chunks for better loading
            expect(jsAssets.length).toBeGreaterThan(5);

            // Check for expected chunks
            const chunkNames = jsAssets.map(asset => asset.file);
            const hasSharedChunks = chunkNames.some(name => name.includes('shared'));
            const hasModuleChunks = chunkNames.some(name => name.includes('modules') || name.includes('facilities'));

            expect(hasSharedChunks || hasModuleChunks).toBe(true);
        });
    });

    describe('Asset Loading Simulation', () => {
        it('should simulate fast asset loading', async () => {
            const startTime = Date.now();

            // Simulate loading all CSS files
            const cssFiles = Object.values(manifest)
                .filter(asset => asset.file && asset.file.endsWith('.css'))
                .map(asset => path.join(buildDir, asset.file));

            const cssLoadPromises = cssFiles.map(file => {
                return new Promise((resolve) => {
                    if (fs.existsSync(file)) {
                        // Simulate file read time
                        fs.readFile(file, 'utf8', () => resolve());
                    } else {
                        resolve();
                    }
                });
            });

            await Promise.all(cssLoadPromises);

            const loadTime = Date.now() - startTime;

            // Asset loading simulation should be fast
            expect(loadTime).toBeLessThan(100); // Under 100ms for local files
        });

        it('should have efficient asset count', () => {
            const totalAssets = Object.keys(manifest).length;

            // Should have a reasonable number of assets (not too many HTTP requests)
            expect(totalAssets).toBeLessThan(30);
            expect(totalAssets).toBeGreaterThan(10);
        });
    });
});
