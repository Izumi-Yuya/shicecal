# 簡素化されたアーキテクチャガイド

## 概要

Shise-Cal（シセカル）は2025年に大幅なリファクタリングを実施し、保守性と可読性を向上させるため、プロジェクト構造を簡素化しました。このドキュメントでは、新しいアーキテクチャの詳細を説明します。

## アーキテクチャ原則

### 1. 機能統合による簡素化
- 関連する機能を論理的にグループ化
- 重複コードの排除
- 一貫性のあるAPI設計

### 2. 責任の明確化
- 各コンポーネントの役割を明確に定義
- 単一責任原則の適用
- 疎結合な設計

### 3. 保守性の向上
- 予測可能なファイル配置
- 統一された命名規則
- 包括的なテストカバレッジ

## コントローラー層

### 統合前後の比較

#### 統合前（13個のコントローラー）
```
AuthController
FacilityController
LandInfoController              ← 統合対象
CommentController
FacilityCommentController       ← 統合対象
PdfExportController            ← 統合対象
CsvExportController            ← 統合対象
NotificationController
MyPageController
MaintenanceController
AnnualConfirmationController
ActivityLogController
Controller (Base)
```

#### 統合後（8個のコントローラー）
```
AuthController                 # 認証機能（変更なし）
FacilityController            # 施設管理 + 土地情報管理
CommentController             # コメント機能（統合）
ExportController              # PDF・CSV出力機能（統合）
NotificationController        # 通知機能（変更なし）
MyPageController             # マイページ機能（変更なし）
MaintenanceController        # 修繕履歴管理（変更なし）
AnnualConfirmationController # 年次確認機能（変更なし）
```

### 主要コントローラーの詳細

#### FacilityController
**責任範囲**: 施設の基本情報管理と土地情報管理

**主要メソッド**:
```php
// 基本的なCRUD操作
public function index()                    # 施設一覧
public function show($id)                  # 施設詳細
public function create()                   # 施設作成フォーム
public function store(Request $request)    # 施設作成処理
public function edit($id)                  # 施設編集フォーム
public function update(Request $request, $id) # 施設更新処理
public function destroy($id)               # 施設削除

// 基本情報管理
public function basicInfo($id)             # 基本情報表示
public function editBasicInfo($id)         # 基本情報編集フォーム
public function updateBasicInfo(Request $request, $id) # 基本情報更新

// 土地情報管理（LandInfoControllerから統合）
public function showLandInfo($id)          # 土地情報表示
public function editLandInfo($id)          # 土地情報編集フォーム
public function updateLandInfo(Request $request, $id) # 土地情報更新
public function calculateLandFields(Request $request, $id) # 土地情報計算
public function approveLandInfo(Request $request, $id) # 土地情報承認
public function rejectLandInfo(Request $request, $id)  # 土地情報差戻し

// ドキュメント管理
public function uploadDocuments(Request $request, $id) # 書類アップロード
public function getDocuments($id)          # 書類一覧取得
public function downloadDocument($id, $fileId) # 書類ダウンロード
public function deleteDocument($id, $fileId)   # 書類削除
```

#### ExportController
**責任範囲**: PDF・CSV出力機能の統合管理

**主要メソッド**:
```php
// PDF出力（PdfExportControllerから統合）
public function pdfIndex()                 # PDF出力メニュー
public function generateSinglePdf($facilityId) # 単一施設PDF生成
public function generateSecurePdf($facilityId)  # セキュアPDF生成
public function generateBatchPdf(Request $request) # 一括PDF生成
public function getBatchProgress($batchId)  # 一括処理進捗取得

// CSV出力（CsvExportControllerから統合）
public function csvIndex()                 # CSV出力メニュー
public function getFieldPreview(Request $request) # フィールドプレビュー
public function generateCsv(Request $request)     # CSV生成

// お気に入り機能
public function getFavorites()             # お気に入り一覧
public function saveFavorite(Request $request)    # お気に入り保存
public function loadFavorite($id)          # お気に入り読込
public function updateFavorite(Request $request, $id) # お気に入り更新
public function deleteFavorite($id)        # お気に入り削除
```

#### CommentController
**責任範囲**: コメント機能の統合管理

**主要メソッド**:
```php
// 基本的なCRUD操作
public function index()                    # コメント一覧
public function store(Request $request)    # コメント作成
public function show($id)                  # コメント詳細
public function update(Request $request, $id) # コメント更新
public function destroy($id)               # コメント削除

// ステータス管理（FacilityCommentControllerから統合）
public function myComments()               # 自分のコメント
public function assignedComments()         # 担当コメント
public function statusDashboard()          # ステータスダッシュボード
public function updateStatus(Request $request, $id) # ステータス更新
public function bulkUpdateStatus(Request $request)  # 一括ステータス更新
```

## サービス層

### 統合前後の比較

#### 統合前（8個のサービス）
```
ActivityLogService
FileService                    ← 統合対象
LandInfoService               ← 統合対象
LandCalculationService        ← 統合対象
NotificationService
PerformanceMonitoringService
SecurePdfService              ← 統合対象
BatchPdfService               ← 統合対象
```

#### 統合後（5個のサービス）
```
ActivityLogService            # アクティビティログ管理（変更なし）
FacilityService              # 施設・土地情報のビジネスロジック
NotificationService          # 通知処理（変更なし）
ExportService                # PDF・CSV出力処理
PerformanceMonitoringService # パフォーマンス監視（変更なし）
```

### 主要サービスの詳細

#### FacilityService
**責任範囲**: 施設と土地情報に関するビジネスロジック

**主要メソッド**:
```php
// 施設管理
public function createFacility(array $data)           # 施設作成
public function updateFacility($id, array $data)      # 施設更新
public function deleteFacility($id)                   # 施設削除
public function getFacilityWithPermissions($id, User $user) # 権限付き施設取得

// 土地情報管理（LandInfoServiceから統合）
public function createLandInfo($facilityId, array $data)    # 土地情報作成
public function updateLandInfo($facilityId, array $data)    # 土地情報更新
public function approveLandInfo($facilityId, User $approver) # 土地情報承認
public function rejectLandInfo($facilityId, User $approver, string $reason) # 土地情報差戻し

// 計算処理（LandCalculationServiceから統合）
public function calculateUnitPrice(float $purchasePrice, float $area) # 単価計算
public function calculateContractYears(string $startDate, string $endDate) # 契約年数計算
public function formatCurrency(float $amount)          # 通貨フォーマット
public function formatArea(float $area, string $unit)  # 面積フォーマット
```

#### ExportService
**責任範囲**: PDF・CSV出力とファイル管理

**主要メソッド**:
```php
// PDF生成（SecurePdfService + BatchPdfServiceから統合）
public function generateFacilityPdf($facilityId, array $options = []) # 施設PDF生成
public function generateSecurePdf($facilityId, array $options = [])    # セキュアPDF生成
public function generateBatchPdf(array $facilityIds, array $options = []) # 一括PDF生成
public function getBatchProgress($batchId)             # 一括処理進捗

// CSV生成
public function generateCsv(array $facilityIds, array $fields) # CSV生成
public function getAvailableFields()                   # 利用可能フィールド取得
public function previewFieldData(array $facilityIds, array $fields) # フィールドプレビュー

// ファイル管理（FileServiceから統合）
public function uploadFile($file, $facilityId, string $type) # ファイルアップロード
public function downloadFile($fileId)                  # ファイルダウンロード
public function deleteFile($fileId)                    # ファイル削除
public function getFilesByFacility($facilityId)       # 施設別ファイル取得
```

## フロントエンド構成

### CSS アーキテクチャ

#### 新しいディレクトリ構造
```
resources/css/
├── app.css                 # メインアプリケーションスタイル
├── shared/                 # 共通スタイル
│   ├── variables.css       # CSS カスタムプロパティ
│   ├── base.css           # ベーススタイルとリセット
│   ├── layout.css         # レイアウトとグリッドシステム
│   ├── components.css     # 再利用可能コンポーネント
│   └── utilities.css      # ユーティリティクラス
├── pages/                 # ページ固有スタイル
│   ├── facilities.css     # 施設関連ページ
│   ├── notifications.css  # 通知ページ
│   ├── export.css         # 出力機能
│   ├── comments.css       # コメントシステム
│   ├── maintenance.css    # 修繕ページ
│   └── admin.css          # 管理インターフェース
└── vendor/
    └── overrides.css      # Bootstrap オーバーライド
```

#### 設計原則
- **変数の統一**: CSS カスタムプロパティで色、サイズ、フォントを管理
- **コンポーネント化**: 再利用可能なUIコンポーネントを定義
- **ユーティリティファースト**: 小さなユーティリティクラスで柔軟性を確保
- **レスポンシブ**: モバイルファーストのレスポンシブデザイン

### JavaScript アーキテクチャ

#### 新しいディレクトリ構造
```
resources/js/
├── app.js                 # メインアプリケーションエントリーポイント
├── modules/               # 機能別モジュール
│   ├── facilities.js     # 施設管理機能
│   ├── notifications.js  # 通知処理
│   ├── export.js         # 出力機能
│   ├── comments.js       # コメントシステム
│   ├── maintenance.js    # 修繕機能
│   └── admin.js          # 管理機能
├── shared/               # 共通ユーティリティ
│   ├── utils.js          # 共通ユーティリティ関数
│   ├── api.js            # API通信ヘルパー
│   ├── validation.js     # フォームバリデーションヘルパー
│   └── components.js     # 再利用可能UIコンポーネント
└── vendor/
    └── bootstrap-init.js  # Bootstrap初期化
```

#### ES6 モジュール設計
```javascript
// modules/facilities.js の例
export class FacilityManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // イベントリスナーの設定
    }

    async saveFacility(data) {
        // 施設保存処理
    }
}

export function initializeFacilities() {
    return new FacilityManager();
}
```

## ルート構造

### RESTful ルート設計

#### 統合後のルート構造
```php
// 施設管理（基本情報 + 土地情報）
Route::resource('facilities', FacilityController::class);
Route::prefix('facilities/{facility}')->group(function () {
    // 基本情報
    Route::get('basic-info', [FacilityController::class, 'basicInfo'])->name('facilities.basic-info');
    Route::get('basic-info/edit', [FacilityController::class, 'editBasicInfo'])->name('facilities.basic-info.edit');
    Route::put('basic-info', [FacilityController::class, 'updateBasicInfo'])->name('facilities.basic-info.update');
    
    // 土地情報
    Route::get('land-info', [FacilityController::class, 'showLandInfo'])->name('facilities.land-info');
    Route::get('land-info/edit', [FacilityController::class, 'editLandInfo'])->name('facilities.land-info.edit');
    Route::put('land-info', [FacilityController::class, 'updateLandInfo'])->name('facilities.land-info.update');
    Route::post('land-info/calculate', [FacilityController::class, 'calculateLandFields'])->name('facilities.land-info.calculate');
    Route::post('land-info/approve', [FacilityController::class, 'approveLandInfo'])->name('facilities.land-info.approve');
    Route::post('land-info/reject', [FacilityController::class, 'rejectLandInfo'])->name('facilities.land-info.reject');
    
    // ドキュメント管理
    Route::post('documents', [FacilityController::class, 'uploadDocuments'])->name('facilities.documents.upload');
    Route::get('documents', [FacilityController::class, 'getDocuments'])->name('facilities.documents.index');
    Route::get('documents/{file}', [FacilityController::class, 'downloadDocument'])->name('facilities.documents.download');
    Route::delete('documents/{file}', [FacilityController::class, 'deleteDocument'])->name('facilities.documents.delete');
});

// 出力機能（PDF + CSV）
Route::prefix('export')->name('export.')->group(function () {
    // PDF出力
    Route::get('pdf', [ExportController::class, 'pdfIndex'])->name('pdf.index');
    Route::post('pdf/single/{facility}', [ExportController::class, 'generateSinglePdf'])->name('pdf.single');
    Route::post('pdf/secure/{facility}', [ExportController::class, 'generateSecurePdf'])->name('pdf.secure');
    Route::post('pdf/batch', [ExportController::class, 'generateBatchPdf'])->name('pdf.batch');
    Route::get('pdf/batch/{batch}/progress', [ExportController::class, 'getBatchProgress'])->name('pdf.batch.progress');
    
    // CSV出力
    Route::get('csv', [ExportController::class, 'csvIndex'])->name('csv.index');
    Route::post('csv/preview', [ExportController::class, 'getFieldPreview'])->name('csv.preview');
    Route::post('csv/generate', [ExportController::class, 'generateCsv'])->name('csv.generate');
    
    // お気に入り
    Route::get('favorites', [ExportController::class, 'getFavorites'])->name('favorites.index');
    Route::post('favorites', [ExportController::class, 'saveFavorite'])->name('favorites.store');
    Route::get('favorites/{favorite}', [ExportController::class, 'loadFavorite'])->name('favorites.show');
    Route::put('favorites/{favorite}', [ExportController::class, 'updateFavorite'])->name('favorites.update');
    Route::delete('favorites/{favorite}', [ExportController::class, 'deleteFavorite'])->name('favorites.destroy');
});

// コメント機能（統合）
Route::resource('comments', CommentController::class);
Route::prefix('comments')->name('comments.')->group(function () {
    Route::get('my-comments', [CommentController::class, 'myComments'])->name('my');
    Route::get('assigned', [CommentController::class, 'assignedComments'])->name('assigned');
    Route::get('dashboard', [CommentController::class, 'statusDashboard'])->name('dashboard');
    Route::put('{comment}/status', [CommentController::class, 'updateStatus'])->name('status.update');
    Route::put('bulk-status', [CommentController::class, 'bulkUpdateStatus'])->name('status.bulk');
});
```

## エラーハンドリング

### 統一されたエラーハンドリング

#### コントローラー用トレイト
```php
trait HandlesControllerErrors
{
    protected function handleException(\Exception $e, string $context = '')
    {
        Log::error("Controller Error [{$context}]: " . $e->getMessage(), [
            'exception' => $e,
            'user_id' => auth()->id(),
            'request_url' => request()->url(),
        ]);
        
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました。',
                'error_code' => $this->getErrorCode($e)
            ], 500);
        }
        
        return back()->with('error', 'エラーが発生しました。しばらく時間をおいて再度お試しください。');
    }
    
    protected function getErrorCode(\Exception $e): string
    {
        return match(get_class($e)) {
            ValidationException::class => 'VALIDATION_ERROR',
            AuthorizationException::class => 'AUTHORIZATION_ERROR',
            ModelNotFoundException::class => 'NOT_FOUND',
            default => 'GENERAL_ERROR'
        };
    }
}
```

#### サービス用トレイト
```php
trait HandlesServiceErrors
{
    protected function logError(string $message, array $context = [])
    {
        Log::error($message, array_merge($context, [
            'service' => static::class,
            'user_id' => auth()->id() ?? null,
        ]));
    }
    
    protected function throwServiceException(string $message, int $code = 0)
    {
        $exceptionClass = $this->getServiceExceptionClass();
        throw new $exceptionClass($message, $code);
    }
    
    abstract protected function getServiceExceptionClass(): string;
}
```

## テスト戦略

### 統合されたテスト構造

#### コントローラーテスト
```
tests/Feature/Controllers/
├── FacilityControllerTest.php      # 施設 + 土地情報テスト
├── ExportControllerTest.php        # PDF + CSV出力テスト
├── CommentControllerTest.php       # コメント機能テスト
├── NotificationControllerTest.php  # 通知機能テスト
├── MyPageControllerTest.php        # マイページテスト
├── MaintenanceControllerTest.php   # 修繕履歴テスト
└── AnnualConfirmationControllerTest.php # 年次確認テスト
```

#### サービステスト
```
tests/Unit/Services/
├── FacilityServiceTest.php         # 施設 + 土地計算テスト
├── ExportServiceTest.php           # 出力サービステスト
├── NotificationServiceTest.php     # 通知サービステスト
├── ActivityLogServiceTest.php      # アクティビティログテスト
└── PerformanceMonitoringServiceTest.php # パフォーマンス監視テスト
```

#### フロントエンドテスト
```
tests/Frontend/
├── CssCompilationTest.php          # CSSビルド検証
├── JavaScriptModuleTest.php        # JSモジュール読込テスト
└── AssetIntegrationTest.php        # エンドツーエンドアセットテスト
```

### 共有テストトレイト
```php
trait CreatesTestFacilities
{
    protected function createFacilityWithLandInfo(array $facilityData = [], array $landData = [])
    {
        $facility = Facility::factory()->create($facilityData);
        $landInfo = LandInfo::factory()->for($facility)->create($landData);
        
        return [$facility, $landInfo];
    }
}

trait CreatesTestUsers
{
    protected function createUserWithRole(string $role, array $attributes = [])
    {
        return User::factory()->create(array_merge(['role' => $role], $attributes));
    }
}
```

## パフォーマンス最適化

### アセット最適化
- **CSS/JS分離**: Bladeテンプレートからの完全分離
- **モジュール化**: 必要な機能のみ読み込み
- **キャッシュ戦略**: ブラウザキャッシュとCDN活用
- **圧縮**: Gzip/Brotli圧縮の適用

### データベース最適化
- **インデックス最適化**: クエリパターンに基づく最適化
- **N+1問題対策**: Eager Loadingの適用
- **クエリ最適化**: 不要なクエリの削減

### サーバー最適化
- **OPcache**: PHPオペコードキャッシュ
- **Redis**: セッションとキャッシュストレージ
- **Queue**: 重い処理の非同期化

## セキュリティ考慮事項

### 認証・認可
- **ロールベースアクセス制御**: 細かい権限管理
- **CSRFトークン**: フォーム送信の保護
- **XSS対策**: 出力エスケープの徹底

### データ保護
- **暗号化**: 機密データの暗号化保存
- **アクセスログ**: 全操作の監査証跡
- **ファイルアップロード**: 安全なファイル処理

## 今後の拡張性

### 新機能追加時の指針
1. **既存パターンの踏襲**: 統一されたアーキテクチャパターンの使用
2. **テスト駆動開発**: 新機能は必ずテストから開始
3. **ドキュメント更新**: 機能追加時の文書化

### スケーラビリティ
- **水平スケーリング**: ロードバランサー対応
- **マイクロサービス化**: 将来的な分割可能性を考慮
- **API化**: 外部システム連携の準備

## まとめ

この簡素化されたアーキテクチャにより、以下の利益を実現しました：

1. **保守性の向上**: コントローラー数38%削減、サービス数37%削減
2. **可読性の向上**: 機能別の明確な分離、一貫性のある構造
3. **開発効率の向上**: 予測可能なファイル配置、統一されたパターン
4. **テスト容易性**: 包括的なテストカバレッジ、共有テストユーティリティ
5. **パフォーマンス向上**: 最適化されたアセット読み込み、効率的なクエリ

このアーキテクチャは、今後の機能拡張や保守作業において、一貫性と効率性を提供します。