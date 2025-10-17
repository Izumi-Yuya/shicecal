/**
 * Application Utilities
 * Shared utility functions for the application
 */

export class AppUtils {
  static _confirmPromise = null;
  static _confirmOpen = false;

  static getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  static formatCurrency(amount, currency = 'JPY') {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '¥0';
    }
    return new Intl.NumberFormat('ja-JP', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  static formatArea(area, unit = 'm²') {
    if (area === null || area === undefined || isNaN(area)) {
      return `0${unit}`;
    }
    return `${parseFloat(area).toLocaleString('ja-JP')}${unit}`;
  }

  static formatDate(date, includeTime = false) {
    if (!date) return '';
    const dateObj = date instanceof Date ? date : new Date(date);
    if (isNaN(dateObj.getTime())) return '';

    const options = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      timeZone: 'Asia/Tokyo'
    };

    if (includeTime) {
      options.hour = '2-digit';
      options.minute = '2-digit';
    }

    return dateObj.toLocaleDateString('ja-JP', options);
  }

  static debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  static escapeHtml(text) {
    if (typeof text !== 'string') return text;
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  static showLoading(element, text = '読み込み中...') {
    if (!element) return;
    element.dataset.originalContent = element.innerHTML;
    element.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text}`;
    element.disabled = true;
  }

  static hideLoading(element) {
    if (!element) return;
    if (element.dataset.originalContent) {
      element.innerHTML = element.dataset.originalContent;
      delete element.dataset.originalContent;
    }
    element.disabled = false;
  }

  static showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      container.style.zIndex = '9999';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

    container.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
      toast.remove();
    });
  }

  static async confirmDialog(message, title = '確認', options = {}) {
    // Input validation
    if (!message || typeof message !== 'string') {
      console.error('confirmDialog: message is required and must be a string');
      return Promise.resolve(false);
    }

    return new Promise((resolve) => {
      // Prevent multiple dialogs
      if (AppUtils._confirmOpen && AppUtils._confirmPromise) {
        console.warn('confirmDialog is already open');
        return resolve(false);
      }

      AppUtils._confirmOpen = true;

      // Clean up existing modal
      const existingModal = document.getElementById('confirm-modal');
      if (existingModal) {
        existingModal.remove();
      }

      // Icon and button configuration
      let iconClass = 'fas fa-question-circle text-primary me-3 fa-2x';
      let buttonClass = 'btn btn-primary';
      let buttonText = 'OK';

      if (options.type === 'delete') {
        iconClass = 'fas fa-trash text-danger me-3 fa-2x';
        buttonClass = 'btn btn-danger';
        buttonText = '削除';
      } else if (options.type === 'warning') {
        iconClass = 'fas fa-exclamation-triangle text-warning me-3 fa-2x';
        buttonClass = 'btn btn-warning';
        buttonText = 'OK';
      }

      // Create modal HTML
      const modalHtml = `
                <div class="modal" id="confirm-modal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmModalLabel">${this.escapeHtml(title)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex align-items-center">
                                    <i class="${iconClass}" aria-hidden="true"></i>
                                    <div>${this.escapeHtml(message).replace(/\n/g, '<br>')}</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="confirm-cancel-btn">キャンセル</button>
                                <button type="button" class="${buttonClass}" id="confirm-ok-btn">${buttonText}</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      document.body.insertAdjacentHTML('beforeend', modalHtml);

      const modal = document.getElementById('confirm-modal');

      // Immediately set z-index before showing modal
      modal.style.zIndex = '2050';
      modal.style.position = 'fixed';

      const modalDialog = modal.querySelector('.modal-dialog');
      if (modalDialog) {
        modalDialog.style.zIndex = '2051';
      }

      const okButton = modal.querySelector('#confirm-ok-btn');
      const cancelButton = modal.querySelector('#confirm-cancel-btn');
      const closeButton = modal.querySelector('.btn-close');

      let isResolved = false;

      const resolveDialog = (result) => {
        if (isResolved) return;
        isResolved = true;

        try {
          const modalInstance = bootstrap.Modal.getInstance(modal);
          if (modalInstance) {
            modal.addEventListener('hidden.bs.modal', () => {
              modal.remove();
              AppUtils._confirmOpen = false;
              AppUtils._confirmPromise = null;
              resolve(result);
            }, { once: true });
            modalInstance.hide();
          } else {
            modal.remove();
            AppUtils._confirmOpen = false;
            AppUtils._confirmPromise = null;
            resolve(result);
          }
        } catch (e) {
          console.warn('Error during modal close:', e);
          modal.remove();
          AppUtils._confirmOpen = false;
          AppUtils._confirmPromise = null;
          resolve(result);
        }
      };

      // Event listeners
      okButton.addEventListener('click', () => resolveDialog(true), { once: true });
      cancelButton.addEventListener('click', () => resolveDialog(false), { once: true });
      closeButton.addEventListener('click', () => resolveDialog(false), { once: true });

      // Show modal
      try {
        // Force z-index before creating Bootstrap modal instance
        modal.style.zIndex = '2050';
        modal.style.position = 'fixed';

        const modalInstance = new bootstrap.Modal(modal, {
          backdrop: 'static',
          keyboard: true
        });

        modal.addEventListener('shown.bs.modal', () => {
          // Adjust backdrop z-index after modal is shown
          const backdrops = document.querySelectorAll('.modal-backdrop');
          backdrops.forEach(backdrop => {
            backdrop.style.zIndex = '2040';
          });

          // Ensure z-index is still correct (in case Bootstrap overrode it)
          modal.style.zIndex = '2050';
          modal.style.position = 'fixed';

          const dialog = modal.querySelector('.modal-dialog');
          if (dialog) {
            dialog.style.zIndex = '2051';
          }

          okButton.focus();
        }, { once: true });

        modalInstance.show();
      } catch (e) {
        console.error('Error showing modal:', e);
        AppUtils._confirmOpen = false;
        AppUtils._confirmPromise = null;
        resolve(false);
      }
    });
  }
}