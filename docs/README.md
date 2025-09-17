# Shise-Cal ドキュメント

**パッケージ**: `shise-cal/facility-management` v2.0.0

このディレクトリには、Shise-Cal（施設管理システム）に関する全てのドキュメントが整理されています。

## 📁 ディレクトリ構成

### 📋 プロジェクト概要
- [要件定義書](requirements/要件定義書（v2.2）.md) - システムの要件定義と仕様
- [README](../README.md) - プロジェクトの基本情報

### 🏗️ アーキテクチャ・設計
- [簡素化されたアーキテクチャガイド](architecture/SIMPLIFIED_ARCHITECTURE.md) - リファクタリング後のシステム構成
- [プロジェクト簡素化マイグレーションガイド](migration/PROJECT_SIMPLIFICATION_GUIDE.md) - リファクタリング内容と移行手順
- [施設フォーム移行ガイド](migration/facility-form-migration-guide.md) - 既存フォームの標準化レイアウトへの移行手順
- [API リファレンス](api/API_REFERENCE.md) - REST API仕様書

### 🚀 環境構築・デプロイ
- [開発環境セットアップ](setup/DEVELOPMENT.md) - ローカル開発環境の構築手順
- [テスト環境セットアップ](setup/TEST_ENVIRONMENT_SETUP.md) - テスト環境の構築・運用
- [本番環境デプロイ](deployment/PRODUCTION.md) - 本番環境へのデプロイ手順

### 🔧 技術実装
- [フロントエンドアーキテクチャ](implementation/FRONTEND_ARCHITECTURE.md) - ES6モジュール構成と設計
- [管理者機能実装状況](implementation/ADMIN_FUNCTIONALITY_STATUS.md) - 管理者機能の実装進捗と仕様
- [ログイン画面実装](implementation/LOGIN_SCREEN_IMPLEMENTATION.md) - ログイン画面の実装詳細
- [マーブル背景実装](implementation/MARBLE_BACKGROUND_IMPLEMENTATION.md) - 背景画像の実装
- [ロゴ実装修正](implementation/LOGO_IMPLEMENTATION_FIX.md) - ロゴ表示の修正
- [メインアプリスタイリング](implementation/MAIN_APP_STYLING_IMPLEMENTATION.md) - メインアプリのUI実装
- [フレームワーク依存関係解消](implementation/FRAMEWORK_DEPENDENCY_RESOLUTION.md) - 依存関係の最適化

### 🎨 コンポーネント・UI
- [施設フォームレイアウトコンポーネント](components/facility-form-layout-components.md) - 標準化されたフォームコンポーネントの使用方法
- [施設フォームレイアウト設計](components/facility-form-layout.md) - フォームレイアウトシステムの設計詳細
- [アクセシビリティ実装](components/accessibility-implementation.md) - アクセシビリティ対応の実装ガイド
- [エラーハンドリングシステム](components/error-handling-system.md) - エラー処理の実装方法
- [サービステーブル](components/service-table.md) - サービステーブルコンポーネント

### 💻 開発ガイド
- [施設フォーム開発者ガイド](development/facility-form-developer-guide.md) - 施設フォーム開発の包括的なガイド
- [施設フォームベストプラクティス](development/facility-form-best-practices.md) - 新しい編集フォーム作成のベストプラクティス
- [施設フォームクイックリファレンス](development/facility-form-quick-reference.md) - 開発時の早見表とコードスニペット

### ⚙️ 設定・構成
- [Vite設定](configuration/VITE_CONFIGURATION.md) - フロントエンドビルド設定
- [サーバー設定ファイル](config/) - Nginx、PHP-FPM、Supervisor設定
- [Docker設定](docker/) - Docker Compose、Dockerfile設定

### 🔧 トラブルシューティング
- [ローカル接続トラブルシューティング](troubleshooting/LOCAL_CONNECTION_TROUBLESHOOTING.md) - 接続問題の解決方法

### 📝 開発プロセス・スクリプト
- [タスク実行チェックリスト](process/TASK_EXECUTION_CHECKLIST.md) - 開発タスクの実行手順
- [運用スクリプト](scripts/) - デプロイ・テスト実行スクリプト

## 🎯 ドキュメントの使い方

### 新規開発者向け
1. [要件定義書](requirements/要件定義書（v2.2）.md) でシステム概要を理解
2. [簡素化されたアーキテクチャガイド](architecture/SIMPLIFIED_ARCHITECTURE.md) で現在の構成を把握
3. [開発環境セットアップ](setup/DEVELOPMENT.md) で環境構築
4. [API リファレンス](api/API_REFERENCE.md) でAPI仕様を確認
5. [技術実装](implementation/) ディレクトリで実装詳細を確認

### 既存開発者向け（リファクタリング後）
1. [プロジェクト簡素化マイグレーションガイド](migration/PROJECT_SIMPLIFICATION_GUIDE.md) で変更内容を確認
2. [API リファレンス](api/API_REFERENCE.md) で新しいAPI仕様を確認
3. 必要に応じて既存コードを新しい構造に適応

### 運用担当者向け
1. [本番環境デプロイ](deployment/PRODUCTION.md) でデプロイ手順を確認
2. [トラブルシューティング](troubleshooting/) で問題解決方法を確認

### 開発チーム向け
1. [開発プロセス](process/) で開発手順を確認
2. [設定・構成](configuration/) で技術設定を確認

## 📅 更新履歴

- 2025/9/9: **プロジェクト簡素化完了** - アーキテクチャドキュメント、マイグレーションガイド、API仕様書を追加
- 2025/9/9: [フロントエンドアーキテクチャ改善](CHANGELOG.md#2025年9月9日---フロントエンドアーキテクチャ改善) - ES6モジュール構成への移行
- 2025/9/3: ドキュメント整理・構造化
- 2025/8/31: 技術実装ドキュメント追加
- 2025/7/4: 要件定義書 v2.2 更新

詳細な変更履歴は [CHANGELOG.md](CHANGELOG.md) を参照してください。

## 🔗 関連リンク

- [プロジェクトリポジトリ](https://github.com/your-repo/shisecal)
- [機能要件一覧](https://docs.google.com/spreadsheets/d/145Jp-tGGYXCY_t7SXxq8MjcaRrBF90x7xYYpLBJIhzc/edit)
- [組織図](https://docs.google.com/spreadsheets/d/1XCMsHDUCr5tywxzyJGJIpwuUL3yZ0f-Zh9_OlR9xImo/edit)