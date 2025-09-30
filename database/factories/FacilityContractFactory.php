<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FacilityContract>
 */
class FacilityContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'facility_id' => \App\Models\Facility::factory(),
            'others_company_name' => $this->faker->company(),
            'others_contract_type' => $this->faker->randomElement(['保守契約', '清掃契約', '警備契約', 'その他']),
            'others_contract_content' => $this->faker->text(200),
            'others_auto_renewal' => $this->faker->randomElement(['あり', 'なし', '条件付き']),
            'others_auto_renewal_details' => $this->faker->optional()->text(100),
            'others_contract_start_date' => $this->faker->date(),
            'others_cancellation_conditions' => $this->faker->optional()->text(100),
            'others_renewal_notice_period' => $this->faker->optional()->randomElement(['1ヶ月前', '3ヶ月前', '6ヶ月前']),
            'others_contract_end_date' => $this->faker->optional()->date(),
            'others_other_matters' => $this->faker->optional()->text(200),
            'others_amount' => $this->faker->optional()->numberBetween(10000, 1000000),
            'others_contact_info' => $this->faker->optional()->text(100),
            'meal_service_data' => $this->faker->optional()->passthrough([
                'company_name' => $this->faker->company(),
                'management_fee' => $this->faker->numberBetween(10000, 100000),
                'contract_content' => $this->faker->text(200),
                'breakfast_price' => $this->faker->numberBetween(200, 800),
                'contract_start_date' => $this->faker->date(),
                'lunch_price' => $this->faker->numberBetween(300, 1000),
                'contract_type' => $this->faker->randomElement(['給食業務委託契約書', '給食サービス契約書']),
                'dinner_price' => $this->faker->numberBetween(400, 1200),
                'auto_renewal' => $this->faker->randomElement(['なし', 'あり']),
                'auto_renewal_details' => $this->faker->optional()->randomElement([
                    '1年毎自動更新、3ヶ月前通知で解約可能',
                    '契約満了時に双方協議の上更新',
                    '2年毎自動更新、6ヶ月前通知必要',
                    '単年契約、毎年更新手続き必要'
                ]),
                'snack_price' => $this->faker->numberBetween(100, 300),
                'cancellation_conditions' => $this->faker->optional()->text(100),
                'event_meal_price' => $this->faker->numberBetween(500, 1500),
                'renewal_notice_period' => $this->faker->optional()->randomElement(['1ヶ月前', '3ヶ月前', '6ヶ月前']),
                'staff_meal_price' => $this->faker->numberBetween(300, 800),
                'other_matters' => $this->faker->optional()->text(200),
            ]),
            'parking_data' => null,
        ];
    }
}
