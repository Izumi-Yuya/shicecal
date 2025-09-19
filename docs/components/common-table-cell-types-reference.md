# Common Table Component セルタイプリファレンス

## 概要

Common Table Componentでサポートされているすべてのセルタイプの詳細な仕様と使用例を説明します。

## サポートされるセルタイプ一覧

| セルタイプ | 説明 | 主な用途 |
|-----------|------|----------|
| `text` | プレーンテキスト | 一般的なテキスト情報 |
| `badge` | バッジ表示 | ステータス、カテゴリ |
| `email` | メールリンク | メールアドレス |
| `url` | URLリンク | ウェブサイト、外部リンク |
| `date` | 日本語日付 | 日付情報 |
| `currency` | 通貨表示 | 金額、価格 |
| `number` | 数値表示 | 数量、カウント |
| `file` | ファイルリンク | ドキュメント、添付ファイル |

## 各セルタイプの詳細

### 1. Text（テキスト）

最も基本的なセルタイプで、プレーンテキストを表示します。

#### 基本的な使用方法

```php
['label' => '会社名', 'value' => '株式会社サンプル', 'type' => 'text']
```

#### 出力例

```html
<td class="detail-value">株式会社サンプル</td>
```

#### 使用例

```php
// 基本情報
['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
['label' => '住所', 'value' => $facility->address, 'type' => 'text'],
['label' => '備考', 'value' => $facility->notes, 'type' => 'text'],

// 長いテキスト
['label' => '詳細説明', 'value' => $facility->description, 'type' => 'text', 'colspan' => 3],
```

#### 空値の処理

```php
['label' => '会社名', 'value' => null, 'type' => 'text']
// 出力: <td class="detail-value empty-field">未設定</td>
```

---

### 2. Badge（バッジ）

ステータスやカテゴリを視覚的に強調して表示します。

#### 基本的な使用方法

```php
['label' => 'ステータス', 'value' => 'アクティブ', 'type' => 'badge']
```

#### 出力例

```html
<td class="detail-value">
    <span class="badge bg-primary">アクティブ</span>
</td>
```

#### 使用例

```php
// ステータス表示
['label' => 'ステータス', 'value' => $facility->status, 'type' => 'badge'],
['label' => '承認状態', 'value' => $facility->approval_status, 'type' => 'badge'],
['label' => 'カテゴリ', 'value' => $facility->category, 'type' => 'badge'],

// 事業所コード
['label' => '事業所コード', 'value' => $facility->office_code, 'type' => 'badge'],
```

#### バッジの色分け

```php
// 条件付きクラス適用
[
    'label' => 'ステータス',
    'value' => $facility->status,
    'type' => 'badge',
    'class' => match($facility->status) {
        'active' => 'bg-success',
        'inactive' => 'bg-secondary',
        'pending' => 'bg-warning',
        'error' => 'bg-danger',
        default => 'bg-primary'
    }
]
```

---

### 3. Email（メールアドレス）

メールアドレスをクリック可能なmailtoリンクとして表示します。

#### 基本的な使用方法

```php
['label' => 'メールアドレス', 'value' => 'contact@example.com', 'type' => 'email']
```

#### 出力例

```html
<td class="detail-value">
    <a href="mailto:contact@example.com">
        <i class="fas fa-envelope"></i> contact@example.com
    </a>
</td>
```

#### 使用例

```php
// 基本的なメールアドレス
['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
['label' => '担当者メール', 'value' => $facility->manager_email, 'type' => 'email'],

// 複数のメールアドレス
['label' => '連絡先', 'value' => $facility->contact_emails, 'type' => 'email'],
```

#### 無効なメールアドレスの処理

```php
// 無効なメールアドレスはテキストとして表示
['label' => 'メール', 'value' => 'invalid-email', 'type' => 'email']
// 出力: invalid-email（リンクなし）
```

---

### 4. URL（ウェブサイト）

URLをクリック可能な外部リンクとして表示します。

#### 基本的な使用方法

```php
['label' => 'ウェブサイト', 'value' => 'https://example.com', 'type' => 'url']
```

#### 出力例

```html
<td class="detail-value">
    <a href="https://example.com" target="_blank" rel="noopener noreferrer">
        <i class="fas fa-external-link-alt"></i> https://example.com
    </a>
</td>
```

#### 使用例

```php
// ウェブサイト
['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'],
['label' => '会社HP', 'value' => $facility->company_website, 'type' => 'url'],

// 内部リンク
['label' => '詳細ページ', 'value' => route('facilities.show', $facility), 'type' => 'url'],
```

#### プロトコルの自動補完

```php
// httpプロトコルが自動で追加される
['label' => 'サイト', 'value' => 'example.com', 'type' => 'url']
// 出力: <a href="http://example.com">
```

---

### 5. Date（日付）

日付を日本語形式で表示します。

#### 基本的な使用方法

```php
['label' => '設立日', 'value' => '2023-04-01', 'type' => 'date']
```

#### 出力例

```html
<td class="detail-value">2023年4月1日</td>
```

#### サポートされる入力形式

```php
// ISO形式
['label' => '日付1', 'value' => '2023-04-01', 'type' => 'date'],

// DateTime オブジェクト
['label' => '日付2', 'value' => $facility->created_at, 'type' => 'date'],

// Carbon オブジェクト
['label' => '日付3', 'value' => Carbon::now(), 'type' => 'date'],

// タイムスタンプ
['label' => '日付4', 'value' => time(), 'type' => 'date'],
```

#### 使用例

```php
// 基本的な日付
['label' => '設立日', 'value' => $facility->established_date, 'type' => 'date'],
['label' => '契約開始日', 'value' => $facility->contract_start_date, 'type' => 'date'],
['label' => '更新日', 'value' => $facility->updated_at, 'type' => 'date'],

// 期限日（条件付きスタイリング）
[
    'label' => '契約終了日',
    'value' => $facility->contract_end_date,
    'type' => 'date',
    'class' => $facility->contract_end_date < now() ? 'text-danger' : 'text-success'
]
```

#### 無効な日付の処理

```php
['label' => '日付', 'value' => 'invalid-date', 'type' => 'date']
// 出力: invalid-date（そのまま表示）
```

---

### 6. Currency（通貨）

金額を日本円形式で表示します。

#### 基本的な使用方法

```php
['label' => '資本金', 'value' => 10000000, 'type' => 'currency']
```

#### 出力例

```html
<td class="detail-value">¥10,000,000</td>
```

#### 使用例

```php
// 基本的な金額
['label' => '資本金', 'value' => $facility->capital, 'type' => 'currency'],
['label' => '月額賃料', 'value' => $facility->monthly_rent, 'type' => 'currency'],
['label' => '敷金', 'value' => $facility->security_deposit, 'type' => 'currency'],

// 小数点を含む金額
['label' => '単価', 'value' => 1234.56, 'type' => 'currency'],
// 出力: ¥1,235（四捨五入）

// 負の金額
['label' => '損失', 'value' => -50000, 'type' => 'currency'],
// 出力: -¥50,000
```

#### ゼロ値の処理

```php
['label' => '金額', 'value' => 0, 'type' => 'currency']
// 出力: ¥0（「未設定」ではない）
```

---

### 7. Number（数値）

数値を区切り文字付きで表示します。

#### 基本的な使用方法

```php
['label' => '従業員数', 'value' => 150, 'type' => 'number']
```

#### 出力例

```html
<td class="detail-value">150</td>
```

#### 使用例

```php
// 基本的な数値
['label' => '従業員数', 'value' => $facility->employee_count, 'type' => 'number'],
['label' => '面積', 'value' => $facility->floor_area, 'type' => 'number'],

// 大きな数値（区切り文字付き）
['label' => '総面積', 'value' => 1234567, 'type' => 'number'],
// 出力: 1,234,567

// 小数点を含む数値
['label' => '平均値', 'value' => 123.45, 'type' => 'number'],
// 出力: 123.45
```

#### ゼロ値の処理

```php
['label' => '数量', 'value' => 0, 'type' => 'number']
// 出力: 0（「未設定」ではない）
```

---

### 8. File（ファイルリンク）

ファイルへのリンクをアイコン付きで表示します。

#### 基本的な使用方法

```php
['label' => '契約書', 'value' => '/storage/contracts/contract.pdf', 'type' => 'file']
```

#### 出力例

```html
<td class="detail-value">
    <a href="/storage/contracts/contract.pdf" target="_blank">
        <i class="fas fa-file-pdf"></i> contract.pdf
    </a>
</td>
```

#### ファイルタイプ別アイコン

```php
// PDF ファイル
['label' => '契約書', 'value' => '/storage/contract.pdf', 'type' => 'file'],
// アイコン: fa-file-pdf

// Excel ファイル
['label' => '資料', 'value' => '/storage/data.xlsx', 'type' => 'file'],
// アイコン: fa-file-excel

// Word ファイル
['label' => '報告書', 'value' => '/storage/report.docx', 'type' => 'file'],
// アイコン: fa-file-word

// 画像ファイル
['label' => '写真', 'value' => '/storage/photo.jpg', 'type' => 'file'],
// アイコン: fa-file-image

// その他のファイル
['label' => 'その他', 'value' => '/storage/file.txt', 'type' => 'file'],
// アイコン: fa-file
```

#### 使用例

```php
// 基本的なファイルリンク
['label' => '契約書', 'value' => $facility->contract_file_path, 'type' => 'file'],
['label' => '図面', 'value' => $facility->blueprint_file_path, 'type' => 'file'],

// 複数ファイル（配列）
['label' => '添付資料', 'value' => $facility->attachment_files, 'type' => 'file'],

// 条件付きファイル表示
[
    'label' => '契約書',
    'value' => $facility->contract_file_path,
    'type' => 'file',
    'class' => $facility->contract_expired ? 'text-muted' : ''
]
```

#### ファイルが存在しない場合

```php
['label' => 'ファイル', 'value' => '/storage/nonexistent.pdf', 'type' => 'file']
// 出力: リンクは表示されるが、クリック時に404エラー
```

## 高度な使用例

### 条件付きセルタイプ

```php
[
    'label' => '連絡先',
    'value' => $contact,
    'type' => filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'text'
]
```

### カスタムフォーマッター

```php
// ValueFormatterクラスを拡張してカスタムタイプを追加
class CustomValueFormatter extends ValueFormatter
{
    public static function format($value, string $type, array $options = []): string
    {
        return match($type) {
            'phone' => self::formatPhone($value),
            'percentage' => self::formatPercentage($value),
            default => parent::format($value, $type, $options)
        };
    }
    
    private static function formatPhone($value): string
    {
        // 電話番号のフォーマット処理
        return preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $value);
    }
    
    private static function formatPercentage($value): string
    {
        return number_format($value, 1) . '%';
    }
}
```

### 複合セルタイプ

```php
// 複数の情報を組み合わせて表示
[
    'label' => '担当者',
    'value' => $facility->manager_name . ' (' . $facility->manager_email . ')',
    'type' => 'text'
]

// または、カスタムフォーマッターで処理
[
    'label' => '担当者',
    'value' => [
        'name' => $facility->manager_name,
        'email' => $facility->manager_email
    ],
    'type' => 'contact'  // カスタムタイプ
]
```

## エラーハンドリング

### 無効なセルタイプ

```php
['label' => 'テスト', 'value' => 'テスト値', 'type' => 'invalid_type']
// フォールバック: textタイプとして処理
```

### 型不一致

```php
['label' => '日付', 'value' => 'not-a-date', 'type' => 'date']
// フォールバック: 元の値をそのまま表示
```

### null値の処理

```php
['label' => 'ラベル', 'value' => null, 'type' => 'any_type']
// 出力: <td class="detail-value empty-field">未設定</td>
```

## パフォーマンス考慮事項

### 大量データでの最適化

```php
// 重い処理を避ける
['label' => 'ファイル', 'value' => $file_path, 'type' => 'file']
// ファイル存在チェックは行わない（表示時のパフォーマンス優先）

// キャッシュ可能な値を使用
['label' => '日付', 'value' => $cached_formatted_date, 'type' => 'text']
// 事前にフォーマット済みの値を使用
```

### メモリ使用量の最適化

```php
// 大きなオブジェクトを避ける
['label' => '日付', 'value' => $datetime->format('Y-m-d'), 'type' => 'date']
// DateTimeオブジェクトではなく文字列を渡す
```

## 関連ドキュメント

- [Common Table Component Usage Guide](common-table-component-usage-guide.md)
- [Data Structure Reference](common-table-data-structure-reference.md)
- [Value Formatter Service](../services/value-formatter-service.md)