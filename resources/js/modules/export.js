/**
 * Export Module - ES6 Module
 * Handles CSV export functionality including field selection and favorites
 */

// Global flag to prevent duplicate form submissions
let globalSubmissionInProgress = false;

/**
 * CSV Download Manager
 * Handles CSV download functionality with iframe-based approach
 */
class CsvDownloadManager {
  constructor(exportManager = null) {
    // Download timeout constants
    this.DOWNLOAD_TIMEOUTS = {
      DOWNLOAD_START_DELAY: 800,     // Time to wait after iframe load before completing
      FALLBACK_TIMEOUT: 3000,       // Maximum time to wait for download
      ERROR_CHECK_DELAY: 300,       // Time to wait before checking for errors
      PROCESSING_UPDATE_DELAY: 200, // Time to wait before updating processing text
      CLEANUP_DELAY: 500            // Time to wait before cleaning up elements
    };

    this.DOWNLOAD_MESSAGES = {
      PREPARING: '出力準備中...',
      GENERATING: 'CSV生成中...',
      DOWNLOADING: 'ダウンロード中...',
      SUCCESS: 'CSVファイルのダウンロードを開始しました。',
      ERROR: 'CSV出力中にエラーが発生しました。再度お試しください（改善しない場合は条件を見直してください）。'
    };

    // Prevent duplicate downloads
    this.isDownloading = false;
    this.exportManager = exportManager;
  }

  /**
   * Start CSV download process
   */
  downloadCSV(selectedFacilities, selectedFields, exportButton) {
    console.log('CsvDownloadManager: Starting download process');

    // Prevent duplicate downloads
    if (this.isDownloading) {
      console.log('CsvDownloadManager: Download already in progress, ignoring duplicate request');
      return;
    }

    this.isDownloading = true;
    console.log('CsvDownloadManager: Setting isDownloading to true');

    const downloadContext = this.createDownloadContext(selectedFacilities, selectedFields);
    this.setupDownloadHandlers(downloadContext, exportButton);
    this.submitDownloadForm(downloadContext, exportButton);
  }

  /**
   * Create download context with iframe and form
   */
  createDownloadContext(selectedFacilities, selectedFields) {
    const downloadId = 'download_' + Date.now();

    const iframe = this.createDownloadIframe(downloadId);
    const form = this.createDownloadForm(downloadId, selectedFacilities, selectedFields);

    return {
      iframe,
      form,
      downloadStarted: false,
      downloadCompleted: false
    };
  }

  /**
   * Create hidden iframe for download
   */
  createDownloadIframe(downloadId) {
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.id = downloadId;
    document.body.appendChild(iframe);
    return iframe;
  }

  /**
   * Create form with all necessary data
   */
  createDownloadForm(targetId, selectedFacilities, selectedFields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/export/csv/generate';
    form.target = targetId;
    form.style.display = 'none';

    // Add CSRF token
    this.addFormInput(form, '_token', this.getCsrfToken());

    // Add facility IDs
    selectedFacilities.forEach(facilityId => {
      this.addFormInput(form, 'facility_ids[]', facilityId);
    });

    // Add export fields
    selectedFields.forEach(field => {
      this.addFormInput(form, 'export_fields[]', field);
    });

    return form;
  }

  /**
   * Add hidden input to form
   */
  addFormInput(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  }

  /**
   * Get CSRF token from meta tag
   */
  getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  /**
   * Heuristics: determine if HTML looks like an error page
   */
  isLikelyErrorHtml(content = '') {
    if (!content || typeof content !== 'string') return false;
    const lowered = content.toLowerCase();
    // Common error keywords (en/ja) and framework markers
    const patterns = [
      'error', 'エラー', 'exception', 'stack trace', 'whoops',
      'symfony', 'laravel', 'php warning', 'php fatal',
      'invalid', 'forbidden', 'unauthorized', 'not found', '内部サーバーエラー'
    ];
    return patterns.some(p => lowered.includes(p));
  }

  /**
   * Setup download event handlers and timeouts
   */
  setupDownloadHandlers(context, exportButton) {
    this.setupIframeLoadHandler(context, exportButton);
    this.setupFallbackTimeout(context, exportButton);
    this.setupErrorCheckTimeout(context, exportButton);
  }

  /**
   * Setup iframe load event handler
   */
  setupIframeLoadHandler(context, exportButton) {
    let loadEventFired = false;

    // Explicit network/load error on iframe (rare but supported by some browsers)
    context.iframe.addEventListener('error', () => {
      if (context.downloadCompleted) return;
      this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.ERROR, 'error');
    });

    context.iframe.addEventListener('load', () => {
      if (loadEventFired || context.downloadCompleted) return; // Prevent multiple executions

      loadEventFired = true;
      // Try to inspect the iframe document for server-side errors
      try {
        const iframeDoc = context.iframe.contentDocument || context.iframe.contentWindow?.document;
        if (this.hasErrorContent(iframeDoc)) {
          this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.ERROR, 'error');
          return;
        }
      } catch (e) {
        // Cross-origin case: cannot inspect — this usually means download started OK
        // We will proceed as success after short delay.
      }

      context.downloadStarted = true;
      this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.DOWNLOADING);

      // Finalize if nothing bad happened shortly after load
      setTimeout(() => {
        if (!context.downloadCompleted) {
          this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.SUCCESS, 'success');
        }
      }, this.DOWNLOAD_TIMEOUTS.DOWNLOAD_START_DELAY);
    });
  }

  /**
   * Setup fallback timeout
   */
  setupFallbackTimeout(context, exportButton) {
    setTimeout(() => {
      if (!context.downloadCompleted) {
        this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.SUCCESS, 'success');
      }
    }, this.DOWNLOAD_TIMEOUTS.FALLBACK_TIMEOUT);
  }

  /**
   * Setup error checking timeout
   */
  setupErrorCheckTimeout(context, exportButton) {
    setTimeout(() => {
      if (context.downloadCompleted) return;
      try {
        const iframeDoc = context.iframe.contentDocument || context.iframe.contentWindow?.document;
        if (this.hasErrorContent(iframeDoc)) {
          this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.ERROR, 'error');
          return;
        }
      } catch (e) {
        // Cross-origin — cannot inspect; do nothing here
      }
      // If we reach here, either no error or unverifiable (likely OK). Keep waiting for normal flow/fallback.
    }, this.DOWNLOAD_TIMEOUTS.ERROR_CHECK_DELAY);
  }

  /**
   * Check for download errors in iframe
   */
  checkForDownloadErrors(context, exportButton) {
    try {
      const iframeDoc = context.iframe.contentDocument || context.iframe.contentWindow.document;
      if (this.hasErrorContent(iframeDoc)) {
        this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.ERROR, 'error');
        return;
      }
    } catch (e) {
      // Cross-origin access issues are normal for successful downloads
      console.log('Cannot access iframe content (normal for successful downloads)');
    }

    // If we can't access iframe content, assume download started successfully
    if (!context.downloadCompleted) {
      context.downloadStarted = true;
      this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.DOWNLOADING);
    }
  }

  /**
   * Check if iframe contains error content
   */
  hasErrorContent(iframeDoc) {
    // No document or empty body → not an error we can detect
    if (!iframeDoc || !iframeDoc.body) return false;
    const html = iframeDoc.body.innerHTML || '';
    // Ignore very small/empty bodies (download streams sometimes leave a blank doc)
    if (html.trim().length < 20) return false;
    return this.isLikelyErrorHtml(html);
  }

  /**
   * Complete download process
   */
  completeDownload(context, exportButton, message, type) {
    console.log('CsvDownloadManager: completeDownload called', { message, type, downloadCompleted: context.downloadCompleted });

    if (context.downloadCompleted) {
      console.log('CsvDownloadManager: Download already completed, ignoring');
      return; // Already completed
    }

    console.log('CsvDownloadManager: Completing download process');
    context.downloadCompleted = true;
    this.isDownloading = false;

    // Reset ExportManager submission flag if available
    if (this.exportManager && this.exportManager.isSubmitting) {
      this.exportManager.isSubmitting = false;
      console.log('CsvDownloadManager: Reset ExportManager isSubmitting flag');
    }

    // Reset global submission flag
    globalSubmissionInProgress = false;
    console.log('CsvDownloadManager: Reset global submission flag');

    this.resetButtonState(exportButton);
    this.showToast(message, type);
    this.cleanupDownload(context.form, context.iframe);
  }

  /**
   * Submit download form
   */
  submitDownloadForm(context, exportButton) {
    // Set initial button state
    this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.PREPARING);

    document.body.appendChild(context.form);
    context.form.submit();

    // Update button text to indicate processing
    setTimeout(() => {
      if (!context.downloadStarted && !context.downloadCompleted) {
        this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.GENERATING);
      }
    }, this.DOWNLOAD_TIMEOUTS.PROCESSING_UPDATE_DELAY);
  }

  /**
   * Update button loading state
   */
  updateButtonState(button, message) {
    console.log('CsvDownloadManager: updateButtonState called', { message });

    if (!button) return;

    // Store original content if not already stored
    if (!button.dataset.originalContent) {
      button.dataset.originalContent = button.innerHTML;
    }

    button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${message}`;
    button.disabled = true;
  }

  /**
   * Reset button to normal state
   */
  resetButtonState(button) {
    console.log('CsvDownloadManager: resetButtonState called', { button: !!button, hasOriginalContent: !!(button?.dataset.originalContent) });

    if (!button) return;

    if (button.dataset.originalContent) {
      console.log('CsvDownloadManager: Restoring button original content');
      button.innerHTML = button.dataset.originalContent;
      delete button.dataset.originalContent;
    }
    button.disabled = false;
    console.log('CsvDownloadManager: Button state reset complete');
  }

  /**
   * Show toast message
   */
  showToast(message, type) {
    // Simple toast implementation
    console.log(`Toast: ${type} - ${message}`);

    // Create a simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 3000);
  }

  /**
   * Clean up download elements
   */
  cleanupDownload(form, iframe) {
    setTimeout(() => {
      try {
        if (form && form.parentNode) {
          form.parentNode.removeChild(form);
        }
        if (iframe && iframe.parentNode) {
          iframe.parentNode.removeChild(iframe);
        }
      } catch (e) {
        console.warn('Error cleaning up download elements:', e);
      }
    }, this.DOWNLOAD_TIMEOUTS.CLEANUP_DELAY);
  }
}

export class ExportManager {
  constructor() {
    // Prevent duplicate initialization
    if (ExportManager.instance) {
      console.log('ExportManager already exists, returning existing instance');
      return ExportManager.instance;
    }

    console.log('Creating new ExportManager instance');
    ExportManager.instance = this;
    this.isSubmitting = false; // Prevent duplicate form submissions
    this.setupModalFixes();
    this.init();
  }

  refreshElements() {
    this.facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
    this.fieldCheckboxes = document.querySelectorAll('.field-checkbox');
    this.categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    this.subcategoryCheckboxes = document.querySelectorAll('.subcategory-checkbox');
    this.exportButton = document.getElementById('exportButton');
    this.saveFavoriteButton = document.getElementById('saveFavoriteButton');
  }

  setupModalFixes() {
    // Insert a small CSS patch once (idempotent)
    if (!document.getElementById('export-modal-zpatch')) {
      const style = document.createElement('style');
      style.id = 'export-modal-zpatch';
      style.textContent = `
        /* Put modals at the very top above any app chrome */
        .modal { z-index: 2055; }
        .modal-backdrop { z-index: 2050; }
        /* Ensure modal content is always clickable */
        .modal.show, .modal.show .modal-dialog, .modal.show .modal-content { pointer-events: auto; }
      `;
      document.head.appendChild(style);
    }

    // Lightweight hidden cleanup only when *all* modals are closed
    this.cleanupModalState = () => {
      if (document.querySelector('.modal.show')) return; // some modal still open
      document.querySelectorAll('.modal-backdrop:not(.fade)').forEach(b => b.remove());
    };

    // On any modal show: close obstructive UI and normalize stacking context
    document.addEventListener('show.bs.modal', (ev) => {
      // Close any open Bootstrap dropdowns that might sit above the backdrop
      document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));

      // Hide any offcanvas that may overlap the modal
      document.querySelectorAll('.offcanvas.show').forEach(el => {
        try { bootstrap.Offcanvas.getInstance(el)?.hide(); } catch (_) { }
      });

      // Normalize z-index & pointer events for the just-opened modal/backdrop
      const modalEl = ev.target;
      // Reparent into body to escape any stacking/transform contexts from ancestors
      if (modalEl && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
      }
      if (modalEl && modalEl.classList.contains('modal')) {
        modalEl.style.zIndex = '2055';
        setTimeout(() => {
          document.querySelectorAll('.modal-backdrop').forEach(b => {
            b.style.zIndex = '2050';
            b.style.pointerEvents = 'auto';
          });
          modalEl.style.pointerEvents = 'auto';
          const dialog = modalEl.querySelector('.modal-dialog');
          const content = modalEl.querySelector('.modal-content');
          if (dialog) dialog.style.pointerEvents = 'auto';
          if (content) content.style.pointerEvents = 'auto';
        }, 0);
      }
    });

    // When a modal gets fully hidden, do a light cleanup (if none remain)
    document.addEventListener('hidden.bs.modal', () => {
      setTimeout(() => this.cleanupModalState(), 50);
    });
  }





  loadStaticFavoritesList() {
    console.log('ExportManager: Loading static favorites list for testing');
    const container = document.getElementById('favoritesList');

    if (!container) {
      console.error('ExportManager: favoritesList container not found');
      return;
    }

    // Display static test content (with delegated event handlers)
    container.innerHTML = `
      <div class="alert alert-info">
        <h6>テスト用お気に入り一覧</h6>
        <p>モーダルの基本動作確認用のダミーデータです（ボタンは委譲イベントで動きます）。</p>
      </div>
      <div class="list-group">
        <div class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-1">テストお気に入り1</h6>
            <small class="text-muted">施設: 3件 | 項目: 5項目 | 作成: ${new Date().toLocaleDateString()}</small>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-action="load" data-favorite-id="101">
              読み込み
            </button>
            <button type="button" class="btn btn-outline-secondary" data-action="edit" data-favorite-id="101" data-favorite-name="テストお気に入り1">
              編集
            </button>
            <button type="button" class="btn btn-outline-danger" data-action="delete" data-favorite-id="101">
              削除
            </button>
          </div>
        </div>
        <div class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-1">テストお気に入り2</h6>
            <small class="text-muted">施設: 2件 | 項目: 8項目 | 作成: ${new Date().toLocaleDateString()}</small>
          </div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-action="load" data-favorite-id="102">
              読み込み
            </button>
            <button type="button" class="btn btn-outline-secondary" data-action="edit" data-favorite-id="102" data-favorite-name="テストお気に入り2">
              編集
            </button>
            <button type="button" class="btn btn-outline-danger" data-action="delete" data-favorite-id="102">
              削除
            </button>
          </div>
        </div>
      </div>
      <div class="mt-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
      </div>
    `;

    console.log('ExportManager: Static favorites list loaded');
  }

  init() {
    this.refreshElements();
    this.setupEventListeners();
    this.setupFacilityFilters();
    this.updateSelectionStatus();
  }

  setupFacilityFilters() {
    const filterSection = document.getElementById('filterSection');
    const filterPrefecture = document.getElementById('filterPrefecture');
    const filterKeyword = document.getElementById('filterKeyword');
    const clearFilters = document.getElementById('clearFilters');

    if (!filterSection || !filterPrefecture || !filterKeyword) {
      console.log('ExportManager: Filter elements not found, skipping filter setup');
      return;
    }

    // Apply filters on change
    const applyFilters = () => {
      const section = filterSection.value.toLowerCase();
      const prefecture = filterPrefecture.value.toLowerCase();
      const keyword = filterKeyword.value.toLowerCase();

      const facilityItems = document.querySelectorAll('.facility-item');
      let visibleCount = 0;

      facilityItems.forEach(item => {
        const itemSection = (item.dataset.section || '').toLowerCase();
        const itemPrefecture = (item.dataset.prefecture || '').toLowerCase();
        const itemName = (item.dataset.facilityName || '').toLowerCase();
        const itemCompany = (item.dataset.companyName || '').toLowerCase();
        const itemCode = (item.dataset.officeCode || '').toLowerCase();
        const itemAddress = (item.dataset.address || '').toLowerCase();

        let visible = true;

        // Section filter
        if (section) {
          // Handle combined section filter
          if (section === '有料老人ホーム・グループホーム') {
            visible = visible && (itemSection === '有料老人ホーム' || itemSection === 'グループホーム');
          } else {
            visible = visible && itemSection === section;
          }
        }

        // Prefecture filter
        if (prefecture) {
          visible = visible && itemPrefecture === prefecture;
        }

        // Keyword filter
        if (keyword) {
          visible = visible && (
            itemName.includes(keyword) ||
            itemCompany.includes(keyword) ||
            itemCode.includes(keyword) ||
            itemAddress.includes(keyword)
          );
        }

        item.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
      });

      // Update visible count
      const visibleCountElement = document.getElementById('visibleFacilitiesCount');
      if (visibleCountElement) {
        visibleCountElement.textContent = visibleCount;
      }

      // Update selection status
      this.updateSelectionStatus();
    };

    // Attach event listeners
    filterSection.addEventListener('change', applyFilters);
    filterPrefecture.addEventListener('change', applyFilters);
    filterKeyword.addEventListener('input', applyFilters);

    // Clear filters button
    if (clearFilters) {
      clearFilters.addEventListener('click', () => {
        filterSection.value = '';
        filterPrefecture.value = '';
        filterKeyword.value = '';
        applyFilters();
      });
    }
  }

  setupEventListeners() {
    // Monitor checkbox changes
    this.facilityCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', () => this.updateSelectionStatus());
    });

    this.fieldCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => {
        this.handleFieldChange(e);
        this.updateSelectionStatus();
      });
    });

    // Category and subcategory checkbox handlers
    this.categoryCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => this.handleCategoryChange(e));
    });

    this.subcategoryCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => this.handleSubcategoryChange(e));
    });

    // Select all/deselect all buttons
    this.setupSelectAllButtons();

    // Favorite functionality
    this.setupFavoriteHandlers();

    // Form submission
    this.setupFormSubmission();
  }

  setupSelectAllButtons() {
    const selectAllFacilities = document.getElementById('selectAllFacilities');
    const deselectAllFacilities = document.getElementById('deselectAllFacilities');
    const selectAllFields = document.getElementById('selectAllFields');
    const deselectAllFields = document.getElementById('deselectAllFields');

    if (selectAllFacilities) {
      selectAllFacilities.addEventListener('click', () => {
        // Only select visible facilities
        this.facilityCheckboxes.forEach(cb => {
          const facilityItem = cb.closest('.facility-item');
          if (!facilityItem || facilityItem.style.display !== 'none') {
            cb.checked = true;
          }
        });
        this.updateSelectionStatus();
      });
    }

    if (deselectAllFacilities) {
      deselectAllFacilities.addEventListener('click', () => {
        // Only deselect visible facilities
        this.facilityCheckboxes.forEach(cb => {
          const facilityItem = cb.closest('.facility-item');
          if (!facilityItem || facilityItem.style.display !== 'none') {
            cb.checked = false;
          }
        });
        this.updateSelectionStatus();
      });
    }

    if (selectAllFields) {
      selectAllFields.addEventListener('click', () => {
        this.fieldCheckboxes.forEach(cb => cb.checked = true);
        this.updateSelectionStatus();
      });
    }

    if (deselectAllFields) {
      deselectAllFields.addEventListener('click', () => {
        this.fieldCheckboxes.forEach(cb => cb.checked = false);
        this.updateSelectionStatus();
      });
    }
  }

  setupFavoriteHandlers() {
    // Save favorite button
    if (this.saveFavoriteButton) {
      this.saveFavoriteButton.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('saveFavoriteModal'));
        modal.show();
      });
    }

    // Save favorite confirmation
    const saveFavoriteConfirm = document.getElementById('saveFavoriteConfirm');
    if (saveFavoriteConfirm) {
      saveFavoriteConfirm.addEventListener('click', () => this.saveFavorite());
    }

    // Load favorites when modal opens
    const favoritesModal = document.getElementById('favoritesModal');
    if (favoritesModal) {
      // Remove existing listener if any
      favoritesModal.removeEventListener('show.bs.modal', this.boundLoadFavoritesList);

      // Bind the method to preserve 'this' context
      this.boundLoadFavoritesList = () => {
        console.log('ExportManager: Favorites modal opening, loading favorites list');
        try {
          // Test with static content first
          this.loadStaticFavoritesList();
        } catch (error) {
          console.error('ExportManager: Error in boundLoadFavoritesList:', error);
        }
      };

      // Add the new event listener
      favoritesModal.addEventListener('show.bs.modal', this.boundLoadFavoritesList);
      console.log('ExportManager: Favorites modal event listener added');
    } else {
      console.error('ExportManager: favoritesModal element not found');
    }
  }

  setupFormSubmission() {
    const csvExportForm = document.getElementById('csvExportForm');
    if (csvExportForm) {
      // Remove any existing event listeners
      csvExportForm.removeEventListener('submit', this.boundHandleFormSubmission);

      // Bind the method to preserve 'this' context
      this.boundHandleFormSubmission = (e) => this.handleFormSubmission(e);

      // Add the new event listener
      csvExportForm.addEventListener('submit', this.boundHandleFormSubmission);

      console.log('ExportManager: Form submission event listener bound');
    }
  }

  updateSelectionStatus() {
    const selectedFacilities = document.querySelectorAll('.facility-checkbox:checked');
    const selectedFields = document.querySelectorAll('.field-checkbox:checked');

    // Update counts
    const facilitiesCountElement = document.getElementById('selectedFacilitiesCount');
    const fieldsCountElement = document.getElementById('selectedFieldsCount');

    if (facilitiesCountElement) {
      facilitiesCountElement.textContent = selectedFacilities.length;
    }
    if (fieldsCountElement) {
      fieldsCountElement.textContent = selectedFields.length;
    }

    // Update button states
    const canExport = selectedFacilities.length > 0 && selectedFields.length > 0;
    if (this.exportButton) {
      this.exportButton.disabled = !canExport;
    }
    if (this.saveFavoriteButton) {
      this.saveFavoriteButton.disabled = !canExport;
    }

    // Update category and subcategory counts
    this.updateCategoryCounts();
  }

  handleCategoryChange(event) {
    const checkbox = event.target;
    const category = checkbox.dataset.category;
    const isChecked = checkbox.checked;

    // Prevent automatic state update during manual category change
    this._manualCategoryChange = true;

    // Select/deselect all fields in this category
    const categoryFields = document.querySelectorAll(`[data-category="${category}"].field-checkbox`);
    categoryFields.forEach(field => {
      field.checked = isChecked;
    });

    // If this is a parent category with subcategories, handle them too (lifeline/contract)
    if (['lifeline', 'contract'].includes(category)) {
      const subcategoryCheckboxes = document.querySelectorAll(`[data-parent-category="${category}"]`);
      subcategoryCheckboxes.forEach(subcategory => {
        subcategory.checked = isChecked;
        this.selectSubcategoryFields(subcategory.dataset.subcategory, isChecked);
      });
    }

    // Reset the flag after a short delay
    setTimeout(() => {
      this._manualCategoryChange = false;
    }, 10);

    this.updateSelectionStatus();
  }

  handleSubcategoryChange(event) {
    const checkbox = event.target;
    const subcategory = checkbox.dataset.subcategory;
    const parentCategory = checkbox.dataset.parentCategory;
    const isChecked = checkbox.checked;

    // Prevent automatic state update during manual subcategory change
    this._manualSubcategoryChange = true;

    // Select/deselect all fields in this subcategory
    this.selectSubcategoryFields(subcategory, isChecked);

    // Update parent category state
    this.updateParentCategoryState(parentCategory);

    // Reset the flag after a short delay
    setTimeout(() => {
      this._manualSubcategoryChange = false;
    }, 10);

    this.updateSelectionStatus();
  }

  selectSubcategoryFields(subcategory, isChecked) {
    const subcategoryFields = document.querySelectorAll(`[data-subcategory="${subcategory}"].field-checkbox`);
    subcategoryFields.forEach(field => {
      field.checked = isChecked;
    });
  }

  handleFieldChange(event) {
    const checkbox = event.target;
    const category = checkbox.dataset.category;
    const subcategory = checkbox.dataset.subcategory;

    // Update subcategory state if this field belongs to one
    if (subcategory) {
      this.updateSubcategoryState(subcategory);
    }

    // Update parent category state
    if (category) {
      this.updateCategoryState(category);
    }
  }

  updateSubcategoryState(subcategory) {
    const subcategoryCheckbox = document.querySelector(`[data-subcategory="${subcategory}"].subcategory-checkbox`);
    if (!subcategoryCheckbox) return;

    const allSubcategoryFields = document.querySelectorAll(`[data-subcategory="${subcategory}"].field-checkbox`);
    const checkedSubcategoryFields = document.querySelectorAll(`[data-subcategory="${subcategory}"].field-checkbox:checked`);

    if (checkedSubcategoryFields.length === 0) {
      subcategoryCheckbox.checked = false;
      subcategoryCheckbox.indeterminate = false;
    } else if (checkedSubcategoryFields.length === allSubcategoryFields.length) {
      subcategoryCheckbox.checked = true;
      subcategoryCheckbox.indeterminate = false;
    } else {
      subcategoryCheckbox.checked = false;
      subcategoryCheckbox.indeterminate = true;
    }

    // Update parent category if this is a subcategory
    const parentCategory = subcategoryCheckbox.dataset.parentCategory;
    if (parentCategory) {
      this.updateParentCategoryState(parentCategory);
    }
  }

  updateCategoryState(category) {
    const categoryCheckbox = document.querySelector(`[data-category="${category}"].category-checkbox`);
    if (!categoryCheckbox) return;

    // Skip if this category has subcategories (handled by updateParentCategoryState)
    if (['lifeline', 'contract'].includes(category)) return;

    const allCategoryFields = document.querySelectorAll(`[data-category="${category}"].field-checkbox`);
    const checkedCategoryFields = document.querySelectorAll(`[data-category="${category}"].field-checkbox:checked`);

    if (checkedCategoryFields.length === 0) {
      categoryCheckbox.checked = false;
      categoryCheckbox.indeterminate = false;
    } else if (checkedCategoryFields.length === allCategoryFields.length) {
      categoryCheckbox.checked = true;
      categoryCheckbox.indeterminate = false;
    } else {
      categoryCheckbox.checked = false;
      categoryCheckbox.indeterminate = true;
    }
  }

  updateParentCategoryState(parentCategory) {
    const subcategoryCheckboxes = document.querySelectorAll(`[data-parent-category="${parentCategory}"]`);
    const checkedSubcategories = document.querySelectorAll(`[data-parent-category="${parentCategory}"]:checked`);
    const parentCheckbox = document.querySelector(`[data-category="${parentCategory}"].category-checkbox`);

    if (parentCheckbox) {
      if (checkedSubcategories.length === 0) {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = false;
      } else if (checkedSubcategories.length === subcategoryCheckboxes.length) {
        parentCheckbox.checked = true;
        parentCheckbox.indeterminate = false;
      } else {
        parentCheckbox.checked = false;
        parentCheckbox.indeterminate = true;
      }
    }
  }

  updateCategoryCounts() {
    // Update category counts
    this.categoryCheckboxes.forEach(categoryCheckbox => {
      const category = categoryCheckbox.dataset.category;
      const categoryFields = document.querySelectorAll(`[data-category="${category}"].field-checkbox:checked`);
      const countElement = document.querySelector(`[data-category="${category}"].category-count`);

      if (countElement) {
        countElement.textContent = categoryFields.length;
      }

      // Only update category checkbox state if not during manual change
      if (!this._manualCategoryChange) {
        const allCategoryFields = document.querySelectorAll(`[data-category="${category}"].field-checkbox`);
        if (allCategoryFields.length > 0) {
          if (categoryFields.length === 0) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
          } else if (categoryFields.length === allCategoryFields.length) {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = false;
          } else {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = true;
          }
        }
      }
    });

    // Update subcategory counts
    this.subcategoryCheckboxes.forEach(subcategoryCheckbox => {
      const subcategory = subcategoryCheckbox.dataset.subcategory;
      const subcategoryFields = document.querySelectorAll(`[data-subcategory="${subcategory}"].field-checkbox:checked`);
      const countElement = document.querySelector(`[data-subcategory="${subcategory}"].subcategory-count`);

      if (countElement) {
        countElement.textContent = subcategoryFields.length;
      }

      // Only update subcategory checkbox state if not during manual change
      if (!this._manualSubcategoryChange) {
        const allSubcategoryFields = document.querySelectorAll(`[data-subcategory="${subcategory}"].field-checkbox`);
        if (allSubcategoryFields.length > 0) {
          if (subcategoryFields.length === 0) {
            subcategoryCheckbox.checked = false;
            subcategoryCheckbox.indeterminate = false;
          } else if (subcategoryFields.length === allSubcategoryFields.length) {
            subcategoryCheckbox.checked = true;
            subcategoryCheckbox.indeterminate = false;
          } else {
            subcategoryCheckbox.checked = false;
            subcategoryCheckbox.indeterminate = true;
          }
        }
      }
    });

    // Update lifeline category count (sum of all subcategories)
    const lifelineCategoryCount = document.querySelector('[data-category="lifeline"].category-count');
    if (lifelineCategoryCount) {
      const allLifelineFields = document.querySelectorAll('[data-category="lifeline"].field-checkbox:checked');
      lifelineCategoryCount.textContent = allLifelineFields.length;
    }
  }

  async saveFavorite() {
    const nameInput = document.getElementById('favoriteName');
    const confirmBtn = document.getElementById('saveFavoriteConfirm');

    if (!nameInput) return;

    const name = nameInput.value.trim();
    if (!name) {
      alert('お気に入り名を入力してください。');
      return;
    }

    const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
    const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

    if (selectedFacilities.length === 0 || selectedFields.length === 0) {
      alert('施設と出力項目を選択してください。');
      return;
    }

    // Disable button while saving
    const originalBtnHtml = confirmBtn ? confirmBtn.innerHTML : null;
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>保存中...';
    }

    try {
      // Use FormData to match typical Laravel expectations
      const fd = new FormData();
      fd.append('_token', this.getCsrfToken());
      fd.append('name', name);
      selectedFacilities.forEach(id => fd.append('facility_ids[]', id));
      selectedFields.forEach(f => fd.append('export_fields[]', f));

      const response = await fetch('/export/csv/favorites', {
        method: 'POST',
        body: fd,
        headers: {
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });

      // Try to parse json safely even on non-2xx
      let data;
      const text = await response.text();
      try { data = text ? JSON.parse(text) : {}; } catch (_) { data = { success: false, message: text || '不明なエラーが発生しました。' }; }

      if (response.status === 419 || response.status === 403) {
        alert('セッションが失効した可能性があります。ページを再読み込みして、もう一度お試しください。');
        return;
      }

      if (response.ok && data && data.success) {
        alert(data.message || 'お気に入りを保存しました。');
        nameInput.value = '';
        const modal = bootstrap.Modal.getInstance(document.getElementById('saveFavoriteModal'));
        if (modal) modal.hide();
        // 再読み込み
        this.loadFavoritesList();
      } else {
        const msg = (data && (data.message || (data.errors && Object.values(data.errors).flat().join('\n')))) || 'お気に入りの保存に失敗しました。';
        alert(msg);
      }
    } catch (error) {
      console.error('お気に入り保存エラー:', error);
      alert('お気に入りの保存中にエラーが発生しました。ネットワーク状態をご確認ください。');
    } finally {
      if (confirmBtn) {
        confirmBtn.disabled = false;
        if (originalBtnHtml) confirmBtn.innerHTML = originalBtnHtml;
      }
    }
  }

  async loadFavoritesList() {
    console.log('ExportManager: Loading favorites list');
    const container = document.getElementById('favoritesList');

    if (!container) {
      console.error('ExportManager: favoritesList container not found');
      return;
    }

    // Show loading state
    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 読み込み中...</div>';

    try {
      // Test with dummy data first to verify modal functionality
      console.log('ExportManager: Testing with dummy data');
      const testData = {
        success: true,
        data: [
          {
            id: 1,
            name: 'テストお気に入り1',
            facility_ids: [1, 2, 3],
            export_fields: ['facility_name', 'address'],
            created_at: new Date().toISOString()
          },
          {
            id: 2,
            name: 'テストお気に入り2',
            facility_ids: [4, 5],
            export_fields: ['facility_name', 'phone_number'],
            created_at: new Date().toISOString()
          }
        ]
      };

      console.log('ExportManager: Using test data:', testData);
      this.displayFavoritesList(testData.data);

      // Uncomment below to use real API
      /*
      console.log('ExportManager: Fetching favorites from /export/csv/favorites');
      const response = await fetch('/export/csv/favorites');

      console.log('ExportManager: Response status:', response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('ExportManager: Favorites response:', data);

      if (data.success) {
        this.displayFavoritesList(data.data);
      } else {
        console.error('ExportManager: Failed to load favorites:', data.message);
        container.innerHTML = `<div class="alert alert-warning">お気に入りの読み込みに失敗しました: ${data.message}</div>`;
      }
      */
    } catch (error) {
      console.error('ExportManager: お気に入り一覧の取得に失敗しました:', error);
      container.innerHTML = '<div class="alert alert-danger">お気に入りの読み込み中にエラーが発生しました。</div>';
    }
  }

  displayFavoritesList(favorites) {
    const container = document.getElementById('favoritesList');
    if (!container) {
      return;
    }

    if (favorites.length === 0) {
      container.innerHTML = '<p class="text-muted text-center">お気に入りがありません。</p>';
      return;
    }

    let html = '<div class="list-group">';
    favorites.forEach(favorite => {
      html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${this.escapeHtml(favorite.name)}</h6>
                        <small class="text-muted">
                            施設: ${favorite.facility_ids.length}件 | 
                            項目: ${favorite.export_fields.length}項目 | 
                            作成: ${new Date(favorite.created_at).toLocaleDateString()}
                        </small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" data-action="load" data-favorite-id="${favorite.id}">
                            読み込み
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-action="edit" data-favorite-id="${favorite.id}" data-favorite-name="${this.escapeHtml(favorite.name)}">
                            編集
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-action="delete" data-favorite-id="${favorite.id}">
                            削除
                        </button>
                    </div>
                </div>
            `;
    });
    html += '</div>';

    container.innerHTML = html;

    // Add event listeners for the buttons
    this.setupFavoriteButtonListeners(container);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  setupFavoriteButtonListeners(container) {
    console.log('ExportManager: Setting up favorite button listeners');

    container.addEventListener('click', (e) => {
      console.log('ExportManager: Click event in favorites container:', e.target);

      const button = e.target.closest('button[data-action]');
      if (!button) {
        console.log('ExportManager: No button with data-action found');
        return;
      }

      console.log('ExportManager: Button clicked:', button);

      const action = button.dataset.action;
      const favoriteId = parseInt(button.dataset.favoriteId);
      const favoriteName = button.dataset.favoriteName;

      console.log('ExportManager: Action:', action, 'ID:', favoriteId, 'Name:', favoriteName);

      switch (action) {
        case 'load':
          console.log('ExportManager: Loading favorite:', favoriteId);
          this.loadFavorite(favoriteId);
          break;
        case 'edit':
          console.log('ExportManager: Editing favorite:', favoriteId);
          this.editFavorite(favoriteId, favoriteName);
          break;
        case 'delete':
          console.log('ExportManager: Deleting favorite:', favoriteId);
          this.deleteFavorite(favoriteId);
          break;
        default:
          console.warn('ExportManager: Unknown action:', action);
      }
    });

    console.log('ExportManager: Favorite button listeners setup complete');
  }

  async loadFavorite(id) {
    try {
      const response = await fetch(`/export/csv/favorites/${id}`);
      const data = await response.json();

      if (data.success) {
        const favoriteData = data.data;

        // Clear all checkboxes
        document.querySelectorAll('.facility-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = false);

        // Select facilities
        favoriteData.facility_ids.forEach(facilityId => {
          const checkbox = document.getElementById(`facility_${facilityId}`);
          if (checkbox) {
            checkbox.checked = true;
          }
        });

        // Select fields
        favoriteData.export_fields.forEach(field => {
          const checkbox = document.getElementById(`field_${field}`);
          if (checkbox) {
            checkbox.checked = true;
          }
        });

        // Update selection status
        this.updateSelectionStatus();

        // Close modal with proper cleanup
        const modalElement = document.getElementById('favoritesModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
          modal.hide();
          // Force cleanup after modal hide
          setTimeout(() => {
            this.cleanupModalState();
          }, 300);
        } else {
          // If no modal instance, force cleanup
          this.cleanupModalState();
        }

        // Show warning if some facilities are not accessible
        if (favoriteData.original_facility_count > favoriteData.accessible_facility_count) {
          alert(`注意: ${favoriteData.original_facility_count - favoriteData.accessible_facility_count}件の施設にアクセス権限がないため、選択から除外されました。`);
        }
      } else {
        alert(data.message);
      }
    } catch (error) {
      console.error('お気に入りの読み込みに失敗しました:', error);
      alert('お気に入りの読み込みに失敗しました。');
    }
  }

  async editFavorite(id, currentName) {
    const newName = prompt('新しい名前を入力してください:', currentName);
    if (newName && newName.trim() !== '' && newName !== currentName) {
      try {
        const response = await fetch(`/export/csv/favorites/${id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            name: newName.trim()
          })
        });

        const data = await response.json();

        if (data.success) {
          alert(data.message);
          this.loadFavoritesList();
        } else {
          alert(data.message);
        }
      } catch (error) {
        console.error('お気に入りの更新に失敗しました:', error);
        alert('お気に入りの更新に失敗しました。');
      }
    }
  }

  async deleteFavorite(id) {
    if (confirm('このお気に入りを削除しますか？')) {
      try {
        const response = await fetch(`/export/csv/favorites/${id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        });

        const data = await response.json();

        if (data.success) {
          alert(data.message);
          this.loadFavoritesList();
        } else {
          alert(data.message);
        }
      } catch (error) {
        console.error('お気に入りの削除に失敗しました:', error);
        alert('お気に入りの削除に失敗しました。');
      }
    }
  }

  handleFormSubmission(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    console.log('ExportManager (export.js): handleFormSubmission called');

    // Prevent duplicate submissions using both instance and global flags
    if (this.isSubmitting || globalSubmissionInProgress) {
      console.log('ExportManager (export.js): Form submission already in progress, ignoring');
      return;
    }

    this.isSubmitting = true;
    globalSubmissionInProgress = true;

    // Immediately disable the export button to prevent multiple clicks
    const exportButton = document.getElementById('exportButton');
    if (exportButton) {
      exportButton.disabled = true;
      console.log('ExportManager (export.js): Export button disabled');
    }

    const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
    const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

    if (selectedFacilities.length === 0 || selectedFields.length === 0) {
      alert('施設と出力項目を選択してください。');
      this.isSubmitting = false;
      globalSubmissionInProgress = false;
      if (exportButton) {
        exportButton.disabled = false;
      }
      return;
    }

    try {
      // Use local CsvDownloadManager
      console.log('ExportManager (export.js): Creating CsvDownloadManager');
      const downloadManager = new CsvDownloadManager(this);
      downloadManager.downloadCSV(selectedFacilities, selectedFields, exportButton);
    } catch (error) {
      console.error('ExportManager (export.js): Error during form submission:', error);
      alert('CSV出力中にエラーが発生しました。');
      this.isSubmitting = false;
      globalSubmissionInProgress = false;
    } finally {
      // Reset submission flag after a delay
      setTimeout(() => {
        this.isSubmitting = false;
        globalSubmissionInProgress = false;
        console.log('ExportManager (export.js): Reset submission flags');
      }, 2000);
    }
  }


}

/**
 * Global functions for backward compatibility
 * These functions provide a bridge between the old global API and the new ES6 modules
 */
export function loadFavorite(id) {
  if (window.exportManager) {
    window.exportManager.loadFavorite(id);
  }
}

export function editFavorite(id, currentName) {
  if (window.exportManager) {
    window.exportManager.editFavorite(id, currentName);
  }
}

export function deleteFavorite(id) {
  if (window.exportManager) {
    window.exportManager.deleteFavorite(id);
  }
}

/**
 * Initialize export manager
 * @returns {ExportManager} - Export manager instance
 */
export function initializeExportManager() {
  console.log('ExportManager: initializeExportManager called');
  const manager = new ExportManager();
  console.log('ExportManager: New ExportManager created:', manager);

  // Make it globally accessible for backward compatibility
  window.exportManager = manager;
  console.log('ExportManager: Set window.exportManager:', window.exportManager);

  // Expose global functions for backward compatibility
  window.loadFavorite = loadFavorite;
  window.editFavorite = editFavorite;
  window.deleteFavorite = deleteFavorite;

  // Global cleanup function for emergency use
  window.forceCleanupExportModals = function () {
    console.log('ExportManager: Force cleanup modals called');
    if (manager && manager.cleanupModalState) {
      manager.cleanupModalState();
    }

    // Also hide any visible modals
    const visibleModals = document.querySelectorAll('.modal.show');
    visibleModals.forEach(modal => {
      const modalInstance = bootstrap.Modal.getInstance(modal);
      if (modalInstance) {
        modalInstance.hide();
      }
    });
  };

  return manager;
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('ExportManager: DOMContentLoaded event fired');

  // Only initialize if we're on the CSV export page
  const csvExportForm = document.getElementById('csvExportForm');
  console.log('ExportManager: CSV form found:', !!csvExportForm);
  console.log('ExportManager: window.exportManager exists:', !!window.exportManager);
  console.log('ExportManager: ExportManager.instance exists:', !!ExportManager.instance);

  if (csvExportForm && !window.exportManager && !ExportManager.instance) {
    console.log('ExportManager: Auto-initializing ExportManager');
    const manager = initializeExportManager();
    console.log('ExportManager: Initialization complete, manager:', manager);
  } else {
    console.log('ExportManager: Skipping initialization - already exists or CSV form not found');
  }
});
