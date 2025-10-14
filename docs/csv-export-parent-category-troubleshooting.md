# CSVエクスポート - 親カテゴリ全選択のトラブルシューティング

## 問題
防犯・防災設備やライフライン設備などの親カテゴリの全選択チェックボックスをクリックしても反応しない。

## 原因の可能性

### 1. ブラウザキャッシュ
最も一般的な原因は、ブラウザが古いJavaScriptファイルをキャッシュしていることです。

### 2. ビルドが実行されていない
JavaScriptの変更後、`npm run build`が実行されていない可能性があります。

### 3. データ属性の不一致
HTMLの`data-parent-category`属性とJavaScriptのセレクタが一致していない可能性があります。

## 解決方法

### ステップ1: ビルドの実行
```bash
npm run build
```

### ステップ2: ブラウザキャッシュのクリア

#### Chrome/Edge
1. `Cmd + Shift + R` (Mac) または `Ctrl + Shift + R` (Windows) でハードリフレッシュ
2. または、開発者ツールを開いた状態でリロードボタンを長押し → "キャッシュの消去とハード再読み込み"

#### Firefox
1. `Cmd + Shift + R` (Mac) または `Ctrl + Shift + R` (Windows)

#### Safari
1. `Cmd + Option + E` でキャッシュを空にする
2. `Cmd + R` でリロード

### ステップ3: ブラウザコンソールでデバッグ

1. ブラウザの開発者ツールを開く（F12 または Cmd+Option+I）
2. Consoleタブを開く
3. 親カテゴリのチェックボックスをクリック
4. 以下のようなログが表示されるか確認：

```
handleCategoryChange: {category: "security", isChecked: true, isParentCategory: true}
Found subcategories: 4
Processing subcategory: security_camera
Processing subcategory: security_lock
Processing subcategory: fire
Processing subcategory: disaster
```

### ステップ4: HTML構造の確認

親カテゴリのチェックボックスに以下の属性があることを確認：

```html
<input class="form-check-input category-checkbox"
       type="checkbox"
       id="category_security"
       data-category="security"
       data-parent-category="true"
       autocomplete="off">
```

サブカテゴリのチェックボックスに以下の属性があることを確認：

```html
<input class="form-check-input subcategory-checkbox"
       type="checkbox"
       id="category_security_camera"
       data-subcategory="security_camera"
       data-parent-category="security"
       autocomplete="off">
```

フィールドのチェックボックスに以下の属性があることを確認：

```html
<input class="form-check-input field-checkbox"
       type="checkbox"
       name="export_fields[]"
       value="security_camera_management_company"
       id="field_security_camera_management_company"
       data-category="security_camera"
       data-subcategory="security_camera"
       autocomplete="off">
```

## デバッグ用コンソールログ

現在、`handleCategoryChange`メソッドにデバッグ用のコンソールログが追加されています：

```javascript
console.log('handleCategoryChange:', { category, isChecked, isParentCategory });
console.log('Found subcategories:', subcategoryCheckboxes.length);
console.log('Processing subcategory:', subcategoryName);
```

これらのログを確認することで、以下を判断できます：

1. **イベントが発火しているか**: `handleCategoryChange`のログが表示されるか
2. **親カテゴリとして認識されているか**: `isParentCategory: true`になっているか
3. **サブカテゴリが見つかっているか**: `Found subcategories`の数が正しいか
4. **各サブカテゴリが処理されているか**: `Processing subcategory`のログが表示されるか

## よくある問題と解決策

### 問題1: ログが全く表示されない
**原因**: イベントリスナーが登録されていない
**解決策**: 
- ページを完全にリロード
- `ExportManager`が正しく初期化されているか確認
- `setupEventListeners()`が呼ばれているか確認

### 問題2: `isParentCategory: false`と表示される
**原因**: HTML属性が正しく設定されていない
**解決策**:
- `data-parent-category="true"`属性を確認
- 属性値が文字列の`"true"`であることを確認（ブール値ではない）

### 問題3: `Found subcategories: 0`と表示される
**原因**: サブカテゴリのセレクタが一致していない
**解決策**:
- サブカテゴリの`data-parent-category`属性が親カテゴリの`data-category`と一致しているか確認
- 例: 親が`data-category="security"`なら、サブは`data-parent-category="security"`

### 問題4: サブカテゴリはチェックされるがフィールドがチェックされない
**原因**: フィールドの`data-subcategory`属性が正しくない
**解決策**:
- フィールドの`data-subcategory`属性がサブカテゴリの`data-subcategory`と一致しているか確認

## 検証手順

### 1. 防犯・防災設備の検証
1. 親カテゴリ「防犯・防災設備」をチェック
2. 以下がすべてチェックされることを確認：
   - サブカテゴリ: 防犯カメラ、電子錠、消防、防災
   - 全フィールド（13項目）

### 2. ライフライン設備の検証
1. 親カテゴリ「ライフライン設備」をチェック
2. 以下がすべてチェックされることを確認：
   - サブカテゴリ: 電気、水道、ガス、エレベーター、空調
   - 全フィールド

### 3. カウント表示の検証
1. 親カテゴリをチェック
2. カウント表示が正しく更新されることを確認
3. 例: 「(13/13 項目選択中)」

## 本番環境での注意事項

### デバッグログの削除
本番環境にデプロイする前に、デバッグ用の`console.log`を削除してください：

```javascript
// 削除する行
console.log('handleCategoryChange:', { category, isChecked, isParentCategory });
console.log('Found subcategories:', subcategoryCheckboxes.length);
console.log('Processing subcategory:', subcategoryName);
console.log('Found category fields:', categoryFields.length);
```

### キャッシュ対策
本番環境では、以下の対策を検討してください：

1. **バージョン管理**: Viteが自動的にファイル名にハッシュを追加
2. **キャッシュヘッダー**: 適切なCache-Controlヘッダーを設定
3. **サービスワーカー**: 必要に応じてキャッシュ戦略を実装

## 関連ファイル

- `resources/views/export/csv/index.blade.php` - HTML構造
- `resources/js/modules/export.js` - JavaScript処理
- `resources/css/pages/export.css` - スタイル

## 参考情報

### 親カテゴリとサブカテゴリの関係

```
防犯・防災設備 (security)
├── 防犯カメラ (security_camera)
├── 電子錠 (security_lock)
├── 消防 (fire)
└── 防災 (disaster)

ライフライン設備 (lifeline)
├── 電気 (electric)
├── 水道 (water)
├── ガス (gas)
├── エレベーター (elevator)
└── 空調 (hvac)
```

### データフロー

1. ユーザーが親カテゴリをチェック
2. `handleCategoryChange`イベントが発火
3. `isParentCategory === 'true'`を確認
4. `data-parent-category="${category}"`でサブカテゴリを検索
5. 各サブカテゴリをチェック
6. `selectSubcategoryFields()`で各サブカテゴリのフィールドをチェック
7. `updateSelectionStatus()`でカウントを更新
