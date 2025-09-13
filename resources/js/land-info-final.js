/**
 * Land Information Form Manager - Refactored Version
 * Modular, maintainable form management with improved error handling
 * Includes section visibility, calculations, validation, and accessibility features
 */

import {
  OWNERSHIP_TYPES,
  SECTION_VISIBILITY_RULES,
  FIELD_GROUPS,
  VALIDATION_RULES,
  PERFORMANCE_CONFIG,
  ERROR_MESSAGES
} from './config/land-info-config.js';

/**
 * Land Information Form Manager Class
 */
class LandInfoManager {
  constructor() {
    this.isInitialized = false;
    this.debounceTimers = new Map();
    this.eventListeners = new Map();
    this.calculationCache = new Map();

    // Configuration
    this.config = {
      ownershipTypes: OWNERSHIP_TYPES,
      sectionRules: SECTION_VISIBILITY_RULES,
      fieldGroups: FIELD_GROUPS,
      validationRules: VALIDATION_RULES,
      performance: PERFORMANCE_CONFIG,
      errorMessages: ERROR_MESSAGES
    };

    this._init();
  }

  // ========================================
  // INITIALIZATION
  // ========================================

  /**
   * Initialize the Land Info Manager
   * @private
   */
  _init() {
    console.log('🔧 Starting LandInfoManager initialization...');

    if (!this._isLandInfoPage()) {
      console.log('❌ Not on land info page, skipping initialization');
      return;
    }

    try {
      this._setupEventListeners();
      this._setupCalculations();
      this._setupValidation();
      this._setupFileHandling();
      this._setupAccessibility();

      // Initial state update
      this._scheduleInitialUpdate();

      this.isInitialized = true;
      console.log('✅ LandInfoManager initialized successfully');

    } catch (error) {
      console.error('❌ Failed to initialize LandInfoManager:', error);
      throw error;
    }
  }

  /**
   * Check if current page is the land info page
   * @returns {boolean} Whether this is the land info page
   * @private
   */
  _isLandInfoPage() {
    return document.getElementById('landInfoForm') &&
      document.getElementById('ownership_type');
  }

  /**
   * Schedule initial update with proper timing
   * @private
   */
  _scheduleInitialUpdate() {
    console.log('⏰ Setting up initial state update...');
    setTimeout(() => {
      console.log('🔄 Performing initial updates...');
      this.updateSectionVisibility();
      this._performInitialCalculations();
      console.log('✅ Initial updates completed');
    }, 100);
  }

  // ========================================
  // EVENT LISTENERS
  // ========================================

  /**
   * Setup all event listeners
   * @private
   */
  _setupEventListeners() {
    this._setupOwnershipChangeListener();
    this._setupCurrencyFormatting();
    this._setupNumberConversion();
    this._setupPhoneFormatting();
    this._setupPostalCodeFormatting();
  }

  /**
   * Setup ownership type change listener
   * @private
   */
  _setupOwnershipChangeListener() {
    const ownershipSelect = document.getElementById('ownership_type');
    if (!ownershipSelect) return;

    const handler = (e) => {
      console.log('🔄 Ownership type changed to:', e.target.value);
      this._handleOwnershipChange();
    };

    ownershipSelect.addEventListener('change', handler);
    this.eventListeners.set('ownershipChange', handler);
  }

  /**
   * Setup currency input formatting
   * @private
   */
  _setupCurrencyFormatting() {
    document.querySelectorAll('.currency-input').forEach(input => {
      const blurHandler = (e) => this._formatCurrency(e.target);
      const focusHandler = (e) => this._removeCurrencyFormat(e.target);

      input.addEventListener('blur', blurHandler);
      input.addEventListener('focus', focusHandler);
    });
  }

  /**
   * Setup number conversion (full-width to half-width)
   * @private
   */
  _setupNumberConversion() {
    document.querySelectorAll('input[type="number"], .currency-input').forEach(input => {
      const handler = (e) => this._convertToHalfWidth(e.target);
      input.addEventListener('input', handler);
    });
  }

  /**
   * Setup phone number formatting
   * @private
   */
  _setupPhoneFormatting() {
    document.querySelectorAll('input[name$="_phone"], input[name$="_fax"]').forEach(input => {
      const handler = (e) => this._formatPhoneNumber(e.target);
      input.addEventListener('blur', handler);
    });
  }

  /**
   * Setup postal code formatting
   * @private
   */
  _setupPostalCodeFormatting() {
    document.querySelectorAll('input[name$="_postal_code"]').forEach(input => {
      const handler = (e) => this._formatPostalCode(e.target);
      input.addEventListener('blur', handler);
    });
  }

  /**
   * Handle ownership type change
   * @private
   */
  _handleOwnershipChange() {
    this.updateSectionVisibility();
    this._clearConditionalFields();
    this._clearValidationErrors();
  }

  // ========================================
  // SECTION VISIBILITY
  // ========================================

  /**
   * Update section visibility based on ownership type (public method)
   */
  updateSectionVisibility() {
    console.log('🔄 Showing all sections (no conditional logic)');

    // Show all sections regardless of ownership type
    Object.keys(this.config.sectionRules).forEach(sectionId => {
      this._showSection(sectionId);
    });

    console.log('📋 All sections are now visible');
  }

  /**
   * Show a specific section
   * @param {string} sectionId - The section ID to show
   * @private
   */
  _showSection(sectionId) {
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
  }

  /**
   * Clear conditional fields (currently disabled)
   * @private
   */
  _clearConditionalFields() {
    // No longer clear fields - allow all fields to retain their values
    console.log('🔄 Field clearing disabled - all fields retain their values');
  }

  // ========================================
  // CALCULATIONS
  // ========================================

  /**
   * Setup calculation event listeners
   * @private
   */
  _setupCalculations() {
    this._setupUnitPriceCalculation();
    this._setupContractPeriodCalculation();
  }

  /**
   * Setup unit price calculation listeners
   * @private
   */
  _setupUnitPriceCalculation() {
    const purchasePrice = document.getElementById('purchase_price');
    const siteAreaTsubo = document.getElementById('site_area_tsubo');

    if (purchasePrice && siteAreaTsubo) {
      [purchasePrice, siteAreaTsubo].forEach(field => {
        field.addEventListener('input', () => this._debouncedCalculation('unitPrice'));
      });
    }
  }

  /**
   * Setup contract period calculation listeners
   * @private
   */
  _setupContractPeriodCalculation() {
    const startDate = document.getElementById('contract_start_date');
    const endDate = document.getElementById('contract_end_date');

    if (startDate && endDate) {
      [startDate, endDate].forEach(field => {
        field.addEventListener('change', () => this._debouncedCalculation('contractPeriod'));
      });
    }
  }

  /**
   * Debounced calculation execution
   * @param {string} type - Calculation type
   * @private
   */
  _debouncedCalculation(type) {
    if (this.debounceTimers.has(type)) {
      clearTimeout(this.debounceTimers.get(type));
    }

    const timer = setTimeout(() => {
      this._executeCalculation(type);
      this.debounceTimers.delete(type);
    }, this.config.performance.debounceDelay);

    this.debounceTimers.set(type, timer);
  }

  /**
   * Execute specific calculation
   * @param {string} type - Calculation type
   * @private
   */
  _executeCalculation(type) {
    switch (type) {
      case 'unitPrice':
        this._calculateUnitPrice();
        break;
      case 'contractPeriod':
        this._calculateContractPeriod();
        break;
      default:
        console.warn('Unknown calculation type:', type);
    }
  }

  /**
   * Calculate unit price per tsubo
   * @private
   */
  _calculateUnitPrice() {
    const elements = this._getUnitPriceElements();
    if (!elements.isValid) return;

    const { purchasePrice, siteAreaTsubo } = this._parseUnitPriceInputs(elements);

    if (purchasePrice > 0 && siteAreaTsubo > 0) {
      this._performUnitPriceCalculation(elements.unitPriceDisplay, purchasePrice, siteAreaTsubo);
    } else {
      this._clearUnitPriceDisplay(elements.unitPriceDisplay);
    }
  }

  /**
   * Get unit price calculation elements
   * @returns {Object} Elements and validation status
   * @private
   */
  _getUnitPriceElements() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const siteAreaTsuboInput = document.getElementById('site_area_tsubo');
    const unitPriceDisplay = document.getElementById('unit_price_display');

    return {
      purchasePriceInput,
      siteAreaTsuboInput,
      unitPriceDisplay,
      isValid: !!(purchasePriceInput && siteAreaTsuboInput && unitPriceDisplay)
    };
  }

  /**
   * Parse unit price inputs
   * @param {Object} elements - Input elements
   * @returns {Object} Parsed values
   * @private
   */
  _parseUnitPriceInputs(elements) {
    const purchasePrice = parseFloat(elements.purchasePriceInput.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(elements.siteAreaTsuboInput.value) || 0;
    return { purchasePrice, siteAreaTsubo };
  }

  /**
   * Perform unit price calculation with animation
   * @param {HTMLElement} display - Display element
   * @param {number} purchasePrice - Purchase price
   * @param {number} siteAreaTsubo - Site area in tsubo
   * @private
   */
  _performUnitPriceCalculation(display, purchasePrice, siteAreaTsubo) {
    display.classList.add('calculating');

    setTimeout(() => {
      const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
      display.value = unitPrice.toLocaleString();

      display.classList.remove('calculating');
      this._addCalculationFeedback(display);
      this._showCalculationSuccess(display);

      // Validation warning for extremely high unit prices
      if (unitPrice > 10000000) {
        this._showWarning(this.config.errorMessages.calculationWarning.unitPrice);
      }
    }, this.config.performance.animationDelay);
  }

  /**
   * Clear unit price display
   * @param {HTMLElement} display - Display element
   * @private
   */
  _clearUnitPriceDisplay(display) {
    display.value = '';
    display.classList.remove('calculating');
  }

  /**
   * Calculate contract period
   * @private
   */
  _calculateContractPeriod() {
    const elements = this._getContractPeriodElements();
    if (!elements.isValid) return;

    const { startDate, endDate } = this._parseContractDates(elements);

    if (startDate && endDate && endDate > startDate) {
      this._performContractPeriodCalculation(elements.periodDisplay, startDate, endDate);
    } else {
      this._clearContractPeriodDisplay(elements.periodDisplay);
    }
  }

  /**
   * Get contract period calculation elements
   * @returns {Object} Elements and validation status
   * @private
   */
  _getContractPeriodElements() {
    const startDateInput = document.getElementById('contract_start_date');
    const endDateInput = document.getElementById('contract_end_date');
    const periodDisplay = document.getElementById('contract_period_display');

    return {
      startDateInput,
      endDateInput,
      periodDisplay,
      isValid: !!(startDateInput && endDateInput && periodDisplay)
    };
  }

  /**
   * Parse contract dates
   * @param {Object} elements - Input elements
   * @returns {Object} Parsed dates
   * @private
   */
  _parseContractDates(elements) {
    const startDate = new Date(elements.startDateInput.value);
    const endDate = new Date(elements.endDateInput.value);
    return { startDate, endDate };
  }

  /**
   * Perform contract period calculation with animation
   * @param {HTMLElement} display - Display element
   * @param {Date} startDate - Contract start date
   * @param {Date} endDate - Contract end date
   * @private
   */
  _performContractPeriodCalculation(display, startDate, endDate) {
    display.classList.add('calculating');

    setTimeout(() => {
      const totalMonths = this._calculateTotalMonths(startDate, endDate);
      const periodText = this._formatPeriodText(totalMonths);

      display.value = periodText;
      display.classList.remove('calculating');
      this._addCalculationFeedback(display);
      this._showCalculationSuccess(display);

      // Validation warning for extremely long contracts
      if (totalMonths > 600) { // 50 years
        this._showWarning(this.config.errorMessages.calculationWarning.contractPeriod);
      }
    }, this.config.performance.animationDelay);
  }

  /**
   * Calculate total months between dates
   * @param {Date} startDate - Start date
   * @param {Date} endDate - End date
   * @returns {number} Total months
   * @private
   */
  _calculateTotalMonths(startDate, endDate) {
    const years = endDate.getFullYear() - startDate.getFullYear();
    const months = endDate.getMonth() - startDate.getMonth();
    let totalMonths = years * 12 + months;

    if (endDate.getDate() < startDate.getDate()) {
      totalMonths--;
    }

    return totalMonths;
  }

  /**
   * Format period text
   * @param {number} totalMonths - Total months
   * @returns {string} Formatted period text
   * @private
   */
  _formatPeriodText(totalMonths) {
    const displayYears = Math.floor(totalMonths / 12);
    const displayMonths = totalMonths % 12;

    let periodText = '';
    if (displayYears > 0) periodText += `${displayYears}年`;
    if (displayMonths > 0) periodText += `${displayMonths}ヶ月`;

    return periodText || '0ヶ月';
  }

  /**
   * Clear contract period display
   * @param {HTMLElement} display - Display element
   * @private
   */
  _clearContractPeriodDisplay(display) {
    display.value = '';
    display.classList.remove('calculating');
  }

  /**
   * Perform initial calculations (public method)
   */
  _performInitialCalculations() {
    this._calculateUnitPrice();
    this._calculateContractPeriod();
  }

  // ========================================
  // FORM VALIDATION
  // ========================================

  /**
   * Setup form validation
   * @private
   */
  _setupValidation() {
    const form = document.getElementById('landInfoForm');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      if (!this._validateForm()) {
        e.preventDefault();
      }
    });
  }

  /**
   * Validate the entire form
   * @returns {boolean} Whether form is valid
   * @private
   */
  _validateForm() {
    // Validation disabled - always return true to allow form submission
    console.log('🔄 Form validation disabled - allowing all submissions');
    this._clearValidationErrors();
    return true;
  }

  // ========================================
  // FILE HANDLING
  // ========================================

  /**
   * Setup file handling
   * @private
   */
  _setupFileHandling() {
    document.querySelectorAll('input[type="file"]').forEach(input => {
      input.addEventListener('change', (e) => this._validateFileSize(e.target));
    });
  }

  /**
   * Validate file size
   * @param {HTMLInputElement} input - File input element
   * @private
   */
  _validateFileSize(input) {
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

  /**
   * Setup accessibility features
   * @private
   */
  _setupAccessibility() {
    this._setupCharacterCounters();
  }

  /**
   * Setup character counters for textareas
   * @private
   */
  _setupCharacterCounters() {
    const textareas = [
      { id: 'notes', maxLength: 2000 },
      { id: 'management_company_notes', maxLength: 1000 },
      { id: 'owner_notes', maxLength: 1000 }
    ];

    textareas.forEach(({ id, maxLength }) => {
      const textarea = document.getElementById(id);
      if (textarea) {
        textarea.addEventListener('input', () => {
          this._updateCharacterCount(textarea, maxLength);
        });
        // Initial count
        this._updateCharacterCount(textarea, maxLength);
      }
    });
  }

  /**
   * Update character count for textarea
   * @param {HTMLTextAreaElement} textarea - Textarea element
   * @param {number} maxLength - Maximum allowed length
   * @private
   */
  _updateCharacterCount(textarea, maxLength) {
    const countElement = document.getElementById(textarea.id + '_count');
    if (!countElement) return;

    const currentLength = textarea.value.length;
    countElement.textContent = currentLength;

    // Warning when approaching limit
    const warningThreshold = maxLength * 0.9;
    countElement.classList.toggle('text-warning', currentLength > warningThreshold);
  }

  // ========================================
  // UTILITY METHODS
  // ========================================

  /**
   * Get current ownership type (public method)
   * @returns {string} Current ownership type
   */
  getOwnershipType() {
    const select = document.getElementById('ownership_type');
    return select ? select.value : '';
  }

  /**
   * Format currency input
   * @param {HTMLInputElement} input - Currency input element
   * @private
   */
  _formatCurrency(input) {
    const value = parseInt(input.value.replace(/,/g, '')) || 0;
    if (value > 0) {
      input.value = value.toLocaleString();
    }
  }

  /**
   * Remove currency formatting
   * @param {HTMLInputElement} input - Currency input element
   * @private
   */
  _removeCurrencyFormat(input) {
    input.value = input.value.replace(/,/g, '');
  }

  /**
   * Convert full-width numbers to half-width
   * @param {HTMLInputElement} input - Input element
   * @private
   */
  _convertToHalfWidth(input) {
    if (!input || !input.value) return;

    let sanitizedValue = this._convertFullWidthNumbers(input.value);
    sanitizedValue = this._sanitizeNumericInput(input, sanitizedValue);
    sanitizedValue = this._truncateInput(sanitizedValue);

    input.value = sanitizedValue;
  }

  /**
   * Convert full-width numbers to half-width
   * @param {string} value - Input value
   * @returns {string} Converted value
   * @private
   */
  _convertFullWidthNumbers(value) {
    return value.replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
  }

  /**
   * Sanitize numeric input
   * @param {HTMLInputElement} input - Input element
   * @param {string} value - Input value
   * @returns {string} Sanitized value
   * @private
   */
  _sanitizeNumericInput(input, value) {
    if (input.type === 'number' || input.classList.contains('currency-input')) {
      return value.replace(/[^\d.,\-]/g, '');
    }
    return value;
  }

  /**
   * Truncate input to maximum length
   * @param {string} value - Input value
   * @returns {string} Truncated value
   * @private
   */
  _truncateInput(value) {
    return value.length > 50 ? value.substring(0, 50) : value;
  }

  /**
   * Format phone number
   * @param {HTMLInputElement} input - Phone input element
   * @private
   */
  _formatPhoneNumber(input) {
    const value = input.value.replace(/[^\d-]/g, '');
    input.value = value;
  }

  /**
   * Format postal code
   * @param {HTMLInputElement} input - Postal code input element
   * @private
   */
  _formatPostalCode(input) {
    let value = input.value.replace(/[^\d-]/g, '');
    if (value.length === 7 && !value.includes('-')) {
      value = value.substring(0, 3) + '-' + value.substring(3);
    }
    input.value = value;
  }

  /**
   * Clear validation errors
   * @private
   */
  _clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => {
      el.classList.remove('is-invalid');
    });
  }

  /**
   * Add calculation feedback animation
   * @param {HTMLElement} element - Element to animate
   * @private
   */
  _addCalculationFeedback(element) {
    element.classList.add('calculated');
    setTimeout(() => {
      element.classList.remove('calculated');
    }, 1000);
  }

  /**
   * Show calculation success feedback
   * @param {HTMLElement} element - Element to show feedback on
   * @private
   */
  _showCalculationSuccess(element) {
    element.style.borderColor = '#198754';
    setTimeout(() => {
      element.style.borderColor = '#0dcaf0';
    }, 1000);
  }

  /**
   * Show validation errors
   * @param {Array} errors - Array of error messages
   * @private
   */
  _showValidationErrors(errors) {
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

  /**
   * Show warning message
   * @param {string} message - Warning message
   * @private
   */
  _showWarning(message) {
    console.warn('⚠️', message);
    // Could implement toast notification here
  }

  // ========================================
  // DEBUG METHODS
  // ========================================

  /**
   * Get performance and state metrics (public method)
   * @returns {Object} Metrics object
   */
  getMetrics() {
    return {
      isInitialized: this.isInitialized,
      ownershipType: this.getOwnershipType(),
      visibleSections: this._getVisibleSections(),
      debounceTimers: this.debounceTimers.size,
      eventListeners: this.eventListeners.size,
      cacheSize: this.calculationCache.size
    };
  }

  /**
   * Get visible sections status
   * @returns {Object} Section visibility status
   * @private
   */
  _getVisibleSections() {
    const sections = {};
    Object.keys(this.config.sectionRules).forEach(sectionId => {
      const element = document.getElementById(sectionId);
      sections[sectionId] = element ? element.style.display !== 'none' : false;
    });
    return sections;
  }

  /**
   * Cleanup method for removing event listeners and clearing references
   */
  cleanup() {
    try {
      console.log('🧹 Cleaning up LandInfoManager...');

      this._clearTimers();
      this._clearCache();
      this._removeEventListeners();

      this.isInitialized = false;
      console.log('✅ LandInfoManager cleanup completed');
    } catch (error) {
      console.error('❌ Error during LandInfoManager cleanup:', error);
    }
  }

  /**
   * Clear all timers
   * @private
   */
  _clearTimers() {
    this.debounceTimers.forEach(timer => clearTimeout(timer));
    this.debounceTimers.clear();
  }

  /**
   * Clear calculation cache
   * @private
   */
  _clearCache() {
    this.calculationCache.clear();
  }

  /**
   * Remove event listeners
   * @private
   */
  _removeEventListeners() {
    this.eventListeners.clear();
  }
}

// ========================================
// INITIALIZATION & GLOBAL FUNCTIONS
// ========================================

/**
 * Initialize Land Info Manager when DOM is ready
 */
function initializeLandInfoManager() {
  console.log('🚀 DOM ready, checking for land info form...');

  const form = document.getElementById('landInfoForm');
  const ownershipSelect = document.getElementById('ownership_type');

  console.log('Form found:', !!form);
  console.log('Ownership select found:', !!ownershipSelect);

  if (!form || !ownershipSelect) {
    console.log('❌ Not on land info page, skipping initialization');
    return null;
  }

  try {
    console.log('✅ Initializing LandInfoManager...');
    const manager = new LandInfoManager();
    window.landInfoManager = manager;
    return manager;
  } catch (error) {
    console.error('❌ Failed to initialize LandInfoManager:', error);
    console.log('🔄 Falling back to basic functionality...');
    return initBasicFunctionality();
  }
}

/**
 * Fallback basic functionality
 * @returns {Object|null} Basic functionality object or null
 */
function initBasicFunctionality() {
  const ownershipSelect = document.getElementById('ownership_type');
  if (!ownershipSelect) {
    console.log('❌ No ownership select found');
    return null;
  }

  console.log('✅ Basic functionality initialized');

  const basicManager = {
    sectionRules: SECTION_VISIBILITY_RULES,

    updateSections() {
      const ownershipType = ownershipSelect.value;
      console.log('📋 Updating sections for:', ownershipType);

      Object.entries(this.sectionRules).forEach(([sectionId, allowedTypes]) => {
        const section = document.getElementById(sectionId);
        if (section) {
          const shouldShow = allowedTypes.includes(ownershipType);
          section.style.display = shouldShow ? 'block' : 'none';
          section.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
        }
      });
    },

    cleanup() {
      // Basic cleanup - no complex state to clean
      console.log('Basic functionality cleanup completed');
    }
  };

  ownershipSelect.addEventListener('change', () => basicManager.updateSections());
  setTimeout(() => basicManager.updateSections(), 100);

  return basicManager;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializeLandInfoManager);

// ========================================
// DEBUG FUNCTIONS
// ========================================

/**
 * Debug function to test ownership change
 * @param {string} type - Ownership type to test
 */
window.testOwnership = function (type) {
  console.log('🧪 Testing ownership change to:', type);
  const select = document.getElementById('ownership_type');
  if (select) {
    select.value = type;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    // Force update if manager exists
    if (window.landInfoManager && typeof window.landInfoManager.updateSectionVisibility === 'function') {
      window.landInfoManager.updateSectionVisibility();
    }
  } else {
    console.log('❌ Ownership select not found');
  }
};

/**
 * Debug function to show current section states
 */
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

  if (window.landInfoManager && typeof window.landInfoManager._getVisibleSections === 'function') {
    console.log('Manager sections:', window.landInfoManager._getVisibleSections());
  }
};

/**
 * Debug function to show performance metrics
 */
window.getLandInfoMetrics = function () {
  if (window.landInfoManager && typeof window.landInfoManager.getMetrics === 'function') {
    console.table(window.landInfoManager.getMetrics());
  } else {
    console.log('LandInfoManager not available');
  }
};

/**
 * Debug function to force section update
 */
window.forceUpdate = function () {
  console.log('🔄 Forcing section update...');
  if (window.landInfoManager && typeof window.landInfoManager.updateSectionVisibility === 'function') {
    window.landInfoManager.updateSectionVisibility();
  } else {
    console.log('❌ LandInfoManager not available');
  }
};

/**
 * Cleanup function for page unload
 */
window.addEventListener('beforeunload', () => {
  if (window.landInfoManager && typeof window.landInfoManager.cleanup === 'function') {
    window.landInfoManager.cleanup();
  }
});

// Export for ES6 module usage
export { LandInfoManager, initializeLandInfoManager };