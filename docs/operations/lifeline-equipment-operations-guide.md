# ライフライン設備管理 - 運用ガイド

## システム概要

ライフライン設備管理機能は、Laravel 9.x フレームワークを使用して構築され、既存のShise-Calシステムに統合されています。

## アーキテクチャ

### データベース構造
```
lifeline_equipment (メインテーブル)
├── electrical_equipment (電気設備詳細)
├── gas_equipment (ガス設備詳細)
├── water_equipment (水道設備詳細)
├── elevator_equipment (エレベーター設備詳細)
└── hvac_lighting_equipment (空調・照明設備詳細)
```

### APIエンドポイント
- `GET /facilities/{facility}/lifeline-equipment/{category}` - 設備情報取得
- `PUT /facilities/{facility}/lifeline-equipment/{category}` - 設備情報更新

## デプロイメント

### 初回デプロイ
```bash
# マイグレーション実行
php artisan migrate

# 権限設定
php artisan config:cache
php artisan route:cache

# アセットビルド
npm run build
```

### 更新デプロイ
```bash
# コード更新
git pull origin main

# 依存関係更新
composer install --no-dev --optimize-autoloader
npm ci

# マイグレーション実行
php artisan migrate --force

# キャッシュクリア
php artisan config:cache
php artisan route:cache
php artisan view:cache

# アセットビルド
npm run build
```

## 監視とメンテナンス

### ログ監視
```bash
# アプリケーションログ
tail -f storage/logs/laravel.log

# エラーログフィルタ
grep "ERROR" storage/logs/laravel.log

# ライフライン設備関連ログ
grep "LifelineEquipment" storage/logs/laravel.log
```

### パフォーマンス監視
```sql
-- 遅いクエリの確認
SELECT * FROM information_schema.processlist 
WHERE command != 'Sleep' AND time > 5;

-- ライフライン設備テーブルのサイズ確認
SELECT 
    table_name,
    round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_name LIKE '%lifeline%' OR table_name LIKE '%equipment%';
```

### データベースメンテナンス
```bash
# バックアップ作成
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# インデックス最適化
php artisan db:seed --class=OptimizeLifelineEquipmentIndexes

# 古いデータのアーカイブ（必要に応じて）
php artisan lifeline:archive-old-data --days=365
```

## セキュリティ

### 権限管理
```php
// 権限チェック例
Gate::define('view-lifeline-equipment', function ($user, $facility) {
    return $user->canViewFacility($facility);
});

Gate::define('edit-lifeline-equipment', function ($user, $facility) {
    return $user->canEditFacility($facility);
});
```

### セキュリティ監査
```bash
# セキュリティテスト実行
php artisan test tests/Feature/LifelineEquipmentSecurityTest

# 権限チェック
php artisan tinker
>>> User::find(1)->can('view-lifeline-equipment', Facility::find(1))
```

## トラブルシューティング

### よくある問題

#### 1. マイグレーションエラー
```bash
# エラー: UNIQUE constraint failed
# 解決: 既存データの重複を確認
SELECT facility_id, category, COUNT(*) 
FROM lifeline_equipment 
GROUP BY facility_id, category 
HAVING COUNT(*) > 1;

# 重複データの削除
DELETE le1 FROM lifeline_equipment le1
INNER JOIN lifeline_equipment le2 
WHERE le1.id > le2.id 
AND le1.facility_id = le2.facility_id 
AND le1.category = le2.category;
```

#### 2. パフォーマンス問題
```bash
# N+1クエリ問題の確認
php artisan debugbar:clear
# ブラウザでページアクセス後
php artisan debugbar:publish

# クエリ最適化
# Eloquentでeager loadingを使用
$facilities = Facility::with(['lifelineEquipment.electricalEquipment'])->get();
```

#### 3. JavaScript エラー
```bash
# アセット再ビルド
npm run build

# キャッシュクリア
php artisan view:clear

# ブラウザキャッシュクリア指示
```

### エラーコード一覧

| エラーコード | 説明 | 対処法 |
|-------------|------|--------|
| LE001 | 設備カテゴリが無効 | 有効なカテゴリを指定 |
| LE002 | 権限不足 | ユーザー権限を確認 |
| LE003 | データ検証エラー | 入力データを確認 |
| LE004 | 設備が見つからない | 設備IDを確認 |
| LE005 | 同時更新エラー | 最新データで再試行 |

## バックアップとリストア

### 定期バックアップ
```bash
# crontabに追加
0 2 * * * /path/to/backup-lifeline-equipment.sh

# バックアップスクリプト例
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME \
  --tables lifeline_equipment electrical_equipment gas_equipment \
  water_equipment elevator_equipment hvac_lighting_equipment \
  > /backup/lifeline_equipment_$DATE.sql
```

### リストア手順
```bash
# データベースリストア
mysql -u username -p database_name < backup_file.sql

# アプリケーションキャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 権限再設定
php artisan config:cache
php artisan route:cache
```

## 拡張とカスタマイズ

### 新しい設備カテゴリの追加
1. マイグレーションファイル作成
```bash
php artisan make:migration create_new_equipment_table
```

2. モデル作成
```bash
php artisan make:model NewEquipment
```

3. ファクトリー作成
```bash
php artisan make:factory NewEquipmentFactory
```

4. テスト作成
```bash
php artisan make:test NewEquipmentTest
```

### カスタムバリデーションルール
```php
// app/Rules/EquipmentValidation.php
class EquipmentValidation implements Rule
{
    public function passes($attribute, $value)
    {
        // カスタムバリデーションロジック
        return true;
    }
}
```

## 連絡先

### 開発チーム
- システム管理者: admin@example.com
- 開発責任者: dev-lead@example.com

### 緊急時連絡先
- 24時間サポート: emergency@example.com
- 電話: 03-XXXX-XXXX

## 更新履歴

- 2025年9月17日: 初版作成
- 電気設備機能の運用開始
- 基本監視・メンテナンス手順確立