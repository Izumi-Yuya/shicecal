# CommonTableコンポーネント ロールバック計画

## 概要

このドキュメントは、CommonTableレイアウトコンポーネントのデプロイメント後に問題が発生した場合のロールバック手順を詳細に説明します。

## ロールバック判断基準

### 即座にロールバックが必要な状況

#### 重大な機能障害
- [ ] 施設詳細ページが表示されない
- [ ] CommonTableコンポーネントが全く表示されない
- [ ] データベースエラーが多発している
- [ ] セキュリティ上の脆弱性が発見された

#### パフォーマンス問題
- [ ] ページ読み込み時間が5秒を超える
- [ ] メモリ使用量が通常の2倍を超える
- [ ] CPU使用率が90%を超える状態が継続

#### データ整合性問題
- [ ] データが正しく表示されない
- [ ] データの更新ができない
- [ ] データが破損している

### 段階的対応が可能な状況

#### 軽微な表示問題
- [ ] 一部のスタイルが適用されない
- [ ] レスポンシブデザインに軽微な問題
- [ ] アクセシビリティ機能の一部が動作しない

#### パフォーマンス軽微な低下
- [ ] ページ読み込み時間が通常より1-2秒遅い
- [ ] メモリ使用量が通常より20-30%増加

## ロールバック手順

### 緊急ロールバック（15分以内）

#### ステップ1: 状況確認と記録
```bash
# 現在の状況をログに記録
echo "$(date): Emergency rollback initiated" >> /var/log/deployment.log

# エラーログの確認
tail -n 100 storage/logs/laravel.log

# システムリソースの確認
free -m
top -n 1
```

#### ステップ2: メンテナンスモード有効化
```bash
# メンテナンスモードを有効にする
php artisan down --message="緊急メンテナンス中です。復旧まで今しばらくお待ちください。" --retry=60

# メンテナンスモードの確認
curl -I https://your-domain.com/
```

#### ステップ3: データベースバックアップ（現在の状態）
```bash
# 現在のデータベース状態をバックアップ
mysqldump -u [username] -p [database_name] > rollback_current_$(date +%Y%m%d_%H%M%S).sql

# バックアップファイルの確認
ls -la rollback_current_*.sql
```

#### ステップ4: コードロールバック
```bash
# 現在のコミットハッシュを記録
git rev-parse HEAD > current_commit.txt

# 前の安定版に戻す
git checkout [previous_stable_commit]

# 変更内容の確認
git diff HEAD current_commit.txt
```

#### ステップ5: 依存関係の復元
```bash
# Composer依存関係の復元
composer install --no-dev --optimize-autoloader

# NPM依存関係の復元
npm ci --production

# アセットの再ビルド
npm run build
```

#### ステップ6: データベースロールバック
```bash
# データベースを前の状態に復元
mysql -u [username] -p [database_name] < backup_[previous_timestamp].sql

# マイグレーション状態の確認
php artisan migrate:status
```

#### ステップ7: キャッシュクリア
```bash
# 全キャッシュをクリア
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# OPcacheのクリア（必要に応じて）
sudo service php8.2-fpm reload
```

#### ステップ8: 動作確認
```bash
# 基本的な動作確認
php artisan tinker --execute="App\Models\User::count();"

# CommonTableコンポーネントのテスト
php artisan test tests/Feature/Components/CommonTableFinalIntegrationSummary.php --stop-on-failure
```

#### ステップ9: メンテナンスモード解除
```bash
# メンテナンスモードを解除
php artisan up

# 解除の確認
curl -I https://your-domain.com/
```

### 段階的ロールバック（1時間以内）

#### フェーズ1: 問題の特定と分析
```bash
# 詳細なログ分析
grep -i error storage/logs/laravel.log | tail -n 50

# パフォーマンス分析
php artisan route:list | grep -i facility

# データベースクエリ分析
php artisan tinker --execute="DB::enableQueryLog(); /* テストクエリ実行 */; dd(DB::getQueryLog());"
```

#### フェーズ2: 部分的な修正試行
```bash
# 特定のファイルのみロールバック
git checkout [previous_commit] -- resources/views/components/common-table.blade.php

# 特定の設定のみ変更
php artisan config:cache
```

#### フェーズ3: 完全ロールバック（必要に応じて）
上記の緊急ロールバック手順を実行

## ロールバック後の検証手順

### 1. 基本機能確認

#### ページアクセステスト
```bash
# 主要ページのアクセステスト
curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/
curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/facilities
curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/facilities/1
```

#### データベース接続確認
```bash
# データベース接続テスト
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';"

# 基本的なデータ取得テスト
php artisan tinker --execute="echo App\Models\Facility::count() . ' facilities found';"
```

### 2. CommonTableコンポーネント確認

#### 表示確認
1. 施設詳細ページにアクセス
2. 基本情報カードの表示確認
3. 土地情報カードの表示確認
4. 建物情報カードの表示確認
5. ライフライン設備カードの表示確認

#### 機能確認
1. レスポンシブデザインの動作確認
2. リンク（メール、URL）の動作確認
3. ファイルダウンロードの動作確認

### 3. パフォーマンス確認

#### レスポンス時間測定
```bash
# 複数回のアクセステスト
for i in {1..10}; do
  curl -w "@curl-format.txt" -o /dev/null -s "https://your-domain.com/facilities/1"
done
```

#### リソース使用量確認
```bash
# メモリ使用量
free -m

# CPU使用率
top -n 1 | head -n 5

# ディスク使用量
df -h
```

## ロールバック後の対応

### 1. 関係者への通知

#### 通知テンプレート
```
件名: [緊急] CommonTableコンポーネント ロールバック完了

本文:
CommonTableコンポーネントのデプロイメントにおいて問題が発生したため、
緊急ロールバックを実施いたしました。

ロールバック実施時刻: [timestamp]
影響範囲: 施設詳細ページの表示機能
現在の状況: 正常動作確認済み

今後の対応:
1. 問題の根本原因調査
2. 修正版の開発
3. 再デプロイメント計画の策定

ご不明な点がございましたら、開発チームまでお問い合わせください。
```

### 2. 問題分析と報告

#### 問題分析レポートテンプレート
```markdown
# ロールバック実施報告書

## 基本情報
- 実施日時: [timestamp]
- 実施者: [name]
- 影響時間: [duration]
- 影響範囲: [scope]

## 問題の概要
[問題の詳細な説明]

## ロールバック理由
[ロールバックを実施した理由]

## 実施手順
[実際に実施した手順]

## 検証結果
[ロールバック後の動作確認結果]

## 根本原因
[問題の根本原因（判明している場合）]

## 再発防止策
[今後の再発防止のための対策]

## 次回デプロイメントに向けた改善点
[次回のデプロイメントで改善すべき点]
```

### 3. 次回デプロイメント準備

#### 改善項目チェックリスト
- [ ] 問題の根本原因が解決されている
- [ ] 追加のテストケースが作成されている
- [ ] ステージング環境での十分なテスト実施
- [ ] パフォーマンステストの実施
- [ ] セキュリティテストの実施
- [ ] ロールバック手順の見直し

## 予防策

### 1. デプロイメント前の確認強化

#### 必須チェック項目
- [ ] 全自動テストの通過
- [ ] 手動テストの実施
- [ ] ステージング環境での動作確認
- [ ] パフォーマンステストの実施
- [ ] セキュリティスキャンの実施

### 2. 監視体制の強化

#### リアルタイム監視
```bash
# アプリケーション監視スクリプト
#!/bin/bash
while true; do
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://your-domain.com/facilities/1)
    if [ $RESPONSE -ne 200 ]; then
        echo "$(date): Application error detected (HTTP $RESPONSE)" | mail -s "Application Alert" admin@example.com
    fi
    sleep 60
done
```

#### ログ監視
```bash
# エラーログ監視
tail -f storage/logs/laravel.log | grep -i error --line-buffered | while read line; do
    echo "$(date): $line" | mail -s "Error Log Alert" admin@example.com
done
```

### 3. 段階的デプロイメント

#### カナリアデプロイメント
1. 一部のユーザーのみに新機能を提供
2. 問題がないことを確認後、全ユーザーに展開
3. 各段階で十分な監視と検証を実施

#### ブルーグリーンデプロイメント
1. 新バージョンを別環境にデプロイ
2. 十分なテスト実施後、トラフィックを切り替え
3. 問題発生時は即座に旧環境に切り戻し

## 連絡先とエスカレーション

### 緊急時連絡先
- システム管理者: admin@example.com (24時間対応)
- 開発チームリーダー: dev-lead@example.com
- インフラチーム: infra@example.com

### エスカレーション手順
1. **レベル1** (0-15分): システム管理者が初期対応
2. **レベル2** (15-30分): 開発チームが参加
3. **レベル3** (30分以上): 全チーム + 外部ベンダーが参加

### 報告先
- 経営陣: management@example.com
- 品質保証チーム: qa@example.com
- カスタマーサポート: support@example.com

## 付録

### A. ロールバック実施チェックシート

```
□ 1. 状況確認と記録
□ 2. メンテナンスモード有効化
□ 3. 現在状態のバックアップ
□ 4. コードロールバック
□ 5. 依存関係の復元
□ 6. データベースロールバック
□ 7. キャッシュクリア
□ 8. 動作確認
□ 9. メンテナンスモード解除
□ 10. 関係者への通知
□ 11. 問題分析レポート作成
```

### B. 緊急時コマンド集

```bash
# 緊急メンテナンスモード
php artisan down --message="緊急メンテナンス中" --retry=60

# 前のコミットに戻す
git checkout HEAD~1

# 全キャッシュクリア
php artisan optimize:clear

# データベース復元
mysql -u [user] -p [db] < backup.sql

# メンテナンスモード解除
php artisan up

# 基本動作確認
php artisan test --stop-on-failure
```

### C. 監視コマンド集

```bash
# システムリソース確認
free -m && df -h && top -n 1

# アプリケーション状態確認
curl -I https://your-domain.com/

# ログ確認
tail -n 50 storage/logs/laravel.log

# データベース接続確認
php artisan tinker --execute="DB::connection()->getPdo();"
```