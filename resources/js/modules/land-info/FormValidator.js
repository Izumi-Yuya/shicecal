/**
 * Form Validation Module for Land Info
 */
export class FormValidator {
  constructor() {
    // Base validation rules that apply to all ownership types
    this.baseValidationRules = {
      ownership_type: { required: true },
      parking_spaces: { type: 'number', min: 0, max: 9999999999 },
      notes: { type: 'text', max: 1000 }
    };

    // Conditional validation rules based on ownership type
    this.conditionalValidationRules = {
      owned: {
        purchase_price: { required: true, type: 'currency', min: 0, max: 999999999999999 },
        // Require either site_area_tsubo or site_area_sqm for owned properties
        site_area_tsubo: { required_without: 'site_area_sqm', type: 'number', min: 0, max: 99999999.99 },
        site_area_sqm: { required_without: 'site_area_tsubo', type: 'number', min: 0, max: 99999999.99 }
      },
      leased: {
        monthly_rent: { required: true, type: 'currency', min: 0, max: 999999999999999 },
        contract_start_date: { required: true, type: 'date' },
        contract_end_date: { required: true, type: 'date' }
      },
      owned_rental: {
        // Combine owned and leased rules for owned_rental
        purchase_price: { required: true, type: 'currency', min: 0, max: 999999999999999 },
        site_area_tsubo: { required_without: 'site_area_sqm', type: 'number', min: 0, max: 99999999.99 },
        site_area_sqm: { required_without: 'site_area_tsubo', type: 'number', min: 0, max: 99999999.99 },
        monthly_rent: { required: true, type: 'currency', min: 0, max: 999999999999999 },
        contract_start_date: { required: true, type: 'date' },
        contract_end_date: { required: true, type: 'date' }
      }
    };

    // Section visibility rules to determine which fields to validate
    this.sectionVisibilityRules = {
      owned_section: ['owned', 'owned_rental'],
      leased_section: ['leased', 'owned_rental'],
      management_section: ['leased'],
      owner_section: ['leased'],
      file_section: ['leased', 'owned_rental']
    };
  }

  /**
   * Validate entire form with conditional validation based on ownership type
   * @returns {Object} { isValid: boolean, errors: string[] }
   */
  validateForm() {
    const errors = [];
    let isValid = true;

    this.clearValidationErrors();

    // Get current ownership type
    const ownershipType = this.getOwnershipType();
    if (!ownershipType) {
      errors.push('所有形態を選択してください。');
      return { isValid: false, errors };
    }

    // Get validation rules for current ownership type
    const validationRules = this.getValidationRulesForOwnershipType(ownershipType);

    // Validate each field according to conditional rules
    Object.entries(validationRules).forEach(([fieldId, rules]) => {
      // Only validate fields in visible sections
      if (this.isFieldInVisibleSection(fieldId, ownershipType)) {
        const fieldErrors = this.validateField(fieldId, rules);
        if (fieldErrors.length > 0) {
          errors.push(...fieldErrors);
          isValid = false;
        }
      }
    });

    // Custom validation rules (only for visible sections)
    const customErrors = this.validateCustomRules(ownershipType);
    if (customErrors.length > 0) {
      errors.push(...customErrors);
      isValid = false;
    }

    return { isValid, errors };
  }

  /**
   * Validate individual field
   * @param {string} fieldId 
   * @param {Object} rules 
   * @returns {string[]} Array of error messages
   */
  validateField(fieldId, rules) {
    const element = document.getElementById(fieldId);
    const errors = [];

    if (!element) return errors;

    let value = element.value.trim();

    // Sanitize input to prevent XSS
    value = this.sanitizeInput(value);

    // Required validation
    if (rules.required && !value) {
      errors.push(`${this.getFieldLabel(fieldId)}は必須項目です。`);
      element.classList.add('is-invalid');
      return errors;
    }

    // Required without validation
    if (rules.required_without && !value) {
      const otherField = document.getElementById(rules.required_without);
      const otherValue = otherField?.value?.trim();

      if (!otherValue) {
        // Don't add error here - it will be handled by custom validation
        // This prevents duplicate error messages
        return errors;
      }
    }

    if (!value) return errors; // Skip other validations if empty

    // Length validation for security
    if (value.length > 1000) {
      errors.push(`${this.getFieldLabel(fieldId)}は1000文字以下で入力してください。`);
      element.classList.add('is-invalid');
      return errors;
    }

    // Type-specific validation
    switch (rules.type) {
      case 'number':
      case 'currency':
        errors.push(...this.validateNumericField(fieldId, value, rules));
        break;
      case 'email':
        errors.push(...this.validateEmailField(fieldId, value));
        break;
      case 'url':
        errors.push(...this.validateUrlField(fieldId, value));
        break;
      case 'phone':
        errors.push(...this.validatePhoneField(fieldId, value));
        break;
      case 'postal_code':
        errors.push(...this.validatePostalCodeField(fieldId, value));
        break;
    }

    return errors;
  }

  /**
   * Sanitize input to prevent XSS attacks
   * @param {string} input 
   * @returns {string}
   */
  sanitizeInput(input) {
    if (typeof input !== 'string') return '';

    // Use the centralized InputSanitizer for consistency
    if (window.landInfoInputSanitizer) {
      return window.landInfoInputSanitizer.sanitize(input, {
        maxLength: 1000,
        allowHTML: false,
        context: 'form_validation'
      });
    }

    // Fallback sanitization if InputSanitizer is not available
    return this.fallbackSanitize(input);
  }

  /**
   * Fallback sanitization method
   * @param {string} input 
   * @returns {string}
   */
  fallbackSanitize(input) {
    const sanitizationSteps = [
      // Remove dangerous protocols
      (str) => str.replace(/(?:javascript|data|vbscript|file|about):/gi, ''),

      // Remove HTML tags and attributes
      (str) => str.replace(/<[^>]*>/g, ''),

      // Remove event handlers
      (str) => str.replace(/on\w+\s*=\s*["'][^"']*["']/gi, ''),

      // Remove CSS expressions and imports
      (str) => str.replace(/(?:expression|import|@import)\s*\([^)]*\)/gi, ''),

      // Remove encoded entities that could be dangerous
      (str) => str.replace(/&(?:#x?[0-9a-f]+|[a-z]+);/gi, ''),

      // Remove null bytes and control characters
      (str) => str.replace(/[\x00-\x1f\x7f-\x9f]/g, ''),

      // Normalize whitespace
      (str) => str.replace(/\s+/g, ' ').trim()
    ];

    // Apply all sanitization steps
    let sanitized = input;
    for (const step of sanitizationSteps) {
      sanitized = step(sanitized);
    }

    // Final length check for DoS prevention
    return sanitized.length > 10000 ? sanitized.substring(0, 10000) : sanitized;
  }

  /**
   * Validate numeric field with enhanced security
   * @param {string} fieldId 
   * @param {string} value 
   * @param {Object} rules 
   * @returns {string[]}
   */
  validateNumericField(fieldId, value, rules) {
    const errors = [];
    const element = document.getElementById(fieldId);

    // Remove formatting and validate
    const cleanValue = value.replace(/[,\s]/g, '');
    const numValue = parseFloat(cleanValue);

    if (isNaN(numValue)) {
      errors.push(`${this.getFieldLabel(fieldId)}は数値で入力してください。`);
      element?.classList.add('is-invalid');
    } else {
      // Range validation
      if (rules.min !== undefined && numValue < rules.min) {
        errors.push(`${this.getFieldLabel(fieldId)}は${rules.min}以上で入力してください。`);
        element?.classList.add('is-invalid');
      }
      if (rules.max !== undefined && numValue > rules.max) {
        errors.push(`${this.getFieldLabel(fieldId)}は${rules.max.toLocaleString()}以下で入力してください。`);
        element?.classList.add('is-invalid');
      }

      // Check for reasonable values (security)
      if (numValue > Number.MAX_SAFE_INTEGER) {
        errors.push(`${this.getFieldLabel(fieldId)}の値が大きすぎます。`);
        element?.classList.add('is-invalid');
      }
    }

    return errors;
  }

  /**
   * Validate email field
   * @param {string} fieldId 
   * @param {string} value 
   * @returns {string[]}
   */
  validateEmailField(fieldId, value) {
    const errors = [];
    const element = document.getElementById(fieldId);

    if (!this.isValidEmail(value)) {
      errors.push(`${this.getFieldLabel(fieldId)}は正しいメールアドレス形式で入力してください。`);
      element?.classList.add('is-invalid');
    }

    return errors;
  }

  /**
   * Validate URL field
   * @param {string} fieldId 
   * @param {string} value 
   * @returns {string[]}
   */
  validateUrlField(fieldId, value) {
    const errors = [];
    const element = document.getElementById(fieldId);

    if (!this.isValidUrl(value)) {
      errors.push(`${this.getFieldLabel(fieldId)}は正しいURL形式で入力してください。`);
      element?.classList.add('is-invalid');
    }

    return errors;
  }

  /**
   * Validate phone field
   * @param {string} fieldId 
   * @param {string} value 
   * @returns {string[]}
   */
  validatePhoneField(fieldId, value) {
    const errors = [];
    const element = document.getElementById(fieldId);

    const phoneRegex = /^\d{2,4}-\d{2,4}-\d{4}$/;
    if (!phoneRegex.test(value)) {
      errors.push(`${this.getFieldLabel(fieldId)}は正しい電話番号形式で入力してください。（例: 03-1234-5678）`);
      element?.classList.add('is-invalid');
    }

    return errors;
  }

  /**
   * Validate postal code field
   * @param {string} fieldId 
   * @param {string} value 
   * @returns {string[]}
   */
  validatePostalCodeField(fieldId, value) {
    const errors = [];
    const element = document.getElementById(fieldId);

    const postalRegex = /^\d{3}-\d{4}$/;
    if (!postalRegex.test(value)) {
      errors.push(`${this.getFieldLabel(fieldId)}は正しい郵便番号形式で入力してください。（例: 123-4567）`);
      element?.classList.add('is-invalid');
    }

    return errors;
  }

  /**
   * Get current ownership type from form
   * @returns {string|null}
   */
  getOwnershipType() {
    // Try select element first
    const select = document.getElementById('ownership_type');
    if (select?.value) {
      return select.value;
    }

    // Fallback to radio buttons
    const checkedRadio = document.querySelector('input[name="ownership_type"]:checked');
    return checkedRadio?.value || null;
  }

  /**
   * Get validation rules for specific ownership type
   * @param {string} ownershipType 
   * @returns {Object}
   */
  getValidationRulesForOwnershipType(ownershipType) {
    const rules = { ...this.baseValidationRules };

    // Add conditional rules based on ownership type
    if (this.conditionalValidationRules[ownershipType]) {
      Object.assign(rules, this.conditionalValidationRules[ownershipType]);
    }

    return rules;
  }

  /**
   * Check if field is in a visible section for the given ownership type
   * @param {string} fieldId 
   * @param {string} ownershipType 
   * @returns {boolean}
   */
  isFieldInVisibleSection(fieldId, ownershipType) {
    // Base fields are always visible
    if (this.baseValidationRules[fieldId]) {
      return true;
    }

    // Check if field is in any visible section
    for (const [sectionId, allowedTypes] of Object.entries(this.sectionVisibilityRules)) {
      if (allowedTypes.includes(ownershipType)) {
        const section = document.getElementById(sectionId);
        if (section) {
          const field = section.querySelector(`#${fieldId}, [name="${fieldId}"]`);
          if (field) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Custom validation rules for visible sections only
   * @param {string} ownershipType 
   * @returns {string[]} Array of error messages
   */
  validateCustomRules(ownershipType) {
    const errors = [];

    // Contract date validation (only for leased properties)
    if (['leased', 'owned_rental'].includes(ownershipType)) {
      const startDate = document.getElementById('contract_start_date');
      const endDate = document.getElementById('contract_end_date');

      if (startDate?.value && endDate?.value) {
        if (new Date(startDate.value) >= new Date(endDate.value)) {
          errors.push('契約終了日は契約開始日より後の日付を入力してください。');
          endDate.classList.add('is-invalid');
        }
      }
    }

    // Required without validation for site area fields (only for owned properties)
    if (['owned', 'owned_rental'].includes(ownershipType)) {
      const siteAreaTsubo = document.getElementById('site_area_tsubo');
      const siteAreaSqm = document.getElementById('site_area_sqm');

      if (siteAreaTsubo && siteAreaSqm) {
        const tsuboValue = siteAreaTsubo.value?.trim();
        const sqmValue = siteAreaSqm.value?.trim();

        if (!tsuboValue && !sqmValue) {
          errors.push('敷地面積（坪数）または敷地面積（㎡）のいずれかは必須項目です。');
          siteAreaTsubo.classList.add('is-invalid');
          siteAreaSqm.classList.add('is-invalid');
        }
      }
    }

    return errors;
  }

  /**
   * Clear all validation errors
   */
  clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(element => {
      element.classList.remove('is-invalid');
    });

    // Also remove error messages
    document.querySelectorAll('.invalid-feedback').forEach(element => {
      element.remove();
    });
  }

  /**
   * Clear validation errors for hidden sections
   * @param {string} ownershipType 
   */
  clearValidationErrorsForHiddenSections(ownershipType) {
    // Get sections that should be hidden
    Object.entries(this.sectionVisibilityRules).forEach(([sectionId, allowedTypes]) => {
      if (!allowedTypes.includes(ownershipType)) {
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
   * Get user-friendly field label
   * @param {string} fieldId 
   * @returns {string}
   */
  getFieldLabel(fieldId) {
    const labels = {
      ownership_type: '所有形態',
      parking_spaces: '敷地内駐車場台数',
      site_area_sqm: '敷地面積（㎡）',
      site_area_tsubo: '敷地面積（坪数）',
      purchase_price: '購入金額',
      monthly_rent: '家賃',
      contract_start_date: '契約開始日',
      contract_end_date: '契約終了日',
      auto_renewal: '自動更新の有無',
      notes: '備考',
      management_company_name: '管理会社名',
      management_company_postal_code: '管理会社郵便番号',
      management_company_address: '管理会社住所',
      management_company_building: '管理会社建物名',
      management_company_phone: '管理会社電話番号',
      management_company_fax: '管理会社FAX番号',
      management_company_email: '管理会社メールアドレス',
      management_company_url: '管理会社URL',
      management_company_notes: '管理会社備考',
      owner_name: 'オーナー名',
      owner_postal_code: 'オーナー郵便番号',
      owner_address: 'オーナー住所',
      owner_building: 'オーナー建物名',
      owner_phone: 'オーナー電話番号',
      owner_fax: 'オーナーFAX番号',
      owner_email: 'オーナーメールアドレス',
      owner_url: 'オーナーURL',
      owner_notes: 'オーナー備考'
    };
    return labels[fieldId] || fieldId;
  }

  /**
   * Validate email format
   * @param {string} email 
   * @returns {boolean}
   */
  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /**
   * Validate URL format
   * @param {string} url 
   * @returns {boolean}
   */
  isValidUrl(url) {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  }

  /**
   * Initialize real-time validation for all form fields
   */
  initializeRealTimeValidation() {
    const form = document.querySelector('form');
    if (!form) return;

    // Get all input, select, and textarea elements
    const fields = form.querySelectorAll('input, select, textarea');

    fields.forEach(field => {
      this.attachRealTimeValidation(field);
    });

    // Special handling for ownership type changes
    this.attachOwnershipTypeValidation();
  }

  /**
   * Attach real-time validation to a specific field
   * @param {HTMLElement} field 
   */
  attachRealTimeValidation(field) {
    const fieldId = field.id || field.name;
    if (!fieldId) return;

    // Debounce validation to avoid excessive calls
    let validationTimeout;

    const validateField = () => {
      clearTimeout(validationTimeout);
      validationTimeout = setTimeout(() => {
        this.validateFieldRealTime(fieldId);
      }, 300);
    };

    // Attach to multiple events for comprehensive coverage
    ['input', 'blur', 'change'].forEach(eventType => {
      field.addEventListener(eventType, validateField);
    });
  }

  /**
   * Attach special validation for ownership type changes
   */
  attachOwnershipTypeValidation() {
    // Handle select element
    const ownershipTypeSelect = document.getElementById('ownership_type');
    if (ownershipTypeSelect) {
      ownershipTypeSelect.addEventListener('change', () => {
        this.handleOwnershipTypeChangeValidation();
      });
    }

    // Handle radio buttons
    const ownershipTypeRadios = document.querySelectorAll('input[name="ownership_type"]');
    ownershipTypeRadios.forEach(radio => {
      radio.addEventListener('change', () => {
        if (radio.checked) {
          this.handleOwnershipTypeChangeValidation();
        }
      });
    });
  }

  /**
   * Handle validation when ownership type changes
   */
  handleOwnershipTypeChangeValidation() {
    const ownershipType = this.getOwnershipType();
    if (ownershipType) {
      // Clear validation errors for hidden sections
      this.clearValidationErrorsForHiddenSections(ownershipType);

      // Re-validate visible fields
      setTimeout(() => {
        this.validateVisibleFields(ownershipType);
      }, 100);
    }
  }

  /**
   * Validate all visible fields for the current ownership type
   * @param {string} ownershipType 
   */
  validateVisibleFields(ownershipType) {
    const validationRules = this.getValidationRulesForOwnershipType(ownershipType);

    Object.keys(validationRules).forEach(fieldId => {
      if (this.isFieldInVisibleSection(fieldId, ownershipType)) {
        this.validateFieldRealTime(fieldId);
      }
    });
  }

  /**
   * Validate a single field in real-time
   * @param {string} fieldId 
   */
  validateFieldRealTime(fieldId) {
    const ownershipType = this.getOwnershipType();
    if (!ownershipType) return;

    // Only validate if field is in visible section
    if (!this.isFieldInVisibleSection(fieldId, ownershipType)) {
      return;
    }

    const validationRules = this.getValidationRulesForOwnershipType(ownershipType);
    const rules = validationRules[fieldId];

    if (!rules) return;

    // Clear previous validation state
    const element = document.getElementById(fieldId);
    if (!element) return;

    element.classList.remove('is-invalid', 'is-valid');
    this.removeFieldErrorMessage(element);

    // Validate the field
    const fieldErrors = this.validateField(fieldId, rules);

    if (fieldErrors.length > 0) {
      element.classList.add('is-invalid');
      this.displayFieldErrorMessage(element, fieldErrors[0]);
    } else if (element.value.trim()) {
      // Only show valid state if field has content
      element.classList.add('is-valid');
    }

    // Handle special cases for required_without validation
    this.handleRequiredWithoutValidation(fieldId, ownershipType);
  }

  /**
   * Handle required_without validation for site area fields
   * @param {string} fieldId 
   * @param {string} ownershipType 
   */
  handleRequiredWithoutValidation(fieldId, ownershipType) {
    if (!['owned', 'owned_rental'].includes(ownershipType)) return;

    if (fieldId === 'site_area_tsubo' || fieldId === 'site_area_sqm') {
      const tsuboField = document.getElementById('site_area_tsubo');
      const sqmField = document.getElementById('site_area_sqm');

      if (tsuboField && sqmField) {
        const tsuboValue = tsuboField.value?.trim();
        const sqmValue = sqmField.value?.trim();

        // Clear previous validation states
        [tsuboField, sqmField].forEach(field => {
          field.classList.remove('is-invalid');
          this.removeFieldErrorMessage(field);
        });

        // If both are empty, show error on both
        if (!tsuboValue && !sqmValue) {
          const errorMessage = '敷地面積（坪数）または敷地面積（㎡）のいずれかは必須項目です。';
          [tsuboField, sqmField].forEach(field => {
            field.classList.add('is-invalid');
            this.displayFieldErrorMessage(field, errorMessage);
          });
        } else {
          // If at least one has value, mark both as valid
          [tsuboField, sqmField].forEach(field => {
            if (field.value?.trim()) {
              field.classList.add('is-valid');
            }
          });
        }
      }
    }
  }

  /**
   * Display error message for a specific field
   * @param {HTMLElement} element 
   * @param {string} message 
   */
  displayFieldErrorMessage(element, message) {
    // Remove existing error message
    this.removeFieldErrorMessage(element);

    // Create new error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-field-error', element.id || element.name);

    // Insert after the field
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
  }

  /**
   * Remove error message for a specific field
   * @param {HTMLElement} element 
   */
  removeFieldErrorMessage(element) {
    const fieldId = element.id || element.name;
    const existingError = element.parentNode.querySelector(`[data-field-error="${fieldId}"]`);
    if (existingError) {
      existingError.remove();
    }
  }
}