# 契約書編集ボタン修正

## 問題
その他契約書画面の「編集」ボタンをクリックすると、給食契約書の編集画面に遷移していた。

## 原因
`resources/views/facilities/contracts/edit.blade.php` で、タブの `active` クラスと `show active` クラスがハードコードされており、常に給食タブがアクティブになっていた。

コントローラーから `$activeSubTab` 変数が渡されていたが、ビューで使用されていなかった。

## 修正内容

### 1. タブナビゲーションの修正
各タブボタンに動的に `active` クラスを適用するように変更：

```blade
<!-- 修正前 -->
<button class="nav-link active" id="meal-service-edit-tab" ...>

<!-- 修正後 -->
<button class="nav-link {{ ($activeSubTab ?? 'others') === 'meal-service' ? 'active' : '' }}" id="meal-service-edit-tab" ...>
```

### 2. タブコンテンツの修正
各タブペインに動的に `show active` クラスを適用するように変更：

```blade
<!-- 修正前 -->
<div class="tab-pane fade show active" id="meal-service-edit" ...>

<!-- 修正後 -->
<div class="tab-pane fade {{ ($activeSubTab ?? 'others') === 'meal-service' ? 'show active' : '' }}" id="meal-service-edit" ...>
```

### 3. デフォルト値の統一
隠しフィールドのデフォルト値を `'others'` に変更：

```blade
<!-- 修正前 -->
<input type="hidden" name="active_sub_tab" id="activeSubTabField" value="{{ $activeSubTab ?? 'meal-service' }}">

<!-- 修正後 -->
<input type="hidden" name="active_sub_tab" id="activeSubTabField" value="{{ $activeSubTab ?? 'others' }}">
```

## 動作確認

### テストケース
1. **その他契約書の編集ボタン**
   - その他契約書タブの「編集」ボタンをクリック
   - → その他契約書の編集画面が表示される ✓

2. **給食契約書の編集ボタン**
   - 給食契約書タブの「編集」ボタンをクリック
   - → 給食契約書の編集画面が表示される ✓

3. **駐車場契約書の編集ボタン**
   - 駐車場契約書タブの「編集ボタン」をクリック
   - → 駐車場契約書の編集画面が表示される ✓

## 影響範囲
- `resources/views/facilities/contracts/edit.blade.php` のみ
- 既存の機能に影響なし
- 後方互換性あり

## 関連ファイル
- `resources/views/facilities/contracts/edit.blade.php` - 修正済み
- `resources/views/facilities/contracts/index.blade.php` - 変更なし（既に正しいパラメータを渡している）
- `app/Http/Controllers/ContractsController.php` - 変更なし（既に正しく動作している）

## 修正日
2025年10月14日
