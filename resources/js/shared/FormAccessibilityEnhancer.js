/**
 * Form Accessibility Enhancer
 * Enhances forms with better accessibility features
 */
class FormAccessibilityEnhancer {
  constructor() {
    this.init();
  }

  init() {
    // Enhance all forms on page load
    this.enhanceForms();

    // Set up observers for dynamic content
    this.setupObservers();
  }

  enhanceForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => this.enhanceForm(form));
  }

  enhanceForm(form) {
    // Add form validation enhancements
    this.addLiveValidation(form);

    // Enhance file inputs
    this.enhanceFileInputs(form);

    // Add form submission feedback
    this.addSubmissionFeedback(form);

    // Fix duplicate IDs within form
    this.fixFormDuplicateIds(form);
  }

  addLiveValidation(form) {
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
      // Add real-time validation feedback
      input.addEventListener('blur', () => {
        this.validateField(input);
      });

      input.addEventListener('input', () => {
        // Clear errors on input for better UX
        if (input.classList.contains('is-invalid')) {
          this.clearFieldError(input);
        }
      });
    });
  }

  validateField(input) {
    const isValid = input.checkValidity();

    if (!isValid) {
      this.showFieldError(input, input.validationMessage);
    } else {
      this.clearFieldError(input);
    }
  }

  showFieldError(input, message) {
    input.classList.add('is-invalid');
    input.setAttribute('aria-invalid', 'true');

    let errorElement = input.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'invalid-feedback';
      input.parentNode.appendChild(errorElement);
    }

    errorElement.textContent = message;

    // Associate error with input
    if (!errorElement.id) {
      errorElement.id = `${input.id || input.name}_error_${Date.now()}`;
    }
    input.setAttribute('aria-describedby', errorElement.id);
  }

  clearFieldError(input) {
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');

    const errorElement = input.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
      errorElement.remove();
    }

    input.removeAttribute('aria-describedby');
  }

  enhanceFileInputs(form) {
    const fileInputs = form.querySelectorAll('input[type="file"]');

    fileInputs.forEach(input => {
      // Add file selection feedback
      input.addEventListener('change', (e) => {
        const files = e.target.files;
        let feedback = input.parentNode.querySelector('.file-feedback');

        if (!feedback) {
          feedback = document.createElement('div');
          feedback.className = 'file-feedback form-text';
          input.parentNode.appendChild(feedback);
        }

        if (files.length > 0) {
          const fileNames = Array.from(files).map(f => f.name).join(', ');
          feedback.textContent = `選択されたファイル: ${fileNames}`;
          feedback.setAttribute('aria-live', 'polite');
        } else {
          feedback.textContent = '';
        }
      });
    });
  }

  addSubmissionFeedback(form) {
    form.addEventListener('submit', (e) => {
      // Add loading state
      const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');

        const originalText = submitButton.textContent || submitButton.value;
        if (submitButton.tagName === 'BUTTON') {
          submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>送信中...';
        } else {
          submitButton.value = '送信中...';
        }

        // Re-enable after 5 seconds as fallback
        setTimeout(() => {
          submitButton.disabled = false;
          submitButton.removeAttribute('aria-busy');
          if (submitButton.tagName === 'BUTTON') {
            submitButton.textContent = originalText;
          } else {
            submitButton.value = originalText;
          }
        }, 5000);
      }
    });
  }

  fixFormDuplicateIds(form) {
    const elements = form.querySelectorAll('[id]');
    const seenIds = new Set();

    elements.forEach(element => {
      const id = element.id;
      if (seenIds.has(id)) {
        const newId = `${id}_${Math.random().toString(36).substr(2, 9)}`;
        element.id = newId;

        // Update associated labels
        const labels = form.querySelectorAll(`label[for="${id}"]`);
        labels.forEach(label => {
          if (!seenIds.has(`label_${id}`)) {
            label.setAttribute('for', newId);
            seenIds.add(`label_${id}`);
          }
        });

        console.warn(`Duplicate ID in form fixed: ${id} -> ${newId}`);
      } else {
        seenIds.add(id);
      }
    });
  }

  setupObservers() {
    // Watch for new forms being added to the DOM
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            if (node.tagName === 'FORM') {
              this.enhanceForm(node);
            } else {
              const forms = node.querySelectorAll('form');
              forms.forEach(form => this.enhanceForm(form));
            }
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }
}

// Auto-initialize
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.formAccessibilityEnhancer = new FormAccessibilityEnhancer();
  });
} else {
  window.formAccessibilityEnhancer = new FormAccessibilityEnhancer();
}

export default FormAccessibilityEnhancer;