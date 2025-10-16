/**
 * 統合アプリケーション用JavaScript - 修正版
 * - 重複コードをすべて削除
 * - モジュラー設計
 * - パフォーマンスを最適化
 */

// Bootstrap is loaded via CDN in the HTML, so no import needed.
// window.bootstrap is available globally.

// ===== Debug utilities (development only) =====
// Note: Debug utilities are available in browser console via window.modalFix
// Load manually in development: <script src="/resources/js/debug/modal-fix.js"></script>

// Import shared utilities
import { AppUtils } from './shared/AppUtils.js';
import ApiClient from './shared/ApiClient.js';

// Import DocumentModalFix - 統一的なモーダル修正
import DocumentModalFix from './shared/DocumentModalFix.js';

// Import LifelineDocumentManager
import LifelineDocumentManager from './modules/LifelineDocumentManager.js';

// Import MaintenanceDocumentManager
import MaintenanceDocumentManager from './modules/MaintenanceDocumentManager.js';

// Import ContractDocumentManager
import ContractDocumentManager from './modules/ContractDocumentManager.js';

// グローバルに公開（ボタンクリックハンドラーで使用）
window.LifelineDocumentManager = LifelineDocumentManager;
window.MaintenanceDocumentManager = MaintenanceDocumentManager;
window.ContractDocumentManager = ContractDocumentManager;

// Import DocumentManager
import { DocumentManager } from './modules/DocumentManager.js';

// Import AccessibilityEnhancer
import AccessibilityEnhancer from './shared/AccessibilityEnhancer.js';

// Import SimpleModalManager
import SimpleModalManager from './modules/SimpleModalManager.js';
import AccessibilityFixer from './shared/AccessibilityFixer.js';
import FormAccessibilityEnhancer from './shared/FormAccessibilityEnhancer.js';

/* ========================================
   Core Utilities (統合版) - shared/AppUtils.jsからimport
   ======================================== */
// AppUtilsはshared/AppUtils.jsからimportされています

/* ========================================
   API Client - Using imported version from shared/ApiClient.js
   ======================================== */

/* ========================================
   Modal Manager (統合版)
   ======================================== */
// ModalManagerクラスを削除 - Bootstrapのデフォルト動作に任せる

/* ========================================
   Facility Manager (統合版)
   ======================================== */
class FacilityManager {
  constructor(facilityId) {
    this.facilityId = facilityId;
    this.apiClient = new ApiClient();
    this.init();
  }

  init() {
    this.bindEvents();
    this.initializeTabHandling();
  }

  bindEvents() {
    // Tab switching
    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
      tab.addEventListener('shown.bs.tab', (e) => {
        this.handleTabSwitch(e.target);
      });
    });
  }

  initializeTabHandling() {
    // Handle URL hash for tab navigation
    const hash = window.location.hash;
    if (hash) {
      const tabElement = document.querySelector(`[data-bs-target="${hash}"]`);
      if (tabElement) {
        const tab = new bootstrap.Tab(tabElement);
        tab.show();
      }
    }
  }

  handleTabSwitch(tabElement) {
    const targetId = tabElement.getAttribute('data-bs-target');

    // Update URL hash without triggering scroll
    if (targetId) {
      history.replaceState(null, null, targetId);
    }

    // Handle lazy loading for specific tabs
    if (targetId === '#documents') {
      this.loadDocumentsTab();
    }
  }

  async loadDocumentsTab() {
    // This will be handled by the DocumentManager
    console.log('Documents tab activated');
  }
}

/* ========================================
   Document Manager - Using imported class
   ======================================== */

/* ========================================
   Export Manager (統合版)
   ======================================== */
class ExportManager {
  constructor() {
    this.isSubmitting = false; // Prevent duplicate form submissions
    this.init();
  }

  refreshElements() {
    this.facilityCheckboxes = document.querySelectorAll('.facility-checkbox');
    this.categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    this.subcategoryCheckboxes = document.querySelectorAll('.subcategory-checkbox');
    this.fieldCheckboxes = document.querySelectorAll('.field-checkbox');
    this.selectAllBtn = document.getElementById('select-all-facilities');
    this.deselectAllBtn = document.getElementById('deselect-all-facilities');
    this.exportForm = document.getElementById('csv-export-form');
    this.exportBtn = document.getElementById('export-btn');
  }

  init() {
    this.refreshElements();
    this.bindEvents();
    this.updateExportButton();
  }

  bindEvents() {
    // Facility selection
    if (this.selectAllBtn) {
      this.selectAllBtn.addEventListener('click', () => this.selectAllFacilities());
    }
    if (this.deselectAllBtn) {
      this.deselectAllBtn.addEventListener('click', () => this.deselectAllFacilities());
    }

    // Facility checkbox handlers
    this.facilityCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', () => this.updateExportButton());
    });

    // Category and subcategory checkbox handlers
    this.categoryCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => this.handleCategoryChange(e));
    });

    this.subcategoryCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => this.handleSubcategoryChange(e));
    });

    // Field checkbox handlers
    this.fieldCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', () => this.updateExportButton());
    });

    // Form submission
    if (this.exportForm) {
      this.exportForm.addEventListener('submit', (e) => this.handleExport(e));
    }
  }

  selectAllFacilities() {
    this.facilityCheckboxes.forEach(checkbox => {
      checkbox.checked = true;
    });
    this.updateExportButton();
  }

  deselectAllFacilities() {
    this.facilityCheckboxes.forEach(checkbox => {
      checkbox.checked = false;
    });
    this.updateExportButton();
  }

  handleCategoryChange(e) {
    const checkbox = e.target;
    const category = checkbox.value;
    const subcategoryCheckboxes = document.querySelectorAll(`.subcategory-checkbox[data-category="${category}"]`);

    subcategoryCheckboxes.forEach(subcategoryCheckbox => {
      subcategoryCheckbox.checked = checkbox.checked;
      this.handleSubcategoryChange({ target: subcategoryCheckbox });
    });

    this.updateExportButton();
  }

  handleSubcategoryChange(e) {
    const checkbox = e.target;
    const category = checkbox.dataset.category;
    const subcategory = checkbox.value;
    const fieldCheckboxes = document.querySelectorAll(`.field-checkbox[data-category="${category}"][data-subcategory="${subcategory}"]`);

    fieldCheckboxes.forEach(fieldCheckbox => {
      fieldCheckbox.checked = checkbox.checked;
    });

    // Update parent category checkbox
    const categoryCheckbox = document.querySelector(`.category-checkbox[value="${category}"]`);
    if (categoryCheckbox) {
      const allSubcategories = document.querySelectorAll(`.subcategory-checkbox[data-category="${category}"]`);
      const checkedSubcategories = document.querySelectorAll(`.subcategory-checkbox[data-category="${category}"]:checked`);
      categoryCheckbox.checked = allSubcategories.length === checkedSubcategories.length;
    }

    this.updateExportButton();
  }

  updateExportButton() {
    const selectedFacilities = document.querySelectorAll('.facility-checkbox:checked');
    const selectedFields = document.querySelectorAll('.field-checkbox:checked');

    if (this.exportBtn) {
      this.exportBtn.disabled = selectedFacilities.length === 0 || selectedFields.length === 0;
    }
  }

  async handleExport(e) {
    e.preventDefault();

    if (this.isSubmitting) {
      console.log('Export already in progress');
      return;
    }

    const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.value);
    const selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);

    if (selectedFacilities.length === 0) {
      AppUtils.showToast('施設を選択してください。', 'error');
      return;
    }

    if (selectedFields.length === 0) {
      AppUtils.showToast('出力フィールドを選択してください。', 'error');
      return;
    }

    this.isSubmitting = true;
    AppUtils.showLoading(this.exportBtn, 'エクスポート中...');

    try {
      const formData = new FormData(this.exportForm);
      const response = await fetch(this.exportForm.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `facilities_export_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        AppUtils.showToast('CSVファイルのダウンロードが開始されました。', 'success');
      } else {
        throw new Error('Export failed');
      }
    } catch (error) {
      console.error('Export error:', error);
      AppUtils.showToast('エクスポートに失敗しました。', 'error');
    } finally {
      AppUtils.hideLoading(this.exportBtn);
      this.isSubmitting = false;
    }
  }
}

/* ========================================
   CSV Download Manager (統合版)
   ======================================== */
class CsvDownloadManager {
  constructor() {
    this.DOWNLOAD_TIMEOUTS = {
      INITIAL_DELAY: 1000,
      RETRY_INTERVAL: 2000,
      MAX_RETRIES: 30,
      CLEANUP_DELAY: 5000
    };

    this.activeDownloads = new Map();
    this.init();
  }

  init() {
    this.bindEvents();
  }

  bindEvents() {
    // CSV export form submission
    const csvForm = document.getElementById('csv-export-form');
    if (csvForm) {
      csvForm.addEventListener('submit', (e) => this.handleCsvExport(e));
    }

    // Favorite management
    const saveFavoriteBtn = document.getElementById('save-favorite-btn');
    if (saveFavoriteBtn) {
      saveFavoriteBtn.addEventListener('click', () => this.handleSaveFavorite());
    }

    const loadFavoriteSelect = document.getElementById('load-favorite-select');
    if (loadFavoriteSelect) {
      loadFavoriteSelect.addEventListener('change', (e) => this.handleLoadFavorite(e));
    }
  }

  async handleCsvExport(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const exportBtn = form.querySelector('button[type="submit"]');

    // Validate selections
    const selectedFacilities = formData.getAll('facilities[]');
    const selectedFields = formData.getAll('fields[]');

    if (selectedFacilities.length === 0) {
      AppUtils.showToast('施設を選択してください。', 'error');
      return;
    }

    if (selectedFields.length === 0) {
      AppUtils.showToast('出力フィールドを選択してください。', 'error');
      return;
    }

    const downloadId = this.generateDownloadId();

    try {
      AppUtils.showLoading(exportBtn, 'エクスポート中...');

      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        if (result.download_url) {
          // Direct download
          this.initiateDownload(result.download_url, result.filename || 'export.csv');
          AppUtils.showToast('CSVファイルのダウンロードが開始されました。', 'success');
        } else if (result.batch_id) {
          // Batch processing
          this.startBatchDownloadPolling(result.batch_id, downloadId);
          AppUtils.showToast('大量データのため、バックグラウンドで処理中です。完了までお待ちください。', 'info');
        }
      } else {
        throw new Error(result.message || 'エクスポートに失敗しました。');
      }
    } catch (error) {
      console.error('CSV export error:', error);
      AppUtils.showToast(error.message || 'エクスポートに失敗しました。', 'error');
    } finally {
      AppUtils.hideLoading(exportBtn);
    }
  }

  generateDownloadId() {
    return 'download_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  initiateDownload(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  startBatchDownloadPolling(batchId, downloadId) {
    let retryCount = 0;

    const pollStatus = async () => {
      try {
        const response = await fetch(`/export/csv/batch-status/${batchId}`, {
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        });

        if (!response.ok) {
          throw new Error('Status check failed');
        }

        const status = await response.json();

        if (status.completed) {
          if (status.success && status.download_url) {
            this.initiateDownload(status.download_url, status.filename);
            AppUtils.showToast('CSVファイルの準備が完了しました。ダウンロードを開始します。', 'success');
          } else {
            AppUtils.showToast(status.error || 'エクスポート処理でエラーが発生しました。', 'error');
          }
          this.cleanupDownload(downloadId);
        } else if (retryCount < this.DOWNLOAD_TIMEOUTS.MAX_RETRIES) {
          retryCount++;
          setTimeout(pollStatus, this.DOWNLOAD_TIMEOUTS.RETRY_INTERVAL);
        } else {
          AppUtils.showToast('エクスポート処理がタイムアウトしました。', 'error');
          this.cleanupDownload(downloadId);
        }
      } catch (error) {
        console.error('Batch status polling error:', error);
        if (retryCount < this.DOWNLOAD_TIMEOUTS.MAX_RETRIES) {
          retryCount++;
          setTimeout(pollStatus, this.DOWNLOAD_TIMEOUTS.RETRY_INTERVAL);
        } else {
          AppUtils.showToast('エクスポート状況の確認に失敗しました。', 'error');
          this.cleanupDownload(downloadId);
        }
      }
    };

    this.activeDownloads.set(downloadId, { batchId, pollStatus });
    setTimeout(pollStatus, this.DOWNLOAD_TIMEOUTS.INITIAL_DELAY);
  }

  cleanupDownload(downloadId) {
    this.activeDownloads.delete(downloadId);

    setTimeout(() => {
      // Additional cleanup if needed
    }, this.DOWNLOAD_TIMEOUTS.CLEANUP_DELAY);
  }
}

/* ========================================
   Main Application Manager (統合版)
   ======================================== */
class ShiseCalApp {
  constructor() {
    this.modules = {};
    this.initialized = false;
    this.init();
  }

  async init() {
    if (this.initialized) return;

    try {
      console.log('ShiseCalApp initializing...');

      // Initialize core utilities
      this.apiClient = new ApiClient();
      window.AppUtils = AppUtils;
      window.ApiClient = this.apiClient;

      // Initialize page-specific functionality
      await this.initializePageSpecificFeatures();

      // Initialize global features
      this.initializeGlobalFeatures();

      this.initialized = true;
      console.log('ShiseCalApp initialized successfully');

    } catch (error) {
      console.error('Failed to initialize ShiseCalApp:', error);
    }
  }

  async initializePageSpecificFeatures() {
    const currentPath = window.location.pathname;

    // Facility pages
    if (currentPath.includes('/facilities/')) {
      await this.initializeFacilityFeatures();
    }

    // Export pages
    if (currentPath.includes('/export/')) {
      this.initializeExportFeatures();
    }

    // Admin pages
    if (currentPath.includes('/admin/')) {
      this.initializeAdminFeatures();
    }
  }

  async initializeFacilityFeatures() {
    const facilityIdMatch = window.location.pathname.match(/\/facilities\/(\d+)/);
    if (facilityIdMatch) {
      const facilityId = facilityIdMatch[1];

      // Initialize facility manager
      this.modules.facilityManager = new FacilityManager(facilityId);

      // Initialize document management if container exists
      await this.initializeDocumentManagement(facilityId);

      // Initialize lifeline document toggles first
      this.initializeLifelineDocumentToggles();

      // Initialize lifeline document management with delay to ensure DOM is ready
      setTimeout(() => {
        this.initializeLifelineDocumentManagement(facilityId);
      }, 500);

      // Also listen for tab changes to initialize when lifeline tab is shown
      const lifelineTab = document.querySelector('a[data-bs-target="#lifeline-equipment"]');
      if (lifelineTab) {
        lifelineTab.addEventListener('shown.bs.tab', () => {
          console.log('Lifeline equipment tab shown, initializing document management');
          setTimeout(() => {
            this.initializeLifelineDocumentManagement(facilityId);
          }, 300);
        });
      }
    }
  }

  async initializeDocumentManagement(facilityId) {
    try {
      console.log('Looking for document-management-container...');
      const documentContainer = document.getElementById('document-management-container');
      console.log('Document container found:', !!documentContainer);

      if (!documentContainer) {
        console.log('Document container not found, skipping initialization');
        return;
      }

      console.log('Creating DocumentManager with facilityId:', facilityId);
      const documentManager = new DocumentManager({
        facilityId: facilityId,
        baseUrl: `/facilities/${facilityId}/documents`,
        permissions: {
          canCreate: documentContainer.dataset.canCreate === 'true',
          canUpdate: documentContainer.dataset.canUpdate === 'true',
          canDelete: documentContainer.dataset.canDelete === 'true'
        }
      });

      console.log('DocumentManager created:', documentManager);
      this.modules.documentManager = documentManager;

      const isDocumentsPage = window.location.pathname.includes('/documents');
      const documentsTab = document.getElementById('documents-tab');
      const isDocumentsTabActive = documentsTab && documentsTab.classList.contains('active');

      if (isDocumentsPage || isDocumentsTabActive) {
        await documentManager.init();
      } else {
        if (documentsTab) {
          documentsTab.addEventListener('shown.bs.tab', async function () {
            if (!documentManager.initialized) {
              await documentManager.init();
            }
          });
        }
      }

      window.documentManager = documentManager;
    } catch (error) {
      console.error('Failed to initialize document management:', error);

      const documentContainer = document.getElementById('document-management-container');
      if (documentContainer) {
        documentContainer.innerHTML = `
          <div class="alert alert-danger">
            <h5>エラー</h5>
            <p>ドキュメント管理の初期化に失敗しました。</p>
            <p><small>エラー詳細: ${error.message}</small></p>
            <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
              <i class="fas fa-refresh"></i> 再読み込み
            </button>
          </div>
        `;
      }
    }
  }

  initializeLifelineDocumentManagement(facilityId) {
    try {
      console.log(`[LifelineDoc] Starting initialization for facility ${facilityId}`);

      // DOM要素を検索
      const lifelineContainers = document.querySelectorAll('[data-lifeline-category]');
      console.log(`[LifelineDoc] Found ${lifelineContainers.length} lifeline containers`);

      if (lifelineContainers.length === 0) {
        console.warn('[LifelineDoc] No lifeline containers found. DOM may not be ready yet.');
        return;
      }

      lifelineContainers.forEach((container, index) => {
        const category = container.dataset.lifelineCategory;
        if (!category) {
          console.warn(`[LifelineDoc] Container ${index} has no category attribute`);
          return;
        }

        // subcategoryがある場合はuniqueIdを生成
        const subcategory = container.dataset.subcategory;
        const uniqueId = subcategory ? `${category}_${subcategory}` : category;

        console.log(`[LifelineDoc] Processing category: ${category}, subcategory: ${subcategory}, uniqueId: ${uniqueId}`);
        console.log(`[LifelineDoc] Container visible:`, container.offsetParent !== null);
        console.log(`[LifelineDoc] Container ID:`, container.id);

        const managerKey = `lifelineDocumentManager_${uniqueId}`;
        const globalKey = `lifelineDocManager_${uniqueId}`;

        // 既存のマネージャーがあるかチェック（グローバルとローカルの両方）
        const existingManager = this.modules[managerKey] || window[globalKey];

        if (!existingManager) {
          console.log(`[LifelineDoc] Creating new manager for ${category} (uniqueId: ${uniqueId})`);

          try {
            const manager = new LifelineDocumentManager(facilityId, category, uniqueId);
            this.modules[managerKey] = manager;

            // グローバル参照も作成（互換性のため）
            window[globalKey] = manager;

            // facilityIdをグローバルに設定（静的メソッド用）
            window.facilityId = facilityId;

            console.log(`[LifelineDoc] ✓ Manager created for ${category}`);
            console.log(`[LifelineDoc] Manager initialized:`, manager.initialized);

            // 初期化されていない場合は明示的に初期化
            if (!manager.initialized) {
              console.log(`[LifelineDoc] Manager not initialized, calling init()`);
              manager.init();
            }
          } catch (error) {
            console.error(`[LifelineDoc] ✗ Failed to create manager for ${category}:`, error);
            console.error(`[LifelineDoc] Error stack:`, error.stack);
          }
        } else {
          console.log(`[LifelineDoc] Manager already exists for ${uniqueId}, using existing instance`);

          // ローカルモジュールに登録されていない場合は登録
          if (!this.modules[managerKey] && window[globalKey]) {
            console.log(`[LifelineDoc] Registering existing global manager to local modules`);
            this.modules[managerKey] = window[globalKey];
          }

          // 既存のマネージャーのデータを再読み込み
          if (existingManager) {
            console.log(`[LifelineDoc] Manager state:`, {
              initialized: existingManager.initialized,
              hasLoadDocuments: typeof existingManager.loadDocuments === 'function',
              loading: existingManager.state?.loading
            });

            if (typeof existingManager.loadDocuments === 'function') {
              console.log(`[LifelineDoc] Calling loadDocuments() for ${uniqueId}`);
              existingManager.loadDocuments();
            } else {
              console.warn(`[LifelineDoc] Manager exists but loadDocuments() not available for ${uniqueId}`);
            }
          } else {
            console.error(`[LifelineDoc] Manager key exists but manager is null/undefined`);
          }
        }
      });

      console.log(`[LifelineDoc] Initialization complete. Active managers:`, Object.keys(this.modules).filter(k => k.startsWith('lifelineDocumentManager_')));
    } catch (error) {
      console.error('[LifelineDoc] Failed to initialize lifeline document management:', error);
      console.error('[LifelineDoc] Error stack:', error.stack);
    }
  }

  // 公開メソッド：ライフライン設備ドキュメントマネージャーを再初期化
  initializeLifelineDocumentManagers() {
    const facilityId = document.querySelector('[data-facility-id]')?.dataset.facilityId;
    if (facilityId) {
      this.initializeLifelineDocumentManagement(facilityId);
    }
  }

  // ライフライン設備ドキュメントトグルボタンの初期化（モーダル対応）
  initializeLifelineDocumentToggles() {
    const self = this; // thisコンテキストを保持

    // モーダルトリガーボタンを検索
    document.querySelectorAll('[id$="-documents-toggle"]').forEach(toggleBtn => {
      const btnId = toggleBtn.id;
      console.log(`[Modal] Found toggle button: ${btnId}`);

      // ボタンIDからカテゴリを抽出（例: electrical-documents-toggle → electrical）
      const category = btnId.replace('-documents-toggle', '');
      console.log(`[Modal] Extracted category: ${category}`);

      // facilityIdを取得
      const facilityId = document.querySelector('[data-facility-id]')?.dataset.facilityId;
      if (!facilityId) {
        console.error(`[Modal] facilityId not found for ${category}`);
        return;
      }

      // ボタンクリック時にBladeコンポーネントのモーダルを開く
      toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        console.log(`[Modal] Button clicked for ${category}, opening Blade modal...`);

        try {
          // Bladeコンポーネントで生成されたモーダルを開く
          const modalId = `${category}-documents-modal`;
          const modalElement = document.getElementById(modalId);

          if (modalElement) {
            // 既存のモーダルインスタンスを取得または新規作成
            let bsModal = bootstrap.Modal.getInstance(modalElement);
            if (!bsModal) {
              bsModal = new bootstrap.Modal(modalElement);
            }
            bsModal.show();
            console.log(`[Modal] ✓ Blade modal opened for ${category}`);

            // モーダルが開いた後、ドキュメントマネージャーを初期化
            const handleModalShown = () => {
              const managerKey = `lifelineDocumentManager_${category}`;
              console.log(`[Modal] Modal shown for ${category}, manager key: ${managerKey}`);
              console.log(`[Modal] Current modules:`, Object.keys(self.modules));

              try {
                if (!self.modules[managerKey]) {
                  console.log(`[Modal] Initializing new document manager for ${category}`);
                  const manager = new LifelineDocumentManager(facilityId, category);
                  self.modules[managerKey] = manager;
                  console.log(`[Modal] ✓ Manager initialized for ${category}`);
                } else {
                  console.log(`[Modal] Document manager already exists for ${category}`);
                  const manager = self.modules[managerKey];

                  if (manager && typeof manager.refresh === 'function') {
                    console.log(`[Modal] Calling refresh() for ${category}`);
                    manager.refresh();
                  } else if (manager && typeof manager.loadDocuments === 'function') {
                    console.log(`[Modal] refresh() not available, calling loadDocuments() for ${category}`);
                    manager.loadDocuments();
                  } else {
                    console.warn(`[Modal] Manager exists but no refresh/loadDocuments method available for ${category}`, manager);
                  }
                }
              } catch (error) {
                console.error(`[Modal] Error initializing/refreshing manager for ${category}:`, error);
              }
            };

            // モーダルが閉じた後のクリーンアップ
            const handleModalHidden = () => {
              console.log(`[Modal] Modal hidden for ${category}, cleaning up...`);
              // 重複したbackdropを削除
              const backdrops = document.querySelectorAll('.modal-backdrop');
              if (backdrops.length > 0) {
                backdrops.forEach(backdrop => backdrop.remove());
              }
            };

            modalElement.addEventListener('shown.bs.modal', handleModalShown, { once: true });
            modalElement.addEventListener('hidden.bs.modal', handleModalHidden, { once: true });

            // モーダルが閉じられた時のクリーンアップ
            modalElement.addEventListener('hidden.bs.modal', () => {
              console.log(`[Modal] Modal closed for ${category}, cleaning up...`);

              // 残っているbackdropを削除
              const backdrops = document.querySelectorAll('.modal-backdrop');
              backdrops.forEach(backdrop => backdrop.remove());

              // bodyのクラスをクリーンアップ
              document.body.classList.remove('modal-open');
              document.body.style.overflow = '';
              document.body.style.paddingRight = '';
            }, { once: true });
          } else {
            console.error(`[Modal] ✗ Modal element not found: ${modalId}`);
          }
        } catch (error) {
          console.error(`[Modal] ✗ Failed to open modal for ${category}:`, error);
          console.error(`[Modal] Error stack:`, error.stack);
        }
      });

      console.log(`[Modal] ✓ Click handler registered for ${category}`);
    });
  }

  initializeExportFeatures() {
    // CSV Export
    if (document.getElementById('csv-export-form')) {
      this.modules.csvDownloadManager = new CsvDownloadManager();
    }

    // General Export
    if (document.querySelector('.facility-checkbox')) {
      this.modules.exportManager = new ExportManager();
    }
  }

  initializeAdminFeatures() {
    // Admin-specific initialization
    console.log('Initializing admin features...');
  }

  initializeGlobalFeatures() {
    // Global event listeners
    this.initializeGlobalEventListeners();

    // Initialize tooltips
    this.initializeTooltips();

    // Initialize accessibility features
    this.initializeAccessibilityFeatures();
  }

  initializeGlobalEventListeners() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
      });
    }

    // Global form validation
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (form.classList.contains('needs-validation')) {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      }
    });
  }

  initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  initializeAccessibilityFeatures() {
    // Focus management for modals
    document.addEventListener('shown.bs.modal', (e) => {
      const modal = e.target;
      const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) {
        // 少し遅延させてフォーカスを設定
        setTimeout(() => firstInput.focus(), 100);
      }
    });

    // モーダルが閉じる前にフォーカスをクリア（aria-hidden警告を防ぐ）
    document.addEventListener('hide.bs.modal', (e) => {
      const modal = e.target;
      // モーダル内のフォーカスされた要素からフォーカスを外す
      const focusedElement = modal.querySelector(':focus');
      if (focusedElement) {
        focusedElement.blur();
      }
    });

    // モーダルが完全に閉じた後にバックドロップをクリーンアップ
    document.addEventListener('hidden.bs.modal', (e) => {
      // 残っているバックドロップを削除
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach((backdrop) => {
        backdrop.remove();
      });

      // body要素からモーダル関連のクラスを削除
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      // ESC key to close modals
      if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
          const modalInstance = bootstrap.Modal.getInstance(openModal);
          if (modalInstance) {
            modalInstance.hide();
          }
        }
      }
    });
  }
}

// クラスをグローバルに公開（デバッグ用）
window.DocumentManager = DocumentManager;
window.LifelineDocumentManager = LifelineDocumentManager;
window.MaintenanceDocumentManager = MaintenanceDocumentManager;
window.AppUtils = AppUtils;
window.ApiClient = ApiClient;

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // シンプルなモーダル管理を初期化
  if (window.SimpleModalManager) {
    console.log('SimpleModalManager initialized');
  }

  // アクセシビリティ強化を初期化
  if (window.AccessibilityEnhancer) {
    window.AccessibilityEnhancer.init();
  }

  window.shiseCalApp = new ShiseCalApp();

  // ハッシュフラグメントに基づいてライフライン設備ドキュメントセクションを開く
  function handleHashFragment() {
    const hash = window.location.hash;
    console.log('Checking hash fragment:', hash);

    if (hash && hash.startsWith('#lifeline-') && hash.endsWith('-documents')) {
      // ハッシュからカテゴリを抽出 (例: #lifeline-electrical-documents -> electrical)
      const category = hash.replace('#lifeline-', '').replace('-documents', '');
      console.log('Extracted category:', category);

      // ライフライン設備タブをアクティブにする
      const lifelineTab = document.querySelector('a[data-bs-target="#lifeline-equipment"]');
      if (lifelineTab) {
        console.log('Found lifeline tab, activating...');
        const tabInstance = new bootstrap.Tab(lifelineTab);
        tabInstance.show();

        // タブが表示された後にドキュメントセクションを開く
        setTimeout(() => {
          const documentToggle = document.getElementById(`${category}-documents-toggle`);
          const documentSection = document.getElementById(`${category}-documents-section`);

          console.log('Document toggle:', documentToggle);
          console.log('Document section:', documentSection);

          if (documentToggle && documentSection) {
            // ドキュメントセクションを開く
            documentSection.classList.add('show');

            // ボタンの状態を更新
            documentToggle.classList.remove('btn-outline-primary');
            documentToggle.classList.add('btn-primary');

            const icon = documentToggle.querySelector('i');
            const text = documentToggle.querySelector('span');
            if (icon) icon.className = 'fas fa-folder-minus me-1';
            if (text) text.textContent = '閉じる';

            console.log(`Successfully opened ${category} documents section from hash`);

            // ハッシュをクリアして、次回のリロード時に問題が起きないようにする
            setTimeout(() => {
              history.replaceState(null, null, window.location.pathname + window.location.search);
            }, 1000);
          } else {
            console.error(`Document elements not found for category: ${category}`);
          }
        }, 800);
      } else {
        console.error('Lifeline tab not found');
      }
    }
  }

  // URLパラメータに基づいてライフライン設備ドキュメントセクションを開く
  function handleDocumentSectionOpen() {
    const urlParams = new URLSearchParams(window.location.search);
    const openDocuments = urlParams.get('open_documents');

    console.log('Checking open_documents parameter:', openDocuments);

    if (openDocuments) {
      const category = openDocuments;
      console.log('Opening documents for category:', category);

      // ライフライン設備タブをアクティブにする
      const lifelineTab = document.querySelector('a[data-bs-target="#lifeline-equipment"]');
      if (lifelineTab) {
        console.log('Found lifeline tab, activating...');
        const tabInstance = new bootstrap.Tab(lifelineTab);
        tabInstance.show();

        // タブが表示された後にドキュメントセクションを開く
        setTimeout(() => {
          const documentToggle = document.getElementById(`${category}-documents-toggle`);
          const documentSection = document.getElementById(`${category}-documents-section`);

          console.log('Document toggle:', documentToggle);
          console.log('Document section:', documentSection);

          if (documentToggle && documentSection) {
            // ドキュメントセクションを開く
            documentSection.classList.add('show');

            // ボタンの状態を更新
            documentToggle.classList.remove('btn-outline-primary');
            documentToggle.classList.add('btn-primary');

            const icon = documentToggle.querySelector('i');
            const text = documentToggle.querySelector('span');
            if (icon) icon.className = 'fas fa-folder-minus me-1';
            if (text) text.textContent = '閉じる';

            console.log(`Successfully opened ${category} documents section from URL parameter`);

            // URLパラメータをクリアして、次回のリロード時に問題が起きないようにする
            setTimeout(() => {
              const newUrl = new URL(window.location);
              newUrl.searchParams.delete('open_documents');
              history.replaceState(null, null, newUrl.toString());
            }, 2000);
          } else {
            console.error(`Document elements not found for category: ${category}`);
          }
        }, 800);
      } else {
        console.error('Lifeline tab not found');
      }
    }
  }

  // 初回実行（ハッシュフラグメントとURLパラメータの両方をチェック）
  setTimeout(handleHashFragment, 1000);
  setTimeout(handleDocumentSectionOpen, 1200);

  // ハッシュ変更時にも実行
  window.addEventListener('hashchange', handleHashFragment);

  // ドキュメント管理の手動初期化チェック
  setTimeout(() => {
    const documentContainer = document.getElementById('document-management-container');
    if (documentContainer && !window.documentManager) {
      console.log('Document container found but DocumentManager not initialized, attempting manual initialization...');
      const facilityId = documentContainer.dataset.facilityId;
      if (facilityId) {
        window.shiseCalApp.initializeDocumentManagement(facilityId);
      }
    }
  }, 1000);
});

// Make AppUtils available globally for backward compatibility
window.AppUtils = AppUtils;

// Export classes for external use
export {
  AppUtils,
  ApiClient,
  FacilityManager,
  DocumentManager,
  ExportManager,
  CsvDownloadManager,
  ShiseCalApp
};