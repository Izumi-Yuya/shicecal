<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandInfoPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't run full seeder to avoid database conflicts
        // $this->seed();
    }

    public function test_land_info_listing_performance_with_large_dataset()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create 1000 facilities with land info
        $facilities = Facility::factory()->count(1000)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        // Measure query performance
        $startTime = microtime(true);
        $queryCount = DB::getQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)
            ->get('/facilities?per_page=100');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $executionTime, 'Query should complete within 2 seconds');
        $this->assertLessThan(10, count($queries), 'Should use fewer than 10 queries (N+1 prevention)');
    }

    public function test_csv_export_performance_with_large_dataset()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create 500 facilities with land info
        $facilities = Facility::factory()->count(500)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->post('/export/csv/generate', [
                'fields' => [
                    'name',
                    'land_ownership_type',
                    'land_purchase_price',
                    'land_monthly_rent',
                    'land_site_area_sqm',
                    'land_notes',
                ],
            ]);

        $endTime = microtime(true);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Performance assertion
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(5.0, $executionTime, 'CSV export should complete within 5 seconds');

        // Memory usage assertion
        $memoryUsage = memory_get_peak_usage(true);
        $this->assertLessThan(128 * 1024 * 1024, $memoryUsage, 'Memory usage should be under 128MB');
    }

    public function test_pdf_export_performance_with_multiple_facilities()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create 50 facilities with land info (PDF generation is more resource intensive)
        $facilities = Facility::factory()->count(50)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->post('/export/pdf/batch', [
                'facility_ids' => $facilities->pluck('id')->toArray(),
            ]);

        $endTime = microtime(true);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Performance assertion
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(30.0, $executionTime, 'PDF export should complete within 30 seconds');
    }

    public function test_land_info_search_performance()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create 1000 facilities with varied land info
        $facilities = Facility::factory()->count(1000)->create();
        foreach ($facilities as $index => $facility) {
            LandInfo::factory()->create([
                'facility_id' => $facility->id,
                'ownership_type' => ['owned', 'leased', 'owned_rental'][$index % 3],
                'purchase_price' => $index % 3 === 0 ? rand(1000000, 50000000) : null,
                'monthly_rent' => $index % 3 === 1 ? rand(100000, 1000000) : null,
            ]);
        }

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Search for owned properties
        $response = $this->actingAs($user)
            ->get('/facilities?land_ownership_type=owned&per_page=50');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Performance assertions
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $executionTime, 'Search should complete within 1 second');
        $this->assertLessThan(5, count($queries), 'Should use efficient queries');
    }

    public function test_land_info_calculation_performance()
    {
        $user = User::factory()->create(['role' => 'editor']);
        $facility = Facility::factory()->create();

        // Test multiple rapid calculations
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $calculationType = $i % 2 === 0 ? 'unit_price' : 'contract_period';
            $response = $this->actingAs($user)
                ->post("/facilities/{$facility->id}/land-info/calculate", [
                    'calculation_type' => $calculationType,
                    'purchase_price' => rand(1000000, 50000000),
                    'site_area_tsubo' => rand(50, 500),
                    'contract_start_date' => '2020-01-01',
                    'contract_end_date' => '2025-06-01',
                ]);

            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should handle 100 calculations quickly
        $this->assertLessThan(2.0, $executionTime, 'Calculations should be fast');
        $this->assertLessThan(0.02, $executionTime / 100, 'Each calculation should take less than 20ms');
    }

    public function test_concurrent_land_info_updates()
    {
        $users = User::factory()->count(10)->create(['role' => 'editor']);
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create(['facility_id' => $facility->id]);

        $startTime = microtime(true);
        $responses = [];

        // Simulate concurrent updates
        foreach ($users as $index => $user) {
            $responses[] = $this->actingAs($user)
                ->put("/facilities/{$facility->id}/land-info", [
                    'ownership_type' => 'owned',
                    'purchase_price' => 10000000 + ($index * 1000000),
                    'notes' => "Update from user {$index}",
                ]);
        }

        $endTime = microtime(true);

        // All requests should complete successfully
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        $executionTime = $endTime - $startTime;
        $this->assertLessThan(5.0, $executionTime, 'Concurrent updates should complete within 5 seconds');
    }

    public function test_memory_usage_with_large_land_info_dataset()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $initialMemory = memory_get_usage(true);

        // Create 2000 facilities with land info
        $facilities = Facility::factory()->count(2000)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        $afterCreationMemory = memory_get_usage(true);

        // Load all facilities with land info
        $facilitiesWithLandInfo = Facility::with('landInfo')->get();

        $afterLoadingMemory = memory_get_usage(true);

        // Memory usage assertions
        $creationMemoryIncrease = $afterCreationMemory - $initialMemory;
        $loadingMemoryIncrease = $afterLoadingMemory - $afterCreationMemory;

        $this->assertLessThan(100 * 1024 * 1024, $creationMemoryIncrease, 'Creation should use less than 100MB');
        $this->assertLessThan(200 * 1024 * 1024, $loadingMemoryIncrease, 'Loading should use less than 200MB');

        // Verify data integrity
        $this->assertCount(2000, $facilitiesWithLandInfo);
        $facilitiesWithLandInfoCount = $facilitiesWithLandInfo->filter(function ($facility) {
            return $facility->landInfo !== null;
        })->count();
        $this->assertEquals(2000, $facilitiesWithLandInfoCount);
    }

    public function test_database_index_performance()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create large dataset
        $facilities = Facility::factory()->count(5000)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        DB::enableQueryLog();

        // Test queries that should use indexes
        $queries = [
            // Query by facility_id (should use index)
            function () use ($facilities) {
                return LandInfo::where('facility_id', $facilities->first()->id)->first();
            },

            // Query by ownership_type (should use index)
            function () {
                return LandInfo::where('ownership_type', 'owned')->count();
            },

            // Query by status (should use index)
            function () {
                return LandInfo::where('status', 'approved')->count();
            },
        ];

        foreach ($queries as $query) {
            $startTime = microtime(true);
            $result = $query();
            $endTime = microtime(true);

            $executionTime = $endTime - $startTime;
            $this->assertLessThan(0.1, $executionTime, 'Indexed queries should be very fast');
        }

        $allQueries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify queries are using indexes (execution time should be consistent)
        $this->assertLessThan(10, count($allQueries), 'Should use efficient queries');
    }

    public function test_bulk_land_info_operations()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Test bulk creation
        $startTime = microtime(true);

        $facilities = Facility::factory()->count(1000)->create();
        $landInfoData = [];

        foreach ($facilities as $facility) {
            $landInfoData[] = [
                'facility_id' => $facility->id,
                'ownership_type' => 'owned',
                'purchase_price' => rand(1000000, 50000000),
                'site_area_tsubo' => rand(50, 500),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert
        LandInfo::insert($landInfoData);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(3.0, $executionTime, 'Bulk operations should be fast');
        $this->assertEquals(1000, LandInfo::count());
    }

    public function test_export_with_complex_filtering()
    {
        $user = User::factory()->create(['role' => 'admin']);

        // Create diverse dataset
        $facilities = Facility::factory()->count(1000)->create();
        foreach ($facilities as $index => $facility) {
            LandInfo::factory()->create([
                'facility_id' => $facility->id,
                'ownership_type' => ['owned', 'leased', 'owned_rental'][$index % 3],
                'purchase_price' => $index % 3 === 0 ? rand(1000000, 50000000) : null,
                'monthly_rent' => $index % 3 === 1 ? rand(100000, 1000000) : null,
                'site_area_sqm' => rand(100, 1000),
            ]);
        }

        $startTime = microtime(true);

        // Complex filtered export
        $response = $this->actingAs($user)
            ->post('/export/csv/generate', [
                'fields' => [
                    'name',
                    'land_ownership_type',
                    'land_purchase_price',
                    'land_monthly_rent',
                    'land_site_area_sqm',
                ],
                'filters' => [
                    'land_ownership_type' => 'owned',
                    'land_purchase_price_min' => 5000000,
                    'land_site_area_sqm_min' => 200,
                ],
            ]);

        $endTime = microtime(true);

        $response->assertStatus(200);

        $executionTime = $endTime - $startTime;
        $this->assertLessThan(3.0, $executionTime, 'Filtered export should complete within 3 seconds');
    }
}
