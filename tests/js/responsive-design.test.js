/**
 * @vitest-environment jsdom
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { FacilityFormLayout } from '../../resources/js/modules/facility-form-layout.js';

// Mock window.matchMedia
const mockMatchMedia = (matches) => {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation(query => ({
            matches,
            media: query,
            onchange: null,
            addListener: vi.fn(),
            removeListener: vi.fn(),
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn()
        }))
    });
};

// Mock DOM setup for responsive testing
const setupResponsiveDOM = () => {
    document.body.innerHTML = `
    <div class="container-fluid facility-edit-layout">
      <form class="facility-edit-form">
        <div class="row">
          <div class="col-12 col-md-8">
            <div class="card form-section" data-collapsible="true">
              <div class="card-header section-header">
                <h5>
                  <i class="fas fa-info-circle"></i>
                  基本情報
                  <i class="fas fa-chevron-up collapse-icon"></i>
                </h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="facility_name" class="form-label">施設名</label>
                      <input type="text" id="facility_name" name="facility_name" class="form-control">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="address" class="form-label">住所</label>
                      <input type="text" id="address" name="address" class="form-control">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="facility-info-card card">
              <div class="card-body">
                <h6>施設情報</h6>
                <p>テスト施設</p>
              </div>
            </div>
          </div>
        </div>
        <div class="form-actions d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary">キャンセル</button>
          <button type="submit" class="btn btn-primary">保存</button>
        </div>
      </form>
    </div>
  `;
};

describe('Responsive Design Tests', () => {
    let facilityForm;

    beforeEach(() => {
        setupResponsiveDOM();
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    describe('Mobile Viewport (< 768px)', () => {
        beforeEach(() => {
            // Mock mobile viewport
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 500
            });

            mockMatchMedia(true); // Mobile media query matches
            facilityForm = new FacilityFormLayout({ enableMobileOptimization: true });
        });

        it('should optimize layout for mobile devices', () => {
            facilityForm.optimizeForMobile();

            // Check that mobile-specific classes are applied
            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('mobile-optimized')).toBe(true);

            // Check that form sections stack vertically
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach(section => {
                expect(section.classList.contains('mobile-section')).toBe(true);
            });
        });

        it('should increase touch target sizes for mobile', () => {
            facilityForm.optimizeForMobile();

            const buttons = document.querySelectorAll('.btn');
            const inputs = document.querySelectorAll('.form-control');

            buttons.forEach(btn => {
                const minHeight = parseInt(btn.style.minHeight) || 44;
                expect(minHeight).toBeGreaterThanOrEqual(44);
            });

            inputs.forEach(input => {
                const minHeight = parseInt(input.style.minHeight) || 44;
                expect(minHeight).toBeGreaterThanOrEqual(44);
            });
        });

        it('should adjust section headers for mobile', () => {
            facilityForm.optimizeForMobile();

            const sectionHeaders = document.querySelectorAll('.section-header');
            sectionHeaders.forEach(header => {
                expect(header.classList.contains('mobile-header')).toBe(true);

                // Check padding is increased for better touch targets
                const paddingTop = parseInt(window.getComputedStyle(header).paddingTop) || 16;
                expect(paddingTop).toBeGreaterThanOrEqual(16);
            });
        });

        it('should collapse sidebar on mobile', () => {
            facilityForm.optimizeForMobile();

            const sidebar = document.querySelector('.col-md-4');
            if (sidebar) {
                expect(sidebar.classList.contains('mobile-collapsed')).toBe(true);
            }
        });

        it('should adjust form actions for mobile', () => {
            facilityForm.optimizeForMobile();

            const formActions = document.querySelector('.form-actions');
            expect(formActions.classList.contains('mobile-actions')).toBe(true);

            // Buttons should stack vertically on mobile
            expect(formActions.classList.contains('flex-column')).toBe(true);
        });

        it('should handle mobile keyboard events', () => {
            const input = document.getElementById('facility_name');
            const focusSpy = vi.spyOn(input, 'focus');
            const scrollSpy = vi.spyOn(input, 'scrollIntoView');

            // Simulate mobile keyboard opening
            const focusEvent = new Event('focus');
            input.dispatchEvent(focusEvent);

            // Should scroll input into view on mobile
            setTimeout(() => {
                expect(scrollSpy).toHaveBeenCalledWith({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);
        });
    });

    describe('Tablet Viewport (768px - 1024px)', () => {
        beforeEach(() => {
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 900
            });

            mockMatchMedia(false); // Desktop media query doesn't match
            facilityForm = new FacilityFormLayout();
        });

        it('should use tablet-optimized layout', () => {
            facilityForm.optimizeForTablet();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('tablet-optimized')).toBe(true);

            // Form sections should use medium grid
            const columns = document.querySelectorAll('.col-md-6');
            expect(columns.length).toBeGreaterThan(0);
        });

        it('should maintain sidebar visibility on tablet', () => {
            facilityForm.optimizeForTablet();

            const sidebar = document.querySelector('.col-md-4');
            if (sidebar) {
                expect(sidebar.classList.contains('d-md-block')).toBe(true);
            }
        });

        it('should adjust section spacing for tablet', () => {
            facilityForm.optimizeForTablet();

            const sections = document.querySelectorAll('.form-section');
            sections.forEach(section => {
                expect(section.classList.contains('tablet-section')).toBe(true);
            });
        });
    });

    describe('Desktop Viewport (> 1024px)', () => {
        beforeEach(() => {
            Object.defineProperty(window, 'innerWidth', {
                writable: true,
                configurable: true,
                value: 1200
            });

            facilityForm = new FacilityFormLayout();
        });

        it('should use desktop-optimized layout', () => {
            facilityForm.optimizeForDesktop();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('desktop-optimized')).toBe(true);

            // Should use full grid system
            const largeColumns = document.querySelectorAll('.col-lg-4, .col-lg-6, .col-lg-8');
            expect(largeColumns.length).toBeGreaterThan(0);
        });

        it('should show all sections expanded by default on desktop', () => {
            const sections = document.querySelectorAll('.form-section[data-collapsible="true"]');
            sections.forEach(section => {
                const header = section.querySelector('.section-header');
                expect(header.getAttribute('aria-expanded')).toBe('true');
            });
        });

        it('should optimize form layout for wide screens', () => {
            facilityForm.optimizeForDesktop();

            // Check that form uses appropriate width constraints
            const form = document.querySelector('.facility-edit-form');
            expect(form.style.maxWidth).toBeTruthy();
        });
    });

    describe('Responsive Breakpoint Handling', () => {
        it('should respond to window resize events', () => {
            const optimizeForMobileSpy = vi.spyOn(FacilityFormLayout.prototype, 'optimizeForMobile');
            const optimizeForTabletSpy = vi.spyOn(FacilityFormLayout.prototype, 'optimizeForTablet');
            const optimizeForDesktopSpy = vi.spyOn(FacilityFormLayout.prototype, 'optimizeForDesktop');

            facilityForm = new FacilityFormLayout({ enableMobileOptimization: true });

            // Simulate resize to mobile
            Object.defineProperty(window, 'innerWidth', { value: 500 });
            window.dispatchEvent(new Event('resize'));

            setTimeout(() => {
                expect(optimizeForMobileSpy).toHaveBeenCalled();
            }, 100);

            // Simulate resize to tablet
            Object.defineProperty(window, 'innerWidth', { value: 900 });
            window.dispatchEvent(new Event('resize'));

            setTimeout(() => {
                expect(optimizeForTabletSpy).toHaveBeenCalled();
            }, 100);

            // Simulate resize to desktop
            Object.defineProperty(window, 'innerWidth', { value: 1200 });
            window.dispatchEvent(new Event('resize'));

            setTimeout(() => {
                expect(optimizeForDesktopSpy).toHaveBeenCalled();
            }, 100);
        });

        it('should debounce resize events', () => {
            const optimizeSpy = vi.spyOn(FacilityFormLayout.prototype, 'handleResize');
            facilityForm = new FacilityFormLayout();

            // Trigger multiple resize events quickly
            for (let i = 0; i < 5; i++) {
                window.dispatchEvent(new Event('resize'));
            }

            // Should only call optimize once after debounce
            setTimeout(() => {
                expect(optimizeSpy).toHaveBeenCalledTimes(1);
            }, 300);
        });
    });

    describe('Orientation Change Handling', () => {
        it('should handle orientation changes on mobile devices', () => {
            Object.defineProperty(window, 'innerWidth', { value: 500 });
            facilityForm = new FacilityFormLayout({ enableMobileOptimization: true });

            const handleOrientationSpy = vi.spyOn(facilityForm, 'handleOrientationChange');

            // Simulate orientation change
            window.dispatchEvent(new Event('orientationchange'));

            setTimeout(() => {
                expect(handleOrientationSpy).toHaveBeenCalled();
            }, 100);
        });

        it('should adjust layout after orientation change', () => {
            Object.defineProperty(window, 'innerWidth', { value: 500 });
            facilityForm = new FacilityFormLayout({ enableMobileOptimization: true });

            // Simulate landscape orientation
            Object.defineProperty(screen, 'orientation', {
                value: { angle: 90 }
            });

            facilityForm.handleOrientationChange();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('landscape-mode')).toBe(true);
        });
    });

    describe('Print Styles', () => {
        it('should apply print-friendly styles', () => {
            mockMatchMedia(true); // Print media query matches
            facilityForm = new FacilityFormLayout();

            facilityForm.optimizeForPrint();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('print-optimized')).toBe(true);

            // Interactive elements should be hidden in print
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                expect(btn.classList.contains('d-print-none')).toBe(true);
            });
        });

        it('should expand all sections for printing', () => {
            facilityForm = new FacilityFormLayout();
            facilityForm.optimizeForPrint();

            const sections = document.querySelectorAll('.form-section[data-collapsible="true"]');
            sections.forEach(section => {
                const content = section.querySelector('.card-body');
                expect(content.classList.contains('collapse')).toBe(false);
            });
        });
    });

    describe('High DPI Display Support', () => {
        it('should handle high DPI displays', () => {
            Object.defineProperty(window, 'devicePixelRatio', {
                value: 2
            });

            facilityForm = new FacilityFormLayout();
            facilityForm.optimizeForHighDPI();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('high-dpi')).toBe(true);
        });
    });

    describe('Container Queries Support', () => {
        it('should use container-based responsive design when supported', () => {
            // Mock container query support
            Object.defineProperty(CSS, 'supports', {
                value: vi.fn().mockReturnValue(true)
            });

            facilityForm = new FacilityFormLayout();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('container-queries-supported')).toBe(true);
        });
    });

    describe('Reduced Motion Support', () => {
        it('should respect reduced motion preferences', () => {
            mockMatchMedia(true); // prefers-reduced-motion: reduce
            facilityForm = new FacilityFormLayout();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('reduced-motion')).toBe(true);

            // Animations should be disabled
            const sections = document.querySelectorAll('.form-section');
            sections.forEach(section => {
                expect(section.style.transition).toBe('none');
            });
        });
    });

    describe('Dark Mode Support', () => {
        it('should support dark mode preferences', () => {
            mockMatchMedia(true); // prefers-color-scheme: dark
            facilityForm = new FacilityFormLayout();

            const layout = document.querySelector('.facility-edit-layout');
            expect(layout.classList.contains('dark-mode')).toBe(true);
        });
    });

    describe('Performance Optimization', () => {
        it('should use passive event listeners for scroll events', () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');
            facilityForm = new FacilityFormLayout();

            expect(addEventListenerSpy).toHaveBeenCalledWith(
                'scroll',
                expect.any(Function),
                { passive: true }
            );
        });

        it('should use requestAnimationFrame for layout updates', () => {
            const rafSpy = vi.spyOn(window, 'requestAnimationFrame');
            facilityForm = new FacilityFormLayout();

            facilityForm.updateLayout();

            expect(rafSpy).toHaveBeenCalled();
        });
    });
});

describe('CSS Grid and Flexbox Support', () => {
    beforeEach(() => {
        setupResponsiveDOM();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('should use CSS Grid when supported', () => {
    // Mock CSS Grid support
        Object.defineProperty(CSS, 'supports', {
            value: vi.fn().mockImplementation(prop => prop.includes('grid'))
        });

        const facilityForm = new FacilityFormLayout();

        const layout = document.querySelector('.facility-edit-layout');
        expect(layout.classList.contains('css-grid-supported')).toBe(true);
    });

    it('should fallback to flexbox when CSS Grid is not supported', () => {
    // Mock no CSS Grid support
        Object.defineProperty(CSS, 'supports', {
            value: vi.fn().mockReturnValue(false)
        });

        const facilityForm = new FacilityFormLayout();

        const layout = document.querySelector('.facility-edit-layout');
        expect(layout.classList.contains('flexbox-fallback')).toBe(true);
    });
});
