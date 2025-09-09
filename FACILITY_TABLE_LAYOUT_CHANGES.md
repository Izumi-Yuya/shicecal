# 基本情報テーブルレイアウト変更

## 変更内容

基本情報テーブルの列レイアウトを以下のように変更しました：

### 最新の変更
- ラベルの背景色をプライマリカラーグラデーション（#00B4E3 → #0099CC）に復元
- テーブル内のスクロール機能を無効化（max-height: 70vh と overflow-y: auto を削除）
- テーブル全体が自然な高さで表示されるように変更

### 変更前
- 全ての列が25%の固定幅
- ラベル（th）が中央揃え
- 値（td）が左揃え

### 変更後
- **1列目（ラベル）**: 左揃え、最大文字数に基づく幅調整、プライマリカラーグラデーション背景
- **2列目（値）**: 左揃え、自動幅（余白があっても良い）
- **3列目（ラベル）**: 右揃え、最大文字数に基づく幅調整、プライマリカラーグラデーション背景
- **4列目（値）**: 右揃え、自動幅
- **テーブル表示**: スクロールなし、自然な高さで全内容を表示

## ラベル幅の計算根拠

### 最大文字数分析
- **左側ラベル最大**: 7文字（住所（建物名）、フリーダイヤル、メールアドレス）
- **右側ラベル最大**: 6文字（事業所コード）

### 幅設定
- **デスクトップ**: 8.5em（7文字 + パディング）
- **タブレット**: 7.5em（コンパクト表示）
- **モバイル**: 6.5em（最小表示）

## 技術的変更

### CSS変更点 (`resources/css/pages/facility-table-view.css`)

1. **CSS変数定義**
   ```css
   :root {
     --label-width-desktop: 8.5em;
     --label-width-tablet: 7.5em;
     --label-width-mobile: 6.5em;
   }
   ```

2. **ヘッダーセル（th）の基本設定**
   ```css
   .facility-table-view .table th {
     background: var(--primary-color);
     color: white;
     border: 1px solid var(--primary-dark);
     width: var(--label-width-desktop);
     min-width: var(--label-width-desktop);
     max-width: var(--label-width-desktop);
     white-space: nowrap;
     text-align: left;
     letter-spacing: 0.02em;
   }
   ```

3. **ラベル列の設定**
   ```css
   .facility-table-view .table .label-column {
     background: var(--primary-color);
     color: white;
     border: 1px solid var(--primary-dark);
     width: var(--label-width-desktop);
     min-width: var(--label-width-desktop);
     max-width: var(--label-width-desktop);
     white-space: nowrap;
     letter-spacing: 0.02em;
   }
   ```

4. **列別の配置設定**
   ```css
   /* 1列目（ラベル）- 左揃え */
   .facility-table-view .table th:nth-child(1) {
     text-align: left;
   }

   /* 2列目（値）- 左揃え、自動幅 */
   .facility-table-view .table td:nth-child(2) {
     text-align: left;
     width: auto;
   }

   /* 3列目（ラベル）- 右揃え */
   .facility-table-view .table th:nth-child(3) {
     text-align: right;
   }

   /* 4列目（値）- 右揃え、自動幅 */
   .facility-table-view .table td:nth-child(4) {
     text-align: right;
     width: auto;
   }
   ```

### レスポンシブ対応

- **タブレット画面（992px以下）**: ラベル幅を7.5emに縮小、配置は維持
- **モバイル画面（768px以下）**: ラベル幅を6.5emに縮小、全て左揃えにリセット
- **小画面（576px以下）**: 最小幅でコンパクト表示

## 影響範囲

- `resources/views/facilities/partials/basic-info-table.blade.php` - テーブル表示テンプレート
- `resources/css/pages/facility-table-view.css` - スタイル定義

## テスト

- CSS構文チェック: ✅ 正常
- ビルドプロセス: ✅ 正常
- レスポンシブデザイン: ✅ 対応済み

## 使用方法

変更は自動的に適用されます。施設詳細画面でテーブル表示モードに切り替えると、新しいレイアウトが表示されます。

## 注意事項

- ラベル幅は日本語文字数に基づいて最適化されています（最大7文字対応）
- ラベルの背景色はプライマリカラーグラデーション（#00B4E3 → #0099CC）に設定されています
- テーブルはスクロールせず、全内容が自然な高さで表示されます
- レスポンシブデザインで画面サイズに応じてラベル幅が調整されます
- モバイル表示では全て左揃えになり、ラベル幅は最小に設定されます
- 印刷時やハイコントラストモードでもグラデーション色が適切に表示されます
- アクセシビリティ機能は維持されています

## 文字数別ラベル一覧

### 左側ラベル
- 7文字: 住所（建物名）、フリーダイヤル、メールアドレス
- 5文字: FAX番号
- 4文字: 郵便番号、電話番号
- 3文字: 会社名、施設名、URL
- 2文字: 住所

### 右側ラベル
- 6文字: 事業所コード
- 5文字: 内SS数
- 4文字: 指定番号、開設年数、建物構造、建物階数
- 3文字: 開設日、居室数、定員数