// Main Application JavaScript
import './land-info.js';

// Global application state
window.ShiseCal = {
  config: {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    locale: document.documentElement.lang || 'ja'
  },
  utils: {},
  components: {}
};

// Utility functions
window.ShiseCal.utils = {
  // Show loading spinner
  showLoading: function (element) {
    if (element) {
      element.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border spinner-border-primary" role="status"><span class="visually-hidden">読み込み中...</span></div></div>';
    }
  },

  // Hide loading spinner
  hideLoading: function (element) {
    if (element) {
      element.innerHTML = '';
    }
  },

  // Show toast notification
  showToast: function (message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
    const toast = this.createToast(message, type);
    toastContainer.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function () {
      toast.remove();
    });
  },

  // Create toast container if it doesn't exist
  createToastContainer: function () {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
  },

  // Create toast element
  createToast: function (message, type) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    const iconMap = {
      success: 'fas fa-check-circle text-success',
      error: 'fas fa-exclamation-circle text-danger',
      warning: 'fas fa-exclamation-triangle text-warning',
      info: 'fas fa-info-circle text-info'
    };

    toast.innerHTML = `
            <div class="toast-header">
                <i class="${iconMap[type] || iconMap.info} me-2"></i>
                <strong class="me-auto">通知</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

    return toast;
  },

  // Format date for Japanese locale
  formatDate: function (date, options = {}) {
    const defaultOptions = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      ...options
    };
    return new Date(date).toLocaleDateString('ja-JP', defaultOptions);
  },

  // Format currency for Japanese yen
  formatCurrency: function (amount) {
    return new Intl.NumberFormat('ja-JP', {
      style: 'currency',
      currency: 'JPY'
    }).format(amount);
  },

  // Debounce function for search inputs
  debounce: function (func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  // Confirm dialog with Japanese text
  confirm: function (message, callback) {
    if (window.confirm(message)) {
      callback();
    }
  },

  // AJAX helper with CSRF token
  ajax: function (url, options = {}) {
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': this.config?.csrfToken || '',
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    };

    return fetch(url, defaultOptions)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .catch(error => {
        console.error('AJAX Error:', error);
        this.showToast('通信エラーが発生しました。', 'error');
        throw error;
      });
  }
};

// Form validation utilities
window.ShiseCal.components.FormValidator = {
  // Validate required fields
  validateRequired: function (form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        this.showFieldError(field, 'この項目は必須です。');
        isValid = false;
      } else {
        this.clearFieldError(field);
      }
    });

    return isValid;
  },

  // Show field error
  showFieldError: function (field, message) {
    this.clearFieldError(field);
    field.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
  },

  // Clear field error
  clearFieldError: function (field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
      errorDiv.remove();
    }
  }
};

// Search functionality
window.ShiseCal.components.Search = {
  init: function () {
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
      const debouncedSearch = window.ShiseCal.utils.debounce(
        this.performSearch.bind(this),
        300
      );
      input.addEventListener('input', debouncedSearch);
    });
  },

  performSearch: function (event) {
    const input = event.target;
    const searchTerm = input.value.trim();
    const targetSelector = input.dataset.search;
    const targetElements = document.querySelectorAll(targetSelector);

    targetElements.forEach(element => {
      const text = element.textContent.toLowerCase();
      const shouldShow = searchTerm === '' || text.includes(searchTerm.toLowerCase());
      element.style.display = shouldShow ? '' : 'none';
    });
  }
};

// Table utilities
window.ShiseCal.components.Table = {
  init: function () {
    this.initSorting();
    this.initRowSelection();
  },

  initSorting: function () {
    const sortableHeaders = document.querySelectorAll('[data-sort]');
    sortableHeaders.forEach(header => {
      header.style.cursor = 'pointer';
      header.addEventListener('click', this.sortTable.bind(this));
    });
  },

  sortTable: function (event) {
    const header = event.target;
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const isAscending = header.dataset.sortDirection !== 'asc';

    rows.sort((a, b) => {
      const aText = a.children[columnIndex].textContent.trim();
      const bText = b.children[columnIndex].textContent.trim();

      if (isAscending) {
        return aText.localeCompare(bText, 'ja');
      } else {
        return bText.localeCompare(aText, 'ja');
      }
    });

    // Update sort direction
    header.dataset.sortDirection = isAscending ? 'asc' : 'desc';

    // Update sort icons
    const allHeaders = table.querySelectorAll('[data-sort]');
    allHeaders.forEach(h => {
      const icon = h.querySelector('.sort-icon');
      if (icon) icon.remove();
    });

    const sortIcon = document.createElement('i');
    sortIcon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} ms-1 sort-icon`;
    header.appendChild(sortIcon);

    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
  },

  initRowSelection: function () {
    const selectAllCheckbox = document.querySelector('[data-select-all]');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', this.toggleAllRows.bind(this));
    }

    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    rowCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', this.updateSelectAllState.bind(this));
    });
  },

  toggleAllRows: function (event) {
    const isChecked = event.target.checked;
    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    rowCheckboxes.forEach(checkbox => {
      checkbox.checked = isChecked;
      this.toggleRowHighlight(checkbox);
    });
  },

  updateSelectAllState: function () {
    const selectAllCheckbox = document.querySelector('[data-select-all]');
    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    const checkedCount = document.querySelectorAll('[data-select-row]:checked').length;

    if (selectAllCheckbox) {
      selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
      selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
  },

  toggleRowHighlight: function (checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
      row.classList.add('table-active');
    } else {
      row.classList.remove('table-active');
    }
  }
};

// Modal utilities
window.ShiseCal.components.Modal = {
  show: function (modalId, options = {}) {
    const modal = document.getElementById(modalId);
    if (modal) {
      const bsModal = new bootstrap.Modal(modal, options);
      bsModal.show();
      return bsModal;
    }
  },

  hide: function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      const bsModal = bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    }
  }
};

// Service Cards Component
window.ShiseCal.components.ServiceCards = {
  init: function () {
    const cardSections = document.querySelectorAll('.card-section');

    cardSections.forEach(section => {
      const content = section.querySelector('.card-section-content');
      const serviceCards = section.querySelectorAll('.service-card');

      if (!content || serviceCards.length === 0) return;

      console.log(`Found ${serviceCards.length} service cards`);

      this.adjustCardSectionHeight(content, serviceCards);
      this.limitServiceDisplay(content, serviceCards);
      this.addMoreContentIndicators(section, serviceCards);
    });
  },

  adjustCardSectionHeight: function (content, serviceCards) {
    const itemCount = serviceCards.length;
    content.setAttribute('data-items', itemCount);

    // Always remove overflow classes first
    content.classList.remove('has-overflow', 'expanded');

    // Reset overflow style to visible for 10 or fewer items
    if (itemCount <= 10) {
      content.style.overflow = 'visible';
      content.style.maxHeight = 'none';
    } else {
      // Only add overflow class if there are more than 10 items
      content.classList.add('has-overflow');
    }
  },

  limitServiceDisplay: function (content, serviceCards) {
    const maxDisplay = 10;

    if (serviceCards.length <= maxDisplay) return;

    // Hide cards beyond the 10th
    serviceCards.forEach((card, index) => {
      if (index >= maxDisplay) {
        card.classList.add('hidden');
      }
    });

    // Create "show more" indicator
    const showMoreCard = this.createShowMoreCard(serviceCards.length - maxDisplay);
    content.appendChild(showMoreCard);

    // Add click handler for show more
    showMoreCard.addEventListener('click', () => {
      this.toggleServiceDisplay(content, serviceCards, showMoreCard);
    });
  },

  createShowMoreCard: function (hiddenCount) {
    const showMoreCard = document.createElement('div');
    showMoreCard.className = 'service-card show-more';
    showMoreCard.innerHTML = `
            <div class="service-card-title">
                <i class="fas fa-plus-circle me-2"></i>
                他 ${hiddenCount} 件のサービス
            </div>
            <div class="service-card-meta">
                <small>クリックして表示</small>
            </div>
        `;
    return showMoreCard;
  },

  addMoreContentIndicators: function (section, serviceCards) {
    if (serviceCards.length <= 10) return;

    // Add class to section to show indicators
    section.classList.add('has-more-content');

    // Add indicator to header
    const header = section.querySelector('.card-section-header');
    if (header && !header.querySelector('.more-content-indicator')) {
      const indicator = document.createElement('span');
      indicator.className = 'more-content-indicator';
      indicator.innerHTML = `<i class="fas fa-ellipsis-h me-1"></i>+${serviceCards.length - 10}`;
      header.appendChild(indicator);
    }

    // Add bottom indicator
    const content = section.querySelector('.card-section-content');
    if (content && !content.querySelector('.bottom-indicator')) {
      const bottomIndicator = document.createElement('div');
      bottomIndicator.className = 'bottom-indicator';
      bottomIndicator.innerHTML = '<i class="fas fa-chevron-down me-1"></i>他にもあります';
      content.appendChild(bottomIndicator);
    }
  },

  removeMoreContentIndicators: function (section) {
    section.classList.remove('has-more-content');

    const indicator = section.querySelector('.more-content-indicator');
    if (indicator) indicator.remove();

    const bottomIndicator = section.querySelector('.bottom-indicator');
    if (bottomIndicator) bottomIndicator.remove();
  },

  toggleServiceDisplay: function (content, serviceCards, showMoreCard) {
    const section = content.closest('.card-section');
    const hiddenCards = content.querySelectorAll('.service-card.hidden');
    const isExpanded = hiddenCards.length === 0;

    if (isExpanded) {
      // Collapse - hide cards beyond 10th
      serviceCards.forEach((card, index) => {
        if (index >= 10) {
          card.classList.add('hidden');
        }
      });

      showMoreCard.innerHTML = `
                <div class="service-card-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    他 ${serviceCards.length - 10} 件のサービス
                </div>
                <div class="service-card-meta">
                    <small>クリックして表示</small>
                </div>
            `;

      // Reset to 10 items display - no scroll needed
      content.setAttribute('data-items', '10');
      content.classList.remove('has-overflow', 'expanded');
      content.style.overflow = 'visible';
      content.style.maxHeight = 'none';

      // Show more content indicators again
      this.addMoreContentIndicators(section, serviceCards);

    } else {
      // Expand - show all cards
      hiddenCards.forEach(card => {
        card.classList.remove('hidden');
      });

      showMoreCard.innerHTML = `
                <div class="service-card-title">
                    <i class="fas fa-minus-circle me-2"></i>
                    表示を縮小
                </div>
                <div class="service-card-meta">
                    <small>クリックして縮小</small>
                </div>
            `;

      // Show all items with scroll if needed
      content.setAttribute('data-items', serviceCards.length.toString());
      content.classList.remove('has-overflow');
      content.classList.add('expanded');

      // Hide more content indicators when expanded
      this.removeMoreContentIndicators(section);
    }
  }
};

// Initialize application
document.addEventListener('DOMContentLoaded', function () {
  console.log('Shise-Cal application loaded');

  // Initialize components
  window.ShiseCal.components.Search.init();
  window.ShiseCal.components.Table.init();
  window.ShiseCal.components.ServiceCards.init();

  // Add fade-in animation to main content
  const mainContent = document.querySelector('main');
  if (mainContent) {
    mainContent.classList.add('fade-in');
  }

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize popovers
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // Handle form submissions with loading states
  const forms = document.querySelectorAll('form[data-loading]');
  forms.forEach(form => {
    form.addEventListener('submit', function (event) {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>処理中...';
      }
    });
  });

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease-in-out';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 5000);
  });
});

// Sidebar functionality
window.ShiseCal.components.sidebar = {
  init: function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    if (!sidebarToggle || !sidebar || !mainContent) return;

    // Load saved sidebar state
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
      this.collapseSidebar(sidebar, mainContent);
    }

    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', () => {
      this.toggleSidebar(sidebar, mainContent);
    });

    // Handle responsive behavior
    this.handleResponsive(sidebar, mainContent);

    // Close sidebar when clicking outside on mobile
    this.handleOutsideClick(sidebar);
  },

  toggleSidebar: function (sidebar, mainContent) {
    const isCollapsed = sidebar.classList.contains('collapsed');

    if (isCollapsed) {
      this.expandSidebar(sidebar, mainContent);
    } else {
      this.collapseSidebar(sidebar, mainContent);
    }

    // Save state
    localStorage.setItem('sidebarCollapsed', !isCollapsed);
  },

  collapseSidebar: function (sidebar, mainContent) {
    sidebar.classList.add('collapsed');
    mainContent.classList.add('expanded');

    // Update toggle button icon
    const toggleIcon = document.querySelector('#sidebarToggle i');
    if (toggleIcon) {
      toggleIcon.className = 'fas fa-bars';
    }
  },

  expandSidebar: function (sidebar, mainContent) {
    sidebar.classList.remove('collapsed');
    mainContent.classList.remove('expanded');

    // Update toggle button icon
    const toggleIcon = document.querySelector('#sidebarToggle i');
    if (toggleIcon) {
      toggleIcon.className = 'fas fa-times';
    }
  },

  handleResponsive: function (sidebar, mainContent) {
    const mediaQuery = window.matchMedia('(max-width: 768px)');

    const handleMediaChange = (e) => {
      if (e.matches) {
        // Mobile: Always collapse sidebar initially
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
      } else {
        // Desktop: Restore saved state
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        if (sidebarState !== 'true') {
          sidebar.classList.remove('collapsed');
          mainContent.classList.remove('expanded');
        }
      }
    };

    // Initial check
    handleMediaChange(mediaQuery);

    // Listen for changes
    mediaQuery.addListener(handleMediaChange);
  },

  handleOutsideClick: function (sidebar) {
    document.addEventListener('click', (e) => {
      const isSmallScreen = window.innerWidth <= 768;
      const isClickInsideSidebar = sidebar.contains(e.target);
      const isClickOnToggle = document.getElementById('sidebarToggle').contains(e.target);
      const isSidebarVisible = !sidebar.classList.contains('collapsed');

      if (isSmallScreen && !isClickInsideSidebar && !isClickOnToggle && isSidebarVisible) {
        const mainContent = document.querySelector('.main-content');
        this.collapseSidebar(sidebar, mainContent);
      }
    });
  }
};

// Active menu highlighting
window.ShiseCal.components.activeMenu = {
  init: function () {
    this.highlightActiveMenu();
    this.handleMenuClicks();
  },

  highlightActiveMenu: function () {
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
  },

  handleMenuClicks: function () {
    const menuLinks = document.querySelectorAll('.sidebar .nav-link');

    menuLinks.forEach(link => {
      link.addEventListener('click', function () {
        // Remove active class from all links
        menuLinks.forEach(l => l.classList.remove('active'));

        // Add active class to clicked link
        this.classList.add('active');

        // On mobile, collapse sidebar after navigation
        if (window.innerWidth <= 768) {
          const sidebar = document.getElementById('sidebar');
          const mainContent = document.querySelector('.main-content');
          if (sidebar && mainContent) {
            window.ShiseCal.components.sidebar.collapseSidebar(sidebar, mainContent);
          }
        }
      });
    });
  }
};

// Smooth scrolling for sidebar
window.ShiseCal.components.smoothScroll = {
  init: function () {
    const sidebarContent = document.querySelector('.sidebar-content');
    if (sidebarContent) {
      // Add smooth scrolling behavior
      sidebarContent.style.scrollBehavior = 'smooth';
    }
  }
};

// Initialize sidebar components when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
  // Initialize sidebar functionality
  window.ShiseCal.components.sidebar.init();
  window.ShiseCal.components.activeMenu.init();
  window.ShiseCal.components.smoothScroll.init();

  // Add keyboard shortcuts
  document.addEventListener('keydown', function (e) {
    // Ctrl/Cmd + B to toggle sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
      e.preventDefault();
      const sidebarToggle = document.getElementById('sidebarToggle');
      if (sidebarToggle) {
        sidebarToggle.click();
      }
    }
  });
});

// Handle window resize
window.addEventListener('resize', function () {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.querySelector('.main-content');

  if (sidebar && mainContent) {
    window.ShiseCal.components.sidebar.handleResponsive(sidebar, mainContent);
  }
});