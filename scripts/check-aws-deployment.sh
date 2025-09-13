#!/bin/bash

# AWS デプロイメント状況確認スクリプト
# 使用方法: ./scripts/check-aws-deployment.sh

set -e

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# SSH鍵ファイルの確認
SSH_KEY_FILE="$HOME/Shise-Cal-test-key.pem"
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"

info "🔍 AWS デプロイメント状況確認開始"

# SSH鍵ファイルの存在確認
if [ ! -f "$SSH_KEY_FILE" ]; then
    error "SSH鍵ファイルが見つかりません: $SSH_KEY_FILE"
    exit 1
fi

# SSH鍵の権限確認
chmod 600 "$SSH_KEY_FILE"

info "📡 AWS EC2インスタンスに接続中..."

# SSH接続でサーバー状況を確認
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$AWS_USER@$AWS_HOST" << 'EOF'
echo "🔍 AWS EC2インスタンス状況確認"
echo "================================"

echo "📅 現在時刻:"
date

echo ""
echo "💻 システム情報:"
uname -a

echo ""
echo "📊 ディスク使用量:"
df -h

echo ""
echo "🔄 実行中のプロセス (nginx, php-fpm):"
ps aux | grep -E "(nginx|php-fpm)" | grep -v grep

echo ""
echo "🌐 Nginxステータス:"
sudo systemctl status nginx --no-pager -l

echo ""
echo "🐘 PHP-FPMステータス:"
sudo systemctl status php-fpm --no-pager -l

echo ""
echo "📁 アプリケーションディレクトリ:"
if [ -d "/home/ec2-user/shicecal-test" ]; then
    echo "✅ /home/ec2-user/shicecal-test が存在します"
    cd /home/ec2-user/shicecal-test
    echo "📂 ディレクトリ内容:"
    ls -la
    
    echo ""
    echo "🔧 Laravel設定確認:"
    if [ -f ".env" ]; then
        echo "✅ .env ファイルが存在します"
        echo "APP_ENV: $(grep APP_ENV .env || echo '未設定')"
        echo "APP_DEBUG: $(grep APP_DEBUG .env || echo '未設定')"
        echo "APP_URL: $(grep APP_URL .env || echo '未設定')"
    else
        echo "❌ .env ファイルが見つかりません"
    fi
    
    echo ""
    echo "🏗️ ビルドファイル確認:"
    if [ -d "public/build" ]; then
        echo "✅ public/build ディレクトリが存在します"
        ls -la public/build/ | head -10
    else
        echo "❌ public/build ディレクトリが見つかりません"
    fi
    
    echo ""
    echo "📝 Laravelログ確認:"
    if [ -f "storage/logs/laravel.log" ]; then
        echo "📋 最新のログエントリ (最後の10行):"
        tail -10 storage/logs/laravel.log
    else
        echo "ℹ️ Laravelログファイルが見つかりません"
    fi
    
else
    echo "❌ アプリケーションディレクトリが見つかりません"
fi

echo ""
echo "🔍 Nginxログ確認:"
echo "📋 アクセスログ (最後の5行):"
sudo tail -5 /var/log/nginx/access.log 2>/dev/null || echo "アクセスログが見つかりません"

echo ""
echo "📋 エラーログ (最後の5行):"
sudo tail -5 /var/log/nginx/error.log 2>/dev/null || echo "エラーログが見つかりません"

echo ""
echo "🌐 ネットワーク接続確認:"
echo "📡 リスニングポート:"
sudo netstat -tlnp | grep -E ":80|:443|:22"

echo ""
echo "🔥 ファイアウォール状況:"
sudo iptables -L INPUT -n | head -10

echo ""
echo "🎯 アプリケーション動作テスト:"
if command -v php >/dev/null 2>&1; then
    cd /home/ec2-user/shicecal-test 2>/dev/null || echo "アプリケーションディレクトリに移動できません"
    if [ -f "artisan" ]; then
        echo "🧪 Laravel Artisan コマンドテスト:"
        php artisan --version 2>/dev/null || echo "Artisanコマンドが実行できません"
        
        echo "🗄️ データベース接続テスト:"
        php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB接続: OK'; } catch(Exception \$e) { echo 'DB接続エラー: ' . \$e->getMessage(); }" 2>/dev/null || echo "データベース接続テストが実行できません"
    fi
else
    echo "❌ PHPが見つかりません"
fi

echo ""
echo "✅ AWS EC2インスタンス状況確認完了"
EOF

if [ $? -eq 0 ]; then
    success "✅ AWS サーバー状況確認完了"
else
    error "❌ AWS サーバーへの接続に失敗しました"
    exit 1
fi