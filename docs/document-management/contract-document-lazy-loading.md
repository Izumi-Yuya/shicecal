# 契約書ドキュメント管理 - 遅延ロード実装

## 概要

契約書タブの統一ドキュメント管理セクションに遅延ロード（Lazy Loading）機能を実装しました。この機能により、ページ初期表示時のパフォーマンスが向上し、ユーザーが実際にドキュメントセクションを展開するまでAPIリクエストが発生しません。

## 実装内容

### 1. 遅延ロードの仕組み

#### 初期化フロー
```javascript
constructor(facilityId) {
  this.facilityId = facilityId;
  this.category = 'contracts';
  this.isInitialLoad = true; // 初回ロードフラグ
  
  this.init();
}

init() {
  this.cacheElements();
  this.attachEventListeners();
  this.setupLazyLoading();
  // 初期ロードは行わない（遅延ロード）
}
```

#### 遅延ロード設定
```javascript
setupLazyLoading() {
  const unifiedSection = document.getElementById('unified-documents-section');
  
  if (!unifiedSection) {
    console.warn('[ContractDoc] Unified section not found, loading documents immediately');
    this.loadDocuments();
    return;
  }

  console.log('[ContractDoc] Lazy loading enabled - documents will load on first expand');

  // shown.bs.collapseイベントをリッスン
  unifiedSection.addEventListener('shown.bs.collapse', () => {
    if (this.isInitialLoad) {
      console.log('[ContractDoc] First expand detected - loading documents');
      this.isInitialLoad = false;
      this.loadDocuments();
    } else {
      console.log('[ContractDoc] Section expanded - documents already loaded');
    }
  });
}
```

### 2. 動作フロー

1. **ページ読み込み時**
   - ContractDocumentManagerクラスがインスタンス化される
   - `isInitialLoad`フラグが`true`に設定される
   - `setupLazyLoading()`が呼び出される
   - `shown.bs.collapse`イベントリスナーが登録される
   - **ドキュメントは読み込まれない**

2. **初回展開時**
   - ユーザーが「ドキュメントを表示」ボタンをクリック
   - Bootstrap Collapseが`shown.bs.collapse`イベントを発火
   - `isInitialLoad`が`true`のため、`loadDocuments()`が呼び出される
   - `isInitialLoad`が`false`に設定される
   - ドキュメント一覧がAPIから取得され表示される

3. **2回目以降の展開時**
   - ユーザーが再度セクションを展開
   - `shown.bs.collapse`イベントが発火
   - `isInitialLoad`が`false`のため、APIリクエストは発生しない
   - 既に読み込まれたドキュメントが表示される

### 3. フォールバック処理

統一ドキュメントセクションが見つからない場合（例：古いバージョンのビューファイル）、即座にドキュメントを読み込みます：

```javascript
if (!unifiedSection) {
  console.warn('[ContractDoc] Unified section not found, loading documents immediately');
  this.loadDocuments();
  return;
}
```

## パフォーマンス効果

### 改善前
- ページ読み込み時に即座にAPIリクエストが発生
- ユーザーがドキュメントを見ない場合でもリクエストが実行される
- 初期表示が遅くなる可能性

### 改善後
- ページ読み込み時はAPIリクエストなし
- ユーザーが展開ボタンをクリックした時のみリクエスト
- 初期表示が高速化
- 不要なAPIリクエストの削減

### 測定可能な指標
- **初期ロード時間**: APIリクエスト1回分の削減（約100-500ms）
- **ネットワークトラフィック**: 初期表示時のリクエスト数削減
- **サーバー負荷**: 不要なAPIコールの削減

## ユーザー体験

### 初回展開時
1. ユーザーが「ドキュメントを表示」ボタンをクリック
2. セクションが展開される
3. ローディングインジケーターが表示される
4. ドキュメント一覧が読み込まれる
5. ドキュメントが表示される

### 2回目以降の展開時
1. ユーザーが「ドキュメントを表示」ボタンをクリック
2. セクションが展開される
3. **即座にドキュメントが表示される**（APIリクエストなし）

## デバッグ

### コンソールログ

遅延ロードの動作を確認するには、ブラウザのコンソールで以下のログを確認できます：

```
[ContractDoc] Initializing manager for facility: 123
[ContractDoc] Lazy loading enabled - documents will load on first expand
[ContractDoc] First expand detected - loading documents
[ContractDoc] Load documents error: ... (エラー時のみ)
[ContractDoc] Section expanded - documents already loaded (2回目以降)
```

### 動作確認手順

1. ブラウザの開発者ツールを開く
2. Networkタブを開く
3. 契約書タブを表示
4. **初期状態ではAPIリクエストが発生しないことを確認**
5. 「ドキュメントを表示」ボタンをクリック
6. `/facilities/{id}/contract-documents`へのAPIリクエストが発生することを確認
7. セクションを折りたたんで再度展開
8. **2回目はAPIリクエストが発生しないことを確認**

## 技術的な詳細

### Bootstrap Collapseイベント

Bootstrap 5のCollapseコンポーネントは以下のイベントを提供します：

- `show.bs.collapse`: 展開開始時（アニメーション前）
- `shown.bs.collapse`: 展開完了時（アニメーション後）
- `hide.bs.collapse`: 折りたたみ開始時（アニメーション前）
- `hidden.bs.collapse`: 折りたたみ完了時（アニメーション後）

本実装では`shown.bs.collapse`を使用しています。これにより、展開アニメーションが完了してからドキュメントを読み込むため、スムーズなユーザー体験を提供できます。

### 状態管理

`isInitialLoad`フラグを使用して初回ロードを判定します：

- `true`: 初回ロード前（ドキュメント未読み込み）
- `false`: 初回ロード完了（ドキュメント読み込み済み）

このフラグにより、2回目以降の展開時に不要なAPIリクエストを防ぎます。

## 今後の拡張

### キャッシュ無効化

将来的に、ドキュメントの更新を検知してキャッシュを無効化する機能を追加できます：

```javascript
refreshDocuments() {
  this.isInitialLoad = true;
  this.loadDocuments();
}
```

### プリフェッチ

ユーザーがボタンにホバーした時点でプリフェッチする機能も検討できます：

```javascript
setupPrefetch() {
  const toggleBtn = document.getElementById('unified-documents-toggle');
  toggleBtn.addEventListener('mouseenter', () => {
    if (this.isInitialLoad) {
      this.loadDocuments();
    }
  });
}
```

## 関連ファイル

- `resources/js/modules/ContractDocumentManager.js`: メインクラス
- `resources/views/components/contract-document-manager.blade.php`: Bladeコンポーネント
- `resources/views/facilities/contracts/index.blade.php`: 契約書タブビュー
- `app/Http/Controllers/ContractDocumentController.php`: APIコントローラー

## 参考資料

- [Bootstrap 5 Collapse Documentation](https://getbootstrap.com/docs/5.1/components/collapse/)
- [Lazy Loading Best Practices](https://web.dev/lazy-loading/)
- [Performance Optimization Techniques](https://developer.mozilla.org/en-US/docs/Web/Performance)

## 変更履歴

- **2024-12-XX**: 遅延ロード機能を実装（タスク5.4完了）
