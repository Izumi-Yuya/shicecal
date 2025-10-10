# CSVエクスポート：修繕履歴の再構築

## 実施日時
2025-10-10

## 変更内容
修繕履歴のCSVエクスポート項目を、外装・内装リニューアル・その他のメインカテゴリーに分けて、それぞれのサブカテゴリーに適した詳細な項目に変更しました。

## 変更前の項目

### 修繕履歴（統合）
- 修繕履歴_最新修繕日
- 修繕履歴_最新修繕内容
- 修繕履歴_最新修繕費用
- 修繕履歴_最新施工業者
- 修繕履歴_最新カテゴリ
- 修繕履歴_最新サブカテゴリ
- 修繕履歴_最新担当者
- 修繕履歴_最新電話番号
- 修繕履歴_最新備考
- 修繕履歴_最新保証期間
- 修繕履歴_総件数
- 修繕履歴_総費用

## 変更後の項目

### メインカテゴリー1: 外装

#### サブカテゴリー: 防水
- **施工日** - 防水工事を実施した日付
- **施工会社** - 防水工事を実施した会社名
- **担当者** - 施工会社の担当者名
- **連絡先** - 担当者の連絡先（電話番号）
- **備考** - 防水工事に関する備考
- **特記事項** - 特に記録すべき事項

#### サブカテゴリー: 塗装
- **施工日** - 塗装工事を実施した日付
- **施工会社** - 塗装工事を実施した会社名
- **担当者** - 施工会社の担当者名
- **連絡先** - 担当者の連絡先（電話番号）
- **備考** - 塗装工事に関する備考
- **特記事項** - 特に記録すべき事項

### メインカテゴリー2: 内装リニューアル

#### サブカテゴリー: 内装リニューアル
- **リニューアル日** - リニューアルを実施した日付
- **会社名** - リニューアルを実施した会社名
- **担当者** - 施工会社の担当者名
- **連絡先** - 担当者の連絡先（電話番号）

#### サブカテゴリー: 内装・衣装履歴
- **NO** - 履歴の管理番号
- **施工日** - 施工を実施した日付
- **施工会社** - 施工を実施した会社名
- **金額** - 施工にかかった金額
- **修繕内容** - 修繕の詳細内容
- **備考** - 施工に関する備考
- **特記事項** - 特に記録すべき事項

### メインカテゴリー3: その他

#### サブカテゴリー: 改修工事履歴
- **No** - 履歴の管理番号
- **施工日** - 施工を実施した日付
- **施工会社** - 施工を実施した会社名
- **金額** - 施工にかかった金額
- **修繕内容** - 修繕の詳細内容
- **備考** - 施工に関する備考
- **特記事項** - 特に記録すべき事項

## 変更理由

### 1. 工事種別による管理の明確化
- **外装工事**: 防水と塗装は建物の外観と耐久性に直結する重要な工事
- **内装工事**: リニューアルと日常的な修繕を分けて管理
- **その他**: 大規模な改修工事を別途管理

### 2. 必要情報の最適化
- 各工事種別に応じた必要な情報を項目化
- 連絡先情報を明確化（担当者と連絡先を分離）
- 特記事項欄を追加して重要な情報を記録

### 3. 履歴管理の強化
- NO/No フィールドで履歴を管理
- 金額情報を明示的に記録
- 修繕内容を詳細に記録

## 修正したファイル

### 1. app/Services/ExportService.php
`getAvailableFields()` メソッドの項目定義を変更：

#### 変更前
```php
// 修繕履歴
'maintenance_latest_date' => '修繕履歴_最新修繕日',
'maintenance_latest_content' => '修繕履歴_最新修繕内容',
// ... 12項目
```

#### 変更後
```php
// 修繕履歴 - 外装
// 外装 - 防水
'maintenance_exterior_waterproof_date' => '外装_防水_施工日',
'maintenance_exterior_waterproof_company' => '外装_防水_施工会社',
'maintenance_exterior_waterproof_contact_person' => '外装_防水_担当者',
'maintenance_exterior_waterproof_contact' => '外装_防水_連絡先',
'maintenance_exterior_waterproof_notes' => '外装_防水_備考',
'maintenance_exterior_waterproof_special_notes' => '外装_防水_特記事項',

// 外装 - 塗装
'maintenance_exterior_painting_date' => '外装_塗装_施工日',
// ... 同様の項目

// 内装リニューアル
'maintenance_interior_renewal_date' => '内装リニューアル_リニューアル日',
// ... 4項目

// 内装・衣装履歴
'maintenance_interior_history_no' => '内装・衣装履歴_NO',
// ... 7項目

// その他 - 改修工事履歴
'maintenance_other_renovation_no' => 'その他_改修工事履歴_No',
// ... 7項目
```

### 2. app/Services/Export/FieldValueExtractor.php
`getMaintenanceFieldValue()` メソッドを実装：

```php
private function getMaintenanceFieldValue(Facility $facility, string $field): string
{
    // 外装 - 防水
    if (str_starts_with($field, 'maintenance_exterior_waterproof_')) {
        return $this->getMaintenanceExteriorWaterproofValue($facility, $field);
    }
    
    // 外装 - 塗装
    if (str_starts_with($field, 'maintenance_exterior_painting_')) {
        return $this->getMaintenanceExteriorPaintingValue($facility, $field);
    }
    
    // 内装リニューアル
    if (str_starts_with($field, 'maintenance_interior_renewal_')) {
        return $this->getMaintenanceInteriorRenewalValue($facility, $field);
    }
    
    // 内装・衣装履歴
    if (str_starts_with($field, 'maintenance_interior_history_')) {
        return $this->getMaintenanceInteriorHistoryValue($facility, $field);
    }
    
    // その他 - 改修工事履歴
    if (str_starts_with($field, 'maintenance_other_renovation_')) {
        return $this->getMaintenanceOtherRenovationValue($facility, $field);
    }
    
    return '';
}
```

各サブカテゴリー用のヘルパーメソッドを追加：
- `getMaintenanceExteriorWaterproofValue()` - 外装・防水
- `getMaintenanceExteriorPaintingValue()` - 外装・塗装
- `getMaintenanceInteriorRenewalValue()` - 内装リニューアル
- `getMaintenanceInteriorHistoryValue()` - 内装・衣装履歴
- `getMaintenanceOtherRenovationValue()` - その他・改修工事履歴

### 3. config/csv-export-fields.php
5つの新しいカテゴリを追加：

```php
'maintenance_exterior_waterproof' => [
    'title' => '外装 - 防水',
    'icon' => 'fas fa-tint',
    'color' => 'text-info',
    'fields' => [
        'maintenance_exterior_waterproof_date' => '施工日',
        'maintenance_exterior_waterproof_company' => '施工会社',
        'maintenance_exterior_waterproof_contact_person' => '担当者',
        'maintenance_exterior_waterproof_contact' => '連絡先',
        'maintenance_exterior_waterproof_notes' => '備考',
        'maintenance_exterior_waterproof_special_notes' => '特記事項',
    ]
],
'maintenance_exterior_painting' => [
    'title' => '外装 - 塗装',
    'icon' => 'fas fa-paint-roller',
    'color' => 'text-success',
    'fields' => [
        // ... 同様の項目
    ]
],
'maintenance_interior_renewal' => [
    'title' => '内装リニューアル',
    'icon' => 'fas fa-home',
    'color' => 'text-warning',
    'fields' => [
        // ... 4項目
    ]
],
'maintenance_interior_history' => [
    'title' => '内装・衣装履歴',
    'icon' => 'fas fa-history',
    'color' => 'text-primary',
    'fields' => [
        // ... 7項目
    ]
],
'maintenance_other_renovation' => [
    'title' => 'その他 - 改修工事履歴',
    'icon' => 'fas fa-tools',
    'color' => 'text-secondary',
    'fields' => [
        // ... 7項目
    ]
],
```

## データベース構造の想定

### maintenance_histories テーブル
各レコードは以下のカラムを持つ：

```sql
- id
- facility_id
- category (例: 'exterior', 'interior_renewal', 'interior_history', 'other')
- subcategory (例: 'waterproof', 'painting', 'renovation')
- record_number (NO/No)
- maintenance_date (施工日)
- contractor (施工会社)
- contact_person (担当者)
- phone_number (連絡先)
- cost (金額)
- content (修繕内容)
- notes (備考)
- special_notes (特記事項)
- created_at
- updated_at
```

### データ例

#### 外装 - 防水
```php
[
    'category' => 'exterior',
    'subcategory' => 'waterproof',
    'maintenance_date' => '2024-06-15',
    'contractor' => '○○防水工業',
    'contact_person' => '山田太郎',
    'phone_number' => '03-1234-5678',
    'notes' => '屋上防水工事',
    'special_notes' => '10年保証付き',
]
```

#### 内装・衣装履歴
```php
[
    'category' => 'interior_history',
    'record_number' => '2024-001',
    'maintenance_date' => '2024-05-20',
    'contractor' => '△△リフォーム',
    'cost' => 1500000,
    'content' => '1階ロビー壁紙張替え',
    'notes' => 'クロス品番: ABC-123',
    'special_notes' => '防火認定品使用',
]
```

## 影響範囲

### CSVエクスポート
- CSV出力時の列が大幅に変更される
- 修繕履歴が5つのカテゴリーに分割される
- 各カテゴリーで最新の履歴のみが出力される

### エクスポート画面
- 項目選択画面で5つの修繕履歴カテゴリーが表示される
- それぞれ独立して選択可能
- アイコンと色で視覚的に区別

### 既存データ
- 既存の修繕履歴データは `category` と `subcategory` で分類される必要がある
- データ移行が必要

## データ取得ロジック

各カテゴリーの値は、該当する `category` と `subcategory` の組み合わせで最新の履歴を取得：

```php
// 外装・防水の最新履歴
$latest = $facility->maintenanceHistories()
    ->where('category', 'exterior')
    ->where('subcategory', 'waterproof')
    ->orderBy('maintenance_date', 'desc')
    ->first();

// 内装リニューアルの最新履歴
$latest = $facility->maintenanceHistories()
    ->where('category', 'interior_renewal')
    ->orderBy('maintenance_date', 'desc')
    ->first();
```

## テスト方法

### 1. エクスポート画面の確認
```
http://localhost:8000/export/csv
```

1. 修繕履歴カテゴリーを確認
2. 以下の5つのセクションが表示されることを確認：
   - 外装 - 防水（青色アイコン）
   - 外装 - 塗装（緑色アイコン）
   - 内装リニューアル（黄色アイコン）
   - 内装・衣装履歴（青色アイコン）
   - その他 - 改修工事履歴（灰色アイコン）

### 2. CSVエクスポートの実行
1. 施設を選択
2. 修繕履歴の各カテゴリーから項目を選択
3. CSV出力を実行
4. ダウンロードしたCSVファイルを確認

### 3. 期待される結果
CSVヘッダー：
```csv
...,外装_防水_施工日,外装_防水_施工会社,外装_防水_担当者,外装_防水_連絡先,外装_防水_備考,外装_防水_特記事項,外装_塗装_施工日,...
```

## データ移行の推奨

### 1. 既存データの確認
```sql
SELECT id, category, subcategory, maintenance_date, contractor 
FROM maintenance_histories 
ORDER BY facility_id, maintenance_date DESC;
```

### 2. カテゴリーの標準化
既存データの `category` と `subcategory` を新しい構造に合わせる：

```php
// 移行スクリプト例
$mappings = [
    ['old_category' => '外装', 'old_subcategory' => '防水', 'new_category' => 'exterior', 'new_subcategory' => 'waterproof'],
    ['old_category' => '外装', 'old_subcategory' => '塗装', 'new_category' => 'exterior', 'new_subcategory' => 'painting'],
    ['old_category' => '内装', 'old_subcategory' => 'リニューアル', 'new_category' => 'interior_renewal', 'new_subcategory' => null],
    // ...
];

foreach ($mappings as $mapping) {
    MaintenanceHistory::where('category', $mapping['old_category'])
        ->where('subcategory', $mapping['old_subcategory'])
        ->update([
            'category' => $mapping['new_category'],
            'subcategory' => $mapping['new_subcategory'],
        ]);
}
```

## 今後の改善案

### 1. 入力フォームの更新
- 施設編集画面で修繕履歴を新しいカテゴリー構造で入力
- カテゴリー選択時に適切な入力フィールドを表示

### 2. 複数履歴の出力
- 現在は最新の履歴のみ出力
- 複数の履歴を出力するオプションを追加

### 3. 集計機能
- カテゴリー別の総費用
- 年度別の修繕履歴サマリー

### 4. 通知機能
- 定期的な外装メンテナンスの通知
- 保証期間終了の通知

## 関連ドキュメント
- `docs/csv-export-hvac-lighting-split.md` - 空調・照明設備の分割
- `docs/csv-export-field-order-fix.md` - 項目順序の修正
- `config/csv-export-fields.php` - フィールド定義
- `app/Services/ExportService.php` - エクスポートサービス
- `app/Services/Export/FieldValueExtractor.php` - 値抽出サービス
