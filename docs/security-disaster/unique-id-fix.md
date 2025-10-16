# 消防・防災ドキュメント管理 - ユニークID修正

## 問題

消防・防災タブには2つのドキュメント管理モーダルがあります：
1. 防犯カメラ・電子錠用
2. 消防・防災用

両方とも同じ `category="security_disaster"` を使用しているため、同じIDのHTML要素が重複して作成され、JavaScriptが正しく動作しません。

## 解決策

`lifeline-document-manager` コンポーネントに `subcategory` パラメータを追加し、ユニークなIDを生成します。

### 変更が必要なファイル

1. **resources/views/components/lifeline-document-manager.blade.php**
   - `subcategory` プロパティを追加
   - `$uniqueId` 変数を作成: `$subcategory ? "{$category}_{$subcategory}" : $category`
   - すべてのHTML要素のIDを `{{ $category }}` から `{{ $uniqueId }}` に変更
   - ただし、APIエンドポイントのURLは `{{ $category }}` のまま（バックエンドは category で処理）

2. **resources/js/modules/LifelineDocumentManager.js**
   - コンストラクタに `uniqueId` パラメータを追加
   - DOM要素の検索に `uniqueId` を使用

### 変更手順

#### 1. コンポーネントのprops定義を更新

```blade
@props([
    'facility',
    'category',
    'categoryName' => null,
    'subcategory' => null  // 追加
])

@php
    $categoryDisplayName = $categoryName ?? ucfirst(str_replace('_', ' ', $category));
    $canEdit = auth()->user()->canEditFacility($facility->id);
    // subcategoryがある場合はユニークなIDを生成
    $uniqueId = $subcategory ? "{$category}_{$subcategory}" : $category;
@endphp
```

#### 2. HTML要素のIDを更新

以下のパターンで置換：
- `id="xxx-{{ $category }}"` → `id="xxx-{{ $uniqueId }}"`
- `for="xxx-{{ $category }}"` → `for="xxx-{{ $uniqueId }}"`
- `name="xxx-{{ $category }}"` → `name="xxx-{{ $uniqueId }}"`

**注意**: APIエンドポイントのURLは変更しない：
- `action="/facilities/{{ $facility->id }}/lifeline-documents/{{ $category }}/folders"` (そのまま)

#### 3. JavaScriptの初期化スクリプトを更新

```javascript
const uniqueId = '{{ $uniqueId }}';
const managerKey = 'lifelineDocManager_' + uniqueId;

// インスタンス作成時にuniqueIdを渡す
new LifelineDocumentManager(facilityId, category, uniqueId);
```

#### 4. LifelineDocumentManager.jsを更新

コンストラクタを修正：

```javascript
constructor(facilityId, category, uniqueId = null) {
    this.facilityId = facilityId;
    this.category = category;
    this.uniqueId = uniqueId || category; // uniqueIdがない場合はcategoryを使用
    
    // DOM要素の検索にuniqueIdを使用
    this.container = document.getElementById(`document-management-container-${this.uniqueId}`);
    
    // グローバル登録にもuniqueIdを使用
    const managerKey = `lifelineDocManager_${this.uniqueId}`;
    window[managerKey] = this;
}
```

すべてのDOM要素検索を `this.uniqueId` を使用するように更新：
- `document.getElementById(`create-folder-btn-${this.category}`)` → `document.getElementById(`create-folder-btn-${this.uniqueId}`)`

### テスト

1. 防犯カメラ・電子錠のドキュメントボタンをクリック
2. 「新しいフォルダ」ボタンが動作することを確認
3. 消防・防災のドキュメントボタンをクリック
4. 「新しいフォルダ」ボタンが動作することを確認
5. ブラウザコンソールで重複IDエラーがないことを確認

### 期待される結果

- 防犯カメラ・電子錠: `document-management-container-security_disaster_camera_lock`
- 消防・防災: `document-management-container-security_disaster_fire_disaster`

両方のモーダルが独立して動作し、JavaScriptエラーが発生しない。
