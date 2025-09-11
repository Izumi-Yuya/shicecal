<?php

namespace Tests\Traits;

use App\Models\Facility;
use App\Models\FacilityComment;
use App\Models\LandInfo;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

trait CreatesTestFacilities
{
    /**
     * Create a basic facility
     */
    protected function createFacility(array $attributes = []): Facility
    {
        return Facility::factory()->create($attributes);
    }

    /**
     * Create an approved facility
     */
    protected function createApprovedFacility(array $attributes = []): Facility
    {
        return Facility::factory()->approved()->create($attributes);
    }

    /**
     * Create a draft facility
     */
    protected function createDraftFacility(array $attributes = []): Facility
    {
        return Facility::factory()->draft()->create($attributes);
    }

    /**
     * Create a facility pending approval
     */
    protected function createPendingFacility(array $attributes = []): Facility
    {
        return Facility::factory()->pendingApproval()->create($attributes);
    }

    /**
     * Create a facility with land information
     *
     * @return array [Facility, LandInfo]
     */
    protected function createFacilityWithLandInfo(array $facilityData = [], array $landData = []): array
    {
        $facility = $this->createFacility($facilityData);
        $landInfo = LandInfo::factory()->for($facility)->create($landData);

        return [$facility, $landInfo];
    }

    /**
     * Create an approved facility with land information
     *
     * @return array [Facility, LandInfo]
     */
    protected function createApprovedFacilityWithLandInfo(array $facilityData = [], array $landData = []): array
    {
        $facility = $this->createApprovedFacility($facilityData);
        $landInfo = LandInfo::factory()->for($facility)->create($landData);

        return [$facility, $landInfo];
    }

    /**
     * Create a facility with complete information
     *
     * @return array [Facility, LandInfo]
     */
    protected function createCompleteFacility(array $facilityData = [], array $landData = []): array
    {
        $defaultFacilityData = [
            'company_name' => 'テスト株式会社',
            'facility_name' => 'テスト施設',
            'address' => '東京都渋谷区テスト1-1-1',
            'phone_number' => '03-1234-5678',
            'status' => 'approved',
        ];

        $defaultLandData = [
            'ownership_type' => 'owned',
            'purchase_price' => 50000000,
            'site_area_sqm' => 1000.50,
            'site_area_tsubo' => 302.50,
            'unit_price_per_tsubo' => 165289,
        ];

        return $this->createFacilityWithLandInfo(
            array_merge($defaultFacilityData, $facilityData),
            array_merge($defaultLandData, $landData)
        );
    }

    /**
     * Create multiple facilities
     */
    protected function createFacilities(int $count, array $attributes = []): Collection
    {
        return Facility::factory()->count($count)->create($attributes);
    }

    /**
     * Create facilities with different statuses
     */
    protected function createFacilitiesWithStatuses(array $statuses, array $commonAttributes = []): Collection
    {
        $facilities = collect();

        foreach ($statuses as $status) {
            $facility = match ($status) {
                'approved' => $this->createApprovedFacility($commonAttributes),
                'draft' => $this->createDraftFacility($commonAttributes),
                'pending_approval' => $this->createPendingFacility($commonAttributes),
                default => $this->createFacility(array_merge(['status' => $status], $commonAttributes))
            };

            $facilities->push($facility);
        }

        return $facilities;
    }

    /**
     * Create a facility with comments
     */
    protected function createFacilityWithComments(array $facilityData = [], int $commentCount = 3, array $commentData = []): Facility
    {
        $facility = $this->createFacility($facilityData);

        FacilityComment::factory()
            ->count($commentCount)
            ->for($facility)
            ->create($commentData);

        return $facility;
    }

    /**
     * Create a facility with maintenance history
     */
    protected function createFacilityWithMaintenanceHistory(array $facilityData = [], int $historyCount = 2, array $historyData = []): Facility
    {
        $facility = $this->createFacility($facilityData);

        MaintenanceHistory::factory()
            ->count($historyCount)
            ->for($facility)
            ->create($historyData);

        return $facility;
    }

    /**
     * Create a facility owned by specific user
     */
    protected function createFacilityOwnedBy(User $user, array $attributes = []): Facility
    {
        return $this->createFacility(array_merge([
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $attributes));
    }

    /**
     * Create a facility approved by specific user
     */
    protected function createFacilityApprovedBy(User $approver, array $attributes = []): Facility
    {
        return $this->createApprovedFacility(array_merge([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ], $attributes));
    }

    /**
     * Create facilities for testing pagination
     */
    protected function createFacilitiesForPagination(int $count = 25, array $attributes = []): Collection
    {
        return $this->createFacilities($count, $attributes);
    }

    /**
     * Create facilities with specific service types
     */
    protected function createFacilityWithServiceTypes(array $serviceTypes, array $attributes = []): Facility
    {
        return $this->createFacility(array_merge([
            'service_types' => $serviceTypes,
        ], $attributes));
    }

    /**
     * Create a facility with all relationships
     */
    protected function createFacilityWithAllRelationships(array $facilityData = [], array $options = []): Facility
    {
        $options = array_merge([
            'land_info' => true,
            'comments' => 2,
            'maintenance_history' => 1,
        ], $options);

        $facility = $this->createFacility($facilityData);

        if ($options['land_info']) {
            LandInfo::factory()->for($facility)->create();
        }

        if ($options['comments'] > 0) {
            FacilityComment::factory()
                ->count($options['comments'])
                ->for($facility)
                ->create();
        }

        if ($options['maintenance_history'] > 0) {
            MaintenanceHistory::factory()
                ->count($options['maintenance_history'])
                ->for($facility)
                ->create();
        }

        return $facility->fresh(['landInfo', 'comments', 'maintenanceHistories']);
    }

    /**
     * Create test data set for comprehensive testing
     */
    protected function createTestDataSet(): array
    {
        return [
            'approved_facility' => $this->createApprovedFacility(),
            'draft_facility' => $this->createDraftFacility(),
            'pending_facility' => $this->createPendingFacility(),
            'facility_with_land' => $this->createFacilityWithLandInfo()[0],
            'complete_facility' => $this->createCompleteFacility()[0],
        ];
    }
}
