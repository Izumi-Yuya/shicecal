/**
 * @vitest-environment jsdom
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock DOM structure for facility views
const createMockFacilityDOM = () => {
  document.body.innerHTML = `
    <div class="view-toggle-container">
      <div class="btn-group">
        <input type="radio" class="btn-check" name="viewMode" id="cardView" value="card" checked>
        <label class="btn btn-outline-primary" for="cardView">カード形式</label>
        
        <input type="radio" class="btn-check" name="viewMode" id="tableView" value="table">
        <label class="btn btn-outline-primary" for="tableView">テーブル形式</label>
      </div>
    </div>

    <!-- Card View -->
    <div class="facility-card-view">
      <div class="card facility-info-card">
        <div class="card-body">
          <div class="facility-detail-table">
            <div class="detail-row">
              <span class="detail-label">会社名</span>
              <span class="detail-value">テスト会社株式会社</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">事業所コード</span>
              <span class="detail-value">
                <span class="badge bg-primary">TEST001</span>
              </span>
            </div>
            <div class="detail-row">
              <span class="detail-label">施設名</span>
              <span class="detail-value fw-bold">テスト施設名</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">郵便番号</span>
              <span class="detail-value">123-4567</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">電話番号</span>
              <span class="detail-value">03-1234-5678</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">メールアドレス</span>
              <span class="detail-value">
                <a href="mailto:test@example.com">test@example.com</a>
              </span>
            </div>
            <div class="detail-row">
              <span class="detail-label">開設日</span>
              <span class="detail-value">2020年01月15日</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">居室数</span>
              <span class="detail-value">50室</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">定員数</span>
              <span class="detail-value">60名</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Service Cards -->
      <div class="service-card">
        <div class="service-card-title">介護付有料老人ホーム</div>
        <div class="service-card-dates">2023/01/01 〜 2026/12/31</div>
      </div>
      <div class="service-card">
        <div class="service-card-title">デイサービス</div>
        <div class="service-card-dates">2023/06/01 〜 2026/05/31</div>
      </div>
    </div>

    <!-- Table View -->
    <div class="facility-table-view" style="display: none;">
      <div class="table-responsive">
        <table class="table table-bordered facility-info">
          <tbody>
            <tr>
              <th>会社名</th>
              <td>テスト会社株式会社</td>
              <th>事業所コード</th>
              <td>TEST001</td>
            </tr>
            <tr>
              <th>施設名</th>
              <td><span class="fw-bold">テスト施設名</span></td>
              <th>郵便番号</th>
              <td>123-4567</td>
            </tr>
            <tr>
              <th>電話番号</th>
              <td>03-1234-5678</td>
              <th>開設日</th>
              <td>2020年01月15日</td>
            </tr>
            <tr>
              <th>メールアドレス</th>
              <td><a href="mailto:test@example.com">test@example.com</a></td>
              <th>居室数</th>
              <td>50室</td>
            </tr>
            <tr>
              <th>定員数</th>
              <td colspan="3">60名</td>
            </tr>
          </tbody>
        </table>
        
        <!-- Service Table -->
        <table class="service-info">
          <tbody>
            <tr>
              <th>サービス種類</th>
              <td>介護付有料老人ホーム</td>
              <td>2023年01月01日 ～ 2026年12月31日</td>
            </tr>
            <tr>
              <td>デイサービス</td>
              <td>2023年06月01日 ～ 2026年05月31日</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  `;
};

// Mock view switching functionality
const mockViewSwitcher = () => {
  const cardView = document.querySelector('.facility-card-view');
  const tableView = document.querySelector('.facility-table-view');
  const cardRadio = document.getElementById('cardView');
  const tableRadio = document.getElementById('tableView');

  const switchView = (viewMode) => {
    if (viewMode === 'table') {
      cardView.style.display = 'none';
      tableView.style.display = 'block';
      tableRadio.checked = true;
      cardRadio.checked = false;
    } else {
      cardView.style.display = 'block';
      tableView.style.display = 'none';
      cardRadio.checked = true;
      tableRadio.checked = false;
    }
  };

  // Add event listeners
  cardRadio.addEventListener('change', () => {
    if (cardRadio.checked) switchView('card');
  });

  tableRadio.addEventListener('change', () => {
    if (tableRadio.checked) switchView('table');
  });

  return { switchView };
};

describe('Facility View Data Parity', () => {
  beforeEach(() => {
    createMockFacilityDOM();
  });

  describe('Data Extraction and Comparison', () => {
    it('should extract all facility data from card view', () => {
      const cardView = document.querySelector('.facility-card-view');
      const detailRows = cardView.querySelectorAll('.detail-row');

      const extractedData = {};
      detailRows.forEach(row => {
        const label = row.querySelector('.detail-label')?.textContent.trim();
        const value = row.querySelector('.detail-value')?.textContent.trim();
        if (label && value) {
          extractedData[label] = value;
        }
      });

      expect(extractedData).toEqual({
        '会社名': 'テスト会社株式会社',
        '事業所コード': 'TEST001',
        '施設名': 'テスト施設名',
        '郵便番号': '123-4567',
        '電話番号': '03-1234-5678',
        'メールアドレス': 'test@example.com',
        '開設日': '2020年01月15日',
        '居室数': '50室',
        '定員数': '60名'
      });
    });

    it('should extract all facility data from table view', () => {
      const tableView = document.querySelector('.facility-table-view');
      const rows = tableView.querySelectorAll('tr');

      const extractedData = {};
      rows.forEach(row => {
        const headers = row.querySelectorAll('th');
        const cells = row.querySelectorAll('td');

        for (let i = 0; i < headers.length; i++) {
          const label = headers[i]?.textContent.trim();
          const value = cells[i]?.textContent.trim();
          if (label && value) {
            extractedData[label] = value;
          }
        }
      });

      expect(extractedData).toEqual({
        '会社名': 'テスト会社株式会社',
        '事業所コード': 'TEST001',
        '施設名': 'テスト施設名',
        '郵便番号': '123-4567',
        '電話番号': '03-1234-5678',
        'メールアドレス': 'test@example.com',
        '開設日': '2020年01月15日',
        '居室数': '50室',
        '定員数': '60名'
      });
    });

    it('should extract service data from both views', () => {
      // Card view services
      const cardServices = [];
      const serviceCards = document.querySelectorAll('.service-card');
      serviceCards.forEach(card => {
        const title = card.querySelector('.service-card-title')?.textContent.trim();
        const dates = card.querySelector('.service-card-dates')?.textContent.trim();
        if (title) {
          cardServices.push({ type: title, dates });
        }
      });

      // Table view services
      const tableServices = [];
      const serviceTable = document.querySelector('.service-info');
      const serviceRows = serviceTable.querySelectorAll('tr');
      serviceRows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 2) {
          const type = cells[index === 0 ? 1 : 0]?.textContent.trim();
          const dates = cells[index === 0 ? 2 : 1]?.textContent.trim();
          if (type) {
            tableServices.push({ type, dates });
          }
        }
      });

      expect(cardServices).toHaveLength(2);
      expect(tableServices).toHaveLength(2);

      // Verify service types match
      const cardTypes = cardServices.map(s => s.type);
      const tableTypes = tableServices.map(s => s.type);

      expect(cardTypes).toContain('介護付有料老人ホーム');
      expect(cardTypes).toContain('デイサービス');
      expect(tableTypes).toContain('介護付有料老人ホーム');
      expect(tableTypes).toContain('デイサービス');
    });
  });

  describe('View Switching Functionality', () => {
    it('should switch from card to table view', () => {
      const { switchView } = mockViewSwitcher();
      const cardView = document.querySelector('.facility-card-view');
      const tableView = document.querySelector('.facility-table-view');

      // Initially card view should be visible
      expect(cardView.style.display).not.toBe('none');
      expect(tableView.style.display).toBe('none');

      // Switch to table view
      switchView('table');

      expect(cardView.style.display).toBe('none');
      expect(tableView.style.display).toBe('block');
    });

    it('should switch from table to card view', () => {
      const { switchView } = mockViewSwitcher();
      const cardView = document.querySelector('.facility-card-view');
      const tableView = document.querySelector('.facility-table-view');

      // Start with table view
      switchView('table');
      expect(cardView.style.display).toBe('none');
      expect(tableView.style.display).toBe('block');

      // Switch back to card view
      switchView('card');
      expect(cardView.style.display).toBe('block');
      expect(tableView.style.display).toBe('none');
    });

    it('should update radio button states when switching views', () => {
      const { switchView } = mockViewSwitcher();
      const cardRadio = document.getElementById('cardView');
      const tableRadio = document.getElementById('tableView');

      // Initially card should be checked
      expect(cardRadio.checked).toBe(true);
      expect(tableRadio.checked).toBe(false);

      // Switch to table
      switchView('table');
      expect(cardRadio.checked).toBe(false);
      expect(tableRadio.checked).toBe(true);

      // Switch back to card
      switchView('card');
      expect(cardRadio.checked).toBe(true);
      expect(tableRadio.checked).toBe(false);
    });
  });

  describe('Data Integrity During View Switching', () => {
    it('should preserve all data when switching between views', () => {
      const { switchView } = mockViewSwitcher();

      // Extract data from card view
      const cardData = extractFacilityData('card');

      // Switch to table view
      switchView('table');

      // Extract data from table view
      const tableData = extractFacilityData('table');

      // Compare critical data points
      expect(cardData.companyName).toBe(tableData.companyName);
      expect(cardData.officeCode).toBe(tableData.officeCode);
      expect(cardData.facilityName).toBe(tableData.facilityName);
      expect(cardData.postalCode).toBe(tableData.postalCode);
      expect(cardData.phoneNumber).toBe(tableData.phoneNumber);
      expect(cardData.email).toBe(tableData.email);
      expect(cardData.openingDate).toBe(tableData.openingDate);
      expect(cardData.roomCount).toBe(tableData.roomCount);
      expect(cardData.capacity).toBe(tableData.capacity);
    });

    it('should maintain service information integrity', () => {
      const { switchView } = mockViewSwitcher();

      // Extract services from card view
      const cardServices = extractServiceData('card');

      // Switch to table view
      switchView('table');

      // Extract services from table view
      const tableServices = extractServiceData('table');

      expect(cardServices.length).toBe(tableServices.length);

      // Check that all service types are preserved
      const cardTypes = cardServices.map(s => s.type);
      const tableTypes = tableServices.map(s => s.type);

      cardTypes.forEach(type => {
        expect(tableTypes).toContain(type);
      });
    });

    it('should handle empty or missing data consistently', () => {
      // Add empty data scenario to DOM
      const emptyRow = document.createElement('div');
      emptyRow.className = 'detail-row';
      emptyRow.innerHTML = `
        <span class="detail-label">FAX番号</span>
        <span class="detail-value"><span class="text-muted">未設定</span></span>
      `;
      document.querySelector('.facility-detail-table').appendChild(emptyRow);

      // Add corresponding table row
      const emptyTableRow = document.createElement('tr');
      emptyTableRow.innerHTML = `
        <th>FAX番号</th>
        <td><span class="text-muted">未設定</span></td>
        <th></th>
        <td></td>
      `;
      document.querySelector('.facility-info tbody').appendChild(emptyTableRow);

      const { switchView } = mockViewSwitcher();

      // Check card view
      const cardView = document.querySelector('.facility-card-view');
      expect(cardView.textContent).toContain('未設定');

      // Switch to table view
      switchView('table');

      // Check table view
      const tableView = document.querySelector('.facility-table-view');
      expect(tableView.textContent).toContain('未設定');
    });
  });

  describe('Link and Format Preservation', () => {
    it('should preserve email links in both views', () => {
      const cardEmailLink = document.querySelector('.facility-card-view a[href^="mailto:"]');
      const tableEmailLink = document.querySelector('.facility-table-view a[href^="mailto:"]');

      expect(cardEmailLink).toBeTruthy();
      expect(tableEmailLink).toBeTruthy();
      expect(cardEmailLink.href).toBe(tableEmailLink.href);
      expect(cardEmailLink.textContent.trim()).toBe(tableEmailLink.textContent.trim());
    });

    it('should preserve numerical formatting with units', () => {
      const { switchView } = mockViewSwitcher();

      // Check card view formatting
      const cardView = document.querySelector('.facility-card-view');
      expect(cardView.textContent).toContain('50室');
      expect(cardView.textContent).toContain('60名');

      // Switch to table view
      switchView('table');

      // Check table view formatting
      const tableView = document.querySelector('.facility-table-view');
      expect(tableView.textContent).toContain('50室');
      expect(tableView.textContent).toContain('60名');
    });

    it('should preserve date formatting', () => {
      const { switchView } = mockViewSwitcher();

      // Check card view date format
      const cardView = document.querySelector('.facility-card-view');
      expect(cardView.textContent).toContain('2020年01月15日');

      // Switch to table view
      switchView('table');

      // Check table view date format
      const tableView = document.querySelector('.facility-table-view');
      expect(tableView.textContent).toContain('2020年01月15日');
    });
  });
});

// Helper functions
function extractFacilityData(viewType) {
  const selector = viewType === 'card' ? '.facility-card-view' : '.facility-table-view';
  const view = document.querySelector(selector);

  if (viewType === 'card') {
    const data = {};
    const rows = view.querySelectorAll('.detail-row');
    rows.forEach(row => {
      const label = row.querySelector('.detail-label')?.textContent.trim();
      const value = row.querySelector('.detail-value')?.textContent.trim();
      if (label && value) {
        data[label] = value;
      }
    });

    return {
      companyName: data['会社名'],
      officeCode: data['事業所コード'],
      facilityName: data['施設名'],
      postalCode: data['郵便番号'],
      phoneNumber: data['電話番号'],
      email: data['メールアドレス'],
      openingDate: data['開設日'],
      roomCount: data['居室数'],
      capacity: data['定員数']
    };
  } else {
    // Extract from table
    const data = {};
    const rows = view.querySelectorAll('tr');
    rows.forEach(row => {
      const headers = row.querySelectorAll('th');
      const cells = row.querySelectorAll('td');

      for (let i = 0; i < headers.length; i++) {
        const label = headers[i]?.textContent.trim();
        const value = cells[i]?.textContent.trim();
        if (label && value) {
          data[label] = value;
        }
      }
    });

    return {
      companyName: data['会社名'],
      officeCode: data['事業所コード'],
      facilityName: data['施設名'],
      postalCode: data['郵便番号'],
      phoneNumber: data['電話番号'],
      email: data['メールアドレス'],
      openingDate: data['開設日'],
      roomCount: data['居室数'],
      capacity: data['定員数']
    };
  }
}

function extractServiceData(viewType) {
  if (viewType === 'card') {
    const services = [];
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
      const title = card.querySelector('.service-card-title')?.textContent.trim();
      const dates = card.querySelector('.service-card-dates')?.textContent.trim();
      if (title) {
        services.push({ type: title, dates });
      }
    });
    return services;
  } else {
    const services = [];
    const serviceTable = document.querySelector('.service-info');
    const rows = serviceTable.querySelectorAll('tr');
    rows.forEach((row, index) => {
      const cells = row.querySelectorAll('td');
      if (cells.length >= 2) {
        const type = cells[index === 0 ? 1 : 0]?.textContent.trim();
        const dates = cells[index === 0 ? 2 : 1]?.textContent.trim();
        if (type) {
          services.push({ type, dates });
        }
      }
    });
    return services;
  }
}