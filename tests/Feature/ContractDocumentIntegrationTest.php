<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\ContractDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractDocumentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ContractDocumentService $contractDocumentService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->contractDocumentService = app(ContractDocumentService::class);
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        Storage::fake('public');
    }

    /** @test */
    public function complete_document_workflow_works_correctly()
    {
        $this->actingAs($this->user);

        // 1. ルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        $this->assertInstanceOf(DocumentFolder::class, $rootFolder);
        $this->assertEquals('contracts', $rootFolder->category);

        // 2. サブフォルダ作成
        $subFolderResult = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Important Contracts',
            $this->user,
            $rootFolder->id
        );

        $this->assertTrue($subFolderResult['success']);
        $subFolder = $subFolderResult['folder'];
        $this->assertEquals('contracts', $subFolder->category);
        $this->assertEquals($rootFolder->id, $subFolder->parent_id);

        // 3. ファイルアップロード
        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');
        
        $uploadResult = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file,
            $this->user,
            $subFolder->id
        );

        $this->assertTrue($uploadResult['success']);
        $uploadedFile = $uploadResult['file'];
        $this->assertEquals('contracts', $uploadedFile->category);
        $this->assertEquals($subFolder->id, $uploadedFile->folder_id);

        // 4. ドキュメント一覧取得
        $documents = $this->contractDocumentService->getCategoryDocuments($this->facility);

        $this->assertIsArray($documents);
        $this->assertArrayHasKey('folders', $documents);
        $this->assertArrayHasKey('files', $documents);

        // ルートフォルダとサブフォルダが含まれることを確認
        $folderIds = collect($documents['folders'])->pluck('id')->toArray();
        $this->assertContains($rootFolder->id, $folderIds);
        $this->assertContains($subFolder->id, $folderIds);

        // 5. 特定フォルダ内のドキュメント取得
        $subFolderDocuments = $this->contractDocumentService->getCategoryDocuments(
            $this->facility,
            ['folder_id' => $subFolder->id]
        );

        $fileIds = collect($subFolderDocuments['files'])->pluck('id')->toArray();
        $this->assertContains($uploadedFile->id, $fileIds);

        // 6. 検索機能
        $searchResults = $this->contractDocumentService->searchCategoryFiles(
            $this->facility,
            'contract'
        );

        $this->assertIsArray($searchResults);
        $searchFileIds = collect($searchResults['files'])->pluck('id')->toArray();
        $this->assertContains($uploadedFile->id, $searchFileIds);

        // 7. 統計情報取得
        $stats = $this->contractDocumentService->getCategoryStats($this->facility);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('folder_count', $stats);
        $this->assertArrayHasKey('file_count', $stats);
        $this->assertGreaterThan(0, $stats['folder_count']);
        $this->assertGreaterThan(0, $stats['file_count']);
    }

    /** @test */
    public function contracts_and_maintenance_categories_are_completely_separated()
    {
        $this->actingAs($this->user);

        // 契約書のフォルダとファイル作成
        $contractsFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        $contractsFile = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');
        $contractsUploadResult = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $contractsFile,
            $this->user,
            $contractsFolder->id
        );

        // 修繕履歴のフォルダとファイル作成
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Maintenance',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $maintenanceFolder->id,
            'original_name' => 'maintenance_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書のドキュメント取得
        $contractsDocuments = $this->contractDocumentService->getCategoryDocuments($this->facility);

        $contractsFolderIds = collect($contractsDocuments['folders'])->pluck('id')->toArray();
        $contractsFileIds = collect($contractsDocuments['files'])->pluck('id')->toArray();

        // 契約書のみが含まれることを確認
        $this->assertContains($contractsFolder->id, $contractsFolderIds);
        $this->assertContains($contractsUploadResult['file']->id, $contractsFileIds);

        // 修繕履歴は含まれないことを確認
        $this->assertNotContains($maintenanceFolder->id, $contractsFolderIds);
        $this->assertNotContains($maintenanceFile->id, $contractsFileIds);

        // 修繕履歴のクエリで契約書が含まれないことを確認
        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        $maintenanceFiles = DocumentFile::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        $this->assertFalse($maintenanceFolders->contains($contractsFolder));
        $this->assertFalse($maintenanceFiles->contains($contractsUploadResult['file']));
    }

    /** @test */
    public function folder_hierarchy_management_works_correctly()
    {
        $this->actingAs($this->user);

        // ルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // レベル1のサブフォルダ作成
        $level1FolderResult = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Level 1 Folder',
            $this->user,
            $rootFolder->id
        );

        $level1Folder = $level1FolderResult['folder'];

        // レベル2のサブフォルダ作成
        $level2FolderResult = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Level 2 Folder',
            $this->user,
            $level1Folder->id
        );

        $level2Folder = $level2FolderResult['folder'];

        // レベル3のサブフォルダ作成
        $level3FolderResult = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Level 3 Folder',
            $this->user,
            $level2Folder->id
        );

        $level3Folder = $level3FolderResult['folder'];

        // すべてのフォルダが正しいカテゴリを持つことを確認
        $this->assertEquals('contracts', $rootFolder->category);
        $this->assertEquals('contracts', $level1Folder->category);
        $this->assertEquals('contracts', $level2Folder->category);
        $this->assertEquals('contracts', $level3Folder->category);

        // 親子関係が正しいことを確認
        $this->assertNull($rootFolder->parent_id);
        $this->assertEquals($rootFolder->id, $level1Folder->parent_id);
        $this->assertEquals($level1Folder->id, $level2Folder->parent_id);
        $this->assertEquals($level2Folder->id, $level3Folder->parent_id);

        // 各レベルにファイルをアップロード
        $file1 = UploadedFile::fake()->create('level1_contract.pdf', 1024, 'application/pdf');
        $upload1Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file1,
            $this->user,
            $level1Folder->id
        );

        $file2 = UploadedFile::fake()->create('level2_contract.pdf', 1024, 'application/pdf');
        $upload2Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file2,
            $this->user,
            $level2Folder->id
        );

        $file3 = UploadedFile::fake()->create('level3_contract.pdf', 1024, 'application/pdf');
        $upload3Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file3,
            $this->user,
            $level3Folder->id
        );

        // すべてのファイルが正しいカテゴリとフォルダを持つことを確認
        $this->assertEquals('contracts', $upload1Result['file']->category);
        $this->assertEquals($level1Folder->id, $upload1Result['file']->folder_id);

        $this->assertEquals('contracts', $upload2Result['file']->category);
        $this->assertEquals($level2Folder->id, $upload2Result['file']->folder_id);

        $this->assertEquals('contracts', $upload3Result['file']->category);
        $this->assertEquals($level3Folder->id, $upload3Result['file']->folder_id);

        // 各レベルのドキュメント取得
        $level1Documents = $this->contractDocumentService->getCategoryDocuments(
            $this->facility,
            ['folder_id' => $level1Folder->id]
        );

        $level2Documents = $this->contractDocumentService->getCategoryDocuments(
            $this->facility,
            ['folder_id' => $level2Folder->id]
        );

        $level3Documents = $this->contractDocumentService->getCategoryDocuments(
            $this->facility,
            ['folder_id' => $level3Folder->id]
        );

        // 各レベルで正しいファイルが取得できることを確認
        $level1FileIds = collect($level1Documents['files'])->pluck('id')->toArray();
        $this->assertContains($upload1Result['file']->id, $level1FileIds);

        $level2FileIds = collect($level2Documents['files'])->pluck('id')->toArray();
        $this->assertContains($upload2Result['file']->id, $level2FileIds);

        $level3FileIds = collect($level3Documents['files'])->pluck('id')->toArray();
        $this->assertContains($upload3Result['file']->id, $level3FileIds);
    }

    /** @test */
    public function default_subfolders_are_created_correctly()
    {
        $this->actingAs($this->user);

        // ルートフォルダ作成（デフォルトサブフォルダも作成される）
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // デフォルトサブフォルダが作成されていることを確認
        $subfolders = DocumentFolder::where('parent_id', $rootFolder->id)
            ->where('category', 'contracts')
            ->get();

        $this->assertCount(4, $subfolders);

        $subfolderNames = $subfolders->pluck('name')->toArray();
        $this->assertContains('契約書', $subfolderNames);
        $this->assertContains('見積書', $subfolderNames);
        $this->assertContains('請求書', $subfolderNames);
        $this->assertContains('その他', $subfolderNames);

        // 各サブフォルダにファイルをアップロード
        foreach ($subfolders as $subfolder) {
            $file = UploadedFile::fake()->create($subfolder->name . '.pdf', 1024, 'application/pdf');
            
            $uploadResult = $this->contractDocumentService->uploadCategoryFile(
                $this->facility,
                $file,
                $this->user,
                $subfolder->id
            );

            $this->assertTrue($uploadResult['success']);
            $this->assertEquals('contracts', $uploadResult['file']->category);
            $this->assertEquals($subfolder->id, $uploadResult['file']->folder_id);
        }

        // 統計情報で正しくカウントされることを確認
        $stats = $this->contractDocumentService->getCategoryStats($this->facility);

        $this->assertGreaterThanOrEqual(5, $stats['folder_count']); // ルート + 4つのサブフォルダ
        $this->assertEquals(4, $stats['file_count']); // 各サブフォルダに1つずつ
    }

    /** @test */
    public function search_works_across_entire_contracts_category()
    {
        $this->actingAs($this->user);

        // ルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // 複数のサブフォルダとファイル作成
        $folder1Result = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Important Contracts',
            $this->user,
            $rootFolder->id
        );

        $folder2Result = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Archive',
            $this->user,
            $rootFolder->id
        );

        // ファイルアップロード
        $file1 = UploadedFile::fake()->create('important_contract.pdf', 1024, 'application/pdf');
        $upload1Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file1,
            $this->user,
            $folder1Result['folder']->id
        );

        $file2 = UploadedFile::fake()->create('contract_archive.pdf', 1024, 'application/pdf');
        $upload2Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file2,
            $this->user,
            $folder2Result['folder']->id
        );

        $file3 = UploadedFile::fake()->create('estimate.pdf', 1024, 'application/pdf');
        $upload3Result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file3,
            $this->user,
            $rootFolder->id
        );

        // "contract"で検索
        $searchResults = $this->contractDocumentService->searchCategoryFiles(
            $this->facility,
            'contract'
        );

        $searchFolderIds = collect($searchResults['folders'])->pluck('id')->toArray();
        $searchFileIds = collect($searchResults['files'])->pluck('id')->toArray();

        // "contract"を含むフォルダとファイルが見つかることを確認
        $this->assertContains($folder1Result['folder']->id, $searchFolderIds);
        $this->assertContains($upload1Result['file']->id, $searchFileIds);
        $this->assertContains($upload2Result['file']->id, $searchFileIds);

        // "contract"を含まないファイルは見つからないことを確認
        $this->assertNotContains($upload3Result['file']->id, $searchFileIds);

        // "estimate"で検索
        $estimateSearchResults = $this->contractDocumentService->searchCategoryFiles(
            $this->facility,
            'estimate'
        );

        $estimateFileIds = collect($estimateSearchResults['files'])->pluck('id')->toArray();

        // "estimate"を含むファイルのみが見つかることを確認
        $this->assertContains($upload3Result['file']->id, $estimateFileIds);
        $this->assertNotContains($upload1Result['file']->id, $estimateFileIds);
        $this->assertNotContains($upload2Result['file']->id, $estimateFileIds);
    }

    /** @test */
    public function multiple_facilities_have_independent_contract_document_systems()
    {
        $this->actingAs($this->user);

        $facility2 = Facility::factory()->create();

        // 施設1の契約書システム
        $facility1RootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        $facility1File = UploadedFile::fake()->create('facility1_contract.pdf', 1024, 'application/pdf');
        $facility1UploadResult = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $facility1File,
            $this->user,
            $facility1RootFolder->id
        );

        // 施設2の契約書システム
        $facility2RootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $facility2,
            $this->user
        );

        $facility2File = UploadedFile::fake()->create('facility2_contract.pdf', 1024, 'application/pdf');
        $facility2UploadResult = $this->contractDocumentService->uploadCategoryFile(
            $facility2,
            $facility2File,
            $this->user,
            $facility2RootFolder->id
        );

        // 施設1のドキュメント取得
        $facility1Documents = $this->contractDocumentService->getCategoryDocuments($this->facility);

        $facility1FolderIds = collect($facility1Documents['folders'])->pluck('id')->toArray();
        $facility1FileIds = collect($facility1Documents['files'])->pluck('id')->toArray();

        // 施設1のドキュメントのみが含まれることを確認
        $this->assertContains($facility1RootFolder->id, $facility1FolderIds);
        $this->assertContains($facility1UploadResult['file']->id, $facility1FileIds);
        $this->assertNotContains($facility2RootFolder->id, $facility1FolderIds);
        $this->assertNotContains($facility2UploadResult['file']->id, $facility1FileIds);

        // 施設2のドキュメント取得
        $facility2Documents = $this->contractDocumentService->getCategoryDocuments($facility2);

        $facility2FolderIds = collect($facility2Documents['folders'])->pluck('id')->toArray();
        $facility2FileIds = collect($facility2Documents['files'])->pluck('id')->toArray();

        // 施設2のドキュメントのみが含まれることを確認
        $this->assertContains($facility2RootFolder->id, $facility2FolderIds);
        $this->assertContains($facility2UploadResult['file']->id, $facility2FileIds);
        $this->assertNotContains($facility1RootFolder->id, $facility2FolderIds);
        $this->assertNotContains($facility1UploadResult['file']->id, $facility2FileIds);

        // 統計情報も独立していることを確認
        $facility1Stats = $this->contractDocumentService->getCategoryStats($this->facility);
        $facility2Stats = $this->contractDocumentService->getCategoryStats($facility2);

        $this->assertGreaterThan(0, $facility1Stats['folder_count']);
        $this->assertGreaterThan(0, $facility1Stats['file_count']);
        $this->assertGreaterThan(0, $facility2Stats['folder_count']);
        $this->assertGreaterThan(0, $facility2Stats['file_count']);
    }
}
