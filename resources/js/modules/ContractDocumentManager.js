/**
 * 契約書ドキュメント管理クラス
 * 
 * 契約書カテゴリに対応したドキュメント管理機能を提供
 */
class ContractDocumentManager {
  constructor(facilityId) {
    this.facilityId = facilityId;
    this.category = 'contracts';
    this.currentFolderId = null;
    this.currentPath = [];
    this.viewMode = 'list';
    this.selectedItem = null;
    this.isInitialLoad = true; // 初回ロードフラグ

    // 再試行機能の設定
    this.maxRetries = 3;
    this.retryCount = 0;
    this.retryDelay = 1000; // 初期遅延時間（ミリ秒）
    this.lastFailedRequest = null; // 最後に失敗したリクエスト情報

    // グローバルに登録
    window.contractDocManager = this;

    console.log(`[ContractDoc] Initializing manager for facility: ${facilityId}`);

    this.init();
  }

  init() {
    this.cacheElements();
    this.attachEventListeners();
    this.setupLazyLoading();
    // 初期ロードは行わない（遅延ロード）
  }

  /**
   * 遅延ロード機能のセットアップ
   * 統一ドキュメントセクションが初めて展開されたときにドキュメントを読み込む
   */
  setupLazyLoading() {
    const unifiedSection = document.getElementById('unified-documents-section');

    if (!unifiedSection) {
      console.warn('[ContractDoc] Unified section not found, loading documents immediately');
      this.loadDocuments();
      return;
    }

    console.log('[ContractDoc] Lazy loading enabled - documents will load on first expand');

    // shown.bs.collapseイベントをリッスン
    unifiedSection.addEventListener('shown.bs.collapse', () => {
      if (this.isInitialLoad) {
        console.log('[ContractDoc] First expand detected - loading documents');
        this.isInitialLoad = false;
        this.loadDocuments();
      } else {
        console.log('[ContractDoc] Section expanded - documents already loaded');
      }
    });
  }

  cacheElements() {
    this.elements = {
      container: document.getElementById('contract-document-management-container'),
      createFolderBtn: document.getElementById('create-folder-btn-contracts'),
      uploadFileBtn: document.getElementById('upload-file-btn-contracts'),
      emptyUploadBtn: document.getElementById('empty-upload-btn-contracts'),
      searchInput: document.getElementById('search-input-contracts'),
      searchBtn: document.getElementById('search-btn-contracts'),
      listViewRadio: document.getElementById('list-view-contracts'),
      gridViewRadio: document.getElementById('grid-view-contracts'),
      breadcrumbNav: document.getElementById('breadcrumb-nav-contracts'),
      loadingIndicator: document.getElementById('loading-indicator-contracts'),
      errorMessage: document.getElementById('error-message-contracts'),
      errorText: document.getElementById('error-text-contracts'),
      emptyState: document.getElementById('empty-state-contracts'),
      documentList: document.getElementById('document-list-contracts'),
      documentListView: document.getElementById('document-list-view-contracts'),
      documentListBody: document.getElementById('document-list-body-contracts'),
      documentGrid: document.getElementById('document-grid-contracts'),
      documentGridBody: document.getElementById('document-grid-body-contracts'),
      createFolderModal: document.getElementById('create-folder-modal-contracts'),
      createFolderForm: document.getElementById('create-folder-form-contracts'),
      folderNameInput: document.getElementById('folder-name-contracts'),
      uploadFileModal: document.getElementById('upload-file-modal-contracts'),
      uploadFileForm: document.getElementById('upload-file-form-contracts'),
      fileInput: document.getElementById('file-input-contracts'),
      uploadProgress: document.getElementById('upload-progress-contracts'),
      contextMenu: document.getElementById('context-menu-contracts'),
      renameModal: document.getElementById('rename-modal-contracts'),
      renameForm: document.getElementById('rename-form-contracts'),
      renameInput: document.getElementById('rename-input-contracts'),
      propertiesModal: document.getElementById('properties-modal-contracts'),
      propertiesContent: document.getElementById('properties-content-contracts'),
    };
  }

  attachEventListeners() {
    // フォルダ作成ボタン
    if (this.elements.createFolderBtn) {
      this.elements.createFolderBtn.addEventListener('click', () => this.showCreateFolderModal());
    }

    // ファイルアップロードボタン
    if (this.elements.uploadFileBtn) {
      this.elements.uploadFileBtn.addEventListener('click', () => this.showUploadFileModal());
    }

    // 空の状態のアップロードボタン
    if (this.elements.emptyUploadBtn) {
      this.elements.emptyUploadBtn.addEventListener('click', () => this.showUploadFileModal());
    }

    // 検索
    if (this.elements.searchBtn) {
      this.elements.searchBtn.addEventListener('click', () => this.handleSearch());
    }
    if (this.elements.searchInput) {
      this.elements.searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          this.handleSearch();
        }
      });
    }

    // 表示モード切替
    if (this.elements.listViewRadio) {
      this.elements.listViewRadio.addEventListener('change', () => this.switchViewMode('list'));
    }
    if (this.elements.gridViewRadio) {
      this.elements.gridViewRadio.addEventListener('change', () => this.switchViewMode('grid'));
    }

    // フォルダ作成フォーム
    if (this.elements.createFolderForm) {
      this.elements.createFolderForm.addEventListener('submit', (e) => this.handleCreateFolder(e));
    }

    // ファイルアップロードフォーム
    if (this.elements.uploadFileForm) {
      this.elements.uploadFileForm.addEventListener('submit', (e) => this.handleUploadFile(e));
    }

    // 名前変更フォーム
    if (this.elements.renameForm) {
      this.elements.renameForm.addEventListener('submit', (e) => this.handleRename(e));
    }

    // コンテキストメニュー
    document.addEventListener('click', () => this.hideContextMenu());
    document.addEventListener('contextmenu', (e) => {
      if (e.target.closest('#document-list-body-contracts') ||
        e.target.closest('#document-grid-body-contracts')) {
        e.preventDefault();
      }
    });
  }

  async loadDocuments(folderId = null) {
    try {
      this.showLoading();

      const url = `/facilities/${this.facilityId}/contract-documents`;
      const params = new URLSearchParams();
      if (folderId) {
        params.append('folder_id', folderId);
      }

      const response = await fetch(`${url}?${params}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        this.currentFolderId = folderId;
        this.renderDocuments(result.data);
        this.updateBreadcrumbs(result.data.breadcrumbs || []);
        // 成功時は再試行カウントをリセット
        this.resetRetryState();
      } else {
        this.showError(result.message || 'ドキュメントの読み込みに失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Load documents error:', error);

      // ネットワークエラーの場合は再試行機能を提供
      if (this.isNetworkError(error)) {
        this.lastFailedRequest = { action: 'loadDocuments', params: { folderId } };
        this.showNetworkError('ドキュメントの読み込み中にネットワークエラーが発生しました。');
      } else {
        this.showError('ドキュメントの読み込み中にエラーが発生しました。');
      }
    }
  }

  renderDocuments(data) {
    const folders = data.folders || [];
    const files = data.files || [];

    if (folders.length === 0 && files.length === 0) {
      this.showEmptyState();
      return;
    }

    this.hideLoading();
    this.hideError();
    this.hideEmptyState();
    this.elements.documentList.classList.remove('d-none');

    if (this.viewMode === 'list') {
      this.renderListView(folders, files);
    } else {
      this.renderGridView(folders, files);
    }
  }

  renderListView(folders, files) {
    this.elements.documentListView.classList.remove('d-none');
    this.elements.documentGrid.classList.add('d-none');

    let html = '';

    // フォルダ
    folders.forEach(folder => {
      html += `
                <tr data-type="folder" data-id="${folder.id}" class="document-item">
                    <td><i class="fas fa-folder text-warning"></i></td>
                    <td>
                        <a href="#" class="text-decoration-none" data-action="open-folder" data-folder-id="${folder.id}">
                            ${this.escapeHtml(folder.name)}
                        </a>
                    </td>
                    <td>-</td>
                    <td>${this.formatDate(folder.updated_at)}</td>
                    <td>${this.escapeHtml(folder.creator?.name || '-')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" data-action="context-menu" data-type="folder" data-id="${folder.id}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </td>
                </tr>
            `;
    });

    // ファイル
    files.forEach(file => {
      html += `
                <tr data-type="file" data-id="${file.id}" class="document-item">
                    <td><i class="fas fa-file-pdf text-danger"></i></td>
                    <td>
                        <a href="/facilities/${this.facilityId}/contract-documents/files/${file.id}/download" class="text-decoration-none">
                            ${this.escapeHtml(file.original_name)}
                        </a>
                    </td>
                    <td>${this.formatFileSize(file.file_size)}</td>
                    <td>${this.formatDate(file.updated_at)}</td>
                    <td>${this.escapeHtml(file.uploader?.name || '-')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" data-action="context-menu" data-type="file" data-id="${file.id}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </td>
                </tr>
            `;
    });

    this.elements.documentListBody.innerHTML = html;
    this.attachDocumentEventListeners();
  }

  renderGridView(folders, files) {
    this.elements.documentListView.classList.add('d-none');
    this.elements.documentGrid.classList.remove('d-none');

    let html = '';

    // フォルダ
    folders.forEach(folder => {
      html += `
                <div class="col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card document-item" data-type="folder" data-id="${folder.id}">
                        <div class="card-body text-center">
                            <i class="fas fa-folder fa-3x text-warning mb-2"></i>
                            <h6 class="card-title">${this.escapeHtml(folder.name)}</h6>
                            <small class="text-muted">${this.formatDate(folder.updated_at)}</small>
                        </div>
                    </div>
                </div>
            `;
    });

    // ファイル
    files.forEach(file => {
      html += `
                <div class="col-md-3 col-sm-4 col-6 mb-3">
                    <div class="card document-item" data-type="file" data-id="${file.id}">
                        <div class="card-body text-center">
                            <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                            <h6 class="card-title">${this.escapeHtml(file.original_name)}</h6>
                            <small class="text-muted">${this.formatFileSize(file.file_size)}</small>
                        </div>
                    </div>
                </div>
            `;
    });

    this.elements.documentGridBody.innerHTML = html;
    this.attachDocumentEventListeners();
  }

  attachDocumentEventListeners() {
    // フォルダを開く
    document.querySelectorAll('[data-action="open-folder"]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const folderId = e.currentTarget.dataset.folderId;
        this.loadDocuments(folderId);
      });
    });

    // コンテキストメニュー
    document.querySelectorAll('[data-action="context-menu"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const type = e.currentTarget.dataset.type;
        const id = e.currentTarget.dataset.id;
        this.showContextMenu(e, type, id);
      });
    });
  }

  updateBreadcrumbs(breadcrumbs) {
    let html = `<li class="breadcrumb-item"><a href="#" data-folder-id="">ルート</a></li>`;

    breadcrumbs.forEach((crumb, index) => {
      if (index === breadcrumbs.length - 1) {
        html += `<li class="breadcrumb-item active">${this.escapeHtml(crumb.name)}</li>`;
      } else {
        html += `<li class="breadcrumb-item"><a href="#" data-folder-id="${crumb.id}">${this.escapeHtml(crumb.name)}</a></li>`;
      }
    });

    this.elements.breadcrumbNav.innerHTML = html;

    // パンくずリンクにイベントリスナーを追加
    this.elements.breadcrumbNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const folderId = e.currentTarget.dataset.folderId || null;
        this.loadDocuments(folderId);
      });
    });
  }

  showCreateFolderModal() {
    if (this.elements.createFolderModal) {
      const modal = new bootstrap.Modal(this.elements.createFolderModal);
      modal.show();
      if (this.elements.folderNameInput) {
        this.elements.folderNameInput.value = '';
        setTimeout(() => this.elements.folderNameInput.focus(), 300);
      }
    }
  }

  showUploadFileModal() {
    if (this.elements.uploadFileModal) {
      const modal = new bootstrap.Modal(this.elements.uploadFileModal);
      modal.show();
      if (this.elements.fileInput) {
        this.elements.fileInput.value = '';
      }
    }
  }

  async handleCreateFolder(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    if (this.currentFolderId) {
      formData.append('parent_id', this.currentFolderId);
    }

    try {
      const response = await fetch(`/facilities/${this.facilityId}/contract-documents/folders`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        bootstrap.Modal.getInstance(this.elements.createFolderModal).hide();
        this.loadDocuments(this.currentFolderId);
        this.showSuccessMessage('フォルダを作成しました。');
      } else {
        alert(result.message || 'フォルダの作成に失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Create folder error:', error);
      alert('フォルダの作成中にエラーが発生しました。');
    }
  }

  async handleUploadFile(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    if (this.currentFolderId) {
      formData.append('folder_id', this.currentFolderId);
    }

    try {
      if (this.elements.uploadProgress) {
        this.elements.uploadProgress.style.display = 'block';
      }

      const response = await fetch(`/facilities/${this.facilityId}/contract-documents/upload`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body: formData,
      });

      const result = await response.json();

      if (this.elements.uploadProgress) {
        this.elements.uploadProgress.style.display = 'none';
      }

      if (result.success) {
        bootstrap.Modal.getInstance(this.elements.uploadFileModal).hide();
        this.loadDocuments(this.currentFolderId);
        this.showSuccessMessage('ファイルをアップロードしました。');
      } else {
        alert(result.message || 'ファイルのアップロードに失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Upload file error:', error);
      if (this.elements.uploadProgress) {
        this.elements.uploadProgress.style.display = 'none';
      }
      alert('ファイルのアップロード中にエラーが発生しました。');
    }
  }

  async handleRename(e) {
    e.preventDefault();

    if (!this.selectedItem) return;

    const newName = this.elements.renameInput.value.trim();
    if (!newName) return;

    const { type, id } = this.selectedItem;
    const endpoint = type === 'folder' ? 'folders' : 'files';

    try {
      const response = await fetch(`/facilities/${this.facilityId}/contract-documents/${endpoint}/${id}/rename`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ name: newName }),
      });

      const result = await response.json();

      if (result.success) {
        bootstrap.Modal.getInstance(this.elements.renameModal).hide();
        this.loadDocuments(this.currentFolderId);
        this.showSuccessMessage('名前を変更しました。');
      } else {
        alert(result.message || '名前の変更に失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Rename error:', error);
      alert('名前の変更中にエラーが発生しました。');
    }
  }

  async handleDelete(type, id) {
    if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
      return;
    }

    const endpoint = type === 'folder' ? 'folders' : 'files';

    try {
      const response = await fetch(`/facilities/${this.facilityId}/contract-documents/${endpoint}/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      const result = await response.json();

      if (result.success) {
        this.loadDocuments(this.currentFolderId);
        this.showSuccessMessage('削除しました。');
      } else {
        alert(result.message || '削除に失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Delete error:', error);
      alert('削除中にエラーが発生しました。');
    }
  }

  showContextMenu(e, type, id) {
    this.selectedItem = { type, id };

    const menu = this.elements.contextMenu;
    if (!menu) return;

    // メニュー項目の表示/非表示
    menu.querySelectorAll('[data-folder-only]').forEach(item => {
      item.style.display = type === 'folder' ? 'block' : 'none';
    });
    menu.querySelectorAll('[data-file-only]').forEach(item => {
      item.style.display = type === 'file' ? 'block' : 'none';
    });

    // メニューを表示
    menu.style.display = 'block';
    menu.style.left = e.pageX + 'px';
    menu.style.top = e.pageY + 'px';

    // メニュー項目のクリックイベント
    menu.querySelectorAll('.context-menu-item').forEach(item => {
      item.onclick = () => {
        const action = item.dataset.action;
        this.handleContextMenuAction(action, type, id);
        this.hideContextMenu();
      };
    });
  }

  hideContextMenu() {
    if (this.elements.contextMenu) {
      this.elements.contextMenu.style.display = 'none';
    }
  }

  handleContextMenuAction(action, type, id) {
    switch (action) {
      case 'open':
        if (type === 'folder') {
          this.loadDocuments(id);
        }
        break;
      case 'rename':
        this.showRenameModal(type, id);
        break;
      case 'delete':
        this.handleDelete(type, id);
        break;
      case 'download':
        if (type === 'file') {
          window.location.href = `/facilities/${this.facilityId}/contract-documents/files/${id}/download`;
        }
        break;
      case 'properties':
        this.showProperties(type, id);
        break;
    }
  }

  showRenameModal(type, id) {
    if (this.elements.renameModal) {
      this.selectedItem = { type, id };
      const modal = new bootstrap.Modal(this.elements.renameModal);
      modal.show();
      if (this.elements.renameInput) {
        this.elements.renameInput.value = '';
        setTimeout(() => this.elements.renameInput.focus(), 300);
      }
    }
  }

  async showProperties(type, id) {
    if (!this.elements.propertiesModal) return;

    try {
      // 現在のドキュメントリストから該当アイテムを検索
      const items = type === 'folder'
        ? document.querySelectorAll('[data-type="folder"]')
        : document.querySelectorAll('[data-type="file"]');

      let itemData = null;
      items.forEach(item => {
        if (item.dataset.id === id.toString()) {
          // アイテムのデータを取得（簡易版）
          const cells = item.querySelectorAll('td');
          if (cells.length > 0) {
            itemData = {
              name: cells[1]?.textContent.trim() || '-',
              size: cells[2]?.textContent.trim() || '-',
              date: cells[3]?.textContent.trim() || '-',
              creator: cells[4]?.textContent.trim() || '-',
            };
          }
        }
      });

      if (!itemData) {
        alert('プロパティ情報の取得に失敗しました。');
        return;
      }

      // プロパティ表示
      let html = '<dl class="row">';
      html += `<dt class="col-sm-4">名前</dt><dd class="col-sm-8">${this.escapeHtml(itemData.name)}</dd>`;
      if (type === 'file') {
        html += `<dt class="col-sm-4">サイズ</dt><dd class="col-sm-8">${this.escapeHtml(itemData.size)}</dd>`;
      }
      html += `<dt class="col-sm-4">更新日時</dt><dd class="col-sm-8">${this.escapeHtml(itemData.date)}</dd>`;
      html += `<dt class="col-sm-4">作成者</dt><dd class="col-sm-8">${this.escapeHtml(itemData.creator)}</dd>`;
      html += '</dl>';

      this.elements.propertiesContent.innerHTML = html;

      const modal = new bootstrap.Modal(this.elements.propertiesModal);
      modal.show();
    } catch (error) {
      console.error('[ContractDoc] Show properties error:', error);
      alert('プロパティの表示中にエラーが発生しました。');
    }
  }

  async handleSearch() {
    const query = this.elements.searchInput.value.trim();
    if (!query) {
      // 検索クエリが空の場合は通常表示に戻る
      this.loadDocuments(this.currentFolderId);
      return;
    }

    try {
      this.showLoading();

      const url = `/facilities/${this.facilityId}/contract-documents`;
      const params = new URLSearchParams();
      params.append('search', query);

      const response = await fetch(`${url}?${params}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        this.renderDocuments(result.data);
        // 検索結果の場合はパンくずリストをクリア
        this.elements.breadcrumbNav.innerHTML = '<li class="breadcrumb-item active">検索結果</li>';
        // 成功時は再試行カウントをリセット
        this.resetRetryState();
      } else {
        this.showError(result.message || '検索に失敗しました。');
      }
    } catch (error) {
      console.error('[ContractDoc] Search error:', error);

      // ネットワークエラーの場合は再試行機能を提供
      if (this.isNetworkError(error)) {
        this.lastFailedRequest = { action: 'search', params: {} };
        this.showNetworkError('検索中にネットワークエラーが発生しました。');
      } else {
        this.showError('検索中にエラーが発生しました。');
      }
    }
  }

  switchViewMode(mode) {
    this.viewMode = mode;
    if (mode === 'list') {
      this.elements.documentListView.classList.remove('d-none');
      this.elements.documentGrid.classList.add('d-none');
    } else {
      this.elements.documentListView.classList.add('d-none');
      this.elements.documentGrid.classList.remove('d-none');
    }
  }

  showLoading() {
    this.elements.loadingIndicator.classList.remove('d-none');
    this.elements.errorMessage.classList.add('d-none');
    this.elements.emptyState.classList.add('d-none');
    this.elements.documentList.classList.add('d-none');
  }

  hideLoading() {
    this.elements.loadingIndicator.classList.add('d-none');
  }

  showError(message) {
    this.hideLoading();
    this.elements.errorText.textContent = message;
    this.elements.errorMessage.classList.remove('d-none');
    this.elements.emptyState.classList.add('d-none');
    this.elements.documentList.classList.add('d-none');

    // 再試行ボタンを非表示
    const retryBtn = this.elements.errorMessage.querySelector('.retry-btn');
    if (retryBtn) {
      retryBtn.style.display = 'none';
    }
  }

  hideError() {
    this.elements.errorMessage.classList.add('d-none');
  }

  /**
   * ネットワークエラー表示（再試行ボタン付き）
   */
  showNetworkError(message) {
    this.hideLoading();
    this.elements.errorText.textContent = message;
    this.elements.errorMessage.classList.remove('d-none');
    this.elements.emptyState.classList.add('d-none');
    this.elements.documentList.classList.add('d-none');

    // 再試行ボタンを表示
    let retryBtn = this.elements.errorMessage.querySelector('.retry-btn');
    if (!retryBtn) {
      retryBtn = document.createElement('button');
      retryBtn.className = 'btn btn-primary btn-sm retry-btn mt-2';
      retryBtn.innerHTML = '<i class="fas fa-redo me-1"></i>再試行';
      retryBtn.addEventListener('click', () => this.handleRetry());
      this.elements.errorMessage.appendChild(retryBtn);
    }

    // 再試行回数に応じてボタンの表示を制御
    if (this.retryCount >= this.maxRetries) {
      retryBtn.disabled = true;
      retryBtn.innerHTML = '<i class="fas fa-times me-1"></i>再試行回数の上限に達しました';
      this.elements.errorText.textContent = message + ' ページを再読み込みしてください。';
    } else {
      retryBtn.disabled = false;
      retryBtn.style.display = 'inline-block';

      if (this.retryCount > 0) {
        const remainingRetries = this.maxRetries - this.retryCount;
        retryBtn.innerHTML = `<i class="fas fa-redo me-1"></i>再試行 (残り${remainingRetries}回)`;
      }
    }
  }

  /**
   * ネットワークエラーかどうかを判定
   */
  isNetworkError(error) {
    // TypeError: Failed to fetch はネットワークエラー
    if (error instanceof TypeError && error.message.includes('fetch')) {
      return true;
    }

    // HTTP 5xx エラーもネットワーク関連として扱う
    if (error.message && error.message.includes('HTTP error! status: 5')) {
      return true;
    }

    // タイムアウトエラー
    if (error.name === 'AbortError' || error.message.includes('timeout')) {
      return true;
    }

    return false;
  }

  /**
   * 再試行処理
   */
  async handleRetry() {
    if (this.retryCount >= this.maxRetries) {
      console.warn('[ContractDoc] Max retry attempts reached');
      return;
    }

    if (!this.lastFailedRequest) {
      console.warn('[ContractDoc] No failed request to retry');
      return;
    }

    this.retryCount++;

    // 指数バックオフ: 1秒 → 2秒 → 4秒
    const delay = this.retryDelay * Math.pow(2, this.retryCount - 1);

    console.log(`[ContractDoc] Retrying request (attempt ${this.retryCount}/${this.maxRetries}) after ${delay}ms`);

    // 遅延後に再試行
    await this.sleep(delay);

    // 最後に失敗したリクエストを再実行
    const { action, params } = this.lastFailedRequest;

    switch (action) {
      case 'loadDocuments':
        await this.loadDocuments(params.folderId);
        break;
      case 'search':
        await this.handleSearch();
        break;
      case 'createFolder':
        // フォルダ作成は再試行しない（ユーザーが再度実行する）
        break;
      case 'uploadFile':
        // ファイルアップロードは再試行しない（ユーザーが再度実行する）
        break;
      default:
        console.warn(`[ContractDoc] Unknown action: ${action}`);
    }
  }

  /**
   * 再試行状態をリセット
   */
  resetRetryState() {
    this.retryCount = 0;
    this.lastFailedRequest = null;
  }

  /**
   * 指定時間待機
   */
  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  showEmptyState() {
    this.hideLoading();
    this.hideError();
    this.elements.emptyState.classList.remove('d-none');
    this.elements.documentList.classList.add('d-none');
  }

  hideEmptyState() {
    this.elements.emptyState.classList.add('d-none');
  }

  showSuccessMessage(message) {
    // 簡易的な成功メッセージ表示
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 3000);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
  }

  formatFileSize(bytes) {
    if (!bytes) return '-';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  }
}

// グローバルに公開
window.ContractDocumentManager = ContractDocumentManager;

// ES6モジュールとしてエクスポート
export default ContractDocumentManager;
