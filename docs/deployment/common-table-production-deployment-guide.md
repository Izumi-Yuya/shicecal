# CommonTable本番環境デプロイメントガイド

## 概要

このドキュメントは、CommonTableレイアウトコンポーネントを本番環境にデプロイするための手順とチェックリストを提供します。

## 前提条件

### システム要件
- PHP 8.2以上
- Laravel 9.x
- MySQL 8.0以上（本番環境）
- Redis（キャッシュ用）
- Nginx/Apache
- Node.js 18以上（アセットビルド用）

### 必要な権限
- サーバーへのSSHアクセス
- データベースへの読み書き権限
- ファイルシステムへの書き込み権限
- Webサーバーの設定変更権限

## デプロイメント前チェックリスト

### 1. コード品質確認
- [ ] 全てのテストが通過している
- [ ] コードレビューが完了している
- [ ] セキュリティ監査が完了している
- [ ] パフォーマンステストが完了している

### 2. 依存関係確認
- [ ] Composerの依存関係が最新である
- [ ] NPMパッケージが最新である
- [ ] 本番環境で必要なPHP拡張が有効である

### 3. 設定ファイル確認
- [ ] `.env.production`ファイルが準備されている
- [ ] データベース接続設定が正しい
- [ ] キャッシュ設定が適切である
- [ ] ログ設定が適切である

### 4. アセット確認
- [ ] CSSファイルがビルドされている
- [ ] JavaScriptファイルがビルドされている
- [ ] 画像ファイルが最適化されている

## デプロイメント手順

### ステップ1: バックアップ作成

```bash
# データベースバックアップ
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# ファイルバックアップ
tar -czf app_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/application

# 設定ファイルバックアップ
cp .env .env.backup_$(date +%Y%m%d_%H%M%S)
```

### ステップ2: メンテナンスモード有効化

```bash
php artisan down --message="システムメンテナンス中です。しばらくお待ちください。"
```

### ステップ3: コードデプロイ

```bash
# Gitからコードを取得
git fetch origin
git checkout main
git pull origin main

# 依存関係のインストール
composer install --no-dev --optimize-autoloader

# NPM依存関係のインストール
npm ci --production

# アセットのビルド
npm run build
```

### ステップ4: データベース更新

```bash
# マイグレーション実行
php artisan migrate --force

# キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### ステップ5: 設定最適化

```bash
# 設定キャッシュ
php artisan config:cache

# ルートキャッシュ
php artisan route:cache

# ビューキャッシュ
php artisan view:cache

# イベントキャッシュ
php artisan event:cache
```

### ステップ6: 権限設定

```bash
# ストレージディレクトリの権限設定
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 所有者設定
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### ステップ7: 動作確認

```bash
# アプリケーションの基本動作確認
php artisan tinker
>>> App\Models\User::count()

# CommonTableコンポーネントの動作確認
php artisan test tests/Feature/Components/CommonTableFinalIntegrationSummary.php
```

### ステップ8: メンテナンスモード解除

```bash
php artisan up
```

## 本番環境テスト手順

### 1. 基本機能テスト

#### CommonTableコンポーネントの表示確認
1. 施設詳細ページにアクセス
2. 基本情報カードが正しく表示されることを確認
3. 土地情報カードが正しく表示されることを確認
4. 建物情報カードが正しく表示されることを確認
5. ライフライン設備カードが正しく表示されることを確認

#### レスポンシブデザインテスト
1. デスクトップブラウザでの表示確認
2. タブレットでの表示確認
3. スマートフォンでの表示確認

#### アクセシビリティテスト
1. スクリーンリーダーでの読み上げ確認
2. キーボードナビゲーション確認
3. ハイコントラストモードでの表示確認

### 2. パフォーマンステスト

#### ページ読み込み速度
```bash
# 複数回アクセスして平均読み込み時間を測定
for i in {1..10}; do
  curl -w "@curl-format.txt" -o /dev/null -s "https://your-domain.com/facilities/1"
done
```

#### メモリ使用量監視
```bash
# メモリ使用量の監視
watch -n 1 'free -m'

# PHPプロセスのメモリ使用量
ps aux | grep php-fpm | awk '{sum+=$6} END {print "Total Memory: " sum/1024 " MB"}'
```

### 3. セキュリティテスト

#### XSS対策確認
1. 入力フィールドにスクリプトタグを入力
2. 出力がエスケープされていることを確認

#### SQLインジェクション対策確認
1. 検索フィールドにSQLインジェクション文字列を入力
2. エラーが発生しないことを確認

## ロールバック計画

### 緊急時ロールバック手順

#### ステップ1: メンテナンスモード有効化
```bash
php artisan down --message="緊急メンテナンス中です"
```

#### ステップ2: コードロールバック
```bash
# 前のバージョンに戻す
git checkout [previous_commit_hash]

# 依存関係の復元
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build
```

#### ステップ3: データベースロールバック
```bash
# バックアップからデータベースを復元
mysql -u [username] -p [database_name] < backup_[timestamp].sql
```

#### ステップ4: キャッシュクリア
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### ステップ5: メンテナンスモード解除
```bash
php artisan up
```

### ロールバック判断基準

以下の場合はロールバックを実行する：
- 重要な機能が動作しない
- パフォーマンスが著しく低下している
- セキュリティ上の問題が発見された
- データの整合性に問題がある

## 監視とアラート

### 監視項目

#### アプリケーション監視
- レスポンス時間
- エラー率
- メモリ使用量
- CPU使用率

#### データベース監視
- クエリ実行時間
- 接続数
- デッドロック発生数

#### ログ監視
- エラーログ
- アクセスログ
- セキュリティログ

### アラート設定

```bash
# ログ監視スクリプト例
#!/bin/bash
ERROR_COUNT=$(tail -n 1000 /var/log/laravel.log | grep -c "ERROR")
if [ $ERROR_COUNT -gt 10 ]; then
    echo "エラーが多発しています: $ERROR_COUNT 件" | mail -s "Laravel Error Alert" admin@example.com
fi
```

## トラブルシューティング

### よくある問題と解決方法

#### 1. CommonTableコンポーネントが表示されない
**症状**: ページは読み込まれるが、テーブルが表示されない

**原因と解決方法**:
- ビューキャッシュの問題: `php artisan view:clear`
- CSSファイルの読み込み問題: `npm run build`
- データの問題: データベースの内容を確認

#### 2. スタイルが適用されない
**症状**: テーブルは表示されるが、スタイルが崩れている

**原因と解決方法**:
- CSSファイルのビルド問題: `npm run build`
- キャッシュの問題: `php artisan cache:clear`
- CDNの問題: ネットワーク接続を確認

#### 3. パフォーマンスが低下している
**症状**: ページの読み込みが遅い

**原因と解決方法**:
- データベースクエリの最適化
- キャッシュの有効化: `php artisan config:cache`
- 画像の最適化

#### 4. エラーが発生している
**症状**: 500エラーまたは例外が発生

**原因と解決方法**:
- ログファイルを確認: `tail -f storage/logs/laravel.log`
- 権限の確認: `chmod -R 775 storage`
- 依存関係の確認: `composer install`

## 連絡先

### 緊急時連絡先
- システム管理者: admin@example.com
- 開発チーム: dev-team@example.com
- インフラチーム: infra@example.com

### エスカレーション手順
1. 第一次対応: システム管理者
2. 第二次対応: 開発チーム
3. 第三次対応: インフラチーム + 外部ベンダー

## 付録

### A. 設定ファイルテンプレート

#### .env.production
```env
APP_NAME="Shise-Cal"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shisecal_production
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### B. Nginxの設定例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # 静的ファイルのキャッシュ
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### C. 監視スクリプト例

```bash
#!/bin/bash
# health-check.sh

# アプリケーションの健全性チェック
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/health)

if [ $RESPONSE -eq 200 ]; then
    echo "$(date): Application is healthy"
else
    echo "$(date): Application is unhealthy (HTTP $RESPONSE)" | mail -s "Health Check Alert" admin@example.com
fi

# データベース接続チェック
php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "$(date): Database connection is healthy"
else
    echo "$(date): Database connection failed" | mail -s "Database Alert" admin@example.com
fi
```