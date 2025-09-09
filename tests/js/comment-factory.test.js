/**
 * Tests for CommentComponentFactory
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { CommentComponentFactory } from '../../resources/js/modules/comment-factory.js';

describe('CommentComponentFactory', () => {
  let container;

  beforeEach(() => {
    // Create a test container
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  afterEach(() => {
    // Clean up
    document.body.removeChild(container);
  });

  describe('createToggleButton', () => {
    it('creates a basic toggle button with default options', () => {
      const button = CommentComponentFactory.createToggleButton('basic_info');

      expect(button.tagName).toBe('BUTTON');
      expect(button.classList.contains('comment-toggle')).toBe(true);
      expect(button.getAttribute('data-section')).toBe('basic_info');
      expect(button.querySelector('.fas.fa-comment')).toBeTruthy();
      expect(button.querySelector('.comment-count')).toBeTruthy();
    });

    it('handles invalid section parameter gracefully', () => {
      expect(() => {
        CommentComponentFactory.createToggleButton('');
      }).toThrow('Section must be a non-empty string');

      expect(() => {
        CommentComponentFactory.createToggleButton(null);
      }).toThrow('Section must be a non-empty string');

      expect(() => {
        CommentComponentFactory.createToggleButton('   ');
      }).toThrow('Section must be a non-empty string');
    });

    it('creates a toggle button with custom options', () => {
      const options = {
        variant: 'outline-secondary',
        size: 'lg',
        showText: false,
        initialCount: 5
      };

      const button = CommentComponentFactory.createToggleButton('service_info', options);

      expect(button.classList.contains('btn-outline-secondary')).toBe(true);
      expect(button.classList.contains('btn-lg')).toBe(true);
      expect(button.textContent.includes('コメント')).toBe(false);
      expect(button.querySelector('.comment-count').textContent).toBe('5');
    });

    it('includes text when showText is true', () => {
      const button = CommentComponentFactory.createToggleButton('basic_info', { showText: true });

      expect(button.textContent.includes('コメント')).toBe(true);
      expect(button.querySelector('.fas.fa-comment').classList.contains('me-1')).toBe(true);
    });

    it('excludes text when showText is false', () => {
      const button = CommentComponentFactory.createToggleButton('basic_info', { showText: false });

      expect(button.textContent.includes('コメント')).toBe(false);
      expect(button.querySelector('.fas.fa-comment').classList.contains('me-1')).toBe(false);
    });
  });

  describe('createCommentSection', () => {
    it('creates a complete comment section', () => {
      const section = CommentComponentFactory.createCommentSection('basic_info');

      expect(section.classList.contains('comment-section')).toBe(true);
      expect(section.classList.contains('d-none')).toBe(true);
      expect(section.getAttribute('data-section')).toBe('basic_info');
      expect(section.querySelector('hr')).toBeTruthy();
      expect(section.querySelector('.comment-form')).toBeTruthy();
      expect(section.querySelector('.comment-list')).toBeTruthy();
    });
  });

  describe('createCommentForm', () => {
    it('creates a comment form with input and submit button', () => {
      const form = CommentComponentFactory.createCommentForm('basic_info');

      expect(form.classList.contains('comment-form')).toBe(true);

      const input = form.querySelector('.comment-input');
      expect(input).toBeTruthy();
      expect(input.getAttribute('data-section')).toBe('basic_info');
      expect(input.placeholder).toBe('コメントを入力...');

      const button = form.querySelector('.comment-submit');
      expect(button).toBeTruthy();
      expect(button.getAttribute('data-section')).toBe('basic_info');
      expect(button.querySelector('.fas.fa-paper-plane')).toBeTruthy();
    });
  });

  describe('createCommentList', () => {
    it('creates a comment list container', () => {
      const list = CommentComponentFactory.createCommentList('basic_info');

      expect(list.classList.contains('comment-list')).toBe(true);
      expect(list.getAttribute('data-section')).toBe('basic_info');
    });
  });

  describe('createToggleButtonBuilder', () => {
    it('creates a builder with fluent interface', () => {
      const button = CommentComponentFactory.createToggleButtonBuilder('basic_info')
        .variant('outline-secondary')
        .size('lg')
        .showText(false)
        .initialCount(3)
        .build();

      expect(button.classList.contains('btn-outline-secondary')).toBe(true);
      expect(button.classList.contains('btn-lg')).toBe(true);
      expect(button.textContent.includes('コメント')).toBe(false);
      expect(button.querySelector('.comment-count').textContent).toBe('3');
    });

    it('allows method chaining', () => {
      const builder = CommentComponentFactory.createToggleButtonBuilder('test_section');

      expect(builder.variant('primary')).toBe(builder);
      expect(builder.size('sm')).toBe(builder);
      expect(builder.showText(true)).toBe(builder);
    });
  });

  describe('validateSection', () => {
    it('validates section parameters', () => {
      expect(() => {
        CommentComponentFactory.validateSection('valid_section');
      }).not.toThrow();

      expect(() => {
        CommentComponentFactory.validateSection('');
      }).toThrow();

      expect(() => {
        CommentComponentFactory.validateSection(null);
      }).toThrow();

      expect(() => {
        CommentComponentFactory.validateSection(undefined);
      }).toThrow();
    });
  });
});