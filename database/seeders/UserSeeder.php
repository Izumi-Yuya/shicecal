<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---------------------------------------------------
        // 固定の管理者ユーザー
        // ---------------------------------------------------
        $adminUsers = [
            [
                'name' => 'システム管理者',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'department' => 'land_affairs', // 管理者は土地総務権限も持つ
                'access_scope' => 'all_facilities', // 管理者は全事業所アクセス
            ],
            [
                'name' => '副管理者',
                'email' => 'sub-admin@example.com',
                'role' => 'admin',
                'department' => 'land_affairs',
                'access_scope' => 'all_facilities', // 管理者は全事業所アクセス
            ],
        ];

        // ---------------------------------------------------
        // JSONから読み込むユーザー
        // ---------------------------------------------------
        $jsonPath = database_path('seeders/users.json');
        $jsonUsers = [];

        if (file_exists($jsonPath)) {
            $jsonData = json_decode(file_get_contents($jsonPath), true);
            $jsonUsers = $jsonData['users'] ?? $jsonData;
        } else {
            $this->command->warn("JSON file not found at: {$jsonPath}");
        }

        // ---------------------------------------------------
        // マージして登録処理
        // ---------------------------------------------------
        $allUsers = array_merge($adminUsers, $jsonUsers);
        $count = 0;

        foreach ($allUsers as $userData) {
            $name        = $userData['users.name'] ?? $userData['name'] ?? null;
            $email       = $userData['users.email'] ?? $userData['email'] ?? null;
            $role        = $userData['users.role'] ?? $userData['role'] ?? 'viewer';
            $department  = $userData['users.department'] ?? $userData['department'] ?? null;
            $accessScope = $userData['users.access_scope'] ?? $userData['access_scope'] ?? null;

            // Convert Japanese access scope values to English constants
            if ($accessScope) {
                // Check if it's already an English constant (for admin users)
                $validEnglishValues = ['all_facilities', 'assigned_facility', 'own_facility'];
                if (!in_array($accessScope, $validEnglishValues)) {
                    // It's a Japanese value, convert it
                    $accessScope = $this->mapJapaneseToEnglish($accessScope);
                }
                // If it's already English, keep it as is
            } else {
                $accessScope = 'all_facilities'; // Default value
            }

            if (! $email) {
                $this->command->warn("Skipping user '{$name}' because email is missing.");
                continue;
            }

            if (! User::where('email', $email)->exists()) {
                User::create([
                    'name'        => $name,
                    'email'       => $email,
                    'password'    => Hash::make('password'),
                    'role'        => $role,
                    'department'  => $department,
                    'access_scope'=> $accessScope,
                    'is_active'   => true,
                ]);
                $count++;
            }
        }

        $this->command->info("Seeded {$count} users (including fixed admins).");
    }

    /**
     * Map Japanese access scope values to English constants.
     */
    private function mapJapaneseToEnglish(string $japaneseValue): string
    {
        $mapping = [
            '全事業所' => 'all_facilities',
            '担当エリアの事業所（複数）' => 'assigned_facility',
            '担当エリアのみ閲覧（複数）' => 'assigned_facility',
            '自施設のみ' => 'own_facility',
        ];

        return $mapping[$japaneseValue] ?? 'all_facilities';
    }
}
