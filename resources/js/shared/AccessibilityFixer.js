/**
 * Accessibility Fixer - Fixes common accessibility issues
 */
class AccessibilityFixer {
  constructor() {
    this.init();
  }

  init() {
    // Fix duplicate IDs on page load
    this.fixDuplicateIds();

    // Add missing autocomplete attributes
    this.addMissingAutocomplete();

    // Fix missing labels
    this.fixMissingLabels();

    // Fix form accessibility
    this.fixFormAccessibility();
  }

  /**
   * Fix duplicate IDs by making them unique
   */
  fixDuplicateIds() {
    const idCounts = {};
    const elements = document.querySelectorAll('[id]');

    elements.forEach(element => {
      const id = element.id;
      if (idCounts[id]) {
        idCounts[id]++;
        const newId = `${id}_${idCounts[id]}`;
        element.id = newId;

        // Update any labels that reference the old ID
        const labels = document.querySelectorAll(`label[for="${id}"]`);
        labels.forEach(label => {
          if (!label.hasAttribute('data-updated')) {
            label.setAttribute('for', newId);
            label.setAttribute('data-updated', 'true');
          }
        });

        console.warn(`Duplicate ID fixed: ${id} -> ${newId}`);
      } else {
        idCounts[id] = 1;
      }
    });
  }

  /**
   * Add missing autocomplete attributes based on field names
   */
  addMissingAutocomplete() {
    const inputs = document.querySelectorAll('input:not([autocomplete]), select:not([autocomplete]), textarea:not([autocomplete])');

    inputs.forEach(input => {
      const name = input.name || input.id || '';
      const type = input.type || '';

      let autocomplete = 'off';

      // Determine appropriate autocomplete value
      if (name.includes('email') || type === 'email') {
        autocomplete = 'email';
      } else if (name.includes('password') || type === 'password') {
        autocomplete = name.includes('new') || name.includes('confirm') ? 'new-password' : 'current-password';
      } else if (name.includes('name') && !name.includes('file')) {
        if (name.includes('first') || name.includes('given')) {
          autocomplete = 'given-name';
        } else if (name.includes('last') || name.includes('family')) {
          autocomplete = 'family-name';
        } else {
          autocomplete = 'name';
        }
      } else if (name.includes('phone') || name.includes('tel') || type === 'tel') {
        autocomplete = 'tel';
      } else if (name.includes('address')) {
        autocomplete = 'street-address';
      } else if (name.includes('city')) {
        autocomplete = 'address-level2';
      } else if (name.includes('state') || name.includes('prefecture')) {
        autocomplete = 'address-level1';
      } else if (name.includes('zip') || name.includes('postal')) {
        autocomplete = 'postal-code';
      } else if (name.includes('country')) {
        autocomplete = 'country';
      } else if (name.includes('organization') || name.includes('company')) {
        autocomplete = 'organization';
      } else if (name.includes('url') || name.includes('website') || type === 'url') {
        autocomplete = 'url';
      } else if (type === 'date' || type === 'datetime-local' || type === 'time') {
        autocomplete = 'off';
      }

      input.setAttribute('autocomplete', autocomplete);
    });
  }

  /**
   * Fix missing labels by creating them or associating existing ones
   */
  fixMissingLabels() {
    const inputs = document.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
      const id = input.id;
      if (!id) return;

      // Check if there's already a label for this input
      const existingLabel = document.querySelector(`label[for="${id}"]`);
      if (existingLabel) return;

      // Check if the input is nested inside a label
      const parentLabel = input.closest('label');
      if (parentLabel) return;

      // Try to find a label by proximity (previous sibling, parent's previous sibling, etc.)
      let potentialLabel = input.previousElementSibling;
      while (potentialLabel && potentialLabel.tagName !== 'LABEL') {
        potentialLabel = potentialLabel.previousElementSibling;
      }

      if (potentialLabel && potentialLabel.tagName === 'LABEL' && !potentialLabel.getAttribute('for')) {
        potentialLabel.setAttribute('for', id);
        return;
      }

      // Create a label if none found
      const placeholder = input.getAttribute('placeholder');
      const name = input.name || input.id;
      const labelText = placeholder || this.generateLabelFromName(name);

      if (labelText) {
        const label = document.createElement('label');
        label.setAttribute('for', id);
        label.textContent = labelText;
        label.className = 'form-label visually-hidden'; // Hidden but accessible
        input.parentNode.insertBefore(label, input);

        console.warn(`Missing label created for input: ${id}`);
      }
    });
  }

  /**
   * Generate a human-readable label from a field name
   * @param {string} name - The field name to convert
   * @returns {string} Human-readable label
   */
  generateLabelFromName(name) {
    if (!name) return '';

    return name
      .replace(/[\[\]]/g, '') // Remove brackets
      .replace(/_/g, ' ') // Replace underscores with spaces
      .replace(/([a-z])([A-Z])/g, '$1 $2') // Add space before capital letters
      .replace(/\b\w/g, l => l.toUpperCase()) // Capitalize first letter of each word
      .trim();
  }

  /**
   * Fix general form accessibility issues
   */
  fixFormAccessibility() {
    // Add required indicators
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredInputs.forEach(input => {
      if (!input.getAttribute('aria-required')) {
        input.setAttribute('aria-required', 'true');
      }

      const label = document.querySelector(`label[for="${input.id}"]`);
      if (label && !label.querySelector('.required-indicator')) {
        const indicator = document.createElement('span');
        indicator.className = 'required-indicator text-danger ms-1';
        indicator.setAttribute('aria-label', '必須');
        indicator.textContent = '*';
        label.appendChild(indicator);
      }
    });

    // Fix invalid inputs
    const invalidInputs = document.querySelectorAll('.is-invalid');
    invalidInputs.forEach(input => {
      if (!input.getAttribute('aria-invalid')) {
        input.setAttribute('aria-invalid', 'true');
      }

      // Find associated error message
      const errorElement = input.parentNode.querySelector('.invalid-feedback');
      if (errorElement && !errorElement.id) {
        const errorId = `${input.id || input.name}_error`;
        errorElement.id = errorId;
        input.setAttribute('aria-describedby', errorId);
      }
    });

    // Fix form submit buttons
    const submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitButtons.forEach(button => {
      if (!button.getAttribute('aria-label') && !button.textContent.trim()) {
        button.setAttribute('aria-label', '送信');
      }
    });
  }

  /**
   * Fix accessibility issues for dynamically added content
   */
  fixDynamicContent() {
    // Re-run fixes for new content
    this.fixDuplicateIds();
    this.addMissingAutocomplete();
    this.fixMissingLabels();
    this.fixFormAccessibility();
  }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.accessibilityFixer = new AccessibilityFixer();
  });
} else {
  window.accessibilityFixer = new AccessibilityFixer();
}

// Export for manual use
export default AccessibilityFixer;