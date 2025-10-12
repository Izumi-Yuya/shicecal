/**
 * ライフラインモーダルデバッグチェッカー
 * ブラウザコンソールで実行: window.checkLifelineModal('electric')
 */

window.checkLifelineModal = function (category = 'electric') {
  console.group(`🔍 ライフラインモーダルチェック: ${category}`);

  // 1. マネージャーインスタンスの確認
  console.group('1️⃣ マネージャーインスタンス');
  const managerKey = `lifelineDocumentManager_${category}`;
  const manager = window.shiseCalApp?.modules?.[managerKey];
  console.log('Manager:', manager);
  if (manager) {
    console.log('  facilityId:', manager.facilityId);
    console.log('  category:', manager.category);
    console.log('  apiCategory:', manager.apiCategory);
    console.log('  initialized:', manager.initialized);
    console.log('  state:', manager.state);
  } else {
    console.warn('❌ マネージャーが見つかりません');
  }
  console.groupEnd();

  // 2. モーダルDOM要素の確認
  console.group('2️⃣ モーダルDOM要素');
  const modalId = `electrical-documents-modal-${category}`;
  const modal = document.getElementById(modalId);
  console.log('Modal element:', modal);
  if (modal) {
    console.log('  ID:', modal.id);
    console.log('  Classes:', modal.className);
    console.log('  Visible:', modal.classList.contains('show'));
    console.log('  z-index:', getComputedStyle(modal).zIndex);

    // コンテナ確認
    const container = modal.querySelector(`[data-lifeline-category="${category}"]`);
    console.log('  Container:', container);

    if (container) {
      // 各要素の確認
      const elements = {
        'loading-indicator': container.querySelector(`#loading-indicator-${category}`),
        'error-message': container.querySelector(`#error-message-${category}`),
        'empty-state': container.querySelector(`#empty-state-${category}`),
        'document-list': container.querySelector(`#document-list-${category}`),
        'document-grid': container.querySelector(`#document-grid-${category}`),
        'breadcrumb': container.querySelector(`#document-breadcrumb-${category}`),
        'pagination': container.querySelector(`#document-pagination-${category}`),
        'info': container.querySelector(`#document-info-${category}`),
        'list-body': container.querySelector(`#document-list-body-${category}`),
        'grid-body': container.querySelector(`#document-grid-body-${category}`)
      };

      console.group('  要素の存在確認');
      Object.entries(elements).forEach(([name, el]) => {
        const status = el ? '✅' : '❌';
        const visible = el && !el.classList.contains('d-none') ? '👁️' : '🙈';
        console.log(`${status} ${visible} ${name}:`, el?.id || 'not found');
        if (el && name === 'list-body') {
          console.log(`    innerHTML length: ${el.innerHTML.length}`);
          console.log(`    children count: ${el.children.length}`);
        }
      });
      console.groupEnd();
    }
  } else {
    console.warn('❌ モーダル要素が見つかりません');
  }
  console.groupEnd();

  // 3. バックドロップの確認
  console.group('3️⃣ バックドロップ');
  const backdrops = document.querySelectorAll('.modal-backdrop');
  console.log('Backdrop count:', backdrops.length);
  backdrops.forEach((bd, i) => {
    console.log(`  Backdrop ${i + 1}:`, {
      classes: bd.className,
      zIndex: getComputedStyle(bd).zIndex
    });
  });
  console.groupEnd();

  // 4. API呼び出しテスト
  console.group('4️⃣ API呼び出しテスト');
  const facilityId = manager?.facilityId || window.facilityId || 1;
  const apiCategory = category === 'electric' ? 'electrical' : category;
  const apiUrl = `/facilities/${facilityId}/lifeline-documents/${apiCategory}?folder_id=&view_mode=list&per_page=50&page=1`;
  console.log('API URL:', apiUrl);

  fetch(apiUrl, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    }
  })
    .then(response => {
      console.log('Response status:', response.status, response.statusText);
      return response.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        console.log('  ✅ API呼び出し成功');
        console.log('  Folders:', data.data?.folders?.length || 0);
        console.log('  Files:', data.data?.files?.length || 0);
      } else {
        console.warn('  ❌ API呼び出し失敗:', data.message);
      }
    })
    .catch(error => {
      console.error('  ❌ API呼び出しエラー:', error);
    });
  console.groupEnd();

  // 5. イベントリスナーの確認
  console.group('5️⃣ イベントリスナー');
  if (manager) {
    console.log('Event listeners count:', manager.eventListeners?.length || 0);
  }
  console.groupEnd();

  console.groupEnd();

  return {
    manager,
    modal,
    backdrops: backdrops.length,
    apiUrl
  };
};

// 自動実行（ページロード後）
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    console.log('💡 ライフラインモーダルチェッカーが利用可能です');
    console.log('   使用方法: window.checkLifelineModal("electric")');
  });
} else {
  console.log('💡 ライフラインモーダルチェッカーが利用可能です');
  console.log('   使用方法: window.checkLifelineModal("electric")');
}
