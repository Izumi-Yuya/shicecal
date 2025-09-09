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
            // Tokyo area facilities - 介護施設
            [
                'company_name' => '社会福祉法人さくら会',
                'office_code' => 'TKY001',
                'designation_number' => '1371200123',
                'facility_name' => 'さくらの里有料老人ホーム',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-2-3',
                'phone_number' => '03-1234-5678',
                'fax_number' => '03-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(30),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社ケアライフ東京',
                'office_code' => 'TKY002',
                'designation_number' => '1371200456',
                'facility_name' => 'ケアライフ新宿',
                'postal_code' => '160-0022',
                'address' => '東京都新宿区新宿3-15-8',
                'phone_number' => '03-2345-6789',
                'fax_number' => '03-2345-6790',
                'status' => 'approved',
                'approved_at' => now()->subDays(28),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '医療法人健康会',
                'office_code' => 'TKY003',
                'designation_number' => '1371200789',
                'facility_name' => '健康の森グループホーム',
                'postal_code' => '150-0002',
                'address' => '東京都渋谷区渋谷2-8-15',
                'phone_number' => '03-3456-7890',
                'fax_number' => '03-3456-7891',
                'status' => 'approved',
                'approved_at' => now()->subDays(26),
                'approved_by' => $approver?->id,
            ],

            // Kanagawa area facilities - 介護施設
            [
                'company_name' => '社会福祉法人みなと会',
                'office_code' => 'YKH001',
                'designation_number' => '1471200234',
                'facility_name' => 'みなとみらいケアセンター',
                'postal_code' => '220-0012',
                'address' => '神奈川県横浜市西区みなとみらい2-3-4',
                'phone_number' => '045-1234-5678',
                'fax_number' => '045-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(20),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社シルバーライフ神奈川',
                'office_code' => 'KWS001',
                'designation_number' => '1471200567',
                'facility_name' => 'シルバーライフ川崎',
                'postal_code' => '210-0007',
                'address' => '神奈川県川崎市川崎区駅前本町12-5',
                'phone_number' => '044-1234-5678',
                'fax_number' => '044-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(18),
                'approved_by' => $approver?->id,
            ],

            // Osaka area facilities - 介護施設
            [
                'company_name' => '社会福祉法人大阪福祉会',
                'office_code' => 'OSK001',
                'designation_number' => '2771200345',
                'facility_name' => '梅田シニアレジデンス',
                'postal_code' => '530-0017',
                'address' => '大阪府大阪市北区角田町8-47',
                'phone_number' => '06-1234-5678',
                'fax_number' => '06-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(25),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '医療法人愛心会',
                'office_code' => 'OSK002',
                'designation_number' => '2771200678',
                'facility_name' => '愛心の家デイサービス',
                'postal_code' => '542-0076',
                'address' => '大阪府大阪市中央区難波5-1-60',
                'phone_number' => '06-2345-6789',
                'fax_number' => '06-2345-6790',
                'status' => 'approved',
                'approved_at' => now()->subDays(23),
                'approved_by' => $approver?->id,
            ],

            // Kyoto area facilities - 介護施設
            [
                'company_name' => '社会福祉法人京都和会',
                'office_code' => 'KYT001',
                'designation_number' => '2671200456',
                'facility_name' => '京都和の里特別養護老人ホーム',
                'postal_code' => '600-8216',
                'address' => '京都府京都市下京区烏丸通七条下ル東塩小路町735-1',
                'phone_number' => '075-1234-5678',
                'fax_number' => '075-1234-5679',
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ],

            // Hyogo area facilities - 介護施設
            [
                'company_name' => '株式会社ハートフル神戸',
                'office_code' => 'KBE001',
                'designation_number' => '2871200789',
                'facility_name' => 'ハートフル三宮ケアホーム',
                'postal_code' => '650-0021',
                'address' => '兵庫県神戸市中央区三宮町2-11-1',
                'phone_number' => '078-1234-5678',
                'fax_number' => '078-1234-5679',
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '医療法人姫路会',
                'office_code' => 'HMJ001',
                'designation_number' => '2871200012',
                'facility_name' => '姫路城下町デイケアセンター',
                'postal_code' => '670-0012',
                'address' => '兵庫県姫路市本町68-290',
                'phone_number' => '079-1234-5678',
                'fax_number' => '079-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(12),
                'approved_by' => $approver?->id,
            ],

            // Aichi area facilities - 介護施設
            [
                'company_name' => '社会福祉法人中部福祉会',
                'office_code' => 'NGY001',
                'designation_number' => '2371200567',
                'facility_name' => '栄シルバーマンション',
                'postal_code' => '460-0008',
                'address' => '愛知県名古屋市中区栄3-4-15',
                'phone_number' => '052-1234-5678',
                'fax_number' => '052-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(15),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '株式会社トヨタケア',
                'office_code' => 'TYD001',
                'designation_number' => '2371200890',
                'facility_name' => 'トヨタケア豊田',
                'postal_code' => '471-0025',
                'address' => '愛知県豊田市西町1-200',
                'phone_number' => '0565-12-3456',
                'fax_number' => '0565-12-3457',
                'status' => 'approved',
                'approved_at' => now()->subDays(13),
                'approved_by' => $approver?->id,
            ],

            // Fukuoka area facilities - 介護施設
            [
                'company_name' => '社会福祉法人九州福祉会',
                'office_code' => 'FKO001',
                'designation_number' => '4071200123',
                'facility_name' => '天神ケアプラザ',
                'postal_code' => '810-0001',
                'address' => '福岡県福岡市中央区天神2-8-38',
                'phone_number' => '092-1234-5678',
                'fax_number' => '092-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(10),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '医療法人小倉会',
                'office_code' => 'KKS001',
                'designation_number' => '4071200456',
                'facility_name' => '小倉リハビリテーションセンター',
                'postal_code' => '802-0001',
                'address' => '福岡県北九州市小倉北区浅野2-14-1',
                'phone_number' => '093-1234-5678',
                'fax_number' => '093-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(8),
                'approved_by' => $approver?->id,
            ],

            // Hokkaido area facilities - 介護施設
            [
                'company_name' => '社会福祉法人雪国会',
                'office_code' => 'SPR001',
                'designation_number' => '0171200789',
                'facility_name' => '大通りシニアハウス',
                'postal_code' => '060-0042',
                'address' => '北海道札幌市中央区大通西10-4',
                'phone_number' => '011-1234-5678',
                'fax_number' => '011-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(5),
                'approved_by' => $approver?->id,
            ],

            // Additional facilities for testing different statuses - 介護施設
            [
                'company_name' => '社会福祉法人杜の都会',
                'office_code' => 'SND001',
                'designation_number' => '0471200012',
                'facility_name' => '杜の都ケアハウス',
                'postal_code' => '980-0021',
                'address' => '宮城県仙台市青葉区中央1-6-35',
                'phone_number' => '022-1234-5678',
                'fax_number' => '022-1234-5679',
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '医療法人平和会',
                'office_code' => 'HRS001',
                'designation_number' => '3471200345',
                'facility_name' => '平和の里介護老人保健施設',
                'postal_code' => '730-0017',
                'address' => '広島県広島市中区鉄砲町10-18',
                'phone_number' => '082-1234-5678',
                'fax_number' => '082-1234-5679',
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ],
            [
                'company_name' => '株式会社富士山ケア',
                'office_code' => 'SZK001',
                'designation_number' => '2271200678',
                'facility_name' => '富士山の麓サービス付き高齢者向け住宅',
                'postal_code' => '420-0858',
                'address' => '静岡県静岡市葵区伝馬町8-6',
                'phone_number' => '054-1234-5678',
                'fax_number' => '054-1234-5679',
                'status' => 'approved',
                'approved_at' => now()->subDays(3),
                'approved_by' => $approver?->id,
            ],
            [
                'company_name' => '社会福祉法人加賀会',
                'office_code' => 'KNZ001',
                'designation_number' => '1771200901',
                'facility_name' => '加賀の里グループホーム',
                'postal_code' => '920-0981',
                'address' => '石川県金沢市片町2-2-15',
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