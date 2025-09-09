# プロジェクト情報

## パッケージ詳細

### Composer パッケージ
- **名前**: `shise-cal/facility-management`
- **タイプ**: `project`
- **説明**: Shise-Cal Facility Management System - Simplified Architecture v2.0.0
- **バージョン**: 2.0.0
- **ライセンス**: MIT

### NPM パッケージ
- **名前**: `shise-cal-frontend`
- **説明**: Shise-Cal Frontend Assets - ES6 Modules Architecture
- **バージョン**: 2.0.0
- **プライベート**: true

## キーワード・タグ

### 機能キーワード
- **facility-management**: 施設管理システムの中核機能
- **land-information**: 土地情報管理機能
- **japanese**: 日本語対応システム
- **laravel**: Laravel フレームワークベース

### 技術キーワード
- **PHP 8.2+**: サーバーサイド言語
- **Laravel 9.x**: Webアプリケーションフレームワーク
- **ES6 Modules**: モダンJavaScript構成
- **Vite**: フロントエンドビルドツール
- **Bootstrap 5**: CSSフレームワーク

## プロジェクト識別子

### リポジトリ情報
```json
{
  "name": "shise-cal/facility-management",
  "type": "project",
  "description": "Shise-Cal Facility Management System - Simplified Architecture v2.0.0",
  "version": "2.0.0",
  "keywords": [
    "facility-management",
    "laravel",
    "japanese", 
    "land-information"
  ],
  "license": "MIT"
}
```

### フロントエンド識別子
```json
{
  "name": "shise-cal-frontend",
  "version": "2.0.0",
  "description": "Shise-Cal Frontend Assets - ES6 Modules Architecture",
  "private": true
}
```

## バージョン履歴

### v2.0.0 (2025年9月9日)
- **メジャーリリース**: プロジェクト構造の大幅簡素化
- **パッケージ名変更**: `laravel/laravel` → `shise-cal/facility-management`
- **アーキテクチャ刷新**: コントローラー・サービス統合、フロントエンド分離
- **破壊的変更**: API エンドポイント、サービスクラス、フロントエンド構成

### v1.x系列
- **v1.9.x**: リファクタリング準備版
- **v1.0.0**: 初期リリース版

## 依存関係

### 主要バックエンド依存関係
```json
{
  "php": "^8.1",
  "laravel/framework": "^9.19",
  "barryvdh/laravel-dompdf": "^2.0",
  "elibyy/tcpdf-laravel": "^9.1",
  "spatie/laravel-activitylog": "^4.7",
  "laravel/sanctum": "^3.0"
}
```

### 主要フロントエンド依存関係
```json
{
  "vite": "^4.0.0",
  "laravel-vite-plugin": "^0.7.2",
  "vitest": "^1.0.0",
  "axios": "^1.1.2"
}
```

## プロジェクト構成

### ディレクトリ構造
```
shise-cal/
├── app/                    # Laravel アプリケーションコード
├── resources/              # フロントエンドリソース
│   ├── css/               # スタイルシート（機能別・共通別）
│   ├── js/                # JavaScript（ES6モジュール）
│   └── views/             # Blade テンプレート
├── docs/                  # プロジェクトドキュメント
├── tests/                 # テストファイル
├── composer.json          # PHP依存関係管理
├── package.json           # Node.js依存関係管理
└── vite.config.js         # フロントエンドビルド設定
```

### 主要機能モジュール
1. **施設管理** (`FacilityController` + `FacilityService`)
   - 施設基本情報管理
   - 土地情報管理
   - ドキュメント管理

2. **出力機能** (`ExportController` + `ExportService`)
   - PDF帳票生成
   - CSV データ出力
   - お気に入り機能

3. **コメントシステム** (`CommentController`)
   - コメント管理
   - ステータス追跡
   - 通知機能

4. **認証・権限** (`AuthController`)
   - ユーザー認証
   - ロールベースアクセス制御

## 開発・運用情報

### 開発環境要件
- **PHP**: 8.2以上
- **Node.js**: 18以上
- **MySQL**: 8.0以上（または SQLite）
- **Redis**: 推奨（キャッシュ・セッション）

### 本番環境要件
- **Webサーバー**: Nginx 1.18以上
- **プロセス管理**: Supervisor
- **SSL/TLS**: 必須
- **バックアップ**: 日次自動バックアップ

### サポート・連絡先
- **開発チーム**: development-team@company.com
- **システム管理者**: admin@company.com
- **緊急時サポート**: emergency@company.com

## ライセンス

MIT License - 詳細は [LICENSE](../LICENSE) ファイルを参照

## 関連ドキュメント

- [README.md](../README.md) - プロジェクト概要
- [アーキテクチャガイド](architecture/SIMPLIFIED_ARCHITECTURE.md) - システム構成詳細
- [リリースノート](RELEASE_NOTES_v2.0.0.md) - v2.0.0の変更内容
- [移行ガイド](migration/PROJECT_SIMPLIFICATION_GUIDE.md) - アップグレード手順