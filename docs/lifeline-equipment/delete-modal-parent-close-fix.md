# ライフライン設備ドキュメント削除時のモーダル問題の修正

## 問題の概要

ライフライン設備のドキュメント管理モーダル内でフォルダやファイルを削除しようとすると、以下の2つの問題が発生していました：

1. **確認ダイアログが背面に表示される**: 削除確認ダイアログが親のドキュメント管理モーダルの背面に表示され、操作できない
2. **親モーダルが閉じる**: 確認ダイアログで「削除」または「キャンセル」をクリックした後、親のドキュメント管理モーダルも一緒に閉じてしまう

## 問題の原因

### 1. z-index の問題
- ドキュメント管理モーダルのz-indexが9999に設定されている
- `AppUtils.confirmDialog`が作成する確認ダイアログのz-indexは2050（固定値）
- そのため、確認ダイアログが親モーダルの背面に表示される

### 2. モーダルイベントの伝播
- `AppUtils.confirmDialog`が新しいモーダルを作成し、それが閉じる際にBootstrapのモーダルイベントシステムが親モーダルにも影響を与える
- 確認ダイアログモーダルの`hidden.bs.modal`イベントが、親モーダルにも伝播し、親モーダルのインスタンスが閉じられる

## 修正内容

### 1. `showConfirmDialog`メソッドの修正（z-index問題の解決）

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

確認ダイアログのz-indexを親モーダルより高く設定するように修正しました：

```javascript
async showConfirmDialog(message, title = '確認') {
  if (typeof window.AppUtils !== 'undefined' && window.AppUtils.confirmDialog) {
    // 親モーダルのz-indexを取得
    const parentModalId = `${this.category}-documents-modal`;
    const parentModal = document.getElementById(parentModalId);
    let parentZIndex = 1050; // デフォルト値

    if (parentModal) {
      const computedStyle = window.getComputedStyle(parentModal);
      const parentZIndexValue = parseInt(computedStyle.zIndex, 10);
      if (!isNaN(parentZIndexValue)) {
        parentZIndex = parentZIndexValue;
      }
    }

    // 確認ダイアログを呼び出し（awaitせずにPromiseを取得）
    const confirmPromise = window.AppUtils.confirmDialog(message, title, { type: 'delete' });

    // 確認ダイアログが表示された直後にz-indexを調整
    setTimeout(() => {
      const confirmModal = document.getElementById('confirm-modal');
      if (confirmModal) {
        confirmModal.style.zIndex = String(parentZIndex + 100);
        
        // モーダルダイアログのz-indexも調整
        const modalDialog = confirmModal.querySelector('.modal-dialog');
        if (modalDialog) {
          modalDialog.style.zIndex = String(parentZIndex + 101);
        }
      }

      // バックドロップのz-indexも調整
      const backdrops = document.querySelectorAll('.modal-backdrop');
      if (backdrops.length > 0) {
        const lastBackdrop = backdrops[backdrops.length - 1];
        lastBackdrop.style.zIndex = String(parentZIndex + 90);
      }
    }, 10);

    // Promiseの結果を待つ
    const result = await confirmPromise;
    return result;
  }
}
```

### 2. `deleteFolder`メソッドの修正（親モーダル閉じる問題の解決）

親モーダルが閉じた場合に再度開くように修正しました：

```javascript
async deleteFolder(folderId) {
  // 親モーダルの参照を保存
  const parentModalId = `${this.category}-documents-modal`;
  const parentModal = document.getElementById(parentModalId);

  try {
    // 削除確認
    const confirmed = await this.showConfirmDialog(
      'このフォルダを削除しますか？\n削除したフォルダは復元できません。',
      '削除確認'
    );

    // 確認ダイアログが閉じた後、親モーダルが閉じていたら再度開く
    if (parentModal && !parentModal.classList.contains('show')) {
      const newModalInstance = new bootstrap.Modal(parentModal, {
        backdrop: 'static',
        keyboard: true
      });
      newModalInstance.show();
    }

    if (!confirmed) return;

    // 削除API呼び出し...
  }
}
```

### 3. `deleteFile`メソッドの修正

`deleteFolder`と同様の修正を適用しました。

## 修正のポイント

### z-index問題の解決

1. **親モーダルのz-indexを動的に取得**: `window.getComputedStyle()`を使用して、親モーダルの実際のz-indexを取得します。

2. **確認ダイアログのz-indexを動的に設定**: 親モーダルのz-index + 100に設定することで、常に確認ダイアログが前面に表示されます。

3. **バックドロップのz-indexも調整**: 確認ダイアログのバックドロップを親モーダルのz-index + 90に設定し、適切な重なり順を保ちます。

4. **タイミングの調整**: `setTimeout`を使用して、確認ダイアログが完全に作成された後にz-indexを調整します。

### 親モーダル閉じる問題の解決

1. **親モーダルの参照を事前に保存**: 確認ダイアログを表示する前に、親モーダルのDOM要素を取得して保存します。

2. **確認ダイアログ後の状態チェック**: `showConfirmDialog`が完了した後、親モーダルが閉じているかどうかを`classList.contains('show')`でチェックします。

3. **親モーダルの再オープン**: 親モーダルが閉じている場合、新しいBootstrap Modalインスタンスを作成して再度開きます。

4. **ユーザー体験の向上**: これらの修正により、ユーザーが削除確認ダイアログで「削除」または「キャンセル」をクリックした後も、ドキュメント管理モーダルは開いたままになり、確認ダイアログも正しく前面に表示されます。

## テスト方法

1. ライフライン設備タブを開く
2. 任意のカテゴリ（電気設備、ガス設備など）のドキュメント管理ボタンをクリック
3. ドキュメント管理モーダルが開く
4. フォルダまたはファイルの削除ボタンをクリック
5. 確認ダイアログが表示される
6. 「削除」または「キャンセル」をクリック
7. **期待される動作**: 確認ダイアログが閉じた後も、ドキュメント管理モーダルは開いたまま
8. 削除が成功した場合、ドキュメント一覧が自動的に更新される

## 影響範囲

- **修正ファイル**: `resources/js/modules/LifelineDocumentManager.js`
- **影響を受けるカテゴリ**: 
  - 電気設備
  - ガス設備
  - 水道設備
  - エレベーター設備
  - 空調・照明設備
  - 防災・防犯設備

## 関連ドキュメント

- [モーダル実装ガイドライン](.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備ドキュメント管理ガイド](docs/lifeline-equipment/document-management-guide.md)
- [削除モーダル修正サマリー](docs/lifeline-equipment/delete-modal-fix-summary.md)

## 修正日

2025年10月17日

## 備考

この修正は、Bootstrapのネストされたモーダルの既知の問題に対する実用的な回避策です。将来的には、Bootstrapのバージョンアップや、より洗練されたモーダル管理システムの導入により、さらに改善される可能性があります。

## 関連修正

この修正は以下の問題と合わせて対応されました：

1. **削除確認モーダルのz-index問題**: 確認ダイアログが親モーダルの背面に表示される問題
2. **モーダルタイトル表示問題**: DocumentModalFixがタイトル要素を誤って移動する問題

完全な修正内容については、[削除モーダル完全修正ドキュメント](delete-modal-complete-fix.md)を参照してください。
