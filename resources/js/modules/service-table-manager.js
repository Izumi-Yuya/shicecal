/**
 * Service Table Manager Module
 * Simple cleanup script to remove empty rows
 */

// サービス種類・開始・終了すべて空の行は削除
(function () {
  const body = document.getElementById('svc-body');
  if (!body) return;

  [...body.querySelectorAll('tr')].forEach(tr => {
    const tds = tr.querySelectorAll('td');
    const name = tds[0]?.textContent.trim();
    const from = tds[2]?.textContent.trim();
    const to = tds[3]?.textContent.trim();

    if (!name && !from && !to) {
      tr.remove();
    }
  });
})();

/**
 * Service Table Manager Class (for compatibility)
 */
export class ServiceTableManager {
  constructor(tableBodyId = 'svc-body') {
    this.tableBodyId = tableBodyId;
    this.tableBody = null;
  }

  initialize() {
    this.tableBody = document.getElementById(this.tableBodyId);
    if (!this.tableBody) {
      console.warn(`Service table body with ID '${this.tableBodyId}' not found`);
      return false;
    }
    this.processTable();
    return true;
  }

  processTable() {
    this.removeEmptyRows();
  }

  removeEmptyRows() {
    const rows = [...this.tableBody.querySelectorAll('tr')];
    rows.forEach(tr => {
      const tds = tr.querySelectorAll('td');
      const name = tds[0]?.textContent.trim();
      const from = tds[2]?.textContent.trim();
      const to = tds[3]?.textContent.trim();

      if (!name && !from && !to) {
        tr.remove();
      }
    });
  }

  static initializeTable(tableBodyId) {
    const manager = new ServiceTableManager(tableBodyId);
    return manager.initialize();
  }
}

/**
 * Auto-initialize when DOM is ready
 */
export function initialize() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      ServiceTableManager.initializeTable('svc-body');
    });
  } else {
    ServiceTableManager.initializeTable('svc-body');
  }
}

export default ServiceTableManager;