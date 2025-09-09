#!/usr/bin/env node

/**
 * Build Performance Measurement Script
 * Measures build time and asset optimization metrics
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 Measuring Build Performance...\n');

// Clean previous build
console.log('Cleaning previous build...');
try {
  execSync('rm -rf public/build', { stdio: 'inherit' });
} catch (error) {
  // Ignore if directory doesn't exist
}

// Measure build time
console.log('Building assets...');
const buildStart = Date.now();

try {
  execSync('npm run build', { stdio: 'pipe' });
  const buildTime = Date.now() - buildStart;

  console.log(`✅ Build completed in ${buildTime}ms\n`);

  // Analyze build output
  const buildDir = path.join(process.cwd(), 'public/build');
  const manifestPath = path.join(buildDir, 'manifest.json');

  if (fs.existsSync(manifestPath)) {
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));

    console.log('📊 Build Analysis:');
    console.log('==================');

    // Count assets by type
    const assets = Object.values(manifest);
    const cssAssets = assets.filter(asset => asset.file && asset.file.endsWith('.css'));
    const jsAssets = assets.filter(asset => asset.file && asset.file.endsWith('.js'));

    console.log(`Total assets: ${assets.length}`);
    console.log(`CSS files: ${cssAssets.length}`);
    console.log(`JS files: ${jsAssets.length}`);

    // Calculate total sizes
    let totalSize = 0;
    let totalCssSize = 0;
    let totalJsSize = 0;

    assets.forEach(asset => {
      if (asset.file) {
        const filePath = path.join(buildDir, asset.file);
        if (fs.existsSync(filePath)) {
          const stats = fs.statSync(filePath);
          totalSize += stats.size;

          if (asset.file.endsWith('.css')) {
            totalCssSize += stats.size;
          } else if (asset.file.endsWith('.js')) {
            totalJsSize += stats.size;
          }
        }
      }
    });

    console.log(`\nSize Analysis:`);
    console.log(`Total size: ${(totalSize / 1024).toFixed(2)} KB`);
    console.log(`CSS size: ${(totalCssSize / 1024).toFixed(2)} KB`);
    console.log(`JS size: ${(totalJsSize / 1024).toFixed(2)} KB`);

    // Check for optimization features
    console.log(`\n🔧 Optimization Features:`);

    // Check for hashed filenames
    const hashedFiles = assets.filter(asset =>
      asset.file && /\.[a-f0-9]{8,}\.(js|css)$/.test(asset.file)
    );
    console.log(`✅ Cache busting: ${hashedFiles.length}/${assets.length} files have hashes`);

    // Check for proper directory structure
    const cssInDir = cssAssets.filter(asset => asset.file.startsWith('css/')).length;
    const jsInDir = jsAssets.filter(asset => asset.file.startsWith('js/')).length;
    console.log(`✅ Directory organization: ${cssInDir}/${cssAssets.length} CSS, ${jsInDir}/${jsAssets.length} JS in proper dirs`);

    // Check for minification (basic check)
    let minifiedCount = 0;
    jsAssets.forEach(asset => {
      const filePath = path.join(buildDir, asset.file);
      if (fs.existsSync(filePath)) {
        const content = fs.readFileSync(filePath, 'utf8');
        if (!content.includes('console.log') && content.split('\n').length < 10) {
          minifiedCount++;
        }
      }
    });
    console.log(`✅ Minification: ${minifiedCount}/${jsAssets.length} JS files appear minified`);

    // Performance recommendations
    console.log(`\n💡 Performance Recommendations:`);

    if (buildTime > 2000) {
      console.log(`⚠️  Build time (${buildTime}ms) could be improved`);
    } else {
      console.log(`✅ Build time (${buildTime}ms) is good`);
    }

    if (totalSize > 500 * 1024) {
      console.log(`⚠️  Total asset size (${(totalSize / 1024).toFixed(2)} KB) is large`);
    } else {
      console.log(`✅ Total asset size (${(totalSize / 1024).toFixed(2)} KB) is reasonable`);
    }

    if (assets.length > 25) {
      console.log(`⚠️  Many assets (${assets.length}) may cause many HTTP requests`);
    } else {
      console.log(`✅ Asset count (${assets.length}) is reasonable`);
    }

  } else {
    console.log('❌ No manifest.json found');
  }

} catch (error) {
  console.error('❌ Build failed:', error.message);
  process.exit(1);
}

console.log('\n🎉 Performance measurement complete!');