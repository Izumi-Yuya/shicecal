/**
 * Calculation Engine for Land Info Form
 * Handles unit price and contract period calculations with caching
 */
export class Calculator {
  constructor(domCache = null, errorHandler = null) {
    this.domCache = domCache;
    this.errorHandler = errorHandler;
    this.cache = new Map();
    this.debounceTimers = new Map();
    this.stats = {
      calculations: 0,
      cacheHits: 0,
      errors: 0
    };
  }

  /**
   * Calculate unit price per tsubo (purchase_price / site_area_tsubo)
   * @param {number} purchasePrice 
   * @param {number} siteAreaTsubo 
   * @returns {Object|null}
   */
  calculateUnitPrice(purchasePrice, siteAreaTsubo) {
    // Input sanitization and type conversion
    const sanitizedPrice = this.sanitizeNumericInput(purchasePrice);
    const sanitizedArea = this.sanitizeNumericInput(siteAreaTsubo);

    const cacheKey = `unitPrice_${sanitizedPrice}_${sanitizedArea}`;

    // Check cache first
    if (this.cache.has(cacheKey)) {
      this.stats.cacheHits++;
      return this.cache.get(cacheKey);
    }

    // Validate inputs with detailed error handling
    if (!this.isValidNumber(sanitizedPrice) || !this.isValidNumber(sanitizedArea)) {
      return {
        error: true,
        errorMessage: '有効な数値を入力してください。',
        unitPrice: 0,
        formattedPrice: ''
      };
    }

    if (sanitizedPrice <= 0) {
      return {
        error: true,
        errorMessage: '購入金額は0より大きい値を入力してください。',
        unitPrice: 0,
        formattedPrice: ''
      };
    }

    if (sanitizedArea <= 0) {
      return {
        error: true,
        errorMessage: '敷地面積（坪数）は0より大きい値を入力してください。',
        unitPrice: 0,
        formattedPrice: ''
      };
    }

    // Security check: prevent extremely large numbers
    if (sanitizedPrice > Number.MAX_SAFE_INTEGER || sanitizedArea > Number.MAX_SAFE_INTEGER) {
      return {
        error: true,
        errorMessage: '入力値が大きすぎます。',
        unitPrice: 0,
        formattedPrice: ''
      };
    }

    // Perform calculation
    this.stats.calculations++;
    const unitPrice = Math.round(sanitizedPrice / sanitizedArea);
    const formattedPrice = unitPrice.toLocaleString();

    // Check for unreasonably high unit prices (warning threshold)
    let warning = null;
    if (unitPrice > 10000000) { // 1000万円/坪
      warning = '坪単価が非常に高額です。入力内容をご確認ください。';
    }

    const result = {
      unitPrice,
      formattedPrice,
      error: false,
      warning: warning
    };

    // Cache result
    this.cache.set(cacheKey, result);
    this.limitCacheSize();

    return result;
  }

  /**
   * Calculate contract period between two dates with edge case handling
   * @param {Date} startDate 
   * @param {Date} endDate 
   * @returns {Object|null}
   */
  calculateContractPeriod(startDate, endDate) {
    // Handle invalid date ranges (end date earlier than start date) with real-time errors
    if (!startDate || !endDate) {
      return null;
    }

    if (endDate <= startDate) {
      // Return error result for invalid date ranges
      return {
        error: true,
        errorMessage: '契約終了日は契約開始日より後の日付を入力してください。',
        totalMonths: 0,
        periodText: ''
      };
    }

    const cacheKey = `period_${startDate.getTime()}_${endDate.getTime()}`;

    // Check cache first
    if (this.cache.has(cacheKey)) {
      this.stats.cacheHits++;
      return this.cache.get(cacheKey);
    }

    // Perform calculation
    this.stats.calculations++;
    const years = endDate.getFullYear() - startDate.getFullYear();
    const months = endDate.getMonth() - startDate.getMonth();

    let totalMonths = years * 12 + months;

    // Adjust for day differences
    if (endDate.getDate() < startDate.getDate()) {
      totalMonths--;
    }

    // Handle edge case where totalMonths might be negative due to day adjustment
    if (totalMonths < 0) {
      totalMonths = 0;
    }

    const displayYears = Math.floor(totalMonths / 12);
    const displayMonths = totalMonths % 12;

    // Consider flexible formatting (years/months/days) for contract period display
    let periodText = this.formatContractPeriod(displayYears, displayMonths, startDate, endDate);

    const result = {
      totalMonths,
      periodText,
      years: displayYears,
      months: displayMonths,
      error: false
    };

    // Cache result
    this.cache.set(cacheKey, result);
    this.limitCacheSize();

    return result;
  }

  /**
   * Format contract period with flexible formatting (years/months/days)
   * @param {number} years 
   * @param {number} months 
   * @param {Date} startDate 
   * @param {Date} endDate 
   * @returns {string}
   */
  formatContractPeriod(years, months, startDate, endDate) {
    let periodText = '';

    // Add years if present
    if (years > 0) {
      periodText += `${years}年`;
    }

    // Add months if present
    if (months > 0) {
      periodText += `${months}ヶ月`;
    }

    // If no years or months, calculate days for more precise display
    if (years === 0 && months === 0) {
      const timeDiff = endDate.getTime() - startDate.getTime();
      const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

      if (daysDiff > 0) {
        periodText = `${daysDiff}日`;
      } else {
        periodText = '0日';
      }
    }

    return periodText || '0ヶ月';
  }

  /**
   * Debounced calculation wrapper
   * @param {string} fieldId 
   * @param {Function} calculationFn 
   * @param {number} delay 
   */
  debouncedCalculation(fieldId, calculationFn, delay = 300) {
    // Clear existing timer
    if (this.debounceTimers.has(fieldId)) {
      clearTimeout(this.debounceTimers.get(fieldId));
    }

    // Set new timer
    const timer = setTimeout(() => {
      calculationFn();
      this.debounceTimers.delete(fieldId);
    }, delay);

    this.debounceTimers.set(fieldId, timer);
  }

  /**
   * Clear calculation cache
   */
  clearCache() {
    this.cache.clear();
    this.stats.calculations = 0;
    this.stats.cacheHits = 0;
  }

  /**
   * Get cache statistics
   * @returns {Object}
   */
  getCacheStats() {
    return {
      size: this.cache.size,
      calculations: this.stats.calculations,
      cacheHits: this.stats.cacheHits,
      hitRate: this.stats.calculations > 0
        ? ((this.stats.cacheHits / this.stats.calculations) * 100).toFixed(2) + '%'
        : '0%'
    };
  }

  /**
   * Limit cache size with LRU eviction policy
   */
  limitCacheSize() {
    const maxSize = 100;

    if (this.cache.size > maxSize) {
      // Implement LRU eviction
      if (!this.cacheAccess) {
        this.cacheAccess = new Map();
      }

      // Find least recently used entry
      let oldestKey = null;
      let oldestTime = Date.now();

      for (const [key] of this.cache) {
        const accessTime = this.cacheAccess.get(key) || 0;
        if (accessTime < oldestTime) {
          oldestTime = accessTime;
          oldestKey = key;
        }
      }

      if (oldestKey) {
        this.cache.delete(oldestKey);
        this.cacheAccess.delete(oldestKey);
      }
    }
  }

  /**
   * Update cache access time for LRU
   */
  updateCacheAccess(key) {
    if (!this.cacheAccess) {
      this.cacheAccess = new Map();
    }
    this.cacheAccess.set(key, Date.now());
  }

  /**
   * Sanitize numeric input
   * @param {any} input 
   * @returns {number}
   */
  sanitizeNumericInput(input) {
    if (typeof input === 'number') return input;
    if (typeof input === 'string') {
      // Remove commas, spaces, and currency symbols
      const cleaned = input.replace(/[,\s円¥]/g, '');
      return parseFloat(cleaned) || 0;
    }
    return 0;
  }

  /**
   * Check if a number is valid for calculations
   * @param {number} num 
   * @returns {boolean}
   */
  isValidNumber(num) {
    return typeof num === 'number' &&
      !isNaN(num) &&
      isFinite(num) &&
      num >= 0;
  }

  /**
   * Cleanup resources
   */
  destroy() {
    this.clearCache();

    // Clear any pending debounce timers
    this.debounceTimers.forEach(timer => clearTimeout(timer));
    this.debounceTimers.clear();
  }
}