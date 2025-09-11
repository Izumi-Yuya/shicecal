#!/usr/bin/env node

/**
 * Migration script to complete the transition from legacy land-info.js 
 * to the new modular architecture
 */

const fs = require('fs');
const path = require('path');

const LEGACY_FILE = 'resources/js/land-info.js';
const BACKUP_FILE = 'resources/js/land-info.js.backup';
const NEW_FILE = 'resources/js/land-info-new.js';

console.log('🚀 Starting Land Info migration...');

// Step 1: Backup the legacy file
if (fs.existsSync(LEGACY_FILE)) {
  console.log('📦 Backing up legacy file...');
  fs.copyFileSync(LEGACY_FILE, BACKUP_FILE);
  console.log(`✅ Legacy file backed up to ${BACKUP_FILE}`);
}

// Step 2: Check if new modular files exist
const requiredModules = [
  'resources/js/modules/land-info/LandInfoManager.js',
  'resources/js/modules/land-info/FormValidator.js',
  'resources/js/modules/land-info/Calculator.js',
  'resources/js/modules/land-info/SectionManager.js',
  'resources/js/modules/land-info/EventManager.js',
  'resources/js/modules/land-info/DOMCache.js'
];

console.log('🔍 Checking modular files...');
const missingModules = requiredModules.filter(file => !fs.existsSync(file));

if (missingModules.length > 0) {
  console.error('❌ Missing required modules:');
  missingModules.forEach(file => console.error(`   - ${file}`));
  process.exit(1);
}

console.log('✅ All modular files present');

// Step 3: Update Vite configuration if needed
const viteConfig = 'vite.config.js';
if (fs.existsSync(viteConfig)) {
  console.log('📝 Checking Vite configuration...');
  const config = fs.readFileSync(viteConfig, 'utf8');

  if (config.includes('land-info.js') && !config.includes('land-info-new.js')) {
    console.log('⚠️  Please update vite.config.js to use land-info-new.js instead of land-info.js');
  }
}

// Step 4: Check Blade templates
const bladeFiles = [
  'resources/views/facilities/land-info/edit.blade.php'
];

console.log('🔍 Checking Blade templates...');
bladeFiles.forEach(file => {
  if (fs.existsSync(file)) {
    const content = fs.readFileSync(file, 'utf8');
    if (content.includes('land-info.js') && !content.includes('land-info-new.js')) {
      console.log(`⚠️  Please update ${file} to use land-info-new.js`);
    }
  }
});

// Step 5: Run tests
console.log('🧪 Running tests...');
const { execSync } = require('child_process');

try {
  execSync('npm run test -- land-info-manager.test.js', { stdio: 'inherit' });
  console.log('✅ Tests passed');
} catch (error) {
  console.error('❌ Tests failed. Please fix issues before completing migration.');
  process.exit(1);
}

// Step 6: Final cleanup recommendation
console.log('\n🎉 Migration checks completed!');
console.log('\n📋 Next steps:');
console.log('1. Test the new modular implementation thoroughly');
console.log('2. Update any remaining references to the old land-info.js');
console.log('3. Remove the legacy file when confident in the new implementation');
console.log(`4. Remove backup file ${BACKUP_FILE} when no longer needed`);

console.log('\n✨ Migration script completed successfully!');