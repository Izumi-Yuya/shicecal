<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\LifelineDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LifelineDocumentServiceCategorySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected LifelineDocumentService $lifelineDocumentService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->lifelineDocumentService = app(LifelineDocumentService::class);
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        Storage::fake('public');
    }

    /** @test */
    public function getOrCreateCategoryRootFolder_creates_folder_with_correct_lifeline_category()
    {
        $rootFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        // フォルダが作成されたことを確認
        $this->assertInstanceOf(DocumentFolder::class, $rootFolder);
        $this->assertEquals('lifeline_electrical', $rootFolder->category);
        $this->assertTrue($rootFolder->isLifeline());
        $this->assertFalse($rootFolder->isMain());
        $this->assertFalse($rootFolder->isMaintenance());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $rootFolder->id,
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function different_lifeline_categories_create_separate_folders()
    {
        // 電気設備のルートフォルダ作成
        $electricalFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        // ガス設備のルートフォルダ作成
        $gasFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'gas',
            $this->user
        );

        // 水道設備のルートフォルダ作成
        $waterFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'water',
            $this->user
        );

        // それぞれ異なるカテゴリであることを確認
        $this->assertEquals('lifeline_electrical', $electricalFolder->category);
        $this->assertEquals('lifeline_gas', $gasFolder->category);
        $this->assertEquals('lifeline_water', $waterFolder->category);

        // すべてライフライン設備であることを確認
        $this->assertTrue($electricalFolder->isLifeline());
        $this->assertTrue($gasFolder->isLifeline());
        $this->assertTrue($waterFolder->isLifeline());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $electricalFolder->id,
            'category' => 'lifeline_electrical',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $gasFolder->id,
            'category' => 'lifeline_gas',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $waterFolder->id,
            'category' => 'lifeline_water',
        ]);
    }

    /** @test */
    public function getCategoryDocuments_returns_only_specified_lifeline_category()
    {
        // 電気設備のフォルダとファイル作成
        $electricalFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $electricalFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => null,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ガス設備のフォルダとファイル作成（混入させない）
        $gasFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'name' => 'Gas Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $gasFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'folder_id' => null,
            'original_name' => 'gas_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのフォルダとファイル作成（混入させない）
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'folder_id' => null,
            'original_name' => 'main_document.pdf',
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
            'folder_id' => null,
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 電気設備のドキュメントのみ取得
        $documents = $this->lifelineDocumentService->getCategoryDocuments(
            $this->facility,
            'electrical',
            null
        );

        // 電気設備のみが含まれることを確認
        $this->assertIsArray($documents);
        $this->assertArrayHasKey('folders', $documents);
        $this->assertArrayHasKey('files', $documents);

        $folderIds = collect($documents['folders'])->pluck('id')->toArray();
        $fileIds = collect($documents['files'])->pluck('id')->toArray();

        // 電気設備が含まれる
        $this->assertContains($electricalFolder->id, $folderIds);
        $this->assertContains($electricalFile->id, $fileIds);

        // 他のカテゴリは含まれない
        $this->assertNotContains($gasFolder->id, $folderIds);
        $this->assertNotContains($gasFile->id, $fileIds);
        $this->assertNotContains($mainFolder->id, $folderIds);
        $this->assertNotContains($mainFile->id, $fileIds);
        $this->assertNotContains($maintenanceFolder->id, $folderIds);
        $this->assertNotContains($maintenanceFile->id, $fileIds);
    }

    /** @test */
    public function uploadCategoryFile_creates_file_with_correct_lifeline_category()
    {
        Storage::fake('public');

        // 電気設備のルートフォルダ作成
        $rootFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        $file = UploadedFile::fake()->create('electrical_report.pdf', 1024, 'application/pdf');

        $documentFile = $this->lifelineDocumentService->uploadCategoryFile(
            $this->facility,
            'electrical',
            $rootFolder,
            $file,
            $this->user
        );

        // ファイルが正しいカテゴリで作成されたことを確認
        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('lifeline_electrical', $documentFile->category);
        $this->assertTrue($documentFile->isLifeline());
        $this->assertFalse($documentFile->isMain());
        $this->assertFalse($documentFile->isMaintenance());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_files', [
            'id' => $documentFile->id,
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $rootFolder->id,
        ]);
    }

    /** @test */
    public function subfolder_inherits_parent_lifeline_category()
    {
        // 電気設備のルートフォルダ作成
        $rootFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        // サブフォルダ作成
        $subFolder = $this->lifelineDocumentService->createCategoryFolder(
            $this->facility,
            'electrical',
            $rootFolder,
            'Subfolder',
            $this->user
        );

        // サブフォルダが親のカテゴリを継承していることを確認
        $this->assertEquals('lifeline_electrical', $subFolder->category);
        $this->assertEquals($rootFolder->id, $subFolder->parent_id);
        $this->assertTrue($subFolder->isLifeline());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $subFolder->id,
            'parent_id' => $rootFolder->id,
            'category' => 'lifeline_electrical',
        ]);
    }

    /** @test */
    public function getCategoryStats_returns_only_specified_lifeline_category_stats()
    {
        // 電気設備のフォルダとファイル作成
        $electricalFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $electricalFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // ガス設備のフォルダとファイル作成（カウントされない）
        $gasFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'folder_id' => $gasFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのフォルダとファイル作成（カウントされない）
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(1)->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'folder_id' => $mainFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // 電気設備の統計のみ取得
        $stats = $this->lifelineDocumentService->getCategoryStats(
            $this->facility,
            'electrical'
        );

        // 電気設備のみがカウントされることを確認
        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['folder_count']); // 電気設備のフォルダ数のみ
        $this->assertEquals(3, $stats['file_count']); // 電気設備のファイル数のみ
    }

    /** @test */
    public function lifeline_documents_do_not_appear_in_main_documents()
    {
        // ライフライン設備のフォルダとファイル作成
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
            'folder_id' => null,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのクエリで取得
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        $mainFiles = DocumentFile::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        // ライフライン設備のドキュメントが含まれないことを確認
        $this->assertFalse($mainFolders->contains($lifelineFolder));
        $this->assertFalse($mainFiles->contains($lifelineFile));
    }

    /** @test */
    public function multiple_facilities_maintain_separate_lifeline_categories()
    {
        $facility2 = Facility::factory()->create();

        // 施設1の電気設備フォルダ作成
        $facility1ElectricalFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        // 施設2の電気設備フォルダ作成
        $facility2ElectricalFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $facility2,
            'electrical',
            $this->user
        );

        // 施設1の電気設備ドキュメント取得
        $facility1Documents = $this->lifelineDocumentService->getCategoryDocuments(
            $this->facility,
            'electrical',
            null
        );

        // 施設2の電気設備ドキュメント取得
        $facility2Documents = $this->lifelineDocumentService->getCategoryDocuments(
            $facility2,
            'electrical',
            null
        );

        // 各施設のドキュメントが分離されていることを確認
        $facility1FolderIds = collect($facility1Documents['folders'])->pluck('id')->toArray();
        $facility2FolderIds = collect($facility2Documents['folders'])->pluck('id')->toArray();

        $this->assertContains($facility1ElectricalFolder->id, $facility1FolderIds);
        $this->assertNotContains($facility2ElectricalFolder->id, $facility1FolderIds);

        $this->assertContains($facility2ElectricalFolder->id, $facility2FolderIds);
        $this->assertNotContains($facility1ElectricalFolder->id, $facility2FolderIds);
    }

    /** @test */
    public function all_lifeline_categories_are_properly_separated()
    {
        $categories = ['electrical', 'gas', 'water', 'elevator', 'hvac_lighting'];

        $folders = [];
        foreach ($categories as $category) {
            $folders[$category] = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
                $this->facility,
                $category,
                $this->user
            );
        }

        // 各カテゴリが正しく設定されていることを確認
        foreach ($categories as $category) {
            $this->assertEquals('lifeline_' . $category, $folders[$category]->category);
            $this->assertTrue($folders[$category]->isLifeline());
        }

        // 各カテゴリのドキュメントが分離されていることを確認
        foreach ($categories as $category) {
            $documents = $this->lifelineDocumentService->getCategoryDocuments(
                $this->facility,
                $category,
                null
            );

            $folderIds = collect($documents['folders'])->pluck('id')->toArray();

            // 自分のカテゴリのフォルダのみが含まれる
            $this->assertContains($folders[$category]->id, $folderIds);

            // 他のカテゴリのフォルダは含まれない
            foreach ($categories as $otherCategory) {
                if ($otherCategory !== $category) {
                    $this->assertNotContains($folders[$otherCategory]->id, $folderIds);
                }
            }
        }
    }
}
