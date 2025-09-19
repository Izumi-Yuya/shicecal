# Common Table Component トラブルシューティングガイド

## 概要

Common Table Componentの使用中に発生する可能性のある問題と解決方法を詳しく説明します。

## よくある問題と解決方法

### 1. 表示関連の問題

#### 1.1 テーブルが表示されない

**症状:** コンポーネントを使用してもテーブルが表示されない

**考えられる原因:**
- データ配列が空または不正な形式
- 必須プロパティが不足
- コンポーネントファイルが見つからない

**解決方法:**

```php
// データ構造を確認
dd($tableData); // データが正しい形式か確認

// 最小限のデータで動作確認
$testData = [
    [
        'type' => 'standard',
        'cells' => [
            ['label' => 'テスト', 'value' => 'テスト値', 'type' => 'text']
        ]
    ]
];
```

```blade
{{-- コンポーネントの存在確認 --}}
@if(View::exists('components.common-table'))
    <x-common-table :data="$testData" />
@else
    <p>Common Table Component が見つかりません</p>
@endif
```

#### 1.2 スタイリングが適用されない

**症状:** テーブルは表示されるが、期待されるスタイルが適用されない

**考えられる原因:**
- CSSファイルが読み込まれていない
- CSSクラス名の不一致
- Bootstrap のバージョン不一致

**解決方法:**

```blade
{{-- CSSファイルの読み込み確認 --}}
@push('styles')
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
@endpush

{{-- カスタムCSSクラスを指定 --}}
<x-common-table 
    :data="$data"
    cardClass="facility-info-card detail-card-improved mb-3"
    tableClass="table table-bordered facility-basic-info-table-clean"
/>
```

```css
/* CSSクラスの存在確認 */
.detail-label {
    background-color: #f8f9fa;
    font-weight: bold;
}

.detail-value {
    background-color: #ffffff;
}

.empty-field {
    color: #6c757d;
    font-style: italic;
}
```

#### 1.3 レスポンシブデザインが機能しない

**症状:** モバイルデバイスでテーブルが正しく表示されない

**解決方法:**

```blade
{{-- レスポンシブ機能を有効にする --}}
<x-common-table 
    :data="$data"
    :responsive="true"
/>

{{-- Bootstrapのレスポンシブクラスを確認 --}}
<div class="table-responsive">
    <x-common-table :data="$data" />
</div>
```

### 2. データ関連の問題

#### 2.1 空値が「未設定」として表示されない

**症状:** null や空文字列が「未設定」ではなく空白で表示される

**考えられる原因:**
- ValueFormatter の isEmpty() メソッドが正しく動作していない
- データタイプの問題

**解決方法:**

```php
// 明示的にnullを設定
['label' => 'ラベル', 'value' => $value ?: null, 'type' => 'text']

// ValueFormatterの動作確認
use App\Services\ValueFormatter;

$result = ValueFormatter::isEmpty(''); // true を返すべき
$result = ValueFormatter::isEmpty(null); // true を返すべき
$result = ValueFormatter::isEmpty([]); // true を返すべき
```

#### 2.2 日付フォーマットが正しく表示されない

**症状:** 日付が「2023年4月1日」形式で表示されない

**解決方法:**

```php
// 正しいセルタイプを指定
['label' => '日付', 'value' => '2023-04-01', 'type' => 'date'], // 'text'ではなく'date'

// 日付形式を確認
$date = '2023-04-01';
$formatted = ValueFormatter::format($date, 'date');
// 結果: 2023年4月1日

// Carbon オブジェクトの場合
['label' => '日付', 'value' => $carbon->format('Y-m-d'), 'type' => 'date']
```

#### 2.3 通貨フォーマットが正しく表示されない

**症状:** 金額が「¥1,000,000」形式で表示されない

**解決方法:**

```php
// 数値データを渡す
['label' => '金額', 'value' => 1000000, 'type' => 'currency'], // 文字列ではなく数値

// フォーマット結果を確認
$amount = 1000000;
$formatted = ValueFormatter::format($amount, 'currency');
// 結果: ¥1,000,000
```

### 3. レイアウト関連の問題

#### 3.1 rowspan が正しく動作しない

**症状:** グループ化された表示で行の結合が正しく表示されない

**解決方法:**

```php
// 正しいグループ化の設定
[
    'type' => 'grouped', // 重要: 'standard'ではなく'grouped'
    'cells' => [
        ['label' => 'グループ', 'value' => '値', 'type' => 'text', 'rowspan' => 2],
        ['label' => 'サブ1', 'value' => '値1', 'type' => 'text'],
    ]
],
[
    'type' => 'standard', // 次の行は'standard'
    'cells' => [
        ['label' => 'サブ2', 'value' => '値2', 'type' => 'text'],
    ]
]
```

#### 3.2 colspan が正しく動作しない

**症状:** 単一行の全幅表示が正しく表示されない

**解決方法:**

```php
// 正しい単一行の設定
[
    'type' => 'single', // 重要: 'standard'ではなく'single'
    'cells' => [
        ['label' => '備考', 'value' => '長いテキスト...', 'type' => 'text', 'colspan' => 3],
    ]
]
```

### 4. パフォーマンス関連の問題

#### 4.1 大量データでの表示が遅い

**症状:** 多くの行を含むテーブルの表示に時間がかかる

**解決方法:**

```php
// データを制限
$limitedData = array_slice($allData, 0, 50);

// キャッシュを活用
$cacheKey = "table_data_{$id}_{$updated_at}";
$tableData = Cache::remember($cacheKey, 3600, function() use ($data) {
    return $this->buildTableData($data);
});

// 遅延読み込みの実装
public function getTableData(Request $request)
{
    $page = $request->get('page', 1);
    $perPage = 20;
    
    $data = $this->buildTableData($facilities->forPage($page, $perPage));
    
    return response()->json($data);
}
```

#### 4.2 メモリ使用量が多い

**症状:** 大量のデータ処理でメモリ不足エラーが発生

**解決方法:**

```php
// 不要なデータを除外
$tableData = $facilities->map(function($facility) {
    return [
        'type' => 'standard',
        'cells' => [
            ['label' => '名前', 'value' => $facility->name, 'type' => 'text'],
            // 必要最小限のデータのみ
        ]
    ];
});

// チャンク処理
$facilities->chunk(100, function($chunk) {
    $tableData = $this->buildTableData($chunk);
    // 処理...
});
```

### 5. JavaScript 関連の問題

#### 5.1 既存のJavaScriptが動作しない

**症状:** コンポーネント移行後、既存のJavaScriptセレクターが動作しない

**解決方法:**

```javascript
// 移行前のセレクター
$('.facility-basic-info-table-clean td.detail-value').each(function() {
    // 処理
});

// 移行後のセレクター（コンポーネントの構造に合わせて調整）
$('[data-component="common-table"] td.detail-value').each(function() {
    // 処理
});

// または、より汎用的なセレクター
$('td.detail-value').each(function() {
    // 処理
});
```

#### 5.2 動的コンテンツの更新ができない

**症状:** JavaScriptでテーブル内容を動的に更新できない

**解決方法:**

```javascript
// AJAX でデータを更新
function updateTableData(facilityId) {
    $.get(`/facilities/${facilityId}/table-data`, function(data) {
        // コンポーネント全体を再レンダリング
        $('#table-container').html(data.html);
    });
}

// または、個別セルの更新
function updateCell(label, value) {
    $(`td.detail-label:contains("${label}")`)
        .next('td.detail-value')
        .text(value);
}
```

### 6. エラーハンドリング関連の問題

#### 6.1 不正なデータでエラーが発生する

**症状:** 不正なデータ構造でアプリケーションエラーが発生

**解決方法:**

```php
// データバリデーション
public function buildTableData($data): array
{
    try {
        $validator = new CommonTableValidator();
        $validator->validate($data);
        
        return $this->processTableData($data);
    } catch (ValidationException $e) {
        Log::error('Table data validation failed: ' . $e->getMessage());
        
        // フォールバック表示
        return [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => 'エラー', 'value' => 'データの読み込みに失敗しました', 'type' => 'text']
                ]
            ]
        ];
    }
}
```

#### 6.2 コンポーネントレンダリングエラー

**症状:** Bladeコンポーネントのレンダリング時にエラーが発生

**解決方法:**

```blade
{{-- エラーハンドリング付きコンポーネント呼び出し --}}
@try
    <x-common-table :data="$tableData" />
@catch(Exception $e)
    <div class="alert alert-warning">
        <p>テーブルの表示中にエラーが発生しました。</p>
        @if(config('app.debug'))
            <small>{{ $e->getMessage() }}</small>
        @endif
    </div>
@endtry
```

## デバッグ方法

### 1. データ構造の確認

```php
// コントローラーでデータをダンプ
public function show($id)
{
    $tableData = $this->buildTableData($id);
    
    // 開発環境でのみダンプ
    if (app()->environment('local')) {
        dd($tableData);
    }
    
    return view('show', compact('tableData'));
}
```

### 2. ビューでのデバッグ

```blade
{{-- 開発環境でのみデータを表示 --}}
@if(config('app.debug'))
    <details>
        <summary>デバッグ情報</summary>
        <pre>{{ json_encode($tableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
@endif

<x-common-table :data="$tableData" />
```

### 3. ログを使用したデバッグ

```php
// ValueFormatter でのデバッグ
public static function format($value, string $type, array $options = []): string
{
    Log::debug('ValueFormatter::format', [
        'value' => $value,
        'type' => $type,
        'options' => $options
    ]);
    
    $result = match($type) {
        'text' => self::formatText($value),
        'date' => self::formatDate($value),
        // ...
    };
    
    Log::debug('ValueFormatter result', ['result' => $result]);
    
    return $result;
}
```

### 4. ブラウザ開発者ツールでの確認

```html
<!-- HTML構造の確認 -->
<table class="table table-bordered facility-basic-info-table-clean">
    <tr>
        <td class="detail-label">ラベル</td>
        <td class="detail-value">値</td>
    </tr>
</table>
```

```css
/* CSSの適用状況確認 */
.detail-label {
    background-color: #f8f9fa; /* 適用されているか確認 */
}
```

## パフォーマンス最適化

### 1. キャッシュ戦略

```php
// 静的データのキャッシュ
public function getCachedTableData($facilityId): array
{
    return Cache::tags(['facility', $facilityId])
        ->remember("table_data_{$facilityId}", 3600, function() use ($facilityId) {
            return $this->buildTableData($facilityId);
        });
}

// キャッシュの無効化
public function updateFacility($facilityId, $data)
{
    // データ更新
    $facility = Facility::find($facilityId);
    $facility->update($data);
    
    // キャッシュクリア
    Cache::tags(['facility', $facilityId])->flush();
}
```

### 2. 遅延読み込み

```php
// ページネーション対応
public function getTableDataPaginated(Request $request)
{
    $page = $request->get('page', 1);
    $perPage = 20;
    
    $facilities = Facility::paginate($perPage);
    $tableData = $this->buildTableData($facilities->items());
    
    return response()->json([
        'data' => $tableData,
        'pagination' => [
            'current_page' => $facilities->currentPage(),
            'last_page' => $facilities->lastPage(),
            'total' => $facilities->total()
        ]
    ]);
}
```

### 3. メモリ最適化

```php
// 大量データの処理
public function processLargeDataset($facilities)
{
    $tableData = [];
    
    foreach ($facilities->cursor() as $facility) {
        $tableData[] = $this->buildSingleRowData($facility);
        
        // メモリ使用量を監視
        if (memory_get_usage() > 128 * 1024 * 1024) { // 128MB
            break;
        }
    }
    
    return $tableData;
}
```

## セキュリティ考慮事項

### 1. XSS対策

```php
// 出力値のエスケープ
public static function formatText($value): string
{
    return e($value); // Laravel のヘルパー関数を使用
}

// HTMLタグの除去
public static function sanitizeValue($value): string
{
    return strip_tags($value);
}
```

### 2. データバリデーション

```php
// 入力データの検証
public function validateTableData(array $data): bool
{
    $validator = Validator::make($data, [
        '*.type' => 'required|in:standard,grouped,single',
        '*.cells' => 'required|array|min:1',
        '*.cells.*.label' => 'nullable|string|max:255',
        '*.cells.*.value' => 'nullable',
        '*.cells.*.type' => 'required|in:text,badge,email,url,date,currency,number,file'
    ]);
    
    return $validator->passes();
}
```

## 関連リソース

### ドキュメント
- [Common Table Component Usage Guide](../components/common-table-component-usage-guide.md)
- [Migration Guide](../migration/common-table-component-migration-guide.md)
- [Data Structure Reference](../components/common-table-data-structure-reference.md)

### テストファイル
- `tests/Feature/Components/CommonTableBasicTest.php`
- `tests/Unit/Services/ValueFormatterTest.php`
- `tests/Browser/CommonTableBrowserTest.php`

### ソースコード
- `resources/views/components/common-table.blade.php`
- `app/Services/ValueFormatter.php`
- `app/Services/CommonTableValidator.php`

## サポート

問題が解決しない場合は、以下の情報を含めて開発チームに報告してください：

1. 発生している問題の詳細な説明
2. 使用しているデータ構造
3. エラーメッセージ（ある場合）
4. 期待される動作と実際の動作
5. 環境情報（Laravel バージョン、PHP バージョンなど）

```php
// 環境情報の取得
$environmentInfo = [
    'laravel_version' => app()->version(),
    'php_version' => PHP_VERSION,
    'environment' => app()->environment(),
    'debug_mode' => config('app.debug'),
];
```