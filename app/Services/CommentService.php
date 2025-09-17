<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use App\Services\Comments\CommentCacheManager;
use App\Services\Comments\CommentValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing facility comments
 * Implements business logic for comment operations with dependency injection
 */
class CommentService
{
    public function __construct(
        private CommentValidator $validator,
        private CommentCacheManager $cacheManager
    ) {}

    /**
     * Get comments for a facility section
     */
    public function getCommentsForSection(Facility $facility, string $section): Collection
    {
        $this->validator->validateSection($section);

        $cacheKey = $this->cacheManager->getCommentsKey($facility->id, $section);

        return $this->cacheManager->remember($cacheKey, function () use ($facility, $section) {
            return Comment::where('facility_id', $facility->id)
                ->where('section', $section)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Create a new comment
     */
    public function createComment(Facility $facility, string $section, string $content, User $user): Comment
    {
        $this->validator->validateSection($section);
        $this->validator->validateContent($content);

        $comment = Comment::create([
            'facility_id' => $facility->id,
            'section' => $section,
            'comment' => trim($content),
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Clear cache for this section
        $this->cacheManager->clearSectionCache($facility->id, $section);

        // Log activity
        Log::info('Comment created', [
            'facility_id' => $facility->id,
            'section' => $section,
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);

        return $comment->load('user:id,name');
    }

    /**
     * Get comment count for a section
     */
    public function getCommentCount(Facility $facility, string $section): int
    {
        $this->validator->validateSection($section);

        $cacheKey = $this->cacheManager->getCountKey($facility->id, $section);

        return $this->cacheManager->remember($cacheKey, function () use ($facility, $section) {
            return Comment::where('facility_id', $facility->id)
                ->where('section', $section)
                ->where('status', 'active')
                ->count();
        });
    }

    /**
     * Get all comment counts for a facility
     */
    public function getAllCommentCounts(Facility $facility): array
    {
        $sections = array_keys(config('comments.sections', []));
        $counts = [];

        foreach ($sections as $section) {
            $counts[$section] = $this->getCommentCount($facility, $section);
        }

        return $counts;
    }

    /**
     * Get section configuration
     */
    public function getSectionConfig(string $section): ?array
    {
        return config("comments.sections.{$section}");
    }

    /**
     * Get all available sections
     */
    public function getAvailableSections(): array
    {
        return config('comments.sections', []);
    }

    /**
     * Clear all comment cache for a facility
     */
    public function clearFacilityCache(int $facilityId): void
    {
        $this->cacheManager->clearFacilityCache($facilityId);
    }
}
