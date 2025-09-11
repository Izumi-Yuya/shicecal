/**
 * Integration tests for Land Info System
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LandInfoManager } from '../../../resources/js/modules/land-info/LandInfoManager.js';

// Mock complete DOM structure
const mockCompleteDOM = () => {
    document.body.innerHTML = `
    <form id="landInfoForm" action="/facilities/1/land-info" method="POST">
      <input type="hidden" name="_token" value="test-token">
      <input type="hidden" name="_method" value="PUT">
      
      <!-- Basic Info Section -->
      <select id="ownership_type" name="ownership_type">
        <option value="">選択してください</option>
        <option value="owned">自社</option>
        <option value="leased">賃借</option>
        <option value="owned_rental">自社（賃貸）</option>
      </select>
      
      <input type="number" id="parking_spaces" name="parking_spaces" />
      
      <!-- Area Info Section -->
      <input type="number" id="site_area_sqm" name="site_area_sqm" step="0.01" />
      <input type="number" id="site_area_tsubo" name="site_area_tsubo" step="0.01" />
      
      <!-- Conditional Sections -->
      <div id="owned_section" class="conditional-section d-none">
        <input type="text" id="purchase_price" name="purchase_price" class="currency-input" />
        <input type="text" id="unit_price_display" readonly />
      </div>
      
      <div id="leased_section" class="conditional-section d-none">
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
      
      <div id="management_section" class="conditional-section d-none">
        <input type="text" id="management_company_name" name="management_company_name" />
        <input type="text" id="management_company_postal_code" name="management_company_postal_code" />
        <input type="text" id="management_company_address" name="management_company_address" />
        <input type="text" id="management_company_phone" name="management_company_phone" />
        <input type="email" id="management_company_email" name="management_company_email" />
        <textarea id="management_company_notes" name="management_company_notes"></textarea>
      </div>
      
      <div id="owner_section" class="conditional-section d-none">
        <input type="text" id="owner_name" name="owner_name" />
        <input type="text" id="owner_postal_code" name="owner_postal_code" />
        <input type="text" id="owner_address" name="owner_address" />
        <input type="text" id="owner_phone" name="owner_phone" />
        <input type="email" id="owner_email" name="owner_email" />
        <textarea id="owner_notes" name="owner_notes"></textarea>
      </div>
      
      <div id="file_section" class="conditional-section d-none">
        <input type="file" id="land_documents" name="land_documents[]" multiple />
      </div>
      
      <!-- Form Actions -->
      <button type="submit" class="btn btn-primary">保存</button>
      <button type="button" id="previewBtn" class="btn btn-secondary">プレビュー</button>
      <button type="button" id="validateBtn" class="btn btn-info">検証</button>
    </form>
  `;
};

describe('Land Info Integration Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

        // Mock window location
        Object.defineProperty(window, 'location', {
            value: { pathname: '/facilities/1/land-info/edit' },
            writable: true
        });

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager && typeof manager.destroy === 'function') {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    describe('Complete Workflow Tests', () => {
        it('should handle complete owned property workflow', async () => {
            // Step 1: Select ownership type
            const ownershipSelect = document.getElementById('ownership_type');
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            // Wait for section visibility update
            await new Promise(resolve => setTimeout(resolve, 50));

            // Step 2: Verify correct sections are visible
            const ownedSection = document.getElementById('owned_section');
            const leasedSection = document.getElementById('leased_section');
            const managementSection = document.getElementById('management_section');

            expect(ownedSection.classList.contains('d-none')).toBe(false);
            expect(leasedSection.classList.contains('d-none')).toBe(true);
            expect(managementSection.classList.contains('d-none')).toBe(true);

            // Step 3: Fill in owned property data
            const purchasePrice = document.getElementById('purchase_price');
            const siteAreaTsubo = document.getElementById('site_area_tsubo');

            purchasePrice.value = '10,000,000';
            siteAreaTsubo.value = '100';

            // Trigger calculations
            purchasePrice.dispatchEvent(new Event('input', { bubbles: true }));
            siteAreaTsubo.dispatchEvent(new Event('input', { bubbles: true }));

            // Wait for debounced calculations
            await new Promise(resolve => setTimeout(resolve, 350));

            // Step 4: Verify calculations
            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBe('100,000');

            // Step 5: Validate form
            const validationResult = manager.validator.validateForm();
            expect(validationResult.isValid).toBe(true);
        });

        it('should handle complete leased property workflow', async () => {
            // Step 1: Select leased ownership type
            const ownershipSelect = document.getElementById('ownership_type');
            ownershipSelect.value = 'leased';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            // Step 2: Verify all leased sections are visible
            const leasedSection = document.getElementById('leased_section');
            const managementSection = document.getElementById('management_section');
            const ownerSection = document.getElementById('owner_section');
            const fileSection = document.getElementById('file_section');

            expect(leasedSection.classList.contains('d-none')).toBe(false);
            expect(managementSection.classList.contains('d-none')).toBe(false);
            expect(ownerSection.classList.contains('d-none')).toBe(false);
            expect(fileSection.classList.contains('d-none')).toBe(false);

            // Step 3: Fill in contract dates
            const startDate = document.getElementById('contract_start_date');
            const endDate = document.getElementById('contract_end_date');

            startDate.value = '2024-01-01';
            endDate.value = '2025-12-31';

            startDate.dispatchEvent(new Event('input', { bubbles: true }));
            endDate.dispatchEvent(new Event('input', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 350));

            // Step 4: Verify contract period calculation
            const contractPeriodDisplay = document.getElementById('contract_period_display');
            expect(contractPeriodDisplay.value).toContain('年');

            // Step 5: Fill required fields and validate
            document.getElementById('monthly_rent').value = '500,000';

            const validationResult = manager.validator.validateForm();
            expect(validationResult.isValid).toBe(true);
        });

        it('should handle ownership type switching correctly', async () => {
            const ownershipSelect = document.getElementById('ownership_type');
            const purchasePrice = document.getElementById('purchase_price');
            const monthlyRent = document.getElementById('monthly_rent');

            // Step 1: Start with owned, fill data
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            purchasePrice.value = '10,000,000';

            // Step 2: Switch to leased
            ownershipSelect.value = 'leased';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            // Step 3: Verify owned data is cleared
            expect(purchasePrice.value).toBe('');

            // Step 4: Fill leased data
            monthlyRent.value = '500,000';

            // Step 5: Switch back to owned
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            // Step 6: Verify leased data is cleared
            expect(monthlyRent.value).toBe('');
        });
    });

    describe('Error Handling Integration', () => {
        it('should handle calculation errors gracefully', async () => {
            const ownershipSelect = document.getElementById('ownership_type');
            const purchasePrice = document.getElementById('purchase_price');
            const siteAreaTsubo = document.getElementById('site_area_tsubo');

            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            // Test with invalid data
            purchasePrice.value = 'invalid';
            siteAreaTsubo.value = '0';

            purchasePrice.dispatchEvent(new Event('input', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 350));

            // Should not crash and should handle gracefully
            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBe('');
        });

        it('should handle form submission with validation errors', () => {
            const form = document.getElementById('landInfoForm');

            // Try to submit empty form
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);

            // Should prevent submission
            expect(submitEvent.defaultPrevented).toBe(true);
        });
    });

    describe('Performance Integration', () => {
        it('should maintain good performance with rapid changes', async () => {
            const ownershipSelect = document.getElementById('ownership_type');
            const purchasePrice = document.getElementById('purchase_price');

            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            const startTime = performance.now();

            // Rapid input changes
            for (let i = 0; i < 10; i++) {
                purchasePrice.value = `${1000000 + i * 100000}`;
                purchasePrice.dispatchEvent(new Event('input', { bubbles: true }));
            }

            await new Promise(resolve => setTimeout(resolve, 400));

            const endTime = performance.now();
            const duration = endTime - startTime;

            // Should complete within reasonable time
            expect(duration).toBeLessThan(1000);

            // Should have final calculated value
            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBeTruthy();
        });
    });

    describe('Accessibility Integration', () => {
        it('should maintain proper ARIA attributes during section changes', async () => {
            const ownershipSelect = document.getElementById('ownership_type');
            const ownedSection = document.getElementById('owned_section');

            // Initially hidden
            expect(ownedSection.getAttribute('aria-hidden')).toBe('true');

            // Show section
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await new Promise(resolve => setTimeout(resolve, 50));

            // Should update ARIA attributes
            expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
            expect(ownedSection.getAttribute('aria-expanded')).toBe('true');
        });
    });
});
