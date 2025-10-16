# 契約書ドキュメント管理モーダル - 操作不可問題の修正

## 問題

契約書のドキュメント管理モーダルを開いた後、モーダル内の要素（ボタン、リンク、フォーム等）が操作できない問題が発生していました。

### 原因

1. **モーダルのネスト問題**: メインモーダル内にコンポーネントのモーダル（フォルダ作成、ファイルアップロード等）がネストされている
2. **z-index問題**: モーダルが親要素のz-indexに影響を受けて、背面に隠れている
3. **pointer-events問題**: モーダルのバックドロップが前面にあり、クリックイベントをブロックしている

## 解決策

### 1. モーダルのHoisting（body直下への移動）

すべてのモーダルを`<body>`直下に移動することで、親要素のz-indexの影響を受けないようにしました。

#### index.blade.php
```javascript
// メインモーダルをbodyに移動
if (contractDocumentsModal.parentElement !== document.body) {
    console.log('[ContractDoc] Hoisting modal to body');
    document.body.appendChild(contractDocumentsModal);
}
```

#### contract-document-manager.blade.php
```javascript
// コンポーネント内のモーダルもbodyに移動
function hoistModalsToBody() {
    const modalIds = [
        'create-folder-modal-contracts',
        'upload-file-modal-contracts',
        'rename-modal-contracts',
        'properties-modal-contracts'
    ];
    
    modalIds.forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && modal.parentElement !== document.body) {
            console.log('[ContractDoc] Hoisting modal to body:', modalId);
            document.body.appendChild(modal);
        }
    });
}
```

### 2. z-indexの階層化

モーダルのz-indexを適切に設定して、重なり順を制御しました。

```css
/* メインモーダル */
#contract-documents-modal {
    z-index: 9999 !important;
}

/* メインモーダルのバックドロップ */
.modal-backdrop.show {
    z-index: 9998 !important;
}

/* ネストされたモーダル（フォルダ作成、ファイルアップロード等） */
#create-folder-modal-contracts,
#upload-file-modal-contracts,
#rename-modal-contracts,
#properties-modal-contracts {
    z-index: 10000 !important;
}

/* ネストされたモーダルのバックドロップ */
#create-folder-modal-contracts + .modal-backdrop,
#upload-file-modal-contracts + .modal-backdrop,
#rename-modal-contracts + .modal-backdrop,
#properties-modal-contracts + .modal-backdrop {
    z-index: 9999 !important;
}

/* コンテキストメニュー */
.context-menu {
    z-index: 10001 !important;
}
```

### 3. pointer-eventsの明示的な設定

モーダル内の要素が確実にクリック可能になるように、`pointer-events: auto`を設定しました。

```css
/* モーダル内のボタンやフォーム要素が操作可能であることを保証 */
.modal button,
.modal input,
.modal select,
.modal textarea,
.modal a,
.modal label {
    pointer-events: auto !important;
    position: relative;
}

/* ドキュメント一覧のテーブル行が操作可能であることを保証 */
#contract-documents-modal .document-item {
    pointer-events: auto !important;
}

#contract-documents-modal .document-item * {
    pointer-events: auto !important;
}
```

### 4. JavaScriptによる動的z-index設定

モーダルが開かれるたびに、z-indexを動的に設定するようにしました。

```javascript
// Modal z-index enforcement
document.addEventListener('show.bs.modal', function(ev) {
    var modalEl = ev.target;
    if (modalEl) {
        // メインモーダル
        if (modalEl.id === 'contract-documents-modal') {
            modalEl.style.zIndex = '9999';
            setTimeout(function() {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(bd) {
                    bd.style.zIndex = '9998';
                });
            }, 0);
        }
        // ネストされたモーダル
        else if (modalEl.id && modalEl.id.includes('contracts')) {
            modalEl.style.zIndex = '10000';
            setTimeout(function() {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                var lastBackdrop = backdrops[backdrops.length - 1];
                if (lastBackdrop) {
                    lastBackdrop.style.zIndex = '9999';
                }
            }, 0);
        }
    }
});
```

### 5. バックドロップのクリーンアップ

モーダルが閉じられたときに、余分なバックドロップを削除するようにしました。

```javascript
// Cleanup extra backdrops
document.addEventListener('hidden.bs.modal', function(ev) {
    if (ev.target && ev.target.id === 'contract-documents-modal') {
        setTimeout(function() {
            var backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 1) {
                for (var i = 0; i < backdrops.length - 1; i++) {
                    backdrops[i].parentNode.removeChild(backdrops[i]);
                }
            }
        }, 100);
    }
});
```

## z-index階層構造

```
10001: コンテキストメニュー
10000: ネストされたモーダル（フォルダ作成、ファイルアップロード等）
 9999: メインモーダル & ネストされたモーダルのバックドロップ
 9998: メインモーダルのバックドロップ
    1: モーダル内のコンテンツ（相対的な位置）
```

## 修正ファイル

### 1. resources/views/facilities/contracts/index.blade.php
- モーダルhoisting処理の追加
- z-index設定の強化
- pointer-events設定の追加
- バックドロップクリーンアップ処理の追加

### 2. resources/views/components/contract-document-manager.blade.php
- コンポーネント内モーダルのhoisting処理の追加

### 3. resources/css/contract-modal-fix.css（新規作成）
- モーダルのz-index設定
- pointer-events設定

## テスト手順

### 1. メインモーダルの操作確認
1. 契約書タブを開く
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. モーダル内のボタン（新しいフォルダ、ファイルアップロード等）がクリック可能
5. 検索ボックスに入力可能
6. 表示モード切替ボタンがクリック可能

### 2. ネストされたモーダルの操作確認
1. 「新しいフォルダ」ボタンをクリック
2. フォルダ作成モーダルが開く
3. フォルダ名入力欄に入力可能
4. 「作成」ボタンがクリック可能
5. 「キャンセル」ボタンがクリック可能

### 3. ファイルアップロードの操作確認
1. 「ファイルアップロード」ボタンをクリック
2. ファイルアップロードモーダルが開く
3. ファイル選択ボタンがクリック可能
4. 「アップロード」ボタンがクリック可能

### 4. ドキュメント一覧の操作確認
1. フォルダ名をクリックしてフォルダを開ける
2. ファイル名をクリックしてダウンロードできる
3. 右クリックメニューが表示される
4. コンテキストメニューの項目がクリック可能

### 5. モーダルの閉じる操作確認
1. 右上の「×」ボタンで閉じられる
2. 「閉じる」ボタンで閉じられる
3. ESCキーで閉じられる
4. バックドロップが正しく削除される

## トラブルシューティング

### モーダルが操作できない場合

1. **ブラウザコンソールを確認**
   ```
   F12キー → Consoleタブ
   ```
   - モーダルがhoistされているか確認
   - エラーメッセージがないか確認

2. **z-indexを確認**
   ```
   F12キー → Elementsタブ → モーダル要素を選択 → Stylesタブ
   ```
   - モーダルのz-indexが9999以上か確認
   - バックドロップのz-indexが適切か確認

3. **pointer-eventsを確認**
   ```
   F12キー → Elementsタブ → ボタン要素を選択 → Stylesタブ
   ```
   - `pointer-events: auto`が設定されているか確認

4. **モーダルの位置を確認**
   ```
   F12キー → Elementsタブ → モーダル要素を選択
   ```
   - モーダルが`<body>`直下にあるか確認

### バックドロップが残る場合

1. **手動でバックドロップを削除**
   ```javascript
   document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
   ```

2. **ページをリロード**
   ```
   F5キーまたはCtrl+R
   ```

## まとめ

契約書ドキュメント管理モーダルの操作不可問題を、以下の方法で修正しました：

1. ✅ モーダルのHoisting（body直下への移動）
2. ✅ z-indexの階層化（9998-10001）
3. ✅ pointer-eventsの明示的な設定
4. ✅ JavaScriptによる動的z-index設定
5. ✅ バックドロップのクリーンアップ

これにより、モーダル内のすべての要素が正常に操作可能になりました。

---

**修正日**: 2025年10月16日  
**バージョン**: 1.1.1  
**ステータス**: ✅ 修正完了
