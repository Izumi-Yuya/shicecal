/**
 * CSV Download Manager
 * Handles CSV download functionality with iframe-based approach
 */

export class CsvDownloadManager {
  constructor() {
    // Download timeout constants
    this.DOWNLOAD_TIMEOUTS = {
      DOWNLOAD_START_DELAY: 1500,    // Time to wait after iframe load before completing
      FALLBACK_TIMEOUT: 5000,       // Maximum time to wait for download
      ERROR_CHECK_DELAY: 2000,      // Time to wait before checking for errors
      PROCESSING_UPDATE_DELAY: 500, // Time to wait before updating processing text
      CLEANUP_DELAY: 1000           // Time to wait before cleaning up elements
    };

    this.DOWNLOAD_MESSAGES = {
      PREPARING: '出力準備中...',
      GENERATING: 'CSV生成中...',
      DOWNLOADING: 'ダウンロード中...',
      SUCCESS: 'CSVファイルのダウンロードを開始しました。',
      ERROR: 'CSV出力中にエラーが発生しました。'
    };
  }

  /**
   * Start CSV download process
   */
  downloadCSV(selectedFacilities, selectedFields, exportButton) {
    const downloadContext = this.createDownloadContext(selectedFacilities, selectedFields);
    this.setupDownloadHandlers(downloadContext, exportButton);
    this.submitDownloadForm(downloadContext, exportButton);
  }

  /**
   * Create download context with iframe and form
   */
  createDownloadContext(selectedFacilities, selectedFields) {
    const downloadId = 'download_' + Date.now();

    const iframe = this.createDownloadIframe(downloadId);
    const form = this.createDownloadForm(downloadId, selectedFacilities, selectedFields);

    return {
      iframe,
      form,
      downloadStarted: false,
      downloadCompleted: false
    };
  }

  /**
   * Create hidden iframe for download
   */
  createDownloadIframe(downloadId) {
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.id = downloadId;
    document.body.appendChild(iframe);
    return iframe;
  }

  /**
   * Create form with all necessary data
   */
  createDownloadForm(targetId, selectedFacilities, selectedFields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/export/csv/generate';
    form.target = targetId;
    form.style.display = 'none';

    // Add CSRF token
    this.addFormInput(form, '_token', this.getCsrfToken());

    // Add facility IDs
    selectedFacilities.forEach(facilityId => {
      this.addFormInput(form, 'facility_ids[]', facilityId);
    });

    // Add export fields
    selectedFields.forEach(field => {
      this.addFormInput(form, 'export_fields[]', field);
    });

    return form;
  }

  /**
   * Add hidden input to form
   */
  addFormInput(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    form.appendChild(input);
  }

  /**
   * Get CSRF token from meta tag
   */
  getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  /**
   * Setup download event handlers and timeouts
   */
  setupDownloadHandlers(context, exportButton) {
    this.setupIframeLoadHandler(context, exportButton);
    this.setupFallbackTimeout(context, exportButton);
    this.setupErrorCheckTimeout(context, exportButton);
  }

  /**
   * Setup iframe load event handler
   */
  setupIframeLoadHandler(context, exportButton) {
    context.iframe.addEventListener('load', () => {
      if (!context.downloadStarted) {
        context.downloadStarted = true;
        this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.DOWNLOADING);

        setTimeout(() => {
          if (!context.downloadCompleted) {
            this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.SUCCESS, 'success');
          }
        }, this.DOWNLOAD_TIMEOUTS.DOWNLOAD_START_DELAY);
      }
    });
  }

  /**
   * Setup fallback timeout
   */
  setupFallbackTimeout(context, exportButton) {
    setTimeout(() => {
      if (!context.downloadCompleted) {
        this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.SUCCESS, 'success');
      }
    }, this.DOWNLOAD_TIMEOUTS.FALLBACK_TIMEOUT);
  }

  /**
   * Setup error checking timeout
   */
  setupErrorCheckTimeout(context, exportButton) {
    setTimeout(() => {
      this.checkForDownloadErrors(context, exportButton);
    }, this.DOWNLOAD_TIMEOUTS.ERROR_CHECK_DELAY);
  }

  /**
   * Check for download errors in iframe
   */
  checkForDownloadErrors(context, exportButton) {
    try {
      const iframeDoc = context.iframe.contentDocument || context.iframe.contentWindow.document;
      if (this.hasErrorContent(iframeDoc)) {
        this.completeDownload(context, exportButton, this.DOWNLOAD_MESSAGES.ERROR, 'error');
        return;
      }
    } catch (e) {
      // Cross-origin access issues are normal for successful downloads
      console.log('Cannot access iframe content (normal for successful downloads)');
    }

    // If we can't access iframe content, assume download started successfully
    if (!context.downloadCompleted) {
      context.downloadStarted = true;
      this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.DOWNLOADING);
    }
  }

  /**
   * Check if iframe contains error content
   */
  hasErrorContent(iframeDoc) {
    if (!iframeDoc || !iframeDoc.body || !iframeDoc.body.innerHTML.trim()) {
      return false;
    }

    const content = iframeDoc.body.innerHTML;
    return content.includes('error') || content.includes('エラー');
  }

  /**
   * Complete download process
   */
  completeDownload(context, exportButton, message, type) {
    context.downloadCompleted = true;
    this.resetButtonState(exportButton);
    this.showToast(message, type);
    this.cleanupDownload(context.form, context.iframe);
  }

  /**
   * Submit download form
   */
  submitDownloadForm(context, exportButton) {
    document.body.appendChild(context.form);
    context.form.submit();

    // Update button text to indicate processing
    setTimeout(() => {
      if (!context.downloadStarted && !context.downloadCompleted) {
        this.updateButtonState(exportButton, this.DOWNLOAD_MESSAGES.GENERATING);
      }
    }, this.DOWNLOAD_TIMEOUTS.PROCESSING_UPDATE_DELAY);
  }

  /**
   * Update button loading state
   */
  updateButtonState(button, message) {
    if (window.AppUtils && window.AppUtils.showLoading) {
      window.AppUtils.showLoading(button, message);
    }
  }

  /**
   * Reset button to normal state
   */
  resetButtonState(button) {
    if (window.AppUtils && window.AppUtils.hideLoading) {
      window.AppUtils.hideLoading(button);
    }
  }

  /**
   * Show toast message
   */
  showToast(message, type) {
    if (window.AppUtils && window.AppUtils.showToast) {
      window.AppUtils.showToast(message, type);
    }
  }

  /**
   * Clean up download elements
   */
  cleanupDownload(form, iframe) {
    setTimeout(() => {
      try {
        if (form && form.parentNode) {
          form.parentNode.removeChild(form);
        }
        if (iframe && iframe.parentNode) {
          iframe.parentNode.removeChild(iframe);
        }
      } catch (e) {
        console.warn('Error cleaning up download elements:', e);
      }
    }, this.DOWNLOAD_TIMEOUTS.CLEANUP_DELAY);
  }
}