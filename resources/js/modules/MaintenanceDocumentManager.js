/**
 * 修繕履歴ドキュメント管理クラス
 * 
 * 修繕履歴の各カテゴリ（外装、内装、その他）に対応したドキュメント管理機能を提供
 */
class MaintenanceDocumentManager {
  constructor(facilityId, category) {
    this.facilityId = facilityId;
    this.category = category;
    this.currentFolderId = null;
    this.currentPath = [];
    this.viewMode = 'list';
    this.selectedItem = null;

    // グローバルに登録
    const managerKey = `maintenanceDocManager_${category}`;
    window[managerKey] = this;

    console.log(`[MaintenanceDoc] Initializing manager for category: ${category}`);

    this.init();
  }

  init() {
    this.cacheElements();
    this.attachEventListeners();
    this.loadDocuments();
  }

  cacheElements() {
    const cat = this.category;

    this.elements = {
      container: document.getElementById(`document-management-container-${cat}`),
      createFolderBtn: document.getElementById(`create-folder-btn-${cat}`),
      uploadFileBtn: document.getElementById(`upload-file-btn-${cat}`),
      emptyUploadBtn: document.getElementById(`empty-upload-btn-${cat}`),
      searchInput: document.getElementById(`search-input-${cat}`),
      searchBtn: document.getElementById(`search-btn-${cat}`),
      listViewRadio: document.getElementById(`list-view-${cat}`),
      gridViewRadio: document.getElementById(`grid-view-${cat}`),
      breadcrumbNav: document.getElementById(`breadcrumb-nav-${cat}`),
      loadingIndicator: document.getElementById(`loading-indicator-${cat}`),
      errorMessage: document.getElementById(`error-message-${cat}`),
      errorText: document.getElementById(`error-text-${cat}`),
      emptyState: document.getElementById(`empty-state-${cat}`),
      documentList: document.getElementById(`document-list-${cat}`),
      documentListView: document.getElementById(`document-list-view-${cat}`),
      documentListBody: document.getElementById(`document-list-body-${cat}`),
      documentGrid: document.getElementById(`document-grid-${cat}`),
      documentGridBody: document.getElementById(`document-grid-body-${cat}`),
      createFolderModal: document.getElementById(`create-folder-modal-${cat}`),
      createFolderForm: document.getElementById(`create-folder-form-${cat}`),
      folderNameInput: document.getElementById(`folder-name-${cat}`),
      uploadFileModal: document.getElementById(`upload-file-modal-${cat}`),
      uploadFileForm: document.getElementById(`upload-file-form-${cat}`),
      fileInput: document.getElementById(`file-input-${cat}`),
      uploadProgress: document.getElementById(`upload-progress-${cat}`),
      contextMenu: document.getElementById(`context-menu-${cat}`),
      renameModal: document.getElementById(`rename-modal-${cat}`),
      renameForm: document.getElementById(`rename-form-${cat}`),
      renameInput: document.getElementById(`rename-input-${cat}`),
      propertiesModal: document.getElementById(`properties-modal-${cat}`),
      propertiesContent: document.getElementById(`properties-content-${cat}`),
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
      if (e.target.closest(`#document-list-body-${this.category}`) ||
        e.target.closest(`#document-grid-body-${this.category}`)) {
        e.preventDefault();
      }
    });
  }

  async loadDocuments(folderId = null) {
    try {
      this.showLoading();

      const url = `/facilities/${this.facilityId}/maintenance-documents/${this.category}`;
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
      } else {
        this.showError(result.message || 'ドキュメントの読み込みに失敗しました。');
      }
    } catch (error) {
      console.error('[MaintenanceDoc] Load documents error:', error);
      this.showError('ドキュメントの読み込み中にエラーが発生しました。');
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
                        <a href="/facilities/${this.facilityId}/maintenance-documents/${this.category}/files/${file.id}/download" class="text-decoration-none">
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
      const response = await fetch(`/facilities/${this.facilityId}/maintenance-documents/${this.category}/folders`, {
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
      console.error('[MaintenanceDoc] Create folder error:', error);
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

      const response = await fetch(`/facilities/${this.facilityId}/maintenance-documents/${this.category}/upload`, {
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
      console.error('[MaintenanceDoc] Upload file error:', error);
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
      const response = await fetch(`/facilities/${this.facilityId}/maintenance-documents/${this.category}/${endpoint}/${id}`, {
        method: 'PUT',
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
      console.error('[MaintenanceDoc] Rename error:', error);
      alert('名前の変更中にエラーが発生しました。');
    }
  }

  async handleDelete(type, id) {
    if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
      return;
    }

    const endpoint = type === 'folder' ? 'folders' : 'files';

    try {
      const response = await fetch(`/facilities/${this.facilityId}/maintenance-documents/${this.category}/${endpoint}/${id}`, {
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
      console.error('[MaintenanceDoc] Delete error:', error);
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
          window.location.href = `/facilities/${this.facilityId}/maintenance-documents/${this.category}/files/${id}/download`;
        }
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

  handleSearch() {
    const query = this.elements.searchInput.value.trim();
    if (query) {
      console.log('[MaintenanceDoc] Search:', query);
      // 検索機能は今後実装
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
  }

  hideError() {
    this.elements.errorMessage.classList.add('d-none');
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
window.MaintenanceDocumentManager = MaintenanceDocumentManager;

// ES6モジュールとしてエクスポート
export default MaintenanceDocumentManager;
