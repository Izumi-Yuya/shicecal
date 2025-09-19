# Common Table Component データ構造リファレンス

## 概要

このドキュメントでは、Common Table Componentで使用するデータ構造の詳細な仕様とサンプルコードを提供します。

## 基本データ構造

### テーブルデータ配列の構造

```php
$tableData = [
    // 行データ1
    [
        'type' => string,      // 行タイプ（必須）
        'cells' => [           // セル配列（必須）
            // セルデータ...
        ]
    ],
    // 行データ2...
];
```

### セルデータの構造

```php
[
    'label' => string|null,    // ラベルテキスト（必須、ただしnull可）
    'value' => mixed,          // 表示値（必須、ただしnull可）
    'type' => string,          // セルタイプ（必須）
    'colspan' => int,          // カラムスパン（オプション、デフォルト: 1）
    'rowspan' => int,          // ローススパン（オプション、デフォルト: 1）
    'class' => string,         // 追加CSSクラス（オプション）
    'attributes' => array,     // 追加HTML属性（オプション）
]
```

## 行タイプ（Row Types）

### 1. Standard（標準行）

最も一般的な行タイプで、ラベル-値のペアを横並びで表示します。

```php
[
    'type' => 'standard',
    'cells' => [
        ['label' => '会社名', 'value' => '株式会社サンプル', 'type' => 'text'],
        ['label' => '事業所コード', 'value' => 'ABC123', 'type' => 'badge'],
    ]
]
```

**レンダリング結果:**
```html
<tr>
    <td class="detail-label">会社名</td>
    <td class="detail-value">株式会社サンプル</td>
    <td class="detail-label">事業所コード</td>
    <td class="detail-value"><span class="badge">ABC123</span></td>
</tr>
```

### 2. Grouped（グループ化行）

関連する情報をグループ化し、rowspanを使用して表示します。

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

### 3. Single（単一行）

全幅を使用する単一の情報を表示します。

```php
[
    'type' => 'single',
    'cells' => [
        ['label' => '備考', 'value' => '長いテキストの説明文...', 'type' => 'text', 'colspan' => 3],
    ]
]
```

## セルタイプ（Cell Types）

### 1. Text（テキスト）

```php
['label' => 'ラベル', 'value' => 'テキスト値', 'type' => 'text']
```

**出力:** プレーンテキストとして表示

### 2. Badge（バッジ）

```php
['label' => 'ステータス', 'value' => 'アクティブ', 'type' => 'badge']
```

**出力:** `<span class="badge bg-primary">アクティブ</span>`

### 3. Email（メールアドレス）

```php
['label' => 'メール', 'value' => 'test@example.com', 'type' => 'email']
```

**出力:** `<a href="mailto:test@example.com"><i class="fas fa-envelope"></i> test@example.com</a>`

### 4. URL（ウェブサイト）

```php
['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url']
```

**出力:** `<a href="https://example.com" target="_blank"><i class="fas fa-external-link-alt"></i> https://example.com</a>`

### 5. Date（日付）

```php
['label' => '設立日', 'value' => '2023-04-01', 'type' => 'date']
```

**出力:** `2023年4月1日`

### 6. Currency（通貨）

```php
['label' => '資本金', 'value' => 10000000, 'type' => 'currency']
```

**出力:** `¥10,000,000`

### 7. Number（数値）

```php
['label' => '従業員数', 'value' => 150, 'type' => 'number']
```

**出力:** `150`（区切り文字付き）

### 8. File（ファイルリンク）

```php
['label' => '契約書', 'value' => '/storage/contracts/contract.pdf', 'type' => 'file']
```

**出力:** `<a href="/storage/contracts/contract.pdf"><i class="fas fa-file-pdf"></i> contract.pdf</a>`

## 実用的なサンプルデータ

### 基本情報表示用データ

```php
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
            ['label' => '都道府県', 'value' => $facility->prefecture, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'single',
        'cells' => [
            ['label' => '住所', 'value' => $facility->address, 'type' => 'text', 'colspan' => 3],
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
        'type' => 'standard',
        'cells' => [
            ['label' => '設立日', 'value' => $facility->established_date, 'type' => 'date'],
            ['label' => '従業員数', 'value' => $facility->employee_count, 'type' => 'number'],
        ]
    ],
    [
        'type' => 'single',
        'cells' => [
            ['label' => '備考', 'value' => $facility->notes, 'type' => 'text', 'colspan' => 3],
        ]
    ]
];
```

### 土地情報表示用データ（複雑な例）

```php
$landInfoData = [
    // 管理会社情報（グループ化）
    [
        'type' => 'grouped',
        'cells' => [
            ['label' => '管理会社', 'value' => $landInfo->management_company, 'type' => 'text', 'rowspan' => 3],
            ['label' => '担当者名', 'value' => $landInfo->manager_name, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '担当者電話', 'value' => $landInfo->manager_phone, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '担当者メール', 'value' => $landInfo->manager_email, 'type' => 'email'],
        ]
    ],
    
    // オーナー情報（グループ化）
    [
        'type' => 'grouped',
        'cells' => [
            ['label' => 'オーナー', 'value' => $landInfo->owner_name, 'type' => 'text', 'rowspan' => 2],
            ['label' => 'オーナー電話', 'value' => $landInfo->owner_phone, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => 'オーナーメール', 'value' => $landInfo->owner_email, 'type' => 'email'],
        ]
    ],
    
    // 契約情報
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
    ],
    
    // 添付ファイル
    [
        'type' => 'single',
        'cells' => [
            ['label' => '契約書', 'value' => $landInfo->contract_file_path, 'type' => 'file', 'colspan' => 3],
        ]
    ]
];
```

### ライフライン設備表示用データ

```php
$lifelineEquipmentData = [
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '設備名', 'value' => $equipment->equipment_name, 'type' => 'text'],
            ['label' => '設備タイプ', 'value' => $equipment->equipment_type, 'type' => 'badge'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '製造メーカー', 'value' => $equipment->manufacturer, 'type' => 'text'],
            ['label' => '型番', 'value' => $equipment->model_number, 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '設置日', 'value' => $equipment->installation_date, 'type' => 'date'],
            ['label' => '保証期限', 'value' => $equipment->warranty_expiry, 'type' => 'date'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '購入価格', 'value' => $equipment->purchase_price, 'type' => 'currency'],
            ['label' => 'ステータス', 'value' => $equipment->status, 'type' => 'badge'],
        ]
    ],
    [
        'type' => 'single',
        'cells' => [
            ['label' => '仕様書', 'value' => $equipment->specification_file, 'type' => 'file', 'colspan' => 3],
        ]
    ]
];
```

## 空値の処理

### 空値として認識される値

```php
// これらの値は「未設定」として表示される
null
''
[]
0 (数値の場合は除く)
false (boolean型の場合は除く)
```

### 空値の表示例

```php
[
    'type' => 'standard',
    'cells' => [
        ['label' => '会社名', 'value' => null, 'type' => 'text'],           // → 未設定
        ['label' => '住所', 'value' => '', 'type' => 'text'],               // → 未設定
        ['label' => 'タグ', 'value' => [], 'type' => 'text'],               // → 未設定
        ['label' => '従業員数', 'value' => 0, 'type' => 'number'],          // → 0（数値は表示）
        ['label' => '公開フラグ', 'value' => false, 'type' => 'text'],      // → false（boolean は表示）
    ]
]
```

## 高度な使用例

### カスタム属性の追加

```php
[
    'label' => 'カスタムフィールド',
    'value' => 'カスタム値',
    'type' => 'text',
    'class' => 'custom-cell-class',
    'attributes' => [
        'data-toggle' => 'tooltip',
        'title' => 'ツールチップテキスト',
        'id' => 'custom-cell-id'
    ]
]
```

### 条件付きスタイリング

```php
[
    'label' => 'ステータス',
    'value' => $facility->status,
    'type' => 'badge',
    'class' => $facility->status === 'active' ? 'text-success' : 'text-danger'
]
```

### 動的データ生成

```php
public function buildTableData($facility)
{
    $data = [];
    
    // 基本情報行
    $data[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
            ['label' => 'コード', 'value' => $facility->office_code, 'type' => 'badge'],
        ]
    ];
    
    // 条件付きで連絡先情報を追加
    if ($facility->email || $facility->website_url) {
        $contactCells = [];
        
        if ($facility->email) {
            $contactCells[] = ['label' => 'メール', 'value' => $facility->email, 'type' => 'email'];
        }
        
        if ($facility->website_url) {
            $contactCells[] = ['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'];
        }
        
        $data[] = [
            'type' => 'standard',
            'cells' => $contactCells
        ];
    }
    
    return $data;
}
```

## バリデーションルール

### 必須フィールド

```php
// 各行に必要な必須フィールド
[
    'type' => 'required|string|in:standard,grouped,single',
    'cells' => 'required|array|min:1'
]

// 各セルに必要な必須フィールド
[
    'label' => 'nullable|string',
    'value' => 'nullable',
    'type' => 'required|string|in:text,badge,email,url,date,currency,number,file'
]
```

### データ構造検証の例

```php
public function validateTableData(array $data): bool
{
    foreach ($data as $row) {
        if (!isset($row['type']) || !isset($row['cells'])) {
            return false;
        }
        
        if (!in_array($row['type'], ['standard', 'grouped', 'single'])) {
            return false;
        }
        
        foreach ($row['cells'] as $cell) {
            if (!isset($cell['type'])) {
                return false;
            }
            
            $validTypes = ['text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file'];
            if (!in_array($cell['type'], $validTypes)) {
                return false;
            }
        }
    }
    
    return true;
}
```

## パフォーマンス最適化

### 大量データの処理

```php
// ページネーション対応
$tableData = $this->buildTableData($facilities->take(50));

// 遅延読み込み対応
$tableData = $this->buildTableDataLazy($facilities, $page, $perPage);
```

### キャッシュ活用

```php
$cacheKey = "facility_table_data_{$facility->id}_{$facility->updated_at->timestamp}";
$tableData = Cache::remember($cacheKey, 3600, function() use ($facility) {
    return $this->buildTableData($facility);
});
```

## 関連リソース

- [Common Table Component Usage Guide](common-table-component-usage-guide.md)
- [Value Formatter Service Documentation](../services/value-formatter-service.md)
- [Error Handling System](error-handling-system.md)