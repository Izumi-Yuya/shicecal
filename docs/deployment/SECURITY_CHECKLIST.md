# 本番環境デプロイ セキュリティチェックリスト

## 概要

本番環境へのデプロイ前に実施すべきセキュリティチェック項目です。特に開発・テスト用の機能が本番環境に残らないよう注意が必要です。

## 🚨 必須セキュリティチェック

### 1. 開発・テスト用ルートの削除

**⚠️ 最重要**: 以下のファイルが本番環境に含まれていないことを確認してください：

```bash
# 削除が必要なファイル
routes/test.php
```

**確認方法:**
```bash
# ファイルの存在確認
ls -la routes/test.php

# ファイルが存在する場合は削除
rm routes/test.php
```

**影響:**
- `routes/test.php` には認証をバイパスするテストルートが含まれています
- このファイルが本番環境に残ると、認証なしで施設情報にアクセス可能になります
- **セキュリティリスク: 高**

### 2. 環境設定の確認

```bash
# .env ファイルの設定確認
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# データベース設定
DB_CONNECTION=mysql
DB_HOST=your-production-db-host
DB_DATABASE=your-production-database
DB_USERNAME=your-production-user
DB_PASSWORD=your-secure-password
```

### 3. デバッグ機能の無効化

```bash
# Laravel デバッグモードの無効化
APP_DEBUG=false

# ログレベルの設定
LOG_LEVEL=error
```

### 4. 不要なファイル・ディレクトリの削除

```bash
# 開発用ファイルの削除
rm -rf tests/
rm -rf .git/
rm -f phpunit.xml
rm -f webpack.mix.js
rm -f package-lock.json
rm -f composer.lock

# テスト用データベースファイルの削除
rm -f database/testing.sqlite
rm -f database/database.sqlite
```

## 📋 デプロイ前チェックリスト

### ファイル・設定チェック

- [ ] `routes/test.php` ファイルが削除されている
- [ ] `.env` ファイルで `APP_ENV=production` が設定されている
- [ ] `.env` ファイルで `APP_DEBUG=false` が設定されている
- [ ] 本番用データベース接続情報が正しく設定されている
- [ ] SSL証明書が正しく設定されている
- [ ] ファイルアップロード先が本番用ストレージに設定されている

### セキュリティ設定チェック

- [ ] HTTPS通信が強制されている
- [ ] セキュリティヘッダーが設定されている
- [ ] CSRFトークンが有効になっている
- [ ] セッション設定が本番環境用になっている
- [ ] ファイルアップロード制限が適切に設定されている

### 権限・アクセス制御チェック

- [ ] ファイル・ディレクトリの権限が適切に設定されている
- [ ] Webサーバーユーザーの権限が最小限に制限されている
- [ ] データベースユーザーの権限が適切に制限されている
- [ ] 管理者アカウントのパスワードが強固に設定されている

## 🔧 デプロイ後の確認

### 1. テストルートの無効化確認

```bash
# テストルートにアクセスして404エラーが返されることを確認
curl -I https://your-domain.com/test-facility/1
# 期待される結果: HTTP/1.1 404 Not Found
```

### 2. 認証機能の確認

```bash
# 未認証でのアクセスがリダイレクトされることを確認
curl -I https://your-domain.com/facilities
# 期待される結果: HTTP/1.1 302 Found (ログインページへリダイレクト)
```

### 3. HTTPS通信の確認

```bash
# HTTPS通信が強制されることを確認
curl -I http://your-domain.com/
# 期待される結果: HTTP/1.1 301 Moved Permanently (HTTPSへリダイレクト)
```

## 🚨 緊急時の対応

### テストルートが本番環境で発見された場合

1. **即座にファイルを削除**
   ```bash
   rm routes/test.php
   ```

2. **Webサーバーの設定をリロード**
   ```bash
   sudo systemctl reload nginx
   # または
   sudo systemctl reload apache2
   ```

3. **Laravel設定キャッシュをクリア**
   ```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

4. **アクセスログの確認**
   ```bash
   # 不正アクセスの有無を確認
   grep "test-facility" /var/log/nginx/access.log
   ```

## 📞 連絡先

セキュリティに関する問題が発見された場合：

- **緊急連絡先**: [システム管理者連絡先]
- **報告先**: [セキュリティ担当者メールアドレス]

## 📚 関連ドキュメント

- [本番環境デプロイガイド](PRODUCTION.md)
- [セキュリティ実装詳細](../implementation/SECURITY_IMPLEMENTATION.md)
- [ルートリファレンス](../routes/facility-routes-reference.md)