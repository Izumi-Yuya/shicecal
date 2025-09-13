#!/bin/bash

# 本番環境健全性チェックスクリプト
# 使用方法: ./scripts/production-health-check.sh

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

info "🏥 本番環境健全性チェック開始"

# 1. HTTP応答チェック
info "📡 HTTP応答チェック..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$APP_URL" || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    success "✅ HTTP応答正常 (Status: $HTTP_STATUS)"
else
    error "❌ HTTP応答異常 (Status: $HTTP_STATUS)"
fi

# 2. SSL証明書チェック（HTTPS使用時）
if [[ $APP_URL == https* ]]; then
    info "🔒 SSL証明書チェック..."
    if curl -s --connect-timeout 10 "$APP_URL" > /dev/null; then
        success "✅ SSL証明書正常"
    else
        warn "⚠️ SSL証明書に問題があります"
    fi
fi

# 3. サーバーリソースチェック
info "💻 サーバーリソースチェック..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$AWS_USER@$AWS_HOST" << 'EOF'
echo "📊 システムリソース状況:"

# CPU使用率
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
echo "CPU使用率: ${CPU_USAGE}%"

# メモリ使用率
MEMORY_INFO=$(free | grep Mem)
TOTAL_MEM=$(echo $MEMORY_INFO | awk '{print $2}')
USED_MEM=$(echo $MEMORY_INFO | awk '{print $3}')
MEMORY_USAGE=$(echo "scale=1; $USED_MEM * 100 / $TOTAL_MEM" | bc)
echo "メモリ使用率: ${MEMORY_USAGE}%"

# ディスク使用率
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | cut -d'%' -f1)
echo "ディスク使用率: ${DISK_USAGE}%"

# 警告レベルチェック
if [ "${CPU_USAGE%.*}" -gt 80 ]; then
    echo "⚠️ CPU使用率が高いです (${CPU_USAGE}%)"
fi

if [ "${MEMORY_USAGE%.*}" -gt 80 ]; then
    echo "⚠️ メモリ使用率が高いです (${MEMORY_USAGE}%)"
fi

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "⚠️ ディスク使用率が高いです (${DISK_USAGE}%)"
fi
EOF

# 4. アプリケーション固有のチェック
info "🔍 アプリケーション状態チェック..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << EOF
cd "$DEPLOY_DIR"

echo "📋 アプリケーション状態:"

# Laravel アプリケーション状態
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Laravel アプリケーション: 正常"
    LARAVEL_VERSION=\$(php artisan --version)
    echo "   バージョン: \$LARAVEL_VERSION"
else
    echo "❌ Laravel アプリケーション: 異常"
fi

# データベース接続チェック
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
    echo "✅ データベース接続: 正常"
else
    echo "❌ データベース接続: 異常"
fi

# ビルドファイル確認
if [ -f "public/build/manifest.json" ]; then
    BUILD_FILES=\$(find public/build -type f | wc -l)
    echo "✅ フロントエンドビルド: 正常 (\${BUILD_FILES}ファイル)"
else
    echo "❌ フロントエンドビルド: 異常"
fi

# ログファイル確認
if [ -f "storage/logs/laravel.log" ]; then
    ERROR_COUNT=\$(grep -c "ERROR" storage/logs/laravel.log 2>/dev/null || echo "0")
    echo "📝 エラーログ: \${ERROR_COUNT}件のエラー"
    
    if [ "\$ERROR_COUNT" -gt 0 ]; then
        echo "⚠️ 最新のエラー (最後の3件):"
        tail -n 50 storage/logs/laravel.log | grep "ERROR" | tail -3
    fi
else
    echo "ℹ️ ログファイルが見つかりません"
fi
EOF

# 5. サービス状態チェック
info "🔧 サービス状態チェック..."
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << 'EOF'
echo "🔄 サービス状態:"

# Nginx状態
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx: 稼働中"
else
    echo "❌ Nginx: 停止中"
fi

# PHP-FPM状態
if systemctl is-active --quiet php-fpm; then
    echo "✅ PHP-FPM: 稼働中"
    
    # PHP-FPMプロセス数
    PHP_PROCESSES=$(ps aux | grep -c "[p]hp-fpm: pool www")
    echo "   プロセス数: ${PHP_PROCESSES}"
else
    echo "❌ PHP-FPM: 停止中"
fi

# ポート確認
echo "📡 リスニングポート:"
netstat -tlnp 2>/dev/null | grep -E ":80|:443|:22" | while read line; do
    echo "   $line"
done
EOF

# 6. パフォーマンステスト
info "⚡ パフォーマンステスト..."
RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}" "$APP_URL" || echo "0")
echo "応答時間: ${RESPONSE_TIME}秒"

if (( $(echo "$RESPONSE_TIME > 3.0" | bc -l) )); then
    warn "⚠️ 応答時間が遅いです (${RESPONSE_TIME}秒)"
elif (( $(echo "$RESPONSE_TIME > 1.0" | bc -l) )); then
    info "ℹ️ 応答時間は許容範囲内です (${RESPONSE_TIME}秒)"
else
    success "✅ 応答時間は良好です (${RESPONSE_TIME}秒)"
fi

# 7. セキュリティチェック
info "🔒 基本セキュリティチェック..."

# HTTPヘッダーチェック
HEADERS=$(curl -s -I "$APP_URL")
if echo "$HEADERS" | grep -q "X-Frame-Options"; then
    success "✅ X-Frame-Options ヘッダー設定済み"
else
    warn "⚠️ X-Frame-Options ヘッダーが設定されていません"
fi

if echo "$HEADERS" | grep -q "X-Content-Type-Options"; then
    success "✅ X-Content-Type-Options ヘッダー設定済み"
else
    warn "⚠️ X-Content-Type-Options ヘッダーが設定されていません"
fi

# 8. 総合評価
info "📊 健全性チェック完了"

# 結果サマリー
echo ""
echo "🎯 健全性チェック結果サマリー:"
echo "================================"
echo "HTTP応答: $([ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ] && echo "✅ 正常" || echo "❌ 異常")"
echo "応答時間: $([ $(echo "$RESPONSE_TIME < 3.0" | bc -l) -eq 1 ] && echo "✅ 良好" || echo "⚠️ 要改善")"
echo "詳細な状態は上記ログを確認してください"
echo ""
echo "🔗 アプリケーションURL: $APP_URL"
echo "📊 監視ダッシュボード: GitHub Actions"
echo "📝 ログ確認: ssh -i $SSH_KEY_FILE $AWS_USER@$AWS_HOST 'tail -f $DEPLOY_DIR/storage/logs/laravel.log'"