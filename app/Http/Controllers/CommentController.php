<?php

namespace App\Http\Controllers;

use App\Models\FacilityComment;
use App\Models\Facility;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected NotificationService $notificationService;
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $facilityId = $request->input('facility_id');
        $fieldName = $request->input('field_name');
        $content = $request->input('content');

        // Validate required fields
        if (!$facilityId || !$content) {
            return redirect()->back()->with('error', 'コメント内容は必須です。');
        }

        // Check if facility exists and user has access
        $facility = Facility::find($facilityId);
        if (!$facility) {
            return redirect()->back()->with('error', '施設が見つかりません。');
        }

        // Find primary responder for notification
        $primaryResponder = User::where('role', 'primary_responder')->first();

        // Create comment
        $comment = FacilityComment::create([
            'facility_id' => $facilityId,
            'user_id' => Auth::id(),
            'section' => 'facility_info', // Default section
            'comment' => $content,
        ]);

        // Log comment creation
        $this->activityLogService->logCommentCreated(
            $comment->id,
            $facilityId,
            $fieldName ?: '施設情報',
            $request
        );

        // Send notification
        $this->notificationService->notifyCommentPosted($comment);

        return redirect()->back()->with('success', 'コメントを投稿しました。');
    }

    /**
     * Display comments for a specific facility.
     */
    public function index(Request $request)
    {
        $facilityId = $request->input('facility_id');

        if (!$facilityId) {
            return response()->json(['error' => '施設IDが必要です。'], 400);
        }

        $facility = Facility::find($facilityId);
        if (!$facility) {
            return response()->json(['error' => '施設が見つかりません。'], 404);
        }

        $comments = FacilityComment::with(['poster', 'assignee'])
            ->where('facility_id', $facilityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments
        ]);
    }

    /**
     * Update comment status.
     */
    public function updateStatus(Request $request, Comment $comment): RedirectResponse
    {
        $status = $request->input('status');

        if (!in_array($status, ['pending', 'in_progress', 'resolved'])) {
            return redirect()->back()->with('error', '無効なステータスです。');
        }

        $oldStatus = $comment->status;
        $comment->status = $status;

        if ($status === 'resolved') {
            $comment->resolved_at = now();
        } else {
            $comment->resolved_at = null;
        }

        $comment->save();

        // Log status change
        if ($oldStatus !== $status) {
            $this->activityLogService->logCommentStatusUpdated(
                $comment->id,
                $oldStatus,
                $status,
                $request
            );
        }

        // Send notification if status changed
        if ($oldStatus !== $status) {
            $this->notificationService->notifyCommentStatusChanged($comment, $oldStatus);
        }

        return redirect()->back()->with('success', 'コメントステータスを更新しました。');
    }

    /**
     * Show user's own comments (for my page functionality).
     */
    public function myComments()
    {
        $comments = FacilityComment::with(['facility', 'assignee'])
            ->where('posted_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statusCounts = [
            'pending' => FacilityComment::where('user_id', Auth::id())->count(),
            'in_progress' => 0, // FacilityComment doesn't have status field yet
            'resolved' => 0, // FacilityComment doesn't have status field yet
        ];

        return view('comments.my-comments', compact('comments', 'statusCounts'));
    }

    /**
     * Show comments assigned to current user.
     */
    public function assigned()
    {
        $comments = FacilityComment::with(['facility', 'poster'])
            ->where('assigned_to', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('comments.assigned-comments', compact('comments'));
    }

    /**
     * Show status dashboard for comment management.
     */
    public function statusDashboard(Request $request)
    {
        // Build query with filters
        $query = FacilityComment::with(['facility', 'poster']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by facility name
        if ($request->filled('facility_name')) {
            $query->whereHas('facility', function ($q) use ($request) {
                $q->where('facility_name', 'like', '%' . $request->input('facility_name') . '%');
            });
        }

        // Filter by assignee
        if ($request->filled('assignee')) {
            $query->where('assigned_to', $request->input('assignee'));
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get status counts
        $statusCounts = [
            'pending' => FacilityComment::count(),
            'in_progress' => 0, // FacilityComment doesn't have status field yet
            'resolved' => 0, // FacilityComment doesn't have status field yet
        ];

        // Get assignees for filter dropdown
        $assignees = User::whereIn('role', ['primary_responder', 'admin'])
            ->orderBy('name')
            ->get();

        return view('comments.status-dashboard', compact('comments', 'statusCounts', 'assignees'));
    }

    /**
     * Bulk update comment statuses.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $commentIds = $request->input('comment_ids', []);
        $status = $request->input('status');

        if (empty($commentIds) || !in_array($status, ['pending', 'in_progress', 'resolved'])) {
            return redirect()->back()->with('error', '無効な操作です。');
        }

        $comments = FacilityComment::whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $oldStatus = $comment->status;
            $comment->status = $status;

            if ($status === 'resolved') {
                $comment->resolved_at = now();
            } else {
                $comment->resolved_at = null;
            }

            $comment->save();

            // Log status change
            if ($oldStatus !== $status) {
                $this->activityLogService->logCommentStatusUpdated(
                    $comment->id,
                    $oldStatus,
                    $status,
                    $request
                );
            }

            // Send notification if status changed
            if ($oldStatus !== $status) {
                $this->notificationService->notifyCommentStatusChanged($comment, $oldStatus);
            }
        }

        return redirect()->back()->with('success', count($commentIds) . '件のコメントステータスを更新しました。');
    }
}
