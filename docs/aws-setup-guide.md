# AWS テスト環境構築ガイド

## 概要
施設管理システムのテスト環境をAWS上に構築するための手順書です。

## 前提条件
- AWSアカウントが作成済み
- AWS CLIがインストール済み
- 適切なIAM権限が設定済み

## 1. EC2インスタンスの作成

### 1.1 インスタンス仕様
- **インスタンスタイプ**: t2.micro (無料利用枠対象)
- **OS**: Amazon Linux 2023
- **ストレージ**: 8GB gp3
- **リージョン**: ap-northeast-1 (東京)

### 1.2 セキュリティグループ設定
```
インバウンドルール:
- SSH (22): 自分のIPアドレスのみ
- HTTP (80): 0.0.0.0/0
- HTTPS (443): 0.0.0.0/0
- MySQL (3306): セキュリティグループ内のみ

アウトバウンドルール:
- All traffic: 0.0.0.0/0
```

### 1.3 キーペア
- 新しいキーペアを作成: `shisecal-test-key`
- .pemファイルをダウンロードして安全に保管

## 2. 初期設定

### 2.1 SSH接続
```bash
chmod 400 shisecal-test-key.pem
ssh -i shisecal-test-key.pem ec2-user@[EC2のパブリックIP]
```

### 2.2 システム更新
```bash
sudo dnf update -y
```

### 2.3 必要なパッケージのインストール
```bash
# Git
sudo dnf install -y git

# Node.js (npm用)
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo dnf install -y nodejs

# Composer用のPHP
sudo dnf install -y php php-cli php-common php-json php-zip php-mbstring php-xml php-curl
```

## 3. 接続テスト

### 3.1 基本動作確認
```bash
# システム情報確認
uname -a
cat /etc/os-release

# ディスク容量確認
df -h

# メモリ確認
free -h

# ネットワーク確認
ping -c 3 google.com
```

### 3.2 セキュリティ確認
```bash
# ファイアウォール状態確認
sudo systemctl status firewalld

# SSH設定確認
sudo cat /etc/ssh/sshd_config | grep -E "PermitRootLogin|PasswordAuthentication"
```

## 4. 環境変数設定

### 4.1 .bashrc設定
```bash
echo 'export APP_ENV=testing' >> ~/.bashrc
echo 'export AWS_DEFAULT_REGION=ap-northeast-1' >> ~/.bashrc
source ~/.bashrc
```

## 5. 動作確認チェックリスト

- [ ] EC2インスタンスが正常に起動している
- [ ] SSH接続が可能
- [ ] インターネット接続が可能
- [ ] 必要なパッケージがインストール済み
- [ ] セキュリティグループが適切に設定されている
- [ ] システムが最新状態に更新されている

## 6. 次のステップ

1. Webサーバー（Apache/Nginx）のインストール
2. PHP 8.1のインストールと設定
3. MySQL 8.0のインストールと設定
4. Laravelプロジェクトのデプロイ

## トラブルシューティング

### SSH接続できない場合
1. セキュリティグループでSSH(22)ポートが開いているか確認
2. キーペアのパーミッションが400になっているか確認
3. 正しいユーザー名（ec2-user）を使用しているか確認

### パッケージインストールに失敗する場合
1. `sudo dnf update -y` を実行してシステムを更新
2. リポジトリの設定を確認
3. ディスク容量を確認

## セキュリティ注意事項

1. **キーペアの管理**
   - .pemファイルは安全な場所に保管
   - 他人と共有しない
   - 定期的にローテーション

2. **セキュリティグループ**
   - 必要最小限のポートのみ開放
   - SSH接続は信頼できるIPアドレスのみ許可
   - 定期的に設定を見直し

3. **システム更新**
   - 定期的にセキュリティパッチを適用
   - 不要なサービスは停止
   - ログ監視の実装

## 費用管理

- t2.microインスタンスは月750時間まで無料
- ストレージは30GBまで無料
- データ転送は月15GBまで無料
- 使用量を定期的に確認し、予算アラートを設定