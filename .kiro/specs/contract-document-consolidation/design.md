# 契約書ドキュメント統合 - 設計書

## Overview

本設計書では、契約書タブの各サブタブに分散しているドキュメント管理セクションを、契約書タブのメインレベルに統合する実装方法を定義します。統合により、すべての契約書関連ドキュメントを一箇所で管理でき、ユーザビリティが向上します。

既存の`ContractDocumentManager`クラスと`contract-document-manager`コンポーネントを活用し、最小限の変更で統合を実現します。

## Architecture

### システム構成

```
契約書タブ (index.blade.php)
├── 統一ドキュメント管理セクション (新規追加)
│   ├── 折りたたみボタン
│   ├── ドキュメント管理コンポーネント
│   │   ├── ツールバー (フォルダ作成、ファイルアップロード、検索)
│   │   ├── パンくずナビゲーション
│   │   ├── ドキュメント一覧 (リスト/グリッド表示)
│   │   └── モーダル (フォルダ作成、ファイルアップロード、名前変更、プロパティ)
│   └── ContractDocumentManager (JavaScript)
├── サブタブナビゲーション
│   ├── 給食タブ
│   ├── 駐車場タブ
│   └── その他タブ
└── サブタブコンテンツ
    ├── 給食契約書データ (ドキュメントセクション削除)
    ├── 駐車場契約書データ (ドキュメントセクション削除)
    └── その他契約書データ (ドキュメントセクション削除)
```

### データフロー

```
ユーザー操作
    ↓
ContractDocumentManager (JavaScript)
    ↓
ContractDocumentController (API)
    ↓
ContractDocumentService
    ↓
DocumentService / FileHandlingService
    ↓
データベース / ストレージ
```

## Components and Interfaces

### 1. Bladeビューファイル

#### resources/views/facilities/contracts/index.blade.php

**変更内容:**
- サブタブナビゲーションの直前に統一ドキュメント管理セクションを追加
- 各サブタブ内のドキュメント管理セクションを削除
- 折りたたみ機能を実装

**新規追加セクション:**
```blade
<!-- 統一ドキュメント管理セクション -->
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

**削除対象:**
- `#others-documents-section` (その他契約書のドキュメントセクション)
- `#meal-service-documents-section` (給食契約書のドキュメントセクション)
- `#parking-documents-section` (駐車場契約書のドキュメントセクション)

### 2. Bladeコンポーネント

#### resources/views/components/contract-document-manager.blade.php

**変更内容:**
- カテゴリ固有のID接尾辞を削除し、単一インスタンスとして動作するように修正
- `data-contract-category`属性を削除
- すべてのID属性から`-{{ $category }}`接尾辞を削除

**変更前:**
```blade
<div class="document-management" data-facility-id="{{ $facility->id }}" data-contract-category="{{ $category }}" id="document-management-container-{{ $category }}">
```

**変更後:**
```blade
<div class="document-management" data-facility-id="{{ $facility->id }}" id="contract-document-management-container">
```

### 3. JavaScriptクラス

#### resources/js/modules/ContractDocumentManager.js

**変更内容:**
- コンストラクタからカテゴリパラメータを削除
- カテゴリ固有のID接尾辞を削除
- 単一インスタンスとして動作するように修正

**変更前:**
```javascript
constructor(facilityId, category) {
    this.facilityId = facilityId;
    this.category = category;
    // ...
}
```

**変更後:**
```javascript
constructor(facilityId) {
    this.facilityId = facilityId;
    this.category = 'contracts';
    // ...
}
```

**要素キャッシュの変更:**
```javascript
cacheElements() {
    this.elements = {
        container: document.getElementById('contract-document-management-container'),
        createFolderBtn: document.getElementById('create-folder-btn-contracts'),
        uploadFileBtn: document.getElementById('upload-file-btn-contracts'),
        // ... (すべてのIDから-{{ $category }}を削除)
    };
}
```

### 4. CSSスタイル

#### resources/css/contract-document-management.css

**新規追加スタイル:**
```css
/* 統一ドキュメント管理セクション */
.unified-contract-documents-section {
    margin-bottom: 2rem;
}

.unified-documents-toggle {
    transition: all 0.3s ease;
    border-radius: 6px;
    font-weight: 500;
}

.unified-documents-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.unified-documents-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

/* 折りたたみアニメーション */
#unified-documents-section.collapsing {
    transition: height 0.35s ease;
}

#unified-documents-section.collapse.show {
    animation: slideDown 0.35s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Modal stacking fixes */
#unified-documents-section {
    overflow: visible;
}

.modal-backdrop {
    z-index: 2000 !important;
}

.modal {
    z-index: 2010 !important;
}
```

**削除対象スタイル:**
- `#others-documents-section`関連スタイル
- `#meal-service-documents-section`関連スタイル
- `#parking-documents-section`関連スタイル

## Data Models

### DocumentFolder

既存のモデルを使用。変更なし。

```php
class DocumentFolder extends Model
{
    protected $fillable = [
        'facility_id',
        'parent_id',
        'category',
        'name',
        'path',
        'created_by',
    ];
    
    // スコープ
    public function scopeContracts($query)
    {
        return $query->where('category', 'contracts');
    }
}
```

### DocumentFile

既存のモデルを使用。変更なし。

```php
class DocumentFile extends Model
{
    protected $fillable = [
        'facility_id',
        'folder_id',
        'category',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'uploaded_by',
    ];
    
    // スコープ
    public function scopeContracts($query)
    {
        return $query->where('category', 'contracts');
    }
}
```

## Error Handling

### エラーハンドリング戦略

1. **ファイルアップロードエラー**
   - ファイルサイズ超過: "ファイルサイズが50MBを超えています。"
   - ファイルタイプ不正: "このファイルタイプはアップロードできません。"
   - ストレージエラー: "ファイルの保存に失敗しました。"

2. **フォルダ操作エラー**
   - 名前重複: "同じ名前のフォルダが既に存在します。"
   - 権限不足: "フォルダを作成する権限がありません。"
   - 親フォルダ不存在: "親フォルダが見つかりません。"

3. **ネットワークエラー**
   - タイムアウト: "通信がタイムアウトしました。再試行してください。"
   - 接続エラー: "サーバーに接続できません。"

4. **認証・認可エラー**
   - 未認証: "ログインが必要です。"
   - 権限不足: "この操作を実行する権限がありません。"

### エラー表示方法

```javascript
showError(message) {
    this.hideLoading();
    this.elements.errorText.textContent = message;
    this.elements.errorMessage.classList.remove('d-none');
    this.elements.emptyState.classList.add('d-none');
    this.elements.documentList.classList.add('d-none');
}
```

## Testing Strategy

### 単体テスト

1. **ContractDocumentServiceTest**
   - `testGetCategoryDocuments()`: ドキュメント一覧取得
   - `testUploadCategoryFile()`: ファイルアップロード
   - `testCreateCategoryFolder()`: フォルダ作成
   - `testGetCategoryStats()`: 統計情報取得

2. **ContractDocumentControllerTest**
   - `testIndex()`: ドキュメント一覧API
   - `testUploadFile()`: ファイルアップロードAPI
   - `testCreateFolder()`: フォルダ作成API
   - `testDeleteFile()`: ファイル削除API
   - `testDeleteFolder()`: フォルダ削除API

### 統合テスト

1. **ContractDocumentIntegrationTest**
   - `testUnifiedDocumentSectionDisplay()`: 統一セクション表示
   - `testSubTabDocumentSectionsRemoved()`: サブタブセクション削除確認
   - `testDocumentOperationsFromUnifiedSection()`: 統一セクションからの操作
   - `testExistingDocumentsAccessible()`: 既存ドキュメントアクセス確認

### E2Eテスト

1. **契約書タブ表示テスト**
   - 統一ドキュメントセクションが表示される
   - サブタブ内にドキュメントセクションが表示されない
   - 折りたたみボタンが機能する

2. **ドキュメント操作テスト**
   - フォルダ作成が成功する
   - ファイルアップロードが成功する
   - ファイルダウンロードが成功する
   - ファイル削除が成功する
   - フォルダ削除が成功する

3. **レスポンシブテスト**
   - モバイルデバイスで正常に表示される
   - タブレットデバイスで正常に表示される
   - デスクトップデバイスで正常に表示される

## Performance Considerations

### 最適化戦略

1. **初期ロード最適化**
   - ドキュメントセクションを折りたたんだ状態で初期表示
   - 展開時に初めてドキュメント一覧を読み込む（遅延ロード）
   - ページネーションで大量ファイルに対応

2. **キャッシング**
   - ブラウザキャッシュを活用
   - APIレスポンスのキャッシュ（短時間）
   - 画像サムネイルのキャッシュ

3. **非同期処理**
   - ファイルアップロードの非同期処理
   - プログレスバーの表示
   - バックグラウンドでの処理完了通知

### パフォーマンス目標

- 初期表示: 2秒以内
- ドキュメント一覧読み込み: 1秒以内
- ファイルアップロード: 10MB/秒以上
- 折りたたみ/展開: 0.5秒以内

## Security Considerations

### セキュリティ対策

1. **認証・認可**
   - すべてのAPI呼び出しで認証チェック
   - ポリシーベースの認可チェック
   - CSRF トークンの検証

2. **ファイルアップロード**
   - ファイルタイプの検証
   - ファイルサイズの制限（50MB）
   - ファイル名のサニタイズ
   - ウイルススキャン（オプション）

3. **XSS対策**
   - ユーザー入力のエスケープ
   - HTMLタグの無害化
   - Content Security Policy (CSP)

4. **パストラバーサル対策**
   - ファイルパスの検証
   - 相対パスの禁止
   - ホワイトリスト方式のパス検証

## Accessibility

### アクセシビリティ対応

1. **ARIA属性**
   - `aria-expanded`: 折りたたみ状態の表示
   - `aria-controls`: 制御対象の指定
   - `aria-labelledby`: ラベルの関連付け
   - `aria-hidden`: 非表示要素の指定

2. **キーボード操作**
   - Tab キーでフォーカス移動
   - Enter キーで選択
   - Escape キーでモーダルを閉じる
   - 矢印キーでリスト内移動

3. **スクリーンリーダー対応**
   - 適切なラベルの設定
   - 状態変化の通知
   - エラーメッセージの読み上げ

4. **視覚的配慮**
   - 十分なコントラスト比
   - フォーカスインジケーター
   - アイコンとテキストの併用

## Migration Strategy

### 段階的移行

1. **Phase 1: 統一セクション追加**
   - 統一ドキュメント管理セクションを追加
   - 既存のサブタブセクションは残す
   - 両方が動作することを確認

2. **Phase 2: サブタブセクション削除**
   - 各サブタブのドキュメントセクションを削除
   - CSSスタイルのクリーンアップ
   - JavaScriptの最適化

3. **Phase 3: 検証とリリース**
   - 全機能の動作確認
   - パフォーマンステスト
   - ユーザー受け入れテスト

### ロールバック計画

問題が発生した場合:
1. 統一セクションを非表示にする
2. サブタブセクションを復元する
3. 原因を調査し修正する

## Implementation Notes

### 実装時の注意点

1. **既存データの互換性**
   - 既存のドキュメントは`category='contracts'`で保存されている
   - データベースマイグレーションは不要
   - 既存のフォルダ構造は維持される

2. **JavaScript初期化**
   - DOMContentLoaded後に初期化
   - 既存インスタンスの重複チェック
   - グローバル変数の衝突回避

3. **モーダルのz-index問題**
   - 折りたたみ領域内のモーダルはz-indexが低くなる可能性
   - モーダルhoisting処理を実装
   - `overflow: visible`を設定

4. **レスポンシブデザイン**
   - モバイルでは折りたたみボタンのテキストを非表示
   - タブレットでは適切な余白を確保
   - デスクトップでは最大幅を制限

5. **パフォーマンス**
   - 初期状態は折りたたまれているため、ドキュメント読み込みは遅延
   - 展開時に初めてAPIを呼び出す
   - ページネーションで大量ファイルに対応

## Deployment Checklist

### デプロイ前チェックリスト

- [ ] すべてのテストが成功している
- [ ] コードレビューが完了している
- [ ] ドキュメントが更新されている
- [ ] パフォーマンステストが完了している
- [ ] セキュリティレビューが完了している
- [ ] アクセシビリティチェックが完了している
- [ ] ブラウザ互換性テストが完了している
- [ ] モバイルデバイステストが完了している
- [ ] ロールバック計画が準備されている
- [ ] ステージング環境でのテストが完了している

### デプロイ後確認事項

- [ ] 統一ドキュメントセクションが表示される
- [ ] サブタブ内のドキュメントセクションが削除されている
- [ ] 既存ドキュメントにアクセスできる
- [ ] ファイルアップロードが動作する
- [ ] フォルダ作成が動作する
- [ ] ファイル削除が動作する
- [ ] フォルダ削除が動作する
- [ ] エラーハンドリングが適切に動作する
- [ ] パフォーマンスが目標値を満たしている
- [ ] ログにエラーが記録されていない
