# 契約書ドキュメント管理 - モーダルボタン実装

## 変更内容

契約書のドキュメント管理を、折りたたみセクションからモーダルベースに変更しました。

### 変更前
- 「ドキュメントを表示」ボタンをクリック → 折りたたみセクションが展開
- セクション内にドキュメント管理機能が表示

### 変更後
- 「ドキュメント」ボタンをクリック → モーダルが開く
- モーダル内にドキュメント管理機能が表示

## 実装詳細

### 1. ビューの変更（index.blade.php）

#### 変更前
```blade
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
```

#### 変更後
```blade
<button type="button" 
        class="btn btn-primary btn-sm" 
        id="open-contract-documents-modal-btn"
        data-bs-toggle="modal" 
        data-bs-target="#contract-documents-modal">
    <i class="fas fa-folder-open me-1"></i>
    <span>ドキュメント</span>
</button>

{{-- ファイルの最後に追加 --}}
<div class="modal fade" id="contract-documents-modal" tabindex="-1" aria-labelledby="contract-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contract-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>契約書ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body p-0">
                <x-contract-document-manager 
                    :facility="$facility" 
                    categoryName="契約書"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>閉じる
                </button>
            </div>
        </div>
    </div>
</div>
```

### 2. JavaScriptの変更（ContractDocumentManager.js）

#### setupLazyLoading()メソッドの変更

**変更前**
```javascript
setupLazyLoading() {
  const unifiedSection = document.getElementById('unified-documents-section');

  if (!unifiedSection) {
    console.warn('[ContractDoc] Unified section not found, loading documents immediately');
    this.loadDocuments();
    return;
  }

  console.log('[ContractDoc] Lazy loading enabled - documents will load on first expand');

  // shown.bs.collapseイベントをリッスン
  unifiedSection.addEventListener('shown.bs.collapse', () => {
    if (this.isInitialLoad) {
      console.log('[ContractDoc] First expand detected - loading documents');
      this.isInitialLoad = false;
      this.loadDocuments();
    } else {
      console.log('[ContractDoc] Section expanded - documents already loaded');
    }
  });
}
```

**変更後**
```javascript
setupLazyLoading() {
  const contractModal = document.getElementById('contract-documents-modal');

  if (!contractModal) {
    console.warn('[ContractDoc] Modal not found, loading documents immediately');
    this.loadDocuments();
    return;
  }

  console.log('[ContractDoc] Lazy loading enabled - documents will load on first modal open');

  // shown.bs.modalイベントをリッスン
  contractModal.addEventListener('shown.bs.modal', () => {
    if (this.isInitialLoad) {
      console.log('[ContractDoc] First modal open detected - loading documents');
      this.isInitialLoad = false;
      this.loadDocuments();
    } else {
      console.log('[ContractDoc] Modal opened - documents already loaded');
    }
  });
}
```

### 3. スタイルの追加

モーダル専用のスタイルを追加しました：

```css
/* 契約書ドキュメント管理モーダルのスタイル */
#contract-documents-modal .modal-dialog {
    max-width: 90%;
    margin: 1.75rem auto;
}

#contract-documents-modal .modal-body {
    min-height: 500px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#contract-documents-modal .document-management {
    padding: 1.5rem;
}

/* モーダル内のドキュメント一覧の高さ調整 */
#contract-documents-modal .document-list-container {
    min-height: 400px;
}

/* z-indexの調整 */
#contract-documents-modal {
    z-index: 2010 !important;
}

#contract-documents-modal .modal-backdrop {
    z-index: 2000 !important;
}
```

### 4. モーダル制御スクリプトの追加

モーダルの開閉時の処理を追加しました：

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const contractDocumentsModal = document.getElementById('contract-documents-modal');
    
    if (contractDocumentsModal) {
        // モーダルが開かれたときにドキュメントを読み込む
        contractDocumentsModal.addEventListener('shown.bs.modal', function() {
            console.log('[ContractDoc] Modal opened, loading documents');
            
            // ContractDocumentManagerのインスタンスを取得または作成
            if (window.contractDocManager) {
                // 既にインスタンスが存在する場合は、ドキュメントを再読み込み
                if (typeof window.contractDocManager.loadDocuments === 'function') {
                    window.contractDocManager.loadDocuments();
                }
            } else {
                // インスタンスが存在しない場合は、少し待ってから再試行
                console.log('[ContractDoc] Manager not found, waiting for initialization');
                setTimeout(function() {
                    if (window.contractDocManager && typeof window.contractDocManager.loadDocuments === 'function') {
                        window.contractDocManager.loadDocuments();
                    }
                }, 500);
            }
        });
        
        // モーダルが閉じられたときの処理
        contractDocumentsModal.addEventListener('hidden.bs.modal', function() {
            console.log('[ContractDoc] Modal closed');
        });
    }
});
```

## 主な変更点

### UI/UX
1. **ボタンテキスト**: 「ドキュメントを表示」→「ドキュメント」（簡潔に）
2. **ボタンスタイル**: `btn-outline-primary` → `btn-primary`（より目立つように）
3. **表示方法**: 折りたたみセクション → モーダル（より集中できる）
4. **モーダルサイズ**: `modal-xl`（90%幅）で広々とした表示

### 機能
1. **遅延ロード**: 折りたたみ展開時 → モーダル開閉時
2. **イベント**: `shown.bs.collapse` → `shown.bs.modal`
3. **要素ID**: `unified-documents-section` → `contract-documents-modal`

### スタイル
1. **モーダル幅**: 90%（大画面で広々と表示）
2. **モーダル高さ**: 最小500px、最大calc(100vh - 200px)
3. **スクロール**: `modal-dialog-scrollable`で長いコンテンツに対応
4. **z-index**: 2010（他の要素より前面に表示）

## 利点

### 1. より集中できる
- モーダルで全画面的に表示されるため、ドキュメント管理に集中できる
- 背景がオーバーレイされるため、他の要素に気を取られない

### 2. スペース効率
- ページ内のスペースを節約
- 必要なときだけモーダルを開く

### 3. 統一感
- 他のモーダル操作（フォルダ作成、ファイルアップロード等）と統一された体験

### 4. モバイル対応
- モーダルはモバイルでも使いやすい
- レスポンシブデザインに対応

## 使用方法

### ユーザー操作

1. **ドキュメントを開く**
   - 契約書タブで「ドキュメント」ボタンをクリック
   - モーダルが開き、ドキュメント管理機能が表示される

2. **ドキュメント操作**
   - モーダル内で通常通りフォルダ作成、ファイルアップロード等が可能
   - すべての機能が利用可能

3. **モーダルを閉じる**
   - 右上の「×」ボタンをクリック
   - または「閉じる」ボタンをクリック
   - ESCキーでも閉じられる

### 開発者向け

#### モーダルの制御

```javascript
// モーダルを開く
const modal = new bootstrap.Modal(document.getElementById('contract-documents-modal'));
modal.show();

// モーダルを閉じる
modal.hide();

// モーダルのイベントをリッスン
document.getElementById('contract-documents-modal').addEventListener('shown.bs.modal', function() {
    console.log('Modal opened');
});
```

#### ドキュメントの再読み込み

```javascript
// ContractDocumentManagerのインスタンスを使用
if (window.contractDocManager) {
    window.contractDocManager.loadDocuments();
}
```

## トラブルシューティング

### モーダルが開かない

1. **Bootstrapが読み込まれているか確認**
   ```javascript
   console.log(typeof bootstrap); // "object"であるべき
   ```

2. **モーダルIDが正しいか確認**
   ```html
   data-bs-target="#contract-documents-modal"
   ```

3. **ブラウザコンソールでエラーを確認**
   ```
   F12キー → Consoleタブ
   ```

### ドキュメントが表示されない

1. **ContractDocumentManagerが初期化されているか確認**
   ```javascript
   console.log(window.contractDocManager); // オブジェクトであるべき
   ```

2. **ネットワークタブでAPIリクエストを確認**
   ```
   F12キー → Networkタブ
   ```

3. **権限を確認**
   - ユーザーに適切な権限があるか確認

### モーダルが背面に隠れる

1. **z-indexを確認**
   ```css
   #contract-documents-modal {
       z-index: 2010 !important;
   }
   ```

2. **他の要素のz-indexを確認**
   - 他の要素が2010以上のz-indexを持っていないか確認

## 検証

実装が正しく動作することを確認するには：

```bash
php scripts/verify-contract-document-implementation.php
```

すべての検証に合格することを確認してください。

## まとめ

契約書のドキュメント管理を、折りたたみセクションからモーダルベースに変更しました。これにより、より集中できる環境でドキュメント管理が可能になり、スペース効率も向上しました。

### 変更ファイル
- ✅ `resources/views/facilities/contracts/index.blade.php`
- ✅ `resources/js/modules/ContractDocumentManager.js`

### 主な変更
- ✅ 折りたたみセクション → モーダル
- ✅ 遅延ロードの対象変更（collapse → modal）
- ✅ モーダル専用スタイルの追加
- ✅ モーダル制御スクリプトの追加

---

**実装日**: 2025年10月16日  
**バージョン**: 1.1.0  
**ステータス**: ✅ 実装完了
