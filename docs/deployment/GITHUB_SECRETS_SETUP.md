# GitHub Secrets Setup Guide

このドキュメントでは、Shise-CalプロジェクトのCI/CDパイプラインに必要なGitHub Secretsの設定方法を説明します。

## 必要なSecrets

### AWS デプロイメント用

#### Production環境
- `AWS_HOST`: 本番環境のEC2インスタンスのIPアドレス
  - 例: `35.75.1.64`
- `AWS_USERNAME`: EC2インスタンスのユーザー名
  - 例: `ec2-user`
- `AWS_PRIVATE_KEY`: EC2インスタンスへのSSH接続用の秘密鍵
  - PEMファイルの内容をそのまま貼り付け

#### Staging環境（オプション）
- `AWS_STAGING_HOST`: ステージング環境のEC2インスタンスのIPアドレス
- `AWS_STAGING_USERNAME`: ステージング環境のユーザー名
- `AWS_STAGING_PRIVATE_KEY`: ステージング環境のSSH秘密鍵

### アプリケーションURL
- `AWS_PROD_URL`: 本番環境のURL
  - 例: `http://35.75.1.64`
- `AWS_STAGING_URL`: ステージング環境のURL（オプション）

### 通知用（オプション）
- `SLACK_WEBHOOK_URL`: Slack通知用のWebhook URL
- `SMTP_USERNAME`: メール通知用のSMTPユーザー名
- `SMTP_PASSWORD`: メール通知用のSMTPパスワード
- `DEVOPS_EMAIL`: DevOpsチームのメールアドレス

## Secretsの設定方法

### 1. GitHubリポジトリでの設定

1. GitHubリポジトリのページに移動
2. `Settings` タブをクリック
3. 左サイドバーの `Secrets and variables` → `Actions` をクリック
4. `New repository secret` ボタンをクリック
5. Secret名と値を入力して `Add secret` をクリック

### 2. 現在の設定状況確認

以下のコマンドで現在の設定を確認できます：

```bash
# AWS接続テスト
./scripts/check-aws-deployment.sh

# GitHub Secrets確認（リポジトリ管理者のみ）
gh secret list
```

### 3. 最小限の設定

デプロイメントを開始するために最低限必要なSecrets：

```
AWS_HOST=35.75.1.64
AWS_USERNAME=ec2-user
AWS_PRIVATE_KEY=[PEMファイルの内容]
AWS_PROD_URL=http://35.75.1.64
```

## セキュリティ注意事項

1. **秘密鍵の管理**
   - SSH秘密鍵は絶対に公開しない
   - 定期的にローテーションを検討

2. **アクセス制限**
   - EC2セキュリティグループでSSHアクセスを制限
   - 必要最小限のIPアドレスからのみアクセス許可

3. **環境分離**
   - 本番環境とステージング環境で異なる認証情報を使用
   - 環境ごとに適切なアクセス制御を設定

## トラブルシューティング

### SSH接続エラー
```bash
# 接続テスト
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "echo 'Connection OK'"

# 鍵の権限確認
chmod 600 ~/Shise-Cal-test-key.pem
```

### デプロイメントエラー
1. GitHub Actions のログを確認
2. EC2インスタンスのログを確認
3. Nginx/PHP-FPMのステータス確認

## 設定完了後の確認

1. **手動デプロイテスト**
   ```bash
   # GitHub Actionsから手動実行
   # Repository → Actions → "Simple Deploy" → "Run workflow"
   ```

2. **アプリケーション動作確認**
   ```bash
   curl -I http://35.75.1.64
   ```

3. **ログ確認**
   ```bash
   ./scripts/check-aws-deployment.sh
   ```