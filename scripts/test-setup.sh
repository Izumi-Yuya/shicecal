#!/bin/bash

# セットアップテストスクリプト
# 使用方法: ./scripts/test-setup.sh

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

info "🧪 Shise-Cal セットアップテスト開始"

# 1. 設定値確認
info "📋 設定値確認..."
show_config

# 2. 必要なツールの確認
info "🔧 必要なツール確認..."
MISSING_TOOLS=()

# SSH確認
if ! command -v ssh >/dev/null 2>&1; then
    MISSING_TOOLS+=("ssh")
fi

# curl確認
if ! command -v curl >/dev/null 2>&1; then
    MISSING_TOOLS+=("curl")
fi

# git確認
if ! command -v git >/dev/null 2>&1; then
    MISSING_TOOLS+=("git")
fi

# GitHub CLI確認（オプション）
if ! command -v gh >/dev/null 2>&1; then
    warn "GitHub CLI (gh) がインストールされていません（GitHub Secrets設定に必要）"
    echo "   インストール: https://cli.github.com/"
else
    success "✅ GitHub CLI利用可能"
fi

if [ ${#MISSING_TOOLS[@]} -eq 0 ]; then
    success "✅ 必要なツールがすべて利用可能です"
else
    error "❌ 不足しているツール: ${MISSING_TOOLS[*]}"
    exit 1
fi

# 3. SSH鍵ファイル確認
info "🔑 SSH鍵ファイル確認..."
if [ ! -f "$SSH_KEY_PATH" ]; then
    error "❌ SSH鍵ファイルが見つかりません: $SSH_KEY_PATH"
    echo "   SSH鍵を正しい場所に配置してください"
    exit 1
else
    success "✅ SSH鍵ファイル確認完了: $SSH_KEY_PATH"
    
    # 権限確認
    perms=$(stat -f "%A" "$SSH_KEY_PATH" 2>/dev/null || stat -c "%a" "$SSH_KEY_PATH" 2>/dev/null)
    if [ "$perms" != "600" ]; then
        warn "⚠️ SSH鍵の権限を修正します"
        chmod 600 "$SSH_KEY_PATH"
        success "✅ SSH鍵の権限を600に設定しました"
    fi
fi

# 4. AWS EC2接続テスト
info "📡 AWS EC2接続テスト..."
if ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$AWS_USERNAME@$AWS_HOST" "echo 'Connection successful'" 2>/dev/null; then
    success "✅ AWS EC2接続テスト成功"
else
    error "❌ AWS EC2接続テストに失敗しました"
    echo "   Host: $AWS_HOST"
    echo "   User: $AWS_USERNAME"
    echo "   Key: $SSH_KEY_PATH"
    echo ""
    echo "   確認事項:"
    echo "   - SSH鍵ファイルが正しいか"
    echo "   - AWSホストアドレスが正しいか"
    echo "   - セキュリティグループでSSH(22)が許可されているか"
    exit 1
fi

# 5. アプリケーション応答テスト
info "🌐 アプリケーション応答テスト..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$AWS_PROD_URL" || echo "000")
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    success "✅ アプリケーション応答テスト成功 (HTTP $HTTP_STATUS)"
else
    warn "⚠️ アプリケーション応答に問題があります (HTTP $HTTP_STATUS)"
    echo "   URL: $AWS_PROD_URL"
fi

# 6. リモートアプリケーション確認
info "🔍 リモートアプリケーション確認..."
ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no "$AWS_USERNAME@$AWS_HOST" << EOF
if [ -d "$DEPLOY_DIR" ]; then
    echo "✅ アプリケーションディレクトリ存在: $DEPLOY_DIR"
    
    cd "$DEPLOY_DIR"
    
    # Laravel確認
    if [ -f "artisan" ]; then
        echo "✅ Laravel artisan ファイル存在"
        
        # PHP確認
        if php artisan --version >/dev/null 2>&1; then
            VERSION=\$(php artisan --version)
            echo "✅ Laravel アプリケーション正常: \$VERSION"
        else
            echo "❌ Laravel アプリケーションに問題があります"
        fi
    else
        echo "❌ Laravel artisan ファイルが見つかりません"
    fi
    
    # ビルドファイル確認
    if [ -f "public/build/manifest.json" ]; then
        BUILD_FILES=\$(find public/build -type f | wc -l)
        echo "✅ フロントエンドビルド確認: \${BUILD_FILES}ファイル"
    else
        echo "⚠️ フロントエンドビルドファイルが見つかりません"
    fi
    
    # 環境設定確認
    if [ -f ".env" ]; then
        echo "✅ 環境設定ファイル(.env)存在"
    else
        echo "❌ 環境設定ファイル(.env)が見つかりません"
    fi
else
    echo "❌ アプリケーションディレクトリが見つかりません: $DEPLOY_DIR"
fi
EOF

# 7. GitHub設定確認
info "🐙 GitHub設定確認..."
if command -v gh >/dev/null 2>&1; then
    if gh auth status >/dev/null 2>&1; then
        success "✅ GitHub CLI認証済み"
        
        # リポジトリアクセス確認
        if gh repo view "$GITHUB_REPO" >/dev/null 2>&1; then
            success "✅ GitHubリポジトリアクセス可能: $GITHUB_REPO"
        else
            warn "⚠️ GitHubリポジトリにアクセスできません: $GITHUB_REPO"
        fi
    else
        warn "⚠️ GitHub CLI認証が必要です"
        echo "   認証方法: gh auth login"
    fi
else
    warn "⚠️ GitHub CLI未インストール（GitHub Secrets設定に必要）"
fi

# 8. 総合評価
success "🎉 セットアップテスト完了"

echo ""
echo "📊 テスト結果サマリー:"
echo "========================"
echo "SSH接続: ✅ 成功"
echo "アプリケーション: $([ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ] && echo "✅ 正常" || echo "⚠️ 要確認")"
echo "GitHub CLI: $(command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1 && echo "✅ 利用可能" || echo "⚠️ 要設定")"
echo ""
echo "🚀 次のステップ:"
echo "1. GitHub Secrets設定: ./scripts/setup-github-secrets.sh"
echo "2. 監視システム設定: ./scripts/monitoring-setup.sh"
echo "3. 初回バックアップ: ./scripts/backup-production.sh"
echo "4. 健全性チェック: ./scripts/production-health-check.sh"