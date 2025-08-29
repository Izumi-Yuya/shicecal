<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Comment;
use App\Models\ActivityLog;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Temporarily disable auth middleware for testing
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        
        // 基本統計情報を取得
        $facilityCount = Facility::count();
        
        // 承認待ちの件数（承認者のみ）
        $pendingApprovals = 0;
        // TODO: 権限システム実装後に有効化
        // if ($user->hasRole(['admin', 'approver'])) {
        //     $pendingApprovals = Facility::where('status', 'pending')->count();
        // }
        
        // 未読コメント数
        $unreadComments = Comment::count(); // 簡略化
        
        // 今月の修繕件数
        $monthlyMaintenance = 0; // TODO: MaintenanceHistoryモデル実装後に有効化
        
        // 最近の活動ログ（最新10件）
        $recentActivities = ActivityLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('home', compact(
            'facilityCount',
            'pendingApprovals',
            'unreadComments',
            'monthlyMaintenance',
            'recentActivities'
        ));
    }
}