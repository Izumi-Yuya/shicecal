# CSVエクスポート項目差異分析

## 実行日時
2025-10-10 16:33:41

## 問題の概要
CSVエクスポート画面で表示されている項目と、実際に出力されるCSVファイルの項目に差異がある。

## 差異の詳細

### 1. 設定ファイルに定義されているが、CSVに出力されていない項目

#### 図面カテゴリ
- **`drawing_handover_notes`** (引き渡し図面備考)
  - 設定ファイル: `config/csv-export-fields.php` に定義あり
  - ビューファイル: `resources/views/export/csv/index.blade.php` に表示あり
  - CSV出力: **含まれていない**
  - 原因: `FieldValueExtractor::getHandoverNotes()` メソッドの実装に問題がある可能性

### 2. CSVに出力されているが、設定ファイルに定義されていない項目

#### 修繕履歴カテゴリ（未定義）
以下の項目がCSVに含まれているが、`config/csv-export-fields.php` には定義されていない：

- `修繕履歴_最新修繕日`
- `修繕履歴_最新修繕内容`
- `修繕履歴_最新修繕費用`
- `修繕履歴_最新施工業者`
- `修繕履歴_最新カテゴリ`
- `修繕履歴_最新サブカテゴリ`
- `修繕履歴_最新担当者`
- `修繕履歴_最新電話番号`
- `修繕履歴_最新備考`
- `修繕履歴_最新保証期間`
- `修繕履歴_総件数`
- `修繕履歴_総費用`

**原因**: これらの項目は古い実装の名残で、現在は使用されていない可能性がある。

### 3. 項目名の不一致

#### 図面カテゴリ
- 設定ファイル: `drawing_notes` → **「図面備考」**
- CSV出力: **「図面備考」** として出力されている（正しい）

## 実際のCSVヘッダー（抜粋）

```csv
会社名,事業所コード,施設名,...,図面備考,修繕履歴_最新修繕日,修繕履歴_最新修繕内容,...
```

## 設定ファイルの定義（図面カテゴリ）

```php
'drawing' => [
    'title' => '図面',
    'icon' => 'fas fa-drafting-compass',
    'color' => 'text-primary',
    'fields' => [
        'drawing_handover_notes' => '引き渡し図面備考',
        'drawing_notes' => '図面備考',
    ]
],
```

## FieldValueExtractorの実装確認

### getDrawingFieldValue メソッド
```php
private function getDrawingFieldValue(Facility $facility, string $field): string
{
    $drawing = $this->getCachedRelationship($facility, 'drawing');
    
    if (!$drawing) {
        return '';
    }

    return match ($field) {
        'drawing_handover_notes' => $this->getHandoverNotes($drawing),
        'drawing_notes' => $drawing->notes ?? '',
        default => '',
    };
}
```

### getHandoverNotes メソッド
```php
private function getHandoverNotes($drawing): string
{
    if (!$drawing) {
        return '';
    }
    
    $handoverDrawings = $drawing->handover_drawings ?? [];
    
    // handover_drawingsがJSON配列の場合、notesキーを取得
    if (is_array($handoverDrawings) && isset($handoverDrawings['notes'])) {
        return $handoverDrawings['notes'];
    }
    
    return '';
}
```

**問題点**: `handover_drawings` は配列形式で複数の図面を管理しているが、`notes` キーは配列のトップレベルではなく、別のカラムに保存されている可能性がある。

## データベース構造の確認が必要

### facility_drawings テーブル
- `handover_drawings` (JSON): 引き渡し図面のリスト
- `handover_drawings_notes` (TEXT): 引き渡し図面備考（別カラム）
- `completion_drawings_notes` (TEXT): 竣工時引き渡し図面備考
- `notes` (TEXT): 図面備考

## 修正が必要な箇所

### 1. FieldValueExtractor::getHandoverNotes() の修正
```php
private function getHandoverNotes($drawing): string
{
    if (!$drawing) {
        return '';
    }
    
    // handover_drawings_notes カラムから取得
    return $drawing->handover_drawings_notes ?? '';
}
```

### 2. 修繕履歴項目の削除
以下のいずれかの対応が必要：
- A. `config/csv-export-fields.php` に修繕履歴カテゴリを追加
- B. CSV出力から修繕履歴項目を削除（推奨）

### 3. CsvExportService の確認
修繕履歴項目がどこで追加されているかを確認する必要がある。

## 推奨される修正手順

1. **FieldValueExtractor の修正**
   - `getHandoverNotes()` メソッドを修正して、正しいカラムから取得

2. **修繕履歴項目の処理**
   - 設定ファイルに定義されていない項目がCSVに含まれている原因を特定
   - 不要な場合は削除、必要な場合は設定ファイルに追加

3. **テスト**
   - 修正後、CSVエクスポートを実行して項目が正しく出力されることを確認

## 関連ファイル

- `config/csv-export-fields.php` - フィールド定義
- `app/Services/Export/FieldValueExtractor.php` - 値抽出ロジック
- `app/Services/Export/CsvExportService.php` - CSV生成ロジック
- `resources/views/export/csv/index.blade.php` - エクスポート画面
- `database/migrations/2025_10_01_000032_add_completion_drawings_to_facility_drawings_table.php` - テーブル構造
