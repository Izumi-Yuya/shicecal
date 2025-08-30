<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin users
        $adminUsers = [
            [
                'name' => 'システム管理者',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'department' => 'システム管理部',
                'access_scope' => null,
            ],
            [
                'name' => '副管理者',
                'email' => 'sub-admin@example.com',
                'role' => 'admin',
                'department' => 'システム管理部',
                'access_scope' => null,
            ],
        ];

        // Create editor users
        $editorUsers = [
            [
                'name' => '編集者（東京）',
                'email' => 'editor-tokyo@example.com',
                'role' => 'editor',
                'department' => '東京施設管理部',
                'access_scope' => null,
            ],
            [
                'name' => '編集者（大阪）',
                'email' => 'editor-osaka@example.com',
                'role' => 'editor',
                'department' => '大阪施設管理部',
                'access_scope' => null,
            ],
            [
                'name' => '編集者（全国）',
                'email' => 'editor@example.com',
                'role' => 'editor',
                'department' => '施設管理部',
                'access_scope' => null,
            ],
        ];

        // Create primary responder users
        $responderUsers = [
            [
                'name' => '一次対応者（東日本）',
                'email' => 'responder-east@example.com',
                'role' => 'primary_responder',
                'department' => '東日本サポート部',
                'access_scope' => null,
            ],
            [
                'name' => '一次対応者（西日本）',
                'email' => 'responder-west@example.com',
                'role' => 'primary_responder',
                'department' => '西日本サポート部',
                'access_scope' => null,
            ],
            [
                'name' => '一次対応者（全国）',
                'email' => 'responder@example.com',
                'role' => 'primary_responder',
                'department' => 'サポート部',
                'access_scope' => null,
            ],
        ];

        // Create approver users
        $approverUsers = [
            [
                'name' => '承認者（部長）',
                'email' => 'approver-manager@example.com',
                'role' => 'approver',
                'department' => '施設管理部',
                'access_scope' => null,
            ],
            [
                'name' => '承認者（課長）',
                'email' => 'approver@example.com',
                'role' => 'approver',
                'department' => '管理部',
                'access_scope' => null,
            ],
        ];

        // Create viewer users with different access scopes
        $viewerUsers = [
            [
                'name' => '閲覧者（全権限）',
                'email' => 'viewer-all@example.com',
                'role' => 'viewer',
                'department' => '営業部',
                'access_scope' => null, // 全施設アクセス可能
            ],
            [
                'name' => '地区担当者（関東）',
                'email' => 'viewer-kanto@example.com',
                'role' => 'viewer',
                'department' => '関東支社',
                'access_scope' => ['region' => 'kanto', 'prefectures' => ['東京都', '神奈川県', '千葉県', '埼玉県']],
            ],
            [
                'name' => '地区担当者（関西）',
                'email' => 'viewer-kansai@example.com',
                'role' => 'viewer',
                'department' => '関西支社',
                'access_scope' => ['region' => 'kansai', 'prefectures' => ['大阪府', '京都府', '兵庫県', '奈良県']],
            ],
            [
                'name' => '地区担当者（中部）',
                'email' => 'viewer-chubu@example.com',
                'role' => 'viewer',
                'department' => '中部支社',
                'access_scope' => ['region' => 'chubu', 'prefectures' => ['愛知県', '静岡県', '岐阜県']],
            ],
            [
                'name' => '地区担当者（九州）',
                'email' => 'viewer-kyushu@example.com',
                'role' => 'viewer',
                'department' => '九州支社',
                'access_scope' => ['region' => 'kyushu', 'prefectures' => ['福岡県', '熊本県', '鹿児島県']],
            ],
            [
                'name' => '地区担当者（北海道）',
                'email' => 'viewer-hokkaido@example.com',
                'role' => 'viewer',
                'department' => '北海道支社',
                'access_scope' => ['region' => 'hokkaido', 'prefectures' => ['北海道']],
            ],
            [
                'name' => '部門責任者（営業）',
                'email' => 'manager-sales@example.com',
                'role' => 'viewer',
                'department' => '営業部',
                'access_scope' => null,
            ],
            [
                'name' => '部門責任者（総務）',
                'email' => 'manager-admin@example.com',
                'role' => 'viewer',
                'department' => '総務部',
                'access_scope' => null,
            ],
            // Legacy test users for backward compatibility
            [
                'name' => '閲覧者テスト',
                'email' => 'viewer@example.com',
                'role' => 'viewer',
                'department' => '営業部',
                'access_scope' => null,
            ],
            [
                'name' => '地区担当者テスト',
                'email' => 'regional@example.com',
                'role' => 'viewer',
                'department' => '東京支社',
                'access_scope' => ['region' => 'tokyo', 'prefectures' => ['東京都', '神奈川県']],
            ],
            [
                'name' => '部門責任者テスト',
                'email' => 'manager@example.com',
                'role' => 'viewer',
                'department' => '関西支社',
                'access_scope' => ['region' => 'kansai', 'prefectures' => ['大阪府', '京都府', '兵庫県']],
            ],
        ];

        // Combine all users
        $allUsers = array_merge($adminUsers, $editorUsers, $responderUsers, $approverUsers, $viewerUsers);

        foreach ($allUsers as $userData) {
            if (!User::where('email', $userData['email'])->exists()) {
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'role' => $userData['role'],
                    'department' => $userData['department'],
                    'access_scope' => $userData['access_scope'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Created ' . count($allUsers) . ' test users across all roles.');
    }
}