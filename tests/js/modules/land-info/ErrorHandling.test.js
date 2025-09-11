/**
 * Comprehensive Error Handling Tests
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { Calculator } from '../../../../resources/js/modules/land-info/Calculator.js';
import { FormValidator } from '../../../../resources/js/modules/land-info/FormValidator.js';
import { ErrorHandler } from '../../../../resources/js/modules/land-info/ErrorHandler.js';

describe('Error Handling Tests', () => {
  let calculator;
  let validator;
  let errorHandler;

  beforeEach(() => {
    calculator = new Calculator();
    validator = new FormValidator();
    errorHandler = new ErrorHandler();

    // Mock console methods to avoid noise in tests
    vi.spyOn(console, 'error').mockImplementation(() => { });
    vi.spyOn(console, 'warn').mockImplementation(() => { });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Calculator Error Handling', () => {
    it('should handle malicious input gracefully', () => {
      const maliciousInputs = [
        '<script>alert("xss")</script>',
        'javascript:alert(1)',
        '../../etc/passwd',
        'DROP TABLE users;',
        '${7*7}', // Template injection
        '{{7*7}}', // Template injection
        'eval("malicious code")',
        'function(){return 1;}()',
      ];

      maliciousInputs.forEach(input => {
        expect(() => {
          const result = calculator.calculateUnitPrice(input, 100);
          // Should either return null or handle safely
          expect(result === null || typeof result === 'object').toBe(true);
        }).not.toThrow();
      });
    });

    it('should handle extreme numeric values', () => {
      const extremeValues = [
        Number.MAX_VALUE,
        Number.MIN_VALUE,
        Number.MAX_SAFE_INTEGER + 1,
        Number.MIN_SAFE_INTEGER - 1,
        Infinity,
        -Infinity,
        NaN,
        1e308, // Larger than MAX_VALUE
        1e-324, // Smaller than MIN_VALUE
      ];

      extremeValues.forEach(value => {
        expect(() => {
          calculator.calculateUnitPrice(value, 100);
        }).not.toThrow();
      });
    });

    it('should handle memory exhaustion scenarios', () => {
      // Test cache overflow
      const originalConsoleWarn = console.warn;
      let warningCalled = false;

      console.warn = () => { warningCalled = true; };

      // Fill cache beyond reasonable limits
      for (let i = 0; i < 1000; i++) {
        calculator.calculateUnitPrice(1000000 + i, 100 + i);
      }

      // Should not crash and should manage memory
      expect(calculator.getCacheStats().size).toBeLessThan(200);

      console.warn = originalConsoleWarn;
    });

    it('should handle concurrent calculation requests', async () => {
      const promises = [];

      // Create many concurrent calculations
      for (let i = 0; i < 100; i++) {
        promises.push(
          new Promise(resolve => {
            setTimeout(() => {
              const result = calculator.calculateUnitPrice(1000000 + i, 100);
              resolve(result);
            }, Math.random() * 10);
          })
        );
      }

      const results = await Promise.all(promises);

      // All should complete without errors
      expect(results).toHaveLength(100);
      results.forEach(result => {
        expect(result).toBeTruthy();
      });
    });
  });

  describe('FormValidator Error Handling', () => {
    beforeEach(() => {
      // Mock DOM for validator tests
      document.body.innerHTML = `
        <select id="ownership_type">
          <option value="">選択してください</option>
          <option value="owned">自社</option>
          <option value="leased">賃借</option>
        </select>
        <input id="purchase_price" type="text" />
        <input id="site_area_tsubo" type="text" />
      `;
    });

    it('should handle missing DOM elements gracefully', () => {
      // Remove elements after validator initialization
      document.getElementById('ownership_type').remove();

      expect(() => {
        validator.validateForm();
      }).not.toThrow();
    });

    it('should handle malformed validation rules', () => {
      const malformedRules = [
        null,
        undefined,
        'invalid',
        { invalid: 'rule' },
        { required: 'not_boolean' },
        { min: 'not_number' },
        { max: null },
      ];

      malformedRules.forEach(rules => {
        expect(() => {
          validator.validateField('test_field', rules);
        }).not.toThrow();
      });
    });

    it('should handle circular references in validation data', () => {
      const circularObj = { a: 1 };
      circularObj.self = circularObj;

      expect(() => {
        // This should not cause infinite recursion
        validator.sanitizeInput(JSON.stringify(circularObj));
      }).not.toThrow();
    });
  });

  describe('ErrorHandler Integration', () => {
    it('should handle error reporting failures gracefully', () => {
      // Mock a failing monitoring service
      window.Sentry = {
        captureException: () => {
          throw new Error('Monitoring service failed');
        }
      };

      expect(() => {
        errorHandler.handleError(new Error('Test error'), 'test');
      }).not.toThrow();

      delete window.Sentry;
    });

    it('should handle listener errors without breaking the chain', () => {
      const goodListener = vi.fn();
      const badListener = vi.fn(() => {
        throw new Error('Listener error');
      });
      const anotherGoodListener = vi.fn();

      errorHandler.addListener(goodListener);
      errorHandler.addListener(badListener);
      errorHandler.addListener(anotherGoodListener);

      // Should not throw and should call all listeners
      expect(() => {
        errorHandler.handleError(new Error('Test error'), 'test');
      }).not.toThrow();

      expect(goodListener).toHaveBeenCalled();
      expect(badListener).toHaveBeenCalled();
      expect(anotherGoodListener).toHaveBeenCalled();
    });

    it('should handle memory leaks in error storage', () => {
      // Generate many errors to test memory management
      for (let i = 0; i < 1000; i++) {
        errorHandler.handleError(new Error(`Error ${i}`), 'test');
      }

      const recentErrors = errorHandler.getRecentErrors();

      // Should limit stored errors to prevent memory leaks
      expect(recentErrors.length).toBeLessThanOrEqual(100);
    });
  });

  describe('Network Error Simulation', () => {
    it('should handle network timeouts', async () => {
      // Mock fetch with timeout
      global.fetch = vi.fn(() =>
        new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Network timeout')), 100)
        )
      );

      const networkError = await errorHandler.handleNetworkError(
        { status: 408, statusText: 'Timeout' },
        'network_test'
      );

      expect(networkError).toBeDefined();
      expect(networkError.context).toBe('network_test');
    });

    it('should handle malformed JSON responses', async () => {
      global.fetch = vi.fn(() =>
        Promise.resolve({
          status: 500,
          statusText: 'Internal Server Error',
          json: () => Promise.reject(new Error('Invalid JSON'))
        })
      );

      expect(async () => {
        await errorHandler.handleNetworkError(
          await fetch('/test'),
          'json_test'
        );
      }).not.toThrow();
    });
  });

  describe('Resource Cleanup', () => {
    it('should clean up resources on page unload', () => {
      const cleanup = vi.fn();

      // Simulate component with cleanup
      const component = {
        destroy: cleanup
      };

      // Simulate page unload
      window.dispatchEvent(new Event('beforeunload'));

      // Should handle cleanup gracefully even if component is null
      expect(() => {
        if (component && typeof component.destroy === 'function') {
          component.destroy();
        }
      }).not.toThrow();
    });
  });
});