# Shise-Cal（シセカル）- 施設カルテシステム

<p align="center">
    <img src="public/images/shicecal-logo.png" width="200" alt="Shise-Cal Logo">
</p>

<p align="center">
    <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 📋 プロジェクト概要

Shise-Cal（シセカル）は、施設情報を一元管理するWebアプリケーションです。権限ベースのアクセス制御と承認フローにより、情報の整合性と業務効率を向上させます。

### 主な機能

- 🏢 **施設管理**: 施設基本情報の登録・更新・削除
- 📄 **ファイル管理**: PDF書類のアップロード・ダウンロード
- 👥 **権限制御**: ロール別のアクセス制御
- ✅ **承認フロー**: 編集内容の承認・差戻し機能
- 📊 **出力機能**: PDF帳票・CSV出力
- 💬 **コメント機能**: 確認・修正依頼の記録・通知
- 📈 **修繕履歴**: 修繕対応の履歴管理
- 🔍 **年次確認**: 定期的な情報確認機能

## 🚀 クイックスタート

### 必要な環境

- PHP 8.1以上
- Composer 2.x
- Node.js 16以上
- MySQL 8.0以上（または SQLite）

### インストール

```bash
# リポジトリのクローン
git clone <repository-url>
cd shicecal

# 依存関係のインストール
composer install
npm install

# 環境設定
cp .env.example .env
php artisan key:generate

# データベースのセットアップ
php artisan migrate --seed

# フロントエンドアセットのビルド
npm run build

# 開発サーバーの起動
php artisan serve
```

アプリケーションは http://localhost:8000 でアクセスできます。

### テストユーザー

| ロール | メールアドレス | パスワード |
|--------|---------------|-----------|
| 管理者 | admin@shisecal.example.com | password |
| 編集者 | editor@shisecal.example.com | password |
| 承認者 | approver@shisecal.example.com | password |
| 閲覧者 | viewer@shisecal.example.com | password |

## 🏛️ プロジェクト構造

### 簡素化されたアーキテクチャ

このプロジェクトは保守性と可読性を向上させるため、2025年に大幅なリファクタリングを実施しました：

#### コントローラー構成（8個）
- **FacilityController**: 施設管理 + 土地情報管理（統合）
- **CommentController**: コメント機能（統合）
- **ExportController**: PDF・CSV出力機能（統合）
- **AuthController**: 認証機能
- **NotificationController**: 通知機能
- **MyPageController**: マイページ機能
- **MaintenanceController**: 修繕履歴管理
- **AnnualConfirmationController**: 年次確認機能

#### サービス層（5個）
- **FacilityService**: 施設・土地情報のビジネスロジック
- **ExportService**: PDF・CSV出力処理
- **ActivityLogService**: アクティビティログ管理
- **NotificationService**: 通知処理
- **PerformanceMonitoringService**: パフォーマンス監視

#### フロントエンド構成
```
resources/
├── css/
│   ├── shared/          # 共通スタイル（変数、コンポーネント、ユーティリティ）
│   └── pages/           # ページ固有スタイル
├── js/
│   ├── modules/         # 機能別JavaScriptモジュール
│   └── shared/          # 共通ユーティリティ
└── views/
    ├── facilities/      # 施設管理（基本情報・土地情報含む）
    ├── export/          # 出力機能
    ├── comments/        # コメント機能
    └── shared/          # 共通コンポーネント
```

## 📚 ドキュメント

詳細なドキュメントは [docs](docs/) ディレクトリに整理されています：

- [📋 要件定義書](docs/requirements/要件定義書（v2.2）.md) - システムの要件と仕様
- [🏗️ アーキテクチャガイド](docs/architecture/SIMPLIFIED_ARCHITECTURE.md) - 簡素化されたシステム構成
- [🔄 マイグレーションガイド](docs/migration/PROJECT_SIMPLIFICATION_GUIDE.md) - リファクタリング内容と移行手順
- [🔧 開発環境セットアップ](docs/setup/DEVELOPMENT.md) - ローカル開発環境の構築
- [🚀 本番環境デプロイ](docs/deployment/PRODUCTION.md) - 本番環境へのデプロイ手順
- [💻 技術実装](docs/implementation/) - 各機能の実装詳細

## 🏗️ 技術スタック

### バックエンド
- **フレームワーク**: Laravel 9.x
- **言語**: PHP 8.2+
- **データベース**: MySQL 8.0 / SQLite
- **キャッシュ**: Redis

### フロントエンド
- **CSS**: Bootstrap 5.1.3 + 機能別モジュール構成
- **JavaScript**: ES6 Modules + Vanilla JS
- **ビルドツール**: Vite 4.x
- **アイコン**: Font Awesome 6.0.0
- **アーキテクチャ**: 機能別モジュール + 共有ユーティリティ

### 簡素化されたアーキテクチャ
- **8個のコントローラー**: 機能別に統合（従来の13個から削減）
- **5個のサービス**: ビジネスロジックを明確に分離（従来の8個から削減）
- **分離されたアセット**: CSS/JavaScriptをBladeテンプレートから分離
- **RESTfulルート**: 一貫性のあるURL構造

### インフラ
- **Webサーバー**: Nginx
- **プロセス管理**: Supervisor
- **コンテナ**: Docker (オプション)

## 🔐 セキュリティ

- **認証**: メールアドレス + パスワード
- **認可**: ロールベースアクセス制御（RBAC）
- **IP制限**: 社内固定IPからのみアクセス可能
- **HTTPS**: SSL/TLS通信の強制
- **セキュリティヘッダー**: XSS、CSRF、Clickjacking対策

## 🧪 テスト

```bash
# 全テストの実行
php artisan test

# カバレッジレポート付き
php artisan test --coverage

# 特定のテストスイート
php artisan test --testsuite=Feature
```

## 📦 デプロイメント

### 開発環境
```bash
npm run dev
php artisan serve
```

### 本番環境
```bash
# アセットビルド
npm run build

# 最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# デプロイスクリプト実行
./deploy.sh
```

詳細は [本番環境デプロイガイド](docs/deployment/PRODUCTION.md) を参照してください。

## 🤝 開発への参加

1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add some amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

## 📄 ライセンス

このプロジェクトは [MIT ライセンス](https://opensource.org/licenses/MIT) の下で公開されています。

## 🆘 サポート

問題が発生した場合：

1. [トラブルシューティングガイド](docs/troubleshooting/) を確認
2. [GitHub Issues](https://github.com/your-repo/shisecal/issues) で既存の問題を検索
3. 新しい Issue を作成して詳細を報告

## 📞 連絡先

- **プロジェクト管理者**: 泉勇也
- **開発チーム**: [開発チーム連絡先]

---

**Shise-Cal** - 施設管理をもっとスマートに 🏢✨