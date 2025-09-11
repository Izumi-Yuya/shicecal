/**
 * Service Table Cleanup Module
 * Handles dynamic table row management and accessibility
 */

export class ServiceTableCleanup {
    constructor(tableBodyId = 'svc-body') {
        try {
            this.tableBody = document.getElementById(tableBodyId);
            this.config = window.serviceTableConfig || {};
            this.observers = new Map();
            this.isInitialized = false;
            this.updateTimeout = null;

            if (this.tableBody) {
                this.init();
            } else {
                console.warn(`ServiceTableCleanup: Table body with ID '${tableBodyId}' not found`);
            }
        } catch (error) {
            console.error('ServiceTableCleanup initialization failed:', error);
        }
    }

    /**
   * Initialize the service table cleanup
   */
    init() {
        if (this.isInitialized) {
            console.warn('ServiceTableCleanup already initialized');
            return;
        }

        try {
            // Use requestAnimationFrame for better performance
            requestAnimationFrame(() => {
                this.cleanupEmptyRows();
                this.updateRowSpan();
                this.setupAccessibility();
                this.setupObservers();
                this.isInitialized = true;
            });
        } catch (error) {
            console.error('ServiceTableCleanup initialization failed:', error);
        }
    }

    /**
   * Remove empty template rows for better UX
   */
    cleanupEmptyRows() {
        try {
            const templateRows = this.tableBody.querySelectorAll('tr.template-row');

            if (templateRows.length === 0) {
                return;
            }

            // Use batch DOM operations for better performance
            const rowsToRemove = Array.from(templateRows).filter(row =>
                this.isRowCompletelyEmpty(row)
            );

            // Remove rows in a single batch
            rowsToRemove.forEach(row => row.remove());

            if (rowsToRemove.length > 0) {
                console.debug(`Removed ${rowsToRemove.length} empty template rows`);
            }
        } catch (error) {
            console.error('Error cleaning up empty rows:', error);
        }
    }

    /**
   * Check if a row is completely empty
   */
    isRowCompletelyEmpty(row) {
        const cells = ['service-name', 'period-start', 'period-end'];
        return cells.every(className => {
            const cell = row.querySelector(`.${className}`);
            return !cell || !cell.textContent.trim();
        });
    }

    /**
   * Update rowspan attribute for service header
   */
    updateRowSpan() {
        const serviceHeader = this.tableBody.querySelector('.service-header');
        if (!serviceHeader) {
            return;
        }

        const dataRows = this.countNonEmptyRows();
        const newRowSpan = Math.max(1, dataRows);

        if (serviceHeader.getAttribute('rowspan') !== newRowSpan.toString()) {
            serviceHeader.setAttribute('rowspan', newRowSpan);
        }
    }

    /**
   * Count rows that contain actual data
   */
    countNonEmptyRows() {
        const allRows = this.tableBody.querySelectorAll('tr:not(.template-row)');
        return Array.from(allRows).filter(row => !this.isRowCompletelyEmpty(row)).length;
    }

    /**
   * Setup accessibility features
   */
    setupAccessibility() {
        const rows = this.tableBody.querySelectorAll('tr');

        rows.forEach((row, index) => {
            // Add ARIA attributes
            row.setAttribute('role', 'row');
            row.setAttribute('tabindex', '0');

            // Add keyboard navigation
            this.setupKeyboardNavigation(row, index, rows);
        });

        // Add table description for screen readers
        this.addTableDescription();
    }

    /**
   * Setup keyboard navigation for table rows
   */
    setupKeyboardNavigation(row, currentIndex, allRows) {
        row.addEventListener('keydown', (event) => {
            this.handleKeyNavigation(event, currentIndex, allRows);
        }, { passive: false });
    }

    /**
   * Handle keyboard navigation events
   */
    handleKeyNavigation(event, currentIndex, rows) {
        const keyActions = {
            'ArrowDown': () => Math.min(currentIndex + 1, rows.length - 1),
            'ArrowUp': () => Math.max(currentIndex - 1, 0),
            'Home': () => 0,
            'End': () => rows.length - 1
        };

        const getTargetIndex = keyActions[event.key];
        if (!getTargetIndex) {
            return;
        }

        event.preventDefault();
        const targetIndex = getTargetIndex();

        if (targetIndex !== currentIndex && rows[targetIndex]) {
            rows[targetIndex].focus();
            this.announceRowChange(targetIndex, rows.length);
        }
    }

    /**
   * Announce row changes for screen readers
   */
    announceRowChange(rowIndex, totalRows) {
        const announcement = `行 ${rowIndex + 1} / ${totalRows}`;
        this.announceToScreenReader(announcement);
    }

    /**
   * Add table description for better accessibility
   */
    addTableDescription() {
        const table = this.tableBody.closest('table');
        if (!table || table.hasAttribute('aria-describedby')) {
            return;
        }

        const descriptionId = 'service-table-description';
        const description = document.createElement('div');
        description.id = descriptionId;
        description.className = 'visually-hidden';
        description.textContent = 'サービス種類と有効期限を表示するテーブル。矢印キーで行間を移動できます。';

        table.parentNode.insertBefore(description, table);
        table.setAttribute('aria-describedby', descriptionId);
    }

    /**
   * Setup mutation observers for dynamic content
   */
    setupObservers() {
        if (!window.MutationObserver) {
            return;
        }

        const observer = new MutationObserver((mutations) => {
            let shouldUpdate = false;

            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && mutation.target === this.tableBody) {
                    shouldUpdate = true;
                }
            });

            if (shouldUpdate) {
                // Debounce updates
                this.debounceUpdate();
            }
        });

        observer.observe(this.tableBody, {
            childList: true,
            subtree: true
        });

        this.observers.set('tableBody', observer);
    }

    /**
   * Debounced update function
   */
    debounceUpdate() {
        if (this.updateTimeout) {
            clearTimeout(this.updateTimeout);
        }

        this.updateTimeout = setTimeout(() => {
            this.updateRowSpan();
            this.setupAccessibility();
        }, 100);
    }

    /**
   * Announce message to screen readers
   */
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'visually-hidden';
        announcement.textContent = message;

        document.body.appendChild(announcement);

        // Remove after announcement
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    /**
   * Cleanup observers and event listeners
   */
    destroy() {
    // Clear timeout
        if (this.updateTimeout) {
            clearTimeout(this.updateTimeout);
        }

        // Disconnect observers
        this.observers.forEach(observer => observer.disconnect());
        this.observers.clear();

    // Remove event listeners (they'll be garbage collected with the elements)
    }
}

/**
 * Auto-initialize when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if service table exists
    if (document.getElementById('svc-body')) {
        new ServiceTableCleanup();
    }
});

/**
 * Export for manual initialization
 */
export default ServiceTableCleanup;
