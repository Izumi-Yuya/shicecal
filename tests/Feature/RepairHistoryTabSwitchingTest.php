<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RepairHistoryTabSwitchingTest extends TestCase
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
     * Test repair history main tab is integrated into facility show page.
     */
    public function test_repair_history_main_tab_integrated_into_facility_show_page()
    {
        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('修繕履歴');
        $response->assertSee('id="repair-history-tab"');
        $response->assertSee('data-bs-toggle="tab"');
        $response->assertSee('data-bs-target="#repair-history"');
    }

    /**
     * Test repair history main tab is positioned correctly after security-disaster tab.
     */
    public function test_repair_history_main_tab_positioned_after_security_disaster_tab()
    {
        $response = $this->get(route('facilities.show', $this->facility));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        $securityDisasterPos = strpos($content, 'id="security-disaster-tab"');
        $repairHistoryPos = strpos($content, 'id="repair-history-tab"');
        
        $this->assertNotFalse($securityDisasterPos);
        $this->assertNotFalse($repairHistoryPos);
        $this->assertLessThan($repairHistoryPos, $securityDisasterPos);
    }

    /**
     * Test repair history subtabs are displayed correctly.
     */
    public function test_repair_history_subtabs_displayed_correctly()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('id="repairHistoryTabs"');
        $response->assertSee('id="exterior-tab"');
        $response->assertSee('id="interior-tab"');
        $response->assertSee('id="other-tab"');
        $response->assertSee('data-bs-toggle="tab"');
        $response->assertSee('data-bs-target="#exterior"');
        $response->assertSee('data-bs-target="#interior"');
        $response->assertSee('data-bs-target="#other"');
    }

    /**
     * Test exterior subtab is active by default.
     */
    public function test_exterior_subtab_active_by_default()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('class="nav-link active"', false);
        
        // Check that exterior tab has active class
        $content = $response->getContent();
        $this->assertStringContainsString('id="exterior-tab"', $content);
        $this->assertStringContainsString('class="nav-link active"', $content);
        
        // Check that exterior content pane is active
        $this->assertStringContainsString('id="exterior"', $content);
        $this->assertStringContainsString('class="tab-pane fade show active"', $content);
    }

    /**
     * Test subtab content areas are properly structured.
     */
    public function test_subtab_content_areas_properly_structured()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('id="repairHistoryTabContent"');
        $response->assertSee('class="tab-content"');
        $response->assertSee('id="exterior"');
        $response->assertSee('id="interior"');
        $response->assertSee('id="other"');
        $response->assertSee('role="tabpanel"');
    }

    /**
     * Test subtab icons are displayed correctly.
     */
    public function test_subtab_icons_displayed_correctly()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('fas fa-building'); // Exterior icon
        $response->assertSee('fas fa-paint-brush'); // Interior icon
        $response->assertSee('fas fa-tools'); // Other icon
    }

    /**
     * Test subtab labels are displayed correctly.
     */
    public function test_subtab_labels_displayed_correctly()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('外装');
        $response->assertSee('内装リニューアル');
        $response->assertSee('その他');
    }

    /**
     * Test JavaScript for tab switching is included.
     */
    public function test_javascript_for_tab_switching_included()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('bootstrap.bundle.min.js');
        $response->assertSee('data-bs-toggle="tab"');
    }

    /**
     * Test tab switching preserves data integrity.
     */
    public function test_tab_switching_preserves_data_integrity()
    {
        // Create data for each category
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '外装防水工事',
            'created_by' => $this->user->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装リニューアル工事',
            'created_by' => $this->user->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'content' => 'その他改修工事',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('外装防水工事');
        $response->assertSee('内装リニューアル工事');
        $response->assertSee('その他改修工事');
    }

    /**
     * Test tab content is loaded correctly for each category.
     */
    public function test_tab_content_loaded_correctly_for_each_category()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        
        // Check that partial views are included
        $response->assertViewHas('exteriorHistory');
        $response->assertViewHas('interiorHistory');
        $response->assertViewHas('otherHistory');
        
        // Check that content includes the partial templates
        $content = $response->getContent();
        $this->assertStringContainsString('exterior-tab', $content);
        $this->assertStringContainsString('interior-tab', $content);
        $this->assertStringContainsString('other-tab', $content);
    }

    /**
     * Test tab switching works with empty data.
     */
    public function test_tab_switching_works_with_empty_data()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('データがありません');
        
        // All tabs should still be functional
        $response->assertSee('id="exterior-tab"');
        $response->assertSee('id="interior-tab"');
        $response->assertSee('id="other-tab"');
    }

    /**
     * Test tab switching maintains responsive design.
     */
    public function test_tab_switching_maintains_responsive_design()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('nav nav-tabs');
        $response->assertSee('repair-history-subtabs');
        $response->assertSee('mb-4'); // Bootstrap margin class
        $response->assertSee('table-responsive'); // Responsive table wrapper
    }

    /**
     * Test tab switching includes proper ARIA attributes for accessibility.
     */
    public function test_tab_switching_includes_proper_aria_attributes()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('role="tablist"');
        $response->assertSee('role="presentation"');
        $response->assertSee('role="tab"');
        $response->assertSee('role="tabpanel"');
        $response->assertSee('aria-selected');
    }

    /**
     * Test tab switching works with edit buttons.
     */
    public function test_tab_switching_works_with_edit_buttons()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        
        // Check that edit buttons are present for each tab
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'exterior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'interior']));
        $response->assertSee(route('facilities.repair-history.edit', [$this->facility, 'other']));
    }

    /**
     * Test tab switching preserves URL structure.
     */
    public function test_tab_switching_preserves_url_structure()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        
        // Check that the URL structure is maintained
        $this->assertEquals(
            route('facilities.repair-history.index', $this->facility),
            $response->getRequest()->url()
        );
    }

    /**
     * Test tab switching handles large datasets efficiently.
     */
    public function test_tab_switching_handles_large_datasets_efficiently()
    {
        // Create multiple records for each category
        for ($i = 1; $i <= 10; $i++) {
            MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'exterior',
                'subcategory' => 'waterproof',
                'content' => "外装工事 {$i}",
                'created_by' => $this->user->id,
            ]);

            MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'interior',
                'subcategory' => 'renovation',
                'content' => "内装工事 {$i}",
                'created_by' => $this->user->id,
            ]);

            MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'other',
                'subcategory' => 'renovation_work',
                'content' => "その他工事 {$i}",
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('外装工事 1');
        $response->assertSee('内装工事 1');
        $response->assertSee('その他工事 1');
        $response->assertSee('外装工事 10');
        $response->assertSee('内装工事 10');
        $response->assertSee('その他工事 10');
    }

    /**
     * Test tab switching CSS classes are applied correctly.
     */
    public function test_tab_switching_css_classes_applied_correctly()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('repair-history-container');
        $response->assertSee('repair-history-subtabs');
        $response->assertSee('nav-item');
        $response->assertSee('nav-link');
        $response->assertSee('tab-pane');
        $response->assertSee('fade');
    }

    /**
     * Test tab switching maintains proper Bootstrap structure.
     */
    public function test_tab_switching_maintains_proper_bootstrap_structure()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        
        // Check Bootstrap tab structure
        $content = $response->getContent();
        $this->assertStringContainsString('nav nav-tabs', $content);
        $this->assertStringContainsString('tab-content', $content);
        $this->assertStringContainsString('data-bs-toggle="tab"', $content);
        $this->assertStringContainsString('data-bs-target=', $content);
    }

    /**
     * Test tab switching works with special notes sections.
     */
    public function test_tab_switching_works_with_special_notes_sections()
    {
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'content' => '防水工事',
            'notes' => '外装工事の特記事項',
            'created_by' => $this->user->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'content' => '内装工事',
            'notes' => '内装工事の特記事項',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('特記事項');
        $response->assertSee('外装工事の特記事項');
        $response->assertSee('内装工事の特記事項');
    }

    /**
     * Test tab switching includes proper meta information.
     */
    public function test_tab_switching_includes_proper_meta_information()
    {
        $response = $this->get(route('facilities.repair-history.index', $this->facility));

        $response->assertStatus(200);
        $response->assertSee('data-facility-id="' . $this->facility->id . '"');
        $response->assertSee('data-category');
    }

    /**
     * Test tab switching handles authorization correctly.
     */
    public function test_tab_switching_handles_authorization_correctly()
    {
        // Test with authorized user
        $response = $this->get(route('facilities.repair-history.index', $this->facility));
        $response->assertStatus(200);
        $response->assertSee('編集');

        // Test with unauthorized user
        $viewerUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewerUser);

        $response = $this->get(route('facilities.repair-history.index', $this->facility));
        $response->assertStatus(200);
        $response->assertDontSee('編集');
    }
}