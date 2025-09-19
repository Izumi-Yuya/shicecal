<?php

namespace Database\Factories;

use App\Models\FacilityInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityInfo>
 */
class FacilityInfoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FacilityInfo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyNames = [
            '株式会社シセ・コーポレーション',
            '医療法人社団シセ会',
            '社会福祉法人シセ福祉会',
            '株式会社シセケア',
            '有限会社シセサービス',
        ];

        $facilityTypes = [
            '有料老人ホーム',
            'グループホーム',
            'デイサービスセンター',
            '訪問看護ステーション',
            'ヘルパーステーション',
        ];

        $prefectures = [
            '東京都', '神奈川県', '千葉県', '埼玉県', '大阪府',
            '愛知県', '兵庫県', '福岡県', '北海道', '宮城県',
        ];

        $cities = [
            '新宿区', '渋谷区', '港区', '中央区', '千代田区',
            '横浜市', '川崎市', '千葉市', 'さいたま市', '大阪市',
        ];

        $facilityType = $this->faker->randomElement($facilityTypes);
        $prefecture = $this->faker->randomElement($prefectures);
        $city = $this->faker->randomElement($cities);

        return [
            'company_name' => $this->faker->randomElement($companyNames),
            'office_code' => $this->faker->unique()->numerify('####'),
            'designation_number' => $this->faker->numerify('##########'),
            'facility_name' => $facilityType.' '.$this->faker->lastName.$this->faker->randomElement(['の里', 'ガーデン', 'ホーム', 'ケア', 'プラザ']),
            'postal_code' => $this->faker->postcode,
            'address' => $prefecture.$city.$this->faker->streetAddress,
            'building_name' => $this->faker->optional(0.3)->lastName.$this->faker->optional(0.3)->randomElement(['ビル', 'マンション', 'ハイツ', 'コーポ']).$this->faker->optional(0.5)->numerify('###号室'),
            'phone_number' => $this->faker->phoneNumber,
            'fax_number' => $this->faker->optional(0.7)->phoneNumber,
            'toll_free_number' => $this->faker->optional(0.3)->numerify('0120-###-###'),
            'email' => $this->faker->optional(0.8)->companyEmail,
            'website_url' => $this->faker->optional(0.5)->url,
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'approved']),
            'approved_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'approved_by' => User::factory(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the facility is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the facility is pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the facility is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Create a facility with specific company name
     */
    public function withCompany(string $companyName): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => $companyName,
        ]);
    }

    /**
     * Create a facility with complete contact information
     */
    public function withCompleteContact(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => $this->faker->phoneNumber,
            'fax_number' => $this->faker->phoneNumber,
            'toll_free_number' => $this->faker->numerify('0120-###-###'),
            'email' => $this->faker->companyEmail,
            'website_url' => $this->faker->url,
        ]);
    }
}
