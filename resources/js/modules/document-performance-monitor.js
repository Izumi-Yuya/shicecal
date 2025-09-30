/**
 * Document Performance Monitor
 * ドキュメント管理のパフォーマンス監視ユーティリティ
 */

export class DocumentPerformanceMonitor {
  constructor(options = {}) {
    this.options = {
      enableMonitoring: false, // Enable only in development
      showStats: false,
      logToConsole: true,
      metricsInterval: 1000,
      memoryThreshold: 0.8, // 80% memory usage threshold
      renderTimeThreshold: 16, // 16ms for 60fps
      ...options
    };

    this.metrics = {
      renderTimes: [],
      memoryUsage: [],
      cacheHitRate: 0,
      totalRequests: 0,
      cachedRequests: 0,
      virtualScrollPerformance: {
        scrollEvents: 0,
        renderCalls: 0,
        averageRenderTime: 0
      }
    };

    this.observers = {
      performance: null,
      memory: null,
      longTask: null
    };

    if (this.options.enableMonitoring) {
      this.init();
    }
  }

  init() {
    this.setupPerformanceObserver();
    this.setupMemoryMonitoring();
    this.setupLongTaskObserver();

    if (this.options.showStats) {
      this.createStatsDisplay();
      this.startStatsUpdate();
    }

    this.startMetricsCollection();
  }

  /**
   * Setup Performance Observer for measuring render times
   */
  setupPerformanceObserver() {
    if (!window.PerformanceObserver) return;

    try {
      this.observers.performance = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        entries.forEach(entry => {
          if (entry.name.includes('document-render')) {
            this.recordRenderTime(entry.duration);
          }
        });
      });

      this.observers.performance.observe({ entryTypes: ['measure'] });
    } catch (error) {
      console.warn('Performance Observer not supported:', error);
    }
  }

  /**
   * Setup memory monitoring
   */
  setupMemoryMonitoring() {
    if (!('memory' in performance)) return;

    setInterval(() => {
      const memInfo = performance.memory;
      const usage = {
        used: memInfo.usedJSHeapSize,
        total: memInfo.totalJSHeapSize,
        limit: memInfo.jsHeapSizeLimit,
        ratio: memInfo.usedJSHeapSize / memInfo.jsHeapSizeLimit,
        timestamp: Date.now()
      };

      this.metrics.memoryUsage.push(usage);

      // Keep only last 100 measurements
      if (this.metrics.memoryUsage.length > 100) {
        this.metrics.memoryUsage.shift();
      }

      // Warn if memory usage is high
      if (usage.ratio > this.options.memoryThreshold) {
        this.logWarning('High memory usage detected', usage);
      }
    }, this.options.metricsInterval);
  }

  /**
   * Setup Long Task Observer for detecting performance issues
   */
  setupLongTaskObserver() {
    if (!window.PerformanceObserver) return;

    try {
      this.observers.longTask = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        entries.forEach(entry => {
          if (entry.duration > 50) { // Tasks longer than 50ms
            this.logWarning('Long task detected', {
              duration: entry.duration,
              startTime: entry.startTime,
              name: entry.name
            });
          }
        });
      });

      this.observers.longTask.observe({ entryTypes: ['longtask'] });
    } catch (error) {
      console.warn('Long Task Observer not supported:', error);
    }
  }

  /**
   * Record render time
   */
  recordRenderTime(duration) {
    this.metrics.renderTimes.push({
      duration,
      timestamp: Date.now()
    });

    // Keep only last 50 measurements
    if (this.metrics.renderTimes.length > 50) {
      this.metrics.renderTimes.shift();
    }

    if (duration > this.options.renderTimeThreshold) {
      this.logWarning('Slow render detected', { duration });
    }
  }

  /**
   * Record cache hit/miss
   */
  recordCacheHit(hit = true) {
    this.metrics.totalRequests++;
    if (hit) {
      this.metrics.cachedRequests++;
    }
    this.metrics.cacheHitRate = this.metrics.cachedRequests / this.metrics.totalRequests;
  }

  /**
   * Record virtual scroll performance
   */
  recordVirtualScrollEvent(type, duration = 0) {
    const vs = this.metrics.virtualScrollPerformance;

    switch (type) {
      case 'scroll':
        vs.scrollEvents++;
        break;
      case 'render':
        vs.renderCalls++;
        if (duration > 0) {
          vs.averageRenderTime = (vs.averageRenderTime + duration) / 2;
        }
        break;
    }
  }

  /**
   * Measure function execution time
   */
  measureFunction(name, fn) {
    return async (...args) => {
      const startTime = performance.now();
      performance.mark(`${name}-start`);

      try {
        const result = await fn.apply(this, args);

        const endTime = performance.now();
        performance.mark(`${name}-end`);
        performance.measure(`document-render-${name}`, `${name}-start`, `${name}-end`);

        this.recordRenderTime(endTime - startTime);

        return result;
      } catch (error) {
        performance.mark(`${name}-error`);
        throw error;
      }
    };
  }

  /**
   * Create stats display
   */
  createStatsDisplay() {
    this.statsElement = document.createElement('div');
    this.statsElement.className = 'performance-stats';
    this.statsElement.innerHTML = `
            <div class="stat" id="render-time">Render: 0ms</div>
            <div class="stat" id="memory-usage">Memory: 0MB</div>
            <div class="stat" id="cache-hit-rate">Cache: 0%</div>
            <div class="stat" id="virtual-scroll">VScroll: 0/0</div>
        `;
    document.body.appendChild(this.statsElement);
  }

  /**
   * Start stats update
   */
  startStatsUpdate() {
    if (!this.statsElement) return;

    setInterval(() => {
      this.updateStatsDisplay();
    }, this.options.metricsInterval);
  }

  /**
   * Update stats display
   */
  updateStatsDisplay() {
    if (!this.statsElement) return;

    // Average render time
    const avgRenderTime = this.getAverageRenderTime();
    const renderElement = this.statsElement.querySelector('#render-time');
    if (renderElement) {
      renderElement.textContent = `Render: ${avgRenderTime.toFixed(1)}ms`;
      renderElement.className = `stat ${avgRenderTime > this.options.renderTimeThreshold ? 'stat-danger' : 'stat-good'}`;
    }

    // Memory usage
    const memoryUsage = this.getCurrentMemoryUsage();
    const memoryElement = this.statsElement.querySelector('#memory-usage');
    if (memoryElement && memoryUsage) {
      const memoryMB = (memoryUsage.used / 1024 / 1024).toFixed(1);
      memoryElement.textContent = `Memory: ${memoryMB}MB`;
      memoryElement.className = `stat ${memoryUsage.ratio > this.options.memoryThreshold ? 'stat-danger' : 'stat-good'}`;
    }

    // Cache hit rate
    const cacheElement = this.statsElement.querySelector('#cache-hit-rate');
    if (cacheElement) {
      const hitRate = (this.metrics.cacheHitRate * 100).toFixed(1);
      cacheElement.textContent = `Cache: ${hitRate}%`;
      cacheElement.className = `stat ${this.metrics.cacheHitRate > 0.7 ? 'stat-good' : 'stat-warning'}`;
    }

    // Virtual scroll performance
    const vsElement = this.statsElement.querySelector('#virtual-scroll');
    if (vsElement) {
      const vs = this.metrics.virtualScrollPerformance;
      vsElement.textContent = `VScroll: ${vs.scrollEvents}/${vs.renderCalls}`;
      vsElement.className = 'stat stat-good';
    }
  }

  /**
   * Get average render time
   */
  getAverageRenderTime() {
    if (this.metrics.renderTimes.length === 0) return 0;

    const sum = this.metrics.renderTimes.reduce((acc, item) => acc + item.duration, 0);
    return sum / this.metrics.renderTimes.length;
  }

  /**
   * Get current memory usage
   */
  getCurrentMemoryUsage() {
    if (this.metrics.memoryUsage.length === 0) return null;
    return this.metrics.memoryUsage[this.metrics.memoryUsage.length - 1];
  }

  /**
   * Start metrics collection
   */
  startMetricsCollection() {
    // Collect metrics periodically
    setInterval(() => {
      this.collectMetrics();
    }, this.options.metricsInterval * 10); // Every 10 seconds
  }

  /**
   * Collect and log metrics
   */
  collectMetrics() {
    const metrics = {
      averageRenderTime: this.getAverageRenderTime(),
      memoryUsage: this.getCurrentMemoryUsage(),
      cacheHitRate: this.metrics.cacheHitRate,
      virtualScrollPerformance: this.metrics.virtualScrollPerformance,
      timestamp: Date.now()
    };

    if (this.options.logToConsole) {
      console.log('Document Performance Metrics:', metrics);
    }

    // Send metrics to analytics service if configured
    if (this.options.analyticsEndpoint) {
      this.sendMetrics(metrics);
    }
  }

  /**
   * Send metrics to analytics service
   */
  async sendMetrics(metrics) {
    try {
      await fetch(this.options.analyticsEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          type: 'document_performance',
          metrics: metrics,
          userAgent: navigator.userAgent,
          url: window.location.href
        })
      });
    } catch (error) {
      console.warn('Failed to send performance metrics:', error);
    }
  }

  /**
   * Log warning
   */
  logWarning(message, data = {}) {
    if (this.options.logToConsole) {
      console.warn(`[Document Performance] ${message}`, data);
    }
  }

  /**
   * Show/hide stats display
   */
  toggleStatsDisplay() {
    if (this.statsElement) {
      this.statsElement.classList.toggle('visible');
    }
  }

  /**
   * Get performance report
   */
  getPerformanceReport() {
    return {
      renderTimes: this.metrics.renderTimes,
      averageRenderTime: this.getAverageRenderTime(),
      memoryUsage: this.getCurrentMemoryUsage(),
      cacheHitRate: this.metrics.cacheHitRate,
      virtualScrollPerformance: this.metrics.virtualScrollPerformance,
      totalRequests: this.metrics.totalRequests,
      cachedRequests: this.metrics.cachedRequests
    };
  }

  /**
   * Reset metrics
   */
  resetMetrics() {
    this.metrics = {
      renderTimes: [],
      memoryUsage: [],
      cacheHitRate: 0,
      totalRequests: 0,
      cachedRequests: 0,
      virtualScrollPerformance: {
        scrollEvents: 0,
        renderCalls: 0,
        averageRenderTime: 0
      }
    };
  }

  /**
   * Cleanup
   */
  destroy() {
    // Disconnect observers
    Object.values(this.observers).forEach(observer => {
      if (observer) {
        observer.disconnect();
      }
    });

    // Remove stats display
    if (this.statsElement) {
      this.statsElement.remove();
    }

    // Clear intervals
    // Note: In a real implementation, you'd want to track interval IDs
  }
}

// Export for use in other modules
export default DocumentPerformanceMonitor;