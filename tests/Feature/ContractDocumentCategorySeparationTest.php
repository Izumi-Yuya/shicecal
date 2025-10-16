<?php

namespace Tests\Feature;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractDocumentCategorySeparationTest extends TestCase
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
    public function contracts_documents_are_isolated_from_other_categories()
    {
        // 各カテゴリのフォルダを作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts',
            'created_by' => $this->user->id,
        ]);

        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main',
            'created_by' => $this->user->id,
        ]);

        $lifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Lifeline',
            'created_by' => $this->user->id,
        ]);

        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Maintenance',
            'created_by' => $this->user->id,
        ]);

        // 各カテゴリのファイルを作成
        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $contractsFolder->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        $mainFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $mainFolder->id,
            'category' => null,
            'uploaded_by' => $this->user->id,
        ]);

        $lifelineFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $lifelineFolder->id,
            'category' => 'lifeline_electrical',
            'uploaded_by' => $this->user->id,
        ]);

        $maintenanceFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $maintenanceFolder->id,
            'category' => 'maintenance_exterior',
            'uploaded_by' => $this->user->id,
        ]);

        // 契約書カテゴリのみを取得
        $contractsFolders = DocumentFolder::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();
        $contractsFiles = DocumentFile::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 契約書のみが含まれることを確認
        $this->assertCount(1, $contractsFolders);
        $this->assertCount(1, $contractsFiles);
        $this->assertEquals('contracts', $contractsFolders->first()->category);
        $this->assertEquals('contracts', $contractsFiles->first()->category);

        // 他のカテゴリのスコープで契約書が含まれないことを確認
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $mainFolders);
        $this->assertFalse($mainFolders->contains($contractsFolder));

        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $lifelineFolders);
        $this->assertFalse($lifelineFolders->contains($contractsFolder));

        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $maintenanceFolders);
        $this->assertFalse($maintenanceFolders->contains($contractsFolder));
    }

    /** @test */
    public function contracts_category_maintains_data_integrity()
    {
        // 契約書フォルダとファイルを作成
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts Root',
            'created_by' => $this->user->id,
        ]);

        $contractsFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $contractsFolder->id,
            'category' => 'contracts',
            'original_name' => 'contract.pdf',
            'uploaded_by' => $this->user->id,
        ]);

        // データベースから直接取得して確認
        $folderFromDb = DocumentFolder::find($contractsFolder->id);
        $fileFromDb = DocumentFile::find($contractsFile->id);

        $this->assertEquals('contracts', $folderFromDb->category);
        $this->assertEquals('contracts', $fileFromDb->category);
        $this->assertTrue($folderFromDb->isContracts());
        $this->assertTrue($fileFromDb->isContracts());
    }

    /** @test */
    public function contracts_scope_works_with_folder_hierarchy()
    {
        // ルートフォルダ
        $rootFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Contracts',
            'parent_id' => null,
            'created_by' => $this->user->id,
        ]);

        // サブフォルダ
        $subFolder1 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Estimates',
            'parent_id' => $rootFolder->id,
            'created_by' => $this->user->id,
        ]);

        $subFolder2 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'name' => 'Invoices',
            'parent_id' => $rootFolder->id,
            'created_by' => $this->user->id,
        ]);

        // 各フォルダにファイルを作成
        $rootFile = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $rootFolder->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        $subFile1 = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $subFolder1->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        $subFile2 = DocumentFile::factory()->create([
            'facility_id' => $this->facility->id,
            'folder_id' => $subFolder2->id,
            'category' => 'contracts',
            'uploaded_by' => $this->user->id,
        ]);

        // すべての契約書フォルダとファイルを取得
        $allFolders = DocumentFolder::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();
        $allFiles = DocumentFile::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();

        // すべてが取得できることを確認
        $this->assertCount(3, $allFolders);
        $this->assertCount(3, $allFiles);

        // 階層構造が保たれていることを確認
        $this->assertNull($rootFolder->parent_id);
        $this->assertEquals($rootFolder->id, $subFolder1->parent_id);
        $this->assertEquals($rootFolder->id, $subFolder2->parent_id);
    }

    /** @test */
    public function contracts_category_does_not_affect_existing_categories()
    {
        // 既存のカテゴリのデータを作成
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

        // 契約書カテゴリを追加
        $contractsFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'contracts',
            'created_by' => $this->user->id,
        ]);

        // 既存のスコープが正常に動作することを確認
        $mainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $mainFolders);
        $this->assertTrue($mainFolders->first()->isMain());

        $lifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $lifelineFolders);
        $this->assertTrue($lifelineFolders->first()->isLifeline());

        $maintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $maintenanceFolders);
        $this->assertTrue($maintenanceFolders->first()->isMaintenance());

        // 契約書スコープも正常に動作することを確認
        $contractsFolders = DocumentFolder::contracts()
            ->where('facility_id', $this->facility->id)
            ->get();
        $this->assertCount(1, $contractsFolders);
        $this->assertTrue($contractsFolders->first()->isContracts());
    }
}
