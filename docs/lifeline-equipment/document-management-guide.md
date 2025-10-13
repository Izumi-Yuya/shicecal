# ライフライン設備ドキュメント管理ガイド

## 概要

ライフライン設備のドキュメント管理機能は、各設備カテゴリ（電気、ガス、水道、エレベーター、空調・照明、防犯・防災）ごとに、点検報告書や保守記録などのファイルを階層的に管理する機能です。

## 主要機能

### 1. フォルダ階層管理
- カテゴリごとにルートフォルダが自動作成される
- サブフォルダを無制限に作成可能
- フォルダ名の変更・削除が可能

### 2. ファイル管理
- PDFファイルのアップロード（PDFのみ対応）
- 複数ファイルの同時アップロード（最大10MB/ファイル）
- ファイルのダウンロード、名前変更、移動、削除

### 3. ナビゲーション
- パンくずナビゲーションでフォルダ階層を表示
- フォルダをクリックして中身を表示
- リスト表示とグリッド表示の切り替え

### 4. 検索
- ファイル名・フォルダ名での検索

## アーキテクチャ

### フロントエンド

#### コンポーネント
- **Bladeコンポーネント**: `resources/views/components/lifeline-document-manager.blade.php`
- **JavaScriptクラス**: `resources/js/modules/LifelineDocumentManager.js`
- **CSS**: `resources/css/lifeline-document-management.css`

#### 状態管理
```javascript
this.state = {
  currentFolder: null,      // 現在表示中のフォルダID
  viewMode: 'list',         // 表示モード（list/grid）
  selectedItems: new Set(), // 選択中のアイテム
  loading: false,           // ローディング状態
  error: null,              // エラーメッセージ
  searchQuery: '',          // 検索クエリ
  sortBy: 'name',          // ソート基準
  sortDirection: 'asc'      // ソート方向
};
```

#### 主要メソッド

**初期化**
```javascript
init() {
  // イベントリスナーを設定
  this.setupEventListeners();
  // 初期データを読み込み
  this.loadDocuments();
}
```

**フォルダナビゲーション**
```javascript
navigateToFolder(folderId) {
  // nullまたは'null'文字列をnullに正規化
  const normalizedFolderId = (folderId === 'null' || folderId === null) ? null : folderId;
  this.setState({ currentFolder: normalizedFolderId });
  this.loadDocuments();
}
```

**フォルダ作成**
```javascript
async handleCreateFolder(event) {
  const formData = new FormData(form);
  // 現在のフォルダIDを親フォルダとして追加
  if (this.state.currentFolder) {
    formData.append('parent_folder_id', this.state.currentFolder);
  }
  // APIリクエスト送信
}
```

**ファイルアップロード**
```javascript
async handleUploadFile(event) {
  const formData = new FormData(form);
  // 現在のフォルダIDを追加
  if (this.state.currentFolder) {
    formData.append('folder_id', this.state.currentFolder);
  }
  // APIリクエスト送信
}
```

### バックエンド

#### コントローラー
**LifelineDocumentController** (`app/Http/Controllers/LifelineDocumentController.php`)

主要エンドポイント：
- `GET /facilities/{facility}/lifeline-documents/{category}` - ドキュメント一覧取得
- `POST /facilities/{facility}/lifeline-documents/{category}/upload` - ファイルアップロード
- `POST /facilities/{facility}/lifeline-documents/{category}/folders` - フォルダ作成
- `PUT /facilities/{facility}/lifeline-documents/{category}/folders/{folder}` - フォルダ名変更
- `DELETE /facilities/{facility}/lifeline-documents/{category}/folders/{folder}` - フォルダ削除
- `PUT /facilities/{facility}/lifeline-documents/{category}/files/{file}` - ファイル名変更
- `DELETE /facilities/{facility}/lifeline-documents/{category}/files/{file}` - ファイル削除
- `PUT /facilities/{facility}/lifeline-documents/{category}/files/{file}/move` - ファイル移動

#### サービス層

**LifelineDocumentService** (`app/Services/LifelineDocumentService.php`)

カテゴリとフォルダ名のマッピング：
```php
const CATEGORY_FOLDER_MAPPING = [
    'electrical' => '電気設備',
    'gas' => 'ガス設備',
    'water' => '水道設備',
    'elevator' => 'エレベーター設備',
    'hvac_lighting' => '空調・照明設備',
    'security_disaster' => '防犯・防災設備',
];
```

デフォルトサブフォルダ：
```php
const DEFAULT_SUBFOLDERS = [
    'inspection_reports' => '点検報告書',
    'maintenance_records' => '保守記録',
    'manuals' => '取扱説明書',
    'certificates' => '証明書類',
    'past_reports' => '過去分報告書',
];
```

主要メソッド：
- `getOrCreateCategoryRootFolder()` - カテゴリのルートフォルダを取得または作成
- `getCategoryDocuments()` - カテゴリのドキュメント一覧を取得
- `uploadCategoryFile()` - カテゴリにファイルをアップロード
- `createCategoryFolder()` - カテゴリにフォルダを作成
- `getCategoryStats()` - カテゴリの統計情報を取得

**DocumentService** (`app/Services/DocumentService.php`)

共通ドキュメント管理機能：
- `createFolder()` - フォルダ作成
- `renameFolder()` - フォルダ名変更
- `deleteFolder()` - フォルダ削除
- `uploadFile()` - ファイルアップロード
- `deleteFile()` - ファイル削除
- `renameFile()` - ファイル名変更
- `moveFile()` - ファイル移動
- `getFolderContents()` - フォルダ内容取得

#### データベース構造

**document_folders テーブル**
```sql
CREATE TABLE document_folders (
    id BIGINT PRIMARY KEY,
    facility_id BIGINT NOT NULL,
    parent_id BIGINT NULL,
    name VARCHAR(255) NOT NULL,
    path TEXT NOT NULL,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (parent_id) REFERENCES document_folders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

**document_files テーブル**
```sql
CREATE TABLE document_files (
    id BIGINT PRIMARY KEY,
    facility_id BIGINT NOT NULL,
    folder_id BIGINT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    uploaded_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (folder_id) REFERENCES document_folders(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
```

## 使用方法

### Bladeテンプレートでの使用

```blade
<x-lifeline-document-manager 
    :facility="$facility" 
    category="electrical"
    categoryName="電気設備"
/>
```

### JavaScriptでの初期化

```javascript
// 自動初期化（app-unified.jsで実行）
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('[data-lifeline-category]');
    containers.forEach(container => {
        const facilityId = container.dataset.facilityId;
        const category = container.dataset.lifelineCategory;
        
        const manager = new LifelineDocumentManager(facilityId, category);
        window.shiseCalApp.modules[`lifelineDocumentManager_${category}`] = manager;
    });
});
```

## トラブルシューティング

### フォルダをクリックしても中身が表示されない

**原因**: `navigateToFolder`メソッドが正しく実装されていない

**解決方法**:
1. ブラウザコンソールで`[LifelineDoc]`で始まるログを確認
2. `this.state.currentFolder`が正しく設定されているか確認
3. 静的メソッド`navigateToFolder`がインスタンスメソッドを呼び出しているか確認

### フォルダ内でサブフォルダが作成できない

**原因**: `parent_folder_id`が送信されていない

**解決方法**:
1. `handleCreateFolder`メソッドで`this.state.currentFolder`を確認
2. `formData.append('parent_folder_id', this.state.currentFolder)`が実行されているか確認
3. サーバーログで`parent_folder_id`が受信されているか確認

### デバッグログが表示されない

**原因**: Viteビルド設定で`drop_console: true`が設定されている

**解決方法**:
1. `vite.config.js`で`drop_console: false`に変更
2. `npm run build`を実行
3. ブラウザで強制リロード（Cmd+Shift+R）

### モーダルが表示されない

**原因**: モーダルが折りたたみ領域内にあり、z-indexの問題が発生

**解決方法**:
1. モーダルhoisting処理を実装（モーダルを`<body>`直下に移動）
2. z-indexを強制設定（`.modal-backdrop`: 2000、`.modal`: 2010）
3. 折りたたみ領域に`overflow: visible`を設定

## パフォーマンス最適化

### 大量ファイル対応
- ページネーション実装（1ページ50件）
- 遅延読み込み（Lazy Loading）
- 仮想スクロール（Virtual Scrolling）の検討

### キャッシュ戦略
- ファイルタイプ一覧を5分間キャッシュ
- フォルダ統計情報をキャッシュ
- ブラウザキャッシュの活用

## セキュリティ

### 認証・認可
- すべてのエンドポイントで認証チェック
- ポリシーベースの認可（`LifelineEquipmentPolicy`）
- ファイルアクセス権限の検証

### ファイルバリデーション
- PDFファイルのみ許可
- MIMEタイプチェック（application/pdf）
- ファイル拡張子チェック（.pdf）
- ファイルサイズ制限（10MB）
- 悪意のあるファイル名の無害化

### アクティビティログ
- すべてのファイル操作をログ記録
- ユーザーID、施設ID、操作内容を記録
- 監査証跡の保持

## 今後の改善予定

1. **ドラッグ&ドロップ対応**
   - ファイルのドラッグ&ドロップアップロード
   - フォルダ間のドラッグ&ドロップ移動

2. **プレビュー機能**
   - PDFファイルのインラインプレビュー
   - 画像ファイルのサムネイル表示

3. **バージョン管理**
   - ファイルの履歴管理
   - 以前のバージョンへの復元

4. **共有機能**
   - 外部ユーザーとのファイル共有
   - 共有リンクの生成

5. **一括操作**
   - 複数ファイルの一括ダウンロード
   - 複数ファイルの一括削除

## 関連ドキュメント

- [フォルダナビゲーション修正](./folder-navigation-fix.md)
- [モーダル実装ガイド](../../.kiro/steering/modal-implementation-guide.md)
- [モーダルz-index修正](./modal-zindex-fix-summary.md)
- [状態管理修正](./state-management-fix.md)

## 参考リンク

- [Laravel File Storage](https://laravel.com/docs/9.x/filesystem)
- [Vite Build Configuration](https://vitejs.dev/config/build-options.html)
- [Bootstrap Modals](https://getbootstrap.com/docs/5.1/components/modal/)
