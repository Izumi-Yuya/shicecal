<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = \App\Models\User::where('role', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->warn('No admin user found. Please run AdminUserSeeder first.');
            return;
        }

        $settings = [
            // Core system settings
            [
                'key' => 'approval_enabled',
                'value' => 'true',
                'description' => '承認機能の有効/無効設定',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'system_maintenance_mode',
                'value' => 'false',
                'description' => 'システムメンテナンスモード',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'system_name',
                'value' => '施設管理システム',
                'description' => 'システム名称',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'system_version',
                'value' => '1.0.0',
                'description' => 'システムバージョン',
                'updated_by' => $adminUser->id,
            ],

            // File management settings
            [
                'key' => 'max_file_size',
                'value' => '10240', // 10MB in KB
                'description' => 'アップロード可能な最大ファイルサイズ（KB）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'allowed_file_types',
                'value' => 'pdf',
                'description' => 'アップロード可能なファイル形式',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'file_storage_path',
                'value' => 'facilities',
                'description' => 'ファイル保存パス',
                'updated_by' => $adminUser->id,
            ],

            // Security settings
            [
                'key' => 'session_timeout',
                'value' => '120', // 120 minutes
                'description' => 'セッションタイムアウト時間（分）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'password_min_length',
                'value' => '8',
                'description' => 'パスワード最小文字数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'login_attempt_limit',
                'value' => '5',
                'description' => 'ログイン試行回数制限',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'account_lockout_duration',
                'value' => '30', // 30 minutes
                'description' => 'アカウントロック時間（分）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'ip_restriction_enabled',
                'value' => 'false',
                'description' => 'IP制限機能の有効/無効',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'allowed_ip_addresses',
                'value' => '',
                'description' => '許可IPアドレス（カンマ区切り）',
                'updated_by' => $adminUser->id,
            ],

            // Notification settings
            [
                'key' => 'notification_email_enabled',
                'value' => 'true',
                'description' => 'メール通知機能の有効/無効設定',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'notification_from_email',
                'value' => 'noreply@facility-system.com',
                'description' => '通知メール送信者アドレス',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'notification_from_name',
                'value' => '施設管理システム',
                'description' => '通知メール送信者名',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'email_notification_delay',
                'value' => '5', // 5 minutes
                'description' => 'メール通知遅延時間（分）',
                'updated_by' => $adminUser->id,
            ],

            // Export settings
            [
                'key' => 'csv_export_limit',
                'value' => '1000',
                'description' => 'CSV出力最大件数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'pdf_export_limit',
                'value' => '100',
                'description' => 'PDF出力最大件数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'export_timeout',
                'value' => '300', // 5 minutes
                'description' => 'エクスポート処理タイムアウト（秒）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'csv_encoding',
                'value' => 'UTF-8',
                'description' => 'CSV出力文字エンコーディング',
                'updated_by' => $adminUser->id,
            ],

            // PDF settings
            [
                'key' => 'pdf_password_protection',
                'value' => 'true',
                'description' => 'PDFパスワード保護の有効/無効',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'pdf_default_password',
                'value' => 'facility2024',
                'description' => 'PDFデフォルトパスワード',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'pdf_print_permission',
                'value' => 'false',
                'description' => 'PDF印刷許可',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'pdf_copy_permission',
                'value' => 'false',
                'description' => 'PDFコピー許可',
                'updated_by' => $adminUser->id,
            ],

            // Data retention settings
            [
                'key' => 'backup_retention_days',
                'value' => '30',
                'description' => 'バックアップ保持日数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'log_retention_days',
                'value' => '90',
                'description' => 'ログ保持日数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'activity_log_retention_days',
                'value' => '365',
                'description' => '操作ログ保持日数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'notification_retention_days',
                'value' => '30',
                'description' => '通知履歴保持日数',
                'updated_by' => $adminUser->id,
            ],

            // Annual confirmation settings
            [
                'key' => 'annual_confirmation_enabled',
                'value' => 'true',
                'description' => '年次確認機能の有効/無効',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'annual_confirmation_period',
                'value' => '30', // 30 days
                'description' => '年次確認期間（日）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'annual_confirmation_reminder_days',
                'value' => '7,3,1',
                'description' => '年次確認リマインダー日数（カンマ区切り）',
                'updated_by' => $adminUser->id,
            ],

            // UI/UX settings
            [
                'key' => 'items_per_page',
                'value' => '20',
                'description' => '1ページあたりの表示件数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'search_results_limit',
                'value' => '500',
                'description' => '検索結果最大件数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'auto_save_interval',
                'value' => '300', // 5 minutes
                'description' => '自動保存間隔（秒）',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'theme_color',
                'value' => '#007bff',
                'description' => 'テーマカラー',
                'updated_by' => $adminUser->id,
            ],

            // Maintenance settings
            [
                'key' => 'maintenance_history_retention_years',
                'value' => '5',
                'description' => '修繕履歴保持年数',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'maintenance_cost_alert_threshold',
                'value' => '100000',
                'description' => '修繕費用アラート閾値（円）',
                'updated_by' => $adminUser->id,
            ],

            // Comment settings
            [
                'key' => 'comment_auto_assignment',
                'value' => 'true',
                'description' => 'コメント自動割り当ての有効/無効',
                'updated_by' => $adminUser->id,
            ],
            [
                'key' => 'comment_response_deadline_days',
                'value' => '7',
                'description' => 'コメント対応期限（日）',
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Created ' . count($settings) . ' system settings.');
    }
}