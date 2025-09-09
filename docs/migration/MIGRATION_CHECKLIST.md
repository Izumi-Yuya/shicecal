# プロジェクト簡素化 移行チェックリスト

## 概要

このチェックリストは、Shise-Cal v1.x から v2.0.0（プロジェクト簡素化版）への移行作業を確実に実行するためのガイドです。

## 事前準備

### 📋 システム要件確認
- [ ] **PHP 8.2+** が利用可能
- [ ] **Laravel 9.x** 互換性確認
- [ ] **Node.js 18+** および **npm** が利用可能
- [ ] **MySQL 8.0** または **SQLite** データベース
- [ ] **Redis** キャッシュサーバー（推奨）

### 🔒 バックアップ作成
- [ ] **データベース完全バックアップ**
  ```bash
  # MySQL の場合
  mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
  
  # Laravel バックアップパッケージの場合
  php artisan backup:run
  ```

- [ ] **ファイルシステムバックアップ**
  ```bash
  # アプリケーションファイル
  tar -czf app_backup_$(date +%Y%m%d_%H%M%S).tar.gz .
  
  # ストレージファイル
  tar -czf storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/
  ```

- [ ] **設定ファイルバックアップ**
  ```bash
  cp .env .env.backup
  cp -r config/ config_backup/
  ```

### 🏷️ バージョンタグ作成
- [ ] **現在のバージョンにタグ付け**
  ```bash
  git tag v1.9.9-pre-migration
  git push origin v1.9.9-pre-migration
  ```

## 移行作業

### Phase 1: 依存関係更新

#### Composer 依存関係
- [ ] **依存関係更新**
  ```bash
  composer update
  composer audit  # セキュリティチェック
  ```

- [ ] **新しい依存関係の確認**
  ```bash
  composer show --outdated
  ```

#### npm 依存関係
- [ ] **Node.js パッケージ更新**
  ```bash
  npm update
  npm audit  # セキュリティチェック
  npm audit fix  # 自動修正可能な脆弱性の修正
  ```

- [ ] **Vite 設定確認**
  ```bash
  # vite.config.js の存在確認
  ls -la vite.config.js
  
  # ビルドテスト
  npm run build
  ```

### Phase 2: データベース移行

#### マイグレーション実行
- [ ] **マイグレーション状態確認**
  ```bash
  php artisan migrate:status
  ```

- [ ] **新しいマイグレーション実行**
  ```bash
  php artisan migrate
  ```

- [ ] **データ整合性確認**
  ```bash
  # 基本的なデータ確認
  php artisan tinker
  # > App\Models\Facility::count()
  # > App\Models\LandInfo::count()
  # > App\Models\User::count()
  ```

### Phase 3: 設定ファイル更新

#### 環境設定
- [ ] **.env ファイル更新**
  ```bash
  # 新しい設定項目の追加確認
  diff .env.example .env
  
  # 必要に応じて新しい設定を追加
  ```

- [ ] **設定キャッシュクリア**
  ```bash
  php artisan config:clear
  php artisan config:cache
  ```

#### ルート設定
- [ ] **ルートキャッシュ更新**
  ```bash
  php artisan route:clear
  php artisan route:cache
  ```

- [ ] **ルート一覧確認**
  ```bash
  php artisan route:list | grep -E "(facilities|export|comments)"
  ```

### Phase 4: アセット更新

#### CSS/JavaScript ビルド
- [ ] **アセットビルド**
  ```bash
  npm run build
  ```

- [ ] **ビルド成果物確認**
  ```bash
  ls -la public/build/
  ```

- [ ] **アセットバージョニング確認**
  ```bash
  # manifest.json の存在確認
  cat public/build/manifest.json
  ```

#### キャッシュクリア
- [ ] **全キャッシュクリア**
  ```bash
  php artisan optimize:clear
  ```

- [ ] **ビューキャッシュクリア**
  ```bash
  php artisan view:clear
  php artisan view:cache
  ```

### Phase 5: 機能テスト

#### 基本機能テスト
- [ ] **ユーザー認証**
  - [ ] ログイン機能
  - [ ] ログアウト機能
  - [ ] パスワードリセット

- [ ] **施設管理機能**
  - [ ] 施設一覧表示
  - [ ] 施設詳細表示
  - [ ] 施設作成
  - [ ] 施設編集
  - [ ] 施設削除

- [ ] **土地情報管理**
  - [ ] 土地情報表示 (`/facilities/{id}/land-info`)
  - [ ] 土地情報編集
  - [ ] 土地情報承認
  - [ ] 土地情報差戻し
  - [ ] 計算機能（単価、契約年数）

- [ ] **出力機能**
  - [ ] PDF単体出力 (`/export/pdf/single/{id}`)
  - [ ] PDFセキュア出力
  - [ ] PDF一括出力
  - [ ] CSV出力 (`/export/csv/generate`)
  - [ ] お気に入り機能

- [ ] **コメント機能**
  - [ ] コメント作成 (`/comments`)
  - [ ] コメント編集
  - [ ] コメント削除
  - [ ] ステータス管理
  - [ ] ダッシュボード表示

#### API エンドポイントテスト
- [ ] **新しいエンドポイント確認**
  ```bash
  # 土地情報API
  curl -X GET "http://localhost:8000/facilities/1/land-info" \
       -H "Accept: application/json" \
       -H "Authorization: Bearer {token}"
  
  # 出力API
  curl -X POST "http://localhost:8000/export/csv/generate" \
       -H "Content-Type: application/json" \
       -H "Authorization: Bearer {token}" \
       -d '{"facility_ids": [1,2,3], "fields": ["name", "address"]}'
  ```

- [ ] **旧エンドポイントリダイレクト確認**
  ```bash
  # 旧URLが新URLにリダイレクトされることを確認
  curl -I "http://localhost:8000/land-info/1"
  # Location: /facilities/1/land-info が返されることを確認
  ```

### Phase 6: パフォーマンステスト

#### 自動テスト実行
- [ ] **PHPUnit テスト**
  ```bash
  php artisan test
  ```

- [ ] **JavaScript テスト**
  ```bash
  npm run test
  ```

- [ ] **ブラウザテスト（Dusk）**
  ```bash
  php artisan dusk
  ```

#### パフォーマンス測定
- [ ] **ページ読み込み時間測定**
  ```bash
  # 主要ページの読み込み時間を測定
  curl -w "@curl-format.txt" -o /dev/null -s "http://localhost:8000/facilities"
  curl -w "@curl-format.txt" -o /dev/null -s "http://localhost:8000/export"
  ```

- [ ] **データベースクエリ分析**
  ```bash
  # クエリログを有効にして N+1 問題をチェック
  php artisan tinker
  # > DB::enableQueryLog();
  # > App\Models\Facility::with('landInfo')->get();
  # > DB::getQueryLog();
  ```

### Phase 7: セキュリティ確認

#### 脆弱性スキャン
- [ ] **依存関係脆弱性チェック**
  ```bash
  composer audit
  npm audit
  ```

- [ ] **セキュリティテスト実行**
  ```bash
  php artisan test tests/Feature/SecurityTest.php
  ```

#### アクセス制御確認
- [ ] **ロール別アクセステスト**
  - [ ] 管理者権限
  - [ ] 編集者権限
  - [ ] 承認者権限
  - [ ] 閲覧者権限

- [ ] **CSRF保護確認**
  ```bash
  # フォーム送信時のCSRFトークン確認
  ```

## 本番環境デプロイ

### デプロイ前チェック
- [ ] **ステージング環境での最終確認**
- [ ] **本番データベースバックアップ**
- [ ] **メンテナンスモード準備**
  ```bash
  php artisan down --message="システム更新中です。しばらくお待ちください。"
  ```

### デプロイ実行
- [ ] **コードデプロイ**
  ```bash
  git pull origin main
  composer install --no-dev --optimize-autoloader
  npm ci
  npm run build
  ```

- [ ] **データベース更新**
  ```bash
  php artisan migrate --force
  ```

- [ ] **キャッシュ更新**
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- [ ] **メンテナンスモード解除**
  ```bash
  php artisan up
  ```

### デプロイ後確認
- [ ] **基本機能動作確認**
- [ ] **エラーログ確認**
  ```bash
  tail -f storage/logs/laravel.log
  ```

- [ ] **パフォーマンス監視**
- [ ] **ユーザー通知**（必要に応じて）

## トラブルシューティング

### よくある問題と解決策

#### 1. ルートが見つからない (404エラー)
**症状**: 新しいルートにアクセスできない
**解決策**:
```bash
php artisan route:clear
php artisan route:cache
php artisan config:clear
```

#### 2. サービスクラスが見つからない
**症状**: `Class 'LandInfoService' not found`
**解決策**:
```bash
composer dump-autoload
php artisan config:clear
```

#### 3. CSS/JSが読み込まれない
**症状**: スタイルやJavaScriptが適用されない
**解決策**:
```bash
npm run build
php artisan view:clear
```

#### 4. データベース接続エラー
**症状**: `SQLSTATE[HY000] [2002] Connection refused`
**解決策**:
```bash
# データベース設定確認
php artisan config:show database.connections.mysql

# 接続テスト
php artisan tinker
# > DB::connection()->getPdo();
```

#### 5. 権限エラー
**症状**: ファイル書き込み権限エラー
**解決策**:
```bash
# ストレージ権限設定
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### ロールバック手順

#### 緊急時ロールバック
```bash
# 1. メンテナンスモード有効化
php artisan down

# 2. 前バージョンに戻す
git checkout v1.9.9-pre-migration

# 3. 依存関係復元
composer install
npm install
npm run build

# 4. データベース復元（必要に応じて）
mysql -u [username] -p [database] < backup_YYYYMMDD_HHMMSS.sql

# 5. キャッシュクリア
php artisan optimize:clear

# 6. メンテナンスモード解除
php artisan up
```

## 完了確認

### 最終チェックリスト
- [ ] **全機能が正常動作**
- [ ] **パフォーマンスが許容範囲内**
- [ ] **セキュリティ要件を満たしている**
- [ ] **エラーログに重大な問題がない**
- [ ] **ユーザーからの問題報告がない**

### ドキュメント更新
- [ ] **運用マニュアル更新**
- [ ] **API仕様書更新**
- [ ] **トラブルシューティングガイド更新**
- [ ] **チーム内での変更内容共有**

### 移行完了報告
- [ ] **移行作業完了報告書作成**
- [ ] **関係者への完了通知**
- [ ] **次回メンテナンス計画の策定**

---

## 連絡先・サポート

### 技術サポート
- **開発チーム**: development-team@company.com
- **システム管理者**: admin@company.com

### 緊急時連絡先
- **24時間サポート**: emergency@company.com
- **電話**: 03-XXXX-XXXX

### 関連ドキュメント
- [プロジェクト簡素化マイグレーションガイド](PROJECT_SIMPLIFICATION_GUIDE.md)
- [簡素化されたアーキテクチャガイド](../architecture/SIMPLIFIED_ARCHITECTURE.md)
- [API リファレンス](../api/API_REFERENCE.md)
- [トラブルシューティングガイド](../troubleshooting/)

---

**注意**: このチェックリストは包括的なガイドですが、環境によって追加の手順が必要な場合があります。不明な点がある場合は、必ず技術サポートに相談してください。