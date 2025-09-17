<?php

namespace App\Services\Comments;

use Illuminate\Support\Facades\Cache;

/**
 * Cache manager for comment operations
 * Implements Single Responsibility Principle for caching
 */
class CommentCacheManager
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get cache key for comments
     */
    public function getCommentsKey(int $facilityId, string $section): string
    {
        return "facility_comments_{$facilityId}_{$section}";
    }

    /**
     * Get cache key for comment count
     */
    public function getCountKey(int $facilityId, string $section): string
    {
        return "facility_comment_count_{$facilityId}_{$section}";
    }

    /**
     * Remember data in cache
     */
    public function remember(string $key, callable $callback)
    {
        return Cache::remember($key, self::CACHE_TTL, $callback);
    }

    /**
     * Clear cache for a specific section
     */
    public function clearSectionCache(int $facilityId, string $section): void
    {
        Cache::forget($this->getCommentsKey($facilityId, $section));
        Cache::forget($this->getCountKey($facilityId, $section));
    }

    /**
     * Clear all comment cache for a facility
     */
    public function clearFacilityCache(int $facilityId): void
    {
        $sections = array_keys(config('comments.sections', []));

        foreach ($sections as $section) {
            $this->clearSectionCache($facilityId, $section);
        }
    }

    /**
     * Get cache TTL
     */
    public function getCacheTtl(): int
    {
        return self::CACHE_TTL;
    }
}
