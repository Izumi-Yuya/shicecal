# Shise-Cal ドキュメント

このディレクトリには、Shise-Cal（施設カルテシステム）に関する全てのドキュメントが整理されています。

## 📁 ディレクトリ構成

### 📋 プロジェクト概要
- [要件定義書](requirements/要件定義書（v2.2）.md) - システムの要件定義と仕様
- [README](../README.md) - プロジェクトの基本情報

### 🚀 環境構築・デプロイ
- [開発環境セットアップ](setup/DEVELOPMENT.md) - ローカル開発環境の構築手順
- [テスト環境セットアップ](setup/TEST_ENVIRONMENT_SETUP.md) - テスト環境の構築・運用
- [本番環境デプロイ](deployment/PRODUCTION.md) - 本番環境へのデプロイ手順

### 🔧 技術実装
- [ログイン画面実装](implementation/LOGIN_SCREEN_IMPLEMENTATION.md) - ログイン画面の実装詳細
- [マーブル背景実装](implementation/MARBLE_BACKGROUND_IMPLEMENTATION.md) - 背景画像の実装
- [ロゴ実装修正](implementation/LOGO_IMPLEMENTATION_FIX.md) - ロゴ表示の修正
- [メインアプリスタイリング](implementation/MAIN_APP_STYLING_IMPLEMENTATION.md) - メインアプリのUI実装
- [フレームワーク依存関係解消](implementation/FRAMEWORK_DEPENDENCY_RESOLUTION.md) - 依存関係の最適化

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
2. [開発環境セットアップ](setup/DEVELOPMENT.md) で環境構築
3. [技術実装](implementation/) ディレクトリで実装詳細を確認

### 運用担当者向け
1. [本番環境デプロイ](deployment/PRODUCTION.md) でデプロイ手順を確認
2. [トラブルシューティング](troubleshooting/) で問題解決方法を確認

### 開発チーム向け
1. [開発プロセス](process/) で開発手順を確認
2. [設定・構成](configuration/) で技術設定を確認

## 📅 更新履歴

- 2025/9/3: ドキュメント整理・構造化
- 2025/8/31: 技術実装ドキュメント追加
- 2025/7/4: 要件定義書 v2.2 更新

## 🔗 関連リンク

- [プロジェクトリポジトリ](https://github.com/your-repo/shisecal)
- [機能要件一覧](https://docs.google.com/spreadsheets/d/145Jp-tGGYXCY_t7SXxq8MjcaRrBF90x7xYYpLBJIhzc/edit)
- [組織図](https://docs.google.com/spreadsheets/d/1XCMsHDUCr5tywxzyJGJIpwuUL3yZ0f-Zh9_OlR9xImo/edit)