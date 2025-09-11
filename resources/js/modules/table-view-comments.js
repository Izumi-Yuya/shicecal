/**
 * Table View Comments Module
 * Handles comment functionality specifically for table view mode
 * Refactored to use separated concerns for better maintainability
 */

import { CommentManager } from './comment-manager.js';
import { CommentUI } from './comment-ui.js';
import { showToast } from '../shared/utils.js';
import { get, post } from '../shared/api.js';

/**
 * Table View Comments Manager Class
 */
class TableViewComments {
  constructor() {
    this.facilityId = null;
    this.commentManager = null;
    this.commentUI = null;
    this.isLoading = false;
    this.comments = new Map(); // Initialize comments storage
    this.currentSection = 'basic_info';

    // Configuration
    this.config = {
      apiEndpoint: '/facilities/{facilityId}/comments',
      refreshInterval: 30000, // 30 seconds
      maxCommentLength: 500
    };
  }

  /**
 * Initialize table view comments functionality
 */
  init(facilityId) {
    try {
      this.facilityId = facilityId;

      if (!this.facilityId) {
        console.warn('Facility ID not provided for table view comments');
        return false;
      }

      // Initialize separated components
      this.commentManager = new CommentManager(facilityId, this.config);
      this.commentUI = new CommentUI('.facility-table-view');

      // Setup UI event listeners
      this.commentUI.setupEventListeners(this.commentManager);

      // Load initial data
      this.loadAllComments();
      this.startPeriodicRefresh();

      console.log('Table view comments initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize table view comments:', error);
      return false;
    }
  }

  /**
 * Setup event listeners for comment functionality using event delegation
 */
  setupEventListeners() {
    const container = document.querySelector('.facility-table-view') || document.body;

    // Use event delegation for better performance
    container.addEventListener('click', this.handleClick.bind(this));
    container.addEventListener('keypress', this.handleKeypress.bind(this));
    container.addEventListener('input', this.handleInput.bind(this));
  }

  /**
 * Handle click events with delegation
 */
  handleClick(event) {
    const target = event.target.closest('[data-section]');
    if (!target) {
      return;
    }

    const section = target.getAttribute('data-section');

    if (target.classList.contains('comment-toggle')) {
      event.preventDefault();
      this.toggleCommentSection(section);
    } else if (target.classList.contains('comment-submit')) {
      event.preventDefault();
      this.submitComment(section);
    }
  }

  /**
 * Handle keypress events with delegation
 */
  handleKeypress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      const target = event.target;
      if (target.classList.contains('comment-input')) {
        event.preventDefault();
        const section = target.getAttribute('data-section');
        this.submitComment(section);
      }
    }
  }

  /**
 * Handle input events with delegation and debouncing
 */
  handleInput(event) {
    const target = event.target;
    if (target.classList.contains('comment-input')) {
      // Debounce character counter updates
      clearTimeout(this.inputTimeout);
      this.inputTimeout = setTimeout(() => {
        this.updateCharacterCounter(target);
      }, 150);
    }
  }

  /**
 * Toggle comment section visibility
 */
  toggleCommentSection(section = 'basic_info') {
    const commentSection = document.querySelector(`[data-section="${section}"].comment-section`);
    const toggleButton = document.querySelector(`[data-section="${section}"].comment-toggle`);

    if (!commentSection || !toggleButton) {
      return;
    }

    const isVisible = !commentSection.classList.contains('d-none');

    if (isVisible) {
      // Hide comments
      commentSection.classList.add('d-none');
      toggleButton.classList.remove('active');
      toggleButton.setAttribute('title', 'コメントを表示');
    } else {
      // Show comments
      commentSection.classList.remove('d-none');
      toggleButton.classList.add('active');
      toggleButton.setAttribute('title', 'コメントを非表示');

      // Set current section and load comments if not already loaded
      this.currentSection = section;
      if (!this.comments.has(section)) {
        this.loadComments();
      }
    }
  }

  /**
 * Load comments for the current section
 */
  async loadComments() {
    if (this.isLoading) {
      return;
    }

    try {
      this.isLoading = true;
      this.showLoadingState();

      // Load all comments at once to avoid N+1 queries
      if (this.comments.size === 0) {
        await this.loadAllComments();
      }

      this.renderComments();
      this.updateCommentCount();
    } catch (error) {
      console.error('Error loading comments:', error);
      this.showErrorState();
    } finally {
      this.isLoading = false;
      this.hideLoadingState();
    }
  }

  /**
 * Load all comments for the facility at once
 */
  async loadAllComments() {
    const endpoint = this.config.apiEndpoint
      .replace('{facilityId}', this.facilityId);

    const response = await get(endpoint);

    if (response.success) {
      // Store comments by section
      Object.entries(response.commentsBySection || {}).forEach(([section, comments]) => {
        this.comments.set(section, comments);
      });
    } else {
      throw new Error(response.message || 'Failed to load comments');
    }
  }

  /**
 * Submit a new comment
 */
  async submitComment(section = 'basic_info') {
    const commentInput = document.querySelector(`[data-section="${section}"].comment-input`);
    if (!commentInput) {
      return;
    }

    const commentText = commentInput.value.trim();
    if (!commentText) {
      showToast('コメントを入力してください。', 'warning');
      return;
    }

    if (commentText.length > this.config.maxCommentLength) {
      showToast(`コメントは${this.config.maxCommentLength}文字以内で入力してください。`, 'warning');
      return;
    }

    try {
      this.setSubmitButtonLoading(true, section);

      const endpoint = this.config.apiEndpoint
        .replace('{facilityId}', this.facilityId);

      const response = await post(endpoint, {
        section,
        comment: commentText
      });

      if (response.success) {
        commentInput.value = '';
        this.currentSection = section;
        this.loadComments(); // Reload to get the new comment
        showToast('コメントを投稿しました。', 'success');
      } else {
        throw new Error(response.message || 'Failed to submit comment');
      }
    } catch (error) {
      console.error('Error submitting comment:', error);
      showToast('コメントの投稿に失敗しました。', 'error');
    } finally {
      this.setSubmitButtonLoading(false, section);
    }
  }

  /**
 * Render comments in the comment list
 */
  renderComments() {
    const commentList = document.querySelector(`[data-section="${this.currentSection}"].comment-list`);
    if (!commentList) {
      return;
    }

    const comments = this.comments.get(this.currentSection) || [];

    if (comments.length === 0) {
      commentList.innerHTML = this.getEmptyStateHTML();
      return;
    }

    const commentsHTML = comments.map(comment => this.getCommentHTML(comment)).join('');
    commentList.innerHTML = commentsHTML;
  }

  /**
 * Update comment count display
 */
  updateCommentCount() {
    // Update count for all sections
    const sections = ['basic_info', 'service_info'];

    sections.forEach(section => {
      const countElement = document.querySelector(`[data-section="${section}"].comment-count`);
      if (!countElement) {
        return;
      }

      const comments = this.comments.get(section) || [];
      const count = comments.length;

      countElement.textContent = count;

      // Update button appearance based on comment count
      const toggleButton = document.querySelector(`[data-section="${section}"].comment-toggle`);
      if (toggleButton) {
        if (count > 0) {
          toggleButton.classList.add('has-comments');
        } else {
          toggleButton.classList.remove('has-comments');
        }
      }
    });
  }

  /**
 * Generate HTML for a single comment
 */
  getCommentHTML(comment) {
    const date = new Date(comment.created_at);
    const formattedDate = date.toLocaleDateString('ja-JP', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    return `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-meta">
                    <strong>${this.escapeHtml(comment.poster?.name || '匿名')}</strong>
                    <span class="text-muted ms-2">${formattedDate}</span>
                </div>
                <div class="comment-text">${this.escapeHtml(comment.comment)}</div>
            </div>
        `;
  }

  /**
 * Get empty state HTML
 */
  getEmptyStateHTML() {
    return `
            <div class="comment-empty">
                <i class="fas fa-comments"></i>
                <p class="mb-0">まだコメントがありません</p>
                <small class="text-muted">最初のコメントを投稿してみましょう</small>
            </div>
        `;
  }

  /**
 * Show loading state
 */
  showLoadingState() {
    const commentList = document.querySelector(`[data-section="${this.currentSection}"].comment-list`);
    if (commentList) {
      commentList.innerHTML = `
                <div class="comment-loading">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                    <span>コメントを読み込み中...</span>
                </div>
            `;
    }
  }

  /**
 * Hide loading state
 */
  hideLoadingState() {
    // Loading state is replaced by renderComments()
  }

  /**
 * Show error state
 */
  showErrorState() {
    const commentList = document.querySelector(`[data-section="${this.currentSection}"].comment-list`);
    if (commentList) {
      commentList.innerHTML = `
                <div class="comment-empty">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <p class="mb-0">コメントの読み込みに失敗しました</p>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="tableViewComments.loadComments()">
                        再試行
                    </button>
                </div>
            `;
    }
  }

  /**
 * Set submit button loading state
 */
  setSubmitButtonLoading(isLoading, section = 'basic_info') {
    const submitButton = document.querySelector(`[data-section="${section}"].comment-submit`);
    if (!submitButton) {
      return;
    }

    if (isLoading) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
    } else {
      submitButton.disabled = false;
      submitButton.innerHTML = '<i class="fas fa-paper-plane"></i>';
    }
  }

  /**
 * Update character counter
 */
  updateCharacterCounter(input) {
    const currentLength = input.value.length;
    const maxLength = this.config.maxCommentLength;

    // Find or create character counter
    let counter = input.parentElement.querySelector('.character-counter');
    if (!counter) {
      counter = document.createElement('small');
      counter.className = 'character-counter text-muted';
      input.parentElement.appendChild(counter);
    }

    counter.textContent = `${currentLength}/${maxLength}`;

    if (currentLength > maxLength) {
      counter.classList.add('text-danger');
      counter.classList.remove('text-muted');
    } else {
      counter.classList.add('text-muted');
      counter.classList.remove('text-danger');
    }
  }

  /**
 * Start periodic refresh of comments
 */
  startPeriodicRefresh() {
    setInterval(() => {
      const commentSection = document.querySelector('.facility-table-view .comment-section');
      if (commentSection && !commentSection.classList.contains('d-none')) {
        this.loadComments();
      }
    }, this.config.refreshInterval);
  }

  /**
 * Escape HTML to prevent XSS
 */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
 * Destroy the table view comments instance
 */
  destroy() {
    // Remove event listeners
    const toggleButton = document.querySelector('.table-view-comment-controls .comment-toggle');
    if (toggleButton) {
      toggleButton.removeEventListener('click', this.toggleCommentSection);
    }

    // Clear data
    this.comments.clear();
    this.facilityId = null;
  }
}

/**
 * Initialize table view comments functionality
 * @param {number} facilityId - The facility ID
 * @returns {TableViewComments|null} The initialized instance or null
 */
export function initializeTableViewComments(facilityId) {
  const tableViewComments = new TableViewComments();
  const initialized = tableViewComments.init(facilityId);

  return initialized ? tableViewComments : null;
}

/**
 * Export the class for direct usage
 */
export { TableViewComments };

/**
 * Auto-initialize if on facility pages with table view
 */
document.addEventListener('DOMContentLoaded', () => {
  // Only initialize on facility show pages
  if (window.location.pathname.match(/\/facilities\/\d+$/)) {
    const facilityIdMatch = window.location.pathname.match(/\/facilities\/(\d+)$/);
    if (facilityIdMatch) {
      const facilityId = facilityIdMatch[1];

      // Check if table view is active
      const tableView = document.querySelector('.facility-table-view');
      if (tableView) {
        window.tableViewComments = initializeTableViewComments(facilityId);
      }
    }
  }
});

