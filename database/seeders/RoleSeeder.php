<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create users for each role to demonstrate role functionality
        
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@shisecal.com'],
            [
                'name' => 'システム管理者',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'department' => '総務部',
                'access_scope' => ['all'],
                'is_active' => true,
            ]
        );

        // Editor user
        User::firstOrCreate(
            ['email' => 'editor@shisecal.com'],
            [
                'name' => '編集者',
                'password' => Hash::make('editor123'),
                'role' => User::ROLE_EDITOR,
                'department' => '施設管理部',
                'access_scope' => ['tokyo', 'osaka', 'nagoya'],
                'is_active' => true,
            ]
        );

        // Primary responder user
        User::firstOrCreate(
            ['email' => 'responder@shisecal.com'],
            [
                'name' => '一次対応者',
                'password' => Hash::make('responder123'),
                'role' => User::ROLE_PRIMARY_RESPONDER,
                'department' => '技術部',
                'access_scope' => ['tokyo'],
                'is_active' => true,
            ]
        );

        // Approver user
        User::firstOrCreate(
            ['email' => 'approver@shisecal.com'],
            [
                'name' => '承認者',
                'password' => Hash::make('approver123'),
                'role' => User::ROLE_APPROVER,
                'department' => '営業部',
                'access_scope' => ['all'],
                'is_active' => true,
            ]
        );

        // Viewer user
        User::firstOrCreate(
            ['email' => 'viewer@shisecal.com'],
            [
                'name' => '閲覧者',
                'password' => Hash::make('viewer123'),
                'role' => User::ROLE_VIEWER,
                'department' => '経理部',
                'access_scope' => ['osaka'],
                'is_active' => true,
            ]
        );

        // Department manager (viewer with broader scope)
        User::firstOrCreate(
            ['email' => 'manager@shisecal.com'],
            [
                'name' => '部門責任者',
                'password' => Hash::make('manager123'),
                'role' => User::ROLE_VIEWER,
                'department' => '営業部',
                'access_scope' => ['tokyo', 'osaka'],
                'is_active' => true,
            ]
        );

        // Executive (viewer with all access)
        User::firstOrCreate(
            ['email' => 'executive@shisecal.com'],
            [
                'name' => '役員',
                'password' => Hash::make('executive123'),
                'role' => User::ROLE_VIEWER,
                'department' => '経営企画部',
                'access_scope' => ['all'],
                'is_active' => true,
            ]
        );
    }
}