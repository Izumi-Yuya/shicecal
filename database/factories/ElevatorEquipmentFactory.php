<?php

namespace Database\Factories;

use App\Models\ElevatorEquipment;
use App\Models\LifelineEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ElevatorEquipment>
 */
class ElevatorEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ElevatorEquipment::class;

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
                'manufacturer' => $this->faker->randomElement(['三菱電機', '東芝エレベータ', '日立ビルシステム', 'オーチス', 'フジテック']),
                'model' => $this->faker->bothify('ELV-####'),
                'capacity' => $this->faker->randomElement([8, 11, 15, 17, 20, 24]),
                'installation_year' => $this->faker->numberBetween(2000, 2024),
                'elevator_type' => $this->faker->randomElement(['乗用', '荷物用', '人荷共用', '非常用']),
                'drive_system' => $this->faker->randomElement(['ロープ式', '油圧式', 'リニアモーター式']),
                'floors_served' => $this->faker->numberBetween(2, 20),
                'speed' => $this->faker->randomFloat(1, 60, 300) . 'm/分',
                'door_type' => $this->faker->randomElement(['センターオープン', 'サイドオープン', '手動']),
                'control_system' => $this->faker->randomElement(['VVVF制御', 'AC制御', 'DC制御']),
            ],
            'maintenance_info' => [
                'company' => $this->faker->randomElement(['三菱電機ビルテクノサービス', '東芝エレベータ', '日立ビルシステム', 'オーチス・エレベータサービス']),
                'contract_type' => $this->faker->randomElement(['フルメンテナンス', 'POG', 'スポット']),
                'last_inspection_date' => $this->faker->date(),
                'next_inspection_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
                'maintenance_frequency' => $this->faker->randomElement(['月1回', '月2回', '隔月']),
                'emergency_contact' => $this->faker->phoneNumber,
                'maintenance_cost_annual' => $this->faker->numberBetween(500000, 2000000),
            ],
            'safety_info' => [
                'inspection_agency' => $this->faker->randomElement(['日本エレベーター協会', '建築設備検査機構', '昇降機検査センター']),
                'certificate_number' => $this->faker->bothify('ELV-####-####'),
                'certificate_expiry' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
                'safety_devices' => $this->faker->randomElement(['非常停止装置', '地震時管制運転装置', '火災時管制運転装置']),
                'emergency_phone' => $this->faker->randomElement(['設置済み', '未設置']),
                'seismic_sensor' => $this->faker->randomElement(['有', '無']),
                'fire_service' => $this->faker->randomElement(['有', '無']),
            ],
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Create an elevator equipment with minimal data.
     */
    public function minimal()
    {
        return $this->state(function (array $attributes) {
            return [
                'basic_info' => [
                    'manufacturer' => '三菱電機',
                    'model' => 'ELV-2024',
                    'capacity' => 15,
                    'installation_year' => 2020,
                ],
                'maintenance_info' => null,
                'safety_info' => null,
                'notes' => null,
            ];
        });
    }

    /**
     * Create an elevator equipment with full maintenance information.
     */
    public function withFullMaintenance()
    {
        return $this->state(function (array $attributes) {
            return [
                'maintenance_info' => [
                    'company' => '三菱電機ビルテクノサービス',
                    'contract_type' => 'フルメンテナンス',
                    'last_inspection_date' => '2024-08-15',
                    'next_inspection_date' => '2024-11-15',
                ],
            ];
        });
    }

    /**
     * Create an elevator equipment with safety certification.
     */
    public function withSafetyCertification()
    {
        return $this->state(function (array $attributes) {
            return [
                'safety_info' => [
                    'inspection_agency' => '日本エレベーター協会',
                    'certificate_number' => 'ELV-2024-0001',
                    'certificate_expiry' => '2026-12-31',
                    'safety_devices' => '非常停止装置、地震時管制運転装置',
                ],
            ];
        });
    }
}
