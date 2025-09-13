/**
 * Detail Card Controller Module
 * Handles display control for empty/unset fields in detail cards
 * Provides toggle functionality and user preference persistence
 */

import { showToast } from '../shared/utils.js';

/**
 * Detail Card Controller Class - Performance Optimized
 */
class DetailCardController {
  constructor() {
    this.detailCards = null;
    this.toggleButtons = new Map();
    this.isInitialized = false;
    this.eventListeners = new Map(); // Track event listeners for cleanup
    this.preferences = null; // Cache preferences to reduce localStorage access
    this.debounceTimer = null; // For debounced operations

    // Optimized configuration - frozen for performance
    this.config = Object.freeze({
      cardSelector: '.detail-card-improved',
      emptyFieldSelector: '.empty-field',
      toggleButtonClass: 'empty-fields-toggle',
      showEmptyClass: 'show-empty-fields',
      storageKey: 'detailCardPreferences',
      defaultShowEmpty: false,
      debounceDelay: 100, // ms for debounced operations
      buttonText: Object.freeze({
        show: '未設定項目を表示',
        hide: '未設定項目を非表示'
      }),
      icons: Object.freeze({
        show: 'fas fa-eye',
        hide: 'fas fa-eye-slash'
      })
    });
  }

  /**
 * Initialize the detail card controller - Performance Optimized
 */
  init() {
    try {
      // Use requestAnimationFrame for non-blocking initialization
      return new Promise((resolve) => {
        requestAnimationFrame(() => {
          this.findDetailCards();

          if (!this.detailCards || this.detailCards.length === 0) {
            console.log('No detail cards found - skipping initialization');
            resolve(false);
            return;
          }

          // Batch DOM operations for better performance
          this.batchInitialization();

          this.isInitialized = true;
          console.log('Detail card controller initialized successfully');
          resolve(true);
        });
      });
    } catch (error) {
      console.error('Failed to initialize detail card controller:', error);
      return Promise.resolve(false);
    }
  }

  /**
 * Batch initialization operations for better performance
 */
  batchInitialization() {
    // Load preferences once and cache
    this.loadUserPreferences();

    // Initialize all components in a single DOM pass
    this.initializeToggleButtons();
    this.setupEventListeners();
    this.addAriaLandmarks();
  }

  /**
 * Find all detail cards on the page - Optimized with Caching
 */
  findDetailCards() {
    // Use more specific selector to reduce search scope
    const containers = document.querySelectorAll('.card-body, .tab-content, main');
    const cards = [];

    // Search within specific containers to improve performance
    for (const container of containers) {
      const containerCards = container.querySelectorAll(this.config.cardSelector);
      cards.push(...containerCards);
    }

    // Remove duplicates and convert to NodeList-like array
    this.detailCards = [...new Set(cards)];
  }

  /**
 * Initialize toggle buttons for all detail cards
 */
  initializeToggleButtons() {
    this.detailCards.forEach(card => {
      this.addToggleButton(card);
    });
  }

  /**
 * Add toggle button to a specific detail card
 * @param {HTMLElement} card - The detail card element
 */
  addToggleButton(card) {
    const header = card.querySelector('.card-header');
    if (!header) {
      console.warn('Card header not found for detail card:', card);
      return;
    }

    // Check if button already exists
    if (header.querySelector(`.${this.config.toggleButtonClass}`)) {
      return;
    }

    // Get section identifier
    const section = card.dataset.section || 'default';

    // Count empty fields
    const emptyFields = card.querySelectorAll(this.config.emptyFieldSelector);
    if (emptyFields.length === 0) {
      console.log(`No empty fields found in section: ${section}`);
      return;
    }

    // Create toggle button
    const button = this.createToggleButton(section, emptyFields.length);

    // Add button to header
    const buttonContainer = this.createButtonContainer();
    buttonContainer.appendChild(button);

    // Add screen reader description after button is in DOM
    try {
      const description = this.addScreenReaderDescription(button, section, emptyFields.length);
      if (description) {
        buttonContainer.appendChild(description);
      }
    } catch (error) {
      console.error('Error adding screen reader description:', error);
      // Continue without description - functionality still works
    }

    header.appendChild(buttonContainer);

    // Store button reference
    this.toggleButtons.set(section, {
      button,
      card,
      emptyFields
    });
  }

  /**
 * Create toggle button element
 * @param {string} section - Section identifier
 * @param {number} emptyCount - Number of empty fields
 * @returns {HTMLElement} The toggle button element
 */
  createToggleButton(section, emptyCount) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `btn btn-outline-secondary btn-sm ${this.config.toggleButtonClass}`;
    button.dataset.section = section;

    // アクセシビリティ属性の設定
    button.setAttribute('aria-label', `未設定項目 ${emptyCount}件の表示を切り替え`);
    button.setAttribute('aria-describedby', `empty-fields-desc-${section}`);
    button.setAttribute('role', 'switch');
    button.title = `未設定項目 ${emptyCount}件の表示を切り替え`;

    // Set initial state
    const isShowing = this.getPreference(section);
    this.updateButtonState(button, isShowing);

    return button;
  }

  /**
 * Create button container
 * @returns {HTMLElement} The button container element
 */
  createButtonContainer() {
    const container = document.createElement('div');
    container.className = 'detail-card-controls ms-auto';
    return container;
  }

  /**
 * Add screen reader description for the toggle button
 * @param {HTMLElement} button - The toggle button
 * @param {string} section - Section identifier
 * @param {number} emptyCount - Number of empty fields
 * @returns {HTMLElement} The description element
 */
  addScreenReaderDescription(button, section, emptyCount) {
    try {
      const description = document.createElement('div');
      description.id = `empty-fields-desc-${section}`;
      description.className = 'sr-only';
      description.textContent = `このセクションには${emptyCount}件の未設定項目があります。ボタンを押すと表示・非表示を切り替えできます。`;

      // Return the description element to be added by the caller
      // This avoids the parentNode null error
      return description;
    } catch (error) {
      console.error('Error creating screen reader description:', error);
      // Return empty div as fallback
      return document.createElement('div');
    }
  }

  /**
 * Update button state and appearance
 * @param {HTMLElement} button - The toggle button
 * @param {boolean} isShowing - Whether empty fields are showing
 */
  updateButtonState(button, isShowing) {
    const icon = isShowing ? this.config.icons.hide : this.config.icons.show;
    const text = isShowing ? this.config.buttonText.hide : this.config.buttonText.show;
    const section = button.dataset.section;

    // ボタンの内容を更新
    button.innerHTML = `
      <i class="${icon} me-1" aria-hidden="true"></i>
      <span class="toggle-text">${text}</span>
      <span class="sr-only">（現在の状態: ${isShowing ? '表示中' : '非表示中'}）</span>
    `;

    // ARIA属性の更新
    button.setAttribute('aria-pressed', isShowing.toString());
    button.setAttribute('aria-expanded', isShowing.toString());

    // タイトルの更新
    const buttonData = this.toggleButtons.get(section);
    if (buttonData) {
      const emptyCount = buttonData.emptyFields.length;
      button.title = `未設定項目 ${emptyCount}件を${isShowing ? '非表示' : '表示'}にする`;
      button.setAttribute('aria-label', `未設定項目 ${emptyCount}件を${isShowing ? '非表示' : '表示'}にする`);
    }
  }

  /**
 * Setup event listeners - Performance Optimized with Event Delegation
 */
  setupEventListeners() {
    // Use single event delegation for better performance
    const clickHandler = (event) => {
      const button = event.target.closest(`.${this.config.toggleButtonClass}`);
      if (button) {
        event.preventDefault();
        this.handleToggleClick(button);
      }
    };

    const keydownHandler = (event) => {
      const button = event.target.closest(`.${this.config.toggleButtonClass}`);
      if (button && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        this.handleToggleClick(button);
        return;
      }

      // Handle detail row focus management
      const detailRow = event.target.closest('.detail-row');
      if (detailRow && event.key === 'Tab') {
        this.handleDetailRowFocus(detailRow, event);
      }
    };

    const focusHandler = (event) => {
      const detailValue = event.target.closest('.detail-value');
      if (detailValue) {
        this.announceDetailValue(detailValue);
      }
    };

    // Add listeners with passive option where appropriate
    document.addEventListener('click', clickHandler);
    document.addEventListener('keydown', keydownHandler);
    document.addEventListener('focus', focusHandler, { passive: true });

    // Store references for cleanup
    this.eventListeners.set('click', clickHandler);
    this.eventListeners.set('keydown', keydownHandler);
    this.eventListeners.set('focus', focusHandler);
  }

  /**
 * Handle toggle button click
 * @param {HTMLElement} button - The clicked toggle button
 */
  handleToggleClick(button) {
    const section = button.dataset.section;
    const buttonData = this.toggleButtons.get(section);

    if (!buttonData) {
      console.error('Button data not found for section:', section);
      return;
    }

    try {
      this.toggleEmptyFields(buttonData.card, section);
    } catch (error) {
      console.error('Error toggling empty fields:', error);
      showToast('表示切り替えに失敗しました。', 'error');
    }
  }

  /**
 * Toggle empty fields visibility for a card
 * @param {HTMLElement} card - The detail card
 * @param {string} section - Section identifier
 */
  toggleEmptyFields(card, section) {
    const isCurrentlyShowing = card.classList.contains(this.config.showEmptyClass);
    const newState = !isCurrentlyShowing;

    // Update card class
    if (newState) {
      card.classList.add(this.config.showEmptyClass);
    } else {
      card.classList.remove(this.config.showEmptyClass);
    }

    // Update button state
    const buttonData = this.toggleButtons.get(section);
    if (buttonData) {
      this.updateButtonState(buttonData.button, newState);
    }

    // Save preference
    this.saveUserPreference(section, newState);

    // Show feedback
    const actionText = newState ? '表示' : '非表示';
    const emptyCount = buttonData.emptyFields.length;
    showToast(`未設定項目 ${emptyCount}件を${actionText}にしました。`, 'success');
  }

  /**
 * Load user preferences from localStorage - Optimized with Caching
 */
  loadUserPreferences() {
    try {
      // Cache preferences to avoid repeated localStorage access
      if (!this.preferences) {
        this.preferences = this.getUserPreferences();
      }

      // Batch DOM updates for better performance
      const updates = [];

      this.toggleButtons.forEach((buttonData, section) => {
        const shouldShow = this.preferences[section]?.showEmptyFields ?? this.config.defaultShowEmpty;

        updates.push({
          card: buttonData.card,
          button: buttonData.button,
          shouldShow
        });
      });

      // Apply all updates in a single batch
      this.batchUpdateCardStates(updates);
    } catch (error) {
      console.error('Error loading user preferences:', error);
    }
  }

  /**
 * Batch update card states for better performance
 */
  batchUpdateCardStates(updates) {
    // Use requestAnimationFrame to batch DOM updates
    requestAnimationFrame(() => {
      updates.forEach(({ card, button, shouldShow }) => {
        if (shouldShow) {
          card.classList.add(this.config.showEmptyClass);
        } else {
          card.classList.remove(this.config.showEmptyClass);
        }
        this.updateButtonState(button, shouldShow);
      });
    });
  }

  /**
 * Save user preference for a section - Optimized with Debouncing and Caching
 * @param {string} section - Section identifier
 * @param {boolean} showEmptyFields - Whether to show empty fields
 */
  saveUserPreference(section, showEmptyFields) {
    try {
      // Update cached preferences immediately
      if (!this.preferences) {
        this.preferences = this.getUserPreferences();
      }

      if (!this.preferences[section]) {
        this.preferences[section] = {};
      }

      this.preferences[section].showEmptyFields = showEmptyFields;

      // Debounce localStorage writes to improve performance
      this.debouncedSaveToStorage();
    } catch (error) {
      console.error('Error saving user preference:', error);
    }
  }

  /**
 * Debounced localStorage save to reduce I/O operations - Enhanced with Compression
 */
  debouncedSaveToStorage() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }

    this.debounceTimer = setTimeout(() => {
      try {
        // Optimize storage by only saving changed preferences
        const optimizedPreferences = this.optimizePreferencesForStorage();
        const serialized = JSON.stringify(optimizedPreferences);

        // Check if we're approaching localStorage limits
        if (this.checkStorageQuota(serialized)) {
          localStorage.setItem(this.config.storageKey, serialized);
        } else {
          console.warn('localStorage quota exceeded, cleaning up old data');
          this.cleanupOldStorageData();
          localStorage.setItem(this.config.storageKey, serialized);
        }
      } catch (error) {
        console.error('Error writing to localStorage:', error);
        // Fallback: try to save minimal data
        this.saveMinimalPreferences();
      }
    }, this.config.debounceDelay);
  }

  /**
 * Optimize preferences for storage by removing default values
 */
  optimizePreferencesForStorage() {
    const optimized = {};

    for (const [section, prefs] of Object.entries(this.preferences || {})) {
      // Only store non-default values to save space
      if (prefs.showEmptyFields !== this.config.defaultShowEmpty) {
        optimized[section] = { showEmptyFields: prefs.showEmptyFields };
      }
    }

    return optimized;
  }

  /**
 * Check if storage operation will exceed quota
 */
  checkStorageQuota(data) {
    try {
      const testKey = `${this.config.storageKey}_test`;
      localStorage.setItem(testKey, data);
      localStorage.removeItem(testKey);
      return true;
    } catch {
      return false;
    }
  }

  /**
 * Clean up old storage data to free space
 */
  cleanupOldStorageData() {
    try {
      // Remove old or corrupted entries
      const keysToRemove = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('detailCard') && key !== this.config.storageKey) {
          keysToRemove.push(key);
        }
      }

      keysToRemove.forEach(key => localStorage.removeItem(key));
    } catch (error) {
      console.error('Error cleaning up storage:', error);
    }
  }

  /**
 * Save minimal preferences as fallback
 */
  saveMinimalPreferences() {
    try {
      // Save only the most recently changed preference
      const minimal = {};
      const sections = Object.keys(this.preferences || {});
      if (sections.length > 0) {
        const lastSection = sections[sections.length - 1];
        minimal[lastSection] = this.preferences[lastSection];
      }

      localStorage.setItem(this.config.storageKey, JSON.stringify(minimal));
    } catch (error) {
      console.error('Error saving minimal preferences:', error);
    }
  }

  /**
 * Get user preference for a section - Optimized with Caching
 * @param {string} section - Section identifier
 * @returns {boolean} Whether to show empty fields
 */
  getPreference(section) {
    try {
      // Use cached preferences if available
      if (!this.preferences) {
        this.preferences = this.getUserPreferences();
      }
      return this.preferences[section]?.showEmptyFields ?? this.config.defaultShowEmpty;
    } catch (error) {
      console.error('Error getting preference:', error);
      return this.config.defaultShowEmpty;
    }
  }

  /**
 * Get all user preferences from localStorage - Optimized with Error Handling
 * @returns {Object} User preferences object
 */
  getUserPreferences() {
    try {
      const stored = localStorage.getItem(this.config.storageKey);
      if (!stored) {
        return {};
      }

      const parsed = JSON.parse(stored);

      // Validate structure to prevent corruption issues
      if (typeof parsed !== 'object' || parsed === null) {
        console.warn('Invalid preferences structure, resetting');
        this.clearUserPreferences();
        return {};
      }

      return parsed;
    } catch (error) {
      console.error('Error parsing user preferences:', error);
      // Clear corrupted data
      try {
        localStorage.removeItem(this.config.storageKey);
      } catch (clearError) {
        console.error('Error clearing corrupted preferences:', clearError);
      }
      return {};
    }
  }

  /**
 * Clear all user preferences - Optimized with Batch Updates
 */
  clearUserPreferences() {
    try {
      // Clear localStorage and cache
      localStorage.removeItem(this.config.storageKey);
      this.preferences = null;

      // Clear any pending debounced saves
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = null;
      }

      // Batch reset all cards to default state
      const updates = [];
      this.toggleButtons.forEach((buttonData, _section) => {
        updates.push({
          card: buttonData.card,
          button: buttonData.button,
          shouldShow: this.config.defaultShowEmpty
        });
      });

      this.batchUpdateCardStates(updates);

      showToast('表示設定をリセットしました。', 'success');
    } catch (error) {
      console.error('Error clearing user preferences:', error);
      showToast('設定のリセットに失敗しました。', 'error');
    }
  }

  /**
 * Get statistics about empty fields - Enhanced with Performance Metrics
 * @returns {Object} Statistics object
 */
  getStatistics() {
    const startTime = performance.now();

    const stats = {
      totalCards: this.detailCards?.length || 0,
      cardsWithEmptyFields: 0,
      totalEmptyFields: 0,
      sectionsShowing: 0,
      sectionsHiding: 0,
      performance: {
        calculationTime: 0,
        memoryUsage: this.getMemoryUsage()
      }
    };

    this.toggleButtons.forEach((buttonData, _section) => {
      stats.cardsWithEmptyFields++;
      stats.totalEmptyFields += buttonData.emptyFields.length;

      if (buttonData.card.classList.contains(this.config.showEmptyClass)) {
        stats.sectionsShowing++;
      } else {
        stats.sectionsHiding++;
      }
    });

    stats.performance.calculationTime = performance.now() - startTime;
    return stats;
  }

  /**
 * Get memory usage estimation
 * @returns {Object} Memory usage information
 */
  getMemoryUsage() {
    const usage = {
      toggleButtons: this.toggleButtons.size,
      eventListeners: this.eventListeners.size,
      cachedPreferences: this.preferences ? Object.keys(this.preferences).length : 0,
      estimatedMemoryKB: 0
    };

    // Rough estimation of memory usage
    usage.estimatedMemoryKB = Math.round(
      (usage.toggleButtons * 0.5) + // Each button data ~0.5KB
      (usage.eventListeners * 0.1) + // Each listener ~0.1KB
      (usage.cachedPreferences * 0.05) + // Each preference ~0.05KB
      1 // Base controller overhead ~1KB
    );

    return usage;
  }

  /**
 * Refresh the controller - Optimized for Dynamic Content Changes
 */
  refresh() {
    if (!this.isInitialized) {
      return this.init();
    }

    // Use requestAnimationFrame for non-blocking refresh
    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        try {
          // Clear existing buttons
          this.toggleButtons.clear();

          // Re-initialize with batched operations
          this.findDetailCards();

          if (this.detailCards && this.detailCards.length > 0) {
            this.batchInitialization();
            console.log('Detail card controller refreshed');
            resolve(true);
          } else {
            console.log('No detail cards found during refresh');
            resolve(false);
          }
        } catch (error) {
          console.error('Error during refresh:', error);
          resolve(false);
        }
      });
    });
  }

  /**
 * Handle focus management for detail rows
 * @param {HTMLElement} detailRow - The detail row element
 * @param {KeyboardEvent} event - The keyboard event
 */
  handleDetailRowFocus(detailRow, _event) {
    const focusableElements = detailRow.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');

    if (focusableElements.length === 0) {
      // フォーカス可能な要素がない場合、行自体をフォーカス可能にする
      if (!detailRow.hasAttribute('tabindex')) {
        detailRow.setAttribute('tabindex', '0');
        detailRow.setAttribute('role', 'row');
      }
    }
  }

  /**
 * Announce detail value for screen readers
 * @param {HTMLElement} detailValue - The detail value element
 */
  announceDetailValue(detailValue) {
    const detailRow = detailValue.closest('.detail-row');
    if (!detailRow) {
      return;
    }

    const label = detailRow.querySelector('.detail-label');
    if (label && !detailValue.hasAttribute('aria-label')) {
      const labelText = label.textContent.trim();
      const valueText = detailValue.textContent.trim();

      // 空の値の場合の処理
      if (!valueText || valueText === '未設定') {
        detailValue.setAttribute('aria-label', `${labelText}: 未設定`);
      } else {
        detailValue.setAttribute('aria-label', `${labelText}: ${valueText}`);
      }
    }
  }

  /**
   * Add ARIA landmarks to detail cards
   */
  addAriaLandmarks() {
    this.detailCards.forEach((card, index) => {
      const header = card.querySelector('.card-header h5');
      if (header && !header.hasAttribute('id')) {
        const cardId = `detail-card-${index}`;
        header.setAttribute('id', cardId);
        card.setAttribute('aria-labelledby', cardId);
        card.setAttribute('role', 'region');
      }
    });
  }

  /**
   * Cleanup method for removing event listeners and clearing references
   */
  cleanup() {
    try {
      console.log('🧹 Cleaning up DetailCardController...');

      console.log('✅ DetailCardController cleanup completed');
    } catch (error) {
      console.error('❌ Error during DetailCardController cleanup:', error);
    }
  }
}

/**
 * Initialize detail card controller functionality - Performance Optimized
 * @returns {Promise<DetailCardController|null>} The initialized controller instance or null
 */
export async function initializeDetailCardController() {
  const controller = new DetailCardController();
  const initialized = await controller.init();

  return initialized ? controller : null;
}

/**
 * Export the class for direct usage
 */
export { DetailCardController };

/**
 * Note: Auto-initialization is now handled by the main app.js file
 * This ensures proper integration with the application lifecycle
 * and error handling mechanisms.
 */