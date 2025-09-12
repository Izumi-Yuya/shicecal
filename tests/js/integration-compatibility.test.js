/**
 * Integration Compatibility Tests
 * Tests for ensuring detail card improvements work with existing functionality
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { JSDOM } from 'jsdom';
import { DetailCardController } from '../../resources/js/modules/detail-card-controller.js';

// Mock utilities
vi.mock('../../resources/js/shared/utils.js', () => ({
  showToast: vi.fn()
}));

describe('Integration Compatibility Tests', () => {
  let dom;
  let document;
  let window;
  let controller;

  beforeEach(() => {
    // Create comprehensive DOM structure matching actual application
    dom = new JSDOM(`
      <!DOCTYPE html>
      <html lang="ja">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Integration Test</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          .detail-card-improved .empty-field { display: none; }
          .detail-card-improved.show-empty-fields .empty-field { display: flex; }
          .comment-section.d-none { display: none !important; }
          .fade-in { opacity: 1; transition: opacity 0.3s ease; }
        </style>
      </head>
      <body>
        <!-- Main facility show page structure -->
        <div class="container">
          <!-- Fixed header card -->
          <div class="facility-header-card card mb-3 sticky-top">
            <div class="card-body py-3">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <div class="d-flex align-items-center">
                    <div class="facility-icon me-3">
                      <i class="fas fa-building"></i>
                    </div>
                    <div>
                      <h5 class="mb-1 facility-name">テスト施設</h5>
                      <div class="facility-meta">
                        <span class="badge bg-primary me-2">TEST001</span>
                        <small class="text-muted">更新日時: 2024年1月1日 12:00</small>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 text-end">
                  <div class="facility-actions">
                    <a href="/facilities" class="btn btn-outline-secondary btn-sm">
                      <i class="fas fa-arrow-left"></i> 一覧に戻る
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab navigation -->
          <div class="facility-detail-container">
            <div class="tab-navigation mb-4">
              <ul class="nav nav-tabs" id="facilityTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" 
                          data-bs-target="#basic-info" type="button" role="tab">
                    <i class="fas fa-info-circle me-2"></i>基本
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="land-tab" data-bs-toggle="tab" 
                          data-bs-target="#land-info" type="button" role="tab">
                    <i class="fas fa-map me-2"></i>土地
                  </button>
                </li>
              </ul>
            </div>
            
            <div class="tab-content" id="facilityTabContent">
              <!-- Basic info tab -->
              <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="mb-0">
                    <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                  </h4>
                  <a href="/facilities/1/edit-basic-info" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>編集
                  </a>
                </div>
                
                <!-- Basic info cards with comment functionality -->
                <div class="row">
                  <div class="col-lg-6 mb-4">
                    <div class="card facility-info-card detail-card-improved h-100" data-section="facility_basic">
                      <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                          <i class="fas fa-info-circle text-primary me-2"></i>基本情報
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="basic_info" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示"
                                aria-label="基本情報のコメントを表示または非表示にする"
                                aria-expanded="false"
                                aria-controls="comment-section-basic_info">
                          <i class="fas fa-comment" aria-hidden="true"></i>
                          <span class="comment-count" data-section="basic_info" aria-label="コメント数">0</span>
                        </button>
                      </div>
                      <div class="card-body">
                        <div class="facility-detail-table">
                          <div class="detail-row">
                            <span class="detail-label">会社名</span>
                            <span class="detail-value">テスト株式会社</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">事業所コード</span>
                            <span class="detail-value">
                              <span class="badge bg-primary">TEST001</span>
                            </span>
                          </div>
                          <div class="detail-row empty-field">
                            <span class="detail-label">指定番号</span>
                            <span class="detail-value">未設定</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">施設名</span>
                            <span class="detail-value fw-bold">テスト施設</span>
                          </div>
                        </div>
                        
                        <!-- Comment section -->
                        <div class="comment-section mt-3 d-none" 
                             data-section="basic_info" 
                             id="comment-section-basic_info"
                             role="region"
                             aria-label="基本情報のコメント">
                          <hr>
                          <div class="comment-form mb-3">
                            <div class="input-group">
                              <input type="text" 
                                     class="form-control comment-input" 
                                     id="comment-input-basic_info"
                                     placeholder="コメントを入力..." 
                                     data-section="basic_info">
                              <button class="btn btn-primary comment-submit" 
                                      data-section="basic_info">
                                <i class="fas fa-paper-plane"></i>
                              </button>
                            </div>
                          </div>
                          <div class="comment-list" data-section="basic_info">
                            <!-- コメントがここに動的に追加されます -->
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-6 mb-4">
                    <div class="card facility-info-card detail-card-improved h-100" data-section="facility_contact">
                      <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                          <i class="fas fa-map-marker-alt text-success me-2"></i>住所・連絡先
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm comment-toggle" 
                                data-section="contact_info" 
                                data-bs-toggle="tooltip" 
                                title="コメントを表示/非表示">
                          <i class="fas fa-comment"></i>
                          <span class="comment-count" data-section="contact_info">0</span>
                        </button>
                      </div>
                      <div class="card-body">
                        <div class="facility-detail-table">
                          <div class="detail-row empty-field">
                            <span class="detail-label">郵便番号</span>
                            <span class="detail-value">未設定</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">住所</span>
                            <span class="detail-value">東京都渋谷区テスト1-2-3</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">電話番号</span>
                            <span class="detail-value">03-1234-5678</span>
                          </div>
                          <div class="detail-row empty-field">
                            <span class="detail-label">FAX番号</span>
                            <span class="detail-value">未設定</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">メールアドレス</span>
                            <span class="detail-value">
                              <a href="mailto:test@example.com">test@example.com</a>
                            </span>
                          </div>
                        </div>
                        
                        <!-- Comment section -->
                        <div class="comment-section mt-3 d-none" data-section="contact_info">
                          <hr>
                          <div class="comment-form mb-3">
                            <div class="input-group">
                              <input type="text" class="form-control comment-input" 
                                     placeholder="コメントを入力..." 
                                     data-section="contact_info">
                              <button class="btn btn-primary comment-submit" 
                                      data-section="contact_info">
                                <i class="fas fa-paper-plane"></i>
                              </button>
                            </div>
                          </div>
                          <div class="comment-list" data-section="contact_info">
                            <!-- コメントがここに動的に追加されます -->
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Land info tab -->
              <div class="tab-pane fade" id="land-info" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="mb-0">
                    <i class="fas fa-map text-primary me-2"></i>土地情報
                  </h4>
                  <a href="/facilities/1/land-info/edit" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>編集
                  </a>
                </div>
                
                <div class="row">
                  <div class="col-lg-6 mb-4">
                    <div class="card facility-info-card detail-card-improved h-100" data-section="land_basic">
                      <div class="card-header">
                        <h5 class="mb-0">
                          <i class="fas fa-map me-2"></i>基本情報
                        </h5>
                      </div>
                      <div class="card-body">
                        <div class="facility-detail-table">
                          <div class="detail-row">
                            <span class="detail-label">所有形態</span>
                            <span class="detail-value">
                              <span class="badge bg-success">自社</span>
                            </span>
                          </div>
                          <div class="detail-row empty-field">
                            <span class="detail-label">敷地内駐車場台数</span>
                            <span class="detail-value">未設定</span>
                          </div>
                          <div class="detail-row">
                            <span class="detail-label">敷地面積（㎡）</span>
                            <span class="detail-value">1,000.00㎡</span>
                          </div>
                          <div class="detail-row empty-field">
                            <span class="detail-label">敷地面積（坪数）</span>
                            <span class="detail-value">未設定</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bootstrap modal for testing -->
        <div class="modal fade" id="testModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">テストモーダル</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p>モーダル内のコンテンツ</p>
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
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn()
    };

    // Mock Bootstrap
    global.bootstrap = {
      Tooltip: vi.fn(),
      Popover: vi.fn(),
      Modal: vi.fn()
    };

    controller = new DetailCardController();
  });

  afterEach(() => {
    if (controller && controller.destroy) {
      controller.destroy();
    }
    dom.window.close();
  });

  describe('Comment Functionality Integration', () => {
    test('should not interfere with existing comment toggle buttons', () => {
      controller.init();

      const commentToggles = document.querySelectorAll('.comment-toggle');
      expect(commentToggles.length).toBeGreaterThan(0);

      // Verify comment toggles are still present and functional
      commentToggles.forEach(toggle => {
        expect(toggle.classList.contains('comment-toggle')).toBe(true);
        expect(toggle.dataset.section).toBeTruthy();
      });
    });

    test('should preserve comment section functionality', () => {
      controller.init();

      const commentSections = document.querySelectorAll('.comment-section');
      expect(commentSections.length).toBeGreaterThan(0);

      // Verify comment sections are initially hidden
      commentSections.forEach(section => {
        expect(section.classList.contains('d-none')).toBe(true);
      });
    });

    test('should not conflict with comment form elements', () => {
      controller.init();

      const commentInputs = document.querySelectorAll('.comment-input');
      const commentSubmits = document.querySelectorAll('.comment-submit');

      expect(commentInputs.length).toBeGreaterThan(0);
      expect(commentSubmits.length).toBeGreaterThan(0);

      // Verify comment form elements are preserved
      commentInputs.forEach(input => {
        expect(input.placeholder).toBe('コメントを入力...');
        expect(input.dataset.section).toBeTruthy();
      });
    });

    test('should maintain comment count display', () => {
      controller.init();

      const commentCounts = document.querySelectorAll('.comment-count');
      expect(commentCounts.length).toBeGreaterThan(0);

      commentCounts.forEach(count => {
        expect(count.textContent).toBe('0');
        expect(count.dataset.section).toBeTruthy();
      });
    });
  });

  describe('Edit Button Integration', () => {
    test('should preserve edit buttons in headers', () => {
      controller.init();

      const editButtons = document.querySelectorAll('a[href*="/edit"]');
      expect(editButtons.length).toBeGreaterThan(0);

      editButtons.forEach(button => {
        expect(button.classList.contains('btn-primary')).toBe(true);
        expect(button.querySelector('i.fas.fa-edit')).toBeTruthy();
      });
    });

    test('should not interfere with edit button positioning', () => {
      controller.init();

      const headerWithEdit = document.querySelector('.d-flex.justify-content-between.align-items-center');
      expect(headerWithEdit).toBeTruthy();

      const editButton = headerWithEdit.querySelector('a[href*="/edit"]');
      expect(editButton).toBeTruthy();
    });

    test('should maintain edit button accessibility', () => {
      controller.init();

      const editButtons = document.querySelectorAll('a[href*="/edit"]');
      editButtons.forEach(button => {
        const icon = button.querySelector('i');
        const text = button.textContent.trim();

        expect(icon).toBeTruthy();
        expect(text).toContain('編集');
      });
    });
  });

  describe('Tab Navigation Integration', () => {
    test('should work with Bootstrap tab navigation', () => {
      controller.init();

      const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
      const tabPanes = document.querySelectorAll('.tab-pane');

      expect(tabButtons.length).toBeGreaterThan(0);
      expect(tabPanes.length).toBeGreaterThan(0);

      // Verify tab structure is preserved
      tabButtons.forEach(button => {
        expect(button.getAttribute('data-bs-target')).toBeTruthy();
        expect(button.getAttribute('role')).toBe('tab');
      });
    });

    test('should maintain tab content structure', () => {
      controller.init();

      const basicTab = document.getElementById('basic-info');
      const landTab = document.getElementById('land-info');

      expect(basicTab).toBeTruthy();
      expect(landTab).toBeTruthy();

      expect(basicTab.classList.contains('tab-pane')).toBe(true);
      expect(landTab.classList.contains('tab-pane')).toBe(true);
    });

    test('should work within tab content', () => {
      controller.init();

      // Check that detail cards in both tabs are processed
      const basicTabCards = document.querySelectorAll('#basic-info .detail-card-improved');
      const landTabCards = document.querySelectorAll('#land-info .detail-card-improved');

      expect(basicTabCards.length).toBeGreaterThan(0);
      expect(landTabCards.length).toBeGreaterThan(0);

      // Verify toggle buttons are added to cards in both tabs
      const toggleButtons = document.querySelectorAll('.empty-fields-toggle');
      expect(toggleButtons.length).toBeGreaterThan(0);
    });
  });

  describe('Card Layout Integration', () => {
    test('should preserve Bootstrap grid layout', () => {
      controller.init();

      const rows = document.querySelectorAll('.row');
      const cols = document.querySelectorAll('[class*="col-"]');

      expect(rows.length).toBeGreaterThan(0);
      expect(cols.length).toBeGreaterThan(0);

      // Verify grid classes are preserved
      cols.forEach(col => {
        expect(col.className).toMatch(/col-/);
      });
    });

    test('should maintain card height classes', () => {
      controller.init();

      const heightCards = document.querySelectorAll('.h-100');
      expect(heightCards.length).toBeGreaterThan(0);

      heightCards.forEach(card => {
        expect(card.classList.contains('h-100')).toBe(true);
      });
    });

    test('should preserve card header structure', () => {
      controller.init();

      const cardHeaders = document.querySelectorAll('.card-header');
      expect(cardHeaders.length).toBeGreaterThan(0);

      cardHeaders.forEach(header => {
        const title = header.querySelector('h5');
        expect(title).toBeTruthy();
        expect(title.classList.contains('mb-0')).toBe(true);
      });
    });

    test('should maintain card body structure', () => {
      controller.init();

      const cardBodies = document.querySelectorAll('.card-body');
      expect(cardBodies.length).toBeGreaterThan(0);

      cardBodies.forEach(body => {
        const detailTable = body.querySelector('.facility-detail-table');
        expect(detailTable).toBeTruthy();
      });
    });
  });

  describe('Accessibility Integration', () => {
    test('should preserve existing ARIA attributes', () => {
      controller.init();

      const ariaElements = document.querySelectorAll('[aria-label], [aria-expanded], [aria-controls]');
      expect(ariaElements.length).toBeGreaterThan(0);

      // Check that existing ARIA attributes are preserved
      const commentToggle = document.querySelector('.comment-toggle[aria-label]');
      expect(commentToggle).toBeTruthy();
      expect(commentToggle.getAttribute('aria-label')).toContain('コメント');
    });

    test('should maintain role attributes', () => {
      controller.init();

      const roleElements = document.querySelectorAll('[role]');
      expect(roleElements.length).toBeGreaterThan(0);

      // Check specific roles
      const tabElements = document.querySelectorAll('[role="tab"]');
      const regionElements = document.querySelectorAll('[role="region"]');

      expect(tabElements.length).toBeGreaterThan(0);
      expect(regionElements.length).toBeGreaterThan(0);
    });

    test('should preserve screen reader text', () => {
      controller.init();

      const srOnlyElements = document.querySelectorAll('.sr-only');
      expect(srOnlyElements.length).toBeGreaterThan(0);

      // Verify screen reader text is preserved
      srOnlyElements.forEach(element => {
        expect(element.textContent.trim()).toBeTruthy();
      });
    });
  });

  describe('Dynamic Content Integration', () => {
    test('should handle dynamically added cards', () => {
      controller.init();

      // Add a new card dynamically
      const newCard = document.createElement('div');
      newCard.className = 'card facility-info-card detail-card-improved';
      newCard.dataset.section = 'dynamic_test';
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

      // Refresh controller
      controller.refresh();

      // Check that new card is processed
      const toggleButton = newCard.querySelector('.empty-fields-toggle');
      expect(toggleButton).toBeTruthy();
    });

    test('should handle content updates', () => {
      controller.init();

      const originalCards = document.querySelectorAll('.detail-card-improved').length;

      // Simulate content update
      const existingCard = document.querySelector('.detail-card-improved');
      const newEmptyField = document.createElement('div');
      newEmptyField.className = 'detail-row empty-field';
      newEmptyField.innerHTML = `
        <span class="detail-label">新しい未設定項目</span>
        <span class="detail-value">未設定</span>
      `;

      const detailTable = existingCard.querySelector('.facility-detail-table');
      detailTable.appendChild(newEmptyField);

      // Refresh controller
      controller.refresh();

      // Verify controller still works
      const cards = document.querySelectorAll('.detail-card-improved');
      expect(cards.length).toBe(originalCards);
    });
  });

  describe('Performance Integration', () => {
    test('should not significantly impact page load time', () => {
      const startTime = performance.now();
      controller.init();
      const endTime = performance.now();

      // Should initialize quickly (< 50ms for test environment)
      expect(endTime - startTime).toBeLessThan(50);
    });

    test('should handle multiple cards efficiently', () => {
      // Add multiple cards
      const container = document.querySelector('.container');
      for (let i = 0; i < 20; i++) {
        const card = document.createElement('div');
        card.className = 'card facility-info-card detail-card-improved';
        card.dataset.section = `perf_test_${i}`;
        card.innerHTML = `
          <div class="card-header">
            <h5 class="mb-0">パフォーマンステスト ${i}</h5>
          </div>
          <div class="card-body">
            <div class="facility-detail-table">
              <div class="detail-row empty-field">
                <span class="detail-label">項目 ${i}</span>
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

      // Should handle multiple cards efficiently
      expect(endTime - startTime).toBeLessThan(100);

      // Verify all cards are processed
      const toggleButtons = document.querySelectorAll('.empty-fields-toggle');
      expect(toggleButtons.length).toBeGreaterThan(20);
    });
  });

  describe('Error Recovery Integration', () => {
    test('should not break existing functionality on errors', () => {
      // Mock console.error to capture errors
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => { });

      // Cause an error in controller
      vi.spyOn(controller, 'addToggleButton').mockImplementation(() => {
        throw new Error('Test error');
      });

      // Initialize should not throw
      expect(() => {
        controller.init();
      }).not.toThrow();

      // Existing elements should still be present
      const commentToggles = document.querySelectorAll('.comment-toggle');
      const editButtons = document.querySelectorAll('a[href*="/edit"]');

      expect(commentToggles.length).toBeGreaterThan(0);
      expect(editButtons.length).toBeGreaterThan(0);

      consoleSpy.mockRestore();
    });

    test('should gracefully handle missing elements', () => {
      // Remove some elements
      const cardHeader = document.querySelector('.card-header');
      if (cardHeader) {
        cardHeader.remove();
      }

      expect(() => {
        controller.init();
      }).not.toThrow();
    });
  });
});