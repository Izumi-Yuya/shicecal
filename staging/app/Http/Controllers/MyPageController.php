<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyPageController extends Controller
{
    /**
     * Display the user's my page dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's comments with status counts
        $myComments = Comment::where('posted_by', $user->id)
            ->with(['facility', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $commentStatusCounts = [
            'pending' => Comment::where('posted_by', $user->id)->where('status', 'pending')->count(),
            'in_progress' => Comment::where('posted_by', $user->id)->where('status', 'in_progress')->count(),
            'resolved' => Comment::where('posted_by', $user->id)->where('status', 'resolved')->count(),
        ];

        // Get recent notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $unreadNotificationCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // Get assigned comments if user is a primary responder
        $assignedComments = collect();
        $assignedStatusCounts = [];

        if ($user->isPrimaryResponder() || $user->isAdmin()) {
            $assignedComments = Comment::where('assigned_to', $user->id)
                ->with(['facility', 'poster'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $assignedStatusCounts = [
                'pending' => Comment::where('assigned_to', $user->id)->where('status', 'pending')->count(),
                'in_progress' => Comment::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
                'resolved' => Comment::where('assigned_to', $user->id)->where('status', 'resolved')->count(),
            ];
        }

        return view('my-page.index', compact(
            'myComments',
            'commentStatusCounts',
            'recentNotifications',
            'unreadNotificationCount',
            'assignedComments',
            'assignedStatusCounts'
        ));
    }

    /**
     * Display detailed view of user's comments.
     */
    public function myComments(Request $request)
    {
        $user = Auth::user();

        $query = Comment::where('posted_by', $user->id)
            ->with(['facility', 'assignee']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by facility
        if ($request->filled('facility_name')) {
            $query->whereHas('facility', function ($q) use ($request) {
                $q->where('facility_name', 'like', '%'.$request->input('facility_name').'%');
            });
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        $statusCounts = [
            'pending' => Comment::where('posted_by', $user->id)->where('status', 'pending')->count(),
            'in_progress' => Comment::where('posted_by', $user->id)->where('status', 'in_progress')->count(),
            'resolved' => Comment::where('posted_by', $user->id)->where('status', 'resolved')->count(),
        ];

        return view('my-page.my-comments', compact('comments', 'statusCounts'));
    }

    /**
     * Display user's activity summary.
     */
    public function activitySummary()
    {
        $user = Auth::user();

        // Comments posted by month (last 6 months)
        $commentsByMonth = Comment::where('posted_by', $user->id)
            ->selectRaw('strftime("%Y-%m", created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month');

        // Response time statistics
        $responseStats = Comment::where('posted_by', $user->id)
            ->whereNotNull('resolved_at')
            ->selectRaw('
                AVG((julianday(resolved_at) - julianday(created_at)) * 24) as avg_response_hours,
                MIN((julianday(resolved_at) - julianday(created_at)) * 24) as min_response_hours,
                MAX((julianday(resolved_at) - julianday(created_at)) * 24) as max_response_hours
            ')
            ->first();

        // Most commented facilities
        $topFacilities = Comment::where('posted_by', $user->id)
            ->with('facility')
            ->selectRaw('facility_id, COUNT(*) as comment_count')
            ->groupBy('facility_id')
            ->orderBy('comment_count', 'desc')
            ->limit(5)
            ->get();

        return view('my-page.activity-summary', compact(
            'commentsByMonth',
            'responseStats',
            'topFacilities'
        ));
    }
}
