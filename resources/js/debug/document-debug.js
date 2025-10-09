/**
 * Document Management Debug Helper
 * Use this in browser console to debug document loading issues
 */

window.debugDocumentManager = {
  // Check if DocumentManager is initialized
  checkInitialization() {
    console.log('=== Document Manager Debug ===');
    console.log('DocumentManager class available:', typeof window.DocumentManager);
    console.log('DocumentManager instance:', window.documentManager);
    console.log('ShiseCalApp instance:', window.shiseCalApp);

    const container = document.getElementById('document-management-container');
    console.log('Document container found:', !!container);

    if (container) {
      console.log('Container dataset:', container.dataset);
    }

    return {
      hasClass: typeof window.DocumentManager !== 'undefined',
      hasInstance: !!window.documentManager,
      hasContainer: !!container,
      containerData: container?.dataset
    };
  },

  // Manually initialize DocumentManager
  async manualInit(facilityId) {
    if (!facilityId) {
      const container = document.getElementById('document-management-container');
      facilityId = container?.dataset.facilityId;
    }

    if (!facilityId) {
      console.error('Facility ID not found');
      return;
    }

    console.log('Manually initializing DocumentManager with facilityId:', facilityId);

    try {
      const manager = new window.DocumentManager({
        facilityId: facilityId,
        baseUrl: `/facilities/${facilityId}/documents`
      });

      await manager.init();
      window.documentManager = manager;

      console.log('DocumentManager initialized successfully:', manager);
      return manager;
    } catch (error) {
      console.error('Failed to initialize DocumentManager:', error);
      throw error;
    }
  },

  // Test API endpoint directly
  async testApiEndpoint(facilityId) {
    if (!facilityId) {
      const container = document.getElementById('document-management-container');
      facilityId = container?.dataset.facilityId;
    }

    const endpoint = `/facilities/${facilityId}/documents/folders`;
    console.log('Testing endpoint:', endpoint);

    try {
      const response = await fetch(endpoint, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
      });

      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));

      const data = await response.json();
      console.log('Response data:', data);

      return { response, data };
    } catch (error) {
      console.error('API test failed:', error);
      throw error;
    }
  },

  // Check current page state
  checkPageState() {
    console.log('=== Page State Debug ===');
    console.log('Current URL:', window.location.href);
    console.log('Document ready state:', document.readyState);
    console.log('Bootstrap available:', typeof window.bootstrap);
    console.log('jQuery available:', typeof window.$);

    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    console.log('Tabs found:', tabs.length);

    const activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
    console.log('Active tab:', activeTab?.getAttribute('data-bs-target'));

    return {
      url: window.location.href,
      readyState: document.readyState,
      hasBootstrap: typeof window.bootstrap !== 'undefined',
      tabCount: tabs.length,
      activeTab: activeTab?.getAttribute('data-bs-target')
    };
  },

  // Force reload documents
  async forceReload() {
    if (!window.documentManager) {
      console.error('DocumentManager not initialized');
      return;
    }

    console.log('Force reloading documents...');
    try {
      await window.documentManager.loadDocuments();
      console.log('Documents reloaded successfully');
    } catch (error) {
      console.error('Failed to reload documents:', error);
    }
  }
};

// Auto-run basic checks
console.log('Document Debug Helper loaded. Use window.debugDocumentManager to debug.');
window.debugDocumentManager.checkInitialization();
window.debugDocumentManager.checkPageState();