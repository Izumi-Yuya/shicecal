<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceBenchmarkService
{
    private array $results = [];

    private float $startTime;

    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Run comprehensive performance benchmarks
     */
    public function runBenchmarks(): array
    {
        $this->results = [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        // Check database state
        $this->checkDatabaseState();

        // Database performance tests
        $this->benchmarkDatabaseQueries();

        // Service layer performance tests
        $this->benchmarkServiceOperations();

        // Memory usage tests
        $this->benchmarkMemoryUsage();

        // Cache performance tests
        $this->benchmarkCacheOperations();

        return $this->results;
    }

    /**
     * Check database state and availability
     */
    private function checkDatabaseState(): void
    {
        $this->results['database_state'] = [
            'facilities_count' => Facility::count(),
            'users_count' => User::count(),
            'land_info_count' => LandInfo::count(),
        ];
    }

    /**
     * Benchmark database query performance
     */
    private function benchmarkDatabaseQueries(): void
    {
        $this->results['database'] = [];

        // Test basic facility queries
        $this->measureOperation('database.facility_index', function () {
            return Facility::with(['landInfo', 'comments'])->paginate(20);
        });

        $this->measureOperation('database.facility_show', function () {
            $facility = Facility::first();
            if (! $facility) {
                return null; // Skip if no facilities exist
            }

            return Facility::with(['landInfo', 'landDocuments', 'comments', 'maintenanceHistories'])->find($facility->id);
        });

        // Test complex land info queries
        $this->measureOperation('database.land_info_calculations', function () {
            return LandInfo::whereNotNull('purchase_price')
                ->whereNotNull('site_area_sqm')
                ->selectRaw('
                    id,
                    purchase_price,
                    site_area_sqm,
                    (purchase_price / site_area_sqm) as unit_price_per_sqm,
                    CASE 
                        WHEN contract_end_date IS NOT NULL AND contract_start_date IS NOT NULL 
                        THEN (julianday(contract_end_date) - julianday(contract_start_date)) / 365.0
                        ELSE NULL 
                    END as contract_years
                ')
                ->limit(100)
                ->get();
        });

        // Test aggregation queries
        $this->measureOperation('database.facility_statistics', function () {
            return [
                'total_facilities' => Facility::count(),
                'facilities_with_land' => Facility::whereHas('landInfo')->count(),
                'avg_site_area_sqm' => LandInfo::avg('site_area_sqm'),
                'avg_site_area_tsubo' => LandInfo::avg('site_area_tsubo'),
                'total_land_value' => LandInfo::sum('purchase_price'),
            ];
        });

        // Test search queries
        $this->measureOperation('database.facility_search', function () {
            return Facility::where('name', 'like', '%施設%')
                ->orWhere('address', 'like', '%東京%')
                ->with('landInfo')
                ->limit(50)
                ->get();
        });
    }

    /**
     * Benchmark service layer operations
     */
    private function benchmarkServiceOperations(): void
    {
        $this->results['services'] = [];

        // Test FacilityService operations
        if (class_exists('App\Services\FacilityService')) {
            $facilityService = app('App\Services\FacilityService');

            $this->measureOperation('services.facility_create', function () use ($facilityService) {
                $testUser = User::first();
                if (! $testUser) {
                    return null; // Skip test if no users exist
                }

                $testData = [
                    'company_name' => 'Performance Test Company',
                    'office_code' => 'PERF001',
                    'facility_name' => 'Performance Test Facility',
                    'address' => 'Test Address',
                    'phone_number' => '03-1234-5678',
                    'status' => 'draft',
                ];

                try {
                    $facility = $facilityService->createFacility($testData, $testUser);
                    // Clean up
                    if ($facility) {
                        $facility->delete();
                    }

                    return $facility;
                } catch (\Exception $e) {
                    // Re-throw with more specific error message including stack trace
                    throw new \Exception('FacilityService::createFacility failed: '.$e->getMessage().' | Previous: '.($e->getPrevious() ? $e->getPrevious()->getMessage() : 'none'));
                }
            });
        }

        // Test ExportService operations
        if (class_exists('App\Services\ExportService')) {
            $exportService = app('App\Services\ExportService');

            $this->measureOperation('services.export_csv_generation', function () use ($exportService) {
                $facilityIds = Facility::limit(10)->pluck('id')->toArray();
                $fields = ['name', 'address', 'facility_type'];

                return $exportService->generateCsv($facilityIds, $fields);
            });
        }

        // Test ActivityLogService operations
        if (class_exists('App\Services\ActivityLogService')) {
            $activityService = app('App\Services\ActivityLogService');

            $this->measureOperation('services.activity_log_creation', function () use ($activityService) {
                $user = User::first();
                if ($user) {
                    // Temporarily authenticate the user for the log operation
                    auth()->login($user);
                    $result = $activityService->log(
                        'performance_test',
                        'benchmark',
                        null,
                        'Performance benchmark test'
                    );
                    auth()->logout();

                    return $result;
                }

                return null;
            });
        }
    }

    /**
     * Benchmark memory usage patterns
     */
    private function benchmarkMemoryUsage(): void
    {
        $this->results['memory'] = [];

        // Test memory usage with large datasets
        $this->measureMemoryOperation('memory.large_facility_collection', function () {
            return Facility::with(['landInfo', 'comments', 'landDocuments'])->get();
        });

        $this->measureMemoryOperation('memory.chunked_processing', function () {
            $processed = 0;
            Facility::chunk(100, function ($facilities) use (&$processed) {
                foreach ($facilities as $facility) {
                    // Simulate processing
                    $processed++;
                }
            });

            return $processed;
        });

        // Test memory usage with file operations
        $this->measureMemoryOperation('memory.file_processing', function () {
            $tempFile = tempnam(sys_get_temp_dir(), 'perf_test');
            $data = str_repeat('test data ', 10000);
            file_put_contents($tempFile, $data);
            $content = file_get_contents($tempFile);
            unlink($tempFile);

            return strlen($content);
        });
    }

    /**
     * Benchmark cache operations
     */
    private function benchmarkCacheOperations(): void
    {
        $this->results['cache'] = [];

        // Test cache write performance
        $this->measureOperation('cache.write_operations', function () {
            for ($i = 0; $i < 100; $i++) {
                Cache::put("perf_test_$i", "test_data_$i", 60);
            }

            return 100;
        });

        // Test cache read performance
        $this->measureOperation('cache.read_operations', function () {
            $hits = 0;
            for ($i = 0; $i < 100; $i++) {
                if (Cache::get("perf_test_$i")) {
                    $hits++;
                }
            }

            return $hits;
        });

        // Clean up cache
        for ($i = 0; $i < 100; $i++) {
            Cache::forget("perf_test_$i");
        }

        // Test cache with complex data
        $this->measureOperation('cache.complex_data', function () {
            $complexData = [
                'facilities' => Facility::limit(10)->get()->toArray(),
                'statistics' => [
                    'total' => Facility::count(),
                    'timestamp' => now(),
                ],
            ];

            Cache::put('complex_perf_test', $complexData, 60);
            $retrieved = Cache::get('complex_perf_test');
            Cache::forget('complex_perf_test');

            return $retrieved ? count($retrieved['facilities']) : 0;
        });
    }

    /**
     * Measure operation execution time and query count
     */
    private function measureOperation(string $name, callable $operation): void
    {
        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $operation();
            $success = true;
            $error = null;
        } catch (\Exception $e) {
            $result = null;
            $success = false;
            $error = $e->getMessage();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->results['operations'][$name] = [
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'query_count' => count($queries),
            'success' => $success,
            'error' => $error,
            'result_size' => $this->getResultSize($result),
        ];
    }

    /**
     * Measure memory usage for specific operations
     */
    private function measureMemoryOperation(string $name, callable $operation): void
    {
        $startMemory = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);

        try {
            $result = $operation();
            $success = true;
            $error = null;
        } catch (\Exception $e) {
            $result = null;
            $success = false;
            $error = $e->getMessage();
        }

        $endMemory = memory_get_usage(true);
        $peakMemoryAfter = memory_get_peak_usage(true);

        $this->results['memory'][$name] = [
            'memory_used_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'peak_memory_mb' => round(($peakMemoryAfter - $peakMemoryBefore) / 1024 / 1024, 2),
            'success' => $success,
            'error' => $error,
            'result_size' => $this->getResultSize($result),
        ];
    }

    /**
     * Get the size of a result for reporting
     */
    private function getResultSize($result): int
    {
        if (is_null($result)) {
            return 0;
        }

        if (is_countable($result)) {
            return count($result);
        }

        if (is_string($result)) {
            return strlen($result);
        }

        if (is_numeric($result)) {
            return 1;
        }

        return 1;
    }

    /**
     * Generate performance report
     */
    public function generateReport(array $benchmarkResults): string
    {
        $report = "# Performance Benchmark Report\n\n";
        $report .= "**Generated:** {$benchmarkResults['timestamp']}\n";
        $report .= "**Environment:** {$benchmarkResults['environment']}\n";
        $report .= "**PHP Version:** {$benchmarkResults['php_version']}\n";
        $report .= "**Laravel Version:** {$benchmarkResults['laravel_version']}\n\n";

        // Operations performance
        if (isset($benchmarkResults['operations'])) {
            $report .= "## Operation Performance\n\n";
            $report .= "| Operation | Time (ms) | Memory (MB) | Queries | Status |\n";
            $report .= "|-----------|-----------|-------------|---------|--------|\n";

            foreach ($benchmarkResults['operations'] as $name => $metrics) {
                $status = $metrics['success'] ? '✅' : '❌';
                $report .= "| {$name} | {$metrics['execution_time_ms']} | {$metrics['memory_usage_mb']} | {$metrics['query_count']} | {$status} |\n";
            }
            $report .= "\n";
        }

        // Memory usage
        if (isset($benchmarkResults['memory'])) {
            $report .= "## Memory Usage Analysis\n\n";
            $report .= "| Operation | Used (MB) | Peak (MB) | Status |\n";
            $report .= "|-----------|-----------|-----------|--------|\n";

            foreach ($benchmarkResults['memory'] as $name => $metrics) {
                $status = $metrics['success'] ? '✅' : '❌';
                $report .= "| {$name} | {$metrics['memory_used_mb']} | {$metrics['peak_memory_mb']} | {$status} |\n";
            }
            $report .= "\n";
        }

        // Performance recommendations
        $report .= "## Performance Recommendations\n\n";
        $report .= $this->generateRecommendations($benchmarkResults);

        return $report;
    }

    /**
     * Generate performance recommendations based on results
     */
    private function generateRecommendations(array $results): string
    {
        $recommendations = [];

        if (isset($results['operations'])) {
            foreach ($results['operations'] as $name => $metrics) {
                if ($metrics['execution_time_ms'] > 1000) {
                    $recommendations[] = "⚠️ **{$name}** is slow ({$metrics['execution_time_ms']}ms) - consider optimization";
                }

                if ($metrics['query_count'] > 10) {
                    $recommendations[] = "⚠️ **{$name}** has many queries ({$metrics['query_count']}) - consider eager loading";
                }

                if ($metrics['memory_usage_mb'] > 50) {
                    $recommendations[] = "⚠️ **{$name}** uses high memory ({$metrics['memory_usage_mb']}MB) - consider chunking";
                }
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = '✅ All operations are performing within acceptable limits';
        }

        return implode("\n", $recommendations)."\n";
    }
}
