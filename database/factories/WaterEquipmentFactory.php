<?php

namespace Database\Factories;

use App\Models\LifelineEquipment;
use App\Models\WaterEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WaterEquipment>
 */
class WaterEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WaterEquipment::class;

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
                'water_contractor' => $this->faker->company() . '水道株式会社',
                'maintenance_company' => $this->faker->company() . '設備メンテナンス',
                'maintenance_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'inspection_report' => 'water_inspection_report_' . $this->faker->date() . '.pdf',
            ],
            'notes' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Create a water equipment with minimal data.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_info' => [
                'water_contractor' => null,
                'maintenance_company' => null,
                'maintenance_date' => null,
                'inspection_report' => null,
            ],
            'notes' => null,
        ]);
    }

    /**
     * Create a water equipment with complete data.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_info' => [
                'water_contractor' => '東京都水道局',
                'maintenance_company' => '水道設備メンテナンス株式会社',
                'maintenance_date' => now()->subMonths(3)->format('Y-m-d'),
                'inspection_report' => 'water_inspection_report_' . now()->format('Y_m_d') . '.pdf',
            ],
            'notes' => '定期点検実施済み。次回点検予定：' . now()->addMonths(6)->format('Y年m月'),
        ]);
    }
}