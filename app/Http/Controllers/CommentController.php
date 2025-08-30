<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Facility;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
        $comment = Comment::create([
            'facility_id' => $facilityId,
            'field_name' => $fieldName,
            'content' => $content,
            'status' => 'pending',
            'posted_by' => Auth::id(),
            'assigned_to' => $primaryResponder?->id,
        ]);

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

        $comments = Comment::with(['poster', 'assignee'])
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
        $comments = Comment::with(['facility', 'assignee'])
            ->where('posted_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statusCounts = [
            'pending' => Comment::where('posted_by', Auth::id())->where('status', 'pending')->count(),
            'in_progress' => Comment::where('posted_by', Auth::id())->where('status', 'in_progress')->count(),
            'resolved' => Comment::where('posted_by', Auth::id())->where('status', 'resolved')->count(),
        ];

        return view('comments.my-comments', compact('comments', 'statusCounts'));
    }

    /**
     * Show comments assigned to current user.
     */
    public function assignedComments()
    {
        $comments = Comment::with(['facility', 'poster'])
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
        $query = Comment::with(['facility', 'poster', 'assignee']);

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
            'pending' => Comment::where('status', 'pending')->count(),
            'in_progress' => Comment::where('status', 'in_progress')->count(),
            'resolved' => Comment::where('status', 'resolved')->count(),
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

        $comments = Comment::whereIn('id', $commentIds)->get();

        foreach ($comments as $comment) {
            $oldStatus = $comment->status;
            $comment->status = $status;
            
            if ($status === 'resolved') {
                $comment->resolved_at = now();
            } else {
                $comment->resolved_at = null;
            }

            $comment->save();

            // Send notification if status changed
            if ($oldStatus !== $status) {
                $this->notificationService->notifyCommentStatusChanged($comment, $oldStatus);
            }
        }

        return redirect()->back()->with('success', count($commentIds) . '件のコメントステータスを更新しました。');
    }
}
