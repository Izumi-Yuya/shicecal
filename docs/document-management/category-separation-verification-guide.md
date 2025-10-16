# ドキュメントカテゴリ分離 - 開発環境動作確認ガイド

## 概要
このガイドは、ドキュメント管理システムのカテゴリ分離機能が正しく動作していることを確認するための手順を提供します。

## 前提条件
- 開発環境が起動していること（`php artisan serve` と `npm run dev`）
- テストユーザーでログインしていること
- テスト用の施設データが存在すること

## 検証手順

### 1. メインドキュメントタブでのフォルダ作成

#### 手順
1. 施設詳細画面を開く
2. 「ドキュメント」タブをクリック
3. 「新しいフォルダ」ボタンをクリック
4. フォルダ名を入力（例：「メインドキュメントテスト」）
5. 「作成」ボタンをクリック

#### 確認ポイント
- [ ] フォルダが正常に作成される
- [ ] 作成したフォルダがメインドキュメントタブに表示される
- [ ] データベースで `category` が `NULL` であることを確認

```sql
SELECT id, facility_id, name, category, path 
FROM document_folders 
WHERE name = 'メインドキュメントテスト';
-- category が NULL であることを確認
```

### 2. ライフライン設備でのフォルダ作成

#### 手順（電気設備の例）
1. 施設詳細画面を開く
2. 「ライフライン設備」タブをクリック
3. 「電気設備」サブタブをクリック
4. 「ドキュメント」セクションを展開
5. 「新しいフォルダ」ボタンをクリック
6. フォルダ名を入力（例：「電気設備テスト」）
7. 「作成」ボタンをクリック

#### 確認ポイント
- [ ] フォルダが正常に作成される
- [ ] 作成したフォルダが電気設備のドキュメントセクションに表示される
- [ ] データベースで `category` が `lifeline_electrical` であることを確認

```sql
SELECT id, facility_id, name, category, path 
FROM document_folders 
WHERE name = '電気設備テスト';
-- category が 'lifeline_electrical' であることを確認
```

#### 他のライフライン設備カテゴリでも同様にテスト
- [ ] ガス設備（`lifeline_gas`）
- [ ] 水道設備（`lifeline_water`）
- [ ] エレベーター設備（`lifeline_elevator`）
- [ ] 空調・照明設備（`lifeline_hvac_lighting`）

### 3. 修繕履歴でのフォルダ作成

#### 手順（外装の例）
1. 施設詳細画面を開く
2. 「修繕履歴」タブをクリック
3. 「外装」サブタブをクリック
4. 「ドキュメント」セクションを展開
5. 「新しいフォルダ」ボタンをクリック
6. フォルダ名を入力（例：「外装修繕テスト」）
7. 「作成」ボタンをクリック

#### 確認ポイント
- [ ] フォルダが正常に作成される
- [ ] 作成したフォルダが外装のドキュメントセクションに表示される
- [ ] データベースで `category` が `maintenance_exterior` であることを確認

```sql
SELECT id, facility_id, name, category, path 
FROM document_folders 
WHERE name = '外装修繕テスト';
-- category が 'maintenance_exterior' であることを確認
```

#### 他の修繕履歴カテゴリでも同様にテスト
- [ ] 内装（`maintenance_interior`）
- [ ] 夏季結露（`maintenance_summer_condensation`）
- [ ] その他（`maintenance_other`）

### 4. カテゴリ間の独立性確認

#### 4.1 メインドキュメントタブの確認
1. 「ドキュメント」タブを開く
2. フォルダ一覧を確認

**期待される結果:**
- [ ] メインドキュメントのフォルダのみ表示される
- [ ] ライフライン設備のフォルダは表示されない
- [ ] 修繕履歴のフォルダは表示されない

#### 4.2 ライフライン設備の確認
各設備カテゴリのドキュメントセクションを開き、以下を確認：

**電気設備:**
- [ ] 電気設備のフォルダのみ表示される
- [ ] メインドキュメントのフォルダは表示されない
- [ ] 他のライフライン設備のフォルダは表示されない
- [ ] 修繕履歴のフォルダは表示されない

**ガス設備、水道設備、エレベーター設備、空調・照明設備:**
- [ ] 各カテゴリのフォルダのみ表示される
- [ ] 他のカテゴリのフォルダは表示されない

#### 4.3 修繕履歴の確認
各修繕カテゴリのドキュメントセクションを開き、以下を確認：

**外装:**
- [ ] 外装のフォルダのみ表示される
- [ ] メインドキュメントのフォルダは表示されない
- [ ] ライフライン設備のフォルダは表示されない
- [ ] 他の修繕履歴カテゴリのフォルダは表示されない

**内装、夏季結露、その他:**
- [ ] 各カテゴリのフォルダのみ表示される
- [ ] 他のカテゴリのフォルダは表示されない

### 5. データベースレベルでの確認

#### 全カテゴリのフォルダ確認
```sql
-- 施設IDを指定して全フォルダを確認
SELECT id, name, category, parent_id, path, created_at
FROM document_folders
WHERE facility_id = [施設ID]
ORDER BY category, created_at;
```

#### カテゴリ別の集計
```sql
-- カテゴリ別のフォルダ数を確認
SELECT 
    CASE 
        WHEN category IS NULL THEN 'メインドキュメント'
        WHEN category LIKE 'lifeline_%' THEN 'ライフライン設備'
        WHEN category LIKE 'maintenance_%' THEN '修繕履歴'
        ELSE 'その他'
    END as category_type,
    category,
    COUNT(*) as folder_count
FROM document_folders
WHERE facility_id = [施設ID]
GROUP BY category
ORDER BY category_type, category;
```

### 6. ファイルアップロードでの確認

各カテゴリでファイルをアップロードし、同様の独立性を確認：

#### メインドキュメント
1. メインドキュメントタブでファイルをアップロード
2. `document_files` テーブルで `category` が `NULL` であることを確認

#### ライフライン設備
1. 各設備カテゴリでファイルをアップロード
2. `document_files` テーブルで適切な `category` が設定されていることを確認

#### 修繕履歴
1. 各修繕カテゴリでファイルをアップロード
2. `document_files` テーブルで適切な `category` が設定されていることを確認

```sql
-- ファイルのカテゴリ確認
SELECT id, original_name, category, folder_id, created_at
FROM document_files
WHERE facility_id = [施設ID]
ORDER BY category, created_at;
```

### 7. エラーケースの確認

#### 7.1 存在しないカテゴリでのアクセス
ブラウザのコンソールで以下を実行：
```javascript
// 不正なカテゴリでのAPI呼び出し
fetch('/api/facilities/[施設ID]/lifeline-documents/invalid_category/folders', {
    method: 'GET',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});
// 404エラーが返されることを確認
```

#### 7.2 カテゴリ不一致のフォルダへのファイルアップロード
- 異なるカテゴリのフォルダにファイルをアップロードしようとした場合、適切にエラーが返されることを確認

### 8. パフォーマンス確認

#### クエリ実行時間の測定
```sql
-- メインドキュメント取得のパフォーマンス
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = [施設ID] AND category IS NULL;
-- インデックス idx_facility_category が使用されることを確認

-- ライフライン設備取得のパフォーマンス
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = [施設ID] AND category = 'lifeline_electrical';
-- インデックス idx_facility_category が使用されることを確認
```

#### 実行時間の確認
- [ ] フォルダ一覧の取得が1秒以内に完了する
- [ ] ファイル一覧の取得が1秒以内に完了する
- [ ] フォルダ作成が2秒以内に完了する

## 検証結果の記録

### 成功基準
- [ ] すべてのカテゴリでフォルダが正常に作成できる
- [ ] 各システムで他のカテゴリのドキュメントが表示されない
- [ ] データベースで適切な `category` 値が設定されている
- [ ] パフォーマンスが要件を満たしている（1秒以内）
- [ ] エラーケースが適切に処理される

### 問題が発生した場合

#### デバッグ手順
1. ブラウザのコンソールでJavaScriptエラーを確認
2. Laravelのログファイルを確認（`storage/logs/laravel.log`）
3. データベースのクエリログを確認
4. ネットワークタブでAPIレスポンスを確認

#### よくある問題と解決方法

**問題1: フォルダが作成されない**
- 原因: JavaScript エラー、API エンドポイントの問題
- 解決: コンソールとネットワークタブを確認

**問題2: 他のカテゴリのフォルダが表示される**
- 原因: スコープメソッドが適用されていない
- 解決: サービス層のクエリを確認

**問題3: category が正しく設定されない**
- 原因: サービス層でのカテゴリ設定ロジックの問題
- 解決: `LifelineDocumentService` または `MaintenanceDocumentService` のコードを確認

## 検証完了チェックリスト

- [ ] メインドキュメントでフォルダ作成成功
- [ ] ライフライン設備（全5カテゴリ）でフォルダ作成成功
- [ ] 修繕履歴（全4カテゴリ）でフォルダ作成成功
- [ ] メインドキュメントタブに他のカテゴリが表示されない
- [ ] ライフライン設備に他のカテゴリが表示されない
- [ ] 修繕履歴に他のカテゴリが表示されない
- [ ] データベースで適切な category 値が設定されている
- [ ] パフォーマンス要件を満たしている
- [ ] エラーケースが適切に処理される

## 次のステップ

検証が完了したら：
1. 検証結果を記録
2. 問題があれば修正
3. タスク 9.2（パフォーマンステスト）に進む
4. タスク 9.3（本番環境へのデプロイ）の準備

## 参考資料

- [カテゴリ実装ガイド](./category-implementation-guide.md)
- [トラブルシューティングガイド](./category-troubleshooting-guide.md)
- [要件定義書](../../.kiro/specs/document-category-separation/requirements.md)
- [設計書](../../.kiro/specs/document-category-separation/design.md)
