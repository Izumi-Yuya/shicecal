# ライフライン設備ドキュメント管理 - 削除確認モーダル修正サマリー

## 問題の概要

ライフライン設備のドキュメント管理画面で、折りたたみ領域内から削除確認モーダルを開いた際、モーダルが背面に表示されて操作できない問題が発生していました。

### 問題の原因

1. **スタッキングコンテキストの問題**: 折りたたみ領域（`.collapse`）が新しいスタッキングコンテキストを作成し、その中にモーダルが配置されていた
2. **z-indexの競合**: 通常のモーダル（z-index: 2010）と削除確認モーダル（z-index: 2050）が同じスタッキングコンテキスト内で競合
3. **モーダルの配置**: 削除確認モーダルが折りたたみ領域内に作成されていた

## 修正内容

### 1. AppUtils.confirmDialog の修正

**ファイル**: `resources/js/shared/AppUtils.js`

削除確認モーダルが表示された際に、z-indexを強制的に設定するように修正しました。

```javascript
modal.addEventListener('shown.bs.modal', () => {
  // Ensure modal is on top with proper z-index
  modal.style.zIndex = '2050';
  modal.style.position = 'fixed';
  
  const dialog = modal.querySelector('.modal-dialog');
  if (dialog) {
    dialog.style.zIndex = '2051';
  }
  
  // Adjust backdrop z-index
  const backdrops = document.querySelectorAll('.modal-backdrop');
  backdrops.forEach(backdrop => {
    backdrop.style.zIndex = '2040';
  });
  
  okButton.focus();
}, { once: true });
```

**修正のポイント**:
- モーダル表示時（`shown.bs.modal`イベント）にz-indexを設定
- モーダル本体: `z-index: 2050`
- モーダルダイアログ: `z-index: 2051`
- バックドロップ: `z-index: 2040`
- `position: fixed`を明示的に設定

### 2. LifelineDocumentManager の簡略化

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

`showConfirmDialog`メソッドを簡略化し、`AppUtils.confirmDialog`に処理を委譲するように変更しました。

```javascript
async showConfirmDialog(message, title = '確認') {
  if (typeof window.AppUtils !== 'undefined' && window.AppUtils.confirmDialog) {
    // AppUtils.confirmDialogは既にbody直下にモーダルを作成し、
    // z-indexも適切に設定するため、そのまま呼び出す
    return await window.AppUtils.confirmDialog(message, title, { type: 'delete' });
  } else {
    // フォールバック: 標準confirm
    return confirm(message);
  }
}
```

**変更点**:
- 重複するz-index調整処理を削除
- `AppUtils.confirmDialog`に処理を一元化
- コードの保守性が向上

### 3. CSS の修正

**ファイル**: `resources/css/lifeline-document-management.css`

削除確認モーダルのz-indexをCSSでも明示的に設定しました。

```css
/* Confirm dialog modal z-index fix - ensure it's always on top */
#confirm-modal {
  z-index: 2050 !important;
  position: fixed !important;
}

#confirm-modal + .modal-backdrop {
  z-index: 2040 !important;
}

/* Ensure confirm modal dialog is also properly positioned */
#confirm-modal .modal-dialog {
  z-index: 2051 !important;
}
```

**修正のポイント**:
- `!important`を使用して他のスタイルを上書き
- `position: fixed`を明示的に設定
- モーダルダイアログにも個別にz-indexを設定

## Z-Index 階層構造

修正後のz-index階層は以下の通りです：

```
2051: 削除確認モーダルダイアログ (#confirm-modal .modal-dialog)
2050: 削除確認モーダル (#confirm-modal)
2040: 削除確認バックドロップ (#confirm-modal + .modal-backdrop)
2010: 通常モーダル (.modal)
2000: 通常バックドロップ (.modal-backdrop)
```

この階層により、削除確認モーダルは常に他のモーダルより前面に表示されます。

## テスト方法

### 1. 手動テスト

1. ライフライン設備の詳細画面を開く
2. 「ドキュメント管理」ボタンをクリックして折りたたみ領域を展開
3. ドキュメント一覧でファイルまたはフォルダの削除ボタンをクリック
4. 削除確認モーダルが最前面に表示されることを確認
5. モーダルが操作可能であることを確認（ボタンクリック、ESCキーで閉じる等）

### 2. テストHTMLファイル

`test-lifeline-delete-modal-fix.html`を使用して、修正内容を単独でテストできます。

```bash
# ブラウザで開く
open test-lifeline-delete-modal-fix.html
```

テストシナリオ:
1. 「ドキュメント管理を開く」ボタンをクリック
2. 「削除テスト」ボタンをクリック
3. 削除確認モーダルが最前面に表示されることを確認
4. 「通常モーダルテスト」ボタンで通常モーダルとの違いを確認

## 影響範囲

### 修正されたファイル

1. `resources/js/shared/AppUtils.js` - 削除確認モーダルのz-index設定を追加
2. `resources/js/modules/LifelineDocumentManager.js` - `showConfirmDialog`メソッドを簡略化
3. `resources/css/lifeline-document-management.css` - 削除確認モーダルのCSSを修正

### 影響を受ける機能

- **ライフライン設備ドキュメント管理**: 全カテゴリ（電気、ガス、水道、エレベーター、空調・照明）
- **その他のドキュメント管理**: DocumentManager、ContractDocumentManager、MaintenanceDocumentManager
- **全ての削除確認ダイアログ**: `AppUtils.confirmDialog`を使用している全ての箇所

### 後方互換性

- 既存の機能に影響なし
- `AppUtils.confirmDialog`のAPIは変更なし
- 既存のコードは修正不要

## 技術的な詳細

### モーダルhoistingについて

`AppUtils.confirmDialog`は既に`document.body.insertAdjacentHTML('beforeend', modalHtml)`を使用してモーダルを`<body>`直下に作成しているため、モーダルhoisting（モーダルを親要素から移動）は不要でした。

問題は、モーダル作成後にz-indexが適切に設定されていなかったことでした。

### shown.bs.modalイベントの使用

Bootstrapの`shown.bs.modal`イベントを使用することで、モーダルが完全に表示された後にz-indexを設定できます。これにより、Bootstrapの内部処理と競合することなく、確実にz-indexを設定できます。

### !importantの使用理由

CSSで`!important`を使用している理由は、以下の通りです：

1. 他のスタイルシートやインラインスタイルを確実に上書き
2. スタッキングコンテキストの影響を最小化
3. 削除確認モーダルが常に最前面に表示されることを保証

## 今後の改善案

1. **統一されたモーダル管理**: 全てのモーダルで同じz-index管理方法を使用
2. **CSS変数の使用**: z-indexの値をCSS変数で管理し、一元化
3. **モーダルスタック管理**: 複数のモーダルが同時に開かれた場合の管理機能

## 関連ドキュメント

- [モーダル実装ガイドライン](../../.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備ドキュメント管理ガイド](./document-management-guide.md)
- [モーダルz-index修正サマリー](./modal-zindex-fix-summary.md)

## 変更履歴

- **2025-10-17**: 削除確認モーダルのz-index問題を修正
  - `AppUtils.confirmDialog`にz-index設定を追加
  - `LifelineDocumentManager.showConfirmDialog`を簡略化
  - CSSでz-indexを明示的に設定
