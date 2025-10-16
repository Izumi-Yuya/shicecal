<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceCategorySeparationTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $documentService;
    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->documentService = app(DocumentService::class);
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
        
        Storage::fake('public');
        
        // ログインしてAuth::id()が動作するようにする
        $this->actingAs($this->user);
    }

    /** @test */
    public function createFolder_creates_main_document_folder_with_null_category()
    {
        $folder = $this->documentService->createFolder(
            $this->facility,
            null,
            'Test Main Folder',
            $this->user
        );

        // フォルダが作成されたことを確認
        $this->assertInstanceOf(DocumentFolder::class, $folder);
        $this->assertEquals('Test Main Folder', $folder->name);
        $this->assertEquals($this->facility->id, $folder->facility_id);
        $this->assertNull($folder->category); // メインドキュメントはcategory = NULL
        $this->assertTrue($folder->isMain());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $folder->id,
            'facility_id' => $this->facility->id,
            'name' => 'Test Main Folder',
            'category' => null,
        ]);
    }

    /** @test */
    public function uploadFile_creates_main_document_file_with_null_category()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('test_document.pdf', 1024, 'application/pdf');

        $documentFile = $this->documentService->uploadFile(
            $this->facility,
            null,
            $file,
            $this->user
        );

        // ファイルが作成されたことを確認
        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('test_document.pdf', $documentFile->original_name);
        $this->assertEquals($this->facility->id, $documentFile->facility_id);
        $this->assertNull($documentFile->category); // メインドキュメントはcategory = NULL
        $this->assertTrue($documentFile->isMain());

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_files', [
            'id' => $documentFile->id,
            'facility_id' => $this->facility->id,
            'original_name' => 'test_document.pdf',
            'category' => null,
        ]);
    }

    /** @test */
    public function getFolderContents_returns_only_main_documents()
    {
        // メインドキュメントフォルダ作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // メインドキュメントファイル作成
        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'folder_id' => null,
            'original_name' => 'main_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備フォルダ作成（混入させない）
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // ライフライン設備ファイル作成（混入させない）
        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => null,
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴フォルダ作成（混入させない）
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // 修繕履歴ファイル作成（混入させない）
        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => null,
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのみ取得
        $contents = $this->documentService->getFolderContents($this->facility, null);

        // メインドキュメントのみが含まれることを確認
        $this->assertIsArray($contents);
        $this->assertArrayHasKey('folders', $contents);
        $this->assertArrayHasKey('files', $contents);

        $folderIds = collect($contents['folders'])->pluck('id')->toArray();
        $fileIds = collect($contents['files'])->pluck('id')->toArray();

        // メインドキュメントが含まれる
        $this->assertContains($mainFolder->id, $folderIds);
        $this->assertContains($mainFile->id, $fileIds);

        // ライフライン設備と修繕履歴は含まれない
        $this->assertNotContains($lifelineFolder->id, $folderIds);
        $this->assertNotContains($lifelineFile->id, $fileIds);
        $this->assertNotContains($maintenanceFolder->id, $folderIds);
        $this->assertNotContains($maintenanceFile->id, $fileIds);
    }

    /** @test */
    public function main_document_operations_do_not_affect_other_categories()
    {
        // ライフライン設備フォルダを事前に作成
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // 修繕履歴フォルダを事前に作成
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // メインドキュメントフォルダを作成
        $mainFolder = $this->documentService->createFolder(
            $this->facility,
            null,
            'Main Folder',
            $this->user
        );

        // メインドキュメントフォルダが作成されたことを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $mainFolder->id,
            'category' => null,
        ]);

        // ライフライン設備フォルダが影響を受けていないことを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $lifelineFolder->id,
            'category' => 'lifeline_electrical',
        ]);

        // 修繕履歴フォルダが影響を受けていないことを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $maintenanceFolder->id,
            'category' => 'maintenance_exterior',
        ]);

        // 各カテゴリのフォルダ数を確認
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->count();
        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->count();
        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->count();

        $this->assertEquals(1, $mainFolders);
        $this->assertEquals(1, $lifelineFolders);
        $this->assertEquals(1, $maintenanceFolders);
    }

    /** @test */
    public function getAvailableFileTypes_returns_only_main_document_file_types()
    {
        // メインドキュメントファイル作成
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'file_extension' => 'pdf',
            'uploaded_by' => $this->user->id,
        ]);

        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'file_extension' => 'docx',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備ファイル作成（混入させない）
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'file_extension' => 'xlsx',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴ファイル作成（混入させない）
        DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'file_extension' => 'jpg',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのファイルタイプのみ取得
        $fileTypes = $this->documentService->getAvailableFileTypes($this->facility);

        // メインドキュメントのファイルタイプのみが含まれることを確認
        $extensions = collect($fileTypes)->pluck('extension')->toArray();
        
        $this->assertContains('pdf', $extensions);
        $this->assertContains('docx', $extensions);
        $this->assertNotContains('xlsx', $extensions); // ライフライン設備
        $this->assertNotContains('jpg', $extensions); // 修繕履歴
    }

    /** @test */
    public function getFolderStats_returns_only_main_document_stats()
    {
        // メインドキュメントフォルダとファイル作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(3)->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'folder_id' => $mainFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備フォルダとファイル作成（カウントされない）
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(2)->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'folder_id' => $lifelineFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴フォルダとファイル作成（カウントされない）
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        DocumentFile::factory()->count(1)->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'folder_id' => $maintenanceFolder->id,
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントの統計のみ取得
        $stats = $this->documentService->getFolderStats($this->facility, $mainFolder);

        // メインドキュメントのみがカウントされることを確認
        $this->assertIsArray($stats);
        $this->assertEquals(3, $stats['file_count']); // メインドキュメントのファイル数のみ
    }

    /** @test */
    public function nested_folders_maintain_category_consistency()
    {
        // メインドキュメントの親フォルダ作成
        $parentFolder = $this->documentService->createFolder(
            $this->facility,
            null,
            'Parent Folder',
            $this->user
        );

        // メインドキュメントの子フォルダ作成
        $childFolder = $this->documentService->createFolder(
            $this->facility,
            $parentFolder,
            'Child Folder',
            $this->user
        );

        // 両方ともcategory = NULLであることを確認
        $this->assertNull($parentFolder->category);
        $this->assertNull($childFolder->category);
        $this->assertTrue($parentFolder->isMain());
        $this->assertTrue($childFolder->isMain());

        // データベースで確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $parentFolder->id,
            'category' => null,
        ]);

        $this->assertDatabaseHas('document_folders', [
            'id' => $childFolder->id,
            'parent_id' => $parentFolder->id,
            'category' => null,
        ]);
    }
}
