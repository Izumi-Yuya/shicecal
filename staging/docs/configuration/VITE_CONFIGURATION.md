# Vite設定ガイド

## 現在の設定状況

### Vite設定ファイル (`vite.config.js`)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info.css',
        // Shared CSS files
        'resources/css/shared/variables.css',
        'resources/css/shared/components.css',
        'resources/css/shared/utilities.css',
        // Page-specific CSS files
        'resources/css/pages/facilities.css',
        'resources/css/pages/notifications.css',
        'resources/css/pages/export.css',
        // JavaScript files
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js',
        // JavaScript modules (for proper bundling)
        'resources/js/modules/facilities.js',
        'resources/js/modules/notifications.js',
        'resources/js/modules/export.js',
        'resources/js/shared/utils.js',
        'resources/js/shared/api.js',
        'resources/js/shared/validation.js',
        'resources/js/shared/components.js',
        'resources/js/shared/sidebar.js'
      ],
      refresh: true,
    }),
  ],
  css: {
    preprocessorOptions: {
      css: {
        charset: false
      }
    },
    postcss: {
      plugins: [
        {
          postcssPlugin: 'internal:charset-removal',
          AtRule: {
            charset: (atRule) => {
              if (atRule.name === 'charset') {
                atRule.remove();
              }
            }
          }
        }
      ]
    }
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      host: 'localhost',
    },
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: [
        'resources/css/app.css',
        'resources/css/auth.css',
        'resources/css/admin.css',
        'resources/css/land-info.css',
        // Shared CSS files
        'resources/css/shared/variables.css',
        'resources/css/shared/components.css',
        'resources/css/shared/utilities.css',
        // Page-specific CSS files
        'resources/css/pages/facilities.css',
        'resources/css/pages/notifications.css',
        'resources/css/pages/export.css',
        // JavaScript files
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/land-info.js',
        // JavaScript modules (for proper bundling)
        'resources/js/modules/facilities.js',
        'resources/js/modules/notifications.js',
        'resources/js/modules/export.js',
        'resources/js/shared/utils.js',
        'resources/js/shared/api.js',
        'resources/js/shared/validation.js',
        'resources/js/shared/components.js',
        'resources/js/shared/sidebar.js'
      ],
      output: {
        // Ensure proper ES6 module chunking
        manualChunks: {
          'shared-utils': ['resources/js/shared/utils.js'],
          'shared-api': ['resources/js/shared/api.js'],
          'shared-validation': ['resources/js/shared/validation.js'],
          'shared-components': ['resources/js/shared/components.js'],
          'shared-sidebar': ['resources/js/shared/sidebar.js'],
          'modules': [
            'resources/js/modules/facilities.js',
            'resources/js/modules/notifications.js',
            'resources/js/modules/export.js'
          ]
        }
      }
    }
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['tests/js/setup.js']
  }
});
```

### 環境変数設定 (`.env`)
```
VITE_DEV_SERVER_URL=http://localhost:5173
```

### レイアウトファイルでの使用

#### メインアプリケーション (`resources/views/layouts/app.blade.php`)
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
@if(auth()->check() && auth()->user()->isAdmin())
    @vite('resources/css/admin.css')
@endif
```

#### 認証画面 (`resources/views/layouts/auth.blade.php`)
```php
@vite('resources/css/auth.css')
```

## 開発環境での使用方法

### 1. 開発サーバーの起動
```bash
# ターミナル1: Vite開発サーバー
npm run dev

# ターミナル2: Laravel開発サーバー
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. 本番ビルド
```bash
npm run build
```

## トラブルシューティング

### 問題1: CSSが読み込まれない
**原因**: Vite開発サーバーが起動していない、またはポートが競合している
**解決方法**: 
1. `npm run dev` でVite開発サーバーを起動
2. ポート競合の場合は `vite.config.js` でポート番号を変更

### 問題2: ホットリロードが動作しない
**原因**: HMR設定の問題
**解決方法**: 
1. `vite.config.js` の `server.hmr` 設定を確認
2. ブラウザのキャッシュをクリア

### 問題3: 本番環境でアセットが見つからない
**原因**: ビルドされたアセットが存在しない
**解決方法**: 
1. `npm run build` を実行
2. `public/build/` ディレクトリの存在を確認

## ファイル構成

### アセットファイル

#### CSS ファイル
- `resources/css/app.css` - メインアプリケーションスタイル
- `resources/css/auth.css` - 認証画面スタイル
- `resources/css/admin.css` - 管理画面スタイル
- `resources/css/land-info.css` - 土地情報機能スタイル

#### 共有CSS
- `resources/css/shared/variables.css` - CSS変数定義
- `resources/css/shared/components.css` - 再利用可能コンポーネント
- `resources/css/shared/utilities.css` - ユーティリティクラス

#### ページ別CSS
- `resources/css/pages/facilities.css` - 施設管理ページ
- `resources/css/pages/notifications.css` - 通知ページ
- `resources/css/pages/export.css` - エクスポートページ

#### JavaScript ファイル
- `resources/js/app.js` - メインアプリケーション（ES6モジュールエントリーポイント）
- `resources/js/admin.js` - 管理画面JavaScript
- `resources/js/land-info.js` - 土地情報機能JavaScript

#### JavaScript モジュール
- `resources/js/modules/facilities.js` - 施設管理機能
- `resources/js/modules/notifications.js` - 通知機能
- `resources/js/modules/export.js` - エクスポート機能

#### 共有JavaScript
- `resources/js/shared/utils.js` - ユーティリティ関数
- `resources/js/shared/api.js` - API通信
- `resources/js/shared/validation.js` - フォームバリデーション
- `resources/js/shared/components.js` - 再利用可能コンポーネント
- `resources/js/shared/sidebar.js` - サイドバー機能

### ビルド出力
- `public/build/assets/` - ビルドされたアセットファイル
- `public/build/manifest.json` - アセットマニフェスト

## 開発ワークフロー

### 日常的な開発
1. `npm run dev` でVite開発サーバーを起動
2. `php artisan serve` でLaravelサーバーを起動
3. ファイルを編集すると自動的にブラウザが更新される

### デプロイ前
1. `npm run build` で本番用アセットをビルド
2. `public/build/` ディレクトリをデプロイに含める

## ES6 モジュール構成

### モジュール分割戦略
Viteの `manualChunks` 設定により、効率的なコード分割を実現：

```javascript
manualChunks: {
  'shared-utils': ['resources/js/shared/utils.js'],
  'shared-api': ['resources/js/shared/api.js'],
  'shared-validation': ['resources/js/shared/validation.js'],
  'shared-components': ['resources/js/shared/components.js'],
  'shared-sidebar': ['resources/js/shared/sidebar.js'],
  'modules': [
    'resources/js/modules/facilities.js',
    'resources/js/modules/notifications.js',
    'resources/js/modules/export.js'
  ]
}
```

### エイリアス設定
```javascript
resolve: {
  alias: {
    '@': path.resolve(__dirname, 'resources/js'),
  },
}
```

これにより、インポート時に `@/shared/utils.js` のような短縮記法が使用可能。

## パフォーマンス最適化

### CSS最適化
- 未使用CSSの削除
- CSS圧縮
- Critical CSSの分離
- CSS変数による一元管理

### JavaScript最適化
- **ES6モジュール分割**: 機能別・共有別のチャンク分割
- **Tree shaking**: 未使用コードの自動削除
- **コード圧縮**: 本番ビルド時の自動minify
- **遅延読み込み**: 必要時のみモジュール初期化

### 画像最適化
- WebP形式の使用
- 遅延読み込み
- レスポンシブ画像

### テスト環境設定
```javascript
test: {
  environment: 'jsdom',
  globals: true,
  setupFiles: ['tests/js/setup.js']
}
```

## JavaScript テスト実行

### テストコマンド
```bash
# 全テスト実行
npm run test

# 監視モード
npm run test:watch

# カバレッジ付き実行
npm run test:coverage
```

### テスト設定
- **環境**: jsdom（ブラウザ環境シミュレーション）
- **グローバル**: describe, it, expect などをグローバルで使用可能
- **セットアップ**: `tests/js/setup.js` で共通設定

## 更新履歴
- 2025年9月9日: ES6モジュール構成への移行、コード分割最適化
- 2025年8月31日: 初期Vite設定実装完了