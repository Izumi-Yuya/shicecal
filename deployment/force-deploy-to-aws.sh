#!/bin/bash

# AWS 強制デプロイメントスクリプト（ローカル変更をリセット）
# 使用方法: ./deployment/force-deploy-to-aws.sh

set -e

# 設定
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
SSH_KEY="~/Shise-Cal-test-key.pem"
REMOTE_PATH="/home/ec2-user/shicecal"

echo "🚀 AWS EC2への強制デプロイを開始します..."

# ローカルでの事前チェック
echo "📋 ローカルでの事前チェック..."
if ! git diff --quiet; then
    echo "❌ 未コミットの変更があります。先にコミットしてください。"
    exit 1
fi

# 現在のブランチを確認
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 現在のブランチ: $CURRENT_BRANCH"

# productionブランチにプッシュ
echo "📤 GitHubにプッシュ中..."
git push origin $CURRENT_BRANCH

# AWS EC2に接続してデプロイ
echo "🔗 AWS EC2に接続して強制デプロイ中..."
ssh -i $SSH_KEY $AWS_USER@$AWS_HOST << EOF
    set -e
    cd $REMOTE_PATH
    
    echo "🔄 ローカル変更をリセット中..."
    git reset --hard HEAD
    git clean -fd
    
    echo "📥 最新コードを取得中..."
    git fetch origin
    git reset --hard origin/$CURRENT_BRANCH
    
    echo "📦 依存関係をインストール中..."
    composer install --no-dev --optimize-autoloader
    npm ci --production=false
    
    echo "🏗️ アセットをビルド中..."
    # Clear any existing build artifacts
    rm -rf public/build
    
    # Build assets with Vite
    npm run build
    
    # Verify build output
    if [ ! -d "public/build" ]; then
        echo "❌ アセットビルドが失敗しました"
        exit 1
    fi
    
    echo "✅ アセットビルド完了 - ファイル数: \$(find public/build -type f | wc -l)"
    
    echo "🗄️ データベースマイグレーション実行中..."
    php artisan migrate --force
    
    echo "🌱 シーダーを実行中..."
    php artisan db:seed --class=AdminUserSeeder --force
    php artisan db:seed --class=FacilitySeeder --force
    php artisan db:seed --class=LandInfoSeeder --force
    
    echo "⚡ キャッシュを最適化中..."
    # Clear existing caches first
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Rebuild optimized caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Verify asset manifest exists
    if [ ! -f "public/build/manifest.json" ]; then
        echo "❌ Viteマニフェストファイルが見つかりません"
        exit 1
    fi
    
    echo "✅ Viteマニフェスト確認完了"
    
    echo "🔄 サービスを再起動中..."
    sudo systemctl restart nginx
    sudo systemctl restart php-fpm
    
    echo "✅ デプロイ完了!"
EOF

echo "🎉 AWS EC2への強制デプロイが完了しました!"
echo "🌐 アプリケーション URL: http://$AWS_HOST"