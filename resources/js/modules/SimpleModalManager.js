/**
 * シンプルなモーダル管理クラス
 * 
 * Bootstrapの標準機能を活用したシンプルなモーダル操作
 */

class SimpleModalManager {
  constructor() {
    this.init();
  }

  init() {
    // モーダル表示時のフォーカス設定
    document.addEventListener('shown.bs.modal', (event) => {
      const modal = event.target;
      this.setModalFocus(modal);
    });

    // モーダル非表示時のクリーンアップ
    document.addEventListener('hidden.bs.modal', (event) => {
      const modal = event.target;
      this.cleanupModal(modal);
    });

    // フォーム送信の処理
    this.setupFormHandlers();
  }

  /**
   * モーダルにフォーカスを設定
   */
  setModalFocus(modal) {
    setTimeout(() => {
      // autofocus属性のある要素を探す
      const autofocusElement = modal.querySelector('[autofocus]');
      if (autofocusElement) {
        autofocusElement.focus();
        return;
      }

      // 最初の入力要素にフォーカス
      const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) {
        firstInput.focus();
      }
    }, 150);
  }

  /**
   * モーダルをクリーンアップ
   */
  cleanupModal(modal) {
    // フォームをリセット
    const forms = modal.querySelectorAll('form');
    forms.forEach(form => {
      form.reset();
      // エラー状態をクリア
      this.clearFormErrors(form);
    });

    // プログレスバーをリセット
    const progressBars = modal.querySelectorAll('.progress');
    progressBars.forEach(progress => {
      progress.style.display = 'none';
      const bar = progress.querySelector('.progress-bar');
      if (bar) bar.style.width = '0%';
    });

    // ファイル選択表示をリセット
    const fileLists = modal.querySelectorAll('[id*="file-list"]');
    fileLists.forEach(list => {
      list.style.display = 'none';
    });
  }

  /**
   * フォームエラーをクリア
   */
  clearFormErrors(form) {
    // エラークラスを削除
    const invalidInputs = form.querySelectorAll('.is-invalid');
    invalidInputs.forEach(input => input.classList.remove('is-invalid'));

    // エラーメッセージを削除
    const errorMessages = form.querySelectorAll('.invalid-feedback');
    errorMessages.forEach(msg => msg.remove());
  }

  /**
   * フォームハンドラーを設定
   */
  setupFormHandlers() {
    // フォルダ作成フォーム
    document.addEventListener('submit', (event) => {
      if (event.target.id && event.target.id.includes('create-folder-form')) {
        this.handleFolderCreate(event);
      }
    });

    // ファイルアップロードフォーム
    document.addEventListener('submit', (event) => {
      if (event.target.id && event.target.id.includes('upload-file-form')) {
        this.handleFileUpload(event);
      }
    });

    // ファイル選択時の処理
    document.addEventListener('change', (event) => {
      if (event.target.type === 'file' && event.target.id.includes('file-input')) {
        this.handleFileSelection(event);
      }
    });
  }

  /**
   * フォルダ作成を処理
   */
  async handleFolderCreate(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    try {
      // ボタンを無効化
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>作成中...';

      // フォームデータを取得
      const formData = new FormData(form);
      const folderName = formData.get('name');

      if (!folderName?.trim()) {
        this.showError('フォルダ名を入力してください。');
        return;
      }

      // APIリクエスト
      const response = await this.submitForm(form);

      if (response.success) {
        this.showSuccess('フォルダを作成しました。');
        this.closeModal(form.closest('.modal'));
        this.refreshDocumentList();
      } else {
        this.showError(response.message || 'フォルダの作成に失敗しました。');
      }

    } catch (error) {
      console.error('Folder creation error:', error);
      this.showError('ネットワークエラーが発生しました。');
    } finally {
      // ボタンを復元
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  /**
   * ファイルアップロードを処理
   */
  async handleFileUpload(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    try {
      // ボタンを無効化
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>アップロード中...';

      // ファイル選択チェック
      const fileInput = form.querySelector('input[type="file"]');
      if (!fileInput.files.length) {
        this.showError('ファイルを選択してください。');
        return;
      }

      // プログレスバーを表示
      const progressContainer = form.querySelector('[id*="upload-progress"]');
      if (progressContainer) {
        progressContainer.style.display = 'block';
      }

      // APIリクエスト
      const response = await this.submitForm(form);

      if (response.success) {
        this.showSuccess('ファイルをアップロードしました。');
        this.closeModal(form.closest('.modal'));
        this.refreshDocumentList();
      } else {
        this.showError(response.message || 'ファイルのアップロードに失敗しました。');
      }

    } catch (error) {
      console.error('File upload error:', error);
      this.showError('ネットワークエラーが発生しました。');
    } finally {
      // ボタンを復元
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  /**
   * ファイル選択時の処理
   */
  handleFileSelection(event) {
    const files = event.target.files;
    const category = this.extractCategoryFromId(event.target.id);
    const fileList = document.getElementById(`file-list-${category}`);
    const selectedFiles = document.getElementById(`selected-files-${category}`);

    if (files.length > 0 && fileList && selectedFiles) {
      let html = '';
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-file me-2"></i>${this.escapeHtml(file.name)}</span>
                        <small class="text-muted">${this.formatFileSize(file.size)}</small>
                    </div>
                `;
      }
      selectedFiles.innerHTML = html;
      fileList.style.display = 'block';
    } else if (fileList) {
      fileList.style.display = 'none';
    }
  }

  /**
   * フォームを送信
   */
  async submitForm(form) {
    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(form.action || window.location.href, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });

    return await response.json();
  }

  /**
   * モーダルを閉じる
   */
  closeModal(modal) {
    if (modal) {
      const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
      bsModal.hide();
    }
  }

  /**
   * ドキュメント一覧を更新
   */
  refreshDocumentList() {
    // 既存のドキュメントマネージャーがあれば更新
    if (window.lifelineDocumentManager) {
      window.lifelineDocumentManager.loadDocuments();
    }

    // ページをリロード（シンプルな解決策）
    setTimeout(() => {
      window.location.reload();
    }, 1000);
  }

  /**
   * 成功メッセージを表示
   */
  showSuccess(message) {
    if (window.AppUtils && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, 'success');
    } else {
      alert('成功: ' + message);
    }
  }

  /**
   * エラーメッセージを表示
   */
  showError(message) {
    if (window.AppUtils && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, 'error');
    } else {
      alert('エラー: ' + message);
    }
  }

  /**
   * IDからカテゴリを抽出
   */
  extractCategoryFromId(id) {
    const match = id.match(/-([^-]+)$/);
    return match ? match[1] : '';
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
   * ファイルサイズをフォーマット
   */
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}

// グローバルインスタンスを作成
window.SimpleModalManager = new SimpleModalManager();

export default SimpleModalManager;