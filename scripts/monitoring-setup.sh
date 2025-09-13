#!/bin/bash

# 監視システムセットアップスクリプト
# 使用方法: ./scripts/monitoring-setup.sh

set -e

# 設定ファイル読み込み
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/config.sh" ]; then
    source "$SCRIPT_DIR/config.sh"
fi

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# 設定（環境変数で上書き可能）
SSH_KEY_FILE="${SSH_KEY_PATH:-$HOME/Shise-Cal-test-key.pem}"
AWS_HOST="${AWS_HOST:-35.75.1.64}"
AWS_USER="${AWS_USERNAME:-ec2-user}"
APP_URL="${AWS_PROD_URL:-http://35.75.1.64}"
DEPLOY_DIR="${DEPLOY_DIR:-/home/ec2-user/shicecal}"

info "📊 監視システムセットアップ開始"

# リモートサーバーに監視スクリプトを配置
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << 'EOF'
# 監視ディレクトリ作成
mkdir -p /home/ec2-user/monitoring
cd /home/ec2-user/monitoring

# システム監視スクリプト作成
cat > system_monitor.sh << 'MONITOR_SCRIPT'
#!/bin/bash

# システム監視スクリプト
LOG_FILE="/home/ec2-user/monitoring/system_monitor.log"
ALERT_FILE="/home/ec2-user/monitoring/alerts.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# ログ関数
log() {
    echo "[$TIMESTAMP] $*" >> "$LOG_FILE"
}

alert() {
    echo "[$TIMESTAMP] ALERT: $*" >> "$ALERT_FILE"
    echo "[$TIMESTAMP] ALERT: $*" >> "$LOG_FILE"
}

# CPU使用率チェック
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
CPU_THRESHOLD=80

if (( $(echo "$CPU_USAGE > $CPU_THRESHOLD" | bc -l) )); then
    alert "CPU使用率が高いです: ${CPU_USAGE}%"
fi

# メモリ使用率チェック
MEMORY_INFO=$(free | grep Mem)
TOTAL_MEM=$(echo $MEMORY_INFO | awk '{print $2}')
USED_MEM=$(echo $MEMORY_INFO | awk '{print $3}')
MEMORY_USAGE=$(echo "scale=1; $USED_MEM * 100 / $TOTAL_MEM" | bc)
MEMORY_THRESHOLD=85

if (( $(echo "$MEMORY_USAGE > $MEMORY_THRESHOLD" | bc -l) )); then
    alert "メモリ使用率が高いです: ${MEMORY_USAGE}%"
fi

# ディスク使用率チェック
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | cut -d'%' -f1)
DISK_THRESHOLD=85

if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    alert "ディスク使用率が高いです: ${DISK_USAGE}%"
fi

# サービス状態チェック
if ! systemctl is-active --quiet nginx; then
    alert "Nginxが停止しています"
fi

if ! systemctl is-active --quiet php-fpm; then
    alert "PHP-FPMが停止しています"
fi

# アプリケーション応答チェック
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost" || echo "000")
if [ "$HTTP_STATUS" != "200" ] && [ "$HTTP_STATUS" != "302" ]; then
    alert "アプリケーションが応答しません (HTTP: $HTTP_STATUS)"
fi

# ログファイルサイズチェック
if [ -f "/home/ec2-user/shicecal/storage/logs/laravel.log" ]; then
    LOG_SIZE=$(du -m "/home/ec2-user/shicecal/storage/logs/laravel.log" | cut -f1)
    if [ "$LOG_SIZE" -gt 100 ]; then
        alert "Laravelログファイルが大きくなっています: ${LOG_SIZE}MB"
    fi
fi

# 正常状態のログ
log "システム監視完了 - CPU: ${CPU_USAGE}%, Memory: ${MEMORY_USAGE}%, Disk: ${DISK_USAGE}%, HTTP: $HTTP_STATUS"
MONITOR_SCRIPT

chmod +x system_monitor.sh

# アプリケーション監視スクリプト作成
cat > app_monitor.sh << 'APP_SCRIPT'
#!/bin/bash

# アプリケーション監視スクリプト
LOG_FILE="/home/ec2-user/monitoring/app_monitor.log"
ALERT_FILE="/home/ec2-user/monitoring/alerts.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
DEPLOY_DIR="/home/ec2-user/shicecal"

# ログ関数
log() {
    echo "[$TIMESTAMP] $*" >> "$LOG_FILE"
}

alert() {
    echo "[$TIMESTAMP] ALERT: $*" >> "$ALERT_FILE"
    echo "[$TIMESTAMP] ALERT: $*" >> "$LOG_FILE"
}

cd "$DEPLOY_DIR" || exit 1

# データベース接続チェック
if ! php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
    alert "データベース接続に失敗しました"
fi

# キューワーカーチェック（使用している場合）
if pgrep -f "artisan queue:work" > /dev/null; then
    log "キューワーカーが稼働中です"
else
    # キューを使用している場合のみアラート
    if grep -q "QUEUE_CONNECTION" .env && [ "$(grep QUEUE_CONNECTION .env | cut -d'=' -f2)" != "sync" ]; then
        alert "キューワーカーが停止しています"
    fi
fi

# エラーログチェック
if [ -f "storage/logs/laravel.log" ]; then
    # 過去5分間のエラーをチェック
    RECENT_ERRORS=$(find storage/logs/laravel.log -mmin -5 -exec grep -c "ERROR" {} \; 2>/dev/null || echo "0")
    if [ "$RECENT_ERRORS" -gt 5 ]; then
        alert "過去5分間に多数のエラーが発生しています: ${RECENT_ERRORS}件"
    fi
fi

# ストレージ容量チェック
STORAGE_SIZE=$(du -sm storage/ | cut -f1)
if [ "$STORAGE_SIZE" -gt 1000 ]; then
    alert "ストレージディレクトリが大きくなっています: ${STORAGE_SIZE}MB"
fi

log "アプリケーション監視完了"
APP_SCRIPT

chmod +x app_monitor.sh

# アラート通知スクリプト作成
cat > send_alerts.sh << 'ALERT_SCRIPT'
#!/bin/bash

# アラート通知スクリプト
ALERT_FILE="/home/ec2-user/monitoring/alerts.log"
SENT_ALERTS_FILE="/home/ec2-user/monitoring/sent_alerts.log"

if [ ! -f "$ALERT_FILE" ]; then
    exit 0
fi

# 新しいアラートがあるかチェック
if [ ! -f "$SENT_ALERTS_FILE" ]; then
    touch "$SENT_ALERTS_FILE"
fi

# 未送信のアラートを取得
NEW_ALERTS=$(comm -23 <(sort "$ALERT_FILE") <(sort "$SENT_ALERTS_FILE"))

if [ -n "$NEW_ALERTS" ]; then
    echo "🚨 新しいアラートが検出されました:"
    echo "$NEW_ALERTS"
    
    # GitHub Issue作成（GitHub CLIがある場合）
    if command -v gh &> /dev/null; then
        ALERT_TITLE="Production Alert - $(date '+%Y-%m-%d %H:%M')"
        ALERT_BODY="Production environment alerts detected:\n\n$NEW_ALERTS"
        
        # gh issue create --title "$ALERT_TITLE" --body "$ALERT_BODY" --label "alert,production" 2>/dev/null || true
    fi
    
    # 送信済みアラートファイルを更新
    cat "$ALERT_FILE" > "$SENT_ALERTS_FILE"
fi
ALERT_SCRIPT

chmod +x send_alerts.sh

echo "✅ 監視スクリプトを作成しました"
EOF

# Crontabに監視ジョブを追加
info "⏰ Crontabに監視ジョブを追加中..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << 'EOF'
# 既存のcrontabを取得
crontab -l > /tmp/current_cron 2>/dev/null || touch /tmp/current_cron

# 監視ジョブが既に存在するかチェック
if ! grep -q "system_monitor.sh" /tmp/current_cron; then
    echo "# システム監視 (5分間隔)" >> /tmp/current_cron
    echo "*/5 * * * * /home/ec2-user/monitoring/system_monitor.sh" >> /tmp/current_cron
fi

if ! grep -q "app_monitor.sh" /tmp/current_cron; then
    echo "# アプリケーション監視 (10分間隔)" >> /tmp/current_cron
    echo "*/10 * * * * /home/ec2-user/monitoring/app_monitor.sh" >> /tmp/current_cron
fi

if ! grep -q "send_alerts.sh" /tmp/current_cron; then
    echo "# アラート通知 (15分間隔)" >> /tmp/current_cron
    echo "*/15 * * * * /home/ec2-user/monitoring/send_alerts.sh" >> /tmp/current_cron
fi

# 新しいcrontabを設定
crontab /tmp/current_cron
rm /tmp/current_cron

echo "✅ Crontabに監視ジョブを追加しました"
crontab -l
EOF

# ローカル監視スクリプト作成
info "💻 ローカル監視スクリプトを作成中..."
cat > scripts/check-production-status.sh << 'LOCAL_MONITOR'
#!/bin/bash

# ローカルから本番環境をチェックするスクリプト
# 使用方法: ./scripts/check-production-status.sh

set -e

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# 設定
SSH_KEY_FILE="$HOME/Shise-Cal-test-key.pem"
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
APP_URL="http://35.75.1.64"

info "📊 本番環境ステータスチェック"

# HTTP応答チェック
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL" || echo "000")
RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}" "$APP_URL" || echo "0")

echo "🌐 HTTP Status: $HTTP_STATUS"
echo "⏱️ Response Time: ${RESPONSE_TIME}s"

# リモートアラートチェック
info "🚨 アラート状況確認..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << 'EOF'
if [ -f "/home/ec2-user/monitoring/alerts.log" ]; then
    ALERT_COUNT=$(wc -l < /home/ec2-user/monitoring/alerts.log)
    if [ "$ALERT_COUNT" -gt 0 ]; then
        echo "⚠️ アラート数: $ALERT_COUNT"
        echo "最新のアラート (最後の3件):"
        tail -3 /home/ec2-user/monitoring/alerts.log
    else
        echo "✅ アラートはありません"
    fi
else
    echo "ℹ️ アラートファイルが見つかりません"
fi
EOF

# 最新のシステム状態
info "💻 最新のシステム状態..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << 'EOF'
if [ -f "/home/ec2-user/monitoring/system_monitor.log" ]; then
    echo "最新の監視ログ:"
    tail -1 /home/ec2-user/monitoring/system_monitor.log
else
    echo "ℹ️ システム監視ログが見つかりません"
fi
EOF

success "✅ ステータスチェック完了"
LOCAL_MONITOR

chmod +x scripts/check-production-status.sh

success "✅ 監視システムのセットアップが完了しました"

info "📋 セットアップ内容:"
echo "  - システム監視 (CPU, メモリ, ディスク, サービス)"
echo "  - アプリケーション監視 (DB接続, エラーログ)"
echo "  - アラート通知システム"
echo "  - Crontabによる自動実行"
echo ""
echo "🔧 使用方法:"
echo "  - ローカルチェック: ./scripts/check-production-status.sh"
echo "  - 健全性チェック: ./scripts/production-health-check.sh"
echo "  - バックアップ: ./scripts/backup-production.sh"
echo ""
echo "📊 監視ログ確認:"
echo "  ssh -i $SSH_KEY_FILE $AWS_USER@$AWS_HOST 'tail -f /home/ec2-user/monitoring/system_monitor.log'"