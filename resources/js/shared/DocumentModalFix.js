/**
 * ドキュメント管理モーダルの統一的な修正
 * 
 * すべてのドキュメント管理モーダル（契約書、ライフライン設備、メンテナンス履歴等）に適用
 * - モーダルのHoisting（body直下への移動）
 * - z-indexの動的設定
 * - バックドロップのクリーンアップ
 */

class DocumentModalFix {
  constructor() {
    this.mainModalIds = [
      'contract-documents-modal',
      'electrical-documents-modal',
      'gas-documents-modal',
      'water-documents-modal',
      'elevator-documents-modal',
      'hvac-documents-modal',
      'maintenance-documents-modal'
    ];

    this.nestedModalPrefixes = [
      'create-folder-modal-',
      'upload-file-modal-',
      'rename-modal-',
      'properties-modal-'
    ];

    this.init();
  }

  init() {
    console.log('[DocumentModalFix] Initializing document modal fixes');

    // DOMContentLoadedまたは即座に実行
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    this.hoistAllModals();
    this.setupZIndexEnforcement();
    this.setupBackdropCleanup();
    console.log('[DocumentModalFix] Setup complete');
  }

  /**
   * すべてのモーダルをbody直下に移動
   */
  hoistAllModals() {
    // メインモーダルをhoisting
    this.mainModalIds.forEach(modalId => {
      const modal = document.getElementById(modalId);
      if (modal && modal.parentElement !== document.body) {
        console.log('[DocumentModalFix] Hoisting main modal:', modalId);
        document.body.appendChild(modal);
      }
    });

    // ネストされたモーダルをhoisting
    this.nestedModalPrefixes.forEach(prefix => {
      const modals = document.querySelectorAll(`[id^="${prefix}"]`);
      modals.forEach(modal => {
        if (modal.parentElement !== document.body) {
          console.log('[DocumentModalFix] Hoisting nested modal:', modal.id);
          document.body.appendChild(modal);
        }
      });
    });
  }

  /**
   * z-indexの動的設定
   */
  setupZIndexEnforcement() {
    document.addEventListener('show.bs.modal', (ev) => {
      const modalEl = ev.target;
      if (!modalEl) return;

      // メインモーダル
      if (this.mainModalIds.includes(modalEl.id)) {
        modalEl.style.zIndex = '9999';
        setTimeout(() => {
          const backdrops = document.querySelectorAll('.modal-backdrop');
          backdrops.forEach(bd => {
            bd.style.zIndex = '9998';
          });
        }, 0);
        console.log('[DocumentModalFix] Set z-index for main modal:', modalEl.id);
      }
      // ネストされたモーダル
      else if (this.isNestedModal(modalEl.id)) {
        modalEl.style.zIndex = '10000';
        setTimeout(() => {
          const backdrops = document.querySelectorAll('.modal-backdrop');
          const lastBackdrop = backdrops[backdrops.length - 1];
          if (lastBackdrop) {
            lastBackdrop.style.zIndex = '9999';
          }
        }, 0);
        console.log('[DocumentModalFix] Set z-index for nested modal:', modalEl.id);
      }
    });
  }

  /**
   * バックドロップのクリーンアップ
   */
  setupBackdropCleanup() {
    document.addEventListener('hidden.bs.modal', (ev) => {
      if (!ev.target) return;

      // メインモーダルが閉じられたときのみクリーンアップ
      if (this.mainModalIds.includes(ev.target.id)) {
        setTimeout(() => {
          const backdrops = document.querySelectorAll('.modal-backdrop');
          if (backdrops.length > 1) {
            console.log('[DocumentModalFix] Cleaning up extra backdrops:', backdrops.length - 1);
            for (let i = 0; i < backdrops.length - 1; i++) {
              backdrops[i].parentNode.removeChild(backdrops[i]);
            }
          }
        }, 100);
      }
    });
  }

  /**
   * ネストされたモーダルかどうかを判定
   */
  isNestedModal(modalId) {
    return this.nestedModalPrefixes.some(prefix => modalId.startsWith(prefix));
  }

  /**
   * 特定のモーダルを手動でhoisting
   */
  hoistModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.parentElement !== document.body) {
      console.log('[DocumentModalFix] Manually hoisting modal:', modalId);
      document.body.appendChild(modal);
    }
  }

  /**
   * すべてのバックドロップを削除
   */
  cleanupAllBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    console.log('[DocumentModalFix] Cleaning up all backdrops:', backdrops.length);
    backdrops.forEach(bd => bd.remove());
  }
}

// グローバルに公開
window.DocumentModalFix = DocumentModalFix;

// 自動初期化
if (typeof window.documentModalFix === 'undefined') {
  window.documentModalFix = new DocumentModalFix();
}

// ES6モジュールとしてエクスポート
export default DocumentModalFix;
