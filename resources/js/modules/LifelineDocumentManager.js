/**
 * ライフライン設備ドキュメント管理モジュール
 * 
 * ライフライン設備の各カテゴリ（電気、ガス、水道等）に対応した
 * ドキュメント管理機能を提供します。
 */

// ApiClient import removed - using direct fetch calls

class LifelineDocumentManager {
  constructor(facilityId = null, category = null, uniqueId = null, options = {}) {
    // Validate required parameters
    if (facilityId && !category) {
      throw new Error('LifelineDocumentManager: category is required when facilityId is provided');
    }

    // uniqueIdがない場合はcategoryを使用
    this.uniqueId = uniqueId || category;

    // 重複インスタンス防止 - より強力なチェック
    const existingKey = `lifelineDocManager_${this.uniqueId}`;
    if (this.uniqueId && window[existingKey]) {
      console.warn(`[LifelineDoc] Manager for ${this.uniqueId} already exists, returning existing instance`);
      return window[existingKey];
    }

    this.facilityId = facilityId;
    this.category = category;
    this.initialized = false;
    this.isUploading = false;
    this.isCreatingFolder = false;

    // カテゴリエイリアスマップ（404回避）
    this.categoryAliasMap = {
      electric: 'electrical',
      // 必要に応じて他カテゴリも追加
    };

    // options
    this.options = {
      mountInModal: false,
      modalIdPrefix: 'lifeline-docs-modal',
      ...options,
    };
    this.rootContainer = null;
    // State management
    this.state = {
      currentFolder: null,
      viewMode: 'list',
      selectedItems: new Set(),
      loading: false,
      error: null,
      searchQuery: '',
      sortBy: 'name',
      sortDirection: 'asc'
    };

    // デフォルト設定
    this.defaultOptions = {
      canEdit: false,
      allowedFileTypes: 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
      maxFileSize: '10MB',
      perPage: 50,
      searchDelay: 500
    };

    // DOM element cache and event management
    this.elements = {};
    this.eventListeners = [];
    this.abortController = new AbortController();

    // Using direct fetch calls instead of ApiClient

    // Debounced methods
    this.debouncedSearch = this.debounce(this.performSearch.bind(this), this.defaultOptions.searchDelay);

    // グローバルに登録（重複防止のため）
    if (this.uniqueId) {
      window[`lifelineDocManager_${this.uniqueId}`] = this;
      console.log(`[LifelineDoc] Registered instance as window.lifelineDocManager_${this.uniqueId}`);
    }

    // 初期化
    if (facilityId && category) {
      this.init();
    }
  }


  /**
   * 初期化
   */
  init() {
    console.log(`LifelineDocumentManager initializing for facility ${this.facilityId}, category ${this.category}`);

    // モーダルモードの場合でも、ensureModalContainerは呼ばない
    // （Bladeテンプレートで既にモーダルが生成されているため）

    // コンテナ解決
    const container = this.getRootContainer();
    if (!container) {
      console.warn(`[LifelineDoc] Container not found for category: ${this.category}, initialization deferred`);
      // コンテナが見つからない場合は、エラーを表示せずに初期化を延期
      this.initialized = false;
      return;
    }

    // イベントリスナーを設定
    this.setupEventListeners();

    // 初期データを読み込み
    this.loadDocuments().catch(error => {
      console.error(`[LifelineDoc] Failed to load initial documents for ${this.category}:`, error);
      // エラーメッセージを表示
      const errorElement = container.querySelector('#error-message');
      const errorText = container.querySelector('#error-text');
      if (errorElement && errorText) {
        errorText.textContent = error.message || 'ドキュメントの読み込みに失敗しました。';
        errorElement.classList.remove('d-none');
      }
    });

    this.initialized = true;
    console.log(`LifelineDocumentManager initialized for category: ${this.category}`);
  }

  /**
   * ドキュメントリストを再読み込み
   */
  async refresh() {
    console.log(`[LifelineDoc] Refreshing documents for ${this.category}`);

    if (!this.initialized) {
      console.log(`[LifelineDoc] Not initialized yet, calling init() first`);
      this.init();
      return;
    }

    try {
      await this.loadDocuments({ forceReload: true });
      console.log(`[LifelineDoc] Successfully refreshed documents for ${this.category}`);
    } catch (error) {
      console.error(`[LifelineDoc] Failed to refresh documents for ${this.category}:`, error);
      throw error;
    }
  }

  /**
   * イベントリスナーを設定
   */
  setupEventListeners() {
    // 既存のイベントリスナーをクリア
    this.removeEventListeners();

    // DOM要素の存在確認を遅延実行
    setTimeout(() => {
      // ツールバーボタンのイベントリスナー
      const createFolderBtn = document.getElementById(`create-folder-btn-${this.uniqueId}`);
      if (createFolderBtn) {
        const handler = () => this.openCreateFolderModal();
        createFolderBtn.addEventListener('click', handler);
        this.eventListeners.push({ element: createFolderBtn, event: 'click', handler });
        console.log(`Create folder button listener added for ${this.category}`);
      } else {
        console.warn(`Create folder button not found for ${this.category}`);
      }

      const uploadFileBtn = document.getElementById(`upload-file-btn-${this.uniqueId}`);
      if (uploadFileBtn) {
        const handler = () => this.openUploadFileModal();
        uploadFileBtn.addEventListener('click', handler);
        this.eventListeners.push({ element: uploadFileBtn, event: 'click', handler });
        console.log(`Upload file button listener added for ${this.category}`);
      } else {
        console.warn(`Upload file button not found for ${this.category}`);
      }

      // 空の状態のアップロードボタン
      const emptyUploadBtn = document.getElementById(`empty-upload-btn-${this.uniqueId}`);
      if (emptyUploadBtn) {
        const handler = () => this.openUploadFileModal();
        emptyUploadBtn.addEventListener('click', handler);
        this.eventListeners.push({ element: emptyUploadBtn, event: 'click', handler });
        console.log(`Empty upload button listener added for ${this.category}`);
      }

      // フォルダ作成フォーム
      const createFolderForm = document.getElementById(`create-folder-form-${this.uniqueId}`);
      if (createFolderForm) {
        const handler = (e) => this.handleCreateFolder(e);
        createFolderForm.addEventListener('submit', handler, { capture: true });
        this.eventListeners.push({ element: createFolderForm, event: 'submit', handler });
        console.log(`Create folder form listener added for ${this.category}`);
      } else {
        console.warn(`Create folder form not found for ${this.category}`);
      }

      // ファイルアップロードフォーム
      const uploadFileForm = document.getElementById(`upload-file-form-${this.uniqueId}`);
      if (uploadFileForm) {
        // 既存のリスナーを削除
        const existingListeners = this.eventListeners.filter(l =>
          l.element === uploadFileForm && l.event === 'submit'
        );
        existingListeners.forEach(({ element, event, handler }) => {
          element.removeEventListener(event, handler);
        });

        const handler = (e) => this.handleUploadFile(e);
        uploadFileForm.addEventListener('submit', handler);
        this.eventListeners.push({ element: uploadFileForm, event: 'submit', handler });
        console.log(`Upload file form listener added for ${this.category}`);
      } else {
        console.warn(`Upload file form not found for ${this.category}`);
      }

      // 検索入力
      const searchInput = document.getElementById(`search-input-${this.uniqueId}`);
      if (searchInput) {
        const handler = (e) => this.handleSearchInput(e);
        searchInput.addEventListener('input', handler);
        this.eventListeners.push({ element: searchInput, event: 'input', handler });
      }

      // コンテキストメニューイベント
      this.setupContextMenuEvents();

      // モーダルイベントリスナーを追加
      this.setupModalEventListeners();

      console.log(`Event listeners set up for LifelineDocumentManager category: ${this.category}`);
    }, 100);
  }

  /**
   * イベントリスナーを削除
   */
  removeEventListeners() {
    this.eventListeners.forEach(({ element, event, handler }) => {
      if (element && element.removeEventListener) {
        element.removeEventListener(event, handler);
      }
    });
    this.eventListeners = [];
  }

  /**
   * z-indexを調整してモーダルを最前面に
   */
  adjustModalZIndex(modalEl) {
    try {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      const topBackdrop = backdrops[backdrops.length - 1];
      const backdropZ = parseInt(getComputedStyle(topBackdrop)?.zIndex || '1050', 10);

      modalEl.style.zIndex = String(backdropZ + 10);
      const dialog = modalEl.querySelector('.modal-dialog');
      if (dialog) {
        dialog.style.zIndex = String(backdropZ + 11);
      }

      console.log(`[LifelineDoc] Adjusted z-index for modal to ${modalEl.style.zIndex}`);
    } catch (e) {
      console.warn('[LifelineDoc] z-index adjust failed:', e);
    }
  }

  /**
   * 残留バックドロップをクリーンアップ
   */
  cleanupBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');

    // 複数のバックドロップがある場合、最新以外を削除
    if (backdrops.length > 1) {
      for (let i = 0; i < backdrops.length - 1; i++) {
        backdrops[i].remove();
      }
      console.log(`[LifelineDoc] Cleaned up ${backdrops.length - 1} stray backdrops`);
    }

    // 孤立したバックドロップの処理
    const anyModal = document.querySelector('.modal.show');
    if (!anyModal && backdrops.length > 0) {
      backdrops.forEach(el => el.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
      console.log('[LifelineDoc] Removed lonely backdrops and restored body state');
    }
  }

  /**
   * フォルダ作成モーダルを開く
   */
  openCreateFolderModal() {
    console.log(`[LifelineDoc] ${this.category}: Attempting to open create folder modal`);
    console.log(`[LifelineDoc] ${this.category}: Current folder before opening modal: ${this.state.currentFolder}`);
    console.log(`[LifelineDoc] ${this.category}: Instance ID:`, this);

    // モーダルコンテナが存在するか確認
    const modalId = `create-folder-modal-${this.category}`;
    let modal = document.getElementById(modalId);

    console.log(`[LifelineDoc] Modal element:`, modal);
    console.log(`[LifelineDoc] Modal ID:`, modalId);

    if (!modal) {
      console.error(`[LifelineDoc] Create folder modal not found: ${modalId}`);
      console.log(`[LifelineDoc] Attempting to ensure modal container...`);

      // モーダルコンテナを再生成
      this.ensureModalContainer();
      modal = document.getElementById(modalId);

      if (!modal) {
        console.error(`[LifelineDoc] Still cannot find modal after ensureModalContainer`);
        return;
      }
    }

    try {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
      console.log(`[LifelineDoc] Successfully opened create folder modal for ${this.category}`);
    } catch (error) {
      console.error(`[LifelineDoc] Error opening modal:`, error);
    }
  }

  /**
   * ファイルアップロードモーダルを開く
   */
  openUploadFileModal() {
    console.log(`[LifelineDoc] Attempting to open upload file modal for ${this.category}`);

    const modalId = `upload-file-modal-${this.category}`;
    let modal = document.getElementById(modalId);

    console.log(`[LifelineDoc] Modal element:`, modal);
    console.log(`[LifelineDoc] Modal ID:`, modalId);

    if (!modal) {
      console.error(`[LifelineDoc] Upload file modal not found: ${modalId}`);
      console.log(`[LifelineDoc] Attempting to ensure modal container...`);

      // モーダルコンテナを再生成
      this.ensureModalContainer();
      modal = document.getElementById(modalId);

      if (!modal) {
        console.error(`[LifelineDoc] Still cannot find modal after ensureModalContainer`);
        return;
      }
    }

    try {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
      console.log(`[LifelineDoc] Successfully opened upload file modal for ${this.category}`);
    } catch (error) {
      console.error(`[LifelineDoc] Error opening modal:`, error);
    }
  }

  /**
   * モーダルイベントリスナーを設定
   */
  setupModalEventListeners() {
    // フォルダ作成モーダル
    const createFolderModal = document.getElementById(`create-folder-modal-${this.uniqueId}`);
    if (createFolderModal) {
      const shownHandler = () => {
        // z-indexを調整
        this.adjustModalZIndex(createFolderModal);

        // フォーカスを設定（少し遅延させて確実に設定）
        setTimeout(() => {
          const folderNameInput = document.getElementById(`folder-name-${this.uniqueId}`);
          if (folderNameInput) {
            folderNameInput.focus();
            folderNameInput.select();
          }
        }, 150);
      };

      const hiddenHandler = () => {
        // 残留バックドロップをクリーンアップ
        this.cleanupBackdrops();

        const form = document.getElementById(`create-folder-form-${this.uniqueId}`);
        if (form) {
          form.reset();
          // エラー状態をクリア
          const inputs = form.querySelectorAll('.is-invalid');
          inputs.forEach(input => input.classList.remove('is-invalid'));
          const feedbacks = form.querySelectorAll('.invalid-feedback');
          feedbacks.forEach(feedback => feedback.remove());
        }
      };

      // キーボードナビゲーション
      const keydownHandler = (e) => {
        if (e.key === 'Escape') {
          const modal = bootstrap.Modal.getInstance(createFolderModal);
          if (modal) {
            modal.hide();
          }
        }
      };

      createFolderModal.addEventListener('shown.bs.modal', shownHandler);
      createFolderModal.addEventListener('hidden.bs.modal', hiddenHandler);
      createFolderModal.addEventListener('keydown', keydownHandler);

      this.eventListeners.push(
        { element: createFolderModal, event: 'shown.bs.modal', handler: shownHandler },
        { element: createFolderModal, event: 'hidden.bs.modal', handler: hiddenHandler },
        { element: createFolderModal, event: 'keydown', handler: keydownHandler }
      );
    }

    // ファイルアップロードモーダル
    const uploadFileModal = document.getElementById(`upload-file-modal-${this.uniqueId}`);
    if (uploadFileModal) {
      const shownHandler = () => {
        // z-indexを調整
        this.adjustModalZIndex(uploadFileModal);

        // ファイル入力にフォーカスを設定
        setTimeout(() => {
          const fileInput = document.getElementById(`file-input-${this.uniqueId}`);
          if (fileInput) {
            fileInput.focus();
          }
        }, 150);
      };

      const hiddenHandler = () => {
        // 残留バックドロップをクリーンアップ
        this.cleanupBackdrops();

        const form = document.getElementById(`upload-file-form-${this.uniqueId}`);
        if (form) {
          form.reset();
          // エラー状態をクリア
          const inputs = form.querySelectorAll('.is-invalid');
          inputs.forEach(input => input.classList.remove('is-invalid'));
          const feedbacks = form.querySelectorAll('.invalid-feedback');
          feedbacks.forEach(feedback => feedback.remove());
        }
        // ファイル選択表示をリセット
        const fileList = document.getElementById(`file-list-${this.uniqueId}`);
        if (fileList) {
          fileList.style.display = 'none';
        }
        // プログレスバーをリセット
        const progressContainer = document.getElementById(`upload-progress-${this.uniqueId}`);
        if (progressContainer) {
          progressContainer.style.display = 'none';
          const progressBar = progressContainer.querySelector('.progress-bar');
          if (progressBar) {
            progressBar.style.width = '0%';
          }
        }
      };

      // キーボードナビゲーション
      const keydownHandler = (e) => {
        if (e.key === 'Escape') {
          const modal = bootstrap.Modal.getInstance(uploadFileModal);
          if (modal) {
            modal.hide();
          }
        }
      };

      uploadFileModal.addEventListener('shown.bs.modal', shownHandler);
      uploadFileModal.addEventListener('hidden.bs.modal', hiddenHandler);
      uploadFileModal.addEventListener('keydown', keydownHandler);

      this.eventListeners.push(
        { element: uploadFileModal, event: 'shown.bs.modal', handler: shownHandler },
        { element: uploadFileModal, event: 'hidden.bs.modal', handler: hiddenHandler },
        { element: uploadFileModal, event: 'keydown', handler: keydownHandler }
      );
    }

    // ファイル選択時の処理
    const fileInput = document.getElementById(`file-input-${this.uniqueId}`);
    if (fileInput) {
      const changeHandler = (e) => this.handleFileSelection(e);
      fileInput.addEventListener('change', changeHandler);
      this.eventListeners.push({ element: fileInput, event: 'change', handler: changeHandler });
    }
  }

  /**
   * ファイル選択時の処理
   */
  handleFileSelection(event) {
    const files = event.target.files;
    const fileList = document.getElementById(`file-list-${this.uniqueId}`);
    const selectedFiles = document.getElementById(`selected-files-${this.uniqueId}`);

    if (files.length > 0 && fileList && selectedFiles) {
      let html = '';
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        html += `
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span><i class="fas fa-file me-2"></i>${this.escapeHtml(file.name)}</span>
            <small class="text-muted">${this.formatFileSize(file.size)}</small>
          </div>
        `;
      }
      selectedFiles.innerHTML = html;
      fileList.style.display = 'block';
    } else if (fileList) {
      fileList.style.display = 'none';
    }
  }

  /**
   * ファイルサイズをフォーマット
   */
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * フォルダ作成を処理
   */
  async handleCreateFolder(event) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const form = event.target;

    // 重複送信防止（複数の方法で確実に防ぐ）
    if (this.isCreatingFolder || form.dataset.submitting === 'true') {
      console.log('Folder creation already in progress, ignoring duplicate request');
      return;
    }

    const formData = new FormData(form);
    const folderName = formData.get('name'); // 'folder_name'ではなく'name'

    // 現在のフォルダIDを親フォルダとして追加
    console.log('[LifelineDoc] Current folder state:', this.state.currentFolder);
    if (this.state.currentFolder) {
      formData.append('parent_folder_id', this.state.currentFolder);
      console.log('[LifelineDoc] Added parent_folder_id:', this.state.currentFolder);
    } else {
      console.log('[LifelineDoc] No current folder, creating at root level');
    }

    // クライアントサイドバリデーション
    const folderNameInput = document.getElementById(`folder-name-${this.uniqueId}`);
    this.clearFieldErrors(folderNameInput);

    if (!folderName?.trim()) {
      this.showFieldError(folderNameInput, 'フォルダ名を入力してください。');
      folderNameInput.focus();
      return;
    }

    if (folderName.trim().length > 255) {
      this.showFieldError(folderNameInput, 'フォルダ名は255文字以内で入力してください。');
      folderNameInput.focus();
      return;
    }

    // 重複送信防止フラグを設定
    this.isCreatingFolder = true;
    form.dataset.submitting = 'true';

    // 送信ボタンを無効化
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnContent = submitBtn?.innerHTML;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>作成中...';
    }

    try {
      // デバッグ: FormDataの内容をログ出力
      console.log('Creating folder with data:');
      for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
      }
      console.log('Current folder:', this.state.currentFolder);
      console.log('URL:', `/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/folders`);

      const response = await fetch(`/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/folders`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      });

      console.log('Response status:', response.status);
      const result = await response.json();
      console.log('Response data:', result);

      if (result.success) {
        this.showSuccessMessage('フォルダを作成しました。');
        form.reset();

        // モーダルを閉じる
        const modalElement = document.getElementById(`create-folder-modal-${this.uniqueId}`);
        if (modalElement) {
          const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
          modal.hide();
        }

        // ドキュメント一覧を更新
        this.loadDocuments();
      } else {
        // サーバーサイドバリデーションエラーを表示
        console.error('Folder creation failed:', result);
        if (result.errors) {
          console.log('Validation errors:', result.errors);
          Object.keys(result.errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
              this.showFieldError(input, result.errors[field][0]);
            } else {
              console.warn(`Input field not found for: ${field}`);
            }
          });
          // エラーがあるが、フィールドが見つからない場合は一般的なエラーメッセージを表示
          if (Object.keys(result.errors).length > 0) {
            const firstError = Object.values(result.errors)[0];
            this.showErrorMessage(Array.isArray(firstError) ? firstError[0] : firstError);
          }
        } else {
          // メッセージを表示（重複エラーなど）
          this.showErrorMessage(result.message || 'フォルダの作成に失敗しました。');
        }
      }
    } catch (error) {
      console.error('Folder creation error:', error);
      this.showErrorMessage('ネットワークエラーが発生しました。');
    } finally {
      // 重複送信防止フラグをリセット
      this.isCreatingFolder = false;
      form.dataset.submitting = 'false';

      // 送信ボタンを復元
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnContent || '<i class="fas fa-folder-plus me-1"></i>作成';
      }
    }
  }

  /**
   * ファイルアップロードを処理
   */
  async handleUploadFile(event) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const form = event.target;

    // より強力な重複送信防止
    if (form.dataset.uploading === 'true' || this.isUploading) {
      console.log('Upload already in progress, ignoring duplicate submission');
      return;
    }

    // 複数の送信中フラグを設定
    form.dataset.uploading = 'true';
    this.isUploading = true;

    // 送信ボタンを即座に無効化
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnContent = submitBtn?.innerHTML;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>アップロード中...';
    }

    const formData = new FormData(form);
    const files = formData.getAll('files[]');

    // 現在のフォルダIDを追加
    console.log('[LifelineDoc] Current folder state for upload:', this.state.currentFolder);
    if (this.state.currentFolder) {
      formData.append('folder_id', this.state.currentFolder);
      console.log('[LifelineDoc] Added folder_id:', this.state.currentFolder);
    } else {
      console.log('[LifelineDoc] No current folder, uploading to root level');
    }

    // クライアントサイドバリデーション
    const fileInput = document.getElementById(`file-input-${this.uniqueId}`);
    this.clearFieldErrors(fileInput);

    if (!files?.length || files[0].size === 0) {
      this.showFieldError(fileInput, 'アップロードするファイルを選択してください。');
      fileInput.focus();
      // エラー時はボタンを復元
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnContent || 'アップロード';
      }
      form.dataset.uploading = 'false';
      this.isUploading = false;
      return;
    }

    // ファイルタイプチェック（PDFのみ）
    for (const file of files) {
      if (!file.name.toLowerCase().endsWith('.pdf')) {
        this.showFieldError(fileInput, `ファイル "${file.name}" はPDFファイルではありません。PDFファイルのみアップロード可能です。`);
        // エラー時はボタンを復元
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnContent || 'アップロード';
        }
        form.dataset.uploading = 'false';
        this.isUploading = false;
        return;
      }
    }

    // ファイルサイズチェック（10MB制限）
    const maxSize = 10 * 1024 * 1024; // 10MB
    for (const file of files) {
      if (file.size > maxSize) {
        this.showFieldError(fileInput, `ファイル "${file.name}" のサイズが10MBを超えています。`);
        // エラー時はボタンを復元
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnContent || 'アップロード';
        }
        form.dataset.uploading = 'false';
        this.isUploading = false;
        return;
      }
    }

    // デバッグ用：送信データを確認
    console.log('Uploading files:', files);
    console.log('FormData entries:', Array.from(formData.entries()));
    console.log('Form element:', form);
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);



    // プログレスバーを表示
    const progressContainer = document.getElementById(`upload-progress-${this.uniqueId}`);
    if (progressContainer) {
      progressContainer.style.display = 'block';
    }

    try {
      const response = await fetch(`/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/upload`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      });

      const result = await response.json();

      // デバッグ用：レスポンス情報をコンソールに出力
      console.log('Upload response:', { status: response.status, result });

      if (result.success) {
        const fileCount = files.length;
        const message = fileCount === 1
          ? 'ファイルをアップロードしました。'
          : `${fileCount}個のファイルをアップロードしました。`;

        this.showSuccessMessage(message);
        form.reset();

        // ファイル選択表示をリセット
        const fileList = document.getElementById(`file-list-${this.uniqueId}`);
        if (fileList) {
          fileList.style.display = 'none';
        }

        // モーダルを閉じる
        const modalElement = document.getElementById(`upload-file-modal-${this.uniqueId}`);
        if (modalElement) {
          const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
          modal.hide();
        }

        // ドキュメント一覧を更新
        this.loadDocuments();
      } else {
        // デバッグ用：エラーレスポンスをコンソールに出力
        console.error('Upload failed:', result);

        // サーバーサイドバリデーションエラーを表示
        if (result.errors) {
          Object.keys(result.errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${field}[]"]`);
            if (input) {
              this.showFieldError(input, result.errors[field][0]);
            }
          });
        } else {
          this.showErrorMessage(result.message || 'ファイルのアップロードに失敗しました。');
        }
      }
    } catch (error) {
      console.error('File upload error:', error);
      this.showErrorMessage('ネットワークエラーが発生しました。');
    } finally {
      // 送信中フラグをクリア
      form.dataset.uploading = 'false';
      this.isUploading = false;

      // 送信ボタンを復元
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnContent || 'アップロード';
      }

      // プログレスバーを非表示
      if (progressContainer) {
        progressContainer.style.display = 'none';
      }
    }
  }

  /**
   * ドキュメント一覧を読み込み
   */
  async loadDocuments(options = {}) {
    // 既に読み込み中の場合は処理をスキップ
    if (this.state.loading) {
      console.log('Already loading documents, skipping...');
      return;
    }

    try {
      this.setState({ loading: true, error: null });

      const params = new URLSearchParams({
        folder_id: this.state.currentFolder || '',
        view_mode: this.state.viewMode,
        per_page: options.perPage || this.defaultOptions.perPage,
        page: options.page || 1,
        sort_by: options.sortBy || this.state.sortBy,
        sort_direction: options.sortDirection || this.state.sortDirection,
        filter_type: options.filterType || '',
        search: options.search || this.state.searchQuery,
        load_stats: 'true'
      });

      const response = await fetch(`/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}?${params}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        signal: this.abortController.signal
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        this.setState({ loading: false });
        this.renderDocuments(result.data);
        this.updateBreadcrumbs(result.data.breadcrumbs);
        this.updatePagination(result.data.pagination);
        this.updateStats(result.data.stats);
      } else {
        throw new Error(result.message || 'ドキュメントの読み込みに失敗しました。');
      }

    } catch (error) {
      console.error('Document loading failed:', error);

      let errorMessage = 'ネットワークエラーが発生しました。';

      if (error.name === 'AbortError') {
        return; // Request was cancelled, don't show error
      } else if (error.message.includes('403')) {
        errorMessage = 'アクセス権限がありません。';
      } else if (error.message.includes('404')) {
        errorMessage = 'ドキュメントが見つかりません。';
      } else if (error.message) {
        errorMessage = error.message;
      }

      this.setState({ loading: false, error: errorMessage });
    }
  }

  /**
   * ドキュメント一覧を描画
   */
  renderDocuments(data) {
    console.log(`[LifelineDoc] Rendering documents for ${this.category}`, data);
    console.log(`[LifelineDoc] Data structure:`, {
      folders: data.folders?.length || 0,
      files: data.files?.length || 0,
      breadcrumbs: data.breadcrumbs?.length || 0,
      pagination: data.pagination,
      stats: data.stats
    });

    // フォルダデータの詳細をログ出力
    if (data.folders && data.folders.length > 0) {
      console.log(`[LifelineDoc] First folder data:`, data.folders[0]);
    }

    // コンテナを取得
    const container = this.getRootContainer();
    if (!container) {
      console.error(`[LifelineDoc] Container not found for category: ${this.category}`);
      return;
    }
    console.log(`[LifelineDoc] Container found:`, container);

    // 表示モードに応じて描画
    if (this.state.viewMode === 'grid') {
      this.renderGridView(data);
    } else {
      this.renderListView(data);
    }

    // ローディング表示を隠す
    const loadingIndicator = container.querySelector(`#${this._id('loading-indicator')}`);
    if (loadingIndicator) {
      loadingIndicator.style.display = 'none';
      console.log(`[LifelineDoc] Loading indicator hidden`);
    }

    // 空の状態を隠す
    const emptyState = container.querySelector(`#${this._id('empty-state')}`);
    if (emptyState) {
      emptyState.classList.add('d-none');
    }

    // ドキュメント一覧を表示
    const listContainer = container.querySelector(`#${this._id('document-list')}`);
    if (listContainer) {
      listContainer.classList.remove('d-none');
      console.log(`[LifelineDoc] Document list shown`);
    }

    // データが空の場合は空の状態を表示
    const hasData = (data.folders && data.folders.length > 0) || (data.files && data.files.length > 0);
    console.log(`[LifelineDoc] Has data:`, hasData, `(folders: ${data.folders?.length || 0}, files: ${data.files?.length || 0})`);

    if (!hasData) {
      if (emptyState) {
        emptyState.classList.remove('d-none');
        console.log(`[LifelineDoc] Empty state shown`);
      }
      if (listContainer) {
        listContainer.classList.add('d-none');
      }
    }
  }

  /**
   * リスト表示を描画
   */
  renderListView(data) {
    const container = this.getRootContainer();
    if (!container) {
      console.error(`[LifelineDoc] Container not found for renderListView`);
      return;
    }

    // uniqueIdを使用してtbodyを検索
    const tbody = container.querySelector(`#document-list-body-${this.uniqueId}`);
    if (!tbody) {
      console.error(`[LifelineDoc] tbody not found: #document-list-body-${this.uniqueId}`);
      return;
    }

    // 編集権限を確認（lifeline-document-managerコンポーネントから取得）
    const documentContainer = container.closest('[data-lifeline-category]') || container;
    const canEdit = documentContainer ?
      documentContainer.closest('.card-body')?.querySelector('button[data-bs-target*="upload"]') !== null :
      false;

    console.log(`LifelineDocumentManager renderListView - uniqueId: ${this.uniqueId}, category: ${this.category}, canEdit: ${canEdit}`);
    console.log('Document container:', documentContainer);
    console.log('Upload button found:', documentContainer?.closest('.card-body')?.querySelector('button[data-bs-target*="upload"]'));

    const config = { canEdit };
    let html = '';

    // フォルダを描画
    if (data.folders) {
      data.folders.forEach(folder => {
        html += this.renderFolderRow(folder, config.canEdit);
      });
    }

    // ファイルを描画
    if (data.files) {
      data.files.forEach(file => {
        html += this.renderFileRow(file, config.canEdit);
      });
    }

    tbody.innerHTML = html;

    // リスト表示を表示、グリッド表示を非表示
    const listView = container.querySelector(`#${this._id('document-list')}`);
    const gridView = container.querySelector(`#${this._id('document-grid')}`);

    if (listView) listView.classList.remove('d-none');
    if (gridView) gridView.classList.add('d-none');
  }

  /**
   * グリッド表示を描画
   */
  renderGridView(data) {
    const rootContainer = this.getRootContainer();
    if (!rootContainer) {
      console.error(`[LifelineDoc] Root container not found for renderGridView`);
      return;
    }

    // uniqueIdを使用してgrid bodyを検索
    const container = rootContainer.querySelector(`#document-grid-body-${this.uniqueId}`);
    if (!container) {
      console.error(`[LifelineDoc] Grid body not found: #document-grid-body-${this.uniqueId}`);
      return;
    }

    // 編集権限を確認（lifeline-document-managerコンポーネントから取得）
    const documentContainer = rootContainer.closest('[data-lifeline-category]') || rootContainer;
    const canEdit = documentContainer ?
      documentContainer.closest('.card-body')?.querySelector('button[data-bs-target*="upload"]') !== null :
      false;

    const config = { canEdit };
    let html = '';

    // フォルダを描画
    if (data.folders) {
      data.folders.forEach(folder => {
        html += this.renderFolderCard(folder);
      });
    }

    // ファイルを描画
    if (data.files) {
      data.files.forEach(file => {
        html += this.renderFileCard(file);
      });
    }

    container.innerHTML = html;

    // グリッド表示を表示、リスト表示を非表示
    const gridView = rootContainer.querySelector(`#${this._id('document-grid')}`);
    const listView = rootContainer.querySelector(`#${this._id('document-list')}`);

    if (gridView) gridView.classList.remove('d-none');
    if (listView) listView.classList.add('d-none');
  }

  /**
   * フォルダ行を描画
   */
  renderFolderRow(folder, canEdit) {
    const uniqueId = this.uniqueId;
    const actionsHtml = canEdit ? `
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="window.lifelineDocManager_${uniqueId}.renameFolder(${folder.id}); return false;">
                            <i class="fas fa-edit me-2"></i>名前変更
                        </a></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="window.lifelineDocManager_${uniqueId}.deleteFolder(${folder.id}); return false;">
                            <i class="fas fa-trash me-2"></i>削除
                        </a></li>
                    </ul>
                </div>
            </td>
        ` : '';

    return `
            <tr class="document-item" data-type="folder" data-id="${folder.id}" data-item-id="${folder.id}" data-item-type="folder" data-item-name="${this.escapeHtml(folder.name)}">
                <td><i class="fas fa-folder text-warning"></i></td>
                <td>
                    <a href="#" onclick="window.lifelineDocManager_${uniqueId}.navigateToFolder(${folder.id}); return false;" 
                       class="text-decoration-none">
                        ${this.escapeHtml(folder.name)}
                    </a>
                </td>
                <td><span class="text-muted">—</span></td>
                <td><small class="text-muted">${this.formatDate(folder.updated_at)}</small></td>
                <td><small class="text-muted">${this.escapeHtml(folder.created_by)}</small></td>
                ${actionsHtml}
            </tr>
        `;
  }

  /**
   * ファイル行を描画
   */
  renderFileRow(file, canEdit) {
    const uniqueId = this.uniqueId;
    const actionsHtml = canEdit ? `
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="${file.download_url}" target="_blank">
                            <i class="fas fa-download me-2"></i>ダウンロード
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="window.lifelineDocManager_${uniqueId}.renameFile(${file.id}); return false;">
                            <i class="fas fa-edit me-2"></i>名前変更
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="window.lifelineDocManager_${uniqueId}.moveFile(${file.id}); return false;">
                            <i class="fas fa-arrows-alt me-2"></i>移動
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="window.lifelineDocManager_${uniqueId}.deleteFile(${file.id}); return false;">
                            <i class="fas fa-trash me-2"></i>削除
                        </a></li>
                    </ul>
                </div>
            </td>
        ` : `
            <td>
                <a href="${file.download_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-download"></i>
                </a>
            </td>
        `;

    return `
            <tr class="document-item" data-type="file" data-id="${file.id}" data-item-id="${file.id}" data-item-type="file" data-item-name="${this.escapeHtml(file.name)}">
                <td><i class="${file.icon} ${file.color}"></i></td>
                <td>
                    <a href="${file.download_url}" target="_blank" class="text-decoration-none">
                        ${this.escapeHtml(file.name)}
                    </a>
                </td>
                <td><small class="text-muted">${file.formatted_size}</small></td>
                <td><small class="text-muted">${this.formatDate(file.updated_at)}</small></td>
                <td><small class="text-muted">${this.escapeHtml(file.uploaded_by)}</small></td>
                ${actionsHtml}
            </tr>
        `;
  }

  /**
   * フォルダカードを描画
   */
  renderFolderCard(folder) {
    const uniqueId = this.uniqueId;
    return `
            <div class="col-md-3 col-sm-4 col-6 mb-3">
                <div class="card document-card" data-type="folder" data-id="${folder.id}">
                    <div class="card-body text-center p-3">
                        <i class="fas fa-folder fa-2x text-warning mb-2"></i>
                        <h6 class="card-title mb-1" style="font-size: 0.9rem;">
                            <a href="#" onclick="window.lifelineDocManager_${uniqueId}.navigateToFolder(${folder.id}); return false;" 
                               class="text-decoration-none">
                                ${this.escapeHtml(folder.name)}
                            </a>
                        </h6>
                        <small class="text-muted">${this.formatDate(folder.updated_at)}</small>
                    </div>
                </div>
            </div>
        `;
  }

  /**
   * ファイルカードを描画
   */
  renderFileCard(file) {
    const uniqueId = this.uniqueId;
    return `
            <div class="col-md-3 col-sm-4 col-6 mb-3">
                <div class="card document-card" data-type="file" data-id="${file.id}">
                    <div class="card-body text-center p-3">
                        <i class="${file.icon} fa-2x ${file.color} mb-2"></i>
                        <h6 class="card-title mb-1" style="font-size: 0.9rem;">
                            <a href="${file.download_url}" target="_blank" class="text-decoration-none">
                                ${this.escapeHtml(file.name)}
                            </a>
                        </h6>
                        <small class="text-muted">${file.formatted_size}</small><br>
                        <small class="text-muted">${this.formatDate(file.updated_at)}</small>
                    </div>
                </div>
            </div>
        `;
  }

  /**
   * パンくずナビゲーションを更新
   */
  updateBreadcrumbs(breadcrumbs) {
    const rootContainer = this.getRootContainer();
    if (!rootContainer) return;

    const container = rootContainer.querySelector(`#breadcrumb-nav-${this.uniqueId}`);
    if (!container || !breadcrumbs) return;

    let html = '';
    breadcrumbs.forEach((crumb, index) => {
      if (crumb.is_current) {
        html += `<li class="breadcrumb-item active">${this.escapeHtml(crumb.name)}</li>`;
      } else {
        const folderId = crumb.id === null ? 'null' : crumb.id;
        html += `
                    <li class="breadcrumb-item">
                        <a href="#" onclick="window.lifelineDocManager_${this.category}.navigateToFolder(${folderId}); return false;">
                            ${index === 0 ? '<i class="fas fa-home me-1"></i>' : ''}${this.escapeHtml(crumb.name)}
                        </a>
                    </li>
                `;
      }
    });

    container.innerHTML = html;
  }

  /**
   * ページネーションを更新
   */
  updatePagination(pagination) {
    const rootContainer = this.getRootContainer();
    if (!rootContainer) return;

    const container = rootContainer.querySelector(`#document-pagination-${this.uniqueId}`);
    if (!container || !pagination) return;

    let html = '';

    // 前のページ
    if (pagination.current_page > 1) {
      html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="window.lifelineDocManager_${this.category}.loadPage(${pagination.current_page - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
    }

    // ページ番号
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
      const isActive = i === pagination.current_page;
      html += `
                <li class="page-item ${isActive ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="window.lifelineDocManager_${this.category}.loadPage(${i})">${i}</a>
                </li>
            `;
    }

    // 次のページ
    if (pagination.current_page < pagination.last_page) {
      html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="window.lifelineDocManager_${this.category}.loadPage(${pagination.current_page + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
    }

    container.innerHTML = html;
  }

  /**
   * 統計情報を更新
   */
  updateStats(stats) {
    const rootContainer = this.getRootContainer();
    if (!rootContainer) return;

    const container = rootContainer.querySelector(`#document-info-${this.uniqueId}`);
    if (!container || !stats) return;

    container.innerHTML = `
            フォルダ: ${stats.folder_count}個 | 
            ファイル: ${stats.file_count}個 | 
            合計サイズ: ${stats.formatted_size}
        `;
  }

  /**
   * フォルダに移動
   */
  navigateToFolder(folderId) {
    // nullまたは'null'文字列をnullに正規化
    const normalizedFolderId = (folderId === 'null' || folderId === null || folderId === undefined) ? null : folderId;
    console.log(`[LifelineDoc] ${this.category}: Navigating to folder: ${normalizedFolderId} (original: ${folderId})`);
    console.log(`[LifelineDoc] ${this.category}: Instance ID:`, this);
    this.setState({ currentFolder: normalizedFolderId });
    console.log(`[LifelineDoc] ${this.category}: State updated, currentFolder is now: ${this.state.currentFolder}`);
    this.loadDocuments();
  }

  /**
   * 表示モードを設定
   */
  setViewMode(mode) {
    this.setState({ viewMode: mode });

    // ボタンの状態を更新
    const buttons = document.querySelectorAll(`[data-view-mode]`);
    buttons.forEach(btn => {
      if (btn.dataset.viewMode === mode) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    this.loadDocuments();
  }

  /**
   * ページを読み込み
   */
  loadPage(page) {
    this.loadDocuments({ page });
  }

  /**
   * 検索入力を処理
   */
  handleSearchInput(event) {
    const query = event.target.value.trim();

    // 既存のタイムアウトをクリア
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }

    // 新しいタイムアウトを設定
    this.searchTimeout = setTimeout(() => {
      this.loadDocuments({ search: query });
    }, this.defaultOptions.searchDelay);
  }

  /**
   * 検索を処理
   */
  handleSearch(category, query) {
    this.loadDocuments(category, { search: query });
  }

  // ファイルアップロード処理はBladeコンポーネント内で直接処理

  // フォルダ作成処理はBladeコンポーネント内で直接処理

  /**
   * アップロードモーダルを表示（Bootstrap data-bs-toggle使用時は不要だが、互換性のため残す）
   */
  showUploadModal(category) {
    console.log(`Upload modal for category: ${category} - using Bootstrap data-bs-toggle`);
  }

  /**
   * フォルダ作成モーダルを表示（Bootstrap data-bs-toggle使用時は不要だが、互換性のため残す）
   */
  showCreateFolderModal(category) {
    console.log(`Create folder modal for category: ${category} - using Bootstrap data-bs-toggle`);
  }

  /**
   * ローディング表示
   */
  showLoading() {
    const c = this.getRootContainer();
    c?.querySelector(`#${this._id('loading-indicator')}`)?.classList.remove('d-none');
    c?.querySelector(`#${this._id('error-message')}`)?.classList.add('d-none');
    c?.querySelector(`#${this._id('empty-state')}`)?.classList.add('d-none');
  }

  /**
   * ローディング非表示
   */
  hideLoading() {
    this.getRootContainer()?.querySelector(`#${this._id('loading-indicator')}`)?.classList.add('d-none');
  }

  /**
   * エラー表示
   */
  showError(message) {
    const c = this.getRootContainer();
    const errorElement = c?.querySelector(`#${this._id('error-message')}`);
    const messageElement = c?.querySelector(`#${this._id('error-text')}`);

    if (errorElement && messageElement) {
      messageElement.textContent = message;
      errorElement.classList.remove('d-none');
    }
  }

  /**
   * 空の状態を切り替え
   */
  toggleEmptyState(isEmpty) {
    const emptyElement = this.getRootContainer()?.querySelector(`#${this._id('empty-state')}`);
    if (emptyElement) {
      if (isEmpty) {
        emptyElement.classList.remove('d-none');
      } else {
        emptyElement.classList.add('d-none');
      }
    }
  }

  /**
   * 成功メッセージを表示
   */
  showSuccessMessage(message) {
    // AppUtilsのshowToastを使用（存在する場合）
    if (typeof window.AppUtils !== 'undefined' && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, 'success');
    } else {
      // フォールバック: コンソールログ
      console.log('Success:', message);
    }
  }

  /**
   * エラーメッセージを表示
   */
  showErrorMessage(message) {
    // AppUtilsのshowToastを使用（存在する場合）
    if (typeof window.AppUtils !== 'undefined' && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, 'error');
    } else {
      // フォールバック: アラート
      alert('エラー: ' + message);
    }
  }

  /**
   * トースト表示（統一メソッド）
   */
  showToast(message, type = 'info') {
    if (typeof window.AppUtils !== 'undefined' && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, type);
    } else {
      // フォールバック
      if (type === 'error') {
        alert(`エラー: ${message}`);
      } else {
        alert(message);
      }
    }
  }

  /**
   * 確認ダイアログ表示
   */
  async showConfirmDialog(message, title = '確認') {
    if (typeof window.AppUtils !== 'undefined' && window.AppUtils.confirmDialog) {
      return await window.AppUtils.confirmDialog(message, title, { type: 'delete' });
    } else {
      // フォールバック: 標準confirm
      return confirm(message);
    }
  }

  /**
   * コンテキストメニューイベントを設定
   */
  setupContextMenuEvents() {
    const contextMenu = document.getElementById(`context-menu-${this.uniqueId}`);
    if (!contextMenu) return;

    // ドキュメントリスト内での右クリック
    const documentList = this.getRootContainer()?.querySelector('#document-list');
    if (documentList) {
      const contextMenuHandler = (e) => {
        e.preventDefault();

        // クリックされた要素から最も近いアイテムを取得
        const item = e.target.closest('[data-item-id]');
        if (!item) return;

        const itemId = item.dataset.itemId;
        const itemType = item.dataset.itemType;
        const itemName = item.dataset.itemName;

        // コンテキストメニューの位置を設定
        contextMenu.style.left = e.pageX + 'px';
        contextMenu.style.top = e.pageY + 'px';
        contextMenu.style.display = 'block';

        // メニューアイテムにデータを設定
        contextMenu.dataset.itemId = itemId;
        contextMenu.dataset.itemType = itemType;
        contextMenu.dataset.itemName = itemName;

        // ファイル/フォルダ専用メニューの表示制御
        const fileOnlyItems = contextMenu.querySelectorAll('[data-file-only="true"]');
        const folderOnlyItems = contextMenu.querySelectorAll('[data-folder-only="true"]');

        fileOnlyItems.forEach(item => {
          item.style.display = itemType === 'file' ? 'block' : 'none';
        });

        folderOnlyItems.forEach(item => {
          item.style.display = itemType === 'folder' ? 'block' : 'none';
        });
      };

      documentList.addEventListener('contextmenu', contextMenuHandler);
      this.eventListeners.push({ element: documentList, event: 'contextmenu', handler: contextMenuHandler });
    }

    // コンテキストメニューアイテムのクリック
    const menuItems = contextMenu.querySelectorAll('.context-menu-item');
    menuItems.forEach(item => {
      const clickHandler = (e) => {
        e.preventDefault();
        const action = item.dataset.action;
        const itemId = contextMenu.dataset.itemId;
        const itemType = contextMenu.dataset.itemType;
        const itemName = contextMenu.dataset.itemName;

        // メニューを隠す
        contextMenu.style.display = 'none';

        // アクションを実行
        this.handleContextMenuAction(action, itemId, itemType, itemName);
      };

      item.addEventListener('click', clickHandler);
      this.eventListeners.push({ element: item, event: 'click', handler: clickHandler });
    });

    // 他の場所をクリックしたらメニューを隠す
    const documentClickHandler = (e) => {
      if (!contextMenu.contains(e.target)) {
        contextMenu.style.display = 'none';
      }
    };

    document.addEventListener('click', documentClickHandler);
    this.eventListeners.push({ element: document, event: 'click', handler: documentClickHandler });
  }

  /**
   * コンテキストメニューアクションを処理
   */
  async handleContextMenuAction(action, itemId, itemType, itemName) {
    switch (action) {
      case 'delete':
        await this.deleteItem(itemId, itemType);
        break;
      case 'rename':
        this.showRenameModal(itemId, itemType, itemName);
        break;
      case 'download':
        if (itemType === 'file') {
          this.downloadFile(itemId);
        }
        break;
      case 'properties':
        this.showPropertiesModal(itemId, itemType, itemName);
        break;
      default:
        console.log(`Unhandled context menu action: ${action}`);
    }
  }

  /**
   * ファイルダウンロード
   */
  downloadFile(fileId) {
    const downloadUrl = `/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/files/${fileId}/download`;
    window.open(downloadUrl, '_blank');
  }

  /**
   * 名前変更モーダル表示
   */
  showRenameModal(itemId, itemType, currentName) {
    const modal = document.getElementById(`rename-modal-${this.uniqueId}`);
    const input = document.getElementById(`rename-input-${this.uniqueId}`);

    if (modal && input) {
      input.value = currentName;
      modal.dataset.itemId = itemId;
      modal.dataset.itemType = itemType;

      const bootstrapModal = new bootstrap.Modal(modal);
      bootstrapModal.show();
    }
  }

  /**
   * プロパティモーダル表示
   */
  showPropertiesModal(itemId, itemType, itemName) {
    const modal = document.getElementById(`properties-modal-${this.uniqueId}`);
    const content = document.getElementById(`properties-content-${this.uniqueId}`);

    if (modal && content) {
      content.innerHTML = `
        <div class="row">
          <div class="col-sm-3"><strong>名前:</strong></div>
          <div class="col-sm-9">${itemName}</div>
        </div>
        <div class="row mt-2">
          <div class="col-sm-3"><strong>種類:</strong></div>
          <div class="col-sm-9">${itemType === 'file' ? 'ファイル' : 'フォルダ'}</div>
        </div>
        <div class="row mt-2">
          <div class="col-sm-3"><strong>ID:</strong></div>
          <div class="col-sm-9">${itemId}</div>
        </div>
      `;

      const bootstrapModal = new bootstrap.Modal(modal);
      bootstrapModal.show();
    }
  }

  /**
   * フィールドエラーを表示
   */
  showFieldError(input, message) {
    if (!input) return;

    // 既存のエラーをクリア
    this.clearFieldErrors(input);

    // input要素にエラークラスを追加
    input.classList.add('is-invalid');

    // エラーメッセージを作成
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;

    // エラーメッセージを挿入
    input.parentNode.insertBefore(errorDiv, input.nextSibling);

    // フォーカスを設定
    input.focus();
  }

  /**
   * フィールドエラーをクリア
   */
  clearFieldErrors(input) {
    if (!input) return;

    // エラークラスを削除
    input.classList.remove('is-invalid');

    // 既存のエラーメッセージを削除
    const existingError = input.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
      existingError.remove();
    }
  }

  /**
   * デバウンス関数
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

  /**
   * HTMLエスケープ
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * カテゴリ別IDを生成（ID衝突防止）
   */
  _id(name) {
    return `${name}-${this.uniqueId}`;
  }

  /**
   * API用カテゴリ名を取得（エイリアス変換）
   */
  get apiCategory() {
    return this.categoryAliasMap?.[this.category] || this.category;
  }

  /**
   * カテゴリエイリアスを解決（static用）
   */
  static resolveApiCategory(category) {
    const aliasMap = {
      electric: 'electrical',
    };
    return aliasMap[category] || category;
  }

  /**
   * モーダルz-indexを調整（保険）
   */
  adjustModalZIndex(modalEl) {
    try {
      const bds = document.querySelectorAll('.modal-backdrop');
      const top = bds[bds.length - 1];
      const bz = parseInt(getComputedStyle(top)?.zIndex || '1050', 10);
      modalEl.style.zIndex = String(bz + 5);
      const dlg = modalEl.querySelector('.modal-dialog');
      if (dlg) dlg.style.zIndex = String(bz + 6);
    } catch (e) {
      console.warn('[LifelineDoc] z-index adjust failed:', e);
    }
  }

  /**
   * 日付フォーマット
   */
  formatDate(dateString) {
    if (!dateString) return '—';

    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  /**
   * フォルダ名変更（プレースホルダー）
   */
  renameFolder(folderId) {
    console.log(`Rename folder ${folderId} in category ${this.category}`);
    // TODO: 実装予定
  }

  /**
   * フォルダ削除
   */
  async deleteFolder(folderId) {
    console.log(`Delete folder ${folderId} in category ${this.category}`);

    try {
      // 削除確認
      const confirmed = await this.showConfirmDialog(
        'このフォルダを削除しますか？\n削除したフォルダは復元できません。',
        '削除確認'
      );

      if (!confirmed) return;

      // 削除API呼び出し
      const response = await fetch(`/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/folders/${folderId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showToast('フォルダを削除しました', 'success');
        // リスト更新（ページリロードなし）
        setTimeout(async () => {
          try {
            await this.loadDocuments();
          } catch (error) {
            console.error('Failed to reload documents:', error);
          }
        }, 500);
      } else {
        this.showToast(result.message || 'フォルダの削除に失敗しました', 'error');
      }
    } catch (error) {
      console.error('Folder deletion error:', error);
      this.showToast('フォルダの削除に失敗しました', 'error');
    }
  }

  /**
   * ファイル名変更（プレースホルダー）
   */
  renameFile(fileId) {
    console.log(`Rename file ${fileId} in category ${this.category}`);
    // TODO: 実装予定
  }

  /**
   * ファイル削除
   */
  async deleteFile(fileId) {
    console.log(`Delete file ${fileId} in category ${this.category}`);

    try {
      // 削除確認
      const confirmed = await this.showConfirmDialog(
        'このファイルを削除しますか？\n削除したファイルは復元できません。',
        '削除確認'
      );

      if (!confirmed) return;

      // 削除API呼び出し
      const response = await fetch(`/facilities/${this.facilityId}/lifeline-documents/${this.apiCategory}/files/${fileId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showToast('ファイルを削除しました', 'success');
        // リスト更新（ページリロードなし）
        setTimeout(async () => {
          try {
            await this.loadDocuments();
          } catch (error) {
            console.error('Failed to reload documents:', error);
          }
        }, 500);
      } else {
        this.showToast(result.message || 'ファイルの削除に失敗しました', 'error');
      }
    } catch (error) {
      console.error('File deletion error:', error);
      this.showToast('ファイルの削除に失敗しました', 'error');
    }
  }

  /**
   * ファイル移動（プレースホルダー）
   */
  moveFile(fileId) {
    console.log(`Move file ${fileId} in category ${this.category}`);
    // TODO: 実装予定
  }

  /**
   * アイテム名変更（プレースホルダー）
   */
  renameItem(category) {
    console.log(`Rename item in category ${category}`);
    // TODO: 実装予定
  }

  /**
   * アイテム移動（プレースホルダー）
   */
  moveItem(category) {
    console.log(`Move item in category ${category}`);
    // TODO: 実装予定
  }

  /**
   * アイテム削除（汎用）
   */
  async deleteItem(itemId, itemType) {
    console.log(`Delete ${itemType} ${itemId} in category ${this.category}`);

    if (itemType === 'folder') {
      await this.deleteFolder(itemId);
    } else if (itemType === 'file') {
      await this.deleteFile(itemId);
    }
  }

  /**
   * State management
   */
  setState(newState) {
    this.state = { ...this.state, ...newState };
    this.onStateChange();
  }

  onStateChange() {
    // React to state changes
    if (this.state.loading) {
      this.showLoading();
    } else {
      this.hideLoading();
    }

    if (this.state.error) {
      this.showError(this.state.error);
    }
  }

  /**
   * Get cached DOM element
   */
  getElement(elementId) {
    if (!this.elements[elementId]) {
      this.elements[elementId] = document.getElementById(elementId);
    }
    return this.elements[elementId];
  }

  /**
   * Debounce utility
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

  /**
   * Perform search with current query
   */
  performSearch(query) {
    this.loadDocuments({ search: query });
  }

  /**
   * Cleanup method
   */
  destroy() {
    // Cancel any pending requests
    this.abortController.abort();

    // Remove event listeners
    this.eventListeners.forEach(({ element, event, handler }) => {
      if (element && element.removeEventListener) {
        element.removeEventListener(event, handler);
      }
    });

    this.eventListeners = [];
    this.elements = {};
    this.initialized = false;
  }

  // 静的メソッド - HTMLから呼び出し可能
  static async deleteFolder(category, folderId) {
    console.log(`Static deleteFolder called: ${category}, ${folderId}`);

    // 簡単な確認ダイアログ
    if (!confirm('このフォルダを削除しますか？\n削除したフォルダは復元できません。')) {
      return;
    }

    try {
      const apiCategory = LifelineDocumentManager.resolveApiCategory(category);
      const response = await fetch(`/facilities/${window.facilityId || 'unknown'}/lifeline-documents/${apiCategory}/folders/${folderId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const result = await response.json();

      if (result.success) {
        // 削除後にドキュメントリストを更新（ページリロードなし）
        const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
        if (manager && typeof manager.loadDocuments === 'function') {
          await manager.loadDocuments();
          alert('フォルダを削除しました');
        } else {
          // マネージャーが見つからない場合はページリロード
          alert('フォルダを削除しました');
          setTimeout(() => {
            LifelineDocumentManager.redirectToDocumentSection(category);
          }, 500);
        }
      } else {
        alert('フォルダの削除に失敗しました: ' + (result.message || ''));
      }
    } catch (error) {
      console.error('Folder deletion error:', error);
      alert('フォルダの削除に失敗しました');
    }
  }

  static async deleteFile(category, fileId) {
    console.log(`Static deleteFile called: ${category}, ${fileId}`);

    // 簡単な確認ダイアログ
    if (!confirm('このファイルを削除しますか？\n削除したファイルは復元できません。')) {
      return;
    }

    try {
      const apiCategory = LifelineDocumentManager.resolveApiCategory(category);
      const response = await fetch(`/facilities/${window.facilityId || 'unknown'}/lifeline-documents/${apiCategory}/files/${fileId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const result = await response.json();

      if (result.success) {
        // 削除後にドキュメントリストを更新（ページリロードなし）
        const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
        if (manager && typeof manager.loadDocuments === 'function') {
          await manager.loadDocuments();
          alert('ファイルを削除しました');
        } else {
          // マネージャーが見つからない場合はページリロード
          alert('ファイルを削除しました');
          setTimeout(() => {
            LifelineDocumentManager.redirectToDocumentSection(category);
          }, 500);
        }
      } else {
        alert('ファイルの削除に失敗しました: ' + (result.message || ''));
      }
    } catch (error) {
      console.error('File deletion error:', error);
      alert('ファイルの削除に失敗しました');
    }
  }

  static async renameFolder(category, folderId) {
    const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
    if (manager) {
      // TODO: 名前変更機能の実装
      console.log(`Rename folder ${folderId} in category ${category}`);
    } else {
      console.error(`LifelineDocumentManager not found for category: ${category}`);
    }
  }

  static async renameFile(category, fileId) {
    const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
    if (manager) {
      // TODO: 名前変更機能の実装
      console.log(`Rename file ${fileId} in category ${category}`);
    } else {
      console.error(`LifelineDocumentManager not found for category: ${category}`);
    }
  }

  static async moveFile(category, fileId) {
    const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
    if (manager) {
      // TODO: 移動機能の実装
      console.log(`Move file ${fileId} in category ${category}`);
    } else {
      console.error(`LifelineDocumentManager not found for category: ${category}`);
    }
  }

  static async navigateToFolder(category, folderId) {
    const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
    if (manager) {
      console.log(`Navigate to folder ${folderId} in category ${category}`);
      manager.navigateToFolder(folderId);
    } else {
      console.error(`LifelineDocumentManager not found for category: ${category}`);
    }
  }

  /**
   * 削除後に適切なドキュメントセクションにリダイレクト
   */
  static redirectToDocumentSection(category) {
    console.log(`Redirecting to ${category} documents section...`);

    // 現在のURLを取得
    const currentUrl = new URL(window.location.href);

    // URLパラメータを追加
    currentUrl.searchParams.set('open_documents', category);

    console.log(`Redirecting to: ${currentUrl.toString()}`);

    // 強制リロード
    window.location.href = currentUrl.toString();
  }
}

// ===== Modal support (defined via prototype/static assignments to avoid parser quirks) =====
LifelineDocumentManager.prototype.getRootContainer = function () {
  if (this.rootContainer) return this.rootContainer;

  // Try multiple selectors to find the container
  // uniqueIdを優先的に使用し、見つからない場合はcategoryで検索
  const selectors = [
    `#document-management-container-${this.uniqueId}`,
    `#document-management-container-${this.category}`,
    `[data-lifeline-category="${this.category}"]`,
    `.document-management[data-lifeline-category="${this.category}"]`
  ];

  for (const sel of selectors) {
    const container = document.querySelector(sel);
    if (container) {
      console.log(`[LifelineDoc] Found container for ${this.uniqueId} using selector: ${sel}`);
      this.rootContainer = container;
      return this.rootContainer;
    }
  }

  console.warn(`[LifelineDoc] Container not found for uniqueId: ${this.uniqueId}, category: ${this.category}`);
  return null;
};

LifelineDocumentManager.prototype.ensureModalContainer = function () {
  const modalId = `${this.options.modalIdPrefix}-${this.category}`;
  let modal = document.getElementById(modalId);
  if (!modal) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
      <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">ドキュメント（${this.category}）</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="lifeline-doc-root" data-lifeline-category="${this.category}">
                <!-- ツールバー -->
                <div class="document-toolbar mb-3">
                  <button type="button" id="create-folder-btn-${this.category}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-folder-plus me-1"></i>新しいフォルダ
                  </button>
                  <button type="button" id="upload-file-btn-${this.category}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-upload me-1"></i>ファイルアップロード
                  </button>
                </div>

                <nav aria-label="breadcrumb" class="mb-2">
                  <ol class="breadcrumb mb-0" id="document-breadcrumb-${this.category}"></ol>
                </nav>
                <div id="document-info-${this.category}" class="small text-muted mb-2"></div>

                <div id="loading-indicator-${this.category}" class="d-none text-center py-5">
                  <i class="fas fa-spinner fa-spin me-2"></i>読み込み中...
                </div>

                <div id="error-message-${this.category}" class="alert alert-danger d-none"><span id="error-text-${this.category}"></span></div>
                <div id="empty-state-${this.category}" class="alert alert-info d-none">ドキュメントがありません。</div>

                <div id="document-list-${this.category}" class="d-none">
                  <table class="table table-sm align-middle mb-2">
                    <thead>
                      <tr>
                        <th style="width:28px"></th>
                        <th>名前</th>
                        <th style="width:100px">サイズ</th>
                        <th style="width:150px">更新日</th>
                        <th style="width:160px">作成者/アップロード</th>
                        <th style="width:42px"></th>
                      </tr>
                    </thead>
                    <tbody id="document-list-body-${this.category}"></tbody>
                  </table>
                </div>

                <div id="document-grid-${this.category}" class="d-none">
                  <div id="document-grid-body-${this.category}" class="row g-2"></div>
                </div>

                <nav>
                  <ul class="pagination pagination-sm justify-content-center" id="document-pagination-${this.category}"></ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- フォルダ作成モーダル -->
      <div class="modal" id="create-folder-modal-${this.category}" tabindex="-1" aria-labelledby="create-folder-modal-title-${this.category}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="create-folder-modal-title-${this.category}">新しいフォルダ</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="create-folder-form-${this.category}" method="POST">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="folder-name-${this.category}" class="form-label">フォルダ名</label>
                  <input type="text" class="form-control" id="folder-name-${this.category}" name="folder_name" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="submit" class="btn btn-primary">作成</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ファイルアップロードモーダル -->
      <div class="modal" id="upload-file-modal-${this.category}" tabindex="-1" aria-labelledby="upload-file-modal-title-${this.category}" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="upload-file-modal-title-${this.category}">ファイルアップロード</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="upload-file-form-${this.category}" method="POST" enctype="multipart/form-data">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="file-input-${this.category}" class="form-label">ファイル選択</label>
                  <input type="file" class="form-control" id="file-input-${this.category}" name="file" required multiple>
                  <div class="form-text">複数ファイルを選択できます</div>
                </div>
                <div id="file-list-${this.category}" class="mb-3" style="display:none;">
                  <label class="form-label">選択されたファイル:</label>
                  <ul id="file-list-items-${this.category}" class="list-group"></ul>
                </div>
                <div id="upload-progress-${this.category}" class="mb-3" style="display:none;">
                  <label class="form-label">アップロード進捗:</label>
                  <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="submit" class="btn btn-success">アップロード</button>
              </div>
            </form>
          </div>
        </div>
      </div>`;

    // すべての子要素（メインモーダル、フォルダ作成モーダル、ファイルアップロードモーダル）をbody直下に追加
    while (wrapper.firstElementChild) {
      document.body.appendChild(wrapper.firstElementChild);
    }

    modal = document.getElementById(modalId);
    console.log(`[LifelineDoc] Created modal container for ${this.category}`);

    // フォルダ作成モーダルとファイルアップロードモーダルも確認
    const createFolderModal = document.getElementById(`create-folder-modal-${this.uniqueId}`);
    const uploadFileModal = document.getElementById(`upload-file-modal-${this.uniqueId}`);
    console.log(`[LifelineDoc] Create folder modal exists:`, !!createFolderModal);
    console.log(`[LifelineDoc] Upload file modal exists:`, !!uploadFileModal);
  }
  this.rootContainer = modal.querySelector(`[data-lifeline-category="${this.category}"]`);
  return modal;
};

LifelineDocumentManager.prototype.openModal = function () {
  // Bladeテンプレートで生成された既存のモーダルを使用
  const modalId = `${this.category}-documents-modal`;
  const modal = document.getElementById(modalId);

  if (!modal) {
    console.error(`[LifelineDoc] Modal not found: ${modalId}`);
    return;
  }

  const bs = bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, keyboard: true, focus: true });

  // モーダル表示時のイベントリスナー（{ once: true }を外して毎回実行）
  modal.addEventListener('shown.bs.modal', () => {
    try {
      this.adjustModalZIndex(modal);
      this.loadDocuments();
    } catch (e) {
      console.error('[LifelineDoc] Error in modal shown handler:', e);
    }
  });

  // モーダルを閉じる前にフォーカスを解除（aria-hidden警告を防ぐ）
  modal.addEventListener('hide.bs.modal', () => {
    try {
      // モーダル内のフォーカスされている要素からフォーカスを外す
      const focusedElement = modal.querySelector(':focus');
      if (focusedElement) {
        focusedElement.blur();
      }
    } catch (e) {
      console.error('[LifelineDoc] Error in modal hide handler:', e);
    }
  });

  // クリーンアップ
  modal.addEventListener('hidden.bs.modal', () => {
    try {
      this.destroy();
      this.cleanupBackdrops();

      // body要素の状態を完全に復元
      setTimeout(() => {
        const remainingModals = document.querySelectorAll('.modal.show');
        if (remainingModals.length === 0) {
          document.body.classList.remove('modal-open');
          document.body.style.removeProperty('overflow');
          document.body.style.removeProperty('padding-right');
          console.log('[LifelineDoc] Body state restored');
        }
      }, 100);
    } catch (e) {
      console.error('[LifelineDoc] Error in modal hidden handler:', e);
    }
    this.rootContainer = null;
  });

  bs.show();
};

// 静的ユーティリティ: 直接モーダルで開く
LifelineDocumentManager.openInModal = function (facilityId, category, options = {}) {
  const manager = new LifelineDocumentManager(facilityId, category, { ...options, mountInModal: true });
  manager.openModal();
  // グローバル参照（既存慣習に合わせる）
  if (!window.shiseCalApp) window.shiseCalApp = { modules: {} };
  window.shiseCalApp.modules[`lifelineDocumentManager_${category}`] = manager;
  return manager;
};

// 直接呼び出し用ヘルパ（例：window.LifelineDocumentManager.openInModal(facilityId, 'electric')）
if (!window.LifelineDocumentManager) {
  window.LifelineDocumentManager = LifelineDocumentManager;
}

// デフォルトエクスポート
export default LifelineDocumentManager;
// 既に上でグローバル公開済み