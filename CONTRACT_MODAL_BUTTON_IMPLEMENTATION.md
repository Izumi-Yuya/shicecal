# 契約書ドキュメント管理 - モーダルボタン実装完了

## 📊 変更内容

契約書のドキュメント管理を、**折りたたみセクション**から**モーダルボタン**に変更しました。

### 変更前 → 変更後

| 項目 | 変更前 | 変更後 |
|------|--------|--------|
| ボタンテキスト | 「ドキュメントを表示」 | 「ドキュメント」 |
| ボタンスタイル | `btn-outline-primary` | `btn-primary` |
| 表示方法 | 折りたたみセクション | モーダル |
| イベント | `shown.bs.collapse` | `shown.bs.modal` |
| 要素ID | `unified-documents-section` | `contract-documents-modal` |

---

## 🎯 実装詳細

### 1. ビューの変更

**ファイル**: `resources/views/facilities/contracts/index.blade.php`

#### ボタン部分
```blade
<!-- 変更前 -->
<button type="button" 
        class="btn btn-outline-primary btn-sm unified-documents-toggle" 
        id="unified-documents-toggle"
        data-bs-toggle="collapse" 
        data-bs-target="#unified-documents-section">
    <i class="fas fa-folder-open me-1"></i>
    <span>ドキュメントを表示</span>
</button>

<!-- 変更後 -->
<button type="button" 
        class="btn btn-primary btn-sm" 
        id="open-contract-documents-modal-btn"
        data-bs-toggle="modal" 
        data-bs-target="#contract-documents-modal">
    <i class="fas fa-folder-open me-1"></i>
    <span>ドキュメント</span>
</button>
```

#### モーダル追加
```blade
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

### 2. JavaScriptの変更

**ファイル**: `resources/js/modules/ContractDocumentManager.js`

#### setupLazyLoading()メソッド
```javascript
// 変更前
setupLazyLoading() {
  const unifiedSection = document.getElementById('unified-documents-section');
  // ...
  unifiedSection.addEventListener('shown.bs.collapse', () => {
    // ...
  });
}

// 変更後
setupLazyLoading() {
  const contractModal = document.getElementById('contract-documents-modal');
  // ...
  contractModal.addEventListener('shown.bs.modal', () => {
    // ...
  });
}
```

### 3. スタイルの追加

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

#contract-documents-modal .document-list-container {
    min-height: 400px;
}

#contract-documents-modal {
    z-index: 2010 !important;
}

#contract-documents-modal .modal-backdrop {
    z-index: 2000 !important;
}
```

---

## ✅ 利点

### 1. より集中できる
- ✅ モーダルで全画面的に表示
- ✅ 背景がオーバーレイされる
- ✅ ドキュメント管理に集中できる

### 2. スペース効率
- ✅ ページ内のスペースを節約
- ✅ 必要なときだけモーダルを開く
- ✅ 他のコンテンツと干渉しない

### 3. 統一感
- ✅ 他のモーダル操作と統一された体験
- ✅ フォルダ作成、ファイルアップロード等と同じパターン

### 4. モバイル対応
- ✅ モーダルはモバイルでも使いやすい
- ✅ レスポンシブデザインに対応

---

## 🚀 使用方法

### ユーザー操作

1. **ドキュメントを開く**
   ```
   契約書タブ → 「ドキュメント」ボタンをクリック
   ```

2. **ドキュメント操作**
   ```
   モーダル内で通常通り操作
   - フォルダ作成
   - ファイルアップロード
   - 検索
   - 表示モード切替
   ```

3. **モーダルを閉じる**
   ```
   - 右上の「×」ボタン
   - 「閉じる」ボタン
   - ESCキー
   ```

---

## 🔧 技術仕様

### モーダル設定
- **サイズ**: `modal-xl`（90%幅）
- **スクロール**: `modal-dialog-scrollable`
- **背景**: `data-bs-backdrop="static"`（クリックで閉じない）
- **キーボード**: `data-bs-keyboard="true"`（ESCで閉じる）

### z-index設定
- **モーダル**: 2010
- **バックドロップ**: 2000

### 遅延ロード
- **トリガー**: モーダルの`shown.bs.modal`イベント
- **初回のみ**: `isInitialLoad`フラグで制御

---

## 📝 変更ファイル

### 修正ファイル
- ✅ `resources/views/facilities/contracts/index.blade.php`
  - 折りたたみセクション削除
  - モーダルボタン追加
  - モーダル追加
  - スタイル追加
  - スクリプト追加

- ✅ `resources/js/modules/ContractDocumentManager.js`
  - `setupLazyLoading()`メソッド修正
  - イベントリスナー変更（collapse → modal）

### 新規ドキュメント
- ✅ `docs/document-management/contract-document-modal-button-implementation.md`

---

## 🎉 まとめ

契約書のドキュメント管理を、折りたたみセクションからモーダルボタンに変更しました。

### 主な変更
- ✅ ボタンをクリック → モーダルが開く
- ✅ より集中できる環境
- ✅ スペース効率の向上
- ✅ 統一されたUI/UX

### 動作確認
1. 契約書タブを開く
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. ドキュメント管理機能が表示される
5. すべての機能が正常に動作する

---

**実装日**: 2025年10月16日  
**バージョン**: 1.1.0  
**ステータス**: ✅ 実装完了
