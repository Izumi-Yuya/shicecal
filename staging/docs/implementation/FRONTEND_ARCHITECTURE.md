# フロントエンド アーキテクチャ

## 概要

Shise-Cal のフロントエンドは、ES6 モジュールベースの現代的なJavaScript アーキテクチャを採用しています。機能別にモジュール化された構成により、保守性と拡張性を向上させています。

## アーキテクチャ構成

### エントリーポイント

**`resources/js/app.js`** - メインアプリケーションエントリーポイント
- ES6 モジュールの統合管理
- アプリケーション状態管理
- 機能モジュールの初期化
- レガシーAPI の提供（後方互換性）

### モジュール構成

```
resources/js/
├── app.js                    # メインエントリーポイント
├── admin.js                  # 管理者機能
├── land-info.js             # 土地情報機能
├── modules/                 # 機能別モジュール
│   ├── facilities.js        # 施設管理機能
│   ├── notifications.js     # 通知機能
│   └── export.js           # エクスポート機能
└── shared/                  # 共有モジュール
    ├── utils.js            # ユーティリティ関数
    ├── api.js              # API通信
    ├── validation.js       # フォームバリデーション
    ├── components.js       # 再利用可能コンポーネント
    └── sidebar.js          # サイドバー機能
```

## 主要コンポーネント

### Application クラス

アプリケーションのライフサイクルを管理する中核クラス：

```javascript
class Application {
  constructor() {
    this.initialized = false;
  }

  async init() {
    // コンポーネント初期化
    this.initializeComponents();
    
    // 機能モジュール初期化
    await this.initializeModules();
    
    // グローバルイベントハンドラー設定
    this.setupGlobalEventHandlers();
    
    // UI拡張機能設定
    this.setupUIEnhancements();
  }
}
```

### ApplicationState クラス

アプリケーション全体の状態管理：

```javascript
class ApplicationState {
  constructor() {
    this.modules = {};      // 機能モジュール
    this.components = {};   // UIコンポーネント
  }

  setModule(name, instance) { /* ... */ }
  getModule(name) { /* ... */ }
}
```

## 機能モジュール

### 施設管理モジュール (`modules/facilities.js`)

- 施設情報の表示・編集
- 土地情報管理
- ファイルアップロード・ダウンロード
- コメント機能

### 通知モジュール (`modules/notifications.js`)

- 通知一覧表示
- 通知の既読・未読管理
- リアルタイム通知更新

### エクスポートモジュール (`modules/export.js`)

- PDF・CSV エクスポート
- エクスポート設定管理
- ダウンロード進捗表示

## 共有モジュール

### ユーティリティ (`shared/utils.js`)

```javascript
export {
  formatCurrency,    // 通貨フォーマット
  formatArea,        // 面積フォーマット
  formatDate,        // 日付フォーマット
  debounce,          // デバウンス処理
  showToast,         // トースト通知
  confirmDialog,     // 確認ダイアログ
  showLoading,       // ローディング表示
  hideLoading        // ローディング非表示
};
```

### API通信 (`shared/api.js`)

```javascript
export {
  get,           // GET リクエスト
  post,          // POST リクエスト
  put,           // PUT リクエスト
  del,           // DELETE リクエスト
  downloadFile   // ファイルダウンロード
};
```

### バリデーション (`shared/validation.js`)

```javascript
export {
  validateForm,        // フォームバリデーション
  displayFormErrors,   // エラー表示
  clearFormErrors      // エラークリア
};
```

### コンポーネント (`shared/components.js`)

```javascript
export {
  FormValidator,         // フォームバリデーター
  SearchComponent,       // 検索コンポーネント
  TableComponent,        // テーブルコンポーネント
  ModalComponent,        // モーダルコンポーネント
  ServiceCardsComponent  // サービスカードコンポーネント
};
```

## 後方互換性

既存コードとの互換性を保つため、グローバル `window.ShiseCal` オブジェクトを提供：

```javascript
window.ShiseCal = {
  config: AppConfig,
  utils: { /* ユーティリティ関数 */ },
  api: { /* API関数 */ },
  validation: { /* バリデーション関数 */ },
  components: { /* コンポーネント */ },
  modules: { /* 機能モジュール */ }
};
```

## ビルド設定

Vite を使用したモジュールバンドリング：

```javascript
// vite.config.js
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'shared-utils': ['resources/js/shared/utils.js'],
          'shared-api': ['resources/js/shared/api.js'],
          'modules': [
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js'
          ]
        }
      }
    }
  }
});
```

## 初期化フロー

1. **DOM Content Loaded** イベント発火
2. **Application.init()** 実行
3. **コンポーネント初期化** - 共有UIコンポーネント
4. **モジュール初期化** - ページコンテキストに基づく機能モジュール
5. **イベントハンドラー設定** - グローバルイベント処理
6. **UI拡張機能設定** - Bootstrap コンポーネント、アニメーション

## 開発ガイドライン

### 新しい機能モジュールの追加

1. `resources/js/modules/` に新しいファイルを作成
2. `initializeXxxManager()` 関数をエクスポート
3. `app.js` の `initializeModules()` メソッドに追加
4. `vite.config.js` の入力ファイルリストに追加

### 共有ユーティリティの追加

1. 適切な共有モジュールファイルに関数を追加
2. `app.js` でre-export
3. 必要に応じてレガシーAPIに追加

### コンポーネントの作成

1. `shared/components.js` にクラスベースコンポーネントを追加
2. 再利用可能な設計を心がける
3. 適切なイベント処理とクリーンアップを実装

## パフォーマンス最適化

- **コード分割**: 機能別チャンク分割
- **遅延読み込み**: 必要時のみモジュール初期化
- **キャッシュ活用**: ビルド時のハッシュベースキャッシュ
- **最小化**: 本番ビルド時の自動最小化

## テスト戦略

- **単体テスト**: 各モジュールの個別テスト
- **統合テスト**: モジュール間の連携テスト
- **E2Eテスト**: ブラウザベースの機能テスト

```bash
# JavaScript テスト実行
npm run test

# テスト監視モード
npm run test:watch
```