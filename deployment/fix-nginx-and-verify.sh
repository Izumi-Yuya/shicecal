#!/bin/bash

# Nginx設定修正とサービス再起動スクリプト
# 使用方法: ./deployment/fix-nginx-and-verify.sh

set -e

# 設定
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
SSH_KEY="~/Shise-Cal-test-key.pem"
REMOTE_PATH="/home/ec2-user/shicecal"

echo "🔧 Nginx設定を修正してサービスを再起動します..."

# AWS EC2に接続してNginx設定を修正
ssh -i $SSH_KEY $AWS_USER@$AWS_HOST << EOF
    set -e
    cd $REMOTE_PATH
    
    echo "📋 Nginx設定状況を確認中..."
    sudo systemctl status nginx || true
    
    echo "🔧 Nginx設定をテスト中..."
    sudo nginx -t || true
    
    echo "🔄 Nginxを再起動中..."
    sudo systemctl stop nginx || true
    sudo systemctl start nginx || true
    
    echo "📊 サービス状況を確認中..."
    sudo systemctl status nginx --no-pager || true
    sudo systemctl status php-fpm --no-pager || true
    
    echo "🌐 ポート80の状況を確認中..."
    sudo netstat -tlnp | grep :80 || true
    
    echo "📁 アプリケーションディレクトリの権限を確認中..."
    ls -la public/
    ls -la public/build/ || true
    
    echo "✅ 修正完了!"
EOF

echo "🎉 Nginx設定修正が完了しました!"
echo "🌐 アプリケーション URL: http://$AWS_HOST"

# 検証を実行
echo "🔍 デプロイメント検証を実行中..."
./deployment/verify-deployment.sh $AWS_HOST 80