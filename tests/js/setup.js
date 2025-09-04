/**
 * Test setup file for JavaScript tests
 */

// Mock DOM elements and global functions that might be needed
global.alert = vi.fn();
global.confirm = vi.fn(() => true);

// Mock fetch for API calls
global.fetch = vi.fn();

// Mock console methods to avoid noise in tests
global.console = {
  ...console,
  error: vi.fn(),
  warn: vi.fn(),
  log: vi.fn()
};

// Setup DOM helpers
beforeEach(() => {
  document.body.innerHTML = '';
  vi.clearAllMocks();
});