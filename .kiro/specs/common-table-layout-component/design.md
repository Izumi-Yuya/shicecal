# 設計書

## 概要

共通テーブルレイアウトコンポーネントは、Shise-Cal施設管理システム全体で一貫したテーブル表示を提供する再利用可能なBladeコンポーネントです。このコンポーネントは、現在の基本情報、土地情報、建物情報、ライフライン設備の表示カードで使用されている重複コードを統合し、保守性と一貫性を向上させます。

## アーキテクチャ

### コンポーネント構造

```
resources/views/components/
├── common-table.blade.php          # メインテーブルコンポーネント
├── common-table/
│   ├── row.blade.php              # テーブル行コンポーネント
│   ├── cell.blade.php             # テーブルセルコンポーネント
│   └── card-wrapper.blade.php     # カードラッパーコンポーネント
```

### データフロー

1. **親ビュー** → データ配列とオプションを渡す
2. **CommonTableコンポーネント** → データを解析し、行とセルを生成
3. **Rowコンポーネント** → 各行のレイアウトを管理
4. **Cellコンポーネント** → 個別セルの内容とスタイリングを処理
5. **CardWrapperコンポーネント** → カード全体のラッピングとヘッダーを管理

## コンポーネントとインターフェース

### 1. CommonTable メインコンポーネント

#### プロパティ

```php
@props([
    'data' => [],           // テーブルデータ配列
    'title' => null,        // カードタイトル（オプション）
    'cardClass' => 'facility-info-card detail-card-improved mb-3',
    'tableClass' => 'table table-bordered facility-basic-info-table-clean',
    'responsive' => true,   // レスポンシブテーブルの有効/無効
    'cleanBody' => true,    // card-body-cleanクラスの適用
])
```

#### データ構造例

```php
$data = [
    [
        'type' => 'standard',  // 'standard', 'grouped', 'single'
        'cells' => [
            ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
            ['label' => '事業所コード', 'value' => $facility->office_code, 'type' => 'badge'],
        ]
    ],
    [
        'type' => 'single',
        'cells' => [
            ['label' => 'URL', 'value' => $facility->website_url, 'type' => 'url', 'colspan' => 3],
        ]
    ]
];
```

### 2. Row コンポーネント

#### プロパティ

```php
@props([
    'cells' => [],          // セル配列
    'type' => 'standard',   // 'standard', 'grouped', 'single'
])
```

### 3. Cell コンポーネント

#### プロパティ

```php
@props([
    'label' => null,        // ラベルテキスト
    'value' => null,        // 値
    'type' => 'text',       // セルタイプ
    'colspan' => 1,         // カラムスパン
    'rowspan' => 1,         // ローススパン
    'isLabel' => false,     // ラベルセルかどうか
    'isEmpty' => false,     // 空フィールドかどうか
    'class' => '',          // 追加CSSクラス
])
```

#### サポートされるセルタイプ

- `text`: 通常のテキスト
- `badge`: バッジ表示
- `email`: メールリンク
- `url`: URLリンク
- `date`: 日本語日付フォーマット
- `currency`: 通貨フォーマット
- `file`: ファイルリンク
- `number`: 数値フォーマット

### 4. CardWrapper コンポーネント

#### プロパティ

```php
@props([
    'title' => null,        // カードタイトル
    'cardClass' => '',      // カードCSSクラス
    'headerClass' => '',    // ヘッダーCSSクラス
])
```

## データモデル

### テーブルデータ構造

```php
interface TableData {
    array $data;           // 行データの配列
    ?string $title;        // カードタイトル
    array $options;        // 表示オプション
}

interface RowData {
    string $type;          // 行タイプ
    array $cells;          // セルデータの配列
}

interface CellData {
    ?string $label;        // ラベル
    mixed $value;          // 値
    string $type;          // セルタイプ
    int $colspan;          // カラムスパン
    int $rowspan;          // ローススパン
    array $attributes;     // 追加属性
}
```

### 値フォーマッター

```php
class ValueFormatter {
    public static function format($value, string $type, array $options = []): string;
    public static function isEmpty($value): bool;
    public static function formatDate($date, string $format = 'Y年m月d日'): string;
    public static function formatCurrency($amount): string;
    public static function formatNumber($number, int $decimals = 0): string;
}
```

## エラーハンドリング

### バリデーション

1. **データ構造バリデーション**
   - 必須フィールドの存在確認
   - データタイプの検証
   - 配列構造の妥当性チェック

2. **値バリデーション**
   - null/空値の適切な処理
   - 不正なセルタイプの検出
   - colspan/rowspanの範囲チェック

### エラー処理

```php
try {
    // コンポーネントレンダリング
} catch (InvalidDataException $e) {
    // デフォルト表示またはエラーメッセージ
    return view('components.common-table.error', ['message' => $e->getMessage()]);
} catch (Exception $e) {
    // ログ記録とフォールバック表示
    Log::error('CommonTable rendering error: ' . $e->getMessage());
    return view('components.common-table.fallback');
}
```

## テスト戦略

### 1. 単体テスト

#### コンポーネントテスト
- 各コンポーネントの独立したレンダリング
- プロパティの正しい処理
- エラー条件での動作

#### フォーマッターテスト
- 各セルタイプの正しいフォーマット
- 空値の処理
- 日本語フォーマットの検証

### 2. 統合テスト

#### ビューテスト
- 完全なテーブルレンダリング
- 既存ビューとの互換性
- CSSクラスの適用確認

#### ブラウザテスト
- レスポンシブデザインの確認
- アクセシビリティ機能の検証
- JavaScript連携の確認

### 3. パフォーマンステスト

#### レンダリング性能
- 大量データでの表示速度
- メモリ使用量の測定
- キャッシュ効果の確認

### テストデータ

```php
// 基本テストケース
$basicTestData = [
    [
        'type' => 'standard',
        'cells' => [
            ['label' => 'テストラベル', 'value' => 'テスト値', 'type' => 'text'],
            ['label' => '空フィールド', 'value' => null, 'type' => 'text'],
        ]
    ]
];

// 複雑なテストケース
$complexTestData = [
    [
        'type' => 'grouped',
        'cells' => [
            ['label' => 'グループラベル', 'value' => '値1', 'type' => 'text', 'rowspan' => 2],
            ['label' => 'サブラベル1', 'value' => 'サブ値1', 'type' => 'text'],
        ]
    ],
    [
        'type' => 'standard',
        'cells' => [
            ['label' => 'サブラベル2', 'value' => 'サブ値2', 'type' => 'badge'],
        ]
    ]
];
```

## 実装考慮事項

### パフォーマンス最適化

1. **ビューキャッシュ**
   - 静的データのキャッシュ
   - 部分ビューの最適化

2. **遅延読み込み**
   - 大量データの段階的表示
   - 必要時のみコンポーネント読み込み

### セキュリティ

1. **XSS対策**
   - 全ての出力値のエスケープ
   - HTMLタグの適切な処理

2. **データサニタイゼーション**
   - 入力値の検証
   - 不正なHTMLの除去

### アクセシビリティ

1. **ARIA属性**
   - 適切なrole属性の設定
   - スクリーンリーダー対応

2. **キーボードナビゲーション**
   - フォーカス管理
   - タブオーダーの最適化

### 国際化対応

1. **日本語フォーマット**
   - 日付の日本語表示
   - 数値の日本語区切り文字

2. **多言語対応準備**
   - 言語ファイルの活用
   - 動的言語切り替え対応