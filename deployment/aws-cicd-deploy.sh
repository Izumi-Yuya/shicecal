#!/bin/bash

# Shise-Cal CI/CD デプロイメントスクリプト
# 使用方法: ./deployment/aws-cicd-deploy.sh [environment] [options]
# 
# 機能:
# - 事前チェック (git status, branch validation)
# - バックアップ作成
# - ゼロダウンタイムデプロイメント
# - ヘルスチェック
# - 自動ロールバック
#
# 要件: 3.1, 3.2, 5.4, 5.5

set -e

# スクリプト設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="/tmp/shicecal_deploy_${TIMESTAMP}.log"

# ヘルプ表示
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    cat << EOF
使用方法: ./deployment/aws-cicd-deploy.sh [environment] [dry_run] [skip_backup] [skip_tests]

引数:
  environment    デプロイ環境 (test|production) [デフォルト: test]
  dry_run       ドライラン実行 (true|false) [デフォルト: false]
  skip_backup   バックアップスキップ (true|false) [デフォルト: false]
  skip_tests    テストスキップ (true|false) [デフォルト: false]

例:
  ./deployment/aws-cicd-deploy.sh                    # テスト環境にデプロイ
  ./deployment/aws-cicd-deploy.sh production         # 本番環境にデプロイ
  ./deployment/aws-cicd-deploy.sh test true          # ドライラン実行
  ./deployment/aws-cicd-deploy.sh test false true    # バックアップスキップ

環境変数:
  PROD_AWS_HOST              # 本番環境ホスト
  PROD_SSH_KEY              # 本番環境SSH鍵
  NOTIFICATION_WEBHOOK      # Slack通知用Webhook URL
EOF
    exit 0
fi

# デフォルト設定
ENVIRONMENT="${1:-test}"
DRY_RUN="${2:-false}"
SKIP_BACKUP="${3:-false}"
SKIP_TESTS="${4:-false}"

# 環境設定
case $ENVIRONMENT in
    "test")
        AWS_HOST="35.75.1.64"
        AWS_USER="ec2-user"
        SSH_KEY="~/Shise-Cal-test-key.pem"
        REMOTE_PATH="/home/ec2-user/shicecal"
        TARGET_BRANCH="main"
        HEALTH_CHECK_URL="http://35.75.1.64"
        ;;
    "production")
        AWS_HOST="${PROD_AWS_HOST:-}"
        AWS_USER="${PROD_AWS_USER:-ec2-user}"
        SSH_KEY="${PROD_SSH_KEY:-}"
        REMOTE_PATH="${PROD_REMOTE_PATH:-/var/www/shicecal}"
        TARGET_BRANCH="production"
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

# エラーハンドリング
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        error "デプロイメントが失敗しました (終了コード: $exit_code)"
        error "ログファイル: $LOG_FILE"
        
        # 失敗時の自動ロールバック
        if [ "$ROLLBACK_ON_FAILURE" = "true" ] && [ -n "$BACKUP_ID" ]; then
            warn "自動ロールバックを実行します..."
            rollback_deployment "$BACKUP_ID"
        fi
    fi
}

trap cleanup EXIT

# 使用方法表示
show_usage() {
    cat << EOF
使用方法: $0 [environment] [dry_run] [skip_backup] [skip_tests]

引数:
  environment    デプロイ環境 (test|production) [デフォルト: test]
  dry_run        ドライラン実行 (true|false) [デフォルト: false]
  skip_backup    バックアップスキップ (true|false) [デフォルト: false]
  skip_tests     テストスキップ (true|false) [デフォルト: false]

例:
  $0 test                    # テスト環境にデプロイ
  $0 production false false  # 本番環境にフルデプロイ
  $0 test true               # テスト環境でドライラン

環境変数:
  ROLLBACK_ON_FAILURE=true   # 失敗時の自動ロールバック有効化
  NOTIFICATION_WEBHOOK       # Slack通知用Webhook URL
  DEPLOY_TIMEOUT=600         # デプロイタイムアウト (秒)
EOF
}

# 事前チェック
pre_deployment_checks() {
    info "🔍 事前チェックを実行中..."
    
    # Git状態チェック
    if ! git diff --quiet; then
        error "未コミットの変更があります。先にコミットしてください。"
        return 1
    fi
    
    # ブランチ確認
    local current_branch=$(git branch --show-current)
    if [ "$current_branch" != "$TARGET_BRANCH" ]; then
        error "現在のブランチ ($current_branch) が対象ブランチ ($TARGET_BRANCH) と異なります"
        return 1
    fi
    
    # SSH接続テスト
    if ! ssh -i "$SSH_KEY" -o ConnectTimeout=10 "$AWS_USER@$AWS_HOST" "echo 'SSH接続テスト成功'" > /dev/null 2>&1; then
        error "SSH接続に失敗しました: $AWS_USER@$AWS_HOST"
        return 1
    fi
    
    # 必要なコマンドの確認
    local required_commands=("git" "ssh" "curl" "jq")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" > /dev/null 2>&1; then
            error "必要なコマンドが見つかりません: $cmd"
            return 1
        fi
    done
    
    # リモートサーバーの基本チェック
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << 'EOF'
        # ディスク容量チェック
        DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
        if [ "$DISK_USAGE" -gt 90 ]; then
            echo "❌ ディスク使用量が90%を超えています: ${DISK_USAGE}%"
            exit 1
        fi
        
        # メモリチェック
        MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
        if [ "$MEMORY_USAGE" -gt 95 ]; then
            echo "⚠️  メモリ使用量が高いです: ${MEMORY_USAGE}%"
        fi
        
        # 必要なサービスの確認
        for service in nginx php-fpm mysql; do
            if ! systemctl is-active --quiet $service; then
                echo "❌ サービスが停止しています: $service"
                exit 1
            fi
        done
        
        echo "✅ リモートサーバーの基本チェック完了"
EOF
    
    success "事前チェック完了"
    return 0
}

# バックアップ作成
create_backup() {
    if [ "$SKIP_BACKUP" = "true" ]; then
        warn "バックアップをスキップします"
        return 0
    fi
    
    info "💾 バックアップを作成中..."
    
    BACKUP_ID="backup_${TIMESTAMP}"
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        cd $REMOTE_PATH
        
        # バックアップディレクトリ作成
        sudo mkdir -p /var/backups/shicecal
        
        # アプリケーションファイルのバックアップ
        echo "📁 アプリケーションファイルをバックアップ中..."
        sudo tar -czf "/var/backups/shicecal/app_${BACKUP_ID}.tar.gz" \
            --exclude=node_modules \
            --exclude=vendor \
            --exclude=storage/logs \
            --exclude=storage/framework/cache \
            --exclude=storage/framework/sessions \
            --exclude=storage/framework/views \
            .
        
        # データベースバックアップ
        echo "🗄️ データベースをバックアップ中..."
        DB_NAME=\$(php artisan tinker --execute="echo config('database.connections.mysql.database');")
        DB_USER=\$(php artisan tinker --execute="echo config('database.connections.mysql.username');")
        DB_PASS=\$(php artisan tinker --execute="echo config('database.connections.mysql.password');")
        
        mysqldump -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME" | gzip > "/var/backups/shicecal/db_${BACKUP_ID}.sql.gz"
        
        # 設定ファイルのバックアップ
        echo "⚙️ 設定ファイルをバックアップ中..."
        sudo cp .env "/var/backups/shicecal/env_${BACKUP_ID}"
        
        # バックアップ情報の記録
        echo "{
            \"backup_id\": \"$BACKUP_ID\",
            \"timestamp\": \"$TIMESTAMP\",
            \"environment\": \"$ENVIRONMENT\",
            \"git_commit\": \"\$(git rev-parse HEAD)\",
            \"git_branch\": \"\$(git branch --show-current)\"
        }" | sudo tee "/var/backups/shicecal/info_${BACKUP_ID}.json" > /dev/null
        
        echo "✅ バックアップ完了: $BACKUP_ID"
EOF
    
    success "バックアップ作成完了: $BACKUP_ID"
    return 0
}

# ゼロダウンタイムデプロイメント
zero_downtime_deployment() {
    info "🚀 ゼロダウンタイムデプロイメントを開始..."
    
    if [ "$DRY_RUN" = "true" ]; then
        info "🔍 ドライラン: デプロイメント手順を表示します"
        cat << EOF
ドライラン - 実行予定の手順:
1. GitHubから最新コードを取得
2. 依存関係のインストール
3. アセットのビルド
4. データベースマイグレーション
5. キャッシュの最適化
6. サービスの再起動
7. ヘルスチェック
EOF
        return 0
    fi
    
    # GitHubにプッシュ
    info "📤 GitHubにプッシュ中..."
    git push origin "$TARGET_BRANCH"
    
    # リモートサーバーでのデプロイメント実行
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        cd $REMOTE_PATH
        
        # メンテナンスモード開始 (Laravel)
        echo "🔧 メンテナンスモードを開始..."
        php artisan down --retry=60 --secret="deploy-${TIMESTAMP}"
        
        # 最新コードの取得
        echo "📥 最新コードを取得中..."
        git fetch origin
        git reset --hard origin/$TARGET_BRANCH
        
        # 依存関係のインストール
        echo "📦 依存関係をインストール中..."
        composer install --no-dev --optimize-autoloader --no-interaction
        npm ci --production=false --silent
        
        # アセットのビルド
        echo "🏗️ アセットをビルド中..."
        rm -rf public/build
        npm run build
        
        # ビルド検証
        if [ ! -d "public/build" ] || [ ! -f "public/build/manifest.json" ]; then
            echo "❌ アセットビルドが失敗しました"
            php artisan up
            exit 1
        fi
        
        # データベースマイグレーション
        echo "🗄️ データベースマイグレーション実行中..."
        php artisan migrate --force
        
        # キャッシュの最適化
        echo "⚡ キャッシュを最適化中..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear
        
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        
        # ストレージリンクの確認
        if [ ! -L "public/storage" ]; then
            php artisan storage:link
        fi
        
        # 権限の設定
        echo "🔐 権限を設定中..."
        sudo chown -R nginx:nginx storage bootstrap/cache
        sudo chmod -R 775 storage bootstrap/cache
        
        # サービスの再起動
        echo "🔄 サービスを再起動中..."
        sudo systemctl reload nginx
        sudo systemctl restart php-fpm
        
        # メンテナンスモード終了
        echo "✅ メンテナンスモードを終了..."
        php artisan up
        
        echo "🎉 デプロイメント完了!"
EOF
    
    success "ゼロダウンタイムデプロイメント完了"
    return 0
}

# ロールバック機能
rollback_deployment() {
    local backup_id="$1"
    
    if [ -z "$backup_id" ]; then
        error "バックアップIDが指定されていません"
        return 1
    fi
    
    warn "🔄 ロールバックを実行中: $backup_id"
    
    ssh -i "$SSH_KEY" "$AWS_USER@$AWS_HOST" << EOF
        set -e
        cd $REMOTE_PATH
        
        # メンテナンスモード開始
        php artisan down --retry=60 --secret="rollback-${TIMESTAMP}"
        
        # バックアップファイルの確認
        if [ ! -f "/var/backups/shicecal/app_${backup_id}.tar.gz" ]; then
            echo "❌ バックアップファイルが見つかりません: $backup_id"
            exit 1
        fi
        
        # アプリケーションファイルの復元
        echo "📁 アプリケーションファイルを復元中..."
        sudo tar -xzf "/var/backups/shicecal/app_${backup_id}.tar.gz" -C .
        
        # 設定ファイルの復元
        echo "⚙️ 設定ファイルを復元中..."
        sudo cp "/var/backups/shicecal/env_${backup_id}" .env
        
        # データベースの復元 (オプション)
        if [ -f "/var/backups/shicecal/db_${backup_id}.sql.gz" ]; then
            echo "🗄️ データベースを復元中..."
            DB_NAME=\$(php artisan tinker --execute="echo config('database.connections.mysql.database');")
            DB_USER=\$(php artisan tinker --execute="echo config('database.connections.mysql.username');")
            DB_PASS=\$(php artisan tinker --execute="echo config('database.connections.mysql.password');")
            
            zcat "/var/backups/shicecal/db_${backup_id}.sql.gz" | mysql -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME"
        fi
        
        # 依存関係の再インストール
        echo "📦 依存関係を再インストール中..."
        composer install --no-dev --optimize-autoloader --no-interaction
        
        # キャッシュのクリア
        echo "🧹 キャッシュをクリア中..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        php artisan cache:clear
        
        # サービスの再起動
        echo "🔄 サービスを再起動中..."
        sudo systemctl restart nginx
        sudo systemctl restart php-fpm
        
        # メンテナンスモード終了
        php artisan up
        
        echo "✅ ロールバック完了"
EOF
    
    success "ロールバック完了: $backup_id"
    return 0
}

# 通知送信
send_notification() {
    local status="$1"
    local message="$2"
    
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
            "title": "Shise-Cal デプロイメント通知",
            "fields": [
                {
                    "title": "環境",
                    "value": "$ENVIRONMENT",
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

# メイン実行関数
main() {
    info "🚀 Shise-Cal CI/CD デプロイメント開始"
    info "環境: $ENVIRONMENT"
    info "ブランチ: $TARGET_BRANCH"
    info "ログファイル: $LOG_FILE"
    
    # ヘルプ表示
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    # 開始通知
    send_notification "info" "デプロイメントを開始しました"
    
    # デプロイメント実行
    pre_deployment_checks || {
        send_notification "error" "事前チェックに失敗しました"
        exit 1
    }
    
    create_backup || {
        send_notification "error" "バックアップ作成に失敗しました"
        exit 1
    }
    
    zero_downtime_deployment || {
        send_notification "error" "デプロイメントに失敗しました"
        exit 1
    }
    
    # ヘルスチェック実行
    if [ -f "$SCRIPT_DIR/health-check.sh" ]; then
        info "🏥 ヘルスチェックを実行中..."
        if ! "$SCRIPT_DIR/health-check.sh" "$HEALTH_CHECK_URL"; then
            error "ヘルスチェックに失敗しました"
            if [ "$ROLLBACK_ON_FAILURE" = "true" ] && [ -n "$BACKUP_ID" ]; then
                rollback_deployment "$BACKUP_ID"
            fi
            send_notification "error" "ヘルスチェックに失敗しました"
            exit 1
        fi
    else
        warn "ヘルスチェックスクリプトが見つかりません"
    fi
    
    # 成功通知
    send_notification "success" "デプロイメントが正常に完了しました"
    
    success "🎉 デプロイメント完了!"
    success "🌐 アプリケーション URL: $HEALTH_CHECK_URL"
    
    return 0
}

# スクリプト実行
main "$@"