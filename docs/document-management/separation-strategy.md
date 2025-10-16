# ドキュメント管理システムの分離戦略

## 概要

施設管理システムには3つの独立したドキュメント管理システムが存在します。それぞれが異なる目的とデータ構造を持ち、完全に分離して管理する必要があります。

## 3つのドキュメント管理システム

### 1. メインドキュメント管理（DocumentManager）
**目的**: 施設全体の汎用ドキュメント管理

**特徴**:
- フォルダ階層構造をサポート
- 任意のファイルタイプをアップロード可能
- 高度な検索・フィルタリング機能
- ユーザー権限による詳細なアクセス制御

**データベーステーブル**:
- `document_folders` - フォルダ構造
- `document_files` - ファイル情報

**コントローラー**: `DocumentController`
**サービス**: `DocumentService`
**JavaScriptクラス**: `DocumentManager`
**ビュー**: `resources/views/facilities/documents/`

**ルート**:
```php
Route::prefix('facilities/{facility}/documents')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/folders/{folder?}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/folders', [DocumentController::class, 'createFolder'])->name('folders.store');
    Route::delete('/folders/{folder}', [DocumentController::class, 'deleteFolder'])->name('folders.destroy');
    Route::post('/files', [DocumentController::class, 'uploadFile'])->name('files.store');
    Route::delete('/files/{file}', [DocumentController::class, 'deleteFile'])->name('files.destroy');
});
```

### 2. ライフライン設備ドキュメント管理（LifelineDocumentManager）
**目的**: ライフライン設備（電気、ガス、水道、エレベーター等）の点検報告書・図面管理

**特徴**:
- カテゴリ別（electrical, gas, water, elevator等）に分離
- 設備固有のドキュメント構造
- 点検報告書PDFの管理に特化
- 設備情報と密接に連携

**データベーステーブル**:
- `lifeline_equipment_documents` - 設備ドキュメント情報
- `lifeline_equipment_folders` - 設備フォルダ構造
- `lifeline_equipment_files` - 設備ファイル情報

**コントローラー**: `LifelineDocumentController`
**サービス**: `LifelineDocumentService`
**JavaScriptクラス**: `LifelineDocumentManager`
**ビュー**: `resources/views/components/lifeline-equipment-documents.blade.php`

**ルート**:
```php
Route::prefix('facilities/{facility}/lifeline-documents')->group(function () {
    Route::get('/{category}', [LifelineDocumentController::class, 'index'])->name('index');
    Route::get('/{category}/folders/{folderId?}', [LifelineDocumentController::class, 'show'])->name('show');
    Route::post('/{category}/folders', [LifelineDocumentController::class, 'createFolder'])->name('create-folder');
    Route::delete('/{category}/folders/{folderId}', [LifelineDocumentController::class, 'deleteFolder'])->name('delete-folder');
    Route::post('/{category}/files', [LifelineDocumentController::class, 'uploadFile'])->name('upload-file');
    Route::delete('/{category}/files/{fileId}', [LifelineDocumentController::class, 'deleteFile'])->name('delete-file');
});
```

### 3. 修繕履歴ドキュメント管理（MaintenanceDocumentManager）
**目的**: 修繕履歴（外装、内装、夏季結露、その他）のドキュメント管理

**特徴**:
- 修繕カテゴリ別（exterior, interior, summer_condensation, other）に分離
- 修繕記録と紐づいたドキュメント管理
- 修繕履歴の証拠資料として機能
- 時系列での管理が重要

**データベーステーブル**:
- `maintenance_documents` - 修繕ドキュメント情報
- `maintenance_folders` - 修繕フォルダ構造
- `maintenance_files` - 修繕ファイル情報

**コントローラー**: `MaintenanceDocumentController`
**サービス**: `MaintenanceDocumentService`
**JavaScriptクラス**: `MaintenanceDocumentManager`
**ビュー**: `resources/views/components/maintenance-document-manager.blade.php`

**ルート**:
```php
Route::prefix('facilities/{facility}/maintenance-documents')->group(function () {
    Route::get('/{category}', [MaintenanceDocumentController::class, 'index'])->name('index');
    Route::get('/{category}/folders/{folder?}', [MaintenanceDocumentController::class, 'show'])->name('show');
    Route::post('/{category}/folders', [MaintenanceDocumentController::class, 'createFolder'])->name('create-folder');
    Route::delete('/{category}/folders/{folder}', [MaintenanceDocumentController::class, 'deleteFolder'])->name('delete-folder');
    Route::post('/{category}/files', [MaintenanceDocumentController::class, 'uploadFile'])->name('upload-file');
    Route::delete('/{category}/files/{file}', [MaintenanceDocumentController::class, 'deleteFile'])->name('delete-file');
});
```

## 分離の理由

### 1. データ構造の違い
- **メイン**: 汎用的なフォルダ・ファイル構造
- **ライフライン**: 設備カテゴリに紐づいた構造
- **修繕履歴**: 修繕記録に紐づいた構造

### 2. アクセス権限の違い
- **メイン**: 施設全体の権限管理
- **ライフライン**: 設備管理者の権限が必要
- **修繕履歴**: 修繕担当者の権限が必要

### 3. ビジネスロジックの違い
- **メイン**: 汎用ドキュメント管理
- **ライフライン**: 点検報告書の期限管理、設備情報との連携
- **修繕履歴**: 修繕記録との紐付け、コスト管理

### 4. UI/UXの違い
- **メイン**: 独立したドキュメントタブ
- **ライフライン**: 設備詳細画面内のサブセクション
- **修繕履歴**: 修繕履歴画面内のサブセクション

## 技術的な分離ポイント

### 1. データベーステーブルの完全分離
```sql
-- メインドキュメント
document_folders (id, facility_id, parent_id, name, path, ...)
document_files (id, facility_id, folder_id, original_name, ...)

-- ライフライン設備ドキュメント
lifeline_equipment_documents (id, facility_id, category, ...)
lifeline_equipment_folders (id, facility_id, category, parent_id, ...)
lifeline_equipment_files (id, facility_id, category, folder_id, ...)

-- 修繕履歴ドキュメント
maintenance_documents (id, facility_id, category, ...)
maintenance_folders (id, facility_id, category, parent_id, ...)
maintenance_files (id, facility_id, category, folder_id, ...)
```

### 2. ストレージパスの分離
```
storage/app/public/
├── facility_{id}/                    # メインドキュメント
│   ├── root/
│   └── folder_{id}/
├── lifeline/{category}/              # ライフライン設備
│   ├── facility_{id}/
│   └── folder_{id}/
└── maintenance/{category}/           # 修繕履歴
    ├── facility_{id}/
    └── folder_{id}/
```

### 3. APIエンドポイントの分離
```
/facilities/{facility}/documents/*              # メイン
/facilities/{facility}/lifeline-documents/*     # ライフライン
/facilities/{facility}/maintenance-documents/*  # 修繕履歴
```

### 4. JavaScriptクラスの分離
```javascript
// 完全に独立したクラス
class DocumentManager { ... }
class LifelineDocumentManager { ... }
class MaintenanceDocumentManager { ... }

// グローバル登録も分離
window.documentManager = new DocumentManager(...);
window.shiseCalApp.modules.lifelineDocumentManager_electrical = new LifelineDocumentManager(...);
window.shiseCalApp.modules.maintenanceDocumentManager_exterior = new MaintenanceDocumentManager(...);
```

### 5. コンポーネントの分離
```blade
{{-- メインドキュメント --}}
@include('facilities.documents.index')

{{-- ライフライン設備ドキュメント --}}
<x-lifeline-equipment-documents :facility="$facility" :category="$category" />

{{-- 修繕履歴ドキュメント --}}
<x-maintenance-document-manager :facility="$facility" :category="$category" />
```

## 共通機能の抽出

完全に分離しつつも、共通機能は抽出して再利用します：

### 1. 共通サービス
```php
// FileHandlingService - ファイル操作の共通処理
// ActivityLogService - アクティビティログ記録
// DocumentErrorHandler - エラーハンドリング
```

### 2. 共通トレイト
```php
// HandlesFileOperations - ファイル操作の共通メソッド
// HandlesApiResponses - API レスポンスの共通処理
```

### 3. 共通JavaScriptユーティリティ
```javascript
// ApiClient - API通信の共通処理
// AppUtils - ユーティリティ関数
```

## 実装チェックリスト

### メインドキュメント管理
- [x] DocumentController実装
- [x] DocumentService実装
- [x] DocumentManager (JavaScript)実装
- [x] document_folders, document_filesテーブル
- [x] 独立したルート定義
- [x] 独立したビューファイル
- [ ] 完全な機能テスト

### ライフライン設備ドキュメント管理
- [x] LifelineDocumentController実装
- [x] LifelineDocumentService実装
- [x] LifelineDocumentManager (JavaScript)実装
- [x] lifeline_equipment_*テーブル
- [x] カテゴリ別ルート定義
- [x] コンポーネント化されたビュー
- [x] 設備情報との連携
- [ ] 完全な機能テスト

### 修繕履歴ドキュメント管理
- [x] MaintenanceDocumentController実装
- [x] MaintenanceDocumentService実装
- [x] MaintenanceDocumentManager (JavaScript)実装
- [x] maintenance_*テーブル
- [x] カテゴリ別ルート定義
- [x] コンポーネント化されたビュー
- [x] 修繕記録との連携
- [ ] 完全な機能テスト

## 今後の拡張性

この分離戦略により、以下の拡張が容易になります：

1. **新しいドキュメント管理システムの追加**
   - 同じパターンで新しいシステムを追加可能
   - 既存システムへの影響なし

2. **独立した機能強化**
   - 各システムを独立して改善可能
   - 他システムへの影響を最小化

3. **パフォーマンス最適化**
   - システムごとに最適化戦略を適用可能
   - データベースインデックスの最適化

4. **セキュリティ強化**
   - システムごとに異なるセキュリティポリシーを適用可能
   - きめ細かいアクセス制御

## まとめ

3つのドキュメント管理システムは、以下の理由で完全に分離されています：

1. **データ構造の違い** - 各システムが異なるビジネス要件を持つ
2. **アクセス権限の違い** - 異なるユーザーロールが必要
3. **ビジネスロジックの違い** - 各システムが独自の処理を持つ
4. **UI/UXの違い** - 異なるユーザー体験を提供

この分離により、各システムを独立して開発・保守でき、システム全体の保守性と拡張性が向上します。
