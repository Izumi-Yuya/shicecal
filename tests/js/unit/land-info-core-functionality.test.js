/**
 * Unit Tests for Land Info Core Functionality
 * Tests section visibility logic, calculation functions, validation rules, and form submission payload
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LandInfoManager } from '../../../resources/js/modules/land-info/LandInfoManager.js';
import { SectionManager } from '../../../resources/js/modules/land-info/SectionManager.js';
import { Calculator } from '../../../resources/js/modules/land-info/Calculator.js';
import { FormValidator } from '../../../resources/js/modules/land-info/FormValidator.js';

// Mock DOM elements for testing
const mockCompleteDOM = () => {
    document.body.innerHTML = `
    <form id="landInfoForm" action="/facilities/1/land-info" method="POST">
      <input type="hidden" name="_token" value="test-token">
      <input type="hidden" name="_method" value="PUT">
      
      <select id="ownership_type" name="ownership_type">
        <option value="">選択してください</option>
        <option value="owned">自社</option>
        <option value="leased">賃借</option>
        <option value="owned_rental">自社（賃貸）</option>
      </select>
      
      <input type="number" id="parking_spaces" name="parking_spaces" />
      <input type="number" id="site_area_sqm" name="site_area_sqm" step="0.01" />
      <input type="number" id="site_area_tsubo" name="site_area_tsubo" step="0.01" />
      
      <!-- Owned Section -->
      <div id="owned_section" class="conditional-section d-none" aria-hidden="true">
        <input type="text" id="purchase_price" name="purchase_price" class="currency-input" />
        <input type="text" id="unit_price_display" readonly />
      </div>
      
      <!-- Leased Section -->
      <div id="leased_section" class="conditional-section d-none" aria-hidden="true">
        <input type="text" id="monthly_rent" name="monthly_rent" class="currency-input" />
        <input type="date" id="contract_start_date" name="contract_start_date" />
        <input type="date" id="contract_end_date" name="contract_end_date" />
        <input type="text" id="contract_period_display" readonly />
        <select id="auto_renewal" name="auto_renewal">
          <option value="">選択してください</option>
          <option value="yes">あり</option>
          <option value="no">なし</option>
        </select>
      </div>
      
      <!-- Management Section -->
      <div id="management_section" class="conditional-section d-none" aria-hidden="true">
        <input type="text" id="management_company_name" name="management_company_name" />
        <input type="text" id="management_company_postal_code" name="management_company_postal_code" />
        <input type="email" id="management_company_email" name="management_company_email" />
        <textarea id="management_company_notes" name="management_company_notes"></textarea>
      </div>
      
      <!-- Owner Section -->
      <div id="owner_section" class="conditional-section d-none" aria-hidden="true">
        <input type="text" id="owner_name" name="owner_name" />
        <input type="text" id="owner_postal_code" name="owner_postal_code" />
        <input type="email" id="owner_email" name="owner_email" />
        <textarea id="owner_notes" name="owner_notes"></textarea>
      </div>
      
      <!-- File Section -->
      <div id="file_section" class="conditional-section d-none" aria-hidden="true">
        <input type="file" id="land_documents" name="land_documents[]" multiple />
      </div>
      
      <button type="submit">保存</button>
    </form>
  `;
};

describe('Section Visibility Logic Tests', () => {
    let sectionManager;

    beforeEach(() => {
        mockCompleteDOM();
        sectionManager = new SectionManager();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('Owned ownership type', () => {
        it('should show only owned section for owned type', () => {
            const visibility = sectionManager.calculateVisibility('owned');

            expect(visibility.owned_section).toBe(true);
            expect(visibility.leased_section).toBe(false);
            expect(visibility.management_section).toBe(false);
            expect(visibility.owner_section).toBe(false);
            expect(visibility.file_section).toBe(false);
        });

        it('should update DOM correctly for owned type', () => {
            sectionManager.updateSectionVisibility('owned');

            const ownedSection = document.getElementById('owned_section');
            const leasedSection = document.getElementById('leased_section');
            const managementSection = document.getElementById('management_section');

            // Check that owned section is shown (collapse + show classes)
            expect(ownedSection.classList.contains('show')).toBe(true);
            expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
            expect(ownedSection.getAttribute('aria-expanded')).toBe('true');

            // Check that other sections are hidden
            expect(leasedSection.classList.contains('show')).toBe(false);
            expect(leasedSection.getAttribute('aria-hidden')).toBe('true');
            expect(managementSection.classList.contains('show')).toBe(false);
        });

        it('should disable inputs in hidden sections for owned type', () => {
            sectionManager.updateSectionVisibility('owned');

            const monthlyRent = document.getElementById('monthly_rent');
            const managementName = document.getElementById('management_company_name');
            const ownerName = document.getElementById('owner_name');

            expect(monthlyRent.disabled).toBe(true);
            expect(managementName.disabled).toBe(true);
            expect(ownerName.disabled).toBe(true);
        });
    });

    describe('Leased ownership type', () => {
        it('should show all leased-related sections for leased type', () => {
            const visibility = sectionManager.calculateVisibility('leased');

            expect(visibility.owned_section).toBe(false);
            expect(visibility.leased_section).toBe(true);
            expect(visibility.management_section).toBe(true);
            expect(visibility.owner_section).toBe(true);
            expect(visibility.file_section).toBe(true);
        });

        it('should update DOM correctly for leased type', () => {
            sectionManager.updateSectionVisibility('leased');

            const ownedSection = document.getElementById('owned_section');
            const leasedSection = document.getElementById('leased_section');
            const managementSection = document.getElementById('management_section');
            const ownerSection = document.getElementById('owner_section');

            // Check that owned section is hidden
            expect(ownedSection.classList.contains('show')).toBe(false);
            expect(ownedSection.getAttribute('aria-hidden')).toBe('true');

            // Check that leased sections are shown
            expect(leasedSection.classList.contains('show')).toBe(true);
            expect(leasedSection.getAttribute('aria-hidden')).toBe('false');
            expect(managementSection.classList.contains('show')).toBe(true);
            expect(ownerSection.classList.contains('show')).toBe(true);
        });

        it('should disable inputs in hidden sections for leased type', () => {
            sectionManager.updateSectionVisibility('leased');

            const purchasePrice = document.getElementById('purchase_price');
            const monthlyRent = document.getElementById('monthly_rent');
            const managementName = document.getElementById('management_company_name');

            expect(purchasePrice.disabled).toBe(true);
            expect(monthlyRent.disabled).toBe(false);
            expect(managementName.disabled).toBe(false);
        });
    });

    describe('Owned rental ownership type', () => {
        it('should show both owned and leased sections for owned_rental type', () => {
            const visibility = sectionManager.calculateVisibility('owned_rental');

            expect(visibility.owned_section).toBe(true);
            expect(visibility.leased_section).toBe(true);
            expect(visibility.management_section).toBe(false);
            expect(visibility.owner_section).toBe(false);
            expect(visibility.file_section).toBe(true);
        });
    });
});

describe('Calculation Functions Tests', () => {
    let calculator;

    beforeEach(() => {
        calculator = new Calculator();
    });

    describe('Unit price calculation', () => {
        it('should calculate unit price correctly with valid inputs', () => {
            const result = calculator.calculateUnitPrice(10000000, 100);

            expect(result).not.toBeNull();
            expect(result.unitPrice).toBe(100000);
            expect(result.formattedPrice).toBe('100,000');
        });

        it('should handle zero purchase price', () => {
            const result = calculator.calculateUnitPrice(0, 100);

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('0より大きい値');
        });

        it('should handle zero site area', () => {
            const result = calculator.calculateUnitPrice(10000000, 0);

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('0より大きい値');
        });

        it('should handle negative values', () => {
            const negativePrice = calculator.calculateUnitPrice(-1000000, 100);
            const negativeArea = calculator.calculateUnitPrice(1000000, -100);

            expect(negativePrice).not.toBeNull();
            expect(negativePrice.error).toBe(true);
            expect(negativeArea).not.toBeNull();
            expect(negativeArea.error).toBe(true);
        });

        it('should handle string inputs with currency symbols', () => {
            const result = calculator.calculateUnitPrice('10,000,000円', '100坪');

            expect(result).not.toBeNull();
            expect(result.unitPrice).toBe(100000);
        });

        it('should handle invalid string inputs', () => {
            const result = calculator.calculateUnitPrice('invalid', 'also invalid');

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('有効な数値');
        });

        it('should handle extremely large numbers safely', () => {
            const result = calculator.calculateUnitPrice(Number.MAX_SAFE_INTEGER + 1, 100);

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('大きすぎます');
        });

        it('should handle floating point precision correctly', () => {
            const result = calculator.calculateUnitPrice(1000000.33, 100.33);

            expect(result).not.toBeNull();
            expect(typeof result.unitPrice).toBe('number');
            expect(result.unitPrice).toBeGreaterThan(0);
        });
    });

    describe('Contract period calculation', () => {
        it('should calculate contract period correctly for valid date range', () => {
            const startDate = new Date('2024-01-01');
            const endDate = new Date('2025-01-01');

            const result = calculator.calculateContractPeriod(startDate, endDate);

            expect(result).not.toBeNull();
            expect(result.totalMonths).toBe(12);
            expect(result.periodText).toBe('1年');
        });

        it('should handle partial months correctly', () => {
            const startDate = new Date('2024-01-01');
            const endDate = new Date('2024-07-15');

            const result = calculator.calculateContractPeriod(startDate, endDate);

            expect(result).not.toBeNull();
            expect(result.totalMonths).toBe(6);
            expect(result.periodText).toBe('6ヶ月');
        });

        it('should handle invalid date ranges (end before start)', () => {
            const startDate = new Date('2024-12-31');
            const endDate = new Date('2024-01-01');

            const result = calculator.calculateContractPeriod(startDate, endDate);

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('契約終了日は契約開始日より後');
        });

        it('should handle same start and end dates', () => {
            const date = new Date('2024-01-01');

            const result = calculator.calculateContractPeriod(date, date);

            expect(result).not.toBeNull();
            expect(result.error).toBe(true);
            expect(result.errorMessage).toContain('契約終了日は契約開始日より後');
        });

        it('should handle invalid date objects', () => {
            const invalidDate = new Date('invalid');
            const validDate = new Date('2024-01-01');

            const result1 = calculator.calculateContractPeriod(invalidDate, validDate);
            const result2 = calculator.calculateContractPeriod(validDate, invalidDate);

            // Invalid dates should return results with NaN values
            expect(result1).not.toBeNull();
            expect(result2).not.toBeNull();
        });

        it('should handle null and undefined inputs', () => {
            const validDate = new Date('2024-01-01');

            const result1 = calculator.calculateContractPeriod(null, validDate);
            const result2 = calculator.calculateContractPeriod(validDate, null);
            const result3 = calculator.calculateContractPeriod(undefined, undefined);

            expect(result1).toBeNull();
            expect(result2).toBeNull();
            expect(result3).toBeNull();
        });

        it('should handle very long contract periods', () => {
            const startDate = new Date('2024-01-01');
            const endDate = new Date('2034-01-01'); // 10 years

            const result = calculator.calculateContractPeriod(startDate, endDate);

            expect(result).not.toBeNull();
            expect(result.totalMonths).toBe(120);
            expect(result.periodText).toBe('10年');
        });
    });
});

describe('Validation Rules Tests', () => {
    let validator;

    beforeEach(() => {
        mockCompleteDOM();
        validator = new FormValidator();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('Owned ownership type validation', () => {
        beforeEach(() => {
            document.getElementById('ownership_type').value = 'owned';
        });

        it('should require ownership type', () => {
            document.getElementById('ownership_type').value = '';

            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('所有形態'))).toBe(true);
        });

        it('should validate with minimal required fields for owned type', () => {
            // For owned type, purchase_price and site area are required
            document.getElementById('purchase_price').value = '10000000';
            document.getElementById('site_area_tsubo').value = '100';

            const result = validator.validateForm();

            expect(result.isValid).toBe(true);
            expect(result.errors).toHaveLength(0);
        });

        it('should validate purchase price when provided', () => {
            document.getElementById('purchase_price').value = 'invalid';

            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('購入金額') || error.includes('数値'))).toBe(true);
        });

        it('should validate site area when provided', () => {
            document.getElementById('site_area_tsubo').value = '-100';

            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('敷地面積') || error.includes('0以上'))).toBe(true);
        });

        it('should not validate hidden leased fields for owned type', () => {
            // Fill required owned fields first
            document.getElementById('purchase_price').value = '10000000';
            document.getElementById('site_area_tsubo').value = '100';

            // Fill invalid data in hidden leased fields
            document.getElementById('monthly_rent').value = 'invalid';
            document.getElementById('contract_start_date').value = 'invalid';

            const result = validator.validateForm();

            // Should still be valid because leased fields are hidden
            expect(result.isValid).toBe(true);
        });
    });

    describe('Leased ownership type validation', () => {
        beforeEach(() => {
            document.getElementById('ownership_type').value = 'leased';
        });

        it('should require monthly rent for leased type', () => {
            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('家賃'))).toBe(true);
        });

        it('should require contract dates for leased type', () => {
            document.getElementById('monthly_rent').value = '500000';

            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('契約開始日'))).toBe(true);
            expect(result.errors.some(error => error.includes('契約終了日'))).toBe(true);
        });

        it('should validate with all required leased fields', () => {
            document.getElementById('monthly_rent').value = '500000';
            document.getElementById('contract_start_date').value = '2024-01-01';
            document.getElementById('contract_end_date').value = '2025-12-31';

            const result = validator.validateForm();

            expect(result.isValid).toBe(true);
            expect(result.errors).toHaveLength(0);
        });

        it('should validate contract date range', () => {
            document.getElementById('monthly_rent').value = '500000';
            document.getElementById('contract_start_date').value = '2024-12-31';
            document.getElementById('contract_end_date').value = '2024-01-01';

            const result = validator.validateForm();

            expect(result.isValid).toBe(false);
            expect(result.errors.some(error => error.includes('契約終了日は契約開始日より後'))).toBe(true);
        });

        it('should not validate hidden owned fields for leased type', () => {
            // Fill required leased fields
            document.getElementById('monthly_rent').value = '500000';
            document.getElementById('contract_start_date').value = '2024-01-01';
            document.getElementById('contract_end_date').value = '2025-12-31';

            // Fill invalid data in hidden owned fields
            document.getElementById('purchase_price').value = 'invalid';

            const result = validator.validateForm();

            // Should still be valid because owned fields are hidden
            expect(result.isValid).toBe(true);
        });
    });
});

describe('Form Submission Payload Tests', () => {
    let sectionManager;

    beforeEach(() => {
        mockCompleteDOM();
        sectionManager = new SectionManager();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('should exclude disabled fields from form submission payload for owned type', () => {
    // Set up owned type
        document.getElementById('ownership_type').value = 'owned';

        // Fill data in both visible and hidden sections
        document.getElementById('purchase_price').value = '10000000';
        document.getElementById('monthly_rent').value = '500000'; // This should be disabled
        document.getElementById('management_company_name').value = 'Test Company'; // This should be disabled

        // Update section visibility (this should disable hidden fields)
        sectionManager.updateSectionVisibility('owned');

        // Get form data
        const form = document.getElementById('landInfoForm');
        const formData = new FormData(form);

        // Convert to object for easier testing
        const payload = {};
        for (const [key, value] of formData.entries()) {
            payload[key] = value;
        }

        // Should include visible field data
        expect(payload.ownership_type).toBe('owned');
        expect(payload.purchase_price).toBe('10000000');

        // Should not include disabled field data
        expect(payload.monthly_rent).toBeUndefined();
        expect(payload.management_company_name).toBeUndefined();
    });

    it('should exclude disabled fields from form submission payload for leased type', () => {
    // Set up leased type
        document.getElementById('ownership_type').value = 'leased';

        // Fill data in both visible and hidden sections
        document.getElementById('monthly_rent').value = '500000';
        document.getElementById('purchase_price').value = '10000000'; // This should be disabled

        // Update section visibility (this should disable hidden fields)
        sectionManager.updateSectionVisibility('leased');

        // Get form data
        const form = document.getElementById('landInfoForm');
        const formData = new FormData(form);

        // Convert to object for easier testing
        const payload = {};
        for (const [key, value] of formData.entries()) {
            payload[key] = value;
        }

        // Should include visible field data
        expect(payload.ownership_type).toBe('leased');
        expect(payload.monthly_rent).toBe('500000');

        // Should not include disabled field data
        expect(payload.purchase_price).toBeUndefined();
    });

    it('should verify disabled attribute is properly set on hidden section inputs', () => {
    // Set up owned type
        document.getElementById('ownership_type').value = 'owned';
        sectionManager.updateSectionVisibility('owned');

        // Check that leased section inputs are disabled
        const monthlyRent = document.getElementById('monthly_rent');
        const contractStartDate = document.getElementById('contract_start_date');
        const managementName = document.getElementById('management_company_name');
        const ownerName = document.getElementById('owner_name');

        expect(monthlyRent.disabled).toBe(true);
        expect(contractStartDate.disabled).toBe(true);
        expect(managementName.disabled).toBe(true);
        expect(ownerName.disabled).toBe(true);

        // Check that owned section inputs are not disabled
        const purchasePrice = document.getElementById('purchase_price');
        expect(purchasePrice.disabled).toBe(false);
    });

    it('should handle ownership type switching and maintain correct disabled states', () => {
        const monthlyRent = document.getElementById('monthly_rent');
        const purchasePrice = document.getElementById('purchase_price');

        // Start with owned
        document.getElementById('ownership_type').value = 'owned';
        sectionManager.updateSectionVisibility('owned');

        expect(purchasePrice.disabled).toBe(false);
        expect(monthlyRent.disabled).toBe(true);

        // Switch to leased
        document.getElementById('ownership_type').value = 'leased';
        sectionManager.updateSectionVisibility('leased');

        expect(purchasePrice.disabled).toBe(true);
        expect(monthlyRent.disabled).toBe(false);

        // Switch back to owned
        document.getElementById('ownership_type').value = 'owned';
        sectionManager.updateSectionVisibility('owned');

        expect(purchasePrice.disabled).toBe(false);
        expect(monthlyRent.disabled).toBe(true);
    });
});
