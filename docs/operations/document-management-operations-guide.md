# ドキュメント管理システム 運用・保守ガイド

## 概要

このガイドは、ドキュメント管理システムの運用・保守を担当するシステム管理者向けの包括的なドキュメントです。日常的な運用手順、監視項目、トラブル対応、セキュリティ管理について説明します。

## システム構成

### アーキテクチャ概要

```
[ユーザー] → [Webサーバー] → [アプリケーション] → [データベース]
                    ↓
              [ファイルストレージ]
```

### 主要コンポーネント

1. **Webサーバー**: Nginx/Apache
2. **アプリケーション**: Laravel 9.x (PHP 8.2+)
3. **データベース**: MySQL 8.0 / SQLite (テスト環境)
4. **ファイルストレージ**: ローカルストレージ / AWS S3
5. **キャッシュ**: Redis (オプション)

### 環境別設定

#### テスト環境
- **データベース**: SQLite
- **ストレージ**: ローカル (`storage/app/public/documents`)
- **URL**: `http://localhost:8000/storage/documents`

#### 開発・本番環境
- **データベース**: MySQL
- **ストレージ**: AWS S3
- **CDN**: CloudFront (推奨)

## 日常運用手順

### 1. システム監視

#### 1.1 基本監視項目

**毎日確認項目:**
- システム稼働状況
- エラーログの確認
- ストレージ使用量
- データベース接続状況

**週次確認項目:**
- パフォーマンス指標
- ユーザーアクティビティ
- セキュリティログ
- バックアップ状況

**月次確認項目:**
- 容量使用傾向
- システムリソース使用状況
- セキュリティ監査
- パフォーマンス分析

#### 1.2 監視コマンド

```bash
# システム状況確認
php artisan queue:work --daemon  # キュー処理確認
php artisan schedule:run         # スケジュール実行確認

# ログ確認
tail -f storage/logs/laravel.log

# データベース接続確認
php artisan tinker
>>> DB::connection()->getPdo();

# ストレージ確認
du -sh storage/app/public/documents/
```

#### 1.3 パフォーマンス監視

```bash
# アプリケーションパフォーマンス
php artisan route:cache
php artisan config:cache
php artisan view:cache

# データベースパフォーマンス
mysql> SHOW PROCESSLIST;
mysql> SHOW STATUS LIKE 'Slow_queries';

# ファイルシステム監視
df -h  # ディスク使用量
iostat -x 1  # I/O統計
```

### 2. ログ管理

#### 2.1 ログファイル場所

```
storage/logs/
├── laravel.log              # アプリケーションログ
├── document-operations.log  # ドキュメント操作ログ
├── security.log            # セキュリティログ
└── performance.log         # パフォーマンスログ
```

#### 2.2 ログローテーション設定

```bash
# /etc/logrotate.d/laravel
/path/to/project/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        /usr/bin/supervisorctl restart laravel-worker
    endscript
}
```

#### 2.3 重要なログエントリ

**監視すべきログパターン:**
```
# エラーログ
ERROR: Document upload failed
ERROR: File not found
ERROR: Permission denied

# セキュリティログ
WARNING: Unauthorized access attempt
WARNING: Suspicious file upload
WARNING: Multiple failed login attempts

# パフォーマンスログ
INFO: Slow query detected
INFO: High memory usage
INFO: Storage capacity warning
```

### 3. バックアップ・復旧手順

#### 3.1 バックアップ対象

1. **データベース**
   - document_folders テーブル
   - document_files テーブル
   - 関連するメタデータ

2. **ファイルストレージ**
   - アップロードされたファイル
   - フォルダ構造

3. **設定ファイル**
   - .env ファイル
   - 設定ファイル

#### 3.2 バックアップスクリプト

```bash
#!/bin/bash
# backup-documents.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/documents"
PROJECT_DIR="/path/to/project"

# データベースバックアップ
mysqldump -u username -p database_name \
  document_folders document_files > \
  "$BACKUP_DIR/db_backup_$DATE.sql"

# ファイルバックアップ
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" \
  "$PROJECT_DIR/storage/app/public/documents"

# 設定ファイルバックアップ
cp "$PROJECT_DIR/.env" "$BACKUP_DIR/env_backup_$DATE"

# 古いバックアップ削除（30日以上）
find "$BACKUP_DIR" -name "*backup*" -mtime +30 -delete

echo "Backup completed: $DATE"
```

#### 3.3 復旧手順

```bash
# 1. データベース復旧
mysql -u username -p database_name < db_backup_YYYYMMDD_HHMMSS.sql

# 2. ファイル復旧
cd /path/to/project
tar -xzf /backup/documents/files_backup_YYYYMMDD_HHMMSS.tar.gz

# 3. 権限設定
chown -R www-data:www-data storage/app/public/documents
chmod -R 755 storage/app/public/documents

# 4. シンボリックリンク再作成
php artisan storage:link

# 5. キャッシュクリア
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 4. 容量管理

#### 4.1 ストレージ監視

```bash
# ディスク使用量確認
df -h /path/to/storage

# フォルダ別使用量
du -sh storage/app/public/documents/facility_*/

# 大きなファイルの特定
find storage/app/public/documents -type f -size +10M -ls
```

#### 4.2 容量制限設定

```php
// config/facility-document.php
'storage' => [
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'max_total_size_per_facility' => 1024 * 1024 * 1024, // 1GB
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'],
],
```

#### 4.3 自動クリーンアップ

```bash
# 古いファイルの自動削除（90日以上）
find storage/app/public/documents -type f -mtime +90 -delete

# 空フォルダの削除
find storage/app/public/documents -type d -empty -delete

# 孤立ファイルの検出と削除
php artisan documents:cleanup-orphaned-files
```

## セキュリティ管理

### 1. アクセス制御

#### 1.1 ファイルアクセス制御

```apache
# .htaccess (Apache)
<Files ~ "\.(php|pl|py|jsp|asp|sh|cgi)$">
    Order allow,deny
    Deny from all
</Files>

# 直接アクセス禁止
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/storage/documents/
RewriteRule ^(.*)$ /index.php [L]
```

```nginx
# nginx.conf
location ~* ^/storage/documents/ {
    deny all;
    return 403;
}

location ~ \.(php|pl|py|jsp|asp|sh|cgi)$ {
    deny all;
    return 403;
}
```

#### 1.2 権限監査

```bash
# ファイル権限確認
find storage/app/public/documents -type f ! -perm 644 -ls
find storage/app/public/documents -type d ! -perm 755 -ls

# 所有者確認
find storage/app/public/documents ! -user www-data -ls
```

### 2. セキュリティ監視

#### 2.1 不正アクセス検知

```bash
# アクセスログ分析
grep "POST /facilities/.*/documents" /var/log/nginx/access.log | \
  awk '{print $1}' | sort | uniq -c | sort -nr

# 失敗したアップロード試行
grep "413\|422\|403" /var/log/nginx/access.log | \
  grep documents
```

#### 2.2 ファイル整合性チェック

```bash
# ファイルハッシュ確認
find storage/app/public/documents -type f -exec sha256sum {} \; > \
  /tmp/file_hashes.txt

# 変更検知
diff /backup/file_hashes_previous.txt /tmp/file_hashes.txt
```

### 3. 脆弱性対策

#### 3.1 ファイルアップロード制限

```php
// app/Rules/SecureFileUpload.php
public function passes($attribute, $value)
{
    // MIMEタイプ検証
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($value->getMimeType(), $allowedMimes)) {
        return false;
    }

    // ファイル内容検証
    $fileContent = file_get_contents($value->getPathname());
    if (strpos($fileContent, '<?php') !== false) {
        return false;
    }

    return true;
}
```

#### 3.2 パストラバーサル対策

```php
// app/Services/DocumentService.php
private function sanitizePath($path)
{
    // 危険な文字列を除去
    $path = str_replace(['../', '..\\', '../', '..\\'], '', $path);
    $path = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $path);
    
    return $path;
}
```

## パフォーマンス最適化

### 1. データベース最適化

#### 1.1 インデックス確認

```sql
-- インデックス使用状況確認
SHOW INDEX FROM document_folders;
SHOW INDEX FROM document_files;

-- スロークエリ確認
SELECT * FROM mysql.slow_log 
WHERE sql_text LIKE '%document_%' 
ORDER BY start_time DESC LIMIT 10;
```

#### 1.2 クエリ最適化

```sql
-- パフォーマンス分析
EXPLAIN SELECT * FROM document_files 
WHERE facility_id = 1 AND folder_id IS NULL;

-- インデックス追加（必要に応じて）
CREATE INDEX idx_files_facility_folder 
ON document_files(facility_id, folder_id);
```

### 2. ファイルシステム最適化

#### 2.1 ディスクI/O監視

```bash
# I/O統計
iostat -x 1 10

# ディスク使用率
iotop -o

# ファイルシステム最適化
tune2fs -l /dev/sda1
```

#### 2.2 キャッシュ設定

```php
// config/cache.php
'stores' => [
    'documents' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => 'doc_cache',
    ],
],
```

### 3. ネットワーク最適化

#### 3.1 CDN設定（AWS CloudFront）

```php
// config/filesystems.php
'documents_cdn' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_DOCUMENTS_BUCKET'),
    'url' => env('AWS_CLOUDFRONT_URL'),
],
```

#### 3.2 圧縮設定

```nginx
# nginx.conf
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types
    text/plain
    text/css
    text/xml
    text/javascript
    application/javascript
    application/xml+rss
    application/json;
```

## トラブルシューティング

### 1. 一般的な問題

#### 1.1 ファイルアップロード失敗

**症状**: ファイルアップロードが失敗する

**診断手順**:
```bash
# 1. ディスク容量確認
df -h

# 2. 権限確認
ls -la storage/app/public/documents/

# 3. PHP設定確認
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time)"

# 4. ログ確認
tail -f storage/logs/laravel.log
```

**対処法**:
- ディスク容量不足 → 容量拡張または不要ファイル削除
- 権限問題 → `chown -R www-data:www-data storage/`
- PHP制限 → php.ini の設定変更

#### 1.2 データベース接続エラー

**症状**: データベースに接続できない

**診断手順**:
```bash
# 1. MySQL接続確認
mysql -u username -p -h hostname

# 2. 設定確認
php artisan tinker
>>> config('database.connections.mysql')

# 3. 接続テスト
php artisan migrate:status
```

**対処法**:
- 認証情報確認
- データベースサーバー状態確認
- ネットワーク接続確認

### 2. パフォーマンス問題

#### 2.1 応答速度低下

**診断手順**:
```bash
# 1. システムリソース確認
top
free -h
df -h

# 2. データベース確認
mysql> SHOW PROCESSLIST;
mysql> SHOW STATUS LIKE 'Slow_queries';

# 3. アプリケーション確認
php artisan route:cache
php artisan config:cache
```

**対処法**:
- キャッシュ最適化
- データベースインデックス追加
- 不要なプロセス停止

### 3. セキュリティインシデント

#### 3.1 不正アクセス検知時の対応

**即座に実行する手順**:
1. 該当IPアドレスのブロック
2. アクセスログの保全
3. 影響範囲の調査
4. 関係者への報告

```bash
# IPブロック（iptables）
iptables -A INPUT -s [不正IP] -j DROP

# ログ保全
cp /var/log/nginx/access.log /backup/incident_$(date +%Y%m%d_%H%M%S).log

# 影響調査
grep [不正IP] /var/log/nginx/access.log
```

## 定期メンテナンス

### 1. 日次メンテナンス

```bash
#!/bin/bash
# daily-maintenance.sh

# ログローテーション
logrotate /etc/logrotate.d/laravel

# 一時ファイル削除
find /tmp -name "laravel_*" -mtime +1 -delete

# キャッシュ最適化
php artisan cache:clear
php artisan view:clear

# バックアップ実行
./backup-documents.sh
```

### 2. 週次メンテナンス

```bash
#!/bin/bash
# weekly-maintenance.sh

# データベース最適化
mysql -u username -p -e "OPTIMIZE TABLE document_folders, document_files;"

# 孤立ファイル削除
php artisan documents:cleanup-orphaned-files

# パフォーマンス分析
php artisan documents:performance-report
```

### 3. 月次メンテナンス

```bash
#!/bin/bash
# monthly-maintenance.sh

# セキュリティ監査
php artisan documents:security-audit

# 容量分析レポート
php artisan documents:storage-report

# システム健全性チェック
php artisan documents:health-check
```

## 監視・アラート設定

### 1. システム監視

#### 1.1 Nagios設定例

```bash
# /etc/nagios/conf.d/document-management.cfg
define service {
    use                     generic-service
    host_name               web-server
    service_description     Document Storage Space
    check_command           check_disk!20%!10%!/path/to/documents
}

define service {
    use                     generic-service
    host_name               web-server
    service_description     Document Upload Function
    check_command           check_http_post!/facilities/1/documents/files
}
```

#### 1.2 Zabbix設定例

```json
{
    "name": "Document Management Monitoring",
    "items": [
        {
            "name": "Document Storage Usage",
            "key": "vfs.fs.size[/path/to/documents,pused]",
            "type": "Zabbix agent",
            "value_type": "Float"
        },
        {
            "name": "Document Upload Errors",
            "key": "log[/path/to/laravel.log,\"Document upload failed\"]",
            "type": "Zabbix agent (active)",
            "value_type": "Log"
        }
    ]
}
```

### 2. アラート通知

#### 2.1 メール通知設定

```php
// config/mail.php
'document_alerts' => [
    'storage_warning' => ['admin@example.com'],
    'security_alert' => ['security@example.com', 'admin@example.com'],
    'performance_alert' => ['dev@example.com'],
],
```

#### 2.2 Slack通知設定

```php
// app/Notifications/DocumentAlert.php
public function via($notifiable)
{
    return ['slack'];
}

public function toSlack($notifiable)
{
    return (new SlackMessage)
        ->error()
        ->content('Document Management Alert')
        ->attachment(function ($attachment) {
            $attachment->title('Storage Warning')
                      ->content('Document storage usage exceeded 80%');
        });
}
```

## 設定ファイル管理

### 1. 環境別設定

#### 1.1 テスト環境 (.env.testing)

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Storage
DOCUMENTS_STORAGE_DRIVER=local
DOCUMENTS_STORAGE_PATH=storage/app/public/documents

# Logging
LOG_LEVEL=debug
```

#### 1.2 本番環境 (.env.production)

```env
# Database
DB_CONNECTION=mysql
DB_HOST=prod-db-server
DB_DATABASE=production_db

# Storage
DOCUMENTS_STORAGE_DRIVER=s3
AWS_DOCUMENTS_BUCKET=prod-documents-bucket
AWS_CLOUDFRONT_URL=https://cdn.example.com

# Logging
LOG_LEVEL=error
```

### 2. 設定の検証

```bash
# 設定値確認
php artisan config:show database
php artisan config:show filesystems

# 環境設定テスト
php artisan documents:config-test
```

## 災害復旧計画

### 1. 復旧優先度

1. **最高優先度**: データベース復旧
2. **高優先度**: 重要ファイルの復旧
3. **中優先度**: 一般ファイルの復旧
4. **低優先度**: キャッシュ・ログの復旧

### 2. 復旧手順書

#### 2.1 完全復旧手順

```bash
#!/bin/bash
# disaster-recovery.sh

echo "Starting disaster recovery..."

# 1. データベース復旧
mysql -u username -p database_name < /backup/latest_db_backup.sql

# 2. 重要ファイル復旧
tar -xzf /backup/critical_files_backup.tar.gz -C /

# 3. 設定ファイル復旧
cp /backup/env_backup_latest /path/to/project/.env

# 4. 権限設定
chown -R www-data:www-data /path/to/project/storage
chmod -R 755 /path/to/project/storage

# 5. アプリケーション再起動
systemctl restart nginx
systemctl restart php-fpm

# 6. 動作確認
php artisan documents:health-check

echo "Disaster recovery completed."
```

#### 2.2 部分復旧手順

```bash
# 特定施設のファイル復旧
tar -xzf /backup/files_backup_latest.tar.gz \
    --wildcards "*/facility_123/*" -C /path/to/project/storage/app/public/documents/

# 特定期間のデータ復旧
mysql -u username -p -e "
DELETE FROM document_files WHERE created_at >= '2024-01-01';
DELETE FROM document_folders WHERE created_at >= '2024-01-01';
"
mysql -u username -p database_name < /backup/db_backup_20231231.sql
```

## 連絡先・エスカレーション

### 1. 緊急連絡先

- **システム管理者**: [連絡先]
- **開発チーム**: [連絡先]
- **インフラチーム**: [連絡先]
- **セキュリティチーム**: [連絡先]

### 2. エスカレーション手順

#### レベル1: 軽微な問題
- 対応者: システム管理者
- 対応時間: 営業時間内
- 報告先: 開発チーム

#### レベル2: 重要な問題
- 対応者: システム管理者 + 開発チーム
- 対応時間: 4時間以内
- 報告先: 管理職

#### レベル3: 緊急事態
- 対応者: 全チーム
- 対応時間: 1時間以内
- 報告先: 経営陣

---

**最終更新日**: 2024年12月
**バージョン**: 1.0
**作成者**: システム管理チーム