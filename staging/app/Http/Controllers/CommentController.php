<?php

namespace App\Http\Controllers;

use App\Models\FacilityComment;
use App\Models\Facility;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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
    public function store(Request $request)
    {
        // Handle facility-specific section comments (JSON response)
        if ($request->has('section') && $request->expectsJson()) {
            return $this->storeFacilityComment($request);
        }

        // Handle general comments (redirect response)
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
     * Store facility-specific section comment (from FacilityCommentController).
     */
    protected function storeFacilityComment(Request $request): JsonResponse
    {
        $request->validate([
            'facility_id' => 'required|exists:facilities,id',
            'section' => 'required|string|in:basic_info,contact_info,building_info,facility_info,services',
            'comment' => 'required|string|max:1000',
        ]);

        $facility = Facility::find($request->facility_id);

        $comment = $facility->comments()->create([
            'user_id' => auth()->id(),
            'section' => $request->section,
            'comment' => $request->comment,
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'コメントを追加しました。',
            'comment' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_name' => $comment->user->name,
                'created_at' => $comment->created_at->format('Y年m月d日 H:i'),
                'can_delete' => true,
            ],
        ]);
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
     * Display comments for a specific facility section (from FacilityCommentController).
     */
    public function facilityComments(Facility $facility, string $section): JsonResponse
    {
        $comments = $facility->comments()
            ->where('section', $section)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $comment->user->name,
                    'created_at' => $comment->created_at->format('Y年m月d日 H:i'),
                    'can_delete' => auth()->id() === $comment->user_id || auth()->user()->isAdmin(),
                ];
            }),
        ]);
    }

    /**
     * Display the specified comment.
     */
    public function show(FacilityComment $comment)
    {
        return response()->json([
            'success' => true,
            'comment' => $comment->load(['poster', 'assignee', 'facility'])
        ]);
    }

    /**
     * Update comment status.
     */
    public function updateStatus(Request $request, FacilityComment $comment): RedirectResponse
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
        $comments = FacilityComment::with(['facility', 'poster'])
            ->where('user_id', Auth::id())
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
        // For now, return empty collection since assigned_to field doesn't exist yet
        $comments = collect()->paginate(20);

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

    /**
     * Show the form for creating a new comment.
     */
    public function create()
    {
        return view('comments.create');
    }

    /**
     * Show the form for editing the specified comment.
     */
    public function edit(FacilityComment $comment)
    {
        return view('comments.edit', compact('comment'));
    }

    /**
     * Update the specified comment in storage.
     */
    public function update(Request $request, FacilityComment $comment)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment->update([
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'コメントを更新しました。');
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(FacilityComment $comment)
    {
        // 権限チェック
        if (auth()->id() !== $comment->user_id && !auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'このコメントを削除する権限がありません。');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'コメントを削除しました。');
    }

    /**
     * Delete a facility comment (from FacilityCommentController).
     */
    public function destroyFacilityComment(Facility $facility, FacilityComment $comment): JsonResponse
    {
        // 権限チェック
        if (auth()->id() !== $comment->user_id && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'このコメントを削除する権限がありません。',
            ], 403);
        }

        // 施設に属するコメントかチェック
        if ($comment->facility_id !== $facility->id) {
            return response()->json([
                'success' => false,
                'message' => 'コメントが見つかりません。',
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'コメントを削除しました。',
        ]);
    }
}
