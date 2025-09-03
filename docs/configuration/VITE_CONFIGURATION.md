# Vite設定ガイド

## 現在の設定状況

### Vite設定ファイル (`vite.config.js`)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/auth.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/admin.js'
            ],
            refresh: true,
        }),
    ],
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
                'resources/js/app.js',
                'resources/js/admin.js'
            ]
        }
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
- `resources/css/app.css` - メインアプリケーションスタイル
- `resources/css/auth.css` - 認証画面スタイル
- `resources/css/admin.css` - 管理画面スタイル
- `resources/js/app.js` - メインアプリケーションJavaScript
- `resources/js/admin.js` - 管理画面JavaScript

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

## パフォーマンス最適化

### CSS最適化
- 未使用CSSの削除
- CSS圧縮
- Critical CSSの分離

### JavaScript最適化
- コード分割
- Tree shaking
- 圧縮とminify

### 画像最適化
- WebP形式の使用
- 遅延読み込み
- レスポンシブ画像

## 実装完了日
2025年8月31日