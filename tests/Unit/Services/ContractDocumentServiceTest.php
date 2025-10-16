<?php

namespace Tests\Unit\Services;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\ContractDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractDocumentServiceTest extends TestCase
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
    public function getOrCreateCategoryRootFolder_creates_folder_with_correct_contracts_category()
    {
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // フォルダが作成されたことを確認
        $this->assertInstanceOf(DocumentFolder::class, $rootFolder);
        $this->assertEquals('contracts', $rootFolder->category);
        $this->assertTrue($rootFolder->isContracts());
        $this->assertFalse($rootFolder->isMain());
        $this->assertFalse($rootFolder->isLifeline());
        $this->assertFalse($rootFolder->isMaintenance());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $rootFolder->id,
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function getOrCreateCategoryRootFolder_returns_existing_folder_if_already_exists()
    {
        // 最初のルートフォルダ作成
        $firstFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // 2回目の呼び出し
        $secondFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // 同じフォルダが返されることを確認
        $this->assertEquals($firstFolder->id, $secondFolder->id);

        // データベースに1つだけ存在することを確認
        $this->assertEquals(
            1,
            DocumentFolder::where('facility_id', $this->facility->id)
                ->where('category', 'contracts')
                ->whereNull('parent_id')
                ->count()
        );
    }

    /** @test */
    public function getOrCreateCategoryRootFolder_creates_default_subfolders()
    {
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // デフォルトサブフォルダが作成されていることを確認
        $subfolders = DocumentFolder::where('parent_id', $rootFolder->id)->get();
        
        $this->assertCount(4, $subfolders);
        
        $subfolderNames = $subfolders->pluck('name')->toArray();
        $this->assertContains('契約書', $subfolderNames);
        $this->assertContains('見積書', $subfolderNames);
        $this->assertContains('請求書', $subfolderNames);
        $this->assertContains('その他', $subfolderNames);

        // すべてのサブフォルダが正しいカテゴリを持つことを確認
        foreach ($subfolders as $subfolder) {
            $this->assertEquals('contracts', $subfolder->category);
        }
    }

    /** @test */
    public function getCategoryDocuments_returns_only_contracts_category()
    {
        // ルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // 契約書のサブフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Subfolder',
            'parent_id' => $rootFolder->id,
            'path' => $rootFolder->path . '/Contracts Subfolder',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $rootFolder->id,
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴のフォルダとファイル作成（混入させない）
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $maintenanceFolder->id,
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備のフォルダとファイル作成（混入させない）
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $lifelineFolder->id,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書のドキュメントのみ取得
        $result = $this->contractDocumentService->getCategoryDocuments($this->facility);

        // 契約書のみが含まれることを確認
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        
        $documents = $result['data'];
        $this->assertArrayHasKey('folders', $documents);
        $this->assertArrayHasKey('files', $documents);

        $folderIds = collect($documents['folders'])->pluck('id')->toArray();
        $fileIds = collect($documents['files'])->pluck('id')->toArray();

        // 契約書が含まれる
        $this->assertContains($contractsFolder->id, $folderIds);
        $this->assertContains($contractsFile->id, $fileIds);

        // 他のカテゴリは含まれない
        $this->assertNotContains($maintenanceFolder->id, $folderIds);
        $this->assertNotContains($maintenanceFile->id, $fileIds);
        $this->assertNotContains($lifelineFolder->id, $folderIds);
        $this->assertNotContains($lifelineFile->id, $fileIds);
    }

    /** @test */
    public function uploadCategoryFile_creates_file_with_correct_contracts_category()
    {
        Storage::fake('public');

        // 契約書のルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $result = $this->contractDocumentService->uploadCategoryFile(
            $this->facility,
            $file,
            $this->user,
            $rootFolder->id
        );

        // ファイルが正しいカテゴリで作成されたことを確認
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('file', $result['data']);

        $documentFile = $result['data']['file'];
        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('contracts', $documentFile->category);
        $this->assertTrue($documentFile->isContracts());
        $this->assertFalse($documentFile->isMain());
        $this->assertFalse($documentFile->isLifeline());
        $this->assertFalse($documentFile->isMaintenance());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_files', [
            'id' => $documentFile->id,
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $rootFolder->id,
        ]);
    }

    /** @test */
    public function createCategoryFolder_creates_folder_with_correct_contracts_category()
    {
        // 契約書のルートフォルダ作成
        $rootFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // サブフォルダ作成
        $result = $this->contractDocumentService->createCategoryFolder(
            $this->facility,
            'Test Subfolder',
            $this->user,
            $rootFolder->id
        );

        // フォルダが正しいカテゴリで作成されたことを確認
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('folder', $result['data']);

        $subFolder = $result['data']['folder'];
        $this->assertInstanceOf(DocumentFolder::class, $subFolder);
        $this->assertEquals('contracts', $subFolder->category);
        $this->assertEquals($rootFolder->id, $subFolder->parent_id);
        $this->assertTrue($subFolder->isContracts());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $subFolder->id,
            'parent_id' => $rootFolder->id,
            'category' => 'contracts',
            'name' => 'Test Subfolder',
        ]);
    }

    /** @test */
    public function getCategoryStats_returns_only_contracts_category_stats()
    {
        // 契約書のフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $contractsFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴のフォルダとファイル作成（カウントされない）
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $maintenanceFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備のフォルダとファイル作成（カウントされない）
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(1)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $lifelineFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書の統計のみ取得
        $stats = $this->contractDocumentService->getCategoryStats($this->facility);

        // 契約書のみがカウントされることを確認
        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['folder_count']); // 契約書のフォルダ数のみ
        $this->assertEquals(3, $stats['file_count']); // 契約書のファイル数のみ
    }

    /** @test */
    public function searchCategoryFiles_returns_only_contracts_category_results()
    {
        // 契約書のフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contract Folder',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $contractsFolder->id,
            'original_name' => 'contract_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴のフォルダとファイル作成（検索結果に含まれない）
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Contract Maintenance',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $maintenanceFolder->id,
            'original_name' => 'contract_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書カテゴリ内で検索
        $result = $this->contractDocumentService->searchCategoryFiles($this->facility, 'contract');

        // 契約書のみが含まれることを確認
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        
        $results = $result['data'];
        $this->assertArrayHasKey('folders', $results);
        $this->assertArrayHasKey('files', $results);

        $folderIds = collect($results['folders'])->pluck('id')->toArray();
        $fileIds = collect($results['files'])->pluck('id')->toArray();

        // 契約書が含まれる
        $this->assertContains($contractsFolder->id, $folderIds);
        $this->assertContains($contractsFile->id, $fileIds);

        // 他のカテゴリは含まれない
        $this->assertNotContains($maintenanceFolder->id, $folderIds);
        $this->assertNotContains($maintenanceFile->id, $fileIds);
    }

    /** @test */
    public function multiple_facilities_maintain_separate_contracts_categories()
    {
        $facility2 = Facility::factory()->create();

        // 施設1の契約書フォルダ作成
        $facility1ContractsFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            $this->user
        );

        // 施設2の契約書フォルダ作成
        $facility2ContractsFolder = $this->contractDocumentService->getOrCreateCategoryRootFolder(
            $facility2,
            $this->user
        );

        // 施設1の契約書ドキュメント取得
        $facility1Result = $this->contractDocumentService->getCategoryDocuments($this->facility);
        $facility1Documents = $facility1Result['data'];

        // 施設2の契約書ドキュメント取得
        $facility2Result = $this->contractDocumentService->getCategoryDocuments($facility2);
        $facility2Documents = $facility2Result['data'];

        // 各施設のドキュメントが分離されていることを確認
        $facility1FolderIds = collect($facility1Documents['folders'])->pluck('id')->toArray();
        $facility2FolderIds = collect($facility2Documents['folders'])->pluck('id')->toArray();

        $this->assertContains($facility1ContractsFolder->id, $facility1FolderIds);
        $this->assertNotContains($facility2ContractsFolder->id, $facility1FolderIds);

        $this->assertContains($facility2ContractsFolder->id, $facility2FolderIds);
        $this->assertNotContains($facility1ContractsFolder->id, $facility2FolderIds);
    }
}
