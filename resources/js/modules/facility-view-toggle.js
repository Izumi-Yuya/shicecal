/**
 * Facility View Toggle Module
 * Handles switching between card and table view modes for facility basic information
 */

import { post } from '../shared/api.js';
import { showToast } from '../shared/utils.js';

/**
 * View Toggle Manager Class
 */
class FacilityViewToggle {
  constructor() {
    this.container = null;
    this.toggleButtons = null;
    this.cardView = null;
    this.tableView = null;
    this.isTransitioning = false;
    this.currentViewMode = 'card';

    // Configuration
    this.config = {
      transitionDuration: 300,
      apiEndpoint: '/facilities/set-view-mode',
      sessionKey: 'facility_basic_info_view_mode'
    };
  }

  /**
 * Initialize the view toggle functionality
 */
  init() {
    try {
      this.findElements();
      if (!this.container) {
        console.log('View toggle container not found - skipping initialization');
        return false;
      }

      this.setupEventListeners();
      this.detectCurrentViewMode();
      this.setupTransitions();

      console.log('Facility view toggle initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize facility view toggle:', error);
      return false;
    }
  }

  /**
 * Find required DOM elements
 */
  findElements() {
    this.container = document.querySelector('.view-toggle-container');
    if (!this.container) {
      return;
    }

    this.toggleButtons = this.container.querySelectorAll('input[name="viewMode"]');
    this.cardView = document.querySelector('.facility-card-view, .basic-info-card');
    this.tableView = document.querySelector('.facility-table-view, .basic-info-table');
  }

  /**
 * Setup event listeners for toggle buttons
 */
  setupEventListeners() {
    this.toggleButtons.forEach(button => {
      button.addEventListener('change', (event) => {
        if (event.target.checked && !this.isTransitioning) {
          this.handleViewModeChange(event.target.value);
        }
      });
    });

    // Add keyboard support
    this.toggleButtons.forEach(button => {
      const label = document.querySelector(`label[for="${button.id}"]`);
      if (label) {
        label.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            button.checked = true;
            button.dispatchEvent(new Event('change'));
          }
        });
      }
    });
  }

  /**
 * Detect current view mode from DOM state
 */
  detectCurrentViewMode() {
    const checkedButton = this.container.querySelector('input[name="viewMode"]:checked');
    if (checkedButton) {
      this.currentViewMode = checkedButton.value;
    }
  }

  /**
 * Setup CSS transitions for smooth view switching
 */
  setupTransitions() {
    // Add transition classes to view containers
    if (this.cardView) {
      this.cardView.style.transition = `opacity ${this.config.transitionDuration}ms ease-in-out, transform ${this.config.transitionDuration}ms ease-in-out`;
    }
    if (this.tableView) {
      this.tableView.style.transition = `opacity ${this.config.transitionDuration}ms ease-in-out, transform ${this.config.transitionDuration}ms ease-in-out`;
    }
  }

  /**
 * Handle view mode change
 * @param {string} newViewMode - The new view mode ('card' or 'table')
 */
  async handleViewModeChange(newViewMode) {
    if (this.isTransitioning || newViewMode === this.currentViewMode) {
      return;
    }

    try {
      this.isTransitioning = true;
      this.showLoadingState();

      // Validate view mode
      if (!['card', 'table'].includes(newViewMode)) {
        throw new Error('Invalid view mode');
      }

      // Save view preference to session via AJAX
      await this.saveViewPreference(newViewMode);

      // Update UI with smooth transition
      await this.transitionToView(newViewMode);

      // Update current view mode
      this.currentViewMode = newViewMode;

      // Show success feedback
      this.showSuccessFeedback(newViewMode);

    } catch (error) {
      console.error('Failed to change view mode:', error);
      this.handleViewModeError(error);
      this.revertToggleState();
    } finally {
      this.isTransitioning = false;
      this.hideLoadingState();
    }
  }

  /**
 * Save view preference to session via AJAX
 * @param {string} viewMode - The view mode to save
 */
  async saveViewPreference(viewMode) {
    try {
      const response = await post(this.config.apiEndpoint, {
        view_mode: viewMode
      });

      if (!response.success) {
        throw new Error(response.message || 'Failed to save view preference');
      }

      return response;
    } catch (error) {
      console.error('API error saving view preference:', error);
      throw error;
    }
  }

  /**
 * Transition to the new view with smooth animation
 * @param {string} newViewMode - The target view mode
 */
  async transitionToView(_newViewMode) {
    return new Promise((resolve) => {
      // For now, we'll reload the page to show the new view
      // In a future enhancement, we could implement client-side view switching
      setTimeout(() => {
        window.location.reload();
        resolve();
      }, this.config.transitionDuration / 2);
    });
  }

  /**
 * Show loading state during transition
 */
  showLoadingState() {
    // Disable all toggle buttons
    this.toggleButtons.forEach(button => {
      button.disabled = true;
    });

    // Add loading class to container
    this.container.classList.add('view-toggle-loading');

    // Show loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'view-toggle-loading-indicator';
    loadingIndicator.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
                <small class="text-muted">表示形式を変更中...</small>
            </div>
        `;

    this.container.appendChild(loadingIndicator);
  }

  /**
 * Hide loading state
 */
  hideLoadingState() {
    // Re-enable toggle buttons
    this.toggleButtons.forEach(button => {
      button.disabled = false;
    });

    // Remove loading class
    this.container.classList.remove('view-toggle-loading');

    // Remove loading indicator
    const loadingIndicator = this.container.querySelector('.view-toggle-loading-indicator');
    if (loadingIndicator) {
      loadingIndicator.remove();
    }
  }

  /**
 * Show success feedback
 * @param {string} viewMode - The new view mode
 */
  showSuccessFeedback(viewMode) {
    const modeLabel = viewMode === 'card' ? 'カード形式' : 'テーブル形式';
    showToast(`表示形式を${modeLabel}に変更しました。`, 'success');
  }

  /**
 * Handle view mode change errors
 * @param {Error} error - The error that occurred
 */
  handleViewModeError(error) {
    let message = '表示形式の変更に失敗しました。';

    if (error.message) {
      message += ` (${error.message})`;
    }

    showToast(message, 'error');
  }

  /**
 * Revert toggle state to previous selection
 */
  revertToggleState() {
    const currentButton = this.container.querySelector(`input[value="${this.currentViewMode}"]`);
    if (currentButton) {
      currentButton.checked = true;
    }
  }

  /**
 * Update toggle button active states
 * @param {string} activeMode - The active view mode
 */
  updateToggleStates(activeMode) {
    this.toggleButtons.forEach(button => {
      const label = document.querySelector(`label[for="${button.id}"]`);
      if (button.value === activeMode) {
        button.checked = true;
        if (label) {
          label.classList.add('active');
        }
      } else {
        button.checked = false;
        if (label) {
          label.classList.remove('active');
        }
      }
    });
  }

  /**
 * Get current view mode
 * @returns {string} Current view mode
 */
  getCurrentViewMode() {
    return this.currentViewMode;
  }

  /**
 * Programmatically set view mode
 * @param {string} viewMode - The view mode to set
 */
  async setViewMode(viewMode) {
    if (['card', 'table'].includes(viewMode)) {
      await this.handleViewModeChange(viewMode);
    }
  }

  /**
 * Destroy the view toggle instance
 */
  destroy() {
    // Remove event listeners
    this.toggleButtons.forEach(button => {
      button.removeEventListener('change', this.handleViewModeChange);
    });

    // Clear references
    this.container = null;
    this.toggleButtons = null;
    this.cardView = null;
    this.tableView = null;
  }
}

/**
 * Initialize facility view toggle functionality
 * @returns {FacilityViewToggle|null} The initialized view toggle instance or null
 */
export function initializeFacilityViewToggle() {
  const viewToggle = new FacilityViewToggle();
  const initialized = viewToggle.init();

  return initialized ? viewToggle : null;
}

/**
 * Export the class for direct usage
 */
export { FacilityViewToggle };

/**
 * Auto-initialize if on facility pages
 */
document.addEventListener('DOMContentLoaded', () => {
  // Only initialize on facility show pages
  if (window.location.pathname.match(/\/facilities\/\d+$/)) {
    initializeFacilityViewToggle();
  }
});
