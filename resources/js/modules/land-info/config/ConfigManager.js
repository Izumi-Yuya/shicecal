/**
 * Centralized Configuration Management
 * Provides type-safe configuration access with environment-specific overrides
 */
export class ConfigManager {
  constructor() {
    this.config = new Map();
    this.watchers = new Map();
    this.loaded = false;
  }

  /**
   * Load configuration from server and local sources
   * @returns {Promise<void>}
   */
  async load() {
    try {
      // Load server configuration
      const serverConfig = await this.loadServerConfig();

      // Load client configuration
      const clientConfig = this.loadClientConfig();

      // Merge configurations (client overrides server)
      this.config = new Map([
        ...Object.entries(serverConfig),
        ...Object.entries(clientConfig)
      ]);

      this.loaded = true;
      this.notifyWatchers('loaded');
    } catch (error) {
      console.error('Failed to load configuration:', error);
      this.loadFallbackConfig();
    }
  }

  /**
   * Load configuration from server
   * @returns {Promise<Object>}
   */
  async loadServerConfig() {
    try {
      const response = await fetch('/api/land-info/config');
      if (response.ok) {
        return await response.json();
      }
    } catch (error) {
      console.warn('Server config unavailable:', error);
    }
    return {};
  }

  /**
   * Load client-side configuration
   * @returns {Object}
   */
  loadClientConfig() {
    // Check for configuration in window object
    if (window.landInfoConfig) {
      return window.landInfoConfig;
    }

    // Check for configuration in data attributes
    const configElement = document.querySelector('[data-land-info-config]');
    if (configElement) {
      try {
        return JSON.parse(configElement.dataset.landInfoConfig);
      } catch (error) {
        console.warn('Invalid client config JSON:', error);
      }
    }

    return {};
  }

  /**
   * Load fallback configuration
   */
  loadFallbackConfig() {
    const fallbackConfig = {
      validation: {
        debounceDelay: 300,
        maxInputLength: 1000,
        enableRealTimeValidation: true
      },
      calculations: {
        cacheEnabled: true,
        cacheTTL: 3600,
        precision: {
          currency: 0,
          area: 2,
          unitPrice: 0
        }
      },
      ui: {
        animationDuration: 300,
        enableTransitions: true,
        enableTooltips: true
      },
      performance: {
        enableDOMCaching: true,
        enableBatchProcessing: true,
        maxCacheSize: 100
      },
      security: {
        enableXSSProtection: true,
        enableCSRFProtection: true,
        sanitizeInputs: true
      }
    };

    this.config = new Map(Object.entries(fallbackConfig));
    this.loaded = true;
    this.notifyWatchers('fallback');
  }

  /**
   * Get configuration value with type safety
   * @param {string} key 
   * @param {*} defaultValue 
   * @returns {*}
   */
  get(key, defaultValue = null) {
    if (!this.loaded) {
      console.warn('Configuration not loaded yet');
      return defaultValue;
    }

    const keys = key.split('.');
    let value = this.config;

    for (const k of keys) {
      if (value instanceof Map) {
        value = value.get(k);
      } else if (typeof value === 'object' && value !== null) {
        value = value[k];
      } else {
        return defaultValue;
      }

      if (value === undefined) {
        return defaultValue;
      }
    }

    return value;
  }

  /**
   * Set configuration value
   * @param {string} key 
   * @param {*} value 
   */
  set(key, value) {
    const keys = key.split('.');
    const lastKey = keys.pop();
    let current = this.config;

    // Navigate to parent object
    for (const k of keys) {
      if (!current.has(k)) {
        current.set(k, new Map());
      }
      current = current.get(k);
    }

    const oldValue = current.get(lastKey);
    current.set(lastKey, value);

    // Notify watchers of change
    this.notifyWatchers('changed', { key, oldValue, newValue: value });
  }

  /**
   * Watch for configuration changes
   * @param {string} event 
   * @param {Function} callback 
   * @returns {Function} Unwatch function
   */
  watch(event, callback) {
    if (!this.watchers.has(event)) {
      this.watchers.set(event, new Set());
    }

    this.watchers.get(event).add(callback);

    return () => {
      const callbacks = this.watchers.get(event);
      if (callbacks) {
        callbacks.delete(callback);
      }
    };
  }

  /**
   * Notify watchers of events
   * @param {string} event 
   * @param {*} data 
   */
  notifyWatchers(event, data = null) {
    const callbacks = this.watchers.get(event);
    if (callbacks) {
      callbacks.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error('Error in config watcher:', error);
        }
      });
    }
  }

  /**
   * Get typed configuration values
   */
  getValidationConfig() {
    return {
      debounceDelay: this.get('validation.debounceDelay', 300),
      maxInputLength: this.get('validation.maxInputLength', 1000),
      enableRealTimeValidation: this.get('validation.enableRealTimeValidation', true)
    };
  }

  getCalculationConfig() {
    return {
      cacheEnabled: this.get('calculations.cacheEnabled', true),
      cacheTTL: this.get('calculations.cacheTTL', 3600),
      precision: this.get('calculations.precision', {
        currency: 0,
        area: 2,
        unitPrice: 0
      })
    };
  }

  getUIConfig() {
    return {
      animationDuration: this.get('ui.animationDuration', 300),
      enableTransitions: this.get('ui.enableTransitions', true),
      enableTooltips: this.get('ui.enableTooltips', true)
    };
  }

  getPerformanceConfig() {
    return {
      enableDOMCaching: this.get('performance.enableDOMCaching', true),
      enableBatchProcessing: this.get('performance.enableBatchProcessing', true),
      maxCacheSize: this.get('performance.maxCacheSize', 100)
    };
  }

  getSecurityConfig() {
    return {
      enableXSSProtection: this.get('security.enableXSSProtection', true),
      enableCSRFProtection: this.get('security.enableCSRFProtection', true),
      sanitizeInputs: this.get('security.sanitizeInputs', true)
    };
  }

  /**
   * Export configuration for debugging
   * @returns {Object}
   */
  export() {
    const result = {};
    for (const [key, value] of this.config) {
      result[key] = value instanceof Map ? Object.fromEntries(value) : value;
    }
    return result;
  }

  /**
   * Check if configuration is loaded
   * @returns {boolean}
   */
  isLoaded() {
    return this.loaded;
  }
}