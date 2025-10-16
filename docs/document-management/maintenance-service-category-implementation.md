# MaintenanceDocumentService カテゴリ分離実装サマリー

## 実装日
2025年10月16日

## 概要
修繕履歴ドキュメント管理サービス（MaintenanceDocumentService）にカテゴリ分離機能を実装しました。これにより、修繕履歴のドキュメントがメインドキュメントやライフライン設備のドキュメントと混在しないようになりました。

## 実装内容

### 1. getOrCreateCategoryRootFolderメソッドの更新

**変更前:**
- カテゴリ値を設定せずにルートフォルダを作成
- 名前のみでフォルダを識別

**変更後:**
- `category => 'maintenance_{$category}'` を設定してルートフォルダを作成
- `DocumentFolder::maintenance($category)` スコープを使用してフォルダを検索
- カテゴリ値をログに記録

```php
$categoryValue = 'maintenance_' . $category;
$rootFolder = DocumentFolder::maintenance($category)
    ->where('facility_id', $facility->id)
    ->whereNull('parent_id')
    ->where('name', $categoryName)
    ->first();

if (!$rootFolder) {
    $rootFolder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => null,
        'category' => $categoryValue,  // カテゴリを設定
        'name' => $categoryName,
        'path' => $categoryName,
        'created_by' => $user->id,
    ]);
}
```

### 2. createDefaultSubfoldersメソッドの更新

**変更前:**
- DocumentServiceを使用してサブフォルダを作成
- カテゴリ継承なし

**変更後:**
- 直接DocumentFolder::createを使用
- 親フォルダの`category`を継承

```php
DocumentFolder::create([
    'facility_id' => $facility->id,
    'parent_id' => $parentFolder->id,
    'category' => $parentFolder->category,  // 親のカテゴリを継承
    'name' => $name,
    'path' => $parentFolder->path . '/' . $name,
    'created_by' => $user->id,
]);
```

### 3. uploadCategoryFileメソッドの更新

**変更前:**
- DocumentServiceを使用してファイルをアップロード
- カテゴリ継承なし

**変更後:**
- 直接DocumentFile::createを使用
- フォルダの`category`を継承

```php
$documentFile = DocumentFile::create([
    'facility_id' => $facility->id,
    'folder_id' => $targetFolder->id,
    'category' => $targetFolder->category,  // フォルダのカテゴリを継承
    'original_name' => $file->getClientOriginalName(),
    // ... その他のフィールド
]);
```

### 4. createCategoryFolderメソッドの更新

**変更前:**
- DocumentServiceを使用してフォルダを作成
- カテゴリ継承なし

**変更後:**
- 直接DocumentFolder::createを使用
- 親フォルダの`category`を継承
- 詳細なログ出力を追加

```php
$newFolder = DocumentFolder::create([
    'facility_id' => $facility->id,
    'parent_id' => $parentFolder->id,
    'category' => $parentFolder->category,  // 親のカテゴリを継承
    'name' => $folderName,
    'path' => $parentFolder->path . '/' . $folderName,
    'created_by' => $user->id,
]);
```

### 5. getCategoryDocumentsメソッドの更新

**変更前:**
- カテゴリフィルタリングなし
- DocumentServiceを使用してフォルダ内容を取得

**変更後:**
- `DocumentFolder::maintenance($category)` スコープを使用
- `DocumentFile::maintenance($category)` スコープを使用
- カテゴリ別にフォルダとファイルをフィルタリング
- ページネーション、ソート、統計情報を直接実装

```php
// フォルダ取得（カテゴリでフィルタリング）
$foldersQuery = DocumentFolder::maintenance($category)
    ->where('facility_id', $facility->id)
    ->where('parent_id', $currentFolder->id)
    ->with(['creator:id,name']);

// ファイル取得（カテゴリでフィルタリング）
$filesQuery = DocumentFile::maintenance($category)
    ->where('facility_id', $facility->id)
    ->where('folder_id', $currentFolder->id)
    ->with(['uploader:id,name']);
```

### 6. 新規メソッドの追加

#### getCategoryStatsメソッド
カテゴリ別の統計情報を取得します。

```php
public function getCategoryStats(Facility $facility, string $category): array
{
    // カテゴリでフィルタリングされた統計情報を返す
    $fileStats = DocumentFile::maintenance($category)
        ->where('facility_id', $facility->id)
        ->whereIn('folder_id', $folderIds)
        ->selectRaw('COUNT(*) as count, SUM(file_size) as total_size')
        ->first();
    
    // ...
}
```

#### getAvailableCategoriesメソッド
利用可能な修繕履歴カテゴリ一覧を取得します。

```php
public function getAvailableCategories(): array
{
    return array_map(function ($key, $name) {
        return [
            'key' => $key,
            'name' => $name,
            'folder_name' => $name,
        ];
    }, array_keys(self::CATEGORY_FOLDER_MAPPING), self::CATEGORY_FOLDER_MAPPING);
}
```

#### searchCategoryFilesメソッド
カテゴリ内のファイルを検索します。

```php
public function searchCategoryFiles(Facility $facility, string $category, string $query, array $options = []): array
{
    // カテゴリでフィルタリングされた検索結果を返す
    $filesQuery = DocumentFile::maintenance($category)
        ->where('facility_id', $facility->id)
        ->whereIn('folder_id', $folderIds)
        ->where('original_name', 'like', "%{$query}%");
    
    // ...
}
```

## カテゴリ値の定義

修繕履歴のカテゴリ値は以下の形式で設定されます：

- `maintenance_exterior` - 外装
- `maintenance_interior` - 内装リニューアル
- `maintenance_summer_condensation` - 夏型結露
- `maintenance_other` - その他

## データベーススキーマ

`document_folders` および `document_files` テーブルの `category` カラムに上記の値が設定されます。

```sql
-- 例：外装カテゴリのルートフォルダ
INSERT INTO document_folders (facility_id, parent_id, category, name, path, created_by)
VALUES (1, NULL, 'maintenance_exterior', '外装', '外装', 1);

-- 例：外装カテゴリのサブフォルダ
INSERT INTO document_folders (facility_id, parent_id, category, name, path, created_by)
VALUES (1, 123, 'maintenance_exterior', '契約書', '外装/契約書', 1);

-- 例：外装カテゴリのファイル
INSERT INTO document_files (facility_id, folder_id, category, original_name, ...)
VALUES (1, 124, 'maintenance_exterior', '契約書.pdf', ...);
```

## 動作確認

### 1. ルートフォルダ作成
```php
$rootFolder = $maintenanceDocumentService->getOrCreateCategoryRootFolder($facility, 'exterior', $user);
// category = 'maintenance_exterior' が設定される
```

### 2. サブフォルダ作成
```php
$result = $maintenanceDocumentService->createCategoryFolder($facility, 'exterior', '契約書', $user);
// 親フォルダのcategory = 'maintenance_exterior' が継承される
```

### 3. ファイルアップロード
```php
$result = $maintenanceDocumentService->uploadCategoryFile($facility, 'exterior', $file, $user);
// フォルダのcategory = 'maintenance_exterior' が継承される
```

### 4. ドキュメント取得
```php
$result = $maintenanceDocumentService->getCategoryDocuments($facility, 'exterior');
// maintenance_exterior カテゴリのドキュメントのみ取得される
```

### 5. 統計情報取得
```php
$stats = $maintenanceDocumentService->getCategoryStats($facility, 'exterior');
// maintenance_exterior カテゴリの統計情報のみ取得される
```

## 期待される効果

1. **データの分離**: 修繕履歴のドキュメントがメインドキュメントやライフライン設備のドキュメントと混在しない
2. **パフォーマンス向上**: カテゴリでフィルタリングすることで、クエリが効率化される
3. **保守性向上**: カテゴリ別にドキュメントを管理できるため、コードの保守が容易になる
4. **拡張性**: 新しいカテゴリの追加が容易

## 既存データへの影響

- 既存の修繕履歴ドキュメント（category = NULL）は、メインドキュメントとして扱われます
- 新規作成される修繕履歴ドキュメントには、適切なカテゴリ値が設定されます
- 既存データの移行は、別途マイグレーションスクリプトで実施可能です

## 関連ファイル

- `app/Services/MaintenanceDocumentService.php` - 修繕履歴ドキュメント管理サービス
- `app/Models/DocumentFolder.php` - フォルダモデル（maintenance スコープ）
- `app/Models/DocumentFile.php` - ファイルモデル（maintenance スコープ）
- `database/migrations/2025_10_15_204829_add_category_to_document_tables.php` - カテゴリカラム追加マイグレーション

## 参考実装

この実装は、LifelineDocumentService の実装パターンに従っています。詳細は以下のドキュメントを参照してください：

- `docs/document-management/lifeline-service-category-implementation.md`
- `docs/document-management/category-implementation-guide.md`

## 次のステップ

1. 機能テストの作成（タスク7）
2. ドキュメントの作成（タスク8）
3. 動作確認とデプロイ（タスク9）

## 実装者
Kiro AI Assistant

## レビュー状態
✅ 実装完了
⏳ テスト待ち
