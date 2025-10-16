<?php

namespace Tests\Unit\Models;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentContractsCategoryScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Facility $facility;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->facility = Facility::factory()->create();
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function contracts_scope_returns_only_contracts_folders()
    {
        // 契約書フォルダ作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Folder',
            'created_by' => $this->user->id,
        ]);

        // メインドキュメントフォルダ作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder',
            'created_by' => $this->user->id,
        ]);

        // ライフライン設備フォルダ作成
        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'created_by' => $this->user->id,
        ]);

        // 修繕履歴フォルダ作成
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'created_by' => $this->user->id,
        ]);

        // 契約書のみ取得
        $folders = DocumentFolder::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のみが含まれることを確認
        $this->assertCount(1, $folders);
        $this->assertTrue($folders->contains($contractsFolder));
        $this->assertFalse($folders->contains($mainFolder));
        $this->assertFalse($folders->contains($lifelineFolder));
        $this->assertFalse($folders->contains($maintenanceFolder));
    }

    /** @test */
    public function contracts_scope_returns_only_contracts_files()
    {
        // 契約書ファイル作成
        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントファイル作成
        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'original_name' => 'main_document.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備ファイル作成
        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'original_name' => 'electrical_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴ファイル作成
        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'original_name' => 'exterior_report.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書のみ取得
        $files = DocumentFile::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のみが含まれることを確認
        $this->assertCount(1, $files);
        $this->assertTrue($files->contains($contractsFile));
        $this->assertFalse($files->contains($mainFile));
        $this->assertFalse($files->contains($lifelineFile));
        $this->assertFalse($files->contains($maintenanceFile));
    }

    /** @test */
    public function isContracts_method_correctly_identifies_contracts_folders()
    {
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

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

        $this->assertTrue($contractsFolder->isContracts());
        $this->assertFalse($mainFolder->isContracts());
        $this->assertFalse($lifelineFolder->isContracts());
        $this->assertFalse($maintenanceFolder->isContracts());
    }

    /** @test */
    public function isContracts_method_correctly_identifies_contracts_files()
    {
        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'uploaded_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertTrue($contractsFile->isContracts());
        $this->assertFalse($mainFile->isContracts());
        $this->assertFalse($lifelineFile->isContracts());
        $this->assertFalse($maintenanceFile->isContracts());
    }

    /** @test */
    public function contracts_documents_do_not_appear_in_main_documents()
    {
        // 契約書のフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Folder',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // メインドキュメントのクエリで取得
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        $mainFiles = DocumentFile::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のドキュメントが含まれないことを確認
        $this->assertFalse($mainFolders->contains($contractsFolder));
        $this->assertFalse($mainFiles->contains($contractsFile));
    }

    /** @test */
    public function contracts_documents_do_not_appear_in_lifeline_documents()
    {
        // 契約書のフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Folder',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // ライフライン設備のクエリで取得
        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        $lifelineFiles = DocumentFile::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のドキュメントが含まれないことを確認
        $this->assertFalse($lifelineFolders->contains($contractsFolder));
        $this->assertFalse($lifelineFiles->contains($contractsFile));
    }

    /** @test */
    public function contracts_documents_do_not_appear_in_maintenance_documents()
    {
        // 契約書のフォルダとファイル作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Folder',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // 修繕履歴のクエリで取得
        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        $maintenanceFiles = DocumentFile::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のドキュメントが含まれないことを確認
        $this->assertFalse($maintenanceFolders->contains($contractsFolder));
        $this->assertFalse($maintenanceFiles->contains($contractsFile));
    }

    /** @test */
    public function multiple_facilities_maintain_separate_contracts_categories()
    {
        $facility2 = Facility::factory()->create();

        // 施設1の契約書フォルダ
        $facility1ContractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

        // 施設2の契約書フォルダ
        $facility2ContractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $facility2->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

        // 施設1の契約書のみ取得
        $facility1ContractsFolders = DocumentFolder::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();

        $this->assertCount(1, $facility1ContractsFolders);
        $this->assertTrue($facility1ContractsFolders->contains($facility1ContractsFolder));
        $this->assertFalse($facility1ContractsFolders->contains($facility2ContractsFolder));

        // 施設2の契約書のみ取得
        $facility2ContractsFolders = DocumentFolder::contracts()
            ->where('facility_id', $facility2->id)
            ->get();

        $this->assertCount(1, $facility2ContractsFolders);
        $this->assertTrue($facility2ContractsFolders->contains($facility2ContractsFolder));
        $this->assertFalse($facility2ContractsFolders->contains($facility1ContractsFolder));
    }

    /** @test */
    public function contracts_category_is_completely_isolated_from_other_categories()
    {
        // 各カテゴリのフォルダとファイルを作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'uploaded_by' => $this->user->id,
        ]);

        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書スコープで取得
        $contractsFolders = DocumentFolder::contracts()->where('facility_id', $this->facility->id)->get();
        $contractsFiles = DocumentFile::contracts()->where('facility_id', $this->facility->id)->get();

        // 契約書のみが含まれることを確認
        $this->assertCount(1, $contractsFolders);
        $this->assertCount(1, $contractsFiles);
        $this->assertTrue($contractsFolders->contains($contractsFolder));
        $this->assertTrue($contractsFiles->contains($contractsFile));

        // メインスコープで取得
        $mainFolders = DocumentFolder::main()->where('facility_id', $this->facility->id)->get();
        $mainFiles = DocumentFile::main()->where('facility_id', $this->facility->id)->get();

        // メインのみが含まれることを確認
        $this->assertCount(1, $mainFolders);
        $this->assertCount(1, $mainFiles);
        $this->assertTrue($mainFolders->contains($mainFolder));
        $this->assertTrue($mainFiles->contains($mainFile));

        // ライフラインスコープで取得
        $lifelineFolders = DocumentFolder::lifeline()->where('facility_id', $this->facility->id)->get();
        $lifelineFiles = DocumentFile::lifeline()->where('facility_id', $this->facility->id)->get();

        // ライフラインのみが含まれることを確認
        $this->assertCount(1, $lifelineFolders);
        $this->assertCount(1, $lifelineFiles);
        $this->assertTrue($lifelineFolders->contains($lifelineFolder));
        $this->assertTrue($lifelineFiles->contains($lifelineFile));

        // 修繕履歴スコープで取得
        $maintenanceFolders = DocumentFolder::maintenance()->where('facility_id', $this->facility->id)->get();
        $maintenanceFiles = DocumentFile::maintenance()->where('facility_id', $this->facility->id)->get();

        // 修繕履歴のみが含まれることを確認
        $this->assertCount(1, $maintenanceFolders);
        $this->assertCount(1, $maintenanceFiles);
        $this->assertTrue($maintenanceFolders->contains($maintenanceFolder));
        $this->assertTrue($maintenanceFiles->contains($maintenanceFile));
    }
}
