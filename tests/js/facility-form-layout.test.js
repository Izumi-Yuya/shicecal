/**
 * @vitest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { FacilityFormLayout, FacilityFormUtils } from '../../resources/js/modules/facility-form-layout.js';

describe('FacilityFormLayout', () => {
  let container;

  beforeEach(() => {
    // Setup DOM
    document.body.innerHTML = '';
    container = document.createElement('div');
    container.className = 'facility-edit-layout';
    document.body.appendChild(container);

    // Mock localStorage
    const localStorageMock = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    };
    global.localStorage = localStorageMock;
  });

  describe('initialization', () => {
    it('should initialize successfully with facility edit layout', () => {
      const form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);

      const layout = new FacilityFormLayout();
      expect(layout).toBeDefined();
      expect(layout.form).toBe(form);
    });

    it('should initialize with land info form as fallback', () => {
      const form = document.createElement('form');
      form.id = 'landInfoForm';
      container.appendChild(form);

      const layout = new FacilityFormLayout();
      expect(layout).toBeDefined();
      expect(layout.form).toBe(form);
    });

    it('should accept configuration options', () => {
      const options = {
        enableAutoSave: false,
        enableRealTimeValidation: false,
        enableMobileOptimization: false,
        enableAccessibility: false
      };

      const layout = new FacilityFormLayout(options);
      expect(layout.options.enableAutoSave).toBe(false);
      expect(layout.options.enableRealTimeValidation).toBe(false);
      expect(layout.options.enableMobileOptimization).toBe(false);
      expect(layout.options.enableAccessibility).toBe(false);
    });
  });

  describe('collapsible sections', () => {
    it('should initialize collapsible sections', () => {
      const section = document.createElement('div');
      section.setAttribute('data-collapsible', 'true');

      const header = document.createElement('div');
      header.className = 'section-header';

      const content = document.createElement('div');
      content.className = 'card-body';

      section.appendChild(header);
      section.appendChild(content);
      container.appendChild(section);

      const layout = new FacilityFormLayout();

      // Check that ARIA attributes are set
      expect(header.getAttribute('aria-expanded')).toBe('true');
      expect(header.getAttribute('aria-controls')).toBeTruthy();
    });

    it('should toggle section on click', () => {
      const section = document.createElement('div');
      section.setAttribute('data-collapsible', 'true');

      const header = document.createElement('div');
      header.className = 'section-header';

      const content = document.createElement('div');
      content.className = 'card-body';

      const collapseIcon = document.createElement('i');
      collapseIcon.className = 'collapse-icon fa-chevron-up';
      header.appendChild(collapseIcon);

      section.appendChild(header);
      section.appendChild(content);
      container.appendChild(section);

      const layout = new FacilityFormLayout();

      // Simulate click
      header.click();

      expect(content.classList.contains('collapse')).toBe(true);
      expect(header.getAttribute('aria-expanded')).toBe('false');
      expect(collapseIcon.classList.contains('fa-chevron-down')).toBe(true);
    });
  });

  describe('form validation', () => {
    let form, layout;

    beforeEach(() => {
      form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);
      layout = new FacilityFormLayout();
    });

    it('should validate required fields', () => {
      const input = document.createElement('input');
      input.setAttribute('required', '');
      input.value = '';
      form.appendChild(input);

      const isValid = layout.validateField(input);
      expect(isValid).toBe(false);
      expect(input.classList.contains('is-invalid')).toBe(true);
    });

    it('should validate email fields', () => {
      const input = document.createElement('input');
      input.type = 'email';
      input.value = 'invalid-email';
      form.appendChild(input);

      const isValid = layout.validateField(input);
      expect(isValid).toBe(false);
      expect(input.classList.contains('is-invalid')).toBe(true);
    });

    it('should validate phone number fields', () => {
      const input = document.createElement('input');
      input.name = 'phone';
      input.value = '03-1234-5678';
      form.appendChild(input);

      const isValid = layout.validateField(input);
      expect(isValid).toBe(true);
      expect(input.classList.contains('is-valid')).toBe(true);
    });

    it('should validate postal code fields', () => {
      const input = document.createElement('input');
      input.name = 'postal_code';
      input.value = '123-4567';
      form.appendChild(input);

      const isValid = layout.validateField(input);
      expect(isValid).toBe(true);
      expect(input.classList.contains('is-valid')).toBe(true);
    });
  });

  describe('currency formatting', () => {
    let layout;

    beforeEach(() => {
      const form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);
      layout = new FacilityFormLayout();
    });

    it('should format currency input', () => {
      const input = document.createElement('input');
      input.value = '1000000';

      layout.formatCurrency(input);
      expect(input.value).toBe('1,000,000');
    });

    it('should remove currency formatting', () => {
      const input = document.createElement('input');
      input.value = '1,000,000';

      layout.removeCurrencyFormat(input);
      expect(input.value).toBe('1000000');
    });
  });

  describe('number conversion', () => {
    let layout;

    beforeEach(() => {
      const form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);
      layout = new FacilityFormLayout();
    });

    it('should convert full-width numbers to half-width', () => {
      const input = document.createElement('input');
      input.value = '１２３４５';

      layout.convertToHalfWidth(input);
      expect(input.value).toBe('12345');
    });
  });

  describe('phone number formatting', () => {
    let layout;

    beforeEach(() => {
      const form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);
      layout = new FacilityFormLayout();
    });

    it('should format 10-digit phone numbers', () => {
      const result = layout.validateAndFormatPhoneNumber('0312345678');
      expect(result).toBe('03-1234-5678');
    });

    it('should format 11-digit phone numbers', () => {
      const result = layout.validateAndFormatPhoneNumber('09012345678');
      expect(result).toBe('090-1234-5678');
    });

    it('should return original for invalid format', () => {
      const result = layout.validateAndFormatPhoneNumber('123');
      expect(result).toBe('123');
    });
  });

  describe('postal code formatting', () => {
    let layout;

    beforeEach(() => {
      const form = document.createElement('form');
      form.className = 'facility-edit-form';
      container.appendChild(form);
      layout = new FacilityFormLayout();
    });

    it('should format 7-digit postal codes', () => {
      const result = layout.validateAndFormatPostalCode('1234567');
      expect(result).toBe('123-4567');
    });

    it('should return original for invalid format', () => {
      const result = layout.validateAndFormatPostalCode('123');
      expect(result).toBe('123');
    });
  });

  describe('auto-save functionality', () => {
    let form, layout;

    beforeEach(() => {
      form = document.createElement('form');
      form.className = 'facility-edit-form';
      form.id = 'test-form';
      container.appendChild(form);

      layout = new FacilityFormLayout({ enableAutoSave: true });
    });

    it('should save draft data', () => {
      const input = document.createElement('input');
      input.name = 'test_field';
      input.value = 'test value';
      form.appendChild(input);

      layout.saveDraft();

      expect(localStorage.setItem).toHaveBeenCalledWith(
        'test-form_draft',
        expect.stringContaining('test value')
      );
    });

    it('should load draft data', () => {
      const input = document.createElement('input');
      input.name = 'test_field';
      form.appendChild(input);

      localStorage.getItem.mockReturnValue('{"test_field":"loaded value"}');

      layout.loadDraft();
      expect(input.value).toBe('loaded value');
    });

    it('should clear draft data', () => {
      layout.clearDraft();
      expect(localStorage.removeItem).toHaveBeenCalledWith('test-form_draft');
    });
  });


});

describe('FacilityFormUtils', () => {
  describe('formatCurrency', () => {
    it('should format numbers with commas', () => {
      expect(FacilityFormUtils.formatCurrency(1000000)).toBe('1,000,000');
      expect(FacilityFormUtils.formatCurrency('1000000')).toBe('1,000,000');
      expect(FacilityFormUtils.formatCurrency(0)).toBe('');
    });
  });

  describe('removeCurrencyFormat', () => {
    it('should remove commas from formatted numbers', () => {
      expect(FacilityFormUtils.removeCurrencyFormat('1,000,000')).toBe('1000000');
      expect(FacilityFormUtils.removeCurrencyFormat(1000000)).toBe('1000000');
    });
  });

  describe('convertToHalfWidth', () => {
    it('should convert full-width numbers to half-width', () => {
      expect(FacilityFormUtils.convertToHalfWidth('１２３４５')).toBe('12345');
      expect(FacilityFormUtils.convertToHalfWidth('12345')).toBe('12345');
    });
  });

  describe('formatPhoneNumber', () => {
    it('should format phone numbers correctly', () => {
      expect(FacilityFormUtils.formatPhoneNumber('0312345678')).toBe('03-1234-5678');
      expect(FacilityFormUtils.formatPhoneNumber('09012345678')).toBe('090-1234-5678');
      expect(FacilityFormUtils.formatPhoneNumber('123')).toBe('123');
    });
  });

  describe('formatPostalCode', () => {
    it('should format postal codes correctly', () => {
      expect(FacilityFormUtils.formatPostalCode('1234567')).toBe('123-4567');
      expect(FacilityFormUtils.formatPostalCode('123')).toBe('123');
    });
  });

  describe('validation functions', () => {
    it('should validate email addresses', () => {
      expect(FacilityFormUtils.validateEmail('test@example.com')).toBe(true);
      expect(FacilityFormUtils.validateEmail('invalid-email')).toBe(false);
    });

    it('should validate URLs', () => {
      expect(FacilityFormUtils.validateUrl('https://example.com')).toBe(true);
      expect(FacilityFormUtils.validateUrl('invalid-url')).toBe(false);
    });

    it('should validate phone numbers', () => {
      expect(FacilityFormUtils.validatePhoneNumber('03-1234-5678')).toBe(true);
      expect(FacilityFormUtils.validatePhoneNumber('090-1234-5678')).toBe(true);
      expect(FacilityFormUtils.validatePhoneNumber('invalid')).toBe(false);
    });

    it('should validate postal codes', () => {
      expect(FacilityFormUtils.validatePostalCode('123-4567')).toBe(true);
      expect(FacilityFormUtils.validatePostalCode('invalid')).toBe(false);
    });
  });
});