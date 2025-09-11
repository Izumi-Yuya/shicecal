/**
 * Observer Pattern for DOM Changes
 * Efficiently manages DOM element references and change notifications
 */
export class DOMObserver {
  constructor() {
    this.observers = new Map();
    this.elementCache = new Map();
    this.mutationObserver = null;
    this.batchedUpdates = new Set();
    this.updateScheduled = false;

    this.initializeMutationObserver();
  }

  /**
   * Initialize mutation observer for DOM changes
   */
  initializeMutationObserver() {
    if (!('MutationObserver' in window)) return;

    this.mutationObserver = new MutationObserver((mutations) => {
      const changedElements = new Set();

      mutations.forEach(mutation => {
        // Track added/removed nodes
        mutation.addedNodes.forEach(node => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            this.handleElementAdded(node);
          }
        });

        mutation.removedNodes.forEach(node => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            this.handleElementRemoved(node);
            changedElements.add(node);
          }
        });

        // Track attribute changes
        if (mutation.type === 'attributes' && mutation.target) {
          changedElements.add(mutation.target);
        }
      });

      // Batch notify observers
      if (changedElements.size > 0) {
        this.batchNotifyObservers(changedElements);
      }
    });

    this.mutationObserver.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class', 'style', 'disabled', 'aria-hidden']
    });
  }

  /**
   * Subscribe to element changes
   * @param {string} elementId 
   * @param {Function} callback 
   */
  observe(elementId, callback) {
    if (!this.observers.has(elementId)) {
      this.observers.set(elementId, new Set());
    }

    this.observers.get(elementId).add(callback);

    // Cache element for efficient access
    this.cacheElement(elementId);

    // Return unsubscribe function
    return () => this.unobserve(elementId, callback);
  }

  /**
   * Unsubscribe from element changes
   * @param {string} elementId 
   * @param {Function} callback 
   */
  unobserve(elementId, callback) {
    const callbacks = this.observers.get(elementId);
    if (callbacks) {
      callbacks.delete(callback);
      if (callbacks.size === 0) {
        this.observers.delete(elementId);
        this.elementCache.delete(elementId);
      }
    }
  }

  /**
   * Get cached element or query DOM
   * @param {string} elementId 
   * @returns {HTMLElement|null}
   */
  getElement(elementId) {
    if (this.elementCache.has(elementId)) {
      const element = this.elementCache.get(elementId);
      // Verify element is still in DOM
      if (document.contains(element)) {
        return element;
      } else {
        this.elementCache.delete(elementId);
      }
    }

    return this.cacheElement(elementId);
  }

  /**
   * Cache element for efficient access
   * @param {string} elementId 
   */
  cacheElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
      this.elementCache.set(elementId, element);
    }
    return element;
  }

  /**
   * Batch query multiple elements
   * @param {string[]} elementIds 
   * @returns {Map<string, HTMLElement>}
   */
  getElements(elementIds) {
    const elements = new Map();
    const uncachedIds = [];

    // Check cache first
    elementIds.forEach(id => {
      const cached = this.elementCache.get(id);
      if (cached && document.contains(cached)) {
        elements.set(id, cached);
      } else {
        uncachedIds.push(id);
      }
    });

    // Batch query uncached elements
    if (uncachedIds.length > 0) {
      const selector = uncachedIds.map(id => `#${id}`).join(', ');
      const foundElements = document.querySelectorAll(selector);

      foundElements.forEach(element => {
        elements.set(element.id, element);
        this.elementCache.set(element.id, element);
      });
    }

    return elements;
  }

  /**
   * Handle element added to DOM
   * @param {Element} element 
   */
  handleElementAdded(element) {
    if (element.id && this.observers.has(element.id)) {
      this.elementCache.set(element.id, element);
      this.scheduleNotification(element.id, 'added');
    }

    // Check for child elements with observers
    const observedChildren = element.querySelectorAll('[id]');
    observedChildren.forEach(child => {
      if (this.observers.has(child.id)) {
        this.elementCache.set(child.id, child);
        this.scheduleNotification(child.id, 'added');
      }
    });
  }

  /**
   * Handle element removed from DOM
   * @param {Element} element 
   */
  handleElementRemoved(element) {
    if (element.id && this.observers.has(element.id)) {
      this.elementCache.delete(element.id);
      this.scheduleNotification(element.id, 'removed');
    }

    // Check for child elements with observers
    const observedChildren = element.querySelectorAll('[id]');
    observedChildren.forEach(child => {
      if (this.observers.has(child.id)) {
        this.elementCache.delete(child.id);
        this.scheduleNotification(child.id, 'removed');
      }
    });
  }

  /**
   * Schedule batched notification
   * @param {string} elementId 
   * @param {string} changeType 
   */
  scheduleNotification(elementId, changeType) {
    this.batchedUpdates.add({ elementId, changeType });

    if (!this.updateScheduled) {
      this.updateScheduled = true;
      requestAnimationFrame(() => {
        this.processBatchedUpdates();
        this.updateScheduled = false;
      });
    }
  }

  /**
   * Process batched updates
   */
  processBatchedUpdates() {
    this.batchedUpdates.forEach(({ elementId, changeType }) => {
      const callbacks = this.observers.get(elementId);
      if (callbacks) {
        const element = this.getElement(elementId);
        callbacks.forEach(callback => {
          try {
            callback(element, changeType);
          } catch (error) {
            console.error(`Error in DOM observer callback for ${elementId}:`, error);
          }
        });
      }
    });

    this.batchedUpdates.clear();
  }

  /**
   * Batch notify observers of changes
   * @param {Set} changedElements 
   */
  batchNotifyObservers(changedElements) {
    changedElements.forEach(element => {
      if (element.id && this.observers.has(element.id)) {
        this.scheduleNotification(element.id, 'changed');
      }
    });
  }

  /**
   * Get cache statistics
   */
  getCacheStats() {
    return {
      cachedElements: this.elementCache.size,
      observedElements: this.observers.size,
      pendingUpdates: this.batchedUpdates.size
    };
  }

  /**
   * Clear all caches and observers
   */
  destroy() {
    if (this.mutationObserver) {
      this.mutationObserver.disconnect();
    }

    this.observers.clear();
    this.elementCache.clear();
    this.batchedUpdates.clear();
  }
}