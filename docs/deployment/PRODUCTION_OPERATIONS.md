# Shise-Cal 本番運用ガイド

## 📋 概要

このドキュメントでは、Shise-Cal施設管理システムの本番環境における日常運用手順を説明します。

## 🚀 デプロイメント

### 自動デプロイ
```bash
# productionブランチにプッシュで自動デプロイ
git checkout production
git merge main
git push origin production
```

### 手動デプロイ
```bash
# 緊急時の手動デプロイ
./scripts/manual-deploy.sh
```

### デプロイ確認
```bash
# デプロイ後の確認
./scripts/production-health-check.sh
```

## 📊 監視

### 日常監視
```bash
# システム状態確認
./scripts/check-production-status.sh

# 詳細な健全性チェック
./scripts/production-health-check.sh
```

### 監視項目
- **HTTP応答**: 200/302ステータス
- **応答時間**: 3秒以内
- **CPU使用率**: 80%以下
- **メモリ使用率**: 85%以下
- **ディスク使用率**: 85%以下
- **サービス状態**: Nginx, PHP-FPM
- **データベース接続**: 正常応答
- **エラーログ**: 異常なエラー増加

### アラート対応

#### CPU使用率高騰
```bash
# プロセス確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "top -n 1"

# 対処法
# 1. 不要なプロセス終了
# 2. PHP-FPM再起動: sudo systemctl restart php-fpm
# 3. 必要に応じてサーバー再起動
```

#### メモリ不足
```bash
# メモリ使用状況確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "free -h"

# 対処法
# 1. キャッシュクリア: php artisan cache:clear
# 2. PHP-FPM再起動
# 3. 不要なプロセス終了
```

#### サービス停止
```bash
# サービス状態確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "systemctl status nginx php-fpm"

# サービス再起動
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "sudo systemctl restart nginx php-fpm"
```

## 💾 バックアップ

### 定期バックアップ
```bash
# 手動バックアップ実行
./scripts/backup-production.sh
```

### バックアップ確認
```bash
# ローカルバックアップ一覧
ls -la ./backups/

# リモートバックアップ一覧
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "ls -la /home/ec2-user/backups/"
```

### 復元手順
```bash
# 1. バックアップファイル確認
ls -la ./backups/shicecal_backup_*.tar.gz

# 2. リモートサーバーにアップロード
scp -i ~/Shise-Cal-test-key.pem ./backups/shicecal_backup_YYYYMMDD_HHMMSS.tar.gz ec2-user@35.75.1.64:/home/ec2-user/

# 3. サーバーで復元実行
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64
cd /home/ec2-user
tar -xzf shicecal_backup_YYYYMMDD_HHMMSS.tar.gz

# 4. 必要なファイルを復元
# .env ファイル
cp shicecal_backup_YYYYMMDD_HHMMSS/.env /home/ec2-user/shicecal/

# データベース（SQLiteの場合）
cp shicecal_backup_YYYYMMDD_HHMMSS/database.sqlite /home/ec2-user/shicecal/database/

# アップロードファイル
cp -r shicecal_backup_YYYYMMDD_HHMMSS/public_uploads/* /home/ec2-user/shicecal/public/uploads/

# 5. サービス再起動
sudo systemctl restart nginx php-fpm
```

## 🔧 メンテナンス

### 定期メンテナンス（月次）

#### 1. システム更新
```bash
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64
sudo dnf update -y
```

#### 2. ログローテーション
```bash
# Laravelログのアーカイブ
cd /home/ec2-user/shicecal
cp storage/logs/laravel.log storage/logs/laravel-$(date +%Y%m).log
> storage/logs/laravel.log
```

#### 3. 不要ファイル削除
```bash
# 古いバックアップ削除（30日以上）
find /home/ec2-user/backups -name "*.tar.gz" -mtime +30 -delete

# 一時ファイル削除
php artisan cache:clear
php artisan view:clear
```

#### 4. パフォーマンス最適化
```bash
# データベース最適化（MySQLの場合）
# mysql -u username -p -e "OPTIMIZE TABLE facilities, land_infos, building_infos;"

# キャッシュ再構築
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 緊急メンテナンス

#### アプリケーション停止
```bash
# メンテナンスモード有効
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "cd /home/ec2-user/shicecal && php artisan down"
```

#### アプリケーション復旧
```bash
# メンテナンスモード解除
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "cd /home/ec2-user/shicecal && php artisan up"
```

## 🔍 トラブルシューティング

### よくある問題と対処法

#### 1. 500エラー
```bash
# エラーログ確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "tail -50 /home/ec2-user/shicecal/storage/logs/laravel.log"

# 権限確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "ls -la /home/ec2-user/shicecal/storage"

# 権限修正
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "chmod -R 775 /home/ec2-user/shicecal/storage"
```

#### 2. アセット読み込みエラー
```bash
# ビルドファイル確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "ls -la /home/ec2-user/shicecal/public/build/"

# アセット再ビルド
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "cd /home/ec2-user/shicecal && npm run build"
```

#### 3. データベース接続エラー
```bash
# データベース接続テスト
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "cd /home/ec2-user/shicecal && php artisan tinker --execute='DB::connection()->getPdo(); echo \"DB OK\";'"

# .env設定確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "cd /home/ec2-user/shicecal && grep DB_ .env"
```

## 📈 パフォーマンス監視

### 応答時間監視
```bash
# 応答時間測定
curl -o /dev/null -s -w "Response time: %{time_total}s\n" http://35.75.1.64
```

### リソース使用量監視
```bash
# リアルタイム監視
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "htop"

# ディスク使用量
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "df -h"
```

## 🔐 セキュリティ

### セキュリティチェック
```bash
# 不正アクセス確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "sudo tail -100 /var/log/nginx/access.log | grep -E '40[0-9]|50[0-9]'"

# SSH接続ログ確認
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "sudo tail -50 /var/log/secure"
```

### セキュリティ更新
```bash
# セキュリティパッチ適用
ssh -i ~/Shise-Cal-test-key.pem ec2-user@35.75.1.64 "sudo dnf update --security -y"
```

## 📞 緊急連絡先

### エスカレーション手順
1. **レベル1**: 自動復旧試行（再起動等）
2. **レベル2**: 開発チーム連絡
3. **レベル3**: システム管理者連絡
4. **レベル4**: 経営陣報告

### 連絡先
- **開発チーム**: [開発チームの連絡先]
- **システム管理者**: [システム管理者の連絡先]
- **AWS サポート**: [AWSサポート連絡先]

## 📚 関連ドキュメント

- [GitHub Secrets設定ガイド](./GITHUB_SECRETS_SETUP.md)
- [デプロイメント手順](./DEPLOYMENT_GUIDE.md)
- [アーキテクチャ概要](../architecture/SYSTEM_ARCHITECTURE.md)
- [API仕様書](../api/API_DOCUMENTATION.md)