/**
 * Improved Table View Comments Module
 * Implements better separation of concerns and error handling
 */

import { CommentManager } from './comment-manager.js';
import { CommentUI } from './comment-ui.js';
import { showToast } from '../shared/utils.js';

/**
 * Configuration factory for table view comments
 */
class TableViewCommentsConfig {
    static create(facilityId) {
        return {
            facilityId,
            apiEndpoint: `/facilities/${facilityId}/comments`,
            refreshInterval: 30000,
            maxCommentLength: 500,
            retryAttempts: 3,
            retryDelay: 1000,
            cacheTimeout: 300000 // 5 minutes
        };
    }
}

/**
 * Error handler for table view comments
 */
class TableViewCommentsErrorHandler {
    static handle(error, context = '') {
        console.error(`TableViewComments Error ${context}:`, error);

        const errorMessages = {
            'network': 'ネットワークエラーが発生しました。',
            'validation': '入力内容に問題があります。',
            'permission': '権限がありません。',
            'default': 'エラーが発生しました。しばらく後にお試しください。'
        };

        const messageKey = this.categorizeError(error);
        showToast(errorMessages[messageKey] || errorMessages.default, 'error');
    }

    static categorizeError(error) {
        if (error.status === 403) {
            return 'permission';
        }
        if (error.status === 422) {
            return 'validation';
        }
        if (error.status >= 500) {
            return 'network';
        }
        return 'default';
    }
}

/**
 * State manager for table view comments
 */
class TableViewCommentsState {
    constructor() {
        this.comments = new Map();
        this.isLoading = false;
        this.currentSection = 'basic_info';
        this.lastRefresh = null;
    }

    setComments(section, comments) {
        this.comments.set(section, comments);
        this.lastRefresh = Date.now();
    }

    getComments(section) {
        return this.comments.get(section) || [];
    }

    clearComments() {
        this.comments.clear();
    }

    isStale(cacheTimeout) {
        return !this.lastRefresh || (Date.now() - this.lastRefresh) > cacheTimeout;
    }
}

/**
 * Improved Table View Comments Manager
 * Follows Single Responsibility Principle with better error handling
 */
class TableViewComments {
    constructor(config = {}) {
        this.config = null;
        this.commentManager = null;
        this.commentUI = null;
        this.state = new TableViewCommentsState();
        this.refreshTimer = null;
        this.retryCount = 0;
    }

    /**
   * Initialize with proper error handling and validation
   */
    async init(facilityId) {
        try {
            if (!facilityId || !this.validateFacilityId(facilityId)) {
                throw new Error('Invalid facility ID provided');
            }

            this.config = TableViewCommentsConfig.create(facilityId);
            await this.initializeComponents();
            await this.loadInitialData();
            this.startPeriodicRefresh();

            return true;
        } catch (error) {
            TableViewCommentsErrorHandler.handle(error, 'during initialization');
            return false;
        }
    }

    /**
   * Initialize components with dependency injection
   */
    async initializeComponents() {
        try {
            this.commentManager = new CommentManager(
                this.config.facilityId,
                this.config
            );

            this.commentUI = new CommentUI('.facility-table-view');
            this.commentUI.setupEventListeners(this.commentManager);

        } catch (error) {
            throw new Error(`Component initialization failed: ${error.message}`);
        }
    }

    /**
   * Load initial data with retry mechanism
   */
    async loadInitialData() {
        const maxRetries = this.config.retryAttempts;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                await this.loadAllComments();
                this.retryCount = 0;
                return;
            } catch (error) {
                if (attempt === maxRetries) {
                    throw error;
                }

                await this.delay(this.config.retryDelay * attempt);
            }
        }
    }

    /**
   * Load all comments with caching
   */
    async loadAllComments() {
        if (!this.state.isStale(this.config.cacheTimeout)) {
            return; // Use cached data
        }

        this.state.isLoading = true;

        try {
            const response = await this.fetchComments();

            if (response.success && response.commentsBySection) {
                Object.entries(response.commentsBySection).forEach(([section, comments]) => {
                    this.state.setComments(section, comments);
                });
            }
        } finally {
            this.state.isLoading = false;
        }
    }

    /**
   * Fetch comments from API
   */
    async fetchComments() {
        const response = await fetch(this.config.apiEndpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
   * Submit comment with validation and error handling
   */
    async submitComment(section, commentText) {
        try {
            this.validateCommentInput(section, commentText);

            const response = await this.postComment(section, commentText);

            if (response.success) {
                await this.loadAllComments(); // Refresh data
                showToast('コメントを投稿しました。', 'success');
            } else {
                throw new Error(response.message || 'Comment submission failed');
            }
        } catch (error) {
            TableViewCommentsErrorHandler.handle(error, 'during comment submission');
            throw error;
        }
    }

    /**
   * Post comment to API
   */
    async postComment(section, commentText) {
        const response = await fetch(this.config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                section,
                comment: commentText
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
   * Validate facility ID format
   */
    validateFacilityId(facilityId) {
        return facilityId &&
      (typeof facilityId === 'string' || typeof facilityId === 'number') &&
      String(facilityId).trim().length > 0;
    }

    /**
   * Validate comment input
   */
    validateCommentInput(section, commentText) {
        if (!section || typeof section !== 'string') {
            throw new Error('Invalid section provided');
        }

        if (!commentText || typeof commentText !== 'string') {
            throw new Error('Comment text is required');
        }

        const trimmedText = commentText.trim();
        if (trimmedText.length === 0) {
            throw new Error('Comment cannot be empty');
        }

        if (trimmedText.length > this.config.maxCommentLength) {
            throw new Error(`Comment exceeds maximum length of ${this.config.maxCommentLength} characters`);
        }
    }

    /**
   * Start periodic refresh with proper cleanup
   */
    startPeriodicRefresh() {
        this.stopPeriodicRefresh(); // Ensure no duplicate timers

        this.refreshTimer = setInterval(async () => {
            try {
                const commentSection = document.querySelector('.facility-table-view .comment-section');
                if (commentSection && !commentSection.classList.contains('d-none')) {
                    await this.loadAllComments();
                }
            } catch (error) {
                TableViewCommentsErrorHandler.handle(error, 'during periodic refresh');
            }
        }, this.config.refreshInterval);
    }

    /**
   * Stop periodic refresh
   */
    stopPeriodicRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
   * Utility delay function
   */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
   * Clean up resources
   */
    destroy() {
        this.stopPeriodicRefresh();
        this.state.clearComments();
        this.commentManager = null;
        this.commentUI = null;
    }
}

/**
 * Factory function for creating table view comments instance
 */
export function createTableViewComments(facilityId) {
    const instance = new TableViewComments();
    return instance.init(facilityId).then(success => success ? instance : null);
}

/**
 * Export classes for testing and direct usage
 */
export {
    TableViewComments,
    TableViewCommentsConfig,
    TableViewCommentsErrorHandler,
    TableViewCommentsState
};

/**
 * Auto-initialize with improved error handling
 */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const facilityIdMatch = window.location.pathname.match(/\/facilities\/(\d+)$/);
        if (facilityIdMatch && document.querySelector('.facility-table-view')) {
            const facilityId = facilityIdMatch[1];
            window.tableViewComments = await createTableViewComments(facilityId);
        }
    } catch (error) {
        console.error('Failed to auto-initialize table view comments:', error);
    }
});
