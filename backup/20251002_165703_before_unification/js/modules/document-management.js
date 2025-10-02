/**
 * ドキュメント管理モジュール
 * 
 * 施設のドキュメント管理機能を提供します。
 * - フォルダ作成・削除・名前変更
 * - ファイルアップロード・ダウンロード・削除
 * - ソート・フィルタ・検索機能
 * - リスト・グリッド表示切替
 */

export class DocumentManager {
  constructor(options = {}) {
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    console.log('DocumentManager initialized with:', {
      facilityId: this.facilityId,
      baseUrl: this.baseUrl,
      csrfToken: this.csrfToken ? 'present' : 'missing'
    });
    this.permissions = {
      canCreate: options.canCreate || false,
      canUpdate: options.canUpdate || false,
      canDelete: options.canDelete || false
    };

    // 状態管理
    this.currentFolder = null;
    this.selectedItems = new Set();
    this.viewMode = 'list';
    this.sortBy = 'name';
    this.sortDirection = 'asc';
    this.filterType = 'all';
    this.searchQuery = '';
    this.currentPage = 1;
    this.isLoading = false;

    // DOM要素の参照
    this.elements = {};

    // イベントリスナーの管理
    this.eventListeners = new Map();

    // モーダル管理
    this.activeModals = new Set();

    this.initializeElements();
    this.bindEvents();
    this.setupModalBackdropFix();
  }

  /**
   * DOM要素の初期化
   */
  initializeElements() {
    const elementIds = [
      'document-management-container',
      'create-folder-btn',
      'upload-file-btn',
      'search-input',
      'search-btn',
      'file-type-filter',
      'sort-select',
      'breadcrumb-nav',
      'document-stats',
      'loading-indicator',
      'error-message',
      'empty-state',
      'document-list',
      'list-view-content',
      'grid-view-content',
      'document-table-body',
      'document-grid-container',
      'pagination-container',
      'create-folder-modal',
      'upload-file-modal',
      'properties-modal',
      'context-menu',
      'create-folder-form',
      'upload-file-form',
      'file-input',
      'list-view',
      'grid-view'
    ];

    elementIds.forEach(id => {
      this.elements[id] = document.getElementById(id);
    });
  }

  /**
   * イベントリスナーの設定
   */
  bindEvents() {
    // ツールバーイベント
    this.addEventListenerSafe('create-folder-btn', 'click', () => this.showCreateFolderModal());
    this.addEventListenerSafe('upload-file-btn', 'click', () => this.showUploadFileModal());

    // 検索・フィルター・ソート
    this.addEventListenerSafe('search-input', 'input', this.debounce(() => this.handleSearch(), 300));
    this.addEventListenerSafe('search-btn', 'click', () => this.handleSearch());
    this.addEventListenerSafe('file-type-filter', 'change', () => this.handleFilterChange());
    this.addEventListenerSafe('sort-select', 'change', () => this.handleSortChange());

    // 表示モード切替
    this.addEventListenerSafe('list-view', 'change', () => this.handleViewModeChange('list'));
    this.addEventListenerSafe('grid-view', 'change', () => this.handleViewModeChange('grid'));

    // フォーム送信
    this.addEventListenerSafe('create-folder-form', 'submit', (e) => this.handleCreateFolder(e));
    this.addEventListenerSafe('upload-file-form', 'submit', (e) => this.handleUploadFile(e));

    // ファイル選択
    this.addEventListenerSafe('file-input', 'change', () => this.handleFileSelection());

    // グローバルイベント
    document.addEventListener('click', () => this.hideContextMenu());
    document.addEventListener('contextmenu', (e) => this.handleContextMenu(e));
    document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
  }

  /**
   * 安全なイベントリスナー追加
   */
  addEventListenerSafe(elementId, event, handler) {
    const element = this.elements[elementId];
    if (element) {
      element.addEventListener(event, handler);

      // リスナーの管理
      const key = `${elementId}-${event}`;
      if (this.eventListeners.has(key)) {
        element.removeEventListener(event, this.eventListeners.get(key));
      }
      this.eventListeners.set(key, handler);
    }
  }

  /**
   * 初期化
   */
  async init() {
    try {
      await this.loadFolderContents();
    } catch (error) {
      console.error('Document manager initialization failed:', error);
      this.showError('ドキュメント管理の初期化に失敗しました。');
    }
  }

  /**
   * フォルダ内容の読み込み
   */
  async loadFolderContents(folderId = null, options = {}) {
    if (this.isLoading) return;

    try {
      this.isLoading = true;
      this.showLoading();

      const params = new URLSearchParams({
        sort_by: this.sortBy,
        sort_direction: this.sortDirection,
        filter_type: this.filterType,
        search: this.searchQuery,
        view_mode: this.viewMode,
        page: this.currentPage,
        per_page: 50,
        ...options
      });

      const url = folderId
        ? `${this.baseUrl}/folders/${folderId}?${params}`
        : `${this.baseUrl}?${params}`;

      const response = await this.fetchWithAuth(url);
      const data = await response.json();

      if (data.success) {
        this.currentFolder = data.data.current_folder;
        this.renderFolderContents(data.data);
        this.updateBreadcrumbs(data.data.breadcrumbs);
        this.updateStats(data.data.stats);
        this.updatePagination(data.data.pagination);
      } else {
        throw new Error(data.message || 'データの読み込みに失敗しました');
      }

    } catch (error) {
      console.error('Failed to load folder contents:', error);
      this.showError(error.message || 'フォルダ内容の読み込みに失敗しました');
    } finally {
      this.isLoading = false;
      this.hideLoading();
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
   * フォルダ内容のレンダリング
   */
  renderFolderContents(data) {
    const { folders, files } = data;
    const hasContent = folders.length > 0 || files.length > 0;

    if (!hasContent) {
      this.showEmptyState();
      return;
    }

    this.hideEmptyState();

    if (this.viewMode === 'list') {
      this.renderListView(folders, files);
    } else {
      this.renderGridView(folders, files);
    }
  }

  /**
   * リスト表示のレンダリング
   */
  renderListView(folders, files) {
    const tbody = this.elements['document-table-body'];
    if (!tbody) return;

    tbody.innerHTML = '';

    // フォルダを先に表示
    folders.forEach(folder => {
      const row = this.createFolderRow(folder);
      tbody.appendChild(row);
    });

    // ファイルを表示
    files.forEach(file => {
      const row = this.createFileRow(file);
      tbody.appendChild(row);
    });

    this.elements['list-view-content'].style.display = 'block';
    this.elements['grid-view-content'].style.display = 'none';
  }

  /**
   * グリッド表示のレンダリング
   */
  renderGridView(folders, files) {
    const container = this.elements['document-grid-container'];
    if (!container) return;

    container.innerHTML = '';

    // フォルダを先に表示
    folders.forEach(folder => {
      const card = this.createFolderCard(folder);
      container.appendChild(card);
    });

    // ファイルを表示
    files.forEach(file => {
      const card = this.createFileCard(file);
      container.appendChild(card);
    });

    this.elements['list-view-content'].style.display = 'none';
    this.elements['grid-view-content'].style.display = 'block';
  }

  /**
   * フォルダ行の作成
   */
  createFolderRow(folder) {
    const row = document.createElement('tr');
    row.className = 'document-item';
    row.dataset.type = 'folder';
    row.dataset.id = folder.id;

    row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input item-checkbox" value="${folder.id}">
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <i class="fas fa-folder text-warning me-2 file-icon"></i>
                    <span class="folder-name">${this.escapeHtml(folder.name)}</span>
                </div>
            </td>
            <td><small class="text-muted">—</small></td>
            <td><small class="text-muted">${this.formatDate(folder.updated_at)}</small></td>
            <td><small class="text-muted">${this.escapeHtml(folder.created_by)}</small></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="documentManager.openFolder(${folder.id})">
                        <i class="fas fa-folder-open"></i>
                    </button>
                </div>
            </td>
        `;

    // ダブルクリックでフォルダを開く
    row.addEventListener('dblclick', () => this.openFolder(folder.id));

    return row;
  }

  /**
   * ファイル行の作成
   */
  createFileRow(file) {
    const row = document.createElement('tr');
    row.className = 'document-item';
    row.dataset.type = 'file';
    row.dataset.id = file.id;

    row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input item-checkbox" value="${file.id}">
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <i class="${file.icon} ${file.color} me-2 file-icon"></i>
                    <span class="file-name">${this.escapeHtml(file.name)}</span>
                </div>
            </td>
            <td><small class="text-muted">${file.formatted_size}</small></td>
            <td><small class="text-muted">${this.formatDate(file.updated_at)}</small></td>
            <td><small class="text-muted">${this.escapeHtml(file.uploaded_by)}</small></td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${file.can_preview ? `
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="documentManager.previewFile(${file.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    ` : ''}
                    <a href="${file.download_url}" class="btn btn-outline-primary btn-sm" download>
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </td>
        `;

    return row;
  }

  /**
   * フォルダカードの作成
   */
  createFolderCard(folder) {
    const col = document.createElement('div');
    col.className = 'col-md-2 col-sm-3 col-4 mb-3';

    col.innerHTML = `
            <div class="document-card" data-type="folder" data-id="${folder.id}">
                <div class="document-icon">
                    <i class="fas fa-folder text-warning"></i>
                </div>
                <div class="document-name" title="${this.escapeHtml(folder.name)}">
                    ${this.escapeHtml(this.truncateText(folder.name, 20))}
                </div>
            </div>
        `;

    // ダブルクリックでフォルダを開く
    col.querySelector('.document-card').addEventListener('dblclick', () => this.openFolder(folder.id));

    return col;
  }

  /**
   * ファイルカードの作成
   */
  createFileCard(file) {
    const col = document.createElement('div');
    col.className = 'col-md-2 col-sm-3 col-4 mb-3';

    col.innerHTML = `
            <div class="document-card" data-type="file" data-id="${file.id}">
                <div class="document-icon">
                    <i class="${file.icon} ${file.color}"></i>
                </div>
                <div class="document-name" title="${this.escapeHtml(file.name)}">
                    ${this.escapeHtml(this.truncateText(file.name, 20))}
                </div>
                <div class="document-size">
                    <small class="text-muted">${file.formatted_size}</small>
                </div>
            </div>
        `;

    return col;
  }

  /**
   * パンくずナビゲーションの更新
   */
  updateBreadcrumbs(breadcrumbs) {
    const nav = this.elements['breadcrumb-nav'];
    if (!nav) return;

    nav.innerHTML = '';

    breadcrumbs.forEach((crumb, index) => {
      const li = document.createElement('li');
      li.className = 'breadcrumb-item';

      if (crumb.is_current) {
        li.classList.add('active');
        li.textContent = crumb.name;
      } else {
        const link = document.createElement('a');
        link.href = '#';
        link.className = 'breadcrumb-link';
        link.dataset.folderId = crumb.id || '';
        link.innerHTML = index === 0 ? `<i class="fas fa-home me-1"></i>${crumb.name}` : crumb.name;
        link.addEventListener('click', (e) => {
          e.preventDefault();
          this.openFolder(crumb.id);
        });
        li.appendChild(link);
      }

      nav.appendChild(li);
    });
  }

  /**
   * 統計情報の更新
   */
  updateStats(stats) {
    if (!stats) return;

    const folderCount = document.getElementById('folder-count');
    const fileCount = document.getElementById('file-count');
    const totalSize = document.getElementById('total-size');

    if (folderCount) folderCount.textContent = stats.folder_count || 0;
    if (fileCount) fileCount.textContent = stats.file_count || 0;
    if (totalSize) totalSize.textContent = stats.formatted_size || '0 B';

    if (this.elements['document-stats']) {
      this.elements['document-stats'].style.display = 'block';
    }
  }

  /**
   * ページネーションの更新
   */
  updatePagination(pagination) {
    const container = this.elements['pagination-container'];
    if (!container || !pagination || pagination.last_page <= 1) {
      container.innerHTML = '';
      return;
    }

    // 簡単なページネーション実装
    let paginationHtml = '<nav><ul class="pagination pagination-sm justify-content-center">';

    // 前のページ
    if (pagination.current_page > 1) {
      paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">前へ</a>
                </li>
            `;
    }

    // ページ番号
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
      const isActive = i === pagination.current_page;
      paginationHtml += `
                <li class="page-item ${isActive ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
    }

    // 次のページ
    if (pagination.current_page < pagination.last_page) {
      paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">次へ</a>
                </li>
            `;
    }

    paginationHtml += '</ul></nav>';
    container.innerHTML = paginationHtml;

    // ページネーションのクリックイベント
    container.addEventListener('click', (e) => {
      if (e.target.classList.contains('page-link')) {
        e.preventDefault();
        const page = parseInt(e.target.dataset.page);
        if (page && page !== this.currentPage) {
          this.currentPage = page;
          this.loadFolderContents(this.currentFolder);
        }
      }
    });
  }

  /**
   * フォルダを開く
   */
  openFolder(folderId) {
    this.currentFolder = folderId;
    this.currentPage = 1;
    this.loadFolderContents(folderId);
  }

  /**
   * フォルダ作成モーダルの表示
   */
  showCreateFolderModal() {
    const parentFolderIdInput = document.getElementById('parent-folder-id');
    const folderNameInput = document.getElementById('folder-name');

    if (parentFolderIdInput) {
      parentFolderIdInput.value = this.currentFolder || '';
    }
    if (folderNameInput) {
      folderNameInput.value = '';
    }

    const modal = this.elements['create-folder-modal'];
    if (modal && window.bootstrap) {
      try {
        // Bootstrap Modal APIを使用
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modalInstance.show();
      } catch (error) {
        console.error('Failed to show create folder modal:', error);
        alert('フォルダ作成モーダルの表示に失敗しました。ページを再読み込みしてください。');
      }
    }
  }

  /**
   * ファイルアップロードモーダルの表示
   */
  showUploadFileModal() {
    const uploadFolderIdInput = document.getElementById('upload-folder-id');
    const fileInput = this.elements['file-input'];
    const fileList = document.getElementById('file-list');

    if (uploadFolderIdInput) {
      uploadFolderIdInput.value = this.currentFolder || '';
    }
    if (fileInput) {
      fileInput.value = '';
    }
    if (fileList) {
      fileList.style.display = 'none';
    }

    const modal = this.elements['upload-file-modal'];
    if (modal && window.bootstrap) {
      try {
        // Bootstrap Modal APIを使用
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
        modalInstance.show();
      } catch (error) {
        console.error('Failed to show upload file modal:', error);
        alert('ファイルアップロードモーダルの表示に失敗しました。ページを再読み込みしてください。');
      }
    }
  }

  /**
   * フォルダ作成の処理
   */
  async handleCreateFolder(e) {
    e.preventDefault();

    try {
      const formData = new FormData(this.elements['create-folder-form']);

      // デバッグ情報
      const requestUrl = `${this.baseUrl}/folders`;
      console.log('Creating folder with data:', {
        name: formData.get('name'),
        parent_id: formData.get('parent_id'),
        baseUrl: this.baseUrl,
        facilityId: this.facilityId,
        requestUrl: requestUrl
      });

      const response = await this.fetchWithAuth(requestUrl, {
        method: 'POST',
        body: formData
      });

      console.log('Create folder response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Create folder error response:', errorText);
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      console.log('Create folder response data:', data);

      if (data.success) {
        // モーダルを閉じる
        const modal = this.elements['create-folder-modal'];
        if (modal) {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          if (modalInstance) {
            modalInstance.hide();
          }
        }

        this.showSuccess(data.message);
        await this.loadFolderContents(this.currentFolder);
      } else {
        this.showError(data.message || 'フォルダの作成に失敗しました');
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
      const formData = new FormData(this.elements['upload-file-form']);

      // プログレス表示
      const progressElement = document.getElementById('upload-progress');
      const submitBtn = document.getElementById('upload-submit-btn');

      if (progressElement) progressElement.style.display = 'block';
      if (submitBtn) submitBtn.disabled = true;

      const response = await this.fetchWithAuth(`${this.baseUrl}/files`, {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        // モーダルを閉じる
        const modal = this.elements['upload-file-modal'];
        if (modal) {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          if (modalInstance) {
            modalInstance.hide();
          }
        }

        this.showSuccess(data.message);
        this.loadFolderContents(this.currentFolder);
      } else {
        this.showError(data.message || 'ファイルのアップロードに失敗しました');
      }

    } catch (error) {
      console.error('Failed to upload file:', error);
      this.showError('ファイルのアップロードに失敗しました: ' + error.message);
    } finally {
      const progressElement = document.getElementById('upload-progress');
      const submitBtn = document.getElementById('upload-submit-btn');

      if (progressElement) progressElement.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  /**
   * ファイル選択の処理
   */
  handleFileSelection() {
    const fileInput = this.elements['file-input'];
    const fileList = document.getElementById('file-list');
    const selectedFiles = document.getElementById('selected-files');

    if (!fileInput || !fileList || !selectedFiles) return;

    const files = fileInput.files;

    if (files.length > 0) {
      selectedFiles.innerHTML = '';

      Array.from(files).forEach((file) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'selected-file-item';
        fileItem.innerHTML = `
                    <div>
                        <i class="fas fa-file me-2"></i>
                        <span>${this.escapeHtml(file.name)}</span>
                        <small class="text-muted ms-2">(${this.formatFileSize(file.size)})</small>
                    </div>
                `;
        selectedFiles.appendChild(fileItem);
      });

      fileList.style.display = 'block';
    } else {
      fileList.style.display = 'none';
    }
  }

  /**
   * 検索の処理
   */
  handleSearch() {
    const searchInput = this.elements['search-input'];
    if (searchInput) {
      this.searchQuery = searchInput.value.trim();
      this.currentPage = 1;
      this.loadFolderContents(this.currentFolder);
    }
  }

  /**
   * フィルター変更の処理
   */
  handleFilterChange() {
    const filterSelect = this.elements['file-type-filter'];
    if (filterSelect) {
      this.filterType = filterSelect.value;
      this.currentPage = 1;
      this.loadFolderContents(this.currentFolder);
    }
  }

  /**
   * ソート変更の処理
   */
  handleSortChange() {
    const sortSelect = this.elements['sort-select'];
    if (sortSelect) {
      const [sortBy, direction] = sortSelect.value.split('-');
      this.sortBy = sortBy;
      this.sortDirection = direction;
      this.currentPage = 1;
      this.loadFolderContents(this.currentFolder);
    }
  }

  /**
   * 表示モード変更の処理
   */
  handleViewModeChange(mode) {
    this.viewMode = mode;
    this.loadFolderContents(this.currentFolder);
  }

  /**
   * コンテキストメニューの処理
   */
  handleContextMenu(e) {
    // 実装は省略（必要に応じて追加）
  }

  /**
   * キーボードショートカットの処理
   */
  handleKeyboardShortcuts(e) {
    // 実装は省略（必要に応じて追加）
  }

  /**
   * コンテキストメニューを隠す
   */
  hideContextMenu() {
    const contextMenu = this.elements['context-menu'];
    if (contextMenu) {
      contextMenu.style.display = 'none';
    }
  }

  /**
   * ローディング表示
   */
  showLoading() {
    const loading = this.elements['loading-indicator'];
    const documentList = this.elements['document-list'];
    const errorMessage = this.elements['error-message'];
    const emptyState = this.elements['empty-state'];

    if (loading) loading.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
  }

  /**
   * ローディング非表示
   */
  hideLoading() {
    const loading = this.elements['loading-indicator'];
    const documentList = this.elements['document-list'];

    if (loading) loading.style.display = 'none';
    if (documentList) documentList.style.display = 'block';
  }

  /**
   * エラー表示
   */
  showError(message) {
    const errorText = document.getElementById('error-text');
    const errorMessage = this.elements['error-message'];
    const documentList = this.elements['document-list'];
    const emptyState = this.elements['empty-state'];

    if (errorText) errorText.textContent = message;
    if (errorMessage) errorMessage.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
  }

  /**
   * 空の状態表示
   */
  showEmptyState() {
    const emptyState = this.elements['empty-state'];
    const documentList = this.elements['document-list'];
    const errorMessage = this.elements['error-message'];

    if (emptyState) emptyState.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
  }

  /**
   * 空の状態非表示
   */
  hideEmptyState() {
    const emptyState = this.elements['empty-state'];
    if (emptyState) emptyState.style.display = 'none';
  }

  /**
   * 成功メッセージ表示
   */
  showSuccess(message) {
    if (window.showToast) {
      window.showToast(message, 'success');
    } else if (window.Swal) {
      window.Swal.fire({
        icon: 'success',
        title: '成功',
        text: message,
        timer: 3000,
        showConfirmButton: false
      });
    } else {
      alert(message);
    }
  }

  /**
   * ファイルプレビュー
   */
  async previewFile(fileId) {
    try {
      const url = `${this.baseUrl}/files/${fileId}/preview`;
      window.open(url, '_blank');
    } catch (error) {
      console.error('Failed to preview file:', error);
      this.showError('ファイルのプレビューに失敗しました');
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
   * ファイルサイズフォーマット
   */
  formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * テキスト切り詰め
   */
  truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  }

  /**
   * モーダルバックドロップ問題の根本的解決
   */
  setupModalBackdropFix() {
    // 緊急時のバックドロップ削除ボタンを追加
    this.createEmergencyBackdropRemover();

    // モーダルイベントの監視
    document.addEventListener('show.bs.modal', (e) => {
      this.activeModals.add(e.target.id);
      console.log('Modal opened:', e.target.id);
    });

    document.addEventListener('hide.bs.modal', (e) => {
      this.activeModals.delete(e.target.id);
      console.log('Modal closed:', e.target.id);

      // モーダルが閉じた後にバックドロップをチェック
      setTimeout(() => {
        this.cleanupOrphanedBackdrops();
      }, 300);
    });

    // ページ離脱時のクリーンアップ
    window.addEventListener('beforeunload', () => {
      this.forceCleanupAllModals();
    });

    // 定期的なバックドロップチェック
    setInterval(() => {
      this.checkAndCleanBackdrops();
    }, 5000);
  }

  /**
   * 緊急時のバックドロップ削除ボタンを作成
   */
  createEmergencyBackdropRemover() {
    const existingButton = document.getElementById('emergency-backdrop-remover');
    if (existingButton) return;

    const button = document.createElement('button');
    button.id = 'emergency-backdrop-remover';
    button.className = 'force-remove-backdrop';
    button.innerHTML = '🚨 バックドロップ削除';
    button.title = 'モーダルバックドロップが残っている場合にクリックしてください';
    button.style.display = 'none';

    button.addEventListener('click', () => {
      this.forceCleanupAllModals();
      button.style.display = 'none';
    });

    document.body.appendChild(button);

    // バックドロップが検出されたらボタンを表示
    const observer = new MutationObserver(() => {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      if (backdrops.length > 0 && this.activeModals.size === 0) {
        button.style.display = 'block';
      } else {
        button.style.display = 'none';
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class']
    });
  }

  /**
   * 孤立したバックドロップをクリーンアップ
   */
  cleanupOrphanedBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const visibleModals = document.querySelectorAll('.modal.show');

    console.log('Cleanup check - Backdrops:', backdrops.length, 'Visible modals:', visibleModals.length);

    if (backdrops.length > 0 && visibleModals.length === 0) {
      console.log('Removing orphaned backdrops');
      backdrops.forEach(backdrop => backdrop.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }
  }

  /**
   * 定期的なバックドロップチェック
   */
  checkAndCleanBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const visibleModals = document.querySelectorAll('.modal.show');

    if (backdrops.length > visibleModals.length) {
      console.log('Detected excess backdrops, cleaning up');
      this.cleanupOrphanedBackdrops();
    }
  }

  /**
   * すべてのモーダルとバックドロップを強制クリーンアップ
   */
  forceCleanupAllModals() {
    console.log('Force cleanup all modals and backdrops');

    // すべてのモーダルを閉じる
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
      modal.classList.remove('show');
      modal.style.display = 'none';
    });

    // すべてのバックドロップを削除
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());

    // body クラスとスタイルをリセット
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    // アクティブモーダルリストをクリア
    this.activeModals.clear();

    console.log('Force cleanup completed');
  }

  /**
   * 安全なモーダル表示
   */
  safeShowModal(modalElement, options = {}) {
    try {
      // 既存のバックドロップをクリーンアップ
      this.cleanupOrphanedBackdrops();

      // モーダルインスタンスを取得または作成
      let modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modalElement, {
          backdrop: 'static',
          keyboard: true,
          ...options
        });
      }

      // モーダルを表示
      modalInstance.show();

      // 表示後のチェック
      setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
          console.log('Multiple backdrops detected, cleaning up');
          for (let i = 1; i < backdrops.length; i++) {
            backdrops[i].remove();
          }
        }
      }, 100);

      return modalInstance;
    } catch (error) {
      console.error('Error showing modal:', error);
      this.forceCleanupAllModals();
      throw error;
    }
  }

  /**
   * 安全なモーダル非表示
   */
  safeHideModal(modalElement) {
    try {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }

      // 非表示後のクリーンアップ
      setTimeout(() => {
        this.cleanupOrphanedBackdrops();
      }, 300);
    } catch (error) {
      console.error('Error hiding modal:', error);
      this.forceCleanupAllModals();
    }
  }

  /**
   * クリーンアップ
   */
  destroy() {
    // モーダルのクリーンアップ
    this.forceCleanupAllModals();

    // 緊急ボタンの削除
    const emergencyButton = document.getElementById('emergency-backdrop-remover');
    if (emergencyButton) {
      emergencyButton.remove();
    }

    // イベントリスナーの削除
    this.eventListeners.forEach((handler, key) => {
      const [elementId, event] = key.split('-');
      const element = this.elements[elementId];
      if (element) {
        element.removeEventListener(event, handler);
      }
    });

    this.eventListeners.clear();
  }
}

// デフォルトエクスポート
export default DocumentManager;