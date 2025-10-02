/**
 * Document File Manager Module
 * ドキュメントファイル管理モジュール
 * Handles file operations, context menus, and file display management for the document system
 * ドキュメントシステムのファイル操作、コンテキストメニュー、およびファイル表示管理を処理
 */

export class DocumentFileManager {
  constructor(facilityId, options = {}) {
    this.facilityId = facilityId;
    this.options = {
      maxNameLength: 50,
      tooltipDelay: 500,
      ...options
    };

    this.contextMenu = null;
    this.currentItem = null;
    this.init();
  }

  init() {
    this.setupContextMenus();
    this.setupFileNameTooltips();
    this.setupEventListeners();
    this.createContextMenuHTML();
  }

  setupEventListeners() {
    // Close context menu when clicking elsewhere
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.context-menu') && !e.target.closest('[data-context-menu]')) {
        this.hideContextMenu();
      }
    });

    // Handle keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.hideContextMenu();
      }

      // Handle Enter key on focused folder/file items
      if (e.key === 'Enter') {
        const focusedElement = document.activeElement;
        if (focusedElement.classList.contains('folder-row') || focusedElement.classList.contains('folder-card')) {
          const folderId = focusedElement.dataset.folderId;
          if (folderId) {
            window.openFolder(parseInt(folderId));
          }
        }
      }

      // Handle F2 key for rename
      if (e.key === 'F2') {
        const focusedElement = document.activeElement;
        if (focusedElement.classList.contains('folder-row') || focusedElement.classList.contains('folder-card')) {
          const folderId = focusedElement.dataset.folderId;
          const folderName = focusedElement.querySelector('.folder-name').textContent.trim();
          if (folderId && window.showRenameFolderModal) {
            window.showRenameFolderModal(parseInt(folderId), folderName);
          }
        } else if (focusedElement.classList.contains('file-row') || focusedElement.classList.contains('file-card')) {
          const fileId = focusedElement.dataset.fileId;
          const fileName = focusedElement.querySelector('.file-name').textContent.trim();
          if (fileId) {
            this.promptRename('file', parseInt(fileId), fileName);
          }
        }
      }

      // Handle Delete key
      if (e.key === 'Delete') {
        const focusedElement = document.activeElement;
        if (focusedElement.classList.contains('folder-row') || focusedElement.classList.contains('folder-card')) {
          const folderId = focusedElement.dataset.folderId;
          const folderName = focusedElement.querySelector('.folder-name').textContent.trim();
          if (folderId && window.showDeleteConfirmModal) {
            const fileCountElement = focusedElement.querySelector('small');
            const fileCount = fileCountElement ? fileCountElement.textContent : '';
            window.showDeleteConfirmModal('folder', parseInt(folderId), folderName, fileCount);
          }
        } else if (focusedElement.classList.contains('file-row') || focusedElement.classList.contains('file-card')) {
          const fileId = focusedElement.dataset.fileId;
          const fileName = focusedElement.querySelector('.file-name').textContent.trim();
          if (fileId && window.showDeleteConfirmModal) {
            window.showDeleteConfirmModal('file', parseInt(fileId), fileName);
          }
        }
      }
    });

    // Listen for folder content updates
    document.addEventListener('folderContentLoaded', () => {
      this.setupContextMenus();
      this.setupFileNameTooltips();
    });
  }

  setupContextMenus() {
    // Setup file context menus
    document.querySelectorAll('.file-row, .file-card').forEach(element => {
      element.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        const fileId = element.dataset.fileId;
        if (fileId) {
          this.showFileContextMenu(e, fileId, element);
        }
      });
    });

    // Setup folder context menus
    document.querySelectorAll('.folder-row, .folder-card').forEach(element => {
      element.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        const folderId = element.dataset.folderId;
        if (folderId) {
          this.showFolderContextMenu(e, folderId, element);
        }
      });
    });
  }

  setupFileNameTooltips() {
    // Setup tooltips for truncated file names
    document.querySelectorAll('.file-name, .folder-name').forEach(element => {
      const text = element.textContent.trim();
      const title = element.getAttribute('title');

      if (text.length > this.options.maxNameLength || title) {
        element.setAttribute('title', title || text);
        element.setAttribute('data-bs-toggle', 'tooltip');
        element.setAttribute('data-bs-placement', 'top');

        // Initialize Bootstrap tooltip
        new bootstrap.Tooltip(element, {
          delay: { show: this.options.tooltipDelay, hide: 100 }
        });
      }
    });
  }

  createContextMenuHTML() {
    // Remove existing context menu
    const existingMenu = document.getElementById('contextMenu');
    if (existingMenu) {
      existingMenu.remove();
    }

    // Create context menu HTML
    const contextMenuHTML = `
            <div id="contextMenu" class="context-menu" style="display: none;">
                <div class="context-menu-content">
                    <!-- File context menu items -->
                    <div class="context-menu-section file-menu">
                        <div class="context-menu-item" data-action="download">
                            <i class="fas fa-download me-2"></i>ダウンロード
                        </div>
                        <div class="context-menu-item" data-action="preview">
                            <i class="fas fa-eye me-2"></i>プレビュー
                        </div>
                        <div class="context-menu-item" data-action="rename">
                            <i class="fas fa-edit me-2"></i>名前を変更
                        </div>
                        <div class="context-menu-item" data-action="move">
                            <i class="fas fa-cut me-2"></i>移動
                        </div>
                        <div class="context-menu-item" data-action="copy">
                            <i class="fas fa-copy me-2"></i>コピー
                        </div>
                        <div class="context-menu-divider"></div>
                        <div class="context-menu-item" data-action="properties">
                            <i class="fas fa-info-circle me-2"></i>プロパティ
                        </div>
                        <div class="context-menu-divider"></div>
                        <div class="context-menu-item text-danger" data-action="delete">
                            <i class="fas fa-trash me-2"></i>削除
                        </div>
                    </div>
                    
                    <!-- Folder context menu items -->
                    <div class="context-menu-section folder-menu">
                        <div class="context-menu-item" data-action="open">
                            <i class="fas fa-folder-open me-2"></i>開く
                        </div>
                        <div class="context-menu-divider"></div>
                        <div class="context-menu-item" data-action="rename">
                            <i class="fas fa-edit me-2"></i>名前を変更
                        </div>
                        <div class="context-menu-item" data-action="new-folder">
                            <i class="fas fa-folder-plus me-2"></i>サブフォルダ作成
                        </div>
                        <div class="context-menu-item" data-action="upload">
                            <i class="fas fa-upload me-2"></i>ファイルアップロード
                        </div>
                        <div class="context-menu-divider"></div>
                        <div class="context-menu-item" data-action="copy-path">
                            <i class="fas fa-copy me-2"></i>パスをコピー
                        </div>
                        <div class="context-menu-item" data-action="properties">
                            <i class="fas fa-info-circle me-2"></i>プロパティ
                        </div>
                        <div class="context-menu-divider"></div>
                        <div class="context-menu-item text-danger" data-action="delete">
                            <i class="fas fa-trash me-2"></i>削除
                        </div>
                    </div>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML('beforeend', contextMenuHTML);
    this.contextMenu = document.getElementById('contextMenu');

    // Setup context menu item click handlers
    this.contextMenu.addEventListener('click', (e) => {
      const action = e.target.closest('[data-action]')?.dataset.action;
      if (action) {
        this.handleContextMenuAction(action);
        this.hideContextMenu();
      }
    });
  }

  showFileContextMenu(event, fileId, element) {
    this.currentItem = { type: 'file', id: fileId, element };
    this.showContextMenu(event, 'file');
  }

  showFolderContextMenu(event, folderId, element) {
    this.currentItem = { type: 'folder', id: folderId, element };
    this.showContextMenu(event, 'folder');
  }

  showContextMenu(event, type) {
    if (!this.contextMenu) return;

    // Hide all menu sections
    this.contextMenu.querySelectorAll('.context-menu-section').forEach(section => {
      section.style.display = 'none';
    });

    // Show appropriate menu section
    const menuSection = this.contextMenu.querySelector(`.${type}-menu`);
    if (menuSection) {
      menuSection.style.display = 'block';
    }

    // Position and show context menu
    const x = event.pageX;
    const y = event.pageY;

    this.contextMenu.style.left = x + 'px';
    this.contextMenu.style.top = y + 'px';
    this.contextMenu.style.display = 'block';

    // Adjust position if menu goes off screen
    const rect = this.contextMenu.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    if (rect.right > viewportWidth) {
      this.contextMenu.style.left = (x - rect.width) + 'px';
    }

    if (rect.bottom > viewportHeight) {
      this.contextMenu.style.top = (y - rect.height) + 'px';
    }

    // Update menu items based on current item
    this.updateContextMenuItems();
  }

  hideContextMenu() {
    if (this.contextMenu) {
      this.contextMenu.style.display = 'none';
    }
    this.currentItem = null;
  }

  updateContextMenuItems() {
    if (!this.currentItem || !this.contextMenu) return;

    const { type, element } = this.currentItem;

    if (type === 'file') {
      // Update preview item visibility for files
      const previewItem = this.contextMenu.querySelector('[data-action="preview"]');
      const canPreview = element.querySelector('.btn[title="プレビュー"]') !== null;

      if (previewItem) {
        previewItem.style.display = canPreview ? 'block' : 'none';
      }
    } else if (type === 'folder') {
      // Update folder-specific menu items
      const deleteItem = this.contextMenu.querySelector('[data-action="delete"]');
      const uploadItem = this.contextMenu.querySelector('[data-action="upload"]');

      // Check if folder has files (for delete confirmation)
      const fileCountElement = element.querySelector('small');
      const hasFiles = fileCountElement && fileCountElement.textContent.includes('ファイル') && !fileCountElement.textContent.includes('0');

      if (deleteItem && hasFiles) {
        deleteItem.innerHTML = '<i class="fas fa-trash me-2"></i>削除 (ファイルを含む)';
        deleteItem.title = 'このフォルダとその中のすべてのファイルを削除します';
      } else if (deleteItem) {
        deleteItem.innerHTML = '<i class="fas fa-trash me-2"></i>削除';
        deleteItem.title = 'このフォルダを削除します';
      }

      // Always enable upload for folders
      if (uploadItem) {
        uploadItem.style.display = 'block';
      }
    }
  }

  async handleContextMenuAction(action) {
    if (!this.currentItem) return;

    const { type, id, element } = this.currentItem;

    try {
      switch (action) {
        case 'download':
          if (type === 'file') {
            await this.downloadFile(id);
          }
          break;

        case 'preview':
          if (type === 'file') {
            await this.previewFile(id);
          }
          break;

        case 'open':
          if (type === 'folder') {
            window.openFolder(id);
          }
          break;

        case 'rename':
          await this.renameItem(type, id, element);
          break;

        case 'move':
          await this.moveItem(type, id);
          break;

        case 'copy':
          if (type === 'file') {
            await this.copyFile(id);
          }
          break;

        case 'delete':
          await this.deleteItem(type, id, element);
          break;

        case 'properties':
          await this.showProperties(type, id);
          break;

        case 'new-folder':
          if (type === 'folder') {
            this.createSubFolder(id);
          }
          break;

        case 'upload':
          if (type === 'folder') {
            this.uploadToFolder(id);
          }
          break;

        case 'copy-path':
          await this.copyPath(type, id);
          break;

        default:
          console.warn('Unknown context menu action:', action);
      }
    } catch (error) {
      console.error('Context menu action failed:', error);
      this.showError('操作に失敗しました：' + error.message);
    }
  }

  async downloadFile(fileId) {
    const downloadLink = document.querySelector(`[data-file-id="${fileId}"] .btn[title="ダウンロード"]`);
    if (downloadLink) {
      downloadLink.click();
    } else {
      // Fallback: construct download URL
      window.location.href = `/facilities/${this.facilityId}/documents/files/${fileId}/download`;
    }
  }

  async previewFile(fileId) {
    const previewBtn = document.querySelector(`[data-file-id="${fileId}"] .btn[title="プレビュー"]`);
    if (previewBtn) {
      previewBtn.click();
    } else {
      // Open preview in new window
      window.open(`/facilities/${this.facilityId}/documents/files/${fileId}/preview`, '_blank');
    }
  }

  async renameItem(type, id, element) {
    const currentName = element.querySelector(`.${type}-name`).textContent.trim();

    if (type === 'folder') {
      // Use the new folder rename modal
      if (window.showRenameFolderModal) {
        window.showRenameFolderModal(id, currentName);
      } else {
        // Fallback to prompt
        this.promptRename(type, id, currentName);
      }
    } else {
      // For files, use prompt for now (can be enhanced later)
      this.promptRename(type, id, currentName);
    }
  }

  async promptRename(type, id, currentName) {
    const newName = prompt(`${type === 'file' ? 'ファイル' : 'フォルダ'}の新しい名前を入力してください:`, currentName);

    if (newName && newName !== currentName) {
      try {
        const endpoint = type === 'file'
          ? `/facilities/${this.facilityId}/documents/files/${id}/rename`
          : `/facilities/${this.facilityId}/documents/folders/${id}`;

        const response = await fetch(endpoint, {
          method: 'PUT',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({ name: newName })
        });

        const result = await response.json();

        if (result.success) {
          this.showSuccess(result.message);
          document.dispatchEvent(new CustomEvent('refreshFolder'));
        } else {
          this.showError(result.message || '名前の変更に失敗しました。');
        }
      } catch (error) {
        console.error('Rename failed:', error);
        this.showError('名前の変更中にエラーが発生しました。');
      }
    }
  }

  async deleteItem(type, id, element) {
    const itemName = element.querySelector(`.${type}-name`).textContent.trim();

    // Use the new delete confirmation modal if available
    if (window.showDeleteConfirmModal) {
      let additionalInfo = '';
      if (type === 'folder') {
        // Try to get file count from the element
        const fileCountElement = element.querySelector('small');
        if (fileCountElement && fileCountElement.textContent.includes('ファイル')) {
          additionalInfo = `含まれるファイル: ${fileCountElement.textContent}`;
        }
      }
      window.showDeleteConfirmModal(type, id, itemName, additionalInfo);
    } else {
      // Fallback to confirm dialog
      const confirmMessage = type === 'file'
        ? `ファイル「${itemName}」を削除しますか？\nこの操作は取り消せません。`
        : `フォルダ「${itemName}」を削除しますか？\n中身のファイルもすべて削除されます。この操作は取り消せません。`;

      if (confirm(confirmMessage)) {
        await this.performDelete(type, id);
      }
    }
  }

  async performDelete(type, id) {
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
        document.dispatchEvent(new CustomEvent('refreshFolder'));
      } else {
        this.showError(result.message || '削除に失敗しました。');
      }
    } catch (error) {
      console.error('Delete failed:', error);
      this.showError('削除中にエラーが発生しました。');
    }
  }

  async showProperties(type, id) {
    try {
      const endpoint = type === 'file'
        ? `/facilities/${this.facilityId}/documents/files/${id}/properties`
        : `/facilities/${this.facilityId}/documents/folders/${id}/properties`;

      const response = await fetch(endpoint, {
        headers: {
          'Accept': 'application/json',
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
      this.showError('プロパティの取得中にエラーが発生しました。');
    }
  }

  showPropertiesModal(data) {
    // Create properties modal if it doesn't exist
    let modal = document.getElementById('propertiesModal');
    if (!modal) {
      const modalHTML = `
                <div class="modal fade" id="propertiesModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">プロパティ</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="propertiesContent">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      document.body.insertAdjacentHTML('beforeend', modalHTML);
      modal = document.getElementById('propertiesModal');
    }

    // Update modal content
    const content = document.getElementById('propertiesContent');
    content.innerHTML = this.generatePropertiesHTML(data);

    // Show modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
  }

  generatePropertiesHTML(data) {
    const isFile = data.type === 'file';

    return `
            <div class="properties-content">
                <div class="row mb-3">
                    <div class="col-4"><strong>名前:</strong></div>
                    <div class="col-8">${this.escapeHtml(data.name)}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>種類:</strong></div>
                    <div class="col-8">${isFile ? 'ファイル' : 'フォルダ'}</div>
                </div>
                ${isFile ? `
                    <div class="row mb-3">
                        <div class="col-4"><strong>サイズ:</strong></div>
                        <div class="col-8">${data.formatted_size} (${data.size.toLocaleString()} バイト)</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><strong>ファイル形式:</strong></div>
                        <div class="col-8">${data.extension.toUpperCase()}</div>
                    </div>
                ` : `
                    <div class="row mb-3">
                        <div class="col-4"><strong>ファイル数:</strong></div>
                        <div class="col-8">${data.file_count}個</div>
                    </div>
                `}
                <div class="row mb-3">
                    <div class="col-4"><strong>作成日時:</strong></div>
                    <div class="col-8">${new Date(data.created_at).toLocaleString('ja-JP')}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>更新日時:</strong></div>
                    <div class="col-8">${new Date(data.updated_at).toLocaleString('ja-JP')}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>${isFile ? 'アップロード者' : '作成者'}:</strong></div>
                    <div class="col-8">${this.escapeHtml(data.creator)}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>パス:</strong></div>
                    <div class="col-8"><code>${this.escapeHtml(data.path)}</code></div>
                </div>
            </div>
        `;
  }

  createSubFolder(parentFolderId) {
    // Use the new create folder modal function if available
    if (window.showCreateFolderModal) {
      window.showCreateFolderModal(parentFolderId);
    } else {
      // Fallback to basic modal
      document.getElementById('parentFolderId').value = parentFolderId;
      const modal = new bootstrap.Modal(document.getElementById('createFolderModal'));
      modal.show();
    }
  }

  uploadToFolder(folderId) {
    // Set target folder and show upload modal
    if (window.documentUpload) {
      window.documentUpload.setCurrentFolder(folderId);
      window.documentUpload.showUploadModal();
    } else {
      // Fallback: trigger upload button click
      document.getElementById('currentFolderId').value = folderId;
      const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
      uploadModal.show();
    }
  }

  async copyPath(type, id) {
    try {
      const endpoint = type === 'file'
        ? `/facilities/${this.facilityId}/documents/files/${id}/properties`
        : `/facilities/${this.facilityId}/documents/folders/${id}/properties`;

      const response = await fetch(endpoint, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success && result.data.path) {
        // Copy path to clipboard
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(result.data.path);
          this.showSuccess('パスをクリップボードにコピーしました');
        } else {
          // Fallback for older browsers (document.execCommand is deprecated)
          const textArea = document.createElement('textarea');
          textArea.value = result.data.path;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand('copy'); // Deprecated but needed for older browser support
          document.body.removeChild(textArea);
          this.showSuccess('パスをクリップボードにコピーしました');
        }
      } else {
        this.showError('パスの取得に失敗しました。');
      }
    } catch (error) {
      console.error('Copy path failed:', error);
      this.showError('パスのコピー中にエラーが発生しました。');
    }
  }

  // Utility methods
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  showSuccess(message) {
    if (window.showSuccess) {
      window.showSuccess(message);
    } else {
      alert(message);
    }
  }

  showError(message) {
    if (window.showError) {
      window.showError(message);
    } else {
      alert(message);
    }
  }

  showWarning(message) {
    if (window.showWarning) {
      window.showWarning(message);
    } else {
      alert(message);
    }
  }
}

// Make it available globally
window.documentFileManager = null;