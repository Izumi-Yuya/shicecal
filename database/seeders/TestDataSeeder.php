<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AnnualConfirmation;
use App\Models\Comment;
use App\Models\ExportFavorite;
use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceSearchFavorite;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createComments();
        $this->createExportFavorites();
        $this->createMaintenanceHistories();
        $this->createMaintenanceSearchFavorites();
        $this->createNotifications();
        $this->createAnnualConfirmations();
        $this->createActivityLogs();
    }

    /**
     * Create test comments.
     */
    private function createComments(): void
    {
        $facilities = Facility::approved()->take(3)->get();
        $viewer = User::where('role', 'viewer')->first();
        $responder = User::where('role', 'primary_responder')->first();

        if ($facilities->isEmpty() || !$viewer || !$responder) {
            $this->command->warn('Insufficient data for creating comments. Please run other seeders first.');
            return;
        }

        $comments = [
            [
                'facility_id' => $facilities[0]->id,
                'field_name' => 'facility_name',
                'content' => '施設名の表記に誤りがあります。正しくは「東京本社ビル新館」です。',
                'status' => 'pending',
                'posted_by' => $viewer->id,
                'assigned_to' => $responder->id,
            ],
            [
                'facility_id' => $facilities[0]->id,
                'field_name' => 'phone_number',
                'content' => '電話番号が古い番号になっています。最新の番号に更新をお願いします。',
                'status' => 'in_progress',
                'posted_by' => $viewer->id,
                'assigned_to' => $responder->id,
            ],
            [
                'facility_id' => $facilities[1]->id,
                'field_name' => 'address',
                'content' => '住所の建物名が変更されています。確認をお願いします。',
                'status' => 'resolved',
                'posted_by' => $viewer->id,
                'assigned_to' => $responder->id,
                'resolved_at' => now()->subDays(2),
            ],
            [
                'facility_id' => $facilities[2]->id,
                'field_name' => 'designation_number',
                'content' => '指定番号の桁数が正しくないようです。',
                'status' => 'pending',
                'posted_by' => $viewer->id,
                'assigned_to' => $responder->id,
            ],
        ];

        foreach ($comments as $commentData) {
            Comment::create($commentData);
        }

        $this->command->info('Created ' . count($comments) . ' test comments.');
    }

    /**
     * Create test export favorites.
     */
    private function createExportFavorites(): void
    {
        $users = User::whereIn('role', ['admin', 'editor', 'viewer'])->get();
        $facilities = Facility::approved()->pluck('id')->toArray();

        if ($users->isEmpty() || empty($facilities)) {
            $this->command->warn('Insufficient data for creating export favorites.');
            return;
        }

        $favorites = [
            [
                'user_id' => $users[0]->id,
                'name' => '基本情報出力',
                'facility_ids' => array_slice($facilities, 0, 3),
                'export_fields' => ['facility_name', 'address', 'phone_number', 'status'],
            ],
            [
                'user_id' => $users[0]->id,
                'name' => '全項目出力',
                'facility_ids' => array_slice($facilities, 0, 5),
                'export_fields' => [
                    'company_name', 'office_code', 'designation_number', 
                    'facility_name', 'postal_code', 'address', 
                    'phone_number', 'fax_number', 'status'
                ],
            ],
        ];

        if (count($users) > 1) {
            $favorites[] = [
                'user_id' => $users[1]->id,
                'name' => '関東地区施設',
                'facility_ids' => array_slice($facilities, 0, 2),
                'export_fields' => ['facility_name', 'address', 'phone_number'],
            ];
        }

        foreach ($favorites as $favoriteData) {
            ExportFavorite::create($favoriteData);
        }

        $this->command->info('Created ' . count($favorites) . ' export favorites.');
    }

    /**
     * Create test maintenance histories.
     */
    private function createMaintenanceHistories(): void
    {
        $facilities = Facility::approved()->take(3)->get();
        $editor = User::where('role', 'editor')->first();

        if ($facilities->isEmpty() || !$editor) {
            $this->command->warn('Insufficient data for creating maintenance histories.');
            return;
        }

        $maintenances = [
            [
                'facility_id' => $facilities[0]->id,
                'maintenance_date' => now()->subDays(30)->toDateString(),
                'content' => 'エアコンフィルター交換作業',
                'cost' => 15000.00,
                'contractor' => '株式会社メンテナンス東京',
                'created_by' => $editor->id,
            ],
            [
                'facility_id' => $facilities[0]->id,
                'maintenance_date' => now()->subDays(60)->toDateString(),
                'content' => '消防設備点検',
                'cost' => 50000.00,
                'contractor' => '東京消防設備株式会社',
                'created_by' => $editor->id,
            ],
            [
                'facility_id' => $facilities[1]->id,
                'maintenance_date' => now()->subDays(15)->toDateString(),
                'content' => '電気設備定期点検',
                'cost' => 80000.00,
                'contractor' => '関西電気工事株式会社',
                'created_by' => $editor->id,
            ],
            [
                'facility_id' => $facilities[1]->id,
                'maintenance_date' => now()->subDays(45)->toDateString(),
                'content' => '給排水設備清掃',
                'cost' => 25000.00,
                'contractor' => '大阪設備メンテナンス',
                'created_by' => $editor->id,
            ],
            [
                'facility_id' => $facilities[2]->id,
                'maintenance_date' => now()->subDays(7)->toDateString(),
                'content' => 'エレベーター定期点検',
                'cost' => 120000.00,
                'contractor' => '横浜エレベーター株式会社',
                'created_by' => $editor->id,
            ],
        ];

        foreach ($maintenances as $maintenanceData) {
            MaintenanceHistory::create($maintenanceData);
        }

        $this->command->info('Created ' . count($maintenances) . ' maintenance histories.');
    }

    /**
     * Create test maintenance search favorites.
     */
    private function createMaintenanceSearchFavorites(): void
    {
        $users = User::whereIn('role', ['admin', 'editor'])->get();
        $facilities = Facility::approved()->pluck('id')->toArray();

        if ($users->isEmpty() || empty($facilities)) {
            $this->command->warn('Insufficient data for creating maintenance search favorites.');
            return;
        }

        $searchFavorites = [
            [
                'user_id' => $users[0]->id,
                'name' => '今月の点検作業',
                'facility_id' => $facilities[0] ?? null,
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'search_content' => '点検',
            ],
            [
                'user_id' => $users[0]->id,
                'name' => '高額メンテナンス',
                'facility_id' => null, // All facilities
                'start_date' => now()->subMonths(6)->toDateString(),
                'end_date' => now()->toDateString(),
                'search_content' => null,
            ],
        ];

        if (count($users) > 1 && count($facilities) > 1) {
            $searchFavorites[] = [
                'user_id' => $users[1]->id,
                'name' => '設備関連作業',
                'facility_id' => $facilities[1],
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->toDateString(),
                'search_content' => '設備',
            ];
        }

        foreach ($searchFavorites as $favoriteData) {
            MaintenanceSearchFavorite::create($favoriteData);
        }

        $this->command->info('Created ' . count($searchFavorites) . ' maintenance search favorites.');
    }

    /**
     * Create test notifications.
     */
    private function createNotifications(): void
    {
        $responder = User::where('role', 'primary_responder')->first();
        $viewer = User::where('role', 'viewer')->first();
        $facility = Facility::approved()->first();

        if (!$responder || !$viewer || !$facility) {
            $this->command->warn('Insufficient data for creating notifications.');
            return;
        }

        $notifications = [
            [
                'user_id' => $responder->id,
                'type' => 'comment_posted',
                'title' => '新しいコメントが投稿されました',
                'message' => $viewer->name . ' さんが施設「' . $facility->facility_name . '」にコメントを投稿しました。',
                'data' => [
                    'comment_id' => 1,
                    'facility_id' => $facility->id,
                    'poster_id' => $viewer->id,
                    'field_name' => 'facility_name',
                ],
                'is_read' => false,
                'email_sent' => true,
                'email_sent_at' => now()->subHours(2),
            ],
            [
                'user_id' => $viewer->id,
                'type' => 'comment_status_changed',
                'title' => 'コメントのステータスが更新されました',
                'message' => '施設「' . $facility->facility_name . '」のあなたのコメントのステータスが「未対応」から「対応中」に変更されました。',
                'data' => [
                    'comment_id' => 1,
                    'facility_id' => $facility->id,
                    'old_status' => 'pending',
                    'new_status' => 'in_progress',
                    'assignee_id' => $responder->id,
                ],
                'is_read' => true,
                'read_at' => now()->subHours(1),
                'email_sent' => true,
                'email_sent_at' => now()->subHours(1),
            ],
        ];

        foreach ($notifications as $notificationData) {
            Notification::create($notificationData);
        }

        $this->command->info('Created ' . count($notifications) . ' test notifications.');
    }

    /**
     * Create test annual confirmations.
     */
    private function createAnnualConfirmations(): void
    {
        $facilities = Facility::approved()->take(5)->get();
        $admin = User::where('role', 'admin')->first();
        $viewers = User::where('role', 'viewer')->get();

        if ($facilities->isEmpty() || !$admin || $viewers->isEmpty()) {
            $this->command->warn('Insufficient data for creating annual confirmations.');
            return;
        }

        $confirmations = [];
        
        foreach ($facilities as $index => $facility) {
            $viewer = $viewers->get($index % $viewers->count());
            
            // Create completed confirmation
            $confirmations[] = [
                'facility_id' => $facility->id,
                'confirmation_year' => now()->year - 1,
                'requested_by' => $admin->id,
                'requested_at' => now()->subMonths(3),
                'facility_manager_id' => $viewer->id,
                'responded_at' => now()->subMonths(2)->subDays(rand(1, 20)),
                'status' => 'confirmed',
                'discrepancy_details' => null,
            ];

            // Create pending confirmation for some facilities
            if ($index < 2) {
                $confirmations[] = [
                    'facility_id' => $facility->id,
                    'confirmation_year' => now()->year,
                    'requested_by' => $admin->id,
                    'requested_at' => now()->subDays(rand(1, 10)),
                    'facility_manager_id' => $viewer->id,
                    'responded_at' => null,
                    'status' => 'pending',
                    'discrepancy_details' => null,
                ];
            }

            // Create discrepancy confirmation for one facility
            if ($index === 2) {
                $confirmations[] = [
                    'facility_id' => $facility->id,
                    'confirmation_year' => now()->year,
                    'requested_by' => $admin->id,
                    'requested_at' => now()->subDays(15),
                    'facility_manager_id' => $viewer->id,
                    'responded_at' => now()->subDays(10),
                    'status' => 'discrepancy_reported',
                    'discrepancy_details' => '電話番号と住所に相違があります。最新の情報に更新が必要です。',
                ];
            }
        }

        foreach ($confirmations as $confirmationData) {
            // Check if confirmation already exists for this facility and year
            $existing = \App\Models\AnnualConfirmation::where('facility_id', $confirmationData['facility_id'])
                ->where('confirmation_year', $confirmationData['confirmation_year'])
                ->first();
                
            if (!$existing) {
                \App\Models\AnnualConfirmation::create($confirmationData);
            }
        }

        $this->command->info('Created ' . count($confirmations) . ' annual confirmations.');
    }

    /**
     * Create test activity logs.
     */
    private function createActivityLogs(): void
    {
        $users = User::all();
        $facilities = Facility::all();

        if ($users->isEmpty() || $facilities->isEmpty()) {
            $this->command->warn('Insufficient data for creating activity logs.');
            return;
        }

        $activities = [];
        $actions = [
            'login' => 'ログイン',
            'logout' => 'ログアウト',
            'facility_view' => '施設情報閲覧',
            'facility_create' => '施設情報作成',
            'facility_update' => '施設情報更新',
            'facility_delete' => '施設情報削除',
            'comment_post' => 'コメント投稿',
            'comment_status_update' => 'コメントステータス更新',
            'file_upload' => 'ファイルアップロード',
            'file_download' => 'ファイルダウンロード',
            'csv_export' => 'CSV出力',
            'pdf_export' => 'PDF出力',
            'maintenance_create' => '修繕履歴作成',
            'maintenance_update' => '修繕履歴更新',
            'user_create' => 'ユーザー作成',
            'user_update' => 'ユーザー更新',
            'settings_update' => 'システム設定更新',
        ];

        // Create activity logs for the past 30 days
        for ($i = 0; $i < 100; $i++) {
            $user = $users->random();
            $facility = $facilities->random();
            $action = array_rand($actions);
            $actionName = $actions[$action];
            
            $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            
            // Determine target type and ID based on action
            $targetType = 'App\\Models\\User'; // Default target type
            $targetId = $user->id; // Default target ID
            
            if (in_array($action, ['facility_view', 'facility_create', 'facility_update', 'facility_delete', 'comment_post', 'file_upload', 'maintenance_create'])) {
                $targetType = 'App\\Models\\Facility';
                $targetId = $facility->id;
            } elseif (in_array($action, ['user_create', 'user_update'])) {
                $targetType = 'App\\Models\\User';
                $targetId = $users->random()->id;
            } elseif ($action === 'settings_update') {
                $targetType = 'App\\Models\\SystemSetting';
                $targetId = 1; // Assuming system settings have IDs
            }
            
            $activities[] = [
                'user_id' => $user->id,
                'action' => $action,
                'description' => $actionName,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'ip_address' => $this->generateRandomIP(),
                'user_agent' => $this->generateRandomUserAgent(),
                'created_at' => $createdAt,
            ];
        }

        foreach ($activities as $activityData) {
            \App\Models\ActivityLog::create($activityData);
        }

        $this->command->info('Created ' . count($activities) . ' activity logs.');
    }

    /**
     * Generate random IP address for testing.
     */
    private function generateRandomIP(): string
    {
        $ips = [
            '192.168.1.' . rand(1, 254),
            '10.0.0.' . rand(1, 254),
            '172.16.0.' . rand(1, 254),
            '203.104.209.' . rand(1, 254),
            '133.106.33.' . rand(1, 254),
        ];
        
        return $ips[array_rand($ips)];
    }

    /**
     * Generate random user agent for testing.
     */
    private function generateRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ];
        
        return $userAgents[array_rand($userAgents)];
    }


}