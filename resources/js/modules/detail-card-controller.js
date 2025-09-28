/**
 * Detail Card Controller Module
 * Handles accessibility and ARIA attributes for detail cards.
 * This is a simplified implementation that keeps empty fields always visible.
 */

/**
 * Detail Card Controller Class
 * Simplified implementation that maintains empty field visibility at all times.
 */
class DetailCardController {
  constructor() {
    this.detailCards = [];
    this.isInitialized = false;

    // Configuration object - frozen for immutability
    this.config = Object.freeze({
      cardSelector: '.detail-card-improved',
      emptyFieldSelector: '.empty-field'
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
    this._setupAccessibility();
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
   * Setup accessibility features for detail cards
   * @private
   */
  _setupAccessibility() {
    this.detailCards.forEach(card => {
      this._enhanceCardAccessibility(card);
    });
  }

  /**
   * Enhance accessibility for a specific detail card
   * @param {HTMLElement} card - The detail card element
   * @private
   */
  _enhanceCardAccessibility(card) {
    const section = card.dataset.section || 'default';
    const emptyFields = card.querySelectorAll(this.config.emptyFieldSelector);

    if (emptyFields.length === 0) {
      console.log(`No empty fields found in section: ${section}`);
      return;
    }

    // Add screen reader description for empty fields
    this._addEmptyFieldsDescription(card, section, emptyFields.length);

    // Enhance detail rows with proper ARIA attributes
    this._enhanceDetailRows(card);
  }

  /**
   * Add screen reader description for empty fields
   * @param {HTMLElement} card - The detail card element
   * @param {string} section - Section identifier
   * @param {number} emptyCount - Number of empty fields
   * @private
   */
  _addEmptyFieldsDescription(card, section, emptyCount) {
    try {
      const existingDescription = card.querySelector(`#empty-fields-desc-${section}`);
      if (existingDescription) {
        return; // Already exists
      }

      const description = document.createElement('div');
      description.id = `empty-fields-desc-${section}`;
      description.className = 'sr-only';
      description.textContent = `このセクションには${emptyCount}件の未設定項目が表示されています。`;

      const cardBody = card.querySelector('.card-body');
      if (cardBody) {
        cardBody.insertBefore(description, cardBody.firstChild);
      }
    } catch (error) {
      console.error('Error creating screen reader description:', error);
    }
  }

  /**
   * Enhance detail rows with proper ARIA attributes
   * @param {HTMLElement} card - The detail card element
   * @private
   */
  _enhanceDetailRows(card) {
    const detailRows = card.querySelectorAll('.detail-row');

    detailRows.forEach((row, index) => {
      const label = row.querySelector('.detail-label');
      const value = row.querySelector('.detail-value');

      if (label && value) {
        const labelId = `detail-label-${card.dataset.section || 'default'}-${index}`;
        label.id = labelId;
        value.setAttribute('aria-labelledby', labelId);

        // Add role for better screen reader support
        row.setAttribute('role', 'row');
        label.setAttribute('role', 'rowheader');
        value.setAttribute('role', 'cell');
      }
    });
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
    let totalEmptyFields = 0;
    let cardsWithEmptyFields = 0;

    this.detailCards.forEach(card => {
      const emptyFields = card.querySelectorAll(this.config.emptyFieldSelector);
      if (emptyFields.length > 0) {
        cardsWithEmptyFields++;
        totalEmptyFields += emptyFields.length;
      }
    });

    return {
      totalCards: this.detailCards.length,
      cardsWithEmptyFields,
      totalEmptyFields,
      performance: {
        calculationTime: 0,
        memoryUsage: this._getMemoryUsage()
      }
    };
  }

  /**
   * Get memory usage estimation
   * @returns {Object} Memory usage information
   * @private
   */
  _getMemoryUsage() {
    return {
      detailCards: this.detailCards.length,
      estimatedMemoryKB: Math.round(this.detailCards.length * 0.1 + 1)
    };
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
   * Cleanup method for clearing references
   */
  cleanup() {
    try {
      console.log('🧹 Cleaning up DetailCardController...');
      this._clearReferences();
      console.log('✅ DetailCardController cleanup completed');
    } catch (error) {
      console.error('❌ Error during DetailCardController cleanup:', error);
    }
  }

  /**
   * Clear object references
   * @private
   */
  _clearReferences() {
    this.detailCards = [];
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