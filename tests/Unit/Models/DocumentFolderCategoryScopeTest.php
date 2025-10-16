<?php

namespace Tests\Unit\Models;

use App\Models\DocumentFolder;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentFolderCategoryScopeTest extends TestCase
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
    public function main_scope_returns_only_main_documents()
    {
        // メインドキュメントフォルダ作成（category = NULL）
        $mainFolder1 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder 1',
            'created_by' => $this->user->id,
        ]);

        $mainFolder2 = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder 2',
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

        // メインドキュメントのみ取得
        $folders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        // メインドキュメントのみが含まれることを確認
        $this->assertCount(2, $folders);
        $this->assertTrue($folders->contains($mainFolder1));
        $this->assertTrue($folders->contains($mainFolder2));
        $this->assertFalse($folders->contains($lifelineFolder));
        $this->assertFalse($folders->contains($maintenanceFolder));
    }

    /** @test */
    public function lifeline_scope_returns_only_lifeline_documents()
    {
        // メインドキュメントフォルダ作成
        $mainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'name' => 'Main Folder',
            'created_by' => $this->user->id,
        ]);

        // ライフライン設備フォルダ作成（複数カテゴリ）
        $electricalFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'created_by' => $this->user->id,
        ]);

        $gasFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'name' => 'Gas Folder',
            'created_by' => $this->user->id,
        ]);

        $waterFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_water',
            'name' => 'Water Folder',
            'created_by' => $this->user->id,
        ]);

        // 修繕履歴フォルダ作成
        $maintenanceFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'created_by' => $this->user->id,
        ]);

        // すべてのライフライン設備フォルダを取得
        $allLifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        // ライフライン設備のみが含まれることを確認
        $this->assertCount(3, $allLifelineFolders);
        $this->assertTrue($allLifelineFolders->contains($electricalFolder));
        $this->assertTrue($allLifelineFolders->contains($gasFolder));
        $this->assertTrue($allLifelineFolders->contains($waterFolder));
        $this->assertFalse($allLifelineFolders->contains($mainFolder));
        $this->assertFalse($allLifelineFolders->contains($maintenanceFolder));
    }

    /** @test */
    public function lifeline_scope_with_category_returns_specific_category_only()
    {
        // ライフライン設備フォルダ作成（複数カテゴリ）
        $electricalFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'name' => 'Electrical Folder',
            'created_by' => $this->user->id,
        ]);

        $gasFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_gas',
            'name' => 'Gas Folder',
            'created_by' => $this->user->id,
        ]);

        // 電気設備のみ取得
        $electricalFolders = DocumentFolder::lifeline('electrical')
            ->where('facility_id', $this->facility->id)
            ->get();

        // 電気設備のみが含まれることを確認
        $this->assertCount(1, $electricalFolders);
        $this->assertTrue($electricalFolders->contains($electricalFolder));
        $this->assertFalse($electricalFolders->contains($gasFolder));

        // ガス設備のみ取得
        $gasFolders = DocumentFolder::lifeline('gas')
            ->where('facility_id', $this->facility->id)
            ->get();

        // ガス設備のみが含まれることを確認
        $this->assertCount(1, $gasFolders);
        $this->assertTrue($gasFolders->contains($gasFolder));
        $this->assertFalse($gasFolders->contains($electricalFolder));
    }

    /** @test */
    public function maintenance_scope_returns_only_maintenance_documents()
    {
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

        // 修繕履歴フォルダ作成（複数カテゴリ）
        $exteriorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'created_by' => $this->user->id,
        ]);

        $interiorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'name' => 'Interior Folder',
            'created_by' => $this->user->id,
        ]);

        $summerCondensationFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_summer_condensation',
            'name' => 'Summer Condensation Folder',
            'created_by' => $this->user->id,
        ]);

        // すべての修繕履歴フォルダを取得
        $allMaintenanceFolders = DocumentFolder::maintenance()
            ->where('facility_id', $this->facility->id)
            ->get();

        // 修繕履歴のみが含まれることを確認
        $this->assertCount(3, $allMaintenanceFolders);
        $this->assertTrue($allMaintenanceFolders->contains($exteriorFolder));
        $this->assertTrue($allMaintenanceFolders->contains($interiorFolder));
        $this->assertTrue($allMaintenanceFolders->contains($summerCondensationFolder));
        $this->assertFalse($allMaintenanceFolders->contains($mainFolder));
        $this->assertFalse($allMaintenanceFolders->contains($lifelineFolder));
    }

    /** @test */
    public function maintenance_scope_with_category_returns_specific_category_only()
    {
        // 修繕履歴フォルダ作成（複数カテゴリ）
        $exteriorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_exterior',
            'name' => 'Exterior Folder',
            'created_by' => $this->user->id,
        ]);

        $interiorFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'maintenance_interior',
            'name' => 'Interior Folder',
            'created_by' => $this->user->id,
        ]);

        // 外装のみ取得
        $exteriorFolders = DocumentFolder::maintenance('exterior')
            ->where('facility_id', $this->facility->id)
            ->get();

        // 外装のみが含まれることを確認
        $this->assertCount(1, $exteriorFolders);
        $this->assertTrue($exteriorFolders->contains($exteriorFolder));
        $this->assertFalse($exteriorFolders->contains($interiorFolder));

        // 内装のみ取得
        $interiorFolders = DocumentFolder::maintenance('interior')
            ->where('facility_id', $this->facility->id)
            ->get();

        // 内装のみが含まれることを確認
        $this->assertCount(1, $interiorFolders);
        $this->assertTrue($interiorFolders->contains($interiorFolder));
        $this->assertFalse($interiorFolders->contains($exteriorFolder));
    }

    /** @test */
    public function isMain_method_correctly_identifies_main_documents()
    {
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

        $this->assertTrue($mainFolder->isMain());
        $this->assertFalse($lifelineFolder->isMain());
        $this->assertFalse($maintenanceFolder->isMain());
    }

    /** @test */
    public function isLifeline_method_correctly_identifies_lifeline_documents()
    {
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

        $this->assertFalse($mainFolder->isLifeline());
        $this->assertTrue($lifelineFolder->isLifeline());
        $this->assertFalse($maintenanceFolder->isLifeline());
    }

    /** @test */
    public function isMaintenance_method_correctly_identifies_maintenance_documents()
    {
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

        $this->assertFalse($mainFolder->isMaintenance());
        $this->assertFalse($lifelineFolder->isMaintenance());
        $this->assertTrue($maintenanceFolder->isMaintenance());
    }

    /** @test */
    public function scopes_work_correctly_with_multiple_facilities()
    {
        $facility2 = Facility::factory()->create();

        // 施設1のフォルダ
        $facility1MainFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        $facility1LifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'lifeline_electrical',
            'created_by' => $this->user->id,
        ]);

        // 施設2のフォルダ
        $facility2MainFolder = DocumentFolder::factory()->create([
            'facility_id' => $facility2->id,
            'category' => null,
            'created_by' => $this->user->id,
        ]);

        $facility2LifelineFolder = DocumentFolder::factory()->create([
            'facility_id' => $facility2->id,
            'category' => 'lifeline_gas',
            'created_by' => $this->user->id,
        ]);

        // 施設1のメインドキュメントのみ取得
        $facility1MainFolders = DocumentFolder::main()
            ->where('facility_id', $this->facility->id)
            ->get();

        $this->assertCount(1, $facility1MainFolders);
        $this->assertTrue($facility1MainFolders->contains($facility1MainFolder));
        $this->assertFalse($facility1MainFolders->contains($facility2MainFolder));

        // 施設1のライフライン設備のみ取得
        $facility1LifelineFolders = DocumentFolder::lifeline()
            ->where('facility_id', $this->facility->id)
            ->get();

        $this->assertCount(1, $facility1LifelineFolders);
        $this->assertTrue($facility1LifelineFolders->contains($facility1LifelineFolder));
        $this->assertFalse($facility1LifelineFolders->contains($facility2LifelineFolder));
    }
}
