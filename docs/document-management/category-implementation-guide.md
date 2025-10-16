# ドキュメント管理システム - カテゴリ実装ガイド

## 概要

このガイドでは、ドキュメント管理システムにおけるカテゴリ分離機能の実装方法を説明します。メインドキュメント、ライフライン設備、修繕履歴の3つのシステムが独立して動作するための実装パターンを提供します。

## カテゴリ値の命名規則

### 基本原則

1. **NULL値**: メインドキュメントを表す
2. **プレフィックス方式**: システムタイプ + アンダースコア + カテゴリ名
3. **小文字とアンダースコア**: すべて小文字で、単語の区切りはアンダースコア

### カテゴリ値の定義

#### メインドキュメント
```php
category = NULL
```
- 施設全体の汎用ドキュメント
- 従来のドキュメント管理タブで使用
- 既存データとの後方互換性を保証

#### ライフライン設備
```php
// プレフィックス: lifeline_
'lifeline_electrical'      // 電気設備
'lifeline_gas'             // ガス設備
'lifeline_water'           // 水道設備
'lifeline_elevator'        // エレベーター設備
'lifeline_hvac_lighting'   // 空調・照明設備
```

#### 修繕履歴
```php
// プレフィックス: maintenance_
'maintenance_exterior'            // 外装
'maintenance_interior'            // 内装
'maintenance_summer_condensation' // 夏季結露
'maintenance_other'               // その他
```

### 命名規則の例

```php
// ✅ 正しい命名
'lifeline_electrical'
'maintenance_exterior'
NULL

// ❌ 間違った命名
'Lifeline_Electrical'  // 大文字を使用
'lifeline-electrical'  // ハイフンを使用
'electrical'           // プレフィックスなし
'lifeline_'            // カテゴリ名なし
```

## モデルでのカテゴリ設定

### DocumentFolderモデル

#### fillableプロパティ
```php
protected $fillable = [
    'facility_id',
    'parent_id',
    'category',  // カテゴリフィールドを追加
    'name',
    'path',
    'created_by',
];
```

#### スコープメソッド
```php
/**
 * メインドキュメントのみ取得
 */
public function scopeMain($query)
{
    return $query->whereNull('category');
}

/**
 * ライフライン設備のドキュメントを取得
 * 
 * @param string|null $category 特定のカテゴリ（例: 'electrical'）
 */
public function scopeLifeline($query, ?string $category = null)
{
    $query = $query->where('category', 'like', 'lifeline_%');
    
    if ($category) {
        $query->where('category', 'lifeline_' . $category);
    }
    
    return $query;
}

/**
 * 修繕履歴のドキュメントを取得
 * 
 * @param string|null $category 特定のカテゴリ（例: 'exterior'）
 */
public function scopeMaintenance($query, ?string $category = null)
{
    $query = $query->where('category', 'like', 'maintenance_%');
    
    if ($category) {
        $query->where('category', 'maintenance_' . $category);
    }
    
    return $query;
}
```

#### カテゴリ判定メソッド
```php
/**
 * メインドキュメントかどうか判定
 */
public function isMain(): bool
{
    return $this->category === null;
}

/**
 * ライフライン設備のドキュメントかどうか判定
 */
public function isLifeline(): bool
{
    return $this->category && str_starts_with($this->category, 'lifeline_');
}

/**
 * 修繕履歴のドキュメントかどうか判定
 */
public function isMaintenance(): bool
{
    return $this->category && str_starts_with($this->category, 'maintenance_');
}

/**
 * カテゴリ名を取得（プレフィックスなし）
 */
public function getCategoryName(): ?string
{
    if ($this->isLifeline()) {
        return str_replace('lifeline_', '', $this->category);
    }
    
    if ($this->isMaintenance()) {
        return str_replace('maintenance_', '', $this->category);
    }
    
    return null;
}
```

### DocumentFileモデル

DocumentFileモデルも同様の実装を行います：

```php
protected $fillable = [
    'facility_id',
    'folder_id',
    'category',  // カテゴリフィールドを追加
    'original_name',
    'stored_name',
    'file_path',
    'file_size',
    'mime_type',
    'file_extension',
    'uploaded_by',
];

// DocumentFolderと同じスコープメソッドとカテゴリ判定メソッドを実装
```

## サービス層でのカテゴリ設定

### DocumentService（メインドキュメント）

#### フォルダ作成
```php
public function createFolder(Facility $facility, ?DocumentFolder $parent, string $name, User $user): DocumentFolder
{
    $folder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => $parent?->id,
        'category' => null,  // メインドキュメントはNULL
        'name' => $name,
        'path' => $this->buildPath($parent, $name),
        'created_by' => $user->id,
    ]);
    
    return $folder;
}
```

#### ファイルアップロード
```php
public function uploadFile(Facility $facility, ?DocumentFolder $folder, UploadedFile $file, User $user): DocumentFile
{
    $documentFile = DocumentFile::create([
        'facility_id' => $facility->id,
        'folder_id' => $folder?->id,
        'category' => null,  // メインドキュメントはNULL
        'original_name' => $file->getClientOriginalName(),
        'stored_name' => $storedName,
        'file_path' => $filePath,
        'file_size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
        'file_extension' => $file->getClientOriginalExtension(),
        'uploaded_by' => $user->id,
    ]);
    
    return $documentFile;
}
```

#### フォルダ内容取得
```php
public function getFolderContents(Facility $facility, ?DocumentFolder $folder, array $options = []): array
{
    // メインドキュメントのみ取得
    $foldersQuery = DocumentFolder::main()
        ->where('facility_id', $facility->id)
        ->where('parent_id', $folder?->id);

    $filesQuery = DocumentFile::main()
        ->where('facility_id', $facility->id)
        ->where('folder_id', $folder?->id);

    // ... 残りの処理
    
    return [
        'folders' => $folders,
        'files' => $files,
        'current_folder' => $folder,
    ];
}
```

### LifelineDocumentService（ライフライン設備）

#### ルートフォルダの取得または作成
```php
public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
{
    // カテゴリ値を生成（プレフィックス付き）
    $categoryValue = 'lifeline_' . $category;
    $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

    // 既存のルートフォルダを検索
    $rootFolder = DocumentFolder::lifeline($category)
        ->where('facility_id', $facility->id)
        ->whereNull('parent_id')
        ->where('name', $categoryName)
        ->first();

    if ($rootFolder) {
        return $rootFolder;
    }

    // ルートフォルダを作成
    $rootFolder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => null,
        'category' => $categoryValue,  // lifeline_electrical など
        'name' => $categoryName,
        'path' => $categoryName,
        'created_by' => $user->id,
    ]);

    return $rootFolder;
}
```

#### サブフォルダ作成
```php
protected function createDefaultSubfolders(Facility $facility, DocumentFolder $parentFolder, User $user): void
{
    foreach (self::DEFAULT_SUBFOLDERS as $key => $name) {
        DocumentFolder::create([
            'facility_id' => $facility->id,
            'parent_id' => $parentFolder->id,
            'category' => $parentFolder->category,  // 親のカテゴリを継承
            'name' => $name,
            'path' => $parentFolder->path . '/' . $name,
            'created_by' => $user->id,
        ]);
    }
}
```

#### ファイルアップロード
```php
public function uploadCategoryFile(
    Facility $facility,
    string $category,
    ?DocumentFolder $folder,
    UploadedFile $file,
    User $user
): DocumentFile {
    // フォルダのカテゴリを継承
    $categoryValue = $folder ? $folder->category : 'lifeline_' . $category;

    $documentFile = DocumentFile::create([
        'facility_id' => $facility->id,
        'folder_id' => $folder?->id,
        'category' => $categoryValue,  // フォルダのカテゴリを継承
        'original_name' => $file->getClientOriginalName(),
        // ... その他のフィールド
    ]);

    return $documentFile;
}
```

#### カテゴリのドキュメント取得
```php
public function getCategoryDocuments(Facility $facility, string $category, array $options = []): array
{
    // 指定されたカテゴリのフォルダのみ取得
    $folders = DocumentFolder::lifeline($category)
        ->where('facility_id', $facility->id)
        ->where('parent_id', $currentFolder?->id)
        ->orderBy('name')
        ->get();

    // 指定されたカテゴリのファイルのみ取得
    $files = DocumentFile::lifeline($category)
        ->where('facility_id', $facility->id)
        ->where('folder_id', $currentFolder?->id)
        ->orderBy('original_name')
        ->get();

    return [
        'folders' => $folders,
        'files' => $files,
        'current_folder' => $currentFolder,
    ];
}
```

### MaintenanceDocumentService（修繕履歴）

MaintenanceDocumentServiceは、LifelineDocumentServiceと同様の実装パターンを使用しますが、プレフィックスを`maintenance_`に変更します：

```php
public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
{
    // カテゴリ値を生成（maintenance_プレフィックス）
    $categoryValue = 'maintenance_' . $category;
    $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

    // 既存のルートフォルダを検索
    $rootFolder = DocumentFolder::maintenance($category)
        ->where('facility_id', $facility->id)
        ->whereNull('parent_id')
        ->where('name', $categoryName)
        ->first();

    if ($rootFolder) {
        return $rootFolder;
    }

    // ルートフォルダを作成
    $rootFolder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => null,
        'category' => $categoryValue,  // maintenance_exterior など
        'name' => $categoryName,
        'path' => $categoryName,
        'created_by' => $user->id,
    ]);

    return $rootFolder;
}
```

## コントローラーでの使用例

### DocumentController（メインドキュメント）

```php
public function getFolderContents(Request $request, Facility $facility)
{
    $this->authorize('view', [DocumentFolder::class, $facility]);

    $folderId = $request->input('folder_id');
    $folder = $folderId ? DocumentFolder::main()->findOrFail($folderId) : null;

    $contents = $this->documentService->getFolderContents($facility, $folder);

    return response()->json([
        'success' => true,
        'data' => $contents,
    ]);
}
```

### LifelineDocumentController

```php
public function getCategoryDocuments(Request $request, Facility $facility, string $category)
{
    $this->authorize('view', [LifelineEquipment::class, $facility]);

    // カテゴリ検証
    $normalizedCategory = str_replace('-', '_', $category);
    if (!array_key_exists($normalizedCategory, LifelineEquipment::CATEGORIES)) {
        return response()->json(['success' => false, 'message' => 'Invalid category'], 400);
    }

    $folderId = $request->input('folder_id');
    $folder = $folderId ? DocumentFolder::lifeline($normalizedCategory)->findOrFail($folderId) : null;

    $contents = $this->lifelineDocumentService->getCategoryDocuments(
        $facility,
        $normalizedCategory,
        ['current_folder' => $folder]
    );

    return response()->json([
        'success' => true,
        'data' => $contents,
    ]);
}
```

### MaintenanceDocumentController

```php
public function getCategoryDocuments(Request $request, Facility $facility, string $category)
{
    $this->authorize('view', [MaintenanceHistory::class, $facility]);

    // カテゴリ検証
    $validCategories = ['exterior', 'interior', 'summer_condensation', 'other'];
    if (!in_array($category, $validCategories)) {
        return response()->json(['success' => false, 'message' => 'Invalid category'], 400);
    }

    $folderId = $request->input('folder_id');
    $folder = $folderId ? DocumentFolder::maintenance($category)->findOrFail($folderId) : null;

    $contents = $this->maintenanceDocumentService->getCategoryDocuments(
        $facility,
        $category,
        ['current_folder' => $folder]
    );

    return response()->json([
        'success' => true,
        'data' => $contents,
    ]);
}
```

## データベースクエリの最適化

### インデックスの活用

```sql
-- 複合インデックスが自動的に使用される
SELECT * FROM document_folders 
WHERE facility_id = 1 AND category IS NULL;

SELECT * FROM document_folders 
WHERE facility_id = 1 AND category = 'lifeline_electrical';

SELECT * FROM document_folders 
WHERE facility_id = 1 AND category LIKE 'lifeline_%';
```

### クエリ例

```php
// メインドキュメントの統計
$mainStats = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->selectRaw('COUNT(*) as folder_count')
    ->first();

// ライフライン設備の全カテゴリ統計
$lifelineStats = DocumentFolder::lifeline()
    ->where('facility_id', $facilityId)
    ->selectRaw('category, COUNT(*) as count')
    ->groupBy('category')
    ->get();

// 特定カテゴリの統計
$electricalStats = DocumentFolder::lifeline('electrical')
    ->where('facility_id', $facilityId)
    ->selectRaw('COUNT(*) as folder_count')
    ->first();
```

## テストでの使用例

### モデルスコープのテスト

```php
/** @test */
public function main_scope_returns_only_main_documents()
{
    $mainFolder = DocumentFolder::factory()->create(['category' => null]);
    $lifelineFolder = DocumentFolder::factory()->create(['category' => 'lifeline_electrical']);
    
    $folders = DocumentFolder::main()->get();
    
    $this->assertTrue($folders->contains($mainFolder));
    $this->assertFalse($folders->contains($lifelineFolder));
}

/** @test */
public function lifeline_scope_returns_only_lifeline_documents()
{
    $mainFolder = DocumentFolder::factory()->create(['category' => null]);
    $electricalFolder = DocumentFolder::factory()->create(['category' => 'lifeline_electrical']);
    $gasFolder = DocumentFolder::factory()->create(['category' => 'lifeline_gas']);
    
    $folders = DocumentFolder::lifeline('electrical')->get();
    
    $this->assertFalse($folders->contains($mainFolder));
    $this->assertTrue($folders->contains($electricalFolder));
    $this->assertFalse($folders->contains($gasFolder));
}
```

### サービス統合テスト

```php
/** @test */
public function lifeline_documents_do_not_appear_in_main_documents()
{
    $facility = Facility::factory()->create();
    $user = User::factory()->create();
    
    // ライフライン設備でフォルダ作成
    $lifelineFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
        $facility,
        'electrical',
        $user
    );
    
    // メインドキュメントで取得
    $mainContents = $this->documentService->getFolderContents($facility, null);
    
    // ライフライン設備のフォルダは表示されない
    $folderIds = collect($mainContents['folders'])->pluck('id');
    $this->assertFalse($folderIds->contains($lifelineFolder->id));
}
```

## ベストプラクティス

### 1. カテゴリ値の一貫性

```php
// ✅ 推奨: 定数を使用
class LifelineDocumentService
{
    const CATEGORY_PREFIX = 'lifeline_';
    
    protected function getCategoryValue(string $category): string
    {
        return self::CATEGORY_PREFIX . $category;
    }
}

// ❌ 非推奨: ハードコーディング
$category = 'lifeline_electrical';  // タイポのリスク
```

### 2. スコープメソッドの活用

```php
// ✅ 推奨: スコープメソッドを使用
$folders = DocumentFolder::lifeline('electrical')
    ->where('facility_id', $facilityId)
    ->get();

// ❌ 非推奨: 直接WHERE句を使用
$folders = DocumentFolder::where('category', 'lifeline_electrical')
    ->where('facility_id', $facilityId)
    ->get();
```

### 3. カテゴリの継承

```php
// ✅ 推奨: 親のカテゴリを継承
DocumentFolder::create([
    'parent_id' => $parentFolder->id,
    'category' => $parentFolder->category,  // 親から継承
    // ...
]);

// ❌ 非推奨: カテゴリを再指定
DocumentFolder::create([
    'parent_id' => $parentFolder->id,
    'category' => 'lifeline_electrical',  // ハードコーディング
    // ...
]);
```

### 4. カテゴリ検証

```php
// ✅ 推奨: カテゴリ検証を実装
$validCategories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];
if (!in_array($category, $validCategories)) {
    throw new InvalidArgumentException("Invalid category: {$category}");
}

// ❌ 非推奨: 検証なし
$categoryValue = 'lifeline_' . $category;  // 不正な値の可能性
```

## まとめ

このガイドに従うことで：

1. **一貫性**: すべてのシステムで統一されたカテゴリ命名規則を使用
2. **保守性**: スコープメソッドとカテゴリ判定メソッドで可読性の高いコード
3. **拡張性**: 新しいカテゴリの追加が容易
4. **パフォーマンス**: インデックスを活用した効率的なクエリ
5. **独立性**: 各システムが互いに干渉しない明確な分離

カテゴリ分離機能を実装する際は、このガイドを参照して一貫した実装を行ってください。
