<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        ];

        $companies = [
            '株式会社ケアサポート', '社会福祉法人みらい', '医療法人健康会',
            '株式会社ライフケア', '社会福祉法人希望の里', '医療法人愛心会',
            '株式会社シニアサポート', '社会福祉法人やすらぎ', '医療法人恵愛会'
        ];

        $facilityTypes = [
            'デイサービスセンター', '特別養護老人ホーム', 'グループホーム',
            'サービス付き高齢者向け住宅', '介護老人保健施設', '小規模多機能型居宅介護',
            '訪問介護事業所', '通所介護事業所', '居宅介護支援事業所'
        ];

        $prefecture = $this->faker->randomElement($prefectures);
        $company = $this->faker->randomElement($companies);
        $facilityType = $this->faker->randomElement($facilityTypes);
        
        return [
            'company_name' => $company,
            'office_code' => $this->faker->unique()->numerify('####-####'),
            'designation_number' => $this->faker->numerify('##########'),
            'facility_name' => $facilityType . ' ' . $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'address' => $prefecture . $this->faker->city() . $this->faker->streetAddress(),
            'phone_number' => $this->faker->phoneNumber(),
            'fax_number' => $this->faker->optional(0.7)->phoneNumber(),
            'status' => $this->faker->randomElement([
                Facility::STATUS_APPROVED,
                Facility::STATUS_PENDING_APPROVAL,
                Facility::STATUS_DRAFT
            ]),
            'approved_at' => function (array $attributes) {
                return $attributes['status'] === Facility::STATUS_APPROVED 
                    ? $this->faker->dateTimeBetween('-1 year', 'now')
                    : null;
            },
            'approved_by' => function (array $attributes) {
                return $attributes['status'] === Facility::STATUS_APPROVED 
                    ? User::factory()
                    : null;
            },
            'created_by' => User::factory(),
            'updated_by' => function (array $attributes) {
                return $attributes['created_by'];
            },
        ];
    }

    /**
     * Indicate that the facility is approved.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Facility::STATUS_APPROVED,
                'approved_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * Indicate that the facility is pending approval.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pendingApproval()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Facility::STATUS_PENDING_APPROVAL,
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }

    /**
     * Indicate that the facility is in draft status.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Facility::STATUS_DRAFT,
                'approved_at' => null,
                'approved_by' => null,
            ];
        });
    }
}