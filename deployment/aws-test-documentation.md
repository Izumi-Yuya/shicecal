# AWSテスト環境構築・テストドキュメント

## 概要
施設管理システムのAWSテスト環境におけるWebサーバー構築とテスト手順書

## 前提条件
- AWS EC2インスタンスが起動済み（Ubuntu 20.04 LTS推奨）
- SSH接続が可能
- セキュリティグループでHTTP(80)、HTTPS(443)、SSH(22)ポートが開放済み

## 1. サーバー初期設定

### 1.1 EC2インスタンスへの接続
```bash
# SSH接続
ssh -i your-key.pem ubuntu@your-ec2-public-ip
```

### 1.2 システム更新
```bash
sudo apt update && sudo apt upgrade -y
```

## 2. Webサーバー環境構築

### 2.1 Nginx インストール
```bash
# Nginxインストール
sudo apt install nginx -y

# サービス開始・自動起動設定
sudo systemctl start nginx
sudo systemctl enable nginx

# 動作確認
sudo systemctl status nginx
```

### 2.2 PHP 8.1 インストール
```bash
# PPAリポジトリ追加
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# PHP 8.1と必要な拡張機能をインストール
sudo apt install php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml \
    php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd php8.1-intl \
    php8.1-bcmath php8.1-soap php8.1-redis php8.1-sqlite3 -y

# PHP-FPMサービス開始
sudo systemctl start php8.1-fpm
sudo systemctl enable php8.1-fpm
```

### 2.3 Composer インストール
```bash
# Composerダウンロード・インストール
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# バージョン確認
composer --version
```

### 2.4 Node.js インストール
```bash
# Node.js 18.x インストール
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# バージョン確認
node --version
npm --version
```

## 3. Laravel プロジェクト配置

### 3.1 プロジェクトディレクトリ作成
```bash
# Webルートディレクトリ作成
sudo mkdir -p /var/www/facility-management
sudo chown -R $USER:www-data /var/www/facility-management
sudo chmod -R 755 /var/www/facility-management
```

### 3.2 プロジェクトファイル転送
```bash
# ローカルからEC2へファイル転送（ローカル環境で実行）
scp -i your-key.pem -r ./facility-management ubuntu@your-ec2-ip:/tmp/
```

```bash
# EC2上でファイル移動
sudo mv /tmp/facility-management/* /var/www/facility-management/
sudo chown -R www-data:www-data /var/www/facility-management
sudo chmod -R 755 /var/www/facility-management
sudo chmod -R 775 /var/www/facility-management/storage
sudo chmod -R 775 /var/www/facility-management/bootstrap/cache
```

### 3.3 Laravel 依存関係インストール
```bash
cd /var/www/facility-management

# Composer依存関係インストール
composer install --optimize-autoloader --no-dev

# NPM依存関係インストール・ビルド
npm install
npm run build
```

### 3.4 Laravel 環境設定
```bash
# .envファイル作成
cp .env.example .env

# アプリケーションキー生成
php artisan key:generate

# .envファイル編集（データベース設定等）
sudo nano .env
```

## 4. Nginx設定

### 4.1 バーチャルホスト設定
```bash
# Nginx設定ファイル作成
sudo nano /etc/nginx/sites-available/facility-management
```

設定内容：
```nginx
server {
    listen 80;
    server_name your-domain.com your-ec2-public-ip;
    root /var/www/facility-management/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.2 設定有効化
```bash
# サイト有効化
sudo ln -s /etc/nginx/sites-available/facility-management /etc/nginx/sites-enabled/

# デフォルトサイト無効化
sudo rm /etc/nginx/sites-enabled/default

# 設定テスト
sudo nginx -t

# Nginx再起動
sudo systemctl reload nginx
```

## 5. データベース設定

### 5.1 MySQL インストール（必要に応じて）
```bash
sudo apt install mysql-server -y
sudo mysql_secure_installation
```

### 5.2 データベース・ユーザー作成
```sql
-- MySQLにログイン
sudo mysql

-- データベース作成
CREATE DATABASE facility_management;

-- ユーザー作成・権限付与
CREATE USER 'facility_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON facility_management.* TO 'facility_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.3 Laravel マイグレーション実行
```bash
cd /var/www/facility-management

# マイグレーション実行
php artisan migrate

# シーダー実行（必要に応じて）
php artisan db:seed
```

## 6. テスト実行

### 6.1 基本動作テスト
```bash
# Webサーバー動作確認
curl -I http://your-ec2-public-ip

# PHP動作確認
php -v

# Laravel動作確認
cd /var/www/facility-management
php artisan --version
```

### 6.2 アプリケーションテスト
```bash
# ブラウザでアクセステスト
# http://your-ec2-public-ip

# Laravel機能テスト
php artisan test

# ログ確認
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log
```

### 6.3 パフォーマンステスト
```bash
# 基本的な負荷テスト（Apache Bench使用）
sudo apt install apache2-utils -y
ab -n 100 -c 10 http://your-ec2-public-ip/
```

## 7. セキュリティ設定

### 7.1 ファイアウォール設定
```bash
# UFW有効化
sudo ufw enable

# 必要なポート開放
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'

# 状態確認
sudo ufw status
```

### 7.2 SSL証明書設定（Let's Encrypt）
```bash
# Certbot インストール
sudo apt install certbot python3-certbot-nginx -y

# SSL証明書取得・設定
sudo certbot --nginx -d your-domain.com
```

## 8. 監視・ログ設定

### 8.1 ログローテーション設定
```bash
# Laravel ログローテーション設定
sudo nano /etc/logrotate.d/laravel
```

### 8.2 システム監視
```bash
# システムリソース確認
htop
df -h
free -h

# サービス状態確認
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
```

## 9. トラブルシューティング

### 9.1 よくある問題と解決方法

#### Nginx 502 Bad Gateway
```bash
# PHP-FPM状態確認
sudo systemctl status php8.1-fpm

# ソケットファイル確認
ls -la /var/run/php/

# 設定ファイル確認
sudo nginx -t
```

#### Laravel Permission Error
```bash
# 権限修正
sudo chown -R www-data:www-data /var/www/facility-management
sudo chmod -R 755 /var/www/facility-management
sudo chmod -R 775 /var/www/facility-management/storage
sudo chmod -R 775 /var/www/facility-management/bootstrap/cache
```

#### Database Connection Error
```bash
# .env設定確認
cat /var/www/facility-management/.env

# MySQL接続テスト
mysql -u facility_user -p facility_management
```

## 10. バックアップ・復旧手順

### 10.1 データベースバックアップ
```bash
# データベースダンプ作成
mysqldump -u facility_user -p facility_management > backup_$(date +%Y%m%d).sql
```

### 10.2 アプリケーションバックアップ
```bash
# アプリケーションファイルバックアップ
tar -czf facility-management-backup-$(date +%Y%m%d).tar.gz /var/www/facility-management
```

## チェックリスト

### 構築完了チェック
- [ ] EC2インスタンス起動・SSH接続確認
- [ ] Nginx インストール・起動確認
- [ ] PHP 8.1 インストール・動作確認
- [ ] Composer インストール確認
- [ ] Node.js インストール確認
- [ ] Laravel プロジェクト配置完了
- [ ] データベース設定完了
- [ ] Nginx バーチャルホスト設定完了
- [ ] アプリケーション動作確認
- [ ] SSL証明書設定（本番環境の場合）
- [ ] セキュリティ設定完了
- [ ] バックアップ設定完了

### テスト完了チェック
- [ ] Webサーバー基本動作テスト
- [ ] Laravel アプリケーションテスト
- [ ] データベース接続テスト
- [ ] パフォーマンステスト
- [ ] セキュリティテスト
- [ ] ログ出力確認

## 注意事項
- 本番環境では必ずSSL証明書を設定してください
- 定期的なセキュリティアップデートを実施してください
- バックアップを定期的に取得してください
- ログファイルの容量監視を行ってください