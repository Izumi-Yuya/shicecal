# ライフライン設備ドキュメント削除モーダル z-index 修正

## 問題の概要

ライフライン設備のドキュメント管理機能で、削除ボタンを押すと確認モーダルが背面に隠れて操作できない問題が発生していました。

### 症状
- 削除ボタンをクリック
- 確認モーダルが表示されるが、背面に隠れて操作不可
- モーダルバックドロップが前面に表示され、モーダル本体が見えない

### 原因
1. **折りたたみ領域のスタッキングコンテキスト**: ライフライン設備タブは折りたたみ可能な領域内にあり、独自のスタッキングコンテキストを作成
2. **動的モーダル生成**: `AppUtils.confirmDialog`が動的にモーダルを生成するが、デフォルトのz-indexでは不十分
3. **z-index競合**: 既存のモーダルやバックドロップとz-indexが競合

## 修正内容

### 1. JavaScript修正 (`resources/js/modules/LifelineDocumentManager.js`)

`showConfirmDialog`メソッドを修正し、確認モーダルのz-indexを動的に調整：

```javascript
async showConfirmDialog(message, title = '確認') {
  if (typeof window.AppUtils !== 'undefined' && window.AppUtils.confirmDialog) {
    // モーダルが表示される前にz-indexを調整するためのイベントリスナーを設定
    const adjustZIndexOnShow = () => {
      const confirmModal = document.getElementById('confirm-modal');
      if (confirmModal) {
        // 確認モーダルを最前面に表示
        confirmModal.style.zIndex = '2050';
        
        // バックドロップも調整
        setTimeout(() => {
          const backdrops = document.querySelectorAll('.modal-backdrop');
          backdrops.forEach(backdrop => {
            backdrop.style.zIndex = '2040';
          });
        }, 50);
        
        console.log('[LifelineDoc] Adjusted confirm dialog z-index to 2050');
      }
    };
    
    // モーダル表示イベントを監視
    document.addEventListener('show.bs.modal', adjustZIndexOnShow, { once: true });
    
    try {
      return await window.AppUtils.confirmDialog(message, title, { type: 'delete' });
    } finally {
      // クリーンアップ（イベントが発火しなかった場合のため）
      document.removeEventListener('show.bs.modal', adjustZIndexOnShow);
    }
  } else {
    // フォールバック: 標準confirm
    return confirm(message);
  }
}
```

### 2. CSS修正 (`resources/css/lifeline-document-management.css`)

確認モーダルとバックドロップのz-indexを強制的に設定：

```css
/* Confirm dialog modal z-index fix - ensure it's always on top */
#confirm-modal {
  z-index: 2050 !important;
}

#confirm-modal + .modal-backdrop {
  z-index: 2040 !important;
}

/* Ensure all lifeline document sections don't create stacking context issues */
.lifeline-documents-section {
  position: relative;
  z-index: auto;
}

/* Gas documents section */
#gas-documents-section {
  overflow: visible;
}

/* Water documents section */
#water-documents-section {
  overflow: visible;
}

/* Elevator documents section */
#elevator-documents-section {
  overflow: visible;
}

/* HVAC/Lighting documents section */
#hvac-lighting-documents-section {
  overflow: visible;
}
```

## z-index階層構造

修正後のz-index階層：

```
2050: 確認モーダル (#confirm-modal)
2040: 確認モーダルバックドロップ
2010: 通常のモーダル（フォルダ作成、ファイルアップロード等）
2000: 通常のモーダルバックドロップ
1-10: 折りたたみ領域やその他のコンテンツ
```

## テスト方法

### 手動テスト
1. ライフライン設備タブを開く（電気、ガス、水道等）
2. ドキュメント管理セクションを展開
3. ファイルまたはフォルダを右クリック
4. 「削除」を選択
5. 確認モーダルが最前面に表示され、操作可能であることを確認

### テストファイル
`test-lifeline-delete-modal.html`を使用して、折りたたみ領域内でのモーダル表示をテスト可能：

```bash
# ブラウザで開く
open test-lifeline-delete-modal.html
```

## 影響範囲

### 修正対象
- ✅ 電気設備ドキュメント削除
- ✅ ガス設備ドキュメント削除
- ✅ 水道設備ドキュメント削除
- ✅ エレベーター設備ドキュメント削除
- ✅ 空調・照明設備ドキュメント削除

### 影響を受けない機能
- フォルダ作成モーダル（既存のz-index調整で対応済み）
- ファイルアップロードモーダル（既存のz-index調整で対応済み）
- 名前変更モーダル（既存のz-index調整で対応済み）
- プロパティモーダル（既存のz-index調整で対応済み）

## 技術的詳細

### なぜこの修正が必要だったか

1. **動的モーダル生成**: `AppUtils.confirmDialog`はモーダルを動的に生成し、`document.body`に追加します
2. **Bootstrap Modal z-index**: Bootstrapのデフォルトモーダルz-indexは1050
3. **折りたたみ領域のスタッキングコンテキスト**: 折りたたみ領域が`position: relative`や`z-index`を持つと、新しいスタッキングコンテキストが作成される
4. **z-index競合**: 既存のモーダル（z-index: 2010）よりも高いz-indexが必要

### イベントリスナーアプローチ

`show.bs.modal`イベントを使用することで：
- モーダルが表示される直前にz-indexを調整
- 他のモーダルとの競合を回避
- クリーンアップ処理で不要なイベントリスナーを削除

### CSS `!important`の使用理由

- Bootstrapのデフォルトスタイルを確実に上書き
- 他のCSSルールとの競合を防止
- 一貫した動作を保証

## 今後の改善案

1. **統一されたモーダル管理**: すべてのモーダルで同じz-index管理戦略を使用
2. **モーダルスタック管理**: 複数のモーダルが同時に開かれた場合の管理
3. **アクセシビリティ**: フォーカストラップとキーボードナビゲーションの改善

## 関連ドキュメント

- [モーダル実装ガイドライン](../../.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備モーダル修正完了](./lifeline-modal-fix-complete.md)
- [モーダルz-index修正サマリー](./modal-zindex-fix-summary.md)

## 変更履歴

- **2025-10-17**: 初版作成 - 削除モーダルz-index問題の修正
