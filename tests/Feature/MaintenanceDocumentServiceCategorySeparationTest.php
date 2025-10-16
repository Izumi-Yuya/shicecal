<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\MaintenanceDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceDocumentServiceCategorySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected MaintenanceDocumentService $maintenanceDocumentService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->maintenanceDocumentService = app(MaintenanceDocumentService::class);
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        Storage::fake('public');
    }

    /** @test */
    public function getOrCreateCategoryRootFolder_creates_folder_with_correct_maintenance_category()
    {
        $rootFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        // フォルダが作成されたことを確認
        $this->assertInstanceOf(DocumentFolder::class, $rootFolder);
        $this->assertEquals('maintenance_exterior', $rootFolder->category);
        $this->assertTrue($rootFolder->isMaintenance());
        $this->assertFalse($rootFolder->isMain());
        $this->assertFalse($rootFolder->isLifeline());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $rootFolder->id,
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'parent_id' => null,
        ]);
    }

    /** @test */
    public function different_maintenance_categories_create_separate_folders()
    {
        // 外装のルートフォルダ作成
        $exteriorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        // 内装のルートフォルダ作成
        $interiorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'interior',
            $this->user
        );

        // 夏季結露のルートフォルダ作成
        $summerCondensationFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'summer_condensation',
            $this->user
        );

        // その他のルートフォルダ作成
        $otherFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'other',
            $this->user
        );

        // それぞれ異なるカテゴリであることを確認
        $this->assertEquals('maintenance_exterior', $exteriorFolder->category);
        $this->assertEquals('maintenance_interior', $interiorFolder->category);
        $this->assertEquals('maintenance_summer_condensation', $summerCondensationFolder->category);
        $this->assertEquals('maintenance_other', $otherFolder->category);

        // すべて修繕履歴であることを確認
        $this->assertTrue($exteriorFolder->isMaintenance());
        $this->assertTrue($interiorFolder->isMaintenance());
        $this->assertTrue($summerCondensationFolder->isMaintenance());
        $this->assertTrue($otherFolder->isMaintenance());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $exteriorFolder->id,
            'category' => 'maintenance_exterior',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $interiorFolder->id,
            'category' => 'maintenance_interior',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $summerCondensationFolder->id,
            'category' => 'maintenance_summer_condensation',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $otherFolder->id,
            'category' => 'maintenance_other',
        ]);
    }

    /** @test */
    public function getCategoryDocuments_returns_only_specified_maintenance_category()
    {
        // 外装のフォルダとファイル作成
        $exteriorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $exteriorFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => null,
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 内装のフォルダとファイル作成（混入させない）
        $interiorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'name' => 'Interior Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        $interiorFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'folder_id' => null,
            'original_name' => 'interior_report.pdf',
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
            'folder_id' => null,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 外装のドキュメントのみ取得
        $documents = $this->maintenanceDocumentService->getCategoryDocuments(
            $this->facility,
            'exterior',
            null
        );

        // 外装のみが含まれることを確認
        $this->assertIsArray($documents);
        $this->assertArrayHasKey('folders', $documents);
        $this->assertArrayHasKey('files', $documents);

        $folderIds = collect($documents['folders'])->pluck('id')->toArray();
        $fileIds = collect($documents['files'])->pluck('id')->toArray();

        // 外装が含まれる
        $this->assertContains($exteriorFolder->id, $folderIds);
        $this->assertContains($exteriorFile->id, $fileIds);

        // 他のカテゴリは含まれない
        $this->assertNotContains($interiorFolder->id, $folderIds);
        $this->assertNotContains($interiorFile->id, $fileIds);
        $this->assertNotContains($mainFolder->id, $folderIds);
        $this->assertNotContains($mainFile->id, $fileIds);
        $this->assertNotContains($lifelineFolder->id, $folderIds);
        $this->assertNotContains($lifelineFile->id, $fileIds);
    }

    /** @test */
    public function uploadCategoryFile_creates_file_with_correct_maintenance_category()
    {
        Storage::fake('public');

        // 外装のルートフォルダ作成
        $rootFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        $file = UploadedFile::fake()->create('exterior_report.pdf', 1024, 'application/pdf');

        $documentFile = $this->maintenanceDocumentService->uploadCategoryFile(
            $this->facility,
            'exterior',
            $rootFolder,
            $file,
            $this->user
        );

        // ファイルが正しいカテゴリで作成されたことを確認
        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('maintenance_exterior', $documentFile->category);
        $this->assertTrue($documentFile->isMaintenance());
        $this->assertFalse($documentFile->isMain());
        $this->assertFalse($documentFile->isLifeline());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_files', [
            'id' => $documentFile->id,
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $rootFolder->id,
        ]);
    }

    /** @test */
    public function subfolder_inherits_parent_maintenance_category()
    {
        // 外装のルートフォルダ作成
        $rootFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        // サブフォルダ作成
        $subFolder = $this->maintenanceDocumentService->createCategoryFolder(
            $this->facility,
            'exterior',
            $rootFolder,
            'Subfolder',
            $this->user
        );

        // サブフォルダが親のカテゴリを継承していることを確認
        $this->assertEquals('maintenance_exterior', $subFolder->category);
        $this->assertEquals($rootFolder->id, $subFolder->parent_id);
        $this->assertTrue($subFolder->isMaintenance());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $subFolder->id,
            'parent_id' => $rootFolder->id,
            'category' => 'maintenance_exterior',
        ]);
    }

    /** @test */
    public function getCategoryStats_returns_only_specified_maintenance_category_stats()
    {
        // 外装のフォルダとファイル作成
        $exteriorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $exteriorFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // 内装のフォルダとファイル作成（カウントされない）
        $interiorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'folder_id' => $interiorFolder->id,
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

        // 外装の統計のみ取得
        $stats = $this->maintenanceDocumentService->getCategoryStats(
            $this->facility,
            'exterior'
        );

        // 外装のみがカウントされることを確認
        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['folder_count']); // 外装のフォルダ数のみ
        $this->assertEquals(3, $stats['file_count']); // 外装のファイル数のみ
    }

    /** @test */
    public function maintenance_documents_do_not_appear_in_main_documents()
    {
        // 修繕履歴のフォルダとファイル作成
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

        // メインドキュメントのクエリで取得
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        $mainFiles = DocumentFile::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 修繕履歴のドキュメントが含まれないことを確認
        $this->assertFalse($mainFolders->contains($maintenanceFolder));
        $this->assertFalse($mainFiles->contains($maintenanceFile));
    }

    /** @test */
    public function maintenance_documents_do_not_appear_in_lifeline_documents()
    {
        // 修繕履歴のフォルダとファイル作成
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

        // ライフライン設備のクエリで取得
        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        $lifelineFiles = DocumentFile::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 修繕履歴のドキュメントが含まれないことを確認
        $this->assertFalse($lifelineFolders->contains($maintenanceFolder));
        $this->assertFalse($lifelineFiles->contains($maintenanceFile));
    }

    /** @test */
    public function multiple_facilities_maintain_separate_maintenance_categories()
    {
        $facility2 = Facility::factory()->create();

        // 施設1の外装フォルダ作成
        $facility1ExteriorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        // 施設2の外装フォルダ作成
        $facility2ExteriorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $facility2,
            'exterior',
            $this->user
        );

        // 施設1の外装ドキュメント取得
        $facility1Documents = $this->maintenanceDocumentService->getCategoryDocuments(
            $this->facility,
            'exterior',
            null
        );

        // 施設2の外装ドキュメント取得
        $facility2Documents = $this->maintenanceDocumentService->getCategoryDocuments(
            $facility2,
            'exterior',
            null
        );

        // 各施設のドキュメントが分離されていることを確認
        $facility1FolderIds = collect($facility1Documents['folders'])->pluck('id')->toArray();
        $facility2FolderIds = collect($facility2Documents['folders'])->pluck('id')->toArray();

        $this->assertContains($facility1ExteriorFolder->id, $facility1FolderIds);
        $this->assertNotContains($facility2ExteriorFolder->id, $facility1FolderIds);

        $this->assertContains($facility2ExteriorFolder->id, $facility2FolderIds);
        $this->assertNotContains($facility1ExteriorFolder->id, $facility2FolderIds);
    }

    /** @test */
    public function all_maintenance_categories_are_properly_separated()
    {
        $categories = ['exterior', 'interior', 'summer_condensation', 'other'];

        $folders = [];
        foreach ($categories as $category) {
            $folders[$category] = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
                $this->facility,
                $category,
                $this->user
            );
        }

        // 各カテゴリが正しく設定されていることを確認
        foreach ($categories as $category) {
            $this->assertEquals('maintenance_' . $category, $folders[$category]->category);
            $this->assertTrue($folders[$category]->isMaintenance());
        }

        // 各カテゴリのドキュメントが分離されていることを確認
        foreach ($categories as $category) {
            $documents = $this->maintenanceDocumentService->getCategoryDocuments(
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
