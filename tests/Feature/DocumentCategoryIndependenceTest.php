<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\LifelineDocumentService;
use App\Services\MaintenanceDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * カテゴリ間独立性テスト
 * 
 * 各ドキュメント管理システム（メインドキュメント、ライフライン設備、修繕履歴）が
 * 互いに干渉せず、独立して動作することを確認するテスト
 */
class DocumentCategoryIndependenceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $documentService;
    protected LifelineDocumentService $lifelineDocumentService;
    protected MaintenanceDocumentService $maintenanceDocumentService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentService = app(DocumentService::class);
        $this->lifelineDocumentService = app(LifelineDocumentService::class);
        $this->maintenanceDocumentService = app(MaintenanceDocumentService::class);
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        Storage::fake('public');
    }

    /** @test */
    public function each_system_creates_documents_in_separate_categories()
    {
        // メインドキュメントフォルダ作成
        $mainFolder = $this->documentService->createFolder(
            $this->facility,
            null,
            'Main Folder',
            $this->user
        );

        // ライフライン設備フォルダ作成
        $lifelineFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        // 修繕履歴フォルダ作成
        $maintenanceFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        // 各フォルダが異なるカテゴリであることを確認
        $this->assertNull($mainFolder->category);
        $this->assertEquals('lifeline_electrical', $lifelineFolder->category);
        $this->assertEquals('maintenance_exterior', $maintenanceFolder->category);

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $mainFolder->id,
            'category' => null,
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $lifelineFolder->id,
            'category' => 'lifeline_electrical',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $maintenanceFolder->id,
            'category' => 'maintenance_exterior',
        ]);
    }

    /** @test */
    public function main_documents_do_not_appear_in_other_systems()
    {
        // メインドキュメントフォルダとファイル作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder',
            'created_by' => $this->user->id,
        ]);

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'original_name' => 'main_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備のクエリで取得
        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        $lifelineFiles = DocumentFile::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 修繕履歴のクエリで取得
        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        $maintenanceFiles = DocumentFile::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        // メインドキュメントが他のシステムに表示されないことを確認
        $this->assertFalse($lifelineFolders->contains($mainFolder));
        $this->assertFalse($lifelineFiles->contains($mainFile));
        $this->assertFalse($maintenanceFolders->contains($mainFolder));
        $this->assertFalse($maintenanceFiles->contains($mainFile));
    }

    /** @test */
    public function lifeline_documents_do_not_appear_in_other_systems()
    {
        // ライフライン設備フォルダとファイル作成
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'created_by' => $this->user->id,
        ]);

        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
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

        // 修繕履歴のクエリで取得
        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        $maintenanceFiles = DocumentFile::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        // ライフライン設備が他のシステムに表示されないことを確認
        $this->assertFalse($mainFolders->contains($lifelineFolder));
        $this->assertFalse($mainFiles->contains($lifelineFile));
        $this->assertFalse($maintenanceFolders->contains($lifelineFolder));
        $this->assertFalse($maintenanceFiles->contains($lifelineFile));
    }

    /** @test */
    public function maintenance_documents_do_not_appear_in_other_systems()
    {
        // 修繕履歴フォルダとファイル作成
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
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

        // ライフライン設備のクエリで取得
        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        $lifelineFiles = DocumentFile::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 修繕履歴が他のシステムに表示されないことを確認
        $this->assertFalse($mainFolders->contains($maintenanceFolder));
        $this->assertFalse($mainFiles->contains($maintenanceFile));
        $this->assertFalse($lifelineFolders->contains($maintenanceFolder));
        $this->assertFalse($lifelineFiles->contains($maintenanceFile));
    }

    /** @test */
    public function all_three_systems_can_coexist_without_interference()
    {
        // 各システムでフォルダとファイルを作成
        
        // メインドキュメント
        $mainFolder = $this->documentService->createFolder(
            $this->facility,
            null,
            'Main Folder',
            $this->user
        );

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'folder_id' => $mainFolder->id,
            'original_name' => 'main_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備（複数カテゴリ）
        $electricalFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'electrical',
            $this->user
        );

        $electricalFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $electricalFolder->id,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $gasFolder = $this->lifelineDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'gas',
            $this->user
        );

        $gasFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'folder_id' => $gasFolder->id,
            'original_name' => 'gas_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴（複数カテゴリ）
        $exteriorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'exterior',
            $this->user
        );

        $exteriorFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $exteriorFolder->id,
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        $interiorFolder = $this->maintenanceDocumentService->getOrCreateCategoryRootFolder(
            $this->facility,
            'interior',
            $this->user
        );

        $interiorFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'folder_id' => $interiorFolder->id,
            'original_name' => 'interior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 各システムで取得して、他のシステムのドキュメントが混入していないことを確認

        // メインドキュメント取得
        $mainContents = $this->documentService->getFolderContents($this->facility, null);
        $mainFolderIds = collect($mainContents['folders'])->pluck('id')->toArray();
        $mainFileIds = collect($mainContents['files'])->pluck('id')->toArray();

        $this->assertContains($mainFolder->id, $mainFolderIds);
        $this->assertContains($mainFile->id, $mainFileIds);
        $this->assertNotContains($electricalFolder->id, $mainFolderIds);
        $this->assertNotContains($gasFolder->id, $mainFolderIds);
        $this->assertNotContains($exteriorFolder->id, $mainFolderIds);
        $this->assertNotContains($interiorFolder->id, $mainFolderIds);

        // ライフライン設備（電気）取得
        $electricalContents = $this->lifelineDocumentService->getCategoryDocuments(
            $this->facility,
            'electrical',
            null
        );
        $electricalFolderIds = collect($electricalContents['folders'])->pluck('id')->toArray();
        $electricalFileIds = collect($electricalContents['files'])->pluck('id')->toArray();

        $this->assertContains($electricalFolder->id, $electricalFolderIds);
        $this->assertContains($electricalFile->id, $electricalFileIds);
        $this->assertNotContains($mainFolder->id, $electricalFolderIds);
        $this->assertNotContains($gasFolder->id, $electricalFolderIds);
        $this->assertNotContains($exteriorFolder->id, $electricalFolderIds);

        // ライフライン設備（ガス）取得
        $gasContents = $this->lifelineDocumentService->getCategoryDocuments(
            $this->facility,
            'gas',
            null
        );
        $gasFolderIds = collect($gasContents['folders'])->pluck('id')->toArray();
        $gasFileIds = collect($gasContents['files'])->pluck('id')->toArray();

        $this->assertContains($gasFolder->id, $gasFolderIds);
        $this->assertContains($gasFile->id, $gasFileIds);
        $this->assertNotContains($mainFolder->id, $gasFolderIds);
        $this->assertNotContains($electricalFolder->id, $gasFolderIds);
        $this->assertNotContains($exteriorFolder->id, $gasFolderIds);

        // 修繕履歴（外装）取得
        $exteriorContents = $this->maintenanceDocumentService->getCategoryDocuments(
            $this->facility,
            'exterior',
            null
        );
        $exteriorFolderIds = collect($exteriorContents['folders'])->pluck('id')->toArray();
        $exteriorFileIds = collect($exteriorContents['files'])->pluck('id')->toArray();

        $this->assertContains($exteriorFolder->id, $exteriorFolderIds);
        $this->assertContains($exteriorFile->id, $exteriorFileIds);
        $this->assertNotContains($mainFolder->id, $exteriorFolderIds);
        $this->assertNotContains($electricalFolder->id, $exteriorFolderIds);
        $this->assertNotContains($interiorFolder->id, $exteriorFolderIds);

        // 修繕履歴（内装）取得
        $interiorContents = $this->maintenanceDocumentService->getCategoryDocuments(
            $this->facility,
            'interior',
            null
        );
        $interiorFolderIds = collect($interiorContents['folders'])->pluck('id')->toArray();
        $interiorFileIds = collect($interiorContents['files'])->pluck('id')->toArray();

        $this->assertContains($interiorFolder->id, $interiorFolderIds);
        $this->assertContains($interiorFile->id, $interiorFileIds);
        $this->assertNotContains($mainFolder->id, $interiorFolderIds);
        $this->assertNotContains($electricalFolder->id, $interiorFolderIds);
        $this->assertNotContains($exteriorFolder->id, $interiorFolderIds);
    }

    /** @test */
    public function category_counts_are_independent()
    {
        // 各システムで異なる数のフォルダとファイルを作成

        // メインドキュメント: 2フォルダ、3ファイル
        DocumentFolder::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備（電気）: 3フォルダ、4ファイル
        DocumentFolder::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(4)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備（ガス）: 1フォルダ、2ファイル
        DocumentFolder::factory()->count(1)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴（外装）: 4フォルダ、5ファイル
        DocumentFolder::factory()->count(4)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(5)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'uploaded_by' => $this->user->id,
        ]);

        // 各カテゴリのカウントが独立していることを確認
        $mainFolderCount = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->count();
        $mainFileCount = DocumentFile::main()
            ->where('facility_id', $this->facility->id)
            ->count();

        $electricalFolderCount = DocumentFolder::lifeline('electrical')
            ->where('facility_id', $this->facility->id)
            ->count();
        $electricalFileCount = DocumentFile::lifeline('electrical')
            ->where('facility_id', $this->facility->id)
            ->count();

        $gasFolderCount = DocumentFolder::lifeline('gas')
            ->where('facility_id', $this->facility->id)
            ->count();
        $gasFileCount = DocumentFile::lifeline('gas')
            ->where('facility_id', $this->facility->id)
            ->count();

        $exteriorFolderCount = DocumentFolder::maintenance('exterior')
            ->where('facility_id', $this->facility->id)
            ->count();
        $exteriorFileCount = DocumentFile::maintenance('exterior')
            ->where('facility_id', $this->facility->id)
            ->count();

        // 各カテゴリのカウントが正しいことを確認
        $this->assertEquals(2, $mainFolderCount);
        $this->assertEquals(3, $mainFileCount);
        $this->assertEquals(3, $electricalFolderCount);
        $this->assertEquals(4, $electricalFileCount);
        $this->assertEquals(1, $gasFolderCount);
        $this->assertEquals(2, $gasFileCount);
        $this->assertEquals(4, $exteriorFolderCount);
        $this->assertEquals(5, $exteriorFileCount);
    }

    /** @test */
    public function deleting_documents_in_one_category_does_not_affect_others()
    {
        // 各システムでフォルダを作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        // メインドキュメントフォルダを削除
        $mainFolder->delete();

        // 他のカテゴリのフォルダが影響を受けていないことを確認
        $this->assertDatabaseMissing('document_folders', [
            'id' => $mainFolder->id,
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $lifelineFolder->id,
            'category' => 'lifeline_electrical',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $maintenanceFolder->id,
            'category' => 'maintenance_exterior',
        ]);
    }

    /** @test */
    public function updating_documents_in_one_category_does_not_affect_others()
    {
        // 各システムでフォルダを作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Original Main Name',
            'created_by' => $this->user->id,
        ]);

        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Original Lifeline Name',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Original Maintenance Name',
            'created_by' => $this->user->id,
        ]);

        // メインドキュメントフォルダの名前を更新
        $mainFolder->update(['name' => 'Updated Main Name']);

        // 他のカテゴリのフォルダが影響を受けていないことを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $mainFolder->id,
            'name' => 'Updated Main Name',
            'category' => null,
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $lifelineFolder->id,
            'name' => 'Original Lifeline Name',
            'category' => 'lifeline_electrical',
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $maintenanceFolder->id,
            'name' => 'Original Maintenance Name',
            'category' => 'maintenance_exterior',
        ]);
    }
}
