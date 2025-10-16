<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\FacilityContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Facility $facility;
    protected User $adminUser;
    protected User $viewerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->facility = Facility::factory()->create();
        
        // 管理者ユーザー（編集権限あり）
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'access_scope' => 'all',
        ]);
        
        // 閲覧者ユーザー（閲覧のみ）
        $this->viewerUser = User::factory()->create([
            'role' => 'viewer',
            'access_scope' => 'all',
        ]);
        
        Storage::fake('public');
    }

    /** @test */
    public function user_can_view_contract_documents()
    {
        $this->actingAs($this->adminUser);

        // 契約書フォルダとファイル作成
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Test Folder',
            'created_by' => $this->adminUser->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $folder->id,
            'original_name' => 'test_contract.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->getJson(route('facilities.contract-documents.index', $this->facility));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'folders',
                'files',
                'breadcrumbs',
                'current_folder',
            ],
        ]);

        // 契約書のフォルダとファイルが含まれることを確認
        $folderIds = collect($response->json('data.folders'))->pluck('id')->toArray();
        $fileIds = collect($response->json('data.files'))->pluck('id')->toArray();

        $this->assertContains($folder->id, $folderIds);
        $this->assertContains($file->id, $fileIds);
    }

    /** @test */
    public function user_can_upload_file_to_contracts()
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');

        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.contract-documents.upload', $this->facility), [
            'file' => $file,
            'folder_id' => null,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_files', [
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
        ]);

        // ファイルが保存されていることを確認
        $documentFile = DocumentFile::where('facility_id', $this->facility->id)
            ->where('category', 'contracts')
            ->where('original_name', 'contract.pdf')
            ->first();

        Storage::disk('public')->assertExists($documentFile->file_path);
    }

    /** @test */
    public function user_can_create_folder_in_contracts()
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('facilities.contract-documents.folders.store', $this->facility), [
            'name' => 'New Contract Folder',
            'parent_id' => null,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'New Contract Folder',
        ]);
    }

    /** @test */
    public function user_can_download_contract_file()
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');

        // テストファイル作成
        $pdfContent = 'fake pdf content';
        $filename = 'test_contract.pdf';
        $path = 'contracts/' . $filename;
        
        Storage::disk('public')->put($path, $pdfContent);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => $filename,
            'file_path' => $path,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('facilities.contract-documents.files.download', [
            $this->facility,
            $file,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function user_can_delete_contract_file()
    {
        $this->actingAs($this->adminUser);

        Storage::fake('public');

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'test_contract.pdf',
            'file_path' => 'contracts/test_contract.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        Storage::disk('public')->put($file->file_path, 'test content');

        $response = $this->deleteJson(route('facilities.contract-documents.files.destroy', [
            $this->facility,
            $file,
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースから削除されていることを確認
        $this->assertDatabaseMissing('document_files', [
            'id' => $file->id,
        ]);

        // ファイルが削除されていることを確認
        Storage::disk('public')->assertMissing($file->file_path);
    }

    /** @test */
    public function user_can_delete_contract_folder()
    {
        $this->actingAs($this->adminUser);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Test Folder',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->deleteJson(route('facilities.contract-documents.folders.destroy', [
            $this->facility,
            $folder,
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースから削除されていることを確認
        $this->assertDatabaseMissing('document_folders', [
            'id' => $folder->id,
        ]);
    }

    /** @test */
    public function user_can_rename_contract_file()
    {
        $this->actingAs($this->adminUser);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'old_name.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->patchJson(route('facilities.contract-documents.files.rename', [
            $this->facility,
            $file,
        ]), [
            'name' => 'new_name.pdf',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースで名前が更新されていることを確認
        $this->assertDatabaseHas('document_files', [
            'id' => $file->id,
            'original_name' => 'new_name.pdf',
        ]);
    }

    /** @test */
    public function user_can_rename_contract_folder()
    {
        $this->actingAs($this->adminUser);

        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Old Folder Name',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->patchJson(route('facilities.contract-documents.folders.rename', [
            $this->facility,
            $folder,
        ]), [
            'name' => 'New Folder Name',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // データベースで名前が更新されていることを確認
        $this->assertDatabaseHas('document_folders', [
            'id' => $folder->id,
            'name' => 'New Folder Name',
        ]);
    }

    /** @test */
    public function user_can_search_contract_documents()
    {
        $this->actingAs($this->adminUser);

        // 契約書フォルダとファイル作成
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contract Folder',
            'created_by' => $this->adminUser->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $folder->id,
            'original_name' => 'contract_document.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->getJson(route('facilities.contract-documents.index', $this->facility) . '?search=contract');

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // 検索結果にフォルダとファイルが含まれることを確認
        $folderIds = collect($response->json('data.folders'))->pluck('id')->toArray();
        $fileIds = collect($response->json('data.files'))->pluck('id')->toArray();

        $this->assertContains($folder->id, $folderIds);
        $this->assertContains($file->id, $fileIds);
    }

    /** @test */
    public function unauthorized_user_cannot_edit_documents()
    {
        $this->actingAs($this->viewerUser);

        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $response = $this->postJson(route('facilities.contract-documents.upload', $this->facility), [
            'file' => $file,
            'folder_id' => null,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function viewer_can_only_view_documents()
    {
        $this->actingAs($this->viewerUser);

        // 契約書フォルダとファイル作成
        $folder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Test Folder',
            'created_by' => $this->adminUser->id,
        ]);

        $file = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'folder_id' => $folder->id,
            'original_name' => 'test_contract.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        // 閲覧は可能
        $response = $this->getJson(route('facilities.contract-documents.index', $this->facility));
        $response->assertOk();

        // アップロードは不可
        $uploadFile = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');
        $uploadResponse = $this->postJson(route('facilities.contract-documents.upload', $this->facility), [
            'file' => $uploadFile,
        ]);
        $uploadResponse->assertForbidden();

        // フォルダ作成は不可
        $createFolderResponse = $this->postJson(route('facilities.contract-documents.folders.store', $this->facility), [
            'name' => 'New Folder',
        ]);
        $createFolderResponse->assertForbidden();

        // 削除は不可
        $deleteResponse = $this->deleteJson(route('facilities.contract-documents.files.destroy', [
            $this->facility,
            $file,
        ]));
        $deleteResponse->assertForbidden();
    }

    /** @test */
    public function file_upload_validates_file_type()
    {
        $this->actingAs($this->adminUser);

        // 不正なファイルタイプ
        $file = UploadedFile::fake()->create('contract.exe', 1024, 'application/x-msdownload');

        $response = $this->postJson(route('facilities.contract-documents.upload', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function file_upload_validates_file_size()
    {
        $this->actingAs($this->adminUser);

        // 大きすぎるファイル（50MBを超える）
        $file = UploadedFile::fake()->create('contract.pdf', 51201, 'application/pdf');

        $response = $this->postJson(route('facilities.contract-documents.upload', $this->facility), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    /** @test */
    public function folder_creation_validates_name()
    {
        $this->actingAs($this->adminUser);

        // 空の名前
        $response = $this->postJson(route('facilities.contract-documents.folders.store', $this->facility), [
            'name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    /** @test */
    public function cannot_access_other_facility_documents()
    {
        $this->actingAs($this->adminUser);

        $otherFacility = Facility::factory()->create();

        $file = DocumentFile::factory()->create([
            'facility_id' => $otherFacility->id,
            'category' => 'contracts',
            'original_name' => 'other_contract.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        // 他の施設のファイルにアクセスできないことを確認
        $response = $this->getJson(route('facilities.contract-documents.index', $this->facility));

        $fileIds = collect($response->json('data.files'))->pluck('id')->toArray();
        $this->assertNotContains($file->id, $fileIds);
    }

    /** @test */
    public function contracts_documents_do_not_mix_with_other_categories()
    {
        $this->actingAs($this->adminUser);

        // 契約書のファイル作成
        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        // 修繕履歴のファイル作成
        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'original_name' => 'maintenance.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        // ライフライン設備のファイル作成
        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'original_name' => 'electrical.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        // 契約書のドキュメント一覧取得
        $response = $this->getJson(route('facilities.contract-documents.index', $this->facility));

        $fileIds = collect($response->json('data.files'))->pluck('id')->toArray();

        // 契約書のみが含まれることを確認
        $this->assertContains($contractsFile->id, $fileIds);
        $this->assertNotContains($maintenanceFile->id, $fileIds);
        $this->assertNotContains($lifelineFile->id, $fileIds);
    }
}
