<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacilityBasicInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザーを取得（存在しない場合は作成）
        $adminUser = User::where('email', 'admin@example.com')->first();
        if (!$adminUser) {
            $adminUser = User::create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]);
        }

        // サンプル施設データ
        $facilities = [
            [
                'company_name' => '株式会社サンプル介護',
                'office_code' => 'SC001',
                'designation_number' => '1234567890',
                'facility_name' => 'サンプル介護施設',
                'postal_code' => '1000001',
                'address' => '東京都千代田区千代田1-1-1',
                'building_name' => 'サンプルビル3階',
                'phone_number' => '03-1234-5678',
                'fax_number' => '03-1234-5679',
                'toll_free_number' => '0120-123-456',
                'email' => 'info@sample-care.co.jp',
                'website_url' => 'https://www.sample-care.co.jp',
                'opening_date' => '2020-04-01',
                'years_in_operation' => 5,
                'building_structure' => '鉄筋コンクリート造',
                'building_floors' => 3,
                'paid_rooms_count' => 30,
                'ss_rooms_count' => 5,
                'capacity' => 35,
                'service_types' => ['介護付有料老人ホーム', 'デイサービス'],
                'designation_renewal_date' => '2026-03-31',
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminUser->id,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'company_name' => '医療法人健康会',
                'office_code' => 'KK002',
                'designation_number' => '2345678901',
                'facility_name' => '健康の里デイサービスセンター',
                'postal_code' => '1500001',
                'address' => '東京都渋谷区神宮前1-2-3',
                'building_name' => '健康ビル1階',
                'phone_number' => '03-2345-6789',
                'fax_number' => '03-2345-6790',
                'toll_free_number' => '0120-234-567',
                'email' => 'contact@kenkou-sato.or.jp',
                'website_url' => 'https://www.kenkou-sato.or.jp',
                'opening_date' => '2018-10-01',
                'years_in_operation' => 6,
                'building_structure' => '鉄骨造',
                'building_floors' => 2,
                'paid_rooms_count' => 0,
                'ss_rooms_count' => 0,
                'capacity' => 25,
                'service_types' => ['通所介護', '認知症対応型通所介護'],
                'designation_renewal_date' => '2024-09-30',
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminUser->id,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
            [
                'company_name' => '社会福祉法人みらい',
                'office_code' => 'MR003',
                'designation_number' => '3456789012',
                'facility_name' => 'みらい特別養護老人ホーム',
                'postal_code' => '1600023',
                'address' => '東京都新宿区西新宿2-3-4',
                'building_name' => 'みらいタワー',
                'phone_number' => '03-3456-7890',
                'fax_number' => '03-3456-7891',
                'toll_free_number' => '0120-345-678',
                'email' => 'info@mirai-fukushi.or.jp',
                'website_url' => 'https://www.mirai-fukushi.or.jp',
                'opening_date' => '2015-04-01',
                'years_in_operation' => 10,
                'building_structure' => '鉄筋コンクリート造',
                'building_floors' => 5,
                'paid_rooms_count' => 80,
                'ss_rooms_count' => 10,
                'capacity' => 90,
                'service_types' => ['特別養護老人ホーム', 'ショートステイ', '居宅介護支援'],
                'designation_renewal_date' => '2025-03-31',
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminUser->id,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ],
        ];

        foreach ($facilities as $facilityData) {
            Facility::create($facilityData);
        }

        $this->command->info('施設基本情報のサンプルデータを作成しました。');
    }
}