#!/bin/bash

# GitHub Secrets 設定スクリプト
# 使用方法: ./scripts/setup-github-secrets.sh [environment]
#
# 機能:
# - 環境別GitHub Secretsの設定
# - SSH鍵の検証
# - 設定値の確認

set -e

# スクリプト設定
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# 使用方法表示
show_usage() {
    cat << EOF
使用方法: ./scripts/setup-github-secrets.sh [environment] [action]

引数:
  environment    設定対象環境 (test|staging|production|all) [デフォルト: test]
  action        実行アクション (setup|verify|list) [デフォルト: setup]

例:
  ./scripts/setup-github-secrets.sh test setup      # テスト環境のSecrets設定
  ./scripts/setup-github-secrets.sh all verify      # 全環境のSecrets確認
  ./scripts/setup-github-secrets.sh production list # 本番環境のSecrets一覧

前提条件:
  - GitHub CLI (gh) がインストール済み
  - GitHubリポジトリにアクセス権限がある
  - 必要なSSH鍵ファイルが存在する

環境変数:
  GITHUB_TOKEN    # GitHub Personal Access Token (オプション)
EOF
}

# GitHub CLI の確認
check_github_cli() {
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI (gh) がインストールされていません"
        error "インストール手順: https://cli.github.com/"
        exit 1
    fi
    
    if ! gh auth status &> /dev/null; then
        error "GitHub CLI にログインしていません"
        error "実行してください: gh auth login"
        exit 1
    fi
    
    success "GitHub CLI の認証確認完了"
}

# SSH鍵ファイルの確認
check_ssh_keys() {
    local environment="$1"
    
    case $environment in
        "test")
            local key_file="$HOME/Shise-Cal-test-key.pem"
            ;;
        "staging")
            local key_file="$HOME/Shise-Cal-staging-key.pem"
            ;;
        "production")
            local key_file="$HOME/Shise-Cal-prod-key.pem"
            ;;
        *)
            warn "不明な環境: $environment"
            return 1
            ;;
    esac
    
    if [ ! -f "$key_file" ]; then
        error "SSH鍵ファイルが見つかりません: $key_file"
        return 1
    fi
    
    # 鍵ファイルの権限確認
    local permissions=$(stat -f "%A" "$key_file" 2>/dev/null || stat -c "%a" "$key_file" 2>/dev/null)
    if [ "$permissions" != "600" ]; then
        warn "SSH鍵の権限を修正します: $key_file"
        chmod 600 "$key_file"
    fi
    
    success "SSH鍵ファイル確認完了: $key_file"
    echo "$key_file"
}

# テスト環境のSecrets設定
setup_test_secrets() {
    info "🧪 テスト環境のGitHub Secrets設定を開始..."
    
    local ssh_key_file=$(check_ssh_keys "test")
    [ $? -ne 0 ] && return 1
    
    # テスト環境の設定値
    local test_host="35.75.1.64"
    local test_user="ec2-user"
    local test_path="/home/ec2-user/shicecal"
    local test_url="http://35.75.1.64"
    
    # SSH鍵の内容を読み込み
    local ssh_key_content=$(cat "$ssh_key_file")
    
    # GitHub Secretsの設定
    info "SSH鍵を設定中..."
    echo "$ssh_key_content" | gh secret set AWS_TEST_PRIVATE_KEY --body -
    
    info "テスト環境設定を追加中..."
    gh secret set AWS_TEST_HOST --body "$test_host"
    gh secret set AWS_TEST_USERNAME --body "$test_user"
    gh secret set AWS_TEST_PATH --body "$test_path"
    gh secret set AWS_TEST_URL --body "$test_url"
    
    # SSH Known Hosts の設定
    info "SSH Known Hosts を設定中..."
    local known_hosts=$(ssh-keyscan -H "$test_host" 2>/dev/null)
    if [ -n "$known_hosts" ]; then
        echo "$known_hosts" | gh secret set SSH_KNOWN_HOSTS --body -
    else
        warn "SSH Known Hosts の取得に失敗しました"
    fi
    
    success "✅ テスト環境のSecrets設定完了"
}

# ステージング環境のSecrets設定
setup_staging_secrets() {
    info "🔧 ステージング環境のGitHub Secrets設定を開始..."
    
    # ステージング環境の情報を入力で取得
    read -p "ステージング環境のホスト名またはIPアドレス: " staging_host
    read -p "ステージング環境のSSHユーザー名 [ec2-user]: " staging_user
    staging_user=${staging_user:-ec2-user}
    read -p "ステージング環境のデプロイパス [/var/www/shisecal]: " staging_path
    staging_path=${staging_path:-/var/www/shisecal}
    read -p "ステージング環境のURL: " staging_url
    
    local ssh_key_file=$(check_ssh_keys "staging")
    if [ $? -ne 0 ]; then
        warn "ステージング用SSH鍵が見つかりません。テスト環境の鍵を使用しますか? (y/n)"
        read -r use_test_key
        if [[ $use_test_key =~ ^[Yy]$ ]]; then
            ssh_key_file=$(check_ssh_keys "test")
        else
            return 1
        fi
    fi
    
    local ssh_key_content=$(cat "$ssh_key_file")
    
    # GitHub Secretsの設定
    echo "$ssh_key_content" | gh secret set AWS_STAGING_PRIVATE_KEY --body -
    gh secret set AWS_STAGING_HOST --body "$staging_host"
    gh secret set AWS_STAGING_USERNAME --body "$staging_user"
    gh secret set AWS_STAGING_PATH --body "$staging_path"
    gh secret set AWS_STAGING_URL --body "$staging_url"
    
    success "✅ ステージング環境のSecrets設定完了"
}

# 本番環境のSecrets設定
setup_production_secrets() {
    info "🚀 本番環境のGitHub Secrets設定を開始..."
    
    warn "⚠️  本番環境の設定は慎重に行ってください"
    read -p "続行しますか? (yes/no): " confirm
    if [[ ! $confirm =~ ^[Yy][Ee][Ss]$ ]]; then
        info "本番環境の設定をキャンセルしました"
        return 0
    fi
    
    read -p "本番環境のホスト名またはIPアドレス: " prod_host
    read -p "本番環境のSSHユーザー名 [ec2-user]: " prod_user
    prod_user=${prod_user:-ec2-user}
    read -p "本番環境のデプロイパス [/var/www/shisecal]: " prod_path
    prod_path=${prod_path:-/var/www/shisecal}
    read -p "本番環境のURL: " prod_url
    
    local ssh_key_file=$(check_ssh_keys "production")
    if [ $? -ne 0 ]; then
        error "本番環境用SSH鍵が必要です"
        return 1
    fi
    
    local ssh_key_content=$(cat "$ssh_key_file")
    
    # GitHub Secretsの設定
    echo "$ssh_key_content" | gh secret set AWS_PROD_PRIVATE_KEY --body -
    gh secret set AWS_PROD_HOST --body "$prod_host"
    gh secret set AWS_PROD_USERNAME --body "$prod_user"
    gh secret set AWS_PROD_PATH --body "$prod_path"
    gh secret set AWS_PROD_URL --body "$prod_url"
    
    success "✅ 本番環境のSecrets設定完了"
}

# 通知システムのSecrets設定
setup_notification_secrets() {
    info "📢 通知システムのSecrets設定を開始..."
    
    # Slack設定
    read -p "Slack Webhook URLを設定しますか? (y/n): " setup_slack
    if [[ $setup_slack =~ ^[Yy]$ ]]; then
        read -p "Slack Webhook URL: " slack_webhook
        if [ -n "$slack_webhook" ]; then
            gh secret set SLACK_WEBHOOK_URL --body "$slack_webhook"
            success "Slack Webhook URL を設定しました"
        fi
    fi
    
    # Email設定
    read -p "Email通知を設定しますか? (y/n): " setup_email
    if [[ $setup_email =~ ^[Yy]$ ]]; then
        read -p "通知先メールアドレス: " notification_email
        read -p "SMTPホスト: " smtp_host
        read -p "SMTPポート [587]: " smtp_port
        smtp_port=${smtp_port:-587}
        read -p "SMTPユーザー名: " smtp_user
        read -s -p "SMTPパスワード: " smtp_pass
        echo
        
        if [ -n "$notification_email" ]; then
            gh secret set NOTIFICATION_EMAIL --body "$notification_email"
            gh secret set SMTP_HOST --body "$smtp_host"
            gh secret set SMTP_PORT --body "$smtp_port"
            gh secret set SMTP_USERNAME --body "$smtp_user"
            gh secret set SMTP_PASSWORD --body "$smtp_pass"
            success "Email通知設定を完了しました"
        fi
    fi
}

# Secrets一覧表示
list_secrets() {
    info "📋 現在のGitHub Secrets一覧:"
    gh secret list
}

# Secrets検証
verify_secrets() {
    local environment="$1"
    
    info "🔍 GitHub Secrets検証を開始..."
    
    case $environment in
        "test")
            local required_secrets=(
                "AWS_TEST_HOST"
                "AWS_TEST_USERNAME"
                "AWS_TEST_PRIVATE_KEY"
                "AWS_TEST_PATH"
                "AWS_TEST_URL"
            )
            ;;
        "staging")
            local required_secrets=(
                "AWS_STAGING_HOST"
                "AWS_STAGING_USERNAME"
                "AWS_STAGING_PRIVATE_KEY"
                "AWS_STAGING_PATH"
                "AWS_STAGING_URL"
            )
            ;;
        "production")
            local required_secrets=(
                "AWS_PROD_HOST"
                "AWS_PROD_USERNAME"
                "AWS_PROD_PRIVATE_KEY"
                "AWS_PROD_PATH"
                "AWS_PROD_URL"
            )
            ;;
        "all")
            verify_secrets "test"
            verify_secrets "staging"
            verify_secrets "production"
            return
            ;;
        *)
            error "不明な環境: $environment"
            return 1
            ;;
    esac
    
    local missing_secrets=()
    local existing_secrets=$(gh secret list --json name --jq '.[].name')
    
    for secret in "${required_secrets[@]}"; do
        if echo "$existing_secrets" | grep -q "^$secret$"; then
            success "✅ $secret"
        else
            error "❌ $secret (未設定)"
            missing_secrets+=("$secret")
        fi
    done
    
    if [ ${#missing_secrets[@]} -eq 0 ]; then
        success "🎉 $environment 環境の全必須Secretsが設定済みです"
    else
        warn "⚠️  $environment 環境で ${#missing_secrets[@]} 個のSecretsが未設定です"
        return 1
    fi
}

# メイン処理
main() {
    local environment="${1:-test}"
    local action="${2:-setup}"
    
    # ヘルプ表示
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    info "🚀 GitHub Secrets設定スクリプト開始"
    info "環境: $environment"
    info "アクション: $action"
    
    # GitHub CLI確認
    check_github_cli
    
    case $action in
        "setup")
            case $environment in
                "test")
                    setup_test_secrets
                    ;;
                "staging")
                    setup_staging_secrets
                    ;;
                "production")
                    setup_production_secrets
                    ;;
                "all")
                    setup_test_secrets
                    setup_staging_secrets
                    setup_production_secrets
                    setup_notification_secrets
                    ;;
                *)
                    error "不明な環境: $environment"
                    show_usage
                    exit 1
                    ;;
            esac
            ;;
        "verify")
            verify_secrets "$environment"
            ;;
        "list")
            list_secrets
            ;;
        "notifications")
            setup_notification_secrets
            ;;
        *)
            error "不明なアクション: $action"
            show_usage
            exit 1
            ;;
    esac
    
    success "🎉 GitHub Secrets設定スクリプト完了"
}

# スクリプト実行
main "$@"
