# AWS テスト環境デプロイメントガイド

このディレクトリには、施設管理システムをAWSテスト環境にデプロイするためのスクリプトとドキュメントが含まれています。

## ファイル構成

- `aws-test-documentation.md` - 詳細な構築・テスト手順書
- `aws-webserver-setup.sh` - サーバー初期設定スクリプト
- `laravel-deployment.sh` - Laravel アプリケーションデプロイスクリプト
- `nginx-config.conf` - Nginx設定ファイル
- `test-scripts.sh` - 環境テストスクリプト

## クイックスタート

### 1. EC2インスタンスでの初期設定
```bash
# スクリプトをEC2にアップロード
scp -i your-key.pem deployment/aws-webserver-setup.sh ubuntu@your-ec2-ip:~/

# EC2にSSH接続
ssh -i your-key.pem ubuntu@your-ec2-ip

# 初期設定実行
chmod +x aws-webserver-setup.sh
./aws-webserver-setup.sh
```

### 2. Laravel プロジェクトのデプロイ
```bash
# プロジェクトファイルをアップロード
scp -i your-key.pem -r . ubuntu@your-ec2-ip:/tmp/facility-management/

# デプロイスクリプト実行
chmod +x laravel-deployment.sh
./laravel-deployment.sh
```

### 3. Nginx設定
```bash
# 設定ファイルをコピー
sudo cp nginx-config.conf /etc/nginx/sites-available/facility-management

# サイト有効化
sudo ln -s /etc/nginx/sites-available/facility-management /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 4. テスト実行
```bash
# テストスクリプト実行
chmod +x test-scripts.sh
./test-scripts.sh
```

## 詳細情報

詳細な手順については `aws-test-documentation.md` を参照してください。

## 注意事項

- 本番環境では必ずSSL証明書を設定してください
- セキュリティグループの設定を確認してください
- データベース認証情報を適切に設定してください