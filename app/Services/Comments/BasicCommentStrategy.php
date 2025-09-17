<?php

namespace App\Services\Comments;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BasicCommentStrategy implements CommentStrategyInterface
{
    private const CACHE_TTL = 300; // 5 minutes

    public function validateContent(string $content): void
    {
        $trimmedContent = trim($content);

        if (strlen($trimmedContent) < 1) {
            throw new \InvalidArgumentException('Comment content too short');
        }

        if (strlen($content) > 500) {
            throw new \InvalidArgumentException('Comment content too long');
        }

        // Basic security validation
        if (preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', $content)) {
            throw new \InvalidArgumentException('Invalid content detected');
        }
    }

    public function createComment(Facility $facility, string $section, string $content, User $user): Comment
    {
        $comment = Comment::create([
            'facility_id' => $facility->id,
            'section' => $section,
            'comment' => trim($content),
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $this->clearCache($facility->id, $section);

        return $comment->load('user:id,name');
    }

    public function getComments(Facility $facility, string $section): Collection
    {
        $cacheKey = "facility_comments_{$facility->id}_{$section}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($facility, $section) {
            return Comment::where('facility_id', $facility->id)
                ->where('section', $section)
                ->where('status', 'active')
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    public function getCommentCount(Facility $facility, string $section): int
    {
        $cacheKey = "facility_comment_count_{$facility->id}_{$section}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($facility, $section) {
            return Comment::where('facility_id', $facility->id)
                ->where('section', $section)
                ->where('status', 'active')
                ->count();
        });
    }

    public function handlePostCreation(Comment $comment): void
    {
        Log::info('Comment created', [
            'facility_id' => $comment->facility_id,
            'section' => $comment->section,
            'user_id' => $comment->user_id,
            'comment_id' => $comment->id,
        ]);
    }

    private function clearCache(int $facilityId, string $section): void
    {
        Cache::forget("facility_comments_{$facilityId}_{$section}");
        Cache::forget("facility_comment_count_{$facilityId}_{$section}");
    }
}
