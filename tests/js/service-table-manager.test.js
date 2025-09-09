/**
 * Service Table Manager Tests
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { ServiceTableManager } from '../../resources/js/modules/service-table-manager.js';

describe('ServiceTableManager', () => {
  let container;
  let manager;

  beforeEach(() => {
    // Create DOM container
    container = document.createElement('div');
    container.innerHTML = `
      <table class="service-info">
        <tbody id="svc-body">
          <tr class="svc-row">
            <th id="svc-label" class="label-cell">サービス種類</th>
            <td class="value-cell svc-name">介護保険</td>
            <td class="value-cell term">
              <span class="from">2024年1月1日</span>
              <span class="sep"> ～ </span>
              <span class="to">2024年12月31日</span>
            </td>
          </tr>
          <tr class="svc-row">
            <td class="value-cell svc-name"></td>
            <td class="value-cell term">
              <span class="from"></span>
              <span class="sep"> ～ </span>
              <span class="to"></span>
            </td>
          </tr>
        </tbody>
      </table>
    `;
    document.body.appendChild(container);

    manager = new ServiceTableManager('svc-body');
  });

  afterEach(() => {
    document.body.removeChild(container);
  });

  describe('initialization', () => {
    it('should initialize successfully with valid table', () => {
      const result = manager.initialize();
      expect(result).toBe(true);
      expect(manager.tableBody).toBeTruthy();
      expect(manager.labelCell).toBeTruthy();
    });

    it('should fail gracefully with invalid table ID', () => {
      const invalidManager = new ServiceTableManager('invalid-id');
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => { });

      const result = invalidManager.initialize();

      expect(result).toBe(false);
      expect(consoleSpy).toHaveBeenCalledWith("Service table body with ID 'invalid-id' not found");

      consoleSpy.mockRestore();
    });
  });

  describe('data row filtering', () => {
    it('should identify rows with data correctly', () => {
      manager.initialize();

      const rows = Array.from(manager.tableBody.querySelectorAll('tr.svc-row'));
      const firstRow = rows[0];
      const secondRow = rows[1];

      expect(manager.rowHasData(firstRow)).toBe(true);
      expect(manager.rowHasData(secondRow)).toBe(false);
    });

    it('should remove empty rows except the first one', () => {
      manager.initialize();

      const rowsBefore = manager.tableBody.querySelectorAll('tr.svc-row').length;
      expect(rowsBefore).toBe(2);

      manager.processTable();

      const rowsAfter = manager.tableBody.querySelectorAll('tr.svc-row').length;
      expect(rowsAfter).toBe(1); // Only the first row with data should remain
    });
  });

  describe('rowspan management', () => {
    it('should set correct rowspan for label cell', () => {
      manager.initialize();

      const labelCell = manager.labelCell;
      expect(labelCell.getAttribute('rowspan')).toBe('1');
    });

    it('should handle multiple data rows', () => {
      // Add another row with data
      const newRow = document.createElement('tr');
      newRow.className = 'svc-row';
      newRow.innerHTML = `
        <td class="value-cell svc-name">障害福祉</td>
        <td class="value-cell term">
          <span class="from">2024年4月1日</span>
          <span class="sep"> ～ </span>
          <span class="to">2025年3月31日</span>
        </td>
      `;
      manager.tableBody.appendChild(newRow);

      manager.initialize();

      const labelCell = manager.labelCell;
      expect(labelCell.getAttribute('rowspan')).toBe('2');
    });
  });

  describe('period separator cleanup', () => {
    it('should hide separator when dates are missing', () => {
      // Create row with missing end date
      const incompleteRow = document.createElement('tr');
      incompleteRow.className = 'svc-row';
      incompleteRow.innerHTML = `
        <td class="value-cell svc-name">テストサービス</td>
        <td class="value-cell term">
          <span class="from">2024年1月1日</span>
          <span class="sep"> ～ </span>
          <span class="to"></span>
        </td>
      `;
      manager.tableBody.appendChild(incompleteRow);

      manager.initialize();

      const separator = incompleteRow.querySelector('.sep');
      expect(separator.style.display).toBe('none');
    });

    it('should keep separator when both dates are present', () => {
      manager.initialize();

      const firstRow = manager.tableBody.querySelector('tr.svc-row');
      const separator = firstRow.querySelector('.sep');
      expect(separator.style.display).not.toBe('none');
    });
  });

  describe('static factory method', () => {
    it('should initialize table using static method', () => {
      const result = ServiceTableManager.initializeTable('svc-body');
      expect(result).toBe(true);
    });
  });
});