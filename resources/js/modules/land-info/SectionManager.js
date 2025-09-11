/**
 * Section Visibility Management for Land Info Form
 */
import { OWNERSHIP_TYPES, SECTION_IDS, FIELD_GROUPS, CSS_CLASSES, ARIA_ATTRIBUTES, VALIDATION_RULES } from './Constants.js';
import { EventEmitter } from './EventEmitter.js';

export class SectionManager extends EventEmitter {
  constructor(options = {}) {
    super();
    this.OWNERSHIP_TYPES = OWNERSHIP_TYPES;
    this.FIELD_GROUPS = FIELD_GROUPS;

    // Configuration with defaults
    this.config = {
      animationDuration: VALIDATION_RULES.ANIMATION_DURATION || 350,
      highlightDuration: 1000,
      debounceDelay: 16, // One animation frame
      ...options
    };

    // Define SECTION_VISIBILITY_RULES constant for owned/leased sections
    this.SECTION_VISIBILITY_RULES = Object.freeze({
      [SECTION_IDS.OWNED]: [OWNERSHIP_TYPES.OWNED, OWNERSHIP_TYPES.OWNED_RENTAL],
      [SECTION_IDS.LEASED]: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL],
      [SECTION_IDS.MANAGEMENT]: [OWNERSHIP_TYPES.LEASED],
      [SECTION_IDS.OWNER]: [OWNERSHIP_TYPES.LEASED],
      [SECTION_IDS.FILE]: [OWNERSHIP_TYPES.LEASED, OWNERSHIP_TYPES.OWNED_RENTAL]
    });

    // Track active timeouts and animations for cleanup
    this.activeTimeouts = new Set();
    this.activeAnimations = new Set();
    this.pendingUpdates = new Map();
  }

  // Backward compatibility getter
  get SECTION_RULES() {
    return this.SECTION_VISIBILITY_RULES;
  }

  /**
   * Update section visibility based on ownership type
   * @param {string} ownershipType 
   */
  updateSectionVisibility(ownershipType) {
    if (!ownershipType) return;

    const visibility = this.calculateVisibility(ownershipType);
    this.applySectionVisibility(visibility);
    this.clearConditionalFields(ownershipType);
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
   * Apply visibility changes with smooth Bootstrap transitions
   * @param {Object} visibility 
   */
  applySectionVisibility(visibility) {
    Object.entries(visibility).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);

      if (!section) {
        console.warn(`Section element not found: ${sectionId}`);
        return;
      }

      this.toggleSectionVisibility(section, shouldShow);
    });
  }

  /**
   * Toggle individual section visibility with Bootstrap collapse functionality
   * @param {HTMLElement} section 
   * @param {boolean} shouldShow 
   */
  toggleSectionVisibility(section, shouldShow) {
    if (shouldShow) {
      // Use Bootstrap collapse to show section
      this._showSectionWithBootstrapCollapse(section);
    } else {
      // Use Bootstrap collapse to hide section
      this._hideSectionWithBootstrapCollapse(section);
    }
  }

  /**
   * Show section using Bootstrap collapse with smooth transitions
   * @param {HTMLElement} section 
   */
  _showSectionWithBootstrapCollapse(section) {
    // Ensure section has collapse class (should already be there from HTML)
    if (!section.classList.contains(CSS_CLASSES.COLLAPSE)) {
      section.classList.add(CSS_CLASSES.COLLAPSE);
    }

    // Update ARIA attributes for accessibility
    section.setAttribute(ARIA_ATTRIBUTES.HIDDEN, 'false');
    section.setAttribute(ARIA_ATTRIBUTES.EXPANDED, 'true');

    // Remove d-none if present and use Bootstrap's collapse show method
    section.classList.remove('d-none');

    // Use requestAnimationFrame for smooth UI updates
    requestAnimationFrame(() => {
      section.classList.add(CSS_CLASSES.SHOW);

      // Add highlight effect for newly shown sections
      if (CSS_CLASSES.HIGHLIGHT) {
        section.classList.add(CSS_CLASSES.HIGHLIGHT);
        this._scheduleHighlightRemoval(section);
      }
    });
  }

  /**
   * Hide section using Bootstrap collapse with smooth transitions
   * @param {HTMLElement} section 
   */
  _hideSectionWithBootstrapCollapse(section) {
    // Update ARIA attributes immediately for accessibility
    section.setAttribute(ARIA_ATTRIBUTES.HIDDEN, 'true');
    section.setAttribute(ARIA_ATTRIBUTES.EXPANDED, 'false');

    // Remove highlight if present
    if (CSS_CLASSES.HIGHLIGHT) {
      section.classList.remove(CSS_CLASSES.HIGHLIGHT);
    }

    // Use Bootstrap's collapse hide method
    section.classList.remove(CSS_CLASSES.SHOW);

    // Add d-none as fallback for immediate hiding if needed
    // This ensures compatibility with tests that expect d-none
    setTimeout(() => {
      if (!section.classList.contains(CSS_CLASSES.SHOW)) {
        section.classList.add('d-none');
      }
    }, 350); // Bootstrap collapse transition duration
  }

  /**
   * Schedule highlight removal with proper cleanup
   * @param {HTMLElement} section 
   */
  _scheduleHighlightRemoval(section) {
    // Clear any existing timeout
    if (section._highlightTimeout) {
      clearTimeout(section._highlightTimeout);
    }

    section._highlightTimeout = setTimeout(() => {
      section.classList.remove(CSS_CLASSES.HIGHLIGHT);
      delete section._highlightTimeout;
    }, 1000);
  }



  /**
   * Clear fields in conditional sections
   * @param {string} ownershipType 
   */
  clearConditionalFields(ownershipType) {
    const fieldsToClear = this.determineFieldsToClear(ownershipType);
    this.clearFields(fieldsToClear);
  }

  /**
   * Determine which fields should be cleared
   * @param {string} ownershipType 
   * @returns {string[]}
   */
  determineFieldsToClear(ownershipType) {
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
   * Clear multiple fields efficiently
   * @param {string[]} fieldIds 
   */
  clearFields(fieldIds) {
    fieldIds.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        field.value = '';
        field.classList.remove('is-invalid', 'calculated');
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  /**
   * Connect ownership type radio buttons/select to section visibility
   * @param {Function} onOwnershipTypeChange - Callback function when ownership type changes
   */
  connectOwnershipTypeToSectionVisibility(onOwnershipTypeChange) {
    // Handle select element
    const ownershipTypeSelect = document.getElementById('ownership_type');
    if (ownershipTypeSelect) {
      ['change', 'input'].forEach(eventType => {
        ownershipTypeSelect.addEventListener(eventType, (e) => {
          this.handleOwnershipTypeChange(e.target.value);
          if (onOwnershipTypeChange) onOwnershipTypeChange(e.target.value);
        });
      });
    }

    // Handle radio buttons
    const ownershipTypeRadios = document.querySelectorAll('input[name="ownership_type"]');
    ownershipTypeRadios.forEach(radio => {
      ['change', 'input'].forEach(eventType => {
        radio.addEventListener(eventType, (e) => {
          if (e.target.checked) {
            this.handleOwnershipTypeChange(e.target.value);
            if (onOwnershipTypeChange) onOwnershipTypeChange(e.target.value);
          }
        });
      });
    });
  }

  /**
   * Handle ownership type change with comprehensive form management
   * @param {string} ownershipType 
   */
  handleOwnershipTypeChange(ownershipType) {
    // Emit event before changes
    this.emit('ownershipTypeChanging', { oldType: this._currentOwnershipType, newType: ownershipType });

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
    this.emit('ownershipTypeChanged', { ownershipType, visibility: this.calculateVisibility(ownershipType) });
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
            if (input.value) {
              input.value = '';
              input.dispatchEvent(new Event('change', { bubbles: true }));
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
   * Get current ownership type value
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
}