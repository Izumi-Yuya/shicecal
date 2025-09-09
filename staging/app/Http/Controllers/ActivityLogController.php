<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            abort(403, 'ログ管理機能にアクセスする権限がありません。');
        }

        // Build query with filters
        $query = ActivityLog::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter by target type
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $startDate = $request->input('start_date') . ' 00:00:00';
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = $request->input('end_date') . ' 23:59:59';
            $query->where('created_at', '<=', $endDate);
        }

        // Filter by IP address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->input('ip_address') . '%');
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $logs = $query->paginate(50);

        // Get filter options
        $users = User::orderBy('name')->get();
        $actions = $this->getAvailableActions();
        $targetTypes = $this->getAvailableTargetTypes();

        // Get statistics
        $stats = $this->getLogStatistics($request);

        return view('admin.logs.index', compact('logs', 'users', 'actions', 'targetTypes', 'stats'));
    }

    /**
     * Display the specified activity log.
     */
    public function show(ActivityLog $activityLog)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            abort(403, 'ログ詳細を閲覧する権限がありません。');
        }

        $activityLog->load('user');

        return view('admin.logs.show', compact('activityLog'));
    }

    /**
     * Get available actions for filter dropdown.
     */
    private function getAvailableActions(): array
    {
        return [
            'login' => 'ログイン',
            'logout' => 'ログアウト',
            'create' => '作成',
            'update' => '更新',
            'delete' => '削除',
            'view' => '閲覧',
            'download' => 'ダウンロード',
            'upload' => 'アップロード',
            'export_csv' => 'CSV出力',
            'export_pdf' => 'PDF出力',
            'approve' => '承認',
            'reject' => '差戻し',
            'update_status' => 'ステータス更新',
        ];
    }

    /**
     * Get available target types for filter dropdown.
     */
    private function getAvailableTargetTypes(): array
    {
        return [
            'user' => 'ユーザー',
            'facility' => '施設',
            'file' => 'ファイル',
            'comment' => 'コメント',
            'maintenance_history' => '修繕履歴',
            'annual_confirmation' => '年次確認',
            'notification' => '通知',
            'system_setting' => 'システム設定',
            'system' => 'システム',
        ];
    }

    /**
     * Get log statistics for the current filters.
     */
    private function getLogStatistics(Request $request): array
    {
        $query = ActivityLog::query();

        // Apply same filters as main query
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('start_date')) {
            $startDate = $request->input('start_date') . ' 00:00:00';
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = $request->input('end_date') . ' 23:59:59';
            $query->where('created_at', '<=', $endDate);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->input('ip_address') . '%');
        }

        $totalLogs = $query->count();

        // Get action breakdown
        $actionStats = ActivityLog::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        // Get user activity breakdown
        $userStats = ActivityLog::selectRaw('user_id, COUNT(*) as count')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->user ? $item->user->name : 'Unknown User',
                    'count' => $item->count
                ];
            })
            ->toArray();

        // Get daily activity for the last 7 days
        $dailyStats = ActivityLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_logs' => $totalLogs,
            'action_stats' => $actionStats,
            'user_stats' => $userStats,
            'daily_stats' => $dailyStats,
        ];
    }

    /**
     * Get recent activity logs for dashboard.
     */
    public function recent(Request $request)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $limit = $request->input('limit', 10);

        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Get activity log statistics for API.
     */
    public function statistics(Request $request)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->getLogStatistics($request);

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Export activity logs to CSV format.
     */
    public function exportCsv(Request $request)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            abort(403, 'ログエクスポート機能にアクセスする権限がありません。');
        }

        // Build query with same filters as index
        $query = ActivityLog::with('user');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->input('target_type'));
        }

        if ($request->filled('start_date')) {
            $startDate = $request->input('start_date') . ' 00:00:00';
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = $request->input('end_date') . ' 23:59:59';
            $query->where('created_at', '<=', $endDate);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->input('ip_address') . '%');
        }

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        // Get all matching logs (no pagination for export)
        $logs = $query->get();

        // Generate filename with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "activity_logs_{$timestamp}.csv";

        // Create CSV content
        $csvContent = $this->generateCsvContent($logs);

        // Return CSV download response
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate CSV content from activity logs.
     */
    private function generateCsvContent($logs): string
    {
        // Add BOM for UTF-8 to prevent character encoding issues in Excel
        $csvContent = "\xEF\xBB\xBF";

        // CSV headers
        $headers = [
            'ログID',
            '日時',
            'ユーザーID',
            'ユーザー名',
            'メールアドレス',
            'ユーザーロール',
            '操作種別',
            '対象種別',
            '対象ID',
            '操作内容',
            'IPアドレス',
            'ユーザーエージェント'
        ];

        // Add headers to CSV
        $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $headers)) . "\n";

        // Add data rows
        foreach ($logs as $log) {
            $row = [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user_id,
                $log->user ? $log->user->name : 'Unknown User',
                $log->user ? $log->user->email : '',
                $log->user ? $log->user->role : '',
                $log->action,
                $log->target_type,
                $log->target_id ?? '',
                $log->description,
                $log->ip_address,
                $log->user_agent
            ];

            $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $row)) . "\n";
        }

        return $csvContent;
    }

    /**
     * Escape CSV field to handle commas, quotes, and newlines.
     */
    private function escapeCsvField($field): string
    {
        // Convert to string and handle null values
        $field = (string) $field;
        
        // If field contains comma, quote, or newline, wrap in quotes and escape internal quotes
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            $field = '"' . str_replace('"', '""', $field) . '"';
        }
        
        return $field;
    }

    /**
     * Generate audit report in CSV format with summary statistics.
     */
    public function exportAuditReport(Request $request)
    {
        // Check if user has admin role
        if (Auth::user()->role !== 'admin') {
            abort(403, 'ログエクスポート機能にアクセスする権限がありません。');
        }

        // Set default date range if not provided (last 30 days)
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Build query for the specified date range
        $query = ActivityLog::with('user')
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->orderBy('created_at', 'desc');

        $logs = $query->get();

        // Generate statistics
        $stats = $this->generateAuditStatistics($logs, $startDate, $endDate);

        // Generate filename
        $filename = "audit_report_{$startDate}_to_{$endDate}.csv";

        // Create CSV content with statistics and detailed logs
        $csvContent = $this->generateAuditReportCsv($logs, $stats, $startDate, $endDate);

        // Return CSV download response
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Generate audit statistics for the report.
     */
    private function generateAuditStatistics($logs, $startDate, $endDate): array
    {
        $stats = [
            'period' => $startDate . ' から ' . $endDate,
            'total_logs' => $logs->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->count(),
            'action_breakdown' => $logs->groupBy('action')->map->count()->toArray(),
            'target_breakdown' => $logs->groupBy('target_type')->map->count()->toArray(),
            'daily_activity' => $logs->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d');
            })->map->count()->toArray(),
            'user_activity' => $logs->groupBy('user_id')->map(function ($userLogs) {
                $user = $userLogs->first()->user;
                return [
                    'name' => $user ? $user->name : 'Unknown User',
                    'email' => $user ? $user->email : '',
                    'count' => $userLogs->count()
                ];
            })->toArray()
        ];

        return $stats;
    }

    /**
     * Generate audit report CSV content.
     */
    private function generateAuditReportCsv($logs, $stats, $startDate, $endDate): string
    {
        // Add BOM for UTF-8
        $csvContent = "\xEF\xBB\xBF";

        // Report header
        $csvContent .= "監査レポート\n";
        $csvContent .= "生成日時," . now()->format('Y-m-d H:i:s') . "\n";
        $csvContent .= "対象期間," . $stats['period'] . "\n";
        $csvContent .= "総ログ数," . $stats['total_logs'] . "\n";
        $csvContent .= "アクティブユーザー数," . $stats['unique_users'] . "\n";
        $csvContent .= "\n";

        // Action breakdown
        $csvContent .= "操作種別別統計\n";
        $csvContent .= "操作種別,件数\n";
        foreach ($stats['action_breakdown'] as $action => $count) {
            $csvContent .= $this->escapeCsvField($action) . "," . $count . "\n";
        }
        $csvContent .= "\n";

        // Target type breakdown
        $csvContent .= "対象種別別統計\n";
        $csvContent .= "対象種別,件数\n";
        foreach ($stats['target_breakdown'] as $targetType => $count) {
            $csvContent .= $this->escapeCsvField($targetType) . "," . $count . "\n";
        }
        $csvContent .= "\n";

        // User activity breakdown
        $csvContent .= "ユーザー別活動統計\n";
        $csvContent .= "ユーザー名,メールアドレス,操作回数\n";
        foreach ($stats['user_activity'] as $userId => $userStats) {
            $csvContent .= $this->escapeCsvField($userStats['name']) . ",";
            $csvContent .= $this->escapeCsvField($userStats['email']) . ",";
            $csvContent .= $userStats['count'] . "\n";
        }
        $csvContent .= "\n";

        // Daily activity
        $csvContent .= "日別活動統計\n";
        $csvContent .= "日付,操作回数\n";
        ksort($stats['daily_activity']);
        foreach ($stats['daily_activity'] as $date => $count) {
            $csvContent .= $date . "," . $count . "\n";
        }
        $csvContent .= "\n";

        // Detailed logs
        $csvContent .= "詳細ログ\n";
        $headers = [
            'ログID',
            '日時',
            'ユーザー名',
            'メールアドレス',
            '操作種別',
            '対象種別',
            '対象ID',
            '操作内容',
            'IPアドレス'
        ];
        $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $headers)) . "\n";

        foreach ($logs as $log) {
            $row = [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user ? $log->user->name : 'Unknown User',
                $log->user ? $log->user->email : '',
                $log->action,
                $log->target_type,
                $log->target_id ?? '',
                $log->description,
                $log->ip_address
            ];

            $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $row)) . "\n";
        }

        return $csvContent;
    }
}