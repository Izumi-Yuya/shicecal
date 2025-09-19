# Common Table Component Usage Examples

## Basic Usage

### Simple Table with Title
```blade
<x-common-table 
    title="基本情報" 
    :data="$tableData" 
/>
```

### Table without Title
```blade
<x-common-table :data="$tableData" />
```

## Data Structure

### Standard Row Format
```php
$tableData = [
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
            ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
            ['label' => 'ウェブサイト', 'value' => $facility->website, 'type' => 'url'],
        ]
    ]
];
```

### Supported Cell Types
- `text`: 通常のテキスト
- `badge`: バッジ表示
- `email`: メールリンク
- `url`: URLリンク
- `date`: 日本語日付フォーマット
- `currency`: 通貨フォーマット
- `number`: 数値フォーマット

### Empty Values
Empty or null values are automatically displayed as "未設定" with the `empty-field` CSS class.

## Component Properties

### Main Component (`common-table`)
- `data`: テーブルデータ配列 (required)
- `title`: カードタイトル (optional)
- `cardClass`: カードのCSSクラス (default: 'facility-info-card detail-card-improved mb-3')
- `tableClass`: テーブルのCSSクラス (default: 'table table-bordered facility-basic-info-table-clean')
- `responsive`: レスポンシブテーブルの有効/無効 (default: true)
- `cleanBody`: card-body-cleanクラスの適用 (default: true)

## CSS Classes Applied

### Automatic Classes
- `detail-label`: ラベルセル
- `detail-value`: 値セル
- `empty-field`: 空フィールド
- `facility-basic-info-table-clean`: テーブル全体
- `card-body-clean`: カードボディ

### Responsive Design
The component automatically applies responsive table classes and works with existing Bootstrap breakpoints.

## Testing

The component includes comprehensive tests in `tests/Feature/Components/CommonTableBasicTest.php` covering:
- Empty table rendering
- Title display
- Data rendering
- Empty value handling
- CSS class application
- Different cell types