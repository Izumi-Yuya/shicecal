<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\LandInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandInfoPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected LandInfoService $landInfoService;

    protected LandCalculationService $calculationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->landInfoService = app(LandInfoService::class);
        $this->calculationService = app(LandCalculationService::class);
    }

    /**
     * Test database query performance with indexes
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function test_database_query_performance_with_indexes()
    {
        // Create test data
        $facilities = Facility::factory()->count(1000)->create();
        $landInfos = [];

        foreach ($facilities as $facility) {
            $landInfos[] = LandInfo::factory()->create([
                'facility_id' => $facility->id,
                'ownership_type' => fake()->randomElement(['owned', 'leased', 'owned_rental']),
                'status' => fake()->randomElement(['draft', 'pending_approval', 'approved']),
            ]);
        }

        // Benchmark queries that should benefit from indexes
        $benchmarks = [];

        // Test facility_id index
        $start = microtime(true);
        $result1 = LandInfo::where('facility_id', $facilities->first()->id)->first();
        $benchmarks['facility_id_query'] = microtime(true) - $start;

        // Test status index
        $start = microtime(true);
        $result2 = LandInfo::where('status', 'approved')->count();
        $benchmarks['status_query'] = microtime(true) - $start;

        // Test ownership_type index
        $start = microtime(true);
        $result3 = LandInfo::where('ownership_type', 'owned')->count();
        $benchmarks['ownership_type_query'] = microtime(true) - $start;

        // Test composite index (status + facility_id)
        $start = microtime(true);
        $result4 = LandInfo::where('status', 'approved')
            ->where('facility_id', $facilities->first()->id)
            ->first();
        $benchmarks['composite_query'] = microtime(true) - $start;

        // Test bulk query performance
        $facilityIds = $facilities->take(100)->pluck('id')->toArray();
        $start = microtime(true);
        $result5 = LandInfo::whereIn('facility_id', $facilityIds)->get();
        $benchmarks['bulk_query'] = microtime(true) - $start;

        // Log performance metrics
        $this->logPerformanceMetrics('Database Queries', $benchmarks);

        // Assert reasonable performance (adjust thresholds as needed)
        $this->assertLessThan(0.01, $benchmarks['facility_id_query'], 'Facility ID query too slow');
        $this->assertLessThan(0.05, $benchmarks['status_query'], 'Status query too slow');
        $this->assertLessThan(0.05, $benchmarks['ownership_type_query'], 'Ownership type query too slow');
        $this->assertLessThan(0.01, $benchmarks['composite_query'], 'Composite query too slow');
        $this->assertLessThan(0.1, $benchmarks['bulk_query'], 'Bulk query too slow');

        // Verify results are correct
        $this->assertNotNull($result1);
        $this->assertIsInt($result2);
        $this->assertIsInt($result3);
        $this->assertEquals(100, $result5->count());
    }

    /**
     * Test caching performance
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
     */
    public function test_caching_performance()
    {
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create(['facility_id' => $facility->id]);

        $benchmarks = [];

        // Test cache miss (first load)
        Cache::flush();
        $start = microtime(true);
        $result1 = $this->landInfoService->getLandInfo($facility);
        $benchmarks['cache_miss'] = microtime(true) - $start;

        // Test cache hit (subsequent loads)
        $start = microtime(true);
        $result2 = $this->landInfoService->getLandInfo($facility);
        $benchmarks['cache_hit'] = microtime(true) - $start;

        // Test formatted data caching
        $start = microtime(true);
        $result3 = $this->landInfoService->getFormattedLandInfoWithCache($facility);
        $benchmarks['formatted_cache_miss'] = microtime(true) - $start;

        $start = microtime(true);
        $result4 = $this->landInfoService->getFormattedLandInfoWithCache($facility);
        $benchmarks['formatted_cache_hit'] = microtime(true) - $start;

        // Test export data caching
        $start = microtime(true);
        $result5 = $this->landInfoService->getExportDataWithCache($facility);
        $benchmarks['export_cache_miss'] = microtime(true) - $start;

        $start = microtime(true);
        $result6 = $this->landInfoService->getExportDataWithCache($facility);
        $benchmarks['export_cache_hit'] = microtime(true) - $start;

        // Test bulk caching
        $facilities = Facility::factory()->count(50)->create();
        foreach ($facilities as $f) {
            LandInfo::factory()->create(['facility_id' => $f->id]);
        }

        $facilityIds = $facilities->pluck('id')->toArray();

        Cache::flush();
        $start = microtime(true);
        $result7 = $this->landInfoService->getBulkLandInfo($facilityIds);
        $benchmarks['bulk_cache_miss'] = microtime(true) - $start;

        $start = microtime(true);
        $result8 = $this->landInfoService->getBulkLandInfo($facilityIds);
        $benchmarks['bulk_cache_hit'] = microtime(true) - $start;

        // Log performance metrics
        $this->logPerformanceMetrics('Caching Performance', $benchmarks);

        // Assert cache hits are significantly faster than misses (or at least not slower)
        $this->assertLessThan($benchmarks['cache_miss'], $benchmarks['cache_hit'], 'Cache hit not faster than miss');
        $this->assertLessThan($benchmarks['formatted_cache_miss'], $benchmarks['formatted_cache_hit'], 'Formatted cache hit not faster than miss');
        $this->assertLessThan($benchmarks['export_cache_miss'], $benchmarks['export_cache_hit'], 'Export cache hit not faster than miss');
        $this->assertLessThan($benchmarks['bulk_cache_miss'], $benchmarks['bulk_cache_hit'], 'Bulk cache hit not faster than miss');

        // Verify results are correct
        $this->assertEquals($landInfo->id, $result1->id);
        $this->assertEquals($landInfo->id, $result2->id);
        $this->assertIsArray($result3);
        $this->assertIsArray($result4);
        $this->assertIsArray($result5);
        $this->assertIsArray($result6);
        $this->assertIsArray($result7);
        $this->assertIsArray($result8);
    }

    /**
     * Test calculation performance
     * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
     */
    public function test_calculation_performance()
    {
        $benchmarks = [];
        $iterations = 1000;

        // Test unit price calculation
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->calculationService->calculateUnitPrice(10000000, 100.5);
        }
        $benchmarks['unit_price_calculation'] = (microtime(true) - $start) / $iterations;

        // Test contract period calculation
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->calculationService->calculateContractPeriod('2020-01-01', '2025-12-31');
        }
        $benchmarks['contract_period_calculation'] = (microtime(true) - $start) / $iterations;

        // Test currency formatting
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->calculationService->formatCurrency(1234567);
        }
        $benchmarks['currency_formatting'] = (microtime(true) - $start) / $iterations;

        // Test area formatting
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->calculationService->formatArea(123.45, 'sqm');
        }
        $benchmarks['area_formatting'] = (microtime(true) - $start) / $iterations;

        // Test Japanese date formatting
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->calculationService->formatJapaneseDate('2025-01-01');
        }
        $benchmarks['japanese_date_formatting'] = (microtime(true) - $start) / $iterations;

        // Log performance metrics
        $this->logPerformanceMetrics('Calculation Performance (per operation)', $benchmarks);

        // Assert calculations are fast enough (microseconds)
        $this->assertLessThan(0.001, $benchmarks['unit_price_calculation'], 'Unit price calculation too slow');
        $this->assertLessThan(0.001, $benchmarks['contract_period_calculation'], 'Contract period calculation too slow');
        $this->assertLessThan(0.001, $benchmarks['currency_formatting'], 'Currency formatting too slow');
        $this->assertLessThan(0.001, $benchmarks['area_formatting'], 'Area formatting too slow');
        $this->assertLessThan(0.001, $benchmarks['japanese_date_formatting'], 'Japanese date formatting too slow');
    }

    /**
     * Test bulk operations performance
     * Requirements: 10.1, 10.2, 10.3, 10.4
     */
    public function test_bulk_operations_performance()
    {
        // Create test data
        $facilities = Facility::factory()->count(500)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        $benchmarks = [];

        // Test bulk land info retrieval
        $facilityIds = $facilities->pluck('id')->toArray();

        $start = microtime(true);
        $result1 = $this->landInfoService->getBulkLandInfo($facilityIds);
        $benchmarks['bulk_retrieval'] = microtime(true) - $start;

        // Test cache warming
        Cache::flush();
        $start = microtime(true);
        $this->landInfoService->warmUpCache($facilityIds);
        $benchmarks['cache_warming'] = microtime(true) - $start;

        // Test bulk formatted data
        $start = microtime(true);
        $formattedData = [];
        foreach ($facilities->take(100) as $facility) {
            $formattedData[] = $this->landInfoService->getFormattedLandInfoWithCache($facility);
        }
        $benchmarks['bulk_formatted_data'] = microtime(true) - $start;

        // Test bulk export data
        $start = microtime(true);
        $exportData = [];
        foreach ($facilities->take(100) as $facility) {
            $exportData[] = $this->landInfoService->getExportDataWithCache($facility);
        }
        $benchmarks['bulk_export_data'] = microtime(true) - $start;

        // Log performance metrics
        $this->logPerformanceMetrics('Bulk Operations Performance', $benchmarks);

        // Assert reasonable performance for bulk operations
        $this->assertLessThan(1.0, $benchmarks['bulk_retrieval'], 'Bulk retrieval too slow');
        $this->assertLessThan(2.0, $benchmarks['cache_warming'], 'Cache warming too slow');
        $this->assertLessThan(0.5, $benchmarks['bulk_formatted_data'], 'Bulk formatted data too slow');
        $this->assertLessThan(0.5, $benchmarks['bulk_export_data'], 'Bulk export data too slow');

        // Verify results
        $this->assertCount(500, $result1);
        $this->assertCount(100, $formattedData);
        $this->assertCount(100, $exportData);
    }

    /**
     * Test memory usage during operations
     */
    public function test_memory_usage()
    {
        $memoryBenchmarks = [];

        // Baseline memory
        $baselineMemory = memory_get_usage(true);
        $memoryBenchmarks['baseline'] = $baselineMemory;

        // Create large dataset
        $facilities = Facility::factory()->count(1000)->create();
        foreach ($facilities as $facility) {
            LandInfo::factory()->create(['facility_id' => $facility->id]);
        }

        $afterDataCreation = memory_get_usage(true);
        $memoryBenchmarks['after_data_creation'] = $afterDataCreation;

        // Test bulk operations memory usage
        $facilityIds = $facilities->pluck('id')->toArray();

        $beforeBulkOp = memory_get_usage(true);
        $bulkData = $this->landInfoService->getBulkLandInfo($facilityIds);
        $afterBulkOp = memory_get_usage(true);

        $memoryBenchmarks['bulk_operation_increase'] = $afterBulkOp - $beforeBulkOp;

        // Test cache warming memory usage
        Cache::flush();
        $beforeCacheWarm = memory_get_usage(true);
        $this->landInfoService->warmUpCache($facilityIds);
        $afterCacheWarm = memory_get_usage(true);

        $memoryBenchmarks['cache_warming_increase'] = $afterCacheWarm - $beforeCacheWarm;

        // Log memory usage
        $this->logMemoryUsage($memoryBenchmarks);

        // Assert memory usage is reasonable (adjust thresholds as needed)
        $this->assertLessThan(50 * 1024 * 1024, $memoryBenchmarks['bulk_operation_increase'], 'Bulk operation uses too much memory'); // 50MB
        $this->assertLessThan(100 * 1024 * 1024, $memoryBenchmarks['cache_warming_increase'], 'Cache warming uses too much memory'); // 100MB

        // Verify data integrity
        $this->assertCount(1000, $bulkData);
    }

    /**
     * Test concurrent access performance
     */
    public function test_concurrent_access_simulation()
    {
        $facility = Facility::factory()->create();
        $landInfo = LandInfo::factory()->create(['facility_id' => $facility->id]);

        $benchmarks = [];
        $concurrentRequests = 50;

        // Simulate concurrent cache access
        $start = microtime(true);
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $this->landInfoService->getLandInfo($facility);
        }
        $benchmarks['concurrent_cache_access'] = microtime(true) - $start;

        // Simulate concurrent calculations
        $start = microtime(true);
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $this->calculationService->calculateUnitPrice(10000000 + $i, 100 + $i);
        }
        $benchmarks['concurrent_calculations'] = microtime(true) - $start;

        // Log performance metrics
        $this->logPerformanceMetrics('Concurrent Access Performance', $benchmarks);

        // Assert reasonable performance under concurrent load
        $this->assertLessThan(1.0, $benchmarks['concurrent_cache_access'], 'Concurrent cache access too slow');
        $this->assertLessThan(0.1, $benchmarks['concurrent_calculations'], 'Concurrent calculations too slow');
    }

    /**
     * Log performance metrics for analysis
     */
    protected function logPerformanceMetrics(string $category, array $metrics): void
    {
        $formattedMetrics = [];
        foreach ($metrics as $operation => $time) {
            $formattedMetrics[$operation] = number_format($time * 1000, 3).'ms';
        }

        $this->addToAssertionCount(1); // Prevent risky test warning

        // Log to console for CI/CD monitoring
        fwrite(STDERR, "\n=== {$category} ===\n");
        foreach ($formattedMetrics as $operation => $time) {
            fwrite(STDERR, "{$operation}: {$time}\n");
        }
        fwrite(STDERR, "========================\n");
    }

    /**
     * Log memory usage for analysis
     */
    protected function logMemoryUsage(array $memoryStats): void
    {
        $formattedStats = [];
        foreach ($memoryStats as $operation => $bytes) {
            $formattedStats[$operation] = $this->formatBytes($bytes);
        }

        $this->addToAssertionCount(1); // Prevent risky test warning

        fwrite(STDERR, "\n=== Memory Usage ===\n");
        foreach ($formattedStats as $operation => $memory) {
            fwrite(STDERR, "{$operation}: {$memory}\n");
        }
        fwrite(STDERR, "===================\n");
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
