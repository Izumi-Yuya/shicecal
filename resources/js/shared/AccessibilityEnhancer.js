/**
 * アクセシビリティ強化モジュール
 * 
 * フォーム要素のアクセシビリティを向上させるためのユーティリティ
 */

class AccessibilityEnhancer {
  constructor() {
    // Singleton pattern implementation
    if (AccessibilityEnhancer.instance) {
      return AccessibilityEnhancer.instance;
    }

    this.initialized = false;

    // Configuration constants
    this.config = {
      timing: {
        focusDelay: 100, // ms - delay for modal focus to ensure DOM is ready
        debounceDelay: 250 // ms - debounce delay for DOM change handling
      },
      selectors: {
        unlabeledInputs: 'input:not([aria-label]):not([aria-labelledby])',
        requiredInputs: 'input[required], select[required], textarea[required]',
        helpTexts: '.form-text, .help-text',
        errorMessages: '.invalid-feedback, .error-message',
        focusableElements: 'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
      }
    };

    AccessibilityEnhancer.instance = this;
    return this;
  }

  /**
   * Gets the singleton instance of AccessibilityEnhancer
   * @returns {AccessibilityEnhancer} The singleton instance
   * @static
   */
  static getInstance() {
    if (!AccessibilityEnhancer.instance) {
      AccessibilityEnhancer.instance = new AccessibilityEnhancer();
    }
    return AccessibilityEnhancer.instance;
  }

  /**
   * 初期化
   * @returns {void}
   */
  init() {
    if (this.initialized) return;

    try {
      this.enhanceFormAccessibility();
      this.setupKeyboardNavigation();
      this.setupFocusManagement();
      this.setupCleanup();

      this.initialized = true;
      console.log('AccessibilityEnhancer initialized');
    } catch (error) {
      console.error('AccessibilityEnhancer: Initialization failed:', error);
    }
  }

  /**
   * Sets up cleanup handlers for memory leak prevention
   * @returns {void}
   */
  setupCleanup() {
    window.addEventListener('beforeunload', () => {
      this.destroy();
    });
  }

  /**
   * Cleanup method to prevent memory leaks
   * @returns {void}
   */
  destroy() {
    // Clean up any stored references
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      delete modal._previousFocus;
    });

    this.initialized = false;
  }

  /**
   * フォームのアクセシビリティを強化
   * @returns {void}
   */
  enhanceFormAccessibility() {
    try {
      this.associateLabelsWithInputs();
      this.addRequiredAttributes();
      this.associateHelpTexts();
    } catch (error) {
      console.error('AccessibilityEnhancer: Failed to enhance form accessibility:', error);
    }
  }

  /**
   * Associates labels with unlabeled input elements
   * @returns {void}
   */
  associateLabelsWithInputs() {
    const unlabeledInputs = document.querySelectorAll(this.config.selectors.unlabeledInputs);
    unlabeledInputs.forEach(input => {
      const label = this.findAssociatedLabel(input);
      if (label && !input.getAttribute('aria-label')) {
        this.linkLabelToInput(label, input);
      }
    });
  }

  /**
   * Links a label to an input element
   * @param {HTMLLabelElement} label - The label element
   * @param {HTMLInputElement} input - The input element
   * @returns {void}
   */
  linkLabelToInput(label, input) {
    if (!label.getAttribute('for') && input.id) {
      label.setAttribute('for', input.id);
    } else if (!input.id) {
      const id = this.generateUniqueId('input');
      input.id = id;
      label.setAttribute('for', id);
    }
  }

  /**
   * Adds aria-required attributes to required form fields
   * @returns {void}
   */
  addRequiredAttributes() {
    const requiredInputs = document.querySelectorAll(this.config.selectors.requiredInputs);
    requiredInputs.forEach(input => {
      if (!input.getAttribute('aria-required')) {
        input.setAttribute('aria-required', 'true');
      }
    });
  }

  /**
   * Associates help texts with form inputs using aria-describedby
   * @returns {void}
   */
  associateHelpTexts() {
    const helpTexts = document.querySelectorAll(this.config.selectors.helpTexts);
    helpTexts.forEach(helpText => {
      const input = this.findAssociatedInput(helpText);
      if (input && helpText.id) {
        const describedBy = input.getAttribute('aria-describedby') || '';
        if (!describedBy.includes(helpText.id)) {
          input.setAttribute('aria-describedby',
            describedBy ? `${describedBy} ${helpText.id}` : helpText.id);
        }
      }
    });
  }

  /**
   * キーボードナビゲーションを設定
   */
  setupKeyboardNavigation() {
    // モーダル内でのTabキー循環
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
          this.handleModalTabNavigation(e, modal);
        }
      });
    });

    // ドロップダウンメニューのキーボードナビゲーション
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
      const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
      const menu = dropdown.querySelector('.dropdown-menu');

      if (toggle && menu) {
        toggle.addEventListener('keydown', (e) => {
          this.handleDropdownKeyNavigation(e, dropdown);
        });
      }
    });
  }

  /**
   * フォーカス管理を設定
   */
  setupFocusManagement() {
    // モーダル表示時のフォーカス管理
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      modal.addEventListener('shown.bs.modal', () => {
        this.setInitialModalFocus(modal);
      });

      modal.addEventListener('hidden.bs.modal', () => {
        this.restorePreviousFocus(modal);
      });
    });
  }

  /**
   * 関連するラベルを検索
   * @param {HTMLInputElement} input - The input element to find a label for
   * @returns {HTMLLabelElement|null} The associated label element or null
   */
  findAssociatedLabel(input) {
    if (!(input instanceof HTMLElement)) {
      console.warn('AccessibilityEnhancer: Invalid input element provided');
      return null;
    }

    // 1. for属性で関連付けられたラベル
    if (input.id) {
      const label = document.querySelector(`label[for="${input.id}"]`);
      if (label) return label;
    }

    // 2. 親要素のラベル
    const parentLabel = input.closest('label');
    if (parentLabel) return parentLabel;

    // 3. 直前の兄弟要素のラベル
    let sibling = input.previousElementSibling;
    while (sibling) {
      if (sibling.tagName === 'LABEL') {
        return sibling;
      }
      sibling = sibling.previousElementSibling;
    }

    return null;
  }

  /**
   * 関連する入力要素を検索
   */
  findAssociatedInput(helpText) {
    // 1. 直前の兄弟要素の入力
    let sibling = helpText.previousElementSibling;
    while (sibling) {
      if (sibling.matches('input, select, textarea')) {
        return sibling;
      }
      sibling = sibling.previousElementSibling;
    }

    // 2. 親要素内の入力要素
    const parent = helpText.parentElement;
    const input = parent.querySelector('input, select, textarea');
    if (input) return input;

    return null;
  }

  /**
   * ユニークIDを生成
   * @param {string} [prefix='element'] - Prefix for the generated ID
   * @returns {string} Unique ID string
   */
  generateUniqueId(prefix = 'element') {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
  }

  /**
   * モーダル内のTabキーナビゲーションを処理
   * @param {KeyboardEvent} event - The keyboard event
   * @param {HTMLElement} modal - The modal element
   * @returns {void}
   */
  handleModalTabNavigation(event, modal) {
    if (!(event instanceof KeyboardEvent) || !(modal instanceof HTMLElement)) {
      console.warn('AccessibilityEnhancer: Invalid parameters for modal tab navigation');
      return;
    }

    const focusableElements = modal.querySelectorAll(this.config.selectors.focusableElements);

    if (focusableElements.length === 0) return;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey) {
      // Shift+Tab: 逆方向
      if (document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      }
    } else {
      // Tab: 順方向
      if (document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  }

  /**
   * ドロップダウンのキーボードナビゲーションを処理
   */
  handleDropdownKeyNavigation(event, dropdown) {
    const menu = dropdown.querySelector('.dropdown-menu');
    const items = menu.querySelectorAll('.dropdown-item:not(.disabled)');

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        if (items.length > 0) {
          items[0].focus();
        }
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (items.length > 0) {
          items[items.length - 1].focus();
        }
        break;
      case 'Escape':
        event.preventDefault();
        const toggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        if (toggle) {
          const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
          if (bsDropdown) {
            bsDropdown.hide();
          }
          toggle.focus();
        }
        break;
    }
  }

  /**
   * モーダルの初期フォーカスを設定
   * @param {HTMLElement} modal - The modal element
   * @returns {void}
   */
  setInitialModalFocus(modal) {
    if (!(modal instanceof HTMLElement)) {
      console.warn('AccessibilityEnhancer: Invalid modal element provided');
      return;
    }

    try {
      // 前のフォーカス要素を記録
      modal._previousFocus = document.activeElement;

      // フォーカス可能な要素を検索
      const focusableElements = modal.querySelectorAll(this.config.selectors.focusableElements);

      if (focusableElements.length > 0) {
        // autofocus属性がある要素を優先
        const autofocusElement = modal.querySelector('[autofocus]');
        const targetElement = (autofocusElement && !autofocusElement.disabled)
          ? autofocusElement
          : focusableElements[0];

        setTimeout(() => {
          if (targetElement && typeof targetElement.focus === 'function') {
            targetElement.focus();
          }
        }, this.config.timing.focusDelay);
      }
    } catch (error) {
      console.error('AccessibilityEnhancer: Failed to set modal focus:', error);
    }
  }

  /**
   * 前のフォーカスを復元
   */
  restorePreviousFocus(modal) {
    if (modal._previousFocus && typeof modal._previousFocus.focus === 'function') {
      try {
        modal._previousFocus.focus();
      } catch (e) {
        // フォーカス復元に失敗した場合は無視
        console.warn('Failed to restore focus:', e);
      }
    }
    delete modal._previousFocus;
  }

  /**
   * エラーメッセージのアクセシビリティを強化
   */
  enhanceErrorMessages() {
    const errorMessages = document.querySelectorAll('.invalid-feedback, .error-message');
    errorMessages.forEach(error => {
      if (!error.getAttribute('role')) {
        error.setAttribute('role', 'alert');
      }
      if (!error.getAttribute('aria-live')) {
        error.setAttribute('aria-live', 'polite');
      }
    });
  }

  /**
   * 動的に追加された要素のアクセシビリティを強化
   */
  enhanceDynamicContent(container) {
    if (container) {
      // 特定のコンテナ内のみを対象
      this.enhanceFormAccessibilityInContainer(container);
      this.enhanceErrorMessagesInContainer(container);
    } else {
      // 全体を再チェック
      this.enhanceFormAccessibility();
      this.enhanceErrorMessages();
    }
  }

  /**
   * 特定のコンテナ内のフォームアクセシビリティを強化
   */
  enhanceFormAccessibilityInContainer(container) {
    const unlabeledInputs = container.querySelectorAll('input:not([aria-label]):not([aria-labelledby])');
    unlabeledInputs.forEach(input => {
      const label = this.findAssociatedLabel(input);
      if (label && !input.getAttribute('aria-label')) {
        if (!label.getAttribute('for') && input.id) {
          label.setAttribute('for', input.id);
        } else if (!input.id) {
          const id = this.generateUniqueId('input');
          input.id = id;
          label.setAttribute('for', id);
        }
      }
    });
  }

  /**
   * 特定のコンテナ内のエラーメッセージを強化
   */
  enhanceErrorMessagesInContainer(container) {
    const errorMessages = container.querySelectorAll('.invalid-feedback, .error-message');
    errorMessages.forEach(error => {
      if (!error.getAttribute('role')) {
        error.setAttribute('role', 'alert');
      }
      if (!error.getAttribute('aria-live')) {
        error.setAttribute('aria-live', 'polite');
      }
    });
  }
}

// グローバルインスタンスを作成 (Singleton pattern)
window.AccessibilityEnhancer = AccessibilityEnhancer.getInstance();

// DOM読み込み完了後に初期化
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.AccessibilityEnhancer.init();
  });
} else {
  window.AccessibilityEnhancer.init();
}

export default AccessibilityEnhancer;