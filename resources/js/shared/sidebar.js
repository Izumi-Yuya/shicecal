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
    this.boundToggleHandler = null;
    console.log('Sidebar: Initializing SidebarComponent');
    this.init();
  }

  static getInstance() {
    if (!SidebarComponent.instance) {
      SidebarComponent.instance = new SidebarComponent();
    }
    return SidebarComponent.instance;
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

    // Check current DOM state
    const isDOMCollapsed = this.sidebar.classList.contains('collapsed');
    console.log('Sidebar: Initial DOM state - collapsed:', isDOMCollapsed);

    // Load saved sidebar state
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    console.log('Sidebar: LocalStorage state:', sidebarState);

    // 初期状態を決定（LocalStorageまたはデフォルト）
    const shouldBeCollapsed = sidebarState === 'true';

    if (shouldBeCollapsed) {
      // LocalStorageに閉じた状態が保存されている
      console.log('Sidebar: Restoring collapsed state from localStorage');
      this.collapseSidebar();
    } else {
      // デフォルトは展開状態
      console.log('Sidebar: Setting initial expanded state');

      // DOMの状態を明示的に設定
      this.sidebar.classList.remove('collapsed');
      this.mainContent.classList.remove('expanded');

      // トグルボタンからcollapsedクラスを削除
      if (this.sidebarToggle) {
        this.sidebarToggle.classList.remove('collapsed');
      }

      // アイコンを正しい状態に設定（展開時は×）
      const toggleIcon = this.sidebarToggle?.querySelector('i');
      if (toggleIcon) {
        toggleIcon.className = 'fas fa-times';
        console.log('Sidebar: Set initial icon to times (×)');
      }

      console.log('Sidebar: Initial state set to expanded');
    }

    // Toggle sidebar on button click - with fallback
    // Remove any existing event listeners first
    if (this.boundToggleHandler) {
      this.sidebarToggle?.removeEventListener('click', this.boundToggleHandler);
    }

    // Create bound handler
    this.boundToggleHandler = (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.toggleSidebar();
    };

    if (this.sidebarToggle) {
      this.sidebarToggle.addEventListener('click', this.boundToggleHandler);
      console.log('Sidebar: Toggle event listener attached');
    } else {
      console.warn('Sidebar: Toggle button not found');
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

    if (isCollapsed) {
      this.expandSidebar();
      localStorage.setItem('sidebarCollapsed', 'false');
    } else {
      this.collapseSidebar();
      localStorage.setItem('sidebarCollapsed', 'true');
    }
  }

  collapseSidebar() {
    this.sidebar.classList.add('collapsed');
    this.mainContent.classList.add('expanded');

    // トグルボタンにcollapsedクラスを追加（CSSでアニメーション）
    if (this.sidebarToggle) {
      this.sidebarToggle.classList.add('collapsed');
    }

    // Update toggle button icon
    const toggleIcon = this.sidebarToggle?.querySelector('i');
    if (toggleIcon) {
      toggleIcon.className = 'fas fa-bars';
    }
  }

  expandSidebar() {
    this.sidebar.classList.remove('collapsed');
    this.mainContent.classList.remove('expanded');

    // トグルボタンからcollapsedクラスを削除（CSSでアニメーション）
    if (this.sidebarToggle) {
      this.sidebarToggle.classList.remove('collapsed');
    }

    // Update toggle button icon
    const toggleIcon = this.sidebarToggle?.querySelector('i');
    if (toggleIcon) {
      toggleIcon.className = 'fas fa-times';
    }
  }

  handleResponsive() {
    const mediaQuery = window.matchMedia('(max-width: 768px)');

    const handleMediaChange = (e) => {
      if (e.matches) {
        // Mobile: Always collapse sidebar
        this.collapseSidebar();
      } else {
        // Desktop: Restore saved state
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState !== 'true') {
          this.expandSidebar();
        } else {
          this.collapseSidebar();
        }
      }
    };

    // 初期チェックはスキップ（init()で既に設定済み）
    // Initial check is skipped - already set in init()

    // Listen for changes only
    mediaQuery.addEventListener('change', handleMediaChange);
  }

  destroy() {
    // Clean up event listeners
    if (this.boundToggleHandler && this.sidebarToggle) {
      this.sidebarToggle.removeEventListener('click', this.boundToggleHandler);
    }
    SidebarComponent.instance = null;
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
          const sidebarComponent = SidebarComponent.getInstance();
          sidebarComponent.collapseSidebar();
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
  // Prevent multiple initializations
  if (window.sidebarInitialized) {
    console.log('Sidebar: Already initialized, skipping');
    return window.sidebarComponents;
  }

  const sidebarComponent = SidebarComponent.getInstance();
  const activeMenuComponent = new ActiveMenuComponent();
  const smoothScrollComponent = new SmoothScrollComponent();

  // Add keyboard shortcuts (only once)
  const keyboardHandler = (e) => {
    // Ctrl/Cmd + B to toggle sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
      e.preventDefault();
      sidebarComponent.toggleSidebar();
    }
  };

  // Remove any existing keyboard handler
  if (window.sidebarKeyboardHandler) {
    document.removeEventListener('keydown', window.sidebarKeyboardHandler);
  }
  document.addEventListener('keydown', keyboardHandler);
  window.sidebarKeyboardHandler = keyboardHandler;

  // Handle window resize (only once)
  const resizeHandler = () => {
    sidebarComponent.handleResponsive();
  };

  // Remove any existing resize handler
  if (window.sidebarResizeHandler) {
    window.removeEventListener('resize', window.sidebarResizeHandler);
  }
  window.addEventListener('resize', resizeHandler);
  window.sidebarResizeHandler = resizeHandler;

  const components = {
    sidebar: sidebarComponent,
    activeMenu: activeMenuComponent,
    smoothScroll: smoothScrollComponent
  };

  window.sidebarComponents = components;
  window.sidebarInitialized = true;

  return components;
}

// Auto-initialize when DOM is ready as fallback
document.addEventListener('DOMContentLoaded', () => {
  // Only initialize if not already initialized by main app
  if (!window.sidebarInitialized) {
    console.log('Sidebar: Auto-initializing as fallback');
    initializeSidebar();
  }
});

// Debug function to test sidebar functionality
window.testSidebar = function () {
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
