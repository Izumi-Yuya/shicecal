<?php

namespace Tests\Support;

use App\Models\Facility;
use App\Models\FacilityService;
use Carbon\Carbon;

/**
 * Factory for creating test facility data
 * Implements Factory pattern to centralize test data creation
 */
class FacilityTestDataFactory
{
    /**
     * Create facility with complete data
     */
    public static function createComplete(): Facility
    {
        return Facility::factory()->create(self::getCompleteData());
    }

    /**
     * Create facility with empty values
     */
    public static function createWithEmptyValues(): Facility
    {
        return Facility::factory()->create(self::getEmptyData());
    }

    /**
     * Create facility with services
     */
    public static function createWithServices(): Facility
    {
        $facility = self::createComplete();
        
        foreach (self::getServiceConfigurations() as $config) {
            FacilityService::factory()->create(
                array_merge(['facility_id' => $facility->id], $config)
            );
        }
        
        return $facility->fresh(['services']);
    }

    /**
     * Create facility with mixed data (some empty, some filled)
     */
    public static function createWithMixedData(): Facility
    {
        return Facility::factory()->create(self::getMixedData());
    }

    /**
     * Get complete test data configuration
     */
    private static function getCompleteData(): array
    {
        return [
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
        ];
    }

    /**
     * Get empty test data configuration
     */
    private static function getEmptyData(): array
    {
        $baseData = [
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST002',
            'facility_name' => 'テスト施設2',
        ];

        $nullableFields = [
            'designation_number', 'postal_code', 'address', 'building_name',
            'phone_number', 'fax_number', 'toll_free_number', 'email',
            'website_url', 'opening_date', 'years_in_operation', 'building_structure',
            'building_floors', 'paid_rooms_count', 'ss_rooms_count', 'capacity'
        ];

        foreach ($nullableFields as $field) {
            $baseData[$field] = null;
        }

        return $baseData;
    }

    /**
     * Get mixed test data configuration
     */
    private static function getMixedData(): array
    {
        return [
            'company_name' => 'ミックステスト株式会社',
            'office_code' => 'MIX001',
            'designation_number' => null, // Empty
            'facility_name' => 'ミックステスト施設',
            'postal_code' => '5678901',
            'address' => '大阪府大阪市テスト区5-6-7',
            'building_name' => null, // Empty
            'phone_number' => '06-5678-9012',
            'fax_number' => null, // Empty
            'toll_free_number' => '0800-567-890',
            'email' => null, // Empty
            'website_url' => 'https://mixed-test.com',
            'opening_date' => Carbon::parse('2018-03-20'),
            'years_in_operation' => null, // Empty
            'building_structure' => '木造',
            'building_floors' => 3,
            'paid_rooms_count' => null, // Empty
            'ss_rooms_count' => 5,
            'capacity' => 25,
            'status' => 'approved',
        ];
    }

    /**
     * Get service configurations for testing
     */
    private static function getServiceConfigurations(): array
    {
        return [
            [
                'service_type' => '介護保険サービス',
                'renewal_start_date' => Carbon::parse('2023-04-01'),
                'renewal_end_date' => Carbon::parse('2029-03-31'),
            ],
            [
                'service_type' => '障害福祉サービス',
                'renewal_start_date' => Carbon::parse('2022-10-01'),
                'renewal_end_date' => Carbon::parse('2028-09-30'),
            ],
            [
                'service_type' => '地域密着型サービス',
                'renewal_start_date' => null,
                'renewal_end_date' => null,
            ],
        ];
    }
}