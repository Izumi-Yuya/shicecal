/**
 * 統合アプリケーション用JavaScript
 * - 重複コードをすべて削除
 * - モジュラー設計
 * - パフォーマンスを最適化
 */

// Bootstrap is loaded via CDN in the HTML, so no import needed.
// window.bootstrap is available globally.

/* ========================================
   Core Utilities (統合版)
   ======================================== */
class AppUtils {
  // Guard for confirmDialog re-entrancy
  static _confirmPromise = null;
  static _confirmOpen = false;
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

  static async confirmDialog(message, title = '確認', options = {}) {
    return new Promise(async (resolve) => {
      // 既に確認ダイアログが開いている場合は、その結果を待機する
      if (AppUtils._confirmOpen && AppUtils._confirmPromise) {
        console.warn('confirmDialog is already open. Reusing existing promise.');
        const priorResult = await AppUtils._confirmPromise; // 既存のダイアログの結果を待ってから続行する
      }
      // このダイアログを現在開いているものとしてマーク
      AppUtils._confirmOpen = true;
      // 既存のモーダルをクリーンアップ（表示中なら先にhide→hidden後にdispose/remove）
      const existingModal = document.getElementById('confirm-modal');
      if (existingModal) {
        try {
          const inst = bootstrap.Modal.getInstance(existingModal) || bootstrap.Modal.getOrCreateInstance(existingModal);
          if (existingModal.classList.contains('show')) {
            await new Promise((done) => {
              existingModal.addEventListener('hidden.bs.modal', () => {
                try { inst.dispose(); } catch { }
                try { existingModal.remove(); } catch { }
                done();
              }, { once: true });
              inst.hide();
            });
          } else {
            try { inst.dispose(); } catch { }
            try { existingModal.remove(); } catch { }
          }
        } catch (e) {
          console.warn('Error cleaning up existing confirm modal:', e);
          try { existingModal.remove(); } catch { }
        }
      }

      // アイコンとボタンの設定
      let iconClass = 'fas fa-question-circle text-primary me-3 fa-2x';
      let buttonClass = 'btn btn-primary';
      let buttonText = 'OK';

      if (options.type === 'delete') {
        iconClass = 'fas fa-trash text-danger me-3 fa-2x';
        buttonClass = 'btn btn-danger';
        buttonText = '削除';
      } else if (options.type === 'warning') {
        iconClass = 'fas fa-exclamation-triangle text-warning me-3 fa-2x';
        buttonClass = 'btn btn-warning';
        buttonText = 'OK';
      }

      // モーダルHTML作成
      const modalHtml = `
        <div class="modal" id="confirm-modal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">${AppUtils.escapeHtml(title)}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="d-flex align-items-center">
                  <i class="${iconClass}" aria-hidden="true"></i>
                  <div>${AppUtils.escapeHtml(message).replace(/\n/g, '<br>')}</div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="confirm-cancel-btn">キャンセル</button>
                <button type="button" class="${buttonClass}" id="confirm-ok-btn">${buttonText}</button>
              </div>
            </div>
          </div>
        </div>
      `;

      // モーダルをDOMに追加
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      // モーダル要素を取得
      const modal = document.getElementById('confirm-modal');
      if (!modal) {
        console.error('Failed to create modal element');
        resolve(false);
        return;
      }

      // Bootstrap modal instance variable
      let bsModal = null;

      // ボタン要素を取得
      const okButton = modal.querySelector('#confirm-ok-btn');
      const cancelButton = modal.querySelector('#confirm-cancel-btn');
      const closeButton = modal.querySelector('.btn-close');

      if (!okButton || !cancelButton || !closeButton) {
        console.error('Failed to find modal buttons');
        modal.remove();
        resolve(false);
        return;
      }

      // 解決済みフラグ - より厳密な管理
      let isResolved = false;

      // 解決処理 - 安全なバージョン
      const resolveDialog = (result) => {
        if (isResolved) {
          console.log('ダイアログは既に解決済みです。重複呼び出しを無視します。結果:', result);
          return;
        }

        isResolved = true;
        console.log('Resolving dialog with result:', result);

        try {
          if (modal) {
            // hidden 後に実撤去（インスタンス破棄→DOM削除→Promise resolve）
            modal.addEventListener('hidden.bs.modal', () => {
              try {
                const inst = bootstrap.Modal.getInstance(modal);
                if (inst) inst.dispose();
              } catch (e) {
                console.warn('Error disposing modal:', e);
              } finally {
                if (modal.parentNode) modal.parentNode.removeChild(modal);
              }
              AppUtils._confirmOpen = false;
              AppUtils._confirmPromise = null;
              resolve(result);
            }, { once: true });

            // 既に表示中でも未表示でも安全に hide()
            const inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.hide();
          } else {
            AppUtils._confirmOpen = false;
            AppUtils._confirmPromise = null;
            resolve(result);
          }
        } catch (e) {
          console.warn('Error during modal close:', e);
          try { if (modal?.parentNode) modal.parentNode.removeChild(modal); } catch { }
          AppUtils._confirmOpen = false;
          AppUtils._confirmPromise = null;
          resolve(result);
        }
      };

      // イベントリスナーを直接追加（シンプル化）
      okButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('OK button clicked');
        resolveDialog(true);
      }, { once: true });

      cancelButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Cancel button clicked');
        resolveDialog(false);
      }, { once: true });

      closeButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Close button clicked');
        resolveDialog(false);
      }, { once: true });

      // モーダルを表示
      try {
        // body直下へ移動（タブ内やoverflow隠しの影響を回避）
        if (modal.parentElement !== document.body) {
          document.body.appendChild(modal);
        }

        bsModal = new bootstrap.Modal(modal, {
          backdrop: 'static',
          keyboard: true
        });

        modal.addEventListener('shown.bs.modal', () => {
          okButton.focus();
        }, { once: true });

        bsModal.show();
        console.log('Modal displayed');
      } catch (e) {
        console.error('Error showing modal:', e);
        AppUtils._confirmOpen = false;
        AppUtils._confirmPromise = null;
        resolveDialog(false);
      }
    });
  }
  // END AppUtils
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

    // Validate required options
    if (!options.facilityId) {
      console.error('DocumentManager: facilityId is required');
      throw new Error('DocumentManager: facilityId is required');
    }

    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    this.initialized = false;
    this.currentFolderId = '';

    // 重複防止フラグ
    this.isCreatingFolder = false;
    this.isUploadingFile = false;
    this.isDeletingFile = false;
    this.isDeletingFolder = false;
    this.isRenamingItem = false;
    this.documentEventsbound = false;
    this.uiEventsBound = false;

    if (!this.csrfToken) {
      console.warn('DocumentManager: CSRF token not found');
    }

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
    try {
      // 二重バインド防止
      if (this.uiEventsBound) {
        console.log('bindEvents skipped: already bound');
        return;
      }
      console.log('DocumentManager bindEvents called');

      // フォルダ作成ボタン
      const createBtn = document.getElementById('create-folder-btn');
      if (createBtn) {
        createBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.showCreateFolderModal();
        });
        console.log('Create folder button event bound');
      }

      // ファイルアップロードボタン
      const uploadBtn = document.getElementById('upload-file-btn');
      if (uploadBtn) {
        uploadBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.showUploadFileModal();
        });
        console.log('Upload file button event bound');
      }

      // 空の状態のアップロードボタン
      const emptyUploadBtn = document.getElementById('empty-upload-btn');
      if (emptyUploadBtn) {
        emptyUploadBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.showUploadFileModal();
        });
      }

      // フォーム送信（重複防止）
      const createForm = document.getElementById('create-folder-form');
      if (createForm) {
        if (createForm.dataset.bound === 'true') {
          console.log('create-folder-form is already bound, skip');
        } else {
          createForm.dataset.bound = 'true';
          // Capture phaseで先に止めて、他のsubmitリスナーやネイティブsubmitを抑止
          createForm.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            this.handleCreateFolder(e);
          }, { capture: true });
        }
      }

      const uploadForm = document.getElementById('upload-file-form');
      if (uploadForm) {
        if (uploadForm.dataset.bound === 'true') {
          console.log('upload-file-form is already bound, skip');
        } else {
          uploadForm.dataset.bound = 'true';
          uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            this.handleUploadFile(e);
          }, { capture: true });
        }
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

      // グリッド表示のイベント委譲
      const gridContainer = document.getElementById('document-grid-container');
      if (gridContainer) {
        gridContainer.addEventListener('click', (e) => {
          this.handleDocumentListClick(e);
        });

        gridContainer.addEventListener('contextmenu', (e) => {
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

      // キーボードショートカット
      document.addEventListener('keydown', (e) => {
        this.handleKeyboardShortcuts(e);
      });
      // バインド成功時にフラグを立てる
      this.uiEventsBound = true;
    } catch (error) {
      console.error('Error in bindEvents:', error);
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

    // 重複送信防止
    if (this.isCreatingFolder) {
      console.log('Folder creation already in progress, ignoring duplicate request');
      return;
    }

    const form = e.target;
    const formData = new FormData(form);
    const folderName = formData.get('name');

    // バリデーション
    if (!folderName || folderName.trim() === '') {
      this.showError('フォルダ名を入力してください。');
      return;
    }

    // 送信中フラグを設定
    this.isCreatingFolder = true;

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
      // 送信中フラグをリセット
      this.isCreatingFolder = false;
    }
  }

  async handleUploadFile(e) {
    e.preventDefault();

    // 重複送信防止
    if (this.isUploadingFile) {
      console.log('File upload already in progress, ignoring duplicate request');
      return;
    }

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

    // 送信中フラグを設定
    this.isUploadingFile = true;

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
      // 送信中フラグをリセット
      this.isUploadingFile = false;
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
            modal.addEventListener('hidden.bs.modal', () => {
              this.cleanupExtraBackdrops();
            }, { once: true });
          }
        }, 10);
      } else {
        const modalInstance = bootstrap.Modal.getInstance(modal) || bootstrap.Modal.getOrCreateInstance(modal);
        if (modalInstance) {
          modalInstance.hide();
          modal.addEventListener('hidden.bs.modal', () => {
            this.cleanupExtraBackdrops();
          }, { once: true });
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
    try {
      const loading = document.getElementById('loading-indicator');
      const documentList = document.getElementById('document-list');
      const errorMessage = document.getElementById('error-message');
      const emptyState = document.getElementById('empty-state');

      if (loading) loading.style.display = 'block';
      if (documentList) documentList.style.display = 'none';
      if (errorMessage) errorMessage.style.display = 'none';
      if (emptyState) emptyState.style.display = 'none';
    } catch (error) {
      console.error('Error in showLoading:', error);
    }
  }

  hideLoading() {
    try {
      const loading = document.getElementById('loading-indicator');
      if (loading) loading.style.display = 'none';
    } catch (error) {
      console.error('Error in hideLoading:', error);
    }
  }

  showError(message) {
    try {
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

      // Also show toast for better user feedback
      AppUtils.showToast(message, 'error');
    } catch (error) {
      console.error('Error in showError:', error);
      // Fallback to toast only
      AppUtils.showToast(message, 'error');
    }
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

  // サブフォルダ作成モーダル表示
  showCreateSubfolderModal(parentFolderId) {
    const modal = this.getModalByIds(['create-folder-modal', 'createFolderModal']);

    if (modal && window.bootstrap) {
      try {
        // 親フォルダIDを設定
        const parentIdInput = modal.querySelector('#parent-folder-id');
        if (parentIdInput) {
          parentIdInput.value = parentFolderId || '';
        }

        // 作成場所の表示を更新
        this.updateCreateLocationDisplay(parentFolderId);

        this.ensureModalInBody(modal);
        this.cleanupExtraBackdrops();
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, focus: true, keyboard: true });
        modalInstance.show();

        // フォルダ名入力にフォーカス
        modal.addEventListener('shown.bs.modal', () => {
          const folderNameInput = modal.querySelector('#folder-name');
          if (folderNameInput) {
            folderNameInput.focus();
            folderNameInput.select();
          }
        }, { once: true });
      } catch (error) {
        console.error('Failed to show create subfolder modal:', error);
      }
    }
  }

  // アイテム移動モーダル表示
  async showMoveItemModal(itemId, itemType) {
    const modal = document.getElementById('move-item-modal');

    if (modal && window.bootstrap) {
      try {
        // アイテム名を設定
        const itemNameSpan = modal.querySelector('#move-item-name');
        if (itemNameSpan) {
          const itemName = this.getCurrentItemName(itemId, itemType);
          itemNameSpan.textContent = itemName;
        }

        // フォルダツリーを読み込み
        await this.loadFolderTree();

        // 移動確認ボタンのイベント設定
        const confirmBtn = modal.querySelector('#confirm-move-btn');
        if (confirmBtn) {
          // 既存のイベントリスナーを削除
          confirmBtn.replaceWith(confirmBtn.cloneNode(true));
          const newConfirmBtn = modal.querySelector('#confirm-move-btn');

          newConfirmBtn.addEventListener('click', async () => {
            const selectedFolderId = this.getSelectedFolderId();
            const success = await this.moveItem(itemId, itemType, selectedFolderId);

            if (success) {
              const modalInstance = bootstrap.Modal.getInstance(modal);
              if (modalInstance) {
                modalInstance.hide();
              }
            }
          });
        }

        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modalInstance.show();
      } catch (error) {
        console.error('Failed to show move item modal:', error);
      }
    }
  }

  // 作成場所の表示を更新
  updateCreateLocationDisplay(parentFolderId) {
    const locationDisplay = document.getElementById('create-location-display');
    if (!locationDisplay) return;

    if (!parentFolderId) {
      locationDisplay.innerHTML = '<i class="fas fa-home me-1"></i>ルート';
      return;
    }

    // 現在のフォルダ名を取得（パンくずナビゲーションから）
    const breadcrumbs = document.querySelectorAll('#breadcrumb-nav .breadcrumb-item');
    let folderName = 'フォルダ';

    breadcrumbs.forEach(breadcrumb => {
      const link = breadcrumb.querySelector('.breadcrumb-link');
      if (link && link.dataset.folderId === parentFolderId) {
        folderName = link.textContent.replace(/^\s*[\w\s]*\s*/, '').trim();
      }
    });

    locationDisplay.innerHTML = `<i class="fas fa-folder me-1"></i>${folderName}`;
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
    return urlParams.get('folder_id') || this.currentFolderId || '';
  }

  // フォルダを開く
  async openFolder(folderId) {
    try {
      this.currentFolderId = folderId;

      // URLを更新（ブラウザ履歴に追加）
      const url = new URL(window.location);
      if (folderId) {
        url.searchParams.set('folder_id', folderId);
      } else {
        url.searchParams.delete('folder_id');
      }
      window.history.pushState({ folderId }, '', url);

      // フォルダ内容を読み込み
      await this.loadDocuments(folderId);

      // パンくずナビゲーションを更新
      this.updateBreadcrumbs();

    } catch (error) {
      console.error('Failed to open folder:', error);
      this.showError('フォルダを開けませんでした。');
    }
  }

  // サブフォルダ作成
  async createSubfolder(parentFolderId, folderName) {
    if (!folderName || folderName.trim() === '') {
      this.showError('フォルダ名を入力してください。');
      return;
    }

    try {
      const formData = new FormData();
      formData.append('_token', this.csrfToken);
      formData.append('parent_id', parentFolderId || '');
      formData.append('name', folderName.trim());

      const response = await fetch(`/facilities/${this.facilityId}/documents/folders`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (response.ok && result.success) {
        this.showSuccess('サブフォルダが作成されました。');
        await this.refreshDocumentList();
        return result.folder;
      } else {
        this.showError(result.message || 'サブフォルダの作成に失敗しました。');
        return null;
      }
    } catch (error) {
      console.error('Subfolder creation error:', error);
      this.showError('ネットワークエラーが発生しました。');
      return null;
    }
  }

  // フォルダツリーを取得
  async getFolderTree() {
    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/folder-tree`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.ok) {
        const result = await response.json();
        return result.data || [];
      } else {
        console.error('Failed to fetch folder tree');
        return [];
      }
    } catch (error) {
      console.error('Error fetching folder tree:', error);
      return [];
    }
  }

  // アイテム移動
  async moveItem(itemId, itemType, targetFolderId) {
    try {
      const endpoint = itemType === 'folder'
        ? `/facilities/${this.facilityId}/documents/folders/${itemId}/move`
        : `/facilities/${this.facilityId}/documents/files/${itemId}/move`;

      const response = await fetch(endpoint, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.csrfToken
        },
        body: JSON.stringify({
          target_folder_id: targetFolderId
        })
      });

      const result = await response.json();

      if (response.ok && result.success) {
        this.showSuccess('アイテムを移動しました。');
        await this.refreshDocumentList();
        return true;
      } else {
        this.showError(result.message || 'アイテムの移動に失敗しました。');
        return false;
      }
    } catch (error) {
      console.error('Move item error:', error);
      this.showError('ネットワークエラーが発生しました。');
      return false;
    }
  }

  // エラーメッセージ表示 (duplicate method removed - using main showError method above)

  // 成功メッセージ表示
  showSuccess(message) {
    AppUtils.showToast(message, 'success');
  }

  // 削除機能
  async handleDeleteFile(fileId) {
    console.log('handleDeleteFile called:', { fileId });

    if (!fileId) {
      AppUtils.showToast('ファイルIDが無効です', 'error');
      return;
    }

    // 重複削除防止
    if (this.isDeletingFile) {
      console.log('File deletion already in progress, ignoring duplicate request');
      return;
    }

    const confirmed = await AppUtils.confirmDialog(
      'このファイルを削除しますか？\n削除したファイルは復元できません。',
      '削除確認',
      { type: 'delete' }
    );
    if (!confirmed) {
      this.isDeletingFile = false;
      return;
    }

    // 削除中フラグを設定
    this.isDeletingFile = true;

    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/files/${fileId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (response.ok && result.success) {
        AppUtils.showToast('ファイルを削除しました', 'success');
        console.log('File deleted successfully, refreshing document list...');
        await this.refreshDocumentList();
        console.log('Document list refresh completed');
      } else {
        AppUtils.showToast(result.message || 'ファイルの削除に失敗しました', 'error');
      }
    } catch (error) {
      console.error('File deletion error:', error);
      AppUtils.showToast('ファイルの削除に失敗しました', 'error');
    } finally {
      // 削除中フラグをリセット
      this.isDeletingFile = false;
    }
  }

  async handleDeleteFolder(folderId) {
    console.log('handleDeleteFolder called:', { folderId });

    if (!folderId) {
      AppUtils.showToast('フォルダIDが無効です', 'error');
      return;
    }

    // 重複削除防止
    if (this.isDeletingFolder) {
      console.log('Folder deletion already in progress, ignoring duplicate request');
      return;
    }

    const confirmed = await AppUtils.confirmDialog(
      'このフォルダを削除しますか？\nフォルダ内にファイルがある場合は削除できません。',
      '削除確認',
      { type: 'delete' }
    );
    if (!confirmed) {
      this.isDeletingFolder = false;
      return;
    }

    // 削除中フラグを設定
    this.isDeletingFolder = true;

    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/folders/${folderId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (response.ok && result.success) {
        AppUtils.showToast('フォルダを削除しました', 'success');
        console.log('Folder deleted successfully, refreshing document list...');
        await this.refreshDocumentList();
        console.log('Document list refresh completed');
      } else {
        AppUtils.showToast(result.message || 'フォルダの削除に失敗しました', 'error');
      }
    } catch (error) {
      console.error('Folder deletion error:', error);
      AppUtils.showToast('フォルダの削除に失敗しました', 'error');
    } finally {
      // 削除中フラグをリセット
      this.isDeletingFolder = false;
    }
  }

  // 編集機能
  async handleRenameFile(fileId, currentName) {
    console.log('handleRenameFile called:', { fileId, currentName });

    // 二重実行ガード（高速連打・二重イベント対策）
    if (this.isRenamingItem) {
      console.log('Rename already in progress, skip');
      return;
    }

    if (!currentName || currentName === 'Unknown') {
      AppUtils.showToast('ファイル名を取得できませんでした', 'error');
      return;
    }

    const newName = await this.showRenameModal(currentName);
    if (!newName) return;

    if (newName === currentName) {
      AppUtils.showToast('名前が変更されていません', 'info');
      return;
    }

    this.isRenamingItem = true;
    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/files/${fileId}/rename`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ name: newName })
      });

      const result = await response.json();

      if (response.ok && result.success) {
        if (!this._lastRenameToastAt || Date.now() - this._lastRenameToastAt > 600) {
          AppUtils.showToast('ファイル名を変更しました', 'success');
          this._lastRenameToastAt = Date.now();
        }
        this.refreshDocumentList();
      } else {
        AppUtils.showToast(result.message || 'ファイル名の変更に失敗しました', 'error');
      }
    } catch (error) {
      console.error('File rename error:', error);
      AppUtils.showToast('ファイル名の変更に失敗しました', 'error');
    } finally {
      this.isRenamingItem = false;
    }
  }

  async handleRenameFolder(folderId, currentName) {
    console.log('handleRenameFolder called:', { folderId, currentName });

    // 二重実行ガード（高速連打・二重イベント対策）
    if (this.isRenamingItem) {
      console.log('Rename already in progress, skip');
      return;
    }

    if (!currentName || currentName === 'Unknown') {
      AppUtils.showToast('フォルダ名を取得できませんでした', 'error');
      return;
    }

    const newName = await this.showRenameModal(currentName);
    if (!newName) return;

    if (newName === currentName) {
      AppUtils.showToast('名前が変更されていません', 'info');
      return;
    }

    this.isRenamingItem = true;
    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/folders/${folderId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ name: newName })
      });

      const result = await response.json();

      if (response.ok && result.success) {
        if (!this._lastRenameToastAt || Date.now() - this._lastRenameToastAt > 600) {
          AppUtils.showToast('フォルダ名を変更しました', 'success');
          this._lastRenameToastAt = Date.now();
        }
        this.refreshDocumentList();
      } else {
        AppUtils.showToast(result.message || 'フォルダ名の変更に失敗しました', 'error');
      }
    } catch (error) {
      console.error('Folder rename error:', error);
      AppUtils.showToast('フォルダ名の変更に失敗しました', 'error');
    } finally {
      this.isRenamingItem = false;
    }
  }

  showRenameModal(currentName) {
    return new Promise((resolve) => {
      const modal = document.getElementById('rename-modal');
      const input = document.getElementById('new-name');
      const saveBtn = document.getElementById('save-rename-btn');

      if (!modal || !input || !saveBtn) {
        console.error('Rename modal elements not found:', { modal, input, saveBtn });
        AppUtils.showToast('名前変更ダイアログを開けませんでした', 'error');
        resolve(null);
        return;
      }

      input.value = currentName || '';
      input.setAttribute('autocomplete', 'off');
      // 保存ボタンが submit だと Enter と衝突するためボタン化
      try { saveBtn.setAttribute('type', 'button'); } catch { }
      // Ensure modal is in body and clean up any extra backdrops before showing
      this.ensureModalInBody(modal);
      this.cleanupExtraBackdrops();

      // Use getOrCreateInstance and allow normal backdrop/focus behavior
      const modalInstance = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, focus: true, keyboard: true });

      // Always clean up any extra backdrops when the modal fully closes
      modal.addEventListener('hidden.bs.modal', () => {
        this.cleanupExtraBackdrops();
      }, { once: true });

      let finished = false;
      const finalize = (val) => {
        if (finished) return;
        finished = true;
        resolve(val);
      };

      const onSave = () => {
        const newName = (input.value || '').trim();
        if (!newName || newName === currentName) {
          finalize(null);
        } else {
          finalize(newName);
        }
        modalInstance.hide();
      };

      const onKey = (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          onSave();
        } else if (e.key === 'Escape') {
          e.preventDefault();
          finalize(null);
          modalInstance.hide();
        }
      };

      // フォームのネイティブsubmitを抑止し、onSaveにルーティング
      const form = modal.querySelector('form');
      if (form) {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          e.stopPropagation();
          onSave();
        }, { once: true, capture: true });
      }

      const onHidden = () => {
        if (!finished) finalize(null);
        input.removeEventListener('keydown', onKey);
        saveBtn.removeEventListener('click', onSave);
        modal.removeEventListener('hidden.bs.modal', onHidden);
      };

      saveBtn.addEventListener('click', onSave);
      input.addEventListener('keydown', onKey);
      modal.addEventListener('hidden.bs.modal', onHidden);

      modal.addEventListener('shown.bs.modal', () => {
        input.focus();
        input.select();
      }, { once: true });

      modalInstance.show();
    });
  }

  // ドキュメントリストクリックハンドラー
  handleDocumentListClick(e) {
    // フォルダのダブルクリック処理
    if (e.detail === 2) { // ダブルクリック
      const folderRow = e.target.closest('[data-type="folder"]');
      if (folderRow) {
        const folderId = folderRow.dataset.id;
        this.openFolder(folderId);
        return;
      }
    }

    const target = e.target.closest('button');
    if (!target) return;

    e.preventDefault();
    e.stopPropagation();

    const action = target.dataset.action;
    const itemType = target.dataset.type;
    const itemId = target.dataset.id;

    if (!action || !itemType || !itemId) {
      console.warn('Missing required data attributes:', { action, itemType, itemId });
      return;
    }

    switch (action) {
      case 'open':
        if (itemType === 'folder') {
          this.openFolder(itemId);
        }
        break;
      case 'create-subfolder':
        if (itemType === 'folder') {
          this.showCreateSubfolderModal(itemId);
        }
        break;
      case 'rename':
      case 'edit':
        // data-name 属性から名前を取得、なければ getCurrentItemName を使用
        let currentName = target.dataset.name || this.getCurrentItemName(itemId, itemType);
        if (itemType === 'file') {
          this.handleRenameFile(itemId, currentName);
        } else if (itemType === 'folder') {
          this.handleRenameFolder(itemId, currentName);
        }
        break;
      case 'move':
        this.showMoveItemModal(itemId, itemType);
        break;
      case 'delete':
        if (itemType === 'file') {
          this.handleDeleteFile(itemId);
        } else if (itemType === 'folder') {
          this.handleDeleteFolder(itemId);
        }
        break;
    }
  }


  // プロパティ表示（将来の実装用）
  showProperties(itemType, itemId) {
    console.log(`Show properties for ${itemType} ${itemId}`);
    // TODO: プロパティモーダルの実装
  }

  // ドキュメントリストを更新
  async refreshDocumentList() {
    try {
      console.log('Refreshing document list...');
      // 現在のフォルダIDを取得して正しいloadDocumentsメソッドを呼び出し
      const currentFolderId = this.getCurrentFolderId();
      await this.loadDocuments(currentFolderId);
    } catch (error) {
      console.error('Failed to refresh document list:', error);
      AppUtils.showToast('ドキュメントリストの更新に失敗しました', 'error');
    }
  }


  // 古いrenderDocumentListメソッドは削除 - renderDocumentsメソッドを使用


  // 統計情報更新
  updateStats(stats) {
    if (!stats) return;

    const folderCount = document.getElementById('folder-count');
    const fileCount = document.getElementById('file-count');
    const totalSize = document.getElementById('total-size');
    const documentStats = document.getElementById('document-stats');

    if (folderCount) folderCount.textContent = stats.folder_count || 0;
    if (fileCount) fileCount.textContent = stats.file_count || 0;
    if (totalSize) totalSize.textContent = stats.formatted_size || '0 B';

    if (documentStats) {
      documentStats.style.display = (stats.folder_count > 0 || stats.file_count > 0) ? 'block' : 'none';
    }
  }

  // 日付フォーマット
  formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // HTMLエスケープ
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // フォルダツリーを読み込む
  async loadFolderTree() {
    try {
      const folderTree = await this.getFolderTree();
      this.renderFolderTree(folderTree);
    } catch (error) {
      console.error('Failed to load folder tree:', error);
      this.showError('フォルダツリーの読み込みに失敗しました。');
    }
  }

  // フォルダツリーを表示する
  renderFolderTree(folders) {
    const treeContainer = document.getElementById('folder-tree');
    if (!treeContainer) return;

    // ルートフォルダオプション
    const rootOption = document.createElement('div');
    rootOption.className = 'folder-tree-item';
    rootOption.innerHTML = `
      <div class="form-check">
        <input class="form-check-input" type="radio" name="target-folder" value="" id="folder-root">
        <label class="form-check-label" for="folder-root">
          <i class="fas fa-home me-1"></i>ルート
        </label>
      </div>
    `;

    treeContainer.innerHTML = '';
    treeContainer.appendChild(rootOption);

    // フォルダツリーを再帰的に構築
    this.renderFolderTreeRecursive(folders, treeContainer, 0);

    // デフォルトでルートを選択
    const rootRadio = treeContainer.querySelector('#folder-root');
    if (rootRadio) {
      rootRadio.checked = true;
    }
  }

  // フォルダツリーを再帰的に表示する
  renderFolderTreeRecursive(folders, container, level) {
    folders.forEach(folder => {
      const folderItem = document.createElement('div');
      folderItem.className = 'folder-tree-item';
      folderItem.style.marginLeft = `${level * 20}px`;

      folderItem.innerHTML = `
        <div class="form-check">
          <input class="form-check-input" type="radio" name="target-folder" value="${folder.id}" id="folder-${folder.id}">
          <label class="form-check-label" for="folder-${folder.id}">
            <i class="fas fa-folder me-1"></i>${this.escapeHtml(folder.name)}
          </label>
        </div>
      `;

      container.appendChild(folderItem);

      // 子フォルダがある場合は再帰的に表示
      if (folder.children && folder.children.length > 0) {
        this.renderFolderTreeRecursive(folder.children, container, level + 1);
      }
    });
  }

  // 選択されたフォルダIDを取得する
  getSelectedFolderId() {
    const selectedRadio = document.querySelector('input[name="target-folder"]:checked');
    return selectedRadio ? selectedRadio.value : '';
  }


  // パンくずナビゲーションを更新する
  updateBreadcrumbs() {
    // この機能は loadDocuments メソッド内で処理される
    // 必要に応じて個別の実装を追加
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
    console.log('renderDocuments called with data:', data);
    const { folders = [], files = [], sort_options = {} } = data;
    const hasContent = folders.length > 0 || files.length > 0;

    console.log('Content check:', {
      foldersCount: folders.length,
      filesCount: files.length,
      hasContent: hasContent,
      folders: folders,
      files: files,
      sort_options: sort_options
    });

    // 空の状態を隠す（常に実行）
    this.hideEmptyState();
    this.hideError();

    // ソート状態を更新
    this.updateSortState(sort_options);

    if (!hasContent) {
      console.log('No content found, showing empty state');
      this.showEmptyState();
      return;
    }

    const documentList = document.getElementById('document-list');
    const tableBody = document.getElementById('document-table-body');
    const gridContainer = document.getElementById('document-grid-container');

    console.log('DOM elements found:', {
      documentList: !!documentList,
      tableBody: !!tableBody,
      gridContainer: !!gridContainer
    });

    if (documentList) {
      documentList.style.display = 'block';
      console.log('Document list set to display: block');
    } else {
      console.error('Document list element not found!');
    }

    // テーブル表示
    if (tableBody) {
      const folderRows = folders.map(folder => this.renderFolderRow(folder));
      const fileRows = files.map(file => this.renderFileRow(file));
      const allRows = [...folderRows, ...fileRows];

      console.log('Rendering rows:', {
        folderRowsCount: folderRows.length,
        fileRowsCount: fileRows.length,
        totalRows: allRows.length,
        sampleFileRow: fileRows[0] ? fileRows[0].substring(0, 100) + '...' : 'none'
      });

      tableBody.innerHTML = allRows.join('');
    }

    // グリッド表示
    if (gridContainer) {
      gridContainer.innerHTML = [
        ...folders.map(folder => this.renderFolderCard(folder)),
        ...files.map(file => this.renderFileCard(file))
      ].join('');
    }
  }

  // ドキュメント固有のイベントリスナーをバインド
  bindDocumentEvents() {
    // ドキュメントリスナーの二重バインドを避けるため、bindEventsでの委譲に一本化
    return;
  }

  // フォルダ行の表示
  renderFolderRow(folder) {
    const folderName = AppUtils.escapeHtml(folder.name);
    return `
      <tr class="document-item folder-item" data-type="folder" data-id="${folder.id}" data-name="${folderName}">
        <td>
          <input type="checkbox" class="form-check-input item-checkbox" value="${folder.id}">
        </td>
        <td>
          <a href="#" class="folder-link text-decoration-none" data-folder-id="${folder.id}">
            <i class="fas fa-folder text-warning me-2"></i>
            ${folderName}
          </a>
        </td>
        <td><span class="text-muted">—</span></td>
        <td><small class="text-muted">${AppUtils.formatDate(folder.updated_at)}</small></td>
        <td><small class="text-muted">${AppUtils.escapeHtml(folder.created_by || '')}</small></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary btn-sm" 
                    data-action="rename" 
                    data-id="${folder.id}" 
                    data-type="folder" 
                    data-name="${folderName}"
                    title="名前変更">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" 
                    data-action="delete" 
                    data-id="${folder.id}" 
                    data-type="folder"
                    title="削除">
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
    const fileName = AppUtils.escapeHtml(file.name);
    return `
      <tr class="document-item file-item" data-type="file" data-id="${file.id}" data-name="${fileName}">
        <td>
          <input type="checkbox" class="form-check-input item-checkbox" value="${file.id}">
        </td>
        <td>
          <a href="${file.download_url}" class="file-link text-decoration-none" target="_blank">
            <i class="${fileIcon} me-2"></i>
            ${fileName}
          </a>
        </td>
        <td><small class="text-muted">${this.formatFileSize(file.size)}</small></td>
        <td><small class="text-muted">${AppUtils.formatDate(file.updated_at)}</small></td>
        <td><small class="text-muted">${AppUtils.escapeHtml(file.uploaded_by || '')}</small></td>
        <td>
          <div class="btn-group btn-group-sm">
            <a href="${file.download_url}" 
               class="btn btn-outline-primary btn-sm" 
               target="_blank"
               title="ダウンロード">
              <i class="fas fa-download"></i>
            </a>
            <button class="btn btn-outline-secondary btn-sm" 
                    data-action="rename" 
                    data-id="${file.id}" 
                    data-type="file" 
                    data-name="${fileName}"
                    title="名前変更">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" 
                    data-action="delete" 
                    data-id="${file.id}" 
                    data-type="file"
                    title="削除">
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
          <div class="card-body text-center position-relative p-0">
            <div class="folder-link" data-folder-id="${folder.id}" style="cursor: pointer; padding: 1rem;">
              <i class="fas fa-folder fa-3x text-warning mb-2"></i>
              <h6 class="card-title mb-1">${AppUtils.escapeHtml(folder.name)}</h6>
              <small class="text-muted">${AppUtils.formatDate(folder.updated_at)}</small>
            </div>
            <div class="card-actions position-absolute top-0 end-0 p-2">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" data-action="rename" data-id="${folder.id}" data-type="folder" data-name="${AppUtils.escapeHtml(folder.name)}">
                    <i class="fas fa-edit me-2"></i>名前変更
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${folder.id}" data-type="folder" data-name="${AppUtils.escapeHtml(folder.name)}">
                    <i class="fas fa-trash me-2"></i>削除
                  </a></li>
                </ul>
              </div>
            </div>
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
          <div class="card-body text-center position-relative p-0">
            <a href="${file.download_url}" class="file-link text-decoration-none d-block" target="_blank" style="color: inherit; padding: 1rem;">
              <i class="${fileIcon} fa-3x mb-2"></i>
              <h6 class="card-title mb-1">${AppUtils.escapeHtml(file.name)}</h6>
              <small class="text-muted">${this.formatFileSize(file.size)}</small>
            </a>
            <div class="card-actions position-absolute top-0 end-0 p-2">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="${file.download_url}" target="_blank">
                    <i class="fas fa-download me-2"></i>ダウンロード
                  </a></li>
                  <li><a class="dropdown-item" href="#" data-action="rename" data-id="${file.id}" data-type="file" data-name="${AppUtils.escapeHtml(file.name)}">
                    <i class="fas fa-edit me-2"></i>名前変更
                  </a></li>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-id="${file.id}" data-type="file" data-name="${AppUtils.escapeHtml(file.name)}">
                    <i class="fas fa-trash me-2"></i>削除
                  </a></li>
                </ul>
              </div>
            </div>
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

  // ソート状態を更新
  updateSortState(sortOptions) {
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect && sortOptions) {
      const sortBy = sortOptions.sort_by || 'name';
      const sortDirection = sortOptions.sort_direction || 'asc';
      const sortValue = `${sortBy}-${sortDirection}`;

      // セレクトボックスの値を更新
      if (sortSelect.value !== sortValue) {
        sortSelect.value = sortValue;
      }
    }
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
    // ドロップダウンボタンのクリックは処理しない（メニューを開くため）
    if (e.target.closest('[data-bs-toggle="dropdown"]')) {
      return;
    }

    const target = e.target.closest('[data-action], .folder-link, .file-link');
    if (!target) return;

    if (target.classList.contains('folder-link')) {
      // フォルダをクリック
      e.preventDefault();
      const folderId = target.dataset.folderId;
      this.navigateToFolder(folderId);
    } else if (target.classList.contains('file-link')) {
      // ファイルをクリック（ダウンロード）- デフォルトの動作を許可
      return;
    } else if (target.dataset.action) {
      // アクションボタンをクリック（ドロップダウンメニュー内も含む）
      e.preventDefault();
      console.log('Action clicked:', {
        action: target.dataset.action,
        id: target.dataset.id,
        type: target.dataset.type,
        name: target.dataset.name
      });
      this.handleAction(target.dataset.action, target.dataset.id, target.dataset.type, target.dataset.name);
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

  // パンくずナビゲーション更新（APIの2系統フォーマットに対応）
  updateBreadcrumbs(breadcrumbs) {
    const breadcrumbNav = document.getElementById('breadcrumb-nav');
    if (!breadcrumbNav || !Array.isArray(breadcrumbs)) return;

    const itemsHtml = breadcrumbs.map((item, index) => {
      const isActive = Boolean(item.active ?? item.is_current ?? item.isActive);
      const id = item.id || '';
      const name = AppUtils.escapeHtml(item.name || '');
      const isHome = index === 0 && !id; // 先頭かつルート扱い

      if (isActive) {
        return `<li class="breadcrumb-item active" aria-current="page"><span>${name}</span></li>`;
      } else {
        return `<li class="breadcrumb-item"><a href="#" class="breadcrumb-link" data-folder-id="${id}">${isHome ? '<i class=\"fas fa-home me-1\"></i>' : ''}${name}</a></li>`;
      }
    }).join('');

    breadcrumbNav.innerHTML = itemsHtml;
  }

  // アクション処理
  async handleAction(action, id, type, name = null) {
    // ドロップダウンメニューを閉じる
    const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
    openDropdowns.forEach(dropdown => {
      const toggle = dropdown.previousElementSibling;
      if (toggle && toggle.hasAttribute('data-bs-toggle')) {
        const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
        if (bsDropdown) {
          bsDropdown.hide();
        }
      }
    });

    switch (action) {
      case 'rename':
        const currentName = name || this.getCurrentItemName(id, type);
        if (type === 'file') {
          await this.handleRenameFile(id, currentName);
        } else if (type === 'folder') {
          await this.handleRenameFolder(id, currentName);
        }
        break;
      case 'delete':
        if (type === 'file') {
          await this.handleDeleteFile(id);
        } else if (type === 'folder') {
          await this.handleDeleteFolder(id);
        }
        break;
      case 'download':
        this.handleDownload(id, type);
        break;
      case 'properties':
        await this.handleProperties(id, type);
        break;
    }
  }

  // 名前変更処理 (generic) - removed
  // 削除処理 (generic) - removed

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

  // キーボードショートカット処理
  handleKeyboardShortcuts(e) {
    // モーダルが開いている場合は処理しない
    if (document.querySelector('.modal.show')) return;

    // 入力フィールドにフォーカスがある場合は処理しない
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    // 選択されたアイテムを取得
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');

    switch (e.key) {
      case 'Delete':
        e.preventDefault();
        if (selectedItems.length === 1) {
          const item = selectedItems[0].closest('[data-type][data-id]');
          if (item) {
            this.handleAction('delete', item.dataset.id, item.dataset.type);
          }
        }
        break;

      case 'F2':
        e.preventDefault();
        if (selectedItems.length === 1) {
          const item = selectedItems[0].closest('[data-type][data-id]');
          if (item) {
            this.handleAction('rename', item.dataset.id, item.dataset.type);
          }
        }
        break;

      case 'Enter':
        if (selectedItems.length === 1) {
          const item = selectedItems[0].closest('[data-type][data-id]');
          if (item && item.dataset.type === 'folder') {
            e.preventDefault();
            this.navigateToFolder(item.dataset.id);
          }
        }
        break;

      case 'Escape':
        // 選択をクリア
        selectedItems.forEach(checkbox => {
          checkbox.checked = false;
        });
        // コンテキストメニューを隠す
        this.hideContextMenu();
        break;
    }
  }

  // 現在のアイテム名を取得
  getCurrentItemName(id, type) {
    console.log('getCurrentItemName called:', { id, type });

    // リスト表示での検索
    let selector = type === 'folder'
      ? `[data-type="folder"][data-id="${id}"] .folder-link`
      : `[data-type="file"][data-id="${id}"] .file-link`;

    let element = document.querySelector(selector);
    console.log('First selector attempt:', selector, element);

    // グリッド表示での検索（リスト表示で見つからない場合）
    if (!element) {
      selector = type === 'folder'
        ? `[data-type="folder"][data-id="${id}"] .card-title`
        : `[data-type="file"][data-id="${id}"] .card-title`;
      element = document.querySelector(selector);
      console.log('Grid selector attempt:', selector, element);
    }

    // テーブル行での検索（上記で見つからない場合）
    if (!element) {
      const row = document.querySelector(`tr[data-type="${type}"][data-id="${id}"]`);
      console.log('Row found:', row);
      if (row) {
        const nameCell = row.querySelector('td:nth-child(2)');
        console.log('Name cell found:', nameCell);
        if (nameCell) {
          const link = nameCell.querySelector('a');
          element = link || nameCell;
          console.log('Final element:', element);
        }
      }
    }

    if (element) {
      // テキストコンテンツを取得し、余分な空白を除去
      let name = element.textContent.trim();
      console.log('Raw name:', name);

      // アイコンのテキストを除去（より確実な方法）
      name = name.replace(/^\s*[\u{1F4C1}\u{1F4C4}\u{1F4DD}]\s*/u, '');

      // FontAwesome アイコンクラスのテキストも除去
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = element.innerHTML;
      const icons = tempDiv.querySelectorAll('i');
      icons.forEach(icon => icon.remove());
      name = tempDiv.textContent.trim();

      console.log('Cleaned name:', name);
      return name || 'Unknown';
    }

    console.warn('Could not find element for:', { id, type });
    return 'Unknown';
  }

  // コンテキストメニュー処理
  handleContextMenu(e) {
    const item = e.target.closest('.document-item, .document-card, tr[data-type]');
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
    const menuItems = contextMenu.querySelectorAll('.context-menu-item');
    menuItems.forEach(menuItem => {
      const action = menuItem.dataset.action;
      const folderOnly = menuItem.dataset.folderOnly === 'true';
      const fileOnly = menuItem.dataset.fileOnly === 'true';

      let shouldShow = true;

      if (folderOnly && itemType !== 'folder') {
        shouldShow = false;
      } else if (fileOnly && itemType !== 'file') {
        shouldShow = false;
      }

      menuItem.style.display = shouldShow ? 'block' : 'none';
    });

    // 区切り線の表示制御
    const dividers = contextMenu.querySelectorAll('.context-menu-divider');
    dividers.forEach(divider => {
      const folderOnly = divider.dataset.folderOnly === 'true';
      divider.style.display = (folderOnly && itemType !== 'folder') ? 'none' : 'block';
    });

    // メニューにデータを設定
    contextMenu.dataset.itemId = itemId;
    contextMenu.dataset.itemType = itemType;

    // 位置を調整（画面外に出ないように）
    const menuWidth = 200;
    const menuHeight = 250;
    const adjustedX = Math.min(x, window.innerWidth - menuWidth);
    const adjustedY = Math.min(y, window.innerHeight - menuHeight);

    contextMenu.style.left = `${adjustedX}px`;
    contextMenu.style.top = `${adjustedY}px`;
    contextMenu.style.display = 'block';

    // メニュー項目のクリックイベント
    const visibleMenuItems = contextMenu.querySelectorAll('.context-menu-item[style*="block"], .context-menu-item:not([style*="none"])');
    visibleMenuItems.forEach(menuItem => {
      menuItem.onclick = (e) => {
        e.stopPropagation();
        const action = menuItem.dataset.action;
        this.handleContextMenuAction(action, itemId, itemType);
        this.hideContextMenu();
      };
    });
  }

  // コンテキストメニューアクション処理
  handleContextMenuAction(action, itemId, itemType) {
    switch (action) {
      case 'open':
        if (itemType === 'folder') {
          this.openFolder(itemId);
        }
        break;
      case 'create-subfolder':
        if (itemType === 'folder') {
          this.showCreateSubfolderModal(itemId);
        }
        break;
      case 'rename':
        const currentName = this.getCurrentItemName(itemId, itemType);
        if (itemType === 'file') {
          this.handleRenameFile(itemId, currentName);
        } else if (itemType === 'folder') {
          this.handleRenameFolder(itemId, currentName);
        }
        break;
      case 'download':
        if (itemType === 'file') {
          this.downloadFile(itemId);
        }
        break;
      case 'move':
        this.showMoveItemModal(itemId, itemType);
        break;
      case 'properties':
        this.showProperties(itemType, itemId);
        break;
      case 'delete':
        if (itemType === 'file') {
          this.handleDeleteFile(itemId);
        } else if (itemType === 'folder') {
          this.handleDeleteFolder(itemId);
        }
        break;
    }
  }

  // ファイルダウンロード
  downloadFile(fileId) {
    const downloadUrl = `/facilities/${this.facilityId}/documents/files/${fileId}/download`;
    window.open(downloadUrl, '_blank');
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

