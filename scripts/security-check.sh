#!/bin/bash

# セキュリティチェックスクリプト
# APP_KEYやその他の機密情報の漏洩をチェック

echo "🔍 セキュリティチェックを実行中..."

# APP_KEYの漏洩チェック
echo "📋 APP_KEYの漏洩チェック..."
if git log --all --full-history -- .env* | grep -q "APP_KEY=base64:"; then
    echo "⚠️  警告: Gitヒストリーに.envファイルが含まれています"
    echo "   以下のコマンドでヒストリーから削除することを検討してください:"
    echo "   git filter-branch --force --index-filter 'git rm --cached --ignore-unmatch .env*' --prune-empty --tag-name-filter cat -- --all"
fi

# 現在のファイルでの機密情報チェック
echo "🔐 現在のファイルでの機密情報チェック..."
SENSITIVE_PATTERNS=(
    "password.*="
    "secret.*="
    "key.*=.*[A-Za-z0-9+/]{20,}"
    "token.*="
    "api.*key.*="
)

for pattern in "${SENSITIVE_PATTERNS[@]}"; do
    if grep -r -i --exclude-dir=vendor --exclude-dir=node_modules --exclude="*.log" "$pattern" . 2>/dev/null | grep -v ".env.example" | grep -v "scripts/security-check.sh"; then
        echo "⚠️  機密情報の可能性: $pattern"
    fi
done

# .gitignoreの確認
echo "📁 .gitignoreの確認..."
if ! grep -q "^\.env\*" .gitignore; then
    echo "⚠️  .gitignoreに.env*パターンが含まれていません"
fi

echo "✅ セキュリティチェック完了"