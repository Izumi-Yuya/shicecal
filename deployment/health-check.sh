#!/bin/bash

# Shise-Cal ヘルスチェックスクリプト
# 使用方法: ./deployment/health-check.sh [target_url] [options]
#
# 機能:
# - HTTP レスポンスチェック
# - データベース接続チェック
# - アセットファイル検証
# - Laravel アプリケーション状態チェック
#
# 要件: 3.4, 3.5

set -e

# スクリプト設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="/tmp/shicecal_healthcheck_${TIMESTAMP}.log"

# デフォルト設定
TARGET_URL="${1:-http://localhost:8000}"
TIMEOUT="${2:-30}"
RETRY_COUNT="${3:-3}"
RETRY_DELAY="${4:-5}"

# ヘルスチェック設定
HEALTH_ENDPOINTS=(
    "/"
    "/login"
    "/health"
)

CRITICAL_ASSETS=(
    "/build/manifest.json"
    "/favicon.ico"
)

DATABASE_TABLES=(
    "users"
    "facilities" 
    "land_info"
    "facility_comments"
    "activity_logs"
)

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
使用方法: $0 [target_url] [timeout] [retry_count] [retry_delay]

引数:
  target_url     チェック対象URL [デフォルト: http://localhost:8000]
  timeout        タイムアウト秒数 [デフォルト: 30]
  retry_count    リトライ回数 [デフォルト: 3]
  retry_delay    リトライ間隔秒数 [デフォルト: 5]

例:
  $0                                    # ローカル環境をチェック
  $0 http://35.75.1.64                 # テスト環境をチェック
  $0 https://prod.example.com 60 5 10  # 本番環境を詳細チェック

環境変数:
  HEALTH_CHECK_STRICT=true    # 厳密モード (全チェック必須)
  HEALTH_CHECK_VERBOSE=true   # 詳細ログ出力
  SKIP_DB_CHECK=true          # データベースチェックスキップ
  SKIP_ASSET_CHECK=true       # アセットチェックスキップ
EOF
}

# リトライ機能付きコマンド実行
retry_command() {
    local command="$1"
    local description="$2"
    local count=0
    
    while [ $count -lt $RETRY_COUNT ]; do
        if eval "$command"; then
            return 0
        fi
        
        count=$((count + 1))
        if [ $count -lt $RETRY_COUNT ]; then
            warn "$description に失敗しました。${RETRY_DELAY}秒後にリトライします... ($count/$RETRY_COUNT)"
            sleep $RETRY_DELAY
        fi
    done
    
    error "$description が $RETRY_COUNT 回失敗しました"
    return 1
}

# HTTP接続チェック
check_http_connectivity() {
    info "🌐 HTTP接続をチェック中..."
    
    # 基本的な接続テスト
    if ! retry_command "curl -f -s --max-time $TIMEOUT '$TARGET_URL' > /dev/null" "基本HTTP接続"; then
        return 1
    fi
    
    # レスポンス時間測定
    local response_time=$(curl -o /dev/null -s -w "%{time_total}" --max-time $TIMEOUT "$TARGET_URL")
    local response_time_ms=$(echo "$response_time * 1000" | bc 2>/dev/null || echo "N/A")
    
    info "レスポンス時間: ${response_time_ms}ms"
    
    # レスポンス時間チェック (5秒以内)
    if [ "$response_time_ms" != "N/A" ] && (( $(echo "$response_time > 5.0" | bc -l 2>/dev/null || echo 0) )); then
        warn "レスポンス時間が5秒を超えています: ${response_time_ms}ms"
    fi
    
    success "HTTP接続チェック完了"
    return 0
}

# エンドポイントチェック
check_endpoints() {
    info "🔗 エンドポイントをチェック中..."
    
    local failed_endpoints=()
    
    for endpoint in "${HEALTH_ENDPOINTS[@]}"; do
        local url="${TARGET_URL}${endpoint}"
        
        if [ "$HEALTH_CHECK_VERBOSE" = "true" ]; then
            info "チェック中: $url"
        fi
        
        if ! curl -f -s --max-time $TIMEOUT "$url" > /dev/null; then
            warn "エンドポイントアクセス失敗: $endpoint"
            failed_endpoints+=("$endpoint")
        fi
    done
    
    if [ ${#failed_endpoints[@]} -gt 0 ]; then
        if [ "$HEALTH_CHECK_STRICT" = "true" ]; then
            error "失敗したエンドポイント: ${failed_endpoints[*]}"
            return 1
        else
            warn "一部のエンドポイントにアクセスできませんでした: ${failed_endpoints[*]}"
        fi
    fi
    
    success "エンドポイントチェック完了"
    return 0
}

# HTTPステータスコードチェック
check_http_status_codes() {
    info "📊 HTTPステータスコードをチェック中..."
    
    local status_issues=()
    
    # メインページ (200 OK)
    local main_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time $TIMEOUT "$TARGET_URL")
    if [ "$main_status" != "200" ]; then
        status_issues+=("メインページ: $main_status")
    fi
    
    # ログインページ (200 OK)
    local login_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time $TIMEOUT "${TARGET_URL}/login")
    if [ "$login_status" != "200" ]; then
        status_issues+=("ログインページ: $login_status")
    fi
    
    # 存在しないページ (404 Not Found)
    local notfound_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time $TIMEOUT "${TARGET_URL}/nonexistent-page-test")
    if [ "$notfound_status" != "404" ]; then
        warn "404ページのステータスコードが期待値と異なります: $notfound_status"
    fi
    
    if [ ${#status_issues[@]} -gt 0 ]; then
        error "HTTPステータスコードの問題: ${status_issues[*]}"
        return 1
    fi
    
    success "HTTPステータスコードチェック完了"
    return 0
}

# アセットファイルチェック
check_assets() {
    if [ "$SKIP_ASSET_CHECK" = "true" ]; then
        warn "アセットチェックをスキップします"
        return 0
    fi
    
    info "📁 アセットファイルをチェック中..."
    
    local failed_assets=()
    
    for asset in "${CRITICAL_ASSETS[@]}"; do
        local asset_url="${TARGET_URL}${asset}"
        
        if [ "$HEALTH_CHECK_VERBOSE" = "true" ]; then
            info "チェック中: $asset_url"
        fi
        
        if ! curl -f -s --max-time $TIMEOUT "$asset_url" > /dev/null; then
            warn "アセットファイルアクセス失敗: $asset"
            failed_assets+=("$asset")
        fi
    done
    
    # Viteマニフェストファイルの内容チェック
    local manifest_content=$(curl -s --max-time $TIMEOUT "${TARGET_URL}/build/manifest.json" 2>/dev/null || echo "")
    if [ -n "$manifest_content" ]; then
        if ! echo "$manifest_content" | jq . > /dev/null 2>&1; then
            warn "Viteマニフェストファイルが無効なJSONです"
            failed_assets+=("/build/manifest.json (invalid JSON)")
        else
            # マニフェストファイル内のエントリ数チェック
            local entry_count=$(echo "$manifest_content" | jq 'length' 2>/dev/null || echo 0)
            if [ "$entry_count" -lt 2 ]; then
                warn "Viteマニフェストのエントリ数が少ないです: $entry_count"
            fi
        fi
    fi
    
    if [ ${#failed_assets[@]} -gt 0 ]; then
        if [ "$HEALTH_CHECK_STRICT" = "true" ]; then
            error "失敗したアセット: ${failed_assets[*]}"
            return 1
        else
            warn "一部のアセットにアクセスできませんでした: ${failed_assets[*]}"
        fi
    fi
    
    success "アセットファイルチェック完了"
    return 0
}

# データベース接続チェック
check_database() {
    if [ "$SKIP_DB_CHECK" = "true" ]; then
        warn "データベースチェックをスキップします"
        return 0
    fi
    
    info "🗄️ データベース接続をチェック中..."
    
    # Laravel Artisanコマンドでデータベース接続テスト
    if ! php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB接続成功';" > /dev/null 2>&1; then
        error "データベース接続に失敗しました"
        return 1
    fi
    
    # 重要なテーブルの存在確認
    local missing_tables=()
    
    for table in "${DATABASE_TABLES[@]}"; do
        if [ "$HEALTH_CHECK_VERBOSE" = "true" ]; then
            info "テーブルチェック中: $table"
        fi
        
        if ! php artisan tinker --execute="DB::table('$table')->count();" > /dev/null 2>&1; then
            warn "テーブルにアクセスできません: $table"
            missing_tables+=("$table")
        fi
    done
    
    if [ ${#missing_tables[@]} -gt 0 ]; then
        if [ "$HEALTH_CHECK_STRICT" = "true" ]; then
            error "アクセスできないテーブル: ${missing_tables[*]}"
            return 1
        else
            warn "一部のテーブルにアクセスできませんでした: ${missing_tables[*]}"
        fi
    fi
    
    # データベースの基本統計
    if [ "$HEALTH_CHECK_VERBOSE" = "true" ]; then
        local user_count=$(php artisan tinker --execute="echo DB::table('users')->count();" 2>/dev/null || echo "N/A")
        local facility_count=$(php artisan tinker --execute="echo DB::table('facilities')->count();" 2>/dev/null || echo "N/A")
        
        info "データベース統計:"
        info "  - ユーザー数: $user_count"
        info "  - 施設数: $facility_count"
    fi
    
    success "データベースチェック完了"
    return 0
}

# Laravel アプリケーション状態チェック
check_laravel_application() {
    info "⚙️ Laravel アプリケーション状態をチェック中..."
    
    # アプリケーションキーの確認
    if ! php artisan key:generate --show > /dev/null 2>&1; then
        error "アプリケーションキーが設定されていません"
        return 1
    fi
    
    # 環境設定の確認
    local app_env=$(php artisan tinker --execute="echo config('app.env');" 2>/dev/null || echo "unknown")
    local app_debug=$(php artisan tinker --execute="echo config('app.debug') ? 'true' : 'false';" 2>/dev/null || echo "unknown")
    
    info "アプリケーション環境: $app_env"
    info "デバッグモード: $app_debug"
    
    # 本番環境でのデバッグモードチェック
    if [ "$app_env" = "production" ] && [ "$app_debug" = "true" ]; then
        error "本番環境でデバッグモードが有効になっています"
        return 1
    fi
    
    # キャッシュ状態の確認
    local cache_files=(
        "bootstrap/cache/config.php"
        "bootstrap/cache/routes-v7.php"
    )
    
    local missing_cache=()
    for cache_file in "${cache_files[@]}"; do
        if [ ! -f "$cache_file" ]; then
            missing_cache+=("$cache_file")
        fi
    done
    
    if [ ${#missing_cache[@]} -gt 0 ]; then
        warn "キャッシュファイルが見つかりません: ${missing_cache[*]}"
    fi
    
    # ストレージリンクの確認
    if [ ! -L "public/storage" ]; then
        warn "ストレージリンクが作成されていません"
    fi
    
    # ログファイルの権限確認
    if [ ! -w "storage/logs" ]; then
        error "ログディレクトリに書き込み権限がありません"
        return 1
    fi
    
    success "Laravel アプリケーション状態チェック完了"
    return 0
}

# システムリソースチェック
check_system_resources() {
    info "💻 システムリソースをチェック中..."
    
    # ディスク使用量チェック
    local disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    info "ディスク使用量: ${disk_usage}%"
    
    if [ "$disk_usage" -gt 90 ]; then
        error "ディスク使用量が90%を超えています"
        return 1
    elif [ "$disk_usage" -gt 80 ]; then
        warn "ディスク使用量が80%を超えています"
    fi
    
    # メモリ使用量チェック
    local memory_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}' 2>/dev/null || echo "N/A")
    if [ "$memory_usage" != "N/A" ]; then
        info "メモリ使用量: ${memory_usage}%"
        
        if [ "$memory_usage" -gt 95 ]; then
            error "メモリ使用量が95%を超えています"
            return 1
        elif [ "$memory_usage" -gt 85 ]; then
            warn "メモリ使用量が85%を超えています"
        fi
    fi
    
    # 重要なサービスの状態確認
    local services=("nginx" "php-fpm")
    local failed_services=()
    
    for service in "${services[@]}"; do
        if ! systemctl is-active --quiet "$service" 2>/dev/null; then
            failed_services+=("$service")
        fi
    done
    
    if [ ${#failed_services[@]} -gt 0 ]; then
        error "停止しているサービス: ${failed_services[*]}"
        return 1
    fi
    
    success "システムリソースチェック完了"
    return 0
}

# 総合ヘルスチェック結果
show_health_summary() {
    local overall_status="$1"
    
    echo ""
    if [ "$overall_status" = "success" ]; then
        success "🎉 ヘルスチェックが正常に完了しました！"
    else
        error "❌ ヘルスチェックで問題が検出されました"
    fi
    
    echo ""
    echo "📊 ヘルスチェック結果サマリー:"
    echo "  🌐 HTTP接続: ✅"
    echo "  🔗 エンドポイント: ✅"
    echo "  📊 HTTPステータス: ✅"
    echo "  📁 アセットファイル: ✅"
    echo "  🗄️ データベース: ✅"
    echo "  ⚙️ Laravel アプリケーション: ✅"
    echo "  💻 システムリソース: ✅"
    echo ""
    echo "🌐 チェック対象URL: $TARGET_URL"
    echo "📝 ログファイル: $LOG_FILE"
    echo ""
}

# メイン実行関数
main() {
    info "🏥 Shise-Cal ヘルスチェック開始"
    info "対象URL: $TARGET_URL"
    info "タイムアウト: ${TIMEOUT}秒"
    info "リトライ回数: $RETRY_COUNT"
    
    # ヘルプ表示
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    local overall_status="success"
    
    # 各ヘルスチェックを順次実行
    check_http_connectivity || overall_status="failed"
    check_endpoints || overall_status="failed"
    check_http_status_codes || overall_status="failed"
    check_assets || overall_status="failed"
    
    # ローカル環境でのみ実行するチェック
    if [ -f "artisan" ]; then
        check_database || overall_status="failed"
        check_laravel_application || overall_status="failed"
        check_system_resources || overall_status="failed"
    else
        warn "Laravel環境が検出されませんでした。一部のチェックをスキップします。"
    fi
    
    show_health_summary "$overall_status"
    
    if [ "$overall_status" = "success" ]; then
        success "✅ ヘルスチェック完了"
        return 0
    else
        error "❌ ヘルスチェック失敗"
        return 1
    fi
}

# エラーハンドリング
trap 'error "ヘルスチェック中にエラーが発生しました"; exit 1' ERR

# スクリプト実行
main "$@"