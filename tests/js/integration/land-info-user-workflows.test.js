/**
 * Integration Tests for Land Info User Workflows
 * Tests complete form submission flow, ownership type switching, responsive design,
 * page reload scenarios, and accessibility attribute synchronization
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { LandInfoManager } from '../../../resources/js/modules/land-info/LandInfoManager.js';

// Mock complete DOM structure with all sections and responsive elements
const mockCompleteFormDOM = () => {
    document.body.innerHTML = `
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 col-md-8 col-lg-6">
          <form id="landInfoForm" action="/facilities/1/land-info" method="POST" novalidate>
            <input type="hidden" name="_token" value="test-token">
            <input type="hidden" name="_method" value="PUT">
            
            <!-- Basic Info Section -->
            <div class="card mb-3">
              <div class="card-header">
                <h5>基本情報</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="ownership_type" class="form-label">所有形態 <span class="text-danger">*</span></label>
                    <select id="ownership_type" name="ownership_type" class="form-select" required>
                      <option value="">選択してください</option>
                      <option value="owned">自社</option>
                      <option value="leased">賃借</option>
                      <option value="owned_rental">自社（賃貸）</option>
                    </select>
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="parking_spaces" class="form-label">駐車場台数</label>
                    <input type="number" id="parking_spaces" name="parking_spaces" class="form-control" min="0" />
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="site_area_sqm" class="form-label">敷地面積（㎡）</label>
                    <input type="number" id="site_area_sqm" name="site_area_sqm" class="form-control" step="0.01" min="0" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="site_area_tsubo" class="form-label">敷地面積（坪）</label>
                    <input type="number" id="site_area_tsubo" name="site_area_tsubo" class="form-control" step="0.01" min="0" />
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Owned Property Section -->
            <div id="owned_section" class="card mb-3 conditional-section d-none" aria-hidden="true" aria-expanded="false">
              <div class="card-header">
                <h5>自社物件情報</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="purchase_price" class="form-label">購入価格（円）</label>
                    <input type="text" id="purchase_price" name="purchase_price" class="form-control currency-input" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="unit_price_display" class="form-label">坪単価（円）</label>
                    <input type="text" id="unit_price_display" class="form-control calculated-field" readonly />
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Leased Property Section -->
            <div id="leased_section" class="card mb-3 conditional-section d-none" aria-hidden="true" aria-expanded="false">
              <div class="card-header">
                <h5>賃借物件情報</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="monthly_rent" class="form-label">月額賃料（円） <span class="text-danger">*</span></label>
                    <input type="text" id="monthly_rent" name="monthly_rent" class="form-control currency-input" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="contract_start_date" class="form-label">契約開始日 <span class="text-danger">*</span></label>
                    <input type="date" id="contract_start_date" name="contract_start_date" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="contract_end_date" class="form-label">契約終了日 <span class="text-danger">*</span></label>
                    <input type="date" id="contract_end_date" name="contract_end_date" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="contract_period_display" class="form-label">契約期間</label>
                    <input type="text" id="contract_period_display" class="form-control calculated-field" readonly />
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="auto_renewal" class="form-label">自動更新</label>
                    <select id="auto_renewal" name="auto_renewal" class="form-select">
                      <option value="">選択してください</option>
                      <option value="yes">あり</option>
                      <option value="no">なし</option>
                    </select>
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Management Company Section -->
            <div id="management_section" class="card mb-3 conditional-section d-none" aria-hidden="true" aria-expanded="false">
              <div class="card-header">
                <h5>管理会社情報</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="management_company_name" class="form-label">会社名</label>
                    <input type="text" id="management_company_name" name="management_company_name" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="management_company_email" class="form-label">メールアドレス</label>
                    <input type="email" id="management_company_email" name="management_company_email" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Owner Information Section -->
            <div id="owner_section" class="card mb-3 conditional-section d-none" aria-hidden="true" aria-expanded="false">
              <div class="card-header">
                <h5>所有者情報</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="owner_name" class="form-label">所有者名</label>
                    <input type="text" id="owner_name" name="owner_name" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                  
                  <div class="col-12 col-sm-6 mb-3">
                    <label for="owner_email" class="form-label">メールアドレス</label>
                    <input type="email" id="owner_email" name="owner_email" class="form-control" />
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- File Upload Section -->
            <div id="file_section" class="card mb-3 conditional-section d-none" aria-hidden="true" aria-expanded="false">
              <div class="card-header">
                <h5>関連書類</h5>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label for="land_documents" class="form-label">土地関連書類</label>
                  <input type="file" id="land_documents" name="land_documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png" />
                  <div class="form-text">PDF、JPG、PNG形式のファイルをアップロードできます。</div>
                  <div class="invalid-feedback"></div>
                </div>
              </div>
            </div>
            
            <!-- Form Actions -->
            <div class="card">
              <div class="card-body">
                <div class="d-flex flex-column flex-sm-row gap-2">
                  <button type="submit" class="btn btn-primary">保存</button>
                  <button type="button" id="previewBtn" class="btn btn-secondary">プレビュー</button>
                  <button type="button" id="validateBtn" class="btn btn-info">検証</button>
                  <button type="button" id="resetBtn" class="btn btn-outline-secondary">リセット</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;
};

// Mock window resize for responsive testing
const mockWindowResize = (width, height) => {
    Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: width
    });
    Object.defineProperty(window, 'innerHeight', {
        writable: true,
        configurable: true,
        value: height
    });
    window.dispatchEvent(new Event('resize'));
};

describe('Complete Form Submission Flow Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteFormDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));
        global.fetch = vi.fn();

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    describe('Owned property submission flow', () => {
        it('should complete full owned property submission workflow', async () => {
            // Step 1: Select ownership type
            const ownershipSelect = document.getElementById('ownership_type');
            ownershipSelect.value = 'owned';
            ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await waitForAnimations();

            // Step 2: Verify section visibility and accessibility
            const ownedSection = document.getElementById('owned_section');
            expect(ownedSection.classList.contains('d-none')).toBe(false);
            expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
            expect(ownedSection.getAttribute('aria-expanded')).toBe('true');

            // Step 3: Fill required and optional data
            document.getElementById('parking_spaces').value = '50';
            document.getElementById('site_area_tsubo').value = '100';
            document.getElementById('purchase_price').value = '10,000,000';

            // Step 4: Trigger calculations
            document.getElementById('purchase_price').dispatchEvent(new Event('input', { bubbles: true }));
            await waitForCalculations();

            // Step 5: Verify calculations
            const unitPriceDisplay = document.getElementById('unit_price_display');
            expect(unitPriceDisplay.value).toBe('100,000');

            // Step 6: Validate form
            const validation = manager.validator.validateForm();
            expect(validation.isValid).toBe(true);

            // Step 7: Mock successful form submission
            global.fetch.mockResolvedValueOnce({
                ok: true,
                json: async () => ({ success: true, message: '保存しました' })
            });

            // Step 8: Submit form
            const form = document.getElementById('landInfoForm');
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);

            // Should not prevent default for valid form
            expect(submitEvent.defaultPrevented).toBe(false);
        });

        it('should handle validation errors during owned property submission', async () => {
            // Select owned type
            document.getElementById('ownership_type').value = 'owned';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            // Fill invalid data
            document.getElementById('purchase_price').value = 'invalid price';
            document.getElementById('site_area_tsubo').value = '-50';

            // Attempt submission
            const form = document.getElementById('landInfoForm');
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);

            // Should prevent submission due to validation errors
            expect(submitEvent.defaultPrevented).toBe(true);

            // Should display validation errors
            const purchasePriceField = document.getElementById('purchase_price');
            const siteAreaField = document.getElementById('site_area_tsubo');

            expect(purchasePriceField.classList.contains('is-invalid')).toBe(true);
            expect(siteAreaField.classList.contains('is-invalid')).toBe(true);
        });
    });

    describe('Leased property submission flow', () => {
        it('should complete full leased property submission workflow', async () => {
            // Step 1: Select leased type
            document.getElementById('ownership_type').value = 'leased';
            document.getElementById('ownership_type').dispatchEvent(new Event('change'));

            await waitForAnimations();

            // Step 2: Verify all leased sections are visible
            const sectionsToCheck = ['leased_section', 'management_section', 'owner_section', 'file_section'];
            sectionsToCheck.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                expect(section.classList.contains('d-none')).toBe(false);
                expect(section.getAttribute('aria-hidden')).toBe('false');
                expect(section.getAttribute('aria-expanded')).toBe('true');
            });

            // Step 3: Fill required leased data
            document.getElementById('monthly_rent').value = '500,000';
            document.getElementById('contract_start_date').value = '2024-01-01';
            document.getElementById('contract_end_date').value = '2025-12-31';
            document.getElementById('auto_renewal').value = 'yes';

            // Step 4: Fill management company data
            document.getElementById('management_company_name').value = '株式会社管理';
            document.getElementById('management_company_email').value = 'info@management.com';

            // Step 5: Fill owner data
            document.getElementById('owner_name').value = '田中太郎';
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

            // Step 9: Submit form
            const form = document.getElementById('landInfoForm');
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);

            expect(submitEvent.defaultPrevented).toBe(false);
        });
    });
});

describe('Ownership Type Switching Behavior Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteFormDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('should handle ownership type switching with data clearing', async () => {
        const ownershipSelect = document.getElementById('ownership_type');
        const purchasePrice = document.getElementById('purchase_price');
        const monthlyRent = document.getElementById('monthly_rent');
        const managementName = document.getElementById('management_company_name');

        // Step 1: Start with owned, fill data
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        purchasePrice.value = '10,000,000';
        document.getElementById('site_area_tsubo').value = '100';

        // Step 2: Switch to leased
        ownershipSelect.value = 'leased';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Step 3: Verify owned data is cleared and sections are properly hidden/shown
        expect(purchasePrice.value).toBe('');
        expect(purchasePrice.disabled).toBe(true);
        expect(monthlyRent.disabled).toBe(false);

        // Step 4: Fill leased data
        monthlyRent.value = '500,000';
        managementName.value = 'Test Company';

        // Step 5: Switch back to owned
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Step 6: Verify leased data is cleared
        expect(monthlyRent.value).toBe('');
        expect(managementName.value).toBe('');
        expect(monthlyRent.disabled).toBe(true);
        expect(purchasePrice.disabled).toBe(false);
    });

    it('should maintain validation state during ownership type switching', async () => {
        const ownershipSelect = document.getElementById('ownership_type');
        const purchasePrice = document.getElementById('purchase_price');
        const monthlyRent = document.getElementById('monthly_rent');

        // Step 1: Start with owned, create validation error
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        purchasePrice.value = 'invalid';
        purchasePrice.classList.add('is-invalid');

        // Step 2: Switch to leased
        ownershipSelect.value = 'leased';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Step 3: Verify validation errors are cleared for hidden fields
        expect(purchasePrice.classList.contains('is-invalid')).toBe(false);

        // Step 4: Create validation error in leased field
        monthlyRent.value = 'invalid';
        monthlyRent.classList.add('is-invalid');

        // Step 5: Switch back to owned
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Step 6: Verify leased validation errors are cleared
        expect(monthlyRent.classList.contains('is-invalid')).toBe(false);
    });
});

describe('Responsive Design Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteFormDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('should maintain functionality across different viewport sizes', async () => {
    // Test mobile viewport (320px)
        mockWindowResize(320, 568);
        await waitForAnimations();

        // Verify form is still functional on mobile
        document.getElementById('ownership_type').value = 'owned';
        document.getElementById('ownership_type').dispatchEvent(new Event('change'));
        await waitForAnimations();

        const ownedSection = document.getElementById('owned_section');
        expect(ownedSection.classList.contains('d-none')).toBe(false);

        // Test tablet viewport (768px)
        mockWindowResize(768, 1024);
        await waitForAnimations();

        // Verify calculations still work on tablet
        document.getElementById('purchase_price').value = '10,000,000';
        document.getElementById('site_area_tsubo').value = '100';
        document.getElementById('purchase_price').dispatchEvent(new Event('input'));
        await waitForCalculations();

        const unitPriceDisplay = document.getElementById('unit_price_display');
        expect(unitPriceDisplay.value).toBe('100,000');

        // Test desktop viewport (1200px)
        mockWindowResize(1200, 800);
        await waitForAnimations();

        // Verify form validation still works on desktop
        const validation = manager.validator.validateForm();
        expect(validation.isValid).toBe(true);
    });

    it('should handle responsive layout changes during form interaction', async () => {
    // Start on desktop
        mockWindowResize(1200, 800);

        // Fill form data
        document.getElementById('ownership_type').value = 'leased';
        document.getElementById('ownership_type').dispatchEvent(new Event('change'));
        await waitForAnimations();

        document.getElementById('monthly_rent').value = '500,000';
        document.getElementById('contract_start_date').value = '2024-01-01';

        // Resize to mobile during form interaction
        mockWindowResize(320, 568);
        await waitForAnimations();

        // Verify data is preserved and form is still functional
        expect(document.getElementById('monthly_rent').value).toBe('500,000');
        expect(document.getElementById('contract_start_date').value).toBe('2024-01-01');

        // Verify sections are still properly visible
        const leasedSection = document.getElementById('leased_section');
        expect(leasedSection.classList.contains('d-none')).toBe(false);
    });
});

describe('Page Reload and Pre-filled Data Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteFormDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('should handle initial state with pre-filled owned data', async () => {
    // Simulate pre-filled data (as would come from old() values or existing data)
        document.getElementById('ownership_type').value = 'owned';
        document.getElementById('purchase_price').value = '15,000,000';
        document.getElementById('site_area_tsubo').value = '150';

        // Initialize manager (simulating page load)
        manager = new LandInfoManager();
        await waitForAnimations();

        // Verify initial state is handled correctly
        const ownedSection = document.getElementById('owned_section');
        expect(ownedSection.classList.contains('d-none')).toBe(false);
        expect(ownedSection.getAttribute('aria-hidden')).toBe('false');

        // Verify calculations are performed on initialization
        await waitForCalculations();
        const unitPriceDisplay = document.getElementById('unit_price_display');
        expect(unitPriceDisplay.value).toBe('100,000');
    });

    it('should handle initial state with pre-filled leased data', async () => {
    // Simulate pre-filled leased data
        document.getElementById('ownership_type').value = 'leased';
        document.getElementById('monthly_rent').value = '800,000';
        document.getElementById('contract_start_date').value = '2024-01-01';
        document.getElementById('contract_end_date').value = '2026-12-31';

        // Initialize manager
        manager = new LandInfoManager();
        await waitForAnimations();

        // Verify all leased sections are visible
        const sectionsToCheck = ['leased_section', 'management_section', 'owner_section', 'file_section'];
        sectionsToCheck.forEach(sectionId => {
            const section = document.getElementById(sectionId);
            expect(section.classList.contains('d-none')).toBe(false);
            expect(section.getAttribute('aria-hidden')).toBe('false');
        });

        // Verify contract period calculation is performed
        await waitForCalculations();
        const contractPeriodDisplay = document.getElementById('contract_period_display');
        expect(contractPeriodDisplay.value).toContain('年');
    });

    it('should handle page reload after validation errors', async () => {
    // Simulate validation errors state (as would persist after form submission failure)
        document.getElementById('ownership_type').value = 'owned';
        document.getElementById('purchase_price').value = 'invalid';
        document.getElementById('purchase_price').classList.add('is-invalid');

        const invalidFeedback = document.querySelector('#purchase_price + .invalid-feedback');
        if (invalidFeedback) {
            invalidFeedback.textContent = '有効な金額を入力してください';
        }

        // Initialize manager
        manager = new LandInfoManager();
        await waitForAnimations();

        // Verify error state is maintained
        const purchasePrice = document.getElementById('purchase_price');
        expect(purchasePrice.classList.contains('is-invalid')).toBe(true);

        // Verify form is still functional
        purchasePrice.value = '10,000,000';
        purchasePrice.dispatchEvent(new Event('input'));
        await waitForCalculations();

        // Error should be cleared and calculation should work
        expect(purchasePrice.classList.contains('is-invalid')).toBe(false);
        const unitPriceDisplay = document.getElementById('unit_price_display');
        expect(unitPriceDisplay.value).toBe('100,000');
    });
});

describe('Accessibility Attribute Synchronization Tests', () => {
    let manager;

    beforeEach(() => {
        mockCompleteFormDOM();
        vi.spyOn(performance, 'now').mockReturnValue(1000);
        global.requestAnimationFrame = vi.fn(cb => setTimeout(cb, 0));

        manager = new LandInfoManager();
    });

    afterEach(() => {
        if (manager?.destroy) {
            manager.destroy();
        }
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    it('should synchronize aria-hidden and aria-expanded during transitions', async () => {
        const ownershipSelect = document.getElementById('ownership_type');
        const ownedSection = document.getElementById('owned_section');
        const leasedSection = document.getElementById('leased_section');

        // Initial state - all sections hidden
        expect(ownedSection.getAttribute('aria-hidden')).toBe('true');
        expect(ownedSection.getAttribute('aria-expanded')).toBe('false');
        expect(leasedSection.getAttribute('aria-hidden')).toBe('true');
        expect(leasedSection.getAttribute('aria-expanded')).toBe('false');

        // Show owned section
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Verify owned section accessibility attributes
        expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
        expect(ownedSection.getAttribute('aria-expanded')).toBe('true');
        expect(leasedSection.getAttribute('aria-hidden')).toBe('true');
        expect(leasedSection.getAttribute('aria-expanded')).toBe('false');

        // Switch to leased
        ownershipSelect.value = 'leased';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Verify accessibility attributes are updated
        expect(ownedSection.getAttribute('aria-hidden')).toBe('true');
        expect(ownedSection.getAttribute('aria-expanded')).toBe('false');
        expect(leasedSection.getAttribute('aria-hidden')).toBe('false');
        expect(leasedSection.getAttribute('aria-expanded')).toBe('true');
    });

    it('should maintain accessibility attributes during rapid transitions', async () => {
        const ownershipSelect = document.getElementById('ownership_type');
        const ownedSection = document.getElementById('owned_section');

        // Rapid switching
        const switchSequence = ['owned', 'leased', 'owned_rental', 'owned'];

        for (const type of switchSequence) {
            ownershipSelect.value = type;
            ownershipSelect.dispatchEvent(new Event('change'));
            await waitForAnimations();

            // Verify accessibility attributes are consistent with visibility
            const shouldShowOwned = ['owned', 'owned_rental'].includes(type);
            expect(ownedSection.getAttribute('aria-hidden')).toBe(shouldShowOwned ? 'false' : 'true');
            expect(ownedSection.getAttribute('aria-expanded')).toBe(shouldShowOwned ? 'true' : 'false');
        }
    });

    it('should handle accessibility attributes during error states', async () => {
        const ownershipSelect = document.getElementById('ownership_type');
        const ownedSection = document.getElementById('owned_section');

        // Show section with error
        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Add validation error
        const purchasePrice = document.getElementById('purchase_price');
        purchasePrice.value = 'invalid';
        purchasePrice.classList.add('is-invalid');

        // Verify accessibility attributes are maintained even with errors
        expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
        expect(ownedSection.getAttribute('aria-expanded')).toBe('true');

        // Switch away and back
        ownershipSelect.value = 'leased';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        ownershipSelect.value = 'owned';
        ownershipSelect.dispatchEvent(new Event('change'));
        await waitForAnimations();

        // Verify attributes are still correct after error clearing
        expect(ownedSection.getAttribute('aria-hidden')).toBe('false');
        expect(ownedSection.getAttribute('aria-expanded')).toBe('true');
        expect(purchasePrice.classList.contains('is-invalid')).toBe(false);
    });
});

// Helper functions
async function waitForAnimations(duration = 100) {
    return new Promise(resolve => setTimeout(resolve, duration));
}

async function waitForCalculations(duration = 350) {
    return new Promise(resolve => setTimeout(resolve, duration));
}
