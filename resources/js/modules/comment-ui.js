/**
 * Comment UI Manager - Handles comment user interface operations
 * Separated from data management for better maintainability
 */

export class CommentUI {
  constructor(containerSelector = '.facility-table-view') {
    try {
      this.container = document.querySelector(containerSelector);
      this.currentSection = 'basic_info';
    } catch (error) {
      console.error('CommentUI constructor error:', error);
      this.container = null;
      this.currentSection = 'basic_info';
    }
  }

  /**
 * Setup event listeners for comment UI
 */
  setupEventListeners(commentManager) {
    this.setupToggleListeners();
    this.setupSubmitListeners(commentManager);
    this.setupInputListeners();
  }

  /**
 * Setup comment toggle button listeners
 */
  setupToggleListeners() {
    if (!this.container) {
      console.warn('CommentUI: Container not found, skipping toggle listeners setup');
      return;
    }

    const toggleButtons = this.container.querySelectorAll('.comment-toggle');

    toggleButtons.forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const section = button.dataset.section;
        this.toggleCommentSection(section);
      });
    });
  }

  /**
 * Setup comment submit button listeners
 */
  setupSubmitListeners(commentManager) {
    if (!this.container) {
      console.warn('CommentUI: Container not found, skipping submit listeners setup');
      return;
    }

    const submitButtons = this.container.querySelectorAll('.comment-submit');

    submitButtons.forEach(button => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();
        const section = button.dataset.section;
        await this.handleCommentSubmit(section, commentManager);
      });
    });
  }

  /**
 * Setup comment input listeners
 */
  setupInputListeners() {
    if (!this.container) {
      console.warn('CommentUI: Container not found, skipping input listeners setup');
      return;
    }

    const commentInputs = this.container.querySelectorAll('.comment-input');

    commentInputs.forEach(input => {
      // Enter key submission
      input.addEventListener('keypress', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          const section = input.dataset.section;
          const submitButton = this.container.querySelector(`.comment-submit[data-section="${section}"]`);
          if (submitButton) {
            submitButton.click();
          }
        }
      });

      // Character counter
      input.addEventListener('input', (event) => {
        this.updateCharacterCounter(event.target);
      });
    });
  }

  /**
 * Toggle comment section visibility
 */
  toggleCommentSection(section) {
    if (!this.container) {
      return;
    }

    const commentSection = this.container.querySelector(`.comment-section[data-section="${section}"]`);
    const toggleButton = this.container.querySelector(`.comment-toggle[data-section="${section}"]`);

    if (!commentSection || !toggleButton) {
      return;
    }

    const isVisible = !commentSection.classList.contains('d-none');

    if (isVisible) {
      this.hideCommentSection(commentSection, toggleButton);
    } else {
      this.showCommentSection(commentSection, toggleButton, section);
    }
  }

  /**
 * Hide comment section
 */
  hideCommentSection(commentSection, toggleButton) {
    commentSection.classList.add('d-none');
    toggleButton.classList.remove('active');
    toggleButton.setAttribute('title', 'コメントを表示');
  }

  /**
 * Show comment section
 */
  showCommentSection(commentSection, toggleButton, section) {
    commentSection.classList.remove('d-none');
    toggleButton.classList.add('active');
    toggleButton.setAttribute('title', 'コメントを非表示');
    this.currentSection = section;
  }

  /**
 * Handle comment submission
 */
  async handleCommentSubmit(section, commentManager) {
    if (!this.container) {
      return;
    }

    const input = this.container.querySelector(`.comment-input[data-section="${section}"]`);
    const submitButton = this.container.querySelector(`.comment-submit[data-section="${section}"]`);

    if (!input || !submitButton) {
      return;
    }

    const commentText = input.value.trim();

    try {
      this.setSubmitButtonLoading(submitButton, true);

      await commentManager.submitComment(section, commentText);

      input.value = '';
      this.renderComments(section, commentManager.getCommentsForSection(section));
      this.updateCommentCount(section, commentManager.getCommentCount(section));

      this.showSuccessMessage('コメントを投稿しました。');
    } catch (error) {
      this.showErrorMessage(error.message || 'コメントの投稿に失敗しました。');
    } finally {
      this.setSubmitButtonLoading(submitButton, false);
    }
  }

  /**
 * Render comments for a section
 */
  renderComments(section, comments) {
    if (!this.container) {
      return;
    }

    const commentList = this.container.querySelector(`.comment-list[data-section="${section}"]`);
    if (!commentList) {
      return;
    }

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
  updateCommentCount(section, count) {
    if (!this.container) {
      return;
    }

    const countElements = this.container.querySelectorAll(`.comment-count[data-section="${section}"]`);

    countElements.forEach(element => {
      element.textContent = count;

      // Update button appearance based on comment count
      const toggleButton = element.closest('.comment-toggle');
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
          <strong>${this.escapeHtml(comment.user?.name || '匿名')}</strong>
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
 * Set submit button loading state
 */
  setSubmitButtonLoading(button, isLoading) {
    if (isLoading) {
      button.disabled = true;
      button.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
    } else {
      button.disabled = false;
      button.innerHTML = '<i class="fas fa-paper-plane"></i>';
    }
  }

  /**
 * Update character counter
 */
  updateCharacterCounter(input) {
    const currentLength = input.value.length;
    const maxLength = 500; // Should come from config

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
 * Show success message
 */
  showSuccessMessage(message) {
    // Implement toast notification or other success feedback
    console.log('Success:', message);
  }

  /**
 * Show error message
 */
  showErrorMessage(message) {
    // Implement toast notification or other error feedback
    console.error('Error:', message);
  }

  /**
 * Escape HTML to prevent XSS
 */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}
