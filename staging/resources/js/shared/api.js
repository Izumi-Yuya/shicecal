/**
 * API communication helpers for the Shise-Cal application
 * Centralized HTTP request handling with error management
 */

import { showToast } from './utils.js';

/**
 * Default configuration for API requests
 */
const DEFAULT_CONFIG = {
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  credentials: 'same-origin'
};

/**
 * Get CSRF token from meta tag
 * @returns {string} CSRF token
 */
function getCsrfToken() {
  const token = document.querySelector('meta[name="csrf-token"]');
  return token ? token.getAttribute('content') : '';
}

/**
 * Add CSRF token to headers
 * @param {Object} headers - Existing headers
 * @returns {Object} Headers with CSRF token
 */
function addCsrfToken(headers = {}) {
  const token = getCsrfToken();
  if (token) {
    headers['X-CSRF-TOKEN'] = token;
  }
  return headers;
}

/**
 * Handle API response
 * @param {Response} response - Fetch response object
 * @returns {Promise} Promise that resolves to response data
 */
async function handleResponse(response) {
  const contentType = response.headers.get('content-type');

  let data;
  if (contentType && contentType.includes('application/json')) {
    data = await response.json();
  } else {
    data = await response.text();
  }

  if (!response.ok) {
    const error = new Error(data.message || `HTTP error! status: ${response.status}`);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}

/**
 * Handle API errors
 * @param {Error} error - Error object
 * @param {boolean} showNotification - Whether to show error notification
 */
function handleError(error, showNotification = true) {
  console.error('API Error:', error);

  if (showNotification) {
    let message = 'エラーが発生しました。';

    if (error.status === 401) {
      message = 'ログインが必要です。';
    } else if (error.status === 403) {
      message = 'この操作を実行する権限がありません。';
    } else if (error.status === 404) {
      message = 'リソースが見つかりません。';
    } else if (error.status === 422) {
      message = '入力データに問題があります。';
    } else if (error.status >= 500) {
      message = 'サーバーエラーが発生しました。';
    } else if (error.data && error.data.message) {
      message = error.data.message;
    }

    showToast(message, 'error');
  }

  throw error;
}

/**
 * Make a GET request
 * @param {string} url - Request URL
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function get(url, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'GET',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      }
    };

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Make a POST request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function post(url, data = {}, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'POST',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      },
      body: JSON.stringify(data)
    };

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Make a PUT request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function put(url, data = {}, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'PUT',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      },
      body: JSON.stringify(data)
    };

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Make a PATCH request
 * @param {string} url - Request URL
 * @param {Object} data - Request data
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function patch(url, data = {}, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'PATCH',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      },
      body: JSON.stringify(data)
    };

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Make a DELETE request
 * @param {string} url - Request URL
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function del(url, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'DELETE',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      }
    };

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Upload file with progress tracking
 * @param {string} url - Upload URL
 * @param {FormData} formData - Form data with file
 * @param {Object} options - Request options
 * @returns {Promise} Promise that resolves to response data
 */
export async function uploadFile(url, formData, options = {}) {
  try {
    const config = {
      method: 'POST',
      headers: {
        ...addCsrfToken(),
        ...options.headers
      },
      body: formData,
      credentials: 'same-origin'
    };

    // Remove Content-Type header to let browser set it with boundary
    delete config.headers['Content-Type'];

    const response = await fetch(url, config);
    return await handleResponse(response);
  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Download file
 * @param {string} url - Download URL
 * @param {string} filename - Optional filename
 * @param {Object} options - Request options
 */
export async function downloadFile(url, filename = null, options = {}) {
  try {
    const config = {
      ...DEFAULT_CONFIG,
      ...options,
      method: 'GET',
      headers: {
        ...DEFAULT_CONFIG.headers,
        ...addCsrfToken(),
        ...options.headers
      }
    };

    const response = await fetch(url, config);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const blob = await response.blob();

    // Create download link
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;

    // Set filename from response header or parameter
    if (!filename) {
      const contentDisposition = response.headers.get('content-disposition');
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (filenameMatch && filenameMatch[1]) {
          filename = filenameMatch[1].replace(/['"]/g, '');
        }
      }
    }

    if (filename) {
      link.download = filename;
    }

    // Trigger download
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Clean up
    window.URL.revokeObjectURL(downloadUrl);

  } catch (error) {
    return handleError(error, options.showNotification !== false);
  }
}

/**
 * Make a request with automatic retry
 * @param {Function} requestFn - Request function to retry
 * @param {number} maxRetries - Maximum number of retries
 * @param {number} delay - Delay between retries in milliseconds
 * @returns {Promise} Promise that resolves to response data
 */
export async function withRetry(requestFn, maxRetries = 3, delay = 1000) {
  let lastError;

  for (let i = 0; i <= maxRetries; i++) {
    try {
      return await requestFn();
    } catch (error) {
      lastError = error;

      // Don't retry on client errors (4xx)
      if (error.status >= 400 && error.status < 500) {
        throw error;
      }

      // Don't retry on last attempt
      if (i === maxRetries) {
        throw error;
      }

      // Wait before retry
      await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
    }
  }

  throw lastError;
}

/**
 * Create a request with timeout
 * @param {Function} requestFn - Request function
 * @param {number} timeout - Timeout in milliseconds
 * @returns {Promise} Promise that resolves to response data or rejects on timeout
 */
export function withTimeout(requestFn, timeout = 30000) {
  return Promise.race([
    requestFn(),
    new Promise((_, reject) => {
      setTimeout(() => {
        reject(new Error('Request timeout'));
      }, timeout);
    })
  ]);
}

/**
 * Batch multiple requests
 * @param {Array} requests - Array of request functions
 * @param {number} concurrency - Maximum concurrent requests
 * @returns {Promise} Promise that resolves to array of results
 */
export async function batchRequests(requests, concurrency = 5) {
  const results = [];

  for (let i = 0; i < requests.length; i += concurrency) {
    const batch = requests.slice(i, i + concurrency);
    const batchResults = await Promise.allSettled(batch.map(fn => fn()));
    results.push(...batchResults);
  }

  return results;
}