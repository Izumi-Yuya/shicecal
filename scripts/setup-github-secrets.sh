#!/bin/bash

# GitHub Secrets 設定スクリプト
# 使用方法: ./scripts/setup-github-secrets.sh

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

# 設定値（環境変数で上書き可能）
REPO="${GITHUB_REPO:-Izumi-Yuya/shicecal}"
SSH_KEY_FILE="${SSH_KEY_PATH:-$HOME/Shise-Cal-test-key.pem}"
AWS_HOST="${AWS_HOST:-35.75.1.64}"
AWS_USERNAME="${AWS_USERNAME:-ec2-user}"
AWS_PROD_URL="${AWS_PROD_URL:-http://35.75.1.64}"

# コマンドライン引数での上書き
while [[ $# -gt 0 ]]; do
  case $1 in
    --ssh-key)
      SSH_KEY_FILE="$2"
      shift 2
      ;;
    --aws-host)
      AWS_HOST="$2"
      shift 2
      ;;
    --repo)
      REPO="$2"
      shift 2
      ;;
    -h|--help)
      echo "使用方法: $0 [オプション]"
      echo "オプション:"
      echo "  --ssh-key PATH    SSH鍵ファイルのパス (デフォルト: $HOME/Shise-Cal-test-key.pem)"
      echo "  --aws-host HOST   AWS EC2のホスト (デフォルト: 35.75.1.64)"
      echo "  --repo REPO       GitHubリポジトリ (デフォルト: Izumi-Yuya/shicecal)"
      echo "  -h, --help        このヘルプを表示"
      exit 0
      ;;
    *)
      error "不明なオプション: $1"
      exit 1
      ;;
  esac
done

info "🔧 GitHub Secrets 設定開始"

# GitHub CLI の確認
if ! command -v gh &> /dev/null; then
    error "GitHub CLI (gh) がインストールされていません"
    info "インストール方法: https://cli.github.com/"
    exit 1
fi

# GitHub認証確認
if ! gh auth status &> /dev/null; then
    warn "GitHub認証が必要です"
    info "認証を開始します..."
    gh auth login
fi

# SSH鍵ファイルの確認
if [ ! -f "$SSH_KEY_FILE" ]; then
    error "SSH鍵ファイルが見つかりません: $SSH_KEY_FILE"
    exit 1
fi

info "📋 設定するSecrets:"
echo "  - AWS_HOST: $AWS_HOST"
echo "  - AWS_USERNAME: $AWS_USERNAME"
echo "  - AWS_PROD_URL: $AWS_PROD_URL"
echo "  - AWS_PRIVATE_KEY: [SSH秘密鍵]"

read -p "これらのSecretsを設定しますか？ (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    info "設定をキャンセルしました"
    exit 0
fi

info "🔑 GitHub Secrets を設定中..."

# AWS_HOST の設定
gh secret set AWS_HOST --body "$AWS_HOST" --repo "$REPO"
success "AWS_HOST を設定しました"

# AWS_USERNAME の設定
gh secret set AWS_USERNAME --body "$AWS_USERNAME" --repo "$REPO"
success "AWS_USERNAME を設定しました"

# AWS_PROD_URL の設定
gh secret set AWS_PROD_URL --body "$AWS_PROD_URL" --repo "$REPO"
success "AWS_PROD_URL を設定しました"

# AWS_PRIVATE_KEY の設定
gh secret set AWS_PRIVATE_KEY --body "$(cat $SSH_KEY_FILE)" --repo "$REPO"
success "AWS_PRIVATE_KEY を設定しました"

info "📊 設定されたSecrets一覧:"
gh secret list --repo "$REPO"

success "✅ GitHub Secrets の設定が完了しました"

info "🧪 接続テストを実行中..."
if ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$AWS_USERNAME@$AWS_HOST" "echo 'Connection test successful'" 2>/dev/null; then
    success "✅ AWS EC2への接続テスト成功"
else
    warn "⚠️ AWS EC2への接続テストに失敗しました"
    info "手動で接続を確認してください: ssh -i $SSH_KEY_FILE $AWS_USERNAME@$AWS_HOST"
fi

info "🚀 次のステップ:"
echo "1. productionブランチにプッシュしてデプロイをテスト"
echo "2. GitHub Actions の実行ログを確認"
echo "3. アプリケーションの動作確認"
echo ""
echo "デプロイテスト: git push origin production"
echo "GitHub Actions: https://github.com/$REPO/actions"