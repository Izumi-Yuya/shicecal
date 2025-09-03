# Shise-Cal テスト環境セットアップガイド

## 📋 概要

このドキュメントは、Shise-Cal（施設管理システム）のテスト環境を構築・デプロイするための包括的なガイドです。

## 🎯 テスト環境の目的

- 本番環境デプロイ前の機能検証
- 自動テストの実行環境
- 開発チーム向けの統合テスト環境
- パフォーマンステストとセキュリティテスト

## 🚀 クイックスタート

### 1. 自動デプロイ（推奨）

```bash
# テスト環境の自動デプロイ
./deploy-test.sh

# デプロイ検証の実行
./test-deployment-verification.sh
```

### 2. Docker環境での実行

```bash
# Docker Composeでテスト環境を起動
docker-compose -f docker-compose.test.yml up -d

# テストの実行
docker-compose -f docker-compose.test.yml run test-runner
```

## 🔧 手動セットアップ

### 前提条件

- PHP 8.1以上
- Composer 2.x
- MySQL 8.0以上
- Redis 6.0以上（オプション）
- Node.js 16以上
- Git

### ステップ1: プロジェクトの準備

```bash
# プロジェクトディレクトリに移動
cd /path/to/shisecal

# テスト環境設定ファイルのコピー
cp .env.testing .env

# 依存関係のインストール
composer install
npm install

# フロントエンドアセットのビルド
npm run build
```

### ステップ2: データベースの設定

```bash
# MySQLにログイン
mysql -u root -p

# テストデータベースの作成
CREATE DATABASE shisecal_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shisecal_test_user'@'localhost' IDENTIFIED BY 'test_secure_password';
GRANT ALL PRIVILEGES ON shisecal_testing.* TO 'shisecal_test_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### ステップ3: アプリケーションの初期化

```bash
# アプリケーションキーの生成
php artisan key:generate

# データベースマイグレーションとシーディング
php artisan migrate:fresh --seed

# ストレージリンクの作成
php artisan storage:link

# キャッシュの最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🧪 テスト実行

### 基本テスト

```bash
# 全テストの実行
php artisan test

# ユニットテストのみ
php artisan test --testsuite=Unit

# フィーチャーテストのみ
php artisan test --testsuite=Feature

# カバレッジレポート付き
php artisan test --coverage
```

### 特定機能のテスト

```bash
# 認証機能のテスト
php artisan test --filter=AuthenticationTest

# 施設管理機能のテスト
php artisan test --filter=FacilityTest

# PDF出力機能のテスト
php artisan test --filter=PdfExportTest
```

## 🌐 Webサーバー設定

### 開発サーバー（簡単）

```bash
# Laravel開発サーバーの起動
php artisan serve --host=0.0.0.0 --port=8000

# アクセス: http://localhost:8000
```

### Nginx設定（本格的）

```bash
# Nginx設定ファイルのコピー
sudo cp nginx-test.conf /etc/nginx/sites-available/shisecal-test
sudo ln -s /etc/nginx/sites-available/shisecal-test /etc/nginx/sites-enabled/

# Nginxの再起動
sudo systemctl restart nginx
```

## 📊 テスト環境の監視

### ヘルスチェック

```bash
# アプリケーションの状態確認
curl http://localhost:8000/health

# データベース接続確認
php artisan migrate:status

# キューワーカーの状態確認
php artisan queue:work --once
```

### ログの確認

```bash
# アプリケーションログ
tail -f storage/logs/laravel.log

# テスト実行ログ
tail -f storage/logs/test-deployment-report-*.log

# Webサーバーログ（Nginx使用時）
sudo tail -f /var/log/nginx/shisecal_test_access.log
sudo tail -f /var/log/nginx/shisecal_test_error.log
```

## 🔍 機能テスト項目

### 1. 認証システム

- [ ] ログイン機能
- [ ] ログアウト機能
- [ ] ロール別アクセス制御
- [ ] IP制限機能

### 2. 施設管理

- [ ] 施設一覧表示
- [ ] 施設詳細表示
- [ ] 施設登録・編集
- [ ] 施設削除機能
- [ ] 検索・絞り込み機能

### 3. ファイル管理

- [ ] PDFアップロード
- [ ] ファイルダウンロード
- [ ] ファイルサイズ制限
- [ ] セキュリティチェック

### 4. 出力機能

- [ ] PDF帳票出力
- [ ] セキュアPDF生成
- [ ] CSV出力
- [ ] 一括出力機能

### 5. コメント・通知

- [ ] コメント投稿
- [ ] 通知システム
- [ ] ステータス管理
- [ ] マイページ機能

### 6. 管理機能

- [ ] ユーザー管理
- [ ] システム設定
- [ ] ログ管理
- [ ] 年次確認機能

## 🐛 トラブルシューティング

### よくある問題

#### 1. データベース接続エラー

```bash
# 接続設定の確認
grep DB_ .env

# MySQLサービスの状態確認
sudo systemctl status mysql

# 接続テスト
php artisan migrate:status
```

#### 2. ファイル権限エラー

```bash
# 権限の修正
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 3. テスト失敗

```bash
# キャッシュのクリア
php artisan optimize:clear

# テストデータベースのリセット
php artisan migrate:fresh --seed --env=testing

# 詳細なテスト出力
php artisan test --verbose
```

#### 4. フロントエンドアセットの問題

```bash
# Node.jsの依存関係を再インストール
rm -rf node_modules package-lock.json
npm install

# アセットの再ビルド
npm run build
```

### パフォーマンス問題

#### 1. レスポンス速度の改善

```bash
# OPcacheの有効化確認
php -m | grep -i opcache

# データベースクエリの最適化
php artisan telescope:install  # 開発環境のみ
```

#### 2. メモリ使用量の確認

```bash
# PHPメモリ制限の確認
php -i | grep memory_limit

# プロセス監視
top -p $(pgrep -f "php artisan")
```

## 📈 パフォーマンステスト

### 負荷テスト

```bash
# Apache Benchを使用した基本的な負荷テスト
ab -n 1000 -c 10 http://localhost:8000/

# より詳細な負荷テスト（wrk使用）
wrk -t12 -c400 -d30s http://localhost:8000/
```

### データベースパフォーマンス

```bash
# スロークエリログの有効化
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
mysql -u root -p -e "SET GLOBAL long_query_time = 1;"

# クエリ分析
php artisan telescope:install
```

## 🔒 セキュリティテスト

### 基本的なセキュリティチェック

```bash
# 依存関係の脆弱性チェック
composer audit

# ファイル権限の確認
find . -type f -perm 777 -ls

# 設定ファイルの露出チェック
curl -I http://localhost:8000/.env
curl -I http://localhost:8000/composer.json
```

## 📝 テストレポート

### 自動レポート生成

```bash
# デプロイ検証レポートの生成
./test-deployment-verification.sh

# テストカバレッジレポートの生成
php artisan test --coverage --coverage-html=storage/app/coverage
```

### 手動チェックリスト

- [ ] 全自動テストが通過
- [ ] 主要機能の手動テスト完了
- [ ] パフォーマンステスト実行
- [ ] セキュリティチェック完了
- [ ] ログ出力の確認
- [ ] エラーハンドリングの確認

## 🚀 本番環境への移行

テスト環境での検証が完了したら、以下の手順で本番環境にデプロイします：

1. テスト結果の確認とドキュメント化
2. 本番環境設定ファイルの準備
3. 本番環境でのデプロイ実行
4. 本番環境での動作確認

## 📞 サポート

問題が発生した場合は、以下の情報を含めて開発チームに連絡してください：

- エラーメッセージの詳細
- 実行していた操作
- 環境情報（OS、PHPバージョンなど）
- ログファイルの関連部分

---

**注意**: このテスト環境は開発・検証目的のみに使用してください。本番データは使用しないでください。