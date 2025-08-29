<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Comment;
use App\Models\MaintenanceHistory;
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
        $this->middleware('auth');
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
        if ($user->hasRole(['admin', 'approver'])) {
            $pendingApprovals = Facility::where('status', 'pending')->count();
        }
        
        // 未読コメント数
        $unreadComments = Comment::where('status', 'unread')
            ->whereHas('facility', function($query) use ($user) {
                // ユーザーの権限に応じて施設をフィルタリング
                if (!$user->hasRole('admin')) {
                    $query->where('prefecture', $user->prefecture)
                          ->orWhere('department', $user->department);
                }
            })
            ->count();
        
        // 今月の修繕件数
        $monthlyMaintenance = MaintenanceHistory::whereMonth('maintenance_date', now()->month)
            ->whereYear('maintenance_date', now()->year)
            ->count();
        
        // 最近の活動ログ（最新10件）
        $recentActivities = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
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