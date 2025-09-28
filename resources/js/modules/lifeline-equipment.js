/**
 * Lifeline Equipment Management Module
 * Handles tab switching, card editing, data saving, and error handling for lifeline equipment
 * Implements requirements 1.8 and 1.9 from the lifeline equipment management spec
 */

class LifelineEquipmentManager {
  constructor() {
    this.facilityId = window.facilityId || this.extractFacilityIdFromUrl();
    this.currentCategory = 'electrical';
    this.isInitialized = false;
    this.commentSystem = null;
    this.init();
  }

  /**
   * Extract facility ID from URL if not available in the window object.
   * @returns {string|null} The facility ID or null if not found
   */
  extractFacilityIdFromUrl() {
    const match = window.location.pathname.match(/\/facilities\/(\d+)/);
    return match ? match[1] : null;
  }

  init() {
    if (this.isInitialized) return;

    try {
      this.bindEvents();
      this.initializeTabSwitching();
      this.initializeCommentSystem();
      this.setupKeyboardNavigation();
      this.isInitialized = true;
      console.log('LifelineEquipmentManager initialized successfully');
    } catch (error) {
      console.error('Failed to initialize LifelineEquipmentManager:', error);
      this.showErrorMessage('ライフライン設備システムの初期化に失敗しました。ページを再読み込みしてください。');
    }
  }

  /**
   * Initialize comment system integration.
   * Integrates with the existing Shise-Cal comment system for equipment-specific discussions.
   */
  initializeCommentSystem() {
    // Check if comment system is available
    if (window.ShiseCal && window.ShiseCal.modules && window.ShiseCal.modules.comments) {
      this.commentSystem = window.ShiseCal.modules.comments;
    }
  }

  /**
   * Set up keyboard navigation for accessibility.
   */
  setupKeyboardNavigation() {
    // Handle Enter key on comment inputs
    const commentInputs = document.querySelectorAll('#lifeline-equipment .comment-input');
    commentInputs.forEach(input => {
      input.addEventListener('keypress', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          const section = input.getAttribute('data-section');
          this.submitComment(section, input.value.trim());
        }
      });
    });

    // Handle Escape key to cancel edit mode
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        const activeEditMode = document.querySelector('#lifeline-equipment .edit-mode:not(.d-none)');
        if (activeEditMode) {
          const section = activeEditMode.closest('[data-section]')?.getAttribute('data-section');
          const cardType = activeEditMode.closest('[data-card-type]')?.getAttribute('data-card-type');
          if (section && cardType) {
            this.cancelEdit(cardType, section);
          }
        }
      }
    });
  }

  bindEvents() {
    // Handle main lifeline tab activation
    const lifelineTab = document.getElementById('lifeline-tab');
    if (lifelineTab) {
      lifelineTab.addEventListener('shown.bs.tab', () => {
        this.onLifelineTabShown();
      });
    }

    // Handle sub-tab switching
    const subTabs = document.querySelectorAll('#lifelineSubTabs .nav-link');
    subTabs.forEach(tab => {
      tab.addEventListener('shown.bs.tab', (event) => {
        this.onSubTabShown(event);
      });
    });

    // Handle comment toggles
    this.bindCommentToggles();

    // Handle card toggles
    this.bindCardToggles();

    // Handle edit functionality
    this.bindEditFunctionality();
  }

  initializeTabSwitching() {
    // Initialize Bootstrap tabs for sub-navigation
    const subTabsContainer = document.getElementById('lifelineSubTabs');
    if (subTabsContainer) {
      // Ensure proper tab initialization
      const firstTab = subTabsContainer.querySelector('.nav-link.active');
      if (firstTab) {
        const tabInstance = new bootstrap.Tab(firstTab);
        // Tab is already active, no need to show
      }
    }
  }

  onLifelineTabShown() {
    console.log('Lifeline equipment tab activated');

    // Trigger animation for cards in the active sub-tab
    this.animateActiveCards();

    // Load data if needed (placeholder for future implementation)
    this.loadEquipmentData(this.currentCategory);
  }

  onSubTabShown(event) {
    const targetId = event.target.getAttribute('data-bs-target');
    this.currentCategory = targetId.replace('#', '');

    console.log(`Switched to ${this.currentCategory} equipment`);

    // Animate cards in the newly active tab
    setTimeout(() => {
      this.animateActiveCards();
    }, 100);

    // Load category-specific data (placeholder for future implementation)
    this.loadEquipmentData(this.currentCategory);
  }

  animateActiveCards() {
    const activePane = document.querySelector('#lifeline-equipment .tab-pane.active .card');
    if (activePane) {
      const cards = activePane.parentElement.querySelectorAll('.card');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-in');
      });
    }
  }

  loadEquipmentData(category) {
    // Placeholder for future API calls to load equipment data
    console.log(`Loading ${category} equipment data for facility ${this.facilityId}`);

    // This will be implemented in future tasks when the backend is ready
    // Example:
    // fetch(`/facilities/${this.facilityId}/lifeline-equipment/${category}`)
    //     .then(response => response.json())
    //     .then(data => this.updateEquipmentDisplay(category, data))
    //     .catch(error => console.error('Error loading equipment data:', error));
  }

  bindCommentToggles() {
    const commentToggles = document.querySelectorAll('#lifeline-equipment .comment-toggle');
    commentToggles.forEach(toggle => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const section = toggle.getAttribute('data-section');
        this.toggleComments(section);
      });
    });

    // Bind comment submit buttons
    const commentSubmitButtons = document.querySelectorAll('#lifeline-equipment .comment-submit');
    commentSubmitButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const section = button.getAttribute('data-section');
        const input = document.querySelector(`[data-section="${section}"] .comment-input`);
        if (input) {
          this.submitComment(section, input.value.trim());
        }
      });
    });
  }

  toggleComments(section) {
    console.log(`Toggle comments for section: ${section}`);

    const commentSection = document.getElementById(`comment-section-${section}`);
    const toggleButton = document.querySelector(`[data-section="${section}"].comment-toggle`);

    if (!commentSection || !toggleButton) {
      console.warn(`Comment section or toggle button not found for: ${section}`);
      return;
    }

    const isVisible = !commentSection.classList.contains('d-none');

    if (isVisible) {
      // Hide comments
      commentSection.classList.add('d-none');
      toggleButton.setAttribute('aria-expanded', 'false');
      toggleButton.classList.remove('active');
    } else {
      // Show comments
      commentSection.classList.remove('d-none');
      toggleButton.setAttribute('aria-expanded', 'true');
      toggleButton.classList.add('active');

      // Load comments for this section
      this.loadComments(section);

      // Focus on comment input
      const commentInput = commentSection.querySelector('.comment-input');
      if (commentInput) {
        setTimeout(() => commentInput.focus(), 100);
      }
    }
  }

  /**
   * Load comments for a specific section
   */
  async loadComments(section) {
    try {
      const response = await fetch(`/facilities/${this.facilityId}/comments?section=${section}`, {
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      if (response.ok) {
        const data = await response.json();
        this.displayComments(section, data.comments || []);
        this.updateCommentCount(section, data.comments?.length || 0);
      }
    } catch (error) {
      console.error('Failed to load comments:', error);
      this.showErrorMessage('コメントの読み込みに失敗しました。');
    }
  }

  /**
   * Display comments in the comment list
   */
  displayComments(section, comments) {
    const commentList = document.querySelector(`[data-section="${section}"] .comment-list`);
    if (!commentList) return;

    if (comments.length === 0) {
      commentList.innerHTML = '<p class="text-muted text-center">コメントはありません</p>';
      return;
    }

    const commentsHtml = comments.map(comment => `
      <div class="comment-item mb-2 p-2 border rounded">
        <div class="d-flex justify-content-between align-items-start">
          <div class="comment-content flex-grow-1">
            <div class="comment-text">${this.escapeHtml(comment.content)}</div>
            <small class="text-muted">
              ${comment.user_name} - ${this.formatDate(comment.created_at)}
            </small>
          </div>
          ${comment.can_delete ? `
            <button type="button" class="btn btn-outline-danger btn-sm ms-2 delete-comment-btn" 
                    data-comment-id="${comment.id}" data-section="${section}"
                    aria-label="コメントを削除">
              <i class="fas fa-trash" aria-hidden="true"></i>
            </button>
          ` : ''}
        </div>
      </div>
    `).join('');

    commentList.innerHTML = commentsHtml;

    // Bind delete comment events
    const deleteButtons = commentList.querySelectorAll('.delete-comment-btn');
    deleteButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const commentId = button.getAttribute('data-comment-id');
        this.deleteComment(section, commentId);
      });
    });
  }

  /**
   * Submit a new comment
   */
  async submitComment(section, content) {
    if (!content) return;

    const commentInput = document.querySelector(`[data-section="${section}"] .comment-input`);
    const submitButton = document.querySelector(`[data-section="${section}"] .comment-submit`);

    if (!commentInput || !submitButton) return;

    // Disable input during submission
    commentInput.disabled = true;
    submitButton.disabled = true;

    try {
      const response = await fetch(`/facilities/${this.facilityId}/comments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          section: section,
          content: content,
          facility_id: this.facilityId
        })
      });

      if (response.ok) {
        const data = await response.json();
        commentInput.value = '';
        this.loadComments(section); // Reload comments
        this.showSuccessMessage('コメントを投稿しました');
      } else {
        const errorData = await response.json();
        this.showErrorMessage(errorData.message || 'コメントの投稿に失敗しました。');
      }
    } catch (error) {
      console.error('Failed to submit comment:', error);
      this.showErrorMessage('コメントの投稿に失敗しました。');
    } finally {
      // Re-enable input
      commentInput.disabled = false;
      submitButton.disabled = false;
      commentInput.focus();
    }
  }

  /**
   * Delete a comment
   */
  async deleteComment(section, commentId) {
    if (!confirm('このコメントを削除しますか？')) return;

    try {
      const response = await fetch(`/facilities/${this.facilityId}/comments/${commentId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      if (response.ok) {
        this.loadComments(section); // Reload comments
        this.showSuccessMessage('コメントを削除しました');
      } else {
        this.showErrorMessage('コメントの削除に失敗しました。');
      }
    } catch (error) {
      console.error('Failed to delete comment:', error);
      this.showErrorMessage('コメントの削除に失敗しました。');
    }
  }

  /**
   * Update comment count display
   */
  updateCommentCount(section, count) {
    const countElement = document.querySelector(`[data-section="${section}"].comment-count`);
    if (countElement) {
      countElement.textContent = count;
      countElement.setAttribute('aria-label', `コメント数: ${count}`);
    }
  }

  bindCardToggles() {
    const cardToggles = document.querySelectorAll('#lifeline-equipment .card-toggle');
    cardToggles.forEach(toggle => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const cardType = toggle.getAttribute('data-card');
        this.toggleCard(cardType, toggle);
      });
    });
  }

  toggleCard(cardType, toggleButton) {
    const targetId = `electrical-${cardType}-content`;
    const targetElement = document.getElementById(targetId);

    if (!targetElement) {
      console.warn(`Card content element not found: ${targetId}`);
      return;
    }

    // Use Bootstrap's Collapse API
    const bsCollapse = new bootstrap.Collapse(targetElement, {
      toggle: false
    });

    const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      // Collapse the card
      bsCollapse.hide();
      toggleButton.setAttribute('aria-expanded', 'false');
      toggleButton.querySelector('i').classList.remove('fa-chevron-up');
      toggleButton.querySelector('i').classList.add('fa-chevron-down');
    } else {
      // Expand the card
      bsCollapse.show();
      toggleButton.setAttribute('aria-expanded', 'true');
      toggleButton.querySelector('i').classList.remove('fa-chevron-down');
      toggleButton.querySelector('i').classList.add('fa-chevron-up');
    }

    console.log(`Toggled ${cardType} card: ${isExpanded ? 'collapsed' : 'expanded'}`);
  }

  bindEditFunctionality() {
    // Handle edit button clicks
    const editButtons = document.querySelectorAll('#lifeline-equipment .edit-card-btn');
    editButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const cardType = button.getAttribute('data-card');
        const section = button.getAttribute('data-section');
        this.enterEditMode(cardType, section);
      });
    });

    // Handle save button clicks
    const saveButtons = document.querySelectorAll('#lifeline-equipment .save-card-btn');
    saveButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const cardType = button.getAttribute('data-card');
        const section = button.getAttribute('data-section');
        this.saveCardData(cardType, section);
      });
    });

    // Handle cancel button clicks
    const cancelButtons = document.querySelectorAll('#lifeline-equipment .cancel-edit-btn');
    cancelButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const cardType = button.getAttribute('data-card');
        const section = button.getAttribute('data-section');
        this.cancelEdit(cardType, section);
      });
    });

    // Handle form submissions
    const forms = document.querySelectorAll('#lifeline-equipment .equipment-form');
    forms.forEach(form => {
      form.addEventListener('submit', (event) => {
        event.preventDefault();
        const cardType = form.getAttribute('data-card');
        const section = form.getAttribute('data-section');
        this.saveCardData(cardType, section);
      });
    });

    // Handle character count for textareas
    this.bindCharacterCount();

    // Handle conditional field display
    this.bindConditionalFields();

    // Handle dynamic equipment list functionality
    this.bindEquipmentListFunctionality();
  }

  enterEditMode(cardType, section) {
    console.log(`Entering edit mode for ${cardType} card in ${section}`);

    const card = document.querySelector(`[data-section="${section}"]`);
    if (!card) {
      console.error(`Card not found for section: ${section}`);
      return;
    }

    const displayMode = card.querySelector('.display-mode');
    const editMode = card.querySelector('.edit-mode');

    if (displayMode && editMode) {
      displayMode.classList.add('d-none');
      editMode.classList.remove('d-none');

      // Focus on the first input field
      const firstInput = editMode.querySelector('input, select, textarea');
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
      }
    }
  }

  cancelEdit(cardType, section) {
    console.log(`Canceling edit for ${cardType} card in ${section}`);

    const card = document.querySelector(`[data-section="${section}"]`);
    if (!card) {
      console.error(`Card not found for section: ${section}`);
      return;
    }

    const displayMode = card.querySelector('.display-mode');
    const editMode = card.querySelector('.edit-mode');
    const form = card.querySelector('.equipment-form');

    if (displayMode && editMode) {
      // Reset form to original values
      if (form) {
        form.reset();
        // Clear any validation errors
        this.clearValidationErrors(form);
      }

      editMode.classList.add('d-none');
      displayMode.classList.remove('d-none');
    }
  }

  async saveCardData(cardType, section) {
    console.log(`Saving ${cardType} card data for ${section}`);

    const card = document.querySelector(`[data-section="${section}"]`);
    if (!card) {
      console.error(`Card not found for section: ${section}`);
      return;
    }

    const form = card.querySelector('.equipment-form');
    if (!form) {
      console.error(`Form not found in card: ${section}`);
      return;
    }

    // Show loading indicator
    this.showLoadingIndicator(card, true);

    // Clear previous validation errors
    this.clearValidationErrors(form);

    try {
      // Collect form data
      const formData = new FormData(form);
      const data = {};

      // Convert FormData to nested object structure
      for (let [key, value] of formData.entries()) {
        this.setNestedProperty(data, key, value);
      }

      console.log('Sending data:', data);

      // Send API request
      const response = await fetch(`/facilities/${this.facilityId}/lifeline-equipment/${this.currentCategory}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // Success - update display and exit edit mode
        this.showSuccessMessage('データを保存しました');
        await this.refreshCardDisplay(section);
        this.exitEditMode(cardType, section);
      } else {
        // Handle validation errors
        if (result.errors) {
          this.displayValidationErrors(form, result.errors);
        } else {
          this.showErrorMessage(result.message || 'データの保存に失敗しました');
        }
      }
    } catch (error) {
      // Use enhanced error handling with retry capability
      await this.handleApiError(error, 'saveCardData', () => {
        this.saveCardData(cardType, section);
      });
    } finally {
      // Hide loading indicator
      this.showLoadingIndicator(card, false);
    }
  }

  exitEditMode(cardType, section) {
    const card = document.querySelector(`[data-section="${section}"]`);
    if (!card) return;

    const displayMode = card.querySelector('.display-mode');
    const editMode = card.querySelector('.edit-mode');

    if (displayMode && editMode) {
      editMode.classList.add('d-none');
      displayMode.classList.remove('d-none');
    }
  }

  async refreshCardDisplay(section) {
    // Reload the page to refresh the display with updated data
    // In a more sophisticated implementation, we could update the display dynamically
    console.log(`Refreshing display for section: ${section}`);

    // For now, we'll reload the page to get the updated data
    // This ensures consistency with the backend data
    window.location.reload();
  }

  showLoadingIndicator(card, show) {
    const indicator = card.querySelector('.loading-indicator');
    if (indicator) {
      if (show) {
        indicator.classList.remove('d-none');
      } else {
        indicator.classList.add('d-none');
      }
    }
  }

  clearValidationErrors(form) {
    const errorElements = form.querySelectorAll('.invalid-feedback');
    errorElements.forEach(element => {
      element.textContent = '';
    });

    const invalidInputs = form.querySelectorAll('.is-invalid');
    invalidInputs.forEach(input => {
      input.classList.remove('is-invalid');
    });
  }

  displayValidationErrors(form, errors) {
    Object.keys(errors).forEach(fieldName => {
      const input = form.querySelector(`[name="${fieldName}"]`);
      if (input) {
        input.classList.add('is-invalid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
          feedback.textContent = errors[fieldName][0];
        }
      }
    });
  }

  showSuccessMessage(message) {
    // Create and show a success toast/alert
    this.showToast(message, 'success');
  }

  showErrorMessage(message, retryCallback = null) {
    // Create and show an error toast/alert with optional retry
    this.showToast(message, 'error', retryCallback);
  }

  showToast(message, type = 'info', retryCallback = null) {
    // Enhanced toast implementation with retry functionality
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';

    let toastContent = `
      <div class="d-flex align-items-center">
        <div class="flex-grow-1">${message}</div>
        <div class="ms-2">
    `;

    if (retryCallback && typeof retryCallback === 'function') {
      toastContent += `
        <button type="button" class="btn btn-sm btn-outline-light me-2 retry-btn">
          <i class="fas fa-redo" aria-hidden="true"></i> 再試行
        </button>
      `;
    }

    toastContent += `
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    `;

    toast.innerHTML = toastContent;
    document.body.appendChild(toast);

    // Bind retry button if provided
    if (retryCallback) {
      const retryBtn = toast.querySelector('.retry-btn');
      if (retryBtn) {
        retryBtn.addEventListener('click', () => {
          toast.remove();
          retryCallback();
        });
      }
    }

    // Auto-remove after 8 seconds (longer for error messages with retry)
    const autoRemoveDelay = retryCallback ? 8000 : 5000;
    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove();
      }
    }, autoRemoveDelay);
  }

  setNestedProperty(obj, path, value) {
    // Handle nested array structures like "cubicle_info[equipment_list][0][equipment_number]"
    const arrayMatch = path.match(/^([^[]+)\[([^\]]+)\]\[(\d+)\]\[([^\]]+)\]$/);
    if (arrayMatch) {
      const [, parentKey, arrayKey, index, fieldKey] = arrayMatch;
      if (!obj[parentKey]) {
        obj[parentKey] = {};
      }
      if (!obj[parentKey][arrayKey]) {
        obj[parentKey][arrayKey] = [];
      }
      if (!obj[parentKey][arrayKey][index]) {
        obj[parentKey][arrayKey][index] = {};
      }
      obj[parentKey][arrayKey][index][fieldKey] = value;
      return;
    }

    // Handle simple nested structures like "basic_info[electrical_contractor]"
    const simpleMatch = path.match(/^([^[]+)\[([^\]]+)\]$/);
    if (simpleMatch) {
      const [, parentKey, childKey] = simpleMatch;
      if (!obj[parentKey]) {
        obj[parentKey] = {};
      }
      obj[parentKey][childKey] = value;
    } else {
      obj[path] = value;
    }
  }

  bindConditionalFields() {
    // Handle conditional field display based on dropdown selections
    const conditionalTriggers = document.querySelectorAll('#lifeline-equipment [data-conditional-trigger]');
    conditionalTriggers.forEach(trigger => {
      trigger.addEventListener('change', (event) => {
        const targetName = trigger.getAttribute('data-conditional-trigger');
        this.handleConditionalDisplay(trigger, targetName);
      });

      // Initialize on page load
      const targetName = trigger.getAttribute('data-conditional-trigger');
      this.handleConditionalDisplay(trigger, targetName);
    });
  }

  handleConditionalDisplay(trigger, targetName) {
    const targetElements = document.querySelectorAll(`[data-conditional-target="${targetName}"]`);
    const selectedValue = trigger.value;

    targetElements.forEach(target => {
      if (selectedValue === '有') {
        target.style.display = 'block';
        // Make fields within the conditional section required if they have required attributes
        const requiredFields = target.querySelectorAll('[data-required-when-visible]');
        requiredFields.forEach(field => {
          field.setAttribute('required', 'required');
        });
      } else {
        target.style.display = 'none';
        // Remove required attribute and clear values when hidden
        const allFields = target.querySelectorAll('input, select, textarea');
        allFields.forEach(field => {
          field.removeAttribute('required');
          if (selectedValue === '無') {
            // Clear values when "無" is selected
            if (field.type === 'checkbox' || field.type === 'radio') {
              field.checked = false;
            } else {
              field.value = '';
            }
          }
        });
      }
    });
  }

  bindCharacterCount() {
    // Handle character count for textareas with maxlength
    const textareas = document.querySelectorAll('#lifeline-equipment textarea[maxlength]');
    textareas.forEach(textarea => {
      const maxLength = parseInt(textarea.getAttribute('maxlength'));
      const counterElement = textarea.parentElement.querySelector('.character-count .current-count');

      if (counterElement) {
        // Update count on input
        textarea.addEventListener('input', () => {
          const currentLength = textarea.value.length;
          counterElement.textContent = currentLength;

          // Add warning class when approaching limit
          const parentCounter = counterElement.closest('.character-count');
          if (currentLength > maxLength * 0.9) {
            parentCounter.classList.add('text-warning');
          } else {
            parentCounter.classList.remove('text-warning');
          }

          // Add danger class when at limit
          if (currentLength >= maxLength) {
            parentCounter.classList.add('text-danger');
            parentCounter.classList.remove('text-warning');
          } else {
            parentCounter.classList.remove('text-danger');
          }
        });

        // Initialize count on page load
        const initialLength = textarea.value.length;
        counterElement.textContent = initialLength;
      }
    });
  }

  bindEquipmentListFunctionality() {
    // Handle add equipment button clicks
    const addEquipmentButtons = document.querySelectorAll('#lifeline-equipment .add-equipment-btn');
    addEquipmentButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const equipmentType = button.getAttribute('data-equipment-type');
        this.addEquipmentItem(equipmentType);
      });
    });

    // Handle remove equipment button clicks (using event delegation)
    document.addEventListener('click', (event) => {
      if (event.target.closest('.remove-equipment-btn')) {
        event.preventDefault();
        const removeButton = event.target.closest('.remove-equipment-btn');
        const equipmentItem = removeButton.closest('.equipment-item');
        this.removeEquipmentItem(equipmentItem);
      }
    });
  }

  addEquipmentItem(equipmentType) {
    console.log(`Adding equipment item for type: ${equipmentType}`);

    const equipmentList = document.querySelector(`[data-equipment-type="${equipmentType}"] .equipment-list`);
    const noEquipmentMessage = document.querySelector(`[data-equipment-type="${equipmentType}"] .no-equipment-message`);

    if (!equipmentList) {
      console.error(`Equipment list not found for type: ${equipmentType}`);
      return;
    }

    // Get the current number of equipment items to determine the new index
    const existingItems = equipmentList.querySelectorAll('.equipment-item');
    const newIndex = existingItems.length;

    // Create new equipment item HTML
    const equipmentItemHtml = this.createEquipmentItemHtml(equipmentType, newIndex);

    // Create a temporary container to parse the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = equipmentItemHtml;
    const newEquipmentItem = tempDiv.firstElementChild;

    // Add the new item to the list
    equipmentList.appendChild(newEquipmentItem);

    // Hide the "no equipment" message if it's visible
    if (noEquipmentMessage) {
      noEquipmentMessage.style.display = 'none';
    }

    // Focus on the first input of the new item
    const firstInput = newEquipmentItem.querySelector('input');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }

    // Add animation
    newEquipmentItem.style.opacity = '0';
    newEquipmentItem.style.transform = 'translateY(-10px)';
    setTimeout(() => {
      newEquipmentItem.style.transition = 'all 0.3s ease';
      newEquipmentItem.style.opacity = '1';
      newEquipmentItem.style.transform = 'translateY(0)';
    }, 10);
  }

  removeEquipmentItem(equipmentItem) {
    if (!equipmentItem) return;

    const equipmentList = equipmentItem.closest('.equipment-list');
    const equipmentType = equipmentItem.closest('[data-equipment-type]')?.getAttribute('data-equipment-type');

    // Add animation before removal
    equipmentItem.style.transition = 'all 0.3s ease';
    equipmentItem.style.opacity = '0';
    equipmentItem.style.transform = 'translateX(-20px)';

    setTimeout(() => {
      equipmentItem.remove();

      // Reindex remaining items
      this.reindexEquipmentItems(equipmentList, equipmentType);

      // Show "no equipment" message if no items remain
      const remainingItems = equipmentList.querySelectorAll('.equipment-item');
      if (remainingItems.length === 0 && equipmentType) {
        const noEquipmentMessage = document.querySelector(`[data-equipment-type="${equipmentType}"] .no-equipment-message`);
        if (noEquipmentMessage) {
          noEquipmentMessage.style.display = 'block';
        }
      }
    }, 300);
  }

  reindexEquipmentItems(equipmentList, equipmentType) {
    const items = equipmentList.querySelectorAll('.equipment-item');
    items.forEach((item, index) => {
      // Update the data attribute
      item.setAttribute('data-equipment-index', index);

      // Update the heading
      const heading = item.querySelector('h6');
      if (heading) {
        heading.textContent = `設備 ${index + 1}`;
      }

      // Update all input names to use the new index
      const inputs = item.querySelectorAll('input');
      inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
          // Replace the index in the name attribute
          const newName = name.replace(/\[\d+\]/, `[${index}]`);
          input.setAttribute('name', newName);
        }
      });
    });
  }

  createEquipmentItemHtml(equipmentType, index) {
    return `
      <div class="equipment-item border rounded p-3 mb-3" data-equipment-index="${index}">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">設備 ${index + 1}</h6>
          <button type="button" class="btn btn-outline-danger btn-sm remove-equipment-btn"
                  aria-label="この設備を削除">
            <i class="fas fa-trash" aria-hidden="true"></i>
          </button>
        </div>
        <div class="row">
          <div class="col-md-3 mb-2">
            <label class="form-label">設備番号</label>
            <input type="text" 
                   class="form-control" 
                   name="${equipmentType}_info[equipment_list][${index}][equipment_number]"
                   maxlength="50"
                   placeholder="例: CB-001">
          </div>
          <div class="col-md-3 mb-2">
            <label class="form-label">メーカー</label>
            <input type="text" 
                   class="form-control" 
                   name="${equipmentType}_info[equipment_list][${index}][manufacturer]"
                   maxlength="100"
                   placeholder="例: 三菱電機">
          </div>
          <div class="col-md-3 mb-2">
            <label class="form-label">年式</label>
            <input type="text" 
                   class="form-control" 
                   name="${equipmentType}_info[equipment_list][${index}][model_year]"
                   maxlength="10"
                   placeholder="例: 2020">
          </div>
          <div class="col-md-3 mb-2">
            <label class="form-label">更新年月日</label>
            <input type="date" 
                   class="form-control" 
                   name="${equipmentType}_info[equipment_list][${index}][update_date]">
          </div>
        </div>
      </div>
    `;
  }

  // Utility method to get current equipment data (placeholder)
  getCurrentEquipmentData() {
    return {
      category: this.currentCategory,
      facilityId: this.facilityId,
      // Additional data will be added as the system develops
    };
  }

  /**
   * Escape HTML to prevent XSS attacks
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Format date for display
   */
  formatDate(dateString) {
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (error) {
      return dateString;
    }
  }

  /**
   * Enhanced error handling with retry capability
   */
  async handleApiError(error, operation, retryCallback = null) {
    console.error(`API Error in ${operation}:`, error);

    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      // Network error
      this.showErrorMessage('ネットワークエラーが発生しました。インターネット接続を確認してください。', retryCallback);
    } else if (error.status === 401) {
      // Unauthorized
      this.showErrorMessage('認証が必要です。ページを再読み込みしてください。');
      setTimeout(() => window.location.reload(), 3000);
    } else if (error.status === 403) {
      // Forbidden
      this.showErrorMessage('この操作を実行する権限がありません。');
    } else if (error.status === 422) {
      // Validation error
      this.showErrorMessage('入力内容に誤りがあります。フォームを確認してください。');
    } else if (error.status >= 500) {
      // Server error
      this.showErrorMessage('サーバーエラーが発生しました。しばらく時間をおいてから再試行してください。', retryCallback);
    } else {
      // Generic error
      this.showErrorMessage(`エラーが発生しました: ${error.message || '不明なエラー'}`, retryCallback);
    }
  }

  /**
   * Destroy the manager and clean up event listeners
   */
  destroy() {
    try {
      // Remove event listeners
      const elements = document.querySelectorAll('#lifeline-equipment [data-section], #lifeline-equipment .nav-link, #lifeline-equipment .comment-toggle');
      elements.forEach(element => {
        element.removeEventListener('click', this.handleClick);
        element.removeEventListener('shown.bs.tab', this.handleTabShown);
      });

      // Clear any pending timeouts
      if (this.animationTimeout) {
        clearTimeout(this.animationTimeout);
      }

      this.isInitialized = false;
      console.log('LifelineEquipmentManager destroyed');
    } catch (error) {
      console.error('Error destroying LifelineEquipmentManager:', error);
    }
  }
}

/**
 * Initialize lifeline equipment manager
 * @returns {LifelineEquipmentManager|null} Manager instance or null if not applicable
 */
function initializeLifelineEquipmentManager() {
  // Only initialize if we're on a facility detail page with lifeline equipment
  if (document.getElementById('lifeline-equipment')) {
    try {
      const manager = new LifelineEquipmentManager();
      window.lifelineEquipmentManager = manager; // For backward compatibility
      return manager;
    } catch (error) {
      console.error('Failed to initialize LifelineEquipmentManager:', error);
      return null;
    }
  }
  return null;
}

// Initialize when DOM is ready (fallback for direct script inclusion)
document.addEventListener('DOMContentLoaded', function () {
  if (!window.lifelineEquipmentManager) {
    initializeLifelineEquipmentManager();
  }
});

// Export for ES6 module usage
export { LifelineEquipmentManager, initializeLifelineEquipmentManager };