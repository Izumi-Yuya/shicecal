/**
 * Sidebar Component Module
 * Handles sidebar functionality including toggle, responsive behavior, and navigation
 */

/**
 * Sidebar Component Class
 */
export class SidebarComponent {
    constructor() {
        this.sidebar = null;
        this.mainContent = null;
        this.sidebarToggle = null;
        console.log('Sidebar: Initializing SidebarComponent');
        this.init();
    }

    init() {
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.querySelector('.main-content');
        this.sidebarToggle = document.getElementById('sidebarToggle');

        if (!this.sidebar || !this.mainContent) {
            console.warn('Sidebar: Required elements not found', {
                sidebar: !!this.sidebar,
                mainContent: !!this.mainContent,
                sidebarToggle: !!this.sidebarToggle
            });
            return;
        }

        console.log('Sidebar: All required elements found, setting up event listeners');

        // Load saved sidebar state
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState === 'true') {
            this.collapseSidebar();
        } else {
            // Set initial position when sidebar is expanded
            this.updateToggleButtonPosition(false);
        }

        // Toggle sidebar on button click - with fallback
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleSidebar();
            });
        } else {
            console.warn('Sidebar: Toggle button not found, setting up fallback');
            // Fallback: Listen for any element with sidebarToggle id
            document.addEventListener('click', (e) => {
                if (e.target.id === 'sidebarToggle' || e.target.closest('#sidebarToggle')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleSidebar();
                }
            });
        }

        // Handle responsive behavior
        this.handleResponsive();

        // Close sidebar when clicking outside on mobile
        this.handleOutsideClick();
    }

    toggleSidebar() {
        if (!this.sidebar) {
            console.error('Sidebar: Cannot toggle - sidebar element not found');
            return;
        }

        const isCollapsed = this.sidebar.classList.contains('collapsed');
        console.log('Sidebar: Toggling sidebar, currently collapsed:', isCollapsed);

        if (isCollapsed) {
            this.expandSidebar();
        } else {
            this.collapseSidebar();
        }

        // Save state
        localStorage.setItem('sidebarCollapsed', !isCollapsed);
    }

    collapseSidebar() {
        console.log('Sidebar: Collapsing sidebar');
        this.sidebar.classList.add('collapsed');
        this.mainContent.classList.add('expanded');

        // Update toggle button icon
        const toggleIcon = this.sidebarToggle?.querySelector('i');
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-bars';
            console.log('Sidebar: Updated toggle icon to bars');
        }

        // Update toggle button position for collapsed state
        this.updateToggleButtonPosition(true);
        console.log('Sidebar: Sidebar collapsed successfully');
    }

    expandSidebar() {
        console.log('Sidebar: Expanding sidebar');
        this.sidebar.classList.remove('collapsed');
        this.mainContent.classList.remove('expanded');

        // Update toggle button icon
        const toggleIcon = this.sidebarToggle?.querySelector('i');
        if (toggleIcon) {
            toggleIcon.className = 'fas fa-times';
            console.log('Sidebar: Updated toggle icon to times');
        }

        // Update toggle button position for expanded state
        this.updateToggleButtonPosition(false);
        console.log('Sidebar: Sidebar expanded successfully');
    }

    updateToggleButtonPosition(isCollapsed) {
        if (!this.sidebarToggle) {
            return;
        }

        if (isCollapsed) {
            // When sidebar is collapsed - position above facility list text
            this.sidebarToggle.style.left = '45px';
            this.sidebarToggle.style.top = '93px';
        } else {
            // When sidebar is expanded - position next to menu text
            this.sidebarToggle.style.left = '200px';
            this.sidebarToggle.style.top = '88px';
        }
    }

    handleResponsive() {
        const mediaQuery = window.matchMedia('(max-width: 768px)');

        const handleMediaChange = (e) => {
            if (e.matches) {
                // Mobile: Always collapse sidebar initially
                this.sidebar.classList.add('collapsed');
                this.mainContent.classList.add('expanded');
            } else {
                // Desktop: Restore saved state
                const sidebarState = localStorage.getItem('sidebarCollapsed');
                if (sidebarState !== 'true') {
                    this.sidebar.classList.remove('collapsed');
                    this.mainContent.classList.remove('expanded');
                }
            }
        };

        // Initial check
        handleMediaChange(mediaQuery);

        // Listen for changes (using addEventListener instead of deprecated addListener)
        mediaQuery.addEventListener('change', handleMediaChange);
    }

    handleOutsideClick() {
        document.addEventListener('click', (e) => {
            const isSmallScreen = window.innerWidth <= 768;
            const isClickInsideSidebar = this.sidebar.contains(e.target);
            const isClickOnToggle = this.sidebarToggle.contains(e.target);
            const isSidebarVisible = !this.sidebar.classList.contains('collapsed');

            if (isSmallScreen && !isClickInsideSidebar && !isClickOnToggle && isSidebarVisible) {
                this.collapseSidebar();
            }
        });
    }
}

/**
 * Active Menu Component Class
 */
export class ActiveMenuComponent {
    constructor() {
        this.init();
    }

    init() {
        this.highlightActiveMenu();
        this.handleMenuClicks();
    }

    highlightActiveMenu() {
        const currentPath = window.location.pathname;
        const menuLinks = document.querySelectorAll('.sidebar .nav-link');

        menuLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;

            // Exact match or starts with path (for sub-pages)
            if (linkPath === currentPath || (currentPath.startsWith(linkPath) && linkPath !== '/')) {
                link.classList.add('active');

                // Scroll active item into view
                setTimeout(() => {
                    link.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            }
        });
    }

    handleMenuClicks() {
        const menuLinks = document.querySelectorAll('.sidebar .nav-link');

        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Remove active class from all links
                menuLinks.forEach(l => l.classList.remove('active'));

                // Add active class to clicked link
                link.classList.add('active');

                // On mobile, collapse sidebar after navigation
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const mainContent = document.querySelector('.main-content');
                    if (sidebar && mainContent) {
                        const sidebarComponent = new SidebarComponent();
                        sidebarComponent.collapseSidebar();
                    }
                }
            });
        });
    }
}

/**
 * Smooth Scroll Component Class
 */
export class SmoothScrollComponent {
    constructor() {
        this.init();
    }

    init() {
        const sidebarContent = document.querySelector('.sidebar-content');
        if (sidebarContent) {
            // Add smooth scrolling behavior
            sidebarContent.style.scrollBehavior = 'smooth';
        }
    }
}

/**
 * Initialize all sidebar components
 */
export function initializeSidebar() {
    const sidebarComponent = new SidebarComponent();
    const activeMenuComponent = new ActiveMenuComponent();
    const smoothScrollComponent = new SmoothScrollComponent();

    // Add keyboard shortcuts
    document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            sidebarComponent.toggleSidebar();
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        sidebarComponent.handleResponsive();
    });

    return {
        sidebar: sidebarComponent,
        activeMenu: activeMenuComponent,
        smoothScroll: smoothScrollComponent
    };
}

// Auto-initialize when DOM is ready as fallback
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if not already initialized by main app
    if (!window.sidebarInitialized) {
        console.log('Sidebar: Auto-initializing as fallback');
        initializeSidebar();
        window.sidebarInitialized = true;
    }
});

// Debug function to test sidebar functionality
window.testSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    console.log('Sidebar Debug Info:', {
        sidebar: !!sidebar,
        toggle: !!toggle,
        mainContent: !!mainContent,
        sidebarClasses: sidebar?.className,
        mainContentClasses: mainContent?.className,
        sidebarInitialized: window.sidebarInitialized
    });
    
    if (toggle) {
        console.log('Manually triggering sidebar toggle...');
        toggle.click();
    }
};
