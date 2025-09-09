/**
 * CSRF token management utilities
 */

/**
 * Get CSRF token from meta tag
 * @returns {string|null} CSRF token or null if not found
 */
export function getCsrfToken() {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute('content') : null;
}

/**
 * Add CSRF token to request headers
 * @param {Object} headers - Existing headers object
 * @returns {Object} Headers with CSRF token added
 */
export function addCsrfToken(headers = {}) {
  const token = getCsrfToken();
  if (token) {
    headers['X-CSRF-TOKEN'] = token;
  }
  return headers;
}

/**
 * Create fetch options with CSRF protection
 * @param {Object} options - Fetch options
 * @returns {Object} Options with CSRF token added
 */
export function withCsrfProtection(options = {}) {
  const headers = addCsrfToken(options.headers || {});
  return {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...headers
    }
  };
}