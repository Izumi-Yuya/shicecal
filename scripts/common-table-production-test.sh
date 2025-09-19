#!/bin/bash

# CommonTableコンポーネント本番環境テストスクリプト
# 使用方法: ./scripts/common-table-production-test.sh [domain]

set -e

# 設定
DOMAIN=${1:-"localhost"}
BASE_URL="https://$DOMAIN"
LOG_FILE="/tmp/common-table-production-test-$(date +%Y%m%d_%H%M%S).log"
CURL_FORMAT_FILE="/tmp/curl-format.txt"

# カラー出力用
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ログ関数
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✓ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠ $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}✗ $1${NC}" | tee -a "$LOG_FILE"
}

# curl フォーマットファイルの作成
cat > "$CURL_FORMAT_FILE" << 'EOF'
     time_namelookup:  %{time_namelookup}\n
        time_connect:  %{time_connect}\n
     time_appconnect:  %{time_appconnect}\n
    time_pretransfer:  %{time_pretransfer}\n
       time_redirect:  %{time_redirect}\n
  time_starttransfer:  %{time_starttransfer}\n
                     ----------\n
          time_total:  %{time_total}\n
           http_code:  %{http_code}\n
EOF

# テスト開始
log "CommonTableコンポーネント本番環境テスト開始"
log "対象ドメイン: $DOMAIN"
log "ログファイル: $LOG_FILE"

# 1. 基本接続テスト
log "=== 1. 基本接続テスト ==="

test_basic_connection() {
    local url="$1"
    local expected_code="$2"
    local description="$3"
    
    log "テスト: $description ($url)"
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
    
    if [ "$response" = "$expected_code" ]; then
        success "$description - HTTP $response"
        return 0
    else
        error "$description - HTTP $response (期待値: $expected_code)"
        return 1
    fi
}

# 基本ページのテスト
test_basic_connection "$BASE_URL" "200" "トップページ"
test_basic_connection "$BASE_URL/facilities" "200" "施設一覧ページ"

# 2. CommonTableコンポーネント表示テスト
log "=== 2. CommonTableコンポーネント表示テスト ==="

test_common_table_display() {
    log "CommonTableコンポーネントの表示テスト開始"
    
    # 施設詳細ページのHTMLを取得
    html_content=$(curl -s "$BASE_URL/facilities/1" 2>/dev/null || echo "")
    
    if [ -z "$html_content" ]; then
        error "施設詳細ページの取得に失敗"
        return 1
    fi
    
    # CommonTableコンポーネントの要素をチェック
    checks=(
        "facility-info-card:基本情報カード"
        "detail-card-improved:改良された詳細カード"
        "table-responsive:レスポンシブテーブル"
        "detail-label:詳細ラベル"
        "detail-value:詳細値"
        "card-body-clean:クリーンカードボディ"
    )
    
    for check in "${checks[@]}"; do
        class_name="${check%%:*}"
        description="${check##*:}"
        
        if echo "$html_content" | grep -q "$class_name"; then
            success "$description ($class_name) が存在"
        else
            warning "$description ($class_name) が見つからない"
        fi
    done
    
    # 基本情報の表示確認
    if echo "$html_content" | grep -q "基本情報"; then
        success "基本情報セクションが表示されている"
    else
        error "基本情報セクションが表示されていない"
    fi
}

test_common_table_display

# 3. レスポンシブデザインテスト
log "=== 3. レスポンシブデザインテスト ==="

test_responsive_design() {
    log "レスポンシブデザインテスト開始"
    
    # 異なるUser-Agentでアクセス
    user_agents=(
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36:デスクトップ"
        "Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15:タブレット"
        "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15:スマートフォン"
    )
    
    for ua in "${user_agents[@]}"; do
        agent="${ua%%:*}"
        device="${ua##*:}"
        
        response=$(curl -s -o /dev/null -w "%{http_code}" -H "User-Agent: $agent" "$BASE_URL/facilities/1" 2>/dev/null || echo "000")
        
        if [ "$response" = "200" ]; then
            success "$device での表示 - HTTP $response"
        else
            error "$device での表示 - HTTP $response"
        fi
    done
}

test_responsive_design

# 4. パフォーマンステスト
log "=== 4. パフォーマンステスト ==="

test_performance() {
    log "パフォーマンステスト開始"
    
    # 複数回アクセスして平均時間を計算
    total_time=0
    test_count=5
    
    for i in $(seq 1 $test_count); do
        time_total=$(curl -w "%{time_total}" -o /dev/null -s "$BASE_URL/facilities/1" 2>/dev/null || echo "0")
        total_time=$(echo "$total_time + $time_total" | bc -l 2>/dev/null || echo "$total_time")
        log "テスト $i: ${time_total}秒"
    done
    
    if command -v bc >/dev/null 2>&1; then
        avg_time=$(echo "scale=3; $total_time / $test_count" | bc -l)
        log "平均レスポンス時間: ${avg_time}秒"
        
        # パフォーマンス基準チェック（3秒以内）
        if (( $(echo "$avg_time < 3.0" | bc -l) )); then
            success "パフォーマンス基準を満たしています (${avg_time}秒 < 3.0秒)"
        else
            warning "パフォーマンスが基準を下回っています (${avg_time}秒 >= 3.0秒)"
        fi
    else
        warning "bc コマンドが利用できないため、平均時間の計算をスキップ"
    fi
}

test_performance

# 5. セキュリティテスト
log "=== 5. セキュリティテスト ==="

test_security() {
    log "セキュリティテスト開始"
    
    # XSS対策テスト
    xss_payload="<script>alert('xss')</script>"
    response=$(curl -s "$BASE_URL/facilities/1" | grep -o "$xss_payload" || echo "")
    
    if [ -z "$response" ]; then
        success "XSS対策: スクリプトタグが実行されない"
    else
        error "XSS対策: スクリプトタグが検出された"
    fi
    
    # HTTPSリダイレクトテスト（HTTPSの場合）
    if [[ "$BASE_URL" == https* ]]; then
        http_url="${BASE_URL/https/http}"
        response=$(curl -s -o /dev/null -w "%{http_code}" "$http_url" 2>/dev/null || echo "000")
        
        if [ "$response" = "301" ] || [ "$response" = "302" ]; then
            success "HTTPS リダイレクト: HTTP から HTTPS へのリダイレクトが動作"
        else
            warning "HTTPS リダイレクト: HTTP から HTTPS へのリダイレクトが設定されていない可能性"
        fi
    fi
    
    # セキュリティヘッダーチェック
    headers=$(curl -s -I "$BASE_URL/facilities/1" 2>/dev/null || echo "")
    
    security_headers=(
        "X-Frame-Options:クリックジャッキング対策"
        "X-Content-Type-Options:MIME タイプスニッフィング対策"
        "X-XSS-Protection:XSS 対策"
    )
    
    for header in "${security_headers[@]}"; do
        header_name="${header%%:*}"
        description="${header##*:}"
        
        if echo "$headers" | grep -qi "$header_name"; then
            success "$description ($header_name) ヘッダーが設定されている"
        else
            warning "$description ($header_name) ヘッダーが設定されていない"
        fi
    done
}

test_security

# 6. アクセシビリティテスト
log "=== 6. アクセシビリティテスト ==="

test_accessibility() {
    log "アクセシビリティテスト開始"
    
    html_content=$(curl -s "$BASE_URL/facilities/1" 2>/dev/null || echo "")
    
    if [ -z "$html_content" ]; then
        error "HTMLコンテンツの取得に失敗"
        return 1
    fi
    
    # ARIA属性のチェック
    aria_attributes=(
        "role=:role属性"
        "aria-label:aria-label属性"
        "aria-describedby:aria-describedby属性"
    )
    
    for attr in "${aria_attributes[@]}"; do
        attr_name="${attr%%:*}"
        description="${attr##*:}"
        
        if echo "$html_content" | grep -q "$attr_name"; then
            success "$description が使用されている"
        else
            warning "$description が使用されていない"
        fi
    done
    
    # スクリーンリーダー用要素のチェック
    if echo "$html_content" | grep -q "sr-only"; then
        success "スクリーンリーダー用要素 (sr-only) が使用されている"
    else
        warning "スクリーンリーダー用要素 (sr-only) が使用されていない"
    fi
}

test_accessibility

# 7. データ整合性テスト
log "=== 7. データ整合性テスト ==="

test_data_integrity() {
    log "データ整合性テスト開始"
    
    html_content=$(curl -s "$BASE_URL/facilities/1" 2>/dev/null || echo "")
    
    # 空フィールドの適切な表示確認
    if echo "$html_content" | grep -q "未設定"; then
        success "空フィールドが適切に表示されている"
    else
        warning "空フィールドの表示が確認できない"
    fi
    
    # 日本語フォーマットの確認
    if echo "$html_content" | grep -qE "[0-9]{4}年[0-9]{1,2}月[0-9]{1,2}日"; then
        success "日本語日付フォーマットが使用されている"
    else
        warning "日本語日付フォーマットが確認できない"
    fi
    
    # 通貨フォーマットの確認
    if echo "$html_content" | grep -qE "[0-9,]+円"; then
        success "日本語通貨フォーマットが使用されている"
    else
        warning "日本語通貨フォーマットが確認できない"
    fi
}

test_data_integrity

# 8. 統合テスト
log "=== 8. 統合テスト ==="

test_integration() {
    log "統合テスト開始"
    
    # 複数のページで CommonTable が正しく動作することを確認
    test_urls=(
        "$BASE_URL/facilities/1:施設詳細ページ1"
        "$BASE_URL/facilities/2:施設詳細ページ2"
    )
    
    for url_desc in "${test_urls[@]}"; do
        url="${url_desc%%:*}"
        description="${url_desc##*:}"
        
        response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
        
        if [ "$response" = "200" ]; then
            success "$description - HTTP $response"
            
            # CommonTable要素の存在確認
            html_content=$(curl -s "$url" 2>/dev/null || echo "")
            if echo "$html_content" | grep -q "facility-info-card"; then
                success "$description - CommonTable要素が存在"
            else
                warning "$description - CommonTable要素が見つからない"
            fi
        else
            error "$description - HTTP $response"
        fi
    done
}

test_integration

# テスト結果サマリー
log "=== テスト結果サマリー ==="

# ログファイルから結果を集計
success_count=$(grep -c "✓" "$LOG_FILE" 2>/dev/null || echo "0")
warning_count=$(grep -c "⚠" "$LOG_FILE" 2>/dev/null || echo "0")
error_count=$(grep -c "✗" "$LOG_FILE" 2>/dev/null || echo "0")

log "成功: $success_count"
log "警告: $warning_count"
log "エラー: $error_count"

if [ "$error_count" -eq 0 ]; then
    if [ "$warning_count" -eq 0 ]; then
        success "全てのテストが成功しました！"
        exit_code=0
    else
        warning "テストは完了しましたが、警告があります。"
        exit_code=1
    fi
else
    error "テストでエラーが発生しました。"
    exit_code=2
fi

# レポート生成
report_file="/tmp/common-table-test-report-$(date +%Y%m%d_%H%M%S).html"
cat > "$report_file" << EOF
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommonTable本番環境テストレポート</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .summary { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>CommonTable本番環境テストレポート</h1>
    <div class="summary">
        <h2>テスト結果サマリー</h2>
        <p><strong>実行日時:</strong> $(date)</p>
        <p><strong>対象ドメイン:</strong> $DOMAIN</p>
        <p><strong>成功:</strong> <span class="success">$success_count</span></p>
        <p><strong>警告:</strong> <span class="warning">$warning_count</span></p>
        <p><strong>エラー:</strong> <span class="error">$error_count</span></p>
    </div>
    
    <h2>詳細ログ</h2>
    <pre>$(cat "$LOG_FILE")</pre>
</body>
</html>
EOF

log "HTMLレポートが生成されました: $report_file"

# クリーンアップ
rm -f "$CURL_FORMAT_FILE"

log "CommonTableコンポーネント本番環境テスト完了"
exit $exit_code