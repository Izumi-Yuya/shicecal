/**
 * Unit tests for LandInfoManager (Modular Version)
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LandInfoManager } from '../../resources/js/modules/land-info/LandInfoManager.js';
import { FormValidator } from '../../resources/js/modules/land-info/FormValidator.js';
import { Calculator } from '../../resources/js/modules/land-info/Calculator.js';
import { SectionManager } from '../../resources/js/modules/land-info/SectionManager.js';

// Mock DOM elements
const mockDOM = () => {
  document.body.innerHTML = `
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
    
    <input type="number" id="purchase_price" value="" />
    <input type="number" id="site_area_tsubo" value="" />
    <input type="text" id="unit_price_display" value="" />
    
    <input type="date" id="contract_start_date" value="" />
    <input type="date" id="contract_end_date" value="" />
    <input type="text" id="contract_period_display" value="" />
  `;
};

describe('LandInfoManager', () => {
  let manager;

  beforeEach(() => {
    mockDOM();
    // Mock performance.now for consistent testing
    vi.spyOn(performance, 'now').mockReturnValue(1000);
    // Mock requestAnimationFrame
    global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

    manager = new LandInfoManager();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  describe('Constants', () => {
    it('should have correct ownership type constants', () => {
      expect(LandInfoManager.OWNERSHIP_TYPES.OWNED).toBe('owned');
      expect(LandInfoManager.OWNERSHIP_TYPES.LEASED).toBe('leased');
      expect(LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL).toBe('owned_rental');
    });

    it('should have correct section visibility rules', () => {
      const rules = LandInfoManager.SECTION_VISIBILITY_RULES;

      expect(rules.owned_section).toContain('owned');
      expect(rules.owned_section).toContain('owned_rental');
      expect(rules.leased_section).toContain('leased');
      expect(rules.leased_section).toContain('owned_rental');
      expect(rules.management_section).toEqual(['leased']);
      expect(rules.owner_section).toEqual(['leased']);
    });
  });

  describe('getOwnershipTypeValue', () => {
    it('should return empty string when no ownership type is selected', () => {
      expect(manager.getOwnershipTypeValue()).toBe('');
    });

    it('should return selected value from select element', () => {
      const select = document.getElementById('ownership_type');
      select.value = 'owned';

      expect(manager.getOwnershipTypeValue()).toBe('owned');
    });

    it('should handle missing select element gracefully', () => {
      document.getElementById('ownership_type').remove();

      expect(manager.getOwnershipTypeValue()).toBe('');
    });
  });

  describe('calculateSectionVisibility', () => {
    it('should show correct sections for owned type', () => {
      const visibility = manager.calculateSectionVisibility('owned');

      expect(visibility.owned_section).toBe(true);
      expect(visibility.leased_section).toBe(false);
      expect(visibility.management_section).toBe(false);
      expect(visibility.owner_section).toBe(false);
      expect(visibility.file_section).toBe(false);
    });

    it('should show correct sections for leased type', () => {
      const visibility = manager.calculateSectionVisibility('leased');

      expect(visibility.owned_section).toBe(false);
      expect(visibility.leased_section).toBe(true);
      expect(visibility.management_section).toBe(true);
      expect(visibility.owner_section).toBe(true);
      expect(visibility.file_section).toBe(true);
    });

    it('should show correct sections for owned_rental type', () => {
      const visibility = manager.calculateSectionVisibility('owned_rental');

      expect(visibility.owned_section).toBe(true);
      expect(visibility.leased_section).toBe(true);
      expect(visibility.management_section).toBe(false);
      expect(visibility.owner_section).toBe(false);
      expect(visibility.file_section).toBe(true);
    });
  });

  describe('determineFieldsToClear', () => {
    it('should clear owned fields when ownership type is leased', () => {
      const fieldsToClear = manager.determineFieldsToClear('leased');

      expect(fieldsToClear).toContain('purchase_price');
      expect(fieldsToClear).toContain('unit_price_display');
    });

    it('should clear leased fields when ownership type is owned', () => {
      const fieldsToClear = manager.determineFieldsToClear('owned');

      expect(fieldsToClear).toContain('monthly_rent');
      expect(fieldsToClear).toContain('contract_start_date');
      expect(fieldsToClear).toContain('contract_end_date');
    });

    it('should clear management and owner fields when ownership type is not leased', () => {
      const fieldsToClear = manager.determineFieldsToClear('owned');

      expect(fieldsToClear).toContain('management_company_name');
      expect(fieldsToClear).toContain('owner_name');
    });

    it('should not clear owned fields for owned_rental type', () => {
      const fieldsToClear = manager.determineFieldsToClear('owned_rental');

      expect(fieldsToClear).not.toContain('purchase_price');
      expect(fieldsToClear).not.toContain('unit_price_display');
    });
  });

  describe('clearFieldsBatch', () => {
    it('should clear specified fields', () => {
      const purchasePrice = document.getElementById('purchase_price');
      const unitPrice = document.getElementById('unit_price_display');

      purchasePrice.value = '1000000';
      unitPrice.value = '50000';

      manager.clearFieldsBatch(['purchase_price', 'unit_price_display']);

      expect(purchasePrice.value).toBe('');
      expect(unitPrice.value).toBe('');
    });

    it('should remove validation classes when clearing fields', () => {
      const purchasePrice = document.getElementById('purchase_price');
      purchasePrice.value = '1000000';
      purchasePrice.classList.add('is-invalid', 'calculated');

      manager.clearFieldsBatch(['purchase_price']);

      expect(purchasePrice.classList.contains('is-invalid')).toBe(false);
      expect(purchasePrice.classList.contains('calculated')).toBe(false);
    });

    it('should trigger change events when clearing fields', () => {
      const purchasePrice = document.getElementById('purchase_price');
      purchasePrice.value = '1000000';

      const changeHandler = vi.fn();
      purchasePrice.addEventListener('change', changeHandler);

      manager.clearFieldsBatch(['purchase_price']);

      expect(changeHandler).toHaveBeenCalled();
    });
  });

  describe('convertToHalfWidth', () => {
    it('should convert full-width numbers to half-width', () => {
      const input = document.createElement('input');
      input.value = '１２３４５';

      manager.convertToHalfWidth(input);

      expect(input.value).toBe('12345');
    });

    it('should sanitize currency input', () => {
      const input = document.createElement('input');
      input.type = 'number';
      input.value = '1,000,000円';

      manager.convertToHalfWidth(input);

      expect(input.value).toBe('1,000,000');
    });

    it('should limit input length for security', () => {
      const input = document.createElement('input');
      input.value = 'a'.repeat(100);

      manager.convertToHalfWidth(input);

      expect(input.value.length).toBe(50);
    });

    it('should handle null input gracefully', () => {
      expect(() => manager.convertToHalfWidth(null)).not.toThrow();
    });
  });

  describe('calculateUnitPriceOptimized', () => {
    it('should calculate unit price correctly', async () => {
      const purchasePrice = document.getElementById('purchase_price');
      const siteAreaTsubo = document.getElementById('site_area_tsubo');
      const unitPriceDisplay = document.getElementById('unit_price_display');

      purchasePrice.value = '10000000';
      siteAreaTsubo.value = '100';

      const result = manager.calculateUnitPriceOptimized();

      // Wait for requestAnimationFrame
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(result.unitPrice).toBe(100000);
      expect(result.formattedPrice).toBe('100,000');
    });

    it('should return null for invalid inputs', () => {
      const result = manager.calculateUnitPriceOptimized();

      expect(result).toBeNull();
    });

    it('should handle zero values', () => {
      const purchasePrice = document.getElementById('purchase_price');
      const siteAreaTsubo = document.getElementById('site_area_tsubo');

      purchasePrice.value = '0';
      siteAreaTsubo.value = '100';

      const result = manager.calculateUnitPriceOptimized();

      expect(result).toBeNull();
    });
  });

  describe('calculateContractPeriodOptimized', () => {
    it('should calculate contract period correctly', async () => {
      const startDate = document.getElementById('contract_start_date');
      const endDate = document.getElementById('contract_end_date');

      startDate.value = '2024-01-01';
      endDate.value = '2025-01-01';

      const result = manager.calculateContractPeriodOptimized();

      // Wait for requestAnimationFrame
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(result.totalMonths).toBe(12);
      expect(result.periodText).toBe('1年');
    });

    it('should handle partial months correctly', async () => {
      const startDate = document.getElementById('contract_start_date');
      const endDate = document.getElementById('contract_end_date');

      startDate.value = '2024-01-01';
      endDate.value = '2024-07-15';

      const result = manager.calculateContractPeriodOptimized();

      await new Promise(resolve => setTimeout(resolve, 10));

      expect(result.totalMonths).toBe(6);
      expect(result.periodText).toBe('6ヶ月');
    });

    it('should return null for invalid dates', () => {
      const result = manager.calculateContractPeriodOptimized();

      expect(result).toBeNull();
    });
  });
});

describe('FormValidator', () => {
  let validator;

  beforeEach(() => {
    mockDOM();
    validator = new FormValidator();
  });

  describe('validateForm', () => {
    it('should return validation errors for invalid form', () => {
      const result = validator.validateForm();

      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('所有形態は必須項目です。');
    });

    it('should validate successfully with valid data', () => {
      document.getElementById('ownership_type').value = 'owned';

      const result = validator.validateForm();

      expect(result.isValid).toBe(true);
      expect(result.errors).toHaveLength(0);
    });
  });

  describe('validateField', () => {
    it('should validate required fields', () => {
      const errors = validator.validateField('ownership_type', { required: true });

      expect(errors).toContain('所有形態は必須項目です。');
    });

    it('should validate numeric fields', () => {
      const input = document.getElementById('purchase_price');
      input.value = 'invalid';

      const errors = validator.validateField('purchase_price', { type: 'currency', min: 0 });

      expect(errors.length).toBeGreaterThan(0);
    });
  });
});

describe('Calculator', () => {
  let calculator;

  beforeEach(() => {
    calculator = new Calculator();
  });

  describe('calculateUnitPrice', () => {
    it('should calculate unit price correctly', () => {
      const result = calculator.calculateUnitPrice(10000000, 100);

      expect(result.unitPrice).toBe(100000);
      expect(result.formattedPrice).toBe('100,000');
    });

    it('should return null for invalid inputs', () => {
      const result = calculator.calculateUnitPrice(0, 100);

      expect(result).toBeNull();
    });

    it('should use cache for repeated calculations', () => {
      const result1 = calculator.calculateUnitPrice(10000000, 100);
      const result2 = calculator.calculateUnitPrice(10000000, 100);

      expect(result1).toEqual(result2);
      expect(calculator.getCacheStats().size).toBe(1);
    });

    it('should handle string inputs with currency symbols', () => {
      const result = calculator.calculateUnitPrice('10,000,000円', '100坪');

      expect(result.unitPrice).toBe(100000);
    });

    it('should reject extremely large numbers for security', () => {
      const result = calculator.calculateUnitPrice(Number.MAX_SAFE_INTEGER + 1, 100);

      expect(result).toBeNull();
    });

    it('should handle negative numbers', () => {
      const result = calculator.calculateUnitPrice(-1000000, 100);

      expect(result).toBeNull();
    });

    it('should handle NaN inputs', () => {
      const result = calculator.calculateUnitPrice(NaN, 100);

      expect(result).toBeNull();
    });

    // Additional edge case tests
    it('should handle Infinity inputs', () => {
      const result = calculator.calculateUnitPrice(Infinity, 100);

      expect(result).toBeNull();
    });

    it('should handle very small decimal numbers', () => {
      const result = calculator.calculateUnitPrice(1000000, 0.001);

      expect(result).toBeNull(); // Should reject very small areas
    });

    it('should handle floating point precision issues', () => {
      const result = calculator.calculateUnitPrice(1000000.33, 100.33);

      expect(result.unitPrice).toBe(Math.round(1000000.33 / 100.33));
    });

    it('should handle cache overflow', () => {
      // Fill cache beyond limit
      for (let i = 0; i < 150; i++) {
        calculator.calculateUnitPrice(1000000 + i, 100 + i);
      }

      const stats = calculator.getCacheStats();
      expect(stats.size).toBeLessThanOrEqual(100); // Should respect cache limit
    });

    it('should handle concurrent calculations', async () => {
      const promises = [];
      for (let i = 0; i < 10; i++) {
        promises.push(Promise.resolve(calculator.calculateUnitPrice(1000000 + i, 100)));
      }

      const results = await Promise.all(promises);
      expect(results).toHaveLength(10);
      results.forEach(result => expect(result).toBeTruthy());
    });
  });

  describe('calculateContractPeriod', () => {
    it('should calculate contract period correctly', () => {
      const startDate = new Date('2024-01-01');
      const endDate = new Date('2025-01-01');

      const result = calculator.calculateContractPeriod(startDate, endDate);

      expect(result.totalMonths).toBe(12);
      expect(result.periodText).toBe('1年');
    });

    it('should handle partial months', () => {
      const startDate = new Date('2024-01-01');
      const endDate = new Date('2024-07-15');

      const result = calculator.calculateContractPeriod(startDate, endDate);

      expect(result.totalMonths).toBe(6);
      expect(result.periodText).toBe('6ヶ月');
    });
  });
});

describe('SectionManager', () => {
  let sectionManager;

  beforeEach(() => {
    mockDOM();
    sectionManager = new SectionManager();
  });

  describe('calculateVisibility', () => {
    it('should show correct sections for owned type', () => {
      const visibility = sectionManager.calculateVisibility('owned');

      expect(visibility.owned_section).toBe(true);
      expect(visibility.leased_section).toBe(false);
      expect(visibility.management_section).toBe(false);
    });

    it('should show correct sections for leased type', () => {
      const visibility = sectionManager.calculateVisibility('leased');

      expect(visibility.owned_section).toBe(false);
      expect(visibility.leased_section).toBe(true);
      expect(visibility.management_section).toBe(true);
    });
  });

  describe('determineFieldsToClear', () => {
    it('should clear appropriate fields when ownership type changes', () => {
      const fieldsToClear = sectionManager.determineFieldsToClear('owned');

      expect(fieldsToClear).toContain('monthly_rent');
      expect(fieldsToClear).toContain('management_company_name');
      expect(fieldsToClear).not.toContain('purchase_price');
    });
  });
});

describe('LandInfoManager Integration', () => {
  let manager;

  beforeEach(() => {
    mockDOM();
    vi.spyOn(performance, 'now').mockReturnValue(1000);
    global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

    manager = new LandInfoManager();
  });

  afterEach(() => {
    if (manager && typeof manager.destroy === 'function') {
      manager.destroy();
    }
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  describe('initialization', () => {
    it('should initialize all modules', () => {
      expect(manager.validator).toBeDefined();
      expect(manager.calculator).toBeDefined();
      expect(manager.sectionManager).toBeDefined();
      expect(manager.eventManager).toBeDefined();
      expect(manager.domCache).toBeDefined();
    });

    it('should setup event handlers', () => {
      expect(manager.eventManager.initialized).toBe(true);
    });
  });

  describe('ownership type changes', () => {
    it('should handle ownership type changes correctly', () => {
      const spy = vi.spyOn(manager.sectionManager, 'updateSectionVisibility');

      manager.handleOwnershipTypeChange('owned');

      expect(spy).toHaveBeenCalledWith('owned');
    });
  });

  describe('calculations', () => {
    it('should perform calculations when fields change', async () => {
      const purchasePrice = manager.domCache.get('purchase_price');
      const siteAreaTsubo = manager.domCache.get('site_area_tsubo');

      purchasePrice.value = '10000000';
      siteAreaTsubo.value = '100';

      manager.calculateUnitPrice();

      await new Promise(resolve => setTimeout(resolve, 10));

      const unitPriceDisplay = manager.domCache.get('unit_price_display');
      expect(unitPriceDisplay.value).toBe('100,000');
    });
  });

  describe('performance metrics', () => {
    it('should track performance metrics', () => {
      const metrics = manager.getMetrics();

      expect(metrics).toHaveProperty('startTime');
      expect(metrics).toHaveProperty('calculationCount');
      expect(metrics).toHaveProperty('cacheStats');
    });
  });
});