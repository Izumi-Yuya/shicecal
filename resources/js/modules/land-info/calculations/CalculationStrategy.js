/**
 * Strategy pattern for different calculation types
 * Allows easy extension and testing of calculation logic
 */

export class CalculationStrategy {
  /**
   * Execute calculation
   * @param {Object} inputs 
   * @returns {Object}
   */
  calculate(inputs) {
    throw new Error('Calculate method must be implemented');
  }

  /**
   * Validate inputs for this calculation
   * @param {Object} inputs 
   * @returns {boolean}
   */
  validateInputs(inputs) {
    return true;
  }

  /**
   * Get cache key for this calculation
   * @param {Object} inputs 
   * @returns {string}
   */
  getCacheKey(inputs) {
    return JSON.stringify(inputs);
  }
}

export class UnitPriceCalculationStrategy extends CalculationStrategy {
  calculate(inputs) {
    const { purchasePrice, siteAreaTsubo } = inputs;

    if (!this.validateInputs(inputs)) {
      return {
        error: true,
        errorMessage: '有効な数値を入力してください。',
        unitPrice: 0,
        formattedPrice: ''
      };
    }

    const unitPrice = Math.round(purchasePrice / siteAreaTsubo);

    return {
      unitPrice,
      formattedPrice: unitPrice.toLocaleString(),
      error: false,
      warning: unitPrice > 10000000 ? '坪単価が非常に高額です。入力内容をご確認ください。' : null
    };
  }

  validateInputs(inputs) {
    const { purchasePrice, siteAreaTsubo } = inputs;

    return (
      this.isValidNumber(purchasePrice) &&
      this.isValidNumber(siteAreaTsubo) &&
      purchasePrice > 0 &&
      siteAreaTsubo > 0 &&
      purchasePrice <= Number.MAX_SAFE_INTEGER &&
      siteAreaTsubo <= Number.MAX_SAFE_INTEGER
    );
  }

  isValidNumber(num) {
    return typeof num === 'number' && !isNaN(num) && isFinite(num);
  }

  getCacheKey(inputs) {
    return `unitPrice_${inputs.purchasePrice}_${inputs.siteAreaTsubo}`;
  }
}

export class ContractPeriodCalculationStrategy extends CalculationStrategy {
  calculate(inputs) {
    const { startDate, endDate } = inputs;

    if (!this.validateInputs(inputs)) {
      return {
        error: true,
        errorMessage: '契約終了日は契約開始日より後の日付を入力してください。',
        totalMonths: 0,
        periodText: ''
      };
    }

    const years = endDate.getFullYear() - startDate.getFullYear();
    const months = endDate.getMonth() - startDate.getMonth();
    let totalMonths = years * 12 + months;

    if (endDate.getDate() < startDate.getDate()) {
      totalMonths--;
    }

    if (totalMonths < 0) totalMonths = 0;

    const displayYears = Math.floor(totalMonths / 12);
    const displayMonths = totalMonths % 12;
    const periodText = this.formatPeriod(displayYears, displayMonths, startDate, endDate);

    return {
      totalMonths,
      periodText,
      years: displayYears,
      months: displayMonths,
      error: false
    };
  }

  validateInputs(inputs) {
    const { startDate, endDate } = inputs;
    return startDate && endDate && endDate > startDate;
  }

  formatPeriod(years, months, startDate, endDate) {
    let periodText = '';

    if (years > 0) periodText += `${years}年`;
    if (months > 0) periodText += `${months}ヶ月`;

    if (years === 0 && months === 0) {
      const timeDiff = endDate.getTime() - startDate.getTime();
      const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
      periodText = daysDiff > 0 ? `${daysDiff}日` : '0日';
    }

    return periodText || '0ヶ月';
  }

  getCacheKey(inputs) {
    return `period_${inputs.startDate.getTime()}_${inputs.endDate.getTime()}`;
  }
}

export class CalculationContext {
  constructor() {
    this.strategies = new Map();
    this.cache = new Map();
    this.cacheLimit = 100;
  }

  /**
   * Register calculation strategy
   * @param {string} type 
   * @param {CalculationStrategy} strategy 
   */
  registerStrategy(type, strategy) {
    this.strategies.set(type, strategy);
  }

  /**
   * Execute calculation with caching
   * @param {string} type 
   * @param {Object} inputs 
   * @returns {Object}
   */
  calculate(type, inputs) {
    const strategy = this.strategies.get(type);
    if (!strategy) {
      throw new Error(`Unknown calculation type: ${type}`);
    }

    const cacheKey = `${type}_${strategy.getCacheKey(inputs)}`;

    if (this.cache.has(cacheKey)) {
      return this.cache.get(cacheKey);
    }

    const result = strategy.calculate(inputs);

    // Cache successful results
    if (!result.error) {
      this.cache.set(cacheKey, result);
      this.limitCacheSize();
    }

    return result;
  }

  /**
   * Clear cache
   */
  clearCache() {
    this.cache.clear();
  }

  /**
   * Limit cache size using LRU
   */
  limitCacheSize() {
    if (this.cache.size > this.cacheLimit) {
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }
  }
}