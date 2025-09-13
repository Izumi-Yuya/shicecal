#!/bin/bash

# GitHub CLI 認証ヘルパースクリプト
# 使用方法: ./scripts/github-auth-helper.sh

set -e

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# GitHub CLI認証状態確認
check_github_auth() {
    info "GitHub CLI認証状態を確認中..."
    
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI (gh) がインストールされていません"
        error "インストール: brew install gh"
        return 1
    fi
    
    if gh auth status &> /dev/null; then
        success "✅ GitHub CLI認証済み"
        gh auth status
        return 0
    else
        warn "❌ GitHub CLI未認証"
        return 1
    fi
}

# Personal Access Token での認証
auth_with_token() {
    info "Personal Access Token での認証を開始..."
    
    echo ""
    echo "📋 GitHub Personal Access Token が必要です"
    echo "   作成手順: docs/deployment/GITHUB_TOKEN_SETUP.md を参照"
    echo ""
    
    read -s -p "GitHub Personal Access Token を入力してください: " token
    echo ""
    
    if [ -z "$token" ]; then
        error "トークンが入力されていません"
        return 1
    fi
    
    # 環境変数に設定
    export GITHUB_TOKEN="$token"
    
    # 認証テスト
    if gh auth status &> /dev/null; then
        success "✅ 認証成功"
        gh auth status
        
        # 永続化するか確認
        read -p "このトークンを ~/.zshrc に保存しますか? (y/n): " save_token
        if [[ $save_token =~ ^[Yy]$ ]]; then
            echo "export GITHUB_TOKEN=\"$token\"" >> ~/.zshrc
            success "トークンを ~/.zshrc に保存しました"
            warn "新しいターミナルセッションで有効になります"
        fi
        
        return 0
    else
        error "❌ 認証失敗"
        error "トークンが無効か、権限が不足している可能性があります"
        return 1
    fi
}

# 対話式認証
auth_interactive() {
    info "対話式認証を開始..."
    
    echo ""
    echo "🌐 ブラウザでGitHubにログインします"
    echo "   プロンプトに従って認証を完了してください"
    echo ""
    
    gh auth login
    
    if gh auth status &> /dev/null; then
        success "✅ 対話式認証成功"
        return 0
    else
        error "❌ 対話式認証失敗"
        return 1
    fi
}

# メイン処理
main() {
    info "🔐 GitHub CLI 認証ヘルパー"
    
    # 現在の認証状態確認
    if check_github_auth; then
        success "既に認証済みです"
        
        # リポジトリアクセステスト
        info "リポジトリアクセスをテスト中..."
        if gh repo view &> /dev/null; then
            success "✅ リポジトリアクセス確認"
        else
            warn "⚠️  リポジトリアクセスに問題があります"
        fi
        
        # Secrets管理権限テスト
        info "Secrets管理権限をテスト中..."
        if gh secret list &> /dev/null; then
            success "✅ Secrets管理権限確認"
        else
            warn "⚠️  Secrets管理権限がありません"
        fi
        
        return 0
    fi
    
    # 認証方法選択
    echo ""
    echo "認証方法を選択してください:"
    echo "1) Personal Access Token"
    echo "2) 対話式認証 (ブラウザ)"
    echo ""
    read -p "選択 (1-2): " choice
    
    case $choice in
        1)
            auth_with_token
            ;;
        2)
            auth_interactive
            ;;
        *)
            error "無効な選択です"
            return 1
            ;;
    esac
}

# スクリプト実行
main "$@"