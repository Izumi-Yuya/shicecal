/**
 * 統合されたアプリケーションJavaScript
 * - 全ての重複を削除
 * - モジュラー設計
 * - パフォーマンス最適化
 */

// Bootstrap is loaded via CDN in the HTML, so no import needed
// window.bootstrap is available globally

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
// ModalManagerクラスを削除 - Bootstrapのデフォルト動作に任せる

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
    console.log('DocumentManager constructor called with options:', options);
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    this.initialized = false;
    this.currentFolderId = '';
    console.log('DocumentManager initialized with facilityId:', this.facilityId);

    // DOM読み込み完了後に初期化
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.bindEvents();
      });
    } else {
      this.bindEvents();
    }
  }

  // イベントバインディング
  bindEvents() {
    // フォルダ作成ボタン
    const createBtn = document.getElementById('create-folder-btn');
    if (createBtn) {
      createBtn.addEventListener('click', (e) => {
        this.showCreateFolderModal();
      });
    }

    // ファイルアップロードボタン
    const uploadBtn = document.getElementById('upload-file-btn');
    if (uploadBtn) {
      uploadBtn.addEventListener('click', (e) => {
        this.showUploadFileModal();
      });
    }

    // 空の状態のアップロードボタン
    const emptyUploadBtn = document.getElementById('empty-upload-btn');
    if (emptyUploadBtn) {
      emptyUploadBtn.addEventListener('click', (e) => {
        this.showUploadFileModal();
      });
    }

    // フォーム送信
    const createForm = document.getElementById('create-folder-form');
    if (createForm) {
      createForm.addEventListener('submit', (e) => {
        this.handleCreateFolder(e);
      });
    }

    const uploadForm = document.getElementById('upload-file-form');
    if (uploadForm) {
      uploadForm.addEventListener('submit', (e) => {
        this.handleUploadFile(e);
      });
    }

    // ファイル選択時の表示
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
      fileInput.addEventListener('change', (e) => {
        this.handleFileSelection(e);
      });
    }

    // 検索機能
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    if (searchInput && searchBtn) {
      const debouncedSearch = this.debounce(() => this.handleSearch(), 300);
      searchInput.addEventListener('input', debouncedSearch);
      searchBtn.addEventListener('click', () => this.handleSearch());
    }

    // フィルターとソート
    const fileTypeFilter = document.getElementById('file-type-filter');
    const sortSelect = document.getElementById('sort-select');
    if (fileTypeFilter) {
      fileTypeFilter.addEventListener('change', () => this.handleFilterChange());
    }
    if (sortSelect) {
      sortSelect.addEventListener('change', () => this.handleSortChange());
    }

    // 表示モード切替
    const viewModeInputs = document.querySelectorAll('input[name="view-mode"]');
    viewModeInputs.forEach(input => {
      input.addEventListener('change', (e) => {
        this.handleViewModeChange(e.target.value);
      });
    });

    // 全選択チェックボックス
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', (e) => {
        this.handleSelectAll(e.target.checked);
      });
    }

    // ドキュメントリストのイベント委譲
    const documentList = document.getElementById('document-list');
    if (documentList) {
      documentList.addEventListener('click', (e) => {
        this.handleDocumentListClick(e);
      });

      documentList.addEventListener('contextmenu', (e) => {
        this.handleContextMenu(e);
      });
    }

    // コンテキストメニューを隠す
    document.addEventListener('click', () => {
      this.hideContextMenu();
    });

    // パンくずナビゲーション
    const breadcrumbNav = document.getElementById('breadcrumb-nav');
    if (breadcrumbNav) {
      breadcrumbNav.addEventListener('click', (e) => {
        this.handleBreadcrumbClick(e);
      });
    }
  }

  async init() {
    if (this.initialized) return;

    // イベントバインディングを再実行
    this.bindEvents();

    // モーダルのアクセシビリティイベントを設定
    this.setupModalAccessibility();

    // 初期データ読み込み
    await this.loadDocuments();

    this.initialized = true;
  }

  // モーダルのアクセシビリティを改善
  setupModalAccessibility() {
    const modals = ['create-folder-modal', 'upload-file-modal'];

    modals.forEach(modalId => {
      const modal = document.getElementById(modalId);
      if (modal) {
        // モーダルが閉じられる前にフォーカスを適切に処理
        modal.addEventListener('hide.bs.modal', (e) => {
          const activeElement = document.activeElement;
          if (modal.contains(activeElement)) {
            activeElement.blur();
          }
        });

        // モーダルが完全に閉じられた後の処理
        modal.addEventListener('hidden.bs.modal', (e) => {
          // フォームをリセット
          const form = modal.querySelector('form');
          if (form) {
            form.reset();
          }
        });
      }
    });
  }

  async loadInitialData() {
    try {
      await this.loadDocuments();
    } catch (error) {
      console.error('Failed to load initial data:', error);
      this.showError('データの読み込みに失敗しました。');
    }
  }

  // 内部ユーティリティ: 複数候補IDから最初に見つかったモーダル要素を返す
  getModalByIds(idCandidates = []) {
    for (const id of idCandidates) {
      const el = document.getElementById(id);
      if (el) return el;
    }
    return null;
  }

  // モーダルを <body> 直下へ移動してスタッキングコンテキスト問題を回避
  ensureModalInBody(modalEl) {
    if (modalEl && modalEl.parentElement !== document.body) {
      document.body.appendChild(modalEl);
    }
  }

  // 余分に残ったバックドロップを整理（最後の1枚だけ残す）
  cleanupExtraBackdrops() {
    const backs = Array.from(document.querySelectorAll('.modal-backdrop'));
    if (backs.length > 1) {
      backs.slice(0, -1).forEach(b => b.remove());
    }
  }

  // 統合前の最もシンプルなテスト用実装
  async handleCreateFolder(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const folderName = formData.get('name');

    // バリデーション
    if (!folderName || folderName.trim() === '') {
      this.showError('フォルダ名を入力してください。');
      return;
    }

    // 送信ボタンを無効化
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = '作成中...';

    try {
      // CSRFトークンを追加
      formData.append('_token', this.csrfToken);
      formData.append('parent_id', this.getCurrentFolderId());

      // APIリクエスト送信（正しいルートを使用）
      const response = await fetch(`/facilities/${this.facilityId}/documents/folders`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // 成功時の処理
        this.showSuccess('フォルダが作成されました。');
        form.reset();
        this.closeModalSafely(['create-folder-modal', 'createFolderModal']);

        // ドキュメントリストを更新
        this.refreshDocumentList();
      } else {
        // エラー時の処理
        this.showError(result.message || 'フォルダの作成に失敗しました。');
      }
    } catch (error) {
      console.error('Folder creation error:', error);
      this.showError('ネットワークエラーが発生しました。');
    } finally {
      // 送信ボタンを復元
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }
  }

  async handleUploadFile(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const files = formData.getAll('files[]');

    // バリデーション
    if (!files || files.length === 0) {
      this.showError('アップロードするファイルを選択してください。');
      return;
    }

    // ファイルサイズチェック（10MB制限）
    const maxSize = 10 * 1024 * 1024; // 10MB
    for (const file of files) {
      if (file.size > maxSize) {
        this.showError(`ファイル "${file.name}" のサイズが大きすぎます。10MB以下のファイルを選択してください。`);
        return;
      }
    }

    // 送信ボタンを無効化
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'アップロード中...';

    // プログレスバー表示
    this.showUploadProgress(true);

    try {
      // CSRFトークンとフォルダIDを追加
      formData.append('_token', this.csrfToken);
      formData.append('folder_id', this.getCurrentFolderId());

      // APIリクエスト送信（正しいルートを使用）
      const response = await fetch(`/facilities/${this.facilityId}/documents/files`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // 成功時の処理
        const fileCount = files.length;
        const message = fileCount === 1
          ? 'ファイルがアップロードされました。'
          : `${fileCount}個のファイルがアップロードされました。`;

        this.showSuccess(message);
        form.reset();
        this.clearFileList();
        this.closeModalSafely(['upload-file-modal', 'uploadFileModal']);

        // ドキュメントリストを更新
        this.refreshDocumentList();
      } else {
        // エラー時の処理
        this.showError(result.message || 'ファイルのアップロードに失敗しました。');
      }
    } catch (error) {
      console.error('File upload error:', error);
      this.showError('ネットワークエラーが発生しました。');
    } finally {
      // 送信ボタンを復元
      submitButton.disabled = false;
      submitButton.textContent = originalText;
      this.showUploadProgress(false);
    }
  }

  // アクセシビリティを考慮したモーダルクローズ
  closeModalSafely(modalIds) {
    const modal = this.getModalByIds(modalIds);
    if (modal && window.bootstrap) {
      // フォーカスされている要素を記録
      const activeElement = document.activeElement;

      // モーダル内の要素がフォーカスされている場合、フォーカスを外す
      if (modal.contains(activeElement)) {
        activeElement.blur();
        // 少し待ってからモーダルを閉じる（フォーカス処理のため）
        setTimeout(() => {
          const modalInstance = bootstrap.Modal.getInstance(modal) || bootstrap.Modal.getOrCreateInstance(modal);
          if (modalInstance) {
            modalInstance.hide();
          }
        }, 10);
      } else {
        const modalInstance = bootstrap.Modal.getInstance(modal) || bootstrap.Modal.getOrCreateInstance(modal);
        if (modalInstance) {
          modalInstance.hide();
        }
      }
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
    const candidates = Array.isArray(modalId) ? modalId : [modalId, modalId.replace(/-([a-z])/g, (_, c) => c.toUpperCase())];
    const modal = this.getModalByIds(candidates);
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getInstance(modal) || bootstrap.Modal.getOrCreateInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
        // 非表示後に余分なバックドロップを掃除
        setTimeout(() => this.cleanupExtraBackdrops(), 0);
      }
    } else {
      console.warn('hideModal: modal not found for', candidates);
    }
  }

  forceCleanupModalBackdrop(modalId) {
    // Completely hands-off approach - Bootstrap handles everything automatically
    console.log('Modal cleanup requested - Bootstrap will handle automatically');
    // No DOM queries or manipulation - pure delegation to Bootstrap
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

  // モーダル管理はModalManagerクラスで統一管理

  // モーダル管理はModalManagerクラスに統一

  /**
   * デバウンス関数（統合前の実装）
   */
  debounce(func, wait) {
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

  // 統合前の最もシンプルなモーダル表示
  showCreateFolderModal() {
    const modal = this.getModalByIds(['create-folder-modal', 'createFolderModal']);

    if (modal && window.bootstrap) {
      try {
        this.ensureModalInBody(modal);
        this.cleanupExtraBackdrops();
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, focus: true, keyboard: true });
        modalInstance.show();
        // 入力に自動フォーカス
        modal.addEventListener('shown.bs.modal', () => {
          const autofocus = modal.querySelector('[autofocus], input, select, textarea, button.btn-primary');
          if (autofocus) autofocus.focus();
        }, { once: true });
      } catch (error) {
        console.error('Failed to show create folder modal:', error);
      }
    }
  }

  showUploadFileModal() {
    const modal = this.getModalByIds(['upload-file-modal', 'uploadFileModal']);

    if (modal && window.bootstrap) {
      try {
        this.ensureModalInBody(modal);
        this.cleanupExtraBackdrops();
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, focus: true, keyboard: true });
        modalInstance.show();
        // 入力に自動フォーカス
        modal.addEventListener('shown.bs.modal', () => {
          const autofocus = modal.querySelector('[autofocus], input, select, textarea, button.btn-primary');
          if (autofocus) autofocus.focus();
        }, { once: true });
      } catch (error) {
        console.error('Failed to show upload file modal:', error);
      }
    }
  }

  /**
   * デバウンス関数（統合前の実装）
   */
  debounce(func, wait) {
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

  // 現在のフォルダIDを取得
  getCurrentFolderId() {
    // URLパラメータまたはブレッドクラムから現在のフォルダIDを取得
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('folder_id') || '';
  }

  // エラーメッセージ表示
  showError(message) {
    AppUtils.showToast(message, 'error');
  }

  // 成功メッセージ表示
  showSuccess(message) {
    AppUtils.showToast(message, 'success');
  }

  // ドキュメントリストを更新
  refreshDocumentList() {
    // 現在のページを再読み込みしてドキュメントリストを更新
    // 将来的にはAJAXで部分更新に変更可能
    window.location.reload();
  }

  // アップロードプログレスバーの表示/非表示
  showUploadProgress(show) {
    const progressContainer = document.getElementById('upload-progress');
    if (progressContainer) {
      progressContainer.style.display = show ? 'block' : 'none';
    }
  }

  // ファイルリストをクリア
  clearFileList() {
    const fileList = document.getElementById('file-list');
    const selectedFiles = document.getElementById('selected-files');

    if (fileList) {
      fileList.style.display = 'none';
    }

    if (selectedFiles) {
      selectedFiles.innerHTML = '';
    }
  }

  // ファイル選択時の表示処理
  handleFileSelection(e) {
    const files = Array.from(e.target.files);
    const fileList = document.getElementById('file-list');
    const selectedFiles = document.getElementById('selected-files');

    if (files.length === 0) {
      if (fileList) fileList.style.display = 'none';
      return;
    }

    if (fileList) fileList.style.display = 'block';
    if (selectedFiles) {
      selectedFiles.innerHTML = files.map(file => {
        const size = this.formatFileSize(file.size);
        return `
          <div class="d-flex justify-content-between align-items-center py-1">
            <span><i class="fas fa-file me-2"></i>${file.name}</span>
            <small class="text-muted">${size}</small>
          </div>
        `;
      }).join('');
    }
  }

  // ファイルサイズフォーマット
  formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  // ドキュメント読み込み
  async loadDocuments(folderId = '', options = {}) {
    try {
      console.log('Loading documents:', { folderId, options, facilityId: this.facilityId });
      this.showLoading();

      // 正しいAPIエンドポイントを使用
      const baseUrl = folderId
        ? `/facilities/${this.facilityId}/documents/folders/${folderId}`
        : `/facilities/${this.facilityId}/documents/folders`;

      const url = new URL(baseUrl, window.location.origin);
      console.log('Request URL:', url.toString());
      if (options.search) {
        url.searchParams.set('search', options.search);
      }
      if (options.filter && options.filter !== 'all') {
        url.searchParams.set('filter', options.filter);
      }
      if (options.sort) {
        url.searchParams.set('sort', options.sort);
      }

      const response = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      console.log('Response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Response error:', errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }

      const result = await response.json();
      console.log('Response data:', result);
      console.log('Response data structure:', {
        success: result.success,
        hasData: !!result.data,
        folders: result.data?.folders?.length || 0,
        files: result.data?.files?.length || 0,
        breadcrumbs: result.data?.breadcrumbs?.length || 0
      });

      if (result.success && result.data) {
        this.renderDocuments(result.data);
        this.updateStats(result.data);
        this.updateBreadcrumbs(result.data.breadcrumbs);
      } else {
        console.error('Invalid response:', result);
        throw new Error(result.message || 'データの取得に失敗しました');
      }

    } catch (error) {
      console.error('Failed to load documents:', error);
      // APIエンドポイントが未実装の場合は空の状態を表示
      if (error.message.includes('404')) {
        this.showEmptyState();
      } else {
        this.showError('ドキュメントの読み込みに失敗しました。');
      }
    } finally {
      this.hideLoading();
    }
  }

  // ドキュメント表示
  renderDocuments(data) {
    const { folders = [], files = [] } = data;
    const hasContent = folders.length > 0 || files.length > 0;

    console.log('Content check:', {
      foldersCount: folders.length,
      filesCount: files.length,
      hasContent: hasContent
    });

    if (!hasContent) {
      console.log('No content found, showing empty state');
      this.showEmptyState();
      return;
    }

    const documentList = document.getElementById('document-list');
    const tableBody = document.getElementById('document-table-body');
    const gridContainer = document.getElementById('document-grid-container');

    if (documentList) documentList.style.display = 'block';

    // テーブル表示
    if (tableBody) {
      tableBody.innerHTML = [
        ...folders.map(folder => this.renderFolderRow(folder)),
        ...files.map(file => this.renderFileRow(file))
      ].join('');
    }

    // グリッド表示
    if (gridContainer) {
      gridContainer.innerHTML = [
        ...folders.map(folder => this.renderFolderCard(folder)),
        ...files.map(file => this.renderFileCard(file))
      ].join('');
    }

    this.hideEmptyState();
    this.hideError();
  }

  // フォルダ行の表示
  renderFolderRow(folder) {
    return `
      <tr class="document-item folder-item" data-type="folder" data-id="${folder.id}">
        <td>
          <input type="checkbox" class="form-check-input item-checkbox" value="${folder.id}">
        </td>
        <td>
          <a href="#" class="folder-link text-decoration-none" data-folder-id="${folder.id}">
            <i class="fas fa-folder text-warning me-2"></i>
            ${AppUtils.escapeHtml(folder.name)}
          </a>
        </td>
        <td><span class="text-muted">—</span></td>
        <td><small class="text-muted">${AppUtils.formatDate(folder.updated_at)}</small></td>
        <td><small class="text-muted">${AppUtils.escapeHtml(folder.created_by || '')}</small></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary btn-sm" data-action="rename" data-id="${folder.id}" data-type="folder">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" data-action="delete" data-id="${folder.id}" data-type="folder">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  // ファイル行の表示
  renderFileRow(file) {
    const fileIcon = this.getFileIcon(file.extension);
    return `
      <tr class="document-item file-item" data-type="file" data-id="${file.id}">
        <td>
          <input type="checkbox" class="form-check-input item-checkbox" value="${file.id}">
        </td>
        <td>
          <a href="${file.download_url}" class="file-link text-decoration-none" target="_blank">
            <i class="${fileIcon} me-2"></i>
            ${AppUtils.escapeHtml(file.name)}
          </a>
        </td>
        <td><small class="text-muted">${this.formatFileSize(file.size)}</small></td>
        <td><small class="text-muted">${AppUtils.formatDate(file.updated_at)}</small></td>
        <td><small class="text-muted">${AppUtils.escapeHtml(file.uploaded_by || '')}</small></td>
        <td>
          <div class="btn-group btn-group-sm">
            <a href="${file.download_url}" class="btn btn-outline-primary btn-sm" target="_blank">
              <i class="fas fa-download"></i>
            </a>
            <button class="btn btn-outline-secondary btn-sm" data-action="rename" data-id="${file.id}" data-type="file">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" data-action="delete" data-id="${file.id}" data-type="file">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  // フォルダカードの表示
  renderFolderCard(folder) {
    return `
      <div class="col-md-3 col-sm-4 col-6 mb-3">
        <div class="card document-card folder-card" data-type="folder" data-id="${folder.id}">
          <div class="card-body text-center">
            <i class="fas fa-folder fa-3x text-warning mb-2"></i>
            <h6 class="card-title">${AppUtils.escapeHtml(folder.name)}</h6>
            <small class="text-muted">${AppUtils.formatDate(folder.updated_at)}</small>
          </div>
        </div>
      </div>
    `;
  }

  // ファイルカードの表示
  renderFileCard(file) {
    const fileIcon = this.getFileIcon(file.extension);
    return `
      <div class="col-md-3 col-sm-4 col-6 mb-3">
        <div class="card document-card file-card" data-type="file" data-id="${file.id}">
          <div class="card-body text-center">
            <i class="${fileIcon} fa-3x mb-2"></i>
            <h6 class="card-title">${AppUtils.escapeHtml(file.name)}</h6>
            <small class="text-muted">${this.formatFileSize(file.size)}</small>
          </div>
        </div>
      </div>
    `;
  }

  // ファイルアイコン取得
  getFileIcon(extension) {
    const iconMap = {
      pdf: 'fas fa-file-pdf text-danger',
      doc: 'fas fa-file-word text-primary',
      docx: 'fas fa-file-word text-primary',
      xls: 'fas fa-file-excel text-success',
      xlsx: 'fas fa-file-excel text-success',
      ppt: 'fas fa-file-powerpoint text-warning',
      pptx: 'fas fa-file-powerpoint text-warning',
      jpg: 'fas fa-file-image text-info',
      jpeg: 'fas fa-file-image text-info',
      png: 'fas fa-file-image text-info',
      gif: 'fas fa-file-image text-info',
      txt: 'fas fa-file-alt text-secondary',
      zip: 'fas fa-file-archive text-dark',
      rar: 'fas fa-file-archive text-dark'
    };
    return iconMap[extension?.toLowerCase()] || 'fas fa-file text-muted';
  }

  // 統計情報更新
  updateStats(data) {
    const { folders = [], files = [] } = data;
    const totalSize = files.reduce((sum, file) => sum + (file.size || 0), 0);

    const folderCount = document.getElementById('folder-count');
    const fileCount = document.getElementById('file-count');
    const totalSizeEl = document.getElementById('total-size');
    const statsContainer = document.getElementById('document-stats');

    if (folderCount) folderCount.textContent = folders.length;
    if (fileCount) fileCount.textContent = files.length;
    if (totalSizeEl) totalSizeEl.textContent = this.formatFileSize(totalSize);
    if (statsContainer) statsContainer.style.display = 'block';
  }

  // 検索処理
  handleSearch() {
    const searchInput = document.getElementById('search-input');
    const query = searchInput?.value.trim() || '';

    // 実装: 検索クエリでドキュメントを再読み込み
    this.loadDocuments(this.getCurrentFolderId(), { search: query });
  }

  // フィルター変更処理
  handleFilterChange() {
    const filterSelect = document.getElementById('file-type-filter');
    const filterValue = filterSelect?.value || 'all';

    // 実装: フィルターでドキュメントを再読み込み
    this.loadDocuments(this.getCurrentFolderId(), { filter: filterValue });
  }

  // ソート変更処理
  handleSortChange() {
    const sortSelect = document.getElementById('sort-select');
    const sortValue = sortSelect?.value || 'name-asc';

    // 実装: ソートでドキュメントを再読み込み
    this.loadDocuments(this.getCurrentFolderId(), { sort: sortValue });
  }

  // 表示モード変更
  handleViewModeChange(mode) {
    const listView = document.getElementById('list-view-content');
    const gridView = document.getElementById('grid-view-content');

    if (mode === 'grid') {
      if (listView) listView.style.display = 'none';
      if (gridView) gridView.style.display = 'block';
    } else {
      if (listView) listView.style.display = 'block';
      if (gridView) gridView.style.display = 'none';
    }
  }

  // 全選択処理
  handleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
      checkbox.checked = checked;
    });
  }

  // エラー状態を隠す
  hideError() {
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) errorMessage.style.display = 'none';
  }

  // 空の状態を隠す
  hideEmptyState() {
    const emptyState = document.getElementById('empty-state');
    if (emptyState) emptyState.style.display = 'none';
  }

  // ドキュメントリストのクリック処理
  handleDocumentListClick(e) {
    const target = e.target.closest('[data-action], .folder-link, .file-link');
    if (!target) return;

    e.preventDefault();

    if (target.classList.contains('folder-link')) {
      // フォルダをクリック
      const folderId = target.dataset.folderId;
      this.navigateToFolder(folderId);
    } else if (target.classList.contains('file-link')) {
      // ファイルをクリック（ダウンロード）
      window.open(target.href, '_blank');
    } else if (target.dataset.action) {
      // アクションボタンをクリック
      this.handleAction(target.dataset.action, target.dataset.id, target.dataset.type);
    }
  }

  // フォルダナビゲーション
  async navigateToFolder(folderId) {
    try {
      this.currentFolderId = folderId;
      await this.loadDocuments(folderId);
      // パンくずナビゲーションはloadDocuments内で更新される
    } catch (error) {
      console.error('Failed to navigate to folder:', error);
      this.showError('フォルダの読み込みに失敗しました。');
    }
  }

  // パンくずナビゲーションのクリック処理
  handleBreadcrumbClick(e) {
    const link = e.target.closest('.breadcrumb-link');
    if (!link) return;

    e.preventDefault();
    const folderId = link.dataset.folderId;
    this.navigateToFolder(folderId);
  }

  // パンくずナビゲーション更新
  updateBreadcrumbs(breadcrumbs) {
    const breadcrumbNav = document.getElementById('breadcrumb-nav');
    if (!breadcrumbNav || !breadcrumbs) return;

    // パンくずリストを生成
    const breadcrumbItems = breadcrumbs.map(item => {
      return `
        <li class="breadcrumb-item ${item.active ? 'active' : ''}">
          ${item.active
          ? `<span>${AppUtils.escapeHtml(item.name)}</span>`
          : `<a href="#" class="breadcrumb-link" data-folder-id="${item.id || ''}">
                 ${item.id ? '' : '<i class="fas fa-home me-1"></i>'}${AppUtils.escapeHtml(item.name)}
               </a>`
        }
        </li>
      `;
    }).join('');

    breadcrumbNav.innerHTML = breadcrumbItems;
  }

  // アクション処理
  async handleAction(action, id, type) {
    switch (action) {
      case 'rename':
        await this.handleRename(id, type);
        break;
      case 'delete':
        await this.handleDelete(id, type);
        break;
      case 'download':
        this.handleDownload(id, type);
        break;
      case 'properties':
        await this.handleProperties(id, type);
        break;
    }
  }

  // 名前変更処理
  async handleRename(id, type) {
    const currentName = this.getCurrentItemName(id, type);
    const newName = prompt(`新しい名前を入力してください:`, currentName);

    if (!newName || newName === currentName) return;

    try {
      const url = type === 'folder'
        ? `/facilities/${this.facilityId}/documents/folders/${id}`
        : `/facilities/${this.facilityId}/documents/files/${id}/rename`;

      const response = await fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ name: newName })
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess('名前を変更しました。');
        await this.loadDocuments(this.currentFolderId);
      } else {
        this.showError(result.message || '名前の変更に失敗しました。');
      }
    } catch (error) {
      console.error('Rename failed:', error);
      this.showError('名前の変更に失敗しました。');
    }
  }

  // 削除処理
  async handleDelete(id, type) {
    const itemName = this.getCurrentItemName(id, type);
    const confirmed = await AppUtils.confirmDialog(
      `「${itemName}」を削除しますか？この操作は取り消せません。`,
      '削除確認'
    );

    if (!confirmed) return;

    try {
      const url = type === 'folder'
        ? `/facilities/${this.facilityId}/documents/folders/${id}`
        : `/facilities/${this.facilityId}/documents/files/${id}`;

      const response = await fetch(url, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess('削除しました。');
        await this.loadDocuments(this.currentFolderId);
      } else {
        this.showError(result.message || '削除に失敗しました。');
      }
    } catch (error) {
      console.error('Delete failed:', error);
      this.showError('削除に失敗しました。');
    }
  }

  // ダウンロード処理
  handleDownload(id, type) {
    if (type === 'file') {
      const downloadUrl = `/facilities/${this.facilityId}/documents/files/${id}/download`;
      window.open(downloadUrl, '_blank');
    }
  }

  // プロパティ表示処理
  async handleProperties(id, type) {
    try {
      const url = type === 'folder'
        ? `/facilities/${this.facilityId}/documents/folders/${id}/properties`
        : `/facilities/${this.facilityId}/documents/files/${id}/properties`;

      const response = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showPropertiesModal(result.data);
      } else {
        this.showError(result.message || 'プロパティの取得に失敗しました。');
      }
    } catch (error) {
      console.error('Properties fetch failed:', error);
      this.showError('プロパティの取得に失敗しました。');
    }
  }

  // プロパティモーダル表示
  showPropertiesModal(data) {
    // 簡単な実装 - 実際にはモーダルを表示
    const info = [
      `名前: ${data.name}`,
      `タイプ: ${data.type === 'folder' ? 'フォルダ' : 'ファイル'}`,
      `作成日: ${AppUtils.formatDate(data.created_at)}`,
      `更新日: ${AppUtils.formatDate(data.updated_at)}`,
      `作成者: ${data.creator}`
    ];

    if (data.type === 'file') {
      info.push(`サイズ: ${data.formatted_size}`);
      info.push(`形式: ${data.extension}`);
    }

    alert(info.join('\n'));
  }

  // 現在のアイテム名を取得
  getCurrentItemName(id, type) {
    const selector = type === 'folder'
      ? `[data-type="folder"][data-id="${id}"] .folder-link`
      : `[data-type="file"][data-id="${id}"] .file-link`;

    const element = document.querySelector(selector);
    return element ? element.textContent.trim() : 'Unknown';
  }

  // コンテキストメニュー処理
  handleContextMenu(e) {
    const item = e.target.closest('.document-item, .document-card');
    if (!item) return;

    e.preventDefault();
    this.showContextMenu(e.clientX, e.clientY, item);
  }

  // コンテキストメニュー表示
  showContextMenu(x, y, item) {
    const contextMenu = document.getElementById('context-menu');
    if (!contextMenu) return;

    const itemType = item.dataset.type;
    const itemId = item.dataset.id;

    // メニュー項目の表示/非表示を制御
    const downloadItem = contextMenu.querySelector('[data-action="download"]');
    if (downloadItem) {
      downloadItem.style.display = itemType === 'file' ? 'block' : 'none';
    }

    // メニューにデータを設定
    contextMenu.dataset.itemId = itemId;
    contextMenu.dataset.itemType = itemType;

    // 位置を調整
    contextMenu.style.left = `${x}px`;
    contextMenu.style.top = `${y}px`;
    contextMenu.style.display = 'block';

    // メニュー項目のクリックイベント
    const menuItems = contextMenu.querySelectorAll('.context-menu-item');
    menuItems.forEach(menuItem => {
      menuItem.onclick = (e) => {
        e.stopPropagation();
        const action = menuItem.dataset.action;
        this.handleAction(action, itemId, itemType);
        this.hideContextMenu();
      };
    });
  }

  // コンテキストメニューを隠す
  hideContextMenu() {
    const contextMenu = document.getElementById('context-menu');
    if (contextMenu) {
      contextMenu.style.display = 'none';
    }
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
    // ModalManagerを削除 - Bootstrapのデフォルト動作に任せる
  }

  async init() {
    if (this.initialized) return;

    try {
      console.log('Application initialization started');
      this.setupGlobalEventHandlers();
      this.setupUIEnhancements();
      await this.initializeModules();

      this.initialized = true;
      console.log('Application initialization completed');
    } catch (error) {
      console.error('Failed to initialize application:', error);
      AppUtils.showToast('アプリケーションの初期化に失敗しました。', 'error');
    }
  }

  async initializeModules() {
    const currentPath = window.location.pathname;
    console.log('Initializing modules for path:', currentPath);

    // Initialize facility module on facility pages
    if (currentPath.includes('/facilities/')) {
      const facilityIdMatch = currentPath.match(/\/facilities\/(\d+)/);
      console.log('Facility ID match:', facilityIdMatch);

      if (facilityIdMatch) {
        const facilityId = facilityIdMatch[1];
        console.log('Creating FacilityManager for facility:', facilityId);
        this.modules.facility = new FacilityManager(facilityId);

        // Initialize document management on facility detail pages
        if (currentPath.match(/\/facilities\/\d+$/)) {
          console.log('Initializing document management for facility:', facilityId);
          await this.initializeDocumentManagement(facilityId);
        }
      }
    }
  }

  async initializeDocumentManagement(facilityId) {
    try {
      console.log('Looking for document-management-container...');
      const documentContainer = document.getElementById('document-management-container');
      console.log('Document container found:', !!documentContainer);

      if (!documentContainer) {
        console.log('Document container not found, skipping initialization');
        return;
      }

      console.log('Creating DocumentManager with facilityId:', facilityId);
      const documentManager = new DocumentManager({
        facilityId: facilityId,
        baseUrl: `/facilities/${facilityId}/documents`,
        permissions: {
          canCreate: documentContainer.dataset.canCreate === 'true',
          canUpdate: documentContainer.dataset.canUpdate === 'true',
          canDelete: documentContainer.dataset.canDelete === 'true'
        }
      });

      console.log('DocumentManager created:', documentManager);
      this.modules.documentManager = documentManager;

      const isDocumentsPage = window.location.pathname.includes('/documents');
      const documentsTab = document.getElementById('documents-tab');
      const isDocumentsTabActive = documentsTab && documentsTab.classList.contains('active');

      if (isDocumentsPage || isDocumentsTabActive) {
        await documentManager.init();
      } else {
        if (documentsTab) {
          documentsTab.addEventListener('shown.bs.tab', async function () {
            if (!documentManager.initialized) {
              await documentManager.init();
            }
          });
        }
      }

      window.documentManager = documentManager;
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
      // 統合前のシンプルなクリーンアップ
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

// Bootstrap is loaded via CDN in the HTML, so no import needed
// window.bootstrap is available globally

// グローバルアクセス用（DOM要素との競合を避けるため別名を使用）
window.shiseCalApp = app;
window.appInstance = app;

// クラスをグローバルに公開（デバッグ用）
window.DocumentManager = DocumentManager;
window.AppUtils = AppUtils;
window.ApiClient = ApiClient;

document.addEventListener('DOMContentLoaded', async () => {
  console.log('DOM Content Loaded - Starting app initialization');
  try {
    await app.init();

    // ドキュメント管理の手動初期化チェック
    setTimeout(() => {
      const documentContainer = document.getElementById('document-management-container');
      if (documentContainer && !window.documentManager) {
        console.log('Document container found but DocumentManager not initialized, attempting manual initialization...');
        const facilityId = documentContainer.dataset.facilityId;
        if (facilityId) {
          app.initializeDocumentManagement(facilityId).catch(error => {
            console.error('Manual document management initialization failed:', error);
          });
        }
      }
    }, 1000);

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

// Emergency modal cleanup for debugging purposes only
window.forceCleanupAllModals = () => {
  console.log('Modal cleanup requested - Bootstrap will handle automatically');
};



/* ========================================
   Exports (ES6 Modules)
   ======================================== */
export {
  Application,
  AppUtils,
  ApiClient,
  FacilityManager,
  DocumentManager,
  app
};

