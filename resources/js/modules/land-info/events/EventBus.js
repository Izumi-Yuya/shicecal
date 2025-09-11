/**
 * Enhanced Event Bus with middleware support
 * Provides better event flow control and debugging capabilities
 */
export class EventBus {
  constructor() {
    this.listeners = new Map();
    this.middleware = [];
    this.eventHistory = [];
    this.maxHistorySize = 100;
  }

  /**
   * Add middleware to process events
   * @param {Function} middleware 
   */
  use(middleware) {
    this.middleware.push(middleware);
  }

  /**
   * Subscribe to events with optional priority
   * @param {string} eventName 
   * @param {Function} callback 
   * @param {number} priority 
   * @returns {Function} Unsubscribe function
   */
  on(eventName, callback, priority = 0) {
    if (!this.listeners.has(eventName)) {
      this.listeners.set(eventName, []);
    }

    const listener = { callback, priority, id: Date.now() + Math.random() };
    this.listeners.get(eventName).push(listener);

    // Sort by priority (higher first)
    this.listeners.get(eventName).sort((a, b) => b.priority - a.priority);

    return () => this.off(eventName, listener.id);
  }

  /**
   * Subscribe to event once
   * @param {string} eventName 
   * @param {Function} callback 
   * @param {number} priority 
   */
  once(eventName, callback, priority = 0) {
    const unsubscribe = this.on(eventName, (...args) => {
      unsubscribe();
      callback(...args);
    }, priority);
    return unsubscribe;
  }

  /**
   * Unsubscribe from event
   * @param {string} eventName 
   * @param {string} listenerId 
   */
  off(eventName, listenerId) {
    const listeners = this.listeners.get(eventName);
    if (listeners) {
      const index = listeners.findIndex(l => l.id === listenerId);
      if (index !== -1) {
        listeners.splice(index, 1);
      }
    }
  }

  /**
   * Emit event with middleware processing
   * @param {string} eventName 
   * @param {*} data 
   * @returns {Promise<boolean>} Whether event was processed successfully
   */
  async emit(eventName, data = {}) {
    const event = {
      name: eventName,
      data,
      timestamp: Date.now(),
      cancelled: false,
      preventDefault: () => { event.cancelled = true; }
    };

    // Process through middleware
    for (const middleware of this.middleware) {
      try {
        await middleware(event);
        if (event.cancelled) break;
      } catch (error) {
        console.error('Event middleware error:', error);
        return false;
      }
    }

    // Add to history
    this.addToHistory(event);

    if (event.cancelled) return false;

    // Notify listeners
    const listeners = this.listeners.get(eventName) || [];
    const promises = listeners.map(listener => {
      try {
        return Promise.resolve(listener.callback(event.data, event));
      } catch (error) {
        console.error(`Error in event listener for ${eventName}:`, error);
        return Promise.resolve(false);
      }
    });

    const results = await Promise.all(promises);
    return results.every(result => result !== false);
  }

  /**
   * Add event to history for debugging
   * @param {Object} event 
   */
  addToHistory(event) {
    this.eventHistory.push({
      name: event.name,
      data: event.data,
      timestamp: event.timestamp,
      cancelled: event.cancelled
    });

    if (this.eventHistory.length > this.maxHistorySize) {
      this.eventHistory.shift();
    }
  }

  /**
   * Get event history for debugging
   * @param {number} limit 
   * @returns {Array}
   */
  getHistory(limit = 10) {
    return this.eventHistory.slice(-limit);
  }

  /**
   * Clear all listeners and history
   */
  clear() {
    this.listeners.clear();
    this.eventHistory = [];
  }

  /**
   * Get debug information
   * @returns {Object}
   */
  getDebugInfo() {
    return {
      listenerCount: Array.from(this.listeners.values()).reduce((sum, arr) => sum + arr.length, 0),
      eventTypes: Array.from(this.listeners.keys()),
      middlewareCount: this.middleware.length,
      historySize: this.eventHistory.length
    };
  }
}