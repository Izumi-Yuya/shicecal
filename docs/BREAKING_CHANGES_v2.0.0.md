# Shise-Cal v2.0.0 破壊的変更一覧

## 概要

このドキュメントでは、Shise-Cal v1.x から v2.0.0 への移行における全ての破壊的変更を詳細に説明します。

## 🚨 重要な注意事項

- **移行期限**: 2025年12月31日
- **レガシーサポート終了**: 2026年3月31日
- **必須移行作業**: フロントエンドAPIコール、依存性注入、テストコード

## 📡 API エンドポイント変更

### 土地情報管理 API

#### 土地情報表示
```http
# 変更前
GET /land-info/{id}
Accept: application/json

# 変更後
GET /facilities/{facility}/land-info
Accept: application/json
```

**影響範囲**: フロントエンドJavaScript、外部API連携
**移行方法**: URLパターンを新形式に更新

#### 土地情報更新
```http
# 変更前
PUT /land-info/{id}
Content-Type: application/json

# 変更後
PUT /facilities/{facility}/land-info
Content-Type: application/json
```

#### 土地情報承認・差戻し
```http
# 変更前
POST /land-info/{id}/approve
POST /land-info/{id}/reject

# 変更後
POST /facilities/{facility}/land-info/approve
POST /facilities/{facility}/land-info/reject
```

#### 土地情報計算
```http
# 変更前
POST /land-info/{id}/calculate

# 変更後
POST /facilities/{facility}/land-info/calculate
```

#### ドキュメント管理
```http
# 変更前
POST /land-info/{id}/documents
GET /land-info/{id}/documents
DELETE /land-info/{id}/documents/{fileId}

# 変更後
POST /facilities/{facility}/land-info/documents
GET /facilities/{facility}/land-info/documents
DELETE /facilities/{facility}/land-info/documents/{fileId}
```

### 出力機能 API

#### PDF出力
```http
# 変更前
POST /pdf/generate/{id}
POST /pdf/secure/{id}
POST /pdf/batch

# 変更後
POST /export/pdf/single/{facility}
POST /export/pdf/secure/{facility}
POST /export/pdf/batch
```

#### CSV出力
```http
# 変更前
POST /csv/export
GET /csv/preview
GET /csv/fields

# 変更後
POST /export/csv/generate
GET /export/csv/preview
GET /export/csv/fields
```

#### お気に入り機能
```http
# 変更前
GET /csv/favorites
POST /csv/favorites
PUT /csv/favorites/{id}
DELETE /csv/favorites/{id}

# 変更後
GET /export/favorites
POST /export/favorites
PUT /export/favorites/{id}
DELETE /export/favorites/{id}
```

### コメント機能 API

#### 基本CRUD
```http
# 変更前
GET /facility-comments
POST /facility-comments
PUT /facility-comments/{id}
DELETE /facility-comments/{id}

# 変更後
GET /comments
POST /comments
PUT /comments/{id}
DELETE /comments/{id}
```

#### ステータス管理
```http
# 変更前
GET /facility-comments/status-dashboard
POST /facility-comments/{id}/assign
POST /facility-comments/bulk-update

# 変更後
GET /comments/dashboard
POST /comments/{id}/assign
POST /comments/bulk-update
```

## 🔧 サービスクラス変更

### 依存性注入の変更

#### 土地情報関連サービス
```php
// 変更前
class SomeController extends Controller
{
    public function __construct(
        private LandInfoService $landInfoService,
        private LandCalculationService $calculationService
    ) {}
    
    public function someMethod()
    {
        $landInfo = $this->landInfoService->create($data);
        $unitPrice = $this->calculationService->calculateUnitPrice($price, $area);
    }
}

// 変更後
class SomeController extends Controller
{
    public function __construct(
        private FacilityService $facilityService
    ) {}
    
    public function someMethod()
    {
        $landInfo = $this->facilityService->createLandInfo($facilityId, $data);
        $unitPrice = $this->facilityService->calculateUnitPrice($price, $area);
    }
}
```

#### 出力関連サービス
```php
// 変更前
class ExportController extends Controller
{
    public function __construct(
        private SecurePdfService $pdfService,
        private BatchPdfService $batchService,
        private FileService $fileService
    ) {}
    
    public function generatePdf($id)
    {
        $pdf = $this->pdfService->generateSecurePdf($id);
        $file = $this->fileService->store($pdf);
    }
}

// 変更後
class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}
    
    public function generatePdf($id)
    {
        $pdf = $this->exportService->generateSecurePdf($id);
        // ファイル保存も ExportService 内で処理
    }
}
```

### メソッド名・シグネチャ変更

#### FacilityService（旧 LandInfoService + LandCalculationService）

```php
// 変更前
$landInfoService = app(LandInfoService::class);
$calculationService = app(LandCalculationService::class);

// 土地情報CRUD
$landInfo = $landInfoService->create($data);
$landInfo = $landInfoService->update($id, $data);
$landInfo = $landInfoService->find($id);
$landInfoService->delete($id);

// 承認・差戻し
$landInfoService->approve($id, $approverId);
$landInfoService->reject($id, $approverId, $reason);

// 計算機能
$unitPrice = $calculationService->calculateUnitPrice($price, $area);
$years = $calculationService->calculateContractYears($startDate, $endDate);
$formatted = $calculationService->formatCurrency($amount);

// 変更後
$facilityService = app(FacilityService::class);

// 土地情報CRUD（facilityId が必須パラメータに）
$landInfo = $facilityService->createLandInfo($facilityId, $data);
$landInfo = $facilityService->updateLandInfo($facilityId, $data);
$landInfo = $facilityService->getLandInfo($facilityId);
$facilityService->deleteLandInfo($facilityId);

// 承認・差戻し（User オブジェクトが必須に）
$facilityService->approveLandInfo($facilityId, $approverUser);
$facilityService->rejectLandInfo($facilityId, $approverUser, $reason);

// 計算機能（メソッド名変更なし、統合のみ）
$unitPrice = $facilityService->calculateUnitPrice($price, $area);
$years = $facilityService->calculateContractYears($startDate, $endDate);
$formatted = $facilityService->formatCurrency($amount);
```

#### ExportService（旧 SecurePdfService + BatchPdfService + FileService）

```php
// 変更前
$pdfService = app(SecurePdfService::class);
$batchService = app(BatchPdfService::class);
$fileService = app(FileService::class);

// PDF生成
$pdf = $pdfService->generateSecurePdf($facilityId, $options);
$batch = $batchService->generateBatch($facilityIds, $options);
$progress = $batchService->getProgress($batchId);

// ファイル管理
$file = $fileService->upload($uploadedFile, $facilityId);
$fileService->download($fileId);
$fileService->delete($fileId);

// 変更後
$exportService = app(ExportService::class);

// PDF生成（メソッド名統一）
$pdf = $exportService->generateSecurePdf($facilityId, $options);
$batch = $exportService->generateBatchPdf($facilityIds, $options);
$progress = $exportService->getBatchProgress($batchId);

// ファイル管理（type パラメータ追加）
$file = $exportService->uploadFile($uploadedFile, $facilityId, 'document');
$exportService->downloadFile($fileId);
$exportService->deleteFile($fileId);

// CSV生成（新機能）
$csv = $exportService->generateCsv($facilityIds, $fields);
$fields = $exportService->getAvailableFields();
```

## 🎨 フロントエンド変更

### CSS 読み込み方法

#### Blade テンプレート内のインラインスタイル
```blade
<!-- 変更前 -->
@push('styles')
<style>
.facility-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.land-info-section {
    background: #f8f9fa;
    padding: 1.5rem;
}

.export-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
}
</style>
@endpush

<!-- 変更後 -->
@vite(['resources/css/pages/facilities.css'])
```

#### 対応するCSSファイル作成
```css
/* resources/css/pages/facilities.css */
.facility-card {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.land-info-section {
    background: var(--bg-light);
    padding: var(--spacing-lg);
}

.export-button {
    background: var(--gradient-primary);
    color: var(--text-white);
    border: none;
    padding: var(--spacing-sm) var(--spacing-lg);
    border-radius: var(--border-radius-sm);
}
```

### JavaScript 読み込み方法

#### Blade テンプレート内のインラインスクリプト
```blade
<!-- 変更前 -->
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 施設管理機能
    const facilityForm = document.getElementById('facility-form');
    if (facilityForm) {
        facilityForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            fetch('/facilities', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/facilities/' + data.facility.id;
                } else {
                    alert('エラーが発生しました');
                }
            });
        });
    }
    
    // 土地情報計算
    const calculateButton = document.getElementById('calculate-land-info');
    if (calculateButton) {
        calculateButton.addEventListener('click', function() {
            const price = document.getElementById('purchase_price').value;
            const area = document.getElementById('land_area').value;
            
            if (price && area) {
                const unitPrice = parseFloat(price) / parseFloat(area);
                document.getElementById('unit_price').value = Math.round(unitPrice);
            }
        });
    }
});
</script>
@endpush

<!-- 変更後 -->
@vite(['resources/js/modules/facilities.js'])
```

#### 対応するJavaScriptモジュール作成
```javascript
// resources/js/modules/facilities.js
import { api } from '../shared/api.js';
import { validation } from '../shared/validation.js';
import { utils } from '../shared/utils.js';

export class FacilityModule {
    constructor() {
        this.initializeForms();
        this.initializeCalculations();
    }
    
    initializeForms() {
        const facilityForm = document.getElementById('facility-form');
        if (facilityForm) {
            facilityForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }
    }
    
    async handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await api.post('/facilities', formData);
            if (response.success) {
                utils.redirect(`/facilities/${response.facility.id}`);
            } else {
                utils.showError('エラーが発生しました');
            }
        } catch (error) {
            utils.showError('通信エラーが発生しました');
        }
    }
    
    initializeCalculations() {
        const calculateButton = document.getElementById('calculate-land-info');
        if (calculateButton) {
            calculateButton.addEventListener('click', this.calculateLandInfo.bind(this));
        }
    }
    
    calculateLandInfo() {
        const price = utils.getNumericValue('purchase_price');
        const area = utils.getNumericValue('land_area');
        
        if (validation.isValidNumber(price) && validation.isValidNumber(area)) {
            const unitPrice = Math.round(price / area);
            utils.setValue('unit_price', unitPrice);
        }
    }
}

// モジュール初期化
export function initialize() {
    return new FacilityModule();
}
```

### Vite 設定更新

#### vite.config.js の変更
```javascript
// 変更前（基本設定のみ）
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});

// 変更後（機能別エントリーポイント追加）
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS エントリーポイント
                'resources/css/app.css',
                'resources/css/auth.css',
                'resources/css/admin.css',
                'resources/css/land-info.css',
                'resources/css/pages/facilities.css',
                'resources/css/pages/notifications.css',
                'resources/css/pages/export.css',
                'resources/css/pages/comments.css',
                'resources/css/pages/maintenance.css',
                
                // JavaScript エントリーポイント
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/land-info.js',
                'resources/js/modules/facilities.js',
                'resources/js/modules/notifications.js',
                'resources/js/modules/export.js',
                'resources/js/modules/comments.js',
                'resources/js/modules/maintenance.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'shared': [
                        'resources/js/shared/utils.js',
                        'resources/js/shared/api.js',
                        'resources/js/shared/validation.js',
                    ],
                },
            },
        },
    },
});
```

## 🧪 テストコード変更

### コントローラーテスト

#### LandInfoControllerTest → FacilityControllerTest
```php
// 変更前
class LandInfoControllerTest extends TestCase
{
    public function test_can_show_land_info()
    {
        $landInfo = LandInfo::factory()->create();
        
        $response = $this->get("/land-info/{$landInfo->id}");
        
        $response->assertStatus(200);
    }
    
    public function test_can_update_land_info()
    {
        $landInfo = LandInfo::factory()->create();
        
        $response = $this->put("/land-info/{$landInfo->id}", [
            'purchase_price' => 1000000,
            'land_area' => 100.5,
        ]);
        
        $response->assertRedirect();
    }
}

// 変更後
class FacilityControllerTest extends TestCase
{
    use CreatesTestFacilities; // 新しい共通トレイト
    
    public function test_can_show_land_info()
    {
        [$facility, $landInfo] = $this->createFacilityWithLandInfo();
        
        $response = $this->get("/facilities/{$facility->id}/land-info");
        
        $response->assertStatus(200);
    }
    
    public function test_can_update_land_info()
    {
        [$facility, $landInfo] = $this->createFacilityWithLandInfo();
        
        $response = $this->put("/facilities/{$facility->id}/land-info", [
            'purchase_price' => 1000000,
            'land_area' => 100.5,
        ]);
        
        $response->assertRedirect();
    }
}
```

### サービステスト

#### LandInfoServiceTest + LandCalculationServiceTest → FacilityServiceTest
```php
// 変更前
class LandInfoServiceTest extends TestCase
{
    private LandInfoService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LandInfoService::class);
    }
    
    public function test_can_create_land_info()
    {
        $facility = Facility::factory()->create();
        $data = ['purchase_price' => 1000000, 'land_area' => 100.5];
        
        $landInfo = $this->service->create($data);
        
        $this->assertInstanceOf(LandInfo::class, $landInfo);
    }
}

class LandCalculationServiceTest extends TestCase
{
    private LandCalculationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LandCalculationService::class);
    }
    
    public function test_can_calculate_unit_price()
    {
        $unitPrice = $this->service->calculateUnitPrice(1000000, 100.5);
        
        $this->assertEquals(9950, $unitPrice);
    }
}

// 変更後
class FacilityServiceTest extends TestCase
{
    use CreatesTestFacilities, CreatesTestUsers; // 共通トレイト使用
    
    private FacilityService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FacilityService::class);
    }
    
    public function test_can_create_land_info()
    {
        $facility = Facility::factory()->create();
        $data = ['purchase_price' => 1000000, 'land_area' => 100.5];
        
        $landInfo = $this->service->createLandInfo($facility->id, $data);
        
        $this->assertInstanceOf(LandInfo::class, $landInfo);
        $this->assertEquals($facility->id, $landInfo->facility_id);
    }
    
    public function test_can_calculate_unit_price()
    {
        $unitPrice = $this->service->calculateUnitPrice(1000000, 100.5);
        
        $this->assertEquals(9950, $unitPrice);
    }
    
    public function test_can_approve_land_info()
    {
        [$facility, $landInfo] = $this->createFacilityWithLandInfo();
        $approver = $this->createUserWithRole('approver');
        
        $result = $this->service->approveLandInfo($facility->id, $approver);
        
        $this->assertTrue($result);
        $landInfo->refresh();
        $this->assertEquals('approved', $landInfo->status);
    }
}
```

## 🔄 ルート名変更

### 名前付きルート

#### 土地情報関連
```php
// 変更前
route('land-info.show', $landInfo->id)
route('land-info.edit', $landInfo->id)
route('land-info.update', $landInfo->id)
route('land-info.approve', $landInfo->id)
route('land-info.reject', $landInfo->id)

// 変更後
route('facilities.land-info.show', $facility->id)
route('facilities.land-info.edit', $facility->id)
route('facilities.land-info.update', $facility->id)
route('facilities.land-info.approve', $facility->id)
route('facilities.land-info.reject', $facility->id)
```

#### 出力機能関連
```php
// 変更前
route('pdf.generate', $facility->id)
route('pdf.secure', $facility->id)
route('csv.export')
route('csv.favorites.index')

// 変更後
route('export.pdf.single', $facility->id)
route('export.pdf.secure', $facility->id)
route('export.csv.generate')
route('export.favorites.index')
```

#### コメント機能関連
```php
// 変更前
route('facility-comments.index')
route('facility-comments.store')
route('facility-comments.show', $comment->id)
route('facility-comments.status-dashboard')

// 変更後
route('comments.index')
route('comments.store')
route('comments.show', $comment->id)
route('comments.dashboard')
```

## 📁 ファイル構造変更

### 削除されたファイル

#### コントローラー
```
app/Http/Controllers/
├── LandInfoController.php          ← 削除（FacilityControllerに統合）
├── FacilityCommentController.php   ← 削除（CommentControllerに統合）
├── PdfExportController.php         ← 削除（ExportControllerに統合）
└── CsvExportController.php         ← 削除（ExportControllerに統合）
```

#### サービス
```
app/Services/
├── LandInfoService.php             ← 削除（FacilityServiceに統合）
├── LandCalculationService.php      ← 削除（FacilityServiceに統合）
├── SecurePdfService.php            ← 削除（ExportServiceに統合）
├── BatchPdfService.php             ← 削除（ExportServiceに統合）
└── FileService.php                 ← 削除（ExportServiceに統合）
```

#### テスト
```
tests/Feature/
├── LandInfoControllerTest.php      ← 削除（FacilityControllerTestに統合）
├── PdfExportControllerTest.php     ← 削除（ExportControllerTestに統合）
└── CsvExportControllerTest.php     ← 削除（ExportControllerTestに統合）

tests/Unit/Services/
├── LandInfoServiceTest.php         ← 削除（FacilityServiceTestに統合）
├── LandCalculationServiceTest.php  ← 削除（FacilityServiceTestに統合）
├── SecurePdfServiceTest.php        ← 削除（ExportServiceTestに統合）
└── BatchPdfServiceTest.php         ← 削除（ExportServiceTestに統合）
```

### 新規作成されたファイル

#### 統合コントローラー
```
app/Http/Controllers/
└── ExportController.php            ← 新規（PDF/CSV統合）
```

#### 統合サービス
```
app/Services/
├── FacilityService.php             ← 新規（土地情報+計算統合）
└── ExportService.php               ← 新規（PDF+ファイル統合）
```

#### 共通トレイト
```
app/Http/Traits/
├── HandlesControllerErrors.php     ← 新規
└── HandlesServiceErrors.php        ← 新規

tests/Traits/
├── CreatesTestFacilities.php       ← 新規
└── CreatesTestUsers.php            ← 新規
```

#### フロントエンドアセット
```
resources/css/
├── shared/                         ← 新規ディレクトリ
│   ├── variables.css
│   ├── base.css
│   ├── layout.css
│   ├── components.css
│   └── utilities.css
└── pages/                          ← 新規ディレクトリ
    ├── facilities.css
    ├── notifications.css
    ├── export.css
    ├── comments.css
    ├── maintenance.css
    └── admin.css

resources/js/
├── modules/                        ← 新規ディレクトリ
│   ├── facilities.js
│   ├── notifications.js
│   ├── export.js
│   ├── comments.js
│   ├── maintenance.js
│   └── admin.js
└── shared/                         ← 新規ディレクトリ
    ├── utils.js
    ├── api.js
    ├── validation.js
    ├── components.js
    └── sidebar.js
```

## 🔧 設定ファイル変更

### サービスプロバイダー

#### AppServiceProvider.php
```php
// 変更前
public function register()
{
    $this->app->singleton(LandInfoService::class);
    $this->app->singleton(LandCalculationService::class);
    $this->app->singleton(SecurePdfService::class);
    $this->app->singleton(BatchPdfService::class);
    $this->app->singleton(FileService::class);
}

// 変更後
public function register()
{
    $this->app->singleton(FacilityService::class);
    $this->app->singleton(ExportService::class);
    
    // 後方互換性のためのエイリアス（6ヶ月後削除予定）
    $this->app->alias(FacilityService::class, 'LandInfoService');
    $this->app->alias(FacilityService::class, 'LandCalculationService');
    $this->app->alias(ExportService::class, 'SecurePdfService');
    $this->app->alias(ExportService::class, 'BatchPdfService');
    $this->app->alias(ExportService::class, 'FileService');
}
```

## 📋 移行チェックリスト

### 必須作業（破壊的変更対応）

#### フロントエンド API コール
- [ ] 土地情報 API エンドポイント更新
- [ ] 出力機能 API エンドポイント更新
- [ ] コメント機能 API エンドポイント更新
- [ ] ルート名の更新（`route()` ヘルパー使用箇所）

#### バックエンド依存性注入
- [ ] `LandInfoService` → `FacilityService` 変更
- [ ] `LandCalculationService` → `FacilityService` 変更
- [ ] `SecurePdfService` → `ExportService` 変更
- [ ] `BatchPdfService` → `ExportService` 変更
- [ ] `FileService` → `ExportService` 変更

#### テストコード
- [ ] コントローラーテストのエンドポイント更新
- [ ] サービステストの依存性注入更新
- [ ] テストデータ作成方法の更新

### 推奨作業（品質向上）

#### CSS/JavaScript 分離
- [ ] インラインスタイルの外部ファイル化
- [ ] インラインスクリプトのES6モジュール化
- [ ] Vite設定の更新

#### エラーハンドリング統一
- [ ] `HandlesControllerErrors` トレイト導入
- [ ] `HandlesServiceErrors` トレイト導入

#### テスト改善
- [ ] `CreatesTestFacilities` トレイト導入
- [ ] `CreatesTestUsers` トレイト導入

## ⏰ 移行スケジュール

### Phase 1: 緊急対応（1週間以内）
- [ ] API エンドポイント変更対応
- [ ] 基本機能動作確認

### Phase 2: 完全移行（1ヶ月以内）
- [ ] 依存性注入更新
- [ ] テストコード更新
- [ ] CSS/JavaScript分離

### Phase 3: 品質向上（3ヶ月以内）
- [ ] エラーハンドリング統一
- [ ] 共通トレイト導入
- [ ] パフォーマンス最適化

### Phase 4: レガシー削除（6ヶ月以内）
- [ ] 後方互換性コード削除
- [ ] 非推奨警告の削除
- [ ] 最終クリーンアップ

---

## 📞 サポート

移行作業でご不明な点がございましたら、以下までお問い合わせください：

- **技術サポート**: development-team@company.com
- **移行支援**: migration-support@company.com
- **緊急時対応**: emergency@company.com

詳細な移行手順は [移行チェックリスト](MIGRATION_CHECKLIST.md) をご参照ください。