/**
 * Detail Card Controller Tests
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { DetailCardController, initializeDetailCardController } from '../../resources/js/modules/detail-card-controller.js';

// Mock showToast utility
vi.mock('../../resources/js/shared/utils.js', () => ({
    showToast: vi.fn()
}));

describe('DetailCardController', () => {
    let controller;

    beforeEach(() => {
    // Set up DOM structure
        document.body.innerHTML = `
      <div class="card facility-info-card detail-card-improved" data-section="facility_basic">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">基本情報</h5>
        </div>
        <div class="card-body">
          <div class="facility-detail-table">
            <div class="detail-row">
              <span class="detail-label">会社名</span>
              <span class="detail-value">テスト会社</span>
            </div>
            <div class="detail-row empty-field">
              <span class="detail-label">事業所コード</span>
              <span class="detail-value">未設定</span>
            </div>
            <div class="detail-row empty-field">
              <span class="detail-label">電話番号</span>
              <span class="detail-value">未設定</span>
            </div>
          </div>
        </div>
      </div>
    `;

        // Mock localStorage
        global.localStorage = {
            getItem: vi.fn(),
            setItem: vi.fn(),
            removeItem: vi.fn()
        };

        controller = new DetailCardController();
    });

    afterEach(() => {
    // Clean up localStorage mock
        delete global.localStorage;
    });

    describe('Initialization', () => {
        it('should initialize successfully with detail cards present', () => {
            const result = controller.init();
            expect(result).toBe(true);
            expect(controller.isInitialized).toBe(true);
        });

        it('should return false when no detail cards are found', () => {
            // Remove all detail cards
            const cards = document.querySelectorAll('.detail-card-improved');
            cards.forEach(card => card.remove());

            const result = controller.init();
            expect(result).toBe(false);
            expect(controller.isInitialized).toBe(false);
        });

        it('should find detail cards correctly', () => {
            controller.findDetailCards();
            expect(controller.detailCards).toBeTruthy();
            expect(controller.detailCards.length).toBe(1);
        });
    });

    describe('Toggle Button Creation', () => {
        beforeEach(() => {
            controller.init();
        });

        it('should add toggle button to card header', () => {
            const card = document.querySelector('.detail-card-improved');
            const header = card.querySelector('.card-header');
            const button = header.querySelector('.empty-fields-toggle');

            expect(button).toBeTruthy();
            expect(button.dataset.section).toBe('facility_basic');
        });

        it('should not add duplicate buttons', () => {
            const card = document.querySelector('.detail-card-improved');

            // Try to add button again
            controller.addToggleButton(card);

            const buttons = card.querySelectorAll('.empty-fields-toggle');
            expect(buttons.length).toBe(1);
        });

        it('should count empty fields correctly', () => {
            const card = document.querySelector('.detail-card-improved');
            const emptyFields = card.querySelectorAll('.empty-field');

            expect(emptyFields.length).toBe(2);
        });
    });

    describe('Toggle Functionality', () => {
        beforeEach(() => {
            controller.init();
        });

        it('should toggle empty fields visibility', () => {
            const card = document.querySelector('.detail-card-improved');
            const section = 'facility_basic';

            // Initially should not have show-empty-fields class
            expect(card.classList.contains('show-empty-fields')).toBe(false);

            // Toggle to show
            controller.toggleEmptyFields(card, section);
            expect(card.classList.contains('show-empty-fields')).toBe(true);

            // Toggle to hide
            controller.toggleEmptyFields(card, section);
            expect(card.classList.contains('show-empty-fields')).toBe(false);
        });

        it('should update button state when toggling', () => {
            const card = document.querySelector('.detail-card-improved');
            const button = card.querySelector('.empty-fields-toggle');
            const section = 'facility_basic';

            // Initially should show "show" state
            expect(button.innerHTML).toContain('未設定項目を表示');
            expect(button.getAttribute('aria-pressed')).toBe('false');

            // Toggle to show
            controller.toggleEmptyFields(card, section);
            expect(button.innerHTML).toContain('未設定項目を非表示');
            expect(button.getAttribute('aria-pressed')).toBe('true');
        });
    });

    describe('User Preferences', () => {
        beforeEach(() => {
            controller.init();
        });

        it('should save user preferences to localStorage', () => {
            const section = 'facility_basic';
            const showEmptyFields = true;

            controller.saveUserPreference(section, showEmptyFields);

            expect(global.localStorage.setItem).toHaveBeenCalledWith(
                'detailCardPreferences',
                JSON.stringify({
                    [section]: { showEmptyFields }
                })
            );
        });

        it('should load user preferences from localStorage', () => {
            const preferences = {
                facility_basic: { showEmptyFields: true }
            };

            global.localStorage.getItem.mockReturnValue(JSON.stringify(preferences));

            controller.loadUserPreferences();

            const card = document.querySelector('.detail-card-improved');
            expect(card.classList.contains('show-empty-fields')).toBe(true);
        });

        it('should get preference for a section', () => {
            const preferences = {
                facility_basic: { showEmptyFields: true }
            };

            global.localStorage.getItem.mockReturnValue(JSON.stringify(preferences));

            const result = controller.getPreference('facility_basic');
            expect(result).toBe(true);
        });

        it('should return default preference when none exists', () => {
            global.localStorage.getItem.mockReturnValue(null);

            const result = controller.getPreference('nonexistent_section');
            expect(result).toBe(controller.config.defaultShowEmpty);
        });
    });

    describe('Statistics', () => {
        beforeEach(() => {
            controller.init();
        });

        it('should provide accurate statistics', () => {
            const stats = controller.getStatistics();

            expect(stats.totalCards).toBe(1);
            expect(stats.cardsWithEmptyFields).toBe(1);
            expect(stats.totalEmptyFields).toBe(2);
            expect(stats.sectionsHiding).toBe(1);
            expect(stats.sectionsShowing).toBe(0);
        });
    });

    describe('Refresh and Destroy', () => {
        it('should refresh successfully', () => {
            controller.init();
            const result = controller.refresh();
            expect(result).toBe(true);
        });

        it('should destroy cleanly', () => {
            controller.init();
            controller.destroy();

            expect(controller.isInitialized).toBe(false);
            expect(controller.detailCards).toBe(null);
        });
    });
});

describe('Module Initialization', () => {
    beforeEach(() => {
        document.body.innerHTML = `
      <div class="detail-card-improved" data-section="test">
        <div class="card-header">
          <h5>Test Card</h5>
        </div>
        <div class="empty-field">Empty field</div>
      </div>
    `;

        global.localStorage = {
            getItem: vi.fn(),
            setItem: vi.fn(),
            removeItem: vi.fn()
        };
    });

    afterEach(() => {
        delete global.localStorage;
    });

    it('should initialize and return controller instance', () => {
        const controller = initializeDetailCardController();
        expect(controller).toBeInstanceOf(DetailCardController);
        expect(controller.isInitialized).toBe(true);
    });

    it('should return null when initialization fails', () => {
    // Remove all detail cards
        const cards = document.querySelectorAll('.detail-card-improved');
        cards.forEach(card => card.remove());

        const controller = initializeDetailCardController();
        expect(controller).toBe(null);
    });
});
