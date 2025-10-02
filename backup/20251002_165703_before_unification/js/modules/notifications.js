/**
 * Notifications Module - ES6 Module
 * Handles notification management functionality including real-time updates and filtering
 */

export class NotificationManager {
    constructor() {
        this.notifications = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadNotifications();
        this.setupRealTimeUpdates();
    }

    setupEventListeners() {
    // Mark as read functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mark-as-read') || e.target.closest('.mark-as-read')) {
                const button = e.target.closest('.mark-as-read');
                const notificationId = button.dataset.notificationId;
                this.markAsRead(notificationId);
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchNotifications');
        if (searchInput) {
            searchInput.addEventListener('input',
                this.debounce(() => this.filterNotifications(), 300));
        }

        // Sort functionality
        const sortSelect = document.getElementById('sortNotifications');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => this.sortNotifications());
        }

        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target').replace('#', '');
                this.filterByTab(target);
            });
        });
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('unread');
                    notificationElement.classList.add('read');
                    notificationElement.dataset.read = 'true';

                    // Remove the mark as read button
                    const button = notificationElement.querySelector('.mark-as-read');
                    if (button) {
                        button.remove();
                    }

                    // Update badge counts
                    this.updateBadgeCounts();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    filterNotifications() {
        const searchInput = document.getElementById('searchNotifications');
        if (!searchInput) {
            return;
        }

        const searchTerm = searchInput.value.toLowerCase();
        const notifications = document.querySelectorAll('.notification-item');

        notifications.forEach(notification => {
            const title = notification.querySelector('.notification-title')?.textContent.toLowerCase() || '';
            const message = notification.querySelector('.notification-message')?.textContent.toLowerCase() || '';

            if (title.includes(searchTerm) || message.includes(searchTerm)) {
                notification.style.display = 'block';
            } else {
                notification.style.display = 'none';
            }
        });
    }

    sortNotifications() {
        const sortSelect = document.getElementById('sortNotifications');
        if (!sortSelect) {
            return;
        }

        const sortBy = sortSelect.value;
        const container = document.getElementById('notificationsList');
        if (!container) {
            return;
        }

        const notifications = Array.from(container.children);

        notifications.sort((a, b) => {
            switch (sortBy) {
            case 'newest':
                return new Date(b.dataset.created) - new Date(a.dataset.created);
            case 'oldest':
                return new Date(a.dataset.created) - new Date(b.dataset.created);
            case 'unread_first':
                if (a.dataset.read === 'false' && b.dataset.read === 'true') {
                    return -1;
                }
                if (a.dataset.read === 'true' && b.dataset.read === 'false') {
                    return 1;
                }
                return new Date(b.dataset.created) - new Date(a.dataset.created);
            case 'type':
                return a.dataset.type.localeCompare(b.dataset.type);
            default:
                return 0;
            }
        });

        notifications.forEach(notification => container.appendChild(notification));
    }

    filterByTab(tab) {
        const notifications = document.querySelectorAll('.notification-item');

        notifications.forEach(notification => {
            switch (tab) {
            case 'unread':
                notification.style.display = notification.dataset.read === 'false' ? 'block' : 'none';
                break;
            case 'comments':
                notification.style.display = notification.dataset.type === 'comment' ? 'block' : 'none';
                break;
            case 'approvals':
                notification.style.display = notification.dataset.type === 'approval' ? 'block' : 'none';
                break;
            default:
                notification.style.display = 'block';
            }
        });
    }

    updateBadgeCounts() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;

        // Update header badge
        const headerBadge = document.querySelector('h1 .badge');
        if (headerBadge) {
            if (unreadCount > 0) {
                headerBadge.textContent = unreadCount;
                headerBadge.style.display = 'inline';
            } else {
                headerBadge.style.display = 'none';
            }
        }

        // Update tab badge
        const tabBadge = document.querySelector('#unread-tab .badge');
        if (tabBadge) {
            tabBadge.textContent = unreadCount;
        }

        // Update stats card
        const statsCard = document.querySelector('.stats-card.bg-danger h4');
        if (statsCard) {
            statsCard.textContent = unreadCount;
        }
    }

    setupRealTimeUpdates() {
    // Poll for new notifications every 30 seconds
        setInterval(() => {
            this.checkForNewNotifications();
        }, 30000);
    }

    async checkForNewNotifications() {
        try {
            const response = await fetch('/notifications/recent');
            const data = await response.json();

            if (data.success && data.new_count > 0) {
                // Show toast notification for new notifications
                this.showToast(`${data.new_count}件の新しい通知があります`, 'info');
            }
        } catch (error) {
            console.error('Error checking for new notifications:', error);
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

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

    loadNotifications() {
    // Load notifications data if needed
        this.updateBadgeCounts();
    }
}

/**
 * Global functions for backward compatibility
 * These functions provide a bridge between the old global API and the new ES6 modules
 */
export function markAllAsRead() {
    if (window.notificationManager) {
        window.notificationManager.markAllAsRead();
    }
}

export function deleteNotification(id) {
    if (confirm('この通知を削除しますか？')) {
        fetch(`/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const element = document.querySelector(`[data-id="${id}"]`);
                    if (element) {
                        element.remove();
                    }
                    if (window.notificationManager) {
                        window.notificationManager.updateBadgeCounts();
                    }
                }
            });
    }
}

export function exportNotifications() {
    window.open('/notifications/export', '_blank');
}

export function showNotificationSettings() {
    const modal = new bootstrap.Modal(document.getElementById('notificationSettingsModal'));
    modal.show();
}

export function saveNotificationSettings() {
    const form = document.getElementById('notificationSettingsForm');
    const formData = new FormData(form);

    fetch('/notifications/settings', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('notificationSettingsModal'));
                modal.hide();
                if (window.notificationManager) {
                    window.notificationManager.showToast('通知設定が保存されました', 'success');
                }
            }
        });
}

/**
 * Initialize notification manager
 * @returns {NotificationManager} - Notification manager instance
 */
export function initializeNotificationManager() {
    const manager = new NotificationManager();
    // Make it globally accessible for backward compatibility
    window.notificationManager = manager;

    // Expose global functions for backward compatibility
    window.markAllAsRead = markAllAsRead;
    window.deleteNotification = deleteNotification;
    window.exportNotifications = exportNotifications;
    window.showNotificationSettings = showNotificationSettings;
    window.saveNotificationSettings = saveNotificationSettings;

    return manager;
}
