# CSV出力における図面・修繕履歴フィールド

## 概要

施設管理システムのCSV出力機能に図面情報と修繕履歴のフィールドを追加しました。これにより、各施設の図面備考データと修繕履歴データをCSV形式でエクスポートできるようになります。

## 追加されたフィールド

### 図面
- `drawing_notes` - 図面備考

### 修繕履歴
- `maintenance_latest_date` - 修繕履歴_最新修繕日
- `maintenance_latest_content` - 修繕履歴_最新修繕内容
- `maintenance_latest_cost` - 修繕履歴_最新修繕費用
- `maintenance_latest_contractor` - 修繕履歴_最新施工業者
- `maintenance_latest_category` - 修繕履歴_最新カテゴリ
- `maintenance_latest_subcategory` - 修繕履歴_最新サブカテゴリ
- `maintenance_latest_contact_person` - 修繕履歴_最新担当者
- `maintenance_latest_phone_number` - 修繕履歴_最新電話番号
- `maintenance_latest_notes` - 修繕履歴_最新備考
- `maintenance_latest_warranty_period` - 修繕履歴_最新保証期間
- `maintenance_total_count` - 修繕履歴_総件数
- `maintenance_total_cost` - 修繕履歴_総費用

## データ形式

### 図面備考フィールド
- 備考が入力されている場合：備考内容を出力
- 備考がない場合：空文字列を出力

### 修繕履歴フィールド

#### 最新データフィールド
- 修繕履歴が存在する場合：最新の修繕履歴（修繕日が最も新しい）のデータを出力
- 修繕履歴が存在しない場合：空文字列を出力

#### 集計フィールド
- `maintenance_total_count`：修繕履歴の総件数（数値）
- `maintenance_total_cost`：修繕履歴の総費用（数値、修繕履歴がない場合は「0」）

#### 特殊フィールド
- `maintenance_latest_date`：日付形式（YYYY-MM-DD）
- `maintenance_latest_cost`：数値形式
- `maintenance_latest_category`：日本語ラベル（外装、内装リニューアル、その他）
- `maintenance_latest_subcategory`：日本語ラベル（防水、塗装、内装リニューアル等）
- `maintenance_latest_warranty_period`：「○年」形式

## 使用方法

1. CSV出力画面で「図面」セクションを展開
2. 必要な図面フィールドにチェックを入れる
3. 施設を選択してCSV出力を実行

## 技術的詳細

### データベース関連
- `FacilityDrawing`モデルから図面データを取得
- `facility.drawing`リレーションを使用してデータを読み込み

### 実装ファイル
- `app/Services/ExportService.php` - フィールド定義とデータ抽出ロジック
- `resources/views/export/csv/index.blade.php` - UI上のフィールド選択
- `tests/Unit/Services/ExportServiceTest.php` - テストケース

### パフォーマンス
- 図面データは`with(['drawing'])`でEager Loadingを使用
- 修繕履歴データは`with(['maintenanceHistories'])`でEager Loadingを使用
- N+1問題を回避するため事前にリレーションを読み込み

## 注意事項

- 図面データがない施設の場合、図面備考フィールドは空文字列が出力されます
- 修繕履歴がない施設の場合、最新データフィールドは空文字列、集計フィールドは「0」が出力されます
- 最新データは修繕日（maintenance_date）で判定され、最も新しい日付の修繕履歴が対象となります
- ファイル名やファイルパス情報は含まれません（テキスト入力項目のみ）