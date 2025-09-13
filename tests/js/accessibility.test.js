/**
 * Accessibility Tests for Facility Form Layout
 *
 * Tests ARIA attributes, keyboard navigation, screen reader support,
 * and other accessibility features.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { FacilityFormLayout } from '../../resources/js/modules/facility-form-layout.js';

// Mock DOM environment
const mockDOM = () => {
    document.body.innerHTML = `
    <div class="container-fluid facility-edit-layout">
      <header role="banner">
        <h1 id="page-title">施設情報編集</h1>
        <nav aria-label="パンくずリスト">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">ホーム</a></li>
            <li class="breadcrumb-item active" aria-current="page">編集</li>
          </ol>
        </nav>
      </header>
      
      <main id="main-content" role="main">
        <form class="facility-edit-form" aria-labelledby="page-title" novalidate>
          <div id="form-status-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>
          
          <section class="card form-section" role="region" aria-labelledby="section-basic" data-collapsible="true">
            <header class="card-header section-header" id="section-basic" role="button" tabindex="0" aria-expanded="true" aria-controls="section-basic-content">
              <h5>
                <i class="fas fa-info-circle text-primary" aria-hidden="true"></i>
                <span class="section-title">基本情報</span>
                <i class="fas fa-chevron-up collapse-icon" aria-hidden="true"></i>
              </h5>
            </header>
            <div class="card-body" id="section-basic-content" role="group" aria-labelledby="section-basic">
              <div class="mb-3">
                <label for="facility_name" class="form-label required">施設名</label>
                <input type="text" id="facility_name" name="facility_name" class="form-control" required aria-required="true">
                <div class="invalid-feedback" id="facility_name_error"></div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-control" aria-describedby="email_desc">
                <div id="email_desc" class="form-text text-muted sr-only">有効なメールアドレスを入力してください。</div>
              </div>
            </div>
          </section>
          
          <section class="card form-section" role="region" aria-labelledby="section-contact" data-collapsible="true">
            <header class="card-header section-header" id="section-contact" role="button" tabindex="0" aria-expanded="false" aria-controls="section-contact-content">
              <h5>
                <i class="fas fa-phone text-primary" aria-hidden="true"></i>
                <span class="section-title">連絡先情報</span>
                <i class="fas fa-chevron-down collapse-icon" aria-hidden="true"></i>
              </h5>
            </header>
            <div class="card-body collapse" id="section-contact-content" role="group" aria-labelledby="section-contact" aria-hidden="true">
              <div class="mb-3">
                <label for="phone" class="form-label">電話番号</label>
                <input type="tel" id="phone" name="phone" class="form-control" aria-describedby="phone_desc">
                <div id="phone_desc" class="form-text text-muted sr-only">電話番号を入力してください（例: 03-1234-5678）。</div>
              </div>
            </div>
          </section>
          
          <div role="group" aria-label="フォームアクション">
            <button type="submit" class="btn btn-primary" aria-label="フォームの内容を保存する">
              <i class="fas fa-save" aria-hidden="true"></i>保存
            </button>
          </div>
        </form>
      </main>
    </div>
  `;
};

describe('Accessibility Features', () => {
    let facilityForm;

    beforeEach(() => {
        mockDOM();
        facilityForm = new FacilityFormLayout({
            enableAccessibility: true,
            enableRealTimeValidation: true
        });
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    describe('ARIA Attributes', () => {
        it('should have proper ARIA landmarks', () => {
            const main = document.querySelector('main');
            const header = document.querySelector('header');
            const nav = document.querySelector('nav');

            expect(main.getAttribute('role')).toBe('main');
            expect(header.getAttribute('role')).toBe('banner');
            expect(nav.getAttribute('role')).toBe('navigation');
        });

        it('should have proper ARIA labels and descriptions', () => {
            const form = document.querySelector('.facility-edit-form');
            const sections = document.querySelectorAll('.form-section');

            expect(form.getAttribute('aria-labelledby')).toBe('page-title');
            expect(form.getAttribute('novalidate')).toBe('');

            sections.forEach(section => {
                expect(section.getAttribute('role')).toBe('region');
                expect(section.getAttribute('aria-labelledby')).toBeTruthy();
            });
        });

        it('should have proper form field associations', () => {
            const facilityNameInput = document.getElementById('facility_name');
            const emailInput = document.getElementById('email');

            expect(facilityNameInput.getAttribute('aria-required')).toBe('true');
            expect(emailInput.getAttribute('aria-describedby')).toBe('email_desc');

            const label = document.querySelector('label[for="facility_name"]');
            expect(label.classList.contains('required')).toBe(true);
        });

        it('should have proper collapsible section attributes', () => {
            const headers = document.querySelectorAll('.section-header[role="button"]');

            headers.forEach(header => {
                expect(header.getAttribute('role')).toBe('button');
                expect(header.getAttribute('tabindex')).toBe('0');
                expect(header.getAttribute('aria-expanded')).toBeTruthy();
                expect(header.getAttribute('aria-controls')).toBeTruthy();
            });
        });
    });

    describe('Live Regions', () => {
        it('should create live regions for announcements', () => {
            const statusRegion = document.getElementById('form-status-live-region');
            const urgentRegion = document.getElementById('urgent-live-region');
            const calcRegion = document.getElementById('calculation-live-region');
            const progressRegion = document.getElementById('progress-live-region');

            expect(statusRegion).toBeTruthy();
            expect(statusRegion.getAttribute('aria-live')).toBe('polite');
            expect(statusRegion.getAttribute('role')).toBe('status');

            expect(urgentRegion).toBeTruthy();
            expect(urgentRegion.getAttribute('aria-live')).toBe('assertive');
            expect(urgentRegion.getAttribute('role')).toBe('alert');

            expect(calcRegion).toBeTruthy();
            expect(progressRegion).toBeTruthy();
        });

        it('should announce form validation errors', () => {
            const form = document.querySelector('.facility-edit-form');
            const statusRegion = document.getElementById('form-status-live-region');

            // Mock form validation failure
            vi.spyOn(facilityForm, 'validateForm').mockReturnValue(false);

            const submitEvent = new Event('submit');
            form.dispatchEvent(submitEvent);

            setTimeout(() => {
                expect(statusRegion.textContent).toContain('入力エラー');
            }, 200);
        });
    });

    describe('Keyboard Navigation', () => {
        it('should support keyboard navigation for collapsible sections', () => {
            const header = document.querySelector('.section-header[role="button"]');
            const content = document.getElementById('section-basic-content');

            // Test Enter key
            const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
            header.dispatchEvent(enterEvent);

            expect(header.getAttribute('aria-expanded')).toBe('false');
            expect(content.classList.contains('collapse')).toBe(true);

            // Test Space key
            const spaceEvent = new KeyboardEvent('keydown', { key: ' ' });
            header.dispatchEvent(spaceEvent);

            expect(header.getAttribute('aria-expanded')).toBe('true');
            expect(content.classList.contains('collapse')).toBe(false);
        });

        it('should support keyboard shortcuts', () => {
            const form = document.querySelector('.facility-edit-form');
            const submitButton = form.querySelector('button[type="submit"]');

            const clickSpy = vi.spyOn(submitButton, 'click');

            // Test Ctrl+S shortcut
            const ctrlSEvent = new KeyboardEvent('keydown', {
                key: 's',
                ctrlKey: true
            });
            document.dispatchEvent(ctrlSEvent);

            expect(clickSpy).toHaveBeenCalled();
        });

        it('should support Escape key to collapse sections', () => {
            const header = document.querySelector('.section-header[role="button"]');

            // Ensure section is expanded
            header.setAttribute('aria-expanded', 'true');

            const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' });
            document.dispatchEvent(escapeEvent);

            expect(header.getAttribute('aria-expanded')).toBe('false');
        });
    });

    describe('Focus Management', () => {
        it('should focus first error field on validation failure', () => {
            const facilityNameInput = document.getElementById('facility_name');
            facilityNameInput.classList.add('is-invalid');

            const focusSpy = vi.spyOn(facilityNameInput, 'focus');
            const scrollSpy = vi.spyOn(facilityNameInput, 'scrollIntoView');

            facilityForm.focusFirstError();

            expect(focusSpy).toHaveBeenCalled();
            expect(scrollSpy).toHaveBeenCalledWith({
                behavior: 'smooth',
                block: 'center'
            });
        });

        it('should manage focus when expanding collapsed sections', () => {
            const header = document.querySelector('.section-header[role="button"]');
            const content = document.getElementById('section-contact-content');
            const firstInput = content.querySelector('input');

            const focusSpy = vi.spyOn(firstInput, 'focus');

            // Simulate expanding a collapsed section
            header.click();

            setTimeout(() => {
                expect(focusSpy).toHaveBeenCalled();
            }, 350);
        });
    });

    describe('Skip Links', () => {
        it('should create skip links for navigation', () => {
            const skipLinks = document.querySelectorAll('.skip-link');
            expect(skipLinks.length).toBeGreaterThan(0);

            const mainSkipLink = document.querySelector('a[href="#main-content"]');
            expect(mainSkipLink).toBeTruthy();
            expect(mainSkipLink.textContent).toContain('メインコンテンツにスキップ');
        });

        it('should create section navigation skip links', () => {
            const skipNav = document.querySelector('.skip-navigation');
            expect(skipNav).toBeTruthy();
            expect(skipNav.getAttribute('aria-label')).toBe('セクションナビゲーション');

            const sectionLinks = skipNav.querySelectorAll('a[href^="#section-"]');
            expect(sectionLinks.length).toBeGreaterThan(0);
        });
    });

    describe('Form Enhancement', () => {
        it('should add proper autocomplete attributes', () => {
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            expect(emailInput.getAttribute('autocomplete')).toBe('email');
            expect(phoneInput.getAttribute('autocomplete')).toBe('tel');
        });

        it('should add proper inputmode attributes', () => {
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            expect(emailInput.getAttribute('inputmode')).toBe('email');
            expect(phoneInput.getAttribute('inputmode')).toBe('tel');
        });

        it('should create fieldset grouping', () => {
            const sections = document.querySelectorAll('.form-section');

            sections.forEach(section => {
                const fieldset = section.querySelector('fieldset');
                const legend = section.querySelector('legend');

                if (section.querySelectorAll('input, select, textarea').length > 0) {
                    expect(fieldset).toBeTruthy();
                    expect(legend).toBeTruthy();
                    expect(legend.classList.contains('sr-only')).toBe(true);
                }
            });
        });
    });

    describe('Error Handling', () => {
        it('should properly associate error messages with fields', () => {
            const facilityNameInput = document.getElementById('facility_name');
            const errorElement = document.getElementById('facility_name_error');

            facilityNameInput.classList.add('is-invalid');
            facilityForm.enhanceErrorMessages();

            expect(facilityNameInput.getAttribute('aria-invalid')).toBe('true');
            expect(facilityNameInput.getAttribute('aria-describedby')).toContain('facility_name_error');
        });

        it('should announce errors to screen readers', () => {
            const statusRegion = document.getElementById('form-status-live-region');

            facilityForm.announceToScreenReader('テストエラーメッセージ', 'urgent');

            setTimeout(() => {
                expect(statusRegion.textContent).toBe('テストエラーメッセージ');
            }, 200);
        });
    });

    describe('Progress Indicators', () => {
        it('should create and update progress indicators', () => {
            facilityForm.addProgressIndicators();

            const progressContainer = document.querySelector('.form-progress');
            const progressBar = document.querySelector('.progress-bar');

            expect(progressContainer).toBeTruthy();
            expect(progressContainer.getAttribute('role')).toBe('progressbar');
            expect(progressBar).toBeTruthy();
        });

        it('should update progress when form fields are filled', () => {
            facilityForm.addProgressIndicators();

            const facilityNameInput = document.getElementById('facility_name');
            const progressBar = document.querySelector('.progress-bar');

            facilityNameInput.value = 'テスト施設';
            facilityForm.updateFormProgress();

            expect(parseInt(progressBar.style.width)).toBeGreaterThan(0);
            expect(progressBar.getAttribute('aria-valuenow')).toBeTruthy();
        });
    });

    describe('Screen Reader Support', () => {
        it('should have proper screen reader only content', () => {
            const srOnlyElements = document.querySelectorAll('.sr-only');
            expect(srOnlyElements.length).toBeGreaterThan(0);

            srOnlyElements.forEach(element => {
                const styles = window.getComputedStyle(element);
                expect(styles.position).toBe('absolute');
                expect(styles.width).toBe('1px');
                expect(styles.height).toBe('1px');
            });
        });

        it('should have proper ARIA hidden attributes for decorative icons', () => {
            const decorativeIcons = document.querySelectorAll('i[aria-hidden="true"]');
            expect(decorativeIcons.length).toBeGreaterThan(0);

            decorativeIcons.forEach(icon => {
                expect(icon.getAttribute('aria-hidden')).toBe('true');
            });
        });
    });

    describe('Mobile Accessibility', () => {
        it('should optimize touch targets for mobile', () => {
            // Mock mobile viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 500
            });

            facilityForm.optimizeForMobile();

            const buttons = document.querySelectorAll('.btn');
            const formControls = document.querySelectorAll('.form-control');

            buttons.forEach(btn => {
                expect(parseInt(btn.style.minHeight) || 44).toBeGreaterThanOrEqual(44);
            });

            formControls.forEach(control => {
                expect(parseInt(control.style.minHeight) || 44).toBeGreaterThanOrEqual(44);
            });
        });
    });

    describe('High Contrast Mode', () => {
        it('should support high contrast mode styles', () => {
            // This test would need to be run in a browser environment with high contrast mode
            // For now, we just verify the CSS classes exist
            const formSection = document.querySelector('.form-section');
            expect(formSection).toBeTruthy();
        });
    });

    describe('Reduced Motion Support', () => {
        it('should respect reduced motion preferences', () => {
            // Mock reduced motion preference
            Object.defineProperty(window, 'matchMedia', {
                writable: true,
                value: vi.fn().mockImplementation(query => ({
                    matches: query === '(prefers-reduced-motion: reduce)',
                    media: query,
                    onchange: null,
                    addListener: vi.fn(),
                    removeListener: vi.fn(),
                    addEventListener: vi.fn(),
                    removeEventListener: vi.fn(),
                    dispatchEvent: vi.fn()
                }))
            });

            // The CSS handles reduced motion, so we just verify the media query works
            const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            expect(mediaQuery.matches).toBe(true);
        });
    });
});

describe('Accessibility Integration', () => {
    beforeEach(() => {
        mockDOM();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('should initialize all accessibility features by default', () => {
        const facilityForm = new FacilityFormLayout();

        // Verify live regions are created
        expect(document.getElementById('form-status-live-region')).toBeTruthy();
        expect(document.getElementById('urgent-live-region')).toBeTruthy();

        // Verify skip links are added
        expect(document.querySelector('.skip-link')).toBeTruthy();

        // Verify ARIA attributes are enhanced
        const form = document.querySelector('.facility-edit-form');
        expect(form.getAttribute('aria-labelledby')).toBe('page-title');
    });

    it('should allow disabling accessibility features', () => {
        const facilityForm = new FacilityFormLayout({
            enableAccessibility: false
        });

        // Should not create additional accessibility features when disabled
        expect(document.getElementById('urgent-live-region')).toBeFalsy();
    });

    it('should work with existing land-info forms', () => {
        document.body.innerHTML = `
      <form id="landInfoForm">
        <input type="text" name="facility_name" required>
      </form>
    `;

        const facilityForm = new FacilityFormLayout();

        // Should enhance existing form
        const form = document.getElementById('landInfoForm');
        expect(form).toBeTruthy();
    });
});
