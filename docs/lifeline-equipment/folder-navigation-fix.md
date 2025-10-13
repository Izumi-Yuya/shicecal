# ライフライン設備ドキュメント管理 - フォルダナビゲーション修正

## 問題の概要

ライフライン設備のドキュメント管理タブで、フォルダをクリックしても中身が表示されない問題が発生していました。

## 原因

1. **静的メソッド`navigateToFolder`が未実装**
   - フォルダをクリックしたときに呼ばれる静的メソッドが「TODO」のままで、実際の処理が実装されていませんでした

2. **パンくずナビゲーションの参照エラー**
   - 間違ったコンテナID（`#document-breadcrumb-${category}`）を使用していました
   - 正しいID（`#breadcrumb-nav`）に修正が必要でした
   - グローバル参照も間違っていました（`window.lifelineDocManager_${category}` → `window.LifelineDocumentManager`）

3. **フォルダ作成時の親フォルダID未送信**
   - サブフォルダを作成する際に`parent_folder_id`を送信していませんでした
   - そのため、すべてのフォルダがルートレベルに作成されていました

4. **ファイルアップロード時のフォルダID未送信**
   - ファイルをアップロードする際に`folder_id`を送信していませんでした
   - そのため、すべてのファイルがルートレベルにアップロードされていました

## 修正内容

### 1. 静的メソッド`navigateToFolder`の実装

**修正前:**
```javascript
static async navigateToFolder(category, folderId) {
  const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
  if (manager) {
    // TODO: フォルダナビゲーション機能の実装
    console.log(`Navigate to folder ${folderId} in category ${category}`);
  } else {
    console.error(`LifelineDocumentManager not found for category: ${category}`);
  }
}
```

**修正後:**
```javascript
static async navigateToFolder(category, folderId) {
  const manager = window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`];
  if (manager) {
    console.log(`Navigate to folder ${folderId} in category ${category}`);
    manager.navigateToFolder(folderId);
  } else {
    console.error(`LifelineDocumentManager not found for category: ${category}`);
  }
}
```

### 2. パンくずナビゲーションの修正

**修正前:**
```javascript
updateBreadcrumbs(breadcrumbs) {
  const rootContainer = this.getRootContainer();
  if (!rootContainer) return;

  const container = rootContainer.querySelector(`#document-breadcrumb-${this.category}`);
  if (!container || !breadcrumbs) return;

  let html = '';
  breadcrumbs.forEach((crumb, index) => {
    if (crumb.is_current) {
      html += `<li class="breadcrumb-item active">${this.escapeHtml(crumb.name)}</li>`;
    } else {
      html += `
        <li class="breadcrumb-item">
          <a href="#" onclick="window.lifelineDocManager_${this.category}.navigateToFolder(${crumb.id})">
            ${index === 0 ? '<i class="fas fa-home me-1"></i>' : ''}${this.escapeHtml(crumb.name)}
          </a>
        </li>
      `;
    }
  });

  container.innerHTML = html;
}
```

**修正後:**
```javascript
updateBreadcrumbs(breadcrumbs) {
  const rootContainer = this.getRootContainer();
  if (!rootContainer) return;

  const container = rootContainer.querySelector('#breadcrumb-nav');
  if (!container || !breadcrumbs) return;

  let html = '';
  breadcrumbs.forEach((crumb, index) => {
    if (crumb.is_current) {
      html += `<li class="breadcrumb-item active">${this.escapeHtml(crumb.name)}</li>`;
    } else {
      html += `
        <li class="breadcrumb-item">
          <a href="#" onclick="window.LifelineDocumentManager.navigateToFolder('${this.category}', ${crumb.id}); return false;">
            ${index === 0 ? '<i class="fas fa-home me-1"></i>' : ''}${this.escapeHtml(crumb.name)}
          </a>
        </li>
      `;
    }
  });

  container.innerHTML = html;
}
```

### 3. フォルダ作成時の親フォルダID送信

**修正前:**
```javascript
async handleCreateFolder(event) {
  // ...
  const formData = new FormData(form);
  const folderName = formData.get('name');
  // ...
}
```

**修正後:**
```javascript
async handleCreateFolder(event) {
  // ...
  const formData = new FormData(form);
  const folderName = formData.get('name');

  // 現在のフォルダIDを親フォルダとして追加
  if (this.state.currentFolder) {
    formData.append('parent_folder_id', this.state.currentFolder);
  }
  // ...
}
```

### 4. ファイルアップロード時のフォルダID送信

**修正前:**
```javascript
async handleUploadFile(event) {
  // ...
  const formData = new FormData(form);
  const files = formData.getAll('files[]');
  // ...
}
```

**修正後:**
```javascript
async handleUploadFile(event) {
  // ...
  const formData = new FormData(form);
  const files = formData.getAll('files[]');

  // 現在のフォルダIDを追加
  if (this.state.currentFolder) {
    formData.append('folder_id', this.state.currentFolder);
  }
  // ...
}
```

## 動作確認

修正後、以下の動作が正常に機能するようになります：

1. **フォルダをクリック** → フォルダの中身が表示される
2. **パンくずナビゲーションをクリック** → 指定したフォルダに移動する
3. **フォルダ内でサブフォルダを作成** → 現在のフォルダ内にサブフォルダが作成される
4. **フォルダ内でファイルをアップロード** → 現在のフォルダ内にファイルがアップロードされる

## テスト手順

1. ライフライン設備タブ（電気、ガス、水道など）を開く
2. 「ドキュメント管理」セクションを展開
3. 「新しいフォルダ」ボタンをクリックしてフォルダを作成
4. 作成したフォルダをクリックして中身を表示
5. フォルダ内で「新しいフォルダ」ボタンをクリックしてサブフォルダを作成
6. フォルダ内で「ファイルアップロード」ボタンをクリックしてファイルをアップロード
7. パンくずナビゲーションをクリックして親フォルダに戻る

## 今後の改善点

1. **ページネーション機能の追加**
   - 現在、ページネーションコンテナがBladeテンプレートに存在しません
   - 大量のファイルがある場合に備えて、ページネーション機能を追加する必要があります

2. **エラーハンドリングの強化**
   - ネットワークエラーやサーバーエラー時のユーザーフィードバックを改善

3. **パフォーマンス最適化**
   - 大量のファイル/フォルダがある場合の読み込み速度を改善
   - 仮想スクロールの実装を検討

## 関連ファイル

- `resources/js/modules/LifelineDocumentManager.js` - メインJavaScriptファイル
- `resources/views/components/lifeline-document-manager.blade.php` - Bladeコンポーネント
- `app/Http/Controllers/LifelineDocumentController.php` - コントローラー
- `app/Services/LifelineDocumentService.php` - サービスクラス
- `app/Services/DocumentService.php` - 共通ドキュメントサービス

## 修正日時

2025年10月13日
