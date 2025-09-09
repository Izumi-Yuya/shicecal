# セキュリティガイドライン

## 🔐 重要な注意事項

### 環境変数の管理

**絶対にやってはいけないこと:**
- `.env`ファイルをGitにコミットする
- APP_KEYを公開リポジトリに含める
- 本番環境の認証情報をコードに含める

### 安全な開発手順

1. **初期セットアップ**
   ```bash
   # 環境ファイルのセットアップ
   ./scripts/setup-env.sh
   
   # セキュリティチェック
   ./scripts/security-check.sh
   ```

2. **新しい環境の作成**
   ```bash
   # テンプレートから環境ファイルを作成
   cp .env.example .env
   cp .env.testing.example .env.testing
   
   # APP_KEYを生成
   php artisan key:generate
   php artisan key:generate --env=testing
   ```

3. **定期的なセキュリティチェック**
   ```bash
   # 機密情報の漏洩チェック
   ./scripts/security-check.sh
   ```

### APP_KEY漏洩時の対応

1. **即座に新しいキーを生成**
   ```bash
   php artisan key:generate
   ```

2. **影響を受けるセッションの無効化**
   ```bash
   php artisan session:flush
   ```

3. **Gitヒストリーからの削除（必要に応じて）**
   ```bash
   git filter-branch --force --index-filter \
     'git rm --cached --ignore-unmatch .env*' \
     --prune-empty --tag-name-filter cat -- --all
   ```

### 本番環境での注意事項

- 環境変数は環境固有の設定管理システムを使用
- APP_KEYは定期的にローテーション
- ログファイルに機密情報が含まれないよう注意
- ファイルアップロード機能のセキュリティ検証

### 報告

セキュリティ上の問題を発見した場合は、公開せずに開発チームに直接報告してください。