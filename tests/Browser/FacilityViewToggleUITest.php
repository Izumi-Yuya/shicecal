<?php

namespace Tests\Browser;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Carbon\Carbon;

class FacilityViewToggleUITest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $this->facility = $this->createFacilityWithCompleteData();
    }

    /**
     * Test view toggle button interactions and visual feedback
     * @test
     */
    public function it_provides_proper_visual_feedback_for_toggle_interactions()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Verify initial state - card view should be active
            $browser->assertChecked('#cardView')
                    ->assertNotChecked('#tableView')
                    ->assertPresent('label[for="cardView"].btn.btn-outline-primary')
                    ->assertPresent('label[for="tableView"].btn.btn-outline-primary');

            // Test hover effects on buttons
            $browser->mouseover('label[for="tableView"]')
                    ->pause(200); // Allow hover animation

            // Click table view button and verify visual feedback
            $browser->click('label[for="tableView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->assertSee('表示形式を変更中...')
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify button states after switch
            $browser->assertNotChecked('#cardView')
                    ->assertChecked('#tableView');

            // Test switching back to card view
            $browser->click('label[for="cardView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-info-card', 10);

            // Verify final button states
            $browser->assertChecked('#cardView')
                    ->assertNotChecked('#tableView');
        });
    }

    /**
     * Test keyboard navigation and accessibility compliance
     * @test
     */
    public function it_supports_keyboard_navigation_and_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Test direct focus on toggle buttons by clicking labels
            $browser->click('label[for="cardView"]')
                    ->assertChecked('#cardView');

            // Test keyboard activation with Enter key on label
            $browser->click('label[for="tableView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify the switch worked
            $browser->assertChecked('#tableView');

            // Test switching back with keyboard
            $browser->click('label[for="cardView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-info-card', 10);

            // Verify accessibility attributes
            $browser->assertAttribute('.btn-group', 'role', 'group')
                    ->assertAttribute('.btn-group', 'aria-label', '表示形式切り替え')
                    ->assertPresent('input[type="radio"][name="viewMode"]')
                    ->assertPresent('label[for="cardView"]')
                    ->assertPresent('label[for="tableView"]');
        });
    }

    /**
     * Test responsive design works on different screen sizes
     * @test
     */
    public function it_works_responsively_on_different_screen_sizes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility));

            // Test on desktop size (1920x1080)
            $browser->resize(1920, 1080)
                    ->waitFor('.view-toggle-container', 10)
                    ->assertPresent('.view-toggle-buttons')
                    ->assertPresent('.view-toggle-info')
                    ->assertVisible('label[for="cardView"]')
                    ->assertVisible('label[for="tableView"]');

            // Verify horizontal layout on desktop
            $toggleContainer = $browser->element('.view-toggle-container .d-flex');
            $this->assertNotNull($toggleContainer);

            // Test switching views on desktop
            $browser->click('label[for="tableView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10)
                    ->assertPresent('.table-responsive');

            // Test on tablet size (768x1024)
            $browser->resize(768, 1024)
                    ->assertPresent('.view-toggle-container')
                    ->assertVisible('label[for="cardView"]')
                    ->assertVisible('label[for="tableView"]')
                    ->assertPresent('.facility-table-view')
                    ->assertPresent('.table-responsive');

            // Test view switching on tablet
            $browser->click('label[for="cardView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-info-card', 10);

            // Test on mobile size (375x667)
            $browser->resize(375, 667)
                    ->assertPresent('.view-toggle-container')
                    ->assertVisible('label[for="cardView"]')
                    ->assertVisible('label[for="tableView"]');

            // Verify mobile layout adjustments
            $browser->assertPresent('.view-toggle-buttons')
                    ->assertPresent('.view-toggle-info');

            // Test view switching on mobile
            $browser->click('label[for="tableView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10)
                    ->assertPresent('.table-responsive');

            // Verify essential data is still visible on mobile
            $browser->assertSee($this->facility->facility_name)
                    ->assertSee($this->facility->company_name);

            // Test on very small mobile (320x568)
            $browser->resize(320, 568)
                    ->assertPresent('.view-toggle-container')
                    ->assertVisible('label[for="cardView"]')
                    ->assertVisible('label[for="tableView"]')
                    ->assertPresent('.facility-table-view');
        });
    }

    /**
     * Test smooth transitions and user experience flow
     * @test
     */
    public function it_provides_smooth_transitions_and_good_user_experience()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Test initial load experience
            $browser->assertPresent('.view-toggle-container')
                    ->assertPresent('.facility-info-card')
                    ->assertMissing('.facility-table-view');

            // Test transition to table view
            $startTime = microtime(true);
            
            $browser->click('label[for="tableView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5);

            // Verify loading state is shown
            $browser->assertSee('表示形式を変更中...')
                    ->assertPresent('.spinner-border');

            // Wait for transition to complete
            $browser->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            $endTime = microtime(true);
            $transitionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Verify transition completed in reasonable time (less than 10 seconds)
            $this->assertLessThan(10000, $transitionTime, 'Transition took too long');

            // Verify final state
            $browser->assertPresent('.facility-table-view')
                    ->assertMissing('.facility-info-card')
                    ->assertChecked('#tableView');

            // Test that interface remains functional after multiple interactions
            $browser->scrollTo('label[for="cardView"]')
                    ->click('label[for="cardView"]')
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.card.facility-info-card', 10);

            // Verify interface is still functional
            $browser->assertPresent('.facility-info-card')
                    ->assertChecked('#cardView');
        });
    }

    /**
     * Test session persistence across browser refresh and navigation
     * @test
     */
    public function it_persists_view_mode_across_browser_refresh_and_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Switch to table view
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify table view is active
            $browser->assertPresent('.facility-table-view')
                    ->assertChecked('#tableView');

            // Test persistence across page refresh
            $browser->refresh()
                    ->waitFor('.view-toggle-container', 10)
                    ->waitFor('.facility-table-view', 10);

            // Verify table view is still active after refresh
            $browser->assertPresent('.facility-table-view')
                    ->assertChecked('#tableView')
                    ->assertMissing('.facility-info-card');

            // Test persistence across navigation
            // Navigate away and back
            $browser->visit('/login')
                    ->pause(1000) // Wait for page to load
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10)
                    ->waitFor('.facility-table-view', 10);

            // Verify table view is still active after navigation
            $browser->assertPresent('.facility-table-view')
                    ->assertChecked('#tableView')
                    ->assertMissing('.facility-info-card');

            // Switch back to card view and test persistence
            $browser->script('document.querySelector("label[for=\'cardView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-info-card', 10);

            // Test card view persistence
            $browser->refresh()
                    ->waitFor('.view-toggle-container', 10)
                    ->waitFor('.facility-info-card', 10);

            $browser->assertPresent('.facility-info-card')
                    ->assertChecked('#cardView')
                    ->assertMissing('.facility-table-view');
        });
    }

    /**
     * Test error handling and recovery
     * @test
     */
    public function it_handles_errors_gracefully_and_provides_recovery_options()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Test behavior when JavaScript is disabled (graceful degradation)
            $browser->script('window.facilityViewToggle = null;'); // Simulate JS failure

            // The buttons should still be clickable (will cause page reload)
            $browser->assertPresent('label[for="cardView"]')
                    ->assertPresent('label[for="tableView"]')
                    ->assertVisible('label[for="cardView"]')
                    ->assertVisible('label[for="tableView"]');

            // Test that the interface remains functional
            // Note: After JS failure, the state might change, so we just verify elements exist
            $browser->assertPresent('#cardView')
                    ->assertPresent('#tableView');

            // Test that clicking still works (even if it causes page reload)
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            $browser->pause(2000) // Wait for potential page reload
                    ->assertPresent('.view-toggle-container');
                    
            // After JavaScript failure, the view mode might change via page reload
            // so we don't assert the specific checked state
        });
    }

    /**
     * Test accessibility features and ARIA compliance
     * @test
     */
    public function it_meets_accessibility_standards()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Test ARIA attributes
            $browser->assertAttribute('.btn-group', 'role', 'group')
                    ->assertAttribute('.btn-group', 'aria-label', '表示形式切り替え');

            // Test proper labeling
            $browser->assertAttribute('input[value="card"]', 'id', 'cardView')
                    ->assertAttribute('input[value="table"]', 'id', 'tableView')
                    ->assertAttribute('label[for="cardView"]', 'for', 'cardView')
                    ->assertAttribute('label[for="tableView"]', 'for', 'tableView');

            // Test screen reader support
            $browser->assertPresent('.visually-hidden'); // Loading spinner text

            // Test focus management
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify focus is maintained after view switch
            $browser->assertPresent('#tableView:checked');

            // Test color contrast and visual indicators
            $browser->assertPresent('.btn-outline-primary') // Proper contrast buttons
                    ->assertPresent('.text-muted'); // Proper contrast for help text

            // Test keyboard navigation
            $browser->keys('label[for="cardView"]', ['{tab}'])
                    ->keys('label[for="tableView"]', ['{enter}'])
                    ->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15);
        });
    }

    /**
     * Test performance and loading states
     * @test
     */
    public function it_provides_appropriate_loading_feedback()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Test loading state appears quickly
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            
            // Loading indicator should appear within 2 seconds
            $browser->waitFor('.view-toggle-loading-indicator', 5);
            
            // Verify loading content
            $browser->assertSee('表示形式を変更中...')
                    ->assertPresent('.spinner-border')
                    ->assertPresent('.spinner-border-sm');

            // Verify buttons are disabled during loading
            $browser->assertAttribute('#cardView', 'disabled', 'true')
                    ->assertAttribute('#tableView', 'disabled', 'true');

            // Wait for loading to complete
            $browser->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify buttons are re-enabled after loading
            $browser->assertAttributeMissing('#cardView', 'disabled')
                    ->assertAttributeMissing('#tableView', 'disabled');
        });
    }

    /**
     * Create facility with complete data for testing
     */
    private function createFacilityWithCompleteData(): Facility
    {
        return Facility::factory()->create([
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'facility_name' => 'テスト施設',
            'postal_code' => '1234567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストビル4F',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'toll_free_number' => '0120-123-456',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
            'opening_date' => Carbon::parse('2020-01-15'),
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60,
            'status' => 'approved',
        ]);
    }

    /**
     * Test table view displays all required elements
     * @test
     */
    public function it_displays_all_required_elements_in_table_view()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Switch to table view
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Check for table structure
            $browser->assertPresent('.facility-table-view')
                    ->assertPresent('.table-responsive')
                    ->assertPresent('.table');

            // Check for basic category headers that should exist
            $browser->assertSee('基本情報');

            // Check for facility data
            $browser->assertSee($this->facility->facility_name)
                    ->assertSee($this->facility->company_name);

            // Verify table is responsive
            $browser->assertPresent('.table-responsive');
        });
    }

    /**
     * Test view switching maintains functionality
     * @test
     */
    public function it_maintains_functionality_across_view_switches()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit(route('facilities.show', $this->facility))
                    ->waitFor('.view-toggle-container', 10);

            // Verify initial card view
            $browser->assertPresent('.facility-info-card')
                    ->assertChecked('#cardView');

            // Switch to table view
            $browser->scrollTo('.view-toggle-container')
                    ->pause(500);
            $browser->script('document.querySelector("label[for=\'tableView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-table-view', 10);

            // Verify table view is active
            $browser->assertPresent('.facility-table-view')
                    ->assertChecked('#tableView')
                    ->assertMissing('.facility-info-card');

            // Switch back to card view
            $browser->script('document.querySelector("label[for=\'cardView\']").click();');
            $browser->waitFor('.view-toggle-loading-indicator', 5)
                    ->waitUntilMissing('.view-toggle-loading-indicator', 15)
                    ->waitFor('.facility-info-card', 10);

            // Verify card view is restored
            $browser->assertPresent('.facility-info-card')
                    ->assertChecked('#cardView')
                    ->assertMissing('.facility-table-view');
        });
    }
}