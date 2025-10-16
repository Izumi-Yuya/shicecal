# 契約書ドキュメント管理 - 開発者向け実装ガイド

## 概要

このドキュメントは、契約書ドキュメント管理機能の実装詳細と、新しいカテゴリのドキュメント管理機能を追加する際のガイドラインを提供します。

## アーキテクチャ

### レイヤー構造

```
Presentation Layer (Blade Components)
    ↓
Controller Layer (ContractDocumentController)
    ↓
Service Layer (ContractDocumentService)
    ↓
Model Layer (DocumentFolder, DocumentFile)
    ↓
Database Layer (document_folders, document_files)
```

### 主要コンポーネント

1. **ContractDocumentService**: ビジネスロジック層
2. **ContractDocumentController**: HTTPリクエスト処理層
3. **DocumentFolder/DocumentFile Models**: データアクセス層
4. **contract-document-manager.blade.php**: UIコンポーネント
5. **ContractDocumentManager.js**: クライアントサイドロジック

## 実装パターン

### 1. サービス層の実装

#### ContractDocumentService

```php
<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\DocumentFolder;
use App\Models\DocumentFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractDocumentService
{
    const CATEGORY = 'contracts';
    const CATEGORY_NAME = '契約書';
    
    const DEFAULT_SUBFOLDERS = [
        'contracts' => '契約書',
        'estimates' => '見積書',
        'invoices' => '請求書',
        'others' => 'その他',
    ];

    protected DocumentService $documentService;
    protected FileHandlingService $fileHandlingService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        DocumentService $documentService,
        FileHandlingService $fileHandlingService,
        ActivityLogService $activityLogService
    ) {
        $this->documentService = $documentService;
        $this->fileHandlingService = $fileHandlingService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * カテゴリルートフォルダの取得または作成
     */
    public function getOrCreateCategoryRootFolder(Facility $facility, User $user): DocumentFolder
    {
        $rootFolder = DocumentFolder::contracts()
            ->where('facility_id', $facility->id)
            ->whereNull('parent_id')
            ->first();

        if (!$rootFolder) {
            DB::beginTransaction();
            try {
                $rootFolder = DocumentFolder::create([
                    'facility_id' => $facility->id,
                    'parent_id' => null,
                    'category' => self::CATEGORY,
                    'name' => self::CATEGORY_NAME,
                    'path' => '/' . self::CATEGORY_NAME,
                    'created_by' => $user->id,
                ]);

                $this->createDefaultSubfolders($rootFolder, $user);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        return $rootFolder;
    }

    /**
     * デフォルトサブフォルダの作成
     */
    protected function createDefaultSubfolders(DocumentFolder $parentFolder, User $user): void
    {
        foreach (self::DEFAULT_SUBFOLDERS as $key => $name) {
            DocumentFolder::create([
                'facility_id' => $parentFolder->facility_id,
                'parent_id' => $parentFolder->id,
                'category' => self::CATEGORY,
                'name' => $name,
                'path' => $parentFolder->path . '/' . $name,
                'created_by' => $user->id,
            ]);
        }
    }

    // その他のメソッド...
}
```

### 2. コントローラー層の実装

#### ContractDocumentController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Services\ContractDocumentService;
use App\Http\Traits\HandlesApiResponses;
use Illuminate\Http\Request;

class ContractDocumentController extends Controller
{
    use HandlesApiResponses;

    protected ContractDocumentService $contractDocumentService;

    public function __construct(ContractDocumentService $contractDocumentService)
    {
        $this->contractDocumentService = $contractDocumentService;
    }

    /**
     * ドキュメント一覧取得
     */
    public function index(Request $request, Facility $facility)
    {
        try {
            $this->authorize('view', [FacilityContract::class, $facility]);

            $folderId = $request->query('folder_id');
            $perPage = $request->query('per_page', 50);

            $result = $this->contractDocumentService->getCategoryDocuments($facility, [
                'folder_id' => $folderId,
                'per_page' => $perPage,
            ]);

            return $this->successResponse($result);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('権限がありません。', 403);
        } catch (\Exception $e) {
            Log::error('Failed to get contract documents', [
                'facility_id' => $facility->id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('ドキュメントの取得に失敗しました。', 500);
        }
    }

    // その他のメソッド...
}
```

### 3. モデル層の実装

#### カテゴリスコープの追加

```php
// DocumentFolder.php
public function scopeContracts($query)
{
    return $query->where('category', 'contracts');
}

// DocumentFile.php
public function scopeContracts($query)
{
    return $query->where('category', 'contracts');
}
```

### 4. フロントエンド実装

#### ContractDocumentManager.js

```javascript
class ContractDocumentManager {
    constructor(facilityId) {
        this.facilityId = facilityId;
        this.category = 'contracts';
        this.currentFolderId = null;
        this.apiClient = new ApiClient();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDocuments();
    }

    async loadDocuments(folderId = null) {
        try {
            const url = `/facilities/${this.facilityId}/contract-documents`;
            const params = folderId ? { folder_id: folderId } : {};
            
            const response = await this.apiClient.get(url, params);
            
            if (response.success) {
                this.currentFolderId = folderId;
                this.updateUI(response.data);
            }
        } catch (error) {
            console.error('Failed to load documents:', error);
            this.showError('ドキュメントの読み込みに失敗しました。');
        }
    }

    // その他のメソッド...
}
```

## 新しいカテゴリの追加方法

### ステップ1: サービスクラスの作成

```php
// app/Services/NewCategoryDocumentService.php
class NewCategoryDocumentService
{
    const CATEGORY = 'new_category';
    const CATEGORY_NAME = '新カテゴリ';
    
    const DEFAULT_SUBFOLDERS = [
        'subfolder1' => 'サブフォルダ1',
        'subfolder2' => 'サブフォルダ2',
    ];

    // ContractDocumentServiceと同じパターンで実装
}
```

### ステップ2: コントローラーの作成

```php
// app/Http/Controllers/NewCategoryDocumentController.php
class NewCategoryDocumentController extends Controller
{
    use HandlesApiResponses;

    protected NewCategoryDocumentService $service;

    // ContractDocumentControllerと同じパターンで実装
}
```

### ステップ3: ルートの追加

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::prefix('facilities/{facility}/new-category-documents')->group(function () {
        Route::get('/', [NewCategoryDocumentController::class, 'index']);
        Route::post('/upload', [NewCategoryDocumentController::class, 'uploadFile']);
        Route::post('/folders', [NewCategoryDocumentController::class, 'createFolder']);
        // その他のルート...
    });
});
```

### ステップ4: Bladeコンポーネントの作成

```blade
{{-- resources/views/components/new-category-document-manager.blade.php --}}
<div class="document-management" 
     data-facility-id="{{ $facility->id }}" 
     id="new-category-document-management-container">
    {{-- ContractDocumentManagerと同じ構造 --}}
</div>
```

### ステップ5: JavaScriptクラスの作成

```javascript
// resources/js/modules/NewCategoryDocumentManager.js
class NewCategoryDocumentManager {
    constructor(facilityId) {
        this.facilityId = facilityId;
        this.category = 'new_category';
        // ContractDocumentManagerと同じパターン
    }
}
```

### ステップ6: モデルスコープの追加

```php
// DocumentFolder.php
public function scopeNewCategory($query)
{
    return $query->where('category', 'new_category');
}

// DocumentFile.php
public function scopeNewCategory($query)
{
    return $query->where('category', 'new_category');
}
```

## テスト実装

### ユニットテスト

```php
// tests/Unit/Services/NewCategoryDocumentServiceTest.php
class NewCategoryDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NewCategoryDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NewCategoryDocumentService::class);
    }

    /** @test */
    public function it_creates_root_folder_with_default_subfolders()
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $rootFolder = $this->service->getOrCreateCategoryRootFolder($facility, $user);

        $this->assertNotNull($rootFolder);
        $this->assertEquals('new_category', $rootFolder->category);
        $this->assertCount(2, $rootFolder->children); // デフォルトサブフォルダ数
    }
}
```

### 機能テスト

```php
// tests/Feature/NewCategoryDocumentControllerTest.php
class NewCategoryDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_documents()
    {
        $user = User::factory()->create();
        $facility = Facility::factory()->create();

        $response = $this->actingAs($user)
            ->get("/facilities/{$facility->id}/new-category-documents");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'folders',
                'files',
                'breadcrumbs',
            ],
        ]);
    }
}
```

## ベストプラクティス

### 1. カテゴリ分離の徹底

```php
// 常にカテゴリスコープを使用
$folders = DocumentFolder::contracts()
    ->where('facility_id', $facility->id)
    ->get();

// カテゴリを明示的に設定
DocumentFolder::create([
    'category' => self::CATEGORY,
    // その他のフィールド...
]);
```

### 2. トランザクション処理

```php
DB::beginTransaction();
try {
    // 複数のデータベース操作
    $folder = DocumentFolder::create([...]);
    $this->createDefaultSubfolders($folder, $user);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### 3. エラーハンドリング

```php
try {
    // 処理
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    return $this->errorResponse('権限がありません。', 403);
} catch (\Illuminate\Validation\ValidationException $e) {
    return $this->errorResponse('入力内容に誤りがあります。', 422, [
        'errors' => $e->errors()
    ]);
} catch (\Exception $e) {
    Log::error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    return $this->errorResponse('システムエラーが発生しました。', 500);
}
```

### 4. ログ出力

```php
Log::info('Document uploaded', [
    'facility_id' => $facility->id,
    'file_name' => $file->getClientOriginalName(),
    'user_id' => $user->id,
]);

Log::error('Upload failed', [
    'facility_id' => $facility->id,
    'error' => $e->getMessage(),
    'user_id' => $user->id,
]);
```

### 5. アクティビティログ

```php
$this->activityLogService->log(
    'document_uploaded',
    'DocumentFile',
    $documentFile->id,
    [
        'facility_id' => $facility->id,
        'file_name' => $documentFile->original_name,
        'category' => self::CATEGORY,
    ]
);
```

## パフォーマンス最適化

### 1. Eager Loading

```php
$folders = DocumentFolder::contracts()
    ->with(['creator', 'children'])
    ->where('facility_id', $facility->id)
    ->get();
```

### 2. ページネーション

```php
$files = DocumentFile::contracts()
    ->where('folder_id', $folderId)
    ->paginate($perPage);
```

### 3. キャッシュ

```php
$stats = Cache::remember(
    "contract_documents_stats_{$facility->id}",
    300, // 5分
    fn() => $this->calculateStats($facility)
);
```

## セキュリティ考慮事項

### 1. 認可チェック

```php
// コントローラーで必ず実施
$this->authorize('view', [FacilityContract::class, $facility]);
$this->authorize('update', [FacilityContract::class, $facility]);
```

### 2. ファイルバリデーション

```php
$request->validate([
    'file' => [
        'required',
        'file',
        'max:51200', // 50MB
        'mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx',
    ],
]);
```

### 3. パストラバーサル対策

```php
// FileHandlingServiceで実装済み
$safePath = $this->fileHandlingService->sanitizePath($path);
```

## トラブルシューティング

### よくある問題と解決方法

1. **カテゴリ分離が機能しない**
   - スコープメソッドを使用しているか確認
   - categoryフィールドが正しく設定されているか確認

2. **ファイルアップロードが失敗する**
   - ストレージディレクトリの権限を確認
   - php.iniのupload_max_filesizeとpost_max_sizeを確認

3. **モーダルが表示されない**
   - Bootstrap JSが読み込まれているか確認
   - data-bs-toggle属性が正しく設定されているか確認

## 参考資料

- [Laravel Documentation](https://laravel.com/docs)
- [Bootstrap Documentation](https://getbootstrap.com/docs)
- [ドキュメント管理システム設計書](./design.md)
- [API仕様書](./contract-document-api-reference.md)
