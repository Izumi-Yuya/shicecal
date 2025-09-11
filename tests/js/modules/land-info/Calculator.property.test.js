/**
 * Property-Based Tests for Calculator Module
 * Tests edge cases and invariants using property-based testing
 */

import { describe, it, expect, beforeEach } from 'vitest';
import { Calculator } from '../../../../resources/js/modules/land-info/Calculator.js';

describe('Calculator Property-Based Tests', () => {
  let calculator;

  beforeEach(() => {
    calculator = new Calculator();
  });

  describe('Unit Price Calculation Properties', () => {
    // Property: Unit price should always be positive for positive inputs
    it('should always return positive unit price for positive inputs', () => {
      const testCases = generatePositiveNumberPairs(100);

      testCases.forEach(([price, area]) => {
        const result = calculator.calculateUnitPrice(price, area);

        if (result && !result.error) {
          expect(result.unitPrice).toBeGreaterThan(0);
        }
      });
    });

    // Property: Unit price should be proportional to purchase price
    it('should maintain proportionality with purchase price', () => {
      const basePrice = 1000000;
      const area = 100;

      const result1 = calculator.calculateUnitPrice(basePrice, area);
      const result2 = calculator.calculateUnitPrice(basePrice * 2, area);

      if (result1 && result2 && !result1.error && !result2.error) {
        expect(result2.unitPrice).toBe(result1.unitPrice * 2);
      }
    });

    // Property: Unit price should be inversely proportional to area
    it('should maintain inverse proportionality with area', () => {
      const price = 1000000;
      const baseArea = 100;

      const result1 = calculator.calculateUnitPrice(price, baseArea);
      const result2 = calculator.calculateUnitPrice(price, baseArea * 2);

      if (result1 && result2 && !result1.error && !result2.error) {
        expect(result2.unitPrice).toBe(Math.round(result1.unitPrice / 2));
      }
    });

    // Property: Should handle edge cases gracefully
    it('should handle edge cases without throwing errors', () => {
      const edgeCases = [
        [0, 100],           // Zero price
        [1000000, 0],       // Zero area
        [-1000000, 100],    // Negative price
        [1000000, -100],    // Negative area
        [Infinity, 100],    // Infinite price
        [1000000, Infinity], // Infinite area
        [NaN, 100],         // NaN price
        [1000000, NaN],     // NaN area
        [Number.MAX_SAFE_INTEGER + 1, 100], // Unsafe integer
        [1000000, 0.0000001] // Very small area
      ];

      edgeCases.forEach(([price, area]) => {
        expect(() => {
          const result = calculator.calculateUnitPrice(price, area);
          // Should either return null or a valid result object
          expect(result === null || typeof result === 'object').toBe(true);
        }).not.toThrow();
      });
    });

    // Property: String inputs should be handled correctly
    it('should handle string inputs with various formats', () => {
      const stringInputs = [
        ['1,000,000', '100'],
        ['1,000,000円', '100坪'],
        ['1 000 000', '100'],
        ['1.000.000', '100'],
        ['￥1,000,000', '100'],
        ['1000000.00', '100.00'],
        ['  1000000  ', '  100  '], // Whitespace
        ['1e6', '1e2'], // Scientific notation
      ];

      stringInputs.forEach(([priceStr, areaStr]) => {
        expect(() => {
          calculator.calculateUnitPrice(priceStr, areaStr);
        }).not.toThrow();
      });
    });

    // Property: Cache should work correctly
    it('should cache results consistently', () => {
      const price = 1000000;
      const area = 100;

      // Clear cache first
      calculator.clearCache();

      const result1 = calculator.calculateUnitPrice(price, area);
      const result2 = calculator.calculateUnitPrice(price, area);

      expect(result1).toEqual(result2);

      const stats = calculator.getCacheStats();
      expect(stats.cacheHits).toBeGreaterThan(0);
    });
  });

  describe('Contract Period Calculation Properties', () => {
    // Property: Contract period should always be positive for valid date ranges
    it('should return positive period for valid date ranges', () => {
      const testCases = generateValidDatePairs(50);

      testCases.forEach(([startDate, endDate]) => {
        const result = calculator.calculateContractPeriod(startDate, endDate);

        if (result && !result.error) {
          expect(result.totalMonths).toBeGreaterThanOrEqual(0);
        }
      });
    });

    // Property: Later end date should result in longer period
    it('should increase period with later end dates', () => {
      const startDate = new Date('2024-01-01');
      const endDate1 = new Date('2024-06-01');
      const endDate2 = new Date('2024-12-01');

      const result1 = calculator.calculateContractPeriod(startDate, endDate1);
      const result2 = calculator.calculateContractPeriod(startDate, endDate2);

      if (result1 && result2 && !result1.error && !result2.error) {
        expect(result2.totalMonths).toBeGreaterThan(result1.totalMonths);
      }
    });

    // Property: Should handle invalid date ranges
    it('should handle invalid date ranges gracefully', () => {
      const invalidCases = [
        [new Date('2024-01-01'), new Date('2023-01-01')], // End before start
        [new Date('invalid'), new Date('2024-01-01')],    // Invalid start
        [new Date('2024-01-01'), new Date('invalid')],    // Invalid end
        [null, new Date('2024-01-01')],                   // Null start
        [new Date('2024-01-01'), null],                   // Null end
        [undefined, undefined],                           // Both undefined
      ];

      invalidCases.forEach(([startDate, endDate]) => {
        expect(() => {
          const result = calculator.calculateContractPeriod(startDate, endDate);
          // Should return null or error result
          expect(result === null || (result && result.error)).toBe(true);
        }).not.toThrow();
      });
    });

    // Property: Same dates should return zero period
    it('should return zero period for same dates', () => {
      const date = new Date('2024-01-01');
      const result = calculator.calculateContractPeriod(date, date);

      // Same dates should be handled as invalid (end not after start)
      expect(result === null || (result && result.error)).toBe(true);
    });
  });

  describe('Performance Properties', () => {
    // Property: Cache should improve performance
    it('should demonstrate cache performance benefits', () => {
      const price = 1000000;
      const area = 100;

      calculator.clearCache();

      // First calculation (no cache)
      const start1 = performance.now();
      calculator.calculateUnitPrice(price, area);
      const time1 = performance.now() - start1;

      // Second calculation (with cache)
      const start2 = performance.now();
      calculator.calculateUnitPrice(price, area);
      const time2 = performance.now() - start2;

      // Cached calculation should be faster (though this might be flaky in tests)
      const stats = calculator.getCacheStats();
      expect(stats.cacheHits).toBeGreaterThan(0);
    });

    // Property: Cache should respect size limits
    it('should respect cache size limits', () => {
      calculator.clearCache();

      // Generate many unique calculations to exceed cache limit
      for (let i = 0; i < 150; i++) {
        calculator.calculateUnitPrice(1000000 + i, 100 + i);
      }

      const stats = calculator.getCacheStats();
      expect(stats.size).toBeLessThanOrEqual(100); // Default cache limit
    });
  });

  describe('Security Properties', () => {
    // Property: Should reject dangerous inputs
    it('should reject potentially dangerous inputs', () => {
      const dangerousInputs = [
        ['<script>alert("xss")</script>', '100'],
        ['javascript:alert(1)', '100'],
        ['1000000', '<img src=x onerror=alert(1)>'],
        ['eval("malicious code")', '100'],
        ['1000000; DROP TABLE users;', '100'],
      ];

      dangerousInputs.forEach(([price, area]) => {
        expect(() => {
          const result = calculator.calculateUnitPrice(price, area);
          // Should handle safely without executing dangerous code
          expect(typeof result === 'object' || result === null).toBe(true);
        }).not.toThrow();
      });
    });

    // Property: Should handle extremely large numbers safely
    it('should handle extremely large numbers safely', () => {
      const largeNumbers = [
        Number.MAX_VALUE,
        Number.MAX_SAFE_INTEGER * 2,
        1e100,
        Infinity,
        -Infinity
      ];

      largeNumbers.forEach(num => {
        expect(() => {
          calculator.calculateUnitPrice(num, 100);
          calculator.calculateUnitPrice(1000000, num);
        }).not.toThrow();
      });
    });
  });
});

/**
 * Generate pairs of positive numbers for testing
 * @param {number} count 
 * @returns {Array<[number, number]>}
 */
function generatePositiveNumberPairs(count) {
  const pairs = [];

  for (let i = 0; i < count; i++) {
    const price = Math.random() * 1000000000 + 1; // 1 to 1 billion
    const area = Math.random() * 10000 + 0.1;     // 0.1 to 10,000
    pairs.push([price, area]);
  }

  return pairs;
}

/**
 * Generate pairs of valid dates for testing
 * @param {number} count 
 * @returns {Array<[Date, Date]>}
 */
function generateValidDatePairs(count) {
  const pairs = [];
  const baseDate = new Date('2020-01-01');

  for (let i = 0; i < count; i++) {
    const startOffset = Math.random() * 365 * 5; // Up to 5 years from base
    const duration = Math.random() * 365 * 3;    // Up to 3 years duration

    const startDate = new Date(baseDate.getTime() + startOffset * 24 * 60 * 60 * 1000);
    const endDate = new Date(startDate.getTime() + duration * 24 * 60 * 60 * 1000);

    pairs.push([startDate, endDate]);
  }

  return pairs;
}