/**
 * Observer pattern implementation for comment system
 * Manages comment state changes and notifications
 */

export class CommentObserver {
  constructor() {
    this.observers = new Map();
  }

  /**
 * Subscribe to comment events
 */
  subscribe(event, callback) {
    if (!this.observers.has(event)) {
      this.observers.set(event, []);
    }
    this.observers.get(event).push(callback);
  }

  /**
 * Unsubscribe from comment events
 */
  unsubscribe(event, callback) {
    if (!this.observers.has(event)) {
      return;
    }

    const callbacks = this.observers.get(event);
    const index = callbacks.indexOf(callback);
    if (index > -1) {
      callbacks.splice(index, 1);
    }
  }

  /**
 * Notify observers of comment events
 */
  notify(event, data) {
    if (!this.observers.has(event)) {
      return;
    }

    this.observers.get(event).forEach(callback => {
      try {
        callback(data);
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(`Error in comment observer callback for ${event}:`, error);
      }
    });
  }
}

/**
 * Comment events enumeration
 */
export const CommentEvents = {
  COMMENT_ADDED: 'comment_added',
  COMMENT_DELETED: 'comment_deleted',
  COMMENT_COUNT_UPDATED: 'comment_count_updated',
  SECTION_TOGGLED: 'section_toggled',
  LOADING_STATE_CHANGED: 'loading_state_changed'
};

// Global comment observer instance
export const commentObserver = new CommentObserver();
