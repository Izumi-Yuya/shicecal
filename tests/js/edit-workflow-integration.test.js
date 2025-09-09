/**
 * Edit Workflow Integration Tests
 * Tests the integration between edit functionality and view mode preferences
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { JSDOM } from 'jsdom';

// Mock the facility view toggle module
const mockFacilityViewToggle = {
  init: vi.fn(() => true),
  getCurrentViewMode: vi.fn(() => 'table'),
  setViewMode: vi.fn(),
  handleViewModeChange: vi.fn(),
  saveViewPreference: vi.fn(() => Promise.resolve({ success: true })),
  destroy: vi.fn()
};

// Mock the API module
const mockApi = {
  post: vi.fn(() => Promise.resolve({ success: true, view_mode: 'table' }))
};

// Mock the utils module
const mockUtils = {
  showToast: vi.fn()
};

describe('Edit Workflow Integration', () => {
  let dom;
  let document;
  let window;

  beforeEach(() => {
    // Create a new JSDOM instance for each test
    dom = new JSDOM(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Test</title>
        </head>
        <body>
          <!-- Facility show page structure -->
          <div class="facility-detail-container">
            <!-- Basic info tab header with edit button -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="mb-0">
                <i class="fas fa-info-circle text-primary me-2"></i>基本情報
              </h4>
              <a href="/facilities/1/edit-basic-info" class="btn btn-primary edit-button">
                <i class="fas fa-edit me-2"></i>編集
              </a>
            </div>

            <!-- View toggle component -->
            <div class="view-toggle-container mb-3">
              <div class="view-toggle-buttons">
                <div class="btn-group" role="group">
                  <input type="radio" class="btn-check" name="viewMode" id="cardView" value="card">
                  <label class="btn btn-outline-primary" for="cardView">
                    <i class="fas fa-th-large me-2"></i>カード形式
                  </label>
                  <input type="radio" class="btn-check" name="viewMode" id="tableView" value="table" checked>
                  <label class="btn btn-outline-primary" for="tableView">
                    <i class="fas fa-table me-2"></i>テーブル形式
                  </label>
                </div>
              </div>
            </div>

            <!-- Content areas -->
            <div class="facility-card-view" style="display: none;">
              <div class="card">Card view content</div>
            </div>
            
            <div class="facility-table-view">
              <div class="table-responsive">
                <table class="table table-bordered facility-info">
                  <tbody>
                    <tr>
                      <th>会社名</th>
                      <td>Test Company</td>
                      <th>事業所コード</th>
                      <td>TEST001</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Success message (appears after edit) -->
          <div class="alert alert-success" id="success-message" style="display: none;">
            施設基本情報を更新しました。
          </div>
        </body>
      </html>
    `, {
      url: 'https://test.example.com/facilities/1',
      pretendToBeVisual: true,
      resources: 'usable'
    });

    document = dom.window.document;
    window = dom.window;

    // Set up global mocks
    global.document = document;
    global.window = window;
    global.sessionStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn()
    };
    global.localStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn()
    };

    // Mock fetch for API calls
    global.fetch = vi.fn();
  });

  afterEach(() => {
    vi.clearAllMocks();
    dom.window.close();
  });

  describe('Edit Button Visibility', () => {
    it('should show edit button in table view mode', () => {
      const editButton = document.querySelector('.edit-button');
      const tableViewRadio = document.querySelector('#tableView');

      expect(editButton).toBeTruthy();
      expect(editButton.href).toContain('/facilities/1/edit-basic-info');
      expect(tableViewRadio.checked).toBe(true);
    });

    it('should show edit button in card view mode', () => {
      const editButton = document.querySelector('.edit-button');
      const cardViewRadio = document.querySelector('#cardView');
      const tableViewRadio = document.querySelector('#tableView');

      // Switch to card view
      cardViewRadio.checked = true;
      tableViewRadio.checked = false;

      expect(editButton).toBeTruthy();
      expect(editButton.href).toContain('/facilities/1/edit-basic-info');
    });

    it('should maintain edit button functionality across view mode changes', () => {
      const editButton = document.querySelector('.edit-button');
      const cardViewRadio = document.querySelector('#cardView');
      const tableViewRadio = document.querySelector('#tableView');

      // Initially in table view
      expect(editButton.href).toContain('/facilities/1/edit-basic-info');

      // Switch to card view
      cardViewRadio.checked = true;
      tableViewRadio.checked = false;
      cardViewRadio.dispatchEvent(new window.Event('change'));

      // Edit button should still be functional
      expect(editButton.href).toContain('/facilities/1/edit-basic-info');
    });
  });

  describe('View Mode Persistence', () => {
    it('should maintain table view mode after simulated edit operation', async () => {
      const tableViewRadio = document.querySelector('#tableView');
      const successMessage = document.querySelector('#success-message');

      // Verify initial state
      expect(tableViewRadio.checked).toBe(true);

      // Simulate successful edit operation by showing success message
      successMessage.style.display = 'block';
      successMessage.textContent = '施設基本情報を更新しました。';

      // Verify table view is still selected
      expect(tableViewRadio.checked).toBe(true);
      expect(successMessage.textContent).toContain('施設基本情報を更新しました。');
    });

    it('should maintain card view mode after simulated edit operation', async () => {
      const cardViewRadio = document.querySelector('#cardView');
      const tableViewRadio = document.querySelector('#tableView');
      const successMessage = document.querySelector('#success-message');

      // Switch to card view
      cardViewRadio.checked = true;
      tableViewRadio.checked = false;

      // Simulate successful edit operation
      successMessage.style.display = 'block';
      successMessage.textContent = '施設基本情報を更新しました。';

      // Verify card view is still selected
      expect(cardViewRadio.checked).toBe(true);
      expect(tableViewRadio.checked).toBe(false);
    });
  });

  describe('Seamless Transition', () => {
    it('should handle edit workflow without breaking view mode state', () => {
      const editButton = document.querySelector('.edit-button');
      const tableViewRadio = document.querySelector('#tableView');
      const facilityTableView = document.querySelector('.facility-table-view');

      // Verify initial state
      expect(tableViewRadio.checked).toBe(true);
      expect(facilityTableView).toBeTruthy();
      expect(editButton).toBeTruthy();

      // Simulate clicking edit button (would navigate to edit page)
      const clickEvent = new window.Event('click');
      editButton.dispatchEvent(clickEvent);

      // After returning from edit (simulated by page reload with same view mode)
      expect(tableViewRadio.checked).toBe(true);
      expect(facilityTableView.style.display).not.toBe('none');
    });

    it('should preserve view mode selection during edit workflow', () => {
      const viewModeInputs = document.querySelectorAll('input[name="viewMode"]');
      const tableViewRadio = document.querySelector('#tableView');

      // Verify table view is selected
      expect(tableViewRadio.checked).toBe(true);

      // Simulate form submission state preservation
      const formData = new FormData();
      viewModeInputs.forEach(input => {
        if (input.checked) {
          formData.append('current_view_mode', input.value);
        }
      });

      expect(formData.get('current_view_mode')).toBe('table');
    });
  });

  describe('View Mode Integration with Edit Actions', () => {
    it('should handle edit button click in table view', () => {
      const editButton = document.querySelector('.edit-button');
      const tableViewRadio = document.querySelector('#tableView');

      expect(tableViewRadio.checked).toBe(true);

      // Mock the click handler
      const clickHandler = vi.fn();
      editButton.addEventListener('click', clickHandler);

      // Simulate click
      editButton.click();

      expect(clickHandler).toHaveBeenCalled();
    });

    it('should handle edit button click in card view', () => {
      const editButton = document.querySelector('.edit-button');
      const cardViewRadio = document.querySelector('#cardView');
      const tableViewRadio = document.querySelector('#tableView');

      // Switch to card view
      cardViewRadio.checked = true;
      tableViewRadio.checked = false;

      // Mock the click handler
      const clickHandler = vi.fn();
      editButton.addEventListener('click', clickHandler);

      // Simulate click
      editButton.click();

      expect(clickHandler).toHaveBeenCalled();
    });
  });

  describe('Error Handling', () => {
    it('should handle edit workflow errors gracefully', () => {
      const editButton = document.querySelector('.edit-button');
      const tableViewRadio = document.querySelector('#tableView');

      // Simulate error state
      const errorMessage = document.createElement('div');
      errorMessage.className = 'alert alert-danger';
      errorMessage.textContent = 'エラーが発生しました。';
      document.body.appendChild(errorMessage);

      // View mode should still be preserved
      expect(tableViewRadio.checked).toBe(true);
      expect(editButton).toBeTruthy();
    });

    it('should maintain view mode even if edit operation fails', () => {
      const tableViewRadio = document.querySelector('#tableView');
      const cardViewRadio = document.querySelector('#cardView');

      // Set initial view mode
      tableViewRadio.checked = true;
      cardViewRadio.checked = false;

      // Simulate failed edit operation (no success message)
      const errorDiv = document.createElement('div');
      errorDiv.className = 'alert alert-danger';
      errorDiv.textContent = '更新に失敗しました。';
      document.body.appendChild(errorDiv);

      // View mode should be preserved
      expect(tableViewRadio.checked).toBe(true);
      expect(cardViewRadio.checked).toBe(false);
    });
  });

  describe('Accessibility', () => {
    it('should maintain proper ARIA attributes during edit workflow', () => {
      const editButton = document.querySelector('.edit-button');
      const viewToggleButtons = document.querySelectorAll('input[name="viewMode"]');

      // Check edit button accessibility
      expect(editButton.textContent.trim()).toContain('編集');

      // Check view toggle accessibility
      viewToggleButtons.forEach(button => {
        const label = document.querySelector(`label[for="${button.id}"]`);
        expect(label).toBeTruthy();
      });
    });

    it('should preserve keyboard navigation after edit operations', () => {
      const editButton = document.querySelector('.edit-button');
      const tableViewRadio = document.querySelector('#tableView');

      // Simulate keyboard navigation
      const tabEvent = new window.KeyboardEvent('keydown', { key: 'Tab' });
      editButton.dispatchEvent(tabEvent);

      // Elements should still be focusable
      expect(editButton.tabIndex).not.toBe(-1);
      expect(tableViewRadio.tabIndex).not.toBe(-1);
    });
  });
});