<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityDataParityValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create([
            'company_name' => 'テスト会社株式会社',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'facility_name' => 'テスト施設名',
            'postal_code' => '1234567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストビル4F',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'toll_free_number' => '0120-123-456',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
            'opening_date' => '2020-01-15',
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60,
        ]);

        // Create test services
        FacilityService::create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護付有料老人ホーム',
            'renewal_start_date' => '2023-01-01',
            'renewal_end_date' => '2026-12-31',
        ]);

        FacilityService::create([
            'facility_id' => $this->facility->id,
            'service_type' => 'デイサービス',
            'renewal_start_date' => '2023-06-01',
            'renewal_end_date' => '2026-05-31',
        ]);
    }

    /** @test */
    public function it_displays_all_facility_basic_data_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view (default)
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Core facility data that must be present in both views
        $coreData = [
            'テスト会社株式会社',
            'TEST001',
            '1234567890',
            'テスト施設名',
            '123-4567', // formatted postal code
            '東京都渋谷区テスト1-2-3 テストビル4F', // full address
            '03-1234-5678',
            '03-1234-5679',
            '0120-123-456',
            'test@example.com',
            'https://example.com',
            '鉄筋コンクリート造',
            '50室',
            '10室',
            '60名',
            '4年',
            '5階',
        ];

        foreach ($coreData as $data) {
            $cardResponse->assertSee($data, false);
            $tableResponse->assertSee($data, false);
        }
    }

    /** @test */
    public function it_displays_service_information_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Service types should be present in both views
        $serviceTypes = ['介護付有料老人ホーム', 'デイサービス'];
        
        foreach ($serviceTypes as $serviceType) {
            $cardResponse->assertSee($serviceType, false);
            $tableResponse->assertSee($serviceType, false);
        }
    }

    /** @test */
    public function it_handles_empty_fields_consistently()
    {
        $this->actingAs($this->user);

        // Create facility with empty fields
        $emptyFacility = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'EMPTY001',
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

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $emptyFacility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $emptyFacility));
        $tableResponse->assertStatus(200);

        // Both views should handle empty fields gracefully
        $cardResponse->assertSee('未設定', false);
        $tableResponse->assertSee('未設定', false);
    }

    /** @test */
    public function it_maintains_data_completeness_when_switching_views()
    {
        $this->actingAs($this->user);

        // Get facility with services
        $facility = $this->facility->fresh(['services']);
        
        // Test card view first
        $cardResponse = $this->get(route('facilities.show', $facility));
        $cardResponse->assertStatus(200);

        // Switch to table view
        $this->post(route('facilities.set-view-mode'), ['view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $facility));
        $tableResponse->assertStatus(200);

        // Key identifiers that must be present in both views
        $keyIdentifiers = [
            $facility->company_name,
            $facility->facility_name,
            $facility->office_code,
        ];

        foreach ($keyIdentifiers as $identifier) {
            $cardResponse->assertSee($identifier, false);
            $tableResponse->assertSee($identifier, false);
        }

        // Service information should be present in both
        foreach ($facility->services as $service) {
            $cardResponse->assertSee($service->service_type, false);
            $tableResponse->assertSee($service->service_type, false);
        }
    }

    /** @test */
    public function it_validates_no_critical_data_loss_between_views()
    {
        $this->actingAs($this->user);

        // Create comprehensive facility
        $comprehensiveFacility = Facility::factory()->create([
            'company_name' => '総合テスト株式会社',
            'office_code' => 'COMP001',
            'designation_number' => '9876543210',
            'facility_name' => '総合テスト施設センター',
            'postal_code' => '1000001',
            'address' => '東京都千代田区千代田1-1-1',
            'building_name' => '千代田ビル10階',
            'phone_number' => '03-9999-8888',
            'fax_number' => '03-9999-8889',
            'toll_free_number' => '0120-999-888',
            'email' => 'comprehensive@test.co.jp',
            'website_url' => 'https://comprehensive-test.co.jp',
            'opening_date' => '2015-04-01',
            'years_in_operation' => 9,
            'building_structure' => '鉄骨鉄筋コンクリート造',
            'building_floors' => 12,
            'paid_rooms_count' => 100,
            'ss_rooms_count' => 25,
            'capacity' => 125,
        ]);

        // Add services
        $serviceTypes = ['介護付有料老人ホーム', 'デイサービス', 'ショートステイ'];
        foreach ($serviceTypes as $serviceType) {
            FacilityService::create([
                'facility_id' => $comprehensiveFacility->id,
                'service_type' => $serviceType,
                'renewal_start_date' => '2023-01-01',
                'renewal_end_date' => '2026-12-31',
            ]);
        }

        // Test both views
        $cardResponse = $this->get(route('facilities.show', $comprehensiveFacility));
        $cardResponse->assertStatus(200);

        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $comprehensiveFacility));
        $tableResponse->assertStatus(200);

        // Critical data points that must not be lost
        $criticalData = [
            '総合テスト株式会社',
            'COMP001',
            '9876543210',
            '総合テスト施設センター',
            '100-0001',
            '東京都千代田区千代田1-1-1 千代田ビル10階',
            '03-9999-8888',
            'comprehensive@test.co.jp',
            'https://comprehensive-test.co.jp',
            '鉄骨鉄筋コンクリート造',
            '100室',
            '25室',
            '125名',
            '9年',
            '12階',
        ];

        foreach ($criticalData as $data) {
            $cardResponse->assertSee($data, false);
            $tableResponse->assertSee($data, false);
        }

        // All service types should be present
        foreach ($serviceTypes as $serviceType) {
            $cardResponse->assertSee($serviceType, false);
            $tableResponse->assertSee($serviceType, false);
        }
    }

    /** @test */
    public function it_formats_numerical_data_with_units_consistently()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Numerical data with units
        $numericalFormats = [
            '50室',  // paid_rooms_count
            '10室',  // ss_rooms_count
            '60名',  // capacity
            '4年',   // years_in_operation
            '5階',   // building_floors
        ];

        foreach ($numericalFormats as $format) {
            $cardResponse->assertSee($format, false);
            $tableResponse->assertSee($format, false);
        }
    }

    /** @test */
    public function it_displays_contact_information_consistently()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Contact information
        $contactInfo = [
            'test@example.com',
            'https://example.com',
            '03-1234-5678',
            '03-1234-5679',
            '0120-123-456',
        ];

        foreach ($contactInfo as $info) {
            $cardResponse->assertSee($info, false);
            $tableResponse->assertSee($info, false);
        }
    }

    /** @test */
    public function it_preserves_office_code_badge_information()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        $this->withSession(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Office code should be present in both views
        $cardResponse->assertSee('TEST001', false);
        $tableResponse->assertSee('TEST001', false);
    }
}