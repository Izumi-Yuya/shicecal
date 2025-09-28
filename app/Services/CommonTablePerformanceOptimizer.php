<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CommonTablePerformanceOptimizer - 共通テーブルコンポーネントのパフォーマンス最適化
 *
 * ビューキャッシュ、メモリ最適化、大量データ処理の最適化を提供
 */
class CommonTablePerformanceOptimizer
{
    /**
     * キャッシュキーのプレフィックス
     */
    private const CACHE_PREFIX = 'common_table_';

    /**
     * デフォルトキャッシュ時間（分）
     */
    private const DEFAULT_CACHE_TTL = 60;

    /**
     * 大量データの閾値
     */
    private const LARGE_DATA_THRESHOLD = 100;

    /**
     * メモリ使用量の警告閾値（MB）
     */
    private const MEMORY_WARNING_THRESHOLD = 128;

    /**
     * テーブルデータのキャッシュキーを生成
     */
    public static function generateCacheKey(array $data, array $options = []): string
    {
        $keyData = [
            'data_hash' => md5(serialize($data)),
            'options_hash' => md5(serialize($options)),
            'version' => '1.0',
        ];

        return self::CACHE_PREFIX.md5(serialize($keyData));
    }

    /**
     * フォーマット済みデータをキャッシュから取得
     */
    public static function getCachedFormattedData(string $cacheKey): ?array
    {
        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::warning('CommonTable cache retrieval failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * フォーマット済みデータをキャッシュに保存
     *
     * @param  int  $ttl  キャッシュ時間（分）
     */
    public static function cacheFormattedData(string $cacheKey, array $formattedData, ?int $ttl = null): bool
    {
        try {
            $cacheTtl = $ttl ?? self::DEFAULT_CACHE_TTL;

            return Cache::put($cacheKey, $formattedData, now()->addMinutes($cacheTtl));
        } catch (\Exception $e) {
            Log::warning('CommonTable cache storage failed', [
                'cache_key' => $cacheKey,
                'data_size' => count($formattedData),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * データが大量かどうかを判定
     */
    public static function isLargeDataset(array $data): bool
    {
        $totalCells = 0;
        foreach ($data as $row) {
            if (isset($row['cells']) && is_array($row['cells'])) {
                $totalCells += count($row['cells']);
            }
        }

        return $totalCells > self::LARGE_DATA_THRESHOLD;
    }

    /**
     * データを最適化してメモリ使用量を削減
     */
    public static function optimizeDataForMemory(array $data, array $options = []): array
    {
        $optimized = [];
        $memoryBefore = memory_get_usage(true);

        foreach ($data as $rowIndex => $row) {
            if (! is_array($row) || ! isset($row['cells'])) {
                continue;
            }

            $optimizedRow = [
                'type' => $row['type'] ?? 'standard',
                'cells' => [],
            ];

            foreach ($row['cells'] as $cellIndex => $cell) {
                $optimizedCell = self::optimizeCell($cell, $options);
                if ($optimizedCell !== null) {
                    $optimizedRow['cells'][] = $optimizedCell;
                }
            }

            if (! empty($optimizedRow['cells'])) {
                $optimized[] = $optimizedRow;
            }

            // メモリ使用量チェック
            if ($rowIndex % 50 === 0) {
                self::checkMemoryUsage($rowIndex);
            }
        }

        $memoryAfter = memory_get_usage(true);
        $memorySaved = $memoryBefore - $memoryAfter;

        if ($memorySaved > 0) {
            Log::info('CommonTable memory optimization completed', [
                'rows_processed' => count($data),
                'memory_saved_mb' => round($memorySaved / 1024 / 1024, 2),
                'final_memory_mb' => round($memoryAfter / 1024 / 1024, 2),
            ]);
        }

        return $optimized;
    }

    /**
     * セルデータを最適化
     */
    private static function optimizeCell(array $cell, array $options = []): ?array
    {
        // 空セルの除外（オプション）
        if (isset($options['skip_empty_cells']) && $options['skip_empty_cells']) {
            if (ValueFormatter::isEmpty($cell['value'] ?? null)) {
                return null;
            }
        }

        $optimized = [
            'label' => $cell['label'] ?? null,
            'value' => $cell['value'] ?? null,
            'type' => $cell['type'] ?? 'text',
        ];

        // オプション属性の最適化
        $optionalFields = ['colspan', 'rowspan', 'class', 'attributes'];
        foreach ($optionalFields as $field) {
            if (isset($cell[$field]) && ! empty($cell[$field])) {
                $optimized[$field] = $cell[$field];
            }
        }

        // 値の前処理（大きなデータの場合）
        if (is_string($optimized['value']) && mb_strlen($optimized['value']) > 1000) {
            $optimized['value'] = mb_substr($optimized['value'], 0, 1000).'...';
            $optimized['_truncated'] = true;
        }

        return $optimized;
    }

    /**
     * メモリ使用量をチェックし、警告を出力
     */
    private static function checkMemoryUsage(int $currentIndex): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB

        if ($memoryUsage > self::MEMORY_WARNING_THRESHOLD) {
            Log::warning('CommonTable high memory usage detected', [
                'current_row' => $currentIndex,
                'memory_usage_mb' => round($memoryUsage, 2),
                'memory_limit' => ini_get('memory_limit'),
            ]);
        }
    }

    /**
     * バッチ処理用にデータを分割
     */
    public static function splitDataIntoBatches(array $data, int $batchSize = 50): array
    {
        return array_chunk($data, $batchSize, true);
    }

    /**
     * レンダリング統計を収集
     */
    public static function collectRenderingStats(array $data, float $renderTime, int $memoryUsed): array
    {
        $stats = [
            'total_rows' => count($data),
            'total_cells' => 0,
            'render_time_ms' => round($renderTime * 1000, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'is_large_dataset' => self::isLargeDataset($data),
            'performance_score' => 'good',
        ];

        // セル数の計算
        foreach ($data as $row) {
            if (isset($row['cells']) && is_array($row['cells'])) {
                $stats['total_cells'] += count($row['cells']);
            }
        }

        // パフォーマンススコアの計算
        if ($renderTime > 1.0 || $memoryUsed > 50 * 1024 * 1024) {
            $stats['performance_score'] = 'poor';
        } elseif ($renderTime > 0.5 || $memoryUsed > 25 * 1024 * 1024) {
            $stats['performance_score'] = 'fair';
        }

        return $stats;
    }

    /**
     * キャッシュクリア
     */
    public static function clearCache(?string $pattern = null): bool
    {
        try {
            if ($pattern) {
                // 特定パターンのキャッシュをクリア
                $keys = Cache::getRedis()->keys(self::CACHE_PREFIX.$pattern.'*');
                if (! empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // 全てのCommonTableキャッシュをクリア
                $keys = Cache::getRedis()->keys(self::CACHE_PREFIX.'*');
                if (! empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }

            Log::info('CommonTable cache cleared', [
                'pattern' => $pattern ?? 'all',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('CommonTable cache clear failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * パフォーマンス設定を取得
     */
    public static function getPerformanceConfig(): array
    {
        return [
            'cache_enabled' => config('cache.default') !== 'array',
            'cache_ttl' => self::DEFAULT_CACHE_TTL,
            'large_data_threshold' => self::LARGE_DATA_THRESHOLD,
            'memory_warning_threshold' => self::MEMORY_WARNING_THRESHOLD,
            'batch_size' => 50,
            'enable_memory_optimization' => true,
            'enable_data_truncation' => true,
            'skip_empty_cells' => false,
        ];
    }

    /**
     * パフォーマンス最適化が推奨されるかチェック
     */
    public static function analyzePerformanceNeeds(array $data, array $options = []): array
    {
        $analysis = [
            'needs_optimization' => false,
            'recommendations' => [],
            'estimated_render_time' => 0,
            'estimated_memory_usage' => 0,
        ];

        $totalCells = 0;
        $totalDataSize = 0;

        foreach ($data as $row) {
            if (isset($row['cells']) && is_array($row['cells'])) {
                $totalCells += count($row['cells']);
                foreach ($row['cells'] as $cell) {
                    if (isset($cell['value'])) {
                        $totalDataSize += mb_strlen(serialize($cell['value']));
                    }
                }
            }
        }

        // レンダリング時間の推定（経験的な値）
        $analysis['estimated_render_time'] = ($totalCells * 0.001) + ($totalDataSize / 1000000 * 0.1);

        // メモリ使用量の推定
        $analysis['estimated_memory_usage'] = $totalDataSize * 2; // 約2倍のメモリを使用

        // 最適化の推奨
        if ($totalCells > self::LARGE_DATA_THRESHOLD) {
            $analysis['needs_optimization'] = true;
            $analysis['recommendations'][] = 'バッチ処理の使用を推奨';
            $analysis['recommendations'][] = 'データキャッシュの有効化を推奨';
        }

        if ($analysis['estimated_memory_usage'] > 10 * 1024 * 1024) {
            $analysis['needs_optimization'] = true;
            $analysis['recommendations'][] = 'メモリ最適化の有効化を推奨';
        }

        if ($analysis['estimated_render_time'] > 0.5) {
            $analysis['needs_optimization'] = true;
            $analysis['recommendations'][] = 'レンダリング最適化の有効化を推奨';
        }

        return $analysis;
    }
}
