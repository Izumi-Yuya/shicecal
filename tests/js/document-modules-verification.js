/**
 * Document Modules Verification
 * ドキュメント管理モジュールの検証
 * Simple verification that modules can be imported and instantiated
 */

import { DocumentManager } from '../../resources/js/modules/document-manager.js';
import { DocumentUploadManager } from '../../resources/js/modules/document-upload.js';
import { DocumentFileManager } from '../../resources/js/modules/document-file-manager.js';

console.log('✓ DocumentManager imported successfully');
console.log('✓ DocumentUploadManager imported successfully');
console.log('✓ DocumentFileManager imported successfully');

// Test basic instantiation without DOM dependencies
try {
  const facilityId = 123;

  // Test that classes can be instantiated
  console.log('Testing class instantiation...');

  // Mock minimal DOM for testing
  if (typeof document === 'undefined') {
    global.document = {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => { },
      createElement: () => ({ textContent: '', innerHTML: '' }),
      body: { insertAdjacentHTML: () => { } }
    };
  }

  if (typeof window === 'undefined') {
    global.window = {
      location: { search: '' },
      history: { replaceState: () => { } },
      innerWidth: 1024,
      innerHeight: 768
    };
  }

  // Test DocumentUploadManager (least dependencies)
  const uploadManager = new DocumentUploadManager(facilityId);
  console.log('✓ DocumentUploadManager instantiated successfully');

  // Test DocumentFileManager
  const fileManager = new DocumentFileManager(facilityId);
  console.log('✓ DocumentFileManager instantiated successfully');

  // Test basic properties
  console.log('Testing basic properties...');
  console.log(`Upload manager facility ID: ${uploadManager.facilityId}`);
  console.log(`File manager facility ID: ${fileManager.facilityId}`);

  console.log('✓ All modules verified successfully!');

} catch (error) {
  console.error('✗ Module verification failed:', error.message);
  process.exit(1);
}