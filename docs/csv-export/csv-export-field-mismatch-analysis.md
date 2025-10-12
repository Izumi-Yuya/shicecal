# CSVエクスポート項目不一致の分析と修正方針

## 問題の概要
CSVエクスポート画面で表示されている項目と、実際に出力される項目に差異がある。

## 発見された問題点

### 1. ビューとConfigの不一致

#### ビューファイル (resources/views/export/csv/index.blade.php)
```php
// 電気設備
'power_company' => '電力会社',
'power_capacity' => '受電容量',
'power_backup' => '自家発電設備の有無',
'power_backup_capacity' => '自家発電容量',
'power_notes' => '電気備考',

// 水道設備
'water_source' => '水源種別',
'water_meter_count' => '水道メーター数',
'water_tank_capacity' => '受水槽容量',
'water_quality_check' => '水質検査有無',
'water_notes' => '水道備考',

// ガス設備
'gas_company' => 'ガス会社',
'gas_type' => 'ガス種別',
'gas_meter_count' => 'ガスメーター数',
'gas_tank_capacity' => 'ガスタンク容量',
'gas_notes' => 'ガス備考',

// エレベーター設備
'elevator_availability' => 'エレベーター有無',
'elevator_manufacturer' => 'エレベーターメーカー',
'elevator_model_year' => 'エレベーター年式',
'elevator_maintenance_company' => 'エレベーター保守会社',
'elevator_maintenance_date' => 'エレベーター保守実施日',
'elevator_notes' => 'エレベーター設備備考',

// 空調・照明設備
'hvac_lighting_availability' => '空調・照明設備有無',
'hvac_lighting_manufacturer' => '空調・照明設備メーカー',
'hvac_lighting_model_year' => '空調・照明設備年式',
'hvac_lighting_maintenance_company' => '空調・照明保守会社',
'hvac_lighting_maintenance_date' => '空調・照明保守実施日',
'hvac_lighting_notes' => '空調・照明設備備考',
```

#### Configファイル (config/csv-export-fields.php)
```php
'lifeline_electrical' => [
    'electrical_contractor' => '電力会社',
    'electrical_safety_management_company' => '電気保安管理業者',
    'electrical_maintenance_inspection_date' => '電気保守点検実施日',
    'electrical_pas_availability' => 'PAS有無',
    'electrical_pas_update_date' => 'PAS更新年月日',
    'electrical_cubicle_availability' => 'キュービクル有無',
    'electrical_cubicle_manufacturers' => 'キュービクルメーカー',
    'electrical_cubicle_model_years' => 'キュービクル年式',
    'electrical_generator_availability' => '非常用発電機有無',
    'electrical_generator_manufacturers' => '非常用発電機メーカー',
    'electrical_generator_model_years' => '非常用発電機年式',
    'electrical_notes' => '電気設備備考',
],

'lifeline_water' => [
    'water_contractor' => '水道契約会社',
    'water_tank_cleaning_company' => '受水槽清掃業者',
    'water_tank_cleaning_date' => '受水槽清掃実施日',
    'water_filter_bath_system' => '浴槽循環方式',
    'water_filter_availability' => 'ろ過器設置の有無',
    'water_filter_manufacturer' => 'ろ過器メーカー',
    'water_filter_model_year' => 'ろ過器年式',
    'water_tank_availability' => '受水槽設置の有無',
    'water_tank_manufacturer' => '受水槽メーカー',
    'water_tank_model_year' => '受水槽年式',
    'water_pump_manufacturers' => '加圧ポンプメーカー',
    'water_pump_model_years' => '加圧ポンプ年式',
    'water_septic_tank_availability' => '浄化槽設置の有無',
    'water_septic_tank_manufacturer' => '浄化槽メーカー',
    'water_septic_tank_model_year' => '浄化槽年式',
    'water_septic_tank_inspection_company' => '浄化槽点検・清掃業者',
    'water_septic_tank_inspection_date' => '浄化槽点検・清掃実施日',
    'water_legionella_inspection_dates' => 'レジオネラ検査実施日',
    'water_legionella_first_results' => 'レジオネラ検査結果（初回）',
    'water_legionella_second_results' => 'レジオネラ検査結果（2回目）',
    'water_notes' => '水道設備備考',
],

'lifeline_gas' => [
    'gas_contractor' => 'ガス会社',
    'gas_safety_management_company' => 'ガス保安管理業者',
    'gas_maintenance_inspection_date' => 'ガス保守点検実施日',
    'gas_notes' => 'ガス設備備考',
],

'lifeline_elevator' => [
    'elevator_availability' => 'エレベーター有無',
    'elevator_manufacturer' => 'エレベーターメーカー',
    'elevator_model_year' => 'エレベーター年式',
    'elevator_maintenance_company' => 'エレベーター保守会社',
    'elevator_maintenance_date' => 'エレベーター保守実施日',
    'elevator_notes' => 'エレベーター設備備考',
],

'lifeline_hvac' => [
    'hvac_lighting_availability' => '空調・照明設備有無',
    'hvac_lighting_manufacturer' => '空調・照明設備メーカー',
    'hvac_lighting_model_year' => '空調・照明設備年式',
    'hvac_lighting_maintenance_company' => '空調・照明保守会社',
    'hvac_lighting_maintenance_date' => '空調・照明保守実施日',
    'hvac_lighting_notes' => '空調・照明設備備考',
],
```

### 2. FieldValueExtractorの未実装

`app/Services/Export/FieldValueExtractor.php`で以下のメソッドがプレースホルダーのまま:
- `getElectricalFieldValue()` - 空の実装
- `getWaterFieldValue()` - 空の実装
- `getGasFieldValue()` - 空の実装
- `getElevatorFieldValue()` - 空の実装
- `getHvacFieldValue()` - 空の実装
- `getBuildingFieldValue()` - 空の実装

### 3. データベース構造の確認が必要

ライフライン設備のデータがどのように保存されているか確認が必要:
- `lifeline_equipments` テーブル
- JSON形式での保存
- カテゴリ別のデータ構造

## 修正方針

### オプション1: Configファイルを正として、ビューを修正
- **メリット**: Configが実際のデータベース構造に基づいている可能性が高い
- **デメリット**: ビューファイルの大幅な修正が必要

### オプション2: ビューを正として、Configとデータ抽出ロジックを修正
- **メリット**: ユーザーに表示されている項目を維持できる
- **デメリット**: データベース構造との整合性確認が必要

### 推奨: オプション1（Configを正とする）

理由:
1. `config/csv-export-fields.php`のフィールド名は実際のデータベース構造に基づいている可能性が高い
2. ライフライン設備の詳細画面で使用されているフィールド名と一致する可能性が高い
3. より詳細な項目が定義されている（電気設備: 12項目 vs 5項目）

## 修正手順

### ステップ1: データベース構造の確認
```bash
php artisan tinker
# LifelineEquipmentモデルの構造確認
```

### ステップ2: ビューファイルの修正
`resources/views/export/csv/index.blade.php`のライフライン設備フィールド定義を`config/csv-export-fields.php`に合わせる

### ステップ3: FieldValueExtractorの実装
各ライフライン設備カテゴリのデータ抽出ロジックを実装

### ステップ4: 建物情報の実装
`getBuildingFieldValue()`メソッドの実装

### ステップ5: テストと検証
- 各カテゴリのフィールドが正しく出力されるか確認
- データが存在する施設でテスト
- データが存在しない施設でテスト

## 次のアクション

1. LifelineEquipmentモデルとデータベース構造の確認
2. 実際の施設データでどのフィールドが使用されているか確認
3. 上記確認後、修正を実施
