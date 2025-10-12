# CSVエクスポート項目不一致の修正サマリー

## 修正日
2025年10月10日

## 問題の概要
CSVエクスポート画面で表示されている項目と、実際に出力される項目に差異があった。

## 根本原因
1. **ビューファイル**と**configファイル**でフィールド名が不一致
2. **FieldValueExtractor.php**でライフライン設備、建物情報、防犯・防災設備、契約書のデータ抽出ロジックが未実装（プレースホルダーのまま）
3. **config/csv-export-fields.php**に防犯・防災設備と契約書のフィールド定義が欠落

## 実施した修正

### 1. ビューファイルの修正 (resources/views/export/csv/index.blade.php)

#### ライフライン設備フィールド名の統一
**修正前（誤ったフィールド名）:**
```php
$electricFields = [
    'power_company' => '電力会社',
    'power_capacity' => '受電容量',
    // ...
];
```

**修正後（正しいフィールド名）:**
```php
$electricFields = [
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
];
```

同様に、水道設備、ガス設備、エレベーター設備、空調・照明設備のフィールド名も修正。

### 2. Configファイルの拡張 (config/csv-export-fields.php)

#### 追加したカテゴリ:
- **防犯カメラ** (`security_camera`)
- **電子錠** (`security_lock`)
- **消防** (`fire`)
- **防災** (`disaster`)
- **その他契約書** (`contract_others`)
- **給食契約書** (`contract_meal`)
- **駐車場契約書** (`contract_parking`)

### 3. FieldValueExtractorの実装 (app/Services/Export/FieldValueExtractor.php)

#### 実装したメソッド:

**建物情報:**
```php
private function getBuildingFieldValue(Facility $facility, string $field): string
{
    $buildingInfo = $this->getCachedRelationship($facility, 'buildingInfo');
    // 27フィールドの抽出ロジック実装
}
```

**電気設備:**
```php
private function getElectricalFieldValue(Facility $facility, string $field): string
{
    $electrical = $facility->getElectricalEquipment();
    // basic_info, pas_info, cubicle_info, generator_infoからデータ抽出
}
```

**水道設備:**
```php
private function getWaterFieldValue(Facility $facility, string $field): string
{
    $water = $facility->getWaterEquipment();
    // 21フィールドの抽出ロジック実装
}
```

**ガス設備:**
```php
private function getGasFieldValue(Facility $facility, string $field): string
{
    $gas = $facility->getGasEquipment();
    // 4フィールドの抽出ロジック実装
}
```

**エレベーター設備:**
```php
private function getElevatorFieldValue(Facility $facility, string $field): string
{
    $elevator = $facility->getElevatorEquipment();
    // 6フィールドの抽出ロジック実装
}
```

**空調・照明設備:**
```php
private function getHvacFieldValue(Facility $facility, string $field): string
{
    $hvac = $facility->getHvacLightingEquipment();
    // 6フィールドの抽出ロジック実装
}
```

**防犯・防災設備:**
```php
private function getSecurityDisasterFieldValue(Facility $facility, string $field): string
{
    $securityDisaster = $facility->getSecurityDisasterEquipment();
    // 防犯カメラ、電子錠、消防、防災の各サブカテゴリ処理
}
```

**契約書:**
```php
private function getContractFieldValue(Facility $facility, string $field): string
{
    $contract = $this->getCachedRelationship($facility, 'contracts');
    // その他契約書、給食契約書、駐車場契約書の各サブカテゴリ処理
}
```

#### ヘルパーメソッドの追加:
- `formatServiceTypes()` - サービス種類の配列を文字列に変換
- `formatArrayField()` - 配列フィールド（メーカー、年式など）を文字列に変換
- `formatLegionellaInspectionDates()` - レジオネラ検査日の複数日付を結合
- `getOthersContractFieldValue()` - その他契約書フィールド抽出
- `getMealContractFieldValue()` - 給食契約書フィールド抽出
- `getParkingContractFieldValue()` - 駐車場契約書フィールド抽出
- `getSecurityCameraFieldValue()` - 防犯カメラフィールド抽出
- `getSecurityLockFieldValue()` - 電子錠フィールド抽出
- `getFireFieldValue()` - 消防フィールド抽出
- `getDisasterFieldValue()` - 防災フィールド抽出

### 4. CsvExportServiceの更新 (app/Services/Export/CsvExportService.php)

#### determineRequiredRelationshipsメソッドの拡張:
```php
$fieldPrefixes = [
    'land_' => 'landInfo',
    'building_' => 'buildingInfo', 
    'maintenance_' => 'maintenanceHistories',
    'drawing_' => 'drawing',
    'electrical_' => 'lifelineEquipments',
    'water_' => 'lifelineEquipments',
    'gas_' => 'lifelineEquipments',
    'elevator_' => 'lifelineEquipments',
    'hvac_' => 'lifelineEquipments',
    'security_' => 'lifelineEquipments',  // 追加
    'fire_' => 'lifelineEquipments',      // 追加
    'disaster_' => 'lifelineEquipments',  // 追加
    'contract_' => 'contracts',
];
```

## データ構造の確認結果

### ライフライン設備のデータ構造
- **親テーブル**: `lifeline_equipment` (カテゴリ管理)
- **子テーブル**: 
  - `electrical_equipment` (電気設備)
  - `water_equipment` (水道設備)
  - `gas_equipment` (ガス設備)
  - `elevator_equipment` (エレベーター設備)
  - `hvac_lighting_equipment` (空調・照明設備)
  - `security_disaster_equipment` (防犯・防災設備)

### データ保存形式
- **基本情報**: `basic_info` (JSON配列)
- **詳細情報**: カテゴリ別の専用JSON配列
  - 電気: `pas_info`, `cubicle_info`, `generator_info`
  - 防犯・防災: `security_systems`, `disaster_prevention`

### 契約書のデータ構造
- **テーブル**: `facility_contracts`
- **その他契約書**: 個別カラム (`others_company_name`, `others_contract_type`, etc.)
- **給食契約書**: `meal_service_data` (JSON配列)
- **駐車場契約書**: `parking_data` (JSON配列)

## 修正されたファイル一覧

1. `resources/views/export/csv/index.blade.php` - ライフライン設備フィールド名の修正
2. `config/csv-export-fields.php` - 防犯・防災設備と契約書カテゴリの追加
3. `app/Services/Export/FieldValueExtractor.php` - データ抽出ロジックの完全実装
4. `app/Services/Export/CsvExportService.php` - リレーションシップマッピングの拡張

## テスト推奨項目

### 1. 基本情報
- [ ] 会社名、施設名、事業所コードが正しく出力される
- [ ] サービス種類が配列から文字列に正しく変換される
- [ ] 日付フィールドが正しいフォーマットで出力される

### 2. 土地情報
- [ ] 土地所有区分が正しく変換される（owned→自社所有、leased→賃貸）
- [ ] 契約期間が正しくフォーマットされる
- [ ] 管理会社とオーナー情報が正しく出力される

### 3. 建物情報
- [ ] 建物所有区分が正しく変換される
- [ ] 築年数が自動計算される
- [ ] 契約日付が正しく出力される

### 4. ライフライン設備
- [ ] 電気設備: PAS、キュービクル、発電機の情報が正しく出力される
- [ ] 水道設備: 受水槽、ろ過器、浄化槽、レジオネラ検査の情報が正しく出力される
- [ ] ガス設備: ガス会社、保安管理業者の情報が正しく出力される
- [ ] エレベーター設備: メーカー、年式、保守会社の情報が正しく出力される
- [ ] 空調・照明設備: メーカー、年式、保守会社の情報が正しく出力される

### 5. 防犯・防災設備
- [ ] 防犯カメラ: 管理業者、年式、備考が正しく出力される
- [ ] 電子錠: 管理業者、年式、備考が正しく出力される
- [ ] 消防: 防火管理者、訓練日、点検業者が正しく出力される
- [ ] 防災: 訓練日、備考が正しく出力される

### 6. 契約書
- [ ] その他契約書: 会社名、契約種類、契約内容、金額が正しく出力される
- [ ] 給食契約書: JSON配列から正しくデータが抽出される
- [ ] 駐車場契約書: JSON配列から正しくデータが抽出される

### 7. 図面
- [ ] 引き渡し図面備考が正しく出力される
- [ ] 図面備考が正しく出力される

### 8. エッジケース
- [ ] データが存在しない施設で空文字列が出力される
- [ ] NULL値が適切に処理される
- [ ] 配列フィールドが正しくカンマ区切りで結合される
- [ ] 日付フィールドがNULLの場合に空文字列が出力される

## 既知の制限事項

1. **service_validity_periods**: このフィールドは現在データベースに存在しないため、常に空文字列を返す
2. **配列フィールド**: メーカーや年式などの配列フィールドは、カンマ区切りで結合される
3. **JSON配列**: 給食契約書と駐車場契約書はJSON配列として保存されているため、データ構造が変更された場合は抽出ロジックの更新が必要

## 今後の改善提案

1. **パフォーマンス最適化**: 大量の施設データをエクスポートする際のメモリ使用量とクエリ最適化
2. **エラーハンドリング**: データ抽出失敗時のより詳細なログ出力
3. **バリデーション**: エクスポート前のデータ整合性チェック
4. **プログレス表示**: 大量データエクスポート時の進捗状況の可視化
5. **カスタムフォーマット**: 日付や数値のフォーマットをユーザーが選択できる機能

## 参考資料

- [CSVエクスポート項目不一致の分析](./csv-export-field-mismatch-analysis.md)
- [ライフライン設備データ構造](../config/lifeline-equipment.php)
- [契約書管理システム実装ガイドライン](./.kiro/steering/contracts-management.md)
