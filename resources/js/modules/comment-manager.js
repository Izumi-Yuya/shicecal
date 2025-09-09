/**
 * Comment Manager - Handles comment data operations
 * Separated from UI concerns for better maintainability
 */

import { post, get } from '../shared/api.js';

export class CommentManager {
  constructor(facilityId, config = {}) {
    this.facilityId = facilityId;
    this.config = {
      apiEndpoint: '/facilities/{facilityId}/comments',
      maxCommentLength: 500,
      ...config
    };
    this.comments = new Map();
  }

  /**
   * Load all comments for the facility
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
      return this.comments;
    } else {
      throw new Error(response.message || 'Failed to load comments');
    }
  }

  /**
   * Get comments for a specific section
   */
  getCommentsForSection(section) {
    return this.comments.get(section) || [];
  }

  /**
   * Get comment count for a section
   */
  getCommentCount(section) {
    const comments = this.getCommentsForSection(section);
    return comments.length;
  }

  /**
   * Submit a new comment
   */
  async submitComment(section, commentText) {
    this.validateComment(commentText);

    const endpoint = this.config.apiEndpoint
      .replace('{facilityId}', this.facilityId);

    const response = await post(endpoint, {
      section: section,
      comment: commentText
    });

    if (response.success) {
      // Update local cache
      await this.loadAllComments();
      return response;
    } else {
      throw new Error(response.message || 'Failed to submit comment');
    }
  }

  /**
   * Validate comment content
   */
  validateComment(commentText) {
    if (!commentText || commentText.trim().length === 0) {
      throw new Error('コメントを入力してください。');
    }

    if (commentText.length > this.config.maxCommentLength) {
      throw new Error(`コメントは${this.config.maxCommentLength}文字以内で入力してください。`);
    }
  }

  /**
   * Clear cached comments
   */
  clearCache() {
    this.comments.clear();
  }
}