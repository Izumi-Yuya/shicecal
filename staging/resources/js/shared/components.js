/**
 * Shared UI Components for the Shise-Cal application
 * Reusable component classes and utilities
 */

import { debounce, showToast, confirmDialog } from './utils.js';

/**
 * Form Validator Component
 */
export class FormValidator {
  /**
   * Validate required fields
   * @param {HTMLFormElement} form - Form element to validate
   * @returns {boolean} - Validation result
   */
  static validateRequired(form) {
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
  }

  /**
   * Show field error
   * @param {HTMLElement} field - Field element
   * @param {string} message - Error message
   */
  static showFieldError(field, message) {
    this.clearFieldError(field);
    field.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
  }

  /**
   * Clear field error
   * @param {HTMLElement} field - Field element
   */
  static clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
      errorDiv.remove();
    }
  }
}

/**
 * Search Component
 */
export class SearchComponent {
  constructor() {
    this.init();
  }

  init() {
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
      const debouncedSearch = debounce(
        this.performSearch.bind(this),
        300
      );
      input.addEventListener('input', debouncedSearch);
    });
  }

  performSearch(event) {
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
}

/**
 * Table Component
 */
export class TableComponent {
  constructor() {
    this.init();
  }

  init() {
    this.initSorting();
    this.initRowSelection();
  }

  initSorting() {
    const sortableHeaders = document.querySelectorAll('[data-sort]');
    sortableHeaders.forEach(header => {
      header.style.cursor = 'pointer';
      header.addEventListener('click', this.sortTable.bind(this));
    });
  }

  sortTable(event) {
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
  }

  initRowSelection() {
    const selectAllCheckbox = document.querySelector('[data-select-all]');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', this.toggleAllRows.bind(this));
    }

    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    rowCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', this.updateSelectAllState.bind(this));
    });
  }

  toggleAllRows(event) {
    const isChecked = event.target.checked;
    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    rowCheckboxes.forEach(checkbox => {
      checkbox.checked = isChecked;
      this.toggleRowHighlight(checkbox);
    });
  }

  updateSelectAllState() {
    const selectAllCheckbox = document.querySelector('[data-select-all]');
    const rowCheckboxes = document.querySelectorAll('[data-select-row]');
    const checkedCount = document.querySelectorAll('[data-select-row]:checked').length;

    if (selectAllCheckbox) {
      selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
      selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
  }

  toggleRowHighlight(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
      row.classList.add('table-active');
    } else {
      row.classList.remove('table-active');
    }
  }
}

/**
 * Modal Component
 */
export class ModalComponent {
  /**
   * Show modal
   * @param {string} modalId - Modal element ID
   * @param {Object} options - Bootstrap modal options
   * @returns {bootstrap.Modal} - Bootstrap modal instance
   */
  static show(modalId, options = {}) {
    const modal = document.getElementById(modalId);
    if (modal) {
      const bsModal = new bootstrap.Modal(modal, options);
      bsModal.show();
      return bsModal;
    }
  }

  /**
   * Hide modal
   * @param {string} modalId - Modal element ID
   */
  static hide(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      const bsModal = bootstrap.Modal.getInstance(modal);
      if (bsModal) {
        bsModal.hide();
      }
    }
  }
}

/**
 * Service Cards Component
 */
export class ServiceCardsComponent {
  constructor() {
    this.init();
  }

  init() {
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
  }

  adjustCardSectionHeight(content, serviceCards) {
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
  }

  limitServiceDisplay(content, serviceCards) {
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
  }

  createShowMoreCard(hiddenCount) {
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
  }

  addMoreContentIndicators(section, serviceCards) {
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
  }

  removeMoreContentIndicators(section) {
    section.classList.remove('has-more-content');

    const indicator = section.querySelector('.more-content-indicator');
    if (indicator) indicator.remove();

    const bottomIndicator = section.querySelector('.bottom-indicator');
    if (bottomIndicator) bottomIndicator.remove();
  }

  toggleServiceDisplay(content, serviceCards, showMoreCard) {
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
}