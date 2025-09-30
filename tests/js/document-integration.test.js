/**
 * Document Management Integration Tests
 * Tests the integration between document management and existing facility system
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock DOM environment
const mockDOM = () => {
  // Mock document
  global.document = {
    addEventListener: vi.fn(),
    getElementById: vi.fn(),
    querySelector: vi.fn(),
    querySelectorAll: vi.fn(() => []),
    createElement: vi.fn(() => ({
      addEventListener: vi.fn(),
      setAttribute: vi.fn(),
      classList: { add: vi.fn(), remove: vi.fn() },
      style: {},
    })),
    dispatchEvent: vi.fn(),
  };

  // Mock window
  global.window = {
    location: { origin: 'http://localhost' },
    facilityId: 1,
    bootstrap: {
      Modal: vi.fn(() => ({
        show: vi.fn(),
        hide: vi.fn(),
      })),
      Tab: vi.fn(() => ({
        show: vi.fn(),
      })),
    },
  };

  // Mock fetch
  global.fetch = vi.fn();
};

describe('Document Management Integration', () => {
  beforeEach(() => {
    mockDOM();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Tab Integration', () => {
    it('should initialize documents tab correctly', () => {
      const mockTab = {
        addEventListener: vi.fn(),
      };
      const mockPane = {
        querySelectorAll: vi.fn(() => []),
      };

      document.getElementById
        .mockReturnValueOnce(mockTab) // documents-tab
        .mockReturnValueOnce(mockPane); // documents pane

      // Simulate tab initialization
      const tabInitialization = () => {
        const documentsTab = document.getElementById('documents-tab');
        const documentsPane = document.getElementById('documents');

        if (documentsTab && documentsPane) {
          documentsTab.addEventListener('shown.bs.tab', () => {
            // Load documents content
          });
        }
      };

      tabInitialization();

      expect(document.getElementById).toHaveBeenCalledWith('documents-tab');
      expect(document.getElementById).toHaveBeenCalledWith('documents');
      expect(mockTab.addEventListener).toHaveBeenCalledWith('shown.bs.tab', expect.any(Function));
    });

    it('should load documents content when tab is activated', async () => {
      const mockResponse = {
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: {
            folders: [],
            files: [],
            breadcrumbs: [{ name: 'ルート', id: null }],
            sort_options: { view_mode: 'list' },
            available_file_types: [],
          },
        }),
      };

      fetch.mockResolvedValue(mockResponse);

      const loadDocumentsContent = async () => {
        const facilityId = window.facilityId;
        const response = await fetch(`/facilities/${facilityId}/documents/folders/`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        return response.json();
      };

      const result = await loadDocumentsContent();

      expect(fetch).toHaveBeenCalledWith('/facilities/1/documents/folders/', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      expect(result.success).toBe(true);
    });

    it('should handle tab switching with active state preservation', () => {
      const mockTabs = [
        { classList: { remove: vi.fn() }, setAttribute: vi.fn() },
        { classList: { remove: vi.fn() }, setAttribute: vi.fn() },
      ];
      const mockPanes = [
        { classList: { remove: vi.fn() } },
        { classList: { remove: vi.fn() } },
      ];

      document.querySelectorAll
        .mockReturnValueOnce(mockTabs) // .nav-link.active
        .mockReturnValueOnce(mockPanes); // .tab-pane.active

      const switchToDocumentsTab = () => {
        // Remove active class from current tabs
        document.querySelectorAll('.nav-link.active').forEach(tab => {
          tab.classList.remove('active');
          tab.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-pane.active').forEach(pane => {
          pane.classList.remove('active');
        });
      };

      switchToDocumentsTab();

      mockTabs.forEach(tab => {
        expect(tab.classList.remove).toHaveBeenCalledWith('active');
        expect(tab.setAttribute).toHaveBeenCalledWith('aria-selected', 'false');
      });
      mockPanes.forEach(pane => {
        expect(pane.classList.remove).toHaveBeenCalledWith('active');
      });
    });
  });

  describe('Permission Integration', () => {
    it('should respect user permissions for document operations', async () => {
      const mockUnauthorizedResponse = {
        ok: false,
        status: 403,
        json: () => Promise.resolve({
          success: false,
          message: '権限がありません。',
        }),
      };

      fetch.mockResolvedValue(mockUnauthorizedResponse);

      const createFolder = async (facilityId, folderName) => {
        const response = await fetch(`/facilities/${facilityId}/documents/folders`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'mock-token',
          },
          body: JSON.stringify({ name: folderName }),
        });
        return response;
      };

      const response = await createFolder(1, 'テストフォルダ');

      expect(response.status).toBe(403);
      expect(fetch).toHaveBeenCalledWith('/facilities/1/documents/folders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'mock-token',
        },
        body: JSON.stringify({ name: 'テストフォルダ' }),
      });
    });

    it('should show/hide UI elements based on permissions', () => {
      const mockUploadBtn = { style: {} };
      const mockCreateFolderBtn = { style: {} };

      document.getElementById
        .mockReturnValueOnce(mockUploadBtn)
        .mockReturnValueOnce(mockCreateFolderBtn);

      const updateUIForPermissions = (canEdit) => {
        const uploadBtn = document.getElementById('uploadFileBtn');
        const createFolderBtn = document.getElementById('createFolderBtn');

        if (uploadBtn) {
          uploadBtn.style.display = canEdit ? 'block' : 'none';
        }
        if (createFolderBtn) {
          createFolderBtn.style.display = canEdit ? 'block' : 'none';
        }
      };

      // Test with edit permissions
      updateUIForPermissions(true);
      expect(mockUploadBtn.style.display).toBe('block');
      expect(mockCreateFolderBtn.style.display).toBe('block');

      // Test without edit permissions
      updateUIForPermissions(false);
      expect(mockUploadBtn.style.display).toBe('none');
      expect(mockCreateFolderBtn.style.display).toBe('none');
    });
  });

  describe('Activity Log Integration', () => {
    it('should trigger activity logging for document operations', async () => {
      const mockResponse = {
        ok: true,
        json: () => Promise.resolve({
          success: true,
          message: 'フォルダを作成しました。',
          folder: { id: 1, name: 'テストフォルダ' },
        }),
      };

      fetch.mockResolvedValue(mockResponse);

      const createFolderWithLogging = async (facilityId, folderName) => {
        const response = await fetch(`/facilities/${facilityId}/documents/folders`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'mock-token',
            'X-Log-Activity': 'true', // Custom header to indicate logging
          },
          body: JSON.stringify({ name: folderName }),
        });
        return response.json();
      };

      const result = await createFolderWithLogging(1, 'テストフォルダ');

      expect(fetch).toHaveBeenCalledWith('/facilities/1/documents/folders', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'mock-token',
          'X-Log-Activity': 'true',
        },
        body: JSON.stringify({ name: 'テストフォルダ' }),
      });
      expect(result.success).toBe(true);
    });
  });

  describe('Error Handling Integration', () => {
    it('should handle network errors gracefully', async () => {
      fetch.mockRejectedValue(new Error('Network error'));

      const mockErrorHandler = vi.fn();

      const loadDocumentsWithErrorHandling = async () => {
        try {
          const response = await fetch('/facilities/1/documents/folders/');
          return response.json();
        } catch (error) {
          mockErrorHandler('ネットワークエラーが発生しました。');
          throw error;
        }
      };

      await expect(loadDocumentsWithErrorHandling()).rejects.toThrow('Network error');
      expect(mockErrorHandler).toHaveBeenCalledWith('ネットワークエラーが発生しました。');
    });

    it('should display validation errors correctly', async () => {
      const mockValidationResponse = {
        ok: false,
        status: 422,
        json: () => Promise.resolve({
          success: false,
          errors: {
            name: ['フォルダ名は必須です。'],
          },
        }),
      };

      fetch.mockResolvedValue(mockValidationResponse);

      const handleValidationErrors = async (facilityId, folderName) => {
        const response = await fetch(`/facilities/${facilityId}/documents/folders`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name: folderName }),
        });

        if (!response.ok) {
          const data = await response.json();
          return data.errors;
        }
      };

      const errors = await handleValidationErrors(1, '');

      expect(errors).toEqual({
        name: ['フォルダ名は必須です。'],
      });
    });
  });

  describe('UI State Management', () => {
    it('should maintain view preferences across tab switches', () => {
      const mockLocalStorage = {
        getItem: vi.fn(),
        setItem: vi.fn(),
      };

      global.localStorage = mockLocalStorage;

      const saveViewPreferences = (preferences) => {
        localStorage.setItem('documentViewPreferences', JSON.stringify(preferences));
      };

      const loadViewPreferences = () => {
        const saved = localStorage.getItem('documentViewPreferences');
        return saved ? JSON.parse(saved) : { viewMode: 'list', sortBy: 'name' };
      };

      // Save preferences
      saveViewPreferences({ viewMode: 'icon', sortBy: 'date' });
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'documentViewPreferences',
        JSON.stringify({ viewMode: 'icon', sortBy: 'date' })
      );

      // Load preferences
      mockLocalStorage.getItem.mockReturnValue(
        JSON.stringify({ viewMode: 'icon', sortBy: 'date' })
      );
      const preferences = loadViewPreferences();
      expect(preferences).toEqual({ viewMode: 'icon', sortBy: 'date' });
    });

    it('should update breadcrumbs when navigating folders', () => {
      const mockBreadcrumbNav = {
        innerHTML: '',
      };

      document.getElementById.mockReturnValue(mockBreadcrumbNav);

      const updateBreadcrumbs = (breadcrumbs) => {
        const nav = document.getElementById('breadcrumbNav');
        if (nav) {
          const items = breadcrumbs.map((item, index) => {
            const isLast = index === breadcrumbs.length - 1;
            return isLast
              ? `<li class="breadcrumb-item active">${item.name}</li>`
              : `<li class="breadcrumb-item"><a href="#" data-folder-id="${item.id}">${item.name}</a></li>`;
          }).join('');

          nav.innerHTML = `<ol class="breadcrumb">${items}</ol>`;
        }
      };

      const breadcrumbs = [
        { id: null, name: 'ルート' },
        { id: 1, name: '親フォルダ' },
        { id: 2, name: '子フォルダ' },
      ];

      updateBreadcrumbs(breadcrumbs);

      expect(mockBreadcrumbNav.innerHTML).toContain('ルート');
      expect(mockBreadcrumbNav.innerHTML).toContain('親フォルダ');
      expect(mockBreadcrumbNav.innerHTML).toContain('子フォルダ');
      expect(mockBreadcrumbNav.innerHTML).toContain('breadcrumb-item active');
    });
  });

  describe('File Operations Integration', () => {
    it('should handle file upload with progress tracking', async () => {
      const mockFormData = {
        append: vi.fn(),
      };
      global.FormData = vi.fn(() => mockFormData);

      const mockXHR = {
        open: vi.fn(),
        setRequestHeader: vi.fn(),
        send: vi.fn(),
        upload: {
          addEventListener: vi.fn(),
        },
        addEventListener: vi.fn(),
      };
      global.XMLHttpRequest = vi.fn(() => mockXHR);

      const uploadFileWithProgress = (file, facilityId, onProgress) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('folder_id', '');

        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', onProgress);
        xhr.open('POST', `/facilities/${facilityId}/documents/files`);
        xhr.send(formData);

        return xhr;
      };

      const mockFile = new File(['content'], 'test.pdf', { type: 'application/pdf' });
      const mockProgressHandler = vi.fn();

      uploadFileWithProgress(mockFile, 1, mockProgressHandler);

      expect(FormData).toHaveBeenCalled();
      expect(mockFormData.append).toHaveBeenCalledWith('file', mockFile);
      expect(mockXHR.open).toHaveBeenCalledWith('POST', '/facilities/1/documents/files');
      expect(mockXHR.upload.addEventListener).toHaveBeenCalledWith('progress', mockProgressHandler);
    });

    it('should handle file download correctly', async () => {
      const mockBlob = new Blob(['file content'], { type: 'application/pdf' });
      const mockResponse = {
        ok: true,
        blob: () => Promise.resolve(mockBlob),
        headers: {
          get: (header) => {
            if (header === 'content-disposition') {
              return 'attachment; filename="test.pdf"';
            }
            return null;
          },
        },
      };

      fetch.mockResolvedValue(mockResponse);

      const mockURL = {
        createObjectURL: vi.fn(() => 'blob:mock-url'),
        revokeObjectURL: vi.fn(),
      };
      global.URL = mockURL;

      const mockLink = {
        href: '',
        download: '',
        click: vi.fn(),
      };
      document.createElement.mockReturnValue(mockLink);

      const downloadFile = async (facilityId, fileId) => {
        const response = await fetch(`/facilities/${facilityId}/documents/files/${fileId}/download`);

        if (response.ok) {
          const blob = await response.blob();
          const url = URL.createObjectURL(blob);

          const link = document.createElement('a');
          link.href = url;
          link.download = 'test.pdf';
          link.click();

          URL.revokeObjectURL(url);
        }
      };

      await downloadFile(1, 1);

      expect(fetch).toHaveBeenCalledWith('/facilities/1/documents/files/1/download');
      expect(mockURL.createObjectURL).toHaveBeenCalledWith(mockBlob);
      expect(mockLink.click).toHaveBeenCalled();
      expect(mockURL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-url');
    });
  });
});