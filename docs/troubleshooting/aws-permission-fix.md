# AWS Permission Fix Documentation

## 問題の概要

AWSサーバー上でLaravelアプリケーションにログインできない問題が発生しました。エラーログには以下のような権限エラーが表示されていました：

```
The stream or file "/home/ec2-user/shicecal/storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied
file_put_contents(/home/ec2-user/shicecal/storage/framework/sessions/...): Failed to open stream: Permission denied
```

## 原因

- `storage`ディレクトリおよび`bootstrap/cache`ディレクトリへの書き込み権限が不適切
- Webサーバー（Apache）がログファイルやセッションファイルを作成・更新できない状態
- ファイル所有者とWebサーバーユーザーの権限設定が不一致

## 解決方法

### 1. 権限修正スクリプトの作成

`scripts/fix-permissions.sh`スクリプトを作成し、以下の処理を自動化：

- ディレクトリ所有者の修正
- ファイル権限の適切な設定
- Webサーバーユーザーの自動検出
- 必要なディレクトリの作成

### 2. 実行した修正内容

```bash
# スクリプトの実行
./scripts/fix-permissions.sh
```

#### 具体的な修正内容：

1. **ディレクトリ所有者の修正**
   ```bash
   sudo chown -R ec2-user:ec2-user /home/ec2-user/shicecal
   ```

2. **基本権限の設定**
   ```bash
   find /home/ec2-user/shicecal -type f -exec chmod 644 {} \;
   find /home/ec2-user/shicecal -type d -exec chmod 755 {} \;
   ```

3. **書き込み可能ディレクトリの権限設定**
   ```bash
   chmod -R 775 /home/ec2-user/shicecal/storage
   chmod -R 775 /home/ec2-user/shicecal/bootstrap/cache
   ```

4. **Webサーバーユーザーへの所有権移譲**
   ```bash
   sudo chown -R apache:apache /home/ec2-user/shicecal/storage
   sudo chown -R apache:apache /home/ec2-user/shicecal/bootstrap/cache
   ```

5. **ユーザーグループの追加**
   ```bash
   sudo usermod -a -G apache ec2-user
   ```

### 3. 必要なディレクトリの作成

スクリプトは以下のディレクトリが存在することを確認し、必要に応じて作成：

- `storage/logs`
- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/app/public`
- `bootstrap/cache`

## 結果

修正後、以下が正常に動作することを確認：

- ✅ Laravel application is running
- ✅ Database connection is working
- ✅ Web server is responding
- ✅ Log file creation successful
- ✅ Session management working

## 今後の予防策

### デプロイメントスクリプトの改善

`scripts/aws-deploy.sh`に権限設定を組み込み済み：

```bash
# Set permissions
print_status "INFO" "Setting file permissions..."
ssh_exec "cd $PROJECT_PATH && chmod -R 755 storage bootstrap/cache"
ssh_exec "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache" || true
```

### 定期的なヘルスチェック

```bash
# ヘルスチェックの実行
./scripts/aws-deploy.sh health
```

## トラブルシューティング

### 権限エラーが再発した場合

1. 権限修正スクリプトを再実行：
   ```bash
   ./scripts/fix-permissions.sh
   ```

2. 手動での権限確認：
   ```bash
   ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64
   ls -la /home/ec2-user/shicecal/storage/
   ```

3. Webサーバーの再起動：
   ```bash
   sudo systemctl restart nginx
   sudo systemctl restart httpd  # Apache使用時
   ```

### ログファイルの確認

```bash
# Laravel ログの確認
tail -f /home/ec2-user/shicecal/storage/logs/laravel.log

# Webサーバーログの確認
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/httpd/error_log  # Apache使用時
```

## 関連ファイル

- `scripts/fix-permissions.sh` - 権限修正スクリプト
- `scripts/aws-deploy.sh` - デプロイメントスクリプト
- `aws-server-config.sh` - サーバー設定ファイル

## 注意事項

- Amazon LinuxではWebサーバーユーザーは通常`apache`または`nginx`
- Ubuntu/Debianでは`www-data`が一般的
- スクリプトは自動的にWebサーバーユーザーを検出して適用

## 修正日時

- **修正日**: 2025年9月18日
- **修正者**: システム管理者
- **確認済み**: アプリケーション正常動作確認済み