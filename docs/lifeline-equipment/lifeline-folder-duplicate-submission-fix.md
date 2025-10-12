# ライフライン設備フォルダ作成 - 重複送信防止の修正

## 問題の発見

ユーザーからの報告:
> 作成ボタンを押した時に複数回作成を実行していませんか？

ブラウザコンソールのログ:
```
POST http://127.0.0.1:8000/facilities/102/lifeline-documents/electrical/folders 422
POST http://127.0.0.1:8000/facilities/102/lifeline-documents/electrical/folders 422
```

**同じリクエストが複数回送信されている！**

## 根本原因

`LifelineDocumentManager` の `handleCreateFolder` メソッドに**重複送信防止の仕組みがなかった**

比較:
- ✅ `handleUploadFile` → `isUploading` フラグあり
- ❌ `handleCreateFolder` → フラグなし

## 実装した修正

### 1. 重複送信防止フラグの追加

```javascript
// コンストラクタ
constructor(facilityId = null, category = null) {
  // ...
  this.isUploading = false;
  this.isCreatingFolder = false;  // ← 追加
}
```

### 2. handleCreateFolder の改善

```javascript
async handleCreateFolder(event) {
  event.preventDefault();
  event.stopPropagation();
  event.stopImmediatePropagation();  // ← 追加

  const form = event.target;

  // 重複送信防止（3層の防御）
  if (this.isCreatingFolder || form.dataset.submitting === 'true') {
    console.log('Folder creation already in progress, ignoring duplicate request');
    return;
  }

  // フラグを設定
  this.isCreatingFolder = true;
  form.dataset.submitting = 'true';

  try {
    // ... フォルダ作成処理 ...
  } finally {
    // フラグをリセット
    this.isCreatingFolder = false;
    form.dataset.submitting = 'false';
  }
}
```

### 3. イベントリスナーの改善

```javascript
// capture モードで登録
createFolderForm.addEventListener('submit', handler, { capture: true });
```

## 3層の防御メカニズム

### 第1層: インスタンスフラグ
```javascript
if (this.isCreatingFolder) return;
```
- クラスインスタンスレベルでの制御
- 最も基本的な防御

### 第2層: DOM属性チェック
```javascript
if (form.dataset.submitting === 'true') return;
```
- フォーム要素自体に状態を保持
- インスタンスが複数ある場合にも対応

### 第3層: イベント伝播の完全停止
```javascript
event.preventDefault();
event.stopPropagation();
event.stopImmediatePropagation();
```
- イベントバブリングを完全に停止
- 他のリスナーへの伝播を防止

## 効果

### Before（修正前）
```
ユーザーがボタンをクリック
  ↓
複数のPOSTリクエストが送信される
  ↓
サーバーで重複処理が発生
  ↓
422エラー（フォルダ名重複）
```

### After（修正後）
```
ユーザーがボタンをクリック
  ↓
フラグチェック → 処理中なら即座にreturn
  ↓
1回だけPOSTリクエストが送信される
  ↓
正常にフォルダ作成完了
```

## テスト方法

### 連続クリックテスト
1. フォルダ作成モーダルを開く
2. フォルダ名を入力
3. **作成ボタンを素早く5回クリック**
4. コンソールで確認:
   ```
   Creating folder with data: ...
   Folder creation already in progress, ignoring duplicate request
   Folder creation already in progress, ignoring duplicate request
   Folder creation already in progress, ignoring duplicate request
   Folder creation already in progress, ignoring duplicate request
   ```
5. ネットワークタブで**POSTリクエストが1回だけ**送信されることを確認

### ネットワーク遅延テスト
1. 開発者ツールで「Slow 3G」に設定
2. フォルダ作成を実行
3. レスポンスが返る前に再度ボタンをクリック
4. 2回目のクリックが無視されることを確認

## 適用範囲

この修正パターンは以下にも適用可能:
- ✅ ファイルアップロード（既に実装済み）
- ✅ フォルダ作成（今回実装）
- 🔄 フォルダ名変更（今後適用予定）
- 🔄 ファイル削除（今後適用予定）
- 🔄 フォルダ削除（今後適用予定）

## ベストプラクティス

### 非同期処理の重複送信防止チェックリスト

- [ ] インスタンスフラグ（`isProcessing`）を追加
- [ ] DOM属性チェック（`data-submitting`）を追加
- [ ] イベント伝播を停止（`stopImmediatePropagation`）
- [ ] ボタンを無効化（`disabled = true`）
- [ ] ローディング表示を追加
- [ ] `finally` ブロックでフラグをリセット
- [ ] イベントリスナーを `capture: true` で登録

## 関連ファイル

- `resources/js/modules/LifelineDocumentManager.js` - 修正実装
- `docs/lifeline-folder-422-error-analysis.md` - 詳細分析
- `resources/views/components/lifeline-document-manager.blade.php` - UI

## 修正日時

2025年10月10日

## 修正者

Kiro AI Assistant
