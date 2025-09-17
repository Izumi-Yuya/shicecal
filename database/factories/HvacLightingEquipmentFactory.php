<?php

namespace Database\Factories;

use App\Models\HvacLightingEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HvacLightingEquipment>
 */
class HvacLightingEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HvacLightingEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lifeline_equipment_id' => LifelineEquipment::factory(),
            'basic_info' => [
                'hvac_contractor' => $this->faker->company(),
                'maintenance_company' => $this->faker->company(),
                'last_inspection_date' => $this->faker->date(),
                'next_inspection_date' => $this->faker->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
                'system_type' => $this->faker->randomElement(['中央空調', '個別空調', 'ハイブリッド']),
                'lighting_type' => $this->faker->randomElement(['LED', '蛍光灯', 'ハロゲン', 'ミックス']),
            ],
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Create a factory instance with minimal basic info.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_info' => [
                'hvac_contractor' => null,
                'maintenance_company' => null,
                'last_inspection_date' => null,
                'next_inspection_date' => null,
                'system_type' => null,
                'lighting_type' => null,
            ],
            'notes' => null,
        ]);
    }

    /**
     * Create a factory instance with complete basic info.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_info' => [
                'hvac_contractor' => '○○空調株式会社',
                'maintenance_company' => '△△メンテナンス株式会社',
                'last_inspection_date' => '2024-03-15',
                'next_inspection_date' => '2024-09-15',
                'system_type' => '中央空調',
                'lighting_type' => 'LED',
                'capacity_tons' => '50',
                'energy_efficiency' => 'A+',
            ],
            'notes' => '定期点検実施済み。次回点検予定日までに部品交換が必要。',
        ]);
    }
}