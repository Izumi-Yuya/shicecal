<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryDisplayTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with editor role
        $this->user = User::factory()->create([
            'role' => 'editor',
        ]);
        $this->actingAs($this->user);

        // Create a test facility
        $this->facility = Facility::factory()->create(['status' => 'approved']);
    }

    /**
     * Test that the repair history index page displays the main tab correctly.
     */
    public function test_repair_history_main_tab_displays_correctly()
    {
        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('修繕履歴');
        $response->assertSee('repair-history-container');
    }

    /**
     * Test that the repair history subtabs are displayed correctly.
     */
    public function test_repair_history_subtabs_display_correctly()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('外装');
        $response->assertSee('内装リニューアル');
        $response->assertSee('その他');
        $response->assertSee('repairHistoryTabs');
    }

    /**
     * Test that the exterior tab displays waterproof and painting sections.
     */
    public function test_exterior_tab_displays_waterproof_and_painting_sections()
    {
        // Create waterproof history
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
            'contractor' => '防水工事株式会社',
            'contact_person' => '山田太郎',
            'phone_number' => '03-1234-5678',
            'maintenance_date' => '2024-01-15',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $this->user->id,
        ]);

        // Create painting history
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'painting',
            'content' => '外壁塗装工事',
            'contractor' => '塗装工事株式会社',
            'contact_person' => '田中花子',
            'phone_number' => '03-9876-5432',
            'maintenance_date' => '2024-02-20',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('防水');
        $response->assertSee('塗装');
        $response->assertSee('屋上防水工事');
        $response->assertSee('外壁塗装工事');
        $response->assertSee('防水工事株式会社');
        $response->assertSee('塗装工事株式会社');
        $response->assertSee('山田太郎');
        $response->assertSee('田中花子');
    }

    /**
     * Test that the waterproof warranty period table is displayed.
     */
    public function test_waterproof_warranty_period_table_displays()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '屋上防水工事',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('保証期間');
        $response->assertSee('10年');
        $response->assertSee('2024年01月15日');
        $response->assertSee('2034年01月15日');
    }

    /**
     * Test that the interior tab displays renovation and design sections.
     */
    public function test_interior_tab_displays_renovation_and_design_sections()
    {
        // Create interior renovation history
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装リニューアル工事',
            'contractor' => '内装工事株式会社',
            'contact_person' => '佐藤次郎',
            'phone_number' => '03-5555-6666',
            'maintenance_date' => '2024-03-10',
            'cost' => 800000,
            'classification' => '改修工事',
            'notes' => '内装工事の特記事項',
            'created_by' => $this->user->id,
        ]);

        // Create interior design history
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'design',
            'content' => '意匠改修工事',
            'contractor' => '意匠工事株式会社',
            'maintenance_date' => '2024-04-05',
            'cost' => 300000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('内装リニューアル');
        $response->assertSee('内装・意匠履歴');
        $response->assertSee('内装リニューアル工事');
        $response->assertSee('意匠改修工事');
        $response->assertSee('内装工事株式会社');
        $response->assertSee('意匠工事株式会社');
        $response->assertSee('800,000');
        $response->assertSee('300,000');
    }

    /**
     * Test that the other tab displays renovation work history.
     */
    public function test_other_tab_displays_renovation_work_history()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'content' => 'エアコン修理',
            'contractor' => '設備工事株式会社',
            'contact_person' => '鈴木美咲',
            'phone_number' => '03-7777-8888',
            'maintenance_date' => '2024-05-12',
            'cost' => 50000,
            'classification' => '緊急修理',
            'notes' => 'エアコン修理の特記事項',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('改修工事履歴');
        $response->assertSee('エアコン修理');
        $response->assertSee('設備工事株式会社');
        $response->assertSee('鈴木美咲');
        $response->assertSee('50,000');
        $response->assertSee('緊急修理');
    }

    /**
     * Test that the repair history data is sorted by maintenance date in descending order.
     */
    public function test_repair_history_sorted_by_date_descending()
    {
        // Create histories with different dates
        $oldHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '古い工事',
            'maintenance_date' => '2023-01-01',
            'created_by' => $this->user->id,
        ]);

        $newHistory = MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '新しい工事',
            'maintenance_date' => '2024-01-01',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        
        // Check that newer history appears before older history in the response
        $content = $response->getContent();
        $newPos = strpos($content, '新しい工事');
        $oldPos = strpos($content, '古い工事');
        
        $this->assertNotFalse($newPos);
        $this->assertNotFalse($oldPos);
        $this->assertLessThan($oldPos, $newPos);
    }

    /**
     * Test that the repair history displays special notes sections.
     */
    public function test_repair_history_displays_special_notes_sections()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'notes' => '外装工事の特記事項です',
            'created_by' => $this->user->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装工事',
            'notes' => '内装工事の特記事項です',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('特記事項');
        $response->assertSee('外装工事の特記事項です');
        $response->assertSee('内装工事の特記事項です');
    }

    /**
     * Test that the repair history displays proper table structure.
     */
    public function test_repair_history_displays_proper_table_structure()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'contractor' => '工事業者',
            'maintenance_date' => '2024-01-15',
            'cost' => 500000,
            'classification' => '定期点検',
            'notes' => '備考',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('NO');
        $response->assertSee('施工日');
        $response->assertSee('施工会社');
        $response->assertSee('金額');
        $response->assertSee('区分');
        $response->assertSee('修繕内容');
        $response->assertSee('備考');
        $response->assertSee('2024-01-15');
        $response->assertSee('工事業者');
        $response->assertSee('500,000');
        $response->assertSee('定期点検');
        $response->assertSee('防水工事');
    }

    /**
     * Test that the repair history displays edit buttons for authorized users.
     */
    public function test_repair_history_displays_edit_buttons_for_authorized_users()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('編集');
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'other']));
    }

    /**
     * Test that the repair history does not display edit buttons for unauthorized users.
     */
    public function test_repair_history_does_not_display_edit_buttons_for_unauthorized_users()
    {
        // Create a viewer user without edit permissions
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertDontSee('編集');
        $response->assertDontSee(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
    }

    /**
     * Test that the repair history displays empty state when no data exists.
     */
    public function test_repair_history_displays_empty_state_when_no_data()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('修繕履歴が登録されていません');
    }

    /**
     * Test that the repair history displays cost formatting correctly.
     */
    public function test_repair_history_displays_cost_formatting_correctly()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'cost' => 1234567.89,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('1,234,568'); // Rounded to nearest yen
    }

    /**
     * Test that the repair history displays contact information correctly.
     */
    public function test_repair_history_displays_contact_information_correctly()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'contractor' => '防水工事株式会社',
            'contact_person' => '山田太郎',
            'phone_number' => '03-1234-5678',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('防水工事株式会社');
        $response->assertSee('山田太郎');
        $response->assertSee('03-1234-5678');
    }

    /**
     * Test that the repair history handles large datasets with scrolling.
     */
    public function test_repair_history_handles_large_datasets_with_scrolling()
    {
        // Create multiple history records
        for ($i = 1; $i <= 20; $i++) {
            MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'exterior',
                'subcategory' => 'waterproof',
                'content' => "防水工事 {$i}",
                'maintenance_date' => sprintf('2024-01-%02d', $i),
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('防水工事 1');
        $response->assertSee('防水工事 20');
        $response->assertSee('table-responsive'); // Check for scrollable table class
    }

    /**
     * Test that the repair history displays warranty information only for the exterior category.
     */
    public function test_repair_history_displays_warranty_only_for_exterior()
    {
        // Create exterior history with warranty
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'warranty_period_years' => 10,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2034-01-15',
            'created_by' => $this->user->id,
        ]);

        // Create interior history without warranty
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装工事',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('保証期間'); // Should appear for exterior
        
        // Check that warranty information appears only once (for exterior)
        $content = $response->getContent();
        $warrantyCount = substr_count($content, '保証期間');
        $this->assertEquals(1, $warrantyCount);
    }
}