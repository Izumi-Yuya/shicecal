<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LandInfoService;
use App\Services\LandCalculationService;
use App\Services\NotificationService;
use App\Models\LandInfo;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandInfoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LandInfoService $service;
    protected LandCalculationService $calculationService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculationService = app(LandCalculationService::class);
        $this->notificationService = app(NotificationService::class);

        $this->service = new LandInfoService(
            $this->calculationService,
            $this->notificationService
        );

        // Create a test user for system settings
        $testUser = User::factory()->create();

        // Set up system setting for approval disabled by default
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'approval_enabled'],
            ['value' => 'false', 'description' => 'Test setting', 'updated_by' => $testUser->id]
        );
    }

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
}
