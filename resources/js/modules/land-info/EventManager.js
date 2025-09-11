/**
 * Event Management for Land Info Form
 * Handles event delegation and cleanup
 */
export class EventManager {
  constructor(errorHandler = null) {
    this.errorHandler = errorHandler;
    this.handlers = new Map();
    this.elementHandlers = new WeakMap(); // Prevents memory leaks
    this.initialized = false;

    // Use AbortController for better event cleanup
    if (typeof AbortController !== 'undefined') {
      this.abortController = new AbortController();
    }

    // Bind cleanup to page unload
    this.setupGlobalCleanup();
  }

  /**
   * Setup global cleanup handlers
   */
  setupGlobalCleanup() {
    const cleanup = () => this.cleanup();

    window.addEventListener('beforeunload', cleanup);
    window.addEventListener('pagehide', cleanup);

    // Store cleanup function for manual removal
    this.globalCleanup = cleanup;
  }

  /**
   * Initialize event listeners
   * @param {Object} handlerConfig - Configuration object with event handlers
   */
  initialize(handlerConfig) {
    if (this.initialized) return;

    this.setupOwnershipTypeHandlers(handlerConfig.onOwnershipTypeChange);
    this.setupCalculationHandlers(handlerConfig.onCalculationFieldChange);
    this.setupFormHandlers(handlerConfig.onFormSubmit);
    this.setupFileHandlers(handlerConfig.onFileChange);

    this.initialized = true;
  }

  /**
   * Setup ownership type change handlers
   * @param {Function} handler 
   */
  setupOwnershipTypeHandlers(handler) {
    // Skip if handler is null (handled elsewhere)
    if (!handler) return;

    const ownershipSelect = document.getElementById('ownership_type');

    if (ownershipSelect) {
      const changeHandler = (e) => handler(e.target.value);

      ownershipSelect.addEventListener('change', changeHandler);
      ownershipSelect.addEventListener('input', changeHandler);

      this.handlers.set('ownership_type_change', changeHandler);
    }

    // Handle radio buttons if present
    const ownershipRadios = document.querySelectorAll('input[name="ownership_type"]');
    if (ownershipRadios.length > 0) {
      const radioHandler = (e) => handler(e.target.value);

      ownershipRadios.forEach(radio => {
        radio.addEventListener('change', radioHandler);
        radio.addEventListener('input', radioHandler);
      });

      this.handlers.set('ownership_type_radio', radioHandler);
    }
  }

  /**
   * Setup calculation field handlers
   * @param {Function} handler 
   */
  setupCalculationHandlers(handler) {
    const calculationFields = [
      'purchase_price', 'site_area_tsubo',
      'contract_start_date', 'contract_end_date'
    ];

    calculationFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        const fieldHandler = (e) => handler(fieldId, e.target.value);

        field.addEventListener('input', fieldHandler);
        field.addEventListener('change', fieldHandler);

        this.handlers.set(`calculation_${fieldId}`, fieldHandler);
      }
    });
  }

  /**
   * Setup form submission handlers
   * @param {Function} handler 
   */
  setupFormHandlers(handler) {
    const form = document.getElementById('landInfoForm');
    if (form) {
      const submitHandler = (e) => {
        if (!handler()) {
          e.preventDefault();
        }
      };

      form.addEventListener('submit', submitHandler);
      this.handlers.set('form_submit', submitHandler);
    }
  }

  /**
   * Setup file upload handlers
   * @param {Function} handler 
   */
  setupFileHandlers(handler) {
    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(input => {
      const fileHandler = (e) => handler(e.target);

      input.addEventListener('change', fileHandler);
      this.handlers.set(`file_${input.id}`, fileHandler);
    });
  }

  /**
   * Cleanup all event listeners
   */
  cleanup() {
    try {
      // Use AbortController for better cleanup if available
      if (this.abortController) {
        this.abortController.abort();
      }

      // Fallback cleanup for older browsers
      this.handlers.forEach((handlerInfo, key) => {
        try {
          // Handle different handler storage formats
          if (typeof handlerInfo === 'function') {
            // Simple function handler - can't remove without element reference
            console.warn(`Cannot cleanup handler ${key} - no element reference`);
          } else if (handlerInfo && typeof handlerInfo === 'object') {
            const { element, eventType, handler } = handlerInfo;
            if (element && element.removeEventListener && handler) {
              element.removeEventListener(eventType, handler);
            }
          }
        } catch (error) {
          console.warn(`Failed to cleanup event listener for ${key}:`, error);
        }
      });

      this.handlers.clear();
      this.initialized = false;

      // Remove global cleanup handlers
      if (this.globalCleanup) {
        window.removeEventListener('beforeunload', this.globalCleanup);
        window.removeEventListener('pagehide', this.globalCleanup);
      }
    } catch (error) {
      console.warn('Error during EventManager cleanup:', error);
    }
  }

  /**
   * Add event listener with proper cleanup tracking
   * @param {HTMLElement} element 
   * @param {string} eventType 
   * @param {Function} handler 
   * @param {string} key 
   */
  addEventListenerWithCleanup(element, eventType, handler, key) {
    if (!element) return;

    // Use AbortController for modern browsers
    const options = this.abortController ? { signal: this.abortController.signal } : {};

    element.addEventListener(eventType, handler, options);

    // Store for manual cleanup fallback
    this.handlers.set(key, { element, eventType, handler });
  }
}