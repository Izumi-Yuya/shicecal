# ライフライン設備ドキュメント管理 - 状態管理修正

## 問題の概要

ライフライン設備のドキュメント管理システムで以下の問題が発生していました：

1. **フォルダ状態の喪失**: フォルダに移動後、新しいフォルダを作成しようとすると、現在のフォルダ状態（`currentFolder`）が`null`にリセットされる
2. **重複インスタンス**: 同じカテゴリに対して複数のマネージャーインスタンスが作成され、状態が共有されない
3. **モーダルバックドロップの蓄積**: モーダルを開閉するたびにバックドロップが残留し、複数のバックドロップが蓄積される
4. **重複ID警告**: 複数のコンポーネントで同じIDが使用され、アクセシビリティの問題が発生

## 根本原因

### 1. インスタンス管理の問題

```javascript
// 問題のあるコード
if (category && window.shiseCalApp?.modules?.[`lifelineDocumentManager_${category}`]) {
  console.warn(`LifelineDocumentManager for ${category} already exists, returning existing instance`);
  return window.shiseCalApp.modules[`lifelineDocumentManager_${category}`];
}
```

- `window.shiseCalApp.modules`を使用していたが、実際には`window`に直接登録されていた
- 既存インスタンスのチェックが機能せず、新しいインスタンスが作成されていた
- 新しいインスタンスには以前の状態（`currentFolder`）が保持されていない

### 2. Bladeコンポーネントの重複初期化

```javascript
// 問題のあるコード
document.addEventListener('DOMContentLoaded', function() {
    const manager = new LifelineDocumentManager(facilityId, category);
    window['lifelineDocManager_' + category] = manager;
});
```

- 既存インスタンスのチェックなしで常に新しいインスタンスを作成
- ページ内に同じカテゴリのコンポーネントが複数ある場合、複数回初期化される

### 3. 重複ID

```html
<!-- 問題のあるコード -->
<ol class="breadcrumb" id="breadcrumb-nav">
```

- すべてのカテゴリで同じID `breadcrumb-nav` を使用
- HTML仕様違反（同じIDは1ページに1つのみ）

## 実装した修正

### 1. インスタンス管理の強化

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

```javascript
// 修正後のコード
class LifelineDocumentManager {
  constructor(facilityId = null, category = null, options = {}) {
    // 重複インスタンス防止 - より強力なチェック
    const existingKey = `lifelineDocManager_${category}`;
    if (category && window[existingKey]) {
      console.warn(`[LifelineDoc] Manager for ${category} already exists, returning existing instance`);
      return window[existingKey];
    }
    
    // ... 初期化処理 ...
    
    // グローバルに登録（重複防止のため）
    if (category) {
      window[`lifelineDocManager_${category}`] = this;
      console.log(`[LifelineDoc] Registered instance as window.lifelineDocManager_${category}`);
    }
  }
}
```

**変更点**:
- `window.shiseCalApp.modules`ではなく、`window`に直接登録
- コンストラクタの最初で既存インスタンスをチェックし、存在する場合は既存インスタンスを返す
- コンストラクタの最後でグローバルに登録

### 2. Bladeコンポーネントの初期化改善

**ファイル**: `resources/views/components/lifeline-document-manager.blade.php`

```javascript
// 修正後のコード
document.addEventListener('DOMContentLoaded', function() {
    const facilityId = {{ $facility->id }};
    const category = '{{ $category }}';
    const managerKey = 'lifelineDocManager_' + category;
    
    // 既存のインスタンスがあればスキップ
    if (window[managerKey]) {
        console.log(`[LifelineDoc] Manager for ${category} already exists, skipping initialization`);
        return;
    }
    
    // インスタンスを作成（コンストラクタ内でグローバルに登録される）
    new LifelineDocumentManager(facilityId, category);
});
```

**変更点**:
- 初期化前に既存インスタンスをチェック
- 既存インスタンスがある場合は初期化をスキップ

### 3. 重複ID修正

**ファイル**: `resources/views/components/lifeline-document-manager.blade.php`

```html
<!-- 修正後のコード -->
<ol class="breadcrumb" id="breadcrumb-nav-{{ $category }}">
```

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

```javascript
// 修正後のコード
updateBreadcrumbs(breadcrumbs) {
    const container = rootContainer.querySelector(`#breadcrumb-nav-${this.category}`);
    // ...
}
```

**変更点**:
- パンくずナビゲーションのIDをカテゴリ固有に変更
- JavaScriptでも対応するIDを使用

### 4. デバッグログの追加

```javascript
// 修正後のコード
navigateToFolder(folderId) {
    console.log(`[LifelineDoc] ${this.category}: Navigating to folder: ${normalizedFolderId}`);
    console.log(`[LifelineDoc] ${this.category}: Instance ID:`, this);
    this.setState({ currentFolder: normalizedFolderId });
    console.log(`[LifelineDoc] ${this.category}: State updated, currentFolder is now: ${this.state.currentFolder}`);
    this.loadDocuments();
}

openCreateFolderModal() {
    console.log(`[LifelineDoc] ${this.category}: Attempting to open create folder modal`);
    console.log(`[LifelineDoc] ${this.category}: Current folder before opening modal: ${this.state.currentFolder}`);
    console.log(`[LifelineDoc] ${this.category}: Instance ID:`, this);
    // ...
}
```

**変更点**:
- カテゴリ名をログに含めて、どのインスタンスのログかを明確化
- インスタンスIDをログ出力して、同じインスタンスが使用されているか確認

## 期待される効果

### 1. 状態の永続化
- フォルダに移動後、同じインスタンスが使用されるため、`currentFolder`状態が保持される
- サブフォルダ作成時に正しい親フォルダIDが使用される

### 2. パフォーマンス向上
- 重複インスタンスが作成されないため、メモリ使用量が削減される
- イベントリスナーの重複登録が防止される

### 3. バックドロップ問題の軽減
- 単一インスタンスでモーダルを管理するため、バックドロップの蓄積が減少
- 既存のクリーンアップ処理がより効果的に機能

### 4. アクセシビリティの改善
- 重複IDが解消され、HTML仕様に準拠
- スクリーンリーダーなどの支援技術が正しく動作

## テスト手順

### 1. 基本的なフォルダ操作
1. ライフライン設備タブを開く
2. 任意のカテゴリ（例：電気設備）のドキュメントセクションを展開
3. フォルダを作成
4. 作成したフォルダをクリックして移動
5. 「新しいフォルダ」ボタンをクリック
6. コンソールログで`currentFolder`が正しいフォルダIDを保持していることを確認
7. サブフォルダを作成
8. サブフォルダが正しい親フォルダ内に作成されることを確認

### 2. 複数カテゴリの同時操作
1. 複数のカテゴリ（電気、水道、ガスなど）のドキュメントセクションを展開
2. 各カテゴリでフォルダを作成
3. コンソールログで各カテゴリが独立したインスタンスを持つことを確認
4. 各カテゴリの状態が独立して管理されることを確認

### 3. モーダル操作
1. フォルダ作成モーダルを開く
2. キャンセルして閉じる
3. 再度開く
4. コンソールログでバックドロップが1つだけ存在することを確認
5. 複数回開閉を繰り返してもバックドロップが蓄積しないことを確認

### 4. ページ再読み込み
1. フォルダに移動した状態でページを再読み込み
2. 状態がリセットされることを確認（これは正常な動作）
3. 再度フォルダに移動
4. サブフォルダ作成が正しく動作することを確認

## 既知の制限事項

1. **ページ再読み込み時の状態**: ページを再読み込みすると、`currentFolder`状態はリセットされます。これは設計上の動作です。
2. **ブラウザバック/フォワード**: ブラウザの戻る/進むボタンでは、フォルダ状態は復元されません。
3. **複数タブ**: 同じ施設を複数のタブで開いた場合、タブ間で状態は共有されません。

## 今後の改善案

1. **URL状態管理**: URLパラメータまたはハッシュフラグメントを使用して、現在のフォルダ状態を永続化
2. **セッションストレージ**: ブラウザのセッションストレージを使用して、ページ再読み込み後も状態を復元
3. **履歴API**: HTML5 History APIを使用して、ブラウザバック/フォワードに対応
4. **状態同期**: WebSocketまたはポーリングを使用して、複数タブ間で状態を同期

## 関連ドキュメント

- [モーダル実装ガイドライン](../../.kiro/steering/modal-implementation-guide.md)
- [フォルダ表示修正](./folder-display-fix.md)
- [モーダルz-index修正](./modal-zindex-fix-summary.md)
- [フォルダナビゲーション修正](./folder-navigation-fix.md)

## 修正日時

2025年10月13日

## 修正者

Kiro AI Assistant
