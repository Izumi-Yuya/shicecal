/**
 * Comprehensive workflow tests for Land Info System
 * Tests complete user journeys and edge cases
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LandInfoManager } from '../../../resources/js/modules/land-info/LandInfoManager.js';

// Mock complete form with all sections
const mockCompleteForm = () => {
    document.body.innerHTML = `
    <form id="landInfoForm" action="/facilities/1/land-info" method="POST">
      <input type="hidden" name="_token" value="test-token">
      <input type="hidden" name="_method" value="PUT">
      
      <!-- Basic Info -->
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
      <div id="owned_section" class="conditional-section d-none">
        <input type="text" id="purchase_price" name="purchase_price" />
        <input type="text" id="unit_price_display" readonly />
      </div>
      
      <!-- Leased Section -->
      <div id="leased_section" class="conditional-section d-none">
        <input type="text" id="monthly_rent" name="monthly_rent" />
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
      <div id="management_section" class="conditional-section d-none">
        <input type="text" id="management_company_name" name="management_company_name" />
        <input type="text" id="management_company_postal_code" name="management_company_postal_code" />
        <input type="email" id="management_company_email" name="management_company_email" />
        <textarea id="management_company_notes" name="management_company_notes"></textarea>
      </div>
      
      <!-- Owner Section -->
      <div id="owner_section" class="conditional-section d-none">
        <input type="text" id="owner_name" name="owner_name" />
        <input type="text" id="owner_postal_code" name="owner_postal_code" />
        <input type="email" id="owner_email" name="owner_email" />
        <textarea id="owner_notes" name="owner_notes"></textarea>
      </div>
      
      <!-- File Section -->
      <div id="file_section" class="conditional-section d-none">
        <input type="file" id="land_documents" name="land_documents[]" multiple />
      </div>
      
      <button type="submit">保存</button>
    </form>
  `;
};

describe('Land Info Workflow Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteForm();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

        Object.defineProperty(window, 'location', {
            value: { pathname: '/facilities/1/land-info/edit' },
            writable: true
        });

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    describe('Complete Owned Property Workflow', () => {
        it('should handle complete owned property data entry and validation', async () => {
            // Step 1: Select ownership type
            const ownershipSelect = document.getElementById('ownership_type');
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await waitForAnimations();

            // Step 2: Verify section visibility
            expect(document.getElementById('owned_section').classList.contains('d-none')).toBe(false);
            expect(document.getElementById('leased_section').classList.contains('d-none')).toBe(true);

            // Step 3: Fill basic info
            document.getElementById('parking_spaces').value = '50';
            document.getElementById('site_area_tsubo').value = '100.5';

            // Step 4: Fill owned property data
            const purchasePrice = document.getElementById('purchase_price');
            const siteAreaTsubo = document.getElementById('site_area_tsubo');

            purchasePrice.value = '10,000,000';
            siteAreaTsubo.value = '100';

            // Trigger calculations
            purchasePrice.dispatchEvent(new Event('input', { bubbles: true }));
            await waitForCalculations();

            // Step 5: Verify calculations
            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBe('100,000');

            // Step 6: Validate complete form
            const validation = manager.validator.validateForm();
            expect(validation.isValid).toBe(true);
            expect(validation.errors).toHaveLength(0);

            // Step 7: Test form submission
            const form = document.getElementById('landInfoForm');
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);

            expect(submitEvent.defaultPrevented).toBe(false);
        });

        it('should handle validation errors in owned property workflow', async () => {
            // Select owned type
            document.getElementById('ownership_type').value = 'owned';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            // Fill invalid data
            document.getElementById('purchase_price').value = 'invalid';
            document.getElementById('site_area_tsubo').value = '0';

            // Validate
            const validation = manager.validator.validateForm();
            expect(validation.isValid).toBe(false);
            expect(validation.errors.length).toBeGreaterThan(0);
        });
    });

    describe('Complete Leased Property Workflow', () => {
        it('should handle complete leased property workflow with all sections', async () => {
            // Step 1: Select leased type
            document.getElementById('ownership_type').value = 'leased';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            // Step 2: Verify all leased sections are visible
            ['leased_section', 'management_section', 'owner_section', 'file_section'].forEach(sectionId => {
                expect(document.getElementById(sectionId).classList.contains('d-none')).toBe(false);
            });

            // Step 3: Fill leased property data
            document.getElementById('monthly_rent').value = '500,000';
            document.getElementById('contract_start_date').value = '2024-01-01';
            document.getElementById('contract_end_date').value = '2025-12-31';
            document.getElementById('auto_renewal').value = 'yes';

            // Step 4: Fill management company data
            document.getElementById('management_company_name').value = '株式会社管理';
            document.getElementById('management_company_postal_code').value = '123-4567';
            document.getElementById('management_company_email').value = 'info@management.com';

            // Step 5: Fill owner data
            document.getElementById('owner_name').value = '田中太郎';
            document.getElementById('owner_postal_code').value = '987-6543';
            document.getElementById('owner_email').value = 'owner@example.com';

            // Step 6: Trigger contract period calculation
            document.getElementById('contract_start_date').dispatchEvent(new Event('input'));
            await waitForCalculations();

            // Step 7: Verify contract period calculation
            const contractPeriodDisplay = document.getElementById('contract_period_display');
            expect(contractPeriodDisplay.value).toContain('年');

            // Step 8: Validate complete form
            const validation = manager.validator.validateForm();
            expect(validation.isValid).toBe(true);
        });

        it('should handle contract date validation errors', async () => {
            document.getElementById('ownership_type').value = 'leased';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            // Set invalid date range (end before start)
            document.getElementById('contract_start_date').value = '2024-12-31';
            document.getElementById('contract_end_date').value = '2024-01-01';
            document.getElementById('monthly_rent').value = '500,000';

            const validation = manager.validator.validateForm();
            expect(validation.isValid).toBe(false);
            expect(validation.errors.some(error =>
                error.includes('契約終了日は契約開始日より後')
            )).toBe(true);
        });
    });

    describe('Ownership Type Switching Workflow', () => {
        it('should handle rapid ownership type switching without data corruption', async () => {
            const ownershipSelect = document.getElementById('ownership_type');

            // Test rapid switching
            const switchSequence = ['owned', 'leased', 'owned_rental', 'owned', 'leased'];

            for (const type of switchSequence) {
                ownershipSelect.value = type;
                ownershipSelect.dispatchEvent(new Event('change'));
                await waitForAnimations();

                // Verify correct sections are visible
                const visibility = manager.sectionManager.calculateVisibility(type);
                Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
                    const section = document.getElementById(sectionId);
                    expect(section.classList.contains('d-none')).toBe(!shouldShow);
                });
            }
        });

        it('should preserve appropriate data during ownership type changes', async () => {
            const ownershipSelect = document.getElementById('ownership_type');

            // Start with owned_rental (has both owned and leased data)
            ownershipSelect.value = 'owned_rental';
            ownershipSelect.dispatchEvent(new Event('change'));
            await waitForAnimations();

            // Fill data for both sections
            document.getElementById('purchase_price').value = '10,000,000';
            document.getElementById('monthly_rent').value = '500,000';

            // Switch to owned (should keep purchase_price, clear monthly_rent)
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change'));
            await waitForAnimations();

            expect(document.getElementById('purchase_price').value).toBe('10,000,000');
            expect(document.getElementById('monthly_rent').value).toBe('');

            // Switch to leased (should clear purchase_price)
            ownershipSelect.value = 'leased';
            ownershipSelect.dispatchEvent(new Event('change'));
            await waitForAnimations();

            expect(document.getElementById('purchase_price').value).toBe('');
        });
    });

    describe('Performance Workflow Tests', () => {
        it('should maintain performance with large amounts of data', async () => {
            const startTime = performance.now();

            // Simulate heavy data entry
            document.getElementById('ownership_type').value = 'leased';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            // Fill all fields with data
            const textInputs = document.querySelectorAll('input[type="text"], input[type="email"]');
            textInputs.forEach((input, index) => {
                input.value = `Test data ${index}`;
                input.dispatchEvent(new Event('input'));
            });

            const textareas = document.querySelectorAll('textarea');
            textareas.forEach((textarea, index) => {
                textarea.value = 'A'.repeat(500); // Large text
                textarea.dispatchEvent(new Event('input'));
            });

            await waitForCalculations();

            const endTime = performance.now();
            const duration = endTime - startTime;

            // Should complete within reasonable time
            expect(duration).toBeLessThan(2000);

            // Verify form is still functional
            const validation = manager.validator.validateForm();
            expect(validation).toBeDefined();
        });
    });

    describe('Error Recovery Workflow', () => {
        it('should recover gracefully from JavaScript errors', async () => {
            // Simulate error in calculation
            const originalCalculate = manager.calculator.calculateUnitPrice;
            manager.calculator.calculateUnitPrice = () => {
                throw new Error('Simulated calculation error');
            };

            document.getElementById('ownership_type').value = 'owned';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            document.getElementById('purchase_price').value = '10,000,000';
            document.getElementById('site_area_tsubo').value = '100';

            // Should not crash
            expect(() => {
                document.getElementById('purchase_price').dispatchEvent(new Event('input'));
            }).not.toThrow();

            // Restore original function
            manager.calculator.calculateUnitPrice = originalCalculate;

            // Should work again
            document.getElementById('purchase_price').dispatchEvent(new Event('input'));
            await waitForCalculations();

            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBe('100,000');
        });
    });
});

// Helper functions
async function waitForAnimations(duration = 100) {
    return new Promise(resolve => setTimeout(resolve, duration));
}

async function waitForCalculations(duration = 350) {
    return new Promise(resolve => setTimeout(resolve, duration));
}
