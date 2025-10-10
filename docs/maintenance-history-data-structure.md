# 修繕履歴データ構造の説明

## データベーステーブル: maintenance_histories

### テーブル構造

| カラム名 | 型 | 説明 |
|---------|-----|------|
| id | bigint | 主キー |
| facility_id | bigint | 施設ID（外部キー） |
| maintenance_date | date | 施工日 |
| content | text | 修繕内容 |
| cost | decimal(10,2) | 金額 |
| contractor | varchar(255) | 施工会社 |
| category | varchar(50) | カテゴリ |
| subcategory | varchar(50) | サブカテゴリ |
| contact_person | varchar(255) | 担当者 |
| phone_number | varchar(20) | 連絡先 |
| notes | text | 備考 |
| warranty_period_years | integer | 保証期間（年） |
| warranty_start_date | date | 保証開始日 |
| warranty_end_date | date | 保証終了日 |
| created_by | bigint | 作成者ID |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

## カテゴリとサブカテゴリの構造

### 1. 外装 (exterior)
- **防水 (waterproof)**: 屋上防水、ベランダ防水、外壁防水など
- **塗装 (painting)**: 外壁塗装、屋根塗装、鉄部塗装など

### 2. 内装リニューアル (interior)
- **内装リニューアル (renovation)**: 内装リニューアル工事、フロア改修など
- **内装・意匠 (design)**: 内装デザイン変更、照明設備更新、床材変更など

### 3. その他 (other)
- **改修工事 (renovation_work)**: エアコン修理、照明器具交換、水道管修理など

## 本社施設のデータ例

### 例1: 外装 - 防水工事
```php
[
    'facility_id' => 1, // 本社
    'maintenance_date' => '2024-06-15',
    'content' => '屋上防水工事',
    'cost' => 850000.00,
    'contractor' => '株式会社山田工務店',
    'category' => 'exterior',
    'subcategory' => 'waterproof',
    'contact_person' => '山田太郎',
    'phone_number' => '03-1234-5678',
    'notes' => '屋上全面の防水シート張替え工事を実施。既存シートの劣化が著しかったため、全面改修を実施。',
    'warranty_period_years' => 10,
    'warranty_start_date' => '2024-06-15',
    'warranty_end_date' => '2034-06-15',
    'created_by' => 2,
]
```

### 例2: 外装 - 塗装工事
```php
[
    'facility_id' => 1, // 本社
    'maintenance_date' => '2023-09-20',
    'content' => '外壁塗装工事',
    'cost' => 1200000.00,
    'contractor' => '高橋塗装',
    'category' => 'exterior',
    'subcategory' => 'painting',
    'contact_person' => '高橋健一',
    'phone_number' => '03-2345-6789',
    'notes' => '外壁全面の塗装工事。下地処理から仕上げまで3週間かけて実施。',
    'warranty_period_years' => null,
    'warranty_start_date' => null,
    'warranty_end_date' => null,
    'created_by' => 2,
]
```

### 例3: 内装リニューアル - リニューアル工事
```php
[
    'facility_id' => 1, // 本社
    'maintenance_date' => '2024-03-10',
    'content' => '1階ロビー内装リニューアル工事',
    'cost' => 2500000.00,
    'contractor' => '渡辺リフォーム',
    'category' => 'interior',
    'subcategory' => 'renovation',
    'contact_person' => '渡辺由美',
    'phone_number' => '03-3456-7890',
    'notes' => '1階ロビーの全面リニューアル。床材、壁材、天井材をすべて新しくし、照明も LED に変更。',
    'warranty_period_years' => null,
    'warranty_start_date' => null,
    'warranty_end_date' => null,
    'created_by' => 2,
]
```

### 例4: 内装リニューアル - 内装・意匠
```php
[
    'facility_id' => 1, // 本社
    'maintenance_date' => '2024-01-25',
    'content' => '2階会議室壁紙張替え工事',
    'cost' => 350000.00,
    'contractor' => '中村建築',
    'category' => 'interior',
    'subcategory' => 'design',
    'contact_person' => '中村恵子',
    'phone_number' => '03-4567-8901',
    'notes' => '2階会議室の壁紙を防音性の高いものに変更。クロス品番: ABC-123（防火認定品）',
    'warranty_period_years' => null,
    'warranty_start_date' => null,
    'warranty_end_date' => null,
    'created_by' => 2,
]
```

### 例5: その他 - 改修工事
```php
[
    'facility_id' => 1, // 本社
    'maintenance_date' => '2024-08-05',
    'content' => 'エアコン修理',
    'cost' => 85000.00,
    'contractor' => '鈴木設備',
    'category' => 'other',
    'subcategory' => 'renovation_work',
    'contact_person' => '鈴木美咲',
    'phone_number' => '03-5678-9012',
    'notes' => '3階事務所のエアコン冷媒ガス漏れ修理。コンプレッサー交換も実施。',
    'warranty_period_years' => null,
    'warranty_start_date' => null,
    'warranty_end_date' => null,
    'created_by' => 2,
]
```

## CSVエクスポートでの項目マッピング

### 外装 - 防水
- **施工日**: `maintenance_date`
- **施工会社**: `contractor`
- **担当者**: `contact_person`
- **連絡先**: `phone_number`
- **備考**: `notes`
- **特記事項**: （現在は使用されていない、将来的に追加予定）

### 外装 - 塗装
- **施工日**: `maintenance_date`
- **施工会社**: `contractor`
- **担当者**: `contact_person`
- **連絡先**: `phone_number`
- **備考**: `notes`
- **特記事項**: （現在は使用されていない、将来的に追加予定）

### 内装リニューアル
- **リニューアル日**: `maintenance_date`
- **会社名**: `contractor`
- **担当者**: `contact_person`
- **連絡先**: `phone_number`

### 内装・衣装履歴（内装・意匠）
- **NO**: （現在は使用されていない、将来的に追加予定）
- **施工日**: `maintenance_date`
- **施工会社**: `contractor`
- **金額**: `cost`
- **修繕内容**: `content`
- **備考**: `notes`
- **特記事項**: （現在は使用されていない、将来的に追加予定）

### その他 - 改修工事履歴
- **No**: （現在は使用されていない、将来的に追加予定）
- **施工日**: `maintenance_date`
- **施工会社**: `contractor`
- **金額**: `cost`
- **修繕内容**: `content`
- **備考**: `notes`
- **特記事項**: （現在は使用されていない、将来的に追加予定）

## データ取得ロジック

CSVエクスポート時は、各カテゴリー・サブカテゴリーの**最新の履歴**を取得します：

```php
// 外装 - 防水の最新履歴
$latest = $facility->maintenanceHistories()
    ->where('category', 'exterior')
    ->where('subcategory', 'waterproof')
    ->orderBy('maintenance_date', 'desc')
    ->first();

// 内装リニューアルの最新履歴
$latest = $facility->maintenanceHistories()
    ->where('category', 'interior')
    ->where('subcategory', 'renovation')
    ->orderBy('maintenance_date', 'desc')
    ->first();

// 内装・意匠の最新履歴
$latest = $facility->maintenanceHistories()
    ->where('category', 'interior')
    ->where('subcategory', 'design')
    ->orderBy('maintenance_date', 'desc')
    ->first();
```

## 注意事項

### 1. 「内装・衣装履歴」について
- データベースでは `category='interior'`, `subcategory='design'` として保存
- 「衣装」は「意匠」の誤字の可能性があります
- 内装デザインや装飾に関する履歴を管理

### 2. NO/No フィールドについて
- 現在のデータベース構造には管理番号フィールドがありません
- 将来的に `record_number` カラムを追加する必要があります
- または、`id` を管理番号として使用することも検討できます

### 3. 特記事項フィールドについて
- 現在は `notes` フィールドのみ使用
- 将来的に `special_notes` カラムを追加して、通常の備考と特記事項を分けることを推奨

### 4. 保証期間について
- 防水工事には保証期間情報が記録されます
- `warranty_period_years`, `warranty_start_date`, `warranty_end_date` カラムを使用
- 他の工事種別でも必要に応じて保証期間を記録可能

## データ移行の推奨

現在のCSVエクスポート項目に完全対応するには、以下のカラム追加を推奨：

```sql
ALTER TABLE maintenance_histories 
ADD COLUMN record_number VARCHAR(50) AFTER id,
ADD COLUMN special_notes TEXT AFTER notes;
```

これにより、NO/No フィールドと特記事項フィールドを適切に管理できます。


---

## 実装完了サマリー（2025年10月10日）

### 配列形式出力の実装

#### 実装内容
1. **FieldValueExtractor.php**
   - `formatMaintenanceHistoriesArray()` メソッドを実装
   - 複数のメンテナンス履歴レコードを配列形式の文字列に変換
   - フォーマット: `[NO:xxx|日付:xxx|会社:xxx|金額:xxx|内容:xxx|担当:xxx|連絡先:xxx|備考:xxx]`
   - 複数レコードは ` / ` で区切り

2. **CSV設定ファイル (config/csv-export-fields.php)**
   - `maintenance_interior_history_all`: 内装・意匠履歴の全データ（配列形式）
   - `maintenance_other_renovation_all`: その他改修工事履歴の全データ（配列形式）

3. **ExportService.php**
   - 配列形式フィールドを利用可能フィールドリストに追加
   - `maintenance_interior_history_all`: 内装・意匠履歴_全データ
   - `maintenance_other_renovation_all`: その他_改修工事履歴_全データ

4. **CSVエクスポート画面 (resources/views/export/csv/index.blade.php)**
   - 内装・意匠履歴セクション: `maintenance_interior_history_all` のみ表示
   - その他改修工事履歴セクション: `maintenance_other_renovation_all` のみ表示
   - 個別フィールドは削除し、配列形式の項目のみに統一

### 出力例

#### 内装・意匠履歴の配列形式出力
```
[NO:15|日付:2024-03-10|会社:株式会社インテリアデザイン|金額:1,200,000|内容:エントランスホール改装|担当:佐藤太郎|連絡先:03-1234-5678|備考:照明とフロア材を一新] / [NO:12|日付:2023-11-20|会社:有限会社リフォーム工房|金額:850,000|内容:共用部壁紙張替え|担当:鈴木花子|連絡先:03-9876-5432|備考:全フロア対応]
```

#### その他改修工事履歴の配列形式出力
```
[NO:18|日付:2024-05-15|会社:株式会社設備メンテナンス|金額:450,000|内容:エアコン3台交換|担当:田中一郎|連絡先:03-5555-6666|備考:省エネ型に更新] / [NO:16|日付:2024-02-28|会社:電気工事株式会社|金額:280,000|内容:照明器具LED化|担当:山本次郎|連絡先:03-7777-8888|備考:共用部のみ]
```

### データ取得ロジック

```php
// 内装・意匠履歴の取得
$histories = $facility->maintenanceHistories()
    ->where('category', 'interior')
    ->where('subcategory', 'design')
    ->orderBy('maintenance_date', 'desc')
    ->get();

// その他改修工事履歴の取得
$histories = $facility->maintenanceHistories()
    ->where('category', 'other')
    ->where('subcategory', 'renovation_work')
    ->orderBy('maintenance_date', 'desc')
    ->get();
```

### 利点
1. **データの完全性**: すべてのレコードが1つのセルに含まれる
2. **可読性**: 構造化された形式で各フィールドが明確
3. **拡張性**: 新しいフィールドを追加しやすい
4. **一貫性**: 他のカテゴリと統一された出力形式

### 今後の拡張可能性
- 他のカテゴリ（外装防水、外装塗装など）も配列形式に対応可能
- フォーマットのカスタマイズ（JSON、XML等）
- フィルタリング機能（日付範囲、金額範囲など）
