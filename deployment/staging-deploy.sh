#!/bin/bash

# Shise-Cal ステージング環境デプロイスクリプト
# 本番デプロイ前のテスト環境構築

set -e

echo "🚀 Shise-Cal ステージング環境デプロイを開始します..."

# 設定
STAGING_HOST="${STAGING_HOST:-localhost}"
STAGING_USER="${STAGING_USER:-$(whoami)}"
STAGING_PATH="${STAGING_PATH:-$(pwd)/staging}"
STAGING_PORT="${STAGING_PORT:-8001}"

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

# 前提条件チェック
check_requirements() {
    log_info "前提条件をチェックしています..."
    
    # PHP バージョンチェック
    if ! command -v php &> /dev/null; then
        log_error "PHPがインストールされていません"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_info "PHP バージョン: $PHP_VERSION"
    
    # Composer チェック
    if ! command -v composer &> /dev/null; then
        log_error "Composerがインストールされていません"
        exit 1
    fi
    
    # Node.js チェック
    if ! command -v node &> /dev/null; then
        log_error "Node.jsがインストールされていません"
        exit 1
    fi
    
    NODE_VERSION=$(node --version)
    log_info "Node.js バージョン: $NODE_VERSION"
    
    # npm チェック
    if ! command -v npm &> /dev/null; then
        log_error "npmがインストールされていません"
        exit 1
    fi
    
    log_info "✅ すべての前提条件が満たされています"
}

# ステージング環境準備
prepare_staging() {
    log_info "ステージング環境を準備しています..."
    
    # ステージングディレクトリ作成
    if [ ! -d "$STAGING_PATH" ]; then
        log_info "ステージングディレクトリを作成中: $STAGING_PATH"
        mkdir -p "$STAGING_PATH"
    fi
    
    # 現在のコードをステージング環境にコピー
    log_info "コードをステージング環境にコピー中..."
    rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='storage/logs/*' --exclude='public/build' . "$STAGING_PATH/"
    
    cd "$STAGING_PATH"
    
    log_info "✅ ステージング環境準備完了"
}

# 環境設定
setup_staging_environment() {
    log_info "ステージング環境設定を行います..."
    
    # .envファイルの設定
    if [ ! -f .env ]; then
        log_info ".envファイルをコピーしています..."
        cp .env.example .env
        
        # ステージング用設定を適用
        sed -i.bak "s/APP_ENV=.*/APP_ENV=staging/" .env
        sed -i.bak "s/APP_DEBUG=.*/APP_DEBUG=true/" .env
        sed -i.bak "s/DB_CONNECTION=.*/DB_CONNECTION=sqlite/" .env
        sed -i.bak "s|DB_DATABASE=.*|DB_DATABASE=$(pwd)/database/staging.sqlite|" .env
        
        # ポート設定
        echo "VITE_DEV_SERVER_PORT=$STAGING_PORT" >> .env
        
        rm .env.bak
    else
        log_warn ".envファイルが既に存在します"
    fi
    
    # アプリケーションキーの生成
    log_info "アプリケーションキーを生成しています..."
    php artisan key:generate --force
    
    log_info "✅ ステージング環境設定完了"
}

# 依存関係のインストール
install_dependencies() {
    log_info "依存関係をインストールしています..."
    
    # Composer依存関係
    log_info "PHP依存関係をインストール中..."
    composer install --optimize-autoloader
    
    # Node.js依存関係
    log_info "Node.js依存関係をインストール中..."
    npm ci
    
    # Vite確認
    if ! npx vite --version &> /dev/null; then
        log_error "Viteが正しくインストールされていません"
        exit 1
    fi
    
    log_info "✅ 依存関係インストール完了"
}

# データベース設定
setup_staging_database() {
    log_info "ステージングデータベースを設定しています..."
    
    # データベースディレクトリの作成
    mkdir -p database
    
    # SQLiteファイルの作成
    if [ ! -f database/staging.sqlite ]; then
        log_info "ステージング用SQLiteファイルを作成中..."
        touch database/staging.sqlite
    fi
    
    # 権限設定
    chmod 755 database/
    chmod 664 database/*.sqlite
    
    # マイグレーション実行
    log_info "データベースマイグレーションを実行中..."
    php artisan migrate:fresh --seed --force
    
    log_info "✅ ステージングデータベース設定完了"
}

# アセットビルド（本番用）
build_production_assets() {
    log_info "本番用アセットをビルドしています..."
    
    # 既存のビルドファイルを削除
    rm -rf public/build
    
    # 本番用ビルド実行
    log_info "Viteで本番用アセットをビルド中..."
    npm run build
    
    # ビルド結果確認
    if [ ! -d "public/build" ]; then
        log_error "アセットビルドが失敗しました"
        exit 1
    fi
    
    if [ ! -f "public/build/manifest.json" ]; then
        log_error "Viteマニフェストファイルが生成されませんでした"
        exit 1
    fi
    
    # ビルド統計表示
    BUILD_FILES=$(find public/build -type f | wc -l)
    BUILD_SIZE=$(du -sh public/build | cut -f1)
    log_info "本番用ビルド完了 - ファイル数: $BUILD_FILES, サイズ: $BUILD_SIZE"
    
    # CSS/JSファイルの確認
    CSS_FILES=$(find public/build -name "*.css" | wc -l)
    JS_FILES=$(find public/build -name "*.js" | wc -l)
    log_info "生成されたファイル - CSS: $CSS_FILES, JS: $JS_FILES"
    
    log_info "✅ 本番用アセットビルド完了"
}

# ストレージ設定
setup_staging_storage() {
    log_info "ステージングストレージを設定しています..."
    
    # ストレージディレクトリの作成
    mkdir -p storage/app/public
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs
    
    # 権限設定
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    
    # ストレージリンクの作成
    php artisan storage:link
    
    log_info "✅ ステージングストレージ設定完了"
}

# キャッシュ最適化
optimize_staging_cache() {
    log_info "ステージングキャッシュを最適化しています..."
    
    # 既存キャッシュクリア
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # 最適化キャッシュ生成
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Viteマニフェスト確認
    if [ ! -f "public/build/manifest.json" ]; then
        log_error "Viteマニフェストファイルが見つかりません"
        exit 1
    fi
    
    # マニフェストファイルの妥当性確認
    if ! php -r "json_decode(file_get_contents('public/build/manifest.json')); if (json_last_error() !== JSON_ERROR_NONE) exit(1);"; then
        log_error "Viteマニフェストファイルが無効です"
        exit 1
    fi
    
    log_info "✅ ステージングキャッシュ最適化完了"
}

# アセット統合テスト
test_asset_integration() {
    log_info "アセット統合テストを実行しています..."
    
    # CSS/JSファイルの存在確認
    log_info "アセットファイルの存在確認中..."
    
    # マニフェストファイルから実際のファイル名を取得してチェック
    php -r "
        \$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
        \$missing = [];
        foreach (\$manifest as \$key => \$entry) {
            if (isset(\$entry['file']) && !file_exists('public/build/' . \$entry['file'])) {
                \$missing[] = \$entry['file'];
            }
        }
        if (!empty(\$missing)) {
            echo 'Missing files: ' . implode(', ', \$missing) . PHP_EOL;
            exit(1);
        }
        echo 'All manifest files exist' . PHP_EOL;
    "
    
    # PHPテスト実行（アセット関連）
    if [ -f "tests/Feature/AssetCompilationTest.php" ]; then
        log_info "アセットコンパイレーションテストを実行中..."
        php artisan test tests/Feature/AssetCompilationTest.php --env=testing
    fi
    
    # JavaScriptテスト実行
    if [ -f "tests/js/asset-integration.test.js" ]; then
        log_info "JavaScriptアセット統合テストを実行中..."
        npm test -- tests/js/asset-integration.test.js
    fi
    
    log_info "✅ アセット統合テスト完了"
}

# ステージングサーバー起動
start_staging_server() {
    log_info "ステージングサーバーを起動しています..."
    
    # 既存のサーバープロセスを停止
    if pgrep -f "php.*artisan.*serve.*$STAGING_PORT" > /dev/null; then
        log_warn "既存のステージングサーバーを停止中..."
        pkill -f "php.*artisan.*serve.*$STAGING_PORT" || true
        sleep 2
    fi
    
    # サーバー起動
    log_info "ポート $STAGING_PORT でサーバーを起動中..."
    nohup php artisan serve --host=0.0.0.0 --port=$STAGING_PORT > storage/logs/staging-server.log 2>&1 &
    
    # サーバー起動確認
    sleep 3
    if ! pgrep -f "php.*artisan.*serve.*$STAGING_PORT" > /dev/null; then
        log_error "ステージングサーバーの起動に失敗しました"
        cat storage/logs/staging-server.log
        exit 1
    fi
    
    log_info "✅ ステージングサーバー起動完了"
}

# ヘルスチェック
health_check() {
    log_info "ヘルスチェックを実行しています..."
    
    # データベース接続確認
    php artisan migrate:status
    
    # 設定確認
    php artisan about
    
    # HTTP接続確認
    sleep 2
    if curl -f -s "http://localhost:$STAGING_PORT" > /dev/null; then
        log_info "✅ HTTP接続確認成功"
    else
        log_error "HTTP接続確認失敗"
        exit 1
    fi
    
    log_info "✅ ヘルスチェック完了"
}

# デプロイ情報表示
show_staging_info() {
    echo ""
    echo "🎉 ステージング環境のデプロイが完了しました！"
    echo ""
    echo "📊 環境情報:"
    echo "  - 環境: ステージング"
    echo "  - パス: $STAGING_PATH"
    echo "  - データベース: SQLite (database/staging.sqlite)"
    echo "  - アセット: Viteビルド済み"
    echo "  - ポート: $STAGING_PORT"
    echo ""
    echo "🌐 アクセス:"
    echo "  http://localhost:$STAGING_PORT"
    echo ""
    echo "🔧 管理コマンド:"
    echo "  cd $STAGING_PATH"
    echo "  php artisan serve --port=$STAGING_PORT  # サーバー再起動"
    echo "  php artisan test                        # テスト実行"
    echo "  npm run build                           # アセット再ビルド"
    echo ""
    echo "📝 ログ確認:"
    echo "  tail -f $STAGING_PATH/storage/logs/laravel.log"
    echo "  tail -f $STAGING_PATH/storage/logs/staging-server.log"
    echo ""
    echo "🛑 サーバー停止:"
    echo "  pkill -f 'php.*artisan.*serve.*$STAGING_PORT'"
    echo ""
}

# クリーンアップ関数
cleanup() {
    log_info "クリーンアップを実行中..."
    # 必要に応じてクリーンアップ処理を追加
}

# メイン実行
main() {
    echo "🏗️  Shise-Cal ステージング環境デプロイスクリプト"
    echo "=================================================="
    echo ""
    
    check_requirements
    prepare_staging
    setup_staging_environment
    install_dependencies
    setup_staging_database
    build_production_assets
    setup_staging_storage
    optimize_staging_cache
    test_asset_integration
    start_staging_server
    health_check
    show_staging_info
    
    echo "✅ ステージングデプロイ完了！"
}

# エラーハンドリング
trap 'log_error "ステージングデプロイ中にエラーが発生しました"; cleanup; exit 1' ERR

# スクリプト実行
main "$@"