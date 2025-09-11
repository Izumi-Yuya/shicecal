/**
 * Enhanced DOM Manager with performance optimizations
 * Provides efficient DOM operations with automatic caching and batch updates
 */
export class DOMManager {
  constructor() {
    this.cache = new Map();
    this.batchedUpdates = new Map();
    this.updateScheduled = false;
    this.observers = new WeakMap();

    // Performance metrics
    this.metrics = {
      cacheHits: 0,
      cacheMisses: 0,
      batchedOperations: 0
    };
  }

  /**
   * Get element with automatic caching
   * @param {string} selector 
   * @returns {HTMLElement|null}
   */
  get(selector) {
    if (this.cache.has(selector)) {
      const element = this.cache.get(selector);
      if (document.contains(element)) {
        this.metrics.cacheHits++;
        return element;
      } else {
        this.cache.delete(selector);
      }
    }

    this.metrics.cacheMisses++;
    const element = document.querySelector(selector);
    if (element) {
      this.cache.set(selector, element);
      this.setupElementObserver(selector, element);
    }
    return element;
  }

  /**
   * Get multiple elements efficiently
   * @param {string[]} selectors 
   * @returns {Map<string, HTMLElement>}
   */
  getMultiple(selectors) {
    const results = new Map();
    const uncachedSelectors = [];

    // Check cache first
    selectors.forEach(selector => {
      const cached = this.cache.get(selector);
      if (cached && document.contains(cached)) {
        results.set(selector, cached);
        this.metrics.cacheHits++;
      } else {
        uncachedSelectors.push(selector);
        this.metrics.cacheMisses++;
      }
    });

    // Batch query uncached elements
    if (uncachedSelectors.length > 0) {
      const combinedSelector = uncachedSelectors.join(', ');
      const elements = document.querySelectorAll(combinedSelector);

      elements.forEach(element => {
        const matchingSelector = uncachedSelectors.find(selector =>
          element.matches(selector)
        );
        if (matchingSelector) {
          results.set(matchingSelector, element);
          this.cache.set(matchingSelector, element);
          this.setupElementObserver(matchingSelector, element);
        }
      });
    }

    return results;
  }

  /**
   * Schedule batched DOM update
   * @param {string} selector 
   * @param {Function} updateFn 
   * @param {number} priority 
   */
  scheduleUpdate(selector, updateFn, priority = 0) {
    if (!this.batchedUpdates.has(priority)) {
      this.batchedUpdates.set(priority, new Map());
    }

    this.batchedUpdates.get(priority).set(selector, updateFn);

    if (!this.updateScheduled) {
      this.updateScheduled = true;
      requestAnimationFrame(() => this.processBatchedUpdates());
    }
  }

  /**
   * Process all batched updates
   */
  processBatchedUpdates() {
    const priorities = Array.from(this.batchedUpdates.keys()).sort((a, b) => b - a);

    priorities.forEach(priority => {
      const updates = this.batchedUpdates.get(priority);

      updates.forEach((updateFn, selector) => {
        const element = this.get(selector);
        if (element) {
          try {
            updateFn(element);
            this.metrics.batchedOperations++;
          } catch (error) {
            console.error(`Error in batched update for ${selector}:`, error);
          }
        }
      });

      updates.clear();
    });

    this.batchedUpdates.clear();
    this.updateScheduled = false;
  }

  /**
   * Set up observer for element removal
   * @param {string} selector 
   * @param {HTMLElement} element 
   */
  setupElementObserver(selector, element) {
    if (!('MutationObserver' in window)) return;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach(mutation => {
        mutation.removedNodes.forEach(node => {
          if (node === element || (node.contains && node.contains(element))) {
            this.cache.delete(selector);
            observer.disconnect();
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    this.observers.set(element, observer);
  }

  /**
   * Batch DOM reads and writes for better performance
   * @param {Function[]} reads 
   * @param {Function[]} writes 
   */
  batchReadWrite(reads = [], writes = []) {
    // Execute all reads first
    const readResults = reads.map(readFn => {
      try {
        return readFn();
      } catch (error) {
        console.error('Error in batched read:', error);
        return null;
      }
    });

    // Then execute all writes
    requestAnimationFrame(() => {
      writes.forEach((writeFn, index) => {
        try {
          writeFn(readResults[index]);
        } catch (error) {
          console.error('Error in batched write:', error);
        }
      });
    });

    return readResults;
  }

  /**
   * Create optimized event delegation
   * @param {string} containerSelector 
   * @param {string} targetSelector 
   * @param {string} eventType 
   * @param {Function} handler 
   */
  delegate(containerSelector, targetSelector, eventType, handler) {
    const container = this.get(containerSelector);
    if (!container) return null;

    const delegatedHandler = (event) => {
      const target = event.target.closest(targetSelector);
      if (target && container.contains(target)) {
        handler(event, target);
      }
    };

    container.addEventListener(eventType, delegatedHandler);

    return () => container.removeEventListener(eventType, delegatedHandler);
  }

  /**
   * Get performance metrics
   * @returns {Object}
   */
  getMetrics() {
    return {
      ...this.metrics,
      cacheSize: this.cache.size,
      hitRate: this.metrics.cacheHits / (this.metrics.cacheHits + this.metrics.cacheMisses) * 100
    };
  }

  /**
   * Clear cache and observers
   */
  clear() {
    this.cache.clear();
    this.batchedUpdates.clear();
    this.updateScheduled = false;

    // Disconnect all observers
    this.observers = new WeakMap();
  }
}