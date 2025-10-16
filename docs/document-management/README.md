# ドキュメント管理システム - 総合ガイド

## 概要

施設管理システムには、4つの独立したドキュメント管理システムが実装されています。各システムは異なる目的とユースケースを持ち、適切に分離されています。

## ドキュメント一覧

### 📋 基本ドキュメント
- **[separation-strategy.md](./separation-strategy.md)** - 3つのシステムの分離戦略と設計思想
- **[current-implementation-analysis.md](./current-implementation-analysis.md)** - 現在の実装状況の詳細分析
- **[implementation-checklist.md](./implementation-checklist.md)** - 実装状況のチェックリスト
- **[folder-deletion-error-fix.md](./folder-deletion-error-fix.md)** - フォルダ削除エラーの修正記録

### 📚 カテゴリ分離実装ガイド
- **[category-implementation-guide.md](./category-implementation-guide.md)** - カテゴリ値の命名規則と実装パターン
- **[category-troubleshooting-guide.md](./category-troubleshooting-guide.md)** - よくある問題と解決方法
- **[category-separation-summary.md](./category-separation-summary.md)** - カテゴリ分離機能の実装サマリー
- **[lifeline-service-category-implementation.md](./lifeline-service-category-implementation.md)** - ライフライン設備サービスの実装詳細
- **[maintenance-service-category-implementation.md](./maintenance-service-category-implementation.md)** - 修繕履歴サービスの実装詳細
- **[contracts-category-implementation.md](./contracts-category-implementation.md)** - 契約書サービスの実装詳細

### 📄 契約書ドキュメント管理
- **[contract-document-management-index.md](./contract-document-management-index.md)** - 契約書ドキュメント管理の総合索引
- **[contract-document-user-guide.md](./contract-document-user-guide.md)** - ユーザー向け使用方法ガイド
- **[contract-document-developer-guide.md](./contract-document-developer-guide.md)** - 開発者向け実装ガイド
- **[contract-document-api-reference.md](./contract-document-api-reference.md)** - API仕様書

## 4つのドキュメント管理システム

### 1. 📁 メインドキュメント管理
**目的**: 施設全体の汎用ドキュメント管理

**主要コンポーネント**:
- コントローラー: `DocumentController`
- サービス: `DocumentService`
- モデル: `DocumentFolder`, `DocumentFile`
- JavaScript: `DocumentManager`
- ビュー: `resources/views/facilities/documents/`

**ルート**: `/facilities/{facility}/documents/*`

**特徴**:
- フォルダ階層構造
- 高度な検索・フィルタリング
- ユーザー権限管理
- ファイルプレビュー機能

### 2. ⚡ ライフライン設備ドキュメント管理
**目的**: 設備（電気、ガス、水道等）の点検報告書・図面管理

**主要コンポーネント**:
- コントローラー: `LifelineDocumentController`
- サービス: `LifelineDocumentService`
- JavaScript: `LifelineDocumentManager`
- ビュー: `resources/views/components/lifeline-equipment-documents.blade.php`

**ルート**: `/facilities/{facility}/lifeline-documents/{category}/*`

**カテゴリ**:
- `electrical` - 電気設備
- `gas` - ガス設備
- `water` - 水道設備
- `elevator` - エレベーター設備
- `hvac_lighting` - 空調・照明設備

**特徴**:
- カテゴリ別管理
- 点検報告書の期限管理
- 設備情報との連携
- デフォルトフォルダ構造

### 3. 🔧 修繕履歴ドキュメント管理
**目的**: 修繕記録（外装、内装等）のドキュメント管理

**主要コンポーネント**:
- コントローラー: `MaintenanceDocumentController`
- サービス: `MaintenanceDocumentService`
- JavaScript: `MaintenanceDocumentManager`
- ビュー: `resources/views/components/maintenance-document-manager.blade.php`

**ルート**: `/facilities/{facility}/maintenance-documents/{category}/*`

**カテゴリ**:
- `exterior` - 外装
- `interior` - 内装
- `summer_condensation` - 夏季結露
- `other` - その他

**特徴**:
- 修繕記録との紐付け
- 時系列管理
- 証拠資料の保管
- コスト情報との連携

### 4. 📄 契約書ドキュメント管理
**目的**: 契約書類（契約書、見積書、請求書等）の管理

**主要コンポーネント**:
- コントローラー: `ContractDocumentController`
- サービス: `ContractDocumentService`
- JavaScript: `ContractDocumentManager`
- ビュー: `resources/views/components/contract-document-manager.blade.php`

**ルート**: `/facilities/{facility}/contract-documents/*`

**カテゴリ**: `contracts`

**デフォルトサブフォルダ**:
- `contracts` - 契約書
- `estimates` - 見積書
- `invoices` - 請求書
- `others` - その他

**特徴**:
- 契約書情報との連携
- デフォルトフォルダ構造
- カテゴリ別管理
- 統一されたUI/UX

**ドキュメント**: [契約書ドキュメント管理索引](./contract-document-management-index.md)

## 現在の実装状況

### ✅ 完全に実装済み
- ルート分離
- コントローラー分離
- サービス層分離
- JavaScript分離
- ビュー分離

### 🔄 部分的に実装済み
- データベーステーブル（共有テーブルを使用）
- ストレージパス（論理的に分離）

### ❌ 未実装
- 完全なテーブル分離
- カテゴリ固有のフィールド
- 包括的な機能テスト

## データベース構造

### 現在の実装（共有テーブル方式）
```
document_folders
├── facility_id
├── parent_id
├── name          ← カテゴリ名で識別
├── path
└── created_by

document_files
├── facility_id
├── folder_id
├── original_name
├── file_path
└── uploaded_by
```

**利点**:
- シンプルな実装
- 統一されたAPI
- 柔軟な構造

**欠点**:
- データの混在
- カテゴリ固有フィールドの追加が困難
- 大量データ時のパフォーマンス

### 推奨される改善（categoryカラム追加）
```sql
ALTER TABLE document_folders ADD COLUMN category VARCHAR(50) AFTER facility_id;
ALTER TABLE document_files ADD COLUMN category VARCHAR(50) AFTER facility_id;

CREATE INDEX idx_folders_facility_category ON document_folders(facility_id, category);
CREATE INDEX idx_files_facility_category ON document_files(facility_id, category);
```

**カテゴリ値**:
- `null` - メインドキュメント
- `lifeline_electrical` - 電気設備
- `lifeline_gas` - ガス設備
- `maintenance_exterior` - 外装修繕
- `maintenance_interior` - 内装修繕
- `contracts` - 契約書

## API エンドポイント

### メインドキュメント管理
```
GET    /facilities/{facility}/documents
GET    /facilities/{facility}/documents/folders/{folder?}
POST   /facilities/{facility}/documents/folders
DELETE /facilities/{facility}/documents/folders/{folder}
POST   /facilities/{facility}/documents/files
DELETE /facilities/{facility}/documents/files/{file}
```

### ライフライン設備ドキュメント
```
GET    /facilities/{facility}/lifeline-documents/{category}
POST   /facilities/{facility}/lifeline-documents/{category}/folders
DELETE /facilities/{facility}/lifeline-documents/{category}/folders/{folderId}
POST   /facilities/{facility}/lifeline-documents/{category}/files
DELETE /facilities/{facility}/lifeline-documents/{category}/files/{fileId}
```

### 修繕履歴ドキュメント
```
GET    /facilities/{facility}/maintenance-documents/{category}
POST   /facilities/{facility}/maintenance-documents/{category}/folders
DELETE /facilities/{facility}/maintenance-documents/{category}/folders/{folder}
POST   /facilities/{facility}/maintenance-documents/{category}/files
DELETE /facilities/{facility}/maintenance-documents/{category}/files/{file}
```

### 契約書ドキュメント
```
GET    /facilities/{facility}/contract-documents
POST   /facilities/{facility}/contract-documents/upload
POST   /facilities/{facility}/contract-documents/folders
GET    /facilities/{facility}/contract-documents/files/{file}/download
DELETE /facilities/{facility}/contract-documents/files/{file}
DELETE /facilities/{facility}/contract-documents/folders/{folder}
PATCH  /facilities/{facility}/contract-documents/files/{file}/rename
PATCH  /facilities/{facility}/contract-documents/folders/{folder}/rename
```

## 使用方法

### メインドキュメント管理
```javascript
// JavaScript初期化
const documentManager = new DocumentManager({
    facilityId: 123
});
documentManager.init();

// フォルダ作成
await documentManager.createFolder('新しいフォルダ', parentFolderId);

// ファイルアップロード
await documentManager.uploadFiles(files, folderId);
```

### ライフライン設備ドキュメント
```javascript
// JavaScript初期化
const lifelineManager = new LifelineDocumentManager(
    facilityId,
    'electrical',  // カテゴリ
    uniqueId
);
lifelineManager.init();

// ドキュメント読み込み
await lifelineManager.loadDocuments(folderId);
```

### 修繕履歴ドキュメント
```javascript
// JavaScript初期化
const maintenanceManager = new MaintenanceDocumentManager(
    facilityId,
    'exterior'  // カテゴリ
);
maintenanceManager.init();

// ドキュメント読み込み
await maintenanceManager.loadDocuments();
```

### 契約書ドキュメント
```javascript
// JavaScript初期化
const contractManager = new ContractDocumentManager(facilityId);
contractManager.init();

// ドキュメント読み込み
await contractManager.loadDocuments(folderId);

// ファイルアップロード
await contractManager.uploadFile(file, folderId);

// フォルダ作成
await contractManager.createFolder(name, parentId);
```

## 共通機能

### ファイル処理
すべてのシステムで`FileHandlingService`を使用：
```php
$result = $fileHandlingService->uploadFile($file, $directory, $type);
$response = $fileHandlingService->downloadFile($path, $filename);
```

### アクティビティログ
すべての操作を`ActivityLogService`で記録：
```php
$activityLogService->logDocumentFolderCreated($folderId, $name, $facilityId);
$activityLogService->logDocumentFileUploaded($fileId, $name, $folderName, $facilityId);
```

### エラーハンドリング
統一されたエラーハンドリング：
```php
try {
    // 処理
} catch (Exception $e) {
    return DocumentErrorHandler::handleError($e, $request, $context);
}
```

## セキュリティ

### 認証・認可
```php
// コントローラーで認可チェック
$this->authorize('view', [DocumentFolder::class, $facility]);
$this->authorize('create', [DocumentFile::class, $facility]);
$this->authorize('delete', $folder);
```

### ファイルバリデーション
```php
// ファイルタイプとサイズの検証
$rules = [
    'files.*' => ['required', 'file', 'max:10240'], // 10MB
];
```

### パストラバーサル対策
```php
// ファイルパスの検証
if (strpos($filePath, '..') !== false) {
    throw new Exception('不正なファイルパスが検出されました。');
}
```

## パフォーマンス最適化

### キャッシュ戦略
```php
// フォルダ削除可能性のキャッシュ
$cacheKey = "folder_can_delete_{$this->id}";
return cache()->remember($cacheKey, 60, function () {
    return !$this->children()->exists() && !$this->files()->exists();
});
```

### ページネーション
```php
// 大量データの処理
$files = DocumentFile::where('folder_id', $folderId)
    ->paginate(50);
```

### インデックス最適化
```sql
-- 推奨インデックス
CREATE INDEX idx_folders_facility_parent ON document_folders(facility_id, parent_id);
CREATE INDEX idx_files_facility_folder ON document_files(facility_id, folder_id);
CREATE INDEX idx_files_created_at ON document_files(created_at);
```

## トラブルシューティング

### よくある問題

#### 1. フォルダ削除エラー（500エラー）
**原因**: フォルダに子フォルダまたはファイルが存在
**解決**: [folder-deletion-error-fix.md](./folder-deletion-error-fix.md)を参照

#### 2. ファイルアップロード失敗
**原因**: ファイルサイズ制限、MIMEタイプ不一致
**解決**: バリデーションルールとサーバー設定を確認

#### 3. モーダルが表示されない
**原因**: Bootstrap初期化の問題、z-indexの競合
**解決**: モーダル実装ガイドラインを参照

## 今後の開発計画

### 短期（1-2週間）
- [ ] categoryカラムの追加
- [ ] インデックス最適化
- [ ] 基本的な機能テスト

### 中期（1-2ヶ月）
- [ ] パフォーマンステスト
- [ ] 包括的な機能テスト
- [ ] ユーザーマニュアル作成

### 長期（3-6ヶ月）
- [ ] 完全なテーブル分離の評価
- [ ] 高度な検索機能
- [ ] レポート機能

## 関連ドキュメント

### 技術ドキュメント
- [tech.md](../../.kiro/steering/tech.md) - 技術スタック
- [structure.md](../../.kiro/steering/structure.md) - プロジェクト構造
- [file-handling.md](../../.kiro/steering/file-handling.md) - ファイル処理ガイドライン

### 実装ガイド
- [modal-implementation-guide.md](../../.kiro/steering/modal-implementation-guide.md) - モーダル実装
- [document-management-guide.md](../lifeline-equipment/document-management-guide.md) - ライフライン設備ドキュメント
- [document-management-implementation.md](../maintenance-history/document-management-implementation.md) - 修繕履歴ドキュメント

## まとめ

4つのドキュメント管理システムは、以下の方針で実装されています：

1. **論理的分離**: 同じテーブルを使用しながら、カテゴリ値で分離
2. **コード分離**: コントローラー、サービス、JavaScriptは完全に分離
3. **API分離**: 異なるエンドポイントで各システムにアクセス
4. **将来の拡張性**: 必要に応じて完全なテーブル分離が可能

この設計により、システムの保守性と拡張性を確保しながら、実装の複雑さを最小限に抑えています。

### 各システムの特徴

| システム | カテゴリ | 主な用途 | 特徴 |
|---------|---------|---------|------|
| メインドキュメント | `null` | 汎用ドキュメント | 高度な検索、フォルダ階層 |
| ライフライン設備 | `lifeline_*` | 点検報告書、図面 | 設備連携、期限管理 |
| 修繕履歴 | `maintenance_*` | 修繕記録 | 時系列管理、コスト連携 |
| 契約書 | `contracts` | 契約書類 | デフォルトフォルダ、契約連携 |
