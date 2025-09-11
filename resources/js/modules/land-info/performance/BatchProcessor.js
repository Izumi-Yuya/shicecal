/**
 * Batch Processing for Performance Optimization
 * Batches DOM updates and calculations for better performance
 */
export class BatchProcessor {
  constructor() {
    this.pendingUpdates = new Map();
    this.pendingCalculations = new Set();
    this.updateScheduled = false;
    this.calculationScheduled = false;

    // Performance monitoring
    this.metrics = {
      batchesProcessed: 0,
      totalUpdateTime: 0,
      averageUpdateTime: 0,
      peakUpdateTime: 0
    };
  }

  /**
   * Schedule DOM update to be batched
   * @param {string} elementId 
   * @param {Function} updateFn 
   * @param {number} priority 
   */
  scheduleUpdate(elementId, updateFn, priority = 0) {
    // Store update with priority
    if (!this.pendingUpdates.has(priority)) {
      this.pendingUpdates.set(priority, new Map());
    }

    this.pendingUpdates.get(priority).set(elementId, updateFn);

    // Schedule batch processing
    if (!this.updateScheduled) {
      this.updateScheduled = true;
      requestAnimationFrame(() => this.processBatchedUpdates());
    }
  }

  /**
   * Schedule calculation to be batched
   * @param {Function} calculationFn 
   */
  scheduleCalculation(calculationFn) {
    this.pendingCalculations.add(calculationFn);

    if (!this.calculationScheduled) {
      this.calculationScheduled = true;
      // Use setTimeout for calculations to not block rendering
      setTimeout(() => this.processBatchedCalculations(), 0);
    }
  }

  /**
   * Process all batched DOM updates
   */
  processBatchedUpdates() {
    const startTime = performance.now();

    try {
      // Process updates by priority (higher priority first)
      const priorities = Array.from(this.pendingUpdates.keys()).sort((a, b) => b - a);

      priorities.forEach(priority => {
        const updates = this.pendingUpdates.get(priority);

        // Batch DOM reads first, then writes
        const reads = [];
        const writes = [];

        updates.forEach((updateFn, elementId) => {
          const element = document.getElementById(elementId);
          if (element) {
            try {
              const result = updateFn(element);

              // Separate reads and writes for better performance
              if (result && result.type === 'read') {
                reads.push(result);
              } else if (result && result.type === 'write') {
                writes.push(result);
              } else {
                // Direct update function
                updateFn(element);
              }
            } catch (error) {
              console.error(`Error in batched update for ${elementId}:`, error);
            }
          }
        });

        // Execute all reads first
        reads.forEach(read => read.execute());

        // Then execute all writes
        writes.forEach(write => write.execute());

        updates.clear();
      });

      this.pendingUpdates.clear();

    } finally {
      this.updateScheduled = false;

      // Update metrics
      const updateTime = performance.now() - startTime;
      this.updateMetrics(updateTime);
    }
  }

  /**
   * Process all batched calculations
   */
  processBatchedCalculations() {
    const startTime = performance.now();

    try {
      // Execute calculations in parallel where possible
      const calculationPromises = Array.from(this.pendingCalculations).map(calcFn => {
        return new Promise(resolve => {
          try {
            const result = calcFn();
            resolve(result);
          } catch (error) {
            console.error('Error in batched calculation:', error);
            resolve(null);
          }
        });
      });

      // Wait for all calculations to complete
      Promise.all(calculationPromises).then(results => {
        // Process results if needed
        results.forEach((result, index) => {
          if (result && result.callback) {
            result.callback(result.data);
          }
        });
      });

      this.pendingCalculations.clear();

    } finally {
      this.calculationScheduled = false;
    }
  }

  /**
   * Create optimized DOM update function
   * @param {string} type - 'read' or 'write'
   * @param {Function} fn 
   * @returns {Object}
   */
  createDOMOperation(type, fn) {
    return {
      type,
      execute: fn
    };
  }

  /**
   * Batch multiple element updates
   * @param {Array} updates - Array of {elementId, updateFn, priority}
   */
  batchMultipleUpdates(updates) {
    updates.forEach(({ elementId, updateFn, priority = 0 }) => {
      this.scheduleUpdate(elementId, updateFn, priority);
    });
  }

  /**
   * Debounced update scheduling
   * @param {string} key 
   * @param {Function} updateFn 
   * @param {number} delay 
   */
  debounceUpdate(key, updateFn, delay = 100) {
    // Clear existing timeout
    if (this.debounceTimeouts && this.debounceTimeouts.has(key)) {
      clearTimeout(this.debounceTimeouts.get(key));
    }

    if (!this.debounceTimeouts) {
      this.debounceTimeouts = new Map();
    }

    // Set new timeout
    const timeoutId = setTimeout(() => {
      updateFn();
      this.debounceTimeouts.delete(key);
    }, delay);

    this.debounceTimeouts.set(key, timeoutId);
  }

  /**
   * Throttled update scheduling
   * @param {string} key 
   * @param {Function} updateFn 
   * @param {number} interval 
   */
  throttleUpdate(key, updateFn, interval = 100) {
    if (!this.throttleTimestamps) {
      this.throttleTimestamps = new Map();
    }

    const now = Date.now();
    const lastExecution = this.throttleTimestamps.get(key) || 0;

    if (now - lastExecution >= interval) {
      updateFn();
      this.throttleTimestamps.set(key, now);
    }
  }

  /**
   * Update performance metrics
   * @param {number} updateTime 
   */
  updateMetrics(updateTime) {
    this.metrics.batchesProcessed++;
    this.metrics.totalUpdateTime += updateTime;
    this.metrics.averageUpdateTime = this.metrics.totalUpdateTime / this.metrics.batchesProcessed;

    if (updateTime > this.metrics.peakUpdateTime) {
      this.metrics.peakUpdateTime = updateTime;
    }
  }

  /**
   * Get performance metrics
   * @returns {Object}
   */
  getMetrics() {
    return {
      ...this.metrics,
      pendingUpdates: this.pendingUpdates.size,
      pendingCalculations: this.pendingCalculations.size,
      isUpdateScheduled: this.updateScheduled,
      isCalculationScheduled: this.calculationScheduled
    };
  }

  /**
   * Clear all pending operations
   */
  clear() {
    this.pendingUpdates.clear();
    this.pendingCalculations.clear();

    if (this.debounceTimeouts) {
      this.debounceTimeouts.forEach(timeoutId => clearTimeout(timeoutId));
      this.debounceTimeouts.clear();
    }

    this.updateScheduled = false;
    this.calculationScheduled = false;
  }

  /**
   * Cleanup resources
   */
  destroy() {
    this.clear();
    this.metrics = {
      batchesProcessed: 0,
      totalUpdateTime: 0,
      averageUpdateTime: 0,
      peakUpdateTime: 0
    };
  }
}