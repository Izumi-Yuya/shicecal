/**
 * Detail Card Controller Tests - Simplified for Always-Visible Empty Fields
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { DetailCardController, initializeDetailCardController } from '../../resources/js/modules/detail-card-controller.js';

describe('DetailCardController', () => {
  let controller;

  beforeEach(() => {
    // Set up DOM structure with proper container
    document.body.innerHTML = `
            <main>
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
            </main>
        `;

    controller = new DetailCardController();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('Initialization', () => {
    it('should initialize successfully with detail cards present', async () => {
      const result = await controller.init();
      expect(result).toBe(true);
      expect(controller.isInitialized).toBe(true);
    });

    it('should initialize successfully when no detail cards are found', async () => {
      // Remove all detail cards
      const cards = document.querySelectorAll('.detail-card-improved');
      cards.forEach(card => card.remove());

      const result = await controller.init();
      expect(result).toBe(true);
      expect(controller.isInitialized).toBe(true);
    });

    it('should find detail cards correctly', () => {
      controller._findDetailCards();
      expect(controller.detailCards).toBeTruthy();
      expect(controller.detailCards.length).toBe(1);
    });
  });

  describe('Accessibility Enhancement', () => {
    beforeEach(async () => {
      await controller.init();
    });

    it('should add screen reader description for empty fields', () => {
      const card = document.querySelector('.detail-card-improved');
      const description = card.querySelector('#empty-fields-desc-facility_basic');

      expect(description).toBeTruthy();
      expect(description.textContent).toContain('このセクションには2件の未設定項目が表示されています');
    });

    it('should enhance detail rows with ARIA attributes', () => {
      const card = document.querySelector('.detail-card-improved');
      const detailRows = card.querySelectorAll('.detail-row');

      detailRows.forEach((row, index) => {
        const label = row.querySelector('.detail-label');
        const value = row.querySelector('.detail-value');

        expect(row.getAttribute('role')).toBe('row');
        expect(label.getAttribute('role')).toBe('rowheader');
        expect(value.getAttribute('role')).toBe('cell');
        expect(value.getAttribute('aria-labelledby')).toBe(label.id);
      });
    });

    it('should count empty fields correctly', () => {
      const card = document.querySelector('.detail-card-improved');
      const emptyFields = card.querySelectorAll('.empty-field');

      expect(emptyFields.length).toBe(2);
    });
  });

  describe('Statistics', () => {
    beforeEach(async () => {
      await controller.init();
    });

    it('should calculate statistics correctly', () => {
      const stats = controller.getStatistics();

      expect(stats.totalCards).toBe(1);
      expect(stats.cardsWithEmptyFields).toBe(1);
      expect(stats.totalEmptyFields).toBe(2);
      expect(stats.performance).toBeDefined();
      expect(stats.performance.memoryUsage).toBeDefined();
    });
  });

  describe('Refresh Functionality', () => {
    beforeEach(async () => {
      await controller.init();
    });

    it('should refresh successfully', async () => {
      const result = await controller.refresh();
      expect(result).toBe(true);
    });

    it('should reinitialize if not already initialized', async () => {
      const newController = new DetailCardController();
      const result = await newController.refresh();
      expect(result).toBe(true);
      expect(newController.isInitialized).toBe(true);
    });
  });

  describe('Cleanup', () => {
    beforeEach(async () => {
      await controller.init();
    });

    it('should cleanup successfully', () => {
      expect(() => controller.cleanup()).not.toThrow();
      expect(controller.isInitialized).toBe(false);
      expect(controller.detailCards.length).toBe(0);
    });
  });

  describe('ARIA Landmarks', () => {
    beforeEach(async () => {
      await controller.init();
    });

    it('should add ARIA landmarks to detail cards', () => {
      const card = document.querySelector('.detail-card-improved');
      const header = card.querySelector('.card-header h5');

      expect(header.hasAttribute('id')).toBe(true);
      expect(card.getAttribute('aria-labelledby')).toBe(header.id);
      expect(card.getAttribute('role')).toBe('region');
    });
  });
});

describe('initializeDetailCardController', () => {
  beforeEach(() => {
    document.body.innerHTML = `
            <main>
                <div class="card facility-info-card detail-card-improved" data-section="test">
                    <div class="card-header">
                        <h5>Test Card</h5>
                    </div>
                    <div class="card-body">
                        <div class="detail-row empty-field">
                            <span class="detail-label">Test Field</span>
                            <span class="detail-value">未設定</span>
                        </div>
                    </div>
                </div>
            </main>
        `;
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  it('should initialize and return controller instance', async () => {
    const controller = await initializeDetailCardController();
    expect(controller).toBeInstanceOf(DetailCardController);
    expect(controller.isInitialized).toBe(true);
  });

  it('should return controller even when no cards are present', async () => {
    document.body.innerHTML = '<main></main>';
    const controller = await initializeDetailCardController();
    expect(controller).toBeInstanceOf(DetailCardController);
    expect(controller.isInitialized).toBe(true);
  });
});