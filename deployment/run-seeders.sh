#!/bin/bash

# AWS EC2でシーダーを実行するスクリプト
# 使用方法: ./deployment/run-seeders.sh

set -e

# 設定
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
SSH_KEY="~/Shise-Cal-test-key.pem"
REMOTE_PATH="/home/ec2-user/shicecal"

echo "🌱 AWS EC2でシーダーを実行します..."

# AWS EC2に接続してシーダー実行
ssh -i $SSH_KEY $AWS_USER@$AWS_HOST << EOF
    set -e
    cd $REMOTE_PATH
    
    echo "📍 現在のディレクトリ: \$(pwd)"
    echo "📋 Laravelバージョン: \$(php artisan --version)"
    
    echo "🗄️ データベース状況確認..."
    php artisan migrate:status
    
    echo "👥 管理者ユーザーシーダー実行中..."
    php artisan db:seed --class=AdminUserSeeder --force
    
    echo "🏢 施設シーダー実行中..."
    php artisan db:seed --class=FacilitySeeder --force
    
    echo "🏞️ 土地情報シーダー実行中..."
    php artisan db:seed --class=LandInfoSeeder --force
    
    echo "📊 データベース確認..."
    php artisan tinker --execute="
        echo 'ユーザー数: ' . App\\Models\\User::count() . PHP_EOL;
        echo '施設数: ' . App\\Models\\Facility::count() . PHP_EOL;
        echo '土地情報数: ' . App\\Models\\LandInfo::count() . PHP_EOL;
    "
    
    echo "✅ シーダー実行完了!"
EOF

echo "🎉 シーダーの実行が完了しました!"
echo "🌐 アプリケーション URL: http://$AWS_HOST"