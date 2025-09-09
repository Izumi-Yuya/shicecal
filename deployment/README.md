# Shise-Cal デプロイメントガイド

このディレクトリには、施設管理システム（Shise-Cal）をデプロイするためのスクリプトとドキュメントが含まれています。

## ファイル構成

### デプロイメントスクリプト
- `deploy-to-aws.sh` - AWS EC2への本番デプロイスクリプト
- `aws-free-deploy.sh` - AWS無料テスト環境デプロイスクリプト
- `free-test-deploy.sh` - ローカル無料テスト環境デプロイスクリプト
- `staging-deploy.sh` - ステージング環境デプロイスクリプト
- `laravel-deployment.sh` - Laravel アプリケーションデプロイスクリプト

### 検証・テストスクリプト
- `verify-deployment.sh` - デプロイメント検証スクリプト
- `test-scripts.sh` - 環境テストスクリプト
- `run-seeders.sh` - データベースシーダー実行スクリプト

### 設定ファイル
- `nginx-config.conf` - Nginx設定ファイル
- `aws-webserver-setup.sh` - サーバー初期設定スクリプト

### ドキュメント
- `aws-test-documentation.md` - 詳細な構築・テスト手順書

## クイックスタート

### 1. ローカル無料テスト環境
```bash
# 無料テスト環境のデプロイ
chmod +x deployment/free-test-deploy.sh
./deployment/free-test-deploy.sh

# サーバー起動
php artisan serve
```

### 2. ステージング環境
```bash
# ステージング環境のデプロイ
chmod +x deployment/staging-deploy.sh
./deployment/staging-deploy.sh

# 検証実行
chmod +x deployment/verify-deployment.sh
./deployment/verify-deployment.sh localhost 8001
```

### 3. AWS無料テスト環境
```bash
# AWS無料テスト環境のデプロイ
chmod +x deployment/aws-free-deploy.sh
./deployment/aws-free-deploy.sh
```

### 4. AWS本番環境
```bash
# AWS EC2への本番デプロイ
chmod +x deployment/deploy-to-aws.sh
./deployment/deploy-to-aws.sh
```

## 新しいアセット構造について

このプロジェクトは新しいアセット構造を使用しています：

### CSS構造
- `resources/css/shared/` - 共通CSSファイル
- `resources/css/pages/` - ページ固有CSSファイル
- `resources/css/app.css` - メインアプリケーションCSS

### JavaScript構造
- `resources/js/shared/` - 共通JavaScriptモジュール
- `resources/js/modules/` - 機能別JavaScriptモジュール
- `resources/js/app.js` - メインアプリケーションJS

### ビルドプロセス
- Viteを使用したモダンなアセットビルド
- ES6モジュールサポート
- 自動的なコード分割とチャンク生成
- 本番用最適化（圧縮、キャッシュバスティング）

### デプロイメント要件
- Node.js 18以上
- npm ci --production=false（ビルドに開発依存関係が必要）
- npm run build（Viteビルド）
- public/build/manifest.json の存在確認

## 詳細情報

詳細な手順については `aws-test-documentation.md` を参照してください。

## デプロイメント検証

デプロイ後は必ず検証スクリプトを実行してください：

```bash
# デプロイメント検証
./deployment/verify-deployment.sh [host] [port]

# 例：ローカル環境
./deployment/verify-deployment.sh localhost 8000

# 例：ステージング環境
./deployment/verify-deployment.sh localhost 8001
```

検証項目：
- ✅ アセットファイル検証（CSS/JS）
- ✅ データベース接続検証
- ✅ Laravel設定検証
- ✅ HTTP接続検証
- ✅ アセット読み込み検証
- ✅ パフォーマンス検証
- ✅ テスト実行

## 注意事項

### セキュリティ
- 本番環境では必ずSSL証明書を設定してください
- セキュリティグループの設定を確認してください
- データベース認証情報を適切に設定してください

### アセット管理
- デプロイ前に必ず `npm run build` を実行してください
- `public/build/manifest.json` の存在を確認してください
- アセットファイルが正しく生成されていることを確認してください

### パフォーマンス
- 本番環境では Laravel キャッシュを有効にしてください
- Viteビルドによる最適化されたアセットを使用してください
- 不要な開発依存関係は本番環境にインストールしないでください