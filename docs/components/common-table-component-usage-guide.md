# Common Table Component 使用ガイド

## 概要

Common Table Componentは、Shise-Cal施設管理システム全体で一貫したテーブル表示を提供する再利用可能なBladeコンポーネントです。このガイドでは、コンポーネントの基本的な使用方法から高度な機能まで詳しく説明します。

## 基本的な使用方法

### 最小限の実装

```blade
<x-common-table :data="$tableData" />
```

### タイトル付きテーブル

```blade
<x-common-table 
    title="基本情報" 
    :data="$tableData" 
/>
```

### カスタムスタイリング

```blade
<x-common-table 
    title="施設詳細情報"
    :data="$tableData"
    cardClass="custom-card-class mb-4"
    tableClass="table table-striped custom-table"
    :responsive="false"
    :cleanBody="false"
/>
```

## データ構造の詳細説明

### 基本データ構造

```php
$tableData = [
    [
        'type' => 'standard',  // 行タイプ: 'standard', 'grouped', 'single'
        'cells' => [
            [
                'label' => 'ラベル名',
                'value' => '表示値',
                'type' => 'text',      // セルタイプ
                'colspan' => 1,        // カラムスパン（オプション）
                'rowspan' => 1,        // ロースパン（オプション）
            ],
            // 追加のセル...
        ]
    ],
    // 追加の行...
];
```

### 行タイプ（Row Types）

#### 1. Standard Row（標準行）
最も一般的な行タイプで、ラベル-値のペアを表示します。

```php
[
    'type' => 'standard',
    'cells' => [
        ['label' => '会社名', 'value' => '株式会社サンプル', 'type' => 'text'],
        ['label' => '電話番号', 'value' => '03-1234-5678', 'type' => 'text'],
    ]
]
```

#### 2. Grouped Row（グループ化行）
関連する情報をグループ化して表示する際に使用します。

```php
[
    'type' => 'grouped',
    'cells' => [
        ['label' => '管理会社', 'value' => '株式会社管理', 'type' => 'text', 'rowspan' => 2],
        ['label' => '担当者', 'value' => '田中太郎', 'type' => 'text'],
    ]
],
[
    'type' => 'standard',
    'cells' => [
        ['label' => '連絡先', 'value' => 'tanaka@example.com', 'type' => 'email'],
    ]
]
```

#### 3. Single Row（単一行）
全幅を使用する単一の情報を表示する際に使用します。

```php
[
    'type' => 'single',
    'cells' => [
        ['label' => '備考', 'value' => '長いテキストの説明...', 'type' => 'text', 'colspan' => 3],
    ]
]
```

## セルタイプ別使用例

### 1. Text（テキスト）
```php
['label' => '会社名', 'value' => '株式会社サンプル', 'type' => 'text']
```

### 2. Badge（バッジ）
```php
['label' => 'ステータス', 'value' => 'アクティブ', 'type' => 'badge']
```

### 3. Email（メールアドレス）
```php
['label' => 'メールアドレス', 'value' => 'contact@example.com', 'type' => 'email']
```

### 4. URL（ウェブサイト）
```php
['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url']
```

### 5. Date（日付）
```php
['label' => '設立日', 'value' => '2023-04-01', 'type' => 'date']
// 表示: 2023年4月1日
```

### 6. Currency（通貨）
```php
['label' => '資本金', 'value' => 10000000, 'type' => 'currency']
// 表示: ¥10,000,000
```

### 7. Number（数値）
```php
['label' => '従業員数', 'value' => 150, 'type' => 'number']
// 表示: 150
```

### 8. File（ファイルリンク）
```php
['label' => '契約書', 'value' => '/storage/contracts/contract.pdf', 'type' => 'file']
```

## 実際の使用例

### 基本情報表示カード

```php
// Controller
public function show(Facility $facility)
{
    $basicInfoData = [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
                ['label' => '事業所コード', 'value' => $facility->office_code, 'type' => 'badge'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '郵便番号', 'value' => $facility->postal_code, 'type' => 'text'],
                ['label' => '住所', 'value' => $facility->address, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
                ['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'],
            ]
        ],
        [
            'type' => 'single',
            'cells' => [
                ['label' => '備考', 'value' => $facility->notes, 'type' => 'text', 'colspan' => 3],
            ]
        ]
    ];

    return view('facilities.show', compact('facility', 'basicInfoData'));
}
```

```blade
{{-- View --}}
<x-common-table 
    title="基本情報" 
    :data="$basicInfoData" 
/>
```

### 土地情報表示カード（複雑な例）

```php
$landInfoData = [
    [
        'type' => 'grouped',
        'cells' => [
            ['label' => '管理会社', 'value' => $landInfo->management_company, 'type' => 'text', 'rowspan' => 3],
            ['label' => '担当者', 'value' => $landInfo->manager_name, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '電話番号', 'value' => $landInfo->manager_phone, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => 'メール', 'value' => $landInfo->manager_email, 'type' => 'email'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '契約開始日', 'value' => $landInfo->contract_start_date, 'type' => 'date'],
            ['label' => '契約終了日', 'value' => $landInfo->contract_end_date, 'type' => 'date'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '月額賃料', 'value' => $landInfo->monthly_rent, 'type' => 'currency'],
            ['label' => '敷金', 'value' => $landInfo->security_deposit, 'type' => 'currency'],
        ]
    ]
];
```

## コンポーネントプロパティ詳細

### 必須プロパティ

| プロパティ | 型 | 説明 |
|-----------|---|------|
| `data` | array | テーブルデータ配列 |

### オプションプロパティ

| プロパティ | 型 | デフォルト値 | 説明 |
|-----------|---|------------|------|
| `title` | string\|null | null | カードタイトル |
| `cardClass` | string | 'facility-info-card detail-card-improved mb-3' | カードのCSSクラス |
| `tableClass` | string | 'table table-bordered facility-basic-info-table-clean' | テーブルのCSSクラス |
| `responsive` | boolean | true | レスポンシブテーブルの有効/無効 |
| `cleanBody` | boolean | true | card-body-cleanクラスの適用 |

## 空値の処理

コンポーネントは空値（null、空文字列、空配列）を自動的に検出し、「未設定」として表示します。

```php
// これらの値は「未設定」として表示される
['label' => '会社名', 'value' => null, 'type' => 'text'],
['label' => '住所', 'value' => '', 'type' => 'text'],
['label' => 'タグ', 'value' => [], 'type' => 'text'],
```

## CSSクラスとスタイリング

### 自動適用されるクラス

- `detail-label`: ラベルセル
- `detail-value`: 値セル  
- `empty-field`: 空フィールド
- `facility-basic-info-table-clean`: テーブル全体
- `card-body-clean`: カードボディ

### カスタムスタイリング

```blade
<x-common-table 
    :data="$data"
    cardClass="custom-facility-card shadow-sm"
    tableClass="table table-hover custom-table-style"
/>
```

## レスポンシブデザイン

コンポーネントは自動的にレスポンシブ対応されており、以下のブレークポイントで最適化されます：

- **768px以下**: モバイル表示に最適化
- **576px以下**: 小画面デバイス用レイアウト

## アクセシビリティ機能

- 適切なARIA属性の自動設定
- スクリーンリーダー対応
- ハイコントラストモード対応
- キーボードナビゲーション対応

## パフォーマンス考慮事項

### 大量データの処理

```php
// 大量データの場合は段階的に表示
$tableData = array_slice($allData, 0, 50); // 最初の50件のみ表示
```

### キャッシュの活用

```php
// 静的データはキャッシュを活用
$tableData = Cache::remember("facility_{$facility->id}_basic_info", 3600, function() use ($facility) {
    return $this->buildBasicInfoData($facility);
});
```

## エラーハンドリング

コンポーネントは不正なデータ構造に対して適切にエラーハンドリングを行います：

```php
// 不正なデータ構造の例
$invalidData = [
    [
        'cells' => [
            ['value' => 'ラベルなし'], // labelが不足
        ]
    ]
];
```

この場合、エラーメッセージが表示され、フォールバック表示が提供されます。

## トラブルシューティング

### よくある問題と解決方法

1. **データが表示されない**
   - データ構造が正しいか確認
   - 必須フィールド（label, value, type）が設定されているか確認

2. **スタイリングが適用されない**
   - CSSファイルが正しく読み込まれているか確認
   - カスタムクラスが正しく指定されているか確認

3. **レスポンシブ表示が機能しない**
   - `responsive` プロパティがtrueに設定されているか確認
   - Bootstrapのレスポンシブクラスが利用可能か確認

## 関連ドキュメント

- [Common Table Row Implementation Summary](common-table-row-implementation-summary.md)
- [Common Table Card Wrapper Implementation](common-table-card-wrapper-implementation.md)
- [Accessibility Implementation](accessibility-implementation.md)
- [Performance Optimization Summary](../performance/common-table-performance-optimization-summary.md)