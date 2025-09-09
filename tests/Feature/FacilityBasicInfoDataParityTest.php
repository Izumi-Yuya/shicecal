<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityBasicInfoDataParityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create([
            'company_name' => 'テスト会社',
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
            'opening_date' => '2020-01-15',
            'years_in_operation' => 4,
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => 5,
            'paid_rooms_count' => 50,
            'ss_rooms_count' => 10,
            'capacity' => 60,
        ]);

        // Create test services
        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => '介護付有料老人ホーム',
            'renewal_start_date' => '2023-01-01',
            'renewal_end_date' => '2026-12-31',
        ]);

        FacilityService::factory()->create([
            'facility_id' => $this->facility->id,
            'service_type' => 'デイサービス',
            'renewal_start_date' => '2023-06-01',
            'renewal_end_date' => '2026-05-31',
        ]);
    }

    /** @test */
    public function it_displays_all_basic_info_fields_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Define all fields that should be present in both views
        $basicInfoFields = [
            'company_name' => 'テスト会社',
            'office_code' => 'TEST001',
            'designation_number' => '1234567890',
            'facility_name' => 'テスト施設',
        ];

        $contactFields = [
            'formatted_postal_code' => '123-4567',
            'full_address' => '東京都渋谷区テスト1-2-3 テストビル4F',
            'phone_number' => '03-1234-5678',
            'fax_number' => '03-1234-5679',
            'toll_free_number' => '0120-123-456',
            'email' => 'test@example.com',
            'website_url' => 'https://example.com',
        ];

        $buildingFields = [
            'opening_date' => '2020年01月15日',
            'years_in_operation' => '4年',
            'building_structure' => '鉄筋コンクリート造',
            'building_floors' => '5階',
        ];

        $facilityFields = [
            'paid_rooms_count' => '50室',
            'ss_rooms_count' => '10室',
            'capacity' => '60名',
        ];

        // Check all fields are present in both views
        foreach ($basicInfoFields as $field => $value) {
            $cardResponse->assertSee($value);
            $tableResponse->assertSee($value);
        }

        foreach ($contactFields as $field => $value) {
            $cardResponse->assertSee($value);
            $tableResponse->assertSee($value);
        }

        foreach ($buildingFields as $field => $value) {
            $cardResponse->assertSee($value);
            $tableResponse->assertSee($value);
        }

        foreach ($facilityFields as $field => $value) {
            $cardResponse->assertSee($value);
            $tableResponse->assertSee($value);
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
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Check service types are present in both views
        $cardResponse->assertSee('介護付有料老人ホーム');
        $cardResponse->assertSee('デイサービス');
        $tableResponse->assertSee('介護付有料老人ホーム');
        $tableResponse->assertSee('デイサービス');

        // Check service dates are formatted correctly in both views
        $cardResponse->assertSee('2023年01月01日');
        $cardResponse->assertSee('2026年12月31日');
        $cardResponse->assertSee('2023年06月01日');
        $cardResponse->assertSee('2026年05月31日');

        $tableResponse->assertSee('2023年01月01日');
        $tableResponse->assertSee('2026年12月31日');
        $tableResponse->assertSee('2023年06月01日');
        $tableResponse->assertSee('2026年05月31日');
    }

    /** @test */
    public function it_handles_empty_values_consistently_in_both_views()
    {
        $this->actingAs($this->user);

        // Create facility with some empty fields
        $facilityWithEmptyFields = Facility::factory()->create([
            'company_name' => 'テスト会社',
            'facility_name' => 'テスト施設',
            'office_code' => 'TEST002',
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
        $cardResponse = $this->get(route('facilities.show', $facilityWithEmptyFields));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $facilityWithEmptyFields));
        $tableResponse->assertStatus(200);

        // Count occurrences of "未設定" in both views
        $cardContent = $cardResponse->getContent();
        $tableContent = $tableResponse->getContent();

        $cardEmptyCount = substr_count($cardContent, '未設定');
        $tableEmptyCount = substr_count($tableContent, '未設定');

        // Both views should show the same number of "未設定" entries
        $this->assertEquals($cardEmptyCount, $tableEmptyCount, 
            'Both views should display the same number of empty field indicators');

        // Verify specific empty fields are handled consistently
        $cardResponse->assertSee('未設定');
        $tableResponse->assertSee('未設定');
    }

    /** @test */
    public function it_formats_dates_consistently_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Check Japanese date format (Y年m月d日) is used in both views
        $expectedDateFormat = '2020年01月15日';
        $cardResponse->assertSee($expectedDateFormat);
        $tableResponse->assertSee($expectedDateFormat);

        // Check service dates are also formatted consistently
        $expectedServiceDate1 = '2023年01月01日';
        $expectedServiceDate2 = '2026年12月31日';
        
        $cardResponse->assertSee($expectedServiceDate1);
        $cardResponse->assertSee($expectedServiceDate2);
        $tableResponse->assertSee($expectedServiceDate1);
        $tableResponse->assertSee($expectedServiceDate2);
    }

    /** @test */
    public function it_formats_numbers_with_units_consistently_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Check number formatting with units
        $numberFormats = [
            '50室',  // paid_rooms_count
            '10室',  // ss_rooms_count
            '60名',  // capacity
            '4年',   // years_in_operation
            '5階',   // building_floors
        ];

        foreach ($numberFormats as $format) {
            $cardResponse->assertSee($format);
            $tableResponse->assertSee($format);
        }
    }

    /** @test */
    public function it_formats_links_consistently_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Check email links
        $cardResponse->assertSee('mailto:test@example.com');
        $tableResponse->assertSee('mailto:test@example.com');

        // Check website links
        $cardResponse->assertSee('https://example.com');
        $tableResponse->assertSee('https://example.com');
        
        // Check that external links have target="_blank"
        $cardResponse->assertSee('target="_blank"');
        $tableResponse->assertSee('target="_blank"');
    }

    /** @test */
    public function it_displays_badges_consistently_in_both_views()
    {
        $this->actingAs($this->user);

        // Test card view
        $cardResponse = $this->get(route('facilities.show', $this->facility));
        $cardResponse->assertStatus(200);

        // Test table view
        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $this->facility));
        $tableResponse->assertStatus(200);

        // Check office code badge
        $cardResponse->assertSee('TEST001');
        $tableResponse->assertSee('TEST001');

        // Check that badge classes are present in both views
        $cardContent = $cardResponse->getContent();
        $tableContent = $tableResponse->getContent();

        // Office code should be displayed as badge in card view
        $this->assertStringContainsString('badge bg-primary', $cardContent);
        
        // In table view, office code might not be in a badge but should still be present
        $this->assertStringContainsString('TEST001', $tableContent);
    }

    /** @test */
    public function it_maintains_data_completeness_when_switching_views()
    {
        $this->actingAs($this->user);

        // Get all facility data
        $facility = $this->facility->fresh(['services']);
        
        // Test switching from card to table view
        $this->withSession(['facility_basic_info_view_mode' => 'card']);
        $cardResponse = $this->get(route('facilities.show', $facility));
        $cardResponse->assertStatus(200);

        // Switch to table view
        $this->post(route('facilities.set-view-mode'), ['view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $facility));
        $tableResponse->assertStatus(200);

        // Extract all text content from both responses
        $cardContent = strip_tags($cardResponse->getContent());
        $tableContent = strip_tags($tableResponse->getContent());

        // Key data points that must be present in both views
        $criticalData = [
            $facility->company_name,
            $facility->facility_name,
            $facility->office_code,
            $facility->phone_number,
            $facility->email,
            $facility->website_url,
            '2020年01月15日', // formatted opening_date
            '50室', // formatted paid_rooms_count
            '60名', // formatted capacity
        ];

        foreach ($criticalData as $data) {
            $this->assertStringContainsString($data, $cardContent, 
                "Card view should contain: {$data}");
            $this->assertStringContainsString($data, $tableContent, 
                "Table view should contain: {$data}");
        }

        // Check service information is present in both
        foreach ($facility->services as $service) {
            $this->assertStringContainsString($service->service_type, $cardContent);
            $this->assertStringContainsString($service->service_type, $tableContent);
        }
    }

    /** @test */
    public function it_validates_no_data_loss_between_view_modes()
    {
        $this->actingAs($this->user);

        // Create a comprehensive facility with all possible data
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

        // Add multiple services
        $serviceTypes = [
            '介護付有料老人ホーム',
            'デイサービス',
            'ショートステイ',
            '訪問介護'
        ];

        foreach ($serviceTypes as $index => $serviceType) {
            FacilityService::factory()->create([
                'facility_id' => $comprehensiveFacility->id,
                'service_type' => $serviceType,
                'renewal_start_date' => '2023-' . sprintf('%02d', $index + 1) . '-01',
                'renewal_end_date' => '2026-' . sprintf('%02d', $index + 1) . '-28',
            ]);
        }

        // Test both views
        $cardResponse = $this->get(route('facilities.show', $comprehensiveFacility));
        $cardResponse->assertStatus(200);

        session(['facility_basic_info_view_mode' => 'table']);
        $tableResponse = $this->get(route('facilities.show', $comprehensiveFacility));
        $tableResponse->assertStatus(200);

        // Extract and compare data presence
        $cardContent = $cardResponse->getContent();
        $tableContent = $tableResponse->getContent();

        // All service types should be present in both views
        foreach ($serviceTypes as $serviceType) {
            $this->assertStringContainsString($serviceType, $cardContent);
            $this->assertStringContainsString($serviceType, $tableContent);
        }

        // All facility data should be present in both views
        $allFacilityData = [
            '総合テスト株式会社',
            'COMP001',
            '9876543210',
            '総合テスト施設センター',
            '100-0001',
            '東京都千代田区千代田1-1-1 千代田ビル10階',
            '03-9999-8888',
            '03-9999-8889',
            '0120-999-888',
            'comprehensive@test.co.jp',
            'https://comprehensive-test.co.jp',
            '2015年04月01日',
            '9年',
            '鉄骨鉄筋コンクリート造',
            '12階',
            '100室',
            '25室',
            '125名',
        ];

        foreach ($allFacilityData as $data) {
            $this->assertStringContainsString($data, $cardContent, 
                "Card view missing: {$data}");
            $this->assertStringContainsString($data, $tableContent, 
                "Table view missing: {$data}");
        }
    }
}