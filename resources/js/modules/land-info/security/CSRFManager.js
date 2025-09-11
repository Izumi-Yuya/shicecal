/**
 * CSRF Token Management for AJAX requests
 * Ensures all requests include valid CSRF tokens
 */
export class CSRFManager {
  constructor() {
    this.token = this.getTokenFromMeta();
    this.setupAxiosInterceptor();
    this.setupFetchInterceptor();
  }

  /**
   * Get CSRF token from meta tag
   * @returns {string|null}
   */
  getTokenFromMeta() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
  }

  /**
   * Refresh CSRF token from server
   * @returns {Promise<string>}
   */
  async refreshToken() {
    try {
      const response = await fetch('/csrf-token', {
        method: 'GET',
        credentials: 'same-origin'
      });

      if (response.ok) {
        const data = await response.json();
        this.token = data.token;
        this.updateMetaTag(this.token);
        return this.token;
      }
    } catch (error) {
      console.error('Failed to refresh CSRF token:', error);
    }
    return null;
  }

  /**
   * Update meta tag with new token
   * @param {string} token 
   */
  updateMetaTag(token) {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      metaTag.setAttribute('content', token);
    }
  }

  /**
   * Setup Axios interceptor for automatic CSRF token inclusion
   */
  setupAxiosInterceptor() {
    if (typeof window.axios !== 'undefined') {
      window.axios.interceptors.request.use(
        (config) => {
          if (this.token && this.shouldIncludeToken(config)) {
            config.headers['X-CSRF-TOKEN'] = this.token;
          }
          return config;
        },
        (error) => Promise.reject(error)
      );

      // Handle 419 responses (CSRF token mismatch)
      window.axios.interceptors.response.use(
        (response) => response,
        async (error) => {
          if (error.response?.status === 419) {
            const newToken = await this.refreshToken();
            if (newToken) {
              // Retry the original request with new token
              error.config.headers['X-CSRF-TOKEN'] = newToken;
              return window.axios.request(error.config);
            }
          }
          return Promise.reject(error);
        }
      );
    }
  }

  /**
   * Setup fetch interceptor
   */
  setupFetchInterceptor() {
    const originalFetch = window.fetch;

    window.fetch = async (url, options = {}) => {
      if (this.shouldIncludeToken({ url, method: options.method })) {
        options.headers = {
          ...options.headers,
          'X-CSRF-TOKEN': this.token
        };
      }

      try {
        const response = await originalFetch(url, options);

        // Handle 419 responses
        if (response.status === 419) {
          const newToken = await this.refreshToken();
          if (newToken) {
            options.headers['X-CSRF-TOKEN'] = newToken;
            return originalFetch(url, options);
          }
        }

        return response;
      } catch (error) {
        throw error;
      }
    };
  }

  /**
   * Determine if request should include CSRF token
   * @param {Object} config 
   * @returns {boolean}
   */
  shouldIncludeToken(config) {
    const method = (config.method || 'GET').toUpperCase();
    const url = config.url || '';

    // Include token for state-changing methods
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      return true;
    }

    // Include token for same-origin requests
    if (url.startsWith('/') || url.includes(window.location.origin)) {
      return true;
    }

    return false;
  }

  /**
   * Get current token
   * @returns {string|null}
   */
  getToken() {
    return this.token;
  }

  /**
   * Manually set token
   * @param {string} token 
   */
  setToken(token) {
    this.token = token;
    this.updateMetaTag(token);
  }
}