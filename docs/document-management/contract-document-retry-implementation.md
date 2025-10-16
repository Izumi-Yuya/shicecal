# 契約書ドキュメント管理 - 再試行機能実装サマリー

## 概要

契約書ドキュメント管理システムに、ネットワークエラー時の自動再試行機能を実装しました。この機能により、一時的なネットワーク問題が発生した場合でも、ユーザーが手動で再試行できるようになり、ユーザーエクスペリエンスが向上します。

## 実装内容

### 1. 再試行機能の設定

ContractDocumentManagerクラスのコンストラクタに以下の設定を追加：

```javascript
// 再試行機能の設定
this.maxRetries = 3;              // 最大再試行回数
this.retryCount = 0;              // 現在の再試行回数
this.retryDelay = 1000;           // 初期遅延時間（ミリ秒）
this.lastFailedRequest = null;    // 最後に失敗したリクエスト情報
```

### 2. ネットワークエラー判定

`isNetworkError(error)` メソッドを実装し、以下のエラーをネットワークエラーとして判定：

- **TypeError: Failed to fetch** - ネットワーク接続エラー
- **HTTP 5xx エラー** - サーバーエラー
- **AbortError / timeout** - タイムアウトエラー

```javascript
isNetworkError(error) {
  // TypeError: Failed to fetch はネットワークエラー
  if (error instanceof TypeError && error.message.includes('fetch')) {
    return true;
  }
  
  // HTTP 5xx エラーもネットワーク関連として扱う
  if (error.message && error.message.includes('HTTP error! status: 5')) {
    return true;
  }
  
  // タイムアウトエラー
  if (error.name === 'AbortError' || error.message.includes('timeout')) {
    return true;
  }
  
  return false;
}
```

### 3. ネットワークエラー表示

`showNetworkError(message)` メソッドを実装し、再試行ボタン付きのエラーメッセージを表示：

- エラーメッセージの表示
- 再試行ボタンの動的生成
- 再試行回数の表示（残り回数）
- 最大再試行回数到達時のボタン無効化

```javascript
showNetworkError(message) {
  this.hideLoading();
  this.elements.errorText.textContent = message;
  this.elements.errorMessage.classList.remove('d-none');
  
  // 再試行ボタンを表示
  let retryBtn = this.elements.errorMessage.querySelector('.retry-btn');
  if (!retryBtn) {
    retryBtn = document.createElement('button');
    retryBtn.className = 'btn btn-primary btn-sm retry-btn mt-2';
    retryBtn.innerHTML = '<i class="fas fa-redo me-1"></i>再試行';
    retryBtn.addEventListener('click', () => this.handleRetry());
    this.elements.errorMessage.appendChild(retryBtn);
  }
  
  // 再試行回数に応じてボタンの表示を制御
  if (this.retryCount >= this.maxRetries) {
    retryBtn.disabled = true;
    retryBtn.innerHTML = '<i class="fas fa-times me-1"></i>再試行回数の上限に達しました';
    this.elements.errorText.textContent = message + ' ページを再読み込みしてください。';
  } else {
    retryBtn.disabled = false;
    retryBtn.style.display = 'inline-block';
    
    if (this.retryCount > 0) {
      const remainingRetries = this.maxRetries - this.retryCount;
      retryBtn.innerHTML = `<i class="fas fa-redo me-1"></i>再試行 (残り${remainingRetries}回)`;
    }
  }
}
```

### 4. 指数バックオフ実装

`handleRetry()` メソッドで指数バックオフを実装：

- **1回目**: 1秒待機（1000ms × 2^0）
- **2回目**: 2秒待機（1000ms × 2^1）
- **3回目**: 4秒待機（1000ms × 2^2）

```javascript
async handleRetry() {
  if (this.retryCount >= this.maxRetries) {
    console.warn('[ContractDoc] Max retry attempts reached');
    return;
  }
  
  if (!this.lastFailedRequest) {
    console.warn('[ContractDoc] No failed request to retry');
    return;
  }
  
  this.retryCount++;
  
  // 指数バックオフ: 1秒 → 2秒 → 4秒
  const delay = this.retryDelay * Math.pow(2, this.retryCount - 1);
  
  console.log(`[ContractDoc] Retrying request (attempt ${this.retryCount}/${this.maxRetries}) after ${delay}ms`);
  
  // 遅延後に再試行
  await this.sleep(delay);
  
  // 最後に失敗したリクエストを再実行
  const { action, params } = this.lastFailedRequest;
  
  switch (action) {
    case 'loadDocuments':
      await this.loadDocuments(params.folderId);
      break;
    case 'search':
      await this.handleSearch();
      break;
    default:
      console.warn(`[ContractDoc] Unknown action: ${action}`);
  }
}
```

### 5. 対応操作

以下の操作でネットワークエラー時に再試行機能が利用可能：

- **ドキュメント一覧読み込み** (`loadDocuments`)
- **検索** (`handleSearch`)

以下の操作は再試行対象外（ユーザーが手動で再実行）：

- フォルダ作成
- ファイルアップロード
- ファイル削除
- フォルダ削除

### 6. 再試行状態のリセット

成功時に再試行カウントをリセット：

```javascript
resetRetryState() {
  this.retryCount = 0;
  this.lastFailedRequest = null;
}
```

## ユーザーエクスペリエンス

### 正常時

1. ドキュメント読み込みまたは検索を実行
2. 成功時は通常通り結果を表示
3. 再試行カウントは自動的にリセット

### ネットワークエラー時

1. ネットワークエラーが発生
2. エラーメッセージと再試行ボタンを表示
3. ユーザーが再試行ボタンをクリック
4. 指数バックオフで待機後、自動的に再試行
5. 成功するか、最大再試行回数（3回）に達するまで繰り返し

### 最大再試行回数到達時

1. 再試行ボタンが無効化
2. 「再試行回数の上限に達しました」と表示
3. 「ページを再読み込みしてください」とメッセージ表示

## 技術的な詳細

### エラーハンドリングフロー

```
ネットワークリクエスト
    ↓
エラー発生
    ↓
isNetworkError() でエラー種別判定
    ↓
ネットワークエラーの場合
    ↓
lastFailedRequest に情報保存
    ↓
showNetworkError() で再試行ボタン表示
    ↓
ユーザーが再試行ボタンクリック
    ↓
handleRetry() で指数バックオフ実行
    ↓
元のリクエストを再実行
    ↓
成功 → resetRetryState()
失敗 → 再度エラーハンドリング
```

### 指数バックオフの計算

```javascript
delay = retryDelay * Math.pow(2, retryCount - 1)

// 例:
// retryCount = 1: 1000 * 2^0 = 1000ms (1秒)
// retryCount = 2: 1000 * 2^1 = 2000ms (2秒)
// retryCount = 3: 1000 * 2^2 = 4000ms (4秒)
```

## テスト方法

### 手動テスト

1. **ネットワーク切断テスト**
   - ブラウザの開発者ツールでネットワークをオフラインに設定
   - ドキュメント一覧を読み込む
   - 再試行ボタンが表示されることを確認
   - ネットワークをオンラインに戻す
   - 再試行ボタンをクリック
   - ドキュメントが正常に読み込まれることを確認

2. **サーバーエラーテスト**
   - サーバーを停止
   - ドキュメント一覧を読み込む
   - 再試行ボタンが表示されることを確認
   - サーバーを起動
   - 再試行ボタンをクリック
   - ドキュメントが正常に読み込まれることを確認

3. **最大再試行回数テスト**
   - ネットワークをオフラインのまま
   - 再試行ボタンを3回クリック
   - ボタンが無効化されることを確認
   - 「再試行回数の上限に達しました」メッセージを確認

## 今後の改善案

1. **プログレスインジケーター**
   - 再試行中の待機時間を視覚的に表示
   - カウントダウンタイマーの追加

2. **自動再試行オプション**
   - ユーザーの操作なしで自動的に再試行
   - 設定で有効/無効を切り替え可能

3. **再試行履歴**
   - 再試行の履歴をログに記録
   - デバッグ情報として活用

4. **カスタマイズ可能な設定**
   - 最大再試行回数の変更
   - 初期遅延時間の変更
   - 指数バックオフの係数変更

## まとめ

契約書ドキュメント管理システムに再試行機能を実装することで、ネットワークの一時的な問題に対する耐性が向上しました。指数バックオフにより、サーバーへの負荷を抑えながら、ユーザーに再試行の機会を提供できます。

この実装は、要件8.4「ネットワークエラーが発生した場合、再試行オプションを提供する」を満たしており、ユーザーエクスペリエンスの向上に貢献します。
