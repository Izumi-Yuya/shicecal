<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class LandInfoSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('ja_JP');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $editor = User::where('role', 'editor')->first();
        $approver = User::where('role', 'approver')->first();

        if (! $editor || ! $approver) {
            $this->command->warn('Editor or approver user not found. Please run AdminUserSeeder first.');

            return;
        }

        // Get existing facilities
        $facilities = Facility::all();

        if ($facilities->isEmpty()) {
            $this->command->warn('No facilities found. Please run FacilitySeeder first.');

            return;
        }

        $landInfoData = [];
        $createdCount = 0;

        // Create realistic land information for each facility
        foreach ($facilities as $facility) {
            // Skip if land info already exists
            if (LandInfo::where('facility_id', $facility->id)->exists()) {
                $this->command->info("Land info already exists for facility: {$facility->facility_name}");

                continue;
            }

            try {
                // Determine ownership type based on facility characteristics
                $ownershipType = $this->determineOwnershipType($facility);
                $siteAreaSqm = $this->getSiteAreaSqm($facility);
                $siteAreaTsubo = round($siteAreaSqm / 3.306, 2);

                $baseData = [
                    'facility_id' => $facility->id,
                    'ownership_type' => $ownershipType,
                    'parking_spaces' => $this->getParkingSpaces($facility),
                    'site_area_sqm' => $siteAreaSqm,
                    'site_area_tsubo' => $siteAreaTsubo,
                    'notes' => $this->getNotes($facility, $ownershipType),
                    'status' => $facility->status === 'approved' ? 'approved' : 'draft',
                    'created_by' => $editor->id,
                    'updated_by' => $editor->id,
                    'approved_by' => $facility->status === 'approved' ? $approver->id : null,
                    'approved_at' => $facility->status === 'approved' ? $facility->approved_at : null,
                ];

                // Add ownership-specific data
                if ($ownershipType === 'owned') {
                    $baseData = array_merge($baseData, $this->getOwnedPropertyData($facility, $siteAreaTsubo));
                } elseif ($ownershipType === 'leased') {
                    $baseData = array_merge($baseData, $this->getLeasedPropertyData($facility));
                } elseif ($ownershipType === 'owned_rental') {
                    $baseData = array_merge($baseData, $this->getOwnedRentalPropertyData($facility, $siteAreaTsubo));
                }

                $landInfoData[] = $baseData;
                $createdCount++;
            } catch (\Exception $e) {
                $this->command->error("Error creating land info for facility {$facility->id}: ".$e->getMessage());

                continue;
            }
        }

        // Create land info records in batches for better performance
        if (! empty($landInfoData)) {
            foreach (array_chunk($landInfoData, 50) as $chunk) {
                foreach ($chunk as $data) {
                    try {
                        LandInfo::create($data);
                    } catch (\Exception $e) {
                        $this->command->error('Error saving land info: '.$e->getMessage());
                    }
                }
            }
        }

        $this->command->info("Created land information for {$createdCount} facilities.");
    }

    /**
     * Determine ownership type based on facility characteristics
     */
    private function determineOwnershipType(Facility $facility): string
    {
        // Larger facilities (hospitals, large care centers) are more likely to be owned
        if (
            str_contains($facility->facility_name, '病院') ||
            str_contains($facility->facility_name, '特別養護老人ホーム') ||
            str_contains($facility->facility_name, 'リハビリテーションセンター')
        ) {
            return fake()->randomElement(['owned', 'owned', 'leased']); // 66% owned
        }

        // Smaller facilities are more likely to be leased
        if (
            str_contains($facility->facility_name, 'デイサービス') ||
            str_contains($facility->facility_name, 'グループホーム')
        ) {
            return fake()->randomElement(['leased', 'leased', 'owned_rental']); // 66% leased
        }

        // Default distribution
        return fake()->randomElement(['owned', 'leased', 'owned_rental']);
    }

    /**
     * Get parking spaces based on facility type
     */
    private function getParkingSpaces(Facility $facility): int
    {
        if (
            str_contains($facility->facility_name, '病院') ||
            str_contains($facility->facility_name, 'リハビリテーションセンター')
        ) {
            return fake()->numberBetween(50, 200);
        }

        if (
            str_contains($facility->facility_name, '特別養護老人ホーム') ||
            str_contains($facility->facility_name, 'ケアセンター')
        ) {
            return fake()->numberBetween(20, 80);
        }

        if (
            str_contains($facility->facility_name, 'デイサービス') ||
            str_contains($facility->facility_name, 'グループホーム')
        ) {
            return fake()->numberBetween(5, 30);
        }

        return fake()->numberBetween(10, 50);
    }

    /**
     * Get site area in square meters based on facility type
     */
    private function getSiteAreaSqm(Facility $facility): float
    {
        if (
            str_contains($facility->facility_name, '病院') ||
            str_contains($facility->facility_name, 'リハビリテーションセンター')
        ) {
            return fake()->randomFloat(2, 3000, 10000);
        }

        if (str_contains($facility->facility_name, '特別養護老人ホーム')) {
            return fake()->randomFloat(2, 2000, 6000);
        }

        if (
            str_contains($facility->facility_name, 'ケアセンター') ||
            str_contains($facility->facility_name, 'ケアプラザ')
        ) {
            return fake()->randomFloat(2, 1500, 4000);
        }

        if (
            str_contains($facility->facility_name, 'デイサービス') ||
            str_contains($facility->facility_name, 'グループホーム')
        ) {
            return fake()->randomFloat(2, 500, 2000);
        }

        return fake()->randomFloat(2, 800, 3000);
    }

    /**
     * Get notes based on facility and ownership type
     */
    private function getNotes(Facility $facility, string $ownershipType): ?string
    {
        $notes = [];
        $facilityName = $facility->facility_name ?? '';
        $address = $facility->address ?? '';

        // Ownership-specific notes
        if ($ownershipType === 'owned') {
            $notes[] = '自社所有物件として長期的な運営を予定';
            if (fake()->boolean(30)) {
                $notes[] = '将来的な増築・改修計画あり';
            }
            if (fake()->boolean(20)) {
                $notes[] = '土地・建物の資産価値維持のため定期メンテナンス実施';
            }
        } elseif ($ownershipType === 'leased') {
            $notes[] = '賃貸契約による運営';
            if (fake()->boolean(40)) {
                $notes[] = '契約更新時の条件見直し要検討';
            }
            if (fake()->boolean(25)) {
                $notes[] = '賃料改定の可能性について定期的に協議';
            }
        } elseif ($ownershipType === 'owned_rental') {
            $notes[] = '自社所有物件を第三者に賃貸';
            if (fake()->boolean(20)) {
                $notes[] = '賃料収入による安定的な収益確保';
            }
            if (fake()->boolean(15)) {
                $notes[] = 'テナントとの良好な関係維持を重視';
            }
        }

        // Location-based notes
        if (str_contains($address, '東京都') || str_contains($address, '大阪府')) {
            if (fake()->boolean(30)) {
                $notes[] = '都市部立地のため地価上昇傾向';
            }
        }

        if (fake()->boolean(25)) {
            $notes[] = '近隣に公共交通機関あり、アクセス良好';
        }

        if (fake()->boolean(20)) {
            $notes[] = '周辺環境は住宅地で静穏';
        }

        // Facility-specific notes
        if (str_contains($facilityName, '病院') || str_contains($facilityName, 'リハビリテーション')) {
            if (fake()->boolean(15)) {
                $notes[] = '救急車両の出入りを考慮した立地';
            }
        }

        if (str_contains($facilityName, 'デイサービス') || str_contains($facilityName, 'グループホーム')) {
            if (fake()->boolean(18)) {
                $notes[] = '利用者の送迎に配慮した駐車場配置';
            }
        }

        // Additional considerations
        if (fake()->boolean(12)) {
            $notes[] = '災害時の避難経路確保済み';
        }

        if (fake()->boolean(10)) {
            $notes[] = 'バリアフリー対応済み';
        }

        return empty($notes) ? null : implode('。', $notes).'。';
    }

    /**
     * Get owned property specific data
     */
    private function getOwnedPropertyData(Facility $facility, float $siteAreaTsubo): array
    {
        // Calculate realistic purchase price based on location and size
        $basePrice = $this->getBasePricePerTsubo($facility);
        $purchasePrice = round($basePrice * $siteAreaTsubo);

        return [
            'purchase_price' => $purchasePrice,
            'unit_price_per_tsubo' => $basePrice,
            'monthly_rent' => null,
            'contract_start_date' => null,
            'contract_end_date' => null,
            'auto_renewal' => null,
            'contract_period_text' => null,
            'management_company_name' => null,
            'management_company_postal_code' => null,
            'management_company_address' => null,
            'management_company_building' => null,
            'management_company_phone' => null,
            'management_company_fax' => null,
            'management_company_email' => null,
            'management_company_url' => null,
            'management_company_notes' => null,
            'owner_name' => null,
            'owner_postal_code' => null,
            'owner_address' => null,
            'owner_building' => null,
            'owner_phone' => null,
            'owner_fax' => null,
            'owner_email' => null,
            'owner_url' => null,
            'owner_notes' => null,

            // PDF file information for owned properties (registry only)
            'lease_contract_pdf_path' => null,
            'lease_contract_pdf_name' => null,
            'registry_pdf_path' => fake()->optional(0.95)->randomElement([
                'land_documents/registry/owned_property_'.fake()->uuid().'.pdf',
                'land_documents/registry/self_owned_registry_'.fake()->uuid().'.pdf',
            ]),
            'registry_pdf_name' => fake()->optional(0.95)->randomElement([
                '自社所有不動産登記簿謄本.pdf',
                '土地建物登記事項証明書.pdf',
                '不動産登記簿謄本_自社所有.pdf',
            ]),
        ];
    }

    /**
     * Get leased property specific data
     */
    private function getLeasedPropertyData(Facility $facility): array
    {
        [$startDate, $endDate] = $this->getContractDates();

        return [
            'purchase_price' => null,
            'unit_price_per_tsubo' => null,
            'monthly_rent' => $this->getMonthlyRent($facility),
            'contract_start_date' => $startDate,
            'contract_end_date' => $endDate,
            'auto_renewal' => fake()->randomElement(['yes', 'no']),
            'contract_period_text' => $this->calculateContractPeriod($startDate, $endDate),

            // Management company (complete information)
            'management_company_name' => $this->getManagementCompanyName(),
            'management_company_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'management_company_address' => $this->getManagementCompanyAddress($facility),
            'management_company_building' => $this->getManagementCompanyBuilding(),
            'management_company_phone' => fake()->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'management_company_fax' => fake()->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'management_company_email' => $this->getManagementCompanyEmail(),
            'management_company_url' => $this->getManagementCompanyUrl(),
            'management_company_notes' => $this->getManagementCompanyNotes(),

            // Owner (complete information)
            'owner_name' => $this->getOwnerName(),
            'owner_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'owner_address' => $this->getOwnerAddress($facility),
            'owner_building' => $this->getOwnerBuilding(),
            'owner_phone' => fake()->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'owner_fax' => fake()->optional(0.6)->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'owner_email' => fake()->optional(0.8)->safeEmail(),
            'owner_url' => fake()->optional(0.3)->url(),
            'owner_notes' => $this->getOwnerNotes(),

            // PDF file information (simulated)
            'lease_contract_pdf_path' => fake()->optional(0.8)->randomElement([
                'land_documents/lease_contracts/contract_'.fake()->uuid().'.pdf',
                'land_documents/lease_contracts/lease_agreement_'.fake()->uuid().'.pdf',
            ]),
            'lease_contract_pdf_name' => fake()->optional(0.8)->randomElement([
                '賃貸借契約書_'.$facility->facility_name.'.pdf',
                '土地賃貸契約書_'.date('Y年m月d日').'.pdf',
                '賃貸借契約書及び覚書.pdf',
            ]),
            'registry_pdf_path' => fake()->optional(0.9)->randomElement([
                'land_documents/registry/registry_'.fake()->uuid().'.pdf',
                'land_documents/registry/land_registry_'.fake()->uuid().'.pdf',
            ]),
            'registry_pdf_name' => fake()->optional(0.9)->randomElement([
                '土地登記簿謄本_'.$facility->facility_name.'.pdf',
                '不動産登記事項証明書.pdf',
                '登記簿謄本_'.date('Y年m月d日取得').'.pdf',
            ]),
        ];
    }

    /**
     * Get owned rental property specific data
     */
    private function getOwnedRentalPropertyData(Facility $facility, float $siteAreaTsubo): array
    {
        $basePrice = $this->getBasePricePerTsubo($facility);
        $purchasePrice = round($basePrice * $siteAreaTsubo);
        [$startDate, $endDate] = $this->getContractDates();

        return [
            'purchase_price' => $purchasePrice,
            'unit_price_per_tsubo' => $basePrice,
            'monthly_rent' => $this->getMonthlyRent($facility),
            'contract_start_date' => $startDate,
            'contract_end_date' => $endDate,
            'auto_renewal' => fake()->randomElement(['yes', 'no']),
            'contract_period_text' => $this->calculateContractPeriod($startDate, $endDate),

            // No management company for owned rental
            'management_company_name' => null,
            'management_company_postal_code' => null,
            'management_company_address' => null,
            'management_company_building' => null,
            'management_company_phone' => null,
            'management_company_fax' => null,
            'management_company_email' => null,
            'management_company_url' => null,
            'management_company_notes' => null,

            // Tenant information (stored in owner fields)
            'owner_name' => $this->getTenantCompanyName(),
            'owner_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'owner_address' => $this->getTenantAddress($facility),
            'owner_building' => $this->getTenantBuilding(),
            'owner_phone' => fake()->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'owner_fax' => fake()->regexify('0\d{1,2}-\d{2,3}-\d{4}'),
            'owner_email' => $this->getTenantEmail(),
            'owner_url' => $this->getTenantUrl(),
            'owner_notes' => $this->getTenantNotes(),

            // PDF file information (simulated)
            'lease_contract_pdf_path' => fake()->optional(0.9)->randomElement([
                'land_documents/lease_contracts/rental_contract_'.fake()->uuid().'.pdf',
                'land_documents/lease_contracts/tenant_agreement_'.fake()->uuid().'.pdf',
            ]),
            'lease_contract_pdf_name' => fake()->optional(0.9)->randomElement([
                'テナント賃貸借契約書_'.$facility->facility_name.'.pdf',
                '事業用賃貸借契約書.pdf',
                '賃貸借契約書_'.date('Y年m月d日').'.pdf',
            ]),
            'registry_pdf_path' => fake()->optional(0.95)->randomElement([
                'land_documents/registry/owned_registry_'.fake()->uuid().'.pdf',
                'land_documents/registry/property_registry_'.fake()->uuid().'.pdf',
            ]),
            'registry_pdf_name' => fake()->optional(0.95)->randomElement([
                '自社所有土地登記簿謄本.pdf',
                '不動産登記事項証明書_自社所有.pdf',
                '土地建物登記簿謄本.pdf',
            ]),
        ];
    }

    /**
     * Get base price per tsubo based on location
     */
    private function getBasePricePerTsubo(Facility $facility): int
    {
        $address = $facility->address ?? '';

        // Tokyo premium areas
        if (str_contains($address, '東京都')) {
            if (
                str_contains($address, '千代田区') ||
                str_contains($address, '中央区') ||
                str_contains($address, '港区') ||
                str_contains($address, '渋谷区') ||
                str_contains($address, '新宿区')
            ) {
                return fake()->numberBetween(600000, 1200000); // Premium Tokyo areas
            }
            if (
                str_contains($address, '品川区') ||
                str_contains($address, '目黒区') ||
                str_contains($address, '世田谷区')
            ) {
                return fake()->numberBetween(400000, 700000); // High-end residential
            }

            return fake()->numberBetween(300000, 600000); // Other Tokyo areas
        }

        // Osaka areas
        if (str_contains($address, '大阪府')) {
            if (
                str_contains($address, '北区') ||
                str_contains($address, '中央区') ||
                str_contains($address, '西区')
            ) {
                return fake()->numberBetween(250000, 500000); // Central Osaka
            }

            return fake()->numberBetween(150000, 350000); // Other Osaka areas
        }

        // Other major cities
        if (str_contains($address, '神奈川県')) {
            if (str_contains($address, '横浜市')) {
                return fake()->numberBetween(200000, 450000);
            }

            return fake()->numberBetween(180000, 350000);
        }

        if (str_contains($address, '愛知県')) {
            if (str_contains($address, '名古屋市')) {
                return fake()->numberBetween(180000, 400000);
            }

            return fake()->numberBetween(120000, 280000);
        }

        // Other prefectures
        $majorPrefectures = ['兵庫県', '福岡県', '埼玉県', '千葉県'];
        foreach ($majorPrefectures as $prefecture) {
            if (str_contains($address, $prefecture)) {
                return fake()->numberBetween(100000, 250000);
            }
        }

        // Rural areas
        return fake()->numberBetween(80000, 200000);
    }

    /**
     * Get monthly rent based on facility type and location
     */
    private function getMonthlyRent(Facility $facility): int
    {
        $facilityName = $facility->facility_name ?? '';
        $address = $facility->address ?? '';
        $baseRent = 0;

        // Base rent by facility type and size
        if (
            str_contains($facilityName, '病院') ||
            str_contains($facilityName, 'リハビリテーションセンター')
        ) {
            $baseRent = fake()->numberBetween(800000, 2500000);
        } elseif (str_contains($facilityName, '特別養護老人ホーム')) {
            $baseRent = fake()->numberBetween(400000, 1200000);
        } elseif (
            str_contains($facilityName, 'ケアセンター') ||
            str_contains($facilityName, 'ケアプラザ')
        ) {
            $baseRent = fake()->numberBetween(250000, 800000);
        } elseif (
            str_contains($facilityName, 'デイサービス') ||
            str_contains($facilityName, 'グループホーム')
        ) {
            $baseRent = fake()->numberBetween(150000, 500000);
        } else {
            $baseRent = fake()->numberBetween(200000, 600000);
        }

        // Location multipliers
        $locationMultiplier = 1.0;

        if (str_contains($address, '東京都')) {
            if (
                str_contains($address, '千代田区') ||
                str_contains($address, '中央区') ||
                str_contains($address, '港区')
            ) {
                $locationMultiplier = 1.8;
            } elseif (
                str_contains($address, '渋谷区') ||
                str_contains($address, '新宿区') ||
                str_contains($address, '品川区')
            ) {
                $locationMultiplier = 1.6;
            } else {
                $locationMultiplier = 1.4;
            }
        } elseif (str_contains($address, '大阪府')) {
            if (
                str_contains($address, '北区') ||
                str_contains($address, '中央区')
            ) {
                $locationMultiplier = 1.3;
            } else {
                $locationMultiplier = 1.1;
            }
        } elseif (
            str_contains($address, '神奈川県') ||
            str_contains($address, '愛知県')
        ) {
            $locationMultiplier = 1.2;
        }

        return round($baseRent * $locationMultiplier);
    }

    /**
     * Get management company name
     */
    private function getManagementCompanyName(): string
    {
        $companies = [
            '株式会社不動産管理センター',
            '有限会社プロパティマネジメント',
            '株式会社総合不動産サービス',
            '三井不動産リアルティ株式会社',
            '住友不動産販売株式会社',
            '東急リバブル株式会社',
            '野村不動産アーバンネット株式会社',
            '大和ハウス工業株式会社',
            '積水ハウス不動産株式会社',
            '株式会社長谷工リアルエステート',
        ];

        return fake()->randomElement($companies);
    }

    /**
     * Get management company address based on facility location
     */
    private function getManagementCompanyAddress(Facility $facility): string
    {
        // Try to get same prefecture as facility
        if (str_contains($facility->address, '東京都')) {
            return fake()->randomElement([
                '新宿区西新宿2-8-1',
                '港区赤坂1-12-32',
                '千代田区丸の内1-6-5',
                '渋谷区道玄坂1-2-3',
            ]);
        }

        if (str_contains($facility->address, '大阪府')) {
            return fake()->randomElement([
                '北区梅田1-1-3',
                '中央区本町4-1-13',
                '西区江戸堀1-9-1',
            ]);
        }

        // Generate short address for other locations
        return fake()->city() . fake()->numberBetween(1, 9) . '-' . fake()->numberBetween(1, 20) . '-' . fake()->numberBetween(1, 30);
    }

    /**
     * Get tenant company name for owned rental properties
     */
    private function getTenantCompanyName(): string
    {
        $companies = [
            '株式会社メディカルケア',
            '医療法人健康グループ',
            '社会福祉法人福祉の里',
            '株式会社シニアライフサポート',
            '医療法人愛和会',
            '社会福祉法人みらい',
            '株式会社ケアパートナー',
            '医療法人仁愛会',
            '社会福祉法人希望の会',
            '株式会社ライフケアサービス',
            '医療法人康生会',
            '社会福祉法人愛心会',
            '株式会社ヘルスケアパートナーズ',
            '医療法人清和会',
            '社会福祉法人恵愛会',
        ];

        return fake()->randomElement($companies);
    }

    /**
     * Get owner name (individual or company)
     */
    private function getOwnerName(): string
    {
        // 70% chance of individual owner, 30% company
        if (fake()->boolean(70)) {
            return fake()->name();
        }

        $companies = [
            '株式会社不動産投資',
            '有限会社プロパティホールディングス',
            '株式会社アセットマネジメント',
            '合同会社不動産開発',
            '株式会社都市開発',
            '有限会社土地活用',
            '株式会社リアルエステート',
            '合資会社不動産経営',
        ];

        return fake()->randomElement($companies);
    }

    /**
     * Get owner address based on facility location
     */
    private function getOwnerAddress(Facility $facility): string
    {
        $facilityAddress = $facility->address ?? '';

        // 60% chance owner is in same prefecture
        if (fake()->boolean(60)) {
            if (str_contains($facilityAddress, '東京都')) {
                return fake()->randomElement([
                    '世田谷区成城1-2-3',
                    '杉並区阿佐谷南1-4-5',
                    '練馬区石神井町2-6-7',
                    '大田区田園調布1-8-9',
                ]);
            }
            if (str_contains($facilityAddress, '大阪府')) {
                return fake()->randomElement([
                    '豊中市緑丘1-2-3',
                    '吹田市千里山東1-4-5',
                    '枚方市楠葉並木2-6-7',
                ]);
            }
        }

        // Generate short address for other locations
        return fake()->city() . fake()->numberBetween(1, 9) . '-' . fake()->numberBetween(1, 20) . '-' . fake()->numberBetween(1, 30);
    }

    /**
     * Get tenant address for owned rental properties
     */
    private function getTenantAddress(Facility $facility): string
    {
        $facilityAddress = $facility->address ?? '';

        // Tenant companies often have offices in business districts
        if (str_contains($facilityAddress, '東京都')) {
            return fake()->randomElement([
                '千代田区丸の内1-1-1',
                '港区虎ノ門1-2-3',
                '新宿区西新宿2-4-5',
                '渋谷区恵比寿1-6-7',
            ]);
        }

        if (str_contains($facilityAddress, '大阪府')) {
            return fake()->randomElement([
                '北区梅田1-1-1',
                '中央区本町2-3-4',
                '西区江戸堀1-5-6',
            ]);
        }

        // Generate short address for other locations
        return fake()->city() . fake()->numberBetween(1, 9) . '-' . fake()->numberBetween(1, 20) . '-' . fake()->numberBetween(1, 30);
    }

    /**
     * Calculate contract period text in Japanese
     */
    private function calculateContractPeriod($startDate, $endDate): string
    {
        if (! $startDate || ! $endDate) {
            return '';
        }

        try {
            $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
            $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

            if ($end <= $start) {
                return '';
            }

            $totalMonths = $start->diffInMonths($end);
            $years = intval($totalMonths / 12);
            $months = $totalMonths % 12;

            $result = '';
            if ($years > 0) {
                $result .= $years.'年';
            }
            if ($months > 0) {
                $result .= $months.'ヶ月';
            }

            return $result ?: '1ヶ月未満';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get realistic contract dates
     */
    private function getContractDates(): array
    {
        // Generate realistic contract periods (1-5 years)
        $contractYears = fake()->randomElement([1, 2, 3, 5]);
        $startDate = fake()->dateTimeBetween('-2 years', 'now');
        $endDate = (clone $startDate)->modify("+{$contractYears} years");

        return [$startDate, $endDate];
    }

    /**
     * Get management company building name
     */
    private function getManagementCompanyBuilding(): ?string
    {
        $buildings = [
            '○○ビル5F',
            '△△タワー12階',
            '□□プラザ3F',
            'ビジネスセンター8階',
            '不動産会館7F',
            'オフィスタワー15階',
            '商業ビル4F',
            null, // Some don't have building names
        ];

        return fake()->randomElement($buildings);
    }

    /**
     * Get management company email
     */
    private function getManagementCompanyEmail(): string
    {
        $domains = [
            'property-mgmt.co.jp',
            'real-estate.jp',
            'management.com',
            'property.co.jp',
            'realestate-service.jp',
        ];

        $prefixes = [
            'info',
            'contact',
            'support',
            'office',
            'management',
        ];

        return fake()->randomElement($prefixes).'@'.fake()->randomElement($domains);
    }

    /**
     * Get management company URL
     */
    private function getManagementCompanyUrl(): ?string
    {
        $domains = [
            'property-management.co.jp',
            'real-estate-service.jp',
            'management-company.com',
            'property-care.jp',
            'realestate-pro.co.jp',
        ];

        return fake()->boolean(70) ? 'https://www.'.fake()->randomElement($domains) : null;
    }

    /**
     * Get management company notes
     */
    private function getManagementCompanyNotes(): ?string
    {
        $notes = [
            '24時間対応可能な管理体制を整備。緊急時の連絡先も明確化されている。',
            '月次報告書の提出あり。建物の維持管理状況を定期的に報告。',
            '清掃・設備点検等の日常管理業務を包括的に委託。',
            '入居者対応、契約更新手続き等も含めた総合管理サービス。',
            '地域密着型の管理会社として、きめ細かいサービスを提供。',
            '大手不動産会社系列で、豊富な管理実績とノウハウを保有。',
            '設備故障時の迅速な対応体制が整っており、信頼性が高い。',
            null,
        ];

        return fake()->randomElement($notes);
    }

    /**
     * Get owner building name
     */
    private function getOwnerBuilding(): ?string
    {
        $buildings = [
            'マンション○○号室',
            '△△ハイツ□□号',
            '住宅○○',
            'アパート△△',
            '戸建て住宅',
            null, // Some don't have building names
        ];

        return fake()->randomElement($buildings);
    }

    /**
     * Get owner notes
     */
    private function getOwnerNotes(): ?string
    {
        $notes = [
            '長期保有を前提とした安定的な賃貸経営を志向。',
            '建物の維持管理に積極的で、定期的な修繕を実施。',
            '賃料改定については市場相場を考慮した柔軟な対応。',
            '相続により取得した不動産で、家族経営による管理。',
            '不動産投資の一環として複数物件を所有・運営。',
            '地域の発展に貢献したいとの意向で長期契約を希望。',
            '建物の老朽化に伴う大規模修繕の計画を検討中。',
            null,
        ];

        return fake()->randomElement($notes);
    }

    /**
     * Get tenant building name for owned rental properties
     */
    private function getTenantBuilding(): ?string
    {
        $buildings = [
            '○○ビル本社',
            '△△センター',
            '□□オフィス',
            'ビジネスプラザ',
            '企業会館',
            null,
        ];

        return fake()->randomElement($buildings);
    }

    /**
     * Get tenant email
     */
    private function getTenantEmail(): string
    {
        $domains = [
            'medical-care.co.jp',
            'healthcare.jp',
            'welfare.or.jp',
            'senior-care.com',
            'medical.co.jp',
        ];

        $prefixes = [
            'info',
            'contact',
            'office',
            'admin',
            'facility',
        ];

        return fake()->randomElement($prefixes).'@'.fake()->randomElement($domains);
    }

    /**
     * Get tenant URL
     */
    private function getTenantUrl(): ?string
    {
        $domains = [
            'medical-care.co.jp',
            'healthcare-service.jp',
            'welfare-group.or.jp',
            'senior-life.com',
            'medical-support.co.jp',
        ];

        return fake()->boolean(80) ? 'https://www.'.fake()->randomElement($domains) : null;
    }

    /**
     * Get tenant notes
     */
    private function getTenantNotes(): ?string
    {
        $notes = [
            '医療・介護事業を主力とする安定した事業基盤を持つ企業。',
            '地域密着型のサービス提供により、利用者からの信頼も厚い。',
            '施設の運営実績が豊富で、適切な管理体制が構築されている。',
            '契約更新時の条件交渉においても誠実な対応を心がけている。',
            '建物の維持管理についても積極的に協力する姿勢を示している。',
            '事業拡大に伴い、長期的な利用を前提とした契約を希望。',
            '地域の高齢化社会に対応したサービス展開を計画中。',
            null,
        ];

        return fake()->randomElement($notes);
    }
}
