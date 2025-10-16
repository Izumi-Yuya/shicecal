# Design Document

## Overview

契約書管理システムにドキュメント管理機能を追加します。既存の修繕履歴ドキュメント管理（MaintenanceDocumentService）と同じアーキテクチャパターンを採用し、契約書カテゴリー専用のドキュメント管理機能を実装します。

### Design Goals

1. **再利用性**: 既存のMaintenanceDocumentServiceのパターンを再利用
2. **一貫性**: 他のドキュメント管理機能と同じUIとUXを提供
3. **カテゴリ分離**: データベースレベルでのカテゴリ分離を保証
4. **拡張性**: 将来的な機能追加に対応できる設計
5. **保守性**: コードの重複を最小限に抑え、保守しやすい構造

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  resources/views/facilities/contracts/index.blade.php │  │
│  │  - 契約書タブ表示                                      │  │
│  │  - ドキュメント管理セクション統合                      │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  resources/views/components/                          │  │
│  │    contract-document-manager.blade.php                │  │
│  │  - ドキュメント管理UIコンポーネント                    │  │
│  │  - ツールバー、フォルダツリー、ファイルリスト          │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Controller Layer                         │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Http/Controllers/ContractDocumentController.php  │  │
│  │  - HTTPリクエスト処理                                  │  │
│  │  - 認証・認可チェック                                  │  │
│  │  - レスポンス生成                                      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      Service Layer                           │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Services/ContractDocumentService.php             │  │
│  │  - ビジネスロジック                                    │  │
│  │  - フォルダ・ファイル管理                              │  │
│  │  - カテゴリ分離処理                                    │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Services/DocumentService.php (既存)              │  │
│  │  - 共通ドキュメント処理                                │  │
│  │  - ファイルアップロード・ダウンロード                  │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Services/FileHandlingService.php (既存)          │  │
│  │  - ファイルシステム操作                                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                       Model Layer                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Models/DocumentFolder.php (既存)                 │  │
│  │  - フォルダモデル                                      │  │
│  │  - カテゴリスコープ: contracts()                       │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  app/Models/DocumentFile.php (既存)                   │  │
│  │  - ファイルモデル                                      │  │
│  │  - カテゴリスコープ: contracts()                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    JavaScript Layer                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  resources/js/modules/ContractDocumentManager.js      │  │
│  │  - クライアントサイドロジック                          │  │
│  │  - API通信                                             │  │
│  │  - UI更新                                              │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. ContractDocumentService

契約書ドキュメント管理のビジネスロジックを担当するサービスクラス。

#### Public Methods

```php
class ContractDocumentService
{
    // カテゴリ定義
    const CATEGORY = 'contracts';
    const CATEGORY_NAME = '契約書';
    
    // デフォルトサブフォルダ
    const DEFAULT_SUBFOLDERS = [
        'contracts' => '契約書',
        'estimates' => '見積書',
        'invoices' => '請求書',
        'others' => 'その他',
    ];

    /**
     * 契約書カテゴリのルートフォルダを取得または作成
     * 
     * @param Facility $facility
     * @param User $user
     * @return DocumentFolder
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, User $user): DocumentFolder;

    /**
     * 契約書カテゴリのドキュメント一覧を取得
     * 
     * @param Facility $facility
     * @param array $options ['folder_id', 'per_page', 'sort_by', 'sort_order']
     * @return array
     */
    public function getCategoryDocuments(Facility $facility, array $options = []): array;

    /**
     * 契約書カテゴリにファイルをアップロード
     * 
     * @param Facility $facility
     * @param UploadedFile $file
     * @param User $user
     * @param int|null $folderId
     * @return array
     */
    public function uploadCategoryFile(
        Facility $facility,
        UploadedFile $file,
        User $user,
        ?int $folderId = null
    ): array;

    /**
     * 契約書カテゴリにフォルダを作成
     * 
     * @param Facility $facility
     * @param string $folderName
     * @param User $user
     * @param int|null $parentFolderId
     * @return array
     */
    public function createCategoryFolder(
        Facility $facility,
        string $folderName,
        User $user,
        ?int $parentFolderId = null
    ): array;

    /**
     * 契約書カテゴリの統計情報を取得
     * 
     * @param Facility $facility
     * @return array
     */
    public function getCategoryStats(Facility $facility): array;

    /**
     * 契約書カテゴリ内のファイル検索
     * 
     * @param Facility $facility
     * @param string $query
     * @param array $options
     * @return array
     */
    public function searchCategoryFiles(Facility $facility, string $query, array $options = []): array;
}
```

#### Dependencies

- `DocumentService`: 共通ドキュメント処理
- `FileHandlingService`: ファイルシステム操作
- `ActivityLogService`: アクティビティログ記録

### 2. ContractDocumentController

HTTPリクエストを処理し、ContractDocumentServiceを呼び出すコントローラー。

#### Routes

```php
// ドキュメント一覧取得
GET /facilities/{facility}/contract-documents
    → ContractDocumentController@index

// ファイルアップロード
POST /facilities/{facility}/contract-documents/upload
    → ContractDocumentController@uploadFile

// フォルダ作成
POST /facilities/{facility}/contract-documents/folders
    → ContractDocumentController@createFolder

// ファイルダウンロード
GET /facilities/{facility}/contract-documents/files/{file}/download
    → ContractDocumentController@downloadFile

// ファイル削除
DELETE /facilities/{facility}/contract-documents/files/{file}
    → ContractDocumentController@deleteFile

// フォルダ削除
DELETE /facilities/{facility}/contract-documents/folders/{folder}
    → ContractDocumentController@deleteFolder

// ファイル名変更
PATCH /facilities/{facility}/contract-documents/files/{file}/rename
    → ContractDocumentController@renameFile

// フォルダ名変更
PATCH /facilities/{facility}/contract-documents/folders/{folder}/rename
    → ContractDocumentController@renameFolder
```

#### Authorization

- `view`: FacilityContractポリシーのviewメソッドを使用
- `update`: FacilityContractポリシーのupdateメソッドを使用

### 3. Blade Components

#### contract-document-manager.blade.php

契約書ドキュメント管理UIコンポーネント。

**Props:**
- `$facility`: Facilityモデルインスタンス
- `$categoryName`: カテゴリ表示名（デフォルト: '契約書'）

**Features:**
- ツールバー（フォルダ作成、ファイルアップロード、検索）
- パンくずナビゲーション
- ドキュメント一覧（リスト表示/グリッド表示）
- コンテキストメニュー
- モーダル（フォルダ作成、ファイルアップロード、名前変更、プロパティ）

### 4. JavaScript Module

#### ContractDocumentManager.js

クライアントサイドのドキュメント管理ロジック。

**Class Structure:**

```javascript
class ContractDocumentManager {
    constructor(facilityId) {
        this.facilityId = facilityId;
        this.category = 'contracts';
        this.currentFolderId = null;
        this.apiClient = new ApiClient();
        this.init();
    }

    // 初期化
    init() {
        this.setupEventListeners();
        this.loadDocuments();
    }

    // イベントリスナー設定
    setupEventListeners() {
        // フォルダ作成ボタン
        // ファイルアップロードボタン
        // 検索ボタン
        // コンテキストメニュー
    }

    // ドキュメント一覧読み込み
    async loadDocuments(folderId = null) {
        // API呼び出し
        // UI更新
    }

    // ファイルアップロード
    async uploadFile(file, folderId = null) {
        // FormData作成
        // API呼び出し
        // 進行状況表示
        // UI更新
    }

    // フォルダ作成
    async createFolder(name, parentId = null) {
        // API呼び出し
        // UI更新
    }

    // ファイル削除
    async deleteFile(fileId) {
        // 確認ダイアログ
        // API呼び出し
        // UI更新
    }

    // フォルダ削除
    async deleteFolder(folderId) {
        // 確認ダイアログ
        // API呼び出し
        // UI更新
    }

    // 名前変更
    async rename(type, id, newName) {
        // API呼び出し
        // UI更新
    }

    // 検索
    async search(query) {
        // API呼び出し
        // 検索結果表示
    }
}
```

## Data Models

### DocumentFolder Model

既存のDocumentFolderモデルにカテゴリスコープを追加。

```php
class DocumentFolder extends Model
{
    // 既存のスコープ
    public function scopeContracts($query)
    {
        return $query->where('category', 'contracts');
    }
}
```

### DocumentFile Model

既存のDocumentFileモデルにカテゴリスコープを追加。

```php
class DocumentFile extends Model
{
    // 既存のスコープ
    public function scopeContracts($query)
    {
        return $query->where('category', 'contracts');
    }
}
```

### Database Schema

既存のテーブルを使用（categoryカラムは既に存在）。

**document_folders テーブル:**
- `id`: bigint (PK)
- `facility_id`: bigint (FK)
- `parent_id`: bigint (FK, nullable)
- `category`: varchar(50) - 'contracts'を設定
- `name`: varchar(255)
- `path`: text
- `created_by`: bigint (FK)
- `created_at`: timestamp
- `updated_at`: timestamp

**document_files テーブル:**
- `id`: bigint (PK)
- `facility_id`: bigint (FK)
- `folder_id`: bigint (FK)
- `category`: varchar(50) - 'contracts'を設定
- `original_name`: varchar(255)
- `stored_name`: varchar(255)
- `file_path`: text
- `file_size`: bigint
- `mime_type`: varchar(100)
- `file_extension`: varchar(20)
- `uploaded_by`: bigint (FK)
- `created_at`: timestamp
- `updated_at`: timestamp

## Error Handling

### Error Types

1. **認証エラー (401)**
   - ユーザーが未認証
   - リダイレクト: ログインページ

2. **認可エラー (403)**
   - ユーザーに権限がない
   - メッセージ: "この施設のドキュメントを操作する権限がありません。"

3. **バリデーションエラー (422)**
   - 入力データが不正
   - エラーメッセージをフォームに表示

4. **ファイルサイズエラー (413)**
   - ファイルサイズが50MBを超過
   - メッセージ: "ファイルサイズは50MB以下にしてください。"

5. **ファイル不存在エラー (404)**
   - ファイルまたはフォルダが見つからない
   - メッセージ: "ファイルまたはフォルダが見つかりません。"

6. **システムエラー (500)**
   - 予期しないエラー
   - ログ出力
   - メッセージ: "システムエラーが発生しました。"

### Error Handling Strategy

```php
try {
    // 処理実行
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    // 認可エラー
    return $this->errorResponse('権限がありません。', 403);
} catch (\Illuminate\Validation\ValidationException $e) {
    // バリデーションエラー
    return $this->errorResponse('入力内容に誤りがあります。', 422, ['errors' => $e->errors()]);
} catch (\Exception $e) {
    // システムエラー
    Log::error('Contract document operation failed', [
        'facility_id' => $facility->id,
        'user_id' => auth()->id(),
        'error' => $e->getMessage(),
    ]);
    return $this->errorResponse('システムエラーが発生しました。', 500);
}
```

## Testing Strategy

### Unit Tests

1. **ContractDocumentServiceTest**
   - `test_get_or_create_category_root_folder()`
   - `test_get_category_documents()`
   - `test_upload_category_file()`
   - `test_create_category_folder()`
   - `test_get_category_stats()`
   - `test_search_category_files()`

2. **DocumentFolderTest**
   - `test_contracts_scope()`
   - `test_category_isolation()`

3. **DocumentFileTest**
   - `test_contracts_scope()`
   - `test_category_isolation()`

### Feature Tests

1. **ContractDocumentControllerTest**
   - `test_user_can_view_contract_documents()`
   - `test_user_can_upload_file_to_contracts()`
   - `test_user_can_create_folder_in_contracts()`
   - `test_user_can_download_contract_file()`
   - `test_user_can_delete_contract_file()`
   - `test_user_can_delete_contract_folder()`
   - `test_user_can_rename_contract_file()`
   - `test_user_can_rename_contract_folder()`
   - `test_user_can_search_contract_documents()`
   - `test_unauthorized_user_cannot_edit_documents()`
   - `test_viewer_can_only_view_documents()`

### Integration Tests

1. **ContractDocumentIntegrationTest**
   - `test_complete_document_workflow()`
   - `test_category_isolation_between_contracts_and_maintenance()`
   - `test_folder_hierarchy_management()`

### JavaScript Tests

1. **ContractDocumentManager.test.js**
   - `test_initialization()`
   - `test_load_documents()`
   - `test_upload_file()`
   - `test_create_folder()`
   - `test_delete_file()`
   - `test_delete_folder()`
   - `test_rename()`
   - `test_search()`

## Security Considerations

### Authentication & Authorization

1. **認証チェック**
   - すべてのエンドポイントで認証を必須化
   - ミドルウェア: `auth`

2. **認可チェック**
   - FacilityContractポリシーを使用
   - 閲覧: `view`メソッド
   - 編集: `update`メソッド

3. **施設アクセス制御**
   - ユーザーのアクセススコープを確認
   - 全施設アクセス、または特定施設のみアクセス

### File Security

1. **ファイルタイプ検証**
   - MIMEタイプチェック
   - ファイル拡張子チェック

2. **ファイルサイズ制限**
   - 最大50MB
   - サーバー設定とアプリケーション設定の両方で制限

3. **ファイル名サニタイゼーション**
   - 特殊文字の除去
   - パストラバーサル攻撃の防止

4. **ストレージセキュリティ**
   - ファイルは`storage/app/public/documents/`配下に保存
   - 直接アクセス不可
   - ダウンロードはコントローラー経由

### Input Validation

1. **フォルダ名**
   - 必須
   - 最大255文字
   - 特殊文字制限

2. **ファイル**
   - 必須
   - 最大50MB
   - 許可されたMIMEタイプ

3. **検索クエリ**
   - SQLインジェクション対策
   - XSS対策

## Performance Optimization

### Database Optimization

1. **インデックス**
   - `document_folders.facility_id`
   - `document_folders.category`
   - `document_folders.parent_id`
   - `document_files.facility_id`
   - `document_files.category`
   - `document_files.folder_id`

2. **クエリ最適化**
   - Eager Loading: `with(['creator', 'uploader'])`
   - ページネーション: デフォルト50件

3. **キャッシュ**
   - フォルダ統計情報のキャッシュ（5分）
   - 検索結果のキャッシュ（1分）

### File Handling Optimization

1. **ストリーミングダウンロード**
   - 大きなファイルはストリーミングで配信
   - メモリ使用量の削減

2. **非同期アップロード**
   - JavaScriptでの非同期アップロード
   - 進行状況表示

3. **サムネイル生成**
   - 画像ファイルのサムネイル生成（将来的な機能）
   - バックグラウンドジョブで処理

## Deployment Considerations

### Migration Strategy

1. **既存データへの影響なし**
   - 新しいカテゴリ（contracts）を追加
   - 既存のドキュメントには影響なし

2. **ロールバック計画**
   - 新しいコードを削除
   - ルートを削除
   - データベースは変更不要（既存テーブルを使用）

### Configuration

1. **環境変数**
   - `DOCUMENT_MAX_FILE_SIZE`: 最大ファイルサイズ（デフォルト: 51200KB = 50MB）
   - `DOCUMENT_STORAGE_PATH`: ストレージパス（デフォルト: documents）

2. **設定ファイル**
   - `config/filesystems.php`: ストレージ設定
   - `config/document-management.php`: ドキュメント管理設定（新規作成）

### Monitoring

1. **ログ**
   - ファイルアップロード/ダウンロード
   - フォルダ作成/削除
   - エラー発生

2. **メトリクス**
   - ファイル数
   - 合計ストレージ使用量
   - アップロード/ダウンロード回数

## Future Enhancements

1. **バージョン管理**
   - ファイルの履歴管理
   - 以前のバージョンへの復元

2. **共有機能**
   - 外部ユーザーとのファイル共有
   - 共有リンクの生成

3. **プレビュー機能**
   - PDFのブラウザ内プレビュー
   - 画像のプレビュー

4. **一括操作**
   - 複数ファイルの一括アップロード
   - 複数ファイルの一括削除

5. **タグ機能**
   - ファイルへのタグ付け
   - タグによる検索

6. **通知機能**
   - ファイルアップロード時の通知
   - フォルダ変更時の通知
