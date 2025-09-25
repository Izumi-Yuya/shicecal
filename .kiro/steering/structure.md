# プロジェクト構造と組織

## ルートディレクトリレイアウト
```
shicecal/
├── app/                    # アプリケーションコード
├── bootstrap/              # フレームワークブートストラップファイル
├── config/                 # 設定ファイル
├── database/               # マイグレーション、シーダー、ファクトリー
├── docs/                   # プロジェクトドキュメント
├── public/                 # Webサーバードキュメントルート
├── resources/              # ビュー、CSS、JS、言語ファイル
├── routes/                 # ルート定義
├── storage/                # ファイルストレージとログ
├── tests/                  # テストファイル
└── vendor/                 # Composer依存関係
```

## アプリケーション構造 (`app/`)
```
app/
├── Console/                # Artisanコマンド
├── Exceptions/             # 例外ハンドラー
├── Helpers/                # ヘルパークラスとユーティリティ
├── Http/
│   ├── Controllers/        # HTTPコントローラー
│   │   └── Admin/          # 管理者専用コントローラー
│   ├── Middleware/         # HTTPミドルウェア
│   └── Requests/           # フォームリクエストバリデーション
├── Models/                 # Eloquentモデル
├── Policies/               # 認可ポリシー
├── Providers/              # サービスプロバイダー
└── Services/               # ビジネスロジックサービス
```

## フロントエンド構造 (`resources/`)
```
resources/
├── css/
│   ├── app.css             # メインアプリケーションスタイル
│   ├── auth.css            # 認証ページ
│   ├── admin.css           # 管理者インターフェーススタイル
│   ├── land-info.css       # 土地情報専用スタイル
│   ├── base.css            # ベーススタイルとリセット
│   ├── components.css      # 再利用可能コンポーネント
│   ├── layout.css          # レイアウトとグリッドシステム
│   ├── pages.css           # ページ固有スタイル
│   ├── utilities.css       # ユーティリティクラス
│   ├── variables.css       # CSSカスタムプロパティ
│   └── animations.css      # アニメーション定義
├── js/
│   ├── app.js              # メインアプリケーションJavaScript
│   ├── admin.js            # 管理者インターフェース機能
│   ├── land-info.js        # 土地情報フォーム処理
│   └── bootstrap.js        # フレームワーク初期化
└── views/
    ├── admin/              # 管理者インターフェースビュー
    ├── auth/               # 認証ビュー
    ├── comments/           # コメントシステムビュー
    ├── export/             # エクスポート機能ビュー
    ├── facilities/         # 施設管理ビュー
    ├── layouts/            # レイアウトテンプレート
    ├── maintenance/        # メンテナンスビュー
    ├── my-page/            # ユーザーダッシュボードビュー
    └── notifications/      # 通知ビュー
```

## データベース構造 (`database/`)
```
database/
├── factories/              # テスト用モデルファクトリー
├── migrations/             # データベーススキーママイグレーション
├── seeders/                # データベースシーダー
├── database.sqlite         # SQLiteデータベース（開発用）
└── testing.sqlite          # SQLiteデータベース（テスト用）
```

## テスト構造 (`tests/`)
```
tests/
├── Feature/                # 機能/統合テスト
├── Unit/                   # 単体テスト
│   ├── Helpers/            # ヘルパークラステスト
│   ├── Models/             # モデルテスト
│   ├── Policies/           # ポリシーテスト
│   └── Services/           # サービステスト
├── js/                     # JavaScriptテスト
└── TestCase.php            # ベーステストケース
```

## 設定ファイル (`config/`)
- `app.php` - アプリケーション設定
- `database.php` - データベース接続
- `auth.php` - 認証設定
- `facility.php` - 施設固有設定
- `dompdf.php` - PDF生成設定
- `tcpdf.php` - 高度なPDF設定

## 主要なアーキテクチャパターン

### MVCパターン
- **Models**: Eloquent ORMを使用したデータ層
- **Views**: プレゼンテーション用Bladeテンプレート
- **Controllers**: HTTPリクエスト処理とレスポンス

### サービス層パターン
- ビジネスロジックをサービスクラスに分離
- コントローラーは薄く保ち、サービスに委譲
- サービスが複雑な操作と計算を処理

### リポジトリパターン（暗黙的）
- Eloquentモデルがリポジトリとして機能
- 複雑なクエリをモデルメソッドにカプセル化
- モデルレベルでリレーションシップを定義

### ポリシーベース認可
- 専用のPolicyクラスに認可ロジック
- きめ細かい制御のためのゲートベース権限
- ロールベースアクセス制御（RBAC）実装

## 命名規則

### PHPクラス
- Controllers: `PascalCase` + `Controller` 接尾辞
- Models: `PascalCase` (単数形)
- Services: `PascalCase` + `Service` 接尾辞
- Policies: `PascalCase` + `Policy` 接尾辞

### データベース
- Tables: `snake_case` (複数形)
- Columns: `snake_case`
- Foreign keys: `{table}_id`
- Pivot tables: `{table1}_{table2}` (アルファベット順)

### ファイルとディレクトリ
- Blade views: `kebab-case.blade.php`
- CSS/JSファイル: `kebab-case`
- Migrationファイル: Laravelタイムスタンプ形式

### ルート
- Route names: `dot.notation` (例: `facilities.show`)
- URL paths: リソース規約に従った `kebab-case`

## 機能組織
各主要機能は一貫した構造に従います：
- HTTP処理用のController
- ビジネスロジック用のService
- データアクセス用のModel
- 認可用のPolicy
- バリデーション用のRequestクラス
- 機能サブディレクトリ内のBladeビュー
- 機能固有のCSS/JSファイル
- 包括的なテストカバレッジ

## ドキュメント構造 (`docs/`)
- `requirements/` - システム要件と仕様
- `setup/` - 開発とデプロイメントセットアップガイド
- `implementation/` - 技術実装詳細
- `deployment/` - 本番デプロイメントドキュメント
- `troubleshooting/` - 一般的な問題と解決策