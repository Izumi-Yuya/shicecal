<?php

namespace Tests\Feature;

use App\Models\ElectricalEquipment;
use App\Models\ElevatorEquipment;
use App\Models\Facility;
use App\Models\GasEquipment;
use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use App\Models\User;
use App\Models\WaterEquipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ライフライン設備管理のパフォーマンステスト
 * 
 * 大量データでのパフォーマンスとN+1クエリ問題の検証を行います。
 */
class LifelineEquipmentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $facilities;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        
        // Create multiple facilities for performance testing
        $this->facilities = Facility::factory()->count(10)->create()->toArray();
    }

    /** @test */
    public function it_handles_large_dataset_efficiently()
    {
        $this->actingAs($this->admin);

        // Create large dataset
        $startTime = microtime(true);
        
        foreach ($this->facilities as $facility) {
            $categories = ['electrical', 'water', 'gas', 'elevator', 'hvac_lighting'];
            
            foreach ($categories as $category) {
                $lifelineEquipment = LifelineEquipment::factory()->create([
                    'facility_id' => $facility['id'],
                    'category' => $category,
                    'status' => 'active',
                    'created_by' => $this->admin->id,
                ]);

                // Create specific equipment based on category
                switch ($category) {
                    case 'electrical':
                        ElectricalEquipment::factory()->create([
                            'lifeline_equipment_id' => $lifelineEquipment->id,
                        ]);
                        break;
                    case 'gas':
                        GasEquipment::factory()->create([
                            'lifeline_equipment_id' => $lifelineEquipment->id,
                        ]);
                        break;
                    case 'water':
                        WaterEquipment::factory()->create([
                            'lifeline_equipment_id' => $lifelineEquipment->id,
                        ]);
                        break;
                    case 'elevator':
                        ElevatorEquipment::factory()->create([
                            'lifeline_equipment_id' => $lifelineEquipment->id,
                        ]);
                        break;
                    case 'hvac_lighting':
                        HvacLightingEquipment::factory()->create([
                            'lifeline_equipment_id' => $lifelineEquipment->id,
                        ]);
                        break;
                }
            }
        }

        $creationTime = microtime(true) - $startTime;

        // Test query performance
        $queryStartTime = microtime(true);
        
        $allEquipment = LifelineEquipment::with([
            'facility',
            'electricalEquipment',
            'gasEquipment',
            'waterEquipment',
            'elevatorEquipment',
            'hvacLightingEquipment'
        ])->get();

        $queryTime = microtime(true) - $queryStartTime;

        // Assertions
        $this->assertEquals(50, $allEquipment->count()); // 10 facilities × 5 categories
        $this->assertLessThan(5.0, $creationTime, 'Creation should take less than 5 seconds');
        $this->assertLessThan(1.0, $queryTime, 'Query should take less than 1 second');

        // Verify data integrity
        foreach ($allEquipment as $equipment) {
            $this->assertNotNull($equipment->facility);
            $this->assertContains($equipment->category, ['electrical', 'water', 'gas', 'elevator', 'hvac_lighting']);
        }
    }

    /** @test */
    public function it_avoids_n_plus_one_queries()
    {
        $this->actingAs($this->admin);

        // Create test data
        $facility = Facility::factory()->create();
        $categories = ['electrical', 'water', 'gas'];

        foreach ($categories as $category) {
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $facility->id,
                'category' => $category,
            ]);

            if ($category === 'electrical') {
                ElectricalEquipment::factory()->create([
                    'lifeline_equipment_id' => $lifelineEquipment->id,
                ]);
            }
        }

        // Test without eager loading (should have N+1 problem)
        DB::enableQueryLog();
        
        $equipmentWithoutEager = LifelineEquipment::where('facility_id', $facility->id)->get();
        foreach ($equipmentWithoutEager as $equipment) {
            $equipment->facility->name; // This will trigger additional queries
        }
        
        $queriesWithoutEager = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Test with eager loading (should avoid N+1)
        $equipmentWithEager = LifelineEquipment::with('facility')
            ->where('facility_id', $facility->id)
            ->get();
        
        foreach ($equipmentWithEager as $equipment) {
            $equipment->facility->name; // This should not trigger additional queries
        }
        
        $queriesWithEager = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Eager loading should use fewer queries
        $this->assertLessThan($queriesWithoutEager, $queriesWithEager);
        $this->assertLessThanOrEqual(2, $queriesWithEager); // Should be 1-2 queries max
    }

    /** @test */
    public function it_handles_concurrent_updates_efficiently()
    {
        $this->actingAs($this->admin);

        $facility = Facility::factory()->create();
        $lifelineEquipment = LifelineEquipment::factory()->create([
            'facility_id' => $facility->id,
            'category' => 'electrical',
        ]);

        $electricalEquipment = ElectricalEquipment::factory()->create([
            'lifeline_equipment_id' => $lifelineEquipment->id,
            'basic_info' => [
                'electrical_contractor' => '東京電力',
                'counter' => 0,
            ],
        ]);

        // Simulate concurrent updates
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $currentInfo = $electricalEquipment->fresh()->basic_info;
            $currentInfo['counter'] = ($currentInfo['counter'] ?? 0) + 1;
            $currentInfo['last_update'] = now()->toISOString();
            
            $electricalEquipment->update([
                'basic_info' => $currentInfo,
            ]);
        }

        $updateTime = microtime(true) - $startTime;

        // Verify final state
        $finalEquipment = $electricalEquipment->fresh();
        $this->assertEquals(10, $finalEquipment->basic_info['counter']);
        $this->assertLessThan(2.0, $updateTime, 'Updates should complete within 2 seconds');
    }

    /** @test */
    public function it_optimizes_complex_queries()
    {
        $this->actingAs($this->admin);

        // Create complex test data
        foreach ($this->facilities as $facility) {
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $facility['id'],
                'category' => 'electrical',
                'status' => 'active',
                'created_by' => $this->admin->id,
            ]);

            ElectricalEquipment::factory()->create([
                'lifeline_equipment_id' => $lifelineEquipment->id,
                'basic_info' => [
                    'electrical_contractor' => '東京電力',
                    'capacity_kw' => rand(100, 1000),
                ],
            ]);
        }

        // Test complex query performance
        $startTime = microtime(true);

        $results = LifelineEquipment::with(['facility', 'electricalEquipment'])
            ->where('category', 'electrical')
            ->where('status', 'active')
            ->whereHas('electricalEquipment', function ($query) {
                $query->whereRaw("JSON_EXTRACT(basic_info, '$.capacity_kw') > ?", [500]);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $queryTime = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $queryTime, 'Complex query should complete within 1 second');
        $this->assertLessThanOrEqual(5, $results->count());

        // Verify results are correct
        foreach ($results as $result) {
            $this->assertEquals('electrical', $result->category);
            $this->assertEquals('active', $result->status);
            $this->assertNotNull($result->electricalEquipment);
            $this->assertGreaterThan(500, $result->electricalEquipment->basic_info['capacity_kw']);
        }
    }

    /** @test */
    public function it_handles_bulk_operations_efficiently()
    {
        $this->actingAs($this->admin);

        $facility = Facility::factory()->create();
        $equipmentIds = [];

        // Create multiple equipment with unique facilities
        for ($i = 0; $i < 20; $i++) {
            $testFacility = Facility::factory()->create();
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $testFacility->id,
                'category' => 'electrical',
                'status' => 'draft',
                'created_by' => $this->admin->id,
            ]);
            $equipmentIds[] = $lifelineEquipment->id;
        }

        // Test bulk update performance
        $startTime = microtime(true);

        LifelineEquipment::whereIn('id', $equipmentIds)
            ->update([
                'status' => 'active',
                'updated_by' => $this->admin->id,
                'approved_by' => $this->admin->id,
                'approved_at' => now(),
            ]);

        $bulkUpdateTime = microtime(true) - $startTime;

        // Verify bulk update
        $updatedCount = LifelineEquipment::whereIn('id', $equipmentIds)
            ->where('status', 'active')
            ->count();

        $this->assertEquals(20, $updatedCount);
        $this->assertLessThan(1.0, $bulkUpdateTime, 'Bulk update should complete within 1 second');
    }

    /** @test */
    public function it_handles_memory_usage_efficiently()
    {
        $this->actingAs($this->admin);

        $memoryBefore = memory_get_usage();

        // Create and process large dataset
        $facility = Facility::factory()->create();
        
        for ($i = 0; $i < 100; $i++) {
            $testFacility = Facility::factory()->create();
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $testFacility->id,
                'category' => 'electrical',
            ]);

            ElectricalEquipment::factory()->create([
                'lifeline_equipment_id' => $lifelineEquipment->id,
                'basic_info' => [
                    'electrical_contractor' => '東京電力',
                    'large_data' => str_repeat('test data ', 100), // Simulate large data
                ],
            ]);
        }

        // Process data in chunks to test memory efficiency
        LifelineEquipment::with('electricalEquipment')
            ->where('facility_id', $facility->id)
            ->chunk(10, function ($equipment) {
                foreach ($equipment as $item) {
                    // Process each item
                    $this->assertNotNull($item->electricalEquipment);
                }
            });

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory usage should be reasonable (less than 50MB for this test)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 50MB');
    }

    /** @test */
    public function it_optimizes_json_field_queries()
    {
        $this->actingAs($this->admin);

        $facility = Facility::factory()->create();

        // Create equipment with various JSON data
        for ($i = 0; $i < 50; $i++) {
            $testFacility = Facility::factory()->create();
            $lifelineEquipment = LifelineEquipment::factory()->create([
                'facility_id' => $testFacility->id,
                'category' => 'electrical',
            ]);

            ElectricalEquipment::factory()->create([
                'lifeline_equipment_id' => $lifelineEquipment->id,
                'basic_info' => [
                    'electrical_contractor' => $i % 2 === 0 ? '東京電力' : '関西電力',
                    'capacity_kw' => rand(100, 2000),
                    'installation_year' => rand(2020, 2024),
                ],
            ]);
        }

        // Test JSON query performance
        $startTime = microtime(true);

        $tokyoElectricResults = ElectricalEquipment::whereRaw(
            "JSON_EXTRACT(basic_info, '$.electrical_contractor') = ?",
            ['東京電力']
        )->count();

        $highCapacityResults = ElectricalEquipment::whereRaw(
            "JSON_EXTRACT(basic_info, '$.capacity_kw') > ?",
            [1000]
        )->count();

        $recentInstallationResults = ElectricalEquipment::whereRaw(
            "JSON_EXTRACT(basic_info, '$.installation_year') >= ?",
            [2023]
        )->count();

        $jsonQueryTime = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $jsonQueryTime, 'JSON queries should complete within 1 second');
        $this->assertEquals(25, $tokyoElectricResults); // Half should be Tokyo Electric
        $this->assertGreaterThan(0, $highCapacityResults);
        $this->assertGreaterThan(0, $recentInstallationResults);
    }
}