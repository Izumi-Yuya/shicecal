/**
 * Document Management Module
 * Handles document and folder operations
 */

import { AppUtils } from '../shared/AppUtils.js';
import { ApiClient } from '../shared/ApiClient.js';

class DocumentManager {
  constructor(options = {}) {
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    this.initialized = false;
    this.currentFolderId = '';

    // State management flags
    this.operationFlags = {
      isCreatingFolder: false,
      isUploadingFile: false,
      isDeletingFile: false,
      isDeletingFolder: false,
      isRenamingItem: false
    };

    this.apiClient = new ApiClient();

    console.log('DocumentManager constructed with facilityId:', this.facilityId);

    // Bind events immediately after DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.bindEvents();
        this.setupModalAccessibility();
      });
    } else {
      this.bindEvents();
      this.setupModalAccessibility();
    }
  }

  async init() {
    if (this.initialized) return;

    console.log('DocumentManager.init() called');

    // Events are already bound in constructor, just load documents
    try {
      await this.loadDocuments();
      console.log('Initial documents loaded successfully');
    } catch (error) {
      console.error('Failed to load initial documents:', error);
    }

    this.initialized = true;
  }

  bindEvents() {
    if (this.uiEventsBound) {
      console.log('DocumentManager: bindEvents skipped - already bound');
      return;
    }

    console.log('DocumentManager: bindEvents called');

    // Folder creation
    const createBtn = document.getElementById('create-folder-btn');
    if (createBtn) {
      console.log('DocumentManager: Binding create folder button');
      createBtn.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('DocumentManager: Create folder button clicked');
        this.showCreateFolderModal();
      });
    } else {
      console.warn('DocumentManager: create-folder-btn not found');
    }

    // File upload
    const uploadBtn = document.getElementById('upload-file-btn');
    if (uploadBtn) {
      console.log('DocumentManager: Binding upload file button');
      uploadBtn.addEventListener('click', (e) => {
        e.preventDefault();
        console.log('DocumentManager: Upload file button clicked');
        this.showUploadFileModal();
      });
    } else {
      console.warn('DocumentManager: upload-file-btn not found');
    }

    // Form submissions
    this.bindFormEvents();
    this.bindDocumentListEvents();

    this.uiEventsBound = true;
    console.log('DocumentManager: All events bound successfully');
  }

  bindFormEvents() {
    const createForm = document.getElementById('create-folder-form');
    if (createForm && !createForm.dataset.bound) {
      createForm.dataset.bound = 'true';
      createForm.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.handleCreateFolder(e);
      }, { capture: true });
    }

    const uploadForm = document.getElementById('upload-file-form');
    if (uploadForm && !uploadForm.dataset.bound) {
      uploadForm.dataset.bound = 'true';
      uploadForm.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.handleUploadFile(e);
      }, { capture: true });
    }
  }

  bindDocumentListEvents() {
    const documentList = document.getElementById('document-list');
    if (documentList) {
      documentList.addEventListener('click', (e) => {
        this.handleDocumentListClick(e);
      });

      documentList.addEventListener('contextmenu', (e) => {
        this.handleContextMenu(e);
      });
    }
  }

  async handleCreateFolder(e) {
    if (this.operationFlags.isCreatingFolder) {
      console.log('Folder creation already in progress');
      return;
    }

    const form = e.target;
    const formData = new FormData(form);
    const folderName = formData.get('name');

    if (!folderName?.trim()) {
      AppUtils.showToast('フォルダ名を入力してください。', 'error');
      return;
    }

    this.operationFlags.isCreatingFolder = true;
    const submitButton = form.querySelector('button[type="submit"]');
    AppUtils.showLoading(submitButton, '作成中...');

    try {
      const parentId = this.getCurrentFolderId();
      const payload = {
        name: folderName,
        parent_id: parentId || null,
        _token: this.csrfToken
      };

      const response = await this.apiClient.post(
        `/facilities/${this.facilityId}/documents/folders`,
        payload
      );

      if (response.success) {
        AppUtils.showToast('フォルダが作成されました。', 'success');
        form.reset();
        this.closeModalSafely(['create-folder-modal']);
        await this.refreshDocumentList();
      } else {
        AppUtils.showToast(response.message || 'フォルダの作成に失敗しました。', 'error');
      }
    } catch (error) {
      console.error('Folder creation error:', error);
      AppUtils.showToast('ネットワークエラーが発生しました。', 'error');
    } finally {
      AppUtils.hideLoading(submitButton);
      this.operationFlags.isCreatingFolder = false;
    }
  }

  async handleUploadFile(e) {
    if (this.operationFlags.isUploadingFile) {
      console.log('File upload already in progress');
      return;
    }

    const form = e.target;
    const formData = new FormData(form);
    const files = formData.getAll('files[]');

    if (!files?.length || (files.length === 1 && !files[0].name)) {
      AppUtils.showToast('アップロードするファイルを選択してください。', 'error');
      return;
    }

    // File size validation
    const maxSize = 10 * 1024 * 1024; // 10MB
    for (const file of files) {
      if (file.size > maxSize) {
        AppUtils.showToast(`ファイル "${file.name}" のサイズが大きすぎます。`, 'error');
        return;
      }
    }

    this.operationFlags.isUploadingFile = true;
    const submitButton = form.querySelector('button[type="submit"]');
    AppUtils.showLoading(submitButton, 'アップロード中...');
    this.showUploadProgress(true);

    try {
      // Add folder_id to formData
      const folderId = this.getCurrentFolderId();
      if (folderId) {
        formData.append('folder_id', folderId);
      }

      const response = await this.apiClient.uploadFile(
        `/facilities/${this.facilityId}/documents/files`,
        formData
      );

      if (response.success) {
        const fileCount = files.length;
        const message = fileCount === 1
          ? 'ファイルがアップロードされました。'
          : `${fileCount}個のファイルがアップロードされました。`;

        AppUtils.showToast(message, 'success');
        form.reset();
        this.clearFileList();
        this.closeModalSafely(['upload-file-modal']);
        await this.refreshDocumentList();
      } else {
        AppUtils.showToast(response.message || 'ファイルのアップロードに失敗しました。', 'error');
      }
    } catch (error) {
      console.error('File upload error:', error);
      AppUtils.showToast('ネットワークエラーが発生しました。', 'error');
    } finally {
      AppUtils.hideLoading(submitButton);
      this.showUploadProgress(false);
      this.operationFlags.isUploadingFile = false;
    }
  }

  async handleDeleteFile(fileId) {
    return this.handleDelete('file', fileId, {
      flagKey: 'isDeletingFile',
      confirmMessage: 'このファイルを削除しますか？\n削除したファイルは復元できません。',
      successMessage: 'ファイルを削除しました',
      endpoint: `/facilities/${this.facilityId}/documents/files/${fileId}`
    });
  }

  async handleDeleteFolder(folderId) {
    return this.handleDelete('folder', folderId, {
      flagKey: 'isDeletingFolder',
      confirmMessage: 'このフォルダを削除しますか？\n削除したフォルダは復元できません。',
      successMessage: 'フォルダを削除しました',
      endpoint: `/facilities/${this.facilityId}/documents/folders/${folderId}`
    });
  }

  async handleDelete(type, id, options) {
    // Validate input
    if (!id || (typeof id !== 'string' && typeof id !== 'number')) {
      console.error(`Invalid ${type} ID:`, id);
      AppUtils.showToast(`無効な${type}IDです`, 'error');
      return;
    }

    if (this.operationFlags[options.flagKey]) return;

    const confirmed = await AppUtils.confirmDialog(
      options.confirmMessage,
      '削除確認',
      { type: 'delete' }
    );

    if (!confirmed) return;

    this.operationFlags[options.flagKey] = true;

    try {
      const response = await this.apiClient.delete(options.endpoint);

      if (response.success) {
        AppUtils.showToast(options.successMessage, 'success');
        await this.refreshDocumentList();
      } else {
        AppUtils.showToast(response.message || '削除に失敗しました', 'error');
      }
    } catch (error) {
      console.error(`${type} deletion error:`, error);
      AppUtils.showToast('削除に失敗しました', 'error');
    } finally {
      this.operationFlags[options.flagKey] = false;
    }
  }

  // Utility methods
  getCurrentFolderId() {
    return this.currentFolderId || '';
  }

  showUploadProgress(show) {
    const progressElement = document.getElementById('upload-progress');
    if (progressElement) {
      progressElement.style.display = show ? 'block' : 'none';
    }
  }

  clearFileList() {
    const fileList = document.getElementById('file-list');
    if (fileList) {
      fileList.style.display = 'none';
    }
  }

  closeModalSafely(modalIds) {
    const modal = this.getModalByIds(modalIds);
    if (modal && window.bootstrap) {
      const modalInstance = bootstrap.Modal.getInstance(modal) ||
        bootstrap.Modal.getOrCreateInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }

  getModalByIds(idCandidates = []) {
    for (const id of idCandidates) {
      const el = document.getElementById(id);
      if (el) return el;
    }
    return null;
  }

  async refreshDocumentList() {
    try {
      await this.loadDocuments(this.currentFolderId);
    } catch (error) {
      console.error('Failed to refresh document list:', error);
    }
  }

  async loadDocuments(folderId = '') {
    this.currentFolderId = folderId;

    try {
      console.log('DocumentManager.loadDocuments called with folderId:', folderId);

      // Show loading indicator
      this.showLoading(true);

      // Use the correct endpoint that matches the route definition
      // Route: GET /facilities/{facility}/documents/folders/{folder?}
      const endpoint = `/facilities/${this.facilityId}/documents/folders${folderId ? '/' + folderId : ''}`;

      console.log('Loading documents from endpoint:', endpoint);
      console.log('Facility ID:', this.facilityId);

      const response = await this.apiClient.get(endpoint);
      console.log('API response received:', response);

      if (response && response.success) {
        console.log('Documents loaded successfully:', response.data);
        this.renderDocuments(response.data);
        this.hideError();
      } else {
        console.error('API response indicates failure:', response);
        this.showError(response?.message || 'ドキュメントの読み込みに失敗しました');
      }
    } catch (error) {
      console.error('Failed to load documents:', {
        error: error,
        message: error.message,
        status: error.status,
        data: error.data
      });

      let errorMessage = 'ネットワークエラーが発生しました';
      if (error.status === 404) {
        errorMessage = 'ドキュメントが見つかりません';
      } else if (error.status === 403) {
        errorMessage = 'ドキュメントにアクセスする権限がありません';
      } else if (error.message) {
        errorMessage = error.message;
      }

      this.showError(errorMessage);
    } finally {
      this.showLoading(false);
    }
  }

  renderDocuments(data) {
    const tableBody = document.getElementById('document-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    // Render folders first
    if (data.folders && data.folders.length > 0) {
      data.folders.forEach(folder => {
        const row = this.createFolderRow(folder);
        tableBody.appendChild(row);
      });
    }

    // Then render files
    if (data.files && data.files.length > 0) {
      data.files.forEach(file => {
        const row = this.createFileRow(file);
        tableBody.appendChild(row);
      });
    }

    // Show empty state if no items
    if ((!data.folders || data.folders.length === 0) &&
      (!data.files || data.files.length === 0)) {
      this.showEmptyState();
    } else {
      this.hideEmptyState();
    }
  }

  createFolderRow(folder) {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>
        <input type="checkbox" class="form-check-input" value="${folder.id}">
      </td>
      <td>
        <div class="d-flex align-items-center">
          <i class="fas fa-folder text-warning me-2"></i>
          <span class="item-name" data-name="${folder.name}">${folder.name}</span>
        </div>
      </td>
      <td>-</td>
      <td>${this.formatDate(folder.updated_at)}</td>
      <td>${folder.created_by || '-'}</td>
      <td>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-primary btn-sm" 
                  data-action="edit" data-item-id="${folder.id}" data-item-type="folder"
                  data-name="${folder.name}" title="名前変更">
            <i class="fas fa-edit"></i>
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" 
                  data-action="delete" data-item-id="${folder.id}" data-item-type="folder"
                  data-name="${folder.name}" title="削除">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </td>
    `;
    return row;
  }

  createFileRow(file) {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>
        <input type="checkbox" class="form-check-input" value="${file.id}">
      </td>
      <td>
        <div class="d-flex align-items-center">
          <i class="fas fa-file text-primary me-2"></i>
          <span class="item-name" data-name="${file.name}">${file.name}</span>
        </div>
      </td>
      <td>${this.formatFileSize(file.size)}</td>
      <td>${this.formatDate(file.updated_at)}</td>
      <td>${file.uploaded_by || '-'}</td>
      <td>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-primary btn-sm" 
                  data-action="edit" data-item-id="${file.id}" data-item-type="file"
                  data-name="${file.name}" title="名前変更">
            <i class="fas fa-edit"></i>
          </button>
          <button type="button" class="btn btn-outline-success btn-sm" 
                  data-action="download" data-item-id="${file.id}" title="ダウンロード">
            <i class="fas fa-download"></i>
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" 
                  data-action="delete" data-item-id="${file.id}" data-item-type="file"
                  data-name="${file.name}" title="削除">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </td>
    `;
    return row;
  }

  formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP') + ' ' + date.toLocaleTimeString('ja-JP', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  }

  showEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const documentList = document.getElementById('document-list');
    if (emptyState) emptyState.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
  }

  hideEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const documentList = document.getElementById('document-list');
    if (emptyState) emptyState.style.display = 'none';
    if (documentList) documentList.style.display = 'block';
  }

  showError(message) {
    const errorElement = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    if (errorElement && errorText) {
      errorText.textContent = message;
      errorElement.style.display = 'block';
    }

    // Hide other states
    this.hideEmptyState();
    this.showLoading(false);
  }

  hideError() {
    const errorElement = document.getElementById('error-message');
    if (errorElement) {
      errorElement.style.display = 'none';
    }
  }

  showLoading(show) {
    const loadingElement = document.getElementById('loading-indicator');
    const documentList = document.getElementById('document-list');

    if (loadingElement) {
      loadingElement.style.display = show ? 'block' : 'none';
    }

    if (show) {
      this.hideEmptyState();
      this.hideError();
      if (documentList) documentList.style.display = 'none';
    }
  }

  setupModalAccessibility() {
    // Setup keyboard navigation for modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      modal.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          if (modalInstance) modalInstance.hide();
        }
      });
    });
  }

  handleDocumentListClick(event) {
    const target = event.target.closest('[data-action]');
    if (!target) return;

    const action = target.dataset.action;
    const itemId = target.dataset.itemId;
    const itemType = target.dataset.itemType;
    const itemName = target.dataset.name || this.getCurrentItemName(target);

    console.log('Document action:', { action, itemId, itemType, itemName });

    switch (action) {
      case 'edit':
        this.showRenameModal(itemId, itemType, itemName);
        break;
      case 'delete':
        if (itemType === 'file') {
          this.handleDeleteFile(itemId);
        } else if (itemType === 'folder') {
          this.handleDeleteFolder(itemId);
        }
        break;
      case 'download':
        this.handleDownload(itemId);
        break;
    }
  }

  getCurrentItemName(element) {
    // Try to get name from data attribute first
    if (element.dataset.name) {
      return element.dataset.name;
    }

    // Fallback: find the name in the same row
    const row = element.closest('tr');
    if (row) {
      const nameElement = row.querySelector('.item-name');
      if (nameElement) {
        return nameElement.textContent.trim();
      }
    }

    return '';
  }

  showRenameModal(itemId, itemType, currentName) {
    const modal = document.getElementById('rename-modal');
    const nameInput = document.getElementById('new-name');
    const saveBtn = document.getElementById('save-rename-btn');

    if (!modal || !nameInput || !saveBtn) {
      console.error('Rename modal elements not found');
      return;
    }

    // Set current name in input
    nameInput.value = currentName || '';

    // Store item info for saving
    modal.dataset.itemId = itemId;
    modal.dataset.itemType = itemType;

    // Show modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Focus on input after modal is shown
    modal.addEventListener('shown.bs.modal', () => {
      nameInput.focus();
      nameInput.select();
    }, { once: true });

    // Handle save button click
    const handleSave = async () => {
      const newName = nameInput.value.trim();
      if (!newName) {
        AppUtils.showToast('名前を入力してください', 'error');
        return;
      }

      if (newName === currentName) {
        modalInstance.hide();
        return;
      }

      await this.handleRenameItem(itemId, itemType, newName);
      modalInstance.hide();
    };

    // Remove existing event listeners
    const newSaveBtn = saveBtn.cloneNode(true);
    saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

    // Add new event listener
    newSaveBtn.addEventListener('click', handleSave);

    // Handle Enter key
    const handleKeydown = (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleSave();
      } else if (e.key === 'Escape') {
        modalInstance.hide();
      }
    };

    nameInput.addEventListener('keydown', handleKeydown);

    // Clean up event listener when modal is hidden
    modal.addEventListener('hidden.bs.modal', () => {
      nameInput.removeEventListener('keydown', handleKeydown);
    }, { once: true });
  }

  async handleRenameItem(itemId, itemType, newName) {
    if (this.operationFlags.isRenamingItem) return;

    this.operationFlags.isRenamingItem = true;

    try {
      const endpoint = itemType === 'file'
        ? `/facilities/${this.facilityId}/documents/files/${itemId}/rename`
        : `/facilities/${this.facilityId}/documents/folders/${itemId}/rename`;

      const response = await this.apiClient.put(endpoint, {
        name: newName
      });

      if (response.success) {
        AppUtils.showToast(`${itemType === 'file' ? 'ファイル' : 'フォルダ'}名を変更しました`, 'success');
        await this.refreshDocumentList();
      } else {
        AppUtils.showToast(response.message || '名前の変更に失敗しました', 'error');
      }
    } catch (error) {
      console.error('Rename error:', error);
      AppUtils.showToast('名前の変更に失敗しました', 'error');
    } finally {
      this.operationFlags.isRenamingItem = false;
    }
  }

  handleContextMenu(event) {
    event.preventDefault();
    // Context menu implementation would go here
    console.log('Context menu triggered');
  }

  showCreateFolderModal() {
    console.log('DocumentManager: showCreateFolderModal called');
    const modal = document.getElementById('create-folder-modal');
    if (modal) {
      console.log('DocumentManager: Modal element found, creating Bootstrap modal instance');
      try {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        console.log('DocumentManager: Modal show() called successfully');
      } catch (error) {
        console.error('DocumentManager: Error showing modal:', error);
      }
    } else {
      console.error('DocumentManager: create-folder-modal element not found in DOM');
    }
  }

  showUploadFileModal() {
    console.log('DocumentManager: showUploadFileModal called');
    const modal = document.getElementById('upload-file-modal');
    if (modal) {
      console.log('DocumentManager: Modal element found, creating Bootstrap modal instance');
      try {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        console.log('DocumentManager: Modal show() called successfully');
      } catch (error) {
        console.error('DocumentManager: Error showing modal:', error);
      }
    } else {
      console.error('DocumentManager: upload-file-modal element not found in DOM');
    }
  }

  handleDownload(fileId) {
    const downloadUrl = `/facilities/${this.facilityId}/documents/files/${fileId}/download`;
    window.open(downloadUrl, '_blank');
  }
}

// Export the class
export { DocumentManager };