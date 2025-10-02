/**
 * 統合されたメインアプリケーションJavaScript
 * - 重複を排除し、統合されたモジュール
 * - パフォーマンス最適化
 */

// 統合されたアプリケーションJavaScript
import {
  Application,
  AppUtils,
  ApiClient,
  ModalManager,
  FacilityManager,
  DocumentManager,
  app
} from './app-unified.js';

// 必要最小限の個別モジュール
import './land-info-final.js';

// 個別に必要なモジュール（統合されていないもの）
import { initializeFacilityFormLayout, FacilityFormUtils } from './modules/facility-form-layout.js';
import { initializeLayout } from './shared/layout.js';
import { validateForm, displayFormErrors, clearFormErrors } from './shared/validation.js';
import { initializeNotificationManager } from './modules/notifications.js';
import { initializeExportManager } from './modules/export.js';
import { initializeLifelineEquipmentManager } from './modules/lifeline-equipment.js';
import { initializeFacilityViewToggle } from './modules/facility-view-toggle.js';
import { initializeDetailCardController } from './modules/detail-card-controller.js';
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
 * Application State Management (統合版と併用)
 */
class ApplicationState {
  constructor() {
    this.modules = {
      facility: null,
      notification: null,
      export: null,
      sidebar: null,
      facilityFormLayout: null,
      detailCardController: null,
      lifelineEquipment: null
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
      formatCurrency: AppUtils.formatCurrency,
      formatArea: AppUtils.formatArea,
      formatDate: AppUtils.formatDate,
      debounce: AppUtils.debounce,
      showToast: AppUtils.showToast,
      confirmDialog: AppUtils.confirmDialog,
      showLoading: AppUtils.showLoading,
      hideLoading: AppUtils.hideLoading,
      // Legacy AJAX function
      ajax: function (url, options = {}) {
        const api = new ApiClient();
        return api.request(url, options);
      },
      // Facility form utilities
      ...FacilityFormUtils,
      // Legacy confirm function
      confirm: function (message, callback) {
        AppUtils.confirmDialog(message).then(result => {
          if (result && callback) {
            callback();
          }
        });
      }
    },
    api: new ApiClient(),
    validation: {
      validateForm,
      displayFormErrors,
      clearFormErrors
    },
    components: {
      FormValidator,
      Modal: ModalComponent
    },
    modules: appState.modules,
    // Detail card controller utilities
    detailCard: {
      refresh: function () {
        try {
          const controller = appState.getModule('detailCardController');
          if (controller && typeof controller.refresh === 'function') {
            return controller.refresh();
          }
          return false;
        } catch (error) {
          console.error('Error refreshing detail card controller:', error);
          return false;
        }
      },
      getStatistics: function () {
        try {
          const controller = appState.getModule('detailCardController');
          if (controller && typeof controller.getStatistics === 'function') {
            return controller.getStatistics();
          }
          return null;
        } catch (error) {
          console.error('Error getting detail card statistics:', error);
          return null;
        }
      },
      clearPreferences: function () {
        try {
          const controller = appState.getModule('detailCardController');
          if (controller && typeof controller.clearUserPreferences === 'function') {
            controller.clearUserPreferences();
            return true;
          }
          return false;
        } catch (error) {
          console.error('Error clearing detail card preferences:', error);
          return false;
        }
      }
    }
  };
}

/**
 * Extended Application Class (統合版を拡張)
 */
class ExtendedApplication extends Application {
  async initializeModules() {
    // 統合版の初期化を実行
    await super.initializeModules();

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

    // Initialize facility module on facility pages
    if (currentPath.includes('/facilities/')) {
      const facilityIdMatch = currentPath.match(/\/facilities\/(\d+)/);
      if (facilityIdMatch) {
        const facilityId = facilityIdMatch[1];

        // Initialize view toggle on facility show pages
        if (currentPath.match(/\/facilities\/\d+$/)) {
          appState.setModule('viewToggle', initializeFacilityViewToggle());

          // Initialize lifeline equipment manager on facility detail pages
          const lifelineManager = initializeLifelineEquipmentManager();
          if (lifelineManager) {
            appState.setModule('lifelineEquipment', lifelineManager);
            console.log('Lifeline Equipment manager initialized');
          }
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

    // Initialize detail card controller on pages with detail cards - Optimized
    try {
      if (document.querySelector('.detail-card-improved, .facility-info-card, .card')) {
        const detailCardController = await initializeDetailCardController();
        if (detailCardController) {
          appState.setModule('detailCardController', detailCardController);
          console.log('Detail card controller initialized successfully');
        } else {
          console.warn('Detail card controller initialization returned null - no detail cards found or initialization failed');
        }
      }
    } catch (error) {
      console.error('Failed to initialize detail card controller:', error);
      // Fallback: Continue without detail card functionality
      AppUtils.showToast('詳細カード機能の初期化に失敗しました。基本機能は利用できます。', 'warning');
    }

    // Initialize sidebar
    appState.setModule('sidebar', initializeSidebar());

    // Initialize layout functionality (notifications, etc.)
    initializeLayout();
  }

  initializeComponents() {
    // Initialize search component
    appState.setComponent('search', new SearchComponent());

    // Initialize table component
    appState.setComponent('table', new TableComponent());

    // Initialize service cards component
    appState.setComponent('serviceCards', new ServiceCardsComponent());
  }
}

// Create extended application instance
const extendedApp = new ExtendedApplication();

/**
 * DOM Content Loaded Event Handler
 */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    await extendedApp.init();
  } catch (error) {
    console.error('Failed to initialize application:', error);
    AppUtils.showToast('アプリケーションの初期化に失敗しました。', 'error');

    // Try to initialize critical components individually as fallback
    try {
      // Initialize detail card controller as fallback if main init failed
      if (document.querySelector('.detail-card-improved, .facility-info-card, .card')) {
        const fallbackController = initializeDetailCardController();
        if (fallbackController) {
          appState.setModule('detailCardController', fallbackController);
          console.log('Detail card controller initialized as fallback');
        }
      }
    } catch (fallbackError) {
      console.error('Fallback initialization also failed:', fallbackError);
    }
  }
});

/**
 * Setup global API for backward compatibility
 * This allows existing code to continue working while we transition to ES6 modules
 */
window.ShiseCal = createLegacyAPI();

/**
 * Cleanup on page unload
 */
window.addEventListener('beforeunload', () => {
  try {
    extendedApp.cleanup();
  } catch (error) {
    console.error('Error during page unload cleanup:', error);
  }
});

/**
 * Export main application components and utilities for ES6 module usage
 */
export {
  ExtendedApplication,
  ApplicationState,
  AppConfig,
  appState,
  extendedApp as app,
  // Re-export utilities for convenience
  AppUtils,
  ApiClient,
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
  initializeNotificationManager,
  initializeExportManager,
  initializeLifelineEquipmentManager,
  initializeFacilityViewToggle,
  initializeFacilityFormLayout,
  initializeSidebar,
  initializeDetailCardController,
  // Re-export facility form utilities
  FacilityFormUtils
};