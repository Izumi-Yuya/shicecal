/**
 * Land Information Form Manager - Final Unified Version
 * All functionality consolidated into a single, maintainable file
 * Includes section visibility, calculations, validation, and accessibility features
 */

class LandInfoManager {
  // Constants for ownership types
  static OWNERSHIP_TYPES = {
    OWNED: 'owned',
    LEASED: 'leased',
    OWNED_RENTAL: 'owned_rental'
  };

  // Section visibility rules
  static SECTION_RULES = {
    owned_section: ['owned', 'owned_rental'],
    leased_section: ['leased', 'owned_rental'],
    management_section: ['leased'],
    owner_section: ['leased'],
    file_section: ['leased', 'owned_rental']
  };

  // Field groups for clearing
  static FIELD_GROUPS = {
    owned: ['purchase_price', 'unit_price_display'],
    leased: ['monthly_rent', 'contract_start_date', 'contract_end_date', 'auto_renewal', 'contract_period_display'],
    management: [
      'management_company_name', 'management_company_postal_code', 'management_company_address',
      'management_company_building', 'management_company_phone', 'management_company_fax',
      'management_company_email', 'management_company_url', 'management_company_notes'
    ],
    owner: [
      'owner_name', 'owner_postal_code', 'owner_address', 'owner_building',
      'owner_phone', 'owner_fax', 'owner_email', 'owner_url', 'owner_notes'
    ]
  };

  constructor() {
    this.isInitialized = false;
    this.debounceTimers = new Map();
    this.init();
  }

  // ========================================
  // INITIALIZATION
  // ========================================

  init() {
    console.log('🔧 Starting LandInfoManager initialization...');

    try {
      this.setupEventListeners();
      this.setupCalculations();
      this.setupValidation();
      this.setupFileHandling();
      this.setupAccessibility();

      // Initial state update with debug
      console.log('⏰ Setting up initial state update...');
      setTimeout(() => {
        console.log('🔄 Performing initial updates...');
        this.updateSectionVisibility();
        this.performInitialCalculations();
        console.log('✅ Initial updates completed');
      }, 100);

      this.isInitialized = true;
      console.log('✅ LandInfoManager initialized successfully');

    } catch (error) {
      console.error('❌ Failed to initialize LandInfoManager:', error);
      throw error;
    }
  }

  isLandInfoPage() {
    return document.getElementById('landInfoForm') &&
      document.getElementById('ownership_type');
  }

  // ========================================
  // EVENT LISTENERS
  // ========================================

  setupEventListeners() {
    const ownershipSelect = document.getElementById('ownership_type');
    if (ownershipSelect) {
      ownershipSelect.addEventListener('change', (e) => {
        console.log('🔄 Ownership type changed to:', e.target.value);
        this.handleOwnershipChange(e.target.value);
      });
    }

    // Currency formatting
    document.querySelectorAll('.currency-input').forEach(input => {
      input.addEventListener('blur', (e) => this.formatCurrency(e.target));
      input.addEventListener('focus', (e) => this.removeCurrencyFormat(e.target));
    });

    // Number conversion (full-width to half-width)
    document.querySelectorAll('input[type="number"], .currency-input').forEach(input => {
      input.addEventListener('input', (e) => this.convertToHalfWidth(e.target));
    });

    // Phone/postal code formatting
    document.querySelectorAll('input[name$="_phone"], input[name$="_fax"]').forEach(input => {
      input.addEventListener('blur', (e) => this.formatPhoneNumber(e.target));
    });

    document.querySelectorAll('input[name$="_postal_code"]').forEach(input => {
      input.addEventListener('blur', (e) => this.formatPostalCode(e.target));
    });
  }

  handleOwnershipChange(ownershipType) {
    this.updateSectionVisibility();
    this.clearConditionalFields();
    this.clearValidationErrors();
  }

  // ========================================
  // SECTION VISIBILITY
  // ========================================

  updateSectionVisibility() {
    console.log('🔄 Showing all sections (no conditional logic)');

    // Show all sections regardless of ownership type
    Object.keys(LandInfoManager.SECTION_RULES).forEach(sectionId => {
      const section = document.getElementById(sectionId);
      if (!section) {
        console.log(`⚠️ Section not found: ${sectionId}`);
        return;
      }

      // Always show all sections
      section.style.display = 'block';
      section.style.visibility = 'visible';
      section.style.opacity = '1';
      section.setAttribute('aria-hidden', 'false');
      section.setAttribute('aria-expanded', 'true');
      section.classList.add('section-visible');
      section.classList.remove('section-hidden');

      console.log(`✅ ${sectionId} is now visible (always shown)`);
    });

    console.log('📋 All sections are now visible');
  }

  clearConditionalFields() {
    // No longer clear fields - allow all fields to retain their values
    console.log('🔄 Field clearing disabled - all fields retain their values');
  }

  // ========================================
  // CALCULATIONS
  // ========================================

  setupCalculations() {
    // Unit price calculation
    const purchasePrice = document.getElementById('purchase_price');
    const siteAreaTsubo = document.getElementById('site_area_tsubo');

    if (purchasePrice && siteAreaTsubo) {
      [purchasePrice, siteAreaTsubo].forEach(field => {
        field.addEventListener('input', () => this.debouncedCalculation('unitPrice'));
      });
    }

    // Contract period calculation
    const startDate = document.getElementById('contract_start_date');
    const endDate = document.getElementById('contract_end_date');

    if (startDate && endDate) {
      [startDate, endDate].forEach(field => {
        field.addEventListener('change', () => this.debouncedCalculation('contractPeriod'));
      });
    }
  }

  debouncedCalculation(type) {
    if (this.debounceTimers.has(type)) {
      clearTimeout(this.debounceTimers.get(type));
    }

    const timer = setTimeout(() => {
      if (type === 'unitPrice') {
        this.calculateUnitPrice();
      } else if (type === 'contractPeriod') {
        this.calculateContractPeriod();
      }
      this.debounceTimers.delete(type);
    }, 300);

    this.debounceTimers.set(type, timer);
  }

  calculateUnitPrice() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const siteAreaTsuboInput = document.getElementById('site_area_tsubo');
    const unitPriceDisplay = document.getElementById('unit_price_display');

    if (!purchasePriceInput || !siteAreaTsuboInput || !unitPriceDisplay) return;

    const purchasePrice = parseFloat(purchasePriceInput.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(siteAreaTsuboInput.value) || 0;

    if (purchasePrice > 0 && siteAreaTsubo > 0) {
      // 計算中のアニメーション開始
      unitPriceDisplay.classList.add('calculating');

      // 少し遅延を入れて計算感を演出
      setTimeout(() => {
        const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
        unitPriceDisplay.value = unitPrice.toLocaleString();

        // アニメーション終了
        unitPriceDisplay.classList.remove('calculating');
        this.addCalculationFeedback(unitPriceDisplay);

        // 成功のフィードバック
        unitPriceDisplay.style.borderColor = '#198754';
        setTimeout(() => {
          unitPriceDisplay.style.borderColor = '#0dcaf0';
        }, 1000);

        // Validation warning for extremely high unit prices
        if (unitPrice > 10000000) {
          this.showWarning('坪単価が非常に高額です。入力内容をご確認ください。');
        }
      }, 300);
    } else {
      unitPriceDisplay.value = '';
      unitPriceDisplay.classList.remove('calculating');
    }
  }

  calculateContractPeriod() {
    const startDateInput = document.getElementById('contract_start_date');
    const endDateInput = document.getElementById('contract_end_date');
    const periodDisplay = document.getElementById('contract_period_display');

    if (!startDateInput || !endDateInput || !periodDisplay) return;

    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);

    if (startDate && endDate && endDate > startDate) {
      // 計算中のアニメーション開始
      periodDisplay.classList.add('calculating');

      // 少し遅延を入れて計算感を演出
      setTimeout(() => {
        const years = endDate.getFullYear() - startDate.getFullYear();
        const months = endDate.getMonth() - startDate.getMonth();
        let totalMonths = years * 12 + months;

        if (endDate.getDate() < startDate.getDate()) {
          totalMonths--;
        }

        const displayYears = Math.floor(totalMonths / 12);
        const displayMonths = totalMonths % 12;

        let periodText = '';
        if (displayYears > 0) periodText += `${displayYears}年`;
        if (displayMonths > 0) periodText += `${displayMonths}ヶ月`;

        periodDisplay.value = periodText || '0ヶ月';

        // アニメーション終了
        periodDisplay.classList.remove('calculating');
        this.addCalculationFeedback(periodDisplay);

        // 成功のフィードバック
        periodDisplay.style.borderColor = '#198754';
        setTimeout(() => {
          periodDisplay.style.borderColor = '#0dcaf0';
        }, 1000);

        // Validation warning for extremely long contracts
        if (totalMonths > 600) { // 50 years
          this.showWarning('契約期間が非常に長期です。入力内容をご確認ください。');
        }
      }, 300);
    } else {
      periodDisplay.value = '';
      periodDisplay.classList.remove('calculating');
    }
  }

  performInitialCalculations() {
    this.calculateUnitPrice();
    this.calculateContractPeriod();
  }

  // ========================================
  // FORM VALIDATION
  // ========================================

  setupValidation() {
    const form = document.getElementById('landInfoForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      if (!this.validateForm()) {
        e.preventDefault();
      }
    });
  }

  validateForm() {
    // Validation disabled - always return true to allow form submission
    console.log('🔄 Form validation disabled - allowing all submissions');
    this.clearValidationErrors();
    return true;
  }

  // ========================================
  // FILE HANDLING
  // ========================================

  setupFileHandling() {
    document.querySelectorAll('input[type="file"]').forEach(input => {
      input.addEventListener('change', (e) => this.validateFileSize(e.target));
    });
  }

  validateFileSize(input) {
    const maxSize = 2 * 1024 * 1024; // 2MB (PHP upload_max_filesize limit)

    Array.from(input.files).forEach(file => {
      if (file.size > maxSize) {
        alert(`ファイル "${file.name}" のサイズが大きすぎます。2MB以下のファイルを選択してください。`);
        input.value = '';
      }
    });
  }

  // ========================================
  // ACCESSIBILITY
  // ========================================

  setupAccessibility() {
    // Character count for textareas
    const textareas = [
      { id: 'notes', maxLength: 2000 },
      { id: 'management_company_notes', maxLength: 1000 },
      { id: 'owner_notes', maxLength: 1000 }
    ];

    textareas.forEach(({ id, maxLength }) => {
      const textarea = document.getElementById(id);
      if (textarea) {
        textarea.addEventListener('input', () => {
          this.updateCharacterCount(textarea, maxLength);
        });
        // Initial count
        this.updateCharacterCount(textarea, maxLength);
      }
    });
  }

  updateCharacterCount(textarea, maxLength) {
    const countElement = document.getElementById(textarea.id + '_count');
    if (countElement) {
      const currentLength = textarea.value.length;
      countElement.textContent = currentLength;

      // Warning when approaching limit
      if (currentLength > maxLength * 0.9) {
        countElement.classList.add('text-warning');
      } else {
        countElement.classList.remove('text-warning');
      }
    }
  }

  // ========================================
  // UTILITY METHODS
  // ========================================

  getOwnershipType() {
    const select = document.getElementById('ownership_type');
    return select ? select.value : '';
  }

  formatCurrency(input) {
    const value = parseInt(input.value.replace(/,/g, '')) || 0;
    if (value > 0) {
      input.value = value.toLocaleString();
    }
  }

  removeCurrencyFormat(input) {
    input.value = input.value.replace(/,/g, '');
  }

  convertToHalfWidth(input) {
    if (!input || !input.value) return;

    let sanitizedValue = input.value.replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });

    if (input.type === 'number' || input.classList.contains('currency-input')) {
      sanitizedValue = sanitizedValue.replace(/[^\d.,\-]/g, '');
    }

    if (sanitizedValue.length > 50) {
      sanitizedValue = sanitizedValue.substring(0, 50);
    }

    input.value = sanitizedValue;
  }

  formatPhoneNumber(input) {
    const value = input.value.replace(/[^\d-]/g, '');
    input.value = value;
  }

  formatPostalCode(input) {
    let value = input.value.replace(/[^\d-]/g, '');
    if (value.length === 7 && !value.includes('-')) {
      value = value.substring(0, 3) + '-' + value.substring(3);
    }
    input.value = value;
  }

  clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => {
      el.classList.remove('is-invalid');
    });
  }

  addCalculationFeedback(element) {
    element.classList.add('calculated');
    setTimeout(() => {
      element.classList.remove('calculated');
    }, 1000);
  }

  showValidationErrors(errors) {
    const errorContainer = document.getElementById('validation-errors');
    if (errorContainer) {
      errorContainer.innerHTML = errors.map(error =>
        `<div class="alert alert-danger">${error}</div>`
      ).join('');
      errorContainer.scrollIntoView({ behavior: 'smooth' });
    } else {
      alert('入力エラー:\n' + errors.join('\n'));
    }
  }

  showWarning(message) {
    console.warn('⚠️', message);
    // Could implement toast notification here
  }

  // ========================================
  // DEBUG METHODS
  // ========================================

  getMetrics() {
    return {
      isInitialized: this.isInitialized,
      ownershipType: this.getOwnershipType(),
      visibleSections: this.getVisibleSections(),
      debounceTimers: this.debounceTimers.size
    };
  }

  getVisibleSections() {
    const sections = {};
    Object.keys(LandInfoManager.SECTION_RULES).forEach(sectionId => {
      const element = document.getElementById(sectionId);
      sections[sectionId] = element ? element.style.display !== 'none' : false;
    });
    return sections;
  }
}

// ========================================
// INITIALIZATION & GLOBAL FUNCTIONS
// ========================================

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 DOM ready, checking for land info form...');

  // Check if we're on the land info page
  const form = document.getElementById('landInfoForm');
  const ownershipSelect = document.getElementById('ownership_type');

  console.log('Form found:', !!form);
  console.log('Ownership select found:', !!ownershipSelect);

  if (!form || !ownershipSelect) {
    console.log('❌ Not on land info page, skipping initialization');
    return;
  }

  try {
    console.log('✅ Initializing LandInfoManager...');
    window.landInfoManager = new LandInfoManager();
  } catch (error) {
    console.error('❌ Failed to initialize LandInfoManager:', error);

    // Fallback to basic functionality
    console.log('🔄 Falling back to basic functionality...');
    initBasicFunctionality();
  }
});

// Fallback basic functionality
function initBasicFunctionality() {
  const ownershipSelect = document.getElementById('ownership_type');
  if (!ownershipSelect) {
    console.log('❌ No ownership select found');
    return;
  }

  console.log('✅ Basic functionality initialized');

  const sectionRules = {
    owned_section: ['owned', 'owned_rental'],
    leased_section: ['leased', 'owned_rental'],
    management_section: ['leased'],
    owner_section: ['leased'],
    file_section: ['leased', 'owned_rental']
  };

  function updateSections() {
    const ownershipType = ownershipSelect.value;
    console.log('📋 Updating sections for:', ownershipType);

    Object.entries(sectionRules).forEach(([sectionId, allowedTypes]) => {
      const section = document.getElementById(sectionId);
      if (section) {
        const shouldShow = allowedTypes.includes(ownershipType);
        section.style.display = shouldShow ? 'block' : 'none';
        section.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
      }
    });
  }

  ownershipSelect.addEventListener('change', updateSections);
  setTimeout(updateSections, 100);
}

// Debug functions for testing
window.testOwnership = function (type) {
  console.log('🧪 Testing ownership change to:', type);
  const select = document.getElementById('ownership_type');
  if (select) {
    select.value = type;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    // Force update if manager exists
    if (window.landInfoManager) {
      window.landInfoManager.updateSectionVisibility();
    }
  } else {
    console.log('❌ Ownership select not found');
  }
};

window.debugSections = function () {
  console.log('🔍 Current section states:');
  const sections = ['owned_section', 'leased_section', 'management_section', 'owner_section', 'file_section'];

  sections.forEach(sectionId => {
    const section = document.getElementById(sectionId);
    if (section) {
      console.log(`${sectionId}:`, {
        display: section.style.display,
        visible: section.style.display !== 'none',
        ariaHidden: section.getAttribute('aria-hidden'),
        classes: section.className
      });
    } else {
      console.log(`${sectionId}: NOT FOUND`);
    }
  });

  if (window.landInfoManager) {
    console.log('Manager sections:', window.landInfoManager.getVisibleSections());
  }
};

window.getMetrics = function () {
  if (window.landInfoManager) {
    console.table(window.landInfoManager.getMetrics());
  } else {
    console.log('LandInfoManager not available');
  }
};

window.forceUpdate = function () {
  console.log('🔄 Forcing section update...');
  if (window.landInfoManager) {
    window.landInfoManager.updateSectionVisibility();
  } else {
    console.log('❌ LandInfoManager not available');
  }
};