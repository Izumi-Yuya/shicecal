# ドキュメント管理システム バックアップ・復旧手順書

## 概要

このドキュメントは、ドキュメント管理システムのバックアップ・復旧に関する詳細な手順を説明します。データの安全性を確保し、障害発生時の迅速な復旧を可能にします。

## バックアップ戦略

### 1. バックアップ対象

#### 1.1 データベース
- **document_folders**: フォルダ構造とメタデータ
- **document_files**: ファイル情報とメタデータ
- **facilities**: 施設情報（関連データ）
- **users**: ユーザー情報（権限管理）
- **activity_logs**: 操作履歴

#### 1.2 ファイルストレージ
- **アップロードファイル**: `storage/app/public/documents/`
- **フォルダ構造**: ディレクトリ階層
- **ファイル属性**: 権限、タイムスタンプ

#### 1.3 設定ファイル
- **.env**: 環境設定
- **config/**: アプリケーション設定
- **storage/logs/**: ログファイル（重要なもの）

### 2. バックアップ種別

#### 2.1 フルバックアップ
- **頻度**: 週1回（日曜日 2:00 AM）
- **保持期間**: 3ヶ月
- **対象**: 全データ・全ファイル

#### 2.2 増分バックアップ
- **頻度**: 毎日（2:00 AM）
- **保持期間**: 30日
- **対象**: 前回バックアップ以降の変更分

#### 2.3 差分バックアップ
- **頻度**: 6時間毎
- **保持期間**: 7日
- **対象**: 最新フルバックアップ以降の変更分

## バックアップ実装

### 1. データベースバックアップ

#### 1.1 フルバックアップスクリプト

```bash
#!/bin/bash
# db-full-backup.sh

# 設定
DB_HOST="localhost"
DB_USER="backup_user"
DB_PASS="backup_password"
DB_NAME="facility_management"
BACKUP_DIR="/backup/database"
DATE=$(date +%Y%m%d_%H%M%S)

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR"

# フルバックアップ実行
mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --opt \
    "$DB_NAME" > "$BACKUP_DIR/full_backup_$DATE.sql"

# 圧縮
gzip "$BACKUP_DIR/full_backup_$DATE.sql"

# 古いバックアップ削除（90日以上）
find "$BACKUP_DIR" -name "full_backup_*.sql.gz" -mtime +90 -delete

# バックアップ検証
if [ -f "$BACKUP_DIR/full_backup_$DATE.sql.gz" ]; then
    echo "Full backup completed successfully: full_backup_$DATE.sql.gz"
    
    # バックアップサイズ記録
    SIZE=$(du -h "$BACKUP_DIR/full_backup_$DATE.sql.gz" | cut -f1)
    echo "$(date): Full backup completed - Size: $SIZE" >> /var/log/backup.log
else
    echo "Full backup failed!" >&2
    echo "$(date): Full backup FAILED" >> /var/log/backup.log
    exit 1
fi
```

#### 1.2 増分バックアップスクリプト

```bash
#!/bin/bash
# db-incremental-backup.sh

# 設定
DB_HOST="localhost"
DB_USER="backup_user"
DB_PASS="backup_password"
DB_NAME="facility_management"
BACKUP_DIR="/backup/database/incremental"
DATE=$(date +%Y%m%d_%H%M%S)
LAST_BACKUP_TIME=$(cat /var/lib/mysql-backup/last_backup_time 2>/dev/null || echo "1970-01-01 00:00:00")

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR"

# 増分バックアップ実行（変更されたレコードのみ）
mysql \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --batch \
    --skip-column-names \
    "$DB_NAME" << EOF > "$BACKUP_DIR/incremental_backup_$DATE.sql"

-- Document folders changes
SELECT CONCAT('INSERT INTO document_folders VALUES (', 
    QUOTE(id), ',', QUOTE(facility_id), ',', IFNULL(QUOTE(parent_id), 'NULL'), ',',
    QUOTE(name), ',', QUOTE(path), ',', QUOTE(created_by), ',',
    QUOTE(created_at), ',', QUOTE(updated_at), ');')
FROM document_folders 
WHERE updated_at > '$LAST_BACKUP_TIME';

-- Document files changes
SELECT CONCAT('INSERT INTO document_files VALUES (',
    QUOTE(id), ',', QUOTE(facility_id), ',', IFNULL(QUOTE(folder_id), 'NULL'), ',',
    QUOTE(original_name), ',', QUOTE(stored_name), ',', QUOTE(file_path), ',',
    QUOTE(file_size), ',', QUOTE(mime_type), ',', QUOTE(file_extension), ',',
    QUOTE(uploaded_by), ',', QUOTE(created_at), ',', QUOTE(updated_at), ');')
FROM document_files 
WHERE updated_at > '$LAST_BACKUP_TIME';

EOF

# 現在時刻を記録
echo "$(date '+%Y-%m-%d %H:%M:%S')" > /var/lib/mysql-backup/last_backup_time

# 圧縮
gzip "$BACKUP_DIR/incremental_backup_$DATE.sql"

echo "Incremental backup completed: incremental_backup_$DATE.sql.gz"
```

### 2. ファイルストレージバックアップ

#### 2.1 フルファイルバックアップ

```bash
#!/bin/bash
# files-full-backup.sh

# 設定
SOURCE_DIR="/var/www/html/storage/app/public/documents"
BACKUP_DIR="/backup/files"
DATE=$(date +%Y%m%d_%H%M%S)

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR"

# フルバックアップ実行
tar \
    --create \
    --gzip \
    --file="$BACKUP_DIR/files_full_backup_$DATE.tar.gz" \
    --directory="$(dirname "$SOURCE_DIR")" \
    --preserve-permissions \
    --preserve-order \
    --verbose \
    "$(basename "$SOURCE_DIR")"

# バックアップ検証
if tar --test --gzip --file="$BACKUP_DIR/files_full_backup_$DATE.tar.gz" > /dev/null 2>&1; then
    echo "Files full backup completed successfully: files_full_backup_$DATE.tar.gz"
    
    # バックアップサイズ記録
    SIZE=$(du -h "$BACKUP_DIR/files_full_backup_$DATE.tar.gz" | cut -f1)
    echo "$(date): Files full backup completed - Size: $SIZE" >> /var/log/backup.log
else
    echo "Files full backup verification failed!" >&2
    echo "$(date): Files full backup FAILED" >> /var/log/backup.log
    exit 1
fi

# 古いバックアップ削除（90日以上）
find "$BACKUP_DIR" -name "files_full_backup_*.tar.gz" -mtime +90 -delete
```

#### 2.2 増分ファイルバックアップ

```bash
#!/bin/bash
# files-incremental-backup.sh

# 設定
SOURCE_DIR="/var/www/html/storage/app/public/documents"
BACKUP_DIR="/backup/files/incremental"
DATE=$(date +%Y%m%d_%H%M%S)
SNAPSHOT_FILE="/var/lib/file-backup/snapshot.snar"

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR"
mkdir -p "$(dirname "$SNAPSHOT_FILE")"

# 増分バックアップ実行
tar \
    --create \
    --gzip \
    --file="$BACKUP_DIR/files_incremental_backup_$DATE.tar.gz" \
    --directory="$(dirname "$SOURCE_DIR")" \
    --listed-incremental="$SNAPSHOT_FILE" \
    --preserve-permissions \
    --preserve-order \
    --verbose \
    "$(basename "$SOURCE_DIR")"

echo "Files incremental backup completed: files_incremental_backup_$DATE.tar.gz"

# 古い増分バックアップ削除（30日以上）
find "$BACKUP_DIR" -name "files_incremental_backup_*.tar.gz" -mtime +30 -delete
```

### 3. 設定ファイルバックアップ

```bash
#!/bin/bash
# config-backup.sh

# 設定
PROJECT_DIR="/var/www/html"
BACKUP_DIR="/backup/config"
DATE=$(date +%Y%m%d_%H%M%S)

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR"

# 設定ファイルバックアップ
tar \
    --create \
    --gzip \
    --file="$BACKUP_DIR/config_backup_$DATE.tar.gz" \
    --directory="$PROJECT_DIR" \
    .env \
    config/ \
    storage/logs/laravel.log

echo "Config backup completed: config_backup_$DATE.tar.gz"

# 古いバックアップ削除（30日以上）
find "$BACKUP_DIR" -name "config_backup_*.tar.gz" -mtime +30 -delete
```

## 自動バックアップ設定

### 1. Crontab設定

```bash
# /etc/crontab

# フルバックアップ（毎週日曜日 2:00 AM）
0 2 * * 0 root /backup/scripts/db-full-backup.sh
0 3 * * 0 root /backup/scripts/files-full-backup.sh

# 増分バックアップ（毎日 2:00 AM、日曜日除く）
0 2 * * 1-6 root /backup/scripts/db-incremental-backup.sh
0 3 * * 1-6 root /backup/scripts/files-incremental-backup.sh

# 設定ファイルバックアップ（毎日 4:00 AM）
0 4 * * * root /backup/scripts/config-backup.sh

# バックアップ監視（毎時）
0 * * * * root /backup/scripts/backup-monitor.sh
```

### 2. Systemd Timer設定

```ini
# /etc/systemd/system/document-backup.timer
[Unit]
Description=Document Management Backup Timer
Requires=document-backup.service

[Timer]
OnCalendar=daily
Persistent=true

[Install]
WantedBy=timers.target
```

```ini
# /etc/systemd/system/document-backup.service
[Unit]
Description=Document Management Backup Service
After=mysql.service

[Service]
Type=oneshot
ExecStart=/backup/scripts/daily-backup.sh
User=backup
Group=backup
```

## 復旧手順

### 1. 完全復旧

#### 1.1 データベース完全復旧

```bash
#!/bin/bash
# db-full-restore.sh

# パラメータ確認
if [ $# -ne 1 ]; then
    echo "Usage: $0 <backup_file>"
    echo "Example: $0 /backup/database/full_backup_20241201_020000.sql.gz"
    exit 1
fi

BACKUP_FILE="$1"
DB_HOST="localhost"
DB_USER="restore_user"
DB_PASS="restore_password"
DB_NAME="facility_management"

# バックアップファイル存在確認
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE" >&2
    exit 1
fi

# 復旧前の確認
echo "WARNING: This will completely replace the current database!"
echo "Database: $DB_NAME"
echo "Backup file: $BACKUP_FILE"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# データベース停止（アプリケーション）
systemctl stop nginx
systemctl stop php-fpm

# 現在のデータベースバックアップ（安全のため）
CURRENT_BACKUP="/tmp/pre_restore_backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    "$DB_NAME" > "$CURRENT_BACKUP"

echo "Current database backed up to: $CURRENT_BACKUP"

# データベース復旧実行
echo "Starting database restore..."

if [[ "$BACKUP_FILE" == *.gz ]]; then
    # 圧縮ファイルの場合
    gunzip -c "$BACKUP_FILE" | mysql \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        "$DB_NAME"
else
    # 非圧縮ファイルの場合
    mysql \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        "$DB_NAME" < "$BACKUP_FILE"
fi

# 復旧結果確認
if [ $? -eq 0 ]; then
    echo "Database restore completed successfully."
    
    # アプリケーション再起動
    systemctl start php-fpm
    systemctl start nginx
    
    # 動作確認
    php /var/www/html/artisan migrate:status
    
    echo "$(date): Database restore completed from $BACKUP_FILE" >> /var/log/restore.log
else
    echo "Database restore failed!" >&2
    
    # 元のデータベースを復旧
    echo "Restoring original database..."
    mysql \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        "$DB_NAME" < "$CURRENT_BACKUP"
    
    systemctl start php-fpm
    systemctl start nginx
    
    echo "$(date): Database restore FAILED from $BACKUP_FILE" >> /var/log/restore.log
    exit 1
fi
```

#### 1.2 ファイル完全復旧

```bash
#!/bin/bash
# files-full-restore.sh

# パラメータ確認
if [ $# -ne 1 ]; then
    echo "Usage: $0 <backup_file>"
    echo "Example: $0 /backup/files/files_full_backup_20241201_030000.tar.gz"
    exit 1
fi

BACKUP_FILE="$1"
RESTORE_DIR="/var/www/html/storage/app/public"
TARGET_DIR="$RESTORE_DIR/documents"

# バックアップファイル存在確認
if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE" >&2
    exit 1
fi

# 復旧前の確認
echo "WARNING: This will completely replace the current files!"
echo "Target directory: $TARGET_DIR"
echo "Backup file: $BACKUP_FILE"
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# 現在のファイルバックアップ（安全のため）
if [ -d "$TARGET_DIR" ]; then
    CURRENT_BACKUP="/tmp/pre_restore_files_$(date +%Y%m%d_%H%M%S).tar.gz"
    tar --create --gzip --file="$CURRENT_BACKUP" --directory="$RESTORE_DIR" documents
    echo "Current files backed up to: $CURRENT_BACKUP"
fi

# アプリケーション停止
systemctl stop nginx

# 既存ディレクトリ削除
if [ -d "$TARGET_DIR" ]; then
    rm -rf "$TARGET_DIR"
fi

# ファイル復旧実行
echo "Starting files restore..."

tar \
    --extract \
    --gzip \
    --file="$BACKUP_FILE" \
    --directory="$RESTORE_DIR" \
    --preserve-permissions \
    --verbose

# 復旧結果確認
if [ $? -eq 0 ] && [ -d "$TARGET_DIR" ]; then
    echo "Files restore completed successfully."
    
    # 権限設定
    chown -R www-data:www-data "$TARGET_DIR"
    chmod -R 755 "$TARGET_DIR"
    
    # シンボリックリンク再作成
    cd /var/www/html
    php artisan storage:link
    
    # アプリケーション再起動
    systemctl start nginx
    
    echo "$(date): Files restore completed from $BACKUP_FILE" >> /var/log/restore.log
else
    echo "Files restore failed!" >&2
    
    # 元のファイルを復旧（存在する場合）
    if [ -f "$CURRENT_BACKUP" ]; then
        echo "Restoring original files..."
        tar --extract --gzip --file="$CURRENT_BACKUP" --directory="$RESTORE_DIR"
        chown -R www-data:www-data "$TARGET_DIR"
        chmod -R 755 "$TARGET_DIR"
    fi
    
    systemctl start nginx
    
    echo "$(date): Files restore FAILED from $BACKUP_FILE" >> /var/log/restore.log
    exit 1
fi
```

### 2. 部分復旧

#### 2.1 特定施設のファイル復旧

```bash
#!/bin/bash
# restore-facility-files.sh

# パラメータ確認
if [ $# -ne 2 ]; then
    echo "Usage: $0 <backup_file> <facility_id>"
    echo "Example: $0 /backup/files/files_full_backup_20241201.tar.gz 123"
    exit 1
fi

BACKUP_FILE="$1"
FACILITY_ID="$2"
RESTORE_DIR="/var/www/html/storage/app/public/documents"
TEMP_DIR="/tmp/restore_facility_$FACILITY_ID"

# 一時ディレクトリ作成
mkdir -p "$TEMP_DIR"

# バックアップから特定施設のファイルを抽出
tar \
    --extract \
    --gzip \
    --file="$BACKUP_FILE" \
    --directory="$TEMP_DIR" \
    --wildcards \
    "documents/facility_$FACILITY_ID/*"

# 抽出されたファイルを復旧
if [ -d "$TEMP_DIR/documents/facility_$FACILITY_ID" ]; then
    # 既存ディレクトリのバックアップ
    if [ -d "$RESTORE_DIR/facility_$FACILITY_ID" ]; then
        mv "$RESTORE_DIR/facility_$FACILITY_ID" "$RESTORE_DIR/facility_${FACILITY_ID}_backup_$(date +%Y%m%d_%H%M%S)"
    fi
    
    # ファイル復旧
    mv "$TEMP_DIR/documents/facility_$FACILITY_ID" "$RESTORE_DIR/"
    
    # 権限設定
    chown -R www-data:www-data "$RESTORE_DIR/facility_$FACILITY_ID"
    chmod -R 755 "$RESTORE_DIR/facility_$FACILITY_ID"
    
    echo "Facility $FACILITY_ID files restored successfully."
else
    echo "No files found for facility $FACILITY_ID in backup." >&2
    exit 1
fi

# 一時ディレクトリ削除
rm -rf "$TEMP_DIR"
```

#### 2.2 特定期間のデータ復旧

```bash
#!/bin/bash
# restore-period-data.sh

# パラメータ確認
if [ $# -ne 3 ]; then
    echo "Usage: $0 <backup_file> <start_date> <end_date>"
    echo "Example: $0 /backup/database/full_backup_20241201.sql.gz 2024-11-01 2024-11-30"
    exit 1
fi

BACKUP_FILE="$1"
START_DATE="$2"
END_DATE="$3"
DB_NAME="facility_management"
TEMP_SQL="/tmp/period_restore_$(date +%Y%m%d_%H%M%S).sql"

# 期間指定でデータ抽出
cat > "$TEMP_SQL" << EOF
-- 指定期間のデータを削除
DELETE FROM document_files 
WHERE created_at BETWEEN '$START_DATE 00:00:00' AND '$END_DATE 23:59:59';

DELETE FROM document_folders 
WHERE created_at BETWEEN '$START_DATE 00:00:00' AND '$END_DATE 23:59:59';
EOF

# バックアップから期間データを抽出して追加
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip -c "$BACKUP_FILE" | grep -E "(INSERT INTO document_|INSERT INTO document_files)" | \
    grep -E "('$START_DATE|'$END_DATE)" >> "$TEMP_SQL"
else
    grep -E "(INSERT INTO document_|INSERT INTO document_files)" "$BACKUP_FILE" | \
    grep -E "('$START_DATE|'$END_DATE)" >> "$TEMP_SQL"
fi

# SQL実行
mysql --host="localhost" --user="restore_user" --password="restore_password" "$DB_NAME" < "$TEMP_SQL"

if [ $? -eq 0 ]; then
    echo "Period data restore completed: $START_DATE to $END_DATE"
else
    echo "Period data restore failed!" >&2
    exit 1
fi

# 一時ファイル削除
rm -f "$TEMP_SQL"
```

## バックアップ監視

### 1. バックアップ状況監視

```bash
#!/bin/bash
# backup-monitor.sh

BACKUP_DIR="/backup"
LOG_FILE="/var/log/backup-monitor.log"
ALERT_EMAIL="admin@example.com"

# 今日のバックアップファイル確認
TODAY=$(date +%Y%m%d)
EXPECTED_FILES=(
    "$BACKUP_DIR/database/full_backup_${TODAY}_*.sql.gz"
    "$BACKUP_DIR/files/files_full_backup_${TODAY}_*.tar.gz"
    "$BACKUP_DIR/config/config_backup_${TODAY}_*.tar.gz"
)

MISSING_BACKUPS=()

for pattern in "${EXPECTED_FILES[@]}"; do
    if ! ls $pattern 1> /dev/null 2>&1; then
        MISSING_BACKUPS+=("$pattern")
    fi
done

# 結果判定
if [ ${#MISSING_BACKUPS[@]} -eq 0 ]; then
    echo "$(date): All backups completed successfully" >> "$LOG_FILE"
else
    echo "$(date): Missing backups detected:" >> "$LOG_FILE"
    for missing in "${MISSING_BACKUPS[@]}"; do
        echo "  - $missing" >> "$LOG_FILE"
    done
    
    # アラートメール送信
    {
        echo "Subject: Backup Alert - Missing Files"
        echo ""
        echo "The following backup files are missing:"
        for missing in "${MISSING_BACKUPS[@]}"; do
            echo "  - $missing"
        done
        echo ""
        echo "Please check the backup system immediately."
    } | sendmail "$ALERT_EMAIL"
fi

# ディスク容量確認
DISK_USAGE=$(df -h "$BACKUP_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "$(date): Backup disk usage high: ${DISK_USAGE}%" >> "$LOG_FILE"
    
    # 容量アラート
    {
        echo "Subject: Backup Alert - Disk Space Warning"
        echo ""
        echo "Backup disk usage is at ${DISK_USAGE}%"
        echo "Please clean up old backups or expand storage."
    } | sendmail "$ALERT_EMAIL"
fi
```

### 2. バックアップ整合性チェック

```bash
#!/bin/bash
# backup-integrity-check.sh

BACKUP_DIR="/backup"
LOG_FILE="/var/log/backup-integrity.log"

# データベースバックアップ整合性チェック
echo "$(date): Starting backup integrity check" >> "$LOG_FILE"

for backup_file in "$BACKUP_DIR"/database/*.sql.gz; do
    if [ -f "$backup_file" ]; then
        # ファイル破損チェック
        if gunzip -t "$backup_file" 2>/dev/null; then
            echo "$(date): OK - $backup_file" >> "$LOG_FILE"
        else
            echo "$(date): CORRUPTED - $backup_file" >> "$LOG_FILE"
        fi
    fi
done

# ファイルバックアップ整合性チェック
for backup_file in "$BACKUP_DIR"/files/*.tar.gz; do
    if [ -f "$backup_file" ]; then
        # アーカイブ整合性チェック
        if tar -tzf "$backup_file" >/dev/null 2>&1; then
            echo "$(date): OK - $backup_file" >> "$LOG_FILE"
        else
            echo "$(date): CORRUPTED - $backup_file" >> "$LOG_FILE"
        fi
    fi
done

echo "$(date): Backup integrity check completed" >> "$LOG_FILE"
```

## 災害復旧計画

### 1. 復旧優先度

#### レベル1（最高優先度）- 1時間以内
- データベース基本機能復旧
- 認証システム復旧
- 基本的なファイルアクセス復旧

#### レベル2（高優先度）- 4時間以内
- 全ファイル機能復旧
- フォルダ管理機能復旧
- ユーザー権限システム復旧

#### レベル3（中優先度）- 24時間以内
- 履歴データ復旧
- 統計情報復旧
- 最適化機能復旧

### 2. 復旧手順書

```bash
#!/bin/bash
# disaster-recovery-plan.sh

echo "=== DISASTER RECOVERY PLAN ==="
echo "Starting emergency recovery procedure..."

# Phase 1: Critical Systems (1 hour)
echo "Phase 1: Critical Systems Recovery"

# データベース復旧
echo "1.1 Database recovery..."
/backup/scripts/db-full-restore.sh /backup/database/latest_full_backup.sql.gz

# 基本設定復旧
echo "1.2 Configuration recovery..."
tar -xzf /backup/config/latest_config_backup.tar.gz -C /var/www/html/

# 権限設定
echo "1.3 Permission setup..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage

# サービス再起動
echo "1.4 Service restart..."
systemctl restart mysql
systemctl restart php-fpm
systemctl restart nginx

# Phase 2: File Systems (4 hours)
echo "Phase 2: File Systems Recovery"

# ファイル復旧
echo "2.1 Files recovery..."
/backup/scripts/files-full-restore.sh /backup/files/latest_files_backup.tar.gz

# Phase 3: Verification
echo "Phase 3: System Verification"

# 動作確認
echo "3.1 System health check..."
php /var/www/html/artisan documents:health-check

echo "=== RECOVERY COMPLETED ==="
```

## 運用チェックリスト

### 日次チェック
- [ ] バックアップ実行確認
- [ ] ログファイル確認
- [ ] ディスク容量確認
- [ ] システム稼働確認

### 週次チェック
- [ ] フルバックアップ実行確認
- [ ] バックアップ整合性チェック
- [ ] 復旧テスト実行
- [ ] 監視システム確認

### 月次チェック
- [ ] 災害復旧計画見直し
- [ ] バックアップ保持期間確認
- [ ] 容量計画見直し
- [ ] セキュリティ監査

---

**最終更新日**: 2024年12月
**バージョン**: 1.0
**作成者**: システム管理チーム