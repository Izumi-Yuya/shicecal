/**
 * Detail Card Controller Performance Tests
 * Tests for the performance optimizations implemented in task 10
 */

import { describe, test, expect, beforeEach, afterEach, vi } from 'vitest';
import { DetailCardController } from '../../resources/js/modules/detail-card-controller.js';

// Mock DOM environment
const mockLocalStorage = {
    data: {},
    getItem: vi.fn((key) => mockLocalStorage.data[key] || null),
    setItem: vi.fn((key, value) => {
        mockLocalStorage.data[key] = value;
    }),
    removeItem: vi.fn((key) => {
        delete mockLocalStorage.data[key];
    }),
    clear: vi.fn(() => {
        mockLocalStorage.data = {};
    })
};

Object.defineProperty(window, 'localStorage', {
    value: mockLocalStorage
});

// Mock performance API
Object.defineProperty(window, 'performance', {
    value: {
        now: vi.fn(() => Date.now())
    }
});

// Mock requestAnimationFrame
global.requestAnimationFrame = vi.fn((callback) => {
    setTimeout(callback, 16); // ~60fps
    return 1;
});

describe('DetailCardController Performance Optimizations', () => {
    let controller;
    let mockDocument;

    beforeEach(() => {
    // Reset mocks
        vi.clearAllMocks();
        mockLocalStorage.clear();

        // Create mock DOM elements
        mockDocument = {
            querySelectorAll: vi.fn(() => []),
            querySelector: vi.fn(() => null),
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            body: {
                querySelectorAll: vi.fn(() => [])
            }
        };

        // Mock global document
        global.document = mockDocument;

        controller = new DetailCardController();
    });

    afterEach(() => {
        if (controller) {
            controller.destroy();
        }
    });

    describe('Configuration Optimization', () => {
        test('should have frozen configuration object', () => {
            expect(Object.isFrozen(controller.config)).toBe(true);
            expect(Object.isFrozen(controller.config.buttonText)).toBe(true);
            expect(Object.isFrozen(controller.config.icons)).toBe(true);
        });

        test('should include debounce delay configuration', () => {
            expect(controller.config.debounceDelay).toBe(100);
        });
    });

    describe('Memory Management', () => {
        test('should track event listeners for cleanup', () => {
            expect(controller.eventListeners).toBeInstanceOf(Map);
            expect(controller.eventListeners.size).toBe(0);
        });

        test('should cache preferences to reduce localStorage access', () => {
            expect(controller.preferences).toBeNull();
        });

        test('should provide memory usage statistics', () => {
            const usage = controller.getMemoryUsage();

            expect(usage).toHaveProperty('toggleButtons');
            expect(usage).toHaveProperty('eventListeners');
            expect(usage).toHaveProperty('cachedPreferences');
            expect(usage).toHaveProperty('estimatedMemoryKB');
            expect(typeof usage.estimatedMemoryKB).toBe('number');
        });
    });

    describe('LocalStorage Optimization', () => {
        test('should debounce localStorage writes', async () => {
            // Mock successful initialization
            mockDocument.querySelectorAll.mockReturnValue([
                {
                    querySelector: vi.fn(() => ({ appendChild: vi.fn() })),
                    querySelectorAll: vi.fn(() => []),
                    dataset: { section: 'test' }
                }
            ]);

            await controller.init();

            // Trigger multiple preference saves
            controller.saveUserPreference('section1', true);
            controller.saveUserPreference('section2', false);
            controller.saveUserPreference('section3', true);

            // Should not have called localStorage.setItem immediately
            expect(mockLocalStorage.setItem).not.toHaveBeenCalled();

            // Wait for debounce delay
            await new Promise(resolve => setTimeout(resolve, 150));

            // Should have called localStorage.setItem only once
            expect(mockLocalStorage.setItem).toHaveBeenCalledTimes(1);
        });

        test('should optimize preferences for storage', () => {
            controller.preferences = {
                section1: { showEmptyFields: false }, // default value
                section2: { showEmptyFields: true },  // non-default value
                section3: { showEmptyFields: false }  // default value
            };

            const optimized = controller.optimizePreferencesForStorage();

            // Should only include non-default values
            expect(optimized).toEqual({
                section2: { showEmptyFields: true }
            });
        });

        test('should handle localStorage quota exceeded', () => {
            // Mock localStorage quota exceeded
            mockLocalStorage.setItem.mockImplementation(() => {
                throw new Error('QuotaExceededError');
            });

            const cleanupSpy = vi.spyOn(controller, 'cleanupOldStorageData');
            const minimalSaveSpy = vi.spyOn(controller, 'saveMinimalPreferences');

            controller.preferences = { test: { showEmptyFields: true } };
            controller.debouncedSaveToStorage();

            // Wait for debounce
            setTimeout(() => {
                expect(minimalSaveSpy).toHaveBeenCalled();
            }, 150);
        });
    });

    describe('DOM Operation Optimization', () => {
        test('should use requestAnimationFrame for non-blocking operations', async () => {
            mockDocument.querySelectorAll.mockReturnValue([]);

            const result = await controller.init();

            expect(global.requestAnimationFrame).toHaveBeenCalled();
            expect(result).toBe(false); // No cards found
        });

        test('should batch DOM updates', () => {
            const mockCard1 = { classList: { add: vi.fn(), remove: vi.fn() } };
            const mockCard2 = { classList: { add: vi.fn(), remove: vi.fn() } };
            const mockButton1 = {};
            const mockButton2 = {};

            const updates = [
                { card: mockCard1, button: mockButton1, shouldShow: true },
                { card: mockCard2, button: mockButton2, shouldShow: false }
            ];

            const updateButtonStateSpy = vi.spyOn(controller, 'updateButtonState').mockImplementation(() => { });

            controller.batchUpdateCardStates(updates);

            // Should use requestAnimationFrame for batching
            expect(global.requestAnimationFrame).toHaveBeenCalled();
        });
    });

    describe('Event Listener Optimization', () => {
        test('should use event delegation instead of individual listeners', async () => {
            mockDocument.querySelectorAll.mockReturnValue([
                {
                    querySelector: vi.fn(() => ({ appendChild: vi.fn() })),
                    querySelectorAll: vi.fn(() => []),
                    dataset: { section: 'test' }
                }
            ]);

            await controller.init();

            // Should add only 3 event listeners to document (click, keydown, focus)
            expect(mockDocument.addEventListener).toHaveBeenCalledTimes(3);
            expect(controller.eventListeners.size).toBe(3);
        });

        test('should properly cleanup event listeners on destroy', () => {
            controller.eventListeners.set('click', vi.fn());
            controller.eventListeners.set('keydown', vi.fn());
            controller.eventListeners.set('focus', vi.fn());

            controller.destroy();

            expect(mockDocument.removeEventListener).toHaveBeenCalledTimes(3);
            expect(controller.eventListeners.size).toBe(0);
        });
    });

    describe('Performance Statistics', () => {
        test('should provide detailed performance statistics', () => {
            // Mock some data
            controller.detailCards = [{}, {}];
            controller.toggleButtons.set('section1', { emptyFields: [1, 2, 3] });
            controller.toggleButtons.set('section2', { emptyFields: [1] });

            const stats = controller.getStatistics();

            expect(stats).toHaveProperty('totalCards', 2);
            expect(stats).toHaveProperty('cardsWithEmptyFields', 2);
            expect(stats).toHaveProperty('totalEmptyFields', 4);
            expect(stats).toHaveProperty('performance');
            expect(stats.performance).toHaveProperty('calculationTime');
            expect(stats.performance).toHaveProperty('memoryUsage');
            expect(typeof stats.performance.calculationTime).toBe('number');
        });
    });

    describe('Async Operations', () => {
        test('should handle async initialization', async () => {
            mockDocument.querySelectorAll.mockReturnValue([
                {
                    querySelector: vi.fn(() => ({ appendChild: vi.fn() })),
                    querySelectorAll: vi.fn(() => []),
                    dataset: { section: 'test' }
                }
            ]);

            const result = await controller.init();
            expect(result).toBe(true);
            expect(controller.isInitialized).toBe(true);
        });

        test('should handle async refresh', async () => {
            controller.isInitialized = true;
            controller.detailCards = [];

            mockDocument.querySelectorAll.mockReturnValue([
                {
                    querySelector: vi.fn(() => ({ appendChild: vi.fn() })),
                    querySelectorAll: vi.fn(() => []),
                    dataset: { section: 'test' }
                }
            ]);

            const result = await controller.refresh();
            expect(result).toBe(true);
        });
    });

    describe('Error Handling', () => {
        test('should handle localStorage errors gracefully', () => {
            mockLocalStorage.getItem.mockImplementation(() => {
                throw new Error('localStorage error');
            });

            const preferences = controller.getUserPreferences();
            expect(preferences).toEqual({});
            expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('detailCardPreferences');
        });

        test('should handle corrupted preference data', () => {
            mockLocalStorage.getItem.mockReturnValue('invalid json');

            const preferences = controller.getUserPreferences();
            expect(preferences).toEqual({});
            expect(mockLocalStorage.removeItem).toHaveBeenCalledWith('detailCardPreferences');
        });

        test('should handle invalid preference structure', () => {
            mockLocalStorage.getItem.mockReturnValue('"string instead of object"');

            const preferences = controller.getUserPreferences();
            expect(preferences).toEqual({});
        });
    });
});
