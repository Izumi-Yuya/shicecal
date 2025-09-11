<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceHistory>
 */
class MaintenanceHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MaintenanceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $maintenanceTypes = [
            'エアコン修理',
            '照明器具交換',
            '水道管修理',
            '外壁塗装',
            '屋根修理',
            '床材張替え',
            '窓ガラス交換',
            '電気設備点検',
            'ドア修理',
            '防水工事',
            '給湯器交換',
            '換気扇清掃',
            '配管工事',
            '内装修繕',
            '外構工事',
        ];

        $contractors = [
            '株式会社山田工務店',
            '田中電気工事',
            '佐藤建設',
            '鈴木設備',
            '高橋塗装',
            '渡辺リフォーム',
            '伊藤工業',
            '中村建築',
            '小林電設',
            '加藤住宅設備',
        ];

        return [
            'facility_id' => Facility::factory(),
            'maintenance_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'content' => $this->faker->randomElement($maintenanceTypes).'を実施。'.$this->faker->sentence(10),
            'cost' => $this->faker->optional(0.8)->randomFloat(2, 5000, 500000),
            'contractor' => $this->faker->optional(0.7)->randomElement($contractors),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the maintenance was expensive.
     */
    public function expensive()
    {
        return $this->state(function (array $attributes) {
            return [
                'cost' => $this->faker->randomFloat(2, 100000, 1000000),
            ];
        });
    }

    /**
     * Indicate that the maintenance was recent.
     */
    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'maintenance_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the maintenance has no cost information.
     */
    public function noCost()
    {
        return $this->state(function (array $attributes) {
            return [
                'cost' => null,
            ];
        });
    }

    /**
     * Indicate that the maintenance has no contractor information.
     */
    public function noContractor()
    {
        return $this->state(function (array $attributes) {
            return [
                'contractor' => null,
            ];
        });
    }
}
