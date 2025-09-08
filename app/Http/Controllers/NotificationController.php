<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification)
    {
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        return view('notifications.show', compact('notification'));
    }

    /**
     * Update the specified notification (mark as read).
     */
    public function update(Notification $notification)
    {
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', '通知を既読にしました。');
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        return $this->update($notification);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(Auth::user());

        return redirect()->back()->with('success', 'すべての通知を既読にしました。');
    }

    /**
     * Get unread notification count for the authenticated user.
     */
    public function unreadCount()
    {
        try {
            if (!Auth::check()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $count = $this->notificationService->getUnreadCount(Auth::user());

            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get notification count', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification count',
                'count' => 0
            ], 500);
        }
    }

    /**
     * Get unread notification count for the authenticated user (alias).
     */
    public function getUnreadCount()
    {
        return $this->unreadCount();
    }

    /**
     * Get recent notifications for dropdown display.
     */
    public function getRecent()
    {
        $notifications = $this->notificationService->getUserNotifications(Auth::user(), 10);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount(Auth::user()),
        ]);
    }
}
