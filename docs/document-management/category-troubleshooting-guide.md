# ドキュメント管理システム - カテゴリ分離トラブルシューティングガイド

## 概要

このガイドでは、ドキュメント管理システムのカテゴリ分離機能に関する一般的な問題と解決方法を説明します。

## 目次

1. [カテゴリ不一致エラー](#カテゴリ不一致エラー)
2. [ドキュメントが表示されない](#ドキュメントが表示されない)
3. [フォルダ作成時のエラー](#フォルダ作成時のエラー)
4. [ファイルアップロード時のエラー](#ファイルアップロード時のエラー)
5. [パフォーマンス問題](#パフォーマンス問題)
6. [マイグレーション関連の問題](#マイグレーション関連の問題)
7. [データ整合性の問題](#データ整合性の問題)

---

## カテゴリ不一致エラー

### 問題: フォルダとファイルのカテゴリが一致しない

#### 症状
```
DocumentServiceException: ファイルのカテゴリがフォルダのカテゴリと一致しません。
file_category: lifeline_electrical
folder_category: lifeline_gas
```

#### 原因
- ファイルアップロード時にフォルダのカテゴリを継承していない
- カテゴリ値が手動で設定されている
- サービス層でのカテゴリ処理が不適切

#### 解決方法

**1. フォルダのカテゴリを継承する**
```php
// ❌ 間違った実装
DocumentFile::create([
    'folder_id' => $folder->id,
    'category' => 'lifeline_electrical',  // ハードコーディング
    // ...
]);

// ✅ 正しい実装
DocumentFile::create([
    'folder_id' => $folder->id,
    'category' => $folder->category,  // フォルダから継承
    // ...
]);
```

**2. サービス層での検証を追加**
```php
public function uploadFile(Facility $facility, DocumentFolder $folder, UploadedFile $file, User $user): DocumentFile
{
    // カテゴリ検証
    if ($folder && $file->category !== $folder->category) {
        throw new DocumentServiceException(
            'ファイルのカテゴリがフォルダのカテゴリと一致しません。',
            [
                'file_category' => $file->category,
                'folder_category' => $folder->category,
            ]
        );
    }
    
    // ファイル作成
    $documentFile = DocumentFile::create([
        'folder_id' => $folder->id,
        'category' => $folder->category,  // フォルダのカテゴリを使用
        // ...
    ]);
    
    return $documentFile;
}
```

**3. 既存データの修正**
```php
// 既存のファイルのカテゴリを親フォルダに合わせる
DB::transaction(function () {
    $files = DocumentFile::whereNotNull('folder_id')->get();
    
    foreach ($files as $file) {
        $folder = $file->folder;
        if ($folder && $file->category !== $folder->category) {
            $file->update(['category' => $folder->category]);
        }
    }
});
```

---

## ドキュメントが表示されない

### 問題: メインドキュメントタブにドキュメントが表示されない

#### 症状
- メインドキュメントタブを開いても何も表示されない
- フォルダやファイルが存在するはずなのに空

#### 原因
- スコープメソッドが適用されていない
- カテゴリ値が誤って設定されている
- クエリにフィルタが適用されていない

#### 解決方法

**1. スコープメソッドの適用を確認**
```php
// ❌ 間違った実装
$folders = DocumentFolder::where('facility_id', $facilityId)->get();

// ✅ 正しい実装
$folders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->get();
```

**2. カテゴリ値の確認**
```sql
-- メインドキュメントのカテゴリ値を確認
SELECT id, name, category FROM document_folders 
WHERE facility_id = 1 AND parent_id IS NULL;

-- 期待される結果: category が NULL
```

**3. デバッグログの追加**
```php
public function getFolderContents(Facility $facility, ?DocumentFolder $folder, array $options = []): array
{
    Log::debug('Getting folder contents', [
        'facility_id' => $facility->id,
        'folder_id' => $folder?->id,
        'folder_category' => $folder?->category,
    ]);

    $folders = DocumentFolder::main()
        ->where('facility_id', $facility->id)
        ->where('parent_id', $folder?->id)
        ->get();

    Log::debug('Folders found', [
        'count' => $folders->count(),
        'folder_ids' => $folders->pluck('id')->toArray(),
    ]);

    return [
        'folders' => $folders,
        'files' => $files,
    ];
}
```

### 問題: ライフライン設備のドキュメントが表示されない

#### 症状
- 電気設備タブでドキュメントが表示されない
- 他のカテゴリでは正常に表示される

#### 原因
- カテゴリ値のプレフィックスが間違っている
- スコープメソッドのパラメータが不正
- ルートフォルダが作成されていない

#### 解決方法

**1. カテゴリ値の確認**
```sql
-- ライフライン設備のカテゴリ値を確認
SELECT id, name, category FROM document_folders 
WHERE facility_id = 1 AND category LIKE 'lifeline_%';

-- 期待される結果: category が 'lifeline_electrical' など
```

**2. スコープメソッドのパラメータ確認**
```php
// ❌ 間違った実装
$folders = DocumentFolder::lifeline('lifeline_electrical')->get();  // プレフィックス付き

// ✅ 正しい実装
$folders = DocumentFolder::lifeline('electrical')->get();  // プレフィックスなし
```

**3. ルートフォルダの作成確認**
```php
// ルートフォルダが存在するか確認
$rootFolder = DocumentFolder::lifeline('electrical')
    ->where('facility_id', $facilityId)
    ->whereNull('parent_id')
    ->first();

if (!$rootFolder) {
    // ルートフォルダを作成
    $rootFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
        $facility,
        'electrical',
        $user
    );
}
```

---

## フォルダ作成時のエラー

### 問題: フォルダ作成時に「カテゴリが設定されていません」エラー

#### 症状
```
Exception: フォルダのカテゴリが設定されていません。
```

#### 原因
- サブフォルダ作成時に親のカテゴリを継承していない
- カテゴリ値がNULLまたは空文字列

#### 解決方法

**1. 親フォルダのカテゴリを継承**
```php
// ❌ 間違った実装
DocumentFolder::create([
    'parent_id' => $parentFolder->id,
    'category' => null,  // カテゴリが設定されていない
    // ...
]);

// ✅ 正しい実装
DocumentFolder::create([
    'parent_id' => $parentFolder->id,
    'category' => $parentFolder->category,  // 親から継承
    // ...
]);
```

**2. ルートフォルダ作成時のカテゴリ設定**
```php
// ライフライン設備のルートフォルダ
DocumentFolder::create([
    'facility_id' => $facility->id,
    'parent_id' => null,
    'category' => 'lifeline_' . $category,  // プレフィックス付き
    'name' => $categoryName,
    'path' => $categoryName,
    'created_by' => $user->id,
]);
```

### 問題: 「親フォルダが見つかりません」エラー

#### 症状
```
ModelNotFoundException: No query results for model [DocumentFolder] 123
```

#### 原因
- 親フォルダIDが不正
- 親フォルダが削除されている
- カテゴリスコープで親フォルダが除外されている

#### 解決方法

**1. 親フォルダの存在確認**
```php
public function createFolder(Facility $facility, ?int $parentId, string $name, User $user): DocumentFolder
{
    $parent = null;
    
    if ($parentId) {
        // カテゴリスコープを適用して親フォルダを検索
        $parent = DocumentFolder::main()
            ->where('facility_id', $facility->id)
            ->findOrFail($parentId);
    }
    
    // フォルダ作成
    $folder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => $parent?->id,
        'category' => $parent?->category,  // 親のカテゴリを継承
        'name' => $name,
        'path' => $this->buildPath($parent, $name),
        'created_by' => $user->id,
    ]);
    
    return $folder;
}
```

**2. カテゴリスコープの適用**
```php
// ライフライン設備の場合
$parent = DocumentFolder::lifeline($category)
    ->where('facility_id', $facility->id)
    ->findOrFail($parentId);

// メインドキュメントの場合
$parent = DocumentFolder::main()
    ->where('facility_id', $facility->id)
    ->findOrFail($parentId);
```

---

## ファイルアップロード時のエラー

### 問題: ファイルアップロード後にメインドキュメントに表示される

#### 症状
- ライフライン設備でファイルをアップロード
- メインドキュメントタブにも表示される

#### 原因
- ファイル作成時にカテゴリが設定されていない
- カテゴリ値がNULLになっている

#### 解決方法

**1. ファイル作成時のカテゴリ設定**
```php
// ❌ 間違った実装
DocumentFile::create([
    'folder_id' => $folder->id,
    'category' => null,  // カテゴリが設定されていない
    // ...
]);

// ✅ 正しい実装
DocumentFile::create([
    'folder_id' => $folder->id,
    'category' => $folder ? $folder->category : 'lifeline_' . $category,
    // ...
]);
```

**2. サービス層での検証**
```php
public function uploadCategoryFile(
    Facility $facility,
    string $category,
    ?DocumentFolder $folder,
    UploadedFile $file,
    User $user
): DocumentFile {
    // カテゴリ値を決定
    $categoryValue = $folder ? $folder->category : 'lifeline_' . $category;
    
    // カテゴリ値の検証
    if (!$categoryValue || !str_starts_with($categoryValue, 'lifeline_')) {
        throw new DocumentServiceException('不正なカテゴリ値です: ' . $categoryValue);
    }
    
    // ファイル作成
    $documentFile = DocumentFile::create([
        'folder_id' => $folder?->id,
        'category' => $categoryValue,
        // ...
    ]);
    
    return $documentFile;
}
```

---

## パフォーマンス問題

### 問題: ドキュメント一覧の読み込みが遅い

#### 症状
- ドキュメント一覧の表示に5秒以上かかる
- 大量のドキュメントがある施設で顕著

#### 原因
- インデックスが使用されていない
- N+1問題が発生している
- 不要なデータを取得している

#### 解決方法

**1. インデックスの確認**
```sql
-- インデックスの存在確認
SHOW INDEX FROM document_folders WHERE Key_name = 'idx_facility_category';
SHOW INDEX FROM document_files WHERE Key_name = 'idx_facility_category';

-- インデックスが存在しない場合は作成
CREATE INDEX idx_facility_category ON document_folders(facility_id, category);
CREATE INDEX idx_facility_category ON document_files(facility_id, category);
```

**2. EXPLAINでクエリを分析**
```sql
-- クエリプランの確認
EXPLAIN SELECT * FROM document_folders 
WHERE facility_id = 1 AND category IS NULL;

-- インデックスが使用されているか確認
-- key: idx_facility_category が表示されるべき
```

**3. Eager Loadingの使用**
```php
// ❌ N+1問題が発生
$folders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->get();

foreach ($folders as $folder) {
    echo $folder->creator->name;  // N+1
}

// ✅ Eager Loadingを使用
$folders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->with(['creator', 'children', 'files'])
    ->get();

foreach ($folders as $folder) {
    echo $folder->creator->name;  // 1クエリ
}
```

**4. ページネーションの実装**
```php
// 大量のドキュメントがある場合はページネーション
$folders = DocumentFolder::main()
    ->where('facility_id', $facilityId)
    ->paginate(50);
```

### 問題: カテゴリ別統計の取得が遅い

#### 症状
- 統計情報の取得に時間がかかる
- 複数のカテゴリを集計する際に顕著

#### 解決方法

**1. 集計クエリの最適化**
```php
// ❌ 非効率な実装
$stats = [];
foreach (['electrical', 'gas', 'water'] as $category) {
    $count = DocumentFolder::lifeline($category)
        ->where('facility_id', $facilityId)
        ->count();
    $stats[$category] = $count;
}

// ✅ 効率的な実装
$stats = DocumentFolder::lifeline()
    ->where('facility_id', $facilityId)
    ->selectRaw('category, COUNT(*) as count')
    ->groupBy('category')
    ->pluck('count', 'category');
```

**2. キャッシュの活用**
```php
use Illuminate\Support\Facades\Cache;

public function getCategoryStats(Facility $facility): array
{
    $cacheKey = "facility_{$facility->id}_document_stats";
    
    return Cache::remember($cacheKey, 3600, function () use ($facility) {
        return DocumentFolder::lifeline()
            ->where('facility_id', $facility->id)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    });
}
```

---

## マイグレーション関連の問題

### 問題: マイグレーション実行時にエラー

#### 症状
```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'category'
```

#### 原因
- マイグレーションが既に実行されている
- ロールバックが不完全

#### 解決方法

**1. マイグレーション状態の確認**
```bash
php artisan migrate:status
```

**2. 既存カラムの確認**
```sql
DESCRIBE document_folders;
DESCRIBE document_files;
```

**3. マイグレーションのロールバックと再実行**
```bash
# 最後のマイグレーションをロールバック
php artisan migrate:rollback --step=1

# マイグレーションを再実行
php artisan migrate
```

### 問題: 既存データのカテゴリがNULLのまま

#### 症状
- マイグレーション後も既存データのcategoryがNULL
- ライフライン設備のドキュメントがメインドキュメントに表示される

#### 原因
- データ移行スクリプトが実行されていない
- カテゴリの自動分類が失敗している

#### 解決方法

**1. 手動でカテゴリを設定**
```php
// Artisanコマンドを作成
php artisan make:command MigrateDocumentCategories

// コマンド実装
public function handle()
{
    DB::transaction(function () {
        // ライフライン設備フォルダの分類
        $lifelineFolderNames = [
            '電気設備' => 'lifeline_electrical',
            'ガス設備' => 'lifeline_gas',
            '水道設備' => 'lifeline_water',
            'エレベーター設備' => 'lifeline_elevator',
            '空調・照明設備' => 'lifeline_hvac_lighting',
        ];

        foreach ($lifelineFolderNames as $name => $category) {
            $folders = DocumentFolder::where('name', $name)
                ->whereNull('parent_id')
                ->whereNull('category')
                ->get();

            foreach ($folders as $folder) {
                $this->updateFolderCategory($folder, $category);
            }
        }
    });
}

private function updateFolderCategory(DocumentFolder $folder, string $category)
{
    // フォルダのカテゴリを更新
    $folder->update(['category' => $category]);
    
    // 子フォルダとファイルのカテゴリも更新
    $folder->children()->update(['category' => $category]);
    $folder->files()->update(['category' => $category]);
    
    // 再帰的に子フォルダを処理
    foreach ($folder->children as $child) {
        $this->updateFolderCategory($child, $category);
    }
}
```

**2. コマンドの実行**
```bash
php artisan migrate:document-categories
```

---

## データ整合性の問題

### 問題: 親フォルダと子フォルダのカテゴリが異なる

#### 症状
- 親フォルダ: `lifeline_electrical`
- 子フォルダ: `lifeline_gas`
- データの整合性が取れていない

#### 原因
- カテゴリ継承が正しく実装されていない
- 手動でカテゴリが変更された

#### 解決方法

**1. データ整合性チェックスクリプト**
```php
public function checkCategoryConsistency()
{
    $inconsistentFolders = DocumentFolder::whereNotNull('parent_id')
        ->get()
        ->filter(function ($folder) {
            $parent = $folder->parent;
            return $parent && $folder->category !== $parent->category;
        });

    if ($inconsistentFolders->isNotEmpty()) {
        Log::warning('Category inconsistency detected', [
            'count' => $inconsistentFolders->count(),
            'folder_ids' => $inconsistentFolders->pluck('id')->toArray(),
        ]);
    }

    return $inconsistentFolders;
}
```

**2. データ修正スクリプト**
```php
public function fixCategoryConsistency()
{
    DB::transaction(function () {
        $folders = DocumentFolder::whereNotNull('parent_id')->get();

        foreach ($folders as $folder) {
            $parent = $folder->parent;
            if ($parent && $folder->category !== $parent->category) {
                // 親のカテゴリに合わせる
                $folder->update(['category' => $parent->category]);
                
                // 子フォルダとファイルも更新
                $this->updateDescendantsCategory($folder, $parent->category);
            }
        }
    });
}

private function updateDescendantsCategory(DocumentFolder $folder, string $category)
{
    // 子フォルダを更新
    $folder->children()->update(['category' => $category]);
    
    // ファイルを更新
    $folder->files()->update(['category' => $category]);
    
    // 再帰的に処理
    foreach ($folder->children as $child) {
        $this->updateDescendantsCategory($child, $category);
    }
}
```

### 問題: ファイルのカテゴリがフォルダと異なる

#### 症状
- フォルダ: `lifeline_electrical`
- ファイル: `lifeline_gas`
- ファイルが正しいフォルダに表示されない

#### 解決方法

**1. ファイルカテゴリの修正**
```php
public function fixFileCategories()
{
    DB::transaction(function () {
        $files = DocumentFile::whereNotNull('folder_id')->get();

        foreach ($files as $file) {
            $folder = $file->folder;
            if ($folder && $file->category !== $folder->category) {
                $file->update(['category' => $folder->category]);
            }
        }
    });
}
```

**2. 定期的な整合性チェック**
```php
// スケジューラーに登録
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('documents:check-consistency')
        ->daily()
        ->at('02:00');
}
```

---

## デバッグツール

### カテゴリ情報の確認

```php
// Artisanコマンドを作成
php artisan make:command DebugDocumentCategories

public function handle()
{
    $facilityId = $this->argument('facility_id');
    
    $this->info('=== Document Folders ===');
    $folders = DocumentFolder::where('facility_id', $facilityId)->get();
    
    foreach ($folders as $folder) {
        $this->line(sprintf(
            'ID: %d | Name: %s | Category: %s | Parent: %s',
            $folder->id,
            $folder->name,
            $folder->category ?? 'NULL',
            $folder->parent_id ?? 'NULL'
        ));
    }
    
    $this->info('=== Document Files ===');
    $files = DocumentFile::where('facility_id', $facilityId)->get();
    
    foreach ($files as $file) {
        $this->line(sprintf(
            'ID: %d | Name: %s | Category: %s | Folder: %s',
            $file->id,
            $file->original_name,
            $file->category ?? 'NULL',
            $file->folder_id ?? 'NULL'
        ));
    }
}
```

### カテゴリ統計の表示

```php
public function showCategoryStats()
{
    $this->info('=== Category Statistics ===');
    
    // フォルダ統計
    $folderStats = DocumentFolder::selectRaw('category, COUNT(*) as count')
        ->groupBy('category')
        ->get();
    
    $this->info('Folders:');
    foreach ($folderStats as $stat) {
        $this->line(sprintf(
            '  %s: %d',
            $stat->category ?? 'NULL (Main)',
            $stat->count
        ));
    }
    
    // ファイル統計
    $fileStats = DocumentFile::selectRaw('category, COUNT(*) as count')
        ->groupBy('category')
        ->get();
    
    $this->info('Files:');
    foreach ($fileStats as $stat) {
        $this->line(sprintf(
            '  %s: %d',
            $stat->category ?? 'NULL (Main)',
            $stat->count
        ));
    }
}
```

---

## よくある質問（FAQ）

### Q1: 既存のドキュメントはどうなりますか？

**A:** 既存のドキュメント（category = NULL）はメインドキュメントとして扱われます。後方互換性が保証されているため、既存データは引き続き正常に動作します。

### Q2: カテゴリを変更できますか？

**A:** カテゴリの変更は推奨されません。カテゴリを変更すると、親子関係やファイルとの整合性が失われる可能性があります。必要な場合は、新しいフォルダを作成してファイルを移動してください。

### Q3: 新しいカテゴリを追加できますか？

**A:** はい、可能です。新しいカテゴリを追加する場合は、以下の手順に従ってください：

1. カテゴリ値を定義（例: `lifeline_security`）
2. サービス層にカテゴリ処理を追加
3. コントローラーにルートを追加
4. ビューにUIを追加

### Q4: パフォーマンスへの影響はありますか？

**A:** 適切にインデックスが設定されていれば、パフォーマンスへの影響は最小限です。複合インデックス `(facility_id, category)` が効率的なクエリ実行を保証します。

### Q5: テスト環境でカテゴリをリセットできますか？

**A:** はい、以下のコマンドでリセットできます：

```bash
# データベースをリセット
php artisan migrate:fresh --seed

# または特定のマイグレーションをロールバック
php artisan migrate:rollback --step=1
php artisan migrate
```

---

## サポートとお問い合わせ

問題が解決しない場合は、以下の情報を含めて開発チームにお問い合わせください：

1. エラーメッセージの全文
2. 発生した操作の詳細
3. 施設ID、フォルダID、ファイルID
4. ログファイル（`storage/logs/laravel.log`）
5. データベースの状態（該当するレコード）

---

## まとめ

このトラブルシューティングガイドでは、ドキュメント管理システムのカテゴリ分離機能に関する一般的な問題と解決方法を説明しました。問題が発生した場合は、このガイドを参照して適切な対処を行ってください。

定期的なデータ整合性チェックとログ監視により、問題を早期に発見し、対処することができます。
