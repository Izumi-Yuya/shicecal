/**
 * Form validation utilities for the Shise-Cal application
 * Client-side validation helpers and form management
 */

import { showToast } from './utils.js';

/**
 * Validation rule definitions
 */
const VALIDATION_RULES = {
  required: {
    validate: (value) => value !== null && value !== undefined && value.toString().trim() !== '',
    message: 'この項目は必須です。'
  },
  email: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(value);
    },
    message: '有効なメールアドレスを入力してください。'
  },
  numeric: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      return !isNaN(value) && !isNaN(parseFloat(value));
    },
    message: '数値を入力してください。'
  },
  integer: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      return Number.isInteger(Number(value));
    },
    message: '整数を入力してください。'
  },
  positive: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      return Number(value) > 0;
    },
    message: '正の数値を入力してください。'
  },
  minLength: {
    validate: (value, param) => {
      if (!value) return true; // Allow empty for optional fields
      return value.toString().length >= param;
    },
    message: (param) => `${param}文字以上で入力してください。`
  },
  maxLength: {
    validate: (value, param) => {
      if (!value) return true; // Allow empty for optional fields
      return value.toString().length <= param;
    },
    message: (param) => `${param}文字以下で入力してください。`
  },
  min: {
    validate: (value, param) => {
      if (!value) return true; // Allow empty for optional fields
      return Number(value) >= param;
    },
    message: (param) => `${param}以上の値を入力してください。`
  },
  max: {
    validate: (value, param) => {
      if (!value) return true; // Allow empty for optional fields
      return Number(value) <= param;
    },
    message: (param) => `${param}以下の値を入力してください。`
  },
  date: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      const date = new Date(value);
      return !isNaN(date.getTime());
    },
    message: '有効な日付を入力してください。'
  },
  futureDate: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      const date = new Date(value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return date >= today;
    },
    message: '今日以降の日付を入力してください。'
  },
  pastDate: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      const date = new Date(value);
      const today = new Date();
      today.setHours(23, 59, 59, 999);
      return date <= today;
    },
    message: '今日以前の日付を入力してください。'
  },
  phone: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      // Japanese phone number pattern (flexible)
      const phoneRegex = /^[\d\-\(\)\+\s]+$/;
      return phoneRegex.test(value) && value.replace(/\D/g, '').length >= 10;
    },
    message: '有効な電話番号を入力してください。'
  },
  zipCode: {
    validate: (value) => {
      if (!value) return true; // Allow empty for optional fields
      // Japanese postal code pattern (xxx-xxxx or xxxxxxx)
      const zipRegex = /^\d{3}-?\d{4}$/;
      return zipRegex.test(value);
    },
    message: '有効な郵便番号を入力してください（例：123-4567）。'
  }
};

/**
 * Parse validation rules from string
 * @param {string} rulesString - Rules string (e.g., "required|numeric|min:0")
 * @returns {Array} Array of rule objects
 */
function parseRules(rulesString) {
  if (!rulesString) return [];

  return rulesString.split('|').map(rule => {
    const [name, param] = rule.split(':');
    return {
      name: name.trim(),
      param: param ? (isNaN(param) ? param : Number(param)) : null
    };
  });
}

/**
 * Validate a single field
 * @param {*} value - Field value
 * @param {string|Array} rules - Validation rules
 * @param {string} fieldName - Field name for error messages
 * @returns {Object} Validation result
 */
export function validateField(value, rules, fieldName = 'フィールド') {
  const ruleArray = typeof rules === 'string' ? parseRules(rules) : rules;
  const errors = [];

  for (const rule of ruleArray) {
    const validator = VALIDATION_RULES[rule.name];
    if (!validator) {
      console.warn(`Unknown validation rule: ${rule.name}`);
      continue;
    }

    const isValid = validator.validate(value, rule.param);
    if (!isValid) {
      const message = typeof validator.message === 'function'
        ? validator.message(rule.param)
        : validator.message;
      errors.push(message);
    }
  }

  return {
    isValid: errors.length === 0,
    errors: errors
  };
}

/**
 * Validate form data
 * @param {Object} data - Form data object
 * @param {Object} rules - Validation rules object
 * @returns {Object} Validation result
 */
export function validateForm(data, rules) {
  const errors = {};
  let isValid = true;

  for (const [fieldName, fieldRules] of Object.entries(rules)) {
    const fieldValue = data[fieldName];
    const result = validateField(fieldValue, fieldRules, fieldName);

    if (!result.isValid) {
      errors[fieldName] = result.errors;
      isValid = false;
    }
  }

  return {
    isValid,
    errors
  };
}

/**
 * Display validation errors on form
 * @param {HTMLFormElement} form - Form element
 * @param {Object} errors - Validation errors object
 */
export function displayFormErrors(form, errors) {
  // Clear existing errors
  clearFormErrors(form);

  for (const [fieldName, fieldErrors] of Object.entries(errors)) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (!field) continue;

    // Add error class to field
    field.classList.add('is-invalid');

    // Create or update error message
    let errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'invalid-feedback';
      field.parentNode.appendChild(errorElement);
    }

    errorElement.textContent = fieldErrors[0]; // Show first error
  }
}

/**
 * Clear validation errors from form
 * @param {HTMLFormElement} form - Form element
 */
export function clearFormErrors(form) {
  // Remove error classes
  form.querySelectorAll('.is-invalid').forEach(field => {
    field.classList.remove('is-invalid');
  });

  // Remove error messages
  form.querySelectorAll('.invalid-feedback').forEach(element => {
    element.remove();
  });
}

/**
 * Real-time validation for form fields
 * @param {HTMLFormElement} form - Form element
 * @param {Object} rules - Validation rules object
 * @param {Object} options - Validation options
 */
export function enableRealTimeValidation(form, rules, options = {}) {
  const {
    validateOnBlur = true,
    validateOnInput = false,
    debounceDelay = 300
  } = options;

  let debounceTimer;

  const validateFieldRealTime = (field) => {
    const fieldName = field.name;
    const fieldRules = rules[fieldName];

    if (!fieldRules) return;

    const result = validateField(field.value, fieldRules, fieldName);

    // Clear previous errors for this field
    field.classList.remove('is-invalid', 'is-valid');
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
      errorElement.remove();
    }

    if (!result.isValid) {
      field.classList.add('is-invalid');
      const newErrorElement = document.createElement('div');
      newErrorElement.className = 'invalid-feedback';
      newErrorElement.textContent = result.errors[0];
      field.parentNode.appendChild(newErrorElement);
    } else if (field.value.trim() !== '') {
      field.classList.add('is-valid');
    }
  };

  // Add event listeners
  for (const fieldName of Object.keys(rules)) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (!field) continue;

    if (validateOnBlur) {
      field.addEventListener('blur', () => {
        validateFieldRealTime(field);
      });
    }

    if (validateOnInput) {
      field.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          validateFieldRealTime(field);
        }, debounceDelay);
      });
    }
  }
}

/**
 * Validate form on submit
 * @param {HTMLFormElement} form - Form element
 * @param {Object} rules - Validation rules object
 * @param {Function} onValid - Callback for valid form
 * @param {Function} onInvalid - Callback for invalid form
 */
export function validateOnSubmit(form, rules, onValid, onInvalid = null) {
  form.addEventListener('submit', (event) => {
    event.preventDefault();

    // Get form data
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validate form
    const result = validateForm(data, rules);

    if (result.isValid) {
      if (onValid) {
        onValid(data, event);
      }
    } else {
      displayFormErrors(form, result.errors);

      // Focus on first error field
      const firstErrorField = form.querySelector('.is-invalid');
      if (firstErrorField) {
        firstErrorField.focus();
      }

      // Show error toast
      showToast('入力内容に問題があります。エラーを確認してください。', 'error');

      if (onInvalid) {
        onInvalid(result.errors, event);
      }
    }
  });
}

/**
 * Custom validation rule
 * @param {string} name - Rule name
 * @param {Function} validator - Validation function
 * @param {string|Function} message - Error message
 */
export function addValidationRule(name, validator, message) {
  VALIDATION_RULES[name] = {
    validate: validator,
    message: message
  };
}

/**
 * Validate file input
 * @param {File} file - File object
 * @param {Object} options - Validation options
 * @returns {Object} Validation result
 */
export function validateFile(file, options = {}) {
  const {
    maxSize = 10 * 1024 * 1024, // 10MB default
    allowedTypes = [],
    allowedExtensions = []
  } = options;

  const errors = [];

  // Check file size
  if (file.size > maxSize) {
    const maxSizeMB = Math.round(maxSize / (1024 * 1024));
    errors.push(`ファイルサイズは${maxSizeMB}MB以下にしてください。`);
  }

  // Check file type
  if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
    errors.push(`許可されていないファイル形式です。`);
  }

  // Check file extension
  if (allowedExtensions.length > 0) {
    const extension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(extension)) {
      errors.push(`許可されていないファイル拡張子です。`);
    }
  }

  return {
    isValid: errors.length === 0,
    errors: errors
  };
}

/**
 * Format validation errors for display
 * @param {Object} errors - Validation errors object
 * @returns {string} Formatted error message
 */
export function formatValidationErrors(errors) {
  const errorMessages = [];

  for (const [fieldName, fieldErrors] of Object.entries(errors)) {
    for (const error of fieldErrors) {
      errorMessages.push(`${fieldName}: ${error}`);
    }
  }

  return errorMessages.join('\n');
}