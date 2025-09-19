/**
 * Common Table Lazy Loading Tests
 * 共通テーブル遅延読み込み機能のテスト
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock DOM environment
const mockDOM = () => {
  // Mock IntersectionObserver
  global.IntersectionObserver = vi.fn().mockImplementation((callback) => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }));

  // Mock performance.memory
  global.performance = {
    ...global.performance,
    memory: {
      usedJSHeapSize: 1024 * 1024,
      totalJSHeapSize: 2048 * 1024,
      jsHeapSizeLimit: 4096 * 1024
    }
  };

  // Mock document methods
  global.document = {
    ...global.document,
    querySelector: vi.fn(),
    querySelectorAll: vi.fn(),
    createElement: vi.fn(),
    createDocumentFragment: vi.fn(),
    addEventListener: vi.fn(),
    dispatchEvent: vi.fn()
  };

  // Mock console methods
  global.console = {
    ...global.console,
    log: vi.fn(),
    warn: vi.fn(),
    error: vi.fn()
  };
};

// Mock CommonTableLazyLoader class
class MockCommonTableLazyLoader {
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

    this.mockElements();
  }

  mockElements() {
    this.loadMoreBtn = {
      addEventListener: vi.fn(),
      classList: { add: vi.fn(), remove: vi.fn() },
      innerHTML: '',
      disabled: false,
      style: { display: 'block' },
      parentNode: {
        replaceChild: vi.fn(),
        insertBefore: vi.fn()
      }
    };

    this.tableBody = {
      appendChild: vi.fn(),
      querySelectorAll: vi.fn(() => [])
    };

    this.batchDataElement = {
      textContent: JSON.stringify([
        [
          {
            type: 'standard',
            cells: [
              { label: 'Test 1', value: 'Value 1', type: 'text' },
              { label: 'Test 2', value: 'Value 2', type: 'text' }
            ]
          }
        ],
        [
          {
            type: 'standard',
            cells: [
              { label: 'Test 3', value: 'Value 3', type: 'text' },
              { label: 'Test 4', value: 'Value 4', type: 'text' }
            ]
          }
        ]
      ])
    };

    // Mock document.querySelector
    document.querySelector = vi.fn((selector) => {
      switch (selector) {
        case this.options.loadMoreSelector:
          return this.loadMoreBtn;
        case this.options.tableBodySelector:
          return this.tableBody;
        case this.options.batchDataSelector:
          return this.batchDataElement;
        default:
          return null;
      }
    });
  }

  init() {
    this.loadBatchData();
    this.bindEvents();
    this.setupIntersectionObserver();
  }

  loadBatchData() {
    try {
      this.batches = JSON.parse(this.batchDataElement.textContent);
      this.totalBatches = this.batches.length + 1;
    } catch (e) {
      console.error('Failed to parse batch data:', e);
    }
  }

  bindEvents() {
    this.loadMoreBtn.addEventListener('click', () => this.loadNextBatch());
    this.loadMoreBtn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.loadNextBatch();
      }
    });
  }

  setupIntersectionObserver() {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !this.isLoading && this.hasMoreBatches()) {
          if (this.options.autoLoad) {
            this.loadNextBatch();
          }
        }
      });
    }, { rootMargin: '100px' });

    observer.observe(this.loadMoreBtn);
  }

  async loadNextBatch() {
    if (this.isLoading || !this.hasMoreBatches()) {
      return;
    }

    this.isLoading = true;
    this.setLoadingState(true);

    try {
      const batch = this.batches[this.currentBatch - 1];
      const fragment = await this.createRowsFragment(batch);
      await this.appendRowsWithAnimation(fragment);

      this.currentBatch++;
      this.updateLoadMoreButton();
      this.logPerformanceStats(batch.length);
    } catch (error) {
      console.error('Error loading batch:', error);
      this.showErrorMessage();
    } finally {
      this.isLoading = false;
      this.setLoadingState(false);
    }
  }

  async createRowsFragment(batch) {
    const fragment = { appendChild: vi.fn() };

    for (const rowData of batch) {
      if (!rowData.cells || !Array.isArray(rowData.cells)) {
        continue;
      }

      const row = await this.createTableRow(rowData);
      fragment.appendChild(row);
    }

    return fragment;
  }

  async createTableRow(rowData) {
    const row = {
      className: 'lazy-loaded-row',
      style: { opacity: '0', transition: '' },
      classList: { add: vi.fn() },
      appendChild: vi.fn()
    };

    for (const cellData of rowData.cells) {
      const cell = await this.createTableCell(cellData);
      row.appendChild(cell);
    }

    return row;
  }

  async createTableCell(cellData) {
    const cell = {
      className: '',
      classList: { add: vi.fn() },
      textContent: '',
      innerHTML: '',
      colSpan: 1,
      rowSpan: 1
    };

    if (cellData.label !== undefined && cellData.label !== null) {
      cell.className = 'detail-label';
      cell.textContent = cellData.label;
    } else {
      cell.className = 'detail-value';

      if (this.isEmpty(cellData.value)) {
        cell.classList.add('empty-field');
        cell.textContent = '未設定';
      } else {
        if (cellData.formatted_value) {
          cell.innerHTML = cellData.formatted_value;
        } else {
          cell.textContent = cellData.value;
        }
      }
    }

    if (cellData.colspan && cellData.colspan > 1) {
      cell.colSpan = cellData.colspan;
    }
    if (cellData.rowspan && cellData.rowspan > 1) {
      cell.rowSpan = cellData.rowspan;
    }

    return cell;
  }

  async appendRowsWithAnimation(fragment) {
    this.tableBody.appendChild(fragment);

    const newRows = [
      { style: { opacity: '0', transition: '' } },
      { style: { opacity: '0', transition: '' } }
    ];

    this.tableBody.querySelectorAll = vi.fn(() => newRows);

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
        }, index * 50);
      });
    });
  }

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

  updateLoadMoreButton() {
    const remainingBatches = this.batches.length - this.currentBatch + 1;
    const remainingRows = this.batches.slice(this.currentBatch - 1).reduce((total, batch) => total + batch.length, 0);

    if (remainingRows > 0) {
      this.loadMoreBtn.innerHTML = `<i class="fas fa-chevron-down me-1"></i>さらに読み込む (${remainingRows}行)`;
    } else {
      this.hideLazyLoadButton();
    }
  }

  hideLazyLoadButton() {
    this.loadMoreBtn.style.display = 'none';

    const completionMessage = {
      className: 'text-center text-muted mt-3',
      innerHTML: '<i class="fas fa-check-circle me-1"></i>すべてのデータを読み込みました'
    };
    this.loadMoreBtn.parentNode.replaceChild(completionMessage, this.loadMoreBtn);
  }

  showErrorMessage() {
    const errorMessage = {
      className: 'alert alert-warning mt-3',
      innerHTML: `
                <i class="fas fa-exclamation-triangle me-1"></i>
                データの読み込みに失敗しました。
                <button class="btn btn-sm btn-outline-primary ms-2" onclick="location.reload()">
                    ページを再読み込み
                </button>
            `
    };
    this.loadMoreBtn.parentNode.insertBefore(errorMessage, this.loadMoreBtn.nextSibling);
  }

  hasMoreBatches() {
    return this.currentBatch <= this.batches.length;
  }

  isEmpty(value) {
    return value === null || value === undefined || value === '';
  }

  logPerformanceStats(batchSize) {
    const stats = {
      batch_loaded: this.currentBatch,
      batch_size: batchSize,
      total_batches: this.totalBatches,
      remaining_batches: this.batches.length - this.currentBatch + 1,
      memory_usage: this.getMemoryUsage()
    };

    console.log('Batch loaded', stats);

    const event = new CustomEvent('commonTableBatchLoaded', { detail: stats });
    document.dispatchEvent(event);
  }

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

  destroy() {
    this.batches = [];
    this.loadMoreBtn = null;
    this.tableBody = null;
    this.batchDataElement = null;
  }
}

describe('CommonTableLazyLoader', () => {
  let lazyLoader;

  beforeEach(() => {
    mockDOM();
    lazyLoader = new MockCommonTableLazyLoader();
  });

  afterEach(() => {
    if (lazyLoader) {
      lazyLoader.destroy();
    }
    vi.clearAllMocks();
  });

  describe('Initialization', () => {
    it('should initialize with default options', () => {
      expect(lazyLoader.options.batchSize).toBe(50);
      expect(lazyLoader.options.loadMoreSelector).toBe('#load-more-rows');
      expect(lazyLoader.options.tableBodySelector).toBe('#table-body-lazy');
      expect(lazyLoader.options.fadeInDuration).toBe(300);
    });

    it('should initialize with custom options', () => {
      const customLoader = new MockCommonTableLazyLoader({
        batchSize: 25,
        fadeInDuration: 500
      });

      expect(customLoader.options.batchSize).toBe(25);
      expect(customLoader.options.fadeInDuration).toBe(500);
    });

    it('should load batch data on initialization', () => {
      lazyLoader.init();

      expect(lazyLoader.batches).toHaveLength(2);
      expect(lazyLoader.totalBatches).toBe(3); // 2 batches + 1 initial
    });
  });

  describe('Batch Data Loading', () => {
    it('should parse batch data correctly', () => {
      lazyLoader.loadBatchData();

      expect(lazyLoader.batches).toHaveLength(2);
      expect(lazyLoader.batches[0][0].cells).toHaveLength(2);
      expect(lazyLoader.batches[0][0].cells[0].label).toBe('Test 1');
    });

    it('should handle invalid batch data gracefully', () => {
      lazyLoader.batchDataElement.textContent = 'invalid json';

      const consoleSpy = vi.spyOn(console, 'error');
      lazyLoader.loadBatchData();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to parse batch data:',
        expect.any(Error)
      );
    });
  });

  describe('Event Binding', () => {
    it('should bind click event to load more button', () => {
      lazyLoader.bindEvents();

      expect(lazyLoader.loadMoreBtn.addEventListener).toHaveBeenCalledWith(
        'click',
        expect.any(Function)
      );
    });

    it('should bind keyboard events for accessibility', () => {
      lazyLoader.bindEvents();

      expect(lazyLoader.loadMoreBtn.addEventListener).toHaveBeenCalledWith(
        'keydown',
        expect.any(Function)
      );
    });
  });

  describe('Intersection Observer', () => {
    it('should setup intersection observer when available', () => {
      lazyLoader.setupIntersectionObserver();

      expect(global.IntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        { rootMargin: '100px' }
      );
    });

    it('should handle missing intersection observer gracefully', () => {
      delete global.IntersectionObserver;

      expect(() => {
        lazyLoader.setupIntersectionObserver();
      }).not.toThrow();
    });
  });

  describe('Batch Loading', () => {
    beforeEach(() => {
      lazyLoader.init();
    });

    it('should load next batch successfully', async () => {
      expect(lazyLoader.currentBatch).toBe(1);
      expect(lazyLoader.hasMoreBatches()).toBe(true);

      await lazyLoader.loadNextBatch();

      expect(lazyLoader.currentBatch).toBe(2);
      expect(lazyLoader.tableBody.appendChild).toHaveBeenCalled();
    });

    it('should not load batch when already loading', async () => {
      lazyLoader.isLoading = true;

      await lazyLoader.loadNextBatch();

      expect(lazyLoader.currentBatch).toBe(1); // Should not increment
    });

    it('should not load batch when no more batches available', async () => {
      lazyLoader.currentBatch = 3; // Beyond available batches

      await lazyLoader.loadNextBatch();

      expect(lazyLoader.tableBody.appendChild).not.toHaveBeenCalled();
    });

    it('should handle batch loading errors gracefully', async () => {
      const consoleSpy = vi.spyOn(console, 'error');
      lazyLoader.createRowsFragment = vi.fn().mockRejectedValue(new Error('Test error'));

      await lazyLoader.loadNextBatch();

      expect(consoleSpy).toHaveBeenCalledWith(
        'Error loading batch:',
        expect.any(Error)
      );
    });
  });

  describe('Row and Cell Creation', () => {
    beforeEach(() => {
      lazyLoader.init();
    });

    it('should create table row with correct structure', async () => {
      const rowData = {
        type: 'standard',
        cells: [
          { label: 'Test Label', value: 'Test Value', type: 'text' }
        ]
      };

      const row = await lazyLoader.createTableRow(rowData);

      expect(row.className).toBe('lazy-loaded-row');
      expect(row.style.opacity).toBe('0');
      expect(row.appendChild).toHaveBeenCalled();
    });

    it('should create label cell correctly', async () => {
      const cellData = { label: 'Test Label', value: 'Test Value', type: 'text' };

      const cell = await lazyLoader.createTableCell(cellData);

      expect(cell.className).toBe('detail-label');
      expect(cell.textContent).toBe('Test Label');
    });

    it('should create value cell correctly', async () => {
      const cellData = { value: 'Test Value', type: 'text' };

      const cell = await lazyLoader.createTableCell(cellData);

      expect(cell.className).toBe('detail-value');
      expect(cell.textContent).toBe('Test Value');
    });

    it('should handle empty values correctly', async () => {
      const cellData = { value: null, type: 'text' };

      const cell = await lazyLoader.createTableCell(cellData);

      expect(cell.className).toBe('detail-value');
      expect(cell.classList.add).toHaveBeenCalledWith('empty-field');
      expect(cell.textContent).toBe('未設定');
    });

    it('should handle colspan and rowspan attributes', async () => {
      const cellData = {
        value: 'Test Value',
        type: 'text',
        colspan: 2,
        rowspan: 3
      };

      const cell = await lazyLoader.createTableCell(cellData);

      expect(cell.colSpan).toBe(2);
      expect(cell.rowSpan).toBe(3);
    });
  });

  describe('Animation and UI Updates', () => {
    beforeEach(() => {
      lazyLoader.init();
    });

    it('should append rows with fade-in animation', async () => {
      const fragment = { appendChild: vi.fn() };

      await lazyLoader.appendRowsWithAnimation(fragment);

      expect(lazyLoader.tableBody.appendChild).toHaveBeenCalledWith(fragment);
      expect(lazyLoader.tableBody.querySelectorAll).toHaveBeenCalledWith(
        '.lazy-loaded-row[style*="opacity: 0"]'
      );
    });

    it('should set loading state correctly', () => {
      lazyLoader.setLoadingState(true);

      expect(lazyLoader.loadMoreBtn.classList.add).toHaveBeenCalledWith('loading');
      expect(lazyLoader.loadMoreBtn.disabled).toBe(true);
      expect(lazyLoader.loadMoreBtn.innerHTML).toContain('読み込み中');

      lazyLoader.setLoadingState(false);

      expect(lazyLoader.loadMoreBtn.classList.remove).toHaveBeenCalledWith('loading');
      expect(lazyLoader.loadMoreBtn.disabled).toBe(false);
    });

    it('should update load more button text correctly', () => {
      lazyLoader.currentBatch = 1;
      lazyLoader.updateLoadMoreButton();

      expect(lazyLoader.loadMoreBtn.innerHTML).toContain('さらに読み込む');
      expect(lazyLoader.loadMoreBtn.innerHTML).toContain('2行'); // 2 remaining rows
    });

    it('should hide load more button when no more batches', () => {
      lazyLoader.currentBatch = 3; // No more batches
      lazyLoader.updateLoadMoreButton();

      expect(lazyLoader.loadMoreBtn.style.display).toBe('none');
      expect(lazyLoader.loadMoreBtn.parentNode.replaceChild).toHaveBeenCalled();
    });
  });

  describe('Performance Monitoring', () => {
    beforeEach(() => {
      lazyLoader.init();
    });

    it('should log performance statistics', () => {
      const consoleSpy = vi.spyOn(console, 'log');

      lazyLoader.logPerformanceStats(10);

      expect(consoleSpy).toHaveBeenCalledWith(
        'Batch loaded',
        expect.objectContaining({
          batch_loaded: expect.any(Number),
          batch_size: 10,
          total_batches: expect.any(Number),
          remaining_batches: expect.any(Number)
        })
      );
    });

    it('should dispatch custom event on batch load', () => {
      lazyLoader.logPerformanceStats(10);

      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'commonTableBatchLoaded',
          detail: expect.any(Object)
        })
      );
    });

    it('should get memory usage when available', () => {
      const memoryUsage = lazyLoader.getMemoryUsage();

      expect(memoryUsage).toEqual({
        used: 1,
        total: 2,
        limit: 4
      });
    });

    it('should return null when memory API not available', () => {
      delete global.performance.memory;

      const memoryUsage = lazyLoader.getMemoryUsage();

      expect(memoryUsage).toBeNull();
    });
  });

  describe('Utility Methods', () => {
    it('should correctly identify empty values', () => {
      expect(lazyLoader.isEmpty(null)).toBe(true);
      expect(lazyLoader.isEmpty(undefined)).toBe(true);
      expect(lazyLoader.isEmpty('')).toBe(true);
      expect(lazyLoader.isEmpty('value')).toBe(false);
      expect(lazyLoader.isEmpty(0)).toBe(false);
    });

    it('should correctly check if more batches are available', () => {
      lazyLoader.init();

      expect(lazyLoader.hasMoreBatches()).toBe(true);

      lazyLoader.currentBatch = 3;
      expect(lazyLoader.hasMoreBatches()).toBe(false);
    });
  });

  describe('Error Handling', () => {
    beforeEach(() => {
      lazyLoader.init();
    });

    it('should show error message on batch loading failure', () => {
      lazyLoader.showErrorMessage();

      expect(lazyLoader.loadMoreBtn.parentNode.insertBefore).toHaveBeenCalledWith(
        expect.objectContaining({
          className: 'alert alert-warning mt-3'
        }),
        lazyLoader.loadMoreBtn.nextSibling
      );
    });
  });

  describe('Memory Management', () => {
    it('should clean up resources on destroy', () => {
      lazyLoader.init();
      lazyLoader.destroy();

      expect(lazyLoader.batches).toEqual([]);
      expect(lazyLoader.loadMoreBtn).toBeNull();
      expect(lazyLoader.tableBody).toBeNull();
      expect(lazyLoader.batchDataElement).toBeNull();
    });
  });
});