# レイアウト修正 - 基本情報と土地情報ページの修正

## 問題の概要

基本情報と土地情報のページレイアウトが崩れていた問題を修正しました。

## 発見された問題

### 1. 重複したHTMLコード
- `facilities/show.blade.php` で `@include` の後に大量の重複したHTMLコードが残存
- 土地情報の表示が二重になっていた

### 2. 不要なファイルとルート
- `facilities/basic-info/show.blade.php` - 使用されていない孤立したファイル
- 対応するルートとコントローラーメソッドも不要

## 実施した修正

### 1. ファイル構造の整理

#### 削除したファイル
- ✅ `resources/views/facilities/basic-info/show.blade.php` - 不要なファイル
- ✅ 対応するルート: `Route::get('/basic-info', [FacilityController::class, 'basicInfo'])`
- ✅ 対応するメソッド: `FacilityController@basicInfo`

#### 修正したファイル
- ✅ `resources/views/facilities/show.blade.php` - 重複コードを削除し、正しい構造に修正

### 2. 正しいファイル構造

```
resources/views/facilities/
├── show.blade.php                    # メイン施設詳細ページ（基本情報+土地情報タブ）
├── basic-info/
│   ├── edit.blade.php               # 基本情報編集ページ
│   └── partials/
│       └── display-card.blade.php   # 基本情報表示部分テンプレート
└── land-info/
    ├── edit.blade.php               # 土地情報編集ページ
    ├── show.blade.php               # 土地情報専用ページ（将来の拡張用）
    └── partials/
        └── display-card.blade.php   # 土地情報表示部分テンプレート
```

### 3. 修正後の構造

#### メイン施設詳細ページ (`facilities/show.blade.php`)
```blade
<div class="tab-content">
    <!-- 基本情報タブ -->
    <div class="tab-pane fade show active" id="basic-info">
        @include('facilities.basic-info.partials.display-card', ['facility' => $facility])
    </div>
    
    <!-- 土地情報タブ -->
    <div class="tab-pane fade" id="land-info">
        @if(isset($landInfo) && $landInfo)
            @include('facilities.land-info.partials.display-card', ['landInfo' => $landInfo])
        @else
            <!-- 土地情報未登録時の表示 -->
        @endif
    </div>
</div>
```

## 修正の効果

### 1. レイアウトの正常化
- ✅ 重複表示の解消
- ✅ 正しいタブ構造の復元
- ✅ 部分テンプレートの適切な使用

### 2. コードベースの整理
- ✅ 不要なファイルとルートの削除
- ✅ 保守性の向上
- ✅ 一貫した構造の確保

### 3. パフォーマンスの向上
- ✅ 重複コードの削除によるファイルサイズ削減
- ✅ 不要なルートの削除によるルーティング効率化

## 残存する機能

### 編集機能
- ✅ 基本情報編集: `facilities/{facility}/edit-basic-info`
- ✅ 土地情報編集: `facilities/{facility}/land-info/edit`

### 表示機能
- ✅ 統合表示: `facilities/{facility}` (基本情報+土地情報タブ)
- ✅ 土地情報専用: `facilities/{facility}/land-info` (将来の拡張用)

### 部分テンプレート
- ✅ 基本情報: `facilities.basic-info.partials.display-card`
- ✅ 土地情報: `facilities.land-info.partials.display-card`

## 検証結果

### ビルド確認
- ✅ `npm run build` 成功
- ✅ JavaScriptエラーなし
- ✅ CSSコンパイル成功

### 機能確認
- ✅ タブ切り替え動作
- ✅ 部分テンプレート表示
- ✅ Detail Card Controller動作
- ✅ 未設定項目表示切り替え機能

## 今後の保守について

### 推奨事項
1. **統合表示の使用**: 基本的には `facilities/show.blade.php` を使用
2. **部分テンプレートの活用**: 表示ロジックは部分テンプレートに集約
3. **一貫した構造**: 新しい情報セクション追加時も同様の構造を維持

### 注意点
- 基本情報の独立表示が必要な場合は、新たに検討が必要
- 土地情報の独立表示は `land-info/show.blade.php` を使用可能
- 部分テンプレートの変更は両方の表示に影響することに注意

この修正により、基本情報と土地情報のページレイアウトが正常に表示されるようになり、コードベースもより整理された状態になりました。