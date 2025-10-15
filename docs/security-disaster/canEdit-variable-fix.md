# lifeline-document-manager コンポーネント $canEdit 変数エラー修正

## 問題

`resources/views/components/lifeline-document-manager.blade.php` の243行目で `$canEdt` という未定義変数エラーが発生していました。

```
ErrorException
Undefined variable $canEdt
resources/views/components/lifeline-document-manager.blade.php : 243
```

## 原因

ファイルが破損しており、以下の問題がありました：

1. **タイポ**: 243行目で `$canEdit` が `$canEdt` と誤って記述されていた
2. **ファイル破損**: HTMLタグが不完全な状態になっていた
3. **uniqueId対応不足**: `subcategory` パラメータに対応していなかった

## 修正内容

### 1. ファイルの復元

破損したファイルをGitから復元：

```bash
git checkout HEAD -- resources/views/components/lifeline-document-manager.blade.php
```

### 2. subcategory パラメータの追加

コンポーネントのpropsに `subcategory` を追加し、ユニークなIDを生成：

```blade
@props([
    'facility',
    'category',
    'categoryName' => null,
    'subcategory' => null
])

@php
    $categoryDisplayName = $categoryName ?? ucfirst(str_replace('_', ' ', $category));
    $canEdit = auth()->user()->canEditFacility($facility->id);
    // subcategoryがある場合はユニークなIDを生成
    $uniqueId = $subcategory ? "{$category}_{$subcategory}" : $category;
@endphp
```

### 3. ID属性の統一

すべてのHTML要素のID属性を `{{ $category }}` から `{{ $uniqueId }}` に変更：

```bash
sed -i '' 's/{{ $category }}/{{ $uniqueId }}/g' resources/views/components/lifeline-document-manager.blade.php
```

### 4. data属性とURLの修正

以下の箇所は元の `$category` を使用するように修正：

- `data-lifeline-category="{{ $category }}"` - APIリクエストで使用
- フォームのaction URL - ルーティングで使用

```blade
<div class="document-management" 
     data-facility-id="{{ $facility->id }}" 
     data-lifeline-category="{{ $category }}" 
     data-subcategory="{{ $subcategory }}" 
     id="document-management-container-{{ $uniqueId }}">
```

```blade
<form id="create-folder-form-{{ $uniqueId }}" 
      action="/facilities/{{ $facility->id }}/lifeline-documents/{{ $category }}/folders" 
      method="POST">
```

### 5. JavaScript初期化の修正

JavaScriptに `category` と `uniqueId` の両方を渡すように修正：

```javascript
const facilityId = {{ $facility->id }};
const category = '{{ $category }}';
const uniqueId = '{{ $uniqueId }}';
const managerKey = 'lifelineDocManager_' + uniqueId;

// インスタンスを作成
new LifelineDocumentManager(facilityId, category, uniqueId);
```

## 修正後の動作

### 防犯・防災タブでの使用例

```blade
<!-- 防犯カメラ・電子錠ドキュメント管理モーダル -->
<x-lifeline-document-manager
    :facility="$facility"
    category="security_disaster"
    categoryName="防犯カメラ・電子錠"
    subcategory="camera_lock"
/>

<!-- 消防・防災ドキュメント管理モーダル -->
<x-lifeline-document-manager
    :facility="$facility"
    category="security_disaster"
    categoryName="消防・防災"
    subcategory="fire_disaster"
/>
```

### 生成されるID

- 防犯カメラ・電子錠: `security_disaster_camera_lock`
- 消防・防災: `security_disaster_fire_disaster`

これにより、同じページ内で複数のドキュメント管理モーダルが競合せずに動作します。

## テスト項目

- [x] ファイルの構文エラーが解消された
- [ ] 防犯カメラ・電子錠モーダルが正常に開く
- [ ] 消防・防災モーダルが正常に開く
- [ ] 両方のモーダルが独立して動作する
- [ ] フォルダ作成が正常に動作する
- [ ] ファイルアップロードが正常に動作する
- [ ] ファイルダウンロードが正常に動作する

## 影響範囲

### 修正されたファイル

1. `resources/views/components/lifeline-document-manager.blade.php`

### 影響を受けるビュー

1. `resources/views/facilities/security-disaster/index.blade.php` - subcategoryパラメータを使用
2. `resources/views/facilities/lifeline-equipment/*.blade.php` - subcategoryなしで動作（後方互換性あり）

## 後方互換性

`subcategory` パラメータはオプションなので、既存の使用箇所（ライフライン設備タブ）には影響ありません：

```blade
<!-- subcategoryなしでも動作 -->
<x-lifeline-document-manager
    :facility="$facility"
    category="electrical"
    categoryName="電気設備"
/>
```

この場合、`$uniqueId` は `$category` と同じ値になります。

## まとめ

- ファイル破損とタイポを修正
- `subcategory` パラメータを追加してユニークなID生成に対応
- 同じページ内で複数のドキュメント管理モーダルが競合しないように改善
- 後方互換性を維持

## 関連ドキュメント

- [防災・防犯タブ ドキュメント管理機能](./document-management-implementation.md)
- [ライフライン設備ドキュメント管理ガイド](../lifeline-equipment/document-management-guide.md)
