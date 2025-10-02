/**
 * Layout-specific JavaScript functionality
 * Handles notification updates and other layout-related features
 */

/**
 * Update notification count from server
 */
function updateNotificationCount() {
    // Get the route URL from a meta tag or data attribute
    const notificationRoute = document.querySelector('meta[name="notification-route"]')?.getAttribute('content');
    if (!notificationRoute) {
        console.warn('Notification route not found');
        return;
    }

    fetch(notificationRoute, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    console.warn('User not authenticated, stopping notification updates');
                    return null;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data === null) {
                return;
            } // Skip if unauthenticated

            const badge = document.getElementById('unread-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching notification count:', error);
            // Optionally stop the interval on persistent errors
        });
}

/**
 * Initialize layout functionality
 */
function initializeLayout() {
    // Only start if we have the notification badge element and are authenticated
    if (document.getElementById('unread-count')) {
        updateNotificationCount();

        // Update count every 30 seconds
        setInterval(updateNotificationCount, 30000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initializeLayout);

// Export functions for potential external use
export { updateNotificationCount, initializeLayout };
