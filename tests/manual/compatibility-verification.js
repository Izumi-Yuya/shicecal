/**
 * Manual Compatibility Verification Script
 * Run this in the browser console on facility detail pages
 */

(function () {
  'use strict';

  const CompatibilityTester = {
    results: {
      browserCompatibility: {},
      responsiveDesign: {},
      existingFunctionality: {},
      performance: {},
      accessibility: {}
    },

    init() {
      console.log('🔍 Starting Detail Card Compatibility Verification...');
      this.testBrowserCompatibility();
      this.testResponsiveDesign();
      this.testExistingFunctionality();
      this.testPerformance();
      this.testAccessibility();
      this.displayResults();
    },

    testBrowserCompatibility() {
      console.log('📱 Testing Browser Compatibility...');

      // CSS Variables support
      this.results.browserCompatibility.cssVariables = CSS.supports('color', 'var(--test)');

      // Flexbox support
      this.results.browserCompatibility.flexbox = CSS.supports('display', 'flex');

      // Grid support
      this.results.browserCompatibility.grid = CSS.supports('display', 'grid');

      // LocalStorage support
      this.results.browserCompatibility.localStorage = typeof (Storage) !== "undefined";

      // ES6 support
      try {
        eval('const test = () => {};');
        this.results.browserCompatibility.es6 = true;
      } catch (e) {
        this.results.browserCompatibility.es6 = false;
      }

      // Media queries support
      this.results.browserCompatibility.mediaQueries = window.matchMedia &&
        window.matchMedia('(min-width: 1024px)').matches !== undefined;

      // Touch events
      this.results.browserCompatibility.touchEvents = 'ontouchstart' in window;

      // Pointer events
      this.results.browserCompatibility.pointerEvents = 'onpointerdown' in window;

      console.log('✅ Browser compatibility tests completed');
    },

    testResponsiveDesign() {
      console.log('📐 Testing Responsive Design...');

      const screenWidth = window.innerWidth;
      const screenHeight = window.innerHeight;

      this.results.responsiveDesign.currentSize = {
        width: screenWidth,
        height: screenHeight,
        category: this.getScreenCategory(screenWidth)
      };

      // Test detail cards at current size
      const detailCards = document.querySelectorAll('.detail-card-improved');
      this.results.responsiveDesign.detailCardsFound = detailCards.length;

      // Test toggle buttons
      const toggleButtons = document.querySelectorAll('.empty-fields-toggle');
      this.results.responsiveDesign.toggleButtonsFound = toggleButtons.length;

      // Test layout integrity
      this.results.responsiveDesign.layoutIntegrity = this.checkLayoutIntegrity();

      console.log('✅ Responsive design tests completed');
    },

    testExistingFunctionality() {
      console.log('🔧 Testing Existing Functionality Integration...');

      // Comment functionality
      const commentToggles = document.querySelectorAll('.comment-toggle');
      const commentSections = document.querySelectorAll('.comment-section');
      const commentInputs = document.querySelectorAll('.comment-input');

      this.results.existingFunctionality.comments = {
        toggleButtons: commentToggles.length,
        sections: commentSections.length,
        inputs: commentInputs.length,
        working: this.testCommentFunctionality()
      };

      // Edit buttons
      const editButtons = document.querySelectorAll('a[href*="/edit"]');
      this.results.existingFunctionality.editButtons = {
        found: editButtons.length,
        accessible: this.testEditButtonAccessibility(editButtons)
      };

      // Tab navigation
      const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
      const tabPanes = document.querySelectorAll('.tab-pane');

      this.results.existingFunctionality.tabs = {
        buttons: tabButtons.length,
        panes: tabPanes.length,
        working: this.testTabFunctionality()
      };

      // Bootstrap components
      this.results.existingFunctionality.bootstrap = {
        tooltips: document.querySelectorAll('[data-bs-toggle="tooltip"]').length,
        modals: document.querySelectorAll('.modal').length,
        cards: document.querySelectorAll('.card').length
      };

      console.log('✅ Existing functionality tests completed');
    },

    testPerformance() {
      console.log('⚡ Testing Performance...');

      const startTime = performance.now();

      // Test detail card controller initialization
      try {
        if (window.ShiseCal && window.ShiseCal.detailCard) {
          window.ShiseCal.detailCard.refresh();
        }
      } catch (error) {
        console.warn('Detail card refresh failed:', error);
      }

      const endTime = performance.now();

      this.results.performance = {
        initTime: endTime - startTime,
        memoryUsage: this.getMemoryUsage(),
        domElements: document.querySelectorAll('*').length,
        detailCardElements: document.querySelectorAll('.detail-card-improved, .detail-row, .detail-label, .detail-value').length
      };

      console.log('✅ Performance tests completed');
    },

    testAccessibility() {
      console.log('♿ Testing Accessibility...');

      // ARIA attributes
      const ariaElements = document.querySelectorAll('[aria-label], [aria-expanded], [aria-controls], [role]');

      // Screen reader text
      const srOnlyElements = document.querySelectorAll('.sr-only');

      // Keyboard navigation
      const focusableElements = document.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');

      // Color contrast (basic check)
      const contrastIssues = this.checkColorContrast();

      this.results.accessibility = {
        ariaElements: ariaElements.length,
        screenReaderText: srOnlyElements.length,
        focusableElements: focusableElements.length,
        contrastIssues: contrastIssues,
        keyboardNavigation: this.testKeyboardNavigation()
      };

      console.log('✅ Accessibility tests completed');
    },

    // Helper methods
    getScreenCategory(width) {
      if (width >= 2560) return '4K';
      if (width >= 1920) return 'Full HD';
      if (width >= 1366) return 'Laptop';
      if (width >= 1024) return 'Desktop';
      if (width >= 768) return 'Tablet';
      return 'Mobile';
    },

    checkLayoutIntegrity() {
      const issues = [];

      // Check for overlapping elements
      const detailRows = document.querySelectorAll('.detail-row');
      detailRows.forEach((row, index) => {
        const rect = row.getBoundingClientRect();
        if (rect.height < 10) {
          issues.push(`Row ${index} has insufficient height`);
        }
      });

      // Check for proper spacing
      const cards = document.querySelectorAll('.detail-card-improved');
      cards.forEach((card, index) => {
        const rect = card.getBoundingClientRect();
        if (rect.width < 200) {
          issues.push(`Card ${index} is too narrow`);
        }
      });

      return issues;
    },

    testCommentFunctionality() {
      try {
        const commentToggle = document.querySelector('.comment-toggle');
        if (!commentToggle) return false;

        const section = commentToggle.dataset.section;
        const commentSection = document.querySelector(`.comment-section[data-section="${section}"]`);

        return commentSection !== null;
      } catch (error) {
        return false;
      }
    },

    testEditButtonAccessibility(editButtons) {
      let accessibleCount = 0;

      editButtons.forEach(button => {
        const hasIcon = button.querySelector('i');
        const hasText = button.textContent.trim().length > 0;

        if (hasIcon && hasText) {
          accessibleCount++;
        }
      });

      return accessibleCount;
    },

    testTabFunctionality() {
      try {
        const activeTab = document.querySelector('.nav-link.active');
        const activePane = document.querySelector('.tab-pane.show.active');

        return activeTab !== null && activePane !== null;
      } catch (error) {
        return false;
      }
    },

    getMemoryUsage() {
      if (performance.memory) {
        return {
          used: Math.round(performance.memory.usedJSHeapSize / 1024 / 1024),
          total: Math.round(performance.memory.totalJSHeapSize / 1024 / 1024),
          limit: Math.round(performance.memory.jsHeapSizeLimit / 1024 / 1024)
        };
      }
      return null;
    },

    checkColorContrast() {
      const issues = [];

      // Check detail labels and values
      const labels = document.querySelectorAll('.detail-label');
      const values = document.querySelectorAll('.detail-value');

      // Basic contrast check (simplified)
      labels.forEach((label, index) => {
        const style = window.getComputedStyle(label);
        const color = style.color;
        const backgroundColor = style.backgroundColor;

        if (color === backgroundColor) {
          issues.push(`Label ${index} has poor contrast`);
        }
      });

      return issues;
    },

    testKeyboardNavigation() {
      const focusableElements = document.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
      let accessibleCount = 0;

      focusableElements.forEach(element => {
        // Check if element is visible and not disabled
        const style = window.getComputedStyle(element);
        const isVisible = style.display !== 'none' && style.visibility !== 'hidden';
        const isEnabled = !element.disabled;

        if (isVisible && isEnabled) {
          accessibleCount++;
        }
      });

      return {
        total: focusableElements.length,
        accessible: accessibleCount
      };
    },

    displayResults() {
      console.log('\n📊 COMPATIBILITY TEST RESULTS');
      console.log('================================');

      // Browser Compatibility
      console.log('\n🌐 Browser Compatibility:');
      Object.entries(this.results.browserCompatibility).forEach(([feature, supported]) => {
        console.log(`  ${supported ? '✅' : '❌'} ${feature}: ${supported}`);
      });

      // Responsive Design
      console.log('\n📱 Responsive Design:');
      console.log(`  Screen: ${this.results.responsiveDesign.currentSize.width}x${this.results.responsiveDesign.currentSize.height} (${this.results.responsiveDesign.currentSize.category})`);
      console.log(`  Detail Cards: ${this.results.responsiveDesign.detailCardsFound}`);
      console.log(`  Toggle Buttons: ${this.results.responsiveDesign.toggleButtonsFound}`);
      console.log(`  Layout Issues: ${this.results.responsiveDesign.layoutIntegrity.length}`);

      // Existing Functionality
      console.log('\n🔧 Existing Functionality:');
      console.log(`  Comment Toggles: ${this.results.existingFunctionality.comments.toggleButtons}`);
      console.log(`  Comment Sections: ${this.results.existingFunctionality.comments.sections}`);
      console.log(`  Edit Buttons: ${this.results.existingFunctionality.editButtons.found}`);
      console.log(`  Tab Buttons: ${this.results.existingFunctionality.tabs.buttons}`);
      console.log(`  Bootstrap Cards: ${this.results.existingFunctionality.bootstrap.cards}`);

      // Performance
      console.log('\n⚡ Performance:');
      console.log(`  Init Time: ${this.results.performance.initTime.toFixed(2)}ms`);
      console.log(`  DOM Elements: ${this.results.performance.domElements}`);
      console.log(`  Detail Card Elements: ${this.results.performance.detailCardElements}`);
      if (this.results.performance.memoryUsage) {
        console.log(`  Memory Usage: ${this.results.performance.memoryUsage.used}MB / ${this.results.performance.memoryUsage.total}MB`);
      }

      // Accessibility
      console.log('\n♿ Accessibility:');
      console.log(`  ARIA Elements: ${this.results.accessibility.ariaElements}`);
      console.log(`  Screen Reader Text: ${this.results.accessibility.screenReaderText}`);
      console.log(`  Focusable Elements: ${this.results.accessibility.focusableElements}`);
      console.log(`  Keyboard Navigation: ${this.results.accessibility.keyboardNavigation.accessible}/${this.results.accessibility.keyboardNavigation.total}`);
      console.log(`  Contrast Issues: ${this.results.accessibility.contrastIssues.length}`);

      // Overall Assessment
      console.log('\n🎯 Overall Assessment:');
      const overallScore = this.calculateOverallScore();
      console.log(`  Compatibility Score: ${overallScore.score}% (${overallScore.grade})`);

      if (overallScore.issues.length > 0) {
        console.log('\n⚠️  Issues Found:');
        overallScore.issues.forEach(issue => {
          console.log(`  - ${issue}`);
        });
      }

      console.log('\n✅ Compatibility verification completed!');

      // Return results for programmatic access
      return this.results;
    },

    calculateOverallScore() {
      let score = 100;
      const issues = [];

      // Browser compatibility (20 points)
      const browserFeatures = Object.values(this.results.browserCompatibility);
      const supportedFeatures = browserFeatures.filter(Boolean).length;
      const browserScore = (supportedFeatures / browserFeatures.length) * 20;
      score = Math.min(score, score - (20 - browserScore));

      if (browserScore < 15) {
        issues.push('Limited browser feature support');
      }

      // Responsive design (20 points)
      if (this.results.responsiveDesign.detailCardsFound === 0) {
        score -= 10;
        issues.push('No detail cards found');
      }

      if (this.results.responsiveDesign.layoutIntegrity.length > 0) {
        score -= 5;
        issues.push('Layout integrity issues detected');
      }

      // Existing functionality (30 points)
      if (this.results.existingFunctionality.comments.toggleButtons === 0) {
        score -= 5;
        issues.push('Comment functionality not found');
      }

      if (this.results.existingFunctionality.editButtons.found === 0) {
        score -= 5;
        issues.push('Edit buttons not found');
      }

      if (this.results.existingFunctionality.tabs.buttons === 0) {
        score -= 5;
        issues.push('Tab navigation not found');
      }

      // Performance (15 points)
      if (this.results.performance.initTime > 100) {
        score -= 5;
        issues.push('Slow initialization time');
      }

      if (this.results.performance.domElements > 5000) {
        score -= 5;
        issues.push('High DOM element count');
      }

      // Accessibility (15 points)
      if (this.results.accessibility.ariaElements < 5) {
        score -= 5;
        issues.push('Limited ARIA support');
      }

      if (this.results.accessibility.contrastIssues.length > 0) {
        score -= 5;
        issues.push('Color contrast issues');
      }

      const grade = score >= 90 ? 'A' : score >= 80 ? 'B' : score >= 70 ? 'C' : score >= 60 ? 'D' : 'F';

      return {
        score: Math.max(0, Math.round(score)),
        grade,
        issues
      };
    }
  };

  // Auto-run if in browser environment
  if (typeof window !== 'undefined') {
    // Make available globally
    window.CompatibilityTester = CompatibilityTester;

    // Auto-run after a short delay to ensure page is loaded
    setTimeout(() => {
      CompatibilityTester.init();
    }, 1000);
  }

  // Export for module environments
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = CompatibilityTester;
  }

})();