# Shise-Cal Development Guide

## 概要

Shise-Cal（施設カルテシステム）のローカル開発環境セットアップと開発ガイドです。

## 必要な環境

### システム要件

- **Docker**: 20.10.0 以降
- **Docker Compose**: 2.0.0 以降
- **Git**: 2.30.0 以降
- **メモリ**: 最低 4GB RAM（推奨 8GB）
- **ディスク容量**: 最低 10GB の空き容量

### 対応OS

- macOS 10.15 以降
- Ubuntu 20.04 以降
- Windows 10/11 (WSL2 推奨)

## クイックスタート

### 1. リポジトリのクローン

```bash
git clone <repository-url>
cd shicecal
```

### 2. 開発環境のセットアップ

```bash
# 自動セットアップスクリプトを実行
./scripts/dev-setup.sh
```

このスクリプトは以下を自動実行します：
- Docker コンテナのビルドと起動
- 環境設定ファイルの作成
- PHP 依存関係のインストール
- データベースマイグレーション
- テストデータの投入
- フロントエンド資産のビルド

### 3. アクセス確認

セットアップ完了後、以下のURLでアクセス可能です：

- **アプリケーション**: http://localhost:8080
- **MailHog (メールテスト)**: http://localhost:8025
- **MinIO Console (ファイルストレージ)**: http://localhost:9001

## 開発環境の詳細

### Docker サービス構成

| サービス | ポート | 説明 |
|---------|--------|------|
| app | 8000 | Laravel アプリケーション |
| nginx | 8080 | Webサーバー |
| db | 3307 | MySQL 8.0 データベース |
| redis | 6380 | Redis キャッシュ・セッション |
| minio | 9000, 9001 | S3互換ファイルストレージ |
| mailhog | 1025, 8025 | メールテスト環境 |
| node | 5173 | Node.js フロントエンド開発 |

### 環境設定

開発環境の設定は `.env.development` ファイルで管理されています。
初回セットアップ時に `.env` ファイルとしてコピーされます。

#### 主要な設定項目

```env
# アプリケーション
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# データベース
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=shisecal_development

# ファイルストレージ (MinIO)
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_BUCKET=shisecal-dev

# メール (MailHog)
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

## 開発コマンド

便利な開発コマンドを `scripts/dev-commands.sh` で提供しています：

### 基本操作

```bash
# 開発環境の開始
./scripts/dev-commands.sh start

# 開発環境の停止
./scripts/dev-commands.sh stop

# ログの確認
./scripts/dev-commands.sh logs

# アプリケーションコンテナにアクセス
./scripts/dev-commands.sh shell
```

### 開発作業

```bash
# テストの実行
./scripts/dev-commands.sh test

# カバレッジ付きテスト
./scripts/dev-commands.sh test --coverage

# データベースマイグレーション
./scripts/dev-commands.sh migrate

# データベースの初期化とシード
./scripts/dev-commands.sh fresh

# Artisan コマンドの実行
./scripts/dev-commands.sh artisan make:controller TestController
```

### フロントエンド開発

```bash
# 資産のビルド
./scripts/dev-commands.sh build

# 開発サーバーの起動（ホットリロード）
./scripts/dev-commands.sh dev

# npm コマンドの実行
./scripts/dev-commands.sh npm install

# コード品質チェック
./scripts/dev-commands.sh npm run lint        # 全てのリンティング
./scripts/dev-commands.sh npm run lint:js     # JavaScript ESLint
./scripts/dev-commands.sh npm run lint:blade  # Blade テンプレート検証
./scripts/dev-commands.sh npm run lint:html   # HTML 構文チェック

# 自動修正
./scripts/dev-commands.sh npm run lint:js:fix  # JavaScript 自動修正

# 統合品質チェック
./scripts/dev-commands.sh npm run quality     # lint + test + build
./scripts/dev-commands.sh npm run ci          # CI パイプライン実行
```

## データベース

### 接続情報

- **ホスト**: localhost
- **ポート**: 3307
- **データベース**: shisecal_development
- **ユーザー**: shisecal_dev
- **パスワード**: dev_password

### テストデータ

開発環境には以下のテストユーザーが作成されます：

| ロール | メールアドレス | パスワード |
|--------|---------------|-----------|
| admin | admin@shisecal.local | password |
| editor | editor@shisecal.local | password |
| approver | approver@shisecal.local | password |
| viewer | viewer@shisecal.local | password |

## ファイルストレージ

### MinIO 設定

開発環境では AWS S3 の代わりに MinIO を使用します：

- **コンソール**: http://localhost:9001
- **ユーザー**: minioadmin
- **パスワード**: minioadmin

### ファイルアップロードテスト

1. MinIO コンソールにアクセス
2. `shisecal-dev` バケットを確認
3. アプリケーションからファイルをアップロード
4. MinIO でファイルが保存されることを確認

## メールテスト

### MailHog 使用方法

1. http://localhost:8025 にアクセス
2. アプリケーションからメール送信機能をテスト
3. MailHog でメールが受信されることを確認

## デバッグ

### Xdebug 設定

開発環境には Xdebug が設定済みです：

#### VS Code 設定例

`.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

### ログの確認

```bash
# アプリケーションログ
./scripts/dev-commands.sh logs app

# データベースログ
./scripts/dev-commands.sh logs db

# 全サービスのログ
./scripts/dev-commands.sh logs
```

## テスト

### テストの実行

```bash
# PHP テストの実行
./scripts/dev-commands.sh test

# 特定のテストスイート
./scripts/dev-commands.sh test --testsuite=Feature

# カバレッジレポート
./scripts/dev-commands.sh test --coverage

# JavaScript テストの実行
./scripts/dev-commands.sh npm run test        # 一回実行
./scripts/dev-commands.sh npm run test:watch  # ウォッチモード

# 統合テスト（PHP + JavaScript）
./scripts/dev-commands.sh npm run quality
```

### テストデータベース

テスト実行時は自動的に専用のテストデータベースが使用されます。

## トラブルシューティング

### よくある問題

#### 1. ポートが既に使用されている

```bash
# 使用中のポートを確認
lsof -i :8080
lsof -i :3307

# 該当プロセスを停止してから再実行
```

#### 2. パーミッションエラー

```bash
# ストレージディレクトリの権限修正
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### 3. Docker コンテナが起動しない

```bash
# コンテナとボリュームの完全削除
./scripts/dev-commands.sh clean

# 再セットアップ
./scripts/dev-setup.sh
```

#### 4. データベース接続エラー

```bash
# データベースコンテナの状態確認
./scripts/dev-commands.sh status

# データベースログの確認
./scripts/dev-commands.sh logs db

# データベースコンテナの再起動
docker-compose -f docker-compose.dev.yml restart db
```

### ログファイルの場所

- **Laravel ログ**: `storage/logs/laravel.log`
- **Nginx ログ**: Docker コンテナ内の `/var/log/nginx/`
- **MySQL ログ**: Docker ボリューム内

## 開発ワークフロー

### 1. 機能開発

```bash
# 新しい機能ブランチを作成
git checkout -b feature/new-feature

# 開発環境で作業
./scripts/dev-commands.sh start

# 開発中の継続的チェック
./scripts/dev-commands.sh npm run test:watch  # テスト監視
./scripts/dev-commands.sh dev                 # 開発サーバー

# コード品質チェック
./scripts/dev-commands.sh npm run lint        # リンティング
./scripts/dev-commands.sh npm run quality     # 統合品質チェック

# コミット前の最終チェック
./scripts/dev-commands.sh npm run ci          # CI パイプライン

# コミットとプッシュ
git add .
git commit -m "Add new feature"
git push origin feature/new-feature
```

### 2. データベース変更

```bash
# マイグレーションファイルの作成
./scripts/dev-commands.sh artisan make:migration create_new_table

# マイグレーションの実行
./scripts/dev-commands.sh migrate

# シーダーの作成（必要に応じて）
./scripts/dev-commands.sh artisan make:seeder NewTableSeeder
```

### 3. フロントエンド開発

```bash
# 開発サーバーの起動
./scripts/dev-commands.sh dev

# 別ターミナルでファイル監視
./scripts/dev-commands.sh logs node
```

## パフォーマンス最適化

### 開発環境の高速化

1. **Docker ボリューム**: vendor と node_modules は名前付きボリュームを使用
2. **ファイル同期**: 必要最小限のファイルのみマウント
3. **キャッシュ**: Redis を使用したキャッシュ設定

### メモリ使用量の最適化

```bash
# 不要なコンテナの停止
./scripts/dev-commands.sh stop

# システムリソースの確認
docker stats
```

## セキュリティ

### 開発環境のセキュリティ設定

- IP制限は開発環境では無効化
- HTTPS は本番環境のみ
- デバッグ情報の表示は開発環境のみ

### 機密情報の管理

- `.env` ファイルは Git にコミットしない
- 開発用の認証情報のみ使用
- 本番環境の設定とは完全に分離

## 次のステップ

開発環境のセットアップが完了したら：

1. [API ドキュメント](docs/api.md) を確認
2. [コーディング規約](docs/coding-standards.md) を確認
3. [テスト戦略](docs/testing.md) を確認
4. 実際の機能開発を開始

## サポート

問題が発生した場合：

1. このドキュメントのトラブルシューティングを確認
2. GitHub Issues で既存の問題を検索
3. 新しい Issue を作成して詳細を報告