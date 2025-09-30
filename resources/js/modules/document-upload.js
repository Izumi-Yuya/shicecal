/**
 * Document Upload Module
 * ドキュメントアップロードモジュール
 * Handles drag & drop file uploads, multiple file selection, and progress tracking
 * ドラッグ&ドロップによるファイルアップロード、複数ファイル選択、および進捗追跡を処理
 */

export class DocumentUploadManager {
  constructor(facilityId, options = {}) {
    this.facilityId = facilityId;
    this.currentFolderId = null;
    this.options = {
      maxFiles: 10,
      maxFileSize: 10 * 1024 * 1024, // 10MB
      allowedTypes: [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed'
      ],
      ...options
    };

    this.uploadQueue = [];
    this.activeUploads = new Map();
    this.init();
  }

  init() {
    this.setupDragAndDrop();
    this.setupFileInput();
    this.setupUploadModal();
    this.setupEventListeners();
  }

  setupDragAndDrop() {
    const dropZone = document.getElementById('documentList');
    if (!dropZone) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, this.preventDefaults, false);
      document.body.addEventListener(eventName, this.preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, () => this.highlight(dropZone), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, () => this.unhighlight(dropZone), false);
    });

    // Handle dropped files
    dropZone.addEventListener('drop', (e) => this.handleDrop(e), false);
  }

  setupFileInput() {
    const fileInput = document.getElementById('files');
    if (fileInput) {
      fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
    }
  }

  setupUploadModal() {
    const uploadBtn = document.getElementById('uploadFileBtn');
    const modal = document.getElementById('uploadModal');

    if (uploadBtn && modal) {
      uploadBtn.addEventListener('click', () => {
        this.showUploadModal();
      });
    }
  }

  setupEventListeners() {
    const uploadSubmit = document.getElementById('uploadSubmit');
    if (uploadSubmit) {
      uploadSubmit.addEventListener('click', () => this.startUpload());
    }

    // Listen for folder changes
    document.addEventListener('folderChanged', (e) => {
      this.currentFolderId = e.detail.folderId;
    });
  }

  preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  highlight(element) {
    element.classList.add('drag-over');
    if (!element.querySelector('.drop-overlay')) {
      const overlay = document.createElement('div');
      overlay.className = 'drop-overlay';
      overlay.innerHTML = `
                <div class="drop-message">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                    <p class="h5">ファイルをドロップしてアップロード</p>
                    <p class="text-muted">複数ファイル対応（最大${this.options.maxFiles}ファイル）</p>
                </div>
            `;
      element.appendChild(overlay);
    }
  }

  unhighlight(element) {
    element.classList.remove('drag-over');
    const overlay = element.querySelector('.drop-overlay');
    if (overlay) {
      overlay.remove();
    }
  }

  handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    this.processFiles(files);
  }

  handleFileSelect(e) {
    const files = e.target.files;
    this.processFiles(files);
  }

  processFiles(files) {
    const fileArray = Array.from(files);

    // Validate file count
    if (fileArray.length > this.options.maxFiles) {
      this.showError(`一度にアップロードできるファイルは最大${this.options.maxFiles}個までです。`);
      return;
    }

    // Validate each file
    const validFiles = [];
    const errors = [];

    fileArray.forEach((file, index) => {
      const validation = this.validateFile(file);
      if (validation.valid) {
        validFiles.push(file);
      } else {
        errors.push(`${file.name}: ${validation.error}`);
      }
    });

    if (errors.length > 0) {
      this.showError('以下のファイルでエラーが発生しました:\n' + errors.join('\n'));
    }

    if (validFiles.length > 0) {
      this.addToUploadQueue(validFiles);
      this.showUploadModal();
    }
  }

  validateFile(file) {
    // Check file size
    if (file.size > this.options.maxFileSize) {
      return {
        valid: false,
        error: `ファイルサイズが制限（${this.formatFileSize(this.options.maxFileSize)}）を超えています。`
      };
    }

    // Check file type
    if (!this.options.allowedTypes.includes(file.type)) {
      return {
        valid: false,
        error: 'サポートされていないファイル形式です'
      };
    }

    // Check filename
    if (this.isDangerousFileName(file.name)) {
      return {
        valid: false,
        error: 'ファイル名に使用できない文字が含まれています'
      };
    }

    return { valid: true };
  }

  isDangerousFileName(filename) {
    const dangerousPatterns = [
      /\.\./, // Path traversal
      /[\/\\:*?"<>|]/, // Invalid filesystem characters
      /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i // Windows reserved names
    ];

    return dangerousPatterns.some(pattern => pattern.test(filename));
  }

  addToUploadQueue(files) {
    this.uploadQueue = files.map(file => ({
      file,
      id: this.generateId(),
      status: 'pending',
      progress: 0,
      error: null
    }));
  }

  showUploadModal() {
    const modal = document.getElementById('uploadModal');
    const modalBody = modal.querySelector('.modal-body');

    // Update current folder ID
    document.getElementById('currentFolderId').value = this.currentFolderId || '';

    // Show file list if files are queued
    if (this.uploadQueue.length > 0) {
      this.renderUploadQueue(modalBody);
    }

    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
  }

  renderUploadQueue(container) {
    const stats = this.getUploadStats();
    const showBulkActions = stats.total > 1;

    const queueHtml = `
            <div class="upload-queue mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">アップロード予定のファイル (${this.uploadQueue.length}個)</h6>
                    ${showBulkActions ? this.renderBulkActions(stats) : ''}
                </div>
                <div class="upload-stats mb-2">
                    <small class="text-muted">
                        ${stats.pending > 0 ? `待機中: ${stats.pending}個 ` : ''}
                        ${stats.uploading > 0 ? `アップロード中: ${stats.uploading}個 ` : ''}
                        ${stats.completed > 0 ? `完了: ${stats.completed}個 ` : ''}
                        ${stats.error > 0 ? `エラー: ${stats.error}個 ` : ''}
                        ${stats.cancelled > 0 ? `キャンセル: ${stats.cancelled}個 ` : ''}
                    </small>
                </div>
                <div class="file-list" style="max-height: 300px; overflow-y: auto;">
                    ${this.uploadQueue.map(item => this.renderQueueItem(item)).join('')}
                </div>
            </div>
        `;

    // Insert before the file input
    const fileInputGroup = container.querySelector('.mb-3');
    fileInputGroup.insertAdjacentHTML('beforebegin', queueHtml);
  }

  renderBulkActions(stats) {
    const actions = [];

    if (stats.uploading > 0 || stats.pending > 0) {
      actions.push(`
        <button type="button" class="btn btn-sm btn-outline-warning" 
                onclick="documentUpload.cancelAllUploads()" 
                title="すべてのアップロードをキャンセル">
          <i class="fas fa-stop me-1"></i>すべてキャンセル
        </button>
      `);
    }

    if (stats.error > 0 || stats.cancelled > 0) {
      actions.push(`
        <button type="button" class="btn btn-sm btn-outline-primary" 
                onclick="documentUpload.retryAllFailed()" 
                title="失敗したアップロードを再試行">
          <i class="fas fa-redo me-1"></i>失敗分を再試行
        </button>
      `);

      actions.push(`
        <button type="button" class="btn btn-sm btn-outline-danger" 
                onclick="documentUpload.removeAllFailed()" 
                title="失敗したファイルをキューから削除">
          <i class="fas fa-trash me-1"></i>失敗分を削除
        </button>
      `);
    }

    if (stats.completed > 0) {
      actions.push(`
        <button type="button" class="btn btn-sm btn-outline-secondary" 
                onclick="documentUpload.removeAllCompleted()" 
                title="完了したファイルをキューから削除">
          <i class="fas fa-check me-1"></i>完了分を削除
        </button>
      `);
    }

    if (actions.length === 0) return '';

    return `
      <div class="btn-group btn-group-sm">
        ${actions.join('')}
      </div>
    `;
  }

  renderQueueItem(item) {
    const statusIcon = this.getStatusIcon(item.status);
    const progressBar = (item.status === 'uploading' || item.progress > 0) ?
      `<div class="progress mt-1" style="height: 6px;">
                <div class="progress-bar ${this.getProgressBarClass(item.status)}" 
                     role="progressbar" 
                     style="width: ${item.progress}%"
                     aria-valuenow="${item.progress}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <div class="progress-text small text-muted">${item.progress}%</div>` : '';

    return `
            <div class="queue-item d-flex align-items-center justify-content-between p-2 border rounded mb-2" data-id="${item.id}">
                <div class="file-info d-flex align-items-center flex-grow-1">
                    <i class="${this.getFileIcon(item.file)} me-2"></i>
                    <div class="flex-grow-1">
                        <div class="file-name">${item.file.name}</div>
                        <small class="text-muted">${this.formatFileSize(item.file.size)}</small>
                        ${progressBar}
                        ${item.error ? `<div class="text-danger small mt-1">${item.error}</div>` : ''}
                        ${item.status === 'completed' && item.uploadedFile ?
        `<div class="text-success small mt-1">
                            <i class="fas fa-check me-1"></i>アップロード完了
                          </div>` : ''
      }
                    </div>
                </div>
                <div class="file-actions d-flex align-items-center">
                    ${statusIcon}
                    ${this.getActionButtons(item)}
                </div>
            </div>
        `;
  }

  getProgressBarClass(status) {
    switch (status) {
      case 'uploading':
        return 'progress-bar-striped progress-bar-animated bg-primary';
      case 'completed':
        return 'bg-success';
      case 'error':
        return 'bg-danger';
      case 'cancelled':
        return 'bg-warning';
      default:
        return 'bg-primary';
    }
  }

  getActionButtons(item) {
    switch (item.status) {
      case 'pending':
        return `
          <button type="button" class="btn btn-sm btn-outline-danger ms-2" 
                  onclick="documentUpload.removeFromQueue('${item.id}')" 
                  title="キューから削除">
            <i class="fas fa-times"></i>
          </button>
        `;

      case 'uploading':
        return `
          <button type="button" class="btn btn-sm btn-outline-warning ms-2" 
                  onclick="documentUpload.cancelUpload('${item.id}')" 
                  title="アップロードをキャンセル">
            <i class="fas fa-stop"></i>
          </button>
        `;

      case 'error':
        return `
          <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                  onclick="documentUpload.retryUpload('${item.id}')" 
                  title="再試行">
            <i class="fas fa-redo"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger ms-1" 
                  onclick="documentUpload.removeFromQueue('${item.id}')" 
                  title="キューから削除">
            <i class="fas fa-times"></i>
          </button>
        `;

      case 'cancelled':
        return `
          <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                  onclick="documentUpload.retryUpload('${item.id}')" 
                  title="再試行">
            <i class="fas fa-redo"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger ms-1" 
                  onclick="documentUpload.removeFromQueue('${item.id}')" 
                  title="キューから削除">
            <i class="fas fa-times"></i>
          </button>
        `;

      default:
        return '';
    }
  }

  getStatusIcon(status) {
    switch (status) {
      case 'pending':
        return '<i class="fas fa-clock text-muted" title="待機中"></i>';
      case 'uploading':
        return '<div class="spinner-border spinner-border-sm text-primary" role="status" title="アップロード中"></div>';
      case 'completed':
        return '<i class="fas fa-check-circle text-success" title="完了"></i>';
      case 'error':
        return '<i class="fas fa-exclamation-circle text-danger" title="エラー"></i>';
      case 'cancelled':
        return '<i class="fas fa-ban text-warning" title="キャンセル"></i>';
      default:
        return '';
    }
  }

  getFileIcon(file) {
    const type = file.type.toLowerCase();

    if (type.includes('pdf')) return 'fas fa-file-pdf text-danger';
    if (type.includes('word') || type.includes('document')) return 'fas fa-file-word text-primary';
    if (type.includes('excel') || type.includes('sheet')) return 'fas fa-file-excel text-success';
    if (type.includes('powerpoint') || type.includes('presentation')) return 'fas fa-file-powerpoint text-warning';
    if (type.includes('image')) return 'fas fa-file-image text-info';
    if (type.includes('text')) return 'fas fa-file-alt text-secondary';
    if (type.includes('zip') || type.includes('rar') || type.includes('7z')) return 'fas fa-file-archive text-dark';

    return 'fas fa-file text-muted';
  }

  removeFromQueue(itemId) {
    const item = this.uploadQueue.find(item => item.id === itemId);

    // Cancel upload if in progress
    if (item && item.xhr) {
      item.xhr.abort();
    }

    this.uploadQueue = this.uploadQueue.filter(item => item.id !== itemId);

    // Re-render queue
    const modal = document.getElementById('uploadModal');
    const queueContainer = modal.querySelector('.upload-queue');
    if (queueContainer) {
      if (this.uploadQueue.length === 0) {
        queueContainer.remove();
      } else {
        queueContainer.querySelector('.file-list').innerHTML =
          this.uploadQueue.map(item => this.renderQueueItem(item)).join('');

        // Update queue count
        const countElement = queueContainer.querySelector('h6');
        if (countElement) {
          countElement.textContent = `アップロード予定のファイル (${this.uploadQueue.length}個)`;
        }
      }
    }
  }

  cancelUpload(itemId) {
    const item = this.uploadQueue.find(item => item.id === itemId);
    if (item) {
      item.cancelled = true;

      if (item.xhr) {
        item.xhr.abort();
      }

      item.status = 'cancelled';
      item.error = 'ユーザーによってキャンセルされました';
      this.updateQueueItemDisplay(item);
    }
  }

  retryUpload(itemId) {
    const item = this.uploadQueue.find(item => item.id === itemId);
    if (item) {
      // Reset item state
      item.status = 'pending';
      item.progress = 0;
      item.error = null;
      item.cancelled = false;
      item.xhr = null;

      this.updateQueueItemDisplay(item);

      // Start upload for this item
      this.uploadSingleFile(item);
    }
  }

  async uploadSingleFile(item) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
      item.status = 'uploading';
      item.progress = 0;
      this.updateQueueItemDisplay(item);

      const formData = new FormData();
      formData.append('file', item.file);
      formData.append('folder_id', this.currentFolderId || '');
      if (csrfToken) {
        formData.append('_token', csrfToken);
      }

      // Create XMLHttpRequest for progress tracking
      const xhr = new XMLHttpRequest();
      item.xhr = xhr;

      // Setup progress tracking
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && !item.cancelled) {
          item.progress = Math.round((e.loaded / e.total) * 100);
          this.updateQueueItemDisplay(item);
        }
      });

      // Setup completion handler
      const uploadPromise = new Promise((resolve, reject) => {
        xhr.addEventListener('load', () => {
          if (xhr.status === 200) {
            try {
              const result = JSON.parse(xhr.responseText);
              resolve(result);
            } catch (e) {
              reject(new Error('Invalid response format'));
            }
          } else {
            reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
          }
        });

        xhr.addEventListener('error', () => {
          reject(new Error('Network error'));
        });

        xhr.addEventListener('abort', () => {
          reject(new Error('Upload cancelled'));
        });
      });

      // Start upload
      xhr.open('POST', `/facilities/${this.facilityId}/documents/files`);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.send(formData);

      const result = await uploadPromise;

      if (result.success) {
        item.status = 'completed';
        item.progress = 100;
        item.uploadedFile = result.file;
        this.showSuccess(`${item.file.name} のアップロードが完了しました。`);

        // Trigger folder refresh
        document.dispatchEvent(new CustomEvent('refreshFolder'));
      } else {
        item.status = 'error';
        item.error = result.message || 'アップロードに失敗しました';
      }

    } catch (error) {
      if (item.cancelled) {
        item.status = 'cancelled';
        item.error = 'キャンセルされました';
      } else {
        item.status = 'error';
        item.error = error.message || 'アップロードに失敗しました';
      }
    } finally {
      item.xhr = null;
      this.updateQueueItemDisplay(item);
    }
  }

  async startUpload() {
    if (this.uploadQueue.length === 0) {
      this.showError('アップロードするファイルがありません。');
      return;
    }

    const uploadBtn = document.getElementById('uploadSubmit');
    const originalText = uploadBtn.textContent;
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>アップロード中...';

    try {
      await this.uploadFiles();
    } finally {
      uploadBtn.disabled = false;
      uploadBtn.textContent = originalText;
    }
  }

  async uploadFiles() {
    // Check if we should upload files individually or in batch
    if (this.uploadQueue.length === 1 || this.options.individualUpload) {
      await this.uploadFilesIndividually();
    } else {
      await this.uploadFilesBatch();
    }
  }

  async uploadFilesIndividually() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    for (const item of this.uploadQueue) {
      if (item.cancelled) continue;

      try {
        item.status = 'uploading';
        item.progress = 0;
        this.updateQueueItemDisplay(item);

        const formData = new FormData();
        formData.append('file', item.file);
        formData.append('folder_id', this.currentFolderId || '');
        if (csrfToken) {
          formData.append('_token', csrfToken);
        }

        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        item.xhr = xhr; // Store for cancellation

        // Setup progress tracking
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable && !item.cancelled) {
            item.progress = Math.round((e.loaded / e.total) * 100);
            this.updateQueueItemDisplay(item);
          }
        });

        // Setup completion handler
        const uploadPromise = new Promise((resolve, reject) => {
          xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
              try {
                const result = JSON.parse(xhr.responseText);
                resolve(result);
              } catch (e) {
                reject(new Error('Invalid response format'));
              }
            } else {
              reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
            }
          });

          xhr.addEventListener('error', () => {
            reject(new Error('Network error'));
          });

          xhr.addEventListener('abort', () => {
            reject(new Error('Upload cancelled'));
          });
        });

        // Start upload
        xhr.open('POST', `/facilities/${this.facilityId}/documents/files`);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);

        const result = await uploadPromise;

        if (result.success) {
          item.status = 'completed';
          item.progress = 100;
          item.uploadedFile = result.file;
        } else {
          item.status = 'error';
          item.error = result.message || 'アップロードに失敗しました';
        }

      } catch (error) {
        if (item.cancelled) {
          item.status = 'cancelled';
          item.error = 'キャンセルされました';
        } else {
          item.status = 'error';
          item.error = error.message || 'アップロードに失敗しました';
        }
      } finally {
        item.xhr = null;
        this.updateQueueItemDisplay(item);
      }
    }

    this.handleUploadCompletion();
  }

  async uploadFilesBatch() {
    const formData = new FormData();

    // Add files to form data
    this.uploadQueue.forEach((item, index) => {
      formData.append(`files[${index}]`, item.file);
    });

    // Add folder ID
    formData.append('folder_id', this.currentFolderId || '');

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
      formData.append('_token', csrfToken);
    }

    try {
      // Update all items to uploading status
      this.uploadQueue.forEach(item => {
        item.status = 'uploading';
        this.updateQueueItemDisplay(item);
      });

      const response = await fetch(`/facilities/${this.facilityId}/documents/files`, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();

      if (result.success) {
        // Mark all as completed
        this.uploadQueue.forEach(item => {
          item.status = 'completed';
          item.progress = 100;
          this.updateQueueItemDisplay(item);
        });

        this.showSuccess(result.message);
        this.handleUploadCompletion();

      } else {
        this.handleUploadErrors(result);
      }

    } catch (error) {
      console.error('Upload failed:', error);

      // Mark all as error
      this.uploadQueue.forEach(item => {
        item.status = 'error';
        item.error = 'アップロードに失敗しました';
        this.updateQueueItemDisplay(item);
      });

      this.showError('アップロード中にエラーが発生しました。');
    }
  }

  handleUploadCompletion() {
    const completedCount = this.uploadQueue.filter(item => item.status === 'completed').length;
    const errorCount = this.uploadQueue.filter(item => item.status === 'error').length;
    const cancelledCount = this.uploadQueue.filter(item => item.status === 'cancelled').length;

    if (completedCount > 0) {
      this.showSuccess(`${completedCount}個のファイルがアップロードされました。`);
    }

    if (errorCount > 0) {
      this.showError(`${errorCount}個のファイルでエラーが発生しました。`);
    }

    if (cancelledCount > 0) {
      this.showWarning(`${cancelledCount}個のファイルがキャンセルされました。`);
    }

    // Close modal after delay if all completed or errored
    if (completedCount + errorCount + cancelledCount === this.uploadQueue.length) {
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
        if (modal) modal.hide();
        this.clearQueue();
        // Trigger folder refresh
        document.dispatchEvent(new CustomEvent('refreshFolder'));
      }, 2000);
    }
  }

  handleUploadErrors(result) {
    if (result.errors && Array.isArray(result.errors)) {
      // Handle individual file errors
      result.errors.forEach(error => {
        const item = this.uploadQueue.find(item => item.file.name === error.file);
        if (item) {
          item.status = 'error';
          item.error = error.error;
          this.updateQueueItemDisplay(item);
        }
      });

      // Mark successful files as completed
      if (result.files && Array.isArray(result.files)) {
        result.files.forEach(file => {
          const item = this.uploadQueue.find(item => item.file.name === file.name);
          if (item) {
            item.status = 'completed';
            item.progress = 100;
            this.updateQueueItemDisplay(item);
          }
        });
      }

      if (result.partial) {
        this.showWarning(result.message);
      } else {
        this.showError(result.message);
      }
    } else {
      // Mark all as error
      this.uploadQueue.forEach(item => {
        item.status = 'error';
        item.error = result.message || 'アップロードに失敗しました';
        this.updateQueueItemDisplay(item);
      });

      this.showError(result.message || 'アップロードに失敗しました。');
    }
  }

  updateQueueItemDisplay(item) {
    const queueItem = document.querySelector(`[data-id="${item.id}"]`);
    if (queueItem) {
      // Re-render the entire item for consistency
      queueItem.outerHTML = this.renderQueueItem(item);

      // Update bulk actions and stats
      this.updateQueueStats();
    }
  }

  updateQueueStats() {
    const modal = document.getElementById('uploadModal');
    const queueContainer = modal?.querySelector('.upload-queue');
    if (!queueContainer) return;

    const stats = this.getUploadStats();

    // Update stats display
    const statsElement = queueContainer.querySelector('.upload-stats small');
    if (statsElement) {
      const statsText = [
        stats.pending > 0 ? `待機中: ${stats.pending}個` : '',
        stats.uploading > 0 ? `アップロード中: ${stats.uploading}個` : '',
        stats.completed > 0 ? `完了: ${stats.completed}個` : '',
        stats.error > 0 ? `エラー: ${stats.error}個` : '',
        stats.cancelled > 0 ? `キャンセル: ${stats.cancelled}個` : ''
      ].filter(text => text).join(' ');

      statsElement.textContent = statsText;
    }

    // Update bulk actions
    const bulkActionsContainer = queueContainer.querySelector('.btn-group');
    if (bulkActionsContainer) {
      const newBulkActions = this.renderBulkActions(stats);
      if (newBulkActions) {
        bulkActionsContainer.outerHTML = newBulkActions;
      } else {
        bulkActionsContainer.remove();
      }
    } else if (stats.total > 1) {
      // Add bulk actions if they don't exist but should
      const headerDiv = queueContainer.querySelector('.d-flex');
      if (headerDiv) {
        const bulkActions = this.renderBulkActions(stats);
        if (bulkActions) {
          headerDiv.insertAdjacentHTML('beforeend', bulkActions);
        }
      }
    }
  }

  clearQueue() {
    // Cancel any active uploads
    this.uploadQueue.forEach(item => {
      if (item.xhr) {
        item.xhr.abort();
      }
    });

    this.uploadQueue = [];
    const fileInput = document.getElementById('files');
    if (fileInput) {
      fileInput.value = '';
    }
  }

  // Bulk operations
  cancelAllUploads() {
    this.uploadQueue.forEach(item => {
      if (item.status === 'uploading' || item.status === 'pending') {
        this.cancelUpload(item.id);
      }
    });
  }

  retryAllFailed() {
    const failedItems = this.uploadQueue.filter(item =>
      item.status === 'error' || item.status === 'cancelled'
    );

    failedItems.forEach(item => {
      this.retryUpload(item.id);
    });
  }

  removeAllCompleted() {
    const completedIds = this.uploadQueue
      .filter(item => item.status === 'completed')
      .map(item => item.id);

    completedIds.forEach(id => {
      this.removeFromQueue(id);
    });
  }

  removeAllFailed() {
    const failedIds = this.uploadQueue
      .filter(item => item.status === 'error' || item.status === 'cancelled')
      .map(item => item.id);

    failedIds.forEach(id => {
      this.removeFromQueue(id);
    });
  }

  // Get upload statistics
  getUploadStats() {
    const stats = {
      total: this.uploadQueue.length,
      pending: 0,
      uploading: 0,
      completed: 0,
      error: 0,
      cancelled: 0
    };

    this.uploadQueue.forEach(item => {
      stats[item.status]++;
    });

    return stats;
  }

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }

  showSuccess(message) {
    this.showNotification(message, 'success');
  }

  showError(message) {
    this.showNotification(message, 'error');
  }

  showWarning(message) {
    this.showNotification(message, 'warning');
  }

  showNotification(message, type) {
    // Use existing notification system if available
    if (window.showSuccess && type === 'success') {
      window.showSuccess(message);
    } else if (window.showError && type === 'error') {
      window.showError(message);
    } else if (window.showWarning && type === 'warning') {
      window.showWarning(message);
    } else {
      // Fallback to alert
      alert(message);
    }
  }

  // Public method to set current folder
  setCurrentFolder(folderId) {
    this.currentFolderId = folderId;
  }

  // Cleanup method
  destroy() {
    // Cancel all active uploads
    this.uploadQueue.forEach(item => {
      if (item.xhr) {
        item.xhr.abort();
      }
    });

    // Clear queue
    this.uploadQueue = [];
    this.activeUploads.clear();

    // Remove event listeners
    const dropZone = document.getElementById('documentList');
    if (dropZone) {
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.removeEventListener(eventName, this.preventDefaults);
      });
    }

    // Clear file input
    const fileInput = document.getElementById('files');
    if (fileInput) {
      fileInput.value = '';
    }
  }
}

// Make it available globally for onclick handlers
window.documentUpload = null;