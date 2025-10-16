# ドキュメント管理システム - 現在の実装分析

## 現在の実装状況

### データベース構造

#### 実際のテーブル構成
```
✅ document_folders - すべてのドキュメントシステムで共有
✅ document_files - すべてのドキュメントシステムで共有
❌ lifeline_equipment_folders - 存在しない
❌ lifeline_equipment_files - 存在しない
❌ maintenance_folders - 存在しない
❌ maintenance_files - 存在しない
```

### 現在の分離方法

#### 論理的分離（フォルダ名ベース）
現在の実装では、**同じテーブルを使用しながら、フォルダ名で論理的に分離**しています：

```php
// LifelineDocumentService.php
const CATEGORY_FOLDER_MAPPING = [
    'electrical' => '電気設備',
    'gas' => 'ガス設備',
    'water' => '水道設備',
    'elevator' => 'エレベーター設備',
    'hvac_lighting' => '空調・照明設備',
    'security_disaster' => '防犯・防災設備',
];

// ルートフォルダを取得または作成
$rootFolder = DocumentFolder::where('facility_id', $facility->id)
    ->whereNull('parent_id')
    ->where('name', $categoryName)  // ← カテゴリ名で識別
    ->first();
```

#### フォルダ階層構造
```
document_folders テーブル
├── 施設A (facility_id = 1)
│   ├── 電気設備 (parent_id = null, name = '電気設備')
│   │   ├── 点検報告書
│   │   ├── 保守記録
│   │   └── 取扱説明書
│   ├── ガス設備 (parent_id = null, name = 'ガス設備')
│   │   └── ...
│   ├── 外装 (parent_id = null, name = '外装')
│   │   └── ...
│   └── その他のドキュメント (parent_id = null)
│       └── ...
```

## 現在の実装の利点と欠点

### ✅ 利点

#### 1. シンプルな実装
- テーブル数が少ない（2テーブルのみ）
- マイグレーションが簡単
- データベース管理が容易

#### 2. 統一されたAPI
- 同じモデル（DocumentFolder, DocumentFile）を使用
- 共通のメソッドで操作可能
- コードの重複が少ない

#### 3. 柔軟性
- 新しいカテゴリの追加が容易
- フォルダ構造の変更が簡単
- カテゴリ間でのファイル移動が可能

#### 4. 統合検索
- すべてのドキュメントを横断検索可能
- 施設全体のドキュメント統計が取りやすい

### ❌ 欠点

#### 1. データの混在
- すべてのドキュメントが同じテーブルに格納される
- カテゴリ固有のフィールドを追加しにくい
- データ量が増えるとパフォーマンスに影響

#### 2. 権限管理の複雑さ
- カテゴリごとの権限管理が複雑
- フォルダ名に依存した権限チェックが必要
- ポリシーの実装が複雑になる

#### 3. ビジネスロジックの制約
- カテゴリ固有の処理を追加しにくい
- 各システムの独立した進化が困難
- カスタムフィールドの追加が制限される

#### 4. パフォーマンス
- 大量データ時のクエリパフォーマンス
- インデックスの最適化が困難
- カテゴリごとのキャッシュ戦略が立てにくい

## 推奨される改善方針

### オプション1: 現在の実装を維持（推奨：短期）

**適用ケース**:
- システムが小規模（施設数 < 100）
- ドキュメント数が少ない（< 10,000ファイル）
- 開発リソースが限られている

**改善策**:
```php
// 1. カテゴリ識別用のカラムを追加
Schema::table('document_folders', function (Blueprint $table) {
    $table->string('category')->nullable()->after('facility_id');
    $table->index(['facility_id', 'category']);
});

Schema::table('document_files', function (Blueprint $table) {
    $table->string('category')->nullable()->after('facility_id');
    $table->index(['facility_id', 'category']);
});

// 2. カテゴリ別のスコープを追加
class DocumentFolder extends Model
{
    public function scopeLifeline($query, $category)
    {
        return $query->where('category', 'lifeline_' . $category);
    }
    
    public function scopeMaintenance($query, $category)
    {
        return $query->where('category', 'maintenance_' . $category);
    }
    
    public function scopeMain($query)
    {
        return $query->whereNull('category');
    }
}

// 3. 使用例
$electricalFolders = DocumentFolder::lifeline('electrical')
    ->where('facility_id', $facilityId)
    ->get();
```

### オプション2: 完全なテーブル分離（推奨：長期）

**適用ケース**:
- システムが大規模（施設数 > 100）
- ドキュメント数が多い（> 10,000ファイル）
- カテゴリ固有の機能が必要

**実装手順**:

#### ステップ1: マイグレーション作成
```bash
php artisan make:migration create_lifeline_equipment_documents_tables
php artisan make:migration create_maintenance_documents_tables
```

#### ステップ2: テーブル定義
```php
// lifeline_equipment_folders
Schema::create('lifeline_equipment_folders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('parent_id')->nullable()->constrained('lifeline_equipment_folders')->onDelete('cascade');
    $table->string('category'); // electrical, gas, water, etc.
    $table->string('name');
    $table->string('path');
    $table->text('description')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    
    $table->index(['facility_id', 'category']);
    $table->index(['facility_id', 'parent_id']);
    $table->unique(['facility_id', 'parent_id', 'name']);
});

// lifeline_equipment_files
Schema::create('lifeline_equipment_files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('facility_id')->constrained()->onDelete('cascade');
    $table->foreignId('folder_id')->nullable()->constrained('lifeline_equipment_folders')->onDelete('cascade');
    $table->string('category'); // electrical, gas, water, etc.
    $table->string('original_name');
    $table->string('stored_name');
    $table->string('file_path');
    $table->unsignedBigInteger('file_size');
    $table->string('mime_type');
    $table->string('file_extension');
    
    // ライフライン設備固有のフィールド
    $table->date('inspection_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('inspector_name')->nullable();
    $table->string('inspection_company')->nullable();
    
    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
    
    $table->index(['facility_id', 'category']);
    $table->index(['facility_id', 'folder_id']);
    $table->index('inspection_date');
    $table->index('expiry_date');
});
```

#### ステップ3: モデル作成
```php
// app/Models/LifelineEquipmentFolder.php
class LifelineEquipmentFolder extends Model
{
    protected $fillable = [
        'facility_id', 'parent_id', 'category', 'name', 
        'path', 'description', 'created_by'
    ];
    
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
    
    public function parent()
    {
        return $this->belongsTo(LifelineEquipmentFolder::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(LifelineEquipmentFolder::class, 'parent_id');
    }
    
    public function files()
    {
        return $this->hasMany(LifelineEquipmentFile::class, 'folder_id');
    }
}

// app/Models/LifelineEquipmentFile.php
class LifelineEquipmentFile extends Model
{
    protected $fillable = [
        'facility_id', 'folder_id', 'category', 'original_name',
        'stored_name', 'file_path', 'file_size', 'mime_type',
        'file_extension', 'inspection_date', 'expiry_date',
        'inspector_name', 'inspection_company', 'uploaded_by'
    ];
    
    protected $casts = [
        'inspection_date' => 'date',
        'expiry_date' => 'date',
    ];
    
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
    
    public function folder()
    {
        return $this->belongsTo(LifelineEquipmentFolder::class, 'folder_id');
    }
}
```

#### ステップ4: データ移行
```php
// database/migrations/xxxx_migrate_lifeline_documents_data.php
public function up()
{
    DB::transaction(function () {
        // 既存のライフライン設備フォルダを移行
        $lifelineFolders = DocumentFolder::whereIn('name', [
            '電気設備', 'ガス設備', '水道設備', 'エレベーター設備', '空調・照明設備'
        ])->get();
        
        foreach ($lifelineFolders as $folder) {
            $this->migrateFolderRecursively($folder);
        }
    });
}

private function migrateFolderRecursively(DocumentFolder $oldFolder, $newParentId = null)
{
    // カテゴリを判定
    $category = $this->determineCategoryFromName($oldFolder->name);
    
    // 新しいテーブルにフォルダを作成
    $newFolder = LifelineEquipmentFolder::create([
        'facility_id' => $oldFolder->facility_id,
        'parent_id' => $newParentId,
        'category' => $category,
        'name' => $oldFolder->name,
        'path' => $oldFolder->path,
        'created_by' => $oldFolder->created_by,
        'created_at' => $oldFolder->created_at,
        'updated_at' => $oldFolder->updated_at,
    ]);
    
    // ファイルを移行
    foreach ($oldFolder->files as $file) {
        LifelineEquipmentFile::create([
            'facility_id' => $file->facility_id,
            'folder_id' => $newFolder->id,
            'category' => $category,
            'original_name' => $file->original_name,
            'stored_name' => $file->stored_name,
            'file_path' => $file->file_path,
            'file_size' => $file->file_size,
            'mime_type' => $file->mime_type,
            'file_extension' => $file->file_extension,
            'uploaded_by' => $file->uploaded_by,
            'created_at' => $file->created_at,
            'updated_at' => $file->updated_at,
        ]);
    }
    
    // 子フォルダを再帰的に移行
    foreach ($oldFolder->children as $child) {
        $this->migrateFolderRecursively($child, $newFolder->id);
    }
}
```

### オプション3: ハイブリッドアプローチ（推奨：中期）

**適用ケース**:
- 段階的な移行が必要
- 既存データを保持したい
- リスクを最小化したい

**実装方針**:
1. 新しいテーブルを作成（オプション2と同じ）
2. 既存データは維持（後方互換性）
3. 新規データは新しいテーブルに保存
4. 徐々に既存データを移行
5. 移行完了後、古いテーブルを削除

## 実装推奨タイムライン

### フェーズ1: 現状維持 + 改善（1-2週間）
- [x] 現在の実装を文書化
- [ ] categoryカラムの追加
- [ ] スコープメソッドの実装
- [ ] パフォーマンス最適化（インデックス追加）

### フェーズ2: 評価と計画（2-4週間）
- [ ] データ量の分析
- [ ] パフォーマンステスト
- [ ] 完全分離の必要性評価
- [ ] 移行計画の策定

### フェーズ3: 段階的移行（2-3ヶ月）
- [ ] 新しいテーブルの作成
- [ ] 新しいモデルの実装
- [ ] データ移行スクリプトの作成
- [ ] 並行運用期間
- [ ] 完全移行

## 結論

### 現時点での推奨
**オプション1（現在の実装を維持）+ 改善**を推奨します。

理由：
1. 既存システムが安定して動作している
2. データ量がまだ管理可能な範囲
3. 開発リソースを他の機能に集中できる
4. 必要に応じて将来的に完全分離が可能

### 改善の優先順位
1. **高**: categoryカラムの追加とインデックス最適化
2. **中**: スコープメソッドの実装
3. **低**: 完全なテーブル分離（データ量が増えた場合）

### 監視すべき指標
- ドキュメント総数（> 10,000で完全分離を検討）
- クエリパフォーマンス（> 1秒で最適化が必要）
- ストレージ使用量（> 10GBで分離を検討）
- ユーザーからのフィードバック
