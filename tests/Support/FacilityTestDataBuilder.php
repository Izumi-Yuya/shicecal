<?php

namespace Tests\Support;

use App\Models\Facility;
use App\Models\FacilityService;
use Carbon\Carbon;

/**
 * Builder pattern for creating test facility data
 * Provides fluent interface for complex test scenarios
 */
class FacilityTestDataBuilder
{
    private array $facilityData = [];

    private array $services = [];

    private bool $withEmptyValues = false;

    private bool $withMixedData = false;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Create new builder instance
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Set facility as complete with all data
     */
    public function complete(): self
    {
        $this->facilityData = [
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

        return $this;
    }

    /**
     * Set facility with empty values
     */
    public function withEmptyValues(): self
    {
        $this->withEmptyValues = true;
        $this->facilityData = array_merge($this->getBaseData(), [
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

        return $this;
    }

    /**
     * Set facility with mixed data (some empty, some filled)
     */
    public function withMixedData(): self
    {
        $this->withMixedData = true;
        $this->facilityData = [
            'company_name' => 'ミックステスト株式会社',
            'office_code' => 'MIX001',
            'designation_number' => null,
            'facility_name' => 'ミックステスト施設',
            'postal_code' => '5678901',
            'address' => '大阪府大阪市テスト区5-6-7',
            'building_name' => null,
            'phone_number' => '06-5678-9012',
            'fax_number' => null,
            'toll_free_number' => '0800-567-890',
            'email' => null,
            'website_url' => 'https://mixed-test.com',
            'opening_date' => Carbon::parse('2018-03-20'),
            'years_in_operation' => null,
            'building_structure' => '木造',
            'building_floors' => 3,
            'paid_rooms_count' => null,
            'ss_rooms_count' => 5,
            'capacity' => 25,
            'status' => 'approved',
        ];

        return $this;
    }

    /**
     * Add standard services
     */
    public function withServices(): self
    {
        $this->services = [
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
        ];

        return $this;
    }

    /**
     * Add custom service
     */
    public function withService(string $type, ?Carbon $startDate = null, ?Carbon $endDate = null): self
    {
        $this->services[] = [
            'service_type' => $type,
            'renewal_start_date' => $startDate,
            'renewal_end_date' => $endDate,
        ];

        return $this;
    }

    /**
     * Override specific facility field
     */
    public function with(string $field, $value): self
    {
        $this->facilityData[$field] = $value;

        return $this;
    }

    /**
     * Build and create the facility
     */
    public function build(): Facility
    {
        $facility = Facility::factory()->create($this->facilityData);

        // Create services if specified
        foreach ($this->services as $serviceData) {
            FacilityService::factory()->create(
                array_merge(['facility_id' => $facility->id], $serviceData)
            );
        }

        return $facility->fresh(['services']);
    }

    /**
     * Get base facility data
     */
    private function getBaseData(): array
    {
        return [
            'company_name' => 'テスト株式会社',
            'office_code' => 'TEST002',
            'facility_name' => 'テスト施設2',
            'status' => 'approved',
        ];
    }

    /**
     * Reset builder to initial state
     */
    private function reset(): void
    {
        $this->facilityData = $this->getBaseData();
        $this->services = [];
        $this->withEmptyValues = false;
        $this->withMixedData = false;
    }
}
