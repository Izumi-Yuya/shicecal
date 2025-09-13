# GitHub CLI セットアップガイド (安全版)

## 概要

GitHub Secretsの設定を自動化するために、GitHub CLI (gh) のインストールと設定が必要です。

## インストール手順

### macOS (Homebrew)
```bash
# Homebrew でインストール
brew install gh

# インストール確認
gh --version
```

## 認証設定

### Personal Access Token での認証
1. GitHub.com にログイン
2. Settings → Developer settings → Personal access tokens → Tokens (classic)
3. "Generate new token (classic)" をクリック
4. 必要なスコープを選択：
   - `repo` (Full control of private repositories)
   - `workflow` (Update GitHub Action workflows)
   - `admin:repo_hook` (Full control of repository hooks)

### GitHub CLI での認証
```bash
# 環境変数で設定
export GITHUB_TOKEN="your_token_here"

# 認証確認
gh auth status
```

## 次のステップ

認証完了後、以下のコマンドでSecrets設定を開始：

```bash
# テスト環境のSecrets設定
./scripts/setup-github-secrets.sh test setup

# 設定確認
./scripts/setup-github-secrets.sh test verify
```

---

**参考リンク**:
- [GitHub CLI 公式ドキュメント](https://cli.github.com/)
- [GitHub Secrets 管理](https://docs.github.com/en/actions/security-guides/encrypted-secrets)