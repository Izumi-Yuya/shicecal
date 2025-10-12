# ライフライン設備ドキュメント機能のモーダル化

## 概要
ライフライン設備のドキュメント管理機能を、折りたたみ（collapse）形式からモーダル形式に変更しました。

## 変更日
2025年10月12日

## 変更理由
- **ユーザビリティの向上**: モーダルを使用することで、ドキュメント管理に集中できる専用の画面を提供
- **コードの簡素化**: 折りたたみ関連の複雑なJavaScript処理（modal hoisting、z-index調整など）が不要に
- **一貫性の向上**: 他の機能と同様のモーダルUIパターンを採用

## 変更内容

### 1. ボタンの変更
**変更前（折りたたみ）:**
```blade
<button type="button" 
        class="btn btn-outline-primary btn-sm" 
        id="electrical-documents-toggle"
        data-bs-toggle="collapse" 
        data-bs-target="#electrical-documents-section" 
        aria-expanded="false" 
        aria-controls="electrical-documents-section">
    <i class="fas fa-folder-open me-1"></i>
    <span class="d-none d-md-inline">ドキュメント</span>
</button>
```

**変更後（モーダル）:**
```blade
<button type="button" 
        class="btn btn-outline-primary btn-sm" 
        id="electrical-documents-toggle"
        data-bs-toggle="modal" 
        data-bs-target="#electrical-documents-modal">
    <i class="fas fa-folder-open me-1"></i>
    <span class="d-none d-md-inline">ドキュメント</span>
</button>
```

### 2. 折りたたみセクションの削除
折りたたみ式のドキュメント管理セクション全体を削除しました。

### 3. モーダルの追加
各設備タイプに対応するモーダルを追加しました。

**電気設備:**
```blade
<div class="modal fade" id="electrical-documents-modal" tabindex="-1" aria-labelledby="electrical-documents-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="electrical-documents-modal-title">
                    <i class="fas fa-folder-open me-2"></i>電気設備ドキュメント管理
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                <x-lifeline-document-manager
                    :facility="$facility"
                    category="electrical"
                    categoryName="電気設備"
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
```

**ガス設備:** `bg-danger` を使用
**水道設備:** `bg-info` を使用
**エレベーター設備:** `bg-secondary` を使用

### 4. JavaScriptの簡素化
折りたたみ関連の複雑なJavaScript処理を削除しました。

**削除されたコード:**
- ボタン状態の更新処理
- collapse イベントリスナー
- modal hoisting 処理
- z-index 調整処理
- backdrop クリーンアップ処理

**変更後:**
```javascript
<script>
// モーダル形式に変更したため、折りたたみ関連のJavaScriptは不要
// ドキュメントマネージャーはapp-unified.jsで自動初期化される
</script>
```

### 5. CSSの簡素化
折りたたみ関連のCSSスタイルを削除しました。

**削除されたスタイル:**
- ボタンのホバーエフェクト
- カードのスタイリング
- 折りたたみセクションのスタイル
- z-index調整
- レスポンシブ対応

**変更後:**
```css
<style>
/* モーダル形式に変更したため、折りたたみ関連のCSSは不要 */
/* モーダルスタイルはapp-unified.cssで統一管理 */
</style>
```

## 変更されたファイル

1. **resources/views/facilities/lifeline-equipment/electrical.blade.php**
   - ボタンをモーダルトリガーに変更
   - 折りたたみセクションを削除
   - モーダルを追加

2. **resources/views/facilities/lifeline-equipment/gas.blade.php**
   - ボタンをモーダルトリガーに変更
   - 折りたたみセクションを削除
   - モーダルを追加
   - JavaScriptを簡素化
   - CSSを簡素化

3. **resources/views/facilities/lifeline-equipment/water.blade.php**
   - ボタンをモーダルトリガーに変更
   - 折りたたみセクションを削除
   - モーダルを追加
   - JavaScriptを簡素化
   - CSSを簡素化

4. **resources/views/facilities/lifeline-equipment/elevator.blade.php**
   - ボタンをモーダルトリガーに変更
   - 折りたたみセクションを削除
   - モーダルを追加
   - JavaScriptを簡素化
   - CSSを簡素化

## モーダルの特徴

### サイズ
- `modal-xl`: 大きなサイズのモーダルを使用してドキュメント管理に十分なスペースを確保
- `modal-dialog-scrollable`: コンテンツが多い場合にスクロール可能

### ヘッダーカラー
各設備タイプに対応した色を使用：
- **電気設備**: `bg-primary` (青)
- **ガス設備**: `bg-danger` (赤)
- **水道設備**: `bg-info` (水色)
- **エレベーター設備**: `bg-secondary` (グレー)

### 動作
- `data-bs-backdrop="static"`: 背景クリックでモーダルが閉じないように設定
- `data-bs-keyboard="true"`: ESCキーでモーダルを閉じることが可能
- `btn-close-white`: 白い閉じるボタンを使用（ヘッダーが暗い色のため）

## メリット

### ユーザー体験
1. **専用画面**: ドキュメント管理に集中できる専用の画面
2. **視認性向上**: 大きなモーダルでドキュメント一覧が見やすい
3. **操作性向上**: モーダル内で完結する操作フロー

### 開発・保守性
1. **コードの簡素化**: 複雑なJavaScript処理が不要
2. **バグの削減**: z-indexやmodal hoistingの問題が解消
3. **保守性向上**: シンプルな構造で理解しやすい
4. **一貫性**: 他の機能と同じUIパターン

### パフォーマンス
1. **初期読み込み**: 折りたたみセクションが常に読み込まれない
2. **JavaScript削減**: 不要なイベントリスナーやDOM操作が削減

## 使用方法

1. ライフライン設備タブを開く
2. 各設備（電気、ガス、水道、エレベーター）のヘッダーにある「ドキュメント」ボタンをクリック
3. モーダルが開き、ドキュメント管理機能が表示される
4. ドキュメントの閲覧、アップロード、フォルダ作成などの操作を実行
5. 「閉じる」ボタンまたはESCキーでモーダルを閉じる

## 互換性

- **Bootstrap 5.x**: Bootstrap 5のモーダルコンポーネントを使用
- **既存機能**: ドキュメントマネージャーの機能は変更なし
- **権限管理**: 既存の権限チェックをそのまま使用

## テスト項目

- [ ] 各設備のドキュメントボタンをクリックしてモーダルが開く
- [ ] モーダル内でドキュメント一覧が正しく表示される
- [ ] ファイルアップロード機能が動作する
- [ ] フォルダ作成機能が動作する
- [ ] ファイルダウンロード機能が動作する
- [ ] モーダルを閉じる機能が動作する（ボタン、ESCキー）
- [ ] 権限に応じた表示・非表示が正しく動作する
- [ ] レスポンシブデザインが正しく動作する

## 今後の展開

この変更により、以下の改善が可能になります：

1. **他の機能への適用**: 同様のパターンを他の機能にも適用可能
2. **機能拡張**: モーダル内に追加機能を実装しやすい
3. **UI統一**: システム全体のUI一貫性が向上

## 参考資料

- [Bootstrap 5 Modal Documentation](https://getbootstrap.com/docs/5.1/components/modal/)
- [modal-implementation-guide.md](.kiro/steering/modal-implementation-guide.md)
- [lifeline-document-management.md](./lifeline-document-management.md)


---

## トラブルシューティング: モーダルz-index問題の修正 (2025-10-12)

### 問題の概要

ライフライン設備ドキュメントモーダルが開いた際、以下の問題が発生していました：

1. **モーダルが背面に表示される**: バックドロップの後ろにモーダルが隠れる
2. **操作不能**: モーダル内のボタンやフォームがクリックできない
3. **残留バックドロップ**: モーダルを閉じた後もバックドロップが残る

### 根本原因

- Bootstrapのデフォルトz-index設定が不十分
- 折りたたみ領域（collapse）内でモーダルをレンダリングすると、親要素のスタッキングコンテキストに負ける
- モーダルイベントリスナーが`{ once: true }`で1回しか実行されない

### 実装した恒久対策

#### 1. z-index自動調整機能

**LifelineDocumentManager.js**に追加：

```javascript
adjustModalZIndex(modalEl) {
  try {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const topBackdrop = backdrops[backdrops.length - 1];
    const backdropZ = parseInt(getComputedStyle(topBackdrop)?.zIndex || '1050', 10);
    
    modalEl.style.zIndex = String(backdropZ + 20);
    const dialog = modalEl.querySelector('.modal-dialog');
    if (dialog) {
      dialog.style.zIndex = String(backdropZ + 21);
    }
  } catch (e) {
    console.warn('[LifelineDoc] z-index adjust failed:', e);
  }
}
```

#### 2. バックドロップクリーンアップ機能

```javascript
cleanupBackdrops() {
  const backdrops = document.querySelectorAll('.modal-backdrop');
  if (backdrops.length > 1) {
    for (let i = 0; i < backdrops.length - 1; i++) {
      backdrops[i].remove();
    }
  }
  
  // 孤立したバックドロップの処理
  const anyModal = document.querySelector('.modal.show');
  if (!anyModal && backdrops.length) {
    backdrops.forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }
}
```

#### 3. モーダルイベントリスナーの強化

**openInModal()メソッドを修正**：

```javascript
openInModal() {
  const modal = document.getElementById(`${this.category}-documents-modal`);
  if (!modal) {
    console.error(`Modal not found: ${this.category}-documents-modal`);
    return;
  }

  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();

  // モーダル表示時のイベントリスナー
  const shownHandler = () => {
    try {
      this.adjustModalZIndex(modal);
      this.loadDocuments();
    } catch (e) {
      console.error('[LifelineDoc] Error in modal shown handler:', e);
    }
  };

  const hiddenHandler = () => {
    this.cleanupBackdrops();
  };

  // { once: true } を外して毎回実行
  modal.addEventListener('shown.bs.modal', shownHandler);
  modal.addEventListener('hidden.bs.modal', hiddenHandler);
}
```

#### 4. CSS強制適用

**app-unified.css**に追加：

```css
/* ===== Modal Styles ===== */
.modal {
    z-index: 1080 !important;
}

.modal .modal-dialog {
    z-index: 1081 !important;
}

.modal-backdrop {
    z-index: 1070 !important;
}

/* ライフラインドキュメントモーダル専用 */
.modal[id$="-documents-modal"] {
    z-index: 1090 !important;
}

.modal[id$="-documents-modal"] .modal-dialog {
    z-index: 1091 !important;
}
```

#### 5. app-unified.jsにも同様の機能を追加

グローバルなモーダル管理機能として、`adjustModalZIndex()`と`cleanupBackdrops()`メソッドを追加。

### デバッグユーティリティ

開発環境で問題が発生した場合、ブラウザコンソールで以下を実行：

```javascript
// 即座にモーダル状態を修復
(function(){
  const anyModal = document.querySelector('.modal.show');
  const backdrops = document.querySelectorAll('.modal-backdrop');
  if (!anyModal && backdrops.length) {
    backdrops.forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }
  const modal = [...document.querySelectorAll('.modal.show')].pop();
  const bd = [...document.querySelectorAll('.modal-backdrop')].pop();
  if (modal && bd) {
    const bz = parseInt(getComputedStyle(bd).zIndex || '1050', 10);
    modal.style.zIndex = String(bz + 20);
    modal.querySelector('.modal-dialog')?.style && (modal.querySelector('.modal-dialog').style.zIndex = String(bz + 21));
  }
  console.log('[fix] Modal state restored');
})();
```

詳細なデバッグユーティリティは`resources/js/debug/modal-fix.js`を参照。

### 修正ファイル一覧

- `resources/js/modules/LifelineDocumentManager.js`
- `resources/js/app-unified.js`
- `resources/css/app-unified.css`
- `resources/js/debug/modal-fix.js` (新規作成)

### テスト確認項目

- [x] モーダルが最前面に表示される
- [x] モーダル内のボタンがクリック可能
- [x] モーダルを閉じた後、バックドロップが残らない
- [x] 複数回モーダルを開閉しても正常動作
- [x] ESCキーでモーダルが閉じる
- [x] バックドロップクリックでモーダルが閉じない（data-bs-backdrop="static"）

### 今後の予防策

1. **折りたたみ領域内でモーダルを使用する場合は、必ずモーダルhoisting処理を実装する**
2. **z-index調整とバックドロップクリーンアップを標準パターンとして適用**
3. **モーダルイベントリスナーは`{ once: true }`を使わず、毎回実行する**
4. **CSS `!important`でz-indexを強制設定**

この修正により、ライフライン設備ドキュメントモーダルが確実に操作可能になりました。
