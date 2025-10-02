/**
 * Common Table Lazy Loading Module
 * 共通テーブルコンポーネントの遅延読み込み機能
 */

class CommonTableLazyLoader {
  constructor(options = {}) {
    this.options = {
      batchSize: 50,
      loadMoreSelector: '#load-more-rows',
      tableBodySelector: '#table-body-lazy',
      batchDataSelector: '#remaining-batches-data',
      loadingClass: 'loading',
      fadeInDuration: 300,
      ...options
    };

    this.currentBatch = 1;
    this.batches = [];
    this.isLoading = false;
    this.totalBatches = 0;

    this.init();
  }

  /**
   * 初期化
   */
  init() {
    this.loadMoreBtn = document.querySelector(this.options.loadMoreSelector);
    this.tableBody = document.querySelector(this.options.tableBodySelector);
    this.batchDataElement = document.querySelector(this.options.batchDataSelector);

    if (!this.loadMoreBtn || !this.tableBody || !this.batchDataElement) {
      console.warn('CommonTableLazyLoader: Required elements not found');
      return;
    }

    this.loadBatchData();
    this.bindEvents();
    this.setupIntersectionObserver();
  }

  /**
   * バッチデータの読み込み
   */
  loadBatchData() {
    try {
      this.batches = JSON.parse(this.batchDataElement.textContent);
      this.totalBatches = this.batches.length + 1; // +1 for initial batch

      console.log(`CommonTableLazyLoader: Loaded ${this.batches.length} additional batches`);
    } catch (e) {
      console.error('CommonTableLazyLoader: Failed to parse batch data:', e);
      this.hideLazyLoadButton();
    }
  }

  /**
   * イベントバインディング
   */
  bindEvents() {
    this.loadMoreBtn.addEventListener('click', (e) => {
      e.preventDefault();
      this.loadNextBatch();
    });

    // キーボードアクセシビリティ
    this.loadMoreBtn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.loadNextBatch();
      }
    });
  }

  /**
   * Intersection Observer の設定（自動読み込み用）
   */
  setupIntersectionObserver() {
    if (!('IntersectionObserver' in window)) {
      return; // フォールバック：手動読み込みのみ
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !this.isLoading && this.hasMoreBatches()) {
          // ユーザーがボタンに近づいたら自動読み込み（オプション）
          if (this.options.autoLoad) {
            this.loadNextBatch();
          }
        }
      });
    }, {
      rootMargin: '100px'
    });

    observer.observe(this.loadMoreBtn);
  }

  /**
   * 次のバッチを読み込み
   */
  async loadNextBatch() {
    if (this.isLoading || !this.hasMoreBatches()) {
      return;
    }

    this.isLoading = true;
    this.setLoadingState(true);

    try {
      const batch = this.batches[this.currentBatch - 1]; // currentBatch is 1-indexed
      const fragment = await this.createRowsFragment(batch);

      // フェードイン効果で追加
      await this.appendRowsWithAnimation(fragment);

      this.currentBatch++;
      this.updateLoadMoreButton();

      // パフォーマンス統計の記録
      this.logPerformanceStats(batch.length);

    } catch (error) {
      console.error('CommonTableLazyLoader: Error loading batch:', error);
      this.showErrorMessage();
    } finally {
      this.isLoading = false;
      this.setLoadingState(false);
    }
  }

  /**
   * 行のフラグメントを作成
   */
  async createRowsFragment(batch) {
    const fragment = document.createDocumentFragment();

    for (const rowData of batch) {
      if (!rowData.cells || !Array.isArray(rowData.cells)) {
        continue;
      }

      const row = await this.createTableRow(rowData);
      fragment.appendChild(row);
    }

    return fragment;
  }

  /**
   * テーブル行を作成
   */
  async createTableRow(rowData) {
    const row = document.createElement('tr');
    row.className = 'lazy-loaded-row';
    row.style.opacity = '0';

    // 行タイプに応じたクラス追加
    if (rowData.type) {
      row.classList.add(`row-type-${rowData.type}`);
    }

    for (const cellData of rowData.cells) {
      const cell = await this.createTableCell(cellData);
      row.appendChild(cell);
    }

    return row;
  }

  /**
   * テーブルセルを作成
   */
  async createTableCell(cellData) {
    const cell = document.createElement('td');

    // セルタイプに応じたクラス設定
    if (cellData.label !== undefined && cellData.label !== null) {
      cell.className = 'detail-label';
      cell.textContent = cellData.label;
    } else {
      cell.className = 'detail-value';

      if (this.isEmpty(cellData.value)) {
        cell.classList.add('empty-field');
        cell.textContent = '未設定';
      } else {
        // フォーマット済みの値があればそれを使用、なければ生の値
        if (cellData.formatted_value) {
          cell.innerHTML = cellData.formatted_value;
        } else {
          cell.textContent = cellData.value;
        }
      }
    }

    // 属性の設定
    if (cellData.colspan && cellData.colspan > 1) {
      cell.colSpan = cellData.colspan;
    }
    if (cellData.rowspan && cellData.rowspan > 1) {
      cell.rowSpan = cellData.rowspan;
    }
    if (cellData.class) {
      cell.className += ' ' + cellData.class;
    }

    return cell;
  }

  /**
   * アニメーション付きで行を追加
   */
  async appendRowsWithAnimation(fragment) {
    this.tableBody.appendChild(fragment);

    // 新しく追加された行にフェードイン効果を適用
    const newRows = this.tableBody.querySelectorAll('.lazy-loaded-row[style*="opacity: 0"]');

    return new Promise((resolve) => {
      let completed = 0;
      const total = newRows.length;

      if (total === 0) {
        resolve();
        return;
      }

      newRows.forEach((row, index) => {
        setTimeout(() => {
          row.style.transition = `opacity ${this.options.fadeInDuration}ms ease-in-out`;
          row.style.opacity = '1';

          setTimeout(() => {
            completed++;
            if (completed === total) {
              resolve();
            }
          }, this.options.fadeInDuration);
        }, index * 50); // 50ms間隔でスタガード
      });
    });
  }

  /**
   * 読み込み状態の設定
   */
  setLoadingState(loading) {
    if (loading) {
      this.loadMoreBtn.classList.add(this.options.loadingClass);
      this.loadMoreBtn.disabled = true;
      this.loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>読み込み中...';
    } else {
      this.loadMoreBtn.classList.remove(this.options.loadingClass);
      this.loadMoreBtn.disabled = false;
    }
  }

  /**
   * 読み込みボタンの更新
   */
  updateLoadMoreButton() {
    const remainingBatches = this.batches.length - this.currentBatch + 1;
    const remainingRows = this.batches.slice(this.currentBatch - 1).reduce((total, batch) => total + batch.length, 0);

    if (remainingRows > 0) {
      this.loadMoreBtn.innerHTML = `<i class="fas fa-chevron-down me-1"></i>さらに読み込む (${remainingRows}行)`;
    } else {
      this.hideLazyLoadButton();
    }
  }

  /**
   * 遅延読み込みボタンを非表示
   */
  hideLazyLoadButton() {
    this.loadMoreBtn.style.display = 'none';

    // 完了メッセージの表示
    const completionMessage = document.createElement('div');
    completionMessage.className = 'text-center text-muted mt-3';
    completionMessage.innerHTML = '<i class="fas fa-check-circle me-1"></i>すべてのデータを読み込みました';
    this.loadMoreBtn.parentNode.replaceChild(completionMessage, this.loadMoreBtn);
  }

  /**
   * エラーメッセージの表示
   */
  showErrorMessage() {
    const errorMessage = document.createElement('div');
    errorMessage.className = 'alert alert-warning mt-3';
    errorMessage.innerHTML = `
            <i class="fas fa-exclamation-triangle me-1"></i>
            データの読み込みに失敗しました。
            <button class="btn btn-sm btn-outline-primary ms-2" onclick="location.reload()">
                ページを再読み込み
            </button>
        `;
    this.loadMoreBtn.parentNode.insertBefore(errorMessage, this.loadMoreBtn.nextSibling);
  }

  /**
   * まだ読み込むバッチがあるかチェック
   */
  hasMoreBatches() {
    return this.currentBatch <= this.batches.length;
  }

  /**
   * 値が空かどうかをチェック
   */
  isEmpty(value) {
    return value === null || value === undefined || value === '';
  }

  /**
   * パフォーマンス統計のログ記録
   */
  logPerformanceStats(batchSize) {
    const stats = {
      batch_loaded: this.currentBatch,
      batch_size: batchSize,
      total_batches: this.totalBatches,
      remaining_batches: this.batches.length - this.currentBatch + 1,
      memory_usage: this.getMemoryUsage()
    };

    console.log('CommonTableLazyLoader: Batch loaded', stats);

    // カスタムイベントの発火
    const event = new CustomEvent('commonTableBatchLoaded', {
      detail: stats
    });
    document.dispatchEvent(event);
  }

  /**
   * メモリ使用量の取得（可能な場合）
   */
  getMemoryUsage() {
    if ('memory' in performance) {
      return {
        used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
        total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
        limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
      };
    }
    return null;
  }

  /**
   * インスタンスの破棄
   */
  destroy() {
    if (this.loadMoreBtn) {
      this.loadMoreBtn.removeEventListener('click', this.loadNextBatch);
    }

    // メモリリークを防ぐためのクリーンアップ
    this.batches = [];
    this.loadMoreBtn = null;
    this.tableBody = null;
    this.batchDataElement = null;
  }
}

// グローバル初期化関数
window.initCommonTableLazyLoader = function (options = {}) {
  return new CommonTableLazyLoader(options);
};

// 自動初期化（DOMContentLoaded時）
document.addEventListener('DOMContentLoaded', function () {
  const lazyTables = document.querySelectorAll('[data-lazy-loading="true"]');

  lazyTables.forEach(table => {
    const batchSize = table.querySelector('[data-batch-size]')?.dataset.batchSize || 50;

    new CommonTableLazyLoader({
      batchSize: parseInt(batchSize),
      autoLoad: false // 手動読み込みをデフォルトに
    });
  });
});

export default CommonTableLazyLoader;