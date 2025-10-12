# ライフライン設備ドキュメント管理機能

## 概要

ライフライン設備の各カテゴリ（電気、ガス、水道、エレベーター、空調・照明、防犯・防災）に対応したドキュメント管理機能です。過去分の報告書や関連資料を整理して保存できます。

## 主な機能

### 1. カテゴリ別ドキュメント管理
- 各ライフライン設備カテゴリごとに独立したドキュメント領域
- 自動的にカテゴリフォルダとデフォルトサブフォルダを作成
- カテゴリ間でのドキュメント混在を防止

### 2. フォルダ階層管理
- 無制限の階層フォルダ作成
- フォルダの作成、名前変更、削除
- パンくずナビゲーション
- フォルダ間でのファイル移動

### 3. ファイル管理
- 複数ファイル形式対応（PDF、Word、Excel、画像等）
- ファイルのアップロード、ダウンロード、削除
- ファイル名変更
- ファイルサイズ制限（最大10MB）

### 4. 検索・フィルタ機能
- ファイル名による検索
- ファイル形式によるフィルタ
- リアルタイム検索

### 5. 表示モード
- リスト表示：詳細情報を表形式で表示
- グリッド表示：カード形式で視覚的に表示

## 使用方法

### 基本的な使い方

1. **施設詳細画面**から対象のライフライン設備タブを選択
2. **関連ドキュメント**セクションでファイル管理を行う
3. 必要に応じてフォルダを作成してファイルを整理

### ファイルアップロード

```html
<!-- 編集権限がある場合のみ表示 -->
<button onclick="LifelineDocumentManager.showUploadModal('electrical')">
    ファイルアップロード
</button>
```

### フォルダ作成

```html
<!-- 編集権限がある場合のみ表示 -->
<button onclick="LifelineDocumentManager.showCreateFolderModal('electrical')">
    フォルダ作成
</button>
```

## 技術仕様

### アーキテクチャ

```
LifelineDocumentService
├── DocumentService (既存のドキュメント管理)
├── FileHandlingService (ファイル処理)
└── ActivityLogService (操作ログ)
```

### データベース構造

既存の `document_folders` と `document_files` テーブルを使用：

```sql
-- フォルダ管理
document_folders
├── facility_id (施設ID)
├── parent_id (親フォルダID)
├── name (フォルダ名)
├── path (フォルダパス)
└── created_by (作成者)

-- ファイル管理
document_files
├── facility_id (施設ID)
├── folder_id (フォルダID)
├── original_name (元ファイル名)
├── stored_name (保存ファイル名)
├── file_path (ファイルパス)
├── file_size (ファイルサイズ)
├── mime_type (MIMEタイプ)
├── file_extension (拡張子)
└── uploaded_by (アップロード者)
```

### API エンドポイント

```php
// ドキュメント一覧取得
GET /facilities/{facility}/lifeline-documents/{category}

// ファイルアップロード
POST /facilities/{facility}/lifeline-documents/{category}/upload

// フォルダ作成
POST /facilities/{facility}/lifeline-documents/{category}/folders

// フォルダ操作
PUT /facilities/{facility}/lifeline-documents/{category}/folders/{folder}
DELETE /facilities/{facility}/lifeline-documents/{category}/folders/{folder}

// ファイル操作
PUT /facilities/{facility}/lifeline-documents/{category}/files/{file}
DELETE /facilities/{facility}/lifeline-documents/{category}/files/{file}
PATCH /facilities/{facility}/lifeline-documents/{category}/files/{file}/move

// 統計・検索
GET /facilities/{facility}/lifeline-documents/{category}/stats
GET /facilities/{facility}/lifeline-documents/{category}/search
```

## 実装例

### Bladeコンポーネントの使用

```blade
<x-lifeline-document-manager 
    :facility="$facility" 
    category="electrical"
    category-name="電気設備"
    height="500px"
    :show-upload="true"
    :show-create-folder="true"
    allowed-file-types="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
    max-file-size="10MB"
/>
```

### JavaScriptでの初期化

```javascript
// 自動初期化（コンポーネント使用時）
document.addEventListener('DOMContentLoaded', function() {
    LifelineDocumentManager.init('electrical', {
        facilityId: 123,
        canEdit: true,
        allowedFileTypes: 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        maxFileSize: '10MB'
    });
});
```

### サービス層での使用

```php
use App\Services\LifelineDocumentService;

class SomeController extends Controller
{
    public function __construct(
        private LifelineDocumentService $lifelineDocumentService
    ) {}

    public function uploadDocument(Request $request, Facility $facility)
    {
        $result = $this->lifelineDocumentService->uploadCategoryFile(
            $facility,
            'electrical',
            $request->file('document'),
            auth()->user()
        );

        return response()->json($result);
    }
}
```

## デフォルトフォルダ構成

各カテゴリに自動作成されるサブフォルダ：

- **点検報告書** - 定期点検の報告書
- **保守記録** - 保守・メンテナンス記録
- **取扱説明書** - 機器の取扱説明書
- **証明書類** - 各種証明書・認定書
- **過去分報告書** - 過去の報告書アーカイブ

## セキュリティ

### 認証・認可
- 施設の表示権限：ドキュメント閲覧可能
- 施設の編集権限：ドキュメント管理（アップロード、削除等）可能
- ポリシーベースの権限チェック

### ファイル検証
- 許可されたファイル形式のみアップロード可能
- ファイルサイズ制限（デフォルト10MB）
- MIMEタイプと拡張子の両方をチェック

### データ保護
- ファイルは施設ごとに分離保存
- 物理ファイルパスの直接アクセス防止
- アクティビティログによる操作追跡

## パフォーマンス最適化

### フロントエンド
- 仮想スクロール対応（大量ファイル時）
- 遅延読み込み（Lazy Loading）
- キャッシュ機能
- 検索デバウンス（500ms）

### バックエンド
- ページネーション（デフォルト50件）
- データベースインデックス最適化
- ファイル統計のキャッシュ（5分）
- N+1クエリ対策

## 拡張性

### 新しいカテゴリの追加

```php
// LifelineDocumentService.php
const CATEGORY_FOLDER_MAPPING = [
    'electrical' => '電気設備',
    'gas' => 'ガス設備',
    'water' => '水道設備',
    'elevator' => 'エレベーター設備',
    'hvac_lighting' => '空調・照明設備',
    'security_disaster' => '防犯・防災設備',
    'new_category' => '新しいカテゴリ', // 追加
];
```

### カスタムサブフォルダの設定

```php
// カテゴリ固有のサブフォルダ設定
const CATEGORY_SPECIFIC_SUBFOLDERS = [
    'electrical' => [
        'inspection_reports' => '点検報告書',
        'pas_documents' => 'PAS関連資料',
        'cubicle_manuals' => 'キュービクル取扱説明書',
    ],
];
```

## トラブルシューティング

### よくある問題

1. **ファイルアップロードが失敗する**
   - ファイルサイズ制限を確認
   - 許可されたファイル形式か確認
   - ストレージ容量を確認

2. **フォルダが削除できない**
   - フォルダ内にファイルやサブフォルダがないか確認
   - 権限があるか確認

3. **検索結果が表示されない**
   - 検索クエリが短すぎないか確認（最低1文字）
   - 対象カテゴリにファイルが存在するか確認

### ログ確認

```bash
# アプリケーションログ
tail -f storage/logs/laravel.log | grep "LifelineDocument"

# アクティビティログ
# データベースのactivity_logテーブルを確認
```

## 今後の拡張予定

- [ ] ファイルプレビュー機能
- [ ] バージョン管理機能
- [ ] 一括ダウンロード機能
- [ ] ファイル共有機能
- [ ] 自動分類機能（AI活用）
- [ ] モバイルアプリ対応