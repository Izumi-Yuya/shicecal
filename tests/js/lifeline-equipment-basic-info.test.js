/**
 * @vitest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { LifelineEquipmentManager } from '../../resources/js/modules/lifeline-equipment.js';

// Mock fetch globally
global.fetch = vi.fn();

// Mock Bootstrap
global.bootstrap = {
  Tab: vi.fn().mockImplementation(() => ({})),
  Collapse: vi.fn().mockImplementation(() => ({
    show: vi.fn(),
    hide: vi.fn()
  }))
};

describe('LifelineEquipmentManager - Basic Info Card', () => {
  let manager;
  let mockContainer;

  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';

    // Create mock facility ID
    window.facilityId = 123;

    // Create mock lifeline equipment container
    mockContainer = document.createElement('div');
    mockContainer.id = 'lifeline-equipment';
    mockContainer.innerHTML = `
      <div class="tab-content">
        <div class="tab-pane fade show active" id="electrical">
          <div class="card equipment-card" data-section="electrical_basic" data-card-type="basic">
            <div class="card-header">
              <h5>基本情報</h5>
            </div>
            <div class="card-body">
              <div class="display-mode">
                <div class="facility-detail-table">
                  <div class="detail-row">
                    <span class="detail-label">電気契約会社</span>
                    <span class="detail-value">東京電力</span>
                  </div>
                </div>
                <button class="btn btn-primary edit-card-btn" data-card="basic" data-section="electrical_basic">
                  編集
                </button>
              </div>
              <div class="edit-mode d-none">
                <form class="equipment-form" data-section="electrical_basic" data-card="basic">
                  <input type="text" name="basic_info[electrical_contractor]" value="東京電力">
                  <input type="text" name="basic_info[safety_management_company]" value="">
                  <button type="submit" class="save-card-btn" data-card="basic" data-section="electrical_basic">
                    保存
                  </button>
                  <button type="button" class="cancel-edit-btn" data-card="basic" data-section="electrical_basic">
                    キャンセル
                  </button>
                  <div class="loading-indicator d-none">保存中...</div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(mockContainer);

    // Add CSRF token meta tag
    const csrfMeta = document.createElement('meta');
    csrfMeta.name = 'csrf-token';
    csrfMeta.content = 'test-csrf-token';
    document.head.appendChild(csrfMeta);

    // Reset fetch mock
    fetch.mockClear();

    // Create manager instance
    manager = new LifelineEquipmentManager();
  });

  it('should enter edit mode when edit button is clicked', () => {
    const editButton = document.querySelector('.edit-card-btn');
    const displayMode = document.querySelector('.display-mode');
    const editMode = document.querySelector('.edit-mode');

    // Initially display mode should be visible, edit mode hidden
    expect(displayMode.classList.contains('d-none')).toBe(false);
    expect(editMode.classList.contains('d-none')).toBe(true);

    // Click edit button
    editButton.click();

    // After click, display mode should be hidden, edit mode visible
    expect(displayMode.classList.contains('d-none')).toBe(true);
    expect(editMode.classList.contains('d-none')).toBe(false);
  });

  it('should cancel edit mode when cancel button is clicked', () => {
    const editButton = document.querySelector('.edit-card-btn');
    const cancelButton = document.querySelector('.cancel-edit-btn');
    const displayMode = document.querySelector('.display-mode');
    const editMode = document.querySelector('.edit-mode');

    // Enter edit mode
    editButton.click();
    expect(editMode.classList.contains('d-none')).toBe(false);

    // Click cancel button
    cancelButton.click();

    // Should return to display mode
    expect(displayMode.classList.contains('d-none')).toBe(false);
    expect(editMode.classList.contains('d-none')).toBe(true);
  });

  it('should collect form data correctly', () => {
    const form = document.querySelector('.equipment-form');
    const formData = new FormData(form);

    // Test the setNestedProperty method
    const data = {};
    for (let [key, value] of formData.entries()) {
      manager.setNestedProperty(data, key, value);
    }

    expect(data).toEqual({
      basic_info: {
        electrical_contractor: '東京電力',
        safety_management_company: ''
      }
    });
  });

  it('should make API call when save button is clicked', async () => {
    // Mock successful API response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        message: 'データを保存しました'
      })
    });

    // Mock window.location.reload
    const originalReload = window.location.reload;
    window.location.reload = vi.fn();

    const saveButton = document.querySelector('.save-card-btn');

    // Click save button
    saveButton.click();

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 0));

    // Verify API call was made
    expect(fetch).toHaveBeenCalledWith(
      '/facilities/123/lifeline-equipment/electrical',
      expect.objectContaining({
        method: 'PUT',
        headers: expect.objectContaining({
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'test-csrf-token',
          'Accept': 'application/json'
        }),
        body: expect.stringContaining('basic_info')
      })
    );

    // Restore original reload function
    window.location.reload = originalReload;
  });

  it('should show loading indicator during save', () => {
    // Mock pending API response
    fetch.mockImplementationOnce(() => new Promise(() => { })); // Never resolves

    const saveButton = document.querySelector('.save-card-btn');
    const loadingIndicator = document.querySelector('.loading-indicator');

    // Initially loading indicator should be hidden
    expect(loadingIndicator.classList.contains('d-none')).toBe(true);

    // Click save button
    saveButton.click();

    // Loading indicator should be visible
    expect(loadingIndicator.classList.contains('d-none')).toBe(false);
  });

  it('should handle validation errors', async () => {
    // Mock validation error response
    fetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({
        success: false,
        errors: {
          'basic_info.electrical_contractor': ['電気契約会社は必須です']
        }
      })
    });

    const form = document.querySelector('.equipment-form');
    const contractorInput = form.querySelector('[name="basic_info[electrical_contractor]"]');

    // Add invalid-feedback element
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    contractorInput.parentElement.appendChild(feedback);

    const saveButton = document.querySelector('.save-card-btn');

    // Click save button
    saveButton.click();

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 0));

    // Verify validation error is displayed
    expect(contractorInput.classList.contains('is-invalid')).toBe(true);
    expect(feedback.textContent).toBe('電気契約会社は必須です');
  });

  it('should clear validation errors when entering edit mode', () => {
    const form = document.querySelector('.equipment-form');
    const contractorInput = form.querySelector('[name="basic_info[electrical_contractor]"]');

    // Add validation error state
    contractorInput.classList.add('is-invalid');
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = 'エラーメッセージ';
    contractorInput.parentElement.appendChild(feedback);

    const cancelButton = document.querySelector('.cancel-edit-btn');

    // Click cancel button (which calls clearValidationErrors)
    cancelButton.click();

    // Verify validation errors are cleared
    expect(contractorInput.classList.contains('is-invalid')).toBe(false);
    expect(feedback.textContent).toBe('');
  });
});