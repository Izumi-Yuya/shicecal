<?php

namespace App\Console\Commands;

use App\Services\PerformanceBenchmarkService;
use Illuminate\Console\Command;

class BenchmarkPerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'benchmark:performance 
                            {--output= : Output file path for results}
                            {--format=json : Output format (json, markdown)}
                            {--compare= : Compare with previous benchmark file}';

    /**
     * The console command description.
     */
    protected $description = 'Run comprehensive performance benchmarks for the application';

    /**
     * Execute the console command.
     */
    public function handle(PerformanceBenchmarkService $benchmarkService): int
    {
        $this->info('Starting performance benchmarks...');

        // Run benchmarks
        $results = $benchmarkService->runBenchmarks();

        // Display results
        $this->displayResults($results);

        // Save results if output specified
        if ($this->option('output')) {
            $this->saveResults($results, $benchmarkService);
        }

        // Compare with previous results if specified
        if ($this->option('compare')) {
            $this->compareResults($results);
        }

        $this->info('Performance benchmarks completed!');

        return Command::SUCCESS;
    }

    /**
     * Display benchmark results in console
     */
    private function displayResults(array $results): void
    {
        $this->info("\n=== Performance Benchmark Results ===");
        $this->info("Environment: {$results['environment']}");
        $this->info("PHP Version: {$results['php_version']}");
        $this->info("Laravel Version: {$results['laravel_version']}");

        // Display database state
        if (isset($results['database_state'])) {
            $this->info("\n--- Database State ---");
            $this->info("Facilities: {$results['database_state']['facilities_count']}");
            $this->info("Users: {$results['database_state']['users_count']}");
            $this->info("Land Info Records: {$results['database_state']['land_info_count']}");
        }

        if (isset($results['operations'])) {
            $this->info("\n--- Operation Performance ---");

            $headers = ['Operation', 'Time (ms)', 'Memory (MB)', 'Queries', 'Status'];
            $rows = [];

            foreach ($results['operations'] as $name => $metrics) {
                $status = $metrics['success'] ? '<info>✅ OK</info>' : '<error>❌ FAIL</error>';
                $rows[] = [
                    $name,
                    $metrics['execution_time_ms'],
                    $metrics['memory_usage_mb'],
                    $metrics['query_count'],
                    $status,
                ];
            }

            $this->table($headers, $rows);
        }

        if (isset($results['memory'])) {
            $this->info("\n--- Memory Usage Analysis ---");

            $headers = ['Operation', 'Used (MB)', 'Peak (MB)', 'Status'];
            $rows = [];

            foreach ($results['memory'] as $name => $metrics) {
                $status = $metrics['success'] ? '<info>✅ OK</info>' : '<error>❌ FAIL</error>';
                $rows[] = [
                    $name,
                    $metrics['memory_used_mb'],
                    $metrics['peak_memory_mb'],
                    $status,
                ];
            }

            $this->table($headers, $rows);
        }

        // Show warnings for performance issues
        $this->showPerformanceWarnings($results);

        // Show error details
        $this->showErrorDetails($results);
    }

    /**
     * Show performance warnings
     */
    private function showPerformanceWarnings(array $results): void
    {
        $warnings = [];

        if (isset($results['operations'])) {
            foreach ($results['operations'] as $name => $metrics) {
                if ($metrics['execution_time_ms'] > 1000) {
                    $warnings[] = "Slow operation: {$name} ({$metrics['execution_time_ms']}ms)";
                }

                if ($metrics['query_count'] > 10) {
                    $warnings[] = "High query count: {$name} ({$metrics['query_count']} queries)";
                }

                if ($metrics['memory_usage_mb'] > 50) {
                    $warnings[] = "High memory usage: {$name} ({$metrics['memory_usage_mb']}MB)";
                }
            }
        }

        if (! empty($warnings)) {
            $this->warn("\n⚠️  Performance Warnings:");
            foreach ($warnings as $warning) {
                $this->warn("  • {$warning}");
            }
        } else {
            $this->info("\n✅ All operations are performing within acceptable limits");
        }
    }

    /**
     * Show error details for failed operations
     */
    private function showErrorDetails(array $results): void
    {
        $errors = [];

        if (isset($results['operations'])) {
            foreach ($results['operations'] as $name => $metrics) {
                if (! $metrics['success'] && $metrics['error']) {
                    $errors[] = "{$name}: {$metrics['error']}";
                }
            }
        }

        if (isset($results['memory'])) {
            foreach ($results['memory'] as $name => $metrics) {
                if (! $metrics['success'] && $metrics['error']) {
                    $errors[] = "{$name}: {$metrics['error']}";
                }
            }
        }

        if (! empty($errors)) {
            $this->error("\n❌ Error Details:");
            foreach ($errors as $error) {
                $this->error("  • {$error}");
            }
        }
    }

    /**
     * Save benchmark results to file
     */
    private function saveResults(array $results, PerformanceBenchmarkService $benchmarkService): void
    {
        $outputPath = $this->option('output');
        $format = $this->option('format');

        try {
            if ($format === 'markdown') {
                $content = $benchmarkService->generateReport($results);
                $outputPath = str_ends_with($outputPath, '.md') ? $outputPath : $outputPath.'.md';
            } else {
                $content = json_encode($results, JSON_PRETTY_PRINT);
                $outputPath = str_ends_with($outputPath, '.json') ? $outputPath : $outputPath.'.json';
            }

            // Ensure directory exists
            $directory = dirname($outputPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($outputPath, $content);
            $this->info("Results saved to: {$outputPath}");
        } catch (\Exception $e) {
            $this->error("Failed to save results: {$e->getMessage()}");
        }
    }

    /**
     * Compare with previous benchmark results
     */
    private function compareResults(array $currentResults): void
    {
        $compareFile = $this->option('compare');

        if (! file_exists($compareFile)) {
            $this->error("Comparison file not found: {$compareFile}");

            return;
        }

        try {
            $previousResults = json_decode(file_get_contents($compareFile), true);

            if (! $previousResults) {
                $this->error('Invalid comparison file format');

                return;
            }

            $this->info("\n=== Performance Comparison ===");

            if (isset($currentResults['operations']) && isset($previousResults['operations'])) {
                $this->info("\n--- Operation Performance Changes ---");

                $headers = ['Operation', 'Previous (ms)', 'Current (ms)', 'Change', 'Status'];
                $rows = [];

                foreach ($currentResults['operations'] as $name => $current) {
                    if (isset($previousResults['operations'][$name])) {
                        $previous = $previousResults['operations'][$name];
                        $change = $current['execution_time_ms'] - $previous['execution_time_ms'];
                        $changePercent = $previous['execution_time_ms'] > 0
                            ? round(($change / $previous['execution_time_ms']) * 100, 1)
                            : 0;

                        $status = $change < 0 ? '<info>⬇️ Improved</info>' : ($change > 0 ? '<comment>⬆️ Slower</comment>' : '<info>➡️ Same</info>');

                        $changeText = $change >= 0 ? "+{$change}" : (string) $change;
                        $changeText .= "ms ({$changePercent}%)";

                        $rows[] = [
                            $name,
                            $previous['execution_time_ms'],
                            $current['execution_time_ms'],
                            $changeText,
                            $status,
                        ];
                    }
                }

                if (! empty($rows)) {
                    $this->table($headers, $rows);
                } else {
                    $this->info('No comparable operations found');
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to compare results: {$e->getMessage()}");
        }
    }
}
