# Common Table Component 移行ガイド

## 概要

このガイドでは、既存のテーブル表示コードをCommon Table Componentに移行する手順を詳しく説明します。段階的な移行アプローチにより、既存の機能を壊すことなく新しいコンポーネントに移行できます。

## 移行前の準備

### 1. 現在のコード構造の確認

移行前に、現在のテーブル表示コードの構造を確認してください：

```blade
{{-- 移行前の例（基本情報表示カード） --}}
<div class="facility-info-card detail-card-improved mb-3">
    <div class="card-header">
        <h5 class="mb-0">基本情報</h5>
    </div>
    <div class="card-body card-body-clean">
        <table class="table table-bordered facility-basic-info-table-clean">
            <tr>
                <td class="detail-label">会社名</td>
                <td class="detail-value">{{ $facility->company_name ?? '未設定' }}</td>
                <td class="detail-label">事業所コード</td>
                <td class="detail-value">
                    @if($facility->office_code)
                        <span class="badge bg-primary">{{ $facility->office_code }}</span>
                    @else
                        <span class="empty-field">未設定</span>
                    @endif
                </td>
            </tr>
            <!-- 追加の行... -->
        </table>
    </div>
</div>
```

### 2. 依存関係の確認

以下のファイルが存在することを確認してください：

- `resources/views/components/common-table.blade.php`
- `resources/views/components/common-table/row.blade.php`
- `resources/views/components/common-table/cell.blade.php`
- `resources/views/components/common-table/card-wrapper.blade.php`
- `app/Services/ValueFormatter.php`

## 段階的移行手順

### ステップ1: データ構造の変換

#### 1.1 コントローラーでのデータ準備

```php
// 移行前
public function show(Facility $facility)
{
    return view('facilities.show', compact('facility'));
}

// 移行後
public function show(Facility $facility)
{
    $basicInfoData = $this->buildBasicInfoTableData($facility);
    
    return view('facilities.show', compact('facility', 'basicInfoData'));
}

private function buildBasicInfoTableData(Facility $facility): array
{
    return [
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
        ]
    ];
}
```

#### 1.2 ビューファイルの更新

```blade
{{-- 移行前 --}}
<div class="facility-info-card detail-card-improved mb-3">
    <div class="card-header">
        <h5 class="mb-0">基本情報</h5>
    </div>
    <div class="card-body card-body-clean">
        <table class="table table-bordered facility-basic-info-table-clean">
            <!-- 複雑なテーブル構造 -->
        </table>
    </div>
</div>

{{-- 移行後 --}}
<x-common-table 
    title="基本情報" 
    :data="$basicInfoData" 
/>
```

### ステップ2: 複雑なレイアウトの移行

#### 2.1 グループ化された情報の移行

```php
// 移行前のコード（土地情報の管理会社セクション）
// 複雑なrowspanを使用したテーブル

// 移行後のデータ構造
private function buildLandInfoTableData(LandInfo $landInfo): array
{
    return [
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
        ]
    ];
}
```

#### 2.2 単一行の全幅表示

```php
// 長いテキストや備考欄の移行
[
    'type' => 'single',
    'cells' => [
        ['label' => '備考', 'value' => $facility->notes, 'type' => 'text', 'colspan' => 3],
    ]
]
```

### ステップ3: 特殊なセルタイプの移行

#### 3.1 バッジ表示の移行

```blade
{{-- 移行前 --}}
<td class="detail-value">
    @if($facility->status === 'active')
        <span class="badge bg-success">アクティブ</span>
    @else
        <span class="badge bg-secondary">非アクティブ</span>
    @endif
</td>

{{-- 移行後（データ構造で処理） --}}
```

```php
[
    'label' => 'ステータス',
    'value' => $facility->status,
    'type' => 'badge',
    'class' => $facility->status === 'active' ? 'bg-success' : 'bg-secondary'
]
```

#### 3.2 リンク表示の移行

```blade
{{-- 移行前 --}}
<td class="detail-value">
    @if($facility->email)
        <a href="mailto:{{ $facility->email }}">
            <i class="fas fa-envelope"></i> {{ $facility->email }}
        </a>
    @else
        <span class="empty-field">未設定</span>
    @endif
</td>

{{-- 移行後（自動処理） --}}
```

```php
['label' => 'メールアドレス', 'value' => $facility->email, 'type' => 'email']
// 空値は自動的に「未設定」として表示される
```

## 実際の移行例

### 基本情報表示カードの完全移行

#### 移行前のコード

```blade
{{-- resources/views/facilities/basic-info/partials/display-card-original.blade.php --}}
<div class="facility-info-card detail-card-improved mb-3">
    <div class="card-header">
        <h5 class="mb-0">基本情報</h5>
    </div>
    <div class="card-body card-body-clean">
        <table class="table table-bordered facility-basic-info-table-clean">
            <tr>
                <td class="detail-label">会社名</td>
                <td class="detail-value">{{ $facility->company_name ?? '未設定' }}</td>
                <td class="detail-label">事業所コード</td>
                <td class="detail-value">
                    @if($facility->office_code)
                        <span class="badge bg-primary">{{ $facility->office_code }}</span>
                    @else
                        <span class="empty-field">未設定</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="detail-label">郵便番号</td>
                <td class="detail-value">{{ $facility->postal_code ?? '未設定' }}</td>
                <td class="detail-label">都道府県</td>
                <td class="detail-value">{{ $facility->prefecture ?? '未設定' }}</td>
            </tr>
            <!-- 追加の行... -->
        </table>
    </div>
</div>
```

#### 移行後のコード

```php
// Controller
private function buildBasicInfoTableData(Facility $facility): array
{
    return [
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
        ]
    ];
}
```

```blade
{{-- resources/views/facilities/basic-info/partials/display-card.blade.php --}}
<x-common-table 
    title="基本情報" 
    :data="$basicInfoData" 
/>
```

### 土地情報表示カードの移行

#### 移行前の複雑なテーブル構造

```blade
<table class="table table-bordered">
    <tr>
        <td class="detail-label" rowspan="3">管理会社</td>
        <td class="detail-label">会社名</td>
        <td class="detail-value">{{ $landInfo->management_company ?? '未設定' }}</td>
    </tr>
    <tr>
        <td class="detail-label">担当者</td>
        <td class="detail-value">{{ $landInfo->manager_name ?? '未設定' }}</td>
    </tr>
    <tr>
        <td class="detail-label">電話番号</td>
        <td class="detail-value">{{ $landInfo->manager_phone ?? '未設定' }}</td>
    </tr>
</table>
```

#### 移行後のデータ構造

```php
private function buildLandInfoTableData(LandInfo $landInfo): array
{
    return [
        [
            'type' => 'grouped',
            'cells' => [
                ['label' => '管理会社', 'value' => $landInfo->management_company, 'type' => 'text', 'rowspan' => 3],
                ['label' => '会社名', 'value' => $landInfo->management_company, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '担当者', 'value' => $landInfo->manager_name, 'type' => 'text'],
            ]
        ],
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '電話番号', 'value' => $landInfo->manager_phone, 'type' => 'text'],
            ]
        ]
    ];
}
```

## 移行チェックリスト

### 移行前の確認事項

- [ ] 現在のテーブル構造を文書化
- [ ] 使用されているCSSクラスを確認
- [ ] JavaScript依存関係を確認
- [ ] 特殊な表示ロジックを特定
- [ ] テストケースを準備

### 移行中の作業

- [ ] データ構造変換メソッドを作成
- [ ] 新しいビューファイルを作成
- [ ] 既存のビューファイルをバックアップ
- [ ] 段階的に置き換え
- [ ] 各段階でテストを実行

### 移行後の確認事項

- [ ] 表示内容が同一であることを確認
- [ ] CSSスタイリングが正しく適用されることを確認
- [ ] レスポンシブデザインが機能することを確認
- [ ] アクセシビリティ機能が維持されることを確認
- [ ] パフォーマンスが改善されることを確認

## トラブルシューティング

### よくある問題と解決方法

#### 1. 表示が崩れる

**問題:** 移行後にテーブルの表示が崩れる

**原因:** CSSクラスの不一致またはデータ構造の問題

**解決方法:**
```php
// CSSクラスを明示的に指定
<x-common-table 
    :data="$data"
    cardClass="facility-info-card detail-card-improved mb-3"
    tableClass="table table-bordered facility-basic-info-table-clean"
/>
```

#### 2. 空値が正しく表示されない

**問題:** 空値が「未設定」として表示されない

**原因:** データタイプの問題または値の判定ロジック

**解決方法:**
```php
// 明示的にnullまたは空文字列を設定
['label' => 'ラベル', 'value' => $value ?: null, 'type' => 'text']
```

#### 3. 特殊なフォーマットが適用されない

**問題:** 日付や通貨のフォーマットが正しく表示されない

**原因:** セルタイプの指定ミス

**解決方法:**
```php
// 正しいセルタイプを指定
['label' => '日付', 'value' => $date, 'type' => 'date'],  // 'text'ではなく'date'
['label' => '金額', 'value' => $amount, 'type' => 'currency'],  // 'number'ではなく'currency'
```

#### 4. rowspanが正しく動作しない

**問題:** グループ化された表示が正しく表示されない

**原因:** 行タイプまたはrowspan設定の問題

**解決方法:**
```php
// 正しいグループ化の設定
[
    'type' => 'grouped',  // 'standard'ではなく'grouped'
    'cells' => [
        ['label' => 'グループ', 'value' => '値', 'type' => 'text', 'rowspan' => 2],
        ['label' => 'サブ1', 'value' => '値1', 'type' => 'text'],
    ]
],
[
    'type' => 'standard',  // 次の行は'standard'
    'cells' => [
        ['label' => 'サブ2', 'value' => '値2', 'type' => 'text'],
    ]
]
```

#### 5. JavaScriptセレクターが動作しない

**問題:** 既存のJavaScriptが動作しなくなる

**原因:** HTML構造の変更によるセレクターの不一致

**解決方法:**
```javascript
// 移行前
$('.facility-basic-info-table-clean td.detail-value').each(function() {
    // 処理
});

// 移行後（コンポーネントの構造に合わせて調整）
$('[data-component="common-table"] td.detail-value').each(function() {
    // 処理
});
```

### デバッグ方法

#### 1. データ構造の確認

```php
// コントローラーでデータ構造をダンプ
$tableData = $this->buildTableData($facility);
dd($tableData);
```

#### 2. レンダリング結果の確認

```blade
{{-- ビューでデータを確認 --}}
@dump($tableData)
<x-common-table :data="$tableData" />
```

#### 3. CSSクラスの確認

```html
<!-- ブラウザの開発者ツールで確認 -->
<td class="detail-value"><!-- 期待されるクラスが適用されているか確認 --></td>
```

## ベストプラクティス

### 1. 段階的移行

```php
// 一度にすべてを移行せず、段階的に進める
public function show(Facility $facility)
{
    $basicInfoData = $this->buildBasicInfoTableData($facility);
    
    return view('facilities.show', compact('facility', 'basicInfoData'));
}

// 最初は一つのセクションから始める
private function buildBasicInfoTableData(Facility $facility): array
{
    // 基本情報のみから開始
    return [
        [
            'type' => 'standard',
            'cells' => [
                ['label' => '会社名', 'value' => $facility->company_name, 'type' => 'text'],
            ]
        ]
    ];
}
```

### 2. データ変換の分離

```php
// データ変換ロジックを専用のサービスクラスに分離
class FacilityTableDataBuilder
{
    public function buildBasicInfo(Facility $facility): array
    {
        return [
            // データ構造の定義
        ];
    }
    
    public function buildLandInfo(LandInfo $landInfo): array
    {
        return [
            // データ構造の定義
        ];
    }
}
```

### 3. テストの充実

```php
// 移行前後の表示内容を比較するテスト
class CommonTableMigrationTest extends TestCase
{
    public function test_basic_info_display_matches_original()
    {
        $facility = Facility::factory()->create();
        
        // 移行前の表示内容を取得
        $originalView = view('facilities.basic-info.partials.display-card-original', compact('facility'))->render();
        
        // 移行後の表示内容を取得
        $basicInfoData = (new FacilityTableDataBuilder())->buildBasicInfo($facility);
        $newView = view('components.common-table', ['data' => $basicInfoData])->render();
        
        // 重要な要素が同じように表示されることを確認
        $this->assertStringContainsString($facility->company_name, $originalView);
        $this->assertStringContainsString($facility->company_name, $newView);
    }
}
```

### 4. パフォーマンス最適化

```php
// 大量データの場合はキャッシュを活用
public function buildTableData(Facility $facility): array
{
    return Cache::remember(
        "facility_table_data_{$facility->id}_{$facility->updated_at->timestamp}",
        3600,
        fn() => $this->generateTableData($facility)
    );
}
```

### 5. 後方互換性の維持

```php
// 移行期間中は両方のビューを維持
public function show(Facility $facility)
{
    $useNewComponent = config('app.use_common_table_component', false);
    
    if ($useNewComponent) {
        $basicInfoData = $this->buildBasicInfoTableData($facility);
        return view('facilities.show-new', compact('facility', 'basicInfoData'));
    }
    
    return view('facilities.show', compact('facility'));
}
```

## 移行完了後の作業

### 1. 古いファイルの削除

```bash
# バックアップを取った後、古いビューファイルを削除
mv resources/views/facilities/basic-info/partials/display-card-original.blade.php \
   resources/views/facilities/basic-info/partials/display-card-backup.blade.php
```

### 2. ドキュメントの更新

- 新しいコンポーネントの使用方法を文書化
- 開発チームへの移行完了の通知
- 今後の開発ガイドラインの更新

### 3. パフォーマンス測定

```php
// 移行前後のパフォーマンスを測定
class PerformanceTest extends TestCase
{
    public function test_table_rendering_performance()
    {
        $facility = Facility::factory()->create();
        
        $start = microtime(true);
        $tableData = $this->buildTableData($facility);
        $view = view('components.common-table', ['data' => $tableData])->render();
        $end = microtime(true);
        
        $renderTime = $end - $start;
        $this->assertLessThan(0.1, $renderTime, 'Table rendering should be fast');
    }
}
```

## 関連ドキュメント

- [Common Table Component Usage Guide](../components/common-table-component-usage-guide.md)
- [Data Structure Reference](../components/common-table-data-structure-reference.md)
- [Cell Types Reference](../components/common-table-cell-types-reference.md)
- [Troubleshooting Guide](common-table-troubleshooting-guide.md)