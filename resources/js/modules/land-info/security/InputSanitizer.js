/**
 * Comprehensive Input Sanitization for Security
 * Implements multiple layers of protection against XSS and injection attacks
 */
export class InputSanitizer {
  constructor() {
    // Whitelist of allowed HTML tags (if any)
    this.allowedTags = new Set([]);

    // Whitelist of allowed attributes
    this.allowedAttributes = new Set(['id', 'class', 'data-*']);

    // Dangerous patterns to detect and remove
    this.dangerousPatterns = [
      // JavaScript protocols
      /(?:javascript|data|vbscript|file|about):/gi,

      // Event handlers
      /on\w+\s*=\s*["'][^"']*["']/gi,

      // CSS expressions and imports
      /(?:expression|import|@import)\s*\([^)]*\)/gi,

      // Script tags and content
      /<script[^>]*>[\s\S]*?<\/script>/gi,

      // Style tags with dangerous content
      /<style[^>]*>[\s\S]*?<\/style>/gi,

      // Meta refresh redirects
      /<meta[^>]*http-equiv\s*=\s*["']?refresh["']?[^>]*>/gi,

      // Object and embed tags
      /<(?:object|embed|applet|iframe)[^>]*>[\s\S]*?<\/(?:object|embed|applet|iframe)>/gi,

      // Form elements that could be dangerous
      /<(?:form|input|textarea|select|button)[^>]*>/gi,

      // Link tags that could load external resources
      /<link[^>]*>/gi
    ];

    // Character encoding attacks
    this.encodingPatterns = [
      // Unicode normalization attacks
      /[\u0000-\u001f\u007f-\u009f]/g,

      // HTML entities that could be dangerous
      /&(?:#x?[0-9a-f]+|[a-z]+);/gi,

      // URL encoding attacks
      /%[0-9a-f]{2}/gi
    ];
  }

  /**
   * Comprehensive input sanitization
   * @param {string} input 
   * @param {Object} options 
   * @returns {string}
   */
  sanitize(input, options = {}) {
    if (typeof input !== 'string') {
      return '';
    }

    const config = {
      maxLength: options.maxLength || 10000,
      allowHTML: options.allowHTML || false,
      allowNumbers: options.allowNumbers !== false,
      allowSpecialChars: options.allowSpecialChars !== false,
      preserveLineBreaks: options.preserveLineBreaks || false,
      ...options
    };

    // Step 1: Length validation (DoS prevention)
    if (input.length > config.maxLength) {
      input = input.substring(0, config.maxLength);
    }

    // Step 2: Remove null bytes and control characters
    input = this.removeControlCharacters(input);

    // Step 3: Normalize Unicode to prevent normalization attacks
    input = this.normalizeUnicode(input);

    // Step 4: Remove dangerous patterns
    input = this.removeDangerousPatterns(input);

    // Step 5: HTML sanitization
    if (!config.allowHTML) {
      input = this.removeHTML(input);
    } else {
      input = this.sanitizeHTML(input);
    }

    // Step 6: Remove dangerous encodings
    input = this.removeDangerousEncodings(input);

    // Step 7: Context-specific sanitization
    if (config.context) {
      input = this.contextSpecificSanitization(input, config.context);
    }

    // Step 8: Final cleanup
    input = this.finalCleanup(input, config);

    return input;
  }

  /**
   * Remove control characters and null bytes
   * @param {string} input 
   * @returns {string}
   */
  removeControlCharacters(input) {
    // Remove null bytes, control characters, but preserve common whitespace
    return input.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
  }

  /**
   * Normalize Unicode to prevent attacks
   * @param {string} input 
   * @returns {string}
   */
  normalizeUnicode(input) {
    try {
      // Normalize to NFC form to prevent Unicode normalization attacks
      return input.normalize('NFC');
    } catch (error) {
      console.warn('Unicode normalization failed:', error);
      return input;
    }
  }

  /**
   * Remove dangerous patterns
   * @param {string} input 
   * @returns {string}
   */
  removeDangerousPatterns(input) {
    let sanitized = input;

    this.dangerousPatterns.forEach(pattern => {
      sanitized = sanitized.replace(pattern, '');
    });

    return sanitized;
  }

  /**
   * Remove all HTML tags and entities
   * @param {string} input 
   * @returns {string}
   */
  removeHTML(input) {
    // Remove HTML tags
    let sanitized = input.replace(/<[^>]*>/g, '');

    // Decode HTML entities to prevent double-encoding attacks
    sanitized = this.decodeHTMLEntities(sanitized);

    // Remove any remaining angle brackets
    sanitized = sanitized.replace(/[<>]/g, '');

    return sanitized;
  }

  /**
   * Sanitize HTML while preserving allowed tags
   * @param {string} input 
   * @returns {string}
   */
  sanitizeHTML(input) {
    // For now, remove all HTML since we don't allow any tags
    // In future, could implement whitelist-based HTML sanitization
    return this.removeHTML(input);
  }

  /**
   * Decode HTML entities safely
   * @param {string} input 
   * @returns {string}
   */
  decodeHTMLEntities(input) {
    const entityMap = {
      '&amp;': '&',
      '&lt;': '<',
      '&gt;': '>',
      '&quot;': '"',
      '&#x27;': "'",
      '&#x2F;': '/',
      '&#x60;': '`',
      '&#x3D;': '='
    };

    return input.replace(/&(?:amp|lt|gt|quot|#x27|#x2F|#x60|#x3D);/g,
      match => entityMap[match] || match);
  }

  /**
   * Remove dangerous encodings
   * @param {string} input 
   * @returns {string}
   */
  removeDangerousEncodings(input) {
    let sanitized = input;

    // Remove URL encoding that could hide dangerous content
    try {
      // Decode URL encoding, but limit iterations to prevent DoS
      let iterations = 0;
      let previous = '';

      while (sanitized !== previous && iterations < 5) {
        previous = sanitized;
        sanitized = decodeURIComponent(sanitized);
        iterations++;
      }
    } catch (error) {
      // If decoding fails, keep original (safer)
      sanitized = input;
    }

    return sanitized;
  }

  /**
   * Context-specific sanitization
   * @param {string} input 
   * @param {string} context 
   * @returns {string}
   */
  contextSpecificSanitization(input, context) {
    switch (context) {
      case 'currency':
        return this.sanitizeCurrency(input);
      case 'email':
        return this.sanitizeEmail(input);
      case 'phone':
        return this.sanitizePhone(input);
      case 'url':
        return this.sanitizeURL(input);
      case 'postal_code':
        return this.sanitizePostalCode(input);
      case 'numeric':
        return this.sanitizeNumeric(input);
      default:
        return input;
    }
  }

  /**
   * Sanitize currency input
   * @param {string} input 
   * @returns {string}
   */
  sanitizeCurrency(input) {
    // Allow only numbers, commas, periods, and currency symbols
    return input.replace(/[^0-9,.\u00A5\u20AC\u0024]/g, '');
  }

  /**
   * Sanitize email input
   * @param {string} input 
   * @returns {string}
   */
  sanitizeEmail(input) {
    // Allow only valid email characters
    return input.replace(/[^a-zA-Z0-9@._+-]/g, '');
  }

  /**
   * Sanitize phone number input
   * @param {string} input 
   * @returns {string}
   */
  sanitizePhone(input) {
    // Allow only numbers, hyphens, parentheses, spaces, and plus
    return input.replace(/[^0-9\-\(\)\s\+]/g, '');
  }

  /**
   * Sanitize URL input
   * @param {string} input 
   * @returns {string}
   */
  sanitizeURL(input) {
    // Basic URL sanitization - remove dangerous protocols
    let sanitized = input.replace(/^(?:javascript|data|vbscript|file):/gi, 'http:');

    // Ensure it starts with http:// or https://
    if (!/^https?:\/\//i.test(sanitized) && sanitized.length > 0) {
      sanitized = 'http://' + sanitized;
    }

    return sanitized;
  }

  /**
   * Sanitize postal code input
   * @param {string} input 
   * @returns {string}
   */
  sanitizePostalCode(input) {
    // Allow only numbers and hyphens for Japanese postal codes
    return input.replace(/[^0-9\-]/g, '');
  }

  /**
   * Sanitize numeric input
   * @param {string} input 
   * @returns {string}
   */
  sanitizeNumeric(input) {
    // Allow only numbers, decimal points, and minus sign
    return input.replace(/[^0-9.\-]/g, '');
  }

  /**
   * Final cleanup
   * @param {string} input 
   * @param {Object} config 
   * @returns {string}
   */
  finalCleanup(input, config) {
    let sanitized = input;

    // Trim whitespace
    sanitized = sanitized.trim();

    // Normalize whitespace unless preserving line breaks
    if (!config.preserveLineBreaks) {
      sanitized = sanitized.replace(/\s+/g, ' ');
    }

    // Remove leading/trailing whitespace from each line if preserving line breaks
    if (config.preserveLineBreaks) {
      sanitized = sanitized.split('\n').map(line => line.trim()).join('\n');
    }

    return sanitized;
  }

  /**
   * Validate that sanitized input is safe
   * @param {string} input 
   * @returns {Object}
   */
  validate(input) {
    const issues = [];

    // Check for remaining dangerous patterns
    this.dangerousPatterns.forEach((pattern, index) => {
      if (pattern.test(input)) {
        issues.push(`Dangerous pattern ${index} detected`);
      }
    });

    // Check for suspicious character sequences
    if (/<|>|javascript:|data:|vbscript:/i.test(input)) {
      issues.push('Suspicious character sequences detected');
    }

    // Check for excessive length
    if (input.length > 10000) {
      issues.push('Input exceeds maximum safe length');
    }

    return {
      isSafe: issues.length === 0,
      issues: issues,
      sanitizedLength: input.length
    };
  }
}