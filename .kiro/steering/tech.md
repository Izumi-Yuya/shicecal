# 技術スタックとビルドシステム

## バックエンドフレームワーク
- **Laravel 9.x** - PHP Webアプリケーションフレームワーク
- **PHP 8.2+** - サーバーサイドプログラミング言語
- **MySQL 8.0** - プライマリデータベース（テスト用はSQLite）
- **Redis** - キャッシュ層

## フロントエンド技術
- **Blade Templates** - Laravelのテンプレートエンジン
- **Bootstrap 5.1.3** - カスタムスタイリング付きCSSフレームワーク
- **ES6 Modules** - モダンJavaScriptモジュールシステム
- **Vanilla JavaScript (ES6+)** - モジュラーアーキテクチャによるクライアントサイドスクリプト
- **Font Awesome 6.0.0** - アイコンライブラリ
- **Vite 4.x** - ES6モジュールサポート付きモダンビルドツールと開発サーバー

## 主要依存関係
- **barryvdh/laravel-dompdf** - PDF生成
- **elibyy/tcpdf-laravel** - 高度なPDF機能
- **spatie/laravel-activitylog** - アクティビティログ
- **laravel/sanctum** - API認証

## 開発ツール
- **Laravel Pint** - コードフォーマット
- **PHPUnit** - PHPテストフレームワーク
- **Vitest** - JavaScriptテストフレームワーク
- **Docker** - コンテナ化（オプション）
- **Composer** - PHP依存関係管理
- **npm** - Node.jsパッケージ管理

## 共通コマンド

### 開発セットアップ
```bash
# 初期セットアップ
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# 開発開始
php artisan serve          # バックエンドサーバー（ポート8000）
npm run dev               # HMR付きフロントエンド開発サーバー
```

### データベース操作
```bash
php artisan migrate              # マイグレーション実行
php artisan migrate:fresh --seed # テストデータ付きフレッシュDB
php artisan db:seed             # テストデータのみシード
```

### テスト
```bash
php artisan test                # 全PHPテスト実行
php artisan test --coverage    # カバレッジレポート付き
npm run test                    # JavaScriptテスト実行
npm run test:watch             # JSテストのウォッチモード
```

### ビルドとデプロイメント
```bash
npm run build                   # 本番アセットビルド
php artisan config:cache        # 設定キャッシュ
php artisan route:cache         # ルートキャッシュ
php artisan view:cache          # ビューキャッシュ
php artisan optimize:clear      # 全キャッシュクリア
```

### Docker開発（オプション）
```bash
make setup                      # 初期Dockerセットアップ
make start                      # コンテナ開始
make shell                      # アプリコンテナアクセス
make test                       # コンテナ内テスト実行
make logs                       # コンテナログ表示
```

## ファイル構造規約
- Controllers は `app/Http/Controllers/` でLaravel規約に従う
- ビジネスロジック用のServices は `app/Services/` に配置
- 適切なリレーションシップを持つModels は `app/Models/` に配置
- 認可用のPolicies は `app/Policies/` に配置
- CSS は `resources/css/` で目的別に整理（shared/, pages/ サブディレクトリ）
- JavaScript ES6モジュールは `resources/js/` に配置（modules/, shared/ サブディレクトリ）
- Bladeビューは `resources/views/` で機能別に整理

## フロントエンドアーキテクチャ
- **エントリーポイント**: `resources/js/app.js` - メインES6モジュールエントリーポイント
- **機能モジュール**: `resources/js/modules/` - 機能固有の機能
- **共有モジュール**: `resources/js/shared/` - 再利用可能なユーティリティとコンポーネント
- **モジュールパターン**: 各機能は初期化関数をエクスポート
- **状態管理**: グローバル状態用のApplicationStateクラス
- **後方互換性**: `window.ShiseCal` オブジェクト経由のレガシーAPI

## コード品質
- 一貫したPHPフォーマットにLaravel Pintを使用
- PSR-12コーディング標準に従う
- 包括的なテスト（機能テストと単体テスト）を記述
- PHPで型ヒントと戻り値の型を使用
- 適切なエラーハンドリングとログを実装
- セキュリティのためのLaravelベストプラクティスに従う