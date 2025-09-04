<?php

namespace Database\Factories;

use App\Models\LandInfo;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandInfoFactory extends Factory
{
    protected $model = LandInfo::class;

    public function definition(): array
    {
        $ownershipTypes = ['owned', 'leased', 'owned_rental'];
        $ownershipType = $this->faker->randomElement($ownershipTypes);

        $baseData = [
            'facility_id' => Facility::factory(),
            'ownership_type' => $ownershipType,
            'parking_spaces' => $this->faker->numberBetween(0, 50),
            'site_area_sqm' => $this->faker->randomFloat(2, 100, 1000),
            'site_area_tsubo' => $this->faker->randomFloat(2, 30, 300),
            'notes' => $this->faker->optional()->text(500),
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'approved']),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];

        // Add ownership-specific fields
        if ($ownershipType === 'owned') {
            $purchasePrice = $this->faker->numberBetween(5000000, 50000000);
            $baseData = array_merge($baseData, [
                'purchase_price' => $purchasePrice,
                'unit_price_per_tsubo' => round($purchasePrice / $baseData['site_area_tsubo']),
            ]);
        }

        if (in_array($ownershipType, ['leased', 'owned_rental'])) {
            $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
            $endDate = $this->faker->dateTimeBetween('now', '+5 years');

            $baseData = array_merge($baseData, [
                'monthly_rent' => $this->faker->numberBetween(100000, 1000000),
                'contract_start_date' => $startDate,
                'contract_end_date' => $endDate,
                'auto_renewal' => $this->faker->randomElement(['yes', 'no']),
                'contract_period_text' => $this->calculateContractPeriod($startDate, $endDate),

                // Management company information
                'management_company_name' => $this->faker->optional()->company(),
                'management_company_postal_code' => $this->faker->optional()->regexify('\d{3}-\d{4}'),
                'management_company_address' => $this->faker->optional()->address(),
                'management_company_building' => $this->faker->optional()->secondaryAddress(),
                'management_company_phone' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'management_company_fax' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'management_company_email' => $this->faker->optional()->safeEmail(),
                'management_company_url' => $this->faker->optional()->url(),
                'management_company_notes' => $this->faker->optional()->text(200),

                // Owner information
                'owner_name' => $this->faker->optional()->name(),
                'owner_postal_code' => $this->faker->optional()->regexify('\d{3}-\d{4}'),
                'owner_address' => $this->faker->optional()->address(),
                'owner_building' => $this->faker->optional()->secondaryAddress(),
                'owner_phone' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_fax' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_email' => $this->faker->optional()->safeEmail(),
                'owner_url' => $this->faker->optional()->url(),
                'owner_notes' => $this->faker->optional()->text(200),
            ]);
        }

        return $baseData;
    }

    /**
     * State for owned property
     */
    public function owned(): static
    {
        return $this->state(function (array $attributes) {
            $purchasePrice = $this->faker->numberBetween(10000000, 100000000);
            $siteAreaTsubo = $attributes['site_area_tsubo'] ?? $this->faker->randomFloat(2, 50, 200);

            return [
                'ownership_type' => 'owned',
                'purchase_price' => $purchasePrice,
                'unit_price_per_tsubo' => round($purchasePrice / $siteAreaTsubo),
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
        });
    }

    /**
     * State for leased property
     */
    public function leased(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
            $endDate = $this->faker->dateTimeBetween('now', '+3 years');

            return [
                'ownership_type' => 'leased',
                'purchase_price' => null,
                'unit_price_per_tsubo' => null,
                'monthly_rent' => $this->faker->numberBetween(200000, 800000),
                'contract_start_date' => $startDate,
                'contract_end_date' => $endDate,
                'auto_renewal' => $this->faker->randomElement(['yes', 'no']),
                'contract_period_text' => $this->calculateContractPeriod($startDate, $endDate),

                // Management company information
                'management_company_name' => $this->faker->company(),
                'management_company_postal_code' => $this->faker->regexify('\d{3}-\d{4}'),
                'management_company_address' => $this->faker->address(),
                'management_company_building' => $this->faker->optional()->secondaryAddress(),
                'management_company_phone' => $this->faker->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'management_company_fax' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'management_company_email' => $this->faker->safeEmail(),
                'management_company_url' => $this->faker->optional()->url(),
                'management_company_notes' => $this->faker->optional()->text(200),

                // Owner information
                'owner_name' => $this->faker->name(),
                'owner_postal_code' => $this->faker->regexify('\d{3}-\d{4}'),
                'owner_address' => $this->faker->address(),
                'owner_building' => $this->faker->optional()->secondaryAddress(),
                'owner_phone' => $this->faker->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_fax' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_email' => $this->faker->optional()->safeEmail(),
                'owner_url' => $this->faker->optional()->url(),
                'owner_notes' => $this->faker->optional()->text(200),
            ];
        });
    }

    /**
     * State for owned rental property
     */
    public function ownedRental(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
            $endDate = $this->faker->dateTimeBetween('now', '+3 years');
            $purchasePrice = $this->faker->numberBetween(20000000, 80000000);
            $siteAreaTsubo = $attributes['site_area_tsubo'] ?? $this->faker->randomFloat(2, 50, 200);

            return [
                'ownership_type' => 'owned_rental',
                'purchase_price' => $purchasePrice,
                'unit_price_per_tsubo' => round($purchasePrice / $siteAreaTsubo),
                'monthly_rent' => $this->faker->numberBetween(300000, 1000000),
                'contract_start_date' => $startDate,
                'contract_end_date' => $endDate,
                'auto_renewal' => $this->faker->randomElement(['yes', 'no']),
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

                // Tenant information in owner fields
                'owner_name' => $this->faker->name(),
                'owner_postal_code' => $this->faker->regexify('\d{3}-\d{4}'),
                'owner_address' => $this->faker->address(),
                'owner_building' => $this->faker->optional()->secondaryAddress(),
                'owner_phone' => $this->faker->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_fax' => $this->faker->optional()->regexify('\d{2,4}-\d{2,4}-\d{4}'),
                'owner_email' => $this->faker->optional()->safeEmail(),
                'owner_url' => $this->faker->optional()->url(),
                'owner_notes' => $this->faker->optional()->text(200),
            ];
        });
    }

    /**
     * State for approved land info
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * State for pending approval land info
     */
    public function pendingApproval(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }

    /**
     * State for draft land info
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }

    /**
     * Calculate contract period text
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
