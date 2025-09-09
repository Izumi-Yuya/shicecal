#!/bin/bash

# 環境設定セットアップスクリプト

echo "🚀 環境設定をセットアップ中..."

# .envファイルの作成
if [ ! -f .env ]; then
    echo "📄 .envファイルを作成中..."
    cp .env.example .env
    php artisan key:generate
    echo "✅ .envファイルが作成されました"
else
    echo "ℹ️  .envファイルは既に存在します"
fi

# テスト用.envファイルの作成
if [ ! -f .env.testing ]; then
    echo "📄 .env.testingファイルを作成中..."
    cp .env.testing.example .env.testing
    php artisan key:generate --env=testing
    echo "✅ .env.testingファイルが作成されました"
else
    echo "ℹ️  .env.testingファイルは既に存在します"
fi

# データベースの初期化
echo "🗄️  データベースを初期化中..."
php artisan migrate:fresh --seed

echo "✅ 環境設定のセットアップが完了しました"
echo ""
echo "次のステップ:"
echo "1. .envファイルを確認し、必要に応じて設定を調整してください"
echo "2. php artisan serve でアプリケーションを起動してください"