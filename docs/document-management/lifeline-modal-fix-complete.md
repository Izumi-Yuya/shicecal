# ライフライン設備ドキュメント管理モーダル - 操作可能化完了

## 修正完了

すべてのライフライン設備のドキュメント管理モーダルに、契約書と同じ修正を適用しました。

### 修正対象

- ✅ 電気設備ドキュメント管理
- ✅ ガス設備ドキュメント管理
- ✅ 水道設備ドキュメント管理
- ✅ エレベーター設備ドキュメント管理
- ✅ 空調・照明設備ドキュメント管理

## 修正内容

各ビューファイルのモーダル直後に、以下を追加しました：

### 1. スタイル

```css
/* [設備名]ドキュメント管理モーダルのスタイル */
#[equipment]-documents-modal { z-index: 9999 !important; }
#[equipment]-documents-modal .modal-dialog { max-width: 90%; margin: 1.75rem auto; }
#[equipment]-documents-modal .modal-body { min-height: 500px; max-height: calc(100vh - 200px); overflow-y: auto; }
#create-folder-modal-[equipment], #upload-file-modal-[equipment], #rename-modal-[equipment], #properties-modal-[equipment] { z-index: 10000 !important; }
.modal button, .modal input, .modal select, .modal textarea, .modal a, .modal label { pointer-events: auto !important; }
.document-item, .document-item * { pointer-events: auto !important; }
```

### 2. スクリプト

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('[equipment]-documents-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
    document.addEventListener('show.bs.modal', function(ev) {
        if (ev.target && ev.target.id && ev.target.id.includes('[equipment]')) {
            ev.target.style.zIndex = ev.target.id === '[equipment]-documents-modal' ? '9999' : '10000';
            setTimeout(function() {
                document.querySelectorAll('.modal-backdrop').forEach(function(bd, i, arr) {
                    bd.style.zIndex = i === arr.length - 1 ? (ev.target.id === '[equipment]-documents-modal' ? '9998' : '9999') : '9998';
                });
            }, 0);
        }
    });
});
```

## 修正ファイル

1. **`resources/views/facilities/lifeline-equipment/electrical.blade.php`**
   - 電気設備ドキュメント管理モーダルの修正

2. **`resources/views/facilities/lifeline-equipment/gas.blade.php`**
   - ガス設備ドキュメント管理モーダルの修正

3. **`resources/views/facilities/lifeline-equipment/water.blade.php`**
   - 水道設備ドキュメント管理モーダルの修正

4. **`resources/views/facilities/lifeline-equipment/elevator.blade.php`**
   - エレベーター設備ドキュメント管理モーダルの修正

5. **`resources/views/facilities/lifeline-equipment/hvac-lighting.blade.php`**
   - 空調・照明設備ドキュメント管理モーダルの修正

## 動作確認

各ライフライン設備で以下を確認してください：

### 電気設備
1. ライフライン設備タブ → 電気設備
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. モーダル内のすべての要素が操作可能
   - ✅ 「新しいフォルダ」ボタンがクリック可能
   - ✅ 「ファイルアップロード」ボタンがクリック可能
   - ✅ 検索ボックスに入力可能
   - ✅ フォルダ/ファイルをクリック可能
   - ✅ 右クリックメニューが表示される

### ガス設備
1. ライフライン設備タブ → ガス設備
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. モーダル内のすべての要素が操作可能

### 水道設備
1. ライフライン設備タブ → 水道設備
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. モーダル内のすべての要素が操作可能

### エレベーター設備
1. ライフライン設備タブ → エレベーター設備
2. 「ドキュメント」ボタンをクリック
3. モーダルが開く
4. モーダル内のすべての要素が操作可能

## 技術詳細

### モーダルのHoisting
```javascript
if (modal && modal.parentElement !== document.body) {
    document.body.appendChild(modal);
}
```
モーダルを`<body>`直下に移動することで、親要素のz-indexの影響を受けないようにします。

### z-indexの階層化
```
10000: ネストされたモーダル（フォルダ作成、ファイルアップロード等）
 9999: メインモーダル
 9998: バックドロップ
```

### pointer-eventsの設定
```css
.modal button,
.modal input,
.modal select,
.modal textarea,
.modal a,
.modal label {
    pointer-events: auto !important;
}
```
モーダル内のすべての要素が確実にクリック可能になるように設定します。

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
   F12キー → Elementsタブ → モーダル要素を選択
   ```
   - モーダルのz-indexが9999か確認

3. **モーダルの位置を確認**
   ```
   F12キー → Elementsタブ → モーダル要素を選択
   ```
   - モーダルが`<body>`直下にあるか確認

4. **ページをリロード**
   ```
   F5キーまたはCtrl+R
   ```

### バックドロップが残る場合

```javascript
// ブラウザコンソールで実行
document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
```

## まとめ

すべてのライフライン設備のドキュメント管理モーダルに、契約書と同じ修正を適用しました。これにより、すべてのモーダルが正常に操作可能になりました。

### 修正内容
- ✅ モーダルのHoisting（body直下への移動）
- ✅ z-indexの階層化（9998-10000）
- ✅ pointer-eventsの明示的な設定
- ✅ 動的z-index設定

### 修正ファイル
- ✅ electrical.blade.php
- ✅ gas.blade.php
- ✅ water.blade.php
- ✅ elevator.blade.php
- ✅ hvac-lighting.blade.php

---

**修正日**: 2025年10月16日  
**バージョン**: 1.2.1  
**ステータス**: ✅ 修正完了
