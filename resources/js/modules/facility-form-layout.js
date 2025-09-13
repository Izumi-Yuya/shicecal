/**
 * Facility Form Layout Module
 *
 * Handles collapsible sections, form validation, and responsive features
 * for the standardized facility form layout system.
 * Integrates with existing land-info functionality and provides common features.
 */

export class FacilityFormLayout {
  constructor(options = {}) {
    this.form = document.querySelector('.facility-edit-form') || document.getElementById('landInfoForm');
    this.collapsibleSections = document.querySelectorAll('[data-collapsible="true"]');
    this.debounceTimers = new Map();
    this.validationCache = new Map();

    // Configuration options
    this.options = {
      enableAutoSave: options.enableAutoSave !== false,
      autoSaveInterval: options.autoSaveInterval || 5000,
      enableRealTimeValidation: options.enableRealTimeValidation !== false,
      enableMobileOptimization: options.enableMobileOptimization !== false,
      enableAccessibility: options.enableAccessibility !== false,
      ...options
    };

    this.init();
  }

  init() {
    this.initializeCollapsibleSections();
    this.initializeFormValidation();
    this.initializeResponsiveFeatures();
    this.initializeAccessibility();
    this.initializeCurrencyFormatting();
    this.initializeNumberConversion();
    this.initializeCharacterCount();

    if (this.options.enableAutoSave) {
      this.initializeAutoSave();
    }
  }

  /**
* Initialize collapsible section functionality
*/
  initializeCollapsibleSections() {
    this.collapsibleSections.forEach(section => {
      const header = section.querySelector('.section-header');
      const content = section.querySelector('.card-body');
      const collapseIcon = header.querySelector('.collapse-icon');

      if (!header || !content) {
        return;
      }

      // Add ARIA attributes
      const sectionId = section.getAttribute('aria-labelledby') || `section-${Date.now()}`;
      const contentId = `${sectionId}-content`;

      content.setAttribute('id', contentId);
      header.setAttribute('aria-expanded', !content.classList.contains('collapse'));
      header.setAttribute('aria-controls', contentId);

      header.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleSection(section, content, collapseIcon, header);
      });

      // Keyboard support
      header.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.toggleSection(section, content, collapseIcon, header);
        }
      });
    });
  }

  /**
* Toggle a collapsible section
*/
  toggleSection(section, content, collapseIcon, header) {
    const isCollapsed = content.classList.contains('collapse');

    if (isCollapsed) {
      content.classList.remove('collapse');
      header.setAttribute('aria-expanded', 'true');
      if (collapseIcon) {
        collapseIcon.classList.remove('fa-chevron-down');
        collapseIcon.classList.add('fa-chevron-up');
      }
    } else {
      content.classList.add('collapse');
      header.setAttribute('aria-expanded', 'false');
      if (collapseIcon) {
        collapseIcon.classList.remove('fa-chevron-up');
        collapseIcon.classList.add('fa-chevron-down');
      }
    }

    // Smooth scroll to section if expanding (with fallback for test environments)
    if (isCollapsed && typeof section.scrollIntoView === 'function') {
      setTimeout(() => {
        section.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }, 100);
    }
  }

  /**
* Initialize form validation
*/
  initializeFormValidation() {
    if (!this.form) {
      return;
    }

    // Real-time validation with debouncing for performance
    if (this.options.enableRealTimeValidation) {
      this.form.addEventListener('input', (e) => {
        this.debouncedValidation(e.target);
      });

      this.form.addEventListener('blur', (e) => {
        // Immediate validation on blur for better UX
        this.validateField(e.target);
      }, true);
    }

    // Form submission validation
    this.form.addEventListener('submit', (e) => {
      if (!this.validateForm()) {
        e.preventDefault();
        this.showValidationErrors();
      }
    });

    // File upload validation
    this.form.querySelectorAll('input[type="file"]').forEach(input => {
      input.addEventListener('change', (e) => {
        this.validateFileSize(e.target);
      });
    });
  }

  /**
* Validate a single field
*/
  validateField(field) {
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    let isValid = true;
    let errorMessage = '';

    // Required field validation
    if (field.hasAttribute('required') && !field.value.trim()) {
      isValid = false;
      errorMessage = 'この項目は必須です';
    }

    // Email validation
    if (field.type === 'email' && field.value && !this.isValidEmail(field.value)) {
      isValid = false;
      errorMessage = '有効なメールアドレスを入力してください';
    }

    // URL validation
    if (field.type === 'url' && field.value && !this.isValidUrl(field.value)) {
      isValid = false;
      errorMessage = '有効なURLを入力してください';
    }

    // Phone number validation (Japanese format)
    if (field.name && field.name.includes('phone') && field.value && !this.isValidPhoneNumber(field.value)) {
      isValid = false;
      errorMessage = '有効な電話番号を入力してください（例: 03-1234-5678）';
    }

    // Postal code validation (Japanese format)
    if (field.name && field.name.includes('postal_code') && field.value && !this.isValidPostalCode(field.value)) {
      isValid = false;
      errorMessage = '有効な郵便番号を入力してください（例: 123-4567）';
    }

    // Update field state
    if (isValid) {
      field.classList.remove('is-invalid');
      field.classList.add('is-valid');
    } else {
      field.classList.remove('is-valid');
      field.classList.add('is-invalid');

      if (errorElement) {
        errorElement.textContent = errorMessage;
      }
    }

    return isValid;
  }

  /**
* Validate entire form
*/
  validateForm() {
    if (!this.form) {
      return true;
    }

    const fields = this.form.querySelectorAll('input, select, textarea');
    let isFormValid = true;

    fields.forEach(field => {
      if (!this.validateField(field)) {
        isFormValid = false;
      }
    });

    return isFormValid;
  }

  /**
* Show validation errors
*/
  showValidationErrors() {
    const firstInvalidField = this.form.querySelector('.is-invalid');
    if (firstInvalidField) {
      // Scroll to first error (with fallback for test environments)
      if (typeof firstInvalidField.scrollIntoView === 'function') {
        firstInvalidField.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }

      // Focus the field
      setTimeout(() => {
        firstInvalidField.focus();
      }, 500);
    }
  }

  /**
* Initialize responsive features
*/
  initializeResponsiveFeatures() {
    // Mobile optimization
    if (window.innerWidth <= 768) {
      this.optimizeForMobile();
    }

    // Handle resize events
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        if (window.innerWidth <= 768) {
          this.optimizeForMobile();
        } else {
          this.optimizeForDesktop();
        }
      }, 250);
    });
  }

  /**
* Optimize for mobile devices
*/
  optimizeForMobile() {
    if (!this.options.enableMobileOptimization) {
      return;
    }

    // Ensure touch-friendly interactions (minimum 44px touch targets)
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
      if (btn.offsetHeight < 44) {
        btn.style.minHeight = '44px';
        btn.style.padding = '12px 16px';
      }
    });

    // Optimize form controls
    const formControls = document.querySelectorAll('.form-control, .form-select');
    formControls.forEach(control => {
      if (control.offsetHeight < 44) {
        control.style.minHeight = '44px';
        control.style.padding = '12px 16px';
      }
    });

    // Auto-collapse sections on mobile to save space
    this.collapsibleSections.forEach(section => {
      const content = section.querySelector('.card-body');
      const header = section.querySelector('.section-header');

      if (content && !content.classList.contains('collapse')) {
        // Only auto-collapse if section has many fields
        const fieldCount = content.querySelectorAll('input, select, textarea').length;
        if (fieldCount > 6) {
          content.classList.add('collapse');
          header.setAttribute('aria-expanded', 'false');

          const collapseIcon = header.querySelector('.collapse-icon');
          if (collapseIcon) {
            collapseIcon.classList.remove('fa-chevron-up');
            collapseIcon.classList.add('fa-chevron-down');
          }
        }
      }
    });

    // Optimize input types for mobile keyboards
    this.optimizeInputTypes();

    // Add mobile-specific CSS classes
    document.body.classList.add('mobile-optimized');
  }

  /**
* Optimize input types for mobile keyboards
*/
  optimizeInputTypes() {
    // Set appropriate input types for better mobile keyboards
    document.querySelectorAll('input[name*="phone"], input[name*="fax"]').forEach(input => {
      if (input.type === 'text') {
        input.type = 'tel';
      }
    });

    document.querySelectorAll('input[name*="email"]').forEach(input => {
      if (input.type === 'text') {
        input.type = 'email';
      }
    });

    document.querySelectorAll('input[name*="url"]').forEach(input => {
      if (input.type === 'text') {
        input.type = 'url';
      }
    });

    // Add inputmode attributes for better mobile experience
    document.querySelectorAll('input[name*="postal_code"]').forEach(input => {
      input.setAttribute('inputmode', 'numeric');
      input.setAttribute('pattern', '[0-9]*');
    });

    document.querySelectorAll('.currency-input, input[type="number"]').forEach(input => {
      input.setAttribute('inputmode', 'decimal');
    });
  }

  /**
* Optimize for desktop
*/
  optimizeForDesktop() {
    // Remove mobile-specific styles
    const elements = document.querySelectorAll('.btn, .form-control, .form-select');
    elements.forEach(el => {
      el.style.minHeight = '';
    });
  }

  /**
* Initialize accessibility features
*/
  initializeAccessibility() {
    if (!this.options.enableAccessibility) {
      return;
    }

    // Add skip links for keyboard navigation
    this.addSkipLinks();

    // Enhance focus management
    this.enhanceFocusManagement();

    // Add live regions for dynamic content
    this.addLiveRegions();

    // Add keyboard shortcuts
    this.addKeyboardShortcuts();

    // Enhance form labels and descriptions
    this.enhanceFormAccessibility();

    // Add ARIA landmarks
    this.addAriaLandmarks();

    // Enhance error announcements
    this.enhanceErrorAnnouncements();

    // Initialize roving tabindex for complex widgets
    this.initializeRovingTabindex();
  }

  /**
* Add skip links for keyboard navigation
*/
  addSkipLinks() {
    const sections = document.querySelectorAll('.form-section');
    if (sections.length <= 1) {
      return;
    }

    // Create main skip link to content
    const mainSkipLink = document.createElement('a');
    mainSkipLink.href = '#main-content';
    mainSkipLink.className = 'skip-link sr-only sr-only-focusable';
    mainSkipLink.textContent = 'メインコンテンツにスキップ';
    mainSkipLink.setAttribute('aria-label', 'メインコンテンツにスキップ');
    document.body.insertBefore(mainSkipLink, document.body.firstChild);

    // Create section navigation
    const skipNav = document.createElement('nav');
    skipNav.className = 'skip-navigation sr-only sr-only-focusable';
    skipNav.setAttribute('aria-label', 'セクションナビゲーション');
    skipNav.setAttribute('role', 'navigation');

    const skipTitle = document.createElement('h2');
    skipTitle.textContent = 'セクションナビゲーション';
    skipTitle.className = 'sr-only';
    skipNav.appendChild(skipTitle);

    const skipList = document.createElement('ul');
    skipList.className = 'list-unstyled';
    skipList.setAttribute('role', 'list');

    sections.forEach((section, index) => {
      const header = section.querySelector('.section-header h5 .section-title');
      if (!header) {
        return;
      }

      const sectionId = section.getAttribute('aria-labelledby') || `section-${index}`;
      if (!section.id) {
        section.id = sectionId;
      }

      const listItem = document.createElement('li');
      listItem.setAttribute('role', 'listitem');

      const skipLink = document.createElement('a');
      skipLink.href = `#${sectionId}`;
      skipLink.textContent = `${header.textContent.trim()}にスキップ`;
      skipLink.className = 'skip-link';
      skipLink.setAttribute('aria-label', `${header.textContent.trim()}セクションにスキップ`);

      // Add keyboard event handler
      skipLink.addEventListener('click', (e) => {
        e.preventDefault();
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
          if (typeof targetSection.scrollIntoView === 'function') {
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
          const focusTarget = targetSection.querySelector('.section-header') || targetSection;
          setTimeout(() => {
            focusTarget.focus();
          }, 300);
        }
      });

      listItem.appendChild(skipLink);
      skipList.appendChild(listItem);
    });

    skipNav.appendChild(skipList);
    document.body.insertBefore(skipNav, document.body.children[1]);
  }

  /**
* Enhance focus management
*/
  enhanceFocusManagement() {
    // Store the last focused element before section toggle
    let _lastFocusedElement = null;

    // Enhanced focus management for collapsible sections
    this.collapsibleSections.forEach(section => {
      const header = section.querySelector('.section-header');
      const content = section.querySelector('.card-body');

      if (!header || !content) {
        return;
      }

      // Store focus before toggling
      header.addEventListener('click', () => {
        _lastFocusedElement = document.activeElement;

        setTimeout(() => {
          const isExpanded = header.getAttribute('aria-expanded') === 'true';

          if (isExpanded) {
            // Focus first interactive element when expanding
            const firstInteractive = content.querySelector(
              'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (firstInteractive) {
              firstInteractive.focus();
            }
          } else {
            // Return focus to header when collapsing
            header.focus();
          }
        }, 300);
      });

      // Keyboard navigation within sections
      header.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          _lastFocusedElement = header;
          this.toggleSection(section, content, header.querySelector('.collapse-icon'), header);
        }
      });
    });

    // Focus trap for form validation errors
    if (this.form) {
      this.form.addEventListener('submit', (e) => {
        if (!this.validateForm()) {
          e.preventDefault();
          setTimeout(() => {
            this.focusFirstError();
          }, 100);
        }
      });
    }

    // Manage focus for dynamic content
    this.manageDynamicFocus();
  }

  /**
* Focus the first error field
*/
  focusFirstError() {
    const firstError = this.form?.querySelector('.is-invalid, [aria-invalid="true"]');
    if (firstError) {
      // Expand parent section if collapsed
      const parentSection = firstError.closest('.form-section');
      if (parentSection) {
        const content = parentSection.querySelector('.card-body');
        const header = parentSection.querySelector('.section-header');

        if (content && content.classList.contains('collapse')) {
          this.toggleSection(parentSection, content, header.querySelector('.collapse-icon'), header);

          setTimeout(() => {
            firstError.focus();
            if (typeof firstError.scrollIntoView === 'function') {
              firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }, 300);
        } else {
          firstError.focus();
          if (typeof firstError.scrollIntoView === 'function') {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
      } else {
        firstError.focus();
        if (typeof firstError.scrollIntoView === 'function') {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }

      // Announce error to screen readers
      this.announceToScreenReader(`エラーがあります: ${firstError.getAttribute('aria-label') || firstError.name || 'フィールド'}`);
    }
  }

  /**
* Manage focus for dynamically added content
*/
  manageDynamicFocus() {
    // Observer for dynamically added form elements
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            // Add proper labels and ARIA attributes to new form elements
            const newInputs = node.querySelectorAll?.('input, select, textarea') || [];
            newInputs.forEach(input => this.enhanceFormElement(input));
          }
        });
      });
    });

    if (this.form) {
      observer.observe(this.form, { childList: true, subtree: true });
    }
  }

  /**
* Add live regions for dynamic content
*/
  addLiveRegions() {
    // Create live region for form status (polite)
    if (!document.getElementById('form-status-live-region')) {
      const liveRegion = document.createElement('div');
      liveRegion.setAttribute('aria-live', 'polite');
      liveRegion.setAttribute('aria-atomic', 'true');
      liveRegion.className = 'sr-only';
      liveRegion.id = 'form-status-live-region';
      liveRegion.setAttribute('role', 'status');
      document.body.appendChild(liveRegion);
    }

    // Create live region for urgent announcements (assertive)
    if (!document.getElementById('urgent-live-region')) {
      const urgentRegion = document.createElement('div');
      urgentRegion.setAttribute('aria-live', 'assertive');
      urgentRegion.setAttribute('aria-atomic', 'true');
      urgentRegion.className = 'sr-only';
      urgentRegion.id = 'urgent-live-region';
      urgentRegion.setAttribute('role', 'alert');
      document.body.appendChild(urgentRegion);
    }

    // Create live region for calculation results
    if (!document.getElementById('calculation-live-region')) {
      const calcLiveRegion = document.createElement('div');
      calcLiveRegion.setAttribute('aria-live', 'polite');
      calcLiveRegion.setAttribute('aria-atomic', 'false');
      calcLiveRegion.className = 'sr-only';
      calcLiveRegion.id = 'calculation-live-region';
      calcLiveRegion.setAttribute('role', 'status');
      document.body.appendChild(calcLiveRegion);
    }

    // Announce validation errors
    if (this.form) {
      this.form.addEventListener('submit', (_e) => {
        if (!this.validateForm()) {
          const errorCount = this.form.querySelectorAll('.is-invalid').length;
          this.announceToScreenReader(
            `入力エラーが${errorCount}件あります。エラーを修正してください。`,
            'urgent'
          );
        } else {
          this.announceToScreenReader('フォームを送信しています...', 'polite');
        }
      });
    }
  }

  /**
* Announce message to screen readers
*/
  announceToScreenReader(message, priority = 'polite') {
    const regionId = priority === 'urgent' ? 'urgent-live-region' : 'form-status-live-region';
    const liveRegion = document.getElementById(regionId);

    if (liveRegion) {
      // Clear previous message
      liveRegion.textContent = '';

      // Add new message after a brief delay to ensure it's announced
      setTimeout(() => {
        liveRegion.textContent = message;
      }, 100);

      // Clear message after announcement
      setTimeout(() => {
        liveRegion.textContent = '';
      }, 5000);
    }
  }

  /**
* Add keyboard shortcuts
*/
  addKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Ctrl/Cmd + S to save (prevent default browser save)
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (this.form) {
          const submitButton = this.form.querySelector('button[type="submit"]');
          if (submitButton) {
            submitButton.click();
          }
        }
      }

      // Escape to close expanded sections
      if (e.key === 'Escape') {
        const expandedSections = document.querySelectorAll('[aria-expanded="true"]');
        expandedSections.forEach(header => {
          const section = header.closest('[data-collapsible="true"]');
          if (section) {
            const content = section.querySelector('.card-body');
            const collapseIcon = header.querySelector('.collapse-icon');
            if (content && !content.classList.contains('collapse')) {
              this.toggleSection(section, content, collapseIcon, header);
            }
          }
        });
      }
    });
  }

  /**
* Enhance form accessibility
*/
  enhanceFormAccessibility() {
    // Add proper labels and descriptions
    this.form?.querySelectorAll('input, select, textarea').forEach(field => {
      this.enhanceFormElement(field);
    });

    // Add fieldset and legend for grouped form elements
    this.addFieldsetGrouping();

    // Enhance error message associations
    this.enhanceErrorMessages();
  }

  /**
* Enhance individual form element accessibility
*/
  enhanceFormElement(field) {
    // Ensure all fields have proper labels
    if (!field.getAttribute('aria-label') && !field.getAttribute('aria-labelledby')) {
      const label = this.form?.querySelector(`label[for="${field.id}"]`);
      if (!label && field.id) {
        // Create label if missing
        const newLabel = document.createElement('label');
        newLabel.setAttribute('for', field.id);
        newLabel.textContent = field.getAttribute('placeholder') || field.name || 'フィールド';
        newLabel.className = 'form-label sr-only';
        field.parentNode.insertBefore(newLabel, field);
      }
    }

    // Add required indicator
    if (field.hasAttribute('required')) {
      field.setAttribute('aria-required', 'true');

      // Add visual required indicator if not present
      const label = this.form?.querySelector(`label[for="${field.id}"]`);
      if (label && !label.classList.contains('required')) {
        label.classList.add('required');
      }
    }

    // Add descriptions for complex fields
    const descriptions = [];

    if (field.hasAttribute('pattern')) {
      descriptions.push('入力形式に注意してください。');
    }

    if (field.hasAttribute('maxlength')) {
      descriptions.push(`最大${field.getAttribute('maxlength')}文字まで入力できます。`);
    }

    if (field.hasAttribute('minlength')) {
      descriptions.push(`最低${field.getAttribute('minlength')}文字必要です。`);
    }

    if (field.type === 'email') {
      descriptions.push('有効なメールアドレスを入力してください。');
    }

    if (field.type === 'tel') {
      descriptions.push('電話番号を入力してください（例: 03-1234-5678）。');
    }

    if (field.type === 'url') {
      descriptions.push('有効なURLを入力してください。');
    }

    if (descriptions.length > 0 && !field.getAttribute('aria-describedby')) {
      const descId = `${field.id || field.name}_desc`;
      let descElement = document.getElementById(descId);

      if (!descElement) {
        descElement = document.createElement('div');
        descElement.id = descId;
        descElement.className = 'form-text text-muted sr-only';
        descElement.textContent = descriptions.join(' ');
        field.parentNode.appendChild(descElement);
      }

      field.setAttribute('aria-describedby', descId);
    }

    // Add input mode for better mobile keyboards
    if (field.name && field.name.includes('postal_code')) {
      field.setAttribute('inputmode', 'numeric');
    } else if (field.name && (field.name.includes('phone') || field.name.includes('fax'))) {
      field.setAttribute('inputmode', 'tel');
    } else if (field.type === 'email') {
      field.setAttribute('inputmode', 'email');
    } else if (field.type === 'url') {
      field.setAttribute('inputmode', 'url');
    } else if (field.classList.contains('currency-input') || field.type === 'number') {
      field.setAttribute('inputmode', 'decimal');
    }

    // Add autocomplete attributes for better UX
    this.addAutocompleteAttributes(field);
  }

  /**
* Add autocomplete attributes for better user experience
*/
  addAutocompleteAttributes(field) {
    if (field.name) {
      const autocompleteMap = {
        'email': 'email',
        'phone': 'tel',
        'fax': 'tel',
        'postal_code': 'postal-code',
        'prefecture': 'address-level1',
        'city': 'address-level2',
        'address': 'street-address',
        'building_name': 'address-line2',
        'facility_name': 'organization',
        'contact_person': 'name',
        'url': 'url'
      };

      for (const [key, value] of Object.entries(autocompleteMap)) {
        if (field.name.includes(key)) {
          field.setAttribute('autocomplete', value);
          break;
        }
      }
    }
  }

  /**
* Add fieldset grouping for related form elements
*/
  addFieldsetGrouping() {
    // Group related fields in fieldsets
    const sections = this.form?.querySelectorAll('.form-section');

    sections?.forEach(section => {
      const content = section.querySelector('.card-body');
      const title = section.querySelector('.section-header h5 .section-title');

      if (content && title && !content.querySelector('fieldset')) {
        const fieldset = document.createElement('fieldset');
        const legend = document.createElement('legend');

        legend.textContent = title.textContent.trim();
        legend.className = 'sr-only';

        fieldset.appendChild(legend);

        // Move all form elements into fieldset
        const formElements = content.querySelectorAll('input, select, textarea');
        if (formElements.length > 0) {
          // Wrap content in fieldset
          const wrapper = document.createElement('div');
          wrapper.innerHTML = content.innerHTML;
          content.innerHTML = '';
          fieldset.appendChild(wrapper);
          content.appendChild(fieldset);
        }
      }
    });
  }

  /**
* Enhance error message associations
*/
  enhanceErrorMessages() {
    // Ensure error messages are properly associated with form fields
    this.form?.querySelectorAll('.invalid-feedback').forEach(errorMsg => {
      const field = errorMsg.parentNode.querySelector('input, select, textarea');
      if (field && !errorMsg.id) {
        const errorId = `${field.id || field.name}_error`;
        errorMsg.id = errorId;

        // Associate error with field
        const existingDescribedBy = field.getAttribute('aria-describedby') || '';
        const describedByIds = existingDescribedBy.split(' ').filter(id => id);

        if (!describedByIds.includes(errorId)) {
          describedByIds.push(errorId);
          field.setAttribute('aria-describedby', describedByIds.join(' '));
        }

        field.setAttribute('aria-invalid', 'true');
      }
    });
  }

  /**
* Add ARIA landmarks
*/
  addAriaLandmarks() {
    // Ensure main content has proper landmark
    const mainContent = document.getElementById('main-content');
    if (mainContent && !mainContent.getAttribute('role')) {
      mainContent.setAttribute('role', 'main');
    }

    // Add navigation landmark for breadcrumbs
    const breadcrumbNav = document.querySelector('nav[aria-label*="パンくず"]');
    if (breadcrumbNav && !breadcrumbNav.getAttribute('role')) {
      breadcrumbNav.setAttribute('role', 'navigation');
    }

    // Add complementary landmark for facility info card
    const facilityCard = document.querySelector('.facility-info-card');
    if (facilityCard && !facilityCard.getAttribute('role')) {
      facilityCard.setAttribute('role', 'complementary');
    }
  }

  /**
* Enhance error announcements
*/
  enhanceErrorAnnouncements() {
    // Real-time error announcements
    if (this.form) {
      this.form.addEventListener('input', (e) => {
        const field = e.target;

        // Debounced error checking
        clearTimeout(this.errorAnnouncementTimeout);
        this.errorAnnouncementTimeout = setTimeout(() => {
          if (field.classList.contains('is-invalid')) {
            const errorMsg = field.parentNode.querySelector('.invalid-feedback');
            if (errorMsg) {
              this.announceToScreenReader(`エラー: ${errorMsg.textContent}`, 'polite');
            }
          } else if (field.classList.contains('is-valid')) {
            this.announceToScreenReader(`${field.getAttribute('aria-label') || field.name}の入力が正常です`, 'polite');
          }
        }, 1000);
      });
    }
  }





  /**
* Initialize roving tabindex for complex widgets
*/
  initializeRovingTabindex() {
    // For file upload areas with multiple buttons
    const fileUploadAreas = document.querySelectorAll('.file-upload-area');

    fileUploadAreas.forEach(area => {
      const buttons = area.querySelectorAll('button, input[type="file"]');
      if (buttons.length > 1) {
        this.setupRovingTabindex(buttons);
      }
    });
  }

  /**
* Setup roving tabindex for a group of elements
*/
  setupRovingTabindex(elements) {
    let currentIndex = 0;

    // Set initial tabindex
    elements.forEach((element, index) => {
      element.setAttribute('tabindex', index === 0 ? '0' : '-1');
    });

    // Add keyboard navigation
    elements.forEach((element, index) => {
      element.addEventListener('keydown', (e) => {
        let newIndex = currentIndex;

        switch (e.key) {
          case 'ArrowRight':
          case 'ArrowDown':
            e.preventDefault();
            newIndex = (currentIndex + 1) % elements.length;
            break;
          case 'ArrowLeft':
          case 'ArrowUp':
            e.preventDefault();
            newIndex = (currentIndex - 1 + elements.length) % elements.length;
            break;
          case 'Home':
            e.preventDefault();
            newIndex = 0;
            break;
          case 'End':
            e.preventDefault();
            newIndex = elements.length - 1;
            break;
          default:
            return;
        }

        // Update tabindex and focus
        elements[currentIndex].setAttribute('tabindex', '-1');
        elements[newIndex].setAttribute('tabindex', '0');
        elements[newIndex].focus();
        currentIndex = newIndex;
      });

      element.addEventListener('focus', () => {
        currentIndex = index;
      });
    });
  }

  /**
* Initialize currency formatting (extracted from land-info.js)
*/
  initializeCurrencyFormatting() {
    document.querySelectorAll('.currency-input').forEach(input => {
      input.addEventListener('blur', (e) => {
        this.formatCurrency(e.target);
      });

      input.addEventListener('focus', (e) => {
        this.removeCurrencyFormat(e.target);
      });
    });
  }

  /**
* Initialize number conversion (full-width to half-width)
*/
  initializeNumberConversion() {
    document.querySelectorAll('input[type="number"], .currency-input').forEach(input => {
      input.addEventListener('input', (e) => {
        this.convertToHalfWidth(e.target);
      });
    });

    // Phone number formatting
    document.querySelectorAll('input[name$="_phone"], input[name$="_fax"]').forEach(input => {
      input.addEventListener('blur', (e) => {
        e.target.value = this.validateAndFormatPhoneNumber(e.target.value);
      });
    });

    // Postal code formatting
    document.querySelectorAll('input[name$="_postal_code"]').forEach(input => {
      input.addEventListener('blur', (e) => {
        e.target.value = this.validateAndFormatPostalCode(e.target.value);
      });
    });
  }

  /**
* Initialize character count for textareas
*/
  initializeCharacterCount() {
    const textareas = document.querySelectorAll('textarea[maxlength]');

    textareas.forEach(textarea => {
      const maxLength = parseInt(textarea.getAttribute('maxlength'));
      const counterId = `${textarea.id}_count`;
      let counter = document.getElementById(counterId);

      // Create counter if it doesn't exist
      if (!counter) {
        counter = document.createElement('small');
        counter.id = counterId;
        counter.className = 'form-text text-muted character-count';
        textarea.parentNode.appendChild(counter);
      }

      const updateCount = () => {
        const currentLength = textarea.value.length;
        counter.textContent = `${currentLength}/${maxLength}`;

        // Add warning class when approaching limit
        if (currentLength > maxLength * 0.9) {
          counter.classList.add('text-warning');
        } else {
          counter.classList.remove('text-warning');
        }

        // Add danger class when over limit
        if (currentLength >= maxLength) {
          counter.classList.add('text-danger');
        } else {
          counter.classList.remove('text-danger');
        }
      };

      textarea.addEventListener('input', updateCount);
      updateCount(); // Initialize count
    });
  }

  /**
* Initialize auto-save functionality
*/
  initializeAutoSave() {
    if (!this.form) {
      return;
    }

    let autoSaveTimeout;
    const triggerAutoSave = () => {
      clearTimeout(autoSaveTimeout);
      autoSaveTimeout = setTimeout(() => {
        this.saveDraft();
      }, this.options.autoSaveInterval);
    };

    // Add listeners to all form inputs
    this.form.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('input', triggerAutoSave);
      input.addEventListener('change', triggerAutoSave);
    });

    // Load draft on initialization
    this.loadDraft();

    // Clear draft on successful form submission
    this.form.addEventListener('submit', (_e) => {
      if (this.validateForm()) {
        this.clearDraft();
      }
    });
  }

  /**
* Save form data as draft
*/
  saveDraft() {
    if (!this.form) {
      return;
    }

    const formData = new FormData(this.form);
    const draftData = {};

    for (const [key, value] of formData.entries()) {
      draftData[key] = value;
    }

    // Save to localStorage with form-specific key
    const formId = this.form.id || 'facility-form';
    localStorage.setItem(`${formId}_draft`, JSON.stringify(draftData));

    // Show save indicator
    this.showDraftSaveIndicator();
  }

  /**
* Load draft data
*/
  loadDraft() {
    if (!this.form) {
      return;
    }

    const formId = this.form.id || 'facility-form';
    const draftData = localStorage.getItem(`${formId}_draft`);

    if (!draftData) {
      return;
    }

    try {
      const data = JSON.parse(draftData);

      Object.entries(data).forEach(([key, value]) => {
        const field = this.form.querySelector(`[name="${key}"]`);
        if (field && field.type !== 'file') {
          field.value = value;

          // Trigger change event to update any dependent fields
          field.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      // Show notification that draft was loaded
      this.showDraftLoadedIndicator();

    } catch (error) {
      console.error('Error loading draft:', error);
    }
  }

  /**
* Clear draft data
*/
  clearDraft() {
    const formId = this.form?.id || 'facility-form';
    localStorage.removeItem(`${formId}_draft`);
  }

  /**
* Show draft save indicator
*/
  showDraftSaveIndicator() {
    let indicator = document.getElementById('draft-save-indicator');

    if (!indicator) {
      indicator = document.createElement('div');
      indicator.id = 'draft-save-indicator';
      indicator.className = 'draft-indicator';
      indicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 1050;
        opacity: 0;
        transition: opacity 0.3s ease;
      `;
      document.body.appendChild(indicator);
    }

    indicator.textContent = '下書き保存済み';
    indicator.style.opacity = '1';

    setTimeout(() => {
      indicator.style.opacity = '0';
    }, 2000);
  }

  /**
* Show draft loaded indicator
*/
  showDraftLoadedIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'alert alert-info alert-dismissible fade show';
    indicator.innerHTML = `
      <i class="fas fa-info-circle me-2"></i>
      下書きデータを読み込みました。
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert at top of form
    if (this.form) {
      this.form.insertBefore(indicator, this.form.firstChild);
    }

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      if (indicator.parentNode) {
        indicator.remove();
      }
    }, 5000);
  }

  /**
* Format currency input
*/
  formatCurrency(input) {
    const value = parseInt(input.value.replace(/,/g, '')) || 0;
    if (value > 0) {
      input.value = value.toLocaleString();
    }
  }

  /**
* Remove currency formatting
*/
  removeCurrencyFormat(input) {
    const value = input.value.replace(/,/g, '');
    input.value = value;
  }

  /**
* Convert full-width numbers to half-width
*/
  convertToHalfWidth(input) {
    input.value = input.value.replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
  }

  /**
* Validate and format phone number
*/
  validateAndFormatPhoneNumber(phoneNumber) {
    // Remove all non-digit characters
    const digits = phoneNumber.replace(/\D/g, '');

    // Format as XXX-XXXX-XXXX or XX-XXXX-XXXX
    if (digits.length === 10) {
      return `${digits.slice(0, 2)}-${digits.slice(2, 6)}-${digits.slice(6)}`;
    } else if (digits.length === 11) {
      return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
    }

    return phoneNumber; // Return original if doesn't match expected format
  }

  /**
* Validate and format postal code
*/
  validateAndFormatPostalCode(postalCode) {
    // Remove all non-digit characters
    const digits = postalCode.replace(/\D/g, '');

    // Format as XXX-XXXX
    if (digits.length === 7) {
      return `${digits.slice(0, 3)}-${digits.slice(3)}`;
    }

    return postalCode; // Return original if doesn't match expected format
  }

  /**
* Debounced validation for performance
*/
  debouncedValidation(field, delay = 300) {
    const fieldId = field.id || field.name || 'unknown';

    // Clear existing timer for this field
    if (this.debounceTimers.has(fieldId)) {
      clearTimeout(this.debounceTimers.get(fieldId));
    }

    // Set new timer
    const timer = setTimeout(() => {
      this.validateField(field);
      this.debounceTimers.delete(fieldId);
    }, delay);

    this.debounceTimers.set(fieldId, timer);
  }

  /**
* Validate file size and type
*/
  validateFileSize(input) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    let hasError = false;

    Array.from(input.files).forEach(file => {
      // File size validation
      if (file.size > maxSize) {
        this.showFileError(input, `ファイル "${file.name}" のサイズが大きすぎます。10MB以下のファイルを選択してください。`);
        hasError = true;
      }

      // File type validation (if allowedTypes is specified)
      if (input.hasAttribute('accept') && !allowedTypes.includes(file.type)) {
        this.showFileError(input, `ファイル "${file.name}" は許可されていないファイル形式です。`);
        hasError = true;
      }
    });

    if (hasError) {
      input.value = '';
    } else {
      this.clearFileError(input);
    }
  }

  /**
* Show file validation error
*/
  showFileError(input, message) {
    let errorElement = input.parentNode.querySelector('.file-error');

    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'file-error text-danger small mt-1';
      input.parentNode.appendChild(errorElement);
    }

    errorElement.textContent = message;
    input.classList.add('is-invalid');
  }

  /**
* Clear file validation error
*/
  clearFileError(input) {
    const errorElement = input.parentNode.querySelector('.file-error');
    if (errorElement) {
      errorElement.remove();
    }
    input.classList.remove('is-invalid');
  }

  /**
* Validation helper methods
*/
  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  isValidUrl(url) {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  }

  isValidPhoneNumber(phone) {
    // Japanese phone number format: 03-1234-5678 or 090-1234-5678
    const phoneRegex = /^0\d{1,4}-\d{1,4}-\d{4}$/;
    return phoneRegex.test(phone);
  }

  isValidPostalCode(postalCode) {
    // Japanese postal code format: 123-4567
    const postalRegex = /^\d{3}-\d{4}$/;
    return postalRegex.test(postalCode);
  }
}

/**
 * Initialize facility form layout with options
 */
export function initializeFacilityFormLayout(options = {}) {
  return new FacilityFormLayout(options);
}

/**
 * Common functionality extracted from land-info.js for reuse
 */
export const FacilityFormUtils = {
  formatCurrency: (value) => {
    const num = parseInt(value.toString().replace(/,/g, '')) || 0;
    return num > 0 ? num.toLocaleString() : '';
  },

  removeCurrencyFormat: (value) => {
    return value.toString().replace(/,/g, '');
  },

  convertToHalfWidth: (value) => {
    return value.toString().replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });
  },

  formatPhoneNumber: (phoneNumber) => {
    const digits = phoneNumber.replace(/\D/g, '');
    if (digits.length === 10) {
      return `${digits.slice(0, 2)}-${digits.slice(2, 6)}-${digits.slice(6)}`;
    } else if (digits.length === 11) {
      return `${digits.slice(0, 3)}-${digits.slice(3, 7)}-${digits.slice(7)}`;
    }
    return phoneNumber;
  },

  formatPostalCode: (postalCode) => {
    const digits = postalCode.replace(/\D/g, '');
    if (digits.length === 7) {
      return `${digits.slice(0, 3)}-${digits.slice(3)}`;
    }
    return postalCode;
  },

  validateEmail: (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  validateUrl: (url) => {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  },

  validatePhoneNumber: (phone) => {
    const phoneRegex = /^0\d{1,4}-\d{1,4}-\d{4}$/;
    return phoneRegex.test(phone);
  },

  validatePostalCode: (postalCode) => {
    const postalRegex = /^\d{3}-\d{4}$/;
    return postalRegex.test(postalCode);
  }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // Initialize for facility edit layout
  if (document.querySelector('.facility-edit-layout')) {
    new FacilityFormLayout();
  }

  // Initialize for land info forms (backward compatibility)
  if (document.getElementById('landInfoForm')) {
    new FacilityFormLayout({
      enableAutoSave: true,
      enableRealTimeValidation: true,
      enableMobileOptimization: true,
      enableAccessibility: true
    });
  }
});

// Export main class and utilities
export default FacilityFormLayout;
