<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PerformanceMonitoringService
{
    protected array $metrics = [];
    protected array $timers = [];

    /**
     * Start timing an operation
     */
    public function startTimer(string $operation): void
    {
        $this->timers[$operation] = microtime(true);
    }

    /**
     * End timing an operation and record the metric
     */
    public function endTimer(string $operation): float
    {
        if (!isset($this->timers[$operation])) {
            return 0.0;
        }

        $duration = microtime(true) - $this->timers[$operation];
        $this->recordMetric($operation, $duration);
        unset($this->timers[$operation]);

        return $duration;
    }

    /**
     * Record a performance metric
     */
    public function recordMetric(string $operation, float $value, string $unit = 'seconds'): void
    {
        $timestamp = now();

        $this->metrics[] = [
            'operation' => $operation,
            'value' => $value,
            'unit' => $unit,
            'timestamp' => $timestamp,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        // Store in cache for real-time monitoring
        $cacheKey = "performance_metrics_" . date('Y-m-d-H');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = [
            'operation' => $operation,
            'value' => $value,
            'unit' => $unit,
            'timestamp' => $timestamp->toISOString(),
            'memory_usage' => memory_get_usage(true),
        ];

        Cache::put($cacheKey, $existingMetrics, 3600); // Store for 1 hour

        // Log slow operations
        if ($unit === 'seconds' && $value > 1.0) {
            Log::warning('Slow operation detected', [
                'operation' => $operation,
                'duration' => $value,
                'memory_usage' => memory_get_usage(true),
            ]);
        }
    }

    /**
     * Monitor database query performance
     */
    public function monitorDatabaseQueries(): void
    {
        DB::listen(function ($query) {
            $duration = $query->time / 1000; // Convert to seconds

            $this->recordMetric(
                'database_query',
                $duration,
                'seconds'
            );

            // Log slow queries
            if ($duration > 0.5) { // 500ms threshold
                Log::warning('Slow database query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'duration' => $duration,
                ]);
            }
        });
    }

    /**
     * Monitor cache performance
     */
    public function monitorCacheOperation(string $operation, callable $callback)
    {
        $this->startTimer("cache_{$operation}");
        $result = $callback();
        $this->endTimer("cache_{$operation}");

        return $result;
    }

    /**
     * Get performance metrics for a time period
     */
    public function getMetrics(string $period = 'hour'): array
    {
        $cacheKey = match ($period) {
            'hour' => "performance_metrics_" . date('Y-m-d-H'),
            'day' => "performance_metrics_" . date('Y-m-d'),
            default => "performance_metrics_" . date('Y-m-d-H'),
        };

        return Cache::get($cacheKey, []);
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(): array
    {
        $metrics = $this->getMetrics();

        if (empty($metrics)) {
            return [
                'total_operations' => 0,
                'average_duration' => 0,
                'slowest_operation' => null,
                'memory_usage' => [
                    'current' => memory_get_usage(true),
                    'peak' => memory_get_peak_usage(true),
                ],
            ];
        }

        $durations = array_column($metrics, 'value');
        $operations = array_column($metrics, 'operation');

        $slowestIndex = array_search(max($durations), $durations);

        return [
            'total_operations' => count($metrics),
            'average_duration' => array_sum($durations) / count($durations),
            'min_duration' => min($durations),
            'max_duration' => max($durations),
            'slowest_operation' => $metrics[$slowestIndex] ?? null,
            'operations_by_type' => array_count_values($operations),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'average' => array_sum(array_column($metrics, 'memory_usage')) / count($metrics),
            ],
        ];
    }

    /**
     * Monitor land info specific operations
     */
    public function monitorLandInfoOperation(string $operation, callable $callback)
    {
        $this->startTimer("land_info_{$operation}");

        try {
            $result = $callback();
            $this->endTimer("land_info_{$operation}");
            return $result;
        } catch (\Exception $e) {
            $this->endTimer("land_info_{$operation}");
            $this->recordMetric("land_info_{$operation}_error", 1, 'count');
            throw $e;
        }
    }

    /**
     * Monitor export operations
     */
    public function monitorExportOperation(string $type, int $recordCount, callable $callback)
    {
        $this->startTimer("export_{$type}");
        $this->recordMetric("export_{$type}_records", $recordCount, 'count');

        try {
            $result = $callback();
            $duration = $this->endTimer("export_{$type}");

            // Calculate records per second
            $recordsPerSecond = $duration > 0 ? $recordCount / $duration : 0;
            $this->recordMetric("export_{$type}_throughput", $recordsPerSecond, 'records/second');

            return $result;
        } catch (\Exception $e) {
            $this->endTimer("export_{$type}");
            $this->recordMetric("export_{$type}_error", 1, 'count');
            throw $e;
        }
    }

    /**
     * Get real-time performance dashboard data
     */
    public function getDashboardData(): array
    {
        $summary = $this->getPerformanceSummary();
        $recentMetrics = array_slice($this->getMetrics(), -50); // Last 50 operations

        return [
            'summary' => $summary,
            'recent_operations' => $recentMetrics,
            'system_info' => [
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'php_version' => PHP_VERSION,
            ],
            'cache_stats' => $this->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
        ];
    }

    /**
     * Get cache statistics
     */
    protected function getCacheStats(): array
    {
        try {
            // This would depend on your cache driver
            // For Redis, you could get more detailed stats
            return [
                'driver' => config('cache.default'),
                'status' => 'connected',
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database statistics
     */
    protected function getDatabaseStats(): array
    {
        try {
            $connectionName = config('database.default');
            $connection = DB::connection($connectionName);

            return [
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'status' => 'connected',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear old performance metrics
     */
    public function clearOldMetrics(): void
    {
        $patterns = [
            'performance_metrics_*',
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Generate performance report
     */
    public function generateReport(string $period = 'day'): array
    {
        $metrics = $this->getMetrics($period);

        if (empty($metrics)) {
            return ['error' => 'No metrics available for the specified period'];
        }

        $operationGroups = [];
        foreach ($metrics as $metric) {
            $operation = $metric['operation'];
            if (!isset($operationGroups[$operation])) {
                $operationGroups[$operation] = [];
            }
            $operationGroups[$operation][] = $metric['value'];
        }

        $report = [
            'period' => $period,
            'total_operations' => count($metrics),
            'operations' => [],
        ];

        foreach ($operationGroups as $operation => $values) {
            $report['operations'][$operation] = [
                'count' => count($values),
                'average' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values),
                'median' => $this->calculateMedian($values),
                'p95' => $this->calculatePercentile($values, 95),
            ];
        }

        return $report;
    }

    /**
     * Calculate median value
     */
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    /**
     * Calculate percentile value
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);

        if (floor($index) === $index) {
            return $values[$index];
        } else {
            $lower = $values[floor($index)];
            $upper = $values[ceil($index)];
            return $lower + ($upper - $lower) * ($index - floor($index));
        }
    }
}
