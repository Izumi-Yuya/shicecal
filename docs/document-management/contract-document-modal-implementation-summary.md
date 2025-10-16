# 契約書ドキュメント管理 - モーダルベース実装完了サマリー

## 実装状況

契約書のドキュメント管理は、ライフライン設備やメンテナンス履歴と同じモーダルベースのシステムとして**既に完全に実装されています**。

## 実装済みコンポーネント

### 1. Bladeコンポーネント
**ファイル**: `resources/views/components/contract-document-manager.blade.php`

- ✅ フォルダ作成モーダル
- ✅ ファイルアップロードモーダル
- ✅ 名前変更モーダル
- ✅ プロパティ表示モーダル
- ✅ コンテキストメニュー
- ✅ リスト/グリッド表示切替
- ✅ 検索機能
- ✅ パンくずナビゲーション

### 2. JavaScriptマネージャー
**ファイル**: `resources/js/modules/ContractDocumentManager.js`

主な機能:
- ✅ 遅延ロード（Lazy Loading）
- ✅ ドキュメント一覧表示
- ✅ フォルダ作成・削除・名前変更
- ✅ ファイルアップロード・ダウンロード・削除
- ✅ 検索機能
- ✅ 表示モード切替（リスト/グリッド）
- ✅ コンテキストメニュー
- ✅ エラーハンドリングと再試行機能
- ✅ ネットワークエラー対応

### 3. バックエンド実装

#### コントローラー
**ファイル**: `app/Http/Controllers/ContractDocumentController.php`

実装済みメソッド:
- ✅ `index()` - ドキュメント一覧取得
- ✅ `uploadFile()` - ファイルアップロード
- ✅ `downloadFile()` - ファイルダウンロード
- ✅ `deleteFile()` - ファイル削除
- ✅ `createFolder()` - フォルダ作成
- ✅ `renameFolder()` - フォルダ名変更
- ✅ `deleteFolder()` - フォルダ削除
- ✅ `renameFile()` - ファイル名変更

#### サービス
**ファイル**: `app/Services/ContractDocumentService.php`

主な機能:
- ✅ カテゴリ別ドキュメント管理
- ✅ ファイルアップロード処理
- ✅ フォルダ管理
- ✅ 権限チェック
- ✅ アクティビティログ記録

### 4. ルート定義
**ファイル**: `routes/web.php`

```php
Route::prefix('contract-documents')->name('contract-documents.')->group(function () {
    Route::get('/', [ContractDocumentController::class, 'index'])->name('index');
    Route::post('/upload', [ContractDocumentController::class, 'uploadFile'])->name('upload');
    Route::get('/files/{file}/download', [ContractDocumentController::class, 'downloadFile'])->name('download-file');
    Route::delete('/files/{file}', [ContractDocumentController::class, 'deleteFile'])->name('delete-file');
    Route::put('/files/{file}', [ContractDocumentController::class, 'renameFile'])->name('rename-file');
    Route::post('/folders', [ContractDocumentController::class, 'createFolder'])->name('create-folder');
    Route::put('/folders/{folder}', [ContractDocumentController::class, 'renameFolder'])->name('rename-folder');
    Route::delete('/folders/{folder}', [ContractDocumentController::class, 'deleteFolder'])->name('delete-folder');
});
```

### 5. ビューでの使用
**ファイル**: `resources/views/facilities/contracts/index.blade.php`

```blade
<div class="unified-contract-documents-section mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-folder text-primary me-2"></i>契約書関連ドキュメント
        </h5>
        <button type="button" 
                class="btn btn-outline-primary btn-sm unified-documents-toggle" 
                id="unified-documents-toggle"
                data-bs-toggle="collapse" 
                data-bs-target="#unified-documents-section" 
                aria-expanded="false" 
                aria-controls="unified-documents-section">
            <i class="fas fa-folder-open me-1"></i>
            <span>ドキュメントを表示</span>
        </button>
    </div>
    
    <div class="collapse" id="unified-documents-section">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i>契約書ドキュメント管理
                </h6>
            </div>
            <div class="card-body p-0">
                <x-contract-document-manager 
                    :facility="$facility" 
                    categoryName="契約書"
                />
            </div>
        </div>
    </div>
</div>
```

## 主な機能

### 1. 遅延ロード（Lazy Loading）
- 統一ドキュメントセクションが初めて展開されたときにドキュメントを読み込む
- ページ読み込み時のパフォーマンスを向上

### 2. モーダルベースの操作
- フォルダ作成
- ファイルアップロード
- 名前変更
- プロパティ表示

### 3. コンテキストメニュー
- 右クリックで操作メニューを表示
- フォルダ/ファイル別に適切なメニュー項目を表示

### 4. 表示モード
- リスト表示: テーブル形式で詳細情報を表示
- グリッド表示: カード形式で視覚的に表示

### 5. 検索機能
- ファイル名・フォルダ名での検索
- リアルタイム検索結果表示

### 6. エラーハンドリング
- ネットワークエラー時の再試行機能
- 最大3回まで自動再試行
- 指数バックオフによる遅延

## 統一されたパターン

契約書のドキュメント管理は、以下の既存実装と同じパターンを使用しています：

1. **ライフライン設備ドキュメント管理**
   - `LifelineDocumentManager.js`
   - `lifeline-document-manager.blade.php`
   - `LifelineDocumentController.php`

2. **メンテナンス履歴ドキュメント管理**
   - `MaintenanceDocumentManager.js`
   - `maintenance-document-manager.blade.php`
   - `MaintenanceDocumentController.php`

3. **契約書ドキュメント管理**
   - `ContractDocumentManager.js`
   - `contract-document-manager.blade.php`
   - `ContractDocumentController.php`

## データベース構造

### document_folders テーブル
- `id`: 主キー
- `facility_id`: 施設ID
- `category`: カテゴリ（'contracts'）
- `parent_id`: 親フォルダID（NULL可）
- `name`: フォルダ名
- `created_by`: 作成者ID
- `created_at`, `updated_at`: タイムスタンプ

### document_files テーブル
- `id`: 主キー
- `facility_id`: 施設ID
- `category`: カテゴリ（'contracts'）
- `folder_id`: フォルダID（NULL可）
- `original_name`: 元のファイル名
- `stored_name`: 保存ファイル名
- `file_path`: ファイルパス
- `file_size`: ファイルサイズ
- `mime_type`: MIMEタイプ
- `uploaded_by`: アップロード者ID
- `created_at`, `updated_at`: タイムスタンプ

## セキュリティ

### 認可チェック
- すべての操作でポリシーベースの認可チェックを実施
- `ContractPolicy`を使用した権限管理

### ファイルバリデーション
- ファイルサイズ制限: 最大50MB
- ファイルタイプ制限: 設定可能
- ファイル名のサニタイズ

### アクティビティログ
- すべての操作をログに記録
- 誰が、いつ、何をしたかを追跡可能

## 使用方法

### ユーザー操作

1. **ドキュメントセクションを開く**
   - 「ドキュメントを表示」ボタンをクリック
   - 初回展開時にドキュメントを自動読み込み

2. **フォルダを作成**
   - 「新しいフォルダ」ボタンをクリック
   - モーダルでフォルダ名を入力
   - 「作成」ボタンをクリック

3. **ファイルをアップロード**
   - 「ファイルアップロード」ボタンをクリック
   - モーダルでファイルを選択
   - 「アップロード」ボタンをクリック

4. **フォルダを開く**
   - フォルダ名をクリック
   - パンくずナビゲーションで階層を移動

5. **ファイルをダウンロード**
   - ファイル名をクリック
   - または右クリックメニューから「ダウンロード」を選択

6. **名前を変更**
   - 右クリックメニューから「名前変更」を選択
   - モーダルで新しい名前を入力

7. **削除**
   - 右クリックメニューから「削除」を選択
   - 確認ダイアログで「OK」をクリック

### 開発者向け

#### 新しいカテゴリの追加

契約書と同じパターンで新しいカテゴリを追加する場合：

1. **Bladeコンポーネントを作成**
   ```bash
   cp resources/views/components/contract-document-manager.blade.php \
      resources/views/components/new-category-document-manager.blade.php
   ```

2. **JavaScriptマネージャーを作成**
   ```bash
   cp resources/js/modules/ContractDocumentManager.js \
      resources/js/modules/NewCategoryDocumentManager.js
   ```

3. **コントローラーを作成**
   ```bash
   cp app/Http/Controllers/ContractDocumentController.php \
      app/Http/Controllers/NewCategoryDocumentController.php
   ```

4. **サービスを作成**
   ```bash
   cp app/Services/ContractDocumentService.php \
      app/Services/NewCategoryDocumentService.php
   ```

5. **ルートを追加**
   ```php
   Route::prefix('new-category-documents')->name('new-category-documents.')->group(function () {
       // ルート定義
   });
   ```

6. **app-unified.jsにインポート**
   ```javascript
   import NewCategoryDocumentManager from './modules/NewCategoryDocumentManager.js';
   window.NewCategoryDocumentManager = NewCategoryDocumentManager;
   ```

## トラブルシューティング

### ドキュメントが表示されない

1. **ブラウザコンソールを確認**
   - F12キーを押してデベロッパーツールを開く
   - Consoleタブでエラーメッセージを確認

2. **ネットワークタブを確認**
   - APIリクエストが正常に送信されているか確認
   - レスポンスステータスコードを確認

3. **権限を確認**
   - ユーザーに適切な権限があるか確認
   - `canEditFacility()`メソッドの戻り値を確認

### ファイルアップロードが失敗する

1. **ファイルサイズを確認**
   - 最大50MBまで
   - `php.ini`の`upload_max_filesize`と`post_max_size`を確認

2. **ストレージ権限を確認**
   ```bash
   chmod -R 775 storage/app/public
   php artisan storage:link
   ```

3. **ログを確認**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### モーダルが表示されない

1. **Bootstrapが読み込まれているか確認**
   - `window.bootstrap`がグローバルに存在するか確認

2. **モーダルIDが重複していないか確認**
   - 各カテゴリで一意のIDを使用

3. **z-indexを確認**
   - モーダルが他の要素の背後に隠れていないか確認

## まとめ

契約書のドキュメント管理システムは、モーダルベースの統一されたパターンで完全に実装されています。ライフライン設備やメンテナンス履歴と同じ使い勝手で、直感的な操作が可能です。

### 主な利点

1. **統一されたUI/UX**: すべてのドキュメント管理で同じ操作感
2. **モーダルベース**: ページ遷移なしでスムーズな操作
3. **遅延ロード**: 初期ページ読み込みの高速化
4. **エラーハンドリング**: ネットワークエラー時の自動再試行
5. **セキュリティ**: ポリシーベースの権限管理
6. **拡張性**: 新しいカテゴリの追加が容易

### 今後の改善案

1. **ドラッグ&ドロップ**: ファイルのドラッグ&ドロップアップロード
2. **一括操作**: 複数ファイルの一括削除・移動
3. **プレビュー機能**: PDFファイルのプレビュー表示
4. **バージョン管理**: ファイルのバージョン履歴管理
5. **共有機能**: 他のユーザーとのファイル共有
6. **タグ機能**: ファイルへのタグ付けと検索

## 関連ドキュメント

- [ドキュメント管理システム概要](./README.md)
- [ライフライン設備ドキュメント管理](./lifeline-equipment/document-management-guide.md)
- [メンテナンス履歴ドキュメント管理](./maintenance-history/document-management-implementation.md)
- [モーダル実装ガイドライン](../.kiro/steering/modal-implementation-guide.md)
- [ファイルハンドリングガイドライン](../.kiro/steering/file-handling.md)
