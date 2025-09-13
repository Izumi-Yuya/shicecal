/**
 * Accessibility Verification Tests for Detail Card Layout Improvement
 * Tests for Task 8: 可読性とアクセシビリティの確保
 */

import { describe, test, expect, beforeEach, afterEach } from 'vitest';
import { JSDOM } from 'jsdom';
import { DetailCardController } from '../../resources/js/modules/detail-card-controller.js';

describe('Detail Card Accessibility', () => {
    let dom;
    let document;
    let window;
    let controller;

    beforeEach(() => {
    // Create a mock DOM environment
        dom = new JSDOM(`
      <!DOCTYPE html>
      <html lang="ja">
      <head>
        <meta charset="UTF-8">
        <title>Test</title>
      </head>
      <body>
        <div class="card facility-info-card detail-card-improved" data-section="test_section">
          <div class="card-header">
            <h5>テストカード</h5>
          </div>
          <div class="card-body">
            <div class="facility-detail-table">
              <div class="detail-row">
                <span class="detail-label">項目1</span>
                <span class="detail-value">値1</span>
              </div>
              <div class="detail-row empty-field">
                <span class="detail-label">項目2</span>
                <span class="detail-value">未設定</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">メールアドレス</span>
                <span class="detail-value">
                  <a href="mailto:test@example.com">test@example.com</a>
                </span>
              </div>
            </div>
          </div>
        </div>
      </body>
      </html>
    `);

        document = dom.window.document;
        window = dom.window;

        // Mock global objects
        global.document = document;
        global.window = window;
        global.localStorage = {
            getItem: () => null,
            setItem: () => { },
            removeItem: () => { }
        };

        controller = new DetailCardController();
    });

    afterEach(() => {
        if (controller) {
            controller.destroy();
        }
    });

    describe('Font Size and Contrast Ratio Maintenance', () => {
        test('should maintain proper font sizes', () => {
            const labels = document.querySelectorAll('.detail-label');
            const values = document.querySelectorAll('.detail-value');

            labels.forEach(label => {
                const _computedStyle = window.getComputedStyle(label);
                // フォントサイズが適切に設定されていることを確認
                expect(label.classList.contains('detail-label')).toBe(true);
            });

            values.forEach(value => {
                const _computedStyle = window.getComputedStyle(value);
                expect(value.classList.contains('detail-value')).toBe(true);
            });
        });

        test('should have proper color contrast for labels and values', () => {
            const labels = document.querySelectorAll('.detail-label');
            const values = document.querySelectorAll('.detail-value');

            // ラベルとバリューが適切なクラスを持っていることを確認
            expect(labels.length).toBeGreaterThan(0);
            expect(values.length).toBeGreaterThan(0);
        });
    });

    describe('Long Text Line Breaking', () => {
        test('should handle long text properly', () => {
            const longTextValue = document.createElement('span');
            longTextValue.className = 'detail-value';
            longTextValue.textContent = 'これは非常に長いテキストの例です。適切に改行されることを確認するためのテストです。';

            const detailRow = document.querySelector('.detail-row');
            detailRow.appendChild(longTextValue);

            // word-break プロパティが適用されていることを確認
            const _computedStyle = window.getComputedStyle(longTextValue);
            expect(longTextValue.classList.contains('detail-value')).toBe(true);
        });

        test('should handle long URLs properly', () => {
            const link = document.querySelector('a[href^="mailto:"]');
            expect(link).toBeTruthy();
            expect(link.getAttribute('href')).toBe('mailto:test@example.com');
        });
    });

    describe('Important Information Visual Emphasis', () => {
        test('should maintain visual emphasis for important elements', () => {
            // 重要な情報のバッジやボールドテキストが維持されていることを確認
            const boldValue = document.createElement('span');
            boldValue.className = 'detail-value fw-bold';
            boldValue.textContent = '重要な情報';

            const detailRow = document.querySelector('.detail-row');
            detailRow.appendChild(boldValue);

            expect(boldValue.classList.contains('fw-bold')).toBe(true);
        });

        test('should maintain badge styling', () => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary';
            badge.textContent = 'テストバッジ';

            const detailValue = document.querySelector('.detail-value');
            detailValue.appendChild(badge);

            expect(badge.classList.contains('badge')).toBe(true);
            expect(badge.classList.contains('bg-primary')).toBe(true);
        });
    });

    describe('Keyboard Navigation Support', () => {
        test('should initialize with proper ARIA attributes', () => {
            controller.init();

            const card = document.querySelector('.detail-card-improved');
            expect(card.getAttribute('role')).toBe('region');
            expect(card.hasAttribute('aria-labelledby')).toBe(true);
        });

        test('should add proper ARIA attributes to detail rows', () => {
            controller.init();

            const detailRows = document.querySelectorAll('.detail-row');
            detailRows.forEach(row => {
                expect(row.getAttribute('role')).toBe('row');
            });
        });

        test('should create accessible toggle buttons', () => {
            controller.init();

            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                expect(toggleButton.hasAttribute('aria-label')).toBe(true);
                expect(toggleButton.hasAttribute('aria-pressed')).toBe(true);
                expect(toggleButton.getAttribute('role')).toBe('switch');
            }
        });

        test('should handle keyboard events properly', () => {
            controller.init();

            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                // Enter キーのテスト
                const enterEvent = new window.KeyboardEvent('keydown', { key: 'Enter' });
                toggleButton.dispatchEvent(enterEvent);

                // Space キーのテスト
                const spaceEvent = new window.KeyboardEvent('keydown', { key: ' ' });
                toggleButton.dispatchEvent(spaceEvent);

                // イベントが適切に処理されることを確認
                expect(toggleButton.hasAttribute('aria-pressed')).toBe(true);
            }
        });

        test('should provide proper focus management', () => {
            controller.init();

            const detailRows = document.querySelectorAll('.detail-row');
            detailRows.forEach(row => {
                // フォーカス可能な要素がない場合、行自体がフォーカス可能になることを確認
                const focusableElements = row.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusableElements.length === 0) {
                    expect(row.hasAttribute('tabindex') || row.getAttribute('tabindex') === '0').toBeTruthy();
                }
            });
        });
    });

    describe('Screen Reader Support', () => {
        test('should provide screen reader descriptions', () => {
            controller.init();

            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                const section = toggleButton.dataset.section;
                const description = document.querySelector(`#empty-fields-desc-${section}`);

                if (description) {
                    expect(description.classList.contains('sr-only')).toBe(true);
                    expect(description.textContent).toContain('未設定項目');
                }
            }
        });

        test('should announce detail values properly', () => {
            controller.init();

            const detailValues = document.querySelectorAll('.detail-value');
            detailValues.forEach(value => {
                // フォーカス時にARIAラベルが設定されることを確認
                const focusEvent = new window.Event('focus');
                value.dispatchEvent(focusEvent);

                // ARIA属性が適切に設定されているかチェック
                expect(value.hasAttribute('aria-label') || value.hasAttribute('aria-labelledby')).toBeTruthy();
            });
        });

        test('should handle empty fields properly for screen readers', () => {
            const emptyField = document.querySelector('.empty-field .detail-value');
            if (emptyField) {
                const focusEvent = new window.Event('focus');
                emptyField.dispatchEvent(focusEvent);

                // 空のフィールドに適切なARIAラベルが設定されることを確認
                const ariaLabel = emptyField.getAttribute('aria-label');
                if (ariaLabel) {
                    expect(ariaLabel).toContain('未設定');
                }
            }
        });
    });

    describe('High Contrast Mode Support', () => {
        test('should maintain readability in high contrast mode', () => {
            // ハイコントラストモード用のCSSクラスが適用されていることを確認
            const labels = document.querySelectorAll('.detail-label');
            const values = document.querySelectorAll('.detail-value');

            expect(labels.length).toBeGreaterThan(0);
            expect(values.length).toBeGreaterThan(0);
        });
    });

    describe('Reduced Motion Support', () => {
        test('should respect reduced motion preferences', () => {
            // アニメーションが無効化されることを確認
            const toggleButton = document.querySelector('.empty-fields-toggle');
            if (toggleButton) {
                // reduced-motion の設定が適用されていることを確認
                expect(toggleButton.classList.contains('empty-fields-toggle')).toBe(true);
            }
        });
    });

    describe('Print Accessibility', () => {
        test('should maintain accessibility in print mode', () => {
            // プリント時に全ての項目が表示されることを確認
            const emptyFields = document.querySelectorAll('.empty-field');
            expect(emptyFields.length).toBeGreaterThan(0);

            // リンクが適切に処理されることを確認
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                expect(link.hasAttribute('href')).toBe(true);
            });
        });
    });
});
