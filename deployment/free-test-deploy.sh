#!/bin/bash

# Shise-Cal 無料テスト環境デプロイスクリプト
# SQLiteベースの完全無料環境を構築

set -e

echo "🚀 Shise-Cal 無料テスト環境デプロイを開始します..."

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
    
    # SQLite拡張チェック
    if ! php -m | grep -q sqlite3; then
        log_error "PHP SQLite拡張がインストールされていません"
        exit 1
    fi
    
    log_info "✅ すべての前提条件が満たされています"
}

# 環境設定
setup_environment() {
    log_info "環境設定を行います..."
    
    # .envファイルの設定
    if [ ! -f .env ]; then
        log_info ".envファイルをコピーしています..."
        cp .env.testing .env
    else
        log_warn ".envファイルが既に存在します（スキップ）"
    fi
    
    # アプリケーションキーの生成
    log_info "アプリケーションキーを生成しています..."
    php artisan key:generate --force
    
    log_info "✅ 環境設定完了"
}

# 依存関係のインストール
install_dependencies() {
    log_info "依存関係をインストールしています..."
    
    # Composer依存関係
    log_info "PHP依存関係をインストール中..."
    composer install --optimize-autoloader --no-dev
    
    # Node.js依存関係
    log_info "Node.js依存関係をインストール中..."
    npm ci
    
    log_info "✅ 依存関係インストール完了"
}

# データベース設定
setup_database() {
    log_info "SQLiteデータベースを設定しています..."
    
    # データベースディレクトリの作成
    mkdir -p database
    
    # SQLiteファイルの作成
    if [ ! -f database/database.sqlite ]; then
        log_info "本番用SQLiteファイルを作成中..."
        touch database/database.sqlite
    fi
    
    if [ ! -f database/testing.sqlite ]; then
        log_info "テスト用SQLiteファイルを作成中..."
        touch database/testing.sqlite
    fi
    
    # 権限設定
    chmod 755 database/
    chmod 664 database/*.sqlite
    
    # マイグレーション実行
    log_info "データベースマイグレーションを実行中..."
    php artisan migrate:fresh --seed --force
    
    log_info "✅ データベース設定完了"
}

# フロントエンドビルド
build_frontend() {
    log_info "フロントエンドアセットをビルドしています..."
    
    npm run build
    
    log_info "✅ フロントエンドビルド完了"
}

# ストレージ設定
setup_storage() {
    log_info "ストレージを設定しています..."
    
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
    
    log_info "✅ ストレージ設定完了"
}

# キャッシュ最適化
optimize_cache() {
    log_info "キャッシュを最適化しています..."
    
    # 設定キャッシュ
    php artisan config:cache
    
    # ルートキャッシュ
    php artisan route:cache
    
    # ビューキャッシュ
    php artisan view:cache
    
    log_info "✅ キャッシュ最適化完了"
}

# テスト実行
run_tests() {
    log_info "テストを実行しています..."
    
    # PHPテスト
    log_info "PHPテストを実行中..."
    php artisan test --env=testing
    
    # JavaScriptテスト（存在する場合）
    if [ -f "package.json" ] && grep -q "test" package.json; then
        log_info "JavaScriptテストを実行中..."
        npm test
    fi
    
    log_info "✅ テスト実行完了"
}

# ヘルスチェック
health_check() {
    log_info "ヘルスチェックを実行しています..."
    
    # データベース接続確認
    php artisan migrate:status
    
    # 設定確認
    php artisan about
    
    log_info "✅ ヘルスチェック完了"
}

# デプロイ情報表示
show_deployment_info() {
    echo ""
    echo "🎉 無料テスト環境のデプロイが完了しました！"
    echo ""
    echo "📊 環境情報:"
    echo "  - データベース: SQLite (database/database.sqlite)"
    echo "  - キャッシュ: ファイルベース"
    echo "  - ストレージ: ローカルファイル"
    echo "  - コスト: 完全無料 💰"
    echo ""
    echo "🚀 サーバー起動:"
    echo "  php artisan serve"
    echo ""
    echo "🌐 アクセス:"
    echo "  http://localhost:8000"
    echo ""
    echo "🧪 テスト実行:"
    echo "  php artisan test"
    echo ""
    echo "📝 ログ確認:"
    echo "  tail -f storage/logs/laravel.log"
    echo ""
}

# メイン実行
main() {
    echo "🏗️  Shise-Cal 無料テスト環境デプロイスクリプト"
    echo "================================================"
    echo ""
    
    check_requirements
    setup_environment
    install_dependencies
    setup_database
    build_frontend
    setup_storage
    optimize_cache
    run_tests
    health_check
    show_deployment_info
    
    echo "✅ デプロイ完了！"
}

# エラーハンドリング
trap 'log_error "デプロイ中にエラーが発生しました"; exit 1' ERR

# スクリプト実行
main "$@"