#!/bin/bash

# Shise-Cal (shise-cal/facility-management) デプロイメント検証スクリプト v2.0.0
# デプロイ後の動作確認を行う

set -e

echo "🔍 Shise-Cal デプロイメント検証を開始します..."

# 色付きログ関数
log_info() {
    echo -e "\033[32m[INFO]\033[0m $1"
}

log_warn() {
    echo -e "\033[33m[WARN]\033[0m $1"
}

log_error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
}

log_success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1"
}

# 設定
TARGET_HOST="${1:-localhost}"
TARGET_PORT="${2:-8000}"
TARGET_URL="http://$TARGET_HOST:$TARGET_PORT"

# アセットファイル検証
verify_assets() {
    log_info "アセットファイルを検証しています..."
    
    # public/buildディレクトリの存在確認
    if [ ! -d "public/build" ]; then
        log_error "public/buildディレクトリが存在しません"
        return 1
    fi
    
    # マニフェストファイルの存在確認
    if [ ! -f "public/build/manifest.json" ]; then
        log_error "Viteマニフェストファイルが存在しません"
        return 1
    fi
    
    # マニフェストファイルの妥当性確認
    if ! php -r "json_decode(file_get_contents('public/build/manifest.json')); if (json_last_error() !== JSON_ERROR_NONE) exit(1);"; then
        log_error "Viteマニフェストファイルが無効です"
        return 1
    fi
    
    # アセットファイル数の確認
    CSS_FILES=$(find public/build -name "*.css" | wc -l)
    JS_FILES=$(find public/build -name "*.js" | wc -l)
    TOTAL_FILES=$(find public/build -type f | wc -l)
    
    log_info "アセットファイル統計:"
    log_info "  - CSS ファイル: $CSS_FILES"
    log_info "  - JS ファイル: $JS_FILES"
    log_info "  - 総ファイル数: $TOTAL_FILES"
    
    # 最小限のファイル数チェック
    if [ "$CSS_FILES" -lt 5 ]; then
        log_error "CSSファイル数が不足しています (期待値: 5以上, 実際: $CSS_FILES)"
        return 1
    fi
    
    if [ "$JS_FILES" -lt 10 ]; then
        log_error "JSファイル数が不足しています (期待値: 10以上, 実際: $JS_FILES)"
        return 1
    fi
    
    log_success "アセットファイル検証完了"
    return 0
}

# データベース接続検証
verify_database() {
    log_info "データベース接続を検証しています..."
    
    # マイグレーション状態確認
    if ! php artisan migrate:status > /dev/null 2>&1; then
        log_error "データベースマイグレーション状態の確認に失敗しました"
        return 1
    fi
    
    # 基本テーブルの存在確認
    TABLES=("users" "facilities" "land_info" "facility_comments")
    for table in "${TABLES[@]}"; do
        if ! php artisan tinker --execute="echo DB::table('$table')->count();" > /dev/null 2>&1; then
            log_error "テーブル '$table' にアクセスできません"
            return 1
        fi
    done
    
    log_success "データベース接続検証完了"
    return 0
}

# Laravel設定検証
verify_laravel_config() {
    log_info "Laravel設定を検証しています..."
    
    # アプリケーションキーの確認
    if ! php artisan key:generate --show > /dev/null 2>&1; then
        log_error "アプリケーションキーが設定されていません"
        return 1
    fi
    
    # キャッシュ状態確認
    CACHE_FILES=("bootstrap/cache/config.php" "bootstrap/cache/routes-v7.php")
    for cache_file in "${CACHE_FILES[@]}"; do
        if [ ! -f "$cache_file" ]; then
            log_warn "キャッシュファイル '$cache_file' が存在しません"
        fi
    done
    
    # ストレージリンク確認
    if [ ! -L "public/storage" ]; then
        log_warn "ストレージリンクが作成されていません"
    fi
    
    log_success "Laravel設定検証完了"
    return 0
}

# HTTP接続検証
verify_http_connection() {
    log_info "HTTP接続を検証しています..."
    
    # サーバーが起動しているかチェック
    if ! curl -f -s "$TARGET_URL" > /dev/null; then
        log_error "HTTP接続に失敗しました: $TARGET_URL"
        return 1
    fi
    
    # 主要ページの確認
    PAGES=("/" "/login" "/register")
    for page in "${PAGES[@]}"; do
        if ! curl -f -s "$TARGET_URL$page" > /dev/null; then
            log_error "ページ '$page' にアクセスできません"
            return 1
        fi
    done
    
    log_success "HTTP接続検証完了"
    return 0
}

# アセット読み込み検証
verify_asset_loading() {
    log_info "アセット読み込みを検証しています..."
    
    # メインページのHTMLを取得
    HTML_CONTENT=$(curl -s "$TARGET_URL")
    
    # Viteディレクティブの確認
    if ! echo "$HTML_CONTENT" | grep -q "vite"; then
        log_error "ViteディレクティブがHTMLに含まれていません"
        return 1
    fi
    
    # CSSファイルの読み込み確認
    if ! echo "$HTML_CONTENT" | grep -q "\.css"; then
        log_error "CSSファイルの読み込みが確認できません"
        return 1
    fi
    
    # JSファイルの読み込み確認
    if ! echo "$HTML_CONTENT" | grep -q "\.js"; then
        log_error "JSファイルの読み込みが確認できません"
        return 1
    fi
    
    log_success "アセット読み込み検証完了"
    return 0
}

# パフォーマンス検証
verify_performance() {
    log_info "パフォーマンスを検証しています..."
    
    # ページ読み込み時間測定
    LOAD_TIME=$(curl -o /dev/null -s -w "%{time_total}" "$TARGET_URL")
    LOAD_TIME_MS=$(echo "$LOAD_TIME * 1000" | bc)
    
    log_info "ページ読み込み時間: ${LOAD_TIME_MS}ms"
    
    # 3秒以内の読み込み時間をチェック
    if (( $(echo "$LOAD_TIME > 3.0" | bc -l) )); then
        log_warn "ページ読み込み時間が3秒を超えています"
    fi
    
    # アセットファイルサイズ確認
    BUILD_SIZE=$(du -sh public/build | cut -f1)
    log_info "ビルドサイズ: $BUILD_SIZE"
    
    log_success "パフォーマンス検証完了"
    return 0
}

# テスト実行
run_tests() {
    log_info "テストを実行しています..."
    
    # PHPテスト（重要なもののみ）
    if ! php artisan test --filter="AssetCompilationTest" > /dev/null 2>&1; then
        log_error "アセットコンパイレーションテストが失敗しました"
        return 1
    fi
    
    # JavaScriptテスト
    if [ -f "package.json" ] && grep -q "test" package.json; then
        if ! npm test -- tests/js/asset-integration.test.js > /dev/null 2>&1; then
            log_error "JavaScriptアセット統合テストが失敗しました"
            return 1
        fi
    fi
    
    log_success "テスト実行完了"
    return 0
}

# 検証結果サマリー
show_verification_summary() {
    echo ""
    echo "🎉 デプロイメント検証が完了しました！"
    echo ""
    echo "📊 検証結果サマリー:"
    echo "  ✅ アセットファイル検証"
    echo "  ✅ データベース接続検証"
    echo "  ✅ Laravel設定検証"
    echo "  ✅ HTTP接続検証"
    echo "  ✅ アセット読み込み検証"
    echo "  ✅ パフォーマンス検証"
    echo "  ✅ テスト実行"
    echo ""
    echo "🌐 アプリケーション URL: $TARGET_URL"
    echo ""
    echo "📝 次のステップ:"
    echo "  1. ブラウザでアプリケーションにアクセス"
    echo "  2. 主要機能の動作確認"
    echo "  3. 本番環境での最終テスト"
    echo ""
}

# メイン実行
main() {
    echo "🏗️  Shise-Cal (shise-cal/facility-management) v2.0.0 デプロイメント検証"
    echo "=================================================================="
    echo ""
    echo "検証対象: $TARGET_URL"
    echo ""
    
    # 各検証を順次実行
    verify_assets || exit 1
    verify_database || exit 1
    verify_laravel_config || exit 1
    verify_http_connection || exit 1
    verify_asset_loading || exit 1
    verify_performance || exit 1
    run_tests || exit 1
    
    show_verification_summary
    
    echo "✅ 検証完了！"
}

# エラーハンドリング
trap 'log_error "検証中にエラーが発生しました"; exit 1' ERR

# スクリプト実行
main "$@"