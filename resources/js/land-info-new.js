/**
 * Modern Land Information Form Manager
 * Refactored for better maintainability and performance
 */

// Import the new modular LandInfoManager
import { LandInfoManager } from './modules/land-info/LandInfoManager.js';

// Initialize when DOM is loaded with comprehensive error handling
document.addEventListener('DOMContentLoaded', function () {
  console.log('🚀 DOMContentLoaded - Initializing LandInfoManager');

  // Check if we're on the correct page first
  if (!isLandInfoPage()) {
    console.log('ℹ️ Not on land info page, skipping initialization');
    return;
  }

  try {
    const landInfoManager = new LandInfoManager();

    // Make landInfoManager globally accessible for backward compatibility
    window.landInfoManager = landInfoManager;

    console.log('✅ LandInfoManager initialized successfully');

    // Setup additional functionality with error handling
    setupAdditionalFunctionality(landInfoManager);

  } catch (error) {
    console.error('❌ Failed to initialize LandInfoManager:', error);

    // Implement fallback functionality for module loading failures
    console.log('🔄 Implementing fallback functionality for module loading failures');
    initializeBasicFunctionality();

    // Report error for monitoring
    reportInitializationError(error);
  }
});

/**
 * Check if we're on the land info page
 * @returns {boolean}
 */
function isLandInfoPage() {
  const form = document.getElementById('landInfoForm');
  const ownershipSelect = document.getElementById('ownership_type');

  return !!(form && ownershipSelect &&
    (window.location.pathname.includes('/land-info/') ||
      document.body.classList.contains('land-info-page')));
}

/**
 * Setup additional functionality with error handling
 * @param {LandInfoManager} landInfoManager 
 */
function setupAdditionalFunctionality(landInfoManager) {
  try {
    setupPreviewFunctionality();
    setupValidationTrigger();
    setupDebugFunctions();

    // Log performance metrics periodically in development
    if (process.env.NODE_ENV === 'development') {
      setInterval(() => {
        try {
          console.log('📊 Performance Metrics:', landInfoManager.getMetrics());
        } catch (error) {
          console.warn('Failed to get performance metrics:', error);
        }
      }, 30000);
    }

    console.log('✅ Additional functionality setup complete');

  } catch (error) {
    console.warn('⚠️ Some additional functionality failed to initialize:', error);
    // Continue without additional features
  }
}

/**
 * Report initialization error for monitoring
 * @param {Error} error 
 */
function reportInitializationError(error) {
  try {
    // Emit error event for monitoring systems
    if (typeof window.dispatchEvent === 'function') {
      window.dispatchEvent(new CustomEvent('landInfoInitializationError', {
        detail: {
          error: error.message,
          stack: error.stack,
          timestamp: new Date().toISOString(),
          userAgent: navigator.userAgent,
          url: window.location.href
        }
      }));
    }

    // Send to external monitoring if available
    if (window.Sentry) {
      window.Sentry.captureException(error, {
        tags: { context: 'land_info_initialization' }
      });
    }

  } catch (reportError) {
    console.error('Failed to report initialization error:', reportError);
  }
}

/**
 * Fallback initialization for basic functionality when modules fail to load
 */
function initializeBasicFunctionality() {
  console.log('🔧 Initializing basic fallback functionality...');

  const form = document.getElementById('landInfoForm');
  if (!form) {
    console.warn('⚠️ Land info form not found, cannot initialize fallback');
    return;
  }

  try {
    // Basic ownership type change handling with section visibility
    initializeBasicSectionVisibility();

    // Basic calculation functionality
    initializeBasicCalculations();

    // Basic form validation
    initializeBasicValidation();

    // Basic error handling
    initializeBasicErrorHandling();

    console.log('✅ Basic fallback functionality initialized');

  } catch (error) {
    console.error('❌ Failed to initialize even basic functionality:', error);
  }
}

/**
 * Initialize basic section visibility as fallback
 */
function initializeBasicSectionVisibility() {
  const ownershipSelect = document.getElementById('ownership_type');
  if (!ownershipSelect) return;

  const sectionRules = {
    'owned_section': ['owned', 'owned_rental'],
    'leased_section': ['leased', 'owned_rental'],
    'management_section': ['leased'],
    'owner_section': ['leased'],
    'file_section': ['leased', 'owned_rental']
  };

  function updateSections(ownershipType) {
    Object.entries(sectionRules).forEach(([sectionId, allowedTypes]) => {
      const section = document.getElementById(sectionId);
      if (section) {
        const shouldShow = allowedTypes.includes(ownershipType);

        if (shouldShow) {
          section.classList.remove('d-none');
          section.classList.add('d-block');
          section.setAttribute('aria-hidden', 'false');
          section.setAttribute('aria-expanded', 'true');
        } else {
          section.classList.remove('d-block');
          section.classList.add('d-none');
          section.setAttribute('aria-hidden', 'true');
          section.setAttribute('aria-expanded', 'false');

          // Clear fields in hidden sections
          const inputs = section.querySelectorAll('input, select, textarea');
          inputs.forEach(input => {
            if (input.value) {
              input.value = '';
              input.classList.remove('is-invalid');
            }
          });
        }
      }
    });
  }

  // Handle initial state
  if (ownershipSelect.value) {
    updateSections(ownershipSelect.value);
  }

  // Handle changes
  ownershipSelect.addEventListener('change', function (e) {
    console.log('Basic fallback: ownership type changed to', e.target.value);
    updateSections(e.target.value);
  });
}

/**
 * Initialize basic calculations as fallback
 */
function initializeBasicCalculations() {
  // Basic unit price calculation
  const purchasePrice = document.getElementById('purchase_price');
  const siteAreaTsubo = document.getElementById('site_area_tsubo');
  const unitPriceDisplay = document.getElementById('unit_price_display');

  if (purchasePrice && siteAreaTsubo && unitPriceDisplay) {
    function calculateUnitPrice() {
      const price = parseFloat(purchasePrice.value.replace(/,/g, '')) || 0;
      const area = parseFloat(siteAreaTsubo.value) || 0;

      if (price > 0 && area > 0) {
        const unitPrice = Math.round(price / area);
        unitPriceDisplay.value = unitPrice.toLocaleString();
      } else {
        unitPriceDisplay.value = '';
      }
    }

    purchasePrice.addEventListener('input', calculateUnitPrice);
    siteAreaTsubo.addEventListener('input', calculateUnitPrice);

    // Initial calculation
    calculateUnitPrice();
  }

  // Basic contract period calculation with edge case handling
  const startDate = document.getElementById('contract_start_date');
  const endDate = document.getElementById('contract_end_date');
  const periodDisplay = document.getElementById('contract_period_display');

  if (startDate && endDate && periodDisplay) {
    function calculatePeriod() {
      // Clear previous errors
      endDate.classList.remove('is-invalid');
      const existingError = endDate.parentNode.querySelector('.invalid-feedback');
      if (existingError) existingError.remove();

      if (startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);

        // Handle invalid date ranges (end date earlier than start date) with real-time errors
        if (end <= start) {
          periodDisplay.value = '';
          endDate.classList.add('is-invalid');

          // Show error message
          const errorDiv = document.createElement('div');
          errorDiv.className = 'invalid-feedback';
          errorDiv.textContent = '契約終了日は契約開始日より後の日付を入力してください。';
          endDate.parentNode.appendChild(errorDiv);
          return;
        }

        const years = end.getFullYear() - start.getFullYear();
        const months = end.getMonth() - start.getMonth();
        let totalMonths = years * 12 + months;

        if (end.getDate() < start.getDate()) {
          totalMonths--;
        }

        // Handle edge case where totalMonths might be negative
        if (totalMonths < 0) totalMonths = 0;

        const displayYears = Math.floor(totalMonths / 12);
        const displayMonths = totalMonths % 12;

        // Consider flexible formatting (years/months/days) for contract period display
        let periodText = '';
        if (displayYears > 0) periodText += `${displayYears}年`;
        if (displayMonths > 0) periodText += `${displayMonths}ヶ月`;

        // If no years or months, show days for precision
        if (displayYears === 0 && displayMonths === 0) {
          const timeDiff = end.getTime() - start.getTime();
          const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
          periodText = daysDiff > 0 ? `${daysDiff}日` : '0日';
        }

        periodDisplay.value = periodText || '0ヶ月';
      } else {
        periodDisplay.value = '';
      }
    }

    // Add real-time calculation updates on field changes
    startDate.addEventListener('change', calculatePeriod);
    startDate.addEventListener('input', calculatePeriod);
    endDate.addEventListener('change', calculatePeriod);
    endDate.addEventListener('input', calculatePeriod);

    // Initial calculation
    calculatePeriod();
  }
}

/**
 * Initialize basic form validation as fallback
 */
function initializeBasicValidation() {
  const form = document.getElementById('landInfoForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    let isValid = true;
    const errors = [];

    // Clear previous errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Basic ownership type validation
    const ownershipType = document.getElementById('ownership_type');
    if (!ownershipType || !ownershipType.value) {
      errors.push('所有形態を選択してください。');
      if (ownershipType) ownershipType.classList.add('is-invalid');
      isValid = false;
    }

    // Basic date validation
    const startDate = document.getElementById('contract_start_date');
    const endDate = document.getElementById('contract_end_date');

    if (startDate && endDate && startDate.value && endDate.value) {
      if (new Date(startDate.value) >= new Date(endDate.value)) {
        errors.push('契約終了日は契約開始日より後の日付を入力してください。');
        endDate.classList.add('is-invalid');
        isValid = false;
      }
    }

    if (!isValid) {
      e.preventDefault();
      alert('入力エラー:\n' + errors.join('\n'));
    }
  });
}

/**
 * Initialize basic error handling as fallback
 */
function initializeBasicErrorHandling() {
  // Global error handler for unhandled errors
  window.addEventListener('error', function (e) {
    console.error('Unhandled error in land info page:', e.error);
  });

  // Promise rejection handler
  window.addEventListener('unhandledrejection', function (e) {
    console.error('Unhandled promise rejection in land info page:', e.reason);
  });
}

/**
 * Setup preview functionality
 */
function setupPreviewFunctionality() {
  const previewBtn = document.getElementById('previewBtn');
  if (!previewBtn) return;

  previewBtn.addEventListener('click', function () {
    const previewForm = document.getElementById('landInfoForm');
    if (!previewForm) return;

    const formData = new FormData(previewForm);

    // Create preview content
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    if (!previewWindow) {
      alert('ポップアップがブロックされました。ブラウザの設定を確認してください。');
      return;
    }

    previewWindow.document.write(`
      <html>
        <head>
          <title>土地情報プレビュー</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
          <style>
            .preview-section { margin-bottom: 2rem; }
            .preview-label { font-weight: bold; color: #495057; }
            .preview-value { margin-left: 1rem; }
          </style>
        </head>
        <body class="p-4">
          <h2>土地情報プレビュー</h2>
          <div class="alert alert-info">
            これは保存前のプレビューです。実際の保存は元の画面で行ってください。
          </div>
          <div id="preview-content">
            ${generatePreviewContent(formData)}
          </div>
          <div class="mt-4">
            <button class="btn btn-secondary" onclick="window.close()">閉じる</button>
          </div>
        </body>
      </html>
    `);
  });
}

/**
 * Generate preview content from form data
 * @param {FormData} formData 
 * @returns {string}
 */
function generatePreviewContent(formData) {
  const sections = [
    {
      title: '基本情報',
      fields: [
        { key: 'ownership_type', label: '所有形態' },
        { key: 'parking_spaces', label: '敷地内駐車場台数' }
      ]
    },
    {
      title: '面積情報',
      fields: [
        { key: 'site_area_sqm', label: '敷地面積（㎡）' },
        { key: 'site_area_tsubo', label: '敷地面積（坪数）' }
      ]
    }
  ];

  let html = '';

  sections.forEach(section => {
    html += `<div class="preview-section">`;
    html += `<h4>${section.title}</h4>`;

    section.fields.forEach(field => {
      const value = formData.get(field.key) || '未入力';
      html += `<div class="row mb-2">`;
      html += `<div class="col-4 preview-label">${field.label}:</div>`;
      html += `<div class="col-8 preview-value">${value}</div>`;
      html += `</div>`;
    });

    html += `</div>`;
  });

  return html;
}

/**
 * Setup manual validation trigger
 */
function setupValidationTrigger() {
  const validateBtn = document.getElementById('validateBtn');
  if (!validateBtn) return;

  validateBtn.addEventListener('click', function () {
    if (window.landInfoManager && window.landInfoManager.validator) {
      const result = window.landInfoManager.validator.validateForm();

      if (result.isValid) {
        alert('✅ 入力内容に問題ありません。');
      } else {
        alert('❌ 入力エラーがあります:\n' + result.errors.join('\n'));
      }
    }
  });
}

/**
 * Setup debug functions for development
 */
function setupDebugFunctions() {
  // Test functions for debugging
  window.testOwnershipChange = function (value) {
    console.log('🧪 Testing ownership change to:', value);
    const select = document.getElementById('ownership_type');
    if (select) {
      select.value = value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
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

  window.getPerformanceMetrics = function () {
    if (window.landInfoManager) {
      console.table(window.landInfoManager.getMetrics());
    } else {
      console.log('LandInfoManager not available');
    }
  };

  window.clearCaches = function () {
    if (window.landInfoManager) {
      window.landInfoManager.calculator.clearCache();
      window.landInfoManager.domCache.clear();
      console.log('🧹 Caches cleared');
    }
  };
}

// Cleanup on page unload
window.addEventListener('beforeunload', function () {
  if (window.landInfoManager && typeof window.landInfoManager.destroy === 'function') {
    window.landInfoManager.destroy();
  }
});

// Export for backward compatibility
window.LandInfoManager = LandInfoManager;