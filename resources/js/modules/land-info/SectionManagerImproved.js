/**
 * Improved Section Visibility Management for Land Info Form
 * Addresses code smells, performance issues, and maintainability concerns
 */
import { OWNERSHIP_TYPES, SECTION_IDS, FIELD_GROUPS, CSS_CLASSES, ARIA_ATTRIBUTES, VALIDATION_RULES } from './Constants.js';
import { EventEmitter } from './EventEmitter.js';

// Configuration constants
const DEFAULT_CONFIG = Object.freeze({
  animationDuration: VALIDATION_RULES.ANIMATION_DURATION || 350,
  highlightDuration: 1000,
  debounceDelay: 16, // One animation frame
  maxRetries: 3,
  fallbackTimeout: 100
});

/**
 * ARIA Management Helper Class
 */
class AriaManager {
  /**
   * Update ARIA attributes for section visibility
   * @param {HTMLElement} section 
   * @param {boolean} isVisible 
   */
  static updateSectionAria(section, isVisible) {
    if (!section) return;

    const updates = {
      [ARIA_ATTRIBUTES.HIDDEN]: isVisible ? 'false' : 'true',
      [ARIA_ATTRIBUTES.EXPANDED]: isVisible ? 'true' : 'false'
    };

    // Batch ARIA updates for better performance
    Object.entries(updates).forEach(([attr, value]) => {
      section.setAttribute(attr, value);
    });

    // Announce changes to screen readers for better accessibility
    if (isVisible && section.id) {
      this._announceToScreenReader(`Section ${section.id} is now visible`);
    }
  }

  /**
   * Announce changes to screen readers
   * @param {string} message 
   */
  static _announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;

    document.body.appendChild(announcement);
    setTimeout(() => {
      if (announcement.parentNode) {
        document.body.removeChild(announcement);
      }
    }, 1000);
  }
}

/**
 * Animation Helper Class
 */
class AnimationHelper {
  constructor(config = DEFAULT_CONFIG) {
    this.config = config;
    this.activeTimeouts = new Set();
    this.activeAnimations = new Set();
  }

  /**
   * Schedule highlight removal with proper cleanup
   * @param {HTMLElement} section 
   * @param {string} highlightClass 
   * @param {number} duration 
   */
  scheduleHighlightRemoval(section, highlightClass, duration = this.config.highlightDuration) {
    // Clear any existing timeout for this section
    this._clearSectionTimeouts(section);

    const timeoutId = setTimeout(() => {
      if (section && section.classList) {
        section.classList.remove(highlightClass);
      }
      this.activeTimeouts.delete(timeoutId);
      if (section) {
        delete section._highlightTimeout;
      }
    }, duration);

    if (section) {
      section._highlightTimeout = timeoutId;
    }
    this.activeTimeouts.add(timeoutId);

    return timeoutId;
  }

  /**
   * Clear timeouts for a specific section
   * @param {HTMLElement} section 
   */
  _clearSectionTimeouts(section) {
    if (section && section._highlightTimeout) {
      clearTimeout(section._highlightTimeout);
      this.activeTimeouts.delete(section._highlightTimeout);
      delete section._highlightTimeout;
    }
  }

  /**
   * Execute animation with requestAnimationFrame
   * @param {Function} callback 
   * @returns {number} Animation ID
   */
  executeAnimation(callback) {
    const animationId = requestAnimationFrame(() => {
      try {
        callback();
      } catch (error) {
        console.error('Animation callback failed:', error);
      } finally {
        this.activeAnimations.delete(animationId);
      }
    });

    this.activeAnimations.add(animationId);
    return animationId;
  }

  /**
   * Clean up all active timeouts and animations
   */
  cleanup() {
    this.activeTimeouts.forEach(id => clearTimeout(id));
    this.activeTimeouts.clear();

    this.activeAnimations.forEach(id => cancelAnimationFrame(id));
    this.activeAnimations.clear();
  }
}

/**
 * Improved Section Manager with better error handling and performance
 */
export class SectionManager extends EventEmitter {
  constructor(options = {}) {
    super();
    this.OWNERSHIP_TYPES = OWNERSHIP_TYPES;
    this.FIELD_GROUPS = FIELD_GROUPS;

    // Configuration with defaults
    this.config = { ...DEFAULT_CONFIG, ...options };

    // Define SECTION_VISIBILITY_RULES constant for owned/leased sections
    this.SECTION_VISIBILITY_RULES = Object.freeze({
      [SECTION_IDS.OWNED]: [OWNERSHIP_TYPES.OWNED, OWNERSHIP_TYPES.OWNED_RENTAL],
      [SECTION_IDS.LEASED]: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL],
      [SECTION_IDS.MANAGEMENT]: [OWNERSHIP_TYPES.LEASED],
      [SECTION_IDS.OWNER]: [OWNERSHIP_TYPES.LEASED],
      [SECTION_IDS.FILE]: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL]
    });

    // Initialize helpers
    this.animationHelper = new AnimationHelper(this.config);
    this.pendingUpdates = new Map();

    // Bind methods to preserve context
    this._performVisibilityUpdate = this._performVisibilityUpdate.bind(this);
  }

  // Backward compatibility getter
  get SECTION_RULES() {
    return this.SECTION_VISIBILITY_RULES;
  }

  /**
   * Update section visibility with debouncing for performance
   * @param {string} ownershipType 
   */
  updateSectionVisibility(ownershipType) {
    if (!ownershipType) return;

    // Debounce rapid ownership type changes
    if (this.pendingUpdates.has('visibility')) {
      cancelAnimationFrame(this.pendingUpdates.get('visibility'));
    }

    const updateId = requestAnimationFrame(() => {
      this._performVisibilityUpdate(ownershipType);
      this.pendingUpdates.delete('visibility');
    });

    this.pendingUpdates.set('visibility', updateId);
  }

  /**
   * Perform the actual visibility update
   * @param {string} ownershipType 
   */
  _performVisibilityUpdate(ownershipType) {
    try {
      const visibility = this.calculateVisibility(ownershipType);
      this.applySectionVisibility(visibility);
      this.clearConditionalFields(ownershipType);
    } catch (error) {
      console.error('Failed to update section visibility:', error);
      // Emit error event for monitoring
      this.emit('error', { type: 'visibility_update', error, ownershipType });
    }
  }

  /**
   * Calculate which sections should be visible
   * @param {string} ownershipType 
   * @returns {Object}
   */
  calculateVisibility(ownershipType) {
    const visibility = {};

    Object.entries(this.SECTION_VISIBILITY_RULES).forEach(([sectionId, allowedTypes]) => {
      visibility[sectionId] = allowedTypes.includes(ownershipType);
    });

    return visibility;
  }

  /**
   * Apply visibility changes with improved error handling
   * @param {Object} visibility 
   */
  applySectionVisibility(visibility) {
    const operations = [];

    // Batch all visibility operations
    Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);

      if (!section) {
        console.warn(`Section element not found: ${sectionId}`);
        return;
      }

      operations.push({ section, shouldShow, sectionId });
    });

    // Execute operations with error boundaries
    operations.forEach(({ section, shouldShow, sectionId }) => {
      try {
        this.toggleSectionVisibility(section, shouldShow);
      } catch (error) {
        console.error(`Failed to toggle visibility for section ${sectionId}:`, error);
        // Fallback to simple show/hide
        this._fallbackToggle(section, shouldShow);
      }
    });
  }

  /**
   * Toggle individual section visibility with improved error handling
   * @param {HTMLElement} section 
   * @param {boolean} shouldShow 
   */
  toggleSectionVisibility(section, shouldShow) {
    if (shouldShow) {
      this._showSectionWithBootstrapCollapse(section);
    } else {
      this._hideSectionWithBootstrapCollapse(section);
    }
  }

  /**
   * Show section using Bootstrap collapse with enhanced error handling
   * @param {HTMLElement} section 
   */
  _showSectionWithBootstrapCollapse(section) {
    try {
      // Validate section element
      if (!section || !section.nodeType) {
        throw new Error(`Invalid section element provided: ${section}`);
      }

      // Ensure section has collapse class with warning
      if (!section.classList.contains(CSS_CLASSES.COLLAPSE)) {
        console.warn(`Section ${section.id} missing collapse class, adding it`);
        section.classList.add(CSS_CLASSES.COLLAPSE);
      }

      // Update ARIA attributes using helper
      AriaManager.updateSectionAria(section, true);

      // Remove d-none if present
      section.classList.remove('d-none');

      // Use animation helper for smooth UI updates
      this.animationHelper.executeAnimation(() => {
        section.classList.add(CSS_CLASSES.SHOW);

        // Add highlight effect for newly shown sections
        if (CSS_CLASSES.HIGHLIGHT) {
          section.classList.add(CSS_CLASSES.HIGHLIGHT);
          this.animationHelper.scheduleHighlightRemoval(
            section,
            CSS_CLASSES.HIGHLIGHT,
            this.config.highlightDuration
          );
        }
      });

    } catch (error) {
      console.error('Failed to show section:', error);
      this._fallbackShow(section);
    }
  }

  /**
   * Hide section using Bootstrap collapse with enhanced error handling
   * @param {HTMLElement} section 
   */
  _hideSectionWithBootstrapCollapse(section) {
    try {
      // Validate section element
      if (!section || !section.nodeType) {
        throw new Error(`Invalid section element provided: ${section}`);
      }

      // Update ARIA attributes using helper
      AriaManager.updateSectionAria(section, false);

      // Remove highlight if present
      if (CSS_CLASSES.HIGHLIGHT) {
        section.classList.remove(CSS_CLASSES.HIGHLIGHT);
      }

      // Use Bootstrap's collapse hide method
      section.classList.remove(CSS_CLASSES.SHOW);

      // Schedule d-none addition with proper cleanup
      const timeoutId = setTimeout(() => {
        if (section && !section.classList.contains(CSS_CLASSES.SHOW)) {
          section.classList.add('d-none');
        }
        this.animationHelper.activeTimeouts.delete(timeoutId);
      }, this.config.animationDuration);

      this.animationHelper.activeTimeouts.add(timeoutId);

    } catch (error) {
      console.error('Failed to hide section:', error);
      this._fallbackHide(section);
    }
  }

  /**
   * Fallback show method for error cases
   * @param {HTMLElement} section 
   */
  _fallbackShow(section) {
    if (!section) return;

    section.style.display = 'block';
    section.setAttribute('aria-hidden', 'false');
    section.setAttribute('aria-expanded', 'true');
    section.classList.remove('d-none');
  }

  /**
   * Fallback hide method for error cases
   * @param {HTMLElement} section 
   */
  _fallbackHide(section) {
    if (!section) return;

    section.style.display = 'none';
    section.setAttribute('aria-hidden', 'true');
    section.setAttribute('aria-expanded', 'false');
    section.classList.add('d-none');
  }

  /**
   * Fallback toggle method for error cases
   * @param {HTMLElement} section 
   * @param {boolean} shouldShow 
   */
  _fallbackToggle(section, shouldShow) {
    if (shouldShow) {
      this._fallbackShow(section);
    } else {
      this._fallbackHide(section);
    }
  }

  /**
   * Clear fields in conditional sections with improved performance
   * @param {string} ownershipType 
   */
  clearConditionalFields(ownershipType) {
    try {
      const fieldsToClear = this.determineFieldsToClear(ownershipType);
      this.clearFields(fieldsToClear);
    } catch (error) {
      console.error('Failed to clear conditional fields:', error);
      this.emit('error', { type: 'field_clearing', error, ownershipType });
    }
  }

  /**
   * Determine which fields should be cleared with validation
   * @param {string} ownershipType 
   * @returns {string[]}
   */
  determineFieldsToClear(ownershipType) {
    if (!ownershipType || !Object.values(OWNERSHIP_TYPES).includes(ownershipType)) {
      console.warn(`Invalid ownership type: ${ownershipType}`);
      return [];
    }

    const fieldsToClear = [];

    // Clear owned fields if not owned or owned_rental
    if (![this.OWNERSHIP_TYPES.OWNED, this.OWNERSHIP_TYPES.OWNED_RENTAL].includes(ownershipType)) {
      fieldsToClear.push(...this.FIELD_GROUPS.OWNED);
    }

    // Clear leased fields if not leased or owned_rental
    if (![this.OWNERSHIP_TYPES.LEASED, this.OWNERSHIP_TYPES.OWNED_RENTAL].includes(ownershipType)) {
      fieldsToClear.push(...this.FIELD_GROUPS.LEASED);
    }

    // Clear management and owner fields if not leased
    if (ownershipType !== this.OWNERSHIP_TYPES.LEASED) {
      fieldsToClear.push(...this.FIELD_GROUPS.MANAGEMENT);
      fieldsToClear.push(...this.FIELD_GROUPS.OWNER);
    }

    return fieldsToClear;
  }

  /**
   * Clear multiple fields efficiently with error handling
   * @param {string[]} fieldIds 
   */
  clearFields(fieldIds) {
    if (!Array.isArray(fieldIds)) {
      console.warn('clearFields expects an array of field IDs');
      return;
    }

    // Batch DOM operations for better performance
    const fieldsToProcess = fieldIds
      .map(fieldId => ({ fieldId, element: document.getElementById(fieldId) }))
      .filter(({ element, fieldId }) => {
        if (!element) {
          console.warn(`Field element not found: ${fieldId}`);
          return false;
        }
        return true;
      });

    // Process fields in batches to avoid blocking the UI
    this._processClearFieldsBatch(fieldsToProcess);
  }

  /**
   * Process field clearing in batches for better performance
   * @param {Array} fieldsToProcess 
   */
  _processClearFieldsBatch(fieldsToProcess) {
    const batchSize = 10;
    let currentIndex = 0;

    const processBatch = () => {
      const endIndex = Math.min(currentIndex + batchSize, fieldsToProcess.length);

      for (let i = currentIndex; i < endIndex; i++) {
        const { element } = fieldsToProcess[i];

        try {
          if (element.value) {
            element.value = '';
            element.classList.remove('is-invalid', 'calculated');
            element.dispatchEvent(new Event('change', { bubbles: true }));
          }
        } catch (error) {
          console.error(`Failed to clear field ${element.id}:`, error);
        }
      }

      currentIndex = endIndex;

      // Continue processing if there are more fields
      if (currentIndex < fieldsToProcess.length) {
        requestAnimationFrame(processBatch);
      }
    };

    // Start processing
    requestAnimationFrame(processBatch);
  }

  /**
   * Connect ownership type radio buttons/select to section visibility
   * @param {Function} onOwnershipTypeChange - Callback function when ownership type changes
   */
  connectOwnershipTypeToSectionVisibility(onOwnershipTypeChange) {
    try {
      // Handle select element
      const ownershipTypeSelect = document.getElementById('ownership_type');
      if (ownershipTypeSelect) {
        const selectHandler = (e) => {
          this.handleOwnershipTypeChange(e.target.value);
          if (onOwnershipTypeChange) onOwnershipTypeChange(e.target.value);
        };

        ['change', 'input'].forEach(eventType => {
          ownershipTypeSelect.addEventListener(eventType, selectHandler);
        });
      }

      // Handle radio buttons
      const ownershipTypeRadios = document.querySelectorAll('input[name="ownership_type"]');
      ownershipTypeRadios.forEach(radio => {
        const radioHandler = (e) => {
          if (e.target.checked) {
            this.handleOwnershipTypeChange(e.target.value);
            if (onOwnershipTypeChange) onOwnershipTypeChange(e.target.value);
          }
        };

        ['change', 'input'].forEach(eventType => {
          radio.addEventListener(eventType, radioHandler);
        });
      });
    } catch (error) {
      console.error('Failed to connect ownership type handlers:', error);
      this.emit('error', { type: 'event_connection', error });
    }
  }

  /**
   * Handle ownership type change with comprehensive form management
   * @param {string} ownershipType 
   */
  handleOwnershipTypeChange(ownershipType) {
    try {
      // Emit event before changes
      this.emit('ownershipTypeChanging', {
        oldType: this._currentOwnershipType,
        newType: ownershipType
      });

      // Update section visibility
      this.updateSectionVisibility(ownershipType);

      // Clear hidden section data
      this.clearHiddenSectionData(ownershipType);

      // Reset validation errors for hidden sections
      this.resetValidationErrorsForHiddenSections(ownershipType);

      // Set disabled attribute on hidden section inputs
      this.setDisabledAttributeOnHiddenInputs(ownershipType);

      // Store current type and emit completion event
      this._currentOwnershipType = ownershipType;
      this.emit('ownershipTypeChanged', {
        ownershipType,
        visibility: this.calculateVisibility(ownershipType)
      });
    } catch (error) {
      console.error('Failed to handle ownership type change:', error);
      this.emit('error', { type: 'ownership_change', error, ownershipType });
    }
  }

  /**
   * Clear hidden section data when sections are hidden
   * @param {string} ownershipType 
   */
  clearHiddenSectionData(ownershipType) {
    const visibility = this.calculateVisibility(ownershipType);

    Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
      if (!shouldShow) {
        const section = document.getElementById(sectionId);
        if (section) {
          // Clear all input values in hidden sections
          const inputs = section.querySelectorAll('input, select, textarea');
          inputs.forEach(input => {
            try {
              if (input.value) {
                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
              }
            } catch (error) {
              console.error(`Failed to clear input ${input.id}:`, error);
            }
          });
        }
      }
    });
  }

  /**
   * Reset validation errors for hidden sections
   * @param {string} ownershipType 
   */
  resetValidationErrorsForHiddenSections(ownershipType) {
    const visibility = this.calculateVisibility(ownershipType);

    Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
      if (!shouldShow) {
        const section = document.getElementById(sectionId);
        if (section) {
          // Remove is-invalid classes from hidden section fields
          const invalidFields = section.querySelectorAll('.is-invalid');
          invalidFields.forEach(field => {
            field.classList.remove('is-invalid');
          });

          // Remove error messages
          const errorMessages = section.querySelectorAll('.invalid-feedback');
          errorMessages.forEach(message => {
            message.remove();
          });
        }
      }
    });
  }

  /**
   * Set disabled attribute on all inputs in hidden sections to prevent submission
   * @param {string} ownershipType 
   */
  setDisabledAttributeOnHiddenInputs(ownershipType) {
    const visibility = this.calculateVisibility(ownershipType);

    Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);
      if (section) {
        const inputs = section.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
          if (shouldShow) {
            // Enable inputs in visible sections
            input.removeAttribute('disabled');
          } else {
            // Disable inputs in hidden sections to prevent submission
            input.setAttribute('disabled', 'disabled');
          }
        });
      }
    });
  }

  /**
   * Ensure hidden fields are never included in form submission payload
   * @param {HTMLFormElement} form 
   * @returns {FormData} - Filtered form data excluding hidden section fields
   */
  getFilteredFormData(form) {
    if (!form) {
      console.warn('No form provided to getFilteredFormData');
      return new FormData();
    }

    const formData = new FormData(form);
    const ownershipType = this.getOwnershipTypeValue();
    const visibility = this.calculateVisibility(ownershipType);

    // Create a new FormData with only visible section fields
    const filteredFormData = new FormData();

    for (const [key, value] of formData.entries()) {
      let shouldInclude = true;

      // Check if this field belongs to a hidden section
      Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
        if (!shouldShow) {
          const section = document.getElementById(sectionId);
          if (section) {
            const fieldInSection = section.querySelector(`[name="${key}"]`);
            if (fieldInSection) {
              shouldInclude = false;
            }
          }
        }
      });

      if (shouldInclude) {
        filteredFormData.append(key, value);
      }
    }

    return filteredFormData;
  }

  /**
   * Get current ownership type value with validation
   * @returns {string}
   */
  getOwnershipTypeValue() {
    // Try select element first
    const select = document.getElementById('ownership_type');
    if (select?.value) {
      return select.value;
    }

    // Fallback to radio buttons
    const checkedRadio = document.querySelector('input[name="ownership_type"]:checked');
    return checkedRadio?.value || '';
  }

  /**
   * Clean up resources and prevent memory leaks
   */
  destroy() {
    try {
      // Clean up animation helper
      if (this.animationHelper) {
        this.animationHelper.cleanup();
      }

      // Cancel pending updates
      this.pendingUpdates.forEach(id => cancelAnimationFrame(id));
      this.pendingUpdates.clear();

      // Remove all event listeners
      this.removeAllListeners();

      console.log('SectionManager cleaned up successfully');
    } catch (error) {
      console.error('Error during SectionManager cleanup:', error);
    }
  }
}