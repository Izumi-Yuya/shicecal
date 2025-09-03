# スクリプト集

このディレクトリには、Shise-Calの運用・デプロイに関するスクリプトファイルが含まれています。

## 📁 ファイル一覧

### デプロイメント
- `deploy.sh` - 本番環境デプロイスクリプト
- `deploy-test.sh` - テスト環境デプロイスクリプト

### テスト・検証
- `test-runner.sh` - テスト実行スクリプト
- `test-deployment-verification.sh` - デプロイ検証スクリプト
- `run-test.sh` - 統合テスト実行スクリプト

## 🚀 使用方法

### 本番デプロイ
```bash
chmod +x docs/scripts/deploy.sh
./docs/scripts/deploy.sh
```

### テスト環境デプロイ
```bash
chmod +x docs/scripts/deploy-test.sh
./docs/scripts/deploy-test.sh
```

### テスト実行
```bash
chmod +x docs/scripts/test-runner.sh
./docs/scripts/test-runner.sh
```

## ⚠️ 注意事項

- スクリプト実行前に実行権限を付与してください
- 本番環境での実行前に必ずテスト環境で動作確認を行ってください
- 環境変数や設定ファイルが正しく設定されていることを確認してください