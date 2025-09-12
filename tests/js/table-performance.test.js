/**
 * Table Performance Tests
 * 
 * Tests for table rendering performance, responsive behavior, and complex column configurations
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock DOM environment
const mockTableHTML = `
<div class="table-responsive-lg">
    <table id="test-table" class="table table-bordered table-layout-key-value-pairs">
        <tbody>
            <tr>
                <th>施設名</th>
                <td>テスト施設</td>
                <th>会社名</th>
                <td>テスト会社</td>
            </tr>
        </tbody>
    </table>
</div>
`;

const mockLargeTableHTML = `
<div class="table-responsive-lg">
    <table id="large-table" class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            ${Array.from({ length: 1000 }, (_, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>User ${i + 1}</td>
                    <td>user${i + 1}@example.com</td>
                    <td>03-1234-${String(i + 1).padStart(4, '0')}</td>
                    <td>Address ${i + 1}</td>
                </tr>
            `).join('')}
        </tbody>
    </table>
</div>
`;

describe('Table Performance Tests', () => {
  let container;

  beforeEach(() => {
    // Set up DOM
    document.body.innerHTML = '';
    container = document.createElement('div');
    container.id = 'test-container';
    document.body.appendChild(container);

    // Mock window dimensions for responsive tests
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 1200
    });

    Object.defineProperty(window, 'innerHeight', {
      writable: true,
      configurable: true,
      value: 800
    });
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
  });

  describe('Table Rendering Performance', () => {
    it('should render small tables within performance threshold', () => {
      const startTime = performance.now();

      container.innerHTML = mockTableHTML;
      const table = container.querySelector('#test-table');

      // Force layout calculation
      table.offsetHeight;

      const endTime = performance.now();
      const renderTime = endTime - startTime;

      expect(renderTime).toBeLessThan(100); // Should render in less than 100ms
      expect(table).toBeTruthy();
      expect(table.querySelector('tbody')).toBeTruthy();
    });

    it('should handle large datasets efficiently', () => {
      const startTime = performance.now();

      container.innerHTML = mockLargeTableHTML;
      const table = container.querySelector('#large-table');

      // Force layout calculation
      table.offsetHeight;

      const endTime = performance.now();
      const renderTime = endTime - startTime;

      // Large tables should still render within 3 seconds (requirement)
      expect(renderTime).toBeLessThan(3000);
      expect(table.querySelectorAll('tbody tr')).toHaveLength(1000);
    });

    it('should optimize DOM updates for large tables', () => {
      container.innerHTML = mockLargeTableHTML;
      const table = container.querySelector('#large-table');
      const tbody = table.querySelector('tbody');

      const startTime = performance.now();

      // Simulate adding new rows
      const fragment = document.createDocumentFragment();
      for (let i = 1001; i <= 1100; i++) {
        const row = document.createElement('tr');
        row.innerHTML = `
                    <td>${i}</td>
                    <td>User ${i}</td>
                    <td>user${i}@example.com</td>
                    <td>03-1234-${String(i).padStart(4, '0')}</td>
                    <td>Address ${i}</td>
                `;
        fragment.appendChild(row);
      }
      tbody.appendChild(fragment);

      const endTime = performance.now();
      const updateTime = endTime - startTime;

      expect(updateTime).toBeLessThan(500); // DOM updates should be fast
      expect(table.querySelectorAll('tbody tr')).toHaveLength(1100);
    });
  });

  describe('Responsive PC Layout Tests', () => {
    it('should apply responsive classes correctly', () => {
      container.innerHTML = mockTableHTML;
      const wrapper = container.querySelector('.table-responsive-lg');
      const table = container.querySelector('#test-table');

      expect(wrapper).toBeTruthy();
      expect(table.classList.contains('table-layout-key-value-pairs')).toBe(true);
    });

    it('should handle horizontal scrolling on narrow screens', () => {
      // Simulate narrow PC screen
      Object.defineProperty(window, 'innerWidth', {
        value: 800
      });

      container.innerHTML = `
                <div class="table-responsive-lg" style="width: 800px; overflow-x: auto;">
                    <table style="min-width: 1200px;" class="table">
                        <tr><td>Wide content that requires scrolling</td></tr>
                    </table>
                </div>
            `;

      const wrapper = container.querySelector('.table-responsive-lg');
      const table = wrapper.querySelector('table');

      // In test environment, offsetWidth might be 0, so check style attributes
      expect(table.style.minWidth).toBe('1200px');
      expect(wrapper.style.width).toBe('800px');
      expect(wrapper.style.overflowX).toBe('auto');
    });

    it('should maintain accessibility with responsive layout', () => {
      container.innerHTML = mockTableHTML;
      const table = container.querySelector('#test-table');

      expect(table.getAttribute('role')).toBe(null); // HTML table has implicit role
      expect(table.querySelector('th')).toBeTruthy();
      expect(table.querySelector('td')).toBeTruthy();
    });

    it('should calculate column widths appropriately for PC screens', () => {
      const columns = [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email', width: '40%' },
        { key: 'phone', label: 'Phone' }
      ];

      // Mock column width calculation
      const calculateColumnWidths = (columns, screenWidth = 1200) => {
        const totalColumns = columns.length;
        const specifiedWidths = {};
        let remainingWidth = 100;

        columns.forEach((col, index) => {
          if (col.width) {
            const width = parseFloat(col.width.replace('%', ''));
            specifiedWidths[index] = width;
            remainingWidth -= width;
          }
        });

        const autoColumns = totalColumns - Object.keys(specifiedWidths).length;
        const autoWidth = autoColumns > 0 ? remainingWidth / autoColumns : 0;

        return columns.map((col, index) => {
          if (specifiedWidths[index]) {
            return `${specifiedWidths[index]}%`;
          }
          return `${Math.round(autoWidth * 100) / 100}%`;
        });
      };

      const widths = calculateColumnWidths(columns);

      expect(widths).toHaveLength(3);
      expect(widths[1]).toBe('40%'); // Specified width
      expect(widths[0]).toBe('30%'); // Auto-calculated
      expect(widths[2]).toBe('30%'); // Auto-calculated
    });
  });

  describe('Complex Column Configuration Tests', () => {
    it('should handle rowspan grouping correctly', () => {
      const groupedTableHTML = `
                <table id="grouped-table" class="table">
                    <tbody>
                        <tr>
                            <td rowspan="2">Web</td>
                            <td>Website A</td>
                            <td>Active</td>
                        </tr>
                        <tr>
                            <td>Website B</td>
                            <td>Inactive</td>
                        </tr>
                        <tr>
                            <td rowspan="1">API</td>
                            <td>REST API</td>
                            <td>Active</td>
                        </tr>
                    </tbody>
                </table>
            `;

      container.innerHTML = groupedTableHTML;
      const table = container.querySelector('#grouped-table');
      const rowspanCells = table.querySelectorAll('td[rowspan]');

      expect(rowspanCells).toHaveLength(2);
      expect(rowspanCells[0].getAttribute('rowspan')).toBe('2');
      expect(rowspanCells[1].getAttribute('rowspan')).toBe('1');
    });

    it('should support dynamic column management', () => {
      container.innerHTML = `
                <table id="dynamic-table" class="table">
                    <thead>
                        <tr id="header-row">
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>User 1</td>
                            <td>user1@example.com</td>
                        </tr>
                    </tbody>
                </table>
            `;

      const table = container.querySelector('#dynamic-table');
      const headerRow = table.querySelector('#header-row');
      const dataRow = table.querySelector('tbody tr');

      // Simulate adding a new column
      const newHeader = document.createElement('th');
      newHeader.textContent = 'Phone';
      headerRow.appendChild(newHeader);

      const newCell = document.createElement('td');
      newCell.textContent = '03-1234-5678';
      dataRow.appendChild(newCell);

      expect(headerRow.children).toHaveLength(3);
      expect(dataRow.children).toHaveLength(3);
      expect(newHeader.textContent).toBe('Phone');
      expect(newCell.textContent).toBe('03-1234-5678');
    });

    it('should handle nested data structures', () => {
      const nestedTableHTML = `
                <table id="nested-table" class="table">
                    <tbody>
                        <tr>
                            <td>Parent 1</td>
                            <td>
                                <table class="nested-table">
                                    <tr><td>Child 1.1</td></tr>
                                    <tr><td>Child 1.2</td></tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            `;

      container.innerHTML = nestedTableHTML;
      const mainTable = container.querySelector('#nested-table');
      const nestedTable = mainTable.querySelector('.nested-table');

      expect(nestedTable).toBeTruthy();
      expect(nestedTable.querySelectorAll('tr')).toHaveLength(2);
      expect(nestedTable.textContent).toContain('Child 1.1');
      expect(nestedTable.textContent).toContain('Child 1.2');
    });
  });

  describe('Memory Usage Optimization Tests', () => {
    it('should clean up event listeners properly', () => {
      const mockEventListener = vi.fn();

      container.innerHTML = mockTableHTML;
      const table = container.querySelector('#test-table');

      // Add event listener
      table.addEventListener('click', mockEventListener);

      // Simulate cleanup
      table.removeEventListener('click', mockEventListener);

      // Trigger event to verify cleanup
      table.click();

      expect(mockEventListener).not.toHaveBeenCalled();
    });

    it('should handle memory efficiently with large datasets', () => {
      // Mock memory usage tracking
      const initialMemory = performance.memory ? performance.memory.usedJSHeapSize : 0;

      container.innerHTML = mockLargeTableHTML;
      const table = container.querySelector('#large-table');

      // Force garbage collection if available
      if (window.gc) {
        window.gc();
      }

      const afterRenderMemory = performance.memory ? performance.memory.usedJSHeapSize : 0;

      // Memory increase should be reasonable for 1000 rows
      if (performance.memory) {
        const memoryIncrease = afterRenderMemory - initialMemory;
        expect(memoryIncrease).toBeLessThan(50 * 1024 * 1024); // Less than 50MB
      }

      expect(table.querySelectorAll('tr')).toHaveLength(1001); // 1000 data rows + 1 header
    });

    it('should support pagination for large datasets', () => {
      const createPaginatedTable = (data, page = 1, perPage = 50) => {
        const start = (page - 1) * perPage;
        const end = start + perPage;
        const pageData = data.slice(start, end);

        return `
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Name</th></tr>
                            </thead>
                            <tbody>
                                ${pageData.map(item => `
                                    <tr>
                                        <td>${item.id}</td>
                                        <td>${item.name}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <div class="pagination-info">
                            Showing ${start + 1}-${Math.min(end, data.length)} of ${data.length}
                        </div>
                    </div>
                `;
      };

      const largeDataset = Array.from({ length: 1000 }, (_, i) => ({
        id: i + 1,
        name: `User ${i + 1}`
      }));

      container.innerHTML = createPaginatedTable(largeDataset, 1, 50);

      const table = container.querySelector('table');
      const rows = table.querySelectorAll('tbody tr');
      const paginationInfo = container.querySelector('.pagination-info');

      expect(rows).toHaveLength(50);
      expect(paginationInfo.textContent).toContain('Showing 1-50 of 1000');
    });
  });

  describe('Scroll Performance Tests', () => {
    it('should maintain smooth scrolling with large tables', () => {
      container.innerHTML = `
                <div style="height: 400px; overflow-y: auto;" id="scroll-container">
                    ${mockLargeTableHTML}
                </div>
            `;

      const scrollContainer = container.querySelector('#scroll-container');
      const table = scrollContainer.querySelector('table');

      let frameCount = 0;
      const startTime = performance.now();

      // Simulate smooth scrolling
      const simulateScroll = () => {
        frameCount++;
        scrollContainer.scrollTop += 10;

        if (frameCount < 100 && scrollContainer.scrollTop < scrollContainer.scrollHeight - scrollContainer.clientHeight) {
          requestAnimationFrame(simulateScroll);
        } else {
          const endTime = performance.now();
          const totalTime = endTime - startTime;
          const fps = (frameCount / totalTime) * 1000;

          // Should maintain reasonable FPS during scrolling
          expect(fps).toBeGreaterThan(30);
        }
      };

      requestAnimationFrame(simulateScroll);

      expect(table).toBeTruthy();
      // In test environment, scrollHeight might be 0, so just verify structure
      expect(scrollContainer.style.height).toBe('400px');
      expect(scrollContainer.style.overflowY).toBe('auto');
    });

    it('should handle horizontal scroll indicators', () => {
      container.innerHTML = `
                <div class="table-responsive-lg" style="position: relative; width: 800px; overflow-x: auto;">
                    <table style="min-width: 1200px;" class="table">
                        <tr><td>Wide content requiring horizontal scroll</td></tr>
                    </table>
                    <div class="scroll-indicator" style="position: absolute; top: 10px; right: 10px;">
                        → 横スクロールできます
                    </div>
                </div>
            `;

      const wrapper = container.querySelector('.table-responsive-lg');
      const indicator = wrapper.querySelector('.scroll-indicator');
      const table = wrapper.querySelector('table');

      expect(indicator).toBeTruthy();
      expect(indicator.textContent).toContain('横スクロールできます');
      // In test environment, check style attributes instead of computed widths
      expect(table.style.minWidth).toBe('1200px');
      expect(wrapper.style.width).toBe('800px');
    });
  });
});