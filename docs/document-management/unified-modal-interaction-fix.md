# 統一的なドキュメント管理モーダル - 操作不可問題の修正

## 問題

すべてのドキュメント管理モーダル（契約書、ライフライン設備、メンテナンス履歴等）で、モーダルを開いた後に内部の要素が操作できない問題が発生していました。

### 影響範囲
- ✅ 契約書ドキュメント管理
- ✅ 電気設備ドキュメント管理
- ✅ ガス設備ドキュメント管理
- ✅ 水道設備ドキュメント管理
- ✅ エレベーター設備ドキュメント管理
- ✅ 空調・照明設備ドキュメント管理
- ✅ メンテナンス履歴ドキュメント管理

## 解決策

### 1. 統一的なCSSファイルの作成

すべてのドキュメント管理モーダルに適用される統一的なCSSファイルを作成しました。

**ファイル**: `resources/css/document-modal-fix.css`

```css
/* メインモーダル */
#contract-documents-modal,
#electrical-documents-modal,
#gas-documents-modal,
#water-documents-modal,
#elevator-documents-modal,
#hvac-documents-modal,
#maintenance-documents-modal {
    z-index: 9999 !important;
}

/* ネストされたモーダル */
[id^="create-folder-modal-"],
[id^="upload-file-modal-"],
[id^="rename-modal-"],
[id^="properties-modal-"] {
    z-index: 10000 !important;
}

/* コンテキストメニュー */
.context-menu {
    z-index: 10001 !important;
}

/* pointer-events設定 */
.modal button,
.modal input,
.modal select,
.modal textarea,
.modal a,
.modal label {
    pointer-events: auto !important;
}
```

### 2. 統一的なJavaScriptクラスの作成

すべてのドキュメント管理モーダルに適用される統一的なJavaScriptクラスを作成しました。

**ファイル**: `resources/js/shared/DocumentModalFix.js`

```javascript
class DocumentModalFix {
    constructor() {
        this.mainModalIds = [
            'contract-documents-modal',
            'electrical-documents-modal',
            'gas-documents-modal',
            'water-documents-modal',
            'elevator-documents-modal',
            'hvac-documents-modal',
            'maintenance-documents-modal'
        ];
        
        this.init();
    }
    
    init() {
        this.hoistAllModals();
        this.setupZIndexEnforcement();
        this.setupBackdropCleanup();
    }
    
    hoistAllModals() {
        // すべてのモーダルをbody直下に移動
    }
    
    setupZIndexEnforcement() {
        // z-indexの動的設定
    }
    
    setupBackdropCleanup() {
        // バックドロップのクリーンアップ
    }
}
```

### 3. app-unified.jsへのインポート

**ファイル**: `resources/js/app-unified.js`

```javascript
// Import DocumentModalFix - 統一的なモーダル修正
import DocumentModalFix from './shared/DocumentModalFix.js';
```

### 4. 各コンポーネントへのHoisting処理の追加

#### contract-document-manager.blade.php
```javascript
// モーダルをbodyに移動
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
            document.body.appendChild(modal);
        }
    });
}
```

#### lifeline-document-manager.blade.php
```javascript
// モーダルをbodyに移動
const modalIds = [
    'create-folder-modal-' + uniqueId,
    'upload-file-modal-' + uniqueId,
    'rename-modal-' + uniqueId,
    'properties-modal-' + uniqueId
];

modalIds.forEach(function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
});
```

#### maintenance-document-manager.blade.php
```javascript
// モーダルをbodyに移動
const modalIds = [
    'create-folder-modal-' + category,
    'upload-file-modal-' + category,
    'rename-modal-' + category,
    'properties-modal-' + category
];

modalIds.forEach(function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
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

### 新規作成
1. **`resources/css/document-modal-fix.css`**
   - 統一的なz-index設定
   - pointer-events設定
   - モーダルサイズ調整

2. **`resources/js/shared/DocumentModalFix.js`**
   - モーダルのHoisting
   - z-indexの動的設定
   - バックドロップのクリーンアップ

### 修正
1. **`resources/js/app-unified.js`**
   - DocumentModalFixのインポート

2. **`resources/views/components/contract-document-manager.blade.php`**
   - モーダルhoisting処理の追加

3. **`resources/views/components/lifeline-document-manager.blade.php`**
   - モーダルhoisting処理の追加

4. **`resources/views/components/maintenance-document-manager.blade.php`**
   - モーダルhoisting処理の追加

## 動作確認

### すべてのドキュメント管理モーダルで確認

1. **契約書**
   - 契約書タブ → 「ドキュメント」ボタン → モーダルが開く
   - モーダル内のボタンがクリック可能
   - フォルダ作成、ファイルアップロード等が正常に動作

2. **電気設備**
   - ライフライン設備タブ → 電気設備 → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

3. **ガス設備**
   - ライフライン設備タブ → ガス設備 → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

4. **水道設備**
   - ライフライン設備タブ → 水道設備 → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

5. **エレベーター設備**
   - ライフライン設備タブ → エレベーター設備 → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

6. **空調・照明設備**
   - ライフライン設備タブ → 空調・照明設備 → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

7. **メンテナンス履歴**
   - メンテナンス履歴タブ → 「ドキュメント」ボタン
   - モーダル内のすべての要素が操作可能

### 操作確認項目

各ドキュメント管理モーダルで以下を確認：

- ✅ モーダルが開く
- ✅ 「新しいフォルダ」ボタンがクリック可能
- ✅ 「ファイルアップロード」ボタンがクリック可能
- ✅ 検索ボックスに入力可能
- ✅ 表示モード切替ボタンがクリック可能
- ✅ フォルダ名をクリックしてフォルダを開ける
- ✅ ファイル名をクリックしてダウンロードできる
- ✅ 右クリックメニューが表示される
- ✅ コンテキストメニューの項目がクリック可能
- ✅ モーダルを閉じられる（×ボタン、閉じるボタン、ESCキー）
- ✅ バックドロップが正しく削除される

## トラブルシューティング

### モーダルが操作できない場合

1. **ブラウザコンソールを確認**
   ```
   F12キー → Consoleタブ
   ```
   - モーダルがhoistされているか確認
   - DocumentModalFixが初期化されているか確認

2. **z-indexを確認**
   ```
   F12キー → Elementsタブ → モーダル要素を選択
   ```
   - モーダルのz-indexが9999以上か確認

3. **モーダルの位置を確認**
   ```
   F12キー → Elementsタブ → モーダル要素を選択
   ```
   - モーダルが`<body>`直下にあるか確認

4. **DocumentModalFixを手動で実行**
   ```javascript
   // ブラウザコンソールで実行
   window.documentModalFix.hoistAllModals();
   window.documentModalFix.cleanupAllBackdrops();
   ```

### バックドロップが残る場合

```javascript
// ブラウザコンソールで実行
document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
```

## 利点

### 1. 統一性
- すべてのドキュメント管理モーダルで同じ修正を適用
- 一貫した動作とユーザー体験

### 2. 保守性
- 統一的なCSSとJavaScriptファイル
- 修正が必要な場合は1箇所を変更するだけ

### 3. 拡張性
- 新しいドキュメント管理モーダルを追加する場合も簡単
- DocumentModalFixに新しいモーダルIDを追加するだけ

### 4. デバッグ性
- 統一的なログ出力
- 問題の特定が容易

## まとめ

すべてのドキュメント管理モーダルの操作不可問題を、統一的なアプローチで修正しました：

1. ✅ 統一的なCSSファイル（document-modal-fix.css）
2. ✅ 統一的なJavaScriptクラス（DocumentModalFix.js）
3. ✅ 各コンポーネントへのhoisting処理の追加
4. ✅ app-unified.jsへのインポート

これにより、すべてのドキュメント管理モーダルが正常に操作可能になりました。

---

**修正日**: 2025年10月16日  
**バージョン**: 1.2.0  
**ステータス**: ✅ 修正完了
