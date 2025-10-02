/**
 * 統合されたドキュメント管理JavaScript
 * - インラインJSを外部化
 * - 重複機能を削除
 * - シンプルで保守しやすい実装
 */

export class DocumentManagerClean {
  constructor(options = {}) {
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    this.permissions = options.permissions || {};

    this.initialized = false;
    this.activeModals = new Set();

    console.log('DocumentManagerClean initialized for facility:', this.facilityId);
  }

  /**
   * 初期化
   */
  async init() {
    if (this.initialized) return;

    try {
      console.log('Initializing clean document management...');

      // DOM要素の確認
      const container = document.getElementById('document-management-container');
      if (!container) {
        console.error('Document management container not found');
        return;
      }

      // イベントリスナーの設定
      this.bindEvents();

      // モーダル管理の設定
      this.setupModalManagement();

      // 初期データの読み込み
      await this.loadInitialData();

      this.initialized = true;
      console.log('Clean document management initialized successfully');

    } catch (error) {
      console.error('Failed to initialize clean document management:', error);
      this.showError('ドキュメント管理の初期化に失敗しました。');
    }
  }

  /**
   * イベントリスナーの設定
   */
  bindEvents() {
    // フォルダ作成フォーム
    const createFolderForm = document.getElementById('create-folder-form');
    if (createFolderForm) {
      createFolderForm.addEventListener('submit', (e) => this.handleCreateFolder(e));
    }

    // ファイルアップロードフォーム
    const uploadFileForm = document.getElementById('upload-file-form');
    if (uploadFileForm) {
      uploadFileForm.addEventListener('submit', (e) => this.handleUploadFile(e));
    }

    // 空の状態でのアップロードボタン
    const emptyUploadBtn = document.getElementById('empty-upload-btn');
    if (emptyUploadBtn) {
      emptyUploadBtn.addEventListener('click', () => this.showUploadModal());
    }

    // 緊急バックドロップ削除ボタン
    const emergencyBtn = document.getElementById('emergency-backdrop-remover');
    if (emergencyBtn) {
      emergencyBtn.addEventListener('click', () => this.cleanupAllModals());
    }
  }

  /**
   * モーダル管理の設定（簡素化版）
   */
  setupModalManagement() {
    // モーダルイベントの監視
    document.addEventListener('show.bs.modal', (e) => {
      this.activeModals.add(e.target.id);
      console.log('Modal opened:', e.target.id);
    });

    document.addEventListener('hidden.bs.modal', (e) => {
      this.activeModals.delete(e.target.id);
      console.log('Modal closed:', e.target.id);

      // モーダルが閉じた後にバックドロップをチェック
      setTimeout(() => this.cleanupOrphanedBackdrops(), 100);
    });

    // 定期的なバックドロップチェック（頻度を下げる）
    setInterval(() => this.cleanupOrphanedBackdrops(), 5000);
  }

  /**
   * 初期データの読み込み
   */
  async loadInitialData() {
    try {
      this.showLoading();

      // 実際のAPIコールは後で実装
      // 現在は空の状態を表示
      this.showEmptyState();

    } catch (error) {
      console.error('Failed to load initial data:', error);
      this.showError('データの読み込みに失敗しました。');
    } finally {
      this.hideLoading();
    }
  }

  /**
   * フォルダ作成の処理
   */
  async handleCreateFolder(e) {
    e.preventDefault();

    try {
      const formData = new FormData(e.target);

      console.log('Creating folder:', {
        name: formData.get('name'),
        parent_id: formData.get('parent_id')
      });

      const response = await this.fetchWithAuth('/documents/folders', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        this.hideModal('create-folder-modal');
        e.target.reset();
        this.showSuccess(data.message || 'フォルダを作成しました');

        // ページをリロード（簡単な解決策）
        setTimeout(() => window.location.reload(), 1000);
      } else {
        throw new Error(data.message || 'フォルダの作成に失敗しました');
      }

    } catch (error) {
      console.error('Failed to create folder:', error);
      this.showError('フォルダの作成に失敗しました: ' + error.message);
    }
  }

  /**
   * ファイルアップロードの処理
   */
  async handleUploadFile(e) {
    e.preventDefault();

    try {
      const formData = new FormData(e.target);

      console.log('Uploading files...');

      const response = await this.fetchWithAuth('/documents/files', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        this.hideModal('upload-file-modal');
        e.target.reset();
        this.showSuccess(data.message || 'ファイルをアップロードしました');

        // ページをリロード
        setTimeout(() => window.location.reload(), 1000);
      } else {
        throw new Error(data.message || 'ファイルのアップロードに失敗しました');
      }

    } catch (error) {
      console.error('Failed to upload file:', error);
      this.showError('ファイルのアップロードに失敗しました: ' + error.message);
    }
  }

  /**
   * 認証付きfetch
   */
  async fetchWithAuth(url, options = {}) {
    const defaultOptions = {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.csrfToken
      }
    };

    const mergedOptions = {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers
      }
    };

    const response = await fetch(url, mergedOptions);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response;
  }

  /**
   * アップロードモーダルの表示
   */
  showUploadModal() {
    const modal = document.getElementById('upload-file-modal');
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
      modalInstance.show();
    }
  }

  /**
   * モーダルを隠す
   */
  hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }

  /**
   * 孤立したバックドロップのクリーンアップ（簡素化版）
   */
  cleanupOrphanedBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const visibleModals = document.querySelectorAll('.modal.show');

    if (backdrops.length > 0 && visibleModals.length === 0) {
      console.log('Cleaning up orphaned backdrops');
      backdrops.forEach(backdrop => backdrop.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';

      // 緊急ボタンを隠す
      const emergencyBtn = document.getElementById('emergency-backdrop-remover');
      if (emergencyBtn) {
        emergencyBtn.style.display = 'none';
      }
    } else if (backdrops.length > 0 && visibleModals.length === 0) {
      // 緊急ボタンを表示
      const emergencyBtn = document.getElementById('emergency-backdrop-remover');
      if (emergencyBtn) {
        emergencyBtn.style.display = 'block';
      }
    }
  }

  /**
   * すべてのモーダルとバックドロップをクリーンアップ
   */
  cleanupAllModals() {
    console.log('Force cleanup all modals and backdrops');

    // すべてのモーダルを閉じる
    document.querySelectorAll('.modal').forEach(modal => {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
      modal.classList.remove('show');
      modal.style.display = 'none';
    });

    // すべてのバックドロップを削除
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
      backdrop.remove();
    });

    // body状態をリセット
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    // 緊急ボタンを隠す
    const emergencyBtn = document.getElementById('emergency-backdrop-remover');
    if (emergencyBtn) {
      emergencyBtn.style.display = 'none';
    }

    console.log('Force cleanup completed');
  }

  /**
   * 状態表示メソッド
   */
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

  showSuccess(message) {
    if (window.showToast) {
      window.showToast(message, 'success');
    } else {
      alert(message);
    }
  }
}

// グローバル関数（後方互換性のため）
window.forceCleanupAllModals = function () {
  const manager = window.documentManagerClean;
  if (manager) {
    manager.cleanupAllModals();
  }
};

window.immediateBackdropCleanup = function () {
  const manager = window.documentManagerClean;
  if (manager) {
    manager.cleanupOrphanedBackdrops();
  }
};

export default DocumentManagerClean;