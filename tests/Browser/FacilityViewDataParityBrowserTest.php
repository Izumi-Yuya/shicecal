<?php

namespace Tests\Browser;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FacilityViewDataParityBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * Test data parity between card and table views through browser interaction
     *
     * @test
     */
    public function it_maintains_data_parity_when_switching_views()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->waitFor('.facility-detail-container');

            // Start in card view and extract visible data
            $browser->assertSee('カード形式')
                ->assertPresent('.card.facility-info-card');

            $cardViewData = $this->extractVisibleData($browser);

            // Switch to table view
            $browser->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view')
                ->pause(500); // Allow transition to complete

            // Extract table view data
            $tableViewData = $this->extractVisibleData($browser);

            // Verify essential data appears in both views
            $this->assertDataParity($cardViewData, $tableViewData);

            // Switch back to card view
            $browser->click('button[data-view-mode="card"]')
                ->waitFor('.card.facility-info-card')
                ->pause(500);

            // Verify data is still present after switching back
            $cardViewDataAfterSwitch = $this->extractVisibleData($browser);
            $this->assertDataParity($cardViewData, $cardViewDataAfterSwitch);
        });
    }

    /**
     * Test proper formatting of different data types in table view
     *
     * @test
     */
    public function it_formats_data_types_correctly_in_table_view()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view');

            // Test date formatting (Japanese format)
            if ($this->facility->opening_date) {
                $expectedDate = $this->facility->opening_date->format('Y年m月d日');
                $browser->assertSee($expectedDate);
            }

            // Test number formatting with units
            if ($this->facility->building_floors !== null) {
                $browser->assertSee(number_format($this->facility->building_floors).'階');
            }

            if ($this->facility->paid_rooms_count !== null) {
                $browser->assertSee(number_format($this->facility->paid_rooms_count).'室');
            }

            if ($this->facility->capacity !== null) {
                $browser->assertSee(number_format($this->facility->capacity).'名');
            }

            // Test email link functionality
            if ($this->facility->email) {
                $browser->assertPresent('a[href="mailto:'.$this->facility->email.'"]');
            }

            // Test URL link functionality
            if ($this->facility->website_url) {
                $browser->assertPresent('a[href="'.$this->facility->website_url.'"][target="_blank"]');
            }

            // Test badge formatting for office code
            $browser->assertPresent('.badge.bg-primary')
                ->assertSee($this->facility->office_code);
        });
    }

    /**
     * Test service information completeness in both views
     *
     * @test
     */
    public function it_displays_complete_service_information_in_both_views()
    {
        $this->facility = $this->createFacilityWithServices();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility));

            // Collect service data from card view
            $cardServices = [];
            foreach ($this->facility->services as $service) {
                $browser->assertSee($service->service_type);
                $cardServices[] = $service->service_type;

                if ($service->renewal_start_date) {
                    $formattedDate = $service->renewal_start_date->format('Y年m月d日');
                    $browser->assertSee($formattedDate);
                    $cardServices[] = $formattedDate;
                }

                if ($service->renewal_end_date) {
                    $formattedDate = $service->renewal_end_date->format('Y年m月d日');
                    $browser->assertSee($formattedDate);
                    $cardServices[] = $formattedDate;
                }
            }

            // Switch to table view
            $browser->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view');

            // Verify all service data appears in table view
            foreach ($cardServices as $serviceData) {
                $browser->assertSee($serviceData);
            }
        });
    }

    /**
     * Test empty value handling displays "未設定" correctly
     *
     * @test
     */
    public function it_handles_empty_values_correctly()
    {
        $facilityWithEmptyValues = $this->createFacilityWithEmptyValues();

        $this->browse(function (Browser $browser) use ($facilityWithEmptyValues) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $facilityWithEmptyValues))
                ->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view');

            // Count "未設定" occurrences - should be present for empty fields
            $pageSource = $browser->driver->getPageSource();
            $emptyValueCount = substr_count($pageSource, '未設定');

            $this->assertGreaterThan(0, $emptyValueCount, 'Empty values should display as "未設定"');

            // Verify specific empty fields show "未設定"
            $browser->assertSee('未設定');
        });
    }

    /**
     * Test view mode persistence across page refresh
     *
     * @test
     */
    public function it_persists_view_mode_across_page_refresh()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility))
                ->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view')
                ->refresh()
                ->waitFor('.facility-table-view');

            // Verify table view is still active after refresh
            $browser->assertPresent('.facility-table-view')
                ->assertMissing('.card.facility-info-card');
        });
    }

    /**
     * Test responsive design works on different screen sizes
     *
     * @test
     */
    public function it_works_responsively_on_different_screen_sizes()
    {
        $this->facility = $this->createFacilityWithCompleteData();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('facilities.show', $this->facility));

            // Test on desktop size
            $browser->resize(1200, 800)
                ->click('button[data-view-mode="table"]')
                ->waitFor('.facility-table-view')
                ->assertPresent('.table-responsive');

            // Test on tablet size
            $browser->resize(768, 1024)
                ->assertPresent('.facility-table-view')
                ->assertPresent('.table-responsive');

            // Test on mobile size
            $browser->resize(375, 667)
                ->assertPresent('.facility-table-view')
                ->assertPresent('.table-responsive');

            // Verify essential data is still visible on mobile
            $browser->assertSee($this->facility->facility_name)
                ->assertSee($this->facility->company_name);
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
     * Create facility with empty values for testing
     */
    private function createFacilityWithEmptyValues(): Facility
    {
        return Facility::factory()->create([
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST002',
            'facility_name' => 'テスト施設2',
            'designation_number' => null,
            'postal_code' => null,
            'address' => null,
            'building_name' => null,
            'phone_number' => null,
            'fax_number' => null,
            'toll_free_number' => null,
            'email' => null,
            'website_url' => null,
            'opening_date' => null,
            'years_in_operation' => null,
            'building_structure' => null,
            'building_floors' => null,
            'paid_rooms_count' => null,
            'ss_rooms_count' => null,
            'capacity' => null,
        ]);
    }

    /**
     * Create facility with services for testing
     */
    private function createFacilityWithServices(): Facility
    {
        $facility = $this->createFacilityWithCompleteData();

        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '介護保険サービス',
            'renewal_start_date' => Carbon::parse('2023-04-01'),
            'renewal_end_date' => Carbon::parse('2029-03-31'),
        ]);

        FacilityService::factory()->create([
            'facility_id' => $facility->id,
            'service_type' => '障害福祉サービス',
            'renewal_start_date' => Carbon::parse('2022-10-01'),
            'renewal_end_date' => Carbon::parse('2028-09-30'),
        ]);

        return $facility->fresh(['services']);
    }

    /**
     * Extract visible data from current browser state
     */
    private function extractVisibleData(Browser $browser): array
    {
        $data = [];

        // Get all visible text content from the page
        $pageText = $browser->text('body');

        // Extract key facility information
        $facilityFields = [
            $this->facility->company_name,
            $this->facility->facility_name,
            $this->facility->office_code,
            $this->facility->designation_number,
            $this->facility->formatted_postal_code,
            $this->facility->full_address,
            $this->facility->phone_number,
            $this->facility->fax_number,
            $this->facility->toll_free_number,
            $this->facility->email,
            $this->facility->website_url,
            $this->facility->building_structure,
        ];

        foreach ($facilityFields as $field) {
            if ($field && strpos($pageText, $field) !== false) {
                $data[] = $field;
            }
        }

        // Extract formatted dates
        if ($this->facility->opening_date) {
            $formattedDate = $this->facility->opening_date->format('Y年m月d日');
            if (strpos($pageText, $formattedDate) !== false) {
                $data[] = $formattedDate;
            }
        }

        // Extract numbers with units
        if ($this->facility->building_floors !== null) {
            $formattedFloors = number_format($this->facility->building_floors).'階';
            if (strpos($pageText, $formattedFloors) !== false) {
                $data[] = $formattedFloors;
            }
        }

        return array_unique($data);
    }

    /**
     * Assert data parity between two data sets
     */
    private function assertDataParity(array $firstData, array $secondData): void
    {
        foreach ($firstData as $dataPoint) {
            $this->assertContains(
                $dataPoint,
                $secondData,
                "Data point '{$dataPoint}' is missing in second view"
            );
        }

        // Verify essential fields are present in both
        $this->assertContains($this->facility->company_name, $firstData);
        $this->assertContains($this->facility->company_name, $secondData);

        $this->assertContains($this->facility->facility_name, $firstData);
        $this->assertContains($this->facility->facility_name, $secondData);
    }
}
