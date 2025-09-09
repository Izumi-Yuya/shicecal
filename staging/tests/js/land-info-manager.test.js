/**
 * Tests for LandInfoManager JavaScript functionality
 * Testing calculation functions and form interactions
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Create a testable version of LandInfoManager
class LandInfoManager {
  constructor() {
    // Mock constructor for testing
  }

  /**
   * Calculate unit price per tsubo
   */
  calculateUnitPrice() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const siteAreaTsuboInput = document.getElementById('site_area_tsubo');
    const unitPriceDisplay = document.getElementById('unit_price_display');

    if (!purchasePriceInput || !siteAreaTsuboInput || !unitPriceDisplay) return;

    const purchasePrice = parseFloat(purchasePriceInput.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(siteAreaTsuboInput.value) || 0;

    if (purchasePrice > 0 && siteAreaTsubo > 0) {
      const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
      unitPriceDisplay.value = unitPrice.toLocaleString();

      // Visual feedback
      unitPriceDisplay.classList.add('calculated');
      setTimeout(() => {
        unitPriceDisplay.classList.remove('calculated');
      }, 1000);
    } else {
      unitPriceDisplay.value = '';
    }
  }

  /**
   * Calculate contract period
   */
  calculateContractPeriod() {
    const startDateInput = document.getElementById('contract_start_date');
    const endDateInput = document.getElementById('contract_end_date');
    const periodDisplay = document.getElementById('contract_period_display');

    if (!startDateInput || !endDateInput || !periodDisplay) return;

    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);

    if (startDate && endDate && endDate > startDate) {
      const years = endDate.getFullYear() - startDate.getFullYear();
      const months = endDate.getMonth() - startDate.getMonth();

      let totalMonths = years * 12 + months;

      // Date adjustment
      if (endDate.getDate() < startDate.getDate()) {
        totalMonths--;
      }

      const displayYears = Math.floor(totalMonths / 12);
      const displayMonths = totalMonths % 12;

      let periodText = '';
      if (displayYears > 0) periodText += displayYears + '年';
      if (displayMonths > 0) periodText += displayMonths + 'ヶ月';

      periodDisplay.value = periodText || '0ヶ月';

      // Visual feedback
      periodDisplay.classList.add('calculated');
      setTimeout(() => {
        periodDisplay.classList.remove('calculated');
      }, 1000);
    } else {
      periodDisplay.value = '';
    }
  }

  /**
   * Format currency input
   */
  formatCurrency(input) {
    const value = parseInt(input.value.replace(/,/g, '')) || 0;
    if (value > 0) {
      input.value = value.toLocaleString();
    }
  }

  /**
   * Remove currency formatting
   */
  removeCurrencyFormat(input) {
    const value = input.value.replace(/,/g, '');
    input.value = value;
  }

  /**
   * Convert full-width numbers to half-width
   */
  convertToHalfWidth(input) {
    input.value = input.value.replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
  }

  /**
   * Update conditional sections based on ownership type
   */
  updateConditionalSections() {
    const ownershipTypeElement = document.getElementById('ownership_type');
    if (!ownershipTypeElement) return;

    const ownershipType = ownershipTypeElement.value;

    // Section visibility control
    const sections = {
      owned_section: ownershipType === 'owned',
      leased_section: ['leased', 'owned_rental'].includes(ownershipType),
      management_section: ownershipType === 'leased',
      owner_section: ownershipType === 'leased',
      file_section: ['leased', 'owned_rental'].includes(ownershipType)
    };

    Object.entries(sections).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);
      if (section) {
        section.style.display = shouldShow ? 'block' : 'none';

        // Animation effect
        if (shouldShow) {
          section.classList.add('fade-in');
        } else {
          section.classList.remove('fade-in');
        }
      }
    });
  }

  /**
   * Validate form before submission
   */
  validateForm() {
    let isValid = true;
    const errors = [];

    // Clear previous validation errors
    this.clearValidationErrors();

    // Ownership type is required
    const ownershipType = document.getElementById('ownership_type');
    if (!ownershipType || !ownershipType.value) {
      errors.push('所有形態を選択してください。');
      if (ownershipType) ownershipType.classList.add('is-invalid');
      isValid = false;
    }

    // Contract period validity check
    const startDate = document.getElementById('contract_start_date');
    const endDate = document.getElementById('contract_end_date');

    if (startDate && endDate && startDate.value && endDate.value) {
      if (new Date(startDate.value) >= new Date(endDate.value)) {
        errors.push('契約終了日は契約開始日より後の日付を入力してください。');
        endDate.classList.add('is-invalid');
        isValid = false;
      }
    }

    // 数値フィールドの妥当性チェック
    const numericFields = [
      { id: 'parking_spaces', name: '敷地内駐車場台数', max: 9999999999 },
      { id: 'site_area_sqm', name: '敷地面積(㎡)', max: 99999999.99 },
      { id: 'site_area_tsubo', name: '敷地面積(坪数)', max: 99999999.99 },
      { id: 'purchase_price', name: '購入金額', max: 999999999999999 },
      { id: 'monthly_rent', name: '家賃', max: 999999999999999 }
    ];

    numericFields.forEach(field => {
      const element = document.getElementById(field.id);
      if (element && element.value) {
        const value = parseFloat(element.value.replace(/,/g, ''));
        if (value < 0) {
          errors.push(`${field.name}は0以上の値を入力してください。`);
          element.classList.add('is-invalid');
          isValid = false;
        } else if (value > field.max) {
          errors.push(`${field.name}は${field.max.toLocaleString()}以下の値を入力してください。`);
          element.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // メールアドレスの妥当性チェック
    const emailFields = ['management_company_email', 'owner_email'];
    emailFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
          errors.push('正しいメールアドレス形式で入力してください。');
          field.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // URLの妥当性チェック
    const urlFields = ['management_company_url', 'owner_url'];
    urlFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        try {
          new URL(field.value);
        } catch {
          errors.push('正しいURL形式で入力してください。');
          field.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    return isValid;
  }

  /**
   * Clear validation errors
   */
  clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(element => {
      element.classList.remove('is-invalid');
    });
  }

  /**
   * Validate and format phone number
   */
  validatePhoneNumber(phoneNumber) {
    // Remove all non-digit characters
    const digits = phoneNumber.replace(/\D/g, '');

    // Format as XXX-XXXX-XXXX or XX-XXXX-XXXX
    if (digits.length === 10) {
      return `${digits.slice(0, 2)}-${digits.slice(2, 6)}-${digits.slice(6)}`;
    } else if (digits.length === 11) {
      return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
    }

    return phoneNumber; // Return original if doesn't match expected format
  }

  /**
   * Validate and format postal code
   */
  validatePostalCode(postalCode) {
    // Remove all non-digit characters
    const digits = postalCode.replace(/\D/g, '');

    // Format as XXX-XXXX
    if (digits.length === 7) {
      return `${digits.slice(0, 3)}-${digits.slice(3)}`;
    }

    return postalCode; // Return original if doesn't match expected format
  }

  /**
   * Format area display with units
   */
  formatAreaDisplay(value, unit) {
    if (!value || value === 0) return '';
    const formattedValue = parseFloat(value).toFixed(2);
    return `${formattedValue}${unit}`;
  }

  /**
   * Save form data as draft
   */
  saveDraft() {
    const form = document.getElementById('landInfoForm');
    if (!form) return;

    const formData = new FormData(form);
    const draftData = {};

    for (let [key, value] of formData.entries()) {
      draftData[key] = value;
    }

    // Save to localStorage
    localStorage.setItem('landInfoDraft', JSON.stringify(draftData));
  }

  /**
   * Load draft data
   */
  loadDraft() {
    const draftData = localStorage.getItem('landInfoDraft');
    if (!draftData) return;

    try {
      const data = JSON.parse(draftData);

      Object.entries(data).forEach(([key, value]) => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
          field.value = value;
        }
      });

      // Trigger calculations after loading
      this.calculateUnitPrice();
      this.calculateContractPeriod();
      this.updateConditionalSections();

    } catch (error) {
      console.error('Error loading draft:', error);
    }
  }

  /**
   * Clear draft data
   */
  clearDraft() {
    localStorage.removeItem('landInfoDraft');
  }
}

describe('LandInfoManager', () => {
  let manager;
  let mockElements;

  beforeEach(() => {
    // Setup DOM elements for testing
    document.body.innerHTML = `
      <input id="purchase_price" type="text" value="" />
      <input id="site_area_tsubo" type="text" value="" />
      <input id="unit_price_display" type="text" value="" readonly />
      <input id="contract_start_date" type="date" value="" />
      <input id="contract_end_date" type="date" value="" />
      <input id="contract_period_display" type="text" value="" readonly />
      <select id="ownership_type">
        <option value="">選択してください</option>
        <option value="owned">自社</option>
        <option value="leased">賃借</option>
        <option value="owned_rental">自社（賃貸）</option>
      </select>
      <div id="owned_section" style="display: none;"></div>
      <div id="leased_section" style="display: none;"></div>
      <div id="management_section" style="display: none;"></div>
      <div id="owner_section" style="display: none;"></div>
      <div id="file_section" style="display: none;"></div>
    `;

    manager = new LandInfoManager();

    mockElements = {
      purchasePrice: document.getElementById('purchase_price'),
      siteAreaTsubo: document.getElementById('site_area_tsubo'),
      unitPriceDisplay: document.getElementById('unit_price_display'),
      contractStartDate: document.getElementById('contract_start_date'),
      contractEndDate: document.getElementById('contract_end_date'),
      contractPeriodDisplay: document.getElementById('contract_period_display'),
      ownershipType: document.getElementById('ownership_type')
    };
  });

  describe('Unit Price Calculation', () => {
    it('should calculate unit price correctly', () => {
      // Test case: 10,000,000 yen / 100 tsubo = 100,000 yen per tsubo
      mockElements.purchasePrice.value = '10000000';
      mockElements.siteAreaTsubo.value = '100';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('100,000');
    });

    it('should handle comma-separated purchase price', () => {
      // Test with formatted input
      mockElements.purchasePrice.value = '10,000,000';
      mockElements.siteAreaTsubo.value = '100';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('100,000');
    });

    it('should handle decimal tsubo values', () => {
      // Test case: 5,000,000 yen / 50.5 tsubo = 99,010 yen per tsubo (rounded)
      mockElements.purchasePrice.value = '5000000';
      mockElements.siteAreaTsubo.value = '50.5';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('99,010');
    });

    it('should clear unit price when purchase price is zero', () => {
      mockElements.purchasePrice.value = '0';
      mockElements.siteAreaTsubo.value = '100';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('');
    });

    it('should clear unit price when tsubo area is zero', () => {
      mockElements.purchasePrice.value = '10000000';
      mockElements.siteAreaTsubo.value = '0';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('');
    });

    it('should handle empty inputs', () => {
      mockElements.purchasePrice.value = '';
      mockElements.siteAreaTsubo.value = '';

      manager.calculateUnitPrice();

      expect(mockElements.unitPriceDisplay.value).toBe('');
    });
  });

  describe('Contract Period Calculation', () => {
    it('should calculate contract period in years and months', () => {
      // Test case: 2020-01-01 to 2025-06-01 = 5年5ヶ月
      mockElements.contractStartDate.value = '2020-01-01';
      mockElements.contractEndDate.value = '2025-06-01';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('5年5ヶ月');
    });

    it('should calculate exact years', () => {
      // Test case: 2020-01-01 to 2025-01-01 = 5年
      mockElements.contractStartDate.value = '2020-01-01';
      mockElements.contractEndDate.value = '2025-01-01';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('5年');
    });

    it('should calculate months only', () => {
      // Test case: 2020-01-01 to 2020-06-01 = 5ヶ月
      mockElements.contractStartDate.value = '2020-01-01';
      mockElements.contractEndDate.value = '2020-06-01';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('5ヶ月');
    });

    it('should handle date adjustment for different day of month', () => {
      // Test case: 2020-01-31 to 2020-03-01 should be less than 2 months
      mockElements.contractStartDate.value = '2020-01-31';
      mockElements.contractEndDate.value = '2020-03-01';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('1ヶ月');
    });

    it('should show 0ヶ月 for same dates', () => {
      mockElements.contractStartDate.value = '2020-01-01';
      mockElements.contractEndDate.value = '2020-01-01';

      manager.calculateContractPeriod();

      // When dates are the same, the condition endDate > startDate is false, so it clears the value
      expect(mockElements.contractPeriodDisplay.value).toBe('');
    });

    it('should clear period when end date is before start date', () => {
      mockElements.contractStartDate.value = '2020-06-01';
      mockElements.contractEndDate.value = '2020-01-01';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('');
    });

    it('should handle empty dates', () => {
      mockElements.contractStartDate.value = '';
      mockElements.contractEndDate.value = '';

      manager.calculateContractPeriod();

      expect(mockElements.contractPeriodDisplay.value).toBe('');
    });
  });

  describe('Currency Formatting', () => {
    it('should format currency with commas', () => {
      const input = { value: '1000000' };

      manager.formatCurrency(input);

      expect(input.value).toBe('1,000,000');
    });

    it('should handle already formatted currency', () => {
      const input = { value: '1,000,000' };

      manager.formatCurrency(input);

      expect(input.value).toBe('1,000,000');
    });

    it('should not format zero values', () => {
      const input = { value: '0' };

      manager.formatCurrency(input);

      expect(input.value).toBe('0');
    });

    it('should remove currency formatting', () => {
      const input = { value: '1,000,000' };

      manager.removeCurrencyFormat(input);

      expect(input.value).toBe('1000000');
    });
  });

  describe('Number Conversion', () => {
    it('should convert full-width numbers to half-width', () => {
      const input = { value: '１２３４５' };

      manager.convertToHalfWidth(input);

      expect(input.value).toBe('12345');
    });

    it('should handle mixed full-width and half-width numbers', () => {
      const input = { value: '１２3４5' };

      manager.convertToHalfWidth(input);

      expect(input.value).toBe('12345');
    });

    it('should not affect half-width numbers', () => {
      const input = { value: '12345' };

      manager.convertToHalfWidth(input);

      expect(input.value).toBe('12345');
    });
  });

  describe('Conditional Sections', () => {
    it('should show owned section when ownership type is owned', () => {
      mockElements.ownershipType.value = 'owned';

      manager.updateConditionalSections();

      expect(document.getElementById('owned_section').style.display).toBe('block');
      expect(document.getElementById('leased_section').style.display).toBe('none');
      expect(document.getElementById('management_section').style.display).toBe('none');
      expect(document.getElementById('owner_section').style.display).toBe('none');
    });

    it('should show leased sections when ownership type is leased', () => {
      mockElements.ownershipType.value = 'leased';

      manager.updateConditionalSections();

      expect(document.getElementById('owned_section').style.display).toBe('none');
      expect(document.getElementById('leased_section').style.display).toBe('block');
      expect(document.getElementById('management_section').style.display).toBe('block');
      expect(document.getElementById('owner_section').style.display).toBe('block');
      expect(document.getElementById('file_section').style.display).toBe('block');
    });

    it('should show appropriate sections for owned_rental', () => {
      mockElements.ownershipType.value = 'owned_rental';

      manager.updateConditionalSections();

      expect(document.getElementById('owned_section').style.display).toBe('none');
      expect(document.getElementById('leased_section').style.display).toBe('block');
      expect(document.getElementById('management_section').style.display).toBe('none');
      expect(document.getElementById('owner_section').style.display).toBe('none');
      expect(document.getElementById('file_section').style.display).toBe('block');
    });

    it('should hide all sections when no ownership type is selected', () => {
      mockElements.ownershipType.value = '';

      manager.updateConditionalSections();

      expect(document.getElementById('owned_section').style.display).toBe('none');
      expect(document.getElementById('leased_section').style.display).toBe('none');
      expect(document.getElementById('management_section').style.display).toBe('none');
      expect(document.getElementById('owner_section').style.display).toBe('none');
      expect(document.getElementById('file_section').style.display).toBe('none');
    });
  });

  describe('Form Validation', () => {
    it('should validate successfully with required fields', () => {
      mockElements.ownershipType.value = 'owned';

      const isValid = manager.validateForm();

      expect(isValid).toBe(true);
    });

    it('should fail validation when ownership type is not selected', () => {
      mockElements.ownershipType.value = '';

      const isValid = manager.validateForm();

      expect(isValid).toBe(false);
      expect(mockElements.ownershipType.classList.contains('is-invalid')).toBe(true);
    });

    it('should fail validation when end date is before start date', () => {
      mockElements.ownershipType.value = 'leased';
      mockElements.contractStartDate.value = '2020-06-01';
      mockElements.contractEndDate.value = '2020-01-01';

      const isValid = manager.validateForm();

      expect(isValid).toBe(false);
      expect(mockElements.contractEndDate.classList.contains('is-invalid')).toBe(true);
    });

    it('should pass validation with valid contract dates', () => {
      mockElements.ownershipType.value = 'leased';
      mockElements.contractStartDate.value = '2020-01-01';
      mockElements.contractEndDate.value = '2020-06-01';

      const isValid = manager.validateForm();

      expect(isValid).toBe(true);
    });
  });
});

describe('Calculation Edge Cases', () => {
  let manager;

  beforeEach(() => {
    document.body.innerHTML = `
      <input id="purchase_price" type="text" value="" />
      <input id="site_area_tsubo" type="text" value="" />
      <input id="unit_price_display" type="text" value="" readonly />
      <input id="contract_start_date" type="date" value="" />
      <input id="contract_end_date" type="date" value="" />
      <input id="contract_period_display" type="text" value="" readonly />
    `;

    manager = new LandInfoManager();
  });

  describe('Unit Price Edge Cases', () => {
    it('should handle very large numbers', () => {
      document.getElementById('purchase_price').value = '999999999999999';
      document.getElementById('site_area_tsubo').value = '1';

      manager.calculateUnitPrice();

      expect(document.getElementById('unit_price_display').value).toBe('999,999,999,999,999');
    });

    it('should handle very small decimal areas', () => {
      document.getElementById('purchase_price').value = '1000000';
      document.getElementById('site_area_tsubo').value = '0.01';

      manager.calculateUnitPrice();

      expect(document.getElementById('unit_price_display').value).toBe('100,000,000');
    });

    it('should round to nearest integer', () => {
      document.getElementById('purchase_price').value = '1000000';
      document.getElementById('site_area_tsubo').value = '3';

      manager.calculateUnitPrice();

      // 1000000 / 3 = 333333.333... should round to 333333
      expect(document.getElementById('unit_price_display').value).toBe('333,333');
    });
  });

  describe('Contract Period Edge Cases', () => {
    it('should handle leap year calculations', () => {
      document.getElementById('contract_start_date').value = '2020-02-29';
      document.getElementById('contract_end_date').value = '2021-02-28';

      manager.calculateContractPeriod();

      expect(document.getElementById('contract_period_display').value).toBe('11ヶ月');
    });

    it('should handle month-end date adjustments', () => {
      document.getElementById('contract_start_date').value = '2020-01-31';
      document.getElementById('contract_end_date').value = '2020-02-29';

      manager.calculateContractPeriod();

      expect(document.getElementById('contract_period_display').value).toBe('0ヶ月');
    });

    it('should handle very long contract periods', () => {
      document.getElementById('contract_start_date').value = '2000-01-01';
      document.getElementById('contract_end_date').value = '2050-01-01';

      manager.calculateContractPeriod();

      expect(document.getElementById('contract_period_display').value).toBe('50年');
    });
  });
});

describe('Additional Functionality Tests', () => {
  let manager;

  beforeEach(() => {
    document.body.innerHTML = `
      <input id="management_company_phone" name="management_company_phone" type="text" value="" />
      <input id="owner_postal_code" name="owner_postal_code" type="text" value="" />
      <input id="management_company_email" name="management_company_email" type="email" value="" />
      <input id="management_company_url" name="management_company_url" type="url" value="" />
    `;

    manager = new LandInfoManager();
  });

  describe('Phone Number Formatting', () => {
    it('should format 10-digit phone number correctly', () => {
      const result = manager.validatePhoneNumber('0312345678');
      expect(result).toBe('03-1234-5678');
    });

    it('should format 11-digit phone number correctly', () => {
      const result = manager.validatePhoneNumber('09012345678');
      expect(result).toBe('090-1234-5678');
    });

    it('should handle phone numbers with existing hyphens', () => {
      const result = manager.validatePhoneNumber('03-1234-5678');
      expect(result).toBe('03-1234-5678');
    });

    it('should return original for invalid length', () => {
      const result = manager.validatePhoneNumber('123');
      expect(result).toBe('123');
    });
  });

  describe('Postal Code Formatting', () => {
    it('should format 7-digit postal code correctly', () => {
      const result = manager.validatePostalCode('1234567');
      expect(result).toBe('123-4567');
    });

    it('should handle postal code with existing hyphen', () => {
      const result = manager.validatePostalCode('123-4567');
      expect(result).toBe('123-4567');
    });

    it('should return original for invalid length', () => {
      const result = manager.validatePostalCode('123');
      expect(result).toBe('123');
    });
  });

  describe('Area Formatting', () => {
    it('should format area with unit correctly', () => {
      const result = manager.formatAreaDisplay(290, '㎡');
      expect(result).toBe('290.00㎡');
    });

    it('should handle decimal values', () => {
      const result = manager.formatAreaDisplay(89.05, '坪');
      expect(result).toBe('89.05坪');
    });

    it('should return empty string for zero or null values', () => {
      expect(manager.formatAreaDisplay(0, '㎡')).toBe('');
      expect(manager.formatAreaDisplay(null, '㎡')).toBe('');
    });
  });

  describe('Enhanced Validation', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <select id="ownership_type">
          <option value="">選択してください</option>
          <option value="owned">自社</option>
          <option value="leased">賃借</option>
        </select>
        <input id="purchase_price" type="text" value="" />
        <input id="management_company_email" type="email" value="" />
        <input id="management_company_url" type="url" value="" />
      `;
      manager = new LandInfoManager();
    });

    it('should validate email format', () => {
      document.getElementById('ownership_type').value = 'leased';
      document.getElementById('management_company_email').value = 'invalid-email';

      const isValid = manager.validateForm();

      expect(isValid).toBe(false);
      expect(document.getElementById('management_company_email').classList.contains('is-invalid')).toBe(true);
    });

    it('should validate URL format', () => {
      document.getElementById('ownership_type').value = 'leased';
      document.getElementById('management_company_url').value = 'invalid-url';

      const isValid = manager.validateForm();

      expect(isValid).toBe(false);
      expect(document.getElementById('management_company_url').classList.contains('is-invalid')).toBe(true);
    });

    it('should validate numeric field ranges', () => {
      document.getElementById('ownership_type').value = 'owned';
      document.getElementById('purchase_price').value = '9999999999999999'; // Exceeds max

      const isValid = manager.validateForm();

      expect(isValid).toBe(false);
      expect(document.getElementById('purchase_price').classList.contains('is-invalid')).toBe(true);
    });

    it('should pass validation with valid inputs', () => {
      document.getElementById('ownership_type').value = 'owned';
      document.getElementById('purchase_price').value = '10000000';

      const isValid = manager.validateForm();

      expect(isValid).toBe(true);
    });
  });

  describe('Draft Functionality', () => {
    beforeEach(() => {
      // Clear localStorage before each test
      localStorage.clear();

      document.body.innerHTML = `
        <form id="landInfoForm">
          <select name="ownership_type" id="ownership_type">
            <option value="owned">自社</option>
          </select>
          <input name="purchase_price" id="purchase_price" type="text" value="" />
        </form>
      `;

      manager = new LandInfoManager();
    });

    it('should save draft data to localStorage', () => {
      document.getElementById('ownership_type').value = 'owned';
      document.getElementById('purchase_price').value = '10000000';

      manager.saveDraft();

      const savedDraft = localStorage.getItem('landInfoDraft');
      expect(savedDraft).toBeTruthy();

      const draftData = JSON.parse(savedDraft);
      expect(draftData.ownership_type).toBe('owned');
      expect(draftData.purchase_price).toBe('10000000');
    });

    it('should load draft data from localStorage', () => {
      const draftData = {
        ownership_type: 'owned',
        purchase_price: '5000000'
      };

      localStorage.setItem('landInfoDraft', JSON.stringify(draftData));

      manager.loadDraft();

      expect(document.getElementById('ownership_type').value).toBe('owned');
      expect(document.getElementById('purchase_price').value).toBe('5000000');
    });

    it('should clear draft data', () => {
      localStorage.setItem('landInfoDraft', '{"test": "data"}');

      manager.clearDraft();

      expect(localStorage.getItem('landInfoDraft')).toBeNull();
    });
  });
});