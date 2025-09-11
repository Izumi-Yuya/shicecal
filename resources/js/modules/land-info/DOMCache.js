/**
 * DOM Element Caching for Performance Optimization
 */
export class DOMCache {
  constructor() {
    this.cache = new Map();
    this.observers = new Map();
    this.initialized = false;
  }

  /**
   * Initialize DOM cache with commonly used elements
   */
  initialize() {
    if (this.initialized) return;

    // Use DocumentFragment for efficient batch queries
    this.batchCacheElements();
    this.setupMutationObserver();

    this.initialized = true;
  }

  /**
   * Batch cache elements for better performance
   */
  batchCacheElements() {
    const elementIds = [
      'ownership_type', 'landInfoForm',
      'purchase_price', 'site_area_tsubo', 'unit_price_display',
      'contract_start_date', 'contract_end_date', 'contract_period_display',
      'owned_section', 'leased_section', 'management_section',
      'owner_section', 'file_section'
    ];

    // Use DocumentFragment for efficient DOM operations
    const fragment = document.createDocumentFragment();

    // Batch query with performance timing
    const startTime = performance.now();

    // Use more efficient selector strategy
    const elements = this.batchQueryElements(elementIds);

    elements.forEach(element => {
      this.cache.set(element.id, element);
      this.setupElementObserver(element);
    });

    const endTime = performance.now();
    this.metrics.cacheInitTime = endTime - startTime;
  }

  /**
   * Efficient batch element querying
   */
  batchQueryElements(elementIds) {
    // Try to use getElementById for better performance when possible
    const elements = [];
    const notFound = [];

    // First pass: direct getElementById (fastest)
    elementIds.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        elements.push(element);
      } else {
        notFound.push(id);
      }
    });

    // Second pass: querySelectorAll for remaining elements
    if (notFound.length > 0) {
      const selector = notFound.map(id => `#${id}`).join(', ');
      const foundElements = document.querySelectorAll(selector);
      elements.push(...foundElements);
    }

    return elements;
  }

  /**
   * Setup individual element observer
   */
  setupElementObserver(element) {
    if (!this.elementObservers) {
      this.elementObservers = new WeakMap();
    }

    // Use WeakMap to avoid memory leaks
    this.elementObservers.set(element, {
      id: element.id,
      lastAccessed: Date.now(),
      accessCount: 0
    });
  }

  /**
   * Setup single mutation observer for all elements
   */
  setupMutationObserver() {
    if (!('MutationObserver' in window)) return;

    this.globalObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.removedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            this.handleNodeRemoval(node);
          }
        });
      });
    });

    this.globalObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  /**
   * Handle node removal efficiently
   * @param {Element} removedNode 
   */
  handleNodeRemoval(removedNode) {
    // Check if any cached elements were removed
    for (const [id, element] of this.cache.entries()) {
      if (removedNode === element || removedNode.contains(element)) {
        this.cache.delete(id);
      }
    }
  }

  /**
   * Get cached element or query DOM if not cached
   * @param {string} id 
   * @returns {HTMLElement|null}
   */
  get(id) {
    if (this.cache.has(id)) {
      const element = this.cache.get(id);
      // Verify element is still in DOM
      if (document.contains(element)) {
        return element;
      } else {
        // Element was removed, update cache
        this.cache.delete(id);
        this.stopObserving(id);
      }
    }

    // Query DOM and cache result
    const element = document.getElementById(id);
    if (element) {
      this.cache.set(id, element);
      this.observeElement(id, element);
    }

    return element;
  }

  /**
   * Get multiple elements at once
   * @param {string[]} ids 
   * @returns {Object}
   */
  getMultiple(ids) {
    const elements = {};
    ids.forEach(id => {
      elements[id] = this.get(id);
    });
    return elements;
  }

  /**
   * Observe element for removal from DOM
   * @param {string} id 
   * @param {HTMLElement} element 
   */
  observeElement(id, element) {
    if (!('MutationObserver' in window)) return;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.removedNodes.forEach((node) => {
          if (node === element || (node.contains && node.contains(element))) {
            this.cache.delete(id);
            this.stopObserving(id);
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    this.observers.set(id, observer);
  }

  /**
   * Stop observing element
   * @param {string} id 
   */
  stopObserving(id) {
    const observer = this.observers.get(id);
    if (observer) {
      observer.disconnect();
      this.observers.delete(id);
    }
  }

  /**
   * Clear all cached elements and observers
   */
  clear() {
    this.cache.clear();
    this.observers.forEach(observer => observer.disconnect());
    this.observers.clear();
    this.initialized = false;
  }

  /**
   * Get cache statistics
   * @returns {Object}
   */
  getStats() {
    return {
      cacheSize: this.cache.size,
      observerCount: this.observers.size,
      cachedElements: Array.from(this.cache.keys())
    };
  }

  /**
   * Preload elements that might be needed later
   * @param {string[]} ids 
   */
  preload(ids) {
    ids.forEach(id => this.get(id));
  }

  /**
   * Check if element exists in cache
   * @param {string} id 
   * @returns {boolean}
   */
  has(id) {
    return this.cache.has(id);
  }

  /**
   * Remove element from cache
   * @param {string} id 
   */
  remove(id) {
    this.cache.delete(id);
    this.stopObserving(id);
  }
}