# ドキュメント管理システムのカテゴリ分離 - 設計書

## 概要

ライフライン設備、修繕履歴、メインドキュメントの3つのドキュメント管理システムを、`category`カラムを使用して明確に分離します。これにより、各システムが独立して動作し、データの混在を防ぎます。

## アーキテクチャ

### データベース層

#### スキーマ変更

```sql
-- document_folders テーブル
ALTER TABLE document_folders 
ADD COLUMN category VARCHAR(50) NULL AFTER facility_id,
ADD INDEX idx_facility_category (facility_id, category);

-- document_files テーブル
ALTER TABLE document_files 
ADD COLUMN category VARCHAR(50) NULL AFTER facility_id,
ADD INDEX idx_facility_category (facility_id, category);
```

#### カテゴリ値の定義

```php
// メインドキュメント
NULL

// ライフライン設備
'lifeline_electrical'      // 電気設備
'lifeline_gas'             // ガス設備
'lifeline_water'           // 水道設備
'lifeline_elevator'        // エレベーター設備
'lifeline_hvac_lighting'   // 空調・照明設備

// 修繕履歴
'maintenance_exterior'            // 外装
'maintenance_interior'            // 内装
'maintenance_summer_condensation' // 夏季結露
'maintenance_other'               // その他
```

### モデル層

#### DocumentFolder モデル

```php
class DocumentFolder extends Model
{
    protected $fillable = [
        'facility_id',
        'parent_id',
        'category',  // 追加
        'name',
        'path',
        'created_by',
    ];

    // スコープ: メインドキュメント
    public function scopeMain($query)
    {
        return $query->whereNull('category');
    }

    // スコープ: ライフライン設備
    public function scopeLifeline($query, ?string $category = null)
    {
        $query = $query->where('category', 'like', 'lifeline_%');
        
        if ($category) {
            $query->where('category', 'lifeline_' . $category);
        }
        
        return $query;
    }

    // スコープ: 修繕履歴
    public function scopeMaintenance($query, ?string $category = null)
    {
        $query = $query->where('category', 'like', 'maintenance_%');
        
        if ($category) {
            $query->where('category', 'maintenance_' . $category);
        }
        
        return $query;
    }

    // カテゴリ判定メソッド
    public function isMain(): bool
    {
        return $this->category === null;
    }

    public function isLifeline(): bool
    {
        return $this->category && str_starts_with($this->category, 'lifeline_');
    }

    public function isMaintenance(): bool
    {
        return $this->category && str_starts_with($this->category, 'maintenance_');
    }

    // カテゴリ名取得
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
}
```

#### DocumentFile モデル

```php
class DocumentFile extends Model
{
    protected $fillable = [
        'facility_id',
        'folder_id',
        'category',  // 追加
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'uploaded_by',
    ];

    // DocumentFolderと同じスコープメソッドを実装
    public function scopeMain($query) { ... }
    public function scopeLifeline($query, ?string $category = null) { ... }
    public function scopeMaintenance($query, ?string $category = null) { ... }
    
    public function isMain(): bool { ... }
    public function isLifeline(): bool { ... }
    public function isMaintenance(): bool { ... }
}
```

### サービス層

#### DocumentService（メインドキュメント管理）

```php
class DocumentService
{
    /**
     * フォルダ作成（メインドキュメント専用）
     */
    public function createFolder(Facility $facility, ?DocumentFolder $parent, string $name, User $user): DocumentFolder
    {
        // categoryはNULLで作成
        $folder = DocumentFolder::create([
            'facility_id' => $facility->id,
            'parent_id' => $parent?->id,
            'category' => null,  // メインドキュメント
            'name' => $name,
            'path' => $path,
            'created_by' => $user->id,
        ]);
        
        return $folder;
    }

    /**
     * ファイルアップロード（メインドキュメント専用）
     */
    public function uploadFile(Facility $facility, ?DocumentFolder $folder, UploadedFile $file, User $user): DocumentFile
    {
        // categoryはNULLで作成
        $documentFile = DocumentFile::create([
            'facility_id' => $facility->id,
            'folder_id' => $folder?->id,
            'category' => null,  // メインドキュメント
            'original_name' => $file->getClientOriginalName(),
            // ... その他のフィールド
        ]);
        
        return $documentFile;
    }

    /**
     * フォルダ内容取得（メインドキュメントのみ）
     */
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
    }

    /**
     * 利用可能なファイルタイプ取得（メインドキュメントのみ）
     */
    public function getAvailableFileTypes(Facility $facility): array
    {
        return DocumentFile::main()
            ->where('facility_id', $facility->id)
            ->selectRaw('file_extension, COUNT(*) as count')
            ->groupBy('file_extension')
            ->get();
    }
}
```

#### LifelineDocumentService（ライフライン設備専用）

```php
class LifelineDocumentService
{
    /**
     * カテゴリのルートフォルダを取得または作成
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
    {
        $categoryValue = 'lifeline_' . $category;
        $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

        // 既存のルートフォルダを検索（カテゴリで識別）
        $rootFolder = DocumentFolder::lifeline($category)
            ->where('facility_id', $facility->id)
            ->whereNull('parent_id')
            ->where('name', $categoryName)
            ->first();

        if ($rootFolder) {
            return $rootFolder;
        }

        // ルートフォルダを作成（カテゴリを設定）
        $rootFolder = DocumentFolder::create([
            'facility_id' => $facility->id,
            'parent_id' => null,
            'category' => $categoryValue,  // ライフライン設備カテゴリ
            'name' => $categoryName,
            'path' => $categoryName,
            'created_by' => $user->id,
        ]);

        return $rootFolder;
    }

    /**
     * サブフォルダ作成（親のカテゴリを継承）
     */
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

    /**
     * カテゴリのドキュメント一覧を取得
     */
    public function getCategoryDocuments(Facility $facility, string $category, array $options = []): array
    {
        $categoryValue = 'lifeline_' . $category;

        // 指定されたカテゴリのフォルダのみ取得
        $folders = DocumentFolder::lifeline($category)
            ->where('facility_id', $facility->id)
            ->where('parent_id', $currentFolder?->id)
            ->get();

        // 指定されたカテゴリのファイルのみ取得
        $files = DocumentFile::lifeline($category)
            ->where('facility_id', $facility->id)
            ->where('folder_id', $currentFolder?->id)
            ->get();

        return [
            'folders' => $folders,
            'files' => $files,
            // ...
        ];
    }
}
```

#### MaintenanceDocumentService（修繕履歴専用）

```php
class MaintenanceDocumentService
{
    /**
     * カテゴリのルートフォルダを取得または作成
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, string $category, User $user): DocumentFolder
    {
        $categoryValue = 'maintenance_' . $category;
        $categoryName = self::CATEGORY_FOLDER_MAPPING[$category] ?? $category;

        // 既存のルートフォルダを検索（カテゴリで識別）
        $rootFolder = DocumentFolder::maintenance($category)
            ->where('facility_id', $facility->id)
            ->whereNull('parent_id')
            ->where('name', $categoryName)
            ->first();

        if ($rootFolder) {
            return $rootFolder;
        }

        // ルートフォルダを作成（カテゴリを設定）
        $rootFolder = DocumentFolder::create([
            'facility_id' => $facility->id,
            'parent_id' => null,
            'category' => $categoryValue,  // 修繕履歴カテゴリ
            'name' => $categoryName,
            'path' => $categoryName,
            'created_by' => $user->id,
        ]);

        return $rootFolder;
    }

    // LifelineDocumentServiceと同様の実装
}
```

## データフロー

### メインドキュメント作成フロー

```
1. ユーザーがメインドキュメントタブでフォルダ作成
   ↓
2. DocumentController::createFolder()
   ↓
3. DocumentService::createFolder()
   - category = NULL で作成
   ↓
4. DocumentFolder::create([
     'category' => null,
     ...
   ])
   ↓
5. メインドキュメントタブにのみ表示
```

### ライフライン設備ドキュメント作成フロー

```
1. ユーザーがライフライン設備（例：電気設備）でフォルダ作成
   ↓
2. LifelineDocumentController::createFolder($category = 'electrical')
   ↓
3. LifelineDocumentService::getOrCreateCategoryRootFolder()
   - category = 'lifeline_electrical' で作成
   ↓
4. DocumentFolder::create([
     'category' => 'lifeline_electrical',
     ...
   ])
   ↓
5. 電気設備のドキュメントセクションにのみ表示
```

### ドキュメント取得フロー

```
メインドキュメントタブ:
DocumentService::getFolderContents()
  ↓
DocumentFolder::main()->where('facility_id', $id)->get()
  ↓
SELECT * FROM document_folders 
WHERE facility_id = ? AND category IS NULL

ライフライン設備（電気設備）:
LifelineDocumentService::getCategoryDocuments('electrical')
  ↓
DocumentFolder::lifeline('electrical')->where('facility_id', $id)->get()
  ↓
SELECT * FROM document_folders 
WHERE facility_id = ? AND category = 'lifeline_electrical'
```

## インデックス戦略

### 複合インデックス

```sql
-- 最も頻繁に使用されるクエリパターンに最適化
CREATE INDEX idx_folders_facility_category ON document_folders(facility_id, category);
CREATE INDEX idx_files_facility_category ON document_files(facility_id, category);

-- 親子関係のクエリに最適化
CREATE INDEX idx_folders_facility_parent ON document_folders(facility_id, parent_id);
CREATE INDEX idx_files_facility_folder ON document_files(facility_id, folder_id);
```

### クエリ最適化

```sql
-- メインドキュメント取得（category IS NULL）
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = 1 AND category IS NULL;
-- インデックス使用: idx_folders_facility_category

-- ライフライン設備取得（category = 'lifeline_electrical'）
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = 1 AND category = 'lifeline_electrical';
-- インデックス使用: idx_folders_facility_category

-- カテゴリ別集計
EXPLAIN SELECT category, COUNT(*) 
FROM document_folders 
WHERE facility_id = 1 
GROUP BY category;
-- インデックス使用: idx_folders_facility_category
```

## エラーハンドリング

### カテゴリ不一致エラー

```php
// フォルダとファイルのカテゴリが一致しない場合
if ($folder && $file->category !== $folder->category) {
    throw new DocumentServiceException(
        'ファイルのカテゴリがフォルダのカテゴリと一致しません。',
        [
            'file_category' => $file->category,
            'folder_category' => $folder->category,
        ]
    );
}
```

### 不正なカテゴリ値エラー

```php
// 許可されていないカテゴリ値
$allowedCategories = [
    null,  // メインドキュメント
    'lifeline_electrical',
    'lifeline_gas',
    // ...
];

if (!in_array($category, $allowedCategories)) {
    throw new InvalidArgumentException(
        "不正なカテゴリ値です: {$category}"
    );
}
```

## テスト戦略

### 単体テスト

```php
// DocumentFolderモデルのテスト
public function test_main_scope_returns_only_main_documents()
{
    // メインドキュメントフォルダ作成
    $mainFolder = DocumentFolder::factory()->create(['category' => null]);
    
    // ライフライン設備フォルダ作成
    $lifelineFolder = DocumentFolder::factory()->create(['category' => 'lifeline_electrical']);
    
    // メインドキュメントのみ取得
    $folders = DocumentFolder::main()->get();
    
    $this->assertTrue($folders->contains($mainFolder));
    $this->assertFalse($folders->contains($lifelineFolder));
}

public function test_lifeline_scope_returns_only_lifeline_documents()
{
    $mainFolder = DocumentFolder::factory()->create(['category' => null]);
    $electricalFolder = DocumentFolder::factory()->create(['category' => 'lifeline_electrical']);
    $gasFolder = DocumentFolder::factory()->create(['category' => 'lifeline_gas']);
    
    // 電気設備のみ取得
    $folders = DocumentFolder::lifeline('electrical')->get();
    
    $this->assertFalse($folders->contains($mainFolder));
    $this->assertTrue($folders->contains($electricalFolder));
    $this->assertFalse($folders->contains($gasFolder));
}
```

### 統合テスト

```php
// カテゴリ分離の統合テスト
public function test_lifeline_documents_do_not_appear_in_main_documents()
{
    $facility = Facility::factory()->create();
    $user = User::factory()->create();
    
    // ライフライン設備でフォルダ作成
    $lifelineService = app(LifelineDocumentService::class);
    $lifelineFolder = $lifelineService->getOrCreateCategoryRootFolder(
        $facility, 
        'electrical', 
        $user
    );
    
    // メインドキュメントで取得
    $documentService = app(DocumentService::class);
    $mainContents = $documentService->getFolderContents($facility, null);
    
    // ライフライン設備のフォルダは表示されない
    $folderIds = collect($mainContents['folders'])->pluck('id');
    $this->assertFalse($folderIds->contains($lifelineFolder->id));
}
```

## パフォーマンス考慮事項

### キャッシュ戦略

```php
// カテゴリ別のキャッシュキー
$cacheKey = "facility_{$facilityId}_documents_{$category}";

// メインドキュメント
$cacheKey = "facility_{$facilityId}_documents_main";

// ライフライン設備
$cacheKey = "facility_{$facilityId}_documents_lifeline_electrical";
```

### クエリ最適化

```php
// N+1問題の回避
$folders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->with(['creator', 'children', 'files'])
    ->get();

// カウントクエリの最適化
$stats = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->selectRaw('COUNT(*) as folder_count')
    ->first();
```

## セキュリティ考慮事項

### カテゴリベースの権限チェック

```php
// ポリシーでカテゴリを考慮
public function view(User $user, DocumentFolder $folder)
{
    // メインドキュメント
    if ($folder->isMain()) {
        return $user->canViewFacility($folder->facility_id);
    }
    
    // ライフライン設備
    if ($folder->isLifeline()) {
        return $user->canViewLifelineEquipment($folder->facility_id);
    }
    
    // 修繕履歴
    if ($folder->isMaintenance()) {
        return $user->canViewMaintenanceHistory($folder->facility_id);
    }
    
    return false;
}
```

## マイグレーション戦略

### ステップ1: カラム追加

```php
public function up()
{
    Schema::table('document_folders', function (Blueprint $table) {
        $table->string('category', 50)->nullable()->after('facility_id');
        $table->index(['facility_id', 'category']);
    });

    Schema::table('document_files', function (Blueprint $table) {
        $table->string('category', 50)->nullable()->after('facility_id');
        $table->index(['facility_id', 'category']);
    });
}
```

### ステップ2: 既存データの分類（オプション）

```php
public function up()
{
    // ライフライン設備フォルダの自動分類
    $lifelineFolderNames = [
        '電気設備' => 'lifeline_electrical',
        'ガス設備' => 'lifeline_gas',
        '水道設備' => 'lifeline_water',
        // ...
    ];

    foreach ($lifelineFolderNames as $name => $category) {
        DB::table('document_folders')
            ->where('name', $name)
            ->whereNull('parent_id')
            ->update(['category' => $category]);
    }
}
```

## まとめ

この設計により：

1. ✅ **明確な分離**: カテゴリカラムで3つのシステムを明確に分離
2. ✅ **後方互換性**: 既存データ（category = NULL）はメインドキュメントとして動作
3. ✅ **パフォーマンス**: 複合インデックスで効率的なクエリ実行
4. ✅ **保守性**: スコープメソッドで可読性の高いコード
5. ✅ **拡張性**: 新しいカテゴリの追加が容易
