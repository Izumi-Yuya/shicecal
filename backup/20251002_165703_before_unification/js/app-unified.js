/**
 * 統合されたアプリケーションJavaScript
 * - 全ての重複を削除
 * - モジュラー設計
 * - パフォーマンス最適化
 */

/* ========================================
   Core Utilities (統合版)
   ======================================== */
class AppUtils {
  static getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  static formatCurrency(amount, currency = 'JPY') {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '¥0';
    }
    return new Intl.NumberFormat('ja-JP', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  static formatArea(area, unit = 'm²') {
    if (area === null || area === undefined || isNaN(area)) {
      return `0${unit}`;
    }
    return `${parseFloat(area).toLocaleString('ja-JP')}${unit}`;
  }

  static formatDate(date, includeTime = false) {
    if (!date) return '';
    const dateObj = date instanceof Date ? date : new Date(date);
    if (isNaN(dateObj.getTime())) return '';

    const options = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Asia/Tokyo'
    };

    if (includeTime) {
      options.hour = '2-digit';
      options.minute = '2-digit';
    }

    return dateObj.toLocaleDateString('ja-JP', options);
  }

  static debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  static escapeHtml(text) {
    if (typeof text !== 'string') return text;
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  static showLoading(element, text = '読み込み中...') {
    if (!element) return;
    element.dataset.originalContent = element.innerHTML;
    element.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text}`;
    element.disabled = true;
  }

  static hideLoading(element) {
    if (!element) return;
    if (element.dataset.originalContent) {
      element.innerHTML = element.dataset.originalContent;
      delete element.dataset.originalContent;
    }
    element.disabled = false;
  }

  static showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      container.style.zIndex = '9999';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${this.escapeHtml(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    `;

    container.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
      toast.remove();
    });
  }

  static confirmDialog(message, title = '確認') {
    return new Promise((resolve) => {
      let modal = document.getElementById('confirm-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.className = 'modal fade';
        modal.innerHTML = `
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirm-modal-title">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="confirm-modal-body"></div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="confirm-modal-ok">OK</button>
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(modal);
      }

      document.getElementById('confirm-modal-title').textContent = title;
      document.getElementById('confirm-modal-body').textContent = message;

      const okButton = document.getElementById('confirm-modal-ok');
      const newOkButton = okButton.cloneNode(true);
      okButton.parentNode.replaceChild(newOkButton, okButton);

      newOkButton.addEventListener('click', () => {
        bootstrap.Modal.getInstance(modal).hide();
        resolve(true);
      });

      modal.addEventListener('hidden.bs.modal', () => {
        resolve(false);
      }, { once: true });

      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    });
  }
}

/* ========================================
   API Client (統合版)
   ======================================== */
class ApiClient {
  constructor() {
    this.defaultConfig = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    };
  }

  addCsrfToken(headers = {}) {
    const token = AppUtils.getCsrfToken();
    if (token) {
      headers['X-CSRF-TOKEN'] = token;
    }
    return headers;
  }

  async handleResponse(response) {
    const contentType = response.headers.get('content-type');
    let data;

    if (contentType && contentType.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    if (!response.ok) {
      const error = new Error(data.message || `HTTP error! status: ${response.status}`);
      error.status = response.status;
      error.data = data;
      throw error;
    }

    return data;
  }

  handleError(error, showNotification = true) {
    console.error('API Error:', error);

    if (showNotification) {
      let message = 'エラーが発生しました。';

      if (error.status === 401) {
        message = 'ログインが必要です。';
      } else if (error.status === 403) {
        message = 'この操作を実行する権限がありません。';
      } else if (error.status === 404) {
        message = 'リソースが見つかりません。';
      } else if (error.status === 422) {
        message = '入力データに問題があります。';
      } else if (error.status >= 500) {
        message = 'サーバーエラーが発生しました。';
      } else if (error.data && error.data.message) {
        message = error.data.message;
      }

      AppUtils.showToast(message, 'error');
    }

    throw error;
  }

  async request(url, options = {}) {
    try {
      const config = {
        ...this.defaultConfig,
        ...options,
        headers: {
          ...this.defaultConfig.headers,
          ...this.addCsrfToken(),
          ...options.headers
        }
      };

      const response = await fetch(url, config);
      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error, options.showNotification !== false);
    }
  }

  async get(url, options = {}) {
    return this.request(url, { ...options, method: 'GET' });
  }

  async post(url, data = {}, options = {}) {
    return this.request(url, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async put(url, data = {}, options = {}) {
    return this.request(url, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async delete(url, options = {}) {
    return this.request(url, { ...options, method: 'DELETE' });
  }

  async uploadFile(url, formData, options = {}) {
    const config = {
      method: 'POST',
      headers: this.addCsrfToken(),
      body: formData,
      credentials: 'same-origin'
    };

    try {
      const response = await fetch(url, config);
      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error, options.showNotification !== false);
    }
  }
}

/* ========================================
   Modal Manager (統合版)
   ======================================== */
class ModalManager {
  constructor() {
    this.activeModals = new Set();
    this.init();
  }

  init() {
    document.addEventListener('show.bs.modal', (e) => {
      this.activeModals.add(e.target.id);
      console.log('Modal opened:', e.target.id);
    });

    document.addEventListener('hidden.bs.modal', (e) => {
      this.activeModals.delete(e.target.id);
      console.log('Modal closed:', e.target.id);

      setTimeout(() => this.cleanupOrphanedBackdrops(), 100);
    });

    setInterval(() => this.cleanupOrphanedBackdrops(), 5000);
  }

  cleanupOrphanedBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const visibleModals = document.querySelectorAll('.modal.show');

    if (backdrops.length > 0 && visibleModals.length === 0) {
      console.log('Cleaning up orphaned backdrops');
      backdrops.forEach(backdrop => backdrop.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }
  }

  forceCleanupAll() {
    console.log('Force cleanup all modals and backdrops');

    document.querySelectorAll('.modal').forEach(modal => {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
      modal.classList.remove('show');
      modal.style.display = 'none';
    });

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
      backdrop.remove();
    });

    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    this.activeModals.clear();
  }
}

/* ========================================
   Facility Manager (統合版)
   ======================================== */
class FacilityManager {
  constructor(facilityId) {
    this.facilityId = facilityId;
    this.landInfoLoaded = false;
    this.tabSwitchTimes = [];
    this.init();
  }

  init() {
    this.setupLazyLoading();
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
    const loadingDiv = landInfoPane?.querySelector('.land-info-loading');
    const contentDiv = landInfoPane?.querySelector('.land-info-content');

    if (loadingDiv && contentDiv) {
      loadingDiv.style.display = 'block';
      contentDiv.style.display = 'none';

      setTimeout(() => {
        loadingDiv.style.display = 'none';
        contentDiv.style.display = 'block';
        this.landInfoLoaded = true;
      }, 500);
    }
  }

  setupTabHandling() {
    this.handleUrlHash();

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
      tab.addEventListener('shown.bs.tab', (e) => {
        const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
        history.replaceState(null, null, `#${targetId}`);
      });
    });

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
        const tab = new bootstrap.Tab(tabButton);
        tab.show();
      }
    }
  }

  switchToTab(tabId) {
    const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (tabButton) {
      const tab = new bootstrap.Tab(tabButton);
      tab.show();
    }
  }
}

/* ========================================
   Document Manager (統合版)
   ======================================== */
class DocumentManager {
  constructor(options = {}) {
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.permissions = options.permissions || {};
    this.api = new ApiClient();
    this.initialized = false;

    console.log('DocumentManager initialized for facility:', this.facilityId);
  }

  async init() {
    if (this.initialized) return;

    try {
      console.log('Initializing document management...');

      const container = document.getElementById('document-management-container');
      if (!container) {
        console.error('Document management container not found');
        return;
      }

      this.bindEvents();
      await this.loadInitialData();

      this.initialized = true;
      console.log('Document management initialized successfully');

    } catch (error) {
      console.error('Failed to initialize document management:', error);
      this.showError('ドキュメント管理の初期化に失敗しました。');
    }
  }

  bindEvents() {
    const createFolderForm = document.getElementById('create-folder-form');
    if (createFolderForm) {
      createFolderForm.addEventListener('submit', (e) => this.handleCreateFolder(e));
    }

    const uploadFileForm = document.getElementById('upload-file-form');
    if (uploadFileForm) {
      uploadFileForm.addEventListener('submit', (e) => this.handleUploadFile(e));
    }

    const emptyUploadBtn = document.getElementById('empty-upload-btn');
    if (emptyUploadBtn) {
      emptyUploadBtn.addEventListener('click', () => this.showUploadModal());
    }
  }

  async loadInitialData() {
    try {
      this.showLoading();
      this.showEmptyState();
    } catch (error) {
      console.error('Failed to load initial data:', error);
      this.showError('データの読み込みに失敗しました。');
    } finally {
      this.hideLoading();
    }
  }

  async handleCreateFolder(e) {
    e.preventDefault();

    try {
      const formData = new FormData(e.target);

      console.log('Creating folder:', {
        name: formData.get('name'),
        parent_id: formData.get('parent_id')
      });

      const data = await this.api.uploadFile('/documents/folders', formData);

      if (data.success) {
        this.hideModal('create-folder-modal');
        e.target.reset();
        AppUtils.showToast(data.message || 'フォルダを作成しました', 'success');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        throw new Error(data.message || 'フォルダの作成に失敗しました');
      }

    } catch (error) {
      console.error('Failed to create folder:', error);
      AppUtils.showToast('フォルダの作成に失敗しました: ' + error.message, 'error');
    }
  }

  async handleUploadFile(e) {
    e.preventDefault();

    try {
      const formData = new FormData(e.target);

      console.log('Uploading files...');

      const data = await this.api.uploadFile('/documents/files', formData);

      if (data.success) {
        this.hideModal('upload-file-modal');
        e.target.reset();
        AppUtils.showToast(data.message || 'ファイルをアップロードしました', 'success');
        setTimeout(() => window.location.reload(), 1000);
      } else {
        throw new Error(data.message || 'ファイルのアップロードに失敗しました');
      }

    } catch (error) {
      console.error('Failed to upload file:', error);
      AppUtils.showToast('ファイルのアップロードに失敗しました: ' + error.message, 'error');
    }
  }

  showUploadModal() {
    const modal = document.getElementById('upload-file-modal');
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
      modalInstance.show();
    }
  }

  hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }

  showLoading() {
    const loading = document.getElementById('loading-indicator');
    const documentList = document.getElementById('document-list');
    const errorMessage = document.getElementById('error-message');
    const emptyState = document.getElementById('empty-state');

    if (loading) loading.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
  }

  hideLoading() {
    const loading = document.getElementById('loading-indicator');
    if (loading) loading.style.display = 'none';
  }

  showError(message) {
    const errorText = document.getElementById('error-text');
    const errorMessage = document.getElementById('error-message');
    const documentList = document.getElementById('document-list');
    const emptyState = document.getElementById('empty-state');
    const loading = document.getElementById('loading-indicator');

    if (errorText) errorText.textContent = message;
    if (errorMessage) errorMessage.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
    if (loading) loading.style.display = 'none';
  }

  showEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const documentList = document.getElementById('document-list');
    const errorMessage = document.getElementById('error-message');
    const loading = document.getElementById('loading-indicator');

    if (emptyState) emptyState.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    if (loading) loading.style.display = 'none';
  }
}

/* ========================================
   Application Class (統合版)
   ======================================== */
class Application {
  constructor() {
    this.initialized = false;
    this.modules = {};
    this.api = new ApiClient();
    this.modalManager = new ModalManager();
  }

  async init() {
    if (this.initialized) return;

    console.log('Initializing Shise-Cal application...');

    try {
      this.setupGlobalEventHandlers();
      this.setupUIEnhancements();
      await this.initializeModules();

      this.initialized = true;
      console.log('Shise-Cal application initialized successfully');
    } catch (error) {
      console.error('Failed to initialize application:', error);
      AppUtils.showToast('アプリケーションの初期化に失敗しました。', 'error');
    }
  }

  async initializeModules() {
    const currentPath = window.location.pathname;

    // Initialize facility module on facility pages
    if (currentPath.includes('/facilities/')) {
      const facilityIdMatch = currentPath.match(/\/facilities\/(\d+)/);
      if (facilityIdMatch) {
        const facilityId = facilityIdMatch[1];
        this.modules.facility = new FacilityManager(facilityId);

        // Initialize document management on facility detail pages
        if (currentPath.match(/\/facilities\/\d+$/)) {
          await this.initializeDocumentManagement(facilityId);
        }
      }
    }
  }

  async initializeDocumentManagement(facilityId) {
    try {
      const documentContainer = document.getElementById('document-management-container');
      if (!documentContainer) {
        console.log('Document management container not found, skipping initialization');
        return;
      }

      const documentManager = new DocumentManager({
        facilityId: facilityId,
        baseUrl: `/facilities/${facilityId}/documents`,
        permissions: {
          canCreate: documentContainer.dataset.canCreate === 'true',
          canUpdate: documentContainer.dataset.canUpdate === 'true',
          canDelete: documentContainer.dataset.canDelete === 'true'
        }
      });

      this.modules.documentManager = documentManager;

      const isDocumentsPage = window.location.pathname.includes('/documents');
      if (isDocumentsPage) {
        await documentManager.init();
      } else {
        const documentsTab = document.getElementById('documents-tab');
        if (documentsTab) {
          documentsTab.addEventListener('shown.bs.tab', async function () {
            if (!documentManager.initialized) {
              await documentManager.init();
            }
          });
        }
      }

      window.documentManager = documentManager;
      console.log('Document management setup completed for facility:', facilityId);
    } catch (error) {
      console.error('Failed to initialize document management:', error);

      const documentContainer = document.getElementById('document-management-container');
      if (documentContainer) {
        documentContainer.innerHTML = `
          <div class="alert alert-danger">
            <h5>エラー</h5>
            <p>ドキュメント管理の初期化に失敗しました。</p>
            <p><small>エラー詳細: ${error.message}</small></p>
            <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
              <i class="fas fa-refresh"></i> 再読み込み
            </button>
          </div>
        `;
      }
    }
  }

  setupGlobalEventHandlers() {
    const forms = document.querySelectorAll('form[data-loading]');
    forms.forEach(form => {
      form.addEventListener('submit', (event) => {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
          AppUtils.showLoading(submitButton, '処理中...');
        }
      });
    });

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
    const mainContent = document.querySelector('main');
    if (mainContent) {
      mainContent.classList.add('fade-in');
    }

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map((tooltipTriggerEl) => {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map((popoverTriggerEl) => {
      return new bootstrap.Popover(popoverTriggerEl);
    });
  }

  cleanup() {
    try {
      if (this.modalManager) {
        this.modalManager.forceCleanupAll();
      }
      console.log('Application cleanup completed');
    } catch (error) {
      console.error('Error during application cleanup:', error);
    }
  }
}

/* ========================================
   Global Initialization
   ======================================== */
const app = new Application();

document.addEventListener('DOMContentLoaded', async () => {
  try {
    await app.init();
  } catch (error) {
    console.error('Failed to initialize application:', error);
    AppUtils.showToast('アプリケーションの初期化に失敗しました。', 'error');
  }
});

window.addEventListener('beforeunload', () => {
  try {
    app.cleanup();
  } catch (error) {
    console.error('Error during page unload cleanup:', error);
  }
});

/* ========================================
   Legacy API (後方互換性)
   ======================================== */
window.ShiseCal = {
  utils: AppUtils,
  api: new ApiClient(),
  app: app,
  // Legacy functions
  formatCurrency: AppUtils.formatCurrency,
  formatArea: AppUtils.formatArea,
  formatDate: AppUtils.formatDate,
  showToast: AppUtils.showToast,
  confirmDialog: AppUtils.confirmDialog,
  showLoading: AppUtils.showLoading,
  hideLoading: AppUtils.hideLoading
};

// Global functions for backward compatibility
window.forceCleanupAllModals = () => app.modalManager?.forceCleanupAll();
window.immediateBackdropCleanup = () => app.modalManager?.cleanupOrphanedBackdrops();

/* ========================================
   Exports (ES6 Modules)
   ======================================== */
export {
  Application,
  AppUtils,
  ApiClient,
  ModalManager,
  FacilityManager,
  DocumentManager,
  app
};