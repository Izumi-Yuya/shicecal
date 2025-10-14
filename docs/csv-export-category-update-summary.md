# CSVエクスポート - カテゴリ更新サマリー

## 更新日
2025年10月10日

## 変更内容

### 1. ライフライン設備カテゴリの変更

#### 削除したカテゴリ
- **EV設備** (`category_ev`)
  - EV充電器台数
  - 充電器タイプ
  - 出力(kW)
  - EV設備備考

#### 追加したカテゴリ
- **エレベーター設備** (`category_elevator`)
  - エレベーター有無
  - エレベーターメーカー
  - エレベーター年式
  - エレベーター保守会社
  - エレベーター保守実施日
  - エレベーター設備備考

### 2. 空調・照明設備の修正

#### 変更前のフィールド
```php
$airFields = [
    'air_conditioner_type' => '空調方式',
    'air_conditioner_count' => '空調機器台数',
    'lighting_type' => '照明種別',
    'lighting_control' => '照明制御方式',
    'air_notes' => '空調・照明備考',
];
```

#### 変更後のフィールド（config/csv-export-fields.phpに合わせて統一）
```php
$hvacFields = [
    'hvac_lighting_availability' => '空調・照明設備有無',
    'hvac_lighting_manufacturer' => '空調・照明設備メーカー',
    'hvac_lighting_model_year' => '空調・照明設備年式',
    'hvac_lighting_maintenance_company' => '空調・照明保守会社',
    'hvac_lighting_maintenance_date' => '空調・照明保守実施日',
    'hvac_lighting_notes' => '空調・照明設備備考',
];
```

### 3. 折りたたみ機能の追加

以下のカテゴリに折りたたみボタンを追加：
- ✅ 基本情報
- ✅ 土地情報
- ✅ 建物情報
- ✅ 電気設備
- ✅ 水道設備
- ✅ ガス設備
- ✅ エレベーター設備（新規）
- ✅ 空調・照明設備
- ✅ 防犯・防災設備

## 現在のライフライン設備カテゴリ一覧

1. **電気設備** (`category_electric`)
   - 色: `text-warning`（黄色）
   - アイコン: `fas fa-bolt`

2. **水道設備** (`category_water`)
   - 色: `text-primary`（青色）
   - アイコン: `fas fa-tint`

3. **ガス設備** (`category_gas`)
   - 色: `text-danger`（赤色）
   - アイコン: `fas fa-fire`

4. **エレベーター設備** (`category_elevator`)
   - 色: `text-secondary`（グレー）
   - アイコン: `fas fa-elevator`

5. **空調・照明設備** (`category_hvac`)
   - 色: `text-success`（緑色）
   - アイコン: `fas fa-snowflake`

## データベースフィールドとの対応

### エレベーター設備
- `elevator_availability` → LifelineEquipment.elevator_data['basic_info']['availability']
- `elevator_manufacturer` → LifelineEquipment.elevator_data['basic_info']['manufacturer']
- `elevator_model_year` → LifelineEquipment.elevator_data['basic_info']['model_year']
- `elevator_maintenance_company` → LifelineEquipment.elevator_data['basic_info']['maintenance_company']
- `elevator_maintenance_date` → LifelineEquipment.elevator_data['basic_info']['maintenance_date']
- `elevator_notes` → LifelineEquipment.elevator_data['basic_info']['notes']

### 空調・照明設備
- `hvac_lighting_availability` → LifelineEquipment.hvac_data['basic_info']['availability']
- `hvac_lighting_manufacturer` → LifelineEquipment.hvac_data['basic_info']['manufacturer']
- `hvac_lighting_model_year` → LifelineEquipment.hvac_data['basic_info']['model_year']
- `hvac_lighting_maintenance_company` → LifelineEquipment.hvac_data['basic_info']['maintenance_company']
- `hvac_lighting_maintenance_date` → LifelineEquipment.hvac_data['basic_info']['maintenance_date']
- `hvac_lighting_notes` → LifelineEquipment.hvac_data['basic_info']['notes']

## 変更ファイル

### 1. resources/views/export/csv/index.blade.php
- EV設備セクションを削除
- エレベーター設備セクションを追加（折りたたみ機能付き）
- 空調・照明設備のフィールドを修正（折りたたみ機能付き）
- 防犯・防災設備に折りたたみ機能を追加

### 2. docs/csv-export-category-toggle-implementation.md
- カテゴリ一覧を更新（EV → エレベーター）
- 防犯・防災設備を追加

### 3. config/csv-export-fields.php
- 既に正しく定義されていることを確認
- `lifeline_elevator` と `lifeline_hvac` が適切に設定済み

## テスト項目

### 機能テスト
- [ ] エレベーター設備のフィールドが正しく表示される
- [ ] エレベーター設備のチェックボックスが正常に動作する
- [ ] エレベーター設備のカテゴリチェックボックスで一括選択/解除できる
- [ ] エレベーター設備の折りたたみボタンが正常に動作する
- [ ] 空調・照明設備のフィールドが正しく表示される
- [ ] 空調・照明設備のデータが正しくCSVに出力される
- [ ] 防犯・防災設備の折りたたみボタンが正常に動作する

### データ整合性
- [ ] エレベーター設備のフィールド名がデータベースと一致している
- [ ] 空調・照明設備のフィールド名がデータベースと一致している
- [ ] CSVエクスポート時にデータが正しく抽出される

### UI/UX
- [ ] カテゴリの色分けが適切
- [ ] アイコンが適切に表示される
- [ ] 折りたたみアニメーションが滑らか
- [ ] カウント表示が正確

## 今後の対応

### 必要に応じて確認
1. **FieldValueExtractor.php**
   - エレベーター設備のデータ抽出ロジックが正しいか確認
   - 空調・照明設備のデータ抽出ロジックが正しいか確認

2. **CsvExportService.php**
   - フィールドマッピングが正しいか確認

3. **ExportService.php**
   - カテゴリカウントの計算が正しいか確認

## 参考情報

### 関連ドキュメント
- `docs/csv-export-category-toggle-implementation.md` - 折りたたみ機能の実装詳細
- `config/csv-export-fields.php` - フィールド定義
- `config/lifeline-equipment.php` - ライフライン設備の設定

### 関連ファイル
- `resources/views/export/csv/index.blade.php` - CSVエクスポート画面
- `resources/js/modules/export.js` - エクスポート機能のJavaScript
- `resources/css/pages/export.css` - エクスポート画面のスタイル
