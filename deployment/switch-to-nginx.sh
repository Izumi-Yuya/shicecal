#!/bin/bash

# ApacheからNginxに切り替えるスクリプト
# 使用方法: ./deployment/switch-to-nginx.sh

set -e

# 設定
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
SSH_KEY="~/Shise-Cal-test-key.pem"
REMOTE_PATH="/home/ec2-user/shicecal"

echo "🔄 ApacheからNginxに切り替えます..."

# AWS EC2に接続してApacheを停止、Nginxを起動
ssh -i $SSH_KEY $AWS_USER@$AWS_HOST << EOF
    set -e
    cd $REMOTE_PATH
    
    echo "🛑 Apacheを停止中..."
    sudo systemctl stop httpd || true
    sudo systemctl disable httpd || true
    
    echo "🌐 ポート80の状況を確認中..."
    sudo netstat -tlnp | grep :80 || echo "ポート80は空いています"
    
    echo "🚀 Nginxを起動中..."
    sudo systemctl enable nginx
    sudo systemctl start nginx
    
    echo "📊 サービス状況を確認中..."
    sudo systemctl status nginx --no-pager
    sudo systemctl status php-fpm --no-pager
    
    echo "🌐 ポート80の最終確認..."
    sudo netstat -tlnp | grep :80
    
    echo "✅ 切り替え完了!"
EOF

echo "🎉 ApacheからNginxへの切り替えが完了しました!"
echo "🌐 アプリケーション URL: http://$AWS_HOST"

# 検証を実行
echo "🔍 デプロイメント検証を実行中..."
./deployment/verify-deployment.sh $AWS_HOST 80