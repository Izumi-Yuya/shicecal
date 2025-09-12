/**
 * Table Performance Management Module
 * Handles performance optimizations for large tables including
 * virtual scrolling, lazy loading, and memory management
 */

import { debounce, throttle } from '../shared/utils.js';
import { globalMemoryManager, DOMNodePool, TableRowManager } from '../shared/memory-manager.js';

/**
 * Table Performance Manager Class
 */
class TablePerformanceManager {
  constructor(tableElement, options = {}) {
    // Validate table element
    if (!tableElement || !tableElement.tagName || tableElement.tagName.toLowerCase() !== 'table') {
      console.error('TablePerformanceManager requires a valid table element');
      return;
    }

    this.table = tableElement;
    this.options = {
      strategy: 'full_render',
      chunkSize: 50,
      loadIncrement: 25,
      rowHeight: 40,
      bufferSize: 10,
      enableMetrics: true,
      enableMemoryOptimization: true,
      maxMemoryUsage: 80, // percentage
      ...options
    };

    this.strategy = this.table.dataset.performanceStrategy || this.options.strategy;
    this.metrics = {
      renderTime: 0,
      scrollEvents: 0,
      memoryUsage: 0,
      loadedRows: 0
    };

    // Memory optimization components
    if (this.options.enableMemoryOptimization) {
      this.rowManager = new TableRowManager({
        maxVisibleRows: this.options.chunkSize,
        rowHeight: this.options.rowHeight,
        bufferSize: this.options.bufferSize
      });

      this.domPools = {
        rows: new DOMNodePool('tr', 100),
        cells: new DOMNodePool('td', 500)
      };
    }

    this.init();
  }

  /**
   * Initialize the performance manager
   */
  init() {
    // Early return if table is invalid
    if (!this.table) {
      return;
    }

    this.setupEventDelegation();
    this.setupPerformanceObserver();
    this.initializeStrategy();

    if (this.options.enableMetrics) {
      this.startMetricsCollection();
    }

    // Setup memory optimization
    if (this.options.enableMemoryOptimization) {
      this.setupMemoryOptimization();
    }

    // Register with global memory manager
    globalMemoryManager.addCleanupTask(() => this.performMemoryCleanup());
  }

  /**
   * Setup event delegation for better performance
   */
  setupEventDelegation() {
    // Use event delegation to minimize event listeners
    this.table.addEventListener('click', this.handleClick.bind(this));
    this.table.addEventListener('mouseover', this.handleMouseOver.bind(this));

    // Throttled scroll handler
    if (this.strategy === 'virtual_scroll') {
      const container = this.table.closest('.virtual-scroll-container');
      if (container) {
        container.addEventListener('scroll',
          throttle(this.handleVirtualScroll.bind(this), 16)
        );
      }
    }

    // Debounced resize handler
    window.addEventListener('resize',
      debounce(this.handleResize.bind(this), 250)
    );
  }

  /**
   * Setup performance observer for monitoring
   */
  setupPerformanceObserver() {
    if ('PerformanceObserver' in window) {
      try {
        this.performanceObserver = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          entries.forEach(entry => {
            if (entry.name.includes('table-render')) {
              this.metrics.renderTime = entry.duration;
            }
          });
        });

        this.performanceObserver.observe({ entryTypes: ['measure'] });
      } catch (e) {
        console.warn('Performance Observer not supported:', e);
      }
    }
  }

  /**
   * Initialize strategy-specific functionality
   */
  initializeStrategy() {
    switch (this.strategy) {
      case 'virtual_scroll':
        this.initVirtualScroll();
        break;
      case 'lazy_loading':
        this.initLazyLoading();
        break;
      case 'pagination':
        this.initPagination();
        break;
      default:
        this.initFullRender();
        break;
    }
  }

  /**
   * Initialize virtual scrolling
   */
  initVirtualScroll() {
    const container = this.table.closest('.virtual-scroll-container');
    if (!container) return;

    this.virtualScroll = {
      container,
      rowHeight: this.options.rowHeight,
      visibleRows: Math.ceil(container.clientHeight / this.options.rowHeight),
      scrollTop: 0,
      totalRows: parseInt(this.table.dataset.rowCount) || 0,
      renderedChunks: new Map(),
      visibleRange: { start: 0, end: 0 }
    };

    this.renderVisibleRows();
    this.updateScrollIndicator();
  }

  /**
   * Handle virtual scroll events
   */
  handleVirtualScroll() {
    if (!this.virtualScroll) return;

    const { container, rowHeight } = this.virtualScroll;
    this.virtualScroll.scrollTop = container.scrollTop;

    // Calculate visible range
    const startIndex = Math.floor(this.virtualScroll.scrollTop / rowHeight);
    const endIndex = Math.min(
      startIndex + this.virtualScroll.visibleRows + this.options.bufferSize,
      this.virtualScroll.totalRows
    );

    // Only update if range changed significantly
    if (Math.abs(startIndex - this.virtualScroll.visibleRange.start) > 5) {
      this.virtualScroll.visibleRange = { start: startIndex, end: endIndex };
      this.renderVisibleRows();
    }

    this.metrics.scrollEvents++;
    this.updateScrollIndicator();
  }

  /**
   * Render visible rows for virtual scrolling
   */
  renderVisibleRows() {
    if (!this.virtualScroll) return;

    performance.mark('table-render-start');

    const { start, end } = this.virtualScroll.visibleRange;
    const tbody = this.table.querySelector('tbody');

    if (!tbody) return;

    // Clear existing rows
    tbody.innerHTML = '';

    // Create spacer for scrolled content
    const topSpacer = document.createElement('tr');
    topSpacer.style.height = `${start * this.virtualScroll.rowHeight}px`;
    topSpacer.className = 'virtual-scroll-spacer';
    tbody.appendChild(topSpacer);

    // Load and render visible chunk
    this.loadDataChunk(start, end).then(chunkData => {
      const fragment = document.createDocumentFragment();

      chunkData.forEach((rowData, index) => {
        const row = this.createTableRow(rowData, start + index);
        fragment.appendChild(row);
      });

      tbody.appendChild(fragment);

      // Bottom spacer
      const bottomSpacer = document.createElement('tr');
      const remainingRows = this.virtualScroll.totalRows - end;
      bottomSpacer.style.height = `${remainingRows * this.virtualScroll.rowHeight}px`;
      bottomSpacer.className = 'virtual-scroll-spacer';
      tbody.appendChild(bottomSpacer);

      performance.mark('table-render-end');
      performance.measure('table-render', 'table-render-start', 'table-render-end');
    });
  }

  /**
   * Initialize lazy loading
   */
  initLazyLoading() {
    this.lazyLoading = {
      loadedRows: parseInt(this.table.dataset.loadedRows) || 0,
      totalRows: parseInt(this.table.dataset.totalRows) || 0,
      loadIncrement: parseInt(this.table.dataset.loadIncrement) || this.options.loadIncrement,
      loading: false,
      observer: null
    };

    this.setupIntersectionObserver();
  }

  /**
   * Setup intersection observer for lazy loading
   */
  setupIntersectionObserver() {
    const trigger = this.table.parentElement.querySelector('.lazy-loading-trigger');
    if (!trigger) return;

    this.lazyLoading.observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !this.lazyLoading.loading) {
          this.loadMoreRows();
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '50px'
    });

    this.lazyLoading.observer.observe(trigger);
  }

  /**
   * Load more rows for lazy loading
   */
  async loadMoreRows() {
    if (this.lazyLoading.loadedRows >= this.lazyLoading.totalRows) return;

    this.lazyLoading.loading = true;
    const trigger = this.table.parentElement.querySelector('.lazy-loading-trigger');

    if (trigger) {
      trigger.querySelector('.lazy-loading-spinner').style.display = 'block';
    }

    try {
      const startIndex = this.lazyLoading.loadedRows;
      const endIndex = Math.min(
        startIndex + this.lazyLoading.loadIncrement,
        this.lazyLoading.totalRows
      );

      const newData = await this.loadDataChunk(startIndex, endIndex);
      this.appendRows(newData);

      this.lazyLoading.loadedRows = endIndex;
      this.metrics.loadedRows = endIndex;

      // Hide trigger if all data loaded
      if (endIndex >= this.lazyLoading.totalRows && trigger) {
        trigger.style.display = 'none';
      }

    } catch (error) {
      console.error('Failed to load more rows:', error);
    } finally {
      this.lazyLoading.loading = false;
      if (trigger) {
        trigger.querySelector('.lazy-loading-spinner').style.display = 'none';
      }
    }
  }

  /**
   * Initialize pagination
   */
  initPagination() {
    this.pagination = {
      currentPage: 1,
      perPage: parseInt(this.table.dataset.perPage) || 100,
      totalRows: parseInt(this.table.dataset.totalRows) || 0,
      totalPages: 0
    };

    this.pagination.totalPages = Math.ceil(this.pagination.totalRows / this.pagination.perPage);
    this.createPaginationControls();
  }

  /**
   * Create pagination controls
   */
  createPaginationControls() {
    const wrapper = this.table.closest('.universal-table-wrapper');
    if (!wrapper) return;

    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'table-pagination d-flex justify-content-between align-items-center mt-3';

    // Page info
    const pageInfo = document.createElement('div');
    pageInfo.className = 'page-info';
    pageInfo.textContent = `${this.pagination.currentPage} / ${this.pagination.totalPages} ページ`;

    // Navigation buttons
    const navButtons = document.createElement('div');
    navButtons.className = 'pagination-nav';

    const prevButton = document.createElement('button');
    prevButton.className = 'btn btn-outline-primary btn-sm me-2';
    prevButton.textContent = '前へ';
    prevButton.disabled = this.pagination.currentPage === 1;
    prevButton.addEventListener('click', () => this.goToPage(this.pagination.currentPage - 1));

    const nextButton = document.createElement('button');
    nextButton.className = 'btn btn-outline-primary btn-sm';
    nextButton.textContent = '次へ';
    nextButton.disabled = this.pagination.currentPage === this.pagination.totalPages;
    nextButton.addEventListener('click', () => this.goToPage(this.pagination.currentPage + 1));

    navButtons.appendChild(prevButton);
    navButtons.appendChild(nextButton);

    paginationContainer.appendChild(pageInfo);
    paginationContainer.appendChild(navButtons);

    wrapper.appendChild(paginationContainer);
  }

  /**
   * Go to specific page
   */
  async goToPage(page) {
    if (page < 1 || page > this.pagination.totalPages) return;

    this.pagination.currentPage = page;
    const startIndex = (page - 1) * this.pagination.perPage;
    const endIndex = Math.min(startIndex + this.pagination.perPage, this.pagination.totalRows);

    try {
      const pageData = await this.loadDataChunk(startIndex, endIndex);
      this.replaceTableData(pageData);
      this.updatePaginationControls();
    } catch (error) {
      console.error('Failed to load page:', error);
    }
  }

  /**
   * Initialize full render (default strategy)
   */
  initFullRender() {
    // Apply basic optimizations even for full render
    this.optimizeExistingTable();
  }

  /**
   * Optimize existing table for better performance
   */
  optimizeExistingTable() {
    if (!this.table) {
      return;
    }

    // Add CSS containment
    if (this.table.style) {
      this.table.style.contain = 'layout style';
    }

    // Optimize images
    const images = this.table.querySelectorAll('img');
    images.forEach(img => {
      if (!img.loading) {
        img.loading = 'lazy';
      }
    });

    // Add hover optimization - use handleMouseOver instead of optimizeRowHover directly
    if (this.table.addEventListener) {
      this.table.addEventListener('mouseover', this.handleMouseOver.bind(this));
    }
  }

  /**
   * Load data chunk from server
   */
  async loadDataChunk(startIndex, endIndex) {
    // This would typically make an AJAX request to load data
    // For now, return mock data
    const chunkSize = endIndex - startIndex;
    const mockData = [];

    for (let i = 0; i < chunkSize; i++) {
      mockData.push({
        id: startIndex + i,
        name: `Row ${startIndex + i}`,
        value: `Value ${startIndex + i}`
      });
    }

    return new Promise(resolve => {
      setTimeout(() => resolve(mockData), 100); // Simulate network delay
    });
  }

  /**
   * Create table row element
   */
  createTableRow(rowData, index) {
    // Use optimized version if memory optimization is enabled
    if (this.options.enableMemoryOptimization) {
      return this.createOptimizedTableRow(rowData, index);
    }

    const row = document.createElement('tr');
    row.dataset.index = index;

    // Create cells based on table structure
    const columns = this.getTableColumns();
    columns.forEach(column => {
      const cell = document.createElement('td');
      cell.textContent = rowData[column.key] || '';
      cell.className = column.className || '';
      row.appendChild(cell);
    });

    return row;
  }

  /**
   * Get table column configuration
   */
  getTableColumns() {
    // Extract column info from table header
    const headers = this.table.querySelectorAll('thead th');
    return Array.from(headers).map((header, index) => ({
      key: header.dataset.key || `col_${index}`,
      label: header.textContent.trim(),
      className: header.className
    }));
  }

  /**
   * Append rows to table
   */
  appendRows(rowsData) {
    const tbody = this.table.querySelector('tbody');
    if (!tbody) return;

    const fragment = document.createDocumentFragment();

    rowsData.forEach((rowData, index) => {
      const row = this.createTableRow(rowData, this.lazyLoading.loadedRows + index);
      fragment.appendChild(row);
    });

    tbody.appendChild(fragment);
  }

  /**
   * Replace table data (for pagination)
   */
  replaceTableData(rowsData) {
    const tbody = this.table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    this.appendRows(rowsData);
  }

  /**
   * Handle click events
   */
  handleClick(event) {
    const target = event.target.closest('[data-action]');
    if (target) {
      const action = target.dataset.action;
      this.executeAction(action, target, event);
    }
  }

  /**
   * Handle mouseover events
   */
  handleMouseOver(event) {
    if (!event || !event.target) {
      return;
    }

    const row = event.target.closest('tr');
    if (row && row.classList && !row.classList.contains('hover-optimized')) {
      this.optimizeRowHover(row);
    }
  }

  /**
   * Optimize row hover effects
   */
  optimizeRowHover(row) {
    // Ensure row is a valid DOM element
    if (!row || !row.classList || !row.style) {
      return;
    }

    // Check if already optimized
    if (row.classList.contains('hover-optimized')) {
      return;
    }

    row.classList.add('hover-optimized');
    row.style.willChange = 'background-color';

    // Clean up after hover
    const cleanup = () => {
      if (row && row.style) {
        row.style.willChange = 'auto';
      }
      if (row && row.removeEventListener) {
        row.removeEventListener('mouseleave', cleanup);
      }
    };

    if (row.addEventListener) {
      row.addEventListener('mouseleave', cleanup);
    }
  }

  /**
   * Handle resize events
   */
  handleResize() {
    if (this.virtualScroll) {
      const { container } = this.virtualScroll;
      this.virtualScroll.visibleRows = Math.ceil(container.clientHeight / this.virtualScroll.rowHeight);
      this.renderVisibleRows();
    }
  }

  /**
   * Update scroll indicator
   */
  updateScrollIndicator() {
    if (!this.virtualScroll) return;

    const { container, totalRows, rowHeight } = this.virtualScroll;
    const scrollPercentage = container.scrollTop / (totalRows * rowHeight - container.clientHeight);

    // Update any scroll indicators
    const indicators = container.querySelectorAll('.scroll-indicator');
    indicators.forEach(indicator => {
      indicator.style.transform = `translateY(${scrollPercentage * 100}%)`;
    });
  }

  /**
   * Start metrics collection
   */
  startMetricsCollection() {
    this.metricsInterval = setInterval(() => {
      this.collectMetrics();
    }, 5000); // Collect every 5 seconds
  }

  /**
   * Collect performance metrics
   */
  collectMetrics() {
    if ('memory' in performance) {
      this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
    }

    // Send metrics to server if needed
    if (this.options.sendMetrics) {
      this.sendMetricsToServer();
    }
  }

  /**
   * Send metrics to server
   */
  async sendMetricsToServer() {
    try {
      await fetch('/api/table-performance-metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          table_id: this.table.dataset.tableId,
          strategy: this.strategy,
          metrics: this.metrics
        })
      });
    } catch (error) {
      console.warn('Failed to send metrics:', error);
    }
  }

  /**
   * Execute action
   */
  executeAction(action, target, event) {
    switch (action) {
      case 'load-more':
        this.loadMoreRows();
        break;
      case 'refresh':
        this.refresh();
        break;
      default:
        console.warn('Unknown action:', action);
    }
  }

  /**
   * Refresh table data
   */
  refresh() {
    // Clear caches and reload
    if (this.virtualScroll) {
      this.virtualScroll.renderedChunks.clear();
      this.renderVisibleRows();
    } else if (this.lazyLoading) {
      this.lazyLoading.loadedRows = 0;
      this.loadMoreRows();
    }
  }

  /**
   * Setup memory optimization
   */
  setupMemoryOptimization() {
    // Monitor memory usage
    this.memoryMonitorInterval = setInterval(() => {
      this.checkMemoryUsage();
    }, 10000); // Check every 10 seconds

    // Setup automatic cleanup triggers
    this.setupMemoryCleanupTriggers();

    // Initialize row manager if using virtual scrolling
    if (this.strategy === 'virtual_scroll' && this.rowManager) {
      const container = this.table.closest('.virtual-scroll-container');
      if (container) {
        this.rowManager.init(container);
      }
    }
  }

  /**
   * Setup memory cleanup triggers
   */
  setupMemoryCleanupTriggers() {
    // Cleanup on visibility change
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.performMemoryCleanup();
      }
    });

    // Cleanup on memory pressure
    if ('memory' in performance) {
      const checkMemoryPressure = () => {
        const memInfo = performance.memory;
        const usagePercentage = (memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit) * 100;

        if (usagePercentage > this.options.maxMemoryUsage) {
          this.performMemoryCleanup();
        }
      };

      this.memoryPressureInterval = setInterval(checkMemoryPressure, 5000);
    }
  }

  /**
   * Check memory usage and trigger cleanup if needed
   */
  checkMemoryUsage() {
    if ('memory' in performance) {
      const memInfo = performance.memory;
      const usagePercentage = (memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit) * 100;

      this.metrics.memoryUsage = usagePercentage;

      if (usagePercentage > this.options.maxMemoryUsage) {
        console.warn(`High memory usage detected: ${usagePercentage.toFixed(2)}%`);
        this.performMemoryCleanup();
      }
    }
  }

  /**
   * Perform memory cleanup
   */
  performMemoryCleanup() {
    console.log('Performing table memory cleanup');

    // Clean up DOM pools
    if (this.domPools) {
      Object.values(this.domPools).forEach(pool => {
        if (pool.clear) {
          pool.clear();
        }
      });
    }

    // Clean up row manager
    if (this.rowManager && this.rowManager.cleanup) {
      this.rowManager.cleanup();
    }

    // Clean up cached data
    if (this.virtualScroll && this.virtualScroll.renderedChunks) {
      this.virtualScroll.renderedChunks.clear();
    }

    // Remove unused event listeners
    this.cleanupUnusedEventListeners();

    // Force garbage collection if available
    if (window.gc) {
      window.gc();
    }

    console.log('Table memory cleanup completed');
  }

  /**
   * Clean up unused event listeners
   */
  cleanupUnusedEventListeners() {
    // Remove event listeners from detached elements
    const allElements = this.table.querySelectorAll('*');
    allElements.forEach(element => {
      if (!document.contains(element)) {
        // Element is detached, clean up any references
        if (element._eventListeners) {
          delete element._eventListeners;
        }
      }
    });
  }

  /**
   * Create table row with memory optimization
   */
  createOptimizedTableRow(rowData, index) {
    let row;

    // Use DOM pool if available
    if (this.domPools && this.domPools.rows) {
      row = this.domPools.rows.get();
    } else {
      row = document.createElement('tr');
    }

    row.dataset.index = index;

    // Create cells based on table structure
    const columns = this.getTableColumns();
    columns.forEach(column => {
      let cell;

      // Use DOM pool if available
      if (this.domPools && this.domPools.cells) {
        cell = this.domPools.cells.get();
      } else {
        cell = document.createElement('td');
      }

      cell.textContent = rowData[column.key] || '';
      cell.className = column.className || '';
      row.appendChild(cell);
    });

    return row;
  }

  /**
   * Recycle table row to memory pool
   */
  recycleTableRow(row) {
    if (!this.domPools) return;

    // Return cells to pool
    const cells = Array.from(row.children);
    cells.forEach(cell => {
      row.removeChild(cell);
      if (this.domPools.cells) {
        this.domPools.cells.release(cell);
      }
    });

    // Return row to pool
    if (this.domPools.rows) {
      this.domPools.rows.release(row);
    }
  }

  /**
   * Implement pagination with memory optimization
   */
  implementMemoryOptimizedPagination(data, page = 1, perPage = 100) {
    // Clear existing rows
    const tbody = this.table.querySelector('tbody');
    if (tbody) {
      // Recycle existing rows
      const existingRows = Array.from(tbody.children);
      existingRows.forEach(row => {
        tbody.removeChild(row);
        this.recycleTableRow(row);
      });
    }

    // Calculate pagination
    const startIndex = (page - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, data.length);
    const pageData = data.slice(startIndex, endIndex);

    // Create new rows using memory optimization
    const fragment = document.createDocumentFragment();
    pageData.forEach((rowData, index) => {
      const row = this.createOptimizedTableRow(rowData, startIndex + index);
      fragment.appendChild(row);
    });

    if (tbody) {
      tbody.appendChild(fragment);
    }

    return {
      currentPage: page,
      totalPages: Math.ceil(data.length / perPage),
      totalItems: data.length,
      itemsPerPage: perPage
    };
  }

  /**
   * Destroy and cleanup
   */
  destroy() {
    // Perform final memory cleanup
    this.performMemoryCleanup();

    // Clean up observers
    if (this.performanceObserver) {
      this.performanceObserver.disconnect();
    }

    if (this.lazyLoading?.observer) {
      this.lazyLoading.observer.disconnect();
    }

    // Clear intervals
    if (this.metricsInterval) {
      clearInterval(this.metricsInterval);
    }

    if (this.memoryMonitorInterval) {
      clearInterval(this.memoryMonitorInterval);
    }

    if (this.memoryPressureInterval) {
      clearInterval(this.memoryPressureInterval);
    }

    // Remove event listeners
    this.table.removeEventListener('click', this.handleClick);
    this.table.removeEventListener('mouseover', this.handleMouseOver);

    const container = this.table.closest('.virtual-scroll-container');
    if (container) {
      container.removeEventListener('scroll', this.handleVirtualScroll);
    }

    window.removeEventListener('resize', this.handleResize);

    // Clean up memory optimization components
    if (this.rowManager && this.rowManager.cleanup) {
      this.rowManager.cleanup();
    }

    if (this.domPools) {
      Object.values(this.domPools).forEach(pool => {
        if (pool.clear) {
          pool.clear();
        }
      });
    }
  }
}

/**
 * Initialize table performance managers
 */
function initializeTablePerformance() {
  const tables = document.querySelectorAll('.performance-optimized');
  const managers = [];

  tables.forEach(table => {
    const strategy = table.dataset.performanceStrategy || 'full_render';
    const options = {
      strategy,
      enableMetrics: true,
      sendMetrics: false // Set to true if you want to send metrics to server
    };

    const manager = new TablePerformanceManager(table, options);
    managers.push(manager);

    // Store reference for cleanup
    table._performanceManager = manager;
  });

  return managers;
}

/**
 * Cleanup all performance managers
 */
function cleanupTablePerformance() {
  const tables = document.querySelectorAll('.performance-optimized');

  tables.forEach(table => {
    if (table._performanceManager) {
      table._performanceManager.destroy();
      delete table._performanceManager;
    }
  });
}

// Note: Initialization is handled by app.js to avoid double initialization
// Cleanup on page unload
window.addEventListener('beforeunload', cleanupTablePerformance);

export {
  TablePerformanceManager,
  initializeTablePerformance,
  cleanupTablePerformance
};