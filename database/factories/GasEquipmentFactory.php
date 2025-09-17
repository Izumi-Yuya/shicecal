<?php

namespace Database\Factories;

use App\Models\GasEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GasEquipment>
 */
class GasEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = GasEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'lifeline_equipment_id' => LifelineEquipment::factory(),
            'basic_info' => [
                'gas_supplier' => $this->faker->company() . 'ガス',
                'safety_management_company' => $this->faker->company() . '保安管理株式会社',
                'maintenance_inspection_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'inspection_report_pdf' => 'gas_inspection_report_' . $this->faker->year() . '.pdf',
            ],
            'notes' => $this->faker->optional(0.7)->text(500),
        ];
    }

    /**
     * Create a gas equipment with minimal data.
     */
    public function minimal()
    {
        return $this->state(function (array $attributes) {
            return [
                'basic_info' => [
                    'gas_supplier' => '',
                    'safety_management_company' => '',
                    'maintenance_inspection_date' => '',
                    'inspection_report_pdf' => '',
                ],
                'notes' => '',
            ];
        });
    }

    /**
     * Create a gas equipment with complete data.
     */
    public function complete()
    {
        return $this->state(function (array $attributes) {
            return [
                'basic_info' => [
                    'gas_supplier' => '東京ガス株式会社',
                    'safety_management_company' => '○○保安管理株式会社',
                    'maintenance_inspection_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                    'inspection_report_pdf' => 'gas_inspection_report_2024.pdf',
                ],
                'notes' => 'ガス設備の定期点検を実施済み。次回点検予定日: ' . $this->faker->dateTimeBetween('now', '+1 year')->format('Y年m月d日'),
            ];
        });
    }
}
