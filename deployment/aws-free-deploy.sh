#!/bin/bash

# AWS無料テスト環境デプロイスクリプト
# 完全無料でShise-Calをデプロイ

set -e

echo "🚀 AWS無料テスト環境デプロイを開始します..."

# 設定変数
REGION="ap-northeast-1"
INSTANCE_TYPE="t2.micro"
AMI_ID="ami-0c02fb55956c7d316"  # Amazon Linux 2
KEY_NAME="shisecal-test-key"
SECURITY_GROUP="shisecal-test-sg"
BUCKET_NAME="shisecal-test-files-$(date +%s)"

# 色付きログ
log_info() {
    echo -e "\033[32m[INFO]\033[0m $1"
}

log_warn() {
    echo -e "\033[33m[WARN]\033[0m $1"
}

log_error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
}

# AWS CLI確認
check_aws_cli() {
    log_info "AWS CLIの確認中..."
    
    if ! command -v aws &> /dev/null; then
        log_error "AWS CLIがインストールされていません"
        log_info "インストール方法: https://docs.aws.amazon.com/cli/latest/userguide/install-cliv2.html"
        exit 1
    fi
    
    # AWS認証確認
    if ! aws sts get-caller-identity &> /dev/null; then
        log_error "AWS認証が設定されていません"
        log_info "設定方法: aws configure"
        exit 1
    fi
    
    log_info "✅ AWS CLI設定完了"
}

# キーペア作成
create_key_pair() {
    log_info "キーペアを作成中..."
    
    if aws ec2 describe-key-pairs --key-names $KEY_NAME &> /dev/null; then
        log_warn "キーペア '$KEY_NAME' は既に存在します"
    else
        aws ec2 create-key-pair \
            --key-name $KEY_NAME \
            --query 'KeyMaterial' \
            --output text > ${KEY_NAME}.pem
        
        chmod 400 ${KEY_NAME}.pem
        log_info "✅ キーペア作成完了: ${KEY_NAME}.pem"
    fi
}

# セキュリティグループ作成
create_security_group() {
    log_info "セキュリティグループを作成中..."
    
    # セキュリティグループ作成
    SG_ID=$(aws ec2 create-security-group \
        --group-name $SECURITY_GROUP \
        --description "Shise-Cal Test Environment Security Group" \
        --query 'GroupId' \
        --output text 2>/dev/null || \
        aws ec2 describe-security-groups \
        --group-names $SECURITY_GROUP \
        --query 'SecurityGroups[0].GroupId' \
        --output text)
    
    # HTTP許可
    aws ec2 authorize-security-group-ingress \
        --group-id $SG_ID \
        --protocol tcp \
        --port 80 \
        --cidr 0.0.0.0/0 2>/dev/null || true
    
    # HTTPS許可
    aws ec2 authorize-security-group-ingress \
        --group-id $SG_ID \
        --protocol tcp \
        --port 443 \
        --cidr 0.0.0.0/0 2>/dev/null || true
    
    # SSH許可
    aws ec2 authorize-security-group-ingress \
        --group-id $SG_ID \
        --protocol tcp \
        --port 22 \
        --cidr 0.0.0.0/0 2>/dev/null || true
    
    log_info "✅ セキュリティグループ作成完了: $SG_ID"
    echo $SG_ID
}

# S3バケット作成
create_s3_bucket() {
    log_info "S3バケットを作成中..."
    
    # バケット作成
    aws s3 mb s3://$BUCKET_NAME --region $REGION
    
    # パブリック読み取り許可
    cat > bucket-policy.json << EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::$BUCKET_NAME/*"
    }
  ]
}
EOF
    
    aws s3api put-bucket-policy \
        --bucket $BUCKET_NAME \
        --policy file://bucket-policy.json
    
    rm bucket-policy.json
    
    log_info "✅ S3バケット作成完了: $BUCKET_NAME"
    echo $BUCKET_NAME
}

# ユーザーデータスクリプト作成
create_user_data() {
    log_info "ユーザーデータスクリプトを作成中..."
    
    cat > user-data.sh << 'EOF'
#!/bin/bash
yum update -y

# PHP 8.2インストール
amazon-linux-extras enable php8.2
yum install -y php php-cli php-sqlite3 php-mbstring php-xml php-zip php-gd php-curl

# Composerインストール
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Node.jsインストール
curl -sL https://rpm.nodesource.com/setup_18.x | bash -
yum install -y nodejs

# Gitインストール
yum install -y git

# Webサーバーディレクトリ作成
mkdir -p /var/www
cd /var/www

# プロジェクトクローン（実際のリポジトリURLに変更してください）
# git clone https://github.com/your-username/shisecal.git
# cd shisecal

# 仮のプロジェクト構造作成（実際のデプロイ時は上記のgit cloneを使用）
mkdir -p shisecal
cd shisecal

# Laravel基本構造作成
mkdir -p {app,bootstrap,config,database,public,resources,routes,storage,tests}
mkdir -p database/{migrations,seeders,factories}
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}

# 基本ファイル作成
touch database/database.sqlite
chmod 664 database/database.sqlite

# 権限設定
chown -R ec2-user:ec2-user /var/www/shisecal
chmod -R 755 /var/www/shisecal
chmod -R 775 storage bootstrap/cache

# 簡単なindex.phpファイル作成
cat > public/index.php << 'PHPEOF'
<?php
echo "<h1>Shise-Cal テスト環境</h1>";
echo "<p>デプロイ成功！</p>";
echo "<p>時刻: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP バージョン: " . PHP_VERSION . "</p>";

// SQLite接続テスト
try {
    $pdo = new PDO('sqlite:/var/www/shisecal/database/database.sqlite');
    echo "<p>✅ SQLite接続成功</p>";
} catch (Exception $e) {
    echo "<p>❌ SQLite接続エラー: " . $e->getMessage() . "</p>";
}
PHPEOF

# PHP内蔵サーバー起動
cd /var/www/shisecal
nohup php -S 0.0.0.0:80 -t public > /var/log/php-server.log 2>&1 &

# 起動ログ
echo "$(date): Shise-Cal test environment started" >> /var/log/deployment.log
EOF
    
    log_info "✅ ユーザーデータスクリプト作成完了"
}

# EC2インスタンス起動
launch_ec2_instance() {
    log_info "EC2インスタンスを起動中..."
    
    SG_ID=$1
    
    INSTANCE_ID=$(aws ec2 run-instances \
        --image-id $AMI_ID \
        --count 1 \
        --instance-type $INSTANCE_TYPE \
        --key-name $KEY_NAME \
        --security-group-ids $SG_ID \
        --user-data file://user-data.sh \
        --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=Shise-Cal-Test}]' \
        --query 'Instances[0].InstanceId' \
        --output text)
    
    log_info "インスタンス起動中: $INSTANCE_ID"
    
    # インスタンス起動待機
    log_info "インスタンスの起動を待機中..."
    aws ec2 wait instance-running --instance-ids $INSTANCE_ID
    
    # パブリックIPアドレス取得
    PUBLIC_IP=$(aws ec2 describe-instances \
        --instance-ids $INSTANCE_ID \
        --query 'Reservations[0].Instances[0].PublicIpAddress' \
        --output text)
    
    log_info "✅ EC2インスタンス起動完了"
    log_info "インスタンスID: $INSTANCE_ID"
    log_info "パブリックIP: $PUBLIC_IP"
    
    echo "$INSTANCE_ID:$PUBLIC_IP"
}

# デプロイ情報表示
show_deployment_info() {
    INSTANCE_INFO=$1
    BUCKET_NAME=$2
    
    IFS=':' read -r INSTANCE_ID PUBLIC_IP <<< "$INSTANCE_INFO"
    
    echo ""
    echo "🎉 AWS無料テスト環境のデプロイが完了しました！"
    echo ""
    echo "📊 リソース情報:"
    echo "  - EC2インスタンス: $INSTANCE_ID (t2.micro)"
    echo "  - パブリックIP: $PUBLIC_IP"
    echo "  - S3バケット: $BUCKET_NAME"
    echo "  - データベース: SQLite"
    echo "  - 月額コスト: 無料 💰"
    echo ""
    echo "🌐 アクセス:"
    echo "  http://$PUBLIC_IP"
    echo ""
    echo "🔑 SSH接続:"
    echo "  ssh -i ${KEY_NAME}.pem ec2-user@$PUBLIC_IP"
    echo ""
    echo "📝 ログ確認:"
    echo "  ssh -i ${KEY_NAME}.pem ec2-user@$PUBLIC_IP 'tail -f /var/log/deployment.log'"
    echo ""
    echo "⚠️  注意事項:"
    echo "  - t2.microは月750時間まで無料"
    echo "  - 使用しない時はインスタンスを停止してください"
    echo "  - 12ヶ月後は課金が発生します"
    echo ""
}

# クリーンアップ関数
cleanup() {
    log_info "一時ファイルをクリーンアップ中..."
    rm -f user-data.sh bucket-policy.json
}

# メイン実行
main() {
    echo "🏗️  AWS無料テスト環境デプロイスクリプト"
    echo "=========================================="
    echo ""
    
    check_aws_cli
    create_key_pair
    SG_ID=$(create_security_group)
    BUCKET_NAME=$(create_s3_bucket)
    create_user_data
    INSTANCE_INFO=$(launch_ec2_instance $SG_ID)
    
    # 少し待機（ユーザーデータスクリプトの実行時間）
    log_info "アプリケーションの初期化を待機中（2分）..."
    sleep 120
    
    show_deployment_info "$INSTANCE_INFO" "$BUCKET_NAME"
    cleanup
    
    echo "✅ デプロイ完了！"
}

# エラーハンドリング
trap 'log_error "デプロイ中にエラーが発生しました"; cleanup; exit 1' ERR

# スクリプト実行
main "$@"