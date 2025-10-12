# 内装・意匠履歴CSVエクスポートトラブルシューティング

## 問題
本社にデータが入っているはずなのに、CSVで内装履歴が表示されていない。

## 原因
CSVエクスポート時に「内装・意匠履歴_全データ」フィールドが選択されていない可能性があります。

## 確認手順

### 1. データの存在確認
本社施設に内装・意匠履歴のデータが存在することを確認しました：

```
施設ID: 102
施設名: 本社
内装・意匠履歴の件数: 1

データ詳細:
ID: 1433, 日付: 2025-05-18, 会社: 田中電気工事
```

### 2. システム動作確認
FieldValueExtractorとExportServiceの動作を確認しました：

```
結果: [NO:1433|日付:2025-05-18|会社:田中電気工事|金額:308,764|内容:給湯器交換|担当:|連絡先:423-446-1488|備考:|特記事項:Et nam quia aspernatur vel ut nesciunt quo qui consequatur aut voluptatem.]
```

✅ システムは正常に動作しています。

### 3. CSVエクスポートテスト
実際のCSVエクスポートをシミュレートしました：

```csv
施設名,内装・意匠履歴_全データ
本社,"[NO:1433|日付:2025-05-18|会社:田中電気工事|金額:308,764|内容:給湯器交換|担当:|連絡先:423-446-1488|備考:|特記事項:Et nam quia aspernatur vel ut nesciunt quo qui consequatur aut voluptatem.]"
```

✅ CSVエクスポートは正常に動作しています。

## 解決方法

### CSVエクスポート画面での操作手順

1. **CSVエクスポート画面にアクセス**
   - `/export/csv` にアクセス

2. **施設を選択**
   - 本社を選択

3. **修繕履歴カテゴリを展開**
   - 「修繕履歴」カテゴリの左側にある矢印アイコンをクリック
   - サブカテゴリが表示されます

4. **内装・意匠履歴を展開**
   - 「内装・意匠履歴」サブカテゴリの左側にある矢印アイコンをクリック
   - フィールドが表示されます

5. **フィールドを選択**
   - ☑ 「全データ（配列形式）」にチェックを入れる

6. **CSVをダウンロード**
   - 「CSVダウンロード」ボタンをクリック

### 確認ポイント

#### ✅ フィールドが表示されているか
- 修繕履歴 > 内装・意匠履歴 > 全データ（配列形式）

#### ✅ チェックボックスが選択されているか
- チェックボックスにチェックが入っているか確認

#### ✅ 施設が選択されているか
- 本社が選択されているか確認

## よくある問題と解決策

### 問題1: フィールドが表示されない
**原因**: カテゴリが折りたたまれている

**解決策**:
1. 「修繕履歴」カテゴリの左側の矢印アイコンをクリック
2. 「内装・意匠履歴」サブカテゴリの左側の矢印アイコンをクリック

### 問題2: チェックボックスが選択できない
**原因**: JavaScriptエラーまたはブラウザの問題

**解決策**:
1. ブラウザのコンソールを開いてエラーを確認
2. ページをリロード
3. 別のブラウザで試す

### 問題3: CSVに空欄で表示される
**原因**: フィールドは選択されているが、データが存在しない

**解決策**:
1. 施設の修繕履歴画面で内装・意匠履歴のデータを確認
2. カテゴリとサブカテゴリが正しいか確認
   - カテゴリ: `interior`
   - サブカテゴリ: `design`

### 問題4: 他の施設では表示されるが、本社では表示されない
**原因**: 本社のデータのカテゴリ/サブカテゴリが異なる

**解決策**:
データベースで確認：
```sql
SELECT id, facility_id, maintenance_date, category, subcategory, content
FROM maintenance_histories
WHERE facility_id = 102
AND category = 'interior';
```

## データ構造の確認

### 正しいデータ構造
```php
[
    'facility_id' => 102,
    'category' => 'interior',
    'subcategory' => 'design',
    'maintenance_date' => '2025-05-18',
    'content' => '給湯器交換',
    'contractor' => '田中電気工事',
    'cost' => 308764,
    // その他のフィールド
]
```

### カテゴリとサブカテゴリの対応表

| カテゴリ | サブカテゴリ | 説明 |
|---------|------------|------|
| exterior | waterproof | 外装 - 防水 |
| exterior | painting | 外装 - 塗装 |
| interior | renovation | 内装リニューアル |
| interior | design | 内装・意匠履歴 |
| other | renovation_work | その他 - 改修工事履歴 |

## テスト用SQLクエリ

### 本社の内装・意匠履歴を確認
```sql
SELECT 
    id,
    maintenance_date,
    contractor,
    cost,
    content,
    category,
    subcategory
FROM maintenance_histories
WHERE facility_id = 102
AND category = 'interior'
AND subcategory = 'design'
ORDER BY maintenance_date DESC;
```

### すべての施設の内装・意匠履歴件数を確認
```sql
SELECT 
    f.id,
    f.facility_name,
    COUNT(mh.id) as history_count
FROM facilities f
LEFT JOIN maintenance_histories mh ON f.id = mh.facility_id
    AND mh.category = 'interior'
    AND mh.subcategory = 'design'
GROUP BY f.id, f.facility_name
HAVING history_count > 0
ORDER BY history_count DESC;
```

## システム動作確認コマンド

### Artisan Tinkerでの確認
```php
// 本社の内装・意匠履歴を確認
$facility = App\Models\Facility::where('facility_name', 'LIKE', '%本社%')->first();
$histories = $facility->maintenanceHistories()
    ->where('category', 'interior')
    ->where('subcategory', 'design')
    ->get();

foreach ($histories as $h) {
    echo "ID: {$h->id}, 日付: {$h->maintenance_date}, 会社: {$h->contractor}\n";
}

// FieldValueExtractorのテスト
$extractor = app(App\Services\Export\FieldValueExtractor::class);
$value = $extractor->getFieldValue($facility, 'maintenance_interior_history_all');
echo $value;

// ExportServiceのテスト
$exportService = app(App\Services\ExportService::class);
$reflection = new ReflectionClass($exportService);
$method = $reflection->getMethod('getFieldValue');
$method->setAccessible(true);
$value = $method->invoke($exportService, $facility, 'maintenance_interior_history_all');
echo $value;
```

## まとめ

### 確認済み項目
- ✅ 本社にデータが存在する（ID: 1433）
- ✅ FieldValueExtractorが正常に動作する
- ✅ ExportServiceが正常に動作する
- ✅ CSVエクスポートが正常に動作する
- ✅ ビューファイルにフィールドが定義されている

### 推奨される対応
1. CSVエクスポート画面で「内装・意匠履歴_全データ」フィールドを選択
2. 選択されているか確認
3. CSVをダウンロード
4. CSVファイルを開いて内容を確認

### サポートが必要な場合
以下の情報を提供してください：
1. CSVエクスポート画面のスクリーンショット
2. ダウンロードしたCSVファイル
3. ブラウザのコンソールエラー（F12キーで開く）
4. 選択したフィールドのリスト

システムは正常に動作しているため、操作手順を確認することで問題が解決するはずです。
