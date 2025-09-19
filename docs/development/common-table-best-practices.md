# Common Table Component ベストプラクティス

## 概要

Common Table Componentを効果的に使用するためのベストプラクティスとガイドラインを説明します。

## 設計原則

### 1. データとプレゼンテーションの分離

**良い例:**
```php
// コントローラーでデータ構造を定義
class FacilityController extends Controller
{
    public function show(Facility $facility)
    {
        $basicInfoData = $this->buildBasicInfoData($facility);
        return view('facilities.show', compact('facility', 'basicInfoData'));
    }
    
    private function buildBasicInfoData(Facility $facility): array
    {
        return [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
                    ['label' => 'ステータス', 'value' => $facility->status, 'type' => 'badge'],
                ]
            ]
        ];
    }
}
```

**悪い例:**
```blade
{{-- ビューで複雑なロジックを記述 --}}
<x-common-table :data="[
    [
        'type' => 'standard',
        'cells' => [
            ['label' => '会社名', 'value' => $facility->company_name ?? '未設定', 'type' => 'text'],
            ['label' => 'ステータス', 'value' => $facility->status === 'active' ? 'アクティブ' : '非アクティブ', 'type' => 'badge'],
        ]
    ]
]" />
```

### 2. 再利用可能なデータビルダーの作成

```php
// 専用のサービスクラスを作成
class TableDataBuilder
{
    public function buildFacilityBasicInfo(Facility $facility): array
    {
        return [
            $this->buildCompanyInfoRow($facility),
            $this->buildContactInfoRow($facility),
            $this->buildAddressInfoRow($facility),
        ];
    }
    
    private function buildCompanyInfoRow(Facility $facility): array
    {
        return [
            'type' => 'standard',
            'cells' => [
                ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
                ['label' => '事業所コード', 'value' => $facility->office_code, 'type' => 'badge'],
            ]
        ];
    }
    
    private function buildContactInfoRow(Facility $facility): array
    {
        return [
            'type' => 'standard',
            'cells' => [
                ['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email'],
                ['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'],
            ]
        ];
    }
}
```

## データ構造のベストプラクティス

### 1. 適切な行タイプの選択

```php
// 標準的な情報表示
[
    'type' => 'standard',
    'cells' => [
        ['label' => 'ラベル1', 'value' => '値1', 'type' => 'text'],
        ['label' => 'ラベル2', 'value' => '値2', 'type' => 'text'],
    ]
]

// 関連情報のグループ化
[
    'type' => 'grouped',
    'cells' => [
        ['label' => 'グループ名', 'value' => 'グループ値', 'type' => 'text', 'rowspan' => 2],
        ['label' => 'サブ項目1', 'value' => 'サブ値1', 'type' => 'text'],
    ]
],
[
    'type' => 'standard',
    'cells' => [
        ['label' => 'サブ項目2', 'value' => 'サブ値2', 'type' => 'text'],
    ]
]

// 全幅を使用する情報
[
    'type' => 'single',
    'cells' => [
        ['label' => '備考', 'value' => '長いテキスト内容...', 'type' => 'text', 'colspan' => 3],
    ]
]
```

### 2. 適切なセルタイプの選択

```php
// セルタイプの適切な使い分け
$cellExamples = [
    // テキスト情報
    ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
    
    // ステータスやカテゴリ
    ['label' => 'ステータス', 'value' => $facility->status, 'type' => 'badge'],
    
    // 連絡先情報
    ['label' => 'メール', 'value' => $facility->email, 'type' => 'email'],
    ['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'],
    
    // 日付情報
    ['label' => '設立日', 'value' => $facility->established_date, 'type' => 'date'],
    
    // 金額情報
    ['label' => '資本金', 'value' => $facility->capital, 'type' => 'currency'],
    
    // 数値情報
    ['label' => '従業員数', 'value' => $facility->employee_count, 'type' => 'number'],
    
    // ファイル情報
    ['label' => '契約書', 'value' => $facility->contract_file_path, 'type' => 'file'],
];
```

### 3. 条件付きデータの処理

```php
public function buildConditionalData(Facility $facility): array
{
    $data = [];
    
    // 基本情報は常に表示
    $data[] = [
        'type' => 'standard',
        'cells' => [
            ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
            ['label' => 'コード', 'value' => $facility->office_code, 'type' => 'badge'],
        ]
    ];
    
    // 連絡先情報は存在する場合のみ表示
    $contactCells = [];
    if ($facility->email) {
        $contactCells[] = ['label' => 'メール', 'value' => $facility->email, 'type' => 'email'];
    }
    if ($facility->website_url) {
        $contactCells[] = ['label' => 'ウェブサイト', 'value' => $facility->website_url, 'type' => 'url'];
    }
    
    if (!empty($contactCells)) {
        $data[] = [
            'type' => 'standard',
            'cells' => $contactCells
        ];
    }
    
    return $data;
}
```

## パフォーマンス最適化

### 1. キャッシュ戦略

```php
class CachedTableDataBuilder
{
    public function buildFacilityData(Facility $facility): array
    {
        $cacheKey = $this->generateCacheKey($facility);
        
        return Cache::remember($cacheKey, 3600, function() use ($facility) {
            return $this->generateTableData($facility);
        });
    }
    
    private function generateCacheKey(Facility $facility): string
    {
        return sprintf(
            'facility_table_data_%d_%s',
            $facility->id,
            $facility->updated_at->timestamp
        );
    }
    
    public function clearCache(Facility $facility): void
    {
        $cacheKey = $this->generateCacheKey($facility);
        Cache::forget($cacheKey);
    }
}
```

### 2. 遅延読み込み

```php
// 大量データの段階的読み込み
class LazyTableDataBuilder
{
    public function buildPaginatedData($facilities, int $page = 1, int $perPage = 20): array
    {
        $paginatedFacilities = $facilities->forPage($page, $perPage);
        
        return $paginatedFacilities->map(function($facility) {
            return $this->buildSingleFacilityData($facility);
        })->toArray();
    }
    
    public function buildIncrementalData($facilities, int $offset = 0, int $limit = 50): array
    {
        return $facilities->skip($offset)
            ->take($limit)
            ->get()
            ->map(function($facility) {
                return $this->buildSingleFacilityData($facility);
            })
            ->toArray();
    }
}
```

### 3. メモリ効率的な処理

```php
public function buildLargeDataset($facilities): Generator
{
    foreach ($facilities->cursor() as $facility) {
        yield $this->buildSingleFacilityData($facility);
        
        // メモリ使用量の監視
        if (memory_get_usage() > 100 * 1024 * 1024) { // 100MB
            gc_collect_cycles(); // ガベージコレクション実行
        }
    }
}
```

## エラーハンドリング

### 1. データバリデーション

```php
class TableDataValidator
{
    public function validate(array $data): void
    {
        foreach ($data as $index => $row) {
            $this->validateRow($row, $index);
        }
    }
    
    private function validateRow(array $row, int $index): void
    {
        if (!isset($row['type'])) {
            throw new InvalidArgumentException("Row {$index}: 'type' is required");
        }
        
        if (!in_array($row['type'], ['standard', 'grouped', 'single'])) {
            throw new InvalidArgumentException("Row {$index}: Invalid row type '{$row['type']}'");
        }
        
        if (!isset($row['cells']) || !is_array($row['cells'])) {
            throw new InvalidArgumentException("Row {$index}: 'cells' must be an array");
        }
        
        foreach ($row['cells'] as $cellIndex => $cell) {
            $this->validateCell($cell, $index, $cellIndex);
        }
    }
    
    private function validateCell(array $cell, int $rowIndex, int $cellIndex): void
    {
        if (!isset($cell['type'])) {
            throw new InvalidArgumentException("Row {$rowIndex}, Cell {$cellIndex}: 'type' is required");
        }
        
        $validTypes = ['text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file'];
        if (!in_array($cell['type'], $validTypes)) {
            throw new InvalidArgumentException("Row {$rowIndex}, Cell {$cellIndex}: Invalid cell type '{$cell['type']}'");
        }
    }
}
```

### 2. 例外処理

```php
public function buildTableDataSafely($facility): array
{
    try {
        $validator = new TableDataValidator();
        $data = $this->buildTableData($facility);
        $validator->validate($data);
        
        return $data;
    } catch (Exception $e) {
        Log::error('Table data building failed', [
            'facility_id' => $facility->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // フォールバック表示
        return $this->buildFallbackData($facility);
    }
}

private function buildFallbackData($facility): array
{
    return [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => 'ID', 'value' => $facility->id, 'type' => 'text'],
                ['label' => 'エラー', 'value' => 'データの読み込みに失敗しました', 'type' => 'text'],
            ]
        ]
    ];
}
```

## テスト戦略

### 1. 単体テスト

```php
class TableDataBuilderTest extends TestCase
{
    public function test_builds_basic_info_data_correctly()
    {
        $facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
            'email' => 'test@example.com'
        ]);
        
        $builder = new TableDataBuilder();
        $data = $builder->buildFacilityBasicInfo($facility);
        
        $this->assertIsArray($data);
        $this->assertCount(2, $data); // 2行のデータ
        
        // 最初の行の検証
        $firstRow = $data[0];
        $this->assertEquals('standard', $firstRow['type']);
        $this->assertCount(2, $firstRow['cells']);
        
        // セルの内容検証
        $companyCell = $firstRow['cells'][0];
        $this->assertEquals('会社名', $companyCell['label']);
        $this->assertEquals('テスト会社', $companyCell['value']);
        $this->assertEquals('text', $companyCell['type']);
    }
    
    public function test_handles_empty_values_correctly()
    {
        $facility = Facility::factory()->create([
            'company_name' => null,
            'email' => ''
        ]);
        
        $builder = new TableDataBuilder();
        $data = $builder->buildFacilityBasicInfo($facility);
        
        // 空値が正しく処理されることを確認
        $companyCell = $data[0]['cells'][0];
        $this->assertNull($companyCell['value']);
    }
}
```

### 2. 統合テスト

```php
class CommonTableIntegrationTest extends TestCase
{
    public function test_renders_table_correctly()
    {
        $facility = Facility::factory()->create();
        $tableData = (new TableDataBuilder())->buildFacilityBasicInfo($facility);
        
        $view = $this->view('components.common-table', ['data' => $tableData]);
        
        $view->assertSee($facility->company_name);
        $view->assertSee('detail-label');
        $view->assertSee('detail-value');
    }
    
    public function test_handles_large_dataset()
    {
        $facilities = Facility::factory()->count(100)->create();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        foreach ($facilities as $facility) {
            $tableData = (new TableDataBuilder())->buildFacilityBasicInfo($facility);
            $this->assertIsArray($tableData);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // パフォーマンス要件の確認
        $this->assertLessThan(5.0, $endTime - $startTime); // 5秒以内
        $this->assertLessThan(50 * 1024 * 1024, $endMemory - $startMemory); // 50MB以内
    }
}
```

### 3. ブラウザテスト

```php
class CommonTableBrowserTest extends DuskTestCase
{
    public function test_table_displays_correctly_in_browser()
    {
        $facility = Facility::factory()->create();
        
        $this->browse(function (Browser $browser) use ($facility) {
            $browser->visit("/facilities/{$facility->id}")
                    ->assertSee($facility->company_name)
                    ->assertPresent('table.facility-basic-info-table-clean')
                    ->assertPresent('td.detail-label')
                    ->assertPresent('td.detail-value');
        });
    }
    
    public function test_responsive_design_works()
    {
        $facility = Facility::factory()->create();
        
        $this->browse(function (Browser $browser) use ($facility) {
            $browser->visit("/facilities/{$facility->id}")
                    ->resize(375, 667) // iPhone サイズ
                    ->assertPresent('.table-responsive')
                    ->assertVisible('table');
        });
    }
}
```

## セキュリティ考慮事項

### 1. XSS対策

```php
class SecureValueFormatter extends ValueFormatter
{
    public static function format($value, string $type, array $options = []): string
    {
        // 入力値のサニタイゼーション
        $value = self::sanitizeInput($value);
        
        $result = parent::format($value, $type, $options);
        
        // 出力値のエスケープ
        return self::escapeOutput($result, $type);
    }
    
    private static function sanitizeInput($value): mixed
    {
        if (is_string($value)) {
            // HTMLタグの除去
            $value = strip_tags($value);
            // 危険な文字の除去
            $value = preg_replace('/[<>"\']/', '', $value);
        }
        
        return $value;
    }
    
    private static function escapeOutput(string $result, string $type): string
    {
        // URLやファイルリンクは除外
        if (in_array($type, ['url', 'file', 'email'])) {
            return $result;
        }
        
        return e($result);
    }
}
```

### 2. データアクセス制御

```php
class AuthorizedTableDataBuilder
{
    public function buildFacilityData(Facility $facility, User $user): array
    {
        $data = [];
        
        // 基本情報は全ユーザーが閲覧可能
        $data[] = $this->buildBasicInfo($facility);
        
        // 財務情報は管理者のみ
        if ($user->hasRole('admin')) {
            $data[] = $this->buildFinancialInfo($facility);
        }
        
        // 個人情報は関係者のみ
        if ($user->canViewPersonalInfo($facility)) {
            $data[] = $this->buildPersonalInfo($facility);
        }
        
        return $data;
    }
}
```

## 国際化対応

### 1. 多言語対応

```php
class LocalizedTableDataBuilder
{
    public function buildLocalizedData(Facility $facility, string $locale = 'ja'): array
    {
        App::setLocale($locale);
        
        return [
            [
                'type' => 'standard',
                'cells' => [
                    ['label' => __('facility.company_name'), 'value' => $facility->company_name, 'type' => 'text'],
                    ['label' => __('facility.office_code'), 'value' => $facility->office_code, 'type' => 'badge'],
                ]
            ]
        ];
    }
}
```

### 2. 日付・通貨フォーマット

```php
class LocalizedValueFormatter extends ValueFormatter
{
    public static function formatDate($date, string $locale = 'ja'): string
    {
        $carbon = Carbon::parse($date);
        
        return match($locale) {
            'ja' => $carbon->format('Y年m月d日'),
            'en' => $carbon->format('M d, Y'),
            default => $carbon->format('Y-m-d')
        };
    }
    
    public static function formatCurrency($amount, string $locale = 'ja'): string
    {
        return match($locale) {
            'ja' => '¥' . number_format($amount),
            'en' => '$' . number_format($amount, 2),
            default => number_format($amount)
        };
    }
}
```

## 保守性の向上

### 1. 設定の外部化

```php
// config/table.php
return [
    'default_classes' => [
        'card' => 'facility-info-card detail-card-improved mb-3',
        'table' => 'table table-bordered facility-basic-info-table-clean',
    ],
    'cell_types' => [
        'text', 'badge', 'email', 'url', 'date', 'currency', 'number', 'file'
    ],
    'row_types' => [
        'standard', 'grouped', 'single'
    ],
    'cache_ttl' => 3600,
];
```

### 2. イベント駆動アーキテクチャ

```php
// データ更新時のキャッシュクリア
class FacilityObserver
{
    public function updated(Facility $facility)
    {
        event(new FacilityUpdated($facility));
    }
}

class ClearTableDataCache
{
    public function handle(FacilityUpdated $event)
    {
        $cacheKey = "facility_table_data_{$event->facility->id}";
        Cache::forget($cacheKey);
    }
}
```

## 関連ドキュメント

- [Common Table Component Usage Guide](../components/common-table-component-usage-guide.md)
- [Migration Guide](../migration/common-table-component-migration-guide.md)
- [Troubleshooting Guide](../troubleshooting/common-table-troubleshooting-guide.md)
- [Performance Optimization](../performance/common-table-performance-optimization-summary.md)