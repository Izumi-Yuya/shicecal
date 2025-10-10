# CSVエクスポート：空調・照明設備の分割

## 実施日時
2025-10-10

## 変更内容
空調・照明設備のCSVエクスポート項目を、空調設備と照明設備に分けて、より詳細な項目に変更しました。

## 変更前の項目

### 空調・照明設備（統合）
- 空調・照明設備有無
- 空調・照明設備メーカー
- 空調・照明設備年式
- 空調・照明保守会社
- 空調・照明保守実施日
- 空調・照明設備備考

## 変更後の項目

### 空調設備
- **フロンガス点検業者** - フロンガス点検を実施する業者名
- **点検実施日** - 点検を実施した日付
- **点検対象機器** - 点検対象となる機器の情報
- **空調設備備考** - 空調設備に関する備考

### 照明設備
- **メーカー** - 照明設備のメーカー名
- **更新日** - 照明設備を更新した日付
- **保証期間** - 照明設備の保証期間
- **照明設備備考** - 照明設備に関する備考

## 変更理由

### 1. 設備の性質の違い
- **空調設備**: フロンガス規制により定期的な点検が法的に義務付けられている
- **照明設備**: LED化などの更新が進んでおり、更新日や保証期間の管理が重要

### 2. 管理項目の明確化
- 空調設備と照明設備では管理すべき情報が異なる
- それぞれの設備に特化した項目を設定することで、より適切な管理が可能

### 3. 法令対応
- フロンガス点検は法的義務があり、点検業者や実施日の記録が必要
- 照明設備は省エネ対応やLED化の進捗管理が重要

## 修正したファイル

### 1. app/Services/ExportService.php
`getAvailableFields()` メソッドの項目定義を変更：

#### 変更前
```php
// ライフライン設備 - 空調・照明
'hvac_lighting_availability' => '空調・照明設備有無',
'hvac_lighting_manufacturer' => '空調・照明設備メーカー',
'hvac_lighting_model_year' => '空調・照明設備年式',
'hvac_lighting_maintenance_company' => '空調・照明保守会社',
'hvac_lighting_maintenance_date' => '空調・照明保守実施日',
'hvac_lighting_notes' => '空調・照明設備備考',
```

#### 変更後
```php
// ライフライン設備 - 空調設備
'hvac_freon_inspection_company' => '空調設備_フロンガス点検業者',
'hvac_freon_inspection_date' => '空調設備_点検実施日',
'hvac_inspection_equipment' => '空調設備_点検対象機器',
'hvac_notes' => '空調設備_備考',

// ライフライン設備 - 照明設備
'lighting_manufacturer' => '照明設備_メーカー',
'lighting_update_date' => '照明設備_更新日',
'lighting_warranty_period' => '照明設備_保証期間',
'lighting_notes' => '照明設備_備考',
```

### 2. app/Services/Export/FieldValueExtractor.php
`getHvacFieldValue()` メソッドを更新：

- 空調設備と照明設備のデータを別々に取得
- `basic_info['hvac']` から空調設備データを取得
- `basic_info['lighting']` から照明設備データを取得
- 後方互換性のため、古い項目名も残す

```php
private function getHvacFieldValue(Facility $facility, string $field): string
{
    $hvac = $facility->getHvacLightingEquipment();
    if (!$hvac) {
        return '';
    }

    $basicInfo = $hvac->basic_info ?? [];
    $hvacData = $basicInfo['hvac'] ?? [];
    $lightingData = $basicInfo['lighting'] ?? [];

    return match ($field) {
        // 空調設備
        'hvac_freon_inspection_company' => $hvacData['freon_inspection_company'] ?? '',
        'hvac_freon_inspection_date' => isset($hvacData['freon_inspection_date']) ? $hvacData['freon_inspection_date'] : '',
        'hvac_inspection_equipment' => $hvacData['inspection_equipment'] ?? '',
        'hvac_notes' => $hvacData['notes'] ?? '',
        
        // 照明設備
        'lighting_manufacturer' => $lightingData['manufacturer'] ?? '',
        'lighting_update_date' => isset($lightingData['update_date']) ? $lightingData['update_date'] : '',
        'lighting_warranty_period' => $lightingData['warranty_period'] ?? '',
        'lighting_notes' => $lightingData['notes'] ?? '',
        
        // 後方互換性のため古い項目も残す
        'hvac_lighting_availability' => $basicInfo['availability'] ?? '',
        // ...
        default => '',
    };
}
```

### 3. config/csv-export-fields.php
カテゴリを分割：

#### 変更前
```php
'lifeline_hvac' => [
    'title' => '空調・照明設備',
    'icon' => 'fas fa-snowflake',
    'color' => 'text-success',
    'fields' => [
        'hvac_lighting_availability' => '空調・照明設備有無',
        // ...
    ]
],
```

#### 変更後
```php
'lifeline_hvac' => [
    'title' => '空調設備',
    'icon' => 'fas fa-snowflake',
    'color' => 'text-info',
    'fields' => [
        'hvac_freon_inspection_company' => 'フロンガス点検業者',
        'hvac_freon_inspection_date' => '点検実施日',
        'hvac_inspection_equipment' => '点検対象機器',
        'hvac_notes' => '空調設備備考',
    ]
],
'lifeline_lighting' => [
    'title' => '照明設備',
    'icon' => 'fas fa-lightbulb',
    'color' => 'text-warning',
    'fields' => [
        'lighting_manufacturer' => 'メーカー',
        'lighting_update_date' => '更新日',
        'lighting_warranty_period' => '保証期間',
        'lighting_notes' => '照明設備備考',
    ]
],
```

### 4. resources/views/export/csv/index.blade.php
表示を2つのセクションに分割：

- 空調設備セクション（青色アイコン）
- 照明設備セクション（黄色アイコン）

## データベース構造の想定

### lifeline_equipments テーブル
`basic_info` JSON カラムの構造：

```json
{
  "hvac": {
    "freon_inspection_company": "○○空調サービス",
    "freon_inspection_date": "2024-06-15",
    "inspection_equipment": "エアコン10台、冷凍機2台",
    "notes": "次回点検は2025年6月予定"
  },
  "lighting": {
    "manufacturer": "パナソニック",
    "update_date": "2023-04-01",
    "warranty_period": "5年",
    "notes": "全館LED化完了"
  }
}
```

## 影響範囲

### CSVエクスポート
- CSV出力時の列が変更される
- 空調設備と照明設備が別々の列として出力される

### エクスポート画面
- 項目選択画面で空調設備と照明設備が別々のセクションとして表示される
- それぞれ独立して選択可能

### 既存データ
- 既存の `hvac_lighting_*` 項目は後方互換性のため残されている
- 新しい項目にデータを移行する必要がある

## 後方互換性

### 古い項目名のサポート
`FieldValueExtractor` では、古い項目名（`hvac_lighting_*`）も引き続きサポートしています：

- `hvac_lighting_availability`
- `hvac_lighting_manufacturer`
- `hvac_lighting_model_year`
- `hvac_lighting_maintenance_company`
- `hvac_lighting_maintenance_date`
- `hvac_lighting_notes`

これにより、既存のCSVエクスポート設定やスクリプトが引き続き動作します。

## データ移行の推奨

### 1. 既存データの確認
```sql
SELECT id, basic_info 
FROM lifeline_equipments 
WHERE category = 'hvac_lighting';
```

### 2. データ構造の更新
既存の `basic_info` を新しい構造に変換：

```php
// 既存データ
$oldData = [
    'availability' => 'あり',
    'manufacturer' => 'パナソニック',
    'model_year' => '2020',
    'maintenance_company' => '○○メンテナンス',
    'maintenance_date' => '2024-06-15',
];

// 新しい構造
$newData = [
    'hvac' => [
        'freon_inspection_company' => $oldData['maintenance_company'],
        'freon_inspection_date' => $oldData['maintenance_date'],
        'inspection_equipment' => '',
        'notes' => '',
    ],
    'lighting' => [
        'manufacturer' => $oldData['manufacturer'],
        'update_date' => '',
        'warranty_period' => '',
        'notes' => '',
    ],
];
```

## テスト方法

### 1. エクスポート画面の確認
```
http://localhost:8000/export/csv
```

1. ライフライン設備カテゴリを展開
2. 「空調設備」セクションが表示されることを確認
3. 「照明設備」セクションが表示されることを確認
4. それぞれの項目が正しく表示されることを確認

### 2. CSVエクスポートの実行
1. 施設を選択
2. 空調設備と照明設備の項目を選択
3. CSV出力を実行
4. ダウンロードしたCSVファイルを確認

### 3. 期待される結果
CSVヘッダー：
```csv
...,空調設備_フロンガス点検業者,空調設備_点検実施日,空調設備_点検対象機器,空調設備_備考,照明設備_メーカー,照明設備_更新日,照明設備_保証期間,照明設備_備考,...
```

## 今後の改善案

### 1. 入力フォームの更新
- 施設編集画面で空調設備と照明設備を別々のセクションに分ける
- 新しい項目に対応した入力フィールドを追加

### 2. バリデーションの追加
- フロンガス点検日の必須チェック（法的義務）
- 日付形式のバリデーション

### 3. 通知機能
- フロンガス点検期限の通知
- 照明設備保証期間終了の通知

## 関連ドキュメント
- `docs/csv-export-field-order-fix.md` - 項目順序の修正
- `docs/csv-export-field-fix-implementation.md` - 項目差異修正
- `config/csv-export-fields.php` - フィールド定義
- `app/Services/ExportService.php` - エクスポートサービス
- `app/Services/Export/FieldValueExtractor.php` - 値抽出サービス
