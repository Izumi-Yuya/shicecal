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

        return view('comments.my-comments', compact('comments'));
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
}
