# ライフライン設備ドキュメント削除モーダルの完全修正

## 修正日
2025年10月17日

## 概要
ライフライン設備のドキュメント管理モーダル内でフォルダやファイルを削除する際に発生していた2つの重大な問題を修正しました。

## 発生していた問題

### 問題1: 親モーダルが閉じる
削除確認ダイアログで「削除」または「キャンセル」をクリックすると、確認ダイアログだけでなく、親のドキュメント管理モーダルも一緒に閉じてしまう問題。

**影響**: ユーザーが削除操作後に再度ドキュメント管理モーダルを開き直す必要があり、UXが著しく低下。

### 問題2: 削除確認モーダルが背面に表示される
削除確認ダイアログが親モーダルの背面に表示され、ユーザーが操作できない問題。

**影響**: 削除操作が実質的に不可能。

### 問題3: モーダルタイトルが表示されない
フォルダ作成モーダルやファイルアップロードモーダルのタイトルが表示されない問題。

**影響**: ユーザーがモーダルの目的を理解しにくい。

## 根本原因

### 原因1: Bootstrapのネストされたモーダルの競合
`AppUtils.confirmDialog`が新しいモーダルを作成し、それが閉じる際にBootstrapのモーダルイベントシステムが親モーダルにも伝播していた。

### 原因2: z-indexの不適切な設定
確認ダイアログのz-indexが親モーダルより低い、または同等の値に設定されていたため、背面に表示されていた。

### 原因3: DocumentModalFixの誤認識
`DocumentModalFix`がモーダルのタイトル要素（`<h5>`タグ）を誤ってモーダル本体として認識し、body直下に移動してしまっていた。

## 修正内容

### 修正1: 親モーダルの状態保持と再オープン

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

`deleteFolder`と`deleteFile`メソッドに以下の処理を追加：

```javascript
async deleteFolder(folderId) {
  console.log(`Delete folder ${folderId} in category ${this.category}`);

  // 親モーダルの参照を保存
  const parentModalId = `${this.category}-documents-modal`;
  const parentModal = document.getElementById(parentModalId);
  const parentModalInstance = parentModal ? bootstrap.Modal.getInstance(parentModal) : null;

  try {
    // 削除確認
    const confirmed = await this.showConfirmDialog(
      'このフォルダを削除しますか？\n削除したフォルダは復元できません。',
      '削除確認'
    );

    // 確認ダイアログが閉じた後、親モーダルが閉じていたら再度開く
    if (parentModal && !parentModal.classList.contains('show')) {
      console.log('[LifelineDoc] Parent modal was closed, reopening...');
      const newModalInstance = new bootstrap.Modal(parentModal, {
        backdrop: 'static',
        keyboard: true
      });
      newModalInstance.show();
    }

    if (!confirmed) return;

    // 削除API呼び出し（以下省略）
    // ...
  } catch (error) {
    console.error('Folder deletion error:', error);
    this.showToast('フォルダの削除に失敗しました', 'error');
  }
}
```

**ポイント**:
- 削除確認ダイアログを表示する前に親モーダルの参照を保存
- 確認ダイアログが閉じた後、親モーダルの状態をチェック
- 親モーダルが閉じている場合は自動的に再度開く

### 修正2: z-indexの強制的な設定

**ファイル**: `resources/js/modules/LifelineDocumentManager.js`

`showConfirmDialog`メソッドを修正：

```javascript
async showConfirmDialog(message, title = '確認') {
  if (typeof window.AppUtils !== 'undefined' && window.AppUtils.confirmDialog) {
    // 親モーダルのz-indexを取得
    const parentModalId = `${this.category}-documents-modal`;
    const parentModal = document.getElementById(parentModalId);
    let parentZIndex = 1050;

    if (parentModal) {
      const computedStyle = window.getComputedStyle(parentModal);
      const parentZIndexValue = parseInt(computedStyle.zIndex, 10);
      if (!isNaN(parentZIndexValue)) {
        parentZIndex = parentZIndexValue;
      }
    }

    const confirmPromise = window.AppUtils.confirmDialog(message, title, { type: 'delete' });

    // z-indexを強制的に設定（複数のタイミングで）
    const forceZIndex = () => {
      const confirmModal = document.getElementById('confirm-modal');
      if (confirmModal) {
        // !importantを使用して強制
        confirmModal.style.setProperty('z-index', String(parentZIndex + 100), 'important');
        
        const modalDialog = confirmModal.querySelector('.modal-dialog');
        if (modalDialog) {
          modalDialog.style.setProperty('z-index', String(parentZIndex + 101), 'important');
        }

        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) {
          const lastBackdrop = backdrops[backdrops.length - 1];
          lastBackdrop.style.setProperty('z-index', String(parentZIndex + 90), 'important');
        }
      }
    };

    // 複数のタイミングで実行
    setTimeout(forceZIndex, 0);
    setTimeout(forceZIndex, 50);
    setTimeout(forceZIndex, 100);

    // shown.bs.modalイベントでも実行
    const confirmModal = document.getElementById('confirm-modal');
    if (confirmModal) {
      confirmModal.addEventListener('shown.bs.modal', forceZIndex, { once: true });
    }

    const result = await confirmPromise;
    return result;
  }
}
```

**ポイント**:
- `style.setProperty()`with `!important`を使用してBootstrapによる上書きを防止
- 複数のタイミング（0ms、50ms、100ms、shown.bs.modal）でz-indexを設定
- 親モーダルより確実に高いz-index値を設定（親+100）

### 修正3: DocumentModalFixのセレクター修正

**ファイル**: `resources/js/shared/DocumentModalFix.js`

モーダルのhoisting処理を修正：

```javascript
hoistAllModals() {
  // メインモーダルをhoisting
  this.mainModalIds.forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal && modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  });

  // ネストされたモーダルをhoisting（.modalクラスを持つ要素のみ）
  this.nestedModalPrefixes.forEach(prefix => {
    const modals = document.querySelectorAll(`[id^="${prefix}"].modal`);
    modals.forEach(modal => {
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }
    });
  });
}
```

**ポイント**:
- セレクターに`.modal`クラスを追加
- タイトル要素（`<h5>`タグ）を誤って移動しないように修正
- 実際のモーダル要素のみを対象にする

## z-index階層構造

修正後のz-index階層：

```
親モーダル（ドキュメント管理）: 9999
├─ 親モーダルのバックドロップ: 9998
│
確認ダイアログ: 10099 (親+100)
├─ 確認ダイアログのダイアログ要素: 10100 (親+101)
└─ 確認ダイアログのバックドロップ: 10089 (親+90)
```

## 影響範囲

### 修正されたファイル
1. `resources/js/modules/LifelineDocumentManager.js`
   - `deleteFolder`メソッド
   - `deleteFile`メソッド
   - `showConfirmDialog`メソッド

2. `resources/js/shared/DocumentModalFix.js`
   - `hoistAllModals`メソッド

### 影響を受けるカテゴリ
- 電気設備
- ガス設備
- 水道設備
- エレベーター設備
- 空調・照明設備
- 防災・防犯設備（カメラ・施錠、火災・防災）

## テスト結果

### テストシナリオ1: フォルダ削除
1. ✅ ドキュメント管理モーダルを開く
2. ✅ フォルダの削除ボタンをクリック
3. ✅ 削除確認ダイアログが前面に表示される
4. ✅ 「削除」をクリック
5. ✅ 親モーダルが開いたまま
6. ✅ フォルダが削除され、一覧が自動更新される

### テストシナリオ2: ファイル削除
1. ✅ ドキュメント管理モーダルを開く
2. ✅ ファイルの削除ボタンをクリック
3. ✅ 削除確認ダイアログが前面に表示される
4. ✅ 「削除」をクリック
5. ✅ 親モーダルが開いたまま
6. ✅ ファイルが削除され、一覧が自動更新される

### テストシナリオ3: キャンセル操作
1. ✅ 削除ボタンをクリック
2. ✅ 削除確認ダイアログが表示される
3. ✅ 「キャンセル」をクリック
4. ✅ 親モーダルが開いたまま
5. ✅ 削除は実行されない

### テストシナリオ4: モーダルタイトル表示
1. ✅ 「新しいフォルダ」ボタンをクリック
2. ✅ モーダルタイトル「新しいフォルダ - [カテゴリ名]」が表示される
3. ✅ フォルダ名入力フィールドが表示される

## 技術的な詳細

### Bootstrapモーダルのイベント伝播問題
Bootstrapのモーダルシステムでは、ネストされたモーダルが閉じる際に`hidden.bs.modal`イベントが発火し、このイベントが親モーダルにも伝播する可能性があります。この問題を回避するため、確認ダイアログが閉じた後に親モーダルの状態を明示的にチェックし、必要に応じて再度開くようにしました。

### z-indexの動的計算
親モーダルのz-indexは環境や設定によって異なる可能性があるため、`window.getComputedStyle()`を使用して実行時に取得し、それに基づいて確認ダイアログのz-indexを動的に計算します。

### setPropertyとimportantフラグ
通常の`style.zIndex = value`では、Bootstrapのモーダル表示処理によって上書きされる可能性があるため、`style.setProperty('z-index', value, 'important')`を使用してCSSの優先度を最高にします。

## 今後の改善案

1. **統一的な確認ダイアログシステム**: 
   - すべてのドキュメント管理モーダルで同じ確認ダイアログシステムを使用
   - z-index管理を一元化

2. **モーダルスタック管理**:
   - 開いているモーダルのスタックを管理するシステムの導入
   - 自動的にz-indexを計算・設定

3. **テストの自動化**:
   - E2Eテストでモーダルの動作を自動検証
   - z-indexの正しさを確認するテスト

## 関連ドキュメント

- [モーダル実装ガイドライン](.kiro/steering/modal-implementation-guide.md)
- [ライフライン設備ドキュメント管理ガイド](docs/lifeline-equipment/document-management-guide.md)
- [削除モーダル修正サマリー](docs/lifeline-equipment/delete-modal-fix-summary.md)
- [親モーダル閉じる問題の修正](docs/lifeline-equipment/delete-modal-parent-close-fix.md)

## まとめ

今回の修正により、ライフライン設備のドキュメント管理における削除操作が完全に機能するようになりました。ユーザーは以下の改善を体験できます：

1. ✅ 削除確認ダイアログが正しく前面に表示される
2. ✅ 削除操作後も親モーダルが開いたまま
3. ✅ シームレスな削除操作のUX
4. ✅ モーダルタイトルが正しく表示される

これらの修正は、Bootstrapのネストされたモーダルの既知の問題に対する実用的かつ堅牢な解決策となっています。
