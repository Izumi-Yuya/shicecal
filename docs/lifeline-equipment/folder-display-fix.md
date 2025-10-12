# ライフライン設備ドキュメント管理 - フォルダ表示問題の解決

## 問題の概要

**発生日**: 2025年10月13日

**症状**: 電気タブのドキュメント管理でフォルダを作成しても、ドキュメント一覧に表示されない

**影響範囲**: すべてのライフライン設備カテゴリ（電気、ガス、水道、エレベーター等）

## 問題の詳細

### 再現手順
1. 施設詳細画面で電気設備タブを開く
2. 「ドキュメント」ボタンをクリックしてドキュメント管理モーダルを開く
3. 「新しいフォルダ」ボタンをクリック
4. フォルダ名を入力して「作成」ボタンをクリック
5. 成功メッセージが表示されるが、ドキュメント一覧にフォルダが表示されない

### 期待される動作
- フォルダ作成後、ドキュメント一覧が自動的に更新される
- 作成されたフォルダが一覧に表示される
- デフォルトサブフォルダ（点検報告書、保守記録等）も表示される

### 実際の動作
- フォルダ作成は成功する（データベースに保存される）
- 成功メッセージは表示される
- しかし、ドキュメント一覧は空のまま表示される

## 原因分析

### 根本原因
**BladeテンプレートのHTML要素IDとJavaScriptで参照するIDが一致していなかった**

### 詳細な原因

#### 1. ID命名規則の不一致

**Bladeテンプレート** (`lifeline-document-manager.blade.php`):
```blade
<!-- カテゴリ接尾辞なし -->
<div id="loading-indicator">...</div>
<div id="error-message">...</div>
<div id="empty-state">...</div>
<div id="document-list">...</div>
```

**JavaScript** (`LifelineDocumentManager.js`):
```javascript
// _idヘルパーメソッドでカテゴリ接尾辞を追加
_id(name) {
  return `${name}-${this.category}`;
}

// 使用例
const loadingIndicator = container.querySelector(`#${this._id('loading-indicator')}`);
// 実際には #loading-indicator-electrical を探す
```

#### 2. 問題の流れ

1. フォルダ作成成功後、`loadDocuments()`が呼び出される
2. APIからドキュメントデータを取得（成功）
3. `renderDocuments(data)`が呼び出される
4. `renderListView()`でフォルダとファイルのHTMLを生成
5. しかし、表示/非表示の切り替えで要素が見つからない：
   - JavaScript: `#loading-indicator-electrical`を探す
   - HTML: `#loading-indicator`しか存在しない
6. 要素が見つからないため、表示状態が更新されない
7. 結果として、ローディング表示が残り、ドキュメント一覧が表示されない

## 解決方法

### 修正内容

#### 1. Bladeテンプレートの修正

**ファイル**: `resources/views/components/lifeline-document-manager.blade.php`

すべての要素IDにカテゴリ接尾辞を追加：

```blade
<!-- 修正前 -->
<div id="loading-indicator">...</div>
<div id="error-message">
    <span id="error-text"></span>
</div>
<div id="empty-state">...</div>
<div id="document-list">...</div>

<!-- 修正後 -->
<div id="loading-indicator-{{ $category }}">...</div>
<div id="error-message-{{ $category }}">
    <span id="error-text-{{ $category }}"></span>
</div>
<div id="empty-state-{{ $category }}">...</div>
<div id="document-list-{{ $category }}">...</div>
```

**変更箇所**:
- `loading-indicator` → `loading-indicator-{{ $category }}`
- `error-message` → `error-message-{{ $category }}`
- `error-text` → `error-text-{{ $category }}`
- `empty-state` → `empty-state-{{ $category }}`
- `document-list` → `document-list-{{ $category }}`

#### 2. サーバー側ログの追加

**ファイル**: `app/Services/LifelineDocumentService.php`

デバッグと今後のトラブルシューティングのためにログを追加：

```php
// getCategoryDocumentsメソッド
if (!$rootFolder) {
    Log::info('Category root folder not found, returning empty data', [
        'facility_id' => $facility->id,
        'category' => $category,
        'category_name' => $categoryName,
    ]);
    // ...
}

Log::info('Category documents retrieved', [
    'facility_id' => $facility->id,
    'category' => $category,
    'root_folder_id' => $rootFolder->id,
    'current_folder_id' => $currentFolder->id,
    'folders_count' => count($result['folders'] ?? []),
    'files_count' => count($result['files'] ?? []),
]);

// createCategoryFolderメソッド
Log::info('Root folder obtained for category', [
    'facility_id' => $facility->id,
    'category' => $category,
    'root_folder_id' => $rootFolder->id,
    'root_folder_name' => $rootFolder->name,
    'root_folder_path' => $rootFolder->path,
]);

Log::info('Lifeline category folder created successfully', [
    'facility_id' => $facility->id,
    'category' => $category,
    'folder_id' => $newFolder->id,
    'folder_name' => $folderName,
    'parent_folder_id' => $parentFolder->id,
    'root_folder_id' => $rootFolder->id,
    'user_id' => $user->id,
]);
```

### 修正の影響範囲

#### 影響を受けるファイル
1. `resources/views/components/lifeline-document-manager.blade.php` - Bladeテンプレート
2. `app/Services/LifelineDocumentService.php` - サービス層

#### 影響を受けるカテゴリ
すべてのライフライン設備カテゴリ：
- 電気設備 (`electrical`)
- ガス設備 (`gas`)
- 水道設備 (`water`)
- エレベーター設備 (`elevator`)
- 空調・照明設備 (`hvac_lighting`)
- 防犯・防災設備 (`security_disaster`)

## 検証方法

### 1. 基本動作の確認

```bash
# 開発サーバーを起動
php artisan serve
npm run dev
```

### 2. 手動テスト手順

1. **フォルダ作成テスト**
   - 施設詳細画面を開く
   - 電気設備タブを選択
   - 「ドキュメント」ボタンをクリック
   - 「新しいフォルダ」ボタンをクリック
   - フォルダ名を入力（例: "テストフォルダ"）
   - 「作成」ボタンをクリック
   - ✅ 成功メッセージが表示される
   - ✅ ドキュメント一覧にフォルダが表示される

2. **デフォルトサブフォルダの確認**
   - ✅ 「点検報告書」フォルダが表示される
   - ✅ 「保守記録」フォルダが表示される
   - ✅ 「取扱説明書」フォルダが表示される
   - ✅ 「証明書類」フォルダが表示される
   - ✅ 「過去分報告書」フォルダが表示される

3. **他のカテゴリでの確認**
   - ガス設備タブで同様のテストを実施
   - 水道設備タブで同様のテストを実施
   - エレベーター設備タブで同様のテストを実施

### 3. ブラウザコンソールでの確認

開発者ツールのコンソールで以下のログを確認：

```javascript
// フォルダ作成時
[LifelineDoc] Root folder obtained for category
[LifelineDoc] Lifeline category folder created successfully

// ドキュメント一覧読み込み時
[LifelineDoc] Rendering documents for electrical
[LifelineDoc] Data structure: { folders: 6, files: 0, ... }
[LifelineDoc] Container found: <div class="document-management">
[LifelineDoc] Loading indicator hidden
[LifelineDoc] Document list shown
[LifelineDoc] Has data: true (folders: 6, files: 0)
```

### 4. サーバーログでの確認

`storage/logs/laravel.log`で以下のログを確認：

```
[2025-10-13 ...] local.INFO: Root folder obtained for category
[2025-10-13 ...] local.INFO: Lifeline category folder created successfully
[2025-10-13 ...] local.INFO: Category documents retrieved
```

## トラブルシューティング

### 問題: フォルダが作成されるが表示されない

**確認事項**:
1. ブラウザのコンソールでJavaScriptエラーがないか確認
2. ネットワークタブでAPIレスポンスを確認
3. サーバーログで`Category documents retrieved`のログを確認

**解決方法**:
```bash
# キャッシュクリア
php artisan cache:clear
php artisan view:clear

# アセット再ビルド
npm run build
```

### 問題: 要素が見つからないエラー

**エラーメッセージ**:
```
[LifelineDoc] Container not found for category: electrical
[LifelineDoc] tbody not found: #document-list-body-electrical
```

**原因**: BladeテンプレートのIDとJavaScriptの参照が一致していない

**解決方法**:
1. Bladeテンプレートで要素IDを確認
2. JavaScriptで`_id()`ヘルパーメソッドを使用しているか確認
3. 両方が一致するように修正

### 問題: モーダルが開かない

**確認事項**:
1. モーダルのIDが正しいか確認
2. Bootstrap JavaScriptが読み込まれているか確認
3. z-indexの問題がないか確認

**解決方法**:
```javascript
// モーダルhoisting処理を確認
hoistModals(documentSection);
```

## 今後の予防策

### 1. 命名規則の統一

**ルール**: すべてのカテゴリ固有の要素IDには必ずカテゴリ接尾辞を付ける

```blade
<!-- 良い例 -->
<div id="element-name-{{ $category }}">...</div>

<!-- 悪い例 -->
<div id="element-name">...</div>
```

### 2. JavaScriptでの要素参照

**ルール**: カテゴリ固有の要素を参照する場合は必ず`_id()`ヘルパーを使用

```javascript
// 良い例
const element = container.querySelector(`#${this._id('element-name')}`);

// 悪い例
const element = container.querySelector(`#element-name-${this.category}`);
```

### 3. テンプレート作成時のチェックリスト

新しいカテゴリ固有のコンポーネントを作成する場合：

- [ ] すべての要素IDにカテゴリ接尾辞を付ける
- [ ] JavaScriptで`_id()`ヘルパーを使用する
- [ ] 複数カテゴリで動作確認する
- [ ] ブラウザコンソールでエラーがないか確認
- [ ] サーバーログでエラーがないか確認

### 4. コードレビューのポイント

- ID命名規則の一貫性
- JavaScript要素参照の正確性
- エラーハンドリングの適切性
- ログ出力の充実度

## 関連ドキュメント

- [モーダル実装ガイドライン](./modal-implementation-guide.md)
- [モーダルz-index修正まとめ](./modal-zindex-fix-summary.md)
- [ライフライン設備ドキュメント管理README](./README.md)

## 変更履歴

| 日付 | 変更内容 | 担当者 |
|------|---------|--------|
| 2025-10-13 | 初版作成 - フォルダ表示問題の解決 | - |

## まとめ

この問題は、BladeテンプレートとJavaScriptの間でID命名規則が一致していなかったことが原因でした。すべての要素IDにカテゴリ接尾辞を追加することで、複数のカテゴリが同時に存在する環境でも正しく動作するようになりました。

今後は、カテゴリ固有のコンポーネントを作成する際は、必ずID命名規則を統一し、`_id()`ヘルパーメソッドを使用することで、同様の問題を予防できます。
