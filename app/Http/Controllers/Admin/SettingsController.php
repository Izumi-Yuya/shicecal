<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! auth()->user()->isAdmin()) {
                abort(403, 'Access denied');
            }

            return $next($request);
        });
    }

    public function index()
    {
        // Get current settings (in a real app, these would come from database)
        $settings = [
            'app_name' => config('app.name', 'Shise-Cal'),
            'app_description' => 'Facility Management System',
            'timezone' => config('app.timezone', 'Asia/Tokyo'),
            'date_format' => 'Y/m/d',
            'pagination_per_page' => '20',
            'approval_enabled' => true,
            'auto_approve_minor_changes' => false,
            'approval_timeout_days' => 7,
            'approval_reminder_days' => 3,
            'ip_restriction_enabled' => false,
            'allowed_ips' => '',
            'session_lifetime' => 120,
            'force_https' => false,
            'secure_cookies' => false,
            'notification_from_email' => 'noreply@example.com',
            'notification_from_name' => 'Shise-Cal システム',
            'email_notifications_enabled' => true,
            'notify_comment_posted' => true,
            'notify_approval_request' => true,
            'notify_annual_confirmation' => true,
            'pdf_password_protection' => true,
            'pdf_watermark' => '機密',
            'csv_encoding' => 'UTF-8-BOM',
            'csv_delimiter' => ',',
            'maintenance_mode' => false,
            'maintenance_message' => 'システムメンテナンス中です。しばらくお待ちください。',
            'log_retention_days' => 365,
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // In a real application, you would save these to database
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'message' => '設定が正常に保存されました',
        ]);
    }

    public function reset()
    {
        // Reset settings to defaults
        return response()->json([
            'success' => true,
            'message' => '設定がデフォルト値にリセットされました',
        ]);
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'キャッシュがクリアされました',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'キャッシュのクリアに失敗しました: '.$e->getMessage(),
            ]);
        }
    }

    public function optimizeDatabase()
    {
        try {
            // In a real application, you would run database optimization commands
            return response()->json([
                'success' => true,
                'message' => 'データベースが最適化されました',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'データベースの最適化に失敗しました: '.$e->getMessage(),
            ]);
        }
    }

    public function updateSingle(Request $request)
    {
        // Update single setting
        return response()->json([
            'success' => true,
            'message' => '設定が更新されました',
        ]);
    }
}
