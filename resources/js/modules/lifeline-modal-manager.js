/**
 * Lifeline Equipment Modal Manager
 * Unified modal handling for lifeline equipment sections
 */

export class LifelineModalManager {
  constructor(options = {}) {
    this.options = {
      modalSelector: '.modal',
      backdropZIndex: 2000,
      modalZIndex: 2010,
      ...options
    };

    this.boundHandlers = new Map();
    this.initializeModalHandlers();
  }

  /**
   * Initialize modal event handlers
   */
  initializeModalHandlers() {
    // Modal hoisting for collapsed sections
    document.addEventListener('shown.bs.collapse', (event) => {
      this.hoistModalsInContainer(event.target);
    });

    // Modal z-index management
    document.addEventListener('show.bs.modal', (event) => {
      this.handleModalShow(event);
    });

    // Backdrop cleanup
    document.addEventListener('hidden.bs.modal', () => {
      this.cleanupBackdrops();
    });
  }

  /**
   * Hoist modals from collapsed containers to body
   * @param {HTMLElement} container - The container to search for modals
   */
  hoistModalsInContainer(container) {
    if (!container) return;

    const modals = container.querySelectorAll('.modal');
    modals.forEach(modal => {
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }
    });
  }

  /**
   * Handle modal show event with proper z-index
   * @param {Event} event - The modal show event
   */
  handleModalShow(event) {
    const modal = event.target;
    if (modal) {
      modal.style.zIndex = '2010';
    }

    // Set backdrop z-index after a short delay
    setTimeout(() => {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach(backdrop => {
        backdrop.style.zIndex = '2000';
      });
    }, 0);
  }

  /**
   * Clean up excess modal backdrops
   */
  cleanupBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 1) {
      // Remove all but the last backdrop
      for (let i = 0; i < backdrops.length - 1; i++) {
        backdrops[i].remove();
      }
    }
  }

  /**
   * Initialize modals for a specific category
   * @param {string} category - The equipment category (electrical, gas, etc.)
   */
  initializeCategoryModals(category) {
    const documentSection = document.getElementById(`${category}-documents-section`);
    if (documentSection) {
      this.hoistModalsInContainer(documentSection);

      // Set overflow visible to prevent clipping
      documentSection.style.overflow = 'visible';
    }
  }

  /**
   * Initialize all lifeline equipment modals
   */
  initializeAllModals() {
    const categories = ['electrical', 'gas', 'water', 'elevator', 'hvac-lighting'];
    categories.forEach(category => {
      this.initializeCategoryModals(category);
    });
  }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const modalManager = new LifelineModalManager();
  modalManager.initializeAllModals();
});

// Export default
export default LifelineModalManager;