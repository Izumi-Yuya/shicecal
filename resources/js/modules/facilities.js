/**
 * Facilities Module - ES6 Module
 * Handles facility detail page functionality including tab switching and lazy loading
 */

export class FacilityManager {
  constructor(facilityId) {
    this.facilityId = facilityId;
    this.landInfoLoaded = false;
    this.tabSwitchTimes = [];
    this.init();
  }

  init() {
    this.setupLazyLoading();
    this.setupPerformanceMonitoring();
    this.setupTabHandling();
  }

  setupLazyLoading() {
    const landTab = document.getElementById('land-tab');
    const landInfoPane = document.getElementById('land-info');

    if (landTab && landInfoPane) {
      landTab.addEventListener('click', () => {
        if (!this.landInfoLoaded) {
          this.loadLandInfo();
        }
      });
    }
  }

  loadLandInfo() {
    const landInfoPane = document.getElementById('land-info');
    const loadingDiv = landInfoPane.querySelector('.land-info-loading');
    const contentDiv = landInfoPane.querySelector('.land-info-content');

    if (loadingDiv && contentDiv) {
      // Show loading spinner
      loadingDiv.style.display = 'block';
      contentDiv.style.display = 'none';

      // Simulate loading delay for heavy content
      setTimeout(() => {
        // Hide loading spinner and show content
        loadingDiv.style.display = 'none';
        contentDiv.style.display = 'block';

        this.landInfoLoaded = true;
      }, 500);
    }
  }

  setupPerformanceMonitoring() {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
      tab.addEventListener('click', () => {
        const startTime = performance.now();

        setTimeout(() => {
          const endTime = performance.now();
          const switchTime = endTime - startTime;
          this.tabSwitchTimes.push(switchTime);

          // Log performance metrics every 10 tab switches
          if (this.tabSwitchTimes.length >= 10) {
            const avgTime = this.tabSwitchTimes.reduce((a, b) => a + b, 0) / this.tabSwitchTimes.length;
            console.debug('Tab switch performance:', {
              average: `${avgTime.toFixed(2)}ms`,
              samples: this.tabSwitchTimes.length
            });
            this.tabSwitchTimes = []; // Reset
          }
        }, 0);
      });
    });
  }

  setupTabHandling() {
    // Handle URL hash for direct tab access
    this.handleUrlHash();

    // Update URL hash when tab changes
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
      tab.addEventListener('shown.bs.tab', (e) => {
        const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
        // Update URL hash without triggering page scroll
        history.replaceState(null, null, `#${targetId}`);
      });
    });

    // Listen for hash changes
    window.addEventListener('hashchange', () => {
      this.handleUrlHash();
    });
  }

  handleUrlHash() {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
      const tabPane = document.getElementById(hash);
      const tabButton = document.querySelector(`[data-bs-target="#${hash}"]`);

      if (tabPane && tabButton) {
        // Use Bootstrap's tab API to switch tabs
        const tab = new bootstrap.Tab(tabButton);
        tab.show();
      }
    }
  }

  // Public method to switch to a specific tab
  switchToTab(tabId) {
    const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (tabButton) {
      const tab = new bootstrap.Tab(tabButton);
      tab.show();
    }
  }
}

/**
 * Initialize facility manager
 * @param {string|number} facilityId - Facility ID (optional, will use window.facilityId if not provided)
 * @returns {FacilityManager} - Facility manager instance
 */
export function initializeFacilityManager(facilityId = null) {
  const id = facilityId || window.facilityId;
  if (!id) {
    console.warn('No facility ID provided for FacilityManager');
    return null;
  }
  return new FacilityManager(id);
}

// Auto-initialize if facility ID is available
document.addEventListener('DOMContentLoaded', () => {
  if (window.facilityId) {
    initializeFacilityManager();
  }
});
