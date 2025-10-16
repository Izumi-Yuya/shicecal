# LifelineDocumentService カテゴリ分離実装サマリー

## 実装日
2025年10月16日

## 概要
ライフライン設備ドキュメント管理サービス（LifelineDocumentService）にカテゴリ分離機能を実装しました。これにより、ライフライン設備のドキュメントがメインドキュメントや修繕履歴のドキュメントと混在しないようになりました。

## 実装内容

### 1. getOrCreateCategoryRootFolder メソッドの更新
**変更内容:**
- ルートフォルダ作成時に `category => 'lifeline_{$category}'` を設定
- 既存フォルダ検索時に `lifeline($category)` スコープを使用
- カテゴリ値をログに記録

**実装例:**
```php
$categoryValue = 'lifeline_' . $category;
$rootFolder = DocumentFolder::lifeline($category)
    ->where('facility_id', $facility->id)
    ->whereNull('parent_id')
    ->where('name', $categoryName)
    ->first();

if (!$rootFolder) {
    $rootFolder = DocumentFolder::create([
        'facility_id' => $facility->id,
        'parent_id' => null,
        'category' => $categoryValue,
        'name' => $categoryName,
        'path' => $categoryName,
        'created_by' => $user->id,
    ]);
}
```

### 2. createDefaultSubfolders メソッドの更新
**変更内容:**
- サブフォルダ作成時に親の `category` を継承
- DocumentService を使用せず、直接 DocumentFolder::create() を使用

**実装例:**
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

### 3. uploadCategoryFile メソッドの更新
**変更内容:**
- ファイルアップロード時にフォルダの `category` を継承
- DocumentService を使用せず、直接 DocumentFile::create() を使用
- FileHandlingService を使用してファイル保存処理を実行

**実装例:**
```php
$documentFile = DocumentFile::create([
    'facility_id' => $facility->id,
    'folder_id' => $targetFolder->id,
    'category' => $targetFolder->category,  // フォルダのカテゴリを継承
    'original_name' => $file->getClientOriginalName(),
    'stored_name' => $this->fileHandlingService->generateUniqueFileName($file),
    'file_path' => $this->fileHandlingService->storeFile($file, 'documents'),
    'file_size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
    'file_extension' => $file->getClientOriginalExtension(),
    'uploaded_by' => $user->id,
]);
```

### 4. getCategoryDocuments メソッドの更新
**変更内容:**
- フォルダとファイルのクエリに `lifeline($category)` スコープを適用
- DocumentService を使用せず、直接クエリを実行
- カテゴリ値をログに記録

**実装例:**
```php
// フォルダ取得（カテゴリでフィルタリング）
$folders = DocumentFolder::lifeline($category)
    ->where('facility_id', $facility->id)
    ->where('parent_id', $currentFolder->id)
    ->with(['creator:id,name'])
    ->orderBy('name', 'asc')
    ->get();

// ファイル取得（カテゴリでフィルタリング）
$filesPaginated = DocumentFile::lifeline($category)
    ->where('facility_id', $facility->id)
    ->where('folder_id', $currentFolder->id)
    ->with(['uploader:id,name'])
    ->orderBy('original_name', 'asc')
    ->paginate($perPage);
```

### 5. getCategoryStats メソッドの更新
**変更内容:**
- 統計クエリに `lifeline($category)` スコープを適用
- フォルダ数とファイル数の集計にカテゴリフィルタを適用

**実装例:**
```php
// ファイル統計（カテゴリでフィルタリング）
$fileStats = DocumentFile::lifeline($category)
    ->where('facility_id', $facility->id)
    ->whereIn('folder_id', $folderIds)
    ->selectRaw('COUNT(*) as count, SUM(file_size) as total_size')
    ->first();

// フォルダ統計（カテゴリでフィルタリング）
$folderCount = DocumentFolder::lifeline($category)
    ->where('facility_id', $facility->id)
    ->whereIn('id', $folderIds)
    ->where('id', '!=', $rootFolder->id)
    ->count();
```

### 6. createCategoryFolder メソッドの更新
**変更内容:**
- フォルダ作成時に親の `category` を継承
- DocumentService を使用せず、直接 DocumentFolder::create() を使用

**実装例:**
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

### 7. searchCategoryFiles メソッドの更新
**変更内容:**
- ファイルとフォルダの検索クエリに `lifeline($category)` スコープを適用

**実装例:**
```php
// ファイル検索（カテゴリでフィルタリング）
$filesQuery = DocumentFile::lifeline($category)
    ->where('facility_id', $facility->id)
    ->whereIn('folder_id', $folderIds)
    ->where('original_name', 'like', "%{$query}%")
    ->with(['uploader:id,name', 'folder:id,name']);

// フォルダ検索（カテゴリでフィルタリング）
$foldersQuery = DocumentFolder::lifeline($category)
    ->where('facility_id', $facility->id)
    ->whereIn('id', $folderIds)
    ->where('name', 'like', "%{$query}%")
    ->with(['creator:id,name']);
```

## カテゴリ値の定義

### ライフライン設備カテゴリ
- `lifeline_electrical` - 電気設備
- `lifeline_gas` - ガス設備
- `lifeline_water` - 水道設備
- `lifeline_elevator` - エレベーター設備
- `lifeline_hvac_lighting` - 空調・照明設備
- `lifeline_security_disaster` - 防犯・防災設備

## データフロー

### フォルダ作成フロー
```
1. ユーザーがライフライン設備（例：電気設備）でフォルダ作成
   ↓
2. LifelineDocumentController::createFolder($category = 'electrical')
   ↓
3. LifelineDocumentService::createCategoryFolder()
   - ルートフォルダ取得/作成（category = 'lifeline_electrical'）
   - 親フォルダのカテゴリを継承してサブフォルダ作成
   ↓
4. DocumentFolder::create([
     'category' => 'lifeline_electrical',
     ...
   ])
   ↓
5. 電気設備のドキュメントセクションにのみ表示
```

### ファイルアップロードフロー
```
1. ユーザーがライフライン設備（例：電気設備）でファイルアップロード
   ↓
2. LifelineDocumentController::uploadFile($category = 'electrical')
   ↓
3. LifelineDocumentService::uploadCategoryFile()
   - ルートフォルダ取得/作成（category = 'lifeline_electrical'）
   - アップロード先フォルダのカテゴリを継承
   ↓
4. DocumentFile::create([
     'category' => 'lifeline_electrical',
     ...
   ])
   ↓
5. 電気設備のドキュメントセクションにのみ表示
```

### ドキュメント取得フロー
```
ライフライン設備（電気設備）:
LifelineDocumentService::getCategoryDocuments('electrical')
  ↓
DocumentFolder::lifeline('electrical')->where('facility_id', $id)->get()
  ↓
SELECT * FROM document_folders 
WHERE facility_id = ? AND category = 'lifeline_electrical'

DocumentFile::lifeline('electrical')->where('facility_id', $id)->get()
  ↓
SELECT * FROM document_files 
WHERE facility_id = ? AND category = 'lifeline_electrical'
```

## 期待される動作

### カテゴリ分離の確認
1. **ライフライン設備でフォルダ作成**
   - フォルダの `category` カラムに `lifeline_{category}` が設定される
   - メインドキュメントタブには表示されない
   - 修繕履歴タブには表示されない

2. **ライフライン設備でファイルアップロード**
   - ファイルの `category` カラムに `lifeline_{category}` が設定される
   - メインドキュメントタブには表示されない
   - 修繕履歴タブには表示されない

3. **ドキュメント取得**
   - 各カテゴリのドキュメントのみが取得される
   - 他のカテゴリのドキュメントは混在しない

4. **統計情報**
   - 各カテゴリの統計情報が正確に計算される
   - 他のカテゴリのデータは含まれない

## テスト項目

### 単体テスト
- [ ] ルートフォルダ作成時にカテゴリが正しく設定される
- [ ] サブフォルダ作成時に親のカテゴリが継承される
- [ ] ファイルアップロード時にフォルダのカテゴリが継承される
- [ ] ドキュメント取得時にカテゴリでフィルタリングされる
- [ ] 統計情報取得時にカテゴリでフィルタリングされる

### 統合テスト
- [ ] ライフライン設備でフォルダを作成してもメインドキュメントに表示されない
- [ ] ライフライン設備でファイルをアップロードしてもメインドキュメントに表示されない
- [ ] 異なるライフライン設備カテゴリのドキュメントが混在しない
- [ ] 検索機能がカテゴリ内のみで動作する

## 既知の問題と制約

### 既存データの扱い
- 既存のライフライン設備ドキュメント（category = NULL）は、メインドキュメントとして扱われる
- 必要に応じて、既存データのカテゴリを手動で更新する必要がある

### パフォーマンス
- カテゴリフィルタリングにより、クエリに WHERE 条件が追加される
- 複合インデックス `(facility_id, category)` により、パフォーマンスは維持される

## 次のステップ

### 実装予定
1. MaintenanceDocumentService の更新（タスク6）
2. 機能テストの作成（タスク7）
3. ドキュメントの作成（タスク8）
4. 動作確認とデプロイ（タスク9）

### 推奨事項
1. 既存データのカテゴリ移行スクリプトの作成
2. カテゴリ分離の動作確認テストの実施
3. パフォーマンステストの実施

## 参考資料
- [要件定義](.kiro/specs/document-category-separation/requirements.md)
- [設計書](.kiro/specs/document-category-separation/design.md)
- [タスクリスト](.kiro/specs/document-category-separation/tasks.md)
- [DocumentFolder モデル](app/Models/DocumentFolder.php)
- [DocumentFile モデル](app/Models/DocumentFile.php)
