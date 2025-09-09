# プロジェクト簡素化マイグレーションガイド

## 概要

このドキュメントでは、Shise-Cal（シセカル）のプロジェクト簡素化リファクタリングにおける変更内容と移行手順を説明します。

## 変更概要

### 実施期間
- **開始日**: 2025年1月
- **完了日**: 2025年2月
- **バージョン**: v2.0.0

### 主な変更点
1. **コントローラー統合**: 13個 → 8個（38%削減）
2. **サービス統合**: 8個 → 5個（37%削減）
3. **アセット分離**: CSS/JavaScriptをBladeテンプレートから分離
4. **ルート簡素化**: RESTful設計への統一

## 破壊的変更

### 1. コントローラー統合

#### LandInfoController → FacilityController
**影響**: 土地情報関連のルートとAPIエンドポイント

**変更前**:
```php
// 旧ルート
Route::get('/land-info/{id}', [LandInfoController::class, 'show']);
Route::put('/land-info/{id}', [LandInfoController::class, 'update']);
Route::post('/land-info/{id}/approve', [LandInfoController::class, 'approve']);
Route::post('/land-info/{id}/reject', [LandInfoController::class, 'reject']);
```

**変更後**:
```php
// 新ルート
Route::get('/facilities/{facility}/land-info', [FacilityController::class, 'showLandInfo']);
Route::put('/facilities/{facility}/land-info', [FacilityController::class, 'updateLandInfo']);
Route::post('/facilities/{facility}/land-info/approve', [FacilityController::class, 'approveLandInfo']);
Route::post('/facilities/{facility}/land-info/reject', [FacilityController::class, 'rejectLandInfo']);
```

**移行手順**:
1. フロントエンドのAPIコールを新しいエンドポイントに更新
2. 旧ルートへのリダイレクト設定（互換性維持）
3. 段階的な旧ルート削除

#### PdfExportController + CsvExportController → ExportController
**影響**: 出力機能関連のルートとAPIエンドポイント

**変更前**:
```php
// 旧ルート
Route::post('/pdf/generate/{id}', [PdfExportController::class, 'generate']);
Route::post('/csv/export', [CsvExportController::class, 'export']);
Route::get('/csv/favorites', [CsvExportController::class, 'getFavorites']);
```

**変更後**:
```php
// 新ルート
Route::post('/export/pdf/single/{facility}', [ExportController::class, 'generateSinglePdf']);
Route::post('/export/csv/generate', [ExportController::class, 'generateCsv']);
Route::get('/export/favorites', [ExportController::class, 'getFavorites']);
```

#### FacilityCommentController → CommentController
**影響**: 施設コメント機能

**変更前**:
```php
// 旧ルート
Route::resource('facility-comments', FacilityCommentController::class);
Route::get('/facility-comments/status-dashboard', [FacilityCommentController::class, 'statusDashboard']);
```

**変更後**:
```php
// 新ルート
Route::resource('comments', CommentController::class);
Route::get('/comments/dashboard', [CommentController::class, 'statusDashboard']);
```

### 2. サービス統合

#### LandInfoService + LandCalculationService → FacilityService
**影響**: 土地情報関連のビジネスロジック

**変更前**:
```php
// 旧サービス使用例
$landInfoService = app(LandInfoService::class);
$calculationService = app(LandCalculationService::class);

$landInfo = $landInfoService->create($data);
$unitPrice = $calculationService->calculateUnitPrice($price, $area);
```

**変更後**:
```php
// 新サービス使用例
$facilityService = app(FacilityService::class);

$landInfo = $facilityService->createLandInfo($facilityId, $data);
$unitPrice = $facilityService->calculateUnitPrice($price, $area);
```

#### SecurePdfService + BatchPdfService + FileService → ExportService
**影響**: PDF生成とファイル管理

**変更前**:
```php
// 旧サービス使用例
$pdfService = app(SecurePdfService::class);
$batchService = app(BatchPdfService::class);
$fileService = app(FileService::class);

$pdf = $pdfService->generateSecurePdf($facilityId);
$batch = $batchService->generateBatch($facilityIds);
$file = $fileService->uploadFile($uploadedFile);
```

**変更後**:
```php
// 新サービス使用例
$exportService = app(ExportService::class);

$pdf = $exportService->generateSecurePdf($facilityId);
$batch = $exportService->generateBatchPdf($facilityIds);
$file = $exportService->uploadFile($uploadedFile, $facilityId, 'document');
```

### 3. フロントエンド変更

#### CSS分離
**影響**: Bladeテンプレート内のインラインスタイル

**変更前**:
```blade
@push('styles')
<style>
.facility-card {
    border: 1px solid #ddd;
    padding: 1rem;
}
</style>
@endpush
```

**変更後**:
```blade
@vite(['resources/css/pages/facilities.css'])
```

**移行手順**:
1. インラインスタイルを対応するCSSファイルに移動
2. `@vite`ディレクティブでCSSファイルを読み込み
3. Vite設定でCSSファイルをビルドプロセスに追加

#### JavaScript分離
**影響**: Bladeテンプレート内のインラインスクリプト

**変更前**:
```blade
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 施設管理のJavaScript処理
});
</script>
@endpush
```

**変更後**:
```blade
@vite(['resources/js/modules/facilities.js'])
```

**移行手順**:
1. インラインスクリプトをES6モジュールに変換
2. 機能別にJavaScriptファイルを作成
3. `app.js`でモジュールを初期化

## 非破壊的変更

### 1. 後方互換性の維持

#### ルートエイリアス
旧ルートから新ルートへのリダイレクト設定:

```php
// routes/compatibility.php
Route::redirect('/land-info/{id}', '/facilities/{id}/land-info', 301);
Route::redirect('/pdf/generate/{id}', '/export/pdf/single/{id}', 301);
Route::redirect('/csv/export', '/export/csv/generate', 301);
```

#### サービスエイリアス
旧サービス名での依存性注入サポート:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    // 後方互換性のためのエイリアス
    $this->app->alias(FacilityService::class, 'LandInfoService');
    $this->app->alias(FacilityService::class, 'LandCalculationService');
    $this->app->alias(ExportService::class, 'SecurePdfService');
    $this->app->alias(ExportService::class, 'BatchPdfService');
}
```

### 2. 新機能

#### 共有エラーハンドリング
```php
// app/Http/Traits/HandlesControllerErrors.php
trait HandlesControllerErrors
{
    protected function handleException(\Exception $e, string $context = '')
    {
        // 統一されたエラーハンドリング
    }
}
```

#### 共有テストトレイト
```php
// tests/Traits/CreatesTestFacilities.php
trait CreatesTestFacilities
{
    protected function createFacilityWithLandInfo(array $facilityData = [], array $landData = [])
    {
        // テストデータ作成ヘルパー
    }
}
```

## 移行手順

### Phase 1: 準備作業（1週間）

#### 1.1 バックアップ作成
```bash
# データベースバックアップ
php artisan backup:run

# コードベースバックアップ
git tag v1.9.9-pre-refactor
git push origin v1.9.9-pre-refactor
```

#### 1.2 依存関係更新
```bash
# Composer依存関係更新
composer update

# npm依存関係更新
npm update
```

#### 1.3 テスト環境準備
```bash
# テスト環境セットアップ
cp .env.example .env.testing
php artisan migrate:fresh --seed --env=testing
```

### Phase 2: コントローラー統合（2週間）

#### 2.1 FacilityController統合
```bash
# 1. LandInfoControllerのメソッドをFacilityControllerに移動
# 2. ルート定義更新
# 3. テスト実行
php artisan test tests/Feature/FacilityControllerTest.php
```

#### 2.2 ExportController統合
```bash
# 1. PDF・CSV出力コントローラーを統合
# 2. ルート定義更新
# 3. テスト実行
php artisan test tests/Feature/ExportControllerTest.php
```

#### 2.3 CommentController統合
```bash
# 1. FacilityCommentControllerをCommentControllerに統合
# 2. ルート定義更新
# 3. テスト実行
php artisan test tests/Feature/CommentControllerTest.php
```

### Phase 3: サービス統合（2週間）

#### 3.1 FacilityService作成
```bash
# 1. 新しいFacilityServiceクラス作成
# 2. LandInfoService・LandCalculationServiceの機能を統合
# 3. コントローラーの依存関係更新
# 4. テスト実行
php artisan test tests/Unit/Services/FacilityServiceTest.php
```

#### 3.2 ExportService作成
```bash
# 1. 新しいExportServiceクラス作成
# 2. PDF・ファイル関連サービスの機能を統合
# 3. コントローラーの依存関係更新
# 4. テスト実行
php artisan test tests/Unit/Services/ExportServiceTest.php
```

### Phase 4: フロントエンド分離（2週間）

#### 4.1 CSS分離
```bash
# 1. インラインCSSを機能別ファイルに移動
# 2. 共有CSSアーキテクチャ作成
# 3. Vite設定更新
# 4. ビルドテスト
npm run build
```

#### 4.2 JavaScript分離
```bash
# 1. インラインJavaScriptをES6モジュールに変換
# 2. 機能別モジュール作成
# 3. 共有ユーティリティ作成
# 4. ビルドテスト
npm run build
```

### Phase 5: 最終検証（1週間）

#### 5.1 全体テスト
```bash
# 全テストスイート実行
php artisan test
npm run test

# ブラウザテスト
php artisan dusk
```

#### 5.2 パフォーマンステスト
```bash
# パフォーマンスベンチマーク
php artisan test tests/Feature/LandInfoPerformanceBenchmarkTest.php
```

#### 5.3 セキュリティ監査
```bash
# 依存関係脆弱性チェック
composer audit
npm audit

# セキュリティテスト
php artisan test tests/Feature/LandInfoSecurityTest.php
```

## ロールバック手順

### 緊急時ロールバック

#### 1. データベースロールバック
```bash
# バックアップからの復元
php artisan backup:restore --backup-name=pre-refactor-backup
```

#### 2. コードベースロールバック
```bash
# 前バージョンへの復帰
git checkout v1.9.9-pre-refactor
composer install
npm install
npm run build
```

#### 3. 設定ロールバック
```bash
# 設定ファイル復元
git checkout v1.9.9-pre-refactor -- config/
php artisan config:cache
```

### 段階的ロールバック

#### 機能別ロールバック
特定の機能のみ問題がある場合:

```bash
# 特定のコントローラーのみロールバック
git checkout v1.9.9-pre-refactor -- app/Http/Controllers/LandInfoController.php
git checkout v1.9.9-pre-refactor -- routes/web.php

# 関連テストの実行
php artisan test tests/Feature/LandInfoControllerTest.php
```

## 検証チェックリスト

### 機能検証

#### 施設管理機能
- [ ] 施設一覧表示
- [ ] 施設詳細表示
- [ ] 施設作成・編集・削除
- [ ] 基本情報管理
- [ ] 土地情報管理
- [ ] 土地情報承認・差戻し
- [ ] ドキュメント管理

#### 出力機能
- [ ] PDF単体出力
- [ ] PDFセキュア出力
- [ ] PDF一括出力
- [ ] CSV出力
- [ ] フィールド選択
- [ ] お気に入り機能

#### コメント機能
- [ ] コメント作成・編集・削除
- [ ] ステータス管理
- [ ] 担当者割り当て
- [ ] 通知機能

### パフォーマンス検証
- [ ] ページ読み込み時間（3秒以内）
- [ ] API応答時間（1秒以内）
- [ ] CSS/JSファイルサイズ（前回比較）
- [ ] データベースクエリ数（N+1問題なし）

### セキュリティ検証
- [ ] 認証・認可機能
- [ ] CSRFトークン検証
- [ ] XSS対策
- [ ] ファイルアップロード制限
- [ ] アクセスログ記録

## トラブルシューティング

### よくある問題

#### 1. ルートが見つからない
**症状**: 404エラーが発生
**原因**: 旧ルートを使用している
**解決策**: 
```bash
# ルートキャッシュクリア
php artisan route:clear
php artisan route:cache
```

#### 2. サービスが見つからない
**症状**: サービス注入エラー
**原因**: 旧サービス名を使用している
**解決策**:
```php
// 旧: app(LandInfoService::class)
// 新: app(FacilityService::class)
```

#### 3. CSS/JSが読み込まれない
**症状**: スタイルやJavaScriptが適用されない
**原因**: Vite設定またはビルドエラー
**解決策**:
```bash
# アセット再ビルド
npm run build

# 開発サーバー再起動
npm run dev
```

#### 4. テストが失敗する
**症状**: 既存テストがエラー
**原因**: テストデータまたはアサーション不整合
**解決策**:
```bash
# テストデータベース再作成
php artisan migrate:fresh --seed --env=testing

# 特定テストの詳細実行
php artisan test --verbose tests/Feature/FacilityControllerTest.php
```

## サポート・連絡先

### 技術サポート
- **開発チーム**: development-team@company.com
- **システム管理者**: admin@company.com

### ドキュメント
- **技術仕様書**: [docs/architecture/SIMPLIFIED_ARCHITECTURE.md](../architecture/SIMPLIFIED_ARCHITECTURE.md)
- **API仕様書**: [docs/api/API_REFERENCE.md](../api/API_REFERENCE.md)
- **トラブルシューティング**: [docs/troubleshooting/](../troubleshooting/)

### 緊急時連絡先
- **24時間サポート**: emergency@company.com
- **電話**: 03-XXXX-XXXX

---

**注意**: このマイグレーションガイドは、プロジェクト簡素化リファクタリングの完了後に作成されました。実際の移行作業は既に完了しており、このドキュメントは今後の参考および新規開発者のオンボーディング用です。