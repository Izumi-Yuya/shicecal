<?php

namespace App\Services\Comments;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface CommentStrategyInterface
{
    /**
     * Validate comment content for this strategy
     */
    public function validateContent(string $content): void;

    /**
     * Create comment with strategy-specific logic
     */
    public function createComment(Facility $facility, string $section, string $content, User $user): Comment;

    /**
     * Get comments with strategy-specific filtering/ordering
     */
    public function getComments(Facility $facility, string $section): Collection;

    /**
     * Get comment count with strategy-specific logic
     */
    public function getCommentCount(Facility $facility, string $section): int;

    /**
     * Handle post-creation actions (notifications, etc.)
     */
    public function handlePostCreation(Comment $comment): void;
}