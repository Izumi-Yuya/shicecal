# 防災・防犯タブ ドキュメント管理機能実装ガイド

## 概要
防災・防犯タブの各サブタブ（防犯カメラ・電子錠、消防・防災）にライフライン設備と同様のドキュメント管理機能を追加しました。

## 実装内容

### 1. ビューファイルの更新
**ファイル**: `resources/views/facilities/security-disaster/index.blade.php`

#### 追加された機能
- 各サブタブにドキュメント管理ボタンを追加
- ドキュメント管理モーダルを2つ追加（防犯カメラ・電子錠用、消防・防災用）

#### 変更箇所

##### 防犯カメラ・電子錠タブ
```blade
<div class="d-flex align-items-center gap-2">
    <!-- ドキュメント管理ボタン -->
    <button type="button" 
            class="btn btn-outline-primary btn-sm" 
            data-bs-toggle="modal"
            data-bs-target="#camera-lock-documents-modal"
            title="防犯カメラ・電子錠ドキュメント管理">
        <i class="fas fa-folder-open me-1"></i>
        <span class="d-none d-md-inline">ドキュメント</span>
    </button>
    <!-- 既存の編集ボタン -->
</div>
```

##### 消防・防災タブ
```blade
<div class="d-flex align-items-center gap-2">
    <!-- ドキュメント管理ボタン -->
    <button type="button" 
            class="btn btn-outline-primary btn-sm" 
            data-bs-toggle="modal"
            data-bs-target="#fire-disaster-documents-modal"
            title="消防・防災ドキュメント管理">
        <i class="fas fa-folder-open me-1"></i>
        <span class="d-none d-md-inline">ドキュメント</span>
    </button>
    <!-- 既存の編集ボタン -->
</div>
```

##### ドキュメント管理モーダル
```blade
<!-- 防犯カメラ・電子錠ドキュメント管理モーダル -->
<div class="modal fade" id="camera-lock-documents-modal" tabindex="-1" aria-labelledby="camera-lock-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="camera-lock-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>防犯カメラ・電子錠ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="security_disaster"
                    categoryName="防犯カメラ・電子錠"
                    subcategory="camera_lock"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- 消防・防災ドキュメント管理モーダル -->
<div class="modal fade" id="fire-disaster-documents-modal" tabindex="-1" aria-labelledby="fire-disaster-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="fire-disaster-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>消防・防災ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="security_disaster"
                    categoryName="消防・防災"
                    subcategory="fire_disaster"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
```

### 2. 既存のサポート機能

#### LifelineDocumentService
**ファイル**: `app/Services/LifelineDocumentService.php`

`security_disaster`カテゴリは既にサポートされています：

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

#### LifelineEquipmentモデル
**ファイル**: `app/Models/LifelineEquipment.php`

`security_disaster`カテゴリは既にCATEGORIESに含まれています：

```php
public const CATEGORIES = [
    'electrical' => '電気',
    'gas' => 'ガス',
    'water' => '水道',
    'elevator' => 'エレベーター',
    'hvac_lighting' => '空調・照明',
    'security_disaster' => '防犯・防災',
];
```

#### ルート設定
**ファイル**: `routes/web.php`

ライフラインドキュメント管理のルートは既に設定されており、`security_disaster`カテゴリもサポートされています：

```php
Route::prefix('lifeline-documents')->name('lifeline-documents.')->group(function () {
    Route::get('/{category}', [LifelineDocumentController::class, 'index'])->name('index');
    Route::post('/{category}/upload', [LifelineDocumentController::class, 'uploadFile'])->name('upload');
    Route::post('/{category}/folders', [LifelineDocumentController::class, 'createFolder'])->name('create-folder');
    // ... その他のルート
});
```

### 3. 使用されるコンポーネント

#### lifeline-document-manager
**ファイル**: `resources/views/components/lifeline-document-manager.blade.php`

このコンポーネントは以下の機能を提供します：
- フォルダ作成
- ファイルアップロード
- ファイル・フォルダの表示（リスト/グリッド）
- ファイル・フォルダの編集・削除
- ファイルのダウンロード
- 検索機能
- ブレッドクラムナビゲーション

## 使用方法

### ユーザー操作フロー

1. **施設詳細画面を開く**
   - 施設一覧から施設を選択

2. **防犯・防災タブを選択**
   - タブメニューから「防犯・防災」をクリック

3. **サブタブを選択**
   - 「防犯カメラ・電子錠」または「消防・防災」を選択

4. **ドキュメント管理を開く**
   - 「ドキュメント」ボタンをクリック
   - モーダルウィンドウが開く

5. **ドキュメント操作**
   - **フォルダ作成**: 「新しいフォルダ」ボタンをクリック
   - **ファイルアップロード**: 「ファイルアップロード」ボタンをクリック
   - **ファイル表示**: リスト表示またはグリッド表示を選択
   - **ファイルダウンロード**: ファイル名をクリック
   - **ファイル編集**: ファイルの「...」メニューから操作を選択

### フォルダ構造の推奨

#### 防犯カメラ・電子錠
```
防犯・防災設備/
├── 防犯カメラ/
│   ├── 配置図/
│   ├── 保守記録/
│   └── 取扱説明書/
└── 電子錠/
    ├── 配置図/
    ├── 保守記録/
    └── 取扱説明書/
```

#### 消防・防災
```
防犯・防災設備/
├── 消防/
│   ├── 訓練報告書/
│   ├── 点検報告書/
│   └── 証明書類/
└── 防災/
    ├── ハザードマップ/
    ├── 避難経路図/
    ├── 訓練報告書/
    └── 備蓄品リスト/
```

## 技術的な詳細

### データベース構造

ドキュメントは`document_files`テーブルと`document_folders`テーブルに保存されます：

```sql
-- document_folders テーブル
- id
- facility_id (施設ID)
- parent_id (親フォルダID、nullの場合はルート)
- name (フォルダ名)
- created_by
- updated_by
- timestamps

-- document_files テーブル
- id
- facility_id (施設ID)
- folder_id (フォルダID)
- name (ファイル名)
- original_name (元のファイル名)
- file_path (ストレージパス)
- file_size (ファイルサイズ)
- mime_type (MIMEタイプ)
- created_by
- updated_by
- timestamps
```

### ストレージパス

ファイルは以下のパスに保存されます：
```
storage/app/public/documents/{facility_id}/lifeline/{category}/{file_name}
```

例：
```
storage/app/public/documents/123/lifeline/security_disaster/report_20241014.pdf
```

### JavaScript処理

ドキュメント管理のJavaScript処理は以下のファイルで実装されています：
- `resources/js/modules/LifelineDocumentManager.js`
- `resources/js/shared/ApiClient.js`

## セキュリティ

### 認証・認可
- すべてのドキュメント操作は認証が必要
- ポリシーベースの認可チェック（LifelineEquipmentPolicy）
- 施設へのアクセス権限チェック

### ファイルバリデーション
- ファイルタイプの検証
- ファイルサイズの制限（最大10MB）
- ファイル名のサニタイズ

### アクティビティログ
- すべてのドキュメント操作はActivityLogServiceで記録
- 作成者・更新者の追跡

## トラブルシューティング

### モーダルが開かない
1. ブラウザのコンソールでJavaScriptエラーを確認
2. Bootstrap 5が正しく読み込まれているか確認
3. モーダルのIDが正しく設定されているか確認

### ファイルアップロードが失敗する
1. ファイルサイズが10MB以下か確認
2. ストレージディレクトリの権限を確認
3. `php artisan storage:link`が実行されているか確認
4. サーバーログ（`storage/logs/laravel.log`）を確認

### ドキュメント一覧が表示されない
1. ネットワークタブでAPIリクエストを確認
2. 施設へのアクセス権限があるか確認
3. LifelineEquipmentレコードが存在するか確認

## 今後の拡張案

### subcategoryパラメータの活用
現在、subcategoryパラメータは渡されていますが、まだ完全には活用されていません。
将来的には以下のような拡張が可能です：

1. **サブカテゴリ別のルートフォルダ**
   - 防犯カメラ・電子錠と消防・防災で完全に分離されたフォルダ構造

2. **サブカテゴリ別のデフォルトフォルダ**
   - 各サブカテゴリに最適化されたフォルダテンプレート

3. **サブカテゴリ別の権限管理**
   - より細かい粒度での権限制御

### その他の改善案
- ファイルプレビュー機能
- バージョン管理機能
- ファイル共有機能
- 一括ダウンロード機能
- ファイルタグ機能

## まとめ

防災・防犯タブにライフライン設備と同様のドキュメント管理機能を追加しました。
既存のLifelineDocumentServiceとコンポーネントを活用することで、最小限の変更で実装できました。

ユーザーは各サブタブから直接ドキュメント管理モーダルを開き、
フォルダ作成、ファイルアップロード、ファイル管理などの操作を行うことができます。
