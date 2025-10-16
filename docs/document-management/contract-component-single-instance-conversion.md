# 契約書ドキュメント管理コンポーネント - 単一インスタンス化実装

## 概要

契約書ドキュメント管理コンポーネント（`contract-document-manager.blade.php`）を、カテゴリベースの複数インスタンスから単一インスタンスとして動作するように修正しました。

## 実装日

2025年10月16日

## 変更内容

### 1. コンポーネントIDの統一

すべてのHTML要素IDから`-{{ $category }}`接尾辞を削除し、固定値`-contracts`に統一しました。

#### 主要な変更

| 変更前 | 変更後 |
|--------|--------|
| `document-management-container-{{ $category }}` | `contract-document-management-container` |
| `create-folder-btn-{{ $category }}` | `create-folder-btn-contracts` |
| `upload-file-btn-{{ $category }}` | `upload-file-btn-contracts` |
| `list-view-{{ $category }}` | `list-view-contracts` |
| `grid-view-{{ $category }}` | `grid-view-contracts` |
| `search-input-{{ $category }}` | `search-input-contracts` |
| `breadcrumb-nav-{{ $category }}` | `breadcrumb-nav-contracts` |
| `loading-indicator-{{ $category }}` | `loading-indicator-contracts` |
| `error-message-{{ $category }}` | `error-message-contracts` |
| `empty-state-{{ $category }}` | `empty-state-contracts` |
| `document-list-{{ $category }}` | `document-list-contracts` |
| `document-list-body-{{ $category }}` | `document-list-body-contracts` |
| `document-grid-{{ $category }}` | `document-grid-contracts` |

#### モーダルIDの統一

| 変更前 | 変更後 |
|--------|--------|
| `create-folder-modal-{{ $category }}` | `create-folder-modal-contracts` |
| `upload-file-modal-{{ $category }}` | `upload-file-modal-contracts` |
| `rename-modal-{{ $category }}` | `rename-modal-contracts` |
| `properties-modal-{{ $category }}` | `properties-modal-contracts` |
| `context-menu-{{ $category }}` | `context-menu-contracts` |

#### フォームIDの統一

| 変更前 | 変更後 |
|--------|--------|
| `create-folder-form-{{ $category }}` | `create-folder-form-contracts` |
| `upload-file-form-{{ $category }}` | `upload-file-form-contracts` |
| `rename-form-{{ $category }}` | `rename-form-contracts` |

### 2. カテゴリ属性の削除

`data-contract-category="{{ $category }}"` 属性を削除しました。

**変更前:**
```blade
<div class="document-management" data-facility-id="{{ $facility->id }}" data-contract-category="{{ $category }}" id="document-management-container-{{ $category }}">
```

**変更後:**
```blade
<div class="document-management" data-facility-id="{{ $facility->id }}" id="contract-document-management-container">
```

カテゴリは固定値`'contracts'`として扱われます。

### 3. 初期化スクリプトの修正

コンポーネント内の初期化スクリプトを単一インスタンス用に修正しました。

#### 主要な変更点

1. **カテゴリパラメータの削除**
   - `new ContractDocumentManager(facilityId, category)` → `new ContractDocumentManager(facilityId)`

2. **グローバル変数名の統一**
   - `window.contractDocManager_${category}` → `window.contractDocManager`

3. **インスタンスチェックの簡素化**
   - カテゴリベースのキーから単一のグローバル変数チェックに変更

**変更前:**
```javascript
const category = '{{ $category }}';
const managerKey = 'contractDocManager_' + category;

if (window[managerKey]) {
    console.log(`[ContractDoc] Manager for ${category} already exists, skipping initialization`);
    return;
}

new ContractDocumentManager(facilityId, category);
console.log(`[ContractDoc] Manager registered as window.${managerKey}`);
```

**変更後:**
```javascript
if (window.contractDocManager) {
    console.log('[ContractDoc] Manager already exists, skipping initialization');
    return;
}

new ContractDocumentManager(facilityId);
console.log('[ContractDoc] Manager registered as window.contractDocManager');
```

## 影響範囲

### 変更されたファイル

- `resources/views/components/contract-document-manager.blade.php`

### 次のステップ

この変更に対応するため、以下のファイルも修正が必要です：

1. **JavaScriptクラス** (`resources/js/modules/ContractDocumentManager.js`)
   - コンストラクタからカテゴリパラメータを削除
   - `this.category`を固定値`'contracts'`に設定
   - 要素キャッシュメソッドのID参照を更新
   - グローバル登録を`window.contractDocManager`に統一

2. **CSSスタイル** (`resources/css/contract-document-management.css`)
   - カテゴリ固有のスタイルを削除
   - 統一されたIDに対応するスタイルに更新

## 検証項目

- [ ] コンポーネントが正常にレンダリングされる
- [ ] すべてのボタンが正しいIDを持つ
- [ ] モーダルが正しく開閉する
- [ ] フォーム送信が動作する
- [ ] JavaScriptの初期化が成功する
- [ ] ブラウザコンソールにエラーが表示されない

## 注意事項

- この変更は、契約書ドキュメント管理を単一インスタンスとして動作させるための第一段階です
- JavaScriptクラスとCSSの修正が完了するまで、機能は完全には動作しません
- 既存のドキュメントデータには影響しません（データベース変更なし）

## 関連タスク

- タスク 2.1: コンポーネントIDの統一 ✅
- タスク 2.2: カテゴリ属性の削除 ✅
- タスク 2.3: 初期化スクリプトの修正 ✅
- タスク 3: JavaScriptクラスの修正（次のステップ）
- タスク 4: CSSスタイルの修正（次のステップ）
