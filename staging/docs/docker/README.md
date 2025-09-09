# Docker設定ファイル

このディレクトリには、Shise-CalのDocker関連設定ファイルが含まれています。

## 📁 ファイル一覧

### Docker Compose設定
- `docker-compose.dev.yml` - 開発環境用Docker Compose設定
- `docker-compose.test.yml` - テスト環境用Docker Compose設定

### Dockerfile
- `Dockerfile.dev` - 開発環境用Dockerイメージ設定
- `Dockerfile.test` - テスト環境用Dockerイメージ設定

## 🐳 使用方法

### 開発環境の起動
```bash
# 開発環境コンテナの起動
docker-compose -f docs/docker/docker-compose.dev.yml up -d

# ログの確認
docker-compose -f docs/docker/docker-compose.dev.yml logs -f

# コンテナの停止
docker-compose -f docs/docker/docker-compose.dev.yml down
```

### テスト環境の起動
```bash
# テスト環境コンテナの起動
docker-compose -f docs/docker/docker-compose.test.yml up -d

# テストの実行
docker-compose -f docs/docker/docker-compose.test.yml run test-runner

# コンテナの停止
docker-compose -f docs/docker/docker-compose.test.yml down
```

## 🔧 カスタマイズ

### 環境変数の設定
各環境に応じて、以下の環境変数を設定してください：

```env
# データベース設定
DB_HOST=db
DB_DATABASE=shisecal_dev
DB_USERNAME=shisecal_user
DB_PASSWORD=secure_password

# Redis設定
REDIS_HOST=redis
REDIS_PORT=6379

# メール設定（開発環境）
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### ポート設定
デフォルトのポート設定：

| サービス | 開発環境 | テスト環境 |
|---------|---------|-----------|
| Web | 8080 | 8081 |
| Database | 3307 | 3308 |
| Redis | 6380 | 6381 |
| MailHog | 8025 | 8026 |

## ⚠️ 注意事項

- Docker Composeファイルを使用する前に、必要な環境変数が設定されていることを確認してください
- ポート競合を避けるため、使用するポートが空いていることを確認してください
- 本番環境では、セキュリティを考慮した設定に変更してください