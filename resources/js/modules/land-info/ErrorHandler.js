/**
 * Centralized Error Handling for Land Info System
 */
export class ErrorHandler {
  constructor() {
    this.errors = new Map();
    this.listeners = new Set();
  }

  /**
   * Handle and log errors consistently
   * @param {Error} error 
   * @param {string} context 
   * @param {Object} metadata 
   */
  handleError(error, context = 'Unknown', metadata = {}) {
    const errorInfo = {
      message: error.message,
      stack: error.stack,
      context,
      metadata,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent
    };

    // Log to console in development
    if (process.env.NODE_ENV === 'development') {
      console.group(`🚨 Error in ${context}`);
      console.error('Error:', error);
      console.table(metadata);
      console.groupEnd();
    }

    // Store error for debugging
    this.errors.set(`${context}_${Date.now()}`, errorInfo);

    // Notify listeners
    this.notifyListeners(errorInfo);

    // Send to monitoring service in production
    if (process.env.NODE_ENV === 'production') {
      this.reportToMonitoring(errorInfo);
    }

    return errorInfo;
  }

  /**
   * Handle validation errors specifically
   * @param {Object} validationResult 
   * @param {string} context 
   */
  handleValidationErrors(validationResult, context = 'Validation') {
    if (!validationResult.isValid) {
      const error = new Error(`Validation failed: ${validationResult.errors.join(', ')}`);
      return this.handleError(error, context, {
        errors: validationResult.errors,
        type: 'validation'
      });
    }
    return null;
  }

  /**
   * Handle network/API errors
   * @param {Response} response 
   * @param {string} context 
   */
  async handleNetworkError(response, context = 'Network') {
    let errorData = {};

    try {
      errorData = await response.json();
    } catch (e) {
      errorData = { message: 'Network error occurred' };
    }

    const error = new Error(`${response.status}: ${errorData.message || 'Unknown error'}`);
    return this.handleError(error, context, {
      status: response.status,
      statusText: response.statusText,
      url: response.url,
      type: 'network'
    });
  }

  /**
   * Add error listener
   * @param {Function} listener 
   */
  addListener(listener) {
    this.listeners.add(listener);
  }

  /**
   * Remove error listener
   * @param {Function} listener 
   */
  removeListener(listener) {
    this.listeners.delete(listener);
  }

  /**
   * Notify all listeners of error
   * @param {Object} errorInfo 
   */
  notifyListeners(errorInfo) {
    this.listeners.forEach(listener => {
      try {
        listener(errorInfo);
      } catch (e) {
        console.error('Error in error listener:', e);
      }
    });
  }

  /**
   * Report error to monitoring service
   * @param {Object} errorInfo 
   */
  reportToMonitoring(errorInfo) {
    // Implementation would depend on monitoring service
    // Example: Sentry, LogRocket, etc.
    if (window.Sentry) {
      window.Sentry.captureException(new Error(errorInfo.message), {
        contexts: {
          landInfo: errorInfo.metadata
        },
        tags: {
          context: errorInfo.context
        }
      });
    }
  }

  /**
   * Get recent errors for debugging
   * @param {number} limit 
   * @returns {Array}
   */
  getRecentErrors(limit = 10) {
    return Array.from(this.errors.entries())
      .sort((a, b) => new Date(b[1].timestamp) - new Date(a[1].timestamp))
      .slice(0, limit)
      .map(([key, error]) => ({ key, ...error }));
  }

  /**
   * Clear stored errors
   */
  clearErrors() {
    this.errors.clear();
  }

  /**
   * Create user-friendly error message
   * @param {Error} error 
   * @param {string} context 
   * @returns {string}
   */
  createUserMessage(error, context) {
    const userMessages = {
      'Calculator': '計算処理でエラーが発生しました。入力値を確認してください。',
      'FormValidator': '入力内容に問題があります。エラーメッセージを確認してください。',
      'SectionManager': 'セクションの表示処理でエラーが発生しました。',
      'EventManager': 'イベント処理でエラーが発生しました。',
      'Network': 'ネットワークエラーが発生しました。接続を確認してください。',
      'Validation': '入力値の検証でエラーが発生しました。'
    };

    return userMessages[context] || 'システムエラーが発生しました。管理者にお問い合わせください。';
  }
}