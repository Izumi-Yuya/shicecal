<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Comment;
use App\Models\Facility;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for comment posted.
     */
    public function notifyCommentPosted(Comment $comment): void
    {
        $facility = $comment->facility;
        $poster = $comment->poster;

        // Find primary responder to notify
        $primaryResponder = User::where('role', 'primary_responder')->first();

        if (!$primaryResponder) {
            Log::warning('No primary responder found for comment notification', [
                'comment_id' => $comment->id,
                'facility_id' => $facility->id,
            ]);
            return;
        }

        // Create notification
        $notification = Notification::create([
            'user_id' => $primaryResponder->id,
            'type' => 'comment_posted',
            'title' => '新しいコメントが投稿されました',
            'message' => sprintf(
                '%s さんが施設「%s」にコメントを投稿しました。',
                $poster->name,
                $facility->facility_name
            ),
            'data' => [
                'comment_id' => $comment->id,
                'facility_id' => $facility->id,
                'poster_id' => $poster->id,
                'field_name' => $comment->field_name,
            ],
        ]);

        // Send email notification
        $this->sendEmailNotification($notification);
    }

    /**
     * Create a notification for comment status change.
     */
    public function notifyCommentStatusChanged(Comment $comment, string $oldStatus): void
    {
        $facility = $comment->facility;
        $poster = $comment->poster;
        $assignee = $comment->assignee;

        $statusText = $this->getStatusText($comment->status);
        $oldStatusText = $this->getStatusText($oldStatus);

        // Notify the comment poster
        $notification = Notification::create([
            'user_id' => $poster->id,
            'type' => 'comment_status_changed',
            'title' => 'コメントのステータスが更新されました',
            'message' => sprintf(
                '施設「%s」のあなたのコメントのステータスが「%s」から「%s」に変更されました。',
                $facility->facility_name,
                $oldStatusText,
                $statusText
            ),
            'data' => [
                'comment_id' => $comment->id,
                'facility_id' => $facility->id,
                'old_status' => $oldStatus,
                'new_status' => $comment->status,
                'assignee_id' => $assignee?->id,
            ],
        ]);

        // Send email notification
        $this->sendEmailNotification($notification);
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(Notification $notification): void
    {
        try {
            // For now, we'll just log the email instead of actually sending
            // In production, this would use AWS SES
            Log::info('Email notification would be sent', [
                'notification_id' => $notification->id,
                'user_email' => $notification->user->email,
                'title' => $notification->title,
                'message' => $notification->message,
            ]);

            // Mark email as sent
            $notification->markEmailAsSent();
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user-friendly status text.
     */
    private function getStatusText(string $status): string
    {
        return match ($status) {
            'pending' => '未対応',
            'in_progress' => '対応中',
            'resolved' => '対応済',
            default => $status,
        };
    }

    /**
     * Get notifications for a user.
     */
    public function getUserNotifications(User $user, int $limit = 20)
    {
        return Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete old notifications (older than specified days).
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return Notification::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Send annual confirmation request notification.
     */
    public function sendAnnualConfirmationRequest(User $facilityManager, Facility $facility, int $year): void
    {
        $notification = Notification::create([
            'user_id' => $facilityManager->id,
            'type' => 'annual_confirmation_request',
            'title' => '年次情報確認のお願い',
            'message' => sprintf(
                '%d年度の施設「%s」の情報確認をお願いします。',
                $year,
                $facility->facility_name
            ),
            'data' => [
                'facility_id' => $facility->id,
                'confirmation_year' => $year,
            ],
        ]);

        // Send email notification
        $this->sendEmailNotification($notification);
    }

    /**
     * Send discrepancy notification to editors.
     */
    public function sendDiscrepancyNotification(User $editor, $annualConfirmation): void
    {
        $facility = $annualConfirmation->facility;
        $facilityManager = $annualConfirmation->facilityManager;

        $notification = Notification::create([
            'user_id' => $editor->id,
            'type' => 'discrepancy_reported',
            'title' => '年次確認で相違が報告されました',
            'message' => sprintf(
                '施設「%s」の%d年度年次確認で相違が報告されました。対応をお願いします。',
                $facility->facility_name,
                $annualConfirmation->confirmation_year
            ),
            'data' => [
                'annual_confirmation_id' => $annualConfirmation->id,
                'facility_id' => $facility->id,
                'confirmation_year' => $annualConfirmation->confirmation_year,
                'facility_manager_id' => $facilityManager?->id,
            ],
        ]);

        // Send email notification
        $this->sendEmailNotification($notification);
    }

    /**
     * Create a notification with the given data.
     */
    public function createNotification(array $data): Notification
    {
        $notification = Notification::create($data);

        // Send email notification
        $this->sendEmailNotification($notification);

        return $notification;
    }
}
