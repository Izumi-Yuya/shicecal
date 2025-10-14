# 修繕履歴ドキュメント管理機能 実装ガイド

## 概要

修繕履歴の各カテゴリ（外装、内装、その他）に対応したドキュメント管理機能を実装しました。
ライフライン設備のドキュメント管理機能と同じアーキテクチャを採用し、統一されたユーザー体験を提供します。

## 実装内容

### 1. バックエンド実装

#### MaintenanceDocumentService
- **場所**: `app/Services/MaintenanceDocumentService.php`
- **機能**:
  - カテゴリ別のルートフォルダ管理
  - デフォルトサブフォルダの自動作成（契約書、見積書、請求書、施工写真、報告書、保証書）
  - ファイルアップロード・ダウンロード
  - フォルダ作成・削除・名前変更
  - ファイル削除・名前変更

#### MaintenanceDocumentController
- **場所**: `app/Http/Controllers/MaintenanceDocumentController.php`
- **エンドポイント**:
  - `GET /facilities/{facility}/maintenance-documents/{category}` - ドキュメント一覧取得
  - `POST /facilities/{facility}/maintenance-documents/{category}/upload` - ファイルアップロード
  - `POST /facilities/{facility}/maintenance-documents/{category}/folders` - フォルダ作成
  - `GET /facilities/{facility}/maintenance-documents/{category}/files/{file}/download` - ファイルダウンロード
  - `PUT /facilities/{facility}/maintenance-documents/{category}/files/{file}` - ファイル名変更
  - `DELETE /facilities/{facility}/maintenance-documents/{category}/files/{file}` - ファイル削除
  - `PUT /facilities/{facility}/maintenance-documents/{category}/folders/{folder}` - フォルダ名変更
  - `DELETE /facilities/{facility}/maintenance-documents/{category}/folders/{folder}` - フォルダ削除

### 2. フロントエンド実装

#### MaintenanceDocumentManager
- **場所**: `resources/js/modules/MaintenanceDocumentManager.js`
- **機能**:
  - ドキュメント一覧の表示（リスト/グリッド表示切替）
  - ファイルアップロード（進捗表示付き）
  - フォルダ作成
  - ファイル・フォルダの名前変更
  - ファイル・フォルダの削除
  - コンテキストメニュー
  - パンくずナビゲーション
  - 検索機能（今後実装予定）

#### Bladeコンポーネント
- **場所**: `resources/views/components/maintenance-document-manager.blade.php`
- **使用方法**:
```blade
<x-maintenance-document-manager 
    :facility="$facility" 
    :category="$category"
    :categoryName="\App\Models\MaintenanceHistory::CATEGORIES[$category]"
/>
```

### 3. ルート定義

```php
// routes/web.php
Route::prefix('maintenance-documents')->name('maintenance-documents.')->group(function () {
    Route::get('/{category}', [MaintenanceDocumentController::class, 'index'])->name('index');
    Route::post('/{category}/upload', [MaintenanceDocumentController::class, 'uploadFile'])->name('upload');
    Route::post('/{category}/folders', [MaintenanceDocumentController::class, 'createFolder'])->name('create-folder');
    
    Route::put('/{category}/folders/{folder}', [MaintenanceDocumentController::class, 'renameFolder'])->name('rename-folder');
    Route::delete('/{category}/folders/{folder}', [MaintenanceDocumentController::class, 'deleteFolder'])->name('delete-folder');
    
    Route::get('/{category}/files/{file}/download', [MaintenanceDocumentController::class, 'downloadFile'])->name('download-file');
    Route::put('/{category}/files/{file}', [MaintenanceDocumentController::class, 'renameFile'])->name('rename-file');
    Route::delete('/{category}/files/{file}', [MaintenanceDocumentController::class, 'deleteFile'])->name('delete-file');
});
```

## カテゴリとフォルダ構成

### 対応カテゴリ
- **exterior** (外装)
- **interior** (内装リニューアル)
- **other** (その他)

### デフォルトサブフォルダ
各カテゴリのルートフォルダ作成時に、以下のサブフォルダが自動的に作成されます：

1. **契約書** (contracts)
2. **見積書** (estimates)
3. **請求書** (invoices)
4. **施工写真** (photos)
5. **報告書** (reports)
6. **保証書** (warranties)

## 使用方法

### 1. 修繕履歴編集ページでの表示

修繕履歴編集ページ（`resources/views/facilities/repair-history/edit.blade.php`）に、ドキュメント管理セクションが追加されています。

```blade
<!-- Document Management Section -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-folder me-2"></i>
            {{ \App\Models\MaintenanceHistory::CATEGORIES[$category] }} 関連ドキュメント
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            契約書、見積書、請求書、施工写真、報告書などの関連ドキュメントを管理できます。
        </p>
        
        <x-maintenance-document-manager 
            :facility="$facility" 
            :category="$category"
            :categoryName="\App\Models\MaintenanceHistory::CATEGORIES[$category]"
        />
    </div>
</div>
```

### 2. ファイルアップロード

1. 「ファイルアップロード」ボタンをクリック
2. ファイルを選択（最大50MB）
3. 「アップロード」ボタンをクリック
4. アップロード進捗が表示され、完了後に一覧に反映

### 3. フォルダ作成

1. 「新しいフォルダ」ボタンをクリック
2. フォルダ名を入力
3. 「作成」ボタンをクリック
4. フォルダが一覧に追加される

### 4. ファイル・フォルダ操作

- **開く**: フォルダ名をクリック
- **ダウンロード**: ファイル名をクリック、または右クリックメニューから「ダウンロード」
- **名前変更**: 右クリックメニューから「名前変更」
- **削除**: 右クリックメニューから「削除」

## セキュリティ

### 認可チェック
すべてのドキュメント操作で、以下の認可チェックが実行されます：

- **閲覧**: `MaintenanceHistory::class` の `view` ポリシー
- **編集**: `MaintenanceHistory::class` の `update` ポリシー

### ファイルサイズ制限
- 最大ファイルサイズ: 50MB

### ファイルタイプ制限
- 現在は制限なし（将来的に追加可能）

## データベース構造

既存の `document_folders` と `document_files` テーブルを使用します。

### document_folders
- `facility_id`: 施設ID
- `parent_id`: 親フォルダID（NULL = ルートフォルダ）
- `name`: フォルダ名
- `path`: フォルダパス
- `created_by`: 作成者ID

### document_files
- `facility_id`: 施設ID
- `folder_id`: フォルダID
- `original_name`: 元のファイル名
- `stored_name`: 保存ファイル名
- `file_path`: ファイルパス
- `file_size`: ファイルサイズ
- `mime_type`: MIMEタイプ
- `uploaded_by`: アップロード者ID

## アクティビティログ

すべてのドキュメント操作は、ActivityLogServiceを通じて記録されます：

- ファイルアップロード
- フォルダ作成
- ファイル削除
- フォルダ削除
- ファイル名変更
- フォルダ名変更

## トラブルシューティング

### ドキュメントが表示されない

1. ブラウザのコンソールでエラーを確認
2. `MaintenanceDocumentManager` クラスが正しく読み込まれているか確認
3. ネットワークタブでAPIリクエストのレスポンスを確認

### ファイルアップロードが失敗する

1. ファイルサイズが50MB以下か確認
2. サーバーの `upload_max_filesize` と `post_max_size` 設定を確認
3. ストレージの書き込み権限を確認

### フォルダが作成できない

1. 施設の編集権限があるか確認
2. フォルダ名が255文字以内か確認
3. 同名のフォルダが既に存在しないか確認

## 今後の拡張予定

1. **検索機能**: ファイル名・フォルダ名での検索
2. **フィルター機能**: ファイルタイプ、日付範囲でのフィルター
3. **一括操作**: 複数ファイルの一括ダウンロード・削除
4. **プレビュー機能**: PDFファイルのプレビュー表示
5. **バージョン管理**: ファイルの履歴管理
6. **共有機能**: 外部ユーザーとのファイル共有

## 参考資料

- [ライフライン設備ドキュメント管理実装ガイド](../lifeline-equipment/document-management-guide.md)
- [ファイルハンドリングガイドライン](../.kiro/steering/file-handling.md)
- [モーダル実装ガイドライン](../.kiro/steering/modal-implementation-guide.md)
