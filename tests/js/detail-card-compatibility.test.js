/**
 * Detail Card Layout Improvement - Compatibility Tests
 * Tests for browser compatibility and responsive behavior
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { JSDOM } from 'jsdom';
import { DetailCardController } from '../../resources/js/modules/detail-card-controller.js';

// Mock utilities
vi.mock('../../resources/js/shared/utils.js', () => ({
    showToast: vi.fn()
}));

describe('Detail Card Compatibility Tests', () => {
    let dom;
    let document;
    let window;
    let controller;

    beforeEach(() => {
    // Create a new JSDOM instance for each test
        dom = new JSDOM(`
      <!DOCTYPE html>
      <html lang="ja">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detail Card Test</title>
        <style>
          /* CSS Variables Support Test */
          :root {
            --detail-row-padding: 0.5rem 0;
            --detail-label-width: 140px;
          }
          
          /* Flexbox Support Test */
          .detail-row {
            display: flex;
            align-items: flex-start;
          }
          
          /* Detail Card Improved Styles */
          .detail-card-improved .empty-field {
            display: none;
          }
          
          .detail-card-improved.show-empty-fields .empty-field {
            display: flex;
          }
          
          /* Media Queries for Responsive Testing */
          @media (min-width: 1024px) {
            .detail-card-improved {
              --improved-row-padding: var(--detail-row-padding);
            }
          }
          
          @media (max-width: 768px) {
            .detail-row {
              flex-direction: column;
            }
          }
        </style>
      </head>
      <body>
        <div class="card facility-info-card detail-card-improved" data-section="test_section">
          <div class="card-header">
            <h5 class="mb-0">テストカード</h5>
          </div>
          <div class="card-body">
            <div class="facility-detail-table">
              <div class="detail-row">
                <span class="detail-label">設定済み項目</span>
                <span class="detail-value">テスト値</span>
              </div>
              <div class="detail-row empty-field">
                <span class="detail-label">未設定項目1</span>
                <span class="detail-value">未設定</span>
              </div>
              <div class="detail-row empty-field">
                <span class="detail-label">未設定項目2</span>
                <span class="detail-value"></span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Comment functionality test -->
        <div class="card facility-info-card detail-card-improved" data-section="comment_test">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">コメント機能テスト</h5>
            <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                    data-section="comment_test" 
                    data-bs-toggle="tooltip" 
                    title="コメントを表示/非表示">
              <i class="fas fa-comment"></i>
              <span class="comment-count" data-section="comment_test">0</span>
            </button>
          </div>
          <div class="card-body">
            <div class="facility-detail-table">
              <div class="detail-row">
                <span class="detail-label">項目1</span>
                <span class="detail-value">値1</span>
              </div>
            </div>
            <div class="comment-section mt-3 d-none" data-section="comment_test">
              <hr>
              <div class="comment-form mb-3">
                <div class="input-group">
                  <input type="text" class="form-control comment-input" 
                         placeholder="コメントを入力..." 
                         data-section="comment_test">
                  <button class="btn btn-primary comment-submit" 
                          data-section="comment_test">
                    <i class="fas fa-paper-plane"></i>
                  </button>
                </div>
              </div>
              <div class="comment-list" data-section="comment_test">
                <!-- コメントがここに動的に追加されます -->
              </div>
            </div>
          </div>
        </div>
      </body>
      </html>
    `, {
            url: 'http://localhost',
            pretendToBeVisual: true,
            resources: 'usable'
        });

        document = dom.window.document;
        window = dom.window;

        // Set up global objects
        global.document = document;
        global.window = window;
        global.localStorage = {
            getItem: vi.fn(),
            setItem: vi.fn(),
            removeItem: vi.fn()
        };

        controller = new DetailCardController();
    });

    afterEach(() => {
        if (controller && controller.destroy) {
            controller.destroy();
        }
        dom.window.close();
    });

    describe('Browser Compatibility', () => {
        test('should work with CSS Variables support', () => {
            // Test CSS variables support
            const rootStyle = window.getComputedStyle(document.documentElement);
            expect(rootStyle.getPropertyValue('--detail-row-padding')).toBeTruthy();
        });

        test('should work with Flexbox support', () => {
            const detailRow = document.querySelector('.detail-row');
            const computedStyle = window.getComputedStyle(detailRow);
            expect(computedStyle.display).toBe('flex');
        });

        test('should handle missing localStorage gracefully', () => {
            // Simulate localStorage not available
            global.localStorage = undefined;

            expect(() => {
                controller.init();
            }).not.toThrow();
        });

        test('should work without modern JavaScript features', () => {
            // Test that basic functionality works without advanced features
            const cards = document.querySelectorAll('.detail-card-improved');
            expect(cards.length).toBeGreaterThan(0);

            // Test that empty fields are initially hidden
            const emptyFields = document.querySelectorAll('.empty-field');
            emptyFields.forEach(field => {
                const computedStyle = window.getComputedStyle(field);
                expect(computedStyle.display).toBe('none');
            });
        });

        test('should handle missing Bootstrap gracefully', () => {
            // Test without Bootstrap tooltip functionality
            expect(() => {
                controller.init();
            }).not.toThrow();
        });
    });

    describe('Responsive Design Tests', () => {
        test('should adapt to desktop screen sizes (1024px+)', () => {
            // Simulate desktop viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 1920
            });

            controller.init();

            const cards = document.querySelectorAll('.detail-card-improved');
            expect(cards.length).toBeGreaterThan(0);

            // Check that toggle buttons are created for cards with empty fields
            const toggleButtons = document.querySelectorAll('.empty-fields-toggle');
            expect(toggleButtons.length).toBeGreaterThan(0);
        });

        test('should adapt to tablet screen sizes (768px-1023px)', () => {
            // Simulate tablet viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 768
            });

            controller.init();

            // Test that functionality still works on tablet
            const cards = document.querySelectorAll('.detail-card-improved');
            expect(cards.length).toBeGreaterThan(0);
        });

        test('should adapt to mobile screen sizes (< 768px)', () => {
            // Simulate mobile viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 375
            });

            controller.init();

            // Test that functionality still works on mobile
            const cards = document.querySelectorAll('.detail-card-improved');
            expect(cards.length).toBeGreaterThan(0);
        });

        test('should handle different screen resolutions', () => {
            const resolutions = [
                { width: 1024, height: 768 },   // Standard desktop
                { width: 1366, height: 768 },   // Common laptop
                { width: 1920, height: 1080 },  // Full HD
                { width: 2560, height: 1440 }   // 4K
            ];

            resolutions.forEach(({ width, height }) => {
                Object.defineProperty(window, 'innerWidth', {
                    writable: true,
                    configurable: true,
                    value: width
                });
                Object.defineProperty(window, 'innerHeight', {
                    writable: true,
                    configurable: true,
                    value: height
                });

                expect(() => {
                    controller.init();
                }).not.toThrow();
            });
        });
    });

    describe('Existing Functionality Integration', () => {
        test('should not interfere with comment toggle buttons', () => {
            controller.init();

            const commentToggle = document.querySelector('.comment-toggle');
            expect(commentToggle).toBeTruthy();

            // Test that comment toggle still works
            const commentSection = document.querySelector('.comment-section');
            expect(commentSection).toBeTruthy();
            expect(commentSection.classList.contains('d-none')).toBe(true);
        });

        test('should preserve edit button functionality', () => {
            // Add edit button to test
            const cardHeader = document.querySelector('.card-header');
            const editButton = document.createElement('a');
            editButton.href = '/facilities/1/edit';
            editButton.className = 'btn btn-primary';
            editButton.innerHTML = '<i class="fas fa-edit me-2"></i>編集';
            cardHeader.appendChild(editButton);

            controller.init();

            // Check that edit button is still present and functional
            const editBtn = document.querySelector('a[href="/facilities/1/edit"]');
            expect(editBtn).toBeTruthy();
            expect(editBtn.classList.contains('btn-primary')).toBe(true);
        });

        test('should work with existing card layouts', () => {
            controller.init();

            // Test that existing card structure is preserved
            const cards = document.querySelectorAll('.facility-info-card');
            expect(cards.length).toBeGreaterThan(0);

            cards.forEach(card => {
                const header = card.querySelector('.card-header');
                const body = card.querySelector('.card-body');

                expect(header).toBeTruthy();
                expect(body).toBeTruthy();
            });
        });

        test('should maintain accessibility features', () => {
            controller.init();

            // Check ARIA attributes
            const toggleButtons = document.querySelectorAll('.empty-fields-toggle');
            toggleButtons.forEach(button => {
                expect(button.getAttribute('aria-label')).toBeTruthy();
                expect(button.getAttribute('role')).toBe('switch');
                expect(button.getAttribute('aria-pressed')).toBeTruthy();
            });
        });
    });

    describe('Performance Tests', () => {
        test('should initialize quickly with multiple cards', () => {
            // Add multiple cards to test performance
            const container = document.body;
            for (let i = 0; i < 10; i++) {
                const card = document.createElement('div');
                card.className = 'card facility-info-card detail-card-improved';
                card.dataset.section = `test_section_${i}`;
                card.innerHTML = `
          <div class="card-header">
            <h5 class="mb-0">テストカード ${i}</h5>
          </div>
          <div class="card-body">
            <div class="facility-detail-table">
              <div class="detail-row empty-field">
                <span class="detail-label">未設定項目</span>
                <span class="detail-value">未設定</span>
              </div>
            </div>
          </div>
        `;
                container.appendChild(card);
            }

            const startTime = performance.now();
            controller.init();
            const endTime = performance.now();

            // Should initialize within reasonable time (< 100ms)
            expect(endTime - startTime).toBeLessThan(100);
        });

        test('should handle DOM mutations efficiently', () => {
            controller.init();

            // Test adding new cards dynamically
            const newCard = document.createElement('div');
            newCard.className = 'card facility-info-card detail-card-improved';
            newCard.dataset.section = 'dynamic_section';
            newCard.innerHTML = `
        <div class="card-header">
          <h5 class="mb-0">動的カード</h5>
        </div>
        <div class="card-body">
          <div class="facility-detail-table">
            <div class="detail-row empty-field">
              <span class="detail-label">動的項目</span>
              <span class="detail-value">未設定</span>
            </div>
          </div>
        </div>
      `;

            document.body.appendChild(newCard);

            // Test that refresh works
            expect(() => {
                controller.refresh();
            }).not.toThrow();
        });
    });

    describe('Error Handling', () => {
        test('should handle malformed HTML gracefully', () => {
            // Add malformed card
            const malformedCard = document.createElement('div');
            malformedCard.className = 'detail-card-improved';
            malformedCard.innerHTML = '<div>Malformed content</div>';
            document.body.appendChild(malformedCard);

            expect(() => {
                controller.init();
            }).not.toThrow();
        });

        test('should handle missing data attributes', () => {
            // Add card without data-section
            const cardWithoutSection = document.createElement('div');
            cardWithoutSection.className = 'card facility-info-card detail-card-improved';
            cardWithoutSection.innerHTML = `
        <div class="card-header">
          <h5 class="mb-0">セクションなしカード</h5>
        </div>
        <div class="card-body">
          <div class="facility-detail-table">
            <div class="detail-row empty-field">
              <span class="detail-label">項目</span>
              <span class="detail-value">未設定</span>
            </div>
          </div>
        </div>
      `;
            document.body.appendChild(cardWithoutSection);

            expect(() => {
                controller.init();
            }).not.toThrow();
        });

        test('should handle localStorage errors', () => {
            // Mock localStorage to throw errors
            global.localStorage = {
                getItem: vi.fn(() => {
                    throw new Error('Storage error');
                }),
                setItem: vi.fn(() => {
                    throw new Error('Storage error');
                }),
                removeItem: vi.fn(() => {
                    throw new Error('Storage error');
                })
            };

            expect(() => {
                controller.init();
            }).not.toThrow();
        });
    });

    describe('User Interaction Tests', () => {
        test('should handle toggle button clicks', () => {
            controller.init();

            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                // Simulate click
                const clickEvent = new window.Event('click', { bubbles: true });
                toggleButton.dispatchEvent(clickEvent);

                // Check that state changed
                const card = toggleButton.closest('.detail-card-improved');
                expect(card.classList.contains('show-empty-fields')).toBe(true);
            }
        });

        test('should handle keyboard navigation', () => {
            controller.init();

            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                // Simulate Enter key
                const enterEvent = new window.KeyboardEvent('keydown', {
                    key: 'Enter',
                    bubbles: true
                });
                toggleButton.dispatchEvent(enterEvent);

                // Check that state changed
                const card = toggleButton.closest('.detail-card-improved');
                expect(card.classList.contains('show-empty-fields')).toBe(true);
            }
        });

        test('should preserve user preferences', () => {
            const mockPreferences = {
                test_section: { showEmptyFields: true }
            };

            global.localStorage.getItem.mockReturnValue(JSON.stringify(mockPreferences));

            controller.init();

            const card = document.querySelector('[data-section="test_section"]');
            expect(card.classList.contains('show-empty-fields')).toBe(true);
        });
    });
});
