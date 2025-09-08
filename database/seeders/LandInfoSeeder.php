<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\User;
use Illuminate\Database\Seeder;

class LandInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $editor = User::where('role', 'editor')->first();
        $approver = User::where('role', 'approver')->first();

        if (!$editor || !$approver) {
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

        // Create realistic land information for each facility
        foreach ($facilities as $facility) {
            // Skip if land info already exists
            if (LandInfo::where('facility_id', $facility->id)->exists()) {
                continue;
            }

            // Determine ownership type based on facility characteristics
            $ownershipType = $this->determineOwnershipType($facility);

            $baseData = [
                'facility_id' => $facility->id,
                'ownership_type' => $ownershipType,
                'parking_spaces' => $this->getParkingSpaces($facility),
                'site_area_sqm' => $this->getSiteAreaSqm($facility),
                'site_area_tsubo' => null, // Will be calculated
                'notes' => $this->getNotes($facility, $ownershipType),
                'status' => $facility->status === 'approved' ? 'approved' : 'draft',
                'created_by' => $editor->id,
                'updated_by' => $editor->id,
                'approved_by' => $facility->status === 'approved' ? $approver->id : null,
                'approved_at' => $facility->status === 'approved' ? $facility->approved_at : null,
            ];

            // Calculate tsubo from sqm (1 tsubo ≈ 3.306 sqm)
            $baseData['site_area_tsubo'] = round($baseData['site_area_sqm'] / 3.306, 2);

            // Add ownership-specific data
            if ($ownershipType === 'owned') {
                $baseData = array_merge($baseData, $this->getOwnedPropertyData($facility, $baseData['site_area_tsubo']));
            } elseif ($ownershipType === 'leased') {
                $baseData = array_merge($baseData, $this->getLeasedPropertyData($facility));
            } elseif ($ownershipType === 'owned_rental') {
                $baseData = array_merge($baseData, $this->getOwnedRentalPropertyData($facility, $baseData['site_area_tsubo']));
            }

            $landInfoData[] = $baseData;
        }

        // Create land info records
        foreach ($landInfoData as $data) {
            LandInfo::create($data);
        }

        $this->command->info('Created land information for ' . count($landInfoData) . ' facilities.');
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

        if ($ownershipType === 'owned') {
            $notes[] = '自社所有物件として長期的な運営を予定';
            if (fake()->boolean(30)) {
                $notes[] = '将来的な増築・改修計画あり';
            }
        } elseif ($ownershipType === 'leased') {
            $notes[] = '賃貸契約による運営';
            if (fake()->boolean(40)) {
                $notes[] = '契約更新時の条件見直し要検討';
            }
        } elseif ($ownershipType === 'owned_rental') {
            $notes[] = '自社所有物件を第三者に賃貸';
            if (fake()->boolean(20)) {
                $notes[] = '賃料収入による安定的な収益確保';
            }
        }

        if (str_contains($facility->address, '東京') || str_contains($facility->address, '大阪')) {
            if (fake()->boolean(30)) {
                $notes[] = '都市部立地のため地価上昇傾向';
            }
        }

        if (fake()->boolean(20)) {
            $notes[] = '近隣に公共交通機関あり、アクセス良好';
        }

        if (fake()->boolean(15)) {
            $notes[] = '周辺環境は住宅地で静穏';
        }

        return empty($notes) ? null : implode('。', $notes) . '。';
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
        ];
    }

    /**
     * Get leased property specific data
     */
    private function getLeasedPropertyData(Facility $facility): array
    {
        $startDate = fake()->dateTimeBetween('-2 years', '-6 months');
        $endDate = fake()->dateTimeBetween('+6 months', '+5 years');

        return [
            'purchase_price' => null,
            'unit_price_per_tsubo' => null,
            'monthly_rent' => $this->getMonthlyRent($facility),
            'contract_start_date' => $startDate,
            'contract_end_date' => $endDate,
            'auto_renewal' => fake()->randomElement(['yes', 'no']),
            'contract_period_text' => $this->calculateContractPeriod($startDate, $endDate),

            // Management company
            'management_company_name' => $this->getManagementCompanyName(),
            'management_company_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'management_company_address' => $this->getManagementCompanyAddress($facility),
            'management_company_building' => fake()->optional(0.3)->secondaryAddress(),
            'management_company_phone' => fake()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'management_company_fax' => fake()->optional(0.7)->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'management_company_email' => fake()->safeEmail(),
            'management_company_url' => fake()->optional(0.6)->url(),
            'management_company_notes' => fake()->optional(0.4)->text(100),

            // Owner
            'owner_name' => fake()->name(),
            'owner_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'owner_address' => fake()->address(),
            'owner_building' => fake()->optional(0.3)->secondaryAddress(),
            'owner_phone' => fake()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'owner_fax' => fake()->optional(0.5)->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'owner_email' => fake()->optional(0.7)->safeEmail(),
            'owner_url' => fake()->optional(0.2)->url(),
            'owner_notes' => fake()->optional(0.3)->text(100),
        ];
    }

    /**
     * Get owned rental property specific data
     */
    private function getOwnedRentalPropertyData(Facility $facility, float $siteAreaTsubo): array
    {
        $basePrice = $this->getBasePricePerTsubo($facility);
        $purchasePrice = round($basePrice * $siteAreaTsubo);

        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween('+1 year', '+3 years');

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

            // Tenant information
            'owner_name' => $this->getTenantCompanyName(),
            'owner_postal_code' => fake()->regexify('\d{3}-\d{4}'),
            'owner_address' => fake()->address(),
            'owner_building' => fake()->optional(0.3)->secondaryAddress(),
            'owner_phone' => fake()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'owner_fax' => fake()->optional(0.7)->regexify('\d{2,4}-\d{2,4}-\d{4}'),
            'owner_email' => fake()->safeEmail(),
            'owner_url' => fake()->optional(0.8)->url(),
            'owner_notes' => fake()->optional(0.4)->text(100),
        ];
    }

    /**
     * Get base price per tsubo based on location
     */
    private function getBasePricePerTsubo(Facility $facility): int
    {
        if (str_contains($facility->address, '東京都')) {
            if (
                str_contains($facility->address, '千代田区') ||
                str_contains($facility->address, '中央区') ||
                str_contains($facility->address, '港区')
            ) {
                return fake()->numberBetween(800000, 1500000); // Premium Tokyo areas
            }
            return fake()->numberBetween(400000, 800000); // Other Tokyo areas
        }

        if (str_contains($facility->address, '大阪府')) {
            if (
                str_contains($facility->address, '北区') ||
                str_contains($facility->address, '中央区')
            ) {
                return fake()->numberBetween(300000, 600000); // Central Osaka
            }
            return fake()->numberBetween(200000, 400000); // Other Osaka areas
        }

        if (
            str_contains($facility->address, '神奈川県') ||
            str_contains($facility->address, '愛知県')
        ) {
            return fake()->numberBetween(250000, 500000); // Major cities
        }

        return fake()->numberBetween(150000, 300000); // Other areas
    }

    /**
     * Get monthly rent based on facility type and location
     */
    private function getMonthlyRent(Facility $facility): int
    {
        $baseRent = 0;

        // Base rent by facility type
        if (
            str_contains($facility->facility_name, '病院') ||
            str_contains($facility->facility_name, 'リハビリテーションセンター')
        ) {
            $baseRent = fake()->numberBetween(1000000, 3000000);
        } elseif (str_contains($facility->facility_name, '特別養護老人ホーム')) {
            $baseRent = fake()->numberBetween(500000, 1500000);
        } elseif (str_contains($facility->facility_name, 'ケアセンター')) {
            $baseRent = fake()->numberBetween(300000, 1000000);
        } else {
            $baseRent = fake()->numberBetween(200000, 800000);
        }

        // Adjust by location
        if (str_contains($facility->address, '東京都')) {
            $baseRent = round($baseRent * 1.5);
        } elseif (str_contains($facility->address, '大阪府')) {
            $baseRent = round($baseRent * 1.2);
        }

        return $baseRent;
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
                '東京都新宿区西新宿2-8-1',
                '東京都港区赤坂1-12-32',
                '東京都千代田区丸の内1-6-5',
                '東京都渋谷区道玄坂1-2-3',
            ]);
        }

        if (str_contains($facility->address, '大阪府')) {
            return fake()->randomElement([
                '大阪府大阪市北区梅田1-1-3',
                '大阪府大阪市中央区本町4-1-13',
                '大阪府大阪市西区江戸堀1-9-1',
            ]);
        }

        return fake()->address();
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
        ];

        return fake()->randomElement($companies);
    }

    /**
     * Calculate contract period text in Japanese
     */
    private function calculateContractPeriod($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return '';
        }

        $start = is_string($startDate) ? new \DateTime($startDate) : $startDate;
        $end = is_string($endDate) ? new \DateTime($endDate) : $endDate;

        if ($end <= $start) {
            return '';
        }

        $diff = $start->diff($end);
        $years = $diff->y;
        $months = $diff->m;

        $result = '';
        if ($years > 0) {
            $result .= $years . '年';
        }
        if ($months > 0) {
            $result .= $months . 'ヶ月';
        }

        return $result ?: '0ヶ月';
    }
}
