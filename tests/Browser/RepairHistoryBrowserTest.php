<?php

namespace Tests\Browser;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RepairHistoryBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    private Facility $facility;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->facility = Facility::factory()->create([
            'facility_name' => 'ブラウザテスト施設',
            'office_code' => 'BROWSER001',
        ]);

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // Create sample maintenance histories
        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'exterior',
            'subcategory' => 'waterproof',
            'maintenance_date' => '2024-01-15',
            'contractor' => '防水工事株式会社',
            'content' => '屋上防水工事',
            'cost' => 1500000,
            'contact_person' => '田中太郎',
            'phone_number' => '03-1234-5678',
            'created_by' => $this->adminUser->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'interior',
            'subcategory' => 'renovation',
            'maintenance_date' => '2024-02-20',
            'contractor' => '内装リフォーム株式会社',
            'content' => '1階フロア全面リニューアル',
            'cost' => 2500000,
            'created_by' => $this->adminUser->id,
        ]);

        MaintenanceHistory::factory()->create([
            'facility_id' => $this->facility->id,
            'category' => 'other',
            'subcategory' => 'renovation_work',
            'maintenance_date' => '2024-03-10',
            'contractor' => '総合建設株式会社',
            'content' => '給排水設備改修工事',
            'cost' => 1200000,
            'created_by' => $this->adminUser->id,
        ]);
    }

    /**
     * Test the repair history tab display and interaction functionality.
     */
    public function test_repair_history_tab_display_and_interaction()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴')
                    ->assertSee('修繕履歴')
                    ->assertSee('外装')
                    ->assertSee('内装リニューアル')
                    ->assertSee('その他');

            // Test tab switching
            $browser->click('#exterior-tab')
                    ->waitForText('防水工事株式会社')
                    ->assertSee('防水工事株式会社')
                    ->assertSee('屋上防水工事')
                    ->assertSee('1,500,000');

            $browser->click('#interior-tab')
                    ->waitForText('内装リフォーム株式会社')
                    ->assertSee('内装リフォーム株式会社')
                    ->assertSee('1階フロア全面リニューアル')
                    ->assertSee('2,500,000');

            $browser->click('#other-tab')
                    ->waitForText('総合建設株式会社')
                    ->assertSee('総合建設株式会社')
                    ->assertSee('給排水設備改修工事')
                    ->assertSee('1,200,000');
        });
    }

    /**
     * Test the edit functionality through the browser interface.
     */
    public function test_edit_functionality_through_browser()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴')
                    ->click('#exterior-tab')
                    ->waitForText('編集')
                    ->click('a[href*="repair-history"][href*="edit"][href*="exterior"]')
                    ->waitForText('外装修繕履歴編集')
                    ->assertSee('外装修繕履歴編集');

            // Test form interaction
            $browser->type('input[name="histories[0][contractor]"]', '新規防水工事株式会社')
                    ->type('input[name="histories[0][content]"]', '新規防水工事内容')
                    ->type('input[name="histories[0][cost]"]', '900000')
                    ->press('保存')
                    ->waitForRoute('facilities.show', $this->facility)
                    ->assertSee('修繕履歴が更新されました。');
        });
    }

    /**
     * Test the responsive design on different screen sizes.
     */
    public function test_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            // Desktop view
            $browser->loginAs($this->adminUser)
                    ->resize(1200, 800)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴')
                    ->assertSee('修繕履歴');

            // Tablet view
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForText('修繕履歴')
                    ->assertSee('修繕履歴')
                    ->click('#exterior-tab')
                    ->waitForText('防水工事株式会社')
                    ->assertSee('防水工事株式会社');

            // Mobile view
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForText('修繕履歴')
                    ->assertSee('修繕履歴')
                    ->click('#interior-tab')
                    ->waitForText('内装リフォーム株式会社')
                    ->assertSee('内装リフォーム株式会社');
        });
    }

    /**
     * Test the JavaScript functionality.
     */
    public function test_javascript_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴');

            // Test tab switching with JavaScript
            $browser->assertVisible('#exterior')
                    ->assertNotVisible('#interior')
                    ->assertNotVisible('#other');

            $browser->click('#interior-tab')
                    ->pause(500)
                    ->assertNotVisible('#exterior')
                    ->assertVisible('#interior')
                    ->assertNotVisible('#other');

            $browser->click('#other-tab')
                    ->pause(500)
                    ->assertNotVisible('#exterior')
                    ->assertNotVisible('#interior')
                    ->assertVisible('#other');

            // Test active tab styling
            $browser->assertAttribute('#other-tab', 'class', 'nav-link active')
                    ->assertAttributeDoesntContain('#exterior-tab', 'class', 'active')
                    ->assertAttributeDoesntContain('#interior-tab', 'class', 'active');
        });
    }

    /**
     * Test the table scrolling functionality.
     */
    public function test_table_scrolling_functionality()
    {
        // Create more test data for scrolling
        for ($i = 0; $i < 20; $i++) {
            MaintenanceHistory::factory()->create([
                'facility_id' => $this->facility->id,
                'category' => 'exterior',
                'subcategory' => 'waterproof',
                'maintenance_date' => '2024-01-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                'contractor' => "防水工事株式会社{$i}",
                'content' => "防水工事{$i}",
                'cost' => 100000 + ($i * 10000),
                'created_by' => $this->adminUser->id,
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴')
                    ->click('#exterior-tab')
                    ->waitForText('防水工事株式会社0');

            // Test that table is scrollable
            $browser->script('document.querySelector(".repair-history-table").scrollTop = 200;');
            $browser->pause(500);

            // Verify scrolling worked
            $scrollTop = $browser->script('return document.querySelector(".repair-history-table").scrollTop;')[0];
            $this->assertGreaterThan(0, $scrollTop);
        });
    }

    /**
     * Test the error handling in the browser.
     */
    public function test_error_handling_in_browser()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.repair-history.edit', [$this->facility, 'exterior']))
                    ->waitForText('外装修繕履歴編集');

            // Submit form with invalid data
            $browser->clear('input[name="histories[0][contractor]"]')
                    ->clear('input[name="histories[0][content]"]')
                    ->type('input[name="histories[0][maintenance_date]"]', 'invalid-date')
                    ->press('保存')
                    ->waitForText('入力内容にエラーがあります')
                    ->assertSee('施工会社は必須項目です。')
                    ->assertSee('修繕内容は必須項目です。')
                    ->assertSee('施工日は有効な日付形式で入力してください。');
        });
    }

    /**
     * Test the accessibility features.
     */
    public function test_accessibility_features()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitForText('修繕履歴');

            // Test keyboard navigation
            $browser->keys('#exterior-tab', ['{tab}'])
                    ->assertFocused('#interior-tab')
                    ->keys('#interior-tab', ['{enter}'])
                    ->pause(500)
                    ->assertVisible('#interior');

            // Test ARIA attributes
            $browser->assertAttribute('#repairHistoryTabs', 'role', 'tablist')
                    ->assertAttribute('#exterior-tab', 'role', 'tab')
                    ->assertAttribute('#interior-tab', 'role', 'tab')
                    ->assertAttribute('#other-tab', 'role', 'tab');
        });
    }
}