/**
 * Refactored Land Information Form Manager
 * Uses modular architecture for better maintainability
 */

import { FormValidator } from './FormValidator.js';
import { Calculator } from './Calculator.js';
import { SectionManager } from './SectionManager.js';
import { EventManager } from './EventManager.js';
import { DOMCache } from './DOMCache.js';
import { ErrorHandler } from './ErrorHandler.js';

export class LandInfoManager {
  constructor(options = {}) {
    // Check if we're on the correct page
    if (!this.isLandInfoPage()) {
      console.log('LandInfoManager: Not on land info edit page, skipping initialization');
      return;
    }

    // Store options
    this.options = { ...this.getDefaultOptions(), ...options };

    // Initialize state
    this.initializeState();

    // Initialize modules
    this.initializeModules();

    // Start initialization process
    this.initialize();
  }

  /**
   * Get default configuration options
   * @returns {Object}
   */
  getDefaultOptions() {
    return {
      enablePerformanceMonitoring: process.env.NODE_ENV === 'development',
      enableErrorReporting: true,
      debounceDelay: 300,
      animationDuration: 300,
      cacheEnabled: true
    };
  }

  /**
   * Initialize application state
   */
  initializeState() {
    this.metrics = {
      startTime: performance.now(),
      calculationCount: 0,
      validationCount: 0
    };

    this.initialized = false;
    this.destroyed = false;
  }

  /**
   * Initialize all modules with dependency injection
   */
  initializeModules() {
    try {
      // Initialize in dependency order
      this.errorHandler = new ErrorHandler();
      this.domCache = new DOMCache();
      this.validator = new FormValidator(this.errorHandler);
      this.calculator = new Calculator(this.domCache, this.errorHandler);
      this.sectionManager = new SectionManager();
      this.eventManager = new EventManager(this.errorHandler);

      // Setup inter-module communication
      this.setupModuleCommunication();
    } catch (error) {
      this.handleInitializationError(error);
    }
  }

  /**
   * Setup communication between modules
   */
  setupModuleCommunication() {
    // Listen to section manager events
    this.sectionManager.on('ownershipTypeChanged', (data) => {
      this.validator.clearValidationErrors();
      this.performCalculations();
    });

    // Listen to calculator events
    if (this.calculator.on) {
      this.calculator.on('calculationCompleted', (data) => {
        this.metrics.calculationCount++;
      });
    }
  }

  /**
   * Check if we're on the land info edit page
   * @returns {boolean}
   */
  isLandInfoPage() {
    const form = document.getElementById('landInfoForm');
    const ownershipSelect = document.getElementById('ownership_type');

    // Ensure we have both the form and key elements
    return !!(form && ownershipSelect &&
      (window.location.pathname.includes('/land-info/') ||
        document.body.classList.contains('land-info-page')));
  }

  /**
   * Initialize the manager with comprehensive error handling
   */
  initialize() {
    try {
      console.log('🚀 Starting LandInfoManager initialization...');

      // Initialize DOM cache with error handling
      this.initializeDOMCache();

      // Initialize event handlers with error handling
      this.initializeEventHandlers();

      // Perform initial setup with error handling
      this.performInitialSetup();

      this.initialized = true;
      console.log('✅ LandInfoManager initialized successfully');

      // Emit initialization complete event
      this.emitInitializationComplete();

    } catch (error) {
      console.error('❌ Error initializing LandInfoManager:', error);
      this.handleInitializationError(error);
    }
  }

  /**
   * Emit initialization complete event for monitoring
   */
  emitInitializationComplete() {
    try {
      if (typeof window.dispatchEvent === 'function') {
        window.dispatchEvent(new CustomEvent('landInfoInitialized', {
          detail: {
            manager: this,
            metrics: this.getMetrics(),
            timestamp: new Date().toISOString()
          }
        }));
      }
    } catch (error) {
      // Don't let event emission errors break initialization
      console.warn('Failed to emit initialization event:', error);
    }
  }

  /**
   * Initialize DOM cache with error handling
   */
  initializeDOMCache() {
    try {
      console.log('🔧 Initializing DOM cache...');
      this.domCache.initialize();
      console.log('✅ DOM cache initialized');
    } catch (error) {
      this.errorHandler.handleError(error, 'LandInfoManager.initializeDOMCache', {
        context: 'dom_cache_initialization'
      });
      // Continue with fallback - direct DOM queries
      console.warn('⚠️ DOM cache initialization failed, using direct DOM queries');
    }
  }

  /**
   * Initialize event handlers with error handling
   */
  initializeEventHandlers() {
    try {
      console.log('🔧 Initializing event handlers...');

      // Initialize real-time validation first
      this.validator.initializeRealTimeValidation();

      // Connect ownership type to section visibility with comprehensive form management
      // This replaces the EventManager's ownership type handling to avoid duplication
      this.sectionManager.connectOwnershipTypeToSectionVisibility((ownershipType) => {
        this.handleOwnershipTypeChange(ownershipType);
      });

      // Initialize other event handlers (excluding ownership type)
      const handlers = {
        onOwnershipTypeChange: null, // Handled by SectionManager
        onCalculationFieldChange: (fieldId, value) => this.handleCalculationFieldChange(fieldId, value),
        onFormSubmit: () => this.handleFormSubmit(),
        onFileChange: (input) => this.handleFileChange(input)
      };

      this.eventManager.initialize(handlers);
      console.log('✅ Event handlers initialized');

    } catch (error) {
      this.errorHandler.handleError(error, 'LandInfoManager.initializeEventHandlers', {
        context: 'event_handler_initialization'
      });
      // Try to initialize basic functionality as fallback
      this.initializeBasicEventHandlers();
    }
  }

  /**
   * Initialize basic event handlers as fallback
   */
  initializeBasicEventHandlers() {
    try {
      console.log('🔄 Initializing basic event handlers as fallback...');

      const ownershipSelect = document.getElementById('ownership_type');
      if (ownershipSelect) {
        ownershipSelect.addEventListener('change', (e) => {
          try {
            this.handleOwnershipTypeChange(e.target.value);
          } catch (error) {
            console.error('Error in basic ownership type handler:', error);
          }
        });
      }

      const form = document.getElementById('landInfoForm');
      if (form) {
        form.addEventListener('submit', (e) => {
          try {
            if (!this.handleFormSubmit()) {
              e.preventDefault();
            }
          } catch (error) {
            console.error('Error in basic form submit handler:', error);
            e.preventDefault();
          }
        });
      }

      console.log('✅ Basic event handlers initialized');

    } catch (error) {
      console.error('❌ Failed to initialize even basic event handlers:', error);
    }
  }

  /**
   * Handle initialization errors gracefully
   * @param {Error} error 
   */
  handleInitializationError(error) {
    // Use centralized error handler
    if (this.errorHandler) {
      this.errorHandler.handleError(error, 'LandInfoManager.initialize', {
        userAgent: navigator.userAgent,
        url: window.location.href,
        timestamp: new Date().toISOString(),
        modules: Object.keys(this).filter(key => this[key] && typeof this[key] === 'object')
      });
    } else {
      console.error('LandInfoManager initialization failed:', error);
    }

    // Provide fallback functionality with error boundary
    try {
      this.initializeBasicFunctionality();
    } catch (fallbackError) {
      console.error('Fallback initialization also failed:', fallbackError);
      this.showCriticalErrorMessage();
    }

    // Emit error event for monitoring with retry capability
    if (typeof window.dispatchEvent === 'function') {
      window.dispatchEvent(new CustomEvent('landInfoError', {
        detail: {
          error,
          context: 'initialization',
          canRetry: true,
          retryAction: () => this.retryInitialization()
        }
      }));
    }
  }

  /**
   * Show critical error message to user
   */
  showCriticalErrorMessage() {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `
      <h4>システムエラー</h4>
      <p>土地情報フォームの初期化に失敗しました。ページを再読み込みしてください。</p>
      <button class="btn btn-primary" onclick="window.location.reload()">
        ページを再読み込み
      </button>
    `;

    const form = document.getElementById('landInfoForm');
    if (form) {
      form.parentNode.insertBefore(errorDiv, form);
    }
  }

  /**
   * Retry initialization with clean state
   */
  retryInitialization() {
    // Clear any existing state
    if (this.destroy) this.destroy();

    // Reinitialize after a short delay
    setTimeout(() => {
      try {
        this.initialize();
      } catch (error) {
        console.error('Retry initialization failed:', error);
      }
    }, 1000);
  }

  /**
   * Initialize basic functionality as fallback
   */
  initializeBasicFunctionality() {
    const ownershipSelect = document.getElementById('ownership_type');
    if (ownershipSelect) {
      ownershipSelect.addEventListener('change', (e) => {
        console.log('Basic fallback: ownership type changed to', e.target.value);
      });
    }
  }

  /**
   * Perform initial setup
   */
  performInitialSetup() {
    // Handle initial state on DOMContentLoaded based on old() or existing values
    this.handleInitialState();

    // Update sections based on current ownership type
    const currentOwnershipType = this.sectionManager.getOwnershipTypeValue();
    if (currentOwnershipType) {
      this.sectionManager.updateSectionVisibility(currentOwnershipType);
    }

    // Synchronize aria-hidden and aria-expanded attributes on page load
    this.synchronizeAriaAttributes();

    // Perform initial calculations
    this.performInitialCalculations();

    // Initialize character counters
    this.initializeCharacterCounters();
  }

  /**
   * Handle initial state on DOMContentLoaded based on old() or existing values
   */
  handleInitialState() {
    try {
      // Get the ownership type from data attribute or current value
      const ownershipSelect = this.domCache.get('ownership_type');
      if (ownershipSelect) {
        const initialValue = ownershipSelect.getAttribute('data-initial-value') || ownershipSelect.value;
        if (initialValue && initialValue !== ownershipSelect.value) {
          ownershipSelect.value = initialValue;
          // Trigger change event to update sections
          ownershipSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }

      // Handle any pre-filled calculation fields
      this.handlePrefilledCalculationFields();

    } catch (error) {
      this.errorHandler.handleError(error, 'LandInfoManager.handleInitialState', {
        context: 'initial_state_handling'
      });
    }
  }

  /**
   * Handle pre-filled calculation fields for initial calculations
   */
  handlePrefilledCalculationFields() {
    // Check if purchase price and site area are pre-filled
    const purchasePriceEl = this.domCache.get('purchase_price');
    const siteAreaTsuboEl = this.domCache.get('site_area_tsubo');

    if (purchasePriceEl?.value && siteAreaTsuboEl?.value) {
      // Perform initial unit price calculation
      setTimeout(() => this.calculateUnitPrice(), 100);
    }

    // Check if contract dates are pre-filled
    const startDateEl = this.domCache.get('contract_start_date');
    const endDateEl = this.domCache.get('contract_end_date');

    if (startDateEl?.value && endDateEl?.value) {
      // Perform initial contract period calculation
      setTimeout(() => this.calculateContractPeriod(), 100);
    }
  }

  /**
   * Synchronize aria-hidden and aria-expanded attributes on page load
   */
  synchronizeAriaAttributes() {
    try {
      const currentOwnershipType = this.sectionManager.getOwnershipTypeValue();
      if (!currentOwnershipType) return;

      const visibility = this.sectionManager.calculateVisibility(currentOwnershipType);

      Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
        const section = document.getElementById(sectionId);
        if (section) {
          // Synchronize aria attributes with actual visibility
          section.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
          section.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');

          // Ensure CSS classes match aria attributes
          if (shouldShow) {
            section.classList.remove('d-none');
            section.classList.add('d-block');
          } else {
            section.classList.remove('d-block');
            section.classList.add('d-none');
          }
        }
      });

    } catch (error) {
      this.errorHandler.handleError(error, 'LandInfoManager.synchronizeAriaAttributes', {
        context: 'aria_synchronization'
      });
    }
  }

  /**
   * Handle ownership type change with comprehensive form management
   * @param {string} ownershipType 
   */
  handleOwnershipTypeChange(ownershipType) {
    console.log('🔄 Ownership type changed to:', ownershipType);

    // Use the comprehensive section management method
    this.sectionManager.handleOwnershipTypeChange(ownershipType);

    // Clear validation errors for hidden sections and re-validate visible fields
    this.validator.handleOwnershipTypeChangeValidation();

    // Recalculate if needed
    this.performCalculations();
  }

  /**
   * Handle calculation field changes with real-time updates
   * @param {string} fieldId 
   * @param {string} value 
   */
  handleCalculationFieldChange(fieldId, value) {
    // Add real-time calculation updates on field changes
    this.calculator.debouncedCalculation(
      fieldId,
      () => this.performSpecificCalculation(fieldId),
      300 // Debounce delay for performance
    );

    // For immediate feedback on certain fields, also trigger instant calculation
    if (['contract_start_date', 'contract_end_date'].includes(fieldId)) {
      // Date fields should update immediately for better UX
      setTimeout(() => this.performSpecificCalculation(fieldId), 50);
    }
  }

  /**
   * Handle form submission with filtered form data
   * @returns {boolean}
   */
  handleFormSubmit() {
    this.metrics.validationCount++;

    const form = this.domCache.get('landInfoForm');
    if (!form) return false;

    // Ensure hidden fields are never included in form submission payload
    const filteredFormData = this.sectionManager.getFilteredFormData(form);

    const validation = this.validator.validateForm();

    if (!validation.isValid) {
      this.showValidationErrors(validation.errors);
      return false;
    }

    // Update form with filtered data before submission
    this.updateFormWithFilteredData(form, filteredFormData);

    this.showSuccessMessage('入力内容に問題ありません。');
    return true;
  }

  /**
   * Update form with filtered data to ensure only visible section data is submitted
   * @param {HTMLFormElement} form 
   * @param {FormData} filteredFormData 
   */
  updateFormWithFilteredData(form, filteredFormData) {
    // This method ensures that when the form is submitted,
    // only the data from visible sections is included

    // Get all form inputs
    const inputs = form.querySelectorAll('input, select, textarea');

    // Check each input against filtered data
    inputs.forEach(input => {
      if (input.name && !filteredFormData.has(input.name)) {
        // This field should not be submitted, ensure it's disabled
        input.setAttribute('disabled', 'disabled');
      }
    });
  }

  /**
   * Handle file upload changes
   * @param {HTMLInputElement} input 
   */
  handleFileChange(input) {
    this.validateFileSize(input);
  }

  /**
   * Perform initial calculations
   */
  performInitialCalculations() {
    this.performCalculations();
  }

  /**
   * Perform all calculations
   */
  performCalculations() {
    this.calculateUnitPrice();
    this.calculateContractPeriod();
  }

  /**
   * Perform specific calculation based on field
   * @param {string} fieldId 
   */
  performSpecificCalculation(fieldId) {
    if (['purchase_price', 'site_area_tsubo'].includes(fieldId)) {
      this.calculateUnitPrice();
    } else if (['contract_start_date', 'contract_end_date'].includes(fieldId)) {
      this.calculateContractPeriod();
    }
  }

  /**
   * Calculate unit price with real-time updates and error handling
   */
  calculateUnitPrice() {
    this.metrics.calculationCount++;

    const purchasePriceEl = this.domCache.get('purchase_price');
    const siteAreaTsuboEl = this.domCache.get('site_area_tsubo');
    const unitPriceDisplayEl = this.domCache.get('unit_price_display');

    if (!purchasePriceEl || !siteAreaTsuboEl || !unitPriceDisplayEl) return;

    const purchasePrice = parseFloat(purchasePriceEl.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(siteAreaTsuboEl.value) || 0;

    const result = this.calculator.calculateUnitPrice(purchasePrice, siteAreaTsubo);

    // Use requestAnimationFrame for smooth UI updates
    requestAnimationFrame(() => {
      if (result && !result.error) {
        unitPriceDisplayEl.value = result.formattedPrice;
        this.addCalculationFeedback(unitPriceDisplayEl);

        // Clear any previous error styling
        this.clearFieldError(unitPriceDisplayEl);

        // Show warning if present
        if (result.warning) {
          this.showCalculationWarning(result.warning, unitPriceDisplayEl);
        }
      } else if (result && result.error) {
        unitPriceDisplayEl.value = '';
        this.showCalculationError(result.errorMessage, unitPriceDisplayEl);
      } else {
        unitPriceDisplayEl.value = '';
        this.clearFieldError(unitPriceDisplayEl);
      }
    });
  }

  /**
   * Calculate contract period with real-time updates and error handling
   */
  calculateContractPeriod() {
    this.metrics.calculationCount++;

    const startDateEl = this.domCache.get('contract_start_date');
    const endDateEl = this.domCache.get('contract_end_date');
    const periodDisplayEl = this.domCache.get('contract_period_display');

    if (!startDateEl || !endDateEl || !periodDisplayEl) return;

    const startDate = startDateEl.value ? new Date(startDateEl.value) : null;
    const endDate = endDateEl.value ? new Date(endDateEl.value) : null;

    const result = this.calculator.calculateContractPeriod(startDate, endDate);

    // Use requestAnimationFrame for smooth UI updates
    requestAnimationFrame(() => {
      if (result && !result.error) {
        periodDisplayEl.value = result.periodText;
        this.addCalculationFeedback(periodDisplayEl);

        // Clear any previous error styling
        this.clearFieldError(endDateEl);
        this.clearFieldError(startDateEl);

      } else if (result && result.error) {
        periodDisplayEl.value = '';
        // Show real-time error for invalid date ranges
        this.showCalculationError(result.errorMessage, endDateEl);
      } else {
        periodDisplayEl.value = '';
        this.clearFieldError(endDateEl);
        this.clearFieldError(startDateEl);
      }
    });
  }

  /**
   * Initialize character counters
   */
  initializeCharacterCounters() {
    const textareas = [
      { id: 'notes', countId: 'notes_count' },
      { id: 'management_company_notes', countId: 'management_company_notes_count' },
      { id: 'owner_notes', countId: 'owner_notes_count' }
    ];

    textareas.forEach(({ id, countId }) => {
      const textarea = this.domCache.get(id);
      const counter = document.getElementById(countId);

      if (textarea && counter) {
        const updateCounter = () => {
          counter.textContent = textarea.value.length;
          const maxLength = parseInt(textarea.getAttribute('maxlength'));

          if (textarea.value.length > maxLength * 0.9) {
            counter.classList.add('text-warning');
          } else {
            counter.classList.remove('text-warning');
          }
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
      }
    });
  }

  /**
   * Validate file size
   * @param {HTMLInputElement} input 
   */
  validateFileSize(input) {
    const maxSize = 10 * 1024 * 1024; // 10MB

    Array.from(input.files).forEach(file => {
      if (file.size > maxSize) {
        alert(`ファイル "${file.name}" のサイズが大きすぎます。10MB以下のファイルを選択してください。`);
        input.value = '';
      }
    });
  }

  /**
   * Add visual feedback for calculations
   * @param {HTMLElement} element 
   */
  addCalculationFeedback(element) {
    element.classList.add('calculated');
    setTimeout(() => {
      element.classList.remove('calculated');
    }, 1000);
  }

  /**
   * Show calculation error with real-time feedback
   * @param {string} message 
   * @param {HTMLElement} element 
   */
  showCalculationError(message, element) {
    // Add error styling
    element.classList.add('is-invalid');

    // Find or create error message element
    let errorElement = element.parentNode.querySelector('.invalid-feedback.calculation-error');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'invalid-feedback calculation-error';
      element.parentNode.appendChild(errorElement);
    }

    errorElement.textContent = message;
    errorElement.style.display = 'block';
  }

  /**
   * Show calculation warning
   * @param {string} message 
   * @param {HTMLElement} element 
   */
  showCalculationWarning(message, element) {
    // Find or create warning message element
    let warningElement = element.parentNode.querySelector('.form-text.calculation-warning');
    if (!warningElement) {
      warningElement = document.createElement('div');
      warningElement.className = 'form-text text-warning calculation-warning';
      element.parentNode.appendChild(warningElement);
    }

    warningElement.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>${message}`;
    warningElement.style.display = 'block';

    // Auto-hide warning after 5 seconds
    setTimeout(() => {
      if (warningElement.parentNode) {
        warningElement.style.display = 'none';
      }
    }, 5000);
  }

  /**
   * Clear field error styling and messages
   * @param {HTMLElement} element 
   */
  clearFieldError(element) {
    if (!element) return;

    element.classList.remove('is-invalid');

    // Remove error messages
    const errorElement = element.parentNode.querySelector('.invalid-feedback.calculation-error');
    if (errorElement) {
      errorElement.style.display = 'none';
    }

    // Remove warning messages
    const warningElement = element.parentNode.querySelector('.form-text.calculation-warning');
    if (warningElement) {
      warningElement.style.display = 'none';
    }
  }

  /**
   * Show validation errors
   * @param {string[]} errors 
   */
  showValidationErrors(errors) {
    // Implementation for showing validation errors
    console.error('Validation errors:', errors);
    // You could show a toast, modal, or inline messages here
  }

  /**
   * Show success message
   * @param {string} message 
   */
  showSuccessMessage(message) {
    console.log('Success:', message);
    // Implementation for showing success message
  }

  /**
   * Get performance metrics
   * @returns {Object}
   */
  getMetrics() {
    return {
      ...this.metrics,
      uptime: performance.now() - this.metrics.startTime,
      cacheStats: this.domCache.getStats(),
      calculatorStats: this.calculator.getCacheStats()
    };
  }

  /**
   * Cleanup resources
   */
  destroy() {
    try {
      if (this.eventManager && typeof this.eventManager.cleanup === 'function') {
        this.eventManager.cleanup();
      }

      if (this.domCache && typeof this.domCache.clear === 'function') {
        this.domCache.clear();
      }

      if (this.calculator && typeof this.calculator.clearCache === 'function') {
        this.calculator.clearCache();
      }

      this.destroyed = true;
      console.log('🧹 LandInfoManager cleaned up');
    } catch (error) {
      console.warn('Error during LandInfoManager cleanup:', error);
    }
  }
}

// Legacy compatibility
window.LandInfoManager = LandInfoManager;