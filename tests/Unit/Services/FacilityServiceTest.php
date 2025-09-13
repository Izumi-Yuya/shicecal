<?php

namespace Tests\Unit\Services;

use App\Exceptions\FacilityServiceException;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use App\Services\FacilityService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FacilityService $service;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationService::class);
        $this->service = new FacilityService($this->notificationService);

        // Create a test user for system settings
        $testUser = User::factory()->create();

        // Set up system setting for approval disabled by default
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'approval_enabled'],
            ['value' => 'false', 'description' => 'Test setting', 'updated_by' => $testUser->id]
        );
    }

    // ========================================
    // Basic Facility Operations Tests
    // ========================================

    /** @test */
    public function it_can_create_a_facility()
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
            'office_code' => 'TC001',
            'address' => 'Test Address',
            'phone_number' => '03-1234-5678',
        ];

        // Act
        $result = $this->service->createFacility($data, $user);

        // Assert
        $this->assertInstanceOf(Facility::class, $result);
        $this->assertEquals('Test Facility', $result->facility_name);
        $this->assertEquals('Test Company', $result->company_name);
        $this->assertEquals($user->id, $result->created_by);
        $this->assertEquals($user->id, $result->updated_by);
        $this->assertDatabaseHas('facilities', [
            'facility_name' => 'Test Facility',
            'company_name' => 'Test Company',
            'created_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_update_a_facility()
    {
        // Arrange
        $user = User::factory()->create();
        $facility = Facility::factory()->create([
            'facility_name' => 'Original Name',
            'company_name' => 'Original Company',
        ]);

        $data = [
            'facility_name' => 'Updated Name',
            'company_name' => 'Updated Company',
            'address' => 'Updated Address',
        ];

        // Act
        $result = $this->service->updateFacility($facility->id, $data, $user);

        // Assert
        $this->assertInstanceOf(Facility::class, $result);
        $this->assertEquals('Updated Name', $result->facility_name);
        $this->assertEquals('Updated Company', $result->company_name);
        $this->assertEquals('Updated Address', $result->address);
        $this->assertEquals($user->id, $result->updated_by);
        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'facility_name' => 'Updated Name',
            'company_name' => 'Updated Company',
        ]);
    }

    /** @test */
    public function it_can_delete_a_facility()
    {
        // Arrange
        $user = User::factory()->create();
        $facility = Facility::factory()->create();

        // Act
        $result = $this->service->deleteFacility($facility->id, $user);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('facilities', ['id' => $facility->id]);
    }

    /** @test */
    public function it_can_get_facility_with_permissions()
    {
        // Arrange
        $user = User::factory()->create();
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create(['facility_id' => $facility->id]);

        // Act
        $result = $this->service->getFacilityWithPermissions($facility->id, $user);

        // Assert
        $this->assertInstanceOf(Facility::class, $result);
        $this->assertEquals($facility->id, $result->id);
        $this->assertTrue($result->relationLoaded('landInfo'));
    }

    /** @test */
    public function it_throws_exception_when_facility_not_found()
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->expectException(FacilityServiceException::class);
        $this->expectExceptionMessage('施設の取得に失敗しました。');

        $this->service->getFacilityWithPermissions(999, $user);
    }

    // ========================================
    // Land Information Operations Tests
    // ========================================

    /** @test */
    public function it_can_get_land_info_with_caching()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create(['facility_id' => $facility->id]);

        // Clear any existing cache
        Cache::forget("land_info.facility.{$facility->id}");

        // Act
        $result1 = $this->service->getLandInfo($facility);
        $result2 = $this->service->getLandInfo($facility); // Should come from cache

        // Assert
        $this->assertInstanceOf(LandInfo::class, $result1);
        $this->assertEquals($landInfo->id, $result1->id);
        $this->assertEquals($landInfo->id, $result2->id);

        // Verify cache was used
        $this->assertTrue(Cache::has("land_info.facility.{$facility->id}"));
    }

    /** @test */
    public function it_returns_null_when_no_land_info_exists()
    {
        // Arrange
        $facility = Facility::factory()->create();
        
        // Ensure no land info exists by deleting any that might have been created
        LandInfo::where('facility_id', $facility->id)->delete();
        
        // Clear cache to ensure fresh query
        Cache::forget("land_info.facility.{$facility->id}");

        // Act
        $result = $this->service->getLandInfo($facility);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_create_land_info_with_approval_disabled()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $data = [
            'ownership_type' => 'owned',
            'site_area_sqm' => '290.50',
            'site_area_tsubo' => '87.85',
            'purchase_price' => '10000000',
            'parking_spaces' => '5',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertInstanceOf(LandInfo::class, $result);
        $this->assertEquals('owned', $result->ownership_type);
        $this->assertEquals('approved', $result->status);
        $this->assertEquals($user->id, $result->created_by);
        $this->assertEquals($user->id, $result->updated_by);
        $this->assertNotNull($result->approved_at);

        // Verify automatic calculation
        $this->assertNotNull($result->unit_price_per_tsubo);
        $this->assertEquals(113830, $result->unit_price_per_tsubo); // 10000000 / 87.85 rounded
    }

    /** @test */
    public function it_can_update_existing_land_info_with_approval_disabled()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 5000000,
        ]);

        $data = [
            'ownership_type' => 'owned',
            'purchase_price' => '15000000',
            'site_area_tsubo' => '100.00',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals($landInfo->id, $result->id);
        $this->assertEquals(15000000, $result->purchase_price);
        $this->assertEquals(150000, $result->unit_price_per_tsubo);
        $this->assertEquals('approved', $result->status);
        $this->assertEquals($user->id, $result->updated_by);
    }

    /** @test */
    public function it_handles_approval_workflow_when_enabled()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();
        $approver = User::factory()->create(['role' => 'approver']);

        // Enable approval
        DB::table('system_settings')
            ->where('key', 'approval_enabled')
            ->update(['value' => 'true']);

        $data = [
            'ownership_type' => 'leased',
            'monthly_rent' => '500000',
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-01-01',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals('pending_approval', $result->status);
        $this->assertNull($result->approved_at);
        $this->assertNull($result->approved_by);
        $this->assertEquals($user->id, $result->updated_by);
        $this->assertEquals('5年', $result->contract_period_text);
    }

    /** @test */
    public function it_can_approve_land_info()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $approver = User::factory()->create(['role' => 'approver']);
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
        ]);

        // Act
        $result = $this->service->approveLandInfo($landInfo, $approver);

        // Assert
        $this->assertEquals('approved', $result->status);
        $this->assertEquals($approver->id, $result->approved_by);
        $this->assertNotNull($result->approved_at);
    }

    /** @test */
    public function it_can_reject_land_info()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $approver = User::factory()->create(['role' => 'approver']);
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'status' => 'pending_approval',
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        $reason = 'データに不備があります';

        // Act
        $result = $this->service->rejectLandInfo($landInfo, $approver, $reason);

        // Assert
        $this->assertEquals('draft', $result->status);
        $this->assertNull($result->approved_by);
        $this->assertNull($result->approved_at);
    }

    // ========================================
    // Calculation Methods Tests
    // ========================================

    /** @test */
    public function it_calculates_unit_price_correctly()
    {
        // Test normal calculation
        $unitPrice = $this->service->calculateUnitPrice(10000000, 100.0);
        $this->assertEquals(100000, $unitPrice);

        // Test with decimal values
        $unitPrice = $this->service->calculateUnitPrice(15000000, 89.05);
        $this->assertEquals(168445, $unitPrice); // Rounded

        // Test with small area
        $unitPrice = $this->service->calculateUnitPrice(5000000, 25.5);
        $this->assertEquals(196078, $unitPrice);
    }

    /** @test */
    public function it_returns_null_for_invalid_unit_price_inputs()
    {
        // Zero purchase price
        $this->assertNull($this->service->calculateUnitPrice(0, 100.0));

        // Negative purchase price
        $this->assertNull($this->service->calculateUnitPrice(-1000000, 100.0));

        // Zero area
        $this->assertNull($this->service->calculateUnitPrice(10000000, 0));

        // Negative area
        $this->assertNull($this->service->calculateUnitPrice(10000000, -50.0));
    }

    /** @test */
    public function it_calculates_contract_period_correctly()
    {
        // Test exact years
        $period = $this->service->calculateContractPeriod('2020-01-01', '2025-01-01');
        $this->assertEquals('5年', $period);

        // Test years and months
        $period = $this->service->calculateContractPeriod('2020-01-01', '2025-06-01');
        $this->assertEquals('5年5ヶ月', $period);

        // Test only months
        $period = $this->service->calculateContractPeriod('2020-01-01', '2020-07-01');
        $this->assertEquals('6ヶ月', $period);

        // Test less than a month
        $period = $this->service->calculateContractPeriod('2020-01-01', '2020-01-15');
        $this->assertEquals('0ヶ月', $period);

        // Test complex period
        $period = $this->service->calculateContractPeriod('2020-03-15', '2023-11-20');
        $this->assertEquals('3年8ヶ月', $period);
    }

    /** @test */
    public function it_returns_empty_string_for_invalid_contract_period_inputs()
    {
        // End date before start date
        $this->assertEquals('', $this->service->calculateContractPeriod('2025-01-01', '2020-01-01'));

        // Same dates
        $this->assertEquals('', $this->service->calculateContractPeriod('2020-01-01', '2020-01-01'));

        // Invalid date format
        $this->assertEquals('', $this->service->calculateContractPeriod('invalid-date', '2025-01-01'));
        $this->assertEquals('', $this->service->calculateContractPeriod('2020-01-01', 'invalid-date'));
    }

    /** @test */
    public function it_formats_currency_with_commas()
    {
        // Test large numbers
        $this->assertEquals('10,000,000', $this->service->formatCurrency(10000000));
        $this->assertEquals('1,234,567', $this->service->formatCurrency(1234567));
        $this->assertEquals('500,000', $this->service->formatCurrency(500000));

        // Test small numbers
        $this->assertEquals('1,000', $this->service->formatCurrency(1000));
        $this->assertEquals('100', $this->service->formatCurrency(100));
        $this->assertEquals('1', $this->service->formatCurrency(1));

        // Test zero
        $this->assertEquals('', $this->service->formatCurrency(0));

        // Test decimal (should be rounded to integer)
        $this->assertEquals('1,235', $this->service->formatCurrency(1234.56));
    }

    /** @test */
    public function it_formats_area_with_units()
    {
        // Test sqm formatting
        $this->assertEquals('290.00㎡', $this->service->formatArea(290.0, 'sqm'));
        $this->assertEquals('89.05㎡', $this->service->formatArea(89.05, 'sqm'));
        $this->assertEquals('1,234.56㎡', $this->service->formatArea(1234.56, 'sqm'));

        // Test tsubo formatting
        $this->assertEquals('89.05坪', $this->service->formatArea(89.05, 'tsubo'));
        $this->assertEquals('100.00坪', $this->service->formatArea(100.0, 'tsubo'));
        $this->assertEquals('1,234.56坪', $this->service->formatArea(1234.56, 'tsubo'));

        // Test zero area
        $this->assertEquals('', $this->service->formatArea(0, 'sqm'));
        $this->assertEquals('', $this->service->formatArea(0, 'tsubo'));
    }

    /** @test */
    public function it_throws_exception_for_invalid_area_unit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid unit: invalid. Use 'sqm' or 'tsubo'.");

        $this->service->formatArea(100.0, 'invalid');
    }

    // ========================================
    // Data Formatting Tests
    // ========================================

    /** @test */
    public function it_formats_display_data_correctly()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000,
            'site_area_sqm' => 290.50,
            'site_area_tsubo' => 87.85,
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-01-01',
        ]);

        // Act
        $result = $this->service->formatDisplayData($landInfo);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($landInfo->id, $result['id']);
        $this->assertEquals('owned', $result['ownership_type']);
        $this->assertEquals(10000000, $result['purchase_price']);
        $this->assertEquals('10,000,000', $result['formatted_purchase_price']);
        $this->assertEquals('290.50㎡', $result['formatted_site_area_sqm']);
        $this->assertEquals('87.85坪', $result['formatted_site_area_tsubo']);
        $this->assertEquals('2024-01-01', $result['contract_start_date']);
        $this->assertEquals('2024年1月1日', $result['japanese_contract_start_date']);
    }

    /** @test */
    public function it_gets_formatted_land_info_with_cache()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000,
        ]);

        // Clear any existing cache
        Cache::forget("land_info.formatted.{$facility->id}");

        // Act
        $result1 = $this->service->getFormattedLandInfoWithCache($facility);
        $result2 = $this->service->getFormattedLandInfoWithCache($facility); // Should come from cache

        // Assert
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertEquals($result1, $result2);
        $this->assertTrue(Cache::has("land_info.formatted.{$facility->id}"));
    }

    /** @test */
    public function it_gets_export_data_with_cache()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create([
            'facility_id' => $facility->id,
            'ownership_type' => 'owned',
            'purchase_price' => 10000000,
            'site_area_sqm' => 290.50,
        ]);

        // Clear any existing cache
        Cache::forget("land_info.export_data.{$facility->id}");

        // Act
        $result = $this->service->getExportDataWithCache($facility);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('owned', $result['land_ownership_type']);
        $this->assertEquals(10000000, $result['land_purchase_price']);
        $this->assertEquals(290.50, $result['land_site_area_sqm']);
        $this->assertTrue(Cache::has("land_info.export_data.{$facility->id}"));
    }

    // ========================================
    // Bulk Operations Tests
    // ========================================

    /** @test */
    public function it_gets_bulk_land_info_with_caching()
    {
        // Arrange
        $facilities = Facility::factory()->count(3)->create();
        $facilityIds = $facilities->pluck('id')->toArray();

        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        // Act
        $result = $this->service->getBulkLandInfo($facilityIds);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        foreach ($facilityIds as $facilityId) {
            $this->assertArrayHasKey($facilityId, $result);
        }
    }

    /** @test */
    public function it_warms_up_cache_for_multiple_facilities()
    {
        // Arrange
        $facilities = Facility::factory()->count(2)->create();
        $facilityIds = $facilities->pluck('id')->toArray();

        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        // Clear existing cache
        foreach ($facilityIds as $facilityId) {
            Cache::forget("land_info.facility.{$facilityId}");
            Cache::forget("land_info.formatted.{$facilityId}");
        }

        // Act
        $this->service->warmUpCache($facilityIds);

        // Assert
        foreach ($facilityIds as $facilityId) {
            $this->assertTrue(Cache::has("land_info.facility.{$facilityId}"));
            $this->assertTrue(Cache::has("land_info.formatted.{$facilityId}"));
        }
    }

    // ========================================
    // Cache Management Tests
    // ========================================

    /** @test */
    public function it_clears_facility_cache()
    {
        // Arrange
        $facility = Facility::factory()->create();
        Cache::put("facility.{$facility->id}", 'test_data', 3600);
        Cache::put("land_info.facility.{$facility->id}", 'test_data', 3600);

        // Act
        $this->service->clearFacilityCache($facility);

        // Assert
        $this->assertFalse(Cache::has("facility.{$facility->id}"));
        $this->assertFalse(Cache::has("land_info.facility.{$facility->id}"));
    }

    /** @test */
    public function it_clears_land_info_cache()
    {
        // Arrange
        $facility = Facility::factory()->create();
        Cache::put("land_info.facility.{$facility->id}", 'test_data', 3600);
        Cache::put("land_info.formatted.{$facility->id}", 'test_data', 3600);
        Cache::put("land_info.export_data.{$facility->id}", 'test_data', 3600);

        // Act
        $this->service->clearLandInfoCache($facility);

        // Assert
        $this->assertFalse(Cache::has("land_info.facility.{$facility->id}"));
        $this->assertFalse(Cache::has("land_info.formatted.{$facility->id}"));
        $this->assertFalse(Cache::has("land_info.export_data.{$facility->id}"));
    }

    // ========================================
    // Data Sanitization Tests
    // ========================================

    /** @test */
    public function it_sanitizes_input_data_correctly()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $data = [
            'ownership_type' => 'owned',
            'parking_spaces' => '１０', // Full-width number
            'site_area_sqm' => '290.50', // Use regular numbers for now
            'management_company_phone' => '03-1234-5678',
            'management_company_postal_code' => '1234567', // Without hyphen
            'notes' => '<script>alert("test")</script>Normal text', // HTML tags
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals(10, $result->parking_spaces);
        $this->assertEquals(290.50, $result->site_area_sqm);
        $this->assertEquals('03-1234-5678', $result->management_company_phone);
        $this->assertEquals('123-4567', $result->management_company_postal_code);
        $this->assertEquals('alert("test")Normal text', $result->notes); // HTML tags stripped but content remains
    }

    /** @test */
    public function it_performs_automatic_calculations_for_owned_property()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $data = [
            'ownership_type' => 'owned',
            'purchase_price' => '10000000',
            'site_area_tsubo' => '100.00',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals(100000, $result->unit_price_per_tsubo);
    }

    /** @test */
    public function it_performs_automatic_calculations_for_leased_property()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        $data = [
            'ownership_type' => 'leased',
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-06-01',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals('5年5ヶ月', $result->contract_period_text);
    }

    /** @test */
    public function it_validates_ownership_type_for_calculations()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        // Test owned_rental type should calculate contract period
        $data = [
            'ownership_type' => 'owned_rental',
            'contract_start_date' => '2024-01-01',
            'contract_end_date' => '2029-01-01',
        ];

        // Act
        $result = $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertEquals('5年', $result->contract_period_text);
    }

    /** @test */
    public function it_clears_cache_when_updating_land_info()
    {
        // Arrange
        $facility = Facility::factory()->create();
        $user = User::factory()->create();

        // Set up cache
        Cache::put("land_info.facility.{$facility->id}", 'cached_data', 3600);
        $this->assertTrue(Cache::has("land_info.facility.{$facility->id}"));

        $data = [
            'ownership_type' => 'owned',
            'purchase_price' => '10000000',
        ];

        // Act
        $this->service->createOrUpdateLandInfo($facility, $data, $user);

        // Assert
        $this->assertFalse(Cache::has("land_info.facility.{$facility->id}"));
    }

    // ========================================
    // Error Handling Tests
    // ========================================

    /** @test */
    public function it_throws_exception_when_facility_creation_fails()
    {
        // Arrange
        $user = User::factory()->create();
        $data = []; // Invalid data that will cause creation to fail

        // Act & Assert
        $this->expectException(FacilityServiceException::class);
        $this->expectExceptionMessage('施設の作成に失敗しました。');

        $this->service->createFacility($data, $user);
    }

    /** @test */
    public function it_throws_exception_when_facility_update_fails()
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->expectException(FacilityServiceException::class);
        $this->expectExceptionMessage('施設の更新に失敗しました。');

        $this->service->updateFacility(999, [], $user); // Non-existent facility
    }

    /** @test */
    public function it_throws_exception_when_land_info_approval_fails()
    {
        // Arrange
        $approver = User::factory()->create(['role' => 'approver']);
        $landInfo = new LandInfo; // Invalid land info without facility

        // Act & Assert
        $this->expectException(FacilityServiceException::class);
        $this->expectExceptionMessage('土地情報の承認に失敗しました。');

        $this->service->approveLandInfo($landInfo, $approver);
    }
}
