<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\FacilityComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FacilityCommentController extends Controller
{
    /**
     * 指定されたセクションのコメントを取得
     */
    public function index(Facility $facility, string $section): JsonResponse
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
     * コメントを保存
     */
    public function store(Request $request, Facility $facility): JsonResponse
    {
        $request->validate([
            'section' => 'required|string|in:basic_info,contact_info,building_info,facility_info,services',
            'comment' => 'required|string|max:1000',
        ]);

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
     * コメントを削除
     */
    public function destroy(Facility $facility, FacilityComment $comment): JsonResponse
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
