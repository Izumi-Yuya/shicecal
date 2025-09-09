# Shise-Cal エンジニア引き継ぎガイド

## 📋 プロジェクト概要

**プロジェクト名**: Shise-Cal（シセカル）  
**パッケージ名**: `shise-cal/facility-management`  
**バージョン**: v2.0.0  
**最終更新**: 2025年9月9日  

### システム概要
施設情報を一元管理するWebアプリケーション。権限ベースのアクセス制御と承認フローにより、施設の基本情報・土地情報・修繕履歴などを効率的に管理します。

### 主要機能
- 🏢 **施設管理**: 基本情報・土地情報の登録・更新・削除
- 📄 **ファイル管理**: PDF書類のアップロード・ダウンロード
- 👥 **権限制御**: ロール別アクセス制御（管理者・編集者・承認者・閲覧者）
- ✅ **承認フロー**: 編集内容の承認・差戻し機能
- 📊 **出力機能**: PDF帳票・CSV出力
- 💬 **コメント機能**: 確認・修正依頼の記録・通知
- 📈 **修繕履歴**: 修繕対応の履歴管理

## 🏗️ 技術スタック

### バックエンド
- **フレームワーク**: Laravel 9.x
- **言語**: PHP 8.2+
- **データベース**: MySQL 8.0（開発時はSQLite）
- **キャッシュ**: Redis

### フロントエンド
- **CSS**: Bootstrap 5.1.3 + 機能別モジュール構成
- **JavaScript**: ES6 Modules + Vanilla JS
- **ビルドツール**: Vite 4.x
- **アイコン**: Font Awesome 6.0.0

### 開発ツール
- **コード品質**: Laravel Pint（PHP）、Vitest（JavaScript）
- **テスト**: PHPUnit、Vitest
- **コンテナ**: Docker（オプション）

## 🚀 開発環境セットアップ

### 必要な環境
```bash
# 必須要件
PHP 8.2+
Composer 2.x
Node.js 18+
MySQL 8.0+ または SQLite
```

### 初期セットアップ
```bash
# 1. リポジトリクローン
git clone <repository-url>
cd shise-cal

# 2. 依存関係インストール
composer install
npm install

# 3. 環境設定
cp .env.example .env
php artisan key:generate

# 4. データベースセットアップ
php artisan migrate --seed

# 5. アセットビルド
npm run build

# 6. 開発サーバー起動
php artisan serve  # http://localhost:8000
npm run dev        # フロントエンド開発サーバー
```

### テストユーザー
| ロール | メールアドレス | パスワード |
|--------|---------------|-----------|
| 管理者 | admin@shisecal.example.com | password |
| 編集者 | editor@shisecal.example.com | password |
| 承認者 | approver@shisecal.example.com | password |
| 閲覧者 | viewer@shisecal.example.com | password |

## 📁 プロジェクト構造（v2.0.0 簡素化版）

### 重要な変更点
2025年9月にプロジェクト構造を大幅に簡素化しました：
- **コントローラー**: 13個 → 8個（38%削減）
- **サービス**: 8個 → 5個（37%削減）
- **フロントエンド**: CSS/JavaScriptをBladeから完全分離

### 現在のアーキテクチャ

#### コントローラー構成（8個）
```
app/Http/Controllers/
├── AuthController.php              # 認証機能
├── FacilityController.php          # 施設管理（土地情報統合）
├── CommentController.php           # コメント機能（統合）
├── ExportController.php            # PDF・CSV出力（統合）
├── NotificationController.php      # 通知機能
├── MyPageController.php            # マイページ機能
├── MaintenanceController.php       # 修繕履歴管理
├── AnnualConfirmationController.php # 年次確認機能
└── Admin/
    ├── UserController.php          # ユーザー管理
    └── SettingsController.php      # システム設定
```

#### サービス構成（5個）
```
app/Services/
├── FacilityService.php             # 施設・土地情報ビジネスロジック
├── ExportService.php               # PDF・CSV出力処理
├── ActivityLogService.php          # アクティビティログ管理
├── NotificationService.php         # 通知処理
└── PerformanceMonitoringService.php # パフォーマンス監視
```

#### フロントエンド構成
```
resources/
├── css/
│   ├── shared/                     # 共通スタイル
│   │   ├── variables.css           # CSS変数
│   │   ├── base.css               # ベーススタイル
│   │   ├── layout.css             # レイアウト
│   │   ├── components.css         # コンポーネント
│   │   └── utilities.css          # ユーティリティ
│   └── pages/                      # ページ固有スタイル
│       ├── facilities.css          # 施設管理
│       ├── export.css             # 出力機能
│       ├── comments.css           # コメント
│       └── admin.css              # 管理機能
├── js/
│   ├── modules/                    # 機能別モジュール
│   │   ├── facilities.js           # 施設管理
│   │   ├── export.js              # 出力機能
│   │   ├── comments.js            # コメント
│   │   └── admin.js               # 管理機能
│   └── shared/                     # 共通ユーティリティ
│       ├── utils.js               # ユーティリティ関数
│       ├── api.js                 # API通信
│       ├── validation.js          # バリデーション
│       └── components.js          # UIコンポーネント
└── views/
    ├── facilities/                 # 施設管理ビュー
    ├── export/                     # 出力機能ビュー
    ├── comments/                   # コメント機能ビュー
    └── layouts/                    # レイアウトテンプレート
```

## 🔧 重要なコマンド

### 開発コマンド
```bash
# 開発サーバー起動
php artisan serve                   # バックエンド（ポート8000）
npm run dev                        # フロントエンド開発サーバー

# データベース操作
php artisan migrate                # マイグレーション実行
php artisan migrate:fresh --seed   # DB初期化＋テストデータ
php artisan db:seed                # テストデータのみ

# テスト実行
php artisan test                   # PHPテスト
php artisan test --coverage        # カバレッジ付き
npm run test                       # JavaScriptテスト
npm run test:watch                 # ウォッチモード

# アセットビルド
npm run build                      # 本番用ビルド
npm run dev                        # 開発用ビルド（ウォッチ）

# キャッシュ操作
php artisan config:cache           # 設定キャッシュ
php artisan route:cache            # ルートキャッシュ
php artisan view:cache             # ビューキャッシュ
php artisan optimize:clear         # 全キャッシュクリア
```

### Docker使用時（オプション）
```bash
make setup                         # 初期セットアップ
make start                         # コンテナ起動
make shell                         # アプリコンテナアクセス
make test                          # テスト実行
make logs                          # ログ確認
```

## 📊 データベース構造

### 主要テーブル
```sql
-- ユーザー管理
users                              # ユーザー情報
├── id, name, email, role
├── created_at, updated_at
└── password_reset_tokens

-- 施設管理
facilities                         # 施設基本情報
├── id, facility_name, office_code
├── company_name, status
├── created_by, updated_by
└── created_at, updated_at

land_info                          # 土地情報
├── id, facility_id
├── purchase_price, land_area
├── unit_price, contract_start_date
├── status, approved_by
└── created_at, updated_at

-- コメント・通知
facility_comments                  # 施設コメント
├── id, facility_id, user_id
├── comment, status, assigned_to
└── created_at, updated_at

notifications                      # 通知
├── id, user_id, type, title
├── message, read_at
└── created_at, updated_at

-- その他
maintenance_histories              # 修繕履歴
annual_confirmations              # 年次確認
activity_logs                     # アクティビティログ
export_favorites                  # 出力お気に入り
```

### 主要リレーション
```php
// Facility Model
public function landInfo()         # 1対1
public function comments()         # 1対多
public function maintenanceHistories() # 1対多
public function creator()          # 多対1（User）
public function updater()          # 多対1（User）

// User Model
public function facilities()       # 1対多
public function comments()         # 1対多
public function notifications()   # 1対多
```

## 🛣️ 主要ルート構造

### 認証
```
GET|POST /login                    # ログイン
GET|POST /register                 # 登録
POST /logout                       # ログアウト
```

### 施設管理（土地情報統合）
```
GET    /facilities                 # 施設一覧
GET    /facilities/create          # 施設作成フォーム
POST   /facilities                 # 施設作成
GET    /facilities/{facility}      # 施設詳細
GET    /facilities/{facility}/edit # 施設編集フォーム
PUT    /facilities/{facility}      # 施設更新
DELETE /facilities/{facility}      # 施設削除

# 土地情報（統合）
GET    /facilities/{facility}/land-info      # 土地情報表示
PUT    /facilities/{facility}/land-info      # 土地情報更新
POST   /facilities/{facility}/land-info/approve  # 承認
POST   /facilities/{facility}/land-info/reject   # 差戻し
```

### 出力機能（PDF・CSV統合）
```
GET    /export                     # 出力メニュー
POST   /export/pdf/single/{facility}  # PDF単体出力
POST   /export/pdf/batch          # PDF一括出力
POST   /export/csv/generate       # CSV出力
GET    /export/favorites          # お気に入り管理
```

### コメント機能（統合）
```
GET    /comments                   # コメント一覧
POST   /comments                   # コメント作成
GET    /comments/dashboard         # ステータスダッシュボード
PUT    /comments/{comment}         # コメント更新
DELETE /comments/{comment}         # コメント削除
```

### 管理機能
```
GET    /admin/users                # ユーザー管理
GET    /admin/settings             # システム設定
```

## 🧪 テスト構造

### テストファイル構成
```
tests/
├── Feature/                       # 機能テスト
│   ├── FunctionalityValidationTest.php  # 基本機能検証
│   ├── EndToEndFunctionalityTest.php    # E2Eテスト
│   ├── FacilityControllerTest.php       # 施設管理
│   ├── ExportControllerTest.php         # 出力機能
│   └── CommentControllerTest.php        # コメント機能
├── Unit/                          # 単体テスト
│   ├── Services/
│   │   ├── FacilityServiceTest.php      # 施設サービス
│   │   ├── ExportServiceTest.php        # 出力サービス
│   │   └── ActivityLogServiceTest.php   # ログサービス
│   └── Models/                    # モデルテスト
└── js/                            # JavaScriptテスト
    ├── asset-performance.test.js   # アセットパフォーマンス
    └── modules/                    # モジュール別テスト
```

### テスト実行
```bash
# 全テスト実行
php artisan test

# 特定のテストスイート
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# 特定のテストファイル
php artisan test tests/Feature/FunctionalityValidationTest.php

# カバレッジレポート
php artisan test --coverage

# JavaScriptテスト
npm run test
npm run test:watch
```

## 🔐 セキュリティ・権限

### ロール定義
```php
// ユーザーロール
'admin'     => '管理者（全権限）'
'editor'    => '編集者（施設情報編集）'
'approver'  => '承認者（承認・差戻し）'
'viewer'    => '閲覧者（読み取り専用）'
```

### 主要ポリシー
```php
// app/Policies/LandInfoPolicy.php
public function view(User $user, LandInfo $landInfo)      # 閲覧権限
public function update(User $user, LandInfo $landInfo)    # 更新権限
public function approve(User $user, LandInfo $landInfo)   # 承認権限
```

### セキュリティ機能
- **認証**: メールアドレス + パスワード
- **認可**: ロールベースアクセス制御（RBAC）
- **CSRF保護**: 全フォームでCSRFトークン必須
- **XSS対策**: Bladeテンプレートの自動エスケープ
- **SQL Injection対策**: Eloquent ORM使用

## 📦 デプロイメント

### 本番環境要件
```
Webサーバー: Nginx 1.18+
PHP: 8.2+
データベース: MySQL 8.0+
プロセス管理: Supervisor
SSL/TLS: 必須
```

### デプロイ手順
```bash
# 1. アセットビルド
npm run build

# 2. Laravel最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. デプロイメント検証
./deployment/verify-deployment.sh

# 4. パフォーマンス測定
node scripts/measure-build-performance.js
```

### 環境変数（重要）
```env
# アプリケーション
APP_NAME="Shise-Cal"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

# データベース
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shisecal
DB_USERNAME=username
DB_PASSWORD=password

# キャッシュ
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# メール
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

## 🚨 v2.0.0 破壊的変更（重要）

### API エンドポイント変更
```
# 土地情報管理
旧: GET /land-info/{id}
新: GET /facilities/{facility}/land-info

# PDF出力
旧: POST /pdf/generate/{id}
新: POST /export/pdf/single/{facility}

# CSV出力
旧: POST /csv/export
新: POST /export/csv/generate

# コメント管理
旧: /facility-comments/*
新: /comments/*
```

### サービスクラス変更
```php
// 変更前
app(LandInfoService::class)
app(LandCalculationService::class)
app(SecurePdfService::class)

// 変更後
app(FacilityService::class)    # 土地情報+計算統合
app(ExportService::class)      # PDF+ファイル管理統合
```

### フロントエンド変更
```blade
<!-- 変更前: インラインスタイル -->
@push('styles')
<style>
.facility-card { /* ... */ }
</style>
@endpush

<!-- 変更後: 外部CSSファイル -->
@vite(['resources/css/pages/facilities.css'])
```

### 移行期間
- **完全移行期限**: 2025年12月31日
- **レガシーAPIサポート終了**: 2026年3月31日
- **後方互換性**: 旧ルートからのリダイレクト設定済み

## 📚 重要なドキュメント

### 必読ドキュメント
1. **[README.md](../README.md)** - プロジェクト概要
2. **[リリースノート v2.0.0](RELEASE_NOTES_v2.0.0.md)** - 最新変更内容
3. **[破壊的変更一覧](BREAKING_CHANGES_v2.0.0.md)** - API・コード変更詳細
4. **[移行チェックリスト](migration/MIGRATION_CHECKLIST.md)** - 段階的移行手順

### 技術ドキュメント
- **[簡素化されたアーキテクチャガイド](architecture/SIMPLIFIED_ARCHITECTURE.md)** - システム構成
- **[フロントエンドアーキテクチャ](implementation/FRONTEND_ARCHITECTURE.md)** - ES6モジュール構成
- **[API リファレンス](api/API_REFERENCE.md)** - REST API仕様

### 運用ドキュメント
- **[開発環境セットアップ](setup/DEVELOPMENT.md)** - ローカル開発環境
- **[本番環境デプロイ](deployment/PRODUCTION.md)** - デプロイ手順
- **[トラブルシューティング](troubleshooting/)** - よくある問題と解決方法

## 🔧 開発時の注意点

### コーディング規約
```bash
# PHP（Laravel Pint使用）
./vendor/bin/pint

# JavaScript（ESLint設定済み）
npm run lint
```

### パフォーマンス
- **N+1問題**: Eager Loadingの使用を徹底
- **キャッシュ**: 重い処理は適切にキャッシュ
- **アセット**: Viteによる最適化を活用

### セキュリティ
- **入力値検証**: FormRequestクラスの使用
- **認可チェック**: ポリシーの適切な使用
- **ログ記録**: 重要な操作はActivityLogに記録

## 🐛 よくある問題と解決方法

### 開発環境
```bash
# アセットが読み込まれない
npm run build
php artisan optimize:clear

# データベース接続エラー
php artisan migrate:status
php artisan config:clear

# 権限エラー
chmod -R 775 storage bootstrap/cache
```

### 本番環境
```bash
# 500エラー
php artisan optimize:clear
tail -f storage/logs/laravel.log

# アセット404エラー
npm run build
php artisan storage:link
```

## 📞 サポート・連絡先

### 開発チーム
- **プロジェクト管理者**: 泉勇也
- **技術サポート**: development-team@company.com
- **システム管理者**: admin@company.com

### 緊急時対応
- **24時間サポート**: emergency@company.com
- **電話**: 03-XXXX-XXXX

### 移行支援
- **移行相談**: migration-support@company.com
- **トレーニング**: training@company.com

## 📋 引き継ぎチェックリスト

### 環境構築
- [ ] 開発環境のセットアップ完了
- [ ] テストユーザーでのログイン確認
- [ ] 主要機能の動作確認
- [ ] テスト実行の確認

### コードベース理解
- [ ] プロジェクト構造の把握
- [ ] v2.0.0の変更内容理解
- [ ] 主要コントローラー・サービスの確認
- [ ] データベース構造の理解

### 開発フロー
- [ ] Git ワークフローの確認
- [ ] テスト実行方法の理解
- [ ] デプロイ手順の確認
- [ ] コーディング規約の理解

### ドキュメント
- [ ] 必読ドキュメントの確認
- [ ] API仕様の理解
- [ ] トラブルシューティングガイドの確認

### 連絡先・サポート
- [ ] 開発チーム連絡先の確認
- [ ] 緊急時対応手順の理解
- [ ] 移行支援窓口の確認

---

**最終更新**: 2025年9月9日  
**作成者**: Shise-Cal開発チーム  
**バージョン**: v2.0.0対応版

このガイドに関するご質問は、development-team@company.com までお問い合わせください。