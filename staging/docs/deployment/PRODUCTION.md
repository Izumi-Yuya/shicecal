# Shise-Cal 本番環境デプロイメントガイド

## 📋 概要

このドキュメントは、Shise-Cal（施設管理システム）を本番環境にデプロイするための包括的なガイドです。

## 🚀 デプロイメント手順

### 1. 前提条件

- PHP 8.1以上
- Composer 2.x
- MySQL 8.0以上
- Redis 6.0以上
- Nginx 1.18以上
- Node.js 16以上（フロントエンドビルド用）

### 2. サーバー準備

```bash
# システムパッケージの更新
sudo apt update && sudo apt upgrade -y

# 必要なパッケージのインストール
sudo apt install -y nginx mysql-server redis-server php8.1-fpm php8.1-mysql php8.1-redis php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath supervisor
```

### 3. アプリケーションのデプロイ

```bash
# プロジェクトのクローン
git clone https://github.com/your-repo/shisecal.git /var/www/shisecal
cd /var/www/shisecal

# 本番環境設定ファイルのコピー
cp .env.production .env

# 環境設定の編集
nano .env

# デプロイスクリプトの実行
chmod +x deploy.sh
./deploy.sh
```

### 4. Webサーバー設定

```bash
# Nginx設定のコピー
sudo cp nginx.conf /etc/nginx/sites-available/shisecal
sudo ln -s /etc/nginx/sites-available/shisecal /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# PHP-FPM設定のコピー
sudo cp php-fpm.conf /etc/php/8.1/fpm/pool.d/shisecal.conf

# サービスの再起動
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### 5. Supervisor設定（キューワーカー）

```bash
# Supervisor設定のコピー
sudo cp supervisor.conf /etc/supervisor/conf.d/shisecal.conf

# Supervisorの再読み込み
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start shisecal-worker:*
sudo supervisorctl start shisecal-scheduler
```

## 🔧 設定項目

### 環境変数（.env）

| 変数名 | 説明 | 例 |
|--------|------|-----|
| `APP_ENV` | アプリケーション環境 | `production` |
| `APP_DEBUG` | デバッグモード | `false` |
| `APP_URL` | アプリケーションURL | `https://your-domain.com` |
| `DB_HOST` | データベースホスト | `127.0.0.1` |
| `DB_DATABASE` | データベース名 | `shisecal_production` |
| `REDIS_HOST` | Redisホスト | `127.0.0.1` |
| `MAIL_MAILER` | メール送信方法 | `smtp` |

### SSL証明書の設定

```bash
# Let's Encryptを使用する場合
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## 📊 監視とメンテナンス

### ヘルスチェック

- 基本チェック: `GET /health`
- 詳細チェック: `GET /health/detailed`

### ログファイル

- アプリケーションログ: `/var/www/shisecal/storage/logs/laravel.log`
- Nginxアクセスログ: `/var/log/nginx/shisecal_access.log`
- Nginxエラーログ: `/var/log/nginx/shisecal_error.log`
- PHP-FPMログ: `/var/log/php8.1-fpm-shisecal-error.log`

### 定期メンテナンス

```bash
# ログローテーション
sudo logrotate -f /etc/logrotate.d/nginx
sudo logrotate -f /etc/logrotate.d/php8.1-fpm

# キャッシュクリア
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# データベース最適化
php artisan optimize:clear
php artisan optimize
```

## 🔒 セキュリティ

### ファイアウォール設定

```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw deny 3306  # MySQL（必要に応じて特定IPのみ許可）
sudo ufw deny 6379  # Redis（必要に応じて特定IPのみ許可）
```

### セキュリティヘッダー

Nginx設定に以下のセキュリティヘッダーが含まれています：

- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `X-Content-Type-Options: nosniff`
- `Strict-Transport-Security`
- `Content-Security-Policy`

## 📈 パフォーマンス最適化

### OPcache設定

```ini
; /etc/php/8.1/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### Redis設定

```bash
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🐳 Docker デプロイメント（オプション）

```bash
# Docker Composeを使用したデプロイ
docker-compose up -d

# コンテナの状態確認
docker-compose ps

# ログの確認
docker-compose logs -f app
```

## 🔄 バックアップ

### データベースバックアップ

```bash
# 日次バックアップスクリプト
#!/bin/bash
BACKUP_DIR="/var/backups/shisecal"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p shisecal_production > "$BACKUP_DIR/db_backup_$DATE.sql"
gzip "$BACKUP_DIR/db_backup_$DATE.sql"

# 30日以上古いバックアップを削除
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete
```

### ファイルバックアップ

```bash
# アプリケーションファイルのバックアップ
tar -czf "/var/backups/shisecal/app_backup_$(date +%Y%m%d_%H%M%S).tar.gz" \
    -C /var/www/shisecal \
    --exclude=vendor \
    --exclude=node_modules \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    .
```

## 🚨 トラブルシューティング

### よくある問題

1. **500エラー**
   - ログファイルを確認: `tail -f storage/logs/laravel.log`
   - ファイル権限を確認: `chmod -R 755 storage bootstrap/cache`

2. **キューが動作しない**
   - Supervisorの状態確認: `sudo supervisorctl status`
   - ワーカーの再起動: `sudo supervisorctl restart shisecal-worker:*`

3. **データベース接続エラー**
   - 接続設定を確認: `.env`ファイルのDB設定
   - MySQLサービス状態: `sudo systemctl status mysql`

### パフォーマンス問題

1. **レスポンスが遅い**
   - OPcacheの状態確認
   - データベースクエリの最適化
   - Redisキャッシュの確認

2. **メモリ不足**
   - PHP-FPMプロセス数の調整
   - メモリ制限の確認

## 📞 サポート

問題が発生した場合は、以下の情報を含めてサポートチームに連絡してください：

- エラーメッセージ
- ログファイルの関連部分
- 発生時刻
- 実行していた操作

---

**注意**: このドキュメントは定期的に更新されます。最新版を確認してください。