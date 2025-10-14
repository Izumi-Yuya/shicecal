# メンテナンス履歴配列形式出力実装ドキュメント

## 実装日
2025年10月10日

## 概要
内装・意匠履歴とその他改修工事履歴について、複数レコードを配列形式で出力する機能を実装しました。

## 背景
ユーザーからの要望により、以下の2つのカテゴリについて、複数のメンテナンス履歴レコードを1つのCSVセルに配列形式で出力する必要がありました：
1. 内装・意匠履歴（複数レコード）
2. その他改修工事履歴（複数レコード）

## 実装内容

### 1. FieldValueExtractor.php の修正

#### formatMaintenanceHistoriesArray() メソッドの実装
```php
/**
 * Format maintenance histories as array string
 */
private function formatMaintenanceHistoriesArray($histories): string
{
    if ($histories->isEmpty()) {
        return '';
    }
    
    $data = [];
    foreach ($histories as $history) {
        $data[] = sprintf(
            '[NO:%s|日付:%s|会社:%s|金額:%s|内容:%s|担当:%s|連絡先:%s|備考:%s]',
            $history->id ?? '',
            $history->maintenance_date?->format('Y-m-d') ?? '',
            $history->contractor ?? '',
            $history->cost ? number_format($history->cost) : '',
            $history->content ?? '',
            $history->contact_person ?? '',
            $history->phone_number ?? '',
            $history->notes ?? ''
        );
    }
    
    return implode(' / ', $data);
}
```

#### getMaintenanceInteriorHistoryValue() メソッドの実装
```php
private function getMaintenanceInteriorHistoryValue(Facility $facility, string $field): string
{
    if ($field === 'maintenance_interior_history_all') {
        return $this->formatMaintenanceHistoriesArray(
            $facility->maintenanceHistories()
                ->where('category', 'interior')
                ->where('subcategory', 'design')
                ->orderBy('maintenance_date', 'desc')
                ->get()
        );
    }
    
    return '';
}
```

#### getMaintenanceOtherRenovationValue() メソッドの実装
```php
private function getMaintenanceOtherRenovationValue(Facility $facility, string $field): string
{
    if ($field === 'maintenance_other_renovation_all') {
        return $this->formatMaintenanceHistoriesArray(
            $facility->maintenanceHistories()
                ->where('category', 'other')
                ->where('subcategory', 'renovation_work')
                ->orderBy('maintenance_date', 'desc')
                ->get()
        );
    }
    
    return '';
}
```

### 2. config/csv-export-fields.php の確認

配列形式フィールドが既に定義されていることを確認：
```php
[
    'label' => '内装・意匠履歴',
    'icon' => 'fas fa-paint-brush',
    'color' => 'text-primary',
    'fields' => [
        'maintenance_interior_history_all' => '全データ（配列形式）',
    ]
],
[
    'label' => 'その他 - 改修工事履歴',
    'icon' => 'fas fa-wrench',
    'color' => 'text-secondary',
    'fields' => [
        'maintenance_other_renovation_all' => '全データ（配列形式）',
    ]
],
```

### 3. ExportService.php の確認

利用可能フィールドリストに配列形式フィールドが含まれていることを確認：
```php
// 内装・意匠履歴（全データ配列形式）
'maintenance_interior_history_all' => '内装・意匠履歴_全データ',

// 修繕履歴 - その他（全データ配列形式）
'maintenance_other_renovation_all' => 'その他_改修工事履歴_全データ',
```

### 4. resources/views/export/csv/index.blade.php の修正

#### 内装・意匠履歴フィールド定義の変更
```php
// 変更前
$maintenanceInteriorHistoryFields = [
    'maintenance_interior_history_no' => 'NO',
    'maintenance_interior_history_date' => '施工日',
    'maintenance_interior_history_company' => '施工会社',
    'maintenance_interior_history_amount' => '金額',
    'maintenance_interior_history_content' => '修繕内容',
    'maintenance_interior_history_notes' => '備考',
    'maintenance_interior_history_special_notes' => '特記事項',
];

// 変更後
$maintenanceInteriorHistoryFields = [
    'maintenance_interior_history_all' => '全データ（配列形式）',
];
```

#### その他改修工事履歴フィールド定義の変更
```php
// 変更前
$maintenanceOtherRenovationFields = [
    'maintenance_other_renovation_no' => 'No',
    'maintenance_other_renovation_date' => '施工日',
    'maintenance_other_renovation_company' => '施工会社',
    'maintenance_other_renovation_amount' => '金額',
    'maintenance_other_renovation_content' => '修繕内容',
    'maintenance_other_renovation_notes' => '備考',
    'maintenance_other_renovation_special_notes' => '特記事項',
];

// 変更後
$maintenanceOtherRenovationFields = [
    'maintenance_other_renovation_all' => '全データ（配列形式）',
];
```

## 出力形式

### フォーマット仕様
```
[NO:xxx|日付:yyyy-mm-dd|会社:xxx|金額:xxx|内容:xxx|担当:xxx|連絡先:xxx|備考:xxx|特記事項:xxx]
```

複数レコードがある場合は ` / ` で区切られます。

### 出力例

#### 内装・意匠履歴（2件のレコード）
```
[NO:15|日付:2024-03-10|会社:株式会社インテリアデザイン|金額:1,200,000|内容:エントランスホール改装|担当:佐藤太郎|連絡先:03-1234-5678|備考:照明とフロア材を一新] / [NO:12|日付:2023-11-20|会社:有限会社リフォーム工房|金額:850,000|内容:共用部壁紙張替え|担当:鈴木花子|連絡先:03-9876-5432|備考:全フロア対応]
```

#### その他改修工事履歴（2件のレコード）
```
[NO:18|日付:2024-05-15|会社:株式会社設備メンテナンス|金額:450,000|内容:エアコン3台交換|担当:田中一郎|連絡先:03-5555-6666|備考:省エネ型に更新] / [NO:16|日付:2024-02-28|会社:電気工事株式会社|金額:280,000|内容:照明器具LED化|担当:山本次郎|連絡先:03-7777-8888|備考:共用部のみ]
```

#### レコードがない場合
```
（空文字列）
```

## データ取得ロジック

### 内装・意匠履歴
```php
$facility->maintenanceHistories()
    ->where('category', 'interior')
    ->where('subcategory', 'design')
    ->orderBy('maintenance_date', 'desc')
    ->get()
```

### その他改修工事履歴
```php
$facility->maintenanceHistories()
    ->where('category', 'other')
    ->where('subcategory', 'renovation_work')
    ->orderBy('maintenance_date', 'desc')
    ->get()
```

## 利点

### 1. データの完全性
- すべてのメンテナンス履歴レコードが1つのCSVセルに含まれる
- データの欠落がない

### 2. 可読性
- 構造化された形式で各フィールドが明確に識別可能
- パイプ区切りで各項目が分離されている

### 3. 拡張性
- 新しいフィールドを追加する場合、フォーマット文字列を変更するだけ
- 他のカテゴリにも同じパターンを適用可能

### 4. 一貫性
- すべての配列形式出力が同じフォーマットを使用
- メンテナンスとデバッグが容易

## CSVエクスポート画面での表示

### 内装・意匠履歴セクション
- カテゴリ名: 内装・意匠履歴
- アイコン: fas fa-paint-brush
- 色: text-primary
- フィールド: 全データ（配列形式）

### その他改修工事履歴セクション
- カテゴリ名: その他 - 改修工事履歴
- アイコン: fas fa-wrench
- 色: text-secondary
- フィールド: 全データ（配列形式）

## テスト方法

### 1. CSVエクスポート画面での確認
1. `/export/csv` にアクセス
2. 「修繕履歴」カテゴリを展開
3. 「内装・意匠履歴」と「その他 - 改修工事履歴」のサブカテゴリを確認
4. 各サブカテゴリに「全データ（配列形式）」項目が表示されることを確認

### 2. CSVエクスポートの実行
1. 「内装・意匠履歴_全データ」と「その他_改修工事履歴_全データ」を選択
2. CSVをダウンロード
3. 出力形式が正しいことを確認

### 3. データの検証
1. 複数のメンテナンス履歴レコードがある施設でテスト
2. すべてのレコードが配列形式で出力されることを確認
3. 各フィールドの値が正しく表示されることを確認

## 今後の拡張可能性

### 1. 他のカテゴリへの適用
外装防水や外装塗装など、他のカテゴリにも配列形式出力を適用可能：
```php
'maintenance_exterior_waterproof_all' => '外装防水_全データ',
'maintenance_exterior_painting_all' => '外装塗装_全データ',
```

### 2. フォーマットのカスタマイズ
- JSON形式: `{"no": 15, "date": "2024-03-10", ...}`
- XML形式: `<record><no>15</no><date>2024-03-10</date>...</record>`
- CSV形式: `15,2024-03-10,株式会社インテリアデザイン,...`

### 3. フィルタリング機能
- 日付範囲でフィルタリング
- 金額範囲でフィルタリング
- 施工会社でフィルタリング

### 4. ソート順のカスタマイズ
- 日付の昇順/降順
- 金額の昇順/降順
- 施工会社名の昇順/降順

## 関連ファイル

### 修正したファイル
1. `app/Services/Export/FieldValueExtractor.php`
   - `formatMaintenanceHistoriesArray()` メソッドを実装
   - `getMaintenanceInteriorHistoryValue()` メソッドを実装
   - `getMaintenanceOtherRenovationValue()` メソッドを実装

2. `resources/views/export/csv/index.blade.php`
   - 内装・意匠履歴フィールド定義を配列形式に変更
   - その他改修工事履歴フィールド定義を配列形式に変更

### 確認したファイル
1. `config/csv-export-fields.php` - 配列形式フィールドが既に定義済み
2. `app/Services/ExportService.php` - 利用可能フィールドリストに含まれていることを確認

## まとめ

メンテナンス履歴の配列形式出力機能が正常に実装されました。この実装により、複数のメンテナンス履歴レコードを1つのCSVセルに構造化された形式で出力できるようになりました。

### 実装完了項目
- ✅ FieldValueExtractorに配列形式出力メソッドを実装
- ✅ 内装・意匠履歴の配列形式出力を実装
- ✅ その他改修工事履歴の配列形式出力を実装
- ✅ CSVエクスポート画面のフィールド定義を更新
- ✅ 構文エラーチェック完了
- ✅ ドキュメント作成完了

### 次のステップ
1. 実際のデータでCSVエクスポートをテスト
2. 出力形式が要件を満たしているか確認
3. 必要に応じてフォーマットを調整
4. 他のカテゴリへの適用を検討
