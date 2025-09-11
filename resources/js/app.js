/**
 * Main Application JavaScript - ES6 Module Entry Point
 * Shise-Cal Facility Management System
 */

// Import land-info module
import './land-info-final.js';

// Import facility form layout module
import { initializeFacilityFormLayout, FacilityFormUtils } from './modules/facility-form-layout.js';

// Import shared utilities and modules
import {
  formatCurrency,
  formatArea,
  formatDate,
  debounce,
  showToast,
  confirmDialog,
  showLoading,
  hideLoading
} from './shared/utils.js';

import { initializeLayout } from './shared/layout.js';

import { get, post, put, del, downloadFile } from './shared/api.js';
import { validateForm, displayFormErrors, clearFormErrors } from './shared/validation.js';

// Import feature modules
import { initializeFacilityManager } from './modules/facilities.js';
import { initializeNotificationManager } from './modules/notifications.js';
import { initializeExportManager } from './modules/export.js';
import { initialize as initializeServiceTableManager } from './modules/service-table-manager.js';
import { initializeFacilityViewToggle } from './modules/facility-view-toggle.js';
import { initializeTableViewComments } from './modules/table-view-comments.js';

// Import component modules
import {
  FormValidator,
  SearchComponent,
  TableComponent,
  ModalComponent,
  ServiceCardsComponent
} from './shared/components.js';

import { initializeSidebar } from './shared/sidebar.js';

/**
 * Application Configuration
 */
const AppConfig = {
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
  locale: document.documentElement.lang || 'ja'
};

/**
 * Application State Management
 */
class ApplicationState {
  constructor() {
    this.modules = {
      facility: null,
      notification: null,
      export: null,
      sidebar: null,
      facilityFormLayout: null
    };
    this.components = {
      search: null,
      table: null,
      serviceCards: null
    };
  }

  setModule(name, instance) {
    this.modules[name] = instance;
  }

  getModule(name) {
    return this.modules[name];
  }

  setComponent(name, instance) {
    this.components[name] = instance;
  }

  getComponent(name) {
    return this.components[name];
  }
}

// Create global application state
const appState = new ApplicationState();

/**
 * Legacy API for backward compatibility
 * @deprecated Use ES6 modules instead
 */
function createLegacyAPI() {
  return {
    config: AppConfig,
    utils: {
      formatCurrency,
      formatArea,
      formatDate,
      debounce,
      showToast,
      confirmDialog,
      showLoading,
      hideLoading,
      // Legacy AJAX function
      ajax: function (url, options = {}) {
        const defaultOptions = {
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': AppConfig.csrfToken || '',
            'Accept': 'application/json',
            ...options.headers
          },
          ...options
        };

        return fetch(url, defaultOptions)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .catch(error => {
            console.error('AJAX Error:', error);
            showToast('通信エラーが発生しました。', 'error');
            throw error;
          });
      },
      // Facility form utilities
      ...FacilityFormUtils,
      // Legacy confirm function
      confirm: function (message, callback) {
        if (window.confirm(message)) {
          callback();
        }
      }
    },
    api: {
      get,
      post,
      put,
      del,
      downloadFile
    },
    validation: {
      validateForm,
      displayFormErrors,
      clearFormErrors
    },
    components: {
      FormValidator,
      Modal: ModalComponent
    },
    modules: appState.modules
  };
}

/**
 * Application Initialization
 */
class Application {
  constructor() {
    this.initialized = false;
  }

  async init() {
    if (this.initialized) return;

    console.log('Initializing Shise-Cal application...');

    // Initialize components
    this.initializeComponents();

    // Initialize feature modules based on page context
    await this.initializeModules();

    // Initialize sidebar
    appState.setModule('sidebar', initializeSidebar());

    // Initialize layout functionality (notifications, etc.)
    initializeLayout();

    // Setup global event handlers
    this.setupGlobalEventHandlers();

    // Setup UI enhancements
    this.setupUIEnhancements();

    this.initialized = true;
    console.log('Shise-Cal application initialized successfully');
  }

  initializeComponents() {
    // Initialize search component
    appState.setComponent('search', new SearchComponent());

    // Initialize table component
    appState.setComponent('table', new TableComponent());

    // Initialize service cards component
    appState.setComponent('serviceCards', new ServiceCardsComponent());
  }

  async initializeModules() {
    const currentPath = window.location.pathname;

    // Initialize facility form layout on form pages
    if (document.querySelector('.facility-edit-layout') || document.getElementById('landInfoForm')) {
      appState.setModule('facilityFormLayout', initializeFacilityFormLayout({
        enableAutoSave: true,
        enableRealTimeValidation: true,
        enableMobileOptimization: true,
        enableAccessibility: true
      }));
    }

    // Initialize service table manager on pages with service tables
    if (document.querySelector('[data-service-table]')) {
      initializeServiceTableManager();
    }

    // Initialize facility module on facility pages
    if (currentPath.includes('/facilities/')) {
      const facilityIdMatch = currentPath.match(/\/facilities\/(\d+)/);
      if (facilityIdMatch) {
        const facilityId = facilityIdMatch[1];
        appState.setModule('facility', initializeFacilityManager(facilityId));

        // Initialize view toggle on facility show pages
        if (currentPath.match(/\/facilities\/\d+$/)) {
          appState.setModule('viewToggle', initializeFacilityViewToggle());
          appState.setModule('tableComments', initializeTableViewComments(facilityId));
        }
      }
    }

    // Initialize notification module on notification pages
    if (currentPath.includes('/notifications')) {
      appState.setModule('notification', initializeNotificationManager());
    }

    // Initialize export module on export pages
    if (currentPath.includes('/export')) {
      appState.setModule('export', initializeExportManager());
    }
  }

  setupGlobalEventHandlers() {
    // Handle form submissions with loading states
    const forms = document.querySelectorAll('form[data-loading]');
    forms.forEach(form => {
      form.addEventListener('submit', (event) => {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
        }
      });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
      setTimeout(() => {
        alert.style.transition = 'opacity 0.5s ease-in-out';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
      }, 5000);
    });
  }

  setupUIEnhancements() {
    // Add fade-in animation to main content
    const mainContent = document.querySelector('main');
    if (mainContent) {
      mainContent.classList.add('fade-in');
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map((tooltipTriggerEl) => {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map((popoverTriggerEl) => {
      return new bootstrap.Popover(popoverTriggerEl);
    });
  }
}

// Create application instance
const app = new Application();

/**
 * DOM Content Loaded Event Handler
 */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    await app.init();
  } catch (error) {
    console.error('Failed to initialize application:', error);
    showToast('アプリケーションの初期化に失敗しました。', 'error');
  }
});

/**
 * Setup global API for backward compatibility
 * This allows existing code to continue working while we transition to ES6 modules
 */
window.ShiseCal = createLegacyAPI();

/**
 * Export main application components and utilities for ES6 module usage
 */
export {
  Application,
  ApplicationState,
  AppConfig,
  appState,
  app,
  // Re-export utilities for convenience
  formatCurrency,
  formatArea,
  formatDate,
  debounce,
  showToast,
  confirmDialog,
  showLoading,
  hideLoading,
  // Re-export API functions
  get,
  post,
  put,
  del,
  downloadFile,
  // Re-export validation functions
  validateForm,
  displayFormErrors,
  clearFormErrors,
  // Re-export components
  FormValidator,
  SearchComponent,
  TableComponent,
  ModalComponent,
  ServiceCardsComponent,
  // Re-export module initializers
  initializeFacilityManager,
  initializeNotificationManager,
  initializeExportManager,
  initializeFacilityViewToggle,
  initializeFacilityFormLayout,
  initializeSidebar,
  // Re-export facility form utilities
  FacilityFormUtils
};