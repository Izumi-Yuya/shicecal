/**
 * Simple EventEmitter implementation for component communication
 */
export class EventEmitter {
  constructor() {
    this.events = new Map();
  }

  /**
   * Subscribe to an event
   * @param {string} eventName 
   * @param {Function} callback 
   * @returns {Function} Unsubscribe function
   */
  on(eventName, callback) {
    if (!this.events.has(eventName)) {
      this.events.set(eventName, new Set());
    }

    this.events.get(eventName).add(callback);

    // Return unsubscribe function
    return () => this.off(eventName, callback);
  }

  /**
   * Subscribe to an event once
   * @param {string} eventName 
   * @param {Function} callback 
   * @returns {Function} Unsubscribe function
   */
  once(eventName, callback) {
    const unsubscribe = this.on(eventName, (...args) => {
      unsubscribe();
      callback(...args);
    });
    return unsubscribe;
  }

  /**
   * Unsubscribe from an event
   * @param {string} eventName 
   * @param {Function} callback 
   */
  off(eventName, callback) {
    const callbacks = this.events.get(eventName);
    if (callbacks) {
      callbacks.delete(callback);
      if (callbacks.size === 0) {
        this.events.delete(eventName);
      }
    }
  }

  /**
   * Emit an event
   * @param {string} eventName 
   * @param {...any} args 
   */
  emit(eventName, ...args) {
    const callbacks = this.events.get(eventName);
    if (callbacks) {
      // Create array to avoid issues with callbacks that modify the set
      const callbackArray = Array.from(callbacks);
      callbackArray.forEach(callback => {
        try {
          callback(...args);
        } catch (error) {
          console.error(`Error in event callback for ${eventName}:`, error);
        }
      });
    }
  }

  /**
   * Remove all event listeners
   */
  removeAllListeners() {
    this.events.clear();
  }

  /**
   * Get list of event names
   * @returns {string[]}
   */
  eventNames() {
    return Array.from(this.events.keys());
  }

  /**
   * Get listener count for an event
   * @param {string} eventName 
   * @returns {number}
   */
  listenerCount(eventName) {
    const callbacks = this.events.get(eventName);
    return callbacks ? callbacks.size : 0;
  }
}