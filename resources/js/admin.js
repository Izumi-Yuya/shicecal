/**
 * Admin UI Enhancement JavaScript
 * Provides common functionality for admin interfaces
 */

class AdminUI {
    constructor() {
        this.init();
    }

    init() {
        this.setupDataTables();
        this.setupTooltips();
        this.setupConfirmDialogs();
        this.setupAjaxForms();
        this.setupBulkActions();
        this.setupRealTimeUpdates();
    }

    /**
     * Initialize DataTables for enhanced table functionality
     */
    setupDataTables() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.admin-table').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ja.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        }
    }

    /**
     * Setup Bootstrap tooltips
     */
    setupTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Setup confirmation dialogs for dangerous actions
     */
    setupConfirmDialogs() {
        document.addEventListener('click', function (e) {
            if (e.target.matches('[data-confirm]')) {
                e.preventDefault();
                const message = e.target.getAttribute('data-confirm');

                if (confirm(message)) {
                    if (e.target.tagName === 'A') {
                        window.location.href = e.target.href;
                    } else if (e.target.tagName === 'BUTTON' && e.target.form) {
                        e.target.form.submit();
                    }
                }
            }
        });
    }

    /**
     * Setup AJAX form submissions with loading states
     */
    setupAjaxForms() {
        document.addEventListener('submit', function (e) {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();

                const form = e.target;
                const submitButton = form.querySelector('[type="submit"]');
                const originalText = submitButton.innerHTML;

                // Show loading state
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
                submitButton.disabled = true;

                const formData = new FormData(form);

                fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showAlert(data.message || '操作が完了しました', 'success');

                            // Reload page or redirect if specified
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else if (data.reload) {
                                location.reload();
                            }
                        } else {
                            this.showAlert(data.message || 'エラーが発生しました', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.showAlert('通信エラーが発生しました', 'danger');
                    })
                    .finally(() => {
                        // Restore button state
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                    });
            }
        });
    }

    /**
     * Setup bulk action functionality
     */
    setupBulkActions() {
        // Select all checkbox functionality
        document.addEventListener('change', function (e) {
            if (e.target.matches('.select-all')) {
                const checkboxes = document.querySelectorAll('.bulk-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });
                this.updateBulkActionButtons();
            }

            if (e.target.matches('.bulk-checkbox')) {
                this.updateBulkActionButtons();
            }
        }.bind(this));
    }

    /**
     * Update bulk action button states
     */
    updateBulkActionButtons() {
        const selectedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
        const bulkButtons = document.querySelectorAll('.bulk-action-btn');

        bulkButtons.forEach(button => {
            button.disabled = selectedCount === 0;
            const countSpan = button.querySelector('.selected-count');
            if (countSpan) {
                countSpan.textContent = selectedCount;
            }
        });
    }

    /**
     * Setup real-time updates for dashboards
     */
    setupRealTimeUpdates() {
        // Update statistics every 30 seconds
        if (document.querySelector('.stats-card')) {
            setInterval(() => {
                this.updateStatistics();
            }, 30000);
        }
    }

    /**
     * Update dashboard statistics
     */
    updateStatistics() {
        const statsEndpoint = document.querySelector('[data-stats-endpoint]');
        if (!statsEndpoint) return;

        fetch(statsEndpoint.getAttribute('data-stats-endpoint'))
            .then(response => response.json())
            .then(data => {
                Object.keys(data).forEach(key => {
                    const element = document.querySelector(`[data-stat="${key}"]`);
                    if (element) {
                        element.textContent = data[key];
                    }
                });
            })
            .catch(error => console.error('Stats update error:', error));
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info', duration = 5000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto-dismiss
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, duration);
    }

    /**
     * Show loading overlay
     */
    showLoading(message = '処理中...') {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'position-fixed w-100 h-100 d-flex align-items-center justify-content-center';
        overlay.style.cssText = 'top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 9999;';
        overlay.innerHTML = `
            <div class="bg-white p-4 rounded shadow text-center">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div>${message}</div>
            </div>
        `;

        document.body.appendChild(overlay);
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Format numbers with thousand separators
     */
    formatNumber(num) {
        return new Intl.NumberFormat('ja-JP').format(num);
    }

    /**
     * Format dates in Japanese format
     */
    formatDate(date, includeTime = false) {
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };

        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }

        return new Intl.DateTimeFormat('ja-JP', options).format(new Date(date));
    }

    /**
     * Export table data to CSV
     */
    exportTableToCSV(tableSelector, filename = 'export.csv') {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        const rows = Array.from(table.querySelectorAll('tr'));
        const csvContent = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => {
                const text = cell.textContent.trim();
                return `"${text.replace(/"/g, '""')}"`;
            }).join(',');
        }).join('\n');

        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Debounce function for search inputs
     */
    debounce(func, wait) {
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
     * Setup live search functionality
     */
    setupLiveSearch(inputSelector, targetSelector, searchFunction) {
        const input = document.querySelector(inputSelector);
        if (!input) return;

        const debouncedSearch = this.debounce(searchFunction, 300);
        input.addEventListener('input', debouncedSearch);
    }
}

// Initialize admin UI when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    window.adminUI = new AdminUI();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminUI;
}