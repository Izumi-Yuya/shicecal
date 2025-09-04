/**
 * Land Information Form Manager
 * Handles dynamic form behavior, calculations, and validations
 */

class LandInfoManager {
  constructor() {
    this.initializeEventListeners();
    this.updateConditionalSections();
    this.initializeCharacterCount();
  }

  /**
   * Initialize all event listeners
   */
  initializeEventListeners() {
    // 所有形態変更時の表示制御
    const ownershipType = document.getElementById('ownership_type');
    if (ownershipType) {
      ownershipType.addEventListener('change', () => {
        this.updateConditionalSections();
        this.clearConditionalFields();
        this.clearValidationErrors();
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
    const form = document.getElementById('landInfoForm');
    if (form) {
      form.addEventListener('submit', (e) => {
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
   * Initialize realtime calculation functionality
   */
  initializeRealtimeCalculation() {
    // デバウンス機能付きリアルタイム計算
    let calculationTimeout;

    const triggerCalculation = () => {
      clearTimeout(calculationTimeout);
      calculationTimeout = setTimeout(() => {
        this.calculateUnitPrice();
        this.calculateContractPeriod();
      }, 300); // 300ms デバウンス
    };

    // 計算に影響する全てのフィールドにリスナーを追加
    const calculationFields = [
      'purchase_price', 'site_area_tsubo',
      'contract_start_date', 'contract_end_date'
    ];

    calculationFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.addEventListener('input', triggerCalculation);
        field.addEventListener('change', triggerCalculation);
      }
    });
  }

  /**
   * Update conditional sections based on ownership type
   */
  updateConditionalSections() {
    const ownershipType = document.getElementById('ownership_type').value;

    // セクションの表示/非表示制御
    const sections = {
      owned_section: ownershipType === 'owned',
      leased_section: ['leased', 'owned_rental'].includes(ownershipType),
      management_section: ownershipType === 'leased',
      owner_section: ownershipType === 'leased',
      file_section: ['leased', 'owned_rental'].includes(ownershipType)
    };

    Object.entries(sections).forEach(([sectionId, shouldShow]) => {
      const section = document.getElementById(sectionId);
      if (section) {
        section.style.display = shouldShow ? 'block' : 'none';

        // アニメーション効果
        if (shouldShow) {
          section.classList.add('fade-in');
        } else {
          section.classList.remove('fade-in');
        }
      }
    });
  }

  /**
   * Clear fields in conditional sections when ownership type changes
   */
  clearConditionalFields() {
    const ownershipType = document.getElementById('ownership_type').value;

    // 自社以外の場合、購入関連フィールドをクリア
    if (ownershipType !== 'owned') {
      const ownedFields = ['purchase_price', 'unit_price_display'];
      ownedFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = '';
      });
    }

    // 賃借以外の場合、賃借関連フィールドをクリア
    if (!['leased', 'owned_rental'].includes(ownershipType)) {
      const leasedFields = [
        'monthly_rent', 'contract_start_date', 'contract_end_date',
        'auto_renewal', 'contract_period_display'
      ];
      leasedFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = '';
      });
    }

    // 賃借以外の場合、管理会社・オーナー情報をクリア
    if (ownershipType !== 'leased') {
      const managementFields = [
        'management_company_name', 'management_company_postal_code',
        'management_company_address', 'management_company_building',
        'management_company_phone', 'management_company_fax',
        'management_company_email', 'management_company_url',
        'management_company_notes'
      ];

      const ownerFields = [
        'owner_name', 'owner_postal_code', 'owner_address',
        'owner_building', 'owner_phone', 'owner_fax',
        'owner_email', 'owner_url', 'owner_notes'
      ];

      [...managementFields, ...ownerFields].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) field.value = '';
      });
    }
  }

  /**
   * Calculate unit price per tsubo
   */
  calculateUnitPrice() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const siteAreaTsuboInput = document.getElementById('site_area_tsubo');
    const unitPriceDisplay = document.getElementById('unit_price_display');

    if (!purchasePriceInput || !siteAreaTsuboInput || !unitPriceDisplay) return;

    const purchasePrice = parseFloat(purchasePriceInput.value.replace(/,/g, '')) || 0;
    const siteAreaTsubo = parseFloat(siteAreaTsuboInput.value) || 0;

    if (purchasePrice > 0 && siteAreaTsubo > 0) {
      const unitPrice = Math.round(purchasePrice / siteAreaTsubo);
      unitPriceDisplay.value = unitPrice.toLocaleString();

      // 視覚的フィードバック
      this.addCalculationFeedback(unitPriceDisplay);

      // 計算結果の妥当性チェック
      if (unitPrice > 10000000) { // 1000万円/坪を超える場合は警告
        this.showCalculationWarning('坪単価が非常に高額です。入力内容をご確認ください。');
      }
    } else {
      unitPriceDisplay.value = '';
    }
  }

  /**
   * Calculate contract period
   */
  calculateContractPeriod() {
    const startDateInput = document.getElementById('contract_start_date');
    const endDateInput = document.getElementById('contract_end_date');
    const periodDisplay = document.getElementById('contract_period_display');

    if (!startDateInput || !endDateInput || !periodDisplay) return;

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

      periodDisplay.value = periodText || '0ヶ月';

      // 視覚的フィードバック
      this.addCalculationFeedback(periodDisplay);

      // 契約期間の妥当性チェック
      if (totalMonths > 600) { // 50年を超える場合は警告
        this.showCalculationWarning('契約期間が非常に長期です。入力内容をご確認ください。');
      }
    } else {
      periodDisplay.value = '';
    }
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
    const form = document.getElementById('landInfoForm');

    if (!form) return;

    const triggerAutoSave = () => {
      clearTimeout(autoSaveTimeout);
      autoSaveTimeout = setTimeout(() => {
        this.saveDraft();
      }, 5000); // Auto-save after 5 seconds of inactivity
    };

    // Add listeners to all form inputs
    form.querySelectorAll('input, select, textarea').forEach(input => {
      input.addEventListener('input', triggerAutoSave);
      input.addEventListener('change', triggerAutoSave);
    });
  }

  /**
   * Save form data as draft
   */
  saveDraft() {
    const form = document.getElementById('landInfoForm');
    if (!form) return;

    const formData = new FormData(form);
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
  const landInfoManager = new LandInfoManager();

  // Enable auto-save functionality
  landInfoManager.enableAutoSave();

  // Load draft data if available
  landInfoManager.loadDraft();

  // Clear draft on successful form submission
  const form = document.getElementById('landInfoForm');
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
    const form = document.getElementById('landInfoForm');
    const formData = new FormData(form);

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