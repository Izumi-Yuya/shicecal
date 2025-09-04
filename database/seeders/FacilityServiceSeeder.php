<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilityServiceSeeder extends Seeder
{
    /**
     * 固定のサービスセクション
     */
    const SECTION_RESIDENTIAL = '入居系サービス';
    const SECTION_HOME_CARE = '在宅系サービス';
    const SECTION_DAY_CARE = '通所系サービス';
    const SECTION_DEMENTIA_CARE = '認知症対応サービス';
    const SECTION_SHORT_STAY = '短期入所サービス';
    const SECTION_HOUSING = '住宅系サービス';
    const SECTION_REHABILITATION = 'リハビリテーションサービス';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facilities = Facility::all();

        foreach ($facilities as $facility) {
            // 各施設に応じたサービス情報を設定
            $services = $this->getServicesForFacility($facility);
            
            foreach ($services as $service) {
                DB::table('facility_services')->insert([
                    'facility_id' => $facility->id,
                    'service_type' => $service['service_type'],
                    'section' => $service['section'],
                    'renewal_start_date' => $service['renewal_start_date'],
                    'renewal_end_date' => $service['renewal_end_date'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Created facility services data.');
    }

    /**
     * 施設に応じたサービス情報を取得
     */
    private function getServicesForFacility(Facility $facility): array
    {
        // 施設名に基づいてサービスタイプを決定
        $facilityName = $facility->facility_name;
        
        if (str_contains($facilityName, '有料老人ホーム')) {
            return [
                // 入居系サービス
                [
                    'service_type' => '介護付有料老人ホーム',
                    'section' => self::SECTION_RESIDENTIAL,
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ],
                [
                    'service_type' => '特定施設入居者生活介護',
                    'section' => self::SECTION_RESIDENTIAL,
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ],
                // 在宅系サービス
                [
                    'service_type' => '居宅介護支援事業所',
                    'section' => self::SECTION_HOME_CARE,
                    'renewal_start_date' => '2023-06-01',
                    'renewal_end_date' => '2029-05-31'
                ]
            ];
        } elseif (str_contains($facilityName, 'グループホーム')) {
            return [
                // 認知症対応サービス
                [
                    'service_type' => '認知症対応型共同生活介護',
                    'section' => '認知症対応サービス',
                    'renewal_start_date' => '2023-04-01',
                    'renewal_end_date' => '2029-03-31'
                ],
                // 在宅系サービス
                [
                    'service_type' => '居宅介護支援事業所',
                    'section' => '在宅系サービス',
                    'renewal_start_date' => '2023-06-01',
                    'renewal_end_date' => '2029-05-31'
                ]
            ];
        } elseif (str_contains($facilityName, 'デイサービス') || str_contains($facilityName, 'デイケア')) {
            return [
                // 通所系サービス
                [
                    'service_type' => '通所介護',
                    'section' => '通所系サービス',
                    'renewal_start_date' => '2023-10-01',
                    'renewal_end_date' => '2029-09-30'
                ],
                // 認知症対応サービス
                [
                    'service_type' => '認知症対応型通所介護',
                    'section' => '認知症対応サービス',
                    'renewal_start_date' => '2024-01-15',
                    'renewal_end_date' => '2030-01-14'
                ]
            ];
        } elseif (str_contains($facilityName, '特別養護老人ホーム')) {
            return [
                // 入居系サービス
                [
                    'service_type' => '介護老人福祉施設',
                    'section' => '入居系サービス',
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ],
                // 短期入所サービス
                [
                    'service_type' => '短期入所生活介護',
                    'section' => '短期入所サービス',
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ]
            ];
        } elseif (str_contains($facilityName, '介護老人保健施設')) {
            return [
                // 入居系サービス
                [
                    'service_type' => '介護老人保健施設',
                    'section' => '入居系サービス',
                    'renewal_start_date' => '2023-04-01',
                    'renewal_end_date' => '2029-03-31'
                ],
                // 短期入所サービス
                [
                    'service_type' => '短期入所療養介護',
                    'section' => '短期入所サービス',
                    'renewal_start_date' => '2023-04-01',
                    'renewal_end_date' => '2029-03-31'
                ]
            ];
        } elseif (str_contains($facilityName, 'サービス付き高齢者向け住宅')) {
            return [
                // 住宅系サービス
                [
                    'service_type' => 'サービス付き高齢者向け住宅',
                    'section' => '住宅系サービス',
                    'renewal_start_date' => '2023-01-01',
                    'renewal_end_date' => '2028-12-31'
                ],
                // 在宅系サービス
                [
                    'service_type' => '訪問介護',
                    'section' => '在宅系サービス',
                    'renewal_start_date' => '2024-01-15',
                    'renewal_end_date' => '2030-01-14'
                ],
                [
                    'service_type' => '居宅介護支援事業所',
                    'section' => '在宅系サービス',
                    'renewal_start_date' => '2023-06-01',
                    'renewal_end_date' => '2029-05-31'
                ]
            ];
        } elseif (str_contains($facilityName, 'リハビリテーション')) {
            return [
                // リハビリテーションサービス
                [
                    'service_type' => '通所リハビリテーション',
                    'section' => 'リハビリテーションサービス',
                    'renewal_start_date' => '2023-04-01',
                    'renewal_end_date' => '2029-03-31'
                ],
                [
                    'service_type' => '訪問リハビリテーション',
                    'section' => 'リハビリテーションサービス',
                    'renewal_start_date' => '2024-01-01',
                    'renewal_end_date' => '2030-12-31'
                ]
            ];
        } else {
            // デフォルトのサービス（ケアセンター、ケアホーム等）
            return [
                // 入居系サービス
                [
                    'service_type' => '介護付有料老人ホーム',
                    'section' => '入居系サービス',
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ],
                [
                    'service_type' => '特定施設入居者生活介護',
                    'section' => '入居系サービス',
                    'renewal_start_date' => '2022-04-01',
                    'renewal_end_date' => '2028-03-31'
                ],
                // 在宅系サービス
                [
                    'service_type' => '訪問介護',
                    'section' => '在宅系サービス',
                    'renewal_start_date' => '2024-01-15',
                    'renewal_end_date' => '2030-01-14'
                ],
                [
                    'service_type' => '居宅介護支援事業所',
                    'section' => '在宅系サービス',
                    'renewal_start_date' => '2023-06-01',
                    'renewal_end_date' => '2029-05-31'
                ]
            ];
        }
    }
}