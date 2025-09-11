/**
 * Land Information Form Manager
 * Handles dynamic form behavior, calculations, and validations
 */

class LandInfoManager {
  // Constants for ownership types - matches database enum values
  static OWNERSHIP_TYPES = {
    OWNED: 'owned',
    LEASED: 'leased',
    OWNED_RENTAL: 'owned_rental'
  };

  // Section visibility rules based on ownership type
  static SECTION_VISIBILITY_RULES = {
    owned_section: [LandInfoManager.OWNERSHIP_TYPES.OWNED, LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL],
    leased_section: [LandInfoManager.OWNERSHIP_TYPES.LEASED, LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL],
    management_section: [LandInfoManager.OWNERSHIP_TYPES.LEASED],
    owner_section: [LandInfoManager.OWNERSHIP_TYPES.LEASED],
    file_section: [LandInfoManager.OWNERSHIP_TYPES.LEASED, LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL]
  };

  // Field groups that should be cleared based on ownership type
  static FIELD_GROUPS = {
    owned: ['purchase_price', 'unit_price_display'],
    leased: [
      'monthly_rent', 'contract_start_date', 'contract_end_date',
      'auto_renewal', 'contract_period_display'
    ],
    management: [
      'management_company_name', 'management_company_postal_code',
      'management_company_address', 'management_company_building',
      'management_company_phone', 'management_company_fax',
      'management_company_email', 'management_company_url',
      'management_company_notes'
    ],
    owner: [
      'owner_name', 'owner_postal_code', 'owner_address',
      'owner_building', 'owner_phone', 'owner_fax',
      'owner_email', 'owner_url', 'owner_notes'
    ]
  };

  constructor() {
    this.debounceTimers = new Map();
    this.calculationCache = new Map();
    this.performanceMetrics = {
      calculationCount: 0,
      cacheHits: 0,
      startTime: performance.now()
    };

    // Check if we're on the land info edit page
    const landInfoForm = document.getElementById('landInfoForm');
    if (!landInfoForm) {
      console.log('LandInfoManager: Not on land info edit page, skipping initialization');
      return;
    }

    this.initializeEventListeners();
    // 初期化時に条件付きセクションを更新（少し遅延させてDOMが完全に読み込まれるのを待つ）
    setTimeout(() => {
      this.debugDOMElements();
      this.updateConditionalSections();
      // 初期計算も実行
      this.calculateUnitPrice();
      this.calculateContractPeriod();
    }, 100);
    this.initializeCharacterCount();
    this.initializePerformanceOptimizations();
  }

  /**
   * Debug DOM elements to check if they exist
   */
  debugDOMElements() {
    console.log('🔍 Debugging DOM elements...');

    // Check ownership type element
    const ownershipType = document.getElementById('ownership_type');
    console.log('ownership_type element:', ownershipType, 'value:', ownershipType?.value);

    // Check all section elements
    const sectionIds = Object.keys(LandInfoManager.SECTION_VISIBILITY_RULES);
    sectionIds.forEach(sectionId => {
      const element = document.getElementById(sectionId);
      console.log(`${sectionId} element:`, !!element, element ? `display: ${element.style.display}` : 'NOT FOUND');
    });

    // Check if we're in the right page
    const debugForm = document.getElementById('landInfoForm');
    console.log('landInfoForm element:', !!debugForm);
  }

  /**
   * Get current ownership type value from select or radio group
   * @returns {string} The ownership type value or empty string if not found
   */
  getOwnershipTypeValue() {
    try {
      // Try select element first (more common)
      const select = document.getElementById('ownership_type');
      if (select && typeof select.value !== 'undefined') {
        return select.value;
      }

      // Fallback to radio buttons
      const radios = document.querySelectorAll('input[name="ownership_type"]');
      for (const radio of radios) {
        if (radio.checked) {
          return radio.value;
        }
      }

      return '';
    } catch (error) {
      console.error('Error getting ownership type value:', error);
      return '';
    }
  }

  /**
   * Initialize all event listeners
   */
  initializeEventListeners() {
    // 所有形態変更時の表示制御（select または radio 両対応）
    const ownershipTypeSelect = document.getElementById('ownership_type');
    if (ownershipTypeSelect) {
      // changeイベントとinputイベント両方を監視
      ['change', 'input'].forEach(eventType => {
        ownershipTypeSelect.addEventListener(eventType, () => {
          console.log('Ownership type event triggered:', eventType, ownershipTypeSelect.value);
          this.updateConditionalSections();
          // フィールドクリアは少し遅延させる
          setTimeout(() => {
            this.clearConditionalFields();
            this.clearValidationErrors();
          }, 50);
        });
      });
    }
    const ownershipTypeRadios = document.querySelectorAll('input[name="ownership_type"]');
    if (ownershipTypeRadios && ownershipTypeRadios.length > 0) {
      ownershipTypeRadios.forEach(r => {
        ['change', 'input'].forEach(eventType => {
          r.addEventListener(eventType, () => {
            console.log('Ownership type radio event triggered:', eventType, r.value);
            this.updateConditionalSections();
            setTimeout(() => {
              this.clearConditionalFields();
              this.clearValidationErrors();
            }, 50);
          });
        });
      });
    }

    // 自動計算機能
    this.initializeCalculations();

    // 通貨フォーマット
    this.initializeCurrencyFormatting();

    // 全角数字を半角に変換
    this.initializeNumberConversion();

    // フォームバリデーション
    this.initializeFormValidation();

    // ファイルアップロード
    this.initializeFileUpload();

    // リアルタイム計算
    this.initializeRealtimeCalculation();
  }

  /**
   * Initialize calculation functionality
   */
  initializeCalculations() {
    // 坪単価計算
    const purchasePrice = document.getElementById('purchase_price');
    const siteAreaTsubo = document.getElementById('site_area_tsubo');

    if (purchasePrice) {
      purchasePrice.addEventListener('input', () => {
        this.calculateUnitPrice();
      });
    }

    if (siteAreaTsubo) {
      siteAreaTsubo.addEventListener('input', () => {
        this.calculateUnitPrice();
      });
    }

    // 契約期間計算
    const contractStartDate = document.getElementById('contract_start_date');
    const contractEndDate = document.getElementById('contract_end_date');

    if (contractStartDate) {
      contractStartDate.addEventListener('change', () => {
        this.calculateContractPeriod();
      });
    }

    if (contractEndDate) {
      contractEndDate.addEventListener('change', () => {
        this.calculateContractPeriod();
      });
    }
  }

  /**
   * Initialize currency formatting
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
        e.target.value = this.validatePhoneNumber(e.target.value);
      });
    });

    // Postal code formatting
    document.querySelectorAll('input[name$="_postal_code"]').forEach(input => {
      input.addEventListener('blur', (e) => {
        e.target.value = this.validatePostalCode(e.target.value);
      });
    });
  }

  /**
   * Initialize form validation
   */
  initializeFormValidation() {
    const validationForm = document.getElementById('landInfoForm');
    if (validationForm) {
      validationForm.addEventListener('submit', (e) => {
        if (!this.validateForm()) {
          e.preventDefault();
        }
      });
    }
  }

  /**
   * Initialize file upload functionality
   */
  initializeFileUpload() {
    // ファイルサイズチェック
    document.querySelectorAll('input[type="file"]').forEach(input => {
      input.addEventListener('change', (e) => {
        this.validateFileSize(e.target);
      });
    });
  }

  /**
   * Initialize realtime calculation functionality with performance optimization
   */
  initializeRealtimeCalculation() {
    // 計算に影響する全てのフィールドにリスナーを追加
    const calculationFields = [
      'purchase_price', 'site_area_tsubo',
      'contract_start_date', 'contract_end_date'
    ];

    calculationFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        // Use optimized debounced calculation
        field.addEventListener('input', (e) => this.debouncedCalculation(fieldId, e.target.value));
        field.addEventListener('change', (e) => this.debouncedCalculation(fieldId, e.target.value));
      }
    });
  }

  /**
   * Initialize performance optimizations
   */
  initializePerformanceOptimizations() {
    // Lazy load heavy operations
    this.initializeLazyLoading();

    // Optimize DOM queries with caching
    this.initializeDOMCache();

    // Setup performance monitoring
    this.setupPerformanceMonitoring();

    // Initialize virtual scrolling for large lists if needed
    this.initializeVirtualScrolling();
  }

  /**
   * Debounced calculation with caching
   */
  debouncedCalculation(fieldId, value) {
    // Clear existing timer for this field
    if (this.debounceTimers.has(fieldId)) {
      clearTimeout(this.debounceTimers.get(fieldId));
    }

    // Set new timer
    const timer = setTimeout(() => {
      this.performOptimizedCalculation(fieldId, value);
      this.debounceTimers.delete(fieldId);
    }, 300);

    this.debounceTimers.set(fieldId, timer);
  }

  /**
   * Perform optimized calculation with caching
   */
  performOptimizedCalculation(fieldId, value) {
    const cacheKey = `${fieldId}_${value}`;

    // Check cache first
    if (this.calculationCache.has(cacheKey)) {
      this.performanceMetrics.cacheHits++;
      const cachedResult = this.calculationCache.get(cacheKey);
      this.applyCalculationResult(fieldId, cachedResult);
      return;
    }

    // Perform calculation
    this.performanceMetrics.calculationCount++;
    let result;

    if (['purchase_price', 'site_area_tsubo'].includes(fieldId)) {
      result = this.calculateUnitPriceOptimized();
    } else if (['contract_start_date', 'contract_end_date'].includes(fieldId)) {
      result = this.calculateContractPeriodOptimized();
    }

    // Cache result
    if (result !== undefined) {
      this.calculationCache.set(cacheKey, result);

      // Limit cache size to prevent memory leaks
      if (this.calculationCache.size > 100) {
        const firstKey = this.calculationCache.keys().next().value;
        this.calculationCache.delete(firstKey);
      }
    }

    this.applyCalculationResult(fieldId, result);
  }

  /**
   * Initialize DOM element caching for performance
   */
  initializeDOMCache() {
    this.domCache = {
      ownershipType: document.getElementById('ownership_type'),
      purchasePrice: document.getElementById('purchase_price'),
      siteAreaTsubo: document.getElementById('site_area_tsubo'),
      unitPriceDisplay: document.getElementById('unit_price_display'),
      contractStartDate: document.getElementById('contract_start_date'),
      contractEndDate: document.getElementById('contract_end_date'),
      contractPeriodDisplay: document.getElementById('contract_period_display'),
      form: document.getElementById('landInfoForm'),
      sections: {
        owned: document.getElementById('owned_section'),
        leased: document.getElementById('leased_section'),
        management: document.getElementById('management_section'),
        owner: document.getElementById('owner_section'),
        file: document.getElementById('file_section')
      }
    };
  }

  /**
   * Initialize lazy loading for heavy operations
   */
  initializeLazyLoading() {
    // Intersection Observer for lazy loading sections
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.loadSectionContent(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      // Observe sections that might contain heavy content
      document.querySelectorAll('.lazy-section').forEach(section => {
        observer.observe(section);
      });
    }
  }

  /**
   * Setup performance monitoring
   */
  setupPerformanceMonitoring() {
    // Monitor calculation performance
    setInterval(() => {
      const elapsed = performance.now() - this.performanceMetrics.startTime;
      const avgCalculationTime = elapsed / this.performanceMetrics.calculationCount;

      console.debug('Land Info Performance Metrics:', {
        calculations: this.performanceMetrics.calculationCount,
        cacheHits: this.performanceMetrics.cacheHits,
        cacheHitRate: (this.performanceMetrics.cacheHits / this.performanceMetrics.calculationCount * 100).toFixed(2) + '%',
        avgCalculationTime: avgCalculationTime.toFixed(2) + 'ms'
      });
    }, 30000); // Log every 30 seconds
  }

  /**
   * Initialize virtual scrolling for large lists
   */
  initializeVirtualScrolling() {
    const largeSelects = document.querySelectorAll('select[data-large-list]');

    largeSelects.forEach(select => {
      // Implement virtual scrolling for large option lists
      this.setupVirtualSelect(select);
    });
  }

  /**
   * Setup virtual select for large option lists
   */
  setupVirtualSelect(select) {
    // This would implement virtual scrolling for large select lists
    // For now, we'll just optimize the rendering
    const options = Array.from(select.options);

    if (options.length > 100) {
      // Convert to searchable dropdown for better performance
      this.convertToSearchableDropdown(select);
    }
  }

  /**
   * Convert large select to searchable dropdown
   */
  convertToSearchableDropdown(select) {
    // Implementation would create a custom searchable dropdown
    // This is a placeholder for the concept
    select.setAttribute('data-optimized', 'true');
  }

  /**
   * Update conditional sections based on ownership type
   */
  updateConditionalSections() {
    const ownershipType = this.getOwnershipTypeValue();
    if (!ownershipType) return;

    // Calculate section visibility using predefined rules
    const sectionVisibility = this.calculateSectionVisibility(ownershipType);

    // Apply visibility changes with animation
    this.applySectionVisibility(sectionVisibility);

    // Debug logging in development mode only
    if (process.env.NODE_ENV === 'development') {
      console.log('Ownership type:', ownershipType, 'Sections visibility:', sectionVisibility);
    }
  }

  /**
   * Calculate which sections should be visible for given ownership type
   * @param {string} ownershipType - The ownership type value
   * @returns {Object} Object mapping section IDs to visibility boolean
   */
  calculateSectionVisibility(ownershipType) {
    const visibility = {};

    Object.entries(LandInfoManager.SECTION_VISIBILITY_RULES).forEach(([sectionId, allowedTypes]) => {
      visibility[sectionId] = allowedTypes.includes(ownershipType);
    });

    return visibility;
  }

  /**
   * Apply section visibility changes with smooth animations
   * @param {Object} sectionVisibility - Object mapping section IDs to visibility
   */
  applySectionVisibility(sectionVisibility) {
    console.log('🔄 Applying section visibility:', sectionVisibility);

    Object.entries(sectionVisibility).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);

      if (!section) {
        console.warn(`⚠️ Section element not found: ${sectionId}`);
        return;
      }

      console.log(`Processing section ${sectionId}: shouldShow=${shouldShow}, current display=${section.style.display}`);

      // Use requestAnimationFrame for smooth transitions
      requestAnimationFrame(() => {
        if (shouldShow) {
          section.style.display = 'block';
          section.style.visibility = 'visible';
          section.classList.remove('d-none', 'hide');
          section.classList.add('d-block', 'fade-in', 'show-highlight');

          // ハイライト効果を一定時間後に削除
          setTimeout(() => {
            section.classList.remove('show-highlight');
          }, 1000);

          console.log(`✅ Showing section: ${sectionId}`);
        } else {
          section.style.display = 'none';
          section.style.visibility = 'hidden';
          section.classList.remove('d-block', 'fade-in', 'show-highlight', 'show');
          section.classList.add('d-none', 'hide');
          console.log(`❌ Hiding section: ${sectionId}`);
        }

        // Set accessibility attributes
        section.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
      });
    });
  }

  /**
   * Clear fields in conditional sections when ownership type changes
   */
  clearConditionalFields() {
    const ownershipType = this.getOwnershipTypeValue();
    const fieldsToClear = this.determineFieldsToClear(ownershipType);

    // Clear fields efficiently using cached DOM elements or batch queries
    this.clearFieldsBatch(fieldsToClear);
  }

  /**
   * Determine which fields should be cleared based on ownership type
   * @param {string} ownershipType - Current ownership type
   * @returns {string[]} Array of field IDs to clear
   */
  determineFieldsToClear(ownershipType) {
    const fieldsToClear = [];

    // Clear owned fields if not owned or owned_rental type
    if (![LandInfoManager.OWNERSHIP_TYPES.OWNED, LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL].includes(ownershipType)) {
      fieldsToClear.push(...LandInfoManager.FIELD_GROUPS.owned);
    }

    // Clear leased fields if not leased or owned_rental
    if (![LandInfoManager.OWNERSHIP_TYPES.LEASED, LandInfoManager.OWNERSHIP_TYPES.OWNED_RENTAL].includes(ownershipType)) {
      fieldsToClear.push(...LandInfoManager.FIELD_GROUPS.leased);
    }

    // Clear management and owner fields if not leased
    if (ownershipType !== LandInfoManager.OWNERSHIP_TYPES.LEASED) {
      fieldsToClear.push(...LandInfoManager.FIELD_GROUPS.management);
      fieldsToClear.push(...LandInfoManager.FIELD_GROUPS.owner);
    }

    return fieldsToClear;
  }

  /**
   * Clear multiple fields efficiently in a single batch operation
   * @param {string[]} fieldIds - Array of field IDs to clear
   */
  clearFieldsBatch(fieldIds) {
    fieldIds.forEach(fieldId => {
      const field = this.domCache?.[fieldId] || document.getElementById(fieldId);
      if (field && field.value) {
        field.value = '';
        field.classList.remove('is-invalid', 'calculated');

        // Trigger change event for any listeners
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  /**
   * Calculate unit price per tsubo (legacy method for compatibility)
   */
  calculateUnitPrice() {
    return this.calculateUnitPriceOptimized();
  }

  /**
   * Optimized unit price calculation using cached DOM elements
   */
  calculateUnitPriceOptimized() {
    const purchasePriceInput = this.domCache?.purchasePrice || document.getElementById('purchase_price');
    const siteAreaTsuboInput = this.domCache?.siteAreaTsubo || document.getElementById('site_area_tsubo');
    const unitPriceDisplay = this.domCache?.unitPriceDisplay || document.getElementById('unit_price_display');

    if (!purchasePriceInput || !siteAreaTsuboInput || !unitPriceDisplay) return null;

    const purchasePrice = parseFloat(purchasePriceInput.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(siteAreaTsuboInput.value) || 0;

    if (purchasePrice > 0 && siteAreaTsubo > 0) {
      const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
      const formattedPrice = unitPrice.toLocaleString();

      // Use requestAnimationFrame for smooth UI updates
      requestAnimationFrame(() => {
        unitPriceDisplay.value = formattedPrice;
        this.addCalculationFeedback(unitPriceDisplay);
      });

      // 計算結果の妥当性チェック (throttled)
      this.throttledValidationCheck(unitPrice, '坪単価が非常に高額です。入力内容をご確認ください。', 10000000);

      return { unitPrice, formattedPrice };
    } else {
      requestAnimationFrame(() => {
        unitPriceDisplay.value = '';
      });
      return null;
    }
  }

  /**
   * Calculate contract period (legacy method for compatibility)
   */
  calculateContractPeriod() {
    return this.calculateContractPeriodOptimized();
  }

  /**
   * Optimized contract period calculation using cached DOM elements
   */
  calculateContractPeriodOptimized() {
    const startDateInput = this.domCache?.contractStartDate || document.getElementById('contract_start_date');
    const endDateInput = this.domCache?.contractEndDate || document.getElementById('contract_end_date');
    const periodDisplay = this.domCache?.contractPeriodDisplay || document.getElementById('contract_period_display');

    if (!startDateInput || !endDateInput || !periodDisplay) return null;

    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);

    if (startDate && endDate && endDate > startDate) {
      const years = endDate.getFullYear() - startDate.getFullYear();
      const months = endDate.getMonth() - startDate.getMonth();

      let totalMonths = years * 12 + months;

      // 日付の調整
      if (endDate.getDate() < startDate.getDate()) {
        totalMonths--;
      }

      const displayYears = Math.floor(totalMonths / 12);
      const displayMonths = totalMonths % 12;

      let periodText = '';
      if (displayYears > 0) periodText += `${displayYears}年`;
      if (displayMonths > 0) periodText += `${displayMonths}ヶ月`;

      const finalText = periodText || '0ヶ月';

      // Use requestAnimationFrame for smooth UI updates
      requestAnimationFrame(() => {
        periodDisplay.value = finalText;
        this.addCalculationFeedback(periodDisplay);
      });

      // 契約期間の妥当性チェック (throttled)
      this.throttledValidationCheck(totalMonths, '契約期間が非常に長期です。入力内容をご確認ください。', 600);

      return { totalMonths, periodText: finalText };
    } else {
      requestAnimationFrame(() => {
        periodDisplay.value = '';
      });
      return null;
    }
  }

  /**
   * Throttled validation check to prevent excessive warnings
   */
  throttledValidationCheck(value, message, threshold) {
    const key = `validation_${message}`;
    const now = Date.now();

    if (!this.lastValidationCheck) {
      this.lastValidationCheck = {};
    }

    // Only show warning if it hasn't been shown in the last 5 seconds
    if (!this.lastValidationCheck[key] || (now - this.lastValidationCheck[key]) > 5000) {
      if (value > threshold) {
        this.showCalculationWarning(message);
        this.lastValidationCheck[key] = now;
      }
    }
  }

  /**
   * Apply calculation result with error handling
   */
  applyCalculationResult(fieldId, result) {
    if (!result) return;

    try {
      if (['purchase_price', 'site_area_tsubo'].includes(fieldId) && result.formattedPrice) {
        const display = this.domCache?.unitPriceDisplay || document.getElementById('unit_price_display');
        if (display) display.value = result.formattedPrice;
      } else if (['contract_start_date', 'contract_end_date'].includes(fieldId) && result.periodText) {
        const display = this.domCache?.contractPeriodDisplay || document.getElementById('contract_period_display');
        if (display) display.value = result.periodText;
      }
    } catch (error) {
      console.error('Error applying calculation result:', error);
    }
  }

  /**
   * Load section content lazily
   */
  loadSectionContent(section) {
    // This would load heavy content for the section
    // For now, just mark as loaded
    section.setAttribute('data-loaded', 'true');
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
   * Convert full-width numbers to half-width and sanitize input
   */
  convertToHalfWidth(input) {
    if (!input || !input.value) return;

    // Convert full-width to half-width numbers
    let sanitizedValue = input.value.replace(/[０-９]/g, function (s) {
      return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
    });

    // Additional sanitization based on input type
    if (input.type === 'number' || input.classList.contains('currency-input')) {
      // Remove non-numeric characters except decimal point and comma
      sanitizedValue = sanitizedValue.replace(/[^\d.,\-]/g, '');
    }

    // Prevent XSS by limiting input length and characters
    if (sanitizedValue.length > 50) {
      sanitizedValue = sanitizedValue.substring(0, 50);
    }

    input.value = sanitizedValue;
  }

  /**
   * Initialize character count for textareas
   */
  initializeCharacterCount() {
    const textareas = [
      { id: 'notes', countId: 'notes_count' },
      { id: 'management_company_notes', countId: 'management_company_notes_count' },
      { id: 'owner_notes', countId: 'owner_notes_count' }
    ];

    textareas.forEach(({ id, countId }) => {
      const textarea = document.getElementById(id);
      const counter = document.getElementById(countId);

      if (textarea && counter) {
        textarea.addEventListener('input', function () {
          counter.textContent = this.value.length;

          // 文字数制限に近づいたら警告
          const maxLength = parseInt(this.getAttribute('maxlength'));
          if (this.value.length > maxLength * 0.9) {
            counter.classList.add('text-warning');
          } else {
            counter.classList.remove('text-warning');
          }
        });
      }
    });
  }

  /**
   * Validate form before submission
   */
  validateForm() {
    let isValid = true;
    const errors = [];

    // Clear previous validation errors
    this.clearValidationErrors();

    // 所有形態は必須
    const ownershipType = document.getElementById('ownership_type');
    if (!ownershipType || !ownershipType.value) {
      errors.push('所有形態を選択してください。');
      if (ownershipType) ownershipType.classList.add('is-invalid');
      isValid = false;
    }

    // 契約期間の妥当性チェック
    const startDate = document.getElementById('contract_start_date');
    const endDate = document.getElementById('contract_end_date');

    if (startDate && endDate && startDate.value && endDate.value) {
      if (new Date(startDate.value) >= new Date(endDate.value)) {
        errors.push('契約終了日は契約開始日より後の日付を入力してください。');
        endDate.classList.add('is-invalid');
        isValid = false;
      }
    }

    // 数値フィールドの妥当性チェック
    const numericFields = [
      { id: 'parking_spaces', name: '敷地内駐車場台数', max: 9999999999 },
      { id: 'site_area_sqm', name: '敷地面積(㎡)', max: 99999999.99 },
      { id: 'site_area_tsubo', name: '敷地面積(坪数)', max: 99999999.99 },
      { id: 'purchase_price', name: '購入金額', max: 999999999999999 },
      { id: 'monthly_rent', name: '家賃', max: 999999999999999 }
    ];

    numericFields.forEach(field => {
      const element = document.getElementById(field.id);
      if (element && element.value) {
        const value = parseFloat(element.value.replace(/,/g, ''));
        if (value < 0) {
          errors.push(`${field.name}は0以上の値を入力してください。`);
          element.classList.add('is-invalid');
          isValid = false;
        } else if (value > field.max) {
          errors.push(`${field.name}は${field.max.toLocaleString()}以下の値を入力してください。`);
          element.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // メールアドレスの妥当性チェック
    const emailFields = ['management_company_email', 'owner_email'];
    emailFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
          errors.push('正しいメールアドレス形式で入力してください。');
          field.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // URLの妥当性チェック
    const urlFields = ['management_company_url', 'owner_url'];
    urlFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        try {
          new URL(field.value);
        } catch {
          errors.push('正しいURL形式で入力してください。');
          field.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // エラーメッセージ表示
    if (!isValid) {
      this.showValidationErrors(errors);
    } else {
      this.showSuccessMessage('入力内容に問題ありません。');
    }

    return isValid;
  }

  /**
   * Validate file size
   */
  validateFileSize(input) {
    const maxSize = 10 * 1024 * 1024; // 10MB

    Array.from(input.files).forEach(file => {
      if (file.size > maxSize) {
        alert(`ファイル "${file.name}" のサイズが大きすぎます。10MB以下のファイルを選択してください。`);
        input.value = '';
      }
    });
  }

  /**
   * Show validation errors
   */
  showValidationErrors(errors) {
    const errorContainer = document.getElementById('validation-errors');
    if (errorContainer) {
      errorContainer.innerHTML = errors.map(error =>
        `<div class="alert alert-danger">${error}</div>`
      ).join('');
      errorContainer.scrollIntoView({ behavior: 'smooth' });
    } else {
      alert(errors.join('\n'));
    }
  }

  /**
   * Clear validation errors
   */
  clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(element => {
      element.classList.remove('is-invalid');
    });

    const errorContainer = document.getElementById('validation-errors');
    if (errorContainer) {
      errorContainer.innerHTML = '';
    }

    const successContainer = document.getElementById('validation-success');
    if (successContainer) {
      successContainer.innerHTML = '';
    }
  }

  /**
   * Show success message
   */
  showSuccessMessage(message) {
    const successContainer = document.getElementById('validation-success');
    if (successContainer) {
      successContainer.innerHTML = `<div class="alert alert-success">${message}</div>`;
      setTimeout(() => {
        successContainer.innerHTML = '';
      }, 3000);
    }
  }

  /**
   * Add visual feedback for calculations
   */
  addCalculationFeedback(element) {
    if (element) {
      element.classList.add('calculated');
      setTimeout(() => {
        element.classList.remove('calculated');
      }, 1000);
    }
  }

  /**
   * Format area display with units
   */
  formatAreaDisplay(value, unit) {
    if (!value || value === 0) return '';
    const formattedValue = parseFloat(value).toFixed(2);
    return `${formattedValue}${unit}`;
  }

  /**
   * Validate and format phone number
   */
  validatePhoneNumber(phoneNumber) {
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
  validatePostalCode(postalCode) {
    // Remove all non-digit characters
    const digits = postalCode.replace(/\D/g, '');

    // Format as XXX-XXXX
    if (digits.length === 7) {
      return `${digits.slice(0, 3)}-${digits.slice(3)}`;
    }

    return postalCode; // Return original if doesn't match expected format
  }

  /**
   * Show calculation warning
   */
  showCalculationWarning(message) {
    const warningContainer = document.getElementById('calculation-warnings');
    if (warningContainer) {
      warningContainer.innerHTML = `<div class="alert alert-warning alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>`;
    } else {
      // Fallback to console warning if no container
      console.warn('Calculation Warning:', message);
    }
  }

  /**
   * Auto-save functionality (draft save)
   */
  enableAutoSave() {
    let autoSaveTimeout;
    const autoSaveForm = document.getElementById('landInfoForm');

    if (!autoSaveForm) return;

    const triggerAutoSave = () => {
      clearTimeout(autoSaveTimeout);
      autoSaveTimeout = setTimeout(() => {
        this.saveDraft();
      }, 5000); // Auto-save after 5 seconds of inactivity
    };

    // Add listeners to all form inputs
    autoSaveForm.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('input', triggerAutoSave);
      input.addEventListener('change', triggerAutoSave);
    });
  }

  /**
   * Save form data as draft
   */
  saveDraft() {
    const draftForm = document.getElementById('landInfoForm');
    if (!draftForm) return;

    const formData = new FormData(draftForm);
    const draftData = {};

    for (let [key, value] of formData.entries()) {
      draftData[key] = value;
    }

    // Save to localStorage
    localStorage.setItem('landInfoDraft', JSON.stringify(draftData));

    // Show save indicator
    this.showDraftSaveIndicator();
  }

  /**
   * Load draft data
   */
  loadDraft() {
    const draftData = localStorage.getItem('landInfoDraft');
    if (!draftData) return;

    try {
      const data = JSON.parse(draftData);

      Object.entries(data).forEach(([key, value]) => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
          // Skip file inputs as they cannot be programmatically set for security reasons
          if (field.type === 'file') {
            return;
          }
          field.value = value;
        }
      });

      // Trigger calculations after loading
      this.calculateUnitPrice();
      this.calculateContractPeriod();
      this.updateConditionalSections();

    } catch (error) {
      console.error('Error loading draft:', error);
    }
  }

  /**
   * Clear draft data
   */
  clearDraft() {
    localStorage.removeItem('landInfoDraft');
  }

  /**
   * Show draft save indicator
   */
  showDraftSaveIndicator() {
    const indicator = document.getElementById('draft-save-indicator');
    if (indicator) {
      indicator.textContent = '下書き保存済み';
      indicator.classList.add('show');

      setTimeout(() => {
        indicator.classList.remove('show');
      }, 2000);
    }
  }
}

// File deletion functionality
function deleteFile(fileId) {
  if (confirm('このファイルを削除しますか？')) {
    fetch(`/files/${fileId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json',
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('ファイルの削除に失敗しました。');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('エラーが発生しました。');
      });
  }
}

// Real-time calculation API call
function calculateFieldsRealtime() {
  const formData = new FormData(document.getElementById('landInfoForm'));

  fetch(window.location.pathname + '/calculate', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update calculated fields
        if (data.unit_price) {
          document.getElementById('unit_price_display').value = data.unit_price;
        }
        if (data.contract_period) {
          document.getElementById('contract_period_display').value = data.contract_period;
        }
      }
    })
    .catch(error => {
      console.error('Calculation error:', error);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
  console.log('🚀 DOMContentLoaded - Initializing LandInfoManager');

  // Check if we're on the correct page
  const form = document.getElementById('landInfoForm');
  if (!form) {
    console.log('❌ landInfoForm not found, skipping LandInfoManager initialization');
    return;
  }

  const landInfoManager = new LandInfoManager();

  // 初期状態のセクション表示を同期（select/radio 両対応）
  landInfoManager.updateConditionalSections();

  // Make landInfoManager globally accessible
  window.landInfoManager = landInfoManager;

  // Additional event listener setup as fallback
  setTimeout(() => {
    console.log('🔄 Setting up fallback event listeners');
    const ownershipSelect = document.getElementById('ownership_type');
    if (ownershipSelect) {
      // Remove existing listeners and add new ones
      const newSelect = ownershipSelect.cloneNode(true);
      ownershipSelect.parentNode.replaceChild(newSelect, ownershipSelect);

      newSelect.addEventListener('change', function (e) {
        console.log('🎯 Fallback change event triggered:', e.target.value);
        landInfoManager.updateConditionalSections();
        setTimeout(() => {
          landInfoManager.clearConditionalFields();
          landInfoManager.clearValidationErrors();
        }, 50);
      });

      console.log('✅ Fallback event listener added');
    }
  }, 500);

  // Enable auto-save functionality
  landInfoManager.enableAutoSave();

  // Load draft data if available
  landInfoManager.loadDraft();

  // Clear draft on successful form submission
  if (form) {
    form.addEventListener('submit', function (e) {
      if (landInfoManager.validateForm()) {
        landInfoManager.clearDraft();
      }
    });
  }

  // Preview functionality
  document.getElementById('previewBtn')?.addEventListener('click', function () {
    // Open preview in new window/modal
    const previewForm = document.getElementById('landInfoForm');
    const formData = new FormData(previewForm);

    // Create preview content
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
            <html>
                <head>
                    <title>土地情報プレビュー</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                </head>
                <body class="p-4">
                    <h2>土地情報プレビュー</h2>
                    <div class="alert alert-info">
                        これは保存前のプレビューです。実際の保存は元の画面で行ってください。
                    </div>
                    <div id="preview-content">
                        <!-- Preview content will be generated here -->
                    </div>
                </body>
            </html>
        `);

    // Generate preview content based on form data
    // This would be implemented based on specific requirements
  });

  // Manual validation trigger
  document.getElementById('validateBtn')?.addEventListener('click', function () {
    landInfoManager.validateForm();
  });
});

// Export for use in other files
window.LandInfoManager = LandInfoManager;
window.deleteFile = deleteFile;
window.calculateFieldsRealtime = calculateFieldsRealtime;

// Test functions for debugging
window.testOwnershipChange = function (value) {
  console.log('🧪 Testing ownership change to:', value);
  const select = document.getElementById('ownership_type');
  if (select) {
    select.value = value;
    if (window.landInfoManager) {
      window.landInfoManager.updateConditionalSections();
    } else {
      console.error('❌ landInfoManager not found');
    }
  } else {
    console.error('❌ ownership_type select not found');
  }
};

window.debugSections = function () {
  console.log('🔍 Current section states:');
  const sections = ['owned_section', 'leased_section', 'management_section', 'owner_section', 'file_section'];
  sections.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
      console.log(`${id}: display=${element.style.display}, visible=${element.style.visibility}, classes=${element.className}`);
    } else {
      console.log(`${id}: NOT FOUND`);
    }
  });
};