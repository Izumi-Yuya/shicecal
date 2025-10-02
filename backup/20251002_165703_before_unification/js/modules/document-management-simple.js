/**
 * シンプルなドキュメント管理モジュール
 */

export class DocumentManager {
  constructor(options = {}) {
    this.facilityId = options.facilityId;
    this.baseUrl = options.baseUrl;
    this.csrfToken = options.csrfToken;
    this.permissions = options.permissions || {};

    this.currentFolder = null;
    this.isLoading = false;

    console.log('DocumentManager initialized for facility:', this.facilityId);
  }

  async init() {
    try {
      console.log('Initializing document management...');

      // DOM要素の確認
      const container = document.getElementById('document-management-container');
      if (!container) {
        console.error('Document management container not found');
        return;
      }

      // 初期データの読み込み
      this.showLoading();

      // 空の状態を表示（実際のAPIコールは後で実装）
      this.showEmptyState();

    } catch (error) {
      console.error('Failed to load initial data:', error);
      this.showError('データの読み込みに失敗しました。');
    } finally {
      this.hideLoading();
    }
  }

  showLoading() {
    const loading = document.getElementById('loading-indicator');
    const documentList = document.getElementById('document-list');
    const errorMessage = document.getElementById('error-message');
    const emptyState = document.getElementById('empty-state');

    if (loading) loading.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
  }

  hideLoading() {
    const loading = document.getElementById('loading-indicator');
    if (loading) loading.style.display = 'none';
  }

  showError(message) {
    const errorText = document.getElementById('error-text');
    const errorMessage = document.getElementById('error-message');
    const documentList = document.getElementById('document-list');
    const emptyState = document.getElementById('empty-state');
    const loading = document.getElementById('loading-indicator');

    if (errorText) errorText.textContent = message;
    if (errorMessage) errorMessage.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (emptyState) emptyState.style.display = 'none';
    if (loading) loading.style.display = 'none';
  }

  showEmptyState() {
    const emptyState = document.getElementById('empty-state');
    const documentList = document.getElementById('document-list');
    const errorMessage = document.getElementById('error-message');
    const loading = document.getElementById('loading-indicator');

    if (emptyState) emptyState.style.display = 'block';
    if (documentList) documentList.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    if (loading) loading.style.display = 'none';
  }
}

export default DocumentManager;