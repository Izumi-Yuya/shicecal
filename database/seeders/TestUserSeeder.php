<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create test users for different roles
        User::create([
            'name' => 'システム管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'department' => '総務部',
            'access_scope' => ['all'],
            'is_active' => true,
        ]);

        User::create([
            'name' => '編集者',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EDITOR,
            'department' => '施設管理部',
            'access_scope' => ['tokyo', 'osaka'],
            'is_active' => true,
        ]);

        User::create([
            'name' => '承認者',
            'email' => 'approver@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_APPROVER,
            'department' => '営業部',
            'access_scope' => ['all'],
            'is_active' => true,
        ]);

        User::create([
            'name' => '閲覧者',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'department' => '技術部',
            'access_scope' => ['tokyo'],
            'is_active' => true,
        ]);
    }
}