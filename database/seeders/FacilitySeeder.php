<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $editor = User::where('role', 'editor')->first();
        $approver = User::where('role', 'approver')->first();

        if (!$editor) {
            $this->command->warn('No editor user found. Please run AdminUserSeeder first.');
            return;
        }

        $facilities = [
            // Tokyo area facilities
            [
                'company_name' => '株式会社テスト東京',
                'office_code' => 'TKY001',
                'designation_number' => '1234567890',
                'facility_name' => '東京本社ビル',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1-1',
                'phone_number' => '03-1234-5678',
                'fax_number' => '03-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(30),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト新宿',
                'office_code' => 'TKY002',
                'designation_number' => '1234567891',
                'facility_name' => '新宿支店',
                'postal_code' => '160-0022',
                'address' => '東京都新宿区新宿3-1-1',
                'phone_number' => '03-2345-6789',
                'fax_number' => '03-2345-6790',
                'status' => 'approved',
                'approved_at' => now()->subDays(28),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト渋谷',
                'office_code' => 'TKY003',
                'designation_number' => '1234567892',
                'facility_name' => '渋谷営業所',
                'postal_code' => '150-0002',
                'address' => '東京都渋谷区渋谷2-1-1',
                'phone_number' => '03-3456-7890',
                'fax_number' => '03-3456-7891',
                'status' => 'approved',
                'approved_at' => now()->subDays(26),
                'approved_by' => $approver?->id,
            ],

            // Kanagawa area facilities
            [
                'company_name' => '株式会社テスト横浜',
                'office_code' => 'YKH001',
                'designation_number' => '3456789012',
                'facility_name' => '横浜営業所',
                'postal_code' => '220-0001',
                'address' => '神奈川県横浜市西区みなとみらい1-1-1',
                'phone_number' => '045-1234-5678',
                'fax_number' => '045-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(20),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト川崎',
                'office_code' => 'KWS001',
                'designation_number' => '3456789013',
                'facility_name' => '川崎事業所',
                'postal_code' => '210-0001',
                'address' => '神奈川県川崎市川崎区駅前本町1-1-1',
                'phone_number' => '044-1234-5678',
                'fax_number' => '044-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(18),
                'approved_by' => $approver?->id,
            ],

            // Osaka area facilities
            [
                'company_name' => '株式会社テスト大阪',
                'office_code' => 'OSK001',
                'designation_number' => '2345678901',
                'facility_name' => '大阪支社',
                'postal_code' => '530-0001',
                'address' => '大阪府大阪市北区梅田1-1-1',
                'phone_number' => '06-1234-5678',
                'fax_number' => '06-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(25),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト難波',
                'office_code' => 'OSK002',
                'designation_number' => '2345678902',
                'facility_name' => '難波営業所',
                'postal_code' => '542-0076',
                'address' => '大阪府大阪市中央区難波1-1-1',
                'phone_number' => '06-2345-6789',
                'fax_number' => '06-2345-6790',
                'status' => 'approved',
                'approved_at' => now()->subDays(23),
                'approved_by' => $approver?->id,
            ],

            // Kyoto area facilities
            [
                'company_name' => '株式会社テスト京都',
                'office_code' => 'KYT001',
                'designation_number' => '4567890123',
                'facility_name' => '京都事業所',
                'postal_code' => '600-0001',
                'address' => '京都府京都市下京区四条通1-1-1',
                'phone_number' => '075-1234-5678',
                'fax_number' => '075-1234-5679',
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ],

            // Hyogo area facilities
            [
                'company_name' => '株式会社テスト神戸',
                'office_code' => 'KBE001',
                'designation_number' => '5678901234',
                'facility_name' => '神戸営業所',
                'postal_code' => '650-0001',
                'address' => '兵庫県神戸市中央区三宮町1-1-1',
                'phone_number' => '078-1234-5678',
                'fax_number' => '078-1234-5679',
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '株式会社テスト姫路',
                'office_code' => 'HMJ001',
                'designation_number' => '5678901235',
                'facility_name' => '姫路営業所',
                'postal_code' => '670-0001',
                'address' => '兵庫県姫路市本町1-1-1',
                'phone_number' => '079-1234-5678',
                'fax_number' => '079-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(12),
                'approved_by' => $approver?->id,
            ],

            // Aichi area facilities
            [
                'company_name' => '株式会社テスト名古屋',
                'office_code' => 'NGY001',
                'designation_number' => '6789012345',
                'facility_name' => '名古屋支店',
                'postal_code' => '460-0001',
                'address' => '愛知県名古屋市中区栄1-1-1',
                'phone_number' => '052-1234-5678',
                'fax_number' => '052-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(15),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト豊田',
                'office_code' => 'TYD001',
                'designation_number' => '6789012346',
                'facility_name' => '豊田事業所',
                'postal_code' => '471-0001',
                'address' => '愛知県豊田市小坂本町1-1-1',
                'phone_number' => '0565-12-3456',
                'fax_number' => '0565-12-3457',
                'status' => 'approved',
                'approved_at' => now()->subDays(13),
                'approved_by' => $approver?->id,
            ],

            // Fukuoka area facilities
            [
                'company_name' => '株式会社テスト福岡',
                'office_code' => 'FKO001',
                'designation_number' => '7890123456',
                'facility_name' => '福岡営業所',
                'postal_code' => '810-0001',
                'address' => '福岡県福岡市中央区天神1-1-1',
                'phone_number' => '092-1234-5678',
                'fax_number' => '092-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(10),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト北九州',
                'office_code' => 'KKS001',
                'designation_number' => '7890123457',
                'facility_name' => '北九州営業所',
                'postal_code' => '802-0001',
                'address' => '福岡県北九州市小倉北区浅野1-1-1',
                'phone_number' => '093-1234-5678',
                'fax_number' => '093-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(8),
                'approved_by' => $approver?->id,
            ],

            // Hokkaido area facilities
            [
                'company_name' => '株式会社テスト札幌',
                'office_code' => 'SPR001',
                'designation_number' => '8901234567',
                'facility_name' => '札幌営業所',
                'postal_code' => '060-0001',
                'address' => '北海道札幌市中央区大通西1-1-1',
                'phone_number' => '011-1234-5678',
                'fax_number' => '011-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(5),
                'approved_by' => $approver?->id,
            ],

            // Additional facilities for testing different statuses
            [
                'company_name' => '株式会社テスト仙台',
                'office_code' => 'SND001',
                'designation_number' => '9012345678',
                'facility_name' => '仙台営業所',
                'postal_code' => '980-0001',
                'address' => '宮城県仙台市青葉区中央1-1-1',
                'phone_number' => '022-1234-5678',
                'fax_number' => '022-1234-5679',
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '株式会社テスト広島',
                'office_code' => 'HRS001',
                'designation_number' => '0123456789',
                'facility_name' => '広島営業所',
                'postal_code' => '730-0001',
                'address' => '広島県広島市中区基町1-1-1',
                'phone_number' => '082-1234-5678',
                'fax_number' => '082-1234-5679',
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '株式会社テスト静岡',
                'office_code' => 'SZK001',
                'designation_number' => '1357924680',
                'facility_name' => '静岡営業所',
                'postal_code' => '420-0001',
                'address' => '静岡県静岡市葵区呉服町1-1-1',
                'phone_number' => '054-1234-5678',
                'fax_number' => '054-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(3),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社テスト金沢',
                'office_code' => 'KNZ001',
                'designation_number' => '2468013579',
                'facility_name' => '金沢営業所',
                'postal_code' => '920-0001',
                'address' => '石川県金沢市香林坊1-1-1',
                'phone_number' => '076-1234-5678',
                'fax_number' => '076-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(1),
                'approved_by' => $approver?->id,
            ],
        ];

        foreach ($facilities as $facilityData) {
            if (!Facility::where('office_code', $facilityData['office_code'])->exists()) {
                Facility::create(array_merge($facilityData, [
                    'created_by' => $editor->id,
                    'updated_by' => $editor->id,
                ]));
            }
        }

        $this->command->info('Created ' . count($facilities) . ' test facilities.');
    }
}