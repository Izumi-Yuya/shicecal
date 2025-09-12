/**
 * Memory Management Utilities
 * Provides memory optimization and cleanup utilities for JavaScript applications
 */

/**
 * Memory Manager Class
 */
class MemoryManager {
  constructor(options = {}) {
    this.options = {
      cleanupInterval: 30000, // 30 seconds
      maxDomNodes: 1000,
      maxEventListeners: 100,
      enableMonitoring: true,
      enableAutoCleanup: true,
      ...options
    };

    this.trackedElements = new WeakSet();
    this.eventListeners = new Map();
    this.timers = new Set();
    this.observers = new Set();
    this.cleanupTasks = new Set();

    this.init();
  }

  /**
   * Initialize memory manager
   */
  init() {
    if (this.options.enableMonitoring) {
      this.startMemoryMonitoring();
    }

    if (this.options.enableAutoCleanup) {
      this.startAutoCleanup();
    }

    // Setup page unload cleanup
    window.addEventListener('beforeunload', () => this.cleanup());

    // Setup visibility change cleanup
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.performMaintenanceCleanup();
      }
    });
  }

  /**
   * Start memory monitoring
   */
  startMemoryMonitoring() {
    if (!('memory' in performance)) {
      console.warn('Performance memory API not available');
      return;
    }

    const monitorInterval = setInterval(() => {
      const memInfo = performance.memory;
      const usage = {
        used: memInfo.usedJSHeapSize,
        total: memInfo.totalJSHeapSize,
        limit: memInfo.jsHeapSizeLimit,
        percentage: (memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit) * 100
      };

      // Trigger cleanup if memory usage is high
      if (usage.percentage > 80) {
        console.warn('High memory usage detected:', usage);
        this.performEmergencyCleanup();
      } else if (usage.percentage > 60) {
        this.performMaintenanceCleanup();
      }

      // Dispatch memory usage event
      this.dispatchMemoryEvent('memory-usage', usage);
    }, 5000);

    this.timers.add(monitorInterval);
  }

  /**
   * Start automatic cleanup
   */
  startAutoCleanup() {
    const cleanupInterval = setInterval(() => {
      this.performMaintenanceCleanup();
    }, this.options.cleanupInterval);

    this.timers.add(cleanupInterval);
  }

  /**
   * Track DOM element for cleanup
   */
  trackElement(element) {
    if (element && element.nodeType === Node.ELEMENT_NODE) {
      this.trackedElements.add(element);
    }
  }

  /**
   * Track event listener for cleanup
   */
  trackEventListener(element, event, handler, options = {}) {
    const key = `${element.constructor.name}_${event}_${handler.name || 'anonymous'}`;

    if (!this.eventListeners.has(key)) {
      this.eventListeners.set(key, []);
    }

    this.eventListeners.get(key).push({
      element,
      event,
      handler,
      options
    });

    // Add the event listener
    element.addEventListener(event, handler, options);

    // Auto-cleanup if too many listeners
    if (this.eventListeners.size > this.options.maxEventListeners) {
      this.cleanupOldEventListeners();
    }
  }

  /**
   * Track observer for cleanup
   */
  trackObserver(observer) {
    this.observers.add(observer);
  }

  /**
   * Track timer for cleanup
   */
  trackTimer(timerId) {
    this.timers.add(timerId);
  }

  /**
   * Add cleanup task
   */
  addCleanupTask(task) {
    if (typeof task === 'function') {
      this.cleanupTasks.add(task);
    }
  }

  /**
   * Perform maintenance cleanup
   */
  performMaintenanceCleanup() {
    // Clean up detached DOM nodes
    this.cleanupDetachedNodes();

    // Clean up unused event listeners
    this.cleanupUnusedEventListeners();

    // Force garbage collection if available
    this.forceGarbageCollection();

    // Run custom cleanup tasks
    this.runCleanupTasks();

    console.log('Maintenance cleanup completed');
  }

  /**
   * Perform emergency cleanup
   */
  performEmergencyCleanup() {
    console.warn('Performing emergency memory cleanup');

    // Aggressive cleanup
    this.cleanupAllEventListeners();
    this.cleanupAllObservers();
    this.cleanupAllTimers();
    this.cleanupDetachedNodes();

    // Force multiple garbage collections
    for (let i = 0; i < 3; i++) {
      this.forceGarbageCollection();
    }

    // Run all cleanup tasks
    this.runCleanupTasks();

    // Dispatch emergency cleanup event
    this.dispatchMemoryEvent('emergency-cleanup', {
      timestamp: Date.now(),
      reason: 'high_memory_usage'
    });
  }

  /**
   * Clean up detached DOM nodes
   */
  cleanupDetachedNodes() {
    // This is a simplified approach - in practice, you'd need more sophisticated detection
    const allElements = document.querySelectorAll('*');
    let detachedCount = 0;

    allElements.forEach(element => {
      // Check if element is detached from document
      if (!document.contains(element)) {
        // Clean up any references
        if (element._eventListeners) {
          delete element._eventListeners;
        }
        if (element._observers) {
          delete element._observers;
        }
        detachedCount++;
      }
    });

    if (detachedCount > 0) {
      console.log(`Cleaned up ${detachedCount} detached DOM nodes`);
    }
  }

  /**
   * Clean up unused event listeners
   */
  cleanupUnusedEventListeners() {
    let cleanedCount = 0;

    for (const [key, listeners] of this.eventListeners.entries()) {
      const validListeners = listeners.filter(({ element }) => {
        if (!document.contains(element)) {
          cleanedCount++;
          return false;
        }
        return true;
      });

      if (validListeners.length === 0) {
        this.eventListeners.delete(key);
      } else {
        this.eventListeners.set(key, validListeners);
      }
    }

    if (cleanedCount > 0) {
      console.log(`Cleaned up ${cleanedCount} unused event listeners`);
    }
  }

  /**
   * Clean up old event listeners (FIFO)
   */
  cleanupOldEventListeners() {
    const entries = Array.from(this.eventListeners.entries());
    const toRemove = entries.slice(0, Math.floor(entries.length * 0.2)); // Remove oldest 20%

    toRemove.forEach(([key, listeners]) => {
      listeners.forEach(({ element, event, handler }) => {
        try {
          element.removeEventListener(event, handler);
        } catch (e) {
          // Ignore errors for already removed listeners
        }
      });
      this.eventListeners.delete(key);
    });

    console.log(`Cleaned up ${toRemove.length} old event listener groups`);
  }

  /**
   * Clean up all event listeners
   */
  cleanupAllEventListeners() {
    let cleanedCount = 0;

    for (const [key, listeners] of this.eventListeners.entries()) {
      listeners.forEach(({ element, event, handler }) => {
        try {
          element.removeEventListener(event, handler);
          cleanedCount++;
        } catch (e) {
          // Ignore errors
        }
      });
    }

    this.eventListeners.clear();
    console.log(`Cleaned up ${cleanedCount} event listeners`);
  }

  /**
   * Clean up all observers
   */
  cleanupAllObservers() {
    let cleanedCount = 0;

    this.observers.forEach(observer => {
      try {
        if (observer.disconnect) {
          observer.disconnect();
          cleanedCount++;
        }
      } catch (e) {
        // Ignore errors
      }
    });

    this.observers.clear();
    console.log(`Cleaned up ${cleanedCount} observers`);
  }

  /**
   * Clean up all timers
   */
  cleanupAllTimers() {
    let cleanedCount = 0;

    this.timers.forEach(timerId => {
      try {
        clearTimeout(timerId);
        clearInterval(timerId);
        cleanedCount++;
      } catch (e) {
        // Ignore errors
      }
    });

    this.timers.clear();
    console.log(`Cleaned up ${cleanedCount} timers`);
  }

  /**
   * Run custom cleanup tasks
   */
  runCleanupTasks() {
    let completedCount = 0;

    this.cleanupTasks.forEach(task => {
      try {
        task();
        completedCount++;
      } catch (e) {
        console.error('Cleanup task failed:', e);
      }
    });

    if (completedCount > 0) {
      console.log(`Completed ${completedCount} cleanup tasks`);
    }
  }

  /**
   * Force garbage collection if available
   */
  forceGarbageCollection() {
    if (window.gc) {
      window.gc();
    } else if (window.CollectGarbage) {
      window.CollectGarbage();
    }
  }

  /**
   * Dispatch memory-related events
   */
  dispatchMemoryEvent(type, detail) {
    const event = new CustomEvent(`memory-manager:${type}`, {
      detail,
      bubbles: false,
      cancelable: false
    });

    document.dispatchEvent(event);
  }

  /**
   * Get current memory usage
   */
  getMemoryUsage() {
    if ('memory' in performance) {
      const memInfo = performance.memory;
      return {
        used: memInfo.usedJSHeapSize,
        total: memInfo.totalJSHeapSize,
        limit: memInfo.jsHeapSizeLimit,
        percentage: (memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit) * 100,
        available: memInfo.jsHeapSizeLimit - memInfo.usedJSHeapSize
      };
    }

    return null;
  }

  /**
   * Get memory statistics
   */
  getStatistics() {
    return {
      trackedElements: this.trackedElements.size || 0,
      eventListeners: this.eventListeners.size,
      observers: this.observers.size,
      timers: this.timers.size,
      cleanupTasks: this.cleanupTasks.size,
      memoryUsage: this.getMemoryUsage()
    };
  }

  /**
   * Complete cleanup and destroy
   */
  cleanup() {
    this.performEmergencyCleanup();

    // Clear all tracking
    this.eventListeners.clear();
    this.observers.clear();
    this.timers.clear();
    this.cleanupTasks.clear();

    console.log('Memory manager cleanup completed');
  }
}

/**
 * DOM Node Pool for reusing elements
 */
class DOMNodePool {
  constructor(tagName, maxSize = 100) {
    this.tagName = tagName;
    this.maxSize = maxSize;
    this.pool = [];
  }

  /**
   * Get element from pool or create new one
   */
  get() {
    if (this.pool.length > 0) {
      const element = this.pool.pop();
      this.resetElement(element);
      return element;
    }

    return document.createElement(this.tagName);
  }

  /**
   * Return element to pool
   */
  release(element) {
    if (this.pool.length < this.maxSize && element.tagName.toLowerCase() === this.tagName) {
      this.resetElement(element);
      this.pool.push(element);
    }
  }

  /**
   * Reset element to clean state
   */
  resetElement(element) {
    element.innerHTML = '';
    element.className = '';
    element.removeAttribute('style');

    // Remove all attributes except essential ones
    const attributes = Array.from(element.attributes);
    attributes.forEach(attr => {
      if (!['id', 'data-pool'].includes(attr.name)) {
        element.removeAttribute(attr.name);
      }
    });
  }

  /**
   * Clear the pool
   */
  clear() {
    this.pool.length = 0;
  }
}

/**
 * Event Listener Pool for reusing handlers
 */
class EventListenerPool {
  constructor() {
    this.handlers = new Map();
  }

  /**
   * Get or create event handler
   */
  getHandler(key, factory) {
    if (!this.handlers.has(key)) {
      this.handlers.set(key, factory());
    }

    return this.handlers.get(key);
  }

  /**
   * Remove handler
   */
  removeHandler(key) {
    this.handlers.delete(key);
  }

  /**
   * Clear all handlers
   */
  clear() {
    this.handlers.clear();
  }
}

/**
 * Memory-optimized table row manager
 */
class TableRowManager {
  constructor(options = {}) {
    this.options = {
      maxVisibleRows: 100,
      rowHeight: 40,
      bufferSize: 10,
      ...options
    };

    this.rowPool = new DOMNodePool('tr', 200);
    this.cellPool = new DOMNodePool('td', 1000);
    this.visibleRows = new Map();
    this.scrollContainer = null;
  }

  /**
   * Initialize with scroll container
   */
  init(scrollContainer) {
    this.scrollContainer = scrollContainer;
    this.setupScrollHandler();
  }

  /**
   * Setup scroll handler for virtual scrolling
   */
  setupScrollHandler() {
    if (!this.scrollContainer) return;

    const handleScroll = throttle(() => {
      this.updateVisibleRows();
    }, 16);

    this.scrollContainer.addEventListener('scroll', handleScroll);
  }

  /**
   * Update visible rows based on scroll position
   */
  updateVisibleRows() {
    if (!this.scrollContainer) return;

    const scrollTop = this.scrollContainer.scrollTop;
    const containerHeight = this.scrollContainer.clientHeight;

    const startIndex = Math.floor(scrollTop / this.options.rowHeight);
    const endIndex = Math.min(
      startIndex + Math.ceil(containerHeight / this.options.rowHeight) + this.options.bufferSize,
      this.totalRows || 0
    );

    // Remove rows that are no longer visible
    for (const [index, row] of this.visibleRows.entries()) {
      if (index < startIndex || index > endIndex) {
        this.recycleRow(row);
        this.visibleRows.delete(index);
      }
    }

    // Add new visible rows
    for (let i = startIndex; i <= endIndex; i++) {
      if (!this.visibleRows.has(i)) {
        const row = this.createRow(i);
        if (row) {
          this.visibleRows.set(i, row);
        }
      }
    }
  }

  /**
   * Create table row
   */
  createRow(index) {
    const row = this.rowPool.get();
    row.dataset.index = index;

    // Add cells based on column configuration
    const columnCount = this.getColumnCount();
    for (let i = 0; i < columnCount; i++) {
      const cell = this.cellPool.get();
      row.appendChild(cell);
    }

    return row;
  }

  /**
   * Recycle table row
   */
  recycleRow(row) {
    // Return cells to pool
    const cells = Array.from(row.children);
    cells.forEach(cell => {
      row.removeChild(cell);
      this.cellPool.release(cell);
    });

    // Return row to pool
    this.rowPool.release(row);
  }

  /**
   * Get column count (override in implementation)
   */
  getColumnCount() {
    return 5; // Default
  }

  /**
   * Cleanup
   */
  cleanup() {
    this.visibleRows.forEach(row => this.recycleRow(row));
    this.visibleRows.clear();
    this.rowPool.clear();
    this.cellPool.clear();
  }
}

// Utility functions
function throttle(func, limit) {
  let inThrottle;
  return function () {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}

// Create global memory manager instance
const globalMemoryManager = new MemoryManager({
  cleanupInterval: 30000,
  maxDomNodes: 1000,
  enableMonitoring: true,
  enableAutoCleanup: true
});

// Export classes and utilities
export {
  MemoryManager,
  DOMNodePool,
  EventListenerPool,
  TableRowManager,
  globalMemoryManager,
  throttle
};