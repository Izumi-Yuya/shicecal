#!/bin/bash

# 本番環境バックアップスクリプト
# 使用方法: ./scripts/backup-production.sh

set -e

# 色付きログ関数
info() { echo -e "\033[32m[INFO]\033[0m $*"; }
warn() { echo -e "\033[33m[WARN]\033[0m $*"; }
error() { echo -e "\033[31m[ERROR]\033[0m $*"; }
success() { echo -e "\033[32m[SUCCESS]\033[0m $*"; }

# 設定
SSH_KEY_FILE="$HOME/Shise-Cal-test-key.pem"
AWS_HOST="35.75.1.64"
AWS_USER="ec2-user"
DEPLOY_DIR="/home/ec2-user/shicecal"
BACKUP_DIR="/home/ec2-user/backups"
LOCAL_BACKUP_DIR="./backups"

# 日付フォーマット
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="shicecal_backup_${TIMESTAMP}"

info "💾 本番環境バックアップ開始: $BACKUP_NAME"

# ローカルバックアップディレクトリ作成
mkdir -p "$LOCAL_BACKUP_DIR"

# リモートでバックアップ実行
ssh -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" << EOF
set -e

# バックアップディレクトリ作成
mkdir -p "$BACKUP_DIR/$BACKUP_NAME"
cd "$DEPLOY_DIR"

echo "📁 ファイルバックアップ中..."

# 重要なファイルをバックアップ
cp -r .env "$BACKUP_DIR/$BACKUP_NAME/" 2>/dev/null || echo "⚠️ .env ファイルが見つかりません"
cp -r storage/app "$BACKUP_DIR/$BACKUP_NAME/storage_app" 2>/dev/null || echo "⚠️ storage/app が見つかりません"
cp -r public/uploads "$BACKUP_DIR/$BACKUP_NAME/public_uploads" 2>/dev/null || echo "ℹ️ public/uploads が見つかりません"

# データベースバックアップ
echo "🗄️ データベースバックアップ中..."
if [ -f ".env" ]; then
    DB_CONNECTION=\$(grep DB_CONNECTION .env | cut -d '=' -f2)
    DB_DATABASE=\$(grep DB_DATABASE .env | cut -d '=' -f2)
    DB_USERNAME=\$(grep DB_USERNAME .env | cut -d '=' -f2)
    DB_PASSWORD=\$(grep DB_PASSWORD .env | cut -d '=' -f2)
    DB_HOST=\$(grep DB_HOST .env | cut -d '=' -f2)
    
    if [ "\$DB_CONNECTION" = "mysql" ]; then
        # MySQLバックアップ
        mysqldump -h "\$DB_HOST" -u "\$DB_USERNAME" -p"\$DB_PASSWORD" "\$DB_DATABASE" > "$BACKUP_DIR/$BACKUP_NAME/database.sql" 2>/dev/null || echo "⚠️ MySQLバックアップに失敗しました"
    elif [ "\$DB_CONNECTION" = "sqlite" ]; then
        # SQLiteバックアップ
        if [ -f "\$DB_DATABASE" ]; then
            cp "\$DB_DATABASE" "$BACKUP_DIR/$BACKUP_NAME/database.sqlite"
            echo "✅ SQLiteデータベースをバックアップしました"
        else
            echo "⚠️ SQLiteデータベースファイルが見つかりません: \$DB_DATABASE"
        fi
    fi
else
    echo "⚠️ .env ファイルが見つからないため、データベースバックアップをスキップします"
fi

# ログファイルバックアップ
echo "📝 ログファイルバックアップ中..."
if [ -d "storage/logs" ]; then
    cp -r storage/logs "$BACKUP_DIR/$BACKUP_NAME/logs"
    echo "✅ ログファイルをバックアップしました"
fi

# バックアップサイズ確認
BACKUP_SIZE=\$(du -sh "$BACKUP_DIR/$BACKUP_NAME" | cut -f1)
echo "📊 バックアップサイズ: \$BACKUP_SIZE"

# 圧縮
echo "🗜️ バックアップを圧縮中..."
cd "$BACKUP_DIR"
tar -czf "${BACKUP_NAME}.tar.gz" "$BACKUP_NAME"
rm -rf "$BACKUP_NAME"

COMPRESSED_SIZE=\$(du -sh "${BACKUP_NAME}.tar.gz" | cut -f1)
echo "✅ 圧縮完了: \$COMPRESSED_SIZE"

# 古いバックアップの削除（7日以上古いもの）
echo "🧹 古いバックアップの削除中..."
find "$BACKUP_DIR" -name "shicecal_backup_*.tar.gz" -mtime +7 -delete 2>/dev/null || true

# 残りのバックアップ数
BACKUP_COUNT=\$(ls -1 "$BACKUP_DIR"/shicecal_backup_*.tar.gz 2>/dev/null | wc -l)
echo "📁 保持中のバックアップ数: \$BACKUP_COUNT"

echo "✅ リモートバックアップ完了: ${BACKUP_NAME}.tar.gz"
EOF

# ローカルにバックアップをダウンロード
info "📥 バックアップをローカルにダウンロード中..."
scp -i "$SSH_KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST:$BACKUP_DIR/${BACKUP_NAME}.tar.gz" "$LOCAL_BACKUP_DIR/"

if [ $? -eq 0 ]; then
    success "✅ ローカルバックアップ完了: $LOCAL_BACKUP_DIR/${BACKUP_NAME}.tar.gz"
    
    # ローカルバックアップサイズ確認
    LOCAL_SIZE=$(du -sh "$LOCAL_BACKUP_DIR/${BACKUP_NAME}.tar.gz" | cut -f1)
    info "📊 ローカルバックアップサイズ: $LOCAL_SIZE"
else
    error "❌ ローカルバックアップのダウンロードに失敗しました"
fi

# バックアップ検証
info "🔍 バックアップ検証中..."
if tar -tzf "$LOCAL_BACKUP_DIR/${BACKUP_NAME}.tar.gz" > /dev/null 2>&1; then
    success "✅ バックアップファイルの整合性確認完了"
else
    error "❌ バックアップファイルが破損している可能性があります"
fi

# 古いローカルバックアップの削除
info "🧹 古いローカルバックアップの削除中..."
find "$LOCAL_BACKUP_DIR" -name "shicecal_backup_*.tar.gz" -mtime +14 -delete 2>/dev/null || true

LOCAL_BACKUP_COUNT=$(ls -1 "$LOCAL_BACKUP_DIR"/shicecal_backup_*.tar.gz 2>/dev/null | wc -l)
info "📁 ローカル保持中のバックアップ数: $LOCAL_BACKUP_COUNT"

# バックアップ完了レポート
success "🎉 バックアップ処理完了"
echo ""
echo "📋 バックアップレポート:"
echo "========================"
echo "バックアップ名: $BACKUP_NAME"
echo "作成日時: $(date)"
echo "リモートパス: $BACKUP_DIR/${BACKUP_NAME}.tar.gz"
echo "ローカルパス: $LOCAL_BACKUP_DIR/${BACKUP_NAME}.tar.gz"
echo ""
echo "🔧 復元方法:"
echo "1. リモート復元: ssh -i $SSH_KEY_FILE $AWS_USER@$AWS_HOST"
echo "2. バックアップ展開: cd $BACKUP_DIR && tar -xzf ${BACKUP_NAME}.tar.gz"
echo "3. ファイル復元: 必要なファイルを適切な場所にコピー"
echo ""
echo "⚠️ 注意: 復元前に現在の環境をバックアップしてください"