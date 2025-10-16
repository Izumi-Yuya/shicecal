# ドキュメント管理システムのカテゴリ分離 - 実装完了サマリー

## 実装完了日
2025年10月16日

## 問題の概要
ライフライン設備でドキュメント（フォルダやファイル）を作成すると、メインのドキュメントタブにも表示されてしまう問題がありました。これは、3つのドキュメント管理システム（メイン、ライフライン設備、修繕履歴）が同じデータベーステーブルを共有しており、カテゴリによる明確な分離がなかったためです。

## 実装した解決策

### 1. データベーススキーマの拡張
- `document_folders`テーブルに`category`カラムを追加（VARCHAR(50), nullable）
- `document_files`テーブルに`category`カラムを追加（VARCHAR(50), nullable）
- パフォーマンス最適化のため、複合インデックス`(facility_id, category)`を両テーブルに追加

### 2. カテゴリ値の定義
```
- NULL: メインドキュメント管理
- 'lifeline_electrical': 電気設備
- 'lifeline_gas': ガス設備
- 'lifeline_water': 水道設備
- 'lifeline_elevator': エレベーター設備
- 'lifeline_hvac_lighting': 空調・照明設備
- 'maintenance_exterior': 外装修繕
- 'maintenance_interior': 内装修繕
- 'maintenance_summer_condensation': 夏季結露
- 'maintenance_other': その他修繕
```

### 3. モデル層の拡張

#### DocumentFolderモデル
```php
// fillableに'category'を追加
protected $fillable = ['facility_id', 'parent_id', 'category', 'name', 'path', 'created_by'];

// スコープメソッド
public function scopeMain($query) { return $query->whereNull('category'); }
public function scopeLifeline($query, ?string $category = null) { ... }
public function scopeMaintenance($query, ?string $category = null) { ... }

// カテゴリ判定メソッド
public function isMain(): bool { return $this->category === null; }
public function isLifeline(): bool { return str_starts_with($this->category, 'lifeline_'); }
public function isMaintenance(): bool { return str_starts_with($this->category, 'maintenance_'); }
```

#### DocumentFileモデル
DocumentFolderと同様のスコープメソッドとカテゴリ判定メソッドを実装

### 4. サービス層の更新

#### DocumentService（メインドキュメント専用化）
```php
// フォルダ作成時
DocumentFolder::create([
    'category' => null,  // メインドキュメント
    // ...
]);

// ファイルアップロード時
DocumentFile::create([
    'category' => null,  // メインドキュメント
    // ...
]);

// フォルダ内容取得時
$foldersQuery = DocumentFolder::main()  // メインドキュメントのみ
    ->where('facility_id', $facility->id)
    ->where('parent_id', $folder?->id);

$filesQuery = DocumentFile::main()  // メインドキュメントのみ
    ->where('facility_id', $facility->id)
    ->where('folder_id', $folder?->id);
```

#### LifelineDocumentService
既に`category => 'lifeline_{$category}'`を設定する実装が存在していました。

#### MaintenanceDocumentService
既に`category => 'maintenance_{$category}'`を設定する実装が存在していました。

## 実装済みファイル

### マイグレーション
- `database/migrations/2025_10_15_204829_add_category_to_document_tables.php`
  - categoryカラムの追加
  - 複合インデックスの作成
  - 既存データの自動分類

### モデル
- `app/Models/DocumentFolder.php`
  - categoryフィールドの追加
  - スコープメソッドの実装
  - カテゴリ判定メソッドの実装

- `app/Models/DocumentFile.php`
  - categoryフィールドの追加
  - スコープメソッドの実装
  - カテゴリ判定メソッドの実装

### サービス
- `app/Services/DocumentService.php`
  - createFolder: category => null
  - uploadFile: category => null
  - getFolderContents: main()スコープを使用
  - getAvailableFileTypes: main()スコープを使用
  - getFolderStats: main()スコープを使用

- `app/Services/LifelineDocumentService.php`
  - 既に適切なカテゴリ設定が実装済み

- `app/Services/MaintenanceDocumentService.php`
  - 既に適切なカテゴリ設定が実装済み

## 動作確認

### テスト項目
1. ✅ メインドキュメントタブでフォルダを作成 → category = NULL
2. ✅ ライフライン設備（電気設備）でフォルダを作成 → category = 'lifeline_electrical'
3. ✅ 修繕履歴（外装）でフォルダを作成 → category = 'maintenance_exterior'
4. ✅ メインドキュメントタブにライフライン設備のフォルダが表示されない
5. ✅ ライフライン設備にメインドキュメントのフォルダが表示されない
6. ✅ 修繕履歴に他のカテゴリのフォルダが表示されない

### 確認コマンド
```bash
# メインドキュメントのフォルダ数を確認
php artisan tinker --execute="
echo 'Main documents: ' . \App\Models\DocumentFolder::main()->count() . PHP_EOL;
echo 'Lifeline documents: ' . \App\Models\DocumentFolder::lifeline()->count() . PHP_EOL;
echo 'Maintenance documents: ' . \App\Models\DocumentFolder::maintenance()->count() . PHP_EOL;
"

# 特定の施設のカテゴリ別フォルダを確認
php artisan tinker --execute="
\$facilityId = 102;
echo 'Facility ' . \$facilityId . ' folders:' . PHP_EOL;
echo '  Main: ' . \App\Models\DocumentFolder::main()->where('facility_id', \$facilityId)->count() . PHP_EOL;
echo '  Lifeline: ' . \App\Models\DocumentFolder::lifeline()->where('facility_id', \$facilityId)->count() . PHP_EOL;
echo '  Maintenance: ' . \App\Models\DocumentFolder::maintenance()->where('facility_id', \$facilityId)->count() . PHP_EOL;
"
```

## パフォーマンス最適化

### インデックスの効果
```sql
-- 複合インデックスが使用されることを確認
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = 102 AND category IS NULL;
-- 結果: idx_folders_facility_category インデックスを使用

EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = 102 AND category = 'lifeline_electrical';
-- 結果: idx_folders_facility_category インデックスを使用
```

### キャッシュキーの分離
```php
// メインドキュメント
"facility_file_types_main_{$facility->id}"

// ライフライン設備
"facility_file_types_lifeline_{$category}_{$facility->id}"

// 修繕履歴
"facility_file_types_maintenance_{$category}_{$facility->id}"
```

## 既存データの互換性

### 自動マイグレーション
マイグレーション実行時に、既存のライフライン設備フォルダと修繕履歴フォルダが自動的に適切なカテゴリに分類されました：

```php
// ライフライン設備フォルダの自動分類
'電気設備' → 'lifeline_electrical'
'ガス設備' → 'lifeline_gas'
'水道設備' → 'lifeline_water'
// ...

// 修繕履歴フォルダの自動分類
'外装' → 'maintenance_exterior'
'内装' → 'maintenance_interior'
'夏季結露' → 'maintenance_summer_condensation'
```

### 既存のメインドキュメント
カテゴリが設定されていない既存のフォルダとファイルは、`category = NULL`のままメインドキュメントとして扱われます。

## 今後の拡張性

### 新しいカテゴリの追加
新しいカテゴリを追加する場合は、以下の手順で実施：

1. カテゴリ値を定義（例: `'lifeline_security'`）
2. サービスクラスでカテゴリを設定
3. 必要に応じてマイグレーションで既存データを分類

### カテゴリ固有のフィールド
将来的に、カテゴリ固有のフィールドが必要になった場合は、完全なテーブル分離を検討します。

## トラブルシューティング

### 問題: ライフライン設備のフォルダがメインドキュメントに表示される
**原因**: DocumentServiceで`main()`スコープが使用されていない
**解決**: `buildQueries`メソッドで`DocumentFolder::main()`を使用

### 問題: 既存のライフライン設備フォルダがカテゴリ分類されていない
**原因**: マイグレーションが実行されていない、またはフォルダ名が一致しない
**解決**: マイグレーションを再実行、または手動でカテゴリを設定

### 問題: パフォーマンスが低下した
**原因**: インデックスが使用されていない
**解決**: `EXPLAIN`でクエリプランを確認し、インデックスが使用されていることを確認

## まとめ

この実装により、3つのドキュメント管理システムが完全に分離され、以下の目標を達成しました：

1. ✅ ライフライン設備でフォルダを作成しても、メインドキュメントタブに表示されない
2. ✅ メインドキュメントでフォルダを作成しても、ライフライン設備に表示されない
3. ✅ 修繕履歴でフォルダを作成しても、他のシステムに表示されない
4. ✅ 既存のドキュメントが引き続き正常に動作する
5. ✅ パフォーマンスが維持される（複合インデックスによる最適化）
6. ✅ 将来的な拡張が容易（新しいカテゴリの追加が簡単）

システムは設計通りに動作し、各ドキュメント管理システムが独立して機能しています。
