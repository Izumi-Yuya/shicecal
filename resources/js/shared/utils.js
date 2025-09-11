/**
 * Shared utility functions for the Shise-Cal application
 * Common helper functions used across multiple modules
 */

/**
 * Format currency value for display
 * @param {number} amount - The amount to format
 * @param {string} currency - Currency code (default: 'JPY')
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currency = 'JPY') {
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

/**
 * Format area value for display
 * @param {number} area - The area value to format
 * @param {string} unit - Unit of measurement (default: 'm²')
 * @returns {string} Formatted area string
 */
export function formatArea(area, unit = 'm²') {
    if (area === null || area === undefined || isNaN(area)) {
        return `0${unit}`;
    }

    return `${parseFloat(area).toLocaleString('ja-JP')}${unit}`;
}

/**
 * Format date for display in Japanese format
 * @param {string|Date} date - Date to format
 * @param {boolean} includeTime - Whether to include time (default: false)
 * @returns {string} Formatted date string
 */
export function formatDate(date, includeTime = false) {
    if (!date) {
        return '';
    }

    const dateObj = date instanceof Date ? date : new Date(date);

    if (isNaN(dateObj.getTime())) {
        return '';
    }

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

/**
 * Debounce function to limit function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait) {
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

/**
 * Throttle function to limit function calls
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} Throttled function
 */
export function throttle(func, limit) {
    let inThrottle;
    return function executedFunction(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Deep clone an object
 * @param {Object} obj - Object to clone
 * @returns {Object} Cloned object
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') {
        return obj;
    }

    if (obj instanceof Date) {
        return new Date(obj.getTime());
    }

    if (obj instanceof Array) {
        return obj.map(item => deepClone(item));
    }

    if (typeof obj === 'object') {
        const clonedObj = {};
        for (const key in obj) {
            if (obj.hasOwnProperty(key)) {
                clonedObj[key] = deepClone(obj[key]);
            }
        }
        return clonedObj;
    }
}

/**
 * Generate a unique ID
 * @param {string} prefix - Optional prefix for the ID
 * @returns {string} Unique ID
 */
export function generateId(prefix = 'id') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
export function escapeHtml(text) {
    if (typeof text !== 'string') {
        return text;
    }

    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show loading state on an element
 * @param {HTMLElement} element - Element to show loading on
 * @param {string} text - Loading text (default: '読み込み中...')
 */
export function showLoading(element, text = '読み込み中...') {
    if (!element) {
        return;
    }

    element.dataset.originalContent = element.innerHTML;
    element.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text}`;
    element.disabled = true;
}

/**
 * Hide loading state on an element
 * @param {HTMLElement} element - Element to hide loading from
 */
export function hideLoading(element) {
    if (!element) {
        return;
    }

    if (element.dataset.originalContent) {
        element.innerHTML = element.dataset.originalContent;
        delete element.dataset.originalContent;
    }
    element.disabled = false;
}

/**
 * Show toast notification
 * @param {string} message - Message to show
 * @param {string} type - Type of toast ('success', 'error', 'warning', 'info')
 * @param {number} duration - Duration in milliseconds (default: 3000)
 */
export function showToast(message, type = 'info', duration = 3000) {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toast);

    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();

    // Remove toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * Confirm dialog with custom styling
 * @param {string} message - Confirmation message
 * @param {string} title - Dialog title (default: '確認')
 * @returns {Promise<boolean>} Promise that resolves to true if confirmed
 */
export function confirmDialog(message, title = '確認') {
    return new Promise((resolve) => {
    // Create modal if it doesn't exist
        let modal = document.getElementById('confirm-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'confirm-modal';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirm-modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="confirm-modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                            <button type="button" class="btn btn-primary" id="confirm-modal-ok">OK</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Update content
        document.getElementById('confirm-modal-title').textContent = title;
        document.getElementById('confirm-modal-body').textContent = message;

        // Set up event handlers
        const okButton = document.getElementById('confirm-modal-ok');
        const newOkButton = okButton.cloneNode(true);
        okButton.parentNode.replaceChild(newOkButton, okButton);

        newOkButton.addEventListener('click', () => {
            bootstrap.Modal.getInstance(modal).hide();
            resolve(true);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            resolve(false);
        }, { once: true });

        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    });
}
