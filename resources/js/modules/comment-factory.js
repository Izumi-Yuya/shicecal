/**
 * Factory for creating comment-related UI components
 * Implements Factory pattern with Builder pattern for consistent comment component creation
 */

/**
 * Builder class for comment toggle buttons
 */
class CommentToggleButtonBuilder {
    constructor(section) {
        this.section = section;
        this.config = {
            variant: 'outline-primary',
            size: 'sm',
            showText: true,
            initialCount: 0,
            tooltip: 'コメントを表示/非表示'
        };
    }

    variant(variant) {
        this.config.variant = variant;
        return this;
    }

    size(size) {
        this.config.size = size;
        return this;
    }

    showText(show) {
        this.config.showText = show;
        return this;
    }

    initialCount(count) {
        this.config.initialCount = count;
        return this;
    }

    tooltip(tooltip) {
        this.config.tooltip = tooltip;
        return this;
    }

    build() {
        return CommentComponentFactory.createToggleButton(this.section, this.config);
    }
}

export class CommentComponentFactory {
    /**
   * Create a comment toggle button
   */
    static createToggleButton(section, options = {}) {
        this.validateSection(section);

        const defaults = {
            variant: 'outline-primary',
            size: 'sm',
            showText: true,
            initialCount: 0,
            tooltip: 'コメントを表示/非表示'
        };

        const config = { ...defaults, ...options };

        const button = document.createElement('button');
        button.className = `btn btn-${config.variant} btn-${config.size} comment-toggle`;
        button.setAttribute('data-section', section);
        button.setAttribute('data-bs-toggle', 'tooltip');
        button.setAttribute('title', config.tooltip);

        const icon = document.createElement('i');
        icon.className = `fas fa-comment${config.showText ? ' me-1' : ''}`;
        button.appendChild(icon);

        if (config.showText) {
            button.appendChild(document.createTextNode('コメント'));
        }

        const badge = document.createElement('span');
        badge.className = `badge bg-primary${config.showText ? ' ms-1' : ''} comment-count`;
        badge.setAttribute('data-section', section);
        badge.textContent = config.initialCount;
        button.appendChild(badge);

        return button;
    }

    /**
   * Create a comment section container
   */
    static createCommentSection(section) {
        const container = document.createElement('div');
        container.className = 'comment-section mt-3 d-none';
        container.setAttribute('data-section', section);

        const hr = document.createElement('hr');
        container.appendChild(hr);

        const form = this.createCommentForm(section);
        container.appendChild(form);

        const list = this.createCommentList(section);
        container.appendChild(list);

        return container;
    }

    /**
   * Create comment form
   */
    static createCommentForm(section) {
        const form = document.createElement('div');
        form.className = 'comment-form mb-3';

        const inputGroup = document.createElement('div');
        inputGroup.className = 'input-group';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control comment-input';
        input.placeholder = 'コメントを入力...';
        input.setAttribute('data-section', section);

        const button = document.createElement('button');
        button.className = 'btn btn-primary comment-submit';
        button.setAttribute('data-section', section);
        button.innerHTML = '<i class="fas fa-paper-plane"></i>';

        inputGroup.appendChild(input);
        inputGroup.appendChild(button);
        form.appendChild(inputGroup);

        return form;
    }

    /**
   * Create comment list container
   */
    static createCommentList(section) {
        const list = document.createElement('div');
        list.className = 'comment-list';
        list.setAttribute('data-section', section);
        return list;
    }

    /**
   * Create a builder for comment toggle buttons
   * @param {string} section - The section identifier
   * @returns {CommentToggleButtonBuilder} Builder instance
   */
    static createToggleButtonBuilder(section) {
        return new CommentToggleButtonBuilder(section);
    }

    /**
   * Validate section parameter
   * @param {string} section - The section to validate
   * @throws {Error} If section is invalid
   */
    static validateSection(section) {
        if (!section || typeof section !== 'string' || section.trim() === '') {
            throw new Error('Section must be a non-empty string');
        }
    }
}
