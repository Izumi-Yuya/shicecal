# 空調・照明設備ドキュメント管理モーダル - 修正完了

## 概要

空調・照明設備のドキュメント管理を、折りたたみセクションからモーダルボタン方式に変更し、他のライフライン設備と同じ操作性を実現しました。

## 修正日時

**2025年10月16日**

## 修正内容

### 1. ボタンの変更

#### 修正前
```blade
<button type="button"
    class="btn btn-outline-primary btn-sm d-none"
    id="hvac-lighting-documents-toggle"
    data-bs-toggle="collapse"
    data-bs-target="#hvac-lighting-documents-section">
```

#### 修正後
```blade
<button type="button"
    class="btn btn-outline-primary btn-sm"
    data-bs-toggle="modal"
    data-bs-target="#hvac-lighting-documents-modal">
```

**変更点**:
- `d-none`クラスを削除してボタンを表示
- `data-bs-toggle`を`collapse`から`modal`に変更
- `data-bs-target`をモーダルIDに変更

### 2. 折りたたみセクションからモーダルへの変換

#### 修正前
```blade
<div class="collapse mb-4" id="hvac-lighting-documents-section">
    <div class="card border-success">
        <!-- 折りたたみコンテンツ -->
    </div>
</div>
```

#### 修正後
```blade
<div class="modal fade" id="hvac-lighting-documents-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <!-- モーダルヘッダー -->
            </div>
            <div class="modal-body p-0">
                <!-- ドキュメント管理コンポーネント -->
            </div>
            <div class="modal-footer">
                <!-- 閉じるボタン -->
            </div>
        </div>
    </div>
</div>
```

**変更点**:
- Bootstrap標準のモーダル構造に変更
- `modal-xl`クラスで大きなモーダルサイズを設定
- ヘッダーに成功色（緑）を適用
- フッターに閉じるボタンを追加

### 3. JavaScriptの修正

#### 修正前（折りたたみ制御）
```javascript
// ボタンアイコンとテキストの更新
function updateButtonState(isExpanded) {
    // 折りたたみ状態に応じてボタンを変更
}

// Bootstrap collapse イベントリスナー
documentSection.addEventListener('shown.bs.collapse', function() {
    updateButtonState(true);
});
```

#### 修正後（モーダル制御）
```javascript
// モーダルをbody直下に移動（hoisting）
const modal = document.getElementById('hvac-lighting-documents-modal');
if (modal && modal.parentElement !== document.body) {
    document.body.appendChild(modal);
}

// モーダル表示時の処理
hvacModal.addEventListener('shown.bs.modal', function() {
    // ドキュメントマネージャーの初期化
});

// z-index動的設定
document.addEventListener('show.bs.modal', function(ev) {
    if (ev.target.id.includes('hvac-lighting')) {
        ev.target.style.zIndex = isMainModal ? '9999' : '10000';
    }
});

// バックドロップクリーンアップ
document.addEventListener('hidden.bs.modal', function(ev) {
    if (ev.target.id.includes('hvac-lighting')) {
        // 余分なバックドロップを削除
    }
});
```

**変更点**:
- モーダルhoisting処理を追加
- z-indexの動的設定を実装
- バックドロップのクリーンアップを実装
- コンソールログを追加してデバッグを容易に

### 4. CSSの修正

#### 修正前
```css
#hvac-lighting-documents-section .card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
```

#### 修正後
```css
/* モーダルのz-index設定 */
#hvac-lighting-documents-modal {
    z-index: 9999 !important;
}

#hvac-lighting-documents-modal .modal-dialog {
    max-width: 90%;
    margin: 1.75rem auto;
}

#hvac-lighting-documents-modal .modal-body {
    min-height: 500px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

/* ネストされたモーダル */
#create-folder-modal-hvac_lighting,
#upload-file-modal-hvac_lighting,
#rename-modal-hvac_lighting,
#properties-modal-hvac_lighting {
    z-index: 10000 !important;
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

**変更点**:
- z-index階層構造を実装（9999-10000）
- モーダルサイズを調整
- pointer-eventsを明示的に設定
- レスポンシブ対応を追加

## 統一ファイルへの追加

### 1. DocumentModalFix.js

```javascript
this.mainModalIds = [
  'contract-documents-modal',
  'electrical-documents-modal',
  'gas-documents-modal',
  'water-documents-modal',
  'elevator-documents-modal',
  'hvac-lighting-documents-modal',  // ← 追加
  'maintenance-documents-modal'
];
```

### 2. document-modal-fix.css

```css
#contract-documents-modal,
#electrical-documents-modal,
#gas-documents-modal,
#water-documents-modal,
#elevator-documents-modal,
#hvac-lighting-documents-modal,  /* ← 追加 */
#maintenance-documents-modal {
  z-index: 9999 !important;
}
```

## 修正ファイル一覧

1. **`resources/views/facilities/lifeline-equipment/hvac-lighting.blade.php`**
   - ボタンの変更（折りたたみ→モーダル）
   - 折りたたみセクションをモーダルに変換
   - JavaScriptの修正（hoisting、z-index、バックドロップ）
   - CSSの修正（z-index階層、pointer-events）

2. **`resources/js/shared/DocumentModalFix.js`**
   - `hvac-lighting-documents-modal`をmainModalIdsに追加

3. **`resources/css/document-modal-fix.css`**
   - `#hvac-lighting-documents-modal`のz-index設定を追加

4. **`docs/document-management/lifeline-modal-fix-complete.md`**
   - 空調・照明設備を修正対象に追加

5. **`docs/document-management/unified-modal-interaction-fix.md`**
   - 空調・照明設備を影響範囲に追加

## z-index階層構造

```
10001: コンテキストメニュー
10000: ネストされたモーダル（フォルダ作成、ファイルアップロード等）
 9999: メインモーダル & ネストされたモーダルのバックドロップ
 9998: メインモーダルのバックドロップ
    1: モーダル内のコンテンツ（相対的な位置）
```

## 動作確認

### 確認手順

1. **施設詳細画面を開く**
   - 任意の施設の詳細画面に移動

2. **ライフライン設備タブを開く**
   - 「ライフライン設備」タブをクリック

3. **空調・照明設備を選択**
   - 空調・照明設備のサブタブをクリック

4. **ドキュメントボタンをクリック**
   - 「ドキュメント」ボタンが表示されていることを確認
   - ボタンをクリックしてモーダルが開くことを確認

5. **モーダル内の操作を確認**
   - ✅ 「新しいフォルダ」ボタンがクリック可能
   - ✅ 「ファイルアップロード」ボタンがクリック可能
   - ✅ 検索ボックスに入力可能
   - ✅ 表示モード切替ボタンがクリック可能
   - ✅ フォルダ名をクリックしてフォルダを開ける
   - ✅ ファイル名をクリックしてダウンロードできる
   - ✅ 右クリックメニューが表示される
   - ✅ コンテキストメニューの項目がクリック可能

6. **ネストされたモーダルを確認**
   - 「新しいフォルダ」をクリック
   - フォルダ作成モーダルが開くことを確認
   - フォーム要素が操作可能であることを確認
   - モーダルを閉じられることを確認

7. **モーダルを閉じる**
   - ✅ ×ボタンで閉じられる
   - ✅ 「閉じる」ボタンで閉じられる
   - ✅ ESCキーで閉じられる
   - ✅ バックドロップが正しく削除される

### デバッグ方法

ブラウザコンソール（F12）で以下のログを確認：

```
[HVAC-Lighting] Modal hoisted to body
[HVAC-Lighting] Modal shown, initializing document manager
[HVAC-Lighting] Document manager refreshed
[HVAC-Lighting] Modal z-index set: hvac-lighting-documents-modal 9999
[HVAC-Lighting] Backdrop z-index updated, count: 1
[HVAC-Lighting] Cleaned up extra backdrops
```

## トラブルシューティング

### モーダルが操作できない場合

1. **ブラウザコンソールを確認**
   ```
   F12キー → Consoleタブ
   ```
   - `[HVAC-Lighting] Modal hoisted to body`が表示されているか確認
   - エラーメッセージがないか確認

2. **z-indexを確認**
   ```
   F12キー → Elementsタブ → #hvac-lighting-documents-modal を選択
   ```
   - `z-index: 9999`が設定されているか確認

3. **モーダルの位置を確認**
   ```
   F12キー → Elementsタブ → #hvac-lighting-documents-modal を選択
   ```
   - モーダルが`<body>`直下にあるか確認

4. **DocumentModalFixを手動で実行**
   ```javascript
   // ブラウザコンソールで実行
   window.documentModalFix.hoistModal('hvac-lighting-documents-modal');
   ```

### バックドロップが残る場合

```javascript
// ブラウザコンソールで実行
document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
```

## 他のライフライン設備との統一性

空調・照明設備のドキュメント管理モーダルは、以下の点で他のライフライン設備と統一されています：

### 1. ボタンの配置とスタイル
- ✅ 設備情報ヘッダーの右側に配置
- ✅ `btn-outline-primary btn-sm`クラスを使用
- ✅ フォルダアイコンとテキストを表示

### 2. モーダルの構造
- ✅ Bootstrap標準のモーダル構造
- ✅ `modal-xl`クラスで大きなサイズ
- ✅ ヘッダーに設備カテゴリの色を適用（緑）
- ✅ フッターに閉じるボタンを配置

### 3. JavaScript処理
- ✅ モーダルhoisting処理
- ✅ z-indexの動的設定
- ✅ バックドロップのクリーンアップ
- ✅ ドキュメントマネージャーの初期化

### 4. CSS設定
- ✅ z-index階層構造（9998-10000）
- ✅ pointer-eventsの明示的設定
- ✅ レスポンシブ対応

### 5. 統一ファイルへの登録
- ✅ DocumentModalFix.jsに登録
- ✅ document-modal-fix.cssに登録

## まとめ

空調・照明設備のドキュメント管理を、他のライフライン設備と同じモーダルボタン方式に変更しました。これにより、すべてのライフライン設備で統一された操作性を実現しました。

### 完了した修正
- ✅ 電気設備
- ✅ ガス設備
- ✅ 水道設備
- ✅ エレベーター設備
- ✅ 空調・照明設備（本修正）

### 統一された機能
- ✅ モーダルボタン方式
- ✅ モーダルhoisting処理
- ✅ z-index階層構造
- ✅ pointer-events設定
- ✅ バックドロップクリーンアップ
- ✅ デバッグログ出力

---

**修正日**: 2025年10月16日  
**バージョン**: 1.3.0  
**ステータス**: ✅ 修正完了
