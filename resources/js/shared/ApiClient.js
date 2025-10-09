/**
 * API Client
 * Handles HTTP requests with consistent error handling
 */

import { AppUtils } from './AppUtils.js';

class ApiClient {
  constructor() {
    this.defaultConfig = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    };
  }

  addCsrfToken(headers = {}) {
    const token = AppUtils.getCsrfToken();
    if (!token) {
      console.warn('CSRF token not found. This may cause authentication issues.');
      throw new Error('CSRF token is required for this request');
    }
    headers['X-CSRF-TOKEN'] = token;
    return headers;
  }

  async handleResponse(response) {
    const contentType = response.headers.get('content-type');
    let data;

    try {
      if (contentType && contentType.includes('application/json')) {
        data = await response.json();
      } else {
        data = await response.text();
      }
    } catch (parseError) {
      console.error('Failed to parse response:', parseError);
      data = { message: 'Invalid response format' };
    }

    if (!response.ok) {
      let errorMessage = 'Unknown error occurred';

      if (typeof data === 'object' && data.message) {
        errorMessage = data.message;
      } else if (typeof data === 'string') {
        errorMessage = data;
      } else {
        errorMessage = `HTTP error! status: ${response.status}`;
      }

      const error = new Error(errorMessage);
      error.status = response.status;
      error.data = data;

      console.error('API Error:', {
        status: response.status,
        url: response.url,
        data: data,
        error: errorMessage
      });

      throw error;
    }

    return data;
  }

  handleError(error, showNotification = true) {
    console.error('API Error:', error);

    if (showNotification) {
      let message = 'エラーが発生しました。';

      switch (error.status) {
        case 401:
          message = 'ログインが必要です。';
          break;
        case 403:
          message = 'この操作を実行する権限がありません。';
          break;
        case 404:
          message = 'リソースが見つかりません。';
          break;
        case 422:
          message = '入力データに問題があります。';
          break;
        case 429:
          message = 'リクエストが多すぎます。しばらく待ってから再試行してください。';
          break;
        default:
          if (error.status >= 500) {
            message = 'サーバーエラーが発生しました。';
          } else if (error.data && error.data.message) {
            message = error.data.message;
          }
      }

      AppUtils.showToast(message, 'error');
    }

    throw error;
  }

  async request(url, options = {}) {
    // Input validation
    if (!url || typeof url !== 'string') {
      throw new Error('URL is required and must be a string');
    }

    try {
      const config = {
        ...this.defaultConfig,
        ...options,
        headers: {
          ...this.defaultConfig.headers,
          ...this.addCsrfToken(),
          ...options.headers
        }
      };

      const response = await fetch(url, config);
      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error, options.showNotification !== false);
    }
  }

  async get(url, options = {}) {
    return this.request(url, { ...options, method: 'GET' });
  }

  async post(url, data = {}, options = {}) {
    return this.request(url, {
      ...options,
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async put(url, data = {}, options = {}) {
    return this.request(url, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async delete(url, options = {}) {
    return this.request(url, { ...options, method: 'DELETE' });
  }

  async uploadFile(url, formData, options = {}) {
    const config = {
      method: 'POST',
      headers: this.addCsrfToken(),
      body: formData,
      credentials: 'same-origin'
    };

    try {
      const response = await fetch(url, config);
      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error, options.showNotification !== false);
    }
  }
}

// Default export
export default ApiClient;
export { ApiClient };