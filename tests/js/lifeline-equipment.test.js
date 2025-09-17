/**
 * Lifeline Equipment JavaScript Module Tests
 * Tests for tab switching, card editing, saving, and error handling functionality
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LifelineEquipmentManager } from '../../resources/js/modules/lifeline-equipment.js';

// Mock DOM elements and global objects
const mockDOM = () => {
  // Mock document methods
  global.document = {
    getElementById: vi.fn(),
    querySelector: vi.fn(),
    querySelectorAll: vi.fn(() => []),
    createElement: vi.fn(() => ({
      className: '',
      style: { cssText: '' },
      innerHTML: '',
      textContent: '',
      appendChild: vi.fn(),
      remove: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      getAttribute: vi.fn(),
      setAttribute: vi.fn(),
      classList: {
        add: vi.fn(),
        remove: vi.fn(),
        contains: vi.fn(() => false)
      }
    })),
    addEventListener: vi.fn(),
    body: {
      appendChild: vi.fn()
    }
  };

  // Mock window object
  global.window = {
    location: {
      pathname: '/facilities/123',
      reload: vi.fn()
    },
    facilityId: '123',
    bootstrap: {
      Tab: vi.fn(),
      Collapse: vi.fn(() => ({
        show: vi.fn(),
        hide: vi.fn()
      }))
    },
    confirm: vi.fn(() => true),
    ShiseCal: {
      modules: {
        comments: {}
      }
    }
  };

  // Mock fetch
  global.fetch = vi.fn();

  // Mock console methods
  global.console = {
    log: vi.fn(),
    error: vi.fn(),
    warn: vi.fn()
  };
};

describe('LifelineEquipmentManager', () => {
  let manager;

  beforeEach(() => {
    mockDOM();
    vi.clearAllMocks();
  });

  afterEach(() => {
    if (manager && typeof manager.destroy === 'function') {
      manager.destroy();
    }
  });

  describe('Initialization', () => {
    it('should initialize with facility ID from window object', () => {
      manager = new LifelineEquipmentManager();
      expect(manager.facilityId).toBe('123');
      expect(manager.currentCategory).toBe('electrical');
    });

    it('should extract facility ID from URL if not in window', () => {
      delete window.facilityId;
      manager = new LifelineEquipmentManager();
      expect(manager.facilityId).toBe('123');
    });

    it('should handle initialization errors gracefully', () => {
      document.querySelector.mockImplementation(() => {
        throw new Error('DOM error');
      });

      expect(() => {
        manager = new LifelineEquipmentManager();
      }).not.toThrow();
    });
  });

  describe('Tab Switching', () => {
    beforeEach(() => {
      // Mock lifeline equipment container
      document.getElementById.mockImplementation((id) => {
        if (id === 'lifeline-equipment') {
          return {
            addEventListener: vi.fn(),
            querySelector: vi.fn(),
            querySelectorAll: vi.fn(() => [])
          };
        }
        return null;
      });

      manager = new LifelineEquipmentManager();
    });

    it('should handle main lifeline tab activation', () => {
      const mockTab = { addEventListener: vi.fn() };
      document.getElementById.mockReturnValue(mockTab);

      manager.onLifelineTabShown();
      expect(console.log).toHaveBeenCalledWith('Lifeline equipment tab activated');
    });

    it('should handle sub-tab switching', () => {
      const mockEvent = {
        target: {
          getAttribute: vi.fn(() => '#electrical')
        }
      };

      manager.onSubTabShown(mockEvent);
      expect(manager.currentCategory).toBe('electrical');
    });
  });

  describe('Card Editing', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      manager = new LifelineEquipmentManager();
    });

    it('should enter edit mode correctly', () => {
      const mockCard = {
        querySelector: vi.fn((selector) => {
          if (selector === '.display-mode') {
            return { classList: { add: vi.fn() } };
          }
          if (selector === '.edit-mode') {
            return {
              classList: { remove: vi.fn() },
              querySelector: vi.fn(() => ({ focus: vi.fn() }))
            };
          }
          return null;
        })
      };

      document.querySelector.mockReturnValue(mockCard);

      manager.enterEditMode('basic', 'electrical_basic');

      expect(mockCard.querySelector).toHaveBeenCalledWith('.display-mode');
      expect(mockCard.querySelector).toHaveBeenCalledWith('.edit-mode');
    });

    it('should cancel edit mode correctly', () => {
      const mockForm = {
        reset: vi.fn()
      };

      const mockCard = {
        querySelector: vi.fn((selector) => {
          if (selector === '.display-mode') {
            return { classList: { remove: vi.fn() } };
          }
          if (selector === '.edit-mode') {
            return { classList: { add: vi.fn() } };
          }
          if (selector === '.equipment-form') {
            return mockForm;
          }
          return null;
        })
      };

      document.querySelector.mockReturnValue(mockCard);

      manager.cancelEdit('basic', 'electrical_basic');

      expect(mockForm.reset).toHaveBeenCalled();
    });
  });

  describe('Data Saving', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      // Mock CSRF token
      document.querySelector.mockImplementation((selector) => {
        if (selector === 'meta[name="csrf-token"]') {
          return { getAttribute: () => 'mock-csrf-token' };
        }
        if (selector.includes('[data-section="electrical_basic"]')) {
          return {
            querySelector: vi.fn(() => ({
              entries: vi.fn(function* () {
                yield ['basic_info[electrical_contractor]', 'Test Company'];
              })
            }))
          };
        }
        return null;
      });

      manager = new LifelineEquipmentManager();
    });

    it('should save card data successfully', async () => {
      const mockResponse = {
        ok: true,
        json: vi.fn().mockResolvedValue({ success: true })
      };
      fetch.mockResolvedValue(mockResponse);

      const mockCard = {
        querySelector: vi.fn(() => ({
          entries: vi.fn(function* () {
            yield ['basic_info[electrical_contractor]', 'Test Company'];
          })
        }))
      };

      document.querySelector.mockReturnValue(mockCard);

      await manager.saveCardData('basic', 'electrical_basic');

      expect(fetch).toHaveBeenCalledWith(
        '/facilities/123/lifeline-equipment/electrical',
        expect.objectContaining({
          method: 'PUT',
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'mock-csrf-token'
          })
        })
      );
    });

    it('should handle validation errors', async () => {
      const mockResponse = {
        ok: false,
        json: vi.fn().mockResolvedValue({
          success: false,
          errors: {
            'basic_info.electrical_contractor': ['This field is required']
          }
        })
      };
      fetch.mockResolvedValue(mockResponse);

      const mockCard = {
        querySelector: vi.fn(() => ({
          entries: vi.fn(function* () {
            yield ['basic_info[electrical_contractor]', ''];
          })
        }))
      };

      document.querySelector.mockReturnValue(mockCard);

      await manager.saveCardData('basic', 'electrical_basic');

      // Should not show success message
      expect(console.log).not.toHaveBeenCalledWith(expect.stringContaining('success'));
    });

    it('should handle network errors with retry', async () => {
      const networkError = new TypeError('Failed to fetch');
      fetch.mockRejectedValue(networkError);

      const mockCard = {
        querySelector: vi.fn(() => ({
          entries: vi.fn(function* () {
            yield ['basic_info[electrical_contractor]', 'Test'];
          })
        }))
      };

      document.querySelector.mockReturnValue(mockCard);

      await manager.saveCardData('basic', 'electrical_basic');

      expect(console.error).toHaveBeenCalledWith(
        'API Error in saveCardData:',
        networkError
      );
    });
  });

  describe('Comment System', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      manager = new LifelineEquipmentManager();
    });

    it('should toggle comment visibility', () => {
      const mockCommentSection = {
        classList: {
          contains: vi.fn(() => true),
          add: vi.fn(),
          remove: vi.fn()
        }
      };

      const mockToggleButton = {
        setAttribute: vi.fn(),
        classList: { remove: vi.fn() }
      };

      document.getElementById.mockReturnValue(mockCommentSection);
      document.querySelector.mockReturnValue(mockToggleButton);

      manager.toggleComments('electrical_basic');

      expect(mockCommentSection.classList.add).toHaveBeenCalledWith('d-none');
      expect(mockToggleButton.setAttribute).toHaveBeenCalledWith('aria-expanded', 'false');
    });

    it('should load comments successfully', async () => {
      const mockResponse = {
        ok: true,
        json: vi.fn().mockResolvedValue({
          comments: [
            {
              id: 1,
              content: 'Test comment',
              user_name: 'Test User',
              created_at: '2024-01-01T00:00:00Z',
              can_delete: true
            }
          ]
        })
      };
      fetch.mockResolvedValue(mockResponse);

      await manager.loadComments('electrical_basic');

      expect(fetch).toHaveBeenCalledWith(
        '/facilities/123/comments?section=electrical_basic',
        expect.objectContaining({
          headers: expect.objectContaining({
            'Accept': 'application/json'
          })
        })
      );
    });

    it('should submit comments successfully', async () => {
      const mockResponse = {
        ok: true,
        json: vi.fn().mockResolvedValue({ success: true })
      };
      fetch.mockResolvedValue(mockResponse);

      const mockInput = {
        value: '',
        disabled: false,
        focus: vi.fn()
      };
      const mockButton = { disabled: false };

      document.querySelector.mockImplementation((selector) => {
        if (selector.includes('comment-input')) return mockInput;
        if (selector.includes('comment-submit')) return mockButton;
        if (selector === 'meta[name="csrf-token"]') {
          return { getAttribute: () => 'mock-csrf-token' };
        }
        return null;
      });

      await manager.submitComment('electrical_basic', 'Test comment');

      expect(fetch).toHaveBeenCalledWith(
        '/facilities/123/comments',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify({
            section: 'electrical_basic',
            content: 'Test comment',
            facility_id: '123'
          })
        })
      );
    });
  });

  describe('Equipment List Management', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      manager = new LifelineEquipmentManager();
    });

    it('should add equipment item correctly', () => {
      const mockEquipmentList = {
        querySelectorAll: vi.fn(() => []),
        appendChild: vi.fn()
      };

      const mockNoEquipmentMessage = {
        style: { display: 'block' }
      };

      document.querySelector.mockImplementation((selector) => {
        if (selector.includes('equipment-list')) return mockEquipmentList;
        if (selector.includes('no-equipment-message')) return mockNoEquipmentMessage;
        return null;
      });

      manager.addEquipmentItem('cubicle');

      expect(mockEquipmentList.appendChild).toHaveBeenCalled();
      expect(mockNoEquipmentMessage.style.display).toBe('none');
    });

    it('should remove equipment item correctly', () => {
      const mockEquipmentItem = {
        style: {},
        remove: vi.fn(),
        closest: vi.fn(() => ({
          getAttribute: vi.fn(() => 'cubicle'),
          querySelectorAll: vi.fn(() => [])
        }))
      };

      manager.removeEquipmentItem(mockEquipmentItem);

      setTimeout(() => {
        expect(mockEquipmentItem.remove).toHaveBeenCalled();
      }, 350);
    });
  });

  describe('Utility Methods', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      manager = new LifelineEquipmentManager();
    });

    it('should escape HTML correctly', () => {
      const result = manager.escapeHtml('<script>alert("xss")</script>');
      expect(result).toBe('&lt;script&gt;alert("xss")&lt;/script&gt;');
    });

    it('should format dates correctly', () => {
      const result = manager.formatDate('2024-01-01T12:00:00Z');
      expect(result).toMatch(/2024/);
    });

    it('should handle nested property setting', () => {
      const obj = {};
      manager.setNestedProperty(obj, 'basic_info[electrical_contractor]', 'Test Company');
      expect(obj.basic_info.electrical_contractor).toBe('Test Company');
    });

    it('should handle array property setting', () => {
      const obj = {};
      manager.setNestedProperty(obj, 'cubicle_info[equipment_list][0][equipment_number]', 'CB-001');
      expect(obj.cubicle_info.equipment_list[0].equipment_number).toBe('CB-001');
    });
  });

  describe('Error Handling', () => {
    beforeEach(() => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [])
      });

      manager = new LifelineEquipmentManager();
    });

    it('should handle network errors', async () => {
      const networkError = { name: 'TypeError', message: 'Failed to fetch' };

      await manager.handleApiError(networkError, 'test operation');

      // Should log the error
      expect(console.error).toHaveBeenCalledWith('API Error in test operation:', networkError);
    });

    it('should handle 401 unauthorized errors', async () => {
      const authError = { status: 401 };

      await manager.handleApiError(authError, 'test operation');

      // Should schedule page reload
      expect(setTimeout).toHaveBeenCalled();
    });

    it('should handle 422 validation errors', async () => {
      const validationError = { status: 422 };

      await manager.handleApiError(validationError, 'test operation');

      expect(console.error).toHaveBeenCalledWith('API Error in test operation:', validationError);
    });
  });

  describe('Cleanup', () => {
    it('should destroy manager correctly', () => {
      document.getElementById.mockReturnValue({
        addEventListener: vi.fn(),
        querySelector: vi.fn(),
        querySelectorAll: vi.fn(() => [
          {
            removeEventListener: vi.fn(),
            addEventListener: vi.fn()
          }
        ])
      });

      manager = new LifelineEquipmentManager();

      expect(() => manager.destroy()).not.toThrow();
      expect(manager.isInitialized).toBe(false);
    });
  });
});