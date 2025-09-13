#!/bin/bash

# Shise-Cal 自動ロールバックスクリプト
# 使用方法: ./deployment/rollback.sh [backup_id] [environment] [options]
#
# 機能:
# - 前バージョンへの復元
# - データベースロールバック
# - サービス再起動
# - ロールバック検証とログ記録
#
# 要件: 3.3, 3.5, 3.6

set -e

# スクリプト設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="/tmp/shicecal_rollback_${TIMESTAMP}.log"

# デフォルト設定
BACKUP_ID="${1:-}"
ENVIRONMENT="${2:-test}"
DRY_RUN="${3:-false}"
SKIP_DB_ROLLBACK="${4:-false}"
FORCE_ROLLBACK="${5:-false}"

# 環境設定
case $ENVIRONMENT in
    "test")
        AWS_HOST="35.75.1.64"
        AWS_USER="ec2-user"
        SSH_KEY="~/Shise-Cal-test-key.pem"
        REMOTE_PATH="/home/ec2-user/shicecal"
        HEALTH_CHECK_URL="http://35.75.1.64"
        ;;
    "production")
        AWS_HOST="${PROD_AWS_HOST:-}"
        AWS_USER="${PROD_AWS_USER:-ec2-user}"
        SSH_KEY="${PROD_SSH_KEY:-}"
        REMOTE_PATH="${PROD_REMOTE_PATH:-/var/www/shicecal}"
        HEALTH_CHECK_URL="${PROD_HEALTH_CHECK_URL:-}"
        ;;
    *)
        echo "❌ 無効な環境: $ENVIRONMENT (test または production を指定してください)"
        exit 1
        ;;
esac

# ログ関数
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE"
}

log_info() { log "INFO" "$@"; }
log_warn() { log "WARN" "$@"; }
log_error() { log "ERROR" "$@"; }
log_success() { log "SUCCESS" "$@"; }

# 色付きログ関数
colored_log() {
    local color="$1"
    local level="$2"
    shift 2
    local message="$*"
    echo -e "\033[${color}m[${level}]\033[0m $message"
    log "$level" "$message"
}

info() { colored_log "32" "INFO" "$@"; }
warn() { colored_log "33" "WARN" "$@"; }
error() { colored_log "31" "ERROR" "$@"; }
success() { colored_log "32" "SUCCESS" "$@"; }

# 使用方法表示
show_usage() {
    cat << EOF
使用方法: $0 [backup_id] [environment] [dry_run] [skip_db_rollback] [force_rollback]

引数:
  backup_id         ロールバック対象のバックアップID
  environment       環境 (test|production) [デフォルト: test]
  dry_run          ドライラン実行 (true|false) [デフォルト: false]
  skip_db_rollback データベースロールバックスキップ (true|false) [デフォルト: false]
  force_rollback   強制ロールバック (true|false) [デフォルト: false]

例:
  $0                                    # 最新バックアップからロールバック
  $0 backup_20250913_143000             # 指定バックアップからロールバック
  $0 backup_20250913_143000 test true   # ドライラン実行
  $0 backup_20250913_143000 production false false true  # 強制ロールバック

環境変数:
  NOTIFICATION_WEBHOOK       # Slack通知用Webhook URL
  ROLLBACK_TIMEOUT=300       # ロールバックタイムアウト (秒)
  BACKUP_RETENTION_DAYS=7    # バックアップ保持日数
EOF
}

# バックアップ一覧表示
list_available_backups() {
    info "📋 利用可能なバックアップを確認中..."
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << 'EOF'
        if [ ! -d "/var/backups/shicecal" ]; then
            echo "❌ バックアップディレクトリが存在しません"
            exit 1
        fi
        
        echo "利用可能なバックアップ:"
        echo "========================"
        
        for info_file in /var/backups/shicecal/info_*.json; do
            if [ -f "$info_file" ]; then
                backup_id=$(basename "$info_file" .json | sed 's/info_//')
                
                if [ -f "/var/backups/shicecal/app_${backup_id}.tar.gz" ]; then
                    echo "🗂️  バックアップID: $backup_id"
                    
                    if command -v jq > /dev/null 2>&1; then
                        echo "   タイムスタンプ: $(jq -r '.timestamp' "$info_file")"
                        echo "   環境: $(jq -r '.environment' "$info_file")"
                        echo "   Git コミット: $(jq -r '.git_commit' "$info_file" | cut -c1-8)"
                        echo "   Git ブランチ: $(jq -r '.git_branch' "$info_file")"
                    fi
                    
                    # ファイルサイズ情報
                    app_size=$(du -h "/var/backups/shicecal/app_${backup_id}.tar.gz" | cut -f1)
                    echo "   アプリサイズ: $app_size"
                    
                    if [ -f "/var/backups/shicecal/db_${backup_id}.sql.gz" ]; then
                        db_size=$(du -h "/var/backups/shicecal/db_${backup_id}.sql.gz" | cut -f1)
                        echo "   DBサイズ: $db_size"
                    else
                        echo "   DBバックアップ: なし"
                    fi
                    
                    echo ""
                fi
            fi
        done
EOF
}

# 最新バックアップの取得
get_latest_backup() {
    info "🔍 最新バックアップを検索中..."
    
    local latest_backup=$(ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << 'EOF'
        latest_file=""
        latest_time=0
        
        for info_file in /var/backups/shicecal/info_*.json; do
            if [ -f "$info_file" ]; then
                backup_id=$(basename "$info_file" .json | sed 's/info_//')
                
                if [ -f "/var/backups/shicecal/app_${backup_id}.tar.gz" ]; then
                    file_time=$(stat -c %Y "$info_file" 2>/dev/null || stat -f %m "$info_file" 2>/dev/null || echo 0)
                    
                    if [ "$file_time" -gt "$latest_time" ]; then
                        latest_time="$file_time"
                        latest_file="$backup_id"
                    fi
                fi
            fi
        done
        
        echo "$latest_file"
EOF
)
    
    if [ -z "$latest_backup" ]; then
        error "利用可能なバックアップが見つかりません"
        return 1
    fi
    
    echo "$latest_backup"
}

# バックアップ検証
validate_backup() {
    local backup_id="$1"
    
    info "🔍 バックアップを検証中: $backup_id"
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        
        # バックアップファイルの存在確認
        if [ ! -f "/var/backups/shicecal/app_${backup_id}.tar.gz" ]; then
            echo "❌ アプリケーションバックアップが見つかりません: $backup_id"
            exit 1
        fi
        
        if [ ! -f "/var/backups/shicecal/info_${backup_id}.json" ]; then
            echo "❌ バックアップ情報ファイルが見つかりません: $backup_id"
            exit 1
        fi
        
        if [ ! -f "/var/backups/shicecal/env_${backup_id}" ]; then
            echo "❌ 環境設定ファイルが見つかりません: $backup_id"
            exit 1
        fi
        
        # バックアップファイルの整合性チェック
        if ! tar -tzf "/var/backups/shicecal/app_${backup_id}.tar.gz" > /dev/null 2>&1; then
            echo "❌ アプリケーションバックアップファイルが破損しています: $backup_id"
            exit 1
        fi
        
        # データベースバックアップの確認 (オプション)
        if [ -f "/var/backups/shicecal/db_${backup_id}.sql.gz" ]; then
            if ! zcat "/var/backups/shicecal/db_${backup_id}.sql.gz" | head -n 1 > /dev/null 2>&1; then
                echo "❌ データベースバックアップファイルが破損しています: $backup_id"
                exit 1
            fi
        fi
        
        echo "✅ バックアップ検証完了: $backup_id"
EOF
    
    success "バックアップ検証完了: $backup_id"
    return 0
}

# 現在の状態バックアップ
create_pre_rollback_backup() {
    info "💾 ロールバック前の現在状態をバックアップ中..."
    
    local pre_rollback_id="pre_rollback_${TIMESTAMP}"
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        cd $REMOTE_PATH
        
        # 現在の状態をバックアップ
        echo "📁 現在のアプリケーション状態をバックアップ中..."
        sudo tar -czf "/var/backups/shicecal/app_${pre_rollback_id}.tar.gz" \
            --exclude=node_modules \
            --exclude=vendor \
            --exclude=storage/logs \
            --exclude=storage/framework/cache \
            --exclude=storage/framework/sessions \
            --exclude=storage/framework/views \
            .
        
        # 現在の設定ファイル
        sudo cp .env "/var/backups/shicecal/env_${pre_rollback_id}"
        
        # バックアップ情報の記録
        echo "{
            \"backup_id\": \"$pre_rollback_id\",
            \"timestamp\": \"$TIMESTAMP\",
            \"type\": \"pre_rollback\",
            \"environment\": \"$ENVIRONMENT\",
            \"git_commit\": \"\$(git rev-parse HEAD 2>/dev/null || echo 'unknown')\",
            \"git_branch\": \"\$(git branch --show-current 2>/dev/null || echo 'unknown')\"
        }" | sudo tee "/var/backups/shicecal/info_${pre_rollback_id}.json" > /dev/null
        
        echo "✅ ロールバック前バックアップ完了: $pre_rollback_id"
EOF
    
    success "ロールバック前バックアップ完了: $pre_rollback_id"
    echo "$pre_rollback_id"
}

# ロールバック実行
execute_rollback() {
    local backup_id="$1"
    
    info "🔄 ロールバックを実行中: $backup_id"
    
    if [ "$DRY_RUN" = "true" ]; then
        info "🔍 ドライラン: ロールバック手順を表示します"
        cat << EOF
ドライラン - 実行予定の手順:
1. メンテナンスモード開始
2. アプリケーションファイルの復元
3. 設定ファイルの復元
4. データベースの復元 (オプション)
5. 依存関係の再インストール
6. キャッシュのクリア
7. サービスの再起動
8. メンテナンスモード終了
9. ヘルスチェック実行
EOF
        return 0
    fi
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        cd $REMOTE_PATH
        
        # メンテナンスモード開始
        echo "🔧 メンテナンスモードを開始..."
        php artisan down --retry=60 --secret="rollback-${TIMESTAMP}" || true
        
        # 現在のディレクトリをバックアップ用に退避
        echo "📦 現在のファイルを退避中..."
        if [ -d "../shicecal_rollback_temp" ]; then
            sudo rm -rf "../shicecal_rollback_temp"
        fi
        sudo mkdir -p "../shicecal_rollback_temp"
        
        # アプリケーションファイルの復元
        echo "📁 アプリケーションファイルを復元中..."
        sudo tar -xzf "/var/backups/shicecal/app_${backup_id}.tar.gz" -C .
        
        # 設定ファイルの復元
        echo "⚙️ 設定ファイルを復元中..."
        sudo cp "/var/backups/shicecal/env_${backup_id}" .env
        
        # データベースの復元 (オプション)
        if [ "$SKIP_DB_ROLLBACK" != "true" ] && [ -f "/var/backups/shicecal/db_${backup_id}.sql.gz" ]; then
            echo "🗄️ データベースを復元中..."
            
            # データベース設定の取得
            DB_NAME=\$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null || echo "")
            DB_USER=\$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null || echo "")
            DB_PASS=\$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null || echo "")
            
            if [ -n "\$DB_NAME" ] && [ -n "\$DB_USER" ]; then
                # 現在のデータベースをバックアップ
                echo "🗄️ 現在のデータベースをバックアップ中..."
                mysqldump -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME" | gzip > "/var/backups/shicecal/db_pre_rollback_${TIMESTAMP}.sql.gz" || true
                
                # データベースの復元
                echo "🗄️ データベースを復元中..."
                zcat "/var/backups/shicecal/db_${backup_id}.sql.gz" | mysql -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME"
            else
                echo "⚠️ データベース設定が取得できませんでした。データベースロールバックをスキップします。"
            fi
        else
            echo "⚠️ データベースロールバックをスキップします"
        fi
        
        # 依存関係の再インストール
        echo "📦 依存関係を再インストール中..."
        if [ -f "composer.json" ]; then
            composer install --no-dev --optimize-autoloader --no-interaction || true
        fi
        
        if [ -f "package.json" ]; then
            npm ci --production=false --silent || true
        fi
        
        # キャッシュのクリア
        echo "🧹 キャッシュをクリア中..."
        php artisan config:clear || true
        php artisan route:clear || true
        php artisan view:clear || true
        php artisan cache:clear || true
        
        # ストレージリンクの確認
        if [ ! -L "public/storage" ]; then
            php artisan storage:link || true
        fi
        
        # 権限の設定
        echo "🔐 権限を設定中..."
        sudo chown -R nginx:nginx storage bootstrap/cache || true
        sudo chmod -R 775 storage bootstrap/cache || true
        
        # サービスの再起動
        echo "🔄 サービスを再起動中..."
        sudo systemctl restart nginx || true
        sudo systemctl restart php-fpm || true
        
        # メンテナンスモード終了
        echo "✅ メンテナンスモードを終了..."
        php artisan up || true
        
        echo "🎉 ロールバック完了!"
EOF
    
    success "ロールバック実行完了: $backup_id"
    return 0
}

# ロールバック後検証
verify_rollback() {
    local backup_id="$1"
    
    info "🔍 ロールバック後の検証を実行中..."
    
    # ヘルスチェック実行
    if [ -f "$SCRIPT_DIR/health-check.sh" ]; then
        info "🏥 ヘルスチェックを実行中..."
        if ! "$SCRIPT_DIR/health-check.sh" "$HEALTH_CHECK_URL"; then
            error "ロールバック後のヘルスチェックに失敗しました"
            return 1
        fi
    else
        warn "ヘルスチェックスクリプトが見つかりません"
    fi
    
    # 基本的な接続テスト
    if ! curl -f -s --max-time 30 "$HEALTH_CHECK_URL" > /dev/null; then
        error "ロールバック後のHTTP接続に失敗しました"
        return 1
    fi
    
    # Git状態の確認
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        cd $REMOTE_PATH
        
        echo "📋 ロールバック後の状態:"
        echo "  Git コミット: \$(git rev-parse HEAD 2>/dev/null || echo 'unknown')"
        echo "  Git ブランチ: \$(git branch --show-current 2>/dev/null || echo 'unknown')"
        echo "  アプリケーション環境: \$(php artisan tinker --execute="echo config('app.env');" 2>/dev/null || echo 'unknown')"
EOF
    
    success "ロールバック後検証完了"
    return 0
}

# 通知送信
send_notification() {
    local status="$1"
    local message="$2"
    local backup_id="$3"
    
    if [ -z "$NOTIFICATION_WEBHOOK" ]; then
        return 0
    fi
    
    local color
    case $status in
        "success") color="good" ;;
        "warning") color="warning" ;;
        "error") color="danger" ;;
        *) color="good" ;;
    esac
    
    local payload=$(cat << EOF
{
    "attachments": [
        {
            "color": "$color",
            "title": "Shise-Cal ロールバック通知",
            "fields": [
                {
                    "title": "環境",
                    "value": "$ENVIRONMENT",
                    "short": true
                },
                {
                    "title": "バックアップID",
                    "value": "$backup_id",
                    "short": true
                },
                {
                    "title": "ステータス",
                    "value": "$status",
                    "short": true
                },
                {
                    "title": "メッセージ",
                    "value": "$message",
                    "short": false
                },
                {
                    "title": "タイムスタンプ",
                    "value": "$TIMESTAMP",
                    "short": true
                }
            ]
        }
    ]
}
EOF
)
    
    curl -X POST -H 'Content-type: application/json' \
        --data "$payload" \
        "$NOTIFICATION_WEBHOOK" > /dev/null 2>&1 || true
}

# 古いバックアップのクリーンアップ
cleanup_old_backups() {
    local retention_days="${BACKUP_RETENTION_DAYS:-7}"
    
    info "🧹 古いバックアップをクリーンアップ中 (${retention_days}日以上前)"
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        if [ -d "/var/backups/shicecal" ]; then
            find /var/backups/shicecal -name "*.tar.gz" -mtime +${retention_days} -delete 2>/dev/null || true
            find /var/backups/shicecal -name "*.sql.gz" -mtime +${retention_days} -delete 2>/dev/null || true
            find /var/backups/shicecal -name "*.json" -mtime +${retention_days} -delete 2>/dev/null || true
            find /var/backups/shicecal -name "env_*" -mtime +${retention_days} -delete 2>/dev/null || true
            
            echo "✅ 古いバックアップのクリーンアップ完了"
        fi
EOF
}

# メイン実行関数
main() {
    info "🔄 Shise-Cal ロールバック開始"
    info "環境: $ENVIRONMENT"
    info "ログファイル: $LOG_FILE"
    
    # ヘルプ表示
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    # バックアップ一覧表示
    if [ "$1" = "--list" ] || [ "$1" = "-l" ]; then
        list_available_backups
        exit 0
    fi
    
    # バックアップIDの決定
    if [ -z "$BACKUP_ID" ]; then
        info "バックアップIDが指定されていません。最新バックアップを検索します..."
        BACKUP_ID=$(get_latest_backup)
        
        if [ -z "$BACKUP_ID" ]; then
            error "利用可能なバックアップが見つかりません"
            exit 1
        fi
        
        info "最新バックアップを使用します: $BACKUP_ID"
    fi
    
    # 確認プロンプト (強制実行でない場合)
    if [ "$FORCE_ROLLBACK" != "true" ] && [ "$DRY_RUN" != "true" ]; then
        echo ""
        warn "⚠️  ロールバックを実行しようとしています"
        warn "環境: $ENVIRONMENT"
        warn "バックアップID: $BACKUP_ID"
        echo ""
        read -p "続行しますか? (yes/no): " -r
        if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
            info "ロールバックがキャンセルされました"
            exit 0
        fi
    fi
    
    # 開始通知
    send_notification "info" "ロールバックを開始しました" "$BACKUP_ID"
    
    # ロールバック実行
    validate_backup "$BACKUP_ID" || {
        send_notification "error" "バックアップ検証に失敗しました" "$BACKUP_ID"
        exit 1
    }
    
    # ロールバック前の現在状態をバックアップ
    local pre_rollback_backup=""
    if [ "$DRY_RUN" != "true" ]; then
        pre_rollback_backup=$(create_pre_rollback_backup)
    fi
    
    execute_rollback "$BACKUP_ID" || {
        send_notification "error" "ロールバック実行に失敗しました" "$BACKUP_ID"
        exit 1
    }
    
    # ロールバック後検証
    if [ "$DRY_RUN" != "true" ]; then
        verify_rollback "$BACKUP_ID" || {
            send_notification "error" "ロールバック後検証に失敗しました" "$BACKUP_ID"
            exit 1
        }
    fi
    
    # 古いバックアップのクリーンアップ
    cleanup_old_backups
    
    # 成功通知
    send_notification "success" "ロールバックが正常に完了しました" "$BACKUP_ID"
    
    success "🎉 ロールバック完了!"
    success "🌐 アプリケーション URL: $HEALTH_CHECK_URL"
    
    if [ -n "$pre_rollback_backup" ]; then
        info "📝 ロールバック前の状態は以下のバックアップに保存されています: $pre_rollback_backup"
    fi
    
    return 0
}

# エラーハンドリング
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        error "ロールバックが失敗しました (終了コード: $exit_code)"
        error "ログファイル: $LOG_FILE"
        
        if [ -n "$BACKUP_ID" ]; then
            send_notification "error" "ロールバックが失敗しました" "$BACKUP_ID"
        fi
    fi
}

trap cleanup EXIT

# スクリプト実行
main "$@"
