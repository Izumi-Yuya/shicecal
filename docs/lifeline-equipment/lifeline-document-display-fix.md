# ライフライン設備ドキュメント表示問題の修正

## 問題の概要

ライフライン設備（電気、ガス、水道等）のドキュメント管理機能で、「ドキュメント」ボタンをクリックしても、ドキュメント一覧が表示されない問題が発生していました。

## 発生日時

2025年10月12日

## 症状

1. 施設詳細ページでライフライン設備タブを開く
2. 「ドキュメント」ボタンをクリック
3. ドキュメント管理セクションが展開される
4. **ローディングインジケーターが表示されたまま、ドキュメント一覧が表示されない**

## 根本原因

### 1. DOM要素の検索方法の問題

**問題のコード** (`LifelineDocumentManager.js`):
```javascript
renderDocuments(data) {
  // グローバルにIDで検索
  const loadingIndicator = document.getElementById('loading-indicator');
  const emptyState = document.getElementById('empty-state');
  const listContainer = document.getElementById('document-list');
  // ...
}
```

**問題点**:
- 複数のライフライン設備カテゴリ（電気、ガス、水道等）が同じページに存在
- すべてのカテゴリが同じID（`loading-indicator`, `empty-state`, `document-list`）を使用
- `document.getElementById()`は最初に見つかった要素のみを返す
- 結果として、間違ったカテゴリの要素を操作していた

### 2. data-lifeline-category属性の欠如

**問題のコード** (`lifeline-document-manager.blade.php`):
```blade
<div class="document-management" data-facility-id="{{ $facility->id }}">
  <!-- data-lifeline-category属性がない -->
</div>
```

**問題点**:
- JavaScriptがカテゴリ固有のコンテナを特定できない
- 初期化時に正しいDOM要素を見つけられない

### 3. thisコンテキストの喪失

**問題のコード** (`app-unified.js`):
```javascript
documentSection.addEventListener('shown.bs.collapse', () => {
  // thisが正しいコンテキストを指していない
  this.initializeLifelineDocumentManagers();
});
```

**問題点**:
- イベントリスナー内で`this`が`ShiseCalApp`インスタンスを指していない
- メソッド呼び出しが失敗する

## 修正内容

### 1. DOM要素の検索方法を修正

**修正後のコード** (`LifelineDocumentManager.js`):
```javascript
renderDocuments(data) {
  console.log(`[LifelineDoc] Rendering documents for ${this.category}`, data);
  
  // カテゴリ固有のコンテナを取得
  const container = document.querySelector(`[data-lifeline-category="${this.category}"]`);
  if (!container) {
    console.error(`[LifelineDoc] Container not found for category: ${this.category}`);
    return;
  }

  // コンテナ内で要素を検索
  const loadingIndicator = container.querySelector('#loading-indicator');
  const emptyState = container.querySelector('#empty-state');
  const listContainer = container.querySelector('#document-list');
  
  // 要素を操作
  if (loadingIndicator) {
    loadingIndicator.style.display = 'none';
  }
  // ...
}
```

**改善点**:
- カテゴリ固有のコンテナを先に取得
- `container.querySelector()`でコンテナ内のみを検索
- 他のカテゴリの要素と競合しない

### 2. data-lifeline-category属性を追加

**修正後のコード** (`lifeline-document-manager.blade.php`):
```blade
<div class="document-management" 
     data-facility-id="{{ $facility->id }}" 
     data-lifeline-category="{{ $category }}" 
     id="document-management-container-{{ $category }}">
  <!-- ... -->
</div>
```

**改善点**:
- カテゴリを明示的に指定
- JavaScriptが正しいコンテナを特定できる
- IDもカテゴリ固有にして重複を防止

### 3. thisコンテキストを保持

**修正後のコード** (`app-unified.js`):
```javascript
initializeLifelineDocumentToggles() {
  const self = this; // thisコンテキストを保持
  
  document.querySelectorAll('[id$="-documents-toggle"]').forEach(toggleBtn => {
    // ...
    
    documentSection.addEventListener('shown.bs.collapse', () => {
      console.log(`[Toggle] ✓ Section ${sectionId} shown`);
      updateButtonState(true);
      
      // selfを使用してメソッドを呼び出し
      setTimeout(() => {
        self.initializeLifelineDocumentManagers();
      }, 100);
    });
  });
}
```

**改善点**:
- `const self = this`で`this`を保持
- イベントリスナー内で`self`を使用
- メソッド呼び出しが確実に成功

### 4. 詳細なデバッグログを追加

**追加したログ** (`app-unified.js`, `LifelineDocumentManager.js`):
```javascript
// 初期化ログ
console.log(`[LifelineDoc] Starting initialization for facility ${facilityId}`);
console.log(`[LifelineDoc] Found ${lifelineContainers.length} lifeline containers`);
console.log(`[LifelineDoc] Processing category: ${category}`);
console.log(`[LifelineDoc] ✓ Manager created for ${category}`);

// レンダリングログ
console.log(`[LifelineDoc] Rendering documents for ${this.category}`, data);
console.log(`[LifelineDoc] Loading indicator hidden`);
console.log(`[LifelineDoc] Has data:`, hasData);
console.log(`[LifelineDoc] Empty state shown`);
```

**改善点**:
- 問題の追跡が容易
- 各ステップの状態を確認可能
- デバッグ時間を大幅に短縮

## 修正ファイル一覧

1. **resources/js/modules/LifelineDocumentManager.js**
   - `renderDocuments()`メソッドを修正
   - カテゴリ固有のコンテナ内で要素を検索

2. **resources/js/app-unified.js**
   - `initializeLifelineDocumentToggles()`メソッドを修正
   - `thisコンテキストを保持
   - 詳細なデバッグログを追加

3. **resources/views/components/lifeline-document-manager.blade.php**
   - `data-lifeline-category`属性を追加
   - IDをカテゴリ固有に変更

4. **resources/css/lifeline-document-management.css**
   - 電気設備用のスタイルを追加

5. **resources/views/facilities/lifeline-equipment/electrical.blade.php**
   - インラインJavaScript/CSSを削除
   - 外部ファイルに統合

## テスト方法

### 1. 基本動作確認

```bash
# 開発サーバー起動
npm run dev
php artisan serve
```

1. 施設詳細ページを開く（例: `/facilities/1`）
2. ライフライン設備タブをクリック
3. 「ドキュメント」ボタンをクリック
4. ドキュメント管理セクションが展開される
5. **期待される表示**:
   - ローディングインジケーターが消える
   - 「ドキュメントがありません」メッセージが表示される（データが空の場合）
   - または、ファイル/フォルダ一覧が表示される（データがある場合）

### 2. ブラウザコンソールで確認

```javascript
// 1. コンテナの存在確認
document.querySelectorAll('[data-lifeline-category]')
// 期待: NodeList(1以上) [div.document-management, ...]

// 2. マネージャーの存在確認
window.shiseCalApp.modules.lifelineDocumentManager_electrical
// 期待: LifelineDocumentManager {initialized: true, ...}

// 3. 手動でデータ読み込み
window.shiseCalApp.modules.lifelineDocumentManager_electrical.loadDocuments()
// 期待: コンソールに[LifelineDoc]ログが表示される
```

### 3. ネットワークタブで確認

1. ブラウザのデベロッパーツールを開く
2. Networkタブを選択
3. 「ドキュメント」ボタンをクリック
4. **期待されるリクエスト**:
   - URL: `/facilities/{id}/lifeline-documents/{category}`
   - Method: GET
   - Status: 200 OK
   - Response: `{"success": true, "data": {...}}`

## 予防策

### 1. DOM要素の命名規則

**ルール**: 複数インスタンスが存在する可能性がある要素には、必ず一意な識別子を含める

**良い例**:
```blade
<div id="document-list-{{ $category }}">
<div data-category="{{ $category }}">
```

**悪い例**:
```blade
<div id="document-list">  <!-- 複数存在すると競合 -->
```

### 2. JavaScriptのスコープ管理

**ルール**: イベントリスナー内で`this`を使用する場合は、必ずコンテキストを保持

**良い例**:
```javascript
const self = this;
element.addEventListener('click', () => {
  self.method();
});
```

**悪い例**:
```javascript
element.addEventListener('click', () => {
  this.method();  // thisが期待と異なる可能性
});
```

### 3. デバッグログの活用

**ルール**: 複雑な初期化処理には、必ず詳細なログを追加

**推奨パターン**:
```javascript
console.log(`[ModuleName] Starting operation`);
console.log(`[ModuleName] Found ${items.length} items`);
console.log(`[ModuleName] ✓ Operation completed`);
console.error(`[ModuleName] ✗ Operation failed:`, error);
```

## 関連ドキュメント

- [JavaScript アーキテクチャ](./javascript-architecture.md)
- [フロントエンド構造](./frontend-structure.md)
- [モーダル実装ガイド](../.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備ドキュメント管理](./lifeline-document-management.md)

## 今後の改善

### 短期
- [ ] 他のライフライン設備カテゴリ（ガス、水道等）にも同じ修正を適用
- [ ] 単体テストを追加
- [ ] E2Eテストを追加

### 長期
- [ ] コンポーネントの完全な分離（Vue.js/React化）
- [ ] TypeScript化でコンパイル時にエラーを検出
- [ ] Storybookでコンポーネントを独立してテスト

## まとめ

この問題は、複数のインスタンスが存在する環境でのDOM要素の検索方法と、JavaScriptのコンテキスト管理の重要性を示しています。

**教訓**:
1. グローバルなID検索は避け、スコープを限定する
2. `this`コンテキストは常に明示的に管理する
3. 詳細なデバッグログは問題解決を大幅に加速する
4. 一意な識別子を使用してDOM要素の競合を防ぐ

これらの原則を守ることで、同様の問題を未然に防ぐことができます。
