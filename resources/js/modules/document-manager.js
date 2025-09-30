/**
 * Document Manager Module
 * ドキュメント管理メインモジュール
 * 
 * Main controller for document management functionality including folder navigation, 
 * file operations, and view management.
 * フォルダナビゲーション、ファイル操作、およびビュー管理を含む
 * ドキュメント管理機能のメインコントローラー
 */

import { DocumentFileManager } from './document-file-manager.js';
import { DocumentUploadManager } from './document-upload.js';
import DocumentErrorHandler from './document-error-handler.js';

export class DocumentManager {
  constructor(facilityId, options = {}) {
    this.facilityId = facilityId;
    this.currentFolderId = null;
    this.currentPath = [];
    this.viewMode = 'list'; // 'list' or 'icon'
    this.sortBy = 'name'; // 'name' or 'date'
    this.sortOrder = 'asc'; // 'asc' or 'desc'

    this.options = {
      autoRefresh: true,
      refreshInterval: 30000, // 30 seconds
      enableKeyboardShortcuts: true,
      enableContextMenu: true,
      enableVirtualScrolling: true,
      virtualScrollThreshold: 100, // Enable virtual scrolling when items > 100
      itemsPerPage: 50,
      enableLazyLoading: true,
      cacheTimeout: 300000, // 5 minutes
      preloadNextPage: true,
      ...options
    };

    // Sub-managers
    this.fileManager = null;
    this.uploadManager = null;

    // State management
    this.isLoading = false;
    this.lastRefresh = null;

    // Performance optimization
    this.cache = new Map();
    this.virtualScrollContainer = null;
    this.virtualScrollData = {
      items: [],
      visibleStart: 0,
      visibleEnd: 0,
      itemHeight: 50,
      containerHeight: 0,
      totalHeight: 0
    };

    // Pagination state
    this.currentPage = 1;
    this.totalPages = 1;
    this.totalItems = 0;
    this.hasMorePages = false;

    // Lazy loading
    this.intersectionObserver = null;
    this.loadingMoreItems = false;

    // Debounced functions
    this.debouncedSearch = this.debounce(this.performSearch.bind(this), 300);
    this.debouncedResize = this.debounce(this.handleResize.bind(this), 100);

    this.init();
  }

  init() {
    this.loadUserPreferences();
    this.setupSubManagers();
    this.setupEventListeners();
    this.setupKeyboardShortcuts();
    this.setupViewControls();
    this.setupSortControls();
    this.setupModals();
    this.setupVirtualScrolling();
    this.setupLazyLoading();
    this.setupPerformanceOptimizations();
    this.loadInitialContent();

    if (this.options.autoRefresh) {
      this.startAutoRefresh();
    }
  }

  setupSubManagers() {
    // Initialize file manager
    this.fileManager = new DocumentFileManager(this.facilityId, {
      enableContextMenu: this.options.enableContextMenu
    });

    // Initialize upload manager
    this.uploadManager = new DocumentUploadManager(this.facilityId);

    // Make managers available globally for compatibility
    window.documentFileManager = this.fileManager;
    window.documentUpload = this.uploadManager;
  }

  setupEventListeners() {
    // Folder navigation events
    document.addEventListener('folderChanged', (e) => {
      this.currentFolderId = e.detail.folderId;
      this.currentPath = e.detail.path || [];
    });

    // Refresh events
    document.addEventListener('refreshFolder', () => {
      this.refreshCurrentFolder();
    });

    // View mode changes
    document.addEventListener('viewModeChanged', (e) => {
      this.handleViewModeChange(e.detail.mode);
    });

    // Sort changes
    document.addEventListener('sortChanged', (e) => {
      this.handleSortChange(e.detail.sortBy, e.detail.sortOrder);
    });

    // Breadcrumb navigation
    document.addEventListener('click', (e) => {
      if (e.target.closest('.breadcrumb-item a')) {
        e.preventDefault();
        const folderId = e.target.closest('a').dataset.folderId;
        this.navigateToFolder(folderId ? parseInt(folderId) : null);
      }
    });

    // Folder double-click navigation
    document.addEventListener('dblclick', (e) => {
      const folderElement = e.target.closest('.folder-row, .folder-card');
      if (folderElement) {
        const folderId = folderElement.dataset.folderId;
        if (folderId) {
          this.navigateToFolder(parseInt(folderId));
        }
      }
    });

    // Window focus event for refresh
    window.addEventListener('focus', () => {
      if (this.lastRefresh && Date.now() - this.lastRefresh > 60000) { // 1 minute
        this.refreshCurrentFolder();
      }
    });
  }

  setupKeyboardShortcuts() {
    if (!this.options.enableKeyboardShortcuts) return;

    document.addEventListener('keydown', (e) => {
      // Only handle shortcuts when not focused on input fields
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
      }

      switch (e.key) {
        case 'F5':
          e.preventDefault();
          this.refreshCurrentFolder();
          break;

        case 'Backspace':
          e.preventDefault();
          this.navigateUp();
          break;

        case 'n':
          if (e.ctrlKey) {
            e.preventDefault();
            this.showCreateFolderModal();
          }
          break;

        case 'u':
          if (e.ctrlKey) {
            e.preventDefault();
            this.showUploadModal();
          }
          break;

        case '1':
          if (e.ctrlKey) {
            e.preventDefault();
            this.setViewMode('list');
          }
          break;

        case '2':
          if (e.ctrlKey) {
            e.preventDefault();
            this.setViewMode('icon');
          }
          break;
      }
    });
  }

  setupViewControls() {
    const viewButtons = document.querySelectorAll('[data-view-mode]');
    viewButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const mode = button.dataset.viewMode;
        this.setViewMode(mode);
      });
    });
  }

  setupSortControls() {
    const sortButtons = document.querySelectorAll('[data-sort-by]');
    sortButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const sortBy = button.dataset.sortBy;
        const currentOrder = this.sortBy === sortBy ? this.sortOrder : 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        this.setSortOrder(sortBy, newOrder);
      });
    });
  }

  setupModals() {
    // Create folder modal
    this.setupCreateFolderModal();

    // Rename folder modal
    this.setupRenameFolderModal();

    // Delete confirmation modal
    this.setupDeleteConfirmModal();

    // Upload modal is handled by DocumentUploadManager
  }

  setupCreateFolderModal() {
    const createBtn = document.getElementById('createFolderBtn');
    if (createBtn) {
      createBtn.addEventListener('click', () => {
        this.showCreateFolderModal();
      });
    }

    // Handle form submission
    const form = document.getElementById('createFolderForm');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleCreateFolder();
      });
    }
  }

  setupRenameFolderModal() {
    const form = document.getElementById('renameFolderForm');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleRenameFolder();
      });
    }
  }

  setupDeleteConfirmModal() {
    const confirmBtn = document.getElementById('deleteConfirmBtn');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        this.handleDeleteConfirm();
      });
    }
  }

  async loadInitialContent() {
    const urlParams = new URLSearchParams(window.location.search);
    const folderId = urlParams.get('folder');

    if (folderId) {
      await this.navigateToFolder(parseInt(folderId));
    } else {
      await this.navigateToFolder(null); // Root folder
    }
  }

  async navigateToFolder(folderId) {
    if (this.isLoading) return;

    this.isLoading = true;
    this.showLoadingState();

    try {
      const response = await this.fetchFolderContents(folderId);

      if (response.success) {
        this.currentFolderId = folderId;
        this.currentPath = response.breadcrumbs || [];

        this.renderFolderContents(response.data);
        this.updateBreadcrumbs(response.breadcrumbs);
        this.updateURL(folderId);
        this.updateViewState();

        // Notify other components
        document.dispatchEvent(new CustomEvent('folderChanged', {
          detail: { folderId, path: this.currentPath }
        }));

        this.lastRefresh = Date.now();
      } else {
        this.showError(response.message || 'フォルダの読み込みに失敗しました。');
      }
    } catch (error) {
      DocumentErrorHandler.handleError(error, {
        operation: 'navigate_to_folder',
        facilityId: this.facilityId,
        folderId: folderId,
        retryCallback: () => this.navigateToFolder(folderId, updateHistory)
      });
    } finally {
      this.isLoading = false;
      this.hideLoadingState();
    }
  }

  // This method is now defined in the performance optimization section

  renderFolderContents(data) {
    const container = document.getElementById('documentList');
    if (!container) return;

    if (this.viewMode === 'list') {
      this.renderListView(container, data);
    } else {
      this.renderIconView(container, data);
    }

    // Trigger content loaded event for sub-managers
    document.dispatchEvent(new CustomEvent('folderContentLoaded'));
  }

  renderListView(container, data) {
    const { folders = [], files = [] } = data;

    let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>名前</th>
                            <th>種類</th>
                            <th>サイズ</th>
                            <th>更新日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

    // Render folders
    folders.forEach(folder => {
      html += this.renderFolderRow(folder);
    });

    // Render files
    files.forEach(file => {
      html += this.renderFileRow(file);
    });

    html += `
                    </tbody>
                </table>
            </div>
        `;

    if (folders.length === 0 && files.length === 0) {
      html = this.renderEmptyState();
    }

    container.innerHTML = html;
  }

  renderIconView(container, data) {
    const { folders = [], files = [] } = data;

    let html = '<div class="row g-3">';

    // Render folders
    folders.forEach(folder => {
      html += this.renderFolderCard(folder);
    });

    // Render files
    files.forEach(file => {
      html += this.renderFileCard(file);
    });

    html += '</div>';

    if (folders.length === 0 && files.length === 0) {
      html = this.renderEmptyState();
    }

    container.innerHTML = html;
  }

  renderFolderRow(folder) {
    return `
            <tr class="folder-row" data-folder-id="${folder.id}" tabindex="0">
                <td>
                    <i class="fas fa-folder text-warning me-2"></i>
                    <span class="folder-name">${this.escapeHtml(folder.name)}</span>
                </td>
                <td>フォルダ</td>
                <td>-</td>
                <td>${this.formatDate(folder.updated_at)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="window.documentManager.navigateToFolder(${folder.id})">
                        <i class="fas fa-folder-open"></i>
                    </button>
                </td>
            </tr>
        `;
  }

  renderFileRow(file) {
    return `
            <tr class="file-row" data-file-id="${file.id}" tabindex="0">
                <td>
                    <i class="${file.icon} ${file.color} me-2"></i>
                    <span class="file-name" title="${this.escapeHtml(file.original_name)}">${this.escapeHtml(file.display_name)}</span>
                </td>
                <td>${file.extension.toUpperCase()}</td>
                <td>${file.formatted_size}</td>
                <td>${this.formatDate(file.updated_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="${file.download_url}" class="btn btn-outline-primary" title="ダウンロード">
                            <i class="fas fa-download"></i>
                        </a>
                        ${file.can_preview ? `
                            <button class="btn btn-outline-info" onclick="window.documentManager.previewFile(${file.id})" title="プレビュー">
                                <i class="fas fa-eye"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
  }

  renderFolderCard(folder) {
    return `
            <div class="col-md-3 col-sm-4 col-6">
                <div class="card folder-card" data-folder-id="${folder.id}" tabindex="0">
                    <div class="card-body text-center">
                        <i class="fas fa-folder fa-3x text-warning mb-2"></i>
                        <h6 class="card-title folder-name" title="${this.escapeHtml(folder.name)}">${this.escapeHtml(folder.display_name)}</h6>
                        <small class="text-muted">${folder.file_count}個のファイル</small>
                    </div>
                </div>
            </div>
        `;
  }

  renderFileCard(file) {
    return `
            <div class="col-md-3 col-sm-4 col-6">
                <div class="card file-card" data-file-id="${file.id}" tabindex="0">
                    <div class="card-body text-center">
                        <i class="${file.icon} fa-3x ${file.color} mb-2"></i>
                        <h6 class="card-title file-name" title="${this.escapeHtml(file.original_name)}">${this.escapeHtml(file.display_name)}</h6>
                        <small class="text-muted">${file.formatted_size}</small>
                        <div class="mt-2">
                            <div class="btn-group btn-group-sm">
                                <a href="${file.download_url}" class="btn btn-outline-primary" title="ダウンロード">
                                    <i class="fas fa-download"></i>
                                </a>
                                ${file.can_preview ? `
                                    <button class="btn btn-outline-info" onclick="window.documentManager.previewFile(${file.id})" title="プレビュー">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  renderEmptyState() {
    return `
            <div class="empty-state text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">このフォルダは空です</h5>
                <p class="text-muted">ファイルをアップロードするか、新しいフォルダを作成できます。</p>
                <div class="mt-3">
                    <button class="btn btn-primary me-2" onclick="window.documentManager.showUploadModal()">
                        <i class="fas fa-upload me-1"></i>ファイルアップロード
                    </button>
                    <button class="btn btn-outline-primary" onclick="window.documentManager.showCreateFolderModal()">
                        <i class="fas fa-folder-plus me-1"></i>フォルダ作成
                    </button>
                </div>
            </div>
        `;
  }

  updateBreadcrumbs(breadcrumbs) {
    const container = document.getElementById('breadcrumbNav');
    if (!container || !breadcrumbs) return;

    let html = `
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#" data-folder-id="">
                            <i class="fas fa-home me-1"></i>ルート
                        </a>
                    </li>
        `;

    breadcrumbs.forEach((crumb, index) => {
      const isLast = index === breadcrumbs.length - 1;
      if (isLast) {
        html += `
                    <li class="breadcrumb-item active" aria-current="page">
                        ${this.escapeHtml(crumb.name)}
                    </li>
                `;
      } else {
        html += `
                    <li class="breadcrumb-item">
                        <a href="#" data-folder-id="${crumb.id}">
                            ${this.escapeHtml(crumb.name)}
                        </a>
                    </li>
                `;
      }
    });

    html += `
                </ol>
            </nav>
        `;

    container.innerHTML = html;
  }

  updateURL(folderId) {
    const url = new URL(window.location);
    if (folderId) {
      url.searchParams.set('folder', folderId);
    } else {
      url.searchParams.delete('folder');
    }
    window.history.replaceState({}, '', url);
  }

  updateViewState() {
    // Update view mode buttons
    document.querySelectorAll('[data-view-mode]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.viewMode === this.viewMode);
    });

    // Update sort buttons
    document.querySelectorAll('[data-sort-by]').forEach(btn => {
      const isActive = btn.dataset.sortBy === this.sortBy;
      btn.classList.toggle('active', isActive);

      if (isActive) {
        const icon = btn.querySelector('i:last-child');
        if (icon) {
          icon.className = this.sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        }
      }
    });

    // Update upload manager current folder
    if (this.uploadManager) {
      this.uploadManager.setCurrentFolder(this.currentFolderId);
    }
  }

  navigateUp() {
    if (this.currentPath.length > 0) {
      const parentFolder = this.currentPath[this.currentPath.length - 2];
      this.navigateToFolder(parentFolder ? parentFolder.id : null);
    }
  }

  async refreshCurrentFolder() {
    await this.navigateToFolder(this.currentFolderId);
  }

  setViewMode(mode) {
    if (this.viewMode !== mode) {
      this.viewMode = mode;
      this.saveUserPreferences();
      this.refreshCurrentFolder();

      document.dispatchEvent(new CustomEvent('viewModeChanged', {
        detail: { mode }
      }));
    }
  }

  setSortOrder(sortBy, sortOrder) {
    if (this.sortBy !== sortBy || this.sortOrder !== sortOrder) {
      this.sortBy = sortBy;
      this.sortOrder = sortOrder;
      this.saveUserPreferences();
      this.refreshCurrentFolder();

      document.dispatchEvent(new CustomEvent('sortChanged', {
        detail: { sortBy, sortOrder }
      }));
    }
  }

  // Modal handlers
  showCreateFolderModal(parentFolderId = null) {
    const modal = document.getElementById('createFolderModal');
    const parentIdField = document.getElementById('parentFolderId');
    const folderNameField = document.getElementById('folderName');

    if (parentIdField) {
      parentIdField.value = parentFolderId || this.currentFolderId || '';
    }

    if (folderNameField) {
      folderNameField.value = '';
      folderNameField.focus();
    }

    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
  }

  showRenameFolderModal(folderId, currentName) {
    const modal = document.getElementById('renameFolderModal');
    const folderIdField = document.getElementById('renameFolderId');
    const folderNameField = document.getElementById('renameFolderName');

    if (folderIdField) folderIdField.value = folderId;
    if (folderNameField) {
      folderNameField.value = currentName;
      folderNameField.select();
    }

    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
  }

  showDeleteConfirmModal(type, id, name, additionalInfo = '') {
    const modal = document.getElementById('deleteConfirmModal');
    const typeField = document.getElementById('deleteType');
    const idField = document.getElementById('deleteId');
    const messageElement = document.getElementById('deleteMessage');

    if (typeField) typeField.value = type;
    if (idField) idField.value = id;

    if (messageElement) {
      const itemType = type === 'file' ? 'ファイル' : 'フォルダ';
      let message = `${itemType}「${name}」を削除しますか？`;

      if (type === 'folder') {
        message += '\n中身のファイルもすべて削除されます。';
      }

      if (additionalInfo) {
        message += `\n\n${additionalInfo}`;
      }

      message += '\n\nこの操作は取り消すことができません。';
      messageElement.textContent = message;
    }

    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
  }

  showUploadModal() {
    if (this.uploadManager) {
      this.uploadManager.showUploadModal();
    }
  }

  // Form handlers
  async handleCreateFolder() {
    const form = document.getElementById('createFolderForm');
    const formData = new FormData(form);

    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/folders`, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        const modal = bootstrap.Modal.getInstance(document.getElementById('createFolderModal'));
        modal.hide();
        this.refreshCurrentFolder();
      } else {
        this.showError(result.message || 'フォルダの作成に失敗しました。');
      }
    } catch (error) {
      DocumentErrorHandler.handleError(error, {
        operation: 'create_folder',
        facilityId: this.facilityId,
        folderName: document.getElementById('folderName').value,
        retryCallback: () => this.handleCreateFolder()
      });
    }
  }

  async handleRenameFolder() {
    const form = document.getElementById('renameFolderForm');
    const formData = new FormData(form);
    const folderId = formData.get('folder_id');

    try {
      const response = await fetch(`/facilities/${this.facilityId}/documents/folders/${folderId}`, {
        method: 'PUT',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        const modal = bootstrap.Modal.getInstance(document.getElementById('renameFolderModal'));
        modal.hide();
        this.refreshCurrentFolder();
      } else {
        this.showError(result.message || 'フォルダ名の変更に失敗しました。');
      }
    } catch (error) {
      DocumentErrorHandler.handleError(error, {
        operation: 'rename_folder',
        facilityId: this.facilityId,
        folderId: this.selectedItemId,
        newName: document.getElementById('newFolderName').value,
        retryCallback: () => this.handleRenameFolder()
      });
    }
  }

  async handleDeleteConfirm() {
    const typeField = document.getElementById('deleteType');
    const idField = document.getElementById('deleteId');

    const type = typeField.value;
    const id = idField.value;

    try {
      const endpoint = type === 'file'
        ? `/facilities/${this.facilityId}/documents/files/${id}`
        : `/facilities/${this.facilityId}/documents/folders/${id}`;

      const response = await fetch(endpoint, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        modal.hide();
        this.refreshCurrentFolder();
      } else {
        this.showError(result.message || '削除に失敗しました。');
      }
    } catch (error) {
      console.error('Delete failed:', error);
      this.showError('削除中にエラーが発生しました。');
    }
  }

  // File operations
  async previewFile(fileId) {
    window.open(`/facilities/${this.facilityId}/documents/files/${fileId}/preview`, '_blank');
  }

  // Auto-refresh functionality
  startAutoRefresh() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }

    this.refreshTimer = setInterval(() => {
      if (!document.hidden && !this.isLoading) {
        this.refreshCurrentFolder();
      }
    }, this.options.refreshInterval);
  }

  stopAutoRefresh() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = null;
    }
  }

  // User preferences
  saveUserPreferences() {
    const preferences = {
      viewMode: this.viewMode,
      sortBy: this.sortBy,
      sortOrder: this.sortOrder
    };

    localStorage.setItem(`documentPrefs_${this.facilityId}`, JSON.stringify(preferences));
  }

  loadUserPreferences() {
    const saved = localStorage.getItem(`documentPrefs_${this.facilityId}`);
    if (saved) {
      try {
        const preferences = JSON.parse(saved);
        this.viewMode = preferences.viewMode || 'list';
        this.sortBy = preferences.sortBy || 'name';
        this.sortOrder = preferences.sortOrder || 'asc';
      } catch (error) {
        console.warn('Failed to load user preferences:', error);
      }
    }
  }

  // UI state management
  showLoadingState() {
    const container = document.getElementById('documentList');
    if (container) {
      container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                    <p class="mt-2 text-muted">読み込み中...</p>
                </div>
            `;
    }
  }

  hideLoadingState() {
    // Loading state is replaced by content in renderFolderContents
  }

  // Utility methods
  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('ja-JP', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  showSuccess(message) {
    if (window.showSuccess) {
      window.showSuccess(message);
    } else {
      console.log('Success:', message);
    }
  }

  showError(message) {
    if (window.showError) {
      window.showError(message);
    } else {
      console.error('Error:', message);
    }
  }

  showWarning(message) {
    if (window.showWarning) {
      window.showWarning(message);
    } else {
      console.warn('Warning:', message);
    }
  }

  // Performance Optimization Methods

  /**
   * Setup virtual scrolling for large datasets
   */
  setupVirtualScrolling() {
    if (!this.options.enableVirtualScrolling) return;

    this.virtualScrollContainer = document.getElementById('documentList');
    if (!this.virtualScrollContainer) return;

    // Create virtual scroll wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'virtual-scroll-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.overflow = 'auto';
    wrapper.style.height = '600px'; // Default height

    const content = document.createElement('div');
    content.className = 'virtual-scroll-content';
    content.style.position = 'relative';

    wrapper.appendChild(content);
    this.virtualScrollContainer.appendChild(wrapper);

    // Setup scroll event listener
    wrapper.addEventListener('scroll', this.throttle(this.handleVirtualScroll.bind(this), 16));
  }

  /**
   * Handle virtual scrolling
   */
  handleVirtualScroll(event) {
    if (!this.options.enableVirtualScrolling || this.virtualScrollData.items.length < this.options.virtualScrollThreshold) {
      return;
    }

    const scrollTop = event.target.scrollTop;
    const containerHeight = event.target.clientHeight;

    const startIndex = Math.floor(scrollTop / this.virtualScrollData.itemHeight);
    const endIndex = Math.min(
      startIndex + Math.ceil(containerHeight / this.virtualScrollData.itemHeight) + 5, // Buffer
      this.virtualScrollData.items.length
    );

    if (startIndex !== this.virtualScrollData.visibleStart || endIndex !== this.virtualScrollData.visibleEnd) {
      this.virtualScrollData.visibleStart = startIndex;
      this.virtualScrollData.visibleEnd = endIndex;
      this.renderVirtualItems();
    }

    // Lazy load more items if near the end
    if (this.options.enableLazyLoading && endIndex > this.virtualScrollData.items.length - 10) {
      this.loadMoreItems();
    }
  }

  /**
   * Render virtual scroll items
   */
  renderVirtualItems() {
    const content = this.virtualScrollContainer.querySelector('.virtual-scroll-content');
    if (!content) return;

    const visibleItems = this.virtualScrollData.items.slice(
      this.virtualScrollData.visibleStart,
      this.virtualScrollData.visibleEnd
    );

    let html = '';
    const offsetY = this.virtualScrollData.visibleStart * this.virtualScrollData.itemHeight;

    if (this.viewMode === 'list') {
      html = `
        <div style="transform: translateY(${offsetY}px)">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>名前</th>
                <th>種類</th>
                <th>サイズ</th>
                <th>更新日時</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
      `;

      visibleItems.forEach(item => {
        html += item.type === 'folder' ? this.renderFolderRow(item) : this.renderFileRow(item);
      });

      html += `
            </tbody>
          </table>
        </div>
      `;
    } else {
      html = `<div class="row g-3" style="transform: translateY(${offsetY}px)">`;
      visibleItems.forEach(item => {
        html += item.type === 'folder' ? this.renderFolderCard(item) : this.renderFileCard(item);
      });
      html += '</div>';
    }

    content.innerHTML = html;

    // Update total height
    const totalHeight = this.virtualScrollData.items.length * this.virtualScrollData.itemHeight;
    content.style.height = `${totalHeight}px`;
  }

  /**
   * Setup lazy loading with Intersection Observer
   */
  setupLazyLoading() {
    if (!this.options.enableLazyLoading || !window.IntersectionObserver) return;

    this.intersectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !this.loadingMoreItems && this.hasMorePages) {
            this.loadMoreItems();
          }
        });
      },
      { threshold: 0.1 }
    );
  }

  /**
   * Load more items for pagination
   */
  async loadMoreItems() {
    if (this.loadingMoreItems || !this.hasMorePages) return;

    this.loadingMoreItems = true;
    this.showLoadMoreIndicator();

    try {
      const nextPage = this.currentPage + 1;
      const response = await this.fetchFolderContents(this.currentFolderId, {
        page: nextPage,
        per_page: this.options.itemsPerPage,
        load_stats: false // Don't load stats for pagination requests
      });

      if (response.success && response.data) {
        const newItems = [...(response.data.folders || []), ...(response.data.files || [])];

        if (this.options.enableVirtualScrolling) {
          this.virtualScrollData.items.push(...newItems);
          this.renderVirtualItems();
        } else {
          this.appendItemsToView(newItems);
        }

        this.currentPage = nextPage;
        this.hasMorePages = response.data.pagination?.has_more_pages || false;

        // Cache the new data
        this.cacheData(`folder_${this.currentFolderId}_page_${nextPage}`, response.data);
      }
    } catch (error) {
      console.error('Failed to load more items:', error);
    } finally {
      this.loadingMoreItems = false;
      this.hideLoadMoreIndicator();
    }
  }

  /**
   * Setup performance optimizations
   */
  setupPerformanceOptimizations() {
    // Setup resize observer for responsive adjustments
    if (window.ResizeObserver) {
      this.resizeObserver = new ResizeObserver(this.debouncedResize);
      this.resizeObserver.observe(document.getElementById('documentList'));
    }

    // Setup visibility change handler for cache management
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.pausePerformanceFeatures();
      } else {
        this.resumePerformanceFeatures();
      }
    });

    // Setup memory management
    this.setupMemoryManagement();
  }

  /**
   * Setup memory management
   */
  setupMemoryManagement() {
    // Clear old cache entries periodically
    setInterval(() => {
      this.cleanupCache();
    }, 60000); // Every minute

    // Clear cache when memory pressure is detected
    if ('memory' in performance) {
      setInterval(() => {
        const memInfo = performance.memory;
        const usedRatio = memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit;

        if (usedRatio > 0.8) { // 80% memory usage
          this.clearCache();
          console.warn('High memory usage detected, clearing document cache');
        }
      }, 30000); // Every 30 seconds
    }
  }

  /**
   * Cache management
   */
  cacheData(key, data) {
    this.cache.set(key, {
      data: data,
      timestamp: Date.now(),
      size: JSON.stringify(data).length
    });

    // Limit cache size
    if (this.cache.size > 50) {
      const oldestKey = this.cache.keys().next().value;
      this.cache.delete(oldestKey);
    }
  }

  getCachedData(key) {
    const cached = this.cache.get(key);
    if (!cached) return null;

    // Check if cache is still valid
    if (Date.now() - cached.timestamp > this.options.cacheTimeout) {
      this.cache.delete(key);
      return null;
    }

    return cached.data;
  }

  cleanupCache() {
    const now = Date.now();
    for (const [key, value] of this.cache.entries()) {
      if (now - value.timestamp > this.options.cacheTimeout) {
        this.cache.delete(key);
      }
    }
  }

  clearCache() {
    this.cache.clear();
  }

  /**
   * Optimized folder contents fetching with caching
   */
  async fetchFolderContents(folderId, options = {}) {
    const cacheKey = `folder_${folderId}_${JSON.stringify(options)}`;

    // Try to get from cache first
    const cached = this.getCachedData(cacheKey);
    if (cached && !options.force_refresh) {
      return { success: true, data: cached };
    }

    const url = folderId
      ? `/facilities/${this.facilityId}/documents/folders/${folderId}`
      : `/facilities/${this.facilityId}/documents/folders/root`;

    const params = new URLSearchParams({
      view_mode: this.viewMode,
      sort_by: this.sortBy,
      sort_order: this.sortOrder,
      page: options.page || 1,
      per_page: options.per_page || this.options.itemsPerPage,
      load_stats: options.load_stats !== false,
      ...options
    });

    try {
      const response = await fetch(`${url}?${params}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        // Cache the result
        this.cacheData(cacheKey, result.data);
      }

      return result;
    } catch (error) {
      console.error('Failed to fetch folder contents:', error);
      throw error;
    }
  }

  /**
   * Optimized rendering with requestAnimationFrame
   */
  renderFolderContents(data) {
    const container = document.getElementById('documentList');
    if (!container) return;

    // Use requestAnimationFrame for smooth rendering
    requestAnimationFrame(() => {
      const { folders = [], files = [] } = data;
      const allItems = [...folders, ...files];

      // Update pagination state
      if (data.pagination) {
        this.currentPage = data.pagination.current_page;
        this.totalPages = data.pagination.last_page;
        this.totalItems = data.pagination.total;
        this.hasMorePages = data.pagination.has_more_pages;
      }

      // Use virtual scrolling for large datasets
      if (this.options.enableVirtualScrolling && allItems.length > this.options.virtualScrollThreshold) {
        this.virtualScrollData.items = allItems;
        this.renderVirtualItems();
      } else {
        // Regular rendering for smaller datasets
        if (this.viewMode === 'list') {
          this.renderListView(container, data);
        } else {
          this.renderIconView(container, data);
        }
      }

      // Setup lazy loading sentinel if needed
      if (this.options.enableLazyLoading && this.hasMorePages) {
        this.setupLazyLoadingSentinel();
      }
    });
  }

  /**
   * Setup lazy loading sentinel
   */
  setupLazyLoadingSentinel() {
    // Remove existing sentinel
    const existingSentinel = document.getElementById('lazy-loading-sentinel');
    if (existingSentinel) {
      existingSentinel.remove();
    }

    // Create new sentinel
    const sentinel = document.createElement('div');
    sentinel.id = 'lazy-loading-sentinel';
    sentinel.style.height = '1px';
    sentinel.style.margin = '20px 0';

    const container = document.getElementById('documentList');
    container.appendChild(sentinel);

    // Observe the sentinel
    if (this.intersectionObserver) {
      this.intersectionObserver.observe(sentinel);
    }
  }

  /**
   * Utility functions
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

  throttle(func, limit) {
    let inThrottle;
    return function () {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  /**
   * Performance monitoring
   */
  pausePerformanceFeatures() {
    this.stopAutoRefresh();
    if (this.intersectionObserver) {
      this.intersectionObserver.disconnect();
    }
  }

  resumePerformanceFeatures() {
    if (this.options.autoRefresh) {
      this.startAutoRefresh();
    }
    if (this.intersectionObserver && this.options.enableLazyLoading) {
      this.setupLazyLoadingSentinel();
    }
  }

  /**
   * Handle resize events
   */
  handleResize() {
    if (this.options.enableVirtualScrolling) {
      const container = this.virtualScrollContainer?.querySelector('.virtual-scroll-wrapper');
      if (container) {
        this.virtualScrollData.containerHeight = container.clientHeight;
        this.renderVirtualItems();
      }
    }
  }

  /**
   * Show/hide loading indicators
   */
  showLoadMoreIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'load-more-indicator';
    indicator.className = 'text-center py-3';
    indicator.innerHTML = `
      <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">読み込み中...</span>
      </div>
      <span class="ms-2 text-muted">さらに読み込み中...</span>
    `;

    const container = document.getElementById('documentList');
    container.appendChild(indicator);
  }

  hideLoadMoreIndicator() {
    const indicator = document.getElementById('load-more-indicator');
    if (indicator) {
      indicator.remove();
    }
  }

  /**
   * Append items to existing view (for pagination)
   */
  appendItemsToView(items) {
    const container = document.getElementById('documentList');
    if (!container) return;

    if (this.viewMode === 'list') {
      const tbody = container.querySelector('tbody');
      if (tbody) {
        items.forEach(item => {
          const row = item.type === 'folder' ? this.renderFolderRow(item) : this.renderFileRow(item);
          tbody.insertAdjacentHTML('beforeend', row);
        });
      }
    } else {
      const row = container.querySelector('.row');
      if (row) {
        items.forEach(item => {
          const card = item.type === 'folder' ? this.renderFolderCard(item) : this.renderFileCard(item);
          row.insertAdjacentHTML('beforeend', card);
        });
      }
    }
  }

  // Cleanup
  destroy() {
    this.stopAutoRefresh();

    // Cleanup performance features
    if (this.intersectionObserver) {
      this.intersectionObserver.disconnect();
    }

    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }

    // Clear cache
    this.clearCache();

    if (this.fileManager) {
      // Cleanup file manager if it has a destroy method
      if (typeof this.fileManager.destroy === 'function') {
        this.fileManager.destroy();
      }
    }

    if (this.uploadManager) {
      // Cleanup upload manager if it has a destroy method
      if (typeof this.uploadManager.destroy === 'function') {
        this.uploadManager.destroy();
      }
    }
  }
}

// Make available globally for onclick handlers and compatibility
window.DocumentManager = DocumentManager;
window.documentManager = null;

// Global functions for backward compatibility
window.openFolder = function (folderId) {
  if (window.documentManager) {
    window.documentManager.navigateToFolder(folderId);
  }
};

window.showCreateFolderModal = function (parentFolderId = null) {
  if (window.documentManager) {
    window.documentManager.showCreateFolderModal(parentFolderId);
  }
};

window.showRenameFolderModal = function (folderId, currentName) {
  if (window.documentManager) {
    window.documentManager.showRenameFolderModal(folderId, currentName);
  }
};

window.showDeleteConfirmModal = function (type, id, name, additionalInfo = '') {
  if (window.documentManager) {
    window.documentManager.showDeleteConfirmModal(type, id, name, additionalInfo);
  }
};