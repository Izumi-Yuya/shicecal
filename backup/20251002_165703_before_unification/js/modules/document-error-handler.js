/**
 * ドキュメント管理システム用エラーハンドラー
 * 
 * フロントエンド側でのエラーハンドリングとユーザーフレンドリーなエラー表示を提供します。
 */
class DocumentErrorHandler {
  /**
   * エラータイプの定数
   */
  static ERROR_TYPES = {
    NETWORK: 'network',
    VALIDATION: 'validation',
    AUTHORIZATION: 'authorization',
    NOT_FOUND: 'not_found',
    STORAGE: 'storage',
    FILE_OPERATION: 'file_operation',
    FOLDER_OPERATION: 'folder_operation',
    SYSTEM: 'system'
  };

  /**
   * エラーメッセージマッピング
   */
  static ERROR_MESSAGES = {
    [this.ERROR_TYPES.NETWORK]: {
      default: 'ネットワークエラーが発生しました。インターネット接続を確認してください。',
      timeout: 'リクエストがタイムアウトしました。しばらく待ってから再試行してください。',
      offline: 'オフライン状態です。インターネット接続を確認してください。',
      server_error: 'サーバーエラーが発生しました。しばらく待ってから再試行してください。'
    },
    [this.ERROR_TYPES.VALIDATION]: {
      default: '入力内容に問題があります。',
      file_size: 'ファイルサイズが制限を超えています。',
      file_type: 'サポートされていないファイル形式です。',
      required: '必須項目が入力されていません。'
    },
    [this.ERROR_TYPES.AUTHORIZATION]: {
      default: 'この操作を実行する権限がありません。',
      login_required: 'ログインが必要です。'
    },
    [this.ERROR_TYPES.NOT_FOUND]: {
      default: '指定されたリソースが見つかりません。',
      folder: 'フォルダが見つかりません。',
      file: 'ファイルが見つかりません。'
    },
    [this.ERROR_TYPES.STORAGE]: {
      default: 'ストレージエラーが発生しました。',
      quota_exceeded: 'ストレージ容量が不足しています。'
    },
    [this.ERROR_TYPES.FILE_OPERATION]: {
      default: 'ファイル操作に失敗しました。',
      upload_failed: 'ファイルのアップロードに失敗しました。',
      download_failed: 'ファイルのダウンロードに失敗しました。'
    },
    [this.ERROR_TYPES.FOLDER_OPERATION]: {
      default: 'フォルダ操作に失敗しました。',
      create_failed: 'フォルダの作成に失敗しました。',
      delete_failed: 'フォルダの削除に失敗しました。'
    },
    [this.ERROR_TYPES.SYSTEM]: {
      default: 'システムエラーが発生しました。システム管理者にお問い合わせください。'
    }
  };

  /**
   * 通知表示用の設定
   */
  static NOTIFICATION_CONFIG = {
    duration: 5000,
    position: 'top-right',
    showClose: true,
    showIcon: true
  };

  /**
   * エラーハンドリングのメインメソッド
   */
  static handleError(error, context = {}) {
    console.error('Document management error:', error, context);

    const errorInfo = this.analyzeError(error);
    const userMessage = this.getUserMessage(errorInfo);

    // エラー統計の記録
    this.recordErrorStats(errorInfo, context);

    // ユーザーへの通知表示
    this.showErrorNotification(userMessage, errorInfo.type);

    // 復旧可能なエラーの場合は復旧オプションを提供
    if (this.isRecoverableError(errorInfo)) {
      this.showRecoveryOptions(errorInfo, context);
    }

    return errorInfo;
  }

  /**
   * エラーの分析
   */
  static analyzeError(error) {
    let type = this.ERROR_TYPES.SYSTEM;
    let code = 500;
    let message = '';
    let details = {};

    if (error.response) {
      // HTTP レスポンスエラー
      code = error.response.status;
      message = error.response.data?.message || error.message;
      details = error.response.data || {};

      switch (code) {
        case 401:
        case 403:
          type = this.ERROR_TYPES.AUTHORIZATION;
          break;
        case 404:
          type = this.ERROR_TYPES.NOT_FOUND;
          break;
        case 422:
          type = this.ERROR_TYPES.VALIDATION;
          break;
        case 413:
          type = this.ERROR_TYPES.STORAGE;
          break;
        case 500:
        case 502:
        case 503:
          type = this.ERROR_TYPES.SYSTEM;
          break;
        default:
          type = this.ERROR_TYPES.SYSTEM;
      }
    } else if (error.request) {
      // ネットワークエラー
      type = this.ERROR_TYPES.NETWORK;
      message = 'ネットワークエラーが発生しました。';

      if (error.code === 'ECONNABORTED') {
        details.subtype = 'timeout';
      } else if (!navigator.onLine) {
        details.subtype = 'offline';
      }
    } else {
      // その他のエラー
      message = error.message || 'Unknown error';

      // メッセージベースの分類
      const lowerMessage = message.toLowerCase();
      if (lowerMessage.includes('file') || lowerMessage.includes('ファイル')) {
        type = this.ERROR_TYPES.FILE_OPERATION;
      } else if (lowerMessage.includes('folder') || lowerMessage.includes('フォルダ')) {
        type = this.ERROR_TYPES.FOLDER_OPERATION;
      }
    }

    return {
      type,
      code,
      message,
      details,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href
    };
  }

  /**
   * ユーザー向けメッセージの取得
   */
  static getUserMessage(errorInfo) {
    const messages = this.ERROR_MESSAGES[errorInfo.type] || this.ERROR_MESSAGES[this.ERROR_TYPES.SYSTEM];

    // サブタイプがある場合はそれを優先
    if (errorInfo.details.subtype && messages[errorInfo.details.subtype]) {
      return messages[errorInfo.details.subtype];
    }

    // サーバーからのメッセージがある場合はそれを使用
    if (errorInfo.message && this.isUserFriendlyMessage(errorInfo.message)) {
      return errorInfo.message;
    }

    return messages.default;
  }

  /**
   * ユーザーフレンドリーなメッセージかどうかの判定
   */
  static isUserFriendlyMessage(message) {
    // 技術的なエラーメッセージを除外
    const technicalKeywords = [
      'exception', 'stack trace', 'undefined', 'null',
      'syntax error', 'parse error', 'fatal error'
    ];

    const lowerMessage = message.toLowerCase();
    return !technicalKeywords.some(keyword => lowerMessage.includes(keyword));
  }

  /**
   * エラー通知の表示
   */
  static showErrorNotification(message, type) {
    const config = {
      ...this.NOTIFICATION_CONFIG,
      type: this.getNotificationType(type),
      title: this.getNotificationTitle(type)
    };

    // Bootstrap Toast を使用した通知表示
    if (typeof window.showToast === 'function') {
      window.showToast(message, config.type, config);
    } else {
      // フォールバック: アラート表示
      alert(`${config.title}: ${message}`);
    }
  }

  /**
   * 通知タイプの取得
   */
  static getNotificationType(errorType) {
    switch (errorType) {
      case this.ERROR_TYPES.VALIDATION:
        return 'warning';
      case this.ERROR_TYPES.AUTHORIZATION:
        return 'warning';
      case this.ERROR_TYPES.NOT_FOUND:
        return 'info';
      case this.ERROR_TYPES.NETWORK:
        return 'warning';
      default:
        return 'error';
    }
  }

  /**
   * 通知タイトルの取得
   */
  static getNotificationTitle(errorType) {
    switch (errorType) {
      case this.ERROR_TYPES.NETWORK:
        return 'ネットワークエラー';
      case this.ERROR_TYPES.VALIDATION:
        return '入力エラー';
      case this.ERROR_TYPES.AUTHORIZATION:
        return '権限エラー';
      case this.ERROR_TYPES.NOT_FOUND:
        return 'リソースが見つかりません';
      case this.ERROR_TYPES.STORAGE:
        return 'ストレージエラー';
      case this.ERROR_TYPES.FILE_OPERATION:
        return 'ファイル操作エラー';
      case this.ERROR_TYPES.FOLDER_OPERATION:
        return 'フォルダ操作エラー';
      default:
        return 'システムエラー';
    }
  }

  /**
   * 復旧可能エラーの判定
   */
  static isRecoverableError(errorInfo) {
    return [
      this.ERROR_TYPES.NETWORK,
      this.ERROR_TYPES.VALIDATION
    ].includes(errorInfo.type);
  }

  /**
   * 復旧オプションの表示
   */
  static showRecoveryOptions(errorInfo, context) {
    if (errorInfo.type === this.ERROR_TYPES.NETWORK) {
      this.showNetworkRecoveryOptions(context);
    } else if (errorInfo.type === this.ERROR_TYPES.VALIDATION) {
      this.showValidationRecoveryOptions(errorInfo, context);
    }
  }

  /**
   * ネットワークエラー復旧オプション
   */
  static showNetworkRecoveryOptions(context) {
    const retryButton = document.createElement('button');
    retryButton.className = 'btn btn-primary btn-sm ms-2';
    retryButton.textContent = '再試行';
    retryButton.onclick = () => {
      if (context.retryCallback && typeof context.retryCallback === 'function') {
        context.retryCallback();
      } else {
        window.location.reload();
      }
    };

    // 通知に再試行ボタンを追加（実装は通知システムに依存）
    if (typeof window.addToastAction === 'function') {
      window.addToastAction(retryButton);
    }
  }

  /**
   * バリデーションエラー復旧オプション
   */
  static showValidationRecoveryOptions(errorInfo, context) {
    // フォームフィールドのハイライト
    if (errorInfo.details.errors) {
      Object.keys(errorInfo.details.errors).forEach(field => {
        const element = document.querySelector(`[name="${field}"]`);
        if (element) {
          element.classList.add('is-invalid');

          // エラーメッセージの表示
          const feedback = element.parentNode.querySelector('.invalid-feedback');
          if (feedback) {
            feedback.textContent = errorInfo.details.errors[field][0];
          }
        }
      });
    }
  }

  /**
   * エラー統計の記録
   */
  static recordErrorStats(errorInfo, context) {
    const stats = JSON.parse(localStorage.getItem('documentErrorStats') || '{}');
    const today = new Date().toDateString();

    if (!stats[today]) {
      stats[today] = {};
    }

    if (!stats[today][errorInfo.type]) {
      stats[today][errorInfo.type] = 0;
    }

    stats[today][errorInfo.type]++;

    // 過去30日分のみ保持
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

    Object.keys(stats).forEach(date => {
      if (new Date(date) < thirtyDaysAgo) {
        delete stats[date];
      }
    });

    localStorage.setItem('documentErrorStats', JSON.stringify(stats));
  }

  /**
   * エラー統計の取得
   */
  static getErrorStats(days = 7) {
    const stats = JSON.parse(localStorage.getItem('documentErrorStats') || '{}');
    const result = {};

    for (let i = 0; i < days; i++) {
      const date = new Date();
      date.setDate(date.getDate() - i);
      const dateStr = date.toDateString();

      result[dateStr] = stats[dateStr] || {};
    }

    return result;
  }

  /**
   * オンライン状態の監視
   */
  static initializeNetworkMonitoring() {
    window.addEventListener('online', () => {
      this.showErrorNotification('インターネット接続が復旧しました。', this.ERROR_TYPES.NETWORK);
    });

    window.addEventListener('offline', () => {
      this.showErrorNotification('インターネット接続が切断されました。', this.ERROR_TYPES.NETWORK);
    });
  }

  /**
   * グローバルエラーハンドラーの設定
   */
  static initializeGlobalErrorHandling() {
    // 未処理のPromise拒否をキャッチ
    window.addEventListener('unhandledrejection', (event) => {
      this.handleError(event.reason, { type: 'unhandled_promise_rejection' });
    });

    // JavaScript エラーをキャッチ
    window.addEventListener('error', (event) => {
      this.handleError(new Error(event.message), {
        type: 'javascript_error',
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      });
    });
  }
}

// モジュールとしてエクスポート
export default DocumentErrorHandler;

// グローバルオブジェクトとしても利用可能にする
window.DocumentErrorHandler = DocumentErrorHandler;