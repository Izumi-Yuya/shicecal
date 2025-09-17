<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized cache management for facility-related data
 *
 * Provides advanced caching features including:
 * - Cache tagging for efficient invalidation
 * - Batch operations for performance
 * - Cache warming strategies
 * - Performance monitoring
 */
class CacheManager
{
    private const DEFAULT_TTL = 300; // 5 minutes

    private const LONG_TTL = 3600;   // 1 hour

    private const SHORT_TTL = 60;    // 1 minute

    // Cache key patterns
    private const FACILITY_KEY = 'facility_%d';

    private const FACILITY_COMMENTS_KEY = 'facility_comments_%d_%s';

    private const FACILITY_COMMENT_COUNT_KEY = 'facility_comment_count_%d_%s';

    private const VIEW_MODE_KEY = 'view_mode_%d';

    private const SERVICE_TABLE_KEY = 'service_table_%d';

    // Cache tags
    private const FACILITY_TAG = 'facility';

    private const COMMENTS_TAG = 'comments';

    private const SERVICE_TAG = 'services';

    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
    ];

    /**
     * Get or set facility data with caching and tagging
     */
    public function rememberFacility(int $facilityId, callable $callback, int $ttl = self::DEFAULT_TTL)
    {
        $key = sprintf(self::FACILITY_KEY, $facilityId);
        $tags = [self::FACILITY_TAG, "facility_{$facilityId}"];

        return $this->rememberWithTags($key, $tags, $ttl, $callback);
    }

    /**
     * Get multiple facilities with batch caching
     */
    public function rememberFacilities(array $facilityIds, callable $callback, int $ttl = self::DEFAULT_TTL): array
    {
        $results = [];
        $missingIds = [];

        // Check cache for each facility
        foreach ($facilityIds as $id) {
            $key = sprintf(self::FACILITY_KEY, $id);
            $cached = Cache::get($key);

            if ($cached !== null) {
                $results[$id] = $cached;
                $this->stats['hits']++;
            } else {
                $missingIds[] = $id;
                $this->stats['misses']++;
            }
        }

        // Fetch missing facilities in batch
        if (! empty($missingIds)) {
            $facilities = $callback($missingIds);

            foreach ($facilities as $facility) {
                $key = sprintf(self::FACILITY_KEY, $facility->id);
                $tags = [self::FACILITY_TAG, "facility_{$facility->id}"];

                $this->putWithTags($key, $facility, $tags, $ttl);
                $results[$facility->id] = $facility;
            }
        }

        return $results;
    }

    /**
     * Get or set facility comments with caching
     */
    public function rememberFacilityComments(int $facilityId, string $section, callable $callback, int $ttl = self::DEFAULT_TTL)
    {
        $key = sprintf(self::FACILITY_COMMENTS_KEY, $facilityId, $section);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Get or set comment count with caching
     */
    public function rememberCommentCount(int $facilityId, string $section, callable $callback, int $ttl = self::DEFAULT_TTL)
    {
        $key = sprintf(self::FACILITY_COMMENT_COUNT_KEY, $facilityId, $section);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear all facility-related cache using tags
     */
    public function clearFacilityCache(int $facilityId): void
    {
        try {
            // Use cache tags for efficient clearing
            if ($this->supportsTags()) {
                Cache::tags("facility_{$facilityId}")->flush();
                $this->stats['deletes']++;
            } else {
                // Fallback to individual key deletion
                $this->clearFacilityCacheByKeys($facilityId);
            }

            Log::debug("Cleared cache for facility {$facilityId}");
        } catch (\Exception $e) {
            Log::error('Failed to clear facility cache', [
                'facility_id' => $facilityId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear facility cache by individual keys (fallback method)
     */
    private function clearFacilityCacheByKeys(int $facilityId): void
    {
        $keys = [
            sprintf(self::FACILITY_KEY, $facilityId),
            sprintf(self::VIEW_MODE_KEY, $facilityId),
            sprintf(self::SERVICE_TABLE_KEY, $facilityId),
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
            $this->stats['deletes']++;
        }

        // Clear comment caches for all sections
        $sections = config('comments.sections', []);
        foreach (array_keys($sections) as $section) {
            Cache::forget(sprintf(self::FACILITY_COMMENTS_KEY, $facilityId, $section));
            Cache::forget(sprintf(self::FACILITY_COMMENT_COUNT_KEY, $facilityId, $section));
            $this->stats['deletes'] += 2;
        }
    }

    /**
     * Clear comment cache for specific section
     */
    public function clearCommentCache(int $facilityId, string $section): void
    {
        Cache::forget(sprintf(self::FACILITY_COMMENTS_KEY, $facilityId, $section));
        Cache::forget(sprintf(self::FACILITY_COMMENT_COUNT_KEY, $facilityId, $section));
    }

    /**
     * Warm up cache for facility
     */
    public function warmUpFacilityCache(int $facilityId): void
    {
        // This could be called after facility updates to pre-populate cache
        $facility = \App\Models\Facility::with(['services', 'comments'])->find($facilityId);

        if ($facility) {
            $key = sprintf(self::FACILITY_KEY, $facilityId);
            Cache::put($key, $facility, self::LONG_TTL);
        }
    }

    /**
     * Get cache statistics for monitoring
     */
    public function getCacheStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;

        return [
            'driver' => config('cache.default'),
            'supports_tags' => $this->supportsTags(),
            'stats' => $this->stats,
            'hit_rate_percentage' => round($hitRate, 2),
            'total_operations' => $total,
        ];
    }

    /**
     * Reset cache statistics
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * Check if cache driver supports tags
     */
    private function supportsTags(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    /**
     * Remember value with cache tags
     */
    private function rememberWithTags(string $key, array $tags, int $ttl, callable $callback)
    {
        if ($this->supportsTags()) {
            $result = Cache::tags($tags)->remember($key, $ttl, $callback);
        } else {
            $result = Cache::remember($key, $ttl, $callback);
        }

        // Track statistics
        if (Cache::has($key)) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
            $this->stats['writes']++;
        }

        return $result;
    }

    /**
     * Put value with cache tags
     */
    private function putWithTags(string $key, $value, array $tags, int $ttl): void
    {
        if ($this->supportsTags()) {
            Cache::tags($tags)->put($key, $value, $ttl);
        } else {
            Cache::put($key, $value, $ttl);
        }

        $this->stats['writes']++;
    }

    /**
     * Warm up cache for multiple facilities
     */
    public function warmUpMultipleFacilities(array $facilityIds): void
    {
        try {
            $facilities = \App\Models\Facility::with(['services', 'comments'])
                ->whereIn('id', $facilityIds)
                ->get();

            foreach ($facilities as $facility) {
                $key = sprintf(self::FACILITY_KEY, $facility->id);
                $tags = [self::FACILITY_TAG, "facility_{$facility->id}"];

                $this->putWithTags($key, $facility, $tags, self::LONG_TTL);
            }

            Log::info('Cache warmed up for facilities', [
                'facility_count' => count($facilityIds),
                'cached_count' => $facilities->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', [
                'facility_ids' => $facilityIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
