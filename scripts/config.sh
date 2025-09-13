#!/bin/bash

# Shise-Cal デプロイメント設定ファイル
# このファイルを編集して環境固有の設定を行ってください

# AWS EC2設定
export AWS_HOST="${AWS_HOST:-35.75.1.64}"
export AWS_USERNAME="${AWS_USERNAME:-ec2-user}"
export AWS_PROD_URL="${AWS_PROD_URL:-http://35.75.1.64}"

# SSH設定
export SSH_KEY_PATH="${SSH_KEY_PATH:-$HOME/Shise-Cal-test-key.pem}"

# GitHub設定
export GITHUB_REPO="${GITHUB_REPO:-Izumi-Yuya/shicecal}"

# デプロイメント設定
export DEPLOY_DIR="${DEPLOY_DIR:-/home/ec2-user/shicecal}"
export REMOTE_BACKUP_DIR="${REMOTE_BACKUP_DIR:-/home/ec2-user/backups}"
export LOCAL_BACKUP_DIR="${LOCAL_BACKUP_DIR:-./backups}"

# 監視設定
export MONITORING_DIR="${MONITORING_DIR:-/home/ec2-user/monitoring}"

# 通知設定（オプション）
export SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"
export NOTIFICATION_EMAIL="${NOTIFICATION_EMAIL:-}"

# 設定値の表示関数
show_config() {
    echo "🔧 現在の設定:"
    echo "================================"
    echo "AWS Host: $AWS_HOST"
    echo "AWS Username: $AWS_USERNAME"
    echo "AWS Prod URL: $AWS_PROD_URL"
    echo "SSH Key Path: $SSH_KEY_PATH"
    echo "GitHub Repo: $GITHUB_REPO"
    echo "Deploy Dir: $DEPLOY_DIR"
    echo "Remote Backup Dir: $REMOTE_BACKUP_DIR"
    echo "Local Backup Dir: $LOCAL_BACKUP_DIR"
    echo "================================"
}

# 設定値の検証関数
validate_config() {
    local errors=0
    
    # SSH鍵ファイルの存在確認
    if [ ! -f "$SSH_KEY_PATH" ]; then
        echo "❌ SSH鍵ファイルが見つかりません: $SSH_KEY_PATH"
        errors=$((errors + 1))
    else
        # SSH鍵の権限確認
        local perms=$(stat -f "%A" "$SSH_KEY_PATH" 2>/dev/null || stat -c "%a" "$SSH_KEY_PATH" 2>/dev/null)
        if [ "$perms" != "600" ]; then
            echo "⚠️ SSH鍵の権限を修正します: chmod 600 $SSH_KEY_PATH"
            chmod 600 "$SSH_KEY_PATH"
        fi
    fi
    
    # AWS接続テスト
    if command -v ssh >/dev/null 2>&1; then
        if ! ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no -o ConnectTimeout=5 "$AWS_USERNAME@$AWS_HOST" "echo 'Connection test'" >/dev/null 2>&1; then
            echo "⚠️ AWS EC2への接続テストに失敗しました"
            echo "   Host: $AWS_HOST"
            echo "   User: $AWS_USERNAME"
            echo "   Key: $SSH_KEY_PATH"
        else
            echo "✅ AWS EC2接続テスト成功"
        fi
    fi
    
    return $errors
}

# このスクリプトが直接実行された場合
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    case "${1:-show}" in
        show)
            show_config
            ;;
        validate)
            show_config
            echo ""
            validate_config
            ;;
        test)
            echo "🧪 設定テスト実行中..."
            validate_config
            if [ $? -eq 0 ]; then
                echo "✅ 設定テスト完了"
            else
                echo "❌ 設定に問題があります"
                exit 1
            fi
            ;;
        *)
            echo "使用方法: $0 [show|validate|test]"
            echo "  show     - 現在の設定を表示"
            echo "  validate - 設定を検証"
            echo "  test     - 設定テストを実行"
            ;;
    esac
fi