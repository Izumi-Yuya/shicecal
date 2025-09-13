/**
 * Detail Card Controller Module
 * Handles display control for empty/unset fields in detail cards
 * Provides toggle functionality and user preference persistence
 */

import { showToast } from '../shared/utils.js';

/**
 * Detail Card Controller Class - Refactored for better maintainability
 */
class DetailCardController {
  constructor() {
    this.detailCards = [];
    this.toggleButtons = new Map();
    this.isInitialized = false;
    this.eventListeners = new Map();
    this.preferences = null;
    this.debounceTimer = null;

    // Configuration object - frozen for immutability
    this.config = Object.freeze({
      cardSelector: '.detail-card-improved',
      emptyFieldSelector: '.empty-field',
      toggleButtonClass: 'empty-fields-toggle',
      showEmptyClass: 'show-empty-fields',
      storageKey: 'detailCardPreferences',
      defaultShowEmpty: false,
      debounceDelay: 100,
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
   * Initialize the detail card controller
   * @returns {Promise<boolean>} Success status
   */
  async init() {
    if (this.isInitialized) {
      return true;
    }

    try {
      await this._performInitialization();
      this.isInitialized = true;
      console.log('Detail card controller initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize detail card controller:', error);
      return false;
    }
  }

  /**
   * Perform the actual initialization work
   * @private
   */
  async _performInitialization() {
    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        this._findDetailCards();

        if (this.detailCards.length === 0) {
          console.log('No detail cards found - skipping initialization');
          resolve();
          return;
        }

        this._batchInitialization();
        resolve();
      });
    });
  }

  /**
   * Batch initialization operations for better performance
   * @private
   */
  _batchInitialization() {
    this._loadUserPreferences();
    this._initializeToggleButtons();
    this._setupEventListeners();
    this._addAriaLandmarks();
  }

  /**
   * Find all detail cards on the page
   * @private
   */
  _findDetailCards() {
    const containers = document.querySelectorAll('.card-body, .tab-content, main');
    const cards = [];

    for (const container of containers) {
      const containerCards = container.querySelectorAll(this.config.cardSelector);
      cards.push(...containerCards);
    }

    // Remove duplicates
    this.detailCards = [...new Set(cards)];
  }

  /**
   * Initialize toggle buttons for all detail cards
   * @private
   */
  _initializeToggleButtons() {
    this.detailCards.forEach(card => {
      this._addToggleButton(card);
    });
  }

  /**
   * Add toggle button to a specific detail card
   * @param {HTMLElement} card - The detail card element
   * @private
   */
  _addToggleButton(card) {
    const header = card.querySelector('.card-header');
    if (!header) {
      console.warn('Card header not found for detail card:', card);
      return;
    }

    // Check if button already exists
    if (header.querySelector(`.${this.config.toggleButtonClass}`)) {
      return;
    }

    const section = card.dataset.section || 'default';
    const emptyFields = card.querySelectorAll(this.config.emptyFieldSelector);

    if (emptyFields.length === 0) {
      console.log(`No empty fields found in section: ${section}`);
      return;
    }

    const button = this._createToggleButton(section, emptyFields.length);
    const buttonContainer = this._createButtonContainer();

    buttonContainer.appendChild(button);

    // Add screen reader description
    const description = this._createScreenReaderDescription(section, emptyFields.length);
    if (description) {
      buttonContainer.appendChild(description);
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
   * @private
   */
  _createToggleButton(section, emptyCount) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `btn btn-outline-secondary btn-sm ${this.config.toggleButtonClass}`;
    button.dataset.section = section;

    // Set accessibility attributes
    this._setButtonAccessibilityAttributes(button, section, emptyCount);

    // Set initial state
    const isShowing = this._getPreference(section);
    this._updateButtonState(button, isShowing);

    return button;
  }

  /**
   * Set accessibility attributes for button
   * @param {HTMLElement} button - The button element
   * @param {string} section - Section identifier
   * @param {number} emptyCount - Number of empty fields
   * @private
   */
  _setButtonAccessibilityAttributes(button, section, emptyCount) {
    button.setAttribute('aria-label', `未設定項目 ${emptyCount}件の表示を切り替え`);
    button.setAttribute('aria-describedby', `empty-fields-desc-${section}`);
    button.setAttribute('role', 'switch');
    button.title = `未設定項目 ${emptyCount}件の表示を切り替え`;
  }

  /**
   * Create button container
   * @returns {HTMLElement} The button container element
   * @private
   */
  _createButtonContainer() {
    const container = document.createElement('div');
    container.className = 'detail-card-controls ms-auto';
    return container;
  }

  /**
   * Create screen reader description for the toggle button
   * @param {string} section - Section identifier
   * @param {number} emptyCount - Number of empty fields
   * @returns {HTMLElement|null} The description element or null if error
   * @private
   */
  _createScreenReaderDescription(section, emptyCount) {
    try {
      const description = document.createElement('div');
      description.id = `empty-fields-desc-${section}`;
      description.className = 'sr-only';
      description.textContent = `このセクションには${emptyCount}件の未設定項目があります。ボタンを押すと表示・非表示を切り替えできます。`;
      return description;
    } catch (error) {
      console.error('Error creating screen reader description:', error);
      return null;
    }
  }

  /**
   * Update button state and appearance
   * @param {HTMLElement} button - The toggle button
   * @param {boolean} isShowing - Whether empty fields are showing
   * @private
   */
  _updateButtonState(button, isShowing) {
    const icon = isShowing ? this.config.icons.hide : this.config.icons.show;
    const text = isShowing ? this.config.buttonText.hide : this.config.buttonText.show;
    const section = button.dataset.section;

    // Update button content
    button.innerHTML = `
      <i class="${icon} me-1" aria-hidden="true"></i>
      <span class="toggle-text">${text}</span>
      <span class="sr-only">（現在の状態: ${isShowing ? '表示中' : '非表示中'}）</span>
    `;

    // Update ARIA attributes
    button.setAttribute('aria-pressed', isShowing.toString());
    button.setAttribute('aria-expanded', isShowing.toString());

    // Update title and aria-label
    const buttonData = this.toggleButtons.get(section);
    if (buttonData) {
      const emptyCount = buttonData.emptyFields.length;
      const actionText = isShowing ? '非表示' : '表示';
      const titleText = `未設定項目 ${emptyCount}件を${actionText}にする`;

      button.title = titleText;
      button.setAttribute('aria-label', titleText);
    }
  }

  /**
   * Setup event listeners using event delegation
   * @private
   */
  _setupEventListeners() {
    const clickHandler = this._createClickHandler();
    const keydownHandler = this._createKeydownHandler();
    const focusHandler = this._createFocusHandler();

    // Add listeners
    document.addEventListener('click', clickHandler);
    document.addEventListener('keydown', keydownHandler);
    document.addEventListener('focus', focusHandler, { passive: true });

    // Store references for cleanup
    this.eventListeners.set('click', clickHandler);
    this.eventListeners.set('keydown', keydownHandler);
    this.eventListeners.set('focus', focusHandler);
  }

  /**
   * Create click event handler
   * @returns {Function} Click handler function
   * @private
   */
  _createClickHandler() {
    return (event) => {
      const button = event.target.closest(`.${this.config.toggleButtonClass}`);
      if (button) {
        event.preventDefault();
        this._handleToggleClick(button);
      }
    };
  }

  /**
   * Create keydown event handler
   * @returns {Function} Keydown handler function
   * @private
   */
  _createKeydownHandler() {
    return (event) => {
      const button = event.target.closest(`.${this.config.toggleButtonClass}`);
      if (button && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        this._handleToggleClick(button);
        return;
      }

      // Handle detail row focus management
      const detailRow = event.target.closest('.detail-row');
      if (detailRow && event.key === 'Tab') {
        this._handleDetailRowFocus(detailRow, event);
      }
    };
  }

  /**
   * Create focus event handler
   * @returns {Function} Focus handler function
   * @private
   */
  _createFocusHandler() {
    return (event) => {
      const detailValue = event.target.closest('.detail-value');
      if (detailValue) {
        this._announceDetailValue(detailValue);
      }
    };
  }

  /**
   * Handle toggle button click
   * @param {HTMLElement} button - The clicked toggle button
   * @private
   */
  _handleToggleClick(button) {
    const section = button.dataset.section;
    const buttonData = this.toggleButtons.get(section);

    if (!buttonData) {
      console.error('Button data not found for section:', section);
      return;
    }

    try {
      this._toggleEmptyFields(buttonData.card, section);
    } catch (error) {
      console.error('Error toggling empty fields:', error);
      showToast('表示切り替えに失敗しました。', 'error');
    }
  }

  /**
   * Toggle empty fields visibility for a card
   * @param {HTMLElement} card - The detail card
   * @param {string} section - Section identifier
   * @private
   */
  _toggleEmptyFields(card, section) {
    const isCurrentlyShowing = card.classList.contains(this.config.showEmptyClass);
    const newState = !isCurrentlyShowing;

    // Update card class
    card.classList.toggle(this.config.showEmptyClass, newState);

    // Update button state
    const buttonData = this.toggleButtons.get(section);
    if (buttonData) {
      this._updateButtonState(buttonData.button, newState);
    }

    // Save preference
    this._saveUserPreference(section, newState);

    // Show feedback
    this._showToggleFeedback(newState, buttonData.emptyFields.length);
  }

  /**
   * Show feedback message for toggle action
   * @param {boolean} newState - New visibility state
   * @param {number} emptyCount - Number of empty fields
   * @private
   */
  _showToggleFeedback(newState, emptyCount) {
    const actionText = newState ? '表示' : '非表示';
    showToast(`未設定項目 ${emptyCount}件を${actionText}にしました。`, 'success');
  }

  /**
   * Load user preferences from localStorage
   * @private
   */
  _loadUserPreferences() {
    try {
      if (!this.preferences) {
        this.preferences = this._getUserPreferences();
      }

      const updates = this._preparePreferenceUpdates();
      this._batchUpdateCardStates(updates);
    } catch (error) {
      console.error('Error loading user preferences:', error);
    }
  }

  /**
   * Prepare preference updates for batch processing
   * @returns {Array} Array of update objects
   * @private
   */
  _preparePreferenceUpdates() {
    const updates = [];

    this.toggleButtons.forEach((buttonData, section) => {
      const shouldShow = this.preferences[section]?.showEmptyFields ?? this.config.defaultShowEmpty;
      updates.push({
        card: buttonData.card,
        button: buttonData.button,
        shouldShow
      });
    });

    return updates;
  }

  /**
   * Batch update card states for better performance
   * @param {Array} updates - Array of update objects
   * @private
   */
  _batchUpdateCardStates(updates) {
    requestAnimationFrame(() => {
      updates.forEach(({ card, button, shouldShow }) => {
        card.classList.toggle(this.config.showEmptyClass, shouldShow);
        this._updateButtonState(button, shouldShow);
      });
    });
  }

  /**
   * Save user preference for a section
   * @param {string} section - Section identifier
   * @param {boolean} showEmptyFields - Whether to show empty fields
   * @private
   */
  _saveUserPreference(section, showEmptyFields) {
    try {
      this._updateCachedPreference(section, showEmptyFields);
      this._debouncedSaveToStorage();
    } catch (error) {
      console.error('Error saving user preference:', error);
    }
  }

  /**
   * Update cached preference
   * @param {string} section - Section identifier
   * @param {boolean} showEmptyFields - Whether to show empty fields
   * @private
   */
  _updateCachedPreference(section, showEmptyFields) {
    if (!this.preferences) {
      this.preferences = this._getUserPreferences();
    }

    if (!this.preferences[section]) {
      this.preferences[section] = {};
    }

    this.preferences[section].showEmptyFields = showEmptyFields;
  }

  /**
   * Debounced localStorage save to reduce I/O operations
   * @private
   */
  _debouncedSaveToStorage() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
    }

    this.debounceTimer = setTimeout(() => {
      this._performStorageSave();
    }, this.config.debounceDelay);
  }

  /**
   * Perform the actual storage save operation
   * @private
   */
  _performStorageSave() {
    try {
      const optimizedPreferences = this._optimizePreferencesForStorage();
      const serialized = JSON.stringify(optimizedPreferences);

      if (this._checkStorageQuota(serialized)) {
        localStorage.setItem(this.config.storageKey, serialized);
      } else {
        console.warn('localStorage quota exceeded, cleaning up old data');
        this._cleanupOldStorageData();
        localStorage.setItem(this.config.storageKey, serialized);
      }
    } catch (error) {
      console.error('Error writing to localStorage:', error);
      this._saveMinimalPreferences();
    }
  }

  /**
   * Optimize preferences for storage by removing default values
   * @returns {Object} Optimized preferences object
   * @private
   */
  _optimizePreferencesForStorage() {
    const optimized = {};

    for (const [section, prefs] of Object.entries(this.preferences || {})) {
      if (prefs.showEmptyFields !== this.config.defaultShowEmpty) {
        optimized[section] = { showEmptyFields: prefs.showEmptyFields };
      }
    }

    return optimized;
  }

  /**
   * Check if storage operation will exceed quota
   * @param {string} data - Data to test
   * @returns {boolean} Whether storage is available
   * @private
   */
  _checkStorageQuota(data) {
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
   * @private
   */
  _cleanupOldStorageData() {
    try {
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
   * @private
   */
  _saveMinimalPreferences() {
    try {
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
   * Get user preference for a section
   * @param {string} section - Section identifier
   * @returns {boolean} Whether to show empty fields
   * @private
   */
  _getPreference(section) {
    try {
      if (!this.preferences) {
        this.preferences = this._getUserPreferences();
      }
      return this.preferences[section]?.showEmptyFields ?? this.config.defaultShowEmpty;
    } catch (error) {
      console.error('Error getting preference:', error);
      return this.config.defaultShowEmpty;
    }
  }

  /**
   * Get all user preferences from localStorage
   * @returns {Object} User preferences object
   * @private
   */
  _getUserPreferences() {
    try {
      const stored = localStorage.getItem(this.config.storageKey);
      if (!stored) {
        return {};
      }

      const parsed = JSON.parse(stored);

      if (!this._isValidPreferencesStructure(parsed)) {
        console.warn('Invalid preferences structure, resetting');
        this._clearStoredPreferences();
        return {};
      }

      return parsed;
    } catch (error) {
      console.error('Error parsing user preferences:', error);
      this._clearStoredPreferences();
      return {};
    }
  }

  /**
   * Validate preferences structure
   * @param {*} parsed - Parsed preferences object
   * @returns {boolean} Whether structure is valid
   * @private
   */
  _isValidPreferencesStructure(parsed) {
    return typeof parsed === 'object' && parsed !== null;
  }

  /**
   * Clear stored preferences from localStorage
   * @private
   */
  _clearStoredPreferences() {
    try {
      localStorage.removeItem(this.config.storageKey);
    } catch (error) {
      console.error('Error clearing corrupted preferences:', error);
    }
  }

  /**
   * Clear all user preferences (public method)
   */
  clearUserPreferences() {
    try {
      this._clearPreferencesData();
      this._resetAllCardsToDefault();
      showToast('表示設定をリセットしました。', 'success');
    } catch (error) {
      console.error('Error clearing user preferences:', error);
      showToast('設定のリセットに失敗しました。', 'error');
    }
  }

  /**
   * Clear preferences data and timers
   * @private
   */
  _clearPreferencesData() {
    localStorage.removeItem(this.config.storageKey);
    this.preferences = null;

    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = null;
    }
  }

  /**
   * Reset all cards to default state
   * @private
   */
  _resetAllCardsToDefault() {
    const updates = [];
    this.toggleButtons.forEach((buttonData) => {
      updates.push({
        card: buttonData.card,
        button: buttonData.button,
        shouldShow: this.config.defaultShowEmpty
      });
    });

    this._batchUpdateCardStates(updates);
  }

  /**
   * Get statistics about empty fields (public method)
   * @returns {Object} Statistics object
   */
  getStatistics() {
    const startTime = performance.now();
    const stats = this._calculateStatistics();
    stats.performance.calculationTime = performance.now() - startTime;
    return stats;
  }

  /**
   * Calculate statistics
   * @returns {Object} Statistics object
   * @private
   */
  _calculateStatistics() {
    const stats = {
      totalCards: this.detailCards.length,
      cardsWithEmptyFields: 0,
      totalEmptyFields: 0,
      sectionsShowing: 0,
      sectionsHiding: 0,
      performance: {
        calculationTime: 0,
        memoryUsage: this._getMemoryUsage()
      }
    };

    this.toggleButtons.forEach((buttonData) => {
      stats.cardsWithEmptyFields++;
      stats.totalEmptyFields += buttonData.emptyFields.length;

      if (buttonData.card.classList.contains(this.config.showEmptyClass)) {
        stats.sectionsShowing++;
      } else {
        stats.sectionsHiding++;
      }
    });

    return stats;
  }

  /**
   * Get memory usage estimation
   * @returns {Object} Memory usage information
   * @private
   */
  _getMemoryUsage() {
    const usage = {
      toggleButtons: this.toggleButtons.size,
      eventListeners: this.eventListeners.size,
      cachedPreferences: this.preferences ? Object.keys(this.preferences).length : 0,
      estimatedMemoryKB: 0
    };

    // Rough estimation of memory usage
    usage.estimatedMemoryKB = Math.round(
      (usage.toggleButtons * 0.5) +
      (usage.eventListeners * 0.1) +
      (usage.cachedPreferences * 0.05) +
      1
    );

    return usage;
  }

  /**
   * Refresh the controller (public method)
   * @returns {Promise<boolean>} Success status
   */
  async refresh() {
    if (!this.isInitialized) {
      return this.init();
    }

    return this._performRefresh();
  }

  /**
   * Perform the actual refresh operation
   * @returns {Promise<boolean>} Success status
   * @private
   */
  _performRefresh() {
    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        try {
          this.toggleButtons.clear();
          this._findDetailCards();

          if (this.detailCards.length > 0) {
            this._batchInitialization();
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
   * @private
   */
  _handleDetailRowFocus(detailRow, _event) {
    const focusableElements = detailRow.querySelectorAll(
      'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    if (focusableElements.length === 0) {
      this._makeFocusable(detailRow);
    }
  }

  /**
   * Make element focusable
   * @param {HTMLElement} element - Element to make focusable
   * @private
   */
  _makeFocusable(element) {
    if (!element.hasAttribute('tabindex')) {
      element.setAttribute('tabindex', '0');
      element.setAttribute('role', 'row');
    }
  }

  /**
   * Announce detail value for screen readers
   * @param {HTMLElement} detailValue - The detail value element
   * @private
   */
  _announceDetailValue(detailValue) {
    const detailRow = detailValue.closest('.detail-row');
    if (!detailRow) {
      return;
    }

    const label = detailRow.querySelector('.detail-label');
    if (label && !detailValue.hasAttribute('aria-label')) {
      this._setDetailValueAriaLabel(detailValue, label);
    }
  }

  /**
   * Set aria-label for detail value
   * @param {HTMLElement} detailValue - The detail value element
   * @param {HTMLElement} label - The label element
   * @private
   */
  _setDetailValueAriaLabel(detailValue, label) {
    const labelText = label.textContent.trim();
    const valueText = detailValue.textContent.trim();

    const ariaLabel = (!valueText || valueText === '未設定')
      ? `${labelText}: 未設定`
      : `${labelText}: ${valueText}`;

    detailValue.setAttribute('aria-label', ariaLabel);
  }

  /**
   * Add ARIA landmarks to detail cards
   * @private
   */
  _addAriaLandmarks() {
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

      this._removeEventListeners();
      this._clearTimers();
      this._clearReferences();

      console.log('✅ DetailCardController cleanup completed');
    } catch (error) {
      console.error('❌ Error during DetailCardController cleanup:', error);
    }
  }

  /**
   * Remove event listeners
   * @private
   */
  _removeEventListeners() {
    this.eventListeners.forEach((handler, eventType) => {
      document.removeEventListener(eventType, handler);
    });
    this.eventListeners.clear();
  }

  /**
   * Clear timers
   * @private
   */
  _clearTimers() {
    if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = null;
    }
  }

  /**
   * Clear object references
   * @private
   */
  _clearReferences() {
    this.toggleButtons.clear();
    this.detailCards = [];
    this.preferences = null;
    this.isInitialized = false;
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