# フロントエンド構造ガイド

## 概要

このドキュメントは、Shise-Calのフロントエンド構造を説明します。

## ファイル構成

### JavaScript

```
resources/js/
├── app.js                    ← メインエントリーポイント（ここから開始）
├── app-unified.js            ← コアアプリケーション（ShiseCalAppクラス）
│
├── modules/                  ← 機能別モジュール
│   ├── DocumentManager.js
│   ├── LifelineDocumentManager.js
│   ├── FacilityManager.js
│   ├── CsvDownloadManager.js
│   └── ...
│
└── shared/                   ← 共有ユーティリティ
    ├── ApiClient.js
    ├── AppUtils.js
    └── ...
```

### CSS

```
resources/css/
├── app.css                           ← メインスタイル
├── app-unified.css                   ← 統合スタイル
├── lifeline-document-management.css  ← ライフライン設備ドキュメント
│
├── shared/                           ← 共有スタイル
│   ├── variables.css
│   └── ...
│
└── pages/                            ← ページ固有スタイル
    └── ...
```

## 主要ファイルの説明

### 1. app.js - メインエントリーポイント

**目的**: アプリケーション全体の起点

**内容**:
- `app-unified.js`をインポート
- `ExtendedApplication`クラスで機能を拡張
- レガシーAPI（`window.ShiseCal`）を提供

**使用例**:
```javascript
// app.jsは自動的に読み込まれます
// 追加機能が必要な場合のみ編集してください
```

### 2. app-unified.js - コアアプリケーション

**目的**: 統合されたアプリケーションロジック

**主要クラス**:
- `ShiseCalApp` - メインアプリケーション
- `AppUtils` - ユーティリティ関数
- `ApiClient` - API通信

**使用例**:
```javascript
// グローバルインスタンスにアクセス
window.shiseCalApp.initializeLifelineDocumentManagers();
```

### 3. modules/ - 機能別モジュール

各モジュールは独立した機能を提供します。

**例: LifelineDocumentManager.js**
```javascript
import LifelineDocumentManager from './modules/LifelineDocumentManager.js';

const manager = new LifelineDocumentManager(facilityId, 'electrical');
manager.loadDocuments();
```

## 開発ガイド

### 新機能の追加

1. **新しいモジュールを作成**
   ```bash
   touch resources/js/modules/MyNewFeature.js
   ```

2. **モジュールを実装**
   ```javascript
   // resources/js/modules/MyNewFeature.js
   export default class MyNewFeature {
     constructor(options) {
       this.options = options;
     }
     
     init() {
       // 初期化処理
     }
   }
   ```

3. **app-unified.jsで初期化**
   ```javascript
   // app-unified.js
   import MyNewFeature from './modules/MyNewFeature.js';
   
   initializeMyNewFeature() {
     const feature = new MyNewFeature(options);
     this.modules.myNewFeature = feature;
     feature.init();
   }
   ```

### スタイルの追加

1. **新しいCSSファイルを作成**
   ```bash
   touch resources/css/my-feature.css
   ```

2. **vite.config.jsに追加**
   ```javascript
   input: [
     'resources/css/my-feature.css',
     // ...
   ]
   ```

3. **Bladeテンプレートで読み込み**
   ```blade
   @vite(['resources/css/my-feature.css'])
   ```

## ビルドとデプロイ

### 開発環境

```bash
# 開発サーバー起動
npm run dev

# ファイル監視
npm run watch
```

### 本番環境

```bash
# 本番ビルド
npm run build

# アセット最適化
npm run optimize
```

## デバッグ

### ブラウザコンソール

```javascript
// アプリケーション状態を確認
console.log(window.shiseCalApp);

// モジュール一覧
console.log(window.shiseCalApp.modules);

// 特定のモジュール
console.log(window.shiseCalApp.modules.lifelineDocumentManager_electrical);
```

### よくある問題

**問題**: JavaScriptが動作しない
```bash
# ビルドを確認
npm run build

# ブラウザキャッシュをクリア
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```

**問題**: スタイルが適用されない
```bash
# CSSをリビルド
npm run build

# public/build/ディレクトリを確認
ls -la public/build/
```

## パフォーマンス最適化

### コード分割

Viteは自動的にコード分割を行いますが、手動で最適化も可能です。

```javascript
// 動的インポート
const module = await import('./modules/HeavyFeature.js');
```

### 遅延読み込み

```javascript
// 必要になるまで読み込まない
document.getElementById('trigger').addEventListener('click', async () => {
  const { default: Feature } = await import('./modules/Feature.js');
  new Feature().init();
});
```

## テスト

### 単体テスト

```bash
# JavaScriptテスト実行
npm run test

# カバレッジレポート
npm run test:coverage
```

### E2Eテスト

```bash
# Cypressテスト実行
npm run cypress:open
```

## 参考リンク

- [JavaScript アーキテクチャ](./javascript-architecture.md)
- [技術スタック](../.kiro/steering/tech.md)
- [プロジェクト構造](../.kiro/steering/structure.md)
