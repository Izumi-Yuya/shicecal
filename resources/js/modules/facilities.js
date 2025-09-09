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
              average: avgTime.toFixed(2) + 'ms',
              samples: this.tabSwitchTimes.length
            });
            this.tabSwitchTimes = []; // Reset
          }
        }, 0);
      });
    });
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